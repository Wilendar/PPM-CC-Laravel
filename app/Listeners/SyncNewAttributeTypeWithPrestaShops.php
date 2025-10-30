<?php

namespace App\Listeners;

use App\Events\AttributeTypeCreated;
use App\Jobs\PrestaShop\SyncAttributeGroupWithPrestaShop;
use App\Models\PrestaShopShop;
use Illuminate\Support\Facades\Log;

/**
 * SyncNewAttributeTypeWithPrestaShops Listener
 *
 * Auto-dispatches sync jobs when new AttributeType is created
 * Sends sync jobs to all active PrestaShop shops
 *
 * ETAP_05b Phase 2.1: Events & Listeners - Variant System
 *
 * @package App\Listeners
 */
class SyncNewAttributeTypeWithPrestaShops
{
    /**
     * Handle the event
     */
    public function handle(AttributeTypeCreated $event): void
    {
        Log::info('AttributeTypeCreated event received', [
            'attribute_type_id' => $event->attributeType->id,
            'attribute_type_name' => $event->attributeType->name,
        ]);

        // Get all active PrestaShop shops
        $shops = PrestaShopShop::where('is_active', true)->get();

        if ($shops->isEmpty()) {
            Log::warning('No active PrestaShop shops found for auto-sync', [
                'attribute_type_id' => $event->attributeType->id,
            ]);
            return;
        }

        // Dispatch sync job for each shop
        foreach ($shops as $shop) {
            dispatch(new SyncAttributeGroupWithPrestaShop($event->attributeType, $shop));

            Log::info('Dispatched SyncAttributeGroupWithPrestaShop job', [
                'attribute_type_id' => $event->attributeType->id,
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
            ]);
        }

        Log::info('AttributeTypeCreated auto-sync dispatched', [
            'attribute_type_id' => $event->attributeType->id,
            'shops_count' => $shops->count(),
        ]);
    }
}
