<?php
/**
 * Debug import compatibility from PrestaShop for product 11190
 * Run on PRODUCTION server
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

$productId = 11191;
$shopId = 1;

echo "=== DEBUG IMPORT COMPATIBILITY ===\n\n";

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

// 2. Get external ID (PrestaShop product ID) - check product_shop_data table
$shopProduct = DB::table('product_shop_data')
    ->where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

if (!$shopProduct) {
    echo "\n2. ERROR: No shop product mapping found for shop {$shopId}\n";
    echo "   Checking if product_shop_data has any records for this product...\n";
    $anyShop = DB::table('product_shop_data')->where('product_id', $productId)->get();
    foreach ($anyShop as $s) {
        echo "   - Found shop_id: {$s->shop_id}, external_id: " . ($s->external_id ?? $s->prestashop_id ?? 'N/A') . "\n";
    }
    exit(1);
}

// The column is prestashop_product_id
$externalId = $shopProduct->prestashop_product_id ?? null;
echo "\n2. Shop Product Mapping:\n";
echo "   - Shop ID: {$shopProduct->shop_id}\n";
echo "   - PrestaShop Product ID: " . ($externalId ?? 'NOT FOUND') . "\n";

if (!$externalId) {
    echo "   - Could not determine external_id\n";
    exit(1);
}

// 3. Get product from PrestaShop API
$shop = PrestaShopShop::find($shopId);
$client = PrestaShopClientFactory::create($shop);

echo "\n3. Fetching product {$externalId} from PrestaShop API...\n";
try {
    $psProduct = $client->getProduct($externalId);

    $ref = $psProduct['product']['reference'] ?? 'N/A';
    $nameData = $psProduct['product']['name'] ?? [];
    $name = 'N/A';
    if (is_array($nameData)) {
        if (isset($nameData['language'])) {
            $lang = $nameData['language'];
            if (is_array($lang)) {
                $name = $lang[0]['value'] ?? $lang['value'] ?? 'N/A';
            } else {
                $name = $lang;
            }
        }
    } elseif (is_string($nameData)) {
        $name = $nameData;
    }

    echo "   - Product Reference: {$ref}\n";
    echo "   - Product Name: {$name}\n";

    // 4. Check associations/product_features
    $associations = $psProduct['product']['associations'] ?? [];
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

    echo "\n4. Product Features from PrestaShop:\n";
    echo "   - Total features count: " . count($features) . "\n";

    if (empty($features)) {
        echo "   - WARNING: No features found in product data!\n";
        echo "   - Raw associations data: " . json_encode($associations, JSON_PRETTY_PRINT) . "\n";
    }

    $compatFeatureIds = [431, 432, 433];
    $compatFeatures = [];

    foreach ($features as $f) {
        $featureId = (int) ($f['id'] ?? 0);
        $featureValueId = (int) ($f['id_feature_value'] ?? 0);

        $featureNames = [431 => 'Oryginal', 432 => 'Model', 433 => 'Zamiennik'];
        $fName = $featureNames[$featureId] ?? '';
        $marker = $fName ? " <-- COMPATIBILITY!" : "";

        echo "   * Feature ID: {$featureId}, Value ID: {$featureValueId}{$marker}\n";

        if (in_array($featureId, $compatFeatureIds)) {
            $compatFeatures[] = $f;
        }
    }

    echo "\n5. Compatibility Features (431/432/433):\n";
    echo "   - Found: " . count($compatFeatures) . " compatibility features\n";

    foreach ($compatFeatures as $cf) {
        $featureId = (int) $cf['id'];
        $featureValueId = (int) $cf['id_feature_value'];

        $featureNames = [431 => 'Oryginal', 432 => 'Model', 433 => 'Zamiennik'];
        $name = $featureNames[$featureId] ?? 'Unknown';

        echo "   * {$name} (Feature {$featureId}): value_id = {$featureValueId}\n";

        // Try to get feature value name
        try {
            $featureValue = $client->getFeatureValue($featureValueId);
            $valueData = $featureValue['product_feature_value']['value'] ?? [];
            $valueName = 'N/A';
            if (is_array($valueData)) {
                if (isset($valueData['language'])) {
                    $lang = $valueData['language'];
                    if (is_array($lang)) {
                        $valueName = $lang[0]['value'] ?? $lang['value'] ?? 'N/A';
                    } else {
                        $valueName = $lang;
                    }
                }
            } elseif (is_string($valueData)) {
                $valueName = $valueData;
            }
            echo "     -> Value Name: {$valueName}\n";
        } catch (Exception $e) {
            echo "     -> Could not get value name: " . $e->getMessage() . "\n";
        }
    }

    // 6. Check existing mappings
    echo "\n6. Checking vehicle_feature_value_mappings table:\n";
    try {
        $mappings = DB::table('vehicle_feature_value_mappings')
            ->where('shop_id', $shopId)
            ->get();

        echo "   - Total mappings for shop {$shopId}: " . count($mappings) . "\n";

        foreach ($compatFeatures as $cf) {
            $featureValueId = (int) $cf['id_feature_value'];
            $mapping = $mappings->firstWhere('prestashop_feature_value_id', $featureValueId);

            if ($mapping) {
                echo "   * Value {$featureValueId} -> Vehicle Product ID: {$mapping->vehicle_product_id}\n";
            } else {
                echo "   * Value {$featureValueId} -> NO MAPPING FOUND!\n";
            }
        }
    } catch (Exception $e) {
        echo "   - ERROR checking mappings: " . $e->getMessage() . "\n";
    }

    // 7. Check current VehicleCompatibility records
    echo "\n7. Current VehicleCompatibility records for product {$productId}:\n";
    $compats = VehicleCompatibility::where('product_id', $productId)->get();
    echo "   - Count: " . count($compats) . "\n";

    foreach ($compats as $c) {
        echo "   * ID: {$c->id}, Vehicle: {$c->vehicle_model_id}, Attr: {$c->compatibility_attribute_id}\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
