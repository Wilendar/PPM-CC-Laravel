<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

/**
 * ProductStock Model - System stanów magazynowych PPM-CC-Laravel
 * 
 * Business Logic:
 * - Multi-warehouse stock tracking dla 6+ magazynów PPM
 * - Support dla product variants (product OR product_variant stock)
 * - Advanced stock reservation system (quantity vs reserved_quantity)
 * - Delivery tracking z container numbers i status workflow
 * - Warehouse locations (wielowartościowe przez ';' separator)
 * - Minimum stock levels dla automatic reorder alerts
 * - Cost tracking (average cost, last cost)
 * 
 * Performance Features:
 * - Strategic indexing dla inventory queries
 * - Computed available_quantity column
 * - JSON casting dla integration mappings
 * - Optimized scopes dla common stock operations
 * 
 * @property int $id
 * @property int $product_id Products.id
 * @property int|null $product_variant_id Product_variants.id (optional)
 * @property int $warehouse_id Warehouses.id
 * @property int $quantity Current stock quantity
 * @property int $reserved_quantity Reserved stock for orders
 * @property int $available_quantity Available stock (computed)
 * @property int $minimum_stock Minimum stock level for alerts
 * @property int|null $maximum_stock Maximum stock level
 * @property int|null $reorder_point Auto-reorder trigger point
 * @property int|null $reorder_quantity Default reorder quantity
 * @property string|null $warehouse_location Physical locations (semicolon-separated)
 * @property string|null $bin_location Primary bin/shelf location
 * @property string|null $location_notes Special location instructions
 * @property \Carbon\Carbon|null $last_delivery_date Last stock delivery date
 * @property string|null $container_number Container number for import tracking
 * @property string $delivery_status Delivery workflow status
 * @property \Carbon\Carbon|null $expected_delivery_date Expected delivery date
 * @property int|null $expected_quantity Expected delivery quantity
 * @property string|null $delivery_notes Delivery notes
 * @property float|null $average_cost Average cost per unit
 * @property float|null $last_cost Cost from last delivery
 * @property \Carbon\Carbon|null $last_cost_update When cost was last updated
 * @property array|null $erp_mapping ERP systems mapping
 * @property int $movements_count Total stock movements
 * @property \Carbon\Carbon|null $last_movement_at Last stock movement timestamp
 * @property int|null $last_movement_by User who made last movement
 * @property bool $low_stock_alert Enable low stock alerts
 * @property bool $out_of_stock_alert Enable out of stock alerts
 * @property \Carbon\Carbon|null $last_alert_sent When last alert was sent
 * @property bool $is_active Stock record is active
 * @property bool $track_stock Whether to track stock
 * @property bool $allow_negative Allow negative stock levels
 * @property string|null $notes General notes
 * @property int|null $created_by User who created record
 * @property int|null $updated_by User who updated record
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\ProductVariant|null $variant
 * @property-read \App\Models\Warehouse $warehouse
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\User|null $updater
 * @property-read \App\Models\User|null $lastMovementUser
 * @property-read array $warehouse_locations_array
 * @property-read bool $is_low_stock
 * @property-read bool $is_out_of_stock
 * @property-read bool $needs_reorder
 * @property-read string $stock_status
 * @property-read float $total_value
 * 
 * @method static \Illuminate\Database\Eloquent\Builder active()
 * @method static \Illuminate\Database\Eloquent\Builder tracked()
 * @method static \Illuminate\Database\Eloquent\Builder inStock()
 * @method static \Illuminate\Database\Eloquent\Builder lowStock()
 * @method static \Illuminate\Database\Eloquent\Builder outOfStock()
 * @method static \Illuminate\Database\Eloquent\Builder byWarehouse(int $warehouseId)
 * @method static \Illuminate\Database\Eloquent\Builder byProduct(int $productId)
 * @method static \Illuminate\Database\Eloquent\Builder byVariant(int $variantId)
 * @method static \Illuminate\Database\Eloquent\Builder byDeliveryStatus(string $status)
 * @method static \Illuminate\Database\Eloquent\Builder needsReorder()
 * 
 * @package App\Models
 * @version FAZA B
 * @since 2024-09-09
 */
class ProductStock extends Model
{
    use HasFactory;

    /**
     * Delivery status enum values
     */
    public const DELIVERY_STATUSES = [
        'not_ordered' => 'Nie zamówione',
        'ordered' => 'Zamówione u dostawcy', 
        'confirmed' => 'Potwierdzone przez dostawcę',
        'in_production' => 'W produkcji',
        'ready_to_ship' => 'Gotowe do wysyłki',
        'shipped' => 'Wysłane',
        'in_container' => 'W kontenerze',
        'in_transit' => 'W transporcie',
        'customs' => 'Odprawa celna',
        'delayed' => 'Opóźnione',
        'receiving' => 'W trakcie odboju',
        'received' => 'Odebrane',
        'available' => 'Dostępne w magazynie',
        'cancelled' => 'Anulowane',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity',
        'minimum_stock',
        'maximum_stock',
        'reorder_point',
        'reorder_quantity',
        'warehouse_location',
        'bin_location',
        'location_notes',
        'last_delivery_date',
        'container_number',
        'delivery_status',
        'expected_delivery_date',
        'expected_quantity',
        'delivery_notes',
        'average_cost',
        'last_cost',
        'last_cost_update',
        'erp_mapping',
        'movements_count',
        'last_movement_at',
        'last_movement_by',
        'low_stock_alert',
        'out_of_stock_alert',
        'last_alert_sent',
        'is_active',
        'track_stock',
        'allow_negative',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'average_cost', // Sensitive cost data - only for Admin/Manager roles
        'last_cost',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'minimum_stock' => 'integer',
            'maximum_stock' => 'integer',
            'reorder_point' => 'integer',
            'reorder_quantity' => 'integer',
            'expected_quantity' => 'integer',
            'movements_count' => 'integer',
            'average_cost' => 'decimal:4',
            'last_cost' => 'decimal:4',
            'low_stock_alert' => 'boolean',
            'out_of_stock_alert' => 'boolean',
            'is_active' => 'boolean',
            'track_stock' => 'boolean',
            'allow_negative' => 'boolean',
            'erp_mapping' => 'array',
            'last_delivery_date' => 'date',
            'expected_delivery_date' => 'date',
            'last_cost_update' => 'datetime',
            'last_movement_at' => 'datetime',
            'last_alert_sent' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Boot the model - Auto-increment movements counter
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($stock) {
            // Increment movements count when quantity changes
            if ($stock->isDirty('quantity') && !$stock->wasRecentlyCreated) {
                $stock->movements_count++;
                $stock->last_movement_at = Carbon::now();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Product that this stock belongs to
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
     * Warehouse where stock is located
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * User who created this stock record
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who last updated stock
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * User who made last stock movement
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lastMovementUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_movement_by');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS
    |--------------------------------------------------------------------------
    */

    /**
     * Get warehouse locations as array
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function warehouseLocationsArray(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                if (!$this->warehouse_location) {
                    return [];
                }
                
                return array_filter(explode(';', $this->warehouse_location));
            },
            set: function (array $locations): string {
                return implode(';', array_filter($locations));
            }
        );
    }

    /**
     * Check if stock is low
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function isLowStock(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->available_quantity <= $this->minimum_stock && $this->available_quantity > 0
        );
    }

    /**
     * Check if stock is out of stock
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function isOutOfStock(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->available_quantity <= 0 && !$this->allow_negative
        );
    }

    /**
     * Check if needs reorder
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function needsReorder(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                return $this->reorder_point && $this->available_quantity <= $this->reorder_point;
            }
        );
    }

    /**
     * Get stock status text
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function stockStatus(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if (!$this->is_active || !$this->track_stock) {
                    return 'Nieśledzony';
                }
                
                if ($this->is_out_of_stock) {
                    return 'Brak w magazynie';
                }
                
                if ($this->is_low_stock) {
                    return 'Niski stan';
                }
                
                if ($this->needs_reorder) {
                    return 'Wymaga uzupełnienia';
                }
                
                return 'Dostępny';
            }
        );
    }

    /**
     * Calculate total value of stock
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function totalValue(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                if (!$this->average_cost || $this->quantity <= 0) {
                    return 0.0;
                }
                
                return round($this->quantity * $this->average_cost, 2);
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Active stock records only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Tracked stock only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTracked(Builder $query): Builder
    {
        return $query->where('track_stock', true);
    }

    /**
     * Scope: Items in stock (quantity > 0)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('quantity', '>', 0);
    }

    /**
     * Scope: Low stock items
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('available_quantity', '<=', 'minimum_stock')
                    ->where('available_quantity', '>', 0);
    }

    /**
     * Scope: Out of stock items
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('available_quantity', '<=', 0)
                    ->where('allow_negative', false);
    }

    /**
     * Scope: Filter by warehouse
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $warehouseId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->where('warehouse_id', $warehouseId);
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
     * Scope: Filter by delivery status
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDeliveryStatus(Builder $query, string $status): Builder
    {
        return $query->where('delivery_status', $status);
    }

    /**
     * Scope: Items that need reorder
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNeedsReorder(Builder $query): Builder
    {
        return $query->whereNotNull('reorder_point')
                    ->whereColumn('available_quantity', '<=', 'reorder_point');
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS LOGIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Reserve stock for order
     *
     * @param int $quantity
     * @param string|null $reason
     * @return bool
     */
    public function reserveStock(int $quantity, ?string $reason = null): bool
    {
        if ($quantity <= 0) {
            return false;
        }

        // Check if enough stock available
        if ($this->available_quantity < $quantity && !$this->allow_negative) {
            return false;
        }

        $this->reserved_quantity += $quantity;
        $this->last_movement_at = Carbon::now();
        $this->movements_count++;

        return $this->save();
    }

    /**
     * Release reserved stock
     *
     * @param int $quantity
     * @param string|null $reason
     * @return bool
     */
    public function releaseStock(int $quantity, ?string $reason = null): bool
    {
        if ($quantity <= 0 || $quantity > $this->reserved_quantity) {
            return false;
        }

        $this->reserved_quantity -= $quantity;
        $this->last_movement_at = Carbon::now();
        $this->movements_count++;

        return $this->save();
    }

    /**
     * Add stock to warehouse
     *
     * @param int $quantity
     * @param float|null $cost
     * @param string|null $reason
     * @return bool
     */
    public function addStock(int $quantity, ?float $cost = null, ?string $reason = null): bool
    {
        if ($quantity <= 0) {
            return false;
        }

        $this->quantity += $quantity;
        
        // Update cost information if provided
        if ($cost !== null) {
            $this->updateCost($cost, $quantity);
        }

        $this->last_movement_at = Carbon::now();
        $this->movements_count++;

        return $this->save();
    }

    /**
     * Remove stock from warehouse
     *
     * @param int $quantity
     * @param string|null $reason
     * @return bool
     */
    public function removeStock(int $quantity, ?string $reason = null): bool
    {
        if ($quantity <= 0) {
            return false;
        }

        // Check if enough stock available (including reserved)
        if ($this->quantity < $quantity && !$this->allow_negative) {
            return false;
        }

        $this->quantity -= $quantity;
        
        // Adjust reserved quantity if needed
        if ($this->reserved_quantity > $this->quantity && $this->quantity >= 0) {
            $this->reserved_quantity = max(0, $this->quantity);
        }

        $this->last_movement_at = Carbon::now();
        $this->movements_count++;

        return $this->save();
    }

    /**
     * Update average cost with new delivery
     *
     * @param float $newCost
     * @param int $newQuantity
     * @return void
     */
    private function updateCost(float $newCost, int $newQuantity): void
    {
        $oldQuantity = $this->quantity - $newQuantity;
        
        if ($oldQuantity > 0 && $this->average_cost) {
            // Calculate weighted average
            $totalValue = ($oldQuantity * $this->average_cost) + ($newQuantity * $newCost);
            $this->average_cost = round($totalValue / $this->quantity, 4);
        } else {
            // First delivery or no previous cost
            $this->average_cost = $newCost;
        }

        $this->last_cost = $newCost;
        $this->last_cost_update = Carbon::now();
    }

    /**
     * Set delivery status with validation
     *
     * @param string $status
     * @param array $additionalData
     * @return bool
     */
    public function setDeliveryStatus(string $status, array $additionalData = []): bool
    {
        if (!array_key_exists($status, self::DELIVERY_STATUSES)) {
            return false;
        }

        $this->delivery_status = $status;

        // Update related fields based on status
        switch ($status) {
            case 'received':
                $this->last_delivery_date = Carbon::now();
                break;
                
            case 'available':
                if (!$this->last_delivery_date) {
                    $this->last_delivery_date = Carbon::now();
                }
                break;
        }

        // Update additional fields if provided
        foreach ($additionalData as $field => $value) {
            if (in_array($field, $this->fillable)) {
                $this->$field = $value;
            }
        }

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
     * Check if alert should be sent
     *
     * @return bool
     */
    public function shouldSendAlert(): bool
    {
        // Don't send alerts if disabled
        if (!$this->low_stock_alert && !$this->out_of_stock_alert) {
            return false;
        }

        // Don't send alert if one was sent recently (within 24 hours)
        if ($this->last_alert_sent && 
            Carbon::parse($this->last_alert_sent)->diffInHours(Carbon::now()) < 24) {
            return false;
        }

        // Send alert if out of stock and alerts enabled
        if ($this->out_of_stock_alert && $this->is_out_of_stock) {
            return true;
        }

        // Send alert if low stock and alerts enabled
        if ($this->low_stock_alert && $this->is_low_stock) {
            return true;
        }

        return false;
    }

    /**
     * Mark alert as sent
     *
     * @return bool
     */
    public function markAlertSent(): bool
    {
        $this->last_alert_sent = Carbon::now();
        
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

        // Reserved quantity validation
        if ($this->reserved_quantity < 0) {
            $errors[] = 'Reserved quantity cannot be negative';
        }

        if ($this->reserved_quantity > abs($this->quantity)) {
            $errors[] = 'Reserved quantity cannot exceed total quantity';
        }

        // Minimum stock validation
        if ($this->minimum_stock < 0) {
            $errors[] = 'Minimum stock cannot be negative';
        }

        // Maximum stock validation
        if ($this->maximum_stock !== null && $this->maximum_stock < $this->minimum_stock) {
            $errors[] = 'Maximum stock cannot be lower than minimum stock';
        }

        // Reorder validation
        if ($this->reorder_point !== null && $this->reorder_point < 0) {
            $errors[] = 'Reorder point cannot be negative';
        }

        if ($this->reorder_quantity !== null && $this->reorder_quantity <= 0) {
            $errors[] = 'Reorder quantity must be positive';
        }

        // Cost validation
        if ($this->average_cost !== null && $this->average_cost < 0) {
            $errors[] = 'Average cost cannot be negative';
        }

        if ($this->last_cost !== null && $this->last_cost < 0) {
            $errors[] = 'Last cost cannot be negative';
        }

        return $errors;
    }
}