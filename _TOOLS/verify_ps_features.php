<?php
/**
 * Verify PrestaShop product features after sync
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopClientFactory;

$psProductId = 7566; // MRF13-68-003 in PrestaShop
$shopId = 1;

echo "=== Verify PrestaShop Features ===\n\n";

$shop = PrestaShopShop::find($shopId);
$client = PrestaShopClientFactory::create($shop);

echo "Fetching product {$psProductId} from {$shop->name}...\n\n";

try {
    $product = $client->getProduct($psProductId);

    $features = $product['product']['associations']['product_features'] ?? [];

    echo "Product: {$product['product']['reference']}\n";
    echo "Features count: " . count($features) . "\n\n";

    $compatFeatures = [431 => 'Oryginal', 432 => 'Model', 433 => 'Zamiennik'];

    echo "=== Compatibility Features ===\n";
    foreach ($features as $f) {
        $featureId = (int) $f['id'];
        if (isset($compatFeatures[$featureId])) {
            $featureName = $compatFeatures[$featureId];
            $valueId = $f['id_feature_value'] ?? 'N/A';
            echo "  {$featureName} (ID: {$featureId}): value_id = {$valueId}\n";
        }
    }

    echo "\n=== All Features ===\n";
    foreach ($features as $f) {
        echo "  Feature {$f['id']} -> value {$f['id_feature_value']}\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Done ===\n";
