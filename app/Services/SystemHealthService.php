<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class SystemHealthService
{
    /**
     * Sprawdza ogólny stan zdrowia systemu
     */
    public function getSystemHealth(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(), 
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
            'logs' => $this->checkLogs()
        ];
        
        $overallStatus = $this->determineOverallStatus($checks);
        $issues = $this->collectIssues($checks);
        
        return [
            'status' => $overallStatus,
            'checks' => $checks,
            'issues' => $issues,
            'last_check' => now(),
            'uptime' => $this->getUptime()
        ];
    }
    
    /**
     * Sprawdza połączenie z bazą danych
     */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $responseTime = round((microtime(true) - $start) * 1000, 2);
            
            // Test basic query
            $result = DB::select('SELECT 1 as test');
            
            // Check connection count (jeśli możliwe na shared hosting)
            try {
                $connections = DB::select("SHOW STATUS LIKE 'Threads_connected'");
                $activeConnections = $connections[0]->Value ?? 'unknown';
            } catch (Exception $e) {
                $activeConnections = 'unknown';
            }
            
            return [
                'status' => 'healthy',
                'response_time' => $responseTime . 'ms',
                'active_connections' => $activeConnections,
                'message' => 'Database connection successful'
            ];
        } catch (Exception $e) {
            Log::error('Database health check failed: ' . $e->getMessage());
            
            return [
                'status' => 'critical',
                'response_time' => 'timeout',
                'active_connections' => 0,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Sprawdza system cache (Redis lub file)
     */
    private function checkCache(): array
    {
        try {
            $start = microtime(true);
            $testKey = 'health_check_' . time();
            $testValue = 'test_' . rand(1000, 9999);
            
            // Test cache write
            Cache::put($testKey, $testValue, 60);
            
            // Test cache read
            $retrieved = Cache::get($testKey);
            
            // Cleanup
            Cache::forget($testKey);
            
            $responseTime = round((microtime(true) - $start) * 1000, 2);
            
            if ($retrieved === $testValue) {
                return [
                    'status' => 'healthy',
                    'driver' => config('cache.default'),
                    'response_time' => $responseTime . 'ms',
                    'message' => 'Cache system working properly'
                ];
            } else {
                return [
                    'status' => 'warning',
                    'driver' => config('cache.default'),
                    'response_time' => $responseTime . 'ms',
                    'message' => 'Cache read/write mismatch'
                ];
            }
        } catch (Exception $e) {
            Log::error('Cache health check failed: ' . $e->getMessage());
            
            return [
                'status' => 'warning',
                'driver' => config('cache.default'),
                'response_time' => 'error',
                'message' => 'Cache system error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Sprawdza system storage i dostępność dysku
     */
    private function checkStorage(): array
    {
        try {
            $storagePath = storage_path();
            $publicPath = public_path();
            
            // Check if directories are writable
            $storageWritable = is_writable($storagePath);
            $publicWritable = is_writable($publicPath);
            
            // Get disk usage (Linux/Unix only)
            $diskUsage = $this->getDiskUsage();
            
            $status = 'healthy';
            if (!$storageWritable || !$publicWritable) {
                $status = 'critical';
            } elseif ($diskUsage['percentage'] > 90) {
                $status = 'warning';
            }
            
            return [
                'status' => $status,
                'storage_writable' => $storageWritable,
                'public_writable' => $publicWritable,
                'disk_usage' => $diskUsage,
                'message' => $status === 'healthy' ? 'Storage systems operational' : 'Storage issues detected'
            ];
        } catch (Exception $e) {
            Log::error('Storage health check failed: ' . $e->getMessage());
            
            return [
                'status' => 'warning',
                'storage_writable' => false,
                'public_writable' => false,
                'disk_usage' => ['percentage' => 'unknown'],
                'message' => 'Storage check error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Sprawdza system kolejek (jeśli używany)
     */
    private function checkQueue(): array
    {
        try {
            $queueConfig = config('queue.default');
            
            // Mock queue check - w przyszłości można rozszerzyć
            $status = 'healthy';
            $pendingJobs = 0; // Placeholder
            $failedJobs = 0; // Placeholder
            
            return [
                'status' => $status,
                'driver' => $queueConfig,
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
                'message' => 'Queue system status: ' . $status
            ];
        } catch (Exception $e) {
            Log::error('Queue health check failed: ' . $e->getMessage());
            
            return [
                'status' => 'warning',
                'driver' => config('queue.default'),
                'pending_jobs' => 'unknown',
                'failed_jobs' => 'unknown',
                'message' => 'Queue check error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Sprawdza system logowania
     */
    private function checkLogs(): array
    {
        try {
            $logPath = storage_path('logs');
            $logFiles = glob($logPath . '/*.log');
            
            $totalSize = 0;
            $oldestLog = null;
            $newestLog = null;
            
            foreach ($logFiles as $file) {
                $size = filesize($file);
                $totalSize += $size;
                
                $mtime = filemtime($file);
                if (!$oldestLog || $mtime < filemtime($logPath . '/' . $oldestLog)) {
                    $oldestLog = basename($file);
                }
                if (!$newestLog || $mtime > filemtime($logPath . '/' . $newestLog)) {
                    $newestLog = basename($file);
                }
            }
            
            // Test log write
            Log::info('Health check log test', ['timestamp' => now()]);
            
            $status = 'healthy';
            $totalSizeMB = round($totalSize / 1024 / 1024, 2);
            
            if ($totalSizeMB > 100) { // Logs większe niż 100MB
                $status = 'warning';
            }
            
            return [
                'status' => $status,
                'total_files' => count($logFiles),
                'total_size_mb' => $totalSizeMB,
                'oldest_log' => $oldestLog,
                'newest_log' => $newestLog,
                'message' => 'Log system operational'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'warning',
                'total_files' => 0,
                'total_size_mb' => 0,
                'oldest_log' => null,
                'newest_log' => null,
                'message' => 'Log check error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Określa ogólny status na podstawie wszystkich sprawdzeń
     */
    private function determineOverallStatus(array $checks): string
    {
        $hasError = false;
        $hasWarning = false;
        
        foreach ($checks as $check) {
            if ($check['status'] === 'critical') {
                $hasError = true;
            } elseif ($check['status'] === 'warning') {
                $hasWarning = true;
            }
        }
        
        if ($hasError) return 'critical';
        if ($hasWarning) return 'warning';
        return 'healthy';
    }
    
    /**
     * Zbiera wszystkie problemy z systemów
     */
    private function collectIssues(array $checks): array
    {
        $issues = [];
        
        foreach ($checks as $system => $check) {
            if ($check['status'] !== 'healthy') {
                $issues[] = [
                    'system' => $system,
                    'status' => $check['status'],
                    'message' => $check['message']
                ];
            }
        }
        
        return $issues;
    }
    
    /**
     * Pobiera informację o uptime (mock dla shared hostingu)
     */
    private function getUptime(): string
    {
        // Na shared hostingu nie możemy pobrać realnego uptime
        // Możemy użyć cache do śledzenia "soft uptime"
        
        $startTime = Cache::remember('app_start_time', 86400, function () {
            return now();
        });
        
        $uptime = now()->diffForHumans($startTime, true);
        
        return $uptime;
    }
    
    /**
     * Pobiera informację o użyciu dysku
     */
    private function getDiskUsage(): array
    {
        try {
            // Na Windows/shared hostingu może nie działać
            $path = storage_path();
            
            if (function_exists('disk_free_space') && function_exists('disk_total_space')) {
                $free = disk_free_space($path);
                $total = disk_total_space($path);
                
                if ($free !== false && $total !== false) {
                    $used = $total - $free;
                    $percentage = round(($used / $total) * 100, 2);
                    
                    return [
                        'used' => $this->formatBytes($used),
                        'free' => $this->formatBytes($free),
                        'total' => $this->formatBytes($total),
                        'percentage' => $percentage
                    ];
                }
            }
        } catch (Exception $e) {
            // Ignore errors on shared hosting
        }
        
        return [
            'used' => 'unknown',
            'free' => 'unknown', 
            'total' => 'unknown',
            'percentage' => 'unknown'
        ];
    }
    
    /**
     * Formatuje bytes na czytelną formę
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}