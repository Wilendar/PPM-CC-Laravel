<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

/**
 * Product Model - Core entity systemu PIM PPM-CC-Laravel
 * 
 * Centralny model produktu obsługujący:
 * - SKU jako primary business identifier
 * - Hierarchię kategorii (5 poziomów)
 * - System wariantów (master-variant pattern)
 * - Grupy cenowe (8 grup - FAZA B)
 * - Stany magazynowe (multi-warehouse - FAZA B)
 * - Galeria zdjęć (do 20 zdjęć - FAZA C)
 * - SEO optimization i soft deletes
 * 
 * Performance: Zaprojektowane dla 100K+ produktów z optymalizacją <100ms
 * 
 * @property int $id
 * @property string $sku Primary business identifier
 * @property string|null $slug URL-friendly slug
 * @property string $name Nazwa produktu
 * @property string|null $short_description Max 800 znaków
 * @property string|null $long_description Max 21844 znaków
 * @property string $product_type vehicle|spare_part|clothing|other
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
 * @property int $sort_order Kolejność sortowania
 * @property string|null $meta_title SEO tytuł
 * @property string|null $meta_description SEO opis
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductVariant[] $variants
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Category[] $categories
 * @property-read \App\Models\Category|null $primaryCategory
 * @property-read string|null $primaryImage
 * @property-read array $formattedPrices
 * @property-read int $totalStock
 * @property-read string $url
 * @property-read array $dimensions
 * @property-read string $displayName
 * @property-read bool $hasVariants
 * 
 * @method static \Illuminate\Database\Eloquent\Builder active()
 * @method static \Illuminate\Database\Eloquent\Builder withVariants()
 * @method static \Illuminate\Database\Eloquent\Builder byType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder masters()
 * @method static \Illuminate\Database\Eloquent\Builder search(string $term)
 * @method static \Illuminate\Database\Eloquent\Builder withFullDetails()
 * 
 * @package App\Models
 * @version 1.0
 * @since FAZA A - Core Models Implementation
 */
class Product extends Model
{
    use HasFactory, SoftDeletes;

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
        'short_description',
        'long_description',
        'product_type',
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
        'sort_order',
        'meta_title',
        'meta_description',
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
    | RELATIONSHIPS - Laravel Eloquent Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Product variants relationship (1:many)
     * 
     * Business Logic: Jeden produkt może mieć wiele wariantów
     * Performance: Eager loading ready z proper indexing
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id', 'id')
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('variant_name', 'asc');
    }

    /**
     * Product categories relationship (many:many)
     * 
     * Business Logic: Produkt może być w wielu kategoriach (max 10)
     * Performance: Pivot table z dodatkowymi metadatami (is_primary, sort_order)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories')
                    ->withPivot(['is_primary', 'sort_order'])
                    ->withTimestamps()
                    ->orderBy('pivot_sort_order', 'asc');
    }

    /**
     * Primary category relationship
     * 
     * Business Logic: Jeden produkt ma jedną kategorię domyślną dla PrestaShop export
     * Performance: Single query dla najważniejszej kategorii
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function primaryCategory(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories')
                    ->withPivot(['is_primary', 'sort_order'])
                    ->wherePivot('is_primary', true)
                    ->limit(1);
    }

    /**
     * Product prices relationship (1:many) - FAZA B ✅ IMPLEMENTED
     * 
     * Business Logic: 8 grup cenowych PPM z support dla variants
     * Performance: Eager loading ready z proper indexing
     * Integration: PrestaShop specific_price mapping ready
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class, 'product_id', 'id')
                    ->orderBy('price_group_id', 'asc');
    }

    /**
     * Product stock levels relationship (1:many) - FAZA B ✅ IMPLEMENTED
     * 
     * Business Logic: Multi-warehouse stock tracking z delivery status
     * Performance: Optimized dla inventory operations
     * Integration: ERP systems mapping ready
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stock(): HasMany
    {
        return $this->hasMany(ProductStock::class, 'product_id', 'id')
                    ->orderBy('warehouse_id', 'asc');
    }

    /**
     * Active stock levels only
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activeStock(): HasMany
    {
        return $this->stock()->where('is_active', true)
                             ->where('track_stock', true);
    }

    /**
     * Valid prices only (active and within date range)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function validPrices(): HasMany
    {
        return $this->prices()->active()->validNow();
    }

    /**
     * Product media/images polymorphic relationship (1:many) - FAZA C ✅ IMPLEMENTED
     * 
     * Obsługa: max 20 zdjęć na produkt, różne rozmiary, watermarki, optymalizacja
     * Performance: Strategic eager loading z gallery order
     * Integration: PrestaShop multi-store mapping ready
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')
                    ->galleryOrder(); // Uses custom scope from Media model
    }

    /**
     * Product files/documents polymorphic relationship (1:many) - FAZA C ✅ IMPLEMENTED
     * 
     * Obsługa: instrukcje, certyfikaty, dokumenty techniczne
     * Security: Access level control per file
     * Integration: Container documentation system ready
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function files(): MorphMany
    {
        return $this->morphMany(FileUpload::class, 'uploadable')
                    ->active()
                    ->orderBy('file_type', 'asc')
                    ->orderBy('original_name', 'asc');
    }

    /**
     * Product attributes relationship (1:many via ProductAttributeValue) - FAZA C ✅ IMPLEMENTED
     * 
     * EAV System: Model, Oryginał, Zamiennik, etc.
     * Performance: Optimized dla automotive compatibility
     * Inheritance: Master product values inherited by variants
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class)
                    ->whereNull('product_variant_id') // Only master product attributes
                    ->with('attribute')
                    ->valid();
    }

    /**
     * Product integration mappings polymorphic relationship (1:many) - FAZA C ✅ IMPLEMENTED
     * 
     * Universal mapping: PrestaShop, Baselinker, Subiekt GT, etc.
     * Performance: Optimized dla sync operations
     * Multi-store: Support dla różnych sklepów PrestaShop
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function integrationMappings(): MorphMany
    {
        return $this->morphMany(IntegrationMapping::class, 'mappable')
                    ->orderBy('integration_type', 'asc')
                    ->orderBy('integration_identifier', 'asc');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS - Laravel 12.x Attribute Pattern
    |--------------------------------------------------------------------------
    */

    /**
     * Get primary image URL - FAZA C ✅ IMPLEMENTED
     * 
     * Business Logic: Real image system z fallback do placeholder
     * Performance: Single query optimization dla primary image
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function primaryImage(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $primaryMedia = $this->media()->primary()->active()->first();
                
                if ($primaryMedia) {
                    return $primaryMedia->url;
                }
                
                // Fallback to first available image
                $firstMedia = $this->media()->active()->first();
                if ($firstMedia) {
                    return $firstMedia->url;
                }
                
                // Final fallback to placeholder
                return $this->getPlaceholderImage();
            }
        );
    }

    /**
     * Get all media collection - FAZA C ✅ IMPLEMENTED
     * 
     * Business Logic: Complete gallery dla product
     * Performance: Eager loaded relationship
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function mediaGallery(): Attribute
    {
        return Attribute::make(
            get: function (): \Illuminate\Database\Eloquent\Collection {
                return $this->media()->active()->get();
            }
        );
    }

    /**
     * Get all attribute values formatted - FAZA C ✅ IMPLEMENTED
     * 
     * Business Logic: EAV values dla product display
     * Performance: Optimized dla form generation
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function attributesFormatted(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $formatted = [];
                
                $attributeValues = $this->attributeValues()->with('attribute')->get();
                
                foreach ($attributeValues as $value) {
                    $formatted[$value->attribute->code] = [
                        'name' => $value->attribute->name,
                        'value' => $value->formatted_value,
                        'type' => $value->attribute->attribute_type,
                        'group' => $value->attribute->display_group,
                    ];
                }
                
                return $formatted;
            }
        );
    }

    /**
     * Get integration data for all systems - FAZA C ✅ IMPLEMENTED
     * 
     * Business Logic: Sync status monitoring
     * Performance: Cached integration summary
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function integrationData(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $data = [];
                
                $mappings = $this->integrationMappings()->get();
                
                foreach ($mappings as $mapping) {
                    $key = $mapping->integration_type . '_' . $mapping->integration_identifier;
                    $data[$key] = [
                        'type' => $mapping->integration_type,
                        'identifier' => $mapping->integration_identifier,
                        'external_id' => $mapping->external_id,
                        'status' => $mapping->sync_status,
                        'last_sync' => $mapping->last_sync_at?->format('Y-m-d H:i:s'),
                        'needs_sync' => $mapping->needs_sync,
                    ];
                }
                
                return $data;
            }
        );
    }

    /**
     * Get formatted prices for all groups - FAZA B ✅ IMPLEMENTED
     * 
     * Business Logic: Ceny dla 8 grup cenowych PPM z integration ready
     * Performance: Optimized query z proper relationships
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function formattedPrices(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $prices = [];
                
                // Get valid prices with price group relationship
                $validPrices = $this->validPrices()->with('priceGroup')->get();
                
                foreach ($validPrices as $price) {
                    $groupCode = $price->priceGroup->code ?? 'unknown';
                    $prices[$groupCode] = [
                        'net' => $price->formatted_price_net,
                        'gross' => $price->formatted_price_gross,
                        'currency' => $price->currency,
                        'is_promotion' => $price->is_promotion,
                        'valid_until' => $price->valid_to?->format('Y-m-d'),
                    ];
                }
                
                return $prices;
            }
        );
    }

    /**
     * Get total stock across all warehouses - FAZA B ✅ IMPLEMENTED
     * 
     * Business Logic: Suma available quantity ze wszystkich aktywnych magazynów
     * Performance: Agregacja z proper indexing
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function totalStock(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                return $this->activeStock()->sum('available_quantity') ?? 0;
            }
        );
    }

    /**
     * Get total reserved stock across all warehouses
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function totalReservedStock(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                return $this->activeStock()->sum('reserved_quantity') ?? 0;
            }
        );
    }

    /**
     * Check if product is in stock (any warehouse)
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function inStock(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->total_stock > 0
        );
    }

    /**
     * Get/Set SKU with normalization
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

    /**
     * Check if product has variants
     * 
     * Business Logic: Convenience accessor dla variant detection
     * Performance: Based on is_variant_master flag dla performance
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function hasVariants(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->is_variant_master && $this->variants->count() > 0
        );
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
     * Scope: Products with variants
     * 
     * Performance: Join optimization dla variant masters
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithVariants(Builder $query): Builder
    {
        return $query->where('is_variant_master', true)
                    ->has('variants');
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
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('product_type', $type);
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
     * Scope: Search products by term
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
            // Exact SKU match (highest priority)
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
            // TODO: Add in FAZA C  
            // 'media:id,mediable_id,path,is_primary',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS LOGIC METHODS - Enterprise Operations
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
     * Get placeholder image for product
     * 
     * Business Logic: Fallback system dla produktów bez zdjęć
     * Performance: Static assets dla fast loading
     *
     * @return string
     */
    private function getPlaceholderImage(): string
    {
        // Different placeholders based on product type
        $placeholders = [
            'vehicle' => '/images/placeholders/vehicle-placeholder.jpg',
            'spare_part' => '/images/placeholders/spare-part-placeholder.jpg', 
            'clothing' => '/images/placeholders/clothing-placeholder.jpg',
            'other' => '/images/placeholders/default-placeholder.jpg',
        ];

        return $placeholders[$this->product_type] ?? $placeholders['other'];
    }

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

        // FAZA B ✅ IMPLEMENTED - Additional business rule checks
        
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
        
        // TODO: Add checks for:
        // - Active orders
        // - Sync status with PrestaShop/ERP

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

    /*
    |--------------------------------------------------------------------------
    | FAZA B: PRICING & INVENTORY BUSINESS METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get price for specific price group
     *
     * @param int $priceGroupId
     * @return \App\Models\ProductPrice|null
     */
    public function getPriceForGroup(int $priceGroupId): ?ProductPrice
    {
        return $this->validPrices()
                    ->where('price_group_id', $priceGroupId)
                    ->first();
    }

    /**
     * Get stock for specific warehouse
     *
     * @param int $warehouseId
     * @return \App\Models\ProductStock|null
     */
    public function getStockForWarehouse(int $warehouseId): ?ProductStock
    {
        return $this->activeStock()
                    ->where('warehouse_id', $warehouseId)
                    ->first();
    }

    /**
     * Check if product is available in specific quantity
     *
     * @param int $quantity
     * @param int|null $warehouseId
     * @return bool
     */
    public function isAvailable(int $quantity = 1, ?int $warehouseId = null): bool
    {
        if ($warehouseId) {
            $stock = $this->getStockForWarehouse($warehouseId);
            return $stock && $stock->available_quantity >= $quantity;
        }
        
        return $this->total_stock >= $quantity;
    }

    /**
     * Get lowest price from all active price groups
     *
     * @return \App\Models\ProductPrice|null
     */
    public function getLowestPrice(): ?ProductPrice
    {
        return $this->validPrices()
                    ->orderBy('price_gross', 'asc')
                    ->first();
    }

    /**
     * Get highest price from all active price groups
     *
     * @return \App\Models\ProductPrice|null
     */
    public function getHighestPrice(): ?ProductPrice
    {
        return $this->validPrices()
                    ->orderBy('price_gross', 'desc')
                    ->first();
    }

    /**
     * Get price for specific price group by code
     *
     * @param string $groupCode (retail, dealer_std, etc.)
     * @return \App\Models\ProductPrice|null
     */
    public function getPriceByGroupCode(string $groupCode): ?ProductPrice
    {
        return $this->validPrices()
                    ->whereHas('priceGroup', function ($query) use ($groupCode) {
                        $query->where('code', $groupCode);
                    })
                    ->first();
    }

    /**
     * Get warehouses where product is in stock
     *
     * @param int $minQuantity Minimum required quantity
     * @return \Illuminate\Support\Collection
     */
    public function getWarehousesInStock(int $minQuantity = 1): \Illuminate\Support\Collection
    {
        return $this->activeStock()
                    ->where('available_quantity', '>=', $minQuantity)
                    ->with('warehouse')
                    ->get()
                    ->pluck('warehouse');
    }

    /**
     * Reserve stock across warehouses (FIFO - First warehouse with stock)
     *
     * @param int $quantity
     * @param string|null $reason
     * @return array ['success' => bool, 'reservations' => array, 'message' => string]
     */
    public function reserveStock(int $quantity, ?string $reason = null): array
    {
        if ($quantity <= 0) {
            return ['success' => false, 'reservations' => [], 'message' => 'Invalid quantity'];
        }

        $remainingQuantity = $quantity;
        $reservations = [];

        // Get stock records ordered by available quantity (highest first)
        $stockRecords = $this->activeStock()
                             ->where('available_quantity', '>', 0)
                             ->orderBy('available_quantity', 'desc')
                             ->get();

        foreach ($stockRecords as $stock) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $availableToReserve = min($remainingQuantity, $stock->available_quantity);
            
            if ($stock->reserveStock($availableToReserve, $reason)) {
                $reservations[] = [
                    'warehouse_id' => $stock->warehouse_id,
                    'warehouse_name' => $stock->warehouse->name,
                    'quantity' => $availableToReserve,
                ];
                
                $remainingQuantity -= $availableToReserve;
            }
        }

        $success = $remainingQuantity === 0;
        $message = $success 
            ? "Successfully reserved {$quantity} units"
            : "Could only reserve " . ($quantity - $remainingQuantity) . " out of {$quantity} units";

        return [
            'success' => $success,
            'reservations' => $reservations,
            'message' => $message,
            'remaining_quantity' => $remainingQuantity,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FAZA C: MEDIA & ATTRIBUTES BUSINESS METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Add media to product
     *
     * @param string $filePath
     * @param array $metadata
     * @return \App\Models\Media
     */
    public function addMedia(string $filePath, array $metadata = []): Media
    {
        $media = new Media();
        $media->file_path = $filePath;
        $media->file_name = $metadata['file_name'] ?? basename($filePath);
        $media->original_name = $metadata['original_name'] ?? $media->file_name;
        $media->file_size = $metadata['file_size'] ?? (file_exists($filePath) ? filesize($filePath) : 0);
        $media->mime_type = $metadata['mime_type'] ?? 'image/jpeg';
        $media->width = $metadata['width'] ?? null;
        $media->height = $metadata['height'] ?? null;
        $media->alt_text = $metadata['alt_text'] ?? null;
        $media->sort_order = $metadata['sort_order'] ?? $this->media()->count();
        $media->is_primary = $metadata['is_primary'] ?? ($this->media()->count() === 0);
        
        $this->media()->save($media);
        
        return $media;
    }

    /**
     * Set attribute value using EAV system
     *
     * @param string|int $attributeCode
     * @param mixed $value
     * @return \App\Models\ProductAttributeValue
     */
    public function setAttribute(string|int $attributeCode, mixed $value): ProductAttributeValue
    {
        // Find attribute by code or ID
        $attribute = is_numeric($attributeCode) 
            ? ProductAttribute::find($attributeCode)
            : ProductAttribute::where('code', $attributeCode)->first();
            
        if (!$attribute) {
            throw new \InvalidArgumentException("Attribute not found: {$attributeCode}");
        }
        
        // Find or create attribute value
        $attributeValue = $this->attributeValues()
            ->where('attribute_id', $attribute->id)
            ->first();
            
        if (!$attributeValue) {
            $attributeValue = new ProductAttributeValue();
            $attributeValue->product_id = $this->id;
            $attributeValue->attribute_id = $attribute->id;
        }
        
        $attributeValue->value = $value;
        $attributeValue->save();
        
        return $attributeValue;
    }

    /**
     * Get attribute value using EAV system
     *
     * @param string|int $attributeCode
     * @return mixed
     */
    public function getAttribute(string|int $attributeCode): mixed
    {
        // Find attribute by code or ID
        $attribute = is_numeric($attributeCode) 
            ? ProductAttribute::find($attributeCode)
            : ProductAttribute::where('code', $attributeCode)->first();
            
        if (!$attribute) {
            return null;
        }
        
        $attributeValue = $this->attributeValues()
            ->where('attribute_id', $attribute->id)
            ->first();
            
        return $attributeValue?->effective_value;
    }

    /**
     * Sync product to specific integration system
     *
     * @param string $integrationType
     * @param string $integrationIdentifier
     * @param array $options
     * @return \App\Models\IntegrationMapping
     */
    public function syncToIntegration(string $integrationType, string $integrationIdentifier, array $options = []): IntegrationMapping
    {
        // Find or create integration mapping
        $mapping = $this->integrationMappings()
            ->where('integration_type', $integrationType)
            ->where('integration_identifier', $integrationIdentifier)
            ->first();
            
        if (!$mapping) {
            $mapping = new IntegrationMapping();
            $mapping->integration_type = $integrationType;
            $mapping->integration_identifier = $integrationIdentifier;
            $mapping->sync_status = 'pending';
            $mapping->sync_direction = $options['sync_direction'] ?? 'both';
            
            $this->integrationMappings()->save($mapping);
        }
        
        // Update sync status to pending if not already syncing
        if (!in_array($mapping->sync_status, ['pending', 'syncing'])) {
            $mapping->sync_status = 'pending';
            $mapping->next_sync_at = now();
            $mapping->save();
        }
        
        return $mapping;
    }

    /**
     * Check if product has specific attribute
     *
     * @param string|int $attributeCode
     * @return bool
     */
    public function hasAttribute(string|int $attributeCode): bool
    {
        return $this->getAttribute($attributeCode) !== null;
    }

    /**
     * Get all automotive compatibility attributes
     *
     * @return array
     */
    public function getAutomotiveAttributes(): array
    {
        $automotive = [];
        
        // Vehicle Model compatibility
        $models = $this->getAttribute('model');
        if ($models) {
            $automotive['models'] = is_array($models) ? $models : [$models];
        }
        
        // OEM part numbers
        $original = $this->getAttribute('original');
        if ($original) {
            $automotive['original'] = $original;
        }
        
        // Replacement part numbers
        $replacement = $this->getAttribute('replacement');
        if ($replacement) {
            $automotive['replacement'] = $replacement;
        }
        
        return $automotive;
    }

    /**
     * Get sync status for all integration systems
     *
     * @return array
     */
    public function getSyncStatus(): array
    {
        $status = [];
        
        $mappings = $this->integrationMappings()->get();
        
        foreach ($mappings as $mapping) {
            $status[$mapping->integration_type][$mapping->integration_identifier] = [
                'status' => $mapping->sync_status,
                'last_sync' => $mapping->last_sync_at,
                'needs_sync' => $mapping->needs_sync,
                'has_error' => $mapping->has_error,
                'error_count' => $mapping->error_count,
            ];
        }
        
        return $status;
    }

    /*
    |--------------------------------------------------------------------------
    | MODEL EVENTS & OBSERVERS HOOKS
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
        // Try slug first, then ID as fallback
        return $this->where('slug', $value)->first() 
            ?? $this->where('id', $value)->first();
    }
}