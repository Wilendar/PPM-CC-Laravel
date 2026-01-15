<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Service for managing product variant pricing operations.
 *
 * Handles bulk price updates, price copying from parent products,
 * price modifiers, and PrestaShop price impact calculations.
 *
 * @see https://laravel.com/docs/12.x/database (Transactions)
 */
class VariantPriceService
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
     * Bulk update prices for multiple variants.
     *
     * @param array<int> $variantIds Array of variant IDs
     * @param array<string, float> $prices Price data: ['price_group' => amount]
     * @return array{success: bool, updated: int, errors: array}
     */
    public function bulkUpdatePrices(array $variantIds, array $prices): array
    {
        if (empty($variantIds)) {
            throw new InvalidArgumentException('Variant IDs array cannot be empty');
        }

        if (empty($prices)) {
            throw new InvalidArgumentException('Prices array cannot be empty');
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

                foreach ($prices as $priceGroup => $amount) {
                    if (!is_numeric($amount) || $amount < 0) {
                        $errors[] = "Invalid price amount for variant {$variantId}, group {$priceGroup}";
                        continue;
                    }

                    ProductPrice::updateOrCreate(
                        [
                            'product_id' => $variant->product_id,
                            'variant_id' => $variantId,
                            'price_group' => $priceGroup,
                        ],
                        [
                            'price' => $amount,
                        ]
                    );

                    $updated++;
                }
            }

            DB::commit();

            Log::info('Bulk price update completed', [
                'variant_ids' => $variantIds,
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

            Log::error('Bulk price update failed', [
                'variant_ids' => $variantIds,
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
     * Copy all prices from parent product to its variants.
     *
     * @param Product $product Parent product
     * @return array{success: bool, copied: int}
     */
    public function copyPricesFromProduct(Product $product): array
    {
        $copied = 0;

        try {
            DB::beginTransaction();

            $parentPrices = ProductPrice::where('product_id', $product->id)
                ->whereNull('variant_id')
                ->get();

            if ($parentPrices->isEmpty()) {
                DB::rollBack();
                return [
                    'success' => false,
                    'copied' => 0,
                ];
            }

            foreach ($product->variants as $variant) {
                foreach ($parentPrices as $parentPrice) {
                    ProductPrice::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'variant_id' => $variant->id,
                            'price_group' => $parentPrice->price_group,
                        ],
                        [
                            'price' => $parentPrice->price,
                        ]
                    );

                    $copied++;
                }
            }

            DB::commit();

            Log::info('Prices copied from product to variants', [
                'product_id' => $product->id,
                'variants_count' => $product->variants->count(),
                'copied_count' => $copied,
            ]);

            return [
                'success' => true,
                'copied' => $copied,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to copy prices from product', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'copied' => 0,
            ];
        }
    }

    /**
     * Apply price modifier to variants.
     *
     * @param array<int> $variantIds Array of variant IDs
     * @param float $modifier Modifier value
     * @param string $type Modifier type: 'percentage' or 'value'
     * @return array{success: bool, modified: int}
     */
    public function applyPriceModifier(
        array $variantIds,
        float $modifier,
        string $type = 'percentage'
    ): array {
        if (!in_array($type, ['percentage', 'value'])) {
            throw new InvalidArgumentException('Type must be "percentage" or "value"');
        }

        $modified = 0;

        try {
            DB::beginTransaction();

            $prices = ProductPrice::whereIn('variant_id', $variantIds)->get();

            foreach ($prices as $price) {
                $newPrice = $type === 'percentage'
                    ? $price->price * (1 + $modifier / 100)
                    : $price->price + $modifier;

                if ($newPrice < 0) {
                    $newPrice = 0;
                }

                $price->update(['price' => round($newPrice, 2)]);
                $modified++;
            }

            DB::commit();

            Log::info('Price modifier applied', [
                'variant_ids' => $variantIds,
                'modifier' => $modifier,
                'type' => $type,
                'modified_count' => $modified,
            ]);

            return [
                'success' => true,
                'modified' => $modified,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to apply price modifier', [
                'variant_ids' => $variantIds,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'modified' => 0,
            ];
        }
    }

    /**
     * Get price matrix for UI grid display.
     *
     * Returns structured data: variant → price_group → price
     *
     * @param Product $product Parent product
     * @return array<int, array<string, float>>
     */
    public function getPriceMatrix(Product $product): array
    {
        $matrix = [];

        $variantPrices = ProductPrice::where('product_id', $product->id)
            ->whereNotNull('variant_id')
            ->with('variant')
            ->get();

        foreach ($variantPrices as $price) {
            if (!isset($matrix[$price->variant_id])) {
                $matrix[$price->variant_id] = [
                    'variant' => $price->variant,
                    'prices' => [],
                ];
            }

            $matrix[$price->variant_id]['prices'][$price->price_group] = $price->price;
        }

        return $matrix;
    }

    /**
     * Calculate price impact for PrestaShop (difference between base and variant price).
     *
     * PrestaShop uses "Price Impact" model: variant price = base price + impact
     *
     * @param float $basePrice Base product price
     * @param float $variantPrice Variant price
     * @return array{impact: float, percentage: float, type: string}
     */
    public function calculatePriceImpact(float $basePrice, float $variantPrice): array
    {
        if ($basePrice <= 0) {
            throw new InvalidArgumentException('Base price must be greater than zero');
        }

        $impact = $variantPrice - $basePrice;
        $percentage = ($impact / $basePrice) * 100;
        $type = $impact >= 0 ? 'increase' : 'decrease';

        return [
            'impact' => round($impact, 2),
            'percentage' => round($percentage, 2),
            'type' => $type,
        ];
    }
}
