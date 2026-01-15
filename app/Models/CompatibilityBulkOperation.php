<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Compatibility Bulk Operation Model
 *
 * ETAP_05d FAZA 1 - Audit logging for bulk compatibility operations
 *
 * PURPOSE:
 * - Track all bulk compatibility operations
 * - Enable undo/rollback of bulk changes
 * - Audit trail for compliance
 * - Performance monitoring (operation timing)
 *
 * OPERATION TYPES:
 * - add: Bulk add compatibility records
 * - remove: Bulk remove compatibility records
 * - verify: Bulk verify compatibility records
 * - copy: Copy compatibility from one shop to another
 * - apply_suggestions: Apply AI suggestions in bulk
 * - import: Import from Excel/CSV
 *
 * @property int $id
 * @property string $operation_type Type of bulk operation
 * @property int $user_id User who initiated operation
 * @property int|null $shop_id Shop context (null = global)
 * @property array $operation_data Input parameters (product_ids, vehicle_ids, etc.)
 * @property array|null $affected_records Records affected for undo
 * @property int $affected_rows Number of rows affected
 * @property int $success_count Successful operations count
 * @property int $error_count Failed operations count
 * @property string $status Operation status
 * @property string|null $error_message Error message if failed
 * @property array|null $error_details Detailed error info
 * @property Carbon|null $started_at When operation started
 * @property Carbon|null $completed_at When operation completed
 * @property int|null $duration_ms Operation duration in milliseconds
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read User $user
 * @property-read PrestaShopShop|null $shop
 */
class CompatibilityBulkOperation extends Model
{
    use HasFactory;

    /**
     * Table name
     */
    protected $table = 'compatibility_bulk_operations';

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'operation_type',
        'user_id',
        'shop_id',
        'operation_data',
        'affected_records',
        'affected_rows',
        'success_count',
        'error_count',
        'status',
        'error_message',
        'error_details',
        'started_at',
        'completed_at',
        'duration_ms',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'user_id' => 'integer',
        'shop_id' => 'integer',
        'operation_data' => 'array',
        'affected_records' => 'array',
        'affected_rows' => 'integer',
        'success_count' => 'integer',
        'error_count' => 'integer',
        'error_details' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_ms' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Operation Type Constants
     */
    public const TYPE_ADD = 'add';
    public const TYPE_REMOVE = 'remove';
    public const TYPE_VERIFY = 'verify';
    public const TYPE_COPY = 'copy';
    public const TYPE_APPLY_SUGGESTIONS = 'apply_suggestions';
    public const TYPE_IMPORT = 'import';

    /**
     * Status Constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * User who initiated this operation
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Shop context (null = global operation)
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Filter by operation type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('operation_type', $type);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Only completed operations
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Only failed operations
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope: Operations in progress
     */
    public function scopeInProgress($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    /**
     * Scope: Filter by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by shop
     */
    public function scopeByShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope: Global operations (no shop context)
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('shop_id');
    }

    /**
     * Scope: Recent operations (last 24h)
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
     * Start the operation (mark as processing)
     */
    public function start(): void
    {
        $this->status = self::STATUS_PROCESSING;
        $this->started_at = now();
        $this->save();
    }

    /**
     * Complete the operation successfully
     */
    public function complete(int $successCount, int $errorCount = 0, ?array $affectedRecords = null): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();
        $this->success_count = $successCount;
        $this->error_count = $errorCount;
        $this->affected_rows = $successCount + $errorCount;

        if ($affectedRecords !== null) {
            $this->affected_records = $affectedRecords;
        }

        // Calculate duration
        if ($this->started_at) {
            $this->duration_ms = $this->started_at->diffInMilliseconds(now());
        }

        $this->save();
    }

    /**
     * Mark operation as failed
     */
    public function fail(string $message, ?array $details = null): void
    {
        $this->status = self::STATUS_FAILED;
        $this->completed_at = now();
        $this->error_message = $message;
        $this->error_details = $details;

        // Calculate duration
        if ($this->started_at) {
            $this->duration_ms = $this->started_at->diffInMilliseconds(now());
        }

        $this->save();
    }

    /**
     * Cancel the operation
     */
    public function cancel(): void
    {
        $this->status = self::STATUS_CANCELLED;
        $this->completed_at = now();

        if ($this->started_at) {
            $this->duration_ms = $this->started_at->diffInMilliseconds(now());
        }

        $this->save();
    }

    /**
     * Check if operation is undoable (has affected_records)
     */
    public function isUndoable(): bool
    {
        return $this->status === self::STATUS_COMPLETED
            && !empty($this->affected_records)
            && in_array($this->operation_type, [self::TYPE_ADD, self::TYPE_REMOVE, self::TYPE_VERIFY]);
    }

    /**
     * Check if operation is in progress
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    /**
     * Check if operation is completed (success or fail)
     */
    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED]);
    }

    /**
     * Get human-readable operation type
     */
    public function getTypeLabel(): string
    {
        return match ($this->operation_type) {
            self::TYPE_ADD => 'Dodawanie dopasowań',
            self::TYPE_REMOVE => 'Usuwanie dopasowań',
            self::TYPE_VERIFY => 'Weryfikacja dopasowań',
            self::TYPE_COPY => 'Kopiowanie między sklepami',
            self::TYPE_APPLY_SUGGESTIONS => 'Stosowanie sugestii AI',
            self::TYPE_IMPORT => 'Import z pliku',
            default => 'Nieznana operacja',
        };
    }

    /**
     * Get human-readable status
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Oczekuje',
            self::STATUS_PROCESSING => 'W trakcie',
            self::STATUS_COMPLETED => 'Zakonczona',
            self::STATUS_FAILED => 'Niepowodzenie',
            self::STATUS_CANCELLED => 'Anulowana',
            default => 'Nieznany status',
        };
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'secondary',
            self::STATUS_PROCESSING => 'info',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Get formatted duration string
     */
    public function getFormattedDuration(): string
    {
        if ($this->duration_ms === null) {
            return '-';
        }

        if ($this->duration_ms < 1000) {
            return $this->duration_ms . 'ms';
        }

        $seconds = round($this->duration_ms / 1000, 1);
        if ($seconds < 60) {
            return $seconds . 's';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = round($seconds % 60, 1);
        return "{$minutes}m {$remainingSeconds}s";
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRate(): float
    {
        if ($this->affected_rows === 0) {
            return 0.0;
        }

        return round(($this->success_count / $this->affected_rows) * 100, 2);
    }

    /**
     * Create a new bulk operation
     */
    public static function createOperation(
        string $type,
        User $user,
        array $operationData,
        ?PrestaShopShop $shop = null
    ): self {
        return self::create([
            'operation_type' => $type,
            'user_id' => $user->id,
            'shop_id' => $shop?->id,
            'operation_data' => $operationData,
            'status' => self::STATUS_PENDING,
        ]);
    }
}
