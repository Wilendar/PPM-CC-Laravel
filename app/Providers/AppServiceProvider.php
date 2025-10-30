<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Product\VariantManager;
use App\Services\Product\FeatureManager;
use App\Services\CompatibilityManager;
use App\Services\CompatibilityVehicleService;
use App\Services\CompatibilityBulkService;
use App\Services\CompatibilityCacheService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Product Services (ETAP_05a FAZA 3 - Services Layer)
        $this->app->singleton(VariantManager::class);
        $this->app->singleton(FeatureManager::class);

        // Compatibility Services (ETAP_05a FAZA 3 - Extended)
        $this->app->singleton(CompatibilityVehicleService::class);
        $this->app->singleton(CompatibilityBulkService::class);
        $this->app->singleton(CompatibilityCacheService::class);
        $this->app->singleton(CompatibilityManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}