<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductDescription;
use App\Models\ProductShopData;

echo "=== ProductDescription for product 11183 ===\n";
$descs = ProductDescription::where('product_id', 11183)->get();
foreach ($descs as $d) {
    echo "ID: {$d->id}, Shop: {$d->shop_id}, Blocks: " . strlen($d->blocks_v2 ?? '') . ", HTML: " . strlen($d->rendered_html ?? '') . "\n";
}

echo "\n=== ProductShopData for product 11183 ===\n";
$shopData = ProductShopData::where('product_id', 11183)->with('shop')->get();
foreach ($shopData as $sd) {
    echo "Shop ID: {$sd->shop_id}, Name: " . ($sd->shop->name ?? 'N/A') . ", PS ID: {$sd->prestashop_id}\n";
}
