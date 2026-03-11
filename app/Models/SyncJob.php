<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

/**
 * SyncJob Model
 * 
 * FAZA B: Shop & ERP Management - Sync Jobs Management
 * 
 * Reprezentuje zadanie synchronizacji między PPM a systemami zewnętrznymi.
 * Każde zadanie ma monitoring postępu, error handling, retry logic i
 * performance metrics dla enterprise-grade operations.
 * 
 * Enterprise Features:
 * - Real-time progress tracking z percentage completion
 * - Comprehensive error logging z stack traces i retry logic
 * - Performance profiling z memory usage i timing metrics
 * - Dependency management dla complex sync workflows
 * - Queue integration z Laravel Jobs system
 * 
 * @property int $id
 * @property string $job_id (UUID)
 * @property string $job_type
 * @property string $job_name
 * @property string $source_type
 * @property string $source_id
 * @property string $target_type  
 * @property string $target_id
 * @property string $status
 * @property int $total_items
 * @property int $processed_items
 * @property int $successful_items
 * @property int $failed_items
 * @property float $progress_percentage
 * @property Carbon $scheduled_at
 * @property Carbon $started_at
 * @property Carbon $completed_at
 * @property int $duration_seconds
 * @property int $timeout_seconds
 * @property array $job_config
 * @property array $job_data
 * @property array $filters
 * @property array $mapping_rules
 * @property string $error_message
 * @property string $error_details
 * @property string $stack_trace
 * @property int $retry_count
 * @property int $max_retries
 * @property Carbon $next_retry_at
 * @property float $avg_item_processing_time
 * @property int $memory_peak_mb
 * @property float $cpu_time_seconds
 * @property int $api_calls_made
 * @property int $database_queries
 * @property array $result_summary
 * @property array $affected_records
 * @property array $validation_errors
 * @property array $warnings
 * @property int $user_id
 * @property string $trigger_type
 * @property string $queue_name
 * @property string $queue_job_id
 * @property int $queue_attempts
 * @property bool $notify_on_completion
 * @property bool $notify_on_failure
 * @property array $notification_channels
 * @property Carbon $last_notification_sent
 * @property string $parent_job_id
 * @property array $dependent_jobs
 * @property string $batch_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SyncJob extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'sync_jobs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'job_id',
        'job_type',
        'job_name',
        'source_type',
        'source_id',
        'target_type',
        'target_id',
        'status',
        'total_items',
        'processed_items',
        'successful_items',
        'failed_items',
        'progress_percentage',
        'scheduled_at',
        'started_at',
        'completed_at',
        'duration_seconds',
        'timeout_seconds',
        'job_config',
        'job_data',
        'filters',
        'mapping_rules',
        'error_message',
        'error_details',
        'stack_trace',
        'retry_count',
        'max_retries',
        'next_retry_at',
        'avg_item_processing_time',
        'memory_peak_mb',
        'cpu_time_seconds',
        'api_calls_made',
        'database_queries',
        'result_summary',
        'affected_records',
        'validation_errors',
        'warnings',
        'user_id',
        'trigger_type',
        'queue_name',
        'queue_job_id',
        'queue_attempts',
        'notify_on_completion',
        'notify_on_failure',
        'notification_channels',
        'last_notification_sent',
        'parent_job_id',
        'dependent_jobs',
        'batch_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'notify_on_completion' => 'boolean',
        'notify_on_failure' => 'boolean',
        'job_config' => 'array',
        'job_data' => 'array',
        'filters' => 'array',
        'mapping_rules' => 'array',
        'result_summary' => 'array',
        'affected_records' => 'array',
        'validation_errors' => 'array',
        'warnings' => 'array',
        'notification_channels' => 'array',
        'dependent_jobs' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'last_notification_sent' => 'datetime',
        'progress_percentage' => 'decimal:2',
        'avg_item_processing_time' => 'decimal:3',
        'cpu_time_seconds' => 'decimal:3',
    ];

    /**
     * Job Status Constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_COMPLETED_WITH_ERRORS = 'completed_with_errors'; // 2025-11-12: Partial success
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_TIMEOUT = 'timeout';

    /**
     * Source/Target Type Constants
     */
    public const TYPE_PPM = 'ppm';
    public const TYPE_PRESTASHOP = 'prestashop';
    public const TYPE_BASELINKER = 'baselinker';
    public const TYPE_SUBIEKT_GT = 'subiekt_gt';
    public const TYPE_DYNAMICS = 'dynamics';
    public const TYPE_MANUAL = 'manual';
    public const TYPE_SCHEDULED = 'scheduled';
    public const TYPE_MULTIPLE = 'multiple';

    /**
     * Trigger Type Constants
     */
    public const TRIGGER_MANUAL = 'manual';
    public const TRIGGER_SCHEDULED = 'scheduled';
    public const TRIGGER_WEBHOOK = 'webhook';
    public const TRIGGER_EVENT = 'event';
    public const TRIGGER_API = 'api';

    /**
     * Job Type Constants
     */
    public const JOB_PRODUCT_SYNC = 'product_sync';
    public const JOB_CATEGORY_SYNC = 'category_sync';
    public const JOB_PRICE_SYNC = 'price_sync';
    public const JOB_STOCK_SYNC = 'stock_sync';
    public const JOB_ORDER_SYNC = 'order_sync';
    public const JOB_BULK_EXPORT = 'bulk_export';
    public const JOB_BULK_IMPORT = 'bulk_import';
    public const JOB_HEALTH_CHECK = 'health_check';

    /**
     * Get the user who initiated this job.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent job (for dependent jobs).
     */
    public function parentJob(): BelongsTo
    {
        return $this->belongsTo(SyncJob::class, 'parent_job_id', 'job_id');
    }

    /**
     * Get child jobs (dependents).
     */
    public function childJobs(): HasMany
    {
        return $this->hasMany(SyncJob::class, 'parent_job_id', 'job_id');
    }

    /**
     * Get integration logs for this job.
     */
    public function integrationLogs(): HasMany
    {
        return $this->hasMany(IntegrationLog::class, 'sync_job_id', 'job_id');
    }

    /**
     * Get PrestaShop shop (target) for this sync job.
     * ETAP_07 FAZA 1H - Support for SyncController eager loading
     */
    public function prestashopShop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'target_id', 'id');
    }

    /**
     * Get status badge class for UI.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'badge-secondary',
            self::STATUS_RUNNING => 'badge-primary',
            self::STATUS_PAUSED => 'badge-warning',
            self::STATUS_COMPLETED => 'badge-success',
            self::STATUS_FAILED => 'badge-danger',
            self::STATUS_CANCELLED => 'badge-dark',
            self::STATUS_TIMEOUT => 'badge-warning',
            default => 'badge-secondary'
        };
    }

    /**
     * Get human-readable status.
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Oczekuje',
            self::STATUS_RUNNING => 'W trakcie',
            self::STATUS_PAUSED => 'Zatrzymane',
            self::STATUS_COMPLETED => 'Ukończone',
            self::STATUS_FAILED => 'Nieudane',
            self::STATUS_CANCELLED => 'Anulowane',
            self::STATUS_TIMEOUT => 'Przekroczenie czasu',
            default => 'Nieznany'
        };
    }

    /**
     * Get success rate percentage.
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->processed_items === 0) {
            return 0.0;
        }
        
        return round(($this->successful_items / $this->processed_items) * 100, 2);
    }

    /**
     * Get failure rate percentage.
     */
    public function getFailureRateAttribute(): float
    {
        if ($this->processed_items === 0) {
            return 0.0;
        }
        
        return round(($this->failed_items / $this->processed_items) * 100, 2);
    }

    /**
     * Get estimated time remaining.
     */
    public function getEstimatedTimeRemainingAttribute(): ?int
    {
        if (!$this->isRunning() || !$this->avg_item_processing_time || $this->total_items === 0) {
            return null;
        }

        $remaining_items = $this->total_items - $this->processed_items;
        
        return (int) ceil($remaining_items * $this->avg_item_processing_time / 1000);
    }

    /**
     * Get actual duration in human readable format.
     */
    public function getDurationHumanAttribute(): ?string
    {
        if (!$this->duration_seconds) {
            return null;
        }

        if ($this->duration_seconds < 60) {
            return $this->duration_seconds . 's';
        } elseif ($this->duration_seconds < 3600) {
            return floor($this->duration_seconds / 60) . 'm ' . ($this->duration_seconds % 60) . 's';
        } else {
            $hours = floor($this->duration_seconds / 3600);
            $minutes = floor(($this->duration_seconds % 3600) / 60);
            $seconds = $this->duration_seconds % 60;
            return "{$hours}h {$minutes}m {$seconds}s";
        }
    }

    /**
     * Check if job is running.
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * Check if job is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if job failed.
     */
    public function isFailed(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_TIMEOUT]);
    }

    /**
     * Check if job can be retried.
     */
    public function canRetry(): bool
    {
        return $this->isFailed() && $this->retry_count < $this->max_retries;
    }

    /**
     * Check if job is due for retry.
     */
    public function isDueForRetry(): bool
    {
        return $this->canRetry() 
            && $this->next_retry_at 
            && Carbon::now()->gte($this->next_retry_at);
    }

    /**
     * Check if job has timed out.
     */
    public function hasTimedOut(): bool
    {
        if (!$this->started_at || !$this->isRunning()) {
            return false;
        }

        return Carbon::now()->diffInSeconds($this->started_at) > $this->timeout_seconds;
    }

    /**
     * Start the job.
     */
    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => Carbon::now(),
        ]);
    }

    /**
     * Complete the job.
     */
    public function complete(array $resultSummary = []): void
    {
        $completedAt = Carbon::now();
        $started_at = $this->started_at ?: $completedAt;

        // BUG FIX (2025-11-12): Ensure duration is never negative
        // Use absolute value to handle edge cases where started_at > completed_at
        $duration = abs($completedAt->diffInSeconds($started_at));

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => $completedAt,
            'duration_seconds' => $duration,
            'progress_percentage' => 100.00,
            'result_summary' => array_merge($this->result_summary ?: [], $resultSummary),
        ]);
    }

    /**
     * Complete the job with errors (Partial Success) - 2025-11-12
     *
     * Used when some items succeeded but others failed.
     * Example: 5 products synced successfully, 3 failed
     *
     * @param array $resultSummary Result summary with successful_items and failed_items counts
     * @return void
     */
    public function completeWithErrors(array $resultSummary = []): void
    {
        $completedAt = Carbon::now();
        $started_at = $this->started_at ?: $completedAt;

        // BUG FIX (2025-11-12): Ensure duration is never negative
        $duration = abs($completedAt->diffInSeconds($started_at));

        $this->update([
            'status' => self::STATUS_COMPLETED_WITH_ERRORS,
            'completed_at' => $completedAt,
            'duration_seconds' => $duration,
            'progress_percentage' => 100.00,
            'result_summary' => array_merge($this->result_summary ?: [], $resultSummary),
            'error_message' => 'Job completed with partial failures. See result_summary for details.',
        ]);
    }

    /**
     * Fail the job.
     */
    public function fail(
        string $errorMessage,
        ?string $errorDetails = null,
        ?string $stackTrace = null
    ): void {
        $completedAt = Carbon::now();
        $started_at = $this->started_at ?: $completedAt;

        // BUG FIX (2025-11-12): Ensure duration is never negative
        $duration = abs($completedAt->diffInSeconds($started_at));

        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => $completedAt,
            'duration_seconds' => $duration,
            'error_message' => $errorMessage,
            'error_details' => $errorDetails,
            'stack_trace' => $stackTrace,
        ]);

        $this->scheduleRetry();
    }

    /**
     * Cancel the job.
     */
    public function cancel(): void
    {
        $completedAt = Carbon::now();
        $started_at = $this->started_at ?: $completedAt;

        // BUG FIX (2025-11-12): Ensure duration is never negative
        $duration = abs($completedAt->diffInSeconds($started_at));

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'completed_at' => $completedAt,
            'duration_seconds' => $duration,
        ]);
    }

    /**
     * Pause the job.
     */
    public function pause(): void
    {
        $this->update([
            'status' => self::STATUS_PAUSED,
        ]);
    }

    /**
     * Resume the job.
     */
    public function resume(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
        ]);
    }

    /**
     * Update progress.
     */
    public function updateProgress(
        int $processedItems,
        int $successfulItems,
        int $failedItems,
        ?float $avgProcessingTime = null
    ): void {
        $progress = $this->total_items > 0 
            ? ($processedItems / $this->total_items) * 100 
            : 0;

        $updateData = [
            'processed_items' => $processedItems,
            'successful_items' => $successfulItems,
            'failed_items' => $failedItems,
            'progress_percentage' => round($progress, 2),
        ];

        if ($avgProcessingTime !== null) {
            $updateData['avg_item_processing_time'] = $avgProcessingTime;
        }

        $this->update($updateData);
    }

    /**
     * Update performance metrics.
     */
    public function updatePerformanceMetrics(
        int $memoryPeakMb,
        float $cpuTimeSeconds,
        int $apiCallsMade = 0,
        int $databaseQueries = 0
    ): void {
        $this->update([
            'memory_peak_mb' => $memoryPeakMb,
            'cpu_time_seconds' => $cpuTimeSeconds,
            'api_calls_made' => $apiCallsMade,
            'database_queries' => $databaseQueries,
        ]);
    }

    /**
     * Schedule retry.
     */
    public function scheduleRetry(): void
    {
        if (!$this->canRetry()) {
            return;
        }

        $this->update([
            'retry_count' => $this->retry_count + 1,
            'next_retry_at' => Carbon::now()->addSeconds($this->retry_delay_seconds ?? 60),
            'status' => self::STATUS_PENDING,
        ]);
    }

    /**
     * Add warning.
     */
    public function addWarning(string $warning): void
    {
        $warnings = $this->warnings ?: [];
        $warnings[] = [
            'message' => $warning,
            'timestamp' => Carbon::now()->toISOString(),
        ];
        
        $this->update(['warnings' => $warnings]);
    }

    /**
     * Add validation error.
     */
    public function addValidationError(string $field, string $error): void
    {
        $validationErrors = $this->validation_errors ?: [];
        $validationErrors[$field] = $error;
        
        $this->update(['validation_errors' => $validationErrors]);
    }

    /**
     * Scope for pending jobs.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for running jobs.
     */
    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    /**
     * Scope for completed jobs.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for failed jobs.
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('status', [self::STATUS_FAILED, self::STATUS_TIMEOUT]);
    }

    /**
     * Scope for jobs by type.
     */
    public function scopeByType($query, string $jobType)
    {
        return $query->where('job_type', $jobType);
    }

    /**
     * Scope for jobs by source.
     */
    public function scopeBySource($query, string $sourceType, ?string $sourceId = null)
    {
        $query = $query->where('source_type', $sourceType);
        
        if ($sourceId) {
            $query->where('source_id', $sourceId);
        }
        
        return $query;
    }

    /**
     * Scope for jobs by target.
     */
    public function scopeByTarget($query, string $targetType, ?string $targetId = null)
    {
        $query = $query->where('target_type', $targetType);
        
        if ($targetId) {
            $query->where('target_id', $targetId);
        }
        
        return $query;
    }

    /**
     * Scope for jobs ready for retry.
     */
    public function scopeReadyForRetry($query)
    {
        return $query->failed()
                    ->where('retry_count', '<', 'max_retries')
                    ->where('next_retry_at', '<=', Carbon::now());
    }

    /**
     * Scope for timed out jobs.
     */
    public function scopeTimedOut($query)
    {
        return $query->running()
                    ->where('started_at', '<=', 
                        Carbon::now()->subSeconds(3600) // Default 1 hour timeout
                    );
    }
}