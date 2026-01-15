<?php
/**
 * Test import of KAYO product with compatibility features
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
use Illuminate\Support\Facades\Log;

$shopId = 1;
$psProductId = 1903; // Pokrywka zaworów głowicy dirt bike 250-69 Kayo

echo "=== TEST KAYO IMPORT ===\n\n";

$shop = PrestaShopShop::find($shopId);
$client = PrestaShopClientFactory::create($shop);

// 1. Get PS product
echo "1. Fetching PS Product {$psProductId}...\n";
$psData = $client->getProduct($psProductId);
$psData = $psData['product'] ?? $psData;

$name = is_array($psData['name'])
    ? ($psData['name']['language'][0]['value'] ?? $psData['name']['language']['value'] ?? 'N/A')
    : ($psData['name'] ?? 'N/A');

echo "   Name: {$name}\n";
echo "   Reference: {$psData['reference']}\n\n";

// 2. Check compatibility features
$features = $psData['associations']['product_features'] ?? [];
if (isset($features['product_feature'])) {
    $pf = $features['product_feature'];
    if (isset($pf['id'])) {
        $features = [$pf];
    } else {
        $features = $pf;
    }
}

$compatFeatures = array_filter($features, fn($f) => in_array((int)$f['id'], [431, 433]));

echo "2. Compatibility features:\n";
$featureNames = [431 => 'Oryginal', 433 => 'Zamiennik'];

foreach ($compatFeatures as $cf) {
    $fid = (int) $cf['id'];
    $fvid = (int) $cf['id_feature_value'];
    $fname = $featureNames[$fid] ?? 'Unknown';

    $fvResponse = $client->getProductFeatureValue($fvid);
    $fvData = $fvResponse['product_feature_value'] ?? $fvResponse;
    $valueField = $fvData['value'] ?? [];

    $valueName = 'N/A';
    if (is_string($valueField)) {
        $valueName = $valueField;
    } elseif (is_array($valueField)) {
        if (isset($valueField['language'])) {
            $lang = $valueField['language'];
            if (is_array($lang)) {
                $valueName = $lang[0]['value'] ?? $lang['value'] ?? 'N/A';
            } else {
                $valueName = $lang;
            }
        }
    }

    echo "   [{$fname}] Value ID {$fvid} = \"{$valueName}\"\n";
}

// 3. Show PPM vehicles
echo "\n3. Available vehicles in PPM:\n";
$vehicles = Product::where('product_type_id', 1)->get(['id', 'name', 'sku']);
foreach ($vehicles as $v) {
    echo "   - ID: {$v->id} | SKU: {$v->sku} | {$v->name}\n";
}

// 4. Find/create PPM product
$reference = $psData['reference'] ?? '';
echo "\n4. Checking PPM for SKU: {$reference}...\n";

$ppmProduct = Product::where('sku', $reference)->first();

if (!$ppmProduct) {
    echo "   Creating new PPM product...\n";
    $ppmProduct = Product::create([
        'name' => $name,
        'sku' => $reference,
        'product_type_id' => 2,
        'status' => 'active',
    ]);

    DB::table('product_shop_data')->insert([
        'product_id' => $ppmProduct->id,
        'shop_id' => $shopId,
        'prestashop_product_id' => $psProductId,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "   Created PPM Product ID: {$ppmProduct->id}\n";
} else {
    echo "   Found PPM Product ID: {$ppmProduct->id}\n";
    DB::table('product_shop_data')->updateOrInsert(
        ['product_id' => $ppmProduct->id, 'shop_id' => $shopId],
        ['prestashop_product_id' => $psProductId, 'updated_at' => now()]
    );
}

// 5. Clear existing
echo "\n5. Clearing existing compatibilities...\n";
VehicleCompatibility::where('product_id', $ppmProduct->id)->delete();

// 6. Run import
echo "\n6. Running import...\n";

$service = app(VehicleCompatibilitySyncService::class);
$service->setShop($shop);
$service->setClient($client);

$imported = $service->importFromPrestaShopFeatures($psData, $ppmProduct, $shopId);

echo "\n7. RESULT: Imported {$imported->count()} compatibilities\n";

if ($imported->count() > 0) {
    echo "\n   Imported:\n";
    foreach ($imported as $c) {
        $vname = $c->vehicleProduct ? $c->vehicleProduct->name : 'Unknown';
        $vsku = $c->vehicleProduct ? $c->vehicleProduct->sku : 'N/A';
        $aname = $c->compatibilityAttribute ? $c->compatibilityAttribute->name : 'Unknown';
        echo "   - [{$aname}] {$vname} (SKU: {$vsku})\n";
    }
} else {
    echo "\n   No compatibilities imported - checking logs...\n";
}

echo "\n=== CHECK LOGS ===\n";
echo "Run: grep 'COMPAT SYNC' storage/logs/laravel.log | tail -50\n";

echo "\n=== DONE ===\n";
