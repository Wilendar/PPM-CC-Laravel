<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\DB;

$productId = 11190;
$product = Product::with('productType')->find($productId);

if (!$product) {
    echo "Product {$productId} not found\n";
    exit(1);
}

echo "=== Product Type Check ===\n";
echo "Product ID: {$product->id}\n";
echo "Product Name: {$product->name}\n";
echo "Product SKU: {$product->sku}\n";

if ($product->productType) {
    echo "Product Type ID: {$product->productType->id}\n";
    echo "Product Type Name: {$product->productType->name}\n";
    echo "Product Type Slug: {$product->productType->slug}\n";
} else {
    echo "Product Type: NULL\n";
    
    // Check raw column
    $raw = DB::table('products')->where('id', $productId)->first();
    echo "Raw product_type_id: " . ($raw->product_type_id ?? 'N/A') . "\n";
}

// Check if it's spare part
$isSpare = $product->productType?->slug === 'czesc-zamienna';
echo "\nIs 'czesc-zamienna': " . ($isSpare ? 'YES' : 'NO') . "\n";

// List all product types
echo "\n=== All Product Types ===\n";
$types = DB::table('product_types')->get();
foreach ($types as $t) {
    echo "  ID: {$t->id}, Slug: {$t->slug}, Name: {$t->name}\n";
}
