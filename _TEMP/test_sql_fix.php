<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make("Illuminate\Contracts\Console\Kernel")->bootstrap();

use App\Models\Product;

$product = Product::find(11033);

if (!$product) {
    echo "Product 11033 not found\n";
    exit(1);
}

echo "Product: {$product->name}\n\n";

echo "Testing categoriesForShop(1, false):\n";
try {
    $shopCats = $product->categoriesForShop(1, false)->pluck('categories.id')->toArray();
    echo "Shop categories: " . json_encode($shopCats) . "\n";
    echo "✅ PASS: No SQL error for shop-specific categories\n\n";
} catch (\Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "\n\n";
    exit(1);
}

echo "Testing categories() [global]:\n";
try {
    $globalCats = $product->categories()->pluck('categories.id')->toArray();
    echo "Global categories: " . json_encode($globalCats) . "\n";
    echo "✅ PASS: No SQL error for global categories\n\n";
} catch (\Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "\n\n";
    exit(1);
}

echo "===================\n";
echo "✅ ALL TESTS PASSED\n";
echo "===================\n";
echo "SQL fix verified successfully!\n";
