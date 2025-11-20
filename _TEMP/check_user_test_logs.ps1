$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING LOGS FROM USER TEST ===" -ForegroundColor Cyan

Write-Host "`n1. Looking for 'SHOP SAVE' or 'saveCurrentShopChanges' calls:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -300 storage/logs/laravel.log" | Select-String -Pattern "SHOP SAVE|saveCurrentShopChanges" -Context 3

Write-Host "`n2. Looking for 'saveAndClose' or 'saveAllChanges' calls:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -300 storage/logs/laravel.log" | Select-String -Pattern "saveAndClose|saveAllChanges" -Context 2

Write-Host "`n3. Recent activity for product 11034:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -200 storage/logs/laravel.log" | Select-String -Pattern "11034" -Context 1

Write-Host "`n4. Check database structure for categories:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=`"echo 'product_categories table: '; DB::select('SHOW TABLES LIKE ''product_categories'''); echo PHP_EOL . 'product_shop_data columns: '; DB::select('SHOW COLUMNS FROM product_shop_data WHERE Field = ''category_mappings''');`""
