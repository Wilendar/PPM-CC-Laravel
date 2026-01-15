<?php
// Test VehicleCompatibilitySyncService import manually

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\VehicleCompatibilitySyncService;
use App\Services\PrestaShop\PrestaShop8Client;

$productId = 11198;
$shopId = 1;
$psProductId = 9722;

$product = Product::find($productId);
$shop = PrestaShopShop::find($shopId);

echo "=== TESTING COMPATIBILITY IMPORT ===\n\n";
echo "Product: {$product->name} (ID: {$product->id})\n";
echo "Shop: {$shop->name} (ID: {$shop->id})\n\n";

// Create client
$client = new PrestaShop8Client($shop);

// Fetch PS product data
echo "=== FETCHING PS PRODUCT DATA ===\n";
try {
    $psData = $client->getProduct($psProductId);
    echo "Got PS data. Keys: " . implode(', ', array_keys($psData)) . "\n";

    // Check structure
    if (isset($psData['product'])) {
        $productData = $psData['product'];
        echo "Using psData['product']\n";
    } else {
        $productData = $psData;
        echo "Using psData directly\n";
    }

    echo "\nProduct data keys: " . implode(', ', array_keys($productData)) . "\n";

    // Check associations
    $associations = $productData['associations'] ?? [];
    echo "\nAssociations keys: " . implode(', ', array_keys($associations)) . "\n";

    // Check product_features
    $features = $associations['product_features'] ?? [];
    echo "\nProduct features:\n";
    print_r($features);

    // Now test the import
    echo "\n=== TESTING IMPORT SERVICE ===\n";
    $compatService = new VehicleCompatibilitySyncService();
    $compatService->setClient($client);
    $compatService->setShop($shop);

    $imported = $compatService->importFromPrestaShopFeatures(
        $productData,
        $product,
        $shop->id
    );

    echo "\nImported count: " . $imported->count() . "\n";
    foreach ($imported as $compat) {
        echo "- Vehicle ID: {$compat->vehicle_model_id}, Type: {$compat->compatibility_attribute_id}\n";
    }

    // Check current compatibilities
    echo "\n=== CURRENT COMPATIBILITIES FOR PRODUCT ===\n";
    $compats = \App\Models\VehicleCompatibility::where('product_id', $productId)->get();
    echo "Total: " . $compats->count() . "\n";
    foreach ($compats as $c) {
        $vehicle = Product::find($c->vehicle_model_id);
        echo "- Vehicle: " . ($vehicle ? $vehicle->name : 'NULL') . " (ID: {$c->vehicle_model_id})\n";
    }

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
