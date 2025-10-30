<?php
/**
 * Diagnostic Tool: Product 9756 Categories Investigation
 *
 * Purpose: Verify PrestaShop API response structure and category mapping logic
 * Issue: preview_id 120 failed to save shop_mappings for categories 800, 801, 2351
 *
 * Investigation:
 * 1. Fetch product 9756 from PrestaShop API
 * 2. Display RAW associations.categories structure
 * 3. Check which categories have shop_mappings
 * 4. Simulate syncProductCategories() logic step-by-step
 *
 * Usage: php _TOOLS/diagnose_product_9756_categories.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Models\ShopMapping;
use App\Services\PrestaShop\PrestaShopClientFactory;
use Illuminate\Support\Facades\Log;

echo "=== DIAGNOSTIC: Product 9756 Categories Investigation ===\n\n";

// 1. Get shop
$shop = PrestaShopShop::find(1); // B2B Test DEV
if (!$shop) {
    die("ERROR: Shop ID 1 not found!\n");
}

echo "✅ Shop: {$shop->name} (ID: {$shop->id})\n";
echo "   URL: {$shop->url}\n\n";

// 2. Fetch product from PrestaShop API
echo "--- STEP 1: Fetch Product from PrestaShop API ---\n";
$client = PrestaShopClientFactory::create($shop);

try {
    $prestashopData = $client->getProduct(9756);

    // Unwrap nested 'product' key
    if (isset($prestashopData['product']) && is_array($prestashopData['product'])) {
        $prestashopData = $prestashopData['product'];
    }

    echo "✅ Product fetched successfully\n";
    echo "   PrestaShop ID: " . data_get($prestashopData, 'id') . "\n";
    echo "   Reference (SKU): " . data_get($prestashopData, 'reference') . "\n";
    echo "   Default Category: " . data_get($prestashopData, 'id_category_default') . "\n\n";

} catch (\Exception $e) {
    die("ERROR: Failed to fetch product - {$e->getMessage()}\n");
}

// 3. Display RAW associations.categories structure
echo "--- STEP 2: RAW associations.categories Structure ---\n";
$prestashopCategories = data_get($prestashopData, 'associations.categories', []);

if (empty($prestashopCategories)) {
    echo "⚠️  EMPTY! No associations.categories found!\n";
    echo "   Full associations structure:\n";
    print_r(data_get($prestashopData, 'associations'));
} else {
    echo "✅ Found " . count($prestashopCategories) . " categories\n";
    echo "   RAW structure:\n";
    print_r($prestashopCategories);
}

echo "\n";

// 4. Check which categories have shop_mappings
echo "--- STEP 3: Shop Mappings Status ---\n";

$categoryIds = array_column($prestashopCategories, 'id');
echo "PrestaShop Category IDs: " . implode(', ', $categoryIds) . "\n\n";

foreach ($categoryIds as $psId) {
    $mapping = ShopMapping::where('shop_id', $shop->id)
        ->where('mapping_type', ShopMapping::TYPE_CATEGORY)
        ->where('prestashop_id', $psId)
        ->first();

    if ($mapping) {
        echo "✅ Category {$psId}: MAPPED → PPM ID {$mapping->ppm_value}\n";
    } else {
        echo "❌ Category {$psId}: NO MAPPING\n";

        // Check if category exists in categories table
        $category = \App\Models\Category::find($psId);
        if ($category) {
            echo "   ℹ️  Category EXISTS in categories table (id: {$category->id}, name: {$category->name})\n";
            echo "   ⚠️  BUT missing shop_mappings entry!\n";
        } else {
            echo "   ❌ Category does NOT exist in categories table\n";
        }
    }
}

echo "\n";

// 5. Simulate syncProductCategories() logic
echo "--- STEP 4: Simulate syncProductCategories() Logic ---\n";

$product = Product::where('sku', 'PPM-TEST')->first();
if (!$product) {
    die("ERROR: Product PPM-TEST not found!\n");
}

echo "Product: {$product->name} (ID: {$product->id})\n";

// Check existing default categories
$existingDefaultCategories = $product->categories()->get();
echo "Existing DEFAULT categories (shop_id=NULL): " . $existingDefaultCategories->pluck('id')->implode(', ') . "\n";

// Simulate mapping loop
$ppmCategoryIds = [];
$defaultCategoryId = (int) data_get($prestashopData, 'id_category_default', 0);

echo "\n--- Simulating Category Mapping Loop ---\n";

foreach ($prestashopCategories as $index => $psCategory) {
    $prestashopCategoryId = (int) data_get($psCategory, 'id', 0);

    echo "  Category {$prestashopCategoryId}:\n";

    // Skip root
    if ($prestashopCategoryId <= 2) {
        echo "    → SKIPPED (root category)\n";
        continue;
    }

    // Check mapping
    $mapping = ShopMapping::where('shop_id', $shop->id)
        ->where('mapping_type', ShopMapping::TYPE_CATEGORY)
        ->where('prestashop_id', $prestashopCategoryId)
        ->where('is_active', true)
        ->first();

    if ($mapping) {
        echo "    → MAPPED to PPM ID {$mapping->ppm_value}\n";
        $ppmCategoryIds[$mapping->ppm_value] = [
            'is_primary' => ($prestashopCategoryId === $defaultCategoryId),
            'sort_order' => $index,
        ];
    } else {
        echo "    → NO MAPPING - would attempt auto-import\n";

        // Check if category exists
        $category = \App\Models\Category::find($prestashopCategoryId);
        if ($category) {
            echo "       ⚠️  Category EXISTS but no mapping → auto-import would FAIL (duplicate)\n";
        } else {
            echo "       ℹ️  Category NOT exists → auto-import would succeed\n";
        }
    }
}

echo "\n";

// 6. Display result
echo "--- STEP 5: Result Analysis ---\n";
echo "Mapped PPM Category IDs: " . implode(', ', array_keys($ppmCategoryIds)) . "\n";

if (empty($ppmCategoryIds)) {
    echo "❌ EMPTY! No categories were mapped!\n";
    echo "   This is why syncProductCategories() logic fails!\n";
} else {
    $defaultCategoryIds = $existingDefaultCategories->pluck('id')->sort()->values()->toArray();
    $newCategoryIds = collect(array_keys($ppmCategoryIds))->sort()->values()->toArray();

    echo "\nComparison:\n";
    echo "  Default categories: [" . implode(', ', $defaultCategoryIds) . "]\n";
    echo "  New categories:     [" . implode(', ', $newCategoryIds) . "]\n";

    if ($defaultCategoryIds !== $newCategoryIds) {
        echo "  → DIFFERENT! Should save per-shop categories\n";
    } else {
        echo "  → SAME! Would fallback to default categories\n";
    }
}

echo "\n=== END OF DIAGNOSTIC ===\n";
