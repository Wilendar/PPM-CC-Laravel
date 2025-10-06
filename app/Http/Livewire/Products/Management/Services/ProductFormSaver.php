<?php

namespace App\Http\Livewire\Products\Management\Services;

use App\Models\Product;
use App\Models\ProductShopData;
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

        // Add category mappings if exist
        if (isset($this->component->shopCategories[$shopId])) {
            $shopData['category_mappings'] = $this->component->shopCategories[$shopId];
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
     * Sync categories with database
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
}