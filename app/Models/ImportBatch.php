<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Import Batch Model
 *
 * Tracks import operations (XLSX and PrestaShop API) with full audit trail.
 * Provides real-time progress monitoring and statistics.
 *
 * @property int $id
 * @property int $user_id
 * @property string $import_type xlsx|prestashop_api
 * @property string|null $filename
 * @property int|null $shop_id
 * @property string $status pending|processing|completed|failed
 * @property int $total_rows
 * @property int $processed_rows
 * @property int $imported_products
 * @property int $failed_products
 * @property int $conflicts_count
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ImportBatch extends Model
{
    use HasFactory;

    /**
     * Table name
     */
    protected $table = 'import_batches';

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'user_id',
        'import_type',
        'filename',
        'shop_id',
        'status',
        'total_rows',
        'processed_rows',
        'imported_products',
        'failed_products',
        'conflicts_count',
        'started_at',
        'completed_at',
        'error_message',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'user_id' => 'integer',
        'shop_id' => 'integer',
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'imported_products' => 'integer',
        'failed_products' => 'integer',
        'conflicts_count' => 'integer',
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
     * User who initiated the import
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * PrestaShop shop (for API imports)
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    /**
     * Conflicts detected during import
     */
    public function conflicts(): HasMany
    {
        return $this->hasMany(ConflictLog::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Filter by import type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('import_type', $type);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Recent batches (last N days)
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: XLSX imports only
     */
    public function scopeXlsx($query)
    {
        return $query->where('import_type', 'xlsx');
    }

    /**
     * Scope: PrestaShop API imports only
     */
    public function scopePrestashopApi($query)
    {
        return $query->where('import_type', 'prestashop_api');
    }

    /**
     * Scope: Pending batches
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Processing batches
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope: Completed batches
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Failed batches
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /*
    |--------------------------------------------------------------------------
    | INSTANCE METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Mark batch as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark batch as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark batch as failed with error message
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $error,
        ]);
    }

    /**
     * Increment progress counters
     *
     * @param int $imported Successfully imported products
     * @param int $failed Failed products
     * @param int $conflicts Conflicts detected
     */
    public function incrementProgress(int $imported = 0, int $failed = 0, int $conflicts = 0): void
    {
        $this->increment('processed_rows');

        if ($imported > 0) {
            $this->increment('imported_products', $imported);
        }

        if ($failed > 0) {
            $this->increment('failed_products', $failed);
        }

        if ($conflicts > 0) {
            $this->increment('conflicts_count', $conflicts);
        }
    }

    /**
     * Get progress percentage (0-100)
     */
    public function getProgressPercentage(): int
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return (int) round(($this->processed_rows / $this->total_rows) * 100);
    }

    /**
     * Get duration in seconds (if completed)
     */
    public function getDurationInSeconds(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->completed_at);
    }

    /**
     * Check if batch is still running
     */
    public function isRunning(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if batch is finished (completed or failed)
     */
    public function isFinished(): bool
    {
        return in_array($this->status, ['completed', 'failed']);
    }

    /**
     * Check if batch has unresolved conflicts
     */
    public function hasUnresolvedConflicts(): bool
    {
        return $this->conflicts()->pending()->exists();
    }
}
