<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ArchiveService
{
    protected string $basePath;
    protected string $format;
    protected int $archiveRetentionDays;

    public function __construct()
    {
        $this->basePath = config('database-cleanup.archive.base_path', 'archives');
        $this->format = config('database-cleanup.archive.format', 'json.gz');
        $this->archiveRetentionDays = config('database-cleanup.archive.archive_retention_days', 180);
    }

    /**
     * Archive records from table before deletion
     */
    public function archive(string $table, Carbon $cutoff, array $options = []): array
    {
        $dateColumn = $options['date_column'] ?? 'created_at';
        $chunkSize = $options['chunk_size'] ?? 1000;

        $query = DB::table($table)->where($dateColumn, '<', $cutoff);
        $totalCount = $query->count();

        if ($totalCount === 0) {
            return [
                'table' => $table,
                'archived' => 0,
                'file' => null,
                'size' => 0,
            ];
        }

        $directory = $this->getTableDirectory($table);
        $timestamp = Carbon::now()->format('Y-m-d_His');
        $filename = "archive_{$table}_{$timestamp}.json.gz";
        $filepath = "{$directory}/{$filename}";

        // Collect data in chunks and compress
        $allRecords = [];
        DB::table($table)
            ->where($dateColumn, '<', $cutoff)
            ->orderBy($dateColumn)
            ->chunk($chunkSize, function ($records) use (&$allRecords) {
                foreach ($records as $record) {
                    $allRecords[] = (array) $record;
                }
            });

        $archiveData = [
            'table' => $table,
            'export_date' => Carbon::now()->toIso8601String(),
            'cutoff_date' => $cutoff->toIso8601String(),
            'record_count' => count($allRecords),
            'records' => $allRecords,
        ];

        $json = json_encode($archiveData, JSON_UNESCAPED_UNICODE);
        $compressed = gzencode($json, 9);

        Storage::disk('local')->put($filepath, $compressed);

        $fileSize = strlen($compressed);

        Log::info('ArchiveService: Data archived', [
            'table' => $table,
            'records' => count($allRecords),
            'file' => $filename,
            'size_bytes' => $fileSize,
        ]);

        return [
            'table' => $table,
            'archived' => count($allRecords),
            'file' => $filename,
            'size' => $fileSize,
        ];
    }

    /**
     * List archive files, optionally filtered by table
     */
    public function listArchives(?string $table = null): Collection
    {
        $archives = collect();

        if ($table) {
            $directories = [$this->getTableDirectory($table)];
        } else {
            $basePath = $this->basePath;
            $allDirs = Storage::disk('local')->directories($basePath);
            $directories = $allDirs;
        }

        foreach ($directories as $dir) {
            $files = Storage::disk('local')->files($dir);

            foreach ($files as $file) {
                if (!str_ends_with($file, '.json.gz')) {
                    continue;
                }

                $basename = basename($file);
                // Parse: archive_{table}_{Y-m-d_His}.json.gz
                $parts = [];
                if (preg_match('/^archive_(.+?)_(\d{4}-\d{2}-\d{2}_\d{6})\.json\.gz$/', $basename, $parts)) {
                    $archives->push([
                        'filename' => $basename,
                        'path' => $file,
                        'table' => $parts[1],
                        'date' => Carbon::createFromFormat('Y-m-d_His', $parts[2]),
                        'size' => Storage::disk('local')->size($file),
                    ]);
                }
            }
        }

        return $archives->sortByDesc('date')->values();
    }

    /**
     * Get archive statistics
     */
    public function getArchiveStats(): array
    {
        $archives = $this->listArchives();

        return [
            'total_files' => $archives->count(),
            'total_size' => $archives->sum('size'),
            'tables' => $archives->groupBy('table')->map(function ($group) {
                return [
                    'files' => $group->count(),
                    'size' => $group->sum('size'),
                    'oldest' => $group->min('date')?->toDateString(),
                    'newest' => $group->max('date')?->toDateString(),
                ];
            })->toArray(),
        ];
    }

    /**
     * Cleanup old archive files
     */
    public function cleanupOldArchives(?int $days = null): int
    {
        $days = $days ?? $this->archiveRetentionDays;
        $cutoff = Carbon::now()->subDays($days);
        $deleted = 0;

        $archives = $this->listArchives();

        foreach ($archives as $archive) {
            if ($archive['date']->lt($cutoff)) {
                Storage::disk('local')->delete($archive['path']);
                $deleted++;

                Log::info('ArchiveService: Old archive deleted', [
                    'file' => $archive['filename'],
                    'table' => $archive['table'],
                    'age_days' => $archive['date']->diffInDays(Carbon::now()),
                ]);
            }
        }

        return $deleted;
    }

    /**
     * Get download path for archive file
     */
    public function downloadPath(string $filename): ?string
    {
        $archives = $this->listArchives();
        $archive = $archives->firstWhere('filename', $filename);

        if (!$archive) {
            return null;
        }

        return Storage::disk('local')->path($archive['path']);
    }

    /**
     * Check if archiving is enabled
     */
    public function isEnabled(): bool
    {
        return config('database-cleanup.archive.enabled', true);
    }

    /**
     * Get directory path for table archives
     */
    protected function getTableDirectory(string $table): string
    {
        return "{$this->basePath}/{$table}";
    }

    /**
     * Format bytes to human readable string
     */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
