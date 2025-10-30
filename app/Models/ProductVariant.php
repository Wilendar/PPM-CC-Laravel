<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * Product Variant Model
 *
 * Reprezentuje wariant produktu (np. rozmiar XL, kolor czerwony)
 * SKU jako unique identifier (SKU-first architecture)
 *
 * @property int $id
 * @property int $product_id
 * @property string $sku Unikalny SKU wariantu
 * @property string $name Nazwa wariantu
 * @property bool $is_active Czy wariant aktywny
 * @property bool $is_default Czy domy[lny wariant
 * @property int|null $position Kolejno[ wy[wietlania
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Table name
     */
    protected $table = 'product_variants';

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'is_active',
        'is_default',
        'position',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'product_id' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'position' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Attributes included in arrays/JSON
     */
    protected $with = [
        'attributes',
        'prices',
        'stock',
        'images',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Parent product relationship
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Variant attributes (color, size, etc.)
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(VariantAttribute::class, 'variant_id');
    }

    /**
     * Variant prices per price group
     */
    public function prices(): HasMany
    {
        return $this->hasMany(VariantPrice::class, 'variant_id');
    }

    /**
     * Variant stock per warehouse
     */
    public function stock(): HasMany
    {
        return $this->hasMany(VariantStock::class, 'variant_id');
    }

    /**
     * Variant images
     */
    public function images(): HasMany
    {
        return $this->hasMany(VariantImage::class, 'variant_id')->orderBy('position');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Only active variants
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Only default variants
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope: Variants for specific product
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope: Find by SKU (SKU-first pattern)
     */
    public function scopeBySku($query, string $sku)
    {
        return $query->where('sku', $sku);
    }

    /**
     * Scope: Ordered by position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position', 'asc')->orderBy('id', 'asc');
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Find variant by SKU (SKU-first architecture)
     */
    public static function findBySku(string $sku): ?self
    {
        return static::where('sku', $sku)->first();
    }

    /*
    |--------------------------------------------------------------------------
    | INSTANCE METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get price for specific price group
     */
    public function getPriceForGroup($priceGroupId): ?float
    {
        $variantPrice = $this->prices()
            ->where('price_group_id', $priceGroupId)
            ->first();

        if (!$variantPrice) {
            return null;
        }

        return $variantPrice->getEffectivePrice();
    }

    /**
     * Get stock for specific warehouse
     */
    public function getStockForWarehouse($warehouseId): int
    {
        $stock = $this->stock()
            ->where('warehouse_id', $warehouseId)
            ->first();

        return $stock ? $stock->getAvailable() : 0;
    }

    /**
     * Get total stock across all warehouses
     */
    public function getTotalStock(): int
    {
        return $this->stock()->sum('quantity') - $this->stock()->sum('reserved');
    }

    /**
     * Check if variant is available (active + has stock)
     */
    public function isAvailable(): bool
    {
        return $this->is_active && $this->getTotalStock() > 0;
    }

    /**
     * Get attributes grouped by type
     */
    public function getAttributes(): Collection
    {
        return $this->attributes()
            ->with('attributeType')
            ->get()
            ->groupBy('attribute_type_id');
    }

    /**
     * Get cover image
     */
    public function getCoverImage(): ?VariantImage
    {
        return $this->images()->where('is_cover', true)->first()
            ?? $this->images()->first();
    }
}
