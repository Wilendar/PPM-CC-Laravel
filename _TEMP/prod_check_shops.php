<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== PRODUCTION DIAGNOSIS ===\n\n";

// Check if table exists
$tables = DB::select('SHOW TABLES');
$tableNames = array_map(fn($t) => array_values(get_object_vars($t))[0], $tables);
$hasShopsTable = in_array('prestashop_shops', $tableNames);

echo "Table prestashop_shops exists: " . ($hasShopsTable ? "YES" : "NO") . "\n\n";

if (!$hasShopsTable) {
    echo "ERROR: prestashop_shops table does not exist!\n";
    echo "Available tables with 'shop':\n";
    foreach ($tableNames as $name) {
        if (stripos($name, 'shop') !== false) {
            echo "  - $name\n";
        }
    }
    exit(1);
}

// Get all shops
echo "=== ALL SHOPS ===\n";
$shops = DB::table('prestashop_shops')->get();
foreach ($shops as $shop) {
    echo "\nID: {$shop->id}\n";
    echo "Name: {$shop->name}\n";
    echo "URL: {$shop->url}\n";
    echo "Version: {$shop->prestashop_version}\n";
    echo "Active: " . ($shop->is_active ? 'YES' : 'NO') . "\n";
    echo "Connection: {$shop->connection_status}\n";
    echo "API Key: " . substr($shop->api_key, 0, 15) . "...\n";
    echo "---\n";
}

// Check product_shop_data for product 11018
echo "\n=== PRODUCT 11018 SHOP DATA ===\n";
$productShopData = DB::table('product_shop_data')
    ->where('product_id', 11018)
    ->get();

if ($productShopData->isEmpty()) {
    echo "No shop data found for product 11018\n";
} else {
    foreach ($productShopData as $psd) {
        echo "\nShop ID: {$psd->shop_id}\n";
        echo "PrestaShop Product ID: {$psd->prestashop_product_id}\n";
        echo "Sync Status: {$psd->sync_status}\n";
        echo "Last Pulled: {$psd->last_pulled_at}\n";
        echo "---\n";
    }
}

// Check if dev.mpptrade.pl exists
echo "\n=== CHECK FOR dev.mpptrade.pl ===\n";
$devShop = DB::table('prestashop_shops')
    ->where('url', 'LIKE', '%dev.mpptrade.pl%')
    ->first();

if ($devShop) {
    echo "⚠️ DEV SHOP FOUND:\n";
    echo "ID: {$devShop->id}\n";
    echo "Name: {$devShop->name}\n";
    echo "URL: {$devShop->url}\n";
    echo "Active: " . ($devShop->is_active ? 'YES' : 'NO') . "\n";
} else {
    echo "✅ No dev.mpptrade.pl shop found\n";
}

echo "\n=== END DIAGNOSIS ===\n";
