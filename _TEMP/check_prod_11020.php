<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;

$product = Product::with(['prices.priceGroup'])->find(11020);

if (!$product) {
    echo "Product 11020 not found\n";
    exit(1);
}

echo "SKU: {$product->sku}\n";
echo "Tax Rate: {$product->tax_rate}%\n";
echo "\n";

foreach ($product->prices as $price) {
    echo "Group: {$price->priceGroup->code} | Net: {$price->price_net} | Gross: {$price->price_gross}\n";
}