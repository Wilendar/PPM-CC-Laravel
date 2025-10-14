<?php
/**
 * Verify Imported Products
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== IMPORTED PRODUCTS VERIFICATION ===\n\n";

$products = DB::table('products')
    ->whereIn('id', [7, 8, 9])
    ->get(['id', 'sku', 'name', 'is_active', 'created_at']);

if ($products->isEmpty()) {
    echo "No products found with IDs: 7, 8, 9\n";
    exit(1);
}

echo "Found " . $products->count() . " products:\n\n";

foreach ($products as $product) {
    echo "ID: {$product->id}\n";
    echo "SKU: {$product->sku}\n";
    echo "Name: {$product->name}\n";
    echo "Active: " . ($product->is_active ? 'YES' : 'NO') . "\n";
    echo "Created: {$product->created_at}\n";
    echo str_repeat('-', 50) . "\n";
}

// Check total products count
$totalCount = DB::table('products')->count();
echo "\nTotal products in database: {$totalCount}\n";
