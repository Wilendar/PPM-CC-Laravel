/**
 * Manual Sync Test - Categories Fix Verification
 */

echo "=== MANUAL SYNC TEST ===\n\n";

// Get product
$product = \App\Models\Product::where('sku', 'TEST-SYNC-001')->first();
$shop = \App\Models\PrestaShopShop::find(1); // B2B Test DEV

if (!$product || !$shop) {
    echo "❌ Product or shop not found\n";
    exit(1);
}

echo "Product: {$product->name}\n";
echo "Shop: {$shop->name}\n";
echo "Categories in PPM: " . $product->categories->count() . "\n\n";

try {
    // Create client
    $client = \App\Services\PrestaShop\PrestaShopClientFactory::create($shop);

    // Create sync strategy
    $transformer = app(\App\Services\PrestaShop\ProductTransformer::class);
    $categoryMapper = app(\App\Services\PrestaShop\CategoryMapper::class);
    $priceMapper = app(\App\Services\PrestaShop\PriceGroupMapper::class);
    $warehouseMapper = app(\App\Services\PrestaShop\WarehouseMapper::class);

    $syncStrategy = new \App\Services\PrestaShop\Sync\ProductSyncStrategy(
        $transformer,
        $categoryMapper,
        $priceMapper,
        $warehouseMapper
    );

    echo "Executing sync...\n";
    $result = $syncStrategy->syncToPrestaShop($product, $client, $shop);

    echo "\n✅ SYNC SUCCESSFUL!\n";
    echo "Operation: {$result['operation']}\n";
    echo "External ID: {$result['external_id']}\n";

    if (isset($result['skipped']) && $result['skipped']) {
        echo "⚠️  Sync was skipped (no changes detected)\n";
        echo "This is expected - category fix only affects NEW syncs or forced re-syncs\n";
    }

} catch (\Exception $e) {
    echo "\n❌ SYNC FAILED:\n";
    echo "Error: {$e->getMessage()}\n";
}

echo "\n=== VERIFYING PRODUCT IN PRESTASHOP ===\n\n";

try {
    $response = $client->getProduct(9760);
    $product = $response['product'];

    echo "Product ID: {$product['id']}\n";
    echo "Reference: {$product['reference']}\n";
    echo "Active: {$product['active']}\n\n";

    if (isset($product['associations']['categories']['category'])) {
        $categories = $product['associations']['categories']['category'];
        if (!isset($categories[0])) {
            $categories = [$categories];
        }
        echo "✅ CATEGORIES IN PRESTASHOP: " . count($categories) . "\n";
        foreach ($categories as $cat) {
            echo "  - Category ID: {$cat['id']}\n";
        }

        echo "\n🎉 SUCCESS! Product now has categories in PrestaShop!\n";
        echo "Product should now be visible in admin panel\n";
    } else {
        echo "❌ NO CATEGORIES - Fix didn't work\n";
    }

} catch (\Exception $e) {
    echo "❌ Error verifying: {$e->getMessage()}\n";
}

echo "\n=== END TEST ===\n";
