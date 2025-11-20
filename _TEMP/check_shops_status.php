<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PrestaShopShop;

$shops = PrestaShopShop::all();

echo "Total shops: " . $shops->count() . "\n\n";

foreach ($shops as $shop) {
    echo "Shop ID: {$shop->id}\n";
    echo "Name: {$shop->name}\n";
    echo "Active: " . ($shop->is_active ? 'YES' : 'NO') . "\n";
    echo "API Key exists: " . ($shop->api_key ? 'YES' : 'NO') . "\n";
    echo "URL: {$shop->url}\n";
    echo "---\n";
}
