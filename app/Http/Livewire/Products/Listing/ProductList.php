<?php

namespace App\Http\Livewire\Products\Listing;

use Livewire\Component;
use Livewire\WithPagination;
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
    public function getSelectedCountProperty(): int
    {
        return count($this->selectedProducts);
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
     * Reset selection
     */
    public function resetSelection(): void
    {
        $this->reset(['selectedProducts', 'selectAll']);
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
    public function confirmDelete(int $productId): void
    {
        $product = Product::find($productId);
        if (!$product) {
            $this->dispatch('error', message: 'Produkt nie został znaleziony');
            return;
        }

        // Check if product can be deleted
        if (!$product->canDelete()) {
            $this->dispatch('error', message: 'Nie można usunąć produktu - ma aktywne powiązania (warianty, ceny, stan magazynowy)');
            return;
        }

        $this->productToDelete = $productId;
        $this->showDeleteModal = true;
    }

    /**
     * Delete product after confirmation
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
            $product->delete();

            $this->dispatch('success', message: "Produkt {$sku} został usunięty");
            $this->cancelDelete();

        } catch (\Exception $e) {
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
                'shopData:id,product_id,shop_id,sync_status,is_published,last_sync_at'
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

        BulkImportProducts::dispatch($shop, 'all');

        $this->dispatch('success', message: 'Import wszystkich produktów rozpoczęty w tle. Otrzymasz powiadomienie po zakończeniu.');

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

        BulkImportProducts::dispatch($shop, 'category', [
            'category_id' => $this->importCategoryId,
            'include_subcategories' => $this->importIncludeSubcategories,
        ]);

        $this->dispatch('success', message: 'Import kategorii rozpoczęty w tle. Otrzymasz powiadomienie po zakończeniu.');

        $this->closeImportModal();
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

        BulkImportProducts::dispatch($shop, 'individual', [
            'product_ids' => $this->selectedProductsToImport,
        ]);

        $this->dispatch('success', message: sprintf('Import %d produktów rozpoczęty w tle.', count($this->selectedProductsToImport)));

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
            'title' => 'Zarządzanie produktami',
            'breadcrumbs' => [
                ['name' => 'Admin', 'url' => route('admin.dashboard')],
                ['name' => 'Produkty', 'url' => null],
            ]
        ]);
    }
}