<?php

namespace App\Jobs\Media;

use App\Models\Media;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\JobProgressService;
use App\Services\Media\MediaSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * PushMediaToPrestaShop Job
 *
 * Upload PPM images to PrestaShop:
 * - Upload via PrestaShop API
 * - Update prestashop_mapping JSONB
 * - Set cover image if is_primary
 * - Handle multi-store mapping
 * - Progress tracking per image
 *
 * ETAP_07d: Media System Implementation
 * Max ~200 lines (zgodnie z CLAUDE.md)
 *
 * @package App\Jobs\Media
 */
class PushMediaToPrestaShop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Product ID to push media for
     */
    public int $productId;

    /**
     * Optional specific media IDs (null = all media for product)
     */
    public ?array $mediaIds;

    /**
     * PrestaShop shop ID
     */
    public int $shopId;

    /**
     * User ID who triggered push
     */
    public ?int $userId;

    /**
     * Number of times job may be attempted
     */
    public int $tries = 3;

    /**
     * Maximum seconds job can run
     */
    public int $timeout = 300; // 5 minutes

    /**
     * Create new job instance
     *
     * @param int $productId Product ID
     * @param int $shopId PrestaShop shop ID
     * @param array|null $mediaIds Optional specific media IDs to push
     * @param int|null $userId User ID who triggered push
     */
    public function __construct(int $productId, int $shopId, ?array $mediaIds = null, ?int $userId = null)
    {
        $this->productId = $productId;
        $this->shopId = $shopId;
        $this->mediaIds = $mediaIds;
        $this->userId = $userId;

        // Use sync connection for immediate execution (no queue worker needed on shared hosting)
        $this->onConnection('sync');
    }

    /**
     * Execute the job
     *
     * @param MediaSyncService $syncService Media sync service
     * @param JobProgressService $progressService Progress tracking service
     */
    public function handle(MediaSyncService $syncService, JobProgressService $progressService): void
    {
        $product = Product::find($this->productId);
        $shop = PrestaShopShop::find($this->shopId);

        if (!$product || !$shop) {
            Log::error('[MEDIA PUSH TO PS] Product or Shop not found', [
                'product_id' => $this->productId,
                'shop_id' => $this->shopId,
            ]);
            return;
        }

        Log::info('[MEDIA PUSH TO PS] Starting media push to PrestaShop', [
            'product_id' => $this->productId,
            'product_sku' => $product->sku,
            'shop_id' => $this->shopId,
            'shop_name' => $shop->name,
            'media_ids' => $this->mediaIds,
            'user_id' => $this->userId,
        ]);

        // Verify shop is active
        if (!$shop->is_active) {
            Log::error('[MEDIA PUSH TO PS] Shop not active', [
                'shop_id' => $this->shopId,
                'shop_name' => $shop->name,
            ]);
            return;
        }

        // Get media to push
        $mediaQuery = Media::where('mediable_type', Product::class)
            ->where('mediable_id', $this->productId)
            ->active();

        if ($this->mediaIds) {
            $mediaQuery->whereIn('id', $this->mediaIds);
        }

        $mediaCollection = $mediaQuery->orderBy('is_primary', 'desc')
            ->orderBy('sort_order', 'asc')
            ->get();

        if ($mediaCollection->isEmpty()) {
            Log::warning('[MEDIA PUSH TO PS] No media found to push', [
                'product_id' => $this->productId,
                'shop_id' => $this->shopId,
                'media_ids' => $this->mediaIds,
            ]);
            return;
        }

        // Create progress tracking
        // FIX (2025-12-02): Generate UUID for sync connection jobs where getJobId() returns empty
        $jobId = $this->job?->getJobId();
        if (empty($jobId)) {
            $jobId = 'sync_' . Str::uuid()->toString();
        }

        $progressId = $progressService->createJobProgress(
            $jobId,
            $shop,
            'media_push',
            $mediaCollection->count()
        );

        try {
            // Push images to PrestaShop (BULK method - ETAP_07d Phase 4.4)
            $result = $syncService->pushBulkToPrestaShop($product, $shop, $mediaCollection);

            // Update progress
            $totalProcessed = $result['uploaded'] + $result['skipped'];
            $progressService->updateProgress(
                $progressId,
                $totalProcessed,
                array_map(function ($error) use ($product) {
                    return [
                        'sku' => $product->sku,
                        'error' => $error,
                    ];
                }, $result['errors'])
            );

            // Mark as completed
            $progressService->markCompleted($progressId, [
                'uploaded' => $result['uploaded'],
                'skipped' => $result['skipped'],
                'errors_count' => count($result['errors']),
                'cover_set' => $result['cover_set'] ?? false,
            ]);

            Log::info('[MEDIA PUSH TO PS] Media push completed', [
                'product_id' => $this->productId,
                'shop_id' => $this->shopId,
                'uploaded' => $result['uploaded'],
                'skipped' => $result['skipped'],
                'errors' => count($result['errors']),
                'cover_set' => $result['cover_set'] ?? false,
            ]);

        } catch (\Exception $e) {
            $progressService->markFailed($progressId, $e->getMessage());

            Log::error('[MEDIA PUSH TO PS] Media push failed', [
                'product_id' => $this->productId,
                'shop_id' => $this->shopId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Job failed permanently
     *
     * @param Throwable $exception Exception that caused failure
     */
    public function failed(Throwable $exception): void
    {
        Log::error('[MEDIA PUSH TO PS] PushMediaToPrestaShop failed permanently', [
            'product_id' => $this->productId,
            'shop_id' => $this->shopId,
            'media_ids' => $this->mediaIds,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);

        // Mark media as sync error
        $mediaQuery = Media::where('mediable_type', Product::class)
            ->where('mediable_id', $this->productId);

        if ($this->mediaIds) {
            $mediaQuery->whereIn('id', $this->mediaIds);
        }

        $mediaCollection = $mediaQuery->get();

        foreach ($mediaCollection as $media) {
            $media->markSyncError("shop_{$this->shopId}", $exception->getMessage());
        }
    }
}
