<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\DTOs\Media\MediaSyncStatusDTO;
use App\Events\Media\MediaSyncCompleted;
use App\Models\Media;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShop8Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Cache\LockTimeoutException;

/**
 * MediaSyncService - PrestaShop Media Synchronization
 *
 * Handles bi-directional sync of product images between PPM and PrestaShop:
 * - Pull missing images from PrestaShop
 * - Push images to PrestaShop
 * - Verify sync status (live labels)
 * - Delete from PrestaShop
 *
 * ETAP_07d Phase 1.2.3: Services Layer
 * Max 300 lines (zgodnie z CLAUDE.md)
 *
 * @package App\Services\Media
 * @version 1.0
 */
class MediaSyncService
{
    /**
     * Rate limit delay in milliseconds
     */
    private const RATE_LIMIT_DELAY_MS = 500;

    /**
     * Retry attempts for API calls
     */
    private const RETRY_ATTEMPTS = 3;

    /**
     * Create new MediaSyncService instance
     *
     * @param MediaStorageService $storage Storage service
     */
    public function __construct(
        private readonly MediaStorageService $storage,
    ) {}

    /**
     * Pull missing images from PrestaShop to PPM
     *
     * IMPROVED LOGIC (2025-12-15):
     * 1. Clean up orphaned Media records (file doesn't exist on disk)
     * 2. Check for shop conflict (images from different shop)
     * 3. Always import if: no images OR only orphaned records
     * 4. Replace orphaned records with fresh downloads
     *
     * @param Product $product PPM Product
     * @param PrestaShopShop $shop PrestaShop shop
     * @return array ['downloaded' => int, 'skipped' => int, 'errors' => array, 'shop_conflict' => bool, 'other_shop_ids' => array]
     */
    public function pullFromPrestaShop(Product $product, PrestaShopShop $shop): array
    {
        $result = ['downloaded' => 0, 'skipped' => 0, 'errors' => [], 'shop_conflict' => false, 'other_shop_ids' => []];
        $startTime = microtime(true);

        try {
            $client = $this->getClient($shop);
            $psProductId = $this->getPrestaShopProductId($product, $shop);

            if (!$psProductId) {
                $result['errors'][] = 'Product not mapped to PrestaShop';
                return $result;
            }

            // STEP 1: Clean up orphaned Media (records without files)
            $orphanedCount = $this->cleanupOrphanedMedia($product);
            if ($orphanedCount > 0) {
                Log::info('[MEDIA SYNC] Cleaned up orphaned media', [
                    'product_id' => $product->id,
                    'orphaned_count' => $orphanedCount,
                ]);
            }

            // STEP 2: Check for shop conflict (images from different shop)
            $conflict = $this->detectShopConflict($product, $shop->id);
            if ($conflict['has_conflict']) {
                $result['shop_conflict'] = true;
                $result['other_shop_ids'] = $conflict['other_shop_ids'];
                Log::info('[MEDIA SYNC] Shop conflict detected - images from other shops exist', [
                    'product_id' => $product->id,
                    'current_shop_id' => $shop->id,
                    'other_shop_ids' => $conflict['other_shop_ids'],
                ]);
                // Don't auto-import on conflict - return to UI for user decision
                return $result;
            }

            // Get images from PrestaShop
            $this->throttle($shop->id);
            $psImages = $client->getProductImages($psProductId);

            if (empty($psImages)) {
                Log::info('[MEDIA SYNC] No images in PrestaShop', [
                    'product_id' => $product->id,
                    'ps_product_id' => $psProductId,
                ]);
                return $result;
            }

            // Process each image
            foreach ($psImages as $psImage) {
                $imageId = is_array($psImage) ? ($psImage['id'] ?? null) : (int) $psImage;
                if (!$imageId) continue;

                // Check if already exists WITH valid file
                if ($this->imageExistsWithFile($product, $shop->id, $imageId)) {
                    $result['skipped']++;
                    continue;
                }

                // Download image
                $this->throttle($shop->id);
                $imageData = $client->downloadProductImage($psProductId, $imageId);

                if (!$imageData) {
                    $result['errors'][] = "Failed to download image {$imageId} (PrestaShop may have missing file)";
                    continue;
                }

                // Store in PPM
                $index = $this->storage->getNextAvailableIndex($product);
                if (!$index) {
                    $result['errors'][] = 'Max images limit reached';
                    break;
                }

                $stored = $this->storage->storeContents($imageData, $product, $index, 'jpg');

                // Verify file was actually stored
                if (!Storage::disk('public')->exists($stored['path'])) {
                    $result['errors'][] = "Failed to store image {$imageId} on disk";
                    Log::error('[MEDIA SYNC] File storage failed', [
                        'product_id' => $product->id,
                        'image_id' => $imageId,
                        'path' => $stored['path'],
                    ]);
                    continue;
                }

                // Create Media record
                $media = Media::create([
                    'mediable_type' => Product::class,
                    'mediable_id' => $product->id,
                    'file_name' => $stored['filename'],
                    'original_name' => "prestashop_{$imageId}.jpg",
                    'file_path' => $stored['path'],
                    'file_size' => $stored['size'],
                    'mime_type' => 'image/jpeg',
                    'sort_order' => $index,
                    'is_primary' => $result['downloaded'] === 0 && $product->media()->count() === 0,
                    'sync_status' => 'synced',
                    'is_active' => true,
                ]);

                // Save mapping
                $media->setPrestaShopMapping($shop->id, [
                    'ps_product_id' => $psProductId,
                    'ps_image_id' => $imageId,
                    'synced_at' => now()->toIso8601String(),
                ]);

                $result['downloaded']++;
            }

            // Dispatch event
            $duration = (int) (microtime(true) - $startTime);
            event(MediaSyncCompleted::downloadSuccess(
                mediaId: $product->media()->first()?->id ?? 0,
                status: MediaSyncStatusDTO::synced(0, ['shop_' . $shop->id => ['synced' => true]]),
                shopIds: [$shop->id],
                duration: $duration
            ));

        } catch (\Exception $e) {
            Log::error('[MEDIA SYNC] Pull failed', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Clean up orphaned Media records (database record exists but file doesn't)
     *
     * @param Product $product
     * @return int Number of orphaned records deleted
     */
    public function cleanupOrphanedMedia(Product $product): int
    {
        $orphaned = $product->media()->get()->filter(fn($m) => !$m->fileExists());
        $count = $orphaned->count();

        foreach ($orphaned as $media) {
            Log::info('[MEDIA SYNC] Removing orphaned media record', [
                'media_id' => $media->id,
                'product_id' => $product->id,
                'file_path' => $media->file_path,
            ]);
            $media->delete();
        }

        return $count;
    }

    /**
     * Detect if product has images from a different shop (conflict)
     *
     * @param Product $product
     * @param int $currentShopId
     * @return array ['has_conflict' => bool, 'other_shop_ids' => array]
     */
    public function detectShopConflict(Product $product, int $currentShopId): array
    {
        $otherShopIds = [];
        $validMedia = $product->media()->get()->filter(fn($m) => $m->fileExists());

        foreach ($validMedia as $media) {
            $mapping = $media->prestashop_mapping ?? [];
            foreach ($mapping as $key => $data) {
                if (str_starts_with($key, 'store_')) {
                    $shopId = (int) str_replace('store_', '', $key);
                    if ($shopId !== $currentShopId && !empty($data['ps_image_id'])) {
                        $otherShopIds[] = $shopId;
                    }
                }
            }
        }

        $otherShopIds = array_unique($otherShopIds);

        return [
            'has_conflict' => count($otherShopIds) > 0 && $validMedia->count() > 0,
            'other_shop_ids' => $otherShopIds,
        ];
    }

    /**
     * Check if image exists in PPM WITH valid file on disk
     */
    private function imageExistsWithFile(Product $product, int $shopId, int $psImageId): bool
    {
        $media = $product->media()
            ->whereJsonContains("prestashop_mapping->store_{$shopId}->ps_image_id", $psImageId)
            ->first();

        return $media && $media->fileExists();
    }

    /**
     * Push image to PrestaShop
     *
     * @param Media $media PPM Media
     * @param PrestaShopShop $shop Target shop
     * @return bool Success
     */
    public function pushToPrestaShop(Media $media, PrestaShopShop $shop): bool
    {
        $startTime = microtime(true);

        try {
            if (!$media->fileExists()) {
                Log::error('[MEDIA SYNC] File not found', ['media_id' => $media->id]);
                return false;
            }

            $product = $media->mediable;
            if (!$product instanceof Product) {
                Log::error('[MEDIA SYNC] Media not attached to product', ['media_id' => $media->id]);
                return false;
            }

            $psProductId = $this->getPrestaShopProductId($product, $shop);
            if (!$psProductId) {
                Log::error('[MEDIA SYNC] Product not mapped', ['product_id' => $product->id]);
                return false;
            }

            $client = $this->getClient($shop);
            // FIX 2025-12-01: Use 'public' disk - media files are stored there
            // Previous: Storage::path() used default 'local' disk = WRONG PATH
            $filePath = Storage::disk('public')->path($media->file_path);

            // FIX 2026-02-05: Convert WebP/PNG to JPEG before upload (conditional)
            // PrestaShop < 8.2.1 doesn't properly handle WebP with .jpg extension
            // PrestaShop >= 8.2.1 has native WebP support - skip conversion
            $uploadPath = $this->ensureJpegForUpload($filePath, $shop);

            // Upload to PrestaShop
            $this->throttle($shop->id);
            $response = $client->uploadProductImage($psProductId, $uploadPath, $media->file_name);

            // Clean up temporary JPEG if created
            $this->cleanupTempJpeg($filePath, $uploadPath);

            if (!$response || !isset($response['id'])) {
                $media->markSyncError("store_{$shop->id}", 'Upload failed - no image ID returned');
                return false;
            }

            // Update mapping
            $media->setPrestaShopMapping($shop->id, [
                'ps_product_id' => $psProductId,
                'ps_image_id' => $response['id'],
                'is_cover' => $media->is_primary,
                'synced_at' => now()->toIso8601String(),
            ]);
            $media->sync_status = 'synced';
            $media->save();

            // Dispatch event
            $duration = (int) (microtime(true) - $startTime);
            event(MediaSyncCompleted::uploadSuccess(
                mediaId: $media->id,
                status: MediaSyncStatusDTO::fromMedia($media->fresh()),
                shopIds: [$shop->id],
                duration: $duration
            ));

            Log::info('[MEDIA SYNC] Push successful', [
                'media_id' => $media->id,
                'ps_image_id' => $response['id'],
                'shop_id' => $shop->id,
            ]);

            return true;

        } catch (\Exception $e) {
            // Clean up temporary file on error
            if (isset($uploadPath) && isset($filePath)) {
                $this->cleanupTempJpeg($filePath, $uploadPath);
            }

            Log::error('[MEDIA SYNC] Push failed', [
                'media_id' => $media->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            $media->markSyncError("store_{$shop->id}", $e->getMessage());
            return false;
        }
    }

    /**
     * Delete image from PrestaShop
     *
     * @param Media $media PPM Media
     * @param PrestaShopShop $shop Target shop
     * @return bool Success
     */
    public function deleteFromPrestaShop(Media $media, PrestaShopShop $shop): bool
    {
        try {
            $mapping = $media->getPrestaShopMapping($shop->id);

            if (!$mapping || !isset($mapping['ps_image_id'], $mapping['ps_product_id'])) {
                Log::warning('[MEDIA SYNC] No mapping for delete', [
                    'media_id' => $media->id,
                    'shop_id' => $shop->id,
                ]);
                return true; // Nothing to delete
            }

            $client = $this->getClient($shop);

            $this->throttle($shop->id);
            $client->deleteProductImage($mapping['ps_product_id'], $mapping['ps_image_id']);

            // Clear mapping
            $media->setPrestaShopMapping($shop->id, [
                'ps_image_id' => null,
                'deleted_at' => now()->toIso8601String(),
            ]);

            Log::info('[MEDIA SYNC] Deleted from PrestaShop', [
                'media_id' => $media->id,
                'ps_image_id' => $mapping['ps_image_id'],
                'shop_id' => $shop->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('[MEDIA SYNC] Delete failed', [
                'media_id' => $media->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Push multiple images to PrestaShop (BULK)
     *
     * ETAP_07d Phase 4.4: Bulk media push for PushMediaToPrestaShop Job
     *
     * @param Product $product PPM Product
     * @param PrestaShopShop $shop Target shop
     * @param \Illuminate\Support\Collection $mediaCollection Media collection
     * @return array ['uploaded' => int, 'skipped' => int, 'errors' => array, 'cover_set' => bool]
     */
    public function pushBulkToPrestaShop(Product $product, PrestaShopShop $shop, $mediaCollection): array
    {
        $result = ['uploaded' => 0, 'skipped' => 0, 'errors' => [], 'cover_set' => false];
        $startTime = microtime(true);

        $psProductId = $this->getPrestaShopProductId($product, $shop);
        if (!$psProductId) {
            $result['errors'][] = 'Product not mapped to PrestaShop';
            Log::error('[MEDIA SYNC BULK] Product not mapped', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
            ]);
            return $result;
        }

        Log::info('[MEDIA SYNC BULK] Starting bulk push', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'media_count' => $mediaCollection->count(),
        ]);

        foreach ($mediaCollection as $media) {
            // Check if already synced to this shop
            $mapping = $media->getPrestaShopMapping($shop->id);
            if ($mapping && isset($mapping['ps_image_id']) && !empty($mapping['ps_image_id'])) {
                $result['skipped']++;
                continue;
            }

            // Push single media
            $success = $this->pushToPrestaShop($media, $shop);

            if ($success) {
                $result['uploaded']++;

                // Set cover if primary
                if ($media->is_primary && !$result['cover_set']) {
                    $result['cover_set'] = true;
                }
            } else {
                $result['errors'][] = "Failed to upload media ID {$media->id}";
            }
        }

        $duration = (int) (microtime(true) - $startTime);
        Log::info('[MEDIA SYNC BULK] Bulk push completed', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'uploaded' => $result['uploaded'],
            'skipped' => $result['skipped'],
            'errors_count' => count($result['errors']),
            'duration_seconds' => $duration,
        ]);

        return $result;
    }

    /**
     * Replace ALL images on PrestaShop with selected ones
     *
     * ETAP_07d: "Replace All" strategy - deletes ALL existing images and uploads selected
     * Solves: duplicates issue + correct cover image based on PPM is_primary
     *
     * @param Product $product PPM Product
     * @param PrestaShopShop $shop Target shop
     * @param \Illuminate\Support\Collection $selectedMedia Media to upload (only checked ones)
     * @return array ['deleted' => int, 'uploaded' => int, 'errors' => array, 'cover_set' => bool]
     */
    public function replaceAllImages(Product $product, PrestaShopShop $shop, $selectedMedia): array
    {
        $result = ['deleted' => 0, 'uploaded' => 0, 'errors' => [], 'cover_set' => false];
        $startTime = microtime(true);

        $psProductId = $this->getPrestaShopProductId($product, $shop);
        if (!$psProductId) {
            $result['errors'][] = 'Product not mapped to PrestaShop';
            Log::error('[MEDIA REPLACE ALL] Product not mapped', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
            ]);
            return $result;
        }

        // FIX 2026-02-05: Add mutex/lock to prevent race condition
        // When multiple shops sync same product simultaneously, mappings can get corrupted
        // Lock per product ensures only one REPLACE ALL runs at a time for given product
        $lockKey = "media_sync_product_{$product->id}";
        $lock = Cache::lock($lockKey, 120); // 120 seconds max lock time

        Log::info('[MEDIA REPLACE ALL] Acquiring lock for product', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'lock_key' => $lockKey,
        ]);

        try {
            // Wait up to 60 seconds to acquire lock
            // If another shop sync is running, we wait for it to finish
            $lock->block(60);

            Log::info('[MEDIA REPLACE ALL] Lock acquired, starting replace all strategy', [
                'product_id' => $product->id,
                'ps_product_id' => $psProductId,
                'shop_id' => $shop->id,
                'media_count' => $selectedMedia->count(),
            ]);

            $client = $this->getClient($shop);

            // STEP 1: Delete ALL existing images from PrestaShop
            $this->throttle($shop->id);
            $result['deleted'] = $client->deleteAllProductImages($psProductId);

            Log::info('[MEDIA REPLACE ALL] Deleted existing images', [
                'product_id' => $product->id,
                'ps_product_id' => $psProductId,
                'deleted' => $result['deleted'],
            ]);

            // STEP 2: Clear ALL mappings for this shop (all product media)
            $allMedia = Media::where('mediable_type', Product::class)
                ->where('mediable_id', $product->id)
                ->get();

            foreach ($allMedia as $media) {
                $media->setPrestaShopMapping($shop->id, [
                    'ps_image_id' => null,
                    'cleared_at' => now()->toIso8601String(),
                ]);
            }

            // STEP 3: Upload ONLY selected images (with correct order)
            $coverImageId = null;
            $primaryMedia = null;

            // Find primary image first
            $primaryMedia = $selectedMedia->firstWhere('is_primary', true);

            // Upload in order: primary first, then others
            $orderedMedia = $selectedMedia->sortByDesc('is_primary')->sortBy('sort_order');

            foreach ($orderedMedia as $media) {
                $this->throttle($shop->id);

                // Get file path from public disk
                $filePath = Storage::disk('public')->path($media->file_path);

                if (!file_exists($filePath)) {
                    $result['errors'][] = "File not found for media ID {$media->id}";
                    continue;
                }

                // FIX 2026-02-05: Convert WebP/PNG to JPEG before upload (conditional)
                // PrestaShop < 8.2.1 doesn't properly handle WebP with .jpg extension
                // PrestaShop >= 8.2.1 has native WebP support - skip conversion
                $uploadPath = $this->ensureJpegForUpload($filePath, $shop);

                try {
                    $response = $client->uploadProductImage($psProductId, $uploadPath, $media->file_name);

                    // Clean up temporary JPEG if created
                    $this->cleanupTempJpeg($filePath, $uploadPath);

                    if (!$response || !isset($response['id'])) {
                        $result['errors'][] = "Upload failed for media ID {$media->id}";
                        continue;
                    }

                    $psImageId = (int) $response['id'];
                    $result['uploaded']++;

                    // Save mapping (including synced_sort_order for smart diff detection)
                    $media->setPrestaShopMapping($shop->id, [
                        'ps_product_id' => $psProductId,
                        'ps_image_id' => $psImageId,
                        'is_cover' => $media->is_primary,
                        'synced_at' => now()->toIso8601String(),
                        'synced_sort_order' => $media->sort_order ?? 0,
                    ]);
                    $media->sync_status = 'synced';
                    $media->save();

                    // Track cover image (primary)
                    if ($media->is_primary) {
                        $coverImageId = $psImageId;
                    }

                    Log::debug('[MEDIA REPLACE ALL] Uploaded image', [
                        'media_id' => $media->id,
                        'ps_image_id' => $psImageId,
                        'is_primary' => $media->is_primary,
                    ]);

                } catch (\Exception $e) {
                    // Clean up temporary file on error too
                    $this->cleanupTempJpeg($filePath, $uploadPath);

                    $result['errors'][] = "Upload failed for media ID {$media->id}: " . $e->getMessage();
                    Log::error('[MEDIA REPLACE ALL] Upload failed', [
                        'media_id' => $media->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // STEP 4: Set correct cover image (if primary exists)
            if ($coverImageId) {
                $this->throttle($shop->id);
                $coverSet = $client->setProductImageCover($psProductId, $coverImageId);
                $result['cover_set'] = $coverSet;

                Log::info('[MEDIA REPLACE ALL] Cover image set', [
                    'product_id' => $product->id,
                    'cover_image_id' => $coverImageId,
                    'success' => $coverSet,
                ]);
            }

        } catch (LockTimeoutException $e) {
            // FIX 2026-02-05: Another sync is already running for this product
            $result['errors'][] = 'Media sync locked - another sync is in progress for this product. Please retry in a moment.';
            Log::warning('[MEDIA REPLACE ALL] Lock timeout - another sync in progress', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'lock_key' => $lockKey,
            ]);
            return $result;
        } catch (\Exception $e) {
            $result['errors'][] = 'Replace all failed: ' . $e->getMessage();
            Log::error('[MEDIA REPLACE ALL] Strategy failed', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);
        } finally {
            // FIX 2026-02-05: Always release lock
            optional($lock)->release();
            Log::debug('[MEDIA REPLACE ALL] Lock released', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
            ]);
        }

        $duration = (int) (microtime(true) - $startTime);
        Log::info('[MEDIA REPLACE ALL] Completed', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'deleted' => $result['deleted'],
            'uploaded' => $result['uploaded'],
            'errors_count' => count($result['errors']),
            'cover_set' => $result['cover_set'],
            'duration_seconds' => $duration,
        ]);

        return $result;
    }

    /**
     * Verify sync status for media
     *
     * @param Media $media PPM Media
     * @param PrestaShopShop $shop Target shop
     * @return MediaSyncStatusDTO
     */
    public function verifySync(Media $media, PrestaShopShop $shop): MediaSyncStatusDTO
    {
        $mapping = $media->getPrestaShopMapping($shop->id);

        if (!$mapping || !isset($mapping['ps_image_id'])) {
            return MediaSyncStatusDTO::pending($media->id);
        }

        try {
            $client = $this->getClient($shop);
            $psProductId = $mapping['ps_product_id'] ?? null;

            if (!$psProductId) {
                return MediaSyncStatusDTO::error($media->id, 'Missing PS product ID');
            }

            $this->throttle($shop->id);
            $psImages = $client->getProductImages($psProductId);

            $imageIds = array_map(
                fn($img) => is_array($img) ? ($img['id'] ?? 0) : (int) $img,
                $psImages
            );

            if (in_array($mapping['ps_image_id'], $imageIds, true)) {
                return MediaSyncStatusDTO::synced($media->id, [
                    $shop->id => [
                        'synced' => true,
                        'ps_image_id' => $mapping['ps_image_id'],
                        'is_cover' => $mapping['is_cover'] ?? false,
                    ],
                ]);
            }

            return MediaSyncStatusDTO::error($media->id, 'Image not found in PrestaShop');

        } catch (\Exception $e) {
            return MediaSyncStatusDTO::error($media->id, $e->getMessage());
        }
    }

    /**
     * Get PrestaShop client for shop
     */
    private function getClient(PrestaShopShop $shop): PrestaShop8Client
    {
        return new PrestaShop8Client($shop);
    }

    /**
     * Get PrestaShop product ID from product_shop table
     */
    private function getPrestaShopProductId(Product $product, PrestaShopShop $shop): ?int
    {
        // Use the shopData relationship to get prestashop_product_id
        $shopData = $product->shopData()
            ->where('shop_id', $shop->id)
            ->first();

        return $shopData?->prestashop_product_id;
    }

    /**
     * Check if image already exists in PPM
     */
    private function imageExistsInPpm(Product $product, int $shopId, int $psImageId): bool
    {
        return $product->media()
            ->whereJsonContains("prestashop_mapping->store_{$shopId}->ps_image_id", $psImageId)
            ->exists();
    }

    /**
     * Apply rate limiting between API calls
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
     * Ensure file is a proper JPEG for PrestaShop upload (conditional)
     *
     * FIX 2026-02-05: PrestaShop < 8.2.1 doesn't accept WebP with .jpg extension
     * PrestaShop >= 8.2.1 has native WebP support - skip conversion for those versions.
     *
     * This method checks actual file format and converts WebP to JPEG only if needed.
     *
     * @param string $filePath Original file path
     * @param PrestaShopShop $shop Shop to check version for WebP support
     * @return string Path to JPEG file (may be temporary converted file)
     */
    private function ensureJpegForUpload(string $filePath, PrestaShopShop $shop): string
    {
        if (!file_exists($filePath)) {
            return $filePath;
        }

        // FIX 2026-02-05: Skip conversion for PrestaShop >= 8.2.1 (native WebP support)
        // Priority: exact version (manual input) > detected version > default
        $psVersion = $shop->prestashop_version_exact ?? $shop->prestashop_version ?? '8.0.0';
        $supportsWebP = version_compare($psVersion, '8.2.1', '>=');

        if ($supportsWebP) {
            Log::debug('[MEDIA SYNC] Skipping WebP conversion - PS >= 8.2.1 supports native WebP', [
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
                'ps_version' => $psVersion,
                'file_path' => $filePath,
            ]);
            return $filePath;
        }

        // Detect actual MIME type (not based on extension!)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $actualMime = $finfo->file($filePath);

        Log::debug('[MEDIA SYNC] File format detection', [
            'file_path' => $filePath,
            'extension' => pathinfo($filePath, PATHINFO_EXTENSION),
            'actual_mime' => $actualMime,
            'shop_id' => $shop->id,
            'ps_version' => $psVersion,
        ]);

        // If already proper JPEG, return as-is
        if ($actualMime === 'image/jpeg') {
            return $filePath;
        }

        // If WebP (common case) - convert to JPEG for older PrestaShop versions
        if ($actualMime === 'image/webp' || $actualMime === 'image/png' || $actualMime === 'image/gif') {
            try {
                $tempDir = storage_path('app/temp');
                if (!is_dir($tempDir)) {
                    mkdir($tempDir, 0755, true);
                }

                $tempPath = $tempDir . '/' . uniqid('jpeg_') . '.jpg';

                // FIX 2026-02-05: Use native PHP GD functions instead of Intervention Image
                // This ensures compatibility on all servers without additional dependencies
                $sourceImage = match ($actualMime) {
                    'image/webp' => imagecreatefromwebp($filePath),
                    'image/png' => imagecreatefrompng($filePath),
                    'image/gif' => imagecreatefromgif($filePath),
                    default => null,
                };

                if (!$sourceImage) {
                    Log::error('[MEDIA SYNC] Failed to load source image for conversion', [
                        'file_path' => $filePath,
                        'actual_mime' => $actualMime,
                        'shop_id' => $shop->id,
                    ]);
                    return $filePath;
                }

                // For PNG with transparency, fill background with white
                if ($actualMime === 'image/png' || $actualMime === 'image/gif') {
                    $width = imagesx($sourceImage);
                    $height = imagesy($sourceImage);
                    $jpegImage = imagecreatetruecolor($width, $height);
                    $white = imagecolorallocate($jpegImage, 255, 255, 255);
                    imagefill($jpegImage, 0, 0, $white);
                    imagecopy($jpegImage, $sourceImage, 0, 0, 0, 0, $width, $height);
                    imagedestroy($sourceImage);
                    $sourceImage = $jpegImage;
                }

                // Save as JPEG with quality 90
                $success = imagejpeg($sourceImage, $tempPath, 90);
                imagedestroy($sourceImage);

                if (!$success || !file_exists($tempPath)) {
                    Log::error('[MEDIA SYNC] Failed to save converted JPEG', [
                        'file_path' => $filePath,
                        'temp_path' => $tempPath,
                        'shop_id' => $shop->id,
                    ]);
                    return $filePath;
                }

                Log::info('[MEDIA SYNC] Converted image to JPEG for PrestaShop < 8.2.1', [
                    'original_path' => $filePath,
                    'original_mime' => $actualMime,
                    'converted_path' => $tempPath,
                    'converted_size' => filesize($tempPath),
                    'shop_id' => $shop->id,
                    'ps_version' => $psVersion,
                ]);

                return $tempPath;

            } catch (\Exception $e) {
                Log::error('[MEDIA SYNC] Failed to convert image to JPEG', [
                    'file_path' => $filePath,
                    'error' => $e->getMessage(),
                    'shop_id' => $shop->id,
                ]);
                // Fall back to original file
                return $filePath;
            }
        }

        // Unknown format - return original
        return $filePath;
    }

    /**
     * Clean up temporary JPEG file after upload
     *
     * @param string $originalPath Original file path
     * @param string $usedPath Path that was used for upload
     */
    private function cleanupTempJpeg(string $originalPath, string $usedPath): void
    {
        // Only delete if it's a temp file we created
        if ($originalPath !== $usedPath && str_contains($usedPath, 'temp') && file_exists($usedPath)) {
            @unlink($usedPath);
            Log::debug('[MEDIA SYNC] Cleaned up temporary JPEG', [
                'temp_path' => $usedPath,
            ]);
        }
    }

}
