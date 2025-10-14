# Check product 10958 (latest test)

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostName = "host379076@host379076.hostido.net.pl"
$Port = 64321

Write-Host "`n=== Product 10958 Categories in DB ===" -ForegroundColor Cyan

$query = "SELECT id, product_id, category_id, shop_id, is_primary, sort_order FROM product_categories WHERE product_id = 10958 ORDER BY shop_id, sort_order"

$command = "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=`"echo json_encode(DB::select('$query'), JSON_PRETTY_PRINT);`""

plink -ssh $HostName -P $Port -i $HostidoKey -batch $command

Write-Host "`n=== Testing allCategoriesGroupedByShop() ===" -ForegroundColor Yellow

$command2 = @"
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
\`$product = App\Models\Product::find(10958);
if (\`$product) {
    \`$grouped = \`$product->allCategoriesGroupedByShop();
    echo 'DEFAULT: ' . json_encode(\`$grouped['default']->pluck('id')->toArray()) . PHP_EOL;
    foreach (\`$grouped['shops'] as \`$shopId => \`$categories) {
        echo 'SHOP ' . \`$shopId . ': ' . json_encode(\`$categories->pluck('id')->toArray()) . PHP_EOL;
    }
}
"
"@

plink -ssh $HostName -P $Port -i $HostidoKey -batch $command2

Write-Host "`n=== Complete ===" -ForegroundColor Green
