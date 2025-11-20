<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== SZUKAM TEST-AUTOFIX-1762422508 W product_shop_data ===\n\n";

// Find product
$product = DB::table('products')
    ->where('sku', 'TEST-AUTOFIX-1762422508')
    ->first();

if (!$product) {
    echo "❌ Product not found\n";
    exit;
}

echo "✅ Product ID: {$product->id}\n\n";

// Find product_shop_data records
$shopData = DB::table('product_shop_data as psd')
    ->leftJoin('prestashop_shops as ps', 'psd.shop_id', '=', 'ps.id')
    ->where('psd.product_id', $product->id)
    ->select('psd.*', 'ps.name as shop_name')
    ->get();

if ($shopData->isEmpty()) {
    echo "❌ No product_shop_data found\n";
} else {
    foreach ($shopData as $data) {
        echo "=== SHOP: " . ($data->shop_name ?? 'UNKNOWN') . " (ID: {$data->shop_id}) ===\n";
        echo "Sync Status: {$data->sync_status}\n";
        echo "PrestaShop Product ID: " . ($data->prestashop_product_id ?? 'NULL') . "\n";
        echo "Last Sync: " . ($data->last_sync_at ?? 'NULL') . "\n";
        echo "Error: " . ($data->error_message ?? 'NULL') . "\n";
        echo "Updated: {$data->updated_at}\n";

        if ($data->sync_status === 'pending') {
            echo "\n✅ ✅ ✅ FOUND PENDING STATUS! ✅ ✅ ✅\n";
        }

        echo "\n";
    }
}

// Count all pending in product_shop_data
$totalPending = DB::table('product_shop_data')
    ->where('sync_status', 'pending')
    ->count();

echo "\n=== TOTAL PENDING W product_shop_data ===\n";
echo "Total: $totalPending records\n\n";

if ($totalPending > 0) {
    echo "❓ PROBLEM: Są pending statusy, ale NIE MA jobów w sync_jobs!\n";
    echo "❓ CZY: System nie tworzy jobów automatycznie?\n";
    echo "❓ CZY: Trzeba ręcznie trigger sync?\n";
}

echo "\n";
