<?php

namespace App\Http\Livewire\Products\Listing\Traits;

use App\Models\Product;
use App\Models\ProductStatus;
use App\Models\Category;
use App\Models\ProductType;
use App\Models\PriceGroup;
use App\Models\Warehouse;
use App\Models\ProductShopData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;

/**
 * ProductListFilters Trait
 *
 * Manages all filtering logic for the ProductList component:
 * - Search, category, status, stock, product type filters
 * - Advanced filters (price range, date range, integration, media)
 * - Data status filters (issues, OK)
 * - Query building with all filter applications
 *
 * @package App\Http\Livewire\Products\Listing\Traits
 */
trait ProductListFilters
{
    /*
    |--------------------------------------------------------------------------
    | FILTER PROPERTIES
    |--------------------------------------------------------------------------
    */

    // Search & Filtering
    public string $search = '';
    public string $categoryFilter = '';
    public string $statusFilter = 'all';
    public string $stockFilter = 'all';
    public string $productTypeFilter = 'all';

    // Advanced Filters
    public float $priceMin = 0;
    public float $priceMax = 10000;
    public string $priceGroupFilter = '';
    public ?int $stockMin = null;
    public ?int $stockMax = null;
    public string $stockWarehouseFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $dateType = 'created_at';
    public string $integrationFilter = 'all';
    public string $mediaFilter = 'all';

    // Product Status Filters
    public ?string $dataStatusFilter = null;
    public array $issueTypeFilters = [];

    // UI State
    public bool $showFilters = false;
    public bool $hasFilters = false;

    /*
    |--------------------------------------------------------------------------
    | FILTER UPDATED HOOKS
    |--------------------------------------------------------------------------
    */

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedStockFilter(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedProductTypeFilter(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedPriceGroupFilter(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedStockMin(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedStockMax(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedStockWarehouseFilter(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    /*
    |--------------------------------------------------------------------------
    | FILTER ACTIONS
    |--------------------------------------------------------------------------
    */

    public function clearFilters(): void
    {
        $this->reset([
            'search',
            'categoryFilter',
            'statusFilter',
            'stockFilter',
            'productTypeFilter',
            'priceMin',
            'priceMax',
            'priceGroupFilter',
            'stockMin',
            'stockMax',
            'stockWarehouseFilter',
            'dateFrom',
            'dateTo',
            'dateType',
            'integrationFilter',
            'mediaFilter'
        ]);

        $this->priceMin = 0;
        $this->priceMax = 10000;
        $this->priceGroupFilter = '';
        $this->stockMin = null;
        $this->stockMax = null;
        $this->stockWarehouseFilter = '';
        $this->dateType = 'created_at';
        $this->resetPage();
        $this->resetSelection();

        $this->dispatch('filters-cleared');
    }

    public function getHasFiltersProperty(): bool
    {
        return !empty($this->search)
            || $this->categoryFilter !== ''
            || $this->statusFilter !== 'all'
            || $this->stockFilter !== 'all'
            || $this->productTypeFilter !== 'all'
            || $this->priceMin > 0
            || $this->priceMax < 10000
            || $this->priceGroupFilter !== ''
            || $this->stockMin !== null
            || $this->stockMax !== null
            || $this->stockWarehouseFilter !== ''
            || !empty($this->dateFrom)
            || !empty($this->dateTo)
            || $this->integrationFilter !== 'all'
            || $this->mediaFilter !== 'all';
    }

    private function updateHasFilters(): void
    {
        $this->hasFilters = $this->getHasFiltersProperty();
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY BUILDING
    |--------------------------------------------------------------------------
    */

    private function buildProductQuery(): Builder
    {
        $query = Product::query()
            ->with([
                'productType:id,name,slug',
                'productStatus:id,name,slug,color,is_active_equivalent',
                'shopData:id,product_id,shop_id,sync_status,is_published,last_sync_at,name,short_description,long_description,weight,height,width,length,tax_rate,is_active',
                'shopData.shop:id,name,label_color,label_icon',
                'erpData:id,product_id,erp_connection_id,sync_status,name,short_description,long_description,weight,height,width,length,tax_rate,is_active',
                'erpData.erpConnection:id,instance_name,erp_type,label_color,label_icon',
                'prices:id,product_id,price_group_id,price_net,price_gross',
                'prices.priceGroup:id,name,code,is_active,is_default',
                'stock:id,product_id,warehouse_id,quantity,reserved_quantity,available_quantity,minimum_stock,is_active',
                'stock.warehouse:id,name,code,is_default',
                'media' => fn($q) => $q->where('is_active', true)->select('id', 'mediable_id', 'mediable_type', 'is_primary', 'is_active', 'prestashop_mapping'),
                'variants:id,product_id,sku,name,is_active',
                'variants.images:id,variant_id,image_path,image_thumb_path,image_url,is_cached,is_cover,position',
                'variants.prices:id,variant_id,price_group_id,price',
                'variants.stock:id,variant_id,warehouse_id,quantity,reserved',
                'variants.attributes.attributeType:id,name,code',
                'variants.attributes.attributeValue:id,label,code'
            ])
            ->select([
                'id', 'sku', 'name', 'product_type_id', 'product_status_id', 'manufacturer',
                'supplier_code', 'is_active', 'is_variant_master',
                'short_description', 'long_description',
                'weight', 'height', 'width', 'length', 'tax_rate',
                'created_at', 'updated_at'
            ]);

        if (!empty($this->search)) {
            $query->search($this->search);
        }

        if (!empty($this->categoryFilter)) {
            $categoryId = (int) $this->categoryFilter;
            $descendantIds = Cache::remember("cat_descendants_{$categoryId}", 3600, function () use ($categoryId) {
                $category = Category::select('id', 'path')->find($categoryId);
                if (!$category) {
                    return [$categoryId];
                }
                $searchPath = $category->path ? $category->path . '/' . $category->id : '/' . $category->id;
                return Category::where('path', 'LIKE', $searchPath . '%')
                    ->pluck('id')
                    ->push($categoryId)
                    ->unique()
                    ->values()
                    ->toArray();
            });
            $query->whereHas('categories', fn($q) => $q->whereIn('categories.id', $descendantIds));
        }

        // SECURITY: Only apply status filter if user has status_read permission
        if ($this->statusFilter !== 'all' && $this->userCan('status_read')) {
            if ($this->statusFilter === 'active') {
                $query->where('is_active', true);
            } elseif ($this->statusFilter === 'inactive') {
                $query->where('is_active', false);
            } elseif (is_numeric($this->statusFilter)) {
                $query->where('product_status_id', (int) $this->statusFilter);
            }
        } elseif ($this->statusFilter !== 'all') {
            $this->statusFilter = 'all';
        }

        if ($this->productTypeFilter !== 'all') {
            $query->byType($this->productTypeFilter);
        }

        // SECURITY: Only apply basic stock filter if user has stock_read permission
        if ($this->stockFilter !== 'all' && $this->userCan('stock_read')) {
            $query = $this->applyStockFilter($query);
        } elseif ($this->stockFilter !== 'all') {
            $this->stockFilter = 'all';
        }

        $query = $this->applyAdvancedFilters($query);

        $query = $this->applySorting($query);

        return $query;
    }

    /**
     * Apply sorting with subquery support for price and stock columns.
     * Uses correlated subqueries to avoid duplicate rows from LEFT JOIN.
     * For 'price': subquery on product_prices with default price group filter.
     * For 'stock': subquery on product_stock with default warehouse filter.
     * For other columns: standard orderBy on products table.
     */
    private function applySorting(Builder $query): Builder
    {
        $direction = strtolower($this->sortDirection) === 'desc' ? 'desc' : 'asc';

        // SECURITY: Reset sort to default if user lacks permission for price/stock columns
        $effectiveSortBy = $this->sortBy;
        if ($effectiveSortBy === 'price' && !$this->userCan('prices_read')) {
            $effectiveSortBy = 'updated_at';
        }
        if ($effectiveSortBy === 'stock' && !$this->userCan('stock_read')) {
            $effectiveSortBy = 'updated_at';
        }

        switch ($effectiveSortBy) {
            case 'price':
                $priceColumn = $this->priceDisplayMode === 'netto' ? 'price_net' : 'price_gross';

                $query->addSelect(DB::raw("(
                    SELECT pp.{$priceColumn}
                    FROM product_prices pp
                    INNER JOIN price_groups pg ON pg.id = pp.price_group_id AND pg.is_default = 1
                    WHERE pp.product_id = products.id
                    LIMIT 1
                ) as sort_price_value"));

                $query->orderBy('sort_price_value', $direction);
                break;

            case 'stock':
                $query->addSelect(DB::raw("(
                    SELECT ps.quantity
                    FROM product_stock ps
                    INNER JOIN warehouses w ON w.id = ps.warehouse_id AND w.is_default = 1
                    WHERE ps.product_id = products.id
                    LIMIT 1
                ) as sort_stock_value"));

                $query->orderBy('sort_stock_value', $direction);
                break;

            default:
                $query->orderBy($effectiveSortBy, $direction);
                break;
        }

        if ($effectiveSortBy !== 'id') {
            $query->orderBy('products.id', 'desc');
        }

        return $query;
    }

    private function applyStockFilter(Builder $query): Builder
    {
        switch ($this->stockFilter) {
            case 'in_stock':
                return $query->whereHas('activeStock', function ($q) {
                    $q->where('available_quantity', '>', 0);
                });
            case 'low_stock':
                return $query->whereHas('activeStock', function ($q) {
                    $q->where('available_quantity', '>', 0)
                      ->where('available_quantity', '<=', 10);
                });
            case 'out_of_stock':
                return $query->whereDoesntHave('activeStock', function ($q) {
                    $q->where('available_quantity', '>', 0);
                });
            default:
                return $query;
        }
    }

    private function applyAdvancedFilters(Builder $query): Builder
    {
        // Price Group + Range Filter - SECURITY: only apply if user has prices_read permission
        if ($this->userCan('prices_read')) {
            if ($this->priceGroupFilter) {
                $query->whereHas('prices', function ($q) {
                    $q->where('price_group_id', $this->priceGroupFilter);
                    if ($this->priceMin > 0) {
                        $q->where('price_net', '>=', $this->priceMin);
                    }
                    if ($this->priceMax < 10000) {
                        $q->where('price_net', '<=', $this->priceMax);
                    }
                });
            } elseif ($this->priceMin > 0 || $this->priceMax < 10000) {
                $query->whereHas('prices', function ($q) {
                    $q->whereBetween('price_net', [$this->priceMin, $this->priceMax]);
                });
            }
        } else {
            // Reset price filters to prevent Livewire wire:call bypass
            $this->priceMin = 0;
            $this->priceMax = 10000;
            $this->priceGroupFilter = '';
        }

        // Stock Range + Warehouse Filter - SECURITY: only apply if user has stock_read permission
        if ($this->userCan('stock_read')) {
            if ($this->stockMin !== null || $this->stockMax !== null || $this->stockWarehouseFilter) {
                $query->whereHas('stock', function ($q) {
                    if ($this->stockWarehouseFilter) {
                        $q->where('warehouse_id', $this->stockWarehouseFilter);
                    }
                    if ($this->stockMin !== null) {
                        $q->where('quantity', '>=', $this->stockMin);
                    }
                    if ($this->stockMax !== null) {
                        $q->where('quantity', '<=', $this->stockMax);
                    }
                });
            }
        } else {
            // Reset stock filters to prevent Livewire wire:call bypass
            $this->stockMin = null;
            $this->stockMax = null;
            $this->stockWarehouseFilter = '';
        }

        if (!empty($this->dateFrom)) {
            $query->whereDate($this->dateType, '>=', $this->dateFrom);
        }
        if (!empty($this->dateTo)) {
            $query->whereDate($this->dateType, '<=', $this->dateTo);
        }

        // SECURITY: Only apply integration filter if user has compliance_read permission
        if ($this->integrationFilter !== 'all' && $this->userCan('compliance_read')) {
            $query = $this->applyIntegrationFilter($query);
        } elseif ($this->integrationFilter !== 'all') {
            $this->integrationFilter = 'all';
        }

        if ($this->mediaFilter !== 'all') {
            $query = $this->applyMediaFilter($query);
        }

        // SECURITY: Only apply compliance filters if user has compliance_read permission
        if ($this->userCan('compliance_read') && (!empty($this->dataStatusFilter) || !empty($this->issueTypeFilters))) {
            $query = $this->applyDataStatusFilter($query);
        } elseif (!$this->userCan('compliance_read')) {
            $this->dataStatusFilter = null;
            $this->issueTypeFilters = [];
        }

        return $query;
    }

    private function applyDataStatusFilter(Builder $query): Builder
    {
        if (!empty($this->issueTypeFilters)) {
            // SECURITY: Remove price/stock issue types if user lacks permissions
            $effectiveFilters = $this->issueTypeFilters;
            if (!$this->userCan('prices_read')) {
                $effectiveFilters = array_values(array_filter($effectiveFilters, fn($f) => $f !== 'zero_price'));
            }
            if (!$this->userCan('stock_read')) {
                $effectiveFilters = array_values(array_filter($effectiveFilters, fn($f) => $f !== 'low_stock'));
            }

            foreach ($effectiveFilters as $issueType) {
                switch ($issueType) {
                    case 'zero_price':
                        $query->whereHas('prices', function ($q) {
                            $q->whereHas('priceGroup', fn($pq) => $pq->where('is_active', true))
                              ->where('price_net', '<=', 0);
                        });
                        break;
                    case 'low_stock':
                        $query->whereHas('stock', function ($q) {
                            $q->whereHas('warehouse', fn($wq) => $wq->where('is_default', true))
                              ->whereColumn('quantity', '<', 'minimum_stock')
                              ->where('minimum_stock', '>', 0);
                        });
                        break;
                    case 'no_images':
                        $query->whereDoesntHave('media', fn($q) => $q->where('is_active', true));
                        break;
                    case 'not_in_prestashop':
                        $query->whereDoesntHave('shopData');
                        break;
                    case 'discrepancy':
                        $query->whereHas('shopData', function ($q) {
                            $q->where('sync_status', '!=', 'synced');
                        });
                        break;
                }
            }
        }

        if ($this->dataStatusFilter === 'issues') {
            $query->where(function ($q) {
                // SECURITY: Only include price/stock issue checks if user has permission
                if ($this->userCan('prices_read')) {
                    $q->whereHas('prices', function ($pq) {
                        $pq->whereHas('priceGroup', fn($pg) => $pg->where('is_active', true))
                           ->where('price_net', '<=', 0);
                    });
                }
                if ($this->userCan('stock_read')) {
                    $q->orWhereHas('stock', function ($sq) {
                        $sq->whereHas('warehouse', fn($wq) => $wq->where('is_default', true))
                           ->whereColumn('quantity', '<', 'minimum_stock')
                           ->where('minimum_stock', '>', 0);
                    });
                }
                $q->orWhereDoesntHave('media', fn($mq) => $mq->where('is_active', true))
                  ->orWhereDoesntHave('shopData');
            });
        } elseif ($this->dataStatusFilter === 'ok') {
            if ($this->userCan('prices_read')) {
                $query->whereDoesntHave('prices', function ($pq) {
                    $pq->whereHas('priceGroup', fn($pg) => $pg->where('is_active', true))
                       ->where('price_net', '<=', 0);
                });
            }
            $query->whereHas('media', fn($mq) => $mq->where('is_active', true))
                  ->whereHas('shopData');
        }

        return $query;
    }

    private function applyIntegrationFilter(Builder $query): Builder
    {
        switch ($this->integrationFilter) {
            case 'synced':
                return $query->where('last_sync_status', 'success')
                            ->whereNotNull('last_sync_at');
            case 'pending':
                return $query->where(function ($q) {
                    $q->whereNull('last_sync_at')
                      ->orWhere('last_sync_status', 'pending');
                });
            case 'error':
                return $query->where('last_sync_status', 'error');
            default:
                return $query;
        }
    }

    private function applyMediaFilter(Builder $query): Builder
    {
        switch ($this->mediaFilter) {
            case 'has_images':
                return $query->whereHas('images');
            case 'no_images':
                return $query->whereDoesntHave('images');
            case 'primary_image':
                return $query->whereHas('images', function ($q) {
                    $q->where('is_primary', true);
                });
            default:
                return $query;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES - Dynamic Filter Options
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function availableProductTypes(): Collection
    {
        return ProductType::active()
            ->ordered()
            ->get(['id', 'name', 'slug', 'label_color']);
    }

    #[Computed]
    public function availablePriceGroups(): Collection
    {
        return PriceGroup::active()
            ->ordered()
            ->get(['id', 'name', 'code', 'is_default']);
    }

    #[Computed]
    public function availableWarehouses(): Collection
    {
        return Warehouse::active()
            ->ordered()
            ->get(['id', 'name', 'code', 'is_default']);
    }

    #[Computed]
    public function availableProductStatuses(): array
    {
        return ProductStatus::getForSelect();
    }

    #[Computed]
    public function syncStatusOptions(): array
    {
        return [
            ProductShopData::STATUS_SYNCED => 'Zsynchronizowane',
            ProductShopData::STATUS_PENDING => 'Oczekujące',
            ProductShopData::STATUS_SYNCING => 'W trakcie synchronizacji',
            ProductShopData::STATUS_ERROR => 'Błąd synchronizacji',
            ProductShopData::STATUS_CONFLICT => 'Konflikt danych',
            ProductShopData::STATUS_DISABLED => 'Wyłączone',
        ];
    }
}
