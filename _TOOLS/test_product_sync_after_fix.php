/**
 * Test Product Sync After Quantity Fix
 *
 * Tests synchronization of product to PrestaShop after fixing
 * "parameter quantity not writable" error
 *
 * BUGFIX 2025-11-05: quantity removed from product XML
 *
 * Usage: php artisan tinker < _TOOLS/test_product_sync_after_fix.php
 */

echo "=== PRESTASHOP PRODUCT SYNC TEST (After Quantity Fix) ===\n\n";

// Step 1: Check if PrestaShop shop configured
echo "1. Checking PrestaShop shop configuration...\n";
$shop = \App\Models\PrestaShopShop::first();

if (!$shop) {
    echo "❌ ERROR: No PrestaShop shop configured!\n";
    echo "   Please configure shop in database first.\n";
    exit(1);
}

echo "✅ Shop found: {$shop->name} ({$shop->url})\n";
echo "   Version: PrestaShop {$shop->version}\n\n";

// Step 2: Get or create test product
echo "2. Finding or creating test product...\n";
$product = \App\Models\Product::where('sku', 'TEST-SYNC-001')->first();

if (!$product) {
    echo "   Creating new test product...\n";
    $product = \App\Models\Product::create([
        'sku' => 'TEST-SYNC-001',
        'name' => 'Test Synchronizacji PrestaShop (After Fix)',
        'short_description' => 'Produkt testowy - sprawdzenie sync po fix quantity',
        'long_description' => 'Ten produkt został utworzony aby przetestować synchronizację po naprawieniu błędu "parameter quantity not writable".',
        'product_type_id' => 2, // spare_part
        'is_active' => true,
        'weight' => 1.5,
        'ean' => '1234567890123',
        'tax_rate' => 23.0,
        'manufacturer' => 'Test Manufacturer',
    ]);

    // Add price
    $priceGroup = \App\Models\PriceGroup::where('code', 'detaliczna')->first();
    if ($priceGroup) {
        \App\Models\ProductPrice::create([
            'product_id' => $product->id,
            'price_group_id' => $priceGroup->id,
            'price_net' => 99.99,
            'price_gross' => 122.99,
            'currency' => 'PLN',
        ]);
    }

    // Add category
    $category = \App\Models\Category::first();
    if ($category) {
        $product->categories()->attach($category->id);
    }

    echo "✅ Test product created: {$product->name} (SKU: {$product->sku})\n";
} else {
    echo "✅ Test product found: {$product->name} (SKU: {$product->sku})\n";
}

// Display product details
echo "   Product ID: {$product->id}\n";
echo "   Active: " . ($product->is_active ? 'Yes' : 'No') . "\n";
echo "   Categories: " . $product->categories->count() . "\n";
echo "   Prices: " . $product->prices->count() . "\n\n";

// Step 3: Test API connection
echo "3. Testing PrestaShop API connection...\n";
try {
    $client = \App\Services\PrestaShop\PrestaShopClientFactory::create($shop);

    $client->testConnection();
    echo "✅ API connection successful!\n\n";
} catch (\Exception $e) {
    echo "❌ API connection failed: {$e->getMessage()}\n";
    exit(1);
}

// Step 4: Perform sync
echo "4. Synchronizing product to PrestaShop...\n";
echo "   This is the CRITICAL TEST - checking if 'quantity' error is fixed\n\n";

try {
    // Get sync strategy
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

    // Execute sync
    echo "   Sending request to PrestaShop API...\n";
    $result = $syncStrategy->syncToPrestaShop($product, $client, $shop);

    echo "\n";
    echo "✅ SYNC SUCCESSFUL!\n";
    echo "   External ID: {$result['external_id']}\n";
    echo "   Operation: {$result['operation']}\n";
    echo "   Message: {$result['message']}\n";

    if (isset($result['skipped']) && $result['skipped']) {
        echo "   (No changes detected - sync skipped)\n";
    }

    echo "\n";
    echo "🎉 TEST PASSED - 'quantity' error is FIXED!\n\n";

    // Check sync status
    $syncStatus = \App\Models\ProductShopData::where('product_id', $product->id)
        ->where('shop_id', $shop->id)
        ->first();

    if ($syncStatus) {
        echo "5. Sync status details:\n";
        echo "   PrestaShop Product ID: {$syncStatus->prestashop_product_id}\n";
        echo "   Sync Status: {$syncStatus->sync_status}\n";
        echo "   Last Sync: {$syncStatus->last_sync_at}\n";
        echo "   Checksum: " . substr($syncStatus->checksum, 0, 16) . "...\n";
    }

} catch (\App\Exceptions\PrestaShopAPIException $e) {
    echo "\n";
    echo "❌ SYNC FAILED - PrestaShop API Error:\n";
    echo "   HTTP Status: {$e->getHttpStatusCode()}\n";
    echo "   Message: {$e->getMessage()}\n";
    echo "\n";

    // Check if it's the quantity error
    if (str_contains($e->getMessage(), 'quantity') && str_contains($e->getMessage(), 'not writable')) {
        echo "🚨 CRITICAL: 'quantity' error still present!\n";
        echo "   The fix was NOT deployed correctly.\n";
    }

    echo "\n";
    exit(1);
} catch (\Exception $e) {
    echo "\n";
    echo "❌ SYNC FAILED - Unexpected Error:\n";
    echo "   Message: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n";
    echo "\n";
    exit(1);
}

echo "\n=== TEST COMPLETED SUCCESSFULLY ===\n";
echo "Next step: Verify product in PrestaShop admin panel\n";
echo "URL: {$shop->url}/admin\n\n";
