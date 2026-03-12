<?php

namespace App\Http\Livewire\Admin\Parameters;

use App\Http\Livewire\Admin\Parameters\Traits\LocationFiltersTrait;
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
    use LocationFiltersTrait;

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

    // Create location modal
    public bool $showCreateModal = false;
    public string $createCode = '';
    public string $createDescription = '';
    public string $createNotes = '';

    // Zone management modal
    public bool $showZoneModal = false;
    public ?string $editingZone = null;
    public string $zoneName = '';
    public string $zoneDescription = '';

    // Zone config modal
    public bool $showZoneConfigModal = false;
    public string $zonePrefix = 'Strefa';
    public string $zoneSeparator = ' ';
    public bool $zoneAutoUppercase = true;

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
        return Warehouse::active()->ordered()->withCount('locations')->get();
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

        $zoneConfig = $this->getZoneNamingConfig();
        $tree = $this->getLocationService()->buildHierarchyForWarehouse($this->selectedWarehouseId, $zoneConfig);

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

            // Auto-refresh product counts after populating
            $this->getLocationService()->refreshProductCounts($this->selectedWarehouseId);

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
    | CREATE LOCATION
    |--------------------------------------------------------------------------
    */

    /**
     * Open create location modal
     */
    public function openCreateModal(): void
    {
        $this->createCode = '';
        $this->createDescription = '';
        $this->createNotes = '';
        $this->showCreateModal = true;
    }

    /**
     * Create new location
     */
    public function createLocation(): void
    {
        $this->validate([
            'createCode' => 'required|string|max:100',
            'createDescription' => 'nullable|string|max:500',
            'createNotes' => 'nullable|string|max:1000',
        ]);

        if ($this->selectedWarehouseId === null) {
            return;
        }

        // Check uniqueness within warehouse
        $exists = Location::where('warehouse_id', $this->selectedWarehouseId)
            ->where('code', $this->createCode)
            ->exists();

        if ($exists) {
            $this->addError('createCode', 'Taki kod lokalizacji juz istnieje w tym magazynie.');
            return;
        }

        try {
            $this->getLocationService()->upsertLocation(
                $this->selectedWarehouseId,
                $this->createCode
            );

            // Update description/notes after creation
            $location = Location::where('warehouse_id', $this->selectedWarehouseId)
                ->where('code', trim($this->createCode))
                ->first();

            if ($location && ($this->createDescription || $this->createNotes)) {
                $location->update([
                    'description' => $this->createDescription ?: null,
                    'notes' => $this->createNotes ?: null,
                ]);
            }

            Cache::forget("location_stats_{$this->selectedWarehouseId}");
            $this->showCreateModal = false;

            $this->dispatch('flash-message',
                type: 'success',
                message: "Lokalizacja '{$this->createCode}' zostala utworzona."
            );
        } catch (\Exception $e) {
            $this->addError('createCode', 'Blad tworzenia lokalizacji: ' . $e->getMessage());
        }
    }

    /**
     * Close create modal
     */
    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetValidation();
    }

    /*
    |--------------------------------------------------------------------------
    | ZONE MANAGEMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Open zone management modal
     */
    public function openZoneModal(?string $zone = null): void
    {
        $this->editingZone = $zone;
        $this->zoneName = $zone ?? '';
        $this->zoneDescription = '';
        $this->showZoneModal = true;
    }

    /**
     * Save zone (rename)
     */
    public function saveZone(): void
    {
        $this->validate([
            'zoneName' => 'required|string|max:50',
        ]);

        if ($this->selectedWarehouseId === null) {
            return;
        }

        if ($this->editingZone !== null && $this->editingZone !== $this->zoneName) {
            // Rename zone: update all locations in this zone
            Location::where('warehouse_id', $this->selectedWarehouseId)
                ->where('zone', $this->editingZone)
                ->update(['zone' => $this->zoneName]);

            Cache::forget("location_stats_{$this->selectedWarehouseId}");

            $this->dispatch('flash-message',
                type: 'success',
                message: "Strefa '{$this->editingZone}' zmieniona na '{$this->zoneName}'."
            );
        }

        $this->showZoneModal = false;
    }

    /**
     * Delete zone and all its locations
     */
    public function deleteZone(string $zone): void
    {
        if ($this->selectedWarehouseId === null) {
            return;
        }

        $count = Location::where('warehouse_id', $this->selectedWarehouseId)
            ->where('zone', $zone)
            ->count();

        Location::where('warehouse_id', $this->selectedWarehouseId)
            ->where('zone', $zone)
            ->delete();

        // Reset selection if deleted zone contained selected location
        if ($this->selectedLocationId !== null) {
            $location = Location::find($this->selectedLocationId);
            if (!$location) {
                $this->selectedLocationId = null;
            }
        }

        Cache::forget("location_stats_{$this->selectedWarehouseId}");

        $this->dispatch('flash-message',
            type: 'success',
            message: "Strefa '{$zone}' i {$count} lokalizacji zostaly usuniete."
        );
    }

    /**
     * Close zone modal
     */
    public function closeZoneModal(): void
    {
        $this->showZoneModal = false;
        $this->resetValidation();
    }

    /*
    |--------------------------------------------------------------------------
    | ZONE NAMING CONFIGURATION
    |--------------------------------------------------------------------------
    */

    /**
     * Open zone naming configuration modal
     */
    public function openZoneConfigModal(): void
    {
        $config = $this->getZoneNamingConfig();
        $this->zonePrefix = $config['prefix'];
        $this->zoneSeparator = $config['separator'];
        $this->zoneAutoUppercase = $config['auto_uppercase'];
        $this->showZoneConfigModal = true;
    }

    /**
     * Save zone naming configuration to warehouse settings
     */
    public function saveZoneConfig(): void
    {
        $this->validate([
            'zonePrefix' => 'nullable|string|max:50',
            'zoneSeparator' => 'nullable|string|max:5',
            'zoneAutoUppercase' => 'boolean',
        ]);

        if ($this->selectedWarehouseId === null) {
            return;
        }

        $warehouse = Warehouse::find($this->selectedWarehouseId);
        if (!$warehouse) {
            return;
        }

        $settings = $warehouse->erp_mapping ?? [];
        $settings['zone_naming'] = [
            'prefix' => $this->zonePrefix,
            'separator' => $this->zoneSeparator,
            'auto_uppercase' => $this->zoneAutoUppercase,
        ];
        $warehouse->update(['erp_mapping' => $settings]);

        $this->showZoneConfigModal = false;

        Cache::forget("location_stats_{$this->selectedWarehouseId}");

        $this->dispatch('flash-message',
            type: 'success',
            message: 'Konfiguracja nazewnictwa stref zostala zapisana.'
        );
    }

    /**
     * Close zone config modal
     */
    public function closeZoneConfigModal(): void
    {
        $this->showZoneConfigModal = false;
    }

    /**
     * Get zone naming configuration for current warehouse
     */
    private function getZoneNamingConfig(): array
    {
        $defaults = [
            'prefix' => 'Strefa',
            'separator' => ' ',
            'auto_uppercase' => true,
        ];

        if ($this->selectedWarehouseId === null) {
            return $defaults;
        }

        $warehouse = Warehouse::find($this->selectedWarehouseId);
        if (!$warehouse) {
            return $defaults;
        }

        $settings = $warehouse->erp_mapping ?? [];
        return array_merge($defaults, $settings['zone_naming'] ?? []);
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

}
