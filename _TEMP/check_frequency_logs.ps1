$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "Checking Laravel logs for frequency save debugging..." -ForegroundColor Cyan
Write-Host ""

# Get last 150 lines and filter for our debug messages
$output = plink -ssh host379076@host379076.hostido.net.nl -P 64321 -i $HostidoKey -batch @"
cd domains/ppm.mpptrade.pl/public_html && tail -150 storage/logs/laravel.log | grep -A 3 -E '(saveSyncConfiguration CALLED|BEFORE updateOrCreate|AFTER updateOrCreate|mount\(\) CALLED|loadSyncConfigurationFromDatabase)'
"@

if ($output) {
    Write-Host $output
} else {
    Write-Host "No debug logs found yet. User needs to:" -ForegroundColor Yellow
    Write-Host "1. Open https://ppm.mpptrade.pl/admin/sync" -ForegroundColor White
    Write-Host "2. Change frequency: hourly → daily" -ForegroundColor White
    Write-Host "3. Click 'Zapisz konfigurację'" -ForegroundColor White
    Write-Host "4. Run this script again" -ForegroundColor White
}
