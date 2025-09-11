<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * ProductVariant Model - Master-Variant Pattern dla PPM-CC-Laravel
 * 
 * Obsługuje system wariantów produktów z selektywnym dziedziczeniem:
 * - Własny SKU dla każdego wariantu
 * - Selektywne dziedziczenie właściwości z master produktu
 * - Kontrola dziedziczenia: prices, stock, attributes, media
 * - Business logic dla effective properties (własne lub dziedziczone)
 * 
 * Inheritance System:
 * - inherit_prices: true = dziedziczy ceny z master, false = własne ceny
 * - inherit_stock: false = własne stany (domyślnie), true = dziedziczy z master  
 * - inherit_attributes: true = dziedziczy atrybuty + może mieć własne
 * 
 * Performance: Zaprojektowane dla produktów z dziesiatkami wariantów
 * Business Logic: PrestaShop combination mapping ready
 * 
 * @property int $id
 * @property int $product_id Foreign key do products
 * @property string $variant_sku Unikalny SKU wariantu
 * @property string $variant_name Nazwa wariantu (np. "Czerwony L")
 * @property string|null $ean Dedykowany EAN barcode
 * @property int $sort_order Kolejność wyświetlania
 * @property bool $inherit_prices Czy dziedziczy ceny z master
 * @property bool $inherit_stock Czy dziedziczy stany z master
 * @property bool $inherit_attributes Czy dziedziczy atrybuty z master
 * @property bool $is_active Status aktywności wariantu
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * 
 * @property-read \App\Models\Product $product Master product
 * @property-read array $effectivePrices Ceny (własne lub dziedziczone)
 * @property-read int $effectiveStock Stany (własne lub dziedziczone)
 * @property-read array $effectiveAttributes Atrybuty (własne + dziedziczone)
 * @property-read string|null $effectiveMedia Główne zdjęcie (własne lub dziedziczone)
 * @property-read string $displayName Pełna nazwa dla UI
 * @property-read string $fullSku Complete SKU z master prefix
 * @property-read array $inheritanceStatus Status dziedziczenia
 * @property-read bool $hasOwnPrices Czy ma własne ceny
 * @property-read bool $hasOwnStock Czy ma własne stany
 * @property-read string $url URL wariantu
 * 
 * @method static \Illuminate\Database\Eloquent\Builder active()
 * @method static \Illuminate\Database\Eloquent\Builder forProduct(int $productId)
 * @method static \Illuminate\Database\Eloquent\Builder withInheritance()
 * @method static \Illuminate\Database\Eloquent\Builder masterVariants()
 * @method static \Illuminate\Database\Eloquent\Builder withOwnPrices()
 * @method static \Illuminate\Database\Eloquent\Builder withOwnStock()
 * 
 * @package App\Models
 * @version 1.0
 * @since FAZA A - Core Models Implementation
 */
class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     * 
     * Security: Mass assignment protection z business validation
     *
     * @var array<string>
     */
    protected $fillable = [
        'product_id',
        'variant_sku', 
        'variant_name',
        'ean',
        'sort_order',
        'inherit_prices',
        'inherit_stock',
        'inherit_attributes',
        'is_active',
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
     * Performance: Optimized casting dla inheritance logic
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'sort_order' => 'integer',
            'inherit_prices' => 'boolean',
            'inherit_stock' => 'boolean', 
            'inherit_attributes' => 'boolean',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     * 
     * Business Logic: SKU normalization i validation
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($variant) {
            $variant->variant_sku = strtoupper(trim($variant->variant_sku));
        });

        static::updating(function ($variant) {
            if ($variant->isDirty('variant_sku')) {
                $variant->variant_sku = strtoupper(trim($variant->variant_sku));
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS - Master-Variant Relations + FAZA C
    |--------------------------------------------------------------------------
    */

    /**
     * Master product relationship (many:1)
     * 
     * Performance: Eager loading ready dla variant queries
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Variant prices relationship (1:many) - FAZA B
     * 
     * TODO: Implement in FAZA B - Price Groups dla wariantów
     * Własne ceny wariantu (gdy inherit_prices = false)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prices(): HasMany
    {
        // TODO: Implement in FAZA B
        // return $this->hasMany(ProductVariantPrice::class, 'variant_id', 'id');
        throw new \BadMethodCallException('Variant prices will be implemented in FAZA B');
    }

    /**
     * Variant stock relationship (1:many) - FAZA B
     * 
     * TODO: Implement in FAZA B - Multi-warehouse Stock dla wariantów
     * Własne stany wariantu (gdy inherit_stock = false)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stock(): HasMany
    {
        // TODO: Implement in FAZA B
        // return $this->hasMany(ProductVariantStock::class, 'variant_id', 'id');
        throw new \BadMethodCallException('Variant stock will be implemented in FAZA B');
    }

    /**
     * Variant media/images polymorphic relationship (1:many) - FAZA C ✅ IMPLEMENTED
     * 
     * Obsługa: dedykowane zdjęcia dla wariantu + inheritance z master
     * Performance: Strategic eager loading z gallery order
     * Integration: PrestaShop combination images mapping
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')
                    ->galleryOrder(); // Uses custom scope from Media model
    }

    /**
     * Variant files/documents polymorphic relationship (1:many) - FAZA C ✅ IMPLEMENTED
     * 
     * Obsługa: dokumenty specyficzne dla wariantu
     * Security: Access level control per file
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
     * Variant-specific attributes relationship (1:many) - FAZA C ✅ IMPLEMENTED
     * 
     * EAV System: wariant-specific attribute values
     * Performance: Optimized dla inheritance logic
     * Business Logic: Override system dla master attributes
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class, 'product_variant_id')
                    ->with('attribute')
                    ->valid();
    }

    /**
     * Variant integration mappings polymorphic relationship (1:many) - FAZA C ✅ IMPLEMENTED
     * 
     * Universal mapping: PrestaShop combinations, Baselinker variants, etc.
     * Performance: Optimized dla variant sync operations
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
    | ACCESSORS & MUTATORS - Inheritance Logic + FAZA C
    |--------------------------------------------------------------------------
    */

    /**
     * Get effective media (own media + inherited from master) - FAZA C ✅ IMPLEMENTED
     * 
     * Business Logic: Media inheritance logic
     * Performance: Optimized dla gallery display
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function effectiveMedia(): Attribute
    {
        return Attribute::make(
            get: function (): \Illuminate\Database\Eloquent\Collection {
                $ownMedia = $this->media()->active()->get();
                
                // If variant has own media, use those
                if ($ownMedia->count() > 0) {
                    return $ownMedia;
                }
                
                // Otherwise inherit from master product
                return $this->product->media()->active()->get();
            }
        );
    }

    /**
     * Get primary media (own or inherited) - FAZA C ✅ IMPLEMENTED
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function primaryMedia(): Attribute
    {
        return Attribute::make(
            get: function (): ?Media {
                // Try own primary media first
                $ownPrimary = $this->media()->primary()->active()->first();
                if ($ownPrimary) {
                    return $ownPrimary;
                }
                
                // Try any own media
                $ownMedia = $this->media()->active()->first();
                if ($ownMedia) {
                    return $ownMedia;
                }
                
                // Fall back to master product media
                return $this->product->media()->primary()->active()->first() 
                    ?: $this->product->media()->active()->first();
            }
        );
    }

    /**
     * Get effective attributes (own + inherited from master) - FAZA C ✅ IMPLEMENTED
     * 
     * Business Logic: EAV inheritance with override capability
     * Performance: Optimized dla attribute resolution
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function effectiveAttributes(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $attributes = [];
                
                // Start with master product attributes if inheriting
                if ($this->inherit_attributes) {
                    $masterAttributes = $this->product->attributesFormatted;
                    $attributes = $masterAttributes;
                }
                
                // Override with own variant-specific attributes
                $ownAttributeValues = $this->attributeValues()->with('attribute')->get();
                
                foreach ($ownAttributeValues as $value) {
                    $attributes[$value->attribute->code] = [
                        'name' => $value->attribute->name,
                        'value' => $value->formatted_value,
                        'type' => $value->attribute->attribute_type,
                        'group' => $value->attribute->display_group,
                        'is_override' => $value->is_override,
                        'is_inherited' => $value->is_inherited,
                    ];
                }
                
                return $attributes;
            }
        );
    }

    /**
     * Get effective integration mappings - FAZA C ✅ IMPLEMENTED
     * 
     * Business Logic: Integration mapping dla variants
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function effectiveIntegrationData(): Attribute
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
                        'is_variant' => true,
                    ];
                }
                
                return $data;
            }
        );
    }

    /**
     * Get effective prices (own or inherited from master)
     * 
     * Business Logic: Core inheritance logic dla pricing
     * Performance: Cached effective properties
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function effectivePrices(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                // TODO: Implement in FAZA B - Price Inheritance Logic
                /*
                if ($this->inherit_prices) {
                    return $this->product->formattedPrices;
                } else {
                    return $this->prices->pluck('price', 'price_group')->toArray();
                }
                */
                
                // Placeholder implementation for FAZA A
                if ($this->inherit_prices) {
                    return $this->product->formattedPrices;
                }
                
                return [
                    'retail' => '0.00',
                    'dealer_standard' => '0.00', 
                    'dealer_premium' => '0.00',
                    'workshop' => '0.00',
                    'workshop_premium' => '0.00',
                    'training' => '0.00',
                    'commission' => '0.00',
                    'employee' => '0.00',
                ];
            }
        );
    }

    /**
     * Get effective stock (own or inherited from master)
     * 
     * Business Logic: Stock inheritance logic
     * Performance: Agregacja z warehouse levels
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function effectiveStock(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                // TODO: Implement in FAZA B - Stock Inheritance Logic  
                /*
                if ($this->inherit_stock) {
                    return $this->product->totalStock;
                } else {
                    return $this->stock->sum('quantity');
                }
                */
                
                // Placeholder implementation for FAZA A
                if ($this->inherit_stock) {
                    return $this->product->totalStock;
                }
                
                return 0;
            }
        );
    }

    /**
     * Get effective attributes (own + inherited from master)
     * 
     * Business Logic: Attribute merging logic (own overrides inherited)
     * Performance: Cached merged attributes
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function effectiveAttributes(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                // TODO: Implement in FAZA B/C - Attribute Inheritance Logic
                /*
                $effective = [];
                
                if ($this->inherit_attributes) {
                    $effective = $this->product->attributes->pluck('value', 'name')->toArray();
                }
                
                // Own attributes override inherited ones
                $ownAttributes = $this->attributes->pluck('value', 'name')->toArray();
                
                return array_merge($effective, $ownAttributes);
                */
                
                // Placeholder implementation for FAZA A
                return [
                    'color' => null,
                    'size' => null,
                    'material' => null,
                    'weight' => $this->product->weight,
                    'dimensions' => $this->product->dimensions,
                ];
            }
        );
    }

    /**
     * Get effective media (own or inherited from master)
     * 
     * Business Logic: Media inheritance dla primary image
     * Performance: Single query dla main image
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function effectiveMedia(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                // TODO: Implement in FAZA C - Media Inheritance Logic
                /*
                $ownPrimaryMedia = $this->media()->where('is_primary', true)->first();
                
                if ($ownPrimaryMedia) {
                    return Storage::url($ownPrimaryMedia->path);
                }
                
                // Fallback to master product media
                return $this->product->primaryImage;
                */
                
                // Placeholder implementation for FAZA A
                return $this->product->primaryImage;
            }
        );
    }

    /**
     * Get display name for UI
     * 
     * Business Logic: Combined master product name + variant name
     * Performance: Computed name dla variant listings
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function displayName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => "{$this->product->name} - {$this->variant_name}"
        );
    }

    /**
     * Get full SKU (master prefix + variant SKU)
     * 
     * Business Logic: Complete SKU identification system
     * Performance: Cached full SKU dla searches
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function fullSku(): Attribute
    {
        return Attribute::make(
            get: fn (): string => "{$this->product->sku}-{$this->variant_sku}"
        );
    }

    /**
     * Get inheritance status summary
     * 
     * Business Logic: Debugging i admin interface helper
     * Performance: Quick inheritance overview
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function inheritanceStatus(): Attribute
    {
        return Attribute::make(
            get: fn (): array => [
                'prices' => $this->inherit_prices ? 'inherited' : 'own',
                'stock' => $this->inherit_stock ? 'inherited' : 'own',
                'attributes' => $this->inherit_attributes ? 'inherited+own' : 'own',
                'media' => 'inherited', // TODO: Add media inheritance flag in FAZA C
            ]
        );
    }

    /**
     * Check if variant has own prices
     * 
     * Performance: Quick check dla pricing logic
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function hasOwnPrices(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                // TODO: Implement in FAZA B
                // return !$this->inherit_prices && $this->prices->count() > 0;
                return !$this->inherit_prices;
            }
        );
    }

    /**
     * Check if variant has own stock
     * 
     * Performance: Quick check dla inventory logic
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function hasOwnStock(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                // TODO: Implement in FAZA B
                // return !$this->inherit_stock && $this->stock->count() > 0;
                return !$this->inherit_stock;
            }
        );
    }

    /**
     * Get variant URL for frontend
     * 
     * Business Logic: SEO-friendly URLs dla variant pages
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function url(): Attribute
    {
        return Attribute::make(
            get: fn (): string => route('products.variants.show', [
                'product' => $this->product->slug ?? $this->product->id,
                'variant' => $this->id
            ])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES - Variant Filtering
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Active variants only
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
     * Scope: Variants for specific product
     * 
     * Performance: Index optimized dla product variant listings
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $productId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId)
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('variant_name', 'asc');
    }

    /**
     * Scope: Load variants with inheritance details
     * 
     * Performance: Eager loading dla inheritance calculations
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithInheritance(Builder $query): Builder
    {
        return $query->with([
            'product:id,sku,name,manufacturer',
            // TODO: Add in FAZA B
            // 'prices:id,variant_id,price_group,price',
            // 'stock:id,variant_id,warehouse,quantity',
            // TODO: Add in FAZA C
            // 'media:id,mediable_id,path,is_primary',
        ]);
    }

    /**
     * Scope: Variants that are variant masters (have sub-variants)
     * 
     * Performance: Nested variant hierarchy support
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMasterVariants(Builder $query): Builder
    {
        return $query->whereHas('product', function ($productQuery) {
            $productQuery->where('is_variant_master', true);
        });
    }

    /**
     * Scope: Variants with own prices (not inherited)
     * 
     * Performance: Filter dla price management
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithOwnPrices(Builder $query): Builder
    {
        return $query->where('inherit_prices', false);
    }

    /**
     * Scope: Variants with own stock (not inherited)
     * 
     * Performance: Filter dla inventory management
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithOwnStock(Builder $query): Builder
    {
        return $query->where('inherit_stock', false);
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS LOGIC METHODS - Variant Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Synchronize variant with master product
     * 
     * Business Logic: Force inheritance update when master changes
     * Performance: Selective sync based on inheritance flags
     *
     * @return bool
     */
    public function syncWithMaster(): bool
    {
        // TODO: Implement in FAZA B/C based on inheritance flags
        /*
        if ($this->inherit_prices) {
            $this->clearOwnPrices();
        }
        
        if ($this->inherit_stock) {
            $this->clearOwnStock();
        }
        
        if ($this->inherit_attributes) {
            $this->syncAttributesWithMaster();
        }
        */
        
        // Placeholder for FAZA A
        return true;
    }

    /**
     * Check inheritance consistency
     * 
     * Business Logic: Validate inheritance setup
     * Performance: Quick consistency check
     *
     * @return array Issues found
     */
    public function checkInheritance(): array
    {
        $issues = [];

        // TODO: Implement in FAZA B/C
        /*
        if (!$this->inherit_prices && $this->prices->count() === 0) {
            $issues[] = 'Variant set to use own prices but has no price records';
        }
        
        if (!$this->inherit_stock && $this->stock->count() === 0) {
            $issues[] = 'Variant set to use own stock but has no stock records';
        }
        
        if ($this->inherit_prices && $this->prices->count() > 0) {
            $issues[] = 'Variant set to inherit prices but has own price records';
        }
        */

        return $issues;
    }

    /**
     * Toggle inheritance for specific property
     * 
     * Business Logic: Dynamic inheritance control
     * Performance: Atomic inheritance switching
     *
     * @param string $property prices|stock|attributes
     * @param bool $inherit
     * @return bool
     */
    public function toggleInheritance(string $property, bool $inherit): bool
    {
        $allowedProperties = ['prices', 'stock', 'attributes'];
        
        if (!in_array($property, $allowedProperties)) {
            throw new \InvalidArgumentException("Invalid inheritance property: $property");
        }

        $field = "inherit_{$property}";
        $this->$field = $inherit;

        // TODO: Implement cleanup logic in FAZA B/C
        /*
        if ($inherit) {
            // Clear own data when switching to inheritance
            switch ($property) {
                case 'prices':
                    $this->clearOwnPrices();
                    break;
                case 'stock':
                    $this->clearOwnStock();
                    break;
            }
        }
        */

        return $this->save();
    }

    /**
     * Create variant from master product template
     * 
     * Business Logic: Smart variant creation z sensible defaults
     * Performance: Optimized creation process
     *
     * @param Product $masterProduct
     * @param string $variantName
     * @param string $variantSku
     * @param array $options
     * @return static
     */
    public static function createFromMaster(
        Product $masterProduct, 
        string $variantName, 
        string $variantSku,
        array $options = []
    ): static {
        if (!$masterProduct->is_variant_master) {
            throw new \InvalidArgumentException('Product must be marked as variant master');
        }

        $variant = new static([
            'product_id' => $masterProduct->id,
            'variant_name' => $variantName,
            'variant_sku' => $variantSku,
            'inherit_prices' => $options['inherit_prices'] ?? true,
            'inherit_stock' => $options['inherit_stock'] ?? false, // Variants usually have own stock
            'inherit_attributes' => $options['inherit_attributes'] ?? true,
            'sort_order' => $options['sort_order'] ?? 0,
            'is_active' => $options['is_active'] ?? true,
        ]);

        $variant->save();

        return $variant;
    }

    /**
     * Validate business rules
     * 
     * Business Logic: Enterprise validation rules
     *
     * @return array Validation errors
     */
    public function validateBusinessRules(): array
    {
        $errors = [];

        // SKU format validation
        if (!preg_match('/^[A-Z0-9\-_]+$/', $this->variant_sku)) {
            $errors[] = 'Variant SKU must contain only uppercase letters, numbers, hyphens and underscores';
        }

        // Variant name validation
        if (empty(trim($this->variant_name))) {
            $errors[] = 'Variant name is required';
        }

        // Master product validation
        if ($this->product && !$this->product->is_variant_master) {
            $errors[] = 'Product must be marked as variant master to have variants';
        }

        // TODO: Add inheritance consistency checks in FAZA B
        /*
        $inheritanceIssues = $this->checkInheritance();
        $errors = array_merge($errors, $inheritanceIssues);
        */

        return $errors;
    }

    /*
    |--------------------------------------------------------------------------
    | FAZA C: MEDIA & ATTRIBUTES BUSINESS METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Add media to variant
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
     * Set attribute value for variant using EAV system
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
        
        // Find or create variant attribute value
        $attributeValue = $this->attributeValues()
            ->where('attribute_id', $attribute->id)
            ->first();
            
        if (!$attributeValue) {
            $attributeValue = new ProductAttributeValue();
            $attributeValue->product_id = $this->product_id; // Always reference master product
            $attributeValue->product_variant_id = $this->id;
            $attributeValue->attribute_id = $attribute->id;
        }
        
        $attributeValue->value = $value;
        $attributeValue->is_inherited = false; // Explicitly set by variant
        $attributeValue->is_override = true;   // Override master value
        $attributeValue->save();
        
        return $attributeValue;
    }

    /**
     * Get attribute value for variant using EAV system (with inheritance)
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
        
        // Try variant-specific value first
        $variantValue = $this->attributeValues()
            ->where('attribute_id', $attribute->id)
            ->first();
            
        if ($variantValue) {
            return $variantValue->effective_value;
        }
        
        // Fall back to master product value if inheriting attributes
        if ($this->inherit_attributes) {
            return $this->product->getAttribute($attributeCode);
        }
        
        return null;
    }

    /**
     * Inherit specific attribute from master product
     *
     * @param string|int $attributeCode
     * @return bool
     */
    public function inheritAttributeFromMaster(string|int $attributeCode): bool
    {
        $masterValue = $this->product->getAttribute($attributeCode);
        
        if ($masterValue === null) {
            return false;
        }
        
        // Find attribute by code or ID
        $attribute = is_numeric($attributeCode) 
            ? ProductAttribute::find($attributeCode)
            : ProductAttribute::where('code', $attributeCode)->first();
            
        if (!$attribute) {
            return false;
        }
        
        // Create or update inherited value
        $attributeValue = $this->attributeValues()
            ->where('attribute_id', $attribute->id)
            ->first();
            
        if (!$attributeValue) {
            $attributeValue = new ProductAttributeValue();
            $attributeValue->product_id = $this->product_id;
            $attributeValue->product_variant_id = $this->id;
            $attributeValue->attribute_id = $attribute->id;
        }
        
        $attributeValue->value = $masterValue;
        $attributeValue->is_inherited = true;
        $attributeValue->is_override = false;
        
        return $attributeValue->save();
    }

    /**
     * Sync variant to specific integration system
     *
     * @param string $integrationType
     * @param string $integrationIdentifier
     * @param array $options
     * @return \App\Models\IntegrationMapping
     */
    public function syncToIntegration(string $integrationType, string $integrationIdentifier, array $options = []): IntegrationMapping
    {
        // Find or create integration mapping for variant
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
            
            // For variants, store reference to master product in external data
            $mapping->external_data = [
                'master_product_id' => $this->product_id,
                'master_product_sku' => $this->product->sku,
                'is_variant' => true,
                'variant_attributes' => $this->effectiveAttributes,
            ];
            
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
     * Check if variant has specific attribute (including inherited)
     *
     * @param string|int $attributeCode
     * @return bool
     */
    public function hasAttribute(string|int $attributeCode): bool
    {
        return $this->getAttribute($attributeCode) !== null;
    }

    /**
     * Inherit all attributes from master product
     *
     * @return int Number of inherited attributes
     */
    public function inheritAllAttributesFromMaster(): int
    {
        $count = 0;
        $masterAttributes = $this->product->attributeValues()->get();
        
        foreach ($masterAttributes as $masterAttribute) {
            if ($this->inheritAttributeFromMaster($masterAttribute->attribute->code)) {
                $count++;
            }
        }
        
        return $count;
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
                'is_variant' => true,
                'master_product_id' => $this->product_id,
            ];
        }
        
        return $status;
    }

    /*
    |--------------------------------------------------------------------------
    | MODEL BINDING & ROUTING
    |--------------------------------------------------------------------------
    */

    /**
     * Get the route key for the model.
     * 
     * Performance: Use ID dla variant routing (SKU w URL może być za długi)
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'id'; // Use ID instead of SKU for variants
    }
}