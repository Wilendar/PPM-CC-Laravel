<?php

// Quick script to check product mapping
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ShopMapping;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

echo "=== PRODUCT 4017 MAPPING CHECK ===\n\n";

// 1. Check ShopMapping
$mapping = ShopMapping::where('shop_id', 5)
    ->where('prestashop_id', 4017)
    ->where('mapping_type', 'product')
    ->first();

if ($mapping) {
    echo "✅ Mapping EXISTS\n";
    echo "  - PPM Product ID: {$mapping->ppm_id}\n";
    echo "  - PrestaShop ID: {$mapping->prestashop_id}\n";
    echo "  - Shop ID: {$mapping->shop_id}\n";
    echo "  - Is Active: " . ($mapping->is_active ? 'YES' : 'NO') . "\n\n";

    // 2. Check product categories in PPM
    $product = Product::find($mapping->ppm_id);
    if ($product) {
        echo "📦 Product: {$product->name} (SKU: {$product->sku})\n\n";

        // Default categories (shop_id = NULL)
        $defaultCategories = DB::table('product_categories')
            ->where('product_id', $mapping->ppm_id)
            ->whereNull('shop_id')
            ->pluck('category_id')
            ->toArray();

        echo "🔷 DEFAULT Categories (shop_id=NULL): " . json_encode($defaultCategories) . "\n";

        // Shop-specific categories (shop_id = 5)
        $shopCategories = DB::table('product_categories')
            ->where('product_id', $mapping->ppm_id)
            ->where('shop_id', 5)
            ->pluck('category_id')
            ->toArray();

        echo "🔶 SHOP Categories (shop_id=5): " . json_encode($shopCategories) . "\n\n";

        // 3. Check PrestaShop categories (from last import)
        echo "🛒 PrestaShop Product 4017 categories:\n";
        echo "  (need API call to fetch - check logs for 'prestashop_categories')\n";
    }
} else {
    echo "❌ NO MAPPING FOUND - This is FIRST IMPORT!\n";
    echo "  Product 4017 has never been imported before.\n";
    echo "  Conflict detection ONLY works on RE-IMPORT.\n";
}

echo "\n=== END ===\n";
