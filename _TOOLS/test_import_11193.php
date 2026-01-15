<?php
/**
 * Test import compatibility for product 11193
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\VehicleCompatibility;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\PrestaShop\VehicleCompatibilitySyncService;
use Illuminate\Support\Facades\DB;

$productId = 11193;
$shopId = 1;

echo "=== TEST IMPORT COMPATIBILITY FOR PRODUCT 11193 ===\n\n";

// Get product
$product = Product::find($productId);
$shop = PrestaShopShop::find($shopId);

echo "1. Product: {$product->name} (SKU: {$product->sku})\n";
echo "   Type: {$product->productType?->slug}\n\n";

// Get PS product ID
$shopData = DB::table('product_shop_data')
    ->where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

$psProductId = $shopData->prestashop_product_id;
echo "2. PrestaShop Product ID: {$psProductId}\n\n";

// Get PS product data
$client = PrestaShopClientFactory::create($shop);
$psData = $client->getProduct($psProductId);
$psData = $psData['product'] ?? $psData;

// Count features
$features = $psData['associations']['product_features'] ?? [];
$compatFeatures = array_filter($features, fn($f) => in_array((int)$f['id'], [431, 433]));
echo "3. Compatibility Features (Oryginał + Zamiennik only): " . count($compatFeatures) . "\n\n";

// Show available vehicles
echo "4. Available vehicles in PPM:\n";
$vehicles = Product::where('product_type_id', 1)->get(['id', 'name', 'sku']);
foreach ($vehicles as $v) {
    echo "   - {$v->sku}: {$v->name}\n";
}

// Clear existing compatibilities
echo "\n5. Clearing existing compatibilities...\n";
VehicleCompatibility::where('product_id', $productId)->delete();

// Run import
echo "\n6. Running importFromPrestaShopFeatures...\n";
$service = app(VehicleCompatibilitySyncService::class);
$service->setShop($shop);
$service->setClient($client);

$imported = $service->importFromPrestaShopFeatures($psData, $product, $shopId);

echo "\n7. RESULT:\n";
echo "   Imported: " . $imported->count() . " compatibilities\n";

if ($imported->count() > 0) {
    echo "\n   Imported vehicles:\n";
    foreach ($imported as $c) {
        $vname = $c->vehicleProduct ? $c->vehicleProduct->name : 'Unknown';
        $aname = $c->compatibilityAttribute ? $c->compatibilityAttribute->name : 'Unknown';
        $vsku = $c->vehicleProduct ? $c->vehicleProduct->sku : 'N/A';
        echo "   - [{$aname}] {$vname} (SKU: {$vsku})\n";
    }
}

echo "\n=== DONE ===\n";
