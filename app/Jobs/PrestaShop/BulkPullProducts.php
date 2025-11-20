<?php

namespace App\Jobs\PrestaShop;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use App\Models\Product;
use App\Models\PrestaShopShop;
use Illuminate\Bus\Batch;
use Throwable;

/**
 * Bulk Pull Products Job
 *
 * ETAP_13: Sync Panel UX Refactoring - Backend Foundation
 *
 * User-triggered bulk pull: Refresh product data from ALL shops (PrestaShop → PPM)
 *
 * Pattern: Mirrors BulkSyncProducts architecture but opposite direction (PS → PPM)
 *
 * Features:
 * - Dispatches PullSingleProductFromPrestaShop per shop
 * - Batch tracking with progress callbacks
 * - User tracking for audit trail
 * - Comprehensive error handling
 *
 * @package App\Jobs\PrestaShop
 * @version 1.0
 * @since ETAP_13 (2025-11-17)
 */
class BulkPullProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Product to pull data for (SINGLE product)
     */
    public Product $product;

    /**
     * Shops to pull from (Collection of PrestaShopShop)
     */
    public Collection $shops;

    /**
     * User ID who triggered the pull (NULL = SYSTEM/scheduled)
     */
    public ?int $userId = null;

    /**
     * Number of times job may be attempted
     */
    public int $tries = 1; // No retry - individual jobs handle retries

    /**
     * Maximum seconds job can run
     */
    public int $timeout = 300; // 5 minutes

    /**
     * Create new job instance
     *
     * CRITICAL: This job pulls ONE product from ALL shops (not all products from all shops!)
     *
     * @param Product $product Product to pull data for
     * @param Collection $shops Shops to pull from (Collection of PrestaShopShop)
     * @param int|null $userId User who triggered pull (NULL = SYSTEM)
     */
    public function __construct(Product $product, Collection $shops, ?int $userId = null)
    {
        $this->product = $product;
        $this->shops = $shops;
        $this->userId = $userId;
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        Log::info('Bulk pull job started', [
            'product_id' => $this->product->id,
            'product_sku' => $this->product->sku,
            'total_shops' => $this->shops->count(),
            'user_id' => $this->userId ?? 'SYSTEM',
        ]);

        try {
            // Verify product exists
            if (!$this->product || !$this->product->exists) {
                Log::error('Bulk pull aborted - product not found', [
                    'product_id' => $this->product?->id,
                ]);
                return;
            }

            // Verify shops collection
            if ($this->shops->isEmpty()) {
                Log::warning('Bulk pull aborted - no shops to pull from', [
                    'product_id' => $this->product->id,
                    'product_sku' => $this->product->sku,
                ]);
                return;
            }

            // Create jobs array
            $jobs = [];

            foreach ($this->shops as $shop) {
                // Verify shop is active
                if (!$shop->is_active) {
                    Log::debug('Skipping inactive shop', [
                        'product_id' => $this->product->id,
                        'shop_id' => $shop->id,
                        'shop_name' => $shop->name,
                    ]);
                    continue;
                }

                // Verify product is linked to shop
                $shopData = $this->product->shopData()
                    ->where('shop_id', $shop->id)
                    ->first();

                if (!$shopData || !$shopData->prestashop_product_id) {
                    Log::debug('Skipping shop - product not linked or missing PrestaShop ID', [
                        'product_id' => $this->product->id,
                        'shop_id' => $shop->id,
                        'shop_name' => $shop->name,
                    ]);
                    continue;
                }

                // Add job to batch
                $jobs[] = new PullSingleProductFromPrestaShop($this->product, $shop);
            }

            if (empty($jobs)) {
                Log::warning('No jobs to dispatch for bulk pull', [
                    'product_id' => $this->product->id,
                    'product_sku' => $this->product->sku,
                    'total_shops' => $this->shops->count(),
                ]);
                return;
            }

            // Capture variables for batch callbacks
            $productId = $this->product->id;
            $productSku = $this->product->sku;
            $userId = $this->userId;

            // Dispatch batch with callbacks
            $batch = Bus::batch($jobs)
                ->name("Bulk Pull Product {$this->product->sku}")
                ->allowFailures() // Don't cancel entire batch on single failure
                ->then(function (Batch $batch) use ($productId, $productSku) {
                    // All jobs completed successfully
                    Log::info('Bulk pull batch completed successfully', [
                        'batch_id' => $batch->id,
                        'batch_name' => $batch->name,
                        'product_id' => $productId,
                        'product_sku' => $productSku,
                        'total_jobs' => $batch->totalJobs,
                        'processed_jobs' => $batch->processedJobs(),
                    ]);
                })
                ->catch(function (Batch $batch, Throwable $e) use ($productId, $productSku) {
                    // First batch job failure
                    Log::error('Bulk pull batch job failed', [
                        'batch_id' => $batch->id,
                        'batch_name' => $batch->name,
                        'product_id' => $productId,
                        'product_sku' => $productSku,
                        'error' => $e->getMessage(),
                        'failed_jobs' => $batch->failedJobs,
                    ]);
                })
                ->finally(function (Batch $batch) use ($productId, $productSku) {
                    // Batch finished processing
                    Log::info('Bulk pull batch finished', [
                        'batch_id' => $batch->id,
                        'batch_name' => $batch->name,
                        'product_id' => $productId,
                        'product_sku' => $productSku,
                        'total_jobs' => $batch->totalJobs,
                        'processed_jobs' => $batch->processedJobs(),
                        'failed_jobs' => $batch->failedJobs,
                        'progress_percentage' => $batch->progress(),
                    ]);
                })
                ->onQueue('prestashop_sync')
                ->dispatch();

            Log::info('Bulk pull batch dispatched', [
                'batch_id' => $batch->id,
                'batch_name' => $batch->name,
                'product_id' => $this->product->id,
                'product_sku' => $this->product->sku,
                'total_jobs' => count($jobs),
                'user_id' => $this->userId ?? 'SYSTEM',
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk pull job failed during setup', [
                'product_id' => $this->product->id,
                'product_sku' => $this->product->sku,
                'error' => $e->getMessage(),
                'trace' => $e->getFile() . ':' . $e->getLine(),
            ]);

            throw $e;
        }
    }

    /**
     * Job failed permanently
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Bulk pull job failed permanently', [
            'product_id' => $this->product->id,
            'product_sku' => $this->product->sku,
            'shops_count' => $this->shops->count(),
            'error' => $exception->getMessage(),
        ]);
    }
}
