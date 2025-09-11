<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

/**
 * ProductPrice Model - System cen produktów PPM-CC-Laravel
 * 
 * Business Logic:
 * - Wielopoziomowe ceny dla każdej grupy cenowej (8 grup PPM)
 * - Support dla product variants (product OR product_variant pricing)
 * - Auto-calculation margins based on cost_price vs selling price
 * - Time-based pricing z valid_from/valid_to periods
 * - Currency support z exchange rates dla international pricing
 * - PrestaShop specific_price mapping per shop
 * 
 * Performance Features:
 * - Strategic indexing dla price lookups
 * - Cached margin calculations
 * - JSON casting dla integration mappings
 * - Optimized query scopes dla common operations
 * 
 * @property int $id
 * @property int $product_id Products.id
 * @property int|null $product_variant_id Product_variants.id (optional)
 * @property int $price_group_id Price_groups.id
 * @property float $price_net Net price (before tax)
 * @property float $price_gross Gross price (with tax)
 * @property float|null $cost_price Purchase/cost price (sensitive)
 * @property string $currency Price currency (ISO 4217)
 * @property float $exchange_rate Exchange rate to base currency
 * @property \Carbon\Carbon|null $valid_from Price validity start
 * @property \Carbon\Carbon|null $valid_to Price validity end
 * @property float|null $margin_percentage Profit margin %
 * @property float|null $markup_percentage Markup %
 * @property array|null $prestashop_mapping PrestaShop mapping per shop
 * @property bool $auto_calculate_gross Auto-calculate gross from net
 * @property bool $auto_calculate_margin Auto-calculate margin from cost
 * @property bool $price_includes_tax Whether net price includes tax
 * @property bool $is_active Price is active
 * @property bool $is_promotion Promotional price indicator
 * @property int|null $created_by User who created price
 * @property int|null $updated_by User who updated price
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\ProductVariant|null $variant
 * @property-read \App\Models\PriceGroup $priceGroup
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\User|null $updater
 * @property-read string $formatted_price_net
 * @property-read string $formatted_price_gross
 * @property-read float $calculated_margin
 * @property-read float $calculated_markup
 * @property-read bool $is_valid_now
 * @property-read bool $is_expired
 * @property-read string $validity_status
 * 
 * @method static \Illuminate\Database\Eloquent\Builder active()
 * @method static \Illuminate\Database\Eloquent\Builder validNow()
 * @method static \Illuminate\Database\Eloquent\Builder byPriceGroup(int $priceGroupId)
 * @method static \Illuminate\Database\Eloquent\Builder byProduct(int $productId)
 * @method static \Illuminate\Database\Eloquent\Builder byVariant(int $variantId)
 * @method static \Illuminate\Database\Eloquent\Builder byCurrency(string $currency)
 * @method static \Illuminate\Database\Eloquent\Builder promotional()
 * 
 * @package App\Models
 * @version FAZA B
 * @since 2024-09-09
 */
class ProductPrice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'price_group_id',
        'price_net',
        'price_gross',
        'cost_price',
        'currency',
        'exchange_rate',
        'valid_from',
        'valid_to',
        'margin_percentage',
        'markup_percentage',
        'prestashop_mapping',
        'auto_calculate_gross',
        'auto_calculate_margin',
        'price_includes_tax',
        'is_active',
        'is_promotion',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'cost_price', // Sensitive data - only for Admin/Manager roles
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_net' => 'decimal:2',
            'price_gross' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'exchange_rate' => 'decimal:4',
            'margin_percentage' => 'decimal:2',
            'markup_percentage' => 'decimal:2',
            'auto_calculate_gross' => 'boolean',
            'auto_calculate_margin' => 'boolean',
            'price_includes_tax' => 'boolean',
            'is_active' => 'boolean',
            'is_promotion' => 'boolean',
            'prestashop_mapping' => 'array',
            'valid_from' => 'datetime',
            'valid_to' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Boot the model - Auto-calculations
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($price) {
            // Auto-calculate gross price if enabled
            if ($price->auto_calculate_gross && $price->isDirty('price_net')) {
                $price->calculateGrossPrice();
            }

            // Auto-calculate margin if enabled and cost_price available
            if ($price->auto_calculate_margin && $price->cost_price && 
                ($price->isDirty('price_net') || $price->isDirty('cost_price'))) {
                $price->calculateMargin();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Product that this price belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Product variant (optional)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Price group
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function priceGroup(): BelongsTo
    {
        return $this->belongsTo(PriceGroup::class, 'price_group_id');
    }

    /**
     * User who created this price
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who last updated this price
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS
    |--------------------------------------------------------------------------
    */

    /**
     * Get formatted net price with currency
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function formattedPriceNet(): Attribute
    {
        return Attribute::make(
            get: fn (): string => number_format($this->price_net, 2, ',', ' ') . ' ' . $this->currency
        );
    }

    /**
     * Get formatted gross price with currency
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function formattedPriceGross(): Attribute
    {
        return Attribute::make(
            get: fn (): string => number_format($this->price_gross, 2, ',', ' ') . ' ' . $this->currency
        );
    }

    /**
     * Calculate current margin percentage
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function calculatedMargin(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                if (!$this->cost_price || $this->cost_price <= 0) {
                    return 0.0;
                }

                return round((($this->price_net - $this->cost_price) / $this->cost_price) * 100, 2);
            }
        );
    }

    /**
     * Calculate current markup percentage
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function calculatedMarkup(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                if (!$this->cost_price || $this->price_net <= 0) {
                    return 0.0;
                }

                return round((($this->price_net - $this->cost_price) / $this->price_net) * 100, 2);
            }
        );
    }

    /**
     * Check if price is valid now
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function isValidNow(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                $now = Carbon::now();
                
                $validFrom = $this->valid_from ? Carbon::parse($this->valid_from) : null;
                $validTo = $this->valid_to ? Carbon::parse($this->valid_to) : null;
                
                if ($validFrom && $now->lt($validFrom)) {
                    return false; // Not started yet
                }
                
                if ($validTo && $now->gt($validTo)) {
                    return false; // Expired
                }
                
                return $this->is_active;
            }
        );
    }

    /**
     * Check if price is expired
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function isExpired(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                return $this->valid_to && Carbon::now()->gt(Carbon::parse($this->valid_to));
            }
        );
    }

    /**
     * Get validity status text
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function validityStatus(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if (!$this->is_active) {
                    return 'Nieaktywna';
                }
                
                $now = Carbon::now();
                $validFrom = $this->valid_from ? Carbon::parse($this->valid_from) : null;
                $validTo = $this->valid_to ? Carbon::parse($this->valid_to) : null;
                
                if ($validFrom && $now->lt($validFrom)) {
                    return 'Oczekuje na aktywację (' . $validFrom->format('Y-m-d') . ')';
                }
                
                if ($validTo && $now->gt($validTo)) {
                    return 'Wygasła (' . $validTo->format('Y-m-d') . ')';
                }
                
                if ($validTo) {
                    return 'Aktywna do ' . $validTo->format('Y-m-d');
                }
                
                return 'Aktywna';
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Active prices only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Prices valid at current time
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValidNow(Builder $query): Builder
    {
        $now = Carbon::now();
        
        return $query->where('is_active', true)
                    ->where(function ($query) use ($now) {
                        $query->whereNull('valid_from')
                              ->orWhere('valid_from', '<=', $now);
                    })
                    ->where(function ($query) use ($now) {
                        $query->whereNull('valid_to')
                              ->orWhere('valid_to', '>', $now);
                    });
    }

    /**
     * Scope: Filter by price group
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $priceGroupId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPriceGroup(Builder $query, int $priceGroupId): Builder
    {
        return $query->where('price_group_id', $priceGroupId);
    }

    /**
     * Scope: Filter by product
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $productId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope: Filter by variant
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $variantId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByVariant(Builder $query, int $variantId): Builder
    {
        return $query->where('product_variant_id', $variantId);
    }

    /**
     * Scope: Filter by currency
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $currency
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCurrency(Builder $query, string $currency): Builder
    {
        return $query->where('currency', strtoupper($currency));
    }

    /**
     * Scope: Promotional prices only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePromotional(Builder $query): Builder
    {
        return $query->where('is_promotion', true);
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS LOGIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Calculate gross price from net price using product tax rate
     *
     * @return void
     */
    public function calculateGrossPrice(): void
    {
        if (!$this->product) {
            return;
        }

        $taxRate = $this->product->tax_rate ?? 23.00; // Default 23% VAT
        $taxMultiplier = 1 + ($taxRate / 100);
        
        if ($this->price_includes_tax) {
            // Net price already includes tax, so gross = net
            $this->price_gross = $this->price_net;
        } else {
            // Calculate gross from net + tax
            $this->price_gross = round($this->price_net * $taxMultiplier, 2);
        }
    }

    /**
     * Calculate margin percentage from cost and net price
     *
     * @return void
     */
    public function calculateMargin(): void
    {
        if (!$this->cost_price || $this->cost_price <= 0) {
            $this->margin_percentage = null;
            $this->markup_percentage = null;
            return;
        }

        // Margin = (Selling Price - Cost) / Cost * 100
        $this->margin_percentage = round((($this->price_net - $this->cost_price) / $this->cost_price) * 100, 2);
        
        // Markup = (Selling Price - Cost) / Selling Price * 100
        if ($this->price_net > 0) {
            $this->markup_percentage = round((($this->price_net - $this->cost_price) / $this->price_net) * 100, 2);
        }
    }

    /**
     * Convert price to different currency
     *
     * @param string $targetCurrency
     * @param float $exchangeRate
     * @return array ['net' => float, 'gross' => float]
     */
    public function convertToCurrency(string $targetCurrency, float $exchangeRate): array
    {
        return [
            'net' => round($this->price_net * $exchangeRate, 2),
            'gross' => round($this->price_gross * $exchangeRate, 2),
        ];
    }

    /**
     * Get PrestaShop mapping for specific shop
     *
     * @param int|string $shopId
     * @return array|null
     */
    public function getPrestaShopMapping($shopId): ?array
    {
        if (!$this->prestashop_mapping || !is_array($this->prestashop_mapping)) {
            return null;
        }

        return $this->prestashop_mapping["shop_{$shopId}"] ?? null;
    }

    /**
     * Set PrestaShop mapping for specific shop
     *
     * @param int|string $shopId
     * @param array $mapping
     * @return bool
     */
    public function setPrestaShopMapping($shopId, array $mapping): bool
    {
        $mappings = $this->prestashop_mapping ?? [];
        $mappings["shop_{$shopId}"] = $mapping;
        
        $this->prestashop_mapping = $mappings;
        
        return $this->save();
    }

    /**
     * Clone price to different price group
     *
     * @param int $newPriceGroupId
     * @param float|null $adjustmentPercentage Optional price adjustment %
     * @return \App\Models\ProductPrice
     */
    public function cloneToPriceGroup(int $newPriceGroupId, ?float $adjustmentPercentage = null): ProductPrice
    {
        $newPrice = $this->replicate();
        $newPrice->price_group_id = $newPriceGroupId;
        
        if ($adjustmentPercentage !== null) {
            $multiplier = 1 + ($adjustmentPercentage / 100);
            $newPrice->price_net = round($this->price_net * $multiplier, 2);
            $newPrice->price_gross = round($this->price_gross * $multiplier, 2);
            
            // Recalculate margin with new price
            if ($newPrice->cost_price) {
                $newPrice->calculateMargin();
            }
        }
        
        $newPrice->save();
        
        return $newPrice;
    }

    /**
     * Validate business rules
     *
     * @return array Validation errors
     */
    public function validateBusinessRules(): array
    {
        $errors = [];

        // Price validation
        if ($this->price_net < 0) {
            $errors[] = 'Net price cannot be negative';
        }

        if ($this->price_gross < $this->price_net) {
            $errors[] = 'Gross price cannot be lower than net price';
        }

        // Cost price validation
        if ($this->cost_price !== null && $this->cost_price < 0) {
            $errors[] = 'Cost price cannot be negative';
        }

        // Date validation
        if ($this->valid_from && $this->valid_to && $this->valid_to <= $this->valid_from) {
            $errors[] = 'Valid to date must be after valid from date';
        }

        // Margin validation
        if ($this->margin_percentage !== null && 
            ($this->margin_percentage < -100 || $this->margin_percentage > 1000)) {
            $errors[] = 'Margin percentage must be between -100% and 1000%';
        }

        return $errors;
    }
}