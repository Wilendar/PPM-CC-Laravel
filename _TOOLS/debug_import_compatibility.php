<?php
/**
 * Debug import compatibility from PrestaShop for product 11190
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

$productId = 11190;
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
echo "   - Type ID: {$product->product_type_id}\n";

// 2. Get external ID (PrestaShop product ID)
$shopProduct = DB::table('prestashop_shop_products')
    ->where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

if (!$shopProduct) {
    echo "\n2. ERROR: No shop product mapping found for shop {$shopId}\n";
    exit(1);
}

echo "\n2. Shop Product Mapping:\n";
echo "   - External ID (PS): {$shopProduct->external_id}\n";
echo "   - Shop ID: {$shopProduct->shop_id}\n";

// 3. Get product from PrestaShop API
$shop = PrestaShopShop::find($shopId);
$client = PrestaShopClientFactory::create($shop);

echo "\n3. Fetching product from PrestaShop API...\n";
try {
    $psProduct = $client->getProduct($shopProduct->external_id);
    
    echo "   - Product Reference: " . ($psProduct['product']['reference'] ?? 'N/A') . "\n";
    echo "   - Product Name: " . ($psProduct['product']['name']['language'][0]['value'] ?? 'N/A') . "\n";
    
    // 4. Check associations/product_features
    $features = $psProduct['product']['associations']['product_features'] ?? [];
    
    echo "\n4. Product Features from PrestaShop:\n";
    echo "   - Total features count: " . count($features) . "\n";
    
    $compatFeatureIds = [431, 432, 433];
    $compatFeatures = [];
    
    foreach ($features as $f) {
        $featureId = (int) ($f['id'] ?? 0);
        $featureValueId = (int) ($f['id_feature_value'] ?? 0);
        
        echo "   * Feature ID: {$featureId}, Value ID: {$featureValueId}\n";
        
        if (in_array($featureId, $compatFeatureIds)) {
            $compatFeatures[] = $f;
        }
    }
    
    echo "\n5. Compatibility Features (431/432/433):\n";
    echo "   - Found: " . count($compatFeatures) . " compatibility features\n";
    
    foreach ($compatFeatures as $cf) {
        $featureId = $cf['id'];
        $featureValueId = $cf['id_feature_value'];
        
        $featureNames = [431 => 'Oryginal', 432 => 'Model', 433 => 'Zamiennik'];
        $name = $featureNames[$featureId] ?? 'Unknown';
        
        echo "   * {$name} (Feature {$featureId}): value_id = {$featureValueId}\n";
        
        // Try to get feature value name
        try {
            $featureValue = $client->getFeatureValue($featureValueId);
            $valueName = $featureValue['product_feature_value']['value']['language'][0]['value'] ?? 'N/A';
            echo "     -> Value Name: {$valueName}\n";
        } catch (Exception $e) {
            echo "     -> Could not get value name: " . $e->getMessage() . "\n";
        }
    }
    
    // 6. Check existing mappings
    echo "\n6. Checking vehicle_feature_value_mappings table:\n";
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
