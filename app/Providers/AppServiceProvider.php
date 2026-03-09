<?php

namespace App\Providers;

use App\Models\ERPConnection;
use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Models\ProductErpData;
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
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Config;
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
        // Sync MySQL session timezone with PHP APP_TIMEZONE (handles DST automatically)
        DB::statement("SET time_zone = '" . now()->format('P') . "'");

        $this->registerProductStatusCacheObservers();
        $this->registerOAuthRateLimiters();
        $this->registerFeedRateLimiters();
        $this->registerSocialiteProviders();
        $this->applyDatabaseMailConfig();
    }

    /**
     * Register feed access rate limiters (per-token instead of per-IP).
     */
    private function registerFeedRateLimiters(): void
    {
        // Feed view: 30 requests per minute per token
        RateLimiter::for('feed-access', function ($request) {
            $token = $request->route('token');
            return Limit::perMinute(30)
                ->by('feed:' . ($token ?? $request->ip()))
                ->response(function () {
                    return response('Too Many Requests', 429)
                        ->header('Retry-After', '60');
                });
        });

        // Feed download: 10 requests per minute per token
        RateLimiter::for('feed-download', function ($request) {
            $token = $request->route('token');
            return Limit::perMinute(10)
                ->by('feed-dl:' . ($token ?? $request->ip()))
                ->response(function () {
                    return response('Too Many Requests', 429)
                        ->header('Retry-After', '60');
                });
        });
    }

    /**
     * Register OAuth rate limiters
     */
    private function registerOAuthRateLimiters(): void
    {
        RateLimiter::for('oauth-redirect', function ($request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('oauth-callback', function ($request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        RateLimiter::for('oauth-verify', function ($request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('oauth-link', function ($request) {
            return Limit::perMinute(5)->by(optional($request->user())->id ?: $request->ip());
        });

        RateLimiter::for('oauth-unlink', function ($request) {
            return Limit::perMinute(3)->by(optional($request->user())->id ?: $request->ip());
        });

        RateLimiter::for('oauth-revoke', function ($request) {
            return Limit::perMinute(2)->by(optional($request->user())->id ?: $request->ip());
        });
    }

    /**
     * Register third-party Socialite providers
     */
    private function registerSocialiteProviders(): void
    {
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('microsoft', \SocialiteProviders\Microsoft\Provider::class);
        });
    }

    /**
     * Override Laravel mail config with DB settings from System Settings panel.
     * Falls back to .env values if no DB settings are configured.
     */
    private function applyDatabaseMailConfig(): void
    {
        try {
            $smtpHost = \App\Models\SystemSetting::get('smtp_host');

            if (empty($smtpHost)) {
                return;
            }

            // Switch driver from log/array to smtp when DB settings exist
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $smtpHost);

            $port = \App\Models\SystemSetting::get('smtp_port');
            if ($port) {
                Config::set('mail.mailers.smtp.port', (int) $port);
            }

            $username = \App\Models\SystemSetting::get('smtp_username');
            if ($username) {
                Config::set('mail.mailers.smtp.username', $username);
            }

            $password = \App\Models\SystemSetting::get('smtp_password');
            if ($password) {
                Config::set('mail.mailers.smtp.password', $password);
            }

            $encryption = \App\Models\SystemSetting::get('smtp_encryption', 'tls');
            Config::set('mail.mailers.smtp.encryption', $encryption);

            $fromEmail = \App\Models\SystemSetting::get('from_email');
            if ($fromEmail) {
                Config::set('mail.from.address', $fromEmail);
            }

            $fromName = \App\Models\SystemSetting::get('from_name');
            if ($fromName) {
                Config::set('mail.from.name', $fromName);
            }
        } catch (\Exception $e) {
            // DB not available (e.g. during migrations) - use .env defaults
        }
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
        // ProductMedia observer disabled - model not yet implemented
        ProductVariant::saved(fn(ProductVariant $v) => $observer->variantSaved($v));

        // INTEGRATION_LABELS.md: Invalidate cache when shop/ERP labels change
        PrestaShopShop::updated(fn(PrestaShopShop $s) => $observer->shopUpdated($s));
        ERPConnection::updated(fn(ERPConnection $e) => $observer->erpConnectionUpdated($e));
    }
}