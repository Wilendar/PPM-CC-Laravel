<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductShopData;

$sku = $argv[1] ?? 'BG-KAYO-S200';

$product = Product::where('sku', $sku)->first();

if (!$product) {
    echo "Product not found: $sku\n";
    exit(1);
}

echo "=== PRODUCT: {$product->sku} ===\n";
echo "ID: {$product->id}\n";
echo "Name: {$product->name}\n\n";

echo "=== SHOP DATA (image_settings) ===\n";
$shopData = $product->shopData()->get();
foreach ($shopData as $sd) {
    echo "Shop ID: {$sd->shop_id}\n";
    echo "Image settings: " . json_encode($sd->image_settings, JSON_PRETTY_PRINT) . "\n";
    echo "---\n";
}

echo "\n=== ERP DATA (image_mappings) ===\n";
$erpData = $product->erpData()->get();
foreach ($erpData as $ed) {
    echo "ERP Connection ID: {$ed->erp_connection_id}\n";
    echo "Image mappings: " . json_encode($ed->image_mappings, JSON_PRETTY_PRINT) . "\n";
    echo "---\n";
}

// Check if product has any images from any source
echo "\n=== POSSIBLE IMAGE SOURCES ===\n";
// Check ProductShopData for any shop
$firstShopData = $shopData->first();
if ($firstShopData && !empty($firstShopData->image_settings)) {
    echo "Found images in ProductShopData!\n";
    print_r($firstShopData->image_settings);
}
