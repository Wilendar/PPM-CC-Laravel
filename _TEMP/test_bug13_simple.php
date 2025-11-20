<?php

/**
 * BUG #13 (2025-11-13): Simple Test Eloquent Relations
 *
 * USAGE: php _TEMP/test_bug13_simple.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== BUG #13: Testing Eloquent Relations ===\n\n";

try {
    // Test 1: Load shops with withCount
    echo "TEST 1: Loading shops with withCount(['priceGroupMappings', 'warehouseMappings'])\n";
    echo str_repeat("-", 70) . "\n";

    $shops = \App\Models\PrestaShopShop::withCount(['priceGroupMappings', 'warehouseMappings'])
        ->orderBy('id')
        ->get();

    if ($shops->isEmpty()) {
        echo "❌ No shops found in database\n";
        exit(1);
    }

    echo "Found {$shops->count()} shop(s)\n\n";

    foreach ($shops as $shop) {
        echo "Shop ID {$shop->id}: {$shop->name}\n";
        echo "  Price Group Mappings Count: {$shop->price_group_mappings_count}\n";
        echo "  Warehouse Mappings Count: {$shop->warehouse_mappings_count}\n";

        // Show old JSON approach values
        $oldPriceCount = is_array($shop->price_group_mappings) ? count($shop->price_group_mappings) : 0;
        $oldWarehouseCount = is_array($shop->warehouse_mappings) ? count($shop->warehouse_mappings) : 0;
        echo "  OLD JSON price_group_mappings: {$oldPriceCount}\n";
        echo "  OLD JSON warehouse_mappings: {$oldWarehouseCount}\n";
        echo "\n";
    }

    // Test 2: Check relations on first shop
    echo "\nTEST 2: Testing relations on first shop\n";
    echo str_repeat("-", 70) . "\n";

    $firstShop = $shops->first();

    // Test priceGroupMappings relation
    try {
        $priceMappings = $firstShop->priceGroupMappings;
        echo "✅ priceGroupMappings relation works\n";
        echo "   Loaded {$priceMappings->count()} price mapping(s)\n";

        foreach ($priceMappings as $mapping) {
            echo "   - {$mapping->prestashop_price_group_name} → {$mapping->ppm_price_group_name}\n";
        }
    } catch (\Exception $e) {
        echo "❌ priceGroupMappings relation failed: {$e->getMessage()}\n";
    }

    // Test warehouseMappings relation
    try {
        $warehouseMappings = $firstShop->warehouseMappings;
        echo "\n✅ warehouseMappings relation works\n";
        echo "   Loaded {$warehouseMappings->count()} warehouse mapping(s)\n";

        foreach ($warehouseMappings as $warehouse) {
            echo "   - {$warehouse->name} (code: {$warehouse->code}, type: {$warehouse->type})\n";
        }
    } catch (\Exception $e) {
        echo "\n❌ warehouseMappings relation failed: {$e->getMessage()}\n";
    }

    echo "\n=== SUCCESS: All relations working correctly ===\n";
    exit(0);

} catch (\Exception $e) {
    echo "\n❌ ERROR: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n";
    echo "   Trace:\n{$e->getTraceAsString()}\n";
    exit(1);
}
