<?php

namespace App\Services;

use App\Models\Category;
use App\Models\PrestaShopShop;
use App\Models\ShopMapping;
use App\Services\PrestaShop\PrestaShopCategoryService;
use App\Services\PrestaShop\CategoryMapper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Category Comparison Service
 *
 * ETAP_07f Import Modal Redesign - FAZA 1
 *
 * Compares category trees between PPM and PrestaShop:
 * - Builds hierarchical comparison tree
 * - Identifies categories only in PrestaShop (to add)
 * - Identifies categories only in PPM (to remove)
 * - Identifies synchronized categories (in both)
 * - Calculates summary statistics
 *
 * Used by CategoryPreviewModal for enhanced category visualization.
 *
 * @package App\Services
 * @version 1.0
 * @since ETAP_07f
 */
class CategoryComparisonService
{
    /**
     * Status constants for category comparison
     */
    public const STATUS_SYNCED = 'both';           // W obu systemach
    public const STATUS_PS_ONLY = 'prestashop_only'; // Tylko PrestaShop (do dodania)
    public const STATUS_PPM_ONLY = 'ppm_only';      // Tylko PPM (do usuniecia)

    /**
     * PrestaShop Category Service
     */
    protected PrestaShopCategoryService $categoryService;

    /**
     * Category Mapper
     */
    protected CategoryMapper $categoryMapper;

    /**
     * Constructor
     */
    public function __construct(
        PrestaShopCategoryService $categoryService,
        CategoryMapper $categoryMapper
    ) {
        $this->categoryService = $categoryService;
        $this->categoryMapper = $categoryMapper;
    }

    /**
     * Build comparison tree for shop
     *
     * Returns hierarchical tree with status for each category:
     * - 'both': Category exists in both PPM and PrestaShop
     * - 'prestashop_only': Category only in PrestaShop (to add)
     * - 'ppm_only': Category only in PPM (to remove)
     *
     * @param PrestaShopShop $shop Shop instance
     * @return array Hierarchical comparison tree
     */
    public function buildComparisonTree(PrestaShopShop $shop): array
    {
        Log::info('[CategoryComparison] Building comparison tree', [
            'shop_id' => $shop->id,
            'shop_name' => $shop->name,
        ]);

        // 1. Fetch PrestaShop categories
        $psCategories = $this->categoryService->getCachedCategoryTree($shop);

        // 2. Get all PPM categories
        $ppmCategories = Category::with('children')
            ->whereNull('parent_id')
            ->orWhere('parent_id', 2) // Root "Wszystko" children
            ->get();

        // 3. Get existing mappings for this shop
        $mappings = $this->categoryMapper->getAllMappingsForShop($shop);
        $mappingsByPsId = $mappings->keyBy('prestashop_id');
        $mappingsByPpmId = $mappings->keyBy('ppm_value');

        // 4. Build comparison tree
        $comparisonTree = $this->mergeCategories(
            $psCategories,
            $ppmCategories,
            $mappingsByPsId,
            $mappingsByPpmId,
            $shop->id
        );

        Log::info('[CategoryComparison] Comparison tree built', [
            'shop_id' => $shop->id,
            'tree_size' => count($comparisonTree),
        ]);

        return $comparisonTree;
    }

    /**
     * Get summary statistics for comparison
     *
     * @param PrestaShopShop $shop Shop instance
     * @return array Summary statistics
     */
    public function getSummary(PrestaShopShop $shop): array
    {
        $tree = $this->buildComparisonTree($shop);

        $summary = [
            'categories_synced' => 0,
            'categories_to_add' => 0,
            'categories_to_remove' => 0,
            'total_prestashop' => 0,
            'total_ppm' => 0,
        ];

        $this->calculateSummaryRecursive($tree, $summary);

        Log::info('[CategoryComparison] Summary calculated', [
            'shop_id' => $shop->id,
            'summary' => $summary,
        ]);

        return $summary;
    }

    /**
     * Get categories only in PrestaShop (to add to PPM)
     *
     * @param PrestaShopShop $shop Shop instance
     * @return array Categories to add
     */
    public function getCategoriesOnlyInPrestaShop(PrestaShopShop $shop): array
    {
        $tree = $this->buildComparisonTree($shop);
        return $this->filterByStatus($tree, self::STATUS_PS_ONLY);
    }

    /**
     * Get categories only in PPM (candidates for removal)
     *
     * @param PrestaShopShop $shop Shop instance
     * @return array Categories to remove
     */
    public function getCategoriesOnlyInPPM(PrestaShopShop $shop): array
    {
        $tree = $this->buildComparisonTree($shop);
        return $this->filterByStatus($tree, self::STATUS_PPM_ONLY);
    }

    /**
     * Get synchronized categories (in both systems)
     *
     * @param PrestaShopShop $shop Shop instance
     * @return array Synchronized categories
     */
    public function getSynchronizedCategories(PrestaShopShop $shop): array
    {
        $tree = $this->buildComparisonTree($shop);
        return $this->filterByStatus($tree, self::STATUS_SYNCED);
    }

    /**
     * Merge PrestaShop and PPM categories into comparison tree
     *
     * @param array $psCategories PrestaShop category tree
     * @param Collection $ppmCategories PPM categories
     * @param Collection $mappingsByPsId Mappings indexed by PS ID
     * @param Collection $mappingsByPpmId Mappings indexed by PPM ID
     * @param int $shopId Shop ID
     * @return array Merged comparison tree
     */
    protected function mergeCategories(
        array $psCategories,
        Collection $ppmCategories,
        Collection $mappingsByPsId,
        Collection $mappingsByPpmId,
        int $shopId
    ): array {
        $result = [];

        // Track processed PPM categories
        $processedPpmIds = [];

        // Process PrestaShop categories
        foreach ($psCategories as $psCategory) {
            $psId = (int) $psCategory['id'];

            // Skip root categories (id=1, id=2)
            if ($psId <= 2) {
                // Process children of root
                if (!empty($psCategory['children'])) {
                    $childResult = $this->mergeCategories(
                        $psCategory['children'],
                        $ppmCategories,
                        $mappingsByPsId,
                        $mappingsByPpmId,
                        $shopId
                    );
                    $result = array_merge($result, $childResult);
                }
                continue;
            }

            // Check if mapped to PPM
            $mapping = $mappingsByPsId->get($psId);
            $ppmId = $mapping ? (int) $mapping->ppm_value : null;
            $ppmCategory = $ppmId ? Category::find($ppmId) : null;

            // Determine status
            $status = $mapping ? self::STATUS_SYNCED : self::STATUS_PS_ONLY;

            // Track processed PPM ID
            if ($ppmId) {
                $processedPpmIds[] = $ppmId;
            }

            // Build node
            $node = $this->buildComparisonNode(
                ppmId: $ppmId,
                psId: $psId,
                name: $psCategory['name'],
                status: $status,
                level: $psCategory['level'] ?? 1,
                ppmCategory: $ppmCategory,
                psCategory: $psCategory
            );

            // Process children recursively
            if (!empty($psCategory['children'])) {
                $node['children'] = $this->mergeCategories(
                    $psCategory['children'],
                    $ppmCategories,
                    $mappingsByPsId,
                    $mappingsByPpmId,
                    $shopId
                );
            }

            $result[] = $node;
        }

        // Add PPM-only categories (not mapped to any PS category)
        $this->addPpmOnlyCategories(
            $result,
            $ppmCategories,
            $processedPpmIds,
            $mappingsByPpmId,
            $shopId
        );

        return $result;
    }

    /**
     * Add PPM-only categories to result
     *
     * @param array &$result Result array (by reference)
     * @param Collection $ppmCategories PPM categories
     * @param array $processedPpmIds Already processed PPM IDs
     * @param Collection $mappingsByPpmId Mappings indexed by PPM ID
     * @param int $shopId Shop ID
     */
    protected function addPpmOnlyCategories(
        array &$result,
        Collection $ppmCategories,
        array $processedPpmIds,
        Collection $mappingsByPpmId,
        int $shopId
    ): void {
        foreach ($ppmCategories as $ppmCategory) {
            // Skip if already processed
            if (in_array($ppmCategory->id, $processedPpmIds)) {
                continue;
            }

            // Skip root categories
            if ($ppmCategory->id <= 2) {
                continue;
            }

            // Check if has mapping (should be processed already)
            $mapping = $mappingsByPpmId->get((string) $ppmCategory->id);
            if ($mapping) {
                continue;
            }

            // PPM-only category
            $node = $this->buildComparisonNode(
                ppmId: $ppmCategory->id,
                psId: null,
                name: $ppmCategory->name,
                status: self::STATUS_PPM_ONLY,
                level: $this->calculatePpmLevel($ppmCategory),
                ppmCategory: $ppmCategory,
                psCategory: null
            );

            // Process children
            if ($ppmCategory->children->isNotEmpty()) {
                $node['children'] = [];
                foreach ($ppmCategory->children as $child) {
                    if (!in_array($child->id, $processedPpmIds)) {
                        $childNode = $this->buildComparisonNode(
                            ppmId: $child->id,
                            psId: null,
                            name: $child->name,
                            status: self::STATUS_PPM_ONLY,
                            level: $this->calculatePpmLevel($child),
                            ppmCategory: $child,
                            psCategory: null
                        );
                        $node['children'][] = $childNode;
                    }
                }
            }

            $result[] = $node;
        }
    }

    /**
     * Build comparison node structure
     *
     * @param int|null $ppmId PPM category ID
     * @param int|null $psId PrestaShop category ID
     * @param string $name Category name
     * @param string $status Comparison status
     * @param int $level Depth level
     * @param Category|null $ppmCategory PPM category instance
     * @param array|null $psCategory PrestaShop category data
     * @return array Comparison node
     */
    protected function buildComparisonNode(
        ?int $ppmId,
        ?int $psId,
        string $name,
        string $status,
        int $level,
        ?Category $ppmCategory,
        ?array $psCategory
    ): array {
        // Calculate product counts
        $productCountPpm = $ppmCategory?->products()->count() ?? 0;

        return [
            'id' => $ppmId,
            'prestashop_id' => $psId,
            'name' => $name,
            'full_path' => $this->buildFullPath($ppmCategory, $psCategory),
            'level' => $level,
            'status' => $status,
            'is_mapped' => ($ppmId !== null && $psId !== null),
            'product_count_ppm' => $productCountPpm,
            'children' => [],
            'can_delete' => ($status === self::STATUS_PPM_ONLY && $productCountPpm === 0),
            'is_selected' => false,
        ];
    }

    /**
     * Build full path for category
     *
     * @param Category|null $ppmCategory PPM category
     * @param array|null $psCategory PrestaShop category
     * @return string Full path
     */
    protected function buildFullPath(?Category $ppmCategory, ?array $psCategory): string
    {
        if ($ppmCategory) {
            $path = [];
            $current = $ppmCategory;
            while ($current) {
                array_unshift($path, $current->name);
                $current = $current->parent;
            }
            return implode(' > ', $path);
        }

        if ($psCategory) {
            return $psCategory['name'];
        }

        return '';
    }

    /**
     * Calculate PPM category level
     *
     * @param Category $category PPM category
     * @return int Level depth
     */
    protected function calculatePpmLevel(Category $category): int
    {
        $level = 1;
        $current = $category->parent;
        while ($current && $level < 5) {
            $level++;
            $current = $current->parent;
        }
        return $level;
    }

    /**
     * Calculate summary statistics recursively
     *
     * @param array $tree Comparison tree
     * @param array &$summary Summary array (by reference)
     */
    protected function calculateSummaryRecursive(array $tree, array &$summary): void
    {
        foreach ($tree as $node) {
            switch ($node['status']) {
                case self::STATUS_SYNCED:
                    $summary['categories_synced']++;
                    $summary['total_prestashop']++;
                    $summary['total_ppm']++;
                    break;
                case self::STATUS_PS_ONLY:
                    $summary['categories_to_add']++;
                    $summary['total_prestashop']++;
                    break;
                case self::STATUS_PPM_ONLY:
                    $summary['categories_to_remove']++;
                    $summary['total_ppm']++;
                    break;
            }

            // Process children
            if (!empty($node['children'])) {
                $this->calculateSummaryRecursive($node['children'], $summary);
            }
        }
    }

    /**
     * Filter tree by status
     *
     * @param array $tree Comparison tree
     * @param string $status Status to filter
     * @return array Filtered categories (flat)
     */
    protected function filterByStatus(array $tree, string $status): array
    {
        $result = [];

        foreach ($tree as $node) {
            if ($node['status'] === $status) {
                $result[] = [
                    'id' => $node['id'],
                    'prestashop_id' => $node['prestashop_id'],
                    'name' => $node['name'],
                    'full_path' => $node['full_path'],
                    'level' => $node['level'],
                    'product_count_ppm' => $node['product_count_ppm'],
                    'can_delete' => $node['can_delete'] ?? false,
                ];
            }

            // Process children
            if (!empty($node['children'])) {
                $childResults = $this->filterByStatus($node['children'], $status);
                $result = array_merge($result, $childResults);
            }
        }

        return $result;
    }

    /**
     * Get comparison tree as flat array for easier processing
     *
     * @param PrestaShopShop $shop Shop instance
     * @return array Flat array of all categories with status
     */
    public function getFlatComparisonList(PrestaShopShop $shop): array
    {
        $tree = $this->buildComparisonTree($shop);
        return $this->flattenTree($tree);
    }

    /**
     * Flatten tree to array
     *
     * @param array $tree Hierarchical tree
     * @return array Flat array
     */
    protected function flattenTree(array $tree): array
    {
        $result = [];

        foreach ($tree as $node) {
            $nodeWithoutChildren = $node;
            unset($nodeWithoutChildren['children']);
            $result[] = $nodeWithoutChildren;

            if (!empty($node['children'])) {
                $result = array_merge($result, $this->flattenTree($node['children']));
            }
        }

        return $result;
    }
}
