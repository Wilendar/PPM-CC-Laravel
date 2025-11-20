<?php
// TEST E2E: Change categories in PPM → Sync → Verify in PrestaShop

require 'vendor/autoload.php';

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ProductShopData;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Jobs\PrestaShop\SyncProductToPrestaShop;
use Illuminate\Support\Facades\Log;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST E2E: Change Categories + Sync + Verify                    ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

$testProductId = 11033;
$testShopId = 1;

// ═══════════════════════════════════════════════════════════════════
// STEP 1: BEFORE STATE
// ═══════════════════════════════════════════════════════════════════

echo "═══ STEP 1: Current State (BEFORE) ═══\n";

$product = Product::find($testProductId);
$shop = PrestaShopShop::find($testShopId);
$shopData = ProductShopData::where('product_id', $testProductId)
    ->where('shop_id', $testShopId)
    ->first();

if (!$product || !$shop || !$shopData) {
    echo "❌ ERROR: Product, Shop, or ProductShopData NOT FOUND!\n";
    exit(1);
}

echo "✅ Product: {$product->name}\n";
echo "✅ Shop: {$shop->name}\n";
echo "✅ PrestaShop Product ID: {$shopData->prestashop_product_id}\n\n";

// Get current mappings
$oldMappings = $shopData->category_mappings;
$oldPsIds = isset($oldMappings['mappings']) ? array_values($oldMappings['mappings']) : [];
sort($oldPsIds);

echo "📦 BEFORE - PPM expects: " . implode(', ', $oldPsIds) . "\n\n";

// Get current PrestaShop state
$client = PrestaShopClientFactory::create($shop);
$psProduct = $client->getProduct($shopData->prestashop_product_id);
if (isset($psProduct['product'])) {
    $psProduct = $psProduct['product'];
}

$psCategories = $psProduct['associations']['categories'] ?? [];
if (isset($psCategories['category'])) {
    $psCategories = $psCategories['category'];
}

$beforePsIds = [];
foreach ($psCategories as $cat) {
    if (isset($cat['id'])) {
        $beforePsIds[] = (int) $cat['id'];
    }
}
sort($beforePsIds);

echo "📦 BEFORE - PrestaShop has: " . implode(', ', $beforePsIds) . "\n\n";

// ═══════════════════════════════════════════════════════════════════
// STEP 2: MODIFY CATEGORIES (Remove one, add different one)
// ═══════════════════════════════════════════════════════════════════

echo "═══ STEP 2: Modifying Categories ═══\n";

// Strategy: Remove category ID 2350, keep others
$newPsIds = array_diff($oldPsIds, [2350]);
$newPsIds = array_values($newPsIds); // Re-index

echo "🔧 Removing PrestaShop category: 2350\n";
echo "🔧 New PrestaShop IDs: " . implode(', ', $newPsIds) . "\n\n";

// Build new mappings (we need to find PPM IDs for these PS IDs)
$newMappings = [
    'ui' => ['selected' => [], 'primary' => null],
    'mappings' => [],
    'metadata' => [
        'last_updated' => now()->toIso8601String(),
        'source' => 'manual',
    ],
];

// Reverse lookup: PS ID → PPM ID
$reverseMappings = [];
if (isset($oldMappings['mappings'])) {
    foreach ($oldMappings['mappings'] as $ppmId => $psId) {
        $reverseMappings[(int)$psId] = (int)$ppmId;
    }
}

foreach ($newPsIds as $psId) {
    if (isset($reverseMappings[$psId])) {
        $ppmId = $reverseMappings[$psId];
        $newMappings['ui']['selected'][] = $ppmId;
        $newMappings['mappings'][(string)$ppmId] = $psId;
    }
}

$newMappings['ui']['primary'] = $newMappings['ui']['selected'][0] ?? null;

echo "📝 New category_mappings structure:\n";
echo json_encode($newMappings, JSON_PRETTY_PRINT) . "\n\n";

// Save to database
$shopData->category_mappings = $newMappings;
$shopData->sync_status = 'pending';
$shopData->save();

echo "✅ Saved new mappings to ProductShopData\n";
echo "✅ Set sync_status = 'pending'\n\n";

// ═══════════════════════════════════════════════════════════════════
// STEP 3: DISPATCH SYNC JOB
// ═══════════════════════════════════════════════════════════════════

echo "═══ STEP 3: Dispatching Sync Job ═══\n";

try {
    $job = new SyncProductToPrestaShop($product, $shop);

    // Dispatch synchronously for testing
    echo "🚀 Dispatching job...\n";
    dispatch_sync($job);

    echo "✅ Job completed\n\n";

} catch (\Exception $e) {
    echo "❌ Job FAILED: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    exit(1);
}

// ═══════════════════════════════════════════════════════════════════
// STEP 4: VERIFY - Check PrestaShop has new categories
// ═══════════════════════════════════════════════════════════════════

echo "═══ STEP 4: Verification (AFTER) ═══\n";

// Wait a bit for PrestaShop to process
sleep(2);

// Get updated PrestaShop state
$psProduct = $client->getProduct($shopData->prestashop_product_id);
if (isset($psProduct['product'])) {
    $psProduct = $psProduct['product'];
}

$psCategories = $psProduct['associations']['categories'] ?? [];
if (isset($psCategories['category'])) {
    $psCategories = $psCategories['category'];
}

$afterPsIds = [];
foreach ($psCategories as $cat) {
    if (isset($cat['id'])) {
        $afterPsIds[] = (int) $cat['id'];
    }
}
sort($afterPsIds);

echo "📦 AFTER - PPM expects: " . implode(', ', $newPsIds) . "\n";
echo "📦 AFTER - PrestaShop has: " . implode(', ', $afterPsIds) . "\n\n";

// ═══════════════════════════════════════════════════════════════════
// STEP 5: COMPARISON
// ═══════════════════════════════════════════════════════════════════

echo "═══ STEP 5: Result ═══\n";

if ($newPsIds === $afterPsIds) {
    echo "✅ ✅ ✅ SUCCESS! Categories synchronized correctly! ✅ ✅ ✅\n\n";
    echo "   BEFORE: " . implode(', ', $beforePsIds) . "\n";
    echo "   AFTER:  " . implode(', ', $afterPsIds) . "\n";
    echo "   REMOVED: 2350\n\n";
    echo "🎉 SYNC IS WORKING!\n\n";
} else {
    echo "❌ ❌ ❌ FAILED! Categories NOT synchronized! ❌ ❌ ❌\n\n";
    echo "   Expected: " . implode(', ', $newPsIds) . "\n";
    echo "   Got:      " . implode(', ', $afterPsIds) . "\n\n";

    $missing = array_diff($newPsIds, $afterPsIds);
    $extra = array_diff($afterPsIds, $newPsIds);

    if (!empty($missing)) {
        echo "   Missing: " . implode(', ', $missing) . "\n";
    }
    if (!empty($extra)) {
        echo "   Extra: " . implode(', ', $extra) . "\n";
    }

    echo "\n🚨 SYNC IS BROKEN!\n\n";
}

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST COMPLETE                                                   ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";
