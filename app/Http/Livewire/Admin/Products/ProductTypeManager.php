<?php

namespace App\Http\Livewire\Admin\Products;

use Livewire\Component;
use App\Models\ProductType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * ProductTypeManager Component - Zarządzanie typami produktów
 *
 * Features:
 * - CRUD operations dla typów produktów
 * - Drag & drop reordering
 * - Bulk operations (activate/deactivate)
 * - Product count per type
 * - Safe deletion (tylko gdy brak produktów)
 *
 * @package App\Http\Livewire\Admin\Products
 * @version 1.0
 * @since ETAP_05 FAZA 4 - Editable Product Types
 */
class ProductTypeManager extends Component
{
    /*
    |--------------------------------------------------------------------------
    | COMPONENT PROPERTIES
    |--------------------------------------------------------------------------
    */

    // Modal states
    public bool $showCreateModal = false;
    public bool $showEditModal = false;
    public bool $showDeleteModal = false;

    // Form data
    public ?ProductType $selectedType = null;
    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public string $icon = '';
    public bool $is_active = true;
    public int $sort_order = 0;

    // UI state
    public string $search = '';
    public array $selected = [];
    public bool $selectAll = false;
    public string $successMessage = '';
    public string $errorMessage = '';

    /*
    |--------------------------------------------------------------------------
    | VALIDATION RULES
    |--------------------------------------------------------------------------
    */

    protected function rules(): array
    {
        $typeId = $this->selectedType?->id;

        return [
            'name' => 'required|string|max:100|min:2',
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9\-]+$/',
                $this->selectedType ? "unique:product_types,slug,{$typeId}" : 'unique:product_types,slug',
            ],
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Nazwa typu jest wymagana.',
            'name.min' => 'Nazwa typu musi mieć minimum 2 znaki.',
            'slug.required' => 'Slug jest wymagany.',
            'slug.unique' => 'Typ o tym slug już istnieje.',
            'slug.regex' => 'Slug może zawierać tylko małe litery, cyfry i myślniki.',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE METHODS
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        // Initialize sort_order for new types
        $this->sort_order = (ProductType::max('sort_order') ?? 0) + 10;
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Get product types with search and product counts
     */
    public function getProductTypesProperty()
    {
        $query = ProductType::query()
            ->withCount('products')
            ->ordered();

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('slug', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        return $query->get();
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD OPERATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Show create modal
     */
    public function showCreateModal(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    /**
     * Show edit modal
     */
    public function showEditModal(ProductType $productType): void
    {
        $this->selectedType = $productType;
        $this->loadTypeData();
        $this->showEditModal = true;
    }

    /**
     * Show delete confirmation modal
     */
    public function showDeleteModal(ProductType $productType): void
    {
        $this->selectedType = $productType;
        $this->showDeleteModal = true;
    }

    /**
     * Create new product type
     */
    public function create(): void
    {
        $this->validate();

        try {
            DB::beginTransaction();

            ProductType::create([
                'name' => $this->name,
                'slug' => $this->slug,
                'description' => $this->description ?: null,
                'icon' => $this->icon ?: null,
                'is_active' => $this->is_active,
                'sort_order' => $this->sort_order,
            ]);

            DB::commit();

            $this->successMessage = "Typ produktu '{$this->name}' został utworzony pomyślnie.";
            $this->showCreateModal = false;
            $this->resetForm();

            Log::info('ProductType created', [
                'name' => $this->name,
                'slug' => $this->slug,
                'user_id' => Auth::id(),
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            $this->errorMessage = 'Wystąpił błąd podczas tworzenia typu produktu.';

            Log::error('ProductType creation failed', [
                'error' => $e->getMessage(),
                'name' => $this->name,
                'user_id' => Auth::id(),
            ]);
        }
    }

    /**
     * Update existing product type
     */
    public function update(): void
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $this->selectedType->update([
                'name' => $this->name,
                'slug' => $this->slug,
                'description' => $this->description ?: null,
                'icon' => $this->icon ?: null,
                'is_active' => $this->is_active,
                'sort_order' => $this->sort_order,
            ]);

            DB::commit();

            $this->successMessage = "Typ produktu '{$this->name}' został zaktualizowany pomyślnie.";
            $this->showEditModal = false;
            $this->resetForm();

            Log::info('ProductType updated', [
                'id' => $this->selectedType->id,
                'name' => $this->name,
                'user_id' => Auth::id(),
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            $this->errorMessage = 'Wystąpił błąd podczas aktualizacji typu produktu.';

            Log::error('ProductType update failed', [
                'error' => $e->getMessage(),
                'id' => $this->selectedType->id,
                'user_id' => Auth::id(),
            ]);
        }
    }

    /**
     * Delete product type
     */
    public function delete(): void
    {
        if (!$this->selectedType->canBeDeleted()) {
            $this->errorMessage = 'Nie można usunąć typu produktu, który ma przypisane produkty.';
            $this->showDeleteModal = false;
            return;
        }

        try {
            DB::beginTransaction();

            $typeName = $this->selectedType->name;
            $this->selectedType->delete();

            DB::commit();

            $this->successMessage = "Typ produktu '{$typeName}' został usunięty pomyślnie.";
            $this->showDeleteModal = false;
            $this->selectedType = null;

            Log::info('ProductType deleted', [
                'name' => $typeName,
                'user_id' => Auth::id(),
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            $this->errorMessage = 'Wystąpił błąd podczas usuwania typu produktu.';

            Log::error('ProductType deletion failed', [
                'error' => $e->getMessage(),
                'id' => $this->selectedType->id,
                'user_id' => Auth::id(),
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | BULK OPERATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Toggle product type status (active/inactive)
     */
    public function toggleStatus(ProductType $productType): void
    {
        try {
            $productType->update(['is_active' => !$productType->is_active]);

            $status = $productType->is_active ? 'aktywowany' : 'dezaktywowany';
            $this->successMessage = "Typ '{$productType->name}' został {$status}.";

        } catch (\Exception $e) {
            $this->errorMessage = 'Wystąpił błąd podczas zmiany statusu.';
            Log::error('ProductType status toggle failed', [
                'error' => $e->getMessage(),
                'id' => $productType->id,
            ]);
        }
    }

    /**
     * Bulk activate selected types
     */
    public function bulkActivate(): void
    {
        $this->bulkUpdateStatus(true, 'aktywowane');
    }

    /**
     * Bulk deactivate selected types
     */
    public function bulkDeactivate(): void
    {
        $this->bulkUpdateStatus(false, 'dezaktywowane');
    }

    /**
     * Bulk update status helper
     */
    private function bulkUpdateStatus(bool $status, string $actionName): void
    {
        if (empty($this->selected)) {
            $this->errorMessage = 'Wybierz typy produktów do operacji.';
            return;
        }

        try {
            DB::beginTransaction();

            $count = ProductType::whereIn('id', $this->selected)
                                ->update(['is_active' => $status]);

            DB::commit();

            $this->successMessage = "Pomyślnie {$actionName} {$count} typów produktów.";
            $this->selected = [];
            $this->selectAll = false;

        } catch (\Exception $e) {
            DB::rollback();
            $this->errorMessage = 'Wystąpił błąd podczas operacji masowej.';
            Log::error('ProductType bulk status update failed', [
                'error' => $e->getMessage(),
                'ids' => $this->selected,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Auto-generate slug from name
     */
    public function updatedName(): void
    {
        if (!$this->selectedType) { // Only auto-generate for new types
            $this->slug = \Illuminate\Support\Str::slug($this->name);
        }
        $this->resetErrorBag('name');
    }

    /**
     * Validate slug format on change
     */
    public function updatedSlug(): void
    {
        $this->slug = \Illuminate\Support\Str::slug($this->slug);
        $this->resetErrorBag('slug');
    }

    /**
     * Toggle select all checkbox
     */
    public function updatedSelectAll(): void
    {
        $this->selected = $this->selectAll
            ? $this->productTypes->pluck('id')->toArray()
            : [];
    }

    /**
     * Load product type data into form
     */
    private function loadTypeData(): void
    {
        $this->name = $this->selectedType->name;
        $this->slug = $this->selectedType->slug;
        $this->description = $this->selectedType->description ?? '';
        $this->icon = $this->selectedType->icon ?? '';
        $this->is_active = $this->selectedType->is_active;
        $this->sort_order = $this->selectedType->sort_order;
    }

    /**
     * Reset form data
     */
    private function resetForm(): void
    {
        $this->selectedType = null;
        $this->name = '';
        $this->slug = '';
        $this->description = '';
        $this->icon = '';
        $this->is_active = true;
        $this->sort_order = (ProductType::max('sort_order') ?? 0) + 10;
        $this->resetErrorBag();
    }

    /**
     * Close modals and clear messages
     */
    public function closeModal(): void
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->showDeleteModal = false;
        $this->resetForm();
    }

    /**
     * Clear messages
     */
    public function clearMessages(): void
    {
        $this->successMessage = '';
        $this->errorMessage = '';
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.admin.products.product-type-manager', [
            'productTypes' => $this->productTypes,
        ])->layout('layouts.admin', [
            'title' => 'Zarządzanie typami produktów',
            'breadcrumbs' => [
                ['name' => 'Admin', 'url' => route('admin.dashboard')],
                ['name' => 'Produkty', 'url' => route('admin.products.index')],
                ['name' => 'Typy produktów', 'url' => null],
            ],
        ]);
    }
}