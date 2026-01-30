<?php

namespace App\Http\Livewire\Products\Management\Services;

use App\Models\Product;
use App\Models\ProductShopData;
use App\Models\ProductPrice;
use App\Models\ProductStock;
use App\Models\PrestaShopShop;
use App\Services\CategoryMappingsConverter;
use App\Services\CategoryAutoCreateService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * ProductFormSaver Service
 *
 * Handles product saving operations for ProductForm
 * Manages both default product data and shop-specific data
 * Separated from main component per CLAUDE.md guidelines
 *
 * @package App\Http\Livewire\Products\Management\Services
 */
class ProductFormSaver
{
    protected $component;

    public function __construct($component)
    {
        $this->component = $component;
    }

    /*
    |--------------------------------------------------------------------------
    | MAIN SAVE OPERATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Save product (create or update)
     */
    public function save(): void
    {
        $this->component->isSaving = true;
        $this->component->validationErrors = [];
        $this->component->successMessage = '';

        try {
            // Validate form data
            $this->component->validate();

            // Additional business validation
            $this->component->validateBusinessRules();

            // CRITICAL FIX: Different logic for default vs shop mode
            if ($this->component->activeShopId === null) {
                // DEFAULT MODE: Save to products table + update defaultData
                $this->saveCurrentDefaultData();

                DB::transaction(function () {
                    if ($this->component->isEditMode) {
                        $this->updateProduct();
                    } else {
                        $this->createProduct();
                    }

                    // Try to sync categories (safely handled)
                    $this->syncCategories();

                    // PROBLEM #4 (2025-11-07): Save prices and stock
                    Log::debug('BEFORE savePrices', [
                        'prices_array' => $this->component->prices,
                        'prices_count' => count($this->component->prices),
                    ]);
                    $this->savePrices();

                    Log::debug('BEFORE saveStock', [
                        'stock_array' => $this->component->stock,
                        'stock_count' => count($this->component->stock),
                    ]);
                    $this->saveStock();
                });
            } else {
                // SHOP MODE: Save to product_shop_data table only
                $this->saveShopSpecificData();
            }

            // Set success message
            $mode = $this->component->activeShopId === null ? 'default' : 'shop-specific';
            $action = $this->component->isEditMode ? 'updated' : 'created';

            $this->component->successMessage = "Product {$action} successfully ({$mode} data)";

            // Dispatch success event
            $this->component->dispatch('product-saved', productId: $this->component->product?->id);

            Log::info('Product saved successfully', [
                'product_id' => $this->component->product?->id,
                'mode' => $mode,
                'action' => $action,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->component->validationErrors = $e->validator->errors()->toArray();
            Log::warning('Product validation failed', [
                'errors' => $this->component->validationErrors,
            ]);
        } catch (\Exception $e) {
            $this->component->validationErrors = ['general' => [$e->getMessage()]];
            Log::error('Product save failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        } finally {
            $this->component->isSaving = false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PRODUCT CRUD OPERATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Create new product
     */
    private function createProduct(): void
    {
        $this->component->product = Product::create([
            'sku' => $this->component->sku,
            'name' => $this->component->name,
            'slug' => $this->component->slug ?: null,
            'product_type_id' => $this->component->product_type_id,
            'manufacturer' => $this->component->manufacturer ?: null,
            'manufacturer_id' => $this->component->manufacturer_id,
            'supplier_id' => $this->component->supplier_id,
            'importer_id' => $this->component->importer_id,
            'supplier_code' => $this->component->supplier_code ?: null,
            'ean' => $this->component->ean ?: null,
            'short_description' => $this->component->short_description ?: null,
            'long_description' => $this->component->long_description ?: null,
            'meta_title' => $this->component->meta_title ?: null,
            'meta_description' => $this->component->meta_description ?: null,
            'weight' => $this->component->weight,
            'height' => $this->component->height,
            'width' => $this->component->width,
            'length' => $this->component->length,
            'tax_rate' => $this->component->tax_rate,
            'is_active' => $this->component->is_active,
            'is_variant_master' => $this->component->is_variant_master,
            'sort_order' => $this->component->sort_order,
        ]);

        // Switch to edit mode
        $this->component->isEditMode = true;

        Log::info('Product created', [
            'product_id' => $this->component->product->id,
            'sku' => $this->component->sku,
        ]);
    }

    /**
     * Update existing product
     */
    private function updateProduct(): void
    {
        $this->component->product->update([
            'sku' => $this->component->sku,
            'name' => $this->component->name,
            'slug' => $this->component->slug ?: null,
            'product_type_id' => $this->component->product_type_id,
            'manufacturer' => $this->component->manufacturer ?: null,
            'manufacturer_id' => $this->component->manufacturer_id,
            'supplier_id' => $this->component->supplier_id,
            'importer_id' => $this->component->importer_id,
            'supplier_code' => $this->component->supplier_code ?: null,
            'ean' => $this->component->ean ?: null,
            'short_description' => $this->component->short_description ?: null,
            'long_description' => $this->component->long_description ?: null,
            'meta_title' => $this->component->meta_title ?: null,
            'meta_description' => $this->component->meta_description ?: null,
            'weight' => $this->component->weight,
            'height' => $this->component->height,
            'width' => $this->component->width,
            'length' => $this->component->length,
            'tax_rate' => $this->component->tax_rate,
            'is_active' => $this->component->is_active,
            'is_variant_master' => $this->component->is_variant_master,
            'sort_order' => $this->component->sort_order,
        ]);

        Log::info('Product updated', [
            'product_id' => $this->component->product->id,
            'sku' => $this->component->sku,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOP-SPECIFIC DATA OPERATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Save shop-specific data to database
     */
    private function saveShopSpecificData(): void
    {
        if (!$this->component->product || !$this->component->product->exists) {
            throw new \Exception('Product must be saved first before saving shop-specific data');
        }

        Log::info('[SHOP SAVE] saveShopSpecificData called', [
            'product_id' => $this->component->product?->id,
            'shop_id' => $this->component->activeShopId,
            'shopCategories_input' => $this->component->shopCategories[$this->component->activeShopId] ?? null,
        ]);

        $shopId = $this->component->activeShopId;

        // Prepare shop data
        $shopData = [
            'product_id' => $this->component->product->id,
            'shop_id' => $shopId,
            'name' => $this->component->name ?: null,
            'slug' => $this->component->slug ?: null,
            'short_description' => $this->component->short_description ?: null,
            'long_description' => $this->component->long_description ?: null,
            'meta_title' => $this->component->meta_title ?: null,
            'meta_description' => $this->component->meta_description ?: null,
            'sync_status' => 'pending',
            'is_published' => false,
        ];

        // FIX #12 + ETAP_07b: Build Option A structure from UI state
        // UI contains PrestaShop IDs (from getShopCategories()), need to convert to PPM IDs
        if (isset($this->component->shopCategories[$shopId])) {
            $shop = PrestaShopShop::find($shopId);

            if ($shop) {
                $converter = app(CategoryMappingsConverter::class);
                $selectedCategories = $this->component->shopCategories[$shopId]['selected'] ?? [];

                Log::debug('[ETAP_07b] Saving shop categories', [
                    'product_id' => $this->component->product->id,
                    'shop_id' => $shopId,
                    'selected_presta_ids' => $selectedCategories,
                ]);

                // FIX 2025-11-20: REMOVED forced roots injection
                // User feedback: Categories 1,2 should NOT be forced
                // PrestaShop allows any categories, not just roots
                // This was causing user-selected categories to be overwritten

                // Convert PrestaShop IDs → PPM IDs + mappings (Option A canonical format)
                try {
                    $shopData['category_mappings'] = $converter->fromPrestaShopFormat(
                        $selectedCategories,
                        $shop
                    );
                } catch (\Throwable $e) {
                    Log::error('[ETAP_07b] Category mapping conversion failed', [
                        'product_id' => $this->component->product->id,
                        'shop_id' => $shopId,
                        'selected_presta_ids' => $selectedCategories,
                        'error' => $e->getMessage(),
                    ]);

                    // Allow save to continue without mappings to avoid full failure
                    $shopData['category_mappings'] = null;
                }

                Log::debug('[ETAP_07b] ProductFormSaver: Converted PrestaShop IDs to Option A', [
                    'shop_id' => $shopId,
                    'prestashop_ids' => $selectedCategories,
                    'canonical_format' => $shopData['category_mappings'],
                ]);
            }
        }

        // Add attribute mappings if exist
        if (isset($this->component->shopAttributes[$shopId])) {
            $shopData['attribute_mappings'] = $this->component->shopAttributes[$shopId];
        }

        // Create or update shop data
        ProductShopData::updateOrCreate(
            [
                'product_id' => $this->component->product->id,
                'shop_id' => $shopId,
            ],
            $shopData
        );

        Log::debug('[ETAP_07b] Shop data saved', [
            'product_id' => $this->component->product->id,
            'shop_id' => $shopId,
            'category_mappings' => $shopData['category_mappings'] ?? null,
        ]);

        // Sync shop-specific categories to pivot table (if changed)
        if (isset($this->component->shopCategories[$shopId])) {
            $this->syncShopCategories($shopId);
        }

        // Update local shop data
        $this->component->shopData[$shopId] = [
            'name' => $this->component->name,
            'slug' => $this->component->slug,
            'short_description' => $this->component->short_description,
            'long_description' => $this->component->long_description,
            'meta_title' => $this->component->meta_title,
            'meta_description' => $this->component->meta_description,
            'sync_status' => 'pending',
            'is_published' => false,
            'last_sync_at' => null,
        ];

        Log::info('Shop-specific data saved', [
            'product_id' => $this->component->product->id,
            'shop_id' => $shopId,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DATA MANAGEMENT HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Save current default data
     */
    private function saveCurrentDefaultData(): void
    {
        $this->component->defaultData = [
            'name' => $this->component->name,
            'slug' => $this->component->slug,
            'short_description' => $this->component->short_description,
            'long_description' => $this->component->long_description,
            'meta_title' => $this->component->meta_title,
            'meta_description' => $this->component->meta_description,
        ];

        Log::info('Default data stored', [
            'product_id' => $this->component->product?->id,
            'default_data' => $this->component->defaultData,
        ]);
    }

    /**
     * Sync categories with database (DEFAULT MODE - shop_id = NULL)
     */
    private function syncCategories(): void
    {
        if (!$this->component->product || !$this->component->product->exists) {
            return;
        }

        try {
            // Use the category manager if available
            if (property_exists($this->component, 'categoryManager') && $this->component->categoryManager) {
                $this->component->categoryManager->syncCategories();
            } else {
                // Fallback direct sync
                $categoryData = [];
                foreach ($this->component->selectedCategories as $categoryId) {
                    $categoryData[$categoryId] = [
                        'is_primary' => $categoryId === $this->component->primaryCategoryId,
                    ];
                }
                $this->component->product->categories()->sync($categoryData);
            }
        } catch (\Exception $e) {
            Log::warning('Category sync failed', [
                'product_id' => $this->component->product->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - category sync failure shouldn't stop product save
        }
    }

    /**
     * Sync shop-specific categories to pivot table and update cache
     *
     * ETAP_07b FAZA 3: Enhanced with auto-create missing categories
     *
     * CRITICAL: Handles shop-specific category sync (shop_id != NULL)
     * Updates both pivot table (PRIMARY source) and category_mappings cache (SECONDARY source)
     *
     * NEW LOGIC (FAZA 3):
     * 1. Detect missing categories (PrestaShop IDs without PPM mappings)
     * 2. If missing → dispatch CategoryCreationJob (wyprzedzający) + skip direct attach
     * 3. If no missing → translate PrestaShop IDs to PPM IDs + proceed with attach
     *
     * This prevents foreign key constraint violation when user selects PrestaShop
     * categories that don't exist in PPM's categories table.
     *
     * @param int $shopId Shop ID
     * @return void
     */
    private function syncShopCategories(int $shopId): void
    {
        if (!$this->component->product || !$this->component->product->exists) {
            return;
        }

        try {
            $selectedCategories = $this->component->shopCategories[$shopId]['selected'] ?? [];
            $primaryCategory = $this->component->shopCategories[$shopId]['primary'] ?? null;

            // Fallback: if UI state is empty, try to load from persisted category_mappings (Option A)
            if (empty($selectedCategories)) {
                $psd = ProductShopData::where('product_id', $this->component->product->id)
                    ->where('shop_id', $shopId)
                    ->first();

                if ($psd && $psd->hasCategoryMappings()) {
                    $converter = app(CategoryMappingsConverter::class);
                    $selectedCategories = $converter->toPrestaShopIdsList($psd->category_mappings);
                    $primaryCategory = $converter->getPrimaryPrestaShopId($psd->category_mappings);

                    Log::debug('[CATEGORY SYNC] Fallback to saved category_mappings for UI state', [
                        'product_id' => $this->component->product->id,
                        'shop_id' => $shopId,
                        'loaded_presta_ids' => $selectedCategories,
                        'primary_presta' => $primaryCategory,
                    ]);
                }
            }

            // Normalizuj do ID PrestaShop (jeśli UI podało PPM, spróbuj znaleźć mapowanie)
            $shop = PrestaShopShop::find($shopId);
            $normalizedPresta = [];
            foreach ($selectedCategories as $id) {
                // Jeśli już istnieje mapping z takim prestashop_id w tabeli – traktuj jako Presta
                $mappingExists = \DB::table('shop_mappings')
                    ->where('shop_id', $shopId)
                    ->where('mapping_type', 'category')
                    ->where('prestashop_id', $id)
                    ->exists();

                if ($mappingExists) {
                    $normalizedPresta[] = (int) $id;
                    continue;
                }

                // Spróbuj interpretować jako PPM i zmapować do Presta
                if ($shop) {
                    $mapped = app(\App\Services\PrestaShop\CategoryMapper::class)->mapToPrestaShop((int) $id, $shop);
                    if ($mapped !== null) {
                        $normalizedPresta[] = (int) $mapped;
                        continue;
                    }
                }

                // Brak mapowania – zostaw oryginał (może to jednak Presta)
                $normalizedPresta[] = (int) $id;
            }

            $selectedCategories = array_values(array_unique($normalizedPresta));

            // FIX 2025-11-20: REMOVED forced roots injection
            // User feedback: "nie miałem na myśli że będzie to zastępować wszystkie kategorie"
            // Categories should match exactly what user selected, no auto-injection

            Log::debug('[CATEGORY SYNC] Normalized Presta IDs before detection', [
                'product_id' => $this->component->product->id,
                'shop_id' => $shopId,
                'prestashop_ids' => $selectedCategories,
                'primary_presta' => $primaryCategory,
            ]);

            // FAZA 3: Detect missing categories (PrestaShop IDs without mappings)
            $categoryAutoCreateService = app(CategoryAutoCreateService::class);
            $detection = $categoryAutoCreateService->detectMissingCategories($selectedCategories, $shopId);

            // If missing categories detected → dispatch CategoryCreationJob (wyprzedzający)
            if (!empty($detection['missing'])) {
                Log::info('[CATEGORY SYNC] Missing categories detected - dispatching CategoryCreationJob', [
                    'product_id' => $this->component->product->id,
                    'shop_id' => $shopId,
                    'missing_count' => count($detection['missing']),
                    'missing_ids' => $detection['missing'],
                    'existing_count' => count($detection['existing']),
                ]);

                // Dispatch CategoryCreationJob (wyprzedzający)
                // Job will create categories + mappings + chain to ProductSyncJob
                $categoryAutoCreateService->createMissingCategoriesJob(
                    $selectedCategories,
                    $shopId,
                    $this->component->product->id,
                    auth()->id() ?? 8 // Fallback to admin for testing
                );

                Log::info('[CATEGORY SYNC] CategoryCreationJob dispatched - skipping direct attach', [
                    'product_id' => $this->component->product->id,
                    'shop_id' => $shopId,
                    'info' => 'Product sync will be handled by CategoryCreationJob chain',
                ]);

                // CRITICAL: Don't proceed with attach - CategoryCreationJob will handle it
                return;
            }

            // FAZA 3: Translate PrestaShop IDs to PPM IDs using mappings
            $ppmCategoryIds = $categoryAutoCreateService->translateToPpmIds($selectedCategories, $shopId);

            // Find primary category PPM ID
            $primaryPpmId = null;
            if ($primaryCategory !== null) {
                $primaryIndex = array_search($primaryCategory, $selectedCategories);
                if ($primaryIndex !== false) {
                    $primaryPpmId = $ppmCategoryIds[$primaryIndex];
                }
            }

            Log::debug('[CATEGORY SYNC] PrestaShop IDs translated to PPM IDs', [
                'product_id' => $this->component->product->id,
                'shop_id' => $shopId,
                'prestashop_ids' => $selectedCategories,
                'ppm_ids' => $ppmCategoryIds,
                'primary_prestashop' => $primaryCategory,
                'primary_ppm' => $primaryPpmId,
            ]);

            // Step 1: Sync to pivot table (product_categories WHERE shop_id = X)
            // IMPORTANT: Use PPM category IDs (not PrestaShop IDs)
            $categoryData = [];
            foreach ($ppmCategoryIds as $ppmCategoryId) {
                $categoryData[$ppmCategoryId] = [
                    'shop_id' => $shopId,
                    'is_primary' => $ppmCategoryId === $primaryPpmId,
                    'sort_order' => 0,
                ];
            }

            // Detach existing shop-specific categories first
            $this->component->product->categories()->wherePivot('shop_id', $shopId)->detach();

            // Attach new shop-specific categories (using PPM IDs)
            if (!empty($categoryData)) {
                $this->component->product->categories()->attach($categoryData);

                Log::info('[CATEGORY SYNC] Shop categories synced to pivot table (PPM IDs)', [
                    'product_id' => $this->component->product->id,
                    'shop_id' => $shopId,
                    'category_count' => count($ppmCategoryIds),
                    'ppm_ids' => $ppmCategoryIds,
                ]);
            }

            // Step 2: Sync category_mappings cache from pivot table
            $this->syncCategoryMappingsCache($this->component->product->id, $shopId);

        } catch (\Exception $e) {
            Log::error('[CATEGORY SYNC] Shop category sync failed', [
                'product_id' => $this->component->product->id,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Don't throw - category sync failure shouldn't stop product save
        }
    }

    /**
     * Sync category_mappings cache after pivot table update
     *
     * CRITICAL: category_mappings MUST reflect pivot table state
     * for backward compatibility and quick lookups
     *
     * @param int $productId Product ID
     * @param int $shopId Shop ID
     * @return void
     */
    private function syncCategoryMappingsCache(int $productId, int $shopId): void
    {
        $productShopData = ProductShopData::firstOrNew([
            'product_id' => $productId,
            'shop_id' => $shopId,
        ]);

        // Get fresh categories from pivot table
        $shopCategories = Product::find($productId)
            ->categories()
            ->wherePivot('shop_id', $shopId)
            ->get();

        if ($shopCategories->isEmpty()) {
            // No shop-specific categories - clear cache
            $productShopData->category_mappings = null;
            $productShopData->save();

            Log::info('[CATEGORY CACHE] Cleared category_mappings (no shop categories)', [
                'product_id' => $productId,
                'shop_id' => $shopId,
            ]);

            return;
        }

        // Get shop instance for CategoryMappingsConverter
        $shop = PrestaShopShop::find($shopId);

        if (!$shop) {
            Log::error('[CATEGORY CACHE] Shop not found, cannot sync cache', [
                'product_id' => $productId,
                'shop_id' => $shopId,
            ]);
            return;
        }

        // Build PPM category IDs array
        $ppmCategoryIds = $shopCategories->pluck('id')->toArray();

        // Convert to Option A format via CategoryMappingsConverter
        $converter = app(CategoryMappingsConverter::class);

        try {
            $categoryMappings = $converter->fromPivotData($ppmCategoryIds, $shop);

            // Update cache
            $productShopData->category_mappings = $categoryMappings;
            $productShopData->save();

            Log::info('[CATEGORY CACHE] Synced category_mappings cache from pivot', [
                'product_id' => $productId,
                'shop_id' => $shopId,
                'ppm_ids' => $ppmCategoryIds,
                'mappings_count' => isset($categoryMappings['mappings']) ? count($categoryMappings['mappings']) : 0,
                'source' => 'manual',
            ]);

        } catch (\Exception $e) {
            Log::error('[CATEGORY CACHE] Failed to sync category_mappings', [
                'product_id' => $productId,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE OPERATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Update product without closing form
     */
    public function updateOnly(): void
    {
        $this->save(); // Reuse existing save logic
    }

    /**
     * Save and close form
     */
    public function saveAndClose()
    {
        $this->save(); // Reuse existing save logic

        // Only redirect if save was successful (no validation errors)
        if (empty($this->component->validationErrors)) {
            return redirect('/admin/products');
        }
        // If there were errors, stay on the form
    }

    /*
    |--------------------------------------------------------------------------
    | PRICES & STOCK OPERATIONS (PROBLEM #4 - 2025-11-07)
    |--------------------------------------------------------------------------
    */

    /**
     * Save product prices to database
     *
     * PROBLEM #4 (2025-11-07): Task 11 - Save prices to product_prices table
     */
    private function savePrices(): void
    {
        if (!$this->component->product || !$this->component->product->exists) {
            Log::debug('savePrices: No product to save prices for');
            return;
        }

        $savedCount = 0;
        $deletedCount = 0;

        try {
            foreach ($this->component->prices as $priceGroupId => $priceData) {
                // If price is NULL or empty, delete existing record
                if (empty($priceData['net']) && empty($priceData['gross'])) {
                    ProductPrice::where('product_id', $this->component->product->id)
                               ->where('price_group_id', $priceGroupId)
                               ->whereNull('product_variant_id')
                               ->delete();
                    $deletedCount++;
                    continue;
                }

                // Create or update price record
                ProductPrice::updateOrCreate(
                    [
                        'product_id' => $this->component->product->id,
                        'product_variant_id' => null, // Master product prices (not variant)
                        'price_group_id' => $priceGroupId,
                    ],
                    [
                        'price_net' => $priceData['net'] ?? 0.00,
                        'price_gross' => $priceData['gross'] ?? 0.00,
                        'margin_percentage' => $priceData['margin'] ?? null,
                        'is_active' => $priceData['is_active'] ?? true,
                        'auto_calculate_gross' => false, // User provided both net and gross
                        'auto_calculate_margin' => false, // User provided margin explicitly
                    ]
                );

                $savedCount++;
            }

            Log::info('Product prices saved', [
                'product_id' => $this->component->product->id,
                'saved_count' => $savedCount,
                'deleted_count' => $deletedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save product prices', [
                'product_id' => $this->component->product->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // Re-throw to rollback transaction
        }
    }

    /**
     * Save product stock to database
     *
     * PROBLEM #4 (2025-11-07): Task 12 - Save stock to product_stock table
     */
    private function saveStock(): void
    {
        if (!$this->component->product || !$this->component->product->exists) {
            Log::debug('[STOCK SAVE] No product to save stock for');
            return;
        }

        $savedCount = 0;

        // DEBUG: Log incoming stock data
        Log::debug('[STOCK SAVE] Starting stock save', [
            'product_id' => $this->component->product->id,
            'stock_data' => $this->component->stock,
            'stock_count' => count($this->component->stock),
        ]);

        try {
            foreach ($this->component->stock as $warehouseId => $stockData) {
                // DEBUG: Log each warehouse stock
                Log::debug('[STOCK SAVE] Processing warehouse', [
                    'warehouse_id' => $warehouseId,
                    'quantity' => $stockData['quantity'] ?? 'null',
                    'reserved' => $stockData['reserved'] ?? 'null',
                    'minimum' => $stockData['minimum'] ?? 'null',
                    'location' => $stockData['location'] ?? 'null', // ETAP_08 FAZA 8
                ]);

                $stock = ProductStock::updateOrCreate(
                    [
                        'product_id' => $this->component->product->id,
                        'product_variant_id' => null, // Master product stock (not variant)
                        'warehouse_id' => $warehouseId,
                    ],
                    [
                        'quantity' => $stockData['quantity'] ?? 0,
                        'reserved_quantity' => $stockData['reserved'] ?? 0,
                        'minimum_stock' => $stockData['minimum'] ?? 0, // FIX: was minimum_stock_level
                        'location' => $stockData['location'] ?? '', // ETAP_08 FAZA 8: Warehouse location
                        'is_active' => true,
                        'track_stock' => true,
                    ]
                );

                // DEBUG: Log result
                Log::debug('[STOCK SAVE] Warehouse stock saved', [
                    'warehouse_id' => $warehouseId,
                    'stock_id' => $stock->id,
                    'was_created' => $stock->wasRecentlyCreated,
                    'saved_quantity' => $stock->quantity,
                ]);

                $savedCount++;
            }

            Log::info('[STOCK SAVE] Product stock saved successfully', [
                'product_id' => $this->component->product->id,
                'saved_count' => $savedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('[STOCK SAVE] Failed to save product stock', [
                'product_id' => $this->component->product->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // Re-throw to rollback transaction
        }
    }
}
