<?php

namespace App\Http\Livewire\Admin\Parameters;

use App\Models\PrestaShopShop;
use App\Models\ProductType;
use App\Models\ShopCategoryTypeMapping;
use App\Services\Import\CategoryTypeMapper;
use App\Services\PrestaShop\PrestaShopCategoryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * CategoryTypeMappingsPanel - Mapowanie kategorii PrestaShop na typy produktow PPM
 *
 * Pokazuje drzewko kategorii z PrestaShop API (cached) i pozwala
 * adminowi przypisac kategorie PS do typow produktow PPM per sklep.
 */
class CategoryTypeMappingsPanel extends Component
{
    // ─── Shop selection ──────────────────────────────────────────────────────
    public ?int $selectedShopId = null;
    public array $availableShops = [];

    // ─── Mappings list ───────────────────────────────────────────────────────
    public array $mappings = [];

    // ─── Modal state ─────────────────────────────────────────────────────────
    public bool $showMappingModal = false;
    public ?int $editingMappingId = null;

    // ─── Form fields ─────────────────────────────────────────────────────────
    public ?int $formCategoryId = null;
    public ?int $formProductTypeId = null;
    public int $formPriority = 50;
    public bool $formIncludeChildren = true;

    // ─── Preview ─────────────────────────────────────────────────────────────
    public ?int $previewCount = null;

    // ─── Category search ──────────────────────────────────────────────────────
    public string $categorySearch = '';
    public ?string $selectedCategoryName = null;

    // ─── Reference data ──────────────────────────────────────────────────────
    public array $availableTypes = [];
    public array $categoryTree = [];

    // ─── Injected services ───────────────────────────────────────────────────
    protected PrestaShopCategoryService $categoryService;

    public function boot(PrestaShopCategoryService $categoryService): void
    {
        $this->categoryService = $categoryService;
    }

    // ─── Validation ──────────────────────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'formCategoryId' => 'required|integer|min:1',
            'formProductTypeId' => 'required|integer|exists:product_types,id',
            'formPriority' => 'required|integer|min:0|max:999',
            'formIncludeChildren' => 'boolean',
        ];
    }

    protected $messages = [
        'formCategoryId.required' => 'Wybierz kategorie.',
        'formProductTypeId.required' => 'Wybierz typ produktu.',
        'formPriority.required' => 'Podaj priorytet.',
        'formPriority.min' => 'Priorytet musi byc >= 0.',
        'formPriority.max' => 'Priorytet musi byc <= 999.',
    ];

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->loadShops();
        $this->loadProductTypes();
    }

    /*
    |--------------------------------------------------------------------------
    | DATA LOADING
    |--------------------------------------------------------------------------
    */

    protected function loadShops(): void
    {
        $this->availableShops = PrestaShopShop::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'url'])
            ->toArray();
    }

    protected function loadProductTypes(): void
    {
        $this->availableTypes = ProductType::active()
            ->ordered()
            ->get(['id', 'name', 'slug', 'label_color'])
            ->toArray();
    }

    public function loadMappings(): void
    {
        if (!$this->selectedShopId) {
            $this->mappings = [];
            return;
        }

        $this->mappings = ShopCategoryTypeMapping::where('shop_id', $this->selectedShopId)
            ->with(['productType:id,name,slug,label_color'])
            ->orderBy('priority')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (ShopCategoryTypeMapping $m) {
                return [
                    'id' => $m->id,
                    'category_id' => $m->category_id,
                    'category_name' => $m->category_name ?? '(PS #' . $m->category_id . ')',
                    'product_type_id' => $m->product_type_id,
                    'product_type_name' => $m->productType?->name ?? '(usuniety)',
                    'product_type_slug' => $m->productType?->slug ?? 'inne',
                    'product_type_color' => $m->productType?->label_color ?? '#6b7280',
                    'include_children' => $m->include_children,
                    'priority' => $m->priority,
                    'is_active' => $m->is_active,
                ];
            })
            ->toArray();
    }

    /**
     * Load category tree from PrestaShop API (cached)
     */
    public function loadCategoryTree(): void
    {
        if (!$this->selectedShopId) {
            $this->categoryTree = [];
            return;
        }

        try {
            $shop = PrestaShopShop::find($this->selectedShopId);
            if (!$shop) {
                $this->categoryTree = [];
                return;
            }

            $psTree = $this->categoryService->getCachedCategoryTree($shop);
            // Transform PS tree to format compatible with our tree node partial
            $this->categoryTree = $this->transformPsTree($psTree);
        } catch (\Exception $e) {
            Log::warning('CategoryTypeMappingsPanel: failed to load PS categories', [
                'shop_id' => $this->selectedShopId,
                'error' => $e->getMessage(),
            ]);
            $this->categoryTree = [];
        }
    }

    /**
     * Transform PS category tree to our partial format
     * PS format: {id, name, id_parent, children, level, position, active}
     * Our format: {id, name, children, is_mapped (always true for PS)}
     */
    protected function transformPsTree(array $psNodes): array
    {
        $tree = [];
        foreach ($psNodes as $node) {
            $children = !empty($node['children'])
                ? $this->transformPsTree($node['children'])
                : [];

            $tree[] = [
                'id' => (int) $node['id'],
                'name' => $node['name'] ?? '(bez nazwy)',
                'children' => $children,
                'is_mapped' => true, // All PS categories are selectable
            ];
        }
        return $tree;
    }

    /**
     * Select a category from the PS tree
     */
    public function selectCategory(int $categoryId): void
    {
        $this->formCategoryId = $categoryId;
        // Find name from loaded tree
        $this->selectedCategoryName = $this->findCategoryNameInTree(
            $this->categoryTree, $categoryId
        );
        $this->refreshPreview();
    }

    /**
     * Recursively find category name in tree
     */
    protected function findCategoryNameInTree(array $tree, int $id): ?string
    {
        foreach ($tree as $node) {
            if ($node['id'] === $id) {
                return $node['name'];
            }
            if (!empty($node['children'])) {
                $found = $this->findCategoryNameInTree($node['children'], $id);
                if ($found) {
                    return $found;
                }
            }
        }
        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | REACTIVE UPDATES
    |--------------------------------------------------------------------------
    */

    public function updatedSelectedShopId(): void
    {
        $this->loadMappings();
        $this->loadCategoryTree();
        $this->resetForm();
    }

    public function updatedFormIncludeChildren(): void
    {
        $this->refreshPreview();
    }

    /*
    |--------------------------------------------------------------------------
    | MODAL ACTIONS
    |--------------------------------------------------------------------------
    */

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showMappingModal = true;
    }

    public function openEditModal(int $id): void
    {
        $mapping = ShopCategoryTypeMapping::find($id);
        if (!$mapping) {
            return;
        }

        $this->editingMappingId = $id;
        $this->formCategoryId = $mapping->category_id;
        $this->selectedCategoryName = $mapping->category_name;
        $this->formProductTypeId = $mapping->product_type_id;
        $this->formPriority = $mapping->priority;
        $this->formIncludeChildren = $mapping->include_children;
        $this->showMappingModal = true;

        $this->refreshPreview();
    }

    public function closeModal(): void
    {
        $this->showMappingModal = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->editingMappingId = null;
        $this->formCategoryId = null;
        $this->selectedCategoryName = null;
        $this->formProductTypeId = null;
        $this->formPriority = 50;
        $this->formIncludeChildren = true;
        $this->previewCount = null;
        $this->categorySearch = '';
        $this->resetValidation();
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD OPERATIONS
    |--------------------------------------------------------------------------
    */

    public function saveMapping(): void
    {
        $this->validate();

        if (!$this->selectedShopId) {
            return;
        }

        try {
            $data = [
                'shop_id' => $this->selectedShopId,
                'category_id' => $this->formCategoryId,
                'category_name' => $this->selectedCategoryName,
                'product_type_id' => $this->formProductTypeId,
                'include_children' => $this->formIncludeChildren,
                'priority' => $this->formPriority,
                'is_active' => true,
                'created_by' => Auth::id(),
            ];

            $isEditing = (bool) $this->editingMappingId;

            if ($this->editingMappingId) {
                $mapping = ShopCategoryTypeMapping::find($this->editingMappingId);
                if ($mapping) {
                    unset($data['created_by']);
                    $mapping->update($data);
                }
            } else {
                // Check for duplicate (same shop + PS category)
                $exists = ShopCategoryTypeMapping::where('shop_id', $this->selectedShopId)
                    ->where('category_id', $this->formCategoryId)
                    ->exists();

                if ($exists) {
                    $this->addError('formCategoryId', 'To mapowanie juz istnieje dla tego sklepu.');
                    return;
                }

                ShopCategoryTypeMapping::create($data);
            }

            $this->closeModal();
            $this->loadMappings();

            $this->dispatch('success', message: $isEditing ? 'Mapowanie zaktualizowane.' : 'Mapowanie dodane.');
        } catch (\Exception $e) {
            Log::error('CategoryTypeMappingsPanel::saveMapping FAILED', [
                'error' => $e->getMessage(),
            ]);
            $this->addError('formCategoryId', 'Blad zapisu: ' . $e->getMessage());
            $this->dispatch('error', message: 'Blad zapisu mapowania.');
        }
    }

    public function deleteMapping(int $id): void
    {
        $mapping = ShopCategoryTypeMapping::find($id);
        if ($mapping && $mapping->shop_id === $this->selectedShopId) {
            $mapping->delete();
            $this->loadMappings();
            $this->dispatch('success', message: 'Mapowanie usuniete.');
        }
    }

    public function toggleActive(int $id): void
    {
        $mapping = ShopCategoryTypeMapping::find($id);
        if ($mapping && $mapping->shop_id === $this->selectedShopId) {
            $mapping->update(['is_active' => !$mapping->is_active]);
            $this->loadMappings();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PREVIEW
    |--------------------------------------------------------------------------
    */

    public function refreshPreview(): void
    {
        if (!$this->formCategoryId || !$this->selectedShopId) {
            $this->previewCount = null;
            return;
        }

        try {
            $mapper = app(CategoryTypeMapper::class);
            $this->previewCount = $mapper->countAffectedProducts(
                $this->formCategoryId,
                $this->selectedShopId,
                $this->formIncludeChildren
            );
        } catch (\Exception $e) {
            Log::warning('CategoryTypeMappingsPanel::refreshPreview failed', [
                'error' => $e->getMessage(),
            ]);
            $this->previewCount = null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        return view('livewire.admin.parameters.category-type-mappings-panel');
    }
}
