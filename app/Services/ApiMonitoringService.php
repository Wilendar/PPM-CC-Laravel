<?php

namespace App\Services;

use App\Models\ApiUsageLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ApiMonitoringService
{
    /**
     * Get API usage statistics
     */
    public function getUsageStatistics(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(7);
        $endDate = $endDate ?? now();
        
        $cacheKey = "api_stats_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 1800, function () use ($startDate, $endDate) {
            $totalRequests = ApiUsageLog::whereBetween('requested_at', [$startDate, $endDate])->count();
            
            $successfulRequests = ApiUsageLog::whereBetween('requested_at', [$startDate, $endDate])
                ->successful()
                ->count();
                
            $failedRequests = ApiUsageLog::whereBetween('requested_at', [$startDate, $endDate])
                ->failed()
                ->count();
                
            $avgResponseTime = ApiUsageLog::whereBetween('requested_at', [$startDate, $endDate])
                ->avg('response_time_ms');
                
            $slowRequests = ApiUsageLog::whereBetween('requested_at', [$startDate, $endDate])
                ->slow(5000)
                ->count();
                
            $suspiciousRequests = ApiUsageLog::whereBetween('requested_at', [$startDate, $endDate])
                ->suspicious()
                ->count();
                
            $rateLimitedRequests = ApiUsageLog::whereBetween('requested_at', [$startDate, $endDate])
                ->rateLimited()
                ->count();

            return [
                'total_requests' => $totalRequests,
                'successful_requests' => $successfulRequests,
                'failed_requests' => $failedRequests,
                'success_rate' => $totalRequests > 0 ? round(($successfulRequests / $totalRequests) * 100, 2) : 0,
                'error_rate' => $totalRequests > 0 ? round(($failedRequests / $totalRequests) * 100, 2) : 0,
                'avg_response_time' => round($avgResponseTime, 2),
                'slow_requests' => $slowRequests,
                'suspicious_requests' => $suspiciousRequests,
                'rate_limited_requests' => $rateLimitedRequests,
            ];
        });
    }

    /**
     * Get endpoint usage statistics
     */
    public function getEndpointStatistics(Carbon $startDate = null, Carbon $endDate = null): Collection
    {
        $startDate = $startDate ?? now()->subDays(7);
        $endDate = $endDate ?? now();
        
        return ApiUsageLog::whereBetween('requested_at', [$startDate, $endDate])
            ->select([
                'endpoint',
                DB::raw('COUNT(*) as total_requests'),
                DB::raw('AVG(response_time_ms) as avg_response_time'),
                DB::raw('MIN(response_time_ms) as min_response_time'),
                DB::raw('MAX(response_time_ms) as max_response_time'),
                DB::raw('COUNT(CASE WHEN response_code >= 400 THEN 1 END) as error_count'),
                DB::raw('COUNT(CASE WHEN suspicious = 1 THEN 1 END) as suspicious_count'),
                DB::raw('COUNT(CASE WHEN response_time_ms > 5000 THEN 1 END) as slow_requests'),
            ])
            ->groupBy('endpoint')
            ->orderBy('total_requests', 'desc')
            ->get()
            ->map(function ($item) {
                $item->error_rate = $item->total_requests > 0 
                    ? round(($item->error_count / $item->total_requests) * 100, 2) 
                    : 0;
                $item->avg_response_time = round($item->avg_response_time, 2);
                return $item;
            });
    }

    /**
     * Get user API usage statistics
     */
    public function getUserStatistics(Carbon $startDate = null, Carbon $endDate = null): Collection
    {
        $startDate = $startDate ?? now()->subDays(7);
        $endDate = $endDate ?? now();
        
        return ApiUsageLog::whereBetween('requested_at', [$startDate, $endDate])
            ->whereNotNull('user_id')
            ->with('user:id,name,email')
            ->select([
                'user_id',
                DB::raw('COUNT(*) as total_requests'),
                DB::raw('AVG(response_time_ms) as avg_response_time'),
                DB::raw('COUNT(CASE WHEN response_code >= 400 THEN 1 END) as error_count'),
                DB::raw('COUNT(CASE WHEN suspicious = 1 THEN 1 END) as suspicious_count'),
                DB::raw('COUNT(DISTINCT endpoint) as unique_endpoints'),
                DB::raw('MAX(requested_at) as last_request'),
            ])
            ->groupBy('user_id')
            ->orderBy('total_requests', 'desc')
            ->get()
            ->map(function ($item) {
                $item->error_rate = $item->total_requests > 0 
                    ? round(($item->error_count / $item->total_requests) * 100, 2) 
                    : 0;
                $item->avg_response_time = round($item->avg_response_time, 2);
                return $item;
            });
    }

    /**
     * Get hourly request distribution
     */
    public function getHourlyDistribution(Carbon $date = null): array
    {
        $date = $date ?? now();
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();
        
        $data = ApiUsageLog::whereBetween('requested_at', [$startOfDay, $endOfDay])
            ->select([
                DB::raw('HOUR(requested_at) as hour'),
                DB::raw('COUNT(*) as requests'),
                DB::raw('AVG(response_time_ms) as avg_response_time'),
                DB::raw('COUNT(CASE WHEN response_code >= 400 THEN 1 END) as errors'),
            ])
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        // Fill missing hours with zeros
        $hourlyData = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $hourlyData[$hour] = [
                'hour' => $hour,
                'requests' => $data->get($hour)->requests ?? 0,
                'avg_response_time' => $data->get($hour)->avg_response_time ?? 0,
                'errors' => $data->get($hour)->errors ?? 0,
            ];
        }

        return $hourlyData;
    }

    /**
     * Get response time percentiles
     */
    public function getResponseTimePercentiles(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(7);
        $endDate = $endDate ?? now();
        
        $responseTimes = ApiUsageLog::whereBetween('requested_at', [$startDate, $endDate])
            ->pluck('response_time_ms')
            ->sort()
            ->values();

        if ($responseTimes->isEmpty()) {
            return [
                'p50' => 0,
                'p90' => 0,
                'p95' => 0,
                'p99' => 0,
            ];
        }

        $count = $responseTimes->count();
        
        return [
            'p50' => $responseTimes->get((int)($count * 0.5)) ?? 0,
            'p90' => $responseTimes->get((int)($count * 0.9)) ?? 0,
            'p95' => $responseTimes->get((int)($count * 0.95)) ?? 0,
            'p99' => $responseTimes->get((int)($count * 0.99)) ?? 0,
        ];
    }

    /**
     * Get top error endpoints
     */
    public function getTopErrorEndpoints(int $limit = 10, Carbon $startDate = null, Carbon $endDate = null): Collection
    {
        $startDate = $startDate ?? now()->subDays(7);
        $endDate = $endDate ?? now();
        
        return ApiUsageLog::whereBetween('requested_at', [$startDate, $endDate])
            ->where('response_code', '>=', 400)
            ->select([
                'endpoint',
                'response_code',
                DB::raw('COUNT(*) as error_count'),
                DB::raw('MAX(requested_at) as last_error'),
            ])
            ->groupBy(['endpoint', 'response_code'])
            ->orderBy('error_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get suspicious activity summary
     */
    public function getSuspiciousActivitySummary(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(7);
        $endDate = $endDate ?? now();
        
        $suspiciousRequests = ApiUsageLog::whereBetween('requested_at', [$startDate, $endDate])
            ->suspicious()
            ->get();

        $topSuspiciousIPs = $suspiciousRequests->groupBy('ip_address')
            ->map(function ($requests) {
                return [
                    'count' => $requests->count(),
                    'last_activity' => $requests->max('requested_at'),
                    'security_notes' => $requests->pluck('security_notes')->filter()->unique()->values(),
                ];
            })
            ->sortByDesc('count')
            ->take(10);

        $suspiciousPatterns = $suspiciousRequests->pluck('security_notes')
            ->filter()
            ->flatMap(function ($notes) {
                return explode('; ', $notes);
            })
            ->countBy()
            ->sortDesc();

        return [
            'total_suspicious' => $suspiciousRequests->count(),
            'unique_ips' => $suspiciousRequests->pluck('ip_address')->unique()->count(),
            'top_suspicious_ips' => $topSuspiciousIPs,
            'suspicious_patterns' => $suspiciousPatterns,
        ];
    }

    /**
     * Get API health status
     */
    public function getHealthStatus(): array
    {
        $now = now();
        $oneHourAgo = $now->copy()->subHour();
        
        // Recent statistics
        $recentStats = $this->getUsageStatistics($oneHourAgo, $now);
        
        // Determine health status
        $status = 'healthy';
        $issues = [];
        
        if ($recentStats['error_rate'] > 10) {
            $status = 'warning';
            $issues[] = "Wysoki wskaźnik błędów: {$recentStats['error_rate']}%";
        }
        
        if ($recentStats['error_rate'] > 25) {
            $status = 'critical';
        }
        
        if ($recentStats['avg_response_time'] > 5000) {
            $status = $status === 'critical' ? 'critical' : 'warning';
            $issues[] = "Powolne odpowiedzi: {$recentStats['avg_response_time']}ms";
        }
        
        if ($recentStats['avg_response_time'] > 10000) {
            $status = 'critical';
        }
        
        if ($recentStats['suspicious_requests'] > 0) {
            $status = $status === 'critical' ? 'critical' : 'warning';
            $issues[] = "Wykryto {$recentStats['suspicious_requests']} podejrzanych żądań";
        }

        return [
            'status' => $status,
            'issues' => $issues,
            'last_check' => $now,
            'stats' => $recentStats,
        ];
    }

    /**
     * Cleanup old API logs
     */
    public function cleanupOldLogs(int $daysToKeep = 90): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        return ApiUsageLog::where('requested_at', '<', $cutoffDate)->delete();
    }

    /**
     * Get daily request trends
     */
    public function getDailyTrends(int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now()->endOfDay();
        
        return ApiUsageLog::whereBetween('requested_at', [$startDate, $endDate])
            ->select([
                DB::raw('DATE(requested_at) as date'),
                DB::raw('COUNT(*) as total_requests'),
                DB::raw('AVG(response_time_ms) as avg_response_time'),
                DB::raw('COUNT(CASE WHEN response_code >= 400 THEN 1 END) as errors'),
                DB::raw('COUNT(CASE WHEN suspicious = 1 THEN 1 END) as suspicious'),
            ])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                $item->error_rate = $item->total_requests > 0 
                    ? round(($item->errors / $item->total_requests) * 100, 2) 
                    : 0;
                $item->avg_response_time = round($item->avg_response_time, 2);
                return $item;
            })
            ->toArray();
    }
}