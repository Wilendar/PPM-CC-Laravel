<?php
/**
 * Analyze feature values from PrestaShop to understand naming patterns
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Services\PrestaShop\PrestaShopClientFactory;

$shopId = 1;
// Sample of feature value IDs from product 11191
$sampleValueIds = [2143, 2144, 2159, 2160, 2219, 2220, 2281, 2282];

$shop = PrestaShopShop::find($shopId);
$client = PrestaShopClientFactory::create($shop);

echo "=== ANALYZE FEATURE VALUES PATTERNS ===\n\n";

echo "1. Sample Feature Values from PrestaShop:\n";
foreach ($sampleValueIds as $valueId) {
    try {
        $response = $client->getProductFeatureValue($valueId);
        $valueData = $response['product_feature_value'] ?? $response;

        $valueName = 'N/A';
        $valueField = $valueData['value'] ?? [];

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

        $featureId = $valueData['id_feature'] ?? 'N/A';
        $featureNames = [431 => 'Oryginal', 432 => 'Model', 433 => 'Zamiennik'];
        $fName = $featureNames[$featureId] ?? 'Unknown';

        echo "   Value ID: {$valueId} (Feature: {$fName})\n";
        echo "   -> Name: {$valueName}\n\n";

    } catch (Exception $e) {
        echo "   Value ID: {$valueId} -> ERROR: {$e->getMessage()}\n\n";
    }
}

echo "2. Search for vehicles in PPM database:\n";
// Get all vehicles
$vehicles = Product::where('product_type_id', 1)->get(['id', 'name', 'sku']);
echo "   Total vehicles in PPM: " . $vehicles->count() . "\n\n";

echo "   First 20 vehicles:\n";
foreach ($vehicles->take(20) as $v) {
    echo "   - ID: {$v->id}, SKU: {$v->sku}, Name: {$v->name}\n";
}

echo "\n3. SKU Pattern Analysis:\n";
// Analyze SKU patterns
$skuPatterns = [];
foreach ($vehicles as $v) {
    $parts = explode('-', $v->sku);
    if (count($parts) > 0) {
        $prefix = $parts[0];
        if (!isset($skuPatterns[$prefix])) {
            $skuPatterns[$prefix] = [];
        }
        $skuPatterns[$prefix][] = $v->sku;
    }
}

foreach ($skuPatterns as $prefix => $skus) {
    echo "   Prefix '{$prefix}': " . count($skus) . " vehicles\n";
    echo "     Examples: " . implode(', ', array_slice($skus, 0, 3)) . "\n";
}

echo "\n=== DONE ===\n";
