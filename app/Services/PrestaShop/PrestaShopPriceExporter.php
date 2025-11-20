<?php

namespace App\Services\PrestaShop;

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ProductPrice;
use Illuminate\Support\Facades\Log;
use App\Exceptions\PrestaShopAPIException;

/**
 * PrestaShop Price Exporter Service
 *
 * ISSUE FIX: PRESTASHOP_PRICE_SYNC_ISSUE.md
 * Date: 2025-11-14
 *
 * Exports PPM product_prices (8 price groups) → PrestaShop specific_prices.
 * Synchronizes group-specific pricing for customer groups.
 *
 * Architecture:
 * - PPM product_prices (price_group_id) → PrestaShop specific_prices (id_group)
 * - Uses PriceGroupMapper for PPM → PrestaShop group mapping
 * - CREATE, UPDATE, DELETE specific_prices via API
 * - Tracks sync status in product_prices.prestashop_mapping JSON
 *
 * PrestaShop specific_price Schema:
 * - id_product: PrestaShop product ID
 * - id_shop: Shop ID (or 0 for all shops)
 * - id_group: Customer group ID (mapped from PPM price_group_id)
 * - price: Override price in NETTO (tax excluded)
 * - from_quantity: Minimum quantity (usually 1)
 * - reduction: Discount amount (0 for price override)
 * - reduction_type: "amount" or "percentage"
 *
 * Usage:
 * ```php
 * $exporter = app(PrestaShopPriceExporter::class);
 * $exporter->exportPricesForProduct($product, $shop, $prestashopProductId);
 * ```
 *
 * @package App\Services\PrestaShop
 * @version 1.0
 * @since 2025-11-14
 */
class PrestaShopPriceExporter
{
    /**
     * Constructor with dependency injection
     *
     * @param PriceGroupMapper $priceGroupMapper
     */
    public function __construct(
        protected PriceGroupMapper $priceGroupMapper
    ) {}

    /**
     * Export all prices for product to PrestaShop
     *
     * Workflow:
     * 1. Fetch all PPM product_prices for product
     * 2. Map each price_group_id to PrestaShop customer group
     * 3. Fetch existing specific_prices from PrestaShop
     * 4. CREATE/UPDATE/DELETE to sync prices
     * 5. Update prestashop_mapping JSON in product_prices
     *
     * @param Product $product Product to export prices for
     * @param PrestaShopShop $shop Shop to export to
     * @param int $prestashopProductId PrestaShop product ID
     * @return array Sync results
     * @throws PrestaShopAPIException On API errors
     */
    public function exportPricesForProduct(Product $product, PrestaShopShop $shop, int $prestashopProductId): array
    {
        Log::info('[PRICE EXPORT] Starting price export to PrestaShop', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'shop_id' => $shop->id,
            'prestashop_product_id' => $prestashopProductId,
        ]);

        try {
            $client = PrestaShopClientFactory::create($shop);

            // 1. Get all product prices from PPM
            $ppmPrices = $product->prices()->with('priceGroup')->get();
            Log::debug('[PRICE EXPORT] PPM prices fetched', [
                'count' => $ppmPrices->count(),
            ]);

            // 2. Get existing specific_prices from PrestaShop
            $existingPrices = $this->fetchExistingSpecificPrices($client, $prestashopProductId);
            Log::debug('[PRICE EXPORT] Existing PrestaShop specific_prices fetched', [
                'count' => count($existingPrices),
            ]);

            // 3. Sync prices
            $results = [
                'created' => [],
                'updated' => [],
                'deleted' => [],
                'skipped' => [],
            ];

            // Track which PrestaShop specific_price IDs should exist
            $expectedSpecificPriceIds = [];

            foreach ($ppmPrices as $productPrice) {
                $result = $this->syncSinglePrice(
                    $product,
                    $productPrice,
                    $shop,
                    $client,
                    $prestashopProductId,
                    $existingPrices
                );

                if ($result['action'] === 'created') {
                    $results['created'][] = $result;
                    $expectedSpecificPriceIds[] = $result['specific_price_id'];
                } elseif ($result['action'] === 'updated') {
                    $results['updated'][] = $result;
                    $expectedSpecificPriceIds[] = $result['specific_price_id'];
                } elseif ($result['action'] === 'skipped') {
                    $results['skipped'][] = $result;
                    if (isset($result['specific_price_id'])) {
                        $expectedSpecificPriceIds[] = $result['specific_price_id'];
                    }
                }
            }

            // 4. Delete orphaned specific_prices (removed from PPM)
            $orphanedPrices = $this->findOrphanedPrices($existingPrices, $expectedSpecificPriceIds);
            foreach ($orphanedPrices as $orphanedPrice) {
                try {
                    $client->deleteSpecificPrice($orphanedPrice['id']);
                    $results['deleted'][] = [
                        'specific_price_id' => $orphanedPrice['id'],
                        'id_group' => $orphanedPrice['id_group'],
                    ];
                    Log::info('[PRICE EXPORT] Deleted orphaned specific_price', [
                        'specific_price_id' => $orphanedPrice['id'],
                    ]);
                } catch (PrestaShopAPIException $e) {
                    Log::error('[PRICE EXPORT] Failed to delete orphaned specific_price', [
                        'specific_price_id' => $orphanedPrice['id'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('[PRICE EXPORT] Price export completed', [
                'product_id' => $product->id,
                'created' => count($results['created']),
                'updated' => count($results['updated']),
                'deleted' => count($results['deleted']),
                'skipped' => count($results['skipped']),
            ]);

            return $results;

        } catch (\Exception $e) {
            Log::error('[PRICE EXPORT] Price export failed', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync single product price to PrestaShop
     *
     * @param Product $product Product instance
     * @param ProductPrice $productPrice Product price to sync
     * @param PrestaShopShop $shop Shop instance
     * @param BasePrestaShopClient $client PrestaShop API client
     * @param int $prestashopProductId PrestaShop product ID
     * @param array $existingPrices Existing specific_prices from PrestaShop
     * @return array Sync result
     */
    private function syncSinglePrice(
        Product $product,
        ProductPrice $productPrice,
        PrestaShopShop $shop,
        BasePrestaShopClient $client,
        int $prestashopProductId,
        array $existingPrices
    ): array {
        // Map PPM price group to PrestaShop customer group
        $prestashopGroupId = $this->priceGroupMapper->mapToPrestaShop(
            $productPrice->price_group_id,
            $shop
        );

        if (!$prestashopGroupId) {
            Log::debug('[PRICE EXPORT] Price group not mapped, skipping', [
                'price_group_id' => $productPrice->price_group_id,
                'price_group_code' => $productPrice->priceGroup->code ?? 'N/A',
            ]);

            return [
                'action' => 'skipped',
                'reason' => 'price_group_not_mapped',
                'price_group_id' => $productPrice->price_group_id,
            ];
        }

        // Build specific_price data
        $specificPriceData = $this->buildSpecificPriceData(
            $prestashopProductId,
            $shop,
            $prestashopGroupId,
            $productPrice
        );

        // Check if specific_price already exists
        $existingPrice = $this->findExistingPrice($existingPrices, $prestashopGroupId);

        if ($existingPrice) {
            // UPDATE existing
            $specificPriceId = $existingPrice['id'];

            try {
                $client->updateSpecificPrice($specificPriceId, $specificPriceData);

                Log::debug('[PRICE EXPORT] Updated specific_price', [
                    'specific_price_id' => $specificPriceId,
                    'id_group' => $prestashopGroupId,
                    'price' => $productPrice->price_net,
                ]);

                return [
                    'action' => 'updated',
                    'specific_price_id' => $specificPriceId,
                    'price_group_id' => $productPrice->price_group_id,
                    'price' => $productPrice->price_net,
                ];

            } catch (PrestaShopAPIException $e) {
                Log::error('[PRICE EXPORT] Failed to update specific_price', [
                    'specific_price_id' => $specificPriceId,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'action' => 'skipped',
                    'reason' => 'update_failed',
                    'error' => $e->getMessage(),
                ];
            }
        } else {
            // CREATE new
            try {
                $response = $client->createSpecificPrice($specificPriceData);
                $specificPriceId = $response['specific_price']['id'] ?? null;

                if (!$specificPriceId) {
                    throw new \RuntimeException('Failed to extract specific_price ID from response');
                }

                Log::debug('[PRICE EXPORT] Created specific_price', [
                    'specific_price_id' => $specificPriceId,
                    'id_group' => $prestashopGroupId,
                    'price' => $productPrice->price_net,
                ]);

                return [
                    'action' => 'created',
                    'specific_price_id' => $specificPriceId,
                    'price_group_id' => $productPrice->price_group_id,
                    'price' => $productPrice->price_net,
                ];

            } catch (PrestaShopAPIException $e) {
                Log::error('[PRICE EXPORT] Failed to create specific_price', [
                    'id_group' => $prestashopGroupId,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'action' => 'skipped',
                    'reason' => 'create_failed',
                    'error' => $e->getMessage(),
                ];
            }
        }
    }

    /**
     * Build specific_price data structure for PrestaShop API
     *
     * @param int $prestashopProductId PrestaShop product ID
     * @param PrestaShopShop $shop Shop instance
     * @param int $prestashopGroupId PrestaShop customer group ID
     * @param ProductPrice $productPrice Product price instance
     * @return array Specific price data
     */
    private function buildSpecificPriceData(
        int $prestashopProductId,
        PrestaShopShop $shop,
        int $prestashopGroupId,
        ProductPrice $productPrice
    ): array {
        return [
            'id_product' => $prestashopProductId,
            'id_shop' => $shop->prestashop_shop_id ?? 0, // 0 = all shops in multistore
            'id_currency' => 0, // 0 = all currencies
            'id_country' => 0, // 0 = all countries
            'id_group' => $prestashopGroupId,
            'id_customer' => 0, // 0 = all customers
            'id_product_attribute' => 0, // 0 = base product (not variant)
            'price' => (float) $productPrice->price_net, // NETTO price (PrestaShop requirement)
            'from_quantity' => 1,
            'reduction' => 0.000000,
            'reduction_type' => 'amount',
            'from' => '0000-00-00 00:00:00',
            'to' => '0000-00-00 00:00:00',
        ];
    }

    /**
     * Fetch existing specific_prices from PrestaShop
     *
     * @param BasePrestaShopClient $client PrestaShop API client
     * @param int $prestashopProductId PrestaShop product ID
     * @return array Existing specific_prices
     */
    private function fetchExistingSpecificPrices(BasePrestaShopClient $client, int $prestashopProductId): array
    {
        try {
            $response = $client->getSpecificPrices($prestashopProductId);

            // PrestaShop API returns: {"specific_prices": [{"id": 1, ...}, ...]}
            if (isset($response['specific_prices']) && is_array($response['specific_prices'])) {
                return $response['specific_prices'];
            }

            return [];

        } catch (PrestaShopAPIException $e) {
            // Graceful handling: If product has no specific prices, PrestaShop returns 404
            if ($e->isNotFound()) {
                return [];
            }

            Log::warning('[PRICE EXPORT] Failed to fetch existing specific_prices', [
                'prestashop_product_id' => $prestashopProductId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Find existing specific_price for customer group
     *
     * @param array $existingPrices Existing specific_prices
     * @param int $prestashopGroupId PrestaShop customer group ID
     * @return array|null Existing price or null
     */
    private function findExistingPrice(array $existingPrices, int $prestashopGroupId): ?array
    {
        foreach ($existingPrices as $price) {
            if ((int) $price['id_group'] === $prestashopGroupId) {
                return $price;
            }
        }

        return null;
    }

    /**
     * Find orphaned specific_prices (exist in PrestaShop but removed from PPM)
     *
     * @param array $existingPrices Existing specific_prices from PrestaShop
     * @param array $expectedIds Expected specific_price IDs that should exist
     * @return array Orphaned prices to delete
     */
    private function findOrphanedPrices(array $existingPrices, array $expectedIds): array
    {
        $orphaned = [];

        foreach ($existingPrices as $price) {
            if (!in_array($price['id'], $expectedIds, true)) {
                $orphaned[] = $price;
            }
        }

        return $orphaned;
    }
}
