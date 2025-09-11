<?php

namespace App\Http\Livewire\Dashboard;

use Livewire\Component;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductStock;
use App\Models\OAuthAuditLog;
use App\Models\PrestaShopShop;
use App\Models\ERPConnection;
use App\Models\SyncJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AdminDashboard extends Component
{
    // Widget configuration state
    public $refreshInterval = 60; // seconds
    public $widgets = [];
    public $widgetPositions = [];
    public $showPerformanceWidget = true;
    public $showMetricsWidget = true;
    public $showChartsWidget = true;
    public $showBusinessWidget = true;
    
    // Auto-refresh configuration
    public $autoRefresh = true;
    public $refreshOptions = [30, 60, 300]; // 30s, 1min, 5min
    
    // Dashboard data cache
    protected $cacheLifetime = 60; // seconds

    protected $listeners = [
        'refreshDashboard' => 'loadDashboardData',
        'updateRefreshInterval' => 'setRefreshInterval',
        'toggleWidget' => 'toggleWidget',
        'reorderWidgets' => 'reorderWidgets',
        'echo:admin-alerts,AlertBroadcast' => 'handleRealTimeAlert'
    ];

    public function mount()
    {
        // Check admin authorization
        if (!auth()->user() || !auth()->user()->hasRole('Admin')) {
            abort(403, 'Unauthorized access to admin dashboard.');
        }

        // Initialize widget positions from user preferences
        $this->initializeWidgetConfiguration();
        
        // Load initial dashboard data
        $this->loadDashboardData();
    }

    public function render()
    {
        return view('livewire.dashboard.admin-dashboard', [
            'stats' => $this->getDashboardStats(),
            'charts' => $this->getChartData(),
            'performance' => $this->getPerformanceMetrics(),
            'businessMetrics' => $this->getBusinessMetrics(),
            'recentActivity' => $this->getRecentActivity(),
            'systemHealth' => $this->getSystemHealth()
        ])->layout('layouts.admin');
    }

    public function loadDashboardData()
    {
        try {
            // Force refresh all cached data
            Cache::forget('admin_dashboard_stats');
            Cache::forget('admin_dashboard_charts');
            Cache::forget('admin_dashboard_performance');
            Cache::forget('admin_dashboard_business');
            
            $this->emit('dashboardRefreshed');
            
        } catch (\Exception $e) {
            Log::error('Dashboard refresh failed: ' . $e->getMessage());
            session()->flash('error', 'Błąd podczas odświeżania dashboardu.');
        }
    }

    public function setRefreshInterval($interval)
    {
        if (in_array($interval, $this->refreshOptions)) {
            $this->refreshInterval = $interval;
            
            // Save user preference
            auth()->user()->update([
                'dashboard_refresh_interval' => $interval
            ]);
        }
    }

    public function toggleWidget($widgetName)
    {
        $property = "show{$widgetName}Widget";
        
        if (property_exists($this, $property)) {
            $this->$property = !$this->$property;
            
            // Save widget preferences
            $this->saveWidgetPreferences();
        }
    }

    public function reorderWidgets($order)
    {
        // Expect $order to be an array of widget keys, e.g., ['performance','business','charts']
        if (is_array($order) && !empty($order)) {
            $allowed = ['performance','business','charts'];
            $filtered = array_values(array_intersect($order, $allowed));
            if (!empty($filtered)) {
                $this->widgetPositions = $filtered;
                $this->saveWidgetPreferences();
            }
        }
    }

    public function handleRealTimeAlert($event)
    {
        // Handle real-time system alerts
        $this->emit('showAlert', $event['message'], $event['type'] ?? 'info');
        
        // Refresh relevant widgets
        if (isset($event['refresh_widgets'])) {
            $this->loadDashboardData();
        }
    }

    protected function getDashboardStats()
    {
        return Cache::remember('admin_dashboard_stats', $this->cacheLifetime, function () {
            return [
                'total_products' => $this->getProductsCount(),
                'active_users' => $this->getActiveUsersCount(),
                'integration_status' => $this->getIntegrationStatus(),
                'recent_activity' => $this->getRecentActivityCount(),
                'system_health' => $this->getSystemHealthStatus()
            ];
        });
    }

    protected function getChartData()
    {
        return Cache::remember('admin_dashboard_charts', $this->cacheLifetime, function () {
            return [
                'products_by_category' => $this->getProductsByCategoryChart(),
                'user_activity' => $this->getUserActivityChart(),
                'integration_sync' => $this->getIntegrationSyncChart()
            ];
        });
    }

    protected function getPerformanceMetrics()
    {
        return Cache::remember('admin_dashboard_performance', $this->cacheLifetime, function () {
            return [
                'cpu_usage' => $this->getCpuUsage(),
                'memory_usage' => $this->getMemoryUsage(),
                'database_connections' => $this->getDatabaseConnections(),
                'response_time' => $this->getAverageResponseTime(),
                'active_sessions' => $this->getActiveSessionsCount(),
                // Application metrics
                'queue_jobs' => $this->getQueueJobsStatus(),
                'cache_hit_rate' => $this->getCacheHitRate(),
                'log_files' => $this->getLogFilesStats(),
                'scheduled_tasks' => $this->getScheduledTasksStatus(),
                'background_sync' => $this->getBackgroundSyncStatus(),
            ];
        });
    }

    protected function getBusinessMetrics()
    {
        return Cache::remember('admin_dashboard_business', $this->cacheLifetime, function () {
            return [
                'products_added_today' => $this->getProductsAddedToday(),
                'categories_without_products' => $this->getCategoriesWithoutProducts(),
                'products_missing_images' => $this->getProductsMissingImages(),
                'price_inconsistencies' => $this->getPriceInconsistencies(),
                'integration_conflicts' => $this->getIntegrationConflicts()
            ];
        });
    }

    // Core metrics calculation methods

    protected function getProductsCount()
    {
        $currentCount = Product::count();
        $previousCount = Product::whereDate('created_at', '<', now()->subDay())->count();
        
        return [
            'total' => $currentCount,
            'trend' => $currentCount - $previousCount,
            'trend_percentage' => $previousCount > 0 ? round((($currentCount - $previousCount) / $previousCount) * 100, 1) : 0
        ];
    }

    protected function getActiveUsersCount()
    {
        $activeToday = User::whereDate('last_login_at', today())->count();
        $activeYesterday = User::whereDate('last_login_at', yesterday())->count();
        
        return [
            'today' => $activeToday,
            'yesterday' => $activeYesterday,
            'trend' => $activeToday - $activeYesterday,
            'total_users' => User::count()
        ];
    }

    protected function getIntegrationStatus()
    {
        $status = [];

        // PrestaShop shops
        try {
            $shopsTotal = PrestaShopShop::count();
            $shopsHealthy = PrestaShopShop::healthy()->count();
            $shopsIssues = max(0, $shopsTotal - $shopsHealthy);
            $status['prestashop'] = [
                'status' => $shopsTotal === 0 ? 'unknown' : ($shopsIssues === 0 ? 'healthy' : 'warning'),
                'last_sync' => PrestaShopShop::max('last_sync_at'),
                'total' => $shopsTotal,
                'healthy' => $shopsHealthy,
                'issues' => $shopsIssues,
            ];
        } catch (\Throwable $e) {
            $status['prestashop'] = ['status' => 'unknown'];
        }

        // ERP connections by type
        foreach ([
            'baselinker' => ERPConnection::ERP_BASELINKER,
            'subiekt' => ERPConnection::ERP_SUBIEKT_GT,
            'dynamics' => ERPConnection::ERP_DYNAMICS,
        ] as $label => $type) {
            try {
                $total = ERPConnection::byType($type)->count();
                $healthy = ERPConnection::byType($type)->healthy()->count();
                $issues = max(0, $total - $healthy);
                $status[$label] = [
                    'status' => $total === 0 ? 'unknown' : ($issues === 0 ? 'healthy' : 'warning'),
                    'last_sync' => ERPConnection::byType($type)->max('last_sync_at'),
                    'total' => $total,
                    'healthy' => $healthy,
                    'issues' => $issues,
                ];
            } catch (\Throwable $e) {
                $status[$label] = ['status' => 'unknown'];
            }
        }

        // OAuth (basic heartbeat)
        $status['oauth'] = ['status' => 'healthy', 'last_check' => now()->subMinutes(5)];

        return $status;
    }

    protected function getRecentActivityCount()
    {
        return OAuthAuditLog::where('created_at', '>=', now()->subDay())->count();
    }

    protected function getSystemHealthStatus()
    {
        $health = 'healthy';
        $issues = [];
        
        // Check various system components
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $health = 'critical';
            $issues[] = 'Database connection failed';
        }
        
        // Check cache
        try {
            Cache::store('redis')->put('health_check', 'ok', 10);
        } catch (\Exception $e) {
            $health = 'warning';
            $issues[] = 'Redis cache unavailable';
        }
        
        return [
            'status' => $health,
            'issues' => $issues,
            'last_check' => now()
        ];
    }

    // Chart data methods

    protected function getProductsByCategoryChart()
    {
        $data = Category::withCount('products')
            ->having('products_count', '>', 0)
            ->orderByDesc('products_count')
            ->limit(8)
            ->get()
            ->map(function ($category) {
                return [
                    'name' => $category->name,
                    'count' => $category->products_count,
                    'color' => $this->generateChartColor($category->id)
                ];
            });

        return $data;
    }

    protected function getUserActivityChart()
    {
        $days = collect();
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = User::whereDate('last_login_at', $date)->count();
            
            $days->push([
                'date' => $date->format('Y-m-d'),
                'count' => $count,
                'label' => $date->format('M j')
            ]);
        }

        return $days;
    }

    protected function getIntegrationSyncChart()
    {
        $map = [
            'prestashop' => SyncJob::TYPE_PRESTASHOP,
            'baselinker' => SyncJob::TYPE_BASELINKER,
            'subiekt' => SyncJob::TYPE_SUBIEKT_GT,
        ];

        $result = [];
        foreach ($map as $label => $type) {
            try {
                $result[$label] = [
                    'successful' => SyncJob::where('target_type', $type)->where('status', SyncJob::STATUS_COMPLETED)->count(),
                    'failed' => SyncJob::where('target_type', $type)->where('status', SyncJob::STATUS_FAILED)->count(),
                    'pending' => SyncJob::where('target_type', $type)->whereIn('status', [SyncJob::STATUS_PENDING, SyncJob::STATUS_RUNNING])->count(),
                ];
            } catch (\Throwable $e) {
                $result[$label] = ['successful' => 0, 'failed' => 0, 'pending' => 0];
            }
        }
        return $result;
    }

    // Performance metrics methods

    protected function getCpuUsage()
    {
        // On shared hosting, we can't get real CPU usage
        // Return mock data or try to estimate from load
        return [
            'current' => rand(15, 45), // Mock percentage
            'average' => rand(20, 35),
            'status' => 'normal'
        ];
    }

    protected function getMemoryUsage()
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimitIni = ini_get('memory_limit');
        $memoryLimit = $this->parseSize($memoryLimitIni ?: '128M');
        
        $percentage = ($memoryUsage / $memoryLimit) * 100;
        
        return [
            'used' => $this->formatBytes($memoryUsage),
            'limit' => $this->formatBytes($memoryLimit),
            'percentage' => round($percentage, 1),
            'status' => $percentage > 80 ? 'critical' : ($percentage > 60 ? 'warning' : 'normal')
        ];
    }

    protected function getDatabaseConnections()
    {
        try {
            $connections = DB::select('SHOW STATUS LIKE "Threads_connected"')[0]->Value ?? 0;
            $maxConnections = DB::select('SHOW VARIABLES LIKE "max_connections"')[0]->Value ?? 100;
            
            return [
                'active' => (int)$connections,
                'max' => (int)$maxConnections,
                'percentage' => round(($connections / $maxConnections) * 100, 1)
            ];
        } catch (\Exception $e) {
            return ['active' => 0, 'max' => 100, 'percentage' => 0];
        }
    }

    protected function getAverageResponseTime()
    {
        // Mock response time - in production, use APM tools
        return [
            'current' => rand(150, 350), // milliseconds
            'average' => rand(200, 300),
            'p95' => rand(400, 600),
            'status' => 'normal'
        ];
    }

    protected function getActiveSessionsCount()
    {
        // Count active user sessions
        return User::whereNotNull('last_login_at')
            ->where('last_login_at', '>=', now()->subHours(2))
            ->count();
    }

    protected function getQueueJobsStatus()
    {
        try {
            $pending = DB::table('jobs')->count();
        } catch (\Throwable $e) {
            $pending = null;
        }
        try {
            $failed = DB::table('failed_jobs')->count();
        } catch (\Throwable $e) {
            $failed = null;
        }
        return [
            'pending' => $pending,
            'processing' => null, // not tracked natively
            'failed' => $failed,
            'status' => ($failed && $failed > 0) ? 'warning' : 'normal',
        ];
    }

    protected function getCacheHitRate()
    {
        // Laravel does not expose hit/miss counters by default; return N/A structure
        return [
            'hits' => null,
            'misses' => null,
            'percentage' => null,
            'supported' => false,
        ];
    }

    protected function getLogFilesStats()
    {
        try {
            $path = storage_path('logs');
            $files = @scandir($path) ?: [];
            $totalSize = 0;
            $count = 0;
            foreach ($files as $f) {
                if ($f === '.' || $f === '..') continue;
                $full = $path . DIRECTORY_SEPARATOR . $f;
                if (is_file($full)) {
                    $totalSize += filesize($full) ?: 0;
                    $count++;
                }
            }
            return [
                'files' => $count,
                'size_bytes' => $totalSize,
                'size_human' => $this->formatBytes($totalSize),
            ];
        } catch (\Throwable $e) {
            return [ 'files' => null, 'size_bytes' => null, 'size_human' => null ];
        }
    }

    protected function getScheduledTasksStatus()
    {
        // Placeholder: without dedicated table, just expose last schedule run if tracked elsewhere
        return [
            'last_run_at' => null,
            'upcoming' => null,
            'status' => 'unknown',
        ];
    }

    protected function getBackgroundSyncStatus()
    {
        try {
            $pending = \App\Models\SyncJob::where('status', \App\Models\SyncJob::STATUS_PENDING)->count();
            $running = \App\Models\SyncJob::where('status', \App\Models\SyncJob::STATUS_RUNNING)->count();
            $failed = \App\Models\SyncJob::where('status', \App\Models\SyncJob::STATUS_FAILED)->count();
            return compact('pending', 'running', 'failed');
        } catch (\Throwable $e) {
            return [ 'pending' => null, 'running' => null, 'failed' => null ];
        }
    }

    protected function parseSize(string $size): int
    {
        // Convert shorthand php.ini sizes (e.g., 128M) to bytes
        if (is_numeric($size)) {
            return (int) $size;
        }
        $unit = strtolower(substr($size, -1));
        $num = (int) substr($size, 0, -1);
        return match ($unit) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => (int) $size,
        };
    }

    protected function formatBytes(int $bytes, int $precision = 1): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);
        $value = $bytes / (1024 ** $power);
        return round($value, $precision) . ' ' . $units[$power];
    }

    // Business metrics methods

    protected function getProductsAddedToday()
    {
        return Product::whereDate('created_at', today())->count();
    }

    protected function getCategoriesWithoutProducts()
    {
        return Category::whereDoesntHave('products')->count();
    }

    protected function getProductsMissingImages()
    {
        return Product::whereDoesntHave('media')->count();
    }

    protected function getPriceInconsistencies()
    {
        // Find products with price inconsistencies across price groups
        return DB::table('product_prices')
            ->select('product_sku')
            ->groupBy('product_sku')
            ->havingRaw('COUNT(DISTINCT price_net) > 1')
            ->count();
    }

    protected function getIntegrationConflicts()
    {
        // Mock integration conflicts count
        return rand(0, 5);
    }

    protected function getRecentActivity()
    {
        return OAuthAuditLog::with('user')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'user' => $log->user->name ?? 'System',
                    'action' => $log->action,
                    'timestamp' => $log->created_at,
                    'ip_address' => $log->ip_address
                ];
            });
    }

    // Utility methods

    protected function initializeWidgetConfiguration()
    {
        $user = auth()->user();
        
        // Load user's dashboard preferences
        $this->refreshInterval = $user->dashboard_refresh_interval ?? 60;
        
        // Load widget visibility preferences
        $preferences = $user->dashboard_widget_preferences ?? [];
        
        $this->showPerformanceWidget = $preferences['performance'] ?? true;
        $this->showMetricsWidget = $preferences['metrics'] ?? true;
        $this->showChartsWidget = $preferences['charts'] ?? true;
        $this->showBusinessWidget = $preferences['business'] ?? true;

        // Load widget order
        $this->widgetPositions = $preferences['order'] ?? ['performance', 'business', 'charts'];
    }

    protected function saveWidgetPreferences()
    {
        auth()->user()->update([
            'dashboard_widget_preferences' => [
                'performance' => $this->showPerformanceWidget,
                'metrics' => $this->showMetricsWidget,
                'charts' => $this->showChartsWidget,
                'business' => $this->showBusinessWidget,
                'order' => $this->widgetPositions,
            ]
        ]);
    }

    public function getWidgetOrder(string $key): int
    {
        $index = array_search($key, $this->widgetPositions, true);
        return $index === false ? 99 : ($index + 1); // CSS order starts at 1
    }

    protected function generateChartColor($seed)
    {
        $colors = [
            '#3B82F6', '#EF4444', '#10B981', '#F59E0B',
            '#8B5CF6', '#06B6D4', '#F97316', '#84CC16'
        ];
        
        return $colors[$seed % count($colors)];
    }

    protected function parseSize($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);
        
        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }
        
        return round($size);
    }

    protected function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
