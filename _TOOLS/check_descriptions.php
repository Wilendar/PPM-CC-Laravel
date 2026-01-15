<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Find descriptions with blocks
$withBlocks = App\Models\ProductDescription::where(function($q) {
    $q->whereNotNull('blocks_v2')
      ->orWhere('blocks_json', '!=', '[]')
      ->orWhere('blocks_json', '!=', 'null');
})->get(['id', 'product_id', 'shop_id', 'blocks_json', 'blocks_v2']);

echo "Descriptions with potential blocks:\n";
foreach ($withBlocks as $desc) {
    $blocksJson = $desc->blocks_json;
    $blocksV2 = $desc->blocks_v2;

    $hasJson = !empty($blocksJson) && $blocksJson !== '[]' && $blocksJson !== [];
    $hasV2 = !empty($blocksV2);

    if ($hasJson || $hasV2) {
        echo "ID: {$desc->id} | Product: {$desc->product_id} | Shop: {$desc->shop_id}";
        echo " | JSON: " . ($hasJson ? 'YES' : 'no');
        echo " | V2: " . ($hasV2 ? 'YES' : 'no');
        echo "\n";
    }
}

echo "\nTotal descriptions: " . App\Models\ProductDescription::count() . "\n";
