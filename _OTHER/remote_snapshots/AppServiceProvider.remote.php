<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use App\Http\Livewire\Dashboard\AdminDashboard;
use App\Http\Livewire\Admin\Settings\SystemSettings;
use App\Http\Livewire\Admin\Backup\BackupManager;
use App\Http\Livewire\Admin\Maintenance\DatabaseMaintenance;
use App\Http\Livewire\Admin\Shops\ShopManager;
use App\Http\Livewire\Admin\ERP\ERPManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Map Livewire aliases for components outside default namespace
        Livewire::component('app.http.livewire.dashboard.admin-dashboard', AdminDashboard::class);
        Livewire::component('app.http.livewire.admin.settings.system-settings', SystemSettings::class);
        Livewire::component('app.http.livewire.admin.backup.backup-manager', BackupManager::class);
        Livewire::component('app.http.livewire.admin.maintenance.database-maintenance', DatabaseMaintenance::class);
        Livewire::component('app.http.livewire.admin.shops.shop-manager', ShopManager::class);
        Livewire::component('app.http.livewire.admin.erp.e-r-p-manager', ERPManager::class);
    }
}
