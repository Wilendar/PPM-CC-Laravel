<?php

/**
 * Test what exact payload is being sent to PrestaShop
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ProductShopData;
use App\Services\PrestaShop\PrestaShop8Client;
use App\Services\PrestaShop\ProductTransformer;
use Illuminate\Support\Facades\Log;

echo "=== PRESTASHOP PAYLOAD INSPECTION ===\n\n";

// Find test product
$productShopData = ProductShopData::whereHas('shop', fn($q) => $q->where('is_active', true))
    ->whereHas('product', fn($q) => $q->where('is_active', true))
    ->whereNotNull('prestashop_product_id')
    ->first();

if (!$productShopData) {
    echo "❌ No test product found\n";
    exit(1);
}

$product = $productShopData->product;
$shop = $productShopData->shop;

echo "Product: {$product->sku} (ID: {$product->id})\n";
echo "Shop: {$shop->name}\n";
echo "PrestaShop ID: {$productShopData->prestashop_product_id}\n\n";

// Create client
$client = $shop->prestashop_version === '9'
    ? new \App\Services\PrestaShop\PrestaShop9Client($shop)
    : new PrestaShop8Client($shop);

// Transform product
$transformer = app(ProductTransformer::class);
$productData = $transformer->transformForPrestaShop($product, $client);

echo "=== TRANSFORMED DATA ===\n";
echo json_encode($productData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Focus on associations
echo "=== CATEGORIES ASSOCIATION ===\n";
if (isset($productData['product']['associations']['categories'])) {
    echo "Categories: " . json_encode($productData['product']['associations']['categories'], JSON_PRETTY_PRINT) . "\n";
    echo "Count: " . count($productData['product']['associations']['categories']) . "\n";
} else {
    echo "❌ No categories in associations!\n";
}

echo "\n=== MANUFACTURER ===\n";
if (isset($productData['product']['manufacturer_name'])) {
    echo "Manufacturer name: " . $productData['product']['manufacturer_name'] . "\n";
}
if (isset($productData['product']['id_manufacturer'])) {
    echo "Manufacturer ID: " . $productData['product']['id_manufacturer'] . "\n";
}

// Now try to fetch current product from PrestaShop
echo "\n=== CURRENT PRODUCT IN PRESTASHOP ===\n";
try {
    $currentProduct = $client->getProduct($productShopData->prestashop_product_id);

    echo "Current categories in PrestaShop:\n";
    if (isset($currentProduct['product']['associations']['categories'])) {
        echo json_encode($currentProduct['product']['associations']['categories'], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "  (no categories)\n";
    }

    echo "\nCurrent manufacturer in PrestaShop:\n";
    echo "  ID: " . ($currentProduct['product']['id_manufacturer'] ?? 'N/A') . "\n";
    echo "  Name: " . ($currentProduct['product']['manufacturer_name'] ?? 'N/A') . "\n";

} catch (\Exception $e) {
    echo "❌ Failed to fetch current product: {$e->getMessage()}\n";
}

// Check if categories are mapped
echo "\n=== CATEGORY MAPPING CHECK ===\n";
foreach ($product->categories as $category) {
    $mapper = app(\App\Services\PrestaShop\CategoryMapper::class);
    $psId = $mapper->mapToPrestaShop($category->id, $shop);
    echo "PPM Category {$category->id} ({$category->name}) → PrestaShop ID: " . ($psId ?? 'NOT MAPPED') . "\n";
}

echo "\n=== END ===\n";
