<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

/**
 * ERPConnection Model
 * 
 * FAZA B: Shop & ERP Management - ERP Systems Integration
 * 
 * Reprezentuje połączenie z systemem ERP (Baselinker, Subiekt GT, Microsoft Dynamics).
 * Każde połączenie ma swoją konfigurację, uwierzytelnienie, ustawienia synchronizacji
 * i monitoring wydajności.
 * 
 * Enterprise Features:
 * - Multi-instance ERP support (wiele instancji tego samego ERP)
 * - Encrypted connection configuration storage
 * - Advanced authentication management (OAuth2, API Keys, DLL bridges)
 * - Comprehensive health monitoring i retry logic
 * - Performance metrics i bottleneck detection
 * 
 * @property int $id
 * @property string $erp_type
 * @property string $instance_name
 * @property string $description
 * @property bool $is_active
 * @property int $priority
 * @property array $connection_config (encrypted)
 * @property string $auth_status
 * @property Carbon $auth_expires_at
 * @property Carbon $last_auth_at
 * @property string $connection_status
 * @property Carbon $last_health_check
 * @property float $last_response_time
 * @property int $consecutive_failures
 * @property string $last_error_message
 * @property int $rate_limit_per_minute
 * @property int $current_api_usage
 * @property Carbon $rate_limit_reset_at
 * @property string $sync_mode
 * @property array $sync_settings
 * @property bool $auto_sync_products
 * @property bool $auto_sync_stock
 * @property bool $auto_sync_prices
 * @property bool $auto_sync_orders
 * @property array $field_mappings
 * @property array $transformation_rules
 * @property array $validation_rules
 * @property Carbon $last_sync_at
 * @property Carbon $next_scheduled_sync
 * @property int $sync_success_count
 * @property int $sync_error_count
 * @property int $records_synced_total
 * @property float $avg_sync_time
 * @property float $avg_response_time
 * @property int $data_volume_mb
 * @property int $max_retry_attempts
 * @property int $retry_delay_seconds
 * @property bool $auto_disable_on_errors
 * @property int $error_threshold
 * @property string $webhook_url
 * @property string $webhook_secret
 * @property bool $webhook_enabled
 * @property array $notification_settings
 * @property bool $notify_on_errors
 * @property bool $notify_on_sync_complete
 * @property bool $notify_on_auth_expire
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ERPConnection extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'erp_connections';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'erp_type',
        'instance_name',
        'description',
        'is_active',
        'priority',
        'connection_config',
        'auth_status',
        'auth_expires_at',
        'last_auth_at',
        'connection_status',
        'last_health_check',
        'last_response_time',
        'consecutive_failures',
        'last_error_message',
        'rate_limit_per_minute',
        'current_api_usage',
        'rate_limit_reset_at',
        'sync_mode',
        'sync_settings',
        'auto_sync_products',
        'auto_sync_stock',
        'auto_sync_prices',
        'auto_sync_orders',
        'field_mappings',
        'transformation_rules',
        'validation_rules',
        'last_sync_at',
        'next_scheduled_sync',
        'sync_success_count',
        'sync_error_count',
        'records_synced_total',
        'avg_sync_time',
        'avg_response_time',
        'data_volume_mb',
        'max_retry_attempts',
        'retry_delay_seconds',
        'auto_disable_on_errors',
        'error_threshold',
        'webhook_url',
        'webhook_secret',
        'webhook_enabled',
        'notification_settings',
        'notify_on_errors',
        'notify_on_sync_complete',
        'notify_on_auth_expire',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'auto_sync_products' => 'boolean',
        'auto_sync_stock' => 'boolean',
        'auto_sync_prices' => 'boolean',
        'auto_sync_orders' => 'boolean',
        'auto_disable_on_errors' => 'boolean',
        'webhook_enabled' => 'boolean',
        'notify_on_errors' => 'boolean',
        'notify_on_sync_complete' => 'boolean',
        'notify_on_auth_expire' => 'boolean',
        'connection_config' => 'array',
        'sync_settings' => 'array',
        'field_mappings' => 'array',
        'transformation_rules' => 'array',
        'validation_rules' => 'array',
        'notification_settings' => 'array',
        'auth_expires_at' => 'datetime',
        'last_auth_at' => 'datetime',
        'last_health_check' => 'datetime',
        'last_sync_at' => 'datetime',
        'next_scheduled_sync' => 'datetime',
        'rate_limit_reset_at' => 'datetime',
        'last_response_time' => 'decimal:3',
        'avg_response_time' => 'decimal:3',
        'avg_sync_time' => 'decimal:3',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'connection_config',
        'webhook_secret',
    ];

    /**
     * ERP Type Constants
     */
    public const ERP_BASELINKER = 'baselinker';
    public const ERP_SUBIEKT_GT = 'subiekt_gt';
    public const ERP_DYNAMICS = 'dynamics';
    public const ERP_INSERT = 'insert';
    public const ERP_CUSTOM = 'custom';

    /**
     * Authentication Status Constants
     */
    public const AUTH_AUTHENTICATED = 'authenticated';
    public const AUTH_EXPIRED = 'expired';
    public const AUTH_FAILED = 'failed';
    public const AUTH_PENDING = 'pending';

    /**
     * Connection Status Constants
     */
    public const CONNECTION_CONNECTED = 'connected';
    public const CONNECTION_DISCONNECTED = 'disconnected';
    public const CONNECTION_ERROR = 'error';
    public const CONNECTION_MAINTENANCE = 'maintenance';
    public const CONNECTION_RATE_LIMITED = 'rate_limited';

    /**
     * Sync Mode Constants
     */
    public const SYNC_BIDIRECTIONAL = 'bidirectional';
    public const SYNC_PUSH_ONLY = 'push_only';
    public const SYNC_PULL_ONLY = 'pull_only';
    public const SYNC_DISABLED = 'disabled';

    /**
     * Get the integration mappings for this ERP connection.
     */
    public function integrationMappings(): MorphMany
    {
        return $this->morphMany(IntegrationMapping::class, 'mappable')
            ->where('integration_type', $this->erp_type)
            ->where('integration_identifier', $this->instance_name);
    }

    /**
     * Get sync jobs for this ERP connection.
     */
    public function syncJobs(): HasMany
    {
        return $this->hasMany(SyncJob::class, 'target_id', 'id')
            ->where('target_type', $this->erp_type);
    }

    /**
     * Get integration logs for this ERP connection.
     */
    public function integrationLogs(): HasMany
    {
        return $this->hasMany(IntegrationLog::class, 'integration_id', 'id')
            ->where('integration_type', $this->erp_type);
    }

    /**
     * Get the decrypted connection config.
     * Supports both encrypted and plain JSON formats for backwards compatibility.
     */
    protected function connectionConfig(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (!$value) {
                    return null;
                }

                // Try plain JSON first (backwards compatibility)
                $decoded = json_decode($value, true);
                if ($decoded !== null && is_array($decoded)) {
                    return $decoded;
                }

                // If not valid JSON, try to decrypt
                try {
                    return json_decode(decrypt($value), true);
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    // Log warning and return empty array
                    \Log::warning('ERPConnection: Failed to decrypt connection_config', [
                        'id' => $this->id ?? 'unknown',
                    ]);
                    return [];
                }
            },
            set: fn (?array $value) => $value ? encrypt(json_encode($value)) : null,
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
            self::CONNECTION_RATE_LIMITED => 'warning',
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
            self::CONNECTION_RATE_LIMITED => 'badge-warning',
            default => 'badge-secondary'
        };
    }

    /**
     * Get authentication status badge class.
     */
    public function getAuthBadgeAttribute(): string
    {
        return match ($this->auth_status) {
            self::AUTH_AUTHENTICATED => 'badge-success',
            self::AUTH_EXPIRED => 'badge-warning',
            self::AUTH_FAILED => 'badge-danger',
            self::AUTH_PENDING => 'badge-info',
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
     * Get API usage percentage.
     */
    public function getApiUsagePercentageAttribute(): float
    {
        if (!$this->rate_limit_per_minute) {
            return 0.0;
        }

        return round(($this->current_api_usage / $this->rate_limit_per_minute) * 100, 2);
    }

    /**
     * Check if ERP connection is healthy.
     */
    public function isConnectionHealthy(): bool
    {
        return $this->connection_status === self::CONNECTION_CONNECTED 
            && $this->auth_status === self::AUTH_AUTHENTICATED
            && $this->consecutive_failures === 0;
    }

    /**
     * Check if authentication is expired or expiring soon.
     */
    public function isAuthExpiring(int $hoursThreshold = 24): bool
    {
        if (!$this->auth_expires_at) {
            return false;
        }

        return Carbon::now()->addHours($hoursThreshold)->gte($this->auth_expires_at);
    }

    /**
     * Check if authentication is expired.
     */
    public function isAuthExpired(): bool
    {
        if (!$this->auth_expires_at) {
            return $this->auth_status === self::AUTH_EXPIRED;
        }

        return Carbon::now()->gt($this->auth_expires_at);
    }

    /**
     * Check if API is rate limited.
     */
    public function isRateLimited(): bool
    {
        return $this->connection_status === self::CONNECTION_RATE_LIMITED
            || $this->current_api_usage >= $this->rate_limit_per_minute;
    }

    /**
     * Check if should auto-disable due to errors.
     */
    public function shouldAutoDisable(): bool
    {
        return $this->auto_disable_on_errors 
            && $this->consecutive_failures >= $this->error_threshold;
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
        $this->last_health_check = Carbon::now();

        if ($responseTime !== null) {
            $this->last_response_time = $responseTime;
            
            // Update average response time
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
            
            // Auto-disable if threshold reached
            if ($this->shouldAutoDisable()) {
                $this->is_active = false;
            }
        }

        $this->save();
    }

    /**
     * Update authentication status.
     */
    public function updateAuthStatus(
        string $status, 
        ?Carbon $expiresAt = null
    ): void {
        $this->auth_status = $status;
        $this->last_auth_at = Carbon::now();
        
        if ($expiresAt) {
            $this->auth_expires_at = $expiresAt;
        }
        
        $this->save();
    }

    /**
     * Update sync statistics.
     */
    public function updateSyncStats(
        bool $success, 
        int $recordsProcessed = 0, 
        ?float $syncTime = null,
        int $dataVolumeBytes = 0
    ): void {
        $this->last_sync_at = Carbon::now();
        
        if ($success) {
            $this->sync_success_count++;
            $this->records_synced_total += $recordsProcessed;
        } else {
            $this->sync_error_count++;
        }
        
        if ($syncTime !== null) {
            // Update average sync time
            if ($this->avg_sync_time) {
                $this->avg_sync_time = ($this->avg_sync_time + $syncTime) / 2;
            } else {
                $this->avg_sync_time = $syncTime;
            }
        }
        
        // Convert bytes to MB and add to total
        $this->data_volume_mb += round($dataVolumeBytes / 1024 / 1024, 2);
        
        $this->save();
    }

    /**
     * Update API usage.
     */
    public function updateApiUsage(int $currentUsage, ?Carbon $resetTime = null): void
    {
        $this->current_api_usage = $currentUsage;
        
        if ($resetTime) {
            $this->rate_limit_reset_at = $resetTime;
        }
        
        // Check for rate limiting
        if ($this->rate_limit_per_minute && $currentUsage >= $this->rate_limit_per_minute) {
            $this->connection_status = self::CONNECTION_RATE_LIMITED;
        }
        
        $this->save();
    }

    /**
     * Reset API usage counter.
     */
    public function resetApiUsage(): void
    {
        $this->current_api_usage = 0;
        $this->rate_limit_reset_at = Carbon::now()->addMinute();
        
        // Reset rate limit status if that was the issue
        if ($this->connection_status === self::CONNECTION_RATE_LIMITED) {
            $this->connection_status = self::CONNECTION_CONNECTED;
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
     * Scope to get only active ERP connections.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get connections by ERP type.
     */
    public function scopeByType($query, string $erpType)
    {
        return $query->where('erp_type', $erpType);
    }

    /**
     * Scope to get connections with healthy status.
     */
    public function scopeHealthy($query)
    {
        return $query->where('connection_status', self::CONNECTION_CONNECTED)
                    ->where('auth_status', self::AUTH_AUTHENTICATED)
                    ->where('consecutive_failures', 0);
    }

    /**
     * Scope to get connections with authentication issues.
     */
    public function scopeWithAuthIssues($query)
    {
        return $query->where('auth_status', '!=', self::AUTH_AUTHENTICATED)
                    ->orWhere('auth_expires_at', '<=', Carbon::now()->addDay());
    }

    /**
     * Scope to get connections with connection issues.
     */
    public function scopeWithConnectionIssues($query)
    {
        return $query->where('connection_status', '!=', self::CONNECTION_CONNECTED)
                    ->orWhere('consecutive_failures', '>', 0);
    }

    /**
     * Scope to order by priority.
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    /**
     * Get Baselinker connections.
     */
    public function scopeBaselinker($query)
    {
        return $query->byType(self::ERP_BASELINKER);
    }

    /**
     * Get Subiekt GT connections.
     */
    public function scopeSubiektGT($query)
    {
        return $query->byType(self::ERP_SUBIEKT_GT);
    }

    /**
     * Get Microsoft Dynamics connections.
     */
    public function scopeDynamics($query)
    {
        return $query->byType(self::ERP_DYNAMICS);
    }
}