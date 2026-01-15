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

    /**
     * Get usage stats for ALL values of a type in ONE query
     *
     * ETAP_05b FAZA 5 - Replaces N+1 queries with single aggregated query
     *
     * @param int $typeId Attribute type ID
     * @return Collection [value_id => ['variants_count' => X, 'products_count' => Y]]
     */
    public function getUsageStatsForType(int $typeId): Collection
    {
        return \DB::table('attribute_values')
            ->select([
                'attribute_values.id',
                \DB::raw('COUNT(DISTINCT variant_attributes.id) as variants_count'),
                \DB::raw('COUNT(DISTINCT product_variants.product_id) as products_count'),
            ])
            ->leftJoin('variant_attributes', 'attribute_values.id', '=', 'variant_attributes.value_id')
            ->leftJoin('product_variants', 'variant_attributes.variant_id', '=', 'product_variants.id')
            ->where('attribute_values.attribute_type_id', $typeId)
            ->groupBy('attribute_values.id')
            ->get()
            ->keyBy('id')
            ->map(fn($row) => [
                'variants_count' => (int) $row->variants_count,
                'products_count' => (int) $row->products_count,
            ]);
    }

    /**
     * Get detailed products for attribute value with pagination
     *
     * ETAP_05b FAZA 5 - For Products Modal
     *
     * @param int $valueId Attribute value ID
     * @param int $perPage Items per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getProductsUsingValuePaginated(int $valueId, int $perPage = 15)
    {
        return \App\Models\Product::query()
            ->select(['products.id', 'products.sku', 'products.name'])
            ->join('product_variants', 'products.id', '=', 'product_variants.product_id')
            ->join('variant_attributes', 'product_variants.id', '=', 'variant_attributes.variant_id')
            ->where('variant_attributes.value_id', $valueId)
            ->distinct()
            ->withCount(['variants as variants_with_value_count' => function ($q) use ($valueId) {
                $q->whereHas('attributes', fn($a) => $a->where('value_id', $valueId));
            }])
            ->orderBy('products.sku')
            ->paginate($perPage);
    }

    /**
     * Get all unused values for a type (no variants assigned)
     *
     * @param int $typeId Attribute type ID
     * @return Collection
     */
    public function getUnusedValuesForType(int $typeId): Collection
    {
        return AttributeValue::where('attribute_type_id', $typeId)
            ->whereDoesntHave('variantAttributes')
            ->get();
    }

    /**
     * Count unused values for a type
     *
     * @param int $typeId Attribute type ID
     * @return int
     */
    public function countUnusedValuesForType(int $typeId): int
    {
        return AttributeValue::where('attribute_type_id', $typeId)
            ->whereDoesntHave('variantAttributes')
            ->count();
    }
}
