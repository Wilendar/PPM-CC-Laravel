<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$product = App\Models\Product::find(11217);
echo "=== Product 11217 ===\n";
echo "SKU: {$product->sku}\n";
echo "Name: {$product->name}\n";
echo "Has variants flag: " . ($product->has_variants ? 'YES' : 'NO') . "\n";

$variantsCount = $product->variants()->count();
echo "Variants count: {$variantsCount}\n";

if ($variantsCount > 0) {
    echo "\nVariants:\n";
    foreach ($product->variants as $variant) {
        echo "  - {$variant->sku}: {$variant->variant_name}\n";
    }
}

// Check integration mapping
$mapping = $product->integrationMappings()->where('integration_type', 'baselinker')->first();
if ($mapping) {
    echo "\nIntegration Mapping:\n";
    echo "  External ID: {$mapping->external_id}\n";
    $externalData = $mapping->external_data;
    if (isset($externalData['variants'])) {
        echo "  Variants in external_data: " . count($externalData['variants']) . "\n";
        foreach ($externalData['variants'] as $varId => $varData) {
            echo "    - ID {$varId}: {$varData['name']} (SKU: {$varData['sku']})\n";
        }
    }
}
