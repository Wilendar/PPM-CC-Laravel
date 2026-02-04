<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductScanSession;

// Test query with ERP data loading
$session = new ProductScanSession([
    'source_type' => 'baselinker',
    'source_id' => 1,
]);

$query = Product::query()
    ->whereNotNull('sku')
    ->where('sku', '!=', '')
    ->select(['id', 'sku', 'name', 'ean', 'manufacturer_id'])
    ->with([
        'erpData' => function ($q) use ($session) {
            $q->where('erp_connection_id', $session->source_id);
        },
        'manufacturerRelation:id,name'
    ]);

echo "Query SQL: " . $query->toSql() . PHP_EOL;
echo "Total products: " . $query->count() . PHP_EOL;

// Check specific product with erpData
$product = $query->where('id', 11216)->first();
if ($product) {
    echo PHP_EOL . "Product ID: " . $product->id . PHP_EOL;
    echo "SKU: " . $product->sku . PHP_EOL;
    echo "erpData loaded: " . ($product->relationLoaded('erpData') ? 'yes' : 'no') . PHP_EOL;
    echo "erpData count: " . $product->erpData->count() . PHP_EOL;
    echo "erpData isNotEmpty: " . ($product->erpData->isNotEmpty() ? 'yes' : 'no') . PHP_EOL;
} else {
    echo "Product 11216 not found" . PHP_EOL;
}

// Test chunk behavior
echo PHP_EOL . "Testing chunk behavior:" . PHP_EOL;
$linkedCount = 0;
$notLinkedCount = 0;

// Re-create query for chunk
$query2 = Product::query()
    ->whereNotNull('sku')
    ->where('sku', '!=', '')
    ->select(['id', 'sku', 'name', 'ean', 'manufacturer_id'])
    ->with([
        'erpData' => function ($q) {
            $q->where('erp_connection_id', 1);
        },
        'manufacturerRelation:id,name'
    ]);

$query2->chunk(100, function ($products) use (&$linkedCount, &$notLinkedCount) {
    foreach ($products as $product) {
        if ($product->erpData->isNotEmpty()) {
            $linkedCount++;
            echo "  LINKED: " . $product->sku . " (erpData count: " . $product->erpData->count() . ")" . PHP_EOL;
        } else {
            $notLinkedCount++;
        }
    }
});

echo PHP_EOL . "Linked products: " . $linkedCount . PHP_EOL;
echo "Not linked products: " . $notLinkedCount . PHP_EOL;
