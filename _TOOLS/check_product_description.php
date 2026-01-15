<?php

/**
 * Check if product has long_description in PPM database
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

$product = Product::find(11183);
if (!$product) {
    echo "Product 11183 not found!\n";
    exit(1);
}

echo "=== PRODUCT ===\n";
echo "ID: {$product->id}\n";
echo "SKU: {$product->sku}\n\n";

echo "=== LONG_DESCRIPTION ===\n";
$longDesc = $product->long_description ?? '';
echo "Length: " . strlen($longDesc) . " chars\n";
if (!empty($longDesc)) {
    echo "First 500 chars: " . substr($longDesc, 0, 500) . "\n";
} else {
    echo "Value: EMPTY/NULL\n";
}

echo "\n=== SHOP-SPECIFIC DATA (Shop 5) ===\n";
$shopData = $product->dataForShop(5)->first();
if ($shopData) {
    echo "ProductShopData ID: {$shopData->id}\n";
    $shopLongDesc = $shopData->long_description ?? '';
    echo "long_description length: " . strlen($shopLongDesc) . " chars\n";
    if (!empty($shopLongDesc)) {
        echo "First 500 chars: " . substr($shopLongDesc, 0, 500) . "\n";
    } else {
        echo "Value: EMPTY/NULL\n";
    }
} else {
    echo "No ProductShopData for shop 5\n";
}

echo "\n=== PRESTASHOP SYNC ===\n";
$psMapping = DB::table('prestashop_product_mappings')
    ->where('product_id', 11183)
    ->where('shop_id', 5)
    ->first();

if ($psMapping) {
    echo "PrestaShop Product ID: {$psMapping->prestashop_product_id}\n";
} else {
    echo "No PrestaShop mapping for this product/shop\n";
}
