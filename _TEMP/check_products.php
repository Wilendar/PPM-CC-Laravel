<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$products = App\Models\Product::all(['id', 'sku', 'is_variant_master', 'has_variants']);

echo "ID | SKU | is_variant_master | has_variants\n";
echo "--------------------------------------------\n";

foreach ($products as $p) {
    echo $p->id . " | " . $p->sku . " | " . ($p->is_variant_master ? 'YES' : 'NO') . " | " . ($p->has_variants ? 'YES' : 'NO') . "\n";
}

// Check variant counts
echo "\n";
echo "Checking variant counts...\n";
foreach ($products as $p) {
    $variantCount = App\Models\ProductVariant::where('product_id', $p->id)->count();
    if ($variantCount > 0) {
        echo "Product ID {$p->id} ({$p->sku}) has {$variantCount} variants\n";
    }
}
