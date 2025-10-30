<?php

namespace App\Http\Livewire\Admin\Variants;

use App\Models\AttributeType;
use App\Models\PrestaShopShop;
use App\Services\Product\AttributeManager;
use App\Services\PrestaShop\PrestaShopAttributeSyncService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Computed;

/**
 * AttributeSystemManager - ETAP_05b Phase 4
 * Enhanced AttributeType CRUD with PrestaShop sync status
 * Search/filter + sync badges + detailed sync modal
 * @since 2025-10-28
 */
class AttributeSystemManager extends Component
{
    // PROPERTIES

    /** @var bool Modal visibility */
    public bool $showModal = false;

    /** @var int|null Editing type ID (null = create mode) */
    public ?int $editingTypeId = null;

    /** @var array Form data */
    public array $formData = [
        'name' => '',
        'code' => '',
        'display_type' => 'dropdown',
        'position' => 0,
        'is_active' => true,
    ];

    /** @var bool Products usage modal visibility */
    public bool $showProductsModal = false;

    /** @var int|null Selected type ID for products modal */
    public ?int $selectedTypeIdForProducts = null;

    /** @var bool Sync status modal visibility */
    public bool $showSyncModal = false;

    /** @var int|null Selected type ID for sync modal */
    public ?int $selectedTypeIdForSync = null;

    /** @var string Search query */
    public string $searchQuery = '';

    /** @var string Status filter (all, active, inactive) */
    public string $statusFilter = 'all';

    /** @var string Sync filter (all, synced, pending, missing) */
    public string $syncFilter = 'all';

    // DEPENDENCY INJECTION

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

    // COMPUTED PROPERTIES

    /**
     * Get filtered attribute types
     */
    #[Computed]
    public function attributeTypes(): Collection
    {
        $query = AttributeType::with('values')
            ->withCount('values')
            ->ordered();

        // Search filter
        if ($this->searchQuery) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->searchQuery . '%')
                  ->orWhere('code', 'like', '%' . $this->searchQuery . '%');
            });
        }

        // Status filter
        if ($this->statusFilter !== 'all') {
            $query->where('is_active', $this->statusFilter === 'active');
        }

        return $query->get();
    }

    /**
     * Get products using selected attribute type
     */
    #[Computed]
    public function productsUsingType(): Collection
    {
        if (!$this->selectedTypeIdForProducts) {
            return collect([]);
        }

        return $this->getAttributeManager()
            ->getProductsUsingAttributeType($this->selectedTypeIdForProducts);
    }

    // ACTIONS - CRUD

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editingTypeId = null;
        $this->showModal = true;
    }

    public function openEditModal(int $typeId): void
    {
        $type = AttributeType::find($typeId);
        if (!$type) {
            $this->addError('edit', 'Attribute type not found');
            return;
        }

        $this->editingTypeId = $typeId;
        $this->formData = [
            'name' => $type->name,
            'code' => $type->code,
            'display_type' => $type->display_type,
            'position' => $type->position,
            'is_active' => $type->is_active,
        ];
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'formData.name' => 'required|string|max:100',
            'formData.code' => 'required|string|max:50|regex:/^[a-z_]+$/',
            'formData.display_type' => 'required|in:dropdown,radio,color,button',
            'formData.position' => 'required|integer|min:0',
        ], [
            'formData.name.required' => 'Nazwa jest wymagana',
            'formData.code.required' => 'Kod jest wymagany',
            'formData.code.regex' => 'Kod moze zawierac tylko male litery i podkreslenia',
        ]);

        try {
            if ($this->editingTypeId) {
                $type = AttributeType::find($this->editingTypeId);
                $this->getAttributeManager()->updateAttributeType($type, $this->formData);
                session()->flash('message', 'Grupa atrybutow zaktualizowana');
            } else {
                $this->getAttributeManager()->createAttributeType($this->formData);
                session()->flash('message', 'Grupa atrybutow utworzona');
            }

            $this->closeModal();
            $this->dispatch('attribute-types-updated');

        } catch (\Exception $e) {
            $this->addError('save', 'Blad: ' . $e->getMessage());
        }
    }

    public function delete(int $typeId): void
    {
        try {
            $type = AttributeType::find($typeId);
            if (!$type) {
                $this->addError('delete', 'Attribute type not found');
                return;
            }

            $productsCount = $this->getAttributeManager()
                ->getProductsUsingAttributeType($typeId)
                ->count();

            if ($productsCount > 0) {
                $this->addError('delete', "Nie mozna usunac - {$productsCount} produktow uzywa tej grupy");
                return;
            }

            $this->getAttributeManager()->deleteAttributeType($type, false);
            session()->flash('message', 'Grupa atrybutow usunieta');
            $this->dispatch('attribute-types-updated');

        } catch (\Exception $e) {
            $this->addError('delete', 'Blad: ' . $e->getMessage());
        }
    }

    // ACTIONS - MODALS

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
        $this->resetErrorBag();
    }

    public function showProductsUsing(int $typeId): void
    {
        $this->selectedTypeIdForProducts = $typeId;
        $this->showProductsModal = true;
    }

    public function closeProductsModal(): void
    {
        $this->showProductsModal = false;
        $this->selectedTypeIdForProducts = null;
    }

    public function openSyncModal(int $typeId): void
    {
        $this->selectedTypeIdForSync = $typeId;
        $this->showSyncModal = true;
    }

    public function closeSyncModal(): void
    {
        $this->showSyncModal = false;
        $this->selectedTypeIdForSync = null;
    }

    public function manageValues(int $typeId): void
    {
        $this->dispatch('open-attribute-value-manager', typeId: $typeId);
    }

    // PRESTASHOP SYNC METHODS

    /**
     * Get PrestaShop sync status for attribute type
     */
    public function getSyncStatusForType(int $typeId): array
    {
        $shops = PrestaShopShop::where('is_active', true)->get();
        $status = [];

        foreach ($shops as $shop) {
            $mapping = DB::table('prestashop_attribute_group_mapping')
                ->where('attribute_type_id', $typeId)
                ->where('prestashop_shop_id', $shop->id)
                ->first();

            $status[$shop->id] = [
                'shop_name' => $shop->name,
                'status' => $mapping ? ($mapping->sync_status ?? 'pending') : 'missing',
                'ps_id' => $mapping->prestashop_attribute_group_id ?? null,
                'last_sync' => $mapping->last_synced_at ?? null,
            ];
        }

        return $status;
    }

    /**
     * Sync attribute type to PrestaShop shop
     */
    public function syncToShop(int $typeId, int $shopId): void
    {
        try {
            $result = $this->getSyncService()->syncAttributeGroup($typeId, $shopId);

            if ($result['status'] === 'synced') {
                session()->flash('message', 'Synchronizacja zakonczona pomyslnie');
            } else {
                session()->flash('error', 'Synchronizacja: ' . $result['message']);
            }

            $this->dispatch('attribute-types-updated');

        } catch (\Exception $e) {
            $this->addError('sync', 'Blad synchronizacji: ' . $e->getMessage());
        }
    }

    /**
     * Get products count for attribute type
     */
    public function getProductsCountForType(int $typeId): int
    {
        return $this->getAttributeManager()
            ->getProductsUsingAttributeType($typeId)
            ->count();
    }

    // HELPERS

    protected function resetForm(): void
    {
        $this->formData = [
            'name' => '',
            'code' => '',
            'display_type' => 'dropdown',
            'position' => 0,
            'is_active' => true,
        ];
    }

    // RENDER

    public function render()
    {
        return view('livewire.admin.variants.attribute-system-manager')
            ->layout('layouts.admin', [
                'title' => 'System Atrybutów - PPM'
            ]);
    }
}
