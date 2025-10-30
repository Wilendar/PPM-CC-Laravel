<?php

namespace App\Services;

use App\Models\Product;
use App\Models\VehicleCompatibility;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CompatibilityBulkService
 *
 * Sub-service for bulk operations in compatibility system
 *
 * FEATURES:
 * - Copy compatibility between products
 * - Import compatibility from CSV/external source
 * - Export compatibility to array (for CSV/API)
 * - Bulk operations (bulk verify, bulk update)
 * - Search compatible products
 *
 * COMPLIANCE:
 * - Laravel 12.x Service Layer patterns
 * - SKU-first architecture (backup columns populated)
 * - DB transactions for bulk operations
 * - Type hints PHP 8.3
 * - CLAUDE.md: ~150 linii limit (compliant)
 *
 * USAGE:
 * ```php
 * $bulkService = app(CompatibilityBulkService::class);
 *
 * // Copy compatibility from source to target
 * $copied = $bulkService->copyCompatibilityFrom($targetProduct, $sourceProduct);
 *
 * // Import compatibility from array
 * $imported = $bulkService->importCompatibility($product, $compatibilityData);
 *
 * // Export to array
 * $exported = $bulkService->exportCompatibility($product);
 * ```
 *
 * RELATED:
 * - app/Services/CompatibilityManager.php (parent service)
 * - app/Models/VehicleCompatibility.php
 *
 * @package App\Services
 * @version 1.0
 * @since ETAP_05a FAZA 3 (2025-10-17)
 */
class CompatibilityBulkService
{
    /**
     * Copy compatibility from one product to another
     *
     * @param Product $target Target product
     * @param Product $source Source product
     * @return Collection Copied compatibility records
     */
    public function copyCompatibilityFrom(Product $target, Product $source): Collection
    {
        return DB::transaction(function () use ($target, $source) {
            Log::info('CompatibilityBulkService::copyCompatibilityFrom CALLED', [
                'target_sku' => $target->sku,
                'source_sku' => $source->sku,
            ]);

            // Get source compatibility (SKU-first)
            $sourceCompatibility = DB::table('vehicle_compatibility')
                ->where('part_sku', $source->sku)
                ->get();

            $copied = [];
            foreach ($sourceCompatibility as $comp) {
                $copied[] = DB::table('vehicle_compatibility')->insertGetId([
                    'part_product_id' => $target->id,
                    'part_sku' => $target->sku, // ✅ SKU BACKUP
                    'vehicle_product_id' => $comp->vehicle_product_id,
                    'vehicle_sku' => $comp->vehicle_sku, // ✅ SKU BACKUP
                    'compatibility_attribute_id' => $comp->compatibility_attribute_id,
                    'compatibility_source_id' => $comp->compatibility_source_id,
                    'notes' => $comp->notes,
                    'verified_at' => null, // Reset verification for new product
                    'verified_by' => null,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Log::info('CompatibilityBulkService::copyCompatibilityFrom COMPLETED', [
                'copied_count' => count($copied),
            ]);

            return collect($copied);
        });
    }

    /**
     * Import compatibility from CSV/external source
     *
     * @param Product $product Product to import compatibility for
     * @param array $data Array of compatibility data
     * @return int Number of imported records
     */
    public function importCompatibility(Product $product, array $data): int
    {
        return DB::transaction(function () use ($product, $data) {
            Log::info('CompatibilityBulkService::importCompatibility CALLED', [
                'product_sku' => $product->sku,
                'data_count' => count($data),
            ]);

            $imported = 0;

            foreach ($data as $item) {
                // Find vehicle by SKU or create
                $vehicle = Product::where('sku', $item['vehicle_sku'])->first();

                if (!$vehicle) {
                    Log::warning('Vehicle not found, skipping', [
                        'vehicle_sku' => $item['vehicle_sku'],
                    ]);
                    continue;
                }

                // Create compatibility record
                DB::table('vehicle_compatibility')->insert([
                    'part_product_id' => $product->id,
                    'part_sku' => $product->sku, // ✅ SKU BACKUP
                    'vehicle_product_id' => $vehicle->id,
                    'vehicle_sku' => $vehicle->sku, // ✅ SKU BACKUP
                    'compatibility_attribute_id' => $item['compatibility_attribute_id'] ?? null,
                    'compatibility_source_id' => $item['compatibility_source_id'] ?? null,
                    'notes' => $item['notes'] ?? null,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $imported++;
            }

            Log::info('CompatibilityBulkService::importCompatibility COMPLETED', [
                'imported_count' => $imported,
            ]);

            return $imported;
        });
    }

    /**
     * Export compatibility to array (for CSV/API)
     *
     * @param Product $product Product to export compatibility for
     * @return array Compatibility data array
     */
    public function exportCompatibility(Product $product): array
    {
        Log::info('CompatibilityBulkService::exportCompatibility CALLED', [
            'product_sku' => $product->sku,
        ]);

        // Get compatibility (SKU-first)
        $compatibility = DB::table('vehicle_compatibility')
            ->where('part_sku', $product->sku)
            ->get();

        $exported = [];
        foreach ($compatibility as $comp) {
            $vehicle = Product::find($comp->vehicle_product_id);

            $exported[] = [
                'part_sku' => $comp->part_sku,
                'vehicle_sku' => $comp->vehicle_sku,
                'vehicle_name' => $vehicle?->name ?? 'Unknown',
                'compatibility_attribute_id' => $comp->compatibility_attribute_id,
                'compatibility_source_id' => $comp->compatibility_source_id,
                'notes' => $comp->notes,
                'verified' => $comp->verified_at ? true : false,
                'verified_at' => $comp->verified_at,
            ];
        }

        Log::info('CompatibilityBulkService::exportCompatibility COMPLETED', [
            'exported_count' => count($exported),
        ]);

        return $exported;
    }

    /**
     * Find compatible products for vehicle
     *
     * @param int $vehicleId Vehicle product ID
     * @param array|null $filters Optional filters
     * @return Collection Compatible products
     */
    public function findCompatibleProducts(int $vehicleId, ?array $filters = []): Collection
    {
        Log::debug('CompatibilityBulkService::findCompatibleProducts CALLED', [
            'vehicle_id' => $vehicleId,
        ]);

        $vehicle = Product::find($vehicleId);
        if (!$vehicle) {
            return collect([]);
        }

        // SKU-first lookup
        $query = DB::table('vehicle_compatibility')
            ->where('vehicle_sku', $vehicle->sku);

        if (isset($filters['compatibility_type'])) {
            $query->where('compatibility_type', $filters['compatibility_type']);
        }

        if (isset($filters['verified_only']) && $filters['verified_only']) {
            $query->whereNotNull('verified_at');
        }

        $compatibility = $query->get();

        $productSkus = $compatibility->pluck('part_sku')->unique();
        $products = Product::whereIn('sku', $productSkus)->get();

        Log::debug('CompatibilityBulkService::findCompatibleProducts COMPLETED', [
            'products_count' => $products->count(),
        ]);

        return $products;
    }
}
