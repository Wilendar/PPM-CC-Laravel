<?php

namespace App\Http\Livewire\Admin\Dashboard;

use Livewire\Component;
use App\Models\User;
use App\Models\UserSession;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Category;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

/**
 * Admin Dashboard Widgets Component
 * 
 * FAZA C: Real-time dashboard components
 * 
 * Features:
 * - User registration trends chart (daily/weekly/monthly)
 * - Active users count z role breakdown pie chart
 * - Recent activity feed z avatar i timestamps
 * - System health indicators (DB connections, cache status)
 * - Permission usage analytics (most/least used permissions)
 * - Top active users this week
 * - Security alerts integration
 * - Performance metrics display
 * - Quick stats cards
 * - Interactive charts z drill-down
 */
class DashboardWidgets extends Component
{
    // ==========================================
    // WIDGET PROPERTIES
    // ==========================================

    public $refreshInterval = 30; // seconds
    public $autoRefresh = true;
    public $lastRefresh;
    
    // ==========================================
    // WIDGET VISIBILITY
    // ==========================================
    
    public $visibleWidgets = [
        'stats_cards' => true,
        'user_trends' => true,
        'active_users' => true,
        'recent_activity' => true,
        'system_health' => true,
        'permission_analytics' => true,
        'security_alerts' => true,
        'top_users' => true
    ];
    
    // ==========================================
    // DATA PROPERTIES
    // ==========================================
    
    public $statsData = [];
    public $userTrendsData = [];
    public $activeUsersData = [];
    public $recentActivityData = [];
    public $systemHealthData = [];
    public $permissionAnalyticsData = [];
    public $securityAlertsData = [];
    public $topUsersData = [];
    
    // ==========================================
    // UI STATE
    // ==========================================
    
    public $trendsPeriod = 'week'; // day, week, month
    public $activityLimit = 10;
    public $showWidgetSettings = false;
    public $loading = [];

    // ==========================================
    // COMPONENT LIFECYCLE
    // ==========================================

    public function mount()
    {
        $this->authorize('viewAny', 'admin_dashboard');
        
        $this->loadUserPreferences();
        $this->refreshAllWidgets();
        $this->lastRefresh = now();
    }

    protected function loadUserPreferences()
    {
        $user = auth()->user();
        $preferences = $user->getUIPreference('dashboard_widgets', []);
        
        if (!empty($preferences['visible_widgets'])) {
            $this->visibleWidgets = array_merge($this->visibleWidgets, $preferences['visible_widgets']);
        }
        
        if (!empty($preferences['trends_period'])) {
            $this->trendsPeriod = $preferences['trends_period'];
        }
        
        if (!empty($preferences['activity_limit'])) {
            $this->activityLimit = $preferences['activity_limit'];
        }
        
        if (isset($preferences['auto_refresh'])) {
            $this->autoRefresh = $preferences['auto_refresh'];
        }
    }

    protected function saveUserPreferences()
    {
        $user = auth()->user();
        
        $preferences = [
            'visible_widgets' => $this->visibleWidgets,
            'trends_period' => $this->trendsPeriod,
            'activity_limit' => $this->activityLimit,
            'auto_refresh' => $this->autoRefresh
        ];
        
        $user->updateUIPreference('dashboard_widgets', $preferences);
    }

    // ==========================================
    // REFRESH METHODS
    // ==========================================

    public function refreshAllWidgets()
    {
        if ($this->visibleWidgets['stats_cards']) {
            $this->refreshStatsCards();
        }
        
        if ($this->visibleWidgets['user_trends']) {
            $this->refreshUserTrends();
        }
        
        if ($this->visibleWidgets['active_users']) {
            $this->refreshActiveUsers();
        }
        
        if ($this->visibleWidgets['recent_activity']) {
            $this->refreshRecentActivity();
        }
        
        if ($this->visibleWidgets['system_health']) {
            $this->refreshSystemHealth();
        }
        
        if ($this->visibleWidgets['permission_analytics']) {
            $this->refreshPermissionAnalytics();
        }
        
        if ($this->visibleWidgets['security_alerts']) {
            $this->refreshSecurityAlerts();
        }
        
        if ($this->visibleWidgets['top_users']) {
            $this->refreshTopUsers();
        }
        
        $this->lastRefresh = now();
    }

    public function refreshWidget($widget)
    {
        $this->loading[$widget] = true;
        
        switch ($widget) {
            case 'stats_cards':
                $this->refreshStatsCards();
                break;
            case 'user_trends':
                $this->refreshUserTrends();
                break;
            case 'active_users':
                $this->refreshActiveUsers();
                break;
            case 'recent_activity':
                $this->refreshRecentActivity();
                break;
            case 'system_health':
                $this->refreshSystemHealth();
                break;
            case 'permission_analytics':
                $this->refreshPermissionAnalytics();
                break;
            case 'security_alerts':
                $this->refreshSecurityAlerts();
                break;
            case 'top_users':
                $this->refreshTopUsers();
                break;
        }
        
        $this->loading[$widget] = false;
    }

    // ==========================================
    // STATS CARDS WIDGET
    // ==========================================

    protected function refreshStatsCards()
    {
        $this->statsData = Cache::remember('dashboard.stats', 300, function () {
            return [
                'total_users' => [
                    'value' => User::count(),
                    'change' => $this->calculateUserGrowth(),
                    'trend' => 'up'
                ],
                'active_users' => [
                    'value' => User::where('is_active', true)->count(),
                    'percentage' => $this->calculateActiveUserPercentage(),
                    'trend' => 'up'
                ],
                'online_now' => [
                    'value' => UserSession::where('is_active', true)->count(),
                    'change' => $this->calculateOnlineUserChange(),
                    'trend' => 'up'
                ],
                'total_sessions' => [
                    'value' => UserSession::whereDate('created_at', today())->count(),
                    'change' => $this->calculateTodaySessionsChange(),
                    'trend' => 'up'
                ],
                'security_alerts' => [
                    'value' => $this->countSecurityAlerts(),
                    'severity' => $this->getHighestAlertSeverity(),
                    'trend' => 'neutral'
                ],
                'system_health' => [
                    'value' => $this->calculateSystemHealthScore(),
                    'status' => $this->getSystemHealthStatus(),
                    'trend' => 'up'
                ]
            ];
        });
    }

    protected function calculateUserGrowth()
    {
        $thisWeek = User::where('created_at', '>=', now()->startOfWeek())->count();
        $lastWeek = User::whereBetween('created_at', [
            now()->subWeek()->startOfWeek(),
            now()->subWeek()->endOfWeek()
        ])->count();
        
        if ($lastWeek == 0) return 0;
        return round((($thisWeek - $lastWeek) / $lastWeek) * 100, 1);
    }

    protected function calculateActiveUserPercentage()
    {
        $total = User::count();
        $active = User::where('is_active', true)->count();
        
        return $total > 0 ? round(($active / $total) * 100, 1) : 0;
    }

    protected function calculateOnlineUserChange()
    {
        $currentHour = UserSession::where('is_active', true)
            ->where('last_activity', '>=', now()->subHour())
            ->count();
        
        $previousHour = UserSession::where('is_active', true)
            ->whereBetween('last_activity', [
                now()->subHours(2),
                now()->subHour()
            ])
            ->count();
        
        if ($previousHour == 0) return $currentHour > 0 ? 100 : 0;
        return round((($currentHour - $previousHour) / $previousHour) * 100, 1);
    }

    protected function calculateTodaySessionsChange()
    {
        $today = UserSession::whereDate('created_at', today())->count();
        $yesterday = UserSession::whereDate('created_at', yesterday())->count();
        
        if ($yesterday == 0) return $today > 0 ? 100 : 0;
        return round((($today - $yesterday) / $yesterday) * 100, 1);
    }

    // ==========================================
    // USER TRENDS WIDGET
    // ==========================================

    protected function refreshUserTrends()
    {
        $this->userTrendsData = $this->getUserTrendsData($this->trendsPeriod);
    }

    protected function getUserTrendsData($period)
    {
        switch ($period) {
            case 'day':
                return $this->getHourlyUserTrends();
            case 'week':
                return $this->getDailyUserTrends(7);
            case 'month':
                return $this->getDailyUserTrends(30);
            default:
                return $this->getDailyUserTrends(7);
        }
    }

    protected function getHourlyUserTrends()
    {
        $data = [];
        $start = now()->startOfDay();
        
        for ($i = 0; $i < 24; $i++) {
            $hour = $start->copy()->addHours($i);
            $registrations = User::where('created_at', '>=', $hour)
                ->where('created_at', '<', $hour->copy()->addHour())
                ->count();
            
            $logins = UserSession::where('created_at', '>=', $hour)
                ->where('created_at', '<', $hour->copy()->addHour())
                ->count();
            
            $data[] = [
                'label' => $hour->format('H:i'),
                'registrations' => $registrations,
                'logins' => $logins,
                'timestamp' => $hour->timestamp
            ];
        }
        
        return $data;
    }

    protected function getDailyUserTrends($days)
    {
        $data = [];
        $start = now()->subDays($days - 1)->startOfDay();
        
        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            
            $registrations = User::whereDate('created_at', $date)->count();
            $logins = UserSession::whereDate('created_at', $date)->count();
            $activeUsers = User::whereHas('sessions', function ($query) use ($date) {
                $query->whereDate('created_at', $date);
            })->count();
            
            $data[] = [
                'label' => $date->format('d.m'),
                'registrations' => $registrations,
                'logins' => $logins,
                'active_users' => $activeUsers,
                'timestamp' => $date->timestamp
            ];
        }
        
        return $data;
    }

    public function changeTrendsPeriod($period)
    {
        $this->trendsPeriod = $period;
        $this->refreshUserTrends();
        $this->saveUserPreferences();
    }

    // ==========================================
    // ACTIVE USERS WIDGET
    // ==========================================

    protected function refreshActiveUsers()
    {
        $this->activeUsersData = Cache::remember('dashboard.active_users', 300, function () {
            $roleBreakdown = User::where('is_active', true)
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->select('roles.name', DB::raw('count(*) as count'))
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->groupBy('roles.name')
                ->get();
            
            $colors = [
                'Admin' => '#EF4444',
                'Manager' => '#F97316',
                'Editor' => '#10B981',
                'Warehouseman' => '#3B82F6',
                'Salesperson' => '#8B5CF6',
                'Claims' => '#06B6D4',
                'User' => '#6B7280'
            ];
            
            $chartData = $roleBreakdown->map(function ($item) use ($colors) {
                return [
                    'label' => $item->name,
                    'value' => $item->count,
                    'color' => $colors[$item->name] ?? '#9CA3AF',
                    'percentage' => round(($item->count / User::where('is_active', true)->count()) * 100, 1)
                ];
            })->toArray();
            
            return [
                'total' => User::where('is_active', true)->count(),
                'breakdown' => $chartData,
                'online_now' => UserSession::where('is_active', true)->count()
            ];
        });
    }

    // ==========================================
    // RECENT ACTIVITY WIDGET
    // ==========================================

    protected function refreshRecentActivity()
    {
        $this->recentActivityData = AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit($this->activityLimit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'user' => $log->user ? [
                        'name' => $log->user->full_name,
                        'avatar' => $log->user->avatar_url,
                        'role' => $log->user->getRoleNames()->first()
                    ] : [
                        'name' => 'System',
                        'avatar' => null,
                        'role' => null
                    ],
                    'action' => $log->action,
                    'model' => $log->model_type ? class_basename($log->model_type) : null,
                    'model_id' => $log->model_id,
                    'created_at' => $log->created_at,
                    'time_ago' => $log->created_at->diffForHumans(),
                    'ip_address' => $log->ip_address,
                    'description' => $this->formatActivityDescription($log)
                ];
            })
            ->toArray();
    }

    protected function formatActivityDescription($log)
    {
        $action = str_replace('_', ' ', $log->action);
        $model = $log->model_type ? class_basename($log->model_type) : 'system';
        
        $descriptions = [
            'login' => 'zalogował się do systemu',
            'logout' => 'wylogował się z systemu',
            'create' => "utworzył nowy rekord {$model}",
            'update' => "zaktualizował rekord {$model}",
            'delete' => "usunął rekord {$model}",
            'export' => "wyeksportował dane {$model}",
            'import' => "zaimportował dane {$model}",
            'bulk update' => "wykonał masową aktualizację {$model}",
            'bulk delete' => "wykonał masowe usuwanie {$model}",
            'force logout' => "wymusił wylogowanie użytkownika",
            'permission granted' => "przyznał uprawnienia",
            'role assigned' => "przypisał rolę użytkownikowi"
        ];
        
        return $descriptions[$action] ?? "{$action} - {$model}";
    }

    public function changeActivityLimit($limit)
    {
        $this->activityLimit = max(5, min(50, $limit));
        $this->refreshRecentActivity();
        $this->saveUserPreferences();
    }

    // ==========================================
    // SYSTEM HEALTH WIDGET
    // ==========================================

    protected function refreshSystemHealth()
    {
        $this->systemHealthData = [
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'sessions' => $this->checkSessionHealth(),
            'disk_space' => $this->checkDiskSpace(),
            'memory_usage' => $this->checkMemoryUsage(),
            'response_time' => $this->checkResponseTime()
        ];
    }

    protected function checkDatabaseHealth()
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $responseTime = (microtime(true) - $start) * 1000;
            
            return [
                'status' => 'healthy',
                'response_time' => round($responseTime, 2),
                'connections' => $this->getDatabaseConnections(),
                'message' => 'Database connection is healthy'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'response_time' => null,
                'connections' => 0,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }

    protected function checkCacheHealth()
    {
        try {
            $testKey = 'health_check_' . time();
            Cache::put($testKey, 'test_value', 10);
            $value = Cache::get($testKey);
            Cache::forget($testKey);
            
            return [
                'status' => $value === 'test_value' ? 'healthy' : 'warning',
                'driver' => config('cache.default'),
                'message' => $value === 'test_value' ? 'Cache is working' : 'Cache read/write issue'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'driver' => config('cache.default'),
                'message' => 'Cache error: ' . $e->getMessage()
            ];
        }
    }

    protected function checkSessionHealth()
    {
        $activeSessions = UserSession::where('is_active', true)->count();
        $totalSessions = UserSession::count();
        
        return [
            'status' => 'healthy',
            'active_sessions' => $activeSessions,
            'total_sessions' => $totalSessions,
            'driver' => config('session.driver'),
            'message' => "Sessions are healthy ({$activeSessions} active)"
        ];
    }

    protected function checkDiskSpace()
    {
        $freeBytes = disk_free_space('/');
        $totalBytes = disk_total_space('/');
        $usedPercentage = round((($totalBytes - $freeBytes) / $totalBytes) * 100, 1);
        
        $status = 'healthy';
        if ($usedPercentage > 90) {
            $status = 'error';
        } elseif ($usedPercentage > 75) {
            $status = 'warning';
        }
        
        return [
            'status' => $status,
            'used_percentage' => $usedPercentage,
            'free_space' => $this->formatBytes($freeBytes),
            'total_space' => $this->formatBytes($totalBytes),
            'message' => "Disk usage: {$usedPercentage}%"
        ];
    }

    protected function checkMemoryUsage()
    {
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        
        $usagePercentage = round(($memoryUsage / $memoryLimit) * 100, 1);
        
        $status = 'healthy';
        if ($usagePercentage > 90) {
            $status = 'error';
        } elseif ($usagePercentage > 75) {
            $status = 'warning';
        }
        
        return [
            'status' => $status,
            'current_usage' => $this->formatBytes($memoryUsage),
            'peak_usage' => $this->formatBytes($peakMemory),
            'memory_limit' => $this->formatBytes($memoryLimit),
            'usage_percentage' => $usagePercentage,
            'message' => "Memory usage: {$usagePercentage}%"
        ];
    }

    protected function checkResponseTime()
    {
        $start = microtime(true);
        
        // Perform a simple database query
        User::count();
        
        $responseTime = (microtime(true) - $start) * 1000;
        
        $status = 'healthy';
        if ($responseTime > 1000) {
            $status = 'error';
        } elseif ($responseTime > 500) {
            $status = 'warning';
        }
        
        return [
            'status' => $status,
            'response_time' => round($responseTime, 2),
            'message' => "Response time: " . round($responseTime, 2) . "ms"
        ];
    }

    // ==========================================
    // PERMISSION ANALYTICS WIDGET
    // ==========================================

    protected function refreshPermissionAnalytics()
    {
        $this->permissionAnalyticsData = Cache::remember('dashboard.permission_analytics', 600, function () {
            // Most used permissions (based on audit logs)
            $mostUsed = AuditLog::select('action', DB::raw('count(*) as usage_count'))
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('action')
                ->orderBy('usage_count', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'permission' => $item->action,
                        'usage_count' => $item->usage_count,
                        'formatted_name' => ucfirst(str_replace('_', ' ', $item->action))
                    ];
                });
            
            // Permission distribution across roles
            $rolePermissions = Role::with('permissions')->get()->map(function ($role) {
                return [
                    'role' => $role->name,
                    'permission_count' => $role->permissions->count(),
                    'users_count' => $role->users->count()
                ];
            });
            
            // Users with direct permissions
            $usersWithDirectPermissions = User::has('permissions')->count();
            $totalUsers = User::count();
            
            return [
                'most_used_permissions' => $mostUsed->toArray(),
                'role_distribution' => $rolePermissions->toArray(),
                'users_with_direct_permissions' => $usersWithDirectPermissions,
                'direct_permission_percentage' => $totalUsers > 0 ? round(($usersWithDirectPermissions / $totalUsers) * 100, 1) : 0,
                'total_permissions' => Permission::count(),
                'total_roles' => Role::count()
            ];
        });
    }

    // ==========================================
    // SECURITY ALERTS WIDGET
    // ==========================================

    protected function refreshSecurityAlerts()
    {
        $this->securityAlertsData = [
            'alerts' => $this->getSecurityAlerts(),
            'summary' => $this->getSecuritySummary()
        ];
    }

    protected function getSecurityAlerts()
    {
        $alerts = [];
        
        // Multiple session alerts
        $multipleSessionUsers = UserSession::select('user_id', DB::raw('count(*) as session_count'))
            ->where('is_active', true)
            ->groupBy('user_id')
            ->having('session_count', '>', 3)
            ->with('user')
            ->get();
        
        foreach ($multipleSessionUsers as $userSessions) {
            $user = User::find($userSessions->user_id);
            if ($user) {
                $alerts[] = [
                    'type' => 'multiple_sessions',
                    'severity' => $userSessions->session_count > 5 ? 'high' : 'medium',
                    'message' => "{$user->full_name} ma {$userSessions->session_count} aktywnych sesji",
                    'user' => $user->full_name,
                    'count' => $userSessions->session_count,
                    'created_at' => now()
                ];
            }
        }
        
        // Failed login attempts
        $failedLogins = AuditLog::where('action', 'login_failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->select('ip_address', DB::raw('count(*) as attempts'))
            ->groupBy('ip_address')
            ->having('attempts', '>', 5)
            ->get();
        
        foreach ($failedLogins as $failedLogin) {
            $alerts[] = [
                'type' => 'failed_logins',
                'severity' => 'high',
                'message' => "Wielokrotne nieudane logowania z IP: {$failedLogin->ip_address}",
                'ip_address' => $failedLogin->ip_address,
                'count' => $failedLogin->attempts,
                'created_at' => now()
            ];
        }
        
        // Suspicious activities during off-hours
        $offHourActivities = AuditLog::whereRaw('HOUR(created_at) < 6 OR HOUR(created_at) > 22')
            ->where('created_at', '>=', now()->subDays(7))
            ->whereNotNull('user_id')
            ->count();
        
        if ($offHourActivities > 10) {
            $alerts[] = [
                'type' => 'off_hours_activity',
                'severity' => 'medium',
                'message' => "Nietypowa aktywność poza godzinami pracy ({$offHourActivities} akcji)",
                'count' => $offHourActivities,
                'created_at' => now()
            ];
        }
        
        return collect($alerts)->sortByDesc('created_at')->take(5)->values()->toArray();
    }

    protected function getSecuritySummary()
    {
        return [
            'total_alerts' => count($this->securityAlertsData['alerts'] ?? []),
            'high_severity' => collect($this->securityAlertsData['alerts'] ?? [])->where('severity', 'high')->count(),
            'medium_severity' => collect($this->securityAlertsData['alerts'] ?? [])->where('severity', 'medium')->count(),
            'last_updated' => now()->format('H:i')
        ];
    }

    // ==========================================
    // TOP USERS WIDGET
    // ==========================================

    protected function refreshTopUsers()
    {
        $this->topUsersData = User::select('users.*', DB::raw('count(audit_logs.id) as activity_count'))
            ->leftJoin('audit_logs', 'users.id', '=', 'audit_logs.user_id')
            ->where('audit_logs.created_at', '>=', now()->subWeek())
            ->groupBy('users.id')
            ->orderBy('activity_count', 'desc')
            ->limit(8)
            ->with('roles')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'avatar' => $user->avatar_url,
                    'role' => $user->getRoleNames()->first(),
                    'activity_count' => $user->activity_count,
                    'last_login' => $user->last_login_at,
                    'is_online' => UserSession::where('user_id', $user->id)
                        ->where('is_active', true)
                        ->exists()
                ];
            })
            ->toArray();
    }

    // ==========================================
    // WIDGET SETTINGS
    // ==========================================

    public function toggleWidget($widget)
    {
        $this->visibleWidgets[$widget] = !$this->visibleWidgets[$widget];
        $this->saveUserPreferences();
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;
        $this->saveUserPreferences();
    }

    public function openWidgetSettings()
    {
        $this->showWidgetSettings = true;
    }

    public function closeWidgetSettings()
    {
        $this->showWidgetSettings = false;
    }

    // ==========================================
    // UTILITY METHODS
    // ==========================================

    protected function countSecurityAlerts()
    {
        // Quick count without full data load
        return collect($this->getSecurityAlerts())->count();
    }

    protected function getHighestAlertSeverity()
    {
        $alerts = $this->getSecurityAlerts();
        $severities = collect($alerts)->pluck('severity');
        
        if ($severities->contains('high')) return 'high';
        if ($severities->contains('medium')) return 'medium';
        return 'low';
    }

    protected function calculateSystemHealthScore()
    {
        // Simple health score calculation
        $health = $this->systemHealthData;
        $score = 100;
        
        foreach ($health as $check) {
            if ($check['status'] === 'error') {
                $score -= 20;
            } elseif ($check['status'] === 'warning') {
                $score -= 10;
            }
        }
        
        return max(0, $score);
    }

    protected function getSystemHealthStatus()
    {
        $score = $this->calculateSystemHealthScore();
        
        if ($score >= 90) return 'excellent';
        if ($score >= 70) return 'good';
        if ($score >= 50) return 'fair';
        return 'poor';
    }

    protected function getDatabaseConnections()
    {
        try {
            return DB::select("SHOW STATUS LIKE 'Threads_connected'")[0]->Value ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function formatBytes($size)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }

    protected function parseMemoryLimit($memoryLimit)
    {
        $memoryLimit = trim($memoryLimit);
        $last = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
        $memoryLimit = (int) $memoryLimit;
        
        switch ($last) {
            case 'g':
                $memoryLimit *= 1024;
            case 'm':
                $memoryLimit *= 1024;
            case 'k':
                $memoryLimit *= 1024;
        }
        
        return $memoryLimit;
    }

    // ==========================================
    // RENDER METHOD
    // ==========================================

    public function render()
    {
        return view('livewire.admin.dashboard.dashboard-widgets')->layout('layouts.app', [
            'title' => 'Dashboard Administratora - PPM'
        ]);
    }
}