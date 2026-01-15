<?php

/**
 * Delete empty ProductDescription record to force fresh import
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductDescription;

$productId = 11183;
$shopId = 5;

echo "=== BEFORE DELETE ===\n";
$desc = ProductDescription::where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

if ($desc) {
    echo "Found ProductDescription ID: {$desc->id}\n";
    echo "Blocks count: " . count($desc->blocks_json ?? []) . "\n";

    if (empty($desc->blocks_json) || count($desc->blocks_json) === 0) {
        echo "\nDeleting empty ProductDescription...\n";
        $desc->delete();
        echo "DELETED!\n";
    } else {
        echo "\nRecord has blocks - NOT deleting\n";
    }
} else {
    echo "No ProductDescription found for product {$productId} shop {$shopId}\n";
}

echo "\n=== AFTER DELETE ===\n";
$descAfter = ProductDescription::where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

echo $descAfter ? "Record still exists" : "Record deleted successfully";
echo "\n";
