<?php
// Check multiple SKUs across shops

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$skusToCheck = [
    '101044-0047', // This one worked earlier
    '101044-0051',
    '101044-0048',
    '101044-0050',
    '907001-0033',
    '904002-0006-02',
];

$shops = \App\Models\PrestaShopShop::all();

echo "=== CHECKING SKUs ACROSS ALL SHOPS ===\n\n";

foreach ($shops as $shop) {
    echo "=== SHOP: {$shop->name} (ID: {$shop->id}) ===\n";

    if (!$shop->url || !$shop->api_key) {
        echo "  SKIP - No API credentials (url or api_key missing)\n\n";
        continue;
    }
    echo "  URL: {$shop->url}\n";

    try {
        $clientClass = $shop->prestashop_version == '9'
            ? \App\Services\PrestaShop\PrestaShop9Client::class
            : \App\Services\PrestaShop\PrestaShop8Client::class;
        $client = new $clientClass($shop);

        foreach ($skusToCheck as $sku) {
            try {
                $result = $client->makeRequest('GET', "products?filter[reference]={$sku}&display=[id,reference,name]");
                $products = $result['products']['product'] ?? $result['products'] ?? [];

                if (!empty($products)) {
                    $product = is_array($products) && isset($products[0]) ? $products[0] : $products;
                    $name = is_array($product['name']) ? ($product['name'][0]['value'] ?? 'N/A') : ($product['name'] ?? 'N/A');
                    echo "  FOUND: {$sku} (ID: {$product['id']}) - {$name}\n";

                    // Check if it has features
                    $fullProduct = $client->makeRequest('GET', "products/{$product['id']}?display=full");
                    $hasFeatures = isset($fullProduct['product']['associations']['product_features']['product_feature']);
                    echo "    HAS FEATURES: " . ($hasFeatures ? "YES" : "NO") . "\n";

                    if ($hasFeatures) {
                        $features = $fullProduct['product']['associations']['product_features']['product_feature'];
                        if (isset($features['id'])) $features = [$features];
                        echo "    Features count: " . count($features) . "\n";
                    }
                }
            } catch (\Exception $e) {
                // SKU not found in this shop - skip silently
            }
        }
    } catch (\Exception $e) {
        echo "  ERROR: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

echo "=== DONE ===\n";
