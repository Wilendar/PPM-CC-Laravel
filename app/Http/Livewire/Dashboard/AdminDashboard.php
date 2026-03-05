<?php

namespace App\Http\Livewire\Dashboard;

use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AdminDashboard extends Component
{
    use AuthorizesRequests;

    public string $userRole = 'User';
    public array $visibleWidgets = [];

    public function mount()
    {
        $this->authorize('dashboard.read');
        $this->userRole = $this->detectUserRole();
        $this->visibleWidgets = $this->resolveWidgets();
    }

    private function detectUserRole(): string
    {
        $user = auth()->user();
        if ($user && method_exists($user, 'getRoleNames')) {
            $roles = $user->getRoleNames();
            if ($roles->isNotEmpty()) {
                return $roles->first();
            }
        }
        return 'User';
    }

    private function resolveWidgets(): array
    {
        $role = $this->userRole;
        $isAdmin = in_array($role, ['Admin', 'Manager']);

        // User-level widgets (all roles)
        $widgets = [
            ['component' => 'dashboard.widgets.welcome-card', 'span' => 2],
            ['component' => 'logo', 'span' => 2],
            ['component' => 'dashboard.widgets.quick-links', 'span' => 4],
            ['component' => 'dashboard.widgets.business-kpi', 'span' => 4],
            ['component' => 'dashboard.widgets.system-messages', 'span' => 2],
            ['component' => 'dashboard.widgets.my-bug-reports', 'span' => 2],
            ['component' => 'dashboard.widgets.products-attention', 'span' => 2],
            ['component' => 'dashboard.widgets.user-activity', 'span' => 2],
            ['component' => 'dashboard.widgets.login-history', 'span' => 2],
        ];

        // Admin-only widgets
        if ($isAdmin) {
            $widgets[] = ['component' => 'divider', 'label' => 'Administracja'];
            $widgets[] = ['component' => 'dashboard.widgets.sync-jobs-stats', 'span' => 2];
            $widgets[] = ['component' => 'dashboard.widgets.system-health', 'span' => 2];
            $widgets[] = ['component' => 'dashboard.widgets.user-stats', 'span' => 2];
            $widgets[] = ['component' => 'dashboard.widgets.security-alerts', 'span' => 2];
        }

        return $widgets;
    }

    public function render()
    {
        return view('livewire.dashboard.admin-dashboard')
            ->layout('layouts.admin', [
                'title' => 'Dashboard - PPM',
                'breadcrumb' => 'Dashboard'
            ]);
    }
}
