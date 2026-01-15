<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductShopData;
use App\Models\ProductDescription;

$productId = 11183;

echo "=== Product ===\n";
$product = Product::find($productId);
echo "ID: {$product->id}\n";
echo "Name: {$product->name}\n";
echo "Long Description length: " . strlen($product->long_description ?? '') . "\n";
echo "Long Description (first 200 chars): " . substr($product->long_description ?? '', 0, 200) . "\n\n";

echo "=== ProductShopData (per shop descriptions) ===\n";
$shopData = ProductShopData::where('product_id', $productId)->with('shop')->get();
foreach ($shopData as $sd) {
    echo "\n--- Shop ID: {$sd->shop_id} ({$sd->shop->name}) ---\n";
    echo "Long Description length: " . strlen($sd->long_description ?? '') . "\n";
    echo "Long Description (first 300 chars): " . substr($sd->long_description ?? '', 0, 300) . "\n";
}

echo "\n=== ProductDescription (visual editor blocks) ===\n";
$descs = ProductDescription::where('product_id', $productId)->get();
if ($descs->isEmpty()) {
    echo "No ProductDescription records found.\n";
} else {
    foreach ($descs as $d) {
        echo "\n--- Description ID: {$d->id}, Shop ID: {$d->shop_id} ---\n";
        echo "Blocks V2 length: " . strlen($d->blocks_v2 ?? '') . "\n";
        echo "Rendered HTML length: " . strlen($d->rendered_html ?? '') . "\n";
        echo "Rendered HTML (first 300 chars): " . substr($d->rendered_html ?? '', 0, 300) . "\n";
    }
}
