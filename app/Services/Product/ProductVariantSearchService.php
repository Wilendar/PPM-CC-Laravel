<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\AttributeType;
use App\Models\AttributeValue;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

/**
 * ProductVariantSearchService - Search products by variant attribute values
 *
 * Variant Panel Redesign - Faza 1 Backend
 *
 * Features:
 * - Find products by variant attribute values (OR/AND mode)
 * - Multi-filter support (combine multiple attribute types)
 * - Pagination with configurable per-page
 * - Sorting by SKU, name, created_at
 * - Eager loading optimization
 *
 * Usage:
 * $service = app(ProductVariantSearchService::class);
 * $products = $service->findByVariantValues([1, 2, 3], 'any', 'sku', 'asc', 15);
 *
 * @package App\Services\Product
 * @version 1.0
 * @since Variant Panel Redesign 2025-12
 */
class ProductVariantSearchService
{
    /**
     * Allowed sort fields for security
     */
    protected array $allowedSortFields = [
        'sku',
        'name',
        'created_at',
        'updated_at',
    ];

    /**
     * Find products by variant attribute value IDs
     *
     * @param array $valueIds Array of AttributeValue IDs to filter by
     * @param string $mode Filter mode: 'any' (OR) or 'all' (AND)
     * @param string $sortField Field to sort by (sku, name, created_at)
     * @param string $sortDirection Sort direction: 'asc' or 'desc'
     * @param int $perPage Items per page
     * @return LengthAwarePaginator
     */
    public function findByVariantValues(
        array $valueIds,
        string $mode = 'any',
        string $sortField = 'sku',
        string $sortDirection = 'asc',
        int $perPage = 15
    ): LengthAwarePaginator {
        // Validate inputs
        $sortField = in_array($sortField, $this->allowedSortFields) ? $sortField : 'sku';
        $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc']) ? strtolower($sortDirection) : 'asc';
        $mode = in_array($mode, ['any', 'all']) ? $mode : 'any';

        $query = Product::query()
            ->select('products.*')
            ->with([
                'variants:id,product_id,sku,name,is_active,position',
                'variants.attributes:id,variant_id,attribute_type_id,value_id',
                'variants.attributes.attributeValue:id,label,code,color_hex',
                'variants.attributes.attributeType:id,name,code',
            ]);

        // Apply variant value filter
        if (!empty($valueIds)) {
            if ($mode === 'all') {
                $query->withAllVariantValues($valueIds);
            } else {
                $query->withAnyVariantValues($valueIds);
            }
        }

        // Apply sorting
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * Find products with multiple filter groups (advanced filtering)
     *
     * Each filter group can have its own mode (AND/OR)
     * Groups are combined with AND logic
     *
     * @param array $filters Array of filter groups: [
     *   ['type_id' => 1, 'value_ids' => [1, 2], 'mode' => 'any'],
     *   ['type_id' => 2, 'value_ids' => [5, 6], 'mode' => 'any'],
     * ]
     * @param string $sortField
     * @param string $sortDirection
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function findByMultipleFilters(
        array $filters,
        string $sortField = 'sku',
        string $sortDirection = 'asc',
        int $perPage = 15
    ): LengthAwarePaginator {
        $sortField = in_array($sortField, $this->allowedSortFields) ? $sortField : 'sku';
        $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc']) ? strtolower($sortDirection) : 'asc';

        $query = Product::query()
            ->select('products.*')
            ->with([
                'variants:id,product_id,sku,name,is_active,position',
                'variants.attributes:id,variant_id,attribute_type_id,value_id',
                'variants.attributes.attributeValue:id,label,code,color_hex',
                'variants.attributes.attributeType:id,name,code',
            ]);

        // Apply each filter group (AND between groups)
        foreach ($filters as $filter) {
            if (!empty($filter['value_ids'])) {
                $query->withVariantFilter($filter);
            }
        }

        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * Get products count for given variant values (for UI badges)
     *
     * @param array $valueIds
     * @param string $mode
     * @return int
     */
    public function countProductsByVariantValues(array $valueIds, string $mode = 'any'): int
    {
        if (empty($valueIds)) {
            return 0;
        }

        $query = Product::query();

        if ($mode === 'all') {
            $query->withAllVariantValues($valueIds);
        } else {
            $query->withAnyVariantValues($valueIds);
        }

        return $query->count();
    }

    /**
     * Get products for a single attribute value (optimized for panel)
     *
     * @param int $valueId Single AttributeValue ID
     * @param int $limit Max products to return
     * @return Collection
     */
    public function getProductsForValue(int $valueId, int $limit = 10): Collection
    {
        return Product::query()
            ->select('products.id', 'products.sku', 'products.name')
            ->withAnyVariantValues([$valueId])
            ->orderBy('sku')
            ->limit($limit)
            ->get();
    }

    /**
     * Get product count for a single attribute value
     *
     * @param int $valueId
     * @return int
     */
    public function countProductsForValue(int $valueId): int
    {
        return Product::query()
            ->withAnyVariantValues([$valueId])
            ->count();
    }

    /**
     * Get products grouped by attribute values for a type
     *
     * Returns array with value_id => product_count mapping
     * Optimized single query instead of N queries
     *
     * @param int $attributeTypeId
     * @return array ['value_id' => count, ...]
     */
    public function getProductCountsPerValue(int $attributeTypeId): array
    {
        $results = \DB::table('variant_attributes')
            ->select('variant_attributes.value_id', \DB::raw('COUNT(DISTINCT product_variants.product_id) as product_count'))
            ->join('product_variants', 'variant_attributes.variant_id', '=', 'product_variants.id')
            ->where('variant_attributes.attribute_type_id', $attributeTypeId)
            ->groupBy('variant_attributes.value_id')
            ->get();

        return $results->pluck('product_count', 'value_id')->toArray();
    }

    /**
     * Search products with text query combined with variant filters
     *
     * @param string $searchTerm Text to search (SKU, name)
     * @param array $valueIds Optional variant value filter
     * @param string $mode Filter mode
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchWithVariantFilter(
        string $searchTerm,
        array $valueIds = [],
        string $mode = 'any',
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Product::query()
            ->select('products.*')
            ->with([
                'variants:id,product_id,sku,name,is_active',
                'variants.attributes:id,variant_id,value_id',
                'variants.attributes.attributeValue:id,label,color_hex',
            ]);

        // Apply text search
        if (!empty($searchTerm)) {
            $query->search($searchTerm);
        }

        // Apply variant filter
        if (!empty($valueIds)) {
            if ($mode === 'all') {
                $query->withAllVariantValues($valueIds);
            } else {
                $query->withAnyVariantValues($valueIds);
            }
        }

        return $query->orderBy('sku')->paginate($perPage);
    }

    /**
     * Get attribute values with their product counts for panel display
     *
     * @param int $attributeTypeId
     * @return Collection Collection of AttributeValue with injected product_count
     */
    public function getValuesWithProductCounts(int $attributeTypeId): Collection
    {
        $productCounts = $this->getProductCountsPerValue($attributeTypeId);

        $values = AttributeValue::query()
            ->where('attribute_type_id', $attributeTypeId)
            ->active()
            ->ordered()
            ->get();

        // Inject product counts
        return $values->map(function ($value) use ($productCounts) {
            $value->product_count = $productCounts[$value->id] ?? 0;
            return $value;
        });
    }

    /**
     * Export products matching variant filter to array (for CSV/Excel export)
     *
     * @param array $valueIds
     * @param string $mode
     * @param array $columns Columns to include
     * @return Collection
     */
    public function exportProductsByVariantValues(
        array $valueIds,
        string $mode = 'any',
        array $columns = ['sku', 'name', 'manufacturer']
    ): Collection {
        $query = Product::query()->select($columns);

        if (!empty($valueIds)) {
            if ($mode === 'all') {
                $query->withAllVariantValues($valueIds);
            } else {
                $query->withAnyVariantValues($valueIds);
            }
        }

        return $query->orderBy('sku')->get();
    }
}
