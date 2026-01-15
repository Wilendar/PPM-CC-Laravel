<?php

namespace App\Http\Livewire\Admin\Parameters;

use App\Models\Warehouse;
use App\Models\PrestaShopShop;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\Computed;

/**
 * WarehouseManager - CRUD dla Magazynów
 *
 * Funkcje:
 * - Lista magazynów z filtrowaniem
 * - Dodawanie/edycja magazynu
 * - Ustawianie domyślnego magazynu
 * - Mapowanie do sklepów PrestaShop
 */
class WarehouseManager extends Component
{
    // Modal states
    public bool $showModal = false;
    public bool $showDeleteModal = false;

    // Editing state
    public ?int $editingId = null;

    // Form data
    public array $formData = [
        'name' => '',
        'code' => '',
        'type' => 'custom',
        'shop_id' => null,
        'address' => '',
        'city' => '',
        'postal_code' => '',
        'country' => 'PL',
        'is_default' => false,
        'is_active' => true,
        'sort_order' => 0,
        'allow_negative_stock' => false,
        'contact_person' => '',
        'phone' => '',
        'email' => '',
        'notes' => '',
    ];

    // Filters
    public string $search = '';
    public string $statusFilter = 'all';
    public string $typeFilter = 'all';

    // Delete confirmation
    public ?int $deleteId = null;
    public string $deleteName = '';

    protected $listeners = ['refreshWarehouses' => '$refresh'];

    #[Computed]
    public function warehouses()
    {
        return Warehouse::query()
            ->with('shop')
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                          ->orWhere('code', 'like', "%{$this->search}%")
                          ->orWhere('city', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter === 'active', fn($q) => $q->active())
            ->when($this->statusFilter === 'inactive', fn($q) => $q->where('is_active', false))
            ->when($this->typeFilter !== 'all', fn($q) => $q->where('type', $this->typeFilter))
            ->ordered()
            ->get();
    }

    #[Computed]
    public function shops()
    {
        return PrestaShopShop::active()->orderBy('name')->get();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => Warehouse::count(),
            'active' => Warehouse::active()->count(),
            'master' => Warehouse::master()->count(),
            'shop_linked' => Warehouse::shopLinked()->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD OPERATIONS
    |--------------------------------------------------------------------------
    */

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $warehouse = Warehouse::findOrFail($id);

        $this->editingId = $id;
        $this->formData = [
            'name' => $warehouse->name,
            'code' => $warehouse->code,
            'type' => $warehouse->type ?? 'custom',
            'shop_id' => $warehouse->shop_id,
            'address' => $warehouse->address ?? '',
            'city' => $warehouse->city ?? '',
            'postal_code' => $warehouse->postal_code ?? '',
            'country' => $warehouse->country ?? 'PL',
            'is_default' => $warehouse->is_default,
            'is_active' => $warehouse->is_active,
            'sort_order' => $warehouse->sort_order,
            'allow_negative_stock' => $warehouse->allow_negative_stock,
            'contact_person' => $warehouse->contact_person ?? '',
            'phone' => $warehouse->phone ?? '',
            'email' => $warehouse->email ?? '',
            'notes' => $warehouse->notes ?? '',
        ];
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'formData.name' => 'required|string|max:255',
            'formData.code' => 'nullable|string|max:50',
            'formData.type' => 'required|in:master,shop_linked,custom',
            'formData.shop_id' => 'nullable|exists:prestashop_shops,id',
            'formData.address' => 'nullable|string|max:500',
            'formData.city' => 'nullable|string|max:100',
            'formData.postal_code' => 'nullable|string|max:20',
            'formData.country' => 'string|max:2',
            'formData.is_default' => 'boolean',
            'formData.is_active' => 'boolean',
            'formData.sort_order' => 'integer|min:0',
            'formData.allow_negative_stock' => 'boolean',
            'formData.contact_person' => 'nullable|string|max:255',
            'formData.phone' => 'nullable|string|max:50',
            'formData.email' => 'nullable|email|max:255',
            'formData.notes' => 'nullable|string',
        ]);

        $data = $this->formData;

        // Auto-generate code if empty
        if (empty($data['code'])) {
            $data['code'] = Str::slug($data['name'], '_');
        }

        // Check code uniqueness
        $existingCode = Warehouse::where('code', $data['code'])
            ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId))
            ->exists();

        if ($existingCode) {
            $this->addError('formData.code', 'Taki kod juz istnieje');
            return;
        }

        // Handle default warehouse logic
        if ($data['is_default']) {
            Warehouse::where('is_default', true)
                ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId))
                ->update(['is_default' => false]);
        }

        if ($this->editingId) {
            $warehouse = Warehouse::findOrFail($this->editingId);
            $warehouse->update($data);
            $message = "Magazyn '{$warehouse->name}' zostal zaktualizowany";
        } else {
            $warehouse = Warehouse::create($data);
            $message = "Magazyn '{$warehouse->name}' zostal utworzony";
        }

        $this->closeModal();
        $this->dispatch('flash-message', type: 'success', message: $message);
    }

    public function confirmDelete(int $id): void
    {
        $warehouse = Warehouse::findOrFail($id);
        $this->deleteId = $id;
        $this->deleteName = $warehouse->name;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if (!$this->deleteId) {
            return;
        }

        $warehouse = Warehouse::findOrFail($this->deleteId);

        if (!$warehouse->canDelete()) {
            $this->dispatch('flash-message', type: 'error', message: 'Nie mozna usunac magazynu - posiada stany magazynowe lub jest domyslny');
            $this->showDeleteModal = false;
            return;
        }

        $name = $warehouse->name;
        $warehouse->delete();

        $this->showDeleteModal = false;
        $this->deleteId = null;
        $this->deleteName = '';

        $this->dispatch('flash-message', type: 'success', message: "Magazyn '{$name}' zostal usuniety");
    }

    /**
     * Set warehouse as default
     */
    public function setAsDefault(int $id): void
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->setAsDefault();

        $this->dispatch('flash-message', type: 'success', message: "'{$warehouse->name}' ustawiony jako domyslny");
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deleteId = null;
        $this->deleteName = '';
    }

    private function resetForm(): void
    {
        $this->formData = [
            'name' => '',
            'code' => '',
            'type' => 'custom',
            'shop_id' => null,
            'address' => '',
            'city' => '',
            'postal_code' => '',
            'country' => 'PL',
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 0,
            'allow_negative_stock' => false,
            'contact_person' => '',
            'phone' => '',
            'email' => '',
            'notes' => '',
        ];
        $this->editingId = null;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.parameters.warehouse-manager');
    }
}
