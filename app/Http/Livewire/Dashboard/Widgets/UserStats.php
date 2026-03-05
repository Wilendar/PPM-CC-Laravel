<?php

namespace App\Http\Livewire\Dashboard\Widgets;

use Livewire\Component;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * UserStats Widget (Admin-only)
 *
 * Displays user statistics: total/active/online counts,
 * role breakdown with color coding, and new registrations.
 * Cached for 5 minutes with manual refresh option.
 */
class UserStats extends Component
{
    public array $metrics = [];
    public array $roleBreakdown = [];

    /**
     * Role colors matching PPM design system.
     * Keys use DB role names (MEMORY.md: role name mapping).
     */
    protected array $roleColors = [
        'Admin' => '#EF4444',
        'Manager' => '#F97316',
        'Edytor' => '#10B981',
        'Magazyn' => '#3B82F6',
        'Handlowy' => '#8B5CF6',
        'Reklamacje' => '#06B6D4',
        'User' => '#6B7280',
    ];

    public function mount(): void
    {
        $this->loadStats();
    }

    public function loadStats(): void
    {
        $cacheKey = 'dashboard_user_stats';

        $data = Cache::remember($cacheKey, 300, function () {
            return $this->calculateStats();
        });

        $this->metrics = $data['metrics'];
        $this->roleBreakdown = $data['roleBreakdown'];
    }

    public function refreshStats(): void
    {
        Cache::forget('dashboard_user_stats');
        $this->loadStats();
    }

    protected function calculateStats(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $onlineNow = UserSession::active()->count();
        $newToday = User::whereDate('created_at', today())->count();
        $newThisWeek = User::where('created_at', '>=', now()->startOfWeek())->count();

        // Role breakdown query (uses Spatie model_has_roles pivot)
        $roles = User::where('is_active', true)
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_type', 'App\\Models\\User')
            ->select('roles.name', DB::raw('count(*) as count'))
            ->groupBy('roles.name')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'count' => $item->count,
                    'color' => $this->roleColors[$item->name] ?? '#9CA3AF',
                ];
            })
            ->toArray();

        return [
            'metrics' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'online_now' => $onlineNow,
                'new_today' => $newToday,
                'new_this_week' => $newThisWeek,
            ],
            'roleBreakdown' => $roles,
        ];
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.user-stats');
    }
}
