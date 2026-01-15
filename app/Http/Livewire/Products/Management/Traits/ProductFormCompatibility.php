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

            // Populate selection arrays
            $this->compatibilityOriginal = $compatibilities
                ->filter(fn($c) => $c->compatibilityAttribute?->code === 'original')
                ->pluck('vehicle_model_id')
                ->unique()
                ->values()
                ->toArray();

            $this->compatibilityZamiennik = $compatibilities
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

            // Load available vehicles
            $this->loadAvailableVehicles($shopId);

            $this->compatibilityPendingChanges = [];

            Log::debug('ProductFormCompatibility::loadCompatibilityData', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'original_count' => count($this->compatibilityOriginal),
                'zamiennik_count' => count($this->compatibilityZamiennik),
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
        return [
            'original' => count($this->compatibilityOriginal),
            'zamiennik' => count($this->compatibilityZamiennik),
            'total' => count(array_unique(array_merge(
                $this->compatibilityOriginal,
                $this->compatibilityZamiennik
            ))),
            'pending' => count($this->compatibilityPendingChanges),
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
                $deleteQuery = VehicleCompatibility::where('product_id', $this->product->id);
                if ($shopId) {
                    $deleteQuery->where('shop_id', $shopId);
                }
                $deleteQuery->delete();

                // Insert Original selections
                foreach ($this->compatibilityOriginal as $vehicleId) {
                    VehicleCompatibility::create([
                        'product_id' => $this->product->id,
                        'vehicle_model_id' => $vehicleId,
                        'shop_id' => $shopId ?? $this->getDefaultShopId(),
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

                    VehicleCompatibility::create([
                        'product_id' => $this->product->id,
                        'vehicle_model_id' => $vehicleId,
                        'shop_id' => $shopId ?? $this->getDefaultShopId(),
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
}
