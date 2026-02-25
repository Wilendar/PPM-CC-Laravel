<?php

namespace App\Http\Livewire\Products\Listing\Traits;

use App\Models\UserFilterPreset;
use Livewire\Attributes\Computed;

/**
 * ProductListPresets Trait
 *
 * Manages saved filter presets for ProductList:
 * - Save current filter state as named preset
 * - Apply saved presets
 * - Delete presets
 * - Load default preset on mount
 *
 * @package App\Http\Livewire\Products\Listing\Traits
 */
trait ProductListPresets
{
    public bool $showPresetModal = false;
    public string $newPresetName = '';
    public bool $newPresetIsDefault = false;

    #[Computed]
    public function savedPresets(): \Illuminate\Support\Collection
    {
        if (!auth()->check()) {
            return collect();
        }

        return UserFilterPreset::forUser(auth()->id())
            ->forContext('product_list')
            ->orderBy('name')
            ->get();
    }

    public function saveCurrentFiltersAsPreset(): void
    {
        if (!auth()->check() || empty(trim($this->newPresetName))) {
            return;
        }

        $filters = $this->getCurrentFilterState();

        if ($this->newPresetIsDefault) {
            UserFilterPreset::forUser(auth()->id())
                ->forContext('product_list')
                ->update(['is_default' => false]);
        }

        UserFilterPreset::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'name' => trim($this->newPresetName),
                'context' => 'product_list',
            ],
            [
                'filters' => $filters,
                'is_default' => $this->newPresetIsDefault,
            ]
        );

        $this->showPresetModal = false;
        $this->newPresetName = '';
        $this->newPresetIsDefault = false;

        unset($this->savedPresets);

        $this->dispatch('notify', type: 'success', message: 'Preset zapisany!');
    }

    public function applyPreset(int $presetId): void
    {
        if (!auth()->check()) {
            return;
        }

        $preset = UserFilterPreset::forUser(auth()->id())
            ->forContext('product_list')
            ->find($presetId);

        if (!$preset) {
            return;
        }

        $this->applyFilterState($preset->filters);
        $this->updateHasFilters();
        $this->resetPage();
    }

    public function deletePreset(int $presetId): void
    {
        if (!auth()->check()) {
            return;
        }

        UserFilterPreset::forUser(auth()->id())
            ->forContext('product_list')
            ->where('id', $presetId)
            ->delete();

        unset($this->savedPresets);

        $this->dispatch('notify', type: 'success', message: 'Preset usunięty.');
    }

    protected function getCurrentFilterState(): array
    {
        return [
            'search' => $this->search ?? '',
            'categoryFilter' => $this->categoryFilter ?? '',
            'statusFilter' => $this->statusFilter ?? 'all',
            'stockFilter' => $this->stockFilter ?? 'all',
            'productTypeFilter' => $this->productTypeFilter ?? 'all',
            'priceMin' => $this->priceMin ?? 0,
            'priceMax' => $this->priceMax ?? 10000,
            'integrationFilter' => $this->integrationFilter ?? 'all',
            'mediaFilter' => $this->mediaFilter ?? 'all',
            'priceGroupFilter' => $this->priceGroupFilter ?? '',
            'stockMin' => $this->stockMin ?? null,
            'stockMax' => $this->stockMax ?? null,
            'stockWarehouseFilter' => $this->stockWarehouseFilter ?? '',
        ];
    }

    protected function applyFilterState(array $filters): void
    {
        foreach ($filters as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function loadDefaultPresetOnMount(): void
    {
        if (!auth()->check()) {
            return;
        }

        $default = UserFilterPreset::forUser(auth()->id())
            ->forContext('product_list')
            ->where('is_default', true)
            ->first();

        if ($default) {
            $this->applyFilterState($default->filters);
        }
    }
}
