# Test Product::allCategoriesGroupedByShop() for debugging
# Product ID: 10957

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostName = "host379076@host379076.hostido.net.pl"
$Port = 64321

Write-Host "`n=== Testing allCategoriesGroupedByShop() for Product 10957 ===" -ForegroundColor Cyan

$command = @"
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
\`$product = App\Models\Product::find(10957);
if (\`$product) {
    \`$grouped = \`$product->allCategoriesGroupedByShop();
    echo 'DEFAULT CATEGORIES: ' . count(\`$grouped['default']) . PHP_EOL;
    foreach (\`$grouped['default'] as \`$cat) {
        echo '  - ID: ' . \`$cat->id . ', Name: ' . \`$cat->name . ', Primary: ' . \`$cat->pivot->is_primary . PHP_EOL;
    }
    echo PHP_EOL . 'SHOP CATEGORIES:' . PHP_EOL;
    foreach (\`$grouped['shops'] as \`$shopId => \`$categories) {
        echo '  Shop ' . \`$shopId . ': ' . count(\`$categories) . ' categories' . PHP_EOL;
        foreach (\`$categories as \`$cat) {
            echo '    - ID: ' . \`$cat->id . ', Name: ' . \`$cat->name . ', Primary: ' . \`$cat->pivot->is_primary . PHP_EOL;
        }
    }
} else {
    echo 'Product 10957 not found';
}
"
"@

plink -ssh $HostName -P $Port -i $HostidoKey -batch $command

Write-Host "`n=== Test Complete ===" -ForegroundColor Green
