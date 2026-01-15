<?php

namespace App\Http\Livewire\Admin\Variants;

use App\Http\Livewire\Admin\Variants\Traits\AttributeValueBulkOperations;
use App\Models\AttributeType;
use App\Models\AttributeValue;
use App\Models\PrestaShopShop;
use App\Services\Product\AttributeManager;
use App\Services\Product\AttributeUsageService;
use App\Services\PrestaShop\PrestaShopAttributeSyncService;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

/**
 * AttributeValueManager - CRUD for AttributeValue with PERFORMANCE OPTIMIZED queries
 *
 * PERFORMANCE FIX (2025-12-11):
 * - Single consolidated query for attributeType + values + usageStats
 * - Cached computed properties (persist: true)
 * - Removed wire:model.live from checkboxes (use wire:model instead)
 * - Eager loading prestashopMappings.shop
 *
 * @since ETAP_05b FAZA 5 (2025-12-11)
 */
class AttributeValueManager extends Component
{
    use AttributeValueBulkOperations;

    public bool $showModal = false;
    public bool $showEditForm = false;
    public bool $showProductsModal = false;
    public bool $showSyncModal = false;
    public ?int $attributeTypeId = null;
    public ?int $editingValueId = null;
    public ?int $selectedValueIdForProducts = null;
    public ?int $selectedValueIdForSync = null;
    public array $formData = [
        'code' => '', 'label' => '', 'color_hex' => '',
        'auto_prefix' => '', 'auto_prefix_enabled' => false,
        'auto_suffix' => '', 'auto_suffix_enabled' => false,
        'position' => 0, 'is_active' => true,
    ];
    #[Url(as: 'q')] public string $search = '';
    #[Url(as: 'filter')] public string $filterStatus = 'all';
    #[Url(as: 'sort')] public string $sortField = 'position';
    #[Url(as: 'dir')] public string $sortDirection = 'asc';
    public array $selectedValues = [];
    public bool $selectAll = false;

    // NOTE: Cannot use private properties for cache - they don't persist between Livewire requests
    // Using on-demand loading with memoization within single request cycle

    private ?AttributeManager $attributeManager = null;
    private ?AttributeUsageService $usageService = null;
    private ?PrestaShopAttributeSyncService $syncService = null;

    protected function getAttributeManager(): AttributeManager { return $this->attributeManager ??= app(AttributeManager::class); }
    protected function getUsageService(): AttributeUsageService { return $this->usageService ??= app(AttributeUsageService::class); }
    protected function getSyncService(): PrestaShopAttributeSyncService { return $this->syncService ??= app(PrestaShopAttributeSyncService::class); }

    #[On('open-attribute-value-manager')]
    public function open(int $typeId): void
    {
        $this->attributeTypeId = $typeId;
        $this->showModal = true;
        $this->resetFilters();
    }

    #[On('color-updated')]
    public function onColorUpdated(string $color): void { $this->formData['color_hex'] = $color; }

    /**
     * COMPUTED: Get attribute type (memoized per request via Computed)
     */
    #[Computed]
    public function attributeType(): ?AttributeType
    {
        return $this->attributeTypeId ? AttributeType::find($this->attributeTypeId) : null;
    }

    /**
     * COMPUTED: Get values with filters applied
     */
    #[Computed]
    public function values(): Collection
    {
        if (!$this->attributeTypeId) {
            return collect([]);
        }

        $query = AttributeValue::where('attribute_type_id', $this->attributeTypeId)
            ->with(['prestashopMappings.shop']);

        if ($this->search) {
            $search = $this->search;
            $query->where(fn($q) => $q->where('label', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
        }

        if ($this->filterStatus === 'used') {
            $query->has('variantAttributes');
        } elseif ($this->filterStatus === 'unused') {
            $query->doesntHave('variantAttributes');
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->get();
    }

    /**
     * COMPUTED: Get usage stats
     */
    #[Computed]
    public function usageStats(): Collection
    {
        return $this->attributeTypeId
            ? $this->getUsageService()->getUsageStatsForType($this->attributeTypeId)
            : collect([]);
    }

    /**
     * COMPUTED: Get unused values count
     */
    #[Computed]
    public function unusedValuesCount(): int
    {
        return $this->usageStats->filter(fn($s) => $s['products_count'] === 0)->count();
    }

    /**
     * COMPUTED: Check if current type is color type
     */
    #[Computed]
    public function isColorType(): bool
    {
        return $this->attributeType?->display_type === 'color';
    }

    // Compatibility getters for templates/traits
    public function getAttributeType(): ?AttributeType { return $this->attributeType; }
    public function getValues(): Collection { return $this->values; }
    public function getUsageStats(): Collection { return $this->usageStats; }
    public function getUnusedValuesCount(): int { return $this->unusedValuesCount; }
    public function getIsColorType(): bool { return $this->isColorType; }

    /**
     * Clear computed caches after data mutations
     */
    public function refreshValues(): void
    {
        unset($this->values, $this->usageStats, $this->unusedValuesCount);
    }

    /**
     * Full refresh including type
     */
    public function loadData(): void
    {
        unset($this->attributeType, $this->values, $this->usageStats, $this->unusedValuesCount, $this->isColorType);
    }

    #[Computed(persist: true)]
    public function activeShops(): Collection { return PrestaShopShop::where('is_active', true)->get(); }

    #[Computed]
    public function productsUsingValue(): Collection
    {
        return $this->selectedValueIdForProducts ? $this->getUsageService()->getProductsUsingAttributeValue($this->selectedValueIdForProducts) : collect([]);
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editingValueId = null;
        $this->showEditForm = true;
    }

    public function openEditModal(int $valueId): void
    {
        $value = AttributeValue::find($valueId);
        if (!$value) { $this->addError('edit', 'Wartosc nie znaleziona'); return; }
        $this->editingValueId = $valueId;
        $this->formData = [
            'code' => $value->code, 'label' => $value->label, 'color_hex' => $value->color_hex ?? '',
            'auto_prefix' => $value->auto_prefix ?? '', 'auto_prefix_enabled' => $value->auto_prefix_enabled ?? false,
            'auto_suffix' => $value->auto_suffix ?? '', 'auto_suffix_enabled' => $value->auto_suffix_enabled ?? false,
            'position' => $value->position, 'is_active' => $value->is_active,
        ];
        $this->showEditForm = true;
    }

    public function save(): void
    {
        $rules = ['formData.code' => 'required|string|max:50|regex:/^[a-z0-9_-]+$/', 'formData.label' => 'required|string|max:100', 'formData.position' => 'required|integer|min:0'];
        if ($this->getIsColorType()) $rules['formData.color_hex'] = 'nullable|regex:/^#[0-9A-Fa-f]{6}$/';
        $this->validate($rules, [
            'formData.code.required' => 'Kod jest wymagany', 'formData.code.regex' => 'Kod: male litery, cyfry, myslniki, podkreslenia',
            'formData.label.required' => 'Etykieta jest wymagana', 'formData.color_hex.regex' => 'Kolor musi byc w formacie #RRGGBB',
        ]);
        try {
            if ($this->editingValueId) {
                $this->getAttributeManager()->updateAttributeValue(AttributeValue::find($this->editingValueId), $this->formData);
                session()->flash('message', 'Wartosc zaktualizowana');
            } else {
                $this->getAttributeManager()->createAttributeValue($this->attributeTypeId, $this->formData);
                session()->flash('message', 'Wartosc utworzona');
            }
            $this->cancelEdit();
            $this->loadData(); // PERFORMANCE: Refresh cache after mutation
        } catch (\Exception $e) { $this->addError('save', 'Blad: ' . $e->getMessage()); }
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
        $this->editingValueId = null;
        $this->showEditForm = false;
        $this->resetErrorBag();
    }

    public function delete(int $valueId): void
    {
        try {
            $value = AttributeValue::find($valueId);
            if ($value) {
                $this->getAttributeManager()->deleteAttributeValue($value);
                session()->flash('message', 'Wartosc usunieta');
                $this->loadData(); // PERFORMANCE: Refresh cache after mutation
            }
        } catch (\Exception $e) { $this->addError('delete', 'Blad: ' . $e->getMessage()); }
    }

    public function reorder(array $valueIds): void
    {
        try {
            $this->getAttributeManager()->reorderAttributeValues($this->attributeTypeId, $valueIds);
            session()->flash('message', 'Kolejnosc zaktualizowana');
        } catch (\Exception $e) { $this->addError('reorder', 'Blad: ' . $e->getMessage()); }
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->attributeTypeId = null;
        $this->resetForm();
        $this->resetErrorBag();
        $this->dispatch('attribute-values-updated');
    }

    public function openProductsModal(int $valueId): void { $this->selectedValueIdForProducts = $valueId; $this->showProductsModal = true; }
    public function closeProductsModal(): void { $this->showProductsModal = false; $this->selectedValueIdForProducts = null; }
    public function openSyncModal(int $valueId): void { $this->selectedValueIdForSync = $valueId; $this->showSyncModal = true; }
    public function closeSyncModal(): void { $this->showSyncModal = false; $this->selectedValueIdForSync = null; }

    public function syncValueToShop(int $valueId, int $shopId): void
    {
        try {
            $value = AttributeValue::find($valueId);
            if (!$value) { $this->addError('sync', 'Wartosc nie znaleziona'); return; }
            $result = $this->getSyncService()->syncAttributeValue($value, $shopId);
            session()->flash('message', $result['status'] === 'synced' ? 'Synchronizacja zakonczona' : 'Synchronizacja: ' . ($result['message'] ?? 'Status nieznany'));
            $this->closeSyncModal();
            $this->dispatch('attribute-values-updated');
        } catch (\Exception $e) { $this->addError('sync', 'Blad synchronizacji: ' . $e->getMessage()); }
    }

    protected function resetForm(): void
    {
        $this->formData = ['code' => '', 'label' => '', 'color_hex' => '', 'auto_prefix' => '', 'auto_prefix_enabled' => false, 'auto_suffix' => '', 'auto_suffix_enabled' => false, 'position' => 0, 'is_active' => true];
    }

    public function render() { return view('livewire.admin.variants.attribute-value-manager'); }
}
