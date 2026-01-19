<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\SystemSetting;

/**
 * Database Health Check Command
 *
 * Monitoruje rozmiar tabel w bazie danych i wysyla alerty
 * gdy tabele przekrocza skonfigurowane limity.
 *
 * KRYTYCZNE: Zapobiega sytuacji gdy tabele rosna do gigabajtow
 * bez wiedzy administratora (jak Telescope/PriceHistory incident 2025-01-19)
 *
 * @package App\Console\Commands
 * @since 2025-01-19
 */
class DatabaseHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'db:health-check
                            {--alert : Send email alert if thresholds exceeded}
                            {--json : Output as JSON}';

    /**
     * The console command description.
     */
    protected $description = 'Check database health and table sizes, alert on threshold violations';

    /**
     * Tables to monitor with their size limits (in MB)
     * Tables exceeding these limits will trigger warnings/alerts
     */
    private array $tableThresholds = [
        // Log/audit tables - should be regularly cleaned
        'telescope_entries' => ['warning' => 100, 'critical' => 500],
        'telescope_entries_tags' => ['warning' => 50, 'critical' => 200],
        'price_history' => ['warning' => 500, 'critical' => 2000],
        'sync_jobs' => ['warning' => 50, 'critical' => 200],
        'sync_logs' => ['warning' => 20, 'critical' => 100],
        'integration_logs' => ['warning' => 50, 'critical' => 200],
        'job_progress' => ['warning' => 20, 'critical' => 100],
        'failed_jobs' => ['warning' => 10, 'critical' => 50],
        'notifications' => ['warning' => 50, 'critical' => 200],
        'cache' => ['warning' => 100, 'critical' => 500],
        'sessions' => ['warning' => 100, 'critical' => 500],

        // Data tables - larger limits
        'products' => ['warning' => 500, 'critical' => 2000],
        'product_prices' => ['warning' => 200, 'critical' => 1000],
        'product_descriptions' => ['warning' => 500, 'critical' => 2000],
        'media' => ['warning' => 100, 'critical' => 500],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Database Health Check');
        $this->info('=====================');

        // Get all table sizes
        $tables = $this->getTableSizes();

        // Calculate totals
        $totalDataMb = 0;
        $totalIndexMb = 0;
        $totalRows = 0;
        $warnings = [];
        $criticals = [];

        foreach ($tables as $table) {
            $totalDataMb += $table->data_mb;
            $totalIndexMb += $table->index_mb;
            $totalRows += $table->table_rows;

            // Check thresholds
            $tableName = $table->table_name;
            $sizeMb = $table->data_mb + $table->index_mb;

            if (isset($this->tableThresholds[$tableName])) {
                $thresholds = $this->tableThresholds[$tableName];

                if ($sizeMb >= $thresholds['critical']) {
                    $criticals[] = [
                        'table' => $tableName,
                        'size_mb' => $sizeMb,
                        'rows' => $table->table_rows,
                        'threshold' => $thresholds['critical'],
                    ];
                } elseif ($sizeMb >= $thresholds['warning']) {
                    $warnings[] = [
                        'table' => $tableName,
                        'size_mb' => $sizeMb,
                        'rows' => $table->table_rows,
                        'threshold' => $thresholds['warning'],
                    ];
                }
            }
        }

        // JSON output
        if ($this->option('json')) {
            echo json_encode([
                'total_data_mb' => round($totalDataMb, 2),
                'total_index_mb' => round($totalIndexMb, 2),
                'total_size_mb' => round($totalDataMb + $totalIndexMb, 2),
                'total_rows' => $totalRows,
                'warnings' => $warnings,
                'criticals' => $criticals,
                'tables' => array_map(fn($t) => [
                    'name' => $t->table_name,
                    'data_mb' => $t->data_mb,
                    'index_mb' => $t->index_mb,
                    'rows' => $t->table_rows,
                ], $tables),
            ], JSON_PRETTY_PRINT);

            return empty($criticals) ? Command::SUCCESS : Command::FAILURE;
        }

        // Console output
        $this->newLine();
        $this->info("Total database size: " . round($totalDataMb + $totalIndexMb, 2) . " MB");
        $this->info("  - Data: " . round($totalDataMb, 2) . " MB");
        $this->info("  - Indexes: " . round($totalIndexMb, 2) . " MB");
        $this->info("  - Total rows: " . number_format($totalRows));

        // Show top 10 largest tables
        $this->newLine();
        $this->info("Top 10 largest tables:");

        $top10 = array_slice($tables, 0, 10);
        $tableData = [];
        foreach ($top10 as $table) {
            $status = $this->getTableStatus($table->table_name, $table->data_mb + $table->index_mb);
            $tableData[] = [
                $table->table_name,
                round($table->data_mb, 2) . ' MB',
                round($table->index_mb, 2) . ' MB',
                number_format($table->table_rows),
                $status,
            ];
        }

        $this->table(['Table', 'Data', 'Index', 'Rows', 'Status'], $tableData);

        // Show warnings
        if (!empty($warnings)) {
            $this->newLine();
            $this->warn("WARNINGS (" . count($warnings) . "):");
            foreach ($warnings as $w) {
                $this->warn("  - {$w['table']}: {$w['size_mb']} MB (threshold: {$w['threshold']} MB)");
            }
        }

        // Show criticals
        if (!empty($criticals)) {
            $this->newLine();
            $this->error("CRITICAL (" . count($criticals) . "):");
            foreach ($criticals as $c) {
                $this->error("  - {$c['table']}: {$c['size_mb']} MB (threshold: {$c['threshold']} MB)");
            }
        }

        // Send alerts if requested
        if ($this->option('alert') && (!empty($warnings) || !empty($criticals))) {
            $this->sendAlert($warnings, $criticals, $totalDataMb + $totalIndexMb);
        }

        // Log to system
        if (!empty($criticals)) {
            Log::error('Database health check: CRITICAL thresholds exceeded', [
                'criticals' => $criticals,
                'total_size_mb' => round($totalDataMb + $totalIndexMb, 2),
            ]);
            return Command::FAILURE;
        }

        if (!empty($warnings)) {
            Log::warning('Database health check: Warning thresholds exceeded', [
                'warnings' => $warnings,
                'total_size_mb' => round($totalDataMb + $totalIndexMb, 2),
            ]);
        }

        $this->newLine();
        $this->info(empty($warnings) && empty($criticals)
            ? 'All tables within healthy limits.'
            : 'Health check completed with issues.');

        return Command::SUCCESS;
    }

    /**
     * Get all table sizes from database
     */
    private function getTableSizes(): array
    {
        return DB::select("
            SELECT
                table_name,
                ROUND(data_length/1024/1024, 2) AS data_mb,
                ROUND(index_length/1024/1024, 2) AS index_mb,
                table_rows
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            ORDER BY (data_length + index_length) DESC
        ");
    }

    /**
     * Get status indicator for table
     */
    private function getTableStatus(string $tableName, float $sizeMb): string
    {
        if (!isset($this->tableThresholds[$tableName])) {
            return 'OK';
        }

        $thresholds = $this->tableThresholds[$tableName];

        if ($sizeMb >= $thresholds['critical']) {
            return 'CRITICAL';
        }
        if ($sizeMb >= $thresholds['warning']) {
            return 'WARNING';
        }

        return 'OK';
    }

    /**
     * Send email alert
     */
    private function sendAlert(array $warnings, array $criticals, float $totalSizeMb): void
    {
        $this->info('Sending alert email...');

        try {
            $adminEmail = config('database-cleanup.alert_email', 'it@mpptrade.pl');

            $subject = !empty($criticals)
                ? '[PPM CRITICAL] Database size thresholds exceeded!'
                : '[PPM WARNING] Database size warnings';

            $body = "PPM Database Health Check Alert\n";
            $body .= "================================\n\n";
            $body .= "Total database size: " . round($totalSizeMb, 2) . " MB\n\n";

            if (!empty($criticals)) {
                $body .= "CRITICAL ISSUES:\n";
                foreach ($criticals as $c) {
                    $body .= "  - {$c['table']}: {$c['size_mb']} MB ({$c['rows']} rows)\n";
                }
                $body .= "\n";
            }

            if (!empty($warnings)) {
                $body .= "WARNINGS:\n";
                foreach ($warnings as $w) {
                    $body .= "  - {$w['table']}: {$w['size_mb']} MB ({$w['rows']} rows)\n";
                }
                $body .= "\n";
            }

            $body .= "\nRecommended actions:\n";
            $body .= "1. Run: php artisan telescope:prune --hours=48\n";
            $body .= "2. Run: php artisan price-history:cleanup --days=90\n";
            $body .= "3. Run: php artisan sync:cleanup\n";
            $body .= "4. Check integration_logs and sync_logs retention\n";

            Mail::raw($body, function ($message) use ($adminEmail, $subject) {
                $message->to($adminEmail)
                    ->subject($subject);
            });

            $this->info('Alert sent to: ' . $adminEmail);

        } catch (\Exception $e) {
            $this->error('Failed to send alert: ' . $e->getMessage());
            Log::error('Database health check alert failed', ['error' => $e->getMessage()]);
        }
    }
}
