<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductStatus extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'color',
        'icon',
        'is_active_equivalent',
        'is_default',
        'sort_order',
        'transition_on_stock_depleted',
        'transition_to_status_id',
        'depletion_warehouse_id',
    ];

    protected $casts = [
        'is_active_equivalent' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
        'transition_on_stock_depleted' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $status) {
            if (empty($status->slug)) {
                $status->slug = Str::slug($status->name);
            }

            // Ensure unique slug
            $base = $status->slug;
            $counter = 1;
            while (static::where('slug', $status->slug)->exists()) {
                $status->slug = $base . '-' . $counter++;
            }
        });

        static::saving(function (self $status) {
            // If this is being set as default, unset others
            if ($status->is_default && $status->isDirty('is_default')) {
                DB::table('product_statuses')
                    ->where('id', '!=', $status->id ?? 0)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            // Prevent self-referencing transition
            if ($status->transition_to_status_id && $status->transition_to_status_id == $status->id) {
                $status->transition_to_status_id = null;
                $status->transition_on_stock_depleted = false;
            }
        });

        // Auto-compute is_active on related products when status changes
        static::updated(function (self $status) {
            if ($status->isDirty('is_active_equivalent')) {
                $status->products()->update([
                    'is_active' => $status->is_active_equivalent,
                ]);
            }
        });
    }

    // ---- Relationships ----

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'product_status_id');
    }

    public function integrationMappings(): HasMany
    {
        return $this->hasMany(ProductStatusIntegrationMapping::class);
    }

    public function transitionToStatus(): BelongsTo
    {
        return $this->belongsTo(self::class, 'transition_to_status_id');
    }

    public function depletionWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'depletion_warehouse_id');
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(ProductStatusTransition::class, 'from_status_id');
    }

    // ---- Scopes ----

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeActiveEquivalent(Builder $query): Builder
    {
        return $query->where('is_active_equivalent', true);
    }

    public function scopeWithStockDepletion(Builder $query): Builder
    {
        return $query->where('transition_on_stock_depleted', true)
            ->whereNotNull('transition_to_status_id');
    }

    // ---- Methods ----

    /**
     * Check if this status maps to active for a given integration type
     */
    public function isActiveFor(string $integrationType): bool
    {
        $mapping = $this->integrationMappings()
            ->where('integration_type', $integrationType)
            ->first();

        return $mapping ? $mapping->maps_to_active : $this->is_active_equivalent;
    }

    /**
     * Get the default status
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first()
            ?? static::ordered()->first();
    }

    /**
     * Get all statuses as key-value for selects
     */
    public static function getForSelect(): array
    {
        return static::ordered()
            ->get(['id', 'name', 'color', 'slug', 'is_active_equivalent'])
            ->toArray();
    }
}
