$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== RUNNING MEDIA SYNC FOR PRODUCT 11103 ===" -ForegroundColor Cyan
Write-Host ""

# Dispatch Media Sync Job using correct namespace App\Jobs\Media
Write-Host "[1/2] Dispatching SyncMediaFromPrestaShop job..." -ForegroundColor Yellow
$cmd = 'cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="\App\Jobs\Media\SyncMediaFromPrestaShop::dispatch(11103, 1);"'
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd

Write-Host ""
Write-Host "[2/2] Processing job queue..." -ForegroundColor Yellow
$cmd2 = 'cd domains/ppm.mpptrade.pl/public_html && php artisan queue:work prestashop_sync --once --timeout=120'
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd2

Write-Host ""
Write-Host "=== COMPLETE ===" -ForegroundColor Green
