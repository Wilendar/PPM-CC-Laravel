<?php

namespace App\Services\PrestaShop;

use App\Models\Product;
use App\Models\Category;
use App\Models\PrestaShopShop;
use App\Models\SyncLog;
use App\Models\ShopMapping;
use App\Models\ProductPrice;
use App\Models\ProductShopData;
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
 * - ProductShopData tracking (consolidated sync + snapshot) dla każdego importu
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
     * 7. Update ProductShopData (consolidated sync tracking + snapshot)
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

            // 2a. Unwrap nested 'product' key from PrestaShop API response
            // PrestaShop API returns: {product: {id: 123, reference: "SKU", ...}}
            // We need just the inner product object for transformers
            if (isset($prestashopData['product']) && is_array($prestashopData['product'])) {
                $prestashopData = $prestashopData['product'];
            }

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
                $prestashopData,  // 🔧 FIX: Add $prestashopData for syncProductCategories()
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
                                    'price_gross' => $priceData['price'] * (1 + ($product->tax_rate / 100)),
                                    'currency' => $priceData['currency'],
                                ]
                            );
                        } else{
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
                    }
                }

                // 8. Create/Update ProductShopData (CONSOLIDATED 2025-10-13)
                // CONSOLIDATED: ProductShopData now contains ALL sync tracking + snapshot data
                // ARCHITECTURE: ProductShopData serves as snapshot of PrestaShop data
                // - Created during import to establish baseline
                // - Updated periodically by SyncConflictDetection job
                // - Compared with fresh API data to detect changes
                // - NOT used for override (all data mirrors products table initially)
                ProductShopData::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'shop_id' => $shop->id,
                    ],
                    [
                        // Copy ALL product data to establish snapshot baseline
                        'sku' => $product->sku,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'short_description' => $product->short_description,
                        'long_description' => $product->long_description,
                        'meta_title' => $product->meta_title,
                        'meta_description' => $product->meta_description,

                        // Product classification
                        'product_type_id' => $product->product_type_id,
                        'manufacturer' => $product->manufacturer,
                        'supplier_code' => $product->supplier_code,
                        'ean' => $product->ean,

                        // Physical properties
                        'weight' => $product->weight,
                        'height' => $product->height,
                        'width' => $product->width,
                        'length' => $product->length,
                        'tax_rate' => $product->tax_rate,

                        // Status
                        'is_active' => $product->is_active,
                        'is_published' => true,
                        'published_at' => now(),

                        // CONSOLIDATED: Sync tracking fields (migrated from ProductSyncStatus)
                        'prestashop_product_id' => $prestashopProductId,
                        'external_reference' => $prestashopData['link_rewrite'] ?? null,
                        'sync_status' => ProductShopData::STATUS_SYNCED,
                        'sync_direction' => ProductShopData::DIRECTION_PS_TO_PPM,
                        'last_sync_at' => now(),
                        'last_success_sync_at' => now(),
                        'error_message' => null,
                    ]
                );

                Log::info('ProductShopData created (consolidated sync tracking + snapshot)', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                    'prestashop_product_id' => $prestashopProductId,
                    'external_reference' => $prestashopData['link_rewrite'] ?? null,
                    'purpose' => 'conflict_detection_baseline',
                ]);

                // 10. Sync categories from PrestaShop associations
                // CRITICAL FIX: Products MUST have categories assigned!
                $this->syncProductCategories($product, $prestashopData, $shop);

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
            return $product->fresh(['prices', 'categories']);

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

            // 2a. Unwrap nested 'category' key from PrestaShop API response
            if (isset($prestashopData['category']) && is_array($prestashopData['category'])) {
                $prestashopData = $prestashopData['category'];
            }

            // 3. Handle parent category (recursive import if needed)
            $parentId = (int) data_get($prestashopData, 'id_parent', 0);

            // PrestaShop root categories have id_parent = 1 or 2
            if ($parentId > 2) {
                if ($recursive) {
                    // Recursive import parent first
                    $parentCategory = $this->importCategoryFromPrestaShop($parentId, $shop, true);
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
            }

            // 4. Sort by level_depth (parents first)
            usort($prestashopCategories, function($a, $b) {
                $levelA = (int) data_get($a, 'level_depth', 0);
                $levelB = (int) data_get($b, 'level_depth', 0);

                return $levelA <=> $levelB;
            });

            // 5. Import each category (non-recursive to avoid loops)
            $imported = [];
            $errors = [];

            foreach ($prestashopCategories as $categoryData) {
                $categoryId = (int) data_get($categoryData, 'id');

                // Skip root categories (id 1, 2)
                if ($categoryId <= 2) {
                    continue;
                }

                try{
                    // Import category (non-recursive - already sorted by level)
                    $category = $this->importCategoryFromPrestaShop(
                        $categoryId,
                        $shop,
                        false // non-recursive (already sorted)
                    );

                    $imported[] = $category;

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

    /**
     * Sync product categories from PrestaShop associations
     *
     * PER-SHOP CATEGORIES SUPPORT (2025-10-13):
     * - First import → categories with shop_id=NULL (default)
     * - Re-import from different shop → categories with shop_id=X (per-shop override)
     * - Default categories are NEVER modified after first import
     *
     * Workflow:
     * 1. Extract PrestaShop category IDs from associations
     * 2. Map each PrestaShop category to PPM category via ShopMapping
     * 3. Check if first import (no default categories exist)
     * 4a. First import → Sync default categories (shop_id=NULL)
     * 4b. Re-import → Set per-shop categories (shop_id=X), preserve defaults
     * 5. Log category structure differences for user awareness
     *
     * @param Product $product Product instance
     * @param array $prestashopData Raw PrestaShop product data
     * @param PrestaShopShop $shop Shop instance
     * @return void
     */
    protected function syncProductCategories(
        Product $product,
        array $prestashopData,
        PrestaShopShop $shop
    ): void
    {
        // Extract PrestaShop category IDs from associations
        // Structure: associations.categories = [['id' => 2], ['id' => 51], ...]
        $prestashopCategories = data_get($prestashopData, 'associations.categories', []);

        if (empty($prestashopCategories)) {
            Log::warning('Product has no categories in PrestaShop', [
                'product_id' => $product->id,
                'prestashop_product_id' => data_get($prestashopData, 'id'),
            ]);
            return;
        }

        // Map PrestaShop category IDs to PPM category IDs
        $ppmCategoryIds = [];
        $defaultCategoryId = (int) data_get($prestashopData, 'id_category_default', 0);

        foreach ($prestashopCategories as $index => $psCategory) {
            $prestashopCategoryId = (int) data_get($psCategory, 'id', 0);

            if ($prestashopCategoryId <= 0) {
                continue;
            }

            // Skip PrestaShop root categories (id 1, 2)
            if ($prestashopCategoryId <= 2) {
                continue;
            }

            // Map PrestaShop category to PPM category via ShopMapping
            $mapping = ShopMapping::where('shop_id', $shop->id)
                ->where('mapping_type', ShopMapping::TYPE_CATEGORY)
                ->where('prestashop_id', $prestashopCategoryId)
                ->where('is_active', true)
                ->first();

            if ($mapping) {
                // Mapping exists - use it
                $ppmCategoryIds[$mapping->ppm_value] = [
                    'is_primary' => ($prestashopCategoryId === $defaultCategoryId),
                    'sort_order' => $index,
                ];
            } else {
                // 🔧 FIX 2025-10-14: No mapping exists - check if category already exists in PPM
                // SCENARIO: AnalyzeMissingCategories auto-created categories WITHOUT shop_mappings
                // SOLUTION: Auto-create mapping if category exists, otherwise auto-import

                // Check if category already exists in PPM (by PrestaShop ID = PPM ID)
                // NOTE: Some categories use PrestaShop ID as PPM ID during auto-creation
                $existingCategory = Category::find($prestashopCategoryId);

                if ($existingCategory) {
                    // Category EXISTS in PPM but has NO shop_mapping
                    // AUTO-CREATE shop_mapping instead of trying to import
                    try {
                        $newMapping = ShopMapping::create([
                            'shop_id' => $shop->id,
                            'mapping_type' => ShopMapping::TYPE_CATEGORY,
                            'ppm_value' => $existingCategory->id,
                            'prestashop_id' => $prestashopCategoryId,
                            'prestashop_value' => $existingCategory->name,
                            'is_active' => true,
                        ]);

                        // Add to product categories
                        $ppmCategoryIds[$existingCategory->id] = [
                            'is_primary' => ($prestashopCategoryId === $defaultCategoryId),
                            'sort_order' => $index,
                        ];

                        Log::info('Category exists - auto-created shop_mapping', [
                            'category_id' => $existingCategory->id,
                            'category_name' => $existingCategory->name,
                            'prestashop_category_id' => $prestashopCategoryId,
                            'shop_id' => $shop->id,
                            'product_id' => $product->id,
                            'mapping_id' => $newMapping->id,
                        ]);

                    } catch (\Exception $e) {
                        Log::error('Failed to auto-create shop_mapping', [
                            'prestashop_category_id' => $prestashopCategoryId,
                            'category_id' => $existingCategory->id,
                            'shop_id' => $shop->id,
                            'product_id' => $product->id,
                            'error' => $e->getMessage(),
                        ]);
                        // Continue with next category - don't fail entire product import
                    }
                } else {
                    // Category does NOT exist in PPM - auto-import it
                    Log::info('PrestaShop category not found in PPM - auto-importing', [
                        'prestashop_category_id' => $prestashopCategoryId,
                        'shop_id' => $shop->id,
                        'product_id' => $product->id,
                    ]);

                    try {
                        // Auto-import category with recursive parent import
                        $category = $this->importCategoryFromPrestaShop(
                            $prestashopCategoryId,
                            $shop,
                            true // recursive = true (import parents too)
                        );

                        // Add to product categories
                        $ppmCategoryIds[$category->id] = [
                            'is_primary' => ($prestashopCategoryId === $defaultCategoryId),
                            'sort_order' => $index,
                        ];

                        Log::info('Category auto-imported and assigned to product', [
                            'category_id' => $category->id,
                            'category_name' => $category->name,
                            'prestashop_category_id' => $prestashopCategoryId,
                            'product_id' => $product->id,
                        ]);

                    } catch (\Exception $e) {
                        Log::error('Failed to auto-import category', [
                            'prestashop_category_id' => $prestashopCategoryId,
                            'shop_id' => $shop->id,
                            'product_id' => $product->id,
                            'error' => $e->getMessage(),
                        ]);
                        // Continue with next category - don't fail entire product import
                    }
                }
            }
        }

        if (empty($ppmCategoryIds)) {
            Log::warning('No categories mapped - product will have NO categories!', [
                'product_id' => $product->id,
                'prestashop_categories' => array_column($prestashopCategories, 'id'),
            ]);
            return;
        }

        // === PER-SHOP CATEGORIES LOGIC (2025-10-13) ===

        // Check if this is FIRST IMPORT (product has no default categories)
        $existingDefaultCategories = $product->categories()->get(); // shop_id=NULL

        if ($existingDefaultCategories->isEmpty()) {
            // === FIRST IMPORT ===
            // ARCHITECTURE (2025-10-13): First import saves BOTH default AND per-shop categories

            // STEP 1: Set DEFAULT categories (shop_id=NULL) as baseline
            $product->categories()->sync($ppmCategoryIds); // sync() uses shop_id=NULL by default

            Log::info('First import: Default categories set (shop_id=NULL)', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'category_count' => count($ppmCategoryIds),
                'category_ids' => array_keys($ppmCategoryIds),
            ]);

            // STEP 2: ALSO save per-shop categories (shop_id=X) for first shop
            // This ensures we have tracking for the first shop too!
            DB::table('product_categories')
                ->where('product_id', $product->id)
                ->where('shop_id', $shop->id)
                ->delete(); // Clean any existing per-shop categories

            foreach ($ppmCategoryIds as $categoryId => $pivotData) {
                DB::table('product_categories')->insert([
                    'product_id' => $product->id,
                    'category_id' => $categoryId,
                    'shop_id' => $shop->id, // Per-shop for FIRST shop
                    'is_primary' => $pivotData['is_primary'],
                    'sort_order' => $pivotData['sort_order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Log::info('First import: Per-shop categories ALSO saved (shop_id=X)', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'category_count' => count($ppmCategoryIds),
                'category_ids' => array_keys($ppmCategoryIds),
                'note' => 'First shop also gets per-shop tracking',
            ]);
        } else {
            // === RE-IMPORT ===
            // Product already has default categories - this is re-import from different shop

            $defaultCategoryIds = $existingDefaultCategories->pluck('id')->sort()->values()->toArray();
            $newCategoryIds = collect(array_keys($ppmCategoryIds))->sort()->values()->toArray();

            // Check if categories differ
            if ($defaultCategoryIds !== $newCategoryIds) {
                // Categories DIFFER between default and this shop!
                // ARCHITECTURE (2025-10-13): Per-shop categories ALWAYS saved
                // Modal only decides: "Update DEFAULT categories or keep them?"

                // STEP 1: ALWAYS save per-shop categories (shop_id=X)
                // Reset is_primary for this product + shop
                DB::table('product_categories')
                    ->where('product_id', $product->id)
                    ->where('shop_id', $shop->id)
                    ->update(['is_primary' => false]);

                // Remove existing per-shop categories
                DB::table('product_categories')
                    ->where('product_id', $product->id)
                    ->where('shop_id', $shop->id)
                    ->delete();

                // Insert NEW per-shop categories (shop_id=X)
                foreach ($ppmCategoryIds as $categoryId => $pivotData) {
                    DB::table('product_categories')->insert([
                        'product_id' => $product->id,
                        'category_id' => $categoryId,
                        'shop_id' => $shop->id, // Per-shop override
                        'is_primary' => $pivotData['is_primary'],
                        'sort_order' => $pivotData['sort_order'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                Log::info('Per-shop categories saved (shop_id=X)', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                    'category_count' => count($ppmCategoryIds),
                    'category_ids' => array_keys($ppmCategoryIds),
                ]);

                // STEP 2: Store conflict data for modal
                // Modal asks: "Update DEFAULT categories (shop_id=NULL) to match shop?"
                $shopData = ProductShopData::where('product_id', $product->id)
                    ->where('shop_id', $shop->id)
                    ->first();

                if ($shopData) {
                    $shopData->update([
                        'conflict_data' => [
                            'type' => 'category_structure_diff',
                            'default_categories' => $defaultCategoryIds,
                            'shop_categories' => $newCategoryIds,
                            'detected_at' => now()->toISOString(),
                        ],
                        'requires_resolution' => true, // Modal: "Update default categories?"
                        'conflict_detected_at' => now(),
                    ]);

                    Log::warning('Category conflict detected - modal will ask about DEFAULT categories', [
                        'product_id' => $product->id,
                        'shop_id' => $shop->id,
                        'default_count' => count($defaultCategoryIds),
                        'shop_count' => count($newCategoryIds),
                        'question' => 'Should we update DEFAULT categories (shop_id=NULL) to match shop?',
                    ]);
                }
            } else {
                // 🔧 FIX 2025-10-14 #2: Categories are SAME AS DEFAULT
                // BUT: First shop import STILL needs per-shop tracking!

                // Check if per-shop categories already exist for this shop
                $perShopCount = DB::table('product_categories')
                    ->where('product_id', $product->id)
                    ->where('shop_id', $shop->id)
                    ->count();

                if ($perShopCount > 0) {
                    // Already has per-shop categories - remove them (fallback to default)
                    DB::table('product_categories')
                        ->where('product_id', $product->id)
                        ->where('shop_id', $shop->id)
                        ->delete();

                    Log::info('Re-import: Same categories - removed per-shop override (fallback to default)', [
                        'product_id' => $product->id,
                        'shop_id' => $shop->id,
                    ]);
                } else {
                    // NO per-shop categories exist - FIRST IMPORT from this shop!
                    // CREATE per-shop categories to track this shop (even though same as default)

                    foreach ($ppmCategoryIds as $categoryId => $pivotData) {
                        DB::table('product_categories')->insert([
                            'product_id' => $product->id,
                            'category_id' => $categoryId,
                            'shop_id' => $shop->id, // Per-shop tracking for THIS shop
                            'is_primary' => $pivotData['is_primary'],
                            'sort_order' => $pivotData['sort_order'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    Log::info('First import from this shop: Same categories - ALSO saved per-shop tracking', [
                        'product_id' => $product->id,
                        'shop_id' => $shop->id,
                        'category_count' => count($ppmCategoryIds),
                        'category_ids' => array_keys($ppmCategoryIds),
                        'note' => 'First import from shop - created per-shop entries even though same as default',
                    ]);
                }

                Log::info('Re-import: Same category structure - using default categories', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                    'category_ids' => $defaultCategoryIds,
                ]);
            }
        }
    }
}
