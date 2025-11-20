<?php
// Find existing test product

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== SEARCHING FOR TEST PRODUCT ===\n\n";

// Check if 11018 exists
$product11018 = DB::table('products')->find(11018);
if ($product11018) {
    echo "✅ Product 11018 exists: {$product11018->name}\n\n";
} else {
    echo "❌ Product 11018 NOT found\n\n";
}

// Get any product with active shop
echo "--- AVAILABLE PRODUCTS (first 10) ---\n";
$products = DB::table('products')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get(['id', 'sku', 'name']);

foreach ($products as $p) {
    echo sprintf("ID: %d | SKU: %s | Name: %s\n", $p->id, $p->sku, $p->name);
}

echo "\n--- ACTIVE SHOPS ---\n";
$shops = DB::table('prestashop_shops')
    ->where('is_active', true)
    ->get(['id', 'name', 'connection_status']);

foreach ($shops as $s) {
    echo sprintf("Shop ID: %d | Name: %s | Status: %s\n", $s->id, $s->name, $s->connection_status);
}

// Recommend a test product
if ($products->isNotEmpty()) {
    $testProduct = $products->first();
    echo "\n=== RECOMMENDATION ===\n";
    echo "Use Product ID: {$testProduct->id} for testing\n";
    echo "  SKU: {$testProduct->sku}\n";
    echo "  Name: {$testProduct->name}\n";
}
