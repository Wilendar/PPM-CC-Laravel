<?php

namespace App\Services\ERP;

use App\Models\Warehouse;
use App\Models\PriceGroup;
use App\Models\ERPConnection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * ERPMappingService - ERP → PPM Mapping Logic
 *
 * ETAP 09.2: Warehouse & Price Group Mapping
 *
 * Provides:
 * - Stock aggregation from multiple ERP warehouses to single PPM warehouse
 * - Price mapping from ERP price levels to PPM price groups
 * - Lookup tables for bidirectional mapping
 * - Validation of mapping configurations
 *
 * Mapping Architecture:
 * - Warehouses: Many-to-One (multiple ERP warehouses → one PPM warehouse)
 * - Price Groups: One-to-One (one ERP price level → one PPM price group)
 */
class ERPMappingService
{
    /**
     * Stock aggregation modes
     */
    public const AGGREGATION_SUM = 'sum';
    public const AGGREGATION_MAX = 'max';
    public const AGGREGATION_FIRST = 'first';

    /**
     * Get aggregated stock for a PPM warehouse from multiple ERP warehouses
     *
     * @param int $ppmWarehouseId PPM warehouse ID
     * @param array $erpStockData Stock data from ERP keyed by ERP warehouse ID
     *                            Format: [erp_warehouse_id => quantity, ...]
     * @param string $erpType ERP system type
     * @return float Aggregated stock quantity
     */
    public function getAggregatedStock(int $ppmWarehouseId, array $erpStockData, string $erpType = 'subiekt_gt'): float
    {
        $warehouse = Warehouse::find($ppmWarehouseId);

        if (!$warehouse || !$warehouse->hasErpMapping($erpType)) {
            return 0.0;
        }

        $erpWarehouseIds = $warehouse->getErpWarehouseIds($erpType);
        $aggregationMode = $warehouse->getStockAggregationMode($erpType);

        if (empty($erpWarehouseIds)) {
            return 0.0;
        }

        // Filter ERP stock data to only mapped warehouses
        $relevantStock = array_filter(
            $erpStockData,
            fn($key) => in_array((int) $key, $erpWarehouseIds, true),
            ARRAY_FILTER_USE_KEY
        );

        if (empty($relevantStock)) {
            return 0.0;
        }

        return $this->aggregateValues(array_values($relevantStock), $aggregationMode);
    }

    /**
     * Aggregate values based on mode
     *
     * @param array $values Numeric values to aggregate
     * @param string $mode Aggregation mode (sum, max, first)
     * @return float Aggregated value
     */
    protected function aggregateValues(array $values, string $mode): float
    {
        if (empty($values)) {
            return 0.0;
        }

        switch ($mode) {
            case self::AGGREGATION_SUM:
                return (float) array_sum($values);

            case self::AGGREGATION_MAX:
                return (float) max($values);

            case self::AGGREGATION_FIRST:
                return (float) reset($values);

            default:
                return (float) array_sum($values);
        }
    }

    /**
     * Get mapped price for a PPM price group from ERP price data
     *
     * @param int $ppmPriceGroupId PPM price group ID
     * @param array $erpPriceData Price data from ERP keyed by price level ID
     *                            Format: [price_level_id => ['net' => X, 'gross' => Y], ...]
     * @param string $erpType ERP system type
     * @return array|null Price data ['net' => float, 'gross' => float] or null
     */
    public function getMappedPrice(int $ppmPriceGroupId, array $erpPriceData, string $erpType = 'subiekt_gt'): ?array
    {
        $priceGroup = PriceGroup::find($ppmPriceGroupId);

        if (!$priceGroup || !$priceGroup->hasErpMapping($erpType)) {
            return null;
        }

        $erpPriceLevelId = $priceGroup->getErpPriceLevelId($erpType);

        if ($erpPriceLevelId === null) {
            return null;
        }

        return $erpPriceData[$erpPriceLevelId] ?? null;
    }

    /**
     * Build lookup: ERP warehouse ID → PPM warehouse IDs
     *
     * Used for reverse mapping when receiving data from ERP
     *
     * @param string $erpType ERP system type
     * @return array [erp_warehouse_id => [ppm_warehouse_id, ...], ...]
     */
    public function buildErpToPpmWarehouseLookup(string $erpType = 'subiekt_gt'): array
    {
        $lookup = [];

        $warehouses = Warehouse::active()->get();

        foreach ($warehouses as $warehouse) {
            $erpWarehouseIds = $warehouse->getErpWarehouseIds($erpType);

            foreach ($erpWarehouseIds as $erpWarehouseId) {
                if (!isset($lookup[$erpWarehouseId])) {
                    $lookup[$erpWarehouseId] = [];
                }
                $lookup[$erpWarehouseId][] = $warehouse->id;
            }
        }

        return $lookup;
    }

    /**
     * Build lookup: ERP price level ID → PPM price group ID
     *
     * @param string $erpType ERP system type
     * @return array [erp_price_level_id => ppm_price_group_id, ...]
     */
    public function buildErpToPpmPriceGroupLookup(string $erpType = 'subiekt_gt'): array
    {
        $lookup = [];

        $priceGroups = PriceGroup::active()->get();

        foreach ($priceGroups as $priceGroup) {
            $erpPriceLevelId = $priceGroup->getErpPriceLevelId($erpType);

            if ($erpPriceLevelId !== null) {
                $lookup[$erpPriceLevelId] = $priceGroup->id;
            }
        }

        return $lookup;
    }

    /**
     * Build lookup: PPM warehouse ID → ERP warehouse IDs
     *
     * @param string $erpType ERP system type
     * @return array [ppm_warehouse_id => [erp_warehouse_id, ...], ...]
     */
    public function buildPpmToErpWarehouseLookup(string $erpType = 'subiekt_gt'): array
    {
        $lookup = [];

        $warehouses = Warehouse::active()->get();

        foreach ($warehouses as $warehouse) {
            $erpWarehouseIds = $warehouse->getErpWarehouseIds($erpType);

            if (!empty($erpWarehouseIds)) {
                $lookup[$warehouse->id] = $erpWarehouseIds;
            }
        }

        return $lookup;
    }

    /**
     * Build lookup: PPM price group ID → ERP price level ID
     *
     * @param string $erpType ERP system type
     * @return array [ppm_price_group_id => erp_price_level_id, ...]
     */
    public function buildPpmToErpPriceGroupLookup(string $erpType = 'subiekt_gt'): array
    {
        $lookup = [];

        $priceGroups = PriceGroup::active()->get();

        foreach ($priceGroups as $priceGroup) {
            $erpPriceLevelId = $priceGroup->getErpPriceLevelId($erpType);

            if ($erpPriceLevelId !== null) {
                $lookup[$priceGroup->id] = $erpPriceLevelId;
            }
        }

        return $lookup;
    }

    /**
     * Validate mapping configuration
     *
     * @param array $warehouseMappings [erp_warehouse_id => ppm_warehouse_id, ...]
     * @param array $priceGroupMappings [erp_price_level_id => ppm_price_group_id, ...]
     * @param array $availableErpWarehouses Available ERP warehouses
     * @param array $availableErpPriceLevels Available ERP price levels
     * @return array Validation result ['valid' => bool, 'errors' => [], 'warnings' => []]
     */
    public function validateMappings(
        array $warehouseMappings,
        array $priceGroupMappings,
        array $availableErpWarehouses = [],
        array $availableErpPriceLevels = []
    ): array {
        $errors = [];
        $warnings = [];

        // Validate warehouse mappings
        $mappedPpmWarehouses = [];
        foreach ($warehouseMappings as $erpId => $ppmId) {
            if ($ppmId === null || $ppmId === '') {
                continue; // Unmapped is OK
            }

            // Check if PPM warehouse exists
            if (!Warehouse::find($ppmId)) {
                $errors[] = "Magazyn PPM o ID {$ppmId} nie istnieje";
                continue;
            }

            // Track for many-to-one summary
            if (!isset($mappedPpmWarehouses[$ppmId])) {
                $mappedPpmWarehouses[$ppmId] = [];
            }
            $mappedPpmWarehouses[$ppmId][] = $erpId;
        }

        // Warn about many-to-one mappings (informational)
        foreach ($mappedPpmWarehouses as $ppmId => $erpIds) {
            if (count($erpIds) > 1) {
                $warehouse = Warehouse::find($ppmId);
                $warnings[] = "Magazyn '{$warehouse->name}' ma {$count = count($erpIds)} zrodel ERP - stany beda sumowane";
            }
        }

        // Validate price group mappings
        $usedPriceLevels = [];
        foreach ($priceGroupMappings as $erpId => $ppmId) {
            if ($ppmId === null || $ppmId === '') {
                continue; // Unmapped is OK
            }

            // Check if PPM price group exists
            if (!PriceGroup::find($ppmId)) {
                $errors[] = "Grupa cenowa PPM o ID {$ppmId} nie istnieje";
                continue;
            }

            // Check for duplicate ERP price level mappings (one ERP level should map to one PPM group)
            if (in_array($erpId, $usedPriceLevels, true)) {
                $warnings[] = "Poziom cenowy ERP {$erpId} jest zmapowany do wielu grup PPM";
            }
            $usedPriceLevels[] = $erpId;
        }

        // Check for unmapped critical items
        $ppmWarehouses = Warehouse::active()->get();
        $ppmPriceGroups = PriceGroup::active()->get();

        $mappedPpmWarehouseIds = array_values(array_filter(array_unique($warehouseMappings)));
        $mappedPpmPriceGroupIds = array_values(array_filter(array_unique($priceGroupMappings)));

        foreach ($ppmWarehouses as $warehouse) {
            if ($warehouse->is_default && !in_array($warehouse->id, $mappedPpmWarehouseIds)) {
                $warnings[] = "Domyslny magazyn '{$warehouse->name}' nie ma mapowania ERP";
            }
        }

        foreach ($ppmPriceGroups as $priceGroup) {
            if ($priceGroup->is_default && !in_array($priceGroup->id, $mappedPpmPriceGroupIds)) {
                $warnings[] = "Domyslna grupa cenowa '{$priceGroup->name}' nie ma mapowania ERP";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Save warehouse mappings from ERP→PPM format
     *
     * @param array $erpToPpmMappings [erp_warehouse_id => ppm_warehouse_id, ...]
     * @param int $connectionId ERP connection ID
     * @param string $erpType ERP system type
     * @param array $aggregationModes Optional [ppm_warehouse_id => 'sum'|'max'|'first', ...]
     * @return array Result with saved count
     */
    public function saveWarehouseMappings(
        array $erpToPpmMappings,
        int $connectionId,
        string $erpType = 'subiekt_gt',
        array $aggregationModes = []
    ): array {
        // First, clear existing mappings for this connection
        $warehouses = Warehouse::active()->get();
        foreach ($warehouses as $warehouse) {
            $mapping = $warehouse->getErpMapping($erpType);
            if ($mapping && ($mapping['connection_id'] ?? null) === $connectionId) {
                $warehouse->clearErpMappings($erpType);
            }
        }

        // Group ERP warehouses by their target PPM warehouse
        $ppmToErpGroups = [];
        foreach ($erpToPpmMappings as $erpId => $ppmId) {
            if ($ppmId === null || $ppmId === '' || $ppmId === 0) {
                continue;
            }

            $ppmId = (int) $ppmId;
            if (!isset($ppmToErpGroups[$ppmId])) {
                $ppmToErpGroups[$ppmId] = [];
            }
            $ppmToErpGroups[$ppmId][] = (int) $erpId;
        }

        // Save mappings to each PPM warehouse
        $savedCount = 0;
        foreach ($ppmToErpGroups as $ppmId => $erpIds) {
            $warehouse = Warehouse::find($ppmId);
            if (!$warehouse) {
                continue;
            }

            $aggregationMode = $aggregationModes[$ppmId] ?? self::AGGREGATION_SUM;
            $warehouse->setErpWarehouseMappings($erpType, $erpIds, $connectionId, $aggregationMode);
            $savedCount++;
        }

        Log::info('ERPMappingService::saveWarehouseMappings', [
            'connection_id' => $connectionId,
            'erp_type' => $erpType,
            'saved_count' => $savedCount,
            'ppm_to_erp_groups' => $ppmToErpGroups,
        ]);

        return [
            'saved_count' => $savedCount,
            'ppm_to_erp_groups' => $ppmToErpGroups,
        ];
    }

    /**
     * Save price group mappings from ERP→PPM format
     *
     * @param array $erpToPpmMappings [erp_price_level_id => ppm_price_group_id, ...]
     * @param int $connectionId ERP connection ID
     * @param string $erpType ERP system type
     * @param array $erpPriceLevelNames Optional [erp_price_level_id => 'Name', ...] for display
     * @return array Result with saved count
     */
    public function savePriceGroupMappings(
        array $erpToPpmMappings,
        int $connectionId,
        string $erpType = 'subiekt_gt',
        array $erpPriceLevelNames = []
    ): array {
        // First, clear existing mappings for this connection
        $priceGroups = PriceGroup::active()->get();
        foreach ($priceGroups as $priceGroup) {
            $mapping = $priceGroup->getErpMapping($erpType);
            if ($mapping && ($mapping['connection_id'] ?? null) === $connectionId) {
                $priceGroup->clearErpMapping($erpType);
            }
        }

        // Save new mappings
        $savedCount = 0;
        foreach ($erpToPpmMappings as $erpPriceLevelId => $ppmPriceGroupId) {
            if ($ppmPriceGroupId === null || $ppmPriceGroupId === '' || $ppmPriceGroupId === 0) {
                continue;
            }

            $priceGroup = PriceGroup::find((int) $ppmPriceGroupId);
            if (!$priceGroup) {
                continue;
            }

            $priceLevelName = $erpPriceLevelNames[$erpPriceLevelId] ?? null;
            $priceGroup->setErpPriceLevelMapping(
                $erpType,
                (int) $erpPriceLevelId,
                $connectionId,
                $priceLevelName
            );
            $savedCount++;
        }

        Log::info('ERPMappingService::savePriceGroupMappings', [
            'connection_id' => $connectionId,
            'erp_type' => $erpType,
            'saved_count' => $savedCount,
        ]);

        return [
            'saved_count' => $savedCount,
        ];
    }

    /**
     * Get mapping summary for display in UI
     *
     * @param int $connectionId ERP connection ID
     * @param string $erpType ERP system type
     * @param array $availableErpWarehouses ERP warehouses for name lookup
     * @param array $availableErpPriceLevels ERP price levels for name lookup
     * @return array Summary data
     */
    public function getMappingSummary(
        int $connectionId,
        string $erpType = 'subiekt_gt',
        array $availableErpWarehouses = [],
        array $availableErpPriceLevels = []
    ): array {
        $warehouseSummary = [];
        $priceGroupSummary = [];

        // Warehouse summaries
        $warehouses = Warehouse::active()->ordered()->get();
        foreach ($warehouses as $warehouse) {
            $mapping = $warehouse->getErpMapping($erpType);
            if ($mapping && ($mapping['connection_id'] ?? null) === $connectionId) {
                $warehouseSummary[] = $warehouse->getErpMappingSummary($erpType, $availableErpWarehouses);
                $warehouseSummary[count($warehouseSummary) - 1]['ppm_warehouse_id'] = $warehouse->id;
                $warehouseSummary[count($warehouseSummary) - 1]['ppm_warehouse_name'] = $warehouse->name;
            }
        }

        // Price group summaries
        $priceGroups = PriceGroup::active()->ordered()->get();
        foreach ($priceGroups as $priceGroup) {
            $mapping = $priceGroup->getErpMapping($erpType);
            if ($mapping && ($mapping['connection_id'] ?? null) === $connectionId) {
                $priceGroupSummary[] = $priceGroup->getErpMappingSummary($erpType, $availableErpPriceLevels);
                $priceGroupSummary[count($priceGroupSummary) - 1]['ppm_price_group_id'] = $priceGroup->id;
                $priceGroupSummary[count($priceGroupSummary) - 1]['ppm_price_group_name'] = $priceGroup->name;
            }
        }

        return [
            'warehouses' => $warehouseSummary,
            'price_groups' => $priceGroupSummary,
            'warehouse_count' => count($warehouseSummary),
            'price_group_count' => count($priceGroupSummary),
        ];
    }

    /**
     * Get all PPM warehouses with their ERP mapping status
     *
     * @param string $erpType ERP system type
     * @param array $availableErpWarehouses ERP warehouses for name lookup
     * @return Collection
     */
    public function getPpmWarehousesWithMappingStatus(
        string $erpType = 'subiekt_gt',
        array $availableErpWarehouses = []
    ): Collection {
        return Warehouse::active()
            ->ordered()
            ->get()
            ->map(function ($warehouse) use ($erpType, $availableErpWarehouses) {
                return [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'code' => $warehouse->code,
                    'is_default' => $warehouse->is_default,
                    'has_erp_mapping' => $warehouse->hasErpMapping($erpType),
                    'erp_warehouse_ids' => $warehouse->getErpWarehouseIds($erpType),
                    'aggregation_mode' => $warehouse->getStockAggregationMode($erpType),
                    'summary' => $warehouse->getErpMappingSummary($erpType, $availableErpWarehouses),
                ];
            });
    }

    /**
     * Get all PPM price groups with their ERP mapping status
     *
     * @param string $erpType ERP system type
     * @param array $availableErpPriceLevels ERP price levels for name lookup
     * @return Collection
     */
    public function getPpmPriceGroupsWithMappingStatus(
        string $erpType = 'subiekt_gt',
        array $availableErpPriceLevels = []
    ): Collection {
        return PriceGroup::active()
            ->ordered()
            ->get()
            ->map(function ($priceGroup) use ($erpType, $availableErpPriceLevels) {
                return [
                    'id' => $priceGroup->id,
                    'name' => $priceGroup->name,
                    'code' => $priceGroup->code,
                    'is_default' => $priceGroup->is_default,
                    'has_erp_mapping' => $priceGroup->hasErpMapping($erpType),
                    'erp_price_level_id' => $priceGroup->getErpPriceLevelId($erpType),
                    'summary' => $priceGroup->getErpMappingSummary($erpType, $availableErpPriceLevels),
                ];
            });
    }
}
