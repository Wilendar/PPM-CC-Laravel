<?php

namespace App\Jobs\Products;

use App\Models\Product;
use App\Models\ProductStatus;
use App\Models\ProductStatusTransition;
use App\Models\Warehouse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckStockDepletionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function handle(): void
    {
        $statuses = ProductStatus::withStockDepletion()
            ->with(['transitionToStatus', 'depletionWarehouse'])
            ->get();

        if ($statuses->isEmpty()) {
            return;
        }

        $totalTransitioned = 0;

        foreach ($statuses as $status) {
            $toStatus = $status->transitionToStatus;
            if (!$toStatus) {
                continue;
            }

            $warehouseId = $status->depletion_warehouse_id;
            if (!$warehouseId) {
                $defaultWarehouse = Warehouse::where('is_default', true)
                    ->where('is_active', true)
                    ->first();
                if (!$defaultWarehouse) {
                    Log::warning('[STOCK_DEPLETION] No default warehouse found, skipping', [
                        'status_id' => $status->id,
                        'status_name' => $status->name,
                    ]);
                    continue;
                }
                $warehouseId = $defaultWarehouse->id;
            }

            $transitioned = $this->processStatus($status, $toStatus, $warehouseId);
            $totalTransitioned += $transitioned;
        }

        if ($totalTransitioned > 0) {
            Log::info('[STOCK_DEPLETION] Auto-transition completed', [
                'total_transitioned' => $totalTransitioned,
            ]);
        }
    }

    protected function processStatus(
        ProductStatus $fromStatus,
        ProductStatus $toStatus,
        int $warehouseId
    ): int {
        $count = 0;

        // Products with this status that have 0 stock on monitored warehouse
        // Uses chunk to handle large datasets efficiently
        Product::where('product_status_id', $fromStatus->id)
            ->whereDoesntHave('stock', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)
                    ->where('is_active', true)
                    ->where('available_quantity', '>', 0);
            })
            ->chunkById(100, function ($products) use ($fromStatus, $toStatus, $warehouseId, &$count) {
                foreach ($products as $product) {
                    try {
                        $this->transitionProduct($product, $fromStatus, $toStatus, $warehouseId);
                        $count++;
                    } catch (\Exception $e) {
                        Log::error('[STOCK_DEPLETION] Product transition failed', [
                            'product_id' => $product->id,
                            'sku' => $product->sku,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return $count;
    }

    protected function transitionProduct(
        Product $product,
        ProductStatus $fromStatus,
        ProductStatus $toStatus,
        int $warehouseId
    ): void {
        $stockRecord = $product->stock()
            ->where('warehouse_id', $warehouseId)
            ->first();
        $currentStock = $stockRecord ? $stockRecord->available_quantity : 0;

        DB::transaction(function () use ($product, $fromStatus, $toStatus, $warehouseId, $currentStock) {
            // Update status (boot observer auto-computes is_active)
            $product->update(['product_status_id' => $toStatus->id]);

            // Log the transition
            ProductStatusTransition::create([
                'product_id' => $product->id,
                'from_status_id' => $fromStatus->id,
                'to_status_id' => $toStatus->id,
                'trigger' => 'stock_depleted',
                'stock_at_transition' => $currentStock,
                'warehouse_id' => $warehouseId,
                'transitioned_at' => now(),
            ]);
        });

        Log::info('[STOCK_DEPLETION] Product transitioned', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'from' => $fromStatus->name,
            'to' => $toStatus->name,
            'stock' => $currentStock,
            'warehouse_id' => $warehouseId,
        ]);
    }
}
