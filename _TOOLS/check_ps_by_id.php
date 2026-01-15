<?php
// Check PrestaShop product by ID directly

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$shopId = 1; // B2B Test DEV
$productId = 9722; // Product ID from previous import

echo "=== CHECKING PRODUCT BY ID: {$productId} ===\n\n";

$shop = \App\Models\PrestaShopShop::find($shopId);
echo "Shop: {$shop->name}\n";
echo "URL: {$shop->url}\n\n";

$clientClass = $shop->prestashop_version == '9'
    ? \App\Services\PrestaShop\PrestaShop9Client::class
    : \App\Services\PrestaShop\PrestaShop8Client::class;
$client = new $clientClass($shop);

echo "=== TESTING API CONNECTION ===\n";
try {
    // First test if API works at all
    $testResult = $client->makeRequest('GET', 'products?limit=1');
    echo "API works! Found products in shop\n\n";
} catch (\Exception $e) {
    echo "API ERROR: " . $e->getMessage() . "\n";
    exit;
}

echo "=== FETCHING PRODUCT {$productId} ===\n";
try {
    $result = $client->makeRequest('GET', "products/{$productId}?display=full");
    $product = $result['product'];

    echo "Reference (SKU): " . ($product['reference'] ?? 'N/A') . "\n";
    $name = is_array($product['name']) ? ($product['name'][0]['value'] ?? 'N/A') : ($product['name'] ?? 'N/A');
    echo "Name: {$name}\n\n";

    echo "=== PRODUCT FEATURES ===\n";
    if (isset($product['associations']['product_features']['product_feature'])) {
        $features = $product['associations']['product_features']['product_feature'];
        if (isset($features['id'])) $features = [$features];

        foreach ($features as $f) {
            $featureId = $f['id'] ?? 'N/A';
            $featureValueId = $f['id_feature_value'] ?? 'N/A';
            echo "Feature ID: {$featureId}, Value ID: {$featureValueId}\n";

            try {
                $fData = $client->makeRequest('GET', "product_features/{$featureId}");
                $fName = is_array($fData['product_feature']['name'])
                    ? ($fData['product_feature']['name'][0]['value'] ?? 'N/A')
                    : ($fData['product_feature']['name'] ?? 'N/A');
                echo "  Name: {$fName}\n";
            } catch (\Exception $e) {
                echo "  Name: ERROR\n";
            }

            try {
                $vData = $client->makeRequest('GET', "product_feature_values/{$featureValueId}");
                $vName = is_array($vData['product_feature_value']['value'])
                    ? ($vData['product_feature_value']['value'][0]['value'] ?? 'N/A')
                    : ($vData['product_feature_value']['value'] ?? 'N/A');
                echo "  Value: {$vName}\n";
            } catch (\Exception $e) {
                echo "  Value: ERROR\n";
            }
            echo "\n";
        }
        echo "Total features: " . count($features) . "\n";
    } else {
        echo "NO FEATURES FOUND!\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
