<?php
// Test buildCategoryAssociations() with saved category_mappings
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\ProductTransformer;
use App\Services\PrestaShop\CategoryMapper;
use App\Services\PrestaShop\PriceGroupMapper;
use App\Services\PrestaShop\WarehouseMapper;

$productId = 11033;
$shopId = 1;

echo "=== TESTING buildCategoryAssociations() ===\n\n";

$product = Product::find($productId);
$shop = PrestaShopShop::find($shopId);

if (!$product || !$shop) {
    echo "Product or Shop NOT FOUND\n";
    exit(1);
}

echo "Product: {$product->name} (ID: {$product->id})\n";
echo "Shop: {$shop->name} (ID: {$shop->id})\n\n";

// Create ProductTransformer
$categoryMapper = app(CategoryMapper::class);
$priceGroupMapper = app(PriceGroupMapper::class);
$warehouseMapper = app(WarehouseMapper::class);

$transformer = new ProductTransformer($categoryMapper, $priceGroupMapper, $warehouseMapper);

// Manually call buildCategoryAssociations (use reflection to access private method)
$reflection = new \ReflectionClass($transformer);
$method = $reflection->getMethod('buildCategoryAssociations');
$method->setAccessible(true);

echo "Calling buildCategoryAssociations()...\n";
$result = $method->invoke($transformer, $product, $shop);

echo "\n=== RESULT ===\n";
echo "Returned categories:\n";
print_r($result);

echo "\nCategory count: " . count($result) . "\n";
echo "Category IDs: " . json_encode(array_column($result, 'id')) . "\n";

// Check ProductShopData
$shopData = $product->dataForShop($shop->id)->first();
echo "\n=== ProductShopData ===\n";
echo "ID: " . ($shopData->id ?? 'NULL') . "\n";
echo "category_mappings: " . json_encode($shopData->category_mappings ?? null) . "\n";
