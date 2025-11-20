/**
 * Re-sync TEST-SYNC-001 Product to Test Categories Fix
 */

echo "=== RE-SYNC TEST PRODUCT ===\n\n";

// Get product
$product = \App\Models\Product::where('sku', 'TEST-SYNC-001')->first();

if (!$product) {
    echo "❌ Product not found\n";
    exit(1);
}

echo "Product: {$product->name} (SKU: {$product->sku})\n";
echo "Product ID: {$product->id}\n\n";

// Check categories
$categories = $product->categories;
echo "Categories in PPM: " . $categories->count() . "\n";
foreach ($categories as $cat) {
    echo "  - {$cat->name} (ID: {$cat->id})\n";
}
echo "\n";

// Get shops
$shops = \App\Models\PrestaShopShop::where('is_active', true)->get();
echo "Active shops: " . $shops->count() . "\n\n";

foreach ($shops as $shop) {
    echo "Processing shop: {$shop->name} (ID: {$shop->id})\n";

    // Get sync status
    $syncStatus = \App\Models\ProductShopData::where('product_id', $product->id)
        ->where('shop_id', $shop->id)
        ->first();

    if ($syncStatus) {
        echo "  Current status: {$syncStatus->sync_status}\n";
        echo "  PrestaShop ID: " . ($syncStatus->prestashop_product_id ?? 'N/A') . "\n";

        // Clear checksum to force re-sync
        echo "  Clearing checksum to force re-sync...\n";
        $syncStatus->update([
            'checksum' => null,
            'sync_status' => 'pending',
        ]);
        echo "  ✅ Checksum cleared\n";
    } else {
        echo "  ⚠️  No sync status record\n";
    }

    // Dispatch sync job
    echo "  Dispatching sync job...\n";
    \App\Jobs\PrestaShop\SyncProductToPrestaShop::dispatch($product, $shop)
        ->onQueue('prestashop-sync');

    echo "  ✅ Job dispatched\n\n";
}

echo "=== JOBS DISPATCHED ===\n";
echo "Check logs in a moment:\n";
echo "  tail -f storage/logs/laravel.log | grep 'TEST-SYNC-001'\n\n";

echo "Then verify product in PrestaShop:\n";
echo "  php artisan tinker < _TOOLS/check_prestashop_product_9760.php\n\n";
