<?php

namespace App\Listeners;

use App\Events\AttributeValueCreated;
use App\Jobs\PrestaShop\SyncAttributeValueWithPrestaShop;
use App\Models\PrestaShopShop;
use Illuminate\Support\Facades\Log;

/**
 * SyncNewAttributeValueWithPrestaShops Listener
 *
 * Auto-dispatches sync jobs when new AttributeValue is created
 * Sends sync jobs to all active PrestaShop shops
 *
 * ETAP_05b Phase 2.1: Events & Listeners - Variant System
 *
 * @package App\Listeners
 */
class SyncNewAttributeValueWithPrestaShops
{
    /**
     * Handle the event
     */
    public function handle(AttributeValueCreated $event): void
    {
        Log::info('AttributeValueCreated event received', [
            'attribute_value_id' => $event->attributeValue->id,
            'attribute_value_label' => $event->attributeValue->value,
            'attribute_type_id' => $event->attributeValue->attribute_type_id,
        ]);

        // Get all active PrestaShop shops
        $shops = PrestaShopShop::where('is_active', true)->get();

        if ($shops->isEmpty()) {
            Log::warning('No active PrestaShop shops found for auto-sync', [
                'attribute_value_id' => $event->attributeValue->id,
            ]);
            return;
        }

        // Dispatch sync job for each shop
        foreach ($shops as $shop) {
            dispatch(new SyncAttributeValueWithPrestaShop($event->attributeValue, $shop));

            Log::info('Dispatched SyncAttributeValueWithPrestaShop job', [
                'attribute_value_id' => $event->attributeValue->id,
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
            ]);
        }

        Log::info('AttributeValueCreated auto-sync dispatched', [
            'attribute_value_id' => $event->attributeValue->id,
            'shops_count' => $shops->count(),
        ]);
    }
}
