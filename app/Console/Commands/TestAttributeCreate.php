<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AttributeType;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopAttributeSyncService;
use Illuminate\Support\Facades\DB;

class TestAttributeCreate extends Command
{
    protected $signature = 'test:attribute-create {attribute_type_id} {shop_id}';
    protected $description = 'E2E Test 2b: Create AttributeType in PrestaShop';

    public function handle(PrestaShopAttributeSyncService $syncService)
    {
        $this->info('=== E2E TEST 2B: Create IN PrestaShop ===');

        $attributeTypeId = (int) $this->argument('attribute_type_id');
        $shopId = (int) $this->argument('shop_id');

        // Step 1: Load entities
        $this->info('[1/4] Loading AttributeType and Shop...');
        $attributeType = AttributeType::find($attributeTypeId);
        $shop = PrestaShopShop::find($shopId);

        if (!$attributeType) {
            $this->error("❌ AttributeType ID={$attributeTypeId} not found");
            return 1;
        }

        if (!$shop) {
            $this->error("❌ PrestaShop Shop ID={$shopId} not found");
            return 1;
        }

        $this->line("✅ AttributeType: ID = {$attributeType->id}, Name = {$attributeType->name}");
        $this->line("✅ Shop: ID = {$shop->id}, Name = {$shop->name}");

        // Step 2: Check current sync status
        $this->info('[2/4] Checking current sync status...');
        $mapping = DB::table('prestashop_attribute_group_mapping')
            ->where('attribute_type_id', $attributeType->id)
            ->where('prestashop_shop_id', $shop->id)
            ->first();

        if ($mapping) {
            $this->line("✅ Current status: {$mapping->sync_status}");
            $this->line("   PrestaShop ID: " . ($mapping->prestashop_attribute_group_id ?? 'NULL'));
        } else {
            $this->line("⚠️  No mapping exists yet");
        }

        // Step 3: Create in PrestaShop
        $this->info('[3/4] Creating AttributeType in PrestaShop...');
        try {
            $psGroupId = $syncService->createAttributeGroupInPS($attributeType->id, $shop->id);
            $this->line("✅ Created in PrestaShop: ps_product_option_id = {$psGroupId}");
        } catch (\Exception $e) {
            $this->error("❌ Creation failed: {$e->getMessage()}");
            return 1;
        }

        // Step 4: Verify mapping updated
        $this->info('[4/4] Verifying mapping update...');
        $updatedMapping = DB::table('prestashop_attribute_group_mapping')
            ->where('attribute_type_id', $attributeType->id)
            ->where('prestashop_shop_id', $shop->id)
            ->first();

        if ($updatedMapping && $updatedMapping->sync_status === 'synced') {
            $this->line("✅ Mapping updated successfully");
            $this->line("   Status: {$updatedMapping->sync_status}");
            $this->line("   PrestaShop ID: {$updatedMapping->prestashop_attribute_group_id}");
            $this->line("   Last Synced: {$updatedMapping->last_synced_at}");
        } else {
            $this->error("❌ Mapping not updated correctly");
            return 1;
        }

        $this->info('');
        $this->info('=== TEST 2B COMPLETE ===');
        $this->line('✅ AttributeType successfully created and synced in PrestaShop');

        return 0;
    }
}
