<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PrestaShopShop;
use App\Models\Category;
use App\Services\PrestaShop\PrestaShopImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * PrestaShop Category API Controller
 *
 * ETAP_07 FAZA 2B.1 - Dynamic Category Loading API
 *
 * Provides API endpoints for fetching PrestaShop category trees dynamically
 * for ProductForm shop tabs. Implements caching strategy dla performance.
 *
 * Features:
 * - GET /api/prestashop/categories/{shopId} - Fetch category tree (cached)
 * - POST /api/prestashop/categories/{shopId}/refresh - Clear cache + re-fetch
 * - 15-minute cache TTL dla balance between freshness and performance
 * - Hierarchical tree structure dla UI rendering
 * - Comprehensive error handling z proper HTTP status codes
 *
 * Response Format:
 * ```json
 * {
 *   "success": true,
 *   "shop_id": 1,
 *   "shop_name": "Sklep Demo",
 *   "categories": [
 *     {
 *       "id": 2,
 *       "name": "Home",
 *       "name_en": "Home",
 *       "parent_id": null,
 *       "level": 0,
 *       "prestashop_id": 2,
 *       "children": [...]
 *     }
 *   ],
 *   "cached": true,
 *   "cache_expires_at": "2025-10-03 15:30:00"
 * }
 * ```
 *
 * Usage Example:
 * ```javascript
 * // Fetch categories for shop
 * const response = await fetch('/api/prestashop/categories/1');
 * const data = await response.json();
 *
 * // Refresh cache
 * await fetch('/api/prestashop/categories/1/refresh', { method: 'POST' });
 * ```
 *
 * @package App\Http\Controllers\API
 * @version 1.0
 * @since ETAP_07 FAZA 2B.1
 */
class PrestaShopCategoryController extends Controller
{
    /**
     * Cache TTL in seconds (15 minutes)
     *
     * Balance between fresh data and API call reduction.
     * Adjust based on category update frequency.
     */
    private const CACHE_TTL = 900; // 15 minutes

    /**
     * Constructor with dependency injection
     *
     * @param PrestaShopImportService $importService
     */
    public function __construct(
        protected PrestaShopImportService $importService
    ) {}

    /**
     * Get category tree for PrestaShop shop (cached)
     *
     * Fetches complete category hierarchy from PrestaShop API z 15-minute caching.
     * Cache key is unique per shop to avoid cross-contamination.
     *
     * Performance:
     * - First request (cache miss): 2-5s (API fetch + transform)
     * - Cached requests: < 100ms
     *
     * HTTP Method: GET
     * Route: /api/prestashop/categories/{shopId}
     *
     * @param int $shopId PrestaShopShop ID
     * @return JsonResponse
     *
     * Response Codes:
     * - 200: Success - categories fetched
     * - 404: Shop not found
     * - 500: PrestaShop API error or internal error
     */
    public function getCategoryTree(int $shopId): JsonResponse
    {
        try {
            // Validate shop exists
            $shop = PrestaShopShop::findOrFail($shopId);

            // Cache key unique per shop
            $cacheKey = "prestashop_categories_shop_{$shopId}";

            // Check cache first
            $cached = Cache::has($cacheKey);

            // Fetch from cache or PrestaShop API
            $categoryTree = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($shop) {
                Log::info('Fetching PrestaShop categories (cache miss)', [
                    'shop_id' => $shop->id,
                    'shop_name' => $shop->name,
                ]);

                // Fetch categories from PrestaShop via importTreeFromPrestaShop
                $categories = Category::importTreeFromPrestaShop($shop);

                // Build hierarchical structure
                return $this->buildCategoryTree($categories);
            });

            Log::info('PrestaShop categories fetched', [
                'shop_id' => $shop->id,
                'cached' => $cached,
                'category_count' => count($categoryTree),
            ]);

            return response()->json([
                'success' => true,
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
                'categories' => $categoryTree,
                'cached' => $cached,
                'cache_expires_at' => now()->addSeconds(self::CACHE_TTL)->toDateTimeString(),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Shop not found
            Log::warning('PrestaShop shop not found', [
                'shop_id' => $shopId,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'PrestaShop shop not found',
            ], 404);

        } catch (\Exception $e) {
            // Handle all other errors
            Log::error('Failed to fetch PrestaShop categories', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch categories: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear category cache for specific shop and re-fetch
     *
     * Forces fresh data fetch from PrestaShop API, bypassing cache.
     * Useful when categories are updated in PrestaShop and immediate
     * refresh is needed.
     *
     * HTTP Method: POST
     * Route: /api/prestashop/categories/{shopId}/refresh
     *
     * @param int $shopId PrestaShopShop ID
     * @return JsonResponse
     *
     * Response Codes:
     * - 200: Success - cache cleared and fresh data returned
     * - 404: Shop not found
     * - 500: PrestaShop API error or internal error
     */
    public function refreshCache(int $shopId): JsonResponse
    {
        try {
            // Validate shop exists
            $shop = PrestaShopShop::findOrFail($shopId);

            // Clear cache
            $cacheKey = "prestashop_categories_shop_{$shopId}";
            Cache::forget($cacheKey);

            Log::info('PrestaShop categories cache cleared', [
                'shop_id' => $shop->id,
            ]);

            // Re-fetch with fresh data (will rebuild cache)
            return $this->getCategoryTree($shopId);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Shop not found
            Log::warning('PrestaShop shop not found', [
                'shop_id' => $shopId,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'PrestaShop shop not found',
            ], 404);

        } catch (\Exception $e) {
            // Handle all other errors
            Log::error('Failed to refresh PrestaShop categories cache', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to refresh cache: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build hierarchical category tree from flat collection
     *
     * Transforms flat array of Category models into nested hierarchical structure
     * suitable for UI rendering (tree components, dropdowns).
     *
     * Algorithm:
     * 1. Group categories by parent_id
     * 2. Recursively build tree starting from root (parent_id = null)
     * 3. Attach children to each parent
     *
     * Performance: O(n log n) where n = number of categories
     *
     * @param \Illuminate\Support\Collection|array $categories Flat category collection
     * @return array Hierarchical tree structure
     */
    protected function buildCategoryTree($categories): array
    {
        // Convert to collection if array
        if (is_array($categories)) {
            $categories = collect($categories);
        }

        // Group by parent_id dla faster lookup
        $grouped = $categories->groupBy('parent_id');

        // Recursive tree builder
        $buildTree = function ($parentId = null) use ($grouped, &$buildTree) {
            $children = $grouped->get($parentId, collect());

            return $children->map(function ($category) use ($buildTree) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'name_en' => $category->name_en ?? $category->name,
                    'parent_id' => $category->parent_id,
                    'level' => $category->level ?? 0,
                    'prestashop_id' => $this->getPrestashopCategoryId($category),
                    'is_active' => $category->is_active ?? true,
                    'children' => $buildTree($category->id)->toArray(),
                ];
            })->toArray();
        };

        // Start from root categories (parent_id = null)
        return $buildTree(null);
    }

    /**
     * Get PrestaShop category ID for category
     *
     * Attempts to retrieve PrestaShop category ID from category's
     * prestashopMappings relationship. Returns null if not mapped.
     *
     * @param \App\Models\Category $category
     * @return int|null PrestaShop category ID or null
     */
    protected function getPrestashopCategoryId(Category $category): ?int
    {
        try {
            // Check if prestashopMappings relationship is loaded
            if ($category->relationLoaded('prestashopMappings')) {
                $mapping = $category->prestashopMappings->first();
                return $mapping?->prestashop_id;
            }

            // Fallback: Query mappings
            $mapping = $category->prestashopMappings()->first();
            return $mapping?->prestashop_id;

        } catch (\Exception $e) {
            Log::warning('Failed to get PrestaShop category ID', [
                'category_id' => $category->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
