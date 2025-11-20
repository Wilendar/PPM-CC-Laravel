$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== DIRECT TEST: loadShopCategories Logic ===" -ForegroundColor Cyan
Write-Host ""

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=`"`$product = App\Models\Product::find(11034); `$psd = App\Models\ProductShopData::where('product_id', `$product->id)->where('shop_id', 1)->first(); if (`$psd && !empty(`$psd->category_mappings)) { `$cm = `$psd->category_mappings; `$selected = `$cm['ui']['selected'] ?? []; `$primary = `$cm['ui']['primary'] ?? null; echo '✅ SUCCESS: loadShopCategories will return:' . PHP_EOL; echo '  Selected: ' . json_encode(`$selected) . PHP_EOL; echo '  Primary: ' . `$primary . PHP_EOL; if (count(`$selected) === 3) { echo '  ✅ All 3 categories present!' . PHP_EOL; } } else { echo '❌ FAILED: No data found' . PHP_EOL; }`""

Write-Host "`n=== TEST COMPLETE ===" -ForegroundColor Green
