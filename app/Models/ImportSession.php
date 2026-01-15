<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * ImportSession Model
 *
 * ETAP_06 Import/Export - System IMPORT DO PPM
 *
 * Reprezentuje sesje importu produktow - grupuje wszystkie produkty
 * zaimportowane w jednej akcji (paste SKU, CSV, Excel, ERP).
 *
 * @property int $id
 * @property string $uuid
 * @property string $session_name
 * @property string $import_method
 * @property string|null $import_source_file
 * @property array|null $parsed_data
 * @property int $total_rows
 * @property int $products_created
 * @property int $products_published
 * @property int $products_failed
 * @property int $products_skipped
 * @property string $status
 * @property array|null $error_log
 * @property int $imported_by
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection|PendingProduct[] $pendingProducts
 *
 * @package App\Models
 * @since 2025-12-08
 */
class ImportSession extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'import_sessions';

    /**
     * Import method constants
     */
    public const METHOD_PASTE_SKU = 'paste_sku';
    public const METHOD_PASTE_SKU_NAME = 'paste_sku_name';
    public const METHOD_CSV = 'csv';
    public const METHOD_EXCEL = 'excel';
    public const METHOD_ERP = 'erp';

    /**
     * Status constants
     */
    public const STATUS_PARSING = 'parsing';
    public const STATUS_READY = 'ready';
    public const STATUS_PUBLISHING = 'publishing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'session_name',
        'import_method',
        'import_source_file',
        'parsed_data',
        'total_rows',
        'products_created',
        'products_published',
        'products_failed',
        'products_skipped',
        'status',
        'error_log',
        'imported_by',
        'started_at',
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'parsed_data' => 'array',
            'error_log' => 'array',
            'total_rows' => 'integer',
            'products_created' => 'integer',
            'products_published' => 'integer',
            'products_failed' => 'integer',
            'products_skipped' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

    protected static function boot(): void
    {
        parent::boot();

        // Auto-generate UUID
        static::creating(function (ImportSession $session) {
            if (empty($session->uuid)) {
                $session->uuid = (string) Str::uuid();
            }

            if (empty($session->session_name)) {
                $session->session_name = 'Import ' . now()->format('Y-m-d H:i');
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * User who created this import session
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    /**
     * Pending products in this import session
     */
    public function pendingProducts(): HasMany
    {
        return $this->hasMany(PendingProduct::class, 'import_session_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Active sessions (not completed, failed, or cancelled)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_PARSING,
            self::STATUS_READY,
            self::STATUS_PUBLISHING,
        ]);
    }

    /**
     * Scope: Completed sessions
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Failed sessions
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope: By import method
     */
    public function scopeByMethod(Builder $query, string $method): Builder
    {
        return $query->where('import_method', $method);
    }

    /**
     * Scope: By user
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('imported_by', $userId);
    }

    /**
     * Scope: Recent (last N days)
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS MANAGEMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Mark session as parsing started
     */
    public function markAsParsing(): void
    {
        $this->update([
            'status' => self::STATUS_PARSING,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark session as ready for editing/publication
     */
    public function markAsReady(int $productsCreated, int $skipped = 0): void
    {
        $this->update([
            'status' => self::STATUS_READY,
            'products_created' => $productsCreated,
            'products_skipped' => $skipped,
        ]);
    }

    /**
     * Mark session as publishing in progress
     */
    public function markAsPublishing(): void
    {
        $this->update([
            'status' => self::STATUS_PUBLISHING,
        ]);
    }

    /**
     * Mark session as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'products_published' => $this->pendingProducts()
                ->whereNotNull('published_at')
                ->count(),
        ]);
    }

    /**
     * Mark session as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->addError('session', $errorMessage);

        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark session as cancelled
     */
    public function markAsCancelled(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'completed_at' => now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ERROR TRACKING
    |--------------------------------------------------------------------------
    */

    /**
     * Add error to error log
     */
    public function addError(string $identifier, string $message): void
    {
        $errors = $this->error_log ?? [];
        $errors[] = [
            'identifier' => $identifier,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->update(['error_log' => $errors]);
    }

    /**
     * Check if session has errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->error_log);
    }

    /**
     * Get error count
     */
    public function getErrorCount(): int
    {
        return count($this->error_log ?? []);
    }

    /*
    |--------------------------------------------------------------------------
    | STATISTICS
    |--------------------------------------------------------------------------
    */

    /**
     * Get session statistics
     */
    public function getStats(): array
    {
        $pendingCount = $this->pendingProducts()->count();
        $readyCount = $this->pendingProducts()->where('is_ready_for_publish', true)->count();
        $publishedCount = $this->pendingProducts()->whereNotNull('published_at')->count();

        return [
            'total_rows' => $this->total_rows,
            'products_created' => $this->products_created,
            'products_skipped' => $this->products_skipped,
            'products_published' => $publishedCount,
            'products_failed' => $this->products_failed,
            'pending_count' => $pendingCount,
            'ready_count' => $readyCount,
            'completion_percentage' => $this->total_rows > 0
                ? round(($publishedCount / $this->total_rows) * 100)
                : 0,
            'duration_seconds' => $this->started_at && $this->completed_at
                ? $this->completed_at->diffInSeconds($this->started_at)
                : null,
        ];
    }

    /**
     * Get average completion of pending products
     */
    public function getAverageCompletion(): float
    {
        return $this->pendingProducts()->avg('completion_percentage') ?? 0;
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Get human-readable import method name
     */
    public function getMethodLabelAttribute(): string
    {
        return match ($this->import_method) {
            self::METHOD_PASTE_SKU => 'Wklej SKU',
            self::METHOD_PASTE_SKU_NAME => 'Wklej SKU + Nazwa',
            self::METHOD_CSV => 'Plik CSV',
            self::METHOD_EXCEL => 'Plik Excel',
            self::METHOD_ERP => 'Import ERP',
            default => $this->import_method,
        };
    }

    /**
     * Get human-readable status name
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PARSING => 'Parsowanie...',
            self::STATUS_READY => 'Gotowe',
            self::STATUS_PUBLISHING => 'Publikowanie...',
            self::STATUS_COMPLETED => 'Zakonczone',
            self::STATUS_FAILED => 'Blad',
            self::STATUS_CANCELLED => 'Anulowane',
            default => $this->status,
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PARSING => 'yellow',
            self::STATUS_READY => 'blue',
            self::STATUS_PUBLISHING => 'indigo',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_FAILED => 'red',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if session is in progress
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, [
            self::STATUS_PARSING,
            self::STATUS_READY,
            self::STATUS_PUBLISHING,
        ]);
    }

    /**
     * Check if session is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if session failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Get available import methods
     */
    public static function getAvailableMethods(): array
    {
        return [
            self::METHOD_PASTE_SKU => 'Wklej liste SKU',
            self::METHOD_PASTE_SKU_NAME => 'Wklej SKU + Nazwa',
            self::METHOD_CSV => 'Import z CSV',
            self::METHOD_EXCEL => 'Import z Excel',
            // self::METHOD_ERP => 'Import z ERP', // Future: ETAP_08
        ];
    }
}
