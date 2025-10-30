<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CompatibilityCacheService
 *
 * Sub-service for compatibility caching operations (extracted from CompatibilityManager)
 *
 * FEATURES:
 * - Cache retrieval (SKU-based cache keys)
 * - Cache rebuild operations
 * - Cache invalidation
 * - Multi-layer caching (Laravel cache + DB cache table)
 *
 * COMPLIANCE:
 * - Laravel 12.x Service Layer patterns
 * - SKU-first architecture (cache keys based on SKU)
 * - Type hints PHP 8.3
 * - CLAUDE.md: ~150 linii limit (compliant)
 *
 * USAGE:
 * ```php
 * $cacheService = app(CompatibilityCacheService::class);
 *
 * // Get cached compatibility
 * $cached = $cacheService->getCachedCompatibility('PART-12345', $shopId);
 *
 * // Rebuild cache
 * $data = $cacheService->rebuildCache('PART-12345', $shopId);
 *
 * // Invalidate cache
 * $cacheService->invalidateCache('PART-12345', $shopId);
 * ```
 *
 * @package App\Services
 * @version 1.0
 * @since ETAP_05a FAZA 3 (2025-10-17)
 */
class CompatibilityCacheService
{
    /**
     * Cache TTL for compatibility data (15 minutes)
     */
    const CACHE_TTL = 900; // 15 minutes

    /**
     * Get cached compatibility by SKU (SKU-based cache key)
     *
     * @param string $sku Part product SKU
     * @param int $shopId Shop ID
     * @return array|null Cached compatibility data or null if not found/expired
     */
    public function getCachedCompatibility(string $sku, int $shopId): ?array
    {
        Log::debug('CompatibilityCacheService::getCachedCompatibility CALLED', [
            'sku' => $sku,
            'shop_id' => $shopId,
        ]);

        // Generate SKU-based cache key
        $cacheKey = "sku:{$sku}:shop:{$shopId}:compatibility";

        // Try Laravel cache
        $cached = Cache::get($cacheKey);
        if ($cached) {
            Log::debug('Cache HIT (Laravel)', ['cache_key' => $cacheKey]);
            return $cached;
        }

        // Try DB cache table
        $dbCache = DB::table('vehicle_compatibility_cache')
            ->where('part_sku', $sku)
            ->where('shop_id', $shopId)
            ->where('last_updated', '>', now()->subSeconds(self::CACHE_TTL))
            ->first();

        if ($dbCache) {
            $data = [
                'original_models' => json_decode($dbCache->original_models, true) ?? [],
                'original_ids' => json_decode($dbCache->original_ids, true) ?? [],
                'replacement_models' => json_decode($dbCache->replacement_models, true) ?? [],
                'replacement_ids' => json_decode($dbCache->replacement_ids, true) ?? [],
                'all_models' => json_decode($dbCache->all_models, true) ?? [],
                'models_count' => $dbCache->models_count,
            ];

            Cache::put($cacheKey, $data, self::CACHE_TTL);
            Log::info('Cache HIT (DB cache)', ['models_count' => $dbCache->models_count]);
            return $data;
        }

        Log::debug('Cache MISS', ['cache_key' => $cacheKey]);
        return null;
    }

    /**
     * Rebuild cache for product compatibility
     *
     * @param string $sku Part product SKU
     * @param int $shopId Shop ID
     * @return array Rebuilt cache data
     */
    public function rebuildCache(string $sku, int $shopId): array
    {
        Log::info('CompatibilityCacheService::rebuildCache CALLED', [
            'sku' => $sku,
            'shop_id' => $shopId,
        ]);

        // Get all compatibility for this product (SKU-first)
        $compatibility = DB::table('vehicle_compatibility')
            ->where('part_sku', $sku)
            ->where('shop_id', $shopId)
            ->get();

        $originalModels = [];
        $originalIds = [];
        $replacementModels = [];
        $replacementIds = [];

        foreach ($compatibility as $comp) {
            $vehicle = Product::find($comp->vehicle_product_id);
            if (!$vehicle) {
                continue;
            }

            if ($comp->compatibility_type === 'original') {
                $originalModels[] = $vehicle->name;
                $originalIds[] = $vehicle->id;
            } else {
                $replacementModels[] = $vehicle->name;
                $replacementIds[] = $vehicle->id;
            }
        }

        $allModels = array_merge($originalModels, $replacementModels);

        $cacheData = [
            'original_models' => $originalModels,
            'original_ids' => $originalIds,
            'replacement_models' => $replacementModels,
            'replacement_ids' => $replacementIds,
            'all_models' => $allModels,
            'models_count' => count($allModels),
        ];

        // Store in Laravel cache
        $cacheKey = "sku:{$sku}:shop:{$shopId}:compatibility";
        Cache::put($cacheKey, $cacheData, self::CACHE_TTL);

        // Store in DB cache table
        $product = Product::where('sku', $sku)->first();
        if ($product) {
            DB::table('vehicle_compatibility_cache')->updateOrInsert(
                ['part_product_id' => $product->id, 'shop_id' => $shopId],
                [
                    'part_sku' => $sku,
                    'cache_key' => $cacheKey,
                    'original_models' => json_encode($originalModels),
                    'original_ids' => json_encode($originalIds),
                    'replacement_models' => json_encode($replacementModels),
                    'replacement_ids' => json_encode($replacementIds),
                    'all_models' => json_encode($allModels),
                    'models_count' => count($allModels),
                    'last_updated' => now(),
                ]
            );
        }

        Log::info('Cache rebuilt', ['models_count' => count($allModels)]);
        return $cacheData;
    }

    /**
     * Invalidate compatibility cache for product and shop
     *
     * @param string $sku Part product SKU
     * @param int $shopId Shop ID
     * @return void
     */
    public function invalidateCache(string $sku, int $shopId): void
    {
        $cacheKey = "sku:{$sku}:shop:{$shopId}:compatibility";
        Cache::forget($cacheKey);

        DB::table('vehicle_compatibility_cache')
            ->where('part_sku', $sku)
            ->where('shop_id', $shopId)
            ->delete();

        Log::info('Cache invalidated', ['cache_key' => $cacheKey]);
    }
}
