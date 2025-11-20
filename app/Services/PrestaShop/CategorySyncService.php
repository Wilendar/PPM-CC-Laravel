<?php

namespace App\Services\PrestaShop;

use App\Models\Category;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\BasePrestaShopClient;
use App\Services\PrestaShop\CategoryMapper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Category Sync Service for PrestaShop
 *
 * BUGFIX 2025-11-05: Ensures categories exist in PrestaShop before product sync
 *
 * Workflow:
 * 1. Check if category mapping exists (PPM → PrestaShop)
 * 2. If NO mapping → Create category in PrestaShop
 * 3. Create mapping in shop_mappings table
 * 4. Return PrestaShop category ID
 *
 * This ensures ProductTransformer can always map categories successfully
 *
 * @package App\Services\PrestaShop
 * @version 1.0
 * @since ETAP_07 BUGFIX
 */
class CategorySyncService
{
    public function __construct(
        private readonly CategoryMapper $categoryMapper
    ) {}

    /**
     * Ensure category exists in PrestaShop
     *
     * Checks if category mapping exists, if not creates category in PrestaShop
     *
     * @param Category $category PPM category
     * @param BasePrestaShopClient $client PrestaShop API client
     * @param PrestaShopShop $shop Shop instance
     * @return int PrestaShop category ID
     * @throws \Exception On sync failure
     */
    public function ensureCategoryExists(
        Category $category,
        BasePrestaShopClient $client,
        PrestaShopShop $shop
    ): int {
        // Check if mapping already exists
        $existingId = $this->categoryMapper->mapToPrestaShop($category->id, $shop);

        if ($existingId) {
            Log::debug('Category mapping exists', [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'prestashop_id' => $existingId,
                'shop_id' => $shop->id,
            ]);

            return $existingId;
        }

        // No mapping exists - need to create category in PrestaShop
        Log::info('Category not mapped, creating in PrestaShop', [
            'category_id' => $category->id,
            'category_name' => $category->name,
            'shop_id' => $shop->id,
        ]);

        // Create category in PrestaShop
        $prestashopCategoryId = $this->createCategoryInPrestaShop($category, $client, $shop);

        // Create mapping
        $this->categoryMapper->createMapping(
            $category->id,
            $shop,
            $prestashopCategoryId,
            $category->name
        );

        Log::info('Category synced to PrestaShop', [
            'category_id' => $category->id,
            'category_name' => $category->name,
            'prestashop_id' => $prestashopCategoryId,
            'shop_id' => $shop->id,
        ]);

        return $prestashopCategoryId;
    }

    /**
     * Sync all product categories to PrestaShop
     *
     * Ensures all categories assigned to product exist in PrestaShop
     *
     * @param \Illuminate\Database\Eloquent\Collection $categories Product categories
     * @param BasePrestaShopClient $client PrestaShop API client
     * @param PrestaShopShop $shop Shop instance
     * @return array Array of PrestaShop category IDs
     */
    public function syncProductCategories(
        \Illuminate\Database\Eloquent\Collection $categories,
        BasePrestaShopClient $client,
        PrestaShopShop $shop
    ): array {
        $prestashopCategoryIds = [];

        foreach ($categories as $category) {
            try {
                $prestashopId = $this->ensureCategoryExists($category, $client, $shop);
                $prestashopCategoryIds[] = $prestashopId;
            } catch (\Exception $e) {
                Log::error('Failed to sync category', [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'shop_id' => $shop->id,
                    'error' => $e->getMessage(),
                ]);

                // Continue with other categories
            }
        }

        return $prestashopCategoryIds;
    }

    /**
     * Create category in PrestaShop via API
     *
     * @param Category $category PPM category
     * @param BasePrestaShopClient $client PrestaShop API client
     * @param PrestaShopShop $shop Shop instance
     * @return int PrestaShop category ID
     * @throws \Exception On API failure
     */
    private function createCategoryInPrestaShop(
        Category $category,
        BasePrestaShopClient $client,
        PrestaShopShop $shop
    ): int {
        // Build category data for PrestaShop
        $categoryData = [
            'category' => [
                'name' => [
                    ['id' => 1, 'value' => $category->name]
                ],
                'link_rewrite' => [
                    ['id' => 1, 'value' => Str::slug($category->name)]
                ],
                'description' => [
                    ['id' => 1, 'value' => $category->description ?? '']
                ],
                'active' => 1,
                'id_parent' => $this->getParentCategoryId($category, $client, $shop),
            ]
        ];

        // Convert to XML
        $xmlBody = $client->arrayToXml($categoryData);

        // Create category via POST
        $response = $client->makeRequest('POST', '/categories', [], [
            'body' => $xmlBody,
            'headers' => [
                'Content-Type' => 'application/xml',
            ],
        ]);

        if (!isset($response['category']['id'])) {
            throw new \Exception('PrestaShop API did not return category ID');
        }

        $prestashopCategoryId = (int) $response['category']['id'];

        Log::info('Category created in PrestaShop', [
            'category_id' => $category->id,
            'category_name' => $category->name,
            'prestashop_id' => $prestashopCategoryId,
            'shop_id' => $shop->id,
        ]);

        return $prestashopCategoryId;
    }

    /**
     * Get parent category ID for PrestaShop
     *
     * Maps PPM parent category to PrestaShop ID, or returns default (2 = Home)
     *
     * @param Category $category PPM category
     * @param BasePrestaShopClient $client PrestaShop API client
     * @param PrestaShopShop $shop Shop instance
     * @return int PrestaShop parent category ID
     */
    private function getParentCategoryId(
        Category $category,
        BasePrestaShopClient $client,
        PrestaShopShop $shop
    ): int {
        // Check if category has parent
        if (!$category->parent_id) {
            return 2; // PrestaShop default category (Home)
        }

        // Try to map parent category
        $parentPrestashopId = $this->categoryMapper->mapToPrestaShop($category->parent_id, $shop);

        if ($parentPrestashopId) {
            return $parentPrestashopId;
        }

        // Parent not mapped - use default
        Log::debug('Parent category not mapped, using default', [
            'category_id' => $category->id,
            'parent_id' => $category->parent_id,
            'shop_id' => $shop->id,
        ]);

        return 2; // PrestaShop default category (Home)
    }
}
