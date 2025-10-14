# Test ProductForm loading for Product 10957
# This will trigger loadCategories() and generate debug logs

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostName = "host379076@host379076.hostido.net.pl"
$Port = 64321

Write-Host "`n=== Simulating ProductForm::mount() for Product 10957 ===" -ForegroundColor Cyan

$command = @"
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
use App\Models\Product;
use App\Http\Livewire\Products\Management\Services\ProductCategoryManager;

\`$product = Product::find(10957);
if (\`$product) {
    echo 'Product found: ' . \`$product->name . PHP_EOL;
    echo 'Calling allCategoriesGroupedByShop()...' . PHP_EOL;
    \`$grouped = \`$product->allCategoriesGroupedByShop();
    echo 'Default categories: ' . count(\`$grouped['default']) . PHP_EOL;
    echo 'Shop categories: ' . count(\`$grouped['shops']) . PHP_EOL;
    print_r(array_keys(\`$grouped['shops']));
} else {
    echo 'Product not found';
}
"
"@

plink -ssh $HostName -P $Port -i $HostidoKey -batch $command

Write-Host "`n=== Now checking logs ===" -ForegroundColor Yellow

$logCommand = "tail -n 50 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -A 10 'loadShopCategories'"

plink -ssh $HostName -P $Port -i $HostidoKey -batch $logCommand

Write-Host "`n=== Test Complete ===" -ForegroundColor Green
