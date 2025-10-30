<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AttributeType;
use App\Models\AttributeValue;
use App\Models\PrestaShopShop;
use App\Jobs\PrestaShop\SyncAttributeGroupWithPrestaShop;

class TestAttributeSync extends Command
{
    protected $signature = 'test:attribute-sync';
    protected $description = 'E2E Test 2: Create test AttributeType and sync to PrestaShop';

    public function handle()
    {
        $this->info('=== E2E TEST 2: Export TO PrestaShop ===');

        // Step 1: Create AttributeType
        $this->info('[1/4] Creating AttributeType...');
        $timestamp = date('YmdHis');
        $attributeType = AttributeType::create([
            'name' => "Rozmiar_Test_E2E_{$timestamp}",
            'code' => "rozmiar_test_e2e_{$timestamp}",
            'display_type' => 'dropdown',
            'position' => 0,
            'is_active' => true,
        ]);

        $this->line("✅ AttributeType created: ID = {$attributeType->id}, Name = {$attributeType->name}");

        // Step 2: Create AttributeValues
        $this->info('[2/4] Creating AttributeValues...');
        $values = ['S_Test', 'M_Test', 'L_Test', 'XL_Test'];
        foreach ($values as $index => $valueName) {
            $attributeValue = AttributeValue::create([
                'attribute_type_id' => $attributeType->id,
                'code' => strtolower($valueName),
                'label' => $valueName,
                'position' => $index + 1,
                'is_active' => true,
            ]);

            $this->line("✅ AttributeValue created: ID = {$attributeValue->id}, Label = {$attributeValue->label}");
        }

        // Step 3: Get PrestaShop shop
        $this->info('[3/4] Finding PrestaShop shop...');
        $shop = PrestaShopShop::where('url', 'LIKE', '%dev.mpptrade.pl%')->first();

        if (!$shop) {
            $this->error('❌ PrestaShop shop not found (dev.mpptrade.pl)');
            $this->info('Available shops:');
            PrestaShopShop::all()->each(function($s) {
                $this->line("  - ID={$s->id}, Name={$s->name}, URL={$s->url}");
            });
            return 1;
        }

        $this->line("✅ PrestaShop shop found: ID = {$shop->id}, Name = {$shop->name}");

        // Step 4: Dispatch sync job
        $this->info('[4/4] Dispatching sync job...');
        SyncAttributeGroupWithPrestaShop::dispatch($attributeType, $shop);

        $this->line("✅ Job dispatched: SyncAttributeGroupWithPrestaShop");
        $this->line("   AttributeType ID: {$attributeType->id}");
        $this->line("   Shop ID: {$shop->id}");

        $this->info('');
        $this->info('=== TEST 2 PREPARATION COMPLETE ===');
        $this->info('Next steps:');
        $this->line('1. Run: php artisan queue:work --once');
        $this->line('2. Check logs: tail -100 storage/logs/laravel.log | grep SyncAttributeGroup');

        return 0;
    }
}
