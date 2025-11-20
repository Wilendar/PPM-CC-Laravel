<?php
/**
 * Diagnostic Script: PrestaShop Category Hierarchy Analysis
 *
 * PROBLEM: PPM nie respektuje pełnej struktury kategorii PrestaShop
 * - Brakuje "Baza" (Root, id=1)
 * - Brakuje "Wszystko" (Home, id=2)
 * - PPM pokazuje tylko: Pojazdy → Quad
 * - PrestaShop ma: Baza → Wszystko → PITGANG → Pojazdy → Quad
 *
 * This script will:
 * 1. Fetch ALL categories from PrestaShop (including root)
 * 2. Display full hierarchy tree
 * 3. Show which categories are filtered out
 * 4. Identify root categories (id_parent = 0 or 1)
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\PrestaShop\PrestaShopCategoryService;
use App\Models\PrestaShopShop;
use Illuminate\Support\Facades\Log;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   DIAGNOSTIC: PrestaShop Category Hierarchy Analysis\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Get shop
$shopId = 5; // Test KAYO shop
$shop = PrestaShopShop::find($shopId);

if (!$shop) {
    echo "❌ Shop {$shopId} not found!\n";
    exit(1);
}

echo "🏪 Shop: {$shop->name} (ID: {$shop->id})\n";
echo "🔗 URL: {$shop->url}\n\n";

// Create service
$categoryService = app(PrestaShopCategoryService::class);

// Fetch categories WITHOUT cache
echo "📡 Fetching categories from PrestaShop API...\n\n";

try {
    $categories = $categoryService->fetchCategoriesFromShop($shop);

    echo "✅ Fetched " . count($categories) . " categories\n\n";

    // Display ALL categories (including root)
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "   ALL CATEGORIES (Raw API Response)\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    // Sort by id for easier reading
    usort($categories, fn($a, $b) => $a['id'] <=> $b['id']);

    foreach ($categories as $category) {
        $id = $category['id'];
        $parentId = $category['id_parent'];
        $name = $category['name'];
        $active = $category['active'] ? '✅' : '❌';
        $levelDepth = $category['level_depth'] ?? 0;

        $indent = str_repeat('  ', $levelDepth);

        echo sprintf(
            "%s[%d] %s (parent: %d, depth: %d) %s\n",
            $indent,
            $id,
            $name,
            $parentId,
            $levelDepth,
            $active
        );
    }

    echo "\n";

    // Identify ROOT categories
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "   ROOT CATEGORIES ANALYSIS\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    $rootCategories = array_filter($categories, function ($cat) {
        return in_array($cat['id_parent'], [0, 1]);
    });

    echo "🌳 Found " . count($rootCategories) . " root categories:\n\n";

    foreach ($rootCategories as $root) {
        echo sprintf(
            "  • ID: %d | Name: '%s' | Parent: %d | Depth: %d\n",
            $root['id'],
            $root['name'],
            $root['id_parent'],
            $root['level_depth'] ?? 0
        );
    }

    echo "\n";

    // Build hierarchy tree
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "   FULL HIERARCHY TREE\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    function buildTree(array $categories, int $parentId = 0, int $depth = 0): void
    {
        $children = array_filter($categories, fn($cat) => $cat['id_parent'] == $parentId);

        foreach ($children as $child) {
            $indent = str_repeat('  ', $depth);
            $arrow = $depth > 0 ? '└─ ' : '';

            echo sprintf(
                "%s%s[%d] %s\n",
                $indent,
                $arrow,
                $child['id'],
                $child['name']
            );

            // Recurse
            buildTree($categories, $child['id'], $depth + 1);
        }
    }

    // Start from root (parent_id = 0)
    buildTree($categories, 0, 0);

    // Also check parent_id = 1 (some PrestaShop versions use this)
    buildTree($categories, 1, 0);

    echo "\n";

    // Check what getCachedCategoryTree returns
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "   getCachedCategoryTree() OUTPUT\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    $tree = $categoryService->getCachedCategoryTree($shop);

    echo "🌲 Tree structure returned by getCachedCategoryTree():\n\n";

    function displayTree(array $tree, int $depth = 0): void
    {
        foreach ($tree as $node) {
            $indent = str_repeat('  ', $depth);
            $arrow = $depth > 0 ? '└─ ' : '';

            echo sprintf(
                "%s%s[%d] %s (parent: %d, children: %d)\n",
                $indent,
                $arrow,
                $node['id'],
                $node['name'],
                $node['id_parent'] ?? 0,
                count($node['children'] ?? [])
            );

            if (!empty($node['children'])) {
                displayTree($node['children'], $depth + 1);
            }
        }
    }

    displayTree($tree);

    echo "\n";

    // PROBLEM IDENTIFICATION
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "   🔍 PROBLEM IDENTIFICATION\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    // Check if root categories are missing
    $treeIds = [];
    function collectIds(array $tree, array &$ids): void
    {
        foreach ($tree as $node) {
            $ids[] = $node['id'];
            if (!empty($node['children'])) {
                collectIds($node['children'], $ids);
            }
        }
    }
    collectIds($tree, $treeIds);

    $allIds = array_column($categories, 'id');
    $missingIds = array_diff($allIds, $treeIds);

    if (!empty($missingIds)) {
        echo "⚠️  PROBLEM: Some categories are MISSING from tree!\n\n";

        foreach ($missingIds as $missingId) {
            $missingCat = array_values(array_filter($categories, fn($c) => $c['id'] == $missingId))[0] ?? null;

            if ($missingCat) {
                echo sprintf(
                    "  ❌ Missing: [%d] '%s' (parent: %d, depth: %d)\n",
                    $missingCat['id'],
                    $missingCat['name'],
                    $missingCat['id_parent'],
                    $missingCat['level_depth'] ?? 0
                );
            }
        }

        echo "\n";
        echo "💡 LIKELY CAUSE: buildCategoryTree() filters out root categories!\n";
        echo "💡 FIX NEEDED: Include categories with id_parent = 0, 1, 2 in tree\n\n";

    } else {
        echo "✅ All categories are present in tree\n\n";
    }

    // Check for "Baza" and "Wszystko"
    $baza = array_values(array_filter($categories, fn($c) => stripos($c['name'], 'Baza') !== false))[0] ?? null;
    $wszystko = array_values(array_filter($categories, fn($c) => stripos($c['name'], 'Wszystko') !== false))[0] ?? null;

    if ($baza) {
        echo "🔍 Found 'Baza': ID {$baza['id']}, parent {$baza['id_parent']}\n";
    } else {
        echo "❌ 'Baza' category NOT FOUND\n";
    }

    if ($wszystko) {
        echo "🔍 Found 'Wszystko': ID {$wszystko['id']}, parent {$wszystko['id_parent']}\n";
    } else {
        echo "❌ 'Wszystko' category NOT FOUND\n";
    }

    echo "\n";

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "   ✅ DIAGNOSTIC COMPLETE\n";
echo "═══════════════════════════════════════════════════════════════\n\n";
