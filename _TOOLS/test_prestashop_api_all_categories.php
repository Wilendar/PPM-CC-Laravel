<?php

/**
 * Test PrestaShop API - All Categories Fetch
 *
 * ETAP_07b FAZA 1 - Debug getCachedCategoryTree()
 *
 * Direct API call to see what PrestaShop returns for /categories?display=full
 *
 * Usage:
 * php _TOOLS/test_prestashop_api_all_categories.php
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopCategoryService;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║     DIAGNOSTIC: PrestaShop API - All Categories (Shop 1)         ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Get shop
$shop = PrestaShopShop::find(1);

if (!$shop) {
    echo "❌ ERROR: Shop 1 not found\n";
    exit(1);
}

echo "✅ Shop: {$shop->name}\n";
echo "   URL: {$shop->url}\n";
echo "   Version: PrestaShop {$shop->version}.x\n";
echo "\n";

// Get category service
$categoryService = app(PrestaShopCategoryService::class);

echo "[TEST 1] Fetching flat categories from PrestaShop API...\n";
echo "────────────────────────────────────────────────────────────────────\n";

try {
    $flatCategories = $categoryService->fetchCategoriesFromShop($shop);

    echo "✅ Fetched " . count($flatCategories) . " categories from PrestaShop\n";
    echo "\n";

    echo "Raw categories (first 10):\n";
    $count = 0;
    foreach ($flatCategories as $cat) {
        $count++;
        if ($count > 10) break;

        echo "   [{$cat['id']}] {$cat['name']} (parent: {$cat['id_parent']}, level: {$cat['level_depth']})\n";
    }
    echo "\n";

} catch (\Exception $e) {
    echo "❌ ERROR: Failed to fetch categories: {$e->getMessage()}\n";
    exit(1);
}

echo "[TEST 2] Building category tree...\n";
echo "────────────────────────────────────────────────────────────────────\n";

try {
    $tree = $categoryService->buildCategoryTree($flatCategories);

    echo "✅ Tree built with " . count($tree) . " root categories\n";
    echo "\n";

    echo "Root categories:\n";
    foreach ($tree as $root) {
        echo "   [{$root['id']}] {$root['name']} (children: " . count($root['children']) . ")\n";
    }
    echo "\n";

    // Display tree structure (first 2 levels)
    echo "Tree structure (2 levels):\n";
    foreach ($tree as $root) {
        echo "└─ [{$root['id']}] {$root['name']}\n";

        if (!empty($root['children'])) {
            $childCount = 0;
            foreach ($root['children'] as $child) {
                $childCount++;
                if ($childCount > 5) {
                    echo "   ... (and " . (count($root['children']) - 5) . " more)\n";
                    break;
                }
                echo "   └─ [{$child['id']}] {$child['name']}\n";
            }
        }
    }
    echo "\n";

} catch (\Exception $e) {
    echo "❌ ERROR: Failed to build tree: {$e->getMessage()}\n";
    exit(1);
}

echo "[TEST 3] Checking specific categories from product 11034...\n";
echo "────────────────────────────────────────────────────────────────────\n";

// Product 11034 should have these PrestaShop categories:
$productCategories = [2, 12, 23]; // Wszystko, PITGANG, Pit Bike

foreach ($productCategories as $psId) {
    $found = null;
    foreach ($flatCategories as $cat) {
        if ($cat['id'] == $psId) {
            $found = $cat;
            break;
        }
    }

    if ($found) {
        echo "   ✅ [{$psId}] {$found['name']} (parent: {$found['id_parent']}, level: {$found['level_depth']})\n";
    } else {
        echo "   ❌ [{$psId}] NOT FOUND in PrestaShop API response\n";
    }
}
echo "\n";

echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║                         SUMMARY                                    ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "📊 Statistics:\n";
echo "   Total categories in PrestaShop: " . count($flatCategories) . "\n";
echo "   Root categories in tree: " . count($tree) . "\n";
echo "   Product 11034 categories found: " . count(array_filter($productCategories, function($psId) use ($flatCategories) {
    foreach ($flatCategories as $cat) {
        if ($cat['id'] == $psId) return true;
    }
    return false;
})) . "/3\n";
echo "\n";

echo "🎯 Analysis:\n";
echo "   If root categories < 2 → Problem in buildCategoryTree() logic\n";
echo "   If product categories not found → Problem in API response\n";
echo "   If tree structure wrong → Problem in parent-child relationships\n";
echo "\n";
