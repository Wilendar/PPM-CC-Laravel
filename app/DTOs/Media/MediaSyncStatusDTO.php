<?php

declare(strict_types=1);

namespace App\DTOs\Media;

use App\Models\Media;
use App\Models\Shop;

/**
 * MediaSyncStatusDTO - Data Transfer Object for PrestaShop Sync Status
 *
 * Represents the synchronization status of a media file with PrestaShop stores.
 * Provides live label data and sync verification information.
 *
 * ETAP_07d Phase 1.4.2: Core Infrastructure - DTOs
 *
 * @package App\DTOs\Media
 * @version 1.0
 */
final readonly class MediaSyncStatusDTO
{
    /**
     * Sync status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_SYNCED = 'synced';
    public const STATUS_ERROR = 'error';
    public const STATUS_IGNORED = 'ignored';
    public const STATUS_DOWNLOADING = 'downloading';
    public const STATUS_UPLOADING = 'uploading';

    /**
     * Create new MediaSyncStatusDTO instance
     *
     * @param int $mediaId Media record ID in PPM
     * @param string $status Current sync status
     * @param array $shopStatuses Status per shop [shop_id => ['synced' => bool, 'ps_image_id' => int|null, 'is_cover' => bool]]
     * @param int|null $prestaShopImageId Primary PrestaShop image ID (if synced)
     * @param bool $existsInPpm File exists in PPM storage
     * @param bool $existsInPrestaShop Image exists in at least one PrestaShop store
     * @param string|null $lastSyncAt Last sync timestamp
     * @param string|null $errorMessage Error message if status is error
     * @param int $downloadProgress Download progress percentage (0-100)
     * @param int $uploadProgress Upload progress percentage (0-100)
     */
    public function __construct(
        public int $mediaId,
        public string $status,
        public array $shopStatuses = [],
        public ?int $prestaShopImageId = null,
        public bool $existsInPpm = true,
        public bool $existsInPrestaShop = false,
        public ?string $lastSyncAt = null,
        public ?string $errorMessage = null,
        public int $downloadProgress = 0,
        public int $uploadProgress = 0,
    ) {}

    /**
     * Create DTO from Media model
     *
     * @param Media $media Media model instance
     * @return self
     */
    public static function fromMedia(Media $media): self
    {
        $mapping = $media->prestashop_mapping ?? [];
        $shopStatuses = [];

        foreach ($mapping as $shopId => $data) {
            $shopStatuses[(int) $shopId] = [
                'synced' => !empty($data['image_id']),
                'ps_image_id' => $data['image_id'] ?? null,
                'is_cover' => $data['is_cover'] ?? false,
                'synced_at' => $data['synced_at'] ?? null,
            ];
        }

        return new self(
            mediaId: $media->id,
            status: $media->sync_status ?? self::STATUS_PENDING,
            shopStatuses: $shopStatuses,
            prestaShopImageId: $mapping['primary_image_id'] ?? null,
            existsInPpm: $media->fileExists(),
            existsInPrestaShop: !empty($shopStatuses),
            lastSyncAt: $media->updated_at?->toIso8601String(),
            errorMessage: $mapping['error'] ?? null,
        );
    }

    /**
     * Create pending status for new upload
     *
     * @param int $mediaId Media ID
     * @return self
     */
    public static function pending(int $mediaId): self
    {
        return new self(
            mediaId: $mediaId,
            status: self::STATUS_PENDING,
            existsInPpm: true,
            existsInPrestaShop: false,
        );
    }

    /**
     * Create downloading status
     *
     * @param int $mediaId Media ID
     * @param int $progress Download progress (0-100)
     * @return self
     */
    public static function downloading(int $mediaId, int $progress = 0): self
    {
        return new self(
            mediaId: $mediaId,
            status: self::STATUS_DOWNLOADING,
            existsInPpm: false,
            existsInPrestaShop: true,
            downloadProgress: min(100, max(0, $progress)),
        );
    }

    /**
     * Create uploading status
     *
     * @param int $mediaId Media ID
     * @param int $progress Upload progress (0-100)
     * @return self
     */
    public static function uploading(int $mediaId, int $progress = 0): self
    {
        return new self(
            mediaId: $mediaId,
            status: self::STATUS_UPLOADING,
            existsInPpm: true,
            existsInPrestaShop: false,
            uploadProgress: min(100, max(0, $progress)),
        );
    }

    /**
     * Create synced status
     *
     * @param int $mediaId Media ID
     * @param array $shopStatuses Shop sync data
     * @return self
     */
    public static function synced(int $mediaId, array $shopStatuses): self
    {
        return new self(
            mediaId: $mediaId,
            status: self::STATUS_SYNCED,
            shopStatuses: $shopStatuses,
            existsInPpm: true,
            existsInPrestaShop: true,
            lastSyncAt: now()->toIso8601String(),
        );
    }

    /**
     * Create error status
     *
     * @param int $mediaId Media ID
     * @param string $errorMessage Error description
     * @return self
     */
    public static function error(int $mediaId, string $errorMessage): self
    {
        return new self(
            mediaId: $mediaId,
            status: self::STATUS_ERROR,
            existsInPpm: true,
            errorMessage: $errorMessage,
        );
    }

    /**
     * Check if synced to specific shop
     *
     * @param int $shopId Shop ID
     * @return bool
     */
    public function isSyncedToShop(int $shopId): bool
    {
        return isset($this->shopStatuses[$shopId])
            && $this->shopStatuses[$shopId]['synced'] === true;
    }

    /**
     * Get PrestaShop image ID for specific shop
     *
     * @param int $shopId Shop ID
     * @return int|null
     */
    public function getPrestaShopImageIdForShop(int $shopId): ?int
    {
        return $this->shopStatuses[$shopId]['ps_image_id'] ?? null;
    }

    /**
     * Check if image is cover for specific shop
     *
     * @param int $shopId Shop ID
     * @return bool
     */
    public function isCoverForShop(int $shopId): bool
    {
        return $this->shopStatuses[$shopId]['is_cover'] ?? false;
    }

    /**
     * Get list of synced shop IDs
     *
     * @return array
     */
    public function getSyncedShopIds(): array
    {
        return array_keys(array_filter(
            $this->shopStatuses,
            fn($status) => $status['synced'] ?? false
        ));
    }

    /**
     * Check if needs sync (exists in PPM but not in PrestaShop)
     *
     * @return bool
     */
    public function needsUpload(): bool
    {
        return $this->existsInPpm && !$this->existsInPrestaShop;
    }

    /**
     * Check if needs download (exists in PrestaShop but not in PPM)
     *
     * @return bool
     */
    public function needsDownload(): bool
    {
        return !$this->existsInPpm && $this->existsInPrestaShop;
    }

    /**
     * Check if is in progress (downloading or uploading)
     *
     * @return bool
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, [self::STATUS_DOWNLOADING, self::STATUS_UPLOADING], true);
    }

    /**
     * Check if has error
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return $this->status === self::STATUS_ERROR;
    }

    /**
     * Get progress percentage (download or upload)
     *
     * @return int
     */
    public function getProgress(): int
    {
        if ($this->status === self::STATUS_DOWNLOADING) {
            return $this->downloadProgress;
        }
        if ($this->status === self::STATUS_UPLOADING) {
            return $this->uploadProgress;
        }
        return $this->status === self::STATUS_SYNCED ? 100 : 0;
    }

    /**
     * Get status label for UI display
     *
     * @return string
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Oczekuje',
            self::STATUS_SYNCED => 'Zsynchronizowane',
            self::STATUS_ERROR => 'Blad',
            self::STATUS_IGNORED => 'Pominiete',
            self::STATUS_DOWNLOADING => 'Pobieranie...',
            self::STATUS_UPLOADING => 'Wysylanie...',
            default => 'Nieznany',
        };
    }

    /**
     * Get status CSS class for UI styling
     *
     * @return string
     */
    public function getStatusClass(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'status-pending',
            self::STATUS_SYNCED => 'status-synced',
            self::STATUS_ERROR => 'status-error',
            self::STATUS_IGNORED => 'status-ignored',
            self::STATUS_DOWNLOADING, self::STATUS_UPLOADING => 'status-progress',
            default => 'status-unknown',
        };
    }

    /**
     * Convert to array for API/JSON response
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'media_id' => $this->mediaId,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'status_class' => $this->getStatusClass(),
            'shop_statuses' => $this->shopStatuses,
            'prestashop_image_id' => $this->prestaShopImageId,
            'exists_in_ppm' => $this->existsInPpm,
            'exists_in_prestashop' => $this->existsInPrestaShop,
            'last_sync_at' => $this->lastSyncAt,
            'error_message' => $this->errorMessage,
            'progress' => $this->getProgress(),
            'needs_upload' => $this->needsUpload(),
            'needs_download' => $this->needsDownload(),
            'synced_shop_ids' => $this->getSyncedShopIds(),
        ];
    }
}
