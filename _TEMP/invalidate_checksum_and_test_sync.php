<?php
// Invalidate checksum to force sync with new category logic
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ProductShopData;
use App\Services\PrestaShop\Sync\ProductSyncStrategy;
use App\Services\PrestaShop\PrestaShopClientFactory;

$productId = 11033;
$shopId = 1;

echo "=== TESTING FIX #11: Shop-specific categories in checksum ===\n\n";

$product = Product::with(['categories', 'prices'])->find($productId);
$shop = PrestaShopShop::find($shopId);

if (!$product || !$shop) {
    echo "Product or Shop NOT FOUND\n";
    exit(1);
}

echo "Product: {$product->name} (ID: {$product->id})\n";
echo "Shop: {$shop->name} (ID: {$shop->id})\n\n";

// Get ProductShopData
$shopData = ProductShopData::where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

echo "=== STEP 1: Check category_mappings ===\n";
echo "category_mappings: " . json_encode($shopData->category_mappings) . "\n";
echo "category_mappings count: " . (is_array($shopData->category_mappings) ? count($shopData->category_mappings) : 0) . "\n\n";

// Calculate NEW checksum (with FIX #11)
echo "=== STEP 2: Calculate NEW checksum (FIX #11) ===\n";
$strategy = app(ProductSyncStrategy::class);
$newChecksum = $strategy->calculateChecksum($product, $shop);
echo "New checksum: {$newChecksum}\n";
echo "Old checksum: " . ($shopData->checksum ?? 'NULL') . "\n";
echo "Checksums match: " . ($newChecksum === $shopData->checksum ? 'YES (no sync needed)' : 'NO (sync needed!)') . "\n\n";

// Check if sync needed
echo "=== STEP 3: Check needsSync() ===\n";
$needsSync = $strategy->needsSync($product, $shop);
echo "needsSync: " . ($needsSync ? 'TRUE (will sync)' : 'FALSE (will skip)') . "\n\n";

if (!$needsSync) {
    echo "⚠️ Sync will be skipped - forcing by invalidating checksum...\n";
    $shopData->update(['checksum' => 'FORCE_SYNC']);
    echo "Checksum invalidated\n\n";

    // Re-check
    $needsSync = $strategy->needsSync($product, $shop);
    echo "needsSync after invalidation: " . ($needsSync ? 'TRUE' : 'FALSE') . "\n\n";
}

// Perform SYNC
echo "=== STEP 4: Perform SYNC ===\n";
$client = PrestaShopClientFactory::create($shop);
$result = $strategy->syncToPrestaShop($product, $client, $shop);

echo "Sync result:\n";
print_r($result);

// Verify on PrestaShop
echo "\n=== STEP 5: Verify on PrestaShop ===\n";
$shopData->refresh();
if ($shopData->prestashop_product_id) {
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

echo "\n=== STEP 6: Final checksum verification ===\n";
$shopData->refresh();
echo "Final checksum: " . ($shopData->checksum ?? 'NULL') . "\n";
echo "Matches new checksum: " . ($shopData->checksum === $newChecksum ? 'YES' : 'NO') . "\n";
