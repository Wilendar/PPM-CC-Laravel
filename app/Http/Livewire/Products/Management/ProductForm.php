<?php

namespace App\Http\Livewire\Products\Management;

use Livewire\Component;
use Livewire\Attributes\Renderless;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Category;
use App\Models\PrestaShopShop;
use App\Models\ProductAttribute;
use App\Models\PriceGroup;
use App\Models\Warehouse;
use App\Models\ProductPrice;
use App\Models\ProductStock;
use App\Models\ProductShopData;  // ETAP_13: Bulk sync status tracking
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\CategoryMappingsConverter;  // FIX #12: Category mappings Option A
use App\Http\Livewire\Products\Management\Traits\ProductFormValidation;
use App\Http\Livewire\Products\Management\Traits\ProductFormUpdates;
use App\Http\Livewire\Products\Management\Traits\ProductFormComputed;
use App\Http\Livewire\Products\Management\Traits\ProductFormShopTabs;
use App\Http\Livewire\Products\Management\Traits\ProductFormERPTabs;
use App\Http\Livewire\Products\Management\Traits\ProductFormFeatures;
use App\Http\Livewire\Products\Management\Traits\ProductFormVariants;
use App\Http\Livewire\Products\Management\Traits\VariantShopContextTrait;
use App\Http\Livewire\Products\Management\Traits\ProductFormCompatibility;
use App\Http\Livewire\Products\Management\Traits\ProductFormVisualDescription;
use App\Http\Livewire\Products\Management\Services\ProductMultiStoreManager;
use App\Http\Livewire\Products\Management\Services\ProductCategoryManager;
use App\Http\Livewire\Products\Management\Services\ProductFormSaver;
use App\Jobs\PrestaShop\SyncProductToPrestaShop;
use App\Jobs\PrestaShop\BulkSyncProducts; // ETAP_13 - Bulk sync operations
use App\Jobs\PrestaShop\BulkPullProducts; // ETAP_13 - Bulk pull operations
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
    use ProductFormShopTabs;
    use ProductFormERPTabs;
    use ProductFormFeatures;
    use ProductFormVariants;
    use VariantShopContextTrait;
    use ProductFormCompatibility;
    use ProductFormVisualDescription;

    /*
    |--------------------------------------------------------------------------
    | LIVEWIRE EVENT LISTENERS (FIX #12)
    |--------------------------------------------------------------------------
    */

    /**
     * Livewire event listeners
     *
     * @var array
     */
    protected $listeners = [
        'shop-categories-reloaded' => 'handleCategoriesReloaded',
        'delayed-reset-unsaved-changes' => 'forceResetUnsavedChanges',
        // Note: openCreateCategoryModal is called directly via wire:click, not via dispatch
    ];

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

    // === TAX RATE PROPERTIES (FAZA 5.2 - 2025-11-14) ===

    /**
     * Tax rate overrides per shop (indexed by shop_id)
     *
     * Format: [shop_id => float|null]
     * NULL value = use product default (no override)
     *
     * @var array<int, float|null>
     */
    public array $shopTaxRateOverrides = [];

    /**
     * Selected tax rate option (dropdown state)
     *
     * Values:
     * - 'use_default' - Use PPM product default
     * - 'prestashop_23' - Use PrestaShop 23% mapping
     * - 'prestashop_8' - Use PrestaShop 8% mapping
     * - 'prestashop_5' - Use PrestaShop 5% mapping
     * - 'prestashop_0' - Use PrestaShop 0% mapping
     * - 'custom' - Use custom value (customTaxRate property)
     *
     * @var string
     */
    public string $selectedTaxRateOption = 'use_default';

    /**
     * Custom tax rate value (when selectedTaxRateOption === 'custom')
     *
     * @var float|null
     */
    public ?float $customTaxRate = null;

    /**
     * Available tax rule groups per shop (cached from PrestaShop)
     *
     * Format: [shop_id => [['rate' => float, 'label' => string, 'prestashop_group_id' => int]]]
     *
     * @var array
     */
    public array $availableTaxRuleGroups = [];

    /**
     * Tax rule groups cache timestamp (prevent excessive API calls)
     *
     * @var array<int, int> [shop_id => timestamp]
     */
    public array $taxRuleGroupsCacheTimestamp = [];

    // === PUBLISHING SCHEDULE ===
    public ?string $available_from = null;
    public ?string $available_to = null;

    // === CATEGORIES (NEW CONTEXT-AWARE SYSTEM) ===

    // Category management service
    protected ?ProductCategoryManager $categoryManager = null;

    // Categories per context
    public array $defaultCategories = ['selected' => [], 'primary' => null]; // Default categories
    public array $shopCategories = []; // [shopId => ['selected' => [ids], 'primary' => id]]

    // FIX 2025-11-21 v5: Category expansion as public property (computed property didn't execute)
    public array $expandedCategoryIds = []; // Category IDs to expand (parent categories of selected items)

    // FIX 2025-11-28: Cache for expandedCategoryIds to avoid repeated database queries
    // MUST be public to persist across Livewire requests (private properties are not serialized)
    public array $expandedCategoryIdsCache = []; // [cacheKey => expandedIds]

    public array $shopAttributes = []; // [shopId => [attributeCode => value]]
    public array $exportedShops = [];   // Shops where product is exported
    public ?int $activeShopId = null;   // null = default data, int = specific shop
    public array $shopData = [];        // Per-shop data storage
    public array $defaultData = [];     // Original product data
    public bool $showShopSelector = false;
    public array $selectedShopsToAdd = [];

    // === PRICES & STOCK (2025-11-07 PROBLEM #4) ===
    public array $prices = [];  // [price_group_id => ['net' => float, 'gross' => float, 'margin' => float, 'is_active' => bool]]
    public array $stock = [];   // [warehouse_id => ['quantity' => int, 'reserved' => int, 'minimum' => int]]
    public array $priceGroups = [];  // Cache of PriceGroup models
    public array $warehouses = [];   // Cache of Warehouse models

    // === UI STATE ===
    public bool $isSaving = false;
    public bool $categoryEditingDisabled = false; // FIX #13: Reactive property for Alpine.js :disabled binding
    public array $validationErrors = [];
    public string $successMessage = '';
    public bool $showSlugField = false;
    public int $shortDescriptionCount = 0;
    public int $longDescriptionCount = 0;

    // === CREATE CATEGORY MODAL (ETAP_07b FAZA 4.2.3) ===
    public bool $showCreateCategoryModal = false;
    public ?int $createCategoryShopId = null;
    public string $newCategoryName = '';
    public ?int $newCategoryParentId = null;

    // === INLINE CATEGORY CREATION (ETAP_07b FAZA 4.2.3 PERFORMANCE FIX) ===
    // Single source of truth - controls which category shows inline form
    // Prevents 927 Alpine x-data instances with functions (performance killer)
    public ?int $inlineCreateParentId = null;
    public string $inlineCreateName = '';
    public string $inlineCreateContext = 'default';

    // === DEFERRED CATEGORY OPERATIONS (2025-11-26) ===
    // Categories are NOT created/deleted immediately - only on Save!
    // User can cancel and all pending operations are discarded.
    public array $pendingNewCategories = [];    // [context => [{name, parentId, tempId}, ...]]
    public array $pendingDeleteCategories = []; // [context => [categoryId, ...]]
    private int $tempCategoryIdCounter = -1;    // Negative IDs for pending categories

    // === PENDING CHANGES SYSTEM ===
    public array $pendingChanges = [];     // [shopId => [field => value]] or ['default' => [field => value]]
    public bool $hasUnsavedChanges = false; // Track if there are any pending changes
    public bool $isLoadingData = false;    // FIX 2025-11-25: Flag to skip updated() hook during data loading
    public array $originalFormData = [];   // Backup of original form data for reset functionality
    public array $shopsToRemove = [];      // Shop IDs pending removal (deleted on save)
    public array $removedShopsCache = [];   // Cache of removed shop data (for undo/re-add)

    // === SERVICE INSTANCES ===
    // Services temporarily disabled for debugging

    // === PRESTASHOP CATEGORIES (ETAP_07 FAZA 2B.2) ===
    public array $prestashopCategories = []; // Cached PrestaShop categories per shop [shopId => tree]

    // === PRESTASHOP LAZY LOADING (ETAP_07 FIX) ===
    public array $loadedShopData = []; // Cache loaded shop data from PrestaShop [shopId => {...data}]
    public bool $isLoadingShopData = false; // Loading state indicator

    // === JOB MONITORING (ETAP_13 - 2025-11-17) ===
    /**
     * Active job ID for real-time monitoring via wire:poll
     * NULL when no job is active
     *
     * @var int|null
     */
    public ?int $activeJobId = null;

    /**
     * Current status of active job
     * Values: 'pending'|'processing'|'completed'|'failed'|null
     *
     * @var string|null
     */
    public ?string $activeJobStatus = null;

    /**
     * Type of active job (direction indicator)
     * Values: 'sync' (PPM → PS) | 'pull' (PS → PPM) | null
     *
     * @var string|null
     */
    public ?string $activeJobType = null;

    /**
     * ISO8601 timestamp when job was created
     * Used by Alpine.js for countdown animation (0-60s)
     *
     * @var string|null
     */
    public ?string $jobCreatedAt = null;

    /**
     * Shop ID for single-shop sync job tracking
     * FIX 2025-11-27: Track which shop sync job was dispatched for
     * Used by checkBulkSyncJobStatus() to check only the relevant shop
     *
     * @var int|null
     */
    public ?int $syncJobShopId = null;

    /**
     * Final result of job after completion
     * Values: 'success'|'error'|null
     *
     * @var string|null
     */
    public ?string $jobResult = null;

    // === CATEGORY VALIDATION (ETAP_07b FAZA 2) ===
    /**
     * Category validation status per shop
     *
     * Format: [shopId => ['status' => string, 'diff' => array, 'badge' => array, 'tooltip' => string]]
     *
     * @var array
     */
    public array $categoryValidationStatus = [];

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

            // PROBLEM #4 (2025-11-07): Load PriceGroups & Warehouses FIRST (before loadProductData)
            // This MUST be done before loadProductData() because loadProductData() calls
            // loadProductPrices() and loadProductStock() which need these arrays populated!
            $this->loadPriceGroupsAndWarehouses();

            // Initialize basic mode and product
            if ($product && $product->exists) {
                $this->product = $product;
                $this->isEditMode = true;
                $this->loadProductData();
                Log::info('Edit mode activated');

                // FAZA 5.2: Load shop tax rate overrides in edit mode
                $this->loadShopTaxRateOverrides();

                // FIX 2025-11-25: Detect active sync job on mount (for re-entry during sync)
                // If user re-enters product while sync is running, restore job tracking state
                $this->detectActiveJobOnMount();
            } else {
                $this->isEditMode = false;
                $this->setDefaults();
                Log::info('Create mode activated');
            }

            // FAZA 5.2: Initialize tax rate properties
            $this->selectedTaxRateOption = $this->selectedTaxRateOption ?? 'use_default';
            $this->customTaxRate = $this->customTaxRate ?? null;
            $this->shopTaxRateOverrides = $this->shopTaxRateOverrides ?? [];
            $this->availableTaxRuleGroups = $this->availableTaxRuleGroups ?? [];
            $this->taxRuleGroupsCacheTimestamp = $this->taxRuleGroupsCacheTimestamp ?? [];

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

            // FIX 2025-11-21 v5: Calculate expanded category IDs after mount
            $this->expandedCategoryIds = $this->calculateExpandedCategoryIds();

            // ETAP_07e FAZA 3: Load product features
            $this->loadProductFeatures();

            // ETAP_05b FAZA 5: Load default variants snapshot for per-shop isolation
            if ($this->isEditMode) {
                $this->loadDefaultVariantsSnapshot();
            }

            // ETAP_05d FAZA 4: Load vehicle compatibility data
            if ($this->isEditMode) {
                $this->loadCompatibilityData();
            }

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

    /**
     * Livewire hydrate hook - restore state from session before each request
     *
     * FIX 2025-12-04: Pending variants were lost between Livewire requests because
     * Livewire resets public properties. We use session storage to persist them.
     *
     * NOTE: hydrateVariantCrudTrait() doesn't work for nested traits, so we call
     * restorePendingVariantsFromSession() directly here in the main component.
     */
    public function hydrate(): void
    {
        // Restore pending variant changes from session
        $this->restorePendingVariantsFromSession();
    }

    /*
    |--------------------------------------------------------------------------
    | TAX RATE MANAGEMENT (FAZA 5.2 - 2025-11-14)
    |--------------------------------------------------------------------------
    */

    /**
     * Load tax rate overrides from product_shop_data for all shops
     *
     * Called during mount() in edit mode
     *
     * @return void
     */
    protected function loadShopTaxRateOverrides(): void
    {
        if (!$this->product) {
            return;
        }

        foreach ($this->product->shopData as $shopData) {
            $shopId = $shopData->shop_id;

            // Load tax rate override
            $this->shopTaxRateOverrides[$shopId] = $shopData->tax_rate_override;

            // ✅ FIX: Load PrestaShop tax rules for this shop
            $this->loadTaxRuleGroupsForShop($shopId);

            // [FAZA 5.2 DEBUG 2025-11-14] Check if loadTaxRuleGroupsForShop is called
            Log::debug('[FAZA 5.2 DEBUG] loadShopTaxRateOverrides - shop iteration', [
                'shop_id' => $shopId,
                'tax_rate_override' => $shopData->tax_rate_override,
                'availableTaxRuleGroups_isset' => isset($this->availableTaxRuleGroups[$shopId]),
                'availableTaxRuleGroups_count' => count($this->availableTaxRuleGroups[$shopId] ?? []),
            ]);
        }

        Log::debug('[ProductForm FAZA 5.2] Loaded shop tax rate overrides', [
            'product_id' => $this->product->id,
            'overrides' => $this->shopTaxRateOverrides,
            'availableTaxRuleGroups_keys' => array_keys($this->availableTaxRuleGroups),
        ]);
    }

    /**
     * Determine selected tax rate option based on override value
     *
     * Matches override value against PrestaShop tax rule groups
     *
     * @param int $shopId
     * @param float $override
     * @return string 'prestashop_XX' or 'custom'
     */
    protected function determineTaxRateOption(int $shopId, float $override): string
    {
        $shop = collect($this->availableShops)->firstWhere('id', $shopId);

        if (!$shop) {
            return 'custom';
        }

        // Match against PrestaShop tax rule mappings
        if ($override === 23.00 && ($shop['tax_rules_group_id_23'] ?? null)) {
            return 'prestashop_23';
        }

        if ($override === 8.00 && ($shop['tax_rules_group_id_8'] ?? null)) {
            return 'prestashop_8';
        }

        if ($override === 5.00 && ($shop['tax_rules_group_id_5'] ?? null)) {
            return 'prestashop_5';
        }

        if ($override === 0.00 && ($shop['tax_rules_group_id_0'] ?? null)) {
            return 'prestashop_0';
        }

        return 'custom';
    }

    /**
     * Load available tax rule groups for shop from PrestaShop
     *
     * Uses TaxRateService with 15min cache
     *
     * @param int $shopId
     * @return void
     */
    public function loadTaxRuleGroupsForShop(int $shopId): void
    {
        // [FAZA 5.2 DEBUG 2025-11-14] Track method calls
        Log::debug('[FAZA 5.2 DEBUG] loadTaxRuleGroupsForShop CALLED', [
            'shop_id' => $shopId,
            'caller' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown',
        ]);

        // Check cache timestamp (15min = 900 seconds)
        $now = time();
        $cacheValid = isset($this->taxRuleGroupsCacheTimestamp[$shopId])
            && ($now - $this->taxRuleGroupsCacheTimestamp[$shopId]) < 900;

        if ($cacheValid && isset($this->availableTaxRuleGroups[$shopId])) {
            Log::debug('[ProductForm FAZA 5.2] Using cached tax rule groups', ['shop_id' => $shopId]);
            return;
        }

        try {
            $shop = collect($this->availableShops)->firstWhere('id', $shopId);

            if (!$shop) {
                Log::warning('[ProductForm FAZA 5.2] Shop not found for tax rule groups', ['shop_id' => $shopId]);
                return;
            }

            // Get PrestaShopShop model instance
            $shopModel = \App\Models\PrestaShopShop::find($shopId);

            if (!$shopModel) {
                Log::warning('[ProductForm FAZA 5.2] PrestaShopShop model not found', ['shop_id' => $shopId]);
                return;
            }

            // Use TaxRateService
            $taxRateService = app(\App\Services\TaxRateService::class);
            $this->availableTaxRuleGroups[$shopId] = $taxRateService->getAvailableTaxRatesForShop($shopModel);
            $this->taxRuleGroupsCacheTimestamp[$shopId] = $now;

            Log::info('[ProductForm FAZA 5.2] Loaded tax rule groups from PrestaShop', [
                'shop_id' => $shopId,
                'groups_count' => count($this->availableTaxRuleGroups[$shopId]),
            ]);

        } catch (\Exception $e) {
            Log::error('[ProductForm FAZA 5.2] Failed to load tax rule groups', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);

            // Fallback: Empty array
            $this->availableTaxRuleGroups[$shopId] = [];
        }
    }

    /**
     * Livewire reactive update: selectedTaxRateOption changed
     *
     * When user selects dropdown option, update tax_rate or shopTaxRateOverrides
     *
     * @param string $value
     * @return void
     */
    public function updatedSelectedTaxRateOption(string $value): void
    {
        // FIX 2025-11-25: Skip during data loading (prevents false hasUnsavedChanges after job completion)
        if ($this->isLoadingData) {
            return;
        }

        // [FAZA 5.2 DEBUG SAVE 2025-11-14] Layer 1: UI Binding Successful
        Log::debug('[FAZA 5.2 DEBUG SAVE] Tax rate option changed', [
            'new_value' => $value,
            'active_shop_id' => $this->activeShopId,
            'current_shopTaxRateOverrides' => $this->shopTaxRateOverrides,
        ]);

        if ($this->activeShopId === null) {
            // Default mode - update products.tax_rate
            Log::debug('[FAZA 5.2 DEBUG SAVE] Entering DEFAULT mode branch', [
                'activeShopId' => $this->activeShopId,
            ]);
            $this->updateDefaultTaxRate($value);
        } else {
            // Shop mode - update shopTaxRateOverrides
            Log::debug('[FAZA 5.2 DEBUG SAVE] Entering SHOP mode branch', [
                'activeShopId' => $this->activeShopId,
            ]);
            $this->updateShopTaxRateOverride($this->activeShopId, $value);
        }

        // FAZA 5.2 FIX: Force Livewire to re-render tax rate indicator & field class
        // getTaxRateIndicator() and getTaxRateFieldClass() are NOT Livewire computed properties
        // so they don't auto-update when $shopTaxRateOverrides changes
        $this->dispatch('$refresh');

        Log::debug('[FAZA 5.2 REACTIVITY] Dispatched $refresh after tax rate change', [
            'active_shop_id' => $this->activeShopId,
            'new_value' => $value,
        ]);
    }

    /**
     * Update default tax rate (products.tax_rate)
     *
     * @param string $option Selected option ('prestashop_XX' or 'custom')
     * @return void
     */
    protected function updateDefaultTaxRate(string $option): void
    {
        switch ($option) {
            case 'custom':
                // Keep current tax_rate or customTaxRate
                $this->customTaxRate = $this->tax_rate;
                break;

            default:
                // Numeric tax rate value (e.g., "0", "5.00", "8.00", "23.00")
                // Blade sends: <option value="{{ $taxRule['rate'] }}">
                if (is_numeric($option)) {
                    $this->tax_rate = (float) $option;
                    $this->customTaxRate = null;
                } else {
                    Log::warning('[ProductForm FAZA 5.2] Invalid tax rate option', ['option' => $option]);
                }
        }

        Log::debug('[ProductForm FAZA 5.2] Updated default tax rate', [
            'option' => $option,
            'tax_rate' => $this->tax_rate,
        ]);
    }

    /**
     * Update shop-specific tax rate override
     *
     * @param int $shopId
     * @param string $option Selected option ('use_default', 'prestashop_XX', 'custom')
     * @return void
     */
    protected function updateShopTaxRateOverride(int $shopId, string $option): void
    {
        // [FAZA 5.2 DEBUG SAVE 2025-11-14] Layer 2: Reactive Hook Triggered
        Log::debug('[FAZA 5.2 DEBUG SAVE] BEFORE updateShopTaxRateOverride', [
            'shop_id' => $shopId,
            'option' => $option,
            'current_override' => $this->shopTaxRateOverrides[$shopId] ?? 'NOT SET',
            'all_overrides' => $this->shopTaxRateOverrides,
        ]);

        switch ($option) {
            case 'use_default':
                // Clear override - use product default
                $this->shopTaxRateOverrides[$shopId] = null;
                $this->customTaxRate = null;
                break;

            case 'custom':
                // Keep current customTaxRate or shopTaxRateOverrides value
                if (!isset($this->shopTaxRateOverrides[$shopId])) {
                    $this->shopTaxRateOverrides[$shopId] = $this->tax_rate; // Fallback to default
                }
                $this->customTaxRate = $this->shopTaxRateOverrides[$shopId];
                break;

            default:
                // Numeric tax rate value (e.g., "0", "5.00", "8.00", "23.00")
                // Blade sends: <option value="{{ $taxRule['rate'] }}">
                if (is_numeric($option)) {
                    $this->shopTaxRateOverrides[$shopId] = (float) $option;
                    $this->customTaxRate = null;
                } else {
                    Log::warning('[ProductForm FAZA 5.2] Invalid shop tax rate option', ['option' => $option, 'shop_id' => $shopId]);
                }
        }

        // [FAZA 5.2 DEBUG SAVE 2025-11-14] Layer 3: Property Updated
        Log::debug('[FAZA 5.2 DEBUG SAVE] AFTER updateShopTaxRateOverride', [
            'shop_id' => $shopId,
            'option' => $option,
            'new_override' => $this->shopTaxRateOverrides[$shopId] ?? 'NULL',
            'all_overrides' => $this->shopTaxRateOverrides,
        ]);
    }

    /**
     * Livewire reactive update: customTaxRate changed
     *
     * When user enters custom value, update tax_rate or shopTaxRateOverrides
     *
     * @param float|null $value
     * @return void
     */
    public function updatedCustomTaxRate(?float $value): void
    {
        // FIX 2025-11-25: Skip during data loading
        if ($this->isLoadingData || $value === null) {
            return;
        }

        if ($this->activeShopId === null) {
            // Default mode
            $this->tax_rate = $value;
        } else {
            // Shop mode
            $this->shopTaxRateOverrides[$this->activeShopId] = $value;
        }

        Log::debug('[ProductForm FAZA 5.2] Custom tax rate updated', [
            'value' => $value,
            'active_shop_id' => $this->activeShopId,
        ]);
    }

    /**
     * Get tax rate indicator for shop (green/yellow/red badge)
     *
     * Integrates with existing getFieldStatusIndicator() system
     *
     * @param int|null $shopId
     * @return array ['show' => bool, 'class' => string, 'text' => string]
     */
    public function getTaxRateIndicator(?int $shopId = null): array
    {
        if ($shopId === null) {
            // Default mode - no indicator
            return ['show' => false, 'class' => '', 'text' => ''];
        }

        // Check if shop data has pending sync
        $shopData = $this->product?->shopData?->where('shop_id', $shopId)->first();

        if ($shopData && $shopData->sync_status === 'pending') {
            // Pending sync - use standard .pending-sync-badge class (consistent with getFieldStatusIndicator)
            return [
                'show' => true,
                'class' => 'pending-sync-badge',
                'text' => 'OCZEKUJE NA SYNCHRONIZACJĘ',
            ];
        }

        // FAZA 5.2 FIX: Use current form state ($shopTaxRateOverrides), NOT database ($shopData)
        // This allows real-time indicator updates when user changes dropdown (before save)
        // CRITICAL: Cast to float for strict type comparison (int 23 !== float 23.0 in in_array!)
        $effectiveTaxRate = (float) ($this->shopTaxRateOverrides[$shopId] ?? $this->tax_rate);

        // Check if using default (inherited)
        $isInherited = !isset($this->shopTaxRateOverrides[$shopId]) || $this->shopTaxRateOverrides[$shopId] === null;

        if ($isInherited) {
            // Using default tax rate from PPM - show green indicator (matches .status-label-inherited)
            return [
                'show' => true,
                'class' => 'status-label-inherited',
                'text' => 'DZIEDZICZONE',
            ];
        }

        // Shop-specific override (not inherited) - check if mapped to PrestaShop
        $shop = \App\Models\PrestaShopShop::find($shopId);
        if (!$shop) {
            return ['show' => false, 'class' => '', 'text' => ''];
        }

        // Get available tax rules for this shop
        $availableRates = [];
        if ($shop->tax_rules_group_id_23) $availableRates[] = 23.00;
        if ($shop->tax_rules_group_id_8) $availableRates[] = 8.00;
        if ($shop->tax_rules_group_id_5) $availableRates[] = 5.00;
        if ($shop->tax_rules_group_id_0) $availableRates[] = 0.00;

        // FAZA 5.2 FIX: Custom override (not "use_default")
        // Check if rate is mapped to PrestaShop
        if (in_array($effectiveTaxRate, $availableRates, true)) {
            // Custom rate that exists in PrestaShop → WŁASNE (orange, matches .status-label-different)
            Log::debug('[FAZA 5.2 INDICATOR] Custom override mapped to PrestaShop', [
                'shop_id' => $shopId,
                'effective_rate' => $effectiveTaxRate,
                'available_rates' => $availableRates,
            ]);

            return [
                'show' => true,
                'class' => 'status-label-different',
                'text' => 'WŁASNE',
            ];
        }

        // Custom rate NOT mapped to PrestaShop → NIE ZMAPOWANE (use standard class)
        Log::warning('[FAZA 5.2 INDICATOR] Custom override NOT mapped to PrestaShop', [
            'shop_id' => $shopId,
            'effective_rate' => $effectiveTaxRate,
            'available_rates' => $availableRates,
        ]);

        return [
            'show' => true,
            'class' => 'status-label-unmapped',
            'text' => 'NIE ZMAPOWANE W PRESTASHOP',
        ];
    }

    /**
     * Get dynamic CSS class for tax rate field based on validation indicator
     *
     * Returns color-coded border class:
     * - Green: tax rate matches PrestaShop mapping (zgodne z default)
     * - Yellow: tax rate unmapped or override (custom rate)
     * - Empty: default mode (no dynamic color)
     *
     * FAZA 5.2 UI Fix - Dynamic Dropdown Color (2025-11-14)
     *
     * @return string CSS class string for <select> element
     */
    public function getTaxRateFieldClass(): string
    {
        // Base classes consistent with getFieldClasses()
        $baseClasses = 'block w-full rounded-md shadow-sm focus:ring-orange-500 sm:text-sm transition-all duration-200';

        // Default mode - standard styling (no shop selected)
        if (!$this->activeShopId) {
            return $baseClasses . ' border-gray-600 bg-gray-700 text-white focus:border-orange-500';
        }

        $indicator = $this->getTaxRateIndicator($this->activeShopId);

        // No indicator - standard styling
        if (!$indicator['show']) {
            return $baseClasses . ' border-gray-600 bg-gray-700 text-white focus:border-orange-500';
        }

        // Map indicator badge class to field CSS class (consistent with other form fields)
        // This ensures label color matches input field color!
        if (str_contains($indicator['class'], 'pending-sync-badge')) {
            // Pending sync - orange border (defined in product-form.css)
            return $baseClasses . ' field-pending-sync';
        }

        if (str_contains($indicator['class'], 'status-label-inherited')) {
            // Inherited - green border (defined in product-form.css)
            return $baseClasses . ' field-status-inherited';
        }

        if (str_contains($indicator['class'], 'status-label-same')) {
            // Same as default - green border (defined in product-form.css)
            return $baseClasses . ' field-status-same';
        }

        if (str_contains($indicator['class'], 'status-label-different')) {
            // Custom override - orange border (defined in product-form.css)
            return $baseClasses . ' field-status-different';
        }

        // Fallback - standard styling
        return $baseClasses . ' border-gray-600 bg-gray-700 text-white focus:border-orange-500';
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
     * Load PriceGroups & Warehouses for Prices/Stock tabs
     *
     * PROBLEM #4 (2025-11-07): Initialize price groups and warehouses
     * for the Prices and Stock tabs UI
     */
    private function loadPriceGroupsAndWarehouses(): void
    {
        try {
            // Load all active price groups (8 groups)
            $priceGroupsCollection = PriceGroup::where('is_active', true)
                                              ->orderBy('sort_order', 'asc')
                                              ->get();

            $this->priceGroups = $priceGroupsCollection->keyBy('id')->toArray();

            // Load all active warehouses (6 warehouses)
            $warehousesCollection = Warehouse::where('is_active', true)
                                            ->orderBy('sort_order', 'asc')
                                            ->get();

            $this->warehouses = $warehousesCollection->keyBy('id')->toArray();

            // Initialize prices array structure
            foreach ($this->priceGroups as $groupId => $group) {
                $this->prices[$groupId] = [
                    'net' => null,
                    'gross' => null,
                    'margin' => null,
                    'is_active' => true,
                ];
            }

            // Initialize stock array structure
            foreach ($this->warehouses as $warehouseId => $warehouse) {
                $this->stock[$warehouseId] = [
                    'quantity' => 0,
                    'reserved' => 0,
                    'minimum' => 0,
                ];
            }

            Log::info('Price groups and warehouses loaded', [
                'price_groups_count' => count($this->priceGroups),
                'warehouses_count' => count($this->warehouses),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load price groups and warehouses', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Initialize empty arrays on error
            $this->priceGroups = [];
            $this->warehouses = [];
            $this->prices = [];
            $this->stock = [];
        }
    }

    /**
     * Load product prices from database
     *
     * PROBLEM #4 (2025-11-07): Task 6 - Load existing prices for this product
     */
    private function loadProductPrices(): void
    {
        try {
            if (!$this->product || !$this->product->exists) {
                Log::debug('loadProductPrices: No product to load prices for (new product)');
                return;
            }

            // Load all prices for this product (no variant filter - master product prices)
            $existingPrices = $this->product->prices()
                                          ->whereNull('product_variant_id')
                                          ->where('is_active', true)
                                          ->get()
                                          ->keyBy('price_group_id');

            // Populate $this->prices array with existing data
            foreach ($this->priceGroups as $groupId => $group) {
                if (isset($existingPrices[$groupId])) {
                    $price = $existingPrices[$groupId];
                    $this->prices[$groupId] = [
                        'net' => $price->price_net,
                        'gross' => $price->price_gross,
                        'margin' => $price->margin_percentage,
                        'is_active' => $price->is_active,
                    ];
                } else {
                    // Keep initialized null values for price groups without data
                    $this->prices[$groupId] = [
                        'net' => null,
                        'gross' => null,
                        'margin' => null,
                        'is_active' => true,
                    ];
                }
            }

            Log::info('Product prices loaded', [
                'product_id' => $this->product->id,
                'loaded_prices_count' => $existingPrices->count(),
                'total_price_groups' => count($this->priceGroups),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load product prices', [
                'product_id' => $this->product?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Load product stock from database
     *
     * PROBLEM #4 (2025-11-07): Task 7 - Load existing stock for this product
     */
    private function loadProductStock(): void
    {
        try {
            if (!$this->product || !$this->product->exists) {
                Log::debug('loadProductStock: No product to load stock for (new product)');
                return;
            }

            // Load all stock for this product (no variant filter - master product stock)
            $existingStock = $this->product->stock()
                                         ->whereNull('product_variant_id')
                                         ->where('is_active', true)
                                         ->get()
                                         ->keyBy('warehouse_id');

            // Populate $this->stock array with existing data
            foreach ($this->warehouses as $warehouseId => $warehouse) {
                if (isset($existingStock[$warehouseId])) {
                    $stock = $existingStock[$warehouseId];
                    $this->stock[$warehouseId] = [
                        'quantity' => $stock->quantity,
                        'reserved' => $stock->reserved_quantity,
                        'minimum' => $stock->minimum_stock_level,
                    ];
                } else {
                    // Keep initialized zero values for warehouses without data
                    $this->stock[$warehouseId] = [
                        'quantity' => 0,
                        'reserved' => 0,
                        'minimum' => 0,
                    ];
                }
            }

            Log::info('Product stock loaded', [
                'product_id' => $this->product->id,
                'loaded_stock_count' => $existingStock->count(),
                'total_warehouses' => count($this->warehouses),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load product stock', [
                'product_id' => $this->product?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
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
        Log::debug('loadProductData: About to load categories', [
            'product_id' => $this->product->id,
            'categoryManager_exists' => $this->categoryManager !== null,
            'shopCategories_before' => $this->shopCategories,
        ]);

        if ($this->categoryManager) {
            $this->categoryManager->loadCategories();

            Log::debug('loadProductData: Categories loaded', [
                'product_id' => $this->product->id,
                'defaultCategories_after' => $this->defaultCategories,
                'shopCategories_after' => $this->shopCategories,
            ]);
        }

        // PROBLEM #4 (2025-11-07): Load prices from database
        $this->loadProductPrices();

        // PROBLEM #4 (2025-11-07): Load stock from database
        $this->loadProductStock();

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
     * Returns only root categories with their children loaded recursively
     */
    public function getAvailableCategories()
    {
        try {
            // Return ONLY root categories (parent_id = null)
            // Children will be rendered recursively via 'children' relationship in view
            // FIX 2025-11-26: Load children recursively (5 levels) for findCategoryInTree/getAllDescendantCategoryIds
            return \App\Models\Category::with([
                    'children',
                    'children.children',
                    'children.children.children',
                    'children.children.children.children',
                    'children.children.children.children.children'
                ])
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to load categories in getAvailableCategories', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * Get category IDs that should be expanded in tree (have selected children)
     *
     * FIX 2025-11-21: Performance optimization - expand ONLY categories with selected children
     * FIX 2025-11-21 v2: Convert PPM IDs → PrestaShop IDs for shop context (ID mismatch)
     *
     * @return array Category IDs to expand
     */
    // FIX 2025-11-21 v5: Changed from computed property to private method called in switchToShop()
    // Root Cause: Computed properties don't execute from @php blocks
    // Solution: Calculate and store in public property during shop switch
    // FIX 2025-11-22: Changed to public to allow access from Blade template (basic-tab.blade.php)
    public function calculateExpandedCategoryIds(): array
    {
        // Get selected categories for current context (default or shop)
        // Note: These are PPM IDs (stored in $this->shopCategories)
        $selectedCategories = $this->activeShopId === null
            ? ($this->defaultCategories['selected'] ?? [])
            : ($this->shopCategories[$this->activeShopId]['selected'] ?? []);

        if (empty($selectedCategories)) {
            return []; // No selected categories = no expansion needed
        }

        // FIX 2025-11-28: Cache key based on context and selected categories
        $contextKey = $this->activeShopId === null ? 'default' : "shop_{$this->activeShopId}";
        sort($selectedCategories); // Ensure consistent order for cache key
        $cacheKey = $contextKey . '_' . implode(',', $selectedCategories);

        // Return cached result if available (avoid expensive database queries)
        if (isset($this->expandedCategoryIdsCache[$cacheKey])) {
            Log::debug('[calculateExpandedCategoryIds] CACHE HIT', [
                'cache_key' => $cacheKey,
                'cached_count' => count($this->expandedCategoryIdsCache[$cacheKey]),
            ]);
            return $this->expandedCategoryIdsCache[$cacheKey];
        }

        // FIX 2025-11-21 v5: Info logging (production LOG_LEVEL=info, debug disabled)
        Log::info('[calculateExpandedCategoryIds] START', [
            'active_shop_id' => $this->activeShopId,
            'selected_categories' => $selectedCategories,
            'selected_count' => count($selectedCategories),
        ]);

        // Build list of categories to expand (parents of selected categories)
        $expandedIds = [];

        foreach ($selectedCategories as $selectedId) {
            // Get category with parent chain (using PPM ID)
            $category = \App\Models\Category::find($selectedId);

            if ($category) {
                // FIX 2025-11-24: Add selected category itself if it has children
                $hasChildren = $category->children()->count() > 0;
                if ($hasChildren) {
                    $expandedIds[] = $selectedId; // Add selected category to expand its children
                }

                // Add all parents to expanded list (recursive)
                $parentIds = [];
                $parent = $category->parent;
                while ($parent) {
                    $expandedIds[] = $parent->id; // PPM ID
                    $parentIds[] = $parent->id;
                    $parent = $parent->parent;
                }

                // FIX 2025-11-21 v5: Info logging (production LOG_LEVEL=info)
                Log::info('[calculateExpandedCategoryIds] Category parents', [
                    'selected_id' => $selectedId,
                    'category_name' => $category->name,
                    'has_children' => $hasChildren,
                    'parent_ids' => $parentIds,
                    'parent_count' => count($parentIds),
                ]);
            } else {
                Log::warning('[calculateExpandedCategoryIds] Category not found', [
                    'selected_id' => $selectedId,
                ]);
            }
        }

        $expandedIds = array_unique($expandedIds);

        // For shop context: convert PPM IDs → PrestaShop IDs
        // For default context: keep PPM IDs as-is
        if ($this->activeShopId !== null) {
            // Shop context - convert PPM → PrestaShop
            $mappings = $this->shopCategories[$this->activeShopId]['mappings'] ?? [];

            // Lazy load mappings if missing
            if (empty($mappings)) {
                $productShopData = \App\Models\ProductShopData::where('product_id', $this->product->id)
                    ->where('shop_id', $this->activeShopId)
                    ->first();

                if ($productShopData && !empty($productShopData->category_mappings)) {
                    $categoryMappings = $productShopData->category_mappings;
                    $mappings = $categoryMappings['mappings'] ?? [];
                    $this->shopCategories[$this->activeShopId]['mappings'] = $mappings;
                }
            }

            // Convert PPM IDs to PrestaShop IDs
            $prestashopExpandedIds = [];
            foreach ($expandedIds as $ppmId) {
                $mappingKey = (string) $ppmId;
                if (isset($mappings[$mappingKey])) {
                    $prestashopExpandedIds[] = (int) $mappings[$mappingKey];
                }
            }

            Log::info('[calculateExpandedCategoryIds] RESULT (Shop context - converted to PrestaShop IDs)', [
                'active_shop_id' => $this->activeShopId,
                'ppm_expanded_ids' => $expandedIds,
                'prestashop_expanded_ids' => $prestashopExpandedIds,
                'conversion_count' => count($prestashopExpandedIds),
            ]);

            // FIX 2025-11-28: Store result in cache
            $this->expandedCategoryIdsCache[$cacheKey] = $prestashopExpandedIds;

            return $prestashopExpandedIds;
        }

        // Default context - return PPM IDs
        Log::info('[calculateExpandedCategoryIds] RESULT (Default context - PPM IDs)', [
            'ppm_expanded_ids' => $expandedIds,
            'ppm_count' => count($expandedIds),
        ]);

        // FIX 2025-11-28: Store result in cache
        $this->expandedCategoryIdsCache[$cacheKey] = $expandedIds;

        return $expandedIds;
    }

    /**
     * FIX 2025-11-28: Calculate parents for a SINGLE category (optimized for toggle)
     * This is much faster than calculateExpandedCategoryIds() which processes ALL categories
     *
     * @param int $ppmCategoryId PPM Category ID
     * @return array Parent IDs (PrestaShop IDs in shop context, PPM IDs in default context)
     */
    protected function calculateParentsForCategory(int $ppmCategoryId): array
    {
        $category = \App\Models\Category::find($ppmCategoryId);

        if (!$category) {
            return [];
        }

        $parentPpmIds = [];

        // Traverse up the parent chain
        $parent = $category->parent;
        while ($parent) {
            $parentPpmIds[] = $parent->id;
            $parent = $parent->parent;
        }

        // For shop context: convert PPM IDs → PrestaShop IDs
        if ($this->activeShopId !== null) {
            $mappings = $this->shopCategories[$this->activeShopId]['mappings'] ?? [];

            $prestashopIds = [];
            foreach ($parentPpmIds as $ppmId) {
                $mappingKey = (string) $ppmId;
                if (isset($mappings[$mappingKey])) {
                    $prestashopIds[] = (int) $mappings[$mappingKey];
                }
            }

            return $prestashopIds;
        }

        // Default context - return PPM IDs
        return $parentPpmIds;
    }

    /*
    |--------------------------------------------------------------------------
    | UI INTERACTION METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Switch between tabs
     *
     * ETAP_08.4 FIX: Reset ERP context when switching to non-ERP tabs
     * to prevent data leakage from ERP-specific values to default product.
     */
    public function switchTab(string $tab): void
    {
        // ETAP_08.4 FIX: If we were in ERP context and switching away from 'integrations' tab,
        // reset ERP context and restore default PPM data to form fields
        $wasInErpContext = $this->activeErpConnectionId !== null;
        $leavingIntegrationsTab = $this->activeTab !== $tab && $tab !== 'integrations';

        if ($wasInErpContext && $leavingIntegrationsTab) {
            Log::info('[ETAP_08.4 FIX] Leaving ERP context - restoring PPM defaults', [
                'from_tab' => $this->activeTab,
                'to_tab' => $tab,
                'erp_connection_id' => $this->activeErpConnectionId,
            ]);

            // Reset ERP context
            $this->activeErpConnectionId = null;
            $this->activeErpTab = 'all';
            $this->erpExternalData = [];
            $this->erpDefaultData = [];

            // Restore PPM default data to form fields
            $this->loadDefaultDataToForm();
        }

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

        // Invalidate category validation cache for current shop
        $this->invalidateCategoryValidationCache();

        // REMOVED 2025-11-24: dispatch('categories-updated') - dead code (no listeners)
        // REMOVED 2025-11-24: updateCategoryColorCoding() - dead code causing race conditions
        // Livewire automatically re-renders on property changes, no manual dispatch needed
    }

    // REMOVED 2025-11-24: updateCategoryColorCoding() method - dead code causing race conditions
    // dispatch('category-status-changed') had no listeners and was triggering unnecessary re-renders
    // Livewire automatically re-renders on property changes, manual dispatch not needed

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
     * Check if current shop has category conflict that needs resolution
     * ADDED 2025-10-13: Per-Shop Categories Conflict Detection
     */
    public function getHasCategoryConflictProperty(): bool
    {
        // Only in shop mode (not default)
        if (!$this->activeShopId || !$this->product || !$this->product->exists) {
            return false;
        }

        $shopData = \App\Models\ProductShopData::where('product_id', $this->product->id)
            ->where('shop_id', $this->activeShopId)
            ->first();

        $hasConflict = $shopData && !empty($shopData->conflict_data) && isset($shopData->conflict_data['type']);

        if ($hasConflict) {
            Log::debug('Category conflict detected', [
                'product_id' => $this->product->id,
                'shop_id' => $this->activeShopId,
                'conflict_type' => $shopData->conflict_data['type'] ?? 'unknown',
            ]);
        }

        return $hasConflict;
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
     * ETAP_07b FAZA 1: Get PrestaShop category IDs for shop context
     *
     * Converts PPM category IDs to PrestaShop IDs using mappings object.
     * This is critical for rendering checkboxes in shop TAB - we need to compare
     * PrestaShop IDs (from API tree) with PrestaShop IDs (from mappings).
     *
     * For default context, returns PPM IDs as-is.
     *
     * @param int|null $contextShopId Shop ID or null for default
     * @return array PrestaShop category IDs for shop context, PPM IDs for default
     */
    public function getPrestaShopCategoryIdsForContext(?int $contextShopId = null): array
    {
        if ($contextShopId === null) {
            // Default context - return PPM IDs as-is
            return $this->defaultCategories['selected'] ?? [];
        }

        // Shop-specific context - convert PPM IDs to PrestaShop IDs
        $ppmIds = $this->shopCategories[$contextShopId]['selected'] ?? [];
        $mappings = $this->shopCategories[$contextShopId]['mappings'] ?? [];

        // ETAP_07b FIX: Lazy load mappings if missing (Livewire hydration issue)
        // When Livewire hydrates old state, 'mappings' key doesn't exist
        // We need to reload it from database
        if (empty($mappings) && !empty($ppmIds)) {
            Log::info('[ETAP_07b] Lazy loading mappings (hydration issue)', [
                'shop_id' => $contextShopId,
                'ppm_ids' => $ppmIds,
            ]);

            // Reload category_mappings from database
            $productShopData = \App\Models\ProductShopData::where('product_id', $this->product->id)
                ->where('shop_id', $contextShopId)
                ->first();

            if ($productShopData && !empty($productShopData->category_mappings)) {
                $categoryMappings = $productShopData->category_mappings;
                $mappings = $categoryMappings['mappings'] ?? [];

                // Update shopCategories with loaded mappings
                $this->shopCategories[$contextShopId]['mappings'] = $mappings;

                Log::info('[ETAP_07b] Mappings lazy loaded successfully', [
                    'shop_id' => $contextShopId,
                    'mappings_count' => count($mappings),
                ]);
            }
        }

        if (empty($mappings)) {
            Log::warning('[ETAP_07b] No mappings available after lazy load', [
                'shop_id' => $contextShopId,
                'ppm_ids' => $ppmIds,
            ]);
            return []; // No mappings = can't show any checkboxes
        }

        $prestashopIds = [];
        foreach ($ppmIds as $ppmId) {
            // Mappings format: {"2": 2, "3": 12, "4": 23}
            // Keys are PPM IDs (as strings), values are PrestaShop IDs
            $mappingKey = (string) $ppmId;
            if (isset($mappings[$mappingKey])) {
                $prestashopIds[] = (int) $mappings[$mappingKey];
            } else {
                Log::debug('[ETAP_07b] PPM category has no PrestaShop mapping', [
                    'shop_id' => $contextShopId,
                    'ppm_id' => $ppmId,
                ]);
            }
        }

        Log::debug('[ETAP_07b] Converted PPM IDs to PrestaShop IDs', [
            'shop_id' => $contextShopId,
            'ppm_ids' => $ppmIds,
            'prestashop_ids' => $prestashopIds,
            'mappings_used' => $mappings,
        ]);

        return $prestashopIds;
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
     * ETAP_07b FAZA 1: Get primary PrestaShop category ID for shop context
     *
     * Converts PPM primary category ID to PrestaShop ID.
     * For default context, returns PPM ID as-is.
     *
     * @param int|null $contextShopId Shop ID or null for default
     * @return int|null PrestaShop category ID for shop context, PPM ID for default
     */
    public function getPrimaryPrestaShopCategoryIdForContext(?int $contextShopId = null): ?int
    {
        if ($contextShopId === null) {
            // Default context - return PPM ID
            return $this->defaultCategories['primary'] ?? null;
        }

        // Shop-specific context - convert PPM ID to PrestaShop ID
        $ppmPrimaryId = $this->shopCategories[$contextShopId]['primary'] ?? null;

        if ($ppmPrimaryId === null) {
            return null;
        }

        // Get mappings (with lazy loading if needed)
        $mappings = $this->shopCategories[$contextShopId]['mappings'] ?? [];

        // Lazy load mappings if missing (same logic as getPrestaShopCategoryIdsForContext)
        if (empty($mappings)) {
            $productShopData = \App\Models\ProductShopData::where('product_id', $this->product->id)
                ->where('shop_id', $contextShopId)
                ->first();

            if ($productShopData && !empty($productShopData->category_mappings)) {
                $categoryMappings = $productShopData->category_mappings;
                $mappings = $categoryMappings['mappings'] ?? [];
                $this->shopCategories[$contextShopId]['mappings'] = $mappings;
            }
        }

        // Convert PPM ID to PrestaShop ID
        $mappingKey = (string) $ppmPrimaryId;
        return isset($mappings[$mappingKey]) ? (int) $mappings[$mappingKey] : null;
    }

    /**
     * FIX 2025-11-21: Convert PrestaShop category ID → PPM category ID
     *
     * Uses reverse lookup in mappings to convert PrestaShop ID back to PPM ID.
     * This is necessary because Blade passes PrestaShop IDs (from category tree),
     * but shopCategories['selected'] stores PPM IDs.
     *
     * @param int $prestashopId PrestaShop category ID
     * @param int $shopId Shop context
     * @return int|null PPM category ID or null if not found
     */
    private function convertPrestaShopIdToPpmId(int $prestashopId, int $shopId): ?int
    {
        // Get mappings (with lazy loading if needed)
        $mappings = $this->shopCategories[$shopId]['mappings'] ?? [];

        // Lazy load mappings if missing
        if (empty($mappings)) {
            $productShopData = \App\Models\ProductShopData::where('product_id', $this->product->id)
                ->where('shop_id', $shopId)
                ->first();

            if ($productShopData && !empty($productShopData->category_mappings)) {
                $categoryMappings = $productShopData->category_mappings;
                $mappings = $categoryMappings['mappings'] ?? [];
                $this->shopCategories[$shopId]['mappings'] = $mappings;
            }
        }

        // Reverse lookup: PrestaShop ID → PPM ID
        // Mappings format: {"3": 12, "12": 11, ...} (PPM ID => PrestaShop ID)
        // We need to find key where value == $prestashopId
        foreach ($mappings as $ppmId => $psId) {
            if ((int) $psId === $prestashopId) {
                return (int) $ppmId;
            }
        }

        return null; // Not found
    }

    /**
     * Toggle category selection with proper context isolation
     */
    public function toggleCategory(int $categoryId): void
    {
        // FIX 2025-11-21: Convert PrestaShop ID → PPM ID for shop context
        // Root Cause: Blade passes PrestaShop category ID (from category tree), but
        // shopCategories['selected'] stores PPM IDs. Without conversion, we mix ID types.
        $ppmCategoryId = $categoryId; // Default: assume PPM ID (for default context)

        if ($this->activeShopId !== null) {
            // Shop context: categoryId is PrestaShop ID → convert to PPM ID
            // FIX #2 2025-11-21: Use mapOrCreate instead of returning null for unmapped categories
            $ppmCategoryId = $this->convertPrestaShopIdToPpmId($categoryId, $this->activeShopId);

            if ($ppmCategoryId === null) {
                // Category not yet mapped - create mapping via CategoryMapper
                // FIX 2025-11-21 v2: Reduce logging for better responsiveness (info → debug)
                Log::debug('[toggleCategory] Category not mapped, creating via mapOrCreate', [
                    'prestashop_id' => $categoryId,
                    'shop_id' => $this->activeShopId,
                ]);

                try {
                    $shop = \App\Models\PrestaShopShop::find($this->activeShopId);
                    if (!$shop) {
                        throw new \Exception("Shop {$this->activeShopId} not found");
                    }

                    $categoryMapper = app(\App\Services\PrestaShop\CategoryMapper::class);
                    $ppmCategoryId = $categoryMapper->mapOrCreateFromPrestaShop($categoryId, $shop);

                    // Get PS ID for this newly mapped category
                    $prestashopId = $categoryMapper->mapToPrestaShop($ppmCategoryId, $shop);

                    // Update shopCategories with new mapping
                    if (!isset($this->shopCategories[$this->activeShopId])) {
                        $this->shopCategories[$this->activeShopId] = ['selected' => [], 'primary' => null, 'mappings' => []];
                    }

                    $this->shopCategories[$this->activeShopId]['mappings'][(string)$ppmCategoryId] = $prestashopId;

                    // FIX 2025-11-21 v2: Reduce logging (info → debug)
                    Log::debug('[toggleCategory] Category mapped successfully', [
                        'ps_id' => $categoryId,
                        'ppm_id' => $ppmCategoryId,
                    ]);
                } catch (\Exception $e) {
                    Log::error('[toggleCategory] Failed to map category via mapOrCreate', [
                        'prestashop_id' => $categoryId,
                        'shop_id' => $this->activeShopId,
                        'error' => $e->getMessage(),
                    ]);

                    $this->addError('categories', 'Nie udało się dodać kategorii: ' . $e->getMessage());
                    return; // Cannot toggle unmapped category
                }
            }
        }

        $currentCategories = $this->getCurrentContextCategories();
        $selectedCategories = $currentCategories['selected'] ?? [];
        $primaryCategory = $currentCategories['primary'] ?? null;

        // FIX 2025-11-28: Track if we're adding a new category (for targeted expansion)
        $isAdding = !in_array($ppmCategoryId, $selectedCategories);

        if (!$isAdding) {
            // Remove category
            $selectedCategories = array_values(array_diff($selectedCategories, [$ppmCategoryId]));
            // Remove as primary if it was primary
            if ($primaryCategory === $ppmCategoryId) {
                $primaryCategory = null;
            }
        } else {
            // Add category
            $selectedCategories[] = $ppmCategoryId;
        }

        // Save back to context (isolated per shop/default)
        $this->setCurrentContextCategories([
            'selected' => $selectedCategories,
            'primary' => $primaryCategory,
        ]);

        // Mark form as changed to track in pending changes
        $this->markFormAsChanged();

        // FIX #1 2025-11-21: Badge now uses real-time comparison, no cache invalidation needed
        // REMOVED: $this->invalidateCategoryValidationCache();

        // FIX 2025-11-28: Optimized expansion calculation
        // - When ADDING: Calculate only parents for the NEW category (not all categories)
        // - When REMOVING: Keep tree expanded (no need to collapse)
        if ($isAdding) {
            // Only calculate parents for the newly added category
            $newParentIds = $this->calculateParentsForCategory($ppmCategoryId);
            // Merge with existing expanded IDs (avoid duplicates)
            $this->expandedCategoryIds = array_values(array_unique(
                array_merge($this->expandedCategoryIds, $newParentIds)
            ));
        }
        // When removing, we keep the tree expanded - no recalculation needed

        // FIX 2025-11-21 v2: Reduced logging for better responsiveness (removed verbose log)
    }

    /**
     * Set primary category
     */
    public function setPrimaryCategory(int $categoryId): void
    {
        // FIX 2025-11-21: Convert PrestaShop ID → PPM ID for shop context
        // Root Cause: Same as toggleCategory() - Blade passes PrestaShop ID
        $ppmCategoryId = $categoryId; // Default: assume PPM ID (for default context)

        if ($this->activeShopId !== null) {
            // Shop context: categoryId is PrestaShop ID → convert to PPM ID
            // FIX #2 2025-11-21: Use mapOrCreate instead of returning null for unmapped categories
            $ppmCategoryId = $this->convertPrestaShopIdToPpmId($categoryId, $this->activeShopId);

            if ($ppmCategoryId === null) {
                // Category not yet mapped - create mapping via CategoryMapper
                Log::info('[FIX #2 2025-11-21] setPrimaryCategory: Category not mapped, creating via mapOrCreate', [
                    'prestashop_id' => $categoryId,
                    'shop_id' => $this->activeShopId,
                ]);

                try {
                    $shop = \App\Models\PrestaShopShop::find($this->activeShopId);
                    if (!$shop) {
                        throw new \Exception("Shop {$this->activeShopId} not found");
                    }

                    $categoryMapper = app(\App\Services\PrestaShop\CategoryMapper::class);
                    $ppmCategoryId = $categoryMapper->mapOrCreateFromPrestaShop($categoryId, $shop);

                    // Get PS ID for this newly mapped category
                    $prestashopId = $categoryMapper->mapToPrestaShop($ppmCategoryId, $shop);

                    // Update shopCategories with new mapping
                    if (!isset($this->shopCategories[$this->activeShopId])) {
                        $this->shopCategories[$this->activeShopId] = ['selected' => [], 'primary' => null, 'mappings' => []];
                    }

                    $this->shopCategories[$this->activeShopId]['mappings'][(string)$ppmCategoryId] = $prestashopId;

                    Log::info('[FIX #2 2025-11-21] setPrimaryCategory: Category mapped successfully', [
                        'ps_id' => $categoryId,
                        'ppm_id' => $ppmCategoryId,
                        'shop_id' => $this->activeShopId,
                    ]);
                } catch (\Exception $e) {
                    Log::error('[FIX #2 2025-11-21] setPrimaryCategory: Failed to map category via mapOrCreate', [
                        'prestashop_id' => $categoryId,
                        'shop_id' => $this->activeShopId,
                        'error' => $e->getMessage(),
                    ]);

                    $this->addError('categories', 'Nie udało się ustawić głównej kategorii: ' . $e->getMessage());
                    return; // Cannot set unmapped category as primary
                }
            }
        }

        $currentCategories = $this->getCurrentContextCategories();
        $selectedCategories = $currentCategories['selected'] ?? [];

        // FIX 2025-11-28: Track if we're adding a new category (affects cache)
        $categoryWasAdded = false;

        // Ensure the category is selected first
        if (!in_array($ppmCategoryId, $selectedCategories)) {
            $selectedCategories[] = $ppmCategoryId;
            $categoryWasAdded = true;
        }

        // Set as primary
        $this->setCurrentContextCategories([
            'selected' => $selectedCategories,
            'primary' => $ppmCategoryId,
        ]);

        // Mark form as changed to track in pending changes
        $this->markFormAsChanged();

        // FIX #1 2025-11-21: Invalidate category validation cache to update badge
        $this->invalidateCategoryValidationCache();

        // FIX 2025-11-24 (v4): Dispatch event for Alpine.js isPrimary synchronization
        // Prevents conflict between Fix #1 (PHP expression) and Fix #3 (static wire:key)
        // Alpine.js event listener will update isPrimary property across all category buttons
        // FIX 2025-11-25: Send correct ID type based on context:
        // - Shop context: send PrestaShop ID (categories in tree are from PrestaShop)
        // - Default context: send PPM ID (categories in tree are from PPM)
        $eventCategoryId = $ppmCategoryId;
        if ($this->activeShopId !== null) {
            // Shop context - convert PPM ID back to PrestaShop ID for Alpine comparison
            $mappings = $this->shopCategories[$this->activeShopId]['mappings'] ?? [];
            $eventCategoryId = $mappings[(string)$ppmCategoryId] ?? $categoryId;
        }
        // PERFORMANCE FIX 2025-11-27: Use consolidated event (86% listener reduction)
        // FIX 2025-11-28: Add context to ensure only ONE primary badge per context
        // FIX 2025-11-28 v2: Context format must match Blade's $context (shop ID or 'default', NOT 'shop_X')
        // FIX 2025-11-28 v3: REMOVED server-side dispatch - Alpine optimistic UI handles visual update
        // Server still saves correct state, but no dispatch needed (causes race condition with fast clicks)
        // $eventContext = $this->activeShopId === null ? 'default' : (string)$this->activeShopId;
        // $this->dispatch('category-event', type: 'primary-changed', categoryId: $eventCategoryId, context: $eventContext);

        Log::info('Primary category set with context isolation', [
            'received_category_id' => $categoryId,
            'ppm_category_id' => $ppmCategoryId,
            'shop_id' => $this->activeShopId,
            'context' => $this->activeShopId === null ? 'default' : "shop_{$this->activeShopId}",
            'selected_categories' => $selectedCategories,
            'primary_category_id' => $ppmCategoryId,
            'category_was_added' => $categoryWasAdded,
        ]);

        // FIX 2025-11-28: Only expand tree to new category if it was added
        // When just setting primary (no new category), no expansion changes needed
        if ($categoryWasAdded) {
            // Optimized: only calculate parents for the NEW category (not all categories)
            $newParentIds = $this->calculateParentsForCategory($ppmCategoryId);
            $this->expandedCategoryIds = array_values(array_unique(
                array_merge($this->expandedCategoryIds, $newParentIds)
            ));
        }
    }

    /**
     * ETAP_07b FAZA 4.2: Clear all category selections for a context
     *
     * For shop context: clears shop-specific categories (can inherit from default)
     * For default context: clears all default categories
     *
     * @param string $context 'default' or shop_id
     */
    public function clearCategorySelection(string $context): void
    {
        Log::info('[clearCategorySelection] Clearing categories', [
            'context' => $context,
            'current_shop_id' => $this->activeShopId,
        ]);

        if ($context === 'default') {
            // Clear default categories - FIX: use defaultCategories (not selectedCategories!)
            // getPrestaShopCategoryIdsForContext(null) returns defaultCategories['selected']
            $this->defaultCategories['selected'] = [];
            $this->defaultCategories['primary'] = null;

            Log::info('[clearCategorySelection] Cleared default categories');
        } else {
            // Clear shop-specific categories
            $shopId = (int) $context;

            if (isset($this->shopCategories[$shopId])) {
                $this->shopCategories[$shopId]['selected'] = [];
                $this->shopCategories[$shopId]['primary'] = null;

                Log::info('[clearCategorySelection] Cleared shop categories', [
                    'shop_id' => $shopId,
                ]);
            }
        }

        // Mark form as changed
        $this->markFormAsChanged();

        // Invalidate category validation cache
        $this->invalidateCategoryValidationCache();

        // Recalculate expanded categories
        $this->expandedCategoryIds = $this->calculateExpandedCategoryIds();

        // PERFORMANCE FIX 2025-11-27: Use consolidated event (86% listener reduction)
        // FAZA 4.2 FIX: Dispatch browser event to sync Alpine.js local state
        $this->dispatch('category-event', type: 'clear-all', context: $context);
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

        // CRITICAL: Dispatch event IMMEDIATELY for UI refresh (not waiting for save)
        // This ensures ProductList updates even if user doesn't click save
        if ($this->product) {
            $this->dispatch('shops-updated', ['productId' => $this->product->id]);
            Log::info('Dispatched shops-updated event', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
            ]);
        }

        $this->successMessage = "Sklep zostanie usunięty po zapisaniu zmian.";
    }

    /**
     * Physically delete product from PrestaShop (not just remove association)
     *
     * ETAP_07 FAZA 3B: Physical product deletion in PrestaShop shop
     */
    public function deleteFromPrestaShop(int $shopId): void
    {
        if (!$this->product) {
            $this->dispatch('error', message: 'Nie można usunąć - produkt nie istnieje');
            return;
        }

        $shop = PrestaShopShop::find($shopId);
        if (!$shop) {
            $this->dispatch('error', message: 'Nie znaleziono sklepu');
            return;
        }

        // Check if product is associated with this shop
        $productShopData = \App\Models\ProductShopData::where('product_id', $this->product->id)
            ->where('shop_id', $shopId)
            ->first();

        if (!$productShopData) {
            $this->dispatch('warning', message: 'Produkt nie jest powiązany z tym sklepem');
            return;
        }

        // Dispatch delete job to queue
        \App\Jobs\PrestaShop\DeleteProductFromPrestaShop::dispatch($this->product, $shop);

        Log::info('Delete job dispatched for product in shop', [
            'product_id' => $this->product->id,
            'shop_id' => $shopId,
            'prestashop_product_id' => $productShopData->prestashop_product_id ?? 'not_synced',
        ]);

        $this->dispatch('success', message: "Zaplanowano usunięcie produktu ze sklepu: {$shop->name}");

        // Remove from local state immediately (optimistic update)
        $this->removeFromShop($shopId);

        // NOTE: removeFromShop() already dispatches 'shops-updated' event for UI refresh
        // No need for additional loadProductFromDb() call (which doesn't exist anyway)
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

    /*
    |--------------------------------------------------------------------------
    | CREATE CATEGORY MODAL (ETAP_07b FAZA 4.2.3)
    |--------------------------------------------------------------------------
    */

    /**
     * Open create category modal for a specific shop
     *
     * @param array $params Event params containing 'shopId'
     */
    public function openCreateCategoryModal(array $params): void
    {
        $shopId = $params['shopId'] ?? null;

        if ($shopId === null || $shopId === 'default') {
            $this->dispatch('error', message: 'Tworzenie kategorii dostępne tylko dla sklepów PrestaShop');
            return;
        }

        $this->createCategoryShopId = (int) $shopId;
        $this->newCategoryName = '';
        $this->newCategoryParentId = null;
        $this->showCreateCategoryModal = true;
    }

    /**
     * Close create category modal
     */
    public function closeCreateCategoryModal(): void
    {
        $this->showCreateCategoryModal = false;
        $this->createCategoryShopId = null;
        $this->newCategoryName = '';
        $this->newCategoryParentId = null;
    }

    /**
     * Create new category in PrestaShop
     *
     * Uses CategorySyncService to create category via API
     */
    public function createNewCategory(): void
    {
        // Validate input
        $this->validate([
            'newCategoryName' => 'required|string|min:2|max:128',
            'newCategoryParentId' => 'nullable|integer',
        ], [
            'newCategoryName.required' => 'Nazwa kategorii jest wymagana',
            'newCategoryName.min' => 'Nazwa musi mieć minimum 2 znaki',
            'newCategoryName.max' => 'Nazwa może mieć maksymalnie 128 znaków',
        ]);

        try {
            $shop = \App\Models\PrestaShopShop::find($this->createCategoryShopId);

            if (!$shop) {
                throw new \Exception('Nie znaleziono sklepu');
            }

            // Get PrestaShop client
            $client = $shop->getClient();

            // Determine parent category ID in PrestaShop
            $parentPrestashopId = 2; // Default: Home category

            if ($this->newCategoryParentId) {
                // Map PPM parent to PrestaShop ID
                $categoryMapper = app(\App\Services\PrestaShop\CategoryMapper::class);
                $mappedParentId = $categoryMapper->mapToPrestaShop($this->newCategoryParentId, $shop);

                if ($mappedParentId) {
                    $parentPrestashopId = $mappedParentId;
                }
            }

            // Build category data for PrestaShop API
            $categoryData = [
                'category' => [
                    'name' => [
                        ['id' => 1, 'value' => $this->newCategoryName]
                    ],
                    'link_rewrite' => [
                        ['id' => 1, 'value' => \Illuminate\Support\Str::slug($this->newCategoryName)]
                    ],
                    'description' => [
                        ['id' => 1, 'value' => '']
                    ],
                    'active' => 1,
                    'id_parent' => $parentPrestashopId,
                ]
            ];

            // Convert to XML and send to PrestaShop
            $xmlBody = $client->arrayToXml($categoryData);

            $response = $client->makeRequest('POST', '/categories', [], [
                'body' => $xmlBody,
                'headers' => [
                    'Content-Type' => 'application/xml',
                ],
            ]);

            if (!isset($response['category']['id'])) {
                throw new \Exception('PrestaShop API nie zwróciło ID kategorii');
            }

            $newPrestashopCategoryId = (int) $response['category']['id'];

            // Create PPM category and mapping
            $ppmCategory = \App\Models\Category::create([
                'name' => $this->newCategoryName,
                'slug' => \Illuminate\Support\Str::slug($this->newCategoryName),
                'parent_id' => $this->newCategoryParentId,
                'is_active' => true,
            ]);

            // Create mapping
            $categoryMapper = app(\App\Services\PrestaShop\CategoryMapper::class);
            $categoryMapper->createMapping(
                $ppmCategory->id,
                $shop,
                $newPrestashopCategoryId,
                $this->newCategoryName
            );

            \Illuminate\Support\Facades\Log::info('[CREATE CATEGORY] Created new category in PrestaShop', [
                'shop_id' => $shop->id,
                'prestashop_id' => $newPrestashopCategoryId,
                'ppm_id' => $ppmCategory->id,
                'name' => $this->newCategoryName,
                'parent_prestashop_id' => $parentPrestashopId,
            ]);

            // Close modal
            $this->closeCreateCategoryModal();

            // Refresh categories for this shop
            $this->refreshPrestaShopCategories($this->createCategoryShopId ?? $this->activeShopId);

            $this->dispatch('success', message: "Utworzono kategorię: {$this->newCategoryName}");

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[CREATE CATEGORY] Failed', [
                'shop_id' => $this->createCategoryShopId,
                'name' => $this->newCategoryName,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('error', message: 'Błąd tworzenia kategorii: ' . $e->getMessage());
        }
    }

    /**
     * Show inline category creation form for specific parent
     *
     * FAZA 4.2.3 PERFORMANCE FIX: Single Livewire property instead of 927 Alpine states
     *
     * @param int $parentId Parent category ID
     * @param string $context 'default' or shop_id
     */
    public function showInlineCreate(int $parentId, string $context): void
    {
        $this->inlineCreateParentId = $parentId;
        $this->inlineCreateName = '';
        $this->inlineCreateContext = $context;
    }

    /**
     * Cancel/hide inline category creation form
     */
    public function cancelInlineCreate(): void
    {
        $this->inlineCreateParentId = null;
        $this->inlineCreateName = '';
        $this->inlineCreateContext = 'default';
    }

    /**
     * Submit inline category creation (called from Livewire form)
     *
     * FAZA 4.2.3 PERFORMANCE FIX: Uses Livewire properties instead of Alpine
     * PERFORMANCE FIX 2025-11-27: Accept params directly to avoid showInlineCreate() Livewire call
     */
    public function submitInlineCreate(?int $parentId = null, ?string $context = null, ?string $name = null): void
    {
        // PERFORMANCE FIX 2025-11-27: Accept $name as param to avoid wire:model.live lag
        // Use passed params or fall back to properties (backwards compat)
        $parentId = $parentId ?? $this->inlineCreateParentId;
        $context = $context ?? $this->inlineCreateContext;
        $name = $name ?? $this->inlineCreateName;

        if ($parentId === null) {
            return;
        }

        // Validate name
        if (empty(trim($name ?? ''))) {
            return;
        }

        $this->createInlineCategory(
            $parentId,
            $name,  // Use passed $name (from Alpine) instead of $this->inlineCreateName
            $context ?? 'default'
        );

        // Reset form after creation (just clear the name, Alpine handles visibility)
        $this->inlineCreateName = '';
        $this->inlineCreateParentId = null;
        $this->inlineCreateContext = 'default';  // Reset to default, not null (property is typed as string)
    }

    /**
     * Create inline subcategory directly in tree view
     *
     * FAZA 4.2.3: Called from category-tree-item.blade.php when user clicks "+" and submits name
     *
     * @param int $parentCategoryId Parent category ID (PPM)
     * @param string $subcategoryName Name for new subcategory
     * @param string $context 'default' or shop_id
     */
    public function createInlineCategory(int $parentCategoryId, string $subcategoryName, string $context): void
    {
        // Validate input
        $subcategoryName = trim($subcategoryName);

        if (strlen($subcategoryName) < 2) {
            $this->dispatch('error', message: 'Nazwa kategorii musi miec minimum 2 znaki');
            return;
        }

        if (strlen($subcategoryName) > 128) {
            $this->dispatch('error', message: 'Nazwa kategorii moze miec maksymalnie 128 znakow');
            return;
        }

        try {
            // 2025-11-26 DEFERRED CREATION: Category is NOT created immediately!
            // It's added to pending queue and created only when user clicks "Zapisz zmiany"
            // This allows user to cancel without leaving orphan categories in PrestaShop.

            // Generate temporary negative ID for pending category
            $tempId = $this->tempCategoryIdCounter--;

            // Create pending category entry
            $pendingCategory = [
                'tempId' => $tempId,
                'name' => $subcategoryName,
                'parentId' => $parentCategoryId,
                'slug' => \Illuminate\Support\Str::slug($subcategoryName),
                'context' => $context,
                'createdAt' => now()->toIso8601String(),
            ];

            // Add to pending queue
            if (!isset($this->pendingNewCategories[$context])) {
                $this->pendingNewCategories[$context] = [];
            }
            $this->pendingNewCategories[$context][] = $pendingCategory;

            // Mark as having unsaved changes
            $this->hasUnsavedChanges = true;

            if ($context === 'default') {
                // DEFAULT TAB: Add pending category to local PPM tree
                $this->addPendingCategoryToTree($tempId, $subcategoryName, $parentCategoryId, 'default');

                \Illuminate\Support\Facades\Log::info('[INLINE CREATE CATEGORY] Added to pending queue (PPM)', [
                    'context' => 'default',
                    'temp_id' => $tempId,
                    'name' => $subcategoryName,
                    'parent_id' => $parentCategoryId,
                ]);

                $this->dispatch('success', message: "Kategoria '{$subcategoryName}' oczekuje na zapis");

            } else {
                // SHOP TAB: Add pending category to PrestaShop tree (local only)
                $shopId = (int) $context;
                $this->addPendingCategoryToTree($tempId, $subcategoryName, $parentCategoryId, $context);

                \Illuminate\Support\Facades\Log::info('[INLINE CREATE CATEGORY] Added to pending queue (PrestaShop)', [
                    'context' => 'shop',
                    'shop_id' => $shopId,
                    'temp_id' => $tempId,
                    'name' => $subcategoryName,
                    'parent_prestashop_id' => $parentCategoryId,
                ]);

                $this->dispatch('success', message: "Kategoria '{$subcategoryName}' oczekuje na zapis");
            }

            // Auto-select the pending category (using temp ID)
            // Note: toggleCategory needs to handle negative IDs for pending categories
            $this->selectPendingCategory($tempId, $context);

            // PERFORMANCE FIX 2025-11-27: Use consolidated event (86% listener reduction)
            $this->js("
                setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('category-event', {
                        detail: { type: 'created-scroll', categoryId: {$tempId}, context: '{$context}' }
                    }));
                }, 100);
            ");

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[INLINE CREATE CATEGORY] Failed to add to pending', [
                'context' => $context,
                'parent_id' => $parentCategoryId,
                'name' => $subcategoryName,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('error', message: 'Blad tworzenia kategorii: ' . $e->getMessage());
        }
    }

    /**
     * Add pending category to the local category tree for display
     * Uses negative tempId to distinguish from real categories
     */
    private function addPendingCategoryToTree(int $tempId, string $name, int $parentId, string $context): void
    {
        // Create a fake category object for display
        $pendingCategoryObj = (object) [
            'id' => $tempId,
            'name' => $name . ' (oczekuje)',
            'parent_id' => $parentId,
            'children' => collect([]),
            'is_pending' => true, // Flag for visual indicator
            'sort_order' => 9999, // Show at end
        ];

        if ($context === 'default') {
            // Add to PPM categories tree
            $this->addCategoryToTreeRecursive($this->categories, $parentId, $pendingCategoryObj);
        } else {
            // Add to PrestaShop categories tree
            $shopId = (int) $context;
            if (isset($this->prestashopCategories[$shopId])) {
                $this->addCategoryToArrayTreeRecursive($this->prestashopCategories[$shopId], $parentId, $pendingCategoryObj);
            }
        }
    }

    /**
     * Recursively find parent and add child category (for Collection-based trees)
     */
    private function addCategoryToTreeRecursive(&$categories, int $parentId, object $newCategory): bool
    {
        foreach ($categories as $category) {
            if ($category->id === $parentId) {
                if (!$category->children) {
                    $category->children = collect([]);
                }
                $category->children->push($newCategory);
                return true;
            }
            if ($category->children && $category->children->count() > 0) {
                $childrenArray = $category->children->all();
                if ($this->addCategoryToTreeRecursive($childrenArray, $parentId, $newCategory)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Recursively find parent and add child category (for array-based trees)
     * 2025-11-26 FIX: Direct Collection push instead of array copy
     */
    private function addCategoryToArrayTreeRecursive(array &$categories, int $parentId, object $newCategory): bool
    {
        foreach ($categories as &$category) {
            $catId = is_object($category) ? $category->id : ($category['id'] ?? null);

            if ($catId === $parentId) {
                if (is_object($category)) {
                    if (!isset($category->children) || $category->children === null) {
                        $category->children = collect([]);
                    }
                    // Direct push to Collection (modifies in place)
                    $category->children->push($newCategory);
                    \Illuminate\Support\Facades\Log::debug('[ADD PENDING TO TREE] Added to parent', [
                        'parent_id' => $parentId,
                        'new_category_id' => $newCategory->id,
                        'children_count' => $category->children->count(),
                    ]);
                } else {
                    if (!isset($category['children'])) {
                        $category['children'] = [];
                    }
                    $category['children'][] = $newCategory;
                }
                return true;
            }

            // Recurse into children - use Collection directly if available
            if (is_object($category) && isset($category->children) && $category->children instanceof \Illuminate\Support\Collection) {
                // Convert to array, recurse, then update Collection if found
                $childrenArray = $category->children->all();
                if ($this->addCategoryToArrayTreeRecursive($childrenArray, $parentId, $newCategory)) {
                    // Update the Collection with modified array
                    $category->children = collect($childrenArray);
                    return true;
                }
            } elseif (is_object($category) && isset($category->children) && is_array($category->children)) {
                if ($this->addCategoryToArrayTreeRecursive($category->children, $parentId, $newCategory)) {
                    return true;
                }
            } elseif (is_array($category) && isset($category['children']) && !empty($category['children'])) {
                if ($this->addCategoryToArrayTreeRecursive($category['children'], $parentId, $newCategory)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Select a pending category (add to selected list)
     */
    private function selectPendingCategory(int $tempId, string $context): void
    {
        if ($context === 'default') {
            // Add to default categories selection
            if (!isset($this->defaultCategories['selected'])) {
                $this->defaultCategories['selected'] = [];
            }
            if (!in_array($tempId, $this->defaultCategories['selected'])) {
                $this->defaultCategories['selected'][] = $tempId;
            }
        } else {
            // Add to shop categories selection
            $shopId = (int) $context;
            if (!isset($this->shopCategories[$shopId])) {
                $this->shopCategories[$shopId] = ['selected' => [], 'primary' => null, 'mappings' => []];
            }
            if (!in_array($tempId, $this->shopCategories[$shopId]['selected'])) {
                $this->shopCategories[$shopId]['selected'][] = $tempId;
            }
        }
    }

    /**
     * Remove a pending category (before save)
     * Called when user clicks delete on a pending category
     */
    public function removePendingCategory(int $tempId, string $context): void
    {
        // Only works for pending (negative ID) categories
        if ($tempId >= 0) {
            $this->dispatch('error', message: 'Mozna usunac tylko oczekujace kategorie');
            return;
        }

        // Remove from pending queue
        if (isset($this->pendingNewCategories[$context])) {
            $this->pendingNewCategories[$context] = array_filter(
                $this->pendingNewCategories[$context],
                fn($cat) => $cat['tempId'] !== $tempId
            );
            $this->pendingNewCategories[$context] = array_values($this->pendingNewCategories[$context]);
        }

        // Remove from selection
        if ($context === 'default') {
            $this->defaultCategories['selected'] = array_values(
                array_diff($this->defaultCategories['selected'] ?? [], [$tempId])
            );
        } else {
            $shopId = (int) $context;
            if (isset($this->shopCategories[$shopId]['selected'])) {
                $this->shopCategories[$shopId]['selected'] = array_values(
                    array_diff($this->shopCategories[$shopId]['selected'], [$tempId])
                );
            }
        }

        // Remove from tree (will be re-rendered)
        if ($context === 'default') {
            $this->removeCategoryFromTree($this->categories, $tempId);
        } else {
            $shopId = (int) $context;
            if (isset($this->prestashopCategories[$shopId])) {
                $this->removeCategoryFromArrayTree($this->prestashopCategories[$shopId], $tempId);
            }
        }

        $this->dispatch('success', message: 'Usunieto oczekujaca kategorie');
    }

    /**
     * Mark a REAL category for deletion (deferred until Save)
     * Category stays visible with red highlight, removed on Save
     * Also marks all descendant categories for deletion
     *
     * FIX 2025-11-28: #[Renderless] prevents Livewire re-render (eliminates LAG!)
     * UI update via Alpine events dispatched at end of method
     *
     * @param int $categoryId Real category ID (positive)
     * @param string $context 'default' or shop_id
     */
    #[Renderless]
    public function markCategoryForDeletion(int $categoryId, string $context): void
    {
        // Only for real (positive ID) categories
        if ($categoryId < 0) {
            // For pending categories, use removePendingCategory instead
            $this->removePendingCategory($categoryId, $context);
            return;
        }

        // Initialize context array if needed
        if (!isset($this->pendingDeleteCategories[$context])) {
            $this->pendingDeleteCategories[$context] = [];
        }

        // Check if already marked for deletion
        if (in_array($categoryId, $this->pendingDeleteCategories[$context])) {
            // Toggle off - unmark for deletion (and all descendants)
            $this->unmarkCategoryForDeletion($categoryId, $context);
            return;
        }

        // Add to pending delete queue
        $this->pendingDeleteCategories[$context][] = $categoryId;

        // Find the category in tree and get all descendant IDs
        // FIX 2025-11-27: Pass context to use correct category tree (PPM vs PrestaShop)
        $category = $this->findCategoryInTree($categoryId, $context);
        $descendantIds = $category ? $this->getAllDescendantCategoryIds($category) : [];

        // Mark all descendants for deletion too
        foreach ($descendantIds as $descendantId) {
            if (!in_array($descendantId, $this->pendingDeleteCategories[$context])) {
                $this->pendingDeleteCategories[$context][] = $descendantId;
            }
        }

        // Mark form as changed
        $this->hasUnsavedChanges = true;

        $totalMarked = 1 + count($descendantIds);
        Log::info('[MARK FOR DELETION] Category and descendants marked for deletion', [
            'category_id' => $categoryId,
            'descendants' => $descendantIds,
            'context' => $context,
            'total_marked' => $totalMarked,
        ]);

        $message = $totalMarked > 1
            ? "Oznaczono $totalMarked kategorii do usuniecia (zapisz aby potwierdzic)"
            : 'Kategoria oznaczona do usuniecia (zapisz aby potwierdzic)';
        $this->dispatch('success', message: $message);

        // FIX 2025-11-28: Dispatch Alpine events for IMMEDIATE UI update (no re-render lag!)
        // Send event for main category
        $this->js("window.dispatchEvent(new CustomEvent('category-event', { detail: { type: 'marked-for-deletion', categoryId: {$categoryId}, context: '{$context}' } }));");

        // Send events for all descendants (they become children of marked parent)
        foreach ($descendantIds as $descId) {
            $this->js("window.dispatchEvent(new CustomEvent('category-event', { detail: { type: 'child-marked-for-deletion', categoryId: {$descId}, context: '{$context}' } }));");
        }
    }

    /**
     * Unmark a category from deletion (undo)
     * Also unmarks all descendant categories
     * BLOCKED if parent category is still marked for deletion
     *
     * FIX 2025-11-28: #[Renderless] prevents Livewire re-render (eliminates LAG!)
     *
     * @param int $categoryId Real category ID
     * @param string $context 'default' or shop_id
     */
    #[Renderless]
    public function unmarkCategoryForDeletion(int $categoryId, string $context): void
    {
        if (!isset($this->pendingDeleteCategories[$context])) {
            return;
        }

        // FIX 2025-11-26: Block unmarking if parent is still marked for deletion
        // FIX 2025-11-27: Pass context to use correct category tree (PPM vs PrestaShop)
        $parentId = $this->findParentCategoryId($categoryId, $context);
        if ($parentId && in_array($parentId, $this->pendingDeleteCategories[$context])) {
            $this->dispatch('warning', message: 'Nie mozna odznczyc - kategoria nadrzedna jest oznaczona do usuniecia');
            Log::debug('[UNMARK BLOCKED] Cannot unmark - parent is still marked for deletion', [
                'category_id' => $categoryId,
                'parent_id' => $parentId,
                'context' => $context,
            ]);
            return;
        }

        // Find category and get all descendant IDs
        // FIX 2025-11-27: Pass context to use correct category tree (PPM vs PrestaShop)
        $category = $this->findCategoryInTree($categoryId, $context);
        $descendantIds = $category ? $this->getAllDescendantCategoryIds($category) : [];

        // Remove parent from pending delete
        $this->pendingDeleteCategories[$context] = array_values(
            array_diff($this->pendingDeleteCategories[$context], [$categoryId])
        );

        // Remove all descendants from pending delete
        foreach ($descendantIds as $descendantId) {
            $this->pendingDeleteCategories[$context] = array_values(
                array_diff($this->pendingDeleteCategories[$context], [$descendantId])
            );
        }

        $totalUnmarked = 1 + count($descendantIds);
        Log::info('[UNMARK DELETION] Category and descendants unmarked from deletion', [
            'category_id' => $categoryId,
            'descendants' => $descendantIds,
            'context' => $context,
            'total_unmarked' => $totalUnmarked,
        ]);

        $message = $totalUnmarked > 1
            ? "Anulowano usuniecie $totalUnmarked kategorii"
            : 'Anulowano usuniecie kategorii';
        $this->dispatch('success', message: $message);

        // FIX 2025-11-28: Dispatch Alpine events for IMMEDIATE UI update (no re-render lag!)
        // Send event for main category
        $this->js("window.dispatchEvent(new CustomEvent('category-event', { detail: { type: 'unmarked-for-deletion', categoryId: {$categoryId}, context: '{$context}' } }));");

        // Send events for all descendants (they are no longer children of marked parent)
        foreach ($descendantIds as $descId) {
            $this->js("window.dispatchEvent(new CustomEvent('category-event', { detail: { type: 'child-unmarked-for-deletion', categoryId: {$descId}, context: '{$context}' } }));");
        }
    }

    /**
     * Check if category is marked for deletion
     */
    public function isCategoryMarkedForDeletion(int $categoryId, string $context): bool
    {
        return isset($this->pendingDeleteCategories[$context])
            && in_array($categoryId, $this->pendingDeleteCategories[$context]);
    }

    /**
     * Get all categories marked for deletion for a context
     */
    public function getCategoriesMarkedForDeletion(string $context): array
    {
        return $this->pendingDeleteCategories[$context] ?? [];
    }

    /**
     * Build contextCategories for save, excluding pendingDeleteCategories
     * FIX 2025-11-27: Categories marked for deletion should be removed from selected
     *
     * @param string $currentKey Context key ('default' or shop_id as string)
     * @return array ['selected' => [...], 'primary' => int|null]
     */
    private function buildContextCategoriesForSave(string $currentKey): array
    {
        // Get current categories for this context
        if ($currentKey === 'default') {
            $contextCategories = $this->defaultCategories;
            $mappings = []; // Default context uses PPM IDs directly
        } else {
            $contextCategories = $this->shopCategories[$currentKey] ?? ['selected' => [], 'primary' => null];
            $mappings = $contextCategories['mappings'] ?? [];
        }

        $selected = $contextCategories['selected'] ?? []; // PPM IDs
        $primary = $contextCategories['primary'] ?? null; // PPM ID

        // Get categories marked for deletion in this context
        // FIX 2025-11-27 v3: These are PrestaShop IDs (from category tree which uses PrestaShop data)
        $toDeletePrestaShopIds = $this->pendingDeleteCategories[$currentKey] ?? [];

        if (empty($toDeletePrestaShopIds)) {
            // No deletions - return as is
            return $contextCategories;
        }

        // FIX 2025-11-27 v3: Convert PrestaShop IDs to PPM IDs for comparison
        // Mappings format: PPM_ID => PrestaShop_ID, so flip to get PrestaShop_ID => PPM_ID
        $flippedMappings = array_flip($mappings);
        $toDeletePpmIds = [];
        foreach ($toDeletePrestaShopIds as $psId) {
            if (isset($flippedMappings[$psId])) {
                $toDeletePpmIds[] = $flippedMappings[$psId];
            } else {
                // If no mapping found, try to use as-is (might be PPM ID already)
                $toDeletePpmIds[] = $psId;
            }
        }

        // Remove deleted categories from selected (now both are PPM IDs)
        $filteredSelected = array_values(array_diff($selected, $toDeletePpmIds));

        // If primary was deleted, reset it
        if ($primary !== null && in_array($primary, $toDeletePpmIds)) {
            $primary = !empty($filteredSelected) ? $filteredSelected[0] : null;
        }

        Log::info('[FIX 2025-11-27 v3] buildContextCategoriesForSave: Filtered deleted categories', [
            'context' => $currentKey,
            'original_selected' => $selected,
            'to_delete_prestashop_ids' => $toDeletePrestaShopIds,
            'mappings' => $mappings,
            'to_delete_ppm_ids' => $toDeletePpmIds,
            'filtered_selected' => $filteredSelected,
            'primary' => $primary,
        ]);

        return [
            'selected' => $filteredSelected,
            'primary' => $primary,
        ];
    }

    /**
     * Get all descendant category IDs from a category tree
     * Used for marking children when parent is marked for deletion
     */
    private function getAllDescendantCategoryIds($category): array
    {
        $ids = [];

        if (!$category || !isset($category->children) || !$category->children) {
            return $ids;
        }

        foreach ($category->children as $child) {
            if (isset($child->id) && $child->id > 0) {
                $ids[] = $child->id;
                // Recursively get grandchildren
                $ids = array_merge($ids, $this->getAllDescendantCategoryIds($child));
            }
        }

        return $ids;
    }

    /**
     * Find a category by ID in the appropriate category tree
     * FIX 2025-11-27: Added context parameter to use correct tree (PPM vs PrestaShop)
     *
     * @param int $categoryId Category ID to find
     * @param string|null $context 'default' or shop_id - determines which category tree to use
     * @param mixed $categories Internal recursion parameter
     */
    private function findCategoryInTree(int $categoryId, ?string $context = null, $categories = null)
    {
        if ($categories === null) {
            // FIX 2025-11-27: Use appropriate category tree based on context
            if ($context !== null && $context !== 'default') {
                $categories = collect($this->getShopCategories());
            } else {
                $categories = $this->getAvailableCategories();
            }
        }

        if (!$categories) {
            return null;
        }

        foreach ($categories as $category) {
            if ($category->id === $categoryId) {
                return $category;
            }
            if (isset($category->children) && $category->children && $category->children->count() > 0) {
                // FIX 2025-11-27: Pass context through recursive calls
                $found = $this->findCategoryInTree($categoryId, $context, $category->children);
                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Find parent category ID for a given category
     * FIX 2025-11-26: Used to block unmarking children when parent is marked for deletion
     * FIX 2025-11-27: Made public and added context parameter to use correct category tree
     * (shop context uses PrestaShop IDs, default uses PPM IDs)
     * Returns parent ID or null if category is root or not found
     *
     * @param int $categoryId Category ID to find parent of
     * @param string|null $context 'default' or shop_id - determines which category tree to use
     * @param mixed $categories Internal recursion parameter
     * @param int|null $currentParentId Internal recursion parameter
     */
    public function findParentCategoryId(int $categoryId, ?string $context = null, $categories = null, ?int $currentParentId = null): ?int
    {
        if ($categories === null) {
            // FIX 2025-11-27: Use appropriate category tree based on context
            // Shop context uses PrestaShop categories, default uses PPM categories
            if ($context !== null && $context !== 'default') {
                $categories = collect($this->getShopCategories());
            } else {
                $categories = $this->getAvailableCategories();
            }
        }

        if (!$categories || $categories->isEmpty()) {
            return null;
        }

        foreach ($categories as $category) {
            // Found the category - return its parent
            if ($category->id === $categoryId) {
                return $currentParentId;
            }

            // Search in children
            $children = $category->children ?? collect();
            if ($children instanceof \Illuminate\Support\Collection && $children->count() > 0) {
                $result = $this->findParentCategoryId($categoryId, $context, $children, $category->id);
                if ($result !== null) {
                    return $result;
                }
            } elseif (is_array($children) && count($children) > 0) {
                $result = $this->findParentCategoryId($categoryId, $context, collect($children), $category->id);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * Remove category from Collection-based tree
     */
    private function removeCategoryFromTree(&$categories, int $categoryId): bool
    {
        foreach ($categories as $key => $category) {
            if ($category->id === $categoryId) {
                unset($categories[$key]);
                return true;
            }
            if ($category->children && $category->children->count() > 0) {
                $children = $category->children->all();
                if ($this->removeCategoryFromTree($children, $categoryId)) {
                    $category->children = collect($children);
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Remove category from array-based tree
     */
    private function removeCategoryFromArrayTree(array &$categories, int $categoryId): bool
    {
        foreach ($categories as $key => &$category) {
            $catId = is_object($category) ? $category->id : ($category['id'] ?? null);

            if ($catId === $categoryId) {
                unset($categories[$key]);
                $categories = array_values($categories);
                return true;
            }

            $children = is_object($category)
                ? ($category->children ?? [])
                : ($category['children'] ?? []);

            if (!empty($children)) {
                $childrenArray = is_object($category) && $category->children instanceof \Illuminate\Support\Collection
                    ? $category->children->all()
                    : (array) $children;

                if ($this->removeCategoryFromArrayTree($childrenArray, $categoryId)) {
                    if (is_object($category)) {
                        $category->children = collect($childrenArray);
                    } else {
                        $category['children'] = $childrenArray;
                    }
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Process all pending categories - called on Save
     * Creates categories in PrestaShop API and updates local state
     */
    public function processPendingCategories(): array
    {
        $results = ['created' => [], 'failed' => []];

        foreach ($this->pendingNewCategories as $context => $pendingList) {
            foreach ($pendingList as $pending) {
                try {
                    if ($context === 'default') {
                        // Create PPM category
                        $ppmCategory = \App\Models\Category::create([
                            'name' => $pending['name'],
                            'slug' => $pending['slug'],
                            'parent_id' => $pending['parentId'],
                            'is_active' => true,
                        ]);

                        $results['created'][] = [
                            'tempId' => $pending['tempId'],
                            'realId' => $ppmCategory->id,
                            'name' => $pending['name'],
                            'context' => $context,
                        ];

                        // Update selection: replace tempId with realId
                        $this->replaceTempIdInSelection($pending['tempId'], $ppmCategory->id, $context);

                    } else {
                        // Create PrestaShop category
                        $shopId = (int) $context;
                        $shop = \App\Models\PrestaShopShop::find($shopId);

                        if (!$shop) {
                            throw new \Exception("Shop not found: {$shopId}");
                        }

                        $client = PrestaShopClientFactory::create($shop);
                        $categoryMapper = app(\App\Services\PrestaShop\CategoryMapper::class);

                        $categoryData = [
                            'category' => [
                                'name' => [['id' => 1, 'value' => $pending['name']]],
                                'link_rewrite' => [['id' => 1, 'value' => $pending['slug']]],
                                'description' => [['id' => 1, 'value' => '']],
                                'active' => 1,
                                'id_parent' => $pending['parentId'],
                            ]
                        ];

                        $response = $client->makeRequest('POST', '/categories', [], [
                            'body' => $client->arrayToXml($categoryData),
                            'headers' => ['Content-Type' => 'application/xml'],
                        ]);

                        if (!isset($response['category']['id'])) {
                            throw new \Exception('PrestaShop API error');
                        }

                        $prestashopId = (int) $response['category']['id'];

                        // Create PPM category + mapping
                        $ppmParentId = $categoryMapper->mapFromPrestaShop($pending['parentId'], $shop);
                        $ppmCategory = \App\Models\Category::create([
                            'name' => $pending['name'],
                            'slug' => $pending['slug'],
                            'parent_id' => $ppmParentId,
                            'is_active' => true,
                        ]);

                        $categoryMapper->createMapping($ppmCategory->id, $shop, $prestashopId, $pending['name']);

                        $results['created'][] = [
                            'tempId' => $pending['tempId'],
                            'realId' => $prestashopId,
                            'ppmId' => $ppmCategory->id,
                            'name' => $pending['name'],
                            'context' => $context,
                        ];

                        // Update selection
                        $this->replaceTempIdInSelection($pending['tempId'], $prestashopId, $context);
                    }

                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'tempId' => $pending['tempId'],
                        'name' => $pending['name'],
                        'context' => $context,
                        'error' => $e->getMessage(),
                    ];

                    \Illuminate\Support\Facades\Log::error('[PROCESS PENDING CATEGORY] Failed', [
                        'pending' => $pending,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Clear pending queue
        $this->pendingNewCategories = [];

        return $results;
    }

    /**
     * Replace tempId with realId in selection arrays
     */
    private function replaceTempIdInSelection(int $tempId, int $realId, string $context): void
    {
        if ($context === 'default') {
            $key = array_search($tempId, $this->defaultCategories['selected'] ?? []);
            if ($key !== false) {
                $this->defaultCategories['selected'][$key] = $realId;
            }
        } else {
            $shopId = (int) $context;
            if (isset($this->shopCategories[$shopId]['selected'])) {
                $key = array_search($tempId, $this->shopCategories[$shopId]['selected']);
                if ($key !== false) {
                    $this->shopCategories[$shopId]['selected'][$key] = $realId;
                }
            }
        }
    }

    /**
     * Discard all pending categories - called on Cancel
     */
    public function discardPendingCategories(): void
    {
        // Remove pending categories from selections
        foreach ($this->pendingNewCategories as $context => $pendingList) {
            foreach ($pendingList as $pending) {
                $tempId = $pending['tempId'];

                if ($context === 'default') {
                    $this->defaultCategories['selected'] = array_values(
                        array_diff($this->defaultCategories['selected'] ?? [], [$tempId])
                    );
                } else {
                    $shopId = (int) $context;
                    if (isset($this->shopCategories[$shopId]['selected'])) {
                        $this->shopCategories[$shopId]['selected'] = array_values(
                            array_diff($this->shopCategories[$shopId]['selected'], [$tempId])
                        );
                    }
                }
            }
        }

        // Clear pending queue
        $this->pendingNewCategories = [];
        $this->pendingDeleteCategories = [];

        \Illuminate\Support\Facades\Log::info('[DISCARD PENDING CATEGORIES] All pending operations discarded');
    }

    /**
     * Check if a category ID is pending (negative = pending)
     */
    public function isPendingCategory(int $categoryId): bool
    {
        return $categoryId < 0;
    }

    /**
     * Get count of pending categories for display
     */
    public function getPendingCategoriesCountProperty(): int
    {
        $count = 0;
        foreach ($this->pendingNewCategories as $contextList) {
            $count += count($contextList);
        }
        return $count;
    }

    /**
     * Get available parent categories for create category modal
     *
     * @return array Parent category options [id => name]
     */
    public function getParentCategoryOptionsProperty(): array
    {
        if (!$this->createCategoryShopId) {
            return [];
        }

        $categories = $this->prestashopCategories[$this->createCategoryShopId] ?? [];

        return $this->flattenCategoriesForSelect($categories);
    }

    /**
     * Flatten category tree for select dropdown
     */
    private function flattenCategoriesForSelect(array $categories, int $level = 0): array
    {
        $options = [];

        foreach ($categories as $category) {
            $prefix = str_repeat('─ ', $level);
            $options[$category['id']] = $prefix . $category['name'];

            if (!empty($category['children'])) {
                $options += $this->flattenCategoriesForSelect($category['children'], $level + 1);
            }
        }

        return $options;
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

            // Invalidate category validation cache for new shop context
            $this->invalidateCategoryValidationCache($shopId);

            // Check if target context has pending changes first
            if ($this->hasPendingChangesForCurrent()) {
                // Load pending changes for this context
                $this->loadPendingChanges();
            } else {
                // No pending changes - load from database/stored values
                if ($shopId === null) {
                    // Switch to default data
                    $this->loadDefaultDataToForm();

                    // ETAP_05b FAZA 5: Restore variant context to default
                    $this->restoreVariantContextToDefault();

                    // ETAP_05c: Clear PrestaShop variants when switching to default tab
                    $this->prestaShopVariants = [];
                } else {
                    // FAZA 5.2 FIX: Load tax rules BEFORE loading form data
                    // Ensures $availableTaxRuleGroups[$shopId] is populated for dropdown options
                    $this->loadTaxRuleGroupsForShop($shopId);

                    // Switch to shop-specific data with inheritance
                    $this->loadShopDataToForm($shopId);

                    // ETAP_05b FAZA 5: Switch variant context to shop
                    $this->switchVariantContextToShop($shopId);
                }
            }

            $this->updateCharacterCounts();

            // ROLLBACK 2025-11-20: Removed auto-pull on tab switch (caused issues)
            // ORIGINAL CODE: Auto-load PrestaShop data when switching to shop (if not already loaded)
            // This fixes the issue where updatedActiveShopId() hook doesn't trigger on PHP-side changes
            if ($shopId !== null && !isset($this->loadedShopData[$shopId]) && $this->isEditMode) {
                Log::info('Auto-loading PrestaShop data in switchToShop()', [
                    'shop_id' => $shopId,
                    'product_id' => $this->product?->id,
                ]);
                $this->loadProductDataFromPrestaShop($shopId);

                // ETAP_07e FAZA 5: Load features from PrestaShop for comparison
                $this->loadShopFeaturesFromPrestaShop($shopId);

                // ETAP_05c: Pull variants from PrestaShop API for shop context
                $this->pullVariantsFromPrestaShop($shopId);
            } elseif ($shopId !== null && isset($this->loadedShopData[$shopId])) {
                // FIX 2025-11-28 v2: Cache hit - data already loaded from PrestaShop API
                // ETAP_07e FAZA 5: Load features from PrestaShop if not cached
                if (!isset($this->shopProductFeatures[$shopId])) {
                    $this->loadShopFeaturesFromPrestaShop($shopId);
                }

                // ETAP_05c: Pull variants from PrestaShop API (always fresh, not cached)
                $this->pullVariantsFromPrestaShop($shopId);

                // Skip database query if shopCategories are already cached
                if (!isset($this->shopCategories[$shopId]) || empty($this->shopCategories[$shopId]['selected'])) {
                    // Categories not cached yet - load from database (first time only)
                    $this->loadShopCategories($shopId);
                    Log::info('Cache hit - categories loaded from database (first time)', [
                        'shop_id' => $shopId,
                        'product_id' => $this->product?->id,
                    ]);
                } else {
                    // Categories already cached - skip database query entirely
                    Log::info('Cache hit - categories already in memory (instant)', [
                        'shop_id' => $shopId,
                        'product_id' => $this->product?->id,
                        'cached_categories_count' => count($this->shopCategories[$shopId]['selected']),
                    ]);
                }

                // Dispatch category tree refresh for Alpine.js
                $this->dispatch('category-tree-refresh', shopId: $shopId);

                // Hide Alpine.js loading overlay (instant - no API call)
                $this->dispatch('prestashop-loading-end');
            }

            Log::info('Switched to shop tab with pending changes support', [
                'product_id' => $this->product?->id,
                'shop_id' => $shopId,
                'active_shop_id' => $this->activeShopId,
                'has_pending_changes' => $this->hasPendingChangesForCurrent(),
                'total_pending_contexts' => count($this->pendingChanges),
            ]);

            // FIX 2025-11-21 v5: Calculate expanded category IDs after switching shops
            $this->expandedCategoryIds = $this->calculateExpandedCategoryIds();

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

            // REMOVED 2025-11-25: updateCategoryColorCoding() - method was deleted, call caused error
            // Livewire automatically re-renders on property changes, no manual update needed

            // === FEATURES (CONTEXT-ISOLATED SYSTEM) ===
            // FIX 2025-12-03: Restore default features when switching to default context
            // This ensures shop-specific changes don't leak into default tab
            $this->restoreDefaultFeatures();
        } elseif ($this->product) {
            // Fallback: load from product if defaultData is not available
            $this->loadProductData();
        }
    }

    /**
     * Restore default features from snapshot
     * FIX 2025-12-03: Called when switching to "Dane domyslne" to restore original values
     */
    private function restoreDefaultFeatures(): void
    {
        if (empty($this->defaultProductFeatures)) {
            return;
        }

        // Rebuild productFeatures from defaultProductFeatures snapshot
        $this->productFeatures = [];
        foreach ($this->defaultProductFeatures as $featureTypeId => $value) {
            $this->productFeatures[] = [
                'feature_type_id' => $featureTypeId,
                'value' => $value,
            ];
        }

        Log::debug('[FEATURE ISOLATION] Restored default features', [
            'restored_count' => count($this->productFeatures),
        ]);
    }

    /**
     * Pull fresh data from PrestaShop instantly (synchronous, no JOB dispatch)
     *
     * FIX 2025-11-21: Instant pull after sync completion
     * User Request: "Pull data z presty instant, bez tworzenia JOB-a, w tle automatycznie"
     *
     * @param int $shopId Shop ID to pull data from
     * @return void
     */
    private function pullShopDataInstant(int $shopId): void
    {
        try {
            // Find shop data
            $shopData = $this->product->shopData->where('shop_id', $shopId)->first();

            if (!$shopData || !$shopData->prestashop_product_id) {
                Log::warning('[INSTANT PULL] Product not linked or missing PrestaShop ID', [
                    'product_id' => $this->product->id,
                    'shop_id' => $shopId,
                ]);
                return;
            }

            Log::info('[INSTANT PULL] Pulling fresh data from PrestaShop (synchronous)', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'prestashop_product_id' => $shopData->prestashop_product_id,
            ]);

            // Create API client
            $client = \App\Services\PrestaShop\PrestaShopClientFactory::create($shopData->shop);

            // Fetch product from PrestaShop
            $psData = $client->getProduct($shopData->prestashop_product_id);

            if (isset($psData['product'])) {
                $psData = $psData['product'];
            }

            // Apply conflict resolution strategy
            $conflictResolver = app(\App\Services\PrestaShop\ConflictResolver::class);
            $resolution = $conflictResolver->resolve($shopData, $psData);

            if ($resolution['should_update']) {
                // Update allowed - apply PrestaShop data
                $shopData->update(array_merge($resolution['data'], [
                    'last_pulled_at' => now(),
                    'sync_status' => 'synced',
                    'has_conflicts' => false,
                    'conflict_log' => null,
                    'conflicts_detected_at' => null,
                ]));

                // FIX 2025-11-25: Ensure root categories are ALWAYS in category_mappings after pull
                // PrestaShop doesn't have PPM root categories (Baza=1, Wszystko=2)
                $this->ensureRootCategoriesInCategoryMappings($shopData);

                Log::info('[INSTANT PULL] Product updated from PrestaShop', [
                    'product_id' => $this->product->id,
                    'shop_id' => $shopId,
                    'reason' => $resolution['reason'],
                ]);

                // Reload form data to reflect changes
                // FIX 2025-11-25: Set flag to skip updated() hook during data loading
                // Prevents false positive hasUnsavedChanges after job completion
                $this->isLoadingData = true;
                try {
                    $this->product->refresh();
                    $this->loadShopDataToForm($shopId);

                    // FIX 2025-11-25: Update $this->shopData array for UI sync status badges
                    // Without this, sync_status in UI remains stale after job completion
                    $this->updateStoredShopData();
                } finally {
                    $this->isLoadingData = false;
                }

            } else {
                Log::warning('[INSTANT PULL] Update blocked by conflict resolver', [
                    'product_id' => $this->product->id,
                    'shop_id' => $shopId,
                    'reason' => $resolution['reason'],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('[INSTANT PULL] Failed to pull data from PrestaShop', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
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

        // === FAZA 5.2: RELOAD TAX RATE OVERRIDE FROM DATABASE ===
        // After save, reload from database to show correct value in dropdown
        $shopData = $this->product?->shopData?->where('shop_id', $shopId)->first();

        Log::debug('[FAZA 5.2 UI RELOAD] loadShopDataToForm called', [
            'shop_id' => $shopId,
            'shopData_exists' => $shopData !== null,
            'tax_rate_override_from_db' => $shopData?->tax_rate_override,
            'current_selectedTaxRateOption' => $this->selectedTaxRateOption,
        ]);

        if ($shopData) {
            $this->shopTaxRateOverrides[$shopId] = $shopData->tax_rate_override;

            // Update dropdown selection to match saved value
            if ($shopData->tax_rate_override !== null) {
                // Shop has override - set dropdown to that rate
                // CRITICAL: Use number_format to match Blade template format (5.00, not 5)
                $this->selectedTaxRateOption = number_format($shopData->tax_rate_override, 2, '.', '');

                Log::debug('[FAZA 5.2 UI RELOAD] Set dropdown to override value', [
                    'override' => $shopData->tax_rate_override,
                    'selectedTaxRateOption' => $this->selectedTaxRateOption,
                ]);
            } else {
                // No override - use default
                $this->selectedTaxRateOption = 'use_default';

                Log::debug('[FAZA 5.2 UI RELOAD] Set dropdown to use_default', [
                    'tax_rate_override' => 'NULL',
                ]);
            }

            // Force Livewire to sync property changes to UI
            // FIX 2025-11-25: Skip dispatch during data loading to prevent hook cascade
            if (!$this->isLoadingData) {
                $this->dispatch('$refresh');
            }
        }

        // === STATUS & SETTINGS ===
        $this->is_active = $this->getShopValue($shopId, 'is_active') ?? $this->is_active;
        // NOTE: is_variant_master is a GLOBAL product property, NOT per-shop
        // It defines whether product has variants at all - should NOT change when switching shops
        // REMOVED: $this->is_variant_master = $this->getShopValue($shopId, 'is_variant_master') ?? $this->is_variant_master;
        $this->is_featured = $this->getShopValue($shopId, 'is_featured') ?? $this->is_featured;
        $this->sort_order = $this->getShopValue($shopId, 'sort_order') ?: $this->sort_order;

        // === CATEGORIES ===
        // BUG FIX 2025-11-19: Reload categories from database after save
        // FIX 2025-11-21 (Fix #12): SKIP category reload when sync_status === 'pending'
        // REASON: Race condition - if user saves and immediately re-enters during JOB execution,
        // loadCategories() would pull OLD data from PrestaShop (before JOB finishes sync)
        // This would overwrite the NEW categories that user just saved
        // SOLUTION: Keep in-memory categories when JOB is pending, reload only when sync completes
        if ($this->product && $this->product->exists && $this->categoryManager) {
            // Check if sync is pending for this shop (use already loaded $shopData from line 2325)
            if ($shopData && $shopData->sync_status === \App\Models\ProductShopData::STATUS_PENDING) {
                // JOB is running - SKIP category reload (keep in-memory data)
                // Category checkboxes will be disabled via blade template (isPendingSyncForShop)
                Log::info('[FIX #12] Categories NOT reloaded - sync pending (preventing race condition)', [
                    'shop_id' => $shopId,
                    'product_id' => $this->product->id,
                    'sync_status' => 'pending',
                    'reason' => 'Waiting for JOB to complete before reloading from DB',
                    'current_categories_in_memory' => $this->shopCategories[$shopId] ?? 'NOT_SET',
                ]);
            } else {
                // Normal flow - sync complete, safe to reload from DB
                $this->categoryManager->loadCategories();

                Log::debug('[FIX #12] Categories reloaded from DB (sync complete)', [
                    'shop_id' => $shopId,
                    'product_id' => $this->product->id,
                    'shopCategories_after_reload' => $this->shopCategories[$shopId] ?? 'NOT_SET',
                ]);
            }
        }

        // FIX #13: Update category editing disabled state for reactive Alpine.js binding
        // Call method to refresh $categoryEditingDisabled property based on sync_status
        $this->isCategoryEditingDisabled();

        // REMOVED 2025-11-25: updateCategoryColorCoding() - method was deleted, call caused error
        // Livewire automatically re-renders on property changes, no manual update needed

        // === FEATURES (CONTEXT-ISOLATED SYSTEM) ===
        // FIX 2025-12-03: Load shop-specific features when switching to shop tab
        // If shop has custom features loaded from PrestaShop, use them
        // Otherwise inherit from default features
        $this->loadShopFeaturesToForm($shopId);
    }

    /**
     * Load shop-specific features to form
     * FIX 2025-12-03: Called when switching to shop tab to load shop-specific feature values
     *
     * @param int $shopId
     */
    private function loadShopFeaturesToForm(int $shopId): void
    {
        // FIX 2025-12-03: OPCJA B - Per-shop features priority:
        // 1. First check attribute_mappings storage (user-saved per-shop features)
        // 2. Fall back to shopProductFeatures cache (from PrestaShop API pull)
        // 3. Finally inherit from default features

        // STEP 1: Try to load from attribute_mappings (per-shop storage)
        $storedFeatures = $this->loadShopFeaturesFromStorage($shopId);

        if (!empty($storedFeatures)) {
            // Shop has saved per-shop features in attribute_mappings - use them
            $this->productFeatures = [];
            foreach ($storedFeatures as $featureTypeId => $value) {
                $this->productFeatures[] = [
                    'feature_type_id' => (int) $featureTypeId,
                    'value' => $value,
                ];
            }

            // Update cache for UI consistency
            $this->shopProductFeatures[$shopId] = $storedFeatures;

            Log::debug('[FEATURE ISOLATION] Loaded shop features from attribute_mappings storage', [
                'shop_id' => $shopId,
                'features_count' => count($this->productFeatures),
                'source' => 'attribute_mappings',
            ]);
            return;
        }

        // STEP 2: Check if shop has features from PrestaShop API pull
        if (isset($this->shopProductFeatures[$shopId]) && !empty($this->shopProductFeatures[$shopId])) {
            // Shop has PrestaShop features - build productFeatures from them
            $this->productFeatures = [];
            foreach ($this->shopProductFeatures[$shopId] as $featureTypeId => $value) {
                $this->productFeatures[] = [
                    'feature_type_id' => (int) $featureTypeId,
                    'value' => $value,
                ];
            }

            Log::debug('[FEATURE ISOLATION] Loaded shop features from PrestaShop API cache', [
                'shop_id' => $shopId,
                'features_count' => count($this->productFeatures),
                'source' => 'prestashop_api',
            ]);
            return;
        }

        // STEP 3: No shop-specific features anywhere - inherit from default
        $this->restoreDefaultFeatures();

        Log::debug('[FEATURE ISOLATION] Shop has no custom features - inheriting from default', [
            'shop_id' => $shopId,
            'inherited_count' => count($this->productFeatures),
            'source' => 'default_inherited',
        ]);
    }

    /**
     * Ensure root categories (Baza, Wszystko) are in category_mappings
     *
     * FIX 2025-11-25: PrestaShop pull doesn't include PPM-only root categories
     * This method adds them to category_mappings.ui.selected after each pull
     *
     * @param \App\Models\ProductShopData $shopData
     * @return void
     */
    private function ensureRootCategoriesInCategoryMappings(\App\Models\ProductShopData $shopData): void
    {
        $rootCategoryIds = [1, 2]; // Baza, Wszystko

        $categoryMappings = $shopData->category_mappings;

        // Skip if no mappings or empty structure
        if (empty($categoryMappings) || !isset($categoryMappings['ui']['selected']) || empty($categoryMappings['mappings'])) {
            Log::debug('[ROOT CATEGORIES] Skipping - empty category_mappings', [
                'product_id' => $shopData->product_id,
                'shop_id' => $shopData->shop_id,
            ]);
            return;
        }

        $selected = $categoryMappings['ui']['selected'];
        $updated = false;

        foreach ($rootCategoryIds as $rootId) {
            if (!in_array($rootId, $selected)) {
                $selected[] = $rootId;
                // Also add to mappings (PPM-only categories map to themselves)
                $categoryMappings['mappings'][(string)$rootId] = $rootId;
                $updated = true;
            }
        }

        if ($updated) {
            $categoryMappings['ui']['selected'] = $selected;
            $categoryMappings['metadata']['last_updated'] = now()->toIso8601String();
            $categoryMappings['metadata']['source'] = 'pull'; // Keep source as pull

            $shopData->category_mappings = $categoryMappings;
            $shopData->save();

            Log::info('[ROOT CATEGORIES] Added root categories after pull', [
                'product_id' => $shopData->product_id,
                'shop_id' => $shopData->shop_id,
                'selected_count' => count($selected),
            ]);
        }
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

        // FIX 2025-11-20: Load categories from product_shop_data.category_mappings (NEW Option A architecture)
        // instead of product_categories pivot table (OLD architecture)
        $productShopData = \App\Models\ProductShopData::where('product_id', $this->product->id)
            ->where('shop_id', $shopId)
            ->first();

        if ($productShopData && !empty($productShopData->category_mappings)) {
            // CategoryMappingsCast automatically deserializes JSON to Option A structure:
            // ['ui' => ['selected' => [1, 36, 2], 'primary' => 1], 'mappings' => [...]]
            $categoryMappings = $productShopData->category_mappings;

            // FIX 2025-11-25: Auto-repair missing root categories (Baza=1, Wszystko=2)
            // These are PPM-only categories that don't exist in PrestaShop
            // If PULL from PrestaShop removed them, add them back automatically
            $selectedCategories = $categoryMappings['ui']['selected'] ?? [];
            $rootCategoryIds = [1, 2];
            $needsRepair = false;

            foreach ($rootCategoryIds as $rootId) {
                if (!in_array($rootId, $selectedCategories)) {
                    $needsRepair = true;
                    break;
                }
            }

            if ($needsRepair) {
                Log::info('[loadShopCategories] ROOT CATEGORIES MISSING - auto-repairing', [
                    'product_id' => $this->product->id,
                    'shop_id' => $shopId,
                    'before_selected' => $selectedCategories,
                ]);

                // Use ensureRootCategoriesInCategoryMappings to repair DB
                $this->ensureRootCategoriesInCategoryMappings($productShopData);

                // Refresh category_mappings after repair
                $productShopData->refresh();
                $categoryMappings = $productShopData->category_mappings;

                Log::info('[loadShopCategories] ROOT CATEGORIES REPAIRED', [
                    'product_id' => $this->product->id,
                    'shop_id' => $shopId,
                    'after_selected' => $categoryMappings['ui']['selected'] ?? [],
                ]);
            }

            $this->shopCategories[$shopId] = [
                'selected' => $categoryMappings['ui']['selected'] ?? [],
                'primary' => $categoryMappings['ui']['primary'] ?? null
            ];

            Log::debug('loadShopCategories: Loaded from product_shop_data.category_mappings', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'selected' => $this->shopCategories[$shopId]['selected'],
                'primary' => $this->shopCategories[$shopId]['primary'],
            ]);
        } else {
            // No shop-specific categories - inherit from default
            $this->shopCategories[$shopId] = [
                'selected' => $this->defaultCategories['selected'] ?? [],
                'primary' => $this->defaultCategories['primary'] ?? null
            ];

            Log::debug('loadShopCategories: No shop data found, using defaults', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'has_defaults' => !empty($this->defaultCategories['selected']),
            ]);
        }

        Log::info('Shop categories loaded from database', [
            'product_id' => $this->product->id,
            'shop_id' => $shopId,
            'categories_count' => count($this->shopCategories[$shopId]['selected'] ?? []),
            'primary_category' => $this->shopCategories[$shopId]['primary'] ?? null,
            'source' => ($productShopData && !empty($productShopData->category_mappings)) ? 'shop_specific' : 'inherited_from_default'
        ]);

        // REMOVED 2025-11-25: updateCategoryColorCoding() - method was deleted, call caused error
        // Livewire automatically re-renders on property changes, no manual update needed
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

            // === FAZA 5.2 CRITICAL FIX: TAX RATE ===
            // In DEFAULT mode: save tax_rate (global default)
            // In SHOP mode: DON'T save tax_rate (it's global!), save tax_rate_override instead
            'tax_rate' => $this->activeShopId === null ? $this->tax_rate : null,

            // === FAZA 5.2: TAX RATE OVERRIDE (SHOP MODE ONLY) ===
            'tax_rate_override' => $this->activeShopId !== null
                ? ($this->shopTaxRateOverrides[$this->activeShopId] ?? null)
                : null,

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
            // FIX 2025-11-27: Remove pendingDeleteCategories from selected before save
            'contextCategories' => $this->buildContextCategoriesForSave($currentKey),

            // Keep full state for save compatibility (but don't use for loading)
            '_fullDefaultCategories' => $this->defaultCategories,
            '_fullShopCategories' => $this->shopCategories,

            // === FEATURES (CONTEXT-ISOLATED SYSTEM) ===
            // FIX 2025-12-03: Save features per context to prevent cross-contamination
            // Each shop tab should have its own feature values
            'productFeatures' => $this->productFeatures,
        ];

        // DIAGNOSIS 2025-11-21: Debug category data flow
        Log::debug('[CATEGORY SYNC DEBUG] savePendingChanges: Category data captured', [
            'product_id' => $this->product->id,
            'active_shop_id' => $this->activeShopId,
            'key' => $currentKey,
            'raw_shopCategories' => $this->activeShopId !== null ? ($this->shopCategories[$this->activeShopId] ?? 'NOT_SET') : 'N/A (default context)',
            'captured_contextCategories' => $this->pendingChanges[$currentKey]['contextCategories'],
            'default_categories' => $this->defaultCategories,
        ]);

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

        // === FEATURES (CONTEXT-ISOLATED SYSTEM) ===
        // FIX 2025-12-03: Load features per context to prevent cross-contamination
        if (isset($changes['productFeatures'])) {
            $this->productFeatures = $changes['productFeatures'];
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
     * Force reset unsaved changes flag (called via delayed dispatch after job completion)
     * FIX 2025-11-25: Handles async Livewire hooks that may re-set hasUnsavedChanges
     */
    public function forceResetUnsavedChanges(): void
    {
        $this->hasUnsavedChanges = false;
        $this->pendingChanges = [];

        Log::info('[JOB COMPLETION] Force reset unsaved changes via delayed dispatch', [
            'product_id' => $this->product?->id,
            'active_shop_id' => $this->activeShopId,
        ]);
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
     * ETAP_08.3: Added ERP context support (Shop-Tab Pattern)
     *
     * Priority:
     * 1. ERP context (activeErpConnectionId !== null) -> compare with ERP external_data
     * 2. Shop context (activeShopId !== null) -> compare with defaultData
     * 3. Default mode -> always 'default'
     *
     * @param string $field
     * @return string 'default'|'inherited'|'same'|'different'
     */
    public function getFieldStatus(string $field): string
    {
        // PRIORITY 1: ERP context (ETAP_08.3)
        if ($this->activeErpConnectionId !== null) {
            return $this->getErpFieldStatusInternal($field);
        }

        // PRIORITY 2: Shop context
        if ($this->activeShopId !== null) {
            return $this->getShopFieldStatusInternal($field);
        }

        // DEFAULT: No context active
        return 'default';
    }

    /**
     * Get field status for Shop context (internal implementation)
     *
     * @param string $field
     * @return string 'default'|'inherited'|'same'|'different'
     */
    protected function getShopFieldStatusInternal(string $field): string
    {
        // SPECIAL CASE: tax_rate - check if override exists (not just value)
        if ($field === 'tax_rate') {
            // If no override set for this shop → inherited (uses default PPM tax_rate)
            if (!isset($this->shopTaxRateOverrides[$this->activeShopId])) {
                return 'inherited';
            }

            // Override exists - check if it matches default
            $overrideValue = $this->shopTaxRateOverrides[$this->activeShopId];
            $defaultValue = $this->defaultData['tax_rate'] ?? $this->tax_rate;

            if ((float) $overrideValue === (float) $defaultValue) {
                return 'same';
            }

            return 'different';
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
     * Get field status for ERP context (ETAP_08.3)
     *
     * Compares current PPM form value with ERP external_data
     *
     * @param string $field
     * @return string 'default'|'inherited'|'same'|'different'
     */
    protected function getErpFieldStatusInternal(string $field): string
    {
        // Get ERP value from external_data cache
        $erpValue = $this->getErpExternalFieldValue($field);
        $ppmValue = $this->defaultData[$field] ?? $this->getCurrentFieldValue($field);

        // Normalize for comparison
        $erpValueNorm = $this->normalizeValueForComparison($erpValue);
        $ppmValueNorm = $this->normalizeValueForComparison($ppmValue);

        // If ERP value is empty, PPM is source of truth -> inherited
        if ($erpValue === null || $erpValue === '') {
            return 'inherited';
        }

        // If values match, they're synchronized
        if ($erpValueNorm === $ppmValueNorm) {
            return 'same';
        }

        // Values are different
        return 'different';
    }

    /**
     * Get field value from ERP external_data cache (ETAP_08.3)
     *
     * Maps PPM field names to ERP external_data structure
     * Different ERPs may have different field structures
     *
     * @param string $field PPM field name
     * @return mixed
     */
    protected function getErpExternalFieldValue(string $field): mixed
    {
        $externalData = $this->erpExternalData['external_data'] ?? [];

        return match ($field) {
            'sku' => $externalData['sku'] ?? null,
            'name' => $externalData['text_fields']['name'] ?? $externalData['name'] ?? null,
            'ean' => $externalData['ean'] ?? null,
            'manufacturer' => $externalData['manufacturer'] ?? null,
            'supplier_code' => $externalData['supplier_code'] ?? null,
            'short_description' => $externalData['text_fields']['short_description'] ?? $externalData['short_description'] ?? null,
            'long_description' => $externalData['text_fields']['description'] ?? $externalData['description'] ?? null,
            'weight' => $externalData['weight'] ?? null,
            'height' => $externalData['height'] ?? null,
            'width' => $externalData['width'] ?? null,
            'length' => $externalData['depth'] ?? $externalData['length'] ?? null,
            'tax_rate' => $externalData['tax_rate'] ?? null,
            'is_active' => $externalData['is_active'] ?? null,
            default => null,
        };
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
            'tax_rate' => $this->activeShopId !== null
                ? ($this->shopTaxRateOverrides[$this->activeShopId] ?? $this->tax_rate)
                : $this->tax_rate,
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
     *
     * FIX 2025-11-18: Enhanced to prevent false positives in pending_fields tracking
     *
     * Handles:
     * - NULL vs empty string ("" vs null)
     * - Numeric vs string ("23" vs 23)
     * - Decimal precision (23.00 vs 23.0)
     * - Boolean vs numeric (1 vs true)
     * - Carbon dates to ISO string
     * - Arrays to sorted string representation
     *
     * @param mixed $value Value to normalize
     * @return mixed Normalized value for strict comparison
     */
    private function normalizeValueForComparison(mixed $value): mixed
    {
        // Carbon dates → ISO string
        if ($value instanceof \Carbon\Carbon) {
            return $value->toIso8601String();
        }

        // NULL and empty string → NULL (treat as same)
        if ($value === '' || $value === null) {
            return null;
        }

        // Boolean → string representation (consistent)
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        // Arrays → sorted string representation (for categories)
        if (is_array($value)) {
            if (empty($value)) {
                return null; // Empty array = null
            }
            sort($value);
            return implode(',', $value);
        }

        // Numeric strings → numeric (for proper comparison)
        if (is_numeric($value)) {
            // If contains decimal point, use float
            if (strpos((string)$value, '.') !== false) {
                return (float)$value;
            }
            // Otherwise use int
            return (int)$value;
        }

        // Return as-is for other types (strings, objects, etc.)
        return $value;
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

            // Sort arrays for proper comparison (numeric sort)
            sort($currentShopCategories, SORT_NUMERIC);
            sort($defaultCategories, SORT_NUMERIC);

            // Empty in shop context => dziedziczenie
            if (empty($currentShopCategories)) {
                return 'inherited';
            }

            // Compare arrays
            $isIdentical = $currentShopCategories === $defaultCategories;

            Log::debug('CATEGORY STATUS COMPARISON (in-memory)', [
                'shop_id' => $this->activeShopId,
                'current_shop_categories' => $currentShopCategories,
                'default_categories' => $defaultCategories,
                'are_identical' => $isIdentical,
                'result' => $isIdentical ? 'same' : 'different',
            ]);

            return $isIdentical ? 'same' : 'different';
        }

        // Fallback: when no in-memory data available (e.g., first load), consult DB
        // UPDATED 2025-10-13: Use new architecture (shop_id in product_categories)
        if ($this->product && $this->product->exists) {
            try {
                $shopCategories = $this->product->categoriesForShop($this->activeShopId, false)->pluck('id')->sort(SORT_NUMERIC)->values()->toArray();
                $defaultCategories = $this->product->categories()->pluck('id')->sort(SORT_NUMERIC)->values()->toArray();

                if (empty($shopCategories)) {
                    return 'inherited'; // No per-shop categories, inherits from default
                }

                $isIdentical = $shopCategories === $defaultCategories;

                Log::debug('CATEGORY STATUS COMPARISON (DB)', [
                    'shop_id' => $this->activeShopId,
                    'product_id' => $this->product->id,
                    'shop_categories' => $shopCategories,
                    'default_categories' => $defaultCategories,
                    'are_identical' => $isIdentical,
                    'result' => $isIdentical ? 'same' : 'different',
                ]);

                return $isIdentical ? 'same' : 'different';
            } catch (\Throwable $e) {
                Log::warning('Failed to get category status from DB', [
                    'error' => $e->getMessage(),
                    'product_id' => $this->product->id,
                    'shop_id' => $this->activeShopId,
                ]);
                // Continue to final fallback
            }
        }

        // Final fallback: compare arrays (handles create mode gracefully)
        $currentShopCategories = $this->getCategoriesForContext($this->activeShopId);
        $defaultCategories = $this->getCategoriesForContext(null);
        sort($currentShopCategories, SORT_NUMERIC);
        sort($defaultCategories, SORT_NUMERIC);
        if (empty($currentShopCategories)) {
            return 'inherited';
        }
        $isIdentical = $currentShopCategories === $defaultCategories;

        Log::debug('CATEGORY STATUS COMPARISON (final fallback)', [
            'shop_id' => $this->activeShopId,
            'current_shop_categories' => $currentShopCategories,
            'default_categories' => $defaultCategories,
            'are_identical' => $isIdentical,
            'result' => $isIdentical ? 'same' : 'different',
        ]);

        return $isIdentical ? 'same' : 'different';
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
     * FIX 2025-11-19 BUG #1: Add pending sync check (PRIORITY 1 before status check)
     */
    public function getCategoryStatusIndicator(): array
    {
        // PRIORITY 0: Hard pending sync flag on ProductShopData (DB) - overrides everything
        if ($this->activeShopId !== null && $this->isPendingSyncForShop($this->activeShopId, 'categories')) {
            return [
                'show' => true,
                'text' => 'Oczekuje na synchronizacj�',
                'class' => 'status-label-pending'
            ];
        }

        // PRIORITY 1: Check if categories have pending sync (highest priority)
        if ($this->activeShopId !== null) {
            $pendingChanges = $this->getPendingChangesForShop($this->activeShopId);

            // Check if 'Kategorie' is in pending changes list
            if (in_array('Kategorie', $pendingChanges)) {
                return [
                    'show' => true,
                    'text' => 'Oczekuje na synchronizację',
                    // FIX #4 2025-11-21: Use user-requested class name
                    'class' => 'status-label-pending'
                ];
            }
        }

        // PRIORITY 2: Check category status (inherited, same, different)
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
                    // FIX #4 2025-11-21: User-requested class (already correct!)
                    'class' => 'text-green-600 dark:text-green-400 text-xs category-status-same'
                ];
            case 'different':
                return [
                    'show' => true,
                    'text' => '(unikalne dla tego sklepu)',
                    // FIX #4 2025-11-21: User-requested class (already correct!)
                    'class' => 'text-orange-600 dark:text-orange-400 text-xs font-medium category-status-different'
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
     * FIX #4 2025-11-21: Check if category editing should be disabled
     * FIX #6 2025-11-21: Use sync_status instead of pending changes detection
     * FIX #7 2025-11-21: Remove sync_status check to prevent race condition
     *
     * Block category editing when:
     * - Form is currently saving (isSaving = true)
     *
     * REMOVED: sync_status check (caused race condition)
     * Problem: savePendingChangesToShop() sets sync_status='pending' → save() → Livewire re-render
     *          → isCategoryEditingDisabled() queries DB → gets 'pending' → disables checkboxes
     * Solution: Only block during $this->isSaving (actual form save operation)
     *          Ignore sync_status (Job queue status - user can edit while Job processes)
     *
     * @return bool True if category editing should be disabled
     */
    public function isCategoryEditingDisabled(): bool
    {
        // FIX 2025-11-22 (CATEGORY PANEL REGRESSION FIX):
        // SIMPLIFIED to check ONLY $this->isSaving (removed sync_status check)
        // REASON: sync_status check caused race condition - categories permanently disabled
        // when Job is pending. User should be able to edit categories immediately after
        // save completes, even if background Job is still processing.
        //
        // Related: FIX #7 (category_checkbox_flash_fix_2025-11-21.md)
        // Architecture: Form submission state ($this->isSaving) controls UI disabled state
        // Background job state (sync_status in DB) tracks async processing separately

        return $this->isSaving;
    }

    /**
     * Get CSS classes for categories section based on status
     */
    public function getCategoryClasses(): string
    {
        // ROLLBACK 2025-11-24: Use getCategoryStatus() (same as Badge for synchronization)
        // This makes CSS class react the SAME WAY as badge indicator
        $status = $this->getCategoryStatus();
        $baseClasses = 'p-4 rounded-lg transition-all duration-200';

        // FIX #4 2025-11-21: Add pending state styling
        if ($this->isCategoryEditingDisabled()) {
            return $baseClasses . ' category-status-pending border-yellow-600 bg-yellow-900/20 opacity-75 cursor-not-allowed';
        }

        switch ($status) {
            case 'default':
                return $baseClasses . ' border border-gray-700 bg-gray-800';
            case 'inherited':
                // CSS class defined in components.css
                return $baseClasses . ' category-status-inherited';
            case 'same':
                // CSS class defined in components.css
                return $baseClasses . ' category-status-same';
            case 'different':
                // CSS class defined in components.css
                return $baseClasses . ' category-status-different';
            default:
                return $baseClasses . ' border border-gray-700 bg-gray-800';
        }
    }

    /**
     * Get CSS classes for field based on 3-level status system + pending sync
     */
    public function getFieldClasses(string $field): string
    {
        // Taller inputs (py-2.5) with proper text padding (px-4) from edges
        $baseClasses = 'block w-full rounded-md shadow-sm focus:ring-orange-500 sm:text-sm transition-all duration-200 px-4 py-2.5';

        // PRIORITY 0: Check if field has pending ERP sync (ETAP_08.4 FIX)
        // Uses existing .field-status-different class from components.css
        if ($this->activeErpConnectionId !== null && $this->isErpFieldPending($field)) {
            return $baseClasses . ' field-status-different';
        }

        // PRIORITY 1: Check if field has pending sync (highest priority visual indicator)
        if ($this->activeShopId !== null && $this->isPendingSyncForShop($this->activeShopId, $field)) {
            return $baseClasses . ' field-pending-sync';
        }

        // PRIORITY 2: Field status (inherited, same, different)
        $status = $this->getFieldStatus($field);

        switch ($status) {
            case 'default':
                // Normal mode - standard styling (DARK THEME ONLY)
                return $baseClasses . ' border-gray-600 bg-gray-700 text-white focus:border-orange-500';

            case 'inherited':
                // Inherited - CSS class defined in product-form.css
                return $baseClasses . ' field-status-inherited';

            case 'same':
                // Same as default - CSS class defined in product-form.css
                return $baseClasses . ' field-status-same';

            case 'different':
                // Different from default - CSS class defined in product-form.css
                return $baseClasses . ' field-status-different';

            default:
                return $baseClasses . ' border-gray-600 bg-gray-700 text-white focus:border-orange-500';
        }
    }

    /**
     * Get status indicator for field (visual badge) + pending sync
     */
    public function getFieldStatusIndicator(string $field): array
    {
        // PRIORITY 0: Check if field has pending ERP sync (ETAP_08.4 FIX)
        if ($this->activeErpConnectionId !== null && $this->isErpFieldPending($field)) {
            return [
                'show' => true,
                'text' => 'Własne',
                'class' => 'status-label-different'
            ];
        }

        // PRIORITY 1: Check if field has pending sync (highest priority)
        if ($this->activeShopId !== null && $this->isPendingSyncForShop($this->activeShopId, $field)) {
            return [
                'show' => true,
                'text' => 'Oczekuje na synchronizację',
                'class' => 'pending-sync-badge'
            ];
        }

        // PRIORITY 2: Field status (inherited, same, different)
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
                    'class' => 'status-label-inherited'
                ];

            case 'same':
                return [
                    'show' => true,
                    'text' => 'Zgodne',
                    'class' => 'status-label-same'
                ];

            case 'different':
                return [
                    'show' => true,
                    'text' => 'Własne',
                    'class' => 'status-label-different'
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
     * Check if field has pending sync for specific shop
     *
     * @param int $shopId Shop ID to check
     * @param string $fieldName Field name to check (e.g., 'name', 'short_description')
     * @return bool True if field has pending sync status
     */
    public function isPendingSyncForShop(int $shopId, string $fieldName): bool
    {
        if (!$this->product || !$this->product->exists) {
            return false;
        }

        try {
            $shopData = $this->product->shopData()
                ->where('shop_id', $shopId)
                ->first();

            if (!$shopData) {
                return false;
            }

            // Check if sync_status is 'pending'
            return $shopData->sync_status === \App\Models\ProductShopData::STATUS_PENDING;

        } catch (\Throwable $e) {
            Log::warning('Failed to check pending sync status', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'field_name' => $fieldName,
                'error' => $e->getMessage(),
            ]);
            return false;
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
     *
     * ETAP_08.4: Added ERP context tracking (Shop-Tab pattern)
     */
    public function updated($propertyName): void
    {
        // FIX 2025-11-25: Skip during data loading (pullShopDataInstant, loadShopDataToForm)
        // Prevents false positive hasUnsavedChanges after job completion
        if ($this->isLoadingData) {
            return;
        }

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
            'originalFormData',
            'isLoadingData',
            // FIX 2025-11-25: Job tracking properties (changed by Alpine.js clearJob())
            'activeJobId',
            'activeJobStatus',
            'activeJobType',
            'jobResult',
            'jobCreatedAt',
            // ERP tab internal properties
            'activeErpTab',
            'activeErpConnectionId',
            'erpExternalData',
            'erpDefaultData',
            'syncingToErp',
            'loadingErpData',
        ];

        if (!in_array($propertyName, $skipProperties)) {
            $this->markFormAsChanged();

            // ETAP_08.4: Track ERP changes when in ERP context (Shop-Tab pattern)
            if ($this->activeErpConnectionId !== null && $this->isEditMode && $this->product) {
                $this->trackErpFieldChange($propertyName);
            }

            Log::info('Form field updated', [
                'property' => $propertyName,
                'value' => $this->$propertyName ?? 'null',
                'shop_id' => $this->activeShopId,
                'erp_connection_id' => $this->activeErpConnectionId,
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
        // Use existing pending-changes flow (handles default/shop + job dispatch)
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

                    // CRITICAL FIX (Bug 2): Mark all associated shops as 'pending' after updating default data
                    // When user edits "Dane domyślne", shops need to be re-synced to reflect new default data
                    $shopsMarkedPending = \App\Models\ProductShopData::where('product_id', $this->product->id)
                        ->where('sync_status', '!=', 'disabled') // Don't change disabled shops
                        ->update(['sync_status' => 'pending']);

                    if ($shopsMarkedPending > 0) {
                        Log::info('Marked shops as pending after default data update', [
                            'product_id' => $this->product->id,
                            'shops_marked' => $shopsMarkedPending,
                        ]);
                    }

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

                // FIXED: Delete shops marked for removal (CONSOLIDATED 2025-10-13)
                if (!empty($this->shopsToRemove) && $this->product) {
                    foreach ($this->shopsToRemove as $shopId) {
                        // CONSOLIDATED: ProductShopData now contains all sync tracking
                        // No need to delete ProductSyncStatus separately (deprecated table)

                        // Delete ProductShopData record (includes all sync tracking)
                        $deletedShopData = \App\Models\ProductShopData::where('product_id', $this->product->id)
                            ->where('shop_id', $shopId)
                            ->delete();

                        Log::info('Deleted shop association from product', [
                            'product_id' => $this->product->id,
                            'shop_id' => $shopId,
                            'deleted_shop_data' => $deletedShopData,
                        ]);
                    }
                    // Clear the pending removals list
                    $this->shopsToRemove = [];

                    // Dispatch event to notify ProductList about shop changes
                    $this->dispatch('shops-updated', ['productId' => $this->product->id]);
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
        // [FAZA 5.2 DEBUG SAVE 2025-11-14] Layer 4: Save Method Entry
        Log::debug('[FAZA 5.2 DEBUG SAVE] saveShopSpecificData CALLED', [
            'product_id' => $this->product?->id,
            'active_shop_id' => $this->activeShopId,
            'shopTaxRateOverrides_array' => $this->shopTaxRateOverrides,
            'override_for_this_shop' => $this->shopTaxRateOverrides[$this->activeShopId] ?? 'NOT SET',
        ]);

        if (!$this->product || $this->activeShopId === null) {
            Log::warning('[FAZA 5.2 DEBUG SAVE] saveShopSpecificData ABORTED - missing product or activeShopId', [
                'product_exists' => $this->product !== null,
                'activeShopId' => $this->activeShopId,
            ]);
            return;
        }

        // Find existing shop data or create new
        $productShopData = \App\Models\ProductShopData::firstOrNew([
            'product_id' => $this->product->id,
            'shop_id' => $this->activeShopId,
        ]);

        // [FAZA 5.2 DEBUG SAVE 2025-11-14] Before fill()
        Log::debug('[FAZA 5.2 DEBUG SAVE] BEFORE $productShopData->fill()', [
            'shop_id' => $this->activeShopId,
            'existing_tax_rate_override' => $productShopData->tax_rate_override,
            'new_tax_rate_override_from_property' => $this->shopTaxRateOverrides[$this->activeShopId] ?? 'NOT SET',
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

            // === TAX RATE OVERRIDE (FAZA 5.2 - 2025-11-14) ===
            // Save shop-specific tax rate override (NULL = use product default)
            'tax_rate_override' => $this->shopTaxRateOverrides[$this->activeShopId] ?? null,

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
                'tax_rate_override' => $this->shopTaxRateOverrides[$this->activeShopId] ?? null, // FAZA 5.2
            ])),
        ]);

        // [FAZA 5.2 DEBUG SAVE 2025-11-14] Layer 5: Before Database Write
        Log::debug('[FAZA 5.2 DEBUG SAVE] BEFORE $productShopData->save()', [
            'shop_id' => $this->activeShopId,
            'old_tax_rate_override' => $productShopData->getOriginal('tax_rate_override'),
            'new_tax_rate_override' => $productShopData->tax_rate_override,
            'is_dirty' => $productShopData->isDirty('tax_rate_override'),
            'all_dirty_fields' => $productShopData->getDirty(),
        ]);

        $productShopData->save();

        // [FAZA 5.2 DEBUG SAVE 2025-11-14] After Database Write
        Log::debug('[FAZA 5.2 DEBUG SAVE] AFTER $productShopData->save()', [
            'shop_id' => $this->activeShopId,
            'saved_tax_rate_override' => $productShopData->fresh()->tax_rate_override,
            'product_shop_data_id' => $productShopData->id,
        ]);

        Log::info('[FAZA 5.2] Shop-specific data saved', [
            'product_id' => $this->product->id,
            'shop_id' => $this->activeShopId,
            'shop_data_id' => $productShopData->id,
            'tax_rate_override' => $this->shopTaxRateOverrides[$this->activeShopId] ?? 'NULL (use default)',
            'user_id' => auth()->id(),
        ]);

        // FIX CRITICAL BUG (2025-11-06): Auto-dispatch sync job after shop data save
        // Problem: User saves changes in shop tab -> data saved to ProductShopData with 'pending'
        //          BUT sync job was never created -> changes never reach PrestaShop!
        // Solution: Automatically dispatch sync job when shop data is saved
        // USER_ID FIX (2025-11-07): Pass auth()->id() to capture user who triggered sync
        try {
            $shop = \App\Models\PrestaShopShop::find($this->activeShopId);

            if ($shop && $shop->connection_status === 'connected' && $shop->is_active) {
                SyncProductToPrestaShop::dispatch($this->product, $shop, auth()->id());

                Log::info('Auto-dispatched sync job after shop data save', [
                    'product_id' => $this->product->id,
                    'shop_id' => $this->activeShopId,
                    'shop_name' => $shop->name,
                    'trigger' => 'saveShopSpecificData',
                ]);
            } else {
                Log::warning('Sync job NOT dispatched - shop not connected or inactive', [
                    'product_id' => $this->product->id,
                    'shop_id' => $this->activeShopId,
                    'shop_status' => $shop?->connection_status ?? 'not_found',
                    'shop_active' => $shop?->is_active ?? false,
                ]);
            }
        } catch (\Exception $e) {
            // Non-blocking error - data is saved, but sync will need manual trigger
            Log::error('Failed to auto-dispatch sync job after shop data save', [
                'product_id' => $this->product->id,
                'shop_id' => $this->activeShopId,
                'error' => $e->getMessage(),
            ]);
        }
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
                    // USER_ID FIX (2025-11-07): Pass auth()->id() to capture user who triggered sync
                    SyncProductToPrestaShop::dispatch($this->product, $shop, auth()->id());

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
                // FIX 2025-11-25: Use 'info' not 'success' - actual success shown by Alpine panel after job completes
                $this->dispatch('info', message: "Zaplanowano synchronizację produktu na {$syncResults['success']} sklepach");
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
            // USER_ID FIX (2025-11-07): Pass auth()->id() to capture user who triggered sync
            SyncProductToPrestaShop::dispatch($this->product, $shop, auth()->id());

            // FIX 2025-11-25: Use 'info' not 'success' - actual success shown by Alpine panel after job completes
            $this->dispatch('info', message: "Zaplanowano synchronizację produktu ze sklepem: {$shop->name}");

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

    /**
     * Detect active sync job on mount (FIX 2025-11-25)
     *
     * When user re-enters product form while sync job is running,
     * this method restores the job tracking state from database.
     *
     * Checks product_shop_data.sync_status = 'pending' for any linked shop
     * and sets activeJobStatus, activeJobType, jobCreatedAt accordingly.
     *
     * @return void
     */
    private function detectActiveJobOnMount(): void
    {
        if (!$this->product || !$this->product->exists) {
            return;
        }

        try {
            // Find any shop with pending sync status for this product
            $pendingShopData = $this->product->shopData()
                ->where('sync_status', 'pending')
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($pendingShopData) {
                // Active sync job found - restore job tracking state
                $this->activeJobStatus = 'pending';
                $this->activeJobType = 'sync';
                $this->activeShopId = $pendingShopData->shop_id;

                // Use updated_at as job start time (when sync_status was set to pending)
                $this->jobCreatedAt = $pendingShopData->updated_at->toIso8601String();

                Log::info('[MOUNT] Detected active sync job - restoring job tracking state', [
                    'product_id' => $this->product->id,
                    'shop_id' => $pendingShopData->shop_id,
                    'sync_status' => $pendingShopData->sync_status,
                    'jobCreatedAt' => $this->jobCreatedAt,
                    'activeJobStatus' => $this->activeJobStatus,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[MOUNT] Failed to detect active job', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check status of active background job (ETAP_13 - 2025-11-17)
     *
     * Called by wire:poll.5s from Blade for real-time JOB monitoring
     *
     * Queries jobs table → checks failed_jobs if completed → updates reactive properties
     * Dispatches Livewire events for UI feedback (Alpine.js integration)
     *
     * @return void
     */
    public function checkJobStatus(): void
    {
        // ETAP_13 FIX (2025-11-18): Support job tracking WITHOUT activeJobId
        // (Bulk jobs dispatch multiple jobs → no single job ID to track)

        // No active job - skip monitoring
        if (!$this->activeJobStatus || $this->activeJobStatus === 'completed' || $this->activeJobStatus === 'failed') {
            return;
        }

        // FOR BULK SYNC JOBS (multiple jobs, no single ID to track)
        if ($this->activeJobType === 'sync' && !$this->activeJobId) {
            $this->checkBulkSyncJobStatus();
            return;
        }

        // FOR PULL JOBS (multiple jobs, no single ID to track)
        if ($this->activeJobType === 'pull' && !$this->activeJobId) {
            $this->checkBulkPullJobStatus();
            return;
        }

        // FOR SINGLE JOBS (with job ID tracking)
        if (!$this->activeJobId) {
            return; // No job ID, can't track via jobs table
        }

        try {
            // Query jobs table for active job
            $job = \Illuminate\Support\Facades\DB::table('jobs')
                ->where('id', $this->activeJobId)
                ->first();

            if (!$job) {
                // Job not in queue - either completed successfully or failed
                $failed = \Illuminate\Support\Facades\DB::table('failed_jobs')
                    ->where('id', $this->activeJobId)
                    ->first();

                if ($failed) {
                    // Job failed permanently
                    $this->activeJobStatus = 'failed';
                    $this->jobResult = 'error';

                    // Extract first 200 chars of exception for user feedback
                    $errorMessage = Str::limit($failed->exception ?? 'Unknown error', 200);
                    $this->dispatch('job-failed', message: $errorMessage);

                    Log::warning('Background job failed', [
                        'job_id' => $this->activeJobId,
                        'job_type' => $this->activeJobType,
                        'product_id' => $this->product?->id,
                        'error_excerpt' => $errorMessage,
                    ]);
                } else {
                    // Job completed successfully (not in jobs table, not in failed_jobs)
                    $this->activeJobStatus = 'completed';
                    $this->jobResult = 'success';

                    $this->dispatch('job-completed');

                    Log::info('Background job completed successfully', [
                        'job_id' => $this->activeJobId,
                        'job_type' => $this->activeJobType,
                        'product_id' => $this->product?->id,
                    ]);

                    // FIX 2025-11-20: Auto-refresh categories after job completion
                    // FIX 2025-11-21: Changed to instant pull (no JOB dispatch) for faster UI updates
                    // User Request: "Pull data z presty instant, bez tworzenia JOB-a, w tle automatycznie"
                    if ($this->activeJobType === 'pull' && $this->activeShopId) {
                        // Pull job completed - reload fresh data including categories (instant)
                        $this->pullShopDataInstant($this->activeShopId);
                    }

                    if ($this->activeJobType === 'sync' && $this->activeShopId) {
                        // Sync job completed - pull fresh data from PrestaShop to verify sync (instant)
                        $this->pullShopDataInstant($this->activeShopId);
                    }

                    // FIX 2025-11-25: Reset hasUnsavedChanges after successful job completion
                    // CRITICAL: Use delayed dispatch to ensure reset happens AFTER all Livewire hooks
                    $this->hasUnsavedChanges = false;
                    $this->pendingChanges = [];

                    // Schedule delayed reset to catch any async hook side-effects
                    $this->dispatch('delayed-reset-unsaved-changes');
                }

                // Auto-clear job status after 5s (dispatch to Alpine.js)
                $this->dispatch('auto-clear-job-status', delay: 5000);
                return;
            }

            // Job still in queue - mark as processing
            $this->activeJobStatus = 'processing';

        } catch (\Exception $e) {
            Log::error('Error checking job status', [
                'job_id' => $this->activeJobId,
                'error' => $e->getMessage(),
                'trace' => $e->getFile() . ':' . $e->getLine(),
            ]);

            // Mark as failed on exception
            $this->activeJobStatus = 'failed';
            $this->jobResult = 'error';
            $this->dispatch('job-failed', message: 'Error checking job status');
        }
    }

    /**
     * Check bulk sync job status by monitoring shop data sync status
     * (Used when activeJobId not set - multi-job dispatch scenario)
     *
     * @return void
     */
    protected function checkBulkSyncJobStatus(): void
    {
        if (!$this->product) {
            return;
        }

        try {
            // FIX 2025-11-27: Use fresh DB query instead of load() which may use cached data
            // Previous bug: load() didn't actually refresh already-loaded relationships

            // FIX 2025-11-27: For single-shop sync, only check that specific shop
            // (Previous logic checked ALL shops which failed when other shops weren't synced)
            if ($this->syncJobShopId) {
                // Single shop sync - query database directly for fresh data
                $targetShopData = ProductShopData::where('product_id', $this->product->id)
                    ->where('shop_id', $this->syncJobShopId)
                    ->first();

                Log::debug('[SINGLE SHOP SYNC] Checking sync status', [
                    'product_id' => $this->product->id,
                    'shop_id' => $this->syncJobShopId,
                    'sync_status' => $targetShopData?->sync_status,
                    'expected' => ProductShopData::STATUS_SYNCED,
                ]);

                if (!$targetShopData) {
                    Log::warning('[SINGLE SHOP SYNC] Shop data not found', [
                        'product_id' => $this->product->id,
                        'shop_id' => $this->syncJobShopId,
                    ]);
                    return;
                }

                if ($targetShopData->sync_status === ProductShopData::STATUS_SYNCED) {
                    // Single shop synced - mark as completed
                    $this->activeJobStatus = 'completed';
                    $this->jobResult = 'success';

                    // FIX 2025-11-27: Dispatch job-completed event for Alpine.js to stop polling
                    $this->dispatch('job-completed');

                    // Clear cache for this shop
                    unset($this->loadedShopData[$this->syncJobShopId]);

                    // Auto-refresh data from PrestaShop
                    $this->pullShopDataInstant($this->syncJobShopId);

                    // Also refresh category tree (deleted categories need to disappear)
                    $this->refreshCategoriesFromShop();

                    // FIX 2025-11-27: Reload shopCategories from database to reflect inline category changes
                    // Without this, UI shows stale data after job completion (tempId not replaced with real ID)
                    $this->loadShopCategories($this->syncJobShopId);

                    // Reset state
                    $this->hasUnsavedChanges = false;
                    $this->pendingChanges = [];
                    $this->dispatch('delayed-reset-unsaved-changes');

                    Log::info('[SINGLE SHOP SYNC] Shop synchronized with auto-refresh', [
                        'product_id' => $this->product->id,
                        'shop_id' => $this->syncJobShopId,
                        'elapsed_seconds' => $this->jobCreatedAt ? now()->diffInSeconds($this->jobCreatedAt) : null,
                    ]);

                    // Clear the single shop tracking
                    $this->syncJobShopId = null;
                } else {
                    // Still syncing
                    Log::debug('[SINGLE SHOP SYNC] Still syncing', [
                        'product_id' => $this->product->id,
                        'shop_id' => $this->syncJobShopId,
                        'sync_status' => $targetShopData->sync_status,
                    ]);
                }
                return;
            }

            // BULK SYNC: Refresh product relationship and get all connected shops
            // FIX 2025-11-27: Force reload from database to get fresh sync_status
            $this->product->load('shopData.shop');
            $connectedShops = $this->product->shopData->filter(function ($shopData) {
                return $shopData->shop && $shopData->shop->is_active && $shopData->shop->connection_status === 'connected';
            });

            if ($connectedShops->isEmpty()) {
                // No shops to sync - mark as completed
                $this->activeJobStatus = 'completed';
                $this->jobResult = 'success';
                $this->dispatch('job-completed');

                Log::info('[ETAP_13 BULK SYNC] No connected shops found', [
                    'product_id' => $this->product->id,
                ]);
                return;
            }

            // Check if ALL connected shops are synchronized
            // FIX 2025-11-18: Use ProductShopData::STATUS_SYNCED constant (was 'synchronized' - TYPO!)
            $allSynchronized = $connectedShops->every(function ($shopData) {
                return $shopData->sync_status === ProductShopData::STATUS_SYNCED;
            });

            if ($allSynchronized) {
                // All shops synced - mark as completed
                $this->activeJobStatus = 'completed';
                $this->jobResult = 'success';

                // FIX 2025-11-27: Dispatch job-completed event for Alpine.js to stop polling
                $this->dispatch('job-completed');

                // FIX 2025-11-18 (#9.1): Clear loadedShopData cache after sync
                // (getPendingChangesForShop() compares DB vs loadedShopData - stale cache = false "Oczekujące zmiany")
                foreach ($connectedShops as $shopData) {
                    unset($this->loadedShopData[$shopData->shop_id]);
                }

                // FIX 2025-11-20: Auto-refresh categories after bulk sync completion
                // User Request: "Po wykonaniu JOB autorefresh, ponowne pobranie kategorii"
                // FIX 2025-11-25: Use pullShopDataInstant() NOT pullShopData()!
                // pullShopData() calls savePendingChangesToShop() which dispatches ANOTHER JOB!
                if ($this->activeShopId) {
                    // Pull fresh data from PrestaShop INSTANTLY (no new job)
                    $this->pullShopDataInstant($this->activeShopId);

                    // FIX 2025-11-27: Reload shopCategories from database to reflect inline category changes
                    $this->refreshCategoriesFromShop();
                    $this->loadShopCategories($this->activeShopId);
                }

                // FIX 2025-11-25: Reset hasUnsavedChanges after successful bulk sync completion
                // CRITICAL: Use delayed dispatch to ensure reset happens AFTER all Livewire hooks
                // (Livewire Client may send follow-up requests that re-set hasUnsavedChanges)
                $this->hasUnsavedChanges = false;
                $this->pendingChanges = [];

                // Schedule delayed reset to catch any async hook side-effects
                $this->dispatch('delayed-reset-unsaved-changes');

                Log::info('[ETAP_13 BULK SYNC] All shops synchronized with auto-refresh', [
                    'product_id' => $this->product->id,
                    'shops_count' => $connectedShops->count(),
                    'elapsed_seconds' => $this->jobCreatedAt ? now()->diffInSeconds($this->jobCreatedAt) : null,
                    'cleared_cache_shops' => $connectedShops->pluck('shop_id')->all(),
                    'auto_refreshed_shop' => $this->activeShopId,
                ]);
            } else {
                // Still syncing - log current status
                $syncedCount = $connectedShops->filter(fn($sd) => $sd->sync_status === ProductShopData::STATUS_SYNCED)->count();

                Log::debug('[ETAP_13 BULK SYNC] Still syncing', [
                    'product_id' => $this->product->id,
                    'synced' => $syncedCount,
                    'total' => $connectedShops->count(),
                    'statuses' => $connectedShops->pluck('sync_status', 'shop_id')->toArray(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('[ETAP_13 BULK SYNC] Error checking bulk sync status', [
                'product_id' => $this->product?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getFile() . ':' . $e->getLine(),
            ]);

            // Don't mark as failed - might be temporary DB issue
        }
    }

    /**
     * Check bulk pull job status by monitoring shop data sync status
     * (Used when activeJobId not set - multi-job dispatch scenario)
     *
     * @return void
     */
    protected function checkBulkPullJobStatus(): void
    {
        if (!$this->product) {
            return;
        }

        try {
            // Refresh shop data to get latest sync statuses
            $this->product->load('shopData.shop');

            // Get all connected shops
            $connectedShops = $this->product->shopData->filter(function ($shopData) {
                return $shopData->shop && $shopData->shop->is_active && $shopData->shop->connection_status === 'connected';
            });

            if ($connectedShops->isEmpty()) {
                // No shops to pull from - mark as completed
                $this->activeJobStatus = 'completed';
                $this->jobResult = 'success';

                Log::info('[ETAP_13 BULK PULL] No connected shops found', [
                    'product_id' => $this->product->id,
                ]);
                return;
            }

            // Check if ALL connected shops have been pulled (sync_status updated recently)
            // For pull jobs, we consider it complete when all shops have fresh data
            // FIX 2025-11-18: Use ProductShopData::STATUS_PENDING constant for consistency
            $allPulled = $connectedShops->every(function ($shopData) {
                // Consider "pulled" if sync_status is NOT 'pending' (it's been updated)
                return $shopData->sync_status !== ProductShopData::STATUS_PENDING;
            });

            if ($allPulled) {
                // All shops pulled - mark as completed
                $this->activeJobStatus = 'completed';
                $this->jobResult = 'success';

                // FIX 2025-11-25: Reset hasUnsavedChanges after successful bulk pull completion
                // CRITICAL: Use delayed dispatch to ensure reset happens AFTER all Livewire hooks
                $this->hasUnsavedChanges = false;
                $this->pendingChanges = [];

                // Schedule delayed reset to catch any async hook side-effects
                $this->dispatch('delayed-reset-unsaved-changes');

                Log::info('[ETAP_13 BULK PULL] All shops pulled', [
                    'product_id' => $this->product->id,
                    'shops_count' => $connectedShops->count(),
                    'elapsed_seconds' => $this->jobCreatedAt ? now()->diffInSeconds($this->jobCreatedAt) : null,
                ]);
            } else {
                // Still pulling - log current status
                $pulledCount = $connectedShops->filter(fn($sd) => $sd->sync_status !== ProductShopData::STATUS_PENDING)->count();

                Log::debug('[ETAP_13 BULK PULL] Still pulling', [
                    'product_id' => $this->product->id,
                    'pulled' => $pulledCount,
                    'total' => $connectedShops->count(),
                    'statuses' => $connectedShops->pluck('sync_status', 'shop_id')->toArray(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('[ETAP_13 BULK PULL] Error checking bulk pull status', [
                'product_id' => $this->product?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getFile() . ':' . $e->getLine(),
            ]);

            // Don't mark as failed - might be temporary DB issue
        }
    }

    /**
     * Sync product to SINGLE shop (PPM → PrestaShop) - ETAP_13
     *
     * Sidepanel "Aktualizuj aktualny sklep" button (per-shop)
     * Dispatches SyncProductToPrestaShop for single shop + job tracking
     *
     * FIX 2025-11-18: Metoda nie istniała - buttons wywoływały undefined method
     *
     * @param int $shopId
     * @return void
     */
    public function syncShop(int $shopId): void
    {
        if (!$this->product) {
            $this->dispatch('error', message: 'Produkt nie istnieje');
            return;
        }

        // ETAP_13: Check for active job (anti-duplicate)
        if ($this->hasActiveSyncJob()) {
            $this->dispatch('warning', message: 'Synchronizacja już w trakcie. Poczekaj na zakończenie.');
            return;
        }

        // FIX 2025-11-18 (#4): TARGETED save - only current context, DON'T mark all shops
        // (Prevents "Dodaj do sklepu" from marking ALL shops as pending)
        try {
            // 1. Capture current form state to pendingChanges
            $this->savePendingChanges();

            // 2. Save ONLY current context (default OR shop) - DON'T save all contexts
            if ($this->activeShopId === null) {
                // User is in "Dane domyślne" tab - save to Product WITHOUT marking all shops
                if (isset($this->pendingChanges['default'])) {
                    $this->savePendingChangesToProduct($this->pendingChanges['default'], $markShopsAsPending = false);
                    unset($this->pendingChanges['default']);
                }
            } else {
                // User is in specific shop tab - save to ProductShopData (doesn't affect other shops)
                if (isset($this->pendingChanges[$this->activeShopId])) {
                    $this->savePendingChangesToShop($this->activeShopId, $this->pendingChanges[$this->activeShopId]);
                    unset($this->pendingChanges[$this->activeShopId]);
                }
            }

            $this->dispatch('$commit');

            Log::info('[ETAP_13 AUTO-SAVE] Targeted save completed (single shop sync)', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'active_shop_id' => $this->activeShopId,
                'context' => $this->activeShopId === null ? 'default' : "shop:{$this->activeShopId}",
            ]);
        } catch (\Exception $e) {
            Log::error('[ETAP_13 AUTO-SAVE] Failed to save pending changes', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('error', message: 'Nie udało się zapisać zmian przed synchronizacją: ' . $e->getMessage());
            return;
        }

        // Get shop
        $shop = \App\Models\PrestaShopShop::find($shopId);

        if (!$shop || !$shop->is_active || $shop->connection_status !== 'connected') {
            $this->dispatch('warning', message: 'Sklep nie jest aktywny lub nie jest połączony');
            return;
        }

        // FIX 2025-11-27: Physical category deletion from PrestaShop (before product sync)
        // pendingDeleteCategories contains PrestaShop category IDs to DELETE ENTIRELY from PrestaShop
        $contextKey = (string) $shopId;
        $categoriesToDelete = $this->pendingDeleteCategories[$contextKey] ?? [];

        if (!empty($categoriesToDelete)) {
            Log::info('[CATEGORY DELETE] Starting physical deletion of categories from PrestaShop', [
                'shop_id' => $shopId,
                'shop_name' => $shop->name,
                'categories_to_delete' => $categoriesToDelete,
            ]);

            try {
                // Get PrestaShop client for this shop
                $clientFactory = app(\App\Services\PrestaShop\PrestaShopClientFactory::class);
                $client = $clientFactory->create($shop);

                $deletedCount = 0;
                $failedCategories = [];

                foreach ($categoriesToDelete as $categoryId) {
                    try {
                        $client->deleteCategory((int) $categoryId);
                        $deletedCount++;

                        Log::info('[CATEGORY DELETE] Category deleted successfully', [
                            'category_id' => $categoryId,
                            'shop_id' => $shopId,
                        ]);
                    } catch (\App\Exceptions\PrestaShopAPIException $e) {
                        // Log but continue with other deletions
                        $failedCategories[] = [
                            'id' => $categoryId,
                            'error' => $e->getMessage(),
                        ];

                        Log::error('[CATEGORY DELETE] Failed to delete category', [
                            'category_id' => $categoryId,
                            'shop_id' => $shopId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Clear pending delete categories for this shop (even if some failed)
                unset($this->pendingDeleteCategories[$contextKey]);

                if ($deletedCount > 0) {
                    $this->dispatch('success', message: "Usunieto $deletedCount kategorii z PrestaShop");
                }

                if (!empty($failedCategories)) {
                    $failedIds = implode(', ', array_column($failedCategories, 'id'));
                    $this->dispatch('warning', message: "Nie udalo sie usunac kategorii: $failedIds");
                }

                Log::info('[CATEGORY DELETE] Category deletion completed', [
                    'shop_id' => $shopId,
                    'deleted_count' => $deletedCount,
                    'failed_count' => count($failedCategories),
                    'failed_categories' => $failedCategories,
                ]);

            } catch (\Exception $e) {
                Log::error('[CATEGORY DELETE] Fatal error during category deletion', [
                    'shop_id' => $shopId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getFile() . ':' . $e->getLine(),
                ]);

                $this->dispatch('error', message: 'Blad podczas usuwania kategorii: ' . $e->getMessage());
                // Don't return - continue with sync even if deletion failed
            }
        }

        // FIX 2025-11-27: Process pending NEW categories BEFORE product sync
        // Creates inline categories in PrestaShop API and updates selections with real IDs
        $pendingNewForShop = $this->pendingNewCategories[$contextKey] ?? [];

        if (!empty($pendingNewForShop)) {
            Log::info('[CATEGORY CREATE] Starting creation of pending categories in PrestaShop', [
                'shop_id' => $shopId,
                'shop_name' => $shop->name,
                'pending_categories' => array_column($pendingNewForShop, 'name'),
            ]);

            try {
                $clientFactory = app(\App\Services\PrestaShop\PrestaShopClientFactory::class);
                $client = $clientFactory->create($shop);

                $createdCount = 0;
                $failedCategories = [];
                $createdCategoryMappings = []; // FIX 2025-11-27: Track PPM ID → PrestaShop ID for mappings update

                foreach ($pendingNewForShop as $pending) {
                    try {
                        // Build category XML for PrestaShop API
                        $categoryData = [
                            'category' => [
                                'name' => [['id' => 1, 'value' => $pending['name']]],
                                'link_rewrite' => [['id' => 1, 'value' => $pending['slug']]],
                                'description' => [['id' => 1, 'value' => '']],
                                'active' => 1,
                                'id_parent' => $pending['parentId'],
                            ]
                        ];

                        $response = $client->makeRequest('POST', '/categories', [], [
                            'body' => $client->arrayToXml($categoryData),
                            'headers' => ['Content-Type' => 'application/xml'],
                        ]);

                        if (!isset($response['category']['id'])) {
                            throw new \Exception('PrestaShop API nie zwrocilo ID kategorii');
                        }

                        $prestashopId = (int) $response['category']['id'];
                        $createdCount++;

                        Log::info('[CATEGORY CREATE] Category created successfully in PrestaShop', [
                            'temp_id' => $pending['tempId'],
                            'prestashop_id' => $prestashopId,
                            'name' => $pending['name'],
                            'parent_id' => $pending['parentId'],
                            'shop_id' => $shopId,
                        ]);

                        // FIX 2025-11-27: Create PPM Category + CategoryMapper mapping
                        // Without this, calculateExpandedCategoryIds() cannot find the category
                        $categoryMapper = app(\App\Services\PrestaShop\CategoryMapper::class);
                        $ppmParentId = $categoryMapper->mapFromPrestaShop($pending['parentId'], $shop);

                        $ppmCategory = \App\Models\Category::create([
                            'name' => $pending['name'],
                            'slug' => $pending['slug'],
                            'parent_id' => $ppmParentId,
                            'is_active' => true,
                        ]);

                        $categoryMapper->createMapping($ppmCategory->id, $shop, $prestashopId, $pending['name']);

                        // FIX 2025-11-27: Store mapping for later update to category_mappings
                        $createdCategoryMappings[$ppmCategory->id] = $prestashopId;

                        Log::info('[CATEGORY CREATE] PPM Category and mapping created', [
                            'ppm_category_id' => $ppmCategory->id,
                            'prestashop_id' => $prestashopId,
                            'ppm_parent_id' => $ppmParentId,
                        ]);

                        // Replace tempId with PPM Category ID in shopCategories selection
                        // IMPORTANT: shopCategories['selected'] stores PPM IDs, NOT PrestaShop IDs!
                        if (isset($this->shopCategories[$shopId]['selected'])) {
                            $key = array_search($pending['tempId'], $this->shopCategories[$shopId]['selected']);
                            if ($key !== false) {
                                $this->shopCategories[$shopId]['selected'][$key] = $ppmCategory->id;
                                Log::debug('[CATEGORY CREATE] Replaced tempId in selection with PPM Category ID', [
                                    'temp_id' => $pending['tempId'],
                                    'ppm_category_id' => $ppmCategory->id,
                                    'prestashop_id' => $prestashopId,
                                    'selection_key' => $key,
                                ]);
                            }
                        }

                    } catch (\Exception $e) {
                        $failedCategories[] = [
                            'name' => $pending['name'],
                            'error' => $e->getMessage(),
                        ];

                        Log::error('[CATEGORY CREATE] Failed to create category', [
                            'temp_id' => $pending['tempId'],
                            'name' => $pending['name'],
                            'parent_id' => $pending['parentId'],
                            'shop_id' => $shopId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Clear pending new categories for this shop
                unset($this->pendingNewCategories[$contextKey]);

                if ($createdCount > 0) {
                    $this->dispatch('success', message: "Utworzono $createdCount nowych kategorii w PrestaShop");
                }

                if (!empty($failedCategories)) {
                    $failedNames = implode(', ', array_column($failedCategories, 'name'));
                    $this->dispatch('warning', message: "Nie udalo sie utworzyc kategorii: $failedNames");
                }

                Log::info('[CATEGORY CREATE] Category creation completed', [
                    'shop_id' => $shopId,
                    'created_count' => $createdCount,
                    'failed_count' => count($failedCategories),
                ]);

                // FIX 2025-11-27: Save updated category selection to database BEFORE sync job
                // Without this, loadShopCategories() after instaPull would load old selection
                // because in-memory shopCategories[shopId]['selected'] is not persisted
                if ($createdCount > 0 && $this->product) {
                    $productShopData = \App\Models\ProductShopData::where('product_id', $this->product->id)
                        ->where('shop_id', $shopId)
                        ->first();

                    if ($productShopData) {
                        $categoryMappings = $productShopData->category_mappings ?? ['ui' => ['selected' => [], 'primary' => null], 'mappings' => []];
                        $categoryMappings['ui']['selected'] = $this->shopCategories[$shopId]['selected'] ?? [];

                        // FIX 2025-11-27: Add new category mappings to mappings array
                        // Without this, validator fails: "Mappings keys must match selected categories"
                        if (!isset($categoryMappings['mappings'])) {
                            $categoryMappings['mappings'] = [];
                        }
                        foreach ($createdCategoryMappings as $ppmId => $prestashopId) {
                            $categoryMappings['mappings'][$ppmId] = $prestashopId;
                        }

                        $categoryMappings['metadata']['last_updated'] = now()->toIso8601String();
                        $categoryMappings['metadata']['source'] = 'manual'; // inline category creation is a manual action

                        $productShopData->category_mappings = $categoryMappings;
                        $productShopData->save();

                        Log::info('[CATEGORY CREATE] Saved updated category selection to database', [
                            'product_id' => $this->product->id,
                            'shop_id' => $shopId,
                            'selected' => $this->shopCategories[$shopId]['selected'],
                            'new_mappings' => $createdCategoryMappings,
                            'all_mappings_keys' => array_keys($categoryMappings['mappings']),
                        ]);
                    }
                }

            } catch (\Exception $e) {
                Log::error('[CATEGORY CREATE] Fatal error during category creation', [
                    'shop_id' => $shopId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getFile() . ':' . $e->getLine(),
                ]);

                $this->dispatch('error', message: 'Blad podczas tworzenia kategorii: ' . $e->getMessage());
                // Don't return - continue with sync even if creation failed
            }
        }

        try {
            // Dispatch sync job for single shop
            SyncProductToPrestaShop::dispatch($this->product, $shop, auth()->id());

            // Set job tracking variables
            $this->activeJobType = 'sync';
            $this->jobCreatedAt = now()->toIso8601String();
            $this->activeJobStatus = 'pending';
            $this->syncJobShopId = $shopId; // FIX 2025-11-27: Track which shop we're syncing

            // PERFORMANCE FIX 2025-11-27: Notify Alpine to start polling
            $this->dispatch('job-started');

            // FIX 2025-11-25: Use 'info' not 'success' - actual success shown by Alpine panel after job completes
            $this->dispatch('info', message: "Rozpoczęto aktualizację produktu na sklepie {$shop->name}");

            Log::info('[ETAP_13 SINGLE SHOP SYNC] Sync job dispatched', [
                'product_id' => $this->product->id,
                'product_sku' => $this->product->sku,
                'shop_id' => $shopId,
                'shop_name' => $shop->name,
                'user_id' => auth()->id(),
            ]);

        } catch (\Exception $e) {
            Log::error('[ETAP_13 SINGLE SHOP SYNC] Error dispatching sync job', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
                'trace' => $e->getFile() . ':' . $e->getLine(),
            ]);

            $this->dispatch('error', message: 'Błąd podczas aktualizacji sklepu: ' . $e->getMessage());
        }
    }

    /**
     * Pull product data from SINGLE shop (PrestaShop → PPM) - ETAP_13
     *
     * Sidepanel "Wczytaj z aktualnego sklepu" button (per-shop)
     * Fetches product data from PrestaShop and reloads form
     *
     * FIX 2025-11-18: Metoda nie istniała - buttons wywoływały undefined method
     *
     * @param int $shopId
     * @return void
     */
    public function pullShopData(int $shopId): void
    {
        if (!$this->product) {
            $this->dispatch('error', message: 'Produkt nie istnieje');
            return;
        }

        // FIX 2025-11-18 (#4): TARGETED save - only current context, DON'T mark all shops
        // (Same fix as syncShop() - prevents marking ALL shops as pending)
        try {
            // 1. Capture current form state to pendingChanges
            $this->savePendingChanges();

            // 2. Save ONLY current context (default OR shop) - DON'T save all contexts
            if ($this->activeShopId === null) {
                // User is in "Dane domyślne" tab - save to Product WITHOUT marking all shops
                if (isset($this->pendingChanges['default'])) {
                    $this->savePendingChangesToProduct($this->pendingChanges['default'], $markShopsAsPending = false);
                    unset($this->pendingChanges['default']);
                }
            } else {
                // User is in specific shop tab - save to ProductShopData (doesn't affect other shops)
                if (isset($this->pendingChanges[$this->activeShopId])) {
                    $this->savePendingChangesToShop($this->activeShopId, $this->pendingChanges[$this->activeShopId]);
                    unset($this->pendingChanges[$this->activeShopId]);
                }
            }

            $this->dispatch('$commit');

            Log::info('[ETAP_13 AUTO-SAVE] Targeted save completed (single shop pull)', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'active_shop_id' => $this->activeShopId,
                'context' => $this->activeShopId === null ? 'default' : "shop:{$this->activeShopId}",
            ]);
        } catch (\Exception $e) {
            Log::error('[ETAP_13 AUTO-SAVE] Failed to save pending changes', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('error', message: 'Nie udało się zapisać zmian przed wczytaniem danych: ' . $e->getMessage());
            return;
        }

        // Get shop
        $shop = \App\Models\PrestaShopShop::find($shopId);

        if (!$shop || !$shop->is_active || $shop->connection_status !== 'connected') {
            $this->dispatch('warning', message: 'Sklep nie jest aktywny lub nie jest połączony');
            return;
        }

        try {
            // FIX 2025-11-18 (#8.1): Removed job tracking properties
            // (pullShopData is SYNCHRONOUS - job tracking only for async bulk operations)
            // OLD: $this->activeJobType = 'pull'; $this->activeJobStatus = 'pending'; etc.

            // FIX 2025-11-20: Block pulling data from PrestaShop when pending sync exists
            // (Same logic as loadProductDataFromPrestaShop - prevents overwriting user changes)
            $productShopData = \App\Models\ProductShopData::where('product_id', $this->product->id)
                ->where('shop_id', $shopId)
                ->first();

            if ($productShopData && $productShopData->sync_status === 'pending') {
                $this->dispatch('warning', message: 'Dane nie zostały pobrane z PrestaShop - oczekuje synchronizacja zapisanych zmian. Kliknij "Aktualizuj sklep" aby wykonać synchronizację.');

                Log::warning('[PULL SHOP DATA] Blocked due to pending sync', [
                    'shop_id' => $shopId,
                    'product_id' => $this->product->id,
                    'sync_status' => $productShopData->sync_status,
                    'last_sync_at' => $productShopData->last_sync_at,
                    'reason' => 'User has pending changes that would be overwritten',
                ]);

                return;
            }

            // FIX 2025-11-18 (#6): Use PrestaShopClientFactory instead of PrestaShopService
            // (PrestaShopService doesn't have getProduct() method)
            $client = \App\Services\PrestaShop\PrestaShopClientFactory::create($shop);

            // Try to fetch product from PrestaShop
            $prestashopData = null;

            if ($productShopData && $productShopData->prestashop_product_id) {
                // Product already synced - fetch by ID
                try {
                    $prestashopData = $client->getProduct($productShopData->prestashop_product_id);
                } catch (\Exception $e) {
                    // Product not found by ID - try search by SKU
                    Log::warning('[PULL SHOP DATA] Product not found by ID, trying SKU search', [
                        'prestashop_id' => $productShopData->prestashop_product_id,
                        'sku' => $this->product->sku,
                    ]);
                }
            }

            // If not found by ID, search by SKU (reference)
            if (!$prestashopData) {
                $products = $client->getProducts(['filter[reference]' => $this->product->sku]);

                if (empty($products)) {
                    // FIX 2025-11-18 (#8.1): No job tracking for sync operation
                    $this->dispatch('error', message: 'Nie znaleziono produktu w sklepie PrestaShop (SKU: ' . $this->product->sku . ')');
                    return;
                }

                // Get full product data for first match
                $prestashopData = $client->getProduct($products[0]['id']);
            }

            // Unwrap nested response (PrestaShop API wraps in 'product' key)
            if (isset($prestashopData['product'])) {
                $prestashopData = $prestashopData['product'];
            }

            // Extract essential data
            $productData = [
                'id' => $prestashopData['id'] ?? null,
                'name' => data_get($prestashopData, 'name.0.value') ?? data_get($prestashopData, 'name'),
                'description_short' => data_get($prestashopData, 'description_short.0.value') ?? data_get($prestashopData, 'description_short'),
                'description' => data_get($prestashopData, 'description.0.value') ?? data_get($prestashopData, 'description'),
                'price' => $prestashopData['price'] ?? null,
                'active' => $prestashopData['active'] ?? null,
                // FIX 2025-11-18 (#10.2): Extract categories from PrestaShop API response
                'categories' => data_get($prestashopData, 'associations.categories') ?? [],
            ];

            if (!$productData['id']) {
                // FIX 2025-11-18 (#8.1): No job tracking for sync operation
                $this->dispatch('error', message: 'Błąd parsowania danych produktu z PrestaShop');
                return;
            }

            // FIX #12: Build Option A structure from PrestaShop API
            $categoryMappings = null;
            if (!empty($productData['categories'])) {
                $psIds = array_column($productData['categories'], 'id');
                $shop = PrestaShopShop::find($shopId);

                if ($shop) {
                    $converter = app(CategoryMappingsConverter::class);
                    $categoryMappings = $converter->fromPrestaShopFormat($psIds, $shop);

                    Log::debug('[FIX #12] ProductForm::pullShopData: Converted PrestaShop to Option A', [
                        'shop_id' => $shop->id,
                        'prestashop_ids' => $psIds,
                        'canonical_format' => $categoryMappings,
                    ]);
                }
            }

            // Update ProductShopData with fetched data
            $productShopData = \App\Models\ProductShopData::firstOrNew([
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
            ]);

            $productShopData->fill([
                'prestashop_product_id' => $productData['id'],
                'name' => $productData['name'] ?? $productShopData->name,
                'short_description' => $productData['description_short'] ?? $productShopData->short_description,
                'long_description' => $productData['description'] ?? $productShopData->long_description,
                'sync_status' => 'synced',
                'last_success_sync_at' => now(),
                // FIX 2025-11-18 (#8.2): Set last_pulled_at for "Szczegóły synchronizacji"
                'last_pulled_at' => now(),
                // FIX 2025-11-18 (#10.2): Save category_mappings (only update if categories were returned)
                'category_mappings' => !empty($categoryMappings) ? $categoryMappings : $productShopData->category_mappings,
            ]);

            $productShopData->save();

            // FIX 2025-11-18 (#7): Update $this->shopData to reflect saved changes
            // (loadShopDataToForm() reads from $this->shopData, not from DB!)
            $this->shopData[$shopId] = array_merge(
                $this->shopData[$shopId] ?? [],
                [
                    'id' => $productShopData->id,
                    'name' => $productShopData->name,
                    'short_description' => $productShopData->short_description,
                    'long_description' => $productShopData->long_description,
                    'sync_status' => $productShopData->sync_status,
                    'last_success_sync_at' => $productShopData->last_success_sync_at,
                    'prestashop_product_id' => $productShopData->prestashop_product_id,
                    // FIX 2025-11-18 (#10.2): Include category_mappings in cache (for UI reactivity)
                    'category_mappings' => $productShopData->category_mappings,
                ]
            );

            // FIX #12: After save, reload UI state from ProductShopData
            if ($productShopData->wasRecentlyCreated || $productShopData->wasChanged()) {
                $this->reloadCleanShopCategories($shopId);
            }

            // Reload shop data to form (if currently viewing this shop)
            if ($this->activeShopId === $shopId) {
                $this->loadShopDataToForm($shopId);
            }

            // Update cached shop data
            $this->loadedShopData[$shopId] = $productData;

            // FIX 2025-11-18 (#8.3): Refresh product->shopData relation
            // (Blade "Szczegóły synchronizacji" uses $product->shopData, not $this->shopData)
            $this->product->load('shopData.shop');

            // FIX 2025-11-18 (#8.1): No job tracking for sync operation
            // (UI feedback via wire:loading + success event only)

            $this->dispatch('success', message: "Wczytano dane ze sklepu {$shop->name}");

            Log::info('[ETAP_13 SINGLE SHOP PULL] Product data pulled successfully', [
                'product_id' => $this->product->id,
                'product_sku' => $this->product->sku,
                'shop_id' => $shopId,
                'shop_name' => $shop->name,
                'prestashop_id' => $productData['id'],
            ]);

        } catch (\Exception $e) {
            // FIX 2025-11-18 (#9.2): No job tracking for sync operation (consistent with success path)

            Log::error('[ETAP_13 SINGLE SHOP PULL] Error pulling product data', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
                'trace' => $e->getFile() . ':' . $e->getLine(),
            ]);

            $this->dispatch('error', message: 'Błąd podczas wczytywania danych: ' . $e->getMessage());
        }
    }

    /**
     * Bulk update product to ALL shops (PPM → PrestaShop) - ETAP_13
     *
     * Sidepanel "Aktualizuj sklepy" button
     * Dispatches SyncProductToPrestaShop per shop + captures job ID for monitoring
     *
     * @return void
     */
    public function bulkUpdateShops(): void
    {
        if (!$this->product) {
            $this->dispatch('error', message: 'Produkt nie istnieje');
            return;
        }

        // ETAP_13: Check for active job (anti-duplicate)
        if ($this->hasActiveSyncJob()) {
            $this->dispatch('warning', message: 'Synchronizacja już w trakcie. Poczekaj na zakończenie.');
            return;
        }

        // CRITICAL FIX (2025-11-18): Auto-save pending changes BEFORE dispatch
        // Without this, checksum is based on OLD data → "No changes - sync skipped"
        try {
            $this->saveAllPendingChanges();

            // Reset Livewire dirty tracking (prevents browser "unsaved changes" warning)
            $this->dispatch('$commit');

            Log::info('[ETAP_13 AUTO-SAVE] Pending changes saved before bulk update', [
                'product_id' => $this->product->id,
                'active_shop_id' => $this->activeShopId,
            ]);
        } catch (\Exception $e) {
            Log::error('[ETAP_13 AUTO-SAVE] Failed to save pending changes', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('error', message: 'Nie udało się zapisać zmian przed synchronizacją: ' . $e->getMessage());
            return;
        }

        // Get connected shops
        $shops = $this->product->shopData->pluck('shop')->filter(function ($shop) {
            return $shop && $shop->is_active && $shop->connection_status === 'connected';
        });

        if ($shops->isEmpty()) {
            $this->dispatch('warning', message: 'Produkt nie jest przypisany do żadnego aktywnego sklepu');
            return;
        }

        try {
            // Dispatch sync job per shop (NOTE: BulkSyncProducts is for MULTIPLE products to ONE shop)
            // We need SINGLE product to MULTIPLE shops, so dispatch per-shop
            foreach ($shops as $shop) {
                SyncProductToPrestaShop::dispatch($this->product, $shop, auth()->id());
            }

            // Capture first dispatched job ID for monitoring (approximation)
            // NOTE: Laravel doesn't return job ID from dispatch() directly - we'll use batch ID workaround
            // For MVP, capture timestamp and mark as pending
            $this->activeJobType = 'sync';
            $this->jobCreatedAt = now()->toIso8601String();
            $this->activeJobStatus = 'pending';
            // NOTE: activeJobId would require batch tracking - deferred to future enhancement

            // FIX 2025-11-25: Use 'info' not 'success' - actual success shown by Alpine panel after job completes
            $this->dispatch('info', message: "Rozpoczęto aktualizację produktu na {$shops->count()} sklepach");

            Log::info('Bulk update shops initiated', [
                'product_id' => $this->product->id,
                'product_sku' => $this->product->sku,
                'shops_count' => $shops->count(),
                'user_id' => auth()->id(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error during bulk update shops', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
                'trace' => $e->getFile() . ':' . $e->getLine(),
            ]);

            $this->dispatch('error', message: 'Błąd podczas aktualizacji sklepów: ' . $e->getMessage());
        }
    }

    /**
     * Pull product data from ALL shops (PrestaShop → PPM) - ETAP_13
     *
     * Sidepanel "Wczytaj ze sklepów" button
     * Dispatches BulkPullProducts JOB (created by laravel-expert)
     *
     * @return void
     */
    public function bulkPullFromShops(): void
    {
        if (!$this->product) {
            $this->dispatch('error', message: 'Produkt nie istnieje');
            return;
        }

        // CRITICAL FIX (2025-11-18): Auto-save pending changes BEFORE pull
        // Prevents data loss when user has unsaved changes
        try {
            $this->saveAllPendingChanges();

            // Reset Livewire dirty tracking (prevents browser "unsaved changes" warning)
            $this->dispatch('$commit');

            Log::info('[ETAP_13 AUTO-SAVE] Pending changes saved before bulk pull', [
                'product_id' => $this->product->id,
                'active_shop_id' => $this->activeShopId,
            ]);
        } catch (\Exception $e) {
            Log::error('[ETAP_13 AUTO-SAVE] Failed to save pending changes', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('error', message: 'Nie udało się zapisać zmian przed wczytaniem danych: ' . $e->getMessage());
            return;
        }

        // Get connected shops
        $shops = $this->product->shopData->pluck('shop')->filter(function ($shop) {
            return $shop && $shop->is_active && $shop->connection_status === 'connected';
        });

        if ($shops->isEmpty()) {
            $this->dispatch('warning', message: 'Produkt nie jest przypisany do żadnego aktywnego sklepu');
            return;
        }

        try {
            // FIX 2025-11-18 (#8.4): Mark shops as PENDING before dispatching job
            // (checkBulkPullJobStatus() requires this to avoid instant "SUCCESS" before job executes)
            \App\Models\ProductShopData::where('product_id', $this->product->id)
                ->whereIn('shop_id', $shops->pluck('id')->all())
                ->update([
                    'sync_status' => \App\Models\ProductShopData::STATUS_PENDING,
                    'sync_direction' => \App\Models\ProductShopData::DIRECTION_PS_TO_PPM,
                ]);

            // Dispatch BulkPullProducts JOB (NEW from laravel-expert ETAP_13)
            // Constructor: Product $product, Collection $shops, ?int $userId
            $batch = BulkPullProducts::dispatch(
                $this->product,
                $shops,
                auth()->id()
            );

            // Capture job ID for monitoring
            // NOTE: BulkPullProducts uses Laravel Bus::batch() - batch ID available
            $this->activeJobId = $batch->id ?? null;
            $this->activeJobType = 'pull';
            $this->jobCreatedAt = now()->toIso8601String();
            $this->activeJobStatus = 'pending';

            // FIX 2025-11-25: Use 'info' not 'success' - actual success shown by Alpine panel after job completes
            $this->dispatch('info', message: "Rozpoczęto wczytywanie danych ze {$shops->count()} sklepów");

            Log::info('Bulk pull from shops initiated', [
                'product_id' => $this->product->id,
                'product_sku' => $this->product->sku,
                'shops_count' => $shops->count(),
                'batch_id' => $this->activeJobId,
                'user_id' => auth()->id(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error during bulk pull from shops', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
                'trace' => $e->getFile() . ':' . $e->getLine(),
            ]);

            $this->dispatch('error', message: 'Błąd podczas wczytywania danych ze sklepów: ' . $e->getMessage());
        }
    }

    /**
     * Reload shop categories from ProductShopData to UI state (FIX #12)
     *
     * Call after pullShopData() or external updates to sync UI with database
     * Converts canonical Option A format → UI format for Livewire
     *
     * @param int $shopId Shop ID
     * @return void
     */
    protected function reloadCleanShopCategories(?int $shopId = null): void
    {
        $shopData = ProductShopData::where('product_id', $this->product->id)
            ->where('shop_id', $shopId)
            ->first();

        if ($shopData && $shopData->hasCategoryMappings()) {
            $converter = app(CategoryMappingsConverter::class);
            $this->shopCategories[$shopId] = $converter->toUiFormat(
                $shopData->category_mappings
            );

            Log::debug('[FIX #12] Reloaded shop categories to UI', [
                'shop_id' => $shopId,
                'ui_categories' => $this->shopCategories[$shopId],
            ]);

            // Trigger Livewire re-render
            $this->dispatch('shop-categories-reloaded', shopId: $shopId);
        }
    }

    /**
     * Handle shop-categories-reloaded event (FIX #12)
     *
     * Triggered after reloadCleanShopCategories() to refresh Alpine.js category tree picker
     *
     * @param int $shopId Shop ID
     * @return void
     */
    public function handleCategoriesReloaded(int $shopId): void
    {
        // Trigger UI update in Alpine.js category tree picker
        $this->dispatch('category-tree-refresh', shopId: $shopId);

        Log::debug('[FIX #12] Category tree refresh dispatched', [
            'shop_id' => $shopId,
        ]);
    }

    /**
     * Detect pending changes for specific shop (ETAP_13.3 - 2025-11-17)
     *
     * Compare ProductShopData fields vs cached PrestaShop data ($this->loadedShopData)
     * Return array of user-friendly labels for changed fields
     *
     * Used in "Szczegóły synchronizacji" panel to show DYNAMIC pending changes
     * (instead of hardcoded "stawka VAT")
     *
     * @param int $shopId Shop ID to check
     * @return array User-friendly labels (e.g., ["Nazwa produktu", "Cena", "Stawka VAT"])
     */
    public function getPendingChangesForShop(int $shopId): array
    {
        if (!$this->product) {
            return [];
        }

        // Get ProductShopData for shop
        $shopData = \App\Models\ProductShopData::where('product_id', $this->product->id)
            ->where('shop_id', $shopId)
            ->first();

        if (!$shopData) {
            return [];
        }

        // Check if PrestaShop data is loaded for this shop
        if (!isset($this->loadedShopData[$shopId])) {
            // No cached data - cannot detect changes
            return [];
        }

        $cached = $this->loadedShopData[$shopId];
        $changes = [];

        // Field mapping: database field => user-friendly Polish label
        // FIX 2025-11-18 (#5): Removed invalid fields that don't exist in ProductShopData
        // - 'price' (ceny w ProductPrice relation, nie w ProductShopData)
        // - 'quantity' (stany w ProductWarehouseStock relation, nie w ProductShopData)
        // - 'description' (ProductShopData ma 'long_description', nie 'description')
        $fieldsToCheck = [
            'name' => 'Nazwa produktu',
            'tax_rate' => 'Stawka VAT',
            'short_description' => 'Krótki opis',
            'meta_title' => 'Meta tytuł',
            'meta_description' => 'Meta opis',
            // FIX 2025-11-18 (#10.3): Add category_mappings detection
            'category_mappings' => 'Kategorie',
        ];

        foreach ($fieldsToCheck as $field => $label) {
            // FIX #12: Compare using Option A canonical format
            if ($field === 'category_mappings') {
                if ($shopData->hasCategoryMappings()) {
                    $converter = app(CategoryMappingsConverter::class);

                    // Get current canonical format from database
                    $savedCanonical = $shopData->category_mappings;

                    // Compare mappings (PrestaShop IDs only) - use converter helper
                    $savedPsIds = $converter->toPrestaShopIdsList($savedCanonical);

                    // PrestaShop cached data format: [{"id": 2}, {"id": 15}] → extract IDs
                    $cachedPsIds = array_column($cached['categories'] ?? [], 'id');

                    // Sort both arrays for consistent comparison
                    sort($savedPsIds);
                    sort($cachedPsIds);

                    if ($savedPsIds !== $cachedPsIds) {
                        $changes[] = $label;

                        Log::debug('[FIX #12] Detected category changes', [
                            'product_id' => $this->product->id,
                            'shop_id' => $shopId,
                            'saved_ps_ids' => $savedPsIds,
                            'cached_ps_ids' => $cachedPsIds,
                        ]);
                    }
                }

                continue; // Skip standard comparison
            }

            // Check if field exists in cached data
            if (!isset($cached[$field])) {
                continue;
            }

            // Get values (handle NULL gracefully)
            $shopValue = $shopData->$field ?? null;
            $psValue = $cached[$field] ?? null;

            // FIX 2025-11-18: Use strict normalization to prevent false positives
            // (e.g., null vs "", "23" vs 23, 23.00 vs 23.0)
            $shopValueNormalized = $this->normalizeValueForComparison($shopValue);
            $psValueNormalized = $this->normalizeValueForComparison($psValue);

            // Compare (strict comparison after normalization)
            if ($shopValueNormalized !== $psValueNormalized) {
                $changes[] = $label;
            }
        }

        return $changes;
    }

    /*
    |--------------------------------------------------------------------------
    | SYNC STATUS METHODS (ETAP_07 FAZA 3)
    |--------------------------------------------------------------------------
    */

    /**
     * Get sync status for specific shop (CONSOLIDATED 2025-10-13)
     *
     * Updated to use ProductShopData instead of deprecated ProductSyncStatus
     *
     * @param int $shopId
     * @return \App\Models\ProductShopData|null
     */
    public function getSyncStatusForShop(int $shopId): ?\App\Models\ProductShopData
    {
        if (!$this->product) {
            return null;
        }

        return \App\Models\ProductShopData::where('product_id', $this->product->id)
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
                // FEATURE (2025-11-07): Field-Level Pending Tracking - show specific fields
                'text' => !empty($syncStatus->pending_fields)
                    ? 'Oczekuje: ' . implode(', ', $syncStatus->pending_fields)
                    : 'Oczekuje',
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
            // USER_ID FIX (2025-11-07): Pass auth()->id() to capture user who triggered sync
            SyncProductToPrestaShop::dispatch($this->product, $shop, auth()->id());

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
     * FIX #5 2025-11-21: Save only CURRENT context and close form
     *
     * Changed from saving ALL contexts to only current context to prevent
     * dispatching Jobs with stale data from inactive tabs.
     *
     * User edited categories in Shop A → save ONLY Shop A (not Shop B/C/default from memory)
     */
    public function saveAndClose()
    {
        // ETAP_07d: Apply pending media shop changes BEFORE product save
        // This ensures all media sync happens before the page redirects
        $this->dispatch('before-product-save');

        $currentContext = $this->activeShopId ?? 'default';

        Log::info('[FIX #5 2025-11-21] saveAndClose: Saving ONLY current context', [
            'active_context' => $currentContext,
            'defaultCategories' => $this->defaultCategories,
            'hasUnsavedChanges' => $this->hasUnsavedChanges,
            'all_pending_contexts' => array_keys($this->pendingChanges),
            'pending_categories_count' => $this->pendingCategoriesCount,
            'pending_delete_categories' => $this->pendingDeleteCategories,
        ]);

        // FIX 2025-11-28: Process pending DELETE categories BEFORE creating new ones
        // This was missing - categories marked for deletion were only cleared but NOT actually deleted!

        // CASE 1: Default tab - delete from PPM database (local categories)
        if ($this->activeShopId === null) {
            $contextKey = 'default';
            $categoriesToDelete = $this->pendingDeleteCategories[$contextKey] ?? [];

            if (!empty($categoriesToDelete)) {
                Log::info('[SAVE] Starting physical deletion of categories from PPM database', [
                    'context' => 'default',
                    'categories_to_delete' => $categoriesToDelete,
                ]);

                try {
                    $deletedCount = 0;
                    $failedCategories = [];

                    foreach ($categoriesToDelete as $categoryId) {
                        try {
                            $category = \App\Models\Category::find($categoryId);
                            if ($category) {
                                // Delete with children (cascade)
                                $category->delete();
                                $deletedCount++;

                                Log::info('[SAVE] Category deleted successfully from PPM database', [
                                    'category_id' => $categoryId,
                                    'category_name' => $category->name,
                                ]);
                            } else {
                                Log::warning('[SAVE] Category not found in PPM database', [
                                    'category_id' => $categoryId,
                                ]);
                            }
                        } catch (\Exception $e) {
                            $failedCategories[] = [
                                'id' => $categoryId,
                                'error' => $e->getMessage(),
                            ];

                            Log::error('[SAVE] Failed to delete category from PPM database', [
                                'category_id' => $categoryId,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    // Clear pending delete categories AFTER successful deletion
                    unset($this->pendingDeleteCategories[$contextKey]);

                    if ($deletedCount > 0) {
                        $this->dispatch('success', message: "Usunieto $deletedCount kategorii z PPM");
                    }

                    if (!empty($failedCategories)) {
                        $failedIds = implode(', ', array_column($failedCategories, 'id'));
                        $this->dispatch('warning', message: "Nie udalo sie usunac kategorii: $failedIds");
                    }

                    Log::info('[SAVE] PPM category deletion completed', [
                        'deleted_count' => $deletedCount,
                        'failed_count' => count($failedCategories),
                    ]);

                } catch (\Exception $e) {
                    Log::error('[SAVE] Fatal error during PPM category deletion', [
                        'error' => $e->getMessage(),
                    ]);

                    $this->dispatch('error', message: 'Blad podczas usuwania kategorii: ' . $e->getMessage());
                    // Continue with save even if deletion failed
                }
            }
        }

        // CASE 2: Shop tab - delete from PrestaShop API
        if ($this->activeShopId !== null) {
            $contextKey = (string) $this->activeShopId;
            $categoriesToDelete = $this->pendingDeleteCategories[$contextKey] ?? [];

            if (!empty($categoriesToDelete)) {
                $shop = \App\Models\PrestaShopShop::find($this->activeShopId);

                if ($shop && $shop->is_active && $shop->connection_status === 'connected') {
                    Log::info('[SAVE] Starting physical deletion of categories from PrestaShop', [
                        'shop_id' => $this->activeShopId,
                        'shop_name' => $shop->name,
                        'categories_to_delete' => $categoriesToDelete,
                    ]);

                    try {
                        $clientFactory = app(\App\Services\PrestaShop\PrestaShopClientFactory::class);
                        $client = $clientFactory->create($shop);

                        $deletedCount = 0;
                        $failedCategories = [];

                        foreach ($categoriesToDelete as $categoryId) {
                            try {
                                $client->deleteCategory((int) $categoryId);
                                $deletedCount++;

                                Log::info('[SAVE] Category deleted successfully from PrestaShop', [
                                    'category_id' => $categoryId,
                                    'shop_id' => $this->activeShopId,
                                ]);
                            } catch (\App\Exceptions\PrestaShopAPIException $e) {
                                $failedCategories[] = [
                                    'id' => $categoryId,
                                    'error' => $e->getMessage(),
                                ];

                                Log::error('[SAVE] Failed to delete category from PrestaShop', [
                                    'category_id' => $categoryId,
                                    'shop_id' => $this->activeShopId,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }

                        // Clear pending delete categories AFTER successful deletion
                        unset($this->pendingDeleteCategories[$contextKey]);

                        if ($deletedCount > 0) {
                            $this->dispatch('success', message: "Usunieto $deletedCount kategorii z PrestaShop");
                        }

                        if (!empty($failedCategories)) {
                            $failedIds = implode(', ', array_column($failedCategories, 'id'));
                            $this->dispatch('warning', message: "Nie udalo sie usunac kategorii: $failedIds");
                        }

                        Log::info('[SAVE] Category deletion completed', [
                            'shop_id' => $this->activeShopId,
                            'deleted_count' => $deletedCount,
                            'failed_count' => count($failedCategories),
                        ]);

                        // FIX 2025-11-28: Clear category cache after deletion so fresh data loads
                        if ($deletedCount > 0) {
                            $categoryService = app(\App\Services\PrestaShop\PrestaShopCategoryService::class);
                            $categoryService->clearCache($shop);
                            Log::info('[SAVE] Category cache cleared after deletion', ['shop_id' => $this->activeShopId]);
                        }

                    } catch (\Exception $e) {
                        Log::error('[SAVE] Fatal error during category deletion', [
                            'shop_id' => $this->activeShopId,
                            'error' => $e->getMessage(),
                        ]);

                        $this->dispatch('error', message: 'Blad podczas usuwania kategorii: ' . $e->getMessage());
                        // Continue with save even if deletion failed
                    }
                } else {
                    Log::warning('[SAVE] Shop not active or not connected - skipping category deletion', [
                        'shop_id' => $this->activeShopId,
                        'shop_active' => $shop?->is_active,
                        'connection_status' => $shop?->connection_status,
                    ]);
                }
            }
        }

        // 2025-11-26: Process pending NEW categories BEFORE saving product data
        // Creates categories in PrestaShop API and updates selections with real IDs
        if (!empty($this->pendingNewCategories)) {
            $categoryResults = $this->processPendingCategories();

            if (!empty($categoryResults['failed'])) {
                foreach ($categoryResults['failed'] as $failed) {
                    $this->addError('categories', "Blad tworzenia kategorii '{$failed['name']}': {$failed['error']}");
                }
            }

            Log::info('[SAVE] Processed pending categories', [
                'created' => count($categoryResults['created']),
                'failed' => count($categoryResults['failed']),
            ]);
        }

        // FIX 2025-12-08: Check for shop-only changes BEFORE commitPendingVariants() clears the array!
        // ETAP_05c: Process SHOP-SPECIFIC variant operations when in shop context
        $hasShopVariantChanges = false;
        if ($this->activeShopId !== null) {
            $hasShopVariantChanges = !empty($this->shopVariantOverrides[$this->activeShopId] ?? []);

            // Check for shop-only pending creates BEFORE they get cleared
            foreach ($this->pendingVariantCreates as $data) {
                if (($data['is_shop_only'] ?? false) && ($data['shop_id'] ?? null) === $this->activeShopId) {
                    $hasShopVariantChanges = true;
                    break;
                }
            }

            Log::info('[SAVE] Shop variant changes check (BEFORE commit)', [
                'shop_id' => $this->activeShopId,
                'hasShopVariantChanges' => $hasShopVariantChanges,
                'shopVariantOverrides' => count($this->shopVariantOverrides[$this->activeShopId] ?? []),
                'pendingVariantCreates' => count($this->pendingVariantCreates),
            ]);
        }

        // 2025-12-04: Process pending VARIANT operations BEFORE saving product data
        // Commits all variant creates/updates/deletes that were queued
        // NOTE: This clears pendingVariantCreates, so shop-only check MUST be done above!
        if ($this->hasPendingVariantChanges()) {
            $variantResults = $this->commitPendingVariants();

            if (!empty($variantResults['errors'])) {
                foreach ($variantResults['errors'] as $error) {
                    $this->addError('variants', $error);
                }
            }

            Log::info('[SAVE] Processed pending variants', [
                'created' => $variantResults['created'],
                'updated' => $variantResults['updated'],
                'deleted' => $variantResults['deleted'],
                'errors' => count($variantResults['errors']),
            ]);
        }

        // ETAP_05c FIX: Process SHOP-SPECIFIC variant operations
        // Uses hasShopVariantChanges computed BEFORE pendingVariantCreates was cleared
        if ($hasShopVariantChanges) {
            $shopVariantResults = $this->commitShopVariants();

            if (!empty($shopVariantResults['errors'])) {
                foreach ($shopVariantResults['errors'] as $error) {
                    $this->addError('variants', $error);
                }
            }

            Log::info('[SAVE] Processed shop variant changes', [
                'shop_id' => $this->activeShopId,
                'created' => $shopVariantResults['created'],
                'updated' => $shopVariantResults['updated'],
                'deleted' => $shopVariantResults['deleted'],
                'errors' => count($shopVariantResults['errors']),
            ]);
        }

        // ETAP_08.4: Handle ERP context (Shop-Tab pattern)
        // When in ERP context, save to product_erp_data and dispatch sync job
        if ($this->activeErpConnectionId !== null && $this->isEditMode && $this->product) {
            $this->saveErpContextAndDispatchJob();

            Log::info('[ETAP_08.4] ERP context save completed', [
                'product_id' => $this->product->id,
                'erp_connection_id' => $this->activeErpConnectionId,
            ]);
        }

        // FIX #5 2025-11-21: Save only current context (not all contexts)
        // FIX 2025-11-25: Skip job tracking - we're redirecting immediately, job runs in background
        $this->saveCurrentContextOnly(skipJobTracking: true);

        if (empty($this->getErrorBag()->all())) {
            // FIX 2025-11-21 (Fix #10): Force reset hasUnsavedChanges before redirect
            // Prevents beforeunload dialog when saveAndClose() redirects to product list
            // Even if other shop contexts have pending changes, we're leaving the form anyway
            $this->hasUnsavedChanges = false;
            $this->pendingChanges = [];

            // FIX 2025-11-25: Clear any job tracking that might have been set
            // This ensures UI shows "Zapisz zmiany" not "Wróć do listy" during redirect
            $this->activeJobStatus = null;
            $this->activeJobType = null;
            $this->activeJobId = null;
            $this->jobResult = null;

            // FIX 2025-11-25: Use Livewire 3.x $this->js() for synchronous JavaScript execution
            // This executes BEFORE re-render, ensuring redirect happens immediately
            // Previous event-based redirect was being overridden by component re-render
            $this->js("window.skipBeforeUnload = true; window.location.href = '/admin/products';");

            Log::info('saveAndClose: Executing immediate JS redirect', [
                'product_id' => $this->product?->id,
                'hasUnsavedChanges_reset' => true,
                'job_tracking_cleared' => true,
            ]);
        }
    }

    /**
     * FIX #5 2025-11-21: Save only current context (default OR active shop)
     *
     * This prevents dispatching Jobs with stale category data from inactive tabs.
     * Only the active context is saved to ensure fresh data goes to PrestaShop.
     *
     * @param bool $skipJobTracking FIX 2025-11-25: When true, skip job tracking UI
     *             (used by saveAndClose to redirect immediately while job runs in background)
     */
    private function saveCurrentContextOnly(bool $skipJobTracking = false): void
    {
        $this->isSaving = true;
        $this->successMessage = '';

        try {
            // Check for active sync job
            if ($this->hasActiveSyncJob()) {
                $this->dispatch('warning', message: 'Synchronizacja już w trakcie. Poczekaj na zakończenie.');
                $this->isSaving = false;
                return;
            }

            // ETAP_08.4 FIX: In ERP context, data is already saved by saveErpContextAndDispatchJob()
            // Don't save to default product - that would overwrite PPM data with ERP-specific data!
            if ($this->activeErpConnectionId !== null) {
                Log::info('[ETAP_08.4 FIX] In ERP context - skipping default product save to prevent data leakage', [
                    'product_id' => $this->product?->id,
                    'erp_connection_id' => $this->activeErpConnectionId,
                ]);
                $this->isSaving = false;
                return;
            }

            // Save current form state to pending changes
            $this->savePendingChanges();

            $currentKey = $this->activeShopId ?? 'default';

            if (!isset($this->pendingChanges[$currentKey])) {
                Log::warning('[FIX #5 2025-11-21] No pending changes for current context', [
                    'current_key' => $currentKey,
                ]);
                $this->isSaving = false;
                return;
            }

            $changes = $this->pendingChanges[$currentKey];

            // Save to appropriate target (default product or specific shop)
            if ($currentKey === 'default') {
                $this->savePendingChangesToProduct($changes);
                Log::info('[FIX #5 2025-11-21] Saved ONLY default context', [
                    'product_id' => $this->product->id,
                ]);
            } else {
                // FIX 2025-11-25: Pass skipJobTracking to prevent UI from showing job status
                $this->savePendingChangesToShop((int)$currentKey, $changes, $skipJobTracking);
                Log::info('[FIX #5 2025-11-21] Saved ONLY shop context', [
                    'product_id' => $this->product->id,
                    'shop_id' => $currentKey,
                    'skip_job_tracking' => $skipJobTracking,
                ]);
            }

            // Clear ONLY current context from pending changes
            unset($this->pendingChanges[$currentKey]);

            // FIX 2025-11-27: Clear pending delete categories for this context after successful save
            if (isset($this->pendingDeleteCategories[$currentKey])) {
                Log::info('[FIX 2025-11-27] Clearing pendingDeleteCategories after save', [
                    'context' => $currentKey,
                    'deleted_count' => count($this->pendingDeleteCategories[$currentKey]),
                ]);
                unset($this->pendingDeleteCategories[$currentKey]);
            }

            // FIX 2025-11-27: Clear pending new categories for this context after successful save
            if (isset($this->pendingNewCategories[$currentKey])) {
                unset($this->pendingNewCategories[$currentKey]);
            }

            $this->hasUnsavedChanges = !empty($this->pendingChanges);

            // Update stored data structures
            $this->storeDefaultData();
            $this->updateStoredShopData();

            // Refresh form with current context data
            if ($this->activeShopId === null) {
                $this->loadDefaultDataToForm();
            } else {
                $this->loadShopDataToForm($this->activeShopId);
            }

            $this->dispatch('success', message: 'Zmiany zostały zapisane pomyślnie');
        } catch (\Exception $e) {
            Log::error('[FIX #5 2025-11-21] Error saving current context', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addError('general', 'Wystąpił błąd podczas zapisywania zmian: ' . $e->getMessage());
        } finally {
            $this->isSaving = false;
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
            // ETAP_13: Check for active sync job before proceeding
            if ($this->hasActiveSyncJob()) {
                $this->dispatch('warning', message: 'Synchronizacja już w trakcie. Poczekaj na zakończenie.');
                $this->isSaving = false;
                return;
            }

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
    /**
     * Save pending changes to product (default data)
     *
     * FIX 2025-11-18 (#4): Added $markShopsAsPending parameter to prevent
     * marking ALL shops as pending when syncing single shop
     *
     * @param array $changes Pending changes to save
     * @param bool $markShopsAsPending If true, marks all shops as 'pending' after update (default behavior)
     */
    private function savePendingChangesToProduct(array $changes, bool $markShopsAsPending = true): void
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

            // FIX 2025-11-18 (#4): Conditionally mark shops as pending (ONLY when explicitly requested)
            // Default behavior: Mark all shops as 'pending' when default data changes (normal edit mode)
            // Targeted save: DON'T mark all shops when syncing single shop (prevents "all shops" bug)
            if ($markShopsAsPending) {
                // CRITICAL FIX (Bug 2): Mark all associated shops as 'pending' after updating default data
                // When user edits "Dane domyślne", shops need to be re-synced to reflect new default data
                $shopsMarkedPending = \App\Models\ProductShopData::where('product_id', $this->product->id)
                    ->where('sync_status', '!=', 'disabled') // Don't change disabled shops
                    ->update(['sync_status' => 'pending']);

                if ($shopsMarkedPending > 0) {
                    Log::info('Marked shops as pending after default data update (pending changes)', [
                        'product_id' => $this->product->id,
                        'shops_marked' => $shopsMarkedPending,
                    ]);
                }
            } else {
                Log::info('Skipped marking all shops as pending (targeted save for single shop)', [
                    'product_id' => $this->product->id,
                ]);
            }

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

            // === FEATURES (CONTEXT-ISOLATED SYSTEM) ===
            // FIX 2025-12-03: Restore and save productFeatures from pending changes
            // This ensures features edited in "Dane domyslne" are saved to database
            if (isset($changes['productFeatures'])) {
                $this->productFeatures = $changes['productFeatures'];
                $this->saveProductFeatures();
                Log::info('[FEATURE SAVE] Product features saved from pending changes (default context)', [
                    'product_id' => $this->product->id,
                    'features_count' => count($this->productFeatures),
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

            // === FEATURES (CONTEXT-ISOLATED SYSTEM) ===
            // FIX 2025-12-03: Save productFeatures for new products
            if (isset($changes['productFeatures'])) {
                $this->productFeatures = $changes['productFeatures'];
                $this->saveProductFeatures();
                Log::info('[FEATURE SAVE] Product features saved for new product', [
                    'product_id' => $this->product->id,
                    'features_count' => count($this->productFeatures),
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

        // PROBLEM #4 (2025-11-07): Save prices and stock after product save
        if ($this->product && $this->product->exists) {
            try {
                $this->savePricesInternal();
                $this->saveStockInternal();

                // BUG FIX (2025-11-12): Auto-dispatch sync jobs for all connected shops after price/stock change
                // User expects jobs to appear automatically when changing prices
                $this->dispatchSyncJobsForAllShops();

            } catch (\Exception $e) {
                Log::error('Failed to save prices/stock in savePendingChangesToProduct', [
                    'product_id' => $this->product->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't throw - allow product save to complete even if prices/stock fail
            }

            // ETAP_07e FAZA 3: Save product features
            try {
                $this->saveProductFeatures();
            } catch (\Exception $e) {
                Log::error('Failed to save product features in savePendingChangesToProduct', [
                    'product_id' => $this->product->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't throw - allow product save to complete even if features fail
            }

            // ETAP_05d FAZA 4: Save vehicle compatibility data
            try {
                $this->saveCompatibilityData();
            } catch (\Exception $e) {
                Log::error('Failed to save compatibility data in savePendingChangesToProduct', [
                    'product_id' => $this->product->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't throw - allow product save to complete even if compatibility fail
            }
        }

        // CRITICAL FIX: Clear removed shops cache after save (no longer needed)
        $this->removedShopsCache = [];
    }

    /**
     * Save pending changes to specific shop data
     * CRITICAL FIX (2025-11-07): Added sync_status='pending' + auto-dispatch
     *
     * @param bool $skipJobTracking FIX 2025-11-25: When true, skip job tracking UI
     *             (job still dispatches but UI doesn't show progress - used for saveAndClose redirect)
     */
    private function savePendingChangesToShop(int $shopId, array $changes, bool $skipJobTracking = false): void
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

        // FEATURE: Field-Level Pending Tracking (2025-11-07)
        // Track WHICH specific fields changed (not just "pending" status)
        $fieldNameMapping = [
            'sku' => 'SKU',
            'name' => 'nazwa',
            'slug' => 'slug',
            'short_description' => 'krótki opis',
            'long_description' => 'pełny opis',
            'meta_title' => 'tytuł meta',
            'meta_description' => 'opis meta',
            'weight' => 'waga',
            'height' => 'wysokość',
            'width' => 'szerokość',
            'length' => 'długość',
            'ean' => 'EAN',
            'tax_rate' => 'stawka VAT',
            'tax_rate_override' => 'stawka VAT (sklep)', // FAZA 5.2
            'manufacturer' => 'producent',
            'supplier_code' => 'kod dostawcy',
            'product_type_id' => 'typ produktu',
            'is_active' => 'aktywny',
            'is_variant_master' => 'główny wariant',
            'is_featured' => 'wyróżniony',
            'sort_order' => 'kolejność',
            'available_from' => 'dostępny od',
            'available_to' => 'dostępny do',
            'contextCategories' => 'kategorie', // FIX 2025-11-19: Track category changes for UI label
        ];

        $changedFields = [];

        // Compare old vs new values and collect changed field names
        foreach ($changes as $fieldKey => $newValue) {
            // FIX 2025-11-19: Special handling for contextCategories (not a DB column)
            if ($fieldKey === 'contextCategories') {
                // Categories changed - always add to changedFields (stored in separate table)
                if (!empty($newValue)) {
                    $changedFields[] = $fieldNameMapping[$fieldKey];
                }
                continue; // Skip normal comparison
            }

            // Skip non-field keys
            if (!array_key_exists($fieldKey, $fieldNameMapping)) {
                continue;
            }

            $oldValue = $productShopData->getOriginal($fieldKey) ?? $productShopData->{$fieldKey};

            // FIX 2025-11-18: Strict normalization to prevent false positives
            // (e.g., null vs "", "23" vs 23, 23.00 vs 23)
            $oldValueNormalized = $this->normalizeValueForComparison($oldValue);
            $newValueNormalized = $this->normalizeValueForComparison($newValue);

            // Check if values are TRULY different (strict comparison after normalization)
            if ($oldValueNormalized !== $newValueNormalized) {
                $changedFields[] = $fieldNameMapping[$fieldKey];
            }
        }

        // FIX 2025-11-21: Handle category_mappings BEFORE save
        // Create canonical Option A format from UI data (PPM ID + primary preserved)
        $categoryMappings = $productShopData->category_mappings; // Keep existing if no changes

        if (isset($changes['contextCategories'])) {
            $shop = \App\Models\PrestaShopShop::find($shopId);

            if ($shop) {
                $shopCategoryData = $changes['contextCategories'];
                $selectedCategories = $shopCategoryData['selected'] ?? [];
                $primaryCategory = $shopCategoryData['primary'] ?? null;

                Log::debug('[FIX 2025-11-21] savePendingChangesToShop: Processing categories', [
                    'product_id' => $this->product->id,
                    'shop_id' => $shopId,
                    'received_contextCategories' => $shopCategoryData,
                    'extracted_selected' => $selectedCategories,
                    'extracted_primary' => $primaryCategory,
                ]);

                // STEP 1: Ensure all IDs are PPM IDs (map PrestaShop → PPM if needed)
                $categoryMapper = app(\App\Services\PrestaShop\CategoryMapper::class);
                $ppmCategoryIds = [];
                $mappings = [];

                foreach ($selectedCategories as $categoryId) {
                    // Check if this is already a PPM category ID
                    $ppmCategory = \App\Models\Category::find($categoryId);

                    if ($ppmCategory) {
                        // Already PPM ID - use directly
                        $ppmId = $categoryId;
                        Log::debug('[FIX 2025-11-21] Category is PPM ID', ['id' => $categoryId, 'name' => $ppmCategory->name]);
                    } else {
                        // Might be PrestaShop ID - map to PPM (or create if missing)
                        try {
                            $ppmId = $categoryMapper->mapOrCreateFromPrestaShop($categoryId, $shop);
                            Log::info('[FIX 2025-11-21] Mapped PS→PPM', ['ps_id' => $categoryId, 'ppm_id' => $ppmId]);
                        } catch (\Exception $e) {
                            Log::warning('[FIX 2025-11-21] Failed to map category, skipping', [
                                'id' => $categoryId,
                                'error' => $e->getMessage(),
                            ]);
                            continue; // Skip unmappable categories
                        }
                    }

                    // Get PrestaShop ID for this PPM category
                    $prestashopId = $categoryMapper->mapToPrestaShop($ppmId, $shop);

                    if ($prestashopId === null) {
                        Log::warning('[FIX 2025-11-21] PPM category has no PS mapping, skipping', [
                            'ppm_id' => $ppmId,
                        ]);
                        continue;
                    }

                    $ppmCategoryIds[] = $ppmId;
                    $mappings[(string)$ppmId] = $prestashopId;
                }

                // FIX 2025-11-21 (Fix #11): REMOVED auto-injection of root categories
                // Previous code ALWAYS added "Baza" + "Wszystko" even when user explicitly unchecked them
                // This caused category changes to NOT persist (roots would re-appear after save)
                // Now we respect user's selection - if they unchecked roots, don't add them back!
                Log::info('[FIX #11 2025-11-21] Respecting user category selection (no auto-injection)', [
                    'product_id' => $this->product->id,
                    'shop_id' => $shopId,
                    'user_selected_categories' => $ppmCategoryIds,
                ]);

                // STEP 2: Determine primary category (preserve from UI, or use first if not set)
                $primaryPpmId = null;

                if ($primaryCategory !== null) {
                    // Primary specified in UI - ensure it's a PPM ID
                    $primaryPpmCategory = \App\Models\Category::find($primaryCategory);

                    if ($primaryPpmCategory) {
                        $primaryPpmId = $primaryCategory; // Already PPM ID
                    } else {
                        // Might be PS ID - map to PPM
                        try {
                            $primaryPpmId = $categoryMapper->mapOrCreateFromPrestaShop($primaryCategory, $shop);
                            Log::info('[FIX 2025-11-21] Mapped primary PS→PPM', [
                                'ps_id' => $primaryCategory,
                                'ppm_id' => $primaryPpmId,
                            ]);
                        } catch (\Exception $e) {
                            Log::warning('[FIX 2025-11-21] Failed to map primary, using first category', [
                                'primary_id' => $primaryCategory,
                                'error' => $e->getMessage(),
                            ]);
                            $primaryPpmId = $ppmCategoryIds[0] ?? null;
                        }
                    }
                } else {
                    // No primary specified - use first category
                    $primaryPpmId = $ppmCategoryIds[0] ?? null;
                }

                // STEP 4: Build canonical Option A format
                $categoryMappings = [
                    'ui' => [
                        'selected' => $ppmCategoryIds,
                        'primary' => $primaryPpmId,
                    ],
                    'mappings' => $mappings,
                    'metadata' => [
                        'last_updated' => now()->toIso8601String(),
                        'source' => 'manual', // User edited via form (manual edit)
                        'notes' => 'FIX 2025-11-21: Canonical Option A with preserved primary',
                    ],
                ];

                Log::info('[FIX 2025-11-21] Created canonical Option A', [
                    'product_id' => $this->product->id,
                    'shop_id' => $shopId,
                    'ppm_category_ids' => $ppmCategoryIds,
                    'primary_ppm_id' => $primaryPpmId,
                    'mappings_count' => count($mappings),
                    'canonical_format' => $categoryMappings,
                ]);
            }
        }

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
            // FAZA 5.2: Shop-specific tax rate override (NULL = use default)
            'tax_rate_override' => array_key_exists('tax_rate_override', $changes)
                ? $changes['tax_rate_override']
                : $productShopData->tax_rate_override,
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
            // CRITICAL FIX (2025-11-07): Mark as pending sync after changes
            'sync_status' => 'pending',
            'pending_fields' => !empty($changedFields) ? $changedFields : null, // FEATURE: Field-level tracking
            'is_published' => $productShopData->is_published ?? false,
            // FIX 2025-11-20 (ETAP_07b): Save category_mappings JSON (Option A)
            'category_mappings' => $categoryMappings,
        ]);

        $productShopData->save();

        // FIX 2025-11-20 (ETAP_07b): Old product_categories table logic REMOVED
        // Categories are now saved to category_mappings JSON column (Option A canonical format)
        // See lines 5092-5126 above for conversion logic

        // === FEATURES (CONTEXT-ISOLATED SYSTEM - OPCJA B: PER-SHOP FEATURES) ===
        // FIX 2025-12-03: Save productFeatures to shop-specific storage (attribute_mappings)
        // Each shop has its own feature values - NOT saved to global product_features table!
        // This allows different features per shop, same as other shop-specific data
        // Storage: ProductShopData.attribute_mappings.features JSON
        if (isset($changes['productFeatures'])) {
            $this->productFeatures = $changes['productFeatures'];
            $this->saveShopFeatures($shopId);
            Log::info('[FEATURE SAVE] Shop features saved to attribute_mappings (per-shop storage)', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'features_count' => count($this->productFeatures),
            ]);
        }

        // === VARIANTS (CONTEXT-ISOLATED SYSTEM - ETAP_05b FAZA 5: PER-SHOP VARIANTS) ===
        // Save shop variant overrides to attribute_mappings.variants JSON
        // Each shop can have different variant SKUs, names, prices, etc.
        if ($this->shopHasVariantOverrides($shopId)) {
            $this->saveShopVariantOverridesToDb($shopId);
            Log::info('[VARIANT SAVE] Shop variant overrides saved to attribute_mappings', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'override_count' => $this->getShopVariantOverrideCount($shopId),
            ]);
        }

        Log::info('Shop-specific data updated from pending changes', [
            'product_id' => $this->product->id,
            'shop_id' => $shopId,
            'shop_data_id' => $productShopData->id,
            'changes_applied' => count($changes),
        ]);

        // CRITICAL FIX (2025-11-07): Auto-dispatch sync job after shop data save
        // BUG: User saves changes in shop tab -> data saved with 'pending' BUT sync job was never created
        // FIX: Automatically dispatch sync job when shop data is saved (same as saveShopSpecificData)
        // USER_ID FIX (2025-11-07): Pass auth()->id() to capture user who triggered sync
        try {
            $shop = \App\Models\PrestaShopShop::find($shopId);

            if ($shop && $shop->connection_status === 'connected' && $shop->is_active) {
                \App\Jobs\PrestaShop\SyncProductToPrestaShop::dispatch($this->product, $shop, auth()->id());

                // FIX 2025-11-25: Set job tracking variables ONLY if not skipping
                // When skipJobTracking=true (from saveAndClose), job runs in background
                // but UI doesn't show progress - allows immediate redirect to product list
                if (!$skipJobTracking) {
                    $this->activeJobType = 'sync';
                    $this->jobCreatedAt = now()->toIso8601String();
                    $this->activeJobStatus = 'pending';
                }

                Log::info('Auto-dispatched sync job after shop data save (from pending changes)', [
                    'product_id' => $this->product->id,
                    'shop_id' => $shopId,
                    'shop_name' => $shop->name,
                    'trigger' => 'savePendingChangesToShop',
                    'job_tracking_enabled' => !$skipJobTracking,
                    'skip_job_tracking' => $skipJobTracking,
                ]);
            } else {
                Log::warning('Sync job NOT dispatched - shop not connected or inactive', [
                    'product_id' => $this->product->id,
                    'shop_id' => $shopId,
                    'shop_status' => $shop?->connection_status ?? 'not_found',
                    'shop_active' => $shop?->is_active ?? false,
                ]);
            }
        } catch (\Exception $e) {
            // Non-blocking error - data is saved, but sync will need manual trigger
            Log::error('Failed to auto-dispatch sync job after shop data save (from pending changes)', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
        }
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
    | ETAP_13: ANTI-DUPLICATE JOB LOGIC (2025-11-17)
    |--------------------------------------------------------------------------
    */

    /**
     * Check if product has active sync job in queue
     *
     * ETAP_13: Backend Foundation - Anti-Duplicate Logic
     *
     * Prevents dispatching duplicate sync jobs when user clicks "Save" multiple times
     *
     * @return bool True if active job exists
     */
    protected function hasActiveSyncJob(): bool
    {
        if (!$this->product || !$this->product->id) {
            return false;
        }

        $productId = $this->product->id;

        // Check pending jobs in queue
        $hasJob = \Illuminate\Support\Facades\DB::table('jobs')
            ->where('queue', 'prestashop_sync')
            ->where('payload', 'like', '%"product_id":' . $productId . '%')
            ->exists();

        return $hasJob;
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

            // FIX 2025-11-26 #4: Use service directly instead of API call (API returns null)
            $shop = \App\Models\PrestaShopShop::find($shopId);
            if (!$shop) {
                throw new \Exception("Shop not found: {$shopId}");
            }

            $categoryService = app(\App\Services\PrestaShop\PrestaShopCategoryService::class);

            // Clear cache for this shop
            $categoryService->clearCache($shop);

            // Reload fresh categories from PrestaShop API
            $tree = $categoryService->getCachedCategoryTree($shop);

            // Convert to objects for Blade compatibility
            $categories = array_map([$this, 'convertCategoryArrayToObject'], $tree);

            // Update component state
            $this->prestashopCategories[$shopId] = $categories;

            Log::info("PrestaShop categories refreshed successfully", [
                'shop_id' => $shopId,
                'root_categories' => count($categories),
            ]);

            $this->dispatch('success', message: "Kategorie odświeżone (" . count($categories) . " głównych)");

        } catch (\Exception $e) {
            Log::error("Failed to refresh PrestaShop categories", [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);

            // Don't show error notification - just log it
            // The tree might still work with cached data
        }
    }

    /**
     * Refresh categories from active shop (ETAP_07b FAZA 1)
     *
     * Clears category cache and forces fresh API call.
     * Used by "Odśwież kategorie" button in ProductForm.
     *
     * @return void
     */
    public function refreshCategoriesFromShop(): void
    {
        if (!$this->activeShopId) {
            Log::warning('refreshCategoriesFromShop called without active shop');
            return;
        }

        try {
            $shop = PrestaShopShop::find($this->activeShopId);

            if (!$shop) {
                throw new \Exception("Shop not found: {$this->activeShopId}");
            }

            // Get category service
            $categoryService = app(\App\Services\PrestaShop\PrestaShopCategoryService::class);

            // Clear cache - forces fresh API call on next getShopCategories() call
            $categoryService->clearCache($shop);

            Log::info('Category cache cleared for shop', [
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
            ]);

            // ETAP_07b FAZA 1 FIX: Trigger Livewire re-render to fetch fresh categories
            // This will cause Blade to call getShopCategories() again, which fetches from cleared cache
            $this->dispatch('$refresh');

            session()->flash('success', 'Kategorie odświeżone z PrestaShop');

        } catch (\Exception $e) {
            Log::error('Failed to refresh categories from shop', [
                'shop_id' => $this->activeShopId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            session()->flash('error', "Błąd podczas odświeżania kategorii: {$e->getMessage()}");
        }
    }

    /**
     * Get shop categories (ETAP_07b FAZA 1)
     *
     * Returns hierarchical category tree for active shop from PrestaShop.
     * If no shop active, returns PPM default categories.
     *
     * @return array Category tree
     */
    public function getShopCategories(): array
    {
        if (!$this->activeShopId) {
            // Default TAB - return PPM categories
            return $this->getDefaultCategories();
        }

        try {
            $shop = PrestaShopShop::find($this->activeShopId);

            if (!$shop) {
                Log::warning('Shop not found in getShopCategories()', [
                    'shop_id' => $this->activeShopId,
                ]);
                return $this->getDefaultCategories();
            }

            // Get category service
            $categoryService = app(\App\Services\PrestaShop\PrestaShopCategoryService::class);

            // Get cached category tree (15min TTL)
            $tree = $categoryService->getCachedCategoryTree($shop);

            // ETAP_07b FAZA 1 FIX: Convert arrays to objects for Blade compatibility
            // Blade partial expects objects with ->children property, not arrays
            $categories = array_map([$this, 'convertCategoryArrayToObject'], $tree);

            // 2025-11-26 FIX: Inject pending categories into tree for display
            // Pending categories are stored locally until Save, need to show them in tree
            $shopIdStr = (string) $this->activeShopId;
            if (!empty($this->pendingNewCategories[$shopIdStr])) {
                foreach ($this->pendingNewCategories[$shopIdStr] as $pending) {
                    $pendingObj = (object) [
                        'id' => $pending['tempId'],
                        'name' => $pending['name'] . ' (oczekuje)',
                        'parent_id' => $pending['parentId'],
                        'children' => collect([]),
                        'is_pending' => true,
                        'sort_order' => 9999,
                    ];
                    $this->addCategoryToArrayTreeRecursive($categories, $pending['parentId'], $pendingObj);
                }
                Log::debug('[getShopCategories] Injected pending categories', [
                    'shop_id' => $this->activeShopId,
                    'pending_count' => count($this->pendingNewCategories[$shopIdStr]),
                ]);
            }

            return $categories;

        } catch (\Exception $e) {
            Log::error('Failed to get shop categories', [
                'shop_id' => $this->activeShopId,
                'error' => $e->getMessage(),
            ]);
            return $this->getDefaultCategories();
        }
    }

    /**
     * Get default PPM categories (fallback)
     *
     * @return array Category tree
     */
    protected function getDefaultCategories(): array
    {
        // Load PPM categories from database
        // FIX 2025-11-24: Eager-load 5 levels deep (zgodnie z CLAUDE.md - 5 poziomów zagnieżdżenia)
        $categories = Category::whereNull('parent_id')
            ->with('children.children.children.children.children')
            ->orderBy('sort_order')
            ->get();

        $categoryArray = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'level' => 1,
                'children' => $this->mapCategoryChildren($category->children),
            ];
        })->toArray();

        // ETAP_07b FAZA 1 FIX: Convert arrays to objects for Blade compatibility
        $result = array_map([$this, 'convertCategoryArrayToObject'], $categoryArray);

        // 2025-11-26 FIX: Inject pending categories into tree for display
        if (!empty($this->pendingNewCategories['default'])) {
            foreach ($this->pendingNewCategories['default'] as $pending) {
                $pendingObj = (object) [
                    'id' => $pending['tempId'],
                    'name' => $pending['name'] . ' (oczekuje)',
                    'parent_id' => $pending['parentId'],
                    'children' => collect([]),
                    'is_pending' => true,
                    'sort_order' => 9999,
                ];
                $this->addCategoryToArrayTreeRecursive($result, $pending['parentId'], $pendingObj);
            }
            Log::debug('[getDefaultCategories] Injected pending categories', [
                'pending_count' => count($this->pendingNewCategories['default']),
            ]);
        }

        return $result;
    }

    /**
     * Get category validation status for active shop (ETAP_07b FAZA 2)
     *
     * Compares shop categories with default categories and returns status badge data.
     *
     * Business Logic:
     * - "zgodne" (green) = shop categories identical to default
     * - "własne" (blue) = shop has custom categories
     * - "dziedziczone" (gray) = inherits from default (no shop-specific)
     *
     * Performance: Cached per shop to avoid repeated validation
     *
     * @return array|null ['status' => string, 'badge' => array, 'tooltip' => string|null] or null if default tab
     */
    public function getCategoryValidationStatus(): ?array
    {
        // Default TAB - no validation needed
        if (!$this->activeShopId || !$this->product) {
            return null;
        }

        // FIX 2025-11-21: DON'T use cache - we need REAL-TIME comparison with current Livewire properties
        // OLD: Check cache first (showed "Dziedziczone" even when categories selected in UI)
        // NEW: Always compare current Livewire properties (real-time reactive badge)

        try {
            // FIX 2025-11-21: Compare CURRENT Livewire properties, NOT database data
            // Root Cause: CategoryValidatorService reads from DB (old data), but user just selected categories in UI (new data)
            // Solution: Compare $this->defaultCategories vs $this->shopCategories directly

            // Get current Livewire properties (what user sees in UI right now)
            $defaultCategories = $this->defaultCategories['selected'] ?? [];
            $defaultPrimary = $this->defaultCategories['primary'] ?? null;

            $shopCategories = $this->shopCategories[$this->activeShopId]['selected'] ?? [];
            $shopPrimary = $this->shopCategories[$this->activeShopId]['primary'] ?? null;

            // Determine status (same logic as CategoryValidatorService)
            if (empty($shopCategories)) {
                // No shop-specific categories → inherits from default
                $status = 'dziedziczone';
                $diff = [];
            } elseif ($this->areCategoriesIdenticalForBadge($defaultCategories, $shopCategories, $defaultPrimary, $shopPrimary)) {
                // Shop categories identical to default
                $status = 'zgodne';
                $diff = [];
            } else {
                // Shop has custom categories
                $status = 'własne';
                $diff = $this->generateDiffReportForBadge($defaultCategories, $shopCategories, $defaultPrimary, $shopPrimary);
            }

            // Get badge configuration
            $validator = app(\App\Services\CategoryValidatorService::class);
            $badge = $validator->getStatusBadge($status);

            // Get tooltip text (simplified for real-time)
            $tooltip = match($status) {
                'zgodne' => 'Kategorie w tym sklepie są identyczne z domyślnymi kategoriami PPM',
                'własne' => 'Sklep używa własnych kategorii, różniących się od kategorii domyślnych PPM',
                'dziedziczone' => 'Sklep dziedziczy kategorie z kategorii domyślnych PPM (brak własnych kategorii)',
                default => null,
            };

            // DON'T cache - we need real-time reactivity
            return [
                'status' => $status,
                'badge' => $badge,
                'tooltip' => $tooltip,
                'diff' => $diff,
                'metadata' => [
                    'default_count' => count($defaultCategories),
                    'shop_count' => count($shopCategories),
                    'default_primary' => $defaultPrimary,
                    'shop_primary' => $shopPrimary,
                    'realtime' => true, // Flag to indicate this is real-time comparison
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get category validation status', [
                'shop_id' => $this->activeShopId,
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Check if categories are identical (for badge real-time comparison)
     *
     * @param array $defaultCategories
     * @param array $shopCategories
     * @param int|null $defaultPrimary
     * @param int|null $shopPrimary
     * @return bool
     */
    private function areCategoriesIdenticalForBadge(array $defaultCategories, array $shopCategories, ?int $defaultPrimary, ?int $shopPrimary): bool
    {
        // Sort for comparison (order doesn't matter)
        sort($defaultCategories);
        sort($shopCategories);

        // Check if arrays are identical
        if ($defaultCategories !== $shopCategories) {
            return false;
        }

        // Check if primary categories match
        if ($defaultPrimary !== $shopPrimary) {
            return false;
        }

        return true;
    }

    /**
     * Generate diff report for badge (simplified)
     *
     * @param array $defaultCategories
     * @param array $shopCategories
     * @param int|null $defaultPrimary
     * @param int|null $shopPrimary
     * @return array
     */
    private function generateDiffReportForBadge(array $defaultCategories, array $shopCategories, ?int $defaultPrimary, ?int $shopPrimary): array
    {
        return [
            'added' => array_values(array_diff($shopCategories, $defaultCategories)),
            'removed' => array_values(array_diff($defaultCategories, $shopCategories)),
            'primary_changed' => $defaultPrimary !== $shopPrimary,
        ];
    }

    /**
     * Invalidate category validation cache
     *
     * Called when categories are modified to force re-evaluation of validation status
     */
    private function invalidateCategoryValidationCache(?int $shopId = null): void
    {
        if ($shopId === null) {
            $shopId = $this->activeShopId;
        }

        // Clear cache for specific shop
        if ($shopId !== null && isset($this->categoryValidationStatus[$shopId])) {
            unset($this->categoryValidationStatus[$shopId]);
        }

        // Also clear default context cache if categories changed
        if (isset($this->categoryValidationStatus[0])) {
            unset($this->categoryValidationStatus[0]);
        }
    }

    /**
     * Recursively map category children
     *
     * @param \Illuminate\Database\Eloquent\Collection $children
     * @return array
     */
    protected function mapCategoryChildren($children): array
    {
        return $children->map(function ($child) {
            return [
                'id' => $child->id,
                'name' => $child->name,
                'level' => ($child->parent?->level ?? 0) + 1,
                'children' => $this->mapCategoryChildren($child->children ?? collect()),
            ];
        })->toArray();
    }

    /**
     * Convert category array to object for Blade compatibility
     *
     * ETAP_07b FAZA 1 FIX: Blade partial expects objects with ->children property,
     * but PrestaShopCategoryService returns arrays with ['children'] key
     *
     * @param array $category Category data as array
     * @return \stdClass Category data as object
     */
    protected function convertCategoryArrayToObject(array $category): \stdClass
    {
        $obj = new \stdClass();
        $obj->id = $category['id'];
        $obj->name = $category['name'];
        $obj->level = $category['level'] ?? 1;

        // Recursively convert children
        $obj->children = collect($category['children'] ?? [])->map(function($child) {
            return $this->convertCategoryArrayToObject($child);
        });

        return $obj;
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
     * Load product data from PrestaShop API (lazy loading on first shop tab click)
     *
     * ETAP_07 FIX - Lazy loading pattern to avoid unnecessary API calls
     * NOTE: Different from private loadShopData() which loads from DB
     *
     * @param int $shopId Shop ID to load data from
     * @param bool $forceReload Force reload even if cached
     * @return void
     */
    public function loadProductDataFromPrestaShop(int $shopId, bool $forceReload = false): void
    {
        // If already loaded and not forcing reload, skip
        if (isset($this->loadedShopData[$shopId]) && !$forceReload) {
            Log::info('Shop data already loaded (cached)', ['shop_id' => $shopId]);
            // FIX 2025-11-28: Dispatch end event for Alpine.js overlay (cache hit = instant hide)
            $this->dispatch('prestashop-loading-end');
            return;
        }

        $this->isLoadingShopData = true;

        try {
            // 1. Get shop from DB
            $shop = PrestaShopShop::findOrFail($shopId);

            // 2. Get ProductShopData (contains prestashop_product_id = PrestaShop product ID)
            // ETAP_07 OPCJA B (2025-10-13): Consolidated - external_id replaced by prestashop_product_id
            $shopData = $this->product->shopData()
                ->where('shop_id', $shopId)
                ->first();

            // ETAP_07 FIX (2025-11-13): Better error message when product not linked to shop
            if (!$shopData) {
                throw new \Exception("Produkt nie jest podłączony do tego sklepu. Użyj przycisku '+ Dodaj sklep' aby połączyć produkt ze sklepem PrestaShop.");
            }

            if (!$shopData->prestashop_product_id) {
                throw new \Exception("Produkt nie ma ID w PrestaShop. Wykonaj najpierw synchronizację (przycisk 'Aktualizuj sklep') aby utworzyć produkt w PrestaShop.");
            }

            // FIX 2025-11-25: SKIP PrestaShop API fetch when sync job is pending
            // Problem: User saves categories → Job dispatches → User re-enters product
            //          → loadProductDataFromPrestaShop() fetches OLD data from PrestaShop
            //          → Overwrites user's saved categories with stale PrestaShop data
            //
            // Solution: When sync_status === 'pending':
            // - DON'T fetch from PrestaShop API (job hasn't updated PS yet!)
            // - Load categories from LOCAL database (what user saved)
            // - DON'T save anything back to DB
            // - Pull only AFTER job completes (in checkBackgroundJobStatus)
            $isPendingSync = ($shopData->sync_status === 'pending');

            if ($isPendingSync) {
                Log::info('[SKIP PRESTASHOP FETCH] Sync job pending - using LOCAL database categories', [
                    'shop_id' => $shopId,
                    'product_id' => $this->product?->id,
                    'sync_status' => $shopData->sync_status,
                    'reason' => 'Job not completed yet - PrestaShop has stale data',
                ]);

                // Load categories from LOCAL database (Option A format)
                $localMappings = $shopData->category_mappings;
                if ($localMappings && isset($localMappings['ui'])) {
                    $this->shopCategories[$shopId] = [
                        'selected' => $localMappings['ui']['selected'] ?? [],
                        'primary' => $localMappings['ui']['primary'] ?? null,
                    ];

                    Log::info('[LOCAL CATEGORIES LOADED] Using saved categories from PPM database', [
                        'shop_id' => $shopId,
                        'selected_count' => count($this->shopCategories[$shopId]['selected']),
                        'primary' => $this->shopCategories[$shopId]['primary'],
                    ]);
                }

                // Mark as loaded (but from local DB, not PrestaShop)
                $this->loadedShopData[$shopId] = [
                    'prestashop_id' => $shopData->prestashop_product_id,
                    'categories' => [], // Empty - we used local data
                    'id_category_default' => null,
                    'loaded_from' => 'local_db_pending_sync',
                ];

                // Load form data from local DB
                $this->loadShopDataToForm($shopId);

                $this->isLoadingShopData = false;
                // FIX 2025-11-28: Dispatch end event for Alpine.js overlay (pending sync = instant hide)
                $this->dispatch('prestashop-loading-end');
                session()->flash('message', 'Oczekiwanie na synchronizacje - pokazano zapisane dane');
                return; // SKIP PrestaShop API call entirely
            }

            // 3. Fetch from PrestaShop API (only when NOT pending)
            // FIX 2025-11-28: Alpine.js shows overlay IMMEDIATELY on button click (x-on:click)
            // PHP only dispatches 'prestashop-loading-end' when done (in finally block)

            $client = PrestaShopClientFactory::create($shop);
            $prestashopData = $client->getProduct($shopData->prestashop_product_id);

            // Unwrap nested response (PrestaShop API wraps in 'product' key)
            if (isset($prestashopData['product'])) {
                $prestashopData = $prestashopData['product'];
            }

            // 4. Extract essential data for UI (only executed when NOT pending - see early return above)
            // FIX 2025-11-25: Simplified - pending sync handled above with early return
            $this->loadedShopData[$shopId] = [
                'prestashop_id' => $shopData->prestashop_product_id,
                'categories' => $prestashopData['associations']['categories'] ?? [],
                'id_category_default' => $prestashopData['id_category_default'] ?? null,
                'link_rewrite' => data_get($prestashopData, 'link_rewrite.0.value') ?? data_get($prestashopData, 'link_rewrite'),
                'name' => data_get($prestashopData, 'name.0.value') ?? data_get($prestashopData, 'name'),
                'description_short' => data_get($prestashopData, 'description_short.0.value') ?? data_get($prestashopData, 'description_short'),
                'description' => data_get($prestashopData, 'description.0.value') ?? data_get($prestashopData, 'description'),
                'weight' => $prestashopData['weight'] ?? null,
                'ean13' => $prestashopData['ean13'] ?? null,
                'reference' => $prestashopData['reference'] ?? null,
                'price' => $prestashopData['price'] ?? null,
                'active' => $prestashopData['active'] ?? null,
                'loaded_from' => 'prestashop_api',
            ];

            session()->flash('message', 'Dane produktu wczytane z PrestaShop');

            // FIX 2025-11-20: Convert PrestaShop category IDs → PPM category IDs and update UI
            // User Request: "celowo zmieniam kategorie w prestashop aby weryfikowac czy PPM wykryje"
            //
            // CRITICAL: Always update UI categories after fetch from PrestaShop API
            // - Even during pending sync (categories are READ-ONLY)
            // - User wants to see CURRENT PrestaShop categories in UI
            $prestashopCategories = $this->loadedShopData[$shopId]['categories'] ?? [];
            $prestashopDefaultCategory = $this->loadedShopData[$shopId]['id_category_default'] ?? null;

            if (!empty($prestashopCategories)) {
                $categoryMapper = app(\App\Services\PrestaShop\CategoryMapper::class);
                $ppmCategoryIds = [];

                // Convert PrestaShop IDs → PPM IDs (auto-create if missing)
                // FIX 2025-11-20: Use mapOrCreateFromPrestaShop() to automatically create
                // categories that exist in PrestaShop but not in PPM
                foreach ($prestashopCategories as $psCategory) {
                    $psCategoryId = is_array($psCategory) ? ($psCategory['id'] ?? null) : $psCategory;

                    if ($psCategoryId) {
                        try {
                            // NEW: Auto-create missing categories
                            $ppmId = $categoryMapper->mapOrCreateFromPrestaShop((int) $psCategoryId, $shop);
                            $ppmCategoryIds[] = $ppmId;
                        } catch (\Exception $e) {
                            Log::error('[AUTO-CREATE CATEGORY] Failed to map/create category', [
                                'prestashop_id' => $psCategoryId,
                                'shop_id' => $shop->id,
                                'error' => $e->getMessage(),
                            ]);
                            // Skip this category on error (don't break the whole process)
                        }
                    }
                }

                // Convert default category (auto-create if missing)
                $ppmPrimaryId = null;
                if ($prestashopDefaultCategory) {
                    try {
                        $ppmPrimaryId = $categoryMapper->mapOrCreateFromPrestaShop((int) $prestashopDefaultCategory, $shop);
                    } catch (\Exception $e) {
                        Log::error('[AUTO-CREATE CATEGORY] Failed to map/create default category', [
                            'prestashop_id' => $prestashopDefaultCategory,
                            'shop_id' => $shop->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Update UI categories (show current PrestaShop state)
                $this->shopCategories[$shopId] = [
                    'selected' => $ppmCategoryIds,
                    'primary' => $ppmPrimaryId
                ];

                // CRITICAL FIX 2025-11-20: Save to database to prevent sync from using stale data
                // Problem: Sync reads from DB (ProductShopData.category_mappings), not from UI
                // Solution: Update DB immediately after fetch from PrestaShop API
                //
                // Build mappings for Option A format (use SAME ppmCategoryIds from above)
                // FIX 2025-11-20: Mappings should match selected categories exactly
                // No need to call mapOrCreateFromPrestaShop() again - we already have ppmCategoryIds
                $mappings = [];
                foreach ($prestashopCategories as $index => $psCategory) {
                    $psCategoryId = is_array($psCategory) ? ($psCategory['id'] ?? null) : $psCategory;
                    if ($psCategoryId && isset($ppmCategoryIds[$index])) {
                        // Use PPM ID from first loop (already created if needed)
                        $mappings[(string) $ppmCategoryIds[$index]] = (int) $psCategoryId;
                    }
                }

                // Create Option A format (manual construction - we already have mappings)
                $optionAData = [
                    'ui' => [
                        'selected' => $ppmCategoryIds,
                        'primary' => $ppmPrimaryId,
                    ],
                    'mappings' => $mappings,
                    'metadata' => [
                        'last_updated' => now()->toIso8601String(),
                        'source' => 'pull', // Pulled from PrestaShop API
                    ],
                ];

                // Validate Option A format
                $validator = app(\App\Services\CategoryMappingsValidator::class);
                $optionAData = $validator->validate($optionAData);

                // Save to database (update ONLY categories, preserve other fields)
                $shopData->category_mappings = $optionAData;
                $shopData->save();

                // CRITICAL FIX 2025-11-20: Force reload categories from DB to UI
                // Problem: loadShopDataToForm() already loaded STALE categories before we saved
                // Solution: Reload THIS shop's categories from DB (fresh data)
                $this->loadShopCategories($shopId);

                Log::info('[CATEGORY SYNC] Reloaded shop categories from DB to UI after save', [
                    'shop_id' => $shopId,
                    'ui_categories_after_reload' => $this->shopCategories[$shopId] ?? 'NOT_SET',
                ]);

                // Dispatch event to update Alpine.js category tree
                $this->dispatch('category-tree-refresh', shopId: $shopId);

                Log::info('[CATEGORY SYNC] Converted PrestaShop categories to PPM IDs and saved to DB', [
                    'shop_id' => $shopId,
                    'prestashop_ids' => array_map(fn($c) => is_array($c) ? ($c['id'] ?? 'unknown') : $c, $prestashopCategories),
                    'ppm_ids' => $ppmCategoryIds,
                    'prestashop_default' => $prestashopDefaultCategory,
                    'ppm_primary' => $ppmPrimaryId,
                    'saved_to_db' => true,
                    'mappings_count' => count($mappings),
                ]);
            }

            Log::info('Shop data loaded from PrestaShop', [
                'shop_id' => $shopId,
                'product_id' => $this->product?->id,
                'prestashop_id' => $shopData->prestashop_product_id,
                'loaded_from' => 'prestashop_api',
                'categories_count' => count($this->loadedShopData[$shopId]['categories']),
                'link_rewrite' => $this->loadedShopData[$shopId]['link_rewrite'] ?? 'N/A',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load shop data from PrestaShop', [
                'shop_id' => $shopId,
                'product_id' => $this->product?->id,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Blad wczytywania danych: ' . $e->getMessage());
        } finally {
            $this->isLoadingShopData = false;
            // FIX 2025-11-28: Dispatch event to hide Alpine.js loading overlay
            $this->dispatch('prestashop-loading-end');
        }
    }

    /**
     * Get PrestaShop product frontend URL
     *
     * ETAP_07 FIX - Generate correct frontend URL (not admin URL)
     *
     * @param int $shopId Shop ID
     * @return string|null Frontend product URL or null if not available
     */
    public function getProductPrestaShopUrl(int $shopId): ?string
    {
        $shop = collect($this->availableShops)->firstWhere('id', $shopId);

        if (!$shop) {
            return null;
        }

        // Try to get data from cache first (loaded from PrestaShop API)
        $shopData = $this->loadedShopData[$shopId] ?? null;

        if ($shopData && isset($shopData['prestashop_id'])) {
            $productId = $shopData['prestashop_id'];
            $linkRewrite = $shopData['link_rewrite'] ?? null;

            if ($linkRewrite) {
                return rtrim($shop['url'], '/') . "/{$productId}-{$linkRewrite}.html";
            }
        }

        // CONSOLIDATED 2025-10-13: Fallback to ProductShopData
        // ProductShopData now contains prestashop_product_id and external_reference for URL generation
        if ($this->product && $this->product->exists) {
            $shopData = $this->product->shopData()
                ->where('shop_id', $shopId)
                ->first();

            if ($shopData && $shopData->prestashop_product_id) {
                $productId = $shopData->prestashop_product_id;
                $linkRewrite = $shopData->external_reference;

                if ($linkRewrite) {
                    return rtrim($shop['url'], '/') . "/{$productId}-{$linkRewrite}.html";
                }

                return rtrim($shop['url'], '/') . "/index.php?id_product={$productId}&controller=product";
            }
        }

        return null;
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

        // ETAP_07 FIX: Auto-load shop data on first shop tab click (lazy loading)
        if (!isset($this->loadedShopData[$shopId]) && $this->isEditMode) {
            Log::info('Auto-loading shop data from PrestaShop on shop tab switch', [
                'shop_id' => $shopId,
            ]);
            $this->loadProductDataFromPrestaShop($shopId);
        }
    }

    /**
     * Cancel form and return to list
     */
    public function cancel()
    {
        // 2025-11-26: Discard pending categories (not created in PrestaShop)
        $this->discardPendingCategories();

        // FIX 2025-11-20 (ETAP_07b Fix #7): Use event-based JavaScript redirect
        $this->dispatch('redirectToProductList');
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
            // FIX 2025-11-24: Calculate expanded categories on every render
            // This ensures categories are expanded when switching between shop tabs
            // or when component is first loaded
            $this->expandedCategoryIds = $this->calculateExpandedCategoryIds();

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
            ])->layout('layouts.admin', [
                'title' => $pageTitle,
                'breadcrumb' => $this->isEditMode ? 'Edytuj produkt' : 'Dodaj produkt'
            ]);

        } catch (\Exception $e) {
            Log::error('ProductForm render() failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response('<h1>Error rendering product form</h1><p>' . $e->getMessage() . '</p>');
        }
    }


    /*
    |--------------------------------------------------------------------------
    | PRICES & STOCK SAVE METHODS (PROBLEM #4 - 2025-11-07)
    |--------------------------------------------------------------------------
    */

    /**
     * Save product prices to database
     * Called from savePendingChangesToProduct()
     */
    private function savePricesInternal(): void
    {
        if (!$this->product || !$this->product->exists) {
            Log::debug('savePricesInternal: No product to save prices for');
            return;
        }

        $savedCount = 0;
        $deletedCount = 0;

        try {
            Log::debug('BEFORE savePricesInternal', [
                'prices_array' => $this->prices,
                'prices_count' => count($this->prices),
            ]);

            foreach ($this->prices as $priceGroupId => $priceData) {
                // If price is NULL or empty, delete existing record
                if (empty($priceData['net']) && empty($priceData['gross'])) {
                    \App\Models\ProductPrice::where('product_id', $this->product->id)
                        ->where('price_group_id', $priceGroupId)
                        ->whereNull('product_variant_id')
                        ->delete();
                    $deletedCount++;
                    continue;
                }

                // Create or update price record
                \App\Models\ProductPrice::updateOrCreate(
                    [
                        'product_id' => $this->product->id,
                        'product_variant_id' => null,
                        'price_group_id' => $priceGroupId,
                    ],
                    [
                        'price_net' => $priceData['net'] ?? 0.00,
                        'price_gross' => $priceData['gross'] ?? 0.00,
                        'margin_percentage' => $priceData['margin'] ?? null,
                        'is_active' => $priceData['is_active'] ?? true,
                        'auto_calculate_gross' => false,
                        'auto_calculate_margin' => false,
                    ]
                );

                $savedCount++;
            }

            Log::info('Product prices saved', [
                'product_id' => $this->product->id,
                'saved_count' => $savedCount,
                'deleted_count' => $deletedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save product prices', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Save product stock to database
     * Called from savePendingChangesToProduct()
     */
    private function saveStockInternal(): void
    {
        if (!$this->product || !$this->product->exists) {
            Log::debug('saveStockInternal: No product to save stock for');
            return;
        }

        $savedCount = 0;

        try {
            Log::debug('BEFORE saveStockInternal', [
                'stock_array' => $this->stock,
                'stock_count' => count($this->stock),
            ]);

            foreach ($this->stock as $warehouseId => $stockData) {
                \App\Models\ProductStock::updateOrCreate(
                    [
                        'product_id' => $this->product->id,
                        'product_variant_id' => null,
                        'warehouse_id' => $warehouseId,
                    ],
                    [
                        'quantity' => $stockData['quantity'] ?? 0,
                        'reserved_quantity' => $stockData['reserved'] ?? 0,
                        'minimum_stock_level' => $stockData['minimum'] ?? 0,
                        'is_active' => true,
                        'track_stock' => true,
                    ]
                );

                $savedCount++;
            }

            Log::info('Product stock saved', [
                'product_id' => $this->product->id,
                'saved_count' => $savedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save product stock', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Dispatch sync jobs for all connected shops (2025-11-12)
     *
     * Called automatically after price/stock changes to trigger PrestaShop sync
     * Only dispatches for shops that are:
     * - Connected and active
     * - Already have ProductShopData for this product
     *
     * @return void
     */
    private function dispatchSyncJobsForAllShops(): void
    {
        if (!$this->product || !$this->product->exists) {
            return;
        }

        try {
            // Get all shops this product is exported to
            $exportedShops = \App\Models\ProductShopData::where('product_id', $this->product->id)
                ->pluck('shop_id')
                ->toArray();

            if (empty($exportedShops)) {
                Log::debug('No shops to sync - product not exported anywhere', [
                    'product_id' => $this->product->id,
                ]);
                return;
            }

            // Get connected and active shops only
            $shops = \App\Models\PrestaShopShop::whereIn('id', $exportedShops)
                ->where('connection_status', 'connected')
                ->where('is_active', true)
                ->get();

            // ETAP_07d (2025-12-02): Get pending media changes from session
            // Session is available in HTTP context but NOT in queue job context
            // So we capture it here and pass it to the job
            $sessionKey = "pending_media_sync_{$this->product->id}";
            $pendingMediaChanges = session($sessionKey, []);

            Log::info('[MEDIA SYNC] Capturing pending media changes for jobs', [
                'product_id' => $this->product->id,
                'pending_changes_count' => count($pendingMediaChanges),
                'pending_changes' => $pendingMediaChanges,
            ]);

            $dispatchedCount = 0;
            foreach ($shops as $shop) {
                \App\Jobs\PrestaShop\SyncProductToPrestaShop::dispatch(
                    $this->product,
                    $shop,
                    auth()->id(),
                    $pendingMediaChanges  // ETAP_07d: Pass pending media changes to job
                );
                $dispatchedCount++;
            }

            // Clear session after dispatching jobs (changes are now in job payload)
            if (!empty($pendingMediaChanges)) {
                session()->forget($sessionKey);
                Log::debug('[MEDIA SYNC] Cleared pending media changes from session after job dispatch');
            }

            if ($dispatchedCount > 0) {
                Log::info('Auto-dispatched sync jobs after price/stock change', [
                    'product_id' => $this->product->id,
                    'product_sku' => $this->product->sku,
                    'shops_count' => $dispatchedCount,
                    'user_id' => auth()->id(),
                    'pending_media_changes_count' => count($pendingMediaChanges),
                ]);
            }

        } catch (\Exception $e) {
            // Non-blocking error - data is saved, sync can be triggered manually
            Log::error('Failed to auto-dispatch sync jobs after price/stock change', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
