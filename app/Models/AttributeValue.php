<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Attribute Value Model
 *
 * Predefined value for an attribute type (e.g., "Czerwony" for "Kolor")
 *
 * ETAP_05b FAZA 2 - Database-backed attribute values
 *
 * FEATURES:
 * - Dynamic CRUD via AttributeManager service
 * - Color hex support for color types
 * - Sortable with position column
 * - Active/inactive status
 *
 * RELATIONSHIPS:
 * - belongs to AttributeType
 * - has many VariantAttribute (usage tracking)
 *
 * @property int $id
 * @property int $attribute_type_id Foreign key to attribute_types
 * @property string $code Unique code per type (e.g., 'red')
 * @property string $label Display label (e.g., 'Czerwony')
 * @property string|null $color_hex Hex color code (e.g., '#ff0000')
 * @property string|null $auto_prefix Prefix to prepend to variant SKU (e.g., 'XXX' -> 'XXX-SKU')
 * @property bool $auto_prefix_enabled Whether to auto-apply prefix when creating variant
 * @property string|null $auto_suffix Suffix to append to variant SKU (e.g., 'XXX' -> 'SKU-XXX')
 * @property bool $auto_suffix_enabled Whether to auto-apply suffix when creating variant
 * @property int $position Sort order
 * @property bool $is_active Active status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @package App\Models
 * @version 1.0
 * @since 2025-10-24
 */
class AttributeValue extends Model
{
    use HasFactory;

    protected $table = 'attribute_values';

    protected $fillable = [
        'attribute_type_id',
        'code',
        'label',
        'color_hex',
        'auto_prefix',
        'auto_prefix_enabled',
        'auto_suffix',
        'auto_suffix_enabled',
        'position',
        'is_active',
    ];

    protected $casts = [
        'attribute_type_id' => 'integer',
        'position' => 'integer',
        'is_active' => 'boolean',
        'auto_prefix_enabled' => 'boolean',
        'auto_suffix_enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Attribute type this value belongs to
     */
    public function attributeType(): BelongsTo
    {
        return $this->belongsTo(AttributeType::class, 'attribute_type_id');
    }

    /**
     * Variant attributes using this value
     */
    public function variantAttributes(): HasMany
    {
        return $this->hasMany(VariantAttribute::class, 'value_id');
    }

    /**
     * PrestaShop mappings for this value (per shop)
     * ETAP_05b FAZA 5 - Eager loading for N+1 optimization
     */
    public function prestashopMappings(): HasMany
    {
        return $this->hasMany(AttributeValuePsMapping::class, 'attribute_value_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Only active values
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Find by attribute type
     */
    public function scopeByType($query, int $typeId)
    {
        return $query->where('attribute_type_id', $typeId);
    }

    /**
     * Scope: Ordered by position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position', 'asc')->orderBy('id', 'asc');
    }

    /**
     * Scope: With variants count (eager loaded)
     * ETAP_05b FAZA 5 - N+1 optimization
     */
    public function scopeWithVariantsCount($query)
    {
        return $query->withCount('variantAttributes');
    }

    /**
     * Scope: With distinct products count via subquery
     * ETAP_05b FAZA 5 - N+1 optimization
     */
    public function scopeWithProductsCount($query)
    {
        return $query->withCount([
            'variantAttributes as products_count' => function ($q) {
                $q->select(\DB::raw('COUNT(DISTINCT product_variants.product_id)'))
                  ->join('product_variants', 'variant_attributes.variant_id', '=', 'product_variants.id');
            }
        ]);
    }

    /**
     * Scope: Only values with variants assigned (used)
     */
    public function scopeUsed($query)
    {
        return $query->has('variantAttributes');
    }

    /**
     * Scope: Only values without variants (unused)
     */
    public function scopeUnused($query)
    {
        return $query->doesntHave('variantAttributes');
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this is a color value
     */
    public function hasColor(): bool
    {
        return !empty($this->color_hex);
    }
}
