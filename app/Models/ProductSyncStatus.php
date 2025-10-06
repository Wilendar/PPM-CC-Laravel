<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Product Sync Status Model
 *
 * Tracks synchronization status of products with PrestaShop shops
 *
 * Lifecycle: pending → syncing → synced/error/conflict/disabled
 *
 * @property int $id
 * @property int $product_id
 * @property int $shop_id
 * @property int|null $prestashop_product_id PrestaShop external product ID
 * @property string $sync_status pending|syncing|synced|error|conflict|disabled
 * @property \Carbon\Carbon|null $last_sync_at
 * @property \Carbon\Carbon|null $last_success_sync_at
 * @property string $sync_direction ppm_to_ps|ps_to_ppm|bidirectional
 * @property string|null $error_message
 * @property array|null $conflict_data
 * @property int $retry_count
 * @property int $max_retries
 * @property int $priority 1-10 (1=highest, 10=lowest)
 * @property string|null $checksum MD5 hash for change detection
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Product $product
 * @property-read PrestaShopShop $shop
 */
class ProductSyncStatus extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_sync_status';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'product_id',
        'shop_id',
        'prestashop_product_id',
        'sync_status',
        'last_sync_at',
        'last_success_sync_at',
        'sync_direction',
        'error_message',
        'conflict_data',
        'retry_count',
        'max_retries',
        'priority',
        'checksum',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_sync_at' => 'datetime',
        'last_success_sync_at' => 'datetime',
        'conflict_data' => 'array',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'priority' => 'integer',
        'prestashop_product_id' => 'integer',
    ];

    /**
     * Sync status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_SYNCING = 'syncing';
    public const STATUS_SYNCED = 'synced';
    public const STATUS_ERROR = 'error';
    public const STATUS_CONFLICT = 'conflict';
    public const STATUS_DISABLED = 'disabled';

    /**
     * Sync direction constants
     */
    public const DIRECTION_PPM_TO_PS = 'ppm_to_ps';
    public const DIRECTION_PS_TO_PPM = 'ps_to_ppm';
    public const DIRECTION_BIDIRECTIONAL = 'bidirectional';

    /**
     * Priority constants
     */
    public const PRIORITY_HIGHEST = 1;
    public const PRIORITY_NORMAL = 5;
    public const PRIORITY_LOWEST = 10;

    /**
     * Get the product that this sync status belongs to
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the shop that this sync status belongs to
     *
     * @return BelongsTo
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    /**
     * Scope: Filter by sync status
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('sync_status', $status);
    }

    /**
     * Scope: Pending sync
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('sync_status', self::STATUS_PENDING);
    }

    /**
     * Scope: Currently syncing
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSyncing($query)
    {
        return $query->where('sync_status', self::STATUS_SYNCING);
    }

    /**
     * Scope: Successfully synced
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSynced($query)
    {
        return $query->where('sync_status', self::STATUS_SYNCED);
    }

    /**
     * Scope: Error status
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeError($query)
    {
        return $query->where('sync_status', self::STATUS_ERROR);
    }

    /**
     * Scope: Conflict status
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConflict($query)
    {
        return $query->where('sync_status', self::STATUS_CONFLICT);
    }

    /**
     * Scope: Disabled
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDisabled($query)
    {
        return $query->where('sync_status', self::STATUS_DISABLED);
    }

    /**
     * Scope: Filter by shop
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
     * Scope: Filter by priority
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $priority
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPriority($query, int $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope: High priority (1-3)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', '<=', 3);
    }

    /**
     * Scope: Filter by direction
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDirection($query, string $direction)
    {
        return $query->where('sync_direction', $direction);
    }

    /**
     * Scope: Needs retry (has errors and under max retries)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNeedsRetry($query)
    {
        return $query->where('sync_status', self::STATUS_ERROR)
            ->whereColumn('retry_count', '<', 'max_retries');
    }

    /**
     * Check if sync is pending
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->sync_status === self::STATUS_PENDING;
    }

    /**
     * Check if currently syncing
     *
     * @return bool
     */
    public function isSyncing(): bool
    {
        return $this->sync_status === self::STATUS_SYNCING;
    }

    /**
     * Check if successfully synced
     *
     * @return bool
     */
    public function isSynced(): bool
    {
        return $this->sync_status === self::STATUS_SYNCED;
    }

    /**
     * Check if has error
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return $this->sync_status === self::STATUS_ERROR;
    }

    /**
     * Check if has conflict
     *
     * @return bool
     */
    public function hasConflict(): bool
    {
        return $this->sync_status === self::STATUS_CONFLICT;
    }

    /**
     * Check if disabled
     *
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->sync_status === self::STATUS_DISABLED;
    }

    /**
     * Check if can retry sync
     *
     * @return bool
     */
    public function canRetry(): bool
    {
        return $this->hasError() && $this->retry_count < $this->max_retries;
    }

    /**
     * Check if max retries exceeded
     *
     * @return bool
     */
    public function maxRetriesExceeded(): bool
    {
        return $this->retry_count >= $this->max_retries;
    }

    /**
     * Mark as syncing
     *
     * @return bool
     */
    public function markSyncing(): bool
    {
        return $this->update([
            'sync_status' => self::STATUS_SYNCING,
            'last_sync_at' => now(),
        ]);
    }

    /**
     * Mark as synced successfully
     *
     * @param int|null $externalId PrestaShop product ID
     * @param string|null $checksum
     * @return bool
     */
    public function markSynced(?int $externalId = null, ?string $checksum = null): bool
    {
        $data = [
            'sync_status' => self::STATUS_SYNCED,
            'last_success_sync_at' => now(),
            'error_message' => null,
            'retry_count' => 0,
        ];

        if ($externalId !== null) {
            $data['prestashop_product_id'] = $externalId;
        }

        if ($checksum !== null) {
            $data['checksum'] = $checksum;
        }

        return $this->update($data);
    }

    /**
     * Mark as error
     *
     * @param string $errorMessage
     * @return bool
     */
    public function markError(string $errorMessage): bool
    {
        return $this->update([
            'sync_status' => self::STATUS_ERROR,
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    /**
     * Mark as conflict
     *
     * @param array $conflictData
     * @return bool
     */
    public function markConflict(array $conflictData): bool
    {
        return $this->update([
            'sync_status' => self::STATUS_CONFLICT,
            'conflict_data' => $conflictData,
        ]);
    }

    /**
     * Mark as disabled
     *
     * @return bool
     */
    public function markDisabled(): bool
    {
        return $this->update([
            'sync_status' => self::STATUS_DISABLED,
        ]);
    }

    /**
     * Reset retry count
     *
     * @return bool
     */
    public function resetRetryCount(): bool
    {
        return $this->update([
            'retry_count' => 0,
            'error_message' => null,
        ]);
    }
}
