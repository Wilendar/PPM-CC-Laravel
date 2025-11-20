$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== FETCHING LATEST DEBUG LOGS ===" -ForegroundColor Cyan

$logs = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cd domains/ppm.mpptrade.pl/public_html && tail -600 storage/logs/laravel.log"

Write-Host "`nFiltering FAZA 5.2 DEBUG SAVE logs..." -ForegroundColor Yellow

$debugLogs = $logs | Select-String -Pattern "FAZA 5.2 DEBUG SAVE" -Context 0

if ($debugLogs) {
    Write-Host "`n=== LATEST DEBUG LOGS ===" -ForegroundColor Green
    $debugLogs | Select-Object -Last 20 | ForEach-Object {
        if ($_.Line -match "AFTER updateShopTaxRateOverride") {
            Write-Host $_.Line -ForegroundColor Cyan
        } elseif ($_.Line -match "saveShopSpecificData") {
            Write-Host $_.Line -ForegroundColor Magenta
        } else {
            Write-Host $_.Line -ForegroundColor White
        }
    }
} else {
    Write-Host "`nNO DEBUG LOGS FOUND!" -ForegroundColor Red
}

Write-Host "`n=== CHECKING FOR saveShopSpecificData CALLS ===" -ForegroundColor Yellow
$saveLogs = $logs | Select-String -Pattern "saveShopSpecificData" | Select-Object -Last 10

if ($saveLogs) {
    Write-Host "Found saveShopSpecificData logs:" -ForegroundColor Green
    $saveLogs | ForEach-Object { Write-Host $_.Line -ForegroundColor Magenta }
} else {
    Write-Host "NO saveShopSpecificData logs - THIS IS THE PROBLEM!" -ForegroundColor Red
}
