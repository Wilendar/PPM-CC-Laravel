<?php
// Check PrestaShop product features

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$shopId = 1; // B2B Test DEV
// Search for product by SKU
$sku = '101044-0047';
echo "=== SEARCHING FOR SKU: {$sku} ===\n";

$shop = \App\Models\PrestaShopShop::find($shopId);
echo "=== SHOP INFO ===\n";
echo "Shop: {$shop->name}\n";
echo "API URL: {$shop->api_url}\n\n";

// Get client
$clientClass = $shop->prestashop_version == '9'
    ? \App\Services\PrestaShop\PrestaShop9Client::class
    : \App\Services\PrestaShop\PrestaShop8Client::class;
$client = new $clientClass($shop);

echo "=== SEARCHING PRESTASHOP PRODUCT ===\n";
// Search by SKU
$searchResult = $client->makeRequest('GET', "products?filter[reference]={$sku}&display=full");
$products = $searchResult['products']['product'] ?? $searchResult['products'] ?? [];
if (empty($products)) {
    echo "Product not found by SKU: {$sku}\n";
    exit;
}
$psProduct = is_array($products) && isset($products[0]) ? $products[0] : $products;
echo "ID: " . ($psProduct['id'] ?? 'N/A') . "\n";
echo "Reference (SKU): " . ($psProduct['reference'] ?? 'N/A') . "\n";
echo "Name: " . ($psProduct['name'][0]['value'] ?? $psProduct['name'] ?? 'N/A') . "\n\n";

echo "=== PRESTASHOP PRODUCT FEATURES (associations) ===\n";
if (isset($psProduct['associations']['product_features']['product_feature'])) {
    $features = $psProduct['associations']['product_features']['product_feature'];
    // Normalize to array
    if (isset($features['id'])) {
        $features = [$features];
    }

    foreach ($features as $f) {
        $featureId = $f['id'] ?? 'N/A';
        $featureValueId = $f['id_feature_value'] ?? 'N/A';

        echo "Feature ID: {$featureId}, Feature Value ID: {$featureValueId}\n";

        // Get feature name
        try {
            $featureData = $client->makeRequest('GET', "product_features/{$featureId}");
            $featureName = $featureData['product_feature']['name'][0]['value'] ?? $featureData['product_feature']['name'] ?? 'Unknown';
            echo "  Feature Name: {$featureName}\n";
        } catch (\Exception $e) {
            echo "  Feature Name: ERROR - " . $e->getMessage() . "\n";
        }

        // Get feature value
        try {
            $valueData = $client->makeRequest('GET', "product_feature_values/{$featureValueId}");
            $valueName = $valueData['product_feature_value']['value'][0]['value'] ?? $valueData['product_feature_value']['value'] ?? 'Unknown';
            echo "  Value: {$valueName}\n";
        } catch (\Exception $e) {
            echo "  Value: ERROR - " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
} else {
    echo "No product features found in associations\n";
}

echo "\n=== COMPATIBILITY FEATURES (431, 432, 433) ===\n";
// Feature IDs for compatibility: 431=Oryginał, 432=Model, 433=Zamiennik
$compatFeatureIds = [431, 432, 433];
foreach ($compatFeatureIds as $fid) {
    try {
        $featureData = $client->makeRequest('GET', "product_features/{$fid}");
        $featureName = $featureData['product_feature']['name'][0]['value'] ?? $featureData['product_feature']['name'] ?? 'Unknown';
        echo "Feature {$fid}: {$featureName}\n";
    } catch (\Exception $e) {
        echo "Feature {$fid}: ERROR - " . $e->getMessage() . "\n";
    }
}
