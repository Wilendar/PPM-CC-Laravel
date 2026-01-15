<?php

namespace App\Http\Livewire\Products\Categories;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
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

    /**
     * Force Delete Modal state
     *
     * @var bool
     */
    public $showForceDeleteModal = false;

    /**
     * Category ID to delete (for force delete modal)
     *
     * @var int|null
     */
    public $categoryToDelete = null;

    /**
     * Delete warnings for force delete modal
     *
     * @var array
     */
    public $deleteWarnings = [];

    /**
     * Job ID for tracking delete progress (UUID string)
     *
     * @var string|null
     */
    public $deleteJobId = null;

    /**
     * Progress ID for JobProgressBar (database ID)
     *
     * @var int|null
     */
    public $deleteProgressId = null;

    /**
     * Show category merge modal
     *
     * @var bool
     */
    public $showMergeCategoriesModal = false;

    /**
     * Source category ID for merge (kategoria do usunięcia)
     *
     * @var int|null
     */
    public $sourceCategoryId = null;

    /**
     * Target category ID for merge (kategoria docelowa)
     *
     * @var int|null
     */
    public $targetCategoryId = null;

    /**
     * Merge warnings (produkty, podkategorie)
     *
     * @var array
     */
    public $mergeWarnings = [];

    /**
     * Show bulk delete confirmation modal
     *
     * @var bool
     */
    public $showBulkDeleteModal = false;

    /**
     * Warnings for bulk delete (products, children)
     *
     * @var array
     */
    public $bulkDeleteWarnings = [];

    /**
     * Categories to bulk delete (after confirmation)
     *
     * @var array
     */
    public $categoriesToBulkDelete = [];

    /**
     * Force bulk delete (include categories with children/products)
     *
     * @var bool
     */
    public $forceBulkDelete = false;

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

    /**
     * Toggle select/deselect all visible categories
     *
     * CRITICAL: This method provides deterministic toggle behavior
     * - If ANY categories are selected → deselect ALL
     * - If NO categories are selected → select ALL
     *
     * This prevents the "random checkbox" bug caused by:
     * - Conditional wire:click that checks count equality
     * - Race conditions between UI state and Livewire state
     */
    public function toggleSelectAll(): void
    {
        // Get current visible category IDs
        $visibleCategoryIds = $this->categories->pluck('id')->toArray();
        $totalVisible = count($visibleCategoryIds);
        $currentlySelected = count($this->selectedCategories);

        // Correct logic: if ALL are selected → deselect all, otherwise → select all
        // This ensures "Select All" always selects everything unless everything is already selected
        if ($currentlySelected === $totalVisible && $totalVisible > 0) {
            // All are selected - deselect everything
            $this->selectedCategories = [];
        } else {
            // Not all selected (including partial selection) - select all visible
            $this->selectedCategories = $visibleCategoryIds;
        }
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
     * Save category from inline form (quick add)
     * FAZA 2 ETAP_15 - Inline Insert functionality
     *
     * @param string $name
     * @param string $description
     * @param int|null $parentId
     */
    public function saveInlineCategory(string $name, string $description, ?int $parentId = null): void
    {
        $name = trim($name);

        if (empty($name)) {
            session()->flash('error', 'Nazwa kategorii jest wymagana.');
            return;
        }

        try {
            // Validate max depth
            if ($parentId) {
                $parent = Category::find($parentId);
                if ($parent && $parent->level >= Category::MAX_LEVEL) {
                    session()->flash('error', 'Nie można utworzyć kategorii - przekroczono maksymalną głębokość drzewa.');
                    return;
                }
            }

            DB::transaction(function () use ($name, $description, $parentId) {
                $category = Category::create([
                    'parent_id' => $parentId,
                    'name' => $name,
                    'description' => !empty($description) ? $description : null,
                    'is_active' => true,
                    'sort_order' => 0,
                ]);

                // Auto-expand parent node to show new category
                if ($category->parent_id && !in_array($category->parent_id, $this->expandedNodes)) {
                    $this->expandedNodes[] = $category->parent_id;
                }

                session()->flash('message', "Kategoria \"{$name}\" została utworzona.");
            });

        } catch (\Exception $e) {
            Log::error('saveInlineCategory error', [
                'name' => $name,
                'parentId' => $parentId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Błąd podczas tworzenia kategorii: ' . $e->getMessage());
        }
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
            $category = Category::with(['products', 'children'])->find($categoryId);

            if (!$category) {
                throw new \Exception('Kategoria nie została znaleziona.');
            }

            // Check if category has products or children - show Force Delete Modal
            if ($category->products()->count() > 0 || $category->children()->count() > 0) {
                $this->showForceDeleteConfirmation($categoryId);
                $this->loadingStates['delete'] = false;
                return;
            }

            // Safe to delete - use forceDelete to permanently remove from DB
            DB::transaction(function () use ($category) {
                $category->forceDelete();
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

    /**
     * Show force delete confirmation modal with warnings
     *
     * @param int $categoryId
     * @return void
     */
    public function showForceDeleteConfirmation(int $categoryId): void
    {
        $category = Category::with(['products', 'children'])->find($categoryId);

        if (!$category) {
            session()->flash('error', 'Kategoria nie została znaleziona.');
            return;
        }

        $this->categoryToDelete = $category->id;
        $this->deleteWarnings = [];

        // Collect warnings
        if ($category->products()->count() > 0) {
            $this->deleteWarnings[] = 'Kategoria zawiera ' . $category->products()->count() . ' produktów. Przypisania do tej kategorii zostaną usunięte.';
        }

        if ($category->children()->count() > 0) {
            $childrenCount = $category->children->count();
            $descendantsCount = $category->descendants->count();

            if ($descendantsCount > $childrenCount) {
                $this->deleteWarnings[] = "Kategoria zawiera {$childrenCount} bezpośrednich podkategorii i łącznie {$descendantsCount} wszystkich potomków. Zostaną one również usunięte wraz z produktami.";
            } else {
                $this->deleteWarnings[] = "Kategoria zawiera {$childrenCount} podkategorii. Zostaną one również usunięte wraz z produktami.";
            }
        }

        $this->showForceDeleteModal = true;
    }

    /**
     * Force delete category with products/children
     *
     * @return void
     */
    public function confirmForceDelete(): void
    {
        if (!$this->categoryToDelete) {
            return;
        }

        // Generate unique job ID for progress tracking
        $this->deleteJobId = (string) \Illuminate\Support\Str::uuid();

        // 🚀 CRITICAL: Create PENDING progress record BEFORE dispatch
        // This ensures progress bar appears IMMEDIATELY when user clicks confirm
        // Wire:poll will detect it within 3s without timing issues
        $category = Category::find($this->categoryToDelete);
        $totalCount = 0;

        if ($category) {
            // Calculate total: category + descendants
            $totalCount = 1 + $category->descendants->count();
        }

        // Create PENDING progress (manually because no shop context for category deletion)
        $progress = \App\Models\JobProgress::create([
            'job_id' => $this->deleteJobId,
            'job_type' => 'category_delete',
            'shop_id' => null, // No shop context for category deletion
            'status' => 'pending',
            'current_count' => 0,
            'total_count' => $totalCount,
            'error_count' => 0,
            'error_details' => [],
            'started_at' => now(),
        ]);

        // CRITICAL: Save progress_id for JobProgressBar
        $this->deleteProgressId = $progress->id;

        Log::info('CategoryTree: Created PENDING progress for category deletion', [
            'job_id' => $this->deleteJobId,
            'progress_id' => $this->deleteProgressId,
            'category_id' => $this->categoryToDelete,
            'total_count' => $totalCount,
        ]);

        // Dispatch BulkDeleteCategoriesJob with force=true
        \App\Jobs\Categories\BulkDeleteCategoriesJob::dispatch(
            [$this->categoryToDelete],
            true, // force delete
            $this->deleteJobId
        );

        // Close modal
        $this->cancelForceDelete();

        // Show info message
        session()->flash('info', 'Proces usuwania kategorii rozpoczęty. Postęp zobaczysz poniżej.');
    }

    /**
     * Cancel force delete and close modal
     *
     * @return void
     */
    public function cancelForceDelete(): void
    {
        $this->showForceDeleteModal = false;
        $this->categoryToDelete = null;
        $this->deleteWarnings = [];
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
     * Bulk delete selected categories
     *
     * Business Logic:
     * - Only categories without products can be deleted
     * - Only categories without children can be deleted
     * - Cascade delete descendants via model boot event
     */
    public function bulkDelete(): void
    {
        if (empty($this->selectedCategories)) {
            session()->flash('error', 'Nie wybrano żadnych kategorii.');
            return;
        }

        try {
            $cannotDelete = [];
            $deleted = 0;

            DB::transaction(function () use (&$cannotDelete, &$deleted) {
                foreach ($this->selectedCategories as $categoryId) {
                    $category = Category::find($categoryId);

                    if (!$category) {
                        continue;
                    }

                    // Check if category has products
                    if ($category->products()->count() > 0) {
                        $cannotDelete[] = $category->name . ' (zawiera produkty)';
                        continue;
                    }

                    // Check if category has children
                    if ($category->children()->count() > 0) {
                        $cannotDelete[] = $category->name . ' (zawiera podkategorie)';
                        continue;
                    }

                    // Safe to delete - use forceDelete to permanently remove from DB
                    $category->forceDelete();
                    $deleted++;
                }
            });

            // Build result message
            $message = '';
            if ($deleted > 0) {
                $message .= "Usunięto {$deleted} kategorii. ";
            }
            if (!empty($cannotDelete)) {
                $message .= "Nie można usunąć: " . implode(', ', $cannotDelete);
            }

            if ($deleted > 0) {
                session()->flash('message', $message);
            } else {
                session()->flash('error', $message ?: 'Nie udało się usunąć żadnej kategorii.');
            }

            $this->selectedCategories = [];

        } catch (\Exception $e) {
            Log::error('CategoryTree: Error bulk deleting categories', [
                'error' => $e->getMessage(),
                'selected_categories' => $this->selectedCategories
            ]);

            session()->flash('error', 'Błąd podczas usuwania kategorii: ' . $e->getMessage());
        }
    }

    /**
     * Show bulk delete confirmation modal with warnings
     *
     * Analyzes selected categories and shows modal with:
     * - List of categories to delete
     * - Warnings about products/children
     * - Option to force delete (with children)
     * - Confirmation button
     */
    public function showBulkDeleteConfirmation(): void
    {
        if (empty($this->selectedCategories)) {
            session()->flash('error', 'Nie wybrano zadnych kategorii.');
            return;
        }

        $this->bulkDeleteWarnings = [];
        $this->categoriesToBulkDelete = [];
        $this->forceBulkDelete = false; // Reset force flag

        foreach ($this->selectedCategories as $categoryId) {
            $category = Category::withCount(['products', 'children'])->find($categoryId);

            if (!$category) {
                continue;
            }

            // Count all descendants (not just direct children)
            $descendantsCount = $category->descendants->count();

            $warning = [
                'id' => $category->id,
                'name' => $category->name,
                'level' => $category->level,
                'products_count' => $category->products_count,
                'children_count' => $category->children_count,
                'descendants_count' => $descendantsCount,
                'can_delete' => ($category->products_count == 0 && $category->children_count == 0),
            ];

            $this->bulkDeleteWarnings[] = $warning;
            $this->categoriesToBulkDelete[] = $categoryId;
        }

        $this->showBulkDeleteModal = true;
    }

    /**
     * Confirm bulk delete - execute permanent deletion
     *
     * If forceBulkDelete is true, dispatches BulkDeleteCategoriesJob
     * which handles cascade deletion of children and products.
     */
    public function confirmBulkDelete(): void
    {
        $this->showBulkDeleteModal = false;

        if ($this->forceBulkDelete) {
            // Use Job for force delete (handles children recursively)
            $this->executeForceBulkDelete();
        } else {
            // Standard bulk delete (only empty categories)
            $this->bulkDelete();
        }

        // Clear warnings and state
        $this->bulkDeleteWarnings = [];
        $this->categoriesToBulkDelete = [];
        $this->forceBulkDelete = false;
    }

    /**
     * Execute force bulk delete via Job
     *
     * Uses BulkDeleteCategoriesJob to handle cascade deletion
     * of categories with children and products.
     */
    private function executeForceBulkDelete(): void
    {
        if (empty($this->categoriesToBulkDelete)) {
            session()->flash('error', 'Nie wybrano żadnych kategorii do usunięcia.');
            return;
        }

        try {
            // Generate unique job ID for progress tracking
            $jobId = (string) \Illuminate\Support\Str::uuid();

            // Calculate total count (categories + all descendants)
            $totalCount = 0;
            foreach ($this->categoriesToBulkDelete as $categoryId) {
                $category = Category::find($categoryId);
                if ($category) {
                    $totalCount += 1 + $category->descendants->count();
                }
            }

            // Create PENDING progress record
            $progress = \App\Models\JobProgress::create([
                'job_id' => $jobId,
                'job_type' => 'category_delete',
                'shop_id' => null,
                'status' => 'pending',
                'current_count' => 0,
                'total_count' => $totalCount,
                'error_count' => 0,
                'error_details' => [],
                'started_at' => now(),
            ]);

            // Save progress ID for UI
            $this->deleteProgressId = $progress->id;

            Log::info('CategoryTree: Starting force bulk delete', [
                'job_id' => $jobId,
                'progress_id' => $progress->id,
                'categories' => $this->categoriesToBulkDelete,
                'total_count' => $totalCount,
            ]);

            // Dispatch Job with force=true
            \App\Jobs\Categories\BulkDeleteCategoriesJob::dispatch(
                $this->categoriesToBulkDelete,
                true, // force delete
                $jobId
            );

            // Clear selection
            $this->selectedCategories = [];

            session()->flash('info', "Proces usuwania {$totalCount} kategorii rozpoczęty. Postęp zobaczysz poniżej.");

        } catch (\Exception $e) {
            Log::error('CategoryTree: Error starting force bulk delete', [
                'error' => $e->getMessage(),
                'categories' => $this->categoriesToBulkDelete
            ]);

            session()->flash('error', 'Błąd podczas uruchamiania usuwania: ' . $e->getMessage());
        }
    }

    /**
     * Cancel bulk delete - close modal
     */
    public function cancelBulkDelete(): void
    {
        $this->showBulkDeleteModal = false;
        $this->bulkDeleteWarnings = [];
        $this->categoriesToBulkDelete = [];
        $this->forceBulkDelete = false;
    }

    /**
     * Bulk move selected categories to new parent
     *
     * @param int|null $newParentId New parent category ID (null = move to root)
     */
    public function bulkMove(?int $newParentId = null): void
    {
        if (empty($this->selectedCategories)) {
            session()->flash('error', 'Nie wybrano żadnych kategorii.');
            return;
        }

        try {
            $cannotMove = [];
            $moved = 0;

            DB::transaction(function () use ($newParentId, &$cannotMove, &$moved) {
                foreach ($this->selectedCategories as $categoryId) {
                    $category = Category::find($categoryId);

                    if (!$category) {
                        continue;
                    }

                    // Cannot move category to itself
                    if ($newParentId === $categoryId) {
                        $cannotMove[] = $category->name . ' (nie można przenieść do siebie)';
                        continue;
                    }

                    // Cannot move to own descendant
                    if ($newParentId && $category->isAncestorOf($newParentId)) {
                        $cannotMove[] = $category->name . ' (nie można przenieść do potomka)';
                        continue;
                    }

                    // Check max depth
                    if ($newParentId) {
                        $newParent = Category::find($newParentId);
                        if ($newParent) {
                            $maxDescendantLevel = $category->getMaxDescendantLevel();
                            $wouldBeLevel = $newParent->level + 1;
                            $finalLevel = $wouldBeLevel + $maxDescendantLevel;

                            if ($finalLevel > Category::MAX_LEVEL) {
                                $cannotMove[] = $category->name . ' (przekroczono maksymalną głębokość)';
                                continue;
                            }
                        }
                    }

                    // Safe to move
                    $category->moveTo($newParentId);
                    $moved++;
                }
            });

            // Build result message
            $message = '';
            if ($moved > 0) {
                $message .= "Przeniesiono {$moved} kategorii. ";
            }
            if (!empty($cannotMove)) {
                $message .= "Nie można przenieść: " . implode(', ', $cannotMove);
            }

            if ($moved > 0) {
                session()->flash('message', $message);
            } else {
                session()->flash('error', $message ?: 'Nie udało się przenieść żadnej kategorii.');
            }

            $this->selectedCategories = [];

        } catch (\Exception $e) {
            Log::error('CategoryTree: Error bulk moving categories', [
                'error' => $e->getMessage(),
                'selected_categories' => $this->selectedCategories,
                'new_parent_id' => $newParentId
            ]);

            session()->flash('error', 'Błąd podczas przenoszenia kategorii: ' . $e->getMessage());
        }
    }

    /**
     * Bulk export selected categories to CSV
     *
     * Format: CSV with category data (id, name, parent, level, products_count)
     * Download: Browser download as categories_export_YYYY-MM-DD.csv
     */
    public function bulkExport(): void
    {
        if (empty($this->selectedCategories)) {
            session()->flash('error', 'Nie wybrano żadnych kategorii.');
            return;
        }

        try {
            $categories = Category::whereIn('id', $this->selectedCategories)
                                 ->withCount('products')
                                 ->with('parent')
                                 ->orderBy('level')
                                 ->orderBy('name')
                                 ->get();

            // Build CSV content
            $csv = "ID,Nazwa,Kategoria nadrzędna,Poziom,Produktów,Slug,Status,Sortowanie\n";

            foreach ($categories as $category) {
                $csv .= sprintf(
                    "%d,%s,%s,%d,%d,%s,%s,%d\n",
                    $category->id,
                    $this->escapeCsv($category->name),
                    $this->escapeCsv($category->parent?->name ?? 'ROOT'),
                    $category->level,
                    $category->products_count,
                    $this->escapeCsv($category->slug ?? ''),
                    $category->is_active ? 'Aktywna' : 'Nieaktywna',
                    $category->sort_order
                );
            }

            // Generate filename with timestamp
            $filename = 'categories_export_' . date('Y-m-d_His') . '.csv';

            // Dispatch browser download event
            $this->dispatch('download-csv', [
                'filename' => $filename,
                'content' => $csv
            ]);

            session()->flash('message', "Wyeksportowano {$categories->count()} kategorii.");

        } catch (\Exception $e) {
            Log::error('CategoryTree: Error bulk exporting categories', [
                'error' => $e->getMessage(),
                'selected_categories' => $this->selectedCategories
            ]);

            session()->flash('error', 'Błąd podczas eksportu kategorii: ' . $e->getMessage());
        }
    }

    /**
     * Escape CSV field values
     *
     * @param string $value
     * @return string
     */
    private function escapeCsv(string $value): string
    {
        // Escape double quotes
        $value = str_replace('"', '""', $value);

        // Wrap in quotes if contains comma, newline, or quote
        if (strpos($value, ',') !== false || strpos($value, "\n") !== false || strpos($value, '"') !== false) {
            $value = '"' . $value . '"';
        }

        return $value;
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
    // CATEGORY MERGE OPERATIONS
    // ==========================================

    /**
     * Open category merge modal
     *
     * Collects warnings about products and child categories that will be affected
     * by the merge operation. Source category will be deleted after merge.
     *
     * @param int $sourceCategoryId Category to merge FROM (will be deleted)
     * @return void
     */
    public function openCategoryMergeModal(int $sourceCategoryId): void
    {
        try {
            $sourceCategory = Category::with(['products', 'children'])
                                     ->withCount(['products', 'children'])
                                     ->find($sourceCategoryId);

            if (!$sourceCategory) {
                session()->flash('error', 'Kategoria źródłowa nie została znaleziona.');
                return;
            }

            $this->sourceCategoryId = $sourceCategoryId;
            $this->targetCategoryId = null; // Reset target selection
            $this->mergeWarnings = [];

            // Collect warnings about affected data
            if ($sourceCategory->products_count > 0) {
                $this->mergeWarnings[] = "Kategoria zawiera {$sourceCategory->products_count} produktów, które zostaną przeniesione do kategorii docelowej.";
            }

            if ($sourceCategory->children_count > 0) {
                $descendantsCount = $sourceCategory->descendants->count();

                if ($descendantsCount > $sourceCategory->children_count) {
                    $this->mergeWarnings[] = "Kategoria zawiera {$sourceCategory->children_count} bezpośrednich podkategorii i łącznie {$descendantsCount} wszystkich potomków. Zostaną przeniesione do kategorii docelowej.";
                } else {
                    $this->mergeWarnings[] = "Kategoria zawiera {$sourceCategory->children_count} podkategorii. Zostaną przeniesione do kategorii docelowej.";
                }
            }

            if (empty($this->mergeWarnings)) {
                $this->mergeWarnings[] = "Kategoria jest pusta (brak produktów i podkategorii).";
            }

            $this->showMergeCategoriesModal = true;

            Log::info('CategoryTree: Opened merge modal', [
                'source_category_id' => $sourceCategoryId,
                'source_category_name' => $sourceCategory->name,
                'products_count' => $sourceCategory->products_count,
                'children_count' => $sourceCategory->children_count,
            ]);

        } catch (\Exception $e) {
            Log::error('CategoryTree: Error opening merge modal', [
                'source_category_id' => $sourceCategoryId,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Błąd podczas otwierania okna łączenia kategorii: ' . $e->getMessage());
        }
    }

    /**
     * Close category merge modal and reset state
     *
     * @return void
     */
    public function closeCategoryMergeModal(): void
    {
        $this->showMergeCategoriesModal = false;
        $this->sourceCategoryId = null;
        $this->targetCategoryId = null;
        $this->mergeWarnings = [];
    }

    /**
     * Merge source category into target category
     *
     * Business Logic:
     * 1. Move all products from source to target
     * 2. Update primary category for products if needed
     * 3. Move all children (subcategories) from source to target
     * 4. Delete source category
     *
     * Validation:
     * - Source and target must be different
     * - Target cannot be descendant of source (circular reference)
     * - Children of source must fit within max level after move
     *
     * Error Handling:
     * - Continue-on-error for product operations
     * - Transaction for atomicity
     * - Detailed logging
     *
     * @return void
     */
    public function mergeCategories(): void
    {
        try {
            // Validation: Check if both categories are selected
            if (!$this->sourceCategoryId || !$this->targetCategoryId) {
                session()->flash('error', 'Wybierz kategorię źródłową i docelową.');
                return;
            }

            // Validation: Source and target must be different
            if ($this->sourceCategoryId === $this->targetCategoryId) {
                session()->flash('error', 'Kategoria źródłowa i docelowa muszą być różne.');
                return;
            }

            // Load categories with relationships
            $sourceCategory = Category::with(['products', 'children'])
                                     ->find($this->sourceCategoryId);

            $targetCategory = Category::find($this->targetCategoryId);

            if (!$sourceCategory || !$targetCategory) {
                session()->flash('error', 'Jedna z wybranych kategorii nie została znaleziona.');
                return;
            }

            // Validation: Target cannot be descendant of source (circular reference)
            if ($sourceCategory->isAncestorOf($this->targetCategoryId)) {
                session()->flash('error', 'Nie można połączyć kategorii z własnym potomkiem (zapętlenie).');
                return;
            }

            // Validation: Check max level for children
            if ($sourceCategory->children()->count() > 0) {
                $maxDescendantLevel = $sourceCategory->getMaxDescendantLevel();
                $wouldBeLevel = $targetCategory->level + 1; // Children will be at target's level + 1
                $finalLevel = $wouldBeLevel + $maxDescendantLevel;

                if ($finalLevel > Category::MAX_LEVEL) {
                    session()->flash('error', "Nie można połączyć kategorii - przekroczono maksymalną głębokość drzewa (poziom {$finalLevel} > " . Category::MAX_LEVEL . ").");
                    return;
                }
            }

            // Execute merge in transaction
            $processed = 0;
            $errors = [];

            DB::transaction(function () use ($sourceCategory, $targetCategory, &$processed, &$errors) {
                // 1. Move all products from source to target (continue-on-error)
                $products = $sourceCategory->products;

                foreach ($products as $product) {
                    try {
                        // Check if product already has target category (global, not per-shop)
                        $hasTargetCategory = $product->categories()
                                                    ->wherePivotNull('shop_id')
                                                    ->where('categories.id', $targetCategory->id)
                                                    ->exists();

                        if (!$hasTargetCategory) {
                            // Attach target category (global)
                            $product->categories()->attach($targetCategory->id, ['shop_id' => null]);
                        }

                        // Detach source category (global)
                        $product->categories()
                               ->wherePivotNull('shop_id')
                               ->detach($sourceCategory->id);

                        // Update primary category if source was primary
                        if ($product->primary_category_id === $sourceCategory->id) {
                            $product->primary_category_id = $targetCategory->id;
                            $product->save();
                        }

                        $processed++;

                    } catch (\Exception $e) {
                        $errors[] = "Product ID {$product->id}: {$e->getMessage()}";

                        Log::error('CategoryMerge: Error processing product', [
                            'product_id' => $product->id,
                            'product_sku' => $product->sku ?? 'N/A',
                            'source_category_id' => $sourceCategory->id,
                            'target_category_id' => $targetCategory->id,
                            'error' => $e->getMessage(),
                        ]);

                        // Continue with next product (continue-on-error)
                        continue;
                    }
                }

                // 2. Move all children (subcategories) from source to target
                $children = Category::where('parent_id', $sourceCategory->id)->get();

                foreach ($children as $child) {
                    try {
                        $child->parent_id = $targetCategory->id;
                        $child->save();

                        // Refresh path/level (Category model handles this in boot)
                        $child->refresh();

                    } catch (\Exception $e) {
                        $errors[] = "Child category ID {$child->id}: {$e->getMessage()}";

                        Log::error('CategoryMerge: Error moving child category', [
                            'child_category_id' => $child->id,
                            'child_category_name' => $child->name,
                            'source_category_id' => $sourceCategory->id,
                            'target_category_id' => $targetCategory->id,
                            'error' => $e->getMessage(),
                        ]);

                        throw $e; // Stop transaction for critical children errors
                    }
                }

                // 3. Delete source category permanently (safe now - no products/children)
                $sourceCategory->forceDelete();
            });

            // Remove from selections and expanded nodes
            $this->selectedCategories = array_diff($this->selectedCategories, [$this->sourceCategoryId]);
            $this->expandedNodes = array_diff($this->expandedNodes, [$this->sourceCategoryId]);

            // Auto-expand target parent to show merged children
            if (!in_array($targetCategory->id, $this->expandedNodes)) {
                $this->expandedNodes[] = $targetCategory->id;
            }

            // Close modal
            $this->closeCategoryMergeModal();

            // User feedback
            if (empty($errors)) {
                session()->flash('message', "Połączono kategorie: {$sourceCategory->name} → {$targetCategory->name}. Przeniesiono {$processed} produktów.");
            } else {
                $errorSummary = implode('; ', array_slice($errors, 0, 3)); // Show max 3 errors
                $moreErrors = count($errors) > 3 ? ' (i ' . (count($errors) - 3) . ' więcej)' : '';

                session()->flash('warning', "Połączono kategorie, ale wystąpiły błędy: {$errorSummary}{$moreErrors}. Przeniesiono {$processed} produktów.");
            }

            Log::info('CategoryTree: Categories merged successfully', [
                'source_category_id' => $sourceCategory->id,
                'source_category_name' => $sourceCategory->name,
                'target_category_id' => $targetCategory->id,
                'target_category_name' => $targetCategory->name,
                'products_processed' => $processed,
                'errors_count' => count($errors),
            ]);

        } catch (\Exception $e) {
            Log::error('CategoryTree: Error merging categories', [
                'source_category_id' => $this->sourceCategoryId,
                'target_category_id' => $this->targetCategoryId,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Błąd podczas łączenia kategorii: ' . $e->getMessage());
        }
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
    // EVENT LISTENERS
    // ==========================================

    /**
     * Refresh category tree after delete job completes
     *
     * Listens to 'progress-completed' event dispatched by JobProgressBar
     * when category delete job finishes (completed or failed)
     *
     * WORKFLOW:
     * 1. JobProgressBar detects job completed (status: completed/failed)
     * 2. Dispatches 'progress-completed' event with progressId (job_id)
     * 3. CategoryTree receives event and checks if it's category_delete type
     * 4. Refreshes component to show updated tree (deleted categories removed)
     *
     * @param int $progressId JobProgress record ID
     * @return void
     */
    #[On('progress-completed')]
    public function refreshAfterDelete(int $progressId): void
    {
        try {
            // Get job progress to check if it's category deletion
            $progress = \App\Models\JobProgress::find($progressId);

            if (!$progress) {
                Log::debug('CategoryTree: Progress record not found', ['progress_id' => $progressId]);
                return;
            }

            // Only refresh if this was a category deletion job
            if ($progress->job_type !== 'category_delete') {
                return;
            }

            Log::info('CategoryTree: Refreshing after category deletion', [
                'progress_id' => $progressId,
                'job_id' => $progress->job_id,
                'status' => $progress->status,
            ]);

            // Clear deleted categories from selection and expanded nodes
            $this->selectedCategories = [];

            // Refresh expanded nodes - remove any that no longer exist
            if (!empty($this->expandedNodes)) {
                $existingCategories = Category::whereIn('id', $this->expandedNodes)->pluck('id')->toArray();
                $this->expandedNodes = $existingCategories;
            }

            // Force component refresh by resetting cached properties
            unset($this->categories);

            // Show success message if job completed successfully
            if ($progress->status === 'completed') {
                $deletedCount = $progress->current_count ?? 0;
                session()->flash('message', "Pomyślnie usunięto {$deletedCount} kategorii wraz z produktami.");
            } else if ($progress->status === 'failed') {
                session()->flash('error', 'Wystąpił błąd podczas usuwania kategorii. Sprawdź logi.');
            }

            // Save updated preferences
            $this->saveUserPreferences();

        } catch (\Exception $e) {
            Log::error('CategoryTree: Error refreshing after delete', [
                'progress_id' => $progressId,
                'error' => $e->getMessage(),
            ]);
        }
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