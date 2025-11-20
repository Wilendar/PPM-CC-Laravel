<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * ProductShopCategory Model - Shop-Specific Product Categories
 *
 * Obsługuje kategorie produktów per sklep PrestaShop:
 * - Dziedziczenie z domyślnych kategorii (product_categories)
 * - Nadpisanie kategorii per sklep
 * - Synchronizacja z PrestaShop ps_category_product per sklep
 * - Color coding support (inherited/same/different)
 *
 * Business Logic:
 * - Jeden produkt może mieć różne kategorie per sklep
 * - is_primary=true -> kategoria główna dla PrestaShop export
 * - sort_order -> kolejność w kategorii per sklep
 *
 * @property int $id
 * @property int $product_id
 * @property int $shop_id
 * @property int $category_id
 * @property bool $is_primary
 * @property int $sort_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\PrestaShopShop $shop
 * @property-read \App\Models\Category $category
 *
 * @method static \Illuminate\Database\Eloquent\Builder forProduct(int $productId)
 * @method static \Illuminate\Database\Eloquent\Builder forShop(int $shopId)
 * @method static \Illuminate\Database\Eloquent\Builder forCategory(int $categoryId)
 * @method static \Illuminate\Database\Eloquent\Builder primaryOnly()
 * @method static \Illuminate\Database\Eloquent\Builder sortedByOrder()
 *
 * @package App\Models
 * @version 1.0
 * @since ETAP_05 Multi-Store Category System
 */
class ProductShopCategory extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_shop_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'product_id',
        'shop_id',
        'category_id',
        'is_primary',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'shop_id' => 'integer',
            'category_id' => 'integer',
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the product that owns this shop category assignment.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the shop that owns this category assignment.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    /**
     * Get the category that this assignment references.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Filter by product ID
     */
    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope: Filter by shop ID
     */
    public function scopeForShop(Builder $query, int $shopId): Builder
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope: Filter by category ID
     */
    public function scopeForCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope: Primary categories only
     */
    public function scopePrimaryOnly(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope: Sort by order and category name
     */
    public function scopeSortedByOrder(Builder $query): Builder
    {
        return $query->join('categories', 'categories.id', '=', 'product_shop_categories.category_id')
                    ->orderBy('product_shop_categories.sort_order', 'asc')
                    ->orderBy('categories.name', 'asc')
                    ->select('product_shop_categories.*');
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get categories for product and shop
     */
    public static function getCategoriesForProductShop(int $productId, int $shopId): array
    {
        return static::forProduct($productId)
                    ->forShop($shopId)
                    ->with('category')
                    ->sortedByOrder()
                    ->get()
                    ->pluck('category_id')
                    ->toArray();
    }

    /**
     * Get primary category for product and shop
     */
    public static function getPrimaryCategoryForProductShop(int $productId, int $shopId): ?int
    {
        $primary = static::forProduct($productId)
                        ->forShop($shopId)
                        ->primaryOnly()
                        ->first();

        return $primary?->category_id;
    }

    /**
     * Set categories for product and shop
     *
     * FIXED: Uproszczona metoda bez triggerów
     * Problem: Triggery powodowały błąd SQL 1442 przy INSERT/UPDATE
     * Rozwiązanie: Usunięte triggery + application logic zapewnia is_primary constraint
     */
    public static function setCategoriesForProductShop(int $productId, int $shopId, array $categoryIds, ?int $primaryCategoryId = null): void
    {
        \DB::transaction(function () use ($productId, $shopId, $categoryIds, $primaryCategoryId) {
            // 1. Remove existing categories for this product-shop combination
            static::forProduct($productId)->forShop($shopId)->delete();

            // 2. Ensure only one primary category (application logic replaces triggers)
            if ($primaryCategoryId !== null && !in_array($primaryCategoryId, $categoryIds)) {
                // Primary category must be in selected categories
                $primaryCategoryId = null;
            }

            // 3. Add new categories
            foreach ($categoryIds as $index => $categoryId) {
                static::create([
                    'product_id' => $productId,
                    'shop_id' => $shopId,
                    'category_id' => $categoryId,
                    'is_primary' => $categoryId === $primaryCategoryId,
                    'sort_order' => $index,
                ]);
            }
        });
    }

    /**
     * Check if product has shop-specific categories (different from default)
     */
    public static function hasShopSpecificCategories(int $productId, int $shopId): bool
    {
        // Get default categories
        $defaultCategories = \DB::table('product_categories')
                               ->where('product_id', $productId)
                               ->pluck('category_id')
                               ->sort()
                               ->values()
                               ->toArray();

        // Get shop categories
        $shopCategories = static::getCategoriesForProductShop($productId, $shopId);
        sort($shopCategories);

        // Compare arrays
        return $defaultCategories !== $shopCategories;
    }

    /**
     * Get inheritance status for product shop categories
     * Returns: 'inherited', 'same', 'different'
     */
    public static function getCategoryInheritanceStatus(int $productId, int $shopId): string
    {
        $shopCategories = static::getCategoriesForProductShop($productId, $shopId);

        if (empty($shopCategories)) {
            return 'inherited'; // No shop-specific categories, inheriting from default
        }

        if (static::hasShopSpecificCategories($productId, $shopId)) {
            return 'different'; // Shop has different categories than default
        }

        return 'same'; // Shop has same categories as default but explicitly set
    }
}