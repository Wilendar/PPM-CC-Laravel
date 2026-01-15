<?php
/**
 * Fix Missing AttributeValue PrestaShop Mappings
 *
 * FIX 2025-12-11: Create mappings for attribute values that were
 * imported without proper PrestaShop shop association.
 *
 * Run via: php artisan tinker < fix_missing_attribute_mappings.php
 * Or: plink ... "cd ... && php artisan tinker --execute='require(\"_TOOLS/fix_missing_attribute_mappings.php\")'"
 */

use App\Models\AttributeValue;
use App\Models\AttributeValuePsMapping;
use App\Models\PrestaShopShop;
use App\Models\VariantAttribute;
use Illuminate\Support\Facades\DB;

echo "=== FIX MISSING ATTRIBUTE VALUE MAPPINGS ===\n\n";

// Get all active PrestaShop shops
$shops = PrestaShopShop::where('is_active', true)->get();
echo "Found " . $shops->count() . " active shops\n";

if ($shops->isEmpty()) {
    echo "ERROR: No active shops found!\n";
    exit(1);
}

// Get attribute values that are USED by variants but have NO mappings
$usedValueIds = VariantAttribute::distinct()->pluck('value_id')->toArray();
echo "Found " . count($usedValueIds) . " attribute values used by variants\n";

// Get values without mappings
$valuesWithoutMappings = AttributeValue::whereIn('id', $usedValueIds)
    ->whereDoesntHave('prestashopMappings')
    ->with('attributeType')
    ->get();

echo "Found " . $valuesWithoutMappings->count() . " values without mappings\n\n";

if ($valuesWithoutMappings->isEmpty()) {
    echo "All attribute values already have mappings. Nothing to fix!\n";
    exit(0);
}

$created = 0;
$errors = 0;

foreach ($valuesWithoutMappings as $value) {
    echo "Processing: [{$value->id}] {$value->label} (Type: {$value->attributeType->name})\n";

    foreach ($shops as $shop) {
        try {
            // Create mapping for this value-shop pair
            AttributeValuePsMapping::updateOrCreate(
                [
                    'attribute_value_id' => $value->id,
                    'prestashop_shop_id' => $shop->id,
                ],
                [
                    'prestashop_attribute_id' => null, // Unknown - will be synced later
                    'prestashop_label' => $value->label,
                    'prestashop_color' => $value->color_hex,
                    'is_synced' => false,
                    'sync_status' => 'pending', // Mark as pending for sync verification
                    'last_synced_at' => now(),
                    'sync_notes' => 'Auto-created by fix script - needs sync verification',
                ]
            );

            echo "  -> Created mapping for shop: {$shop->name}\n";
            $created++;

        } catch (\Exception $e) {
            echo "  -> ERROR for shop {$shop->name}: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
}

echo "\n=== SUMMARY ===\n";
echo "Mappings created: {$created}\n";
echo "Errors: {$errors}\n";
echo "\nDone! Now go to /admin/product-parameters and click 'Sync PS' to verify mappings.\n";
