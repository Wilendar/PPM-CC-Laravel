# Test Queue Job Hang with Detailed Logging
# CRITICAL BUG: SyncProductToPrestaShop hangs on "Processing"

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "=== QUEUE JOB HANG TEST WITH LOGGING ===" -ForegroundColor Cyan
Write-Host ""

# Step 1: Clear old jobs and logs
Write-Host "1. Cleaning up old jobs and logs..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "cd $RemotePath && php artisan tinker --execute=`"DB::table('jobs')->delete(); DB::table('failed_jobs')->delete(); echo 'Cleaned'; echo PHP_EOL;`""
plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "cd $RemotePath && echo '' > storage/logs/laravel.log"
Write-Host "   ✅ Cleaned up" -ForegroundColor Green
Write-Host ""

# Step 2: Check queue worker status
Write-Host "2. Checking queue worker status..." -ForegroundColor Yellow
$cronJobs = plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "crontab -l | grep queue:work"
if ($cronJobs) {
    Write-Host "   ✅ Queue worker configured:" -ForegroundColor Green
    Write-Host "   $cronJobs" -ForegroundColor Gray
} else {
    Write-Host "   ⚠️  No queue worker cron found!" -ForegroundColor Red
}
Write-Host ""

# Step 3: Instructions for manual test
Write-Host "3. MANUAL TEST STEPS:" -ForegroundColor Magenta
Write-Host "   a) Open product: https://ppm.mpptrade.pl/admin/products/[ID]/edit" -ForegroundColor White
Write-Host "   b) Scroll to shop sync status section" -ForegroundColor White
Write-Host "   c) Expand 'Szczegóły synchronizacji'" -ForegroundColor White
Write-Host "   d) Click 'Aktualizuj sklep' button" -ForegroundColor White
Write-Host "   e) Wait 30 seconds" -ForegroundColor White
Write-Host "   f) Observe if job hangs on 'Processing' or 'Pending'" -ForegroundColor White
Write-Host ""
Write-Host "Press ENTER after dispatching the job..." -ForegroundColor Yellow
Read-Host

# Step 4: Monitor jobs table
Write-Host ""
Write-Host "4. Checking jobs table..." -ForegroundColor Yellow
Write-Host "=" * 80 -ForegroundColor Gray

$jobs = plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch @"
cd $RemotePath && php artisan tinker --execute="
\$jobs = DB::table('jobs')->orderBy('id', 'desc')->limit(5)->get();
foreach(\$jobs as \$job) {
    \$payload = json_decode(\$job->payload, true);
    echo 'ID: ' . \$job->id . PHP_EOL;
    echo '  Queue: ' . \$job->queue . PHP_EOL;
    echo '  Job: ' . (\$payload['displayName'] ?? 'Unknown') . PHP_EOL;
    echo '  Attempts: ' . \$job->attempts . PHP_EOL;
    echo '  Reserved: ' . (\$job->reserved_at ? date('Y-m-d H:i:s', \$job->reserved_at) : 'NULL') . PHP_EOL;
    echo '  Available: ' . date('Y-m-d H:i:s', \$job->available_at) . PHP_EOL;
    echo '---' . PHP_EOL;
}
echo PHP_EOL;
"
"@

$jobs | Write-Host
Write-Host "=" * 80 -ForegroundColor Gray

# Step 5: Fetch detailed logs
Write-Host ""
Write-Host "5. Fetching sync debug logs..." -ForegroundColor Yellow
Write-Host "=" * 80 -ForegroundColor Gray

$logs = plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "cd $RemotePath && tail -500 storage/logs/laravel.log | grep -E '\[SYNC DEBUG\]|SyncProductToPrestaShop|Processing|Exception'"

if ($logs) {
    $logs | Write-Host
} else {
    Write-Host "   ⚠️  No matching logs found!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Fetching ALL recent logs (last 100 lines):" -ForegroundColor Yellow
    plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "cd $RemotePath && tail -100 storage/logs/laravel.log"
}

Write-Host ""
Write-Host "=" * 80 -ForegroundColor Gray

# Step 6: Check failed jobs
Write-Host ""
Write-Host "6. Checking failed jobs..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "cd $RemotePath && php artisan tinker --execute=`"echo 'Failed jobs: ' . DB::table('failed_jobs')->count(); echo PHP_EOL;`""

Write-Host ""
Write-Host "=== TEST COMPLETE ===" -ForegroundColor Cyan
Write-Host "Analyze logs above to identify where job hangs" -ForegroundColor Gray
Write-Host ""
Write-Host "KEY LOGGING CHECKPOINTS:" -ForegroundColor Yellow
Write-Host "  ✓ [SYNC DEBUG] START" -ForegroundColor White
Write-Host "  ✓ [SYNC DEBUG] Validation passed" -ForegroundColor White
Write-Host "  ✓ [SYNC DEBUG] Transforming product data" -ForegroundColor White
Write-Host "  ✓ [SYNC DEBUG] Calling PrestaShop API" -ForegroundColor White
Write-Host "  ✓ [SYNC DEBUG] API returned" -ForegroundColor White
Write-Host "  ✓ [SYNC DEBUG] COMPLETED SUCCESSFULLY" -ForegroundColor White
Write-Host ""
Write-Host "Missing checkpoint = hang location" -ForegroundColor Gray
