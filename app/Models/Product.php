<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

// ETAP_05a SEKCJA 0 - Product.php Refactoring ✅
use App\Models\Concerns\Product\HasPricing;
use App\Models\Concerns\Product\HasStock;
use App\Models\Concerns\Product\HasCategories;
use App\Models\Concerns\Product\HasVariants;
use App\Models\Concerns\Product\HasFeatures;
use App\Models\Concerns\Product\HasCompatibility;
use App\Models\Concerns\Product\HasMultiStore;
use App\Models\Concerns\Product\HasSyncStatus;

/**
 * Product Model - Core entity systemu PIM PPM-CC-Laravel
 *
 * Centralny model produktu obsługujący:
 * - **SKU jako primary business identifier** (SKU-first architecture)
 * - Hierarchię kategorii (5 poziomów) z per-shop support
 * - System wariantów (master-variant pattern) - ETAP_05a
 * - Grupy cenowe (8 grup - HasPricing Trait)
 * - Stany magazynowe (multi-warehouse - HasStock Trait)
 * - Galeria zdjęć (do 20 zdjęć - HasFeatures Trait)
 * - Vehicle compatibility (HasCompatibility Trait - ETAP_05a)
 * - Multi-store sync (HasMultiStore Trait)
 * - Integration sync (HasSyncStatus Trait)
 * - SEO optimization i soft deletes
 *
 * Architecture: SKU-FIRST PATTERN (ref: _DOCS/SKU_ARCHITECTURE_GUIDE.md)
 * Performance: Zaprojektowane dla 100K+ produktów z optymalizacją <100ms
 * Refactored: 2025-10-17 (2182 lines → ~250 lines via 8 Traits)
 *
 * @property int $id
 * @property string $sku Primary business identifier (ALWAYS use SKU for lookups!)
 * @property string|null $slug URL-friendly slug
 * @property string $name Nazwa produktu
 * @property string|null $short_description Max 800 znaków
 * @property string|null $long_description Max 21844 znaków
 * @property int $product_type_id ID typu produktu
 * @property \App\Models\ProductType $productType Typ produktu (relation)
 * @property string|null $manufacturer Producent
 * @property string|null $supplier_code Kod dostawcy
 * @property float|null $weight Waga w kg
 * @property float|null $height Wysokość w cm
 * @property float|null $width Szerokość w cm
 * @property float|null $length Długość w cm
 * @property string|null $ean Kod EAN
 * @property float $tax_rate Stawka VAT %
 * @property bool $is_active Status aktywności
 * @property bool $is_variant_master Czy posiada warianty
 * @property bool $is_featured Czy produkt jest wyróżniony
 * @property int $sort_order Kolejność sortowania
 * @property string|null $meta_title SEO tytuł
 * @property string|null $meta_description SEO opis
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder active()
 * @method static \Illuminate\Database\Eloquent\Builder withVariants()
 * @method static \Illuminate\Database\Eloquent\Builder byType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder masters()
 * @method static \Illuminate\Database\Eloquent\Builder search(string $term)
 * @method static \Illuminate\Database\Eloquent\Builder withFullDetails()
 * @method static \Illuminate\Database\Eloquent\Builder importedFrom(int $shopId)
 * @method static \Illuminate\Database\Eloquent\Builder currentlyAvailable()
 *
 * @package App\Models
 * @version 2.0 (Refactored)
 * @since FAZA A - Core Models Implementation
 */
class Product extends Model
{
    use HasFactory, SoftDeletes;

    // ETAP_05a SEKCJA 0 - Refactoring Traits ✅
    use HasPricing;          // Pricing system (145 lines)
    use HasStock;            // Stock management (414 lines)
    use HasCategories;       // Category relationships (231 lines)
    use HasVariants;         // Variants system stub (91 lines - ETAP_05a ready)
    use HasFeatures;         // Features/Media/Attributes (267 lines)
    use HasCompatibility;    // Vehicle compatibility stub (117 lines - ETAP_05a ready)
    use HasMultiStore;       // Multi-store sync (229 lines)
    use HasSyncStatus;       // Integration sync (192 lines)

    /**
     * The attributes that are mass assignable.
     *
     * Security: Określa które pola mogą być mass-assigned
     * Performance: Ogranicza do niezbędnych pól dla bulk operations
     *
     * @var array<string>
     */
    protected $fillable = [
        'sku',
        'name',
        'slug',
        'short_description',
        'long_description',
        'product_type_id',
        'manufacturer_id',
        'manufacturer',
        'supplier_code',
        'weight',
        'height',
        'width',
        'length',
        'ean',
        'tax_rate',
        'is_active',
        'is_variant_master',
        'is_featured',
        'available_from',
        'available_to',
        'sort_order',
        'meta_title',
        'meta_description',
        // Subiekt GT Extended Fields (ETAP_08 FAZA 3.1-3.2)
        'application',
        'cn_code',
        'defect_symbol',
        'material',
        'shop_internet',
        'split_payment',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'available_from' => 'datetime',
        'available_to' => 'datetime',
        'is_active' => 'boolean',
        'is_variant_master' => 'boolean',
        'weight' => 'decimal:3',
        'height' => 'decimal:2',
        'width' => 'decimal:2',
        'length' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        // Subiekt GT Extended Fields (ETAP_08 FAZA 3.1-3.2)
        'shop_internet' => 'boolean',
        'split_payment' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * Security: Ukrywa wrażliwe dane w JSON responses
     *
     * @var array<string>
     */
    protected $hidden = [
        'deleted_at', // Soft delete timestamp nie powinien być widoczny w API
    ];

    /**
     * Get the attributes that should be cast.
     *
     * Performance: Laravel 12.x optimized casting
     * Type Safety: Strong typing dla business logic
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight' => 'decimal:3',
            'height' => 'decimal:2',
            'width' => 'decimal:2',
            'length' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'is_variant_master' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     *
     * Business Logic: Auto-generation slug przy tworzeniu/aktualizacji
     * Performance: Event-driven slug generation
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = $product->generateUniqueSlug($product->name);
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty('name') && empty($product->slug)) {
                $product->slug = $product->generateUniqueSlug($product->name);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | CORE RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Product type relationship (many:1)
     *
     * Business Logic: Każdy produkt ma przypisany typ (edytowalny)
     * Performance: Eager loading ready z proper indexing
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    /**
     * Manufacturer relationship (Marka produktu)
     *
     * FIX 2025-12-15: Added for automatic manufacturer import from PrestaShop
     */
    public function manufacturerRelation(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id');
    }

    /*
    |--------------------------------------------------------------------------
    | CORE ACCESSORS & MUTATORS
    |--------------------------------------------------------------------------
    */

    /**
     * Get/Set SKU with normalization (SKU-FIRST architecture)
     *
     * Business Logic: SKU normalizacja (trim, uppercase, format validation)
     * Security: Input sanitization dla primary business identifier
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function sku(): Attribute
    {
        return Attribute::make(
            get: fn (string $value): string => strtoupper(trim($value)),
            set: fn (string $value): string => strtoupper(trim($value)),
        );
    }

    /**
     * Get product URL for frontend
     *
     * Business Logic: SEO-friendly URLs dla product pages
     * Performance: Route model binding ready
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function url(): Attribute
    {
        return Attribute::make(
            get: fn (): string => route('products.show', ['product' => $this->slug ?? $this->id])
        );
    }

    /**
     * Get product dimensions as array
     *
     * Business Logic: Wymiary produktu dla shipping calculations
     * Performance: Computed attribute dla logistics
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function dimensions(): Attribute
    {
        return Attribute::make(
            get: fn (): array => [
                'length' => $this->length,
                'width' => $this->width,
                'height' => $this->height,
                'weight' => $this->weight,
            ]
        );
    }

    /**
     * Get display name for UI
     *
     * Business Logic: Inteligentna nazwa dla UI (z manufacturer jeśli dostępny)
     * Performance: Computed name dla listings
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function displayName(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->manufacturer) {
                    return "{$this->manufacturer} {$this->name}";
                }
                return $this->name;
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TAX RATE HELPERS (FAZA 5.3 - 2025-11-14)
    |--------------------------------------------------------------------------
    */

    /**
     * Get effective tax rate for a shop (with override support)
     *
     * ETAP_07 FAZA 5.3 - Tax Rules UI Enhancement
     *
     * Priority:
     * 1. ProductShopData->tax_rate_override (shop-specific)
     * 2. Product->tax_rate (global default)
     * 3. 23.00 (Poland standard VAT as fallback)
     *
     * @param int|null $shopId Shop ID for override lookup
     * @return float Effective tax rate
     */
    public function getTaxRateForShop(?int $shopId = null): float
    {
        // No shop specified - return global default
        if ($shopId === null) {
            return $this->tax_rate ?? 23.00;
        }

        // Load shop data with override
        $shopData = $this->shopData()->where('shop_id', $shopId)->first();

        if ($shopData) {
            return $shopData->getEffectiveTaxRate();
        }

        // Fallback to global default if no shop data exists
        return $this->tax_rate ?? 23.00;
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES - Business Logic Filters
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Active products only
     *
     * Performance: Most common filter, indexed column
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Products with variants (ETAP_05a FAZA 2 ✅ UPDATED)
     *
     * Performance: Join optimization dla variant masters
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithVariants(Builder $query): Builder
    {
        return $query->where('has_variants', true)
                    ->has('variants');
    }

    /**
     * Scope: Products without variants (ETAP_05a FAZA 2 ✅ NEW)
     *
     * Performance: Filter dla simple products
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithoutVariants(Builder $query): Builder
    {
        return $query->where('has_variants', false);
    }

    /**
     * Scope: Filter by product type
     *
     * Performance: Enum index optimization
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType(Builder $query, string $typeSlugOrId): Builder
    {
        // Support both slug and ID
        if (is_numeric($typeSlugOrId)) {
            return $query->where('product_type_id', $typeSlugOrId);
        }

        return $query->whereHas('productType', function (Builder $subquery) use ($typeSlugOrId) {
            $subquery->where('slug', $typeSlugOrId);
        });
    }

    /**
     * Scope: Master products only (not variants)
     *
     * Performance: Filter dla main product listings
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMasters(Builder $query): Builder
    {
        return $query->whereNull('deleted_at'); // Soft delete aware
    }

    /**
     * Scope: Search products by term (SKU-FIRST pattern)
     *
     * Performance: Full-text search ready (MySQL FULLTEXT indexes)
     * Security: SQL injection safe with bindings
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $searchTerm
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch(Builder $query, string $searchTerm): Builder
    {
        $searchTerm = trim($searchTerm);

        if (empty($searchTerm)) {
            return $query;
        }

        return $query->where(function ($query) use ($searchTerm) {
            // Exact SKU match (highest priority - SKU-FIRST!)
            $query->where('sku', 'LIKE', $searchTerm . '%')
                  // Supplier code match
                  ->orWhere('supplier_code', 'LIKE', '%' . $searchTerm . '%')
                  // Product name match
                  ->orWhere('name', 'LIKE', '%' . $searchTerm . '%')
                  // Description search
                  ->orWhere('short_description', 'LIKE', '%' . $searchTerm . '%')
                  // Manufacturer match
                  ->orWhere('manufacturer', 'LIKE', '%' . $searchTerm . '%');
        });
    }

    /**
     * Scope: Eager load all relationships for detailed views
     *
     * Performance: Optimized eager loading dla product details
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithFullDetails(Builder $query): Builder
    {
        return $query->with([
            'categories:id,name,slug,path',
            'variants:id,product_id,variant_name,variant_sku,is_active',
            // FAZA B ✅ IMPLEMENTED - Pricing & Inventory
            'validPrices:id,product_id,price_group_id,price_net,price_gross,currency,is_promotion',
            'validPrices.priceGroup:id,name,code',
            'activeStock:id,product_id,warehouse_id,quantity,reserved_quantity,available_quantity,delivery_status',
            'activeStock.warehouse:id,name,code',
        ]);
    }

    /**
     * Scope to filter products that are currently available
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCurrentlyAvailable(Builder $query): Builder
    {
        $now = now();

        return $query->where(function ($query) use ($now) {
            $query->where(function ($q) use ($now) {
                // No schedule set - always available
                $q->whereNull('available_from')
                  ->whereNull('available_to');
            })->orWhere(function ($q) use ($now) {
                // Only available_from set - past that date
                $q->whereNotNull('available_from')
                  ->whereNull('available_to')
                  ->where('available_from', '<=', $now);
            })->orWhere(function ($q) use ($now) {
                // Only available_to set - before that date
                $q->whereNull('available_from')
                  ->whereNotNull('available_to')
                  ->where('available_to', '>=', $now);
            })->orWhere(function ($q) use ($now) {
                // Both dates set - within range
                $q->whereNotNull('available_from')
                  ->whereNotNull('available_to')
                  ->where('available_from', '<=', $now)
                  ->where('available_to', '>=', $now);
            });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | CORE BUSINESS METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Generate unique slug for product
     *
     * Business Logic: SEO-friendly URLs z uniqueness check
     * Performance: Optimized dla mass operations
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
     * Check if product can be deleted
     *
     * Business Logic: Prevent deletion with active dependencies
     * Security: Business rule enforcement
     *
     * @return bool
     */
    public function canDelete(): bool
    {
        // Cannot delete products with active variants
        if ($this->variants()->where('is_active', true)->exists()) {
            return false;
        }

        // Cannot delete if has active prices
        if ($this->validPrices()->exists()) {
            return false;
        }

        // Cannot delete if has stock in any warehouse
        if ($this->activeStock()->where('quantity', '>', 0)->exists()) {
            return false;
        }

        // Cannot delete if has reserved stock
        if ($this->activeStock()->where('reserved_quantity', '>', 0)->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Validate business rules
     *
     * Business Logic: Enterprise validation rules
     * Performance: Early validation dla data integrity
     *
     * @return array Validation errors
     */
    public function validateBusinessRules(): array
    {
        $errors = [];

        // SKU format validation
        if (!preg_match('/^[A-Z0-9\-_]+$/', $this->sku)) {
            $errors[] = 'SKU must contain only uppercase letters, numbers, hyphens and underscores';
        }

        // Description length validation
        if ($this->short_description && strlen($this->short_description) > 800) {
            $errors[] = 'Short description cannot exceed 800 characters';
        }

        if ($this->long_description && strlen($this->long_description) > 21844) {
            $errors[] = 'Long description cannot exceed 21844 characters';
        }

        // Category count validation
        if ($this->categories()->count() > 10) {
            $errors[] = 'Product cannot be assigned to more than 10 categories';
        }

        // Variant master validation
        if ($this->is_variant_master && $this->variants()->count() === 0) {
            $errors[] = 'Variant master must have at least one variant';
        }

        return $errors;
    }

    /**
     * Check if product is currently available based on publishing schedule
     *
     * @return bool
     */
    public function isCurrentlyAvailable(): bool
    {
        $now = now();

        // If both dates are null, product is always available (no schedule set)
        if (!$this->available_from && !$this->available_to) {
            return true;
        }

        // If only available_from is set, check if we're past that date
        if ($this->available_from && !$this->available_to) {
            return $now->gte($this->available_from);
        }

        // If only available_to is set, check if we're before that date
        if (!$this->available_from && $this->available_to) {
            return $now->lte($this->available_to);
        }

        // If both dates are set, check if we're within the range
        return $now->gte($this->available_from) && $now->lte($this->available_to);
    }

    /**
     * Get publishing status with details
     *
     * @return array
     */
    public function getPublishingStatus(): array
    {
        $now = now();
        $status = [
            'is_available' => $this->isCurrentlyAvailable(),
            'status_text' => '',
            'available_from' => $this->available_from,
            'available_to' => $this->available_to,
        ];

        if (!$this->available_from && !$this->available_to) {
            $status['status_text'] = 'Zawsze dostępny';
        } elseif ($this->available_from && $now->lt($this->available_from)) {
            $status['status_text'] = 'Będzie dostępny od ' . $this->available_from->format('d.m.Y H:i');
        } elseif ($this->available_to && $now->gt($this->available_to)) {
            $status['status_text'] = 'Już niedostępny (do ' . $this->available_to->format('d.m.Y H:i') . ')';
        } elseif ($this->isCurrentlyAvailable()) {
            if ($this->available_to) {
                $status['status_text'] = 'Dostępny do ' . $this->available_to->format('d.m.Y H:i');
            } else {
                $status['status_text'] = 'Dostępny od ' . $this->available_from->format('d.m.Y H:i');
            }
        } else {
            $status['status_text'] = 'Niedostępny';
        }

        return $status;
    }

    /*
    |--------------------------------------------------------------------------
    | ROUTE MODEL BINDING
    |--------------------------------------------------------------------------
    */

    /**
     * Get the route key for the model.
     *
     * Performance: Route model binding na slug dla SEO URLs, fallback to ID
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the value of the model's route key.
     *
     * Fallback: Return ID if slug is null/empty
     *
     * @return mixed
     */
    public function getRouteKey()
    {
        return $this->slug ?: $this->id;
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
        // Try slug first, then ID as fallback
        return $this->where('slug', $value)->first()
            ?? $this->where('id', $value)->first();
    }

    /**
     * Find product by SKU (SKU-FIRST architecture)
     *
     * Business Logic: SKU jako PRIMARY identifier (zgodnie z SKU_ARCHITECTURE_GUIDE.md)
     * Performance: Single query optimization
     *
     * @param string $sku
     * @return self|null
     */
    public static function findBySku(string $sku): ?self
    {
        return static::where('sku', strtoupper(trim($sku)))->first();
    }

    /*
    |--------------------------------------------------------------------------
    | ERP INTEGRATION RELATIONSHIPS (ETAP_08.3)
    |--------------------------------------------------------------------------
    */

    /**
     * Product ERP data relationship (1:many) - ETAP_08.3: ERP Tab (Shop-Tab Pattern)
     *
     * Business Logic: Kazdy produkt moze miec rozne dane per system ERP
     * Performance: Eager loading ready z proper indexing
     * Multi-ERP: Connection-specific names, mappings, sync status
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function erpData(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\ProductErpData::class, 'product_id', 'id')
                    ->orderBy('erp_connection_id', 'asc');
    }

    /**
     * Active ERP data only (sync enabled)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activeErpData(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->erpData()
                    ->where('sync_status', '!=', 'disabled');
    }

    /**
     * ERP data for specific connection
     *
     * @param int $erpConnectionId
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dataForErpConnection(int $erpConnectionId): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->erpData()
                    ->where('erp_connection_id', $erpConnectionId);
    }

    /**
     * Get or create ERP data for specific connection
     *
     * @param int $erpConnectionId
     * @return \App\Models\ProductErpData
     */
    public function getOrCreateErpData(int $erpConnectionId): \App\Models\ProductErpData
    {
        return $this->erpData()
                    ->where('erp_connection_id', $erpConnectionId)
                    ->firstOrCreate([
                        'product_id' => $this->id,
                        'erp_connection_id' => $erpConnectionId,
                    ]);
    }

    /**
     * Check if product has ERP data for specific connection
     *
     * @param int $erpConnectionId
     * @return bool
     */
    public function hasErpData(int $erpConnectionId): bool
    {
        return $this->erpData()
                    ->where('erp_connection_id', $erpConnectionId)
                    ->exists();
    }

    /**
     * Get ERP sync status for specific connection
     *
     * @param int $erpConnectionId
     * @return string|null
     */
    public function getErpSyncStatus(int $erpConnectionId): ?string
    {
        $data = $this->erpData()
                    ->where('erp_connection_id', $erpConnectionId)
                    ->first();

        return $data?->sync_status;
    }
}
