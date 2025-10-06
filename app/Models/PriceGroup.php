<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * PriceGroup Model - Grupy cenowe systemu PPM-CC-Laravel
 * 
 * Business Logic:
 * - 8 grup cenowych PPM: Detaliczna, Dealer Standard/Premium, 
 *   Warsztat Standard/Premium, Szkółka-Komis-Drop, Pracownik
 * - Tylko jedna grupa może być domyślna (is_default=true)
 * - Każda grupa ma domyślną marżę dla kalkulacji cen
 * - Integration mapping dla PrestaShop specific_price groups
 * 
 * Performance Features:
 * - Eager loading ready relationships
 * - Strategic query scopes dla frequent operations
 * - JSON casting dla integration mappings
 * - Cached default group detection
 * 
 * @property int $id
 * @property string $name Display name (Detaliczna, Dealer Standard, etc.)
 * @property string $code Unique code (retail, dealer_std, etc.)
 * @property bool $is_default Only one group can be default
 * @property float|null $margin_percentage Default margin % for this group
 * @property bool $is_active Active status
 * @property int $sort_order Display order
 * @property array|null $prestashop_mapping PrestaShop mapping per shop
 * @property array|null $erp_mapping ERP systems mapping
 * @property string|null $description Group description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductPrice[] $prices
 * @property-read int $products_count
 * @property-read string $display_name
 * @property-read bool $has_products
 * 
 * @method static \Illuminate\Database\Eloquent\Builder active()
 * @method static \Illuminate\Database\Eloquent\Builder default()
 * @method static \Illuminate\Database\Eloquent\Builder ordered()
 * @method static \Illuminate\Database\Eloquent\Builder byCode(string $code)
 * @method static \App\Models\PriceGroup getDefault()
 * 
 * @package App\Models
 * @version FAZA B
 * @since 2024-09-09
 */
class PriceGroup extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'code',
        'is_default',
        'margin_percentage',
        'is_active',
        'sort_order',
        'prestashop_mapping',
        'erp_mapping',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'margin_percentage' => 'decimal:2',
            'sort_order' => 'integer',
            'prestashop_mapping' => 'array',
            'erp_mapping' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Boot model - Add audit trail hooks
     */
    protected static function boot(): void
    {
        parent::boot();

        // Create audit trail on creation
        static::created(function ($priceGroup) {
            \App\Models\PriceHistory::createForModel(
                $priceGroup,
                'created',
                [],
                $priceGroup->toArray(),
                [
                    'reason' => 'Price group created',
                    'source' => 'system'
                ]
            );
        });

        // Create audit trail on update
        static::updated(function ($priceGroup) {
            $oldValues = $priceGroup->getOriginal();
            $newValues = $priceGroup->toArray();

            \App\Models\PriceHistory::createForModel(
                $priceGroup,
                'updated',
                $oldValues,
                $newValues,
                [
                    'reason' => 'Price group updated',
                    'source' => 'system'
                ]
            );
        });

        // Create audit trail on deletion
        static::deleted(function ($priceGroup) {
            \App\Models\PriceHistory::createForModel(
                $priceGroup,
                'deleted',
                $priceGroup->toArray(),
                [],
                [
                    'reason' => 'Price group deleted',
                    'source' => 'system'
                ]
            );
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Product prices using this group
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class, 'price_group_id')
                    ->orderBy('created_at', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS
    |--------------------------------------------------------------------------
    */

    /**
     * Get display name with additional context
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function displayName(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $name = $this->name;
                
                if ($this->is_default) {
                    $name .= ' (Domyślna)';
                }
                
                if (!$this->is_active) {
                    $name .= ' (Nieaktywna)';
                }
                
                return $name;
            }
        );
    }

    /**
     * Get products count using this price group
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function productsCount(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->prices()->distinct('product_id')->count('product_id')
        );
    }

    /**
     * Check if group has any products
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function hasProducts(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->prices()->exists()
        );
    }

    /**
     * Normalize code to lowercase
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function code(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => strtolower(trim($value))
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Active price groups only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Default price group
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope: Ordered by sort_order and name
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')
                    ->orderBy('name', 'asc');
    }

    /**
     * Scope: Find by code
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $code
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', strtolower(trim($code)));
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC HELPER METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get default price group
     *
     * @return \App\Models\PriceGroup|null
     */
    public static function getDefault(): ?PriceGroup
    {
        return static::default()->active()->first();
    }

    /**
     * Get price group by code with caching
     *
     * @param string $code
     * @return \App\Models\PriceGroup|null
     */
    public static function findByCode(string $code): ?PriceGroup
    {
        return static::byCode($code)->active()->first();
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS LOGIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Set as default price group (ensure only one default)
     *
     * @return bool
     */
    public function setAsDefault(): bool
    {
        // Remove default from all other groups
        static::where('id', '!=', $this->id)->update(['is_default' => false]);
        
        // Set this group as default
        $this->is_default = true;
        
        return $this->save();
    }

    /**
     * Calculate price using group's margin
     *
     * @param float $costPrice
     * @return array ['net' => float, 'gross' => float, 'margin' => float]
     */
    public function calculatePrice(float $costPrice): array
    {
        if (!$this->margin_percentage || $costPrice <= 0) {
            return ['net' => 0, 'gross' => 0, 'margin' => 0];
        }

        $margin = $this->margin_percentage / 100;
        $netPrice = $costPrice * (1 + $margin);
        
        // Assume 23% VAT by default (can be overridden by product tax_rate)
        $grossPrice = $netPrice * 1.23;
        
        return [
            'net' => round($netPrice, 2),
            'gross' => round($grossPrice, 2),
            'margin' => $this->margin_percentage,
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
     * Get ERP mapping for specific system
     *
     * @param string $erpSystem (baselinker, subiekt_gt, dynamics)
     * @return array|null
     */
    public function getErpMapping(string $erpSystem): ?array
    {
        if (!$this->erp_mapping || !is_array($this->erp_mapping)) {
            return null;
        }

        return $this->erp_mapping[$erpSystem] ?? null;
    }

    /**
     * Set ERP mapping for specific system
     *
     * @param string $erpSystem
     * @param array $mapping
     * @return bool
     */
    public function setErpMapping(string $erpSystem, array $mapping): bool
    {
        $mappings = $this->erp_mapping ?? [];
        $mappings[$erpSystem] = $mapping;
        
        $this->erp_mapping = $mappings;
        
        return $this->save();
    }

    /**
     * Validate business rules
     *
     * @return array Validation errors
     */
    public function validateBusinessRules(): array
    {
        $errors = [];

        // Code format validation
        if (!preg_match('/^[a-z0-9_]+$/', $this->code)) {
            $errors[] = 'Code must contain only lowercase letters, numbers, and underscores';
        }

        // Margin percentage validation
        if ($this->margin_percentage !== null) {
            if ($this->margin_percentage < -100.00 || $this->margin_percentage > 999.99) {
                $errors[] = 'Margin percentage must be between -100.00% and 999.99%';
            }
        }

        // Default group validation
        if ($this->is_default) {
            $otherDefaults = static::where('id', '!=', $this->id)
                                 ->where('is_default', true)
                                 ->count();
            
            if ($otherDefaults > 0) {
                $errors[] = 'Only one price group can be set as default';
            }
        }

        return $errors;
    }

    /**
     * Check if price group can be deleted
     *
     * @return bool
     */
    public function canDelete(): bool
    {
        // Cannot delete if it has prices
        if ($this->prices()->exists()) {
            return false;
        }

        // Cannot delete if it's the default group
        if ($this->is_default) {
            return false;
        }

        return true;
    }
}