$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "Fetching debug logs from production..." -ForegroundColor Cyan

$logs = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cd domains/ppm.mpptrade.pl/public_html && tail -500 storage/logs/laravel.log"

Write-Host "`nFiltering FAZA 5.2 DEBUG SAVE logs..." -ForegroundColor Yellow

$debugLogs = $logs | Select-String -Pattern "FAZA 5.2 DEBUG SAVE" -Context 1

if ($debugLogs) {
    Write-Host "`n=== DEBUG LOGS FOUND ===" -ForegroundColor Green
    $debugLogs | ForEach-Object { Write-Host $_.Line -ForegroundColor White }
} else {
    Write-Host "`nNO DEBUG LOGS FOUND - checking if any logs exist..." -ForegroundColor Red
    $allLogs = $logs | Select-String -Pattern "ProductForm" | Select-Object -First 10
    $allLogs | ForEach-Object { Write-Host $_.Line -ForegroundColor Gray }
}

Write-Host "`nDone." -ForegroundColor Cyan
