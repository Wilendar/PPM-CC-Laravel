<?php
/**
 * Check if shop categories were saved to database
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "   DATABASE CHECK: Shop Categories for Product 11034\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Check product_categories for product 11034, shop_id=1
$shopCategories = DB::table('product_categories')
    ->where('product_id', 11034)
    ->where('shop_id', 1)
    ->get(['id', 'category_id', 'shop_id', 'is_primary', 'sort_order', 'created_at']);

if ($shopCategories->isEmpty()) {
    echo "❌ NO SHOP CATEGORIES FOUND for product 11034, shop_id=1!\n\n";
    echo "🔍 Checking if product has ANY categories...\n\n";

    $allCategories = DB::table('product_categories')
        ->where('product_id', 11034)
        ->get(['id', 'category_id', 'shop_id', 'is_primary', 'sort_order']);

    if ($allCategories->isEmpty()) {
        echo "❌ Product 11034 has NO categories at all!\n\n";
    } else {
        echo "✅ Product 11034 has " . $allCategories->count() . " categories:\n\n";
        foreach ($allCategories as $cat) {
            $shopDisplay = $cat->shop_id === null ? 'DEFAULT' : "Shop {$cat->shop_id}";
            $primary = $cat->is_primary ? '⭐' : '  ';
            echo sprintf("%s [%d] Category %d (%s)\n", $primary, $cat->id, $cat->category_id, $shopDisplay);
        }
    }
} else {
    echo "✅ Found " . $shopCategories->count() . " shop categories for shop_id=1:\n\n";

    foreach ($shopCategories as $cat) {
        $primary = $cat->is_primary ? '⭐' : '  ';
        echo sprintf(
            "%s [%d] Category %d (Shop %d, Order: %d) - Created: %s\n",
            $primary,
            $cat->id,
            $cat->category_id,
            $cat->shop_id,
            $cat->sort_order,
            $cat->created_at
        );
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n\n";
