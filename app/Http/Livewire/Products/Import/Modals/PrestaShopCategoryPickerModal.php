<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\Import\Modals;

use App\Models\PendingProduct;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopCategoryService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * PrestaShop Category Picker Modal
 *
 * FAZA 9.7b Feature #8 - Select PrestaShop categories per shop for import.
 *
 * Features:
 * - Multi-select category tree (checkboxes)
 * - Category search/filter
 * - Refresh cache button
 * - Save selected categories to PendingProduct.shop_categories
 *
 * @package App\Http\Livewire\Products\Import\Modals
 */
class PrestaShopCategoryPickerModal extends Component
{
    /**
     * Modal visibility state
     */
    public bool $isOpen = false;

    /**
     * Current product ID
     */
    public ?int $productId = null;

    /**
     * Current shop ID
     */
    public ?int $shopId = null;

    /**
     * Current shop name (for display)
     */
    public string $shopName = '';

    /**
     * Selected category IDs (PrestaShop category IDs)
     */
    public array $selectedCategoryIds = [];

    /**
     * Search query for filtering categories
     */
    public string $searchQuery = '';

    /**
     * Loading state for refresh
     */
    public bool $isLoading = false;

    /**
     * Injected service
     */
    protected PrestaShopCategoryService $categoryService;

    /**
     * Boot method - inject dependencies
     */
    public function boot(PrestaShopCategoryService $categoryService): void
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Open modal for specific product and shop
     *
     * @param int $productId PendingProduct ID
     * @param int $shopId PrestaShopShop ID
     */
    #[On('openPrestaShopCategoryPicker')]
    public function open(int $productId, int $shopId): void
    {
        $this->productId = $productId;
        $this->shopId = $shopId;
        $this->searchQuery = '';
        $this->isLoading = false;

        // Load shop name
        $shop = PrestaShopShop::find($shopId);
        $this->shopName = $shop?->name ?? 'PrestaShop Shop';

        // Load current selected categories from product
        $product = PendingProduct::find($productId);
        $shopCategories = $product?->shop_categories ?? [];
        $this->selectedCategoryIds = $shopCategories[(string)$shopId] ?? [];

        // Ensure array of integers
        $this->selectedCategoryIds = array_map('intval', $this->selectedCategoryIds);

        Log::debug('PrestaShopCategoryPickerModal: opened', [
            'product_id' => $productId,
            'shop_id' => $shopId,
            'selected_categories' => $this->selectedCategoryIds,
        ]);

        $this->isOpen = true;
    }

    /**
     * Close modal
     */
    public function close(): void
    {
        $this->isOpen = false;
        $this->productId = null;
        $this->shopId = null;
        $this->selectedCategoryIds = [];
        $this->searchQuery = '';
    }

    /**
     * Toggle category selection
     *
     * @param int $categoryId PrestaShop category ID
     */
    public function toggleCategory(int $categoryId): void
    {
        if (in_array($categoryId, $this->selectedCategoryIds)) {
            $this->selectedCategoryIds = array_values(
                array_filter($this->selectedCategoryIds, fn($id) => $id !== $categoryId)
            );
        } else {
            $this->selectedCategoryIds[] = $categoryId;
        }

        Log::debug('PrestaShopCategoryPickerModal: toggled category', [
            'category_id' => $categoryId,
            'selected_count' => count($this->selectedCategoryIds),
        ]);
    }

    /**
     * Refresh category tree from API
     */
    public function refreshCategories(): void
    {
        if (!$this->shopId) {
            return;
        }

        $this->isLoading = true;

        try {
            $shop = PrestaShopShop::find($this->shopId);
            if ($shop) {
                $this->categoryService->clearCache($shop);
            }

            Log::info('PrestaShopCategoryPickerModal: cache cleared', [
                'shop_id' => $this->shopId,
            ]);
        } catch (\Exception $e) {
            Log::error('PrestaShopCategoryPickerModal: refresh failed', [
                'error' => $e->getMessage(),
            ]);
        }

        $this->isLoading = false;
    }

    /**
     * Save selected categories to product
     */
    public function save(): void
    {
        if (!$this->productId || !$this->shopId) {
            return;
        }

        $product = PendingProduct::find($this->productId);
        if (!$product) {
            $this->addError('save', 'Produkt nie istnieje');
            return;
        }

        // Update shop_categories JSON
        $shopCategories = $product->shop_categories ?? [];
        $shopCategories[(string)$this->shopId] = array_values($this->selectedCategoryIds);
        $product->shop_categories = $shopCategories;
        $product->save();

        Log::info('PrestaShopCategoryPickerModal: saved categories', [
            'product_id' => $this->productId,
            'shop_id' => $this->shopId,
            'category_count' => count($this->selectedCategoryIds),
        ]);

        // Dispatch event for parent to refresh (named params for Alpine listener)
        $this->dispatch('prestashop-categories-saved',
            productId: $this->productId,
            shopId: $this->shopId,
            categoryCount: count($this->selectedCategoryIds)
        );

        $this->close();
    }

    /**
     * Get category tree for current shop
     *
     * @return array Hierarchical category tree
     */
    public function getCategoryTreeProperty(): array
    {
        if (!$this->shopId) {
            return [];
        }

        try {
            $shop = PrestaShopShop::find($this->shopId);
            if (!$shop) {
                return [];
            }

            return $this->categoryService->getCachedCategoryTree($shop);
        } catch (\Exception $e) {
            Log::error('PrestaShopCategoryPickerModal: failed to get category tree', [
                'shop_id' => $this->shopId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get filtered category tree (by search query)
     *
     * @return array Filtered categories (flat list if searching)
     */
    public function getFilteredCategoriesProperty(): array
    {
        $tree = $this->categoryTree;

        if (empty($this->searchQuery)) {
            return $tree;
        }

        // Flatten and filter
        return $this->filterCategoriesRecursive($tree, strtolower($this->searchQuery));
    }

    /**
     * Filter categories recursively by search query
     *
     * @param array $categories Categories to filter
     * @param string $query Lowercase search query
     * @return array Matching categories (flat)
     */
    protected function filterCategoriesRecursive(array $categories, string $query): array
    {
        $results = [];

        foreach ($categories as $category) {
            $name = strtolower($category['name'] ?? '');

            if (str_contains($name, $query)) {
                $results[] = $category;
            }

            if (!empty($category['children'])) {
                $childResults = $this->filterCategoriesRecursive($category['children'], $query);
                $results = array_merge($results, $childResults);
            }
        }

        return $results;
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.products.import.modals.prestashop-category-picker-modal', [
            'categoryTree' => $this->categoryTree,
        ]);
    }
}
