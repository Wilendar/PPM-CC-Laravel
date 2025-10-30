<?php
/**
 * E2E Test Script - PrestaShop Attribute Sync
 * Test 2: Export TO PrestaShop (Automated)
 *
 * Usage: php artisan tinker < _TEMP/test_attribute_sync_e2e.php
 */

use App\Models\AttributeType;
use App\Models\AttributeValue;
use App\Models\PrestaShopShop;
use App\Jobs\PrestaShop\SyncAttributeGroupWithPrestaShop;
use App\Jobs\PrestaShop\SyncAttributeValueWithPrestaShop;

echo "\n=== E2E TEST 2: Export TO PrestaShop ===\n";
echo "Creating test AttributeType...\n";

// Step 1: Create AttributeType
$attributeType = AttributeType::create([
    'name' => 'Rozmiar_Test_E2E_v2',
    'group' => 'Warianty',
    'display_type' => 'select',
    'is_active' => true,
    'is_required' => false,
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "✅ AttributeType created: ID = {$attributeType->id}, Name = {$attributeType->name}\n";

// Step 2: Create AttributeValues
$values = ['S_Test', 'M_Test', 'L_Test', 'XL_Test'];
foreach ($values as $index => $valueName) {
    $attributeValue = AttributeValue::create([
        'attribute_type_id' => $attributeType->id,
        'label' => $valueName,
        'value' => strtolower($valueName), // s_test, m_test, etc.
        'display_order' => $index + 1,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    echo "✅ AttributeValue created: ID = {$attributeValue->id}, Label = {$attributeValue->label}\n";
}

// Step 3: Get PrestaShop shop (dev.mpptrade.pl)
$shop = PrestaShopShop::where('name', 'LIKE', '%DEV%')->orWhere('url', 'LIKE', '%dev.mpptrade.pl%')->first();

if (!$shop) {
    echo "❌ ERROR: PrestaShop shop not found (looking for DEV shop)\n";
    echo "Available shops:\n";
    PrestaShopShop::all()->each(function($s) {
        echo "  - ID={$s->id}, Name={$s->name}, URL={$s->url}\n";
    });
    exit(1);
}

echo "✅ PrestaShop shop found: ID = {$shop->id}, Name = {$shop->name}, URL = {$shop->url}\n";

// Step 4: Dispatch sync job
echo "\nDispatching sync job to queue...\n";
SyncAttributeGroupWithPrestaShop::dispatch($attributeType, $shop);

echo "✅ Job dispatched: SyncAttributeGroupWithPrestaShop\n";
echo "   AttributeType ID: {$attributeType->id}\n";
echo "   Shop ID: {$shop->id}\n";

// Step 5: Instructions for manual verification
echo "\n=== NEXT STEPS ===\n";
echo "1. Run queue worker:\n";
echo "   php artisan queue:work --once\n";
echo "\n";
echo "2. Check logs:\n";
echo "   tail -100 storage/logs/laravel.log | grep 'SyncAttributeGroup'\n";
echo "\n";
echo "3. Verify mapping table:\n";
echo "   SELECT * FROM prestashop_attribute_group_mapping WHERE attribute_type_id = {$attributeType->id};\n";
echo "\n";
echo "4. Verify PrestaShop database:\n";
echo "   SELECT * FROM ps_attribute_group WHERE name LIKE '%Rozmiar_Test_E2E_v2%';\n";
echo "\n";

echo "=== TEST 2 PREPARATION COMPLETE ===\n\n";
