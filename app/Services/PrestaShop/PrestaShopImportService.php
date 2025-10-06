<?php

namespace App\Services\PrestaShop;

use App\Models\Product;
use App\Models\Category;
use App\Models\PrestaShopShop;
use App\Models\ProductSyncStatus;
use App\Models\SyncLog;
use App\Models\ShopMapping;
use App\Models\ProductPrice;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\PrestaShop\ProductTransformer;
use App\Services\PrestaShop\CategoryTransformer;
use App\Exceptions\PrestaShopAPIException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * PrestaShop Import Service
 *
 * ETAP_07 FAZA 2A.2 - Import produktów i kategorii z PrestaShop do PPM
 *
 * Główny orchestrator service dla importu danych z PrestaShop API do PPM.
 * Wykorzystuje reverse transformers (FAZA 2A.1) do konwersji danych.
 *
 * Features:
 * - Single product import z pełną transformacją danych
 * - Category import z recursive parent handling
 * - Complete category tree import
 * - Database transactions dla data integrity
 * - ProductSyncStatus tracking dla każdego importu
 * - SyncLog audit trail dla wszystkich operacji
 * - Comprehensive error handling z graceful degradation
 *
 * Dependencies:
 * - PrestaShopClientFactory (API client creation)
 * - ProductTransformer (PS → PPM product transformation)
 * - CategoryTransformer (PS → PPM category transformation)
 *
 * Usage Example:
 * ```php
 * $importService = app(PrestaShopImportService::class);
 * $shop = PrestaShopShop::find(1);
 *
 * // Import single product
 * $product = $importService->importProductFromPrestaShop(123, $shop);
 *
 * // Import category with parents
 * $category = $importService->importCategoryFromPrestaShop(7, $shop);
 *
 * // Import entire category tree
 * $categories = $importService->importCategoryTreeFromPrestaShop($shop);
 * ```
 *
 * @package App\Services\PrestaShop
 * @version 1.0
 * @since ETAP_07 FAZA 2A.2
 */
class PrestaShopImportService
{
    /**
     * Constructor with dependency injection
     *
     * @param PrestaShopClientFactory $clientFactory
     * @param ProductTransformer $productTransformer
     * @param CategoryTransformer $categoryTransformer
     */
    public function __construct(
        protected PrestaShopClientFactory $clientFactory,
        protected ProductTransformer $productTransformer,
        protected CategoryTransformer $categoryTransformer
    ) {}

    /**
     * Import single product from PrestaShop to PPM
     *
     * Workflow:
     * 1. Fetch product z PrestaShop API
     * 2. Transform data (PS → PPM format)
     * 3. Check if product exists (by SKU)
     * 4. Create/Update Product
     * 5. Sync ProductPrice records
     * 6. Sync Stock records
     * 7. Update ProductSyncStatus
     * 8. Create SyncLog audit entry
     *
     * @param int $prestashopProductId PrestaShop product ID
     * @param PrestaShopShop $shop Shop instance
     * @return Product Created/updated Product instance
     * @throws PrestaShopAPIException On API errors
     * @throws InvalidArgumentException On validation errors
     */
    public function importProductFromPrestaShop(
        int $prestashopProductId,
        PrestaShopShop $shop
    ): Product
    {
        $startTime = microtime(true);

        Log::info('Starting product import from PrestaShop', [
            'prestashop_product_id' => $prestashopProductId,
            'shop_id' => $shop->id,
            'shop_name' => $shop->name,
        ]);

        try {
            // 1. Create API client dla tego shop
            $client = $this->clientFactory::create($shop);

            // 2. Fetch product from PrestaShop API
            $prestashopData = $client->getProduct($prestashopProductId);

            Log::debug('Product fetched from PrestaShop API', [
                'prestashop_product_id' => $prestashopProductId,
                'reference' => data_get($prestashopData, 'reference'),
                'name' => data_get($prestashopData, 'name.0.value'),
            ]);

            // 3. Transform data using ProductTransformer (PS → PPM)
            $productData = $this->productTransformer->transformToPPM($prestashopData, $shop);
            $pricesData = $this->productTransformer->transformPriceToPPM($prestashopData, $shop);
            $stockData = $this->productTransformer->transformStockToPPM($prestashopData, $shop);

            // 4. Database transaction dla data integrity
            $product = DB::transaction(function () use (
                $productData,
                $pricesData,
                $stockData,
                $prestashopProductId,
                $shop
            ) {
                // 5. Check if product exists (by SKU)
                $existingProduct = Product::where('sku', $productData['sku'])->first();

                if ($existingProduct) {
                    Log::info('Updating existing product', [
                        'product_id' => $existingProduct->id,
                        'sku' => $productData['sku'],
                    ]);

                    // UPDATE existing product
                    $existingProduct->update($productData);
                    $product = $existingProduct;
                } else {
                    Log::info('Creating new product', [
                        'sku' => $productData['sku'],
                    ]);

                    // CREATE new product
                    $product = Product::create($productData);
                }

                // 6. Sync prices (ProductPrice model)
                if (!empty($pricesData)) {
                    foreach ($pricesData as $priceData) {
                        // Get price_group_id z code
                        $priceGroup = \App\Models\PriceGroup::where('code', $priceData['price_group'])->first();

                        if ($priceGroup) {
                            ProductPrice::updateOrCreate(
                                [
                                    'product_id' => $product->id,
                                    'price_group_id' => $priceGroup->id,
                                ],
                                [
                                    'price_net' => $priceData['price'],
                                    'price_gross' => $priceData['price'], // Will be recalculated
                                    'currency' => $priceData['currency'],
                                ]
                            );

                            Log::debug('Price synced', [
                                'product_id' => $product->id,
                                'price_group' => $priceData['price_group'],
                                'price' => $priceData['price'],
                            ]);
                        } else {
                            Log::warning('Price group not found', [
                                'price_group_code' => $priceData['price_group'],
                            ]);
                        }
                    }
                }

                // 7. Sync stock (Stock model) - TYLKO jeśli model Stock istnieje
                if (!empty($stockData) && class_exists('\App\Models\Stock')) {
                    foreach ($stockData as $stockItem) {
                        \App\Models\Stock::updateOrCreate(
                            [
                                'product_id' => $product->id,
                                'warehouse_code' => $stockItem['warehouse_code'],
                            ],
                            [
                                'quantity' => $stockItem['quantity'],
                                'reserved' => $stockItem['reserved'],
                                'available' => $stockItem['available'],
                            ]
                        );

                        Log::debug('Stock synced', [
                            'product_id' => $product->id,
                            'warehouse' => $stockItem['warehouse_code'],
                            'quantity' => $stockItem['quantity'],
                        ]);
                    }
                }

                // 8. Create/Update ProductSyncStatus
                ProductSyncStatus::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'shop_id' => $shop->id,
                    ],
                    [
                        'prestashop_product_id' => $prestashopProductId,
                        'sync_status' => ProductSyncStatus::STATUS_SYNCED,
                        'sync_direction' => ProductSyncStatus::DIRECTION_PS_TO_PPM,
                        'last_sync_at' => now(),
                        'last_success_sync_at' => now(),
                        'error_message' => null,
                    ]
                );

                return $product;
            });

            // 9. Calculate execution time
            $executionTime = (int) ((microtime(true) - $startTime) * 1000);

            // 10. Create SyncLog success entry
            SyncLog::create([
                'shop_id' => $shop->id,
                'product_id' => $product->id,
                'operation' => SyncLog::OPERATION_SYNC_PRODUCT,
                'direction' => SyncLog::DIRECTION_PS_TO_PPM,
                'status' => SyncLog::STATUS_SUCCESS,
                'message' => "Product imported successfully: {$product->name} (SKU: {$product->sku})",
                'execution_time_ms' => $executionTime,
                'created_at' => now(),
            ]);

            Log::info('Product import completed successfully', [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'execution_time_ms' => $executionTime,
            ]);

            // 11. Return Product z relationships
            return $product->fresh(['prices', 'category']);

        } catch (PrestaShopAPIException $e) {
            // Handle PrestaShop API errors
            Log::error('PrestaShop API error during product import', [
                'prestashop_product_id' => $prestashopProductId,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
                'http_status' => $e->getCode(),
            ]);

            // Create SyncLog error entry
            SyncLog::create([
                'shop_id' => $shop->id,
                'operation' => SyncLog::OPERATION_SYNC_PRODUCT,
                'direction' => SyncLog::DIRECTION_PS_TO_PPM,
                'status' => SyncLog::STATUS_ERROR,
                'message' => "PrestaShop API error: {$e->getMessage()}",
                'http_status_code' => $e->getCode(),
                'execution_time_ms' => (int) ((microtime(true) - $startTime) * 1000),
                'created_at' => now(),
            ]);

            throw $e;

        } catch (\Exception $e) {
            // Handle general errors
            Log::error('Unexpected error during product import', [
                'prestashop_product_id' => $prestashopProductId,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Create SyncLog error entry
            SyncLog::create([
                'shop_id' => $shop->id,
                'operation' => SyncLog::OPERATION_SYNC_PRODUCT,
                'direction' => SyncLog::DIRECTION_PS_TO_PPM,
                'status' => SyncLog::STATUS_ERROR,
                'message' => "Import failed: {$e->getMessage()}",
                'execution_time_ms' => (int) ((microtime(true) - $startTime) * 1000),
                'created_at' => now(),
            ]);

            throw new InvalidArgumentException(
                "Failed to import product from PrestaShop: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Import category from PrestaShop to PPM (with optional recursive parent import)
     *
     * Workflow:
     * 1. Fetch category z PrestaShop API
     * 2. Check if parent exists (recursive import if needed)
     * 3. Transform category data (PS → PPM)
     * 4. Check if category already exists (by mapping)
     * 5. Create/Update Category
     * 6. Create/Update ShopMapping
     * 7. Create SyncLog entry
     *
     * @param int $prestashopCategoryId PrestaShop category ID
     * @param PrestaShopShop $shop Shop instance
     * @param bool $recursive If true, recursively import parent categories
     * @return Category Created/updated Category instance
     * @throws PrestaShopAPIException On API errors
     * @throws InvalidArgumentException On validation errors
     */
    public function importCategoryFromPrestaShop(
        int $prestashopCategoryId,
        PrestaShopShop $shop,
        bool $recursive = true
    ): Category
    {
        $startTime = microtime(true);

        Log::info('Starting category import from PrestaShop', [
            'prestashop_category_id' => $prestashopCategoryId,
            'shop_id' => $shop->id,
            'recursive' => $recursive,
        ]);

        try {
            // 1. Create API client
            $client = $this->clientFactory::create($shop);

            // 2. Fetch category from PrestaShop API
            $prestashopData = $client->getCategory($prestashopCategoryId);

            Log::debug('Category fetched from PrestaShop API', [
                'prestashop_category_id' => $prestashopCategoryId,
                'name' => data_get($prestashopData, 'name.0.value'),
                'id_parent' => data_get($prestashopData, 'id_parent'),
            ]);

            // 3. Handle parent category (recursive import if needed)
            $parentId = (int) data_get($prestashopData, 'id_parent', 0);

            // PrestaShop root categories have id_parent = 1 or 2
            if ($parentId > 2) {
                if ($recursive) {
                    Log::debug('Recursively importing parent category', [
                        'parent_id' => $parentId,
                    ]);

                    // Recursive import parent first
                    $parentCategory = $this->importCategoryFromPrestaShop($parentId, $shop, true);

                    Log::debug('Parent category imported', [
                        'parent_category_id' => $parentCategory->id,
                    ]);
                } else {
                    // Non-recursive mode: check if parent mapping exists
                    $parentMapping = ShopMapping::where('shop_id', $shop->id)
                        ->where('mapping_type', ShopMapping::TYPE_CATEGORY)
                        ->where('prestashop_id', $parentId)
                        ->first();

                    if (!$parentMapping) {
                        throw new InvalidArgumentException(
                            "Parent category not found in mappings: PrestaShop ID {$parentId}. " .
                            "Enable recursive import or import parent category first."
                        );
                    }
                }
            }

            // 4. Transform category data (PS → PPM)
            $categoryData = $this->categoryTransformer->transformToPPM($prestashopData, $shop);

            // 5. Database transaction
            $category = DB::transaction(function () use (
                $categoryData,
                $prestashopCategoryId,
                $shop
            ) {
                // 6. Check if category exists (by mapping)
                $mapping = ShopMapping::where('shop_id', $shop->id)
                    ->where('mapping_type', ShopMapping::TYPE_CATEGORY)
                    ->where('prestashop_id', $prestashopCategoryId)
                    ->first();

                if ($mapping) {
                    // Update existing category
                    $category = Category::findOrFail($mapping->ppm_value);

                    Log::info('Updating existing category', [
                        'category_id' => $category->id,
                        'name' => $categoryData['name'],
                    ]);

                    $category->update($categoryData);
                } else {
                    // Create new category
                    Log::info('Creating new category', [
                        'name' => $categoryData['name'],
                        'parent_id' => $categoryData['parent_id'],
                    ]);

                    $category = Category::create($categoryData);

                    // 7. Create ShopMapping
                    ShopMapping::create([
                        'shop_id' => $shop->id,
                        'mapping_type' => ShopMapping::TYPE_CATEGORY,
                        'ppm_value' => $category->id,
                        'prestashop_id' => $prestashopCategoryId,
                        'prestashop_value' => $category->name,
                        'is_active' => true,
                    ]);

                    Log::info('Category mapping created', [
                        'category_id' => $category->id,
                        'prestashop_category_id' => $prestashopCategoryId,
                    ]);
                }

                return $category;
            });

            // 8. Calculate execution time
            $executionTime = (int) ((microtime(true) - $startTime) * 1000);

            // 9. Create SyncLog success entry
            SyncLog::create([
                'shop_id' => $shop->id,
                'operation' => SyncLog::OPERATION_SYNC_CATEGORY,
                'direction' => SyncLog::DIRECTION_PS_TO_PPM,
                'status' => SyncLog::STATUS_SUCCESS,
                'message' => "Category imported successfully: {$category->name}",
                'execution_time_ms' => $executionTime,
                'created_at' => now(),
            ]);

            Log::info('Category import completed successfully', [
                'category_id' => $category->id,
                'name' => $category->name,
                'execution_time_ms' => $executionTime,
            ]);

            // 10. Return Category z relationships
            return $category->fresh(['parent', 'children']);

        } catch (PrestaShopAPIException $e) {
            // Handle PrestaShop API errors
            Log::error('PrestaShop API error during category import', [
                'prestashop_category_id' => $prestashopCategoryId,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            SyncLog::create([
                'shop_id' => $shop->id,
                'operation' => SyncLog::OPERATION_SYNC_CATEGORY,
                'direction' => SyncLog::DIRECTION_PS_TO_PPM,
                'status' => SyncLog::STATUS_ERROR,
                'message' => "PrestaShop API error: {$e->getMessage()}",
                'http_status_code' => $e->getCode(),
                'execution_time_ms' => (int) ((microtime(true) - $startTime) * 1000),
                'created_at' => now(),
            ]);

            throw $e;

        } catch (\Exception $e) {
            // Handle general errors
            Log::error('Unexpected error during category import', [
                'prestashop_category_id' => $prestashopCategoryId,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            SyncLog::create([
                'shop_id' => $shop->id,
                'operation' => SyncLog::OPERATION_SYNC_CATEGORY,
                'direction' => SyncLog::DIRECTION_PS_TO_PPM,
                'status' => SyncLog::STATUS_ERROR,
                'message' => "Import failed: {$e->getMessage()}",
                'execution_time_ms' => (int) ((microtime(true) - $startTime) * 1000),
                'created_at' => now(),
            ]);

            throw new InvalidArgumentException(
                "Failed to import category from PrestaShop: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Import complete category tree from PrestaShop
     *
     * Imports all categories z PrestaShop shop, zachowując hierarchię.
     * Sortuje categories by level_depth aby parent categories były importowane pierwsze.
     *
     * Workflow:
     * 1. Fetch all categories from PrestaShop API
     * 2. Optional: Filter by root category ID
     * 3. Sort by level_depth (parents first)
     * 4. Import each category (non-recursive - already sorted)
     * 5. Collect success/error statistics
     * 6. Return imported categories array
     *
     * @param PrestaShopShop $shop Shop instance
     * @param int|null $rootCategoryId Optional root category to filter by
     * @return array Array of imported Category instances
     * @throws PrestaShopAPIException On API errors
     */
    public function importCategoryTreeFromPrestaShop(
        PrestaShopShop $shop,
        ?int $rootCategoryId = null
    ): array
    {
        $startTime = microtime(true);

        Log::info('Starting category tree import from PrestaShop', [
            'shop_id' => $shop->id,
            'root_category_id' => $rootCategoryId,
        ]);

        try {
            // 1. Create API client
            $client = $this->clientFactory::create($shop);

            // 2. Fetch all categories z display=full dla complete data
            $response = $client->getCategories(['display' => 'full']);

            Log::debug('Raw PrestaShop categories response', [
                'response_type' => gettype($response),
                'response_keys' => is_array($response) ? array_keys($response) : 'not_array',
                'response_sample' => is_array($response) ? array_slice($response, 0, 2) : $response,
            ]);

            // 3. Extract categories from response structure
            // PrestaShop API może zwrócić:
            // - ['categories' => [...]] (zagnieżdżona struktura)
            // - [...] (bezpośrednia tablica kategorii)
            $prestashopCategories = [];

            if (is_array($response)) {
                if (isset($response['categories']) && is_array($response['categories'])) {
                    // Zagnieżdżona struktura
                    $prestashopCategories = $response['categories'];
                } elseif (isset($response[0])) {
                    // Bezpośrednia tablica kategorii
                    $prestashopCategories = $response;
                } else {
                    // Pusta odpowiedź lub nieznana struktura
                    Log::warning('Unexpected PrestaShop categories response structure', [
                        'response_keys' => array_keys($response),
                    ]);
                }
            }

            Log::info('Categories fetched from PrestaShop API', [
                'total_categories' => count($prestashopCategories),
            ]);

            // 3. Optional: Filter by root category
            if ($rootCategoryId) {
                $prestashopCategories = array_filter($prestashopCategories, function($cat) use ($rootCategoryId) {
                    $parentId = (int) data_get($cat, 'id_parent', 0);
                    $categoryId = (int) data_get($cat, 'id', 0);

                    return $parentId == $rootCategoryId || $categoryId == $rootCategoryId;
                });

                Log::debug('Categories filtered by root', [
                    'filtered_count' => count($prestashopCategories),
                    'root_category_id' => $rootCategoryId,
                ]);
            }

            // 4. Sort by level_depth (parents first)
            usort($prestashopCategories, function($a, $b) {
                $levelA = (int) data_get($a, 'level_depth', 0);
                $levelB = (int) data_get($b, 'level_depth', 0);

                return $levelA <=> $levelB;
            });

            Log::debug('Categories sorted by level_depth');

            // 5. Import each category (non-recursive to avoid loops)
            $imported = [];
            $errors = [];

            foreach ($prestashopCategories as $categoryData) {
                $categoryId = (int) data_get($categoryData, 'id');

                // Skip root categories (id 1, 2)
                if ($categoryId <= 2) {
                    Log::debug('Skipping PrestaShop root category', [
                        'category_id' => $categoryId,
                    ]);
                    continue;
                }

                try {
                    // Import category (non-recursive - already sorted by level)
                    $category = $this->importCategoryFromPrestaShop(
                        $categoryId,
                        $shop,
                        false // non-recursive (already sorted)
                    );

                    $imported[] = $category;

                    Log::debug('Category imported', [
                        'category_id' => $category->id,
                        'name' => $category->name,
                        'level' => $category->level,
                    ]);

                } catch (\Exception $e) {
                    // Log error but continue with next category
                    Log::warning('Failed to import category', [
                        'prestashop_category_id' => $categoryId,
                        'name' => data_get($categoryData, 'name.0.value'),
                        'error' => $e->getMessage(),
                    ]);

                    $errors[] = [
                        'prestashop_category_id' => $categoryId,
                        'name' => data_get($categoryData, 'name.0.value'),
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // 6. Calculate execution time
            $executionTime = (int) ((microtime(true) - $startTime) * 1000);

            // 7. Create summary SyncLog entry
            $status = empty($errors) ? SyncLog::STATUS_SUCCESS : SyncLog::STATUS_WARNING;
            $message = sprintf(
                "Category tree import completed: %d imported, %d errors",
                count($imported),
                count($errors)
            );

            SyncLog::create([
                'shop_id' => $shop->id,
                'operation' => SyncLog::OPERATION_SYNC_CATEGORY,
                'direction' => SyncLog::DIRECTION_PS_TO_PPM,
                'status' => $status,
                'message' => $message,
                'response_data' => [
                    'imported_count' => count($imported),
                    'error_count' => count($errors),
                    'errors' => $errors,
                ],
                'execution_time_ms' => $executionTime,
                'created_at' => now(),
            ]);

            Log::info('Category tree import completed', [
                'imported_count' => count($imported),
                'error_count' => count($errors),
                'execution_time_ms' => $executionTime,
            ]);

            // 8. Return imported categories
            return $imported;

        } catch (PrestaShopAPIException $e) {
            // Handle PrestaShop API errors
            Log::error('PrestaShop API error during category tree import', [
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            SyncLog::create([
                'shop_id' => $shop->id,
                'operation' => SyncLog::OPERATION_SYNC_CATEGORY,
                'direction' => SyncLog::DIRECTION_PS_TO_PPM,
                'status' => SyncLog::STATUS_ERROR,
                'message' => "PrestaShop API error: {$e->getMessage()}",
                'http_status_code' => $e->getCode(),
                'execution_time_ms' => (int) ((microtime(true) - $startTime) * 1000),
                'created_at' => now(),
            ]);

            throw $e;

        } catch (\Exception $e) {
            // Handle general errors
            Log::error('Unexpected error during category tree import', [
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            SyncLog::create([
                'shop_id' => $shop->id,
                'operation' => SyncLog::OPERATION_SYNC_CATEGORY,
                'direction' => SyncLog::DIRECTION_PS_TO_PPM,
                'status' => SyncLog::STATUS_ERROR,
                'message' => "Import failed: {$e->getMessage()}",
                'execution_time_ms' => (int) ((microtime(true) - $startTime) * 1000),
                'created_at' => now(),
            ]);

            throw new InvalidArgumentException(
                "Failed to import category tree from PrestaShop: {$e->getMessage()}",
                0,
                $e
            );
        }
    }
}
