<?php
// Debug API URL building

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$shop = \App\Models\PrestaShopShop::find(1);
echo "=== SHOP DATA ===\n";
echo "ID: {$shop->id}\n";
echo "Name: {$shop->name}\n";
echo "URL: " . ($shop->url ?: 'EMPTY') . "\n";
echo "API Key: " . ($shop->api_key ? substr($shop->api_key, 0, 10) . '...' : 'EMPTY') . "\n";
echo "PS Version: {$shop->prestashop_version}\n\n";

// Build URL manually
$baseUrl = rtrim($shop->url, '/');
$basePath = '/api';
$endpoint = 'products?limit=1';

$fullUrl = "{$baseUrl}{$basePath}/{$endpoint}";
echo "=== BUILT URL ===\n";
echo "Full URL: {$fullUrl}\n\n";

// Try direct HTTP request
echo "=== TESTING DIRECT HTTP ===\n";
$testUrl = "{$baseUrl}{$basePath}/products/9722?output_format=JSON";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_USERPWD, $shop->api_key . ':');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

echo "Testing: {$testUrl}\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
if ($error) {
    echo "cURL Error: {$error}\n";
}
if ($httpCode == 200) {
    $data = json_decode($response, true);
    echo "Response: " . (is_array($data) ? json_encode(array_keys($data)) : substr($response, 0, 200)) . "\n";
} else {
    echo "Response: " . substr($response, 0, 500) . "\n";
}
