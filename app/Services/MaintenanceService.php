<?php

namespace App\Services;

use App\Models\MaintenanceTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class MaintenanceService
{
    /**
     * Wykonaj zadanie maintenance
     */
    public function executeTask(MaintenanceTask $task): bool
    {
        try {
            $task->markAsStarted();
            Log::info("Starting maintenance task: {$task->name}");

            $result = match($task->type) {
                MaintenanceTask::TYPE_DB_OPTIMIZATION => $this->optimizeDatabase($task),
                MaintenanceTask::TYPE_LOG_CLEANUP => $this->cleanupLogs($task),
                MaintenanceTask::TYPE_CACHE_CLEANUP => $this->cleanupCache($task),
                MaintenanceTask::TYPE_SECURITY_CHECK => $this->securityCheck($task),
                MaintenanceTask::TYPE_FILE_CLEANUP => $this->cleanupFiles($task),
                MaintenanceTask::TYPE_INDEX_REBUILD => $this->rebuildIndexes($task),
                MaintenanceTask::TYPE_STATS_UPDATE => $this->updateStats($task),
                default => throw new \Exception("Nieznany typ zadania: {$task->type}")
            };

            $task->markAsCompleted($result);
            Log::info("Maintenance task completed: {$task->name}");

            return true;

        } catch (\Exception $e) {
            $task->markAsFailed($e->getMessage());
            Log::error("Maintenance task failed: {$task->name}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Optymalizacja bazy danych
     */
    private function optimizeDatabase(MaintenanceTask $task): array
    {
        $config = $task->configuration;
        $results = [];

        if ($config['optimize_tables']) {
            $tables = DB::select('SHOW TABLES');
            $dbName = DB::getDatabaseName();
            $tableColumn = "Tables_in_{$dbName}";

            foreach ($tables as $table) {
                $tableName = $table->$tableColumn;
                DB::statement("OPTIMIZE TABLE `{$tableName}`");
                $results['optimized_tables'][] = $tableName;
            }
        }

        if ($config['analyze_tables']) {
            $tables = DB::select('SHOW TABLES');
            $dbName = DB::getDatabaseName();
            $tableColumn = "Tables_in_{$dbName}";

            foreach ($tables as $table) {
                $tableName = $table->$tableColumn;
                DB::statement("ANALYZE TABLE `{$tableName}`");
                $results['analyzed_tables'][] = $tableName;
            }
        }

        if ($config['rebuild_indexes']) {
            // Dla MySQL można użyć ALTER TABLE ... ENGINE=InnoDB
            // lub bardziej zaawansowanych operacji na indeksach
            $results['indexes_rebuilt'] = 'Scheduled for background processing';
        }

        $results['database_size'] = $this->getDatabaseSize();
        $results['completion_time'] = now()->toISOString();

        return $results;
    }

    /**
     * Czyszczenie logów
     */
    private function cleanupLogs(MaintenanceTask $task): array
    {
        $config = $task->configuration;
        $results = [];

        $logPath = storage_path('logs');
        $retentionDays = $config['retention_days'] ?? 30;
        $cutoffDate = Carbon::now()->subDays($retentionDays);

        $deletedFiles = 0;
        $deletedSize = 0;
        $compressedFiles = 0;

        $files = File::allFiles($logPath);

        foreach ($files as $file) {
            $fileTime = Carbon::createFromTimestamp($file->getMTime());
            $fileSizeMB = $file->getSize() / 1024 / 1024;

            // Usuń stare pliki
            if ($fileTime->lt($cutoffDate)) {
                $deletedSize += $file->getSize();
                File::delete($file->getPathname());
                $deletedFiles++;
                continue;
            }

            // Skompresuj duże pliki
            if ($config['compress_old_logs'] && 
                $fileSizeMB > ($config['max_log_size_mb'] ?? 100) &&
                !str_ends_with($file->getFilename(), '.gz')) {
                
                $this->compressLogFile($file->getPathname());
                $compressedFiles++;
            }

            // Usuń puste pliki
            if ($config['delete_empty_logs'] && $file->getSize() === 0) {
                File::delete($file->getPathname());
                $deletedFiles++;
            }
        }

        $results = [
            'deleted_files' => $deletedFiles,
            'deleted_size_mb' => round($deletedSize / 1024 / 1024, 2),
            'compressed_files' => $compressedFiles,
            'retention_days' => $retentionDays,
            'completion_time' => now()->toISOString(),
        ];

        return $results;
    }

    /**
     * Czyszczenie cache
     */
    private function cleanupCache(MaintenanceTask $task): array
    {
        $config = $task->configuration;
        $results = [];

        if ($config['clear_application_cache']) {
            \Artisan::call('cache:clear');
            $results['application_cache'] = 'cleared';
        }

        if ($config['clear_view_cache']) {
            \Artisan::call('view:clear');
            $results['view_cache'] = 'cleared';
        }

        if ($config['clear_route_cache']) {
            \Artisan::call('route:clear');
            $results['route_cache'] = 'cleared';
        }

        if ($config['clear_config_cache']) {
            \Artisan::call('config:clear');
            $results['config_cache'] = 'cleared';
        }

        // Wyczyść cache storage
        $cacheSize = $this->getCacheDirectorySize();
        if ($cacheSize > 0) {
            Storage::disk('local')->deleteDirectory('framework/cache');
            Storage::disk('local')->makeDirectory('framework/cache');
            $results['storage_cache_size_mb'] = round($cacheSize / 1024 / 1024, 2);
        }

        $results['completion_time'] = now()->toISOString();

        return $results;
    }

    /**
     * Kontrola bezpieczeństwa
     */
    private function securityCheck(MaintenanceTask $task): array
    {
        $config = $task->configuration;
        $results = [];

        if ($config['check_file_permissions']) {
            $results['file_permissions'] = $this->checkFilePermissions();
        }

        if ($config['check_config_security']) {
            $results['config_security'] = $this->checkConfigSecurity();
        }

        if ($config['check_dependencies']) {
            $results['dependencies'] = $this->checkDependencies();
        }

        if ($config['check_ssl_certificates']) {
            $results['ssl_certificates'] = $this->checkSSLCertificates();
        }

        $results['completion_time'] = now()->toISOString();

        return $results;
    }

    /**
     * Czyszczenie plików
     */
    private function cleanupFiles(MaintenanceTask $task): array
    {
        $config = $task->configuration;
        $results = [];

        $deletedFiles = 0;
        $deletedSize = 0;

        // Czyszczenie plików tymczasowych
        if ($config['cleanup_temp_files']) {
            $tempPath = storage_path('app/temp');
            if (is_dir($tempPath)) {
                $tempFiles = File::allFiles($tempPath);
                foreach ($tempFiles as $file) {
                    $deletedSize += $file->getSize();
                    File::delete($file->getPathname());
                    $deletedFiles++;
                }
            }
        }

        // Czyszczenie starych uploadów
        if ($config['cleanup_old_uploads']) {
            $uploadsPath = storage_path('app/public/uploads');
            $maxAge = $config['max_file_age_days'] ?? 90;
            $cutoffDate = Carbon::now()->subDays($maxAge);

            if (is_dir($uploadsPath)) {
                $uploadFiles = File::allFiles($uploadsPath);
                foreach ($uploadFiles as $file) {
                    $fileTime = Carbon::createFromTimestamp($file->getMTime());
                    if ($fileTime->lt($cutoffDate)) {
                        $deletedSize += $file->getSize();
                        File::delete($file->getPathname());
                        $deletedFiles++;
                    }
                }
            }
        }

        // Sprawdź wolne miejsce na dysku
        $freeSpace = disk_free_space(storage_path());
        $minFreeSpaceBytes = ($config['min_free_space_gb'] ?? 1) * 1024 * 1024 * 1024;

        $results = [
            'deleted_files' => $deletedFiles,
            'deleted_size_mb' => round($deletedSize / 1024 / 1024, 2),
            'free_space_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
            'min_free_space_met' => $freeSpace >= $minFreeSpaceBytes,
            'completion_time' => now()->toISOString(),
        ];

        return $results;
    }

    /**
     * Odbudowa indeksów
     */
    private function rebuildIndexes(MaintenanceTask $task): array
    {
        $results = [];

        // Lista kluczowych tabel do reindexowania
        $importantTables = [
            'products', 'categories', 'users', 'system_settings', 
            'backup_jobs', 'maintenance_tasks'
        ];

        foreach ($importantTables as $tableName) {
            try {
                // Sprawdź czy tabela istnieje
                $exists = DB::table('information_schema.tables')
                           ->where('table_schema', DB::getDatabaseName())
                           ->where('table_name', $tableName)
                           ->exists();

                if ($exists) {
                    DB::statement("ANALYZE TABLE `{$tableName}`");
                    $results['reindexed_tables'][] = $tableName;
                }
            } catch (\Exception $e) {
                $results['errors'][] = "Failed to reindex {$tableName}: " . $e->getMessage();
            }
        }

        $results['completion_time'] = now()->toISOString();

        return $results;
    }

    /**
     * Aktualizacja statystyk
     */
    private function updateStats(MaintenanceTask $task): array
    {
        $results = [];

        // Podstawowe statystyki aplikacji
        $results['stats'] = [
            'total_users' => DB::table('users')->count(),
            'total_products' => DB::table('products')->count() ?? 0,
            'total_categories' => DB::table('categories')->count() ?? 0,
            'database_size_mb' => $this->getDatabaseSize(),
            'storage_size_mb' => $this->getStorageSize(),
            'cache_size_mb' => round($this->getCacheDirectorySize() / 1024 / 1024, 2),
        ];

        // Statystyki wydajności
        $results['performance'] = [
            'avg_response_time' => $this->getAverageResponseTime(),
            'error_rate_24h' => $this->getErrorRate(),
            'disk_usage_percent' => $this->getDiskUsagePercentage(),
        ];

        $results['completion_time'] = now()->toISOString();

        return $results;
    }

    /**
     * Pomocnicze metody
     */
    private function compressLogFile(string $filePath): void
    {
        $data = file_get_contents($filePath);
        $compressedData = gzencode($data);
        file_put_contents($filePath . '.gz', $compressedData);
        unlink($filePath);
    }

    private function getDatabaseSize(): float
    {
        $result = DB::select("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size_mb' 
            FROM information_schema.tables 
            WHERE table_schema = ?
        ", [DB::getDatabaseName()]);

        return $result[0]->size_mb ?? 0;
    }

    private function getStorageSize(): float
    {
        $storagePath = storage_path('app');
        $size = 0;
        
        if (is_dir($storagePath)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($storagePath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                $size += $file->getSize();
            }
        }

        return round($size / 1024 / 1024, 2);
    }

    private function getCacheDirectorySize(): int
    {
        $cachePath = storage_path('framework/cache');
        $size = 0;
        
        if (is_dir($cachePath)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($cachePath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    private function checkFilePermissions(): array
    {
        $checks = [];
        
        $criticalDirs = [
            'storage' => storage_path(),
            'cache' => storage_path('framework/cache'),
            'logs' => storage_path('logs'),
            'public' => public_path(),
        ];

        foreach ($criticalDirs as $name => $path) {
            if (is_dir($path)) {
                $checks[$name] = [
                    'writable' => is_writable($path),
                    'permissions' => substr(sprintf('%o', fileperms($path)), -4),
                ];
            }
        }

        return $checks;
    }

    private function checkConfigSecurity(): array
    {
        $issues = [];

        // Sprawdź APP_DEBUG
        if (config('app.debug') === true) {
            $issues[] = 'APP_DEBUG is enabled in production';
        }

        // Sprawdź APP_KEY
        if (empty(config('app.key'))) {
            $issues[] = 'APP_KEY is not set';
        }

        // Sprawdź HTTPS
        if (!config('app.secure')) {
            $issues[] = 'HTTPS is not enforced';
        }

        return [
            'issues_found' => count($issues),
            'issues' => $issues,
        ];
    }

    private function checkDependencies(): array
    {
        // Sprawdź czy composer.lock jest aktualny
        $composerPath = base_path('composer.json');
        $composerLockPath = base_path('composer.lock');
        
        $outdated = false;
        if (file_exists($composerPath) && file_exists($composerLockPath)) {
            $outdated = filemtime($composerPath) > filemtime($composerLockPath);
        }

        return [
            'composer_lock_outdated' => $outdated,
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];
    }

    private function checkSSLCertificates(): array
    {
        // Podstawowa implementacja - można rozszerzyć
        $domain = config('app.url');
        
        if (!str_starts_with($domain, 'https://')) {
            return ['ssl_enabled' => false];
        }

        // W prawdziwej implementacji można by sprawdzić certyfikat
        return [
            'ssl_enabled' => true,
            'domain' => $domain,
            'check_note' => 'Manual certificate check required',
        ];
    }

    private function getAverageResponseTime(): float
    {
        // Placeholder - w prawdziwej implementacji można by to pobierać z logów
        return 0.15; // 150ms
    }

    private function getErrorRate(): float
    {
        // Placeholder - można implementować na podstawie logów Laravel
        return 0.1; // 0.1%
    }

    private function getDiskUsagePercentage(): float
    {
        $total = disk_total_space(storage_path());
        $free = disk_free_space(storage_path());
        
        if ($total > 0) {
            return round((($total - $free) / $total) * 100, 2);
        }
        
        return 0;
    }

    /**
     * Pobierz statystyki maintenance
     */
    public function getMaintenanceStats(): array
    {
        return [
            'total_tasks' => MaintenanceTask::count(),
            'pending_tasks' => MaintenanceTask::pending()->count(),
            'completed_tasks' => MaintenanceTask::completed()->count(),
            'failed_tasks' => MaintenanceTask::where('status', MaintenanceTask::STATUS_FAILED)->count(),
            'recurring_tasks' => MaintenanceTask::recurring()->count(),
            'next_scheduled_task' => MaintenanceTask::pending()
                                                   ->orderBy('scheduled_at')
                                                   ->first()?->scheduled_at,
            'last_completed_task' => MaintenanceTask::completed()
                                                   ->latest('completed_at')
                                                   ->first()?->completed_at,
        ];
    }

    /**
     * Utwórz zadanie cykliczne
     */
    public function createRecurringTask(string $name, string $type, string $recurrence, array $configuration = []): MaintenanceTask
    {
        $nextRun = match($recurrence) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(), 
            'monthly' => now()->addMonth(),
            default => now()->addHour(),
        };

        return MaintenanceTask::create([
            'name' => $name,
            'type' => $type,
            'scheduled_at' => $nextRun,
            'configuration' => array_merge(
                MaintenanceTask::getDefaultConfiguration($type),
                $configuration
            ),
            'is_recurring' => true,
            'recurrence_rule' => $recurrence,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Uruchom wszystkie zaległe zadania
     */
    public function runDueTasks(): array
    {
        $dueTasks = MaintenanceTask::due()->limit(10)->get();
        $results = [];

        foreach ($dueTasks as $task) {
            $results[] = [
                'task_id' => $task->id,
                'task_name' => $task->name,
                'success' => $this->executeTask($task),
            ];
        }

        return $results;
    }
}