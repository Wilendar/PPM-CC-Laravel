<?php

namespace App\Providers;

use App\Models\BugReport;
use App\Policies\BugReportPolicy;
use App\Policies\RolePolicy;
use App\Policies\PermissionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        BugReport::class => BugReportPolicy::class,
        Role::class => RolePolicy::class,
        Permission::class => PermissionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Grant all abilities to Admin role
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('Admin') ? true : null;
        });

        // Explicit gates used przez komponenty Livewire
        $abilities = [
            'admin.settings.manage',
            'admin.erp.view', 'admin.erp.create', 'admin.erp.edit', 'admin.erp.delete',
            'admin.shops.view', 'admin.shops.create', 'admin.shops.edit', 'admin.shops.delete',
        ];

        foreach ($abilities as $ability) {
            Gate::define($ability, function (User $user) use ($ability) {
                // Spatie Permission integration (fallback gdy nie Admin)
                return method_exists($user, 'hasPermissionTo')
                    ? $user->hasPermissionTo($ability)
                    : false;
            });
        }
    }
}

