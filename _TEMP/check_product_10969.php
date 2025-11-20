<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$product = App\Models\Product::find(10977);

echo "=== PRODUCT 10977 STATE ===\n";
echo "ID: " . $product->id . "\n";
echo "SKU: " . $product->sku . "\n";
echo "Name: " . $product->name . "\n";
echo "\n";

echo "=== DATABASE VALUES (getRawOriginal) ===\n";
echo "is_variant_master (raw): " . $product->getRawOriginal('is_variant_master') . "\n";
echo "has_variants (raw): " . $product->getRawOriginal('has_variants') . "\n";
echo "\n";

echo "=== ELOQUENT ACCESSORS ===\n";
echo "is_variant_master (accessor): " . ($product->is_variant_master ? '1' : '0') . "\n";
echo "has_variants (accessor): " . ($product->has_variants ? '1' : '0') . "\n";
echo "\n";

echo "=== VARIANTS COUNT ===\n";
$variantCount = App\Models\ProductVariant::where('product_id', 10969)->count();
echo "Variants in database: " . $variantCount . "\n";
echo "\n";

echo "=== EXPECTED BEHAVIOR ===\n";
echo "Checkbox should be: " . ($product->is_variant_master ? 'CHECKED' : 'UNCHECKED') . "\n";
echo "Tab should be: " . ($product->has_variants ? 'VISIBLE' : 'HIDDEN') . "\n";
