<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\VehicleCompatibility;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\PrestaShop\VehicleCompatibilitySyncService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

$productId = 11190;
$psProductId = 447;
$shopId = 1;

echo "=== TEST DIRECT IMPORT ===\n\n";

// Get product
$product = Product::find($productId);
$shop = PrestaShopShop::find($shopId);
$client = PrestaShopClientFactory::create($shop);

echo "1. Product: {$product->name} (SKU: {$product->sku})\n";
echo "   Type: {$product->productType?->slug}\n\n";

// Get PS product data
$psData = $client->getProduct($psProductId);
$psData = $psData['product'] ?? $psData;

echo "2. Features from PrestaShop:\n";
$features = $psData['associations']['product_features'] ?? [];
echo "   Count: " . count($features) . "\n";
foreach ($features as $f) {
    $marker = in_array((int)$f['id'], [431, 432, 433]) ? ' <-- COMPAT' : '';
    echo "   Feature {$f['id']} -> value {$f['id_feature_value']}{$marker}\n";
}

// Check existing mappings
echo "\n3. Existing mappings in vehicle_feature_value_mappings:\n";
$mappings = DB::table('vehicle_feature_value_mappings')
    ->where('shop_id', $shopId)
    ->get();
echo "   Total mappings: " . count($mappings) . "\n";

$compatValueIds = [2304, 2314]; // From PS
foreach ($compatValueIds as $vid) {
    $mapping = $mappings->firstWhere('prestashop_feature_value_id', $vid);
    if ($mapping) {
        echo "   Value {$vid} -> Vehicle ID: {$mapping->vehicle_product_id}\n";
    } else {
        echo "   Value {$vid} -> NO MAPPING\n";
    }
}

// Try to import manually
echo "\n4. Calling importFromPrestaShopFeatures:\n";
$service = app(VehicleCompatibilitySyncService::class);
$service->setShop($shop);
$service->setClient($client);

// Delete existing to test fresh import
VehicleCompatibility::where('product_id', $productId)->delete();
echo "   Cleared existing compatibilities\n";

try {
    $imported = $service->importFromPrestaShopFeatures($psData, $product, $shopId);
    echo "   Imported: " . $imported->count() . " compatibilities\n";
    
    foreach ($imported as $c) {
        echo "   - Vehicle: {$c->vehicle_model_id}, Attr: {$c->compatibility_attribute_id}\n";
    }
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// Check final state
echo "\n5. Final VehicleCompatibility count:\n";
$final = VehicleCompatibility::where('product_id', $productId)->count();
echo "   Count: {$final}\n";

echo "\n=== TEST COMPLETE ===\n";
