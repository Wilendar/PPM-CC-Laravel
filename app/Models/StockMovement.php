<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

/**
 * StockMovement Model - Historia ruchów magazynowych PPM-CC-Laravel
 *
 * Business Logic:
 * - Complete audit trail wszystkich stock movements
 * - Support dla IN/OUT/TRANSFER/ADJUSTMENT operations
 * - Integration z container tracking i delivery system
 * - User tracking dla accountability
 * - Reference system dla orders, deliveries, adjustments
 * - Cost tracking per movement dla accounting
 *
 * Performance Features:
 * - Strategic indexing dla history queries
 * - Date-based partitioning capability
 * - Optimized dla high-frequency stock operations
 * - JSON casting dla integration data
 *
 * @property int $id
 * @property int $product_id Products.id
 * @property int|null $product_variant_id Product_variants.id (optional)
 * @property int $warehouse_id Warehouses.id
 * @property int $product_stock_id Product_stock.id reference
 * @property string $movement_type Type of movement (in/out/transfer/etc.)
 * @property int $quantity_before Stock before movement
 * @property int $quantity_change Quantity change (+ or -)
 * @property int $quantity_after Stock after movement
 * @property int $reserved_before Reserved before movement
 * @property int $reserved_after Reserved after movement
 * @property int|null $from_warehouse_id Source warehouse (transfers)
 * @property int|null $to_warehouse_id Destination warehouse (transfers)
 * @property float|null $unit_cost Unit cost at movement time
 * @property float|null $total_cost Total cost of movement
 * @property string $currency Currency code
 * @property float $exchange_rate Exchange rate at movement time
 * @property string|null $reference_type Type of reference document
 * @property string|null $reference_id Reference document ID
 * @property string|null $reference_notes Reference information
 * @property string|null $container_number Container tracking
 * @property \Carbon\Carbon|null $delivery_date Delivery date
 * @property string|null $delivery_document Delivery document number
 * @property string|null $location_from Source location
 * @property string|null $location_to Destination location
 * @property string|null $location_notes Location notes
 * @property string|null $reason Business reason
 * @property string|null $notes Additional notes
 * @property bool $is_automatic Movement was automatic
 * @property bool $is_correction Movement is correction/reversal
 * @property array|null $erp_data ERP integration data
 * @property int $created_by User who created movement
 * @property int|null $approved_by User who approved
 * @property \Carbon\Carbon|null $approved_at When approved
 * @property \Carbon\Carbon $movement_date Actual movement date
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\ProductVariant|null $variant
 * @property-read \App\Models\Warehouse $warehouse
 * @property-read \App\Models\ProductStock $productStock
 * @property-read \App\Models\Warehouse|null $fromWarehouse
 * @property-read \App\Models\Warehouse|null $toWarehouse
 * @property-read \App\Models\User $creator
 * @property-read \App\Models\User|null $approver
 * @property-read string $movement_type_label
 * @property-read string $reference_type_label
 * @property-read bool $is_inbound
 * @property-read bool $is_outbound
 * @property-read bool $is_transfer
 * @property-read string $formatted_quantity_change
 * @property-read float $calculated_total_cost
 *
 * @method static \Illuminate\Database\Eloquent\Builder recent(int $days = 30)
 * @method static \Illuminate\Database\Eloquent\Builder byMovementType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder byProduct(int $productId)
 * @method static \Illuminate\Database\Eloquent\Builder byWarehouse(int $warehouseId)
 * @method static \Illuminate\Database\Eloquent\Builder byReference(string $type, string $id)
 * @method static \Illuminate\Database\Eloquent\Builder byContainer(string $containerNumber)
 * @method static \Illuminate\Database\Eloquent\Builder inbound()
 * @method static \Illuminate\Database\Eloquent\Builder outbound()
 * @method static \Illuminate\Database\Eloquent\Builder transfers()
 * @method static \Illuminate\Database\Eloquent\Builder automatic()
 * @method static \Illuminate\Database\Eloquent\Builder manual()
 * @method static \Illuminate\Database\Eloquent\Builder corrections()
 *
 * @package App\Models
 * @version STOCK MANAGEMENT SYSTEM
 * @since 2025-09-17
 */
class StockMovement extends Model
{
    use HasFactory;

    /**
     * Movement type enum values with labels
     */
    public const MOVEMENT_TYPES = [
        'in' => 'Przyjęcie',
        'out' => 'Wydanie',
        'transfer' => 'Transfer',
        'reservation' => 'Rezerwacja',
        'release' => 'Zwolnienie',
        'adjustment' => 'Korekta',
        'return' => 'Zwrot',
        'damage' => 'Uszkodzenie',
        'lost' => 'Utrata',
        'found' => 'Znalezienie',
        'production' => 'Produkcja',
        'correction' => 'Poprawka',
    ];

    /**
     * Reference type enum values with labels
     */
    public const REFERENCE_TYPES = [
        'order' => 'Zamówienie sprzedaży',
        'purchase_order' => 'Zamówienie zakupu',
        'delivery' => 'Dostawa',
        'container' => 'Kontener',
        'adjustment' => 'Korekta manualna',
        'return' => 'Zwrot produktu',
        'transfer' => 'Transfer magazynowy',
        'production' => 'Zlecenie produkcji',
        'inventory' => 'Inwentaryzacja',
        'correction' => 'Korekta danych',
        'integration' => 'Integracja ERP',
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
        'movement_type',
        'quantity_before',
        'quantity_change',
        'quantity_after',
        'reserved_before',
        'reserved_after',
        'from_warehouse_id',
        'to_warehouse_id',
        'unit_cost',
        'total_cost',
        'currency',
        'exchange_rate',
        'reference_type',
        'reference_id',
        'reference_notes',
        'container_number',
        'delivery_date',
        'delivery_document',
        'location_from',
        'location_to',
        'location_notes',
        'reason',
        'notes',
        'is_automatic',
        'is_correction',
        'erp_data',
        'created_by',
        'approved_by',
        'approved_at',
        'movement_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_before' => 'integer',
            'quantity_change' => 'integer',
            'quantity_after' => 'integer',
            'reserved_before' => 'integer',
            'reserved_after' => 'integer',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:4',
            'exchange_rate' => 'decimal:4',
            'is_automatic' => 'boolean',
            'is_correction' => 'boolean',
            'erp_data' => 'array',
            'delivery_date' => 'date',
            'approved_at' => 'datetime',
            'movement_date' => 'datetime',
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
     * Product that was moved
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
     * Warehouse where movement occurred
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Stock record that was affected
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function productStock(): BelongsTo
    {
        return $this->belongsTo(ProductStock::class, 'product_stock_id');
    }

    /**
     * Source warehouse (for transfers)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    /**
     * Destination warehouse (for transfers)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    /**
     * User who created this movement
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who approved this movement
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS
    |--------------------------------------------------------------------------
    */

    /**
     * Get movement type label
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function movementTypeLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => self::MOVEMENT_TYPES[$this->movement_type] ?? $this->movement_type
        );
    }

    /**
     * Get reference type label
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function referenceTypeLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => self::REFERENCE_TYPES[$this->reference_type] ?? ($this->reference_type ?? 'Brak')
        );
    }

    /**
     * Check if movement is inbound
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function isInbound(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => in_array($this->movement_type, ['in', 'return', 'found', 'adjustment']) && $this->quantity_change > 0
        );
    }

    /**
     * Check if movement is outbound
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function isOutbound(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => in_array($this->movement_type, ['out', 'damage', 'lost', 'adjustment']) && $this->quantity_change < 0
        );
    }

    /**
     * Check if movement is transfer
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function isTransfer(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->movement_type === 'transfer'
        );
    }

    /**
     * Get formatted quantity change with + or - sign
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function formattedQuantityChange(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $sign = $this->quantity_change >= 0 ? '+' : '';
                return $sign . number_format($this->quantity_change);
            }
        );
    }

    /**
     * Calculate total cost if not stored
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function calculatedTotalCost(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                if ($this->total_cost !== null) {
                    return $this->total_cost;
                }

                if ($this->unit_cost !== null) {
                    return abs($this->quantity_change) * $this->unit_cost;
                }

                return 0.0;
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Recent movements within specified days
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('movement_date', '>=', Carbon::now()->subDays($days))
                    ->orderBy('movement_date', 'desc');
    }

    /**
     * Scope: Filter by movement type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByMovementType(Builder $query, string $type): Builder
    {
        return $query->where('movement_type', $type);
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
     * Scope: Filter by container number
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $containerNumber
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByContainer(Builder $query, string $containerNumber): Builder
    {
        return $query->where('container_number', $containerNumber);
    }

    /**
     * Scope: Inbound movements only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInbound(Builder $query): Builder
    {
        return $query->whereIn('movement_type', ['in', 'return', 'found'])
                    ->orWhere(function ($q) {
                        $q->where('movement_type', 'adjustment')
                          ->where('quantity_change', '>', 0);
                    });
    }

    /**
     * Scope: Outbound movements only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOutbound(Builder $query): Builder
    {
        return $query->whereIn('movement_type', ['out', 'damage', 'lost'])
                    ->orWhere(function ($q) {
                        $q->where('movement_type', 'adjustment')
                          ->where('quantity_change', '<', 0);
                    });
    }

    /**
     * Scope: Transfer movements only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTransfers(Builder $query): Builder
    {
        return $query->where('movement_type', 'transfer');
    }

    /**
     * Scope: Automatic movements only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAutomatic(Builder $query): Builder
    {
        return $query->where('is_automatic', true);
    }

    /**
     * Scope: Manual movements only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeManual(Builder $query): Builder
    {
        return $query->where('is_automatic', false);
    }

    /**
     * Scope: Correction movements only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCorrections(Builder $query): Builder
    {
        return $query->where('is_correction', true);
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS LOGIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Create stock movement record
     *
     * @param array $movementData
     * @return static
     */
    public static function createMovement(array $movementData): self
    {
        // Auto-calculate total cost if not provided
        if (!isset($movementData['total_cost']) && isset($movementData['unit_cost'], $movementData['quantity_change'])) {
            $movementData['total_cost'] = abs($movementData['quantity_change']) * $movementData['unit_cost'];
        }

        // Set movement date if not provided
        if (!isset($movementData['movement_date'])) {
            $movementData['movement_date'] = Carbon::now();
        }

        return static::create($movementData);
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
     * Approve movement
     *
     * @param int $userId
     * @return bool
     */
    public function approve(int $userId): bool
    {
        $this->approved_by = $userId;
        $this->approved_at = Carbon::now();

        return $this->save();
    }

    /**
     * Check if movement can be reversed
     *
     * @return bool
     */
    public function canReverse(): bool
    {
        // Cannot reverse corrections or already reversed movements
        if ($this->is_correction) {
            return false;
        }

        // Cannot reverse movements older than 24 hours without approval
        if (!$this->approved_by && $this->created_at->diffInHours(Carbon::now()) > 24) {
            return false;
        }

        return true;
    }

    /**
     * Create reversal movement
     *
     * @param string $reason
     * @param int $userId
     * @return static|null
     */
    public function createReversal(string $reason, int $userId): ?self
    {
        if (!$this->canReverse()) {
            return null;
        }

        $reversalData = [
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'warehouse_id' => $this->warehouse_id,
            'product_stock_id' => $this->product_stock_id,
            'movement_type' => 'correction',
            'quantity_before' => $this->quantity_after,
            'quantity_change' => -$this->quantity_change,
            'quantity_after' => $this->quantity_before,
            'reserved_before' => $this->reserved_after,
            'reserved_after' => $this->reserved_before,
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->total_cost ? -$this->total_cost : null,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'reference_type' => 'correction',
            'reference_id' => "REV-{$this->id}",
            'reference_notes' => "Reversal of movement #{$this->id}",
            'reason' => $reason,
            'is_automatic' => false,
            'is_correction' => true,
            'created_by' => $userId,
            'movement_date' => Carbon::now(),
        ];

        return static::createMovement($reversalData);
    }

    /**
     * Get movement summary for display
     *
     * @return array
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->movement_type_label,
            'quantity_change' => $this->formatted_quantity_change,
            'date' => $this->movement_date->format('Y-m-d H:i'),
            'warehouse' => $this->warehouse->name ?? 'Unknown',
            'user' => $this->creator->name ?? 'System',
            'reference' => $this->reference_id ?? 'N/A',
            'reason' => $this->reason ?? 'N/A',
            'cost' => $this->calculated_total_cost,
            'currency' => $this->currency,
        ];
    }
}