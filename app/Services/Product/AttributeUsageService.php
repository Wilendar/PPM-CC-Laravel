<?php

namespace App\Services\Product;

use App\Models\AttributeType;
use App\Models\AttributeValue;
use App\Models\VariantAttribute;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * AttributeUsageService - Tracks attribute usage in products/variants
 *
 * RESPONSIBILITY: Count/query products and variants using attribute types/values
 *
 * FEATURES:
 * - Count products using type/value
 * - Get products list using type/value
 * - Validate delete safety (can delete without breaking data?)
 *
 * CLAUDE.md: <300 lines (currently ~100 lines)
 *
 * @package App\Services\Product
 * @version 1.0
 * @since ETAP_05b Phase 2 (2025-10-24)
 */
class AttributeUsageService
{
    /**
     * Count products using attribute type
     *
     * @param int $typeId Attribute type ID
     * @return int Number of products
     */
    public function countProductsUsingType(int $typeId): int
    {
        return $this->getProductsUsingAttributeType($typeId)->count();
    }

    /**
     * Count variants using attribute value
     *
     * @param int $valueId Attribute value ID
     * @return int Number of variants
     */
    public function countVariantsUsingValue(int $valueId): int
    {
        return VariantAttribute::where('value_id', $valueId)->count();
    }

    /**
     * Get products using attribute type
     *
     * @param int $typeId Attribute type ID
     * @return Collection Collection of products
     */
    public function getProductsUsingAttributeType(int $typeId): Collection
    {
        $valueIds = AttributeValue::where('attribute_type_id', $typeId)->pluck('id');

        if ($valueIds->isEmpty()) {
            return collect();
        }

        $variantIds = VariantAttribute::whereIn('value_id', $valueIds)
            ->distinct()
            ->pluck('variant_id');

        if ($variantIds->isEmpty()) {
            return collect();
        }

        return ProductVariant::whereIn('id', $variantIds)
            ->with('product:id,sku,name')
            ->get()
            ->groupBy('product_id')
            ->map(function ($variants, $productId) {
                $product = $variants->first()->product;
                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'variant_count' => $variants->count(),
                ];
            })
            ->values();
    }

    /**
     * Get variants using attribute value
     *
     * @param int $valueId Attribute value ID
     * @return Collection Collection of variants
     */
    public function getVariantsUsingValue(int $valueId): Collection
    {
        return VariantAttribute::where('value_id', $valueId)
            ->with(['variant.product:id,sku,name', 'variant:id,product_id,sku,name'])
            ->get()
            ->map(function ($variantAttr) {
                return [
                    'id' => $variantAttr->variant->id,
                    'sku' => $variantAttr->variant->sku,
                    'name' => $variantAttr->variant->name,
                    'product_sku' => $variantAttr->variant->product->sku,
                    'product_name' => $variantAttr->variant->product->name,
                ];
            });
    }

    /**
     * Get products using attribute value
     *
     * @param int $valueId Attribute value ID
     * @return Collection Collection of products
     */
    public function getProductsUsingAttributeValue(int $valueId): Collection
    {
        $variantIds = VariantAttribute::where('value_id', $valueId)
            ->distinct()
            ->pluck('variant_id');

        if ($variantIds->isEmpty()) {
            return collect();
        }

        return ProductVariant::whereIn('id', $variantIds)
            ->with('product:id,sku,name')
            ->get()
            ->groupBy('product_id')
            ->map(function ($variants, $productId) {
                $product = $variants->first()->product;
                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'variant_count' => $variants->count(),
                ];
            })
            ->values();
    }

    /**
     * Check if attribute type can be deleted safely
     *
     * @param int $typeId Attribute type ID
     * @return bool True if safe to delete (no products using)
     */
    public function canDeleteType(int $typeId): bool
    {
        return $this->countProductsUsingType($typeId) === 0;
    }

    /**
     * Check if attribute value can be deleted safely
     *
     * @param int $valueId Attribute value ID
     * @return bool True if safe to delete (no variants using)
     */
    public function canDeleteValue(int $valueId): bool
    {
        return $this->countVariantsUsingValue($valueId) === 0;
    }
}
