$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING FEATURE SYNC FIX v2 ===" -ForegroundColor Cyan

Write-Host "[1/3] Uploading HasFeatures.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Models\Concerns\Product\HasFeatures.php" "${RemoteBase}/app/Models/Concerns/Product/HasFeatures.php"

Write-Host "[2/3] Uploading PrestaShopShop.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Models\PrestaShopShop.php" "${RemoteBase}/app/Models/PrestaShopShop.php"

Write-Host "[3/3] Clearing cache and restarting queue..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan config:clear && php artisan queue:restart"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
