$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== TESTING loadShopCategories FIX ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "Simulating ProductForm load for product 11034, shop 1..." -ForegroundColor Yellow
Write-Host "(This will trigger loadShopCategories method)" -ForegroundColor Gray
Write-Host ""

# Create test script to simulate Livewire mount
$testScript = @'
use App\Models\Product;
use App\Models\ProductShopData;

echo "=== TEST START ===" . PHP_EOL;
echo PHP_EOL;

// Load product
$product = Product::find(11034);
echo "Product loaded: " . $product->id . " - " . $product->name . PHP_EOL;
echo PHP_EOL;

// Load ProductShopData (this is what loadShopCategories does now)
$productShopData = ProductShopData::where('product_id', $product->id)
    ->where('shop_id', 1)
    ->first();

if ($productShopData && !empty($productShopData->category_mappings)) {
    echo "ProductShopData found!" . PHP_EOL;
    echo "category_mappings structure:" . PHP_EOL;
    print_r($productShopData->category_mappings);
    echo PHP_EOL;

    $categoryMappings = $productShopData->category_mappings;
    $selected = $categoryMappings['ui']['selected'] ?? [];
    $primary = $categoryMappings['ui']['primary'] ?? null;

    echo "Extracted data (what loadShopCategories returns):" . PHP_EOL;
    echo "  - Selected categories: " . json_encode($selected) . PHP_EOL;
    echo "  - Primary category: " . $primary . PHP_EOL;
    echo PHP_EOL;

    if (count($selected) === 3 && in_array(1, $selected) && in_array(36, $selected) && in_array(2, $selected)) {
        echo "✅ SUCCESS: All 3 categories loaded correctly!" . PHP_EOL;
    } else {
        echo "❌ FAILED: Expected [1, 36, 2], got: " . json_encode($selected) . PHP_EOL;
    }
} else {
    echo "❌ ProductShopData NOT FOUND or category_mappings is empty!" . PHP_EOL;
}

echo PHP_EOL;
echo "=== TEST END ===" . PHP_EOL;
'@

# Upload test script
$testScript | Out-File -FilePath "_TEMP/test_load_categories.php" -Encoding UTF8 -NoNewline

Write-Host "Running test..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "_TEMP/test_load_categories.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/test_load_categories.php"

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='require ""test_load_categories.php"";'"

Write-Host "`n=== TEST COMPLETE ===" -ForegroundColor Green
