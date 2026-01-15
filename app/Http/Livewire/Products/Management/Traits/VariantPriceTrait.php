<?php

namespace App\Http\Livewire\Products\Management\Traits;

use App\Models\ProductVariant;
use App\Models\VariantPrice;
use App\Models\PriceGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * VariantPriceTrait - Price Management for Product Variants
 *
 * Handles: Update prices, bulk copy from parent, save prices grid
 *
 * EXTRACTED FROM: ProductFormVariants.php (1369 lines -> 6 traits)
 * LINE COUNT TARGET: < 200 lines (CLAUDE.md compliance)
 *
 * DEPENDENCIES:
 * - VariantValidation trait (validateVariantPrice)
 * - Product model ($this->product)
 *
 * @package App\Http\Livewire\Products\Management\Traits
 * @version 2.0 (Refactored)
 * @since ETAP_05b FAZA 1
 */
trait VariantPriceTrait
{
    /*
    |--------------------------------------------------------------------------
    | PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Variant prices data [variant_id][price_group_key] => price
     *
     * Used by Alpine.js x-model in variant-prices-grid.blade.php
     *
     * @var array
     */
    public array $variantPrices = [];

    /*
    |--------------------------------------------------------------------------
    | PRICE MANAGEMENT METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Update variant price for specific price group
     */
    public function updateVariantPrice(int $variantId, int $priceGroupId, array $priceData): void
    {
        try {
            $variant = ProductVariant::findOrFail($variantId);
            $this->validateVariantPrice($priceData);

            $variant->prices()->updateOrCreate(
                ['price_group_id' => $priceGroupId],
                [
                    'price' => $priceData['price'],
                    'price_special' => $priceData['price_special'] ?? null,
                    'special_from' => $priceData['special_from'] ?? null,
                    'special_to' => $priceData['special_to'] ?? null,
                ]
            );

            Log::info('Variant price updated', [
                'variant_id' => $variantId,
                'price_group_id' => $priceGroupId,
            ]);

            $this->dispatch('variant-price-updated');
        } catch (\Exception $e) {
            Log::error('Variant price update failed', [
                'variant_id' => $variantId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Blad podczas aktualizacji ceny: ' . $e->getMessage());
        }
    }

    /**
     * Copy prices from parent product to all variants
     */
    public function bulkCopyPricesFromParent(): void
    {
        try {
            DB::transaction(function () {
                $productPrices = $this->product->prices;

                foreach ($this->product->variants as $variant) {
                    foreach ($productPrices as $price) {
                        $variant->prices()->updateOrCreate(
                            ['price_group_id' => $price->price_group_id],
                            [
                                'price' => $price->price,
                                'price_special' => $price->price_special,
                                'special_from' => $price->special_from,
                                'special_to' => $price->special_to,
                            ]
                        );
                    }
                }

                Log::info('Bulk copied prices from parent to variants', [
                    'product_id' => $this->product->id,
                    'variants_count' => $this->product->variants->count(),
                ]);
            });

            $this->product->load('variants.prices');
            $this->dispatch('prices-bulk-copied');
            session()->flash('message', 'Ceny zostaly skopiowane do wszystkich wariantow.');
        } catch (\Exception $e) {
            Log::error('Bulk copy prices failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Blad podczas kopiowania cen: ' . $e->getMessage());
        }
    }

    /**
     * Save variant prices grid
     */
    public function savePrices(): void
    {
        try {
            DB::beginTransaction();

            foreach ($this->variantPrices as $variantId => $prices) {
                foreach ($prices as $priceGroupKey => $price) {
                    $priceGroup = PriceGroup::where('code', $priceGroupKey)->first();

                    if (!$priceGroup) {
                        continue;
                    }

                    if (!is_numeric($price) || $price < 0) {
                        throw new \Exception("Nieprawidlowa cena dla wariantu {$variantId}");
                    }

                    VariantPrice::updateOrCreate(
                        [
                            'variant_id' => $variantId,
                            'price_group_id' => $priceGroup->id,
                        ],
                        [
                            'price' => $price,
                            'updated_at' => now(),
                        ]
                    );
                }
            }

            DB::commit();

            Log::info('Variant prices saved', [
                'product_id' => $this->product->id,
                'variants_count' => count($this->variantPrices),
            ]);

            $this->dispatch('success', message: 'Ceny wariantow zapisane');
            $this->dispatch('prices-saved');
            session()->flash('message', 'Ceny wariantow zostaly zapisane pomyslnie.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Variant prices save error', ['error' => $e->getMessage()]);
            $this->dispatch('error', message: 'Blad zapisu cen: ' . $e->getMessage());
            session()->flash('error', 'Blad podczas zapisu cen: ' . $e->getMessage());
        }
    }

    /**
     * Load variant prices data for grid
     */
    protected function loadVariantPrices(): void
    {
        if (!$this->product || !$this->product->is_variant_master) {
            return;
        }

        $variants = $this->product->variants()->with('prices.priceGroup')->get();
        $this->variantPrices = [];

        foreach ($variants as $variant) {
            foreach ($variant->prices as $price) {
                if ($price->priceGroup) {
                    $this->variantPrices[$variant->id][$price->priceGroup->code] = $price->price;
                }
            }
        }
    }

    /**
     * Get price groups with prices for grid rendering
     */
    public function getPriceGroupsWithPrices(): Collection
    {
        $priceGroups = PriceGroup::orderBy('name')->get();

        return $priceGroups->map(function ($group) {
            return [
                'id' => $group->id,
                'name' => $group->name,
                'prices' => $this->product->variants->mapWithKeys(function ($variant) use ($group) {
                    $price = $variant->prices->firstWhere('price_group_id', $group->id);
                    return [
                        $variant->id => [
                            'price' => $price?->price ?? 0,
                            'price_special' => $price?->price_special,
                            'has_special' => $price && $price->price_special,
                        ]
                    ];
                }),
            ];
        });
    }
}
