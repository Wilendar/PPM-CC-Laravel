<?php
// Check shop data in database

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ALL PRESTASHOP SHOPS ===\n\n";

$shops = \App\Models\PrestaShopShop::all();

foreach ($shops as $shop) {
    echo "ID: {$shop->id}\n";
    echo "Name: {$shop->name}\n";
    echo "API URL: " . ($shop->api_url ?: 'EMPTY') . "\n";
    echo "URL (shop->url): " . ($shop->url ?: 'EMPTY') . "\n";
    echo "API Key: " . ($shop->api_key ? substr($shop->api_key, 0, 10) . '...' : 'EMPTY') . "\n";
    echo "PS Version: {$shop->prestashop_version}\n";
    echo "Is Active: " . ($shop->is_active ? 'Yes' : 'No') . "\n";
    echo "---\n";
}

echo "\nTotal shops: " . $shops->count() . "\n";
