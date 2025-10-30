<?php

namespace App\Http\Livewire\Dashboard;

use Livewire\Component;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductStock;
use App\Models\OAuthAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

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
    ];

    public function mount()
    {
        // Check admin authorization (support canonical 'Admin' role name)
        if (!auth()->user() || !auth()->user()->hasAnyRole(['Admin', 'admin'])) {
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
            'systemHealth' => $this->getSystemHealthStatus()
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
            
            // Notify browser (Livewire v3)
            $this->dispatch('dashboard-refreshed');
            
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
                'active_sessions' => $this->getActiveSessionsCount()
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
        $activeToday = User::whereDate('last_login_at', Carbon::today())->count();
        $activeYesterday = User::whereDate('last_login_at', Carbon::yesterday())->count();
        
        return [
            'today' => $activeToday,
            'yesterday' => $activeYesterday,
            'trend' => $activeToday - $activeYesterday,
            'total_users' => User::count()
        ];
    }

    protected function getIntegrationStatus()
    {
        // Mock integration status - to be implemented with actual integrations
        return [
            'prestashop' => ['status' => 'healthy', 'last_sync' => now()->subMinutes(15)],
            'erp' => ['status' => 'warning', 'last_sync' => now()->subHour()],
            'oauth' => ['status' => 'healthy', 'last_check' => now()->subMinutes(5)]
        ];
    }

    protected function getRecentActivityCount()
    {
        // Placeholder until OAuthAuditLog model is available
        return 0;
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
        // Mock data for integration sync statistics
        return [
            'prestashop' => ['successful' => 145, 'failed' => 3, 'pending' => 12],
            'baselinker' => ['successful' => 89, 'failed' => 1, 'pending' => 5],
            'subiekt' => ['successful' => 67, 'failed' => 0, 'pending' => 8]
        ];
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
        $memoryLimit = $this->parseSize(ini_get('memory_limit'));
        
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
        // Placeholder until media relations are implemented
        return 0;
    }

    protected function getPriceInconsistencies()
    {
        // Placeholder until pricing schema is finalized
        return 0;
    }

protected function getIntegrationConflicts()
    {
        // Placeholder for integration conflicts metric
        return 0;
    }

    protected function getRecentActivity()
    {
        return collect([]);
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
    }

    protected function saveWidgetPreferences()
    {
        auth()->user()->update([
            'dashboard_widget_preferences' => [
                'performance' => $this->showPerformanceWidget,
                'metrics' => $this->showMetricsWidget,
                'charts' => $this->showChartsWidget,
                'business' => $this->showBusinessWidget
            ]
        ]);
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

