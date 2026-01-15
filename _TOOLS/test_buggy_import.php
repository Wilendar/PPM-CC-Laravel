<?php
/**
 * Test import of BUGGY parts from PrestaShop
 * Category 2111 = części buggy
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
$categoryId = 2111; // Części buggy

echo "=== TEST BUGGY IMPORT ===\n\n";

// 1. Get shop and client
$shop = PrestaShopShop::find($shopId);
$client = PrestaShopClientFactory::create($shop);

echo "1. Shop: {$shop->name}\n";
echo "   API URL: {$shop->api_url}\n\n";

// 2. Get products from category 2111
echo "2. Fetching products from PS category {$categoryId}...\n";

try {
    $response = $client->getProducts([
        'filter[id_category_default]' => $categoryId,
        'display' => '[id,reference,name]',
        'limit' => 10,
    ]);

    $products = $response['products']['product'] ?? $response['products'] ?? [];

    // Handle single product
    if (isset($products['id'])) {
        $products = [$products];
    }

    echo "   Found " . count($products) . " products\n\n";

    if (empty($products)) {
        echo "No products found in category {$categoryId}\n";
        exit(1);
    }

    // Show first 5 products
    echo "3. Sample products:\n";
    foreach (array_slice($products, 0, 5) as $p) {
        $name = is_array($p['name']) ? ($p['name']['language'][0]['value'] ?? $p['name']['language']['value'] ?? 'N/A') : $p['name'];
        echo "   - PS ID: {$p['id']} | Ref: {$p['reference']} | Name: {$name}\n";
    }

    // 4. Pick first product for import test
    $testProduct = $products[0];
    $psProductId = $testProduct['id'];
    $testName = is_array($testProduct['name'])
        ? ($testProduct['name']['language'][0]['value'] ?? $testProduct['name']['language']['value'] ?? 'N/A')
        : $testProduct['name'];

    echo "\n4. Testing import for PS Product ID: {$psProductId}\n";
    echo "   Name: {$testName}\n";
    echo "   Reference: {$testProduct['reference']}\n\n";

    // 5. Get full product data
    $psData = $client->getProduct($psProductId);
    $psData = $psData['product'] ?? $psData;

    // 6. Check compatibility features
    $features = $psData['associations']['product_features'] ?? [];

    // Handle nested structure
    if (isset($features['product_feature'])) {
        $pf = $features['product_feature'];
        if (isset($pf['id'])) {
            $features = [$pf];
        } else {
            $features = $pf;
        }
    }

    $compatFeatures = array_filter($features, fn($f) => in_array((int)$f['id'], [431, 433]));

    echo "5. Compatibility features (431/433):\n";
    echo "   Total features: " . count($features) . "\n";
    echo "   Compatibility features: " . count($compatFeatures) . "\n\n";

    if (empty($compatFeatures)) {
        echo "   WARNING: No compatibility features found! Trying another product...\n\n";

        // Try more products
        foreach (array_slice($products, 1, 5) as $p) {
            $pData = $client->getProduct($p['id']);
            $pData = $pData['product'] ?? $pData;
            $pFeatures = $pData['associations']['product_features'] ?? [];

            if (isset($pFeatures['product_feature'])) {
                $pf = $pFeatures['product_feature'];
                if (isset($pf['id'])) {
                    $pFeatures = [$pf];
                } else {
                    $pFeatures = $pf;
                }
            }

            $pCompat = array_filter($pFeatures, fn($f) => in_array((int)$f['id'], [431, 433]));

            if (!empty($pCompat)) {
                $psProductId = $p['id'];
                $psData = $pData;
                $compatFeatures = $pCompat;
                $pName = is_array($p['name'])
                    ? ($p['name']['language'][0]['value'] ?? $p['name']['language']['value'] ?? 'N/A')
                    : $p['name'];
                echo "   Found product with compatibilities: PS ID {$psProductId} - {$pName}\n";
                echo "   Compatibility features: " . count($compatFeatures) . "\n\n";
                break;
            }
        }
    }

    if (empty($compatFeatures)) {
        echo "ERROR: No products with compatibility features found in this category!\n";
        exit(1);
    }

    // 7. Show compatibility feature details
    echo "6. Compatibility feature details:\n";
    $featureNames = [431 => 'Oryginal', 433 => 'Zamiennik'];

    foreach ($compatFeatures as $cf) {
        $fid = (int) $cf['id'];
        $fvid = (int) $cf['id_feature_value'];
        $fname = $featureNames[$fid] ?? 'Unknown';

        // Get value name from PS
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

        echo "   [{$fname}] Feature {$fid}, Value ID: {$fvid} = \"{$valueName}\"\n";
    }

    // 8. Check if product exists in PPM
    $reference = $psData['reference'] ?? '';
    echo "\n7. Checking if product exists in PPM (SKU: {$reference})...\n";

    $ppmProduct = Product::where('sku', $reference)->first();

    if (!$ppmProduct) {
        echo "   Product NOT in PPM. Creating test product...\n";

        // Create minimal test product
        $ppmProduct = Product::create([
            'name' => $testName,
            'sku' => $reference,
            'product_type_id' => 2, // Część zamienna
            'status' => 'active',
        ]);

        // Create shop mapping
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
        echo "   Found existing PPM Product ID: {$ppmProduct->id}\n";

        // Update shop mapping if needed
        DB::table('product_shop_data')->updateOrInsert(
            ['product_id' => $ppmProduct->id, 'shop_id' => $shopId],
            ['prestashop_product_id' => $psProductId, 'updated_at' => now()]
        );
    }

    // 9. Clear existing compatibilities
    echo "\n8. Clearing existing compatibilities for product {$ppmProduct->id}...\n";
    $deleted = VehicleCompatibility::where('product_id', $ppmProduct->id)->delete();
    echo "   Deleted: {$deleted}\n";

    // 10. Run import
    echo "\n9. Running compatibility import...\n";
    Log::info('[TEST BUGGY] Starting import test', [
        'ppm_product_id' => $ppmProduct->id,
        'ps_product_id' => $psProductId,
    ]);

    $service = app(VehicleCompatibilitySyncService::class);
    $service->setShop($shop);
    $service->setClient($client);

    $imported = $service->importFromPrestaShopFeatures($psData, $ppmProduct, $shopId);

    echo "\n10. IMPORT RESULT:\n";
    echo "    Imported: {$imported->count()} compatibilities\n";

    if ($imported->count() > 0) {
        echo "\n    Imported vehicles:\n";
        foreach ($imported as $c) {
            $vname = $c->vehicleProduct ? $c->vehicleProduct->name : 'Unknown';
            $vsku = $c->vehicleProduct ? $c->vehicleProduct->sku : 'N/A';
            $aname = $c->compatibilityAttribute ? $c->compatibilityAttribute->name : 'Unknown';
            echo "    - [{$aname}] {$vname} (SKU: {$vsku})\n";
        }
    }

    // 11. Show available vehicles for reference
    echo "\n11. Available vehicles in PPM:\n";
    $vehicles = Product::where('product_type_id', 1)->get(['id', 'name', 'sku']);
    foreach ($vehicles as $v) {
        echo "    - {$v->sku}: {$v->name}\n";
    }

    echo "\n12. Check latest logs:\n";
    echo "    grep 'COMPAT SYNC' storage/logs/laravel.log | tail -30\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
}

echo "\n=== DONE ===\n";
