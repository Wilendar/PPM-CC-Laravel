<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Export Batch Model
 *
 * Tracks export operations (XLSX and PrestaShop API sync).
 * Provides export history and progress monitoring.
 *
 * @property int $id
 * @property int $user_id
 * @property string $export_type xlsx|prestashop_api
 * @property int|null $shop_id
 * @property string|null $filename
 * @property string $status pending|processing|completed|failed
 * @property int $total_products
 * @property int $exported_products
 * @property int $failed_products
 * @property array|null $filters Export filters (JSON)
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ExportBatch extends Model
{
    use HasFactory;

    /**
     * Table name
     */
    protected $table = 'export_batches';

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'user_id',
        'export_type',
        'shop_id',
        'filename',
        'status',
        'total_products',
        'exported_products',
        'failed_products',
        'filters',
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
        'total_products' => 'integer',
        'exported_products' => 'integer',
        'failed_products' => 'integer',
        'filters' => 'array',
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
     * User who initiated the export
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * PrestaShop shop (for API exports)
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
     * Scope: Filter by export type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('export_type', $type);
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
     * Scope: XLSX exports only
     */
    public function scopeXlsx($query)
    {
        return $query->where('export_type', 'xlsx');
    }

    /**
     * Scope: PrestaShop API exports only
     */
    public function scopePrestashopApi($query)
    {
        return $query->where('export_type', 'prestashop_api');
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
     * @param int $exported Successfully exported products
     * @param int $failed Failed products
     */
    public function incrementProgress(int $exported = 0, int $failed = 0): void
    {
        if ($exported > 0) {
            $this->increment('exported_products', $exported);
        }

        if ($failed > 0) {
            $this->increment('failed_products', $failed);
        }
    }

    /**
     * Get progress percentage (0-100)
     */
    public function getProgressPercentage(): int
    {
        if ($this->total_products === 0) {
            return 0;
        }

        $processed = $this->exported_products + $this->failed_products;
        return (int) round(($processed / $this->total_products) * 100);
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
     * Get specific filter value
     */
    public function getFilter(string $key): mixed
    {
        return $this->filters[$key] ?? null;
    }

    /**
     * Check if filter exists
     */
    public function hasFilter(string $key): bool
    {
        return isset($this->filters[$key]);
    }

    /**
     * Get all applied filters
     */
    public function getAppliedFilters(): array
    {
        return $this->filters ?? [];
    }
}
