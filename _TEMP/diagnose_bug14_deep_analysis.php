<?php
/**
 * BUG #14 DEEP ANALYSIS: Why specific prices aren't importing
 *
 * GOAL: Identify why only base price (Detaliczna) imports, not specific_prices
 *
 * CHECKS:
 * 1. Does prestashop_shop_price_mappings table exist?
 * 2. Does it have any mappings for test shop?
 * 3. What PrestaShop groups exist in test shop?
 * 4. What specific_prices exist for test product?
 * 5. What prices are saved in PPM product_prices?
 * 6. What do production logs show?
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║       BUG #14 DEEP ANALYSIS: Specific Prices Import          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// =============================================================================
// CHECK 1: Does prestashop_shop_price_mappings table exist?
// =============================================================================
echo "═══════════════════════════════════════════════════════════════\n";
echo "CHECK 1: prestashop_shop_price_mappings TABLE EXISTENCE\n";
echo "═══════════════════════════════════════════════════════════════\n";

$tableExists = \Schema::hasTable('prestashop_shop_price_mappings');
echo $tableExists ? "✅ TABLE EXISTS\n" : "❌ TABLE DOES NOT EXIST\n";

if (!$tableExists) {
    echo "\n🚨 CRITICAL: Table does not exist! Run migration:\n";
    echo "   php artisan migrate --path=database/migrations/2025_11_13_092744_create_prestashop_shop_price_mappings_table.php\n\n";
    exit(1);
}

// Get table structure
echo "\nTable columns:\n";
$columns = \DB::select("SHOW COLUMNS FROM prestashop_shop_price_mappings");
foreach ($columns as $col) {
    echo "  - {$col->Field} ({$col->Type})\n";
}

// =============================================================================
// CHECK 2: Get test shop details
// =============================================================================
echo "\n═══════════════════════════════════════════════════════════════\n";
echo "CHECK 2: TEST SHOP DETAILS\n";
echo "═══════════════════════════════════════════════════════════════\n";

$shop = \App\Models\PrestaShopShop::first();
if (!$shop) {
    echo "❌ NO SHOPS FOUND\n";
    exit(1);
}

echo "✅ SHOP: {$shop->name} (ID: {$shop->id})\n";
echo "   URL: {$shop->url}\n";
echo "   Active: " . ($shop->is_active ? 'YES' : 'NO') . "\n";

// =============================================================================
// CHECK 3: Mappings for this shop
// =============================================================================
echo "\n═══════════════════════════════════════════════════════════════\n";
echo "CHECK 3: PRICE GROUP MAPPINGS FOR SHOP #{$shop->id}\n";
echo "═══════════════════════════════════════════════════════════════\n";

$mappings = \DB::table('prestashop_shop_price_mappings')
    ->where('prestashop_shop_id', $shop->id)
    ->get();

echo "Mappings count: " . $mappings->count() . "\n\n";

if ($mappings->isEmpty()) {
    echo "⚠️ NO MAPPINGS FOUND!\n";
    echo "This means ALL specific_prices with id_group > 0 will be SKIPPED!\n\n";
    echo "SOLUTION: Create mappings using:\n";
    echo "  INSERT INTO prestashop_shop_price_mappings (prestashop_shop_id, prestashop_price_group_id, prestashop_price_group_name, ppm_price_group_name) VALUES\n";
    echo "  ({$shop->id}, 1, 'Visitor', 'Detaliczna'),\n";
    echo "  ({$shop->id}, 2, 'Guest', 'Detaliczna'),\n";
    echo "  ({$shop->id}, 3, 'Customer', 'Dealer Standard');\n\n";
} else {
    echo "Existing mappings:\n";
    foreach ($mappings as $map) {
        echo "  - PrestaShop Group {$map->prestashop_price_group_id} ({$map->prestashop_price_group_name}) → PPM: {$map->ppm_price_group_name}\n";
    }
    echo "\n";
}

// =============================================================================
// CHECK 4: Test product details
// =============================================================================
echo "═══════════════════════════════════════════════════════════════\n";
echo "CHECK 4: TEST PRODUCT (Product #11020 mentioned by user)\n";
echo "═══════════════════════════════════════════════════════════════\n";

$product = \App\Models\Product::find(11020);
if (!$product) {
    echo "❌ PRODUCT 11020 NOT FOUND (using most recent product instead)\n";
    $product = \App\Models\Product::orderBy('updated_at', 'desc')->first();
}

echo "✅ PRODUCT: {$product->name} (ID: {$product->id}, SKU: {$product->sku})\n";

// Get shop_data
$shopData = $product->shopData()->where('shop_id', $shop->id)->first();
if (!$shopData) {
    echo "❌ NO SHOP_DATA FOR THIS PRODUCT\n";
    exit(1);
}

echo "   PrestaShop Product ID: {$shopData->prestashop_product_id}\n";
echo "   Last Synced: " . ($shopData->last_synced_at ?? 'NEVER') . "\n";
echo "   Last Pulled: " . ($shopData->last_pulled_at ?? 'NEVER') . "\n";

// =============================================================================
// CHECK 5: Current prices in PPM
// =============================================================================
echo "\n═══════════════════════════════════════════════════════════════\n";
echo "CHECK 5: CURRENT PRICES IN PPM (product_prices table)\n";
echo "═══════════════════════════════════════════════════════════════\n";

$prices = \DB::table('product_prices')
    ->join('price_groups', 'product_prices.price_group_id', '=', 'price_groups.id')
    ->where('product_prices.product_id', $product->id)
    ->select('price_groups.name as group_name', 'product_prices.price_net', 'product_prices.price_gross', 'product_prices.prestashop_mapping')
    ->get();

echo "Prices count: " . $prices->count() . "\n\n";

if ($prices->isEmpty()) {
    echo "⚠️ NO PRICES FOUND!\n";
    echo "This confirms prices are NOT being saved!\n\n";
} else {
    foreach ($prices as $price) {
        echo "  - {$price->group_name}: {$price->price_net} PLN (net), {$price->price_gross} PLN (gross)\n";
        if ($price->prestashop_mapping) {
            $mapping = json_decode($price->prestashop_mapping, true);
            echo "    PrestaShop Mapping: " . json_encode($mapping, JSON_PRETTY_PRINT) . "\n";
        }
    }
    echo "\n";
}

// =============================================================================
// CHECK 6: Production logs analysis
// =============================================================================
echo "═══════════════════════════════════════════════════════════════\n";
echo "CHECK 6: RECENT PRODUCTION LOGS (price import)\n";
echo "═══════════════════════════════════════════════════════════════\n";

echo "Searching for price import logs...\n\n";

$logFile = storage_path('logs/laravel.log');
if (!file_exists($logFile)) {
    echo "❌ Log file not found\n";
} else {
    // Get last 500 lines
    $logLines = array_slice(file($logFile), -500);

    $priceImportLogs = [];
    $mappingLogs = [];
    $warningLogs = [];

    foreach ($logLines as $line) {
        if (stripos($line, 'price import') !== false || stripos($line, 'importPricesForProduct') !== false) {
            $priceImportLogs[] = $line;
        }
        if (stripos($line, 'Mapped PrestaShop price group') !== false) {
            $mappingLogs[] = $line;
        }
        if (stripos($line, 'No price group mapping found') !== false) {
            $warningLogs[] = $line;
        }
    }

    if (empty($priceImportLogs) && empty($mappingLogs) && empty($warningLogs)) {
        echo "⚠️ NO PRICE IMPORT LOGS FOUND IN LAST 500 LINES\n";
        echo "This suggests:\n";
        echo "  1. No recent price import was executed\n";
        echo "  2. OR logging level is too high (not capturing debug logs)\n\n";
    } else {
        if (!empty($priceImportLogs)) {
            echo "Price Import Logs (" . count($priceImportLogs) . " entries):\n";
            foreach (array_slice($priceImportLogs, -3) as $log) {
                echo "  " . trim($log) . "\n";
            }
            echo "\n";
        }

        if (!empty($mappingLogs)) {
            echo "✅ Mapping Success Logs (" . count($mappingLogs) . " entries):\n";
            foreach (array_slice($mappingLogs, -3) as $log) {
                echo "  " . trim($log) . "\n";
            }
            echo "\n";
        }

        if (!empty($warningLogs)) {
            echo "⚠️ Mapping Warning Logs (" . count($warningLogs) . " entries):\n";
            foreach (array_slice($warningLogs, -3) as $log) {
                echo "  " . trim($log) . "\n";
            }
            echo "\n";
        }
    }
}

// =============================================================================
// CHECK 7: Simulate manual import
// =============================================================================
echo "═══════════════════════════════════════════════════════════════\n";
echo "CHECK 7: MANUAL IMPORT SIMULATION (DRY RUN)\n";
echo "═══════════════════════════════════════════════════════════════\n";

try {
    echo "Creating PrestaShop client...\n";
    $client = \App\Services\PrestaShop\PrestaShopClientFactory::create($shop);

    echo "Fetching product data from PrestaShop...\n";
    $productData = $client->getProduct($shopData->prestashop_product_id);

    if (isset($productData['product'])) {
        $productData = $productData['product'];
    }

    $basePrice = (float) data_get($productData, 'price', 0);
    echo "  Base Price: {$basePrice} PLN\n";

    echo "\nFetching specific_prices from PrestaShop...\n";
    $specificPricesData = $client->getSpecificPrices($shopData->prestashop_product_id);

    $specificPrices = [];
    if (isset($specificPricesData['specific_prices']) && is_array($specificPricesData['specific_prices'])) {
        $specificPrices = $specificPricesData['specific_prices'];
    }

    echo "  Specific Prices Count: " . count($specificPrices) . "\n\n";

    if (empty($specificPrices)) {
        echo "⚠️ NO SPECIFIC_PRICES IN PRESTASHOP!\n";
        echo "This means PrestaShop doesn't have any price groups/reductions for this product.\n";
        echo "ONLY base price would be imported (Detaliczna).\n\n";
        echo "USER EXPECTATION: Multiple price groups should exist in PrestaShop!\n";
        echo "ACTION REQUIRED: Check PrestaShop admin → Product → Specific Prices tab\n\n";
    } else {
        echo "Specific Prices Details:\n";
        foreach ($specificPrices as $sp) {
            $spId = data_get($sp, 'id');
            $idGroup = (int) data_get($sp, 'id_group', 0);
            $reduction = (float) data_get($sp, 'reduction', 0);
            $reductionType = data_get($sp, 'reduction_type', 'percentage');
            $priceOverride = (float) data_get($sp, 'price', -1);

            echo "\n  Specific Price #{$spId}:\n";
            echo "    id_group: {$idGroup}\n";
            echo "    reduction: {$reduction} ({$reductionType})\n";
            echo "    price_override: " . ($priceOverride >= 0 ? $priceOverride : 'NOT SET') . "\n";

            // Check if mapping exists
            if ($idGroup === 0) {
                echo "    ✅ WILL MAP TO: Default (Detaliczna) - id_group=0\n";
            } else {
                $mapping = \DB::table('prestashop_shop_price_mappings')
                    ->where('prestashop_shop_id', $shop->id)
                    ->where('prestashop_price_group_id', $idGroup)
                    ->first();

                if ($mapping) {
                    echo "    ✅ WILL MAP TO: {$mapping->ppm_price_group_name}\n";
                } else {
                    echo "    ❌ NO MAPPING FOUND! This price will be SKIPPED!\n";
                }
            }
        }
    }

} catch (\App\Exceptions\PrestaShopAPIException $e) {
    echo "❌ PrestaShop API Error: {$e->getMessage()}\n";
    echo "   HTTP Status: {$e->getHttpStatusCode()}\n";
} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n";
}

// =============================================================================
// SUMMARY
// =============================================================================
echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║                       DIAGNOSIS SUMMARY                        ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "ROOT CAUSE ANALYSIS:\n\n";

$rootCauses = [];

if (!$tableExists) {
    $rootCauses[] = "❌ CRITICAL: prestashop_shop_price_mappings table DOES NOT EXIST";
}

if ($mappings->isEmpty()) {
    $rootCauses[] = "❌ CRITICAL: NO PRICE GROUP MAPPINGS configured for shop";
}

if (empty($specificPrices ?? [])) {
    $rootCauses[] = "⚠️ IMPORTANT: PrestaShop has NO specific_prices for test product";
}

if ($prices->isEmpty()) {
    $rootCauses[] = "❌ SYMPTOM: NO prices saved in PPM product_prices table";
}

if (empty($rootCauses)) {
    echo "✅ All checks PASSED! Price import should be working.\n";
    echo "   If user reports issues, check:\n";
    echo "   - Are specific_prices configured in PrestaShop for other products?\n";
    echo "   - Is PullProductsFromPrestaShop job running?\n";
    echo "   - Check production logs for warnings\n";
} else {
    foreach ($rootCauses as $i => $cause) {
        echo ($i + 1) . ". {$cause}\n";
    }
    echo "\n";
}

echo "\nRECOMMENDATIONS:\n";
echo "1. Run this script on PRODUCTION to verify table/mappings exist\n";
echo "2. Check PrestaShop admin → Product → Specific Prices tab\n";
echo "3. Manually trigger import: php artisan prestashop:pull-products\n";
echo "4. Monitor logs: tail -f storage/logs/laravel.log | grep -i 'price'\n\n";
