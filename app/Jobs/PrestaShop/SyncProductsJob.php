<?php

namespace App\Jobs\PrestaShop;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SyncJob;
use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Models\IntegrationLog;
use App\Services\PrestaShop\PrestaShopService;
use Carbon\Carbon;
use Exception;

/**
 * SyncProductsJob
 * 
 * FAZA B: Shop & ERP Management - PrestaShop Products Sync Job
 * 
 * Background job dla synchronizacji produktów z PPM do PrestaShop.
 * Zapewnia:
 * - Progress tracking w real-time
 * - Error handling z retry logic
 * - Performance monitoring
 * - Comprehensive logging
 * 
 * Enterprise Features:
 * - Batch processing z memory management
 * - Rate limiting respect per shop
 * - Automatic recovery z failed syncs
 * - Detailed performance metrics
 */
class SyncProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected SyncJob $syncJob;
    protected int $batchSize = 50; // Products per batch
    protected int $maxMemoryUsage = 256 * 1024 * 1024; // 256MB

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public $maxExceptions = 5;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 3600; // 1 hour

    /**
     * Create a new job instance.
     */
    public function __construct(SyncJob $syncJob)
    {
        $this->syncJob = $syncJob;
        $this->onQueue('prestashop-sync');
    }

    /**
     * Execute the job.
     */
    public function handle(PrestaShopService $prestaShopService): void
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        try {
            // Start the sync job
            $this->syncJob->start();
            
            // Get shop configuration
            $shop = PrestaShopShop::findOrFail($this->syncJob->target_id);
            
            if (!$shop->is_active) {
                throw new Exception("Shop '{$shop->name}' is not active");
            }

            IntegrationLog::info(
                'sync_start',
                "Starting products sync for shop: {$shop->name}",
                [
                    'sync_job_id' => $this->syncJob->job_id,
                    'shop_id' => $shop->id,
                    'shop_name' => $shop->name,
                ],
                IntegrationLog::INTEGRATION_PRESTASHOP,
                (string) $shop->id
            );

            // Get products to sync based on job configuration
            $products = $this->getProductsToSync($shop);
            
            // Update job with total items
            $this->syncJob->update(['total_items' => $products->count()]);

            // Process products in batches
            $processedItems = 0;
            $successfulItems = 0;
            $failedItems = 0;
            $errors = [];

            foreach ($products->chunk($this->batchSize) as $batch) {
                // Check memory usage
                if (memory_get_usage(true) > $this->maxMemoryUsage) {
                    IntegrationLog::warning(
                        'memory_limit',
                        'Memory usage approaching limit, forcing garbage collection',
                        [
                            'memory_usage' => memory_get_usage(true),
                            'memory_limit' => $this->maxMemoryUsage,
                        ],
                        IntegrationLog::INTEGRATION_PRESTASHOP,
                        (string) $shop->id
                    );
                    
                    gc_collect_cycles();
                }

                foreach ($batch as $product) {
                    try {
                        $result = $prestaShopService->syncSingleProduct($shop, $product);
                        
                        if ($result['success'] && !$result['skipped']) {
                            $successfulItems++;
                        } elseif ($result['skipped']) {
                            // Count skipped as successful for progress purposes
                            $successfulItems++;
                        } else {
                            $failedItems++;
                            $errors[] = [
                                'product_sku' => $product->sku,
                                'product_name' => $product->name,
                                'error' => $result['message']
                            ];

                            IntegrationLog::error(
                                'product_sync_failed',
                                "Product sync failed for SKU: {$product->sku}",
                                [
                                    'sync_job_id' => $this->syncJob->job_id,
                                    'product_id' => $product->id,
                                    'product_sku' => $product->sku,
                                    'error_message' => $result['message'],
                                ],
                                IntegrationLog::INTEGRATION_PRESTASHOP,
                                (string) $shop->id
                            );
                        }

                        $processedItems++;

                        // Update progress
                        $this->updateProgress($processedItems, $successfulItems, $failedItems);

                        // Respect rate limiting
                        if ($shop->rate_limit_per_minute) {
                            usleep((60 / $shop->rate_limit_per_minute) * 1000000); // Convert to microseconds
                        }

                    } catch (Exception $e) {
                        $failedItems++;
                        $processedItems++;
                        
                        $errors[] = [
                            'product_sku' => $product->sku,
                            'product_name' => $product->name,
                            'error' => 'Exception: ' . $e->getMessage()
                        ];

                        IntegrationLog::error(
                            'product_sync_exception',
                            "Product sync exception for SKU: {$product->sku}",
                            [
                                'sync_job_id' => $this->syncJob->job_id,
                                'product_id' => $product->id,
                                'product_sku' => $product->sku,
                            ],
                            IntegrationLog::INTEGRATION_PRESTASHOP,
                            (string) $shop->id,
                            $e
                        );

                        $this->updateProgress($processedItems, $successfulItems, $failedItems);
                    }
                }

                // Log batch completion
                IntegrationLog::debug(
                    'batch_completed',
                    "Processed batch of {$batch->count()} products",
                    [
                        'sync_job_id' => $this->syncJob->job_id,
                        'batch_size' => $batch->count(),
                        'processed_total' => $processedItems,
                        'memory_usage' => memory_get_usage(true),
                    ],
                    IntegrationLog::INTEGRATION_PRESTASHOP,
                    (string) $shop->id
                );
            }

            // Calculate performance metrics
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            $duration = $endTime - $startTime;
            $memoryUsed = $endMemory - $startMemory;
            $avgProcessingTime = $processedItems > 0 ? ($duration / $processedItems) * 1000 : 0; // ms per item

            $this->syncJob->updatePerformanceMetrics(
                round($memoryUsed / 1024 / 1024), // MB
                round($duration, 3), // seconds
                $processedItems * 2, // Estimate API calls (get + update/create)
                $processedItems * 5  // Estimate DB queries
            );

            // Complete the job
            $resultSummary = [
                'total_products' => $products->count(),
                'processed_products' => $processedItems,
                'successful_products' => $successfulItems,
                'failed_products' => $failedItems,
                'errors' => $errors,
                'performance' => [
                    'duration_seconds' => round($duration, 2),
                    'memory_usage_mb' => round($memoryUsed / 1024 / 1024, 2),
                    'avg_processing_time_ms' => round($avgProcessingTime, 2),
                ]
            ];

            $this->syncJob->complete($resultSummary);

            // Update shop statistics
            $shop->updateSyncStats(
                $failedItems === 0,
                $successfulItems,
                $duration,
                $memoryUsed
            );

            IntegrationLog::info(
                'sync_completed',
                "Products sync completed for shop: {$shop->name}",
                [
                    'sync_job_id' => $this->syncJob->job_id,
                    'result_summary' => $resultSummary,
                ],
                IntegrationLog::INTEGRATION_PRESTASHOP,
                (string) $shop->id
            );

            // Emit Livewire event if needed
            // This would require additional implementation
            // broadcast(new SyncCompletedEvent($this->syncJob->job_id));

        } catch (Exception $e) {
            $this->handleJobFailure($e, $startTime, $startMemory);
            throw $e; // Re-throw to trigger Laravel's retry mechanism
        }
    }

    /**
     * Handle job failure.
     */
    protected function handleJobFailure(Exception $e, float $startTime, int $startMemory): void
    {
        $duration = microtime(true) - $startTime;
        $memoryUsed = memory_get_usage(true) - $startMemory;

        $this->syncJob->fail(
            'Job execution failed: ' . $e->getMessage(),
            $e->getMessage(),
            $e->getTraceAsString()
        );

        $this->syncJob->updatePerformanceMetrics(
            round($memoryUsed / 1024 / 1024),
            round($duration, 3),
            0,
            0
        );

        IntegrationLog::error(
            'sync_job_failed',
            'Products sync job failed',
            [
                'sync_job_id' => $this->syncJob->job_id,
                'error_message' => $e->getMessage(),
                'duration_seconds' => round($duration, 2),
                'memory_usage_mb' => round($memoryUsed / 1024 / 1024, 2),
            ],
            IntegrationLog::INTEGRATION_PRESTASHOP,
            $this->syncJob->target_id,
            $e
        );
    }

    /**
     * Get products to sync based on job configuration.
     */
    protected function getProductsToSync(PrestaShopShop $shop)
    {
        $query = Product::query()->where('is_active', true);

        // Apply job-specific filters if configured
        $jobConfig = $this->syncJob->job_config ?? [];
        
        if (isset($jobConfig['product_ids']) && !empty($jobConfig['product_ids'])) {
            $query->whereIn('id', $jobConfig['product_ids']);
        }

        if (isset($jobConfig['category_ids']) && !empty($jobConfig['category_ids'])) {
            $query->whereHas('categories', function ($q) use ($jobConfig) {
                $q->whereIn('categories.id', $jobConfig['category_ids']);
            });
        }

        if (isset($jobConfig['updated_since'])) {
            $query->where('updated_at', '>=', Carbon::parse($jobConfig['updated_since']));
        }

        // Apply shop-specific filters
        if ($shop->sync_settings && isset($shop->sync_settings['product_filters'])) {
            $filters = $shop->sync_settings['product_filters'];
            
            if (isset($filters['categories']) && !empty($filters['categories'])) {
                $query->whereHas('categories', function ($q) use ($filters) {
                    $q->whereIn('categories.id', $filters['categories']);
                });
            }
            
            if (isset($filters['price_min']) || isset($filters['price_max'])) {
                $query->whereHas('prices', function ($q) use ($filters) {
                    $q->whereHas('priceGroup', function ($pg) {
                        $pg->where('name', 'Detaliczna');
                    });
                    
                    if (isset($filters['price_min'])) {
                        $q->where('price_gross', '>=', $filters['price_min']);
                    }
                    
                    if (isset($filters['price_max'])) {
                        $q->where('price_gross', '<=', $filters['price_max']);
                    }
                });
            }
        }

        return $query->with(['categories', 'prices.priceGroup', 'integrationMappings'])->get();
    }

    /**
     * Update sync job progress.
     */
    protected function updateProgress(int $processedItems, int $successfulItems, int $failedItems): void
    {
        $avgProcessingTime = $this->syncJob->started_at 
            ? (Carbon::now()->diffInMilliseconds($this->syncJob->started_at) / $processedItems)
            : null;

        $this->syncJob->updateProgress(
            $processedItems,
            $successfulItems,
            $failedItems,
            $avgProcessingTime
        );
    }

    /**
     * The job failed to process.
     */
    public function failed(Exception $exception): void
    {
        IntegrationLog::error(
            'sync_job_failed_final',
            'Products sync job failed permanently after all retries',
            [
                'sync_job_id' => $this->syncJob->job_id,
                'attempts' => $this->attempts(),
                'error_message' => $exception->getMessage(),
            ],
            IntegrationLog::INTEGRATION_PRESTASHOP,
            $this->syncJob->target_id,
            $exception
        );

        // Mark job as permanently failed
        $this->syncJob->update([
            'status' => SyncJob::STATUS_FAILED,
            'error_message' => 'Job failed permanently after ' . $this->attempts() . ' attempts',
            'error_details' => $exception->getMessage(),
            'stack_trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [30, 60, 300]; // 30s, 1min, 5min
    }
}