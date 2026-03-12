<?php

namespace App\Services\Location;

use App\Models\Location;
use App\Models\ProductStock;
use App\Models\Warehouse;
use App\Services\Location\LocationParser;
use App\Services\Location\LocationParseResult;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class LocationLibraryService
{
    public function __construct(
        private LocationParser $parser
    ) {}

    /**
     * Scan product_stock table, parse location codes, and upsert into locations table.
     *
     * @param int|null $warehouseId Limit to specific warehouse, or all active warehouses if null
     * @return int Number of created/updated location entries
     */
    public function populateFromProductStock(?int $warehouseId = null): int
    {
        $count = 0;

        $warehouseIds = $warehouseId
            ? [$warehouseId]
            : Warehouse::active()->pluck('id')->toArray();

        foreach ($warehouseIds as $wId) {
            $rawLocations = ProductStock::where('warehouse_id', $wId)
                ->whereNotNull('location')
                ->where('location', '!=', '')
                ->distinct()
                ->pluck('location');

            foreach ($rawLocations as $rawLocation) {
                $codes = $this->parser->splitMultipleLocations($rawLocation);

                foreach ($codes as $code) {
                    $this->upsertLocation($wId, $code);
                    $count++;
                }
            }
        }

        // Invalidate stats cache for affected warehouses
        foreach ($warehouseIds as $wId) {
            Cache::forget("location_stats_{$wId}");
        }

        return $count;
    }

    /**
     * Build a hierarchical tree (Zone > Row > Shelf > Bin) for a warehouse.
     *
     * @param int $warehouseId
     * @param array $zoneConfig Zone naming config (prefix, separator, auto_uppercase)
     * @return array Nested hierarchy array
     */
    public function buildHierarchyForWarehouse(int $warehouseId, array $zoneConfig = []): array
    {
        $locations = Location::byWarehouse($warehouseId)
            ->active()
            ->orderBy('zone')
            ->orderBy('row_code')
            ->orderBy('shelf')
            ->orderBy('bin')
            ->get();

        $zones = [];

        foreach ($locations as $location) {
            $zone = $location->zone ?? '__ungrouped__';

            if (!isset($zones[$zone])) {
                $zones[$zone] = [
                    'zone' => $zone,
                    'label' => $this->buildZoneLabel($zone, $location->pattern_type, $zoneConfig),
                    'product_count' => 0,
                    'pattern_type' => $location->pattern_type,
                    'children' => [],
                    '_rows' => [],
                ];
            }

            $zones[$zone]['product_count'] += $location->product_count;

            // Named/other locations - add as direct zone-level bins (clickable leaves)
            if (in_array($location->pattern_type, ['named', 'other']) || $location->row_code === null) {
                $zones[$zone]['children'][] = [
                    'id' => $location->id,
                    'code' => $location->code,
                    'product_count' => $location->product_count,
                    'pattern_type' => $location->pattern_type,
                ];
                continue;
            }

            $rowCode = $location->row_code;

            if (!isset($zones[$zone]['_rows'][$rowCode])) {
                $zones[$zone]['_rows'][$rowCode] = [
                    'row_code' => $rowCode,
                    'label' => "Rzad {$rowCode}",
                    'product_count' => 0,
                    'children' => [],
                    '_shelves' => [],
                ];
            }

            $zones[$zone]['_rows'][$rowCode]['product_count'] += $location->product_count;

            if ($location->shelf === null) {
                $zones[$zone]['_rows'][$rowCode]['children'][] = [
                    'id' => $location->id,
                    'code' => $location->code,
                    'product_count' => $location->product_count,
                    'pattern_type' => $location->pattern_type,
                ];
                continue;
            }

            $shelfKey = $location->shelf;

            if (!isset($zones[$zone]['_rows'][$rowCode]['_shelves'][$shelfKey])) {
                $zones[$zone]['_rows'][$rowCode]['_shelves'][$shelfKey] = [
                    'shelf' => $shelfKey,
                    'label' => 'Polka ' . str_pad((string) $shelfKey, 2, '0', STR_PAD_LEFT),
                    'product_count' => 0,
                    'children' => [],
                ];
            }

            $zones[$zone]['_rows'][$rowCode]['_shelves'][$shelfKey]['product_count'] += $location->product_count;

            $zones[$zone]['_rows'][$rowCode]['_shelves'][$shelfKey]['children'][] = [
                'id' => $location->id,
                'code' => $location->code,
                'product_count' => $location->product_count,
                'pattern_type' => $location->pattern_type,
            ];
        }

        // Flatten temporary _rows/_shelves into children arrays
        return $this->flattenHierarchy($zones);
    }

    /**
     * Refresh product_count on locations from ProductStock records.
     *
     * @param int|null $warehouseId Limit to specific warehouse, or all if null
     * @return void
     */
    public function refreshProductCounts(?int $warehouseId = null): void
    {
        $query = Location::query();

        if ($warehouseId !== null) {
            $query->byWarehouse($warehouseId);
        }

        $query->chunkById(200, function (Collection $locations) {
            foreach ($locations as $location) {
                $location->recalculateProductCount();
            }
        });

        // Invalidate stats cache
        if ($warehouseId !== null) {
            Cache::forget("location_stats_{$warehouseId}");
        } else {
            $warehouseIds = Warehouse::active()->pluck('id');
            foreach ($warehouseIds as $wId) {
                Cache::forget("location_stats_{$wId}");
            }
        }
    }

    /**
     * Search locations within a warehouse by code, description, or zone.
     *
     * @param int    $warehouseId
     * @param string $query
     * @return Collection
     */
    public function search(int $warehouseId, string $query): Collection
    {
        $term = '%' . $query . '%';

        return Location::byWarehouse($warehouseId)
            ->where(function ($q) use ($term) {
                $q->where('code', 'LIKE', $term)
                  ->orWhere('description', 'LIKE', $term)
                  ->orWhere('zone', 'LIKE', $term);
            })
            ->orderByDesc('product_count')
            ->limit(50)
            ->get();
    }

    /**
     * Get paginated products stored at a given location.
     *
     * @param int $locationId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getProductsForLocation(int $locationId, int $perPage = 15): LengthAwarePaginator
    {
        $location = Location::findOrFail($locationId);
        $code = $location->code;

        return ProductStock::where('warehouse_id', $location->warehouse_id)
            ->where(function ($q) use ($code) {
                $q->where('location', $code)
                  ->orWhere('location', 'LIKE', $code . ',%')
                  ->orWhere('location', 'LIKE', '%,' . $code)
                  ->orWhere('location', 'LIKE', '%,' . $code . ',%');
            })
            ->with(['product', 'product.manufacturerRelation'])
            ->orderByDesc('quantity')
            ->paginate($perPage);
    }

    /**
     * Get aggregated statistics for a warehouse's locations.
     * Cached for 5 minutes.
     *
     * @param int $warehouseId
     * @return array{total: int, occupied: int, empty: int, zones_count: int, total_products: int}
     */
    public function getStats(int $warehouseId): array
    {
        return Cache::remember(
            "location_stats_{$warehouseId}",
            now()->addMinutes(5),
            function () use ($warehouseId) {
                $base = Location::byWarehouse($warehouseId)->active();

                return [
                    'total' => (clone $base)->count(),
                    'occupied' => (clone $base)->where('product_count', '>', 0)->count(),
                    'empty' => (clone $base)->where('product_count', 0)->count(),
                    'zones_count' => (clone $base)->distinct()->count('zone'),
                    'total_products' => (clone $base)->sum('product_count'),
                ];
            }
        );
    }

    /**
     * Find or create a Location for a given warehouse and raw code.
     *
     * @param int    $warehouseId
     * @param string $code
     * @return Location
     */
    public function upsertLocation(int $warehouseId, string $code): Location
    {
        $normalizedCode = $this->parser->normalize($code);

        $existing = Location::where('warehouse_id', $warehouseId)
            ->where('normalized_code', $normalizedCode)
            ->first();

        if ($existing) {
            return $existing;
        }

        $result = $this->parser->parse($code);

        return Location::create([
            'warehouse_id' => $warehouseId,
            'code' => trim($code),
            'normalized_code' => $result->normalizedCode,
            'pattern_type' => $result->patternType,
            'zone' => $result->zone,
            'row_code' => $result->rowCode,
            'shelf' => $result->shelf,
            'bin' => $result->bin,
            'depth' => $result->depth,
            'path' => $result->path,
            'product_count' => 0,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    // ------------------------------------------------------------------
    //  Private helpers
    // ------------------------------------------------------------------

    /**
     * Build a human-readable label for a zone.
     *
     * @param string $zone Raw zone identifier
     * @param string|null $patternType Location pattern type
     * @param array $zoneConfig Zone naming config (prefix, separator, auto_uppercase)
     */
    private function buildZoneLabel(string $zone, ?string $patternType, array $zoneConfig = []): string
    {
        if ($zone === '__ungrouped__') {
            return 'Niesklasyfikowane';
        }

        if (in_array($patternType, ['named', 'other'])) {
            return $zone;
        }

        $prefix = $zoneConfig['prefix'] ?? 'Strefa';
        $separator = $zoneConfig['separator'] ?? ' ';
        $autoUppercase = $zoneConfig['auto_uppercase'] ?? true;

        if (mb_strlen($zone) <= 2 && ctype_alpha($zone)) {
            $zoneLabel = $autoUppercase ? mb_strtoupper($zone) : $zone;
            return $prefix . $separator . $zoneLabel;
        }

        return $zone;
    }

    /**
     * Convert temporary _rows/_shelves maps into clean children arrays.
     */
    private function flattenHierarchy(array $zones): array
    {
        $result = [];

        foreach ($zones as $zoneData) {
            $rows = [];

            foreach ($zoneData['_rows'] as $rowData) {
                $shelves = [];

                foreach ($rowData['_shelves'] as $shelfData) {
                    $shelves[] = $shelfData;
                }

                $directBins = $rowData['children'];
                unset($rowData['_shelves']);

                // FIX BUG 1.1: Separate leaf bins from shelf containers
                $rowData['children'] = $shelves;
                $rowData['bins'] = $directBins;

                $rows[] = $rowData;
            }

            // Zone-level: separate direct bins (named/other) from row containers
            $zoneBins = $zoneData['children'];
            unset($zoneData['_rows']);
            $zoneData['children'] = $rows;
            $zoneData['bins'] = $zoneBins;

            $result[] = $zoneData;
        }

        return $result;
    }
}
