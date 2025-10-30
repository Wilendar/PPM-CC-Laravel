<?php

namespace App\Events;

use App\Models\AttributeType;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * AttributeTypeCreated Event
 *
 * Dispatched when new AttributeType is created
 * Triggers auto-sync with all active PrestaShop shops
 *
 * ETAP_05b Phase 2.1: Events & Listeners - Variant System
 *
 * @package App\Events
 */
class AttributeTypeCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Newly created AttributeType
     */
    public AttributeType $attributeType;

    /**
     * Create new event instance
     */
    public function __construct(AttributeType $attributeType)
    {
        $this->attributeType = $attributeType;
    }
}
