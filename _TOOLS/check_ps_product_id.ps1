# check_ps_product_id.ps1
# Get real PrestaShop product ID for product 11148

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== Getting PrestaShop Product ID ===" -ForegroundColor Cyan

$query = "echo Product::find(11148)->dataForShop(1)->first()?->prestashop_product_id;"

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='$query'"
