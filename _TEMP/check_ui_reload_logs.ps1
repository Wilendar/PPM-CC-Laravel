$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== CHECKING UI RELOAD DEBUG LOGS ===" -ForegroundColor Cyan

$logs = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cd domains/ppm.mpptrade.pl/public_html && tail -500 storage/logs/laravel.log"

Write-Host "`nSearching for [FAZA 5.2 UI RELOAD] logs..." -ForegroundColor Yellow

$reloadLogs = $logs | Select-String -Pattern "FAZA 5.2 UI RELOAD" -Context 0

if ($reloadLogs) {
    Write-Host "`n=== UI RELOAD LOGS FOUND ===" -ForegroundColor Green
    $reloadLogs | ForEach-Object {
        Write-Host $_.Line -ForegroundColor White
    }
} else {
    Write-Host "`nNO UI RELOAD LOGS - loadShopDataToForm() NOT CALLED!" -ForegroundColor Red
}

Write-Host "`n=== Checking saveAllPendingChanges logs ===" -ForegroundColor Yellow
$saveLogs = $logs | Select-String -Pattern "All pending changes saved|saveAllPendingChanges" | Select-Object -Last 5

if ($saveLogs) {
    Write-Host "Found save logs:" -ForegroundColor Green
    $saveLogs | ForEach-Object { Write-Host $_.Line -ForegroundColor Cyan }
} else {
    Write-Host "NO save logs found" -ForegroundColor Red
}
