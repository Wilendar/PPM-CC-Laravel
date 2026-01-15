<?php

declare(strict_types=1);

namespace App\Events\Media;

use App\Models\Media;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * MediaUploaded Event
 *
 * Dispatched when a new media file is uploaded to PPM.
 * Triggers:
 * - Thumbnail generation job
 * - WebP conversion job
 * - Optional auto-sync to PrestaShop
 *
 * ETAP_07d Phase 1.5.1: Core Infrastructure - Events
 *
 * @package App\Events\Media
 * @version 1.0
 */
class MediaUploaded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create new event instance
     *
     * @param Media $media Newly uploaded media record
     * @param bool $generateThumbnails Whether to trigger thumbnail generation
     * @param bool $convertToWebp Whether to trigger WebP conversion
     * @param bool $autoSyncToPrestaShop Whether to auto-sync to PrestaShop
     * @param int|null $uploadedByUserId User who uploaded (null for system)
     */
    public function __construct(
        public readonly Media $media,
        public readonly bool $generateThumbnails = true,
        public readonly bool $convertToWebp = true,
        public readonly bool $autoSyncToPrestaShop = false,
        public readonly ?int $uploadedByUserId = null,
    ) {}

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags(): array
    {
        return [
            'media',
            'media:' . $this->media->id,
            'mediable:' . $this->media->mediable_type . ':' . $this->media->mediable_id,
        ];
    }
}
