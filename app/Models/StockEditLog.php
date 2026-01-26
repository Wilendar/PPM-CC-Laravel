<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model do śledzenia zmian w stanach magazynowych.
 *
 * Akcje:
 * - unlock: odblokowanie kolumny do edycji
 * - lock: zablokowanie kolumny
 * - edit: edycja wartości
 * - sync_to_erp: synchronizacja do systemu ERP
 *
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property int|null $warehouse_id
 * @property string $column_name
 * @property string $action
 * @property float|null $old_value
 * @property float|null $new_value
 * @property int|null $erp_connection_id
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 */
class StockEditLog extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     * We only use created_at, no updated_at.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'warehouse_id',
        'column_name',
        'action',
        'old_value',
        'new_value',
        'erp_connection_id',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'old_value' => 'decimal:4',
        'new_value' => 'decimal:4',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Column name constants.
     */
    public const COLUMN_QUANTITY = 'quantity';
    public const COLUMN_RESERVED = 'reserved';
    public const COLUMN_MINIMUM = 'minimum';

    /**
     * Action constants.
     */
    public const ACTION_UNLOCK = 'unlock';
    public const ACTION_LOCK = 'lock';
    public const ACTION_EDIT = 'edit';
    public const ACTION_SYNC_TO_ERP = 'sync_to_erp';

    /**
     * Available columns.
     */
    public static array $columns = [
        self::COLUMN_QUANTITY,
        self::COLUMN_RESERVED,
        self::COLUMN_MINIMUM,
    ];

    /**
     * Available actions.
     */
    public static array $actions = [
        self::ACTION_UNLOCK,
        self::ACTION_LOCK,
        self::ACTION_EDIT,
        self::ACTION_SYNC_TO_ERP,
    ];

    /**
     * User who made the change.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Product that was changed.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Warehouse (if applicable).
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * ERP connection (if synced).
     */
    public function erpConnection(): BelongsTo
    {
        return $this->belongsTo(ERPConnection::class, 'erp_connection_id');
    }

    /**
     * Scope: filter by product.
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope: filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: filter by column.
     */
    public function scopeForColumn($query, string $column)
    {
        return $query->where('column_name', $column);
    }

    /**
     * Scope: filter by action.
     */
    public function scopeWithAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: only edits.
     */
    public function scopeOnlyEdits($query)
    {
        return $query->where('action', self::ACTION_EDIT);
    }

    /**
     * Scope: only syncs.
     */
    public function scopeOnlySyncs($query)
    {
        return $query->where('action', self::ACTION_SYNC_TO_ERP);
    }

    /**
     * Scope: recent (last N days).
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get human-readable column name.
     */
    public function getColumnLabelAttribute(): string
    {
        return match ($this->column_name) {
            self::COLUMN_QUANTITY => 'Stan dostępny',
            self::COLUMN_RESERVED => 'Zarezerwowane',
            self::COLUMN_MINIMUM => 'Minimum',
            default => $this->column_name,
        };
    }

    /**
     * Get human-readable action name.
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_UNLOCK => 'Odblokowanie',
            self::ACTION_LOCK => 'Zablokowanie',
            self::ACTION_EDIT => 'Edycja',
            self::ACTION_SYNC_TO_ERP => 'Synchronizacja ERP',
            default => $this->action,
        };
    }

    /**
     * Log an unlock action.
     */
    public static function logUnlock(
        int $userId,
        int $productId,
        string $column,
        ?array $metadata = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'product_id' => $productId,
            'column_name' => $column,
            'action' => self::ACTION_UNLOCK,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log a lock action.
     */
    public static function logLock(
        int $userId,
        int $productId,
        string $column,
        ?array $metadata = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'product_id' => $productId,
            'column_name' => $column,
            'action' => self::ACTION_LOCK,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log an edit action.
     */
    public static function logEdit(
        int $userId,
        int $productId,
        int $warehouseId,
        string $column,
        ?float $oldValue,
        ?float $newValue,
        ?array $metadata = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'column_name' => $column,
            'action' => self::ACTION_EDIT,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log a sync to ERP action.
     */
    public static function logSyncToErp(
        int $userId,
        int $productId,
        ?int $warehouseId,
        string $column,
        int $erpConnectionId,
        ?array $metadata = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'column_name' => $column,
            'action' => self::ACTION_SYNC_TO_ERP,
            'erp_connection_id' => $erpConnectionId,
            'metadata' => $metadata,
        ]);
    }
}
