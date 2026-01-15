<?php

namespace App\Http\Livewire\Products\Import\Modals;

use App\Http\Livewire\Admin\Compatibility\Traits\ManagesVehicleSelection;
use App\Models\PendingProduct;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\CompatibilityAttribute;
use App\Services\Compatibility\ShopFilteringService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Attributes\On;

/**
 * CompatibilityModal - ETAP_06 FAZA 5.6 (REDESIGNED + BULK SUPPORT)
 *
 * Modal dopasowań pojazdów dla pending products - KOPIA widoku /admin/compatibility
 * z tile-based UI i filtrem SKU części.
 *
 * Features:
 * - Tile-based vehicle selection (click = toggle Original/Zamiennik)
 * - Per-shop filtering (kontekst sklepu)
 * - Brand sections with collapse/expand
 * - Selection mode toggle (Original/Zamiennik)
 * - Copy from product functionality
 * - BULK MODE: Edit multiple products at once with sidebar
 *
 * @package App\Http\Livewire\Products\Import\Modals
 * @since 2025-12-09
 */
class CompatibilityModal extends Component
{
    use ManagesVehicleSelection;

    /*
    |--------------------------------------------------------------------------
    | MODAL STATE
    |--------------------------------------------------------------------------
    */

    /** Modal visibility */
    public bool $showModal = false;

    /** Currently editing pending product ID (single mode) */
    public ?int $pendingProductId = null;

    /**
     * Alias for pendingProductId - required by ManagesVehicleSelection trait
     * The trait uses $editingProductId for trackChange() method
     */
    public ?int $editingProductId = null;

    /** Pending product model (not hydrated - just data) */
    public ?array $pendingProductData = null;

    /** Processing flag */
    public bool $isProcessing = false;

    /*
    |--------------------------------------------------------------------------
    | BULK MODE STATE
    |--------------------------------------------------------------------------
    */

    /** Bulk mode enabled flag */
    public bool $bulkMode = false;

    /** Array of pending product IDs for bulk edit */
    public array $bulkProductIds = [];

    /** Array of pending products data for sidebar */
    public array $bulkProductsData = [];

    /** Currently selected product in sidebar (for preview) */
    public ?int $activeProductId = null;

    /*
    |--------------------------------------------------------------------------
    | FILTERS
    |--------------------------------------------------------------------------
    */

    /** Shop context for filtering */
    public ?int $shopContext = null;

    /** Brand filter */
    public string $filterBrand = '';

    /** Vehicle search */
    public string $vehicleSearch = '';

    /** Collapsed brand sections */
    public array $collapsedBrands = [];

    /** Copy from product SKU */
    public string $copyFromSku = '';

    /*
    |--------------------------------------------------------------------------
    | LISTENERS
    |--------------------------------------------------------------------------
    */

    protected $listeners = [
        'openCompatibilityModal' => 'openModal',
    ];

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE
    |--------------------------------------------------------------------------
    */

    #[On('openCompatibilityModal')]
    public function openModal(int $productId): void
    {
        $this->resetBulkMode();
        $this->reset([
            'filterBrand', 'vehicleSearch', 'collapsedBrands', 'copyFromSku',
            'selectedOriginal', 'selectedZamiennik', 'pendingChanges'
        ]);
        $this->selectionMode = 'original';

        $pendingProduct = PendingProduct::find($productId);
        if (!$pendingProduct) {
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Nie znaleziono produktu',
            ]);
            return;
        }

        $this->pendingProductId = $productId;
        $this->editingProductId = $productId; // Set alias for ManagesVehicleSelection trait
        $this->pendingProductData = [
            'id' => $pendingProduct->id,
            'sku' => $pendingProduct->sku,
            'name' => $pendingProduct->name ?? '(brak nazwy)',
        ];

        // Load existing compatibility data from JSON
        $existingData = $pendingProduct->compatibility_data ?? [];
        $this->loadFromCompatibilityData($existingData);

        $this->showModal = true;
    }

    /**
     * Open modal in BULK MODE for multiple products
     */
    #[On('openBulkCompatibilityModal')]
    public function openBulkModal(array $productIds): void
    {
        $this->reset([
            'filterBrand', 'vehicleSearch', 'collapsedBrands', 'copyFromSku',
            'selectedOriginal', 'selectedZamiennik', 'pendingChanges',
            'pendingProductId', 'pendingProductData'
        ]);
        $this->selectionMode = 'original';

        if (empty($productIds)) {
            $this->dispatch('flash-message', [
                'type' => 'warning',
                'message' => 'Nie wybrano zadnych produktow',
            ]);
            return;
        }

        // Enable bulk mode
        $this->bulkMode = true;
        $this->bulkProductIds = $productIds;

        // Load products data for sidebar
        $products = PendingProduct::whereIn('id', $productIds)->get();
        $this->bulkProductsData = $products->map(fn($p) => [
            'id' => $p->id,
            'sku' => $p->sku,
            'name' => $p->name ?? '(brak nazwy)',
            'compatibility_count' => count($p->compatibility_data['compatibilities'] ?? []),
        ])->toArray();

        // Set first product as active for preview
        if (!empty($this->bulkProductsData)) {
            $this->activeProductId = $this->bulkProductsData[0]['id'];
            $this->editingProductId = $this->activeProductId; // Set alias for ManagesVehicleSelection trait
            $this->loadActiveProductCompatibilities();
        }

        $this->showModal = true;
    }

    /**
     * Reset bulk mode state
     */
    protected function resetBulkMode(): void
    {
        $this->bulkMode = false;
        $this->bulkProductIds = [];
        $this->bulkProductsData = [];
        $this->activeProductId = null;
    }

    /**
     * Select a product in bulk mode sidebar (for preview)
     */
    public function selectBulkProduct(int $productId): void
    {
        if (!$this->bulkMode || !in_array($productId, $this->bulkProductIds)) {
            return;
        }

        $this->activeProductId = $productId;
        $this->editingProductId = $productId; // Update alias for ManagesVehicleSelection trait
        $this->loadActiveProductCompatibilities();
    }

    /**
     * Load compatibilities from active product (for preview in bulk mode)
     */
    protected function loadActiveProductCompatibilities(): void
    {
        if (!$this->activeProductId) {
            return;
        }

        $product = PendingProduct::find($this->activeProductId);
        if ($product) {
            $this->loadFromCompatibilityData($product->compatibility_data ?? []);
        }
    }

    /**
     * Load selections from compatibility_data JSON
     */
    protected function loadFromCompatibilityData(array $data): void
    {
        $this->selectedOriginal = [];
        $this->selectedZamiennik = [];

        if (empty($data['compatibilities'])) {
            return;
        }

        foreach ($data['compatibilities'] as $compat) {
            $vehicleId = $compat['vehicle_id'] ?? null;
            // Normalize attribute name - remove Polish diacritics for comparison
            $attrName = strtolower($compat['compatibility_attribute_name'] ?? '');
            $attrName = str_replace(['ą','ć','ę','ł','ń','ó','ś','ź','ż'], ['a','c','e','l','n','o','s','z','z'], $attrName);

            if (!$vehicleId) continue;

            if (str_contains($attrName, 'oryginal') || str_contains($attrName, 'original')) {
                $this->selectedOriginal[] = $vehicleId;
            } elseif (str_contains($attrName, 'zamiennik') || str_contains($attrName, 'replacement')) {
                $this->selectedZamiennik[] = $vehicleId;
            }
        }

        $this->selectedOriginal = array_unique($this->selectedOriginal);
        $this->selectedZamiennik = array_unique($this->selectedZamiennik);
    }

    /**
     * Close modal
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['pendingProductId', 'pendingProductData', 'editingProductId']);
        $this->resetBulkMode();
    }

    /*
    |--------------------------------------------------------------------------
    | VEHICLE DATA
    |--------------------------------------------------------------------------
    */

    /**
     * Get vehicles filtered by shop context
     */
    public function getVehiclesProperty(): Collection
    {
        $query = Product::byType('pojazd')
            ->whereNotNull('manufacturer')
            ->where('manufacturer', '!=', '')
            ->orderBy('manufacturer')
            ->orderBy('name');

        // Apply shop context filtering
        if ($this->shopContext !== null) {
            $shop = PrestaShopShop::find($this->shopContext);
            if ($shop && $shop->allowed_vehicle_brands !== null) {
                if (empty($shop->allowed_vehicle_brands)) {
                    return collect();
                }
                $query->whereIn('manufacturer', $shop->allowed_vehicle_brands);
            }
        }

        $vehicles = $query->get();

        // Apply brand filter
        if (!empty($this->filterBrand)) {
            $vehicles = $vehicles->filter(fn($v) => $v->manufacturer === $this->filterBrand);
        }

        // Apply vehicle search
        if (!empty($this->vehicleSearch)) {
            $search = strtolower($this->vehicleSearch);
            $vehicles = $vehicles->filter(function ($v) use ($search) {
                return str_contains(strtolower($v->manufacturer ?? ''), $search)
                    || str_contains(strtolower($v->name ?? ''), $search)
                    || str_contains(strtolower($v->sku ?? ''), $search);
            });
        }

        return $vehicles;
    }

    /**
     * Get vehicles grouped by brand (manufacturer)
     */
    public function getVehiclesGroupedProperty(): Collection
    {
        return $this->vehicles->groupBy('manufacturer');
    }

    /**
     * Get unique brands from available vehicles
     */
    public function getBrandsProperty(): Collection
    {
        return Product::byType('pojazd')
            ->whereNotNull('manufacturer')
            ->where('manufacturer', '!=', '')
            ->distinct()
            ->orderBy('manufacturer')
            ->pluck('manufacturer');
    }

    /**
     * Get available shops
     */
    public function getShopsProperty(): Collection
    {
        return PrestaShopShop::orderBy('name')->get();
    }

    /*
    |--------------------------------------------------------------------------
    | UI HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Toggle brand collapse
     */
    public function toggleBrandCollapse(string $brand): void
    {
        if (in_array($brand, $this->collapsedBrands)) {
            $this->collapsedBrands = array_values(array_diff($this->collapsedBrands, [$brand]));
        } else {
            $this->collapsedBrands[] = $brand;
        }
    }

    /**
     * Check if brand is collapsed
     */
    public function isBrandCollapsed(string $brand): bool
    {
        return in_array($brand, $this->collapsedBrands);
    }

    /**
     * Reset filters
     */
    public function resetFilters(): void
    {
        $this->filterBrand = '';
        $this->vehicleSearch = '';
        $this->shopContext = null;
    }

    /*
    |--------------------------------------------------------------------------
    | COPY FUNCTIONALITY
    |--------------------------------------------------------------------------
    */

    /**
     * Copy compatibilities from another product
     */
    public function copyFromProduct(): void
    {
        if (empty($this->copyFromSku)) {
            return;
        }

        // Try PendingProduct first
        $source = PendingProduct::where('sku', $this->copyFromSku)->first();
        if ($source) {
            $compatData = $source->compatibility_data ?? [];
            if (!empty($compatData['compatibilities'])) {
                $copiedCount = 0;
                foreach ($compatData['compatibilities'] as $compat) {
                    $vehicleId = $compat['vehicle_id'] ?? null;
                    $attrName = strtolower($compat['compatibility_attribute_name'] ?? '');

                    if (!$vehicleId) continue;

                    if (str_contains($attrName, 'oryginal') || str_contains($attrName, 'original')) {
                        if (!in_array($vehicleId, $this->selectedOriginal)) {
                            $this->selectedOriginal[] = $vehicleId;
                            $copiedCount++;
                        }
                    } elseif (str_contains($attrName, 'zamiennik') || str_contains($attrName, 'replacement')) {
                        if (!in_array($vehicleId, $this->selectedZamiennik)) {
                            $this->selectedZamiennik[] = $vehicleId;
                            $copiedCount++;
                        }
                    }
                }

                $this->dispatch('flash-message', [
                    'type' => 'success',
                    'message' => "Skopiowano {$copiedCount} dopasowan z produktu {$this->copyFromSku}",
                ]);
            } else {
                $this->dispatch('flash-message', [
                    'type' => 'info',
                    'message' => "Produkt {$this->copyFromSku} nie ma zdefiniowanych dopasowan",
                ]);
            }
            $this->copyFromSku = '';
            return;
        }

        // Try Product model with vehicleCompatibility
        $product = Product::where('sku', $this->copyFromSku)
            ->with('vehicleCompatibility.compatibilityAttribute')
            ->first();

        if (!$product) {
            $this->dispatch('flash-message', [
                'type' => 'warning',
                'message' => "Nie znaleziono produktu o SKU: {$this->copyFromSku}",
            ]);
            return;
        }

        $copiedCount = 0;
        foreach ($product->vehicleCompatibility ?? [] as $vc) {
            $vehicleId = $vc->vehicle_model_id;
            $attrCode = $vc->compatibilityAttribute?->code ?? '';

            if ($attrCode === 'original') {
                if (!in_array($vehicleId, $this->selectedOriginal)) {
                    $this->selectedOriginal[] = $vehicleId;
                    $copiedCount++;
                }
            } elseif ($attrCode === 'replacement') {
                if (!in_array($vehicleId, $this->selectedZamiennik)) {
                    $this->selectedZamiennik[] = $vehicleId;
                    $copiedCount++;
                }
            }
        }

        $this->dispatch('flash-message', [
            'type' => 'success',
            'message' => "Skopiowano {$copiedCount} dopasowan z produktu {$this->copyFromSku}",
        ]);
        $this->copyFromSku = '';
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE
    |--------------------------------------------------------------------------
    */

    /**
     * Save compatibilities to pending product JSON
     */
    public function saveCompatibilities(): void
    {
        // In bulk mode, use bulk save
        if ($this->bulkMode) {
            $this->saveBulkCompatibilities();
            return;
        }

        if (!$this->pendingProductId) {
            return;
        }

        $pendingProduct = PendingProduct::find($this->pendingProductId);
        if (!$pendingProduct) {
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Nie znaleziono produktu',
            ]);
            return;
        }

        $this->isProcessing = true;

        try {
            // Get attribute names
            $originalAttr = CompatibilityAttribute::where('code', 'original')->first();
            $replacementAttr = CompatibilityAttribute::where('code', 'replacement')->first();

            $compatibilities = [];

            // Add Original vehicles
            foreach ($this->selectedOriginal as $vehicleId) {
                $vehicle = Product::find($vehicleId);
                if (!$vehicle) continue;

                $compatibilities[] = [
                    'vehicle_id' => $vehicle->id,
                    'vehicle_sku' => $vehicle->sku,
                    'vehicle_name' => $vehicle->name,
                    'compatibility_attribute_id' => $originalAttr?->id,
                    'compatibility_attribute_name' => $originalAttr?->name ?? 'Oryginal',
                    'compatibility_source_id' => 1,
                    'compatibility_source_name' => 'Manual',
                    'notes' => '',
                ];
            }

            // Add Zamiennik vehicles (skip if already in Original)
            foreach ($this->selectedZamiennik as $vehicleId) {
                if (in_array($vehicleId, $this->selectedOriginal)) {
                    continue; // Skip duplicates - Original has priority
                }

                $vehicle = Product::find($vehicleId);
                if (!$vehicle) continue;

                $compatibilities[] = [
                    'vehicle_id' => $vehicle->id,
                    'vehicle_sku' => $vehicle->sku,
                    'vehicle_name' => $vehicle->name,
                    'compatibility_attribute_id' => $replacementAttr?->id,
                    'compatibility_attribute_name' => $replacementAttr?->name ?? 'Zamiennik',
                    'compatibility_source_id' => 1,
                    'compatibility_source_name' => 'Manual',
                    'notes' => '',
                ];
            }

            // Build compatibility_data structure
            $compatibilityData = [
                'compatibilities' => $compatibilities,
                'updated_at' => now()->toIso8601String(),
            ];

            $pendingProduct->update([
                'compatibility_data' => $compatibilityData,
            ]);

            // Recalculate completion percentage
            $pendingProduct->recalculateCompletion();

            Log::info('[CompatibilityModal] Saved compatibilities', [
                'pending_product_id' => $this->pendingProductId,
                'original_count' => count($this->selectedOriginal),
                'zamiennik_count' => count($this->selectedZamiennik),
                'total' => count($compatibilities),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => 'Zapisano ' . count($compatibilities) . ' dopasowan',
            ]);

            $this->dispatch('refreshPendingProducts');
            $this->closeModal();

        } catch (\Exception $e) {
            Log::error('[CompatibilityModal] Save failed', [
                'pending_product_id' => $this->pendingProductId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad zapisu: ' . $e->getMessage(),
            ]);
        } finally {
            $this->isProcessing = false;
        }
    }

    /**
     * Save compatibilities to ALL selected products (BULK MODE)
     */
    protected function saveBulkCompatibilities(): void
    {
        if (empty($this->bulkProductIds)) {
            return;
        }

        $this->isProcessing = true;

        try {
            // Get attribute names
            $originalAttr = CompatibilityAttribute::where('code', 'original')->first();
            $replacementAttr = CompatibilityAttribute::where('code', 'replacement')->first();

            // Build compatibilities array
            $compatibilities = [];

            // Add Original vehicles
            foreach ($this->selectedOriginal as $vehicleId) {
                $vehicle = Product::find($vehicleId);
                if (!$vehicle) continue;

                $compatibilities[] = [
                    'vehicle_id' => $vehicle->id,
                    'vehicle_sku' => $vehicle->sku,
                    'vehicle_name' => $vehicle->name,
                    'compatibility_attribute_id' => $originalAttr?->id,
                    'compatibility_attribute_name' => $originalAttr?->name ?? 'Oryginal',
                    'compatibility_source_id' => 1,
                    'compatibility_source_name' => 'Manual',
                    'notes' => '',
                ];
            }

            // Add Zamiennik vehicles (skip if already in Original)
            foreach ($this->selectedZamiennik as $vehicleId) {
                if (in_array($vehicleId, $this->selectedOriginal)) {
                    continue;
                }

                $vehicle = Product::find($vehicleId);
                if (!$vehicle) continue;

                $compatibilities[] = [
                    'vehicle_id' => $vehicle->id,
                    'vehicle_sku' => $vehicle->sku,
                    'vehicle_name' => $vehicle->name,
                    'compatibility_attribute_id' => $replacementAttr?->id,
                    'compatibility_attribute_name' => $replacementAttr?->name ?? 'Zamiennik',
                    'compatibility_source_id' => 1,
                    'compatibility_source_name' => 'Manual',
                    'notes' => '',
                ];
            }

            // Build compatibility_data structure
            $compatibilityData = [
                'compatibilities' => $compatibilities,
                'updated_at' => now()->toIso8601String(),
            ];

            // Update ALL selected products
            $updatedCount = PendingProduct::whereIn('id', $this->bulkProductIds)
                ->update(['compatibility_data' => $compatibilityData]);

            Log::info('[CompatibilityModal] Bulk saved compatibilities', [
                'product_count' => $updatedCount,
                'product_ids' => $this->bulkProductIds,
                'original_count' => count($this->selectedOriginal),
                'zamiennik_count' => count($this->selectedZamiennik),
                'total_compatibilities' => count($compatibilities),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => "Zapisano " . count($compatibilities) . " dopasowan dla {$updatedCount} produktow",
            ]);

            $this->dispatch('refreshPendingProducts');
            $this->closeModal();

        } catch (\Exception $e) {
            Log::error('[CompatibilityModal] Bulk save failed', [
                'product_ids' => $this->bulkProductIds,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad zapisu: ' . $e->getMessage(),
            ]);
        } finally {
            $this->isProcessing = false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SKIP FLAG (Brak dopasowan)
    |--------------------------------------------------------------------------
    */

    /**
     * Check if skip_compatibility flag is set
     */
    public function getIsSkippedProperty(): bool
    {
        if ($this->bulkMode) {
            return false; // Skip flag not applicable in bulk mode
        }

        $product = PendingProduct::find($this->pendingProductId);
        return $product?->skip_compatibility ?? false;
    }

    /**
     * Set "Brak dopasowan" flag and close modal
     *
     * ETAP_06: Quick Actions - skip flag with history tracking
     */
    public function setSkipCompatibility(): void
    {
        if ($this->bulkMode || !$this->pendingProductId) {
            return;
        }

        $this->isProcessing = true;

        try {
            $product = PendingProduct::find($this->pendingProductId);
            if ($product) {
                $product->setSkipFlag('skip_compatibility', true);

                Log::info('[CompatibilityModal] Set skip_compatibility flag', [
                    'pending_product_id' => $this->pendingProductId,
                    'user_id' => auth()->id(),
                ]);

                $this->dispatch('flash-message', [
                    'type' => 'info',
                    'message' => 'Oznaczono jako "Brak dopasowan"',
                ]);

                $this->dispatch('refreshPendingProducts');
                $this->closeModal();
            }

        } catch (\Exception $e) {
            Log::error('[CompatibilityModal] Set skip flag failed', [
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad: ' . $e->getMessage(),
            ]);
        } finally {
            $this->isProcessing = false;
        }
    }

    /**
     * Clear skip_compatibility flag
     */
    public function clearSkipCompatibility(): void
    {
        if ($this->bulkMode || !$this->pendingProductId) {
            return;
        }

        $product = PendingProduct::find($this->pendingProductId);
        if ($product) {
            $product->setSkipFlag('skip_compatibility', false);

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => 'Odznaczono "Brak dopasowan"',
            ]);

            // Refresh the row to update status %
            $this->dispatch('refreshPendingProducts');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        return view('livewire.products.import.modals.compatibility-modal');
    }
}
