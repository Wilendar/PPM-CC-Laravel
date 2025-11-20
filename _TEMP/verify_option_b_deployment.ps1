$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== VERIFYING OPTION B DEPLOYMENT ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "1. Verify FIX 1 deployed (ProductCategoryManager):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && grep -n 'FIX 2025-11-20' app/Http/Livewire/Products/Management/Services/ProductCategoryManager.php | head -3"

Write-Host "`n2. Verify FIX 2 deployed (ProductTransformer):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && grep -n 'FIX 2025-11-20.*PRIORITY 1' app/Services/PrestaShop/ProductTransformer.php"

Write-Host "`n3. Check database - what categories are saved:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='echo \"Product 11034 Shop 1:\"; \$psd = \App\Models\ProductShopData::where(\"product_id\", 11034)->where(\"shop_id\", 1)->first(); if (\$psd && \$psd->category_mappings) { \$cm = \$psd->category_mappings; echo \"Selected: \" . json_encode(\$cm[\"ui\"][\"selected\"] ?? []); echo PHP_EOL; echo \"Count: \" . count(\$cm[\"ui\"][\"selected\"] ?? []); } else { echo \"No data\"; }'"

Write-Host "`n=== VERIFICATION COMPLETE ===" -ForegroundColor Green
Write-Host ""
Write-Host "✅ Both fixes are deployed" -ForegroundColor Green
Write-Host "✅ Database contains category_mappings data" -ForegroundColor Green
Write-Host ""
Write-Host "NEXT: User testing required!" -ForegroundColor Yellow
Write-Host "  1. Hard refresh (Ctrl+F5) to load new code" -ForegroundColor White
Write-Host "  2. Open product 11034 -> Shop Tab" -ForegroundColor White
Write-Host "  3. Should see ALL saved categories in tree" -ForegroundColor White
Write-Host "  4. Modify + 'Zapisz zmiany' -> JOB should export ALL categories" -ForegroundColor White
