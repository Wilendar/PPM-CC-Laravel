<?php

namespace App\Services;

use App\Models\SyncJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Sync Job Cleanup Service
 *
 * Handles cleanup of old sync jobs according to retention policy
 * defined in config/sync.php
 *
 * BUG #9 FIX #4 + FIX #6
 */
class SyncJobCleanupService
{
    /**
     * Execute cleanup based on retention policy
     *
     * @param bool $dryRun If true, only count jobs without deleting
     * @return array Statistics about cleanup
     */
    public function cleanup(bool $dryRun = false): array
    {
        $stats = [
            'completed' => 0,
            'failed' => 0,
            'canceled' => 0,
            'total' => 0,
            'dry_run' => $dryRun,
        ];

        Log::debug('SyncJobCleanupService::cleanup STARTED', [
            'dry_run' => $dryRun,
            'config' => config('sync.retention'),
        ]);

        DB::transaction(function () use (&$stats, $dryRun) {
            // Cleanup completed jobs
            $stats['completed'] = $this->cleanupByStatus(
                'completed',
                config('sync.retention.completed_days', 30),
                $dryRun
            );

            // Cleanup failed jobs
            $stats['failed'] = $this->cleanupByStatus(
                'failed',
                config('sync.retention.failed_days', 90),
                $dryRun
            );

            // Cleanup canceled jobs
            $stats['canceled'] = $this->cleanupByStatus(
                'canceled',
                config('sync.retention.canceled_days', 14),
                $dryRun
            );

            $stats['total'] = $stats['completed'] + $stats['failed'] + $stats['canceled'];
        });

        Log::info('SyncJobCleanupService::cleanup COMPLETED', $stats);

        return $stats;
    }

    /**
     * Cleanup sync jobs by status older than X days
     *
     * @param string $status Job status
     * @param int $days Days to keep
     * @param bool $dryRun If true, only count without deleting
     * @return int Number of jobs deleted/counted
     */
    protected function cleanupByStatus(string $status, int $days, bool $dryRun): int
    {
        $cutoffDate = Carbon::now()->subDays($days);

        $query = SyncJob::where('status', $status)
            ->where('created_at', '<', $cutoffDate);

        $count = $query->count();

        if (!$dryRun && $count > 0) {
            $batchSize = config('sync.cleanup.batch_size', 500);
            $deleted = 0;

            while (true) {
                // Create fresh query for each batch
                $batch = SyncJob::where('status', $status)
                    ->where('created_at', '<', $cutoffDate)
                    ->limit($batchSize)
                    ->delete();

                if ($batch === 0) {
                    break;
                }
                $deleted += $batch;
            }

            Log::debug('SyncJobCleanupService::cleanupByStatus DELETED', [
                'status' => $status,
                'days' => $days,
                'cutoff_date' => $cutoffDate->toDateTimeString(),
                'deleted' => $deleted,
            ]);

            return $deleted;
        }

        Log::debug('SyncJobCleanupService::cleanupByStatus DRY RUN', [
            'status' => $status,
            'days' => $days,
            'cutoff_date' => $cutoffDate->toDateTimeString(),
            'would_delete' => $count,
        ]);

        return $count;
    }

    /**
     * Custom cleanup with user-specified parameters (2025-11-12)
     *
     * @param string $type Type of jobs to clean: 'all', 'completed', 'failed', 'completed_with_errors'
     * @param int $days Age threshold in days
     * @param bool $clearAllAges If true, delete all jobs regardless of age
     * @param bool $dryRun If true, only count jobs without deleting
     * @return array Statistics about cleanup
     */
    public function cleanupCustom(string $type, int $days, bool $clearAllAges = false, bool $dryRun = false): array
    {
        $stats = [
            'type' => $type,
            'days' => $days,
            'clear_all_ages' => $clearAllAges,
            'deleted' => 0,
            'dry_run' => $dryRun,
        ];

        Log::debug('SyncJobCleanupService::cleanupCustom STARTED', [
            'type' => $type,
            'days' => $days,
            'clear_all_ages' => $clearAllAges,
            'dry_run' => $dryRun,
        ]);

        DB::transaction(function () use (&$stats, $type, $days, $clearAllAges, $dryRun) {
            if ($type === 'all') {
                // Clean all statuses except pending/running
                $statuses = ['completed', 'completed_with_errors', 'failed', 'cancelled', 'timeout'];
                foreach ($statuses as $status) {
                    $stats['deleted'] += $this->cleanupByStatusCustom($status, $days, $clearAllAges, $dryRun);
                }
            } else {
                // Clean specific status
                $stats['deleted'] = $this->cleanupByStatusCustom($type, $days, $clearAllAges, $dryRun);
            }
        });

        Log::info('SyncJobCleanupService::cleanupCustom COMPLETED', $stats);

        return $stats;
    }

    /**
     * Custom cleanup by status with age threshold (2025-11-12)
     *
     * @param string $status Job status
     * @param int $days Days to keep
     * @param bool $clearAllAges If true, delete all jobs regardless of age
     * @param bool $dryRun If true, only count without deleting
     * @return int Number of jobs deleted/counted
     */
    protected function cleanupByStatusCustom(string $status, int $days, bool $clearAllAges, bool $dryRun): int
    {
        $query = SyncJob::where('status', $status);

        // Apply age filter only if NOT clearing all ages
        if (!$clearAllAges) {
            $cutoffDate = Carbon::now()->subDays($days);
            $query->where('created_at', '<', $cutoffDate);
        }

        $count = $query->count();

        if (!$dryRun && $count > 0) {
            $batchSize = config('sync.cleanup.batch_size', 500);
            $deleted = 0;

            while (true) {
                // Create fresh query for each batch
                $batchQuery = SyncJob::where('status', $status);

                if (!$clearAllAges) {
                    $cutoffDate = Carbon::now()->subDays($days);
                    $batchQuery->where('created_at', '<', $cutoffDate);
                }

                $batch = $batchQuery->limit($batchSize)->delete();

                if ($batch === 0) {
                    break;
                }
                $deleted += $batch;
            }

            Log::debug('SyncJobCleanupService::cleanupByStatusCustom DELETED', [
                'status' => $status,
                'days' => $days,
                'clear_all_ages' => $clearAllAges,
                'deleted' => $deleted,
            ]);

            return $deleted;
        }

        Log::debug('SyncJobCleanupService::cleanupByStatusCustom DRY RUN', [
            'status' => $status,
            'days' => $days,
            'clear_all_ages' => $clearAllAges,
            'would_delete' => $count,
        ]);

        return $count;
    }

    /**
     * Archive and cleanup sync jobs (2025-11-12)
     *
     * Exports sync jobs to JSON file before deletion
     *
     * @param string $type Type of jobs to archive
     * @param int $days Age threshold in days
     * @param bool $clearAllAges If true, archive all jobs regardless of age
     * @return array Statistics about archiving
     */
    public function archiveAndCleanup(string $type, int $days, bool $clearAllAges = false): array
    {
        $stats = [
            'type' => $type,
            'days' => $days,
            'clear_all_ages' => $clearAllAges,
            'archived' => 0,
            'deleted' => 0,
            'archive_file' => null,
        ];

        Log::debug('SyncJobCleanupService::archiveAndCleanup STARTED', [
            'type' => $type,
            'days' => $days,
            'clear_all_ages' => $clearAllAges,
        ]);

        DB::transaction(function () use (&$stats, $type, $days, $clearAllAges) {
            // 1. Fetch jobs to archive
            $jobsToArchive = $this->getJobsToArchive($type, $days, $clearAllAges);
            $stats['archived'] = $jobsToArchive->count();

            if ($stats['archived'] > 0) {
                // 2. Export to JSON
                $archivePath = storage_path('app/sync_jobs_archive');
                if (!file_exists($archivePath)) {
                    mkdir($archivePath, 0755, true);
                }

                $timestamp = now()->format('Y-m-d_His');
                $filename = "sync_jobs_archive_{$type}_{$timestamp}.json";
                $filepath = "{$archivePath}/{$filename}";

                $archiveData = [
                    'export_date' => now()->toIso8601String(),
                    'export_user' => auth()->user()->email ?? 'system',
                    'filter_type' => $type,
                    'filter_days' => $days,
                    'filter_clear_all_ages' => $clearAllAges,
                    'total_jobs' => $stats['archived'],
                    'jobs' => $jobsToArchive->toArray(),
                ];

                file_put_contents($filepath, json_encode($archiveData, JSON_PRETTY_PRINT));
                $stats['archive_file'] = $filename;

                Log::info('Sync jobs archived to file', [
                    'file' => $filename,
                    'count' => $stats['archived'],
                ]);

                // 3. Delete archived jobs
                $deleteStats = $this->cleanupCustom($type, $days, $clearAllAges, dryRun: false);
                $stats['deleted'] = $deleteStats['deleted'];
            }
        });

        Log::info('SyncJobCleanupService::archiveAndCleanup COMPLETED', $stats);

        return $stats;
    }

    /**
     * Get jobs to archive based on filters
     *
     * @param string $type Job type filter
     * @param int $days Age threshold
     * @param bool $clearAllAges Ignore age filter
     * @return \Illuminate\Support\Collection
     */
    protected function getJobsToArchive(string $type, int $days, bool $clearAllAges)
    {
        if ($type === 'all') {
            $statuses = ['completed', 'completed_with_errors', 'failed', 'canceled', 'timeout'];
            $query = SyncJob::whereIn('status', $statuses);
        } else {
            $query = SyncJob::where('status', $type);
        }

        if (!$clearAllAges) {
            $cutoffDate = Carbon::now()->subDays($days);
            $query->where('created_at', '<', $cutoffDate);
        }

        return $query->get();
    }

    /**
     * Get cleanup preview (counts without deleting)
     *
     * @return array Preview statistics
     */
    public function preview(): array
    {
        return $this->cleanup(dryRun: true);
    }
}
