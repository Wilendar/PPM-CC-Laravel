<?php

namespace App\Events\PrestaShop;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * CategoryPreviewReady Event
 *
 * ETAP_07 FAZA 3D: Category Import Preview System - Events Layer
 *
 * Purpose: Notify UI that category preview is ready to display
 *
 * Workflow:
 * 1. AnalyzeMissingCategories job creates CategoryPreview record
 * 2. Event dispatched with preview details
 * 3. Broadcasting pushes notification to user's browser
 * 4. Frontend displays CategoryPreviewModal with tree
 * 5. User approves/rejects → triggers BulkCreateCategories
 *
 * Broadcasting:
 * - Channel: shop.{shopId} (private channel dla specific shop)
 * - Payload: job_id, shop_id, preview_id
 * - Frontend listener: Echo.private('shop.{shopId}').listen('CategoryPreviewReady', ...)
 *
 * Usage:
 * ```php
 * event(new CategoryPreviewReady($jobId, $shopId, $previewId));
 * ```
 *
 * @package App\Events\PrestaShop
 * @version 1.0
 * @since ETAP_07 FAZA 3D
 */
class CategoryPreviewReady implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Job ID (UUID) linking to JobProgress
     *
     * @var string
     */
    public string $jobId;

    /**
     * PrestaShop shop ID
     *
     * @var int
     */
    public int $shopId;

    /**
     * CategoryPreview record ID
     *
     * @var int
     */
    public int $previewId;

    /**
     * Create a new event instance
     *
     * @param string $jobId Job progress UUID
     * @param int $shopId PrestaShop shop ID
     * @param int $previewId CategoryPreview record ID
     */
    public function __construct(string $jobId, int $shopId, int $previewId)
    {
        $this->jobId = $jobId;
        $this->shopId = $shopId;
        $this->previewId = $previewId;
    }

    /**
     * Get the channels the event should broadcast on
     *
     * Uses private channel dla security (only authenticated users can listen)
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('shop.' . $this->shopId),
        ];
    }

    /**
     * Get the data to broadcast
     *
     * Frontend receives this payload via Laravel Echo
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'job_id' => $this->jobId,
            'shop_id' => $this->shopId,
            'preview_id' => $this->previewId,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name
     *
     * Frontend listens for: .listen('CategoryPreviewReady', ...)
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'CategoryPreviewReady';
    }
}
