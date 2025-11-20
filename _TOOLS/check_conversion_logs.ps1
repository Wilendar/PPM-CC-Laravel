$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING LARAVEL LOGS ===" -ForegroundColor Cyan
Write-Host ""

# Get last 100 lines from Laravel log
$logOutput = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -n 100 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log"

# Filter for conversion-related entries
$conversionLines = $logOutput | Select-String -Pattern "handleConvertVariants|VariantConversionService|Conversion" -Context 3

if ($conversionLines) {
    Write-Host "Found conversion-related log entries:" -ForegroundColor Yellow
    Write-Host ""
    $conversionLines | ForEach-Object { Write-Host $_.Line }
} else {
    Write-Host "No conversion-related entries found in last 100 lines" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Last 20 lines of log:" -ForegroundColor Cyan
    $logOutput | Select-Object -Last 20 | ForEach-Object { Write-Host $_ }
}
