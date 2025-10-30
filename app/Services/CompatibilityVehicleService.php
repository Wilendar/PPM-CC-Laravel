<?php

namespace App\Services;

use App\Models\VehicleModel;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CompatibilityVehicleService
 *
 * Sub-service for vehicle model management in compatibility system
 *
 * FEATURES:
 * - Vehicle model CRUD (create, find, search)
 * - Vehicle compatibility statistics
 * - Search & filter operations
 * - Vehicle SKU management
 *
 * COMPLIANCE:
 * - Laravel 12.x Service Layer patterns
 * - SKU-first architecture (vehicle_sku backup columns)
 * - Type hints PHP 8.3
 * - CLAUDE.md: ~150 linii limit (compliant)
 *
 * USAGE:
 * ```php
 * $vehicleService = app(CompatibilityVehicleService::class);
 *
 * // Create vehicle model
 * $vehicle = $vehicleService->createVehicleModel([
 *     'sku' => 'VEH-HONDA-CBR600-2013',
 *     'brand' => 'Honda',
 *     'model' => 'CBR600RR',
 *     'year' => 2013,
 *     'engine_type' => '4-stroke',
 *     'displacement' => '599cc'
 * ]);
 *
 * // Find vehicles
 * $vehicles = $vehicleService->findVehicles([
 *     'brand' => 'Honda',
 *     'model' => 'CBR',
 *     'year' => 2013
 * ]);
 * ```
 *
 * RELATED:
 * - app/Services/CompatibilityManager.php (parent service)
 * - app/Models/VehicleModel.php
 *
 * @package App\Services
 * @version 1.0
 * @since ETAP_05a FAZA 3 (2025-10-17)
 */
class CompatibilityVehicleService
{
    /**
     * Create vehicle model
     *
     * @param array $data Vehicle data
     * @return VehicleModel Created vehicle model
     * @throws \Exception
     */
    public function createVehicleModel(array $data): VehicleModel
    {
        try {
            Log::info('CompatibilityVehicleService::createVehicleModel CALLED', [
                'sku' => $data['sku'] ?? null,
                'brand' => $data['brand'] ?? null,
                'model' => $data['model'] ?? null,
            ]);

            $vehicle = VehicleModel::create([
                'sku' => $data['sku'],
                'brand' => $data['brand'],
                'model' => $data['model'],
                'year' => $data['year'] ?? null,
                'engine_type' => $data['engine_type'] ?? null,
                'displacement' => $data['displacement'] ?? null,
                'variant' => $data['variant'] ?? null,
            ]);

            Log::info('CompatibilityVehicleService::createVehicleModel COMPLETED', [
                'vehicle_id' => $vehicle->id,
                'vehicle_sku' => $vehicle->sku,
            ]);

            return $vehicle;

        } catch (\Exception $e) {
            Log::error('CompatibilityVehicleService::createVehicleModel FAILED', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Find vehicles by criteria
     *
     * @param array $criteria Search criteria (brand, model, year, etc.)
     * @return Collection Found vehicles
     */
    public function findVehicles(array $criteria): Collection
    {
        Log::debug('CompatibilityVehicleService::findVehicles CALLED', [
            'criteria' => $criteria,
        ]);

        $query = VehicleModel::query();

        if (isset($criteria['brand'])) {
            $query->where('brand', 'LIKE', '%' . $criteria['brand'] . '%');
        }

        if (isset($criteria['model'])) {
            $query->where('model', 'LIKE', '%' . $criteria['model'] . '%');
        }

        if (isset($criteria['year'])) {
            $query->where('year', $criteria['year']);
        }

        if (isset($criteria['engine_type'])) {
            $query->where('engine_type', $criteria['engine_type']);
        }

        if (isset($criteria['displacement'])) {
            $query->where('displacement', 'LIKE', '%' . $criteria['displacement'] . '%');
        }

        $results = $query->get();

        Log::debug('CompatibilityVehicleService::findVehicles COMPLETED', [
            'results_count' => $results->count(),
        ]);

        return $results;
    }

    /**
     * Get vehicle compatibility statistics
     *
     * @param VehicleModel $vehicle Vehicle to get stats for
     * @return array Statistics array
     */
    public function getVehicleStats(VehicleModel $vehicle): array
    {
        Log::debug('CompatibilityVehicleService::getVehicleStats CALLED', [
            'vehicle_id' => $vehicle->id,
            'vehicle_sku' => $vehicle->sku,
        ]);

        // Count compatible parts by compatibility type
        $originalParts = DB::table('vehicle_compatibility')
            ->where('vehicle_sku', $vehicle->sku)
            ->where('compatibility_type', 'original')
            ->count();

        $replacementParts = DB::table('vehicle_compatibility')
            ->where('vehicle_sku', $vehicle->sku)
            ->where('compatibility_type', 'replacement')
            ->count();

        $totalParts = $originalParts + $replacementParts;

        // Count verified vs unverified
        $verifiedParts = DB::table('vehicle_compatibility')
            ->where('vehicle_sku', $vehicle->sku)
            ->whereNotNull('verified_at')
            ->count();

        $stats = [
            'vehicle_id' => $vehicle->id,
            'vehicle_sku' => $vehicle->sku,
            'vehicle_name' => "{$vehicle->brand} {$vehicle->model} ({$vehicle->year})",
            'total_parts' => $totalParts,
            'original_parts' => $originalParts,
            'replacement_parts' => $replacementParts,
            'verified_parts' => $verifiedParts,
            'unverified_parts' => $totalParts - $verifiedParts,
            'verification_percentage' => $totalParts > 0 ? round(($verifiedParts / $totalParts) * 100, 2) : 0,
        ];

        Log::debug('CompatibilityVehicleService::getVehicleStats COMPLETED', [
            'total_parts' => $totalParts,
        ]);

        return $stats;
    }
}
