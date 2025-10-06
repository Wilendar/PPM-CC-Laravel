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
use App\Models\ProductSyncStatus;
use Illuminate\Bus\Batch;
use Throwable;

/**
 * Bulk Sync Products Job
 *
 * Dispatches individual product sync jobs dla bulk synchronization operations
 *
 * Features:
 * - Priority handling (high priority first)
 * - Batch tracking with progress callbacks
 * - Memory efficient (chunks products)
 * - Comprehensive error handling
 *
 * FAZA 1E: Queue Jobs - ETAP_07 PrestaShop Integration
 *
 * @package App\Jobs\PrestaShop
 */
class BulkSyncProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Products to sync
     */
    public Collection $products;

    /**
     * Target PrestaShop shop
     */
    public PrestaShopShop $shop;

    /**
     * Batch name for tracking
     */
    public string $batchName;

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
     */
    public function __construct(Collection $products, PrestaShopShop $shop, ?string $batchName = null)
    {
        $this->products = $products;
        $this->shop = $shop;
        $this->batchName = $batchName ?? "Bulk Sync to {$shop->name}";
        $this->onQueue('prestashop_sync');
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        Log::info('Bulk sync job started', [
            'shop_id' => $this->shop->id,
            'shop_name' => $this->shop->name,
            'total_products' => $this->products->count(),
            'batch_name' => $this->batchName,
        ]);

        // Verify shop is active
        if (!$this->shop->is_active) {
            Log::error('Bulk sync aborted - shop not active', [
                'shop_id' => $this->shop->id,
                'shop_name' => $this->shop->name,
            ]);
            return;
        }

        // Group products by priority
        $productsByPriority = $this->groupProductsByPriority();

        // Create jobs array with priority order
        $jobs = [];

        // High priority first (priority <= 3)
        if (isset($productsByPriority['high'])) {
            foreach ($productsByPriority['high'] as $product) {
                $jobs[] = new SyncProductToPrestaShop($product, $this->shop);
            }
        }

        // Normal priority (priority = 5)
        if (isset($productsByPriority['normal'])) {
            foreach ($productsByPriority['normal'] as $product) {
                $jobs[] = new SyncProductToPrestaShop($product, $this->shop);
            }
        }

        // Low priority (priority >= 7)
        if (isset($productsByPriority['low'])) {
            foreach ($productsByPriority['low'] as $product) {
                $jobs[] = new SyncProductToPrestaShop($product, $this->shop);
            }
        }

        if (empty($jobs)) {
            Log::warning('No jobs to dispatch for bulk sync', [
                'shop_id' => $this->shop->id,
                'products_count' => $this->products->count(),
            ]);
            return;
        }

        // Dispatch batch with callbacks
        $batch = Bus::batch($jobs)
            ->name($this->batchName)
            ->allowFailures() // Don't cancel entire batch on single failure
            ->then(function (Batch $batch) {
                // All jobs completed successfully
                Log::info('Bulk sync batch completed successfully', [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'total_jobs' => $batch->totalJobs,
                    'processed_jobs' => $batch->processedJobs(),
                ]);
            })
            ->catch(function (Batch $batch, Throwable $e) {
                // First batch job failure
                Log::error('Bulk sync batch job failed', [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'error' => $e->getMessage(),
                    'failed_jobs' => $batch->failedJobs,
                ]);
            })
            ->finally(function (Batch $batch) {
                // Batch finished processing
                Log::info('Bulk sync batch finished', [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'total_jobs' => $batch->totalJobs,
                    'processed_jobs' => $batch->processedJobs(),
                    'failed_jobs' => $batch->failedJobs,
                    'progress_percentage' => $batch->progress(),
                ]);
            })
            ->onQueue('prestashop_sync')
            ->dispatch();

        Log::info('Bulk sync batch dispatched', [
            'batch_id' => $batch->id,
            'batch_name' => $batch->name,
            'shop_id' => $this->shop->id,
            'total_jobs' => count($jobs),
            'high_priority' => count($productsByPriority['high'] ?? []),
            'normal_priority' => count($productsByPriority['normal'] ?? []),
            'low_priority' => count($productsByPriority['low'] ?? []),
        ]);
    }

    /**
     * Group products by priority level
     */
    private function groupProductsByPriority(): array
    {
        $grouped = [
            'high' => [],
            'normal' => [],
            'low' => [],
        ];

        foreach ($this->products as $product) {
            $priority = $this->getProductPriority($product);

            if ($priority <= 3) {
                $grouped['high'][] = $product;
            } elseif ($priority >= 7) {
                $grouped['low'][] = $product;
            } else {
                $grouped['normal'][] = $product;
            }
        }

        return $grouped;
    }

    /**
     * Get product priority from sync status
     */
    private function getProductPriority(Product $product): int
    {
        $syncStatus = ProductSyncStatus::where('product_id', $product->id)
            ->where('shop_id', $this->shop->id)
            ->first();

        return $syncStatus?->priority ?? ProductSyncStatus::PRIORITY_NORMAL;
    }

    /**
     * Job failed permanently
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Bulk sync job failed permanently', [
            'shop_id' => $this->shop->id,
            'shop_name' => $this->shop->name,
            'products_count' => $this->products->count(),
            'error' => $exception->getMessage(),
        ]);
    }
}
