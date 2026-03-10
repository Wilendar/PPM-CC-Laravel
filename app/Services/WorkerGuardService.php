<?php

namespace App\Services;

use App\Models\WorkerHeartbeat;
use App\Models\JobProgress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * WorkerGuardService
 *
 * Central service for managing queue workers:
 * - Heartbeat registration/tracking
 * - Anti-duplication (prevent multiple workers on same job)
 * - Stale worker cleanup
 *
 * Used by bulk import/sync jobs and scheduler to coordinate workers.
 */
class WorkerGuardService
{
    /**
     * Default heartbeat threshold in seconds.
     * Worker is considered dead if no heartbeat within this time.
     */
    private const HEARTBEAT_THRESHOLD = 120;

    /**
     * Register a worker for a job (called at start of handle())
     *
     * @param string $jobId UUID from job_progress.job_id
     * @param string $workerType scheduler|manual|artisan
     * @param array $metadata Additional context (job class, shop_id, etc.)
     * @return int WorkerHeartbeat ID
     */
    public function registerWorker(string $jobId, string $workerType = 'scheduler', array $metadata = []): int
    {
        $pid = $this->getWorkerPid();

        // Find related job_progress record
        $jobProgress = JobProgress::where('job_id', $jobId)->first();

        $heartbeat = WorkerHeartbeat::create([
            'job_progress_id' => $jobProgress?->id,
            'job_id' => $jobId,
            'worker_pid' => $pid,
            'worker_type' => $workerType,
            'queue_name' => $metadata['queue'] ?? null,
            'status' => 'processing',
            'started_at' => now(),
            'last_heartbeat_at' => now(),
            'metadata' => $metadata,
        ]);

        // Update job_progress with worker info
        if ($jobProgress) {
            $jobProgress->update([
                'worker_pid' => $pid,
                'last_heartbeat_at' => now(),
            ]);
        }

        Log::info('WorkerGuard: Worker registered', [
            'heartbeat_id' => $heartbeat->id,
            'job_id' => $jobId,
            'worker_pid' => $pid,
            'worker_type' => $workerType,
        ]);

        return $heartbeat->id;
    }

    /**
     * Send heartbeat update (called periodically in processing loop)
     *
     * @param int $heartbeatId WorkerHeartbeat ID
     */
    public function sendHeartbeat(int $heartbeatId): void
    {
        $heartbeat = WorkerHeartbeat::find($heartbeatId);
        if (!$heartbeat) {
            return;
        }

        $heartbeat->sendHeartbeat();

        // Also update job_progress.last_heartbeat_at
        if ($heartbeat->job_progress_id) {
            DB::table('job_progress')
                ->where('id', $heartbeat->job_progress_id)
                ->update(['last_heartbeat_at' => now()]);
        }
    }

    /**
     * Unregister worker (called at end of handle() or in failed())
     *
     * @param int $heartbeatId WorkerHeartbeat ID
     */
    public function unregisterWorker(int $heartbeatId): void
    {
        $heartbeat = WorkerHeartbeat::find($heartbeatId);
        if (!$heartbeat) {
            return;
        }

        $heartbeat->markFinished();

        Log::info('WorkerGuard: Worker unregistered', [
            'heartbeat_id' => $heartbeatId,
            'job_id' => $heartbeat->job_id,
            'worker_pid' => $heartbeat->worker_pid,
        ]);
    }

    /**
     * Check if a job already has an active (alive) worker
     *
     * @param string $jobId UUID from job_progress.job_id
     * @return bool
     */
    public function hasActiveWorker(string $jobId): bool
    {
        $alive = WorkerHeartbeat::alive(self::HEARTBEAT_THRESHOLD)
            ->forJob($jobId)
            ->exists();

        if ($alive) {
            Log::warning('WorkerGuard: Active worker detected for job, skipping', [
                'job_id' => $jobId,
            ]);
        }

        return $alive;
    }

    /**
     * Check if a manual worker can be spawned
     * Returns false if any worker is currently processing
     *
     * @return bool
     */
    public function canSpawnManualWorker(): bool
    {
        $activeCount = WorkerHeartbeat::alive(self::HEARTBEAT_THRESHOLD)->count();

        if ($activeCount > 0) {
            Log::debug('WorkerGuard: Cannot spawn manual worker, active workers exist', [
                'active_count' => $activeCount,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Cleanup stale workers (dead heartbeats)
     *
     * Worker Guard v2: Uses 'interrupted' status instead of 'failed' to allow
     * the progress bar to remain visible. Only marks 'failed' after max retries.
     *
     * @param int $thresholdSeconds Seconds after which worker is considered dead
     * @return int Number of workers cleaned up
     */
    public function cleanupStaleWorkers(int $thresholdSeconds = 120): int
    {
        $staleWorkers = WorkerHeartbeat::dead($thresholdSeconds)->get();
        $count = $staleWorkers->count();

        foreach ($staleWorkers as $worker) {
            Log::warning('WorkerGuard: Marking stale worker as dead', [
                'heartbeat_id' => $worker->id,
                'job_id' => $worker->job_id,
                'last_heartbeat' => $worker->last_heartbeat_at?->toDateTimeString(),
                'worker_pid' => $worker->worker_pid,
            ]);

            $worker->markDead();

            // Worker Guard v2: Track death count and use 'interrupted' status
            if ($worker->job_progress_id) {
                $jobProgress = JobProgress::find($worker->job_progress_id);
                if ($jobProgress && in_array($jobProgress->status, ['running', 'interrupted'])) {
                    $deathCount = ($jobProgress->getMetadataValue('worker_death_count', 0)) + 1;
                    $maxDeaths = 3; // After 3 deaths, mark as truly failed

                    if ($deathCount >= $maxDeaths) {
                        // Max retries exceeded — mark as failed
                        $jobProgress->update([
                            'status' => 'failed',
                            'completed_at' => now(),
                        ]);
                        $jobProgress->updateMetadata(['worker_death_count' => $deathCount]);
                        $jobProgress->addError(
                            'SYSTEM',
                            "Worker przestal odpowiadac {$deathCount}x. Job oznaczony jako nieudany po przekroczeniu limitu prob."
                        );

                        Log::warning('WorkerGuard: Marked job_progress as failed (max deaths exceeded)', [
                            'job_progress_id' => $worker->job_progress_id,
                            'job_id' => $worker->job_id,
                            'death_count' => $deathCount,
                        ]);
                    } else {
                        // Mark as interrupted — will be retried
                        $jobProgress->update(['status' => 'interrupted']);
                        $jobProgress->updateMetadata([
                            'worker_death_count' => $deathCount,
                            'last_interrupted_at' => now()->toDateTimeString(),
                        ]);
                        $jobProgress->addError(
                            'SYSTEM',
                            "Worker przestal odpowiadac (proba {$deathCount}/{$maxDeaths}). Wznowienie za ~60s."
                        );

                        Log::warning('WorkerGuard: Marked job_progress as interrupted', [
                            'job_progress_id' => $worker->job_progress_id,
                            'job_id' => $worker->job_id,
                            'death_count' => $deathCount,
                        ]);
                    }

                    // Force-release reserved job for immediate retry
                    $this->forceReleaseReservedJobs($worker->job_id);
                }
            }
        }

        if ($count > 0) {
            Log::info('WorkerGuard: Stale workers cleanup completed', [
                'cleaned_count' => $count,
            ]);
        }

        return $count;
    }

    /**
     * Force-release reserved jobs matching a job_id
     *
     * When a worker dies, the queue driver keeps the job "reserved" for retry_after seconds.
     * This method immediately makes the job available for another worker to pick up.
     *
     * @param string $jobId UUID from job_progress.job_id
     * @return int Number of jobs released
     */
    public function forceReleaseReservedJobs(string $jobId): int
    {
        $released = DB::table('jobs')
            ->whereNotNull('reserved_at')
            ->where('payload', 'like', '%' . $jobId . '%')
            ->update([
                'reserved_at' => null,
                'available_at' => now()->timestamp,
            ]);

        if ($released > 0) {
            Log::info('WorkerGuard: Force-released reserved jobs', [
                'job_id' => $jobId,
                'released_count' => $released,
            ]);
        }

        return $released;
    }

    /**
     * Get worker PID (with fallback for environments where getmypid() is disabled)
     */
    private function getWorkerPid(): int
    {
        $pid = @getmypid();
        return $pid ?: crc32(uniqid('worker-', true)) & 0x7FFFFFFF;
    }
}
