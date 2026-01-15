<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Product\VariantManager;
use App\Services\Product\FeatureManager;
use App\Services\CompatibilityManager;
use App\Services\CompatibilityVehicleService;
use App\Services\CompatibilityBulkService;
use App\Services\CompatibilityCacheService;
use App\Services\VisualEditor\BlockRegistry;
use App\Services\VisualEditor\BlockRenderer;
use App\Services\VisualEditor\CssDeploymentService;
use App\Services\VisualEditor\DescriptionRenderer;
use App\Services\VisualEditor\StylesetManager;

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

        // Visual Editor Services (ETAP_07f - Visual Description Editor)
        $this->app->singleton(StylesetManager::class);

        $this->app->singleton(BlockRegistry::class, function ($app) {
            $registry = new BlockRegistry();
            $registry->discoverBlocks();
            return $registry;
        });

        $this->app->singleton(BlockRenderer::class, function ($app) {
            return new BlockRenderer(
                $app->make(BlockRegistry::class),
                $app->make(StylesetManager::class)
            );
        });

        // Description Renderer (ETAP_07f Faza 8 - Rendering i Export)
        $this->app->singleton(DescriptionRenderer::class, function ($app) {
            return new DescriptionRenderer(
                $app->make(BlockRegistry::class),
                $app->make(StylesetManager::class)
            );
        });

        // CSS Deployment Service (ETAP_07f Faza 8.4)
        $this->app->singleton(CssDeploymentService::class, function ($app) {
            return new CssDeploymentService(
                $app->make(StylesetManager::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}