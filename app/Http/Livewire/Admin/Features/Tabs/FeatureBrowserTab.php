<?php

namespace App\Http\Livewire\Admin\Features\Tabs;

use App\Models\FeatureGroup;
use App\Models\FeatureType;
use App\Models\FeatureValue;
use App\Services\Product\FeatureUsageService;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

/**
 * FeatureBrowserTab - 3-column browser for vehicle features
 *
 * LAYOUT (like AttributeValueManager):
 * - Left column: Feature Groups (collapsible list)
 * - Middle column: Feature Types + Values for selected group
 * - Right column: Products using selected value
 *
 * LIVEWIRE 3.x COMPLIANCE:
 * - #[Computed] for memoized data
 * - dispatch() events
 * - NO constructor DI (lazy loading)
 *
 * CLAUDE.md: <300 lines (currently ~250 lines)
 *
 * @package App\Http\Livewire\Admin\Features\Tabs
 * @version 1.0
 * @since ETAP_07e Phase 2 (2025-12-17)
 */
class FeatureBrowserTab extends Component
{
    // ========================================
    // SELECTION STATE
    // ========================================

    /** Selected group ID (left column) */
    public ?int $selectedGroupId = null;

    /** Selected feature type ID (middle column) */
    public ?int $selectedFeatureTypeId = null;

    /** Selected feature value IDs (middle column - checkboxes) */
    public array $selectedValueIds = [];

    /** Selected custom values (middle column - checkboxes for custom) */
    public array $selectedCustomValues = [];

    /** Search query for feature types */
    public string $searchFeatureType = '';

    // ========================================
    // SERVICE (LAZY LOADING)
    // ========================================

    private ?FeatureUsageService $usageService = null;

    protected function getUsageService(): FeatureUsageService
    {
        return $this->usageService ??= app(FeatureUsageService::class);
    }

    // ========================================
    // COMPUTED PROPERTIES
    // ========================================

    /**
     * Get groups with stats (left column)
     */
    #[Computed]
    public function groups(): Collection
    {
        return $this->getUsageService()->getGroupsWithStats();
    }

    /**
     * Get feature types for selected group (middle column header)
     */
    #[Computed]
    public function featureTypes(): Collection
    {
        if (!$this->selectedGroupId) {
            return collect([]);
        }

        $stats = $this->getUsageService()->getUsageStatsForGroup($this->selectedGroupId);

        return $stats->map(fn($row) => [
            'id' => $row->id,
            'name' => $row->name,
            'code' => $row->code,
            'value_type' => $row->value_type,
            'unit' => $row->unit,
            'products_count' => $row->products_count,
            'values_count' => $row->values_count,
        ])->values();
    }

    /**
     * Get values for selected feature type (middle column list)
     */
    #[Computed]
    public function featureValues(): Collection
    {
        if (!$this->selectedFeatureTypeId) {
            return collect([]);
        }

        $stats = $this->getUsageService()->getUsageStatsForFeatureType($this->selectedFeatureTypeId);

        return $stats->map(fn($row) => [
            'id' => $row->id,
            'value' => $row->value,
            'products_count' => $row->products_count,
        ])->values();
    }

    /**
     * Get custom values stats for selected feature type
     */
    #[Computed]
    public function customValuesStats(): array
    {
        if (!$this->selectedFeatureTypeId) {
            return ['count' => 0, 'unique_values' => 0];
        }

        return $this->getUsageService()->getCustomValuesStats($this->selectedFeatureTypeId);
    }

    /**
     * Get custom values for selected feature type (middle column)
     */
    #[Computed]
    public function customValues(): Collection
    {
        if (!$this->selectedFeatureTypeId) {
            return collect([]);
        }

        return $this->getUsageService()->getCustomValues($this->selectedFeatureTypeId);
    }

    /**
     * Get products for selected values (right column)
     * Includes both predefined and custom values
     */
    #[Computed]
    public function products(): Collection
    {
        if (empty($this->selectedValueIds) && empty($this->selectedCustomValues)) {
            return collect([]);
        }

        $products = collect([]);

        // Get products for predefined values
        foreach ($this->selectedValueIds as $valueId) {
            $valueProducts = $this->getUsageService()->getProductsUsingFeatureValue($valueId);
            $products = $products->merge($valueProducts);
        }

        // Get products for custom values
        if ($this->selectedFeatureTypeId && !empty($this->selectedCustomValues)) {
            foreach ($this->selectedCustomValues as $customValue) {
                $customProducts = $this->getUsageService()->getProductsByCustomValue(
                    $this->selectedFeatureTypeId,
                    $customValue
                );
                $products = $products->merge($customProducts);
            }
        }

        // Remove duplicates by product ID
        return $products->unique('id')->values();
    }

    /**
     * Get selected feature type details
     */
    #[Computed]
    public function selectedFeatureType(): ?array
    {
        if (!$this->selectedFeatureTypeId) {
            return null;
        }

        $type = FeatureType::find($this->selectedFeatureTypeId);

        return $type ? [
            'id' => $type->id,
            'name' => $type->name,
            'code' => $type->code,
            'value_type' => $type->value_type,
            'unit' => $type->unit,
        ] : null;
    }

    // ========================================
    // ACTIONS
    // ========================================

    /**
     * Select a group (left column click)
     */
    public function selectGroup(int $groupId): void
    {
        $this->selectedGroupId = $groupId;
        $this->selectedFeatureTypeId = null;
        $this->selectedValueIds = [];
        $this->selectedCustomValues = [];

        // Clear computed caches
        unset($this->featureTypes, $this->featureValues, $this->products, $this->customValues);
    }

    /**
     * Select a feature type (middle column click)
     */
    public function selectFeatureType(int $featureTypeId): void
    {
        $this->selectedFeatureTypeId = $featureTypeId;
        $this->selectedValueIds = [];
        $this->selectedCustomValues = [];

        // Clear computed caches
        unset($this->featureValues, $this->products, $this->customValuesStats, $this->customValues);
    }

    /**
     * Toggle value selection (checkbox)
     */
    public function toggleValue(int $valueId): void
    {
        if (in_array($valueId, $this->selectedValueIds)) {
            $this->selectedValueIds = array_values(array_diff($this->selectedValueIds, [$valueId]));
        } else {
            $this->selectedValueIds[] = $valueId;
        }

        // Clear products cache
        unset($this->products);
    }

    /**
     * Toggle custom value selection (checkbox)
     */
    public function toggleCustomValue(string $customValue): void
    {
        if (in_array($customValue, $this->selectedCustomValues)) {
            $this->selectedCustomValues = array_values(array_diff($this->selectedCustomValues, [$customValue]));
        } else {
            $this->selectedCustomValues[] = $customValue;
        }

        // Clear products cache
        unset($this->products);
    }

    /**
     * Select all values (predefined + custom)
     */
    public function selectAllValues(): void
    {
        $this->selectedValueIds = $this->featureValues->pluck('id')->toArray();
        $this->selectedCustomValues = $this->customValues->pluck('value')->toArray();
        unset($this->products);
    }

    /**
     * Deselect all values (predefined + custom)
     */
    public function deselectAllValues(): void
    {
        $this->selectedValueIds = [];
        $this->selectedCustomValues = [];
        unset($this->products);
    }

    /**
     * Navigate to product edit
     */
    public function goToProduct(int $productId): void
    {
        $this->redirect(route('admin.products.edit', $productId), navigate: true);
    }

    /**
     * Reset all selections
     */
    public function resetSelections(): void
    {
        $this->selectedGroupId = null;
        $this->selectedFeatureTypeId = null;
        $this->selectedValueIds = [];
        $this->selectedCustomValues = [];

        unset($this->featureTypes, $this->featureValues, $this->products, $this->customValues);
    }

    // ========================================
    // EVENT HANDLERS
    // ========================================

    /**
     * Refresh data when feature is updated elsewhere
     */
    #[On('feature-updated')]
    public function refreshData(): void
    {
        unset($this->groups, $this->featureTypes, $this->featureValues, $this->products);
    }

    // ========================================
    // RENDER
    // ========================================

    public function render()
    {
        return view('livewire.admin.features.tabs.feature-browser-tab');
    }
}
