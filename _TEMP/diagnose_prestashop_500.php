<?php

/**
 * Diagnose PrestaShop API 500 Error
 * Captures exact request/response to identify issue
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

echo "=== PRESTASHOP API 500 DIAGNOSIS ===\n\n";

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

echo "Test Product:\n";
echo "  Product ID: {$product->id}\n";
echo "  SKU: {$product->sku}\n";
echo "  Shop: {$shop->name}\n";
echo "  Shop URL: {$shop->api_url}\n";
echo "  PrestaShop Product ID: {$productShopData->prestashop_product_id}\n\n";

// Create client
$client = $shop->prestashop_version === '9'
    ? new \App\Services\PrestaShop\PrestaShop9Client($shop)
    : new PrestaShop8Client($shop);

echo "Client created: " . get_class($client) . "\n\n";

// Transform product data
$transformer = app(ProductTransformer::class);
echo "Transforming product data...\n";

try {
    $productData = $transformer->transformForPrestaShop($product, $client);

    echo "✓ Product data transformed\n";
    echo "  Has product key: " . (isset($productData['product']) ? 'YES' : 'NO') . "\n";
    echo "  Reference: " . ($productData['product']['reference'] ?? 'N/A') . "\n";
    echo "  Name: " . (is_array($productData['product']['name'] ?? null)
        ? $productData['product']['name'][0]['value'] ?? 'N/A'
        : ($productData['product']['name'] ?? 'N/A')) . "\n";

    // Show key fields
    echo "\nKey fields being sent:\n";
    $keyFields = ['id', 'reference', 'price', 'active', 'id_manufacturer', 'id_tax_rules_group'];
    foreach ($keyFields as $field) {
        if (isset($productData['product'][$field])) {
            $value = $productData['product'][$field];
            echo "  {$field}: " . (is_array($value) ? json_encode($value) : $value) . "\n";
        }
    }

    echo "\n";

} catch (\Exception $e) {
    echo "❌ Transform failed: {$e->getMessage()}\n";
    exit(1);
}

// Try API update with detailed logging
echo "Attempting API update to PrestaShop...\n";
echo str_repeat('=', 80) . "\n";

try {
    // Get current product from PrestaShop first
    echo "1. Fetching current product from PrestaShop...\n";
    $currentProduct = $client->getProduct($productShopData->prestashop_product_id);
    echo "   ✓ Current product fetched (ID: {$currentProduct['product']['id']})\n\n";

    // Try update
    echo "2. Sending UPDATE request...\n";
    $response = $client->updateProduct($productShopData->prestashop_product_id, $productData);

    echo "   ✅ UPDATE SUCCESSFUL!\n";
    echo "   Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";

} catch (\App\Exceptions\PrestaShopAPIException $e) {
    echo "   ❌ PrestaShop API Exception:\n";
    echo "   Message: {$e->getMessage()}\n";
    echo "   Code: {$e->getCode()}\n";

    if (method_exists($e, 'getContext')) {
        $context = $e->getContext();
        if (isset($context['request_url'])) {
            echo "   Request URL: {$context['request_url']}\n";
        }
        if (isset($context['response_body'])) {
            echo "   Response Body (first 500 chars):\n";
            echo "   " . substr($context['response_body'], 0, 500) . "\n";
        }
    }

    echo "\n";

    // Check PrestaShop error logs
    echo "3. Checking PrestaShop error logs...\n";
    echo "   Run this command to see PrestaShop errors:\n";
    echo "   ssh dev.mpptrade.pl \"tail -50 /path/to/prestashop/var/logs/error.log\"\n\n";

} catch (\Exception $e) {
    echo "   ❌ Unexpected Exception:\n";
    echo "   Class: " . get_class($e) . "\n";
    echo "   Message: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n\n";
}

echo str_repeat('=', 80) . "\n";

// Check for common issues
echo "\n4. Common PrestaShop 500 causes:\n";
echo "   □ Missing required fields (id_manufacturer, id_supplier_default, etc.)\n";
echo "   □ Invalid associations (categories, features, etc.)\n";
echo "   □ Database constraints violations\n";
echo "   □ PrestaShop module conflicts\n";
echo "   □ Memory limit exceeded\n";
echo "   □ Invalid XML structure\n\n";

echo "=== DIAGNOSIS COMPLETE ===\n";
echo "\nNext steps:\n";
echo "1. Check PrestaShop error logs on dev.mpptrade.pl\n";
echo "2. Verify all required fields are present\n";
echo "3. Test with minimal product data\n";
