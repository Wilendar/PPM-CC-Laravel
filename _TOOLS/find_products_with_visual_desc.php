<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Find products with visual descriptions for shop ID=5 (Test KAYO)
$shopId = 5;

$descriptions = DB::table('product_descriptions')
    ->where('shop_id', $shopId)
    ->whereNotNull('blocks_json')
    ->where('blocks_json', '!=', '[]')
    ->where('blocks_json', '!=', 'null')
    ->select('id', 'product_id', 'shop_id')
    ->limit(5)
    ->get();

echo "Products with visual descriptions for shop_id=$shopId:" . PHP_EOL;
foreach ($descriptions as $desc) {
    $product = DB::table('products')->where('id', $desc->product_id)->first();
    echo "  Product ID: " . $desc->product_id . " | SKU: " . ($product->sku ?? 'N/A') . " | Name: " . ($product->name ?? 'N/A') . PHP_EOL;
}

if ($descriptions->isEmpty()) {
    echo "  No products with visual descriptions found." . PHP_EOL;

    // Check if any products are assigned to this shop
    $shopProducts = DB::table('product_shop_data')
        ->where('shop_id', $shopId)
        ->limit(5)
        ->get();

    echo PHP_EOL . "Products assigned to shop_id=$shopId:" . PHP_EOL;
    foreach ($shopProducts as $sp) {
        $product = DB::table('products')->where('id', $sp->product_id)->first();
        echo "  Product ID: " . $sp->product_id . " | SKU: " . ($product->sku ?? 'N/A') . PHP_EOL;
    }
}
