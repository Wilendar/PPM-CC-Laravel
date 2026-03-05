<?php

namespace App\Http\Livewire\Dashboard\Widgets;

use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * SystemHealth Widget (Admin-only)
 *
 * Monitors core infrastructure health: database connectivity,
 * cache operation, storage capacity, and queue status.
 * Polls every 60 seconds for status updates.
 */
class SystemHealth extends Component
{
    public array $checks = [];

    public array $dbDetails = [];

    public function mount(): void
    {
        $this->runChecks();
    }

    /**
     * Polled every 60s via wire:poll.60s in blade.
     */
    public function runChecks(): void
    {
        $this->checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $this->loadDbDetails();
    }

    protected function checkDatabase(): array
    {
        try {
            $start = hrtime(true);
            DB::select('SELECT 1');
            $responseMs = round((hrtime(true) - $start) / 1e6, 2);

            $status = 'healthy';
            if ($responseMs > 1000) {
                $status = 'error';
            } elseif ($responseMs > 500) {
                $status = 'warning';
            }

            return [
                'status' => $status,
                'message' => 'Polaczenie aktywne',
                'details' => "Czas odpowiedzi: {$responseMs}ms",
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Blad polaczenia',
                'details' => mb_substr($e->getMessage(), 0, 80),
            ];
        }
    }

    protected function checkCache(): array
    {
        try {
            $testKey = 'health_check_' . time();
            Cache::put($testKey, 'ok', 10);
            $value = Cache::get($testKey);
            Cache::forget($testKey);

            $driver = config('cache.default');

            if ($value === 'ok') {
                return [
                    'status' => 'healthy',
                    'message' => 'Cache dziala poprawnie',
                    'details' => "Driver: {$driver}",
                ];
            }

            return [
                'status' => 'warning',
                'message' => 'Problem z odczytem cache',
                'details' => "Driver: {$driver}",
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Blad cache',
                'details' => mb_substr($e->getMessage(), 0, 80),
            ];
        }
    }

    protected function checkStorage(): array
    {
        try {
            $freeBytes = disk_free_space('/');
            $totalBytes = disk_total_space('/');

            if ($totalBytes <= 0) {
                return [
                    'status' => 'unknown',
                    'message' => 'Nie mozna odczytac',
                    'details' => 'Brak danych o dysku',
                ];
            }

            $usedPercent = round((($totalBytes - $freeBytes) / $totalBytes) * 100, 1);
            $freeFormatted = $this->formatBytes($freeBytes);

            $status = 'healthy';
            if ($usedPercent > 90) {
                $status = 'error';
            } elseif ($usedPercent > 75) {
                $status = 'warning';
            }

            return [
                'status' => $status,
                'message' => "Zajete: {$usedPercent}%",
                'details' => "Wolne: {$freeFormatted}",
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unknown',
                'message' => 'Nie mozna odczytac',
                'details' => mb_substr($e->getMessage(), 0, 80),
            ];
        }
    }

    protected function checkQueue(): array
    {
        try {
            $failedCount = DB::table('failed_jobs')->count();
            $pendingCount = DB::table('jobs')->count();

            $status = 'healthy';
            if ($failedCount > 10) {
                $status = 'error';
            } elseif ($failedCount > 0 || $pendingCount > 50) {
                $status = 'warning';
            }

            return [
                'status' => $status,
                'message' => "{$failedCount} nieudanych / {$pendingCount} oczekujacych",
                'details' => $failedCount > 0
                    ? "Sprawdz logi bledow"
                    : "Kolejka dziala poprawnie",
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unknown',
                'message' => 'Nie mozna sprawdzic',
                'details' => mb_substr($e->getMessage(), 0, 80),
            ];
        }
    }

    public function loadDbDetails(): void
    {
        try {
            // Top 5 tables by size
            $tables = DB::select("
                SELECT table_name, table_rows,
                       ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                ORDER BY data_length + index_length DESC
                LIMIT 5
            ");

            // Active connections
            $threads = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            $threadsConnected = $threads[0]->Value ?? 0;

            // Slow queries count
            $slow = DB::select("SHOW STATUS LIKE 'Slow_queries'");
            $slowQueries = $slow[0]->Value ?? 0;

            $this->dbDetails = [
                'tables' => array_map(fn($t) => [
                    'name' => $t->table_name,
                    'rows' => number_format($t->table_rows),
                    'size' => $t->size_mb . ' MB',
                ], $tables),
                'connections' => (int) $threadsConnected,
                'slow_queries' => (int) $slowQueries,
            ];
        } catch (\Exception $e) {
            $this->dbDetails = [];
        }
    }

    protected function formatBytes(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 1) . ' ' . $units[$i];
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.system-health');
    }
}
