<?php

namespace App\Services\PrestaShop;

use App\Models\PriceGroup;
use App\Models\PrestaShopShop;
use App\Models\ShopMapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Price Group Mapper for PrestaShop Integration
 *
 * ETAP_07 FAZA 1D - Data Layer
 *
 * Maps PPM price groups to PrestaShop customer groups
 *
 * PPM Price Groups (8):
 * - Detaliczna (Retail)
 * - Dealer Standard
 * - Dealer Premium
 * - Warsztat Standard
 * - Warsztat Premium
 * - Szkółka (Nursery)
 * - Komis (Consignment)
 * - Drop Shipping
 *
 * PrestaShop Customer Groups (default):
 * - 1: Visitor
 * - 2: Guest
 * - 3: Customer (default)
 *
 * Features:
 * - Persistent mapping storage (shop_mappings table)
 * - Cache layer for performance (15min TTL)
 * - Default price group per shop
 * - Bidirectional mapping (PPM ↔ PrestaShop)
 *
 * @package App\Services\PrestaShop
 * @version 1.0
 * @since ETAP_07 FAZA 1D
 */
class PriceGroupMapper
{
    /**
     * Cache TTL in seconds (15 minutes)
     */
    private const CACHE_TTL = 900;

    /**
     * Map PPM price group ID to PrestaShop customer group ID
     *
     * @param int $priceGroupId PPM price group ID
     * @param PrestaShopShop $shop Shop instance
     * @return int|null PrestaShop customer group ID or null if not mapped
     */
    public function mapToPrestaShop(int $priceGroupId, PrestaShopShop $shop): ?int
    {
        // Cache key for this mapping
        $cacheKey = $this->getCacheKey($shop->id, $priceGroupId);

        // Try cache first
        $prestashopId = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($priceGroupId, $shop) {
            return $this->fetchMapping($priceGroupId, $shop);
        });

        if ($prestashopId === null) {
            Log::debug('Price group mapping not found', [
                'price_group_id' => $priceGroupId,
                'shop_id' => $shop->id,
            ]);
        }

        return $prestashopId;
    }

    /**
     * Map PrestaShop customer group ID back to PPM price group ID
     *
     * @param int $prestashopGroupId PrestaShop customer group ID
     * @param PrestaShopShop $shop Shop instance
     * @return int|null PPM price group ID or null if not mapped
     */
    public function mapFromPrestaShop(int $prestashopGroupId, PrestaShopShop $shop): ?int
    {
        $mapping = ShopMapping::where('shop_id', $shop->id)
            ->where('mapping_type', ShopMapping::TYPE_PRICE_GROUP)
            ->where('prestashop_id', $prestashopGroupId)
            ->where('is_active', true)
            ->first();

        if (!$mapping) {
            return null;
        }

        // PPM value is stored as string, cast to int
        return (int) $mapping->ppm_value;
    }

    /**
     * Get default price group for shop
     *
     * Priority:
     * 1. Shop-specific default (from shop_mappings metadata)
     * 2. "Detaliczna" (Retail) price group
     * 3. First available price group
     *
     * @param PrestaShopShop $shop Shop instance
     * @return PriceGroup Default price group
     * @throws \RuntimeException If no price groups exist
     */
    public function getDefaultPriceGroup(PrestaShopShop $shop): PriceGroup
    {
        // Try to get shop-specific default from shop configuration
        if (isset($shop->price_group_mappings['default_price_group_id'])) {
            $priceGroup = PriceGroup::find($shop->price_group_mappings['default_price_group_id']);

            if ($priceGroup) {
                return $priceGroup;
            }
        }

        // Fallback: Try "Detaliczna" (Retail) price group
        $priceGroup = PriceGroup::where('code', 'detaliczna')->first();

        if ($priceGroup) {
            return $priceGroup;
        }

        // Final fallback: First available price group
        $priceGroup = PriceGroup::where('is_active', true)->first();

        if (!$priceGroup) {
            throw new \RuntimeException('No price groups available in system');
        }

        Log::warning('Using fallback default price group', [
            'shop_id' => $shop->id,
            'price_group_id' => $priceGroup->id,
            'price_group_code' => $priceGroup->code,
        ]);

        return $priceGroup;
    }

    /**
     * Create or update price group mapping
     *
     * @param int $priceGroupId PPM price group ID
     * @param PrestaShopShop $shop Shop instance
     * @param int $prestashopGroupId PrestaShop customer group ID
     * @param string|null $prestashopGroupName PrestaShop group name (optional)
     * @return ShopMapping Created or updated mapping
     */
    public function createMapping(
        int $priceGroupId,
        PrestaShopShop $shop,
        int $prestashopGroupId,
        ?string $prestashopGroupName = null
    ): ShopMapping {
        // Validate price group exists
        $priceGroup = PriceGroup::find($priceGroupId);

        if (!$priceGroup) {
            throw new \InvalidArgumentException("Price group not found: {$priceGroupId}");
        }

        // Create or update mapping
        $mapping = ShopMapping::createOrUpdateMapping(
            shopId: $shop->id,
            type: ShopMapping::TYPE_PRICE_GROUP,
            ppmValue: (string) $priceGroupId,
            prestashopId: $prestashopGroupId,
            prestashopValue: $prestashopGroupName
        );

        // Clear cache for this mapping
        $this->clearCache($shop->id, $priceGroupId);

        Log::info('Price group mapping created/updated', [
            'price_group_id' => $priceGroupId,
            'price_group_code' => $priceGroup->code,
            'shop_id' => $shop->id,
            'prestashop_group_id' => $prestashopGroupId,
        ]);

        return $mapping;
    }

    /**
     * Delete price group mapping
     *
     * @param int $priceGroupId PPM price group ID
     * @param PrestaShopShop $shop Shop instance
     * @return bool Success status
     */
    public function deleteMapping(int $priceGroupId, PrestaShopShop $shop): bool
    {
        $deleted = ShopMapping::where('shop_id', $shop->id)
            ->where('mapping_type', ShopMapping::TYPE_PRICE_GROUP)
            ->where('ppm_value', (string) $priceGroupId)
            ->delete();

        if ($deleted > 0) {
            // Clear cache
            $this->clearCache($shop->id, $priceGroupId);

            Log::info('Price group mapping deleted', [
                'price_group_id' => $priceGroupId,
                'shop_id' => $shop->id,
            ]);
        }

        return $deleted > 0;
    }

    /**
     * Get all price group mappings for shop
     *
     * @param PrestaShopShop $shop Shop instance
     * @return \Illuminate\Support\Collection Collection of ShopMapping instances
     */
    public function getAllMappingsForShop(PrestaShopShop $shop)
    {
        return ShopMapping::where('shop_id', $shop->id)
            ->where('mapping_type', ShopMapping::TYPE_PRICE_GROUP)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Check if price group is mapped for shop
     *
     * @param int $priceGroupId PPM price group ID
     * @param PrestaShopShop $shop Shop instance
     * @return bool True if mapped
     */
    public function isMapped(int $priceGroupId, PrestaShopShop $shop): bool
    {
        return $this->mapToPrestaShop($priceGroupId, $shop) !== null;
    }

    /**
     * Fetch mapping from database
     *
     * @param int $priceGroupId PPM price group ID
     * @param PrestaShopShop $shop Shop instance
     * @return int|null PrestaShop customer group ID or null
     */
    private function fetchMapping(int $priceGroupId, PrestaShopShop $shop): ?int
    {
        $mapping = ShopMapping::where('shop_id', $shop->id)
            ->where('mapping_type', ShopMapping::TYPE_PRICE_GROUP)
            ->where('ppm_value', (string) $priceGroupId)
            ->where('is_active', true)
            ->first();

        return $mapping ? $mapping->prestashop_id : null;
    }

    /**
     * Get cache key for mapping
     *
     * @param int $shopId Shop ID
     * @param int $priceGroupId Price group ID
     * @return string Cache key
     */
    private function getCacheKey(int $shopId, int $priceGroupId): string
    {
        return "price_group_mapping:{$shopId}:{$priceGroupId}";
    }

    /**
     * Clear cache for mapping
     *
     * @param int $shopId Shop ID
     * @param int $priceGroupId Price group ID
     */
    private function clearCache(int $shopId, int $priceGroupId): void
    {
        Cache::forget($this->getCacheKey($shopId, $priceGroupId));
    }

    /**
     * Clear all price group mapping cache for shop
     *
     * @param PrestaShopShop $shop Shop instance
     */
    public function clearAllCacheForShop(PrestaShopShop $shop): void
    {
        $mappings = $this->getAllMappingsForShop($shop);

        foreach ($mappings as $mapping) {
            $this->clearCache($shop->id, (int) $mapping->ppm_value);
        }

        Log::info('Cleared all price group mapping cache for shop', [
            'shop_id' => $shop->id,
            'mappings_cleared' => $mappings->count(),
        ]);
    }
}
