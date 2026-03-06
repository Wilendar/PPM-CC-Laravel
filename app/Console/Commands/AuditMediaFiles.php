<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AuditMediaFiles extends Command
{
    protected $signature = 'media:audit-files
                            {--delete-orphans : Delete files from disk that have no DB record}
                            {--fix-missing : Mark DB records without physical file as inactive with error status}
                            {--dry-run : Show what would be done without doing it}';

    protected $description = 'Audit media files - compare filesystem vs database records';

    /**
     * Allowed image extensions for scanning.
     */
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    public function handle(): int
    {
        $deleteOrphans = $this->option('delete-orphans');
        $fixMissing = $this->option('fix-missing');
        $dryRun = $this->option('dry-run');

        $this->info('Media Files Audit');
        $this->info('=================');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - no data will be modified');
        }

        // Step 1: Scan filesystem for image files
        $this->info('Scanning filesystem...');
        $diskFiles = $this->scanDiskFiles();
        $totalFilesOnDisk = count($diskFiles);
        $this->info("Found {$totalFilesOnDisk} image files on disk.");

        // Step 2: Get all DB file paths (including soft-deleted) as a lookup set
        $this->info('Loading database records...');
        $dbFilePaths = $this->getDbFilePaths();
        $this->info('Loaded ' . count($dbFilePaths) . ' file paths from DB (including trashed).');

        // Step 3: Find orphaned files (on disk but NOT in DB)
        $orphanedFiles = [];
        $orphanedSize = 0;
        $filesWithRecord = 0;

        foreach ($diskFiles as $path => $size) {
            if (isset($dbFilePaths[$path])) {
                $filesWithRecord++;
            } else {
                $orphanedFiles[$path] = $size;
                $orphanedSize += $size;
            }
        }

        // Step 4: Find missing files (in DB but NOT on disk) - only non-trashed active records
        $this->info('Checking DB records for missing files...');
        $missingRecords = $this->findMissingFileRecords();

        // Step 5: Report
        $this->newLine();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total files on disk', number_format($totalFilesOnDisk)],
                ['Files with DB record', number_format($filesWithRecord)],
                ['Orphaned files (no DB)', number_format(count($orphanedFiles)) . ' (' . $this->formatBytes($orphanedSize) . ')'],
                ['DB records without file', number_format(count($missingRecords))],
            ]
        );

        // Step 6: Show top orphaned files by size
        if (count($orphanedFiles) > 0) {
            $this->newLine();
            $this->info('Top 10 largest orphaned files:');

            arsort($orphanedFiles);
            $topOrphans = array_slice($orphanedFiles, 0, 10, true);

            $rows = [];
            foreach ($topOrphans as $path => $size) {
                $rows[] = [$path, $this->formatBytes($size)];
            }
            $this->table(['File Path', 'Size'], $rows);
        }

        // Step 7: Show missing file records
        if (count($missingRecords) > 0) {
            $this->newLine();
            $this->info('DB records without physical file:');

            $rows = [];
            foreach (array_slice($missingRecords, 0, 20) as $record) {
                $rows[] = [$record['id'], $record['file_path'], $record['sync_status']];
            }
            $this->table(['Media ID', 'File Path', 'Current Status'], $rows);

            if (count($missingRecords) > 20) {
                $this->info('... and ' . (count($missingRecords) - 20) . ' more.');
            }
        }

        // Step 8: Actions
        $deletedCount = 0;
        $fixedCount = 0;

        if ($deleteOrphans && count($orphanedFiles) > 0) {
            $deletedCount = $this->deleteOrphanedFiles($orphanedFiles, $dryRun);
        }

        if ($fixMissing && count($missingRecords) > 0) {
            $fixedCount = $this->fixMissingRecords($missingRecords, $dryRun);
        }

        // Step 9: Summary of actions taken
        if ($deleteOrphans || $fixMissing) {
            $this->newLine();
            $this->info('Actions Summary:');
            $this->table(
                ['Action', 'Count'],
                [
                    ['Orphaned files deleted', $deletedCount],
                    ['Missing records fixed', $fixedCount],
                ]
            );
        }

        // Log summary
        $summary = [
            'total_files_on_disk' => $totalFilesOnDisk,
            'files_with_db_record' => $filesWithRecord,
            'orphaned_files' => count($orphanedFiles),
            'orphaned_size_bytes' => $orphanedSize,
            'db_records_without_file' => count($missingRecords),
            'deleted_orphans' => $deletedCount,
            'fixed_missing' => $fixedCount,
            'dry_run' => $dryRun,
        ];

        Log::info('media:audit-files completed', $summary);

        return Command::SUCCESS;
    }

    /**
     * Scan public disk for image files.
     *
     * @return array<string, int> path => size in bytes
     */
    private function scanDiskFiles(): array
    {
        $disk = Storage::disk('public');
        $allFiles = $disk->allFiles();
        $imageFiles = [];

        $this->output->progressStart(count($allFiles));

        foreach ($allFiles as $file) {
            $this->output->progressAdvance();

            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            if (!in_array($extension, self::IMAGE_EXTENSIONS, true)) {
                continue;
            }

            $imageFiles[$file] = $disk->size($file);
        }

        $this->output->progressFinish();

        return $imageFiles;
    }

    /**
     * Get all file paths from DB (including soft-deleted) as a hash set for O(1) lookup.
     *
     * @return array<string, true>
     */
    private function getDbFilePaths(): array
    {
        $paths = [];

        Media::withTrashed()
            ->select('file_path')
            ->whereNotNull('file_path')
            ->where('file_path', '!=', '')
            ->chunk(500, function ($records) use (&$paths) {
                foreach ($records as $record) {
                    $paths[$record->file_path] = true;
                }
            });

        return $paths;
    }

    /**
     * Find active (non-trashed) DB records whose physical file is missing from disk.
     *
     * @return array<int, array{id: int, file_path: string, sync_status: string}>
     */
    private function findMissingFileRecords(): array
    {
        $missing = [];
        $disk = Storage::disk('public');

        Media::query()
            ->select(['id', 'file_path', 'sync_status'])
            ->whereNotNull('file_path')
            ->where('file_path', '!=', '')
            ->chunk(500, function ($records) use (&$missing, $disk) {
                foreach ($records as $record) {
                    if (!$disk->exists($record->file_path)) {
                        $missing[] = [
                            'id' => $record->id,
                            'file_path' => $record->file_path,
                            'sync_status' => $record->sync_status ?? 'unknown',
                        ];
                    }
                }
            });

        return $missing;
    }

    /**
     * Delete orphaned files from disk.
     *
     * @param array<string, int> $orphanedFiles path => size
     * @param bool $dryRun
     * @return int Number of files deleted
     */
    private function deleteOrphanedFiles(array $orphanedFiles, bool $dryRun): int
    {
        $disk = Storage::disk('public');
        $deleted = 0;
        $totalSize = 0;

        $this->newLine();
        $this->info('Deleting orphaned files...');
        $this->output->progressStart(count($orphanedFiles));

        foreach ($orphanedFiles as $path => $size) {
            $this->output->progressAdvance();

            if ($dryRun) {
                $deleted++;
                $totalSize += $size;
                continue;
            }

            if ($disk->delete($path)) {
                $deleted++;
                $totalSize += $size;
                Log::info('media:audit-files deleted orphan', ['path' => $path, 'size' => $size]);
            } else {
                $this->warn("Failed to delete: {$path}");
                Log::warning('media:audit-files failed to delete orphan', ['path' => $path]);
            }
        }

        $this->output->progressFinish();

        $prefix = $dryRun ? 'DRY RUN: Would delete' : 'Deleted';
        $this->info("{$prefix} {$deleted} orphaned files ({$this->formatBytes($totalSize)})");

        return $deleted;
    }

    /**
     * Fix DB records that have no physical file on disk.
     * Sets is_active=false and sync_status='error' (does NOT delete the record).
     *
     * @param array<int, array{id: int, file_path: string, sync_status: string}> $missingRecords
     * @param bool $dryRun
     * @return int Number of records fixed
     */
    private function fixMissingRecords(array $missingRecords, bool $dryRun): int
    {
        $fixed = 0;

        $this->newLine();
        $this->info('Fixing DB records with missing files...');
        $this->output->progressStart(count($missingRecords));

        // Process in batches of 100 IDs for efficient UPDATE
        $batches = array_chunk($missingRecords, 100);

        foreach ($batches as $batch) {
            $ids = array_column($batch, 'id');

            if ($dryRun) {
                $fixed += count($ids);
                foreach ($ids as $id) {
                    $this->output->progressAdvance();
                }
                continue;
            }

            $updated = Media::whereIn('id', $ids)
                ->update([
                    'is_active' => false,
                    'sync_status' => 'error',
                ]);

            $fixed += $updated;

            Log::info('media:audit-files fixed missing records batch', [
                'ids' => $ids,
                'updated' => $updated,
            ]);

            foreach ($ids as $id) {
                $this->output->progressAdvance();
            }
        }

        $this->output->progressFinish();

        $prefix = $dryRun ? 'DRY RUN: Would fix' : 'Fixed';
        $this->info("{$prefix} {$fixed} DB records (set is_active=false, sync_status=error)");

        return $fixed;
    }

    /**
     * Format bytes into human-readable string.
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}
