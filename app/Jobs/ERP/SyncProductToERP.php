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
     * Sync options for selective field synchronization.
     *
     * Supported options:
     * - 'stock_columns' => ['quantity', 'minimum'] - only sync specified stock columns
     * - 'sync_prices' => true/false - whether to sync prices
     * - 'sync_stock' => true/false - whether to sync stock at all
     */
    public array $syncOptions = [];

    /**
     * Create a new job instance.
     *
     * ETAP_08.5: Capture userId during dispatch (web context) because
     * auth()->id() returns NULL in queue worker context.
     *
     * @param Product $product Product to sync
     * @param ERPConnection $erpConnection ERP connection to use
     * @param SyncJob|null $syncJob Optional SyncJob for tracking
     * @param array $syncOptions Selective sync options (stock_columns, sync_prices, sync_stock)
     */
    public function __construct(
        public Product $product,
        public ERPConnection $erpConnection,
        public ?SyncJob $syncJob = null,
        array $syncOptions = []
    ) {
        $this->syncOptions = $syncOptions;
        $this->onQueue($this->determineQueue());

        // Capture user ID from web context (null in queue = SYSTEM)
        $this->userId = auth()->id();
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
     * User ID captured during dispatch (web context).
     * Queue context has no auth() so we capture it upfront.
     */
    public ?int $userId = null;

    /**
     * Execute the job.
     */
    public function handle(ERPServiceManager $erpManager): void
    {
        $startTime = microtime(true);

        // ETAP_08.5: Create SyncJob record for tracking (like SyncProductToPrestaShop)
        // This allows UI to track job progress
        if (!$this->syncJob) {
            $this->syncJob = SyncJob::create([
                'job_id' => \Str::uuid(),
                'job_type' => SyncJob::JOB_PRODUCT_SYNC,
                'job_name' => "ERP Sync: {$this->product->sku} -> {$this->erpConnection->instance_name}",
                'source_type' => SyncJob::TYPE_PPM,
                'source_id' => $this->product->id,
                'target_type' => $this->erpConnection->erp_type,
                'target_id' => (string) $this->erpConnection->id,
                'status' => SyncJob::STATUS_PENDING,
                'user_id' => $this->userId,
                // ETAP_08.5 FIX: Set progress metrics for single product sync
                'total_items' => 1,
                'processed_items' => 0,
                'successful_items' => 0,
                'failed_items' => 0,
                'meta_data' => [
                    'product_id' => $this->product->id,
                    'product_sku' => $this->product->sku,
                    'erp_connection_id' => $this->erpConnection->id,
                    'erp_type' => $this->erpConnection->erp_type,
                ],
            ]);
        }

        // Update SyncJob to running
        $this->syncJob->start();

        try {
            // Get appropriate ERP service
            $service = $erpManager->getService($this->erpConnection);

            // Sync product with optional sync options
            // Pass syncOptions to enable selective field synchronization (e.g., only dirty stock columns)
            $result = $service->syncProductToERP($this->erpConnection, $this->product, $this->syncOptions);

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

                // ETAP_08.5 FIX: Removed redundant IntegrationLog here
                // makeRequest() in BaselinkerService already logs api_call_* with full HTTP/BL details
                // Having 2 logs per sync was confusing in admin/shops/sync UI

                // Complete SyncJob with progress metrics
                if ($this->syncJob) {
                    // ETAP_08.5 FIX: Update progress metrics for single product sync
                    $this->syncJob->update([
                        'processed_items' => 1,
                        'successful_items' => 1,
                        'failed_items' => 0,
                    ]);
                    $this->syncJob->complete([
                        'external_id' => $result['external_id'] ?? null,
                        'message' => $result['message'],
                        'action' => $result['action'] ?? 'synced',
                        'sku' => $result['sku'] ?? $this->product->sku,
                        'rows_affected' => $result['rows_affected'] ?? null,
                        'updated_fields' => $result['updated_fields'] ?? [],
                        'prices_updated' => $result['prices_updated'] ?? 0,
                        'erp_type' => $this->erpConnection->erp_type,
                        'connection_name' => $this->erpConnection->instance_name,
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

        // Fail SyncJob with progress metrics
        if ($this->syncJob) {
            // ETAP_08.5 FIX: Update progress metrics for failed single product sync
            $this->syncJob->update([
                'processed_items' => 1,
                'successful_items' => 0,
                'failed_items' => 1,
            ]);
            $this->syncJob->fail(
                $message,
                $exception?->getMessage(),
                $exception?->getTraceAsString()
            );
        }

        // FIX 2026-01-22: Update ProductErpData status to error
        // This is critical for UI to reflect the failed state
        try {
            $erpData = ProductErpData::where('product_id', $this->product->id)
                ->where('erp_connection_id', $this->erpConnection->id)
                ->first();

            if ($erpData) {
                $erpData->update([
                    'sync_status' => ProductErpData::STATUS_ERROR,
                    'error_message' => $message,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('SyncProductToERP: Failed to update ProductErpData status', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
            ]);
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
