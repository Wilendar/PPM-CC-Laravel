<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "All visual descriptions with blocks:" . PHP_EOL;
$descriptions = DB::table('product_descriptions')
    ->whereNotNull('blocks_json')
    ->where('blocks_json', '!=', '[]')
    ->where('blocks_json', '!=', 'null')
    ->where('blocks_json', '!=', '')
    ->limit(10)
    ->get();

foreach ($descriptions as $desc) {
    $product = DB::table('products')->where('id', $desc->product_id)->first();
    echo "  Desc ID: " . $desc->id . " | Product: " . $desc->product_id . " (" . ($product->sku ?? 'N/A') . ") | Shop: " . $desc->shop_id . PHP_EOL;
}

if ($descriptions->isEmpty()) {
    echo "  No visual descriptions with blocks found." . PHP_EOL;
}

echo PHP_EOL . "Total count in product_descriptions table: " . DB::table('product_descriptions')->count() . PHP_EOL;
