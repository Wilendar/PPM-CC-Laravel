<?php

namespace App\Http\Livewire\Admin\Parameters;

use App\Models\Location;
use App\Models\Warehouse;
use App\Services\Location\LocationLibraryService;
use App\Services\Location\LocationParser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * LocationManager - Zarzadzanie lokalizacjami magazynowymi
 *
 * ETAP 08: Location Library - hierarchiczny widok lokalizacji,
 * powiazanie z magazynami, filtrowanie, edycja, populacja z ProductStock.
 *
 * Funkcje:
 * - Wybor magazynu i hierarchiczny widok lokalizacji (Zone > Row > Shelf > Bin)
 * - Filtry: pattern type, occupancy, wyszukiwanie
 * - Lista produktow przypisanych do lokalizacji (paginacja)
 * - Edycja lokalizacji (code, description, notes, is_active)
 * - Populacja lokalizacji z product_stock
 * - Odswiezone product_count
 * - Statystyki magazynu (total, occupied, empty, zones, products)
 */
class LocationManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    /*
    |--------------------------------------------------------------------------
    | PUBLIC PROPERTIES
    |--------------------------------------------------------------------------
    */

    public ?int $selectedWarehouseId = null;
    public ?int $selectedLocationId = null;
    public string $search = '';
    public string $productSearch = '';
    public string $patternFilter = 'all';      // all/coded/dash/wall/named/gift/other
    public string $occupancyFilter = 'all';     // all/occupied/empty
    public bool $showEditModal = false;

    // Edit modal fields
    public string $editCode = '';
    public string $editDescription = '';
    public string $editNotes = '';
    public bool $editIsActive = true;

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        if (!auth()->user()?->can('stock.locations')) {
            abort(403);
        }

        // Select default warehouse (is_default=true), fallback to first active
        $defaultWarehouse = Warehouse::getDefault() ?? Warehouse::active()->ordered()->first();
        if ($defaultWarehouse) {
            $this->selectedWarehouseId = $defaultWarehouse->id;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED / HELPER METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get active warehouses for selector
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getWarehouses()
    {
        return Warehouse::active()->ordered()->get();
    }

    /**
     * Build hierarchical location tree for selected warehouse
     * with applied filters (pattern, occupancy, search).
     *
     * @return array
     */
    private function getLocationTree(): array
    {
        if ($this->selectedWarehouseId === null) {
            return [];
        }

        $tree = $this->getLocationService()->buildHierarchyForWarehouse($this->selectedWarehouseId);

        // Apply pattern filter
        if ($this->patternFilter !== 'all') {
            $tree = $this->filterTreeByPattern($tree, $this->patternFilter);
        }

        // Apply occupancy filter
        if ($this->occupancyFilter !== 'all') {
            $tree = $this->filterTreeByOccupancy($tree, $this->occupancyFilter);
        }

        // Apply search filter
        if ($this->search !== '') {
            $tree = $this->filterTreeBySearch($tree, $this->search);
        }

        return $tree;
    }

    /**
     * Get selected location with relationships
     *
     * @return \App\Models\Location|null
     */
    private function getSelectedLocationData()
    {
        if ($this->selectedLocationId === null) {
            return null;
        }

        return Location::with('warehouse', 'parent', 'children')
            ->find($this->selectedLocationId);
    }

    /**
     * Get paginated products for selected location
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection
     */
    private function getProducts()
    {
        if ($this->selectedLocationId === null) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10, 1, [
                'pageName' => 'productsPage',
            ]);
        }

        $paginator = $this->getLocationService()
            ->getProductsForLocation($this->selectedLocationId, 10);

        // Apply product search filter if provided
        if ($this->productSearch !== '') {
            $term = mb_strtolower($this->productSearch);

            $filtered = $paginator->getCollection()->filter(function ($stock) use ($term) {
                if (!$stock->product) {
                    return false;
                }
                $matchesSku = str_contains(mb_strtolower($stock->product->sku ?? ''), $term);
                $matchesName = str_contains(mb_strtolower($stock->product->name ?? ''), $term);
                return $matchesSku || $matchesName;
            });

            return new \Illuminate\Pagination\LengthAwarePaginator(
                $filtered->values(),
                $filtered->count(),
                10,
                $paginator->currentPage(),
                ['pageName' => 'productsPage']
            );
        }

        return $paginator;
    }

    /**
     * Get aggregated stats for selected warehouse
     *
     * @return array
     */
    private function getStats(): array
    {
        if ($this->selectedWarehouseId === null) {
            return [
                'total' => 0,
                'occupied' => 0,
                'empty' => 0,
                'zones_count' => 0,
                'total_products' => 0,
            ];
        }

        return $this->getLocationService()->getStats($this->selectedWarehouseId);
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Select warehouse and reset dependent state
     */
    public function selectWarehouse(int $warehouseId): void
    {
        $this->selectedWarehouseId = $warehouseId;
        $this->selectedLocationId = null;
        $this->search = '';
        $this->patternFilter = 'all';
        $this->occupancyFilter = 'all';
        $this->resetPage();
    }

    /**
     * Select a location to show its details and products
     */
    public function selectLocation(int $locationId): void
    {
        $this->selectedLocationId = $locationId;
        $this->productSearch = '';
        $this->resetPage(pageName: 'productsPage');
    }

    /**
     * Populate locations from ProductStock records for selected warehouse
     */
    public function populateLocations(): void
    {
        if ($this->selectedWarehouseId === null) {
            return;
        }

        try {
            $count = $this->getLocationService()
                ->populateFromProductStock($this->selectedWarehouseId);

            Cache::forget("location_stats_{$this->selectedWarehouseId}");

            $this->dispatch('flash-message',
                type: 'success',
                message: "Skanowanie zakonczone: znaleziono/zaktualizowano {$count} lokalizacji."
            );

            Log::info('[LOCATION] Populate from ProductStock', [
                'warehouse_id' => $this->selectedWarehouseId,
                'locations_count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error('[LOCATION] Populate failed', [
                'warehouse_id' => $this->selectedWarehouseId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message',
                type: 'error',
                message: 'Blad skanowania lokalizacji: ' . $e->getMessage()
            );
        }
    }

    /**
     * Refresh product counts for all locations in selected warehouse
     */
    public function refreshCounts(): void
    {
        if ($this->selectedWarehouseId === null) {
            return;
        }

        try {
            $this->getLocationService()
                ->refreshProductCounts($this->selectedWarehouseId);

            $this->dispatch('flash-message',
                type: 'success',
                message: 'Liczniki produktow zostaly odswiezone.'
            );

            Log::info('[LOCATION] Product counts refreshed', [
                'warehouse_id' => $this->selectedWarehouseId,
            ]);
        } catch (\Exception $e) {
            Log::error('[LOCATION] Refresh counts failed', [
                'warehouse_id' => $this->selectedWarehouseId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message',
                type: 'error',
                message: 'Blad odswiezania licznikow: ' . $e->getMessage()
            );
        }
    }

    /**
     * Open edit modal for a location
     */
    public function editLocation(int $locationId): void
    {
        $location = Location::find($locationId);

        if (!$location) {
            return;
        }

        $this->selectedLocationId = $locationId;
        $this->editCode = $location->code;
        $this->editDescription = $location->description ?? '';
        $this->editNotes = $location->notes ?? '';
        $this->editIsActive = $location->is_active;
        $this->showEditModal = true;
    }

    /**
     * Save edited location
     */
    public function saveLocation(): void
    {
        $this->validate([
            'editCode' => 'required|string|max:100',
            'editDescription' => 'nullable|string|max:500',
            'editNotes' => 'nullable|string|max:1000',
            'editIsActive' => 'boolean',
        ]);

        $location = Location::find($this->selectedLocationId);

        if (!$location) {
            $this->showEditModal = false;
            return;
        }

        // Check code uniqueness within warehouse
        $codeExists = Location::where('warehouse_id', $location->warehouse_id)
            ->where('code', $this->editCode)
            ->where('id', '!=', $location->id)
            ->exists();

        if ($codeExists) {
            $this->addError('editCode', 'Taki kod lokalizacji juz istnieje w tym magazynie.');
            return;
        }

        $location->update([
            'code' => $this->editCode,
            'description' => $this->editDescription ?: null,
            'notes' => $this->editNotes ?: null,
            'is_active' => $this->editIsActive,
        ]);

        $this->showEditModal = false;

        $this->dispatch('flash-message',
            type: 'success',
            message: "Lokalizacja '{$this->editCode}' zostala zaktualizowana."
        );
    }

    /**
     * Delete (soft) a location
     */
    public function deleteLocation(int $locationId): void
    {
        $location = Location::find($locationId);

        if (!$location) {
            return;
        }

        $code = $location->code;
        $location->delete();

        // Reset selection if deleted location was selected
        if ($this->selectedLocationId === $locationId) {
            $this->selectedLocationId = null;
        }

        Cache::forget("location_stats_{$this->selectedWarehouseId}");

        $this->dispatch('flash-message',
            type: 'success',
            message: "Lokalizacja '{$code}' zostala usunieta."
        );
    }

    /**
     * Close edit modal
     */
    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->resetValidation();
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATED HOOKS
    |--------------------------------------------------------------------------
    */

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedProductSearch(): void
    {
        $this->resetPage(pageName: 'productsPage');
    }

    public function updatedPatternFilter(): void
    {
        $this->resetPage();
    }

    public function updatedOccupancyFilter(): void
    {
        $this->resetPage();
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        return view('livewire.admin.parameters.location-manager', [
            'warehouses' => $this->getWarehouses(),
            'locationTree' => $this->getLocationTree(),
            'selectedLocationData' => $this->getSelectedLocationData(),
            'products' => $this->getProducts(),
            'stats' => $this->getStats(),
        ])->layout('layouts.admin', [
            'title' => 'Lokalizacje magazynowe - Admin PPM',
            'breadcrumb' => 'Lokalizacje magazynowe',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PRIVATE HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Resolve LocationLibraryService from container (no constructor DI in Livewire 3.x)
     */
    private function getLocationService(): LocationLibraryService
    {
        return app(LocationLibraryService::class);
    }

    /**
     * Filter hierarchy tree by pattern_type
     */
    private function filterTreeByPattern(array $tree, string $pattern): array
    {
        return array_values(array_filter(
            array_map(function (array $zone) use ($pattern) {
                if (isset($zone['pattern_type']) && $zone['pattern_type'] === $pattern) {
                    return $zone;
                }

                // Filter children recursively
                $zone['children'] = $this->filterChildrenByPattern($zone['children'] ?? [], $pattern);

                if (!empty($zone['children']) || (isset($zone['pattern_type']) && $zone['pattern_type'] === $pattern)) {
                    return $zone;
                }

                return null;
            }, $tree)
        ));
    }

    /**
     * Recursively filter children nodes by pattern_type
     */
    private function filterChildrenByPattern(array $children, string $pattern): array
    {
        return array_values(array_filter(
            array_map(function (array $child) use ($pattern) {
                // Leaf node with id
                if (isset($child['id'])) {
                    return (isset($child['pattern_type']) && $child['pattern_type'] === $pattern)
                        ? $child
                        : null;
                }

                // Intermediate node - filter its children
                $child['children'] = $this->filterChildrenByPattern($child['children'] ?? [], $pattern);

                return !empty($child['children']) ? $child : null;
            }, $children)
        ));
    }

    /**
     * Filter hierarchy tree by occupancy (occupied/empty)
     */
    private function filterTreeByOccupancy(array $tree, string $occupancy): array
    {
        $showOccupied = ($occupancy === 'occupied');

        return array_values(array_filter(
            array_map(function (array $zone) use ($showOccupied) {
                $zone['children'] = $this->filterChildrenByOccupancy($zone['children'] ?? [], $showOccupied);

                // Keep zone if it has matching children or matching product_count
                $zoneMatch = $showOccupied
                    ? ($zone['product_count'] ?? 0) > 0
                    : ($zone['product_count'] ?? 0) === 0;

                if (!empty($zone['children']) || $zoneMatch) {
                    return $zone;
                }

                return null;
            }, $tree)
        ));
    }

    /**
     * Recursively filter children by occupancy
     */
    private function filterChildrenByOccupancy(array $children, bool $showOccupied): array
    {
        return array_values(array_filter(
            array_map(function (array $child) use ($showOccupied) {
                if (isset($child['id'])) {
                    $occupied = ($child['product_count'] ?? 0) > 0;
                    return ($occupied === $showOccupied) ? $child : null;
                }

                $child['children'] = $this->filterChildrenByOccupancy($child['children'] ?? [], $showOccupied);
                return !empty($child['children']) ? $child : null;
            }, $children)
        ));
    }

    /**
     * Filter hierarchy tree by search term (matches code)
     */
    private function filterTreeBySearch(array $tree, string $searchTerm): array
    {
        $term = mb_strtolower($searchTerm);

        return array_values(array_filter(
            array_map(function (array $zone) use ($term) {
                // Check if zone label matches
                $zoneMatch = str_contains(mb_strtolower($zone['label'] ?? ''), $term)
                    || str_contains(mb_strtolower($zone['zone'] ?? ''), $term);

                $zone['children'] = $this->filterChildrenBySearch($zone['children'] ?? [], $term);

                if ($zoneMatch || !empty($zone['children'])) {
                    return $zone;
                }

                return null;
            }, $tree)
        ));
    }

    /**
     * Recursively filter children by search term
     */
    private function filterChildrenBySearch(array $children, string $term): array
    {
        return array_values(array_filter(
            array_map(function (array $child) use ($term) {
                if (isset($child['id'])) {
                    return str_contains(mb_strtolower($child['code'] ?? ''), $term)
                        ? $child
                        : null;
                }

                // Check label/row_code match
                $labelMatch = str_contains(mb_strtolower($child['label'] ?? ''), $term)
                    || str_contains(mb_strtolower($child['row_code'] ?? ''), $term);

                $child['children'] = $this->filterChildrenBySearch($child['children'] ?? [], $term);

                if ($labelMatch || !empty($child['children'])) {
                    return $child;
                }

                return null;
            }, $children)
        ));
    }
}
