<?php

declare(strict_types=1);

namespace App\Events\Media;

use App\DTOs\Media\MediaSyncStatusDTO;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * MediaSyncCompleted Event
 *
 * Dispatched when media sync with PrestaShop completes.
 * Triggers:
 * - UI refresh with new sync status
 * - Live label updates
 * - Notification to user
 *
 * ETAP_07d Phase 1.5.3: Core Infrastructure - Events
 *
 * @package App\Events\Media
 * @version 1.0
 */
class MediaSyncCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Sync direction constants
     */
    public const DIRECTION_UPLOAD = 'upload';
    public const DIRECTION_DOWNLOAD = 'download';
    public const DIRECTION_BIDIRECTIONAL = 'bidirectional';

    /**
     * Create new event instance
     *
     * @param int $mediaId Media record ID
     * @param string $direction Sync direction (upload/download/bidirectional)
     * @param bool $success Whether sync completed successfully
     * @param MediaSyncStatusDTO $syncStatus Current sync status
     * @param array $affectedShopIds Shop IDs affected by this sync
     * @param string|null $errorMessage Error message if sync failed
     * @param int $duration Sync duration in seconds
     */
    public function __construct(
        public readonly int $mediaId,
        public readonly string $direction,
        public readonly bool $success,
        public readonly MediaSyncStatusDTO $syncStatus,
        public readonly array $affectedShopIds = [],
        public readonly ?string $errorMessage = null,
        public readonly int $duration = 0,
    ) {}

    /**
     * Create successful upload event
     *
     * @param int $mediaId Media ID
     * @param MediaSyncStatusDTO $status Sync status
     * @param array $shopIds Affected shop IDs
     * @param int $duration Duration in seconds
     * @return self
     */
    public static function uploadSuccess(
        int $mediaId,
        MediaSyncStatusDTO $status,
        array $shopIds,
        int $duration = 0
    ): self {
        return new self(
            mediaId: $mediaId,
            direction: self::DIRECTION_UPLOAD,
            success: true,
            syncStatus: $status,
            affectedShopIds: $shopIds,
            duration: $duration,
        );
    }

    /**
     * Create successful download event
     *
     * @param int $mediaId Media ID
     * @param MediaSyncStatusDTO $status Sync status
     * @param array $shopIds Source shop IDs
     * @param int $duration Duration in seconds
     * @return self
     */
    public static function downloadSuccess(
        int $mediaId,
        MediaSyncStatusDTO $status,
        array $shopIds,
        int $duration = 0
    ): self {
        return new self(
            mediaId: $mediaId,
            direction: self::DIRECTION_DOWNLOAD,
            success: true,
            syncStatus: $status,
            affectedShopIds: $shopIds,
            duration: $duration,
        );
    }

    /**
     * Create failed sync event
     *
     * @param int $mediaId Media ID
     * @param string $direction Sync direction
     * @param string $errorMessage Error description
     * @param MediaSyncStatusDTO $status Current sync status
     * @return self
     */
    public static function failed(
        int $mediaId,
        string $direction,
        string $errorMessage,
        MediaSyncStatusDTO $status
    ): self {
        return new self(
            mediaId: $mediaId,
            direction: $direction,
            success: false,
            syncStatus: $status,
            errorMessage: $errorMessage,
        );
    }

    /**
     * Check if was upload operation
     *
     * @return bool
     */
    public function wasUpload(): bool
    {
        return $this->direction === self::DIRECTION_UPLOAD;
    }

    /**
     * Check if was download operation
     *
     * @return bool
     */
    public function wasDownload(): bool
    {
        return $this->direction === self::DIRECTION_DOWNLOAD;
    }

    /**
     * Get human-readable direction label
     *
     * @return string
     */
    public function getDirectionLabel(): string
    {
        return match ($this->direction) {
            self::DIRECTION_UPLOAD => 'PPM -> PrestaShop',
            self::DIRECTION_DOWNLOAD => 'PrestaShop -> PPM',
            self::DIRECTION_BIDIRECTIONAL => 'Dwukierunkowa',
            default => 'Nieznana',
        };
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags(): array
    {
        $tags = [
            'media',
            'media:' . $this->mediaId,
            'media:sync',
            'sync:' . $this->direction,
        ];

        if (!$this->success) {
            $tags[] = 'sync:failed';
        }

        foreach ($this->affectedShopIds as $shopId) {
            $tags[] = 'shop:' . $shopId;
        }

        return $tags;
    }

    /**
     * Convert to array for broadcasting/logging
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'media_id' => $this->mediaId,
            'direction' => $this->direction,
            'direction_label' => $this->getDirectionLabel(),
            'success' => $this->success,
            'sync_status' => $this->syncStatus->toArray(),
            'affected_shop_ids' => $this->affectedShopIds,
            'error_message' => $this->errorMessage,
            'duration' => $this->duration,
        ];
    }
}
