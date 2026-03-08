<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CleanupOldFeeds extends Command
{
    protected $signature = 'feeds:cleanup
                            {--days=7 : Days to keep feed files}
                            {--dry-run : Show what would be deleted without deleting}';

    protected $description = 'Remove old generated feed files';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $feedsPath = storage_path('app/exports/feeds');

        if (!File::isDirectory($feedsPath)) {
            $this->info('No feeds directory found. Nothing to clean.');
            return self::SUCCESS;
        }

        $cutoffDate = now()->subDays($days);
        $files = File::files($feedsPath);
        $deletedCount = 0;
        $freedBytes = 0;

        if ($dryRun) {
            $this->warn('DRY RUN MODE - no files will be deleted');
        }

        foreach ($files as $file) {
            if ($file->getMTime() < $cutoffDate->timestamp) {
                $freedBytes += $file->getSize();
                $deletedCount++;

                if ($dryRun) {
                    $this->line("Would delete: {$file->getFilename()} ({$this->formatSize($file->getSize())})");
                } else {
                    File::delete($file->getPathname());
                }
            }
        }

        $freedMB = round($freedBytes / 1024 / 1024, 2);

        if ($deletedCount > 0) {
            $action = $dryRun ? 'Would delete' : 'Deleted';
            $this->info("{$action} {$deletedCount} old feed file(s), freed {$freedMB} MB.");

            if (!$dryRun) {
                Log::info('Old feed files cleaned up', [
                    'deleted' => $deletedCount,
                    'freed_bytes' => $freedBytes,
                    'retention_days' => $days,
                ]);
            }
        } else {
            $this->info('No old feed files to clean up.');
        }

        return self::SUCCESS;
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        return round($bytes / 1024, 2) . ' KB';
    }
}
