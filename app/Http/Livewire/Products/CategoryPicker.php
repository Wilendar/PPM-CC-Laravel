<?php

namespace App\Http\Livewire\Products;

use Livewire\Component;
use Livewire\Attributes\Modelable;
use App\Models\Category;
use Illuminate\Support\Facades\Log;

/**
 * CategoryPicker Livewire Component
 *
 * ETAP_07 FAZA 3D - ETAP 2: Category Picker UI
 *
 * Purpose: Hierarchical category tree picker with multi-select
 *
 * Features:
 * - Display categories in hierarchical tree (5 levels deep)
 * - Multi-select checkboxes with parent-child relationships
 * - Real-time search filtering
 * - "Show only selected" toggle
 * - Expand/collapse nodes (Alpine.js)
 * - Visual indentation for hierarchy
 * - Enterprise styling (dark theme)
 *
 * Usage:
 * <livewire:products.category-picker wire:model="selectedCategories" />
 *
 * @package App\Http\Livewire\Products
 * @version 1.0
 * @since ETAP_07 FAZA 3D - ETAP 2
 */
class CategoryPicker extends Component
{
    /*
    |--------------------------------------------------------------------------
    | PUBLIC PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Selected category IDs (modelable for wire:model)
     *
     * @var array
     */
    #[Modelable]
    public array $selectedCategories = [];

    /**
     * Search query for filtering categories
     *
     * @var string
     */
    public string $search = '';

    /**
     * Show only selected categories
     *
     * @var bool
     */
    public bool $showOnlySelected = false;

    /**
     * Component context (for unique wire:key generation)
     * Prevents checkbox cross-contamination between different picker instances
     *
     * @var string
     */
    public string $context = 'default';

    /**
     * Filter by shop ID (optional)
     * If provided, only show categories available for this shop
     *
     * @var int|null
     */
    public ?int $shopId = null;

    /*
    |--------------------------------------------------------------------------
    | LIVEWIRE LIFECYCLE METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Component mount
     *
     * @param string $context Unique context identifier
     * @param int|null $shopId Optional shop filter
     */
    public function mount(string $context = 'default', ?int $shopId = null): void
    {
        $this->context = $context;
        $this->shopId = $shopId;

        Log::info('🟢 CategoryPicker: MOUNTED', [
            'livewire_id' => $this->getId(),
            'context' => $this->context,
            'shop_id' => $this->shopId,
            'initial_selected' => $this->selectedCategories,
            'initial_selected_count' => count($this->selectedCategories),
            'timestamp' => now()->format('Y-m-d H:i:s.u'),
        ]);
    }

    /**
     * Dehydrate - called before every Livewire response
     * DIAGNOSTIC: Track component state before each render
     */
    public function dehydrate(): void
    {
        Log::debug('🔵 CategoryPicker: DEHYDRATE (before response)', [
            'livewire_id' => $this->getId(),
            'context' => $this->context,
            'selected_categories' => $this->selectedCategories,
            'selected_count' => count($this->selectedCategories),
            'search' => $this->search,
            'show_only_selected' => $this->showOnlySelected,
            'timestamp' => now()->format('Y-m-d H:i:s.u'),
        ]);
    }

    /**
     * Hydrate - called after Livewire request hydration
     * DIAGNOSTIC: Track component state after hydration
     */
    public function hydrate(): void
    {
        Log::debug('🟡 CategoryPicker: HYDRATE (after request)', [
            'livewire_id' => $this->getId(),
            'context' => $this->context,
            'selected_categories' => $this->selectedCategories,
            'selected_count' => count($this->selectedCategories),
            'timestamp' => now()->format('Y-m-d H:i:s.u'),
        ]);
    }

    /**
     * Updated search - reset showOnlySelected
     */
    public function updatedSearch(): void
    {
        // When user searches, show all matching (not only selected)
        if (!empty($this->search)) {
            $this->showOnlySelected = false;
        }

        Log::debug('CategoryPicker: Search updated', [
            'search' => $this->search,
            'context' => $this->context,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Toggle category selection
     *
     * CRITICAL FIX: Manual toggle to avoid wire:model issues in nested components
     *
     * @param int $categoryId PPM category ID
     */
    public function toggleCategory(int $categoryId): void
    {
        $beforeState = $this->selectedCategories;
        $wasSelected = in_array($categoryId, $this->selectedCategories, true);

        if ($wasSelected) {
            // Remove from selection
            $this->selectedCategories = array_values(
                array_filter($this->selectedCategories, fn($id) => $id !== $categoryId)
            );
            $action = 'REMOVED';
        } else {
            // Add to selection
            $this->selectedCategories[] = $categoryId;
            $action = 'ADDED';
        }

        Log::info('🔄 CategoryPicker: Category toggled', [
            'livewire_id' => $this->getId(),
            'context' => $this->context,
            'category_id' => $categoryId,
            'action' => $action,
            'before_state' => $beforeState,
            'after_state' => $this->selectedCategories,
            'before_count' => count($beforeState),
            'after_count' => count($this->selectedCategories),
            'timestamp' => now()->format('Y-m-d H:i:s.u'),
        ]);
    }

    /**
     * Check if category is selected
     *
     * @param int $categoryId
     * @return bool
     */
    public function isCategorySelected(int $categoryId): bool
    {
        return in_array($categoryId, $this->selectedCategories, true);
    }

    /**
     * Select all visible categories
     */
    public function selectAll(): void
    {
        $categories = $this->getFilteredCategoriesQuery()->get();

        foreach ($categories as $category) {
            if (!in_array($category->id, $this->selectedCategories, true)) {
                $this->selectedCategories[] = $category->id;
            }
        }

        Log::info('CategoryPicker: Selected all visible categories', [
            'count' => count($this->selectedCategories),
            'context' => $this->context,
        ]);
    }

    /**
     * Deselect all categories
     */
    public function deselectAll(): void
    {
        $this->selectedCategories = [];

        Log::info('CategoryPicker: Deselected all categories', [
            'context' => $this->context,
        ]);
    }

    /**
     * Toggle "show only selected" filter
     */
    public function toggleShowOnlySelected(): void
    {
        $this->showOnlySelected = !$this->showOnlySelected;

        Log::debug('CategoryPicker: Show only selected toggled', [
            'show_only_selected' => $this->showOnlySelected,
            'context' => $this->context,
        ]);
    }

    /**
     * Clear search
     */
    public function clearSearch(): void
    {
        $this->search = '';
        $this->showOnlySelected = false;

        Log::debug('CategoryPicker: Search cleared', [
            'context' => $this->context,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Get hierarchical category tree
     *
     * Returns categories in tree structure with filtering applied
     *
     * @return array
     */
    public function getCategoryTreeProperty(): array
    {
        $query = $this->getFilteredCategoriesQuery();

        // Get all matching categories
        $categories = $query->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        // Build hierarchical tree
        return $this->buildTree($categories);
    }

    /**
     * Get total visible category count
     *
     * @return int
     */
    public function getVisibleCountProperty(): int
    {
        return $this->getFilteredCategoriesQuery()->count();
    }

    /**
     * Get total selected count
     *
     * @return int
     */
    public function getSelectedCountProperty(): int
    {
        return count($this->selectedCategories);
    }

    /*
    |--------------------------------------------------------------------------
    | PRIVATE METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get filtered categories query
     *
     * Apply search, showOnlySelected, and shopId filters
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getFilteredCategoriesQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Category::query()->where('is_active', true);

        // Filter by shop (if provided)
        if ($this->shopId) {
            // Get categories available for this shop via ShopMapping
            $categoryIds = \App\Models\ShopMapping::where('shop_id', $this->shopId)
                ->where('mapping_type', \App\Models\ShopMapping::TYPE_CATEGORY)
                ->where('is_active', true)
                ->pluck('ppm_value') // Fixed: use 'ppm_value' instead of 'ppm_id'
                ->map(fn($val) => (int) $val) // Convert string to int (ppm_value stores category ID as string)
                ->toArray();

            if (!empty($categoryIds)) {
                $query->whereIn('id', $categoryIds);
            }
        }

        // Filter by search
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('slug', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        // Filter by selected only
        if ($this->showOnlySelected && !empty($this->selectedCategories)) {
            $query->whereIn('id', $this->selectedCategories);
        }

        return $query;
    }

    /**
     * Build hierarchical tree from flat category collection
     *
     * @param \Illuminate\Database\Eloquent\Collection $categories
     * @return array
     */
    private function buildTree($categories, ?int $parentId = null, int $currentLevel = 0): array
    {
        $tree = [];

        foreach ($categories as $category) {
            if ($category->parent_id === $parentId) {
                $node = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'parent_id' => $category->parent_id,
                    'level' => $currentLevel, // Use passed level instead of calculating
                    'is_active' => $category->is_active,
                    'children' => $this->buildTree($categories, $category->id, $currentLevel + 1),
                    'has_children' => false, // Will be set below
                ];

                $node['has_children'] = !empty($node['children']);

                Log::debug('CategoryPicker: Building tree node', [
                    'category_id' => $category->id,
                    'name' => $category->name,
                    'parent_id' => $category->parent_id,
                    'level' => $currentLevel,
                    'has_children' => $node['has_children'],
                ]);

                $tree[] = $node;
            }
        }

        return $tree;
    }

    /**
     * Calculate category level in hierarchy
     *
     * @param Category $category
     * @param \Illuminate\Database\Eloquent\Collection $allCategories
     * @return int
     */
    private function getCategoryLevel(Category $category, $allCategories): int
    {
        $level = 0;
        $currentId = $category->parent_id;

        while ($currentId !== null && $level < 10) { // Max 10 levels to prevent infinite loop
            $parent = $allCategories->firstWhere('id', $currentId);
            if (!$parent) {
                break;
            }
            $level++;
            $currentId = $parent->parent_id;
        }

        return $level;
    }

    /*
    |--------------------------------------------------------------------------
    | COMPONENT RENDER
    |--------------------------------------------------------------------------
    */

    /**
     * Render the component
     */
    public function render()
    {
        $tree = $this->categoryTree;
        $visible = $this->visibleCount;
        $selected = $this->selectedCount;

        Log::debug('🎨 CategoryPicker: RENDER', [
            'livewire_id' => $this->getId(),
            'context' => $this->context,
            'selected_categories' => $this->selectedCategories,
            'selected_count' => $selected,
            'visible_count' => $visible,
            'tree_nodes' => count($tree),
            'search' => $this->search,
            'show_only_selected' => $this->showOnlySelected,
            'timestamp' => now()->format('Y-m-d H:i:s.u'),
        ]);

        return view('livewire.products.category-picker', [
            'categoryTree' => $tree,
            'visibleCount' => $visible,
            'selectedCount' => $selected,
        ]);
    }
}
