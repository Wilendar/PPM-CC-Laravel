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
}
