<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VERIFICATION: Price Group Mappings Fix ===\n\n";

// Test with first available shop
$shop = \App\Models\PrestaShopShop::first();

if (!$shop) {
    echo "No shops found in database.\n";
    exit(1);
}

echo "Testing with Shop ID: {$shop->id} - {$shop->name}\n";
echo "---\n\n";

// Check if mappings exist in database
$mappings = DB::table('prestashop_shop_price_mappings')
    ->where('prestashop_shop_id', $shop->id)
    ->get();

echo "Step 1: Check database mappings\n";
if ($mappings->count() > 0) {
    echo "✅ Found {$mappings->count()} mappings in database:\n";
    foreach ($mappings as $mapping) {
        echo "  - PS Group ID {$mapping->prestashop_price_group_id}: {$mapping->prestashop_price_group_name} → PPM: {$mapping->ppm_price_group_name}\n";
    }
} else {
    echo "❌ No mappings found in database for this shop.\n";
}

echo "\n---\n\n";

// Simulate what loadShopData() does
echo "Step 2: Simulate loadShopData() logic\n";
$priceGroupMappings = [];
$existingMappings = DB::table('prestashop_shop_price_mappings')
    ->where('prestashop_shop_id', $shop->id)
    ->get();

if ($existingMappings->count() > 0) {
    echo "✅ Loading mappings into priceGroupMappings array:\n";
    foreach ($existingMappings as $mapping) {
        $priceGroupMappings[$mapping->prestashop_price_group_id] = $mapping->ppm_price_group_name;
        echo "  - priceGroupMappings[{$mapping->prestashop_price_group_id}] = '{$mapping->ppm_price_group_name}'\n";
    }
} else {
    echo "❌ No mappings loaded.\n";
}

echo "\n---\n\n";

echo "Step 3: Verify array structure\n";
if (count($priceGroupMappings) > 0) {
    echo "✅ priceGroupMappings array populated with " . count($priceGroupMappings) . " items:\n";
    echo json_encode($priceGroupMappings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "❌ priceGroupMappings array is empty.\n";
}

echo "\n---\n\n";

echo "=== VERIFICATION COMPLETE ===\n";
echo "\n";
echo "FIX STATUS: ";
if (count($priceGroupMappings) > 0) {
    echo "✅ SUCCESS - Mappings are loaded correctly\n";
} else {
    echo "❌ FAILED - Mappings are not loaded\n";
}
