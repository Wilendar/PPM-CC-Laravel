<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== BADANIE product_shop_data DLA TEST-AUTOFIX-1762422508 ===\n\n";

// Find product
$product = DB::table('products')
    ->where('sku', 'TEST-AUTOFIX-1762422508')
    ->first();

if (!$product) {
    echo "❌ Product not found\n";
    exit;
}

echo "✅ Product ID: {$product->id}\n";
echo "SKU: {$product->sku}\n";
echo "Name: {$product->name}\n\n";

// Find product_shop_data
echo "=== DANE SHOP DLA TEGO PRODUKTU ===\n\n";

$shopData = DB::table('product_shop_data as psd')
    ->join('prestashop_shops as ps', 'psd.prestashop_shop_id', '=', 'ps.id')
    ->where('psd.product_id', $product->id)
    ->select('psd.*', 'ps.name as shop_name')
    ->get();

if ($shopData->isEmpty()) {
    echo "❌ No product_shop_data found for product_id={$product->id}\n";
} else {
    foreach ($shopData as $data) {
        echo "Shop: {$data->shop_name} (ID: {$data->prestashop_shop_id})\n";
        echo "  Sync Status: " . ($data->sync_status ?? 'NULL') . "\n";
        echo "  External Product ID: " . ($data->external_product_id ?? 'NULL') . "\n";
        echo "  Last Synced: " . ($data->last_synced_at ?? 'NULL') . "\n";
        echo "  Sync Error: " . ($data->sync_error_message ?? 'NULL') . "\n";

        if ($data->sync_status === 'pending') {
            echo "  ✅ ✅ ✅ FOUND PENDING STATUS! ✅ ✅ ✅\n";
        }

        echo "\n";
    }
}

// Check if there are ANY pending statuses in product_shop_data
echo "\n=== WSZYSTKIE PENDING STATUSY W product_shop_data ===\n\n";

$allPending = DB::table('product_shop_data as psd')
    ->join('products as p', 'psd.product_id', '=', 'p.id')
    ->join('prestashop_shops as ps', 'psd.prestashop_shop_id', '=', 'ps.id')
    ->where('psd.sync_status', 'pending')
    ->select('psd.*', 'p.sku as product_sku', 'p.name as product_name', 'ps.name as shop_name')
    ->get();

echo "Total pending: " . $allPending->count() . "\n\n";

foreach ($allPending as $pending) {
    echo "Product: {$pending->product_sku}\n";
    echo "  Name: {$pending->product_name}\n";
    echo "  Shop: {$pending->shop_name}\n";
    echo "  Updated: {$pending->updated_at}\n";
    echo "\n";
}

echo "\n";
