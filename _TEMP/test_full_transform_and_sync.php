<?php
// Test FULL product transformation and sync to PrestaShop
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\PrestaShop\ProductTransformer;
use App\Services\PrestaShop\Sync\ProductSyncStrategy;

$productId = 11033;
$shopId = 1;

echo "=== TESTING FULL transformForPrestaShop() + SYNC ===\n\n";

$product = Product::with(['categories', 'prices'])->find($productId);
$shop = PrestaShopShop::find($shopId);

if (!$product || !$shop) {
    echo "Product or Shop NOT FOUND\n";
    exit(1);
}

echo "Product: {$product->name} (ID: {$product->id})\n";
echo "Shop: {$shop->name} (ID: {$shop->id})\n\n";

try {
    // Create client and transformer
    $client = PrestaShopClientFactory::create($shop);
    $transformer = app(ProductTransformer::class);

    echo "=== STEP 1: transformForPrestaShop() ===\n";
    $productData = $transformer->transformForPrestaShop($product, $client);

    echo "Transformed data structure:\n";
    echo "  - Contains 'product' key: " . (isset($productData['product']) ? 'YES' : 'NO') . "\n";
    echo "  - Product reference: " . ($productData['product']['reference'] ?? 'NULL') . "\n";
    echo "  - Product id_category_default: " . ($productData['product']['id_category_default'] ?? 'NULL') . "\n";

    if (isset($productData['product']['associations']['categories'])) {
        $categories = $productData['product']['associations']['categories'];
        echo "  - Categories in payload: YES\n";
        echo "  - Categories count: " . count($categories) . "\n";
        echo "  - Categories IDs: " . json_encode(array_column($categories, 'id')) . "\n\n";

        echo "Categories payload structure:\n";
        print_r($categories);
    } else {
        echo "  - Categories in payload: NO ❌\n";
        echo "  - Checking alternative paths...\n";
        echo "    - product['categories']: " . (isset($productData['product']['categories']) ? 'YES' : 'NO') . "\n";
        echo "    - product['associations']: " . (isset($productData['product']['associations']) ? 'YES' : 'NO') . "\n";

        if (isset($productData['product']['associations'])) {
            echo "\n  Associations keys:\n";
            print_r(array_keys($productData['product']['associations']));
        }
    }

    echo "\n=== STEP 2: Actual SYNC to PrestaShop ===\n";
    echo "Using ProductSyncStrategy to sync...\n";

    $strategy = app(ProductSyncStrategy::class);
    $result = $strategy->syncToPrestaShop($product, $client, $shop);

    echo "\nSync result:\n";
    print_r($result);

    echo "\n=== STEP 3: Verify on PrestaShop ===\n";
    $shopData = $product->dataForShop($shop->id)->first();
    if ($shopData && $shopData->prestashop_product_id) {
        echo "Fetching updated product from PrestaShop...\n";
        $updated = $client->getProduct($shopData->prestashop_product_id);
        $updated = $updated['product'] ?? $updated;

        echo "PrestaShop product categories AFTER sync:\n";
        if (isset($updated['associations']['categories'])) {
            $psCategories = $updated['associations']['categories'];
            echo "  - Count: " . count($psCategories) . "\n";
            echo "  - IDs: " . json_encode(array_column($psCategories, 'id')) . "\n";
        } else {
            echo "  - NO CATEGORIES ❌\n";
        }
    }

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
