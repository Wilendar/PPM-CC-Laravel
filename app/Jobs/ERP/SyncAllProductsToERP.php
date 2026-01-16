<?php

namespace App\Jobs\ERP;

use App\Models\ERPConnection;
use App\Models\Product;
use App\Models\SyncJob;
use App\Models\IntegrationLog;
use App\Services\ERP\ERPServiceManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * SyncAllProductsToERP Job
 *
 * ETAP_08: BaseLinker ERP Integration
 *
 * Asynchroniczne zadanie synchronizacji WSZYSTKICH produktow do ERP.
 * Procesuje produkty w batchach, raportuje progress, jest resumable.
 *
 * Features:
 * - Batch processing (100 produktow per chunk)
 * - Progress reporting via SyncJob
 * - Resumable (zapisuje ostatni przetworzony SKU)
 * - Memory efficient (chunk processing)
 */
class SyncAllProductsToERP implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of retry attempts.
     */
    public int $tries = 3;

    /**
     * Job timeout (10 minutes for bulk operations).
     */
    public int $timeout = 600;

    /**
     * Batch size for chunk processing.
     */
    protected int $batchSize = 100;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ERPConnection $connection,
        public ?SyncJob $syncJob = null,
        public array $filters = [],
        public ?string $resumeFromSku = null
    ) {
        $this->onQueue('erp_default');
    }

    /**
     * Execute the job.
     */
    public function handle(ERPServiceManager $erpManager): void
    {
        $startTime = microtime(true);
        $memoryStart = memory_get_usage(true);

        // Initialize counters
        $stats = [
            'total' => 0,
            'synced' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        // Start SyncJob
        if ($this->syncJob) {
            $this->syncJob->start();
        }

        try {
            $service = $erpManager->getService($this->connection);

            // Build query
            $query = Product::where('is_active', true);

            // Apply filters
            if (!empty($this->filters['product_ids'])) {
                $query->whereIn('id', $this->filters['product_ids']);
            }

            if (!empty($this->filters['category_ids'])) {
                $query->whereHas('categories', function ($q) {
                    $q->whereIn('categories.id', $this->filters['category_ids']);
                });
            }

            // Resume from last SKU
            if ($this->resumeFromSku) {
                $query->where('sku', '>', $this->resumeFromSku);
            }

            // Order by SKU for consistent resumability
            $query->orderBy('sku');

            // Count total
            $stats['total'] = $query->count();

            // Update SyncJob total
            if ($this->syncJob) {
                $this->syncJob->update(['total_items' => $stats['total']]);
            }

            // Process in chunks
            $query->chunk($this->batchSize, function ($products) use ($service, &$stats, $startTime) {
                foreach ($products as $product) {
                    try {
                        $result = $service->syncProductToERP($this->connection, $product);

                        if ($result['success']) {
                            $stats['synced']++;
                        } else {
                            $stats['failed']++;
                            if (count($stats['errors']) < 20) {
                                $stats['errors'][] = [
                                    'sku' => $product->sku,
                                    'message' => $result['message'],
                                ];
                            }
                        }

                    } catch (\Exception $e) {
                        $stats['failed']++;
                        if (count($stats['errors']) < 20) {
                            $stats['errors'][] = [
                                'sku' => $product->sku,
                                'message' => 'Exception: ' . $e->getMessage(),
                            ];
                        }
                    }

                    // Update progress
                    $processed = $stats['synced'] + $stats['failed'] + $stats['skipped'];

                    if ($this->syncJob && ($processed % 10 === 0)) {
                        $this->syncJob->updateProgress(
                            $processed,
                            $stats['synced'],
                            $stats['failed'],
                            $this->calculateAvgTime($startTime, $processed)
                        );
                    }

                    // Rate limiting (respect Baselinker 100 req/min)
                    usleep(1000000); // 1 second between products
                }

                // Memory check
                if (memory_get_usage(true) > 256 * 1024 * 1024) { // > 256MB
                    Log::warning('SyncAllProductsToERP: Memory limit approaching', [
                        'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                        'processed' => $stats['synced'] + $stats['failed'],
                    ]);
                }
            });

            // Calculate duration
            $duration = round((microtime(true) - $startTime), 2);
            $memoryUsed = round((memory_get_usage(true) - $memoryStart) / 1024 / 1024, 2);

            // Update connection stats
            $this->connection->updateSyncStats(
                $stats['failed'] === 0,
                $stats['synced'],
                $duration * 1000,
                $memoryUsed * 1024 * 1024
            );

            // Log completion
            IntegrationLog::info(
                'sync_all_products_job',
                "Bulk sync completed to {$this->connection->erp_type}",
                [
                    'connection_id' => $this->connection->id,
                    'total' => $stats['total'],
                    'synced' => $stats['synced'],
                    'failed' => $stats['failed'],
                    'duration_seconds' => $duration,
                    'memory_mb' => $memoryUsed,
                ],
                IntegrationLog::INTEGRATION_BASELINKER,
                (string) $this->connection->id
            );

            // Complete or fail SyncJob
            if ($this->syncJob) {
                $this->syncJob->updateProgress(
                    $stats['synced'] + $stats['failed'] + $stats['skipped'],
                    $stats['synced'],
                    $stats['failed']
                );

                if ($stats['failed'] === 0) {
                    $this->syncJob->complete([
                        'total' => $stats['total'],
                        'synced' => $stats['synced'],
                        'duration_seconds' => $duration,
                    ]);
                } else {
                    $this->syncJob->completeWithErrors([
                        'total' => $stats['total'],
                        'synced' => $stats['synced'],
                        'failed' => $stats['failed'],
                        'errors' => array_slice($stats['errors'], 0, 10),
                        'duration_seconds' => $duration,
                    ]);
                }

                // Update performance metrics
                $this->syncJob->updatePerformanceMetrics(
                    (int) $memoryUsed,
                    $duration,
                    $stats['synced'] + $stats['failed'], // API calls
                    0 // DB queries (not tracked here)
                );
            }

        } catch (\Exception $e) {
            Log::error('SyncAllProductsToERP job failed', [
                'connection_id' => $this->connection->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update connection health
            $this->connection->updateConnectionHealth(
                ERPConnection::CONNECTION_ERROR,
                null,
                $e->getMessage()
            );

            // Fail SyncJob
            if ($this->syncJob) {
                $this->syncJob->fail(
                    'Job exception: ' . $e->getMessage(),
                    null,
                    $e->getTraceAsString()
                );
            }

            throw $e;
        }
    }

    /**
     * Calculate average processing time per item.
     */
    protected function calculateAvgTime(float $startTime, int $processed): float
    {
        if ($processed === 0) {
            return 0;
        }

        $elapsed = microtime(true) - $startTime;
        return round(($elapsed / $processed) * 1000, 2); // ms per item
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncAllProductsToERP job failed completely', [
            'connection_id' => $this->connection->id,
            'exception' => $exception->getMessage(),
        ]);

        if ($this->syncJob) {
            $this->syncJob->fail(
                'Job failed completely',
                $exception->getMessage(),
                $exception->getTraceAsString()
            );
        }
    }
}
