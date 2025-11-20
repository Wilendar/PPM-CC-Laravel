<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ShopMapping;
use App\Models\Category;
use App\Models\ProductShopData;
use Illuminate\Support\Facades\DB;

echo "=== CATEGORY MAPPINGS DIAGNOSIS ===\n\n";

// 1. Check shop_mappings table
echo "=== [1/3] SHOP_MAPPINGS TABLE (Shop 1) ===\n\n";

$mappings = ShopMapping::where('shop_id', 1)
    ->where('mapping_type', 'category')
    ->where('is_active', true)
    ->orderBy('prestashop_id')
    ->get();

echo "Total mappings: " . $mappings->count() . "\n\n";

foreach ($mappings as $mapping) {
    $ppmCat = Category::find($mapping->ppm_value);
    $ppmName = $ppmCat ? $ppmCat->name : 'NOT FOUND';

    echo "PS {$mapping->prestashop_id} ({$mapping->prestashop_value}) → PPM {$mapping->ppm_value} ({$ppmName})\n";
}

// 2. Check PrestaShop categories for product 1831
echo "\n\n=== [2/3] PRESTASHOP CATEGORIES (Product 1831) ===\n\n";

try {
    $psCategories = DB::connection('prestashop_dev')
        ->table('ps_category_product')
        ->where('id_product', 1831)
        ->get(['id_category']);

    echo "Total PrestaShop categories: " . $psCategories->count() . "\n\n";

    foreach ($psCategories as $cat) {
        $categoryData = DB::connection('prestashop_dev')
            ->table('ps_category_lang')
            ->where('id_category', $cat->id_category)
            ->where('id_lang', 1)
            ->first(['name']);

        $name = $categoryData ? $categoryData->name : 'UNKNOWN';
        echo "  PS {$cat->id_category}: {$name}\n";
    }

    // Default category
    $product = DB::connection('prestashop_dev')
        ->table('ps_product')
        ->where('id_product', 1831)
        ->first(['id_category_default']);

    $defaultName = DB::connection('prestashop_dev')
        ->table('ps_category_lang')
        ->where('id_category', $product->id_category_default)
        ->where('id_lang', 1)
        ->first(['name']);

    echo "\nDefault: PS {$product->id_category_default} (" . ($defaultName ? $defaultName->name : 'UNKNOWN') . ")\n";

} catch (\Exception $e) {
    echo "ERROR accessing PrestaShop DB: " . $e->getMessage() . "\n";
}

// 3. Check PPM product_shop_data
echo "\n\n=== [3/3] PPM PRODUCT_SHOP_DATA (Product 11034, Shop 1) ===\n\n";

$psd = ProductShopData::where('product_id', 11034)
    ->where('shop_id', 1)
    ->first();

if ($psd && $psd->category_mappings) {
    $cm = $psd->category_mappings;

    echo "Selected PPM IDs: " . json_encode($cm['ui']['selected'] ?? []) . "\n";
    echo "Primary PPM ID: " . ($cm['ui']['primary'] ?? 'NULL') . "\n";
    echo "Mappings count: " . count($cm['mappings'] ?? []) . "\n\n";

    echo "PPM Category Names:\n";
    foreach (($cm['ui']['selected'] ?? []) as $ppmId) {
        $cat = Category::find($ppmId);
        $name = $cat ? $cat->name : 'NOT FOUND';
        echo "  PPM {$ppmId}: {$name}\n";
    }

    echo "\nFull category_mappings JSON:\n";
    echo json_encode($cm, JSON_PRETTY_PRINT) . "\n";

} else {
    echo "NO DATA FOUND\n";
}

// 4. COMPARISON ANALYSIS
echo "\n\n=== [4/4] COMPARISON ANALYSIS ===\n\n";

if ($psd && isset($psCategories)) {
    echo "PrestaShop IDs: " . $psCategories->pluck('id_category')->implode(', ') . "\n";

    $mappedPpmIds = [];
    foreach ($psCategories as $psCat) {
        $mapping = ShopMapping::where('shop_id', 1)
            ->where('mapping_type', 'category')
            ->where('prestashop_id', $psCat->id_category)
            ->first();

        if ($mapping) {
            $mappedPpmIds[] = (int) $mapping->ppm_value;
        }
    }

    echo "Expected PPM IDs (based on mappings): " . implode(', ', $mappedPpmIds) . "\n";
    echo "Actual PPM IDs (in product_shop_data): " . implode(', ', $cm['ui']['selected'] ?? []) . "\n";

    $missing = array_diff($mappedPpmIds, $cm['ui']['selected'] ?? []);
    $extra = array_diff($cm['ui']['selected'] ?? [], $mappedPpmIds);

    if (count($missing) > 0) {
        echo "\n⚠️ MISSING from product_shop_data: " . implode(', ', $missing) . "\n";
    }

    if (count($extra) > 0) {
        echo "⚠️ EXTRA in product_shop_data: " . implode(', ', $extra) . "\n";
    }

    if (count($missing) == 0 && count($extra) == 0) {
        echo "\n✅ Categories MATCH!\n";
    }
}

echo "\n=== DIAGNOSIS COMPLETE ===\n";
