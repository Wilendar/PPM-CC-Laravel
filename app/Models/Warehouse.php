<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Warehouse Model - Magazyny systemu PPM-CC-Laravel
 * 
 * Business Logic:
 * - 6 głównych magazynów PPM: MPPTRADE (main), Pitbike.pl, Cameraman, 
 *   Otopit, INFMS, Reklamacje + możliwość custom warehouses
 * - Tylko jeden magazyn może być domyślny (is_default=true)
 * - Integration mapping fields dla PrestaShop stores i ERP systems
 * - Full address information dla logistics operations
 * 
 * Performance Features:
 * - Strategic indexing dla warehouse lookups
 * - JSON casting dla integration mappings
 * - Optimized stock relationships
 * - Cached default warehouse detection
 * 
 * @property int $id
 * @property string $name Display name (MPPTRADE, Pitbike.pl, etc.)
 * @property string $code Unique code (mpptrade, pitbike, etc.)
 * @property string|null $address Full warehouse address
 * @property string|null $city City name
 * @property string|null $postal_code Postal code
 * @property string $country Country code (default: PL)
 * @property bool $is_default Only one warehouse can be default
 * @property bool $is_active Active status
 * @property int $sort_order Display order
 * @property bool $allow_negative_stock Allow negative stock levels
 * @property bool $auto_reserve_stock Auto reserve stock for orders
 * @property int $default_minimum_stock Default minimum stock level
 * @property array|null $prestashop_mapping PrestaShop mapping per shop
 * @property array|null $erp_mapping ERP systems mapping
 * @property string|null $contact_person Warehouse manager/contact
 * @property string|null $phone Contact phone
 * @property string|null $email Contact email
 * @property string|null $operating_hours Working hours
 * @property string|null $special_instructions Special handling instructions
 * @property string|null $notes General notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductStock[] $stock
 * @property-read int $total_products
 * @property-read int $total_stock_value
 * @property-read string $display_name
 * @property-read string $full_address
 * @property-read bool $has_stock
 * 
 * @method static \Illuminate\Database\Eloquent\Builder active()
 * @method static \Illuminate\Database\Eloquent\Builder default()
 * @method static \Illuminate\Database\Eloquent\Builder ordered()
 * @method static \Illuminate\Database\Eloquent\Builder byCode(string $code)
 * @method static \Illuminate\Database\Eloquent\Builder byCountry(string $country)
 * @method static \App\Models\Warehouse getDefault()
 * 
 * @package App\Models
 * @version FAZA B
 * @since 2024-09-09
 */
class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'code',
        'type', // Strategy B: master, shop_linked, custom
        'shop_id', // Strategy B: Link to PrestaShop shop
        'address',
        'city',
        'postal_code',
        'country',
        'is_default',
        'is_active',
        'sort_order',
        'allow_negative_stock',
        'auto_reserve_stock',
        'default_minimum_stock',
        'inherit_from_shop', // Strategy B: Pull stock from PS
        'prestashop_mapping',
        'erp_mapping',
        'contact_person',
        'phone',
        'email',
        'operating_hours',
        'special_instructions',
        'notes',
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
            'allow_negative_stock' => 'boolean',
            'auto_reserve_stock' => 'boolean',
            'inherit_from_shop' => 'boolean', // Strategy B
            'sort_order' => 'integer',
            'default_minimum_stock' => 'integer',
            'prestashop_mapping' => 'array',
            'erp_mapping' => 'array',
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
     * Product stock levels in this warehouse
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stock(): HasMany
    {
        return $this->hasMany(ProductStock::class, 'warehouse_id')
                    ->orderBy('updated_at', 'desc');
    }

    /**
     * Strategy B: Related PrestaShop shop (for shop_linked warehouses)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shop()
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    /**
     * Strategy B: Stock inheritance logs for this warehouse
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inheritanceLogs(): HasMany
    {
        return $this->hasMany(StockInheritanceLog::class, 'warehouse_id')
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
                    $name .= ' (Główny)';
                }
                
                if (!$this->is_active) {
                    $name .= ' (Nieaktywny)';
                }
                
                return $name;
            }
        );
    }

    /**
     * Get full formatted address
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function fullAddress(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $parts = array_filter([
                    $this->address,
                    $this->postal_code . ' ' . $this->city,
                    $this->country !== 'PL' ? $this->country : null,
                ]);
                
                return implode(', ', $parts);
            }
        );
    }

    /**
     * Get total number of products in warehouse
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function totalProducts(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->stock()->where('quantity', '>', 0)->count()
        );
    }

    /**
     * Calculate total stock value (requires cost data)
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function totalStockValue(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                return $this->stock()
                          ->where('quantity', '>', 0)
                          ->whereNotNull('average_cost')
                          ->selectRaw('SUM(quantity * average_cost) as total')
                          ->value('total') ?? 0.0;
            }
        );
    }

    /**
     * Check if warehouse has any stock
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function hasAnyStock(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->stock()->where('quantity', '>', 0)->exists()
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

    /**
     * Normalize country to uppercase
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function country(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => strtoupper(trim($value))
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Active warehouses only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Default warehouse
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

    /**
     * Scope: Filter by country
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $country
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCountry(Builder $query, string $country): Builder
    {
        return $query->where('country', strtoupper(trim($country)));
    }

    /**
     * Strategy B Scope: Master warehouses only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMaster(Builder $query): Builder
    {
        return $query->where('type', 'master');
    }

    /**
     * Strategy B Scope: Shop-linked warehouses only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeShopLinked(Builder $query): Builder
    {
        return $query->where('type', 'shop_linked');
    }

    /**
     * Strategy B Scope: Custom warehouses only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCustom(Builder $query): Builder
    {
        return $query->where('type', 'custom');
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC HELPER METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get default warehouse
     *
     * @return \App\Models\Warehouse|null
     */
    public static function getDefault(): ?Warehouse
    {
        return static::default()->active()->first();
    }

    /**
     * Get warehouse by code with caching
     *
     * @param string $code
     * @return \App\Models\Warehouse|null
     */
    public static function findByCode(string $code): ?Warehouse
    {
        return static::byCode($code)->active()->first();
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS LOGIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Set as default warehouse (ensure only one default)
     *
     * @return bool
     */
    public function setAsDefault(): bool
    {
        // Remove default from all other warehouses
        static::where('id', '!=', $this->id)->update(['is_default' => false]);
        
        // Set this warehouse as default
        $this->is_default = true;
        
        return $this->save();
    }

    /**
     * Get current stock level for specific product
     *
     * @param int $productId
     * @param int|null $variantId
     * @return \App\Models\ProductStock|null
     */
    public function getProductStock(int $productId, ?int $variantId = null): ?ProductStock
    {
        return $this->stock()
                    ->where('product_id', $productId)
                    ->where('product_variant_id', $variantId)
                    ->first();
    }

    /**
     * Get available quantity for product
     *
     * @param int $productId
     * @param int|null $variantId
     * @return int
     */
    public function getAvailableQuantity(int $productId, ?int $variantId = null): int
    {
        $stock = $this->getProductStock($productId, $variantId);
        
        return $stock ? $stock->available_quantity : 0;
    }

    /**
     * Check if product is in stock
     *
     * @param int $productId
     * @param int|null $variantId
     * @param int $requiredQuantity
     * @return bool
     */
    public function hasStock(int $productId, ?int $variantId = null, int $requiredQuantity = 1): bool
    {
        return $this->getAvailableQuantity($productId, $variantId) >= $requiredQuantity;
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

        // Default warehouse validation
        if ($this->is_default) {
            $otherDefaults = static::where('id', '!=', $this->id)
                                 ->where('is_default', true)
                                 ->count();
            
            if ($otherDefaults > 0) {
                $errors[] = 'Only one warehouse can be set as default';
            }
        }

        // Minimum stock validation
        if ($this->default_minimum_stock < 0) {
            $errors[] = 'Default minimum stock cannot be negative';
        }

        return $errors;
    }

    /**
     * Check if warehouse can be deleted
     *
     * @return bool
     */
    public function canDelete(): bool
    {
        // Cannot delete if it has stock records
        if ($this->stock()->exists()) {
            return false;
        }

        // Cannot delete if it's the default warehouse
        if ($this->is_default) {
            return false;
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | STRATEGY B HELPER METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if warehouse is master type
     *
     * @return bool
     */
    public function isMaster(): bool
    {
        return $this->type === 'master';
    }

    /**
     * Check if warehouse is shop-linked type
     *
     * @return bool
     */
    public function isShopLinked(): bool
    {
        return $this->type === 'shop_linked';
    }

    /**
     * Check if warehouse is custom type
     *
     * @return bool
     */
    public function isCustom(): bool
    {
        return $this->type === 'custom';
    }

    /*
    |--------------------------------------------------------------------------
    | ERP INTEGRATION METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Create warehouse from ERP data
     *
     * @param string $erpType (baselinker, subiekt_gt, dynamics)
     * @param array $erpData ERP warehouse data
     * @param int $connectionId ERP connection ID
     * @return static
     */
    public static function createFromErpData(string $erpType, array $erpData, int $connectionId): static
    {
        $name = $erpData['name'] ?? $erpData['symbol'] ?? 'Magazyn ERP';
        $code = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $name));
        $code = substr($code, 0, 50) . '_' . $erpType . '_' . $erpData['id'];

        // Ensure unique code
        $baseCode = $code;
        $counter = 1;
        while (static::where('code', $code)->exists()) {
            $code = $baseCode . '_' . $counter;
            $counter++;
        }

        $warehouse = static::create([
            'name' => $name,
            'code' => $code,
            'type' => 'custom',
            'is_active' => true,
            'is_default' => false,
            'sort_order' => static::max('sort_order') + 1 ?: 1,
            'country' => 'PL',
            'erp_mapping' => [
                $erpType => [
                    'id' => $erpData['id'],
                    'name' => $erpData['name'] ?? null,
                    'symbol' => $erpData['symbol'] ?? null,
                    'connection_id' => $connectionId,
                    'synced_at' => now()->toIso8601String(),
                ],
            ],
            'notes' => "Utworzony automatycznie z ERP: {$erpType} (ID: {$erpData['id']})",
        ]);

        return $warehouse;
    }

    /**
     * Clear ERP mapping for specific system
     *
     * @param string $erpType (baselinker, subiekt_gt, dynamics)
     * @return bool
     */
    public function clearErpMapping(string $erpType): bool
    {
        $mappings = $this->erp_mapping ?? [];

        if (isset($mappings[$erpType])) {
            unset($mappings[$erpType]);
            $this->erp_mapping = empty($mappings) ? null : $mappings;
            return $this->save();
        }

        return true;
    }
}