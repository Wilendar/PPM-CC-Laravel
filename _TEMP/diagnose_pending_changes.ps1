$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== DIAGNOSING PENDING CHANGES SYSTEM ===" -ForegroundColor Cyan

Write-Host "`n1. Check recent logs for 'Zapisz zmiany' button click:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -500 storage/logs/laravel.log" | Select-String -Pattern "saveAllChanges|saveAndClose|Pending changes saved|savePendingChanges" -Context 2

Write-Host "`n2. Check if categories are being tracked in pending changes:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -500 storage/logs/laravel.log" | Select-String -Pattern "contextCategories|shopCategories|defaultCategories" -Context 1

Write-Host "`n3. Check saveAllPendingChanges execution:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -500 storage/logs/laravel.log" | Select-String -Pattern "All pending changes saved|savePendingChangesToShop|savePendingChangesToProduct" -Context 2

Write-Host "`n4. Check database structure (product_categories vs product_shop_data):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=`"echo 'Tables with categories: '; DB::select('SHOW TABLES LIKE ''%categor%'''); echo PHP_EOL . 'product_shop_data columns: '; \`$cols = DB::select('SHOW COLUMNS FROM product_shop_data'); foreach(\`$cols as \`$col) { echo \`$col->Field . PHP_EOL; }`""

Write-Host "`n5. Check current categories for product 11034:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=`"DB::table('product_shop_data')->where('product_id', 11034)->where('shop_id', 1)->get(['id', 'product_id', 'shop_id', 'category_mappings', 'updated_at'])->each(function(\`$psd) { echo 'Product: ' . \`$psd->product_id . ' | Shop: ' . \`$psd->shop_id . PHP_EOL; echo 'Categories: ' . (\`$psd->category_mappings ?? 'NULL') . PHP_EOL; echo 'Updated: ' . \`$psd->updated_at . PHP_EOL; });`""
