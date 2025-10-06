<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'job_type',
        'job_name',
        'source_type',
        'target_type',
        'target_id',
        'trigger_type',
        'user_id',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'job_config',
        'result_data',
        'error_message',
        'progress_percentage',
        'duration_seconds',
        'items_processed',
        'items_total',
    ];

    protected $casts = [
        'job_config' => 'array',
        'result_data' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress_percentage' => 'integer',
        'duration_seconds' => 'integer',
        'items_processed' => 'integer',
        'items_total' => 'integer',
    ];

    // Job Types
    const JOB_PRODUCT_SYNC = 'product_sync';
    const JOB_BULK_EXPORT = 'bulk_export';
    const JOB_BULK_IMPORT = 'bulk_import';
    const JOB_CATEGORY_SYNC = 'category_sync';
    const JOB_PRICE_SYNC = 'price_sync';
    const JOB_STOCK_SYNC = 'stock_sync';
    const JOB_IMAGE_SYNC = 'image_sync';

    // Source/Target Types
    const TYPE_PPM = 'ppm';
    const TYPE_PRESTASHOP = 'prestashop';
    const TYPE_BASELINKER = 'baselinker';
    const TYPE_SUBIEKT_GT = 'subiekt_gt';
    const TYPE_DYNAMICS = 'dynamics';

    // Trigger Types
    const TRIGGER_MANUAL = 'manual';
    const TRIGGER_SCHEDULED = 'scheduled';
    const TRIGGER_AUTO = 'auto';
    const TRIGGER_WEBHOOK = 'webhook';

    // Job Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_PAUSED = 'paused';

    /**
     * Get the user who triggered this job.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the PrestaShop shop for this job (if target is PrestaShop).
     */
    public function prestashopShop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'target_id');
    }

    /**
     * Scope to get active (running) jobs.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_RUNNING]);
    }

    /**
     * Scope to get completed jobs.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to get failed jobs.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to get jobs for a specific target (shop).
     */
    public function scopeForTarget($query, $targetType, $targetId)
    {
        return $query->where('target_type', $targetType)->where('target_id', $targetId);
    }

    /**
     * Scope to get jobs by type.
     */
    public function scopeOfType($query, $jobType)
    {
        return $query->where('job_type', $jobType);
    }

    /**
     * Check if job is active (pending or running).
     */
    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_RUNNING]);
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
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if job was cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Mark job as started.
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark job as completed.
     */
    public function markAsCompleted(array $resultData = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'progress_percentage' => 100,
            'result_data' => $resultData,
            'duration_seconds' => $this->started_at ? now()->diffInSeconds($this->started_at) : null,
        ]);
    }

    /**
     * Mark job as failed.
     */
    public function markAsFailed(string $errorMessage, array $resultData = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'error_message' => $errorMessage,
            'result_data' => $resultData,
            'duration_seconds' => $this->started_at ? now()->diffInSeconds($this->started_at) : null,
        ]);
    }

    /**
     * Update job progress.
     */
    public function updateProgress(int $progressPercentage, array $data = null): void
    {
        $updateData = [
            'progress_percentage' => max(0, min(100, $progressPercentage)),
        ];

        if ($data) {
            if (isset($data['items_processed'])) {
                $updateData['items_processed'] = $data['items_processed'];
            }
            if (isset($data['items_total'])) {
                $updateData['items_total'] = $data['items_total'];
            }
            if (isset($data['result_data'])) {
                $updateData['result_data'] = array_merge($this->result_data ?? [], $data['result_data']);
            }
        }

        $this->update($updateData);
    }

    /**
     * Get progress percentage with fallback calculation.
     */
    public function getProgressPercentageAttribute($value): int
    {
        if ($value !== null) {
            return $value;
        }

        // Fallback calculation based on items processed
        if ($this->items_total && $this->items_processed !== null) {
            return min(100, (int) round(($this->items_processed / $this->items_total) * 100));
        }

        return 0;
    }

    /**
     * Get estimated time remaining in seconds.
     */
    public function getEstimatedTimeRemaining(): ?int
    {
        if (!$this->isActive() || !$this->started_at || $this->progress_percentage <= 0) {
            return null;
        }

        $elapsedSeconds = now()->diffInSeconds($this->started_at);
        $progressRatio = $this->progress_percentage / 100;
        $totalEstimatedSeconds = $elapsedSeconds / $progressRatio;
        $remainingSeconds = $totalEstimatedSeconds - $elapsedSeconds;

        return max(0, (int) round($remainingSeconds));
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDuration(): string
    {
        if (!$this->duration_seconds) {
            return '—';
        }

        if ($this->duration_seconds < 60) {
            return $this->duration_seconds . 's';
        }

        if ($this->duration_seconds < 3600) {
            return gmdate('i:s', $this->duration_seconds);
        }

        return gmdate('H:i:s', $this->duration_seconds);
    }

    /**
     * Get status badge color.
     */
    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'green',
            self::STATUS_FAILED => 'red',
            self::STATUS_CANCELLED => 'gray',
            self::STATUS_RUNNING => 'blue',
            self::STATUS_PENDING => 'orange',
            self::STATUS_PAUSED => 'yellow',
            default => 'gray'
        };
    }

    /**
     * Get human-readable job type.
     */
    public function getJobTypeLabel(): string
    {
        return match ($this->job_type) {
            self::JOB_PRODUCT_SYNC => 'Synchronizacja Produktów',
            self::JOB_BULK_EXPORT => 'Eksport Masowy',
            self::JOB_BULK_IMPORT => 'Import Masowy',
            self::JOB_CATEGORY_SYNC => 'Synchronizacja Kategorii',
            self::JOB_PRICE_SYNC => 'Synchronizacja Cen',
            self::JOB_STOCK_SYNC => 'Synchronizacja Stanów',
            self::JOB_IMAGE_SYNC => 'Synchronizacja Zdjęć',
            default => 'Nieznany Typ Zadania'
        };
    }
}