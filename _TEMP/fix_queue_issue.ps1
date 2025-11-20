# FIX: Jobs nie trafiają do kolejki - Clear cache i diagnostyka
# 2025-11-12

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== NAPRAWIANIE PROBLEMU Z QUEUE ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "[1/5] Clearing ALL cache (config + cache + view)..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan config:clear && php artisan cache:clear && php artisan view:clear"

Write-Host "`n[2/5] Checking current queue config..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan config:show queue.default"

Write-Host "`n[3/5] Checking jobs table count BEFORE test..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='echo DB::table(\"jobs\")->count();'"

Write-Host "`n[4/5] Testing simple job dispatch..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='App\Jobs\PrestaShop\SyncProductToPrestaShop::dispatch(App\Models\Product::find(11017), App\Models\PrestaShopShop::find(1), 8); echo \"Job dispatched\";'"

Write-Host "`n[5/5] Checking jobs table count AFTER test..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='echo DB::table(\"jobs\")->count();'"

Write-Host ""
Write-Host "=== DIAGNOSTYKA ZAKONCZONA ===" -ForegroundColor Green
Write-Host ""
