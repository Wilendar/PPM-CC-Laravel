<?php

namespace App\Services\PrestaShop;

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ProductStock;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Log;

/**
 * PrestaShop Stock Importer Service
 *
 * PROBLEM #4 - Task 17: PrestaShop Stock Import
 *
 * Imports stock from PrestaShop stock_availables → PPM product_stock.
 * Maps PrestaShop shops to PPM warehouses.
 *
 * Architecture:
 * - Fetches stock_availables via PrestaShop API
 * - Maps PrestaShop shop → PPM warehouse (via warehouses.prestashop_mapping)
 * - Saves erp_mapping JSON per stock record
 * - Updates existing stock or creates new records
 *
 * PrestaShop stock_available fields:
 * - id: stock_available ID
 * - id_product: Product ID
 * - id_product_attribute: Variant ID (0 = main product)
 * - id_shop: Shop ID
 * - quantity: Stock quantity
 * - depends_on_stock: Whether uses advanced stock management
 * - out_of_stock: Out of stock behavior (0=deny, 1=allow, 2=default)
 *
 * PPM product_stock schema:
 * - product_id + warehouse_id (UNIQUE)
 * - quantity, reserved_quantity, available_quantity
 * - erp_mapping JSON: {"prestashop": {"shop_X": {"stock_id": 123, "quantity": 50}}}
 *
 * Mapping Strategy:
 * 1. PrestaShop shop_id → PPM warehouse via warehouses.prestashop_mapping
 * 2. If no mapping exists → use default warehouse (MPPTRADE)
 * 3. Update quantity, preserve reserved_quantity
 *
 * Usage:
 * ```php
 * $importer = app(PrestaShopStockImporter::class);
 * $importedStock = $importer->importStockForProduct($product, $shop);
 * ```
 *
 * @package App\Services\PrestaShop
 * @version 1.0
 * @since PROBLEM #4 - Task 17
 */
class PrestaShopStockImporter
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
     * Import stock for product from PrestaShop
     *
     * Workflow:
     * 1. Fetch stock_availables from PrestaShop API
     * 2. Map PrestaShop shop → PPM warehouse
     * 3. Update/create product_stock records
     * 4. Save erp_mapping JSON
     *
     * @param Product $product Product to import stock for
     * @param PrestaShopShop $shop Shop to import from
     * @return array Array of imported stock records
     * @throws \Exception On API errors
     */
    public function importStockForProduct(Product $product, PrestaShopShop $shop): array
    {
        Log::info('Starting stock import from PrestaShop', [
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

            // 3. Fetch stock_availables from API
            $stockData = $client->getStock($prestashopProductId);

            // Extract stock_availables array
            $stockAvailables = [];
            if (isset($stockData['stock_availables']) && is_array($stockData['stock_availables'])) {
                $stockAvailables = $stockData['stock_availables'];
            } elseif (isset($stockData['stock_available']) && is_array($stockData['stock_available'])) {
                // Single stock_available (not wrapped in array)
                $stockAvailables = [$stockData['stock_available']];
            }

            Log::info('Fetched stock from PrestaShop', [
                'product_id' => $product->id,
                'stock_records_count' => count($stockAvailables),
            ]);

            $imported = [];

            // 4. Import each stock_available
            foreach ($stockAvailables as $stockAvailable) {
                // Extract fields
                $stockId = (int) data_get($stockAvailable, 'id');
                $quantity = (int) data_get($stockAvailable, 'quantity', 0);
                $idShop = (int) data_get($stockAvailable, 'id_shop', 1);
                $idProductAttribute = (int) data_get($stockAvailable, 'id_product_attribute', 0);

                // Skip if this is for a variant (we import variants separately)
                if ($idProductAttribute > 0) {
                    Log::debug('Skipping variant stock (not main product)', [
                        'stock_id' => $stockId,
                        'id_product_attribute' => $idProductAttribute,
                    ]);
                    continue;
                }

                // Map PrestaShop shop → PPM warehouse
                $warehouseId = $this->mapShopToWarehouse($shop, $idShop);

                if (!$warehouseId) {
                    Log::warning('Could not map shop to warehouse', [
                        'shop_id' => $shop->id,
                        'prestashop_shop_id' => $idShop,
                    ]);
                    continue;
                }

                // Update stock
                $this->updateProductStock(
                    product: $product,
                    warehouseId: $warehouseId,
                    quantity: $quantity,
                    erpMapping: [
                        'prestashop' => [
                            "shop_{$shop->id}" => [
                                'stock_available_id' => $stockId,
                                'quantity' => $quantity,
                                'id_shop' => $idShop,
                                'last_synced' => now()->toISOString(),
                            ]
                        ]
                    ]
                );

                $imported[] = [
                    'warehouse_id' => $warehouseId,
                    'quantity' => $quantity,
                    'stock_available_id' => $stockId,
                ];
            }

            Log::info('Stock import completed', [
                'product_id' => $product->id,
                'imported_count' => count($imported),
            ]);

            return $imported;

        } catch (\App\Exceptions\PrestaShopAPIException $e) {
            // BUG #8 FIX #1: Re-throw PrestaShop API exceptions (including 404)
            // Caller (PullProductsFromPrestaShop) will handle 404 specifically
            Log::info('PrestaShop API error during stock import', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'http_status' => $e->getHttpStatusCode(),
                'is_404' => $e->isNotFound(),
                'error' => $e->getMessage(),
            ]);

            throw $e; // Re-throw to caller

        } catch (\Exception $e) {
            Log::error('Stock import failed (generic error)', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Map PrestaShop shop to PPM warehouse
     *
     * Mapping strategy:
     * 1. Check warehouses.prestashop_mapping JSON for shop mapping
     * 2. If no mapping exists → use default warehouse (MPPTRADE)
     *
     * Example warehouses.prestashop_mapping:
     * {
     *   "shop_1": {"warehouse_id": 1, "name": "Main Store"},
     *   "shop_2": {"warehouse_id": 2, "name": "Pitbike Store"}
     * }
     *
     * @param PrestaShopShop $shop Shop instance
     * @param int $prestashopShopId PrestaShop shop ID from stock_available
     * @return int|null PPM warehouse_id or null if no mapping
     */
    protected function mapShopToWarehouse(PrestaShopShop $shop, int $prestashopShopId): ?int
    {
        // Try to find warehouse with prestashop_mapping for this shop
        $warehouses = Warehouse::where('is_active', true)->get();

        foreach ($warehouses as $warehouse) {
            $mapping = $warehouse->prestashop_mapping ?? [];

            // Check if this warehouse has mapping for this shop
            $shopKey = "shop_{$prestashopShopId}";
            if (isset($mapping[$shopKey])) {
                Log::debug('Found warehouse mapping', [
                    'warehouse_id' => $warehouse->id,
                    'warehouse_code' => $warehouse->code,
                    'prestashop_shop_id' => $prestashopShopId,
                ]);
                return $warehouse->id;
            }
        }

        // No mapping found → use default warehouse
        $defaultWarehouse = Warehouse::where('is_default', true)->first();

        if ($defaultWarehouse) {
            Log::debug('Using default warehouse (no mapping found)', [
                'warehouse_id' => $defaultWarehouse->id,
                'warehouse_code' => $defaultWarehouse->code,
                'prestashop_shop_id' => $prestashopShopId,
            ]);
            return $defaultWarehouse->id;
        }

        // No default warehouse → try MPPTRADE
        $mpptrade = Warehouse::where('code', 'mpptrade')->first();

        if ($mpptrade) {
            Log::debug('Using MPPTRADE warehouse (no default found)', [
                'warehouse_id' => $mpptrade->id,
                'prestashop_shop_id' => $prestashopShopId,
            ]);
            return $mpptrade->id;
        }

        // No warehouse available
        Log::error('No warehouse available for stock import', [
            'prestashop_shop_id' => $prestashopShopId,
        ]);
        return null;
    }

    /**
     * Update or create product_stock record
     *
     * @param Product $product Product instance
     * @param int $warehouseId Warehouse ID
     * @param int $quantity Stock quantity
     * @param array $erpMapping ERP mapping data
     * @return void
     */
    protected function updateProductStock(
        Product $product,
        int $warehouseId,
        int $quantity,
        array $erpMapping
    ): void
    {
        // Get existing stock or create new
        $productStock = ProductStock::where('product_id', $product->id)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if ($productStock) {
            // Merge erp_mapping (preserve existing integrations)
            $existingMapping = $productStock->erp_mapping ?? [];
            $mergedMapping = array_merge_recursive($existingMapping, $erpMapping);

            $productStock->update([
                'quantity' => $quantity,
                // Preserve reserved_quantity (don't overwrite)
                'erp_mapping' => $mergedMapping,
            ]);

            Log::debug('Updated existing product_stock', [
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
            ]);
        } else {
            ProductStock::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
                'reserved_quantity' => 0,
                'erp_mapping' => $erpMapping,
                'is_active' => true,
                'track_stock' => true,
            ]);

            Log::debug('Created new product_stock', [
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
            ]);
        }
    }
}
