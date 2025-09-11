<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
     * Get the decrypted API key.
     */
    protected function apiKey(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => decrypt($value),
            set: fn (string $value) => encrypt($value),
        );
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
}