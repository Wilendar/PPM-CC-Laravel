<?php

namespace App\Http\Livewire\Admin\Variants;

use App\Models\AttributeType;
use App\Models\AttributeValue;
use App\Models\PrestaShopShop;
use App\Services\Product\AttributeManager;
use App\Services\PrestaShop\PrestaShopAttributeSyncService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

/**
 * AttributeValueManager Livewire Component
 *
 * CRUD management for AttributeValue (values per attribute type)
 *
 * FEATURES:
 * - List values for selected attribute type
 * - Drag & drop reordering (Alpine.js Sortable)
 * - Create/Edit modal (code, label, color_hex for color types)
 * - Delete with confirmation (check variants using this value)
 * - AttributeColorPicker integration (Phase 3)
 * - PrestaShop sync status per value per shop (Phase 5)
 * - Products usage tracking ("Products Using This Value")
 * - Sync operations (Create in PS, Verify, Re-sync)
 *
 * COMPLIANCE:
 * - Livewire 3.x patterns (dispatch, #[Computed], #[On], wire:model.live)
 * - wire:key for all @foreach loops
 * - AttributeManager service for ALL business logic
 * - NO inline styles (CSS classes only, EXCEPT color preview swatch)
 * - ~250 lines target (Phase 5 enhanced)
 *
 * USAGE:
 * Triggered by: $this->dispatch('open-attribute-value-manager', typeId: X)
 *
 * @package App\Http\Livewire\Admin\Variants
 * @version 2.0
 * @since ETAP_05b Phase 5 (2025-10-28)
 */
class AttributeValueManager extends Component
{
    /*
    |--------------------------------------------------------------------------
    | PROPERTIES
    |--------------------------------------------------------------------------
    */

    public bool $showModal = false;
    public ?int $attributeTypeId = null;
    public ?int $editingValueId = null;

    public array $formData = [
        'code' => '',
        'label' => '',
        'color_hex' => '',
        'position' => 0,
        'is_active' => true,
    ];

    // NEW Phase 5: Products Usage Modal
    public bool $showProductsModal = false;
    public ?int $selectedValueIdForProducts = null;

    // NEW Phase 5: Sync Status Modal
    public bool $showSyncModal = false;
    public ?int $selectedValueIdForSync = null;

    // NEW: Show edit form flag
    public bool $showEditForm = false;

    /*
    |--------------------------------------------------------------------------
    | DEPENDENCY INJECTION
    |--------------------------------------------------------------------------
    */

    private ?AttributeManager $attributeManager = null;
    private ?PrestaShopAttributeSyncService $syncService = null;

    protected function getAttributeManager(): AttributeManager
    {
        if (!$this->attributeManager) {
            $this->attributeManager = app(AttributeManager::class);
        }
        return $this->attributeManager;
    }

    protected function getSyncService(): PrestaShopAttributeSyncService
    {
        if (!$this->syncService) {
            $this->syncService = app(PrestaShopAttributeSyncService::class);
        }
        return $this->syncService;
    }

    /*
    |--------------------------------------------------------------------------
    | EVENT LISTENERS
    |--------------------------------------------------------------------------
    */

    /**
     * Listen for open event
     */
    #[On('open-attribute-value-manager')]
    public function open(int $typeId): void
    {
        $this->attributeTypeId = $typeId;
        $this->showModal = true;
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function attributeType(): ?AttributeType
    {
        return $this->attributeTypeId
            ? AttributeType::find($this->attributeTypeId)
            : null;
    }

    #[Computed]
    public function values(): Collection
    {
        return $this->attributeTypeId
            ? AttributeValue::where('attribute_type_id', $this->attributeTypeId)
                ->orderBy('position')
                ->get()
            : collect([]);
    }

    #[Computed]
    public function isColorType(): bool
    {
        return $this->attributeType?->display_type === 'color';
    }

    /**
     * NEW Phase 5: Get products using selected attribute value
     */
    #[Computed]
    public function productsUsingValue(): Collection
    {
        if (!$this->selectedValueIdForProducts) {
            return collect([]);
        }

        return $this->getAttributeManager()
            ->getProductsUsingAttributeValue($this->selectedValueIdForProducts);
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIONS - CRUD
    |--------------------------------------------------------------------------
    */

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editingValueId = null;
        $this->showEditForm = true;
    }

    public function openEditModal(int $valueId): void
    {
        $value = AttributeValue::find($valueId);
        if (!$value) {
            $this->addError('edit', 'Attribute value not found');
            return;
        }

        $this->editingValueId = $valueId;
        $this->formData = [
            'code' => $value->code,
            'label' => $value->label,
            'color_hex' => $value->color_hex ?? '',
            'position' => $value->position,
            'is_active' => $value->is_active,
        ];
        $this->showEditForm = true;
    }

    public function save(): void
    {
        $rules = [
            'formData.code' => 'required|string|max:50|regex:/^[a-z0-9_-]+$/',
            'formData.label' => 'required|string|max:100',
            'formData.position' => 'required|integer|min:0',
        ];

        // Add color_hex validation only if color type
        if ($this->isColorType) {
            $rules['formData.color_hex'] = 'nullable|regex:/^#[0-9A-Fa-f]{6}$/';
        }

        $this->validate($rules, [
            'formData.code.required' => 'Kod jest wymagany',
            'formData.code.regex' => 'Kod moze zawierac tylko male litery, cyfry, myslniki i podkreslenia',
            'formData.label.required' => 'Etykieta jest wymagana',
            'formData.color_hex.regex' => 'Kolor musi byc w formacie hex (#RRGGBB)',
        ]);

        try {
            if ($this->editingValueId) {
                $value = AttributeValue::find($this->editingValueId);
                $this->getAttributeManager()->updateAttributeValue($value, $this->formData);
                session()->flash('message', 'Wartosc zaktualizowana');
            } else {
                $this->getAttributeManager()->createAttributeValue(
                    $this->attributeTypeId,
                    $this->formData
                );
                session()->flash('message', 'Wartosc utworzona');
            }

            $this->resetForm();
            $this->editingValueId = null;
            $this->showEditForm = false;

        } catch (\Exception $e) {
            $this->addError('save', 'Blad: ' . $e->getMessage());
        }
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
            if (!$value) {
                $this->addError('delete', 'Attribute value not found');
                return;
            }

            $this->getAttributeManager()->deleteAttributeValue($value);
            session()->flash('message', 'Wartosc usunieta');

        } catch (\Exception $e) {
            $this->addError('delete', 'Blad: ' . $e->getMessage());
        }
    }

    public function reorder(array $valueIds): void
    {
        try {
            $this->getAttributeManager()->reorderAttributeValues(
                $this->attributeTypeId,
                $valueIds
            );
            session()->flash('message', 'Kolejnosc zaktualizowana');

        } catch (\Exception $e) {
            $this->addError('reorder', 'Blad: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIONS - MODALS
    |--------------------------------------------------------------------------
    */

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->attributeTypeId = null;
        $this->resetForm();
        $this->resetErrorBag();
        $this->dispatch('attribute-values-updated');
    }

    /**
     * NEW Phase 5: Open products usage modal
     */
    public function openProductsModal(int $valueId): void
    {
        $this->selectedValueIdForProducts = $valueId;
        $this->showProductsModal = true;
    }

    /**
     * NEW Phase 5: Close products usage modal
     */
    public function closeProductsModal(): void
    {
        $this->showProductsModal = false;
        $this->selectedValueIdForProducts = null;
    }

    /**
     * NEW Phase 5: Open sync status modal
     */
    public function openSyncModal(int $valueId): void
    {
        $this->selectedValueIdForSync = $valueId;
        $this->showSyncModal = true;
    }

    /**
     * NEW Phase 5: Close sync status modal
     */
    public function closeSyncModal(): void
    {
        $this->showSyncModal = false;
        $this->selectedValueIdForSync = null;
    }

    /*
    |--------------------------------------------------------------------------
    | PRESTASHOP SYNC METHODS (NEW Phase 5)
    |--------------------------------------------------------------------------
    */

    /**
     * Get PrestaShop sync status for attribute value
     *
     * @param int $valueId Attribute value ID
     * @return array Shop sync status array
     */
    public function getSyncStatusForValue(int $valueId): array
    {
        $shops = PrestaShopShop::where('is_active', true)->get();
        $status = [];

        foreach ($shops as $shop) {
            $mapping = DB::table('prestashop_attribute_value_mapping')
                ->where('attribute_value_id', $valueId)
                ->where('prestashop_shop_id', $shop->id)
                ->first();

            $status[$shop->id] = [
                'shop_name' => $shop->name,
                'status' => $mapping ? ($mapping->sync_status ?? 'pending') : 'missing',
                'ps_id' => $mapping ? ($mapping->prestashop_attribute_id ?? null) : null,
                'last_sync' => ($mapping && $mapping->last_synced_at) ? \Carbon\Carbon::parse($mapping->last_synced_at) : null,
            ];
        }

        return $status;
    }

    /**
     * Get products count for attribute value
     *
     * @param int $valueId Attribute value ID
     * @return int Products count
     */
    public function getProductsCountForValue(int $valueId): int
    {
        return $this->getAttributeManager()
            ->getProductsUsingAttributeValue($valueId)
            ->count();
    }

    /**
     * Sync attribute value to PrestaShop shop
     *
     * @param int $valueId Attribute value ID
     * @param int $shopId PrestaShop shop ID
     * @return void
     */
    public function syncValueToShop(int $valueId, int $shopId): void
    {
        try {
            $value = AttributeValue::find($valueId);
            if (!$value) {
                $this->addError('sync', 'Attribute value not found');
                return;
            }

            // Trigger sync via service
            $result = $this->getSyncService()->syncAttributeValue($value, $shopId);

            if ($result['status'] === 'synced') {
                session()->flash('message', 'Synchronizacja zakonczona pomyslnie');
            } else {
                session()->flash('message', 'Synchronizacja: ' . ($result['message'] ?? 'Status nieznany'));
            }

            $this->closeSyncModal();
            $this->dispatch('attribute-values-updated');

        } catch (\Exception $e) {
            $this->addError('sync', 'Blad synchronizacji: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Listen for color picker updates (nested Livewire component)
     */
    #[On('color-updated')]
    public function onColorUpdated(string $color): void
    {
        $this->formData['color_hex'] = $color;
    }

    protected function resetForm(): void
    {
        $this->formData = [
            'code' => '',
            'label' => '',
            'color_hex' => '',
            'position' => 0,
            'is_active' => true,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        return view('livewire.admin.variants.attribute-value-manager');
    }
}
