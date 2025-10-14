# Analyze inline styles in PPM-CC-Laravel project
$basePath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire"

Write-Host "`n=== INLINE STYLES ANALYSIS ===" -ForegroundColor Cyan
Write-Host "Scanning: $basePath`n" -ForegroundColor Gray

$files = Get-ChildItem -Path $basePath -Filter "*.blade.php" -Recurse

$results = @()

foreach ($file in $files) {
    $count = (Select-String -Path $file.FullName -Pattern 'style="' -AllMatches).Matches.Count
    if ($count -gt 0) {
        $relativePath = $file.FullName.Replace($basePath + "\", "")
        $results += [PSCustomObject]@{
            File = $relativePath
            Count = $count
            FullPath = $file.FullName
        }
    }
}

# Sort by count descending
$results = $results | Sort-Object -Property Count -Descending

Write-Host "FILES WITH INLINE STYLES:" -ForegroundColor Yellow
Write-Host ("=" * 80) -ForegroundColor Gray

foreach ($result in $results) {
    $color = if ($result.Count -gt 20) { "Red" } elseif ($result.Count -gt 10) { "Yellow" } else { "Green" }
    Write-Host ("{0,3} occurrences - {1}" -f $result.Count, $result.File) -ForegroundColor $color
}

Write-Host "`n" -ForegroundColor Gray
Write-Host ("=" * 80) -ForegroundColor Gray
Write-Host "TOTAL: $($results.Count) files with $($results | Measure-Object -Property Count -Sum | Select-Object -ExpandProperty Sum) inline styles" -ForegroundColor Cyan

# Export to CSV for detailed analysis
$csvPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\_REPORTS\inline_styles_analysis.csv"
$results | Export-Csv -Path $csvPath -NoTypeInformation -Encoding UTF8
Write-Host "`nDetailed report saved to: $csvPath" -ForegroundColor Green