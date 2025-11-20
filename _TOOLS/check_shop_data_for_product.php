<?php

/**
 * Check Shop Data for Product
 *
 * Diagnostic: Check if shop data is actually in database or just UI fallback
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\ProductShopData;
use Illuminate\Support\Facades\DB;

$sku = $argv[1] ?? 'TEST-AUTOFIX-1762422647';

echo "=== SHOP DATA CHECK ===\n\n";
echo "SKU: {$sku}\n\n";

try {
    $product = Product::where('sku', $sku)->first();

    if (!$product) {
        echo "❌ Product not found!\n";
        exit(1);
    }

    echo "Product ID: {$product->id}\n";
    echo "Product Name (default): {$product->name}\n\n";

    // Check product_shop_data table
    echo "=== ProductShopData Table ===\n\n";

    $shopData = ProductShopData::where('product_id', $product->id)->get();

    if ($shopData->isEmpty()) {
        echo "❌ NO shop-specific data in database!\n";
        echo "   This means UI is showing default data as fallback.\n\n";
    } else {
        echo "Found " . $shopData->count() . " shop-specific record(s):\n\n";

        foreach ($shopData as $data) {
            echo "Shop ID: {$data->shop_id}\n";
            echo "  name: " . ($data->name ?? 'NULL (inherits from default)') . "\n";
            echo "  slug: " . ($data->slug ?? 'NULL') . "\n";
            echo "  short_description: " . (strlen($data->short_description ?? '') > 0 ? '[SET]' : 'NULL') . "\n";
            echo "  long_description: " . (strlen($data->long_description ?? '') > 0 ? '[SET]' : 'NULL') . "\n";
            echo "  sync_status: " . ($data->sync_status ?? 'NULL') . "\n";
            echo "  is_published: " . ($data->is_published ? 'true' : 'false') . "\n";
            echo "  last_sync_at: " . ($data->last_sync_at ?? 'NULL') . "\n";
            echo "  prestashop_product_id: " . ($data->prestashop_product_id ?? 'NULL') . "\n\n";
        }
    }

    // Check raw database
    echo "=== Raw Database Query ===\n\n";

    $raw = DB::table('product_shop_data')
        ->where('product_id', $product->id)
        ->get();

    foreach ($raw as $row) {
        echo "Shop ID {$row->shop_id}:\n";
        echo "  name (raw): " . var_export($row->name, true) . "\n";
        echo "  name (is_null): " . (is_null($row->name) ? 'YES' : 'NO') . "\n";
        echo "  name (empty string): " . ($row->name === '' ? 'YES' : 'NO') . "\n\n";
    }

} catch (\Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
    exit(1);
}
