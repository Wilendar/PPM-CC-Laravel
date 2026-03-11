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
 * - Status management (pending, running, completed, failed, awaiting_user)
 * - Error tracking with SKU-specific details
 * - Duration calculation (started_at → completed_at)
 * - Shop relationship for multi-store tracking
 * - User tracking (who initiated the job)
 * - Flexible metadata for job-specific context
 * - Action button config for completed jobs
 *
 * JOB TYPES:
 * - import: Import products from PrestaShop
 * - sync: Synchronize products
 * - export: Legacy export
 * - category_delete: Delete categories
 * - category_analysis: Background category analysis before import (ETAP_07c)
 * - bulk_export: Export products to PrestaShop (ETAP_07c)
 * - bulk_update: Update products on PrestaShop (ETAP_07c)
 * - stock_sync: Synchronize stock levels (ETAP_07c)
 * - price_sync: Synchronize prices (ETAP_07c)
 *
 * USAGE:
 * ```php
 * $progress = JobProgress::create([
 *     'job_id' => $this->job->getJobId(),
 *     'job_type' => 'import',
 *     'shop_id' => $shop->id,
 *     'user_id' => auth()->id(),
 *     'total_count' => 100,
 *     'status' => 'running',
 *     'started_at' => now(),
 *     'metadata' => ['mode' => 'category', 'category_id' => 5],
 * ]);
 *
 * $progress->updateProgress(50, [['sku' => 'ABC', 'error' => 'Failed']]);
 * $progress->markCompleted();
 * $progress->setActionButton('retry', 'Ponów import', 'admin.import.retry', ['shop_id' => 5]);
 * ```
 *
 * @property int $id
 * @property string $job_id
 * @property string $job_type
 * @property int|null $shop_id
 * @property int|null $user_id
 * @property string $status
 * @property int $current_count
 * @property int $total_count
 * @property int $error_count
 * @property array|null $error_details
 * @property array|null $metadata
 * @property array|null $action_button
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read PrestaShopShop|null $shop
 * @property-read User|null $user
 * @property-read int $progress_percentage
 * @property-read int|null $duration_seconds
 * @property-read bool $is_running
 * @property-read bool $is_completed
 * @property-read bool $is_failed
 * @property-read bool $is_awaiting_user
 *
 * @package App\Models
 * @version 2.0
 * @since ETAP_07 - Real-Time Progress Tracking
 * @since ETAP_07c - Rich Progress Bar
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
        'user_id',
        'status',
        'current_count',
        'total_count',
        'error_count',
        'error_details',
        'metadata',
        'action_button',
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
        'user_id' => 'integer',
        'current_count' => 'integer',
        'total_count' => 'integer',
        'error_count' => 'integer',
        'error_details' => 'array',
        'metadata' => 'array',
        'action_button' => 'array',
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

    /**
     * Get the user who initiated this job
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    /**
     * Check if job is awaiting user action (e.g., category preview)
     *
     * @return Attribute
     */
    protected function isAwaitingUser(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'awaiting_user'
        );
    }

    /**
     * Check if job was cancelled by user
     *
     * @return Attribute
     */
    protected function isCancelled(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'cancelled'
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
        // ETAP_07c FIX: Include awaiting_user - job requires user action but is still "active"
        return $query->whereIn('status', ['pending', 'running', 'awaiting_user']);
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

    /**
     * Scope for jobs by user
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for jobs awaiting user action
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAwaitingUser($query)
    {
        return $query->where('status', 'awaiting_user');
    }

    /**
     * Scope for jobs requiring attention (awaiting_user or with errors)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRequiringAttention($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'awaiting_user')
              ->orWhere('error_count', '>', 0);
        });
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
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'status' => $this->status,
            'progress_percentage' => $this->progress_percentage,
            'current_count' => $this->current_count,
            'total_count' => $this->total_count,
            'error_count' => $this->error_count,
            'success_count' => $this->current_count - $this->error_count,
            'duration_seconds' => $this->duration_seconds,
            'started_at' => $this->started_at?->toDateTimeString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'metadata' => $this->metadata,
            'action_button' => $this->action_button,
        ];
    }

    /**
     * Mark job as awaiting user action (e.g., category preview confirmation)
     *
     * @param string $message Optional message for user
     * @return bool
     */
    public function markAwaitingUser(string $message = ''): bool
    {
        $this->status = 'awaiting_user';

        if ($message) {
            $this->updateMetadata(['awaiting_message' => $message]);
        }

        return $this->save();
    }

    /**
     * Set action button for completed/awaiting jobs
     *
     * @param string $type Button type (e.g., 'preview', 'retry', 'view_details')
     * @param string $label Button label (e.g., 'Zobacz podglad kategorii')
     * @param string $route Route name or action identifier
     * @param array $params Route parameters
     * @return bool
     */
    public function setActionButton(string $type, string $label, string $route, array $params = []): bool
    {
        $this->action_button = [
            'type' => $type,
            'label' => $label,
            'route' => $route,
            'params' => $params,
            'created_at' => now()->toDateTimeString(),
        ];

        return $this->save();
    }

    /**
     * Clear action button
     *
     * @return bool
     */
    public function clearActionButton(): bool
    {
        $this->action_button = null;
        return $this->save();
    }

    /**
     * Mark that user has taken action on this job
     * FIX (2025-12-02): Stored in DB so ALL users see the same state
     *
     * @return bool
     */
    public function markUserActionTaken(): bool
    {
        return $this->updateMetadata(['user_action_taken' => true]);
    }

    /**
     * Check if user has already taken action on this job
     * FIX (2025-12-02): Read from DB metadata for cross-user sync
     *
     * @return bool
     */
    public function isUserActionTaken(): bool
    {
        return $this->getMetadataValue('user_action_taken', false) === true;
    }

    /**
     * Update metadata with new data (merge with existing)
     *
     * @param array $newData Data to merge into metadata
     * @return bool
     */
    public function updateMetadata(array $newData): bool
    {
        $existing = $this->metadata ?? [];
        $this->metadata = array_merge($existing, $newData);
        return $this->save();
    }

    /**
     * Get specific metadata value
     *
     * @param string $key Metadata key
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Check if job has action button
     *
     * @return bool
     */
    public function hasActionButton(): bool
    {
        return !empty($this->action_button);
    }

    /**
     * Get human-readable job type label
     * ETAP_07c FAZA 4: Uses config/job_types.php
     *
     * @return string
     */
    public function getJobTypeLabel(): string
    {
        $config = config("job_types.{$this->job_type}", config('job_types.default'));
        return $config['label'] ?? ucfirst($this->job_type);
    }

    /**
     * Get full job type configuration
     * ETAP_07c FAZA 4: Uses config/job_types.php
     *
     * @return array
     */
    public function getJobTypeConfig(): array
    {
        return config("job_types.{$this->job_type}", config('job_types.default'));
    }

    /**
     * Get job type icon name
     * ETAP_07c FAZA 4: Uses config/job_types.php
     *
     * @return string
     */
    public function getJobTypeIcon(): string
    {
        $config = $this->getJobTypeConfig();
        return $config['icon'] ?? 'cog';
    }

    /**
     * Get job type color
     * ETAP_07c FAZA 4: Uses config/job_types.php
     *
     * @return string
     */
    public function getJobTypeColor(): string
    {
        $config = $this->getJobTypeConfig();
        return $config['color'] ?? 'gray';
    }

    /**
     * Check if job is cancellable
     * ETAP_07c FAZA 4: Uses config/job_types.php
     *
     * @return bool
     */
    public function isCancellable(): bool
    {
        $config = $this->getJobTypeConfig();
        return $config['cancellable'] ?? false;
    }

    /**
     * Check if job requires user confirmation
     * ETAP_07c FAZA 4: Uses config/job_types.php
     *
     * @return bool
     */
    public function requiresConfirmation(): bool
    {
        $config = $this->getJobTypeConfig();
        return $config['requires_confirmation'] ?? false;
    }

    /**
     * Get human-readable status label
     *
     * @return string
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Oczekuje',
            'running' => 'W trakcie',
            'completed' => 'Ukonczone',
            'failed' => 'Blad',
            'cancelled' => 'Anulowane',
            'awaiting_user' => 'Oczekuje na akcje',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get CSS class for status badge
     *
     * @return string
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'pending' => 'bg-gray-500',
            'running' => 'bg-blue-500',
            'completed' => 'bg-green-500',
            'failed' => 'bg-red-500',
            'cancelled' => 'bg-orange-500',
            'awaiting_user' => 'bg-yellow-500',
            default => 'bg-gray-500',
        };
    }

    /**
     * Cancel the job by user action
     *
     * FIX (2025-12-10): Added method to properly cancel jobs
     *
     * @return bool
     */
    public function cancelByUser(): bool
    {
        if (!in_array($this->status, ['running', 'pending', 'awaiting_user'])) {
            return false;
        }

        $this->status = 'cancelled';
        $this->completed_at = now();
        $this->updateMetadata([
            'cancelled_by_user' => true,
            'cancelled_at' => now()->toDateTimeString(),
        ]);

        return $this->save();
    }
}
