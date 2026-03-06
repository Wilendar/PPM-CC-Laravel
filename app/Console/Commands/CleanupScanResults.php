<?php

namespace App\Console\Commands;

use App\Models\ProductScanResult;
use App\Models\ProductScanSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupScanResults extends Command
{
    protected $signature = 'scan:cleanup
                            {--days=30 : Retention period in days}
                            {--strip : Strip heavy data from resolved but unstripped results}
                            {--dry-run : Show what would be done without doing it}';

    protected $description = 'Clean up old scan results and strip resolved data';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $strip = $this->option('strip');
        $dryRun = $this->option('dry-run');

        $this->info('Scan Results Cleanup');
        $this->info('====================');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - no data will be modified');
        }

        $stats = [
            'stripped' => 0,
            'deleted_results' => 0,
            'deleted_sessions' => 0,
            'freed_estimate' => 0,
        ];

        // Step 1: Strip unstripped resolved results
        if ($strip || !$dryRun) {
            $unstripped = ProductScanResult::resolved()
                ->whereNotNull('source_data')
                ->count();

            $this->info("Unstripped resolved results: {$unstripped}");

            if ($unstripped > 0 && !$dryRun) {
                ProductScanResult::resolved()
                    ->whereNotNull('source_data')
                    ->chunk(100, function ($results) use (&$stats) {
                        foreach ($results as $result) {
                            $result->stripResolvedData();
                            $stats['stripped']++;
                        }
                    });
                $this->info("Stripped: {$stats['stripped']} results");
            } elseif ($dryRun) {
                $stats['stripped'] = $unstripped;
                $this->warn("DRY RUN: Would strip {$unstripped} results");
            }
        }

        // Step 2: Delete old resolved results
        $cutoff = now()->subDays($days);

        $toDelete = ProductScanResult::resolved()
            ->where('created_at', '<', $cutoff)
            ->count();

        $this->info("Resolved results older than {$days} days: {$toDelete}");

        if ($toDelete > 0 && !$dryRun) {
            $deleted = 0;
            do {
                $batch = ProductScanResult::resolved()
                    ->where('created_at', '<', $cutoff)
                    ->limit(1000)
                    ->delete();
                $deleted += $batch;
            } while ($batch > 0);

            $stats['deleted_results'] = $deleted;
            $this->info("Deleted: {$deleted} results");
        } elseif ($dryRun) {
            $stats['deleted_results'] = $toDelete;
        }

        // Step 3: Delete orphaned scan sessions (no results left)
        $orphanedSessions = ProductScanSession::whereDoesntHave('results')->count();

        $this->info("Orphaned scan sessions: {$orphanedSessions}");

        if ($orphanedSessions > 0 && !$dryRun) {
            $stats['deleted_sessions'] = ProductScanSession::whereDoesntHave('results')->delete();
            $this->info("Deleted: {$stats['deleted_sessions']} orphaned sessions");
        } elseif ($dryRun) {
            $stats['deleted_sessions'] = $orphanedSessions;
        }

        // Summary
        $this->newLine();
        $this->info('Summary:');
        $this->table(
            ['Action', 'Count'],
            [
                ['Stripped resolved results', $stats['stripped']],
                ['Deleted old results', $stats['deleted_results']],
                ['Deleted orphaned sessions', $stats['deleted_sessions']],
            ]
        );

        if (!$dryRun) {
            // Get current table size
            $size = DB::selectOne("
                SELECT ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                AND table_name = 'product_scan_results'
            ");
            $this->info("Current table size: " . ($size->size_mb ?? 'N/A') . " MB");

            Log::info('scan:cleanup completed', $stats);
        }

        return Command::SUCCESS;
    }
}
