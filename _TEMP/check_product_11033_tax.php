<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductShopData;
use App\Models\Product;

echo "=== PRODUCT 11033 TAX RATE STATUS ===" . PHP_EOL;
echo PHP_EOL;

// Check ProductShopData
$psd = ProductShopData::where('product_id', 11033)->where('shop_id', 1)->first();

if ($psd) {
    echo "Product 11033 - Shop 1 (B2B Test DEV):" . PHP_EOL;
    echo "  tax_rate_override: " . ($psd->tax_rate_override ?? 'NULL') . PHP_EOL;
    echo "  getEffectiveTaxRate(): " . $psd->getEffectiveTaxRate() . PHP_EOL;
    echo PHP_EOL;
} else {
    echo "❌ No shop data found for product 11033 shop 1" . PHP_EOL;
}

// Check Product default
$product = Product::find(11033);

if ($product) {
    echo "Product 11033 default tax_rate: " . $product->tax_rate . PHP_EOL;
} else {
    echo "❌ Product 11033 not found" . PHP_EOL;
}

echo PHP_EOL;
echo "=== END ===" . PHP_EOL;
