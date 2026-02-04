<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

/**
 * PrestaShopShop Model
 * 
 * FAZA B: Shop & ERP Management - PrestaShop Shop Integration
 * 
 * Reprezentuje pojedynczy sklep PrestaShop podłączony do systemu PPM.
 * Każdy sklep ma swoją konfigurację API, ustawienia synchronizacji,
 * mapowanie pól i monitoring zdrowia połączenia.
 * 
 * Enterprise Features:
 * - Encrypted API credentials storage
 * - Real-time connection health monitoring
 * - Advanced sync configuration per shop
 * - Performance metrics tracking
 * - Multi-store support z dedicated settings
 * 
 * @property int $id
 * @property string $name
 * @property string $url
 * @property string $description
 * @property bool $is_active
 * @property string $api_key (encrypted)
 * @property string $api_version
 * @property bool $ssl_verify
 * @property int $timeout_seconds
 * @property int $rate_limit_per_minute
 * @property string $connection_status
 * @property Carbon $last_connection_test
 * @property float $last_response_time
 * @property int $consecutive_failures
 * @property string $last_error_message
 * @property string $prestashop_version
 * @property bool $version_compatible
 * @property array $supported_features
 * @property string $sync_frequency
 * @property array $sync_settings
 * @property bool $auto_sync_products
 * @property bool $auto_sync_categories
 * @property bool $auto_sync_prices
 * @property bool $auto_sync_stock
 * @property string $conflict_resolution
 * @property array $category_mappings
 * @property array $price_group_mappings
 * @property array $warehouse_mappings
 * @property array $custom_field_mappings
 * @property Carbon $last_sync_at
 * @property Carbon $next_scheduled_sync
 * @property int $products_synced
 * @property int $sync_success_count
 * @property int $sync_error_count
 * @property float $avg_response_time
 * @property int $api_quota_used
 * @property int $api_quota_limit
 * @property Carbon $quota_reset_at
 * @property array $notification_settings
 * @property bool $notify_on_errors
 * @property bool $notify_on_sync_complete
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property array|null $allowed_vehicle_brands Allowed vehicle brands (ETAP_05d: null=all, []=none, array=whitelist)
 * @property array|null $compatibility_settings Smart suggestions settings (ETAP_05d)
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VehicleCompatibility[] $vehicleCompatibilities
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\CompatibilitySuggestion[] $compatibilitySuggestions
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\CompatibilityBulkOperation[] $compatibilityBulkOperations
 */
class PrestaShopShop extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'prestashop_shops';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'url',
        'description',
        'is_active',
        'api_key',
        'api_version',
        'ssl_verify',
        'timeout_seconds',
        'rate_limit_per_minute',
        'connection_status',
        'last_connection_test',
        'last_response_time',
        'consecutive_failures',
        'last_error_message',
        'prestashop_version',
        'version_compatible',
        'supported_features',
        'sync_frequency',
        'sync_settings',
        'auto_sync_products',
        'auto_sync_categories',
        'auto_sync_prices',
        'auto_sync_stock',
        'conflict_resolution',
        'category_mappings',
        'price_group_mappings',
        'warehouse_mappings',
        'custom_field_mappings',
        'last_sync_at',
        'next_scheduled_sync',
        'products_synced',
        'sync_success_count',
        'sync_error_count',
        'avg_response_time',
        'api_quota_used',
        'api_quota_limit',
        'quota_reset_at',
        'notification_settings',
        'notify_on_errors',
        'notify_on_sync_complete',
        // Tax Rules Mapping (2025-11-14)
        'tax_rules_group_id_23',
        'tax_rules_group_id_8',
        'tax_rules_group_id_5',
        'tax_rules_group_id_0',
        'tax_rules_last_fetched_at',
        // ETAP_05d: Vehicle Compatibility Settings (2025-12-05)
        'allowed_vehicle_brands',
        'compatibility_settings',
        // ETAP_07f: CSS/JS Sync Configuration (2025-12-16)
        'custom_css_url',
        'custom_js_url',
        'css_last_fetched_at',
        'cached_custom_css',
        'cached_custom_js',
        'ftp_config',
        'css_last_deployed_at',
        'css_deploy_status',
        'css_deploy_message',
        // ETAP_07f_P3: Multi-file CSS cache (2025-12-17)
        'cached_theme_css',
        'theme_css_fetched_at',
        'css_asset_manifest',
        'css_manifest_fetched_at',
        'selected_css_modules',
        // ETAP_07f_P3.5: Multi-file CSS/JS configuration (2025-12-17)
        'css_files',
        'js_files',
        'files_scanned_at',
        // ETAP_10: Customizable label colors/icons
        'label_color',
        'label_icon',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'ssl_verify' => 'boolean',
        'version_compatible' => 'boolean',
        'auto_sync_products' => 'boolean',
        'auto_sync_categories' => 'boolean',
        'auto_sync_prices' => 'boolean',
        'auto_sync_stock' => 'boolean',
        'notify_on_errors' => 'boolean',
        'notify_on_sync_complete' => 'boolean',
        'supported_features' => 'array',
        'sync_settings' => 'array',
        'category_mappings' => 'array',
        'price_group_mappings' => 'array',
        'warehouse_mappings' => 'array',
        'custom_field_mappings' => 'array',
        'notification_settings' => 'array',
        'last_connection_test' => 'datetime',
        'last_sync_at' => 'datetime',
        'next_scheduled_sync' => 'datetime',
        'quota_reset_at' => 'datetime',
        'last_response_time' => 'decimal:3',
        'avg_response_time' => 'decimal:3',
        // ETAP_05d: Vehicle Compatibility Settings (2025-12-05)
        'allowed_vehicle_brands' => 'array',
        'compatibility_settings' => 'array',
        // ETAP_07f: CSS/JS Sync Configuration (2025-12-16)
        'ftp_config' => 'array',
        'css_last_fetched_at' => 'datetime',
        'css_last_deployed_at' => 'datetime',
        // ETAP_07f_P3: Multi-file CSS cache (2025-12-17)
        'css_asset_manifest' => 'array',
        'css_manifest_fetched_at' => 'datetime',
        'theme_css_fetched_at' => 'datetime',
        'selected_css_modules' => 'array',
        // ETAP_07f_P3.5: Multi-file CSS/JS configuration (2025-12-17)
        'css_files' => 'array',
        'js_files' => 'array',
        'files_scanned_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'api_key',
    ];

    /**
     * Connection Status Constants
     */
    public const CONNECTION_CONNECTED = 'connected';
    public const CONNECTION_DISCONNECTED = 'disconnected';
    public const CONNECTION_ERROR = 'error';
    public const CONNECTION_MAINTENANCE = 'maintenance';

    /**
     * Sync Frequency Constants
     */
    public const SYNC_REALTIME = 'realtime';
    public const SYNC_HOURLY = 'hourly';
    public const SYNC_DAILY = 'daily';
    public const SYNC_MANUAL = 'manual';

    /**
     * Conflict Resolution Constants
     */
    public const CONFLICT_PPM_WINS = 'ppm_wins';
    public const CONFLICT_PRESTASHOP_WINS = 'prestashop_wins';
    public const CONFLICT_MANUAL = 'manual';
    public const CONFLICT_NEWEST_WINS = 'newest_wins';

    /**
     * Get the integration mappings for this shop.
     */
    public function integrationMappings(): MorphMany
    {
        return $this->morphMany(IntegrationMapping::class, 'mappable')
            ->where('integration_type', 'prestashop')
            ->where('integration_identifier', $this->id);
    }

    /**
     * Get price group mappings for this shop.
     *
     * BUG FIX #13 (2025-11-13): Liczniki mapowań na liście sklepów
     * Provides Eloquent relation to price mappings table instead of JSON column
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function priceGroupMappings(): HasMany
    {
        return $this->hasMany(PrestaShopShopPriceMapping::class, 'prestashop_shop_id');
    }

    /**
     * Get warehouse mappings for this shop.
     *
     * BUG FIX #13 (2025-11-13): Liczniki mapowań na liście sklepów
     * Returns warehouses linked to this shop (type: 'shop_linked')
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function warehouseMappings(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'shop_id')
                    ->where('type', 'shop_linked');
    }

    /**
     * Get sync jobs for this shop.
     */
    public function syncJobs(): HasMany
    {
        return $this->hasMany(SyncJob::class, 'target_id', 'id')
            ->where('target_type', 'prestashop');
    }

    /**
     * Get integration logs for this shop.
     */
    public function integrationLogs(): HasMany
    {
        return $this->hasMany(IntegrationLog::class, 'integration_id', 'id')
            ->where('integration_type', 'prestashop');
    }

    /**
     * Get product shop data for this shop - FAZA 1.5: Multi-Store Synchronization System ✅ IMPLEMENTED
     *
     * Business Logic: All products with shop-specific data for this PrestaShop store
     * Performance: Indexed relationship dla multi-store operations
     * Sync Status: Access to per-product sync status and settings
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productShopData(): HasMany
    {
        return $this->hasMany(ProductShopData::class, 'shop_id', 'id')
                    ->orderBy('product_id', 'asc');
    }

    /**
     * Get published products for this shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function publishedProducts(): HasMany
    {
        return $this->productShopData()
                    ->where('is_published', true)
                    ->where('sync_status', '!=', 'disabled');
    }

    /**
     * Get products needing sync for this shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productsNeedingSync(): HasMany
    {
        return $this->productShopData()
                    ->whereIn('sync_status', ['pending', 'error', 'conflict']);
    }

    /**
     * Get the decrypted API key.
     * ETAP_07e FIX: Accept nullable values to prevent TypeError
     */
    protected function apiKey(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? decrypt($value) : null,
            set: fn (?string $value) => $value ? encrypt($value) : null,
        );
    }

    /**
     * Accessor: Get simplified version field (maps prestashop_version to version)
     *
     * CRITICAL FIX: PrestaShopClientFactory expects $shop->version but DB column is prestashop_version
     * This accessor provides compatibility layer without requiring DB changes
     *
     * @return string Version number ('8' or '9')
     */
    public function getVersionAttribute(): string
    {
        // Extract major version from prestashop_version (e.g., "1.7.8.11" -> "8", "9.0.0" -> "9")
        $version = $this->attributes['prestashop_version'] ?? '';

        if (empty($version)) {
            return '8'; // Default to version 8 if empty
        }

        // If version is already simplified ('8' or '9'), return as-is
        if (in_array($version, ['8', '9'])) {
            return $version;
        }

        // Extract first digit from version string (e.g., "1.7.8" -> "8")
        // PrestaShop 1.7.x = version 8, PrestaShop 9.x = version 9
        if (str_starts_with($version, '1.7') || str_starts_with($version, '1.8') || str_starts_with($version, '8')) {
            return '8';
        }

        if (str_starts_with($version, '9')) {
            return '9';
        }

        // Default fallback
        return '8';
    }

    /**
     * Get connection health status with color coding.
     */
    public function getConnectionHealthAttribute(): string
    {
        return match ($this->connection_status) {
            self::CONNECTION_CONNECTED => 'healthy',
            self::CONNECTION_DISCONNECTED => 'warning',
            self::CONNECTION_ERROR => 'danger',
            self::CONNECTION_MAINTENANCE => 'info',
            default => 'unknown'
        };
    }

    /**
     * Get connection health badge class.
     */
    public function getConnectionBadgeAttribute(): string
    {
        return match ($this->connection_status) {
            self::CONNECTION_CONNECTED => 'badge-success',
            self::CONNECTION_DISCONNECTED => 'badge-warning',
            self::CONNECTION_ERROR => 'badge-danger',
            self::CONNECTION_MAINTENANCE => 'badge-info',
            default => 'badge-secondary'
        };
    }

    /**
     * Get sync success rate percentage.
     */
    public function getSyncSuccessRateAttribute(): float
    {
        $total = $this->sync_success_count + $this->sync_error_count;
        
        if ($total === 0) {
            return 0.0;
        }
        
        return round(($this->sync_success_count / $total) * 100, 2);
    }

    /**
     * Check if shop is due for synchronization.
     */
    public function isDueForSync(): bool
    {
        if (!$this->is_active || $this->sync_frequency === self::SYNC_MANUAL) {
            return false;
        }

        if (!$this->next_scheduled_sync) {
            return true;
        }

        return Carbon::now()->gte($this->next_scheduled_sync);
    }

    /**
     * Check if shop connection is healthy.
     */
    public function isConnectionHealthy(): bool
    {
        return $this->connection_status === self::CONNECTION_CONNECTED 
            && $this->consecutive_failures === 0;
    }

    /**
     * Check if API quota is near limit.
     */
    public function isApiQuotaNearLimit(int $threshold = 80): bool
    {
        if (!$this->api_quota_limit) {
            return false;
        }

        $usage_percentage = ($this->api_quota_used / $this->api_quota_limit) * 100;
        
        return $usage_percentage >= $threshold;
    }

    /**
     * Calculate next scheduled sync time based on frequency.
     */
    public function calculateNextSyncTime(): ?Carbon
    {
        if ($this->sync_frequency === self::SYNC_MANUAL) {
            return null;
        }

        $base = $this->last_sync_at ?: Carbon::now();

        return match ($this->sync_frequency) {
            self::SYNC_REALTIME => Carbon::now(),
            self::SYNC_HOURLY => $base->addHour(),
            self::SYNC_DAILY => $base->addDay(),
            default => null
        };
    }

    /**
     * Update connection health metrics.
     */
    public function updateConnectionHealth(
        string $status,
        ?float $responseTime = null,
        ?string $errorMessage = null
    ): void {
        $this->connection_status = $status;
        $this->last_connection_test = Carbon::now();

        if ($responseTime !== null) {
            $this->last_response_time = $responseTime;
            
            // Update average response time (simple moving average)
            if ($this->avg_response_time) {
                $this->avg_response_time = ($this->avg_response_time + $responseTime) / 2;
            } else {
                $this->avg_response_time = $responseTime;
            }
        }

        if ($status === self::CONNECTION_CONNECTED) {
            $this->consecutive_failures = 0;
            $this->last_error_message = null;
        } else {
            $this->consecutive_failures++;
            $this->last_error_message = $errorMessage;
        }

        $this->save();
    }

    /**
     * Update sync statistics.
     */
    public function updateSyncStats(bool $success, int $itemsProcessed = 0): void
    {
        $this->last_sync_at = Carbon::now();
        
        if ($success) {
            $this->sync_success_count++;
            $this->products_synced += $itemsProcessed;
        } else {
            $this->sync_error_count++;
        }
        
        // Calculate next sync
        $this->next_scheduled_sync = $this->calculateNextSyncTime();
        
        $this->save();
    }

    /**
     * Update API quota usage.
     */
    public function updateApiUsage(int $usedQuota, ?Carbon $resetTime = null): void
    {
        $this->api_quota_used = $usedQuota;
        
        if ($resetTime) {
            $this->quota_reset_at = $resetTime;
        }
        
        $this->save();
    }

    /**
     * Reset consecutive failures counter.
     */
    public function resetFailures(): void
    {
        $this->consecutive_failures = 0;
        $this->last_error_message = null;
        $this->save();
    }

    /**
     * Scope to get only active shops.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get shops with healthy connections.
     */
    public function scopeHealthy($query)
    {
        return $query->where('connection_status', self::CONNECTION_CONNECTED)
                    ->where('consecutive_failures', 0);
    }

    /**
     * Scope to get shops due for sync.
     */
    public function scopeDueForSync($query)
    {
        return $query->where('is_active', true)
                    ->where('sync_frequency', '!=', self::SYNC_MANUAL)
                    ->where(function ($q) {
                        $q->whereNull('next_scheduled_sync')
                          ->orWhere('next_scheduled_sync', '<=', Carbon::now());
                    });
    }

    /**
     * Scope to get shops with connection issues.
     */
    public function scopeWithConnectionIssues($query)
    {
        return $query->where('connection_status', '!=', self::CONNECTION_CONNECTED)
                    ->orWhere('consecutive_failures', '>', 0);
    }

    // ==========================================
    // ETAP_05d: Vehicle Compatibility Relations & Methods
    // 2025-12-05: Per-shop compatibility system
    // ==========================================

    /**
     * Get vehicle compatibility records for this shop (ETAP_05d)
     */
    public function vehicleCompatibilities(): HasMany
    {
        return $this->hasMany(VehicleCompatibility::class, 'shop_id');
    }

    /**
     * Get compatibility suggestions for this shop (ETAP_05d)
     */
    public function compatibilitySuggestions(): HasMany
    {
        return $this->hasMany(CompatibilitySuggestion::class, 'shop_id');
    }

    /**
     * Get bulk operations for this shop (ETAP_05d)
     */
    public function compatibilityBulkOperations(): HasMany
    {
        return $this->hasMany(CompatibilityBulkOperation::class, 'shop_id');
    }

    /**
     * Get stylesets for this shop (ETAP_07f: Visual Description Editor)
     */
    public function stylesets(): HasMany
    {
        return $this->hasMany(ShopStyleset::class, 'shop_id');
    }

    /**
     * Get active styleset for this shop (ETAP_07f: Visual Description Editor)
     */
    public function activeStyleset(): HasOne
    {
        return $this->hasOne(ShopStyleset::class, 'shop_id')->where('is_active', true);
    }

    /**
     * Get product descriptions for this shop (ETAP_07f: Visual Description Editor)
     */
    public function productDescriptions(): HasMany
    {
        return $this->hasMany(ProductDescription::class, 'shop_id');
    }

    /**
     * Get block definitions for this shop (ETAP_07f_P3: Dedicated Blocks System)
     */
    public function blockDefinitions(): HasMany
    {
        return $this->hasMany(BlockDefinition::class, 'shop_id');
    }

    /**
     * Get active block definitions for this shop (ETAP_07f_P3)
     */
    public function activeBlockDefinitions(): HasMany
    {
        return $this->hasMany(BlockDefinition::class, 'shop_id')->where('is_active', true);
    }

    /**
     * Check if a vehicle brand is allowed for this shop (ETAP_05d)
     *
     * @param string $brand Vehicle brand name (e.g., "YCF", "KAYO")
     * @return bool True if brand allowed (null = all, [] = none, array = whitelist)
     */
    public function isVehicleBrandAllowed(string $brand): bool
    {
        // null = all brands allowed
        if ($this->allowed_vehicle_brands === null) {
            return true;
        }

        // empty array = no brands allowed
        if (empty($this->allowed_vehicle_brands)) {
            return false;
        }

        // array = whitelist
        return in_array($brand, $this->allowed_vehicle_brands, true);
    }

    /**
     * Get compatibility setting value (ETAP_05d)
     *
     * @param string $key Setting key
     * @param mixed $default Default value
     * @return mixed Setting value or default
     */
    public function getCompatibilitySetting(string $key, mixed $default = null): mixed
    {
        return $this->compatibility_settings[$key] ?? $default;
    }

    /**
     * Check if smart suggestions are enabled (ETAP_05d)
     */
    public function hasSmartSuggestionsEnabled(): bool
    {
        return $this->getCompatibilitySetting('enable_smart_suggestions', true);
    }

    /**
     * Check if auto-apply suggestions is enabled (ETAP_05d)
     */
    public function hasAutoApplySuggestionsEnabled(): bool
    {
        return $this->getCompatibilitySetting('auto_apply_suggestions', false);
    }

    /**
     * Get minimum confidence score for suggestions (ETAP_05d)
     */
    public function getMinConfidenceScore(): float
    {
        return (float) $this->getCompatibilitySetting('min_confidence_score', 0.50);
    }

    // ==========================================
    // COMPUTED PROPERTIES (Real-time from sync_jobs)
    // 2025-11-07: Fix outdated statistics columns
    // ==========================================

    /**
     * Get last successful sync time (from sync_jobs)
     * Overrides outdated last_sync_at column with real-time data
     */
    public function getLastSyncAtComputedAttribute(): ?Carbon
    {
        $lastSync = $this->syncJobs()
            ->where('status', SyncJob::STATUS_COMPLETED)
            ->latest('completed_at')
            ->first();

        return $lastSync ? $lastSync->completed_at : null;
    }

    /**
     * Get successful syncs count (from sync_jobs)
     * Overrides outdated sync_success_count column with real-time data
     */
    public function getSyncSuccessCountComputedAttribute(): int
    {
        return $this->syncJobs()
            ->where('status', SyncJob::STATUS_COMPLETED)
            ->count();
    }

    /**
     * Get failed syncs count (from sync_jobs)
     * Overrides outdated sync_error_count column with real-time data
     */
    public function getSyncErrorCountComputedAttribute(): int
    {
        return $this->syncJobs()
            ->whereIn('status', [SyncJob::STATUS_FAILED, SyncJob::STATUS_TIMEOUT, SyncJob::STATUS_CANCELLED])
            ->count();
    }

    /**
     * Get unique products synced count (from sync_jobs)
     * Overrides outdated products_synced column with real-time data
     */
    public function getProductsSyncedComputedAttribute(): int
    {
        return $this->syncJobs()
            ->where('job_type', SyncJob::JOB_PRODUCT_SYNC)
            ->where('status', SyncJob::STATUS_COMPLETED)
            ->distinct('source_id')
            ->count('source_id');
    }

    /**
     * Check if shop has pending or running sync jobs (2025-11-12)
     *
     * Used for SYNC NOW button state:
     * - true = button ENABLED (can execute pending job immediately)
     * - false = button DISABLED (no pending jobs to execute)
     *
     * BUG FIX #12 (2025-11-12): Check BOTH sync_jobs AND Laravel jobs table
     *
     * PROBLEM: Jobs are added to Laravel 'jobs' table IMMEDIATELY when dispatch() is called,
     * but sync_jobs records are created LATER in handle() method when queue worker executes.
     * This timing gap caused button to be disabled even when jobs were queued.
     *
     * SOLUTION: Check sync_jobs first (jobs being processed), then fallback to Laravel queue
     * (jobs waiting to be processed) using QueueJobsService.
     *
     * @return bool
     */
    public function hasPendingSyncJob(): bool
    {
        // Check sync_jobs table (jobs being processed)
        $hasSyncJob = $this->syncJobs()
            ->whereIn('status', [SyncJob::STATUS_PENDING, SyncJob::STATUS_RUNNING])
            ->exists();

        if ($hasSyncJob) {
            return true;
        }

        // Check Laravel queue (jobs waiting to be processed)
        try {
            $queueService = app(\App\Services\QueueJobsService::class);
            $activeJobs = $queueService->getActiveJobs();

            // Filter for jobs targeting this shop
            $hasQueueJob = $activeJobs->contains(function($job) {
                return isset($job['data']['shop_id']) && $job['data']['shop_id'] == $this->id;
            });

            return $hasQueueJob;
        } catch (\Exception $e) {
            // If queue service fails, fallback to sync_jobs only (conservative approach)
            \Log::warning("hasPendingSyncJob() queue check failed: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Get pending sync job for this shop (2025-11-12)
     *
     * Returns the most recent pending or running job
     *
     * @return SyncJob|null
     */
    public function getPendingSyncJob(): ?SyncJob
    {
        return $this->syncJobs()
            ->whereIn('status', [SyncJob::STATUS_PENDING, SyncJob::STATUS_RUNNING])
            ->orderBy('created_at', 'desc')
            ->first();
    }

    // ==========================================
    // ETAP_07f_P3.5: Multi-file CSS/JS Methods
    // 2025-12-17: Auto-scan and multi-file support
    // ==========================================

    /**
     * Get enabled CSS files for preview.
     *
     * @return array Array of enabled CSS file entries
     */
    public function getEnabledCssFiles(): array
    {
        $files = $this->css_files ?? [];
        return array_filter($files, fn($f) => $f['enabled'] ?? false);
    }

    /**
     * Get enabled JS files for preview.
     *
     * @return array Array of enabled JS file entries
     */
    public function getEnabledJsFiles(): array
    {
        $files = $this->js_files ?? [];
        return array_filter($files, fn($f) => $f['enabled'] ?? false);
    }

    /**
     * Get combined CSS content from all enabled files.
     *
     * @return string Combined CSS content
     */
    public function getCombinedCssContent(): string
    {
        $parts = [];
        foreach ($this->getEnabledCssFiles() as $file) {
            if (!empty($file['cached_content'])) {
                $name = $file['name'] ?? basename($file['url'] ?? '');
                $parts[] = "/* ========== {$name} ========== */\n" . $file['cached_content'];
            }
        }
        return implode("\n\n", $parts);
    }

    /**
     * Get combined JS content from all enabled files.
     *
     * @return string Combined JS content
     */
    public function getCombinedJsContent(): string
    {
        $parts = [];
        foreach ($this->getEnabledJsFiles() as $file) {
            if (!empty($file['cached_content'])) {
                $name = $file['name'] ?? basename($file['url'] ?? '');
                $parts[] = "/* ========== {$name} ========== */\n" . $file['cached_content'];
            }
        }
        return implode("\n\n", $parts);
    }

    /**
     * Check if shop has scanned files.
     *
     * @return bool
     */
    public function hasScannedFiles(): bool
    {
        return $this->files_scanned_at !== null && (!empty($this->css_files) || !empty($this->js_files));
    }

    /**
     * Get total count of CSS files.
     */
    public function getCssFilesCount(): int
    {
        return count($this->css_files ?? []);
    }

    /**
     * Get total count of JS files.
     */
    public function getJsFilesCount(): int
    {
        return count($this->js_files ?? []);
    }

    /**
     * Get count of enabled CSS files.
     */
    public function getEnabledCssFilesCount(): int
    {
        return count($this->getEnabledCssFiles());
    }

    /**
     * Get count of enabled JS files.
     */
    public function getEnabledJsFilesCount(): int
    {
        return count($this->getEnabledJsFiles());
    }

    // ==========================================
    // ETAP_10: Customizable Label Colors & Icons
    // ==========================================

    /**
     * Default label color for PrestaShop shops
     */
    public const DEFAULT_LABEL_COLOR = '#06b6d4'; // cyan-500

    /**
     * Default label icon for PrestaShop shops
     */
    public const DEFAULT_LABEL_ICON = 'shopping-cart';

    /**
     * Available label colors for selection
     */
    public static function getAvailableLabelColors(): array
    {
        return [
            '#ef4444' => 'Czerwony',
            '#f97316' => 'Pomaranczowy',
            '#f59e0b' => 'Bursztynowy',
            '#eab308' => 'Zolty',
            '#84cc16' => 'Limonkowy',
            '#22c55e' => 'Zielony',
            '#14b8a6' => 'Morski',
            '#06b6d4' => 'Cyjan',
            '#3b82f6' => 'Niebieski',
            '#6366f1' => 'Indygo',
            '#8b5cf6' => 'Fioletowy',
            '#d946ef' => 'Magenta',
            '#ec4899' => 'Rozowy',
            '#64748b' => 'Szary',
        ];
    }

    /**
     * Available label icons for selection
     */
    public static function getAvailableLabelIcons(): array
    {
        return [
            'shopping-cart' => 'Koszyk',
            'store' => 'Sklep',
            'globe' => 'Globus',
            'tag' => 'Etykieta',
            'credit-card' => 'Karta',
            'truck' => 'Ciezarowka',
            'box' => 'Paczka',
            'star' => 'Gwiazdka',
            'heart' => 'Serce',
            'lightning-bolt' => 'Blyskawica',
            'sparkles' => 'Iskierki',
            'badge-check' => 'Odznaka',
        ];
    }

    /**
     * Get the effective label color (custom or default)
     */
    public function getLabelColorAttribute(): string
    {
        return $this->attributes['label_color'] ?? self::DEFAULT_LABEL_COLOR;
    }

    /**
     * Get the effective label icon (custom or default)
     */
    public function getLabelIconAttribute(): string
    {
        return $this->attributes['label_icon'] ?? self::DEFAULT_LABEL_ICON;
    }

    /**
     * Get CSS inline styles for label badge
     */
    public function getLabelBadgeStyleAttribute(): string
    {
        $color = $this->label_color;
        return "background-color: {$color}20; color: {$color}; border-color: {$color}50;";
    }

    /**
     * Get label data for display in other components
     */
    public function getLabelDataAttribute(): array
    {
        return [
            'name' => $this->name,
            'color' => $this->label_color,
            'icon' => $this->label_icon,
            'url' => $this->url,
        ];
    }
}