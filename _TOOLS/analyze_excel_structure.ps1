# Analiza struktury pliku Excel dla systemu dopasowan
# Encoding: UTF-8 with BOM
$OutputEncoding = [System.Text.Encoding]::UTF8

param(
    [string]$ExcelPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\References\Produkty_Przyklad_Large.xlsx"
)

Write-Host "`n=== ANALIZA STRUKTURY EXCEL ===" -ForegroundColor Cyan

# Sprawdz czy plik istnieje
if (-not (Test-Path $ExcelPath)) {
    Write-Host "BLAD: Plik nie istnieje: $ExcelPath" -ForegroundColor Red
    exit 1
}

# Sprawdz czy modul ImportExcel jest zainstalowany
if (-not (Get-Module -ListAvailable -Name ImportExcel)) {
    Write-Host "Instalowanie modulu ImportExcel..." -ForegroundColor Yellow
    Install-Module -Name ImportExcel -Force -Scope CurrentUser
}

Import-Module ImportExcel

try {
    # Odczytaj Excel
    Write-Host "`n1. Ladowanie pliku Excel..." -ForegroundColor Green
    $excelData = Import-Excel -Path $ExcelPath

    # Podstawowe statystyki
    Write-Host "`n2. PODSTAWOWE STATYSTYKI:" -ForegroundColor Green
    Write-Host "   Liczba wierszy: $($excelData.Count)" -ForegroundColor White

    # Kolumny
    Write-Host "`n3. KOLUMNY W PLIKU:" -ForegroundColor Green
    $columns = $excelData[0].PSObject.Properties.Name
    $columns | ForEach-Object {
        Write-Host "   - $_" -ForegroundColor White
    }
    Write-Host "   TOTAL: $($columns.Count) kolumn" -ForegroundColor Yellow

    # Identyfikacja kolumn produktowych vs dopasowania
    Write-Host "`n4. ANALIZA TYPOW KOLUMN:" -ForegroundColor Green

    $productColumns = @('SKU', 'Nazwa', 'Name', 'Parts Name', 'U8 Code', 'MRF CODE', 'Opis', 'Description')
    $foundProductColumns = $columns | Where-Object { $_ -in $productColumns }

    Write-Host "   Kolumny produktowe:" -ForegroundColor Cyan
    $foundProductColumns | ForEach-Object {
        Write-Host "     - $_" -ForegroundColor White
    }

    # Kolumny dopasowania (wszystkie pozostale)
    $vehicleColumns = $columns | Where-Object { $_ -notin $productColumns }
    Write-Host "`n   Kolumny dopasowania (modele pojazdow):" -ForegroundColor Cyan
    Write-Host "     Liczba modeli: $($vehicleColumns.Count)" -ForegroundColor Yellow
    $vehicleColumns | Select-Object -First 10 | ForEach-Object {
        Write-Host "     - $_" -ForegroundColor White
    }
    if ($vehicleColumns.Count -gt 10) {
        Write-Host "     ... i $($vehicleColumns.Count - 10) wiecej" -ForegroundColor Gray
    }

    # Analiza wartosci dopasowania
    Write-Host "`n5. ANALIZA WARTOSCI DOPASOWANIA:" -ForegroundColor Green
    $matchingValues = @{}

    foreach ($row in ($excelData | Select-Object -First 50)) {
        foreach ($col in $vehicleColumns) {
            $value = $row.$col
            if ($null -ne $value -and $value -ne '') {
                if (-not $matchingValues.ContainsKey($value)) {
                    $matchingValues[$value] = 0
                }
                $matchingValues[$value]++
            }
        }
    }

    Write-Host "   Znalezione wartosci (pierwsze 50 wierszy):" -ForegroundColor Cyan
    $matchingValues.GetEnumerator() | Sort-Object Value -Descending | ForEach-Object {
        Write-Host "     '$($_.Key)' - wystapienia: $($_.Value)" -ForegroundColor White
    }

    # Przykladowe dane
    Write-Host "`n6. PRZYKLADOWE DANE (pierwsze 3 wiersze):" -ForegroundColor Green
    $rowNum = 1
    $excelData | Select-Object -First 3 | ForEach-Object {
        Write-Host "`n   Wiersz ${rowNum}:" -ForegroundColor Yellow

        # Dane produktu
        foreach ($col in $foundProductColumns) {
            if ($_.PSObject.Properties.Name -contains $col) {
                Write-Host "     ${col} : $($_.$col)" -ForegroundColor White
            }
        }

        # Dopasowania (tylko niepuste)
        $matches = @()
        foreach ($col in $vehicleColumns) {
            if ($_.PSObject.Properties.Name -contains $col) {
                $value = $_.$col
                if ($null -ne $value -and $value -ne '') {
                    $matches += "${col} = ${value}"
                }
            }
        }

        if ($matches.Count -gt 0) {
            Write-Host "     Dopasowania:" -ForegroundColor Cyan
            $matches | Select-Object -First 5 | ForEach-Object {
                Write-Host "       - $_" -ForegroundColor White
            }
            if ($matches.Count -gt 5) {
                Write-Host "       ... i $($matches.Count - 5) wiecej" -ForegroundColor Gray
            }
        } else {
            Write-Host "     Dopasowania: BRAK" -ForegroundColor Gray
        }

        $rowNum++
    }

    # Statystyki pokrycia
    Write-Host "`n7. STATYSTYKI POKRYCIA DOPASOWAN:" -ForegroundColor Green
    $totalCells = $excelData.Count * $vehicleColumns.Count
    $filledCells = 0

    foreach ($row in $excelData) {
        foreach ($col in $vehicleColumns) {
            if ($null -ne $row.$col -and $row.$col -ne '') {
                $filledCells++
            }
        }
    }

    $coveragePercent = [math]::Round(($filledCells / $totalCells) * 100, 2)

    Write-Host "   Calkowita liczba komorek dopasowan: $totalCells" -ForegroundColor White
    Write-Host "   Wypelnione komorki: $filledCells" -ForegroundColor White
    Write-Host "   Pokrycie: $coveragePercent%" -ForegroundColor Yellow

    # Export do JSON dla dalszej analizy
    $analysisResult = @{
        FilePath = $ExcelPath
        TotalRows = $excelData.Count
        TotalColumns = $columns.Count
        ProductColumns = $foundProductColumns
        VehicleColumns = $vehicleColumns
        MatchingValues = $matchingValues
        CoveragePercent = $coveragePercent
        SampleData = $excelData | Select-Object -First 3
    }

    $jsonPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\_TEMP\excel_analysis.json"
    $analysisResult | ConvertTo-Json -Depth 10 | Out-File -FilePath $jsonPath -Encoding UTF8

    Write-Host "`n8. WYNIK ZAPISANY DO:" -ForegroundColor Green
    Write-Host "   $jsonPath" -ForegroundColor White

} catch {
    Write-Host "`nBLAD: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host $_.ScriptStackTrace -ForegroundColor Gray
    exit 1
}

Write-Host "`n=== ANALIZA ZAKONCZONA ===" -ForegroundColor Cyan
