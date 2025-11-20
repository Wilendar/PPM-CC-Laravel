$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING PRODUCTION CODE ===" -ForegroundColor Cyan

# Check if fix is deployed
Write-Host "`nSearching for prestashop_shop_price_mappings in production..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && grep -n 'prestashop_shop_price_mappings' app/Services/PrestaShop/PrestaShopPriceImporter.php"

Write-Host "`n`nGetting mapSpecificPriceToPriceGroup method..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && sed -n '266,312p' app/Services/PrestaShop/PrestaShopPriceImporter.php"
