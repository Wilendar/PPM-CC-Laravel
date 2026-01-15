<?php

namespace App\Models\Concerns\Product;

use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * HasVariants Trait - Product Variants Management (STUB for ETAP_05a)
 *
 * Responsibility: Product variants system (master-variant pattern)
 *
 * Features (to be implemented in ETAP_05a):
 * - Variants relationship (1:many)
 * - Variant generation (combinations z attributes)
 * - Variant inheritance (master → variant properties)
 * - Default variant selection
 *
 * Architecture: SKU-first pattern (każdy variant ma własny SKU)
 * Performance: Eager loading ready z proper indexing
 * Integration: PrestaShop product_attribute mapping ready
 *
 * @package App\Models\Concerns\Product
 * @version 1.0
 * @since ETAP_05a SEKCJA 0 - Product.php Refactoring
 */
trait HasVariants
{
    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS - Variant Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Product variants relationship (1:many)
     *
     * Business Logic: Jeden produkt może mieć wiele wariantów
     * Performance: Eager loading ready z proper indexing
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id', 'id')
                    ->orderBy('position', 'asc')
                    ->orderBy('name', 'asc');
    }

    /**
     * Default variant relationship (1:1)
     *
     * Business Logic: Domyślny wariant produktu
     * Performance: Single query optimization
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function defaultVariant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'default_variant_id', 'id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS - Computed Variant Attributes
    |--------------------------------------------------------------------------
    */

    /**
     * Check if product has variants
     *
     * Business Logic: Convenience accessor dla variant detection
     * Performance: Based on is_variant_master flag dla performance
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function hasVariants(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->is_variant_master && $this->variants->count() > 0
        );
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS METHODS - Variant Operations (STUB - to be implemented)
    |--------------------------------------------------------------------------
    */

    /**
     * Get default variant (first active variant or null)
     *
     * ETAP_05a FAZA 2 ✅ IMPLEMENTED
     *
     * @return \App\Models\ProductVariant|null
     */
    public function getDefaultVariant(): ?ProductVariant
    {
        // Try explicit default_variant_id first
        if ($this->default_variant_id && $this->defaultVariant) {
            return $this->defaultVariant;
        }

        // Fallback to first active variant with is_default flag
        return $this->variants()
                    ->where('is_active', true)
                    ->where('is_default', true)
                    ->first()
                ?? $this->variants()
                    ->where('is_active', true)
                    ->orderBy('position', 'asc')
                    ->first();
    }

    /**
     * Get all active variants
     *
     * ETAP_05a FAZA 2 ✅ IMPLEMENTED
     *
     * @return \Illuminate\Support\Collection
     */
    public function getVariants(): \Illuminate\Support\Collection
    {
        return $this->variants()
                    ->active()
                    ->ordered()
                    ->get();
    }

    /**
     * Check if product has variants (method version)
     *
     * ETAP_05a FAZA 2 ✅ IMPLEMENTED
     *
     * @return bool
     */
    public function hasVariantsMethod(): bool
    {
        return $this->has_variants && $this->variants()->exists();
    }

    // NOTE: Shop variant methods (shopVariants, shopVariantsForShop, getVariantsForShop)
    // are implemented in HasMultiStore trait to avoid duplication

    /*
    |--------------------------------------------------------------------------
    | SCOPES - Variant-based Product Filtering (Variant Panel Redesign)
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Products with ANY of the specified variant values (OR mode)
     *
     * Usage: Product::withAnyVariantValues([1, 2, 3])->get()
     * Returns products where ANY variant has ANY of the specified value IDs
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $valueIds Array of AttributeValue IDs
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithAnyVariantValues($query, array $valueIds)
    {
        if (empty($valueIds)) {
            return $query;
        }

        return $query->whereHas('variants.attributes', function ($q) use ($valueIds) {
            $q->whereIn('value_id', $valueIds);
        });
    }

    /**
     * Scope: Products with ALL of the specified variant values (AND mode)
     *
     * Usage: Product::withAllVariantValues([1, 2, 3])->get()
     * Returns products where variants collectively have ALL specified value IDs
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $valueIds Array of AttributeValue IDs
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithAllVariantValues($query, array $valueIds)
    {
        if (empty($valueIds)) {
            return $query;
        }

        foreach ($valueIds as $valueId) {
            $query->whereHas('variants.attributes', function ($q) use ($valueId) {
                $q->where('value_id', $valueId);
            });
        }

        return $query;
    }

    /**
     * Scope: Products with variants of specific attribute type
     *
     * Usage: Product::withVariantAttributeType(1)->get()
     * Returns products that have variants with the specified attribute type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $attributeTypeId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithVariantAttributeType($query, int $attributeTypeId)
    {
        return $query->whereHas('variants.attributes', function ($q) use ($attributeTypeId) {
            $q->where('attribute_type_id', $attributeTypeId);
        });
    }

    /**
     * Scope: Products filtered by variant attribute type and values (combined)
     *
     * Usage: Product::withVariantFilter(['type_id' => 1, 'value_ids' => [1,2,3], 'mode' => 'any'])->get()
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filter ['type_id' => int, 'value_ids' => array, 'mode' => 'any'|'all']
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithVariantFilter($query, array $filter)
    {
        $typeId = $filter['type_id'] ?? null;
        $valueIds = $filter['value_ids'] ?? [];
        $mode = $filter['mode'] ?? 'any';

        // Filter by type if specified
        if ($typeId) {
            $query->whereHas('variants.attributes', function ($q) use ($typeId) {
                $q->where('attribute_type_id', $typeId);
            });
        }

        // Filter by values if specified
        if (!empty($valueIds)) {
            if ($mode === 'all') {
                return $query->withAllVariantValues($valueIds);
            } else {
                return $query->withAnyVariantValues($valueIds);
            }
        }

        return $query;
    }
}
