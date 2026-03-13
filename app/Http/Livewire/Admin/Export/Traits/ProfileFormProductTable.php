<?php

namespace App\Http\Livewire\Admin\Export\Traits;

use App\Models\Product;
use App\Services\Export\ProductExportService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

/**
 * ProfileFormProductTable Trait
 *
 * Server-side paginated product table for Export Step 3.
 * Reuses ProductExportService::applyFilters() for consistent filtering.
 * Replaces old ProfileFormCategoryProducts (pseudo-pagination, client-side).
 */
trait ProfileFormProductTable
{
    /** Product IDs excluded from export */
    public array $excludedProductIds = [];

    /** Search query for product table */
    public string $exportSearch = '';

    /** Sort column */
    public string $exportSortBy = 'sku';

    /** Sort direction */
    public string $exportSortDirection = 'asc';

    /** Items per page */
    public int $exportPerPage = 25;

    /**
     * Whether at least one category is selected (required to show products).
     */
    #[Computed]
    public function hasCategoryFilter(): bool
    {
        return !empty($this->filterCategoryIds);
    }

    /**
     * Whether products should be loaded (category selected OR search active).
     */
    #[Computed]
    public function hasActiveProductFilter(): bool
    {
        return $this->hasCategoryFilter || !empty($this->exportSearch);
    }

    /**
     * Paginated product list for table display.
     * Requires category or search query to avoid loading entire DB.
     */
    #[Computed]
    public function exportProducts(): LengthAwarePaginator
    {
        if (!$this->hasActiveProductFilter) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->exportPerPage, 1, [
                'pageName' => 'exportPage',
            ]);
        }

        return $this->buildExportFilterQuery()
            ->paginate($this->exportPerPage, ['*'], 'exportPage');
    }

    /**
     * Total product count matching current filters (for stats).
     * Returns 0 when no filter active.
     */
    #[Computed]
    public function exportProductsCount(): int
    {
        if (!$this->hasActiveProductFilter) {
            return 0;
        }

        return $this->buildExportFilterQuery()->count();
    }

    /**
     * Build query with all current filters applied.
     * Does NOT exclude excluded_product_ids - those are shown visually in table.
     */
    protected function buildExportFilterQuery(): Builder
    {
        $query = Product::query()
            ->with([
                'media' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->limit(1),
                'productStatus:id,name,slug,color',
                'validPrices' => fn ($q) => $q->orderBy('price_group_id')->limit(1),
                'activeStock',
            ])
            ->select([
                'id', 'sku', 'name', 'is_active', 'manufacturer_id', 'product_status_id',
            ]);

        // Apply all current filters via ProductExportService (without excluded_product_ids)
        $filterConfig = $this->buildCurrentFilterConfig();
        if (!empty($filterConfig)) {
            $exportService = app(ProductExportService::class);
            $query = $exportService->applyFilters($query, $filterConfig);
        }

        // Search (SKU or name)
        if (!empty($this->exportSearch)) {
            $search = '%' . $this->exportSearch . '%';
            $query->where(function (Builder $q) use ($search) {
                $q->where('sku', 'LIKE', $search)
                  ->orWhere('name', 'LIKE', $search);
            });
        }

        // Sort
        $query->orderBy($this->exportSortBy, $this->exportSortDirection);

        return $query;
    }

    /**
     * Build filter_config from current properties (WITHOUT excluded_product_ids).
     * Reuses getFilterConfig() and strips excluded - ensures consistency with save format.
     */
    protected function buildCurrentFilterConfig(): array
    {
        $config = $this->getFilterConfig();
        unset($config['excluded_product_ids']);

        return $config;
    }

    /**
     * Exclude ALL products matching current filters (server-side, max 10K).
     * Requires at least one category selected.
     */
    public function excludeAllFromFilter(): void
    {
        if (!$this->hasActiveProductFilter) {
            return;
        }

        $allIds = $this->buildExportFilterQuery()
            ->limit(10000)
            ->pluck('products.id')
            ->toArray();

        $this->excludedProductIds = array_values(
            array_unique(array_merge($this->excludedProductIds, $allIds))
        );
    }

    /**
     * Restore all excluded products.
     */
    public function restoreAllProducts(): void
    {
        $this->excludedProductIds = [];
    }

    /**
     * Toggle single product exclusion.
     */
    public function toggleProductExclusion(int $productId): void
    {
        if (in_array($productId, $this->excludedProductIds, true)) {
            $this->excludedProductIds = array_values(
                array_filter($this->excludedProductIds, fn ($id) => $id !== $productId)
            );
        } else {
            $this->excludedProductIds[] = $productId;
        }
    }

    /**
     * Sort table by column (toggle direction if same column).
     */
    public function sortExportBy(string $column): void
    {
        $allowed = ['sku', 'name', 'is_active'];
        if (!in_array($column, $allowed, true)) {
            return;
        }

        if ($this->exportSortBy === $column) {
            $this->exportSortDirection = $this->exportSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->exportSortBy = $column;
            $this->exportSortDirection = 'asc';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PAGINATION RESET HOOKS
    |--------------------------------------------------------------------------
    */

    public function updatedExportSearch(): void
    {
        $this->resetPage('exportPage');
    }

    public function updatedExportPerPage(): void
    {
        $this->resetPage('exportPage');
    }

    public function updatedFilterCategoryIds(): void
    {
        $this->resetPage('exportPage');
    }

    public function updatedFilterIsActive(): void
    {
        $this->resetPage('exportPage');
    }

    public function updatedFilterProductTypeId(): void
    {
        $this->resetPage('exportPage');
    }

    public function updatedFilterStockStatus(): void
    {
        $this->resetPage('exportPage');
    }

    public function updatedFilterManufacturerIds(): void
    {
        $this->resetPage('exportPage');
    }

    public function updatedFilterShopIds(): void
    {
        $this->resetPage('exportPage');
    }

    public function updatedFilterSupplierIds(): void
    {
        $this->resetPage('exportPage');
    }

    public function updatedFilterWarehouseIds(): void
    {
        $this->resetPage('exportPage');
    }

    public function updatedFilterErpConnectionIds(): void
    {
        $this->resetPage('exportPage');
    }

    /**
     * Event listener: CategoryPicker selection changed.
     */
    #[On('category-selection-changed')]
    public function onCategorySelectionChanged($selectedIds = []): void
    {
        $this->resetPage('exportPage');
    }

    /*
    |--------------------------------------------------------------------------
    | BACKWARD-COMPATIBLE CONFIG (save/load)
    |--------------------------------------------------------------------------
    */

    /**
     * Get excluded product IDs for filter_config storage.
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
