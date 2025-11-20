$HostidoKey = 'D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk'

Write-Host '=== PRODUCT 11034 - PrestaShop Product ID ===' -ForegroundColor Cyan
Write-Host ''

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=`"`$psd = App\\Models\\ProductShopData::where('product_id', 11034)->where('shop_id', 1)->first(); if (`$psd) { echo 'PrestaShop Product ID: ' . `$psd->prestashop_product_id . PHP_EOL; echo 'SKU: ' . `$psd->product->sku . PHP_EOL; echo 'Shop: ' . `$psd->shop->name . PHP_EOL; } else { echo 'ProductShopData NOT FOUND'; }`"`"

Write-Host ''
Write-Host '=== DONE ===' -ForegroundColor Green
