<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * PublishHistory Model
 *
 * ETAP_06 Import/Export - Audit Trail dla publikacji produktow
 *
 * Kazda operacja publikacji (PendingProduct -> Product) jest
 * logowana tutaj wraz z informacja o sync do PrestaShop.
 * Umozliwia pelny audit trail i analize sukcesu publikacji.
 *
 * @property int $id
 * @property int|null $pending_product_id
 * @property int $product_id
 * @property int $published_by
 * @property \Carbon\Carbon $published_at
 * @property string $sku_snapshot
 * @property string|null $name_snapshot
 * @property array $published_shops
 * @property array $published_categories
 * @property int $published_media_count
 * @property int $published_variants_count
 * @property array|null $sync_jobs_dispatched
 * @property string $sync_status
 * @property \Carbon\Carbon|null $sync_completed_at
 * @property array|null $sync_errors
 * @property string $publish_mode
 * @property string|null $batch_id
 * @property int|null $processing_time_ms
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read PendingProduct|null $pendingProduct
 * @property-read Product $product
 * @property-read User $publisher
 *
 * @package App\Models
 * @since 2025-12-08
 */
class PublishHistory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'publish_history';

    /**
     * Sync status constants
     */
    public const SYNC_PENDING = 'pending';
    public const SYNC_IN_PROGRESS = 'in_progress';
    public const SYNC_COMPLETED = 'completed';
    public const SYNC_PARTIAL = 'partial';
    public const SYNC_FAILED = 'failed';

    /**
     * Publish mode constants
     */
    public const MODE_SINGLE = 'single';
    public const MODE_BULK = 'bulk';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'pending_product_id',
        'product_id',
        'published_by',
        'published_at',
        'sku_snapshot',
        'name_snapshot',
        'published_shops',
        'published_categories',
        'published_media_count',
        'published_variants_count',
        'sync_jobs_dispatched',
        'sync_status',
        'sync_completed_at',
        'sync_errors',
        'publish_mode',
        'batch_id',
        'processing_time_ms',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'published_shops' => 'array',
            'published_categories' => 'array',
            'sync_jobs_dispatched' => 'array',
            'sync_errors' => 'array',
            'published_media_count' => 'integer',
            'published_variants_count' => 'integer',
            'processing_time_ms' => 'integer',
            'published_at' => 'datetime',
            'sync_completed_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Source pending product (may be null if soft-deleted)
     */
    public function pendingProduct(): BelongsTo
    {
        return $this->belongsTo(PendingProduct::class, 'pending_product_id')
            ->withTrashed();
    }

    /**
     * Created product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * User who published
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: By sync status
     */
    public function scopeBySyncStatus(Builder $query, string $status): Builder
    {
        return $query->where('sync_status', $status);
    }

    /**
     * Scope: Pending sync
     */
    public function scopePendingSync(Builder $query): Builder
    {
        return $query->where('sync_status', self::SYNC_PENDING);
    }

    /**
     * Scope: Completed sync
     */
    public function scopeCompletedSync(Builder $query): Builder
    {
        return $query->where('sync_status', self::SYNC_COMPLETED);
    }

    /**
     * Scope: Failed sync
     */
    public function scopeFailedSync(Builder $query): Builder
    {
        return $query->where('sync_status', self::SYNC_FAILED);
    }

    /**
     * Scope: Partial sync (some shops failed)
     */
    public function scopePartialSync(Builder $query): Builder
    {
        return $query->where('sync_status', self::SYNC_PARTIAL);
    }

    /**
     * Scope: Has sync errors
     */
    public function scopeHasSyncErrors(Builder $query): Builder
    {
        return $query->whereIn('sync_status', [self::SYNC_FAILED, self::SYNC_PARTIAL]);
    }

    /**
     * Scope: By batch (bulk operations)
     */
    public function scopeByBatch(Builder $query, string $batchId): Builder
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Scope: Bulk mode only
     */
    public function scopeBulkMode(Builder $query): Builder
    {
        return $query->where('publish_mode', self::MODE_BULK);
    }

    /**
     * Scope: Single mode only
     */
    public function scopeSingleMode(Builder $query): Builder
    {
        return $query->where('publish_mode', self::MODE_SINGLE);
    }

    /**
     * Scope: By publisher
     */
    public function scopeByPublisher(Builder $query, int $userId): Builder
    {
        return $query->where('published_by', $userId);
    }

    /**
     * Scope: Published today
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('published_at', today());
    }

    /**
     * Scope: Published in date range
     */
    public function scopeInDateRange(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('published_at', [$from, $to]);
    }

    /**
     * Scope: Recent (last N days)
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('published_at', '>=', now()->subDays($days));
    }

    /*
    |--------------------------------------------------------------------------
    | SYNC STATUS MANAGEMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Mark sync as in progress
     */
    public function markSyncInProgress(): void
    {
        $this->update(['sync_status' => self::SYNC_IN_PROGRESS]);
    }

    /**
     * Mark sync as completed
     */
    public function markSyncCompleted(): void
    {
        $this->update([
            'sync_status' => self::SYNC_COMPLETED,
            'sync_completed_at' => now(),
        ]);
    }

    /**
     * Mark sync as failed
     */
    public function markSyncFailed(array $errors = []): void
    {
        $this->update([
            'sync_status' => self::SYNC_FAILED,
            'sync_completed_at' => now(),
            'sync_errors' => $errors,
        ]);
    }

    /**
     * Mark sync as partial (some shops failed)
     */
    public function markSyncPartial(array $errors = []): void
    {
        $this->update([
            'sync_status' => self::SYNC_PARTIAL,
            'sync_completed_at' => now(),
            'sync_errors' => $errors,
        ]);
    }

    /**
     * Add sync job UUID to tracking
     */
    public function addSyncJob(string $jobUuid): void
    {
        $jobs = $this->sync_jobs_dispatched ?? [];
        $jobs[] = $jobUuid;

        $this->update(['sync_jobs_dispatched' => $jobs]);
    }

    /**
     * Add sync error
     */
    public function addSyncError(string $shopId, string $message): void
    {
        $errors = $this->sync_errors ?? [];
        $errors[] = [
            'shop_id' => $shopId,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->update(['sync_errors' => $errors]);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Get sync status label
     */
    public function getSyncStatusLabelAttribute(): string
    {
        return match ($this->sync_status) {
            self::SYNC_PENDING => 'Oczekuje',
            self::SYNC_IN_PROGRESS => 'W trakcie...',
            self::SYNC_COMPLETED => 'Zakonczone',
            self::SYNC_PARTIAL => 'Czesciowo',
            self::SYNC_FAILED => 'Blad',
            default => $this->sync_status,
        };
    }

    /**
     * Get sync status color for UI
     */
    public function getSyncStatusColorAttribute(): string
    {
        return match ($this->sync_status) {
            self::SYNC_PENDING => 'yellow',
            self::SYNC_IN_PROGRESS => 'blue',
            self::SYNC_COMPLETED => 'green',
            self::SYNC_PARTIAL => 'orange',
            self::SYNC_FAILED => 'red',
            default => 'gray',
        };
    }

    /**
     * Get publish mode label
     */
    public function getPublishModeLabelAttribute(): string
    {
        return match ($this->publish_mode) {
            self::MODE_SINGLE => 'Pojedyncza',
            self::MODE_BULK => 'Masowa',
            default => $this->publish_mode,
        };
    }

    /**
     * Get processing time as human readable
     */
    public function getProcessingTimeFormattedAttribute(): ?string
    {
        if (!$this->processing_time_ms) {
            return null;
        }

        if ($this->processing_time_ms < 1000) {
            return $this->processing_time_ms . 'ms';
        }

        $seconds = round($this->processing_time_ms / 1000, 2);
        return $seconds . 's';
    }

    /**
     * Get shops count
     */
    public function getShopsCountAttribute(): int
    {
        return count($this->published_shops ?? []);
    }

    /**
     * Get categories count
     */
    public function getCategoriesCountAttribute(): int
    {
        return count($this->published_categories ?? []);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if sync completed successfully
     */
    public function isSyncSuccessful(): bool
    {
        return $this->sync_status === self::SYNC_COMPLETED;
    }

    /**
     * Check if sync has any errors
     */
    public function hasSyncErrors(): bool
    {
        return in_array($this->sync_status, [self::SYNC_FAILED, self::SYNC_PARTIAL])
            || !empty($this->sync_errors);
    }

    /**
     * Check if sync is pending or in progress
     */
    public function isSyncPending(): bool
    {
        return in_array($this->sync_status, [self::SYNC_PENDING, self::SYNC_IN_PROGRESS]);
    }

    /**
     * Check if this was bulk publish
     */
    public function isBulkPublish(): bool
    {
        return $this->publish_mode === self::MODE_BULK;
    }

    /**
     * Get sync error count
     */
    public function getSyncErrorCount(): int
    {
        return count($this->sync_errors ?? []);
    }

    /**
     * Get dispatched job count
     */
    public function getJobCount(): int
    {
        return count($this->sync_jobs_dispatched ?? []);
    }

    /*
    |--------------------------------------------------------------------------
    | FACTORY METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Create history entry for single publication
     */
    public static function createForSinglePublish(
        PendingProduct $pendingProduct,
        Product $product,
        User $user,
        array $shops,
        array $categories,
        int $mediaCount = 0,
        int $variantCount = 0,
        ?int $processingTimeMs = null
    ): self {
        return self::create([
            'pending_product_id' => $pendingProduct->id,
            'product_id' => $product->id,
            'published_by' => $user->id,
            'published_at' => now(),
            'sku_snapshot' => $pendingProduct->sku,
            'name_snapshot' => $pendingProduct->name,
            'published_shops' => $shops,
            'published_categories' => $categories,
            'published_media_count' => $mediaCount,
            'published_variants_count' => $variantCount,
            'sync_status' => self::SYNC_PENDING,
            'publish_mode' => self::MODE_SINGLE,
            'processing_time_ms' => $processingTimeMs,
        ]);
    }

    /**
     * Create history entry for bulk publication
     */
    public static function createForBulkPublish(
        PendingProduct $pendingProduct,
        Product $product,
        User $user,
        array $shops,
        array $categories,
        string $batchId,
        int $mediaCount = 0,
        int $variantCount = 0,
        ?int $processingTimeMs = null
    ): self {
        return self::create([
            'pending_product_id' => $pendingProduct->id,
            'product_id' => $product->id,
            'published_by' => $user->id,
            'published_at' => now(),
            'sku_snapshot' => $pendingProduct->sku,
            'name_snapshot' => $pendingProduct->name,
            'published_shops' => $shops,
            'published_categories' => $categories,
            'published_media_count' => $mediaCount,
            'published_variants_count' => $variantCount,
            'sync_status' => self::SYNC_PENDING,
            'publish_mode' => self::MODE_BULK,
            'batch_id' => $batchId,
            'processing_time_ms' => $processingTimeMs,
        ]);
    }

    /**
     * Generate new batch ID for bulk operations
     */
    public static function generateBatchId(): string
    {
        return (string) Str::uuid();
    }

    /*
    |--------------------------------------------------------------------------
    | STATISTICS
    |--------------------------------------------------------------------------
    */

    /**
     * Get batch statistics
     */
    public static function getBatchStats(string $batchId): array
    {
        $query = self::byBatch($batchId);

        return [
            'total' => $query->count(),
            'completed' => (clone $query)->completedSync()->count(),
            'failed' => (clone $query)->failedSync()->count(),
            'partial' => (clone $query)->partialSync()->count(),
            'pending' => (clone $query)->pendingSync()->count(),
            'total_media' => (clone $query)->sum('published_media_count'),
            'total_variants' => (clone $query)->sum('published_variants_count'),
            'avg_processing_time_ms' => (clone $query)->avg('processing_time_ms'),
        ];
    }

    /**
     * Get daily publication statistics
     */
    public static function getDailyStats($date = null): array
    {
        $date = $date ?? today();
        $query = self::whereDate('published_at', $date);

        return [
            'total_published' => $query->count(),
            'single_mode' => (clone $query)->singleMode()->count(),
            'bulk_mode' => (clone $query)->bulkMode()->count(),
            'sync_completed' => (clone $query)->completedSync()->count(),
            'sync_failed' => (clone $query)->failedSync()->count(),
            'total_media' => (clone $query)->sum('published_media_count'),
            'total_variants' => (clone $query)->sum('published_variants_count'),
            'unique_publishers' => (clone $query)->distinct('published_by')->count('published_by'),
        ];
    }
}
