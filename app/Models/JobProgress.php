<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * JobProgress Model
 *
 * Tracks real-time progress of long-running import/export jobs
 *
 * FEATURES:
 * - Real-time progress tracking (current/total)
 * - Status management (pending, running, completed, failed)
 * - Error tracking with SKU-specific details
 * - Duration calculation (started_at → completed_at)
 * - Shop relationship for multi-store tracking
 *
 * USAGE:
 * ```php
 * $progress = JobProgress::create([
 *     'job_id' => $this->job->getJobId(),
 *     'job_type' => 'import',
 *     'shop_id' => $shop->id,
 *     'total_count' => 100,
 *     'status' => 'running',
 *     'started_at' => now(),
 * ]);
 *
 * $progress->updateProgress(50, [['sku' => 'ABC', 'error' => 'Failed']]);
 * $progress->markCompleted();
 * ```
 *
 * @property int $id
 * @property string $job_id
 * @property string $job_type
 * @property int|null $shop_id
 * @property string $status
 * @property int $current_count
 * @property int $total_count
 * @property int $error_count
 * @property array|null $error_details
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read PrestaShopShop|null $shop
 * @property-read int $progress_percentage
 * @property-read int|null $duration_seconds
 * @property-read bool $is_running
 * @property-read bool $is_completed
 * @property-read bool $is_failed
 *
 * @package App\Models
 * @version 1.0
 * @since ETAP_07 - Real-Time Progress Tracking
 */
class JobProgress extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'job_progress';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'job_id',
        'job_type',
        'shop_id',
        'status',
        'current_count',
        'total_count',
        'error_count',
        'error_details',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'shop_id' => 'integer',
        'current_count' => 'integer',
        'total_count' => 'integer',
        'error_count' => 'integer',
        'error_details' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the PrestaShop shop associated with this job
     *
     * @return BelongsTo
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS
    |--------------------------------------------------------------------------
    */

    /**
     * Get progress percentage (0-100)
     *
     * @return Attribute
     */
    protected function progressPercentage(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->total_count > 0
                ? min(100, round(($this->current_count / $this->total_count) * 100))
                : 0
        );
    }

    /**
     * Get job duration in seconds
     *
     * @return Attribute
     */
    protected function durationSeconds(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->started_at && $this->completed_at
                ? $this->completed_at->diffInSeconds($this->started_at)
                : null
        );
    }

    /**
     * Check if job is currently running
     *
     * @return Attribute
     */
    protected function isRunning(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'running'
        );
    }

    /**
     * Check if job is completed
     *
     * @return Attribute
     */
    protected function isCompleted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'completed'
        );
    }

    /**
     * Check if job has failed
     *
     * @return Attribute
     */
    protected function isFailed(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'failed'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope for active jobs (running or pending)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'running']);
    }

    /**
     * Scope for jobs by shop
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $shopId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope for jobs by type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('job_type', $type);
    }

    /**
     * Scope for recent jobs (last 24 hours)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subDay());
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Update progress with current count and optional errors
     *
     * @param int $currentCount
     * @param array $newErrors Optional array of errors to append
     * @return bool
     */
    public function updateProgress(int $currentCount, array $newErrors = []): bool
    {
        $this->current_count = $currentCount;

        // Append new errors to existing error_details
        if (!empty($newErrors)) {
            $existingErrors = $this->error_details ?? [];
            $this->error_details = array_merge($existingErrors, $newErrors);
            $this->error_count = count($this->error_details);
        }

        // Auto-update status to running if still pending
        if ($this->status === 'pending') {
            $this->status = 'running';
            $this->started_at = now();
        }

        return $this->save();
    }

    /**
     * Mark job as completed with optional summary
     *
     * @param array $summary Optional completion summary
     * @return bool
     */
    public function markCompleted(array $summary = []): bool
    {
        $this->status = 'completed';
        $this->completed_at = now();

        // Ensure current_count matches total_count for completed jobs
        if ($this->current_count < $this->total_count) {
            $this->current_count = $this->total_count;
        }

        return $this->save();
    }

    /**
     * Mark job as failed with error message
     *
     * @param string $errorMessage
     * @param array $errorDetails Optional detailed error info
     * @return bool
     */
    public function markFailed(string $errorMessage, array $errorDetails = []): bool
    {
        $this->status = 'failed';
        $this->completed_at = now();

        // Store error message in error_details
        $failureInfo = [
            'message' => $errorMessage,
            'timestamp' => now()->toDateTimeString(),
        ];

        if (!empty($errorDetails)) {
            $failureInfo['details'] = $errorDetails;
        }

        $existingErrors = $this->error_details ?? [];
        $this->error_details = array_merge($existingErrors, [$failureInfo]);
        $this->error_count = count($this->error_details);

        return $this->save();
    }

    /**
     * Add error for specific product/item
     *
     * @param string $identifier Product SKU or ID
     * @param string $errorMessage Error message
     * @return bool
     */
    public function addError(string $identifier, string $errorMessage): bool
    {
        $existingErrors = $this->error_details ?? [];
        $existingErrors[] = [
            'sku' => $identifier,
            'error' => $errorMessage,
            'timestamp' => now()->toDateTimeString(),
        ];

        $this->error_details = $existingErrors;
        $this->error_count = count($existingErrors);

        return $this->save();
    }

    /**
     * Get summary statistics
     *
     * @return array
     */
    public function getSummary(): array
    {
        return [
            'job_id' => $this->job_id,
            'job_type' => $this->job_type,
            'shop_id' => $this->shop_id,
            'shop_name' => $this->shop?->name,
            'status' => $this->status,
            'progress_percentage' => $this->progress_percentage,
            'current_count' => $this->current_count,
            'total_count' => $this->total_count,
            'error_count' => $this->error_count,
            'success_count' => $this->current_count - $this->error_count,
            'duration_seconds' => $this->duration_seconds,
            'started_at' => $this->started_at?->toDateTimeString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
        ];
    }
}
