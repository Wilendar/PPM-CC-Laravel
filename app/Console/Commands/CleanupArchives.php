<?php

namespace App\Console\Commands;

use App\Services\ArchiveService;
use Illuminate\Console\Command;

class CleanupArchives extends Command
{
    protected $signature = 'archives:cleanup
                            {--days= : Override retention days for archives}
                            {--dry-run : Show what would be deleted}';

    protected $description = 'Clean up old archive files based on retention policy';

    public function handle(ArchiveService $archiveService): int
    {
        $days = $this->option('days')
            ? (int) $this->option('days')
            : app(\App\Services\RetentionConfigService::class)->getArchiveRetentionDays();

        $dryRun = $this->option('dry-run');

        $this->info('Archive Cleanup');
        $this->info('===============');
        $this->info("Archive retention: {$days} days");

        if ($dryRun) {
            $this->warn('DRY RUN MODE');
        }

        // List current archives
        $archives = $archiveService->listArchives();
        $cutoff = now()->subDays($days);

        $toDelete = $archives->filter(fn($a) => $a['date']->lt($cutoff));

        $this->info("Total archives: {$archives->count()}");
        $this->info("Archives to delete (older than {$days} days): {$toDelete->count()}");

        if ($toDelete->isEmpty()) {
            $this->info('Nothing to clean up.');
            return Command::SUCCESS;
        }

        if ($dryRun) {
            $this->table(
                ['File', 'Table', 'Date', 'Size'],
                $toDelete->map(fn($a) => [
                    $a['filename'],
                    $a['table'],
                    $a['date']->format('Y-m-d'),
                    ArchiveService::formatBytes($a['size']),
                ])->toArray()
            );
            $this->warn("DRY RUN: Would delete {$toDelete->count()} archives");
            return Command::SUCCESS;
        }

        $deleted = $archiveService->cleanupOldArchives($days);

        $this->info("Deleted {$deleted} old archive files.");

        return Command::SUCCESS;
    }
}
