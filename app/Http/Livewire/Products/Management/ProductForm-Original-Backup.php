<?php

namespace App\Http\Livewire\Products\Management;

use Livewire\Component;
use App\Models\Product;
use App\Models\Category;
use App\Models\PrestaShopShop;
use App\Models\ProductShopData;
use App\Models\ProductAttribute;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * ProductForm Component - CRUD form dla produktów z tab system
 *
 * Features:
 * - 3-tab interface (Basic Information, Description, Physical Properties)
 * - Real-time validation z error handling
 * - SKU uniqueness validation
 * - Auto-slug generation z live preview
 * - Character counters dla descriptions
 * - Volume calculation dla dimensions
 * - Category selection z hierarchical dropdown
 * - Form state persistence across tabs
 *
 * Performance:
 * - Optimized queries z proper eager loading
 * - Client-side validation dla UX
 * - Efficient form state management
 * - Progressive enhancement z Alpine.js
 *
 * Business Logic:
 * - Enterprise validation rules
 * - Permission-based field access
 * - Audit trail integration
 * - Integration readiness (PrestaShop, ERP)
 *
 * @package App\Http\Livewire\Products\Management
 * @version 1.0
 * @since ETAP_05 FAZA 2 - ProductForm Implementation
 */
class ProductForm extends Component
{
    use AuthorizesRequests;

    /*
    |--------------------------------------------------------------------------
    | COMPONENT PROPERTIES - Form State Management
    |--------------------------------------------------------------------------
    */

    // Product instance (null dla create mode)
    public ?Product $product = null;
    public bool $isEditMode = false;

    // Active tab management
    public string $activeTab = 'basic';

    // === BASIC INFORMATION TAB ===
    public string $sku = '';
    public string $name = '';
    public string $slug = '';
    public ?int $product_type_id = 1; // Default to ID 1 (likely "inne/other")
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

    // === CATEGORIES (Basic Tab) ===
    public array $selectedCategories = [];
    public ?int $primaryCategoryId = null;

    // Shop-specific categories (FAZA 1.5.3.2.4)
    public array $shopCategories = []; // Format: [shopId => ['selected' => [ids], 'primary' => id]]

    // Shop-specific attributes (FAZA 1.5.3.2.5)
    public array $shopAttributes = []; // Format: [shopId => [attributeCode => value]]

    // === UI STATE ===
    public bool $isSaving = false;
    public array $validationErrors = [];
    public string $successMessage = '';
    public bool $showSlugField = false;

    // === CHARACTER COUNTERS ===
    public int $shortDescriptionCount = 0;
    public int $longDescriptionCount = 0;

    // === MULTI-STORE SYNCHRONIZATION (FAZA 1.5) ===
    public array $exportedShops = [];   // Shops where product is exported

    public ?int $activeShopId = null;   // null = default data, int = specific shop
    public array $shopData = [];        // Per-shop data storage (only custom values)
    public array $defaultData = [];     // Original product data (never overwritten)
    public bool $showShopSelector = false; // Show add shop modal
    public array $selectedShopsToAdd = []; // Selected shops for bulk add

    /*
    |--------------------------------------------------------------------------
    | VALIDATION RULES - Live Validation
    |--------------------------------------------------------------------------
    */

    protected function rules(): array
    {
        $productId = $this->product?->id;

        return [
            // Basic Information
            'sku' => [
                'required',
                'string',
                'max:100',
                $this->isEditMode ? "unique:products,sku,{$productId}" : 'unique:products,sku',
                'regex:/^[A-Z0-9\-_]+$/',
            ],
            'name' => 'required|string|max:500|min:3',
            'slug' => [
                'nullable',
                'string',
                'max:500',
                $this->isEditMode ? "unique:products,slug,{$productId}" : 'unique:products,slug',
                'regex:/^[a-z0-9\-]+$/',
            ],
            'product_type_id' => 'required|exists:product_types,id',
            'manufacturer' => 'nullable|string|max:200',
            'supplier_code' => 'nullable|string|max:100',
            'ean' => 'nullable|string|max:20|regex:/^[0-9]+$/',
            'is_active' => 'boolean',
            'is_variant_master' => 'boolean',
            'sort_order' => 'integer|min:0',

            // Descriptions
            'short_description' => 'nullable|string|max:800',
            'long_description' => 'nullable|string|max:21844',
            'meta_title' => 'nullable|string|max:300',
            'meta_description' => 'nullable|string|max:300',

            // Physical Properties
            'weight' => 'nullable|numeric|min:0|max:99999.999',
            'height' => 'nullable|numeric|min:0|max:999999.99',
            'width' => 'nullable|numeric|min:0|max:999999.99',
            'length' => 'nullable|numeric|min:0|max:999999.99',
            'tax_rate' => 'required|numeric|min:0|max:100',

            // Categories
            'selectedCategories.*' => 'exists:categories,id',
            'primaryCategoryId' => 'nullable|exists:categories,id',
        ];
    }

    protected function messages(): array
    {
        return [
            'sku.required' => 'SKU produktu jest wymagane.',
            'sku.unique' => 'Produkt z tym SKU już istnieje.',
            'sku.regex' => 'SKU może zawierać tylko wielkie litery, cyfry, myślniki i podkreślenia.',
            'name.required' => 'Nazwa produktu jest wymagana.',
            'name.min' => 'Nazwa produktu musi mieć minimum 3 znaki.',
            'product_type_id.required' => 'Typ produktu jest wymagany.', 'product_type_id.exists' => 'Wybrany typ produktu nie istnieje.',
            'short_description.max' => 'Krótki opis nie może przekraczać 800 znaków.',
            'long_description.max' => 'Długi opis nie może przekraczać 21844 znaków.',
            'tax_rate.required' => 'Stawka VAT jest wymagana.',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | COMPONENT LIFECYCLE
    |--------------------------------------------------------------------------
    */

    /**
     * Initialize component dla create/edit mode
     */
    public function mount(?Product $product = null): void
    {
        if ($product && $product->exists) {
            $this->product = $product;
            $this->isEditMode = true;
            $this->loadProductData();
        } else {
            $this->isEditMode = false;
            $this->setDefaults();
        }

        // Note: availableShops and availableAttributes are now computed properties

        // Load existing shop-specific data if editing
        if ($this->isEditMode) {
            $this->loadShopData();
        }

        $this->updateCharacterCounts();
    }

    /**
     * Set default values for new product creation
     */
    private function setDefaults(): void
    {
        $this->sku = '';
        $this->name = '';
        $this->slug = '';
        $this->product_type_id = 1; // Default to "inne/other"
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

        // Store default data for new products
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

        // Store default data (CRITICAL: These are never overwritten by shop data)
        $this->storeDefaultData();

        // Load categories
        $this->loadCategories();
    }

    /**
     * Store current form data as default data (never overwritten)
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

        Log::info('Default data stored', [
            'product_id' => $this->product?->id,
            'default_data' => $this->defaultData,
        ]);
    }

    /**
     * Load product categories
     */
    private function loadCategories(): void
    {
        if (!$this->product) return;

        $categories = $this->product->categories()->get();
        $this->selectedCategories = $categories->pluck('id')->toArray();

        $primaryCategory = $categories->where('pivot.is_primary', true)->first();
        $this->primaryCategoryId = $primaryCategory?->id;
    }

    /*
    |--------------------------------------------------------------------------
    | REAL-TIME VALIDATION & UX FEATURES
    |--------------------------------------------------------------------------
    */

    /**
     * Auto-generate slug from name
     */
    public function updatedName(): void
    {
        if (!$this->showSlugField && !$this->isEditMode) {
            $this->slug = \Illuminate\Support\Str::slug($this->name);
        }
        $this->resetErrorBag('name');
    }

    /**
     * Normalize SKU input
     */
    public function updatedSku(): void
    {
        $this->sku = strtoupper(trim($this->sku));
        $this->resetErrorBag('sku');
    }

    /**
     * Update character counts dla descriptions
     */
    public function updatedShortDescription(): void
    {
        $this->updateCharacterCounts();
        $this->resetErrorBag('short_description');
    }

    public function updatedLongDescription(): void
    {
        $this->updateCharacterCounts();
        $this->resetErrorBag('long_description');
    }

    /**
     * Update character counters
     */
    private function updateCharacterCounts(): void
    {
        $this->shortDescriptionCount = mb_strlen($this->short_description);
        $this->longDescriptionCount = mb_strlen($this->long_description);
    }

    /*
    |--------------------------------------------------------------------------
    | MULTI-STORE METHODS (FAZA 1.5) - Shop Management
    |--------------------------------------------------------------------------
    */


    /**
     * Load existing shop-specific data for edit mode
     * CRITICAL: Only stores custom values, does NOT overwrite default data
     */
    private function loadShopData(): void
    {
        if (!$this->product) {
            return;
        }

        // Load all shop data for this product
        $productShopData = ProductShopData::where('product_id', $this->product->id)
            ->with('shop')
            ->get();

        foreach ($productShopData as $shopData) {
            // CRITICAL: Only store custom values (non-null values that differ from defaults)
            $customData = [];

            // Only store name if it's different from default
            if (!empty($shopData->name) && $shopData->name !== $this->defaultData['name']) {
                $customData['name'] = $shopData->name;
            }

            // Only store slug if it's different from default
            if (!empty($shopData->slug) && $shopData->slug !== $this->defaultData['slug']) {
                $customData['slug'] = $shopData->slug;
            }

            // Only store short_description if it's different from default
            if (!empty($shopData->short_description) && $shopData->short_description !== $this->defaultData['short_description']) {
                $customData['short_description'] = $shopData->short_description;
            }

            // Only store long_description if it's different from default
            if (!empty($shopData->long_description) && $shopData->long_description !== $this->defaultData['long_description']) {
                $customData['long_description'] = $shopData->long_description;
            }

            // Only store meta_title if it's different from default
            if (!empty($shopData->meta_title) && $shopData->meta_title !== $this->defaultData['meta_title']) {
                $customData['meta_title'] = $shopData->meta_title;
            }

            // Only store meta_description if it's different from default
            if (!empty($shopData->meta_description) && $shopData->meta_description !== $this->defaultData['meta_description']) {
                $customData['meta_description'] = $shopData->meta_description;
            }

            // Store metadata (always preserved)
            $customData['id'] = $shopData->id;
            $customData['category_mappings'] = $shopData->category_mappings ?? [];
            $customData['attribute_mappings'] = $shopData->attribute_mappings ?? [];
            $customData['image_settings'] = $shopData->image_settings ?? [];
            $customData['is_published'] = $shopData->is_published ?? false;
            $customData['sync_status'] = $shopData->sync_status;
            $customData['last_sync_at'] = $shopData->last_sync_at?->format('Y-m-d H:i:s');
            $customData['conflict_data'] = $shopData->conflict_data ?? [];

            $this->shopData[$shopData->shop_id] = $customData;

            // Add to exported shops list
            $this->exportedShops[] = $shopData->shop_id;

            // Load shop-specific categories (FAZA 1.5.3.2.4)
            if (!empty($shopData->category_mappings)) {
                $categoryIds = array_keys($shopData->category_mappings);
                $primaryCategoryId = null;

                // Find primary category
                foreach ($shopData->category_mappings as $categoryId => $categoryData) {
                    if (isset($categoryData['is_primary']) && $categoryData['is_primary']) {
                        $primaryCategoryId = $categoryId;
                        break;
                    }
                }

                $this->shopCategories[$shopData->shop_id] = [
                    'selected' => $categoryIds,
                    'primary' => $primaryCategoryId,
                ];
            }
        }

        Log::info('Shop-specific data loaded (custom values only)', [
            'product_id' => $this->product->id,
            'shops_count' => count($this->shopData),
            'exported_shops' => $this->exportedShops,
        ]);
    }

    /**
     * Add product to specific shop(s)
     */
    public function addToShops(): void
    {
        if (!$this->product) {
            $this->dispatch('error', message: 'Najpierw zapisz produkt');
            return;
        }

        if (empty($this->selectedShopsToAdd)) {
            $this->dispatch('error', message: 'Wybierz co najmniej jeden sklep');
            return;
        }

        $addedCount = 0;
        foreach ($this->selectedShopsToAdd as $shopId) {
            if (!in_array($shopId, $this->exportedShops)) {
                // Create shop data entry - empty record for inheritance
                $productShopData = ProductShopData::create([
                    'product_id' => $this->product->id,
                    'shop_id' => $shopId,
                    'name' => null, // Will inherit from default
                    'slug' => null, // Will inherit from default
                    'short_description' => null, // Will inherit from default
                    'long_description' => null, // Will inherit from default
                    'meta_title' => null, // Will inherit from default
                    'meta_description' => null, // Will inherit from default
                    'sync_status' => 'pending',
                    'is_published' => false,
                ]);

                $this->exportedShops[] = $shopId;
                // Store minimal data - no custom values initially (all inherited)
                $this->shopData[$shopId] = [
                    'id' => $productShopData->id,
                    // No custom data - all fields will inherit from defaults
                    'category_mappings' => [],
                    'attribute_mappings' => [],
                    'image_settings' => [],
                    'is_published' => false,
                    'sync_status' => 'pending',
                    'last_sync_at' => null,
                    'conflict_data' => [],
                ];

                Log::info('Product added to shop (inheriting from defaults)', [
                    'product_id' => $this->product->id,
                    'shop_id' => $shopId,
                    'user_id' => Auth::id(),
                    'inheritance_mode' => 'all_fields_inherit',
                ]);

                $addedCount++;
            }
        }

        // Close modal and reset selection
        $this->showShopSelector = false;
        $this->selectedShopsToAdd = [];

        if ($addedCount > 0) {
            $message = $addedCount === 1
                ? 'Produkt dodany do sklepu'
                : "Produkt dodany do {$addedCount} sklepów";
            $this->dispatch('success', message: $message);
        } else {
            $this->dispatch('info', message: 'Produkt był już dodany do wybranych sklepów');
        }
    }

    /**
     * Remove product from specific shop
     */
    public function removeFromShop(int $shopId): void
    {
        if (in_array($shopId, $this->exportedShops)) {
            // Delete shop data
            if (isset($this->shopData[$shopId]['id'])) {
                ProductShopData::find($this->shopData[$shopId]['id'])?->delete();
            }

            // Remove from arrays
            $this->exportedShops = array_filter($this->exportedShops, fn($id) => $id !== $shopId);
            unset($this->shopData[$shopId]);

            // Switch back to default if current shop was removed
            if ($this->activeShopId === $shopId) {
                $this->activeShopId = null;
            }

            Log::info('Product removed from shop', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'user_id' => Auth::id(),
            ]);

            $this->dispatch('success', message: 'Produkt usunięty ze sklepu');
        }
    }

    /**
     * Show shop selector modal
     */
    public function openShopSelector(): void
    {
        $this->showShopSelector = true;
    }

    /**
     * Close shop selector modal
     */
    public function closeShopSelector(): void
    {
        $this->showShopSelector = false;
    }

    /**
     * Switch to specific shop tab or default data
     * CRITICAL: Uses inheritance pattern - shows default values when shop has no custom data
     */
    public function switchToShop(?int $shopId = null): void
    {
        // Save current data before switching - CRITICAL FIX
        if ($this->activeShopId === null && $shopId !== null) {
            // Switching FROM default TO shop - save default data
            $this->saveCurrentDefaultData();
        } elseif ($this->activeShopId !== null && $this->activeShopId !== $shopId) {
            // Switching FROM shop TO another tab - save shop data
            $this->saveCurrentShopData();
        }

        $this->activeShopId = $shopId;

        if ($shopId === null) {
            // Switch to default data
            $this->loadDefaultDataToForm();
        } else {
            // Switch to shop-specific data with inheritance
            $this->loadShopDataToForm($shopId);
        }

        $this->updateCharacterCounts();

        Log::info('Switched to shop tab', [
            'product_id' => $this->product?->id,
            'shop_id' => $shopId,
            'active_shop_id' => $this->activeShopId,
            'save_action' => $this->activeShopId === null ? 'saved_default_data' : 'saved_shop_data',
        ]);
    }

    /**
     * Load default data to form fields (when activeShopId is null)
     */
    private function loadDefaultDataToForm(): void
    {
        if (!empty($this->defaultData)) {
            $this->name = $this->defaultData['name'];
            $this->slug = $this->defaultData['slug'];
            $this->short_description = $this->defaultData['short_description'];
            $this->long_description = $this->defaultData['long_description'];
            $this->meta_title = $this->defaultData['meta_title'];
            $this->meta_description = $this->defaultData['meta_description'];
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
        $this->name = $this->getShopValue($shopId, 'name');
        $this->slug = $this->getShopValue($shopId, 'slug');
        $this->short_description = $this->getShopValue($shopId, 'short_description');
        $this->long_description = $this->getShopValue($shopId, 'long_description');
        $this->meta_title = $this->getShopValue($shopId, 'meta_title');
        $this->meta_description = $this->getShopValue($shopId, 'meta_description');
    }

    /**
     * Get value for shop with inheritance from default data
     * Returns shop-specific value if exists, otherwise returns default value
     */
    private function getShopValue(int $shopId, string $field): string
    {
        // If shop has custom value, return it
        if (isset($this->shopData[$shopId][$field])) {
            return $this->shopData[$shopId][$field];
        }

        // Otherwise return default value
        return $this->defaultData[$field] ?? '';
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

        // Merge custom data with existing metadata
        $this->shopData[$this->activeShopId] = array_merge(
            $this->shopData[$this->activeShopId] ?? [],
            $customData
        );

        Log::info('Shop data saved (custom values only)', [
            'product_id' => $this->product?->id,
            'shop_id' => $this->activeShopId,
            'custom_fields' => array_keys($customData),
        ]);
    }

    /**
     * Save current form data to default data storage
     * CRITICAL: Updates defaultData when user modifies "Dane domyślne"
     */
    private function saveCurrentDefaultData(): void
    {
        // Update defaultData with current form values
        $this->defaultData = [
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'long_description' => $this->long_description,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
        ];

        Log::info('Default data updated', [
            'product_id' => $this->product?->id,
            'updated_fields' => array_keys($this->defaultData),
            'name' => $this->name,
        ]);
    }

    /**
     * Toggle slug field visibility
     */
    public function toggleSlugField(): void
    {
        $this->showSlugField = !$this->showSlugField;
        if (!$this->showSlugField && !$this->isEditMode) {
            $this->slug = \Illuminate\Support\Str::slug($this->name);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | TAB MANAGEMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Switch active tab
     */
    public function switchTab(string $tab): void
    {
        $validTabs = ['basic', 'description', 'physical'];

        if (in_array($tab, $validTabs)) {
            $this->activeTab = $tab;
            $this->dispatch('tab-switched', tab: $tab);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CATEGORY MANAGEMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Toggle category selection (supports shop-specific categories)
     */
    public function toggleCategory(int $categoryId): void
    {
        if ($this->activeShopId !== null) {
            // Shop-specific category management
            $this->toggleShopCategory($categoryId);
        } else {
            // Default category management
            if (in_array($categoryId, $this->selectedCategories)) {
                $this->selectedCategories = array_diff($this->selectedCategories, [$categoryId]);

                // Remove primary if deselected
                if ($this->primaryCategoryId === $categoryId) {
                    $this->primaryCategoryId = null;
                }
            } else {
                $this->selectedCategories[] = $categoryId;

                // Set as primary if first category
                if (!$this->primaryCategoryId) {
                    $this->primaryCategoryId = $categoryId;
                }
            }
        }
    }

    /**
     * Set primary category (supports shop-specific categories)
     */
    public function setPrimaryCategory(int $categoryId): void
    {
        if ($this->activeShopId !== null) {
            // Shop-specific primary category
            $this->setShopPrimaryCategory($categoryId);
        } else {
            // Default primary category
            if (in_array($categoryId, $this->selectedCategories)) {
                $this->primaryCategoryId = $categoryId;
            }
        }
    }

    /**
     * Toggle shop-specific category
     */
    private function toggleShopCategory(int $categoryId): void
    {
        $shopId = $this->activeShopId;

        if (!isset($this->shopCategories[$shopId])) {
            $this->shopCategories[$shopId] = ['selected' => [], 'primary' => null];
        }

        $selected = $this->shopCategories[$shopId]['selected'];

        if (in_array($categoryId, $selected)) {
            // Remove category
            $this->shopCategories[$shopId]['selected'] = array_diff($selected, [$categoryId]);

            // Remove primary if deselected
            if ($this->shopCategories[$shopId]['primary'] === $categoryId) {
                $this->shopCategories[$shopId]['primary'] = null;
            }
        } else {
            // Add category
            $this->shopCategories[$shopId]['selected'][] = $categoryId;

            // Set as primary if first category
            if (!$this->shopCategories[$shopId]['primary']) {
                $this->shopCategories[$shopId]['primary'] = $categoryId;
            }
        }
    }

    /**
     * Set shop-specific primary category
     */
    private function setShopPrimaryCategory(int $categoryId): void
    {
        $shopId = $this->activeShopId;

        if (!isset($this->shopCategories[$shopId])) {
            return;
        }

        if (in_array($categoryId, $this->shopCategories[$shopId]['selected'])) {
            $this->shopCategories[$shopId]['primary'] = $categoryId;
        }
    }

    /**
     * Get current selected categories (default or shop-specific)
     */
    public function getCurrentSelectedCategoriesProperty(): array
    {
        if ($this->activeShopId !== null && isset($this->shopCategories[$this->activeShopId])) {
            return $this->shopCategories[$this->activeShopId]['selected'];
        }
        return $this->selectedCategories;
    }

    /**
     * Get current primary category (default or shop-specific)
     */
    public function getCurrentPrimaryCategoryIdProperty(): ?int
    {
        if ($this->activeShopId !== null && isset($this->shopCategories[$this->activeShopId])) {
            return $this->shopCategories[$this->activeShopId]['primary'];
        }
        return $this->primaryCategoryId;
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Get available categories dla selection
     */
    public function getCategoriesProperty()
    {
        return Category::active()
                      ->treeOrder()
                      ->get(['id', 'name', 'level', 'path']);
    }

    /**
     * Get available product types dla selection
     */
    public function getProductTypesProperty()
    {
        return \App\Models\ProductType::active()
                      ->ordered()
                      ->get(['id', 'name', 'slug', 'description']);
    }

    /**
     * Get calculated volume
     */
    public function getCalculatedVolumeProperty(): ?float
    {
        if ($this->height && $this->width && $this->length) {
            return round(($this->height * $this->width * $this->length) / 1000000, 6); // m³
        }
        return null;
    }

    /**
     * Get character count warnings
     */
    public function getShortDescriptionWarningProperty(): bool
    {
        return $this->shortDescriptionCount > 700; // Warning at 700/800
    }

    public function getLongDescriptionWarningProperty(): bool
    {
        return $this->longDescriptionCount > 20000; // Warning at 20000/21844
    }

    /**
     * Check if form has unsaved changes
     */
    public function getHasChangesProperty(): bool
    {
        if (!$this->isEditMode) {
            return !empty($this->sku) || !empty($this->name);
        }

        // Compare with original values
        return $this->sku !== ($this->product->sku ?? '') ||
               $this->name !== ($this->product->name ?? '') ||
               $this->short_description !== ($this->product->short_description ?? '');
    }

    /*
    |--------------------------------------------------------------------------
    | FORM ACTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Save product (create or update)
     */
    public function save(): void
    {
        $this->isSaving = true;
        $this->validationErrors = [];
        $this->successMessage = '';

        try {
            // Validate form data
            $this->validate();

            // Additional business validation
            $this->validateBusinessRules();

            // CRITICAL FIX: Different logic for default vs shop mode
            if ($this->activeShopId === null) {
                // DEFAULT MODE: Save to products table + update defaultData
                $this->saveCurrentDefaultData();

                DB::transaction(function () {
                    if ($this->isEditMode) {
                        $this->updateProduct();
                    } else {
                        $this->createProduct();
                    }

                    // Try to sync categories (safely handled)
                    $this->syncCategories();
                });
            } else {
                // SHOP MODE: Save ONLY to product_shop_data, DON'T touch products table
                $this->saveCurrentShopData(); // Save current form data as shop data

                DB::transaction(function () {
                    // Save shop-specific data (FAZA 1.5) - safely handled
                    try {
                        $this->saveShopSpecificData();
                    } catch (\Exception $e) {
                        Log::warning('Shop-specific data save failed', [
                            'product_id' => $this->product->id,
                            'shop_id' => $this->activeShopId,
                            'error' => $e->getMessage(),
                        ]);
                        throw $e; // Re-throw to show user error
                    }
                });
            }

            $action = $this->isEditMode ? 'zaktualizowany' : 'utworzony';
            $this->successMessage = "Produkt został {$action} pomyślnie.";

            $this->dispatch('product-saved', productId: $this->product->id);

            // Log action dla audit trail
            Log::info('Product saved', [
                'product_id' => $this->product->id,
                'sku' => $this->sku,
                'action' => $this->isEditMode ? 'update' : 'create',
                'user_id' => Auth::id(),
            ]);

        } catch (ValidationException $e) {
            $this->validationErrors = $e->validator->errors()->toArray();
            Log::error('Product validation failed', [
                'validation_errors' => $this->validationErrors,
                'sku' => $this->sku,
                'product_type_id' => $this->product_type_id,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            Log::error('Product save error', [
                'error' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
                'sku' => $this->sku,
                'product_type_id' => $this->product_type_id,
                'is_edit_mode' => $this->isEditMode,
                'form_data' => [
                    'name' => $this->name,
                    'weight' => $this->weight,
                ],
                'user_id' => Auth::id(),
            ]);

            $this->dispatch('error', message: 'Wystąpił błąd podczas zapisywania produktu: ' . $e->getMessage());
        } finally {
            $this->isSaving = false;
        }
    }

    /**
     * Save shop-specific data to ProductShopData table (FAZA 1.5)
     * CRITICAL: Only saves custom values, null for inherited fields
     */
    private function saveShopSpecificData(): void
    {
        if (!$this->product || empty($this->shopData)) {
            return;
        }

        foreach ($this->shopData as $shopId => $data) {
            // Find existing shop data or create new
            $productShopData = ProductShopData::firstOrNew([
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
            ]);

            // Prepare shop-specific category mappings (FAZA 1.5.3.2.4)
            $categoryMappings = [];
            if (isset($this->shopCategories[$shopId])) {
                foreach ($this->shopCategories[$shopId]['selected'] as $categoryId) {
                    $categoryMappings[$categoryId] = [
                        'is_primary' => $categoryId == $this->shopCategories[$shopId]['primary'],
                        'sort_order' => 0,
                    ];
                }
            }

            // Update shop-specific fields - ONLY custom values, null for inherited
            $fieldsToUpdate = [
                'name' => isset($data['name']) ? $data['name'] : null,
                'slug' => isset($data['slug']) ? $data['slug'] : null,
                'short_description' => isset($data['short_description']) ? $data['short_description'] : null,
                'long_description' => isset($data['long_description']) ? $data['long_description'] : null,
                'meta_title' => isset($data['meta_title']) ? $data['meta_title'] : null,
                'meta_description' => isset($data['meta_description']) ? $data['meta_description'] : null,
                'category_mappings' => $categoryMappings,
                'attribute_mappings' => $data['attribute_mappings'] ?? [],
                'image_settings' => $data['image_settings'] ?? [],
                'is_published' => $data['is_published'] ?? false,
                'sync_status' => $data['sync_status'] ?? 'pending',
            ];

            $productShopData->fill($fieldsToUpdate);

            // Generate sync hash for conflict detection (use actual saved values)
            $dataForHash = [
                'name' => $productShopData->name,
                'short_description' => $productShopData->short_description,
                'long_description' => $productShopData->long_description,
            ];
            $productShopData->last_sync_hash = md5(json_encode($dataForHash));

            $productShopData->save();

            // Log what was actually saved
            $customFields = array_filter([
                'name' => isset($data['name']) ? 'custom' : 'inherited',
                'slug' => isset($data['slug']) ? 'custom' : 'inherited',
                'short_description' => isset($data['short_description']) ? 'custom' : 'inherited',
                'long_description' => isset($data['long_description']) ? 'custom' : 'inherited',
                'meta_title' => isset($data['meta_title']) ? 'custom' : 'inherited',
                'meta_description' => isset($data['meta_description']) ? 'custom' : 'inherited',
            ], fn($value) => $value === 'custom');

            Log::info('Shop-specific data saved (custom values only)', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'shop_data_id' => $productShopData->id,
                'custom_fields' => array_keys($customFields),
                'user_id' => Auth::id(),
            ]);
        }
    }

    /**
     * Create new product
     */
    private function createProduct(): void
    {
        $this->product = Product::create([
            'sku' => $this->sku,
            'name' => $this->name,
            'slug' => $this->slug ?: null,
            'product_type_id' => $this->product_type_id,
            'manufacturer' => $this->manufacturer ?: null,
            'supplier_code' => $this->supplier_code ?: null,
            'ean' => $this->ean ?: null,
            'short_description' => $this->short_description ?: null,
            'long_description' => $this->long_description ?: null,
            'weight' => $this->weight,
            'height' => $this->height,
            'width' => $this->width,
            'length' => $this->length,
            'tax_rate' => $this->tax_rate,
            'is_active' => $this->is_active,
            'is_variant_master' => $this->is_variant_master,
            'sort_order' => $this->sort_order,
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
        ]);

        $this->isEditMode = true;

        // Update defaultData after creating product
        $this->storeDefaultData();
    }

    /**
     * Update existing product
     */
    private function updateProduct(): void
    {
        $this->product->update([
            'sku' => $this->sku,
            'name' => $this->name,
            'slug' => $this->slug ?: null,
            'product_type_id' => $this->product_type_id,
            'manufacturer' => $this->manufacturer ?: null,
            'supplier_code' => $this->supplier_code ?: null,
            'ean' => $this->ean ?: null,
            'short_description' => $this->short_description ?: null,
            'long_description' => $this->long_description ?: null,
            'weight' => $this->weight,
            'height' => $this->height,
            'width' => $this->width,
            'length' => $this->length,
            'tax_rate' => $this->tax_rate,
            'is_active' => $this->is_active,
            'is_variant_master' => $this->is_variant_master,
            'sort_order' => $this->sort_order,
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
        ]);

        // Update defaultData after updating product
        $this->storeDefaultData();
    }

    /**
     * Sync product categories - trigger-safe implementation
     */
    private function syncCategories(): void
    {
        // Skip category sync if there's a trigger conflict
        // Let categories be managed separately to avoid MySQL trigger issues
        try {
            if (empty($this->selectedCategories)) {
                // Don't detach if we don't have categories selected
                // This avoids trigger conflicts
                return;
            }

            // First check current categories to avoid unnecessary operations
            $currentCategoryIds = $this->product->categories()->pluck('categories.id')->toArray();
            $newCategoryIds = array_map('intval', $this->selectedCategories);

            // If categories haven't changed, skip sync
            if (array_diff($currentCategoryIds, $newCategoryIds) === [] &&
                array_diff($newCategoryIds, $currentCategoryIds) === []) {
                return;
            }

            // Use a safer approach - detach and reattach separately
            if (!empty($currentCategoryIds)) {
                $this->product->categories()->detach();
            }

            // Add new categories one by one to avoid trigger conflicts
            foreach ($this->selectedCategories as $index => $categoryId) {
                $this->product->categories()->attach($categoryId, [
                    'is_primary' => $categoryId == $this->primaryCategoryId,
                    'sort_order' => $index,
                ]);
            }

        } catch (\Exception $e) {
            // Log but don't fail the entire save operation
            Log::warning('Category sync failed, continuing without category update', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
                'selected_categories' => $this->selectedCategories,
            ]);
        }
    }

    /**
     * Additional business validation
     */
    private function validateBusinessRules(): void
    {
        // Primary category must be selected if categories exist
        if (!empty($this->selectedCategories) && !$this->primaryCategoryId) {
            throw ValidationException::withMessages([
                'primaryCategoryId' => 'Należy wybrać kategorię główną.'
            ]);
        }

        // Variant master name should be descriptive
        if ($this->is_variant_master && strlen($this->name) < 5) {
            throw ValidationException::withMessages([
                'name' => 'Produkty z wariantami powinny mieć opisową nazwę (minimum 5 znaków).'
            ]);
        }
    }

    /**
     * Update product but don't close the form
     */
    public function updateOnly(): void
    {
        $this->isSaving = true;
        $this->validationErrors = [];
        $this->successMessage = '';

        try {
            // Validate form data
            $this->validate();

            // Additional business validation
            $this->validateBusinessRules();

            // CRITICAL FIX: Different logic for default vs shop mode
            if ($this->activeShopId === null) {
                // DEFAULT MODE: Save to products table + update defaultData
                $this->saveCurrentDefaultData();

                DB::transaction(function () {
                    if ($this->isEditMode) {
                        $this->updateProduct();
                    } else {
                        $this->createProduct();
                    }

                    // Try to sync categories (safely handled)
                    $this->syncCategories();
                });
            } else {
                // SHOP MODE: Save ONLY to product_shop_data, DON'T touch products table
                $this->saveCurrentShopData(); // Save current form data as shop data

                DB::transaction(function () {
                    // Save shop-specific data (FAZA 1.5) - safely handled
                    try {
                        $this->saveShopSpecificData();
                    } catch (\Exception $e) {
                        Log::warning('Shop-specific data save failed in updateOnly', [
                            'product_id' => $this->product->id,
                            'shop_id' => $this->activeShopId,
                            'error' => $e->getMessage(),
                        ]);
                        throw $e; // Re-throw to show user error
                    }
                });
            }

            // Special message for update-only action
            $this->successMessage = "✅ Produkt został zaktualizowany pomyślnie. Pozostajesz w trybie edycji.";

            $this->dispatch('product-updated', productId: $this->product->id);

            // Log action dla audit trail
            Log::info('Product updated (stay in form)', [
                'product_id' => $this->product->id,
                'user_id' => Auth::id(),
                'sku' => $this->sku,
            ]);

        } catch (ValidationException $e) {
            $this->validationErrors = $e->errors();
        } catch (\Exception $e) {
            $this->validationErrors = ['general' => 'Wystąpił błąd podczas aktualizacji produktu: ' . $e->getMessage()];

            Log::error('Product update failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'sku' => $this->sku,
            ]);
        } finally {
            $this->isSaving = false;
        }
    }

    /**
     * Save product and close form (redirect to list)
     */
    public function saveAndClose()
    {
        $this->save(); // Reuse existing save logic

        // Only redirect if save was successful (no validation errors)
        if (empty($this->validationErrors)) {
            return redirect('/admin/products');
        }
        // If there were errors, stay on the form
    }

    /**
     * Cancel form and return to list without saving
     */
    public function cancel()
    {
        return redirect('/admin/products');
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES - Optimized for Livewire Snapshot Size
    |--------------------------------------------------------------------------
    */

    /**
     * Get available shops (computed property to avoid serialization)
     */
    public function getAvailableShopsProperty(): array
    {
        return PrestaShopShop::active()
            ->orderBy('name')
            ->get()
            ->map(function ($shop) {
                return [
                    'id' => $shop->id,
                    'name' => $shop->name,
                    'url' => $shop->url,
                    'is_active' => $shop->is_active,
                    'connection_status' => $shop->connection_status,
                ];
            })
            ->toArray();
    }

    /**
     * Get available attributes (computed property to avoid serialization)
     */
    public function getAvailableAttributesProperty(): array
    {
        try {
            return ProductAttribute::active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(function ($attribute) {
                    return [
                        'id' => $attribute->id,
                        'name' => $attribute->name,
                        'code' => $attribute->code,
                        'attribute_type' => $attribute->attribute_type,
                        'is_required' => $attribute->is_required,
                        'help_text' => $attribute->help_text,
                        'options' => $attribute->options_parsed ?? [],
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            // Fallback if attributes table doesn't exist yet
            Log::warning('Could not load attributes', ['error' => $e->getMessage()]);
            return [];
        }
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