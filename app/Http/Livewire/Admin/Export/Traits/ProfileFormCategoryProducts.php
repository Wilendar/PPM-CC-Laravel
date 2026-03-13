<?php

namespace App\Http\Livewire\Admin\Export\Traits;

use App\Models\Product;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;

/**
 * ProfileFormCategoryProducts Trait
 *
 * Manages product listing for selected categories in Export Step 3.
 * Shows products from ALL selected categories with exclusion support.
 *
 * Exclusion logic: selected categories define the base set,
 * excludedProductIds removes individual products from export.
 */
trait ProfileFormCategoryProducts
{
    /** Products loaded from selected categories */
    public array $categoryProducts = [];

    /** Product IDs excluded from export (despite being in selected categories) */
    public array $excludedProductIds = [];

    /** Current pagination page for product list */
    public int $productPage = 1;

    /** Products per page */
    public int $productPerPage = 50;

    /** Total product count for selected categories */
    public int $categoryProductsTotal = 0;

    /** Whether more products are available to load */
    public bool $hasMoreProducts = false;

    /**
     * Load products from ALL selected categories with pagination.
     */
    public function loadCategoryProducts(): void
    {
        if (empty($this->filterCategoryIds)) {
            $this->categoryProducts = [];
            $this->categoryProductsTotal = 0;
            $this->hasMoreProducts = false;
            return;
        }

        $categoryIds = array_map('intval', $this->filterCategoryIds);

        $query = Product::whereHas('categories', function ($q) use ($categoryIds) {
            $q->whereIn('categories.id', $categoryIds);
        })
        ->where('is_active', true)
        ->select('id', 'sku', 'name')
        ->orderBy('sku');

        $this->categoryProductsTotal = $query->count();

        $limit = $this->productPage * $this->productPerPage;
        $products = $query->limit($limit)->get();

        $this->categoryProducts = $products->map(fn($p) => [
            'id' => $p->id,
            'sku' => $p->sku,
            'name' => $p->name,
        ])->toArray();

        $this->hasMoreProducts = $this->categoryProductsTotal > $limit;
    }

    /**
     * Toggle product exclusion from export.
     */
    public function toggleProductExclusion(int $productId): void
    {
        if (in_array($productId, $this->excludedProductIds, true)) {
            $this->excludedProductIds = array_values(
                array_filter($this->excludedProductIds, fn($id) => $id !== $productId)
            );
        } else {
            $this->excludedProductIds[] = $productId;
        }
    }

    /**
     * Exclude all currently loaded products.
     */
    public function excludeAllProducts(): void
    {
        $this->excludedProductIds = array_column($this->categoryProducts, 'id');
    }

    /**
     * Restore all excluded products.
     */
    public function restoreAllProducts(): void
    {
        $this->excludedProductIds = [];
    }

    /**
     * Load more products (pagination).
     */
    public function loadMoreProducts(): void
    {
        $this->productPage++;
        $this->loadCategoryProducts();
    }

    /**
     * Hook: reload products when selected categories change.
     * Called by Livewire when filterCategoryIds property is updated directly.
     */
    public function updatedFilterCategoryIds(): void
    {
        $this->productPage = 1;
        $this->loadCategoryProducts();
    }

    /**
     * Event listener: reload products when CategoryPicker dispatches selection change.
     * This is needed because wire:model from child #[Modelable] doesn't trigger updated* hooks.
     */
    #[On('category-selection-changed')]
    public function onCategorySelectionChanged($selectedIds = []): void
    {
        $this->productPage = 1;
        $this->loadCategoryProducts();
    }

    /**
     * Get excluded product IDs config for filter storage.
     */
    public function getCategoryProductsFilterConfig(): array
    {
        if (empty($this->excludedProductIds)) {
            return [];
        }

        return [
            'excluded_product_ids' => array_map('intval', $this->excludedProductIds),
        ];
    }

    /**
     * Load excluded product IDs from existing profile.
     */
    public function loadCategoryProductsFromProfile(\App\Models\ExportProfile $profile): void
    {
        $filterConfig = $profile->filter_config ?? [];
        $this->excludedProductIds = array_map(
            'intval',
            (array) ($filterConfig['excluded_product_ids'] ?? [])
        );
    }
}
