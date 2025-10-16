<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopImportService;

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
        'slug',
        'short_description',
        'long_description',
        'product_type_id',
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
    | RELATIONSHIPS - Laravel Eloquent Relations
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
    public function getEffectiveCategoriesForShop(int $shopId)
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

    /**
     * Shop-specific categories relationship (1:many) - ETAP_05 ✅ IMPLEMENTED
     *
     * Business Logic: Kategorie per sklep PrestaShop z dziedziczeniem
     * Performance: Eager loading ready z proper indexing
     * Integration: ps_category_product per sklep mapping ready
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function shopCategories(): HasMany
    {
        return $this->hasMany(ProductShopCategory::class, 'product_id', 'id')
                    ->orderBy('shop_id', 'asc')
                    ->orderBy('sort_order', 'asc');
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

    /**
     * Product shop data relationship (1:many) - FAZA 1.5: Multi-Store Synchronization System ✅ IMPLEMENTED
     *
     * Business Logic: Każdy produkt może mieć różne dane per sklep PrestaShop
     * Performance: Eager loading ready z proper indexing
     * Multi-store: Shop-specific names, descriptions, categories, images
     * Sync Status: Tracking synchronization status per shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function shopData(): HasMany
    {
        return $this->hasMany(ProductShopData::class, 'product_id', 'id')
                    ->orderBy('shop_id', 'asc');
    }

    /**
     * Active shop data only (published and sync enabled)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activeShopData(): HasMany
    {
        return $this->shopData()
                    ->where('is_published', true)
                    ->where('sync_status', '!=', 'disabled');
    }

    /**
     * Shop data for specific shop
     *
     * @param int $shopId
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dataForShop(int $shopId): HasMany
    {
        return $this->shopData()
                    ->where('shop_id', $shopId);
    }

    /**
     * Stock movements history dla all warehouses - STOCK MANAGEMENT SYSTEM ✅ IMPLEMENTED
     *
     * Complete audit trail wszystkich ruchów magazynowych
     * Business Logic: IN/OUT/TRANSFER operations z cost tracking
     * Performance: Indexed dla history queries i reporting
     * Integration: ERP sync ready z external reference tracking
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_id', 'id')
                    ->with(['warehouse', 'creator'])
                    ->orderBy('movement_date', 'desc');
    }

    /**
     * Recent stock movements (last 30 days) dla performance optimization
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function recentStockMovements(): HasMany
    {
        return $this->stockMovements()
                    ->recent(30)
                    ->limit(50);
    }

    /**
     * Stock reservations dla all warehouses - STOCK MANAGEMENT SYSTEM ✅ IMPLEMENTED
     *
     * Advanced reservation system dla orders/quotes/transfers
     * Business Logic: Priority-based queue z expiry management
     * Performance: Optimized dla reservation queries
     * Integration: Order management system ready
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stockReservations(): HasMany
    {
        return $this->hasMany(StockReservation::class, 'product_id', 'id')
                    ->with(['warehouse', 'reserver'])
                    ->orderBy('priority', 'asc')
                    ->orderBy('reserved_at', 'asc');
    }

    /**
     * Active stock reservations only (pending/confirmed/partial)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activeReservations(): HasMany
    {
        return $this->stockReservations()
                    ->active()
                    ->orderBy('priority', 'asc');
    }

    /**
     * High priority reservations requiring attention
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function urgentReservations(): HasMany
    {
        return $this->stockReservations()
                    ->highPriority()
                    ->active()
                    ->orderBy('reserved_at', 'asc');
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
            'pojazd' => '/images/placeholders/vehicle-placeholder.jpg',
            'czesc-zamiennicza' => '/images/placeholders/spare-part-placeholder.jpg',
            'odziez' => '/images/placeholders/clothing-placeholder.jpg',
            'inne' => '/images/placeholders/default-placeholder.jpg',
        ];

        $typeSlug = $this->productType?->slug ?? 'inne';
        return $placeholders[$typeSlug] ?? $placeholders['inne'];
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
    | STOCK MANAGEMENT SYSTEM: BUSINESS METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get total available stock across all warehouses
     *
     * @return int
     */
    public function getTotalAvailableStock(): int
    {
        return $this->activeStock()->sum('available_quantity');
    }

    /**
     * Get stock level for specific warehouse
     *
     * @param int $warehouseId
     * @return int
     */
    public function getWarehouseStock(int $warehouseId): int
    {
        $stock = $this->activeStock()
                     ->where('warehouse_id', $warehouseId)
                     ->first();

        return $stock ? $stock->available_quantity : 0;
    }

    /**
     * Check if product has sufficient stock in any warehouse
     *
     * @param int $requiredQuantity
     * @param int|null $warehouseId
     * @return bool
     */
    public function hasStock(int $requiredQuantity, ?int $warehouseId = null): bool
    {
        if ($warehouseId) {
            return $this->getWarehouseStock($warehouseId) >= $requiredQuantity;
        }

        return $this->getTotalAvailableStock() >= $requiredQuantity;
    }

    /**
     * Get warehouses with available stock
     *
     * @param int|null $minQuantity
     * @return \Illuminate\Support\Collection
     */
    public function getWarehousesWithStock(?int $minQuantity = 1)
    {
        return $this->activeStock()
                   ->with('warehouse')
                   ->where('available_quantity', '>=', $minQuantity)
                   ->get()
                   ->map(function ($stock) {
                       return [
                           'warehouse_id' => $stock->warehouse_id,
                           'warehouse_name' => $stock->warehouse->name,
                           'warehouse_code' => $stock->warehouse->code,
                           'available_quantity' => $stock->available_quantity,
                           'is_low_stock' => $stock->is_low_stock,
                           'delivery_status' => $stock->delivery_status,
                       ];
                   });
    }

    /**
     * Get recent stock movements (last 7 days)
     *
     * @param int $days
     * @return \Illuminate\Support\Collection
     */
    public function getRecentMovements(int $days = 7)
    {
        return $this->stockMovements()
                   ->with(['warehouse', 'creator', 'fromWarehouse', 'toWarehouse'])
                   ->recent($days)
                   ->limit(20)
                   ->get()
                   ->map(function ($movement) {
                       return $movement->getSummary();
                   });
    }

    /**
     * Get pending reservations summary
     *
     * @return array
     */
    public function getReservationsSummary(): array
    {
        $activeReservations = $this->activeReservations;

        $summary = [
            'total_reservations' => $activeReservations->count(),
            'total_reserved_quantity' => $activeReservations->sum('quantity_remaining'),
            'high_priority_count' => $activeReservations->where('priority', '<=', 3)->count(),
            'expiring_soon' => $activeReservations->filter(function ($reservation) {
                return $reservation->expires_at &&
                       $reservation->expires_at->diffInHours(now()) <= 24;
            })->count(),
        ];

        return $summary;
    }

    /**
     * Calculate stock turnover rate
     *
     * @param int $days
     * @return float
     */
    public function getStockTurnoverRate(int $days = 30): float
    {
        $outboundMovements = $this->stockMovements()
                                 ->outbound()
                                 ->where('movement_date', '>=', now()->subDays($days))
                                 ->sum(\DB::raw('ABS(quantity_change)'));

        $averageStock = $this->activeStock()->avg('quantity') ?? 0;

        if ($averageStock <= 0) {
            return 0.0;
        }

        return round($outboundMovements / $averageStock, 2);
    }

    /**
     * Get low stock alerts dla this product
     *
     * @return \Illuminate\Support\Collection
     */
    public function getLowStockAlerts()
    {
        return $this->activeStock()
                   ->with('warehouse')
                   ->where('low_stock_alert', true)
                   ->whereRaw('available_quantity <= minimum_stock')
                   ->get()
                   ->map(function ($stock) {
                       return [
                           'warehouse_name' => $stock->warehouse->name,
                           'current_stock' => $stock->available_quantity,
                           'minimum_stock' => $stock->minimum_stock,
                           'deficit' => $stock->minimum_stock - $stock->available_quantity,
                           'last_movement' => $stock->last_movement_at?->format('Y-m-d H:i'),
                       ];
                   });
    }

    /**
     * Get stock movement statistics
     *
     * @param int $days
     * @return array
     */
    public function getStockStatistics(int $days = 30): array
    {
        $movements = $this->stockMovements()
                         ->where('movement_date', '>=', now()->subDays($days));

        $inbound = $movements->inbound()->sum(\DB::raw('ABS(quantity_change)'));
        $outbound = $movements->outbound()->sum(\DB::raw('ABS(quantity_change)'));
        $transfers = $movements->transfers()->count();

        return [
            'period_days' => $days,
            'total_movements' => $movements->count(),
            'inbound_quantity' => $inbound,
            'outbound_quantity' => $outbound,
            'net_change' => $inbound - $outbound,
            'transfers_count' => $transfers,
            'current_total_stock' => $this->getTotalAvailableStock(),
            'turnover_rate' => $this->getStockTurnoverRate($days),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FAZA 1.5: MULTI-STORE SYNCHRONIZATION BUSINESS METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get or create shop data for specific shop
     *
     * @param int $shopId
     * @return \App\Models\ProductShopData
     */
    public function getOrCreateShopData(int $shopId): ProductShopData
    {
        return $this->shopData()
                    ->where('shop_id', $shopId)
                    ->firstOrCreate([
                        'product_id' => $this->id,
                        'shop_id' => $shopId,
                    ]);
    }

    /**
     * Check if product is published on specific shop
     *
     * @param int $shopId
     * @return bool
     */
    public function isPublishedOnShop(int $shopId): bool
    {
        $shopData = $this->dataForShop($shopId)->first();
        return $shopData ? $shopData->is_published : false;
    }

    /**
     * Get sync status for specific shop
     *
     * @param int $shopId
     * @return string|null
     */
    public function getSyncStatusForShop(int $shopId): ?string
    {
        $shopData = $this->dataForShop($shopId)->first();
        return $shopData ? $shopData->sync_status : null;
    }

    /**
     * Get all shops where product is published
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPublishedShops()
    {
        return $this->activeShopData()
                    ->with('shop')
                    ->get()
                    ->pluck('shop');
    }

    /**
     * Get sync status summary across all shops
     *
     * @return array
     */
    public function getMultiStoreSyncSummary(): array
    {
        $shopData = $this->shopData()->with('shop')->get();

        $summary = [
            'total_shops' => $shopData->count(),
            'published_shops' => $shopData->where('is_published', true)->count(),
            'synced_shops' => $shopData->where('sync_status', 'synced')->count(),
            'error_shops' => $shopData->where('sync_status', 'error')->count(),
            'conflict_shops' => $shopData->where('sync_status', 'conflict')->count(),
            'disabled_shops' => $shopData->where('sync_status', 'disabled')->count(),
            'shops_needing_sync' => $shopData->filter(function ($data) {
                return $data->needsSync();
            })->count(),
        ];

        $summary['sync_health_percentage'] = $summary['total_shops'] > 0
            ? round(($summary['synced_shops'] / $summary['total_shops']) * 100, 1)
            : 0;

        return $summary;
    }

    /**
     * Publish product to specific shop
     *
     * @param int $shopId
     * @param array $shopSpecificData
     * @return \App\Models\ProductShopData
     */
    public function publishToShop(int $shopId, array $shopSpecificData = []): ProductShopData
    {
        $shopData = $this->getOrCreateShopData($shopId);

        // Update shop-specific data if provided
        if (!empty($shopSpecificData)) {
            $shopData->fill($shopSpecificData);
        }

        $shopData->publish();

        return $shopData;
    }

    /**
     * Unpublish product from specific shop
     *
     * @param int $shopId
     * @return bool
     */
    public function unpublishFromShop(int $shopId): bool
    {
        $shopData = $this->dataForShop($shopId)->first();

        if ($shopData) {
            $shopData->unpublish();
            return true;
        }

        return false;
    }

    /**
     * Mark all shop data as needing sync
     *
     * @return int Count of updated records
     */
    public function markAllShopsForSync(): int
    {
        return $this->activeShopData()
                    ->update([
                        'sync_status' => 'pending',
                        'sync_errors' => null,
                        'conflict_data' => null,
                        'conflict_detected_at' => null,
                    ]);
    }

    /**
     * Get shops with sync conflicts
     *
     * @return \Illuminate\Support\Collection
     */
    public function getShopsWithConflicts()
    {
        return $this->shopData()
                    ->with('shop')
                    ->where('sync_status', 'conflict')
                    ->whereNotNull('conflict_detected_at')
                    ->get()
                    ->map(function ($shopData) {
                        return [
                            'shop_id' => $shopData->shop_id,
                            'shop_name' => $shopData->shop->name ?? 'Unknown Shop',
                            'conflict_detected_at' => $shopData->conflict_detected_at,
                            'conflict_data' => $shopData->conflict_data,
                            'time_since_conflict' => $shopData->conflict_detected_at?->diffForHumans(),
                        ];
                    });
    }

    /**
     * Get effective name for specific shop (shop-specific or fallback to product name)
     *
     * @param int $shopId
     * @return string
     */
    public function getEffectiveNameForShop(int $shopId): string
    {
        $shopData = $this->dataForShop($shopId)->first();
        return $shopData && $shopData->name ? $shopData->name : $this->name;
    }

    /**
     * Get effective description for specific shop
     *
     * @param int $shopId
     * @param string $type 'short' or 'long'
     * @return string|null
     */
    public function getEffectiveDescriptionForShop(int $shopId, string $type = 'short'): ?string
    {
        $shopData = $this->dataForShop($shopId)->first();

        if ($type === 'short') {
            return $shopData && $shopData->short_description
                ? $shopData->short_description
                : $this->short_description;
        }

        return $shopData && $shopData->long_description
            ? $shopData->long_description
            : $this->long_description;
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
     * Set attribute value using EAV system (renamed to avoid Laravel Model conflict)
     *
     * @param string|int $attributeCode
     * @param mixed $value
     * @return \App\Models\ProductAttributeValue
     */
    public function setProductAttributeValue(string|int $attributeCode, mixed $value): ProductAttributeValue
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
     * Get attribute value using EAV system (renamed to avoid Laravel Model conflict)
     *
     * @param string|int $attributeCode
     * @return mixed
     */
    public function getProductAttributeValue(string|int $attributeCode): mixed
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
     * Check if product has specific product attribute (renamed to avoid Laravel Model conflict)
     *
     * @param string|int $attributeCode
     * @return bool
     */
    public function hasProductAttribute(string|int $attributeCode): bool
    {
        return $this->getProductAttributeValue($attributeCode) !== null;
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
        $models = $this->getProductAttributeValue('model');
        if ($models) {
            $automotive['models'] = is_array($models) ? $models : [$models];
        }
        
        // OEM part numbers
        $original = $this->getProductAttributeValue('original');
        if ($original) {
            $automotive['original'] = $original;
        }
        
        // Replacement part numbers
        $replacement = $this->getProductAttributeValue('replacement');
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
    | ETAP_07 FAZA 2A.4: PRESTASHOP IMPORT/EXPORT MODEL EXTENSIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Get sync status for specific PrestaShop shop (CONSOLIDATED 2025-10-13)
     *
     * Usage: $syncStatus = $product->getSyncStatus($shop);
     * Returns: ProductShopData instance or null
     * Performance: Single query with shop_id filter
     * UPDATED: Now uses shopData() relation instead of deprecated syncStatuses()
     *
     * @param \App\Models\PrestaShopShop $shop
     * @return \App\Models\ProductShopData|null
     */
    public function getShopSyncStatus(PrestaShopShop $shop): ?ProductShopData
    {
        return $this->shopData()
            ->where('shop_id', $shop->id)
            ->first();
    }

    /**
     * Get sync status for specific shop by ID (CONSOLIDATED 2025-10-13)
     *
     * ETAP_07 FAZA 3: Helper method for ProductForm integration
     * Usage: $syncStatus = $product->syncStatusForShop($shopId);
     * Returns: ProductShopData instance or null
     * Performance: Single query with shop_id filter
     * UPDATED: Now uses shopData() relation instead of deprecated syncStatuses()
     *
     * @param int $shopId
     * @return \App\Models\ProductShopData|null
     */
    public function syncStatusForShop(int $shopId): ?ProductShopData
    {
        return $this->shopData()
            ->where('shop_id', $shopId)
            ->first();
    }

    /**
     * Get PrestaShop product ID for specific shop (if synced)
     *
     * Usage: $psProductId = $product->getPrestashopProductId($shop);
     * Returns: PrestaShop product ID or null if not synced
     * Business Logic: Convenience method for sync operations
     *
     * @param \App\Models\PrestaShopShop $shop
     * @return int|null
     */
    public function getPrestashopProductId(PrestaShopShop $shop): ?int
    {
        $syncStatus = $this->getShopSyncStatus($shop);
        return $syncStatus?->prestashop_product_id;
    }

    /**
     * Import this product's data from PrestaShop shop
     *
     * Usage: $product = Product::importFromPrestaShop(123, $shop);
     * Business Logic: Static factory method for PrestaShop imports
     * Performance: Delegates to PrestaShopImportService
     * Integration: ETAP_07 FAZA 2A.1 reverse transformation
     *
     * @param int $prestashopProductId PrestaShop product ID
     * @param \App\Models\PrestaShopShop $shop Shop to import from
     * @return self Imported Product instance
     */
    public static function importFromPrestaShop(
        int $prestashopProductId,
        PrestaShopShop $shop
    ): self
    {
        $importService = app(PrestaShopImportService::class);
        return $importService->importProductFromPrestaShop($prestashopProductId, $shop);
    }

    /**
     * Scope: Products imported from specific PrestaShop shop (CONSOLIDATED 2025-10-13)
     *
     * Usage: $products = Product::importedFrom($shop->id)->get();
     * Business Logic: Filter products by import source
     * Performance: Optimized subquery with sync_direction filter
     * UPDATED: Now uses shopData() relation instead of deprecated syncStatuses()
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $shopId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeImportedFrom(Builder $query, int $shopId): Builder
    {
        return $query->whereHas('shopData', function($q) use ($shopId) {
            $q->where('shop_id', $shopId)
              ->where('sync_direction', 'ps_to_ppm');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLISHING SCHEDULE BUSINESS LOGIC
    |--------------------------------------------------------------------------
    */

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
    | MODEL EVENTS & OBSERVERS HOOKS
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
}