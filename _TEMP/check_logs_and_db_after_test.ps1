$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== WERYFIKACJA PO E2E TEST ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "1. Sprawdzam logi Laravel (ostatnie 100 linii):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -100 storage/logs/laravel.log" | Select-String -Pattern "loadShopCategories|saveAndClose|redirectToProductList|buildCategoryAssociations|savePendingChangesToShop" -Context 2

Write-Host "`n2. Sprawdzam bazę danych - product_shop_data dla product 11034:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='echo ""Product 11034 - Shop 1 (B2B Test DEV):""; echo PHP_EOL; \$psd = \App\Models\ProductShopData::where(""product_id"", 11034)->where(""shop_id"", 1)->first(); if (\$psd) { echo ""category_mappings column: ""; echo \$psd->getRawOriginal(""category_mappings""); echo PHP_EOL . PHP_EOL; \$cm = \$psd->category_mappings; if (is_array(\$cm) && isset(\$cm[""ui""][""selected""])) { echo ""Selected categories: "" . json_encode(\$cm[""ui""][""selected""]); echo PHP_EOL; echo ""Count: "" . count(\$cm[""ui""][""selected""]); echo PHP_EOL; echo ""Primary: "" . (\$cm[""ui""][""primary""] ?? ""NULL""); } else { echo ""Invalid structure or empty""; } } else { echo ""ProductShopData NOT FOUND""; }'"

Write-Host "`n3. Sprawdzam ostatnie JOB sync:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='echo ""Ostatnie 3 JOBy sync:""; echo PHP_EOL; \$jobs = \App\Models\SyncJob::with(""product"", ""shop"")->orderBy(""created_at"", ""desc"")->limit(3)->get(); foreach (\$jobs as \$job) { echo ""Job #"" . \$job->id . "" | Product: "" . (\$job->product->sku ?? ""NULL"") . "" | Shop: "" . (\$job->shop->name ?? ""NULL"") . "" | Status: "" . \$job->status . "" | Created: "" . \$job->created_at . PHP_EOL; }'"

Write-Host "`n=== WERYFIKACJA ZAKOŃCZONA ===" -ForegroundColor Green
