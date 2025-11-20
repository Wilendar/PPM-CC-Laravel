<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

/**
 * PriceHistory Model - Audit trail systemu cenowego PPM-CC-Laravel
 *
 * Business Logic:
 * - Kompletny audit trail wszystkich zmian cen produktów
 * - Tracking zmian w PriceGroups i ProductPrices
 * - Support dla bulk price updates z batch tracking
 * - Polymorph relationships dla różnych source entities
 * - Retention policy dla historical data management
 *
 * Performance Features:
 * - Strategic indexing dla time-based queries
 * - JSON storage dla change details z efficient casting
 * - Partitioned table support dla large datasets
 * - Optimized scopes dla common audit queries
 *
 * @property int $id
 * @property string $historyable_type Source model type (PriceGroup, ProductPrice)
 * @property int $historyable_id Source model ID
 * @property string $action Action type (created, updated, deleted, bulk_update)
 * @property array $old_values Previous values przed zmianą
 * @property array $new_values New values po zmianie
 * @property array $changed_fields Lista changed fields names
 * @property string|null $change_reason Business reason for price change
 * @property string|null $batch_id Batch ID dla bulk operations
 * @property float|null $adjustment_percentage % adjustment w bulk updates
 * @property string|null $adjustment_type (percentage, fixed_amount, set_margin)
 * @property int|null $affected_products_count Products count w batch operation
 * @property string $source Source of change (admin_panel, api, import, erp_sync)
 * @property array|null $metadata Dodatkowe metadata (IP, browser, etc.)
 * @property int $created_by User who made the change
 * @property \Carbon\Carbon $created_at
 *
 * @property-read \Illuminate\Database\Eloquent\Model $historyable Source model
 * @property-read \App\Models\User $user User who made change
 * @property-read string $formatted_action
 * @property-read string $change_summary
 * @property-read bool $is_bulk_operation
 * @property-read array $significant_changes
 *
 * @method static \Illuminate\Database\Eloquent\Builder forModel(string $modelType, int $modelId)
 * @method static \Illuminate\Database\Eloquent\Builder byAction(string $action)
 * @method static \Illuminate\Database\Eloquent\Builder byUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder byBatch(string $batchId)
 * @method static \Illuminate\Database\Eloquent\Builder bySource(string $source)
 * @method static \Illuminate\Database\Eloquent\Builder inDateRange(Carbon $from, Carbon $to)
 * @method static \Illuminate\Database\Eloquent\Builder recent(int $days = 30)
 * @method static \Illuminate\Database\Eloquent\Builder bulkOperations()
 *
 * @package App\Models
 * @version FAZA 4 - PRICE MANAGEMENT
 * @since 2025-09-17
 */
class PriceHistory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'price_history';

    /**
     * Indicates if the model should be timestamped.
     * Only created_at, no updated_at needed for audit log
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'historyable_type',
        'historyable_id',
        'action',
        'old_values',
        'new_values',
        'changed_fields',
        'change_reason',
        'batch_id',
        'adjustment_percentage',
        'adjustment_type',
        'affected_products_count',
        'source',
        'metadata',
        'created_by',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'changed_fields' => 'array',
            'metadata' => 'array',
            'adjustment_percentage' => 'decimal:2',
            'affected_products_count' => 'integer',
            'created_by' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the owning model that has price history
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function historyable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * User who made the change
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS
    |--------------------------------------------------------------------------
    */

    /**
     * Get formatted action name
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function formattedAction(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $actions = [
                    'created' => 'Utworzono',
                    'updated' => 'Zaktualizowano',
                    'deleted' => 'Usunięto',
                    'bulk_update' => 'Masowa aktualizacja',
                    'import' => 'Importowano',
                    'sync' => 'Synchronizacja',
                    'restore' => 'Przywrócono',
                ];

                return $actions[$this->action] ?? ucfirst($this->action);
            }
        );
    }

    /**
     * Get change summary text
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function changeSummary(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->is_bulk_operation) {
                    $count = $this->affected_products_count ?? 0;
                    $adjustment = $this->adjustment_percentage ?
                        " ({$this->adjustment_percentage}%)" : '';

                    return "Masowa zmiana cen: {$count} produktów{$adjustment}";
                }

                if (!$this->changed_fields || empty($this->changed_fields)) {
                    return $this->formatted_action;
                }

                $fieldNames = [
                    'price_net' => 'cena netto',
                    'price_gross' => 'cena brutto',
                    'cost_price' => 'cena zakupu',
                    'margin_percentage' => 'marża',
                    'name' => 'nazwa',
                    'is_active' => 'status aktywności',
                    'is_default' => 'grupa domyślna',
                ];

                $changedFieldsText = collect($this->changed_fields)
                    ->map(fn ($field) => $fieldNames[$field] ?? $field)
                    ->join(', ');

                return "{$this->formatted_action}: {$changedFieldsText}";
            }
        );
    }

    /**
     * Check if this is bulk operation
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function isBulkOperation(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => !empty($this->batch_id) || $this->action === 'bulk_update'
        );
    }

    /**
     * Get significant changes (price-related only)
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function significantChanges(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                if (!$this->old_values || !$this->new_values) {
                    return [];
                }

                $significantFields = [
                    'price_net',
                    'price_gross',
                    'cost_price',
                    'margin_percentage',
                    'is_active',
                    'is_default'
                ];

                $changes = [];
                foreach ($significantFields as $field) {
                    if (isset($this->old_values[$field]) && isset($this->new_values[$field])) {
                        $oldValue = $this->old_values[$field];
                        $newValue = $this->new_values[$field];

                        if ($oldValue != $newValue) {
                            $changes[$field] = [
                                'old' => $oldValue,
                                'new' => $newValue,
                                'change' => $this->calculateChange($field, $oldValue, $newValue)
                            ];
                        }
                    }
                }

                return $changes;
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Filter by model type and ID
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $modelType
     * @param int $modelId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForModel(Builder $query, string $modelType, int $modelId): Builder
    {
        return $query->where('historyable_type', $modelType)
                    ->where('historyable_id', $modelId);
    }

    /**
     * Scope: Filter by action
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $action
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: Filter by user
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope: Filter by batch ID
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $batchId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByBatch(Builder $query, string $batchId): Builder
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Scope: Filter by source
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $source
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    /**
     * Scope: Filter by date range
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Carbon\Carbon $from
     * @param \Carbon\Carbon $to
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInDateRange(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope: Recent entries (last N days)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Scope: Bulk operations only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBulkOperations(Builder $query): Builder
    {
        return $query->where(function ($query) {
            $query->whereNotNull('batch_id')
                  ->orWhere('action', 'bulk_update');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC FACTORY METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Create price history entry for model change
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $action
     * @param array $oldValues
     * @param array $newValues
     * @param array $options
     * @return \App\Models\PriceHistory
     */
    public static function createForModel(
        Model $model,
        string $action,
        array $oldValues = [],
        array $newValues = [],
        array $options = []
    ): PriceHistory {
        $changedFields = [];

        if ($action === 'updated' && !empty($oldValues) && !empty($newValues)) {
            // BUG #14 FIX: Handle nested arrays (prestashop_mapping) by comparing serialized values
            foreach ($newValues as $key => $value) {
                $oldValue = $oldValues[$key] ?? null;

                // Serialize arrays for comparison
                $oldSerialized = is_array($oldValue) ? json_encode($oldValue) : $oldValue;
                $newSerialized = is_array($value) ? json_encode($value) : $value;

                if ($oldSerialized !== $newSerialized) {
                    $changedFields[] = $key;
                }
            }
        }

        return static::create([
            'historyable_type' => get_class($model),
            'historyable_id' => $model->getKey(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_fields' => $changedFields,
            'change_reason' => $options['reason'] ?? null,
            'batch_id' => $options['batch_id'] ?? null,
            'adjustment_percentage' => $options['adjustment_percentage'] ?? null,
            'adjustment_type' => $options['adjustment_type'] ?? null,
            'affected_products_count' => $options['affected_products_count'] ?? null,
            'source' => $options['source'] ?? 'admin_panel',
            'metadata' => $options['metadata'] ?? null,
            'created_by' => auth()->id() ?? $options['user_id'] ?? null,
            'created_at' => Carbon::now(),
        ]);
    }

    /**
     * Create bulk operation history entry
     *
     * @param string $batchId
     * @param array $options
     * @return \App\Models\PriceHistory
     */
    public static function createBulkOperation(string $batchId, array $options): PriceHistory
    {
        return static::create([
            'historyable_type' => $options['model_type'] ?? 'App\Models\ProductPrice',
            'historyable_id' => 0, // 0 for bulk operations
            'action' => 'bulk_update',
            'old_values' => [],
            'new_values' => [],
            'changed_fields' => $options['changed_fields'] ?? [],
            'change_reason' => $options['reason'] ?? 'Bulk price update',
            'batch_id' => $batchId,
            'adjustment_percentage' => $options['adjustment_percentage'] ?? null,
            'adjustment_type' => $options['adjustment_type'] ?? null,
            'affected_products_count' => $options['affected_products_count'] ?? 0,
            'source' => $options['source'] ?? 'admin_panel',
            'metadata' => $options['metadata'] ?? null,
            'created_by' => auth()->id() ?? $options['user_id'] ?? null,
            'created_at' => Carbon::now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Calculate change between old and new value
     *
     * @param string $field
     * @param mixed $oldValue
     * @param mixed $newValue
     * @return mixed
     */
    private function calculateChange(string $field, $oldValue, $newValue)
    {
        $numericFields = ['price_net', 'price_gross', 'cost_price', 'margin_percentage'];

        if (in_array($field, $numericFields) && is_numeric($oldValue) && is_numeric($newValue)) {
            $difference = $newValue - $oldValue;
            $percentageChange = $oldValue != 0 ? round(($difference / $oldValue) * 100, 2) : null;

            return [
                'difference' => $difference,
                'percentage_change' => $percentageChange
            ];
        }

        return null; // For non-numeric fields
    }

    /**
     * Get audit summary for specific model
     *
     * @param string $modelType
     * @param int $modelId
     * @param int $days
     * @return array
     */
    public static function getAuditSummary(string $modelType, int $modelId, int $days = 30): array
    {
        $query = static::forModel($modelType, $modelId)->recent($days);

        return [
            'total_changes' => $query->count(),
            'actions_breakdown' => $query->groupBy('action')
                                        ->selectRaw('action, count(*) as count')
                                        ->pluck('count', 'action')
                                        ->toArray(),
            'users_involved' => $query->distinct('created_by')->count('created_by'),
            'last_change' => $query->latest('created_at')->first(),
        ];
    }

    /**
     * Clean old history entries based on retention policy
     *
     * @param int $retentionDays
     * @return int Number of deleted records
     */
    public static function cleanOldEntries(int $retentionDays = 365): int
    {
        $cutoffDate = Carbon::now()->subDays($retentionDays);

        return static::where('created_at', '<', $cutoffDate)->delete();
    }
}