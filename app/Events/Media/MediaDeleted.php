<?php

declare(strict_types=1);

namespace App\Events\Media;

use App\Models\Media;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * MediaDeleted Event
 *
 * Dispatched when a media file is deleted from PPM.
 * Triggers:
 * - File cleanup from storage
 * - Thumbnail cleanup
 * - Optional deletion from PrestaShop
 *
 * ETAP_07d Phase 1.5.2: Core Infrastructure - Events
 *
 * @package App\Events\Media
 * @version 1.0
 */
class MediaDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Delete source options
     */
    public const SOURCE_PPM_ONLY = 'ppm_only';
    public const SOURCE_PRESTASHOP_ONLY = 'prestashop_only';
    public const SOURCE_BOTH = 'both';

    /**
     * Create new event instance
     *
     * @param int $mediaId Media record ID (before deletion)
     * @param string $filePath File path in storage
     * @param string $mediableType Class name of parent model
     * @param int $mediableId ID of parent model
     * @param string $deleteSource Where to delete from (ppm_only, prestashop_only, both)
     * @param array $prestaShopMapping PrestaShop mapping data for cleanup
     * @param int|null $deletedByUserId User who deleted (null for system)
     */
    public function __construct(
        public readonly int $mediaId,
        public readonly string $filePath,
        public readonly string $mediableType,
        public readonly int $mediableId,
        public readonly string $deleteSource = self::SOURCE_PPM_ONLY,
        public readonly array $prestaShopMapping = [],
        public readonly ?int $deletedByUserId = null,
    ) {}

    /**
     * Create from Media model before deletion
     *
     * @param Media $media Media to be deleted
     * @param string $deleteSource Delete source option
     * @param int|null $userId User performing deletion
     * @return self
     */
    public static function fromMedia(
        Media $media,
        string $deleteSource = self::SOURCE_PPM_ONLY,
        ?int $userId = null
    ): self {
        return new self(
            mediaId: $media->id,
            filePath: $media->file_path,
            mediableType: $media->mediable_type,
            mediableId: $media->mediable_id,
            deleteSource: $deleteSource,
            prestaShopMapping: $media->prestashop_mapping ?? [],
            deletedByUserId: $userId,
        );
    }

    /**
     * Check if should delete from PPM storage
     *
     * @return bool
     */
    public function shouldDeleteFromPpm(): bool
    {
        return in_array($this->deleteSource, [self::SOURCE_PPM_ONLY, self::SOURCE_BOTH], true);
    }

    /**
     * Check if should delete from PrestaShop
     *
     * @return bool
     */
    public function shouldDeleteFromPrestaShop(): bool
    {
        return in_array($this->deleteSource, [self::SOURCE_PRESTASHOP_ONLY, self::SOURCE_BOTH], true)
            && !empty($this->prestaShopMapping);
    }

    /**
     * Get PrestaShop image IDs to delete
     *
     * @return array [shop_id => image_id]
     */
    public function getPrestaShopImageIds(): array
    {
        $imageIds = [];
        foreach ($this->prestaShopMapping as $shopId => $data) {
            if (isset($data['image_id'])) {
                $imageIds[(int) $shopId] = (int) $data['image_id'];
            }
        }
        return $imageIds;
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags(): array
    {
        return [
            'media',
            'media:' . $this->mediaId,
            'media:delete',
            'mediable:' . $this->mediableType . ':' . $this->mediableId,
        ];
    }
}
