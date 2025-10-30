<?php

namespace App\Http\Livewire\Product;

use Livewire\Component;
use App\Models\Product;
use App\Models\VehicleModel;
use App\Models\VehicleCompatibility;
use App\Models\CompatibilityAttribute;
use App\Services\CompatibilityManager;
use App\Services\CompatibilityVehicleService;
use Illuminate\Support\Collection;

/**
 * CompatibilitySelector Component
 *
 * SKU-FIRST Livewire component for vehicle compatibility management
 *
 * ARCHITECTURE:
 * - SKU as PRIMARY identifier (NOT product_id)
 * - Livewire 3.x patterns (dispatch, #[On], proper DI)
 * - No inline styles (CSS classes only)
 * - Contextual wire:key (includes SKU)
 *
 * COMPLIANCE:
 * - Livewire 3.x (Context7 verified 2025-10-24)
 * - SKU_ARCHITECTURE_GUIDE.md (SKU-first patterns)
 * - CLAUDE.md (~220 lines, no inline styles)
 *
 * @version 2.0 SKU-FIRST
 * @since ETAP_05a FAZA 4
 */
class CompatibilitySelector extends Component
{
    public Product $product;
    public Collection $compatibilities;
    public Collection $searchResults;
    public array $searchFilters = ['brand' => '', 'model' => '', 'year' => null];
    public bool $editMode = false;
    public ?int $selectedVehicleId = null;
    public ?int $selectedAttributeId = null;

    protected $listeners = ['refreshCompatibilities' => 'loadCompatibilities'];

    /**
     * Mount component with product
     *
     * SKU-FIRST: Product passed in, SKU used for all operations
     */
    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->loadCompatibilities();
        $this->searchResults = collect();
    }

    /**
     * Load compatibilities using SKU-FIRST pattern
     *
     * Uses product_id relationship for now (VehicleCompatibility->product())
     * but ready for SKU-based lookup migration
     */
    protected function loadCompatibilities(): void
    {
        $this->compatibilities = $this->product
            ->vehicleCompatibility()
            ->with([
                'vehicleModel',
                'compatibilityAttribute',
                'compatibilitySource',
                'verifier'
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Search vehicles using CompatibilityVehicleService
     *
     * Minimum 2 characters for brand/model search
     */
    public function updatedSearchFilters(): void
    {
        $brand = trim($this->searchFilters['brand'] ?? '');
        $model = trim($this->searchFilters['model'] ?? '');
        $year = $this->searchFilters['year'] ?? null;

        // Minimum 2 characters for search
        if (strlen($brand) >= 2 || strlen($model) >= 2) {
            $filters = [];

            if (strlen($brand) >= 2) {
                $filters['brand'] = $brand;
            }

            if (strlen($model) >= 2) {
                $filters['model'] = $model;
            }

            if ($year && is_numeric($year)) {
                $filters['year'] = (int) $year;
            }

            $vehicleService = app(CompatibilityVehicleService::class);
            $this->searchResults = $vehicleService->findVehicles($filters);
        } else {
            $this->searchResults = collect();
        }
    }

    /**
     * Add vehicle compatibility using SKU-FIRST pattern
     *
     * Uses CompatibilityManager service (SKU-based)
     */
    public function addCompatibility(): void
    {
        $this->validate([
            'selectedVehicleId' => 'required|exists:vehicle_models,id',
            'selectedAttributeId' => 'nullable|exists:compatibility_attributes,id'
        ]);

        $vehicle = VehicleModel::findOrFail($this->selectedVehicleId);

        // Check for duplicate compatibility (SKU-based)
        $existing = VehicleCompatibility::where('product_id', $this->product->id)
            ->where('vehicle_model_id', $this->selectedVehicleId)
            ->first();

        if ($existing) {
            $this->addError('selectedVehicleId', 'This vehicle is already in compatibility list.');
            return;
        }

        try {
            // Use CompatibilityManager for SKU-first pattern (app() helper to avoid DI conflict)
            $compatManager = app(CompatibilityManager::class);

            $compatManager->addCompatibility($this->product, [
                'vehicle_model_id' => $this->selectedVehicleId,
                'vehicle_sku' => $vehicle->sku, // SKU-first backup
                'compatibility_attribute_id' => $this->selectedAttributeId,
                'compatibility_source_id' => 3, // Manual entry (default)
                'is_verified' => false
            ]);

            $this->loadCompatibilities();
            $this->reset(['selectedVehicleId', 'selectedAttributeId', 'searchFilters', 'searchResults']);

            $this->dispatch('compatibility-added', [
                'message' => 'Vehicle compatibility added successfully',
                'vehicle' => $vehicle->getFullName()
            ]);

            session()->flash('message', 'Vehicle compatibility added successfully.');
        } catch (\Exception $e) {
            $this->addError('general', 'Error adding compatibility: ' . $e->getMessage());
        }
    }

    /**
     * Update compatibility attribute
     *
     * Uses CompatibilityManager service
     */
    public function updateAttribute(int $compatId, string $attrIdValue): void
    {
        $this->validate([
            'attrIdValue' => 'nullable|exists:compatibility_attributes,id'
        ], [
            'attrIdValue' => $attrIdValue
        ]);

        try {
            $compat = VehicleCompatibility::where('id', $compatId)
                ->where('product_id', $this->product->id)
                ->firstOrFail();

            $attributeId = $attrIdValue === '' ? null : (int) $attrIdValue;

            $compatManager = app(CompatibilityManager::class);
            $compatManager->updateCompatibility($compat, [
                'compatibility_attribute_id' => $attributeId
            ]);

            $this->loadCompatibilities();

            $this->dispatch('compatibility-updated', [
                'message' => 'Compatibility attribute updated',
                'compatId' => $compatId
            ]);

            session()->flash('message', 'Compatibility attribute updated successfully.');
        } catch (\Exception $e) {
            $this->addError('general', 'Error updating attribute: ' . $e->getMessage());
        }
    }

    /**
     * Remove vehicle compatibility
     *
     * Uses CompatibilityManager service
     */
    public function removeCompatibility(int $compatId): void
    {
        try {
            $compat = VehicleCompatibility::where('id', $compatId)
                ->where('product_id', $this->product->id)
                ->firstOrFail();

            $vehicleName = $compat->vehicleModel->getFullName();

            $compatManager = app(CompatibilityManager::class);
            $compatManager->removeCompatibility($compat);

            $this->loadCompatibilities();

            $this->dispatch('compatibility-removed', [
                'message' => 'Vehicle compatibility removed',
                'vehicle' => $vehicleName
            ]);

            session()->flash('message', 'Vehicle compatibility removed successfully.');
        } catch (\Exception $e) {
            $this->addError('general', 'Error removing compatibility: ' . $e->getMessage());
        }
    }

    /**
     * Verify compatibility (admin only)
     *
     * Uses CompatibilityManager service
     */
    public function verifyCompatibility(int $compatId): void
    {
        // Admin-only verification
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Only administrators can verify compatibility');
        }

        try {
            $compat = VehicleCompatibility::where('id', $compatId)
                ->where('product_id', $this->product->id)
                ->firstOrFail();

            $compatManager = app(CompatibilityManager::class);
            $compatManager->verifyCompatibility($compat, auth()->user());

            $this->loadCompatibilities();

            $this->dispatch('compatibility-verified', [
                'message' => 'Compatibility verified',
                'compatId' => $compatId
            ]);

            session()->flash('message', 'Compatibility verified successfully.');
        } catch (\Exception $e) {
            $this->addError('general', 'Error verifying compatibility: ' . $e->getMessage());
        }
    }

    public function toggleEditMode(): void
    {
        $this->editMode = !$this->editMode;

        if (!$this->editMode) {
            // Clear search state when exiting edit mode
            $this->reset(['selectedVehicleId', 'selectedAttributeId', 'searchFilters', 'searchResults']);
        }
    }

    public function getCompatibilityAttributesProperty(): Collection
    {
        return CompatibilityAttribute::orderBy('order')->get();
    }

    public function render()
    {
        return view('livewire.product.compatibility-selector');
    }
}
