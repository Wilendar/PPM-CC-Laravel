# deploy_variant_images_debug.ps1
# Deploy debug logging for variant images

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== Deploying Variant Images Debug Logging ===" -ForegroundColor Cyan

# Upload ShopVariantService.php
Write-Host "`n1. Uploading ShopVariantService.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app\Services\PrestaShop\ShopVariantService.php" host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Services/PrestaShop/

# Clear cache
Write-Host "`n2. Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan config:clear"

Write-Host "`nDone! Now test in browser and check logs." -ForegroundColor Green
