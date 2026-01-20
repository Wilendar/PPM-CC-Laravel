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
 * Supports full and incremental sync modes with progress tracking.
 *
 * Features:
 * - ShouldBeUnique: prevents duplicate job execution
 * - Progress tracking via SyncJob model
 * - Incremental sync via tw_DataMod timestamp
 * - Configurable batch size (chunking)
 *
 * @package App\Jobs\ERP
 * @version 1.0
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
     * Sync mode: 'full', 'incremental', 'stock_only'
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
     * @param string $mode Sync mode: 'full', 'incremental', 'stock_only'
     * @param string|null $since Timestamp for incremental (optional)
     * @param int $limit Max products to process
     * @param int $batchSize Chunk size for processing
     * @param int|null $syncJobId SyncJob ID for progress tracking
     */
    public function __construct(
        int $connectionId,
        string $mode = 'full',
        ?string $since = null,
        int $limit = 5000,
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
        $this->updateSyncJobStatus('processing', 'Starting product pull');

        try {
            // Prepare filters based on mode
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

            // Execute pull operation
            $results = $service->pullAllProducts($connection, $filters);

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
                $message = sprintf(
                    'Pobrano %d produktow (nowe: %d, zaktualizowane: %d, pominięte: %d)',
                    $results['total'],
                    $results['imported'] ?? 0,
                    $results['updated'] ?? 0,
                    $results['skipped'] ?? 0
                );

                $this->updateSyncJobStatus('completed', $message, [
                    'total' => $results['total'],
                    'imported' => $results['imported'] ?? 0,
                    'updated' => $results['updated'] ?? 0,
                    'skipped' => $results['skipped'] ?? 0,
                    'duration_seconds' => $duration,
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

        if (!empty($metadata)) {
            $existing = $syncJob->metadata ?? [];
            $updateData['metadata'] = array_merge($existing, $metadata);
        }

        if ($status === 'completed' || $status === 'failed') {
            $updateData['completed_at'] = Carbon::now();
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
