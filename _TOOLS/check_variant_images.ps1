# check_variant_images.ps1
# Check variant images structure in production logs

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== Checking Variant Images in Production ===" -ForegroundColor Cyan

# Check ShopVariantService logs
Write-Host "`n1. ShopVariantService extractCombinationImages logs:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -n 200 storage/logs/laravel.log | grep -A 10 'extractCombinationImages\|pullShopVariants COMPLETE'"

Write-Host "`n2. Checking shop URL format:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='echo PrestaShopShop::find(1)->url;'"

Write-Host "`n3. Check variant images data structure:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -n 500 storage/logs/laravel.log | grep -A 20 'Mapped variants for display'"
