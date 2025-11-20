<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();


$product = App\Models\Product::find(10969);
if (!$product) {
    echo "ERROR: Product not found";
    exit(1);
}
echo json_encode([
    'id' => $product->id,
    'sku' => $product->sku,
    'is_variant_master' => (bool)$product->is_variant_master,
    'has_variants' => (bool)$product->has_variants,
]);
    
?>