<?php

namespace App\Http\Livewire\Products\Management\Traits;

use App\Models\VehicleCompatibility;
use App\Models\Product;
use App\Models\CompatibilityAttribute;
use App\Services\Compatibility\ShopFilteringService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProductFormCompatibility Trait
 *
 * ETAP_05d FAZA 4 - Vehicle compatibility management in ProductForm
 *
 * Features:
 * - Load/save vehicle compatibility from ProductForm
 * - Tile-based vehicle selection (Original/Zamiennik)
 * - Per-shop context support
 * - Integration with existing ProductForm shop tabs
 *
 * @package App\Http\Livewire\Products\Management\Traits
 * @version 1.0
 * @since 2025-12-08
 */
trait ProductFormCompatibility
{
    /*
    |--------------------------------------------------------------------------
    | COMPATIBILITY PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Selected vehicle IDs for Original type
     */
    public array $compatibilityOriginal = [];

    /**
     * Selected vehicle IDs for Zamiennik type
     */
    public array $compatibilityZamiennik = [];

    /**
     * Currently active selection mode
     * 'original' | 'zamiennik'
     */
    public string $compatibilityMode = 'original';

    /**
     * Available vehicles grouped by brand
     * Format: ['brand_name' => [vehicles...]]
     */
    public array $vehiclesByBrand = [];

    /**
     * Search filter for vehicles
     */
    public string $vehicleSearch = '';

    /**
     * Brand filter for vehicles
     */
    public string $vehicleBrandFilter = '';

    /**
     * Collapsed brand sections (for UI state)
     */
    public array $collapsedBrands = [];

    /**
     * Track compatibility changes for save
     */
    public array $compatibilityPendingChanges = [];

    /**
     * Default compatibility snapshot (for shop comparison)
     */
    public array $defaultCompatibilityOriginal = [];
    public array $defaultCompatibilityZamiennik = [];

    /**
     * Ghost suggestions from AI SmartSuggestionEngine
     * Format: [['vehicle_id' => int, 'vehicle_name' => string, 'vehicle_manufacturer' => string,
     *           'vehicle_sku' => string, 'score' => float, 'reason' => string, 'breakdown' => array]]
     */
    public array $ghostSuggestions = [];

    /**
     * Map of vehicle_id => score for AI suggested vehicles
     * Used by blade to highlight existing tiles
     */
    public array $suggestedVehicleScores = [];

    /**
     * Whether to show dismissed suggestions
     */
    public bool $showDismissedSuggestions = false;

    /**
     * IDs of dismissed vehicles (for quick lookup)
     */
    public array $dismissedSuggestionIds = [];

    /**
     * Archived vehicles - exist in compatibility but not in active tiles
     * Format: [vehicleId => ['id'=>X, 'name'=>'...', 'manufacturer'=>'...', 'sku'=>'...', 'source'=>'db|metadata']]
     */
    public array $archivedVehicles = [];

    /**
     * Phantom compatibility records (vehicle doesn't exist in PPM)
     * Format: ['phantom_DB_ID' => ['name' => ..., 'manufacturer' => ..., 'type' => 'original|zamiennik', 'metadata' => ...]]
     */
    public array $phantomCompatibilities = [];

    /*
    |--------------------------------------------------------------------------
    | COMPATIBILITY LOADING
    |--------------------------------------------------------------------------
    */

    /**
     * Load compatibility data for current product
     * Called during mount() and when switching shop context
     */
    public function loadCompatibilityData(): void
    {
        if (!$this->product || !$this->product->id) {
            $this->resetCompatibilityState();
            return;
        }

        // Only load for czesc-zamienna type products
        if ($this->product->productType?->slug !== 'czesc-zamienna') {
            return;
        }

        try {
            // Get shop context from ProductFormShopTabs
            $shopId = $this->selectedShop ?? null;

            // Load existing compatibility
            $query = VehicleCompatibility::where('product_id', $this->product->id)
                ->with(['vehicleProduct', 'compatibilityAttribute']);

            if ($shopId) {
                $query->where('shop_id', $shopId);
            }

            $compatibilities = $query->get();

            // Separate phantom records (vehicle_model_id is null)
            $phantomRecords = $compatibilities->filter(fn($c) => $c->vehicle_model_id === null);
            $regularRecords = $compatibilities->filter(fn($c) => $c->vehicle_model_id !== null);

            // Build phantom compatibilities array
            $this->phantomCompatibilities = [];
            foreach ($phantomRecords as $phantom) {
                $meta = $phantom->metadata ?? [];
                $type = $phantom->compatibilityAttribute?->code === 'original' ? 'original' : 'zamiennik';
                $this->phantomCompatibilities['phantom_' . $phantom->id] = [
                    'id' => $phantom->id,
                    'name' => $meta['ps_vehicle_name'] ?? "Phantom #{$phantom->id}",
                    'manufacturer' => $meta['ps_vehicle_manufacturer'] ?? 'Nieznany',
                    'type' => $type,
                    'metadata' => $meta,
                ];
            }

            // Populate selection arrays (only regular records with vehicle_model_id)
            $this->compatibilityOriginal = $regularRecords
                ->filter(fn($c) => $c->compatibilityAttribute?->code === 'original')
                ->pluck('vehicle_model_id')
                ->unique()
                ->values()
                ->toArray();

            $this->compatibilityZamiennik = $regularRecords
                ->filter(fn($c) => $c->compatibilityAttribute?->code === 'replacement')
                ->pluck('vehicle_model_id')
                ->unique()
                ->values()
                ->toArray();

            // Store default snapshot (when no shop context)
            if (!$shopId) {
                $this->defaultCompatibilityOriginal = $this->compatibilityOriginal;
                $this->defaultCompatibilityZamiennik = $this->compatibilityZamiennik;
            }

            // Vehicles loaded lazily - only when user opens Compatibility tab
            // See loadVehiclesIfNeeded()

            $this->compatibilityPendingChanges = [];

            // If phantom records exist and vehicles already loaded, re-detect archived
            if (!empty($this->phantomCompatibilities) && !empty($this->vehiclesByBrand)) {
                $this->detectArchivedVehicles();
            }

            Log::debug('ProductFormCompatibility::loadCompatibilityData', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'original_count' => count($this->compatibilityOriginal),
                'zamiennik_count' => count($this->compatibilityZamiennik),
                'phantom_count' => count($this->phantomCompatibilities),
                'total_records' => $compatibilities->count(),
                'archived_count' => count($this->archivedVehicles),
            ]);

        } catch (\Exception $e) {
            Log::error('ProductFormCompatibility::loadCompatibilityData error', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Load available vehicles (pojazd type products) grouped by brand
     */
    protected function loadAvailableVehicles(?int $shopId = null): void
    {
        try {
            // Get vehicles (products with type='pojazd')
            $query = Product::where('product_type_id', function($q) {
                $q->select('id')
                    ->from('product_types')
                    ->where('slug', 'pojazd')
                    ->limit(1);
            })->where('is_active', true);

            // Apply shop filtering if service available
            if ($shopId && class_exists(ShopFilteringService::class)) {
                $filterService = app(ShopFilteringService::class);
                $allowedBrands = $filterService->getAllowedBrands($shopId);

                if ($allowedBrands !== null && !empty($allowedBrands)) {
                    $query->whereIn('manufacturer', $allowedBrands);
                }
            }

            $vehicles = $query->orderBy('manufacturer')
                ->orderBy('name')
                ->get(['id', 'name', 'manufacturer', 'sku']);

            // Group by brand (manufacturer)
            $this->vehiclesByBrand = $vehicles
                ->groupBy('manufacturer')
                ->map(fn($group) => $group->toArray())
                ->toArray();

        } catch (\Exception $e) {
            Log::error('ProductFormCompatibility::loadAvailableVehicles error', [
                'error' => $e->getMessage(),
            ]);
            $this->vehiclesByBrand = [];
        }
    }

    /**
     * Load vehicles lazily - called when user opens Compatibility tab.
     * Prevents heavy vehicle query from running on every mount().
     */
    public function loadVehiclesIfNeeded(): void
    {
        Log::debug('loadVehiclesIfNeeded called', [
            'product_id' => $this->product?->id,
            'vehiclesByBrand_empty' => empty($this->vehiclesByBrand),
            'phantom_count' => count($this->phantomCompatibilities),
            'archived_count' => count($this->archivedVehicles),
        ]);

        if (!empty($this->vehiclesByBrand)) {
            // Vehicles already loaded but phantom may need re-detection
            if (!empty($this->phantomCompatibilities) && empty($this->archivedVehicles)) {
                $this->detectArchivedVehicles();
            }
            return;
        }

        $shopId = $this->selectedShop ?? null;
        $this->loadAvailableVehicles($shopId);
        $this->detectArchivedVehicles();
    }

    /**
     * Detect archived vehicles - IDs in compatibility arrays but not in active tiles
     */
    protected function detectArchivedVehicles(): void
    {
        $this->archivedVehicles = [];

        // Collect all vehicle IDs from compatibility selections
        $selectedIds = array_unique(array_merge(
            $this->compatibilityOriginal,
            $this->compatibilityZamiennik
        ));

        if (empty($selectedIds) && empty($this->phantomCompatibilities)) {
            return;
        }

        // Collect all vehicle IDs from active tiles (flat)
        $activeTileIds = [];
        foreach ($this->vehiclesByBrand as $vehicles) {
            foreach ($vehicles as $v) {
                $activeTileIds[] = $v['id'];
            }
        }

        // Orphaned = in selections but not in tiles
        $orphanedIds = array_diff($selectedIds, $activeTileIds);

        if (empty($orphanedIds)) {
            return;
        }

        // Try loading from products table
        $foundProducts = Product::whereIn('id', $orphanedIds)
            ->get(['id', 'name', 'manufacturer', 'sku'])
            ->keyBy('id');

        // For IDs not found in products, try metadata from vehicle_compatibility
        $notFoundIds = array_diff($orphanedIds, $foundProducts->keys()->toArray());

        $metadataVehicles = [];
        if (!empty($notFoundIds) && $this->product?->id) {
            $metadataRecords = VehicleCompatibility::where('product_id', $this->product->id)
                ->whereIn('vehicle_model_id', $notFoundIds)
                ->whereNotNull('metadata')
                ->get(['vehicle_model_id', 'metadata']);

            foreach ($metadataRecords as $record) {
                $meta = $record->metadata;
                if (!empty($meta['ps_vehicle_name'])) {
                    $metadataVehicles[$record->vehicle_model_id] = [
                        'id' => $record->vehicle_model_id,
                        'name' => $meta['ps_vehicle_name'],
                        'manufacturer' => $meta['ps_vehicle_manufacturer'] ?? 'Nieznany',
                        'sku' => $meta['ps_vehicle_sku'] ?? null,
                        'source' => 'metadata',
                    ];
                }
            }
        }

        // Build archived vehicles array
        foreach ($orphanedIds as $vehicleId) {
            if ($foundProducts->has($vehicleId)) {
                $p = $foundProducts->get($vehicleId);
                $this->archivedVehicles[$vehicleId] = [
                    'id' => $p->id,
                    'name' => $p->name,
                    'manufacturer' => $p->manufacturer ?? 'Nieznany',
                    'sku' => $p->sku,
                    'source' => 'db',
                ];
            } elseif (isset($metadataVehicles[$vehicleId])) {
                $this->archivedVehicles[$vehicleId] = $metadataVehicles[$vehicleId];
            } else {
                $this->archivedVehicles[$vehicleId] = [
                    'id' => $vehicleId,
                    'name' => "Pojazd #$vehicleId (usuniety)",
                    'manufacturer' => 'Nieznany',
                    'sku' => null,
                    'source' => 'missing',
                ];
            }
        }

        // Add phantom records as archived with 'phantom' source
        foreach ($this->phantomCompatibilities as $key => $phantom) {
            $this->archivedVehicles[$key] = [
                'id' => $phantom['id'],
                'name' => $phantom['name'],
                'manufacturer' => $phantom['manufacturer'],
                'sku' => null,
                'source' => 'phantom',
                'type' => $phantom['type'],
            ];
        }

        Log::debug('ProductFormCompatibility::detectArchivedVehicles', [
            'product_id' => $this->product?->id,
            'orphaned_count' => count($orphanedIds),
            'found_in_db' => $foundProducts->count(),
            'found_in_metadata' => count($metadataVehicles),
            'phantom_count' => count($this->phantomCompatibilities),
        ]);
    }

    /**
     * Reset compatibility state
     */
    protected function resetCompatibilityState(): void
    {
        $this->compatibilityOriginal = [];
        $this->compatibilityZamiennik = [];
        $this->compatibilityPendingChanges = [];
        $this->vehiclesByBrand = [];
        $this->vehicleSearch = '';
        $this->vehicleBrandFilter = '';
        $this->archivedVehicles = [];
        $this->phantomCompatibilities = [];
    }

    /*
    |--------------------------------------------------------------------------
    | VEHICLE SELECTION METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Toggle vehicle selection based on current mode
     */
    public function toggleCompatibilityVehicle(int $vehicleId): void
    {
        if ($this->compatibilityMode === 'original') {
            $this->toggleCompatibilityOriginal($vehicleId);
        } else {
            $this->toggleCompatibilityZamiennik($vehicleId);
        }

        // Clear AI suggestion state when user accepts a suggestion
        if (isset($this->suggestedVehicleScores[$vehicleId])) {
            unset($this->suggestedVehicleScores[$vehicleId]);
            $this->ghostSuggestions = array_values(array_filter(
                $this->ghostSuggestions,
                fn($s) => $s['vehicle_id'] !== $vehicleId
            ));
        }

        $this->trackCompatibilityChange($vehicleId);
    }

    /**
     * Toggle vehicle as Original
     */
    public function toggleCompatibilityOriginal(int $vehicleId): void
    {
        if (in_array($vehicleId, $this->compatibilityOriginal)) {
            $this->compatibilityOriginal = array_values(
                array_diff($this->compatibilityOriginal, [$vehicleId])
            );
        } else {
            $this->compatibilityOriginal[] = $vehicleId;
        }
    }

    /**
     * Toggle vehicle as Zamiennik
     */
    public function toggleCompatibilityZamiennik(int $vehicleId): void
    {
        if (in_array($vehicleId, $this->compatibilityZamiennik)) {
            $this->compatibilityZamiennik = array_values(
                array_diff($this->compatibilityZamiennik, [$vehicleId])
            );
        } else {
            $this->compatibilityZamiennik[] = $vehicleId;
        }
    }

    /**
     * Set compatibility selection mode
     */
    public function setCompatibilityMode(string $mode): void
    {
        if (in_array($mode, ['original', 'zamiennik'])) {
            $this->compatibilityMode = $mode;
        }
    }

    /**
     * Select all vehicles in a brand
     */
    public function selectAllVehiclesInBrand(string $brand): void
    {
        if (!isset($this->vehiclesByBrand[$brand])) {
            return;
        }

        foreach ($this->vehiclesByBrand[$brand] as $vehicle) {
            $vehicleId = $vehicle['id'];

            if ($this->compatibilityMode === 'original') {
                if (!in_array($vehicleId, $this->compatibilityOriginal)) {
                    $this->compatibilityOriginal[] = $vehicleId;
                    $this->trackCompatibilityChange($vehicleId);
                }
            } else {
                if (!in_array($vehicleId, $this->compatibilityZamiennik)) {
                    $this->compatibilityZamiennik[] = $vehicleId;
                    $this->trackCompatibilityChange($vehicleId);
                }
            }
        }
    }

    /**
     * Deselect all vehicles in a brand
     */
    public function deselectAllVehiclesInBrand(string $brand): void
    {
        if (!isset($this->vehiclesByBrand[$brand])) {
            return;
        }

        foreach ($this->vehiclesByBrand[$brand] as $vehicle) {
            $vehicleId = $vehicle['id'];

            $this->compatibilityOriginal = array_values(
                array_diff($this->compatibilityOriginal, [$vehicleId])
            );
            $this->compatibilityZamiennik = array_values(
                array_diff($this->compatibilityZamiennik, [$vehicleId])
            );

            $this->trackCompatibilityChange($vehicleId);
        }
    }

    /**
     * Toggle brand section collapse state
     */
    public function toggleBrandCollapse(string $brand): void
    {
        if (in_array($brand, $this->collapsedBrands)) {
            $this->collapsedBrands = array_values(
                array_diff($this->collapsedBrands, [$brand])
            );
        } else {
            $this->collapsedBrands[] = $brand;
        }
    }

    /**
     * Track compatibility change
     */
    protected function trackCompatibilityChange(int $vehicleId): void
    {
        $this->compatibilityPendingChanges[$vehicleId] = [
            'vehicle_id' => $vehicleId,
            'timestamp' => now()->toIso8601String(),
        ];

        // Mark form as having unsaved changes
        if (property_exists($this, 'hasUnsavedChanges')) {
            $this->hasUnsavedChanges = true;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS FOR BLADE
    |--------------------------------------------------------------------------
    */

    /**
     * Check if vehicle is selected as Original
     */
    public function isCompatibilityOriginal(int $vehicleId): bool
    {
        return in_array($vehicleId, $this->compatibilityOriginal);
    }

    /**
     * Check if vehicle is selected as Zamiennik
     */
    public function isCompatibilityZamiennik(int $vehicleId): bool
    {
        return in_array($vehicleId, $this->compatibilityZamiennik);
    }

    /**
     * Check if vehicle is selected as both
     */
    public function isCompatibilityBoth(int $vehicleId): bool
    {
        return $this->isCompatibilityOriginal($vehicleId) &&
               $this->isCompatibilityZamiennik($vehicleId);
    }

    /**
     * Get vehicle tile CSS class
     */
    public function getCompatibilityTileClass(int $vehicleId): string
    {
        if ($this->isCompatibilityBoth($vehicleId)) {
            return 'vehicle-tile--selected-both';
        }
        if ($this->isCompatibilityOriginal($vehicleId)) {
            return 'vehicle-tile--selected-original';
        }
        if ($this->isCompatibilityZamiennik($vehicleId)) {
            return 'vehicle-tile--selected-zamiennik';
        }
        return '';
    }

    /**
     * Get filtered vehicles for display
     */
    public function getFilteredVehiclesByBrand(): array
    {
        $filtered = $this->vehiclesByBrand;

        // Apply brand filter
        if (!empty($this->vehicleBrandFilter)) {
            $filtered = array_filter($filtered, fn($brand) =>
                $brand === $this->vehicleBrandFilter,
                ARRAY_FILTER_USE_KEY
            );
        }

        // Apply search filter
        if (!empty($this->vehicleSearch)) {
            $search = mb_strtolower($this->vehicleSearch);
            foreach ($filtered as $brand => $vehicles) {
                $filtered[$brand] = array_filter($vehicles, fn($v) =>
                    str_contains(mb_strtolower($v['name']), $search) ||
                    str_contains(mb_strtolower($v['sku'] ?? ''), $search)
                );
            }
            // Remove empty brands
            $filtered = array_filter($filtered, fn($vehicles) => !empty($vehicles));
        }

        return $filtered;
    }

    /**
     * Get counts for summary display
     */
    public function getCompatibilityCounts(): array
    {
        $phantomOriginal = collect($this->phantomCompatibilities)->where('type', 'original')->count();
        $phantomZamiennik = collect($this->phantomCompatibilities)->where('type', 'zamiennik')->count();

        return [
            'original' => count($this->compatibilityOriginal) + $phantomOriginal,
            'zamiennik' => count($this->compatibilityZamiennik) + $phantomZamiennik,
            'total' => count(array_unique(array_merge(
                $this->compatibilityOriginal,
                $this->compatibilityZamiennik
            ))) + count($this->phantomCompatibilities),
            'pending' => count($this->compatibilityPendingChanges),
            'archived' => count($this->archivedVehicles),
        ];
    }

    /**
     * Get available brands for filter dropdown
     */
    public function getAvailableBrands(): array
    {
        return array_keys($this->vehiclesByBrand);
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE COMPATIBILITY
    |--------------------------------------------------------------------------
    */

    /**
     * Save compatibility data to database
     * Called from ProductFormSaver or directly
     */
    public function saveCompatibilityData(): void
    {
        if (!$this->product || !$this->product->id) {
            return;
        }

        // Only save for czesc-zamienna type products
        if ($this->product->productType?->slug !== 'czesc-zamienna') {
            return;
        }

        try {
            DB::transaction(function () {
                $shopId = $this->selectedShop ?? null;

                // Get attribute IDs
                $originalAttr = CompatibilityAttribute::where('code', 'original')->first();
                $zamiennikAttr = CompatibilityAttribute::where('code', 'replacement')->first();

                if (!$originalAttr || !$zamiennikAttr) {
                    Log::warning('ProductFormCompatibility: Missing compatibility attributes');
                    return;
                }

                // Delete existing compatibility for this product+shop
                // Preserve phantom records (vehicle_model_id is null)
                $deleteQuery = VehicleCompatibility::where('product_id', $this->product->id)
                    ->whereNotNull('vehicle_model_id');
                if ($shopId) {
                    $deleteQuery->where('shop_id', $shopId);
                }
                $deleteQuery->delete();

                // Insert Original selections
                $defaultShopId = $shopId ?? $this->getDefaultShopId();
                foreach ($this->compatibilityOriginal as $vehicleId) {
                    if (!Product::where('id', $vehicleId)->exists()) {
                        Log::warning('ProductFormCompatibility: Skipping archived vehicle in save', [
                            'product_id' => $this->product->id,
                            'vehicle_id' => $vehicleId,
                            'type' => 'original',
                        ]);
                        continue;
                    }
                    VehicleCompatibility::create([
                        'product_id' => $this->product->id,
                        'vehicle_model_id' => $vehicleId,
                        'shop_id' => $defaultShopId,
                        'compatibility_attribute_id' => $originalAttr->id,
                        'compatibility_source_id' => 1, // Manual
                        'verified' => true,
                        'verified_by' => auth()->id(),
                        'verified_at' => now(),
                    ]);
                }

                // Insert Zamiennik selections
                foreach ($this->compatibilityZamiennik as $vehicleId) {
                    // Skip if already added as Original (don't duplicate)
                    if (in_array($vehicleId, $this->compatibilityOriginal)) {
                        continue;
                    }
                    if (!Product::where('id', $vehicleId)->exists()) {
                        Log::warning('ProductFormCompatibility: Skipping archived vehicle in save', [
                            'product_id' => $this->product->id,
                            'vehicle_id' => $vehicleId,
                            'type' => 'zamiennik',
                        ]);
                        continue;
                    }
                    VehicleCompatibility::create([
                        'product_id' => $this->product->id,
                        'vehicle_model_id' => $vehicleId,
                        'shop_id' => $defaultShopId,
                        'compatibility_attribute_id' => $zamiennikAttr->id,
                        'compatibility_source_id' => 1, // Manual
                        'verified' => true,
                        'verified_by' => auth()->id(),
                        'verified_at' => now(),
                    ]);
                }

                // Clear pending changes
                $this->compatibilityPendingChanges = [];

                Log::info('ProductFormCompatibility::saveCompatibilityData', [
                    'product_id' => $this->product->id,
                    'shop_id' => $shopId,
                    'original_count' => count($this->compatibilityOriginal),
                    'zamiennik_count' => count($this->compatibilityZamiennik),
                ]);
            });

        } catch (\Exception $e) {
            Log::error('ProductFormCompatibility::saveCompatibilityData error', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get default shop ID
     * Uses first active shop since prestashop_shops doesn't have is_default column
     */
    protected function getDefaultShopId(): int
    {
        return \App\Models\PrestaShopShop::where('is_active', true)->orderBy('id')->value('id') ?? 1;
    }

    /**
     * Create a PendingProduct from an archived/phantom vehicle and redirect to import
     */
    public function createVehicleFromArchived(string $vehicleKey): void
    {
        if (!isset($this->archivedVehicles[$vehicleKey])) {
            session()->flash('error', 'Nie znaleziono danych archiwalnego pojazdu.');
            return;
        }

        $vehicle = $this->archivedVehicles[$vehicleKey];

        // Find product_type_id for 'pojazd'
        $vehicleTypeId = \App\Models\ProductType::where('slug', 'pojazd')->value('id');
        if (!$vehicleTypeId) {
            session()->flash('error', 'Brak typu produktu "pojazd" w systemie.');
            return;
        }

        $pendingProduct = \App\Models\PendingProduct::create([
            'name' => $vehicle['name'],
            'manufacturer' => $vehicle['manufacturer'],
            'product_type_id' => $vehicleTypeId,
            'imported_by' => auth()->id(),
            'compatibility_data' => [
                'created_from_phantom' => true,
                'source_product_id' => $this->product?->id,
                'original_vehicle_key' => $vehicleKey,
            ],
        ]);

        $this->redirect('/admin/products/import?editPending=' . $pendingProduct->id);
    }

    /*
    |--------------------------------------------------------------------------
    | GHOST SUGGESTIONS (AI Smart Matching)
    |--------------------------------------------------------------------------
    */

    /**
     * Load ghost suggestions from SmartSuggestionEngine
     * Called via wire:init on compatibility tab
     */
    public function loadGhostSuggestions(): void
    {
        if (!$this->product || !$this->product->id) {
            return;
        }

        // Only for czesc-zamienna type
        if ($this->product->productType?->slug !== 'czesc-zamienna') {
            return;
        }

        try {
            $engine = app(\App\Services\Compatibility\SmartSuggestionEngine::class);
            $this->ghostSuggestions = $engine->generateForProductCentral($this->product);

            // Load dismissed IDs
            $this->dismissedSuggestionIds = \App\Models\SmartSuggestionDismissal::where('product_id', $this->product->id)
                ->whereNull('restored_at')
                ->pluck('vehicle_product_id')
                ->toArray();

            // Build suggestedVehicleScores map for blade tile highlighting
            $this->suggestedVehicleScores = [];
            foreach ($this->ghostSuggestions as $suggestion) {
                $this->suggestedVehicleScores[$suggestion['vehicle_id']] = $suggestion['score'];
            }

        } catch (\Exception $e) {
            Log::error('Ghost suggestions load failed', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
            ]);
            $this->ghostSuggestions = [];
            $this->suggestedVehicleScores = [];
        }
    }

    /**
     * Dismiss a ghost suggestion
     */
    public function dismissGhostSuggestion(int $vehicleId): void
    {
        \App\Models\SmartSuggestionDismissal::updateOrCreate(
            [
                'product_id' => $this->product->id,
                'vehicle_product_id' => $vehicleId,
            ],
            [
                'dismissed_by' => auth()->id(),
                'dismissed_at' => now(),
                'restored_at' => null,
                'restored_by' => null,
            ]
        );

        // Remove from ghost suggestions
        $this->ghostSuggestions = array_values(array_filter(
            $this->ghostSuggestions,
            fn($s) => $s['vehicle_id'] !== $vehicleId
        ));

        unset($this->suggestedVehicleScores[$vehicleId]);
        $this->dismissedSuggestionIds[] = $vehicleId;
    }

    /**
     * Restore a dismissed suggestion
     */
    public function restoreDismissedSuggestion(int $vehicleId): void
    {
        $dismissal = \App\Models\SmartSuggestionDismissal::where('product_id', $this->product->id)
            ->where('vehicle_product_id', $vehicleId)
            ->first();

        if ($dismissal) {
            $dismissal->restore(auth()->user());
        }

        $this->dismissedSuggestionIds = array_values(
            array_diff($this->dismissedSuggestionIds, [$vehicleId])
        );

        // Reload ghost suggestions to include restored one
        $this->loadGhostSuggestions();
    }

    /**
     * Toggle show dismissed suggestions
     */
    public function toggleShowDismissed(): void
    {
        $this->showDismissedSuggestions = !$this->showDismissedSuggestions;
    }
}
