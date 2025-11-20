<?php
/**
 * BUG #14 Verification Script
 *
 * Verify that specific prices import works correctly after fix.
 *
 * Tests:
 * 1. Check prestashop_shop_price_mappings table has mappings
 * 2. Trigger price import for test product
 * 3. Verify product_prices table has all mapped price groups
 * 4. Check logs for mapping confirmations
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\PrestaShop\PrestaShopPriceImporter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "\n=== BUG #14: Specific Prices Import Verification ===\n\n";

// 1. Check prestashop_shop_price_mappings
echo "1. Checking prestashop_shop_price_mappings table...\n";

$mappings = DB::table('prestashop_shop_price_mappings')
    ->select('prestashop_shop_id', 'prestashop_price_group_id', 'prestashop_price_group_name', 'ppm_price_group_name')
    ->orderBy('prestashop_shop_id')
    ->orderBy('prestashop_price_group_id')
    ->get();

if ($mappings->isEmpty()) {
    echo "   ⚠️  WARNING: No price group mappings found!\n";
    echo "   User needs to configure price mappings in shop settings first.\n\n";
    exit(1);
}

echo "   ✅ Found " . $mappings->count() . " price group mappings:\n\n";

foreach ($mappings as $mapping) {
    echo "   Shop ID: {$mapping->prestashop_shop_id}\n";
    echo "   PS Group ID: {$mapping->prestashop_price_group_id} ({$mapping->prestashop_price_group_name})\n";
    echo "   → PPM Group: {$mapping->ppm_price_group_name}\n";
    echo "   ---\n";
}

// 2. Get first enabled shop with mappings
echo "\n2. Finding enabled shop with product data...\n";

// Get shop ID from mappings table
$shopIdWithMappings = DB::table('prestashop_shop_price_mappings')
    ->select('prestashop_shop_id')
    ->distinct()
    ->first();

if (!$shopIdWithMappings) {
    echo "   ⚠️  WARNING: No shop with price mappings found!\n";
    exit(1);
}

$shop = PrestaShopShop::where('is_active', true)
    ->where('id', $shopIdWithMappings->prestashop_shop_id)
    ->first();

if (!$shop) {
    echo "   ⚠️  WARNING: No enabled shop with price mappings found!\n";
    exit(1);
}

echo "   ✅ Shop: {$shop->name} (ID: {$shop->id})\n";
echo "   API: {$shop->url}\n";
echo "   Version: {$shop->version}\n\n";

// 3. Get test product linked to this shop
echo "3. Finding test product linked to shop...\n";

$product = Product::whereHas('shopData', function($query) use ($shop) {
    $query->where('shop_id', $shop->id)
          ->whereNotNull('prestashop_product_id');
})
->first();

if (!$product) {
    echo "   ⚠️  WARNING: No product linked to shop found!\n";
    exit(1);
}

$shopData = $product->shopData()->where('shop_id', $shop->id)->first();

echo "   ✅ Product: {$product->name} (SKU: {$product->sku})\n";
echo "   Product ID: {$product->id}\n";
echo "   PrestaShop Product ID: {$shopData->prestashop_product_id}\n\n";

// 4. Check current product_prices BEFORE import
echo "4. Current product_prices BEFORE import:\n";

$pricesBefore = ProductPrice::where('product_id', $product->id)
    ->with('priceGroup')
    ->get();

if ($pricesBefore->isEmpty()) {
    echo "   ℹ️  No prices found (first import)\n\n";
} else {
    echo "   Found " . $pricesBefore->count() . " existing prices:\n";
    foreach ($pricesBefore as $price) {
        echo "   - {$price->priceGroup->name}: {$price->price_net} PLN (net)\n";
    }
    echo "\n";
}

// 5. Trigger price import
echo "5. Triggering price import...\n";

try {
    $importer = app(PrestaShopPriceImporter::class);
    $importedPrices = $importer->importPricesForProduct($product, $shop);

    echo "   ✅ Import completed!\n";
    echo "   Imported " . count($importedPrices) . " price records\n\n";

    if (!empty($importedPrices)) {
        echo "   Imported prices:\n";
        foreach ($importedPrices as $imported) {
            $source = $imported['source'] ?? 'unknown';
            $groupInfo = $imported['price_group'] ?? "ID: {$imported['price_group_id']}";
            $net = $imported['net'] ?? 'N/A';
            echo "   - {$groupInfo}: {$net} PLN (source: {$source})\n";
        }
        echo "\n";
    }

} catch (\App\Exceptions\PrestaShopAPIException $e) {
    echo "   ❌ PrestaShop API Error!\n";
    echo "   Status: {$e->getHttpStatusCode()}\n";
    echo "   Error: {$e->getMessage()}\n\n";
    exit(1);
} catch (\Exception $e) {
    echo "   ❌ Import failed!\n";
    echo "   Error: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n\n";
    exit(1);
}

// 6. Check product_prices AFTER import
echo "6. Product_prices AFTER import:\n";

$pricesAfter = ProductPrice::where('product_id', $product->id)
    ->with('priceGroup')
    ->get();

if ($pricesAfter->isEmpty()) {
    echo "   ⚠️  WARNING: No prices found after import!\n\n";
    exit(1);
}

echo "   ✅ Found " . $pricesAfter->count() . " prices:\n\n";

foreach ($pricesAfter as $price) {
    echo "   Price Group: {$price->priceGroup->name}\n";
    echo "   Net: {$price->price_net} PLN\n";
    echo "   Gross: {$price->price_gross} PLN\n";

    if ($price->prestashop_mapping) {
        $mapping = $price->prestashop_mapping[$shop->id] ?? null;
        if ($mapping) {
            echo "   Source: " . ($mapping['source'] ?? 'specific_price') . "\n";
            if (isset($mapping['specific_price_id'])) {
                echo "   Specific Price ID: {$mapping['specific_price_id']}\n";
            }
            if (isset($mapping['reduction'])) {
                echo "   Reduction: {$mapping['reduction']} ({$mapping['reduction_type']})\n";
            }
        }
    }

    echo "   ---\n";
}

// 7. Compare price groups coverage
echo "\n7. Coverage Analysis:\n";

$mappedGroups = $mappings->where('prestashop_shop_id', $shop->id)
    ->pluck('ppm_price_group_name')
    ->unique();

$importedGroups = $pricesAfter->pluck('priceGroup.name');

echo "   Mapped Groups (configured): " . $mappedGroups->count() . "\n";
echo "   Imported Groups (actual): " . $importedGroups->count() . "\n\n";

$missing = $mappedGroups->diff($importedGroups);

if ($missing->isEmpty()) {
    echo "   ✅ All mapped price groups have been imported!\n\n";
} else {
    echo "   ⚠️  Missing price groups:\n";
    foreach ($missing as $missingGroup) {
        echo "   - {$missingGroup}\n";
    }
    echo "\n   Possible reasons:\n";
    echo "   - PrestaShop product has no specific_prices for these groups\n";
    echo "   - Price group mapping ID mismatch\n\n";
}

// 8. Recent log entries
echo "8. Recent log entries (last 20 lines):\n";

$logFile = storage_path('logs/laravel.log');

if (file_exists($logFile)) {
    $logs = explode("\n", file_get_contents($logFile));
    $recentLogs = array_slice($logs, -20);

    $relevantLogs = array_filter($recentLogs, function($line) use ($product, $shop) {
        return strpos($line, 'Mapped PrestaShop price group') !== false
            || strpos($line, "product_id.*{$product->id}") !== false
            || strpos($line, "shop_id.*{$shop->id}") !== false;
    });

    if (!empty($relevantLogs)) {
        echo "\n   Recent relevant logs:\n";
        foreach ($relevantLogs as $log) {
            echo "   " . substr($log, 0, 200) . "\n";
        }
    } else {
        echo "   ℹ️  No relevant logs found in last 20 lines\n";
    }
} else {
    echo "   ⚠️  Log file not found: {$logFile}\n";
}

echo "\n=== Verification Complete ===\n\n";

echo "SUMMARY:\n";
echo "✅ Price import completed successfully\n";
echo "✅ " . $pricesAfter->count() . " price groups imported\n";

if ($missing->isEmpty()) {
    echo "✅ All mapped price groups have prices\n";
} else {
    echo "⚠️  " . $missing->count() . " mapped price groups missing (may be expected)\n";
}

echo "\nNext Steps:\n";
echo "1. Check PrestaShop admin for specific_prices configuration\n";
echo "2. Verify price group mappings in shop settings are correct\n";
echo "3. Test with product that has specific_prices in PrestaShop\n";
echo "4. Monitor Laravel logs for mapping confirmations\n\n";
