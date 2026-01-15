<?php
/**
 * Debug import compatibility for product 11193
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\VehicleCompatibility;
use App\Services\PrestaShop\PrestaShopClientFactory;
use Illuminate\Support\Facades\DB;

$productId = 11193;
$shopId = 1;

echo "=== DEBUG IMPORT COMPATIBILITY FOR PRODUCT 11193 ===\n\n";

// 1. Get product info
$product = Product::find($productId);
if (!$product) {
    echo "ERROR: Product {$productId} not found\n";
    exit(1);
}

echo "1. Product Info:\n";
echo "   - ID: {$product->id}\n";
echo "   - Name: {$product->name}\n";
echo "   - SKU: {$product->sku}\n";
echo "   - Type: " . ($product->productType?->slug ?? 'N/A') . "\n";

// 2. Get shop mapping
$shopProduct = DB::table('product_shop_data')
    ->where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

if (!$shopProduct) {
    echo "\n2. ERROR: No shop product mapping for shop {$shopId}\n";
    $anyShop = DB::table('product_shop_data')->where('product_id', $productId)->get();
    echo "   Available mappings:\n";
    foreach ($anyShop as $s) {
        echo "   - Shop {$s->shop_id}: PS ID " . ($s->prestashop_product_id ?? 'N/A') . "\n";
    }
    exit(1);
}

$psProductId = $shopProduct->prestashop_product_id ?? null;
echo "\n2. Shop Mapping:\n";
echo "   - Shop ID: {$shopProduct->shop_id}\n";
echo "   - PrestaShop Product ID: " . ($psProductId ?? 'NOT FOUND') . "\n";

if (!$psProductId) {
    echo "   ERROR: No prestashop_product_id!\n";
    exit(1);
}

// 3. Get PS product data
$shop = PrestaShopShop::find($shopId);
$client = PrestaShopClientFactory::create($shop);

echo "\n3. Fetching PS Product {$psProductId}...\n";
try {
    $psProduct = $client->getProduct($psProductId);
    $psData = $psProduct['product'] ?? $psProduct;

    $ref = $psData['reference'] ?? 'N/A';
    echo "   - Reference: {$ref}\n";

    // 4. Get associations/features
    $associations = $psData['associations'] ?? [];
    $featuresData = $associations['product_features'] ?? [];

    // Handle different structures
    $features = [];
    if (isset($featuresData['product_feature'])) {
        $pf = $featuresData['product_feature'];
        if (isset($pf['id'])) {
            $features = [$pf];
        } else {
            $features = $pf;
        }
    } elseif (is_array($featuresData) && !empty($featuresData)) {
        $features = $featuresData;
    }

    echo "\n4. Product Features:\n";
    echo "   - Total features: " . count($features) . "\n";

    if (empty($features)) {
        echo "   WARNING: NO FEATURES FOUND!\n";
        echo "   Raw associations: " . json_encode($associations, JSON_PRETTY_PRINT) . "\n";
        exit(0);
    }

    // Filter compatibility features (431, 432, 433)
    $compatFeatureIds = [431, 432, 433];
    $featureNames = [431 => 'Oryginal', 432 => 'Model', 433 => 'Zamiennik'];

    $compatFeatures = [];
    $otherFeatures = [];

    foreach ($features as $f) {
        $featureId = (int) ($f['id'] ?? 0);
        if (in_array($featureId, $compatFeatureIds)) {
            $compatFeatures[] = $f;
        } else {
            $otherFeatures[] = $f;
        }
    }

    echo "\n5. Compatibility Features (431/432/433):\n";
    echo "   - Found: " . count($compatFeatures) . " compatibility features\n";

    if (empty($compatFeatures)) {
        echo "\n   === DIAGNOSIS: PRODUCT HAS NO COMPATIBILITY FEATURES ===\n";
        echo "   This product has no Oryginal/Model/Zamiennik attributes in PrestaShop.\n";
        echo "   Other features found: " . count($otherFeatures) . "\n";

        if (count($otherFeatures) > 0) {
            echo "\n   Other feature IDs:\n";
            foreach (array_slice($otherFeatures, 0, 10) as $of) {
                echo "   - Feature ID: " . ($of['id'] ?? 'N/A') . ", Value ID: " . ($of['id_feature_value'] ?? 'N/A') . "\n";
            }
        }
        exit(0);
    }

    // 6. Analyze each compatibility feature
    echo "\n6. Compatibility Feature Details:\n";

    // Only process 431 (Oryginal) and 433 (Zamiennik) - 432 is computed
    $importableFeatures = array_filter($compatFeatures, fn($f) => in_array((int)$f['id'], [431, 433]));
    echo "   - Importable (431+433 only): " . count($importableFeatures) . "\n\n";

    foreach ($importableFeatures as $cf) {
        $featureId = (int) $cf['id'];
        $featureValueId = (int) $cf['id_feature_value'];
        $fName = $featureNames[$featureId] ?? 'Unknown';

        echo "   [{$fName}] Feature {$featureId}, Value ID: {$featureValueId}\n";

        // Get feature value name
        try {
            $fvResponse = $client->getProductFeatureValue($featureValueId);
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

            echo "      PS Name: {$valueName}\n";

            // Check mapping table
            $mapping = DB::table('vehicle_feature_value_mappings')
                ->where('prestashop_feature_value_id', $featureValueId)
                ->where('shop_id', $shopId)
                ->first();

            if ($mapping) {
                echo "      Mapping: Found -> Vehicle ID {$mapping->vehicle_product_id}\n";
            } else {
                echo "      Mapping: NOT FOUND\n";

                // Try flexible match
                $vehicles = Product::where('product_type_id', 1)->get(['id', 'name', 'sku']);
                $matched = null;

                // Try exact match
                $matched = $vehicles->first(fn($v) =>
                    stripos($v->name, $valueName) !== false ||
                    stripos($v->sku, $valueName) !== false
                );

                if ($matched) {
                    echo "      Flexible Match: {$matched->name} (ID: {$matched->id})\n";
                } else {
                    // Extract keywords and try
                    $keywords = array_filter(
                        preg_split('/[\s\-_]+/', strtoupper($valueName)),
                        fn($w) => strlen($w) >= 2
                    );
                    echo "      Keywords: " . implode(', ', $keywords) . "\n";
                    echo "      Flexible Match: NOT FOUND\n";
                }
            }

        } catch (Exception $e) {
            echo "      ERROR: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    // 7. Show existing VehicleCompatibility records
    echo "7. Current VehicleCompatibility records:\n";
    $compats = VehicleCompatibility::where('product_id', $productId)->get();
    echo "   - Count: " . count($compats) . "\n";

    foreach ($compats as $c) {
        $vname = $c->vehicleProduct ? $c->vehicleProduct->name : 'Unknown';
        $aname = $c->compatibilityAttribute ? $c->compatibilityAttribute->name : 'Unknown';
        echo "   - {$aname}: {$vname}\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
