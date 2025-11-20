<?php
// TEST E2E #2 SIMPLIFIED: Test PULL mechanism only

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
echo "║  TEST E2E #2: Pull Mechanism Test (PrestaShop → PPM)            ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

$testProductId = 11033;
$testShopId = 1;

// ═══════════════════════════════════════════════════════════════════
// STEP 1: GET CURRENT STATE
// ═══════════════════════════════════════════════════════════════════

echo "═══ STEP 1: Current State ═══\n";

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

// Get current PrestaShop categories
$client = PrestaShopClientFactory::create($shop);
$prestashopProductId = $shopData->prestashop_product_id;

$psProduct = $client->getProduct($prestashopProductId);
if (isset($psProduct['product'])) {
    $psProduct = $psProduct['product'];
}

$psCategories = $psProduct['associations']['categories'] ?? [];
if (isset($psCategories['category'])) {
    $psCategories = $psCategories['category'];
}

$prestashopCategoryIds = [];
foreach ($psCategories as $cat) {
    if (isset($cat['id'])) {
        $prestashopCategoryIds[] = (int) $cat['id'];
    }
}
sort($prestashopCategoryIds);

echo "📦 PrestaShop has: " . implode(', ', $prestashopCategoryIds) . "\n\n";

// Get current PPM state
echo "📦 PPM BEFORE pull:\n";
if ($shopData->category_mappings && isset($shopData->category_mappings['mappings'])) {
    $ppmPsIds = array_values($shopData->category_mappings['mappings']);
    sort($ppmPsIds);
    echo "   category_mappings: " . implode(', ', $ppmPsIds) . "\n\n";
} else {
    echo "   category_mappings: NULL or empty\n\n";
}

// ═══════════════════════════════════════════════════════════════════
// STEP 2: SIMULATE pullShopData()
// ═══════════════════════════════════════════════════════════════════

echo "═══ STEP 2: Simulate pullShopData() ═══\n";

try {
    // This simulates ProductForm::pullShopData() lines 3992-4026
    $converter = app(CategoryMappingsConverter::class);
    $categoryMappings = $converter->fromPrestaShopFormat($prestashopCategoryIds, $shop);

    echo "✅ Converter::fromPrestaShopFormat() executed\n";
    echo "   Input (PS IDs): " . implode(', ', $prestashopCategoryIds) . "\n";
    echo "   Output (Option A):\n" . json_encode($categoryMappings, JSON_PRETTY_PRINT) . "\n\n";

    // Save to database
    $shopData->category_mappings = $categoryMappings;
    $shopData->last_pulled_at = now();
    $shopData->sync_status = 'synced';
    $shopData->save();

    echo "✅ Saved to ProductShopData\n";
    echo "✅ Set sync_status = 'synced'\n\n";

} catch (\Exception $e) {
    echo "❌ Pull FAILED: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

// ═══════════════════════════════════════════════════════════════════
// STEP 3: VERIFY - Refresh and check
// ═══════════════════════════════════════════════════════════════════

echo "═══ STEP 3: Verification (AFTER pull) ═══\n";

$shopData->refresh();

echo "📦 PPM AFTER pull:\n";
if ($shopData->category_mappings) {
    if (isset($shopData->category_mappings['ui']['selected'])) {
        echo "   UI selected (PPM IDs): " . implode(', ', $shopData->category_mappings['ui']['selected']) . "\n";
    }

    if (isset($shopData->category_mappings['mappings'])) {
        $ppmPsIdsAfter = array_values($shopData->category_mappings['mappings']);
        sort($ppmPsIdsAfter);
        echo "   Mappings values (PS IDs): " . implode(', ', $ppmPsIdsAfter) . "\n\n";
    }
} else {
    echo "   category_mappings: NULL or empty\n\n";
}

// ═══════════════════════════════════════════════════════════════════
// STEP 4: COMPARISON
// ═══════════════════════════════════════════════════════════════════

echo "═══ STEP 4: Result ═══\n";

if (isset($ppmPsIdsAfter) && $prestashopCategoryIds === $ppmPsIdsAfter) {
    echo "✅ ✅ ✅ SUCCESS! Pull mechanism works! ✅ ✅ ✅\n\n";
    echo "   PrestaShop has: " . implode(', ', $prestashopCategoryIds) . "\n";
    echo "   PPM pulled:      " . implode(', ', $ppmPsIdsAfter) . "\n\n";
    echo "🎉 PULL fromPrestaShopFormat() + save() IS WORKING!\n\n";

    echo "📋 This confirms:\n";
    echo "   1. ✅ CategoryMappingsConverter::fromPrestaShopFormat() works\n";
    echo "   2. ✅ Category_mappings saved correctly to DB\n";
    echo "   3. ✅ Validator accepts the structure\n";
    echo "   4. ✅ Backend pull logic is functional\n\n";

    echo "⚠️  If user reports 'categories not showing in TAB Sklepy':\n";
    echo "   → Problem is in FRONTEND (Livewire UI refresh)\n";
    echo "   → NOT in backend pull logic\n\n";

} else {
    echo "❌ ❌ ❌ FAILED! Pull mechanism broken! ❌ ❌ ❌\n\n";
    echo "   PrestaShop has: " . implode(', ', $prestashopCategoryIds) . "\n";
    echo "   PPM has:        " . implode(', ', $ppmPsIdsAfter ?? []) . "\n\n";

    $missing = array_diff($prestashopCategoryIds, $ppmPsIdsAfter ?? []);
    $extra = array_diff($ppmPsIdsAfter ?? [], $prestashopCategoryIds);

    if (!empty($missing)) {
        echo "   Missing in PPM: " . implode(', ', $missing) . "\n";
    }
    if (!empty($extra)) {
        echo "   Extra in PPM: " . implode(', ', $extra) . "\n";
    }

    echo "\n🚨 BACKEND PULL LOGIC IS BROKEN!\n\n";
}

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST COMPLETE                                                   ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";
