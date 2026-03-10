<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Location Model - Lokalizacje magazynowe PPM-CC-Laravel
 *
 * Business Logic:
 * - Hierarchiczna struktura lokalizacji (parent/children z depth)
 * - Powiazanie z magazynami (Warehouse)
 * - Pattern-based location codes (coded, dash, wall, named, gift, other)
 * - Zone/row/shelf/bin granularity dla precyzyjnej lokalizacji
 * - Materialized path dla breadcrumb/tree navigation
 * - Product count tracking (recalculated from ProductStock)
 *
 * Performance Features:
 * - Strategic indexing na warehouse_id, parent_id, normalized_code
 * - Scopes dla common warehouse queries
 * - Soft deletes dla audit trail
 *
 * @property int $id
 * @property int|null $warehouse_id FK to warehouses
 * @property string $code Location code (e.g. A-01-03)
 * @property string $normalized_code Normalized code for comparison
 * @property string|null $description Human-readable description
 * @property string $pattern_type Location pattern type (coded/dash/wall/named/gift/other)
 * @property string|null $zone Zone identifier (e.g. A, B, C)
 * @property string|null $row_code Row code within zone
 * @property int|null $shelf Shelf number
 * @property int|null $bin Bin number on shelf
 * @property int|null $parent_id FK self-referencing parent location
 * @property int $depth Nesting depth (0 = root)
 * @property string|null $path Materialized path for tree traversal
 * @property int $product_count Cached count of products at this location
 * @property bool $is_active Active status
 * @property int $sort_order Display ordering
 * @property string|null $notes General notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read \App\Models\Warehouse|null $warehouse
 * @property-read \App\Models\Location|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Location[] $children
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Location[] $descendants
 * @property-read string $display_path
 *
 * @method static \Illuminate\Database\Eloquent\Builder byWarehouse(int $warehouseId)
 * @method static \Illuminate\Database\Eloquent\Builder active()
 * @method static \Illuminate\Database\Eloquent\Builder rootLevel()
 * @method static \Illuminate\Database\Eloquent\Builder byZone(string $zone)
 * @method static \Illuminate\Database\Eloquent\Builder withProducts()
 * @method static \Illuminate\Database\Eloquent\Builder empty()
 *
 * @package App\Models
 * @version ETAP_08
 * @since 2026-03-09
 */
class Location extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'warehouse_id',
        'code',
        'normalized_code',
        'description',
        'pattern_type',
        'zone',
        'row_code',
        'shelf',
        'bin',
        'parent_id',
        'depth',
        'path',
        'product_count',
        'is_active',
        'sort_order',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'shelf' => 'integer',
            'bin' => 'integer',
            'depth' => 'integer',
            'product_count' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Warehouse this location belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Parent location (for hierarchical structures)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    /**
     * Direct children locations
     *
     * Performance: Sorted by sort_order and code for consistent display
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_id')
                    ->orderBy('sort_order')
                    ->orderBy('code');
    }

    /**
     * All descendant locations (direct children only in Eloquent scope)
     *
     * Note: Deep recursive traversal is handled in the service layer.
     * This relation returns direct children, useful for eager loading.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function descendants(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS
    |--------------------------------------------------------------------------
    */

    /**
     * Get display path - returns materialized path if available, otherwise code
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function displayPath(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->path ?: $this->code
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Filter by warehouse
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $warehouseId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope: Active locations only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Root level locations (no parent)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRootLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: Filter by zone
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $zone
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByZone(Builder $query, string $zone): Builder
    {
        return $query->where('zone', $zone);
    }

    /**
     * Scope: Locations with products (product_count > 0)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithProducts(Builder $query): Builder
    {
        return $query->where('product_count', '>', 0);
    }

    /**
     * Scope: Empty locations (product_count = 0)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEmpty(Builder $query): Builder
    {
        return $query->where('product_count', 0);
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS LOGIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Recalculate product count from ProductStock records
     *
     * Counts ProductStock entries where the location field matches this location's code.
     * Supports comma-separated location values in ProductStock.location field.
     *
     * @return void
     */
    public function recalculateProductCount(): void
    {
        $count = ProductStock::where('warehouse_id', $this->warehouse_id)
            ->where(function (Builder $q) {
                $q->where('location', $this->code)
                  ->orWhere('location', 'LIKE', '%,' . $this->code . ',%')
                  ->orWhere('location', 'LIKE', $this->code . ',%')
                  ->orWhere('location', 'LIKE', '%,' . $this->code);
            })
            ->count();

        $this->update(['product_count' => $count]);
    }
}
