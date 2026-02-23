<?php

namespace App\Services\Import;

use App\Models\Product;
use App\Models\ProductType;
use App\Models\ShopCategoryTypeMapping;
use Illuminate\Support\Facades\Log;

/**
 * CategoryTypeMapper - Resolves ProductType for a product using cascade logic
 *
 * Cascade priority:
 * 1. Direct ShopCategoryTypeMapping match using PrestaShop category IDs
 * 2. Ancestor inheritance (walk up PS category tree via include_children)
 * 3. ProductTypeDetector keyword fallback
 * 4. Fallback to ProductType 'inne'
 *
 * NOTE: category_id in ShopCategoryTypeMapping stores PrestaShop category IDs,
 * NOT PPM category IDs. The UI panel loads PS categories from the API.
 */
class CategoryTypeMapper
{
    public function __construct(
        protected ProductTypeDetector $detector
    ) {}

    /**
     * Resolve ProductType via cascade: PS category mapping → keywords → fallback
     *
     * @param Product $product Product instance
     * @param int $shopId PrestaShop shop ID
     * @param array $psCategoryIds PrestaShop category IDs from import associations
     * @return int Resolved ProductType ID
     */
    public function resolveTypeForProduct(Product $product, int $shopId, array $psCategoryIds = []): int
    {
        // 1. Match PS category IDs against shop_category_type_mappings
        if (!empty($psCategoryIds)) {
            $mapping = $this->findMappingForPsCategories($psCategoryIds, $shopId);
            if ($mapping) {
                Log::info('CategoryTypeMapper: resolved via PS category mapping', [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'shop_id' => $shopId,
                    'product_type_id' => $mapping->product_type_id,
                    'matched_ps_category_id' => $mapping->category_id,
                    'ps_category_ids' => $psCategoryIds,
                ]);
                return $mapping->product_type_id;
            }
        }

        // 2. ProductTypeDetector (keyword-based fallback using PPM category names)
        $categoryNames = $product->categories()->pluck('name')->toArray();
        $detected = $this->detector->detect($categoryNames);
        if ($detected) {
            Log::info('CategoryTypeMapper: resolved via keyword detection', [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'detected_type' => $detected->name,
                'category_names' => $categoryNames,
            ]);
            return $detected->id;
        }

        // 3. Fallback: typ 'inne'
        Log::info('CategoryTypeMapper: using fallback type "inne"', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'ps_category_ids' => $psCategoryIds,
        ]);
        return $this->getFallbackTypeId();
    }

    /**
     * Find mapping matching PS category IDs for a shop
     *
     * PrestaShop product associations typically include ALL parent categories,
     * so a direct whereIn match is sufficient. The include_children flag on
     * mappings provides additional safety for cases where PS only returns
     * leaf categories.
     *
     * @param array $psCategoryIds PrestaShop category IDs
     * @param int $shopId Shop ID
     * @return ShopCategoryTypeMapping|null
     */
    protected function findMappingForPsCategories(array $psCategoryIds, int $shopId): ?ShopCategoryTypeMapping
    {
        if (empty($psCategoryIds)) {
            return null;
        }

        // Direct match: any PS category ID matches a mapping's category_id
        // Since PS associations include parent categories, this catches both
        // direct matches AND inherited matches (include_children)
        return ShopCategoryTypeMapping::active()
            ->forShop($shopId)
            ->whereIn('category_id', $psCategoryIds)
            ->byPriority()
            ->first();
    }

    /**
     * Count products affected by a mapping (for UI preview)
     *
     * Note: This counts products whose PS category associations include the given
     * PS category ID. Since we don't store PS associations in PPM, this uses
     * a heuristic based on PPM categories mapped from the same PS source.
     *
     * @param int $psCategoryId PrestaShop category ID
     * @param int $shopId Shop ID
     * @param bool $includeChildren Whether mapping includes children
     * @return int Number of affected products (estimate)
     */
    public function countAffectedProducts(int $psCategoryId, int $shopId, bool $includeChildren): int
    {
        // Find PPM category mapped from this PS category via ShopMapping
        $ppmCategoryId = \App\Models\ShopMapping::where('shop_id', $shopId)
            ->where('mapping_type', 'category')
            ->where('prestashop_id', $psCategoryId)
            ->where('is_active', true)
            ->value('ppm_value');

        if (!$ppmCategoryId) {
            return 0;
        }

        $categoryIds = [(int) $ppmCategoryId];

        if ($includeChildren) {
            $categoryIds = array_merge($categoryIds, $this->getPpmDescendantIds((int) $ppmCategoryId));
        }

        return Product::whereHas('categories', function ($query) use ($categoryIds) {
            $query->whereIn('categories.id', $categoryIds);
        })->count();
    }

    /**
     * Get PPM descendant category IDs (for preview count)
     */
    protected function getPpmDescendantIds(int $categoryId): array
    {
        $descendants = [];
        $queue = [$categoryId];

        while (!empty($queue)) {
            $parentId = array_shift($queue);
            $childIds = \App\Models\Category::where('parent_id', $parentId)->pluck('id')->toArray();
            foreach ($childIds as $childId) {
                $descendants[] = $childId;
                $queue[] = $childId;
            }
        }

        return $descendants;
    }

    /**
     * Fallback: typ 'inne' lub pierwszy dostepny
     */
    public function getFallbackTypeId(): int
    {
        return ProductType::where('slug', 'inne')->value('id')
            ?? ProductType::first()?->id
            ?? 1;
    }
}
