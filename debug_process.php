<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductScanSession;

// Emulate the exact query from the job
$session = ProductScanSession::find(18); // Use existing session
if (!$session) {
    echo "Creating test session" . PHP_EOL;
    $session = new ProductScanSession([
        'source_type' => 'baselinker',
        'source_id' => 1,
    ]);
}

echo "Session: source_type=" . $session->source_type . ", source_id=" . $session->source_id . PHP_EOL;

// THIS IS THE EXACT QUERY FROM getAllPpmProductsQuery
$query = Product::query()
    ->whereNotNull('sku')
    ->where('sku', '!=', '')
    ->select(['id', 'sku', 'name', 'ean', 'manufacturer_id']);

if ($session->source_type === ProductScanSession::SOURCE_PRESTASHOP) {
    $query->with([
        'shopData' => function ($q) use ($session) {
            $q->where('shop_id', $session->source_id);
        },
        'manufacturerRelation:id,name'
    ]);
} else {
    $query->with([
        'erpData' => function ($q) use ($session) {
            $q->where('erp_connection_id', $session->source_id);
        },
        'manufacturerRelation:id,name'
    ]);
}

// Test the exact condition from isProductAlreadyLinked
echo PHP_EOL . "Testing isProductAlreadyLinked logic:" . PHP_EOL;
$alreadyLinkedCount = 0;
$notLinkedCount = 0;

$query->chunk(100, function ($products) use ($session, &$alreadyLinkedCount, &$notLinkedCount) {
    foreach ($products as $product) {
        // Exact logic from isProductAlreadyLinked
        if ($session->source_type === ProductScanSession::SOURCE_PRESTASHOP) {
            $isLinked = $product->shopData->isNotEmpty();
        } else {
            $isLinked = $product->erpData->isNotEmpty();
        }

        if ($isLinked) {
            $alreadyLinkedCount++;
            echo "  LINKED: " . $product->sku . " (erpData count: " . $product->erpData->count() . ")" . PHP_EOL;
        } else {
            $notLinkedCount++;
        }
    }
});

echo PHP_EOL . "Already linked: " . $alreadyLinkedCount . PHP_EOL;
echo "Not linked: " . $notLinkedCount . PHP_EOL;
echo "Total: " . ($alreadyLinkedCount + $notLinkedCount) . PHP_EOL;
