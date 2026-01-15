<?php
// List PrestaShop products with 101044 prefix

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$shop = \App\Models\PrestaShopShop::find(1);
$baseUrl = rtrim($shop->url, '/') . '/api';

echo "=== SEARCHING FOR PRODUCTS 101044-* ===\n\n";

// Category 2275 = Buggy S70 from PS
$url = "{$baseUrl}/products?display=[id,reference,name]&filter[id_category_default]=[2275]&output_format=JSON";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_USERPWD, $shop->api_key . ':');
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
$products = $data['products'] ?? [];

echo "Found " . count($products) . " products:\n\n";

foreach ($products as $p) {
    $name = is_array($p['name']) ? ($p['name'][0]['value'] ?? 'N/A') : ($p['name'] ?? 'N/A');

    // Check if exists in PPM
    $ppmProduct = \App\Models\Product::where('sku', $p['reference'])->first();
    $existsInPPM = $ppmProduct ? "EXISTS (ID: {$ppmProduct->id})" : "NEW";

    echo "[PS:{$p['id']}] {$p['reference']}: {$name} - {$existsInPPM}\n";
}
