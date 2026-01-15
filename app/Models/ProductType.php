<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * ProductType Model - Edytowalne typy produktów
 *
 * Zastępuje hardcoded ENUM system elastycznym
 * zarządzaniem typami produktów przez Admin/Manager
 *
 * @property int $id
 * @property string $name Nazwa typu (np. "Części zamienne")
 * @property string $slug Slug URL-friendly (np. "czesci-zamienne")
 * @property string|null $description Opis typu produktu
 * @property string|null $icon Ikona typu (CSS class lub SVG)
 * @property array|null $default_attributes Domyślne atrybuty dla typu
 * @property bool $is_active Status aktywności typu
 * @property int $sort_order Kolejność wyświetlania
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Product[] $products
 * @property-read int $products_count
 *
 * @method static Builder active()
 * @method static Builder ordered()
 *
 * @package App\Models
 * @version 1.0
 * @since ETAP_05 FAZA 4 - Editable Product Types
 */
class ProductType extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'default_attributes',
        'is_active',
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
            'default_attributes' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Get products of this type
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to get only active product types
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort_order then name
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS
    |--------------------------------------------------------------------------
    */

    /**
     * Set slug attribute automatically from name
     */
    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = $value;

        // Auto-generate slug if not exists
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = \Illuminate\Support\Str::slug($value);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this type can be deleted (no products assigned)
     */
    public function canBeDeleted(): bool
    {
        return $this->products()->count() === 0;
    }

    /**
     * Get default product types for seeding
     *
     * FIX 2025-12-22: Added missing types (Akcesoria, Oleje i chemia, Outlet)
     * and renamed "Część zamiennicza" to "Części Zamienne" for consistency
     */
    public static function getDefaultTypes(): array
    {
        return [
            [
                'name' => 'Pojazdy',
                'slug' => 'pojazdy',
                'description' => 'Kompletne pojazdy - motocykle, quady, skutery, pitbike',
                'icon' => 'fas fa-motorcycle',
                'is_active' => true,
                'sort_order' => 1,
                'default_attributes' => [
                    'model' => null,
                    'year' => null,
                    'engine' => null,
                    'vin' => null,
                ]
            ],
            [
                'name' => 'Części Zamienne',
                'slug' => 'czesci-zamienne',
                'description' => 'Części zamienne do pojazdów - silnik, zawieszenie, hamulce',
                'icon' => 'fas fa-cog',
                'is_active' => true,
                'sort_order' => 2,
                'default_attributes' => [
                    'compatibility' => [],
                    'original_number' => null,
                    'replacement_number' => null,
                ]
            ],
            [
                'name' => 'Akcesoria',
                'slug' => 'akcesoria',
                'description' => 'Akcesoria do pojazdów i motocykli',
                'icon' => 'fas fa-puzzle-piece',
                'is_active' => true,
                'sort_order' => 3,
                'default_attributes' => []
            ],
            [
                'name' => 'Oleje i chemia',
                'slug' => 'oleje-i-chemia',
                'description' => 'Oleje silnikowe, płyny eksploatacyjne, chemia motocyklowa',
                'icon' => 'fas fa-oil-can',
                'is_active' => true,
                'sort_order' => 4,
                'default_attributes' => [
                    'volume' => null,
                    'viscosity' => null,
                ]
            ],
            [
                'name' => 'Odzież',
                'slug' => 'odziez',
                'description' => 'Odzież motocyklowa - kurtki, spodnie, buty, kaski',
                'icon' => 'fas fa-tshirt',
                'is_active' => true,
                'sort_order' => 5,
                'default_attributes' => [
                    'sizes' => [],
                    'colors' => [],
                    'material' => null,
                ]
            ],
            [
                'name' => 'Outlet',
                'slug' => 'outlet',
                'description' => 'Produkty przecenione, końcówki kolekcji',
                'icon' => 'fas fa-tags',
                'is_active' => true,
                'sort_order' => 6,
                'default_attributes' => []
            ],
            [
                'name' => 'Inne',
                'slug' => 'inne',
                'description' => 'Pozostałe produkty nieskategoryzowane',
                'icon' => 'fas fa-box',
                'is_active' => true,
                'sort_order' => 99,
                'default_attributes' => []
            ],
        ];
    }

    /**
     * Category name to ProductType slug mapping
     *
     * Maps level-2 category names (children of "Wszystko") to ProductType slugs
     * Used for auto-detecting product type during import
     *
     * @var array<string, string>
     */
    public const CATEGORY_TYPE_MAP = [
        'Pojazdy' => 'pojazdy',
        'Części Zamienne' => 'czesci-zamienne',
        'Części zamienne' => 'czesci-zamienne',  // Alternative spelling
        'Akcesoria' => 'akcesoria',
        'Oleje i chemia' => 'oleje-i-chemia',
        'Odzież' => 'odziez',
        'Outlet' => 'outlet',
    ];

    /**
     * Auto-detect ProductType from category hierarchy
     *
     * Finds the level-2 (main) category and maps it to ProductType
     * Level structure: Baza (0) -> Wszystko (1) -> Main Category (2) -> Subcategories (3+)
     *
     * @param Category $category The product's primary category
     * @return ProductType|null Detected ProductType or null if not found
     */
    public static function detectFromCategory(Category $category): ?self
    {
        // Find the level-2 ancestor (main category under "Wszystko")
        $level2Category = self::findLevel2Ancestor($category);

        if (!$level2Category) {
            \Illuminate\Support\Facades\Log::debug('ProductType::detectFromCategory - No level-2 ancestor found', [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'category_level' => $category->level,
            ]);
            return null;
        }

        // Map category name to ProductType slug
        $typeSlug = self::CATEGORY_TYPE_MAP[$level2Category->name] ?? null;

        if (!$typeSlug) {
            \Illuminate\Support\Facades\Log::debug('ProductType::detectFromCategory - Category not in mapping', [
                'level2_category_name' => $level2Category->name,
                'available_mappings' => array_keys(self::CATEGORY_TYPE_MAP),
            ]);
            return null;
        }

        // Find ProductType by slug
        $productType = self::where('slug', $typeSlug)->first();

        \Illuminate\Support\Facades\Log::info('ProductType::detectFromCategory - Type detected', [
            'category_id' => $category->id,
            'category_name' => $category->name,
            'level2_category' => $level2Category->name,
            'detected_type' => $productType?->name,
            'type_slug' => $typeSlug,
        ]);

        return $productType;
    }

    /**
     * Find the level-2 ancestor of a category
     *
     * @param Category $category
     * @return Category|null
     */
    protected static function findLevel2Ancestor(Category $category): ?Category
    {
        // If category is level 2, return it
        if ($category->level === 2) {
            return $category;
        }

        // If category is level 1 or 0, no level-2 ancestor
        if ($category->level < 2) {
            return null;
        }

        // Walk up the tree until we find level 2
        $current = $category;
        while ($current && $current->level > 2) {
            $current = $current->parent;
        }

        return ($current && $current->level === 2) ? $current : null;
    }

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-generate sort_order if not set
        static::creating(function (ProductType $productType) {
            if (is_null($productType->sort_order)) {
                $maxOrder = static::max('sort_order') ?? 0;
                $productType->sort_order = $maxOrder + 10;
            }
        });
    }
}