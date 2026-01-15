<?php

namespace App\Jobs\Features;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\JobProgressService;
use App\Services\PrestaShop\PrestaShop8Client;
use App\Services\PrestaShop\PrestaShopFeatureSyncService;
use App\Services\PrestaShop\Transformers\FeatureTransformer;

/**
 * SyncFeaturesJob
 *
 * ETAP_07e FAZA 4.3 - Batch synchronization of product features to PrestaShop
 *
 * Features:
 * - Batch processing with configurable chunk size
 * - Progress tracking with JobProgressService
 * - Error tracking per product
 * - Rate limiting for API compliance
 *
 * @package App\Jobs\Features
 * @version 1.0
 * @since 2025-12-03
 */
class SyncFeaturesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Product IDs to sync
     */
    public array $productIds;

    /**
     * Target PrestaShop shop ID
     */
    public int $shopId;

    /**
     * Job progress ID for tracking
     */
    public ?int $jobProgressId;

    /**
     * User ID who triggered the job
     */
    public ?int $userId;

    /**
     * Batch size for processing
     */
    public int $batchSize = 50;

    /**
     * Number of times job may be attempted
     */
    public int $tries = 3;

    /**
     * Maximum seconds job can run
     */
    public int $timeout = 600; // 10 minutes

    /**
     * Create new job instance
     *
     * @param array $productIds Product IDs to sync
     * @param int $shopId Target shop ID
     * @param int|null $jobProgressId Progress tracking ID
     * @param int|null $userId User who triggered (null = system)
     */
    public function __construct(
        array $productIds,
        int $shopId,
        ?int $jobProgressId = null,
        ?int $userId = null
    ) {
        $this->productIds = $productIds;
        $this->shopId = $shopId;
        $this->jobProgressId = $jobProgressId;
        $this->userId = $userId;
    }

    /**
     * Execute the job
     *
     * @return void
     */
    public function handle(JobProgressService $progressService): void
    {
        $shop = PrestaShopShop::find($this->shopId);
        if (!$shop) {
            Log::error('[FEATURE SYNC JOB] Shop not found', ['shop_id' => $this->shopId]);
            $this->markFailed($progressService, 'Shop not found');
            return;
        }

        Log::info('[FEATURE SYNC JOB] Starting', [
            'shop_id' => $this->shopId,
            'shop_name' => $shop->name,
            'product_count' => count($this->productIds),
            'job_progress_id' => $this->jobProgressId,
        ]);

        // Mark job as running
        if ($this->jobProgressId) {
            $progressService->updateStatus($this->jobProgressId, 'running');
        }

        // Initialize services
        try {
            $client = new PrestaShop8Client($shop);
            $transformer = new FeatureTransformer();
            $syncService = new PrestaShopFeatureSyncService($client, $transformer);
        } catch (\Exception $e) {
            Log::error('[FEATURE SYNC JOB] Failed to initialize services', [
                'error' => $e->getMessage(),
            ]);
            $this->markFailed($progressService, 'Service initialization failed: ' . $e->getMessage());
            return;
        }

        // Process products in batches
        $totalStats = [
            'processed' => 0,
            'synced' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $chunks = array_chunk($this->productIds, $this->batchSize);
        $totalProducts = count($this->productIds);
        $processedCount = 0;

        foreach ($chunks as $chunkIndex => $chunk) {
            foreach ($chunk as $productId) {
                try {
                    $result = $this->syncSingleProduct($productId, $shop, $syncService);

                    $totalStats['processed']++;
                    $totalStats['synced'] += $result['synced'];

                    if (!empty($result['errors'])) {
                        $totalStats['errors'] = array_merge($totalStats['errors'], $result['errors']);
                    }

                } catch (\Exception $e) {
                    $totalStats['processed']++;
                    $totalStats['errors'][] = [
                        'product_id' => $productId,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('[FEATURE SYNC JOB] Product sync failed', [
                        'product_id' => $productId,
                        'error' => $e->getMessage(),
                    ]);
                }

                $processedCount++;

                // Update progress every 10 products
                if ($processedCount % 10 === 0 && $this->jobProgressId) {
                    $progressService->updateProgress(
                        $this->jobProgressId,
                        $processedCount,
                        array_slice($totalStats['errors'], -5) // Keep last 5 errors
                    );
                }
            }

            // Rate limiting between chunks
            if ($chunkIndex < count($chunks) - 1) {
                usleep(500000); // 500ms between chunks
            }
        }

        // Mark job complete
        if ($this->jobProgressId) {
            $progressService->markCompleted($this->jobProgressId, [
                'synced_features' => $totalStats['synced'],
                'processed_products' => $totalStats['processed'],
                'error_count' => count($totalStats['errors']),
            ]);
        }

        Log::info('[FEATURE SYNC JOB] Completed', [
            'shop_id' => $this->shopId,
            'processed' => $totalStats['processed'],
            'synced' => $totalStats['synced'],
            'errors' => count($totalStats['errors']),
        ]);
    }

    /**
     * Sync features for single product
     *
     * @param int $productId
     * @param PrestaShopShop $shop
     * @param PrestaShopFeatureSyncService $syncService
     * @return array Result stats
     */
    protected function syncSingleProduct(
        int $productId,
        PrestaShopShop $shop,
        PrestaShopFeatureSyncService $syncService
    ): array {
        $product = Product::find($productId);
        if (!$product) {
            return ['synced' => 0, 'errors' => [['product_id' => $productId, 'error' => 'Product not found']]];
        }

        // Get PrestaShop product ID from shop data
        $shopData = $product->shopData()->where('shop_id', $shop->id)->first();
        if (!$shopData || !$shopData->external_id) {
            return ['synced' => 0, 'errors' => [['product_id' => $productId, 'error' => 'No external ID']]];
        }

        return $syncService->syncProductFeatures($product, $shop, (int) $shopData->external_id);
    }

    /**
     * Mark job as failed
     *
     * @param JobProgressService $progressService
     * @param string $reason
     */
    protected function markFailed(JobProgressService $progressService, string $reason): void
    {
        if ($this->jobProgressId) {
            $progressService->markFailed($this->jobProgressId, $reason);
        }
    }

    /**
     * Handle job failure
     *
     * @param \Throwable $exception
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[FEATURE SYNC JOB] Job failed', [
            'shop_id' => $this->shopId,
            'product_count' => count($this->productIds),
            'error' => $exception->getMessage(),
        ]);

        if ($this->jobProgressId) {
            $progressService = app(JobProgressService::class);
            $progressService->markFailed($this->jobProgressId, $exception->getMessage());
        }
    }
}
