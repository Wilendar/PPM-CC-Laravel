<?php

namespace App\Providers;

use App\Models\ERPConnection;
use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Models\ProductErpData;
use App\Models\ProductMedia;
use App\Models\ProductPrice;
use App\Models\ProductShopData;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Observers\ProductStatusCacheObserver;
use App\Services\CompatibilityBulkService;
use App\Services\CompatibilityCacheService;
use App\Services\CompatibilityManager;
use App\Services\CompatibilityVehicleService;
use App\Services\Permissions\PermissionModuleLoader;
use App\Services\Product\FeatureManager;
use App\Services\Product\ProductStatusAggregator;
use App\Services\Product\VariantManager;
use Illuminate\Support\ServiceProvider;

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

        // Product Status Aggregator (Product List status monitoring)
        $this->app->singleton(ProductStatusAggregator::class);

        // Compatibility Services (ETAP_05a FAZA 3 - Extended)
        $this->app->singleton(CompatibilityVehicleService::class);
        $this->app->singleton(CompatibilityBulkService::class);
        $this->app->singleton(CompatibilityCacheService::class);
        $this->app->singleton(CompatibilityManager::class);

        // Permission Module Loader (Modular Permissions Architecture)
        $this->app->singleton(PermissionModuleLoader::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerProductStatusCacheObservers();
    }

    /**
     * Register observers for product status cache invalidation
     */
    private function registerProductStatusCacheObservers(): void
    {
        $observer = new ProductStatusCacheObserver();

        Product::saved(fn(Product $p) => $observer->productSaved($p));
        Product::deleted(fn(Product $p) => $observer->productDeleted($p));

        ProductShopData::saved(fn(ProductShopData $d) => $observer->shopDataSaved($d));
        ProductErpData::saved(fn(ProductErpData $d) => $observer->erpDataSaved($d));
        ProductPrice::saved(fn(ProductPrice $p) => $observer->priceSaved($p));
        ProductStock::saved(fn(ProductStock $s) => $observer->stockSaved($s));
        ProductMedia::saved(fn(ProductMedia $m) => $observer->mediaSaved($m));
        ProductVariant::saved(fn(ProductVariant $v) => $observer->variantSaved($v));

        // INTEGRATION_LABELS.md: Invalidate cache when shop/ERP labels change
        PrestaShopShop::updated(fn(PrestaShopShop $s) => $observer->shopUpdated($s));
        ERPConnection::updated(fn(ERPConnection $e) => $observer->erpConnectionUpdated($e));
    }
}