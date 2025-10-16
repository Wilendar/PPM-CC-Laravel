<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use App\Models\ShopMapping;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopImportService;

/**
 * Category Model - Self-Referencing Tree Structure dla PPM-CC-Laravel
 * 
 * Obsługuje hierarchiczną strukturę kategorii:
 * - 5 poziomów zagnieżdżenia (0-4)
 * - Path materialization dla performance ('/1/2/5')
 * - Breadcrumb navigation
 * - Tree traversal operations (ancestors, descendants)
 * - SEO optimization z automatic slug generation
 * 
 * Performance: Path indexes dla <50ms tree queries
 * Business Logic: PrestaShop category mapping ready
 * 
 * @property int $id
 * @property int|null $parent_id Parent category ID
 * @property string $name Nazwa kategorii
 * @property string|null $slug URL-friendly slug
 * @property string|null $description Opis kategorii
 * @property string|null $short_description Krótki opis
 * @property int $level Poziom zagnieżdżenia (0-4)
 * @property string|null $path Materialized path '/1/2/5'
 * @property int $sort_order Kolejność w kategorii
 * @property bool $is_active Status aktywności
 * @property bool $is_featured Kategoria polecana
 * @property string|null $icon Font-awesome lub custom icon
 * @property string|null $icon_path Ścieżka do pliku ikony
 * @property string|null $banner_path Ścieżka do bannera
 * @property string|null $meta_title SEO tytuł
 * @property string|null $meta_description SEO opis
 * @property string|null $meta_keywords SEO słowa kluczowe
 * @property string|null $canonical_url Kanoniczny URL
 * @property string|null $og_title OpenGraph tytuł
 * @property string|null $og_description OpenGraph opis
 * @property string|null $og_image OpenGraph obraz
 * @property array|null $visual_settings Ustawienia wizualne
 * @property array|null $visibility_settings Ustawienia widoczności
 * @property array|null $default_values Domyślne wartości produktów
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * 
 * @property-read \App\Models\Category|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Category[] $children  
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Product[] $products
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Category[] $ancestors
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Category[] $descendants
 * @property-read array $breadcrumb
 * @property-read string $fullName
 * @property-read bool $isRoot
 * @property-read bool $isLeaf
 * @property-read string $url
 * @property-read int $productCount
 * @property-read int $totalProductCount
 * 
 * @method static \Illuminate\Database\Eloquent\Builder active()
 * @method static \Illuminate\Database\Eloquent\Builder rootCategories()  
 * @method static \Illuminate\Database\Eloquent\Builder byLevel(int $level)
 * @method static \Illuminate\Database\Eloquent\Builder withProductCounts()
 * @method static \Illuminate\Database\Eloquent\Builder treeOrder()
 * @method static \Illuminate\Database\Eloquent\Builder descendants(int $categoryId)
 * @method static \Illuminate\Database\Eloquent\Builder ancestors(int $categoryId)
 * 
 * @package App\Models
 * @version 1.0
 * @since FAZA A - Core Models Implementation
 */
class Category extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Maximum category tree depth (business rule)
     * 5 poziomów: Kategoria główna -> Podkategoria -> ... -> Kategoria4
     */
    public const MAX_LEVEL = 4;

    /**
     * The attributes that are mass assignable.
     * 
     * Security: Mass assignment protection z business validation
     *
     * @var array<string>
     */
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'short_description',
        'sort_order',
        'is_active',
        'is_featured',
        'icon',
        'icon_path',
        'banner_path',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image',
        'visual_settings',
        'visibility_settings',
        'default_values',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Get the attributes that should be cast.
     * 
     * Performance: Optimized casting dla tree operations
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'level' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'visual_settings' => 'array',
            'visibility_settings' => 'array',
            'default_values' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     * 
     * Business Logic: Auto-generation slug, path, level przy crud operations
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($category) {
            $category->setLevelAndPath();
            if (empty($category->slug)) {
                $category->slug = $category->generateUniqueSlug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('parent_id')) {
                $category->setLevelAndPath();
            }
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = $category->generateUniqueSlug($category->name);
            }
        });

        static::updated(function ($category) {
            if ($category->isDirty('parent_id') || $category->isDirty('path')) {
                $category->updateChildrenPaths();
            }
        });

        static::deleting(function ($category) {
            // Soft delete all descendants when parent is deleted
            // descendants is an Attribute accessor returning Collection, not a query builder
            // So we must iterate and delete each descendant individually
            $category->descendants->each->delete();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS - Tree Structure Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Parent category relationship (many:1)
     * 
     * Performance: Index na parent_id dla fast tree traversal
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id', 'id');
    }

    /**
     * Children categories relationship (1:many)
     * 
     * Performance: Sorted by sort_order dla consistent tree display
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id', 'id')
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('name', 'asc');
    }

    /**
     * Products in this category relationship (many:many)
     * 
     * Business Logic: Many-to-many z pivot metadatami
     * Performance: Eager loading ready dla product listings
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_categories')
                    ->withPivot(['is_primary', 'sort_order'])
                    ->withTimestamps()
                    ->orderBy('pivot_sort_order', 'asc');
    }

    /**
     * Products where this is primary category
     * 
     * Business Logic: Primary category dla PrestaShop export logic
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function primaryProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_categories')
                    ->withPivot(['is_primary', 'sort_order'])
                    ->wherePivot('is_primary', true);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS - Tree Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get all ancestor categories
     * 
     * Performance: Path-based query dla fast ancestor retrieval
     * Returns: Collection ordered from root to direct parent
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function ancestors(): Attribute
    {
        return Attribute::make(
            get: function (): Collection {
                if (!$this->path) {
                    return collect();
                }

                $ancestorIds = array_filter(explode('/', trim($this->path, '/')));
                
                if (empty($ancestorIds)) {
                    return collect();
                }

                return static::whereIn('id', $ancestorIds)
                            ->orderByRaw('FIELD(id, ' . implode(',', $ancestorIds) . ')')
                            ->get();
            }
        );
    }

    /**
     * Get all descendant categories
     * 
     * Performance: Path LIKE query dla all descendants
     * Returns: Collection of all children, grandchildren, etc.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function descendants(): Attribute
    {
        return Attribute::make(
            get: function (): Collection {
                if (!$this->id) {
                    return collect();
                }

                $searchPath = $this->path ? $this->path . '/' . $this->id : '/' . $this->id;
                
                return static::where('path', 'LIKE', $searchPath . '%')
                            ->where('id', '!=', $this->id)
                            ->orderBy('level')
                            ->orderBy('sort_order')
                            ->get();
            }
        );
    }

    /**
     * Get breadcrumb array for navigation
     * 
     * Business Logic: SEO-friendly breadcrumb dla category pages
     * Performance: Uses ancestors relation
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function breadcrumb(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $breadcrumb = [];
                
                // Add ancestors
                foreach ($this->ancestors as $ancestor) {
                    $breadcrumb[] = [
                        'id' => $ancestor->id,
                        'name' => $ancestor->name,
                        'slug' => $ancestor->slug,
                        'url' => $ancestor->url,
                    ];
                }

                // Add current category
                $breadcrumb[] = [
                    'id' => $this->id,
                    'name' => $this->name,
                    'slug' => $this->slug,
                    'url' => $this->url,
                ];

                return $breadcrumb;
            }
        );
    }

    /**
     * Get full category name with ancestors
     *
     * Business Logic: Hierarchical name dla admin interfaces
     * Format: "Parent > Child > Current"
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function fullName(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $names = $this->ancestors->pluck('name')->toArray();
                $names[] = $this->name;

                return implode(' > ', $names);
            }
        );
    }

    /**
     * Get full category path with ancestors (alias for fullName)
     *
     * ADDED 2025-10-13: Alias method for CategoryConflictModal compatibility
     * Format: "Parent > Child > Current"
     *
     * @return string
     */
    public function getFullPath(): string
    {
        $names = $this->ancestors->pluck('name')->toArray();
        $names[] = $this->name;

        return implode(' > ', $names);
    }

    /**
     * Check if category is root level
     * 
     * Performance: Simple null check
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function isRoot(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => is_null($this->parent_id)
        );
    }

    /**
     * Check if category is leaf (has no children)
     * 
     * Performance: Relationship count check
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function isLeaf(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->children()->count() === 0
        );
    }

    /**
     * Get category URL for frontend
     * 
     * Business Logic: SEO-friendly URLs dla category pages
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function url(): Attribute
    {
        return Attribute::make(
            get: fn (): string => route('categories.show', ['category' => $this->slug ?? $this->id])
        );
    }

    /**
     * Get direct product count
     * 
     * Performance: Relationship count dla category statistics
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function productCount(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->products()->count()
        );
    }

    /**
     * Get total product count including descendants
     * 
     * Performance: Agregacja z wszystkich potomnych kategorii
     * Business Logic: Total dla category tree statistics
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function totalProductCount(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                $total = $this->productCount;
                
                foreach ($this->descendants as $descendant) {
                    $total += $descendant->productCount;
                }
                
                return $total;
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES - Tree Query Optimization
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Active categories only
     *
     * Performance: Most common filter dla public interfaces
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Featured categories only
     *
     * Performance: Index na is_featured dla featured category queries
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Root categories (top level)
     * 
     * Performance: Index na parent_id dla root category queries  
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRootCategories(Builder $query): Builder
    {
        return $query->whereNull('parent_id')
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('name', 'asc');
    }

    /**
     * Scope: Categories at specific level
     * 
     * Performance: Level index dla efficient filtering
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $level
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByLevel(Builder $query, int $level): Builder
    {
        return $query->where('level', $level);
    }

    /**
     * Scope: Categories with product counts
     * 
     * Performance: Eager loading z count queries
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithProductCounts(Builder $query): Builder
    {
        return $query->withCount(['products', 'primaryProducts']);
    }

    /**
     * Scope: Tree order (hierarchical sorting)
     * 
     * Performance: Optimized tree display ordering
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTreeOrder(Builder $query): Builder
    {
        return $query->orderBy('level', 'asc')
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('name', 'asc');
    }

    /**
     * Scope: All descendants of given category
     * 
     * Performance: Path LIKE query dla tree filtering
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $categoryId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDescendants(Builder $query, int $categoryId): Builder
    {
        $category = static::find($categoryId);
        
        if (!$category) {
            return $query->whereRaw('1 = 0'); // Empty result
        }

        $searchPath = $category->path ? $category->path . '/' . $category->id : '/' . $category->id;
        
        return $query->where('path', 'LIKE', $searchPath . '%')
                    ->where('id', '!=', $categoryId);
    }

    /**
     * Scope: All ancestors of given category
     * 
     * Performance: Path parsing dla ancestor queries
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $categoryId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAncestors(Builder $query, int $categoryId): Builder
    {
        $category = static::find($categoryId);
        
        if (!$category || !$category->path) {
            return $query->whereRaw('1 = 0'); // Empty result
        }

        $ancestorIds = array_filter(explode('/', trim($category->path, '/')));
        
        if (empty($ancestorIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('id', $ancestorIds);
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS LOGIC METHODS - Tree Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Set level and path based on parent
     * 
     * Business Logic: Auto-calculation dla tree integrity
     * Performance: Single query dla path generation
     */
    private function setLevelAndPath(): void
    {
        if ($this->parent_id) {
            $parent = static::find($this->parent_id);
            
            if ($parent) {
                $this->level = $parent->level + 1;
                $this->path = ($parent->path ?: '') . '/' . $parent->id;
            } else {
                throw new \InvalidArgumentException('Parent category not found');
            }
        } else {
            $this->level = 0;
            $this->path = null;
        }

        // Business rule validation
        if ($this->level > self::MAX_LEVEL) {
            throw new \InvalidArgumentException('Maximum category depth exceeded');
        }
    }

    /**
     * Update paths for all children after parent change
     * 
     * Business Logic: Cascade path updates dla tree consistency
     * Performance: Bulk update operations
     */
    private function updateChildrenPaths(): void
    {
        $children = $this->children()->get();
        
        foreach ($children as $child) {
            $child->setLevelAndPath();
            $child->save();
        }
    }

    /**
     * Generate unique slug for category
     * 
     * Business Logic: SEO-friendly URLs z uniqueness check
     *
     * @param string $name
     * @return string
     */
    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (static::where('slug', $slug)->where('id', '!=', $this->id ?? 0)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Move category to new parent
     * 
     * Business Logic: Safe tree restructuring z validation
     * Performance: Optimized dla tree operations
     *
     * @param int|null $newParentId
     * @return bool
     */
    public function moveTo(?int $newParentId): bool
    {
        // Prevent circular references
        if ($newParentId && $this->isAncestorOf($newParentId)) {
            throw new \InvalidArgumentException('Cannot move category to its descendant');
        }

        // Prevent exceeding max depth
        if ($newParentId) {
            $newParent = static::find($newParentId);
            if (!$newParent) {
                throw new \InvalidArgumentException('New parent category not found');
            }

            $maxDescendantLevel = $this->getMaxDescendantLevel();
            $wouldBeLevel = $newParent->level + 1;
            $finalLevel = $wouldBeLevel + $maxDescendantLevel;

            if ($finalLevel > self::MAX_LEVEL) {
                throw new \InvalidArgumentException('Move would exceed maximum tree depth');
            }
        }

        $this->parent_id = $newParentId;
        return $this->save();
    }

    /**
     * Check if this category is ancestor of given category
     * 
     * Performance: Path-based comparison dla circular reference prevention
     *
     * @param int $categoryId
     * @return bool
     */
    private function isAncestorOf(int $categoryId): bool
    {
        $category = static::find($categoryId);
        
        if (!$category || !$category->path) {
            return false;
        }

        $ancestorIds = array_filter(explode('/', trim($category->path, '/')));
        
        return in_array($this->id, $ancestorIds);
    }

    /**
     * Get maximum level among descendants
     * 
     * Performance: Single query dla depth calculation
     *
     * @return int
     */
    private function getMaxDescendantLevel(): int
    {
        if ($this->descendants->isEmpty()) {
            return 0;
        }

        return $this->descendants->max('level') - $this->level;
    }

    /**
     * Get category tree for select options
     * 
     * Business Logic: Formatted array dla UI dropdowns
     * Performance: Single query z proper ordering
     *
     * @param bool $activeOnly
     * @return array
     */
    public static function getTreeOptions(bool $activeOnly = true): array
    {
        $query = static::treeOrder();
        
        if ($activeOnly) {
            $query->active();
        }

        $categories = $query->get();
        $options = [];

        foreach ($categories as $category) {
            $prefix = str_repeat('— ', $category->level);
            $options[$category->id] = $prefix . $category->name;
        }

        return $options;
    }

    /**
     * Validate business rules for category
     * 
     * Business Logic: Enterprise validation rules
     *
     * @return array Validation errors
     */
    public function validateBusinessRules(): array
    {
        $errors = [];

        // Level validation
        if ($this->level > self::MAX_LEVEL) {
            $errors[] = 'Category level cannot exceed ' . self::MAX_LEVEL;
        }

        // Circular reference validation
        if ($this->parent_id && $this->parent_id === $this->id) {
            $errors[] = 'Category cannot be its own parent';
        }

        // Name validation
        if (empty(trim($this->name))) {
            $errors[] = 'Category name is required';
        }

        return $errors;
    }

    /*
    |--------------------------------------------------------------------------
    | MODEL BINDING & ROUTING
    |--------------------------------------------------------------------------
    */

    /**
     * Get the route key for the model.
     * 
     * Performance: Route model binding na slug dla SEO URLs
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Retrieve the model for a bound value.
     *
     * Performance: Fallback to ID jeśli slug nie istnieje
     *
     * @param mixed $value
     * @param string|null $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('slug', $value)->first()
            ?? $this->where('id', $value)->first();
    }

    /*
    |--------------------------------------------------------------------------
    | ETAP_07 FAZA 2A.4: PRESTASHOP IMPORT/EXPORT MODEL EXTENSIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Get PrestaShop mappings for this category
     *
     * ETAP_07 Integration: Track category mappings per PrestaShop shop
     * Performance: Eager loading ready with mapping_type filter
     * Business Logic: Category ID mapping between PPM and PrestaShop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prestashopMappings(): HasMany
    {
        return $this->hasMany(ShopMapping::class, 'ppm_value')
            ->where('mapping_type', 'category');
    }

    /**
     * Get PrestaShop category ID for specific shop
     *
     * Usage: $psCategoryId = $category->getPrestashopCategoryId($shop);
     * Returns: PrestaShop category ID or null if not mapped
     * Performance: Single query with shop_id and mapping_type filter
     *
     * @param \App\Models\PrestaShopShop $shop
     * @return int|null
     */
    public function getPrestashopCategoryId(PrestaShopShop $shop): ?int
    {
        $mapping = $this->prestashopMappings()
            ->where('shop_id', $shop->id)
            ->first();

        return $mapping?->prestashop_id;
    }

    /**
     * Import entire category tree from PrestaShop shop
     *
     * Usage: $categories = Category::importTreeFromPrestaShop($shop);
     * Business Logic: Static factory method for bulk category imports
     * Performance: Delegates to PrestaShopImportService
     * Integration: ETAP_07 FAZA 2A.1 reverse transformation
     *
     * @param \App\Models\PrestaShopShop $shop
     * @param int|null $rootCategoryId Optional root category to start from
     * @return \Illuminate\Support\Collection
     */
    public static function importTreeFromPrestaShop(
        PrestaShopShop $shop,
        ?int $rootCategoryId = null
    ): Collection
    {
        $importService = app(PrestaShopImportService::class);
        $categories = $importService->importCategoryTreeFromPrestaShop($shop, $rootCategoryId);

        return collect($categories);
    }

    /**
     * Sync this category to PrestaShop shop (create or update mapping)
     *
     * Usage: $mapping = $category->syncWithPrestaShop($shop, 5);
     * Business Logic: Create/update ShopMapping for category
     * Performance: UpdateOrCreate pattern for atomic operation
     * Integration: Ready for export workflow
     *
     * @param \App\Models\PrestaShopShop $shop
     * @param int $prestashopCategoryId
     * @return \App\Models\ShopMapping
     */
    public function syncWithPrestaShop(PrestaShopShop $shop, int $prestashopCategoryId): ShopMapping
    {
        return ShopMapping::updateOrCreate(
            [
                'shop_id' => $shop->id,
                'mapping_type' => 'category',
                'ppm_value' => $this->id
            ],
            [
                'prestashop_id' => $prestashopCategoryId,
                'prestashop_value' => $this->name,
                'is_active' => true
            ]
        );
    }

    /**
     * Scope: Categories mapped to specific PrestaShop shop
     *
     * Usage: $categories = Category::mappedToPrestaShop($shop->id)->get();
     * Business Logic: Filter categories by mapping existence
     * Performance: Optimized subquery with mapping_type and is_active filter
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $shopId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMappedToPrestaShop(Builder $query, int $shopId): Builder
    {
        return $query->whereHas('prestashopMappings', function($q) use ($shopId) {
            $q->where('shop_id', $shopId)
              ->where('is_active', true);
        });
    }
}