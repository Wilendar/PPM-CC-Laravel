<?php

namespace App\Services\PrestaShop;

use App\Models\PrestaShopShop;
use App\Exceptions\PrestaShopAPIException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * PrestaShop Category Service
 *
 * ETAP_07b FAZA 1 - PrestaShop Category API Integration
 *
 * Handles category operations for PrestaShop shops:
 * - Fetching categories from PrestaShop API
 * - Building hierarchical category trees
 * - Caching category data (15min TTL, stale-while-revalidate)
 * - PrestaShop 8.x & 9.x compatibility
 *
 * Architecture:
 * - Uses BasePrestaShopClient for API calls
 * - Cache strategy: flexible cache (15min normal, 60min stale fallback)
 * - Graceful degradation on API errors
 *
 * @package App\Services\PrestaShop
 * @version 1.0
 * @since ETAP_07b FAZA 1
 */
class PrestaShopCategoryService
{
    /**
     * Cache TTL settings
     */
    private const CACHE_TTL_NORMAL = 900;     // 15 minutes
    private const CACHE_TTL_STALE = 3600;     // 60 minutes (stale fallback)

    /**
     * PrestaShop Client Factory
     *
     * @var PrestaShopClientFactory
     */
    protected PrestaShopClientFactory $clientFactory;

    /**
     * Constructor
     *
     * @param PrestaShopClientFactory $clientFactory
     */
    public function __construct(PrestaShopClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }

    /**
     * Get cached category tree for shop
     *
     * Returns hierarchical category tree with cache (15min TTL).
     * On cache miss, fetches fresh data from PrestaShop API.
     * On API error, uses stale cache (max 60min) if available.
     *
     * @param PrestaShopShop $shop Shop instance
     * @return array Hierarchical category tree
     *
     * Example return:
     * [
     *   ['id' => 2, 'name' => 'Home', 'id_parent' => 1, 'level' => 1, 'children' => [
     *     ['id' => 3, 'name' => 'Clothes', 'id_parent' => 2, 'level' => 2, 'children' => [...]],
     *   ]],
     * ]
     */
    public function getCachedCategoryTree(PrestaShopShop $shop): array
    {
        $cacheKey = $this->getCacheKey($shop->id);

        try {
            // Laravel 12.x flexible cache: normal TTL with stale fallback
            return Cache::flexible(
                $cacheKey,
                [self::CACHE_TTL_NORMAL, self::CACHE_TTL_STALE],
                function () use ($shop) {
                    Log::info('Fetching fresh categories from PrestaShop', [
                        'shop_id' => $shop->id,
                        'shop_name' => $shop->name,
                    ]);

                    // Fetch flat categories from API
                    $flatCategories = $this->fetchCategoriesFromShop($shop);

                    // Build hierarchical tree
                    $tree = $this->buildCategoryTree($flatCategories);

                    Log::info('Category tree built successfully', [
                        'shop_id' => $shop->id,
                        'category_count' => count($flatCategories),
                        'tree_roots' => count($tree),
                    ]);

                    return $tree;
                }
            );
        } catch (PrestaShopAPIException $e) {
            Log::warning('PrestaShop API error, attempting to use stale cache', [
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            // Try to get stale cache
            $staleData = Cache::get($cacheKey);

            if ($staleData !== null) {
                Log::info('Using stale category cache due to API error', [
                    'shop_id' => $shop->id,
                ]);
                return $staleData;
            }

            // No cache available, return empty array
            Log::error('No category cache available after API error', [
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Fetch categories from PrestaShop API
     *
     * Fetches all categories from shop using PrestaShop Web Services API.
     * Normalizes differences between PrestaShop 8.x and 9.x responses.
     *
     * @param PrestaShopShop $shop Shop instance
     * @return array Flat array of categories
     *
     * @throws PrestaShopAPIException On API errors
     *
     * Example return:
     * [
     *   ['id' => 2, 'name' => 'Home', 'id_parent' => 1, 'position' => 0, 'active' => '1'],
     *   ['id' => 3, 'name' => 'Clothes', 'id_parent' => 2, 'position' => 1, 'active' => '1'],
     * ]
     */
    public function fetchCategoriesFromShop(PrestaShopShop $shop): array
    {
        // Create appropriate client (v8 or v9)
        $client = $this->clientFactory->create($shop);

        Log::info('Fetching categories from PrestaShop', [
            'shop_id' => $shop->id,
            'shop_name' => $shop->name,
            'prestashop_version' => $client->getVersion(),
        ]);

        try {
            // GET /categories?display=full (returns all category details)
            $response = $client->makeRequest('GET', 'categories?display=full');

            // Normalize response (PrestaShop returns different structures)
            $categories = $this->normalizeCategoriesResponse($response);

            Log::info('Categories fetched successfully', [
                'shop_id' => $shop->id,
                'category_count' => count($categories),
            ]);

            return $categories;

        } catch (PrestaShopAPIException $e) {
            Log::error('Failed to fetch categories from PrestaShop', [
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
                'error' => $e->getMessage(),
                'http_status' => $e->getHttpStatusCode(),
            ]);

            throw $e;
        }
    }

    /**
     * Fetch single category by ID from PrestaShop API
     *
     * ETAP_07b FAZA 3: Used by CategoryCreationJob to fetch category details
     * before creating in PPM.
     *
     * Fetches specific category details including:
     * - id: PrestaShop category ID
     * - name: Category name (multilang)
     * - id_parent: Parent category ID
     * - description: Category description (optional)
     * - position: Sort order
     * - active: Active status
     *
     * @param PrestaShopShop $shop Shop instance
     * @param int $categoryId PrestaShop category ID
     * @return array|null Category data or null if not found
     *
     * @throws PrestaShopAPIException On API errors
     *
     * Example return:
     * [
     *   'id' => 800,
     *   'name' => 'Wheels',
     *   'id_parent' => 2,
     *   'description' => 'All wheels and rims',
     *   'position' => 5,
     *   'active' => '1',
     * ]
     */
    public function fetchCategoryById(PrestaShopShop $shop, int $categoryId): ?array
    {
        // Create appropriate client (v8 or v9)
        $client = $this->clientFactory->create($shop);

        Log::info('Fetching single category from PrestaShop', [
            'shop_id' => $shop->id,
            'shop_name' => $shop->name,
            'category_id' => $categoryId,
            'prestashop_version' => $client->getVersion(),
        ]);

        try {
            // GET /categories/{id}?display=full (returns single category details)
            $response = $client->makeRequest('GET', "categories/{$categoryId}?display=full");

            // Normalize response (single category)
            $category = $this->normalizeSingleCategoryResponse($response);

            if (!$category) {
                Log::warning('Category not found in PrestaShop', [
                    'shop_id' => $shop->id,
                    'category_id' => $categoryId,
                ]);
                return null;
            }

            Log::info('Category fetched successfully', [
                'shop_id' => $shop->id,
                'category_id' => $categoryId,
                'category_name' => $category['name'] ?? 'Unknown',
            ]);

            return $category;

        } catch (PrestaShopAPIException $e) {
            // 404 Not Found is not an error (category doesn't exist)
            if ($e->getHttpStatusCode() === 404) {
                Log::warning('Category not found (404)', [
                    'shop_id' => $shop->id,
                    'category_id' => $categoryId,
                ]);
                return null;
            }

            Log::error('Failed to fetch category from PrestaShop', [
                'shop_id' => $shop->id,
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
                'http_status' => $e->getHttpStatusCode(),
            ]);

            throw $e;
        }
    }

    /**
     * Build hierarchical category tree from flat array
     *
     * Transforms flat category array into hierarchical tree structure.
     * Handles parent-child relationships, sorting by position, depth levels.
     *
     * @param array $flatCategories Flat array of categories
     * @return array Hierarchical tree
     *
     * Algorithm:
     * 1. Index categories by ID for fast lookup
     * 2. Build parent-child relationships
     * 3. Sort children by position
     * 4. Calculate depth levels
     */
    public function buildCategoryTree(array $flatCategories): array
    {
        if (empty($flatCategories)) {
            return [];
        }

        // Index categories by ID for fast lookup
        $categoriesById = [];
        foreach ($flatCategories as $category) {
            $id = (int) $category['id'];
            $categoriesById[$id] = array_merge($category, [
                'children' => [],
                'level' => 0,
            ]);
        }

        // Build parent-child relationships
        $tree = [];
        foreach ($categoriesById as $id => $category) {
            $parentId = (int) ($category['id_parent'] ?? 0);

            if ($parentId === 0 || !isset($categoriesById[$parentId])) {
                // Root category (no parent or parent doesn't exist)
                $tree[$id] = &$categoriesById[$id];
            } else {
                // Add as child to parent
                $categoriesById[$parentId]['children'][$id] = &$categoriesById[$id];
            }
        }

        // Sort children by position and calculate levels
        $this->sortAndCalculateLevels($tree, 1);

        return array_values($tree);
    }

    /**
     * Clear cache for shop
     *
     * Forces fresh API call on next getCachedCategoryTree() request.
     *
     * @param PrestaShopShop $shop Shop instance
     * @return void
     */
    public function clearCache(PrestaShopShop $shop): void
    {
        $cacheKey = $this->getCacheKey($shop->id);
        Cache::forget($cacheKey);

        Log::info('Category cache cleared', [
            'shop_id' => $shop->id,
            'cache_key' => $cacheKey,
        ]);
    }

    /**
     * Get cache key for shop categories
     *
     * @param int $shopId Shop ID
     * @return string Cache key
     */
    protected function getCacheKey(int $shopId): string
    {
        return "prestashop_categories_{$shopId}";
    }

    /**
     * Normalize categories response from PrestaShop API
     *
     * Handles different response formats between PrestaShop 8.x and 9.x.
     * Extracts essential fields: id, name, id_parent, position, active.
     *
     * @param array $response API response
     * @return array Normalized categories
     */
    protected function normalizeCategoriesResponse(array $response): array
    {
        // PrestaShop API returns: {"categories": [{"id": 1, ...}, ...]}
        $categoriesData = $response['categories'] ?? [];

        // Handle single category vs array of categories
        if (isset($categoriesData['category'])) {
            $categoriesData = is_array($categoriesData['category'])
                && isset($categoriesData['category'][0])
                ? $categoriesData['category']
                : [$categoriesData['category']];
        }

        $normalized = [];

        foreach ($categoriesData as $category) {
            // Extract multilang field (name)
            $name = $this->extractMultilangField($category, 'name');

            $normalized[] = [
                'id' => (int) ($category['id'] ?? 0),
                'name' => $name,
                'id_parent' => (int) ($category['id_parent'] ?? 0),
                'position' => (int) ($category['position'] ?? 0),
                'active' => (bool) ($category['active'] ?? true),
                'level_depth' => (int) ($category['level_depth'] ?? 0),
            ];
        }

        return $normalized;
    }

    /**
     * Normalize single category response from PrestaShop API
     *
     * ETAP_07b FAZA 3: Used by fetchCategoryById() to normalize single category.
     *
     * Similar to normalizeCategoriesResponse() but handles single category response.
     * Extracts: id, name, id_parent, description, position, active.
     *
     * @param array $response API response
     * @return array|null Normalized category or null if invalid
     */
    protected function normalizeSingleCategoryResponse(array $response): ?array
    {
        // PrestaShop API returns: {"category": {"id": 1, "name": {...}, ...}}
        $categoryData = $response['category'] ?? null;

        if (!$categoryData || !isset($categoryData['id'])) {
            return null;
        }

        // Extract multilang fields
        $name = $this->extractMultilangField($categoryData, 'name');
        $description = $this->extractMultilangField($categoryData, 'description');

        return [
            'id' => (int) $categoryData['id'],
            'name' => $name,
            'id_parent' => (int) ($categoryData['id_parent'] ?? 2), // Default to root
            'description' => $description,
            'position' => (int) ($categoryData['position'] ?? 0),
            'active' => (bool) ($categoryData['active'] ?? true),
            'level_depth' => (int) ($categoryData['level_depth'] ?? 0),
        ];
    }

    /**
     * Extract multilang field from PrestaShop response
     *
     * PrestaShop multilang fields format:
     * {"name": {"language": [{"id": "1", "value": "Home"}, {"id": "2", "value": "Accueil"}]}}
     *
     * Returns first language value (usually ID 1).
     *
     * @param array $data Category data
     * @param string $field Field name (e.g., 'name')
     * @return string Extracted value
     */
    protected function extractMultilangField(array $data, string $field): string
    {
        if (!isset($data[$field])) {
            return '';
        }

        $fieldData = $data[$field];

        // Multilang format: {"language": [{"id": "1", "value": "Text"}]}
        if (isset($fieldData['language']) && is_array($fieldData['language'])) {
            $languages = $fieldData['language'];

            // Single language: {"language": {"id": "1", "value": "Text"}}
            if (isset($languages['id']) && isset($languages['value'])) {
                return (string) $languages['value'];
            }

            // Multiple languages: [{"id": "1", "value": "Text"}, ...]
            if (isset($languages[0]['value'])) {
                return (string) $languages[0]['value'];
            }
        }

        // Simple string value
        return (string) $fieldData;
    }

    /**
     * Sort children by position and calculate depth levels recursively
     *
     * @param array &$categories Categories array (passed by reference)
     * @param int $level Current depth level
     * @return void
     */
    protected function sortAndCalculateLevels(array &$categories, int $level): void
    {
        foreach ($categories as &$category) {
            // Set level
            $category['level'] = $level;

            // Sort children by position
            if (!empty($category['children'])) {
                uasort($category['children'], function ($a, $b) {
                    return ($a['position'] ?? 0) <=> ($b['position'] ?? 0);
                });

                // Recursive call for children
                $this->sortAndCalculateLevels($category['children'], $level + 1);
            }
        }
    }
}
