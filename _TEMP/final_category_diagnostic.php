<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\PrestaShop\CategoryMapper;

echo "=== FINAL CATEGORY DIAGNOSTIC ===\n\n";

// PrestaShop categories (from direct DB query)
$psCategories = [2, 12, 23, 800, 801];

// PPM product_shop_data categories
$psd = DB::table('product_shop_data')
    ->where('product_id', 11034)
    ->where('shop_id', 1)
    ->first();

$categoryMappings = json_decode($psd->category_mappings, true);
$ppmSelected = $categoryMappings['ui']['selected'] ?? [];

echo "PRESTASHOP ACTUAL CATEGORIES (from DB):\n";
echo json_encode($psCategories) . "\n\n";

echo "PPM STORED CATEGORIES (product_shop_data):\n";
echo json_encode($ppmSelected) . "\n\n";

echo "COMMON: " . json_encode(array_intersect($psCategories, $ppmSelected)) . "\n";
echo "PPM ONLY (ghost): " . json_encode(array_diff($ppmSelected, $psCategories)) . "\n";
echo "PrestaShop ONLY (missing): " . json_encode(array_diff($psCategories, $ppmSelected)) . "\n\n";

// Check if categories exist in PPM
echo "=== PPM CATEGORY EXISTENCE CHECK ===\n\n";

foreach ($ppmSelected as $catId) {
    $cat = DB::table('categories')->where('id', $catId)->first();
    if ($cat) {
        echo "PPM {$catId}: ✅ EXISTS ({$cat->name})\n";
    } else {
        echo "PPM {$catId}: ❌ GHOST (doesn't exist in categories table)\n";
    }
}

echo "\n=== PRESTASHOP SHOP CATEGORY MAPPINGS ===\n\n";

$shop = DB::table('prestashop_shops')->where('id', 1)->first();

if (isset($shop->category_mappings)) {
    $shopMappings = json_decode($shop->category_mappings, true);
    echo "Shop has category_mappings column: YES\n";
    echo "Mappings count: " . (is_array($shopMappings) ? count($shopMappings) : 0) . "\n";

    if ($shopMappings && is_array($shopMappings)) {
        echo "\nSample mappings:\n";
        $count = 0;
        foreach ($shopMappings as $psId => $ppmId) {
            echo "  PrestaShop {$psId} -> PPM {$ppmId}\n";
            $count++;
            if ($count >= 10) {
                echo "  ... and " . (count($shopMappings) - 10) . " more\n";
                break;
            }
        }
    }
} else {
    echo "Shop has category_mappings column: NO\n";
}

echo "\n=== SOLUTION ===\n\n";

echo "PROBLEM IDENTIFIED:\n";
echo "  PPM stores: " . json_encode($ppmSelected) . "\n";
echo "  PrestaShop has: " . json_encode($psCategories) . "\n";
echo "  They don't match!\n\n";

echo "ROOT CAUSE:\n";
echo "  Auto-loading from PrestaShop is SUPPOSED to fetch [" . implode(',', $psCategories) . "]\n";
echo "  But CategoryMapper translates them to [" . implode(',', $ppmSelected) . "]\n";
echo "  Some translated IDs (36, 41, 43) are GHOST categories\n\n";

echo "NEXT STEP:\n";
echo "  1. Check CategoryMapper::mapPrestaShopToPPM() logic\n";
echo "  2. Check shop category_mappings for correct PrestaShop->PPM translation\n";
echo "  3. Fix ghost category mappings\n";

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
