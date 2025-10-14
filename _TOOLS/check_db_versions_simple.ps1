# Simple DB Check for PrestaShop Versions
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING PRESTASHOP VERSIONS ===" -ForegroundColor Cyan

# Simple artisan command to list shops
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan db:table prestashop_shops --columns=id,shop_name,version"

Write-Host "`n=== DONE ===" -ForegroundColor Green
