<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PrestaShopShop;

$shop = PrestaShopShop::find(5);
echo "ID: " . $shop->id . "\n";
echo "Name: " . $shop->name . "\n";
echo "URL: " . $shop->url . "\n";
echo "API URL: " . $shop->api_url . "\n";
echo "API Key: " . substr($shop->api_key ?? '', 0, 15) . "...\n";
