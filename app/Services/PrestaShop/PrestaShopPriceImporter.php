<?php

namespace App\Services\PrestaShop;

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ProductPrice;
use App\Models\PriceGroup;
use Illuminate\Support\Facades\Log;

/**
 * PrestaShop Price Importer Service
 *
 * PROBLEM #4 - Task 16: PrestaShop Price Import
 *
 * Imports prices from PrestaShop specific_prices → PPM product_prices.
 * Maps PrestaShop discounts/group prices to PPM's 8-tier price groups.
 *
 * Architecture:
 * - Fetches specific_prices via PrestaShop API
 * - Maps specific_prices → PPM price_groups (8 groups)
 * - Saves prestashop_mapping JSON per price record
 * - Updates existing prices or creates new ones
 *
 * PrestaShop specific_price fields:
 * - reduction: Discount amount (0.15 = 15% or 5.00 = 5 PLN)
 * - reduction_type: "percentage" or "amount"
 * - id_group: Customer group (0 = all groups)
 * - id_shop: Shop ID
 * - price: Override price (or -1 = use base price)
 * - from/to: Date validity range
 *
 * PPM product_prices schema:
 * - product_id + price_group_id (UNIQUE)
 * - price_net, price_gross
 * - prestashop_mapping JSON: {"shop_X": {"specific_price_id": 123, "reduction": 0.15, ...}}
 *
 * Mapping Strategy:
 * 1. Base price (products.price) → "Detaliczna" (default)
 * 2. specific_prices with reduction → map to appropriate price_group
 * 3. Group-specific prices → map to corresponding PPM price_group
 *
 * Usage:
 * ```php
 * $importer = app(PrestaShopPriceImporter::class);
 * $importedPrices = $importer->importPricesForProduct($product, $shop);
 * ```
 *
 * @package App\Services\PrestaShop
 * @version 1.0
 * @since PROBLEM #4 - Task 16
 */
class PrestaShopPriceImporter
{
    /**
     * Constructor with dependency injection
     *
     * @param PrestaShopClientFactory $clientFactory
     */
    public function __construct(
        protected PrestaShopClientFactory $clientFactory
    ) {}

    /**
     * Import prices for product from PrestaShop
     *
     * Workflow:
     * 1. Fetch base price from products.price
     * 2. Fetch specific_prices from PrestaShop API
     * 3. Map each price to PPM price_group
     * 4. Update/create product_prices records
     * 5. Save prestashop_mapping JSON
     *
     * @param Product $product Product to import prices for
     * @param PrestaShopShop $shop Shop to import from
     * @return array Array of imported price records
     * @throws \Exception On API errors
     */
    public function importPricesForProduct(Product $product, PrestaShopShop $shop): array
    {
        Log::info('Starting price import from PrestaShop', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'shop_id' => $shop->id,
        ]);

        try {
            // 1. Get PrestaShop product ID from product_shop_data
            $shopData = $product->shopData()->where('shop_id', $shop->id)->first();

            if (!$shopData || !$shopData->prestashop_product_id) {
                Log::warning('Product not linked to PrestaShop', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                ]);
                return [];
            }

            $prestashopProductId = $shopData->prestashop_product_id;

            // 2. Create API client
            $client = $this->clientFactory::create($shop);

            // 3. Fetch base product data (for base price)
            $productData = $client->getProduct($prestashopProductId);

            if (isset($productData['product'])) {
                $productData = $productData['product'];
            }

            $basePrice = (float) data_get($productData, 'price', 0);

            // 4. Fetch specific_prices from API
            $specificPricesData = $client->getSpecificPrices($prestashopProductId);

            $specificPrices = [];
            if (isset($specificPricesData['specific_prices']) && is_array($specificPricesData['specific_prices'])) {
                $specificPrices = $specificPricesData['specific_prices'];
            }

            Log::info('Fetched prices from PrestaShop', [
                'product_id' => $product->id,
                'base_price' => $basePrice,
                'specific_prices_count' => count($specificPrices),
            ]);

            $imported = [];

            // 5. Import base price → "Detaliczna" (default price group)
            $defaultPriceGroup = PriceGroup::where('is_default', true)->first();

            if ($defaultPriceGroup && $basePrice > 0) {
                $this->updateProductPrice(
                    product: $product,
                    priceGroupId: $defaultPriceGroup->id,
                    net: $basePrice,
                    gross: $basePrice * (1 + ($product->tax_rate / 100)),
                    prestashopMapping: [
                        $shop->id => [
                            'source' => 'base_price',
                            'prestashop_price' => $basePrice,
                            'shop_id' => $shop->id,
                        ]
                    ]
                );

                $imported[] = [
                    'price_group' => $defaultPriceGroup->code,
                    'net' => $basePrice,
                    'source' => 'base_price',
                ];
            }

            // 6. Import specific_prices
            foreach ($specificPrices as $specificPrice) {
                // Extract fields
                $specificPriceId = (int) data_get($specificPrice, 'id');
                $reduction = (float) data_get($specificPrice, 'reduction', 0);
                $reductionType = data_get($specificPrice, 'reduction_type', 'percentage');
                $priceOverride = (float) data_get($specificPrice, 'price', -1);
                $idGroup = (int) data_get($specificPrice, 'id_group', 0);

                // Calculate final price
                $finalPrice = 0;

                if ($priceOverride >= 0) {
                    // Price override specified
                    $finalPrice = $priceOverride;
                } elseif ($reduction > 0) {
                    // Reduction from base price
                    if ($reductionType === 'percentage') {
                        $finalPrice = $basePrice * (1 - $reduction);
                    } else {
                        $finalPrice = $basePrice - $reduction;
                    }
                } else {
                    // No override, no reduction → skip (same as base)
                    continue;
                }

                // Map to price_group
                $priceGroupId = $this->mapSpecificPriceToPriceGroup($specificPrice, $shop, $idGroup);

                if (!$priceGroupId) {
                    Log::warning('Could not map specific_price to price_group', [
                        'specific_price_id' => $specificPriceId,
                        'id_group' => $idGroup,
                        'reduction' => $reduction,
                    ]);
                    continue;
                }

                // Update price
                $this->updateProductPrice(
                    product: $product,
                    priceGroupId: $priceGroupId,
                    net: $finalPrice,
                    gross: $finalPrice * (1 + ($product->tax_rate / 100)),
                    prestashopMapping: [
                        $shop->id => [
                            'specific_price_id' => $specificPriceId,
                            'reduction' => $reduction,
                            'reduction_type' => $reductionType,
                            'price_override' => $priceOverride >= 0 ? $priceOverride : null,
                            'id_group' => $idGroup,
                            'shop_id' => $shop->id,
                        ]
                    ]
                );

                $imported[] = [
                    'price_group_id' => $priceGroupId,
                    'net' => $finalPrice,
                    'source' => 'specific_price',
                    'specific_price_id' => $specificPriceId,
                ];
            }

            Log::info('Price import completed', [
                'product_id' => $product->id,
                'imported_count' => count($imported),
            ]);

            return $imported;

        } catch (\App\Exceptions\PrestaShopAPIException $e) {
            // BUG #8 FIX #1: Re-throw PrestaShop API exceptions (including 404)
            // Caller (PullProductsFromPrestaShop) will handle 404 specifically
            Log::info('PrestaShop API error during price import', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'http_status' => $e->getHttpStatusCode(),
                'is_404' => $e->isNotFound(),
                'error' => $e->getMessage(),
            ]);

            throw $e; // Re-throw to caller

        } catch (\Exception $e) {
            Log::error('Price import failed (generic error)', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Map PrestaShop specific_price to PPM price_group
     *
     * BUG #14 FIX: Use prestashop_shop_price_mappings table instead of hardcoded mapping
     *
     * Mapping strategy:
     * 1. Check prestashop_shop_price_mappings table for explicit mapping
     * 2. If id_group = 0 (all groups) → Default price group (Detaliczna)
     * 3. If no mapping found → return null (skip this specific price)
     *
     * @param array $specificPrice PrestaShop specific_price data
     * @param PrestaShopShop $shop Shop instance
     * @param int $idGroup PrestaShop customer group ID
     * @return int|null PPM price_group_id or null if no mapping
     */
    protected function mapSpecificPriceToPriceGroup(array $specificPrice, PrestaShopShop $shop, int $idGroup): ?int
    {
        // Special case: id_group = 0 (all groups) → Default price group
        if ($idGroup === 0) {
            $defaultPriceGroup = PriceGroup::where('is_default', true)->first();
            return $defaultPriceGroup?->id;
        }

        // BUG #14 FIX: Query prestashop_shop_price_mappings table
        $mapping = \DB::table('prestashop_shop_price_mappings')
            ->where('prestashop_shop_id', $shop->id)
            ->where('prestashop_price_group_id', $idGroup)
            ->first();

        if (!$mapping) {
            Log::warning('No price group mapping found for PrestaShop group', [
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
                'prestashop_group_id' => $idGroup,
            ]);
            return null;
        }

        // Get PPM price_group by name
        $priceGroup = PriceGroup::where('name', $mapping->ppm_price_group_name)
            ->orWhere('code', $mapping->ppm_price_group_name) // Fallback to code
            ->first();

        if (!$priceGroup) {
            Log::warning('PPM price group not found for mapped name', [
                'shop_id' => $shop->id,
                'prestashop_group_id' => $idGroup,
                'ppm_price_group_name' => $mapping->ppm_price_group_name,
            ]);
            return null;
        }

        Log::info('Mapped PrestaShop price group to PPM price group', [
            'shop_id' => $shop->id,
            'prestashop_group_id' => $idGroup,
            'prestashop_group_name' => $mapping->prestashop_price_group_name,
            'ppm_price_group_name' => $mapping->ppm_price_group_name,
            'ppm_price_group_id' => $priceGroup->id,
        ]);

        return $priceGroup->id;
    }

    /**
     * Update or create product_price record
     *
     * @param Product $product Product instance
     * @param int $priceGroupId Price group ID
     * @param float $net Net price
     * @param float $gross Gross price
     * @param array $prestashopMapping PrestaShop mapping data
     * @return void
     */
    protected function updateProductPrice(
        Product $product,
        int $priceGroupId,
        float $net,
        float $gross,
        array $prestashopMapping
    ): void
    {
        // Get existing price or create new
        $productPrice = ProductPrice::where('product_id', $product->id)
            ->where('price_group_id', $priceGroupId)
            ->first();

        if ($productPrice) {
            // Merge prestashop_mapping (preserve existing shops)
            $existingMapping = $productPrice->prestashop_mapping ?? [];
            $mergedMapping = array_merge($existingMapping, $prestashopMapping);

            $productPrice->update([
                'price_net' => $net,
                'price_gross' => $gross,
                'prestashop_mapping' => $mergedMapping,
            ]);

            Log::debug('Updated existing product_price', [
                'product_id' => $product->id,
                'price_group_id' => $priceGroupId,
                'net' => $net,
            ]);
        } else {
            ProductPrice::create([
                'product_id' => $product->id,
                'price_group_id' => $priceGroupId,
                'price_net' => $net,
                'price_gross' => $gross,
                'prestashop_mapping' => $prestashopMapping,
                'currency' => 'PLN',
                'is_active' => true,
            ]);

            Log::debug('Created new product_price', [
                'product_id' => $product->id,
                'price_group_id' => $priceGroupId,
                'net' => $net,
            ]);
        }
    }
}
