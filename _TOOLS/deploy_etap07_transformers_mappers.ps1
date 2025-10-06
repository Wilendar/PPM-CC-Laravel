# 🚀 Deploy ETAP_07 FAZA 1D - Transformers & Mappers to Hostido
# Upload 5 plików PHP (ProductTransformer, CategoryTransformer, 3x Mappers)

# Konfiguracja
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostidoHost = "host379076@host379076.hostido.net.pl"
$HostidoPort = 64321
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

# Kolory PowerShell 7
$Green = "`e[32m"
$Red = "`e[31m"
$Cyan = "`e[36m"
$Yellow = "`e[33m"
$Reset = "`e[0m"

function Write-Info { param([string]$Text) Write-Host "${Cyan}$Text${Reset}" }
function Write-Success { param([string]$Text) Write-Host "${Green}$Text${Reset}" }
function Write-Error { param([string]$Text) Write-Host "${Red}$Text${Reset}" }
function Write-Warning { param([string]$Text) Write-Host "${Yellow}$Text${Reset}" }

Write-Info "🚀 Deploy ETAP_07 FAZA 1D - Transformers & Mappers"
Write-Info "=================================================="

# Sprawdz czy pscp jest dostepne
$pscp = "C:\Program Files\PuTTY\pscp.exe"
if (!(Test-Path $pscp)) {
    Write-Error "❌ pscp.exe nie znalezione w: $pscp"
    Write-Warning "💡 Zainstaluj PuTTY"
    exit 1
}

Write-Success "✅ pscp.exe znalezione"

# Lista plikow do uploadu
$files = @(
    @{
        Local = "$LocalPath\app\Services\PrestaShop\ProductTransformer.php"
        Remote = "domains/ppm.mpptrade.pl/public_html/app/Services/PrestaShop/ProductTransformer.php"
    },
    @{
        Local = "$LocalPath\app\Services\PrestaShop\CategoryTransformer.php"
        Remote = "domains/ppm.mpptrade.pl/public_html/app/Services/PrestaShop/CategoryTransformer.php"
    },
    @{
        Local = "$LocalPath\app\Services\PrestaShop\CategoryMapper.php"
        Remote = "domains/ppm.mpptrade.pl/public_html/app/Services/PrestaShop/CategoryMapper.php"
    },
    @{
        Local = "$LocalPath\app\Services\PrestaShop\PriceGroupMapper.php"
        Remote = "domains/ppm.mpptrade.pl/public_html/app/Services/PrestaShop/PriceGroupMapper.php"
    },
    @{
        Local = "$LocalPath\app\Services\PrestaShop\WarehouseMapper.php"
        Remote = "domains/ppm.mpptrade.pl/public_html/app/Services/PrestaShop/WarehouseMapper.php"
    }
)

# Upload kazdego pliku
$uploadedCount = 0
foreach ($file in $files) {
    if (!(Test-Path $file.Local)) {
        Write-Error "❌ Nie znaleziono pliku: $($file.Local)"
        continue
    }

    $fileName = Split-Path $file.Local -Leaf
    Write-Info "📤 Uploading: $fileName"

    try {
        $remotePath = "${HostidoHost}:$($file.Remote)"
        & $pscp -i $HostidoKey -P $HostidoPort $file.Local $remotePath

        if ($LASTEXITCODE -eq 0) {
            Write-Success "✅ Uploaded: $fileName"
            $uploadedCount++
        } else {
            Write-Error "❌ Blad uploadu: $fileName"
        }
    } catch {
        Write-Error "❌ Wyjatek podczas uploadu: $_"
    }
}

Write-Info ""
Write-Info "📊 PODSUMOWANIE"
Write-Info "=================================================="
Write-Info "Uploaded: $uploadedCount / $($files.Count)"

if ($uploadedCount -eq $files.Count) {
    Write-Success "🎉 Wszystkie pliki uploadowane pomyslnie!"
    Write-Info ""
    Write-Info "🔧 NASTEPNE KROKI:"
    Write-Info "1. Transformers i Mappers sa gotowe"
    Write-Info "2. Kontynuuj z FAZA 1C (Sync Strategies)"
    Write-Info "3. Pamietaj o composer dump-autoload na serwerze"
    exit 0
} else {
    Write-Error "⚠️ Nie wszystkie pliki zostaly uploadowane!"
    exit 1
}
