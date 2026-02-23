<?php

namespace App\Services\Compatibility;

use App\Models\PrestaShopShop;
use App\Models\SmartSyncBrandRule;
use App\Models\VehicleCompatibility;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * ShopFilteringService
 *
 * ETAP_05d FAZA 2.2 - Per-shop vehicle filtering service
 *
 * PURPOSE:
 * - Filter vehicles (products with type='pojazd') by shop's allowed brands
 * - Get per-shop compatibility records
 * - Cache frequently used data
 * - Support multi-store display logic
 *
 * ARCHITECTURE (2025-12-08):
 * - Vehicles = Products with product_type_id pointing to 'pojazd' type
 * - Brand = manufacturer column in products table
 * - Model = name column in products table
 * - FK vehicle_compatibility.vehicle_model_id → products.id
 *
 * BUSINESS RULES:
 * - allowed_vehicle_brands = null → all brands allowed
 * - allowed_vehicle_brands = [] → no brands (compatibility disabled)
 * - allowed_vehicle_brands = ["YCF", "KAYO"] → only these brands
 *
 * USAGE:
 * ```php
 * $service = app(ShopFilteringService::class);
 * $vehicles = $service->getFilteredVehicles($shop);
 * $compatibilities = $service->getProductCompatibilities($product, $shop);
 * ```
 */
class ShopFilteringService
{
    /**
     * Cache TTL for vehicle lists (in seconds)
     */
    protected const CACHE_TTL = 3600; // 1 hour

    /**
     * Get allowed brands from smart_sync_brand_rules (priority)
     * Falls back to legacy allowed_vehicle_brands if no smart rules exist
     *
     * @param PrestaShopShop $shop
     * @return array|null null = all brands, [] = none, array = whitelist
     */
    public function getSmartAllowedBrands(PrestaShopShop $shop): ?array
    {
        // Check if smart rules exist for this shop
        $smartRules = SmartSyncBrandRule::forShop($shop->id)->get();

        if ($smartRules->isEmpty()) {
            // No smart rules - fall back to legacy
            return $shop->allowed_vehicle_brands;
        }

        // Smart rules exist - use them
        $allowedBrands = $smartRules
            ->where('is_allowed', true)
            ->pluck('brand')
            ->toArray();

        // If all rules are "not allowed" → return empty (disabled)
        if (empty($allowedBrands)) {
            return [];
        }

        return $allowedBrands;
    }

    /**
     * Get allowed vehicle brands for a shop
     *
     * @param PrestaShopShop $shop
     * @return array|null null = all brands, [] = none, array = whitelist
     */
    public function getAllowedBrands(PrestaShopShop $shop): ?array
    {
        return $this->getSmartAllowedBrands($shop);
    }

    /**
     * Get all unique vehicle brands in the system
     *
     * 2025-12-08: Uses Product::byType('pojazd') - manufacturer column
     * FK changed to point to products table
     *
     * @return Collection<string>
     */
    public function getAllBrands(): Collection
    {
        return Cache::remember('vehicle_brands_all', self::CACHE_TTL, function () {
            return Product::byType('pojazd')
                ->whereNotNull('manufacturer')
                ->where('manufacturer', '!=', '')
                ->distinct()
                ->orderBy('manufacturer')
                ->pluck('manufacturer');
        });
    }

    /**
     * Get vehicles filtered by shop's brand restrictions
     *
     * 2025-12-08: Returns Product (type=pojazd) instead of VehicleModel
     *
     * @param PrestaShopShop $shop
     * @param bool $useCache
     * @return Collection<Product>
     */
    public function getFilteredVehicles(PrestaShopShop $shop, bool $useCache = true): Collection
    {
        $cacheKey = "shop_{$shop->id}_filtered_vehicles";

        if (!$useCache) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($shop) {
            return $this->queryFilteredVehicles($shop)->get();
        });
    }

    /**
     * Get filtered vehicles query builder (for pagination)
     *
     * 2025-12-08: Uses Product::byType('pojazd') instead of VehicleModel
     * - manufacturer column = brand
     * - name column = model
     *
     * @param PrestaShopShop $shop
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function queryFilteredVehicles(PrestaShopShop $shop)
    {
        $query = Product::byType('pojazd');

        $allowedBrands = $this->getSmartAllowedBrands($shop);

        if ($allowedBrands === null) {
            // null = all brands allowed, no filter
        } elseif (empty($allowedBrands)) {
            // empty array = no brands allowed
            $query->whereRaw('1 = 0'); // Force empty result
        } else {
            // array = whitelist filter
            $query->whereIn('manufacturer', $allowedBrands);
        }

        return $query->orderBy('manufacturer')->orderBy('name');
    }

    /**
     * Get vehicles grouped by brand for a shop
     *
     * 2025-12-08: Groups by 'manufacturer' column (was 'brand')
     *
     * @param PrestaShopShop $shop
     * @return Collection<string, Collection<Product>>
     */
    public function getVehiclesGroupedByBrand(PrestaShopShop $shop): Collection
    {
        return $this->getFilteredVehicles($shop)->groupBy('manufacturer');
    }

    /**
     * Get minimum confidence score for a specific brand in a shop
     */
    public function getMinConfidenceForBrand(PrestaShopShop $shop, string $brand): float
    {
        $rule = SmartSyncBrandRule::forShop($shop->id)
            ->where('brand', $brand)
            ->first();

        return $rule ? (float) $rule->min_confidence : 0.50;
    }

    /**
     * Check if auto-sync is enabled for a brand in a shop
     */
    public function isAutoSyncEnabled(PrestaShopShop $shop, string $brand): bool
    {
        $rule = SmartSyncBrandRule::forShop($shop->id)
            ->where('brand', $brand)
            ->where('is_allowed', true)
            ->first();

        return $rule ? $rule->auto_sync : false;
    }

    /**
     * Check if a specific brand is allowed for a shop
     *
     * @param PrestaShopShop $shop
     * @param string $brand
     * @return bool
     */
    public function isBrandAllowed(PrestaShopShop $shop, string $brand): bool
    {
        return $shop->isVehicleBrandAllowed($brand);
    }

    /**
     * Get compatibility records for a product in a specific shop
     *
     * 2025-12-08: Uses vehicleProduct instead of vehicleModel
     *
     * @param Product $product
     * @param PrestaShopShop $shop
     * @return Collection<VehicleCompatibility>
     */
    public function getProductCompatibilities(Product $product, PrestaShopShop $shop): Collection
    {
        return VehicleCompatibility::byProduct($product->id)
            ->byShop($shop->id)
            ->with(['vehicleProduct', 'compatibilityAttribute', 'compatibilitySource'])
            ->get();
    }

    /**
     * Get compatibility records for a product across ALL shops
     *
     * 2025-12-08: Uses vehicleProduct instead of vehicleModel
     *
     * @param Product $product
     * @return Collection<VehicleCompatibility>
     */
    public function getProductCompatibilitiesAllShops(Product $product): Collection
    {
        return VehicleCompatibility::byProduct($product->id)
            ->with(['shop', 'vehicleProduct', 'compatibilityAttribute', 'compatibilitySource'])
            ->get();
    }

    /**
     * Get compatibility records grouped by shop
     *
     * @param Product $product
     * @return Collection<int, Collection<VehicleCompatibility>>
     */
    public function getProductCompatibilitiesGroupedByShop(Product $product): Collection
    {
        return $this->getProductCompatibilitiesAllShops($product)
            ->groupBy('shop_id');
    }

    /**
     * Get products compatible with a vehicle in a specific shop
     *
     * 2025-12-08: Vehicle is now a Product (type=pojazd)
     *
     * @param Product $vehicleProduct Vehicle product (type=pojazd)
     * @param PrestaShopShop $shop
     * @return Collection<Product>
     */
    public function getCompatibleProducts(Product $vehicleProduct, PrestaShopShop $shop): Collection
    {
        $productIds = VehicleCompatibility::byVehicle($vehicleProduct->id)
            ->byShop($shop->id)
            ->pluck('product_id')
            ->unique();

        return Product::whereIn('id', $productIds)->get();
    }

    /**
     * Count vehicles available for a shop
     *
     * @param PrestaShopShop $shop
     * @return int
     */
    public function countFilteredVehicles(PrestaShopShop $shop): int
    {
        $cacheKey = "shop_{$shop->id}_vehicle_count";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($shop) {
            return $this->queryFilteredVehicles($shop)->count();
        });
    }

    /**
     * Count products with compatibility in a shop
     *
     * @param PrestaShopShop $shop
     * @return int
     */
    public function countProductsWithCompatibility(PrestaShopShop $shop): int
    {
        return VehicleCompatibility::byShop($shop->id)
            ->distinct('product_id')
            ->count('product_id');
    }

    /**
     * Get compatibility statistics for a shop
     *
     * @param PrestaShopShop $shop
     * @return array
     */
    public function getShopStatistics(PrestaShopShop $shop): array
    {
        $base = VehicleCompatibility::byShop($shop->id);

        return [
            'total_compatibilities' => (clone $base)->count(),
            'unique_products' => (clone $base)->distinct('product_id')->count('product_id'),
            'unique_vehicles' => (clone $base)->distinct('vehicle_model_id')->count('vehicle_model_id'),
            'verified' => (clone $base)->verified()->count(),
            'suggested' => (clone $base)->suggested()->count(),
            'available_vehicles' => $this->countFilteredVehicles($shop),
            'allowed_brands' => $shop->allowed_vehicle_brands,
        ];
    }

    /**
     * Copy compatibilities from one shop to another
     *
     * 2025-12-08: Uses vehicleProduct->manufacturer instead of vehicleModel->brand
     *
     * @param PrestaShopShop $sourceShop
     * @param PrestaShopShop $targetShop
     * @param bool $overwrite Overwrite existing in target?
     * @return int Number of records copied
     */
    public function copyCompatibilities(
        PrestaShopShop $sourceShop,
        PrestaShopShop $targetShop,
        bool $overwrite = false
    ): int {
        $sourceRecords = VehicleCompatibility::byShop($sourceShop->id)->get();
        $copied = 0;

        foreach ($sourceRecords as $record) {
            // Check if vehicle brand is allowed in target shop
            // 2025-12-08: vehicleProduct->manufacturer instead of vehicleModel->brand
            if (!$targetShop->isVehicleBrandAllowed($record->vehicleProduct->manufacturer ?? '')) {
                continue;
            }

            // Check for existing record
            $existing = VehicleCompatibility::where('product_id', $record->product_id)
                ->where('vehicle_model_id', $record->vehicle_model_id)
                ->where('shop_id', $targetShop->id)
                ->first();

            if ($existing && !$overwrite) {
                continue;
            }

            if ($existing && $overwrite) {
                $existing->delete();
            }

            // Create new record for target shop
            VehicleCompatibility::create([
                'product_id' => $record->product_id,
                'vehicle_model_id' => $record->vehicle_model_id,
                'shop_id' => $targetShop->id,
                'compatibility_attribute_id' => $record->compatibility_attribute_id,
                'compatibility_source_id' => $record->compatibility_source_id,
                'verified' => false, // Reset verification for new shop
                'notes' => $record->notes,
                'is_suggested' => false,
                'metadata' => [
                    'copied_from_shop_id' => $sourceShop->id,
                    'copied_at' => now()->toIso8601String(),
                ],
            ]);

            $copied++;
        }

        // Clear cache for target shop
        $this->clearShopCache($targetShop);

        return $copied;
    }

    /**
     * Clear all cached data for a shop
     *
     * @param PrestaShopShop $shop
     */
    public function clearShopCache(PrestaShopShop $shop): void
    {
        Cache::forget("shop_{$shop->id}_filtered_vehicles");
        Cache::forget("shop_{$shop->id}_vehicle_count");
        Cache::forget("smart_sync_rules_shop_{$shop->id}");
    }

    /**
     * Clear all vehicle-related caches
     */
    public function clearAllCaches(): void
    {
        Cache::forget('vehicle_brands_all');

        // Clear per-shop caches
        $shopIds = PrestaShopShop::pluck('id');
        foreach ($shopIds as $shopId) {
            Cache::forget("shop_{$shopId}_filtered_vehicles");
            Cache::forget("shop_{$shopId}_vehicle_count");
        }

        // Clear smart sync rules cache
        foreach ($shopIds as $shopId) {
            Cache::forget("smart_sync_rules_shop_{$shopId}");
        }
    }
}
