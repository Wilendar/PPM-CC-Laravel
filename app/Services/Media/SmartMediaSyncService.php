<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\DTOs\Media\MediaSyncDiff;
use App\Models\Media;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShop8Client;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * SmartMediaSyncService - Intelligent diff-based media sync to PrestaShop
 *
 * Instead of delete-all + re-upload-all, calculates minimal operations:
 * - Skip if no changes (most common case)
 * - Upload only new images
 * - Delete only removed images
 * - Patch cover if changed
 * - Update positions via custom PS module
 *
 * Preserves PS image IDs across saves (no more ID inflation).
 */
class SmartMediaSyncService
{
    private const RATE_LIMIT_DELAY_MS = 500;

    public function __construct(
        private readonly MediaSyncService $mediaSyncService,
        private readonly MediaDiffCalculator $diffCalculator,
    ) {}

    /**
     * Sync images using smart diff strategy
     *
     * @return array{uploaded: int, deleted: int, cover_set: bool, positions_updated: int, skipped: bool, errors: array}
     */
    public function syncImages(
        Product $product,
        PrestaShopShop $shop,
        Collection $selectedMedia,
        array $pendingChanges = []
    ): array {
        $result = [
            'uploaded' => 0,
            'deleted' => 0,
            'cover_set' => false,
            'positions_updated' => 0,
            'skipped' => false,
            'errors' => [],
        ];
        $startTime = microtime(true);

        $psProductId = $this->getPrestaShopProductId($product, $shop);
        if (!$psProductId) {
            $result['errors'][] = 'Product not mapped to PrestaShop';
            return $result;
        }

        // Calculate diff
        $diff = $this->diffCalculator->calculateDiff($selectedMedia, $shop->id);

        Log::info('[SMART MEDIA SYNC] Diff calculated', array_merge(
            ['product_id' => $product->id, 'shop_id' => $shop->id, 'ps_product_id' => $psProductId],
            $diff->toLogArray()
        ));

        // No changes → skip entirely
        if ($diff->isEmpty()) {
            $result['skipped'] = true;
            return $result;
        }

        // Mutex lock per product (same pattern as replaceAllImages)
        $lockKey = "media_sync_product_{$product->id}";
        $lock = Cache::lock($lockKey, 120);

        try {
            $lock->block(60);

            $client = new PrestaShop8Client($shop);

            // STEP 1: Delete removed images
            if ($diff->toDelete->isNotEmpty()) {
                $result['deleted'] = $this->deleteImages(
                    $diff->toDelete, $shop, $psProductId, $client
                );
            }

            // STEP 2: Upload new images
            if ($diff->toUpload->isNotEmpty()) {
                $result['uploaded'] = $this->uploadImages(
                    $diff->toUpload, $shop, $psProductId, $client, $result
                );
            }

            // STEP 3: Set cover if changed
            if ($diff->coverChanged) {
                $result['cover_set'] = $this->updateCover(
                    $diff, $shop, $psProductId, $client, $selectedMedia
                );
            }

            // STEP 4: Update positions via PS module (if order changed)
            if ($diff->orderChanged && !empty($diff->positionUpdates)) {
                $result['positions_updated'] = $this->updatePositions(
                    $shop, $psProductId, $diff->positionUpdates, $client
                );
            }

        } catch (LockTimeoutException $e) {
            $result['errors'][] = 'Media sync locked - another sync is in progress';
            Log::warning('[SMART MEDIA SYNC] Lock timeout', [
                'product_id' => $product->id, 'shop_id' => $shop->id,
            ]);
            return $result;
        } catch (\Exception $e) {
            $result['errors'][] = 'Smart sync failed: ' . $e->getMessage();
            Log::error('[SMART MEDIA SYNC] Failed', [
                'product_id' => $product->id, 'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);
        } finally {
            optional($lock)->release();
        }

        $duration = round(microtime(true) - $startTime, 2);
        Log::info('[SMART MEDIA SYNC] Completed', array_merge($result, [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'duration_seconds' => $duration,
        ]));

        return $result;
    }

    /**
     * Delete specific images from PrestaShop and clear their mappings
     */
    private function deleteImages(
        Collection $toDelete,
        PrestaShopShop $shop,
        int $psProductId,
        PrestaShop8Client $client
    ): int {
        $deleted = 0;
        $storeKey = "store_{$shop->id}";

        foreach ($toDelete as $media) {
            $mapping = $media->prestashop_mapping[$storeKey] ?? [];
            $psImageId = $mapping['ps_image_id'] ?? null;

            if (!$psImageId) {
                continue;
            }

            try {
                $this->throttle($shop->id);
                $client->deleteProductImage($psProductId, (int) $psImageId);
                $deleted++;

                // Clear mapping
                $media->setPrestaShopMapping($shop->id, [
                    'ps_image_id' => null,
                    'cleared_at' => now()->toIso8601String(),
                ]);

                Log::debug('[SMART MEDIA SYNC] Deleted image', [
                    'media_id' => $media->id, 'ps_image_id' => $psImageId,
                ]);
            } catch (\Exception $e) {
                Log::warning('[SMART MEDIA SYNC] Failed to delete image', [
                    'media_id' => $media->id, 'ps_image_id' => $psImageId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $deleted;
    }

    /**
     * Upload new images to PrestaShop and save mappings
     */
    private function uploadImages(
        Collection $toUpload,
        PrestaShopShop $shop,
        int $psProductId,
        PrestaShop8Client $client,
        array &$result
    ): int {
        $uploaded = 0;

        // Sort: primary first, then by sort_order
        $ordered = $toUpload->sortByDesc('is_primary')->sortBy('sort_order');

        foreach ($ordered as $media) {
            $psImageId = $this->uploadSingleImage($media, $shop, $psProductId, $client);

            if ($psImageId) {
                $uploaded++;

                // Save mapping with synced_sort_order
                $media->setPrestaShopMapping($shop->id, [
                    'ps_product_id' => $psProductId,
                    'ps_image_id' => $psImageId,
                    'is_cover' => $media->is_primary,
                    'synced_at' => now()->toIso8601String(),
                    'synced_sort_order' => $media->sort_order ?? 0,
                ]);
                $media->sync_status = 'synced';
                $media->save();
            } else {
                $result['errors'][] = "Upload failed for media ID {$media->id}";
            }
        }

        return $uploaded;
    }

    /**
     * Upload single image to PrestaShop
     *
     * Reuses ensureJpegForUpload and cleanupTempJpeg patterns from MediaSyncService
     */
    private function uploadSingleImage(
        Media $media,
        PrestaShopShop $shop,
        int $psProductId,
        PrestaShop8Client $client
    ): ?int {
        $filePath = Storage::disk('public')->path($media->file_path);

        if (!file_exists($filePath)) {
            Log::warning('[SMART MEDIA SYNC] File not found', [
                'media_id' => $media->id, 'file_path' => $filePath,
            ]);
            return null;
        }

        // WebP/PNG → JPEG conversion for older PS versions
        $uploadPath = $this->ensureJpegForUpload($filePath, $shop);

        try {
            $this->throttle($shop->id);
            $response = $client->uploadProductImage($psProductId, $uploadPath, $media->file_name);

            $this->cleanupTempJpeg($filePath, $uploadPath);

            if (!$response || !isset($response['id'])) {
                return null;
            }

            return (int) $response['id'];

        } catch (\Exception $e) {
            $this->cleanupTempJpeg($filePath, $uploadPath);
            Log::error('[SMART MEDIA SYNC] Upload failed', [
                'media_id' => $media->id, 'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Update cover image on PrestaShop
     */
    private function updateCover(
        MediaSyncDiff $diff,
        PrestaShopShop $shop,
        int $psProductId,
        PrestaShop8Client $client,
        Collection $selectedMedia
    ): bool {
        // If we have a known PS image ID for the new cover, use it
        if ($diff->newCoverPsImageId) {
            $this->throttle($shop->id);
            $coverSet = $client->setProductImageCover($psProductId, $diff->newCoverPsImageId);

            if ($coverSet) {
                // Update mapping to reflect cover status
                $primaryMedia = $selectedMedia->firstWhere('is_primary', true);
                if ($primaryMedia) {
                    $primaryMedia->setPrestaShopMapping($shop->id, [
                        'is_cover' => true,
                    ]);
                }
            }

            return $coverSet;
        }

        // Cover image was just uploaded → find its new PS ID from mapping
        $primaryMedia = $selectedMedia->firstWhere('is_primary', true);
        if (!$primaryMedia) {
            return false;
        }

        $storeKey = "store_{$shop->id}";
        // Re-read from DB to get fresh mapping after upload
        $primaryMedia->refresh();
        $mapping = $primaryMedia->prestashop_mapping[$storeKey] ?? [];
        $psImageId = $mapping['ps_image_id'] ?? null;

        if (!$psImageId) {
            return false;
        }

        $this->throttle($shop->id);
        $coverSet = $client->setProductImageCover($psProductId, (int) $psImageId);

        if ($coverSet) {
            $primaryMedia->setPrestaShopMapping($shop->id, [
                'is_cover' => true,
            ]);
        }

        return $coverSet;
    }

    /**
     * Update image positions via custom PS module endpoint
     *
     * @return int Number of positions updated (0 if module not available)
     */
    private function updatePositions(
        PrestaShopShop $shop,
        int $psProductId,
        array $positionUpdates,
        PrestaShop8Client $client
    ): int {
        try {
            $success = $client->updateImagePositions($psProductId, $positionUpdates);

            if ($success) {
                // Update synced_sort_order in mappings
                $this->updateSyncedSortOrders($shop->id, $positionUpdates);
                return count($positionUpdates);
            }

            return 0;
        } catch (\Exception $e) {
            Log::warning('[SMART MEDIA SYNC] Position update failed (module may not be installed)', [
                'shop_id' => $shop->id, 'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Update synced_sort_order in mappings after successful position sync
     */
    private function updateSyncedSortOrders(int $shopId, array $positionUpdates): void
    {
        $storeKey = "store_{$shopId}";

        // Find media by PS image IDs and update their synced_sort_order
        $allMedia = Media::whereNotNull('prestashop_mapping')
            ->get()
            ->filter(function (Media $media) use ($storeKey, $positionUpdates) {
                $mapping = $media->prestashop_mapping[$storeKey] ?? [];
                $psImageId = $mapping['ps_image_id'] ?? null;
                return $psImageId && isset($positionUpdates[$psImageId]);
            });

        foreach ($allMedia as $media) {
            $mapping = $media->prestashop_mapping[$storeKey] ?? [];
            $psImageId = $mapping['ps_image_id'];
            $media->setPrestaShopMapping($shopId, [
                'synced_sort_order' => $positionUpdates[$psImageId],
            ]);
        }
    }

    /**
     * Get PrestaShop product ID (delegates to MediaSyncService pattern)
     */
    private function getPrestaShopProductId(Product $product, PrestaShopShop $shop): ?int
    {
        $shopData = $product->shopData()
            ->where('shop_id', $shop->id)
            ->first();

        return $shopData?->prestashop_product_id;
    }

    /**
     * Rate limiting between API calls
     */
    private function throttle(int $shopId): void
    {
        $cacheKey = "media_sync_last_request_{$shopId}";
        $lastRequest = Cache::get($cacheKey);

        if ($lastRequest) {
            $elapsed = (microtime(true) - $lastRequest) * 1000;
            if ($elapsed < self::RATE_LIMIT_DELAY_MS) {
                usleep((int) ((self::RATE_LIMIT_DELAY_MS - $elapsed) * 1000));
            }
        }

        Cache::put($cacheKey, microtime(true), 60);
    }

    /**
     * Ensure file is JPEG for upload (delegates to same logic as MediaSyncService)
     */
    private function ensureJpegForUpload(string $filePath, PrestaShopShop $shop): string
    {
        if (!file_exists($filePath)) {
            return $filePath;
        }

        $psVersion = $shop->prestashop_version_exact ?? $shop->prestashop_version ?? '8.0.0';
        if (version_compare($psVersion, '8.2.1', '>=')) {
            return $filePath;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $actualMime = $finfo->file($filePath);

        if ($actualMime === 'image/jpeg') {
            return $filePath;
        }

        if (in_array($actualMime, ['image/webp', 'image/png', 'image/gif'])) {
            try {
                $tempDir = storage_path('app/temp');
                if (!is_dir($tempDir)) {
                    mkdir($tempDir, 0755, true);
                }

                $tempPath = $tempDir . '/' . uniqid('jpeg_') . '.jpg';
                $sourceImage = match ($actualMime) {
                    'image/webp' => imagecreatefromwebp($filePath),
                    'image/png' => imagecreatefrompng($filePath),
                    'image/gif' => imagecreatefromgif($filePath),
                    default => null,
                };

                if (!$sourceImage) {
                    return $filePath;
                }

                if ($actualMime !== 'image/webp') {
                    $width = imagesx($sourceImage);
                    $height = imagesy($sourceImage);
                    $jpegImage = imagecreatetruecolor($width, $height);
                    $white = imagecolorallocate($jpegImage, 255, 255, 255);
                    imagefill($jpegImage, 0, 0, $white);
                    imagecopy($jpegImage, $sourceImage, 0, 0, 0, 0, $width, $height);
                    imagedestroy($sourceImage);
                    $sourceImage = $jpegImage;
                }

                $success = imagejpeg($sourceImage, $tempPath, 90);
                imagedestroy($sourceImage);

                return ($success && file_exists($tempPath)) ? $tempPath : $filePath;

            } catch (\Exception $e) {
                return $filePath;
            }
        }

        return $filePath;
    }

    /**
     * Clean up temporary JPEG after upload
     */
    private function cleanupTempJpeg(string $originalPath, string $usedPath): void
    {
        if ($originalPath !== $usedPath && str_contains($usedPath, 'temp') && file_exists($usedPath)) {
            @unlink($usedPath);
        }
    }
}
