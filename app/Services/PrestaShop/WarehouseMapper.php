<?php

namespace App\Services\PrestaShop;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\PrestaShopShop;
use App\Models\ShopMapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Warehouse Mapper for PrestaShop Integration
 *
 * ETAP_07 FAZA 1D - Data Layer
 *
 * Maps PPM warehouses to PrestaShop stock management
 *
 * PPM Warehouses (6+ custom):
 * - MPPTRADE (main warehouse)
 * - Pitbike.pl
 * - Cameraman
 * - Otopit
 * - INFMS
 * - Reklamacje (returns)
 *
 * PrestaShop Stock:
 * - Advanced stock management with warehouses
 * - Or simple quantity field (no warehouse separation)
 *
 * Features:
 * - Persistent mapping storage (shop_mappings table)
 * - Cache layer for performance (15min TTL)
 * - Stock aggregation from mapped warehouses
 * - Support for shop-specific warehouse selection
 * - NULL safety for unmapped warehouses
 *
 * @package App\Services\PrestaShop
 * @version 1.0
 * @since ETAP_07 FAZA 1D
 */
class WarehouseMapper
{
    /**
     * Cache TTL in seconds (15 minutes)
     */
    private const CACHE_TTL = 900;

    /**
     * Map PPM warehouse ID to PrestaShop warehouse ID
     *
     * @param int $warehouseId PPM warehouse ID
     * @param PrestaShopShop $shop Shop instance
     * @return int|null PrestaShop warehouse ID or null if not mapped
     */
    public function mapToPrestaShop(int $warehouseId, PrestaShopShop $shop): ?int
    {
        // Cache key for this mapping
        $cacheKey = $this->getCacheKey($shop->id, $warehouseId);

        // Try cache first
        $prestashopId = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($warehouseId, $shop) {
            return $this->fetchMapping($warehouseId, $shop);
        });

        if ($prestashopId === null) {
            Log::debug('Warehouse mapping not found', [
                'warehouse_id' => $warehouseId,
                'shop_id' => $shop->id,
            ]);
        }

        return $prestashopId;
    }

    /**
     * Map PrestaShop warehouse ID back to PPM warehouse ID
     *
     * @param int $prestashopId PrestaShop warehouse ID
     * @param PrestaShopShop $shop Shop instance
     * @return int|null PPM warehouse ID or null if not mapped
     */
    public function mapFromPrestaShop(int $prestashopId, PrestaShopShop $shop): ?int
    {
        $mapping = ShopMapping::where('shop_id', $shop->id)
            ->where('mapping_type', ShopMapping::TYPE_WAREHOUSE)
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
     * Calculate total stock for product on specific shop
     *
     * Aggregates stock from all warehouses mapped to this shop
     *
     * @param Product $product Product instance
     * @param PrestaShopShop $shop Shop instance
     * @return int Total available stock
     */
    public function calculateStockForShop(Product $product, PrestaShopShop $shop): int
    {
        $totalStock = 0;

        // Get all mapped warehouses for this shop
        $mappedWarehouses = $this->getAllMappingsForShop($shop);

        if ($mappedWarehouses->isEmpty()) {
            // Fallback: If no warehouses mapped, sum ALL warehouses
            Log::warning('No warehouse mappings found, using all warehouses', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
            ]);

            return $product->getTotalAvailableStock();
        }

        // Sum stock from mapped warehouses only
        foreach ($mappedWarehouses as $mapping) {
            $warehouseId = (int) $mapping->ppm_value;

            $stock = $product->getWarehouseStock($warehouseId);
            $totalStock += $stock;

            Log::debug('Added warehouse stock to total', [
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId,
                'stock' => $stock,
                'running_total' => $totalStock,
            ]);
        }

        Log::info('Calculated stock for shop', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'total_stock' => $totalStock,
            'mapped_warehouses_count' => $mappedWarehouses->count(),
        ]);

        return $totalStock;
    }

    /**
     * Get warehouses assigned to shop
     *
     * @param PrestaShopShop $shop Shop instance
     * @return \Illuminate\Support\Collection Collection of Warehouse models
     */
    public function getWarehousesForShop(PrestaShopShop $shop)
    {
        $mappings = $this->getAllMappingsForShop($shop);

        $warehouseIds = $mappings->pluck('ppm_value')->map(fn($id) => (int) $id)->toArray();

        if (empty($warehouseIds)) {
            return collect();
        }

        return Warehouse::whereIn('id', $warehouseIds)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Create or update warehouse mapping
     *
     * @param int $warehouseId PPM warehouse ID
     * @param PrestaShopShop $shop Shop instance
     * @param int $prestashopWarehouseId PrestaShop warehouse ID
     * @param string|null $prestashopWarehouseName PrestaShop warehouse name (optional)
     * @return ShopMapping Created or updated mapping
     */
    public function createMapping(
        int $warehouseId,
        PrestaShopShop $shop,
        int $prestashopWarehouseId,
        ?string $prestashopWarehouseName = null
    ): ShopMapping {
        // Validate warehouse exists
        $warehouse = Warehouse::find($warehouseId);

        if (!$warehouse) {
            throw new \InvalidArgumentException("Warehouse not found: {$warehouseId}");
        }

        // Create or update mapping
        $mapping = ShopMapping::createOrUpdateMapping(
            shopId: $shop->id,
            type: ShopMapping::TYPE_WAREHOUSE,
            ppmValue: (string) $warehouseId,
            prestashopId: $prestashopWarehouseId,
            prestashopValue: $prestashopWarehouseName
        );

        // Clear cache for this mapping
        $this->clearCache($shop->id, $warehouseId);

        Log::info('Warehouse mapping created/updated', [
            'warehouse_id' => $warehouseId,
            'warehouse_code' => $warehouse->code,
            'shop_id' => $shop->id,
            'prestashop_warehouse_id' => $prestashopWarehouseId,
        ]);

        return $mapping;
    }

    /**
     * Delete warehouse mapping
     *
     * @param int $warehouseId PPM warehouse ID
     * @param PrestaShopShop $shop Shop instance
     * @return bool Success status
     */
    public function deleteMapping(int $warehouseId, PrestaShopShop $shop): bool
    {
        $deleted = ShopMapping::where('shop_id', $shop->id)
            ->where('mapping_type', ShopMapping::TYPE_WAREHOUSE)
            ->where('ppm_value', (string) $warehouseId)
            ->delete();

        if ($deleted > 0) {
            // Clear cache
            $this->clearCache($shop->id, $warehouseId);

            Log::info('Warehouse mapping deleted', [
                'warehouse_id' => $warehouseId,
                'shop_id' => $shop->id,
            ]);
        }

        return $deleted > 0;
    }

    /**
     * Get all warehouse mappings for shop
     *
     * @param PrestaShopShop $shop Shop instance
     * @return \Illuminate\Support\Collection Collection of ShopMapping instances
     */
    public function getAllMappingsForShop(PrestaShopShop $shop)
    {
        return ShopMapping::where('shop_id', $shop->id)
            ->where('mapping_type', ShopMapping::TYPE_WAREHOUSE)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Check if warehouse is mapped for shop
     *
     * @param int $warehouseId PPM warehouse ID
     * @param PrestaShopShop $shop Shop instance
     * @return bool True if mapped
     */
    public function isMapped(int $warehouseId, PrestaShopShop $shop): bool
    {
        return $this->mapToPrestaShop($warehouseId, $shop) !== null;
    }

    /**
     * Fetch mapping from database
     *
     * @param int $warehouseId PPM warehouse ID
     * @param PrestaShopShop $shop Shop instance
     * @return int|null PrestaShop warehouse ID or null
     */
    private function fetchMapping(int $warehouseId, PrestaShopShop $shop): ?int
    {
        $mapping = ShopMapping::where('shop_id', $shop->id)
            ->where('mapping_type', ShopMapping::TYPE_WAREHOUSE)
            ->where('ppm_value', (string) $warehouseId)
            ->where('is_active', true)
            ->first();

        return $mapping ? $mapping->prestashop_id : null;
    }

    /**
     * Get cache key for mapping
     *
     * @param int $shopId Shop ID
     * @param int $warehouseId Warehouse ID
     * @return string Cache key
     */
    private function getCacheKey(int $shopId, int $warehouseId): string
    {
        return "warehouse_mapping:{$shopId}:{$warehouseId}";
    }

    /**
     * Clear cache for mapping
     *
     * @param int $shopId Shop ID
     * @param int $warehouseId Warehouse ID
     */
    private function clearCache(int $shopId, int $warehouseId): void
    {
        Cache::forget($this->getCacheKey($shopId, $warehouseId));
    }

    /**
     * Clear all warehouse mapping cache for shop
     *
     * @param PrestaShopShop $shop Shop instance
     */
    public function clearAllCacheForShop(PrestaShopShop $shop): void
    {
        $mappings = $this->getAllMappingsForShop($shop);

        foreach ($mappings as $mapping) {
            $this->clearCache($shop->id, (int) $mapping->ppm_value);
        }

        Log::info('Cleared all warehouse mapping cache for shop', [
            'shop_id' => $shop->id,
            'mappings_cleared' => $mappings->count(),
        ]);
    }
}
