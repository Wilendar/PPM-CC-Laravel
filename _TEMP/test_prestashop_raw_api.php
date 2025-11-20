<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductShopData;
use App\Models\PrestaShopShop;

echo "=== RAW PRESTASHOP API TEST ===\n\n";

$psd = ProductShopData::where('product_id', 11034)
    ->where('shop_id', 1)
    ->first();

if (!$psd) {
    echo "ERROR: ProductShopData NOT FOUND\n";
    exit(1);
}

$shop = $psd->shop;
$apiUrl = rtrim($shop->api_url, '/');
$apiKey = decrypt($shop->api_key);
$productId = $psd->prestashop_product_id;

echo "Shop: {$shop->name}\n";
echo "API URL: {$apiUrl}\n";
echo "Product ID: {$productId}\n\n";

// Test 1: Simple GET request
echo "TEST 1: Simple GET products/{$productId}\n";
$url = "{$apiUrl}/products/{$productId}";
echo "URL: {$url}\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Basic " . base64_encode("{$apiKey}:")
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

echo "HTTP Code: {$httpCode}\n";

if ($error) {
    echo "CURL Error: {$error}\n";
}

if ($httpCode == 200) {
    echo "SUCCESS!\n";

    // Parse XML
    $xml = simplexml_load_string($response);

    if ($xml && isset($xml->product->associations->categories->category)) {
        echo "\nCategories found in PrestaShop:\n";
        foreach ($xml->product->associations->categories->category as $cat) {
            echo "  PrestaShop Category ID: {$cat->id}\n";
        }
    } else {
        echo "No categories found or XML parse error\n";
    }
} else {
    echo "ERROR Response:\n";
    echo substr($response, 0, 500) . "\n";
}

curl_close($ch);

// Test 2: Check if product exists at all
echo "\n\nTEST 2: Check product existence\n";
$url = "{$apiUrl}/products?filter[reference]=" . urlencode($psd->product->sku);
echo "URL: {$url}\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Basic " . base64_encode("{$apiKey}:")
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Code: {$httpCode}\n";

if ($httpCode == 200) {
    $xml = simplexml_load_string($response);
    if ($xml && isset($xml->products->product)) {
        foreach ($xml->products->product as $product) {
            echo "  Found Product ID: {$product['id']}\n";
        }
    } else {
        echo "  No products found with SKU: {$psd->product->sku}\n";
    }
} else {
    echo "ERROR Response:\n";
    echo substr($response, 0, 500) . "\n";
}

curl_close($ch);

echo "\n=== TEST COMPLETE ===\n";
