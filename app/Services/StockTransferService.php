<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Warehouse;
use App\Models\StockMovement;
use App\Models\StockReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * StockTransferService - Enterprise Stock Transfer System
 *
 * STOCK MANAGEMENT SYSTEM - Advanced stock transfer operations
 *
 * Business Logic:
 * - Atomic stock transfers between warehouses
 * - Validation dla available quantities i business rules
 * - Complete audit trail z stock movements
 * - Integration z reservation system
 * - Support dla partial transfers
 * - Cost tracking z movement history
 * - Multi-warehouse availability checks
 *
 * Performance Features:
 * - Transaction-based operations dla data integrity
 * - Batch transfer capabilities
 * - Optimized database queries
 * - Comprehensive error handling and rollback
 *
 * @package App\Services
 * @version STOCK MANAGEMENT SYSTEM
 * @since 2025-09-17
 */
class StockTransferService
{
    /**
     * Transfer result status codes
     */
    public const TRANSFER_SUCCESS = 'success';
    public const TRANSFER_FAILED = 'failed';
    public const TRANSFER_PARTIAL = 'partial';
    public const TRANSFER_INSUFFICIENT_STOCK = 'insufficient_stock';
    public const TRANSFER_VALIDATION_ERROR = 'validation_error';
    public const TRANSFER_WAREHOUSE_ERROR = 'warehouse_error';

    /**
     * Transfer single product between warehouses
     *
     * @param int $productId
     * @param int|null $productVariantId
     * @param int $fromWarehouseId
     * @param int $toWarehouseId
     * @param int $quantity
     * @param array $options
     * @return array
     */
    public function transferProduct(
        int $productId,
        ?int $productVariantId,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $quantity,
        array $options = []
    ): array {
        try {
            DB::beginTransaction();

            // Validate transfer request
            $validation = $this->validateTransferRequest(
                $productId,
                $productVariantId,
                $fromWarehouseId,
                $toWarehouseId,
                $quantity,
                $options
            );

            if (!$validation['valid']) {
                DB::rollBack();
                return [
                    'status' => self::TRANSFER_VALIDATION_ERROR,
                    'message' => $validation['message'],
                    'errors' => $validation['errors'],
                ];
            }

            // Get stock records
            $fromStock = $this->getOrCreateStockRecord($productId, $productVariantId, $fromWarehouseId);
            $toStock = $this->getOrCreateStockRecord($productId, $productVariantId, $toWarehouseId);

            // Check available quantity
            if ($fromStock->available_quantity < $quantity) {
                DB::rollBack();
                return [
                    'status' => self::TRANSFER_INSUFFICIENT_STOCK,
                    'message' => "Insufficient stock. Available: {$fromStock->available_quantity}, Requested: {$quantity}",
                    'available_quantity' => $fromStock->available_quantity,
                ];
            }

            // Perform transfer
            $result = $this->executeTransfer(
                $fromStock,
                $toStock,
                $quantity,
                $options
            );

            if ($result['success']) {
                DB::commit();

                Log::info('Stock transfer completed successfully', [
                    'product_id' => $productId,
                    'variant_id' => $productVariantId,
                    'from_warehouse' => $fromWarehouseId,
                    'to_warehouse' => $toWarehouseId,
                    'quantity' => $quantity,
                    'movements' => $result['movement_ids'],
                ]);

                return [
                    'status' => self::TRANSFER_SUCCESS,
                    'message' => 'Stock transfer completed successfully',
                    'transfer_data' => $result,
                ];
            }

            DB::rollBack();
            return [
                'status' => self::TRANSFER_FAILED,
                'message' => $result['message'] ?? 'Transfer failed',
                'errors' => $result['errors'] ?? [],
            ];

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Stock transfer failed with exception', [
                'product_id' => $productId,
                'variant_id' => $productVariantId,
                'from_warehouse' => $fromWarehouseId,
                'to_warehouse' => $toWarehouseId,
                'quantity' => $quantity,
                'exception' => $e->getMessage(),
            ]);

            return [
                'status' => self::TRANSFER_FAILED,
                'message' => 'Transfer failed: ' . $e->getMessage(),
                'exception' => $e->getMessage(),
            ];
        }
    }

    /**
     * Batch transfer multiple products
     *
     * @param array $transfers
     * @param array $options
     * @return array
     */
    public function batchTransfer(array $transfers, array $options = []): array
    {
        $results = [];
        $successCount = 0;
        $failCount = 0;

        try {
            DB::beginTransaction();

            foreach ($transfers as $index => $transfer) {
                $result = $this->transferProduct(
                    $transfer['product_id'],
                    $transfer['product_variant_id'] ?? null,
                    $transfer['from_warehouse_id'],
                    $transfer['to_warehouse_id'],
                    $transfer['quantity'],
                    array_merge($options, $transfer['options'] ?? [])
                );

                $results[$index] = $result;

                if ($result['status'] === self::TRANSFER_SUCCESS) {
                    $successCount++;
                } else {
                    $failCount++;

                    // If stop_on_error is enabled, rollback all and return
                    if ($options['stop_on_error'] ?? false) {
                        DB::rollBack();
                        return [
                            'status' => self::TRANSFER_FAILED,
                            'message' => "Batch transfer stopped on error at index {$index}",
                            'results' => $results,
                            'summary' => [
                                'total' => count($transfers),
                                'success' => $successCount,
                                'failed' => $failCount,
                            ],
                        ];
                    }
                }
            }

            DB::commit();

            $status = $failCount === 0 ? self::TRANSFER_SUCCESS :
                     ($successCount === 0 ? self::TRANSFER_FAILED : self::TRANSFER_PARTIAL);

            return [
                'status' => $status,
                'message' => "Batch transfer completed. Success: {$successCount}, Failed: {$failCount}",
                'results' => $results,
                'summary' => [
                    'total' => count($transfers),
                    'success' => $successCount,
                    'failed' => $failCount,
                ],
            ];

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Batch stock transfer failed', [
                'transfers_count' => count($transfers),
                'exception' => $e->getMessage(),
            ]);

            return [
                'status' => self::TRANSFER_FAILED,
                'message' => 'Batch transfer failed: ' . $e->getMessage(),
                'results' => $results,
                'exception' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get available stock across all warehouses dla specific product
     *
     * @param int $productId
     * @param int|null $productVariantId
     * @return array
     */
    public function getAvailableStock(int $productId, ?int $productVariantId = null): array
    {
        $query = ProductStock::with(['warehouse'])
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->where('track_stock', true);

        if ($productVariantId) {
            $query->where('product_variant_id', $productVariantId);
        } else {
            $query->whereNull('product_variant_id');
        }

        $stockRecords = $query->get();

        $result = [
            'product_id' => $productId,
            'product_variant_id' => $productVariantId,
            'total_quantity' => 0,
            'total_available' => 0,
            'total_reserved' => 0,
            'warehouses' => [],
        ];

        foreach ($stockRecords as $stock) {
            $warehouseData = [
                'warehouse_id' => $stock->warehouse_id,
                'warehouse_name' => $stock->warehouse->name,
                'warehouse_code' => $stock->warehouse->code,
                'quantity' => $stock->quantity,
                'reserved_quantity' => $stock->reserved_quantity,
                'available_quantity' => $stock->available_quantity,
                'minimum_stock' => $stock->minimum_stock,
                'is_low_stock' => $stock->is_low_stock,
                'delivery_status' => $stock->delivery_status,
            ];

            $result['warehouses'][] = $warehouseData;
            $result['total_quantity'] += $stock->quantity;
            $result['total_available'] += $stock->available_quantity;
            $result['total_reserved'] += $stock->reserved_quantity;
        }

        return $result;
    }

    /**
     * Find best warehouse dla stock allocation
     *
     * @param int $productId
     * @param int|null $productVariantId
     * @param int $requiredQuantity
     * @param array $criteria
     * @return array|null
     */
    public function findOptimalWarehouse(
        int $productId,
        ?int $productVariantId,
        int $requiredQuantity,
        array $criteria = []
    ): ?array {
        $availableStock = $this->getAvailableStock($productId, $productVariantId);

        if ($availableStock['total_available'] < $requiredQuantity) {
            return null; // Not enough stock in any warehouse
        }

        $suitableWarehouses = array_filter(
            $availableStock['warehouses'],
            fn($w) => $w['available_quantity'] >= $requiredQuantity
        );

        if (empty($suitableWarehouses)) {
            return null; // No single warehouse has enough stock
        }

        // Sort by criteria (priority: default warehouse > highest stock > lowest warehouse_id)
        usort($suitableWarehouses, function ($a, $b) use ($criteria) {
            // Priority 1: Default warehouse
            $aDefault = Warehouse::find($a['warehouse_id'])->is_default ?? false;
            $bDefault = Warehouse::find($b['warehouse_id'])->is_default ?? false;

            if ($aDefault !== $bDefault) {
                return $bDefault <=> $aDefault;
            }

            // Priority 2: Avoid low stock warehouses
            if ($a['is_low_stock'] !== $b['is_low_stock']) {
                return $a['is_low_stock'] <=> $b['is_low_stock'];
            }

            // Priority 3: Higher available quantity
            if ($a['available_quantity'] !== $b['available_quantity']) {
                return $b['available_quantity'] <=> $a['available_quantity'];
            }

            // Priority 4: Lower warehouse ID (first created)
            return $a['warehouse_id'] <=> $b['warehouse_id'];
        });

        return $suitableWarehouses[0];
    }

    /**
     * Suggest transfer to balance stock across warehouses
     *
     * @param int $productId
     * @param int|null $productVariantId
     * @return array
     */
    public function suggestRebalancing(int $productId, ?int $productVariantId = null): array
    {
        $availableStock = $this->getAvailableStock($productId, $productVariantId);

        if (count($availableStock['warehouses']) < 2) {
            return [
                'suggestions' => [],
                'message' => 'Not enough warehouses for rebalancing',
            ];
        }

        $warehouses = $availableStock['warehouses'];
        $totalStock = $availableStock['total_available'];
        $warehouseCount = count($warehouses);
        $targetPerWarehouse = intval($totalStock / $warehouseCount);

        $suggestions = [];

        // Find overstock and understock warehouses
        $overstock = [];
        $understock = [];

        foreach ($warehouses as $warehouse) {
            $difference = $warehouse['available_quantity'] - $targetPerWarehouse;

            if ($difference > 1) { // Has excess stock
                $overstock[] = [
                    'warehouse' => $warehouse,
                    'excess' => $difference,
                ];
            } elseif ($difference < -1) { // Needs more stock
                $understock[] = [
                    'warehouse' => $warehouse,
                    'deficit' => abs($difference),
                ];
            }
        }

        // Generate transfer suggestions
        foreach ($overstock as $over) {
            foreach ($understock as $under) {
                $transferQuantity = min($over['excess'], $under['deficit']);

                if ($transferQuantity > 0) {
                    $suggestions[] = [
                        'from_warehouse_id' => $over['warehouse']['warehouse_id'],
                        'from_warehouse_name' => $over['warehouse']['warehouse_name'],
                        'to_warehouse_id' => $under['warehouse']['warehouse_id'],
                        'to_warehouse_name' => $under['warehouse']['warehouse_name'],
                        'suggested_quantity' => $transferQuantity,
                        'reason' => 'Stock balancing',
                        'priority' => $this->calculateTransferPriority($over['warehouse'], $under['warehouse']),
                    ];

                    // Reduce remaining excess/deficit
                    $over['excess'] -= $transferQuantity;
                    $under['deficit'] -= $transferQuantity;

                    if ($over['excess'] <= 0) break;
                }
            }
        }

        return [
            'suggestions' => $suggestions,
            'current_distribution' => $warehouses,
            'target_per_warehouse' => $targetPerWarehouse,
            'total_available' => $totalStock,
            'message' => count($suggestions) > 0 ?
                'Found ' . count($suggestions) . ' rebalancing opportunities' :
                'Stock is already well balanced',
        ];
    }

    /**
     * Reserve stock for future transfer
     *
     * @param int $productId
     * @param int|null $productVariantId
     * @param int $warehouseId
     * @param int $quantity
     * @param array $reservationData
     * @return array
     */
    public function reserveForTransfer(
        int $productId,
        ?int $productVariantId,
        int $warehouseId,
        int $quantity,
        array $reservationData = []
    ): array {
        try {
            $stock = $this->getOrCreateStockRecord($productId, $productVariantId, $warehouseId);

            if ($stock->available_quantity < $quantity) {
                return [
                    'success' => false,
                    'message' => 'Insufficient available stock for reservation',
                    'available_quantity' => $stock->available_quantity,
                ];
            }

            // Create reservation
            $reservation = StockReservation::create([
                'product_id' => $productId,
                'product_variant_id' => $productVariantId,
                'warehouse_id' => $warehouseId,
                'product_stock_id' => $stock->id,
                'reservation_type' => 'transfer',
                'quantity_requested' => $quantity,
                'quantity_reserved' => $quantity,
                'status' => 'confirmed',
                'priority' => $reservationData['priority'] ?? 5,
                'reason' => 'Stock transfer reservation',
                'reference_type' => 'transfer_request',
                'reference_id' => $reservationData['transfer_id'] ?? null,
                'reserved_by' => $reservationData['user_id'] ?? auth()->id(),
                'expires_at' => Carbon::now()->addHours($reservationData['hours'] ?? 24),
                'notes' => $reservationData['notes'] ?? 'Reserved for warehouse transfer',
            ]);

            // Update stock reservation
            $stock->reserved_quantity += $quantity;
            $stock->save();

            return [
                'success' => true,
                'reservation_id' => $reservation->id,
                'reservation_number' => $reservation->reservation_number,
                'message' => 'Stock reserved successfully for transfer',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to reserve stock: ' . $e->getMessage(),
                'exception' => $e->getMessage(),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PRIVATE METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Validate transfer request
     *
     * @param int $productId
     * @param int|null $productVariantId
     * @param int $fromWarehouseId
     * @param int $toWarehouseId
     * @param int $quantity
     * @param array $options
     * @return array
     */
    private function validateTransferRequest(
        int $productId,
        ?int $productVariantId,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $quantity,
        array $options = []
    ): array {
        $errors = [];

        // Basic validation
        if ($quantity <= 0) {
            $errors[] = 'Transfer quantity must be positive';
        }

        if ($fromWarehouseId === $toWarehouseId) {
            $errors[] = 'Source and destination warehouses cannot be the same';
        }

        // Validate product exists
        if (!Product::find($productId)) {
            $errors[] = 'Product not found';
        }

        // Validate warehouses exist and are active
        $fromWarehouse = Warehouse::find($fromWarehouseId);
        $toWarehouse = Warehouse::find($toWarehouseId);

        if (!$fromWarehouse) {
            $errors[] = 'Source warehouse not found';
        } elseif (!$fromWarehouse->is_active) {
            $errors[] = 'Source warehouse is not active';
        }

        if (!$toWarehouse) {
            $errors[] = 'Destination warehouse not found';
        } elseif (!$toWarehouse->is_active) {
            $errors[] = 'Destination warehouse is not active';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'message' => empty($errors) ? 'Validation passed' : 'Validation failed: ' . implode(', ', $errors),
        ];
    }

    /**
     * Execute the actual stock transfer
     *
     * @param ProductStock $fromStock
     * @param ProductStock $toStock
     * @param int $quantity
     * @param array $options
     * @return array
     */
    private function executeTransfer(ProductStock $fromStock, ProductStock $toStock, int $quantity, array $options): array
    {
        try {
            // Store original quantities for movement records
            $fromQuantityBefore = $fromStock->quantity;
            $toQuantityBefore = $toStock->quantity;

            // Calculate average cost for transfer
            $transferCost = $fromStock->average_cost ?? 0.0;

            // Update source stock (subtract)
            $fromStock->quantity -= $quantity;
            $fromStock->movements_count++;
            $fromStock->last_movement_at = Carbon::now();
            $fromStock->save();

            // Update destination stock (add) with cost calculation
            if ($toStock->quantity > 0 && $toStock->average_cost && $transferCost > 0) {
                // Calculate weighted average cost
                $totalValue = ($toStock->quantity * $toStock->average_cost) + ($quantity * $transferCost);
                $newTotalQuantity = $toStock->quantity + $quantity;
                $toStock->average_cost = $totalValue / $newTotalQuantity;
            } elseif ($transferCost > 0) {
                $toStock->average_cost = $transferCost;
            }

            $toStock->quantity += $quantity;
            $toStock->movements_count++;
            $toStock->last_movement_at = Carbon::now();
            $toStock->save();

            // Create outbound movement (from source)
            $outMovement = StockMovement::createMovement([
                'product_id' => $fromStock->product_id,
                'product_variant_id' => $fromStock->product_variant_id,
                'warehouse_id' => $fromStock->warehouse_id,
                'product_stock_id' => $fromStock->id,
                'movement_type' => 'transfer',
                'quantity_before' => $fromQuantityBefore,
                'quantity_change' => -$quantity,
                'quantity_after' => $fromStock->quantity,
                'reserved_before' => $fromStock->reserved_quantity,
                'reserved_after' => $fromStock->reserved_quantity,
                'from_warehouse_id' => $fromStock->warehouse_id,
                'to_warehouse_id' => $toStock->warehouse_id,
                'unit_cost' => $transferCost,
                'total_cost' => $quantity * $transferCost,
                'currency' => 'PLN',
                'reference_type' => 'transfer',
                'reference_id' => $options['reference_id'] ?? "TRANS-" . time(),
                'reason' => $options['reason'] ?? 'Stock transfer between warehouses',
                'notes' => $options['notes'] ?? null,
                'is_automatic' => $options['is_automatic'] ?? false,
                'created_by' => $options['user_id'] ?? auth()->id(),
            ]);

            // Create inbound movement (to destination)
            $inMovement = StockMovement::createMovement([
                'product_id' => $toStock->product_id,
                'product_variant_id' => $toStock->product_variant_id,
                'warehouse_id' => $toStock->warehouse_id,
                'product_stock_id' => $toStock->id,
                'movement_type' => 'transfer',
                'quantity_before' => $toQuantityBefore,
                'quantity_change' => $quantity,
                'quantity_after' => $toStock->quantity,
                'reserved_before' => $toStock->reserved_quantity,
                'reserved_after' => $toStock->reserved_quantity,
                'from_warehouse_id' => $fromStock->warehouse_id,
                'to_warehouse_id' => $toStock->warehouse_id,
                'unit_cost' => $transferCost,
                'total_cost' => $quantity * $transferCost,
                'currency' => 'PLN',
                'reference_type' => 'transfer',
                'reference_id' => $options['reference_id'] ?? "TRANS-" . time(),
                'reason' => $options['reason'] ?? 'Stock transfer between warehouses',
                'notes' => $options['notes'] ?? null,
                'is_automatic' => $options['is_automatic'] ?? false,
                'created_by' => $options['user_id'] ?? auth()->id(),
            ]);

            return [
                'success' => true,
                'from_stock_id' => $fromStock->id,
                'to_stock_id' => $toStock->id,
                'quantity_transferred' => $quantity,
                'transfer_cost' => $transferCost,
                'movement_ids' => [$outMovement->id, $inMovement->id],
                'from_quantity_after' => $fromStock->quantity,
                'to_quantity_after' => $toStock->quantity,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Transfer execution failed: ' . $e->getMessage(),
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Get or create stock record for product in warehouse
     *
     * @param int $productId
     * @param int|null $productVariantId
     * @param int $warehouseId
     * @return ProductStock
     */
    private function getOrCreateStockRecord(int $productId, ?int $productVariantId, int $warehouseId): ProductStock
    {
        $stock = ProductStock::where('product_id', $productId)
            ->where('product_variant_id', $productVariantId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (!$stock) {
            $stock = ProductStock::create([
                'product_id' => $productId,
                'product_variant_id' => $productVariantId,
                'warehouse_id' => $warehouseId,
                'quantity' => 0,
                'reserved_quantity' => 0,
                'minimum_stock' => 0,
                'is_active' => true,
                'track_stock' => true,
                'created_by' => auth()->id(),
            ]);
        }

        return $stock;
    }

    /**
     * Calculate transfer priority based on warehouse characteristics
     *
     * @param array $fromWarehouse
     * @param array $toWarehouse
     * @return int
     */
    private function calculateTransferPriority(array $fromWarehouse, array $toWarehouse): int
    {
        $priority = 5; // Default priority

        // Higher priority if destination is low stock
        if ($toWarehouse['is_low_stock']) {
            $priority -= 2;
        }

        // Lower priority if source would become low stock after transfer
        if ($fromWarehouse['available_quantity'] <= $fromWarehouse['minimum_stock'] * 1.2) {
            $priority += 1;
        }

        // Higher priority for default warehouse as destination
        $toWarehouseModel = Warehouse::find($toWarehouse['warehouse_id']);
        if ($toWarehouseModel && $toWarehouseModel->is_default) {
            $priority -= 1;
        }

        return max(1, min(10, $priority)); // Ensure priority is between 1-10
    }
}