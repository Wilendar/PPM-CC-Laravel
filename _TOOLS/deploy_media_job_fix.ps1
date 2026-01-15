$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING MEDIA JOB FIX (2025-12-02) ===" -ForegroundColor Cyan

Write-Host "[1/4] Uploading SyncProductToPrestaShop.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Jobs/PrestaShop/SyncProductToPrestaShop.php" "$RemoteBase/app/Jobs/PrestaShop/SyncProductToPrestaShop.php"

Write-Host "[2/4] Uploading ProductSyncStrategy.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Services/PrestaShop/Sync/ProductSyncStrategy.php" "$RemoteBase/app/Services/PrestaShop/Sync/ProductSyncStrategy.php"

Write-Host "[3/4] Uploading ProductForm.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Products/Management/ProductForm.php" "$RemoteBase/app/Http/Livewire/Products/Management/ProductForm.php"

Write-Host "[4/4] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
