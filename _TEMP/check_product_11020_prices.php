<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;

$product = Product::with(['prices.priceGroup', 'shopData'])->find(11020);

if (!$product) {
    echo "Product 11020 not found\n";
    exit(1);
}

echo "=== PRODUCT 11020 DATA ===\n";
echo "SKU: {$product->sku}\n";
echo "Tax Rate: {$product->tax_rate}%\n";
echo "Name: {$product->name}\n";
echo "\n";

echo "=== PRICES ===\n";
foreach ($product->prices as $price) {
    echo "Group: {$price->priceGroup->code}\n";
    echo "  Net:   {$price->price_net} PLN\n";
    echo "  Gross: {$price->price_gross} PLN\n";
    echo "  PrestaShop Mapping: " . json_encode($price->prestashop_mapping, JSON_PRETTY_PRINT) . "\n";
    echo "\n";
}

echo "=== PRESTASHOP SHOP DATA ===\n";
foreach ($product->shopData as $shopData) {
    echo "Shop ID: {$shopData->shop_id}\n";
    echo "  PrestaShop Product ID: {$shopData->prestashop_product_id}\n";
    echo "  Last Pulled: {$shopData->last_pulled_at}\n";
    echo "\n";
}
