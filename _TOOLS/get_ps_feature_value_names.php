<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopClientFactory;

$shopId = 1;
$featureValueIds = [2304, 2314];

$shop = PrestaShopShop::find($shopId);
$client = PrestaShopClientFactory::create($shop);

echo "=== GET PS FEATURE VALUE NAMES ===\n\n";

// Check available methods on client
echo "1. Available methods on client:\n";
$methods = get_class_methods($client);
$featureMethods = array_filter($methods, fn($m) => stripos($m, 'feature') !== false);
echo "   Feature-related: " . implode(', ', $featureMethods) . "\n\n";

// Try to get feature values
echo "2. Getting feature values:\n";

foreach ($featureValueIds as $valueId) {
    echo "\n   Value ID: {$valueId}\n";
    
    // Try API request directly
    try {
        // The method name might be getProductFeatureValues (plural) with filter
        $response = $client->getProductFeatureValues([
            'filter[id]' => $valueId,
            'display' => 'full',
        ]);
        
        echo "   Response structure: " . json_encode(array_keys($response), JSON_PRETTY_PRINT) . "\n";
        
        // Extract value
        $values = $response['product_feature_values']['product_feature_value'] ?? 
                  $response['product_feature_values'] ?? 
                  $response ?? [];
                  
        if (isset($values['id'])) {
            $values = [$values];
        }
        
        foreach ($values as $v) {
            $name = $v['value']['language'][0]['value'] ?? 
                    $v['value']['language']['value'] ?? 
                    $v['value'] ?? 'N/A';
            echo "   Name: {$name}\n";
            echo "   Feature ID: " . ($v['id_feature'] ?? 'N/A') . "\n";
        }
        
    } catch (Exception $e) {
        echo "   ERROR: " . $e->getMessage() . "\n";
    }
}

echo "\n=== DONE ===\n";
