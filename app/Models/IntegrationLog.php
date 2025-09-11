<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * IntegrationLog Model
 * 
 * FAZA B: Shop & ERP Management - Integration Logging System
 * 
 * Reprezentuje wpis w logu operacji integracyjnych. Przechowuje kompletne
 * informacje o każdej operacji (API calls, sync operations, webhooks),
 * umożliwiając debugging, compliance i performance analysis.
 * 
 * Enterprise Features:
 * - Structured logging z JSON payloads dla complex data
 * - Performance metrics tracking (response time, memory usage, CPU)
 * - Distributed tracing support z correlation IDs
 * - Security i GDPR compliance tracking
 * - Advanced filtering i search capabilities
 * 
 * @property int $id
 * @property string $log_level
 * @property string $log_type
 * @property string $category
 * @property string $subcategory
 * @property string $integration_type
 * @property string $integration_id
 * @property string $external_system
 * @property string $operation
 * @property string $method
 * @property string $endpoint
 * @property string $description
 * @property array $request_data
 * @property array $response_data
 * @property int $http_status
 * @property int $response_time_ms
 * @property int $response_size_bytes
 * @property string $error_code
 * @property string $error_message
 * @property string $error_details
 * @property string $stack_trace
 * @property string $entity_type
 * @property string $entity_id
 * @property string $entity_reference
 * @property int $affected_records
 * @property string $sync_job_id
 * @property int $user_id
 * @property string $session_id
 * @property string $ip_address
 * @property string $user_agent
 * @property bool $sensitive_data
 * @property bool $gdpr_relevant
 * @property Carbon $retention_until
 * @property int $memory_usage_mb
 * @property float $cpu_time_ms
 * @property int $database_queries
 * @property float $database_time_ms
 * @property string $correlation_id
 * @property string $trace_id
 * @property string $span_id
 * @property string $parent_span_id
 * @property array $tags
 * @property array $metadata
 * @property array $custom_fields
 * @property bool $alert_triggered
 * @property string $alert_rule
 * @property Carbon $alert_sent_at
 * @property string $environment
 * @property string $server_name
 * @property string $application_version
 * @property string $processing_status
 * @property Carbon $processed_at
 * @property Carbon $archived_at
 * @property Carbon $logged_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class IntegrationLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'integration_logs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'log_level',
        'log_type',
        'category',
        'subcategory',
        'integration_type',
        'integration_id',
        'external_system',
        'operation',
        'method',
        'endpoint',
        'description',
        'request_data',
        'response_data',
        'http_status',
        'response_time_ms',
        'response_size_bytes',
        'error_code',
        'error_message',
        'error_details',
        'stack_trace',
        'entity_type',
        'entity_id',
        'entity_reference',
        'affected_records',
        'sync_job_id',
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'sensitive_data',
        'gdpr_relevant',
        'retention_until',
        'memory_usage_mb',
        'cpu_time_ms',
        'database_queries',
        'database_time_ms',
        'correlation_id',
        'trace_id',
        'span_id',
        'parent_span_id',
        'tags',
        'metadata',
        'custom_fields',
        'alert_triggered',
        'alert_rule',
        'alert_sent_at',
        'environment',
        'server_name',
        'application_version',
        'processing_status',
        'processed_at',
        'archived_at',
        'logged_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'custom_fields' => 'array',
        'sensitive_data' => 'boolean',
        'gdpr_relevant' => 'boolean',
        'alert_triggered' => 'boolean',
        'logged_at' => 'datetime',
        'retention_until' => 'datetime',
        'alert_sent_at' => 'datetime',
        'processed_at' => 'datetime',
        'archived_at' => 'datetime',
        'cpu_time_ms' => 'decimal:3',
        'database_time_ms' => 'decimal:3',
    ];

    /**
     * Log Level Constants (PSR-3 compatible)
     */
    public const LEVEL_DEBUG = 'debug';
    public const LEVEL_INFO = 'info';
    public const LEVEL_NOTICE = 'notice';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';
    public const LEVEL_CRITICAL = 'critical';
    public const LEVEL_ALERT = 'alert';
    public const LEVEL_EMERGENCY = 'emergency';

    /**
     * Log Type Constants
     */
    public const TYPE_API_CALL = 'api_call';
    public const TYPE_SYNC = 'sync';
    public const TYPE_AUTH = 'auth';
    public const TYPE_WEBHOOK = 'webhook';
    public const TYPE_IMPORT = 'import';
    public const TYPE_EXPORT = 'export';
    public const TYPE_VALIDATION = 'validation';
    public const TYPE_ERROR = 'error';

    /**
     * Integration Type Constants
     */
    public const INTEGRATION_PRESTASHOP = 'prestashop';
    public const INTEGRATION_BASELINKER = 'baselinker';
    public const INTEGRATION_SUBIEKT_GT = 'subiekt_gt';
    public const INTEGRATION_DYNAMICS = 'dynamics';
    public const INTEGRATION_INTERNAL = 'internal';
    public const INTEGRATION_WEBHOOK = 'webhook';

    /**
     * Processing Status Constants
     */
    public const PROCESSING_RAW = 'raw';
    public const PROCESSING_PROCESSED = 'processed';
    public const PROCESSING_ARCHIVED = 'archived';
    public const PROCESSING_DELETED = 'deleted';

    /**
     * Get the user associated with this log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the sync job associated with this log.
     */
    public function syncJob(): BelongsTo
    {
        return $this->belongsTo(SyncJob::class, 'sync_job_id', 'job_id');
    }

    /**
     * Get log level badge class for UI.
     */
    public function getLogLevelBadgeAttribute(): string
    {
        return match ($this->log_level) {
            self::LEVEL_DEBUG => 'badge-secondary',
            self::LEVEL_INFO => 'badge-info',
            self::LEVEL_NOTICE => 'badge-primary',
            self::LEVEL_WARNING => 'badge-warning',
            self::LEVEL_ERROR => 'badge-danger',
            self::LEVEL_CRITICAL => 'badge-danger',
            self::LEVEL_ALERT => 'badge-warning',
            self::LEVEL_EMERGENCY => 'badge-dark',
            default => 'badge-light'
        };
    }

    /**
     * Get human-readable log level.
     */
    public function getLogLevelTextAttribute(): string
    {
        return match ($this->log_level) {
            self::LEVEL_DEBUG => 'Debug',
            self::LEVEL_INFO => 'Informacja',
            self::LEVEL_NOTICE => 'Uwaga',
            self::LEVEL_WARNING => 'Ostrzeżenie',
            self::LEVEL_ERROR => 'Błąd',
            self::LEVEL_CRITICAL => 'Krytyczny',
            self::LEVEL_ALERT => 'Alert',
            self::LEVEL_EMERGENCY => 'Nagły',
            default => 'Nieznany'
        };
    }

    /**
     * Get response time in human readable format.
     */
    public function getResponseTimeHumanAttribute(): string
    {
        if (!$this->response_time_ms) {
            return 'N/A';
        }

        if ($this->response_time_ms < 1000) {
            return $this->response_time_ms . 'ms';
        } else {
            return round($this->response_time_ms / 1000, 2) . 's';
        }
    }

    /**
     * Get response size in human readable format.
     */
    public function getResponseSizeHumanAttribute(): string
    {
        if (!$this->response_size_bytes) {
            return 'N/A';
        }

        $bytes = $this->response_size_bytes;
        
        if ($bytes < 1024) {
            return $bytes . 'B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 1) . 'KB';
        } else {
            return round($bytes / 1048576, 1) . 'MB';
        }
    }

    /**
     * Check if log contains an error.
     */
    public function isError(): bool
    {
        return in_array($this->log_level, [
            self::LEVEL_ERROR,
            self::LEVEL_CRITICAL,
            self::LEVEL_ALERT,
            self::LEVEL_EMERGENCY
        ]);
    }

    /**
     * Check if log is a warning.
     */
    public function isWarning(): bool
    {
        return $this->log_level === self::LEVEL_WARNING;
    }

    /**
     * Check if log needs attention (warning or error).
     */
    public function needsAttention(): bool
    {
        return $this->isError() || $this->isWarning();
    }

    /**
     * Check if log has performance issues.
     */
    public function hasPerformanceIssues(): bool
    {
        return $this->response_time_ms > 5000 // > 5 seconds
            || $this->memory_usage_mb > 512   // > 512 MB
            || $this->database_queries > 100; // > 100 queries
    }

    /**
     * Check if log should be archived.
     */
    public function shouldBeArchived(): bool
    {
        return $this->retention_until && Carbon::now()->gt($this->retention_until);
    }

    /**
     * Archive the log.
     */
    public function archive(): void
    {
        $this->update([
            'processing_status' => self::PROCESSING_ARCHIVED,
            'archived_at' => Carbon::now(),
        ]);
    }

    /**
     * Mark as processed.
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'processing_status' => self::PROCESSING_PROCESSED,
            'processed_at' => Carbon::now(),
        ]);
    }

    /**
     * Add tag.
     */
    public function addTag(string $key, $value = true): void
    {
        $tags = $this->tags ?: [];
        $tags[$key] = $value;
        $this->update(['tags' => $tags]);
    }

    /**
     * Add metadata.
     */
    public function addMetadata(string $key, $value): void
    {
        $metadata = $this->metadata ?: [];
        $metadata[$key] = $value;
        $this->update(['metadata' => $metadata]);
    }

    /**
     * Trigger alert for this log.
     */
    public function triggerAlert(string $alertRule): void
    {
        $this->update([
            'alert_triggered' => true,
            'alert_rule' => $alertRule,
            'alert_sent_at' => Carbon::now(),
        ]);
    }

    /**
     * Scope for specific log level.
     */
    public function scopeLevel($query, string $level)
    {
        return $query->where('log_level', $level);
    }

    /**
     * Scope for error logs.
     */
    public function scopeErrors($query)
    {
        return $query->whereIn('log_level', [
            self::LEVEL_ERROR,
            self::LEVEL_CRITICAL,
            self::LEVEL_ALERT,
            self::LEVEL_EMERGENCY
        ]);
    }

    /**
     * Scope for warning logs.
     */
    public function scopeWarnings($query)
    {
        return $query->where('log_level', self::LEVEL_WARNING);
    }

    /**
     * Scope for logs by integration type.
     */
    public function scopeByIntegration($query, string $integrationType)
    {
        return $query->where('integration_type', $integrationType);
    }

    /**
     * Scope for logs by operation.
     */
    public function scopeByOperation($query, string $operation)
    {
        return $query->where('operation', $operation);
    }

    /**
     * Scope for logs with performance issues.
     */
    public function scopePerformanceIssues($query)
    {
        return $query->where(function ($q) {
            $q->where('response_time_ms', '>', 5000)
              ->orWhere('memory_usage_mb', '>', 512)
              ->orWhere('database_queries', '>', 100);
        });
    }

    /**
     * Scope for logs by date range.
     */
    public function scopeByDateRange($query, Carbon $from, Carbon $to)
    {
        return $query->whereBetween('logged_at', [$from, $to]);
    }

    /**
     * Scope for logs by correlation ID.
     */
    public function scopeByCorrelation($query, string $correlationId)
    {
        return $query->where('correlation_id', $correlationId);
    }

    /**
     * Scope for logs by trace ID.
     */
    public function scopeByTrace($query, string $traceId)
    {
        return $query->where('trace_id', $traceId);
    }

    /**
     * Scope for logs ready for archiving.
     */
    public function scopeReadyForArchiving($query)
    {
        return $query->where('processing_status', self::PROCESSING_RAW)
                    ->where('retention_until', '<=', Carbon::now());
    }

    /**
     * Scope for logs with alerts triggered.
     */
    public function scopeWithAlerts($query)
    {
        return $query->where('alert_triggered', true);
    }

    /**
     * Scope for GDPR relevant logs.
     */
    public function scopeGdprRelevant($query)
    {
        return $query->where('gdpr_relevant', true);
    }

    /**
     * Scope for sensitive data logs.
     */
    public function scopeSensitiveData($query)
    {
        return $query->where('sensitive_data', true);
    }

    /**
     * Static method to create debug log.
     */
    public static function debug(
        string $operation,
        string $description,
        array $data = [],
        ?string $integrationType = null,
        ?string $integrationId = null
    ): self {
        return self::createLog(self::LEVEL_DEBUG, $operation, $description, $data, $integrationType, $integrationId);
    }

    /**
     * Static method to create info log.
     */
    public static function info(
        string $operation,
        string $description,
        array $data = [],
        ?string $integrationtype = null,
        ?string $integrationId = null
    ): self {
        return self::createLog(self::LEVEL_INFO, $operation, $description, $data, $integrationtype, $integrationId);
    }

    /**
     * Static method to create error log.
     */
    public static function error(
        string $operation,
        string $errorMessage,
        array $data = [],
        ?string $integrationType = null,
        ?string $integrationId = null,
        ?\Throwable $exception = null
    ): self {
        $logData = $data;
        
        if ($exception) {
            $logData['error_details'] = $exception->getMessage();
            $logData['stack_trace'] = $exception->getTraceAsString();
            $logData['error_code'] = $exception->getCode();
        }

        return self::createLog(self::LEVEL_ERROR, $operation, $errorMessage, $logData, $integrationType, $integrationId);
    }

    /**
     * Private method to create log entry.
     */
    private static function createLog(
        string $level,
        string $operation,
        string $description,
        array $data = [],
        ?string $integrationType = null,
        ?string $integrationId = null
    ): self {
        $logData = [
            'log_level' => $level,
            'log_type' => $data['log_type'] ?? self::TYPE_API_CALL,
            'operation' => $operation,
            'description' => $description,
            'integration_type' => $integrationType ?? self::INTEGRATION_INTERNAL,
            'integration_id' => $integrationId,
            'logged_at' => Carbon::now(),
            'environment' => config('app.env', 'production'),
            'correlation_id' => $data['correlation_id'] ?? \Str::uuid(),
        ];

        // Merge additional data
        $logData = array_merge($logData, $data);

        return self::create($logData);
    }
}