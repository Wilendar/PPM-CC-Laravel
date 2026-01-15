$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING PRODUCT MEDIA ===" -ForegroundColor Cyan

Write-Host "`n[1] Media for products 11094 and 11095:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='print_r(\App\Models\Media::where(\"product_id\", 11094)->orWhere(\"product_id\", 11095)->get([\"id\", \"product_id\", \"filename\", \"prestashop_image_id\"])->toArray());'"

Write-Host "`n[2] Products in PrestaShop (images count):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='
\$shop = \App\Models\PrestaShopShop::find(1);
\$client = \App\Services\PrestaShop\PrestaShopClientFactory::create(\$shop);
\$ps_prod = \$client->getProduct(9756);
echo \"Product 9756 images: \" . (isset(\$ps_prod[\"associations\"][\"images\"]) ? count(\$ps_prod[\"associations\"][\"images\"]) : 0) . PHP_EOL;
'"

Write-Host "`n[3] SyncMediaFromPrestaShop logs:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -200 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -i 'SyncMedia' -A 3"

Write-Host "`n=== DONE ===" -ForegroundColor Green
