<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopClientFactory;

$psProductId = 447; // Silnik Kayo AU150
$shopId = 1;

$shop = PrestaShopShop::find($shopId);
$client = PrestaShopClientFactory::create($shop);

echo "=== DEBUG PS FEATURES STRUCTURE ===\n\n";

try {
    $psProduct = $client->getProduct($psProductId);
    
    echo "1. Raw product[associations] structure:\n";
    $associations = $psProduct['product']['associations'] ?? [];
    
    echo "   Keys: " . implode(', ', array_keys($associations)) . "\n\n";
    
    echo "2. product_features structure:\n";
    $pf = $associations['product_features'] ?? [];
    echo "   Type: " . gettype($pf) . "\n";
    if (is_array($pf)) {
        echo "   Keys: " . implode(', ', array_keys($pf)) . "\n";
        
        if (isset($pf['product_feature'])) {
            echo "\n3. product_features[product_feature] structure:\n";
            $features = $pf['product_feature'];
            echo "   Type: " . gettype($features) . "\n";
            
            if (is_array($features)) {
                if (isset($features['id'])) {
                    echo "   Single feature object\n";
                    echo "   Feature: " . json_encode($features) . "\n";
                } else {
                    echo "   Array of " . count($features) . " features\n";
                    foreach ($features as $idx => $f) {
                        $marker = in_array((int)$f['id'], [431, 432, 433]) ? ' <-- COMPATIBILITY' : '';
                        echo "   [$idx] id={$f['id']}, value_id={$f['id_feature_value']}{$marker}\n";
                    }
                }
            }
        }
    }
    
    echo "\n4. Full JSON of product_features:\n";
    echo json_encode($pf, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
