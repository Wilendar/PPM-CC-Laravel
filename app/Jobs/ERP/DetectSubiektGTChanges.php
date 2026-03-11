<?php

namespace App\Jobs\ERP;

use App\Models\ERPConnection;
use App\Models\SyncJob;
use App\Models\IntegrationLog;
use App\Services\ERP\SubiektGTService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * DetectSubiektGTChanges Job
 *
 * ETAP: Subiekt GT ERP Integration
 *
 * Lightweight job for detecting changes in Subiekt GT database.
 * Dispatches PullProductsFromSubiektGT if changes detected.
 *
 * Features:
 * - Fast execution (single COUNT query)
 * - No heavy database operations
 * - Automatic incremental pull dispatch
 * - Designed for frequent scheduling (every 5-15 min)
 *
 * @package App\Jobs\ERP
 * @version 1.0
 */
class DetectSubiektGTChanges implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Job timeout in seconds (should be quick)
     */
    public int $timeout = 60;

    /**
     * Number of retry attempts
     */
    public int $tries = 2;

    /**
     * ERP Connection ID
     */
    protected int $connectionId;

    /**
     * Minimum number of changes to trigger pull
     */
    protected int $threshold;

    /**
     * Create a new job instance.
     *
     * @param int $connectionId ERP connection ID
     * @param int $threshold Minimum changes to trigger pull (default: 1)
     */
    public function __construct(int $connectionId, int $threshold = 1)
    {
        $this->connectionId = $connectionId;
        $this->threshold = $threshold;

        // Use default queue (not erp-sync to avoid blocking)
        $this->onQueue('default');
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
            Log::warning('DetectSubiektGTChanges: Connection not found', [
                'connection_id' => $this->connectionId,
            ]);
            return;
        }

        if (!$connection->is_active) {
            Log::debug('DetectSubiektGTChanges: Connection is inactive, skipping', [
                'connection_id' => $this->connectionId,
            ]);
            return;
        }

        // Skip if auto-sync is disabled
        if (!$connection->auto_sync_products) {
            Log::debug('DetectSubiektGTChanges: Auto-sync disabled, skipping', [
                'connection_id' => $this->connectionId,
            ]);
            return;
        }

        try {
            // Calculate "since" timestamp (last sync or fallback)
            $since = $connection->last_sync_at?->toDateTimeString()
                ?? Carbon::now()->subHours(6)->toDateTimeString();

            // Quick check: count modified products
            $modifiedCount = $service->getModifiedProductsCount($connection, $since);

            $duration = $startTime->diffInMilliseconds(Carbon::now());

            Log::debug('DetectSubiektGTChanges: Check completed', [
                'connection_id' => $this->connectionId,
                'since' => $since,
                'modified_count' => $modifiedCount,
                'threshold' => $this->threshold,
                'duration_ms' => $duration,
            ]);

            // Dispatch incremental pull if changes detected
            if ($modifiedCount >= $this->threshold) {
                Log::info('DetectSubiektGTChanges: Changes detected, dispatching pull', [
                    'connection_id' => $this->connectionId,
                    'modified_count' => $modifiedCount,
                    'since' => $since,
                ]);

                // Create SyncJob for tracking
                $syncJob = SyncJob::create([
                    'target_type' => 'subiekt_gt',
                    'target_id' => $this->connectionId,
                    'job_type' => 'pull_products',
                    'status' => 'pending',
                    'status_message' => 'Wykryto zmiany: ' . $modifiedCount . ' produktow',
                    'metadata' => [
                        'mode' => 'incremental',
                        'since' => $since,
                        'detected_changes' => $modifiedCount,
                        'triggered_by' => 'change_detection',
                    ],
                ]);

                // Dispatch pull job
                PullProductsFromSubiektGT::dispatch(
                    $this->connectionId,
                    'incremental',
                    $since,
                    $modifiedCount + 100, // Add buffer
                    100,
                    $syncJob->id
                );

                // Update connection health (successful check)
                $connection->updateConnectionHealth(
                    ERPConnection::CONNECTION_CONNECTED,
                    $duration / 1000 // Convert to seconds
                );

            } else {
                // Update connection health (successful check, no changes)
                $connection->updateConnectionHealth(
                    ERPConnection::CONNECTION_CONNECTED,
                    $duration / 1000
                );
            }

        } catch (\Exception $e) {
            Log::error('DetectSubiektGTChanges: Check failed', [
                'connection_id' => $this->connectionId,
                'error' => $e->getMessage(),
            ]);

            // Update connection health
            $connection->updateConnectionHealth(
                ERPConnection::CONNECTION_ERROR,
                null,
                'Change detection failed: ' . $e->getMessage()
            );

            // Don't rethrow - this is a monitoring job, not critical
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'erp-monitoring',
            'subiekt-gt',
            'change-detection',
            'connection:' . $this->connectionId,
        ];
    }
}
