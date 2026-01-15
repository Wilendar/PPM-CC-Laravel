<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ShopVariantsSyncCompleted Event
 *
 * ETAP_05c: Broadcasted when shop variant sync job completes
 *
 * Used to:
 * - Notify Livewire component to refresh data
 * - Unblock UI fields after sync
 * - Show success/failure notification
 *
 * @package App\Events
 */
class ShopVariantsSyncCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $productId;
    public int $shopId;
    public bool $success;

    /**
     * Create a new event instance.
     */
    public function __construct(int $productId, int $shopId, bool $success)
    {
        $this->productId = $productId;
        $this->shopId = $shopId;
        $this->success = $success;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("product.{$this->productId}.shop.{$this->shopId}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'shop-variants-sync-completed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'product_id' => $this->productId,
            'shop_id' => $this->shopId,
            'success' => $this->success,
        ];
    }
}
