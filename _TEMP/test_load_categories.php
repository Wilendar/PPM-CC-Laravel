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