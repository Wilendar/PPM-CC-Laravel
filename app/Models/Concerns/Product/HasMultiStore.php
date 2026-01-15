<?php

namespace App\Models\Concerns\Product;

use App\Models\ProductShopData;
use App\Models\ShopVariant;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * HasMultiStore Trait - Multi-Store Synchronization System
 *
 * Responsibility: Multi-store PrestaShop data i sync status management
 *
 * Features:
 * - Shop-specific data per PrestaShop shop (names, descriptions, etc.)
 * - Sync status tracking per shop (synced, pending, error, conflict)
 * - Publish/unpublish products per shop
 * - Sync health monitoring
 * - Conflict detection and resolution
 *
 * Performance: Optimized dla multi-shop queries
 * Integration: PrestaShop multi-store sync ready
 *
 * @package App\Models\Concerns\Product
 * @version 1.0
 * @since ETAP_05a SEKCJA 0 - Product.php Refactoring
 */
trait HasMultiStore
{
    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS - Multi-Store Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Product shop data relationship (1:many) - FAZA 1.5: Multi-Store Synchronization System ✅ IMPLEMENTED
     *
     * Business Logic: Każdy produkt może mieć różne dane per sklep PrestaShop
     * Performance: Eager loading ready z proper indexing
     * Multi-store: Shop-specific names, descriptions, categories, images
     * Sync Status: Tracking synchronization status per shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function shopData(): HasMany
    {
        return $this->hasMany(ProductShopData::class, 'product_id', 'id')
                    ->orderBy('shop_id', 'asc');
    }

    /**
     * Active shop data only (published and sync enabled)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activeShopData(): HasMany
    {
        return $this->shopData()
                    ->where('is_published', true)
                    ->where('sync_status', '!=', 'disabled');
    }

    /**
     * Shop data for specific shop
     *
     * @param int $shopId
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dataForShop(int $shopId): HasMany
    {
        return $this->shopData()
                    ->where('shop_id', $shopId);
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS METHODS - Multi-Store Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Get or create shop data for specific shop
     *
     * @param int $shopId
     * @return \App\Models\ProductShopData
     */
    public function getOrCreateShopData(int $shopId): ProductShopData
    {
        return $this->shopData()
                    ->where('shop_id', $shopId)
                    ->firstOrCreate([
                        'product_id' => $this->id,
                        'shop_id' => $shopId,
                    ]);
    }

    /**
     * Check if product is published on specific shop
     *
     * @param int $shopId
     * @return bool
     */
    public function isPublishedOnShop(int $shopId): bool
    {
        $shopData = $this->dataForShop($shopId)->first();
        return $shopData ? $shopData->is_published : false;
    }

    /**
     * Get sync status for specific shop
     *
     * @param int $shopId
     * @return string|null
     */
    public function getSyncStatusForShop(int $shopId): ?string
    {
        $shopData = $this->dataForShop($shopId)->first();
        return $shopData ? $shopData->sync_status : null;
    }

    /**
     * Get all shops where product is published
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPublishedShops(): Collection
    {
        return $this->activeShopData()
                    ->with('shop')
                    ->get()
                    ->pluck('shop');
    }

    /**
     * Get sync status summary across all shops
     *
     * @return array
     */
    public function getMultiStoreSyncSummary(): array
    {
        $shopData = $this->shopData()->with('shop')->get();

        $summary = [
            'total_shops' => $shopData->count(),
            'published_shops' => $shopData->where('is_published', true)->count(),
            'synced_shops' => $shopData->where('sync_status', 'synced')->count(),
            'error_shops' => $shopData->where('sync_status', 'error')->count(),
            'conflict_shops' => $shopData->where('sync_status', 'conflict')->count(),
            'disabled_shops' => $shopData->where('sync_status', 'disabled')->count(),
            'shops_needing_sync' => $shopData->filter(function ($data) {
                return $data->needsSync();
            })->count(),
        ];

        $summary['sync_health_percentage'] = $summary['total_shops'] > 0
            ? round(($summary['synced_shops'] / $summary['total_shops']) * 100, 1)
            : 0;

        return $summary;
    }

    /**
     * Publish product to specific shop
     *
     * @param int $shopId
     * @param array $shopSpecificData
     * @return \App\Models\ProductShopData
     */
    public function publishToShop(int $shopId, array $shopSpecificData = []): ProductShopData
    {
        $shopData = $this->getOrCreateShopData($shopId);

        // Update shop-specific data if provided
        if (!empty($shopSpecificData)) {
            $shopData->fill($shopSpecificData);
        }

        $shopData->publish();

        return $shopData;
    }

    /**
     * Unpublish product from specific shop
     *
     * @param int $shopId
     * @return bool
     */
    public function unpublishFromShop(int $shopId): bool
    {
        $shopData = $this->dataForShop($shopId)->first();

        if ($shopData) {
            $shopData->unpublish();
            return true;
        }

        return false;
    }

    /**
     * Mark all shop data as needing sync
     *
     * @return int Count of updated records
     */
    public function markAllShopsForSync(): int
    {
        return $this->activeShopData()
                    ->update([
                        'sync_status' => 'pending',
                        'sync_errors' => null,
                        'conflict_data' => null,
                        'conflict_detected_at' => null,
                    ]);
    }

    /**
     * Get shops with sync conflicts
     *
     * @return \Illuminate\Support\Collection
     */
    public function getShopsWithConflicts(): Collection
    {
        return $this->shopData()
                    ->with('shop')
                    ->where('sync_status', 'conflict')
                    ->whereNotNull('conflict_detected_at')
                    ->get()
                    ->map(function ($shopData) {
                        return [
                            'shop_id' => $shopData->shop_id,
                            'shop_name' => $shopData->shop->name ?? 'Unknown Shop',
                            'conflict_detected_at' => $shopData->conflict_detected_at,
                            'conflict_data' => $shopData->conflict_data,
                            'time_since_conflict' => $shopData->conflict_detected_at?->diffForHumans(),
                        ];
                    });
    }

    /**
     * Get effective name for specific shop (shop-specific or fallback to product name)
     *
     * @param int $shopId
     * @return string
     */
    public function getEffectiveNameForShop(int $shopId): string
    {
        $shopData = $this->dataForShop($shopId)->first();
        return $shopData && $shopData->name ? $shopData->name : $this->name;
    }

    /**
     * Get effective description for specific shop
     *
     * @param int $shopId
     * @param string $type 'short' or 'long'
     * @return string|null
     */
    public function getEffectiveDescriptionForShop(int $shopId, string $type = 'short'): ?string
    {
        $shopData = $this->dataForShop($shopId)->first();

        if ($type === 'short') {
            return $shopData && $shopData->short_description
                ? $shopData->short_description
                : $this->short_description;
        }

        return $shopData && $shopData->long_description
            ? $shopData->long_description
            : $this->long_description;
    }

    /*
    |--------------------------------------------------------------------------
    | PER-SHOP VARIANTS SYSTEM (ETAP_05c)
    |--------------------------------------------------------------------------
    | Each shop can have different variants of the same product.
    | Shops inherit from product_variants by default, but can have:
    | - ADD: Shop-only variants (not in product_variants)
    | - OVERRIDE: Modified existing variants
    | - DELETE: Hidden variants (exist but not synced)
    */

    /**
     * Shop-specific variants relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function shopVariants(): HasMany
    {
        return $this->hasMany(ShopVariant::class, 'product_id');
    }

    /**
     * Shop variants for specific shop
     *
     * @param int $shopId
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function shopVariantsForShop(int $shopId): HasMany
    {
        return $this->shopVariants()->where('shop_id', $shopId);
    }

    /**
     * Get all variants for specific shop (base + shop-specific)
     *
     * Logic:
     * 1. Get base variants from product_variants (not deleted in shop)
     * 2. Apply overrides for variants with OVERRIDE operation
     * 3. Add shop-only variants (ADD operation)
     * 4. Return merged collection
     *
     * @param int $shopId
     * @return \Illuminate\Support\Collection
     */
    public function getVariantsForShop(int $shopId): Collection
    {
        $result = collect();

        // Get shop overrides indexed by variant_id
        $shopOverrides = $this->shopVariantsForShop($shopId)
            ->get()
            ->keyBy('variant_id');

        // 1. Process base variants (from product_variants)
        $baseVariants = $this->variants ?? collect();

        foreach ($baseVariants as $variant) {
            $override = $shopOverrides->get($variant->id);

            if ($override) {
                if ($override->isDeleteOperation()) {
                    // Skip - variant is hidden in this shop
                    continue;
                }

                if ($override->isOverrideOperation()) {
                    // Apply override - merge base with shop-specific data
                    $mergedData = $override->getEffectiveVariantData();
                    $mergedData['id'] = $variant->id;
                    $mergedData['shop_variant_id'] = $override->id;
                    $mergedData['operation_type'] = 'OVERRIDE';
                    $mergedData['sync_status'] = $override->sync_status;
                    $result->push((object) $mergedData);
                    continue;
                }
            }

            // INHERIT - use base variant as-is
            $variantData = [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'name' => $variant->name,
                'is_active' => $variant->is_active,
                'is_default' => $variant->is_default,
                'position' => $variant->position,
                'attributes' => $variant->attributes,
                'images' => $variant->images,
                'operation_type' => 'INHERIT',
                'sync_status' => 'synced',
            ];
            $result->push((object) $variantData);
        }

        // 2. Add shop-only variants (ADD operation)
        $shopOnlyVariants = $shopOverrides->filter(fn($sv) => $sv->isAddOperation());

        foreach ($shopOnlyVariants as $shopVariant) {
            $variantData = $shopVariant->getEffectiveVariantData();
            $variantData['id'] = 'shop_' . $shopVariant->id; // Prefix to distinguish from base
            $variantData['shop_variant_id'] = $shopVariant->id;
            $variantData['operation_type'] = 'ADD';
            $variantData['sync_status'] = $shopVariant->sync_status;
            $result->push((object) $variantData);
        }

        return $result->sortBy('position');
    }

    /**
     * Check if shop has any variant overrides
     *
     * @param int $shopId
     * @return bool
     */
    public function hasShopVariantOverrides(int $shopId): bool
    {
        return $this->shopVariantsForShop($shopId)
            ->where('operation_type', '!=', 'INHERIT')
            ->exists();
    }

    /**
     * Get count of pending variant syncs for shop
     *
     * @param int $shopId
     * @return int
     */
    public function getPendingVariantSyncsCount(int $shopId): int
    {
        return $this->shopVariantsForShop($shopId)
            ->where('sync_status', 'pending')
            ->count();
    }
}
