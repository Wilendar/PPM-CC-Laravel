<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

/**
 * StockReservation Model - System rezerwacji stanów PPM-CC-Laravel
 *
 * Business Logic:
 * - Detailed stock reservations dla orders/quotes
 * - Time-based reservation expiry system
 * - Priority-based reservation queue
 * - Integration z order management system
 * - Automatic release dla expired reservations
 * - Support dla partial fulfillment
 *
 * Performance Features:
 * - Strategic indexing dla reservation queries
 * - Expiry date indexes dla cleanup jobs
 * - Optimized dla high-frequency reservation operations
 * - Composite unique constraints dla data integrity
 *
 * @property int $id
 * @property int $product_id Products.id
 * @property int|null $product_variant_id Product_variants.id (optional)
 * @property int $warehouse_id Warehouses.id
 * @property int $product_stock_id Product_stock.id reference
 * @property string $reservation_number Unique reservation identifier
 * @property string $reservation_type Type of reservation
 * @property int $quantity_requested Originally requested quantity
 * @property int $quantity_reserved Actually reserved quantity
 * @property int $quantity_fulfilled Quantity fulfilled/shipped
 * @property int $quantity_remaining Remaining reserved quantity (computed)
 * @property \Carbon\Carbon $reserved_at When reservation was created
 * @property \Carbon\Carbon|null $expires_at When reservation expires
 * @property \Carbon\Carbon|null $fulfilled_at When fully fulfilled
 * @property int|null $duration_minutes Reservation duration
 * @property string $status Reservation status
 * @property int $priority Reservation priority (1-10)
 * @property bool $auto_release Auto release when expired
 * @property string|null $reference_type Type of reference document
 * @property string|null $reference_id Reference document ID
 * @property string|null $reference_line_id Reference line item ID
 * @property string|null $reference_notes Reference information
 * @property string|null $customer_id Customer identifier
 * @property string|null $customer_name Customer name
 * @property string|null $sales_person Salesperson
 * @property string|null $department Requesting department
 * @property float|null $unit_price Unit price at reservation
 * @property float|null $total_value Total reservation value
 * @property string $currency Currency code
 * @property string|null $price_group Price group used
 * @property \Carbon\Carbon|null $requested_delivery_date Customer requested delivery
 * @property \Carbon\Carbon|null $promised_delivery_date Promised delivery
 * @property string|null $delivery_method Delivery method
 * @property string|null $delivery_address Delivery address
 * @property string|null $delivery_notes Delivery instructions
 * @property string|null $reason Business reason
 * @property string|null $special_instructions Special instructions
 * @property string|null $notes Additional notes
 * @property bool $is_firm Firm reservation (cannot auto-release)
 * @property bool $allow_partial Allow partial fulfillment
 * @property bool $notify_expiry Send notification before expiry
 * @property array|null $erp_data ERP integration data
 * @property int $reserved_by User who created reservation
 * @property int|null $confirmed_by User who confirmed
 * @property \Carbon\Carbon|null $confirmed_at When confirmed
 * @property int|null $released_by User who released
 * @property \Carbon\Carbon|null $released_at When released
 * @property string|null $release_reason Reason for release
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\ProductVariant|null $variant
 * @property-read \App\Models\Warehouse $warehouse
 * @property-read \App\Models\ProductStock $productStock
 * @property-read \App\Models\User $reserver
 * @property-read \App\Models\User|null $confirmer
 * @property-read \App\Models\User|null $releaser
 * @property-read string $reservation_type_label
 * @property-read string $status_label
 * @property-read string $priority_label
 * @property-read bool $is_expired
 * @property-read bool $is_active
 * @property-read bool $needs_attention
 * @property-read float $fulfillment_percentage
 * @property-read int $minutes_until_expiry
 * @property-read string $formatted_total_value
 *
 * @method static \Illuminate\Database\Eloquent\Builder active()
 * @method static \Illuminate\Database\Eloquent\Builder expired()
 * @method static \Illuminate\Database\Eloquent\Builder expiringSoon(int $hours = 24)
 * @method static \Illuminate\Database\Eloquent\Builder byStatus(string $status)
 * @method static \Illuminate\Database\Eloquent\Builder byProduct(int $productId)
 * @method static \Illuminate\Database\Eloquent\Builder byWarehouse(int $warehouseId)
 * @method static \Illuminate\Database\Eloquent\Builder byCustomer(string $customerId)
 * @method static \Illuminate\Database\Eloquent\Builder byReference(string $type, string $id)
 * @method static \Illuminate\Database\Eloquent\Builder byPriority(int $priority)
 * @method static \Illuminate\Database\Eloquent\Builder highPriority()
 * @method static \Illuminate\Database\Eloquent\Builder needsAttention()
 *
 * @package App\Models
 * @version STOCK MANAGEMENT SYSTEM
 * @since 2025-09-17
 */
class StockReservation extends Model
{
    use HasFactory;

    /**
     * Reservation type enum values with labels
     */
    public const RESERVATION_TYPES = [
        'order' => 'Zamówienie sprzedaży',
        'quote' => 'Oferta',
        'pre_order' => 'Zamówienie przedpłatowe',
        'allocation' => 'Alokacja manualna',
        'production' => 'Rezerwacja produkcji',
        'transfer' => 'Rezerwacja transferu',
        'sample' => 'Próbka/Demo',
        'warranty' => 'Wymiana gwarancyjna',
        'exchange' => 'Wymiana produktu',
        'temp' => 'Rezerwacja tymczasowa',
    ];

    /**
     * Status enum values with labels
     */
    public const STATUSES = [
        'pending' => 'Oczekuje potwierdzenia',
        'confirmed' => 'Potwierdzone',
        'partial' => 'Częściowo zrealizowane',
        'fulfilled' => 'Zrealizowane',
        'expired' => 'Wygasłe',
        'cancelled' => 'Anulowane',
        'on_hold' => 'Wstrzymane',
        'processing' => 'W trakcie realizacji',
    ];

    /**
     * Reference type enum values with labels
     */
    public const REFERENCE_TYPES = [
        'sales_order' => 'Zamówienie sprzedaży',
        'quote' => 'Oferta dla klienta',
        'internal_order' => 'Zamówienie wewnętrzne',
        'production_order' => 'Zlecenie produkcji',
        'transfer_request' => 'Wniosek o transfer',
        'sample_request' => 'Zapytanie o próbkę',
        'warranty_claim' => 'Reklamacja gwarancyjna',
        'exchange_request' => 'Wniosek o wymianę',
        'manual' => 'Rezerwacja manualna',
    ];

    /**
     * Priority levels with labels
     */
    public const PRIORITIES = [
        1 => 'Najwyższy',
        2 => 'Bardzo wysoki',
        3 => 'Wysoki',
        4 => 'Średni-wysoki',
        5 => 'Średni',
        6 => 'Średni-niski',
        7 => 'Niski',
        8 => 'Bardzo niski',
        9 => 'Najniższy',
        10 => 'Backup',
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
        'product_stock_id',
        'reservation_number',
        'reservation_type',
        'quantity_requested',
        'quantity_reserved',
        'quantity_fulfilled',
        'reserved_at',
        'expires_at',
        'fulfilled_at',
        'duration_minutes',
        'status',
        'priority',
        'auto_release',
        'reference_type',
        'reference_id',
        'reference_line_id',
        'reference_notes',
        'customer_id',
        'customer_name',
        'sales_person',
        'department',
        'unit_price',
        'total_value',
        'currency',
        'price_group',
        'requested_delivery_date',
        'promised_delivery_date',
        'delivery_method',
        'delivery_address',
        'delivery_notes',
        'reason',
        'special_instructions',
        'notes',
        'is_firm',
        'allow_partial',
        'notify_expiry',
        'erp_data',
        'reserved_by',
        'confirmed_by',
        'confirmed_at',
        'released_by',
        'released_at',
        'release_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_requested' => 'integer',
            'quantity_reserved' => 'integer',
            'quantity_fulfilled' => 'integer',
            'duration_minutes' => 'integer',
            'priority' => 'integer',
            'auto_release' => 'boolean',
            'unit_price' => 'decimal:4',
            'total_value' => 'decimal:4',
            'is_firm' => 'boolean',
            'allow_partial' => 'boolean',
            'notify_expiry' => 'boolean',
            'erp_data' => 'array',
            'reserved_at' => 'datetime',
            'expires_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'released_at' => 'datetime',
            'requested_delivery_date' => 'date',
            'promised_delivery_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($reservation) {
            // Generate reservation number if not provided
            if (!$reservation->reservation_number) {
                $reservation->reservation_number = static::generateReservationNumber();
            }

            // Set reserved_at if not provided
            if (!$reservation->reserved_at) {
                $reservation->reserved_at = Carbon::now();
            }

            // Calculate expiry based on duration
            if (!$reservation->expires_at && $reservation->duration_minutes) {
                $reservation->expires_at = Carbon::parse($reservation->reserved_at)
                    ->addMinutes($reservation->duration_minutes);
            }

            // Calculate total value if not provided
            if (!$reservation->total_value && $reservation->unit_price) {
                $reservation->total_value = $reservation->quantity_reserved * $reservation->unit_price;
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Product being reserved
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Product variant (if applicable)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Warehouse where stock is reserved
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Stock record being reserved
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function productStock(): BelongsTo
    {
        return $this->belongsTo(ProductStock::class, 'product_stock_id');
    }

    /**
     * User who made the reservation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reserver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reserved_by');
    }

    /**
     * User who confirmed the reservation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * User who released the reservation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function releaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS
    |--------------------------------------------------------------------------
    */

    /**
     * Get reservation type label
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function reservationTypeLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => self::RESERVATION_TYPES[$this->reservation_type] ?? $this->reservation_type
        );
    }

    /**
     * Get status label
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => self::STATUSES[$this->status] ?? $this->status
        );
    }

    /**
     * Get priority label
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function priorityLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => self::PRIORITIES[$this->priority] ?? "Priorytet {$this->priority}"
        );
    }

    /**
     * Check if reservation is expired
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function isExpired(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                if (!$this->expires_at) {
                    return false;
                }

                return Carbon::now()->greaterThan($this->expires_at) &&
                       !in_array($this->status, ['fulfilled', 'cancelled']);
            }
        );
    }

    /**
     * Check if reservation is active
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function isActive(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => in_array($this->status, ['pending', 'confirmed', 'partial', 'processing'])
        );
    }

    /**
     * Check if reservation needs attention
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function needsAttention(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                // High priority reservations
                if ($this->priority <= 3) {
                    return true;
                }

                // Expiring soon
                if ($this->expires_at && $this->expires_at->diffInHours(Carbon::now()) <= 24) {
                    return true;
                }

                // Overdue promised delivery
                if ($this->promised_delivery_date &&
                    Carbon::now()->greaterThan($this->promised_delivery_date) &&
                    $this->status !== 'fulfilled') {
                    return true;
                }

                return false;
            }
        );
    }

    /**
     * Calculate fulfillment percentage
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function fulfillmentPercentage(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                if ($this->quantity_reserved <= 0) {
                    return 0.0;
                }

                return round(($this->quantity_fulfilled / $this->quantity_reserved) * 100, 2);
            }
        );
    }

    /**
     * Minutes until expiry
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function minutesUntilExpiry(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                if (!$this->expires_at) {
                    return -1; // No expiry
                }

                $diff = Carbon::now()->diffInMinutes($this->expires_at, false);
                return max(0, $diff);
            }
        );
    }

    /**
     * Formatted total value with currency
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function formattedTotalValue(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if (!$this->total_value) {
                    return 'N/A';
                }

                return number_format($this->total_value, 2) . ' ' . $this->currency;
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Active reservations
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'confirmed', 'partial', 'processing']);
    }

    /**
     * Scope: Expired reservations
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', Carbon::now())
                    ->whereNotIn('status', ['fulfilled', 'cancelled']);
    }

    /**
     * Scope: Expiring soon
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $hours
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpiringSoon(Builder $query, int $hours = 24): Builder
    {
        return $query->whereBetween('expires_at', [
                        Carbon::now(),
                        Carbon::now()->addHours($hours)
                    ])
                    ->whereIn('status', ['pending', 'confirmed', 'partial']);
    }

    /**
     * Scope: Filter by status
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
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
     * Scope: Filter by customer
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $customerId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCustomer(Builder $query, string $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope: Filter by reference
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @param string $id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByReference(Builder $query, string $type, string $id): Builder
    {
        return $query->where('reference_type', $type)
                    ->where('reference_id', $id);
    }

    /**
     * Scope: Filter by priority
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $priority
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPriority(Builder $query, int $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope: High priority reservations (1-3)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->where('priority', '<=', 3);
    }

    /**
     * Scope: Reservations needing attention
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNeedsAttention(Builder $query): Builder
    {
        return $query->where(function ($q) {
                $q->where('priority', '<=', 3)
                  ->orWhere('expires_at', '<=', Carbon::now()->addDay())
                  ->orWhere(function ($subQ) {
                      $subQ->where('promised_delivery_date', '<', Carbon::now())
                           ->whereNotIn('status', ['fulfilled', 'cancelled']);
                  });
            });
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Generate unique reservation number
     *
     * @param string $prefix
     * @return string
     */
    public static function generateReservationNumber(string $prefix = 'RES'): string
    {
        $date = Carbon::now()->format('Ymd');
        $sequence = static::whereDate('created_at', Carbon::today())->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS LOGIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Confirm reservation
     *
     * @param int $userId
     * @return bool
     */
    public function confirm(int $userId): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->status = 'confirmed';
        $this->confirmed_by = $userId;
        $this->confirmed_at = Carbon::now();

        return $this->save();
    }

    /**
     * Partially fulfill reservation
     *
     * @param int $quantity
     * @return bool
     */
    public function partiallyFulfill(int $quantity): bool
    {
        if (!$this->allow_partial || $quantity <= 0) {
            return false;
        }

        if ($this->quantity_fulfilled + $quantity > $this->quantity_reserved) {
            return false;
        }

        $this->quantity_fulfilled += $quantity;

        if ($this->quantity_fulfilled >= $this->quantity_reserved) {
            $this->status = 'fulfilled';
            $this->fulfilled_at = Carbon::now();
        } else {
            $this->status = 'partial';
        }

        return $this->save();
    }

    /**
     * Release reservation
     *
     * @param string $reason
     * @param int $userId
     * @return bool
     */
    public function release(string $reason, int $userId): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $this->status = 'cancelled';
        $this->released_by = $userId;
        $this->released_at = Carbon::now();
        $this->release_reason = $reason;

        return $this->save();
    }

    /**
     * Extend expiry time
     *
     * @param int $additionalMinutes
     * @return bool
     */
    public function extend(int $additionalMinutes): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        $this->expires_at = $this->expires_at->addMinutes($additionalMinutes);

        return $this->save();
    }

    /**
     * Get ERP mapping for specific system
     *
     * @param string $erpSystem
     * @return array|null
     */
    public function getErpMapping(string $erpSystem): ?array
    {
        if (!$this->erp_data || !is_array($this->erp_data)) {
            return null;
        }

        return $this->erp_data[$erpSystem] ?? null;
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
        $erpData = $this->erp_data ?? [];
        $erpData[$erpSystem] = $mapping;

        $this->erp_data = $erpData;

        return $this->save();
    }

    /**
     * Get reservation summary for display
     *
     * @return array
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->reservation_number,
            'type' => $this->reservation_type_label,
            'status' => $this->status_label,
            'priority' => $this->priority_label,
            'product' => $this->product->name ?? 'Unknown',
            'warehouse' => $this->warehouse->name ?? 'Unknown',
            'quantity_reserved' => $this->quantity_reserved,
            'quantity_remaining' => $this->quantity_remaining,
            'fulfillment' => $this->fulfillment_percentage . '%',
            'customer' => $this->customer_name ?? 'N/A',
            'reserved_at' => $this->reserved_at->format('Y-m-d H:i'),
            'expires_at' => $this->expires_at?->format('Y-m-d H:i') ?? 'Brak',
            'value' => $this->formatted_total_value,
            'needs_attention' => $this->needs_attention,
        ];
    }
}