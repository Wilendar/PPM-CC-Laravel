<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Conflict Log Model
 *
 * Tracks SKU conflicts during import operations.
 * Enables manual conflict resolution workflow with full audit trail.
 *
 * @property int $id
 * @property int $import_batch_id
 * @property string $sku
 * @property string $conflict_type duplicate_sku|validation_error|missing_dependency
 * @property array $existing_data Current DB data (JSON)
 * @property array $new_data Incoming import data (JSON)
 * @property string $resolution_status pending|resolved|ignored
 * @property int|null $resolved_by_user_id
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property string|null $resolution_notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ConflictLog extends Model
{
    use HasFactory;

    /**
     * Table name
     */
    protected $table = 'conflict_logs';

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'import_batch_id',
        'sku',
        'conflict_type',
        'existing_data',
        'new_data',
        'resolution_status',
        'resolved_by_user_id',
        'resolved_at',
        'resolution_notes',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'import_batch_id' => 'integer',
        'existing_data' => 'array',
        'new_data' => 'array',
        'resolved_by_user_id' => 'integer',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Parent import batch
     */
    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    /**
     * User who resolved the conflict
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Pending conflicts (unresolved)
     */
    public function scopePending($query)
    {
        return $query->where('resolution_status', 'pending');
    }

    /**
     * Scope: Resolved conflicts
     */
    public function scopeResolved($query)
    {
        return $query->where('resolution_status', 'resolved');
    }

    /**
     * Scope: Ignored conflicts
     */
    public function scopeIgnored($query)
    {
        return $query->where('resolution_status', 'ignored');
    }

    /**
     * Scope: Conflicts for specific import batch
     */
    public function scopeForBatch($query, int $batchId)
    {
        return $query->where('import_batch_id', $batchId);
    }

    /**
     * Scope: Conflicts by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('conflict_type', $type);
    }

    /**
     * Scope: Duplicate SKU conflicts
     */
    public function scopeDuplicateSku($query)
    {
        return $query->where('conflict_type', 'duplicate_sku');
    }

    /**
     * Scope: Validation error conflicts
     */
    public function scopeValidationError($query)
    {
        return $query->where('conflict_type', 'validation_error');
    }

    /**
     * Scope: Missing dependency conflicts
     */
    public function scopeMissingDependency($query)
    {
        return $query->where('conflict_type', 'missing_dependency');
    }

    /**
     * Scope: Find by SKU
     */
    public function scopeBySku($query, string $sku)
    {
        return $query->where('sku', $sku);
    }

    /*
    |--------------------------------------------------------------------------
    | INSTANCE METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Resolve conflict with specific strategy
     *
     * @param int $userId User resolving the conflict
     * @param string $strategy Resolution strategy (e.g., 'use_new', 'use_existing', 'merge')
     * @param string|null $notes Additional resolution notes
     */
    public function resolve(int $userId, string $strategy, ?string $notes = null): void
    {
        $resolutionNotes = $notes ?? "Strategy: {$strategy}";

        $this->update([
            'resolution_status' => 'resolved',
            'resolved_by_user_id' => $userId,
            'resolved_at' => now(),
            'resolution_notes' => $resolutionNotes,
        ]);
    }

    /**
     * Mark conflict as ignored
     */
    public function ignore(): void
    {
        $this->update([
            'resolution_status' => 'ignored',
        ]);
    }

    /**
     * Reopen conflict (reset to pending)
     */
    public function reopen(): void
    {
        $this->update([
            'resolution_status' => 'pending',
            'resolved_by_user_id' => null,
            'resolved_at' => null,
            'resolution_notes' => null,
        ]);
    }

    /**
     * Check if conflict is pending
     */
    public function isPending(): bool
    {
        return $this->resolution_status === 'pending';
    }

    /**
     * Check if conflict is resolved
     */
    public function isResolved(): bool
    {
        return $this->resolution_status === 'resolved';
    }

    /**
     * Check if conflict is ignored
     */
    public function isIgnored(): bool
    {
        return $this->resolution_status === 'ignored';
    }

    /**
     * Get field differences between existing and new data
     *
     * @return array Fields that differ
     */
    public function getDifferences(): array
    {
        $differences = [];

        foreach ($this->new_data as $key => $newValue) {
            $existingValue = $this->existing_data[$key] ?? null;

            if ($existingValue !== $newValue) {
                $differences[$key] = [
                    'existing' => $existingValue,
                    'new' => $newValue,
                ];
            }
        }

        return $differences;
    }

    /**
     * Get specific field from existing data
     */
    public function getExistingField(string $field): mixed
    {
        return $this->existing_data[$field] ?? null;
    }

    /**
     * Get specific field from new data
     */
    public function getNewField(string $field): mixed
    {
        return $this->new_data[$field] ?? null;
    }
}
