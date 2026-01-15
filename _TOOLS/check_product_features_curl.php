<?php
// Check PrestaShop product features using direct curl

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$shop = \App\Models\PrestaShopShop::find(1);
$productId = 9722;

$baseUrl = rtrim($shop->url, '/') . '/api';

echo "=== FETCHING PRODUCT {$productId} ===\n\n";

// Fetch product with full display
$url = "{$baseUrl}/products/{$productId}?display=full&output_format=JSON";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_USERPWD, $shop->api_key . ':');
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 200) {
    echo "HTTP Error: {$httpCode}\n";
    exit;
}

$data = json_decode($response, true);
if (!$data) {
    echo "JSON decode error\n";
    echo "Raw response: " . substr($response, 0, 500) . "\n";
    exit;
}
echo "Response keys: " . implode(', ', array_keys($data)) . "\n";
$product = $data['product'] ?? ($data['products'][0] ?? null);
if (!$product) {
    echo "No product found in response\n";
    print_r(array_slice($data, 0, 2));
    exit;
}

echo "Reference: " . ($product['reference'] ?? 'N/A') . "\n";

// Get name
$name = $product['name'];
if (is_array($name)) {
    $name = $name[0]['value'] ?? ($name[1]['value'] ?? 'N/A');
}
echo "Name: {$name}\n\n";

echo "=== PRODUCT FEATURES (associations) ===\n";
$rawFeatures = $product['associations']['product_features'] ?? null;
if ($rawFeatures) {
    // Handle both formats
    $features = isset($rawFeatures['product_feature']) ? $rawFeatures['product_feature'] : $rawFeatures;
    // Normalize to array
    if (isset($features['id'])) {
        $features = [$features];
    }
    // Remove empty features
    $features = array_filter($features, fn($f) => !empty($f['id']));

    echo "Found " . count($features) . " features:\n\n";

    foreach ($features as $f) {
        $featureId = $f['id'] ?? 'N/A';
        $featureValueId = $f['id_feature_value'] ?? 'N/A';

        echo "Feature ID: {$featureId}, Feature Value ID: {$featureValueId}\n";

        // Get feature name
        $fUrl = "{$baseUrl}/product_features/{$featureId}?output_format=JSON";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $shop->api_key . ':');
        $fResponse = curl_exec($ch);
        $fCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($fCode == 200) {
            $fData = json_decode($fResponse, true);
            $fName = $fData['product_feature']['name'];
            if (is_array($fName)) {
                $fName = $fName[0]['value'] ?? ($fName[1]['value'] ?? 'N/A');
            }
            echo "  Feature Name: {$fName}\n";
        } else {
            echo "  Feature Name: ERROR ({$fCode})\n";
        }

        // Get feature value
        $vUrl = "{$baseUrl}/product_feature_values/{$featureValueId}?output_format=JSON";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $vUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $shop->api_key . ':');
        $vResponse = curl_exec($ch);
        $vCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($vCode == 200) {
            $vData = json_decode($vResponse, true);
            $vName = $vData['product_feature_value']['value'];
            if (is_array($vName)) {
                $vName = $vName[0]['value'] ?? ($vName[1]['value'] ?? 'N/A');
            }
            echo "  Value: {$vName}\n";
        } else {
            echo "  Value: ERROR ({$vCode})\n";
        }
        echo "\n";
    }
} else {
    echo "NO PRODUCT FEATURES!\n";
    echo "Associations structure:\n";
    print_r(array_keys($product['associations'] ?? []));
}
