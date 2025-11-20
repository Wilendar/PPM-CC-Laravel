<?php

namespace App\Http\Livewire\Products\Management\Services;

use App\Models\Product;
use App\Models\ProductShopData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * ProductCategoryManager Service
 *
 * Handles category management for ProductForm
 * Manages both default and shop-specific category assignments
 * Separated from main component per CLAUDE.md guidelines
 *
 * UPDATED 2025-10-13: Per-Shop Categories Architecture
 * - Uses shop_id column in product_categories pivot table
 * - shop_id=NULL → Default categories (from first import)
 * - shop_id=X → Per-shop override (different categories per shop)
 * - Single table architecture replaces old ProductShopCategory table
 *
 * @package App\Http\Livewire\Products\Management\Services
 * @version 2.0 - Per-Shop Categories Support
 * @since 2025-10-13
 */
class ProductCategoryManager
{
    protected $component;

    public function __construct($component)
    {
        $this->component = $component;
    }

    /*
    |--------------------------------------------------------------------------
    | CATEGORY LOADING
    |--------------------------------------------------------------------------
    */

    /**
     * Load existing categories for edit mode
     * Enhanced for multi-store support with shop-specific categories
     */
    public function loadCategories(): void
    {
        if (!$this->component->product || !$this->component->product->exists) {
            return;
        }

        // Load default categories to new system
        $categories = $this->component->product->categories()
            ->select(['categories.id', 'categories.name'])
            ->get();

        $selectedIds = $categories->pluck('id')->toArray();

        // Set primary category (first one or from product_categories pivot)
        $primaryCategoryId = null;
        if ($categories->isNotEmpty()) {
            $primaryCategory = $this->component->product->categories()
                ->wherePivot('is_primary', true)
                ->first();

            $primaryCategoryId = $primaryCategory?->id ?? $categories->first()->id;
        }

        // Store in new defaultCategories structure
        $this->component->defaultCategories = [
            'selected' => $selectedIds,
            'primary' => $primaryCategoryId
        ];

        // Load shop-specific categories
        $this->loadShopCategories();

        Log::info('Categories loaded for product', [
            'product_id' => $this->component->product->id,
            'default_categories_count' => count($this->component->defaultCategories['selected']),
            'primary_category' => $this->component->defaultCategories['primary'],
            'shop_categories_loaded' => count($this->component->shopCategories),
        ]);
    }

    /**
     * Load shop-specific categories from database
     * FIX 2025-11-20: Read from product_shop_data.category_mappings (Option A) instead of pivot table
     */
    private function loadShopCategories(): void
    {
        if (!$this->component->product || !$this->component->product->exists) {
            return;
        }

        // FIX 2025-11-20: Load from product_shop_data.category_mappings (NEW Option A architecture)
        // Get all ProductShopData records for this product
        $shopDataRecords = ProductShopData::where('product_id', $this->component->product->id)
            ->whereNotNull('shop_id')
            ->get();

        foreach ($shopDataRecords as $shopData) {
            if (!empty($shopData->category_mappings)) {
                // CategoryMappingsCast auto-deserializes JSON to Option A structure
                $categoryMappings = $shopData->category_mappings;

                $this->component->shopCategories[$shopData->shop_id] = [
                    'selected' => $categoryMappings['ui']['selected'] ?? [],
                    'primary' => $categoryMappings['ui']['primary'] ?? null,
                    'mappings' => $categoryMappings['mappings'] ?? [], // ETAP_07b: PPM ID → PrestaShop ID mappings
                ];

                Log::debug('loadShopCategories: Loaded from product_shop_data.category_mappings', [
                    'product_id' => $this->component->product->id,
                    'shop_id' => $shopData->shop_id,
                    'selected' => $this->component->shopCategories[$shopData->shop_id]['selected'],
                    'primary' => $this->component->shopCategories[$shopData->shop_id]['primary'],
                    'mappings_count' => count($this->component->shopCategories[$shopData->shop_id]['mappings']),
                ]);
            }
        }

        Log::info('Shop categories loaded (Option A Architecture)', [
            'product_id' => $this->component->product->id,
            'shops_with_categories' => array_keys($this->component->shopCategories),
            'architecture' => 'product_shop_data.category_mappings JSON',
            'final_shopCategories' => $this->component->shopCategories,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CATEGORY MANAGEMENT - Default Product
    |--------------------------------------------------------------------------
    */

    /**
     * Toggle category selection
     */
    public function toggleCategory(int $categoryId): void
    {
        if ($this->component->activeShopId === null) {
            // Default product categories
            $this->toggleDefaultCategory($categoryId);
        } else {
            // Shop-specific categories
            $this->toggleShopCategory($categoryId);
        }
    }

    /**
     * Set primary category
     */
    public function setPrimaryCategory(int $categoryId): void
    {
        if ($this->component->activeShopId === null) {
            // Default primary category
            $this->setDefaultPrimaryCategory($categoryId);
        } else {
            // Shop-specific primary category
            $this->setShopPrimaryCategory($categoryId);
        }
    }

    /**
     * Toggle default product category
     */
    private function toggleDefaultCategory(int $categoryId): void
    {
        $currentCategories = $this->component->defaultCategories;
        $selectedCategories = $currentCategories['selected'] ?? [];
        $primaryCategory = $currentCategories['primary'] ?? null;

        if (in_array($categoryId, $selectedCategories)) {
            // Remove category
            $selectedCategories = array_values(array_diff($selectedCategories, [$categoryId]));

            // Clear primary if this was primary category
            if ($primaryCategory === $categoryId) {
                $primaryCategory = !empty($selectedCategories) ? $selectedCategories[0] : null;
            }
        } else {
            // Add category
            $selectedCategories[] = $categoryId;

            // Set as primary if first category
            if (!$primaryCategory) {
                $primaryCategory = $categoryId;
            }
        }

        // Update default categories
        $this->component->defaultCategories = [
            'selected' => $selectedCategories,
            'primary' => $primaryCategory
        ];
    }

    /**
     * Set default primary category
     */
    private function setDefaultPrimaryCategory(int $categoryId): void
    {
        $currentCategories = $this->component->defaultCategories;
        $selectedCategories = $currentCategories['selected'] ?? [];

        if (in_array($categoryId, $selectedCategories)) {
            $this->component->defaultCategories = [
                'selected' => $selectedCategories,
                'primary' => $categoryId
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CATEGORY MANAGEMENT - Shop-Specific
    |--------------------------------------------------------------------------
    */

    /**
     * Toggle shop-specific category
     */
    private function toggleShopCategory(int $categoryId): void
    {
        $shopId = $this->component->activeShopId;

        if (!isset($this->component->shopCategories[$shopId])) {
            $this->component->shopCategories[$shopId] = [
                'selected' => [],
                'primary' => null,
            ];
        }

        $selected = $this->component->shopCategories[$shopId]['selected'];

        if (in_array($categoryId, $selected)) {
            // Remove category
            $this->component->shopCategories[$shopId]['selected'] = array_diff($selected, [$categoryId]);

            // Clear primary if this was primary category
            if ($this->component->shopCategories[$shopId]['primary'] === $categoryId) {
                $this->component->shopCategories[$shopId]['primary'] = null;
            }
        } else {
            // Add category
            $this->component->shopCategories[$shopId]['selected'][] = $categoryId;

            // Set as primary if first category
            if (!$this->component->shopCategories[$shopId]['primary']) {
                $this->component->shopCategories[$shopId]['primary'] = $categoryId;
            }
        }

        // Re-index array
        $this->component->shopCategories[$shopId]['selected'] = array_values(
            $this->component->shopCategories[$shopId]['selected']
        );
    }

    /**
     * Set shop-specific primary category
     */
    private function setShopPrimaryCategory(int $categoryId): void
    {
        $shopId = $this->component->activeShopId;

        if (!isset($this->component->shopCategories[$shopId])) {
            return;
        }

        if (in_array($categoryId, $this->component->shopCategories[$shopId]['selected'])) {
            $this->component->shopCategories[$shopId]['primary'] = $categoryId;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CATEGORY SYNCHRONIZATION
    |--------------------------------------------------------------------------
    */

    /**
     * Sync categories with database (both default and shop-specific)
     */
    public function syncCategories(): void
    {
        Log::info('syncCategories called', [
            'product_id' => $this->component->product->id ?? 'NO_PRODUCT',
            'product_exists' => $this->component->product ? $this->component->product->exists : false,
            'defaultCategories_before_sync' => $this->component->defaultCategories,
        ]);

        if (!$this->component->product || !$this->component->product->exists) {
            Log::warning('syncCategories aborted - no product or product does not exist');
            return;
        }

        DB::transaction(function () {
            // Sync default categories (shop_id=NULL)
            $this->syncDefaultCategories();

            // UPDATED 2025-10-13: Re-enabled with new architecture
            // Now uses shop_id column in product_categories pivot
            $this->syncShopCategories();

            Log::info('All categories synced to database (NEW ARCHITECTURE)', [
                'product_id' => $this->component->product->id,
                'default_categories_count' => count($this->component->defaultCategories['selected'] ?? []),
                'shop_categories_count' => count($this->component->shopCategories),
                'primary_category' => $this->component->defaultCategories['primary'] ?? null,
                'architecture' => 'shop_id in product_categories pivot',
            ]);
        });
    }

    /**
     * Sync default categories to product_categories table
     * NOTE: Triggers removed, we handle primary logic manually
     */
    private function syncDefaultCategories(): void
    {
        $selectedCategories = $this->component->defaultCategories['selected'] ?? [];
        $primaryCategoryId = $this->component->defaultCategories['primary'] ?? null;

        Log::info('syncDefaultCategories called', [
            'product_id' => $this->component->product->id,
            'selectedCategories' => $selectedCategories,
            'primaryCategoryId' => $primaryCategoryId,
            'component_defaultCategories' => $this->component->defaultCategories,
        ]);

        // Validate that all selected categories exist in database
        $existingCategoryIds = \App\Models\Category::pluck('id')->toArray();
        $validCategoryIds = array_intersect($selectedCategories, $existingCategoryIds);

        if (count($validCategoryIds) !== count($selectedCategories)) {
            $invalidIds = array_diff($selectedCategories, $existingCategoryIds);
            Log::warning('Invalid category IDs filtered out', [
                'invalid_ids' => $invalidIds,
                'valid_ids' => $validCategoryIds,
                'product_id' => $this->component->product->id,
            ]);
        }

        // Update component with only valid categories
        $this->component->defaultCategories['selected'] = $validCategoryIds;

        // Validate primary category
        if ($primaryCategoryId && !in_array($primaryCategoryId, $validCategoryIds)) {
            $primaryCategoryId = !empty($validCategoryIds) ? $validCategoryIds[0] : null;
            $this->component->defaultCategories['primary'] = $primaryCategoryId;
            Log::warning('Primary category was invalid, reset to first valid category', [
                'new_primary' => $primaryCategoryId,
                'product_id' => $this->component->product->id,
            ]);
        }

        // UPDATED 2025-10-13: Manual is_primary reset (triggers removed due to MySQL 1442)
        // Reset all is_primary=0 for this product's default categories BEFORE sync
        DB::table('product_categories')
            ->where('product_id', $this->component->product->id)
            ->whereNull('shop_id') // Only default categories
            ->update(['is_primary' => false]);

        Log::debug('Default categories is_primary reset to 0', [
            'product_id' => $this->component->product->id,
        ]);

        // Prepare category data with proper primary flags and shop_id=NULL
        // UPDATED 2025-10-13: Add shop_id=null for default categories
        $categoryData = [];
        foreach ($validCategoryIds as $index => $categoryId) {
            $categoryData[$categoryId] = [
                'is_primary' => $categoryId === $primaryCategoryId,
                'sort_order' => $index,
                'shop_id' => null, // Default categories have shop_id=NULL
            ];
        }

        // Use sync to replace all categories at once
        // This will only sync categories with shop_id=NULL (default)
        $this->component->product->categories()->sync($categoryData);

        // CRITICAL FIX: Update component with exactly what was saved to database
        // This ensures pending changes system has correct data for "Save and Close"
        $this->component->defaultCategories = [
            'selected' => $validCategoryIds,
            'primary' => $primaryCategoryId
        ];

        Log::info('Default categories synced successfully', [
            'product_id' => $this->component->product->id,
            'categories' => $validCategoryIds,
            'primary' => $primaryCategoryId,
            'sync_data' => $categoryData,
            'component_updated' => true,
        ]);
    }

    /**
     * Sync shop-specific categories to product_categories pivot with shop_id
     * UPDATED 2025-10-13: Uses new shop_id column in product_categories pivot
     */
    private function syncShopCategories(): void
    {
        // FIX 2025-11-20 (ETAP_07b): DISABLED - Shop categories now stored in product_shop_data.category_mappings
        // OLD ARCHITECTURE: product_categories table with shop_id column (DEPRECATED)
        // NEW ARCHITECTURE: product_shop_data.category_mappings JSON (Option A canonical format)
        // Shop categories are saved by ProductForm::savePendingChangesToShop() using CategoryMappingsConverter
        Log::debug('[ETAP_07b] syncShopCategories() SKIPPED - using category_mappings JSON', [
            'product_id' => $this->component->product->id ?? 'NO_PRODUCT',
            'shop_count' => count($this->component->shopCategories),
            'reason' => 'ETAP_07b new architecture',
        ]);
        return;

        // OLD CODE BELOW (DISABLED 2025-11-20)
        foreach ($this->component->shopCategories as $shopId => $shopCategoryData) {
            $selectedCategories = $shopCategoryData['selected'] ?? [];
            $primaryCategoryId = $shopCategoryData['primary'] ?? null;

            // UPDATED 2025-10-13: Manual is_primary reset (triggers removed due to MySQL 1442)
            // Reset all is_primary=0 for this product + shop BEFORE insert
            DB::table('product_categories')
                ->where('product_id', $this->component->product->id)
                ->where('shop_id', $shopId)
                ->update(['is_primary' => false]);

            Log::debug('Shop categories is_primary reset to 0', [
                'product_id' => $this->component->product->id,
                'shop_id' => $shopId,
            ]);

            // Delete existing per-shop categories for this product + shop
            DB::table('product_categories')
                ->where('product_id', $this->component->product->id)
                ->where('shop_id', $shopId)
                ->delete();

            // Insert new per-shop categories
            foreach ($selectedCategories as $index => $categoryId) {
                DB::table('product_categories')->insert([
                    'product_id' => $this->component->product->id,
                    'category_id' => $categoryId,
                    'shop_id' => $shopId, // Per-shop override
                    'is_primary' => $categoryId === $primaryCategoryId,
                    'sort_order' => $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Log::info('Shop categories synced (NEW ARCHITECTURE)', [
                'product_id' => $this->component->product->id,
                'shop_id' => $shopId,
                'categories' => $selectedCategories,
                'primary' => $primaryCategoryId,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CATEGORY UTILITIES
    |--------------------------------------------------------------------------
    */

    /**
     * Get categories for current context (default or shop-specific)
     */
    public function getCurrentCategories(): array
    {
        return $this->component->getCurrentContextCategories()['selected'] ?? [];
    }

    /**
     * Get primary category for current context
     */
    public function getCurrentPrimaryCategory(): ?int
    {
        return $this->component->getCurrentContextCategories()['primary'] ?? null;
    }

    /**
     * Check if category is selected in current context
     */
    public function isCategorySelected(int $categoryId): bool
    {
        return in_array($categoryId, $this->getCurrentCategories());
    }

    /**
     * Check if category is primary in current context
     */
    public function isCategoryPrimary(int $categoryId): bool
    {
        return $categoryId === $this->getCurrentPrimaryCategory();
    }
}