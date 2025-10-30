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

    /*
    |--------------------------------------------------------------------------
    | BULK COMPATIBILITY OPERATIONS (ETAP_05d FAZA 2.1)
    |--------------------------------------------------------------------------
    | Excel-inspired workflow: horizontal/vertical drag operations
    | - 1 part × 26 vehicles = 26 compatibilities
    | - 50 parts × 1 vehicle = 50 compatibilities
    | - Deadlock resilient (attempts: 5)
    | - SKU-first architecture compliant
    */

    /**
     * Bulk add compatibilities (transaction-safe, deadlock resilient)
     *
     * Excel-inspired workflow: Drag operation (horizontal or vertical)
     * - Horizontal: 1 part × N vehicles
     * - Vertical: N parts × 1 vehicle
     *
     * @param array $partIds Array of product IDs (spare parts)
     * @param array $vehicleIds Array of vehicle_model IDs
     * @param string $attributeCode 'original' OR 'replacement'
     * @param int $sourceId compatibility_source_id (default: 3 = manual entry)
     * @return array ['created' => int, 'duplicates' => int, 'errors' => array]
     * @throws \Exception
     */
    public function bulkAddCompatibilities(
        array $partIds,
        array $vehicleIds,
        string $attributeCode,
        int $sourceId = 3
    ): array {
        $stats = ['created' => 0, 'duplicates' => 0, 'errors' => []];

        try {
            // Validation: Check max bulk size (safety limit)
            $totalCombinations = count($partIds) * count($vehicleIds);
            if ($totalCombinations > 500) {
                throw new \Exception("Bulk size exceeds maximum (500 combinations). Requested: {$totalCombinations}");
            }

            // SKU-first: Load products with SKU
            $products = Product::whereIn('id', $partIds)
                ->select('id', 'sku', 'name')
                ->get()
                ->keyBy('id');

            if ($products->isEmpty()) {
                throw new \Exception("No products found with provided IDs");
            }

            // SKU-first: Load vehicles with SKU
            $vehicles = \App\Models\VehicleModel::whereIn('id', $vehicleIds)
                ->select('id', 'sku', 'brand', 'model')
                ->get()
                ->keyBy('id');

            if ($vehicles->isEmpty()) {
                throw new \Exception("No vehicles found with provided IDs");
            }

            // Get compatibility_attribute_id from code
            $attribute = \App\Models\CompatibilityAttribute::where('code', $attributeCode)->first();

            if (!$attribute) {
                throw new \Exception("Invalid attribute code: {$attributeCode}");
            }

            // DB transaction with deadlock resilience (attempts: 5)
            DB::transaction(function () use (
                $products,
                $vehicles,
                $attribute,
                $sourceId,
                &$stats
            ) {
                foreach ($products as $product) {
                    foreach ($vehicles as $vehicle) {
                        // Check duplicate (exact match: product_id + vehicle_id + attribute)
                        $exists = VehicleCompatibility::where('product_id', $product->id)
                            ->where('vehicle_model_id', $vehicle->id)
                            ->where('compatibility_attribute_id', $attribute->id)
                            ->exists();

                        if ($exists) {
                            $stats['duplicates']++;
                            continue;
                        }

                        // Insert compatibility with SKU backup
                        VehicleCompatibility::create([
                            'product_id' => $product->id,
                            'part_sku' => $product->sku,
                            'vehicle_model_id' => $vehicle->id,
                            'vehicle_sku' => $vehicle->sku,
                            'compatibility_attribute_id' => $attribute->id,
                            'compatibility_source_id' => $sourceId,
                            'is_verified' => false,
                        ]);

                        $stats['created']++;
                    }
                }
            }, attempts: 5); // Deadlock resilience

            Log::info('bulkAddCompatibilities COMPLETED', [
                'parts_count' => count($partIds),
                'vehicles_count' => count($vehicleIds),
                'created' => $stats['created'],
                'duplicates' => $stats['duplicates'],
            ]);

            return $stats;

        } catch (\Exception $e) {
            Log::error('bulkAddCompatibilities FAILED', [
                'parts_count' => count($partIds),
                'vehicles_count' => count($vehicleIds),
                'error' => $e->getMessage(),
            ]);

            $stats['errors'][] = $e->getMessage();
            return $stats;
        }
    }

    /**
     * Detect duplicate compatibilities before bulk operation
     *
     * Preview what would happen if bulk operation executed
     * Identifies:
     * - Exact duplicates (same part + vehicle + attribute)
     * - Conflicts (same part + vehicle but DIFFERENT attribute)
     *
     * @param array $data Array of ['part_id', 'vehicle_id', 'attribute_code']
     * @return array ['duplicates' => array, 'conflicts' => array]
     */
    public function detectDuplicates(array $data): array
    {
        $result = ['duplicates' => [], 'conflicts' => []];

        try {
            // Extract unique combinations
            $combinations = collect($data)->unique(function ($item) {
                return "{$item['part_id']}_{$item['vehicle_id']}_{$item['attribute_code']}";
            });

            // Get attribute codes → IDs mapping
            $attributeCodes = $combinations->pluck('attribute_code')->unique();
            $attributes = \App\Models\CompatibilityAttribute::whereIn('code', $attributeCodes)
                ->get()
                ->keyBy('code');

            // Query existing compatibilities
            $partIds = $combinations->pluck('part_id')->unique()->toArray();
            $vehicleIds = $combinations->pluck('vehicle_id')->unique()->toArray();

            $existingCompatibilities = VehicleCompatibility::whereIn('product_id', $partIds)
                ->whereIn('vehicle_model_id', $vehicleIds)
                ->with(['product:id,sku,name', 'vehicleModel:id,sku,brand,model', 'compatibilityAttribute:id,code,name'])
                ->get()
                ->groupBy(function ($item) {
                    return "{$item->product_id}_{$item->vehicle_model_id}";
                });

            // Check each combination
            foreach ($combinations as $combo) {
                $key = "{$combo['part_id']}_{$combo['vehicle_id']}";
                $requestedAttributeId = $attributes[$combo['attribute_code']]->id ?? null;

                if (!$requestedAttributeId) {
                    continue;
                }

                $existing = $existingCompatibilities->get($key);

                if ($existing) {
                    foreach ($existing as $compatibility) {
                        if ($compatibility->compatibility_attribute_id === $requestedAttributeId) {
                            // Exact duplicate
                            $result['duplicates'][] = [
                                'part_id' => $combo['part_id'],
                                'part_sku' => $compatibility->product->sku,
                                'part_name' => $compatibility->product->name,
                                'vehicle_id' => $combo['vehicle_id'],
                                'vehicle_name' => $compatibility->vehicleModel->getFullName(),
                                'attribute' => $combo['attribute_code'],
                                'existing_id' => $compatibility->id,
                            ];
                        } else {
                            // Conflict (different attribute)
                            $result['conflicts'][] = [
                                'part_id' => $combo['part_id'],
                                'part_sku' => $compatibility->product->sku,
                                'part_name' => $compatibility->product->name,
                                'vehicle_id' => $combo['vehicle_id'],
                                'vehicle_name' => $compatibility->vehicleModel->getFullName(),
                                'requested_attribute' => $combo['attribute_code'],
                                'existing_attribute' => $compatibility->compatibilityAttribute->code,
                                'existing_id' => $compatibility->id,
                            ];
                        }
                    }
                }
            }

            Log::info('detectDuplicates COMPLETED', [
                'combinations_checked' => $combinations->count(),
                'duplicates_found' => count($result['duplicates']),
                'conflicts_found' => count($result['conflicts']),
            ]);

        } catch (\Exception $e) {
            Log::error('detectDuplicates FAILED', ['error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * Copy all compatibilities from one part to another
     *
     * Use case: Part A has 26 vehicle compatibilities → copy all to Part B
     * Excel equivalent: Copy range + paste to another row
     *
     * @param int $sourcePartId Source product ID
     * @param int $targetPartId Target product ID
     * @param array $options ['skip_duplicates' => bool, 'replace_existing' => bool]
     * @return array ['copied' => int, 'skipped' => int, 'errors' => array]
     * @throws \Exception
     */
    public function copyCompatibilities(
        int $sourcePartId,
        int $targetPartId,
        array $options = ['skip_duplicates' => true, 'replace_existing' => false]
    ): array {
        $stats = ['copied' => 0, 'skipped' => 0, 'errors' => []];

        try {
            // SKU-first: Load source and target products
            $sourceProduct = Product::select('id', 'sku', 'name')->find($sourcePartId);
            $targetProduct = Product::select('id', 'sku', 'name')->find($targetPartId);

            if (!$sourceProduct) {
                throw new \Exception("Source product not found: ID {$sourcePartId}");
            }

            if (!$targetProduct) {
                throw new \Exception("Target product not found: ID {$targetPartId}");
            }

            // Load source compatibilities with SKU backup
            $sourceCompatibilities = VehicleCompatibility::where('product_id', $sourcePartId)
                ->with(['vehicleModel:id,sku,brand,model'])
                ->get();

            if ($sourceCompatibilities->isEmpty()) {
                throw new \Exception("Source product has no compatibilities to copy");
            }

            // DB transaction with deadlock resilience
            DB::transaction(function () use (
                $sourceCompatibilities,
                $targetProduct,
                $options,
                &$stats
            ) {
                foreach ($sourceCompatibilities as $sourceCompat) {
                    // Check if target already has this compatibility
                    $existsQuery = VehicleCompatibility::where('product_id', $targetProduct->id)
                        ->where('vehicle_model_id', $sourceCompat->vehicle_model_id)
                        ->where('compatibility_attribute_id', $sourceCompat->compatibility_attribute_id);

                    $existing = $existsQuery->first();

                    if ($existing) {
                        if ($options['skip_duplicates'] && !$options['replace_existing']) {
                            $stats['skipped']++;
                            continue;
                        }

                        if ($options['replace_existing']) {
                            $existing->delete();
                        } else {
                            $stats['skipped']++;
                            continue;
                        }
                    }

                    // Copy compatibility to target
                    VehicleCompatibility::create([
                        'product_id' => $targetProduct->id,
                        'part_sku' => $targetProduct->sku,
                        'vehicle_model_id' => $sourceCompat->vehicle_model_id,
                        'vehicle_sku' => $sourceCompat->vehicle_sku,
                        'compatibility_attribute_id' => $sourceCompat->compatibility_attribute_id,
                        'compatibility_source_id' => $sourceCompat->compatibility_source_id,
                        'is_verified' => false, // Reset verification status
                        'notes' => $sourceCompat->notes,
                    ]);

                    $stats['copied']++;
                }
            }, attempts: 5); // Deadlock resilience

            Log::info('copyCompatibilities COMPLETED', [
                'source_sku' => $sourceProduct->sku,
                'target_sku' => $targetProduct->sku,
                'copied' => $stats['copied'],
                'skipped' => $stats['skipped'],
            ]);

            return $stats;

        } catch (\Exception $e) {
            Log::error('copyCompatibilities FAILED', [
                'source_id' => $sourcePartId,
                'target_id' => $targetPartId,
                'error' => $e->getMessage(),
            ]);

            $stats['errors'][] = $e->getMessage();
            return $stats;
        }
    }

    /**
     * Toggle compatibility type (Oryginał ↔ Zamiennik)
     *
     * Use case: User marked as "Oryginał" but should be "Zamiennik" → toggle
     * Excel equivalent: Change cell value from "O" to "Z"
     *
     * @param int $compatibilityId vehicle_compatibility.id
     * @param string $newAttributeCode 'original' OR 'replacement'
     * @return bool Success status
     * @throws \Exception
     */
    public function updateCompatibilityType(
        int $compatibilityId,
        string $newAttributeCode
    ): bool {
        try {
            // Get compatibility_attribute_id from code
            $attribute = \App\Models\CompatibilityAttribute::where('code', $newAttributeCode)->first();

            if (!$attribute) {
                throw new \Exception("Invalid attribute code: {$newAttributeCode}");
            }

            // Find compatibility
            $compatibility = VehicleCompatibility::find($compatibilityId);

            if (!$compatibility) {
                throw new \Exception("Compatibility not found: ID {$compatibilityId}");
            }

            // Update attribute type
            $compatibility->update([
                'compatibility_attribute_id' => $attribute->id,
                'updated_at' => now(),
            ]);

            // Invalidate cache if shop_id present
            if ($compatibility->shop_id) {
                $this->invalidateCache($compatibility->part_sku, $compatibility->shop_id);
            }

            Log::info('updateCompatibilityType COMPLETED', [
                'compatibility_id' => $compatibilityId,
                'new_attribute' => $newAttributeCode,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('updateCompatibilityType FAILED', [
                'compatibility_id' => $compatibilityId,
                'new_attribute' => $newAttributeCode,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
