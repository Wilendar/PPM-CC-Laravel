<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SyncJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * Cleanup Old SyncJobs Command
 *
 * Removes old sync_jobs records based on retention policy
 *
 * Retention Policy:
 * - Completed jobs: 30 days
 * - Failed jobs: 90 days (longer for debugging)
 * - Cancelled jobs: 7 days
 * - Running/Pending jobs: NEVER (keep active jobs)
 *
 * Optional archiving before deletion
 *
 * Usage:
 *   php artisan sync-jobs:cleanup
 *   php artisan sync-jobs:cleanup --dry-run
 *   php artisan sync-jobs:cleanup --archive
 *
 * @package App\Console\Commands
 */
class CleanupSyncJobs extends Command
{
    /**
     * Command signature
     */
    protected $signature = 'sync-jobs:cleanup
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--archive : Archive sync_jobs to JSON before deletion}
                            {--force : Skip confirmation prompt}';

    /**
     * Command description
     */
    protected $description = 'Cleanup old sync_jobs based on retention policy (completed:30d, failed:90d, cancelled:7d)';

    /**
     * Retention policy (days)
     */
    private const RETENTION_COMPLETED = 30;
    private const RETENTION_FAILED = 90;
    private const RETENTION_CANCELLED = 7;

    /**
     * Execute the command
     */
    public function handle(): int
    {
        $this->info('=== SYNC JOBS CLEANUP ===');
        $this->newLine();

        $isDryRun = $this->option('dry-run');
        $shouldArchive = $this->option('archive');
        $force = $this->option('force');

        // Calculate cutoff dates
        $completedCutoff = Carbon::now()->subDays(self::RETENTION_COMPLETED);
        $failedCutoff = Carbon::now()->subDays(self::RETENTION_FAILED);
        $cancelledCutoff = Carbon::now()->subDays(self::RETENTION_CANCELLED);

        $this->line("Retention Policy:");
        $this->line("  Completed jobs: " . self::RETENTION_COMPLETED . " days (before {$completedCutoff->toDateString()})");
        $this->line("  Failed jobs: " . self::RETENTION_FAILED . " days (before {$failedCutoff->toDateString()})");
        $this->line("  Cancelled jobs: " . self::RETENTION_CANCELLED . " days (before {$cancelledCutoff->toDateString()})");
        $this->newLine();

        // Find jobs to delete
        $completedJobs = SyncJob::where('status', SyncJob::STATUS_COMPLETED)
            ->where('completed_at', '<', $completedCutoff)
            ->get();

        $failedJobs = SyncJob::whereIn('status', [SyncJob::STATUS_FAILED, SyncJob::STATUS_TIMEOUT])
            ->where('completed_at', '<', $failedCutoff)
            ->get();

        $cancelledJobs = SyncJob::where('status', SyncJob::STATUS_CANCELLED)
            ->where('completed_at', '<', $cancelledCutoff)
            ->get();

        $totalToDelete = $completedJobs->count() + $failedJobs->count() + $cancelledJobs->count();

        // Show summary
        $this->table(
            ['Status', 'Count', 'Cutoff Date'],
            [
                ['Completed', $completedJobs->count(), $completedCutoff->toDateString()],
                ['Failed', $failedJobs->count(), $failedCutoff->toDateString()],
                ['Cancelled', $cancelledJobs->count(), $cancelledCutoff->toDateString()],
                ['TOTAL', $totalToDelete, '-'],
            ]
        );

        if ($totalToDelete === 0) {
            $this->info('✓ No jobs to delete (all within retention period)');
            return Command::SUCCESS;
        }

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No deletions will be performed');
            $this->info("Would delete {$totalToDelete} sync_jobs");
            return Command::SUCCESS;
        }

        // Confirmation prompt
        if (!$force && !$this->confirm("Delete {$totalToDelete} sync_jobs?", false)) {
            $this->warn('Operation cancelled by user');
            return Command::FAILURE;
        }

        // Archive if requested
        if ($shouldArchive) {
            $this->archiveJobs($completedJobs, $failedJobs, $cancelledJobs);
        }

        // Delete jobs
        $this->info('Deleting old sync_jobs...');
        $deletedCount = 0;

        $bar = $this->output->createProgressBar($totalToDelete);
        $bar->start();

        foreach ($completedJobs as $job) {
            $job->delete();
            $deletedCount++;
            $bar->advance();
        }

        foreach ($failedJobs as $job) {
            $job->delete();
            $deletedCount++;
            $bar->advance();
        }

        foreach ($cancelledJobs as $job) {
            $job->delete();
            $deletedCount++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✓ Deleted {$deletedCount} sync_jobs");

        // Log cleanup
        Log::info('SyncJobs cleanup completed', [
            'deleted_count' => $deletedCount,
            'completed' => $completedJobs->count(),
            'failed' => $failedJobs->count(),
            'cancelled' => $cancelledJobs->count(),
            'archived' => $shouldArchive,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Archive sync_jobs to JSON before deletion
     */
    private function archiveJobs($completedJobs, $failedJobs, $cancelledJobs): void
    {
        $this->info('Archiving sync_jobs to JSON...');

        $archiveData = [
            'archived_at' => Carbon::now()->toISOString(),
            'retention_policy' => [
                'completed_days' => self::RETENTION_COMPLETED,
                'failed_days' => self::RETENTION_FAILED,
                'cancelled_days' => self::RETENTION_CANCELLED,
            ],
            'jobs' => [
                'completed' => $completedJobs->toArray(),
                'failed' => $failedJobs->toArray(),
                'cancelled' => $cancelledJobs->toArray(),
            ],
            'counts' => [
                'completed' => $completedJobs->count(),
                'failed' => $failedJobs->count(),
                'cancelled' => $cancelledJobs->count(),
                'total' => $completedJobs->count() + $failedJobs->count() + $cancelledJobs->count(),
            ],
        ];

        $filename = 'sync_jobs_archive_' . Carbon::now()->format('Y-m-d_His') . '.json';
        $path = 'archives/' . $filename;

        Storage::put($path, json_encode($archiveData, JSON_PRETTY_PRINT));

        $this->info("✓ Archived to: storage/app/{$path}");
    }
}
