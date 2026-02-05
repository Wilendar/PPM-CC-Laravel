<?php

namespace App\Http\Livewire\Products\Import\Traits;

use App\Models\PendingProduct;
use App\Models\Category;
use App\Models\PrestaShopShop;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * ImportPanelCategoryShopTrait - Trait dla zarzadzania kategoriami i sklepami w pending products
 *
 * ETAP_06 FAZA 5: Edycja inline dla kategorii i sklepow
 * REDESIGN: 3 kaskadowe dropdowny (L3 -> L4 -> L5), auto-include Baza (L1) + Wszystko (L2)
 */
trait ImportPanelCategoryShopTrait
{
    /**
     * Currently editing product ID for categories
     */
    public ?int $editingCategoryProductId = null;

    /**
     * Currently editing product ID for shops
     */
    public ?int $editingShopProductId = null;

    /**
     * Selected categories for picker (temporary state)
     */
    public array $selectedCategoryIds = [];

    /**
     * Selected shops for picker (temporary state)
     */
    public array $selectedShopIds = [];

    /**
     * Cascading category picker - Level 3 selection (first selectable level)
     */
    public ?int $selectedL3 = null;

    /**
     * Cascading category picker - Level 4 selection (dependent on L3)
     */
    public ?int $selectedL4 = null;

    /**
     * Cascading category picker - Level 5 selection (dependent on L4)
     */
    public ?int $selectedL5 = null;

    /**
     * Search query for category picker (kept for backward compat)
     */
    public string $categorySearch = '';

    /**
     * Open category picker for product
     */
    public function openCategoryPicker(int $productId): void
    {
        $product = PendingProduct::find($productId);
        if (!$product) {
            return;
        }

        $this->editingCategoryProductId = $productId;
        $this->selectedCategoryIds = $product->category_ids ?? [];
        $this->categorySearch = '';
        $this->editingShopProductId = null; // Close shop picker if open

        // Initialize cascading dropdowns from existing selection
        $this->initializeCascadingFromSelection();
    }

    /**
     * Initialize cascading dropdown values from selected category IDs
     * Finds the deepest selected category and works backwards
     */
    protected function initializeCascadingFromSelection(): void
    {
        $this->selectedL3 = null;
        $this->selectedL4 = null;
        $this->selectedL5 = null;

        if (empty($this->selectedCategoryIds)) {
            return;
        }

        // Get all selected categories with their levels
        $selectedCategories = Category::whereIn('id', $this->selectedCategoryIds)
            ->where('level', '>=', 2) // L3+ (level is 0-indexed, so level 2 = L3)
            ->orderBy('level', 'desc')
            ->get();

        if ($selectedCategories->isEmpty()) {
            return;
        }

        // Take deepest category and trace back
        $deepest = $selectedCategories->first();

        if ($deepest->level === 4) { // L5
            $this->selectedL5 = $deepest->id;
            $this->selectedL4 = $deepest->parent_id;
            $parent = Category::find($deepest->parent_id);
            $this->selectedL3 = $parent?->parent_id;
        } elseif ($deepest->level === 3) { // L4
            $this->selectedL4 = $deepest->id;
            $this->selectedL3 = $deepest->parent_id;
        } elseif ($deepest->level === 2) { // L3
            $this->selectedL3 = $deepest->id;
        }
    }

    /**
     * Close category picker
     */
    public function closeCategoryPicker(): void
    {
        $this->editingCategoryProductId = null;
        $this->selectedCategoryIds = [];
        $this->categorySearch = '';
        $this->selectedL3 = null;
        $this->selectedL4 = null;
        $this->selectedL5 = null;
    }

    /**
     * Handle L3 (first visible level) selection change
     */
    public function updatedSelectedL3(): void
    {
        // Reset dependent dropdowns when L3 changes
        $this->selectedL4 = null;
        $this->selectedL5 = null;
    }

    /**
     * Handle L4 selection change
     */
    public function updatedSelectedL4(): void
    {
        // Reset L5 when L4 changes
        $this->selectedL5 = null;
    }

    /**
     * Get categories for Level 3 dropdown (first visible level)
     * These are children of Wszystko (L2)
     */
    public function getCategoriesL3(): Collection
    {
        // Find "Wszystko" category (L2, level=1) or get all level=2 categories
        $wszystkoCategory = Category::where('level', 1)
            ->where('is_active', true)
            ->first();

        if ($wszystkoCategory) {
            return Category::where('parent_id', $wszystkoCategory->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        // Fallback: get all level 2 categories
        return Category::where('level', 2)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get categories for Level 4 dropdown (dependent on L3 selection)
     */
    public function getCategoriesL4(): Collection
    {
        if (!$this->selectedL3) {
            return collect();
        }

        return Category::where('parent_id', $this->selectedL3)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get categories for Level 5 dropdown (dependent on L4 selection)
     */
    public function getCategoriesL5(): Collection
    {
        if (!$this->selectedL4) {
            return collect();
        }

        return Category::where('parent_id', $this->selectedL4)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Toggle category selection in picker (kept for backwards compat)
     */
    public function toggleCategory(int $categoryId): void
    {
        if (in_array($categoryId, $this->selectedCategoryIds)) {
            $this->selectedCategoryIds = array_values(array_filter(
                $this->selectedCategoryIds,
                fn($id) => $id !== $categoryId
            ));
        } else {
            $this->selectedCategoryIds[] = $categoryId;
        }
    }

    /**
     * Save selected categories to product
     * Auto-includes Baza (L1) and Wszystko (L2) based on cascading selection
     */
    public function saveCategories(): void
    {
        if (!$this->editingCategoryProductId) {
            return;
        }

        $product = PendingProduct::find($this->editingCategoryProductId);
        if (!$product) {
            $this->closeCategoryPicker();
            return;
        }

        try {
            // Build category IDs from cascading selection
            $categoryIds = $this->buildCategoryIdsFromCascading();

            $product->setCategories($categoryIds);

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => 'Zaktualizowano kategorie produktu',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save categories for pending product', [
                'product_id' => $this->editingCategoryProductId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad podczas zapisu kategorii',
            ]);
        }

        $this->closeCategoryPicker();
    }

    /**
     * Build complete category path from cascading selection
     * Auto-includes Baza (L1) + Wszystko (L2) + selected L3/L4/L5
     */
    protected function buildCategoryIdsFromCascading(): array
    {
        $categoryIds = [];

        // Auto-include Baza (L1, level=0) and Wszystko (L2, level=1)
        $baza = Category::where('level', 0)->where('is_active', true)->first();
        $wszystko = Category::where('level', 1)->where('is_active', true)->first();

        if ($baza) {
            $categoryIds[] = $baza->id;
        }
        if ($wszystko) {
            $categoryIds[] = $wszystko->id;
        }

        // Add selected categories from cascading dropdowns
        if ($this->selectedL3) {
            $categoryIds[] = $this->selectedL3;
        }
        if ($this->selectedL4) {
            $categoryIds[] = $this->selectedL4;
        }
        if ($this->selectedL5) {
            $categoryIds[] = $this->selectedL5;
        }

        return array_unique($categoryIds);
    }

    /**
     * Open shop picker for product
     */
    public function openShopPicker(int $productId): void
    {
        $product = PendingProduct::find($productId);
        if (!$product) {
            return;
        }

        $this->editingShopProductId = $productId;
        $this->selectedShopIds = $product->shop_ids ?? [];
        $this->editingCategoryProductId = null; // Close category picker if open
    }

    /**
     * Close shop picker
     */
    public function closeShopPicker(): void
    {
        $this->editingShopProductId = null;
        $this->selectedShopIds = [];
    }

    /**
     * Toggle shop selection in picker
     */
    public function toggleShop(int $shopId): void
    {
        if (in_array($shopId, $this->selectedShopIds)) {
            $this->selectedShopIds = array_values(array_filter(
                $this->selectedShopIds,
                fn($id) => $id !== $shopId
            ));
        } else {
            $this->selectedShopIds[] = $shopId;
        }
    }

    /**
     * Save selected shops to product
     */
    public function saveShops(): void
    {
        if (!$this->editingShopProductId) {
            return;
        }

        $product = PendingProduct::find($this->editingShopProductId);
        if (!$product) {
            $this->closeShopPicker();
            return;
        }

        try {
            $product->setShops($this->selectedShopIds);

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => 'Zaktualizowano sklepy produktu',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save shops for pending product', [
                'product_id' => $this->editingShopProductId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad podczas zapisu sklepow',
            ]);
        }

        $this->closeShopPicker();
    }

    /**
     * Get available categories for picker (searchable)
     * Returns tree structure for hierarchical display
     */
    public function getCategoriesForPicker(): Collection
    {
        $query = Category::query()
            ->where('is_active', true)
            ->orderBy('level')
            ->orderBy('sort_order')
            ->orderBy('name');

        // Apply search filter if provided
        if ($this->categorySearch) {
            $query->where('name', 'like', "%{$this->categorySearch}%");
        }

        return $query->get();
    }

    /**
     * Get root level categories (level 0 or level 1)
     */
    public function getRootCategories(): Collection
    {
        return Category::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('parent_id')
                  ->orWhere('level', 0);
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get children of category
     */
    public function getCategoryChildren(int $parentId): Collection
    {
        return Category::query()
            ->where('is_active', true)
            ->where('parent_id', $parentId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get available shops for picker
     */
    public function getAvailableShops(): Collection
    {
        return PrestaShopShop::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * REDESIGN: Set category for specific level inline (without popup)
     * Auto-handles: Baza (L1) + Wszystko (L2) auto-include
     * Clears dependent levels when parent changes
     */
    public function setCategoryForLevel(int $productId, int $level, ?int $categoryId, ?int $parentId = null): void
    {
        $product = PendingProduct::find($productId);
        if (!$product) {
            return;
        }

        try {
            $currentCats = $product->category_ids ?? [];

            // Get Baza (L1) and Wszystko (L2) for auto-include
            $baza = Category::where('level', 0)->where('is_active', true)->first();
            $wszystko = Category::where('level', 1)->where('is_active', true)->first();

            // Remove old category at this level and all dependent levels
            $dbLevel = $level - 1; // level db is 0-indexed
            $catsToRemove = Category::whereIn('id', $currentCats)
                ->where('level', '>=', $dbLevel)
                ->pluck('id')
                ->toArray();

            // If clearing a parent level, also clear children based on hierarchy
            if ($categoryId === null && $parentId) {
                // Find all descendants of removed category
                $descendants = $this->getCategoryDescendantIds($parentId);
                $catsToRemove = array_merge($catsToRemove, $descendants);
            }

            // Filter out removed categories
            $newCats = array_values(array_filter($currentCats, fn($id) => !in_array($id, $catsToRemove)));

            // Add new category if provided
            if ($categoryId) {
                $newCats[] = $categoryId;
            }

            // Auto-include Baza and Wszystko
            if ($baza && !in_array($baza->id, $newCats)) {
                array_unshift($newCats, $baza->id);
            }
            if ($wszystko && !in_array($wszystko->id, $newCats)) {
                $newCats[] = $wszystko->id;
            }

            $product->setCategories(array_unique($newCats));

            Log::debug('Category set for level', [
                'product_id' => $productId,
                'level' => $level,
                'category_id' => $categoryId,
                'new_cats' => $newCats,
            ]);

            // UI: jezeli dane dogonily wymuszony poziom, zdejmij wymuszenie
            if (method_exists($this, 'syncCategoryForcedMaxLevel')) {
                $this->syncCategoryForcedMaxLevel();
            }

        } catch (\Exception $e) {
            Log::error('Failed to set category for level', [
                'product_id' => $productId,
                'level' => $level,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get all descendant category IDs for cascade clearing
     */
    protected function getCategoryDescendantIds(int $parentId): array
    {
        $descendants = [];
        $children = Category::where('parent_id', $parentId)->pluck('id')->toArray();

        foreach ($children as $childId) {
            $descendants[] = $childId;
            $descendants = array_merge($descendants, $this->getCategoryDescendantIds($childId));
        }

        return $descendants;
    }

    /**
     * REDESIGN: Set shops for product inline (without popup)
     */
    public function setShopsForProduct(int $productId, array $shopIds): void
    {
        $product = PendingProduct::find($productId);
        if (!$product) {
            return;
        }

        try {
            $product->setShops($shopIds);

            Log::debug('Shops set for product', [
                'product_id' => $productId,
                'shop_ids' => $shopIds,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to set shops for product', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * REDESIGN: Get selected category ID for specific level
     */
    public function getSelectedCategoryForLevel(PendingProduct $product, int $level): ?int
    {
        $categoryIds = $product->category_ids ?? [];
        $dbLevel = $level - 1; // DB level is 0-indexed

        return Category::whereIn('id', $categoryIds)
            ->where('level', $dbLevel)
            ->value('id');
    }

    /**
     * REDESIGN: Get parent category ID for cascade (finds L3 for L4, L4 for L5)
     */
    public function getParentCategoryId(PendingProduct $product, int $level): ?int
    {
        if ($level <= 3) {
            return null;
        }

        $parentLevel = $level - 1;
        return $this->getSelectedCategoryForLevel($product, $parentLevel);
    }

    /**
     * ETAP_06 FAZA 5.2: Create inline category from dropdown
     * Creates new category as child of parent (or as L3 if no parent)
     * Auto-assigns to product after creation
     *
     * @param int $productId Product to assign category to
     * @param int $level Category level (3+)
     * @param int|null $parentId Parent category ID (null for L3)
     * @param string $name New category name
     * @return array|null ['id' => int, 'name' => string] or null on failure
     */
    public function createInlineCategory(int $productId, int $level, ?int $parentId, string $name): ?array
    {
        $name = trim($name);
        if (empty($name)) {
            return null;
        }

        $product = PendingProduct::find($productId);
        if (!$product) {
            Log::warning('createInlineCategory: Product not found', ['product_id' => $productId]);
            return null;
        }

        try {
            // Determine parent category
            if ($level === 3) {
                // L3 parent is Wszystko (level=1)
                $parent = Category::where('level', 1)->where('is_active', true)->first();
            } else {
                // L4+ parent is provided parentId
                $parent = $parentId ? Category::find($parentId) : null;
            }

            if (!$parent && $level > 3) {
                Log::warning('createInlineCategory: Parent not found for level > 3', [
                    'level' => $level,
                    'parent_id' => $parentId,
                ]);
                return null;
            }

            // Check for duplicate name under same parent
            $existingQuery = Category::where('name', $name)->where('is_active', true);
            if ($parent) {
                $existingQuery->where('parent_id', $parent->id);
            }
            $existing = $existingQuery->first();

            if ($existing) {
                // Use existing category instead of creating duplicate
                $this->setCategoryForLevel($productId, $level, $existing->id, $parentId);
                return ['id' => $existing->id, 'name' => $existing->name];
            }

            // Calculate DB level (level 3 = db level 2, etc.)
            $dbLevel = $level - 1;

            // Get max sort_order for siblings
            $maxSortOrder = Category::where('parent_id', $parent?->id)->max('sort_order') ?? 0;

            // Create new category
            $newCategory = Category::create([
                'name' => $name,
                'parent_id' => $parent?->id,
                'level' => $dbLevel,
                'is_active' => true,
                'sort_order' => $maxSortOrder + 1,
            ]);

            Log::info('createInlineCategory: Category created', [
                'category_id' => $newCategory->id,
                'name' => $name,
                'level' => $level,
                'db_level' => $dbLevel,
                'parent_id' => $parent?->id,
            ]);

            // Auto-assign to product
            $this->setCategoryForLevel($productId, $level, $newCategory->id, $parentId);

            return ['id' => $newCategory->id, 'name' => $newCategory->name];

        } catch (\Exception $e) {
            Log::error('createInlineCategory: Failed to create category', [
                'product_id' => $productId,
                'level' => $level,
                'parent_id' => $parentId,
                'name' => $name,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // NOTE: Bulk operations (bulkSetCategories, bulkSetShops) are in ImportPanelBulkOperations trait
}
