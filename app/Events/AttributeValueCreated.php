<?php

namespace App\Events;

use App\Models\AttributeValue;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * AttributeValueCreated Event
 *
 * Dispatched when new AttributeValue is created
 * Triggers auto-sync with all active PrestaShop shops
 *
 * ETAP_05b Phase 2.1: Events & Listeners - Variant System
 *
 * @package App\Events
 */
class AttributeValueCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Newly created AttributeValue
     */
    public AttributeValue $attributeValue;

    /**
     * Create new event instance
     */
    public function __construct(AttributeValue $attributeValue)
    {
        $this->attributeValue = $attributeValue;
    }
}
