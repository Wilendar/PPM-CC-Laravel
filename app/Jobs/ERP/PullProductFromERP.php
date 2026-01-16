<?php

namespace App\Jobs\ERP;

use App\Models\ERPConnection;
use App\Models\Product;
use App\Models\ProductErpData;
use App\Models\SyncJob;
use App\Models\IntegrationLog;
use App\Services\ERP\ERPServiceManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * PullProductFromERP Job
 *
 * ETAP_08: BaseLinker ERP Integration
 *
 * Asynchroniczne zadanie pobierania danych produktu z systemu ERP.
 * Wspiera wszystkie typy ERP (Baselinker, Subiekt GT, Dynamics).
 *
 * Features:
 * - ShouldBeUnique - zapobiega duplikatom w kolejce
 * - Retry logic z exponential backoff
 * - Progress tracking via SyncJob model
 * - Comprehensive error handling i logging
 * - Updates ProductErpData with external_data cache
 */
class PullProductFromERP implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of retry attempts.
     */
    public int $tries = 3;

    /**
     * Job timeout in seconds.
     */
    public int $timeout = 300;

    /**
     * Unique job identifier (prevents duplicates).
     */
    public function uniqueId(): string
    {
        return 'pull_product_' . $this->product->id . '_' . $this->connection->id;
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Product $product,
        public ERPConnection $connection,
        public ?SyncJob $syncJob = null
    ) {
        $this->onQueue($this->determineQueue());
    }

    /**
     * Determine queue based on product priority.
     */
    protected function determineQueue(): string
    {
        // Featured products get high priority queue
        if ($this->product->is_featured ?? false) {
            return 'erp_high';
        }

        return 'erp_default';
    }

    /**
     * Execute the job.
     */
    public function handle(ERPServiceManager $erpManager): void
    {
        $startTime = microtime(true);

        // Update SyncJob if provided
        if ($this->syncJob) {
            $this->syncJob->start();
        }

        try {
            // Get appropriate ERP service
            $service = $erpManager->getService($this->connection);

            // Pull product data from ERP
            $result = $service->syncProductFromERP($this->connection, $this->product);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if ($result['success']) {
                // Update connection health
                $this->connection->updateConnectionHealth(
                    ERPConnection::CONNECTION_CONNECTED,
                    $duration
                );

                // Update sync stats
                $this->connection->updateSyncStats(true, 1, $duration);

                // ETAP_08.3: Update ProductErpData model with pulled data
                $this->updateProductErpData($result);

                // Log success
                IntegrationLog::info(
                    'pull_product_job',
                    "Product pulled from {$this->connection->erp_type}: {$this->product->sku}",
                    [
                        'product_id' => $this->product->id,
                        'product_sku' => $this->product->sku,
                        'connection_id' => $this->connection->id,
                        'external_id' => $result['external_id'] ?? null,
                        'duration_ms' => $duration,
                        'fields_updated' => array_keys($result['data'] ?? []),
                    ],
                    IntegrationLog::INTEGRATION_BASELINKER,
                    (string) $this->connection->id
                );

                // Complete SyncJob
                if ($this->syncJob) {
                    $this->syncJob->complete([
                        'external_id' => $result['external_id'] ?? null,
                        'message' => $result['message'],
                        'fields_updated' => array_keys($result['data'] ?? []),
                    ]);
                }

            } else {
                // Handle failure
                $this->handleFailure($result['message'], $duration);
            }

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->handleFailure($e->getMessage(), $duration, $e);
        }
    }

    /**
     * Handle job failure.
     */
    protected function handleFailure(string $message, float $duration, ?\Exception $exception = null): void
    {
        // Update connection health
        $this->connection->updateConnectionHealth(
            ERPConnection::CONNECTION_ERROR,
            $duration,
            $message
        );

        // Update sync stats
        $this->connection->updateSyncStats(false, 0, $duration);

        // Log error
        IntegrationLog::error(
            'pull_product_job',
            "Failed to pull product from {$this->connection->erp_type}: {$this->product->sku}",
            [
                'product_id' => $this->product->id,
                'product_sku' => $this->product->sku,
                'connection_id' => $this->connection->id,
                'error_message' => $message,
                'duration_ms' => $duration,
            ],
            IntegrationLog::INTEGRATION_BASELINKER,
            (string) $this->connection->id,
            $exception
        );

        // Update ProductErpData with error status
        $this->updateProductErpDataError($message);

        // Fail SyncJob
        if ($this->syncJob) {
            $this->syncJob->fail(
                $message,
                $exception?->getMessage(),
                $exception?->getTraceAsString()
            );
        }
    }

    /**
     * Handle a job failure (Laravel Queue callback).
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('PullProductFromERP job failed completely', [
            'product_id' => $this->product->id,
            'product_sku' => $this->product->sku,
            'connection_id' => $this->connection->id,
            'exception' => $exception->getMessage(),
        ]);

        // Update connection health
        $this->connection->updateConnectionHealth(
            ERPConnection::CONNECTION_ERROR,
            null,
            'Job failed: ' . $exception->getMessage()
        );

        // Fail SyncJob if exists
        if ($this->syncJob) {
            $this->syncJob->fail(
                'Job failed completely',
                $exception->getMessage(),
                $exception->getTraceAsString()
            );
        }
    }

    /**
     * Calculate retry delay (exponential backoff).
     */
    public function backoff(): array
    {
        return [60, 180, 600]; // 1 min, 3 min, 10 min
    }

    /**
     * ETAP_08.3: Update ProductErpData after successful pull.
     *
     * Updates the product_erp_data table with pulled data and timestamps.
     */
    protected function updateProductErpData(array $result): void
    {
        try {
            $erpData = ProductErpData::firstOrCreate(
                [
                    'product_id' => $this->product->id,
                    'erp_connection_id' => $this->connection->id,
                ],
                [
                    'sync_status' => 'pending',
                    'sync_direction' => 'bidirectional',
                ]
            );

            // Build external_data cache from result
            $externalData = $result['data'] ?? [];

            $erpData->update([
                'external_id' => $result['external_id'] ?? $erpData->external_id,
                'sync_status' => 'synced',
                'pending_fields' => null,
                'external_data' => $externalData,
                'last_sync_at' => Carbon::now(),
                'last_pull_at' => Carbon::now(),
                'error_message' => null,
            ]);

            Log::info('PullProductFromERP: ProductErpData updated', [
                'product_id' => $this->product->id,
                'connection_id' => $this->connection->id,
                'external_id' => $result['external_id'] ?? null,
                'fields_count' => count($externalData),
            ]);

        } catch (\Exception $e) {
            Log::warning('PullProductFromERP: Failed to update ProductErpData', [
                'product_id' => $this->product->id,
                'connection_id' => $this->connection->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update ProductErpData with error status.
     */
    protected function updateProductErpDataError(string $errorMessage): void
    {
        try {
            $erpData = ProductErpData::firstOrCreate(
                [
                    'product_id' => $this->product->id,
                    'erp_connection_id' => $this->connection->id,
                ],
                [
                    'sync_status' => 'pending',
                    'sync_direction' => 'bidirectional',
                ]
            );

            $erpData->update([
                'sync_status' => 'error',
                'error_message' => $errorMessage,
            ]);

        } catch (\Exception $e) {
            Log::warning('PullProductFromERP: Failed to update error status', [
                'product_id' => $this->product->id,
                'connection_id' => $this->connection->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
