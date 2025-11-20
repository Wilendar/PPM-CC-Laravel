<?php
// Find product with shop data linked
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;

// Find first product with shopData
$product = Product::with('shopData.shop')
    ->whereHas('shopData')
    ->first();

if ($product) {
    echo "Product ID: {$product->id}\n";
    echo "SKU: {$product->sku}\n";
    echo "Name: {$product->name}\n";
    echo "Shop Count: {$product->shopData->count()}\n";

    foreach ($product->shopData as $shopData) {
        echo "  - Shop: {$shopData->shop->name} (ID: {$shopData->shop_id})\n";
        echo "    Status: {$shopData->sync_status}\n";
        echo "    PrestaShop ID: {$shopData->prestashop_product_id}\n";
    }
} else {
    echo "No products with shop data found!\n";
}
