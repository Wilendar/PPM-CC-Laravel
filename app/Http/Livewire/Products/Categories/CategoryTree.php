<?php

namespace App\Http\Livewire\Products\Categories;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CategoryTree Livewire Component - Interactive Category Management
 *
 * Core Features:
 * - 5-level hierarchical tree visualization
 * - Drag & drop category reordering
 * - Real-time search and filtering
 * - Inline editing capabilities
 * - Bulk operations support
 * - Live product count display
 *
 * Performance Optimizations:
 * - Eager loading relationships
 * - Path-based tree queries
 * - Lazy loading for large trees
 * - Smart re-rendering
 *
 * @package App\Http\Livewire\Products\Categories
 * @version 1.0
 * @since ETAP_05 - FAZA 3: Category System Implementation
 */
class CategoryTree extends Component
{
    use WithPagination;

    // ==========================================
    // COMPONENT STATE PROPERTIES
    // ==========================================

    /**
     * Search query for filtering categories
     *
     * @var string
     */
    public $search = '';

    /**
     * Selected categories for bulk operations
     *
     * @var array
     */
    public $selectedCategories = [];

    /**
     * Expanded category nodes state
     *
     * @var array
     */
    public $expandedNodes = [];

    /**
     * Current category being edited inline
     *
     * @var int|null
     */
    public $editingCategory = null;

    /**
     * Temporary edit values
     *
     * @var array
     */
    public $editForm = [
        'name' => '',
        'description' => '',
        'icon' => '',
        'is_active' => true,
    ];

    /**
     * Show only active categories
     *
     * @var bool
     */
    public $showActiveOnly = true;

    /**
     * Show categories with products only
     *
     * @var bool
     */
    public $showWithProductsOnly = false;

    /**
     * View mode: tree|flat
     *
     * @var string
     */
    public $viewMode = 'tree';

    /**
     * Sort field
     *
     * @var string
     */
    public $sortField = 'sort_order';

    /**
     * Sort direction
     *
     * @var string
     */
    public $sortDirection = 'asc';

    /**
     * Show category form modal
     *
     * @var bool
     */
    public $showModal = false;

    /**
     * Modal mode: create|edit
     *
     * @var string
     */
    public $modalMode = 'create';

    /**
     * Category form data for modal
     *
     * @var array
     */
    public $categoryForm = [
        'parent_id' => null,
        'name' => '',
        'description' => '',
        'icon' => '',
        'meta_title' => '',
        'meta_description' => '',
        'is_active' => true,
    ];

    /**
     * Loading states for different operations
     *
     * @var array
     */
    public $loadingStates = [
        'tree' => false,
        'search' => false,
        'reorder' => false,
        'save' => false,
        'delete' => false,
    ];

    // ==========================================
    // LIVEWIRE LIFECYCLE METHODS
    // ==========================================

    /**
     * Component mount - initialize default state
     */
    public function mount(): void
    {
        // Force tree view mode as default (override session preferences)
        $this->viewMode = 'tree';

        // Load user preferences for expanded nodes (but not viewMode)
        $this->loadUserPreferences();

        // Force tree mode again to ensure it's not overridden
        $this->viewMode = 'tree';

        // Start with all categories collapsed (remove auto-expand)
        // $this->expandRootCategories(); // COMMENTED OUT - start collapsed

        // Save the forced tree mode to session
        $this->saveUserPreferences();
    }

    /**
     * Updated search - reset pagination and apply filters
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->loadingStates['search'] = true;

        // Auto-expand matching nodes
        if (!empty($this->search)) {
            $this->expandMatchingNodes();
        }
    }

    /**
     * Updated view mode - adjust display
     */
    public function updatedViewMode(): void
    {
        $this->resetPage();
        $this->saveUserPreferences();
    }

    /**
     * Updated filters - refresh tree
     */
    public function updatedShowActiveOnly(): void
    {
        $this->resetPage();
    }

    public function updatedShowWithProductsOnly(): void
    {
        $this->resetPage();
    }

    // ==========================================
    // TREE DISPLAY METHODS
    // ==========================================

    /**
     * Get categories for tree display
     *
     * @return Collection
     */
    public function getCategoriesProperty(): Collection
    {
        $this->loadingStates['tree'] = true;

        try {
            $query = Category::query()
                ->withCount(['products', 'primaryProducts', 'children'])
                ->with(['parent']);

            // Apply search filter
            if (!empty($this->search)) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            }

            // Apply active filter
            if ($this->showActiveOnly) {
                $query->active();
            }

            // Apply products filter
            if ($this->showWithProductsOnly) {
                $query->has('products');
            }

            // Tree mode: Filter categories based on expanded nodes
            if ($this->viewMode === 'tree' && empty($this->search)) {
                // Show root categories (level 0) and children of expanded nodes
                $query->where(function ($q) {
                    $q->where('level', 0); // Root categories always visible

                    if (!empty($this->expandedNodes)) {
                        // Add children of expanded categories
                        $q->orWhere(function ($subQ) {
                            $subQ->whereIn('parent_id', $this->expandedNodes);
                        });
                    }
                });
            }

            // Apply sorting
            if ($this->viewMode === 'tree') {
                // Custom tree sorting that respects hierarchy
                $query->orderByRaw('parent_id IS NULL DESC') // nulls first (root categories)
                     ->orderBy('level', 'asc')
                     ->orderBy('parent_id', 'asc')
                     ->orderBy('sort_order', 'asc')
                     ->orderBy('name', 'asc');
            } else {
                $query->orderBy($this->sortField, $this->sortDirection);
            }

            $categories = $query->get();

            // For tree view, re-sort to ensure proper hierarchy display
            if ($this->viewMode === 'tree' && empty($this->search)) {
                $categories = $this->sortCategoriesHierarchically($categories);
            }

            $this->loadingStates['tree'] = false;

            return $categories;

        } catch (\Exception $e) {
            Log::error('CategoryTree: Error loading categories', [
                'error' => $e->getMessage(),
                'search' => $this->search,
                'filters' => [
                    'active_only' => $this->showActiveOnly,
                    'with_products_only' => $this->showWithProductsOnly,
                ]
            ]);

            $this->loadingStates['tree'] = false;
            session()->flash('error', 'Błąd podczas ładowania kategorii: ' . $e->getMessage());

            return collect();
        }
    }

    /**
     * Get tree structure for hierarchical display
     *
     * @return array
     */
    public function getTreeStructureProperty(): array
    {
        if ($this->viewMode !== 'tree') {
            return [];
        }

        return $this->buildTreeStructure($this->categories);
    }

    /**
     * Build hierarchical tree structure from flat collection
     *
     * @param Collection $categories
     * @param int|null $parentId
     * @return array
     */
    private function buildTreeStructure(Collection $categories, ?int $parentId = null): array
    {
        $tree = [];

        $children = $categories->filter(function ($category) use ($parentId) {
            return $category->parent_id === $parentId;
        });

        foreach ($children as $category) {
            $node = [
                'category' => $category,
                'children' => $this->buildTreeStructure($categories, $category->id),
                'expanded' => in_array($category->id, $this->expandedNodes),
                'selected' => in_array($category->id, $this->selectedCategories),
            ];

            $tree[] = $node;
        }

        return $tree;
    }

    // ==========================================
    // TREE INTERACTION METHODS
    // ==========================================

    /**
     * Toggle category node expansion
     *
     * @param int $categoryId
     */
    public function toggleNode(int $categoryId): void
    {
        if (in_array($categoryId, $this->expandedNodes)) {
            $this->expandedNodes = array_diff($this->expandedNodes, [$categoryId]);
        } else {
            $this->expandedNodes[] = $categoryId;
        }

        $this->saveUserPreferences();
    }

    /**
     * Expand all nodes
     */
    public function expandAll(): void
    {
        $this->expandedNodes = $this->categories->pluck('id')->toArray();
        $this->saveUserPreferences();
    }

    /**
     * Collapse all nodes
     */
    public function collapseAll(): void
    {
        $this->expandedNodes = [];
        $this->saveUserPreferences();
    }

    /**
     * Select/deselect category for bulk operations
     *
     * @param int $categoryId
     */
    public function toggleSelection(int $categoryId): void
    {
        if (in_array($categoryId, $this->selectedCategories)) {
            $this->selectedCategories = array_diff($this->selectedCategories, [$categoryId]);
        } else {
            $this->selectedCategories[] = $categoryId;
        }
    }

    /**
     * Select all visible categories
     */
    public function selectAll(): void
    {
        $this->selectedCategories = $this->categories->pluck('id')->toArray();
    }

    /**
     * Deselect all categories
     */
    public function deselectAll(): void
    {
        $this->selectedCategories = [];
    }

    // ==========================================
    // CATEGORY CRUD OPERATIONS
    // ==========================================

    /**
     * Open create category modal
     *
     * @param int|null $parentId
     */
    public function createCategory(?int $parentId = null): void
    {
        $this->modalMode = 'create';
        $this->categoryForm = [
            'parent_id' => $parentId,
            'name' => '',
            'slug' => null,
            'description' => '',
            'short_description' => '',
            'sort_order' => 0,
            'is_active' => true,
            'is_featured' => false,
            'icon' => '',
            'icon_path' => null,
            'banner_path' => null,
            'meta_title' => '',
            'meta_description' => '',
            'meta_keywords' => '',
            'canonical_url' => '',
            'og_title' => '',
            'og_description' => '',
            'og_image' => '',
            'visual_settings' => null,
            'visibility_settings' => null,
            'default_values' => null,
        ];
        $this->showModal = true;
    }

    /**
     * Open edit category modal
     *
     * @param int $categoryId
     */
    public function editCategory(int $categoryId): void
    {
        $category = Category::find($categoryId);

        if (!$category) {
            session()->flash('error', 'Kategoria nie została znaleziona.');
            return;
        }

        $this->modalMode = 'edit';
        $this->categoryForm = [
            'id' => $category->id,
            'parent_id' => $category->parent_id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description ?? '',
            'short_description' => $category->short_description ?? '',
            'sort_order' => $category->sort_order ?? 0,
            'is_active' => $category->is_active ?? true,
            'is_featured' => $category->is_featured ?? false,
            'icon' => $category->icon ?? '',
            'icon_path' => $category->icon_path ?? '',
            'banner_path' => $category->banner_path ?? '',
            'meta_title' => $category->meta_title ?? '',
            'meta_description' => $category->meta_description ?? '',
            'meta_keywords' => $category->meta_keywords ?? '',
            'canonical_url' => $category->canonical_url ?? '',
            'og_title' => $category->og_title ?? '',
            'og_description' => $category->og_description ?? '',
            'og_image' => $category->og_image ?? '',
            'visual_settings' => $category->visual_settings,
            'visibility_settings' => $category->visibility_settings,
            'default_values' => $category->default_values,
        ];
        $this->showModal = true;
    }

    /**
     * Save category (create or update)
     */
    public function saveCategory(): void
    {
        $this->loadingStates['save'] = true;

        try {
            $this->validate([
                'categoryForm.name' => 'required|string|max:300',
                'categoryForm.slug' => 'nullable|string|max:300',
                'categoryForm.description' => 'nullable|string',
                'categoryForm.short_description' => 'nullable|string',
                'categoryForm.sort_order' => 'nullable|integer|min:0',
                'categoryForm.is_active' => 'boolean',
                'categoryForm.is_featured' => 'boolean',
                'categoryForm.icon' => 'nullable|string|max:200',
                'categoryForm.icon_path' => 'nullable|string|max:500',
                'categoryForm.banner_path' => 'nullable|string|max:500',
                'categoryForm.meta_title' => 'nullable|string|max:300',
                'categoryForm.meta_description' => 'nullable|string|max:300',
                'categoryForm.meta_keywords' => 'nullable|string|max:500',
                'categoryForm.canonical_url' => 'nullable|string|max:500',
                'categoryForm.og_title' => 'nullable|string|max:300',
                'categoryForm.og_description' => 'nullable|string|max:300',
                'categoryForm.og_image' => 'nullable|string|max:500',
                'categoryForm.parent_id' => 'nullable|exists:categories,id',
                'categoryForm.visual_settings' => 'nullable|array',
                'categoryForm.visibility_settings' => 'nullable|array',
                'categoryForm.default_values' => 'nullable|array',
            ]);

            // Validate business rules
            if ($this->categoryForm['parent_id']) {
                $parent = Category::find($this->categoryForm['parent_id']);
                if ($parent && $parent->level >= Category::MAX_LEVEL) {
                    throw new \InvalidArgumentException('Nie można utworzyć kategorii - przekroczono maksymalną głębokość drzewa.');
                }
            }

            DB::transaction(function () {
                // Clean form data - remove empty strings and convert to null where needed
                $cleanFormData = $this->categoryForm;

                // Convert empty strings to null for nullable fields
                $nullableFields = [
                    'slug', 'description', 'short_description', 'icon', 'icon_path', 'banner_path',
                    'meta_title', 'meta_description', 'meta_keywords', 'canonical_url',
                    'og_title', 'og_description', 'og_image', 'visual_settings',
                    'visibility_settings', 'default_values'
                ];

                foreach ($nullableFields as $field) {
                    if (isset($cleanFormData[$field]) && $cleanFormData[$field] === '') {
                        $cleanFormData[$field] = null;
                    }
                }

                if ($this->modalMode === 'create') {
                    // Remove id field for create
                    unset($cleanFormData['id']);
                    $category = Category::create($cleanFormData);
                    session()->flash('message', 'Kategoria została utworzona pomyślnie.');
                } else {
                    $category = Category::find($this->categoryForm['id']);
                    $category->update($cleanFormData);
                    session()->flash('message', 'Kategoria została zaktualizowana pomyślnie.');
                }

                // Auto-expand parent node
                if ($category->parent_id && !in_array($category->parent_id, $this->expandedNodes)) {
                    $this->expandedNodes[] = $category->parent_id;
                }
            });

            $this->closeModal();

        } catch (\Exception $e) {
            Log::error('CategoryTree: Error saving category', [
                'error' => $e->getMessage(),
                'form_data' => $this->categoryForm,
                'mode' => $this->modalMode
            ]);

            session()->flash('error', 'Błąd podczas zapisywania kategorii: ' . $e->getMessage());
        }

        $this->loadingStates['save'] = false;
    }

    /**
     * Delete category with confirmation
     *
     * @param int $categoryId
     */
    public function deleteCategory(int $categoryId): void
    {
        $this->loadingStates['delete'] = true;

        try {
            $category = Category::find($categoryId);

            if (!$category) {
                throw new \Exception('Kategoria nie została znaleziona.');
            }

            // Check if category has products
            if ($category->products()->count() > 0) {
                throw new \Exception('Nie można usunąć kategorii zawierającej produkty.');
            }

            // Check if category has children
            if ($category->children()->count() > 0) {
                throw new \Exception('Nie można usunąć kategorii zawierającej podkategorie.');
            }

            DB::transaction(function () use ($category) {
                $category->delete();
            });

            // Remove from selections and expanded nodes
            $this->selectedCategories = array_diff($this->selectedCategories, [$categoryId]);
            $this->expandedNodes = array_diff($this->expandedNodes, [$categoryId]);

            session()->flash('message', 'Kategoria została usunięta pomyślnie.');

        } catch (\Exception $e) {
            Log::error('CategoryTree: Error deleting category', [
                'error' => $e->getMessage(),
                'category_id' => $categoryId
            ]);

            session()->flash('error', 'Błąd podczas usuwania kategorii: ' . $e->getMessage());
        }

        $this->loadingStates['delete'] = false;
    }

    // ==========================================
    // DRAG & DROP OPERATIONS
    // ==========================================

    /**
     * Handle category reordering via drag & drop
     *
     * @param int $categoryId
     * @param int|null $newParentId
     * @param int $newSortOrder
     */
    public function reorderCategory(int $categoryId, ?int $newParentId, int $newSortOrder): void
    {
        $this->loadingStates['reorder'] = true;

        try {
            $category = Category::find($categoryId);

            if (!$category) {
                throw new \Exception('Kategoria nie została znaleziona.');
            }

            DB::transaction(function () use ($category, $newParentId, $newSortOrder) {
                // Update parent if changed
                if ($category->parent_id !== $newParentId) {
                    $category->moveTo($newParentId);
                }

                // Update sort order
                $category->sort_order = $newSortOrder;
                $category->save();

                // Update sort orders for siblings
                $this->updateSiblingOrders($newParentId, $categoryId, $newSortOrder);
            });

            session()->flash('message', 'Kolejność kategorii została zaktualizowana.');

        } catch (\Exception $e) {
            Log::error('CategoryTree: Error reordering category', [
                'error' => $e->getMessage(),
                'category_id' => $categoryId,
                'new_parent_id' => $newParentId,
                'new_sort_order' => $newSortOrder
            ]);

            session()->flash('error', 'Błąd podczas zmiany kolejności: ' . $e->getMessage());
        }

        $this->loadingStates['reorder'] = false;
    }

    /**
     * Update sort orders for sibling categories
     *
     * @param int|null $parentId
     * @param int $movedCategoryId
     * @param int $newSortOrder
     */
    private function updateSiblingOrders(?int $parentId, int $movedCategoryId, int $newSortOrder): void
    {
        $siblings = Category::where('parent_id', $parentId)
                           ->where('id', '!=', $movedCategoryId)
                           ->orderBy('sort_order')
                           ->get();

        $sortOrder = 0;
        foreach ($siblings as $sibling) {
            if ($sortOrder === $newSortOrder) {
                $sortOrder++; // Skip the position for moved category
            }

            $sibling->sort_order = $sortOrder;
            $sibling->save();
            $sortOrder++;
        }
    }

    // ==========================================
    // BULK OPERATIONS
    // ==========================================

    /**
     * Bulk activate selected categories
     */
    public function bulkActivate(): void
    {
        if (empty($this->selectedCategories)) {
            session()->flash('error', 'Nie wybrano żadnych kategorii.');
            return;
        }

        try {
            Category::whereIn('id', $this->selectedCategories)
                   ->update(['is_active' => true]);

            session()->flash('message', 'Wybrane kategorie zostały aktywowane.');
            $this->selectedCategories = [];

        } catch (\Exception $e) {
            session()->flash('error', 'Błąd podczas aktywacji kategorii: ' . $e->getMessage());
        }
    }

    /**
     * Bulk deactivate selected categories
     */
    public function bulkDeactivate(): void
    {
        if (empty($this->selectedCategories)) {
            session()->flash('error', 'Nie wybrano żadnych kategorii.');
            return;
        }

        try {
            Category::whereIn('id', $this->selectedCategories)
                   ->update(['is_active' => false]);

            session()->flash('message', 'Wybrane kategorie zostały dezaktywowane.');
            $this->selectedCategories = [];

        } catch (\Exception $e) {
            session()->flash('error', 'Błąd podczas dezaktywacji kategorii: ' . $e->getMessage());
        }
    }

    /**
     * Sort categories hierarchically so children appear directly after parents
     *
     * @param Collection $categories
     * @return Collection
     */
    private function sortCategoriesHierarchically(Collection $categories): Collection
    {
        $sortedItems = [];
        $categoryMap = $categories->keyBy('id');

        // Start with root categories (level 0)
        $rootCategories = $categories->where('level', 0)
                                   ->sortBy('sort_order')
                                   ->sortBy('name');

        foreach ($rootCategories as $rootCategory) {
            // Add the root category
            $sortedItems[] = $rootCategory;

            // Add its children recursively if expanded
            if (in_array($rootCategory->id, $this->expandedNodes)) {
                $children = $this->getChildrenRecursively($rootCategory->id, $categoryMap);
                foreach ($children as $child) {
                    $sortedItems[] = $child;
                }
            }
        }

        // Return Eloquent Collection with the same model class
        return $categories->make($sortedItems);
    }

    /**
     * Get children categories recursively based on expanded state
     *
     * @param int $parentId
     * @param Collection $categoryMap
     * @return array
     */
    private function getChildrenRecursively(int $parentId, Collection $categoryMap): array
    {
        $children = [];

        // Find direct children of this parent
        $directChildren = $categoryMap->filter(function ($category) use ($parentId) {
            return $category->parent_id === $parentId;
        })->sortBy('sort_order')->sortBy('name');

        foreach ($directChildren as $child) {
            $children[] = $child;

            // If this child is expanded, add its children too
            if (in_array($child->id, $this->expandedNodes)) {
                $grandChildren = $this->getChildrenRecursively($child->id, $categoryMap);
                $children = array_merge($children, $grandChildren);
            }
        }

        return $children;
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Close modal and reset form
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->categoryForm = [
            'parent_id' => null,
            'name' => '',
            'slug' => null,
            'description' => '',
            'short_description' => '',
            'sort_order' => 0,
            'is_active' => true,
            'is_featured' => false,
            'icon' => '',
            'icon_path' => null,
            'banner_path' => null,
            'meta_title' => '',
            'meta_description' => '',
            'meta_keywords' => '',
            'canonical_url' => '',
            'og_title' => '',
            'og_description' => '',
            'og_image' => '',
            'visual_settings' => null,
            'visibility_settings' => null,
            'default_values' => null,
        ];
    }

    /**
     * Load user preferences from session
     */
    private function loadUserPreferences(): void
    {
        $preferences = session('category_tree_preferences', []);

        $this->expandedNodes = $preferences['expanded_nodes'] ?? [];
        $this->viewMode = $preferences['view_mode'] ?? 'tree';
        $this->showActiveOnly = $preferences['show_active_only'] ?? true;
    }

    /**
     * Save user preferences to session
     */
    private function saveUserPreferences(): void
    {
        session(['category_tree_preferences' => [
            'expanded_nodes' => $this->expandedNodes,
            'view_mode' => $this->viewMode,
            'show_active_only' => $this->showActiveOnly,
        ]]);
    }

    /**
     * Expand root categories by default
     */
    private function expandRootCategories(): void
    {
        $rootCategories = Category::rootCategories()->pluck('id')->toArray();
        $this->expandedNodes = array_unique(array_merge($this->expandedNodes, $rootCategories));
    }

    /**
     * Auto-expand nodes that match search criteria
     */
    private function expandMatchingNodes(): void
    {
        if (empty($this->search)) {
            return;
        }

        $matchingCategories = Category::where('name', 'like', '%' . $this->search . '%')
                                    ->orWhere('description', 'like', '%' . $this->search . '%')
                                    ->get();

        foreach ($matchingCategories as $category) {
            // Expand all ancestors of matching categories
            $ancestors = $category->ancestors;
            foreach ($ancestors as $ancestor) {
                if (!in_array($ancestor->id, $this->expandedNodes)) {
                    $this->expandedNodes[] = $ancestor->id;
                }
            }
        }
    }

    /**
     * Get available parent categories for select options
     *
     * @param int|null $excludeId Category to exclude (for preventing circular references)
     * @return array
     */
    public function getParentOptionsProperty(): array
    {
        $excludeId = $this->modalMode === 'edit' ? $this->categoryForm['id'] ?? null : null;

        $query = Category::active()->treeOrder();

        if ($excludeId) {
            // Exclude the category itself and all its descendants
            $category = Category::find($excludeId);
            if ($category) {
                $excludeIds = [$excludeId];
                $excludeIds = array_merge($excludeIds, $category->descendants->pluck('id')->toArray());
                $query->whereNotIn('id', $excludeIds);
            }
        }

        return $query->get()->mapWithKeys(function ($category) {
            $prefix = str_repeat('— ', $category->level);
            return [$category->id => $prefix . $category->name];
        })->toArray();
    }

    // ==========================================
    // LIVEWIRE RENDER METHOD
    // ==========================================

    /**
     * Render the component
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.products.categories.category-tree-ultra-clean', [
            'categories' => $this->categories,
            'treeStructure' => $this->treeStructure,
            'parentOptions' => $this->parentOptions,
        ])->layout('layouts.admin', [
            'title' => 'Zarządzanie Kategoriami - PPM',
            'breadcrumb' => 'Kategorie produktów'
        ]);
    }

    // ==========================================
    // COMPACT VIEW ACTION METHODS
    // ==========================================

    /**
     * Toggle category active status
     *
     * @param int $categoryId
     * @param bool $newStatus
     * @return void
     */
    public function toggleStatus(int $categoryId, bool $newStatus): void
    {
        try {
            $category = Category::findOrFail($categoryId);

            $category->update([
                'is_active' => $newStatus
            ]);

            $statusText = $newStatus ? 'aktywowana' : 'dezaktywowana';
            $this->dispatch('category-updated', [
                'message' => "Kategoria '{$category->name}' została {$statusText}.",
                'type' => 'success'
            ]);

            Log::info("Category status toggled: {$category->name} -> " . ($newStatus ? 'active' : 'inactive'));

        } catch (\Exception $e) {
            Log::error("Error toggling category status: " . $e->getMessage());

            $this->dispatch('category-error', [
                'message' => 'Wystąpił błąd podczas zmiany statusu kategorii.',
                'type' => 'error'
            ]);
        }
    }

}