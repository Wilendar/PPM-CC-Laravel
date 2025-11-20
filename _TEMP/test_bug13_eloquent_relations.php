<?php

/**
 * BUG #13 (2025-11-13): Test Eloquent Relations dla liczników mapowań
 *
 * USAGE: php artisan tinker < _TEMP/test_bug13_eloquent_relations.php
 *
 * EXPECTED OUTPUT:
 * - Lista sklepów z price_group_mappings_count i warehouse_mappings_count
 * - Wartości > 0 dla sklepów z istniejącymi mapowaniami
 */

echo "=== BUG #13: Testing Eloquent Relations for Mapping Counts ===\n\n";

// Test 1: Load shops with withCount()
echo "TEST 1: Load shops with withCount() relations\n";
echo str_repeat("-", 60) . "\n";

$shops = \App\Models\PrestaShopShop::withCount(['priceGroupMappings', 'warehouseMappings'])
    ->get();

if ($shops->isEmpty()) {
    echo "❌ No shops found in database\n";
    exit(1);
}

foreach ($shops as $shop) {
    echo "Shop: {$shop->name} (ID: {$shop->id})\n";
    echo "  Price Group Mappings Count: {$shop->price_group_mappings_count}\n";
    echo "  Warehouse Mappings Count: {$shop->warehouse_mappings_count}\n";
    echo "\n";
}

// Test 2: Verify relation methods exist
echo "\nTEST 2: Verify relation methods exist\n";
echo str_repeat("-", 60) . "\n";

$firstShop = $shops->first();

try {
    $priceRelation = $firstShop->priceGroupMappings();
    echo "✅ priceGroupMappings() relation exists\n";
    echo "   Type: " . get_class($priceRelation) . "\n";
} catch (\Exception $e) {
    echo "❌ priceGroupMappings() relation error: {$e->getMessage()}\n";
}

try {
    $warehouseRelation = $firstShop->warehouseMappings();
    echo "✅ warehouseMappings() relation exists\n";
    echo "   Type: " . get_class($warehouseRelation) . "\n";
} catch (\Exception $e) {
    echo "❌ warehouseMappings() relation error: {$e->getMessage()}\n";
}

// Test 3: Manually load price mappings for first shop
echo "\nTEST 3: Manually load price mappings\n";
echo str_repeat("-", 60) . "\n";

$priceMappings = \App\Models\PrestaShopShopPriceMapping::where('prestashop_shop_id', $firstShop->id)->get();
echo "Price Mappings for Shop ID {$firstShop->id}: {$priceMappings->count()}\n";

foreach ($priceMappings as $mapping) {
    echo "  - {$mapping->prestashop_price_group_name} → {$mapping->ppm_price_group_name}\n";
}

// Test 4: Manually load warehouse mappings for first shop
echo "\nTEST 4: Manually load warehouse mappings\n";
echo str_repeat("-", 60) . "\n";

$warehouseMappings = \App\Models\Warehouse::where('shop_id', $firstShop->id)
    ->where('type', 'shop_linked')
    ->get();
echo "Warehouse Mappings for Shop ID {$firstShop->id}: {$warehouseMappings->count()}\n";

foreach ($warehouseMappings as $warehouse) {
    echo "  - {$warehouse->name} (code: {$warehouse->code})\n";
}

// Test 5: Compare old JSON approach vs new Eloquent approach
echo "\nTEST 5: Compare old JSON vs new Eloquent approach\n";
echo str_repeat("-", 60) . "\n";

$oldPriceCount = is_array($firstShop->price_group_mappings) ? count($firstShop->price_group_mappings) : 0;
$newPriceCount = $firstShop->price_group_mappings_count;

$oldWarehouseCount = is_array($firstShop->warehouse_mappings) ? count($firstShop->warehouse_mappings) : 0;
$newWarehouseCount = $firstShop->warehouse_mappings_count;

echo "Price Group Mappings:\n";
echo "  OLD (JSON column): {$oldPriceCount}\n";
echo "  NEW (Eloquent withCount): {$newPriceCount}\n";
echo "  Status: " . ($newPriceCount > 0 ? "✅ WORKING" : "⚠️ NO DATA") . "\n";

echo "\nWarehouse Mappings:\n";
echo "  OLD (JSON column): {$oldWarehouseCount}\n";
echo "  NEW (Eloquent withCount): {$newWarehouseCount}\n";
echo "  Status: " . ($newWarehouseCount > 0 ? "✅ WORKING" : "⚠️ NO DATA") . "\n";

echo "\n=== TEST COMPLETED ===\n";
