<?php

namespace App\Services;

use App\Models\SystemReport;
use App\Models\User;
use App\Models\Product;
use App\Models\AdminNotification;
use App\Models\ApiUsageLog;
use App\Models\PrestaShopShop;
use App\Models\ERPConnection;
use App\Jobs\GenerateReportJob;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ReportsService
{
    /**
     * Generate usage analytics report
     */
    public function generateUsageAnalyticsReport(string $period = 'daily', ?Carbon $date = null): SystemReport
    {
        $date = $date ?? now()->startOfDay();
        $reportName = "Usage Analytics - " . ucfirst($period) . " - " . $date->format('Y-m-d');
        
        $report = SystemReport::create([
            'name' => $reportName,
            'type' => SystemReport::TYPE_USAGE_ANALYTICS,
            'period' => $period,
            'report_date' => $date->toDateString(),
            'status' => SystemReport::STATUS_GENERATING,
            'generated_by' => auth()->id(),
            'data' => [],
        ]);

        // Queue report generation
        GenerateReportJob::dispatch($report);
        
        return $report;
    }

    /**
     * Generate performance report
     */
    public function generatePerformanceReport(string $period = 'daily', ?Carbon $date = null): SystemReport
    {
        $date = $date ?? now()->startOfDay();
        $reportName = "Performance Report - " . ucfirst($period) . " - " . $date->format('Y-m-d');
        
        $report = SystemReport::create([
            'name' => $reportName,
            'type' => SystemReport::TYPE_PERFORMANCE,
            'period' => $period,
            'report_date' => $date->toDateString(),
            'status' => SystemReport::STATUS_GENERATING,
            'generated_by' => auth()->id(),
            'data' => [],
        ]);

        GenerateReportJob::dispatch($report);
        
        return $report;
    }

    /**
     * Generate business intelligence report
     */
    public function generateBusinessIntelligenceReport(string $period = 'weekly', ?Carbon $date = null): SystemReport
    {
        $date = $date ?? now()->startOfWeek();
        $reportName = "Business Intelligence - " . ucfirst($period) . " - " . $date->format('Y-m-d');
        
        $report = SystemReport::create([
            'name' => $reportName,
            'type' => SystemReport::TYPE_BUSINESS_INTELLIGENCE,
            'period' => $period,
            'report_date' => $date->toDateString(),
            'status' => SystemReport::STATUS_GENERATING,
            'generated_by' => auth()->id(),
            'data' => [],
        ]);

        GenerateReportJob::dispatch($report);
        
        return $report;
    }

    /**
     * Generate integration performance report
     */
    public function generateIntegrationPerformanceReport(string $period = 'daily', ?Carbon $date = null): SystemReport
    {
        $date = $date ?? now()->startOfDay();
        $reportName = "Integration Performance - " . ucfirst($period) . " - " . $date->format('Y-m-d');
        
        $report = SystemReport::create([
            'name' => $reportName,
            'type' => SystemReport::TYPE_INTEGRATION_PERFORMANCE,
            'period' => $period,
            'report_date' => $date->toDateString(),
            'status' => SystemReport::STATUS_GENERATING,
            'generated_by' => auth()->id(),
            'data' => [],
        ]);

        GenerateReportJob::dispatch($report);
        
        return $report;
    }

    /**
     * Build usage analytics data
     */
    public function buildUsageAnalyticsData(Carbon $startDate, Carbon $endDate): array
    {
        $cacheKey = "usage_analytics_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 3600, function () use ($startDate, $endDate) {
            return [
                'user_activity' => $this->getUserActivityData($startDate, $endDate),
                'feature_usage' => $this->getFeatureUsageData($startDate, $endDate),
                'login_patterns' => $this->getLoginPatternsData($startDate, $endDate),
                'session_analytics' => $this->getSessionAnalyticsData($startDate, $endDate),
                'summary' => $this->getUserActivitySummary($startDate, $endDate),
            ];
        });
    }

    /**
     * Build performance data
     */
    public function buildPerformanceData(Carbon $startDate, Carbon $endDate): array
    {
        $cacheKey = "performance_data_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 1800, function () use ($startDate, $endDate) {
            return [
                'api_performance' => $this->getApiPerformanceData($startDate, $endDate),
                'database_performance' => $this->getDatabasePerformanceData($startDate, $endDate),
                'error_rates' => $this->getErrorRatesData($startDate, $endDate),
                'resource_utilization' => $this->getResourceUtilizationData($startDate, $endDate),
                'summary' => $this->getPerformanceSummary($startDate, $endDate),
            ];
        });
    }

    /**
     * Build business intelligence data
     */
    public function buildBusinessIntelligenceData(Carbon $startDate, Carbon $endDate): array
    {
        $cacheKey = "business_intelligence_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 3600, function () use ($startDate, $endDate) {
            return [
                'product_management' => $this->getProductManagementData($startDate, $endDate),
                'user_productivity' => $this->getUserProductivityData($startDate, $endDate),
                'data_quality' => $this->getDataQualityData(),
                'category_distribution' => $this->getCategoryDistributionData(),
                'summary' => $this->getBusinessIntelligenceSummary($startDate, $endDate),
            ];
        });
    }

    /**
     * Build integration performance data
     */
    public function buildIntegrationPerformanceData(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'prestashop_performance' => $this->getPrestaShopPerformanceData($startDate, $endDate),
            'erp_performance' => $this->getERPPerformanceData($startDate, $endDate),
            'sync_statistics' => $this->getSyncStatisticsData($startDate, $endDate),
            'api_usage' => $this->getAPIUsageData($startDate, $endDate),
            'summary' => $this->getIntegrationPerformanceSummary($startDate, $endDate),
        ];
    }

    /**
     * Get user activity data
     */
    protected function getUserActivityData(Carbon $startDate, Carbon $endDate): array
    {
        $dailyUsers = DB::table('users')
            ->selectRaw('DATE(last_login_at) as date, COUNT(*) as active_users')
            ->whereBetween('last_login_at', [$startDate, $endDate])
            ->whereNotNull('last_login_at')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('active_users', 'date')
            ->toArray();

        $totalUsers = User::count();
        $activeUsers = User::whereBetween('last_login_at', [$startDate, $endDate])->count();
        
        return [
            'daily_active_users' => $dailyUsers,
            'total_users' => $totalUsers,
            'active_users_period' => $activeUsers,
            'activity_rate' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0,
        ];
    }

    /**
     * Get feature usage data
     */
    protected function getFeatureUsageData(Carbon $startDate, Carbon $endDate): array
    {
        // This would track feature usage through API logs or custom tracking
        return [
            'product_management' => ApiUsageLog::whereIn('endpoint', [
                '/api/products', '/api/products/create', '/api/products/update'
            ])->whereBetween('requested_at', [$startDate, $endDate])->count(),
            
            'category_management' => ApiUsageLog::whereIn('endpoint', [
                '/api/categories', '/api/categories/create'
            ])->whereBetween('requested_at', [$startDate, $endDate])->count(),
            
            'import_export' => ApiUsageLog::whereIn('endpoint', [
                '/api/import', '/api/export'
            ])->whereBetween('requested_at', [$startDate, $endDate])->count(),
            
            'prestashop_sync' => ApiUsageLog::where('endpoint', 'LIKE', '%prestashop%')
                ->whereBetween('requested_at', [$startDate, $endDate])->count(),
        ];
    }

    /**
     * Get login patterns data
     */
    protected function getLoginPatternsData(Carbon $startDate, Carbon $endDate): array
    {
        $hourlyLogins = DB::table('users')
            ->selectRaw('HOUR(last_login_at) as hour, COUNT(*) as logins')
            ->whereBetween('last_login_at', [$startDate, $endDate])
            ->whereNotNull('last_login_at')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('logins', 'hour')
            ->toArray();

        $weekdayLogins = DB::table('users')
            ->selectRaw('DAYOFWEEK(last_login_at) as weekday, COUNT(*) as logins')
            ->whereBetween('last_login_at', [$startDate, $endDate])
            ->whereNotNull('last_login_at')
            ->groupBy('weekday')
            ->orderBy('weekday')
            ->get()
            ->pluck('logins', 'weekday')
            ->toArray();

        return [
            'hourly_distribution' => $hourlyLogins,
            'weekday_distribution' => $weekdayLogins,
            'peak_hour' => array_keys($hourlyLogins, max($hourlyLogins))[0] ?? null,
        ];
    }

    /**
     * Get session analytics data
     */
    protected function getSessionAnalyticsData(Carbon $startDate, Carbon $endDate): array
    {
        // This would require session tracking implementation
        return [
            'average_session_duration' => 'N/A', // Would be calculated from session data
            'total_sessions' => 'N/A',
            'bounce_rate' => 'N/A',
        ];
    }

    /**
     * Get API performance data
     */
    protected function getApiPerformanceData(Carbon $startDate, Carbon $endDate): array
    {
        $avgResponseTime = ApiUsageLog::whereBetween('requested_at', [$startDate, $endDate])
            ->avg('response_time_ms');

        $slowQueries = ApiUsageLog::whereBetween('requested_at', [$startDate, $endDate])
            ->where('response_time_ms', '>', 5000)
            ->count();

        $errorRate = ApiUsageLog::whereBetween('requested_at', [$startDate, $endDate])
            ->where('response_code', '>=', 400)
            ->count();

        $totalRequests = ApiUsageLog::whereBetween('requested_at', [$startDate, $endDate])->count();

        return [
            'average_response_time' => round($avgResponseTime, 2),
            'slow_queries_count' => $slowQueries,
            'error_rate' => $totalRequests > 0 ? round(($errorRate / $totalRequests) * 100, 2) : 0,
            'total_requests' => $totalRequests,
        ];
    }

    /**
     * Get product management data
     */
    protected function getProductManagementData(Carbon $startDate, Carbon $endDate): array
    {
        $productsCreated = Product::whereBetween('created_at', [$startDate, $endDate])->count();
        $productsUpdated = Product::whereBetween('updated_at', [$startDate, $endDate])
            ->where('created_at', '<', $startDate)
            ->count();

        return [
            'products_created' => $productsCreated,
            'products_updated' => $productsUpdated,
            'total_products' => Product::count(),
            'creation_velocity' => $productsCreated / max(1, $startDate->diffInDays($endDate)),
        ];
    }

    /**
     * Get reports by type and period
     */
    public function getReportsByType(string $type, string $period = null, int $limit = 10): Collection
    {
        $query = SystemReport::byType($type)
            ->completed()
            ->orderBy('report_date', 'desc')
            ->limit($limit);

        if ($period) {
            $query->byPeriod($period);
        }

        return $query->get();
    }

    /**
     * Get latest reports dashboard
     */
    public function getLatestReports(int $limit = 5): Collection
    {
        return SystemReport::completed()
            ->orderBy('generated_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get report statistics
     */
    public function getReportStatistics(): array
    {
        return [
            'total_reports' => SystemReport::count(),
            'completed_reports' => SystemReport::completed()->count(),
            'failed_reports' => SystemReport::where('status', SystemReport::STATUS_FAILED)->count(),
            'generating_reports' => SystemReport::where('status', SystemReport::STATUS_GENERATING)->count(),
            'by_type' => SystemReport::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
        ];
    }

    /**
     * Private helper methods for data collection
     */
    protected function getUserActivitySummary(Carbon $startDate, Carbon $endDate): string
    {
        $days = $startDate->diffInDays($endDate) + 1;
        $activeUsers = User::whereBetween('last_login_at', [$startDate, $endDate])->count();
        
        return "W okresie {$days} dni, {$activeUsers} użytkowników było aktywnych w systemie.";
    }

    protected function getPerformanceSummary(Carbon $startDate, Carbon $endDate): string
    {
        $avgResponse = ApiUsageLog::whereBetween('requested_at', [$startDate, $endDate])
            ->avg('response_time_ms');
            
        return "Średni czas odpowiedzi API: " . round($avgResponse, 2) . "ms";
    }

    protected function getBusinessIntelligenceSummary(Carbon $startDate, Carbon $endDate): string
    {
        $productsCreated = Product::whereBetween('created_at', [$startDate, $endDate])->count();
        
        return "Utworzono {$productsCreated} nowych produktów w systemie.";
    }

    protected function getIntegrationPerformanceSummary(Carbon $startDate, Carbon $endDate): string
    {
        return "Podsumowanie wydajności integracji za okres " . $startDate->format('Y-m-d') . " - " . $endDate->format('Y-m-d');
    }

    // Additional helper methods would be implemented here
    protected function getDatabasePerformanceData(Carbon $startDate, Carbon $endDate): array { return []; }
    protected function getErrorRatesData(Carbon $startDate, Carbon $endDate): array { return []; }
    protected function getResourceUtilizationData(Carbon $startDate, Carbon $endDate): array { return []; }
    protected function getUserProductivityData(Carbon $startDate, Carbon $endDate): array { return []; }
    protected function getDataQualityData(): array { return []; }
    protected function getCategoryDistributionData(): array { return []; }
    protected function getPrestaShopPerformanceData(Carbon $startDate, Carbon $endDate): array { return []; }
    protected function getERPPerformanceData(Carbon $startDate, Carbon $endDate): array { return []; }
    protected function getSyncStatisticsData(Carbon $startDate, Carbon $endDate): array { return []; }
    protected function getAPIUsageData(Carbon $startDate, Carbon $endDate): array { return []; }
}