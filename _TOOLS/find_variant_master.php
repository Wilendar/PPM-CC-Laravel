<?php
// Find variant master product for testing ZADANIE 2

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$product = App\Models\Product::where('is_variant_master', true)
    ->whereHas('variants')
    ->with('variants')
    ->first();

if ($product) {
    echo "Found variant master:\n";
    echo "  SKU: {$product->sku}\n";
    echo "  ID: {$product->id}\n";
    echo "  Name: {$product->name}\n";
    echo "  Variants: {$product->variants->count()}\n";

    foreach ($product->variants as $variant) {
        echo "    - {$variant->sku} (ID: {$variant->id})\n";
    }
} else {
    echo "No variant master product found with variants.\n";

    // Check if any products have is_variant_master = true
    $masters = App\Models\Product::where('is_variant_master', true)->count();
    echo "Products with is_variant_master=true: {$masters}\n";

    // Check variants count
    $variants = App\Models\ProductVariant::count();
    echo "Total ProductVariant records: {$variants}\n";
}
