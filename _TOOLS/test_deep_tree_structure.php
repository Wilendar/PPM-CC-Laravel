<?php

/**
 * Deep Tree Structure Diagnostic
 *
 * ETAP_07b FAZA 1 - Verify tree builds ALL levels recursively
 *
 * Usage:
 * php _TOOLS/test_deep_tree_structure.php
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
echo "║         DIAGNOSTIC: Deep Tree Structure (All Levels)              ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$shop = PrestaShopShop::find(1);
$categoryService = app(PrestaShopCategoryService::class);

echo "[STEP 1] Fetching categories and building tree...\n\n";

$flatCategories = $categoryService->fetchCategoriesFromShop($shop);
$tree = $categoryService->buildCategoryTree($flatCategories);

echo "✅ Tree built: " . count($tree) . " root(s), " . count($flatCategories) . " total categories\n\n";

// Recursive display function
function displayDeepTree($categories, $level = 0, $maxDisplay = 3) {
    $indent = str_repeat('  ', $level);
    $arrow = $level > 0 ? '└─ ' : '';

    foreach ($categories as $cat) {
        echo "{$indent}{$arrow}[{$cat['id']}] {$cat['name']} (level: {$cat['level']})";

        // Highlight if this is one of the product categories
        if (in_array($cat['id'], [2, 12, 23])) {
            echo " ✅ PRODUCT CATEGORY";
        }

        echo "\n";

        // Recursively display children
        if (!empty($cat['children'])) {
            echo "{$indent}   (children: " . count($cat['children']) . ")\n";

            // Display first few children, or all if few
            $childCount = 0;
            foreach ($cat['children'] as $child) {
                $childCount++;
                if ($childCount > $maxDisplay && count($cat['children']) > $maxDisplay + 2) {
                    echo "{$indent}   ... (and " . (count($cat['children']) - $maxDisplay) . " more children)\n";
                    break;
                }
                displayDeepTree([$child], $level + 1, $maxDisplay);
            }
        }
    }
}

echo "[STEP 2] Full tree structure (focusing on product categories)...\n";
echo "────────────────────────────────────────────────────────────────────\n";

displayDeepTree($tree);

echo "────────────────────────────────────────────────────────────────────\n\n";

echo "[STEP 3] Searching for product categories in tree...\n\n";

function searchCategoryInTree($categoryId, $categories, $path = []) {
    foreach ($categories as $cat) {
        $currentPath = array_merge($path, [['id' => $cat['id'], 'name' => $cat['name']]]);

        if ($cat['id'] == $categoryId) {
            return $currentPath;
        }

        if (!empty($cat['children'])) {
            $found = searchCategoryInTree($categoryId, $cat['children'], $currentPath);
            if ($found) {
                return $found;
            }
        }
    }

    return null;
}

$productCategories = [2 => 'Wszystko', 12 => 'PITGANG', 23 => 'Pit Bike'];

foreach ($productCategories as $psId => $name) {
    $path = searchCategoryInTree($psId, $tree);

    if ($path) {
        echo "✅ [{$psId}] {$name} - FOUND in tree\n";
        echo "   Path: ";
        $pathNames = array_map(fn($p) => $p['name'], $path);
        echo implode(' → ', $pathNames) . "\n";
    } else {
        echo "❌ [{$psId}] {$name} - NOT FOUND in tree (CRITICAL BUG!)\n";
    }
}

echo "\n";

echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║                         FINAL VERDICT                              ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$allFound = true;
foreach ([2, 12, 23] as $psId) {
    if (!searchCategoryInTree($psId, $tree)) {
        $allFound = false;
        break;
    }
}

if ($allFound) {
    echo "✅ ALL 3 product categories are in the tree\n";
    echo "   → Problem is in UI rendering (Blade/Livewire)\n";
    echo "   → Check convertCategoryArrayToObject() and category-tree-item.blade.php\n";
} else {
    echo "❌ SOME categories are MISSING from tree\n";
    echo "   → Problem is in buildCategoryTree() logic\n";
    echo "   → Tree is not building complete hierarchy\n";
}

echo "\n";
