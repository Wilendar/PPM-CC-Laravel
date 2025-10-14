<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopClientFactory;

// Test product 9673 from shop 1
$shop = PrestaShopShop::find(1);
$client = PrestaShopClientFactory::create($shop);

echo "=== Testing PrestaShop API Product Response ===" . PHP_EOL;
echo "Shop: {$shop->name} ({$shop->url})" . PHP_EOL;
echo "Product ID: 9673" . PHP_EOL . PHP_EOL;

$data = $client->getProduct(9673);

if (isset($data['product'])) {
    $product = $data['product'];
} else {
    $product = $data;
}

echo "=== KEY FIELDS ===" . PHP_EOL;
echo "ID: " . ($product['id'] ?? 'null') . PHP_EOL;
echo "Link rewrite (raw): " . print_r($product['link_rewrite'] ?? 'null', true) . PHP_EOL;

// Extract link_rewrite properly
if (isset($product['link_rewrite'])) {
    if (is_array($product['link_rewrite'])) {
        if (isset($product['link_rewrite'][0]['value'])) {
            $linkRewrite = $product['link_rewrite'][0]['value'];
        } elseif (isset($product['link_rewrite']['value'])) {
            $linkRewrite = $product['link_rewrite']['value'];
        } else {
            $linkRewrite = $product['link_rewrite'][0] ?? 'array_unknown_structure';
        }
    } else {
        $linkRewrite = $product['link_rewrite'];
    }
    echo "Link rewrite (extracted): {$linkRewrite}" . PHP_EOL;
}

echo PHP_EOL . "=== ALL AVAILABLE KEYS ===" . PHP_EOL;
echo implode(', ', array_keys($product)) . PHP_EOL;

// Check for any URL-related fields
echo PHP_EOL . "=== URL-RELATED FIELDS ===" . PHP_EOL;
foreach ($product as $key => $value) {
    if (stripos($key, 'url') !== false || stripos($key, 'link') !== false) {
        echo "{$key}: " . print_r($value, true) . PHP_EOL;
    }
}
