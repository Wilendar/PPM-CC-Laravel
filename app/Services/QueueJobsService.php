<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Queue Jobs Monitoring Service
 *
 * Provides comprehensive monitoring and management of Laravel queue jobs.
 * Tracks active, failed, and stuck jobs across all queue connections.
 *
 * @package App\Services
 */
class QueueJobsService
{
    /**
     * Get all active jobs (pending + processing)
     *
     * @return Collection<int, array>
     */
    public function getActiveJobs(): Collection
    {
        return DB::table('jobs')
            ->select([
                'id',
                'queue',
                'payload',
                'attempts',
                'reserved_at',
                'available_at',
                'created_at',
            ])
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn($job) => $this->parseJob($job));
    }

    /**
     * Get failed jobs
     *
     * @return Collection<int, array>
     */
    public function getFailedJobs(): Collection
    {
        return DB::table('failed_jobs')
            ->select([
                'id',
                'uuid',
                'connection',
                'queue',
                'payload',
                'exception',
                'failed_at',
            ])
            ->orderBy('failed_at', 'desc')
            ->get()
            ->map(fn($job) => $this->parseFailedJob($job));
    }

    /**
     * Get stuck jobs (processing > 5 minutes)
     *
     * @return Collection<int, array>
     */
    public function getStuckJobs(): Collection
    {
        $fiveMinutesAgo = now()->subMinutes(5)->timestamp;

        return DB::table('jobs')
            ->select([
                'id',
                'queue',
                'payload',
                'attempts',
                'reserved_at',
                'available_at',
                'created_at',
            ])
            ->whereNotNull('reserved_at')
            ->where('reserved_at', '<', $fiveMinutesAgo)
            ->orderBy('reserved_at', 'asc')
            ->get()
            ->map(fn($job) => $this->parseJob($job));
    }

    /**
     * Parse job payload and extract useful information
     *
     * @param object $job
     * @return array
     */
    public function parseJob(object $job): array
    {
        $payload = json_decode($job->payload, true);
        $commandName = $payload['displayName'] ?? 'Unknown';

        // Extract command data (with error handling for missing dependencies)
        $data = [];
        if (isset($payload['data']['command'])) {
            try {
                $command = unserialize($payload['data']['command']);
                $data = $this->extractJobData($command);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                // Job references a deleted model - gracefully handle
                $data['error'] = 'Referenced model no longer exists';
                if (preg_match('/model \[(.+?)\]/', $e->getMessage(), $matches)) {
                    $data['missing_model'] = $matches[1];
                }
            } catch (\Exception $e) {
                // Other deserialization errors
                $data['error'] = 'Failed to parse job data: ' . $e->getMessage();
            }
        }

        return [
            'id' => $job->id,
            'queue' => $job->queue,
            'job_name' => $commandName,
            'status' => $job->reserved_at ? 'processing' : 'pending',
            'attempts' => $job->attempts,
            'data' => $data,
            'created_at' => Carbon::createFromTimestamp($job->created_at),
            'reserved_at' => $job->reserved_at ? Carbon::createFromTimestamp($job->reserved_at) : null,
            'available_at' => Carbon::createFromTimestamp($job->available_at),
        ];
    }

    /**
     * Parse failed job payload
     *
     * @param object $job
     * @return array
     */
    public function parseFailedJob(object $job): array
    {
        $payload = json_decode($job->payload, true);
        $commandName = $payload['displayName'] ?? 'Unknown';

        // Extract command data (with error handling for missing dependencies)
        $data = [];
        if (isset($payload['data']['command'])) {
            try {
                $command = unserialize($payload['data']['command']);
                $data = $this->extractJobData($command);
            } catch (\Exception $e) {
                // Failed to unserialize (missing dependencies like deleted models)
                // Try to extract basic info from payload
                $data['error'] = 'Failed to parse job data: ' . $e->getMessage();

                // Attempt to extract basic info from exception
                if (preg_match('/model \[(.+?)\]/', $e->getMessage(), $matches)) {
                    $data['missing_model'] = $matches[1];
                }
            }
        }

        // Extract first line of exception
        $exceptionLines = explode("\n", $job->exception);
        $exceptionMessage = $exceptionLines[0] ?? 'Unknown error';

        return [
            'id' => $job->id,
            'uuid' => $job->uuid,
            'job_name' => $commandName,
            'queue' => $job->queue,
            'connection' => $job->connection,
            'data' => $data,
            'exception' => $job->exception,
            'exception_message' => $exceptionMessage,
            'failed_at' => $job->failed_at,
        ];
    }

    /**
     * Extract useful data from job command
     *
     * Extracts product_id, sku, shop_id, shop_name from common job types
     *
     * @param mixed $data
     * @return array
     */
    public function extractJobData(mixed $data): array
    {
        $extracted = [];

        // Try to extract data (with error handling for protected properties)
        try {
            if (is_object($data)) {
                // Check for product property (use reflection for protected properties)
                if (property_exists($data, 'product')) {
                    try {
                        $product = $this->getPropertyValue($data, 'product');
                        if ($product) {
                            $extracted['product_id'] = $product->id ?? null;
                            $extracted['sku'] = $product->sku ?? null;
                        }
                    } catch (\Exception $e) {
                        // Skip if property access fails
                    }
                }

                // Check for shop property (use reflection for protected properties)
                if (property_exists($data, 'shop')) {
                    try {
                        $shop = $this->getPropertyValue($data, 'shop');
                        if ($shop) {
                            $extracted['shop_id'] = $shop->id ?? null;
                            $extracted['shop_name'] = $shop->name ?? null;
                        }
                    } catch (\Exception $e) {
                        // Skip if property access fails
                    }
                }

                // Check for batch property (use reflection for protected properties)
                if (property_exists($data, 'batch')) {
                    try {
                        $batch = $this->getPropertyValue($data, 'batch');
                        if ($batch) {
                            $extracted['batch_id'] = $batch->id ?? null;
                        }
                    } catch (\Exception $e) {
                        // Skip if property access fails
                    }
                }
            }
        } catch (\Exception $e) {
            // Failed to extract any data
            $extracted['extraction_error'] = $e->getMessage();
        }

        return $extracted;
    }

    /**
     * Get property value using Reflection (handles protected/private properties)
     *
     * @param object $object
     * @param string $property
     * @return mixed
     */
    protected function getPropertyValue(object $object, string $property): mixed
    {
        try {
            $reflection = new \ReflectionClass($object);
            $prop = $reflection->getProperty($property);
            $prop->setAccessible(true);
            return $prop->getValue($object);
        } catch (\Exception $e) {
            // Try direct access as fallback
            return $object->$property ?? null;
        }
    }

    /**
     * Retry failed job
     *
     * @param string $uuid
     * @return int Artisan command exit code
     */
    public function retryFailedJob(string $uuid): int
    {
        return Artisan::call('queue:retry', ['id' => [$uuid]]);
    }

    /**
     * Delete failed job
     *
     * @param string $uuid
     * @return int Number of deleted rows
     */
    public function deleteFailedJob(string $uuid): int
    {
        return DB::table('failed_jobs')->where('uuid', $uuid)->delete();
    }

    /**
     * Cancel pending job
     *
     * Removes job from jobs table before processing
     *
     * @param int $id
     * @return int Number of deleted rows
     */
    public function cancelPendingJob(int $id): int
    {
        return DB::table('jobs')->where('id', $id)->delete();
    }

    /**
     * Detect orphaned jobs
     *
     * Cross-references sync_jobs with jobs/failed_jobs tables to find:
     * - Type 1: Queue jobs without corresponding SyncJob record
     * - Type 2: SyncJobs without corresponding queue job
     *
     * @return array{queue_without_sync: Collection, sync_without_queue: Collection, recommendations: array}
     */
    public function detectOrphanedJobs(): array
    {
        // TYPE 1: Queue jobs (active) without SyncJob record
        // These are jobs that were dispatched but didn't create SyncJob entry
        $queueWithoutSync = DB::table('jobs')
            ->select([
                'id',
                'queue',
                'payload',
                'attempts',
                'created_at',
                'reserved_at',
            ])
            ->get()
            ->filter(function ($job) {
                // Extract displayName from payload
                $payload = json_decode($job->payload, true);
                $commandName = $payload['displayName'] ?? null;

                // Only check PrestaShop sync jobs
                if ($commandName !== 'App\\Jobs\\PrestaShop\\SyncProductsJob') {
                    return false;
                }

                // Try to extract SyncJob model from command
                try {
                    if (isset($payload['data']['command'])) {
                        $command = unserialize($payload['data']['command']);

                        // Access protected syncJob property via Reflection
                        $reflection = new \ReflectionClass($command);
                        if ($reflection->hasProperty('syncJob')) {
                            $prop = $reflection->getProperty('syncJob');
                            $prop->setAccessible(true);
                            $syncJob = $prop->getValue($command);

                            // Check if SyncJob exists in database
                            $syncJobExists = DB::table('sync_jobs')
                                ->where('id', $syncJob->id)
                                ->exists();

                            // Orphaned if SyncJob doesn't exist
                            return !$syncJobExists;
                        }
                    }
                } catch (\Exception $e) {
                    // If unserialization fails, consider it orphaned (missing dependencies)
                    return true;
                }

                // Default: not orphaned
                return false;
            })
            ->map(fn($job) => $this->parseJob($job));

        // TYPE 2: SyncJobs without corresponding queue job
        // Only for pending/running status (completed/failed/cancelled are expected to have no queue job)
        $syncWithoutQueue = DB::table('sync_jobs')
            ->select([
                'id',
                'job_id',
                'job_name',
                'job_type',
                'status',
                'queue_job_id',
                'queue_name',
                'created_at',
                'started_at',
            ])
            ->whereIn('status', ['pending', 'running'])
            ->whereNotNull('queue_job_id')
            ->get()
            ->filter(function ($syncJob) {
                // Check if queue job exists in jobs table
                $queueJobExists = DB::table('jobs')
                    ->where('id', $syncJob->queue_job_id)
                    ->exists();

                if ($queueJobExists) {
                    return false; // Not orphaned
                }

                // Check if it's in failed_jobs table
                $failedJobExists = DB::table('failed_jobs')
                    ->whereRaw("JSON_EXTRACT(payload, '$.data.command') LIKE ?", [
                        '%' . $syncJob->job_id . '%'
                    ])
                    ->exists();

                // Orphaned only if NOT in jobs AND NOT in failed_jobs
                return !$failedJobExists;
            });

        // Generate recommendations based on findings
        $recommendations = $this->generateOrphanedJobRecommendations(
            $queueWithoutSync,
            $syncWithoutQueue
        );

        return [
            'queue_without_sync' => $queueWithoutSync,
            'sync_without_queue' => $syncWithoutQueue,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Generate recommendations for orphaned jobs
     *
     * @param Collection $queueWithoutSync
     * @param Collection $syncWithoutQueue
     * @return array
     */
    protected function generateOrphanedJobRecommendations(
        Collection $queueWithoutSync,
        Collection $syncWithoutQueue
    ): array {
        $recommendations = [];

        // Type 1 recommendations
        if ($queueWithoutSync->isNotEmpty()) {
            $recommendations[] = [
                'type' => 'queue_without_sync',
                'severity' => 'high',
                'count' => $queueWithoutSync->count(),
                'message' => 'Found queue jobs without SyncJob records. These may indicate failed job creation or deleted SyncJob records.',
                'actions' => [
                    'safe' => 'Review job details and cancel if no longer needed',
                    'risky' => 'Delete queue jobs (may lose in-progress work)',
                ],
            ];
        }

        // Type 2 recommendations
        if ($syncWithoutQueue->isNotEmpty()) {
            $recommendations[] = [
                'type' => 'sync_without_queue',
                'severity' => 'medium',
                'count' => $syncWithoutQueue->count(),
                'message' => 'Found SyncJob records without corresponding queue jobs. These may be stuck jobs that were never processed or queue jobs that were manually deleted.',
                'actions' => [
                    'safe' => 'Mark SyncJobs as failed with reason "Queue job not found"',
                    'risky' => 'Retry these jobs (may cause duplicates)',
                ],
            ];
        }

        // All clear
        if (empty($recommendations)) {
            $recommendations[] = [
                'type' => 'all_clear',
                'severity' => 'none',
                'count' => 0,
                'message' => 'No orphaned jobs detected. All sync jobs are properly cross-referenced.',
                'actions' => [],
            ];
        }

        return $recommendations;
    }

    /**
     * Cleanup orphaned queue jobs (Type 1)
     *
     * Removes queue jobs that don't have corresponding SyncJob records
     *
     * @param array $jobIds Array of queue job IDs to delete
     * @return int Number of deleted jobs
     */
    public function cleanupOrphanedQueueJobs(array $jobIds): int
    {
        return DB::table('jobs')->whereIn('id', $jobIds)->delete();
    }

    /**
     * Mark orphaned SyncJobs as failed (Type 2)
     *
     * Updates SyncJob status to failed when queue job doesn't exist
     *
     * @param array $syncJobIds Array of SyncJob IDs to mark as failed
     * @return int Number of updated records
     */
    public function markOrphanedSyncJobsAsFailed(array $syncJobIds): int
    {
        return DB::table('sync_jobs')
            ->whereIn('id', $syncJobIds)
            ->update([
                'status' => 'failed',
                'error_message' => 'Queue job not found (orphaned)',
                'error_details' => 'This sync job was marked as failed because the corresponding queue job could not be found. This may indicate that the queue job was manually deleted or never dispatched.',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
