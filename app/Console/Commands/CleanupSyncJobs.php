<?php

namespace App\Console\Commands;

use App\Services\SyncJobCleanupService;
use Illuminate\Console\Command;

/**
 * Cleanup old sync jobs command
 *
 * Usage:
 *   php artisan sync:cleanup           # Execute cleanup
 *   php artisan sync:cleanup --dry-run # Preview only
 *
 * BUG #9 FIX #4 + FIX #6
 */
class CleanupSyncJobs extends Command
{
    protected $signature = 'sync:cleanup
                            {--dry-run : Preview cleanup without deleting}';

    protected $description = 'Clean up old sync jobs according to retention policy';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('Sync Jobs Cleanup');
        $this->info('================');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No records will be deleted');
            $this->newLine();
        }

        $this->info('Retention Policy:');
        $this->line('  Completed: ' . config('sync.retention.completed_days') . ' days');
        $this->line('  Failed: ' . config('sync.retention.failed_days') . ' days');
        $this->line('  Canceled: ' . config('sync.retention.canceled_days') . ' days');
        $this->newLine();

        $cleanupService = app(SyncJobCleanupService::class);

        $this->info('Analyzing sync jobs...');
        $stats = $cleanupService->cleanup($dryRun);

        $this->newLine();
        $this->info('Results:');
        $this->line('  Completed: ' . $stats['completed']);
        $this->line('  Failed: ' . $stats['failed']);
        $this->line('  Canceled: ' . $stats['canceled']);
        $this->line('  Total: ' . $stats['total']);

        if ($dryRun) {
            $this->newLine();
            $this->info('Run without --dry-run to execute cleanup');
        } else {
            $this->newLine();
            $this->info('✅ Cleanup completed successfully');
        }

        return Command::SUCCESS;
    }
}
