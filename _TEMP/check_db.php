<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PRESTASHOP SHOPS CONFIGURATION ===" . PHP_EOL . PHP_EOL;

$shops = DB::table('prestashop_shops')
    ->whereIn('id', [1, 5])
    ->select('id', 'name', 'url', 'api_key', 'prestashop_version', 'is_active', 'connection_status')
    ->get();

foreach ($shops as $shop) {
    echo "SHOP #" . $shop->id . PHP_EOL;
    echo "  Name: " . $shop->name . PHP_EOL;
    echo "  URL: " . $shop->url . PHP_EOL;
    echo "  API Key: " . substr($shop->api_key, 0, 15) . "..." . PHP_EOL;
    echo "  Active: " . ($shop->is_active ? 'yes' : 'no') . PHP_EOL;
    echo "  Connection: " . $shop->connection_status . PHP_EOL;
    echo "  Version: " . ($shop->prestashop_version ?? 'not set') . PHP_EOL;
    echo PHP_EOL;
}
