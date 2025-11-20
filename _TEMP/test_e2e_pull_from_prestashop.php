<?php
// TEST E2E #2: Pull from PrestaShop → PPM

require 'vendor/autoload.php';

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ProductShopData;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\CategoryMappingsConverter;
use Illuminate\Support\Facades\Log;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST E2E #2: Pull Categories from PrestaShop → PPM             ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

$testProductId = 11033;
$testShopId = 1;

// ═══════════════════════════════════════════════════════════════════
// STEP 1: RESTORE category 2350 in PrestaShop (via API)
// ═══════════════════════════════════════════════════════════════════

echo "═══ STEP 1: Restore Category 2350 in PrestaShop ═══\n";

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

$client = PrestaShopClientFactory::create($shop);
$prestashopProductId = $shopData->prestashop_product_id;

// Get current categories from PrestaShop
$psProduct = $client->getProduct($prestashopProductId);
if (isset($psProduct['product'])) {
    $psProduct = $psProduct['product'];
}

$psCategories = $psProduct['associations']['categories'] ?? [];
if (isset($psCategories['category'])) {
    $psCategories = $psCategories['category'];
}

$currentPsIds = [];
foreach ($psCategories as $cat) {
    if (isset($cat['id'])) {
        $currentPsIds[] = (int) $cat['id'];
    }
}
sort($currentPsIds);

echo "📦 Current PrestaShop categories: " . implode(', ', $currentPsIds) . "\n\n";

// Add category 2350 if not present
if (!in_array(2350, $currentPsIds)) {
    echo "🔧 Adding category 2350...\n";

    $newPsIds = array_merge($currentPsIds, [2350]);
    sort($newPsIds);

    // Build associations array for PrestaShop
    $associations = [];
    foreach ($newPsIds as $psId) {
        $associations[] = ['id' => $psId];
    }

    // Update product via API
    $updatePayload = [
        'product' => [
            'associations' => [
                'categories' => ['category' => $associations]
            ]
        ]
    ];

    try {
        $client->updateProduct($prestashopProductId, $updatePayload);
        echo "✅ Category 2350 added to PrestaShop\n\n";

        // Verify
        sleep(1);
        $psProduct = $client->getProduct($prestashopProductId);
        if (isset($psProduct['product'])) {
            $psProduct = $psProduct['product'];
        }

        $psCategories = $psProduct['associations']['categories'] ?? [];
        if (isset($psCategories['category'])) {
            $psCategories = $psCategories['category'];
        }

        $verifyPsIds = [];
        foreach ($psCategories as $cat) {
            if (isset($cat['id'])) {
                $verifyPsIds[] = (int) $cat['id'];
            }
        }
        sort($verifyPsIds);

        echo "📦 Verified PrestaShop categories: " . implode(', ', $verifyPsIds) . "\n\n";

    } catch (\Exception $e) {
        echo "❌ Failed to add category 2350: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "✅ Category 2350 already present in PrestaShop\n\n";
    $verifyPsIds = $currentPsIds;
}

// ═══════════════════════════════════════════════════════════════════
// STEP 2: PULL from PrestaShop (simulate ProductForm::pullShopData)
// ═══════════════════════════════════════════════════════════════════

echo "═══ STEP 2: Pull from PrestaShop (simulate pullShopData) ═══\n";

echo "📦 PPM BEFORE pull:\n";
if ($shopData->category_mappings && isset($shopData->category_mappings['mappings'])) {
    $ppmPsIds = array_values($shopData->category_mappings['mappings']);
    sort($ppmPsIds);
    echo "   category_mappings: " . implode(', ', $ppmPsIds) . "\n\n";
} else {
    echo "   category_mappings: NULL or empty\n\n";
}

// Simulate pullShopData logic (lines 3992-4026 in ProductForm.php)
try {
    $converter = app(CategoryMappingsConverter::class);
    $categoryMappings = $converter->fromPrestaShopFormat($verifyPsIds, $shop);

    Log::debug('[TEST E2E #2] Converted PrestaShop to Option A', [
        'shop_id' => $shop->id,
        'prestashop_ids' => $verifyPsIds,
        'canonical_format' => $categoryMappings,
    ]);

    // Update ProductShopData
    $shopData->category_mappings = $categoryMappings;
    $shopData->last_pulled_at = now();
    $shopData->save();

    echo "✅ Pulled categories from PrestaShop\n";
    echo "✅ Converted to Option A format\n";
    echo "✅ Saved to ProductShopData\n\n";

} catch (\Exception $e) {
    echo "❌ Pull FAILED: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

// ═══════════════════════════════════════════════════════════════════
// STEP 3: VERIFY - Check PPM has new categories
// ═══════════════════════════════════════════════════════════════════

echo "═══ STEP 3: Verification (AFTER pull) ═══\n";

// Refresh model
$shopData->refresh();

echo "📦 PPM AFTER pull:\n";
if ($shopData->category_mappings) {
    echo "   Structure:\n" . json_encode($shopData->category_mappings, JSON_PRETTY_PRINT) . "\n\n";

    if (isset($shopData->category_mappings['mappings'])) {
        $ppmPsIdsAfter = array_values($shopData->category_mappings['mappings']);
        sort($ppmPsIdsAfter);
        echo "   Mappings values (PrestaShop IDs): " . implode(', ', $ppmPsIdsAfter) . "\n\n";
    }
} else {
    echo "   category_mappings: NULL or empty\n\n";
}

// ═══════════════════════════════════════════════════════════════════
// STEP 4: COMPARISON
// ═══════════════════════════════════════════════════════════════════

echo "═══ STEP 4: Result ═══\n";

if (isset($ppmPsIdsAfter) && $verifyPsIds === $ppmPsIdsAfter) {
    echo "✅ ✅ ✅ SUCCESS! Pull worked correctly! ✅ ✅ ✅\n\n";
    echo "   PrestaShop has: " . implode(', ', $verifyPsIds) . "\n";
    echo "   PPM pulled:      " . implode(', ', $ppmPsIdsAfter) . "\n\n";
    echo "🎉 PULL IS WORKING!\n\n";
} else {
    echo "❌ ❌ ❌ FAILED! Pull did NOT work! ❌ ❌ ❌\n\n";
    echo "   PrestaShop has: " . implode(', ', $verifyPsIds) . "\n";
    echo "   PPM has:        " . implode(', ', $ppmPsIdsAfter ?? []) . "\n\n";

    $missing = array_diff($verifyPsIds, $ppmPsIdsAfter ?? []);
    $extra = array_diff($ppmPsIdsAfter ?? [], $verifyPsIds);

    if (!empty($missing)) {
        echo "   Missing in PPM: " . implode(', ', $missing) . "\n";
    }
    if (!empty($extra)) {
        echo "   Extra in PPM: " . implode(', ', $extra) . "\n";
    }

    echo "\n🚨 PULL IS BROKEN!\n\n";
}

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST COMPLETE                                                   ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";
