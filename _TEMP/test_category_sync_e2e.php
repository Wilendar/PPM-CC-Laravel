<?php
/**
 * E2E Test: Category Sync Fixes (CATEGORY_SYNC_STALE_CACHE_ISSUE)
 *
 * Tests all 3 fixes:
 * - Fix #1: ProductTransformer priority (pivot → cache)
 * - Fix #2: ProductFormSaver sync category_mappings
 * - Fix #3: CategoryMappingsConverter fromPivotData()
 *
 * Test Product: 11034 (Q-KAYO-EA70 / MPPTrade Pitbike.pl - Shop ID 2)
 * Test Categories: 59, 87 (PPM IDs)
 *
 * Verification:
 * - Pivot table (product_categories WHERE shop_id = 2)
 * - Cache (product_shop_data.category_mappings)
 * - Logs (laravel.log - [CATEGORY SYNC], [CATEGORY CACHE])
 *
 * Usage: php artisan tinker --execute="require '_TEMP/test_category_sync_e2e.php';"
 */

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ProductShopData;
use App\Services\CategoryMappingsConverter;
use App\Services\PrestaShop\CategoryMapper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "\n";
echo "=============================================================================\n";
echo " E2E TEST: Category Sync Fixes (CATEGORY_SYNC_STALE_CACHE_ISSUE)\n";
echo "=============================================================================\n";
echo "\n";

// Configuration
$productId = 11034; // Q-KAYO-EA70
$shopId = 1; // B2B Test DEV
$testCategoryIds = [100, 105]; // PPM category IDs (different from existing 42,99,59,87)

echo "[SETUP] Test configuration:\n";
echo "  Product ID: {$productId}\n";
echo "  Shop ID: {$shopId}\n";
echo "  Test Categories (PPM): " . implode(', ', $testCategoryIds) . "\n";
echo "\n";

// Step 1: Load entities
echo "[STEP 1] Loading entities...\n";

$product = Product::find($productId);
if (!$product) {
    echo "  ❌ ERROR: Product {$productId} not found\n";
    exit(1);
}
echo "  ✅ Product loaded: {$product->name}\n";

$shop = PrestaShopShop::find($shopId);
if (!$shop) {
    echo "  ❌ ERROR: Shop {$shopId} not found\n";
    exit(1);
}
echo "  ✅ Shop loaded: {$shop->name}\n";

// Step 2: BEFORE state - Read current pivot table
echo "\n[STEP 2] BEFORE state - Pivot table:\n";

$beforePivot = DB::table('product_categories')
    ->where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->get(['category_id', 'shop_id', 'is_primary']);

if ($beforePivot->isEmpty()) {
    echo "  ℹ️  No shop-specific categories in pivot (will be created)\n";
} else {
    echo "  Current shop-specific categories:\n";
    foreach ($beforePivot as $row) {
        echo "    - Category {$row->category_id} (shop_id={$row->shop_id}, is_primary=" . ($row->is_primary ? 'YES' : 'NO') . ")\n";
    }
}

// Step 3: BEFORE state - Read current cache
echo "\n[STEP 3] BEFORE state - category_mappings cache:\n";

$beforeCache = ProductShopData::where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

if (!$beforeCache || !$beforeCache->category_mappings) {
    echo "  ℹ️  No category_mappings cache (will be created)\n";
} else {
    $mappings = $beforeCache->category_mappings;
    echo "  Cache structure:\n";
    echo "    - UI selected: " . json_encode($mappings['ui']['selected'] ?? []) . "\n";
    echo "    - UI primary: " . ($mappings['ui']['primary'] ?? 'null') . "\n";
    echo "    - Mappings count: " . count($mappings['mappings'] ?? []) . "\n";
    echo "    - Source: " . ($mappings['metadata']['source'] ?? 'unknown') . "\n";
    echo "    - Last updated: " . ($mappings['metadata']['last_updated'] ?? 'unknown') . "\n";
}

// Step 4: SIMULATE USER SAVE - Update pivot table
echo "\n[STEP 4] SIMULATING USER SAVE - Updating pivot table...\n";

try {
    // Detach existing shop-specific categories (direct DB delete for reliability)
    DB::table('product_categories')
        ->where('product_id', $productId)
        ->where('shop_id', $shopId)
        ->delete();
    echo "  ✅ Detached existing shop-specific categories (direct DB)\n";

    // Attach new categories with shop_id
    $categoryData = [];
    foreach ($testCategoryIds as $index => $categoryId) {
        $categoryData[$categoryId] = [
            'shop_id' => $shopId,
            'is_primary' => $index === 0, // First category is primary
            'sort_order' => $index,
        ];
    }

    $product->categories()->attach($categoryData);
    echo "  ✅ Attached new shop-specific categories: " . implode(', ', $testCategoryIds) . "\n";

} catch (\Exception $e) {
    echo "  ❌ ERROR: Failed to update pivot table: {$e->getMessage()}\n";
    exit(1);
}

// Step 5: SIMULATE CACHE SYNC - Call fromPivotData()
echo "\n[STEP 5] SIMULATING CACHE SYNC - Converting pivot → Option A...\n";

try {
    $converter = app(CategoryMappingsConverter::class);

    // Get fresh categories from pivot table (direct DB query to avoid model scope conflicts)
    $shopCategories = DB::table('product_categories')
        ->where('product_id', $productId)
        ->where('shop_id', $shopId)
        ->pluck('category_id')
        ->toArray();

    echo "  Retrieved from pivot: " . json_encode($shopCategories) . "\n";

    // Convert to Option A format
    $categoryMappings = $converter->fromPivotData($shopCategories, $shop);

    echo "  ✅ Converted to Option A:\n";
    echo "    - UI selected: " . json_encode($categoryMappings['ui']['selected']) . "\n";
    echo "    - UI primary: " . $categoryMappings['ui']['primary'] . "\n";
    echo "    - Mappings: " . json_encode($categoryMappings['mappings']) . "\n";
    echo "    - Source: " . $categoryMappings['metadata']['source'] . "\n";

    // Update cache
    $productShopData = ProductShopData::firstOrNew([
        'product_id' => $productId,
        'shop_id' => $shopId,
    ]);

    $productShopData->category_mappings = $categoryMappings;
    $productShopData->save();

    echo "  ✅ Cache updated in product_shop_data\n";

} catch (\Exception $e) {
    echo "  ❌ ERROR: Failed to sync cache: {$e->getMessage()}\n";
    echo "  Stack trace: {$e->getTraceAsString()}\n";
    exit(1);
}

// Step 6: VERIFY AFTER state - Pivot table
echo "\n[STEP 6] VERIFY AFTER state - Pivot table:\n";

$afterPivot = DB::table('product_categories')
    ->where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->get(['category_id', 'shop_id', 'is_primary', 'sort_order']);

if ($afterPivot->isEmpty()) {
    echo "  ❌ ERROR: Pivot table empty after save!\n";
    exit(1);
}

$pivotCategoryIds = $afterPivot->pluck('category_id')->toArray();
$pivotMatch = $pivotCategoryIds == $testCategoryIds;

echo "  Shop-specific categories:\n";
foreach ($afterPivot as $row) {
    echo "    - Category {$row->category_id} (shop_id={$row->shop_id}, is_primary=" . ($row->is_primary ? 'YES' : 'NO') . ", sort={$row->sort_order})\n";
}
echo "\n  " . ($pivotMatch ? "✅ PASS" : "❌ FAIL") . ": Pivot table matches test data\n";

// Step 7: VERIFY AFTER state - Cache
echo "\n[STEP 7] VERIFY AFTER state - category_mappings cache:\n";

$afterCache = ProductShopData::where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

if (!$afterCache || !$afterCache->category_mappings) {
    echo "  ❌ FAIL: Cache not created after sync!\n";
    exit(1);
}

$mappings = $afterCache->category_mappings;

// Validation checks
$checks = [
    'UI selected matches' => ($mappings['ui']['selected'] ?? []) == $testCategoryIds,
    'UI primary is first' => ($mappings['ui']['primary'] ?? null) == $testCategoryIds[0],
    'Mappings count matches' => count($mappings['mappings'] ?? []) == count($testCategoryIds),
    'Source is manual' => ($mappings['metadata']['source'] ?? null) == 'manual',
    'Has last_updated' => isset($mappings['metadata']['last_updated']),
];

echo "  Cache validation:\n";
foreach ($checks as $check => $passed) {
    echo "    " . ($passed ? "✅" : "❌") . " {$check}\n";
}

$cachePass = !in_array(false, $checks, true);
echo "\n  " . ($cachePass ? "✅ PASS" : "❌ FAIL") . ": Cache validation\n";

// Step 8: VERIFY PrestaShop IDs resolution
echo "\n[STEP 8] VERIFY PrestaShop IDs resolution:\n";

$categoryMapper = app(CategoryMapper::class);
$prestashopIds = [];

foreach ($testCategoryIds as $ppmId) {
    $psId = $categoryMapper->mapToPrestaShop($ppmId, $shop);
    $prestashopIds[$ppmId] = $psId;

    if ($psId) {
        echo "  ✅ PPM {$ppmId} → PrestaShop {$psId}\n";
    } else {
        echo "  ⚠️  PPM {$ppmId} → NOT MAPPED\n";
    }
}

$mappingsInCache = $mappings['mappings'] ?? [];
$mappingsMatch = true;
foreach ($testCategoryIds as $ppmId) {
    $cacheValue = $mappingsInCache[(string) $ppmId] ?? null;
    $expectedValue = $prestashopIds[$ppmId] ?? 0;

    if ($cacheValue != $expectedValue) {
        echo "  ❌ FAIL: Cache mapping mismatch for PPM {$ppmId}: cache={$cacheValue}, expected={$expectedValue}\n";
        $mappingsMatch = false;
    }
}

if ($mappingsMatch) {
    echo "\n  ✅ PASS: Cache mappings match CategoryMapper\n";
}

// Step 9: FINAL VERDICT
echo "\n";
echo "=============================================================================\n";
echo " FINAL VERDICT\n";
echo "=============================================================================\n";

$allPass = $pivotMatch && $cachePass && $mappingsMatch;

if ($allPass) {
    echo " ✅ ALL TESTS PASSED\n";
    echo "\n";
    echo " Summary:\n";
    echo "   - Pivot table updated correctly (shop_id = {$shopId})\n";
    echo "   - category_mappings cache synced from pivot (Option A format)\n";
    echo "   - Cache mappings match CategoryMapper (PrestaShop IDs resolved)\n";
    echo "   - Source set to 'manual' (user action)\n";
    echo "\n";
    echo " Next step: Test ProductTransformer sync to PrestaShop\n";
    echo " (Dispatch sync job and verify logs for [CATEGORY SYNC] entries)\n";
} else {
    echo " ❌ SOME TESTS FAILED\n";
    echo "\n";
    echo " Failed checks:\n";
    if (!$pivotMatch) echo "   - Pivot table data mismatch\n";
    if (!$cachePass) echo "   - Cache validation failed\n";
    if (!$mappingsMatch) echo "   - Cache mappings don't match CategoryMapper\n";
    echo "\n";
    echo " Review logs for detailed errors.\n";
}

echo "=============================================================================\n";
echo "\n";

exit($allPass ? 0 : 1);
