<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

/**
 * ArchiveOldLogs - Move old daily logs to archive folder
 *
 * FUNKCJONALNOŚĆ:
 * - Przenosi wszystkie pliki laravel-YYYY-MM-DD.log starsze niż 1 dzień do archive/
 * - Zostawia tylko aktualny log w głównym folderze
 * - Automatycznie usuwa archiwa starsze niż 14 dni
 * - Uruchamiany codziennie przez scheduler
 *
 * USAGE:
 * ```bash
 * php artisan logs:archive
 * ```
 *
 * SCHEDULER (routes/console.php):
 * ```php
 * Schedule::command('logs:archive')->daily();
 * ```
 *
 * @package App\Console\Commands
 * @version 1.0
 */
class ArchiveOldLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:archive
                            {--keep-days=14 : Number of days to keep archived logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive old daily log files to storage/logs/archive folder';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $logsPath = storage_path('logs');
        $archivePath = storage_path('logs/archive');
        $keepDays = (int) $this->option('keep-days');

        // Ensure archive directory exists
        if (!File::isDirectory($archivePath)) {
            File::makeDirectory($archivePath, 0775, true);
            $this->info("Created archive directory: {$archivePath}");
        }

        $today = Carbon::today()->format('Y-m-d');
        $archivedCount = 0;
        $deletedCount = 0;

        // Get all daily log files (laravel-YYYY-MM-DD.log)
        $logFiles = File::glob($logsPath . '/laravel-*.log');

        foreach ($logFiles as $logFile) {
            $filename = basename($logFile);

            // Extract date from filename (laravel-2025-10-10.log)
            if (preg_match('/laravel-(\d{4}-\d{2}-\d{2})\.log$/', $filename, $matches)) {
                $fileDate = $matches[1];

                // Move to archive if NOT today's log
                if ($fileDate !== $today) {
                    $archiveFile = $archivePath . '/' . $filename;

                    if (File::move($logFile, $archiveFile)) {
                        $archivedCount++;
                        $this->line("→ Archived: {$filename}");
                    } else {
                        $this->error("✗ Failed to archive: {$filename}");
                    }
                }
            }
        }

        // Clean old archives (older than $keepDays)
        $archiveFiles = File::glob($archivePath . '/laravel-*.log');
        $cutoffDate = Carbon::now()->subDays($keepDays);

        foreach ($archiveFiles as $archiveFile) {
            $filename = basename($archiveFile);

            if (preg_match('/laravel-(\d{4}-\d{2}-\d{2})\.log$/', $filename, $matches)) {
                $fileDate = Carbon::createFromFormat('Y-m-d', $matches[1]);

                if ($fileDate->lt($cutoffDate)) {
                    if (File::delete($archiveFile)) {
                        $deletedCount++;
                        $this->line("→ Deleted old archive: {$filename}");
                    } else {
                        $this->error("✗ Failed to delete: {$filename}");
                    }
                }
            }
        }

        // Summary
        $this->newLine();
        $this->info("📦 Log Archival Summary:");
        $this->line("  • Archived: {$archivedCount} log file(s)");
        $this->line("  • Deleted: {$deletedCount} old archive(s) (older than {$keepDays} days)");
        $this->line("  • Archive location: storage/logs/archive/");

        return self::SUCCESS;
    }
}
