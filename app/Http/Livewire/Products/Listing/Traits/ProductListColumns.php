<?php

namespace App\Http\Livewire\Products\Listing\Traits;

use App\Models\Category;
use App\Models\PrestaShopShop;
use App\DTOs\ProductStatusDTO;
use App\Services\Product\ProductStatusAggregator;
use App\Services\JobProgressService;
use Livewire\Attributes\Computed;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * ProductListColumns Trait
 *
 * Manages sorting, display settings, and computed properties for columns:
 * - Sort column and direction
 * - Per page and view mode
 * - Computed properties: products, categories, availableShops, productStatuses
 * - Active job progress tracking
 *
 * @package App\Http\Livewire\Products\Listing\Traits
 */
trait ProductListColumns
{
    /*
    |--------------------------------------------------------------------------
    | SORTING & DISPLAY PROPERTIES
    |--------------------------------------------------------------------------
    */

    public string $sortBy = 'updated_at';
    public string $sortDirection = 'desc';
    public int $perPage = 25;
    public string $viewMode = 'table';

    // Sync status polling
    public ?int $previousActiveSyncJobCount = null;

    /*
    |--------------------------------------------------------------------------
    | SORTING & DISPLAY METHODS
    |--------------------------------------------------------------------------
    */

    public function setSortColumn(string $column): void
    {
        // SECURITY: Block sorting by price/stock without permissions
        if ($column === 'price' && !$this->userCan('prices_read')) {
            return;
        }
        if ($column === 'stock' && !$this->userCan('stock_read')) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
        $this->saveUserPreferences();
    }

    public function changePerPage(int $perPage): void
    {
        $this->perPage = $perPage;
        $this->resetPage();
        $this->saveUserPreferences();
    }

    public function changeViewMode(string $mode): void
    {
        $this->viewMode = $mode;
        $this->saveUserPreferences();

        $this->dispatch('view-mode-changed', mode: $mode);
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    public function getProductsProperty(): LengthAwarePaginator
    {
        $query = $this->buildProductQuery();
        return $query->paginate($this->perPage, ['*'], 'page');
    }

    public function getCategoriesProperty()
    {
        return Category::select(['id', 'name', 'path'])
                      ->active()
                      ->orderBy('name', 'asc')
                      ->get();
    }

    public function getAvailableShopsProperty()
    {
        return PrestaShopShop::select(['id', 'name', 'url', 'prestashop_version', 'is_active'])
                             ->where('is_active', true)
                             ->orderBy('name', 'asc')
                             ->get();
    }

    #[Computed]
    public function selectedCount(): int
    {
        return count($this->selectedProducts);
    }

    #[Computed]
    public function totalFilteredCount(): int
    {
        return $this->buildProductQuery()->count();
    }

    #[Computed]
    public function productStatuses(): array
    {
        $paginator = $this->products;

        if ($paginator->isEmpty()) {
            return [];
        }

        $products = $paginator->getCollection();
        $statuses = app(ProductStatusAggregator::class)->aggregateForProducts($products);

        // SECURITY: Filter sensitive issues based on permissions
        $canReadPrices = $this->userCan('prices_read');
        $canReadStock = $this->userCan('stock_read');

        if (!$canReadPrices || !$canReadStock) {
            foreach ($statuses as $productId => $status) {
                if (!$canReadPrices) {
                    $status->clearGlobalIssue(ProductStatusDTO::ISSUE_ZERO_PRICE);
                }
                if (!$canReadStock) {
                    $status->clearGlobalIssue(ProductStatusDTO::ISSUE_LOW_STOCK);
                }
            }
        }

        return $statuses;
    }

    public function getProductStatus(int $productId): ?ProductStatusDTO
    {
        $statuses = $this->productStatuses;
        return $statuses[$productId] ?? null;
    }

    /*
    |--------------------------------------------------------------------------
    | JOB PROGRESS TRACKING
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function activeJobProgress(): array
    {
        $progressService = app(JobProgressService::class);
        $activeJobs = $progressService->getActiveJobs();

        $recentlyCompletedJobs = \App\Models\JobProgress::with('shop:id,name')
            ->whereIn('status', ['completed', 'failed'])
            ->where('completed_at', '>=', now()->subSeconds(30))
            ->orderBy('completed_at', 'desc')
            ->get();

        $allJobs = $activeJobs->merge($recentlyCompletedJobs);

        $filteredJobs = $allJobs->filter(function ($job) {
            return $job->job_type !== 'category_delete';
        });

        return $filteredJobs->map(function ($job) {
            return [
                'id' => $job->id,
                'job_id' => $job->job_id,
                'job_type' => $job->job_type,
                'shop_id' => $job->shop_id,
                'shop_name' => $job->shop?->name,
                'status' => $job->status,
                'progress_percentage' => $job->progress_percentage,
                'current_count' => $job->current_count,
                'total_count' => $job->total_count,
                'error_count' => $job->error_count,
                'started_at' => $job->started_at?->diffForHumans(),
            ];
        })->toArray();
    }

    public function getRecentJobHistory(): array
    {
        $progressService = app(JobProgressService::class);
        $recentJobs = $progressService->getRecentJobs();

        return $recentJobs->map(function ($job) {
            return [
                'id' => $job->id,
                'job_type' => $job->job_type,
                'shop_id' => $job->shop_id,
                'shop_name' => $job->shop?->name,
                'status' => $job->status,
                'progress_percentage' => $job->progress_percentage,
                'current_count' => $job->current_count,
                'total_count' => $job->total_count,
                'error_count' => $job->error_count,
                'duration_seconds' => $job->duration_seconds,
                'started_at' => $job->started_at?->format('Y-m-d H:i:s'),
                'completed_at' => $job->completed_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    public function getJobProgressDetails(int $progressId): ?array
    {
        $progressService = app(JobProgressService::class);
        $summary = $progressService->getProgressSummary($progressId);

        if (!$summary) {
            return null;
        }

        $progress = \App\Models\JobProgress::find($progressId);

        return array_merge($summary, [
            'error_details' => $progress?->error_details ?? [],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PRICE & STOCK COLUMNS
    |--------------------------------------------------------------------------
    */

    public string $priceDisplayMode = 'netto';

    public function togglePriceDisplay(): void
    {
        $this->priceDisplayMode = $this->priceDisplayMode === 'netto' ? 'brutto' : 'netto';
        $this->saveUserPreferences();
    }

    /**
     * Get default price for product (from default price group)
     */
    public function getDefaultPriceForProduct(\App\Models\Product $product): ?float
    {
        if (!$this->userCan('prices_read')) {
            return null;
        }

        if (!$product->relationLoaded('prices')) {
            return null;
        }

        $defaultPrice = $product->prices->first(function ($price) {
            return $price->priceGroup && $price->priceGroup->is_default;
        });

        if (!$defaultPrice) {
            $defaultPrice = $product->prices->first();
        }

        if (!$defaultPrice) {
            return null;
        }

        return $this->priceDisplayMode === 'netto'
            ? (float) ($defaultPrice->price_net ?? 0)
            : (float) ($defaultPrice->price_gross ?? 0);
    }

    /**
     * Get all prices for product (tooltip data)
     */
    public function getAllPricesForProduct(\App\Models\Product $product): array
    {
        if (!$this->userCan('prices_read')) {
            return [];
        }

        if (!$product->relationLoaded('prices')) {
            return [];
        }

        return $product->prices
            ->filter(fn($p) => $p->priceGroup)
            ->map(function ($price) {
                return [
                    'group' => $price->priceGroup->name,
                    'netto' => (float) ($price->price_net ?? 0),
                    'brutto' => (float) ($price->price_gross ?? 0),
                    'is_default' => (bool) ($price->priceGroup->is_default ?? false),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get default stock for product (from default warehouse)
     */
    public function getDefaultStockForProduct(\App\Models\Product $product): ?int
    {
        if (!$this->userCan('stock_read')) {
            return null;
        }

        if (!$product->relationLoaded('stock')) {
            return null;
        }

        $defaultStock = $product->stock->first(function ($stock) {
            return $stock->warehouse && $stock->warehouse->is_default;
        });

        if (!$defaultStock) {
            $defaultStock = $product->stock->first();
        }

        return $defaultStock ? (int) ($defaultStock->quantity ?? 0) : null;
    }

    /**
     * Get all stock levels for product (tooltip data)
     * Shows ALL active warehouses - even those without stock records (shown as 0)
     */
    public function getAllStockForProduct(\App\Models\Product $product): array
    {
        if (!$this->userCan('stock_read')) {
            return [];
        }

        // Get all active warehouses (cached via computed)
        $warehouses = $this->allActiveWarehouses;

        // Build lookup from product stock records
        $stockByWarehouse = [];
        if ($product->relationLoaded('stock')) {
            foreach ($product->stock as $stock) {
                if ($stock->warehouse_id) {
                    $stockByWarehouse[$stock->warehouse_id] = $stock;
                }
            }
        }

        // Build result for ALL active warehouses
        $result = [];
        foreach ($warehouses as $warehouse) {
            $stock = $stockByWarehouse[$warehouse->id] ?? null;

            $quantity = $stock ? (int) ($stock->quantity ?? 0) : 0;
            $reserved = $stock ? (int) ($stock->reserved_quantity ?? 0) : 0;
            $available = $stock ? (int) ($stock->available_quantity ?? ($quantity - $reserved)) : 0;
            $minimumStock = $stock ? (int) ($stock->minimum_stock ?? 0) : (int) ($warehouse->default_minimum_stock ?? 0);

            $result[] = [
                'warehouse' => $warehouse->name,
                'quantity' => $quantity,
                'reserved' => $reserved,
                'available' => $available,
                'minimum_stock' => $minimumStock,
                'is_low' => $minimumStock > 0 && $available <= $minimumStock && $available > 0,
                'is_out' => $available <= 0,
                'is_default' => (bool) $warehouse->is_default,
            ];
        }

        return $result;
    }

    /**
     * Cached list of all active warehouses (prevents N+1)
     */
    #[\Livewire\Attributes\Computed]
    public function allActiveWarehouses(): \Illuminate\Support\Collection
    {
        return \App\Models\Warehouse::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_default', 'default_minimum_stock']);
    }

    /**
     * Format price for display
     */
    public function formatPrice(?float $price): string
    {
        if ($price === null) {
            return '-';
        }
        return number_format($price, 2, ',', ' ') . ' zl';
    }

    /**
     * Get stock CSS class based on quantity
     */
    public function getStockIndicatorClass(?int $quantity): string
    {
        if ($quantity === null) {
            return 'stock-indicator--unknown';
        }
        if ($quantity > 10) {
            return 'stock-indicator--high';
        }
        if ($quantity > 0) {
            return 'stock-indicator--low';
        }
        return 'stock-indicator--zero';
    }
}
