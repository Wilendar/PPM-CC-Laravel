<?php

namespace App\Http\Livewire\Admin\Suppliers\Traits;

use App\Models\BusinessPartner;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

/**
 * BusinessPartnerCrudTrait - CRUD, lista, statystyki, logo upload
 *
 * @property string $activeTab
 * @property int|null $selectedEntityId
 * @property string $entitySearch
 * @property string $statusFilter
 */
trait BusinessPartnerCrudTrait
{
    public bool $showCreateModal = false;
    public bool $showDeleteConfirm = false;
    public ?int $deleteId = null;
    public ?int $editingEntityId = null;
    public $logoUpload = null;

    public array $formData = [
        'name' => '',
        'company_name' => '',
        'address' => '',
        'postal_code' => '',
        'city' => '',
        'country' => 'Polska',
        'email' => '',
        'phone' => '',
        'website' => '',
        'is_active' => true,
        'sort_order' => 0,
        'description' => '',
        'short_description' => '',
    ];

    private const RELATION_MAP = [
        'supplier' => 'productsAsSupplier',
        'manufacturer' => 'productsAsManufacturer',
        'importer' => 'productsAsImporter',
    ];

    #[Computed]
    public function entities()
    {
        if ($this->activeTab === 'brak') {
            return collect();
        }

        $query = BusinessPartner::query()
            ->where('type', $this->activeTab);

        if ($this->entitySearch !== '') {
            $query->where(function ($q) {
                $q->where('name', 'LIKE', '%' . $this->entitySearch . '%')
                    ->orWhere('company_name', 'LIKE', '%' . $this->entitySearch . '%')
                    ->orWhere('email', 'LIKE', '%' . $this->entitySearch . '%')
                    ->orWhere('city', 'LIKE', '%' . $this->entitySearch . '%');
            });
        }

        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        $relation = self::RELATION_MAP[$this->activeTab] ?? null;
        if ($relation) {
            $query->withCount($relation);
        }

        return $query->orderBy('sort_order')->orderBy('name')->get();
    }

    #[Computed]
    public function selectedEntity(): ?BusinessPartner
    {
        return $this->selectedEntityId
            ? BusinessPartner::find($this->selectedEntityId)
            : null;
    }

    #[Computed]
    public function entityStats(): array
    {
        if ($this->activeTab === 'brak') {
            return $this->getBrakStats();
        }

        $baseQuery = BusinessPartner::where('type', $this->activeTab);
        $relation = self::RELATION_MAP[$this->activeTab] ?? null;

        return [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
            'inactive' => (clone $baseQuery)->where('is_active', false)->count(),
            'with_products' => $relation
                ? (clone $baseQuery)->whereHas($relation)->count()
                : 0,
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editingEntityId = null;
        $this->showCreateModal = true;
    }

    public function save(): void
    {
        $this->validate($this->getValidationRules());

        $data = $this->formData;
        if ($this->activeTab !== 'brak') {
            $data['type'] = $this->activeTab;
        }
        $data['slug'] = Str::slug($data['name']);
        $data['code'] = Str::upper(Str::slug($data['name'], '_'));

        try {
            $entity = BusinessPartner::create($data);

            if ($this->logoUpload) {
                $this->handleLogoUpload($entity);
            }

            $this->closeCreateModal();
            $this->selectedEntityId = $entity->id;
            $this->dispatch('flash-message', type: 'success', message: "Partner '{$entity->name}' zostal utworzony");
            Log::info('[BUSINESS_PARTNER] Created', ['id' => $entity->id, 'type' => $entity->type]);
        } catch (\Exception $e) {
            Log::error('[BUSINESS_PARTNER] Create failed', ['error' => $e->getMessage()]);
            $this->dispatch('flash-message', type: 'error', message: 'Blad tworzenia: ' . $e->getMessage());
        }
    }

    public function loadEntityForEdit(): void
    {
        $entity = $this->selectedEntity;
        if (! $entity) {
            return;
        }

        $this->editingEntityId = $entity->id;
        $this->formData = [
            'name' => $entity->name ?? '',
            'company_name' => $entity->company_name ?? '',
            'address' => $entity->address ?? '',
            'postal_code' => $entity->postal_code ?? '',
            'city' => $entity->city ?? '',
            'country' => $entity->country ?? 'Polska',
            'email' => $entity->email ?? '',
            'phone' => $entity->phone ?? '',
            'website' => $entity->website ?? '',
            'is_active' => $entity->is_active ?? true,
            'sort_order' => $entity->sort_order ?? 0,
            'description' => $entity->description ?? '',
            'short_description' => $entity->short_description ?? '',
        ];
        $this->logoUpload = null;
    }

    public function saveEntityDetails(): void
    {
        $this->validate($this->getValidationRules());

        $entity = BusinessPartner::find($this->editingEntityId);
        if (! $entity) {
            $this->dispatch('flash-message', type: 'error', message: 'Partner nie znaleziony');
            return;
        }

        try {
            $entity->update($this->formData);

            if ($this->logoUpload) {
                $this->handleLogoUpload($entity);
            }

            $this->editingEntityId = null;
            $this->dispatch('flash-message', type: 'success', message: "Partner '{$entity->name}' zaktualizowany");
            Log::info('[BUSINESS_PARTNER] Updated', ['id' => $entity->id]);
        } catch (\Exception $e) {
            Log::error('[BUSINESS_PARTNER] Update failed', ['id' => $this->editingEntityId, 'error' => $e->getMessage()]);
            $this->dispatch('flash-message', type: 'error', message: 'Blad aktualizacji: ' . $e->getMessage());
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->showDeleteConfirm = true;
    }

    public function delete(): void
    {
        if (! $this->deleteId) {
            return;
        }

        $entity = BusinessPartner::find($this->deleteId);
        if (! $entity) {
            $this->showDeleteConfirm = false;
            $this->deleteId = null;
            return;
        }

        $name = $entity->name;
        try {
            $entity->delete();

            if ($this->selectedEntityId === $this->deleteId) {
                $this->selectedEntityId = null;
            }

            $this->showDeleteConfirm = false;
            $this->deleteId = null;
            $this->dispatch('flash-message', type: 'success', message: "Partner '{$name}' usuniety");
            Log::info('[BUSINESS_PARTNER] Deleted', ['id' => $entity->id, 'name' => $name]);
        } catch (\Exception $e) {
            Log::error('[BUSINESS_PARTNER] Delete failed', ['id' => $this->deleteId, 'error' => $e->getMessage()]);
            $this->dispatch('flash-message', type: 'error', message: 'Blad usuwania: ' . $e->getMessage());
        }
    }

    public function cancelEdit(): void
    {
        $this->editingEntityId = null;
        $this->resetForm();
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->formData = [
            'name' => '',
            'company_name' => '',
            'address' => '',
            'postal_code' => '',
            'city' => '',
            'country' => 'Polska',
            'email' => '',
            'phone' => '',
            'website' => '',
            'is_active' => true,
            'sort_order' => 0,
            'description' => '',
            'short_description' => '',
        ];
        $this->logoUpload = null;
        $this->resetValidation();
    }

    protected function handleLogoUpload(BusinessPartner $entity): void
    {
        try {
            $extension = $this->logoUpload->getClientOriginalExtension();
            $filename = 'business-partners/' . Str::slug($entity->name) . '-' . $entity->id . '.' . $extension;

            if ($entity->logo_path && Storage::disk('public')->exists($entity->logo_path)) {
                Storage::disk('public')->delete($entity->logo_path);
            }

            $this->logoUpload->storeAs('public', $filename);

            $fullPath = Storage::disk('public')->path($filename);
            @chmod(dirname($fullPath), 0755);
            @chmod($fullPath, 0644);

            $entity->update(['logo_path' => $filename]);
            Log::info('[BUSINESS_PARTNER] Logo uploaded', ['id' => $entity->id, 'filename' => $filename]);
        } catch (\Exception $e) {
            Log::error('[BUSINESS_PARTNER] Logo upload failed', ['id' => $entity->id, 'error' => $e->getMessage()]);
            $this->dispatch('flash-message', type: 'warning', message: 'Logo nie zapisane: ' . $e->getMessage());
        }
    }

    protected function getValidationRules(): array
    {
        return [
            'formData.name' => 'required|string|max:255',
            'formData.company_name' => 'nullable|string|max:255',
            'formData.email' => 'nullable|email|max:255',
            'formData.phone' => 'nullable|string|max:50',
            'formData.postal_code' => 'nullable|string|max:20',
            'formData.city' => 'nullable|string|max:100',
            'formData.country' => 'nullable|string|max:100',
            'formData.address' => 'nullable|string|max:500',
            'formData.website' => 'nullable|url|max:255',
            'formData.is_active' => 'boolean',
            'formData.sort_order' => 'integer|min:0',
            'formData.description' => 'nullable|string|max:5000',
            'formData.short_description' => 'nullable|string|max:1000',
            'logoUpload' => 'nullable|image|max:2048',
        ];
    }

    protected function getBrakStats(): array
    {
        return [
            'total' => \App\Models\Product::where(function ($q) {
                $q->whereNull('supplier_id')
                    ->orWhereNull('manufacturer_id')
                    ->orWhereNull('importer_id');
            })->count(),
            'active' => 0,
            'inactive' => 0,
            'with_products' => 0,
        ];
    }
}
