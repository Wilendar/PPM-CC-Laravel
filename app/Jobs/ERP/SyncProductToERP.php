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
 * SyncProductToERP Job
 *
 * ETAP_08: BaseLinker ERP Integration
 *
 * Asynchroniczne zadanie synchronizacji pojedynczego produktu do systemu ERP.
 * Wspiera wszystkie typy ERP (Baselinker, Subiekt GT, Dynamics).
 *
 * Features:
 * - ShouldBeUnique - zapobiega duplikatom w kolejce
 * - Retry logic z exponential backoff
 * - Progress tracking via SyncJob model
 * - Comprehensive error handling i logging
 */
class SyncProductToERP implements ShouldQueue, ShouldBeUnique
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
        return 'sync_product_' . $this->product->id . '_' . $this->erpConnection->id;
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Product $product,
        public ERPConnection $erpConnection,
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
            $service = $erpManager->getService($this->erpConnection);

            // Sync product
            $result = $service->syncProductToERP($this->erpConnection, $this->product);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if ($result['success']) {
                // Update connection health
                $this->erpConnection->updateConnectionHealth(
                    ERPConnection::CONNECTION_CONNECTED,
                    $duration
                );

                // Update sync stats
                $this->erpConnection->updateSyncStats(true, 1, $duration);

                // ETAP_08.3: Update ProductErpData model
                $this->updateProductErpData($result['external_id'] ?? null);

                // Log success
                IntegrationLog::info(
                    'sync_product_job',
                    "Product synced to {$this->erpConnection->erp_type}: {$this->product->sku}",
                    [
                        'product_id' => $this->product->id,
                        'product_sku' => $this->product->sku,
                        'connection_id' => $this->erpConnection->id,
                        'external_id' => $result['external_id'] ?? null,
                        'duration_ms' => $duration,
                    ],
                    IntegrationLog::INTEGRATION_BASELINKER,
                    (string) $this->erpConnection->id
                );

                // Complete SyncJob
                if ($this->syncJob) {
                    $this->syncJob->complete([
                        'external_id' => $result['external_id'] ?? null,
                        'message' => $result['message'],
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
        $this->erpConnection->updateConnectionHealth(
            ERPConnection::CONNECTION_ERROR,
            $duration,
            $message
        );

        // Update sync stats
        $this->erpConnection->updateSyncStats(false, 0, $duration);

        // Log error
        IntegrationLog::error(
            'sync_product_job',
            "Failed to sync product to {$this->erpConnection->erp_type}: {$this->product->sku}",
            [
                'product_id' => $this->product->id,
                'product_sku' => $this->product->sku,
                'connection_id' => $this->erpConnection->id,
                'error_message' => $message,
                'duration_ms' => $duration,
            ],
            IntegrationLog::INTEGRATION_BASELINKER,
            (string) $this->erpConnection->id,
            $exception
        );

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
        Log::error('SyncProductToERP job failed completely', [
            'product_id' => $this->product->id,
            'product_sku' => $this->product->sku,
            'connection_id' => $this->erpConnection->id,
            'exception' => $exception->getMessage(),
        ]);

        // Update connection health
        $this->erpConnection->updateConnectionHealth(
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
     * ETAP_08.3: Update ProductErpData after successful sync.
     *
     * Updates the product_erp_data table with sync status and timestamps.
     */
    protected function updateProductErpData(?string $externalId): void
    {
        try {
            $erpData = ProductErpData::firstOrCreate(
                [
                    'product_id' => $this->product->id,
                    'erp_connection_id' => $this->erpConnection->id,
                ],
                [
                    'sync_status' => 'pending',
                    'sync_direction' => 'bidirectional',
                ]
            );

            $erpData->update([
                'external_id' => $externalId ?? $erpData->external_id,
                'sync_status' => 'synced',
                'pending_fields' => null,
                'last_sync_at' => Carbon::now(),
                'last_push_at' => Carbon::now(),
                'error_message' => null,
            ]);

            Log::info('SyncProductToERP: ProductErpData updated', [
                'product_id' => $this->product->id,
                'connection_id' => $this->erpConnection->id,
                'external_id' => $externalId,
            ]);

        } catch (\Exception $e) {
            Log::warning('SyncProductToERP: Failed to update ProductErpData', [
                'product_id' => $this->product->id,
                'connection_id' => $this->erpConnection->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
