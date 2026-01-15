<?php

namespace App\Console\Commands;

use App\Models\AttributeValue;
use App\Models\AttributeValuePsMapping;
use App\Models\PrestaShopShop;
use App\Models\VariantAttribute;
use Illuminate\Console\Command;

/**
 * Fix Missing AttributeValue PrestaShop Mappings
 *
 * FIX 2025-12-11: Create mappings for attribute values that were
 * imported without proper PrestaShop shop association.
 */
class FixMissingAttributeMappings extends Command
{
    protected $signature = 'ppm:fix-attribute-mappings';
    protected $description = 'Create missing PrestaShop mappings for attribute values';

    public function handle(): int
    {
        $this->info('=== FIX MISSING ATTRIBUTE VALUE MAPPINGS ===');

        // Get all active PrestaShop shops
        $shops = PrestaShopShop::where('is_active', true)->get();
        $this->info("Found {$shops->count()} active shops");

        if ($shops->isEmpty()) {
            $this->error('No active shops found!');
            return 1;
        }

        // Get attribute values that are USED by variants but have NO mappings
        $usedValueIds = VariantAttribute::distinct()->pluck('value_id')->toArray();
        $this->info("Found " . count($usedValueIds) . " attribute values used by variants");

        // Get values without mappings
        $valuesWithoutMappings = AttributeValue::whereIn('id', $usedValueIds)
            ->whereDoesntHave('prestashopMappings')
            ->with('attributeType')
            ->get();

        $this->info("Found {$valuesWithoutMappings->count()} values without mappings");

        if ($valuesWithoutMappings->isEmpty()) {
            $this->info('All attribute values already have mappings. Nothing to fix!');
            return 0;
        }

        $created = 0;
        $errors = 0;

        foreach ($valuesWithoutMappings as $value) {
            $this->line("Processing: [{$value->id}] {$value->label} (Type: {$value->attributeType->name})");

            foreach ($shops as $shop) {
                try {
                    AttributeValuePsMapping::updateOrCreate(
                        [
                            'attribute_value_id' => $value->id,
                            'prestashop_shop_id' => $shop->id,
                        ],
                        [
                            'prestashop_attribute_id' => null,
                            'prestashop_label' => $value->label,
                            'prestashop_color' => $value->color_hex,
                            'is_synced' => false,
                            'sync_status' => 'pending',
                            'last_synced_at' => now(),
                            'sync_notes' => 'Auto-created by fix command - needs sync',
                        ]
                    );

                    $this->line("  -> Created mapping for shop: {$shop->name}");
                    $created++;

                } catch (\Exception $e) {
                    $this->error("  -> ERROR for shop {$shop->name}: " . $e->getMessage());
                    $errors++;
                }
            }
        }

        $this->newLine();
        $this->info("=== SUMMARY ===");
        $this->info("Mappings created: {$created}");
        $this->info("Errors: {$errors}");

        return 0;
    }
}
