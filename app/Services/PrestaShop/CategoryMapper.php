<?php

namespace App\Services\PrestaShop;

use App\Models\Category;
use App\Models\PrestaShopShop;
use App\Models\ShopMapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Category Mapper for PrestaShop Integration
 *
 * ETAP_07 FAZA 1D - Data Layer
 *
 * Maps PPM categories to PrestaShop category IDs using ShopMapping model
 *
 * Features:
 * - Persistent mapping storage (shop_mappings table)
 * - Cache layer for performance (15min TTL)
 * - Automatic mapping creation
 * - Bidirectional mapping (PPM ↔ PrestaShop)
 * - NULL safety for unmapped categories
 *
 * @package App\Services\PrestaShop
 * @version 1.0
 * @since ETAP_07 FAZA 1D
 */
class CategoryMapper
{
    /**
     * Cache TTL in seconds (15 minutes)
     */
    private const CACHE_TTL = 900;

    /**
     * Map PPM category ID to PrestaShop category ID
     *
     * @param int $categoryId PPM category ID
     * @param PrestaShopShop $shop Shop instance
     * @return int|null PrestaShop category ID or null if not mapped
     */
    public function mapToPrestaShop(int $categoryId, PrestaShopShop $shop): ?int
    {
        // Cache key for this mapping
        $cacheKey = $this->getCacheKey($shop->id, $categoryId);

        // Try cache first
        $prestashopId = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($categoryId, $shop) {
            return $this->fetchMapping($categoryId, $shop);
        });

        if ($prestashopId === null) {
            Log::debug('Category mapping not found', [
                'category_id' => $categoryId,
                'shop_id' => $shop->id,
            ]);
        }

        return $prestashopId;
    }

    /**
     * Map PrestaShop category ID back to PPM category ID
     *
     * @param int $prestashopId PrestaShop category ID
     * @param PrestaShopShop $shop Shop instance
     * @return int|null PPM category ID or null if not mapped
     */
    public function mapFromPrestaShop(int $prestashopId, PrestaShopShop $shop): ?int
    {
        $mapping = ShopMapping::where('shop_id', $shop->id)
            ->where('mapping_type', ShopMapping::TYPE_CATEGORY)
            ->where('prestashop_id', $prestashopId)
            ->where('is_active', true)
            ->first();

        if (!$mapping) {
            return null;
        }

        // PPM value is stored as string, cast to int
        return (int) $mapping->ppm_value;
    }

    /**
     * Map PrestaShop category ID to PPM category ID, or create if missing
     *
     * ETAP_07b FAZA 1 - Auto-Create Missing Categories WITH HIERARCHY
     *
     * Workflow:
     * 1. Try mapFromPrestaShop() first (check existing mapping)
     * 2. If null → Fetch category details from PrestaShop API
     * 3. Extract parent_id from PrestaShop response
     * 4. If parent exists → Recursively create/map parent first
     * 5. Search for existing PPM category by NAME (case-insensitive)
     * 6. If not found → Create new PPM category with proper parent_id
     * 7. Create ShopMapping entry
     * 8. Return PPM category ID
     *
     * This allows PPM to automatically sync category tree from PrestaShop
     * WITH FULL HIERARCHY PRESERVATION (parent→child relationships).
     *
     * @param int $prestashopId PrestaShop category ID
     * @param PrestaShopShop $shop Shop instance
     * @return int PPM category ID (existing or newly created)
     * @throws \Exception If PrestaShop API fails or category data invalid
     */
    public function mapOrCreateFromPrestaShop(int $prestashopId, PrestaShopShop $shop): int
    {
        // Try existing mapping first
        $ppmId = $this->mapFromPrestaShop($prestashopId, $shop);

        if ($ppmId !== null) {
            return $ppmId;
        }

        Log::info('[AUTO-CREATE CATEGORY] PrestaShop category not mapped, fetching from API', [
            'prestashop_id' => $prestashopId,
            'shop_id' => $shop->id,
        ]);

        // Fetch category details from PrestaShop API
        // Use PrestaShopClientFactory to create version-specific client
        $client = \App\Services\PrestaShop\PrestaShopClientFactory::create($shop);
        $categoryData = $client->getCategory($prestashopId);

        if (!$categoryData) {
            throw new \Exception("Failed to fetch PrestaShop category {$prestashopId}");
        }

        // Unwrap if API returns nested 'category' key
        if (isset($categoryData['category'])) {
            $categoryData = $categoryData['category'];
        }

        // Extract category name (multilang format: [{'id': 1, 'value': 'Name'}])
        $categoryName = data_get($categoryData, 'name.0.value') ?? data_get($categoryData, 'name');

        if (!$categoryName) {
            throw new \Exception("PrestaShop category {$prestashopId} has no name");
        }

        // Extract parent_id from PrestaShop response
        $prestashopParentId = isset($categoryData['id_parent']) ? (int) $categoryData['id_parent'] : null;

        Log::info('[AUTO-CREATE CATEGORY] Fetched category from PrestaShop', [
            'prestashop_id' => $prestashopId,
            'name' => $categoryName,
            'parent_id' => $prestashopParentId,
        ]);

        // HIERARCHY SUPPORT: Recursively create/map parent if exists
        // PrestaShop roots: 1 = Home, 2 = Root catalog (should NOT be created in PPM)
        $ppmParentId = null;
        if ($prestashopParentId && !in_array($prestashopParentId, [1, 2], true)) {
            try {
                // Recursively create parent category first
                $ppmParentId = $this->mapOrCreateFromPrestaShop($prestashopParentId, $shop);

                Log::info('[AUTO-CREATE CATEGORY] Parent category mapped/created', [
                    'prestashop_parent_id' => $prestashopParentId,
                    'ppm_parent_id' => $ppmParentId,
                ]);
            } catch (\Exception $e) {
                // Parent creation failed - log warning but continue (create as root)
                Log::warning('[AUTO-CREATE CATEGORY] Failed to create parent category', [
                    'prestashop_parent_id' => $prestashopParentId,
                    'error' => $e->getMessage(),
                ]);
                $ppmParentId = null;
            }
        }

        // Search for existing PPM category by name (case-insensitive)
        $ppmCategory = Category::whereRaw('LOWER(name) = ?', [strtolower($categoryName)])->first();

        if (!$ppmCategory) {
            // FIX 2025-11-25: Default parent to "Wszystko" (id=2) if no parent found
            // This ensures all imported categories have proper PPM hierarchy
            $effectiveParentId = $ppmParentId ?? 2; // Wszystko (id=2) as fallback

            // Create new PPM category WITH HIERARCHY
            $ppmCategory = Category::create([
                'name' => $categoryName,
                'parent_id' => $effectiveParentId,
                'is_active' => true,
            ]);

            Log::info('[AUTO-CREATE CATEGORY] Created new PPM category with hierarchy', [
                'ppm_id' => $ppmCategory->id,
                'name' => $categoryName,
                'parent_id' => $ppmParentId,
                'prestashop_id' => $prestashopId,
                'prestashop_parent_id' => $prestashopParentId,
            ]);
        } else {
            // Category exists by name - update parent_id if missing
            if ($ppmCategory->parent_id === null && $ppmParentId !== null) {
                $ppmCategory->update(['parent_id' => $ppmParentId]);

                Log::info('[AUTO-CREATE CATEGORY] Updated existing PPM category with parent', [
                    'ppm_id' => $ppmCategory->id,
                    'name' => $categoryName,
                    'parent_id' => $ppmParentId,
                ]);
            } else {
                Log::info('[AUTO-CREATE CATEGORY] Found existing PPM category by name', [
                    'ppm_id' => $ppmCategory->id,
                    'name' => $categoryName,
                    'parent_id' => $ppmCategory->parent_id,
                ]);
            }
        }

        // Create mapping
        $this->createMapping($ppmCategory->id, $shop, $prestashopId, $categoryName);

        Log::info('[AUTO-CREATE CATEGORY] Mapping created', [
            'ppm_id' => $ppmCategory->id,
            'prestashop_id' => $prestashopId,
            'shop_id' => $shop->id,
            'hierarchy_preserved' => $ppmParentId !== null,
        ]);

        return $ppmCategory->id;
    }

    /**
     * Create or update category mapping
     *
     * @param int $categoryId PPM category ID
     * @param PrestaShopShop $shop Shop instance
     * @param int $prestashopId PrestaShop category ID
     * @param string|null $prestashopName PrestaShop category name (optional)
     * @return ShopMapping Created or updated mapping
     */
    public function createMapping(
        int $categoryId,
        PrestaShopShop $shop,
        int $prestashopId,
        ?string $prestashopName = null
    ): ShopMapping {
        // Validate category exists
        $category = Category::find($categoryId);

        if (!$category) {
            throw new \InvalidArgumentException("Category not found: {$categoryId}");
        }

        // Create or update mapping
        $mapping = ShopMapping::createOrUpdateMapping(
            shopId: $shop->id,
            type: ShopMapping::TYPE_CATEGORY,
            ppmValue: (string) $categoryId,
            prestashopId: $prestashopId,
            prestashopValue: $prestashopName
        );

        // Clear cache for this mapping
        $this->clearCache($shop->id, $categoryId);

        Log::info('Category mapping created/updated', [
            'category_id' => $categoryId,
            'category_name' => $category->name,
            'shop_id' => $shop->id,
            'prestashop_id' => $prestashopId,
        ]);

        return $mapping;
    }

    /**
     * Delete category mapping
     *
     * @param int $categoryId PPM category ID
     * @param PrestaShopShop $shop Shop instance
     * @return bool Success status
     */
    public function deleteMapping(int $categoryId, PrestaShopShop $shop): bool
    {
        $deleted = ShopMapping::where('shop_id', $shop->id)
            ->where('mapping_type', ShopMapping::TYPE_CATEGORY)
            ->where('ppm_value', (string) $categoryId)
            ->delete();

        if ($deleted > 0) {
            // Clear cache
            $this->clearCache($shop->id, $categoryId);

            Log::info('Category mapping deleted', [
                'category_id' => $categoryId,
                'shop_id' => $shop->id,
            ]);
        }

        return $deleted > 0;
    }

    /**
     * Get all category mappings for shop
     *
     * @param PrestaShopShop $shop Shop instance
     * @return \Illuminate\Support\Collection Collection of ShopMapping instances
     */
    public function getAllMappingsForShop(PrestaShopShop $shop)
    {
        return ShopMapping::where('shop_id', $shop->id)
            ->where('mapping_type', ShopMapping::TYPE_CATEGORY)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Check if category is mapped for shop
     *
     * @param int $categoryId PPM category ID
     * @param PrestaShopShop $shop Shop instance
     * @return bool True if mapped
     */
    public function isMapped(int $categoryId, PrestaShopShop $shop): bool
    {
        return $this->mapToPrestaShop($categoryId, $shop) !== null;
    }

    /**
     * Fetch mapping from database
     *
     * @param int $categoryId PPM category ID
     * @param PrestaShopShop $shop Shop instance
     * @return int|null PrestaShop category ID or null
     */
    private function fetchMapping(int $categoryId, PrestaShopShop $shop): ?int
    {
        $mapping = ShopMapping::where('shop_id', $shop->id)
            ->where('mapping_type', ShopMapping::TYPE_CATEGORY)
            ->where('ppm_value', (string) $categoryId)
            ->where('is_active', true)
            ->first();

        return $mapping ? $mapping->prestashop_id : null;
    }

    /**
     * Get cache key for mapping
     *
     * @param int $shopId Shop ID
     * @param int $categoryId Category ID
     * @return string Cache key
     */
    private function getCacheKey(int $shopId, int $categoryId): string
    {
        return "category_mapping:{$shopId}:{$categoryId}";
    }

    /**
     * Clear cache for mapping
     *
     * @param int $shopId Shop ID
     * @param int $categoryId Category ID
     */
    private function clearCache(int $shopId, int $categoryId): void
    {
        Cache::forget($this->getCacheKey($shopId, $categoryId));
    }

    /**
     * Clear all category mapping cache for shop
     *
     * @param PrestaShopShop $shop Shop instance
     */
    public function clearAllCacheForShop(PrestaShopShop $shop): void
    {
        $mappings = $this->getAllMappingsForShop($shop);

        foreach ($mappings as $mapping) {
            $this->clearCache($shop->id, (int) $mapping->ppm_value);
        }

        Log::info('Cleared all category mapping cache for shop', [
            'shop_id' => $shop->id,
            'mappings_cleared' => $mappings->count(),
        ]);
    }

    /**
     * Get mapping status for PPM category and shop
     *
     * ETAP_07b FAZA 1 - Category Mapping Status
     *
     * Returns mapping status:
     * - 'mapped': Category has active mapping in shop_mappings
     * - 'unmapped': Category has no mapping for this shop
     *
     * Used by ProductForm UI to display mapping status badges.
     *
     * @param int $ppmCategoryId PPM category ID
     * @param int $shopId Shop ID
     * @return string 'mapped' or 'unmapped'
     */
    public function getMappingStatus(int $ppmCategoryId, int $shopId): string
    {
        $mapping = ShopMapping::where('shop_id', $shopId)
            ->where('mapping_type', ShopMapping::TYPE_CATEGORY)
            ->where('ppm_value', (string) $ppmCategoryId)
            ->where('is_active', true)
            ->first();

        return $mapping ? 'mapped' : 'unmapped';
    }
}
