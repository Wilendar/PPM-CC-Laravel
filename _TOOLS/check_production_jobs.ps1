# Check Production Jobs Status
# Run with: pwsh -File _TOOLS/check_production_jobs.ps1

$HostidoKey = "D:\SSH\Hostido\HostidoSSHNoPass.ppk"
$Host379 = "host379076@host379076.hostido.net.pl"
$Port = 64321
$LaravelPath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "=== Checking Production Jobs Status ===" -ForegroundColor Cyan

# Check tables
Write-Host "`n1. Checking database tables..." -ForegroundColor Yellow
$cmd = "cd $LaravelPath && php artisan tinker --execute=`"
try {
    echo 'jobs: ' . (Schema::hasTable('jobs') ? 'EXISTS' : 'MISSING');
    echo chr(10) . 'failed_jobs: ' . (Schema::hasTable('failed_jobs') ? 'EXISTS' : 'MISSING');
    echo chr(10) . 'sync_jobs: ' . (Schema::hasTable('sync_jobs') ? 'EXISTS' : 'MISSING');
    echo chr(10) . 'job_progress: ' . (Schema::hasTable('job_progress') ? 'EXISTS' : 'MISSING');
} catch (Exception \$e) { echo 'Error: ' . \$e->getMessage(); }
`""

plink -ssh $Host379 -P $Port -i $HostidoKey -batch $cmd

# Check sync_jobs counts
Write-Host "`n2. Checking sync_jobs counts..." -ForegroundColor Yellow
$cmd2 = "cd $LaravelPath && php artisan tinker --execute=`"
try {
    if (Schema::hasTable('sync_jobs')) {
        \$pending = DB::table('sync_jobs')->where('status', 'pending')->count();
        \$running = DB::table('sync_jobs')->where('status', 'running')->count();
        echo 'pending: ' . \$pending . chr(10);
        echo 'running: ' . \$running . chr(10);
        echo 'total: ' . DB::table('sync_jobs')->count();
    } else {
        echo 'sync_jobs table does not exist';
    }
} catch (Exception \$e) { echo 'Error: ' . \$e->getMessage(); }
`""

plink -ssh $Host379 -P $Port -i $HostidoKey -batch $cmd2

# Check jobs queue
Write-Host "`n3. Checking jobs queue..." -ForegroundColor Yellow
$cmd3 = "cd $LaravelPath && php artisan tinker --execute=`"
try {
    if (Schema::hasTable('jobs')) {
        echo 'Jobs in queue: ' . DB::table('jobs')->count();
    } else {
        echo 'jobs table does not exist';
    }
} catch (Exception \$e) { echo 'Error: ' . \$e->getMessage(); }
`""

plink -ssh $Host379 -P $Port -i $HostidoKey -batch $cmd3

Write-Host "`n=== Done ===" -ForegroundColor Cyan
