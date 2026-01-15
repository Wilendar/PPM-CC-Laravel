<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Service for managing product variant stock operations.
 *
 * Handles bulk stock updates, stock transfers between variants,
 * stock reservations, and low stock monitoring.
 *
 * @see https://laravel.com/docs/12.x/database (Transactions)
 */
class VariantStockService
{
    /**
     * Create a new service instance.
     *
     * @see https://laravel.com/docs/12.x/container (Dependency Injection)
     */
    public function __construct()
    {
        // Future: Inject repositories or other services if needed
    }

    /**
     * Bulk update stock for multiple variants in a warehouse.
     *
     * @param array<int> $variantIds Array of variant IDs
     * @param int $warehouseId Warehouse ID
     * @param int $quantity Stock quantity
     * @return array{success: bool, updated: int, errors: array}
     */
    public function bulkUpdateStock(array $variantIds, int $warehouseId, int $quantity): array
    {
        if (empty($variantIds)) {
            throw new InvalidArgumentException('Variant IDs array cannot be empty');
        }

        if ($quantity < 0) {
            throw new InvalidArgumentException('Quantity cannot be negative');
        }

        $updated = 0;
        $errors = [];

        try {
            DB::beginTransaction();

            foreach ($variantIds as $variantId) {
                $variant = ProductVariant::find($variantId);

                if (!$variant) {
                    $errors[] = "Variant ID {$variantId} not found";
                    continue;
                }

                ProductStock::updateOrCreate(
                    [
                        'product_id' => $variant->product_id,
                        'variant_id' => $variantId,
                        'warehouse_id' => $warehouseId,
                    ],
                    [
                        'quantity' => $quantity,
                    ]
                );

                $updated++;
            }

            DB::commit();

            Log::info('Bulk stock update completed', [
                'variant_ids' => $variantIds,
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
                'updated_count' => $updated,
                'errors_count' => count($errors),
            ]);

            return [
                'success' => true,
                'updated' => $updated,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bulk stock update failed', [
                'variant_ids' => $variantIds,
                'warehouse_id' => $warehouseId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'updated' => 0,
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Transfer stock from one variant to another within same warehouse.
     *
     * @param int $fromVariantId Source variant ID
     * @param int $toVariantId Destination variant ID
     * @param int $warehouseId Warehouse ID
     * @param int $quantity Amount to transfer
     * @return array{success: bool, transferred: int}
     */
    public function transferStock(
        int $fromVariantId,
        int $toVariantId,
        int $warehouseId,
        int $quantity
    ): array {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Transfer quantity must be greater than zero');
        }

        if ($fromVariantId === $toVariantId) {
            throw new InvalidArgumentException('Cannot transfer stock to the same variant');
        }

        try {
            DB::beginTransaction();

            $fromStock = ProductStock::where('variant_id', $fromVariantId)
                ->where('warehouse_id', $warehouseId)
                ->first();

            if (!$fromStock || $fromStock->quantity < $quantity) {
                DB::rollBack();
                return [
                    'success' => false,
                    'transferred' => 0,
                ];
            }

            $fromVariant = ProductVariant::find($fromVariantId);
            $toVariant = ProductVariant::find($toVariantId);

            if (!$fromVariant || !$toVariant) {
                DB::rollBack();
                return [
                    'success' => false,
                    'transferred' => 0,
                ];
            }

            // Deduct from source
            $fromStock->decrement('quantity', $quantity);

            // Add to destination
            $toStock = ProductStock::firstOrNew([
                'product_id' => $toVariant->product_id,
                'variant_id' => $toVariantId,
                'warehouse_id' => $warehouseId,
            ]);

            $toStock->quantity = ($toStock->quantity ?? 0) + $quantity;
            $toStock->save();

            DB::commit();

            Log::info('Stock transferred between variants', [
                'from_variant_id' => $fromVariantId,
                'to_variant_id' => $toVariantId,
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
            ]);

            return [
                'success' => true,
                'transferred' => $quantity,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Stock transfer failed', [
                'from_variant_id' => $fromVariantId,
                'to_variant_id' => $toVariantId,
                'warehouse_id' => $warehouseId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'transferred' => 0,
            ];
        }
    }

    /**
     * Get stock matrix for UI grid display.
     *
     * Returns structured data: variant → warehouse → quantity
     *
     * @param Product $product Parent product
     * @return array<int, array<string, mixed>>
     */
    public function getStockMatrix(Product $product): array
    {
        $matrix = [];

        $variantStocks = ProductStock::where('product_id', $product->id)
            ->whereNotNull('variant_id')
            ->with(['variant', 'warehouse'])
            ->get();

        foreach ($variantStocks as $stock) {
            if (!isset($matrix[$stock->variant_id])) {
                $matrix[$stock->variant_id] = [
                    'variant' => $stock->variant,
                    'warehouses' => [],
                ];
            }

            $matrix[$stock->variant_id]['warehouses'][$stock->warehouse_id] = [
                'warehouse' => $stock->warehouse,
                'quantity' => $stock->quantity,
            ];
        }

        return $matrix;
    }

    /**
     * Get variants with low stock levels.
     *
     * @param Product $product Parent product
     * @param int $threshold Stock level threshold (default: 10)
     * @return array<int, array<string, mixed>>
     */
    public function getLowStockVariants(Product $product, int $threshold = 10): array
    {
        $lowStockVariants = [];

        $stocks = ProductStock::where('product_id', $product->id)
            ->whereNotNull('variant_id')
            ->where('quantity', '<=', $threshold)
            ->with(['variant', 'warehouse'])
            ->get();

        foreach ($stocks as $stock) {
            if (!isset($lowStockVariants[$stock->variant_id])) {
                $lowStockVariants[$stock->variant_id] = [
                    'variant' => $stock->variant,
                    'low_stock_warehouses' => [],
                    'total_quantity' => 0,
                ];
            }

            $lowStockVariants[$stock->variant_id]['low_stock_warehouses'][] = [
                'warehouse' => $stock->warehouse,
                'quantity' => $stock->quantity,
            ];

            $lowStockVariants[$stock->variant_id]['total_quantity'] += $stock->quantity;
        }

        return $lowStockVariants;
    }

    /**
     * Reserve stock for a variant in a warehouse.
     *
     * Creates a temporary reservation that reduces available stock.
     * Actual quantity remains unchanged until fulfillment.
     *
     * @param int $variantId Variant ID
     * @param int $warehouseId Warehouse ID
     * @param int $quantity Amount to reserve
     * @return array{success: bool, reserved: int, available: int}
     */
    public function reserveStock(int $variantId, int $warehouseId, int $quantity): array
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Reservation quantity must be greater than zero');
        }

        try {
            DB::beginTransaction();

            $stock = ProductStock::where('variant_id', $variantId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                DB::rollBack();
                return [
                    'success' => false,
                    'reserved' => 0,
                    'available' => 0,
                ];
            }

            // Calculate available stock (total - already reserved)
            $reserved = $stock->reserved_quantity ?? 0;
            $available = $stock->quantity - $reserved;

            if ($available < $quantity) {
                DB::rollBack();
                return [
                    'success' => false,
                    'reserved' => $reserved,
                    'available' => $available,
                ];
            }

            // Update reserved quantity
            $stock->increment('reserved_quantity', $quantity);

            DB::commit();

            Log::info('Stock reserved', [
                'variant_id' => $variantId,
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
                'new_reserved_total' => $reserved + $quantity,
            ]);

            return [
                'success' => true,
                'reserved' => $quantity,
                'available' => $available - $quantity,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Stock reservation failed', [
                'variant_id' => $variantId,
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'reserved' => 0,
                'available' => 0,
            ];
        }
    }
}
