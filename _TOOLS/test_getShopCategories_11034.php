<?php

/**
 * Diagnostic Tool: getShopCategories() Output Inspection
 *
 * ETAP_07b FAZA 1 - Debug UI Rendering Issue
 *
 * Simulates ProductForm->getShopCategories() workflow to understand:
 * 1. Which root categories are returned?
 * 2. Are all selected categories included in the tree?
 * 3. Is tree structure correct?
 *
 * Usage:
 * php _TOOLS/test_getShopCategories_11034.php
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductShopData;
use App\Services\PrestaShop\PrestaShopCategoryService;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║  DIAGNOSTIC: getShopCategories() Output (Product 11034, Shop 1)  ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// STEP 1: Get product and shop
echo "[STEP 1] Loading product 11034 and shop 1...\n";

$product = Product::find(11034);
if (!$product) {
    echo "❌ ERROR: Product 11034 not found\n";
    exit(1);
}

$shop = PrestaShopShop::find(1);
if (!$shop) {
    echo "❌ ERROR: Shop 1 not found\n";
    exit(1);
}

echo "✅ Product: {$product->name} (SKU: {$product->sku})\n";
echo "✅ Shop: {$shop->name}\n";
echo "\n";

// STEP 2: Check product_shop_data.category_mappings
echo "[STEP 2] Checking product_shop_data.category_mappings...\n";

$productShopData = ProductShopData::where('product_id', 11034)
    ->where('shop_id', 1)
    ->first();

if (!$productShopData || !$productShopData->category_mappings) {
    echo "❌ ERROR: No category_mappings found\n";
    exit(1);
}

$categoryMappings = $productShopData->category_mappings;

echo "✅ category_mappings JSON loaded:\n";
echo "   ui.selected: " . json_encode($categoryMappings['ui']['selected']) . "\n";
echo "   ui.primary: " . ($categoryMappings['ui']['primary'] ?? 'null') . "\n";
echo "\n";

$selectedIds = $categoryMappings['ui']['selected'];

// STEP 3: Check PPM categories structure
echo "[STEP 3] PPM Categories Structure...\n";
echo "────────────────────────────────────────────────────────────────────\n";

$allCategories = Category::all();

echo "All Categories:\n";
foreach ($allCategories as $cat) {
    $isSelected = in_array($cat->id, $selectedIds) ? "✅ SELECTED" : "";
    $parentInfo = $cat->parent_id ? "Parent: {$cat->parent_id}" : "ROOT";
    echo "   [{$cat->id}] {$cat->name} ({$parentInfo}) {$isSelected}\n";
}
echo "\n";

// STEP 4: Simulate getShopCategories() call
echo "[STEP 4] Simulating getShopCategories() call...\n";
echo "────────────────────────────────────────────────────────────────────\n";

try {
    $categoryService = app(PrestaShopCategoryService::class);
    $tree = $categoryService->getCachedCategoryTree($shop);

    echo "✅ getCachedCategoryTree() returned " . count($tree) . " root categories\n";
    echo "\n";

    // Display tree structure
    function displayTreeRecursive($category, $level = 0, $selectedIds = []) {
        $indent = str_repeat('  ', $level);
        $arrow = $level > 0 ? '└─ ' : '';
        $isSelected = in_array($category->id, $selectedIds) ? "✅" : "❌";

        echo "{$indent}{$arrow}[{$category->id}] {$category->name} {$isSelected}\n";

        if (isset($category->children) && $category->children) {
            foreach ($category->children as $child) {
                displayTreeRecursive($child, $level + 1, $selectedIds);
            }
        }
    }

    echo "Tree Structure (✅ = should be checked):\n";
    foreach ($tree as $root) {
        displayTreeRecursive($root, 0, $selectedIds);
    }
    echo "\n";

} catch (\Exception $e) {
    echo "❌ ERROR: Failed to get category tree: {$e->getMessage()}\n";
    echo "   Trace: {$e->getTraceAsString()}\n";
    exit(1);
}

echo "────────────────────────────────────────────────────────────────────\n";
echo "\n";

// STEP 5: Check if all selected categories are in tree
echo "[STEP 5] Verifying all selected categories are in tree...\n";

function findCategoryInTree($categoryId, $tree) {
    foreach ($tree as $node) {
        if ($node->id == $categoryId) {
            return $node;
        }

        if (isset($node->children) && $node->children) {
            $found = findCategoryInTree($categoryId, $node->children);
            if ($found) {
                return $found;
            }
        }
    }

    return null;
}

$allFoundInTree = true;

foreach ($selectedIds as $selectedId) {
    $found = findCategoryInTree($selectedId, $tree);
    $cat = Category::find($selectedId);
    $catName = $cat ? $cat->name : 'N/A';

    if ($found) {
        echo "   ✅ [{$selectedId}] {$catName} - Found in tree\n";
    } else {
        echo "   ❌ [{$selectedId}] {$catName} - NOT found in tree (PROBLEM!)\n";
        $allFoundInTree = false;
    }
}

echo "\n";

// FINAL SUMMARY
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║                         FINAL SUMMARY                              ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

if ($allFoundInTree) {
    echo "✅ ALL selected categories are in tree\n";
    echo "   Problem is likely in Blade rendering logic or Alpine.js collapse state\n";
} else {
    echo "❌ SOME selected categories are NOT in tree\n";
    echo "   Problem is in getCachedCategoryTree() - it's not returning all roots\n";
}

echo "\n";
echo "📊 Statistics:\n";
echo "   Selected categories: " . count($selectedIds) . "\n";
echo "   Root categories in tree: " . count($tree) . "\n";
echo "   Total PPM categories: " . $allCategories->count() . "\n";
echo "\n";

echo "🎯 Next Steps:\n";
if (!$allFoundInTree) {
    echo "   1. Check PrestaShopCategoryService::getCachedCategoryTree()\n";
    echo "   2. Verify it returns ALL root categories, not just PrestaShop roots\n";
    echo "   3. Check if PPM-created categories are included\n";
} else {
    echo "   1. Check category-tree-item.blade.php rendering\n";
    echo "   2. Verify Alpine.js 'collapsed' state\n";
    echo "   3. Check if trees are collapsed on initial render\n";
}
echo "\n";
