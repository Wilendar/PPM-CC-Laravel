<?php

namespace App\Models\Concerns\Product;

use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\StockReservation;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;

/**
 * HasStock Trait - Product Stock Management Business Logic
 *
 * Responsibility: Multi-warehouse stock tracking and inventory operations
 *
 * Features:
 * - Stock levels per warehouse (multi-warehouse support)
 * - Stock movements history (IN/OUT/TRANSFER)
 * - Stock reservations (priority-based queue)
 * - Stock availability checks
 * - Low stock alerts
 * - Stock statistics and turnover rate
 *
 * Performance: Optimized dla inventory operations z proper indexing
 * Integration: ERP systems mapping ready
 *
 * @package App\Models\Concerns\Product
 * @version 1.0
 * @since ETAP_05a SEKCJA 0 - Product.php Refactoring
 */
trait HasStock
{
    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS - Stock Relations
    |--------------------------------------------------------------------------
    */

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
    | ACCESSORS - Computed Stock Attributes
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | BUSINESS METHODS - Stock Operations
    |--------------------------------------------------------------------------
    */

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
     * Get warehouses where product is in stock
     *
     * @param int $minQuantity Minimum required quantity
     * @return \Illuminate\Support\Collection
     */
    public function getWarehousesInStock(int $minQuantity = 1): Collection
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
    public function getWarehousesWithStock(?int $minQuantity = 1): Collection
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
    public function getRecentMovements(int $days = 7): Collection
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
    public function getLowStockAlerts(): Collection
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
}
