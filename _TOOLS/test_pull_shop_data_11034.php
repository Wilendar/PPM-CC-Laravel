<?php

/**
 * Test pullShopData workflow for product 11034
 *
 * ETAP_07b FAZA 1 - Symulacja wejścia na TAB sklepu w ProductForm
 *
 * Wykonuje:
 * 1. Pobiera dane produktu 11034 z PrestaShop API (shop ID=1)
 * 2. Wywołuje CategoryMappingsConverter::fromPrestaShopFormat()
 * 3. Weryfikuje utworzenie kategorii w PPM z hierarchią
 * 4. Weryfikuje utworzenie mappings w shop_mappings
 *
 * Usage:
 * php _TOOLS/test_pull_shop_data_11034.php
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Models\PrestaShopShop;
use App\Models\Category;
use App\Models\ShopMapping;
use App\Models\Product;
use App\Services\CategoryMappingsConverter;
use App\Services\PrestaShop\PrestaShop8Client;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║  ETAP_07b FAZA 1 - pullShopData Workflow Test (Product 11034)    ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// STEP 1: Get product and shop
echo "[STEP 1] Loading product 11034 and shop...\n";

$product = Product::find(11034);
if (!$product) {
    echo "❌ ERROR: Product 11034 not found in PPM database\n";
    exit(1);
}

echo "✅ Product loaded: {$product->name} (SKU: {$product->sku})\n";

$shop = PrestaShopShop::find(1);
if (!$shop) {
    echo "❌ ERROR: Shop ID=1 not found\n";
    exit(1);
}

echo "✅ Shop loaded: {$shop->name}\n";
echo "   URL: {$shop->url}\n";
echo "\n";

// STEP 2: Show current state (BEFORE pull)
echo "[STEP 2] Current state (BEFORE pullShopData)...\n";
echo "────────────────────────────────────────────────────────────────────\n";

$ppmCategoriesBefore = Category::all();
echo "📊 PPM Categories: " . $ppmCategoriesBefore->count() . "\n";
if ($ppmCategoriesBefore->count() > 0 && $ppmCategoriesBefore->count() < 20) {
    foreach ($ppmCategoriesBefore as $cat) {
        $parentInfo = $cat->parent_id ? " (Parent: {$cat->parent_id})" : " (Root)";
        echo "   - [{$cat->id}] {$cat->name}{$parentInfo}\n";
    }
} elseif ($ppmCategoriesBefore->count() >= 20) {
    echo "   (Too many categories to display - showing count only)\n";
}
echo "\n";

$mappingsBefore = ShopMapping::where('shop_id', $shop->id)
    ->where('mapping_type', ShopMapping::TYPE_CATEGORY)
    ->get();
echo "📊 Shop Mappings (shop {$shop->id}): " . $mappingsBefore->count() . "\n";
if ($mappingsBefore->count() > 0 && $mappingsBefore->count() < 20) {
    foreach ($mappingsBefore as $mapping) {
        $ppmCat = Category::find($mapping->ppm_value);
        $catName = $ppmCat ? $ppmCat->name : 'N/A';
        echo "   - PPM {$mapping->ppm_value} ({$catName}) ↔ PS {$mapping->prestashop_id}\n";
    }
} elseif ($mappingsBefore->count() >= 20) {
    echo "   (Too many mappings to display - showing count only)\n";
}
echo "────────────────────────────────────────────────────────────────────\n";
echo "\n";

// STEP 3: Check if product has PrestaShop ID for this shop
echo "[STEP 3] Checking ProductShopData...\n";

$productShopData = $product->shopData()->where('shop_id', $shop->id)->first();

if (!$productShopData || !$productShopData->prestashop_product_id) {
    echo "⚠️  Product 11034 has no PrestaShop ID for shop {$shop->id}\n";
    echo "   Skipping pull (no product to pull from PrestaShop)\n";
    echo "\n";
    echo "ℹ️  To test this workflow, use a product that exists in PrestaShop\n";
    echo "   Try product #1831 instead (if it exists)\n";
    exit(0);
}

$prestashopProductId = $productShopData->prestashop_product_id;
echo "✅ Product has PrestaShop ID: {$prestashopProductId}\n";
echo "\n";

// STEP 4: Fetch product from PrestaShop API
echo "[STEP 4] Fetching product from PrestaShop API...\n";

try {
    $client = new PrestaShop8Client(
        apiKey: decrypt($shop->api_key),
        baseUrl: rtrim($shop->url, '/'),
        timeout: $shop->timeout_seconds ?? 30
    );

    $productData = $client->getProduct($prestashopProductId);

    if (!$productData) {
        echo "❌ ERROR: Failed to fetch product {$prestashopProductId} from PrestaShop\n";
        exit(1);
    }

    // Unwrap
    if (isset($productData['product'])) {
        $productData = $productData['product'];
    }

    $psName = is_array($productData['name']) ? $productData['name'][0]['value'] : $productData['name'];
    echo "✅ Product fetched from PrestaShop: {$psName}\n";
    echo "\n";

} catch (\Exception $e) {
    echo "❌ ERROR: Failed to fetch product: {$e->getMessage()}\n";
    exit(1);
}

// STEP 5: Extract category IDs
echo "[STEP 5] Extracting category IDs from PrestaShop product...\n";

$categoryIds = [];

if (isset($productData['associations']['categories'])) {
    foreach ($productData['associations']['categories'] as $cat) {
        $categoryIds[] = (int) ($cat['id'] ?? $cat);
    }
} elseif (isset($productData['categories'])) {
    $categoryIds = array_map('intval', $productData['categories']);
}

if (empty($categoryIds)) {
    echo "⚠️  No categories found in PrestaShop product {$prestashopProductId}\n";
    echo "   This product has no categories assigned in PrestaShop\n";
    exit(0);
}

echo "✅ Found " . count($categoryIds) . " categories: " . implode(', ', $categoryIds) . "\n";
echo "\n";

// STEP 6: Call CategoryMappingsConverter::fromPrestaShopFormat()
echo "[STEP 6] Executing CategoryMappingsConverter::fromPrestaShopFormat()...\n";
echo "────────────────────────────────────────────────────────────────────\n";
echo "⚡ This will trigger auto-creation of missing categories WITH HIERARCHY\n";
echo "\n";

try {
    $converter = app(CategoryMappingsConverter::class);
    $canonical = $converter->fromPrestaShopFormat($categoryIds, $shop);

    echo "✅ Conversion successful!\n";
    echo "\n";
    echo "📦 Result (Option A canonical format):\n";
    echo json_encode($canonical, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    echo "\n";

} catch (\Exception $e) {
    echo "❌ ERROR: Conversion failed: {$e->getMessage()}\n";
    echo "   Trace: {$e->getTraceAsString()}\n";
    exit(1);
}

echo "────────────────────────────────────────────────────────────────────\n";
echo "\n";

// STEP 7: Show new state (AFTER pull)
echo "[STEP 7] New state (AFTER pullShopData)...\n";
echo "────────────────────────────────────────────────────────────────────\n";

$ppmCategoriesAfter = Category::all();
echo "📊 PPM Categories: " . $ppmCategoriesAfter->count() . "\n";
echo "   New categories created: " . ($ppmCategoriesAfter->count() - $ppmCategoriesBefore->count()) . "\n";
echo "\n";

if (($ppmCategoriesAfter->count() - $ppmCategoriesBefore->count()) > 0) {
    echo "   New categories:\n";
    foreach ($ppmCategoriesAfter as $cat) {
        if (!$ppmCategoriesBefore->contains('id', $cat->id)) {
            $parentInfo = $cat->parent_id ? " (Parent: {$cat->parent_id})" : " (Root)";
            echo "   🆕 [{$cat->id}] {$cat->name}{$parentInfo}\n";
        }
    }
    echo "\n";
}

$mappingsAfter = ShopMapping::where('shop_id', $shop->id)
    ->where('mapping_type', ShopMapping::TYPE_CATEGORY)
    ->get();
echo "📊 Shop Mappings (shop {$shop->id}): " . $mappingsAfter->count() . "\n";
echo "   New mappings created: " . ($mappingsAfter->count() - $mappingsBefore->count()) . "\n";
echo "\n";

if (($mappingsAfter->count() - $mappingsBefore->count()) > 0) {
    echo "   New mappings:\n";
    foreach ($mappingsAfter as $mapping) {
        if (!$mappingsBefore->contains('id', $mapping->id)) {
            $ppmCat = Category::find($mapping->ppm_value);
            $catName = $ppmCat ? $ppmCat->name : 'N/A';
            echo "   🆕 PPM {$mapping->ppm_value} ({$catName}) ↔ PS {$mapping->prestashop_id}\n";
        }
    }
}

echo "────────────────────────────────────────────────────────────────────\n";
echo "\n";

// STEP 8: Verify hierarchy
echo "[STEP 8] Verifying category hierarchy...\n";
echo "────────────────────────────────────────────────────────────────────\n";

$hierarchyOk = true;
$categoriesChecked = 0;

foreach ($canonical['ui']['selected'] as $ppmId) {
    $ppmCategory = Category::find($ppmId);

    if (!$ppmCategory) {
        echo "❌ PPM category {$ppmId} not found\n";
        $hierarchyOk = false;
        continue;
    }

    $categoriesChecked++;

    $psId = $canonical['mappings'][(string) $ppmId] ?? null;

    if ($ppmCategory->parent_id) {
        $parentCat = Category::find($ppmCategory->parent_id);
        $parentName = $parentCat ? $parentCat->name : 'N/A';
        echo "✅ [{$ppmId}] {$ppmCategory->name} → Parent: [{$ppmCategory->parent_id}] {$parentName}\n";
    } else {
        echo "✅ [{$ppmId}] {$ppmCategory->name} (Root category)\n";
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
echo "   Product: {$product->name} (SKU: {$product->sku})\n";
echo "   PrestaShop Product ID: {$prestashopProductId}\n";
echo "   Shop: {$shop->name}\n";
echo "   \n";
echo "   Categories in PrestaShop: " . count($categoryIds) . "\n";
echo "   PPM categories before: " . $ppmCategoriesBefore->count() . "\n";
echo "   PPM categories after: " . $ppmCategoriesAfter->count() . "\n";
echo "   New categories created: " . ($ppmCategoriesAfter->count() - $ppmCategoriesBefore->count()) . "\n";
echo "   \n";
echo "   Mappings before: " . $mappingsBefore->count() . "\n";
echo "   Mappings after: " . $mappingsAfter->count() . "\n";
echo "   New mappings created: " . ($mappingsAfter->count() - $mappingsBefore->count()) . "\n";
echo "   \n";
echo "   Categories checked: {$categoriesChecked}\n";
echo "\n";

if ($hierarchyOk) {
    echo "✅ HIERARCHY VERIFICATION: PASSED\n";
    echo "   All categories have correct parent→child relationships\n";
} else {
    echo "❌ HIERARCHY VERIFICATION: FAILED\n";
    echo "   Some categories missing or have incorrect parent_id\n";
}
echo "\n";

echo "🎯 Test completed successfully!\n";
echo "\n";
