$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING LARAVEL LOGS FOR CATEGORY SAVE ===" -ForegroundColor Cyan

# Get recent logs related to categories
Write-Host "`n1. Category-related log entries:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -500 storage/logs/laravel.log" | Select-String -Pattern "saveAndClose|category|Category|CATEGORY|ProductForm" -Context 2

Write-Host "`n2. Checking product_shop_data for product 11033:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=`"DB::table('product_shop_data')->where('product_id', 11033)->where('shop_id', 1)->get(['id', 'product_id', 'shop_id', 'category_mappings', 'updated_at'])->each(function(\`$psd) { echo 'ID: ' . \`$psd->id . ' | Product: ' . \`$psd->product_id . ' | Shop: ' . \`$psd->shop_id . ' | Categories: ' . (\`$psd->category_mappings ?? 'NULL') . ' | Updated: ' . \`$psd->updated_at . PHP_EOL; });`""

Write-Host "`n3. Checking for save errors:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -200 storage/logs/laravel.log" | Select-String -Pattern "ERROR|Exception|Failed" -Context 1
