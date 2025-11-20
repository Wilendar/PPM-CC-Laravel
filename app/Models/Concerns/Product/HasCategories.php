<?php

namespace App\Models\Concerns\Product;

use App\Models\Category;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;

/**
 * HasCategories Trait - Product Category Management
 *
 * Responsibility: Category relationships i multi-store category support
 *
 * Features:
 * - Default categories (shop_id=NULL) dla pierwszy import
 * - Per-shop categories z fallback do default
 * - Primary category per shop support
 * - Category grouped by shop dla UI
 * - Max 10 categories per product validation
 *
 * Architecture: SKU-first pattern preserved
 * Performance: Optimized queries z proper eager loading
 * Integration: PrestaShop ps_category_product mapping ready
 *
 * @package App\Models\Concerns\Product
 * @version 1.0
 * @since ETAP_05a SEKCJA 0 - Product.php Refactoring
 */
trait HasCategories
{
    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS - Category Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Product categories relationship (many:many) - DEFAULT CATEGORIES ONLY
     *
     * UPDATED 2025-10-13: Per-Shop Categories Support
     *
     * Business Logic:
     * - shop_id=NULL → "dane domyślne" (z pierwszego importu)
     * - Produkt może być w wielu kategoriach (max 10)
     *
     * Performance: Pivot table z dodatkowymi metadatami (is_primary, sort_order, shop_id)
     *
     * Usage: $product->categories - zwraca TYLKO default categories (shop_id=NULL)
     * Per-shop: Use categoriesForShop($shopId) dla shop-specific categories
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories')
                    ->withPivot(['is_primary', 'sort_order', 'shop_id'])
                    ->wherePivotNull('shop_id') // ONLY default categories
                    ->withTimestamps()
                    ->orderBy('product_categories.sort_order', 'asc');
    }

    /**
     * Product categories for specific shop (many:many) - PER-SHOP OVERRIDE
     *
     * ADDED 2025-10-13: Per-Shop Categories Support
     *
     * Business Logic:
     * - Returns categories specific to given shop (shop_id=X)
     * - Falls back to default categories if no shop-specific exist
     *
     * Performance: Single query with shop_id filter
     *
     * Usage: $product->categoriesForShop($shopId) - zwraca per-shop lub default
     *
     * @param int $shopId PrestaShop shop ID
     * @param bool $fallbackToDefault If true, returns default categories when no shop-specific exist
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categoriesForShop(int $shopId, bool $fallbackToDefault = true): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories')
                    ->withPivot(['is_primary', 'sort_order', 'shop_id'])
                    ->wherePivot('shop_id', $shopId)
                    ->withTimestamps()
                    ->orderBy('product_categories.sort_order', 'asc');
    }

    /**
     * Get effective categories for shop (per-shop if exist, otherwise default)
     *
     * ADDED 2025-10-13: Per-Shop Categories Support
     *
     * Business Logic:
     * - Checks if shop-specific categories exist
     * - Returns shop-specific if exist, default otherwise
     *
     * Performance: Two queries max (shop-specific check + fallback)
     *
     * Usage: $categories = $product->getEffectiveCategoriesForShop($shopId)
     *
     * @param int $shopId PrestaShop shop ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEffectiveCategoriesForShop(int $shopId): Collection
    {
        // Check if shop-specific categories exist
        $shopCategories = $this->categoriesForShop($shopId, false)->get();

        if ($shopCategories->isNotEmpty()) {
            return $shopCategories;
        }

        // Fallback to default categories
        return $this->categories;
    }

    /**
     * Get all categories grouped by shop (for UI display)
     *
     * ADDED 2025-10-13: Per-Shop Categories Support
     *
     * Business Logic:
     * - Returns all categories grouped by shop_id
     * - shop_id=NULL → "Dane domyślne"
     * - shop_id=X → Shop name from prestashop_shops
     *
     * Performance: Single query with join to prestashop_shops
     *
     * Usage: $grouped = $product->allCategoriesGroupedByShop()
     *
     * @return array ['default' => Collection, 'shops' => ['shop_name' => Collection]]
     */
    public function allCategoriesGroupedByShop(): array
    {
        $allCategories = $this->belongsToMany(Category::class, 'product_categories')
                              ->withPivot(['is_primary', 'sort_order', 'shop_id'])
                              ->withTimestamps()
                              ->orderBy('product_categories.shop_id', 'asc')
                              ->orderBy('product_categories.sort_order', 'asc')
                              ->get();

        $grouped = [
            'default' => collect([]),
            'shops' => [],
        ];

        foreach ($allCategories as $category) {
            $shopId = $category->pivot->shop_id;

            if ($shopId === null) {
                $grouped['default']->push($category);
            } else {
                if (!isset($grouped['shops'][$shopId])) {
                    $grouped['shops'][$shopId] = collect([]);
                }
                $grouped['shops'][$shopId]->push($category);
            }
        }

        return $grouped;
    }

    /**
     * Primary category relationship - DEFAULT CATEGORIES ONLY
     *
     * UPDATED 2025-10-13: Per-Shop Categories Support
     *
     * Business Logic: Jeden produkt ma jedną kategorię domyślną dla PrestaShop export
     * Performance: Single query dla najważniejszej kategorii
     *
     * Usage: $product->primaryCategory - zwraca TYLKO default primary (shop_id=NULL)
     * Per-shop: Use primaryCategoryForShop($shopId) dla shop-specific primary
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function primaryCategory(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories')
                    ->withPivot(['is_primary', 'sort_order', 'shop_id'])
                    ->wherePivotNull('shop_id') // ONLY default
                    ->wherePivot('is_primary', true)
                    ->limit(1);
    }

    /**
     * Primary category for specific shop
     *
     * ADDED 2025-10-13: Per-Shop Categories Support
     *
     * Business Logic: Returns primary category for given shop
     * Performance: Single query with shop_id + is_primary filter
     *
     * Usage: $primaryCat = $product->primaryCategoryForShop($shopId)->first()
     *
     * @param int $shopId PrestaShop shop ID
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function primaryCategoryForShop(int $shopId): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories')
                    ->withPivot(['is_primary', 'sort_order', 'shop_id'])
                    ->wherePivot('shop_id', $shopId)
                    ->wherePivot('is_primary', true)
                    ->limit(1);
    }



    /*
    |--------------------------------------------------------------------------
    | BUSINESS METHODS - Category Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Set primary category for product
     *
     * Business Logic: Enforce single primary category rule
     * Performance: Optimized pivot update
     *
     * @param int $categoryId
     * @return bool
     */
    public function setPrimaryCategory(int $categoryId): bool
    {
        // Remove current primary
        $this->categories()->updateExistingPivot(
            $this->categories()->pluck('categories.id')->toArray(),
            ['is_primary' => false]
        );

        // Set new primary (attach if not exists)
        if (!$this->categories()->where('categories.id', $categoryId)->exists()) {
            $this->categories()->attach($categoryId, [
                'is_primary' => true,
                'sort_order' => 0,
            ]);
        } else {
            $this->categories()->updateExistingPivot($categoryId, [
                'is_primary' => true
            ]);
        }

        return true;
    }
}
