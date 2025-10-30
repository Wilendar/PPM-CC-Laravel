<?php

namespace Database\Seeders;

use App\Models\AttributeType;
use App\Models\AttributeValue;
use App\Models\PrestaShopShop;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PrestaShopAttributeMappingSeeder
 *
 * ETAP_05b Phase 1: Database Schema
 *
 * PURPOSE:
 * - Create initial PrestaShop mapping records for existing AttributeTypes
 * - Create mapping records for existing AttributeValues
 * - All mappings start with 'pending' status (waiting for verification)
 * - Enables immediate PrestaShop sync after schema deployment
 *
 * LOGIC:
 * - For each active PrestaShop shop:
 *   - Create attribute_group_mapping for each AttributeType
 *   - Create attribute_value_mapping for each AttributeValue
 * - Status: 'pending' (requires manual sync verification)
 * - No PrestaShop IDs set (populated during first sync)
 *
 * PRODUCTION SAFE:
 * - Uses updateOrCreate (idempotent)
 * - Skips inactive shops
 * - Logs progress for monitoring
 * - Transaction-wrapped for atomicity
 *
 * @version 1.0
 * @since 2025-10-24
 */
class PrestaShopAttributeMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Log::info('PrestaShopAttributeMappingSeeder: Starting seeding...');

        // Get all active PrestaShop shops
        $shops = PrestaShopShop::where('is_active', true)->get();

        if ($shops->isEmpty()) {
            $this->command->warn('⚠️  No active PrestaShop shops found. Skipping seeder.');
            Log::warning('PrestaShopAttributeMappingSeeder: No active shops found');
            return;
        }

        $this->command->info("Found {$shops->count()} active PrestaShop shop(s)");
        Log::info("PrestaShopAttributeMappingSeeder: Processing {$shops->count()} shops");

        // Get all active attribute types
        $attributeTypes = AttributeType::where('is_active', true)->get();

        if ($attributeTypes->isEmpty()) {
            $this->command->warn('⚠️  No active AttributeTypes found. Skipping seeder.');
            Log::warning('PrestaShopAttributeMappingSeeder: No active attribute types found');
            return;
        }

        $this->command->info("Found {$attributeTypes->count()} active AttributeType(s)");
        Log::info("PrestaShopAttributeMappingSeeder: Processing {$attributeTypes->count()} attribute types");

        DB::beginTransaction();

        try {
            $groupMappingsCreated = 0;
            $valueMappingsCreated = 0;

            // Create attribute group mappings
            foreach ($attributeTypes as $type) {
                foreach ($shops as $shop) {
                    // Check if mapping already exists
                    $exists = DB::table('prestashop_attribute_group_mapping')
                        ->where('attribute_type_id', $type->id)
                        ->where('prestashop_shop_id', $shop->id)
                        ->exists();

                    if (!$exists) {
                        DB::table('prestashop_attribute_group_mapping')->insert([
                            'attribute_type_id' => $type->id,
                            'prestashop_shop_id' => $shop->id,
                            'prestashop_attribute_group_id' => null,
                            'prestashop_label' => null,
                            'is_synced' => false,
                            'last_synced_at' => null,
                            'sync_status' => 'pending',
                            'sync_notes' => 'Initial mapping created by seeder. Awaiting first sync verification.',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $groupMappingsCreated++;
                    }
                }

                // Create attribute value mappings for this type
                $values = AttributeValue::where('attribute_type_id', $type->id)
                    ->where('is_active', true)
                    ->get();

                foreach ($values as $value) {
                    foreach ($shops as $shop) {
                        // Check if mapping already exists
                        $exists = DB::table('prestashop_attribute_value_mapping')
                            ->where('attribute_value_id', $value->id)
                            ->where('prestashop_shop_id', $shop->id)
                            ->exists();

                        if (!$exists) {
                            DB::table('prestashop_attribute_value_mapping')->insert([
                                'attribute_value_id' => $value->id,
                                'prestashop_shop_id' => $shop->id,
                                'prestashop_attribute_id' => null,
                                'prestashop_label' => null,
                                'prestashop_color' => $value->color_hex, // Copy from PPM (will be verified during sync)
                                'is_synced' => false,
                                'last_synced_at' => null,
                                'sync_status' => 'pending',
                                'sync_notes' => 'Initial mapping created by seeder. Awaiting first sync verification.',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            $valueMappingsCreated++;
                        }
                    }
                }
            }

            DB::commit();

            $this->command->info("✅ PrestaShop Attribute Mappings seeded successfully!");
            $this->command->info("   - Attribute Group Mappings created: {$groupMappingsCreated}");
            $this->command->info("   - Attribute Value Mappings created: {$valueMappingsCreated}");
            $this->command->info("   - All mappings status: 'pending' (awaiting sync verification)");

            Log::info('PrestaShopAttributeMappingSeeder: Seeding completed successfully', [
                'group_mappings_created' => $groupMappingsCreated,
                'value_mappings_created' => $valueMappingsCreated,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            $this->command->error("❌ Seeding failed: {$e->getMessage()}");
            Log::error('PrestaShopAttributeMappingSeeder: Seeding failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
