$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== DIAGNOZA: User test - kategorie nie dzialajs ===" -ForegroundColor Red
Write-Host ""

Write-Host "1. Check LAST 50 log lines for loadShopCategories:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -50 storage/logs/laravel.log" | Select-String -Pattern "loadShopCategories|Shop categories loaded" -Context 1

Write-Host "`n2. Check latest category save operation:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -100 storage/logs/laravel.log" | Select-String -Pattern "savePendingChangesToShop|Converted PrestaShop IDs" -Context 1

Write-Host "`n3. Check what JOB sync exported:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -200 storage/logs/laravel.log" | Select-String -Pattern "ProductTransformer.*categor|buildCategoryAssociations|categories.*export" -Context 2

Write-Host "`n4. Check database - what's actually saved:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='echo \"Product 11034 categories:\"; echo PHP_EOL; \$psd = \App\Models\ProductShopData::where(\"product_id\", 11034)->where(\"shop_id\", 1)->first(); if (\$psd) { echo \"category_mappings: \" . \$psd->category_mappings; echo PHP_EOL; \$cm = json_decode(\$psd->category_mappings, true); if (isset(\$cm[\"ui\"][\"selected\"])) { echo \"Selected count: \" . count(\$cm[\"ui\"][\"selected\"]); echo PHP_EOL; echo \"Selected IDs: \" . json_encode(\$cm[\"ui\"][\"selected\"]); } } else { echo \"ProductShopData NOT FOUND\"; }'"

Write-Host "`n=== DIAGNOSIS COMPLETE ===" -ForegroundColor Cyan
