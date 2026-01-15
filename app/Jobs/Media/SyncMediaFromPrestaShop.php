<?php

namespace App\Jobs\Media;

use App\Models\Media;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\Media\MediaSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * SyncMediaFromPrestaShop Job
 *
 * Pull images from PrestaShop that don't exist in PPM:
 * - Download via PrestaShop API
 * - Create Media records in PPM
 * - Update sync_status to 'synced'
 * - Progress tracking per image
 *
 * ETAP_07d: Media System Implementation
 * Max ~200 lines (zgodnie z CLAUDE.md)
 *
 * @package App\Jobs\Media
 */
class SyncMediaFromPrestaShop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Product ID to sync media for
     */
    public int $productId;

    /**
     * PrestaShop shop ID
     */
    public int $shopId;

    /**
     * User ID who triggered sync
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
     * @param int|null $userId User ID who triggered sync
     */
    public function __construct(int $productId, int $shopId, ?int $userId = null)
    {
        $this->productId = $productId;
        $this->shopId = $shopId;
        $this->userId = $userId;

        // Use sync connection for immediate execution (no queue worker needed on shared hosting)
        $this->onConnection('sync');
    }

    /**
     * Execute the job
     *
     * @param MediaSyncService $syncService Media sync service
     */
    public function handle(MediaSyncService $syncService): void
    {
        $product = Product::find($this->productId);
        $shop = PrestaShopShop::find($this->shopId);

        if (!$product || !$shop) {
            Log::error('[MEDIA SYNC FROM PS] Product or Shop not found', [
                'product_id' => $this->productId,
                'shop_id' => $this->shopId,
            ]);
            return;
        }

        Log::info('[MEDIA SYNC FROM PS] Starting media pull from PrestaShop', [
            'product_id' => $this->productId,
            'product_sku' => $product->sku,
            'shop_id' => $this->shopId,
            'shop_name' => $shop->name,
            'user_id' => $this->userId,
        ]);

        // Verify shop is active
        if (!$shop->is_active) {
            Log::error('[MEDIA SYNC FROM PS] Shop not active', [
                'shop_id' => $this->shopId,
                'shop_name' => $shop->name,
            ]);
            return;
        }

        // FIX (2025-12-02): REMOVED JobProgress tracking for media_pull
        // Reason: Main "import" job already tracks progress. Creating separate JobProgress
        // for each product's media sync clutters the UI with many progress bars.
        // Media sync errors are still logged to Laravel log.

        try {
            // Pull images from PrestaShop
            $result = $syncService->pullFromPrestaShop($product, $shop);

            // Handle shop conflict (images from different shop already exist)
            if ($result['shop_conflict'] ?? false) {
                Log::warning('[MEDIA SYNC FROM PS] Shop conflict detected - manual resolution required', [
                    'product_id' => $this->productId,
                    'product_sku' => $product->sku,
                    'current_shop_id' => $this->shopId,
                    'other_shop_ids' => $result['other_shop_ids'] ?? [],
                    'message' => 'Product has images from another shop. Open Gallery tab to choose which images to keep.',
                ]);

                // Store conflict info in product_shop_data for UI notification
                $this->storeConflictInfo($product, $shop, $result['other_shop_ids'] ?? []);
                return;
            }

            // Log detailed error info for user
            $errorCount = count($result['errors'] ?? []);
            if ($errorCount > 0) {
                Log::warning('[MEDIA SYNC FROM PS] Some images could not be downloaded', [
                    'product_id' => $this->productId,
                    'product_sku' => $product->sku,
                    'shop_id' => $this->shopId,
                    'error_count' => $errorCount,
                    'errors' => $result['errors'],
                    'hint' => 'PrestaShop may have missing image files on disk. Re-upload images in PrestaShop admin.',
                ]);
            }

            Log::info('[MEDIA SYNC FROM PS] Media pull completed', [
                'product_id' => $this->productId,
                'shop_id' => $this->shopId,
                'downloaded' => $result['downloaded'],
                'skipped' => $result['skipped'],
                'errors' => $errorCount,
            ]);

            // If downloaded new images, dispatch processing jobs
            if ($result['downloaded'] > 0) {
                $this->dispatchProcessingJobs($product);
            }

        } catch (\Exception $e) {
            Log::error('[MEDIA SYNC FROM PS] Media pull failed', [
                'product_id' => $this->productId,
                'shop_id' => $this->shopId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Store conflict info in product_shop_data for UI notification
     *
     * @param Product $product Product instance
     * @param PrestaShopShop $shop Current shop
     * @param array $otherShopIds IDs of shops with existing images
     */
    private function storeConflictInfo(Product $product, PrestaShopShop $shop, array $otherShopIds): void
    {
        $shopData = $product->shopData()->where('shop_id', $shop->id)->first();

        if ($shopData) {
            $conflictData = $shopData->conflict_data ?? [];
            $conflictData['media_conflict'] = [
                'detected_at' => now()->toIso8601String(),
                'other_shop_ids' => $otherShopIds,
                'resolved' => false,
                'message' => 'Produkt ma zdjecia z innego sklepu. Otworz zakladke Galeria aby wybrac ktore zachowac.',
            ];
            $shopData->conflict_data = $conflictData;
            $shopData->save();

            Log::info('[MEDIA SYNC FROM PS] Conflict info stored', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'conflict_shops' => $otherShopIds,
            ]);
        }
    }

    /**
     * Dispatch ProcessMediaUpload jobs for newly downloaded images
     *
     * @param Product $product Product instance
     */
    private function dispatchProcessingJobs(Product $product): void
    {
        // Get recently created media (last 5 minutes) that need processing
        $recentMedia = Media::where('mediable_type', Product::class)
            ->where('mediable_id', $product->id)
            ->where('sync_status', 'pending')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->get();

        foreach ($recentMedia as $media) {
            ProcessMediaUpload::dispatch($media->id, $product->id, $this->userId);

            Log::info('[MEDIA SYNC FROM PS] Dispatched processing job', [
                'media_id' => $media->id,
                'product_id' => $product->id,
            ]);
        }
    }

    /**
     * Job failed permanently
     *
     * @param Throwable $exception Exception that caused failure
     */
    public function failed(Throwable $exception): void
    {
        Log::error('[MEDIA SYNC FROM PS] SyncMediaFromPrestaShop failed permanently', [
            'product_id' => $this->productId,
            'shop_id' => $this->shopId,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);

        // Update media records to error state if any exist
        $media = Media::where('mediable_type', Product::class)
            ->where('mediable_id', $this->productId)
            ->where('sync_status', 'pending')
            ->get();

        foreach ($media as $m) {
            $m->markSyncError("shop_{$this->shopId}", $exception->getMessage());
        }
    }
}
