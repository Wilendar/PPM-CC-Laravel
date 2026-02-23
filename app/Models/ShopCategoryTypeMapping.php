<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * ShopCategoryTypeMapping - Maps PPM categories to product types per shop
 *
 * Allows admin to define: "PPM Category X in Shop Y -> ProductType Z"
 * with optional inheritance to child categories (include_children flag).
 *
 * Cascade priority resolution:
 * 1. Direct match (category_id matches product's category)
 * 2. Ancestor match with include_children=true (walk up category tree)
 * 3. Higher priority value wins (orderByDesc)
 *
 * @property int $id
 * @property int $shop_id FK to prestashop_shops
 * @property int $category_id FK to categories (PPM category ID)
 * @property int $product_type_id FK to product_types
 * @property bool $include_children Whether mapping applies to child categories
 * @property int $priority Higher = more important (default 50)
 * @property bool $is_active
 * @property int|null $created_by FK to users
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read PrestaShopShop $shop
 * @property-read Category $category
 * @property-read ProductType $productType
 * @property-read User|null $createdBy
 */
class ShopCategoryTypeMapping extends Model
{
    protected $table = 'shop_category_type_mappings';

    protected $fillable = [
        'shop_id',
        'category_id',
        'product_type_id',
        'include_children',
        'priority',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'include_children' => 'boolean',
            'is_active' => 'boolean',
            'priority' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeForShop(Builder $query, int $shopId): Builder
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderByDesc('priority');
    }
}
