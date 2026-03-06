<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\RetentionConfigService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupMedia extends Command
{
    protected $signature = 'media:cleanup
                            {--purge-days=0 : Days before soft-deleted media is permanently purged (0 = use config)}
                            {--orphan-days=0 : Days before orphaned media is cleaned up (0 = use config)}
                            {--dry-run : Show what would be done without doing it}';

    protected $description = 'Clean up soft-deleted and orphaned media records with physical file cleanup';

    public function handle(RetentionConfigService $retentionConfig): int
    {
        $purgeDays = (int) $this->option('purge-days');
        $orphanDays = (int) $this->option('orphan-days');
        $dryRun = $this->option('dry-run');

        // Use RetentionConfigService defaults when CLI options are 0
        if ($purgeDays === 0) {
            $purgeDays = $retentionConfig->getMediaPurgeDays();
        }
        if ($orphanDays === 0) {
            $orphanDays = $retentionConfig->getMediaOrphanDays();
        }

        $this->info('Media Cleanup');
        $this->info('=============');
        $this->info("Purge soft-deleted older than: {$purgeDays} days");
        $this->info("Orphan cleanup older than: {$orphanDays} days");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - no data will be modified');
        }

        $stats = [
            'purged_count' => 0,
            'purged_mb' => 0,
            'orphan_count' => 0,
            'orphan_mb' => 0,
            'orphan_archived' => 0,
        ];

        // Stage 1: Purge soft-deleted media
        $this->purge($purgeDays, $dryRun, $stats);

        // Stage 2: Orphan cleanup
        $this->cleanupOrphans($orphanDays, $dryRun, $stats);

        // Stage 3: Summary
        $this->printSummary($stats, $dryRun);

        return Command::SUCCESS;
    }

    /**
     * Stage 1: Purge soft-deleted media older than cutoff.
     */
    private function purge(int $days, bool $dryRun, array &$stats): void
    {
        $this->newLine();
        $this->info('Stage 1: Purge soft-deleted media');
        $this->info('---------------------------------');

        $cutoff = now()->subDays($days);

        $toPurge = Media::onlyTrashed()
            ->where('deleted_at', '<', $cutoff)
            ->count();

        $this->info("Soft-deleted media older than {$days} days: {$toPurge}");

        if ($toPurge === 0) {
            $this->info('Nothing to purge.');
            return;
        }

        if ($dryRun) {
            $totalSize = Media::onlyTrashed()
                ->where('deleted_at', '<', $cutoff)
                ->sum('file_size');
            $stats['purged_count'] = $toPurge;
            $stats['purged_mb'] = round($totalSize / 1048576, 2);
            $this->warn("DRY RUN: Would purge {$toPurge} media (~{$stats['purged_mb']} MB)");
            return;
        }

        $purgedCount = 0;
        $freedBytes = 0;

        Media::onlyTrashed()
            ->where('deleted_at', '<', $cutoff)
            ->chunk(100, function ($mediaItems) use (&$purgedCount, &$freedBytes) {
                foreach ($mediaItems as $media) {
                    $freedBytes += $media->file_size ?? 0;

                    // Delete physical file
                    $this->deletePhysicalFile($media);

                    // Delete thumbnail
                    $this->deleteThumbnail($media);

                    $media->forceDelete();
                    $purgedCount++;

                    Log::info('media:cleanup purged soft-deleted media', [
                        'media_id' => $media->id,
                        'file_path' => $media->file_path,
                        'file_size' => $media->file_size,
                    ]);
                }
            });

        $stats['purged_count'] = $purgedCount;
        $stats['purged_mb'] = round($freedBytes / 1048576, 2);

        $this->info("Purged {$purgedCount} soft-deleted media ({$stats['purged_mb']} MB freed)");
    }

    /**
     * Stage 2: Clean up orphaned media (mediable no longer exists).
     */
    private function cleanupOrphans(int $days, bool $dryRun, array &$stats): void
    {
        $this->newLine();
        $this->info('Stage 2: Orphan cleanup');
        $this->info('-----------------------');

        $orphanCutoff = now()->subDays($days);
        $orphans = collect();

        // Find orphaned Product media (mediable_id NOT IN products including soft-deleted)
        $productOrphans = Media::query()
            ->where('mediable_type', 'App\\Models\\Product')
            ->where('created_at', '<', $orphanCutoff)
            ->whereNotIn('mediable_id', Product::withTrashed()->select('id'))
            ->get();

        $orphans = $orphans->merge($productOrphans);

        // Find orphaned ProductVariant media
        $variantOrphans = Media::query()
            ->where('mediable_type', 'App\\Models\\ProductVariant')
            ->where('created_at', '<', $orphanCutoff)
            ->whereNotIn('mediable_id', ProductVariant::withTrashed()->select('id'))
            ->get();

        $orphans = $orphans->merge($variantOrphans);

        $this->info("Orphaned media older than {$days} days: {$orphans->count()}");

        if ($orphans->isEmpty()) {
            $this->info('No orphans found.');
            return;
        }

        if ($dryRun) {
            $totalSize = $orphans->sum('file_size');
            $stats['orphan_count'] = $orphans->count();
            $stats['orphan_mb'] = round($totalSize / 1048576, 2);
            $this->warn("DRY RUN: Would clean {$orphans->count()} orphaned media (~{$stats['orphan_mb']} MB)");
            return;
        }

        $cleanedCount = 0;
        $freedBytes = 0;
        $archivedCount = 0;

        // Ensure archive directory exists
        $archivePath = 'archives/media';
        if (!Storage::disk('local')->exists($archivePath)) {
            Storage::disk('local')->makeDirectory($archivePath);
        }

        foreach ($orphans->chunk(100) as $chunk) {
            foreach ($chunk as $media) {
                $freedBytes += $media->file_size ?? 0;

                // Archive physical file before deleting
                if ($this->archiveFile($media, $archivePath)) {
                    $archivedCount++;
                }

                // Delete physical file from public storage
                $this->deletePhysicalFile($media);

                // Delete thumbnail
                $this->deleteThumbnail($media);

                $media->forceDelete();
                $cleanedCount++;

                Log::info('media:cleanup cleaned orphaned media', [
                    'media_id' => $media->id,
                    'file_path' => $media->file_path,
                    'mediable_type' => $media->mediable_type,
                    'mediable_id' => $media->mediable_id,
                ]);
            }
        }

        $stats['orphan_count'] = $cleanedCount;
        $stats['orphan_mb'] = round($freedBytes / 1048576, 2);
        $stats['orphan_archived'] = $archivedCount;

        $this->info("Cleaned {$cleanedCount} orphaned media ({$stats['orphan_mb']} MB freed, {$archivedCount} archived)");
    }

    /**
     * Delete the physical file from public storage.
     */
    private function deletePhysicalFile(Media $media): void
    {
        if (empty($media->file_path)) {
            return;
        }

        if (Storage::disk('public')->exists($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }
    }

    /**
     * Delete the thumbnail file from public storage.
     * Thumbnail path pattern: dirname/thumbs/filename_thumb.ext
     */
    private function deleteThumbnail(Media $media): void
    {
        if (empty($media->file_path)) {
            return;
        }

        $pathInfo = pathinfo($media->file_path);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'] ?? '';

        $thumbPath = $directory . '/thumbs/' . $filename . '_thumb.' . $extension;

        if (Storage::disk('public')->exists($thumbPath)) {
            Storage::disk('public')->delete($thumbPath);
        }
    }

    /**
     * Archive the physical file to local storage before deleting.
     */
    private function archiveFile(Media $media, string $archivePath): bool
    {
        if (empty($media->file_path)) {
            return false;
        }

        if (!Storage::disk('public')->exists($media->file_path)) {
            return false;
        }

        $archiveFilePath = $archivePath . '/' . basename($media->file_path);

        try {
            $contents = Storage::disk('public')->get($media->file_path);
            Storage::disk('local')->put($archiveFilePath, $contents);
            return true;
        } catch (\Throwable $e) {
            Log::warning('media:cleanup failed to archive file', [
                'media_id' => $media->id,
                'file_path' => $media->file_path,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Stage 3: Print summary report.
     */
    private function printSummary(array $stats, bool $dryRun): void
    {
        $this->newLine();
        $prefix = $dryRun ? 'Summary (DRY RUN):' : 'Summary:';
        $this->info($prefix);

        $totalMb = round($stats['purged_mb'] + $stats['orphan_mb'], 2);

        $this->table(
            ['Action', 'Count', 'MB freed'],
            [
                ['Purged soft-deleted', $stats['purged_count'], $stats['purged_mb']],
                ['Cleaned orphans', $stats['orphan_count'], $stats['orphan_mb']],
                ['Archived before delete', $stats['orphan_archived'], '-'],
                ['TOTAL', $stats['purged_count'] + $stats['orphan_count'], $totalMb],
            ]
        );

        if (!$dryRun) {
            $size = DB::selectOne("
                SELECT ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                AND table_name = 'media'
            ");
            $this->info('Current media table size: ' . ($size->size_mb ?? 'N/A') . ' MB');

            Log::info('media:cleanup completed', [
                'purged_count' => $stats['purged_count'],
                'purged_mb' => $stats['purged_mb'],
                'orphan_count' => $stats['orphan_count'],
                'orphan_mb' => $stats['orphan_mb'],
                'orphan_archived' => $stats['orphan_archived'],
            ]);
        }
    }
}
