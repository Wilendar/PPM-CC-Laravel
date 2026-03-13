<?php

namespace App\Http\Livewire\Admin\Parameters;

use App\Models\ProductStatus;
use App\Models\ProductStatusIntegrationMapping;
use App\Models\Warehouse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Attributes\Computed;

/**
 * ProductStatusManager - CRUD dla statusow produktow
 *
 * Zarzadzanie dynamicznymi statusami produktow z mapowaniem do integracji.
 * Osadzany w tabie ProductParametersManager.
 */
class ProductStatusManager extends Component
{
    // Modal states
    public bool $showModal = false;
    public bool $showDeleteModal = false;

    // Editing state
    public ?int $editingId = null;

    // Search
    public string $search = '';

    // Form data
    public array $formData = [
        'name' => '',
        'color' => '#6b7280',
        'icon' => '',
        'is_active_equivalent' => true,
        'is_default' => false,
        'sort_order' => 0,
        'transition_on_stock_depleted' => false,
        'transition_to_status_id' => null,
        'depletion_warehouse_id' => null,
    ];

    // Integration mappings (checkboxes)
    public array $integrationMappings = [
        'prestashop' => true,
        'baselinker' => true,
        'subiekt_gt' => true,
    ];

    // Delete confirmation
    public ?int $deleteId = null;
    public string $deleteName = '';
    public int $deleteProductsCount = 0;

    protected $listeners = ['refreshProductStatuses' => '$refresh'];

    #[Computed]
    public function statuses()
    {
        return ProductStatus::query()
            ->withCount('products')
            ->with('integrationMappings')
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('slug', 'like', '%' . $this->search . '%');
            })
            ->ordered()
            ->get();
    }

    #[Computed]
    public function availableTransitionStatuses(): array
    {
        return ProductStatus::query()
            ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId))
            ->ordered()
            ->get(['id', 'name', 'color'])
            ->toArray();
    }

    #[Computed]
    public function warehouses(): array
    {
        return Warehouse::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_default'])
            ->toArray();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => ProductStatus::count(),
            'active_equivalent' => ProductStatus::where('is_active_equivalent', true)->count(),
            'inactive_equivalent' => ProductStatus::where('is_active_equivalent', false)->count(),
            'in_use' => ProductStatus::whereHas('products')->count(),
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
        $status = ProductStatus::with('integrationMappings')->findOrFail($id);

        $this->editingId = $id;
        $this->formData = [
            'name' => $status->name,
            'color' => $status->color,
            'icon' => $status->icon ?? '',
            'is_active_equivalent' => $status->is_active_equivalent,
            'is_default' => $status->is_default,
            'sort_order' => $status->sort_order,
            'transition_on_stock_depleted' => $status->transition_on_stock_depleted,
            'transition_to_status_id' => $status->transition_to_status_id,
            'depletion_warehouse_id' => $status->depletion_warehouse_id,
        ];

        // Load integration mappings
        $this->integrationMappings = [
            'prestashop' => true,
            'baselinker' => true,
            'subiekt_gt' => true,
        ];

        foreach ($status->integrationMappings as $mapping) {
            if (array_key_exists($mapping->integration_type, $this->integrationMappings)) {
                $this->integrationMappings[$mapping->integration_type] = $mapping->maps_to_active;
            }
        }

        $this->showModal = true;
    }

    public function save(): void
    {
        $rules = [
            'formData.name' => 'required|string|max:100',
            'formData.color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'formData.icon' => 'nullable|string|max:50',
            'formData.is_active_equivalent' => 'boolean',
            'formData.is_default' => 'boolean',
            'formData.sort_order' => 'integer|min:0',
            'formData.transition_on_stock_depleted' => 'boolean',
            'formData.transition_to_status_id' => 'nullable|integer|exists:product_statuses,id',
            'formData.depletion_warehouse_id' => 'nullable|integer|exists:warehouses,id',
        ];

        if (!empty($this->formData['transition_on_stock_depleted'])) {
            $rules['formData.transition_to_status_id'] = 'required|integer|exists:product_statuses,id';
        }

        $this->validate($rules, [
            'formData.name.required' => 'Nazwa statusu jest wymagana.',
            'formData.name.max' => 'Nazwa nie moze miec wiecej niz 100 znakow.',
            'formData.color.required' => 'Kolor jest wymagany.',
            'formData.color.regex' => 'Kolor musi byc w formacie HEX (#RRGGBB).',
            'formData.transition_to_status_id.required' => 'Wybierz status docelowy po wyczerpaniu zapasow.',
        ]);

        $data = $this->formData;

        // Clear transition fields if auto-transition disabled
        if (empty($data['transition_on_stock_depleted'])) {
            $data['transition_to_status_id'] = null;
            $data['depletion_warehouse_id'] = null;
        }

        // Generate slug from name
        $slug = Str::slug($data['name']);

        // Ensure unique slug (skip current when editing)
        $slugQuery = ProductStatus::where('slug', $slug);
        if ($this->editingId) {
            $slugQuery->where('id', '!=', $this->editingId);
        }

        if ($slugQuery->exists()) {
            $counter = 1;
            while (ProductStatus::where('slug', $slug . '-' . $counter)
                ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId))
                ->exists()) {
                $counter++;
            }
            $slug = $slug . '-' . $counter;
        }

        $data['slug'] = $slug;

        try {
            DB::transaction(function () use ($data) {
                if ($this->editingId) {
                    $status = ProductStatus::findOrFail($this->editingId);
                    $status->update($data);
                    $message = "Status '{$status->name}' zostal zaktualizowany";
                } else {
                    $status = ProductStatus::create($data);
                    $message = "Status '{$status->name}' zostal utworzony";
                }

                // Sync integration mappings
                $this->syncIntegrationMappings($status);

                $this->closeModal();
                $this->dispatch('flash-message', type: 'success', message: $message);
            });
        } catch (\Exception $e) {
            Log::error('[PRODUCT_STATUS] Save failed', [
                'editing_id' => $this->editingId,
                'error' => $e->getMessage(),
            ]);

            $this->addError('formData.name', 'Blad zapisu: ' . $e->getMessage());
        }
    }

    /**
     * Sync integration mappings for a status
     */
    protected function syncIntegrationMappings(ProductStatus $status): void
    {
        foreach ($this->integrationMappings as $type => $mapsToActive) {
            ProductStatusIntegrationMapping::updateOrCreate(
                [
                    'product_status_id' => $status->id,
                    'integration_type' => $type,
                ],
                [
                    'maps_to_active' => (bool) $mapsToActive,
                ]
            );
        }
    }

    public function confirmDelete(int $id): void
    {
        // Cannot delete the last status
        if (ProductStatus::count() <= 1) {
            $this->dispatch('flash-message', type: 'error', message: 'Nie mozna usunac ostatniego statusu produktu.');
            return;
        }

        $status = ProductStatus::withCount('products')->findOrFail($id);

        $this->deleteId = $id;
        $this->deleteName = $status->name;
        $this->deleteProductsCount = $status->products_count;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if (!$this->deleteId) {
            return;
        }

        // Double-check: cannot delete the last status
        if (ProductStatus::count() <= 1) {
            $this->dispatch('flash-message', type: 'error', message: 'Nie mozna usunac ostatniego statusu produktu.');
            $this->closeDeleteModal();
            return;
        }

        try {
            DB::transaction(function () {
                $status = ProductStatus::findOrFail($this->deleteId);
                $name = $status->name;

                // Reassign products to default status (PPM rule: detach + delete, NEVER block!)
                $defaultStatus = ProductStatus::where('id', '!=', $this->deleteId)
                    ->where('is_default', true)
                    ->first();

                if (!$defaultStatus) {
                    // If no default, pick the first available
                    $defaultStatus = ProductStatus::where('id', '!=', $this->deleteId)
                        ->ordered()
                        ->first();
                }

                $reassignedCount = 0;
                if ($defaultStatus) {
                    $reassignedCount = $status->products()->count();
                    $status->products()->update(['product_status_id' => $defaultStatus->id]);
                }

                // Delete integration mappings (cascade)
                $status->integrationMappings()->delete();

                // Delete the status
                $status->delete();

                $message = "Status '{$name}' zostal usuniety";
                if ($reassignedCount > 0) {
                    $message .= " ({$reassignedCount} produktow przeniesiono do '{$defaultStatus->name}')";
                }

                Log::info('[PRODUCT_STATUS] Deleted', [
                    'name' => $name,
                    'reassigned_to' => $defaultStatus?->name,
                    'reassigned_count' => $reassignedCount,
                ]);

                $this->closeDeleteModal();
                $this->dispatch('flash-message', type: 'success', message: $message);
            });
        } catch (\Exception $e) {
            Log::error('[PRODUCT_STATUS] Delete failed', [
                'id' => $this->deleteId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', type: 'error', message: 'Blad usuwania: ' . $e->getMessage());
        }
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
        $this->deleteProductsCount = 0;
    }

    private function resetForm(): void
    {
        $this->formData = [
            'name' => '',
            'color' => '#6b7280',
            'icon' => '',
            'is_active_equivalent' => true,
            'is_default' => false,
            'sort_order' => 0,
            'transition_on_stock_depleted' => false,
            'transition_to_status_id' => null,
            'depletion_warehouse_id' => null,
        ];
        $this->integrationMappings = [
            'prestashop' => true,
            'baselinker' => true,
            'subiekt_gt' => true,
        ];
        $this->editingId = null;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.parameters.product-status-manager');
    }
}
