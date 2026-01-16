<?php

namespace App\Jobs\ERP;

use App\Models\SyncJob;
use App\Models\ERPConnection;
use App\Models\IntegrationLog;
use App\Models\JobProgress;
use App\Services\ERP\BaselinkerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * BaselinkerSyncJob
 *
 * ETAP_08: BaseLinker ERP Integration
 *
 * Job uruchamiany z ERPManager do synchronizacji z Baselinker.
 * Obsluguje rozne typy synchronizacji (full, products, stock, prices).
 *
 * FAZA 10: Dodano wsparcie dla JobProgress (progress bar w ProductList)
 */
class BaselinkerSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of retry attempts.
     */
    public int $tries = 3;

    /**
     * Job timeout.
     */
    public int $timeout = 600;

    /**
     * JobProgress instance for UI progress bar.
     */
    protected ?JobProgress $jobProgress = null;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public SyncJob $syncJob
    ) {
        $this->onQueue('erp_default');
    }

    /**
     * Execute the job.
     */
    public function handle(BaselinkerService $baselinker): void
    {
        $startTime = microtime(true);

        // Get connection from SyncJob config
        $connectionId = $this->syncJob->job_config['connection_id'] ?? null;
        $syncType = $this->syncJob->job_config['sync_type'] ?? 'full';

        // Get JobProgress for UI updates (FAZA 10)
        $jobProgressId = $this->syncJob->job_config['job_progress_id'] ?? null;
        if ($jobProgressId) {
            $this->jobProgress = JobProgress::find($jobProgressId);
        }

        if (!$connectionId) {
            $this->syncJob->fail('Connection ID not specified in job config');
            $this->updateJobProgressFailed('Connection ID not specified');
            return;
        }

        $connection = ERPConnection::find($connectionId);

        if (!$connection) {
            $this->syncJob->fail('ERPConnection not found: ' . $connectionId);
            $this->updateJobProgressFailed('ERPConnection not found');
            return;
        }

        // Start job
        $this->syncJob->start();
        $this->updateJobProgressRunning($connection);

        try {
            $results = match ($syncType) {
                'full' => $this->runFullSync($baselinker, $connection),
                'products' => $this->runProductsSync($baselinker, $connection),
                'stock' => $this->runStockSync($baselinker, $connection),
                'prices' => $this->runPricesSync($baselinker, $connection),
                'pull' => $this->runPullSync($baselinker, $connection),
                default => throw new \InvalidArgumentException("Unknown sync type: {$syncType}"),
            };

            $duration = round((microtime(true) - $startTime), 2);

            // Log results
            IntegrationLog::info(
                'baselinker_sync_job',
                "Baselinker sync completed ({$syncType})",
                [
                    'connection_id' => $connection->id,
                    'sync_type' => $syncType,
                    'results' => $results,
                    'duration_seconds' => $duration,
                ],
                IntegrationLog::INTEGRATION_BASELINKER,
                (string) $connection->id
            );

            // Update SyncJob
            $this->syncJob->updateProgress(
                $results['total_products'] ?? $results['total'] ?? 0,
                $results['synced_products'] ?? $results['synced'] ?? $results['imported'] ?? 0,
                $results['error_products'] ?? $results['failed'] ?? 0
            );

            if ($results['success']) {
                $this->syncJob->complete($results);
                $this->updateJobProgressCompleted($results);
            } else {
                $this->syncJob->completeWithErrors($results);
                $this->updateJobProgressCompletedWithErrors($results);
            }

            // Update connection
            $connection->update([
                'last_sync_at' => Carbon::now(),
            ]);

            $connection->updateSyncStats(
                $results['success'],
                $results['synced_products'] ?? $results['synced'] ?? $results['imported'] ?? 0,
                $duration * 1000
            );

        } catch (\Exception $e) {
            Log::error('BaselinkerSyncJob failed', [
                'sync_job_id' => $this->syncJob->job_id,
                'connection_id' => $connection->id,
                'exception' => $e->getMessage(),
            ]);

            IntegrationLog::error(
                'baselinker_sync_job',
                'Baselinker sync failed: ' . $e->getMessage(),
                [
                    'connection_id' => $connection->id,
                    'sync_type' => $syncType,
                ],
                IntegrationLog::INTEGRATION_BASELINKER,
                (string) $connection->id,
                $e
            );

            $this->syncJob->fail(
                $e->getMessage(),
                null,
                $e->getTraceAsString()
            );

            $this->updateJobProgressFailed($e->getMessage());

            $connection->updateConnectionHealth(
                ERPConnection::CONNECTION_ERROR,
                null,
                $e->getMessage()
            );
        }
    }

    /**
     * Run full synchronization (products + stock + prices).
     */
    protected function runFullSync(BaselinkerService $baselinker, ERPConnection $connection): array
    {
        return $baselinker->syncProducts($connection);
    }

    /**
     * Run products-only synchronization.
     */
    protected function runProductsSync(BaselinkerService $baselinker, ERPConnection $connection): array
    {
        return $baselinker->syncAllProducts($connection, $this->syncJob->filters ?? []);
    }

    /**
     * Run stock-only synchronization (batch).
     */
    protected function runStockSync(BaselinkerService $baselinker, ERPConnection $connection): array
    {
        // Stock-only sync - simplified version
        $results = [
            'success' => true,
            'total' => 0,
            'synced' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $products = \App\Models\Product::where('is_active', true)
            ->whereHas('integrationMappings', function ($q) use ($connection) {
                $q->where('integration_type', 'baselinker')
                  ->where('integration_identifier', $connection->instance_name);
            })
            ->get();

        $results['total'] = $products->count();
        $this->updateJobProgressTotal($results['total']);

        foreach ($products as $index => $product) {
            $stockResult = $baselinker->syncStock($connection, $product);

            if ($stockResult['success']) {
                $results['synced']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'sku' => $product->sku,
                    'message' => $stockResult['message'],
                ];
            }

            // Update progress every 10 items
            if (($index + 1) % 10 === 0 || ($index + 1) === $results['total']) {
                $this->updateJobProgressCurrent($index + 1, $results['failed']);
            }

            usleep(100000); // 0.1 second rate limit
        }

        $results['success'] = $results['failed'] === 0;

        return $results;
    }

    /**
     * Run prices-only synchronization (batch).
     */
    protected function runPricesSync(BaselinkerService $baselinker, ERPConnection $connection): array
    {
        $results = [
            'success' => true,
            'total' => 0,
            'synced' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $products = \App\Models\Product::where('is_active', true)
            ->whereHas('integrationMappings', function ($q) use ($connection) {
                $q->where('integration_type', 'baselinker')
                  ->where('integration_identifier', $connection->instance_name);
            })
            ->get();

        $results['total'] = $products->count();
        $this->updateJobProgressTotal($results['total']);

        foreach ($products as $index => $product) {
            $priceResult = $baselinker->syncPrices($connection, $product);

            if ($priceResult['success']) {
                $results['synced']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'sku' => $product->sku,
                    'message' => $priceResult['message'],
                ];
            }

            // Update progress every 10 items
            if (($index + 1) % 10 === 0 || ($index + 1) === $results['total']) {
                $this->updateJobProgressCurrent($index + 1, $results['failed']);
            }

            usleep(100000); // 0.1 second rate limit
        }

        $results['success'] = $results['failed'] === 0;

        return $results;
    }

    /**
     * Run pull synchronization (from Baselinker to PPM).
     */
    protected function runPullSync(BaselinkerService $baselinker, ERPConnection $connection): array
    {
        // Pass jobProgress to service for real-time updates
        return $baselinker->pullAllProducts(
            $connection,
            $this->syncJob->filters ?? [],
            $this->jobProgress
        );
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('BaselinkerSyncJob failed completely', [
            'sync_job_id' => $this->syncJob->job_id,
            'exception' => $exception->getMessage(),
        ]);

        $this->syncJob->fail(
            'Job failed completely',
            $exception->getMessage(),
            $exception->getTraceAsString()
        );

        $this->updateJobProgressFailed($exception->getMessage());
    }

    /*
    |--------------------------------------------------------------------------
    | JobProgress Helper Methods (FAZA 10)
    |--------------------------------------------------------------------------
    */

    /**
     * Update JobProgress to running status.
     */
    protected function updateJobProgressRunning(ERPConnection $connection): void
    {
        if (!$this->jobProgress) {
            return;
        }

        $this->jobProgress->update([
            'status' => 'running',
            'metadata' => array_merge($this->jobProgress->metadata ?? [], [
                'phase' => 'running',
                'phase_label' => "Synchronizacja z {$connection->instance_name}...",
            ]),
        ]);
    }

    /**
     * Update JobProgress total count.
     */
    protected function updateJobProgressTotal(int $total): void
    {
        if (!$this->jobProgress) {
            return;
        }

        $this->jobProgress->update([
            'total_count' => $total,
        ]);
    }

    /**
     * Update JobProgress current count.
     */
    protected function updateJobProgressCurrent(int $current, int $errors = 0): void
    {
        if (!$this->jobProgress) {
            return;
        }

        $this->jobProgress->update([
            'current_count' => $current,
            'error_count' => $errors,
        ]);
    }

    /**
     * Update JobProgress to completed status.
     */
    protected function updateJobProgressCompleted(array $results): void
    {
        if (!$this->jobProgress) {
            return;
        }

        $imported = $results['imported'] ?? $results['synced'] ?? $results['synced_products'] ?? 0;
        $total = $results['total'] ?? $results['total_products'] ?? 0;

        $this->jobProgress->update([
            'status' => 'completed',
            'current_count' => $total,
            'total_count' => $total,
            'completed_at' => now(),
            'metadata' => array_merge($this->jobProgress->metadata ?? [], [
                'phase' => 'completed',
                'phase_label' => "Zaimportowano {$imported} produktow",
                'results' => $results,
            ]),
        ]);
    }

    /**
     * Update JobProgress to completed with errors status.
     */
    protected function updateJobProgressCompletedWithErrors(array $results): void
    {
        if (!$this->jobProgress) {
            return;
        }

        $imported = $results['imported'] ?? $results['synced'] ?? 0;
        $failed = $results['failed'] ?? $results['error_products'] ?? 0;
        $total = $results['total'] ?? $results['total_products'] ?? 0;

        $this->jobProgress->update([
            'status' => 'completed',
            'current_count' => $total,
            'total_count' => $total,
            'error_count' => $failed,
            'error_details' => $results['errors'] ?? [],
            'completed_at' => now(),
            'metadata' => array_merge($this->jobProgress->metadata ?? [], [
                'phase' => 'completed_with_errors',
                'phase_label' => "Zaimportowano {$imported}, bledow: {$failed}",
                'results' => $results,
            ]),
        ]);
    }

    /**
     * Update JobProgress to failed status.
     */
    protected function updateJobProgressFailed(string $errorMessage): void
    {
        if (!$this->jobProgress) {
            return;
        }

        $this->jobProgress->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_details' => [
                ['error' => $errorMessage],
            ],
            'metadata' => array_merge($this->jobProgress->metadata ?? [], [
                'phase' => 'failed',
                'phase_label' => 'Synchronizacja nie powiodla sie',
                'error' => $errorMessage,
            ]),
        ]);
    }
}
