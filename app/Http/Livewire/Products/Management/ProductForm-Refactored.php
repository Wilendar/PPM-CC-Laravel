<?php

namespace App\Http\Livewire\Products\Management;

use Livewire\Component;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Category;
use App\Models\PrestaShopShop;
use App\Models\ProductAttribute;
use App\Http\Livewire\Products\Management\Traits\ProductFormValidation;
use App\Http\Livewire\Products\Management\Traits\ProductFormUpdates;
use App\Http\Livewire\Products\Management\Traits\ProductFormComputed;
use App\Http\Livewire\Products\Management\Services\ProductMultiStoreManager;
use App\Http\Livewire\Products\Management\Services\ProductCategoryManager;
use App\Http\Livewire\Products\Management\Services\ProductFormSaver;
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

    // === CATEGORIES ===
    public array $selectedCategories = [];
    public ?int $primaryCategoryId = null;

    // === MULTI-STORE SUPPORT ===
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

    // === SERVICE INSTANCES ===
    protected ProductMultiStoreManager $multiStoreManager;
    protected ProductCategoryManager $categoryManager;
    protected ProductFormSaver $formSaver;

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
        // Initialize services
        $this->multiStoreManager = new ProductMultiStoreManager($this);
        $this->categoryManager = new ProductCategoryManager($this);
        $this->formSaver = new ProductFormSaver($this);

        // Load product data or set defaults
        if ($product && $product->exists) {
            $this->product = $product;
            $this->isEditMode = true;
            $this->loadProductData();
        } else {
            $this->isEditMode = false;
            $this->setDefaults();
        }

        // Load existing shop-specific data if editing
        if ($this->isEditMode) {
            $this->multiStoreManager->loadShopData();
            $this->categoryManager->loadCategories();
        }

        $this->updateCharacterCounts();
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
        $this->selectedCategories = [];
        $this->primaryCategoryId = null;

        // Store default data
        $this->storeDefaultData();
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

        // Store default data
        $this->storeDefaultData();
    }

    /**
     * Store current form data as default data
     */
    private function storeDefaultData(): void
    {
        $this->defaultData = [
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'long_description' => $this->long_description,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CATEGORY MANAGEMENT - Delegated to Service
    |--------------------------------------------------------------------------
    */

    /**
     * Toggle category selection
     */
    public function toggleCategory(int $categoryId): void
    {
        $this->categoryManager->toggleCategory($categoryId);
    }

    /**
     * Set primary category
     */
    public function setPrimaryCategory(int $categoryId): void
    {
        $this->categoryManager->setPrimaryCategory($categoryId);
    }

    /*
    |--------------------------------------------------------------------------
    | MULTI-STORE MANAGEMENT - Delegated to Service
    |--------------------------------------------------------------------------
    */

    /**
     * Add product to selected shops
     */
    public function addToShops(): void
    {
        $this->multiStoreManager->addToShops();
    }

    /**
     * Remove product from specific shop
     */
    public function removeFromShop(int $shopId): void
    {
        $this->multiStoreManager->removeFromShop($shopId);
    }

    /**
     * Open shop selector modal
     */
    public function openShopSelector(): void
    {
        $this->multiStoreManager->openShopSelector();
    }

    /**
     * Close shop selector modal
     */
    public function closeShopSelector(): void
    {
        $this->multiStoreManager->closeShopSelector();
    }

    /**
     * Switch between shops or default data
     */
    public function switchToShop(?int $shopId = null): void
    {
        $this->multiStoreManager->switchToShop($shopId);
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE OPERATIONS - Delegated to Service
    |--------------------------------------------------------------------------
    */

    /**
     * Save product (create or update)
     */
    public function save(): void
    {
        $this->formSaver->save();
    }

    /**
     * Update product without closing form
     */
    public function updateOnly(): void
    {
        $this->formSaver->updateOnly();
    }

    /**
     * Save and close form
     */
    public function saveAndClose()
    {
        return $this->formSaver->saveAndClose();
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
        $pageTitle = $this->isEditMode
            ? "Edytuj produkt: {$this->name}"
            : 'Dodaj nowy produkt';

        $breadcrumbs = [
            ['name' => 'Admin', 'url' => route('admin.dashboard')],
            ['name' => 'Produkty', 'url' => route('admin.products.index')],
            ['name' => $this->isEditMode ? 'Edytuj' : 'Dodaj', 'url' => null],
        ];

        return view('livewire.products.management.product-form', [
            'categories' => $this->categories,
            'productTypes' => $this->productTypes,
            'calculatedVolume' => $this->calculatedVolume,
            'shortDescriptionWarning' => $this->shortDescriptionWarning,
            'longDescriptionWarning' => $this->longDescriptionWarning,
            'hasChanges' => $this->hasChanges,
            'availableShops' => $this->availableShops,
            'availableAttributes' => $this->availableAttributes,
        ])->layout('layouts.admin', [
            'title' => $pageTitle,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}