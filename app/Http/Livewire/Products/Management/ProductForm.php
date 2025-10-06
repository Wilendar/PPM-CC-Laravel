<?php

namespace App\Http\Livewire\Products\Management;

use Livewire\Component;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Category;
use App\Models\PrestaShopShop;
use App\Models\ProductAttribute;
use App\Models\ProductSyncStatus;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Http\Livewire\Products\Management\Traits\ProductFormValidation;
use App\Http\Livewire\Products\Management\Traits\ProductFormUpdates;
use App\Http\Livewire\Products\Management\Traits\ProductFormComputed;
use App\Http\Livewire\Products\Management\Services\ProductMultiStoreManager;
use App\Http\Livewire\Products\Management\Services\ProductCategoryManager;
use App\Http\Livewire\Products\Management\Services\ProductFormSaver;
use App\Jobs\PrestaShop\SyncProductToPrestaShop;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ProductForm Component - Refactored
 *
 * Main product creation/editing form with multi-store support
 * Refactored per CLAUDE.md guidelines into smaller, manageable components
 *
 * Architecture:
 * - Main component: ~200 lines (within CLAUDE.md limits)
 * - Traits: Validation, Updates, Computed properties
 * - Services: MultiStore, Category, Saver management
 *
 * @package App\Http\Livewire\Products\Management
 * @version 2.0 - Refactored
 */
class ProductForm extends Component
{
    use AuthorizesRequests;
    use ProductFormValidation;
    use ProductFormUpdates;
    use ProductFormComputed;

    /*
    |--------------------------------------------------------------------------
    | COMPONENT PROPERTIES - Form State Management
    |--------------------------------------------------------------------------
    */

    // Product instance (null for create mode)
    public ?Product $product = null;
    public bool $isEditMode = false;

    // Active tab management
    public string $activeTab = 'basic';

    // === BASIC INFORMATION TAB ===
    public string $sku = '';
    public string $name = '';
    public string $slug = '';
    public ?int $product_type_id = 1; // Default to ID 1
    public string $manufacturer = '';
    public string $supplier_code = '';
    public string $ean = '';
    public bool $is_active = true;
    public bool $is_variant_master = false;
    public int $sort_order = 0;
    public bool $is_featured = false;

    // === DESCRIPTION TAB ===
    public string $short_description = '';
    public string $long_description = '';
    public string $meta_title = '';
    public string $meta_description = '';

    // === PHYSICAL PROPERTIES TAB ===
    public ?float $weight = null;
    public ?float $height = null;
    public ?float $width = null;
    public ?float $length = null;
    public float $tax_rate = 23.00;

    // === PUBLISHING SCHEDULE ===
    public ?string $available_from = null;
    public ?string $available_to = null;

    // === CATEGORIES (NEW CONTEXT-AWARE SYSTEM) ===

    // Category management service
    protected ?ProductCategoryManager $categoryManager = null;

    // Categories per context
    public array $defaultCategories = ['selected' => [], 'primary' => null]; // Default categories
    public array $shopCategories = []; // [shopId => ['selected' => [ids], 'primary' => id]]

    public array $shopAttributes = []; // [shopId => [attributeCode => value]]
    public array $exportedShops = [];   // Shops where product is exported
    public ?int $activeShopId = null;   // null = default data, int = specific shop
    public array $shopData = [];        // Per-shop data storage
    public array $defaultData = [];     // Original product data
    public bool $showShopSelector = false;
    public array $selectedShopsToAdd = [];

    // === UI STATE ===
    public bool $isSaving = false;
    public array $validationErrors = [];
    public string $successMessage = '';
    public bool $showSlugField = false;
    public int $shortDescriptionCount = 0;
    public int $longDescriptionCount = 0;

    // === PENDING CHANGES SYSTEM ===
    public array $pendingChanges = [];     // [shopId => [field => value]] or ['default' => [field => value]]
    public bool $hasUnsavedChanges = false; // Track if there are any pending changes
    public array $originalFormData = [];   // Backup of original form data for reset functionality
    public array $shopsToRemove = [];      // Shop IDs pending removal (deleted on save)
    public array $removedShopsCache = [];   // Cache of removed shop data (for undo/re-add)

    // === SERVICE INSTANCES ===
    // Services temporarily disabled for debugging

    // === PRESTASHOP CATEGORIES (ETAP_07 FAZA 2B.2) ===
    public array $prestashopCategories = []; // Cached PrestaShop categories per shop [shopId => tree]

    /*
    |--------------------------------------------------------------------------
    | COMPONENT LIFECYCLE
    |--------------------------------------------------------------------------
    */

    /**
     * Initialize component with product data
     */
    public function mount(?Product $product = null): void
    {
        try {
            Log::info('ProductForm mount() called', ['product_id' => $product?->id]);

            // Initialize category manager
            try {
                $this->categoryManager = new ProductCategoryManager($this);
                Log::info('CategoryManager initialized successfully', [
                    'categoryManager_exists' => $this->categoryManager !== null,
                    'categoryManager_type' => get_class($this->categoryManager),
                    'product_id' => $product?->id,
                ]);
            } catch (\Exception $e) {
                Log::error('CategoryManager initialization failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'product_id' => $product?->id,
                ]);
                $this->categoryManager = null;
            }

            // Initialize basic mode and product
            if ($product && $product->exists) {
                $this->product = $product;
                $this->isEditMode = true;
                $this->loadProductData();
                Log::info('Edit mode activated');
            } else {
                $this->isEditMode = false;
                $this->setDefaults();
                Log::info('Create mode activated');
            }

            // Initialize all required arrays for computed properties
            $this->defaultCategories = $this->defaultCategories ?? ['selected' => [], 'primary' => null];
            $this->shopCategories = $this->shopCategories ?? [];
            $this->shopAttributes = $this->shopAttributes ?? [];
            $this->exportedShops = $this->exportedShops ?? [];
            $this->shopData = $this->shopData ?? [];
            $this->defaultData = $this->defaultData ?? [];
            $this->selectedShopsToAdd = $this->selectedShopsToAdd ?? [];
            $this->validationErrors = $this->validationErrors ?? [];

            // Initialize pending changes system
            $this->pendingChanges = $this->pendingChanges ?? [];
            $this->hasUnsavedChanges = false;
            $this->originalFormData = $this->originalFormData ?? [];

            // Set default tab
            $this->activeTab = 'basic';
            $this->activeShopId = null;
            $this->showShopSelector = false;
            $this->showSlugField = false;
            $this->isSaving = false;
            $this->successMessage = '';

            Log::info('ProductForm mount() completed successfully', [
                'isEditMode' => $this->isEditMode,
                'sku' => $this->sku,
                'name' => $this->name
            ]);

        } catch (\Exception $e) {
            Log::error('ProductForm mount() failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'product_id' => $product?->id,
            ]);

            // Absolute minimal defaults with arrays
            $this->isEditMode = false;
            $this->activeTab = 'basic';
            $this->sku = '';
            $this->name = '';
            $this->product_type_id = 1;
            $this->defaultCategories = ['selected' => [], 'primary' => null];
            $this->shopCategories = [];
            $this->shopAttributes = [];
            $this->exportedShops = [];
            $this->shopData = [];
            $this->defaultData = [];
            $this->selectedShopsToAdd = [];
            $this->validationErrors = [];
            $this->activeShopId = null;
            $this->showShopSelector = false;

            // Initialize pending changes system for error case
            $this->pendingChanges = [];
            $this->hasUnsavedChanges = false;
            $this->originalFormData = [];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DATA INITIALIZATION
    |--------------------------------------------------------------------------
    */

    /**
     * Set default values for new product creation
     */
    private function setDefaults(): void
    {
        try {
            $this->sku = '';
            $this->name = '';
            $this->slug = '';
            $this->product_type_id = 1;
            $this->manufacturer = '';
            $this->supplier_code = '';
            $this->ean = '';
            $this->short_description = '';
            $this->long_description = '';
            $this->meta_title = '';
            $this->meta_description = '';
            $this->weight = null;
            $this->height = null;
            $this->width = null;
            $this->length = null;
            $this->tax_rate = 23.00;
            $this->is_active = true;
            $this->is_variant_master = false;
            $this->sort_order = 0;
            $this->defaultCategories = ['selected' => [], 'primary' => null];

            // Store default data
            $this->storeDefaultData();

            Log::info('Default data stored', [
                'product_id' => $this->product?->id,
                'default_data' => $this->defaultData
            ]);

        } catch (\Exception $e) {
            Log::error('setDefaults failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Minimal safe defaults
            $this->sku = '';
            $this->name = '';
            $this->isEditMode = false;
            $this->activeTab = 'basic';
        }
    }

    /**
     * Load existing product data into form
     */
    private function loadProductData(): void
    {
        // Basic Information
        $this->sku = $this->product->sku;
        $this->name = $this->product->name;
        $this->slug = $this->product->slug ?? '';
        $this->product_type_id = $this->product->product_type_id;
        $this->manufacturer = $this->product->manufacturer ?? '';
        $this->supplier_code = $this->product->supplier_code ?? '';
        $this->ean = $this->product->ean ?? '';
        $this->is_active = $this->product->is_active;
        $this->is_variant_master = $this->product->is_variant_master;
        $this->is_featured = $this->product->is_featured;
        $this->sort_order = $this->product->sort_order;

        // Descriptions
        $this->short_description = $this->product->short_description ?? '';
        $this->long_description = $this->product->long_description ?? '';
        $this->meta_title = $this->product->meta_title ?? '';
        $this->meta_description = $this->product->meta_description ?? '';

        // Physical Properties
        $this->weight = $this->product->weight;
        $this->height = $this->product->height;
        $this->width = $this->product->width;
        $this->length = $this->product->length;
        $this->tax_rate = $this->product->tax_rate;

        // Publishing Schedule
        $this->available_from = $this->product->available_from?->format('Y-m-d\TH:i');
        $this->available_to = $this->product->available_to?->format('Y-m-d\TH:i');

        // Load categories using category manager
        if ($this->categoryManager) {
            $this->categoryManager->loadCategories();
        }

        // Store default data
        $this->storeDefaultData();

        // Load shop-specific data if editing
        if ($this->isEditMode) {
            $this->loadShopData();
        }
    }

    /**
     * Store current form data as default data
     */
    private function storeDefaultData(): void
    {
        if ($this->product && $this->product->exists) {
            // Load from product (database data) - preferred when product exists
            $this->defaultData = [
                'sku' => $this->product->sku,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'product_type_id' => $this->product->product_type_id,
                'manufacturer' => $this->product->manufacturer,
                'supplier_code' => $this->product->supplier_code,
                'ean' => $this->product->ean,
                'short_description' => $this->product->short_description,
                'long_description' => $this->product->long_description,
                'meta_title' => $this->product->meta_title,
                'meta_description' => $this->product->meta_description,
                'weight' => $this->product->weight,
                'height' => $this->product->height,
                'width' => $this->product->width,
                'length' => $this->product->length,
                'tax_rate' => $this->product->tax_rate,
                'is_active' => $this->product->is_active,
                'is_variant_master' => $this->product->is_variant_master,
                'is_featured' => $this->product->is_featured,
                'sort_order' => $this->product->sort_order,
                'available_from' => $this->product->available_from?->format('Y-m-d H:i:s'),
                'available_to' => $this->product->available_to?->format('Y-m-d H:i:s'),

                // === CATEGORIES (NEW CONTEXT-AWARE SYSTEM) ===
                'defaultCategories' => $this->defaultCategories,
            ];
        } else {
            // Load from form (current form state) - fallback when no product
            $this->defaultData = [
                'sku' => $this->sku,
                'name' => $this->name,
                'slug' => $this->slug,
                'product_type_id' => $this->product_type_id,
                'manufacturer' => $this->manufacturer,
                'supplier_code' => $this->supplier_code,
                'ean' => $this->ean,
                'short_description' => $this->short_description,
                'long_description' => $this->long_description,
                'meta_title' => $this->meta_title,
                'meta_description' => $this->meta_description,
                'weight' => $this->weight,
                'height' => $this->height,
                'width' => $this->width,
                'length' => $this->length,
                'tax_rate' => $this->tax_rate,
                'is_active' => $this->is_active,
                'is_variant_master' => $this->is_variant_master,
                'is_featured' => $this->is_featured,
                'sort_order' => $this->sort_order,
                'available_from' => $this->available_from,
                'available_to' => $this->available_to,

                // === CATEGORIES (NEW CONTEXT-AWARE SYSTEM) ===
                'defaultCategories' => $this->defaultCategories,
            ];
        }
    }

    /**
     * Load shop-specific data from ProductShopData table
     * CRITICAL: Only loads existing shop data, builds exportedShops list
     */
    private function loadShopData(): void
    {
        if (!$this->product) {
            return;
        }

        // Load all shop data for this product
        $productShopData = \App\Models\ProductShopData::where('product_id', $this->product->id)
            ->with('shop')
            ->get();

        foreach ($productShopData as $shopData) {
            // Store shop data with ID for management
            $this->shopData[$shopData->shop_id] = [
                'id' => $shopData->id,

                // === BASIC INFORMATION ===
                'sku' => $shopData->sku,
                'name' => $shopData->name,
                'slug' => $shopData->slug,
                'product_type_id' => $shopData->product_type_id,
                'manufacturer' => $shopData->manufacturer,
                'supplier_code' => $shopData->supplier_code,
                'ean' => $shopData->ean,

                // === DESCRIPTIONS & SEO ===
                'short_description' => $shopData->short_description,
                'long_description' => $shopData->long_description,
                'meta_title' => $shopData->meta_title,
                'meta_description' => $shopData->meta_description,

                // === PHYSICAL PROPERTIES ===
                'weight' => $shopData->weight,
                'height' => $shopData->height,
                'width' => $shopData->width,
                'length' => $shopData->length,
                'tax_rate' => $shopData->tax_rate,

                // === STATUS & SETTINGS ===
                'is_active' => $shopData->is_active,
                'is_variant_master' => $shopData->is_variant_master,
                'is_featured' => $shopData->is_featured,
                'sort_order' => $shopData->sort_order,

                // === SYNC DATA ===
                'is_published' => $shopData->is_published,
                'sync_status' => $shopData->sync_status,
                'last_sync_at' => $shopData->last_sync_at?->format('Y-m-d H:i:s'),
            ];

            // Add to exported shops list
            $this->exportedShops[] = $shopData->shop_id;
        }

        Log::info('Shop data loaded', [
            'product_id' => $this->product->id,
            'exported_shops' => $this->exportedShops,
            'shops_count' => count($this->shopData),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Get available categories for selection
     */
    public function getCategoriesProperty()
    {
        try {
            return \App\Models\Category::with('children')
                ->orderBy('sort_order')
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to load categories', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * Get categories for template (public method)
     */
    public function getAvailableCategories()
    {
        try {
            return \App\Models\Category::orderBy('sort_order')->get();
        } catch (\Exception $e) {
            Log::error('Failed to load categories in getAvailableCategories', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UI INTERACTION METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Switch between tabs
     */
    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->dispatch('tab-switched', ['tab' => $tab]);
    }

    /**
     * Toggle product active status with confirmation
     */
    public function toggleActiveStatus(): void
    {
        $newStatus = !$this->is_active;
        $statusText = $newStatus ? 'aktywny' : 'nieaktywny';

        // Dispatch confirmation event to frontend
        $this->dispatch('confirm-status-change', [
            'message' => "Czy na pewno chcesz ustawić produkt jako {$statusText}?",
            'newStatus' => $newStatus
        ]);
    }

    /**
     * Confirm and apply status change
     */
    public function confirmStatusChange(bool $newStatus): void
    {
        $this->is_active = $newStatus;

        // If editing existing product, save immediately
        if ($this->isEditMode && $this->product) {
            $this->updateOnly();
        }

        $statusText = $newStatus ? 'aktywny' : 'nieaktywny';
        $this->successMessage = "Status produktu został zmieniony na: {$statusText}";
    }

    /**
     * Toggle product visibility for specific shop/integration
     */
    public function toggleShopVisibility(int $shopId): void
    {
        if (!$this->product) {
            $this->addError('general', 'Najpierw zapisz produkt');
            return;
        }

        // Find existing shop data or create new
        $productShopData = \App\Models\ProductShopData::firstOrNew([
            'product_id' => $this->product->id,
            'shop_id' => $shopId,
        ]);

        $newStatus = !($productShopData->is_published ?? false);

        // Update shop data with new visibility status
        $productShopData->fill([
            'is_published' => $newStatus,
            'sync_status' => 'pending', // Mark as pending sync
        ]);

        $productShopData->save();

        // Update local shop data
        if (!isset($this->shopData[$shopId])) {
            $this->shopData[$shopId] = [];
        }
        $this->shopData[$shopId]['is_published'] = $newStatus;
        $this->shopData[$shopId]['sync_status'] = 'pending';

        // Add to exported shops if not already present and now published
        if ($newStatus && !in_array($shopId, $this->exportedShops)) {
            $this->exportedShops[] = $shopId;
        }

        $shop = \App\Models\PrestaShopShop::find($shopId);
        $statusText = $newStatus ? 'opublikowany' : 'ukryty';
        $this->successMessage = "Produkt został {$statusText} w sklepie: {$shop->name}";

        Log::info('Shop visibility toggled', [
            'product_id' => $this->product->id,
            'shop_id' => $shopId,
            'is_published' => $newStatus,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Get visibility status for specific shop
     */
    public function getShopVisibility(int $shopId): bool
    {
        return $this->shopData[$shopId]['is_published'] ?? false;
    }

    /**
     * Toggle slug field visibility
     */
    public function toggleSlugField(): void
    {
        $this->showSlugField = !$this->showSlugField;
    }

    /*
    |--------------------------------------------------------------------------
    | CATEGORY MANAGEMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Get current context categories (default or shop-specific)
     */
    public function getCurrentContextCategories(): array
    {
        if ($this->activeShopId === null) {
            Log::debug('getCurrentContextCategories: returning default', [
                'defaultCategories' => $this->defaultCategories
            ]);
            return $this->defaultCategories;
        }

        if (!isset($this->shopCategories[$this->activeShopId])) {
            $this->shopCategories[$this->activeShopId] = ['selected' => [], 'primary' => null];
            Log::debug('getCurrentContextCategories: created empty shop categories', [
                'shop_id' => $this->activeShopId,
                'created' => $this->shopCategories[$this->activeShopId]
            ]);
        }

        Log::debug('getCurrentContextCategories: returning shop categories', [
            'shop_id' => $this->activeShopId,
            'shopCategories' => $this->shopCategories[$this->activeShopId],
            'all_shopCategories' => $this->shopCategories
        ]);

        return $this->shopCategories[$this->activeShopId];
    }

    /**
     * Set current context categories (default or shop-specific)
     */
    public function setCurrentContextCategories(array $categories): void
    {
        if ($this->activeShopId === null) {
            $this->defaultCategories = $categories;
        } else {
            $this->shopCategories[$this->activeShopId] = $categories;
        }

        // Force Livewire component refresh for real-time updates
        $this->dispatch('categories-updated', [
            'shop_id' => $this->activeShopId,
            'context' => $this->activeShopId === null ? 'default' : "shop_{$this->activeShopId}",
            'categories' => $categories
        ]);

        // Update UI color coding in real-time
        $this->updateCategoryColorCoding();
    }

    /**
     * Update category color coding in real-time
     */
    private function updateCategoryColorCoding(): void
    {
        // Force re-evaluation of computed properties for UI
        $currentStatus = $this->getCategoryStatus();

        // Dispatch event to frontend for real-time UI updates
        $this->dispatch('category-status-changed', [
            'status' => $currentStatus,
            'shop_id' => $this->activeShopId,
            'classes' => $this->getCategoryClasses(),
            'indicator' => $this->getCategoryStatusIndicator()
        ]);

        Log::info('Category color coding updated', [
            'shop_id' => $this->activeShopId,
            'status' => $currentStatus,
            'context' => $this->activeShopId === null ? 'default' : "shop_{$this->activeShopId}"
        ]);
    }

    /**
     * Getter for selected categories (for blade templates compatibility)
     * FIXED: Now uses context-safe getCategoriesForContext() to prevent cross-contamination
     */
    public function getSelectedCategoriesProperty(): array
    {
        $categories = $this->getCategoriesForContext($this->activeShopId);

        Log::debug('getSelectedCategoriesProperty called', [
            'active_shop_id' => $this->activeShopId,
            'context' => $this->activeShopId === null ? 'default' : "shop_{$this->activeShopId}",
            'categories' => $categories,
            'method' => 'getCategoriesForContext (CONTEXT-SAFE)',
        ]);

        return $categories;
    }

    /**
     * Getter for primary category ID (for blade templates compatibility)
     * FIXED: Now uses context-safe getPrimaryCategoryForContext() to prevent cross-contamination
     */
    public function getPrimaryCategoryIdProperty(): ?int
    {
        $primary = $this->getPrimaryCategoryForContext($this->activeShopId);

        Log::debug('getPrimaryCategoryIdProperty called', [
            'active_shop_id' => $this->activeShopId,
            'context' => $this->activeShopId === null ? 'default' : "shop_{$this->activeShopId}",
            'primary' => $primary,
            'method' => 'getPrimaryCategoryForContext (CONTEXT-SAFE)',
        ]);

        return $primary;
    }

    /**
     * CONTEXT-AWARE: Get selected categories for specific context (shop or default)
     * This method prevents cross-tab contamination in multi-store UI
     */
    public function getCategoriesForContext(?int $contextShopId = null): array
    {
        if ($contextShopId === null) {
            // Default context
            return $this->defaultCategories['selected'] ?? [];
        }

        // Shop-specific context
        return $this->shopCategories[$contextShopId]['selected'] ?? [];
    }

    /**
     * CONTEXT-AWARE: Get primary category for specific context (shop or default)
     * This method prevents cross-tab contamination in multi-store UI
     */
    public function getPrimaryCategoryForContext(?int $contextShopId = null): ?int
    {
        if ($contextShopId === null) {
            // Default context
            return $this->defaultCategories['primary'] ?? null;
        }

        // Shop-specific context
        return $this->shopCategories[$contextShopId]['primary'] ?? null;
    }

    /**
     * Toggle category selection with proper context isolation
     */
    public function toggleCategory(int $categoryId): void
    {
        $currentCategories = $this->getCurrentContextCategories();
        $selectedCategories = $currentCategories['selected'] ?? [];
        $primaryCategory = $currentCategories['primary'] ?? null;

        if (in_array($categoryId, $selectedCategories)) {
            // Remove category
            $selectedCategories = array_values(array_diff($selectedCategories, [$categoryId]));
            // Remove as primary if it was primary
            if ($primaryCategory === $categoryId) {
                $primaryCategory = null;
            }
        } else {
            // Add category
            $selectedCategories[] = $categoryId;
        }

        // Save back to context (isolated per shop/default)
        $this->setCurrentContextCategories([
            'selected' => $selectedCategories,
            'primary' => $primaryCategory,
        ]);

        // Mark form as changed to track in pending changes
        $this->markFormAsChanged();

        Log::info('Category toggled with context isolation', [
            'category_id' => $categoryId,
            'shop_id' => $this->activeShopId,
            'context' => $this->activeShopId === null ? 'default' : "shop_{$this->activeShopId}",
            'selected_categories' => $selectedCategories,
            'primary_category_id' => $primaryCategory,
            'defaultCategories_after_toggle' => $this->defaultCategories,
            'hasUnsavedChanges' => $this->hasUnsavedChanges,
        ]);
    }

    /**
     * Set primary category
     */
    public function setPrimaryCategory(int $categoryId): void
    {
        $currentCategories = $this->getCurrentContextCategories();
        $selectedCategories = $currentCategories['selected'] ?? [];

        // Ensure the category is selected first
        if (!in_array($categoryId, $selectedCategories)) {
            $selectedCategories[] = $categoryId;
        }

        // Set as primary
        $this->setCurrentContextCategories([
            'selected' => $selectedCategories,
            'primary' => $categoryId,
        ]);

        // Mark form as changed to track in pending changes
        $this->markFormAsChanged();

        Log::info('Primary category set with context isolation', [
            'category_id' => $categoryId,
            'shop_id' => $this->activeShopId,
            'context' => $this->activeShopId === null ? 'default' : "shop_{$this->activeShopId}",
            'selected_categories' => $selectedCategories,
            'primary_category_id' => $categoryId,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | MULTI-STORE MANAGEMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Add product to selected shops - FIXED to create actual database records
     */
    public function addToShops(): void
    {
        if (empty($this->selectedShopsToAdd)) {
            $this->addError('general', 'Wybierz co najmniej jeden sklep');
            return;
        }

        $addedCount = 0;

        foreach ($this->selectedShopsToAdd as $shopId) {
            // CRITICAL FIX: Normalize shopId to int (may come as string from form)
            $shopId = (int) $shopId;

            // CRITICAL FIX: If shop was marked for removal, cancel the removal (user changed their mind)
            // Use type-safe comparison with normalization
            $removalKey = false;
            foreach ($this->shopsToRemove as $idx => $removeId) {
                if ((int)$removeId === $shopId) {
                    $removalKey = $idx;
                    break;
                }
            }

            if ($removalKey !== false) {
                array_splice($this->shopsToRemove, $removalKey, 1);
                $this->shopsToRemove = array_values($this->shopsToRemove);
                Log::info('Cancelled pending shop removal (user re-added shop)', [
                    'product_id' => $this->product?->id,
                    'shop_id' => $shopId,
                ]);
            }

            // Check if shop already exported - use type-safe check
            $alreadyExported = false;
            foreach ($this->exportedShops as $exportedId) {
                if ((int)$exportedId === $shopId) {
                    $alreadyExported = true;
                    break;
                }
            }

            if (!$alreadyExported) {
                // CRITICAL FIX: If shop was recently removed, restore its data (undo removal)
                if (isset($this->removedShopsCache[$shopId])) {
                    // Restore shop data from cache (preserves DB ID and other fields)
                    $this->shopData[$shopId] = $this->removedShopsCache[$shopId];
                    unset($this->removedShopsCache[$shopId]);

                    Log::info('Restored shop data from cache (undo removal)', [
                        'product_id' => $this->product?->id,
                        'shop_id' => $shopId,
                        'shopData_id' => $this->shopData[$shopId]['id'] ?? 'null',
                    ]);
                } else {
                    // Create new pending shop data
                    // FIXED: Never create DB record immediately - mark as pending
                    // DB record will be created in save() method
                    $this->shopData[$shopId] = [
                        'id' => null, // null = pending creation (will be created on save)
                        'name' => null,
                        'slug' => null,
                        'short_description' => null,
                        'long_description' => null,
                        'meta_title' => null,
                        'meta_description' => null,
                        'is_published' => false,
                        'sync_status' => 'pending',
                    ];

                    Log::info('Created new pending shop data', [
                        'product_id' => $this->product?->id,
                        'shop_id' => $shopId,
                    ]);
                }

                $this->exportedShops[] = $shopId;
                $addedCount++;

                // Mark as unsaved changes
                $this->hasUnsavedChanges = true;
            }
        }

        if ($this->product) {
            Log::info('Shops added to product', [
                'product_id' => $this->product->id,
                'added_shops' => $this->selectedShopsToAdd,
                'added_count' => $addedCount,
            ]);
        } else {
            Log::info('Shops marked for addition to new product', [
                'added_shops' => $this->selectedShopsToAdd,
                'added_count' => $addedCount,
            ]);
        }

        $this->selectedShopsToAdd = [];
        $this->closeShopSelector();

        if ($this->product) {
            $this->successMessage = "Produkt został dodany do {$addedCount} sklepu/ów.";
        } else {
            $this->successMessage = "Wybrano {$addedCount} sklep/ów. Zostaną dodane po zapisaniu produktu.";
        }
    }

    /**
     * Remove product from specific shop - FIXED: Mark for removal, delete on save
     */
    public function removeFromShop(int $shopId): void
    {
        // CRITICAL FIX: Normalize shopId to int (may come as string from Livewire)
        $shopId = (int) $shopId;

        Log::debug('removeFromShop CALLED', [
            'shop_id' => $shopId,
            'shop_id_type' => gettype($shopId),
            'exportedShops_BEFORE' => $this->exportedShops,
            'exportedShops_types' => array_map('gettype', $this->exportedShops),
        ]);

        // Check if shop exists in exported list - USE LOOSE COMPARISON for mixed types
        $key = array_search($shopId, $this->exportedShops, false); // false = loose comparison!
        if ($key === false) {
            Log::warning('removeFromShop ABORTED - shop not found', [
                'shop_id' => $shopId,
                'exportedShops' => $this->exportedShops,
            ]);
            return; // Shop not in list
        }

        // CRITICAL FIX: Cache shop data before removal (for undo/re-add)
        if (isset($this->shopData[$shopId])) {
            $this->removedShopsCache[$shopId] = $this->shopData[$shopId];
        }

        // If shop has DB record (id !== null), mark for removal on save
        if (isset($this->shopData[$shopId]['id']) && $this->shopData[$shopId]['id'] !== null) {
            $this->shopsToRemove[] = $shopId;
            Log::info('Shop marked for DB deletion on save', [
                'product_id' => $this->product?->id,
                'shop_id' => $shopId,
                'shopData_id' => $this->shopData[$shopId]['id'],
            ]);
        } else {
            // Pending shop (id=null) - just remove from state, no DB operation needed
            Log::info('Pending shop removed from state', [
                'product_id' => $this->product?->id,
                'shop_id' => $shopId,
            ]);
        }

        // CRITICAL FIX: Remove using array_filter with type-safe comparison
        // This handles mixed int/string shopIds correctly
        $this->exportedShops = array_values(
            array_filter($this->exportedShops, function($id) use ($shopId) {
                return (int)$id !== $shopId; // Normalize both to int for comparison
            })
        );

        // Remove from related arrays
        unset($this->shopData[$shopId]);
        unset($this->shopCategories[$shopId]);
        unset($this->shopAttributes[$shopId]);

        // Switch back to default if current shop was removed
        if ($this->activeShopId === $shopId) {
            $this->activeShopId = null;
        }

        // Mark as unsaved changes
        $this->hasUnsavedChanges = true;

        Log::debug('removeFromShop COMPLETED', [
            'shop_id' => $shopId,
            'exportedShops_AFTER' => $this->exportedShops,
            'shopsToRemove_AFTER' => $this->shopsToRemove,
        ]);

        $this->successMessage = "Sklep zostanie usunięty po zapisaniu zmian.";
    }

    /**
     * Open shop selector modal
     */
    public function openShopSelector(): void
    {
        $this->showShopSelector = true;
        $this->selectedShopsToAdd = [];
    }

    /**
     * Close shop selector modal
     */
    public function closeShopSelector(): void
    {
        $this->showShopSelector = false;
        $this->selectedShopsToAdd = [];
    }

    /**
     * Switch between shops or default data - Enhanced with pending changes system
     * CRITICAL: Now preserves user changes between tab switches until save/reset
     */
    public function switchToShop(?int $shopId = null): void
    {
        try {
            // Save current form state to pending changes BEFORE switching
            $this->savePendingChanges();

            // Switch active shop context
            $this->activeShopId = $shopId;

            // Check if target context has pending changes first
            if ($this->hasPendingChangesForCurrent()) {
                // Load pending changes for this context
                $this->loadPendingChanges();
            } else {
                // No pending changes - load from database/stored values
                if ($shopId === null) {
                    // Switch to default data
                    $this->loadDefaultDataToForm();
                } else {
                    // Switch to shop-specific data with inheritance
                    $this->loadShopDataToForm($shopId);
                }
            }

            $this->updateCharacterCounts();

            Log::info('Switched to shop tab with pending changes support', [
                'product_id' => $this->product?->id,
                'shop_id' => $shopId,
                'active_shop_id' => $this->activeShopId,
                'has_pending_changes' => $this->hasPendingChangesForCurrent(),
                'total_pending_contexts' => count($this->pendingChanges),
            ]);

        } catch (\Exception $e) {
            Log::error('Error switching to shop tab', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'shop_id' => $shopId,
                'active_shop_id' => $this->activeShopId,
            ]);

            $this->dispatch('error', message: 'Błąd podczas przełączania zakładki');
        }
    }

    /**
     * Load default data to form fields (when activeShopId is null)
     */
    private function loadDefaultDataToForm(): void
    {
        if (!empty($this->defaultData)) {
            // === BASIC INFORMATION ===
            $this->sku = $this->defaultData['sku'] ?? $this->sku;
            $this->name = $this->defaultData['name'] ?? $this->name;
            $this->slug = $this->defaultData['slug'] ?? $this->slug;
            $this->product_type_id = $this->defaultData['product_type_id'] ?? $this->product_type_id;
            $this->manufacturer = $this->defaultData['manufacturer'] ?? $this->manufacturer;
            $this->supplier_code = $this->defaultData['supplier_code'] ?? $this->supplier_code;
            $this->ean = $this->defaultData['ean'] ?? $this->ean;

            // === DESCRIPTIONS & SEO ===
            $this->short_description = $this->defaultData['short_description'] ?? $this->short_description;
            $this->long_description = $this->defaultData['long_description'] ?? $this->long_description;
            $this->meta_title = $this->defaultData['meta_title'] ?? $this->meta_title;
            $this->meta_description = $this->defaultData['meta_description'] ?? $this->meta_description;

            // === PHYSICAL PROPERTIES ===
            $this->weight = $this->defaultData['weight'] ?? $this->weight;
            $this->height = $this->defaultData['height'] ?? $this->height;
            $this->width = $this->defaultData['width'] ?? $this->width;
            $this->length = $this->defaultData['length'] ?? $this->length;
            $this->tax_rate = $this->defaultData['tax_rate'] ?? $this->tax_rate;

            // === STATUS & SETTINGS ===
            $this->is_active = $this->defaultData['is_active'] ?? $this->is_active;
            $this->is_variant_master = $this->defaultData['is_variant_master'] ?? $this->is_variant_master;
            $this->is_featured = $this->defaultData['is_featured'] ?? $this->is_featured;
            $this->sort_order = $this->defaultData['sort_order'] ?? $this->sort_order;

            // === CATEGORIES (NEW CONTEXT-AWARE SYSTEM) ===
            $this->defaultCategories = $this->defaultData['defaultCategories'] ?? $this->defaultCategories;

            // Force update of computed properties for UI reactivity
            $this->updateCategoryColorCoding();
        } elseif ($this->product) {
            // Fallback: load from product if defaultData is not available
            $this->loadProductData();
        }
    }

    /**
     * Load shop data to form fields with inheritance from defaults
     */
    private function loadShopDataToForm(int $shopId): void
    {
        // === BASIC INFORMATION ===
        $this->sku = $this->getShopValue($shopId, 'sku') ?? $this->sku;
        $this->name = $this->getShopValue($shopId, 'name') ?? $this->name;
        $this->slug = $this->getShopValue($shopId, 'slug') ?? $this->slug;
        $this->product_type_id = $this->getShopValue($shopId, 'product_type_id') ?: $this->product_type_id;
        $this->manufacturer = $this->getShopValue($shopId, 'manufacturer') ?? $this->manufacturer;
        $this->supplier_code = $this->getShopValue($shopId, 'supplier_code') ?? $this->supplier_code;
        $this->ean = $this->getShopValue($shopId, 'ean') ?? $this->ean;

        // === DESCRIPTIONS & SEO ===
        $this->short_description = $this->getShopValue($shopId, 'short_description') ?? $this->short_description;
        $this->long_description = $this->getShopValue($shopId, 'long_description') ?? $this->long_description;
        $this->meta_title = $this->getShopValue($shopId, 'meta_title') ?? $this->meta_title;
        $this->meta_description = $this->getShopValue($shopId, 'meta_description') ?? $this->meta_description;

        // === PHYSICAL PROPERTIES ===
        $this->weight = $this->getShopValue($shopId, 'weight') ?: $this->weight;
        $this->height = $this->getShopValue($shopId, 'height') ?: $this->height;
        $this->width = $this->getShopValue($shopId, 'width') ?: $this->width;
        $this->length = $this->getShopValue($shopId, 'length') ?: $this->length;
        $this->tax_rate = $this->getShopValue($shopId, 'tax_rate') ?: $this->tax_rate;

        // === STATUS & SETTINGS ===
        $this->is_active = $this->getShopValue($shopId, 'is_active') ?? $this->is_active;
        $this->is_variant_master = $this->getShopValue($shopId, 'is_variant_master') ?? $this->is_variant_master;
        $this->is_featured = $this->getShopValue($shopId, 'is_featured') ?? $this->is_featured;
        $this->sort_order = $this->getShopValue($shopId, 'sort_order') ?: $this->sort_order;

        // === CATEGORIES ===
        $this->loadShopCategories($shopId);

        // Force update of computed properties for UI reactivity
        $this->updateCategoryColorCoding();
    }

    /**
     * Load shop-specific categories from database
     */
    private function loadShopCategories(int $shopId): void
    {
        if (!$this->product || !$this->product->exists) {
            // No product - inherit from default
            if (!isset($this->shopCategories[$shopId])) {
                $this->shopCategories[$shopId] = ['selected' => [], 'primary' => null];
            }
            return;
        }

        // Load categories from database for this shop
        $shopCategories = \App\Models\ProductShopCategory::getCategoriesForProductShop(
            $this->product->id,
            $shopId
        );

        $primaryCategory = \App\Models\ProductShopCategory::getPrimaryCategoryForProductShop(
            $this->product->id,
            $shopId
        );

        if (!empty($shopCategories)) {
            // Shop has specific categories - store in per-shop structure
            $this->shopCategories[$shopId] = [
                'selected' => $shopCategories,
                'primary' => $primaryCategory
            ];
        } else {
            // No shop-specific categories - inherit from default
            $this->shopCategories[$shopId] = [
                'selected' => $this->defaultCategories['selected'] ?? [],
                'primary' => $this->defaultCategories['primary'] ?? null
            ];
        }

        Log::info('Shop categories loaded from database', [
            'product_id' => $this->product->id,
            'shop_id' => $shopId,
            'categories_count' => count($this->shopCategories[$shopId]['selected'] ?? []),
            'primary_category' => $this->shopCategories[$shopId]['primary'] ?? null,
            'source' => empty($shopCategories) ? 'inherited_from_default' : 'shop_specific'
        ]);

        // Force UI update for loaded categories if this is the current active shop
        if ($this->activeShopId === $shopId) {
            $this->updateCategoryColorCoding();
        }
    }

    /**
     * Get value for shop with inheritance from default data
     * Returns shop-specific value if exists, otherwise returns default value
     * ENHANCED: Supports all data types (string, int, float, bool, null)
     */
    private function getShopValue(int $shopId, string $field): mixed
    {
        // If shop has custom value set (even if null/false/0), return it
        if (isset($this->shopData[$shopId][$field]) && $this->shopData[$shopId][$field] !== null) {
            return $this->shopData[$shopId][$field];
        }

        // Otherwise return default value
        return $this->defaultData[$field] ?? null;
    }

    /**
     * Save current form data as default data
     */
    private function saveCurrentDefaultData(): void
    {
        // Update defaultData with current form values - ALL FIELDS
        $this->defaultData = [
            // === BASIC INFORMATION ===
            'sku' => $this->sku,
            'name' => $this->name,
            'slug' => $this->slug,
            'product_type_id' => $this->product_type_id,
            'manufacturer' => $this->manufacturer,
            'supplier_code' => $this->supplier_code,
            'ean' => $this->ean,

            // === DESCRIPTIONS & SEO ===
            'short_description' => $this->short_description,
            'long_description' => $this->long_description,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,

            // === PHYSICAL PROPERTIES ===
            'weight' => $this->weight,
            'height' => $this->height,
            'width' => $this->width,
            'length' => $this->length,
            'tax_rate' => $this->tax_rate,

            // === STATUS & SETTINGS ===
            'is_active' => $this->is_active,
            'is_variant_master' => $this->is_variant_master,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,
        ];
    }

    /**
     * Save current form data to shop data storage
     * CRITICAL: Only saves custom values that differ from defaults
     */
    private function saveCurrentShopData(): void
    {
        if (!$this->activeShopId) {
            return;
        }

        if (!isset($this->shopData[$this->activeShopId])) {
            $this->shopData[$this->activeShopId] = [];
        }

        $customData = [];

        // Only save name if it differs from default
        if ($this->name !== ($this->defaultData['name'] ?? '')) {
            $customData['name'] = $this->name;
        }

        // Only save slug if it differs from default
        if ($this->slug !== ($this->defaultData['slug'] ?? '')) {
            $customData['slug'] = $this->slug;
        }

        // Only save short_description if it differs from default
        if ($this->short_description !== ($this->defaultData['short_description'] ?? '')) {
            $customData['short_description'] = $this->short_description;
        }

        // Only save long_description if it differs from default
        if ($this->long_description !== ($this->defaultData['long_description'] ?? '')) {
            $customData['long_description'] = $this->long_description;
        }

        // Only save meta_title if it differs from default
        if ($this->meta_title !== ($this->defaultData['meta_title'] ?? '')) {
            $customData['meta_title'] = $this->meta_title;
        }

        // Only save meta_description if it differs from default
        if ($this->meta_description !== ($this->defaultData['meta_description'] ?? '')) {
            $customData['meta_description'] = $this->meta_description;
        }

        // Merge with existing shop data (preserve ID and metadata)
        $this->shopData[$this->activeShopId] = array_merge(
            $this->shopData[$this->activeShopId] ?? [],
            $customData
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PENDING CHANGES SYSTEM - Temporary Memory Between Tabs
    |--------------------------------------------------------------------------
    */

    /**
     * Save current form state to pending changes
     */
    private function savePendingChanges(): void
    {
        $currentKey = $this->activeShopId ?? 'default';

        $this->pendingChanges[$currentKey] = [
            // === BASIC INFORMATION ===
            'sku' => $this->sku,
            'name' => $this->name,
            'slug' => $this->slug,
            'product_type_id' => $this->product_type_id,
            'manufacturer' => $this->manufacturer,
            'supplier_code' => $this->supplier_code,
            'ean' => $this->ean,

            // === DESCRIPTIONS & SEO ===
            'short_description' => $this->short_description,
            'long_description' => $this->long_description,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,

            // === PHYSICAL PROPERTIES ===
            'weight' => $this->weight,
            'height' => $this->height,
            'width' => $this->width,
            'length' => $this->length,
            'tax_rate' => $this->tax_rate,

            // === STATUS & SETTINGS ===
            'is_active' => $this->is_active,
            'is_variant_master' => $this->is_variant_master,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,

            // === PUBLISHING SCHEDULE ===
            'available_from' => $this->available_from,
            'available_to' => $this->available_to,

            // === CATEGORIES (CONTEXT-ISOLATED SYSTEM) ===
            // Save only current context categories to prevent cross-contamination
            'contextCategories' => $this->activeShopId === null
                ? $this->defaultCategories  // Save default categories only for default context
                : ($this->shopCategories[$this->activeShopId] ?? ['selected' => [], 'primary' => null]), // Save only current shop categories

            // Keep full state for save compatibility (but don't use for loading)
            '_fullDefaultCategories' => $this->defaultCategories,
            '_fullShopCategories' => $this->shopCategories,
        ];

        Log::info('Pending changes saved', [
            'key' => $currentKey,
            'changes_count' => count($this->pendingChanges[$currentKey]),
            'context_categories' => $this->pendingChanges[$currentKey]['contextCategories'] ?? 'NOT_SET',
            'saving_categories_for' => $this->activeShopId === null ? 'defaultCategories' : "shopCategories[{$this->activeShopId}]",
            'current_activeShopId' => $this->activeShopId,
        ]);
    }

    /**
     * Load pending changes to form fields
     */
    private function loadPendingChanges(): void
    {
        $currentKey = $this->activeShopId ?? 'default';

        if (!isset($this->pendingChanges[$currentKey])) {
            return;
        }

        $changes = $this->pendingChanges[$currentKey];

        // === BASIC INFORMATION ===
        $this->sku = $changes['sku'] ?? $this->sku;
        $this->name = $changes['name'] ?? $this->name;
        $this->slug = $changes['slug'] ?? $this->slug;
        $this->product_type_id = $changes['product_type_id'] ?? $this->product_type_id;
        $this->manufacturer = $changes['manufacturer'] ?? $this->manufacturer;
        $this->supplier_code = $changes['supplier_code'] ?? $this->supplier_code;
        $this->ean = $changes['ean'] ?? $this->ean;

        // === DESCRIPTIONS & SEO ===
        $this->short_description = $changes['short_description'] ?? $this->short_description;
        $this->long_description = $changes['long_description'] ?? $this->long_description;
        $this->meta_title = $changes['meta_title'] ?? $this->meta_title;
        $this->meta_description = $changes['meta_description'] ?? $this->meta_description;

        // === PHYSICAL PROPERTIES ===
        $this->weight = $changes['weight'] ?? $this->weight;
        $this->height = $changes['height'] ?? $this->height;
        $this->width = $changes['width'] ?? $this->width;
        $this->length = $changes['length'] ?? $this->length;
        $this->tax_rate = $changes['tax_rate'] ?? $this->tax_rate;

        // === STATUS & SETTINGS ===
        $this->is_active = $changes['is_active'] ?? $this->is_active;
        $this->is_variant_master = $changes['is_variant_master'] ?? $this->is_variant_master;
        $this->is_featured = $changes['is_featured'] ?? $this->is_featured;
        $this->sort_order = $changes['sort_order'] ?? $this->sort_order;

        // === PUBLISHING SCHEDULE ===
        $this->available_from = $changes['available_from'] ?? $this->available_from;
        $this->available_to = $changes['available_to'] ?? $this->available_to;

        // === CATEGORIES (CONTEXT-ISOLATED SYSTEM) ===
        // Load only current context categories to prevent cross-contamination
        if (isset($changes['contextCategories'])) {
            if ($this->activeShopId === null) {
                // Loading into default context
                $this->defaultCategories = $changes['contextCategories'];
            } else {
                // Loading into specific shop context
                $this->shopCategories[$this->activeShopId] = $changes['contextCategories'];
            }
        }

        Log::info('Pending changes loaded', [
            'key' => $currentKey,
            'changes_count' => count($changes),
            'context_categories_loaded' => isset($changes['contextCategories']) ? 'YES' : 'NO',
            'loaded_categories_for' => $this->activeShopId === null ? 'defaultCategories' : "shopCategories[{$this->activeShopId}]",
        ]);
    }

    /**
     * Check if there are any pending changes for current context
     */
    private function hasPendingChangesForCurrent(): bool
    {
        $currentKey = $this->activeShopId ?? 'default';
        return isset($this->pendingChanges[$currentKey]) && !empty($this->pendingChanges[$currentKey]);
    }

    /**
     * Mark that form has unsaved changes and track field changes
     */
    public function markFormAsChanged(): void
    {
        $this->hasUnsavedChanges = true;

        // Update pending changes automatically when any field changes
        $this->savePendingChanges();
    }

    /**
     * Reset form to original database values (discard pending changes)
     */
    public function resetToDefaults(): void
    {
        try {
            // Clear pending changes for current context
            $currentKey = $this->activeShopId ?? 'default';
            if (isset($this->pendingChanges[$currentKey])) {
                unset($this->pendingChanges[$currentKey]);
            }

            // Reload data from database/stored values
            if ($this->activeShopId === null) {
                $this->loadDefaultDataToForm();
            } else {
                $this->loadShopDataToForm($this->activeShopId);
            }

            // Check if there are still any pending changes
            $this->hasUnsavedChanges = !empty($this->pendingChanges);

            $this->updateCharacterCounts();

            $this->dispatch('success', message: 'Formularz został przywrócony do wartości z bazy danych');

            Log::info('Form reset to defaults', [
                'key' => $currentKey,
                'remaining_changes' => count($this->pendingChanges)
            ]);

        } catch (\Exception $e) {
            Log::error('Error resetting form to defaults', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->dispatch('error', message: 'Błąd podczas przywracania wartości domyślnych');
        }
    }

    /**
     * Update character counts for descriptions
     */
    private function updateCharacterCounts(): void
    {
        $this->shortDescriptionCount = strlen($this->short_description ?? '');
        $this->longDescriptionCount = strlen($this->long_description ?? '');
    }

    /**
     * Get field status for visual indication (3-level system) - REACTIVE VERSION
     *
     * ENHANCED: Now uses current form values for real-time color coding
     *
     * @param string $field
     * @return string 'default'|'inherited'|'same'|'different'
     */
    public function getFieldStatus(string $field): string
    {
        // If we're in default mode, it's always default
        if ($this->activeShopId === null) {
            return 'default';
        }

        // Get current form value (what user is typing) - REACTIVE!
        $currentValue = $this->getCurrentFieldValue($field);

        // Special handling for category fields
        if ($field === 'categories') {
            $defaultValue = $this->defaultCategories['selected'] ?? [];
        } elseif ($field === 'primary_category') {
            $defaultValue = $this->defaultCategories['primary'] ?? null;
        } else {
            $defaultValue = $this->defaultData[$field] ?? '';
        }

        // Convert to strings for comparison (handle nulls, numbers, booleans, arrays)
        $currentValueStr = $this->normalizeValueForComparison($currentValue);
        $defaultValueStr = $this->normalizeValueForComparison($defaultValue);

        // If current value is empty/null, it's inherited from defaults
        if ($currentValue === null || $currentValue === '' || $currentValue === 0 || (is_array($currentValue) && empty($currentValue))) {
            return 'inherited';
        }

        // If current value equals default value, they're the same
        if ($currentValueStr === $defaultValueStr) {
            return 'same';
        }

        // Otherwise they're different
        return 'different';
    }

    /**
     * Get current field value from form properties (reactive)
     */
    private function getCurrentFieldValue(string $field): mixed
    {
        return match ($field) {
            'sku' => $this->sku,
            'name' => $this->name,
            'slug' => $this->slug,
            'product_type_id' => $this->product_type_id,
            'manufacturer' => $this->manufacturer,
            'supplier_code' => $this->supplier_code,
            'ean' => $this->ean,
            'short_description' => $this->short_description,
            'long_description' => $this->long_description,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'weight' => $this->weight,
            'height' => $this->height,
            'width' => $this->width,
            'length' => $this->length,
            'tax_rate' => $this->tax_rate,
            'is_active' => $this->is_active,
            'is_variant_master' => $this->is_variant_master,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,
            'categories' => $this->getCategoriesForContext($this->activeShopId),
            'primary_category' => $this->getPrimaryCategoryForContext($this->activeShopId),
            default => null,
        };
    }

    /**
     * Normalize values for comparison (handle different data types)
     */
    private function normalizeValueForComparison(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_array($value)) {
            // For categories: sort array and create string representation
            if (empty($value)) {
                return '';
            }
            sort($value);
            return implode(',', $value);
        }
        if (is_numeric($value)) {
            return (string) $value;
        }
        return (string) $value;
    }

    /**
     * Check if field value is inherited from default data (for backward compatibility)
     */
    public function isFieldInherited(string $field): bool
    {
        return $this->getFieldStatus($field) === 'inherited';
    }

    /**
     * Get category selection status compared to defaults
     */
    public function getCategoryStatus(): string
    {
        // If we're in default mode, it's always default
        if ($this->activeShopId === null) {
            return 'default';
        }

        // Prefer REAL-TIME in-memory data for live color-coding
        $hasContextData = isset($this->shopCategories[$this->activeShopId]);
        $hasPendingForCurrent = $this->hasPendingChangesForCurrent();

        if ($hasContextData || $hasPendingForCurrent) {
            $currentShopCategories = $this->getCategoriesForContext($this->activeShopId);
            $defaultCategories = $this->getCategoriesForContext(null); // null = default context

            // Sort arrays for proper comparison
            sort($currentShopCategories);
            sort($defaultCategories);

            // Empty in shop context => dziedziczenie
            if (empty($currentShopCategories)) {
                return 'inherited';
            }

            return ($currentShopCategories === $defaultCategories) ? 'same' : 'different';
        }

        // Fallback: when no in-memory data available (e.g., first load), consult DB
        if ($this->product && $this->product->exists) {
            try {
                return \App\Models\ProductShopCategory::getCategoryInheritanceStatus(
                    $this->product->id,
                    $this->activeShopId
                );
            } catch (\Throwable $e) {
                // Defensive fallback to comparison logic
            }
        }

        // Final fallback: compare arrays (handles create mode gracefully)
        $currentShopCategories = $this->getCategoriesForContext($this->activeShopId);
        $defaultCategories = $this->getCategoriesForContext(null);
        sort($currentShopCategories);
        sort($defaultCategories);
        if (empty($currentShopCategories)) {
            return 'inherited';
        }
        return ($currentShopCategories === $defaultCategories) ? 'same' : 'different';
    }

    /**
     * Get primary category status compared to defaults
     */
    public function getPrimaryCategoryStatus(): string
    {
        // If we're in default mode, it's always default
        if ($this->activeShopId === null) {
            return 'default';
        }

        // Use CONTEXT-SAFE methods to prevent cross-contamination
        $currentPrimary = $this->getPrimaryCategoryForContext($this->activeShopId);
        $defaultPrimary = $this->getPrimaryCategoryForContext(null); // null = default context

        if ($currentPrimary === $defaultPrimary) {
            return 'same';
        }

        // Check if current is null (inheriting from default)
        if ($currentPrimary === null) {
            return 'inherited';
        }

        return 'different';
    }

    /**
     * Get category status indicator for UI
     */
    public function getCategoryStatusIndicator(): array
    {
        $status = $this->getCategoryStatus();
        switch ($status) {
            case 'default':
                return [
                    'show' => false,
                    'text' => '',
                    'class' => ''
                ];
            case 'inherited':
                return [
                    'show' => true,
                    'text' => '(dziedziczone)',
                    'class' => 'text-purple-600 dark:text-purple-400 text-xs italic'
                ];
            case 'same':
                return [
                    'show' => true,
                    'text' => '(takie same jak domyślne)',
                    'class' => 'text-green-600 dark:text-green-400 text-xs'
                ];
            case 'different':
                return [
                    'show' => true,
                    'text' => '(unikalne dla tego sklepu)',
                    'class' => 'text-orange-600 dark:text-orange-400 text-xs font-medium'
                ];
            default:
                return [
                    'show' => false,
                    'text' => '',
                    'class' => ''
                ];
        }
    }

    /**
     * Get CSS classes for categories section based on status
     */
    public function getCategoryClasses(): string
    {
        $status = $this->getCategoryStatus();
        $baseClasses = 'p-4 border rounded-lg transition-all duration-200';

        switch ($status) {
            case 'default':
                return $baseClasses . ' border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800';
            case 'inherited':
                return $baseClasses . ' border-purple-300 dark:border-purple-500 bg-purple-50 dark:bg-purple-900/20';
            case 'same':
                return $baseClasses . ' border-green-300 dark:border-green-500 bg-green-50 dark:bg-green-900/20';
            case 'different':
                return $baseClasses . ' border-orange-300 dark:border-orange-500 bg-orange-50 dark:bg-orange-900/20';
            default:
                return $baseClasses . ' border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800';
        }
    }

    /**
     * Get CSS classes for field based on 3-level status system
     */
    public function getFieldClasses(string $field): string
    {
        $baseClasses = 'block w-full rounded-md shadow-sm focus:ring-orange-500 sm:text-sm transition-all duration-200';

        $status = $this->getFieldStatus($field);

        switch ($status) {
            case 'default':
                // Normal mode - standard styling
                return $baseClasses . ' border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:border-orange-500';

            case 'inherited':
                // Inherited - purple/blue tint, italic
                return $baseClasses . ' border-purple-300 dark:border-purple-500 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 italic focus:border-purple-500';

            case 'same':
                // Same as default - green tint
                return $baseClasses . ' border-green-300 dark:border-green-500 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 focus:border-green-500';

            case 'different':
                // Different from default - orange tint, bold
                return $baseClasses . ' border-orange-300 dark:border-orange-500 bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300 font-medium focus:border-orange-500';

            default:
                return $baseClasses . ' border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:border-orange-500';
        }
    }

    /**
     * Get status indicator for field (visual badge)
     */
    public function getFieldStatusIndicator(string $field): array
    {
        $status = $this->getFieldStatus($field);

        switch ($status) {
            case 'default':
                return [
                    'show' => false,
                    'text' => '',
                    'class' => ''
                ];

            case 'inherited':
                return [
                    'show' => true,
                    'text' => 'Dziedziczone',
                    'class' => 'bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100'
                ];

            case 'same':
                return [
                    'show' => true,
                    'text' => 'Zgodne',
                    'class' => 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
                ];

            case 'different':
                return [
                    'show' => true,
                    'text' => 'Własne',
                    'class' => 'bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-100'
                ];

            default:
                return [
                    'show' => false,
                    'text' => '',
                    'class' => ''
                ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DESCRIPTION TRACKING
    |--------------------------------------------------------------------------
    */

    /**
     * Update short description character count
     */
    public function updatedShortDescription(): void
    {
        $this->shortDescriptionCount = mb_strlen($this->short_description);
        $this->markFormAsChanged();
    }

    /**
     * Update long description character count
     */
    public function updatedLongDescription(): void
    {
        $this->longDescriptionCount = mb_strlen($this->long_description);
        $this->markFormAsChanged();
    }

    /**
     * Universal handler for all other form field changes
     * Automatically marks form as changed and saves to pending changes
     */
    public function updated($propertyName): void
    {
        // Skip internal properties and already handled fields
        $skipProperties = [
            'shortDescriptionCount',
            'longDescriptionCount',
            'validationErrors',
            'successMessage',
            'isSaving',
            'showShopSelector',
            'showSlugField',
            'activeTab',
            'selectedShopsToAdd',
            'pendingChanges',
            'hasUnsavedChanges',
            'originalFormData'
        ];

        if (!in_array($propertyName, $skipProperties)) {
            $this->markFormAsChanged();

            Log::info('Form field updated', [
                'property' => $propertyName,
                'value' => $this->$propertyName ?? 'null',
                'shop_id' => $this->activeShopId,
                'has_pending_changes' => $this->hasUnsavedChanges,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE OPERATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Save product (create or update)
     */
    public function save(): void
    {
        $this->saveAndClose();
    }

    /**
     * Update product without closing form - FIXED MULTI-STORE LOGIC
     */
    public function updateOnly(): void
    {
        $this->isSaving = true;
        $this->successMessage = '';

        try {
            // Basic validation
            $this->validate([
                'sku' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'product_type_id' => 'required|integer|exists:product_types,id',
                'tax_rate' => 'required|numeric|min:0|max:100',
            ]);

            // CRITICAL FIX: Different logic for default vs shop mode
            if ($this->activeShopId === null) {
                // DEFAULT MODE: Save to products table
                if ($this->isEditMode && $this->product) {
                    // Update existing product
                    $this->product->update([
                        'sku' => $this->sku,
                        'name' => $this->name,
                        'slug' => $this->slug ?: Str::slug($this->name),
                        'product_type_id' => $this->product_type_id,
                        'manufacturer' => $this->manufacturer,
                        'supplier_code' => $this->supplier_code,
                        'ean' => $this->ean,
                        'short_description' => $this->short_description,
                        'long_description' => $this->long_description,
                        'meta_title' => $this->meta_title,
                        'meta_description' => $this->meta_description,
                        'weight' => $this->weight,
                        'height' => $this->height,
                        'width' => $this->width,
                        'length' => $this->length,
                        'tax_rate' => $this->tax_rate,
                        'available_from' => $this->available_from ? \Carbon\Carbon::parse($this->available_from) : null,
                        'available_to' => $this->available_to ? \Carbon\Carbon::parse($this->available_to) : null,
                        'is_active' => $this->is_active,
                        'is_variant_master' => $this->is_variant_master,
                        'is_featured' => $this->is_featured,
                        'sort_order' => $this->sort_order,
                    ]);
                    $this->successMessage = 'Produkt został zaktualizowany pomyślnie.';
                } else {
                    // Create new product
                    $this->product = Product::create([
                        'sku' => $this->sku,
                        'name' => $this->name,
                        'slug' => $this->slug ?: Str::slug($this->name),
                        'product_type_id' => $this->product_type_id,
                        'manufacturer' => $this->manufacturer,
                        'supplier_code' => $this->supplier_code,
                        'ean' => $this->ean,
                        'short_description' => $this->short_description,
                        'long_description' => $this->long_description,
                        'meta_title' => $this->meta_title,
                        'meta_description' => $this->meta_description,
                        'weight' => $this->weight,
                        'height' => $this->height,
                        'width' => $this->width,
                        'length' => $this->length,
                        'tax_rate' => $this->tax_rate,
                        'available_from' => $this->available_from ? \Carbon\Carbon::parse($this->available_from) : null,
                        'available_to' => $this->available_to ? \Carbon\Carbon::parse($this->available_to) : null,
                        'is_active' => $this->is_active,
                        'is_variant_master' => $this->is_variant_master,
                        'is_featured' => $this->is_featured,
                        'sort_order' => $this->sort_order,
                    ]);
                    $this->isEditMode = true;

                    // Create ProductShopData records for shops that were added before product save
                    // CRITICAL FIX: Filter out shops that are marked for removal!
                    if (!empty($this->exportedShops)) {
                        // Normalize all IDs to int for proper comparison
                        $exportedNormalized = array_map('intval', $this->exportedShops);
                        $toRemoveNormalized = array_map('intval', $this->shopsToRemove);
                        $shopsToCreate = array_diff($exportedNormalized, $toRemoveNormalized);

                        Log::debug('Save: Filtering shops to create', [
                            'exportedShops' => $this->exportedShops,
                            'shopsToRemove' => $this->shopsToRemove,
                            'shopsToCreate' => $shopsToCreate,
                        ]);

                        foreach ($shopsToCreate as $shopId) {
                            // Only create if shopData exists and has no DB ID yet
                            if (isset($this->shopData[$shopId]) &&
                                (!isset($this->shopData[$shopId]['id']) || $this->shopData[$shopId]['id'] === null)) {
                                $productShopData = \App\Models\ProductShopData::create([
                                    'product_id' => $this->product->id,
                                    'shop_id' => $shopId,
                                    'name' => null,
                                    'slug' => null,
                                    'short_description' => null,
                                    'long_description' => null,
                                    'meta_title' => null,
                                    'meta_description' => null,
                                    'sync_status' => 'pending',
                                    'is_published' => false,
                                ]);
                                // Update shopData with created ID
                                $this->shopData[$shopId]['id'] = $productShopData->id;

                                Log::info('Created ProductShopData for pending shop', [
                                    'product_id' => $this->product->id,
                                    'shop_id' => $shopId,
                                    'db_id' => $productShopData->id,
                                ]);
                            } else if (!isset($this->shopData[$shopId])) {
                                Log::warning('Skipping shop create - shopData missing (likely removed)', [
                                    'shop_id' => $shopId,
                                ]);
                            }
                        }
                    }

                    $this->successMessage = 'Produkt został utworzony pomyślnie.';
                    $this->dispatch('product-saved', ['productId' => $this->product->id]);
                }

                // FIXED: Delete shops marked for removal
                if (!empty($this->shopsToRemove) && $this->product) {
                    foreach ($this->shopsToRemove as $shopId) {
                        // Find and delete ProductShopData record
                        $deleted = \App\Models\ProductShopData::where('product_id', $this->product->id)
                            ->where('shop_id', $shopId)
                            ->delete();

                        Log::info('Deleted shop from product (pending removal)', [
                            'product_id' => $this->product->id,
                            'shop_id' => $shopId,
                            'deleted_count' => $deleted,
                        ]);
                    }
                    // Clear the pending removals list
                    $this->shopsToRemove = [];
                }

                // CRITICAL FIX: Clear removed shops cache after save (no longer needed)
                $this->removedShopsCache = [];
            } else {
                // SHOP MODE: Save ONLY to product_shop_data, DON'T touch products table
                if (!$this->product) {
                    throw new \Exception('Nie można zapisać danych dla sklepu - produkt nie istnieje. Najpierw utwórz produkt w trybie domyślnym.');
                }

                $this->saveShopSpecificData();
                $this->successMessage = "Dane produktu dla sklepu zostały zapisane pomyślnie.";
            }

        } catch (\Exception $e) {
            Log::error('Product save failed', [
                'error' => $e->getMessage(),
                'sku' => $this->sku,
                'name' => $this->name,
                'activeShopId' => $this->activeShopId,
                'isEditMode' => $this->isEditMode,
            ]);
            $this->addError('general', 'Wystąpił błąd podczas zapisywania produktu: ' . $e->getMessage());
        } finally {
            $this->isSaving = false;
        }
    }

    /**
     * Save shop-specific data to ProductShopData table
     * CRITICAL: Only when activeShopId is set (shop mode)
     */
    private function saveShopSpecificData(): void
    {
        if (!$this->product || $this->activeShopId === null) {
            return;
        }

        // Find existing shop data or create new
        $productShopData = \App\Models\ProductShopData::firstOrNew([
            'product_id' => $this->product->id,
            'shop_id' => $this->activeShopId,
        ]);

        // Save current form data as shop-specific data - ALL FIELDS SUPPORTED
        $productShopData->fill([
            // === BASIC INFORMATION ===
            'sku' => $this->sku,
            'name' => $this->name,
            'slug' => $this->slug ?: Str::slug($this->name),
            'product_type_id' => $this->product_type_id,
            'manufacturer' => $this->manufacturer,
            'supplier_code' => $this->supplier_code,
            'ean' => $this->ean,

            // === DESCRIPTIONS & SEO ===
            'short_description' => $this->short_description,
            'long_description' => $this->long_description,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,

            // === PHYSICAL PROPERTIES ===
            'weight' => $this->weight,
            'height' => $this->height,
            'width' => $this->width,
            'length' => $this->length,
            'tax_rate' => $this->tax_rate,

            // === STATUS & SETTINGS ===
            'is_active' => $this->is_active,
            'is_variant_master' => $this->is_variant_master,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,

            // === SYNC METADATA ===
            'is_published' => $this->is_active,
            'sync_status' => 'pending',
            'last_sync_hash' => md5(json_encode([
                'sku' => $this->sku,
                'name' => $this->name,
                'manufacturer' => $this->manufacturer,
                'short_description' => $this->short_description,
                'long_description' => $this->long_description,
                'weight' => $this->weight,
                'tax_rate' => $this->tax_rate,
            ])),
        ]);

        $productShopData->save();

        Log::info('Shop-specific data saved', [
            'product_id' => $this->product->id,
            'shop_id' => $this->activeShopId,
            'shop_data_id' => $productShopData->id,
            'user_id' => auth()->id(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SYNCHRONIZATION OPERATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Enhanced sync functionality - context-aware synchronization
     * Replaces old updateOnly with sync-focused operation
     */
    public function syncToShops(): void
    {
        $this->isSaving = true;
        $this->successMessage = '';

        try {
            // First save ALL current changes (including pending from other tabs)
            $this->saveAllPendingChanges();

            if (!empty($this->getErrorBag()->all())) {
                return; // Stop if there are validation errors
            }

            if ($this->activeShopId === null) {
                // Sync to ALL shops from default data
                $this->syncToAllShops();
            } else {
                // Sync current shop data to PrestaShop
                $this->syncToCurrentShop();
            }

        } catch (\Exception $e) {
            Log::error('Error during sync operation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'product_id' => $this->product?->id,
                'shop_id' => $this->activeShopId,
            ]);

            $this->dispatch('error', message: 'Błąd podczas synchronizacji: ' . $e->getMessage());
        } finally {
            $this->isSaving = false;
        }
    }

    /**
     * Sync product data to all connected shops
     */
    private function syncToAllShops(): void
    {
        if (!$this->product) {
            $this->dispatch('error', message: 'Nie można synchronizować - brak produktu');
            return;
        }

        try {
            $connectedShops = \App\Models\PrestaShopShop::where('connection_status', 'connected')
                                                         ->where('is_active', true)
                                                         ->get();

            if ($connectedShops->isEmpty()) {
                $this->dispatch('warning', message: 'Brak aktywnych sklepów do synchronizacji');
                return;
            }

            $syncResults = [
                'success' => 0,
                'failed' => 0,
                'shops' => []
            ];

            foreach ($connectedShops as $shop) {
                try {
                    // Mark shop data for sync (or create if doesn't exist)
                    $shopData = \App\Models\ProductShopData::firstOrCreate([
                        'product_id' => $this->product->id,
                        'shop_id' => $shop->id,
                    ], [
                        // Initialize with default product data
                        'name' => $this->product->name,
                        'slug' => $this->product->slug,
                        'short_description' => $this->product->short_description,
                        'long_description' => $this->product->long_description,
                        'meta_title' => $this->product->meta_title,
                        'meta_description' => $this->product->meta_description,
                        'sync_status' => 'pending',
                        'is_published' => false,
                    ]);

                    // Update sync status to pending
                    $shopData->update([
                        'sync_status' => 'pending',
                        'last_sync_attempt' => now(),
                    ]);

                    // DISPATCH SYNC JOB - ETAP_07 PrestaShop Integration
                    SyncProductToPrestaShop::dispatch($this->product, $shop);

                    $syncResults['success']++;
                    $syncResults['shops'][] = $shop->name;

                    Log::info('Shop sync job dispatched', [
                        'product_id' => $this->product->id,
                        'shop_id' => $shop->id,
                        'shop_name' => $shop->name,
                        'queue' => 'prestashop-sync',
                    ]);

                } catch (\Exception $e) {
                    $syncResults['failed']++;
                    Log::error('Failed to mark shop for sync', [
                        'shop_id' => $shop->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($syncResults['success'] > 0) {
                $this->dispatch('success', message: "Zaplanowano synchronizację produktu na {$syncResults['success']} sklepach");
            }

            if ($syncResults['failed'] > 0) {
                $this->dispatch('warning', message: "Nie udało się zaplanować synchronizacji na {$syncResults['failed']} sklepach");
            }

        } catch (\Exception $e) {
            Log::error('Error syncing to all shops', [
                'error' => $e->getMessage(),
                'product_id' => $this->product->id,
            ]);

            $this->dispatch('error', message: 'Błąd podczas planowania synchronizacji ze wszystkimi sklepami');
        }
    }

    /**
     * Sync current shop data to PrestaShop
     */
    private function syncToCurrentShop(): void
    {
        if (!$this->product || $this->activeShopId === null) {
            $this->dispatch('error', message: 'Nie można synchronizować - brak produktu lub sklepu');
            return;
        }

        try {
            $shop = \App\Models\PrestaShopShop::find($this->activeShopId);
            if (!$shop) {
                $this->dispatch('error', message: 'Nie znaleziono sklepu');
                return;
            }

            if ($shop->connection_status !== 'connected') {
                $this->dispatch('error', message: "Sklep {$shop->name} nie jest połączony");
                return;
            }

            // Get or create shop data
            $shopData = \App\Models\ProductShopData::firstOrCreate([
                'product_id' => $this->product->id,
                'shop_id' => $this->activeShopId,
            ]);

            // Update shop data with current form values
            $shopData->update([
                'name' => $this->name,
                'slug' => $this->slug ?: Str::slug($this->name),
                'short_description' => $this->short_description,
                'long_description' => $this->long_description,
                'meta_title' => $this->meta_title,
                'meta_description' => $this->meta_description,
                'sync_status' => 'pending',
                'last_sync_attempt' => now(),
            ]);

            // DISPATCH SYNC JOB - ETAP_07 PrestaShop Integration
            SyncProductToPrestaShop::dispatch($this->product, $shop);

            $this->dispatch('success', message: "Zaplanowano synchronizację produktu ze sklepem: {$shop->name}");

            Log::info('Single shop sync job dispatched', [
                'product_id' => $this->product->id,
                'shop_id' => $this->activeShopId,
                'shop_name' => $shop->name,
                'queue' => 'prestashop-sync',
            ]);

        } catch (\Exception $e) {
            Log::error('Error syncing to current shop', [
                'error' => $e->getMessage(),
                'product_id' => $this->product->id,
                'shop_id' => $this->activeShopId,
            ]);

            $this->dispatch('error', message: 'Błąd podczas planowania synchronizacji ze sklepem');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SYNC STATUS METHODS (ETAP_07 FAZA 3)
    |--------------------------------------------------------------------------
    */

    /**
     * Get sync status for specific shop
     *
     * @param int $shopId
     * @return ProductSyncStatus|null
     */
    public function getSyncStatusForShop(int $shopId): ?ProductSyncStatus
    {
        if (!$this->product) {
            return null;
        }

        return ProductSyncStatus::where('product_id', $this->product->id)
            ->where('shop_id', $shopId)
            ->first();
    }

    /**
     * Get sync status display data for shop
     * Returns formatted data for UI display
     *
     * @param int $shopId
     * @return array
     */
    public function getSyncStatusDisplay(int $shopId): array
    {
        $syncStatus = $this->getSyncStatusForShop($shopId);

        if (!$syncStatus) {
            return [
                'status' => 'not_synced',
                'icon' => '⚪',
                'class' => 'text-gray-400',
                'text' => 'Nie synchronizowano',
                'prestashop_id' => null,
            ];
        }

        return match($syncStatus->sync_status) {
            'synced' => [
                'status' => 'synced',
                'icon' => '✅',
                'class' => 'text-green-600 dark:text-green-400',
                'text' => 'Zsynchronizowany',
                'prestashop_id' => $syncStatus->prestashop_product_id,
                'last_sync' => $syncStatus->last_success_sync_at?->diffForHumans(),
            ],
            'pending' => [
                'status' => 'pending',
                'icon' => '⏳',
                'class' => 'text-yellow-600 dark:text-yellow-400',
                'text' => 'Oczekuje',
                'prestashop_id' => $syncStatus->prestashop_product_id,
            ],
            'syncing' => [
                'status' => 'syncing',
                'icon' => '🔄',
                'class' => 'text-blue-600 dark:text-blue-400',
                'text' => 'Synchronizacja...',
                'prestashop_id' => $syncStatus->prestashop_product_id,
            ],
            'error' => [
                'status' => 'error',
                'icon' => '❌',
                'class' => 'text-red-600 dark:text-red-400',
                'text' => 'Błąd',
                'prestashop_id' => $syncStatus->prestashop_product_id,
                'error_message' => $syncStatus->error_message,
                'retry_count' => $syncStatus->retry_count,
            ],
            'conflict' => [
                'status' => 'conflict',
                'icon' => '⚠️',
                'class' => 'text-orange-600 dark:text-orange-400',
                'text' => 'Konflikt',
                'prestashop_id' => $syncStatus->prestashop_product_id,
            ],
            default => [
                'status' => 'unknown',
                'icon' => '❓',
                'class' => 'text-gray-400',
                'text' => 'Nieznany',
                'prestashop_id' => $syncStatus->prestashop_product_id,
            ],
        };
    }

    /**
     * Retry failed sync for shop
     *
     * @param int $shopId
     * @return void
     */
    public function retrySync(int $shopId): void
    {
        if (!$this->product) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Brak produktu do synchronizacji',
            ]);
            return;
        }

        $syncStatus = $this->getSyncStatusForShop($shopId);
        if (!$syncStatus) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Nie znaleziono statusu synchronizacji',
            ]);
            return;
        }

        // Reset error and dispatch new job
        $syncStatus->update([
            'sync_status' => 'pending',
            'error_message' => null,
        ]);

        $shop = PrestaShopShop::find($shopId);
        if ($shop) {
            SyncProductToPrestaShop::dispatch($this->product, $shop);

            Log::info('Sync retry dispatched', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
            ]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Synchronizacja została wznowiona',
            ]);
        } else {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Nie znaleziono sklepu',
            ]);
        }
    }

    /**
     * Save all pending changes without closing form
     * Public method accessible from frontend
     */
    public function saveAllChanges(): void
    {
        $this->saveAllPendingChanges();
    }

    /**
     * Save all pending changes from all tabs/shops and close form
     * CRITICAL: This saves changes from ALL contexts, not just current tab
     */
    public function saveAndClose()
    {
        Log::info('saveAndClose called', [
            'defaultCategories' => $this->defaultCategories,
            'hasUnsavedChanges' => $this->hasUnsavedChanges,
            'pendingChanges_keys' => array_keys($this->pendingChanges),
            'pendingChanges_default_categories' => $this->pendingChanges['default']['defaultCategories'] ?? 'NOT_SET',
        ]);

        $this->saveAllPendingChanges();

        if (empty($this->getErrorBag()->all())) {
            return redirect('/admin/products');
        }
    }

    /**
     * Save all pending changes across all contexts (default + all shops)
     * This ensures no user changes are lost regardless of which tab they're currently on
     */
    public function saveAllPendingChanges(): void
    {
        $this->isSaving = true;
        $this->successMessage = '';

        try {
            // First, save current form state to pending changes
            $this->savePendingChanges();

            if (empty($this->pendingChanges)) {
                // No pending changes - fallback to current context save
                $this->updateOnly();
                return;
            }

            // Count contexts before processing
            $contextsCount = count($this->pendingChanges);

            // Process all pending changes
            foreach ($this->pendingChanges as $contextKey => $changes) {
                if ($contextKey === 'default') {
                    // Save to main products table
                    $this->savePendingChangesToProduct($changes);
                } else {
                    // Save to specific shop (contextKey is shopId)
                    $this->savePendingChangesToShop((int)$contextKey, $changes);
                }
            }

            // Clear all pending changes after successful save
            $this->pendingChanges = [];
            $this->hasUnsavedChanges = false;

            // Update stored data structures
            $this->storeDefaultData();
            $this->updateStoredShopData();

            // Refresh form with current context data
            if ($this->activeShopId === null) {
                $this->loadDefaultDataToForm();
            } else {
                $this->loadShopDataToForm($this->activeShopId);
            }

            $this->dispatch('success', message: "Wszystkie zmiany zostały zapisane pomyślnie ({$contextsCount} kontekstów)");

            Log::info('All pending changes saved successfully', [
                'product_id' => $this->product?->id,
                'contexts_saved' => $contextsCount,
                'user_id' => auth()->id(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error saving all pending changes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'product_id' => $this->product?->id,
                'pending_contexts' => array_keys($this->pendingChanges),
            ]);

            $this->addError('general', 'Wystąpił błąd podczas zapisywania zmian: ' . $e->getMessage());
        } finally {
            $this->isSaving = false;
        }
    }

    /**
     * Save pending changes to main products table (default data)
     */
    private function savePendingChangesToProduct(array $changes): void
    {
        // Basic validation
        $this->validate([
            'sku' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'product_type_id' => 'required|integer|exists:product_types,id',
            'tax_rate' => 'required|numeric|min:0|max:100',
        ]);

        if ($this->isEditMode && $this->product) {
            // Update existing product with pending changes
            $this->product->update([
                'sku' => $changes['sku'] ?? $this->product->sku,
                'name' => $changes['name'] ?? $this->product->name,
                'slug' => $changes['slug'] ?? $this->product->slug ?: Str::slug($changes['name'] ?? $this->product->name),
                'product_type_id' => $changes['product_type_id'] ?? $this->product->product_type_id,
                'manufacturer' => $changes['manufacturer'] ?? $this->product->manufacturer,
                'supplier_code' => $changes['supplier_code'] ?? $this->product->supplier_code,
                'ean' => $changes['ean'] ?? $this->product->ean,
                'short_description' => $changes['short_description'] ?? $this->product->short_description,
                'long_description' => $changes['long_description'] ?? $this->product->long_description,
                'meta_title' => $changes['meta_title'] ?? $this->product->meta_title,
                'meta_description' => $changes['meta_description'] ?? $this->product->meta_description,
                'weight' => $changes['weight'] ?? $this->product->weight,
                'height' => $changes['height'] ?? $this->product->height,
                'width' => $changes['width'] ?? $this->product->width,
                'length' => $changes['length'] ?? $this->product->length,
                'tax_rate' => $changes['tax_rate'] ?? $this->product->tax_rate,
                'available_from' => isset($changes['available_from']) && $changes['available_from']
                    ? \Carbon\Carbon::parse($changes['available_from'])
                    : $this->product->available_from,
                'available_to' => isset($changes['available_to']) && $changes['available_to']
                    ? \Carbon\Carbon::parse($changes['available_to'])
                    : $this->product->available_to,
                'is_active' => $changes['is_active'] ?? $this->product->is_active,
                'is_variant_master' => $changes['is_variant_master'] ?? $this->product->is_variant_master,
                'is_featured' => $changes['is_featured'] ?? $this->product->is_featured,
                'sort_order' => $changes['sort_order'] ?? $this->product->sort_order,
            ]);

            // Save categories if they were changed (using full state for save compatibility)
            Log::info('Checking category sync condition', [
                'fullDefaultCategories_isset' => isset($changes['_fullDefaultCategories']),
                'fullDefaultCategories_value' => $changes['_fullDefaultCategories'] ?? 'NOT_SET',
                'categoryManager_exists' => $this->categoryManager !== null,
                'categoryManager_type' => $this->categoryManager ? get_class($this->categoryManager) : 'NULL',
                'product_id' => $this->product->id,
            ]);

            // CRITICAL FIX: Re-initialize CategoryManager if null (Livewire serialization issue)
            if (isset($changes['_fullDefaultCategories']) && !$this->categoryManager) {
                Log::info('Re-initializing CategoryManager - was null during save');
                $this->categoryManager = new ProductCategoryManager($this);
            }

            if (isset($changes['_fullDefaultCategories']) && $this->categoryManager) {
                Log::info('Category sync condition MET - executing sync');

                // Update component data before syncing categories
                // CRITICAL: Only update defaultCategories - DO NOT contaminate shopCategories!
                $this->defaultCategories = $changes['_fullDefaultCategories'];

                // Do NOT restore shopCategories from _fullShopCategories as they may be cross-contaminated
                // Instead, reload clean shopCategories from database before sync
                $this->reloadCleanShopCategories();

                $this->categoryManager->syncCategories();
                Log::info('Product categories updated via CategoryManager', [
                    'product_id' => $this->product->id,
                    'default_categories' => $changes['_fullDefaultCategories'],
                    'shop_categories' => $changes['_fullShopCategories'] ?? [],
                ]);
            }

            Log::info('Product updated from pending changes', [
                'product_id' => $this->product->id,
                'changes_applied' => count($changes),
            ]);

        } else {
            // Create new product from pending changes
            $this->product = Product::create([
                'sku' => $changes['sku'],
                'name' => $changes['name'],
                'slug' => $changes['slug'] ?: Str::slug($changes['name']),
                'product_type_id' => $changes['product_type_id'],
                'manufacturer' => $changes['manufacturer'] ?? '',
                'supplier_code' => $changes['supplier_code'] ?? '',
                'ean' => $changes['ean'] ?? '',
                'short_description' => $changes['short_description'] ?? '',
                'long_description' => $changes['long_description'] ?? '',
                'meta_title' => $changes['meta_title'] ?? '',
                'meta_description' => $changes['meta_description'] ?? '',
                'weight' => $changes['weight'] ?? null,
                'height' => $changes['height'] ?? null,
                'width' => $changes['width'] ?? null,
                'length' => $changes['length'] ?? null,
                'tax_rate' => $changes['tax_rate'] ?? 23.00,
                'available_from' => isset($changes['available_from']) && $changes['available_from']
                    ? \Carbon\Carbon::parse($changes['available_from'])
                    : null,
                'available_to' => isset($changes['available_to']) && $changes['available_to']
                    ? \Carbon\Carbon::parse($changes['available_to'])
                    : null,
                'is_active' => $changes['is_active'] ?? true,
                'is_variant_master' => $changes['is_variant_master'] ?? false,
                'is_featured' => $changes['is_featured'] ?? false,
                'sort_order' => $changes['sort_order'] ?? 0,
            ]);

            $this->isEditMode = true;

            // Save categories if they were provided
            if (isset($changes['defaultCategories']) && !empty($changes['defaultCategories']['selected']) && $this->categoryManager) {
                // Update component data before syncing categories
                $this->defaultCategories = $changes['defaultCategories'];
                if (isset($changes['shopCategories'])) {
                    $this->shopCategories = $changes['shopCategories'];
                }

                $this->categoryManager->syncCategories();
                Log::info('New product categories attached via CategoryManager', [
                    'product_id' => $this->product->id,
                    'default_categories' => $changes['defaultCategories'],
                    'shop_categories' => $changes['shopCategories'] ?? [],
                ]);
            }

            Log::info('New product created from pending changes', [
                'product_id' => $this->product->id,
                'sku' => $this->product->sku,
            ]);
        }

        // FIXED: Create ProductShopData for pending shops (same logic as updateOnly)
        // CRITICAL FIX: Filter out shops that are marked for removal!
        if (!empty($this->exportedShops) && $this->product) {
            // Normalize all IDs to int for proper comparison
            $exportedNormalized = array_map('intval', $this->exportedShops);
            $toRemoveNormalized = array_map('intval', $this->shopsToRemove);
            $shopsToCreate = array_diff($exportedNormalized, $toRemoveNormalized);

            Log::debug('SavePendingChanges: Filtering shops to create', [
                'exportedShops' => $this->exportedShops,
                'shopsToRemove' => $this->shopsToRemove,
                'shopsToCreate' => $shopsToCreate,
            ]);

            foreach ($shopsToCreate as $shopId) {
                // Only create if shopData exists and has no DB ID yet
                if (isset($this->shopData[$shopId]) &&
                    (!isset($this->shopData[$shopId]['id']) || $this->shopData[$shopId]['id'] === null)) {
                    $productShopData = \App\Models\ProductShopData::create([
                        'product_id' => $this->product->id,
                        'shop_id' => $shopId,
                        'name' => null,
                        'slug' => null,
                        'short_description' => null,
                        'long_description' => null,
                        'meta_title' => null,
                        'meta_description' => null,
                        'sync_status' => 'pending',
                        'is_published' => false,
                    ]);
                    $this->shopData[$shopId]['id'] = $productShopData->id;

                    Log::info('Created ProductShopData from pending changes', [
                        'product_id' => $this->product->id,
                        'shop_id' => $shopId,
                        'db_id' => $productShopData->id,
                    ]);
                } else if (!isset($this->shopData[$shopId])) {
                    Log::warning('Skipping shop create from pending - shopData missing', [
                        'shop_id' => $shopId,
                    ]);
                }
            }
        }

        // FIXED: Delete shops marked for removal (same logic as updateOnly)
        if (!empty($this->shopsToRemove) && $this->product) {
            foreach ($this->shopsToRemove as $shopId) {
                $deleted = \App\Models\ProductShopData::where('product_id', $this->product->id)
                    ->where('shop_id', $shopId)
                    ->delete();

                Log::info('Deleted shop from product (pending removal - from savePendingChangesToProduct)', [
                    'product_id' => $this->product->id,
                    'shop_id' => $shopId,
                    'deleted_count' => $deleted,
                ]);
            }
            $this->shopsToRemove = [];
        }

        // CRITICAL FIX: Clear removed shops cache after save (no longer needed)
        $this->removedShopsCache = [];
    }

    /**
     * Save pending changes to specific shop data
     */
    private function savePendingChangesToShop(int $shopId, array $changes): void
    {
        if (!$this->product) {
            Log::warning('Cannot save shop data - no product exists', ['shop_id' => $shopId]);
            return;
        }

        // Find existing shop data or create new
        $productShopData = \App\Models\ProductShopData::firstOrNew([
            'product_id' => $this->product->id,
            'shop_id' => $shopId,
        ]);

        // Save changes to shop-specific data
        $productShopData->fill([
            'sku' => $changes['sku'] ?? $productShopData->sku,
            'name' => $changes['name'] ?? $productShopData->name,
            'slug' => $changes['slug'] ?? $productShopData->slug ?: Str::slug($changes['name'] ?? $productShopData->name),
            'product_type_id' => $changes['product_type_id'] ?? $productShopData->product_type_id,
            'manufacturer' => $changes['manufacturer'] ?? $productShopData->manufacturer,
            'supplier_code' => $changes['supplier_code'] ?? $productShopData->supplier_code,
            'ean' => $changes['ean'] ?? $productShopData->ean,
            'short_description' => $changes['short_description'] ?? $productShopData->short_description,
            'long_description' => $changes['long_description'] ?? $productShopData->long_description,
            'meta_title' => $changes['meta_title'] ?? $productShopData->meta_title,
            'meta_description' => $changes['meta_description'] ?? $productShopData->meta_description,
            'weight' => $changes['weight'] ?? $productShopData->weight,
            'height' => $changes['height'] ?? $productShopData->height,
            'width' => $changes['width'] ?? $productShopData->width,
            'length' => $changes['length'] ?? $productShopData->length,
            'tax_rate' => $changes['tax_rate'] ?? $productShopData->tax_rate,
            'available_from' => isset($changes['available_from']) && $changes['available_from']
                ? \Carbon\Carbon::parse($changes['available_from'])
                : $productShopData->available_from,
            'available_to' => isset($changes['available_to']) && $changes['available_to']
                ? \Carbon\Carbon::parse($changes['available_to'])
                : $productShopData->available_to,
            'is_active' => $changes['is_active'] ?? $productShopData->is_active,
            'is_variant_master' => $changes['is_variant_master'] ?? $productShopData->is_variant_master,
            'is_featured' => $changes['is_featured'] ?? $productShopData->is_featured,
            'sort_order' => $changes['sort_order'] ?? $productShopData->sort_order,
        ]);

        $productShopData->save();

        // Save shop-specific categories if they were changed
        // FIXED: Use contextCategories instead of shopCategories (which doesn't exist in pending changes)
        if (isset($changes['contextCategories'])) {
            $shopCategoryData = $changes['contextCategories'];
            $selectedCategories = $shopCategoryData['selected'] ?? [];
            $primaryCategoryId = $shopCategoryData['primary'] ?? null;

            \App\Models\ProductShopCategory::setCategoriesForProductShop(
                $this->product->id,
                $shopId,
                $selectedCategories,
                $primaryCategoryId
            );

            Log::info('Shop-specific categories saved to database', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'categories_count' => count($selectedCategories),
                'primary_category' => $primaryCategoryId,
                'source' => 'contextCategories',
            ]);
        }

        Log::info('Shop-specific data updated from pending changes', [
            'product_id' => $this->product->id,
            'shop_id' => $shopId,
            'shop_data_id' => $productShopData->id,
            'changes_applied' => count($changes),
        ]);
    }


    /**
     * Update stored shop data after saving
     */
    private function updateStoredShopData(): void
    {
        if (!$this->product) {
            return;
        }

        // Reload all shop data from database
        $productShopData = \App\Models\ProductShopData::where('product_id', $this->product->id)->get();

        foreach ($productShopData as $shopData) {
            $this->shopData[$shopData->shop_id] = [
                'id' => $shopData->id,
                'sku' => $shopData->sku,
                'name' => $shopData->name,
                'slug' => $shopData->slug,
                'short_description' => $shopData->short_description,
                'long_description' => $shopData->long_description,
                'meta_title' => $shopData->meta_title,
                'meta_description' => $shopData->meta_description,
                'sync_status' => $shopData->sync_status,
                'is_published' => $shopData->is_published,
                'last_sync_at' => $shopData->last_sync_at,
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PRESTASHOP CATEGORY METHODS (ETAP_07 FAZA 2B.2)
    |--------------------------------------------------------------------------
    */

    /**
     * Load PrestaShop categories for a shop (from API endpoint)
     *
     * ETAP_07 FAZA 2B.2 - Dynamic category loading
     *
     * @param int $shopId
     * @return void
     */
    public function loadPrestaShopCategories(int $shopId): void
    {
        try {
            Log::info('Loading PrestaShop categories', [
                'shop_id' => $shopId,
                'product_id' => $this->product?->id,
            ]);

            // Call API endpoint (FAZA 2B.1)
            $response = \Illuminate\Support\Facades\Http::get(url("/api/v1/prestashop/categories/{$shopId}"));

            if ($response->successful()) {
                $data = $response->json();

                // Store categories in component state
                $this->prestashopCategories[$shopId] = $data['categories'];

                // Success notification
                $this->dispatch('notification', [
                    'type' => 'success',
                    'message' => "Kategorie załadowane z {$data['shop_name']} (" . count($data['categories']) . " głównych kategorii)"
                ]);

                Log::info("PrestaShop categories loaded successfully", [
                    'shop_id' => $shopId,
                    'shop_name' => $data['shop_name'],
                    'cached' => $data['cached'] ?? false,
                    'root_categories' => count($data['categories']),
                ]);
            } else {
                throw new \Exception("API error: HTTP " . $response->status());
            }

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => "Błąd podczas ładowania kategorii: {$e->getMessage()}"
            ]);

            Log::error("Failed to load PrestaShop categories", [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Refresh PrestaShop categories (clear cache + reload)
     *
     * @param int $shopId
     * @return void
     */
    public function refreshPrestaShopCategories(int $shopId): void
    {
        try {
            Log::info('Refreshing PrestaShop categories', [
                'shop_id' => $shopId,
                'product_id' => $this->product?->id,
            ]);

            // Call refresh endpoint (clears cache)
            $response = \Illuminate\Support\Facades\Http::post(url("/api/v1/prestashop/categories/{$shopId}/refresh"));

            if ($response->successful()) {
                $data = $response->json();

                // Update component state
                $this->prestashopCategories[$shopId] = $data['categories'];

                $this->dispatch('notification', [
                    'type' => 'success',
                    'message' => "Kategorie odświeżone z PrestaShop (" . count($data['categories']) . " głównych kategorii)"
                ]);

                Log::info("PrestaShop categories refreshed successfully", [
                    'shop_id' => $shopId,
                    'shop_name' => $data['shop_name'] ?? 'Unknown',
                    'root_categories' => count($data['categories']),
                ]);
            } else {
                throw new \Exception("API error: HTTP " . $response->status());
            }

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => "Błąd podczas odświeżania: {$e->getMessage()}"
            ]);

            Log::error("Failed to refresh PrestaShop categories", [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get category name by ID (for selected categories display)
     *
     * @param int $shopId
     * @param int $categoryId
     * @return string
     */
    public function getCategoryName(int $shopId, int $categoryId): string
    {
        if (!isset($this->prestashopCategories[$shopId])) {
            return "Category #{$categoryId}";
        }

        // Recursive search in tree
        $findCategory = function($categories, $id) use (&$findCategory) {
            foreach ($categories as $cat) {
                if ($cat['id'] == $id) {
                    return $cat['name'];
                }

                if (count($cat['children']) > 0) {
                    $result = $findCategory($cat['children'], $id);
                    if ($result) return $result;
                }
            }
            return null;
        };

        $name = $findCategory($this->prestashopCategories[$shopId], $categoryId);
        return $name ?? "Category #{$categoryId}";
    }

    /**
     * Hook: when user switches to a shop, auto-load PrestaShop categories if not cached
     *
     * @param int|null $shopId
     * @return void
     */
    public function updatedActiveShopId($shopId): void
    {
        // Only auto-load if switching TO a shop (not to default)
        if ($shopId === null) {
            return;
        }

        // Auto-load categories if not already loaded
        if (!isset($this->prestashopCategories[$shopId])) {
            Log::info('Auto-loading PrestaShop categories on shop tab switch', [
                'shop_id' => $shopId,
            ]);
            $this->loadPrestaShopCategories($shopId);
        }
    }

    /**
     * Cancel form and return to list
     */
    public function cancel()
    {
        return redirect('/admin/products');
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
        try {
            $pageTitle = $this->isEditMode
                ? "Edytuj produkt: {$this->name}"
                : 'Dodaj nowy produkt';

            $breadcrumbs = [
                ['name' => 'Admin', 'url' => route('admin.dashboard')],
                ['name' => 'Produkty', 'url' => route('admin.products.index')],
                ['name' => $this->isEditMode ? 'Edytuj' : 'Dodaj', 'url' => null],
            ];

            Log::info('ProductForm render() called', [
                'isEditMode' => $this->isEditMode,
                'pageTitle' => $pageTitle
            ]);

            // Prepare field inheritance information
            $fieldInheritance = [];
            $fields = ['name', 'slug', 'short_description', 'long_description', 'meta_title', 'meta_description'];
            foreach ($fields as $field) {
                $fieldInheritance[$field] = $this->isFieldInherited($field);
            }

            return view('livewire.products.management.product-form', [
                'categories' => $this->categories ?? collect([]),
                'productTypes' => $this->productTypes ?? collect([]),
                'calculatedVolume' => null,
                'shortDescriptionWarning' => false,
                'longDescriptionWarning' => false,
                'hasChanges' => false,
                'availableShops' => $this->availableShops,
                'availableAttributes' => [],
                'fieldInheritance' => $fieldInheritance,
                'isShopMode' => $this->activeShopId !== null,
            ]);

        } catch (\Exception $e) {
            Log::error('ProductForm render() failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response('<h1>Error rendering product form</h1><p>' . $e->getMessage() . '</p>');
        }
    }

    /**
     * Reload clean shop categories from database to prevent cross-contamination
     * This is used before save to ensure CategoryManager gets clean data
     */
    private function reloadCleanShopCategories(): void
    {
        if (!$this->product || !$this->product->exists) {
            return;
        }

        // Clear potentially contaminated shopCategories
        $this->shopCategories = [];

        // Load clean shop categories from database
        $shopCategories = \App\Models\ProductShopCategory::forProduct($this->product->id)
            ->with('shop')
            ->get()
            ->groupBy('shop_id');

        foreach ($shopCategories as $shopId => $categories) {
            $selectedIds = $categories->pluck('category_id')->toArray();
            $primaryCategory = $categories->where('is_primary', true)->first();

            $this->shopCategories[$shopId] = [
                'selected' => $selectedIds,
                'primary' => $primaryCategory?->category_id,
            ];
        }

        Log::info('Clean shop categories reloaded before save', [
            'product_id' => $this->product->id,
            'shop_categories_count' => count($this->shopCategories),
            'shop_ids' => array_keys($this->shopCategories),
        ]);
    }
}
