$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING MEDIA ERROR DETAILS ===" -ForegroundColor Cyan

Write-Host "`n[1] Full media sync logs (last 50 lines):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -100 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -iE '(MEDIA|pullFromPrestaShop|getProductImages|error|Error)' | tail -30"

Write-Host "`n[2] Check if product has prestashop_id:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=\"\\\$p = App\\Models\\Product::find(11100); echo 'Product: ' . \\\$p->sku . ', ps_id: ' . (\\\$p->prestashop_id ?? 'NULL');\""

Write-Host "`n[3] Check product_shop_mappings for prestashop_product_id:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=\"\\\$m = DB::table('product_shop_mappings')->where('product_id', 11100)->first(); echo 'PS Product ID: ' . (\\\$m->prestashop_product_id ?? 'NULL');\""

Write-Host "`n=== DONE ===" -ForegroundColor Green
