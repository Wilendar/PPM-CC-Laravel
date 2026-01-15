<?php

namespace App\Services\Product;

use App\Models\FeatureGroup;
use App\Models\FeatureType;
use App\Models\FeatureValue;
use App\Models\ProductFeature;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * FeatureUsageService - Tracks feature usage in products
 *
 * RESPONSIBILITY: Count/query products using feature types/values
 *
 * FEATURES:
 * - Count products using type/value
 * - Get products list using type/value
 * - Usage stats for browser panel (3-column layout)
 * - Validate delete safety
 *
 * CLAUDE.md: <300 lines (currently ~200 lines)
 *
 * @package App\Services\Product
 * @version 1.0
 * @since ETAP_07e Phase 2 (2025-12-17)
 */
class FeatureUsageService
{
    /**
     * Get usage stats for ALL feature types in a group (single query)
     *
     * @param int $groupId Feature group ID
     * @return Collection [feature_type_id => ['products_count' => X, 'values_count' => Y]]
     */
    public function getUsageStatsForGroup(int $groupId): Collection
    {
        return DB::table('feature_types')
            ->select([
                'feature_types.id',
                'feature_types.name',
                'feature_types.code',
                'feature_types.value_type',
                'feature_types.unit',
                DB::raw('COUNT(DISTINCT product_features.product_id) as products_count'),
                DB::raw('(SELECT COUNT(*) FROM feature_values WHERE feature_values.feature_type_id = feature_types.id) as values_count'),
            ])
            ->leftJoin('product_features', 'feature_types.id', '=', 'product_features.feature_type_id')
            ->where('feature_types.feature_group_id', $groupId)
            ->where('feature_types.is_active', true)
            ->groupBy('feature_types.id', 'feature_types.name', 'feature_types.code', 'feature_types.value_type', 'feature_types.unit')
            ->orderBy('feature_types.position')
            ->get()
            ->keyBy('id');
    }

    /**
     * Get usage stats for ALL values of a feature type (single query)
     *
     * @param int $featureTypeId Feature type ID
     * @return Collection [feature_value_id => ['products_count' => X]]
     */
    public function getUsageStatsForFeatureType(int $featureTypeId): Collection
    {
        return DB::table('feature_values')
            ->select([
                'feature_values.id',
                'feature_values.value',
                DB::raw('COUNT(DISTINCT product_features.product_id) as products_count'),
            ])
            ->leftJoin('product_features', 'feature_values.id', '=', 'product_features.feature_value_id')
            ->where('feature_values.feature_type_id', $featureTypeId)
            ->where('feature_values.is_active', true)
            ->groupBy('feature_values.id', 'feature_values.value')
            ->orderBy('feature_values.position')
            ->get()
            ->keyBy('id');
    }

    /**
     * Get products using specific feature value (predefined)
     *
     * @param int $featureValueId Feature value ID
     * @return Collection
     */
    public function getProductsUsingFeatureValue(int $featureValueId): Collection
    {
        return ProductFeature::where('feature_value_id', $featureValueId)
            ->with(['product:id,sku,name'])
            ->get()
            ->map(fn($pf) => [
                'id' => $pf->product->id,
                'sku' => $pf->product->sku,
                'name' => $pf->product->name,
                'custom_value' => $pf->custom_value,
            ]);
    }

    /**
     * Get products with custom value for feature type
     *
     * @param int $featureTypeId Feature type ID
     * @return Collection
     */
    public function getProductsWithCustomValue(int $featureTypeId): Collection
    {
        return ProductFeature::where('feature_type_id', $featureTypeId)
            ->whereNull('feature_value_id')
            ->whereNotNull('custom_value')
            ->with(['product:id,sku,name'])
            ->get()
            ->map(fn($pf) => [
                'id' => $pf->product->id,
                'sku' => $pf->product->sku,
                'name' => $pf->product->name,
                'custom_value' => $pf->custom_value,
            ]);
    }

    /**
     * Get products using feature type (any value)
     *
     * @param int $featureTypeId Feature type ID
     * @return Collection
     */
    public function getProductsUsingFeatureType(int $featureTypeId): Collection
    {
        return ProductFeature::where('feature_type_id', $featureTypeId)
            ->with(['product:id,sku,name', 'featureValue:id,value'])
            ->get()
            ->groupBy('product_id')
            ->map(function ($features, $productId) {
                $first = $features->first();
                return [
                    'id' => $first->product->id,
                    'sku' => $first->product->sku,
                    'name' => $first->product->name,
                    'value' => $first->featureValue?->value ?? $first->custom_value,
                ];
            })
            ->values();
    }

    /**
     * Count products using feature type
     *
     * @param int $featureTypeId Feature type ID
     * @return int
     */
    public function countProductsUsingFeatureType(int $featureTypeId): int
    {
        return ProductFeature::where('feature_type_id', $featureTypeId)
            ->distinct('product_id')
            ->count('product_id');
    }

    /**
     * Count products using feature value
     *
     * @param int $featureValueId Feature value ID
     * @return int
     */
    public function countProductsUsingFeatureValue(int $featureValueId): int
    {
        return ProductFeature::where('feature_value_id', $featureValueId)
            ->distinct('product_id')
            ->count('product_id');
    }

    /**
     * Check if feature type can be deleted safely
     *
     * @param int $featureTypeId Feature type ID
     * @return bool True if safe to delete (no products using)
     */
    public function canDeleteFeatureType(int $featureTypeId): bool
    {
        return $this->countProductsUsingFeatureType($featureTypeId) === 0;
    }

    /**
     * Check if feature value can be deleted safely
     *
     * @param int $featureValueId Feature value ID
     * @return bool True if safe to delete (no products using)
     */
    public function canDeleteFeatureValue(int $featureValueId): bool
    {
        return $this->countProductsUsingFeatureValue($featureValueId) === 0;
    }

    /**
     * Get groups with usage statistics (for left column)
     *
     * @return Collection
     */
    public function getGroupsWithStats(): Collection
    {
        return FeatureGroup::active()
            ->ordered()
            ->withCount(['featureTypes as features_count' => function ($q) {
                $q->where('is_active', true);
            }])
            ->get()
            ->map(fn($group) => [
                'id' => $group->id,
                'code' => $group->code,
                'name' => $group->getDisplayName(),
                'icon' => $group->icon,
                'color' => $group->color,
                'colorClasses' => $group->getColorClasses(),
                'features_count' => $group->features_count,
            ]);
    }

    /**
     * Get custom values statistics for feature type
     *
     * @param int $featureTypeId Feature type ID
     * @return array ['count' => X, 'unique_values' => Y]
     */
    public function getCustomValuesStats(int $featureTypeId): array
    {
        $query = ProductFeature::where('feature_type_id', $featureTypeId)
            ->whereNull('feature_value_id')
            ->whereNotNull('custom_value');

        return [
            'count' => $query->count(),
            'unique_values' => $query->distinct('custom_value')->count('custom_value'),
        ];
    }

    /**
     * Get unique custom values for feature type with product counts
     *
     * @param int $featureTypeId Feature type ID
     * @return Collection [['value' => 'X', 'products_count' => Y, 'product_ids' => [...]]]
     */
    public function getCustomValues(int $featureTypeId): Collection
    {
        return DB::table('product_features')
            ->select([
                'custom_value as value',
                DB::raw('COUNT(DISTINCT product_id) as products_count'),
                DB::raw('GROUP_CONCAT(DISTINCT product_id) as product_ids'),
            ])
            ->where('feature_type_id', $featureTypeId)
            ->whereNull('feature_value_id')
            ->whereNotNull('custom_value')
            ->where('custom_value', '!=', '')
            ->groupBy('custom_value')
            ->orderBy('custom_value')
            ->get()
            ->map(fn($row) => [
                'value' => $row->value,
                'products_count' => $row->products_count,
                'product_ids' => array_map('intval', explode(',', $row->product_ids)),
            ]);
    }

    /**
     * Get products by custom value for feature type
     *
     * @param int $featureTypeId Feature type ID
     * @param string $customValue Custom value
     * @return Collection
     */
    public function getProductsByCustomValue(int $featureTypeId, string $customValue): Collection
    {
        return ProductFeature::where('feature_type_id', $featureTypeId)
            ->whereNull('feature_value_id')
            ->where('custom_value', $customValue)
            ->with(['product:id,sku,name'])
            ->get()
            ->map(fn($pf) => [
                'id' => $pf->product->id,
                'sku' => $pf->product->sku,
                'name' => $pf->product->name,
                'custom_value' => $pf->custom_value,
            ]);
    }
}
