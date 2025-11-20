<?php
/**
 * Test Category Sync Workflow
 *
 * BUGFIX 2025-11-05: Test kompletnego workflow z CategorySyncService
 *
 * Workflow:
 * 1. Produkt ma kategorię w PPM (PITGANG)
 * 2. CategorySyncService sprawdza czy kategoria istnieje w PrestaShop
 * 3. Jeśli nie → tworzy kategorię w PrestaShop
 * 4. Tworzy mapping w shop_mappings
 * 5. ProductTransformer używa mapping do przypisania kategorii
 * 6. Produkt jest zapisany z kategoriami w PrestaShop
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST CATEGORY SYNC WORKFLOW ===\n\n";

// Get shop
$shop = \App\Models\PrestaShopShop::find(1); // B2B Test DEV

if (!$shop) {
    echo "❌ Shop not found\n";
    exit(1);
}

echo "Shop: {$shop->name}\n";
echo "URL: {$shop->url}\n\n";

// Get test product
$product = \App\Models\Product::where('sku', 'TEST-SYNC-001')->first();

if (!$product) {
    echo "❌ Product TEST-SYNC-001 not found\n";
    exit(1);
}

echo "Product: {$product->name} (ID: {$product->id})\n";
echo "SKU: {$product->sku}\n\n";

// Check categories
echo "1. Checking product categories in PPM...\n";
$categories = $product->categories;

if ($categories->isEmpty()) {
    echo "❌ Product has NO categories in PPM\n";
    echo "→ Please assign PITGANG category to this product first\n";
    exit(1);
}

echo "✅ Product has {$categories->count()} categories:\n";
foreach ($categories as $category) {
    echo "   - {$category->name} (ID: {$category->id})\n";
}
echo "\n";

// Clear checksum to force re-sync
echo "2. Clearing checksum to force re-sync...\n";
$syncStatus = \App\Models\ProductShopData::where('product_id', $product->id)
    ->where('shop_id', $shop->id)
    ->first();

if ($syncStatus) {
    $syncStatus->update([
        'checksum' => null,
        'sync_status' => 'pending',
    ]);
    echo "✅ Checksum cleared\n\n";
} else {
    echo "⚠️  No sync status found - will create new\n\n";
}

// Test CategorySyncService
echo "3. Testing CategorySyncService...\n";

try {
    $client = \App\Services\PrestaShop\PrestaShopClientFactory::create($shop);
    $categorySync = app(\App\Services\PrestaShop\CategorySyncService::class);

    echo "→ Syncing categories to PrestaShop...\n";
    $prestashopCategoryIds = $categorySync->syncProductCategories($categories, $client, $shop);

    echo "✅ Categories synced! PrestaShop IDs: " . implode(', ', $prestashopCategoryIds) . "\n\n";

} catch (\Exception $e) {
    echo "❌ CategorySyncService FAILED:\n";
    echo "Error: {$e->getMessage()}\n";
    echo "Trace: {$e->getTraceAsString()}\n";
    exit(1);
}

// Now sync product with ProductSyncStrategy
echo "4. Syncing product to PrestaShop (should use mapped categories)...\n";

try {
    $transformer = app(\App\Services\PrestaShop\ProductTransformer::class);
    $categoryMapper = app(\App\Services\PrestaShop\CategoryMapper::class);
    $priceMapper = app(\App\Services\PrestaShop\PriceGroupMapper::class);
    $warehouseMapper = app(\App\Services\PrestaShop\WarehouseMapper::class);
    $categorySyncService = app(\App\Services\PrestaShop\CategorySyncService::class);
    $categoryAssocService = app(\App\Services\PrestaShop\CategoryAssociationService::class);

    $syncStrategy = new \App\Services\PrestaShop\Sync\ProductSyncStrategy(
        $transformer,
        $categoryMapper,
        $categorySyncService,
        $categoryAssocService,
        $priceMapper,
        $warehouseMapper
    );

    $result = $syncStrategy->syncToPrestaShop($product, $client, $shop);

    echo "✅ PRODUCT SYNC SUCCESSFUL!\n";
    echo "Operation: {$result['operation']}\n";
    echo "PrestaShop Product ID: {$result['external_id']}\n\n";

    $prestashopProductId = $result['external_id'];

    // Verify in PrestaShop
    echo "5. Verifying product in PrestaShop...\n";

    $response = $client->getProduct($prestashopProductId);
    $prestashopProduct = $response['product'];

    echo "Product ID: {$prestashopProduct['id']}\n";
    echo "Reference: {$prestashopProduct['reference']}\n";
    echo "Active: {$prestashopProduct['active']}\n\n";

    // Check categories
    if (isset($prestashopProduct['associations']['categories']['category'])) {
        $psCategories = $prestashopProduct['associations']['categories']['category'];

        // Normalize to array
        if (!isset($psCategories[0])) {
            $psCategories = [$psCategories];
        }

        echo "✅ ✅ ✅ SUCCESS! Product HAS CATEGORIES in PrestaShop!\n";
        echo "Categories count: " . count($psCategories) . "\n";
        foreach ($psCategories as $cat) {
            echo "  - Category ID: {$cat['id']}\n";
        }

        echo "\n🎉 🎉 🎉 WORKFLOW DZIAŁA!\n";
        echo "→ CategorySyncService poprawnie tworzy kategorie w PrestaShop\n";
        echo "→ ProductSyncStrategy poprawnie używa zmapowanych kategorii\n";
        echo "→ Produkty są teraz widoczne w admin panelu PrestaShop!\n";

    } else {
        echo "❌ FAILURE! Product STILL has NO CATEGORIES\n";
        echo "→ Check Laravel logs for errors\n";
    }

} catch (\Exception $e) {
    echo "\n❌ PRODUCT SYNC FAILED:\n";
    echo "Error: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}:{$e->getLine()}\n";
    echo "Trace:\n{$e->getTraceAsString()}\n";
}

echo "\n=== END TEST ===\n";
