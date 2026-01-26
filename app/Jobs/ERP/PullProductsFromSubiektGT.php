<?php

namespace App\Jobs\ERP;

use App\Models\ERPConnection;
use App\Models\SyncJob;
use App\Models\IntegrationLog;
use App\Services\ERP\SubiektGTService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * PullProductsFromSubiektGT Job
 *
 * ETAP: Subiekt GT ERP Integration
 *
 * Batch job for pulling products from Subiekt GT database.
 * Supports multiple sync modes with progress tracking.
 *
 * SYNC MODES:
 * - 'linked_only' (DEFAULT): Only pull products already linked to PPM (optimized)
 *   Uses pullLinkedProducts() - fetches only products with ProductErpData records.
 *   Compares tw_DataMod timestamps to skip unchanged products.
 *   Typical: 13 products processed in <5s instead of 5000+ in minutes.
 *
 * - 'prices', 'stock', 'basic_data': Scheduled sync modes (ETAP_08 FAZA 7)
 *   Uses pullLinkedProducts() - same optimized path as linked_only.
 *   Dynamic scheduler dispatches these modes every 15min-daily based on config.
 *   Only linked products are processed (not full Subiekt database).
 *
 * - 'full': Pull ALL products from Subiekt GT (up to limit)
 *   Uses pullAllProducts() - fetches from entire Subiekt database.
 *   Use for initial sync or when discovering new products. SLOW!
 *
 * - 'incremental': Pull products modified since timestamp
 *   Uses pullAllProducts() with 'since' filter.
 *
 * Features:
 * - ShouldBeUnique: prevents duplicate job execution
 * - Progress tracking via SyncJob model
 * - Incremental sync via tw_DataMod timestamp
 * - Configurable batch size (chunking)
 *
 * @package App\Jobs\ERP
 * @version 2.0 (2026-01-26: Added linked_only mode as default)
 */
class PullProductsFromSubiektGT implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Job timeout in seconds
     */
    public int $timeout = 3600; // 1 hour

    /**
     * Number of retry attempts
     */
    public int $tries = 3;

    /**
     * Backoff strategy (seconds between retries)
     */
    public array $backoff = [60, 300, 600]; // 1 min, 5 min, 10 min

    /**
     * The number of seconds after which the job's unique lock will be released.
     */
    public int $uniqueFor = 3600;

    /**
     * ERP Connection ID
     */
    protected int $connectionId;

    /**
     * Sync mode: 'linked_only' (default), 'full', 'incremental', 'stock_only'
     *
     * - linked_only: Only products with ProductErpData (optimized, default)
     * - full: All products from Subiekt GT (up to limit)
     * - incremental: Products modified since timestamp
     * - stock_only: Only sync stock data
     */
    protected string $mode;

    /**
     * Timestamp for incremental sync (ISO 8601)
     */
    protected ?string $since;

    /**
     * Maximum products to process per job
     */
    protected int $limit;

    /**
     * Batch size for chunked processing
     */
    protected int $batchSize;

    /**
     * Optional SyncJob ID for progress tracking
     */
    protected ?int $syncJobId;

    /**
     * Create a new job instance.
     *
     * @param int $connectionId ERP connection ID
     * @param string $mode Sync mode: 'linked_only' (default), 'full', 'incremental', 'stock_only'
     * @param string|null $since Timestamp for incremental (optional)
     * @param int $limit Max products to process (only for 'full' mode)
     * @param int $batchSize Chunk size for processing
     * @param int|null $syncJobId SyncJob ID for progress tracking
     */
    public function __construct(
        int $connectionId,
        string $mode = 'linked_only',  // CHANGED: Default to optimized mode
        ?string $since = null,
        int $limit = 5000,  // Only used for 'full' mode
        int $batchSize = 100,
        ?int $syncJobId = null
    ) {
        $this->connectionId = $connectionId;
        $this->mode = $mode;
        $this->since = $since;
        $this->limit = $limit;
        $this->batchSize = $batchSize;
        $this->syncJobId = $syncJobId;

        // Use specific queue for ERP jobs
        $this->onQueue('erp-sync');
    }

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return 'pull_subiekt_' . $this->connectionId . '_' . $this->mode;
    }

    /**
     * Execute the job.
     *
     * @param SubiektGTService $service
     * @return void
     */
    public function handle(SubiektGTService $service): void
    {
        $startTime = Carbon::now();

        // Get ERP connection
        $connection = ERPConnection::find($this->connectionId);

        if (!$connection) {
            Log::error('PullProductsFromSubiektGT: Connection not found', [
                'connection_id' => $this->connectionId,
            ]);
            $this->updateSyncJobStatus('failed', 'Connection not found');
            return;
        }

        if (!$connection->is_active) {
            Log::warning('PullProductsFromSubiektGT: Connection is inactive', [
                'connection_id' => $this->connectionId,
            ]);
            $this->updateSyncJobStatus('failed', 'Connection is inactive');
            return;
        }

        Log::info('PullProductsFromSubiektGT: Starting job', [
            'connection_id' => $this->connectionId,
            'connection_name' => $connection->instance_name,
            'mode' => $this->mode,
            'since' => $this->since,
            'limit' => $this->limit,
        ]);

        // Update sync job status
        $this->updateSyncJobStatus('running', 'Starting product pull');

        try {
            // Execute pull operation based on mode
            // ETAP_08 FAZA 7: Scheduled modes (prices, stock, basic_data) use optimized linked_only
            // This ensures scheduled jobs only process linked products (like PrestaShop jobs)
            $optimizedModes = ['linked_only', 'prices', 'stock', 'basic_data', 'stock_only'];

            if (in_array($this->mode, $optimizedModes)) {
                // OPTIMIZED: Pull only products already linked to this ERP connection
                // This compares tw_DataMod with last_pull_at and skips unchanged products
                Log::info('PullProductsFromSubiektGT: Using optimized linked_only mode', [
                    'sync_type' => $this->mode,
                ]);
                $results = $service->pullLinkedProducts($connection, [
                    'sync_type' => $this->mode, // Pass sync type for future filtering
                ]);
            } else {
                // LEGACY: Pull all/incremental from Subiekt GT (full database scan)
                // Used for 'full' mode or manual sync operations
                $filters = [
                    'mode' => $this->mode,
                    'limit' => $this->limit,
                ];

                if ($this->mode === 'incremental' && $this->since) {
                    $filters['since'] = $this->since;
                } elseif ($this->mode === 'incremental' && !$this->since) {
                    // Use last sync timestamp from connection
                    $filters['since'] = $connection->last_sync_at?->toDateTimeString()
                        ?? Carbon::now()->subDay()->toDateTimeString();
                }

                Log::info('PullProductsFromSubiektGT: Using full/incremental mode', [
                    'filters' => $filters,
                ]);
                $results = $service->pullAllProducts($connection, $filters);
            }

            $duration = $startTime->diffInSeconds(Carbon::now());

            // Log results
            Log::info('PullProductsFromSubiektGT: Job completed', [
                'connection_id' => $this->connectionId,
                'mode' => $this->mode,
                'results' => $results,
                'duration_seconds' => $duration,
            ]);

            // Update connection timestamps
            $connection->update([
                'last_sync_at' => Carbon::now(),
                'next_scheduled_sync' => $this->calculateNextSync($connection),
            ]);

            // Update sync job status
            if ($results['success']) {
                // Build message based on mode
                $notFound = $results['not_found'] ?? 0;
                if ($this->mode === 'linked_only' && $notFound > 0) {
                    $message = sprintf(
                        'Pobrano %d produktow (nowe: %d, zaktualizowane: %d, pominiete: %d, nie znaleziono w Subiekt: %d)',
                        $results['total'],
                        $results['imported'] ?? 0,
                        $results['updated'] ?? 0,
                        $results['skipped'] ?? 0,
                        $notFound
                    );
                } else {
                    $message = sprintf(
                        'Pobrano %d produktow (nowe: %d, zaktualizowane: %d, pominiete: %d)',
                        $results['total'],
                        $results['imported'] ?? 0,
                        $results['updated'] ?? 0,
                        $results['skipped'] ?? 0
                    );
                }

                // ETAP_08: Build result_summary with product details
                $resultSummary = [
                    'imported_count' => $results['imported'] ?? 0,
                    'updated_count' => $results['updated'] ?? 0,
                    'skipped' => $results['skipped'] ?? 0,
                    'not_found' => $notFound,
                    'mode' => $this->mode,
                    'imported_products' => $results['imported_products'] ?? [],
                    'updated_products' => $results['updated_products'] ?? [],
                ];

                $this->updateSyncJobStatus('completed', $message, [
                    'total' => $results['total'],
                    'imported' => $results['imported'] ?? 0,
                    'updated' => $results['updated'] ?? 0,
                    'skipped' => $results['skipped'] ?? 0,
                    'not_found' => $notFound,
                    'duration_seconds' => $duration,
                    'result_summary' => $resultSummary,
                ]);
            } else {
                $this->updateSyncJobStatus('failed', 'Pull completed with errors', [
                    'errors' => $results['errors'] ?? [],
                ]);
            }

            // Log to IntegrationLog
            IntegrationLog::info(
                'products_pull_job',
                'Product pull job completed',
                [
                    'connection_id' => $this->connectionId,
                    'mode' => $this->mode,
                    'results' => $results,
                    'duration_seconds' => $duration,
                ],
                IntegrationLog::INTEGRATION_SUBIEKT_GT,
                (string) $this->connectionId
            );

        } catch (\Exception $e) {
            $duration = $startTime->diffInSeconds(Carbon::now());

            Log::error('PullProductsFromSubiektGT: Job failed', [
                'connection_id' => $this->connectionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->updateSyncJobStatus('failed', 'Exception: ' . $e->getMessage());

            // Update connection health
            $connection->updateConnectionHealth(
                ERPConnection::CONNECTION_ERROR,
                null,
                $e->getMessage()
            );

            // Log to IntegrationLog
            IntegrationLog::error(
                'products_pull_job',
                'Product pull job failed',
                [
                    'connection_id' => $this->connectionId,
                    'error' => $e->getMessage(),
                ],
                IntegrationLog::INTEGRATION_SUBIEKT_GT,
                (string) $this->connectionId,
                $e
            );

            throw $e; // Rethrow for retry mechanism
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('PullProductsFromSubiektGT: Job permanently failed', [
            'connection_id' => $this->connectionId,
            'error' => $exception->getMessage(),
        ]);

        $this->updateSyncJobStatus('failed', 'Job permanently failed: ' . $exception->getMessage());

        // Update connection health
        $connection = ERPConnection::find($this->connectionId);
        if ($connection) {
            $connection->updateConnectionHealth(
                ERPConnection::CONNECTION_ERROR,
                null,
                'Pull job permanently failed: ' . $exception->getMessage()
            );
        }
    }

    /**
     * Update SyncJob status if tracking is enabled.
     *
     * @param string $status
     * @param string|null $message
     * @param array $metadata
     * @return void
     */
    protected function updateSyncJobStatus(string $status, ?string $message = null, array $metadata = []): void
    {
        if (!$this->syncJobId) {
            return;
        }

        $syncJob = SyncJob::find($this->syncJobId);
        if (!$syncJob) {
            return;
        }

        $updateData = [
            'status' => $status,
        ];

        if ($message) {
            $updateData['status_message'] = $message;
        }

        // Extract stats from metadata for proper SyncJob fields
        if (!empty($metadata)) {
            $existing = $syncJob->metadata ?? [];
            $updateData['metadata'] = array_merge($existing, $metadata);

            // Map metadata to SyncJob columns for UI display
            if (isset($metadata['total'])) {
                $updateData['total_items'] = (int) $metadata['total'];
            }
            if (isset($metadata['imported']) || isset($metadata['updated'])) {
                $updateData['successful_items'] = (int) ($metadata['imported'] ?? 0) + (int) ($metadata['updated'] ?? 0);
                $updateData['processed_items'] = $updateData['successful_items'] + (int) ($metadata['skipped'] ?? 0);
            }
            if (isset($metadata['skipped'])) {
                $updateData['failed_items'] = 0; // skipped != failed
            }
            if (isset($metadata['duration_seconds'])) {
                $updateData['duration_seconds'] = (int) $metadata['duration_seconds'];
            }
            // Calculate progress
            if (isset($updateData['total_items']) && $updateData['total_items'] > 0) {
                $updateData['progress_percentage'] = min(100, round(
                    ($updateData['processed_items'] ?? 0) / $updateData['total_items'] * 100,
                    2
                ));
            }
            // ETAP_08: Save result_summary with product details
            if (isset($metadata['result_summary'])) {
                $updateData['result_summary'] = json_encode($metadata['result_summary']);
            }
        }

        if ($status === 'running' && !$syncJob->started_at) {
            $updateData['started_at'] = Carbon::now();
        }

        if ($status === 'completed' || $status === 'failed') {
            $updateData['completed_at'] = Carbon::now();
            $updateData['progress_percentage'] = 100.00;
        }

        $syncJob->update($updateData);
    }

    /**
     * Calculate next scheduled sync based on connection settings.
     *
     * @param ERPConnection $connection
     * @return Carbon
     */
    protected function calculateNextSync(ERPConnection $connection): Carbon
    {
        $syncSettings = $connection->sync_settings ?? [];
        $intervalMinutes = $syncSettings['sync_interval_minutes'] ?? 360; // Default 6 hours

        return Carbon::now()->addMinutes($intervalMinutes);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'erp-sync',
            'subiekt-gt',
            'connection:' . $this->connectionId,
            'mode:' . $this->mode,
        ];
    }
}
