# Test Frequency Bug with Detailed Logging
# CRITICAL BUG: Frequency reverts to "hourly" despite session guard

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "=== FREQUENCY BUG TEST WITH LOGGING ===" -ForegroundColor Cyan
Write-Host ""

# Step 1: Clear existing logs to start fresh
Write-Host "1. Clearing old logs..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "cd $RemotePath && echo '' > storage/logs/laravel.log"
Write-Host "   ✅ Logs cleared" -ForegroundColor Green
Write-Host ""

# Step 2: Check current value
Write-Host "2. Current frequency value:" -ForegroundColor Yellow
plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "cd $RemotePath && php artisan tinker --execute=`"echo \App\Models\SystemSetting::where('key', 'sync.schedule.frequency')->value('value') ?? 'NOT FOUND'; echo PHP_EOL;`""
Write-Host ""

# Step 3: Instructions for manual test
Write-Host "3. MANUAL TEST STEPS:" -ForegroundColor Magenta
Write-Host "   a) Open: https://ppm.mpptrade.pl/admin/shops/sync" -ForegroundColor White
Write-Host "   b) Expand 'Pokaż konfigurację'" -ForegroundColor White
Write-Host "   c) Change frequency from 'Co godzinę' to 'Codziennie'" -ForegroundColor White
Write-Host "   d) Click 'Zapisz konfigurację'" -ForegroundColor White
Write-Host "   e) Wait for success message" -ForegroundColor White
Write-Host "   f) Refresh page (F5)" -ForegroundColor White
Write-Host "   g) Check if frequency reverted to 'Co godzinę'" -ForegroundColor White
Write-Host ""
Write-Host "Press ENTER after completing the test..." -ForegroundColor Yellow
Read-Host

# Step 4: Fetch detailed logs
Write-Host ""
Write-Host "4. Fetching detailed logs..." -ForegroundColor Yellow
Write-Host "=" * 80 -ForegroundColor Gray

$logs = plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "cd $RemotePath && tail -300 storage/logs/laravel.log | grep -E 'saveSyncConfiguration|loadSyncConfigurationFromDatabase|Session flag|BEFORE updateOrCreate|AFTER updateOrCreate|autoSyncFrequency'"

if ($logs) {
    $logs | Write-Host
} else {
    Write-Host "   ⚠️  No matching logs found!" -ForegroundColor Red
}

Write-Host ""
Write-Host "=" * 80 -ForegroundColor Gray

# Step 5: Check final value
Write-Host ""
Write-Host "5. Final frequency value:" -ForegroundColor Yellow
plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "cd $RemotePath && php artisan tinker --execute=`"echo \App\Models\SystemSetting::where('key', 'sync.schedule.frequency')->value('value') ?? 'NOT FOUND'; echo PHP_EOL;`""

Write-Host ""
Write-Host "=== TEST COMPLETE ===" -ForegroundColor Cyan
Write-Host "Analyze logs above to identify why session guard is bypassed" -ForegroundColor Gray
