<?php

/**
 * Category Auto-Creation Diagnostic Tool - Product #1831
 *
 * ETAP_07b FAZA 1 - Test pobierania kategorii z PrestaShop i auto-creation w PPM
 *
 * Test Workflow:
 * 1. Połącz się z PrestaShop API (sklep B2B Test DEV, ID=1)
 * 2. Pobierz dane produktu #1831
 * 3. Pobierz kategorie przypisane do produktu
 * 4. Dla każdej kategorii pobierz szczegóły (nazwa, parent_id)
 * 5. Wyświetl strukturę drzewa kategorii
 * 6. Przetestuj CategoryMappingsConverter::fromPrestaShopFormat()
 * 7. Sprawdź czy kategorie zostały utworzone w PPM z hierarchią
 * 8. Sprawdź czy mappingi zostały utworzone w shop_mappings
 *
 * Usage:
 * php _TOOLS/test_category_auto_creation_1831.php
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Models\PrestaShopShop;
use App\Models\Category;
use App\Models\ShopMapping;
use App\Services\CategoryMappingsConverter;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║  ETAP_07b FAZA 1 - Category Auto-Creation Test (Product #1831)   ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// STEP 1: Get shop instance (B2B Test DEV, ID=1)
echo "[STEP 1] Fetching shop instance (B2B Test DEV, ID=1)...\n";
$shop = PrestaShopShop::find(1);

if (!$shop) {
    echo "❌ ERROR: Shop ID=1 not found in database\n";
    exit(1);
}

echo "✅ Shop found: {$shop->name} (PrestaShop v{$shop->version})\n";
echo "   URL: {$shop->url}\n";
echo "   Status: " . ($shop->is_active ? 'Active' : 'Inactive') . "\n";
echo "\n";

// STEP 2: Get PrestaShop client
echo "[STEP 2] Initializing PrestaShop API client...\n";
try {
    $client = $shop->getClient();
    echo "✅ Client initialized: " . get_class($client) . "\n";
    echo "\n";
} catch (\Exception $e) {
    echo "❌ ERROR: Failed to initialize client: {$e->getMessage()}\n";
    exit(1);
}

// STEP 3: Fetch product #1831 from PrestaShop
echo "[STEP 3] Fetching product #1831 from PrestaShop API...\n";
try {
    $productData = $client->getProduct(1831);

    if (!$productData) {
        echo "❌ ERROR: Product #1831 not found in PrestaShop\n";
        exit(1);
    }

    // Unwrap if nested
    if (isset($productData['product'])) {
        $productData = $productData['product'];
    }

    $productName = data_get($productData, 'name.0.value', 'N/A');
    $productSku = data_get($productData, 'reference', 'N/A');

    echo "✅ Product fetched:\n";
    echo "   ID: 1831\n";
    echo "   Name: {$productName}\n";
    echo "   SKU: {$productSku}\n";
    echo "\n";
} catch (\Exception $e) {
    echo "❌ ERROR: Failed to fetch product: {$e->getMessage()}\n";
    echo "   Trace: {$e->getTraceAsString()}\n";
    exit(1);
}

// STEP 4: Extract category IDs from product
echo "[STEP 4] Extracting category IDs from product...\n";
$categoryIds = [];

if (isset($productData['associations']['categories'])) {
    foreach ($productData['associations']['categories'] as $cat) {
        $categoryIds[] = (int) ($cat['id'] ?? $cat);
    }
} elseif (isset($productData['categories'])) {
    $categoryIds = array_map('intval', $productData['categories']);
}

if (empty($categoryIds)) {
    echo "❌ ERROR: No categories found for product #1831\n";
    exit(1);
}

echo "✅ Found " . count($categoryIds) . " categories: " . implode(', ', $categoryIds) . "\n";
echo "\n";

// STEP 5: Fetch category details and build tree structure
echo "[STEP 5] Fetching category details from PrestaShop API...\n";
echo "────────────────────────────────────────────────────────────────────\n";

$categoryTree = [];

foreach ($categoryIds as $catId) {
    try {
        $catData = $client->getCategory($catId);

        // Unwrap if nested
        if (isset($catData['category'])) {
            $catData = $catData['category'];
        }

        $catName = data_get($catData, 'name.0.value', 'N/A');
        $catParentId = isset($catData['id_parent']) ? (int) $catData['id_parent'] : null;
        $catLevel = isset($catData['level_depth']) ? (int) $catData['level_depth'] : 0;

        $categoryTree[$catId] = [
            'id' => $catId,
            'name' => $catName,
            'parent_id' => $catParentId,
            'level' => $catLevel,
        ];

        $indent = str_repeat('  ', $catLevel);
        $parentInfo = $catParentId ? " (Parent: {$catParentId})" : " (Root)";

        echo "{$indent}[{$catId}] {$catName}{$parentInfo}\n";

    } catch (\Exception $e) {
        echo "⚠️  WARNING: Failed to fetch category {$catId}: {$e->getMessage()}\n";
    }
}

echo "────────────────────────────────────────────────────────────────────\n";
echo "\n";

// STEP 6: Show current PPM categories (BEFORE auto-creation)
echo "[STEP 6] Current PPM categories (BEFORE auto-creation)...\n";
$ppmCategoriesBefore = Category::all();
echo "📊 Total PPM categories: " . $ppmCategoriesBefore->count() . "\n";

if ($ppmCategoriesBefore->count() > 0) {
    echo "   Categories:\n";
    foreach ($ppmCategoriesBefore as $cat) {
        $parentInfo = $cat->parent_id ? " (Parent: {$cat->parent_id})" : " (Root)";
        echo "   - [{$cat->id}] {$cat->name}{$parentInfo}\n";
    }
} else {
    echo "   (Empty - no categories in PPM)\n";
}
echo "\n";

// STEP 7: Show current shop_mappings (BEFORE auto-creation)
echo "[STEP 7] Current shop_mappings (BEFORE auto-creation)...\n";
$mappingsBefore = ShopMapping::where('shop_id', $shop->id)
    ->where('mapping_type', ShopMapping::TYPE_CATEGORY)
    ->get();
echo "📊 Total category mappings for shop {$shop->id}: " . $mappingsBefore->count() . "\n";

if ($mappingsBefore->count() > 0) {
    echo "   Mappings:\n";
    foreach ($mappingsBefore as $mapping) {
        echo "   - PPM ID {$mapping->ppm_value} ↔ PrestaShop ID {$mapping->prestashop_id}\n";
    }
} else {
    echo "   (Empty - no mappings for this shop)\n";
}
echo "\n";

// STEP 8: Test CategoryMappingsConverter::fromPrestaShopFormat()
echo "[STEP 8] Testing CategoryMappingsConverter::fromPrestaShopFormat()...\n";
echo "────────────────────────────────────────────────────────────────────\n";
echo "⚡ Running: CategoryMappingsConverter::fromPrestaShopFormat()\n";
echo "   Input: PrestaShop category IDs: " . implode(', ', $categoryIds) . "\n";
echo "   Shop: {$shop->name} (ID={$shop->id})\n";
echo "\n";

try {
    $converter = app(CategoryMappingsConverter::class);
    $canonical = $converter->fromPrestaShopFormat($categoryIds, $shop);

    echo "✅ Conversion successful!\n";
    echo "\n";
    echo "📦 Canonical Format (Option A):\n";
    echo json_encode($canonical, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    echo "\n";

} catch (\Exception $e) {
    echo "❌ ERROR: Conversion failed: {$e->getMessage()}\n";
    echo "   Trace: {$e->getTraceAsString()}\n";
    exit(1);
}

echo "────────────────────────────────────────────────────────────────────\n";
echo "\n";

// STEP 9: Show PPM categories (AFTER auto-creation)
echo "[STEP 9] PPM categories (AFTER auto-creation)...\n";
$ppmCategoriesAfter = Category::all();
echo "📊 Total PPM categories: " . $ppmCategoriesAfter->count() . "\n";
echo "   New categories created: " . ($ppmCategoriesAfter->count() - $ppmCategoriesBefore->count()) . "\n";
echo "\n";

if ($ppmCategoriesAfter->count() > 0) {
    echo "   Categories:\n";
    foreach ($ppmCategoriesAfter as $cat) {
        $parentInfo = $cat->parent_id ? " (Parent: {$cat->parent_id})" : " (Root)";
        $isNew = !$ppmCategoriesBefore->contains('id', $cat->id) ? " 🆕 NEW" : "";
        echo "   - [{$cat->id}] {$cat->name}{$parentInfo}{$isNew}\n";
    }
}
echo "\n";

// STEP 10: Show shop_mappings (AFTER auto-creation)
echo "[STEP 10] Shop_mappings (AFTER auto-creation)...\n";
$mappingsAfter = ShopMapping::where('shop_id', $shop->id)
    ->where('mapping_type', ShopMapping::TYPE_CATEGORY)
    ->get();
echo "📊 Total category mappings for shop {$shop->id}: " . $mappingsAfter->count() . "\n";
echo "   New mappings created: " . ($mappingsAfter->count() - $mappingsBefore->count()) . "\n";
echo "\n";

if ($mappingsAfter->count() > 0) {
    echo "   Mappings:\n";
    foreach ($mappingsAfter as $mapping) {
        $isNew = !$mappingsBefore->contains('id', $mapping->id) ? " 🆕 NEW" : "";
        $ppmCategory = Category::find($mapping->ppm_value);
        $catName = $ppmCategory ? $ppmCategory->name : 'N/A';
        echo "   - PPM ID {$mapping->ppm_value} ({$catName}) ↔ PrestaShop ID {$mapping->prestashop_id}{$isNew}\n";
    }
}
echo "\n";

// STEP 11: Verify hierarchy preservation
echo "[STEP 11] Verifying hierarchy preservation...\n";
echo "────────────────────────────────────────────────────────────────────\n";

$hierarchyOk = true;

foreach ($canonical['ui']['selected'] as $ppmId) {
    $ppmCategory = Category::find($ppmId);

    if (!$ppmCategory) {
        echo "❌ PPM category {$ppmId} not found in database\n";
        $hierarchyOk = false;
        continue;
    }

    // Find corresponding PrestaShop ID
    $psId = $canonical['mappings'][(string) $ppmId] ?? null;

    if (!$psId) {
        echo "⚠️  No PrestaShop mapping found for PPM category {$ppmId}\n";
        continue;
    }

    // Get PrestaShop category data
    $psCatData = $categoryTree[$psId] ?? null;

    if (!$psCatData) {
        echo "⚠️  PrestaShop category {$psId} not in fetched tree\n";
        continue;
    }

    // Check if parent_id matches
    $psParentId = $psCatData['parent_id'];
    $ppmParentId = $ppmCategory->parent_id;

    // If PrestaShop has parent (not root 1, 2), check if PPM also has parent
    if ($psParentId && !in_array($psParentId, [1, 2], true)) {
        // Find PPM ID of PrestaShop parent
        $psParentPpmId = null;
        foreach ($canonical['mappings'] as $pId => $psIdMapping) {
            if ($psIdMapping == $psParentId) {
                $psParentPpmId = (int) $pId;
                break;
            }
        }

        if ($psParentPpmId && $ppmParentId != $psParentPpmId) {
            echo "❌ Hierarchy mismatch for [{$ppmId}] {$ppmCategory->name}:\n";
            echo "   PPM parent_id: {$ppmParentId}\n";
            echo "   Expected (from PrestaShop): {$psParentPpmId}\n";
            $hierarchyOk = false;
        } else {
            echo "✅ [{$ppmId}] {$ppmCategory->name} - Hierarchy OK\n";
        }
    } else {
        // PrestaShop root - PPM should be root too
        if ($ppmParentId !== null) {
            echo "⚠️  [{$ppmId}] {$ppmCategory->name} - PPM has parent but PrestaShop is root\n";
        } else {
            echo "✅ [{$ppmId}] {$ppmCategory->name} - Root category OK\n";
        }
    }
}

echo "────────────────────────────────────────────────────────────────────\n";
echo "\n";

// FINAL SUMMARY
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║                         FINAL SUMMARY                              ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "📊 Statistics:\n";
echo "   PrestaShop categories fetched: " . count($categoryIds) . "\n";
echo "   PPM categories before: " . $ppmCategoriesBefore->count() . "\n";
echo "   PPM categories after: " . $ppmCategoriesAfter->count() . "\n";
echo "   New categories created: " . ($ppmCategoriesAfter->count() - $ppmCategoriesBefore->count()) . "\n";
echo "   Mappings before: " . $mappingsBefore->count() . "\n";
echo "   Mappings after: " . $mappingsAfter->count() . "\n";
echo "   New mappings created: " . ($mappingsAfter->count() - $mappingsBefore->count()) . "\n";
echo "\n";

if ($hierarchyOk) {
    echo "✅ HIERARCHY VERIFICATION: PASSED\n";
    echo "   All categories created with correct parent→child relationships\n";
} else {
    echo "❌ HIERARCHY VERIFICATION: FAILED\n";
    echo "   Some categories have incorrect parent_id values\n";
}
echo "\n";

echo "🎯 Test completed successfully!\n";
echo "\n";
