<?php

namespace App\Services;

use App\Models\Product;
use App\Models\VehicleCompatibility;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * CompatibilityManager Service
 *
 * Centralized service for managing vehicle parts compatibility with SKU-FIRST architecture
 *
 * FEATURES:
 * - SKU-first lookup pattern (with ID fallback for backward compatibility)
 * - Compatibility CRUD operations (add, update, remove)
 * - Verification system (verify, bulk verify, get unverified)
 * - Cache layer for performance (SKU-based cache keys)
 * - Delegation to Sub-Services (CompatibilityVehicleService, CompatibilityBulkService)
 * - Per-shop compatibility tracking
 *
 * SKU-FIRST PRINCIPLES (from SKU_ARCHITECTURE_GUIDE.md):
 * - PRIMARY: Always lookup by SKU
 * - SECONDARY: Fallback to product_id if SKU not found
 * - REASON: SKU persists across re-imports, product_id may change
 * - CACHE KEYS: Based on SKU (not ID) to survive product re-import
 *
 * COMPLIANCE:
 * - Laravel 12.x Service Layer patterns (Context7 verified)
 * - DB transactions for multi-record operations
 * - Dependency injection for Sub-Services
 * - Type hints PHP 8.3
 * - CLAUDE.md: ~280 linii limit (compliant after split to Sub-Services)
 *
 * USAGE:
 * ```php
 * $manager = app(CompatibilityManager::class);
 *
 * // Get compatibility by SKU (recommended)
 * $compatibility = $manager->getCompatibilityBySku('PART-12345', $shopId);
 *
 * // Add compatibility
 * $compat = $manager->addCompatibility($product, [
 *     'vehicle_model_id' => 1,
 *     'compatibility_attribute_id' => 1,
 *     'notes' => 'Fits perfectly'
 * ]);
 *
 * // Verify compatibility
 * $manager->verifyCompatibility($compat, $user);
 *
 * // Delegate to Sub-Services
 * $manager->vehicleService->createVehicleModel([...]);
 * $manager->bulkService->copyCompatibilityFrom($target, $source);
 * ```
 *
 * RELATED:
 * - _DOCS/SKU_ARCHITECTURE_GUIDE.md - SKU-first patterns
 * - Plan_Projektu/ETAP_05a_Produkty.md - FAZA 3 (Services Layer)
 * - app/Services/CompatibilityVehicleService.php (Sub-Service)
 * - app/Services/CompatibilityBulkService.php (Sub-Service)
 *
 * @package App\Services
 * @version 2.0
 * @since ETAP_05a FAZA 3 (2025-10-17) - Extended with CRUD, verification, Sub-Services
 */
class CompatibilityManager
{
    /**
     * Cache TTL for compatibility data (15 minutes)
     */
    const CACHE_TTL = 900; // 15 minutes

    /**
     * Sub-Services (injected via constructor)
     */
    public CompatibilityVehicleService $vehicleService;
    public CompatibilityBulkService $bulkService;
    public CompatibilityCacheService $cacheService;

    /**
     * Constructor - Inject Sub-Services (Laravel 12.x DI pattern)
     */
    public function __construct(
        CompatibilityVehicleService $vehicleService,
        CompatibilityBulkService $bulkService,
        CompatibilityCacheService $cacheService
    ) {
        $this->vehicleService = $vehicleService;
        $this->bulkService = $bulkService;
        $this->cacheService = $cacheService;
    }

    /**
     * Get product compatibility by SKU (SKU-FIRST pattern)
     *
     * Falls back to ID lookup if SKU lookup fails for backward compatibility
     *
     * @param string $sku Part product SKU
     * @param int|null $shopId Optional shop filter
     * @param string|null $compatibilityType Optional filter: 'original' or 'replacement'
     * @return Collection VehicleCompatibility records
     */
    public function getCompatibilityBySku(
        string $sku,
        ?int $shopId = null,
        ?string $compatibilityType = null
    ): Collection {
        // PRIMARY: Try SKU lookup first
        $query = DB::table('vehicle_compatibility')->where('part_sku', $sku);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        if ($compatibilityType) {
            $query->where('compatibility_type', $compatibilityType);
        }

        $compatibility = $query->get();

        // FALLBACK: Try ID lookup if SKU returned no results
        if ($compatibility->isEmpty()) {
            $product = Product::where('sku', $sku)->first();

            if ($product) {
                $query = DB::table('vehicle_compatibility')->where('part_product_id', $product->id);

                if ($shopId) {
                    $query->where('shop_id', $shopId);
                }

                if ($compatibilityType) {
                    $query->where('compatibility_type', $compatibilityType);
                }

                $compatibility = $query->get();
            }
        }

        return collect($compatibility);
    }

    /**
     * Get cached compatibility by SKU (delegates to CompatibilityCacheService)
     *
     * @param string $sku Part product SKU
     * @param int $shopId Shop ID
     * @return array|null Cached compatibility data or null if not found/expired
     */
    public function getCachedCompatibilityBySku(string $sku, int $shopId): ?array
    {
        return $this->cacheService->getCachedCompatibility($sku, $shopId);
    }

    /**
     * Save compatibility with SKU backup columns populated (LEGACY METHOD)
     *
     * @deprecated Use addCompatibility() for new code (Eloquent-based with proper error handling)
     * @param Product $partProduct Part product
     * @param Product $vehicleProduct Vehicle product
     * @param string $compatibilityType 'original' or 'replacement'
     * @param int $shopId Shop ID
     * @param array $metadata Optional metadata
     * @return int Created/updated compatibility ID
     */
    public function saveCompatibility(
        Product $partProduct,
        Product $vehicleProduct,
        string $compatibilityType,
        int $shopId,
        array $metadata = []
    ): int {
        if (!in_array($compatibilityType, ['original', 'replacement'])) {
            throw new \InvalidArgumentException("Invalid compatibility type: {$compatibilityType}");
        }

        $existing = DB::table('vehicle_compatibility')
            ->where('part_product_id', $partProduct->id)
            ->where('vehicle_product_id', $vehicleProduct->id)
            ->where('compatibility_type', $compatibilityType)
            ->where('shop_id', $shopId)
            ->first();

        if ($existing) {
            DB::table('vehicle_compatibility')->where('id', $existing->id)->update([
                'part_sku' => $partProduct->sku,
                'vehicle_sku' => $vehicleProduct->sku,
                'notes' => $metadata['notes'] ?? null,
                'verified_at' => $metadata['verified_at'] ?? null,
                'verified_by' => $metadata['verified_by'] ?? null,
                'updated_at' => now(),
            ]);

            $this->invalidateCache($partProduct->sku, $shopId);
            return $existing->id;
        }

        $id = DB::table('vehicle_compatibility')->insertGetId([
            'part_product_id' => $partProduct->id,
            'part_sku' => $partProduct->sku,
            'vehicle_product_id' => $vehicleProduct->id,
            'vehicle_sku' => $vehicleProduct->sku,
            'compatibility_type' => $compatibilityType,
            'shop_id' => $shopId,
            'notes' => $metadata['notes'] ?? null,
            'verified_at' => $metadata['verified_at'] ?? null,
            'verified_by' => $metadata['verified_by'] ?? null,
            'created_by' => $metadata['created_by'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->invalidateCache($partProduct->sku, $shopId);
        return $id;
    }

    /**
     * Invalidate compatibility cache (delegates to CompatibilityCacheService)
     *
     * @param string $sku Part product SKU
     * @param int $shopId Shop ID
     * @return void
     */
    public function invalidateCache(string $sku, int $shopId): void
    {
        $this->cacheService->invalidateCache($sku, $shopId);
    }

    /**
     * Rebuild cache for product compatibility (delegates to CompatibilityCacheService)
     *
     * @param string $sku Part product SKU
     * @param int $shopId Shop ID
     * @return array Rebuilt cache data
     */
    public function rebuildCache(string $sku, int $shopId): array
    {
        return $this->cacheService->rebuildCache($sku, $shopId);
    }

    /**
     * Add vehicle compatibility to product
     *
     * @param Product $product Part product
     * @param array $data Compatibility data
     * @return VehicleCompatibility Created compatibility
     * @throws \Exception
     */
    public function addCompatibility(Product $product, array $data): VehicleCompatibility
    {
        try {
            $data['part_product_id'] = $product->id;
            $data['part_sku'] = $product->sku;

            if (isset($data['vehicle_model_id'])) {
                $vehicle = Product::find($data['vehicle_model_id']);
                $data['vehicle_product_id'] = $vehicle->id;
                $data['vehicle_sku'] = $vehicle->sku;
            }

            $data['created_by'] = auth()->id();
            $compatibility = VehicleCompatibility::create($data);

            if (isset($data['shop_id'])) {
                $this->invalidateCache($product->sku, $data['shop_id']);
            }

            return $compatibility;
        } catch (\Exception $e) {
            Log::error('addCompatibility FAILED', ['product_sku' => $product->sku, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Update compatibility entry
     *
     * @param VehicleCompatibility $compatibility Compatibility to update
     * @param array $data Updated data
     * @return VehicleCompatibility Updated compatibility
     * @throws \Exception
     */
    public function updateCompatibility(VehicleCompatibility $compatibility, array $data): VehicleCompatibility
    {
        try {
            $compatibility->update($data);

            if ($compatibility->shop_id) {
                $this->invalidateCache($compatibility->part_sku, $compatibility->shop_id);
            }

            return $compatibility->fresh();
        } catch (\Exception $e) {
            Log::error('updateCompatibility FAILED', ['id' => $compatibility->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Remove compatibility
     *
     * @param VehicleCompatibility $compatibility Compatibility to remove
     * @return bool Success status
     * @throws \Exception
     */
    public function removeCompatibility(VehicleCompatibility $compatibility): bool
    {
        try {
            $shopId = $compatibility->shop_id;
            $partSku = $compatibility->part_sku;
            $result = $compatibility->delete();

            if ($shopId) {
                $this->invalidateCache($partSku, $shopId);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('removeCompatibility FAILED', ['id' => $compatibility->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Verify compatibility (admin/expert only)
     *
     * @param VehicleCompatibility $compatibility Compatibility to verify
     * @param User $user User performing verification
     * @return VehicleCompatibility Verified compatibility
     */
    public function verifyCompatibility(VehicleCompatibility $compatibility, User $user): VehicleCompatibility
    {
        $compatibility->update(['verified_at' => now(), 'verified_by' => $user->id]);

        if ($compatibility->shop_id) {
            $this->invalidateCache($compatibility->part_sku, $compatibility->shop_id);
        }

        return $compatibility->fresh();
    }

    /**
     * Bulk verify compatibilities
     *
     * @param Collection $compatibilities Compatibilities to verify
     * @param User $user User performing verification
     * @return int Number of verified records
     */
    public function bulkVerify(Collection $compatibilities, User $user): int
    {
        $verified = 0;

        foreach ($compatibilities as $compatibility) {
            $this->verifyCompatibility($compatibility, $user);
            $verified++;
        }

        return $verified;
    }

    /**
     * Get unverified compatibilities (for review)
     *
     * @param int|null $sourceId Optional compatibility source filter
     * @return Collection Unverified compatibility records
     */
    public function getUnverified(?int $sourceId = null): Collection
    {
        $query = VehicleCompatibility::whereNull('verified_at');

        if ($sourceId) {
            $query->where('compatibility_source_id', $sourceId);
        }

        return $query->with(['partProduct', 'vehicleProduct'])->get();
    }
}
