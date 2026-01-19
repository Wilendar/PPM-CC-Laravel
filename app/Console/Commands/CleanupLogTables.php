<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

/**
 * Universal Log Tables Cleanup Command
 *
 * Czyści wszystkie tabele logowe zgodnie z retention policy
 * zdefiniowaną w config/database-cleanup.php
 *
 * KRYTYCZNE: Zapobiega rozrostowi tabel do gigabajtów!
 *
 * @package App\Console\Commands
 * @since 2025-01-19
 */
class CleanupLogTables extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'logs:cleanup
                            {--table= : Cleanup specific table only}
                            {--dry-run : Show what would be deleted without deleting}
                            {--force : Skip confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old log records from all configured tables based on retention policy';

    /**
     * Tables that have their own specialized commands
     */
    private array $tablesWithCommands = [
        'telescope_entries',
        'price_history',
        'sync_jobs',
        'job_progress',
        'category_previews',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!config('database-cleanup.enabled', true)) {
            $this->warn('Database cleanup is disabled in config.');
            return Command::SUCCESS;
        }

        $this->info('Log Tables Cleanup');
        $this->info('==================');

        $dryRun = $this->option('dry-run');
        $specificTable = $this->option('table');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - no data will be deleted');
        }

        $tables = config('database-cleanup.tables', []);

        if ($specificTable) {
            if (!isset($tables[$specificTable])) {
                $this->error("Table '{$specificTable}' not configured in database-cleanup.php");
                return Command::FAILURE;
            }
            $tables = [$specificTable => $tables[$specificTable]];
        }

        $totalDeleted = 0;
        $results = [];

        foreach ($tables as $tableName => $config) {
            // Skip disabled tables
            if (!($config['enabled'] ?? true)) {
                $this->line("  Skipping {$tableName} (disabled)");
                continue;
            }

            // Skip tables with their own commands (use those instead)
            if (in_array($tableName, $this->tablesWithCommands) && !$specificTable) {
                if (isset($config['command'])) {
                    $this->line("  Skipping {$tableName} (use: php artisan {$config['command']})");
                }
                continue;
            }

            // Check if table exists
            if (!Schema::hasTable($tableName)) {
                $this->line("  Skipping {$tableName} (table does not exist)");
                continue;
            }

            $result = $this->cleanupTable($tableName, $config, $dryRun);
            $results[$tableName] = $result;
            $totalDeleted += $result['deleted'];
        }

        // Summary
        $this->newLine();
        $this->info('Summary:');
        $this->table(
            ['Table', 'Before', 'Deleted', 'After', 'Status'],
            array_map(fn($name, $r) => [
                $name,
                number_format($r['before']),
                number_format($r['deleted']),
                number_format($r['after']),
                $r['status'],
            ], array_keys($results), $results)
        );

        $this->info("Total deleted: " . number_format($totalDeleted) . " records");

        if (!$dryRun && $totalDeleted > 0) {
            Log::info('Log tables cleanup completed', [
                'total_deleted' => $totalDeleted,
                'tables' => array_map(fn($r) => $r['deleted'], $results),
            ]);
        }

        return Command::SUCCESS;
    }

    /**
     * Cleanup a single table
     */
    private function cleanupTable(string $tableName, array $config, bool $dryRun): array
    {
        $retentionDays = $config['retention_days'] ?? 30;
        $dateColumn = $config['date_column'] ?? 'created_at';
        $chunkSize = $config['chunk_size'] ?? 1000;

        $cutoffDate = Carbon::now()->subDays($retentionDays);

        // Get current count
        $beforeCount = DB::table($tableName)->count();

        // Count records to delete
        $toDeleteCount = DB::table($tableName)
            ->where($dateColumn, '<', $cutoffDate)
            ->count();

        $this->line("  {$tableName}: {$beforeCount} records, {$toDeleteCount} older than {$retentionDays} days");

        if ($toDeleteCount === 0) {
            return [
                'before' => $beforeCount,
                'deleted' => 0,
                'after' => $beforeCount,
                'status' => 'OK (nothing to delete)',
            ];
        }

        if ($dryRun) {
            return [
                'before' => $beforeCount,
                'deleted' => $toDeleteCount,
                'after' => $beforeCount - $toDeleteCount,
                'status' => 'DRY RUN',
            ];
        }

        // Delete in chunks
        $totalDeleted = 0;

        do {
            $deleted = DB::table($tableName)
                ->where($dateColumn, '<', $cutoffDate)
                ->limit($chunkSize)
                ->delete();

            $totalDeleted += $deleted;

            // Small delay to prevent DB overload
            if ($deleted === $chunkSize) {
                usleep(50000); // 50ms
            }
        } while ($deleted === $chunkSize);

        $afterCount = DB::table($tableName)->count();

        return [
            'before' => $beforeCount,
            'deleted' => $totalDeleted,
            'after' => $afterCount,
            'status' => 'CLEANED',
        ];
    }
}
