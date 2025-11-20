<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DIAGNOZA: Sklepy dla produktu 11018 ===\n\n";

// A. Zbadaj sklepy
$shops = DB::table('prestashop_shops')
    ->whereIn('id', function($query) {
        $query->select('shop_id')
              ->from('product_shop_data')
              ->where('product_id', 11018);
    })
    ->get();

echo "Sklepy powiązane z produktem 11018:\n";
foreach ($shops as $shop) {
    echo "\n";
    echo "ID: {$shop->id}\n";
    echo "Name: {$shop->name}\n";
    echo "URL: {$shop->url}\n";
    echo "API Key: " . substr($shop->api_key, 0, 10) . "...\n";
    echo "Version: {$shop->prestashop_version}\n";
    echo "Connection Status: {$shop->connection_status}\n";
    echo "Is Active: " . ($shop->is_active ? 'YES' : 'NO') . "\n";
    echo "---\n";
}

// B. Zbadaj ProductShopData
echo "\n=== ProductShopData dla produktu 11018 ===\n";
$shopData = DB::table('product_shop_data')
    ->where('product_id', 11018)
    ->get();

foreach ($shopData as $data) {
    echo "\n";
    echo "Shop ID: {$data->shop_id}\n";
    echo "PrestaShop Product ID: {$data->prestashop_product_id}\n";
    echo "Sync Status: {$data->sync_status}\n";
    echo "Last Pulled At: {$data->last_pulled_at}\n";
    echo "Updated At: {$data->updated_at}\n";
    echo "---\n";
}

// C. Sprawdź czy któryś sklep to dev.mpptrade.pl
echo "\n=== Sprawdzenie czy jest sklep dev.mpptrade.pl ===\n";
$devShop = DB::table('prestashop_shops')
    ->where('url', 'LIKE', '%dev.mpptrade.pl%')
    ->first();

if ($devShop) {
    echo "⚠️ ZNALEZIONO SKLEP DEV:\n";
    echo "ID: {$devShop->id}\n";
    echo "Name: {$devShop->name}\n";
    echo "URL: {$devShop->url}\n";
    echo "Is Active: " . ($devShop->is_active ? 'YES' : 'NO') . "\n";
} else {
    echo "✅ Brak sklepu dev.mpptrade.pl w bazie\n";
}

echo "\n=== KONIEC DIAGNOZY ===\n";
