<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test produktu z powiązaniem do Baselinker (id=11216)
$product = \App\Models\Product::with(['erpData' => function($q) {
    $q->where('erp_connection_id', 1);
}])->find(11216);

echo "Product ID: " . $product->id . PHP_EOL;
echo "Product SKU: " . $product->sku . PHP_EOL;
echo "erpData count: " . $product->erpData->count() . PHP_EOL;
echo "erpData isEmpty: " . ($product->erpData->isEmpty() ? 'true' : 'false') . PHP_EOL;
echo "erpData isNotEmpty: " . ($product->erpData->isNotEmpty() ? 'true' : 'false') . PHP_EOL;

if ($product->erpData->isNotEmpty()) {
    $link = $product->erpData->first();
    echo "First link external_id: " . $link->external_id . PHP_EOL;
}
