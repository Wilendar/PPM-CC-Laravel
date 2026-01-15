<?php
// Check product type for product 11198

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$product = \App\Models\Product::with('productType')->find(11198);

echo "=== PRODUCT 11198 ===\n";
echo "SKU: {$product->sku}\n";
echo "Name: {$product->name}\n";
echo "Product Type ID: " . ($product->product_type_id ?? 'NULL') . "\n";

if ($product->productType) {
    echo "Product Type Name: {$product->productType->name}\n";
    echo "Product Type Slug: {$product->productType->slug}\n";
} else {
    echo "Product Type: NULL (NO TYPE ASSIGNED!)\n";
}

echo "\n=== ALL PRODUCT TYPES ===\n";
$types = \App\Models\ProductType::all();
foreach ($types as $t) {
    echo "[{$t->id}] {$t->name} (slug: {$t->slug})\n";
}

echo "\n=== ISSUE ANALYSIS ===\n";
$requiredSlug = 'czesc-zamienna';
if ($product->productType && $product->productType->slug === $requiredSlug) {
    echo "Product HAS correct type for compatibility import\n";
    echo "Import SHOULD call VehicleCompatibilitySyncService\n";
} else {
    echo "Product DOES NOT have required type '{$requiredSlug}'\n";
    echo "Import will SKIP compatibility import!\n";
    echo "This is why no compatibilities were created!\n";
}
