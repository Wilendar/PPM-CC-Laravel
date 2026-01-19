<?php

namespace App\Console\Commands;

use App\Models\PriceHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Artisan Command: Cleanup Price History
 *
 * Usuwa stare rekordy z tabeli price_history zgodnie z retention policy.
 * KRYTYCZNE: Tabela moze rosnac do gigabajtow jesli nie jest regularnie czyszczona!
 *
 * @package App\Console\Commands
 * @since 2025-01-19
 */
class CleanupPriceHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'price-history:cleanup
                            {--days=90 : Retention period in days (default: 90)}
                            {--chunk=10000 : Delete in chunks to avoid memory issues}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old price history records based on retention policy';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $retentionDays = (int) $this->option('days');
        $chunkSize = (int) $this->option('chunk');
        $dryRun = $this->option('dry-run');

        $this->info("Price History Cleanup");
        $this->info("=====================");
        $this->info("Retention period: {$retentionDays} days");
        $this->info("Chunk size: {$chunkSize}");

        if ($dryRun) {
            $this->warn("DRY RUN MODE - no data will be deleted");
        }

        // Get current stats
        $beforeCount = PriceHistory::count();
        $beforeSize = $this->getTableSize();

        $this->info("Before cleanup:");
        $this->info("  - Records: " . number_format($beforeCount));
        $this->info("  - Table size: {$beforeSize}");

        // Count records to delete
        $cutoffDate = now()->subDays($retentionDays);
        $toDeleteCount = PriceHistory::where('created_at', '<', $cutoffDate)->count();

        if ($toDeleteCount === 0) {
            $this->info("No records older than {$retentionDays} days found. Nothing to clean.");
            return Command::SUCCESS;
        }

        $this->info("Records to delete: " . number_format($toDeleteCount));

        if ($dryRun) {
            $this->warn("DRY RUN: Would delete {$toDeleteCount} records");
            return Command::SUCCESS;
        }

        // Delete in chunks to avoid memory/timeout issues
        $totalDeleted = 0;
        $this->output->progressStart($toDeleteCount);

        do {
            $deleted = PriceHistory::where('created_at', '<', $cutoffDate)
                ->limit($chunkSize)
                ->delete();

            $totalDeleted += $deleted;
            $this->output->progressAdvance($deleted);

            // Small delay to prevent DB overload
            if ($deleted === $chunkSize) {
                usleep(100000); // 100ms
            }
        } while ($deleted === $chunkSize);

        $this->output->progressFinish();

        // Get stats after cleanup
        $afterCount = PriceHistory::count();
        $afterSize = $this->getTableSize();

        $this->newLine();
        $this->info("Cleanup completed!");
        $this->info("After cleanup:");
        $this->info("  - Records: " . number_format($afterCount));
        $this->info("  - Table size: {$afterSize}");
        $this->info("  - Deleted: " . number_format($totalDeleted) . " records");

        // Suggest OPTIMIZE TABLE if significant data was deleted
        if ($totalDeleted > 10000) {
            $this->warn("TIP: Run 'OPTIMIZE TABLE price_history' to reclaim disk space");
        }

        return Command::SUCCESS;
    }

    /**
     * Get current table size
     */
    private function getTableSize(): string
    {
        $result = DB::selectOne("
            SELECT ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = 'price_history'
        ");

        return ($result->size_mb ?? 0) . ' MB';
    }
}
