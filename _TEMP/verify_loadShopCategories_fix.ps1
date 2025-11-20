$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== VERIFYING loadShopCategories FIX ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "1. Check if NEW loadShopCategories code is deployed:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && grep -n 'FIX 2025-11-20' app/Http/Livewire/Products/Management/ProductForm.php"

Write-Host "`n2. Check database for product 11034 categories:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=`"DB::table('product_shop_data')->where('product_id', 11034)->where('shop_id', 1)->get(['id', 'product_id', 'shop_id', 'category_mappings', 'updated_at'])->each(function(\`$psd) { echo 'Product: ' . \`$psd->product_id . ' | Shop: ' . \`$psd->shop_id . PHP_EOL; echo 'Categories: ' . (\`$psd->category_mappings ?? 'NULL') . PHP_EOL; echo 'Updated: ' . \`$psd->updated_at . PHP_EOL; });`""

Write-Host "`n3. Ready for user test!" -ForegroundColor Green
Write-Host "Test steps:" -ForegroundColor Cyan
Write-Host "  1. Open product 11034" -ForegroundColor White
Write-Host "  2. Switch to Shop Tab (Shop: Pitrally)" -ForegroundColor White
Write-Host "  3. Verify categories are displayed correctly (should see saved categories)" -ForegroundColor White
Write-Host "  4. Optional: Modify categories and click 'Zapisz zmiany'" -ForegroundColor White
Write-Host "  5. Reload page and verify categories persist" -ForegroundColor White
