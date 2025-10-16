<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * ArchiveLogsCommand - Daily log archiving task
 *
 * Purpose: Rotate laravel.log daily, move to archive/ with date
 *
 * Workflow:
 * 1. Check if laravel.log exists and is not empty
 * 2. Rename to laravel-YYYY-MM-DD.log
 * 3. Move to storage/logs/archive/
 * 4. Create new empty laravel.log
 * 5. Delete archives older than 30 days
 *
 * Scheduled: Daily at 00:01 (see app/Console/Kernel.php)
 *
 * Usage:
 * php artisan logs:archive
 *
 * @package App\Console\Commands
 * @version 1.0
 * @since 2025-10-13 Log Management System
 */
class ArchiveLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:archive
                            {--force : Force archiving even if log is small}
                            {--keep-days=30 : Number of days to keep archived logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive laravel.log to storage/logs/archive/ with date suffix';

    /**
     * Minimum log file size to trigger archiving (1MB)
     *
     * @var int
     */
    protected const MIN_SIZE_BYTES = 1048576; // 1MB

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $logPath = storage_path('logs/laravel.log');
        $archivePath = storage_path('logs/archive');
        $keepDays = (int) $this->option('keep-days');
        $force = $this->option('force');

        // Create archive directory if not exists
        if (!File::isDirectory($archivePath)) {
            File::makeDirectory($archivePath, 0755, true);
            $this->info("✅ Created archive directory: {$archivePath}");
        }

        // Check if laravel.log exists
        if (!File::exists($logPath)) {
            $this->warn('⚠️  laravel.log does not exist. Nothing to archive.');
            return self::SUCCESS;
        }

        // Check file size
        $fileSize = File::size($logPath);
        $fileSizeMB = round($fileSize / 1048576, 2);

        if (!$force && $fileSize < self::MIN_SIZE_BYTES) {
            $this->info("ℹ️  Log file is too small ({$fileSizeMB} MB). Skipping archive. Use --force to override.");
            return self::SUCCESS;
        }

        // Generate archive filename with yesterday's date
        // (since we're archiving at 00:01, the log contains yesterday's data)
        $archiveDate = now()->subDay()->format('Y-m-d');
        $archiveFilename = "laravel-{$archiveDate}.log";
        $archiveFullPath = "{$archivePath}/{$archiveFilename}";

        // Check if archive already exists
        if (File::exists($archiveFullPath)) {
            $this->warn("⚠️  Archive already exists: {$archiveFilename}. Appending timestamp.");
            $archiveFilename = "laravel-{$archiveDate}-" . now()->format('His') . ".log";
            $archiveFullPath = "{$archivePath}/{$archiveFilename}";
        }

        // Move log to archive
        try {
            File::move($logPath, $archiveFullPath);
            $this->info("✅ Archived log: {$archiveFilename} ({$fileSizeMB} MB)");

            // Create new empty log file
            File::put($logPath, '');
            chmod($logPath, 0664);

            $this->info('✅ Created new empty laravel.log');

        } catch (\Exception $e) {
            $this->error("❌ Failed to archive log: {$e->getMessage()}");
            return self::FAILURE;
        }

        // Clean old archives (older than $keepDays)
        $this->cleanOldArchives($archivePath, $keepDays);

        $this->info("🎉 Log archiving completed successfully!");

        return self::SUCCESS;
    }

    /**
     * Delete archived logs older than specified days
     *
     * @param string $archivePath Archive directory path
     * @param int $keepDays Number of days to keep
     * @return void
     */
    protected function cleanOldArchives(string $archivePath, int $keepDays): void
    {
        $this->info("🧹 Cleaning archives older than {$keepDays} days...");

        $files = File::files($archivePath);
        $cutoffDate = now()->subDays($keepDays);
        $deletedCount = 0;

        foreach ($files as $file) {
            $fileTime = File::lastModified($file->getPathname());

            if ($fileTime < $cutoffDate->timestamp) {
                try {
                    File::delete($file->getPathname());
                    $deletedCount++;
                    $this->line("  🗑️  Deleted: {$file->getFilename()}");
                } catch (\Exception $e) {
                    $this->warn("  ⚠️  Failed to delete {$file->getFilename()}: {$e->getMessage()}");
                }
            }
        }

        if ($deletedCount > 0) {
            $this->info("✅ Deleted {$deletedCount} old archive(s)");
        } else {
            $this->info('ℹ️  No old archives to delete');
        }
    }
}
