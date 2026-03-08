<?php

namespace App\Http\Livewire\Admin\Compatibility\Traits;

use App\Models\UserFilterPreset;
use Illuminate\Support\Collection;

/**
 * ManagesFilterPresets Trait
 *
 * Handles saving/loading filter presets using UserFilterPreset model.
 * Assumes the using class has filter properties from CompatibilityManagement
 * (searchPart, filterBrand, filterNoMatches, sortField, sortDirection, etc.)
 */
trait ManagesFilterPresets
{
    public bool $showPresetDropdown = false;
    public string $newPresetName = '';
    public ?int $activePresetId = null;

    protected string $presetContext = 'compatibility_management';

    public function getFilterState(): array
    {
        return [
            'searchPart' => $this->searchPart ?? '',
            'filterBrand' => $this->filterBrand ?? '',
            'filterNoMatches' => $this->filterNoMatches ?? false,
            'filterCategory' => $this->filterCategory ?? '',
            'filterShopAssignment' => $this->filterShopAssignment ?? '',
            'filterManufacturer' => $this->filterManufacturer ?? '',
            'filterCompatCountRange' => $this->filterCompatCountRange ?? '',
            'sortField' => $this->sortField ?? 'sku',
            'sortDirection' => $this->sortDirection ?? 'asc',
        ];
    }

    public function setFilterState(array $state): void
    {
        foreach ($state as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
        $this->resetPage();
    }

    public function savePreset(): void
    {
        $name = trim($this->newPresetName);

        if ($name === '') {
            $this->dispatch('flash-message', type: 'error', message: 'Nazwa presetu nie moze byc pusta');
            return;
        }

        $preset = UserFilterPreset::create([
            'user_id' => auth()->id(),
            'name' => $name,
            'context' => $this->presetContext,
            'filters' => $this->getFilterState(),
            'is_default' => false,
        ]);

        $this->activePresetId = $preset->id;
        $this->newPresetName = '';
        $this->showPresetDropdown = false;

        $this->dispatch('flash-message', type: 'success', message: 'Preset zapisany');
    }

    public function loadPreset(int $id): void
    {
        $preset = UserFilterPreset::forUser(auth()->id())
            ->forContext($this->presetContext)
            ->find($id);

        if (!$preset) {
            $this->dispatch('flash-message', type: 'error', message: 'Preset nie znaleziony');
            return;
        }

        $this->setFilterState($preset->filters);
        $this->activePresetId = $id;

        $this->dispatch('flash-message', type: 'success', message: 'Preset zaladowany');
    }

    public function deletePreset(int $id): void
    {
        $preset = UserFilterPreset::forUser(auth()->id())
            ->forContext($this->presetContext)
            ->find($id);

        if (!$preset) {
            return;
        }

        $preset->delete();

        if ($this->activePresetId === $id) {
            $this->activePresetId = null;
        }

        $this->dispatch('flash-message', type: 'success', message: 'Preset usuniety');
    }

    public function setDefaultPreset(int $id): void
    {
        $preset = UserFilterPreset::forUser(auth()->id())
            ->forContext($this->presetContext)
            ->find($id);

        if (!$preset) {
            return;
        }

        // Unset all other defaults for this user+context
        UserFilterPreset::forUser(auth()->id())
            ->forContext($this->presetContext)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        $preset->update(['is_default' => true]);

        $this->dispatch('flash-message', type: 'success', message: 'Preset ustawiony jako domyslny');
    }

    public function getPresetsProperty(): Collection
    {
        return UserFilterPreset::forUser(auth()->id())
            ->forContext($this->presetContext)
            ->orderBy('name')
            ->get();
    }

    public function loadDefaultPreset(): void
    {
        $defaultPreset = UserFilterPreset::forUser(auth()->id())
            ->forContext($this->presetContext)
            ->where('is_default', true)
            ->first();

        if ($defaultPreset) {
            $this->loadPreset($defaultPreset->id);
        }
    }
}
