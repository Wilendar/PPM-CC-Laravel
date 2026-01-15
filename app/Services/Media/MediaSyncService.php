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

            // Upload to PrestaShop
            $this->throttle($shop->id);
            $response = $client->uploadProductImage($psProductId, $filePath, $media->file_name);

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

        Log::info('[MEDIA REPLACE ALL] Starting replace all strategy', [
            'product_id' => $product->id,
            'ps_product_id' => $psProductId,
            'shop_id' => $shop->id,
            'media_count' => $selectedMedia->count(),
        ]);

        try {
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

                try {
                    $response = $client->uploadProductImage($psProductId, $filePath, $media->file_name);

                    if (!$response || !isset($response['id'])) {
                        $result['errors'][] = "Upload failed for media ID {$media->id}";
                        continue;
                    }

                    $psImageId = (int) $response['id'];
                    $result['uploaded']++;

                    // Save mapping
                    $media->setPrestaShopMapping($shop->id, [
                        'ps_product_id' => $psProductId,
                        'ps_image_id' => $psImageId,
                        'is_cover' => $media->is_primary,
                        'synced_at' => now()->toIso8601String(),
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

        } catch (\Exception $e) {
            $result['errors'][] = 'Replace all failed: ' . $e->getMessage();
            Log::error('[MEDIA REPLACE ALL] Strategy failed', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
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
}
