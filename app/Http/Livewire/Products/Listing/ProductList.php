<?php

namespace App\Http\Livewire\Products\Listing;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Models\Product;
use App\Models\Category;
use App\Models\PrestaShopShop;
use App\Models\ProductShopData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Jobs\PrestaShop\BulkImportProducts;
use App\Services\JobProgressService;

/**
 * ProductList Component - Main product listing interface
 *
 * Features:
 * - Server-side pagination with configurable per-page options
 * - Advanced filtering (search, category, status, stock)
 * - Bulk selection and operations
 * - Multiple view modes (table, grid, compact)
 * - Real-time search with debouncing
 * - Column sorting and customization
 *
 * Performance:
 * - Optimized queries with proper eager loading
 * - Indexed searches for <100ms response time
 * - Efficient pagination for 100K+ products
 *
 * Business Logic:
 * - Permission-based feature access
 * - User preferences persistence
 * - Stock status calculations
 * - Integration status tracking
 *
 * @package App\Http\Livewire\Products\Listing
 * @version 1.0
 * @since ETAP_05 - Products Module
 */
class ProductList extends Component
{
    use WithPagination;

    /*
    |--------------------------------------------------------------------------
    | PUBLIC PROPERTIES - Component State
    |--------------------------------------------------------------------------
    */

    // Search & Filtering
    public string $search = '';
    public string $categoryFilter = '';
    public string $statusFilter = 'all'; // all, active, inactive
    public string $stockFilter = 'all'; // all, in_stock, low_stock, out_of_stock
    public string $productTypeFilter = 'all'; // all, vehicle, spare_part, clothing, other

    // ETAP_05 - Advanced Filters (1.1.1.2.4-1.1.1.2.8)
    public float $priceMin = 0;
    public float $priceMax = 10000;
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $dateType = 'created_at'; // created_at, updated_at, last_sync
    public string $integrationFilter = 'all'; // all, synced, pending, error
    public string $mediaFilter = 'all'; // all, has_images, no_images, primary_image

    // Sorting & Display
    public string $sortBy = 'updated_at';
    public string $sortDirection = 'desc';
    public int $perPage = 25;
    public string $viewMode = 'table'; // table, grid, compact

    // Bulk Operations
    public array $selectedProducts = [];
    public bool $selectAll = false;
    public bool $selectingAllPages = false; // True when user selects ALL products across pagination

    // UI State
    public bool $showFilters = false;
    public bool $showBulkActions = false;

    // FAZA 1.5: Multi-Store Features - Modal State
    public bool $showPreviewModal = false;
    public ?Product $selectedProduct = null;
    public bool $showDeleteModal = false;
    public ?int $productToDelete = null;

    // Quick Send to Shops (Bulk Action)
    public bool $showQuickSendModal = false;
    public array $selectedShopsForBulk = [];

    // Bulk Delete Modal
    public bool $showBulkDeleteModal = false;

    // ETAP_07a FAZA 2: Bulk Category Operations
    // Bulk Assign Categories Modal
    public bool $showBulkAssignCategoriesModal = false;
    public array $selectedCategoriesForBulk = [];
    public ?int $primaryCategoryForBulk = null;

    // Bulk Remove Categories Modal
    public bool $showBulkRemoveCategoriesModal = false;
    public array $commonCategories = [];
    public array $categoriesToRemove = [];

    // Bulk Move Categories Modal
    public bool $showBulkMoveCategoriesModal = false;
    public ?int $fromCategoryId = null;
    public ?int $toCategoryId = null;
    public string $moveMode = 'replace'; // replace|add_keep

    // ETAP_07 FAZA 3: Import Modal State
    public bool $showImportModal = false;
    public ?int $importShopId = null;
    public string $importMode = 'all'; // all, category, individual
    public ?int $importCategoryId = null;
    public array $selectedProductsToImport = [];
    public array $prestashopProducts = [];
    public array $prestashopCategories = [];
    public array $expandedCategories = []; // Track which categories are expanded
    public array $cachedCategoryChildren = []; // Cache: category_id => children array
    public array $cachedProductSearches = []; // Cache: search_term => products array
    public string $importSearch = ''; // CRITICAL: For name/SKU search
    public bool $importIncludeSubcategories = true;

    // ETAP_07 FAZA 3D: Category Preview Loading State
    public bool $isAnalyzingCategories = false; // True when AnalyzeMissingCategories job is running
    public ?string $analyzingShopName = null; // Shop name being analyzed (for display)

    // ETAP_07 FAZA 3D: Track shown previews to prevent polling from showing same modal multiple times
    // CRITICAL: MUST be public for Livewire to track across poll cycles!
    public array $shownPreviewIds = []; // Preview IDs that have been shown (prevents duplicate modal opens)

    // Computed
    public bool $hasFilters = false;

    /*
    |--------------------------------------------------------------------------
    | COMPONENT LIFECYCLE
    |--------------------------------------------------------------------------
    */

    /**
     * Initialize component state
     */
    public function mount(): void
    {
        // Load user preferences if available
        $this->loadUserPreferences();

        // Update computed properties
        $this->updateHasFilters();
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Get paginated products with applied filters
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getProductsProperty(): LengthAwarePaginator
    {
        // Build query with filters and sorting
        $query = $this->buildProductQuery();

        // Get paginated results
        return $query->paginate($this->perPage, ['*'], 'page');
    }

    /**
     * Get available categories for filter dropdown
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCategoriesProperty()
    {
        // Get available categories for filter dropdown
        return Category::select(['id', 'name', 'path'])
                      ->active()
                      ->orderBy('name', 'asc')
                      ->get();
    }

    /**
     * Get all PrestaShop shops for import selector
     *
     * CRITICAL FIX: Replaces inline App\Models\PrestaShopShop::all() in blade template
     * Performance: Cached computed property instead of N+1 queries
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableShopsProperty()
    {
        return PrestaShopShop::select(['id', 'name', 'url', 'prestashop_version', 'is_active'])
                             ->where('is_active', true)
                             ->orderBy('name', 'asc')
                             ->get();
    }

    /**
     * Get count of selected products
     *
     * @return int
     */
    #[Computed]
    public function selectedCount(): int
    {
        return count($this->selectedProducts);
    }

    /**
     * Get total count of products matching current filters (across all pages)
     *
     * Used for "Select all X products" banner
     *
     * @return int
     */
    #[Computed]
    public function totalFilteredCount(): int
    {
        return $this->buildProductQuery()->count();
    }

    /**
     * Check if any filters are active
     *
     * @return bool
     */
    public function getHasFiltersProperty(): bool
    {
        return !empty($this->search)
            || $this->categoryFilter !== ''
            || $this->statusFilter !== 'all'
            || $this->stockFilter !== 'all'
            || $this->productTypeFilter !== 'all'
            || $this->priceMin > 0
            || $this->priceMax < 10000
            || !empty($this->dateFrom)
            || !empty($this->dateTo)
            || $this->integrationFilter !== 'all'
            || $this->mediaFilter !== 'all';
    }

    /**
     * Update hasFilters property
     */
    private function updateHasFilters(): void
    {
        $this->hasFilters = !empty($this->search)
            || $this->categoryFilter !== ''
            || $this->statusFilter !== 'all'
            || $this->stockFilter !== 'all'
            || $this->productTypeFilter !== 'all'
            || $this->priceMin > 0
            || $this->priceMax < 10000
            || !empty($this->dateFrom)
            || !empty($this->dateTo)
            || $this->integrationFilter !== 'all'
            || $this->mediaFilter !== 'all';
    }


    /*
    |--------------------------------------------------------------------------
    | SEARCH & FILTERING
    |--------------------------------------------------------------------------
    */

    /**
     * Handle search input changes (with debouncing)
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    /**
     * Handle category filter changes
     */
    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    /**
     * Handle status filter changes
     */
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    /**
     * Handle stock filter changes
     */
    public function updatedStockFilter(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    /**
     * Handle product type filter changes
     */
    public function updatedProductTypeFilter(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    /**
     * Clear all filters
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
            'dateFrom',
            'dateTo',
            'dateType',
            'integrationFilter',
            'mediaFilter'
        ]);

        // Reset advanced filter defaults
        $this->priceMin = 0;
        $this->priceMax = 10000;
        $this->dateType = 'created_at';
        $this->resetPage();
        $this->resetSelection();

        $this->dispatch('filters-cleared');
    }

    /*
    |--------------------------------------------------------------------------
    | SORTING & DISPLAY
    |--------------------------------------------------------------------------
    */

    /**
     * Handle column sorting
     *
     * @param string $column
     */
    public function sortBy(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
        $this->saveUserPreferences();
    }

    /**
     * Change items per page
     *
     * @param int $perPage
     */
    public function changePerPage(int $perPage): void
    {
        $this->perPage = $perPage;
        $this->resetPage();
        $this->saveUserPreferences();
    }

    /**
     * Change view mode
     *
     * @param string $mode
     */
    public function changeViewMode(string $mode): void
    {
        $this->viewMode = $mode;
        $this->saveUserPreferences();

        $this->dispatch('view-mode-changed', mode: $mode);
    }

    /*
    |--------------------------------------------------------------------------
    | BULK OPERATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Handle select all toggle
     */
    public function updatedSelectAll(): void
    {
        if ($this->selectAll) {
            // Select all products on current page
            $this->selectedProducts = $this->products->pluck('id')->toArray();
        } else {
            $this->resetSelection();
        }

        // Reset "selecting all pages" when user manually toggles selectAll
        $this->selectingAllPages = false;

        $this->updateBulkActionsVisibility();
    }

    /**
     * Handle individual product selection
     */
    public function updatedSelectedProducts(): void
    {
        $this->selectAll = count($this->selectedProducts) === $this->products->count();
        $this->updateBulkActionsVisibility();
    }

    /**
     * Toggle product selection
     *
     * @param int $productId
     */
    public function toggleSelection(int $productId): void
    {
        if (in_array($productId, $this->selectedProducts)) {
            $this->selectedProducts = array_diff($this->selectedProducts, [$productId]);
        } else {
            $this->selectedProducts[] = $productId;
        }

        $this->updatedSelectedProducts();
    }

    /**
     * Select ALL products across all pages (matching current filters)
     *
     * This method selects ALL product IDs that match the current filters,
     * not just the products visible on the current page.
     */
    public function selectAllPages(): void
    {
        // Get ALL product IDs matching current filters (across all pages)
        $this->selectedProducts = $this->buildProductQuery()
            ->pluck('id')
            ->toArray();

        $this->selectAll = true;
        $this->selectingAllPages = true;

        $this->updateBulkActionsVisibility();

        $this->dispatch('success', message: sprintf(
            'Zaznaczono wszystkie %d produktów pasujących do filtrów',
            count($this->selectedProducts)
        ));
    }

    /**
     * Deselect all pages (return to current page selection only)
     */
    public function deselectAllPages(): void
    {
        // Return to "current page only" selection
        $this->selectedProducts = $this->products->pluck('id')->toArray();
        $this->selectAll = true;
        $this->selectingAllPages = false;

        $this->updateBulkActionsVisibility();
    }

    /**
     * Reset selection
     */
    public function resetSelection(): void
    {
        $this->reset(['selectedProducts', 'selectAll', 'selectingAllPages']);
        $this->updateBulkActionsVisibility();
    }

    /**
     * Update bulk actions visibility
     */
    private function updateBulkActionsVisibility(): void
    {
        $this->showBulkActions = count($this->selectedProducts) > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | QUICK ACTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Toggle product active status
     *
     * @param int $productId
     */
    public function toggleStatus(int $productId): void
    {
        $product = Product::find($productId);

        if (!$product) {
            $this->dispatch('error', message: 'Produkt nie został znaleziony');
            return;
        }

        $product->is_active = !$product->is_active;
        $product->save();

        $status = $product->is_active ? 'aktywowany' : 'deaktywowany';
        $this->dispatch('success', message: "Produkt został {$status}");
    }

    // ==========================================
    // FAZA 1.5: MULTI-STORE ACTIONS
    // ==========================================

    /**
     * Show product preview modal
     *
     * @param int $productId
     */
    public function showProductPreview(int $productId): void
    {
        $this->selectedProduct = Product::with(['productType', 'shopData.shop'])
                                      ->find($productId);

        if (!$this->selectedProduct) {
            $this->dispatch('error', message: 'Produkt nie został znaleziony');
            return;
        }

        $this->showPreviewModal = true;
    }

    /**
     * Close product preview modal
     */
    public function closePreviewModal(): void
    {
        $this->showPreviewModal = false;
        $this->selectedProduct = null;
    }

    /**
     * Synchronize product with all shops
     *
     * @param int $productId
     */
    public function syncProduct(int $productId): void
    {
        $product = Product::find($productId);
        if (!$product) {
            $this->dispatch('error', message: 'Produkt nie został znaleziony');
            return;
        }

        try {
            // Mark all shop data as pending for sync
            $updatedCount = $product->markAllShopsForSync();

            // Close modal if open
            if ($this->showPreviewModal && $this->selectedProduct?->id === $productId) {
                $this->closePreviewModal();
            }

            if ($updatedCount > 0) {
                $this->dispatch('success', message: "Synchronizacja produktu {$product->sku} została zaplanowana dla {$updatedCount} sklepów");
            } else {
                $this->dispatch('info', message: "Produkt {$product->sku} nie ma skonfigurowanych sklepów do synchronizacji");
            }

        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Błąd podczas planowania synchronizacji: ' . $e->getMessage());
        }
    }

    /**
     * Publish product to shops
     *
     * @param int $productId
     */
    public function publishToShops(int $productId): void
    {
        $product = Product::find($productId);
        if (!$product) {
            $this->dispatch('error', message: 'Produkt nie został znaleziony');
            return;
        }

        try {
            // Get active shops for publishing
            $activeShops = \App\Models\PrestaShopShop::active()->get();

            if ($activeShops->isEmpty()) {
                $this->dispatch('warning', message: 'Brak aktywnych sklepów do publikacji');
                return;
            }

            $publishedCount = 0;
            foreach ($activeShops as $shop) {
                $shopData = $product->publishToShop($shop->id);
                if ($shopData) {
                    $publishedCount++;
                }
            }

            $this->dispatch('success', message: "Produkt {$product->sku} został opublikowany na {$publishedCount} sklepach");

        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Błąd podczas publikacji: ' . $e->getMessage());
        }
    }

    /**
     * Confirm product deletion
     *
     * @param int $productId
     */
    /**
     * Confirm single product deletion - ALWAYS show modal (permanent delete)
     *
     * CRITICAL FIX 2025-10-07: Removed canDelete() check
     * Quick Action delete should ALWAYS show confirmation modal,
     * just like bulk delete, and allow FORCE DELETE with all associations
     */
    public function confirmDelete(int $productId): void
    {
        $product = Product::find($productId);
        if (!$product) {
            $this->dispatch('error', message: 'Produkt nie został znaleziony');
            return;
        }

        // ALWAYS show modal (removed canDelete() check)
        // User will see warning about permanent deletion with all associations
        $this->productToDelete = $productId;
        $this->showDeleteModal = true;
    }

    /**
     * Delete product after confirmation - PERMANENT (force delete)
     *
     * CRITICAL FIX 2025-10-07: Changed to forceDelete()
     * Quick Action delete performs PERMANENT deletion with all associations,
     * just like bulk delete
     */
    public function deleteProduct(): void
    {
        if (!$this->productToDelete) {
            $this->dispatch('error', message: 'Brak produktu do usunięcia');
            return;
        }

        $product = Product::find($this->productToDelete);
        if (!$product) {
            $this->dispatch('error', message: 'Produkt nie został znaleziony');
            $this->cancelDelete();
            return;
        }

        try {
            $sku = $product->sku;

            // FORCE DELETE - permanently remove product from database with all associations
            // Note: Product model uses SoftDeletes, so we use forceDelete() for permanent removal
            $product->forceDelete();

            Log::info('Quick Action delete completed', [
                'product_id' => $this->productToDelete,
                'sku' => $sku,
            ]);

            $this->dispatch('success', message: "Produkt {$sku} został trwale usunięty");
            $this->cancelDelete();

            // Refresh products list
            unset($this->products);

        } catch (\Exception $e) {
            Log::error('Quick Action delete failed', [
                'product_id' => $this->productToDelete,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('error', message: 'Błąd podczas usuwania produktu: ' . $e->getMessage());
        }
    }

    /**
     * Cancel product deletion
     */
    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->productToDelete = null;
    }

    /**
     * Duplicate product
     *
     * @param int $productId
     */
    public function duplicateProduct(int $productId): void
    {
        $product = Product::find($productId);

        if (!$product) {
            $this->dispatch('error', message: 'Produkt nie został znaleziony');
            return;
        }

        // Create duplicate with modified SKU
        $newProduct = $product->replicate();
        $newProduct->sku = $this->generateDuplicateSku($product->sku);
        $newProduct->name = $product->name . ' (kopia)';
        $newProduct->is_active = false; // Set as inactive by default
        $newProduct->save();

        $this->dispatch('success', message: 'Produkt został zduplikowany');
        $this->dispatch('product-duplicated', productId: $newProduct->id);
    }

    /*
    |--------------------------------------------------------------------------
    | PRIVATE METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Build product query with filters and sorting
     *
     * @return Builder
     */
    private function buildProductQuery(): Builder
    {
        $query = Product::query()
            ->with([
                'productType:id,name,slug',
                // FAZA 1.5: Multi-Store Sync Status - Eager load shop data for sync status display
                // ETAP_07 OPCJA B (2025-10-13): Consolidated sync tracking in product_shop_data
                'shopData:id,product_id,shop_id,sync_status,is_published,last_sync_at',
                'shopData.shop:id,name' // Load shop relation through shopData (replaces syncStatuses.shop)
            ])
            ->select([
                'id', 'sku', 'name', 'product_type_id', 'manufacturer',
                'supplier_code', 'is_active', 'is_variant_master',
                'created_at', 'updated_at'
            ]);

        // Apply search filter
        if (!empty($this->search)) {
            $query->search($this->search);
        }

        // Apply category filter
        if (!empty($this->categoryFilter)) {
            $query->whereHas('categories', function ($q) {
                $q->where('categories.id', $this->categoryFilter);
            });
        }

        // Apply status filter
        if ($this->statusFilter !== 'all') {
            $query->where('is_active', $this->statusFilter === 'active');
        }

        // Apply product type filter
        if ($this->productTypeFilter !== 'all') {
            $query->byType($this->productTypeFilter);
        }

        // Apply stock filter
        if ($this->stockFilter !== 'all') {
            $query = $this->applyStockFilter($query);
        }

        // ETAP_05 - Advanced Filters Implementation
        $query = $this->applyAdvancedFilters($query);

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        // Always include secondary sort for consistency
        if ($this->sortBy !== 'id') {
            $query->orderBy('id', 'desc');
        }

        return $query;
    }

    /**
     * Apply stock filter to query
     *
     * @param Builder $query
     * @return Builder
     */
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
                      ->where('available_quantity', '<=', 10); // TODO: Make configurable
                });

            case 'out_of_stock':
                return $query->whereDoesntHave('activeStock', function ($q) {
                    $q->where('available_quantity', '>', 0);
                });

            default:
                return $query;
        }
    }

    /**
     * Apply advanced filters (ETAP_05: 1.1.1.2.4-1.1.1.2.8)
     *
     * @param Builder $query
     * @return Builder
     */
    private function applyAdvancedFilters(Builder $query): Builder
    {
        // 1.1.1.2.4: Price range filter
        if ($this->priceMin > 0 || $this->priceMax < 10000) {
            $query->whereHas('prices', function ($q) {
                $q->whereBetween('price', [$this->priceMin, $this->priceMax]);
            });
        }

        // 1.1.1.2.5: Date range filters
        if (!empty($this->dateFrom)) {
            $query->whereDate($this->dateType, '>=', $this->dateFrom);
        }
        if (!empty($this->dateTo)) {
            $query->whereDate($this->dateType, '<=', $this->dateTo);
        }

        // 1.1.1.2.7: Integration status filter
        if ($this->integrationFilter !== 'all') {
            $query = $this->applyIntegrationFilter($query);
        }

        // 1.1.1.2.8: Media status filter
        if ($this->mediaFilter !== 'all') {
            $query = $this->applyMediaFilter($query);
        }

        return $query;
    }

    /**
     * Apply integration status filter
     *
     * @param Builder $query
     * @return Builder
     */
    private function applyIntegrationFilter(Builder $query): Builder
    {
        switch ($this->integrationFilter) {
            case 'synced':
                // Products successfully synced with integrations
                return $query->where('last_sync_status', 'success')
                            ->whereNotNull('last_sync_at');

            case 'pending':
                // Products pending sync or never synced
                return $query->where(function ($q) {
                    $q->whereNull('last_sync_at')
                      ->orWhere('last_sync_status', 'pending');
                });

            case 'error':
                // Products with sync errors
                return $query->where('last_sync_status', 'error');

            default:
                return $query;
        }
    }

    /**
     * Apply media status filter
     *
     * @param Builder $query
     * @return Builder
     */
    private function applyMediaFilter(Builder $query): Builder
    {
        switch ($this->mediaFilter) {
            case 'has_images':
                // Products with at least one image
                return $query->whereHas('images');

            case 'no_images':
                // Products without images
                return $query->whereDoesntHave('images');

            case 'primary_image':
                // Products with designated primary image
                return $query->whereHas('images', function ($q) {
                    $q->where('is_primary', true);
                });

            default:
                return $query;
        }
    }

    /**
     * Generate unique SKU for duplicate product
     *
     * @param string $originalSku
     * @return string
     */
    private function generateDuplicateSku(string $originalSku): string
    {
        $baseSku = $originalSku . '-COPY';
        $counter = 1;
        $newSku = $baseSku;

        while (Product::where('sku', $newSku)->exists()) {
            $newSku = $baseSku . '-' . $counter;
            $counter++;
        }

        return $newSku;
    }

    /**
     * Load user preferences from session/database
     */
    private function loadUserPreferences(): void
    {
        // TODO: Load from user preferences table or session
        // For now, use session
        if (session()->has('product_list_preferences')) {
            $preferences = session('product_list_preferences');

            $this->perPage = $preferences['per_page'] ?? 25;
            $this->viewMode = $preferences['view_mode'] ?? 'table';
            $this->sortBy = $preferences['sort_by'] ?? 'updated_at';
            $this->sortDirection = $preferences['sort_direction'] ?? 'desc';
        }
    }

    /**
     * Save user preferences to session/database
     */
    private function saveUserPreferences(): void
    {
        $preferences = [
            'per_page' => $this->perPage,
            'view_mode' => $this->viewMode,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
        ];

        session(['product_list_preferences' => $preferences]);
    }

    /*
    |--------------------------------------------------------------------------
    | REAL-TIME PROGRESS TRACKING API (ETAP_07)
    |--------------------------------------------------------------------------
    */

    /**
     * Get active job progress for current user/shop
     *
     * USAGE: Computed property - access as $this->activeJobProgress in blade
     * Returns JSON array of active jobs with progress data
     *
     * @return array
     */
    #[Computed]
    public function activeJobProgress(): array
    {
        $progressService = app(JobProgressService::class);

        // Get all active jobs (pending, running)
        $activeJobs = $progressService->getActiveJobs();

        // ALSO get recently completed/failed jobs (last 30 seconds)
        // This ensures progress bars appear even for fast-completing jobs
        $recentlyCompletedJobs = \App\Models\JobProgress::with('shop:id,name')
            ->whereIn('status', ['completed', 'failed'])
            ->where('completed_at', '>=', now()->subSeconds(30))
            ->orderBy('completed_at', 'desc')
            ->get();

        // Merge active + recently completed
        $allJobs = $activeJobs->merge($recentlyCompletedJobs);

        // FILTER OUT category_delete jobs - those are shown only in CategoryTree
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

    /**
     * Get recent job history (last 24h)
     *
     * USAGE: For displaying completed/failed jobs in UI
     *
     * @return array
     */
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

    /**
     * Get detailed job progress by ID
     *
     * USAGE: For modal/detail view of specific job
     *
     * @param int $progressId
     * @return array|null
     */
    public function getJobProgressDetails(int $progressId): ?array
    {
        $progressService = app(JobProgressService::class);

        $summary = $progressService->getProgressSummary($progressId);

        if (!$summary) {
            return null;
        }

        // Load full JobProgress model for error_details
        $progress = \App\Models\JobProgress::find($progressId);

        return array_merge($summary, [
            'error_details' => $progress?->error_details ?? [],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | COMPONENT RENDER
    |--------------------------------------------------------------------------
    */

    // ==========================================
    // BULK QUICK SEND TO SHOPS
    // ==========================================

    /**
     * Open quick send modal for bulk action
     */
    public function openQuickSendModal(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Wybierz co najmniej jeden produkt.');
            return;
        }

        $this->selectedShopsForBulk = [];
        $this->showQuickSendModal = true;
    }

    /**
     * Close quick send modal
     */
    public function closeQuickSendModal(): void
    {
        $this->showQuickSendModal = false;
        $this->selectedShopsForBulk = [];
    }

    /**
     * Send selected products to selected shops
     */
    public function bulkSendToShops(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Wybierz co najmniej jeden produkt.');
            return;
        }

        if (empty($this->selectedShopsForBulk)) {
            $this->dispatch('error', message: 'Wybierz co najmniej jeden sklep.');
            return;
        }

        try {
            DB::transaction(function () {
                $addedCount = 0;

                foreach ($this->selectedProducts as $productId) {
                    $product = Product::find($productId);
                    if (!$product) continue;

                    foreach ($this->selectedShopsForBulk as $shopId) {
                        // Check if product is already exported to this shop
                        $exists = ProductShopData::where('product_id', $productId)
                            ->where('shop_id', $shopId)
                            ->exists();

                        if (!$exists) {
                            ProductShopData::create([
                                'product_id' => $productId,
                                'shop_id' => $shopId,
                                'name' => $product->name,
                                'slug' => $product->slug,
                                'short_description' => $product->short_description,
                                'long_description' => $product->long_description,
                                'meta_title' => $product->meta_title,
                                'meta_description' => $product->meta_description,
                                'category_mappings' => [],
                                'attribute_mappings' => [],
                                'image_settings' => [],
                                'sync_status' => 'pending',
                                'is_published' => false,
                            ]);
                            $addedCount++;
                        }
                    }
                }

                $productsCount = count($this->selectedProducts);
                $shopsCount = count($this->selectedShopsForBulk);

                $this->dispatch('success', message: "Wysłano {$productsCount} produktów do {$shopsCount} sklepów. Dodano {$addedCount} nowych powiązań.");
            });

            // Reset selection and close modal
            $this->resetSelection();
            $this->closeQuickSendModal();

        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Wystąpił błąd podczas wysyłania produktów do sklepów.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | IMPORT FROM PRESTASHOP METHODS (ETAP_07 FAZA 3)
    |--------------------------------------------------------------------------
    */

    /**
     * Open import modal with selected mode
     *
     * @param string $mode all|category|individual
     */
    public function openImportModal(string $mode = 'all'): void
    {
        $this->importMode = $mode;
        $this->showImportModal = true;
        $this->importShopId = null;
        $this->selectedProductsToImport = [];
        $this->prestashopProducts = [];
        $this->importSearch = '';
        $this->prestashopCategories = [];
    }

    /**
     * Close import modal and reset state
     */
    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->importShopId = null;
        $this->importMode = 'all';
        $this->importCategoryId = null;
        $this->selectedProductsToImport = [];
        $this->prestashopProducts = [];
        $this->prestashopCategories = [];
        $this->importSearch = '';

        // Clear caches
        $this->expandedCategories = [];
        $this->cachedCategoryChildren = [];
        $this->cachedProductSearches = [];
    }

    /**
     * Reset shop selection to go back to shop selector
     *
     * CRITICAL FIX: "Zmień sklep" button should return to shop selection,
     * not close the entire modal
     */
    public function resetShopSelection(): void
    {
        $this->importShopId = null;
        $this->importCategoryId = null;
        $this->selectedProductsToImport = [];
        $this->prestashopProducts = [];
        $this->prestashopCategories = [];
        $this->importSearch = '';
        // importMode stays the same - user keeps selected tab
        // showImportModal stays true - modal remains open
    }

    /**
     * Set import shop and load data
     *
     * @param int $shopId
     */
    public function setImportShop(int $shopId): void
    {
        $this->importShopId = $shopId;

        if ($this->importMode === 'category') {
            $this->loadPrestaShopCategories();
        } elseif ($this->importMode === 'individual') {
            $this->loadPrestaShopProducts();
        }
    }

    /**
     * Livewire hook: Called when importShopId property changes
     *
     * CRITICAL FIX: wire:model.live triggers this method, not setImportShop()
     * This ensures automatic data loading when shop is selected from dropdown
     *
     * @param mixed $value New shop ID value
     */
    public function updatedImportShopId($value): void
    {
        if ($value) {
            $this->setImportShop((int) $value);
        }
    }

    /**
     * Livewire hook: Called when importSearch property changes
     *
     * CRITICAL FIX: Auto-trigger product search when user types in search box
     * Works with wire:model.live.debounce.500ms in blade template
     */
    public function updatedImportSearch(): void
    {
        // Only search if minimum 3 characters to avoid API rate limits
        if ($this->importMode === 'individual' && $this->importShopId) {
            if (empty($this->importSearch) || strlen($this->importSearch) < 3) {
                $this->prestashopProducts = [];
                return;
            }

            $this->loadPrestaShopProducts();
        }
    }

    /**
     * Livewire hook: Called when importMode changes (tab switching)
     *
     * CRITICAL FIX: Auto-load data when user switches tabs
     * If shop is already selected, load categories/products automatically
     */
    public function updatedImportMode($value): void
    {
        // Only auto-load if shop is already selected
        if (!$this->importShopId) {
            return;
        }

        // Load appropriate data based on new mode
        if ($value === 'category') {
            $this->loadPrestaShopCategories();
        } elseif ($value === 'individual') {
            // DON'T auto-load products on tab switch - only on search
            // This prevents hitting API limits
            $this->prestashopProducts = [];
        }
    }

    /**
     * Import ALL products from shop
     */
    public function importAllProducts(): void
    {
        if (!$this->importShopId) {
            $this->dispatch('error', message: 'Wybierz sklep PrestaShop');
            return;
        }

        $shop = PrestaShopShop::find($this->importShopId);

        if (!$shop) {
            $this->dispatch('error', message: 'Sklep nie został znaleziony');
            return;
        }

        // 🎯 FAZA 3D: Set loading state for Category Preview Modal
        $this->isAnalyzingCategories = true;
        $this->analyzingShopName = $shop->name;

        // 🚀 CRITICAL: Create PENDING progress record BEFORE dispatch
        $jobId = (string) \Illuminate\Support\Str::uuid();
        $progressService = app(\App\Services\JobProgressService::class);

        $progressService->createPendingJobProgress(
            $jobId,
            $shop,
            'import',
            0
        );

        BulkImportProducts::dispatch($shop, 'all', [], $jobId);

        $this->dispatch('success', message: 'Analizuję kategorie z PrestaShop... To może potrwać kilka sekund.');

        $this->closeImportModal();
    }

    /**
     * Load PrestaShop categories for category mode
     */
    public function loadPrestaShopCategories(): void
    {
        if (!$this->importShopId) {
            return;
        }

        try {
            $shop = PrestaShopShop::find($this->importShopId);

            // Validate shop exists
            if (!$shop) {
                $this->dispatch('error', message: 'Sklep nie został znaleziony');
                return;
            }

            // Validate shop has version configured
            if (empty($shop->version)) {
                $this->dispatch('error', message: 'Sklep nie ma ustawionej wersji PrestaShop. Skonfiguruj wersję w panelu zarządzania sklepami.');
                Log::error('PrestaShop shop missing version', [
                    'shop_id' => $shop->id,
                    'shop_name' => $shop->name,
                ]);
                return;
            }

            // CRITICAL FIX: Use static method instead of app() container injection
            $client = PrestaShopClientFactory::create($shop);

            // PERFORMANCE OPTIMIZATION: Request only ROOT categories (level_depth <= 2)
            // This is for performance - children loaded on-demand when expanded
            // Note: level 0 = "Baza", level 1 = "Wszystko", level 2 = actual categories
            // OPTIMIZATION: Fetch only required fields (100x faster!)
            // Before: 'display' => 'full' (235 KB for 16 categories!)
            // After: selective fields (~5-10 KB for same data)
            // ROLLBACK: Load only root categories (levels 0-2), children loaded on-demand
            $response = $client->getCategories([
                'display' => '[id,name,id_parent,level_depth,nb_products_recursive]',
                'language' => 1,
                'filter[level_depth]' => '[0,2]', // Root categories only (interval 0-2)
            ]);

            // Parse response structure - multiple possible formats
            $this->prestashopCategories = [];

            // DEBUG: Log response structure for selective fields
            Log::debug('loadPrestaShopCategories response structure', [
                'response_keys' => is_array($response) ? array_keys($response) : 'not_array',
                'has_categories_key' => isset($response['categories']),
                'has_prestashop_key' => isset($response['prestashop']),
                'response_sample' => is_array($response) ? array_slice($response, 0, 1) : null,
            ]);

            if (is_array($response)) {
                // Format 1: Nested structure {"categories": [...]}
                if (isset($response['categories']) && is_array($response['categories'])) {
                    // Check if categories is array of category objects or nested further
                    if (isset($response['categories']['category'])) {
                        // Format: {"categories": {"category": [...]}}
                        $categories = $response['categories']['category'];
                        $this->prestashopCategories = is_array($categories) ? (isset($categories[0]) ? $categories : [$categories]) : [];
                        Log::debug('Parsed format 1a (categories.category)', ['count' => count($this->prestashopCategories)]);
                    } else {
                        // Format: {"categories": [...]}
                        $this->prestashopCategories = $response['categories'];
                        Log::debug('Parsed format 1b (categories direct)', ['count' => count($this->prestashopCategories)]);
                    }
                }
                // Format 2: Flat array [{"id": 1, ...}, {"id": 2, ...}]
                elseif (isset($response[0]) && is_array($response[0])) {
                    $this->prestashopCategories = $response;
                    Log::debug('Parsed format 2 (flat array)', ['count' => count($this->prestashopCategories)]);
                }
                // Format 3: PrestaShop wrapper {"prestashop": {"categories": [...]}}
                elseif (isset($response['prestashop']['categories'])) {
                    $categories = $response['prestashop']['categories'];
                    if (isset($categories['category'])) {
                        $this->prestashopCategories = is_array($categories['category'][0] ?? null)
                            ? $categories['category']
                            : [$categories['category']];
                    } else {
                        $this->prestashopCategories = is_array($categories) ? $categories : [];
                    }
                    Log::debug('Parsed format 3 (prestashop wrapper)', ['count' => count($this->prestashopCategories)]);
                } else {
                    Log::warning('Unknown response format - categories not parsed', [
                        'response_keys' => array_keys($response),
                    ]);
                }
            }

            // Sort categories by level_depth then position for proper hierarchy display
            usort($this->prestashopCategories, function($a, $b) {
                $levelA = $a['level_depth'] ?? 0;
                $levelB = $b['level_depth'] ?? 0;
                if ($levelA !== $levelB) {
                    return $levelA <=> $levelB;
                }
                return ($a['position'] ?? 0) <=> ($b['position'] ?? 0);
            });

            // DEBUG: Log detailed category structure for troubleshooting indent/visibility
            $debugCategories = array_slice(
                array_map(function($cat) {
                    return sprintf(
                        'ID:%s Name:"%s" Parent:%s Level:%s',
                        $cat['id'] ?? '?',
                        substr($cat['name'] ?? '?', 0, 15),
                        $cat['id_parent'] ?? '?',
                        $cat['level_depth'] ?? '?'
                    );
                }, $this->prestashopCategories),
                0,
                20 // First 20 for log readability
            );

            Log::info('PrestaShop root categories loaded', [
                'shop_id' => $this->importShopId,
                'count' => count($this->prestashopCategories),
                'structure' => $debugCategories,
            ]);

            // AUTO-EXPAND root categories (Baza = 1, Wszystko = 2)
            // This makes them expanded by default for better UX
            $this->expandedCategories = [1, 2];

        } catch (\Exception $e) {
            Log::error('Failed to load PrestaShop categories', [
                'shop_id' => $this->importShopId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('error', message: 'Nie udało się pobrać kategorii: ' . $e->getMessage());
        }
    }

    /**
     * Toggle category expand/collapse and lazy load children
     *
     * @param int $categoryId
     */
    public function toggleCategoryExpand(int $categoryId): void
    {
        // Check if already expanded
        $key = array_search($categoryId, $this->expandedCategories);

        if ($key !== false) {
            // Already expanded - just collapse it (keep children in array, just hide in UI)
            unset($this->expandedCategories[$key]);
            $this->expandedCategories = array_values($this->expandedCategories); // Reindex
            return;
        }

        // Not expanded - check if children already loaded, if not fetch them
        try {
            // Check if children already exist in prestashopCategories
            $existingChildren = array_filter($this->prestashopCategories, function($cat) use ($categoryId) {
                return ($cat['id_parent'] ?? null) == $categoryId;
            });

            if (!empty($existingChildren)) {
                // Children already loaded - just expand (no API call!)
                $this->expandedCategories[] = $categoryId;

                Log::debug('Category expanded - children already in memory', [
                    'category_id' => $categoryId,
                    'children_count' => count($existingChildren),
                ]);

                return;
            }

            // Children not loaded yet - fetch from API
            $shop = PrestaShopShop::find($this->importShopId);
            if (!$shop) {
                return;
            }

            $client = PrestaShopClientFactory::create($shop);

            // Fetch children of this category (id_parent = categoryId)
            $response = $client->getCategories([
                'display' => 'full',
                'language' => 1,
                'filter[id_parent]' => "[{$categoryId}]", // Exact match parent ID
            ]);

            // Parse children
            $children = [];
            if (isset($response['categories']) && is_array($response['categories'])) {
                $children = $response['categories'];
            }

            Log::debug('Category children loaded from API', [
                'category_id' => $categoryId,
                'children_count' => count($children),
            ]);

            // Insert children right after parent in the array
            $parentIndex = null;
            foreach ($this->prestashopCategories as $index => $cat) {
                if ($cat['id'] == $categoryId) {
                    $parentIndex = $index;
                    break;
                }
            }

            if ($parentIndex !== null && !empty($children)) {
                // Insert children after parent
                array_splice($this->prestashopCategories, $parentIndex + 1, 0, $children);

                // Mark as expanded
                $this->expandedCategories[] = $categoryId;
            }

        } catch (\Exception $e) {
            Log::error('Failed to load category children', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Fetch category children from PrestaShop API (or return if already in cache)
     * OPTIMIZED: Used by Alpine.js for instant client-side expand/collapse
     *
     * @param int $categoryId
     * @return bool True if children were fetched/available, false on error
     */
    public function fetchCategoryChildren(int $categoryId): bool
    {
        try {
            // Check if children already exist in prestashopCategories (CACHE HIT)
            $existingChildren = array_filter($this->prestashopCategories, function($cat) use ($categoryId) {
                return ($cat['id_parent'] ?? null) == $categoryId;
            });

            if (!empty($existingChildren)) {
                // Children already loaded - instant return (no API call, no re-render!)
                Log::debug('Category children loaded FROM CACHE', [
                    'category_id' => $categoryId,
                    'children_count' => count($existingChildren),
                ]);

                // CRITICAL: Skip render for cache hits - children already in DOM
                // This provides instant expand/collapse without server roundtrip
                $this->skipRender();

                return true; // Success - children available
            }

            // Children not loaded yet - fetch from API
            $shop = PrestaShopShop::find($this->importShopId);
            if (!$shop) {
                Log::warning('fetchCategoryChildren - shop not found', [
                    'shop_id' => $this->importShopId,
                ]);
                return false;
            }

            $client = PrestaShopClientFactory::create($shop);

            // PERFORMANCE OPTIMIZATION: Fetch only required fields (100x faster!)
            // Before: 'display' => 'full' (107 KB for 6 categories!)
            // After: selective fields (1-2 KB for same data)
            $response = $client->getCategories([
                'display' => '[id,name,id_parent,level_depth,nb_products_recursive]',
                'language' => 1,
                'filter[id_parent]' => "[{$categoryId}]", // Exact match parent ID
            ]);

            // Parse children
            $children = [];
            if (isset($response['categories']) && is_array($response['categories'])) {
                $children = $response['categories'];
            }

            // DEBUG: Log response structure for selective fields
            Log::debug('fetchCategoryChildren response structure', [
                'category_id' => $categoryId,
                'response_keys' => array_keys($response ?? []),
                'has_categories_key' => isset($response['categories']),
                'categories_is_array' => isset($response['categories']) && is_array($response['categories']),
                'children_count' => count($children),
                'first_child_sample' => $children[0] ?? null,
            ]);

            Log::info('Category children loaded from PrestaShop API', [
                'category_id' => $categoryId,
                'children_count' => count($children),
                'shop_id' => $this->importShopId,
            ]);

            // Insert children right after parent in the array
            if (!empty($children)) {
                // Find parent to get level_depth
                $parentIndex = null;
                $parentLevel = 0;
                foreach ($this->prestashopCategories as $index => $cat) {
                    if ($cat['id'] == $categoryId) {
                        $parentIndex = $index;
                        $parentLevel = (int)($cat['level_depth'] ?? 0);
                        break;
                    }
                }

                // FIX INDENTATION: Calculate level_depth for children
                // PrestaShop API may not return level_depth, so we calculate it
                $childLevel = $parentLevel + 1;
                foreach ($children as &$child) {
                    if (!isset($child['level_depth']) || $child['level_depth'] == 0) {
                        $child['level_depth'] = $childLevel;
                    }
                }
                unset($child); // Break reference

                if ($parentIndex !== null) {
                    // DEBUG: Log before insertion
                    Log::debug('Inserting children into prestashopCategories', [
                        'parent_id' => $categoryId,
                        'parent_index' => $parentIndex,
                        'parent_level' => $parentLevel,
                        'child_level' => $childLevel,
                        'children_to_insert' => count($children),
                        'array_size_before' => count($this->prestashopCategories),
                    ]);

                    // Insert children after parent
                    array_splice($this->prestashopCategories, $parentIndex + 1, 0, $children);

                    // DEBUG: Log after insertion
                    Log::debug('Children inserted successfully', [
                        'array_size_after' => count($this->prestashopCategories),
                        'inserted_count' => count($children),
                    ]);
                } else {
                    Log::warning('Parent not found in prestashopCategories array', [
                        'category_id' => $categoryId,
                        'array_size' => count($this->prestashopCategories),
                    ]);
                }
            }

            // IMPORTANT: Do NOT skipRender() here!
            // New children must be added to DOM so Alpine.js can show them
            // First load needs full render to inject children into template
            // Subsequent loads use cache hit path above (with skipRender for instant response)

            return true; // Success

        } catch (\Exception $e) {
            Log::error('Failed to fetch category children', [
                'category_id' => $categoryId,
                'shop_id' => $this->importShopId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Nie udało się załadować podkategorii: ' . $e->getMessage(),
            ]);

            return false; // Error
        }
    }

    /**
     * Select category for import
     *
     * @param int $categoryId
     */
    public function selectImportCategory(int $categoryId): void
    {
        $this->importCategoryId = $categoryId;
    }

    /**
     * Import products from selected category
     */
    public function importFromCategory(): void
    {
        if (!$this->importShopId || !$this->importCategoryId) {
            $this->dispatch('error', message: 'Wybierz sklep i kategorię');
            return;
        }

        $shop = PrestaShopShop::find($this->importShopId);

        if (!$shop) {
            $this->dispatch('error', message: 'Sklep nie został znaleziony');
            return;
        }

        // 🎯 FAZA 3D: Set loading state for Category Preview Modal
        $this->isAnalyzingCategories = true;
        $this->analyzingShopName = $shop->name;

        // 🚀 CRITICAL: Create PENDING progress record BEFORE dispatch
        // This ensures progress bar appears IMMEDIATELY when user clicks "Import"
        // Wire:poll will detect it within 3s without timing issues
        $jobId = (string) \Illuminate\Support\Str::uuid();
        $progressService = app(\App\Services\JobProgressService::class);

        $progressService->createPendingJobProgress(
            $jobId,
            $shop,
            'import',
            0 // Will be updated to actual count when job starts
        );

        BulkImportProducts::dispatch($shop, 'category', [
            'category_id' => $this->importCategoryId,
            'include_subcategories' => $this->importIncludeSubcategories,
        ], $jobId); // Pass job_id to job

        $this->dispatch('success', message: 'Analizuję kategorie z PrestaShop... To może potrwać kilka sekund.');

        $this->closeImportModal();
    }

    /*
    |--------------------------------------------------------------------------
    | BULK ACTIONS METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Bulk activate selected products
     */
    public function bulkActivate(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            return;
        }

        $count = Product::whereIn('id', $this->selectedProducts)
            ->update(['is_active' => true]);

        $this->dispatch('success', message: "Aktywowano {$count} " . ($count == 1 ? 'produkt' : 'produkty'));
        $this->resetSelection();
    }

    /**
     * Bulk deactivate selected products
     */
    public function bulkDeactivate(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            return;
        }

        $count = Product::whereIn('id', $this->selectedProducts)
            ->update(['is_active' => false]);

        $this->dispatch('success', message: "Dezaktywowano {$count} " . ($count == 1 ? 'produkt' : 'produkty'));
        $this->resetSelection();
    }

    /**
     * Open bulk category assignment modal (DEPRECATED - replaced by specific operations)
     */
    public function openBulkCategoryModal(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            return;
        }

        // Redirect to new Bulk Assign Categories modal
        $this->openBulkAssignCategories();
    }

    /**
     * Open bulk delete confirmation modal
     */
    public function openBulkDeleteModal(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            return;
        }

        $this->showBulkDeleteModal = true;
    }

    /**
     * Close bulk delete modal
     */
    public function closeBulkDeleteModal(): void
    {
        $this->showBulkDeleteModal = false;
    }

    /*
    |--------------------------------------------------------------------------
    | BULK CATEGORY OPERATIONS (ETAP_07a FAZA 2)
    |--------------------------------------------------------------------------
    */

    /**
     * Open Bulk Assign Categories modal
     *
     * ETAP_07a FAZA 2.2.2.2.1: Bulk Assign Categories
     */
    public function openBulkAssignCategories(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            return;
        }

        // Reset modal state
        $this->selectedCategoriesForBulk = [];
        $this->primaryCategoryForBulk = null;

        $this->showBulkAssignCategoriesModal = true;
    }

    /**
     * Close Bulk Assign Categories modal
     */
    public function closeBulkAssignCategories(): void
    {
        $this->showBulkAssignCategoriesModal = false;
        $this->selectedCategoriesForBulk = [];
        $this->primaryCategoryForBulk = null;
    }

    /**
     * Execute Bulk Assign Categories
     *
     * Assigns selected categories to all selected products
     * Validation: Max 10 categories per product
     * Synchronous for ≤50 products, Queue for >50
     */
    public function bulkAssignCategories(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            return;
        }

        if (empty($this->selectedCategoriesForBulk)) {
            $this->dispatch('error', message: 'Wybierz co najmniej jedną kategorię');
            return;
        }

        // Validation: Max 10 categories
        if (count($this->selectedCategoriesForBulk) > 10) {
            $this->dispatch('error', message: 'Maksymalnie 10 kategorii na produkt');
            return;
        }

        try {
            $productsCount = count($this->selectedProducts);
            $categoriesCount = count($this->selectedCategoriesForBulk);

            // CRITICAL: Multi-Store Compatibility - ONLY default categories (shop_id = NULL)
            // Per-shop categories are managed in ProductForm, not bulk operations
            if ($productsCount <= 50) {
                // Synchronous processing for small batches
                DB::transaction(function () {
                    foreach ($this->selectedProducts as $productId) {
                        $product = Product::find($productId);
                        if (!$product) continue;

                        foreach ($this->selectedCategoriesForBulk as $categoryId) {
                            // Check if already assigned (avoid duplicates)
                            $exists = DB::table('product_categories')
                                ->where('product_id', $productId)
                                ->where('category_id', $categoryId)
                                ->whereNull('shop_id') // ONLY default categories
                                ->exists();

                            if (!$exists) {
                                // Determine if this should be primary
                                $isPrimary = ($categoryId == $this->primaryCategoryForBulk);

                                // If setting as primary, unset other primary flags for this product
                                if ($isPrimary) {
                                    DB::table('product_categories')
                                        ->where('product_id', $productId)
                                        ->whereNull('shop_id')
                                        ->update(['is_primary' => false]);
                                }

                                // Insert new category assignment
                                DB::table('product_categories')->insert([
                                    'product_id' => $productId,
                                    'category_id' => $categoryId,
                                    'shop_id' => null, // Default categories
                                    'is_primary' => $isPrimary,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            } elseif ($categoryId == $this->primaryCategoryForBulk) {
                                // Category already exists, but we need to set it as primary
                                DB::table('product_categories')
                                    ->where('product_id', $productId)
                                    ->whereNull('shop_id')
                                    ->update(['is_primary' => false]);

                                DB::table('product_categories')
                                    ->where('product_id', $productId)
                                    ->where('category_id', $categoryId)
                                    ->whereNull('shop_id')
                                    ->update(['is_primary' => true]);
                            }
                        }

                        // Touch product to update timestamp
                        $product->touch();
                    }
                });

                $this->dispatch('success', message: "Przypisano {$categoriesCount} kategorii do {$productsCount} produktów");

            } else {
                // Queue processing for large batches (>50 products)
                $jobId = (string) \Illuminate\Support\Str::uuid();

                // Dispatch BulkAssignCategories queue job
                \App\Jobs\Products\BulkAssignCategories::dispatch(
                    $this->selectedProducts,
                    $this->selectedCategoriesForBulk,
                    $this->primaryCategoryForBulk,
                    $jobId
                );

                $this->dispatch('info', message: "Przypisywanie {$categoriesCount} kategorii do {$productsCount} produktów rozpoczęte. Postęp zobaczysz poniżej.");

                Log::info('Bulk Assign Categories queued', [
                    'products_count' => $productsCount,
                    'categories_count' => $categoriesCount,
                    'job_id' => $jobId,
                ]);
            }

            // Reset selection and close modal
            $this->resetSelection();
            $this->closeBulkAssignCategories();

            // Refresh products list
            unset($this->products);

        } catch (\Exception $e) {
            Log::error('Bulk Assign Categories failed', [
                'products' => $this->selectedProducts,
                'categories' => $this->selectedCategoriesForBulk,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('error', message: 'Błąd podczas przypisywania kategorii: ' . $e->getMessage());
        }
    }

    /**
     * Open Bulk Remove Categories modal
     *
     * ETAP_07a FAZA 2.2.2.2.2: Bulk Remove Categories
     */
    public function openBulkRemoveCategories(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            return;
        }

        // Reset modal state
        $this->categoriesToRemove = [];

        // Get common categories across selected products
        $this->commonCategories = $this->getCommonCategories();

        if (empty($this->commonCategories)) {
            $this->dispatch('warning', message: 'Wybrane produkty nie mają wspólnych kategorii');
            return;
        }

        $this->showBulkRemoveCategoriesModal = true;
    }

    /**
     * Close Bulk Remove Categories modal
     */
    public function closeBulkRemoveCategories(): void
    {
        $this->showBulkRemoveCategoriesModal = false;
        $this->commonCategories = [];
        $this->categoriesToRemove = [];
    }

    /**
     * Execute Bulk Remove Categories
     *
     * Removes selected categories from all selected products
     * Auto-reassigns primary if removing primary category
     */
    public function bulkRemoveCategories(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            return;
        }

        if (empty($this->categoriesToRemove)) {
            $this->dispatch('error', message: 'Wybierz co najmniej jedną kategorię do usunięcia');
            return;
        }

        try {
            $productsCount = count($this->selectedProducts);
            $categoriesCount = count($this->categoriesToRemove);

            if ($productsCount <= 50) {
                // Synchronous processing
                DB::transaction(function () {
                    foreach ($this->selectedProducts as $productId) {
                        $product = Product::find($productId);
                        if (!$product) continue;

                        // Check if removing primary category
                        $removingPrimary = DB::table('product_categories')
                            ->where('product_id', $productId)
                            ->whereIn('category_id', $this->categoriesToRemove)
                            ->whereNull('shop_id')
                            ->where('is_primary', true)
                            ->exists();

                        // Remove selected categories
                        DB::table('product_categories')
                            ->where('product_id', $productId)
                            ->whereIn('category_id', $this->categoriesToRemove)
                            ->whereNull('shop_id')
                            ->delete();

                        // If we removed primary, set first remaining category as primary
                        if ($removingPrimary) {
                            $firstRemaining = DB::table('product_categories')
                                ->where('product_id', $productId)
                                ->whereNull('shop_id')
                                ->first();

                            if ($firstRemaining) {
                                DB::table('product_categories')
                                    ->where('id', $firstRemaining->id)
                                    ->update(['is_primary' => true]);
                            }
                        }

                        // Touch product to update timestamp
                        $product->touch();
                    }
                });

                $this->dispatch('success', message: "Usunięto {$categoriesCount} kategorii z {$productsCount} produktów");

            } else {
                // Queue processing for large batches
                $jobId = (string) \Illuminate\Support\Str::uuid();

                // Dispatch BulkRemoveCategories queue job
                \App\Jobs\Products\BulkRemoveCategories::dispatch(
                    $this->selectedProducts,
                    $this->categoriesToRemove,
                    $jobId
                );

                $this->dispatch('info', message: "Usuwanie {$categoriesCount} kategorii z {$productsCount} produktów rozpoczęte. Postęp zobaczysz poniżej.");

                Log::info('Bulk Remove Categories queued', [
                    'products_count' => $productsCount,
                    'categories_count' => $categoriesCount,
                    'job_id' => $jobId,
                ]);
            }

            // Reset selection and close modal
            $this->resetSelection();
            $this->closeBulkRemoveCategories();

            // Refresh products list
            unset($this->products);

        } catch (\Exception $e) {
            Log::error('Bulk Remove Categories failed', [
                'products' => $this->selectedProducts,
                'categories' => $this->categoriesToRemove,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('error', message: 'Błąd podczas usuwania kategorii: ' . $e->getMessage());
        }
    }

    /**
     * Get common categories across selected products
     *
     * Returns categories that are assigned to ALL selected products
     *
     * @return array Array of category data [id, name, is_primary_in_any]
     */
    private function getCommonCategories(): array
    {
        if (empty($this->selectedProducts)) {
            return [];
        }

        $productsCount = count($this->selectedProducts);

        // Get categories that appear in ALL selected products
        $commonCategories = DB::table('product_categories')
            ->join('categories', 'product_categories.category_id', '=', 'categories.id')
            ->whereIn('product_categories.product_id', $this->selectedProducts)
            ->whereNull('product_categories.shop_id') // Only default categories
            ->select(
                'categories.id',
                'categories.name',
                DB::raw('COUNT(DISTINCT product_categories.product_id) as product_count'),
                DB::raw('MAX(product_categories.is_primary) as is_primary_in_any')
            )
            ->groupBy('categories.id', 'categories.name')
            ->having('product_count', '=', $productsCount) // Present in ALL products
            ->get()
            ->toArray();

        return array_map(function ($cat) {
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'is_primary_in_any' => (bool) $cat->is_primary_in_any,
            ];
        }, $commonCategories);
    }

    /**
     * Open Bulk Move Categories modal
     *
     * ETAP_07a FAZA 2.2.2.2.3: Bulk Move Categories
     */
    public function openBulkMoveCategories(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            return;
        }

        // Reset modal state
        $this->fromCategoryId = null;
        $this->toCategoryId = null;
        $this->moveMode = 'replace';

        $this->showBulkMoveCategoriesModal = true;
    }

    /**
     * Close Bulk Move Categories modal
     */
    public function closeBulkMoveCategories(): void
    {
        $this->showBulkMoveCategoriesModal = false;
        $this->fromCategoryId = null;
        $this->toCategoryId = null;
        $this->moveMode = 'replace';
    }

    /**
     * Execute Bulk Move Categories
     *
     * Moves products from one category to another
     * Two modes: replace (remove FROM, add TO) or add_keep (keep both)
     */
    public function bulkMoveCategories(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            return;
        }

        if (!$this->fromCategoryId || !$this->toCategoryId) {
            $this->dispatch('error', message: 'Wybierz kategorię źródłową i docelową');
            return;
        }

        if ($this->fromCategoryId == $this->toCategoryId) {
            $this->dispatch('error', message: 'Kategoria źródłowa i docelowa muszą być różne');
            return;
        }

        try {
            $productsCount = count($this->selectedProducts);

            if ($productsCount <= 50) {
                // Synchronous processing
                DB::transaction(function () {
                    $movedCount = 0;

                    foreach ($this->selectedProducts as $productId) {
                        $product = Product::find($productId);
                        if (!$product) continue;

                        // Check if product has FROM category
                        $hasFromCategory = DB::table('product_categories')
                            ->where('product_id', $productId)
                            ->where('category_id', $this->fromCategoryId)
                            ->whereNull('shop_id')
                            ->exists();

                        if (!$hasFromCategory) {
                            // Skip products without FROM category
                            continue;
                        }

                        $wasPrimary = DB::table('product_categories')
                            ->where('product_id', $productId)
                            ->where('category_id', $this->fromCategoryId)
                            ->whereNull('shop_id')
                            ->value('is_primary');

                        if ($this->moveMode === 'replace') {
                            // REPLACE mode: Remove FROM, add TO
                            DB::table('product_categories')
                                ->where('product_id', $productId)
                                ->where('category_id', $this->fromCategoryId)
                                ->whereNull('shop_id')
                                ->delete();
                        }

                        // Add TO category (if not already exists)
                        $existsTo = DB::table('product_categories')
                            ->where('product_id', $productId)
                            ->where('category_id', $this->toCategoryId)
                            ->whereNull('shop_id')
                            ->exists();

                        if (!$existsTo) {
                            DB::table('product_categories')->insert([
                                'product_id' => $productId,
                                'category_id' => $this->toCategoryId,
                                'shop_id' => null,
                                'is_primary' => $wasPrimary, // Preserve primary status
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        } elseif ($wasPrimary) {
                            // Category exists, but we need to set it as primary
                            DB::table('product_categories')
                                ->where('product_id', $productId)
                                ->whereNull('shop_id')
                                ->update(['is_primary' => false]);

                            DB::table('product_categories')
                                ->where('product_id', $productId)
                                ->where('category_id', $this->toCategoryId)
                                ->whereNull('shop_id')
                                ->update(['is_primary' => true]);
                        }

                        $movedCount++;

                        // Touch product to update timestamp
                        $product->touch();
                    }

                    if ($movedCount === 0) {
                        $this->dispatch('warning', message: 'Żaden produkt nie posiadał kategorii źródłowej');
                    } else {
                        $modeText = $this->moveMode === 'replace' ? 'Przeniesiono' : 'Skopiowano';
                        $this->dispatch('success', message: "{$modeText} {$movedCount} produktów między kategoriami");
                    }
                });

            } else {
                // Queue processing for large batches
                $jobId = (string) \Illuminate\Support\Str::uuid();

                // Dispatch BulkMoveCategories queue job
                \App\Jobs\Products\BulkMoveCategories::dispatch(
                    $this->selectedProducts,
                    $this->fromCategoryId,
                    $this->toCategoryId,
                    $this->moveMode,
                    $jobId
                );

                $modeText = $this->moveMode === 'replace' ? 'Przenoszenie' : 'Kopiowanie';
                $this->dispatch('info', message: "{$modeText} {$productsCount} produktów między kategoriami rozpoczęte. Postęp zobaczysz poniżej.");

                Log::info('Bulk Move Categories queued', [
                    'products_count' => $productsCount,
                    'from_category' => $this->fromCategoryId,
                    'to_category' => $this->toCategoryId,
                    'mode' => $this->moveMode,
                    'job_id' => $jobId,
                ]);
            }

            // Reset selection and close modal
            $this->resetSelection();
            $this->closeBulkMoveCategories();

            // Refresh products list
            unset($this->products);

        } catch (\Exception $e) {
            Log::error('Bulk Move Categories failed', [
                'products' => $this->selectedProducts,
                'from_category' => $this->fromCategoryId,
                'to_category' => $this->toCategoryId,
                'mode' => $this->moveMode,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('error', message: 'Błąd podczas przenoszenia kategorii: ' . $e->getMessage());
        }
    }

    /**
     * Confirm and execute bulk delete
     */
    public function confirmBulkDelete(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            $this->closeBulkDeleteModal();
            return;
        }

        try {
            $count = Product::whereIn('id', $this->selectedProducts)->count();

            // FORCE DELETE - permanently remove products from database
            // Note: Product model uses SoftDeletes, so we need forceDelete() for permanent removal
            Product::whereIn('id', $this->selectedProducts)->forceDelete();

            Log::info('Bulk delete completed', [
                'count' => $count,
                'product_ids' => $this->selectedProducts,
            ]);

            $this->dispatch('success', message: "Trwale usunięto {$count} " . ($count == 1 ? 'produkt' : ($count < 5 ? 'produkty' : 'produktów')));

            $this->resetSelection();
            $this->closeBulkDeleteModal();

        } catch (\Exception $e) {
            Log::error('Bulk delete failed', [
                'error' => $e->getMessage(),
                'products' => $this->selectedProducts,
            ]);

            $this->dispatch('error', message: 'Błąd podczas usuwania produktów: ' . $e->getMessage());
        }
    }

    /**
     * Bulk export selected products to CSV
     *
     * Exports product data with SKU, name, category, status, stock, prices
     * ETAP_05a FAZA 6: Bulk operations UI
     */
    public function bulkExportCsv(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            return;
        }

        try {
            $products = Product::whereIn('id', $this->selectedProducts)
                ->with(['categories', 'priceGroups'])
                ->orderBy('sku')
                ->get();

            // Build CSV header
            $csv = "SKU;Nazwa;Kategoria główna;Status;Stan magazynowy;Cena detaliczna;Cena dealer;Utworzono;Aktualizacja\n";

            foreach ($products as $product) {
                $primaryCategory = $product->categories
                    ->where('pivot.is_primary', true)
                    ->where('pivot.shop_id', null)
                    ->first();

                $retailPrice = $product->priceGroups
                    ->where('code', 'detaliczna')
                    ->first();

                $dealerPrice = $product->priceGroups
                    ->where('code', 'dealer_standard')
                    ->first();

                $csv .= sprintf(
                    "%s;%s;%s;%s;%d;%s;%s;%s;%s\n",
                    $this->escapeCsv($product->sku),
                    $this->escapeCsv($product->name),
                    $this->escapeCsv($primaryCategory?->name ?? '-'),
                    $product->is_active ? 'Aktywny' : 'Nieaktywny',
                    $product->stock_quantity ?? 0,
                    $retailPrice ? number_format($retailPrice->pivot->price, 2, ',', '') : '-',
                    $dealerPrice ? number_format($dealerPrice->pivot->price, 2, ',', '') : '-',
                    $product->created_at->format('Y-m-d H:i'),
                    $product->updated_at->format('Y-m-d H:i')
                );
            }

            $filename = 'products_export_' . date('Y-m-d_His') . '.csv';

            // Dispatch browser download event (Livewire 3.x pattern)
            $this->dispatch('download-csv', [
                'filename' => $filename,
                'content' => $csv
            ]);

            Log::info('ProductList: Bulk export CSV completed', [
                'count' => $products->count(),
                'filename' => $filename,
            ]);

            $this->dispatch('success', message: "Wyeksportowano {$products->count()} produktów do CSV");

        } catch (\Exception $e) {
            Log::error('ProductList: Bulk export CSV failed', [
                'error' => $e->getMessage(),
                'selected_products' => $this->selectedProducts
            ]);

            $this->dispatch('error', message: 'Błąd podczas eksportu CSV: ' . $e->getMessage());
        }
    }

    /**
     * Escape CSV field values (handle quotes and separators)
     *
     * @param string $value
     * @return string
     */
    private function escapeCsv(string $value): string
    {
        // Remove any existing quotes
        $value = str_replace('"', '""', $value);

        // If value contains semicolon, comma, newline, or quotes, wrap in quotes
        if (strpos($value, ';') !== false || strpos($value, ',') !== false ||
            strpos($value, "\n") !== false || strpos($value, '"') !== false) {
            $value = '"' . $value . '"';
        }

        return $value;
    }

    /**
     * Load PrestaShop products for individual selection
     * CRITICAL: Filters by $this->importSearch (name or SKU)
     */
    public function loadPrestaShopProducts(): void
    {
        if (!$this->importShopId) {
            return;
        }

        // Check cache first
        $cacheKey = $this->importSearch;
        if (isset($this->cachedProductSearches[$cacheKey])) {
            $this->prestashopProducts = $this->cachedProductSearches[$cacheKey];

            Log::info('PrestaShop products loaded FROM CACHE', [
                'shop_id' => $this->importShopId,
                'search' => $this->importSearch,
                'count' => count($this->prestashopProducts),
                'from_cache' => true,
            ]);

            return;
        }

        try {
            $shop = PrestaShopShop::find($this->importShopId);

            // Validate shop exists
            if (!$shop) {
                $this->dispatch('error', message: 'Sklep nie został znaleziony');
                return;
            }

            // Validate shop has version configured
            if (empty($shop->version)) {
                $this->dispatch('error', message: 'Sklep nie ma ustawionej wersji PrestaShop. Skonfiguruj wersję w panelu zarządzania sklepami.');
                Log::error('PrestaShop shop missing version', [
                    'shop_id' => $shop->id,
                    'shop_name' => $shop->name,
                ]);
                return;
            }

            // CRITICAL FIX: Use static method instead of app() container injection
            $client = PrestaShopClientFactory::create($shop);

            // Request full product data
            // PrestaShop API note: For lists, display=full may return nested structure
            $params = [
                'display' => 'full',
                'language' => 1, // Add language ID for multilanguage fields
            ];

            // CRITICAL: Apply search filter if present
            // PrestaShop API doesn't support OR logic, so we make TWO requests
            $allProducts = [];

            if (!empty($this->importSearch)) {
                // PrestaShop filter syntax variations
                // Try multiple approaches since API is inconsistent

                // Approach 1: Contains filter (wildcard in middle)
                $paramsName = $params;
                $paramsName['filter[name]'] = '%[' . $this->importSearch . ']%';
                $responseByName = $client->getProducts($paramsName);

                // Approach 2: Exact reference match (SKU is usually exact)
                $paramsRef = $params;
                $paramsRef['filter[reference]'] = '[' . $this->importSearch . ']';
                $responseByReference = $client->getProducts($paramsRef);

                Log::debug('Search responses', [
                    'search' => $this->importSearch,
                    'by_name_count' => isset($responseByName['products']) ? count($responseByName['products']) : 0,
                    'by_reference_count' => isset($responseByReference['products']) ? count($responseByReference['products']) : 0,
                ]);

                // If both empty, try begins-with pattern
                if (empty($responseByName['products']) && empty($responseByReference['products'])) {
                    $paramsBegins = $params;
                    $paramsBegins['filter[name]'] = '[' . $this->importSearch . ']%';
                    $responseBegins = $client->getProducts($paramsBegins);

                    $response = [
                        'products' => $responseBegins['products'] ?? []
                    ];
                } else {
                    // Merge results (will deduplicate later)
                    $response = [
                        'products' => array_merge(
                            $responseByName['products'] ?? [],
                            $responseByReference['products'] ?? []
                        )
                    ];
                }
            } else {
                $response = $client->getProducts($params);
            }

            // Parse response - multiple possible formats (same as categories)
            $allProducts = [];

            if (is_array($response)) {
                // Format 1: Nested structure {"products": [...]}
                if (isset($response['products']) && is_array($response['products'])) {
                    // Check if products is array of product objects or nested further
                    if (isset($response['products']['product'])) {
                        // Format: {"products": {"product": [...]}}
                        $products = $response['products']['product'];
                        $allProducts = is_array($products) ? (isset($products[0]) ? $products : [$products]) : [];
                    } else {
                        // Format: {"products": [...]}
                        $allProducts = $response['products'];
                    }
                }
                // Format 2: Flat array [{"id": 1, ...}, {"id": 2, ...}]
                elseif (isset($response[0]) && is_array($response[0])) {
                    $allProducts = $response;
                }
                // Format 3: PrestaShop wrapper {"prestashop": {"products": [...]}}
                elseif (isset($response['prestashop']['products'])) {
                    $products = $response['prestashop']['products'];
                    if (isset($products['product'])) {
                        $allProducts = is_array($products['product'][0] ?? null)
                            ? $products['product']
                            : [$products['product']];
                    } else {
                        $allProducts = is_array($products) ? $products : [];
                    }
                }
            }

            // Deduplicate products by ID (in case product matches both name and SKU)
            $uniqueProducts = [];
            foreach ($allProducts as $product) {
                $productId = $product['id'] ?? null;
                if ($productId && !isset($uniqueProducts[$productId])) {
                    $uniqueProducts[$productId] = $product;
                }
            }

            $this->prestashopProducts = array_values($uniqueProducts);

            // Cache results for this search term
            $this->cachedProductSearches[$cacheKey] = $this->prestashopProducts;

            Log::info('PrestaShop products loaded', [
                'shop_id' => $this->importShopId,
                'total' => count($allProducts),
                'filtered' => count($this->prestashopProducts),
                'search' => $this->importSearch,
                'from_cache' => false,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load PrestaShop products', [
                'shop_id' => $this->importShopId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('error', message: 'Nie udało się pobrać produktów: ' . $e->getMessage());
        }
    }

    /**
     * Toggle product selection for import
     *
     * @param int $productId
     */
    public function toggleProductSelection(int $productId): void
    {
        $key = array_search($productId, $this->selectedProductsToImport);

        if ($key !== false) {
            unset($this->selectedProductsToImport[$key]);
            $this->selectedProductsToImport = array_values($this->selectedProductsToImport);
        } else {
            $this->selectedProductsToImport[] = $productId;
        }
    }

    /**
     * Import selected products
     */
    public function importSelectedProducts(): void
    {
        if (!$this->importShopId || empty($this->selectedProductsToImport)) {
            $this->dispatch('error', message: 'Wybierz sklep i przynajmniej jeden produkt');
            return;
        }

        $shop = PrestaShopShop::find($this->importShopId);

        if (!$shop) {
            $this->dispatch('error', message: 'Sklep nie został znaleziony');
            return;
        }

        // 🎯 FAZA 3D: Set loading state for Category Preview Modal
        $this->isAnalyzingCategories = true;
        $this->analyzingShopName = $shop->name;

        // 🚀 CRITICAL: Create PENDING progress record BEFORE dispatch
        $jobId = (string) \Illuminate\Support\Str::uuid();
        $progressService = app(\App\Services\JobProgressService::class);

        $progressService->createPendingJobProgress(
            $jobId,
            $shop,
            'import',
            count($this->selectedProductsToImport) // We know exact count for individual mode
        );

        BulkImportProducts::dispatch($shop, 'individual', [
            'product_ids' => $this->selectedProductsToImport,
        ], $jobId);

        $this->dispatch('success', message: sprintf('Analizuję kategorie dla %d produktów... To może potrwać kilka sekund.', count($this->selectedProductsToImport)));

        $this->closeImportModal();
    }

    /**
     * Check if product exists in PPM
     *
     * @param array $prestashopProduct
     * @return bool
     */
    private function productExistsInPPM(array $prestashopProduct): bool
    {
        $sku = $prestashopProduct['reference'] ?? null;

        if (!$sku) {
            return false;
        }

        return Product::where('sku', $sku)->exists();
    }

    /**
     * Listen for shop changes from ProductForm and refresh list
     *
     * CRITICAL: Force full component refresh to reload shop associations
     * Livewire 3.x uses $refresh magic action to re-render component with fresh data
     */
    #[On('shops-updated')]
    public function refreshAfterShopUpdate($productId = null): void
    {
        // Clear computed property cache to force fresh query
        unset($this->products);

        // Reset to first page to ensure we see the updated product
        $this->resetPage();

        // Force component re-render by touching a tracked property
        $this->perPage = $this->perPage;

        Log::info('ProductList refreshed after shop update', [
            'product_id' => $productId,
            'current_page' => $this->getPage(),
        ]);

        // Dispatch client-side refresh event
        $this->js('$wire.$refresh()');
    }

    /**
     * Refresh product list after import job completes
     *
     * Listens to 'progress-completed' event dispatched by JobProgressBar
     * when import job finishes (completed or failed)
     */
    #[On('progress-completed')]
    public function refreshAfterImport(): void
    {
        // Clear computed property cache to force fresh query
        unset($this->products);

        // Reset to first page to show newly imported products
        $this->resetPage();

        // Force component re-render
        $this->perPage = $this->perPage;

        Log::info('ProductList refreshed after import completion');

        // Dispatch client-side refresh event
        $this->js('$wire.$refresh()');
    }

    /**
     * Check for pending category previews (polling mechanism)
     *
     * ETAP_07 FAZA 3D: Category Import Preview System - Polling Method
     *
     * Called by wire:poll.3s to check if there are pending CategoryPreview records
     * This is needed because Livewire::dispatch() in Queue Jobs doesn't work
     *
     * @return void
     */
    public function checkForPendingCategoryPreviews(): void
    {
        // Only check if we have active jobs
        if (empty($this->activeJobProgress)) {
            return;
        }

        // Get job IDs from active progress tracking
        $activeJobIds = collect($this->activeJobProgress)->pluck('job_id')->filter()->toArray();

        if (empty($activeJobIds)) {
            return;
        }

        // Check for pending CategoryPreview records for these jobs
        $pendingPreviews = \App\Models\CategoryPreview::whereIn('job_id', $activeJobIds)
            ->where('status', \App\Models\CategoryPreview::STATUS_PENDING)
            ->where('expires_at', '>', now())
            ->get();

        // Show modal for each pending preview (usually just one)
        foreach ($pendingPreviews as $preview) {
            // 🔥 CRITICAL: Skip if already shown (prevents duplicate modal opens from polling)
            if (in_array($preview->id, $this->shownPreviewIds, true)) {
                Log::debug('ProductList: Skipping already shown preview', [
                    'preview_id' => $preview->id,
                ]);
                continue;
            }

            Log::info('ProductList: Pending CategoryPreview detected via polling', [
                'preview_id' => $preview->id,
                'job_id' => $preview->job_id,
                'shop_id' => $preview->shop_id,
                'total_categories' => $preview->total_categories,
            ]);

            // Dispatch event to CategoryPreviewModal component
            $this->dispatch('show-category-preview', previewId: $preview->id);

            // 🎯 FAZA 3D: Hide loading state when modal appears
            $this->isAnalyzingCategories = false;
            $this->analyzingShopName = null;

            // Show info notification
            $this->dispatch('info', message: "Analiza kategorii ukończona. Znaleziono {$preview->total_categories} brakujących kategorii.");

            // 🔥 CRITICAL: Mark as shown to prevent polling from showing modal again
            // Polling runs every 3s, tracking prevents duplicate opens
            $this->shownPreviewIds[] = $preview->id;

            Log::debug('CategoryPreview marked as shown', [
                'preview_id' => $preview->id,
                'shown_count' => count($this->shownPreviewIds),
            ]);

            break; // Show only one modal at a time
        }
    }

    /**
     * Handle category preview ready event
     *
     * ETAP_07 FAZA 3D: Category Import Preview System
     *
     * Listens to 'category-preview-ready' event dispatched by AnalyzeMissingCategories job
     * Shows CategoryPreviewModal with preview data
     *
     * @param array $data Event data with preview_id, job_id, shop_id
     */
    #[On('category-preview-ready')]
    public function handleCategoryPreviewReady(array $data): void
    {
        $previewId = $data['preview_id'] ?? null;

        if (!$previewId) {
            Log::warning('ProductList: category-preview-ready event without preview_id', [
                'event_data' => $data,
            ]);
            return;
        }

        Log::info('ProductList: CategoryPreviewReady event received', [
            'preview_id' => $previewId,
            'job_id' => $data['job_id'] ?? null,
            'shop_id' => $data['shop_id'] ?? null,
        ]);

        // Dispatch event to CategoryPreviewModal component
        $this->dispatch('show-category-preview', previewId: $previewId);

        // Show info notification
        $this->dispatch('info', message: 'Analiza kategorii ukończona. Sprawdź podgląd przed importem.');
    }

    /**
     * Render the component
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.products.listing.product-list', [
            'products' => $this->products,
            'categories' => $this->categories,
        ])->layout('layouts.admin', [
            'title' => 'Lista produktów - PPM',
            'breadcrumb' => 'Lista produktów'
        ]);
    }
}