<?php

namespace App\Http\Livewire\Products\Management\Traits;

use App\Models\ProductVariant;
use App\Models\VariantStock;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * VariantStockTrait - Stock Management for Product Variants
 *
 * Handles: Update stock per warehouse, save stock grid
 *
 * EXTRACTED FROM: ProductFormVariants.php (1369 lines -> 6 traits)
 * LINE COUNT TARGET: < 170 lines (CLAUDE.md compliance)
 *
 * DEPENDENCIES:
 * - VariantValidation trait (validateVariantStock)
 * - Product model ($this->product)
 *
 * @package App\Http\Livewire\Products\Management\Traits
 * @version 2.0 (Refactored)
 * @since ETAP_05b FAZA 1
 */
trait VariantStockTrait
{
    /*
    |--------------------------------------------------------------------------
    | PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Variant stock data [variant_id][warehouse_id] => quantity
     *
     * Used by Alpine.js x-model in variant-stock-grid.blade.php
     *
     * @var array
     */
    public array $variantStock = [];

    /*
    |--------------------------------------------------------------------------
    | STOCK MANAGEMENT METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Update variant stock for specific warehouse
     */
    public function updateVariantStock(int $variantId, int $warehouseId, array $stockData): void
    {
        try {
            $variant = ProductVariant::findOrFail($variantId);

            $stockData['warehouse_id'] = $warehouseId;
            $this->validateVariantStock($stockData);

            $variant->stock()->updateOrCreate(
                ['warehouse_id' => $warehouseId],
                [
                    'quantity' => $stockData['quantity'],
                    'reserved' => $stockData['reserved'] ?? 0,
                ]
            );

            Log::info('Variant stock updated', [
                'variant_id' => $variantId,
                'warehouse_id' => $warehouseId,
            ]);

            $this->dispatch('variant-stock-updated');
        } catch (\Exception $e) {
            Log::error('Variant stock update failed', [
                'variant_id' => $variantId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Blad podczas aktualizacji stanu magazynowego: ' . $e->getMessage());
        }
    }

    /**
     * Save variant stock grid
     */
    public function saveStock(): void
    {
        try {
            DB::beginTransaction();

            foreach ($this->variantStock as $variantId => $stock) {
                foreach ($stock as $warehouseIndex => $quantity) {
                    $warehouse = Warehouse::where('is_active', true)
                        ->orderBy('name')
                        ->skip($warehouseIndex - 1)
                        ->first();

                    if (!$warehouse) {
                        continue;
                    }

                    if (!is_int($quantity) && !is_numeric($quantity)) {
                        throw new \Exception("Nieprawidlowy stan dla wariantu {$variantId}");
                    }

                    $quantity = (int) $quantity;

                    if ($quantity < 0) {
                        throw new \Exception("Stan nie moze byc ujemny dla wariantu {$variantId}");
                    }

                    VariantStock::updateOrCreate(
                        [
                            'variant_id' => $variantId,
                            'warehouse_id' => $warehouse->id,
                        ],
                        [
                            'quantity' => $quantity,
                            'updated_at' => now(),
                        ]
                    );
                }
            }

            DB::commit();

            Log::info('Variant stock saved', [
                'product_id' => $this->product->id,
                'variants_count' => count($this->variantStock),
            ]);

            $this->dispatch('success', message: 'Stany magazynowe zapisane');
            $this->dispatch('stock-saved');
            session()->flash('message', 'Stany magazynowe zostaly zapisane pomyslnie.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Variant stock save error', ['error' => $e->getMessage()]);
            $this->dispatch('error', message: 'Blad zapisu stanow: ' . $e->getMessage());
            session()->flash('error', 'Blad podczas zapisu stanow: ' . $e->getMessage());
        }
    }

    /**
     * Load variant stock data for grid
     */
    protected function loadVariantStock(): void
    {
        if (!$this->product || !$this->product->is_variant_master) {
            return;
        }

        $variants = $this->product->variants()->with('stock.warehouse')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        $this->variantStock = [];

        foreach ($variants as $variant) {
            foreach ($warehouses as $index => $warehouse) {
                $stock = $variant->stock->firstWhere('warehouse_id', $warehouse->id);
                $this->variantStock[$variant->id][$index + 1] = $stock?->quantity ?? 0;
            }
        }
    }

    /**
     * Get warehouses with stock for grid rendering
     */
    public function getWarehousesWithStock(): Collection
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return $warehouses->map(function ($warehouse) {
            return [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'stock' => $this->product->variants->mapWithKeys(function ($variant) use ($warehouse) {
                    $stock = $variant->stock->firstWhere('warehouse_id', $warehouse->id);
                    return [
                        $variant->id => [
                            'quantity' => $stock?->quantity ?? 0,
                            'reserved' => $stock?->reserved ?? 0,
                            'available' => ($stock?->quantity ?? 0) - ($stock?->reserved ?? 0),
                            'is_low' => ($stock?->quantity ?? 0) < 10,
                        ]
                    ];
                }),
            ];
        });
    }
}
