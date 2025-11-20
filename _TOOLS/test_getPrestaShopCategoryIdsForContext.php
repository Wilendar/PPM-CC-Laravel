<?php

/**
 * Diagnostic: Test getPrestaShopCategoryIdsForContext()
 *
 * ETAP_07b FAZA 1 - Debug why checkboxes are NOT checked
 *
 * Usage:
 * php _TOOLS/test_getPrestaShopCategoryIdsForContext.php
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Models\ProductShopData;
use App\Models\PrestaShopShop;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║  DIAGNOSTIC: getPrestaShopCategoryIdsForContext() Debug           ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Product 11034, Shop 1
$productId = 11034;
$shopId = 1;

echo "[STEP 1] Loading product_shop_data.category_mappings...\n";
echo "────────────────────────────────────────────────────────────────────\n";

$psd = ProductShopData::where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

if (!$psd || !$psd->category_mappings) {
    echo "❌ ERROR: No category_mappings found\n";
    exit(1);
}

$categoryMappings = $psd->category_mappings;

echo "Raw category_mappings:\n";
echo json_encode($categoryMappings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
echo "\n";

echo "Extracted data:\n";
echo "  ui.selected: " . json_encode($categoryMappings['ui']['selected']) . " (PPM IDs)\n";
echo "  ui.primary: " . ($categoryMappings['ui']['primary'] ?? 'null') . "\n";
echo "  mappings: " . json_encode($categoryMappings['mappings']) . "\n";
echo "\n";

echo "[STEP 2] Simulating ProductCategoryManager::loadShopCategories()...\n";
echo "────────────────────────────────────────────────────────────────────\n";

$shopCategories = [
    'selected' => $categoryMappings['ui']['selected'] ?? [],
    'primary' => $categoryMappings['ui']['primary'] ?? null,
    'mappings' => $categoryMappings['mappings'] ?? [],
];

echo "shopCategories array:\n";
echo "  selected: " . json_encode($shopCategories['selected']) . "\n";
echo "  primary: " . ($shopCategories['primary'] ?? 'null') . "\n";
echo "  mappings: " . json_encode($shopCategories['mappings']) . "\n";
echo "  mappings_count: " . count($shopCategories['mappings']) . "\n";
echo "\n";

echo "[STEP 3] Simulating getPrestaShopCategoryIdsForContext()...\n";
echo "────────────────────────────────────────────────────────────────────\n";

$ppmIds = $shopCategories['selected'];
$mappings = $shopCategories['mappings'];

echo "Input:\n";
echo "  PPM IDs: " . json_encode($ppmIds) . "\n";
echo "  Mappings: " . json_encode($mappings) . "\n";
echo "\n";

if (empty($mappings)) {
    echo "❌ CRITICAL: Mappings array is EMPTY!\n";
    echo "   This is why no checkboxes are checked.\n";
    exit(1);
}

$prestashopIds = [];
foreach ($ppmIds as $ppmId) {
    $mappingKey = (string) $ppmId;
    echo "  Converting PPM ID {$ppmId} (key: '{$mappingKey}')...\n";

    if (isset($mappings[$mappingKey])) {
        $prestashopId = (int) $mappings[$mappingKey];
        $prestashopIds[] = $prestashopId;
        echo "    ✅ Found mapping: PPM {$ppmId} → PS {$prestashopId}\n";
    } else {
        echo "    ❌ NO mapping found for PPM ID {$ppmId}\n";
        echo "    Available mapping keys: " . json_encode(array_keys($mappings)) . "\n";
    }
}

echo "\n";
echo "Output:\n";
echo "  PrestaShop IDs: " . json_encode($prestashopIds) . "\n";
echo "\n";

echo "[STEP 4] Verifying PrestaShop API categories...\n";
echo "────────────────────────────────────────────────────────────────────\n";

$shop = PrestaShopShop::find($shopId);
$categoryService = app(\App\Services\PrestaShop\PrestaShopCategoryService::class);
$tree = $categoryService->getCachedCategoryTree($shop);

echo "Tree contains " . count($tree) . " root categories\n";

function findCategoryInTreeById($categoryId, $tree) {
    foreach ($tree as $node) {
        if ($node['id'] == $categoryId) {
            return $node;
        }
        if (!empty($node['children'])) {
            $found = findCategoryInTreeById($categoryId, $node['children']);
            if ($found) return $found;
        }
    }
    return null;
}

echo "\nChecking if PrestaShop IDs exist in tree:\n";
foreach ($prestashopIds as $psId) {
    $found = findCategoryInTreeById($psId, $tree);
    if ($found) {
        echo "  ✅ [{$psId}] {$found['name']} - Found in tree\n";
    } else {
        echo "  ❌ [{$psId}] - NOT found in tree\n";
    }
}

echo "\n";

echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║                         FINAL VERDICT                              ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

if (empty($mappings)) {
    echo "❌ PROBLEM: Mappings array is EMPTY\n";
    echo "   → ProductCategoryManager::loadShopCategories() nie ładuje 'mappings'\n";
    echo "   → Sprawdź czy linia 110 została dodana do ProductCategoryManager.php\n";
} elseif (empty($prestashopIds)) {
    echo "❌ PROBLEM: No PrestaShop IDs after conversion\n";
    echo "   → Mappings exist but conversion failed\n";
    echo "   → Check mapping key format (string vs int)\n";
} elseif (count($prestashopIds) < count($ppmIds)) {
    echo "⚠️ PROBLEM: Only " . count($prestashopIds) . "/" . count($ppmIds) . " IDs converted\n";
    echo "   → Some PPM IDs have no PrestaShop mapping\n";
} else {
    echo "✅ SUCCESS: All " . count($prestashopIds) . " IDs converted correctly\n";
    echo "   PrestaShop IDs that should be checked: " . json_encode($prestashopIds) . "\n";
}

echo "\n";
