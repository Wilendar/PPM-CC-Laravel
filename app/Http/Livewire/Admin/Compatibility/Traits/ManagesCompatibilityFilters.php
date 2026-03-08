<?php

namespace App\Http\Livewire\Admin\Compatibility\Traits;

use App\Models\Category;
use App\Models\PrestaShopShop;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * ManagesCompatibilityFilters
 *
 * Shared filter logic for CompatibilityManagement and PendingCompatibilityTab.
 * Handles search, brand, category, shop assignment, manufacturer,
 * compatibility count range, and no-matches filtering.
 *
 * Assumes the using class:
 * - Uses Livewire\WithPagination (for resetPage())
 * - Has optional ?int $shopContext property (for shop-aware filtering)
 */
trait ManagesCompatibilityFilters
{
    /*
    |--------------------------------------------------------------------------
    | FILTER PROPERTIES
    |--------------------------------------------------------------------------
    */

    /** Search by SKU or name */
    public string $searchPart = '';

    /** Filter vehicles by brand */
    public string $filterBrand = '';

    /** Vehicle search within tiles */
    public string $vehicleSearch = '';

    /** Show only parts without any compatibility matches */
    public bool $filterNoMatches = false;

    /** Sort field for parts list */
    public string $sortField = 'sku';

    /** Sort direction */
    public string $sortDirection = 'asc';

    /*
    |--------------------------------------------------------------------------
    | ADVANCED FILTER PROPERTIES
    |--------------------------------------------------------------------------
    */

    /** Filter by category (ID) - includes descendants */
    public string $filterCategory = '';

    /** Filter by shop assignment: '' | 'none' | 'any' | 'shop_X' */
    public string $filterShopAssignment = '';

    /** Filter by manufacturer */
    public string $filterManufacturer = '';

    /** Filter by compatibility count range: '' | '0' | '1-5' | '6-20' | '20+' */
    public string $filterCompatCountRange = '';

    /*
    |--------------------------------------------------------------------------
    | QUERY STRING
    |--------------------------------------------------------------------------
    */

    /**
     * Get query string configuration for filter properties.
     * Merge into the component's $queryString in the using class.
     */
    protected function getCompatibilityFilterQueryString(): array
    {
        return [
            'searchPart' => ['except' => ''],
            'filterBrand' => ['except' => ''],
            'filterNoMatches' => ['except' => false],
            'filterCategory' => ['except' => ''],
            'filterShopAssignment' => ['except' => ''],
            'filterManufacturer' => ['except' => ''],
            'filterCompatCountRange' => ['except' => ''],
            'sortField' => ['except' => 'sku'],
            'sortDirection' => ['except' => 'asc'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE HOOKS (Livewire updated*)
    |--------------------------------------------------------------------------
    */

    public function updatedSearchPart(): void
    {
        $this->resetPage();
    }

    public function updatedFilterBrand(): void
    {
        $this->resetPage();
    }

    public function updatedFilterNoMatches(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCategory(): void
    {
        $this->resetPage();
    }

    public function updatedFilterShopAssignment(): void
    {
        $this->resetPage();
    }

    public function updatedFilterManufacturer(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCompatCountRange(): void
    {
        $this->resetPage();
    }

    /*
    |--------------------------------------------------------------------------
    | FILTER ACTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Reset all filter properties to defaults and go to page 1.
     */
    public function resetFilters(): void
    {
        $this->searchPart = '';
        $this->filterBrand = '';
        $this->vehicleSearch = '';
        $this->filterNoMatches = false;
        $this->filterCategory = '';
        $this->filterShopAssignment = '';
        $this->filterManufacturer = '';
        $this->filterCompatCountRange = '';
        $this->sortField = 'sku';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    /**
     * Toggle sort direction if same field, otherwise set new field ascending.
     */
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY BUILDER
    |--------------------------------------------------------------------------
    */

    /**
     * Apply all active filters to a Product query builder.
     *
     * Call this from getPartsProperty() or similar computed property
     * on a base Product::query() builder.
     */
    public function applyPartFilters(Builder $query): Builder
    {
        // Search: SKU or name
        if ($this->searchPart !== '') {
            $search = '%' . $this->searchPart . '%';
            $query->where(function (Builder $q) use ($search) {
                $q->where('sku', 'like', $search)
                  ->orWhere('name', 'like', $search);
            });
        }

        // No matches: parts without vehicle compatibility
        if ($this->filterNoMatches) {
            $shopContext = $this->shopContext ?? null;
            $query->whereDoesntHave('vehicleCompatibility', function (Builder $q) use ($shopContext) {
                if ($shopContext !== null) {
                    $q->where('shop_id', $shopContext);
                }
            });
        }

        // Category: selected category + all descendants
        if ($this->filterCategory !== '') {
            $categoryId = (int) $this->filterCategory;
            $descendantIds = Category::descendants($categoryId)->pluck('id');
            $allCategoryIds = $descendantIds->push($categoryId)->unique()->values();

            $query->whereHas('categories', function (Builder $q) use ($allCategoryIds) {
                $q->whereIn('categories.id', $allCategoryIds);
            });
        }

        // Shop assignment
        if ($this->filterShopAssignment !== '') {
            $this->applyShopAssignmentFilter($query);
        }

        // Manufacturer
        if ($this->filterManufacturer !== '') {
            $query->where('manufacturer', $this->filterManufacturer);
        }

        // Compatibility count range
        if ($this->filterCompatCountRange !== '') {
            $this->applyCompatCountRangeFilter($query);
        }

        // Sort
        $query->orderBy($this->sortField, $this->sortDirection);

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | FILTER OPTIONS (for dropdowns)
    |--------------------------------------------------------------------------
    */

    /**
     * Category tree options for the filter dropdown.
     *
     * @return array<int, string> [id => '--- CategoryName']
     */
    public function getCategoryFilterOptions(): array
    {
        return Category::getTreeOptions();
    }

    /**
     * Available shops for the shop assignment filter dropdown.
     */
    public function getShopFilterOptions(): Collection
    {
        return PrestaShopShop::orderBy('name')->get();
    }

    /**
     * Distinct manufacturers from spare-part products, cached 5 minutes.
     */
    public function getManufacturerFilterOptions(): Collection
    {
        return Cache::remember('compat_manufacturer_options', 300, function () {
            return Product::byType('czesc-zamienna')
                ->whereNotNull('manufacturer')
                ->where('manufacturer', '!=', '')
                ->distinct()
                ->orderBy('manufacturer')
                ->pluck('manufacturer');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | FILTER COUNTS (result counts per option)
    |--------------------------------------------------------------------------
    */

    /**
     * Get result counts per filter option for display in dropdowns.
     * Each filter's counts are calculated EXCLUDING that filter's current value
     * but INCLUDING all other active filters.
     *
     * Cached 30s per unique filter state hash.
     *
     * @return array{manufacturers: array, categories: array, compat_ranges: array}
     */
    public function getFilterCounts(): array
    {
        $state = $this->getFilterStateForCounts();
        $cacheKey = 'compat_filter_counts_' . md5(serialize($state));

        return Cache::remember($cacheKey, 30, function () {
            return [
                'manufacturers' => $this->countByManufacturer(),
                'categories' => $this->countByCategory(),
                'compat_ranges' => $this->countByCompatRange(),
            ];
        });
    }

    /**
     * Get filter state for cache key (excludes volatile properties).
     */
    private function getFilterStateForCounts(): array
    {
        return [
            'search' => $this->searchPart,
            'brand' => $this->filterBrand,
            'noMatches' => $this->filterNoMatches,
            'category' => $this->filterCategory,
            'shopAssignment' => $this->filterShopAssignment,
            'manufacturer' => $this->filterManufacturer,
            'compatRange' => $this->filterCompatCountRange,
            'shopContext' => $this->shopContext ?? null,
        ];
    }

    /**
     * Count products per manufacturer (excluding manufacturer filter).
     */
    private function countByManufacturer(): array
    {
        $query = $this->buildBaseCountQuery();
        // Apply all filters EXCEPT manufacturer
        $this->applyCountFilters($query, exclude: 'manufacturer');

        return $query->whereNotNull('manufacturer')
            ->where('manufacturer', '!=', '')
            ->selectRaw('manufacturer, COUNT(*) as cnt')
            ->groupBy('manufacturer')
            ->pluck('cnt', 'manufacturer')
            ->toArray();
    }

    /**
     * Count products per category (excluding category filter).
     */
    private function countByCategory(): array
    {
        $query = $this->buildBaseCountQuery();
        $this->applyCountFilters($query, exclude: 'category');

        return $query->join('product_categories', 'products.id', '=', 'product_categories.product_id')
            ->whereNull('product_categories.shop_id')
            ->selectRaw('product_categories.category_id, COUNT(DISTINCT products.id) as cnt')
            ->groupBy('product_categories.category_id')
            ->pluck('cnt', 'product_categories.category_id')
            ->toArray();
    }

    /**
     * Count products per compatibility range (excluding range filter).
     */
    private function countByCompatRange(): array
    {
        $query = $this->buildBaseCountQuery();
        $this->applyCountFilters($query, exclude: 'compatRange');

        $query->withCount('vehicleCompatibility');
        $products = $query->get(['id']);

        $ranges = ['0' => 0, '1-5' => 0, '6-20' => 0, '20+' => 0];
        foreach ($products as $product) {
            $count = $product->vehicle_compatibility_count;
            if ($count === 0) {
                $ranges['0']++;
            } elseif ($count <= 5) {
                $ranges['1-5']++;
            } elseif ($count <= 20) {
                $ranges['6-20']++;
            } else {
                $ranges['20+']++;
            }
        }

        return $ranges;
    }

    /**
     * Build base query for counting (spare parts only).
     */
    private function buildBaseCountQuery(): Builder
    {
        return Product::query()->byType('czesc-zamienna');
    }

    /**
     * Apply all filters except one (for counting alternative options).
     */
    private function applyCountFilters(Builder $query, string $exclude): void
    {
        if ($exclude !== 'search' && $this->searchPart !== '') {
            $search = '%' . $this->searchPart . '%';
            $query->where(function (Builder $q) use ($search) {
                $q->where('sku', 'like', $search)
                  ->orWhere('name', 'like', $search);
            });
        }

        if ($exclude !== 'noMatches' && $this->filterNoMatches) {
            $shopContext = $this->shopContext ?? null;
            $query->whereDoesntHave('vehicleCompatibility', function (Builder $q) use ($shopContext) {
                if ($shopContext !== null) {
                    $q->where('shop_id', $shopContext);
                }
            });
        }

        if ($exclude !== 'category' && $this->filterCategory !== '') {
            $categoryId = (int) $this->filterCategory;
            $descendantIds = Category::descendants($categoryId)->pluck('id');
            $allCategoryIds = $descendantIds->push($categoryId)->unique()->values();
            $query->whereHas('categories', fn(Builder $q) => $q->whereIn('categories.id', $allCategoryIds));
        }

        if ($exclude !== 'shopAssignment' && $this->filterShopAssignment !== '') {
            match ($this->filterShopAssignment) {
                'none' => $query->whereDoesntHave('shopData'),
                'any'  => $query->whereHas('shopData'),
                default => $this->applySpecificShopFilterOnQuery($query, $this->filterShopAssignment),
            };
        }

        if ($exclude !== 'manufacturer' && $this->filterManufacturer !== '') {
            $query->where('manufacturer', $this->filterManufacturer);
        }

        if ($exclude !== 'compatRange' && $this->filterCompatCountRange !== '') {
            match ($this->filterCompatCountRange) {
                '0'    => $query->doesntHave('vehicleCompatibility'),
                '1-5'  => $query->has('vehicleCompatibility', '>=', 1)->has('vehicleCompatibility', '<=', 5),
                '6-20' => $query->has('vehicleCompatibility', '>=', 6)->has('vehicleCompatibility', '<=', 20),
                '20+'  => $query->has('vehicleCompatibility', '>=', 21),
                default => null,
            };
        }
    }

    /**
     * Apply specific shop filter on a query builder (reusable for counts).
     */
    private function applySpecificShopFilterOnQuery(Builder $query, string $filterValue): void
    {
        if (str_starts_with($filterValue, 'shop_')) {
            $shopId = (int) str_replace('shop_', '', $filterValue);
            if ($shopId > 0) {
                $query->whereHas('shopData', fn(Builder $q) => $q->where('shop_id', $shopId));
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIVE FILTER HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Count how many filters are currently active (non-default).
     */
    public function getActiveFiltersCount(): int
    {
        $count = 0;

        if ($this->searchPart !== '') $count++;
        if ($this->filterBrand !== '') $count++;
        if ($this->filterNoMatches) $count++;
        if ($this->filterCategory !== '') $count++;
        if ($this->filterShopAssignment !== '') $count++;
        if ($this->filterManufacturer !== '') $count++;
        if ($this->filterCompatCountRange !== '') $count++;

        return $count;
    }

    /**
     * Check if any filter is active.
     */
    public function hasActiveFilters(): bool
    {
        return $this->getActiveFiltersCount() > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | PRIVATE FILTER HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Apply shop assignment sub-filter to the query.
     */
    private function applyShopAssignmentFilter(Builder $query): void
    {
        match ($this->filterShopAssignment) {
            'none' => $query->whereDoesntHave('shopData'),
            'any'  => $query->whereHas('shopData'),
            default => $this->applySpecificShopFilter($query),
        };
    }

    /**
     * Handle 'shop_X' format for specific shop filtering.
     */
    private function applySpecificShopFilter(Builder $query): void
    {
        if (!str_starts_with($this->filterShopAssignment, 'shop_')) {
            return;
        }

        $shopId = (int) str_replace('shop_', '', $this->filterShopAssignment);
        if ($shopId > 0) {
            $query->whereHas('shopData', function (Builder $q) use ($shopId) {
                $q->where('shop_id', $shopId);
            });
        }
    }

    /**
     * Apply compatibility count range sub-filter using has() constraints.
     */
    private function applyCompatCountRangeFilter(Builder $query): void
    {
        match ($this->filterCompatCountRange) {
            '0'    => $query->doesntHave('vehicleCompatibility'),
            '1-5'  => $query->has('vehicleCompatibility', '>=', 1)
                            ->has('vehicleCompatibility', '<=', 5),
            '6-20' => $query->has('vehicleCompatibility', '>=', 6)
                            ->has('vehicleCompatibility', '<=', 20),
            '20+'  => $query->has('vehicleCompatibility', '>=', 21),
            default => null,
        };
    }
}
