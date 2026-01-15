$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== RUNNING MEDIA SYNC FOR PRODUCT 11103 ===" -ForegroundColor Cyan
Write-Host ""

# First check queue config on server
Write-Host "[1/4] Checking queue configuration..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && grep QUEUE_CONNECTION .env"

Write-Host ""
# Dispatch Media Sync Job on default queue
Write-Host "[2/4] Dispatching SyncMediaFromPrestaShop job on default queue..." -ForegroundColor Yellow
$cmd = 'cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="\App\Jobs\Media\SyncMediaFromPrestaShop::dispatch(11103, 1)->onQueue(\"default\");"'
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd

Write-Host ""
Write-Host "[3/4] Processing job queue..." -ForegroundColor Yellow
$cmd2 = 'cd domains/ppm.mpptrade.pl/public_html && php artisan queue:work --once --timeout=120'
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd2

Write-Host ""
Write-Host "[4/4] Checking logs..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -30 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E '(MEDIA|IMAGE)' | tail -15"

Write-Host ""
Write-Host "=== COMPLETE ===" -ForegroundColor Green
