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
use App\Models\FeatureType;
use App\Models\FeatureGroup;
use App\Models\ProductFeature;
use App\Models\PrestashopFeatureMapping;
use App\Services\PrestaShop\VehicleCompatibilitySyncService;
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
        PrestaShopShop $shop,
        bool $importWithVariants = false
    ): Product
    {
        $startTime = microtime(true);

        Log::info('Starting product import from PrestaShop', [
            'prestashop_product_id' => $prestashopProductId,
            'shop_id' => $shop->id,
            'shop_name' => $shop->name,
            'import_with_variants' => $importWithVariants,
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
                $shop,
                $client,  // 🔧 ETAP_07e FIX: Add $client for syncProductFeatures()
                $importWithVariants  // 🔧 FIX: Add for variant import
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

                // 11. FIX 2025-11-25: Build category_mappings from product_categories
                // CRITICAL: ProductForm reads from category_mappings, not product_categories!
                $this->buildCategoryMappingsFromProductCategories($product, $shop);

                // 11b. FIX 2025-12-22: Auto-detect ProductType from category hierarchy
                // If product has no type, detect from level-2 (main) category
                if (!$product->product_type_id) {
                    $primaryCategory = $product->categories()
                        ->wherePivot('is_primary', true)
                        ->first();

                    if (!$primaryCategory) {
                        $primaryCategory = $product->categories()->first();
                    }

                    if ($primaryCategory) {
                        $detectedType = \App\Models\ProductType::detectFromCategory($primaryCategory);

                        if ($detectedType) {
                            $product->update(['product_type_id' => $detectedType->id]);

                            Log::info('ProductType auto-detected from category', [
                                'product_id' => $product->id,
                                'sku' => $product->sku,
                                'category_id' => $primaryCategory->id,
                                'category_name' => $primaryCategory->name,
                                'detected_type_id' => $detectedType->id,
                                'detected_type_name' => $detectedType->name,
                            ]);
                        }
                    }
                }

                // 12. ETAP_07e FIX 2025-12-03: Import product features from PrestaShop
                // CRITICAL: Features must be imported for products to have technical specifications!
                $this->syncProductFeatures($product, $prestashopData, $shop, $client);

                // 13. ETAP_05d FIX 2025-12-22: Import vehicle compatibilities from PrestaShop features
                // Only for spare parts that have compatibility features (431 Oryginal, 433 Zamiennik)
                // FIX 2025-12-22: Support both old (czesc-zamienna) and new (czesci-zamienne) slug
                if (in_array($product->productType?->slug, ['czesc-zamienna', 'czesci-zamienne'])) {
                    try {
                        $compatService = new VehicleCompatibilitySyncService();
                        $compatService->setClient($client);
                        $compatService->setShop($shop);

                        $importedCompat = $compatService->importFromPrestaShopFeatures(
                            $prestashopData,
                            $product,
                            $shop->id
                        );

                        if ($importedCompat->isNotEmpty()) {
                            Log::info('Compatibility imported during bulk import', [
                                'product_id' => $product->id,
                                'sku' => $product->sku,
                                'compatibility_count' => $importedCompat->count(),
                            ]);
                        }
                    } catch (\Exception $compatError) {
                        // Non-blocking: log but continue
                        Log::warning('Failed to import compatibility during bulk import', [
                            'product_id' => $product->id,
                            'sku' => $product->sku,
                            'error' => $compatError->getMessage(),
                        ]);
                    }
                }

                // 14. FIX 2025-12-10: Import product variants (combinations) from PrestaShop
                if ($importWithVariants) {
                    $this->syncProductVariants($product, $prestashopData, $shop, $client);
                }

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
                    // FIX 2025-12-08: Check if category actually exists (handle orphaned mappings)
                    // Orphaned mapping = ShopMapping exists but Category was deleted from PPM
                    $category = Category::find($mapping->ppm_value);

                    if ($category) {
                        // Update existing category
                        Log::info('Updating existing category', [
                            'category_id' => $category->id,
                            'name' => $categoryData['name'],
                        ]);

                        $category->update($categoryData);
                    } else {
                        // Orphaned mapping detected - category doesn't exist in PPM
                        Log::warning('Orphaned mapping detected - category deleted from PPM, will recreate', [
                            'mapping_id' => $mapping->id,
                            'prestashop_id' => $prestashopCategoryId,
                            'orphaned_ppm_value' => $mapping->ppm_value,
                        ]);

                        // Delete orphaned mapping
                        $mapping->delete();

                        // Treat as new category (fall through to else block logic)
                        $mapping = null;
                    }
                }

                if (!$mapping) {
                    // FIX 2025-12-09: Improved parent category handling for imports
                    // - Root categories (PrestaShop id_parent ≤ 2) should have parent_id = null
                    // - Only set parent_id = 2 if "Wszystko" category exists AND this is not a root category
                    $prestashopParentId = (int) data_get($categoryData, 'prestashop_parent_id', 0);

                    if (empty($categoryData['parent_id'])) {
                        // FIX 2025-12-22: Correct category hierarchy
                        // PPM Structure: Baza (level=0) → Wszystko (level=1) → Categories (level=2+)
                        // PrestaShop: Root (id=1) → Home/Wszystko (id=2) → Categories

                        // Find "Baza" (root category, level=0)
                        $bazaCategory = Category::where('name', 'Baza')
                            ->where('level', 0)
                            ->first();

                        // Find "Wszystko" (level=1, child of Baza)
                        $wszystkoCategory = Category::where('name', 'Wszystko')
                            ->where('level', 1)
                            ->first();

                        // Check if this is PrestaShop's root category (id=2, "Wszystko/Home")
                        if ($prestashopCategoryId === 2 || $prestashopParentId <= 1) {
                            // This is PrestaShop's root category (Home/Wszystko)
                            // It should be child of "Baza" in PPM (NOT a root category!)
                            if ($bazaCategory) {
                                $categoryData['parent_id'] = $bazaCategory->id;
                                Log::info('PrestaShop root category - assigning to Baza', [
                                    'prestashop_category_id' => $prestashopCategoryId,
                                    'name' => $categoryData['name'],
                                    'baza_id' => $bazaCategory->id,
                                ]);
                            } else {
                                // Baza doesn't exist - create as actual root (this should be rare)
                                $categoryData['parent_id'] = null;
                                Log::warning('Baza category not found - creating as root', [
                                    'prestashop_category_id' => $prestashopCategoryId,
                                    'name' => $categoryData['name'],
                                ]);
                            }
                        } else {
                            // Non-root category - should be child of "Wszystko"
                            if ($wszystkoCategory) {
                                $categoryData['parent_id'] = $wszystkoCategory->id;
                                Log::info('Found "Wszystko" category dynamically', [
                                    'wszystko_id' => $wszystkoCategory->id,
                                    'prestashop_category_id' => $prestashopCategoryId,
                                    'category_name' => $categoryData['name'],
                                ]);
                            } elseif ($bazaCategory) {
                                // Wszystko doesn't exist but Baza does - use Baza as fallback
                                $categoryData['parent_id'] = $bazaCategory->id;
                                Log::warning('"Wszystko" not found - using Baza as fallback parent', [
                                    'prestashop_category_id' => $prestashopCategoryId,
                                    'name' => $categoryData['name'],
                                    'baza_id' => $bazaCategory->id,
                                ]);
                            } else {
                                // Neither exists - create as root (edge case)
                                $categoryData['parent_id'] = null;
                                Log::warning('Neither Baza nor Wszystko found - creating as root', [
                                    'prestashop_category_id' => $prestashopCategoryId,
                                    'name' => $categoryData['name'],
                                ]);
                            }
                        }
                    }

                    // Create new category
                    Log::info('Creating new category', [
                        'name' => $categoryData['name'],
                        'parent_id' => $categoryData['parent_id'],
                        'prestashop_category_id' => $prestashopCategoryId,
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
                // 🔧 FIX 2025-12-15: Validate category exists before using mapping
                // Prevents FK constraint violation if category was deleted but mapping remains
                $categoryId = (int) $mapping->ppm_value;
                $categoryExists = Category::where('id', $categoryId)->exists();

                if ($categoryExists) {
                    // Category exists - safe to use mapping
                    $ppmCategoryIds[$categoryId] = [
                        'is_primary' => ($prestashopCategoryId === $defaultCategoryId),
                        'sort_order' => $index,
                    ];
                } else {
                    // ORPHAN MAPPING DETECTED - category was deleted but mapping remains
                    Log::warning('Orphan shop_mapping detected - category does not exist', [
                        'mapping_id' => $mapping->id,
                        'ppm_value' => $mapping->ppm_value,
                        'prestashop_id' => $prestashopCategoryId,
                        'shop_id' => $shop->id,
                        'product_id' => $product->id,
                    ]);

                    // Deactivate orphan mapping
                    $mapping->update(['is_active' => false]);

                    // Try auto-import category as fallback
                    try {
                        $category = $this->importCategoryFromPrestaShop(
                            $prestashopCategoryId,
                            $shop,
                            true // recursive
                        );

                        // Update mapping with correct ppm_value and reactivate
                        $mapping->update([
                            'ppm_value' => $category->id,
                            'prestashop_value' => $category->name,
                            'is_active' => true,
                        ]);

                        $ppmCategoryIds[$category->id] = [
                            'is_primary' => ($prestashopCategoryId === $defaultCategoryId),
                            'sort_order' => $index,
                        ];

                        Log::info('Orphan mapping repaired - category auto-imported', [
                            'mapping_id' => $mapping->id,
                            'new_category_id' => $category->id,
                            'category_name' => $category->name,
                            'prestashop_id' => $prestashopCategoryId,
                        ]);
                    } catch (\Exception $e) {
                        // Skip this category entirely - cannot repair
                        Log::error('Failed to auto-repair orphan mapping', [
                            'mapping_id' => $mapping->id,
                            'prestashop_id' => $prestashopCategoryId,
                            'error' => $e->getMessage(),
                        ]);
                        // Continue to next category - don't fail entire product import
                    }
                }
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

        // FIX 2025-11-25: Ensure PPM base category "Baza" (id=1) is always assigned
        // "Baza" is PPM-only category (not in PrestaShop) required for category tree visibility
        $this->ensureBaseCategoryAssigned($product, $shop);
    }

    /**
     * Ensure PPM base categories "Baza" and "Wszystko" are assigned to product
     *
     * FIX 2025-11-25 v2: PrestaShop imports lack PPM root categories
     * Both Baza (id=1) AND Wszystko (id=2) are required for category tree expansion
     *
     * @param Product $product
     * @param PrestaShopShop $shop
     * @return void
     */
    protected function ensureBaseCategoryAssigned(Product $product, PrestaShopShop $shop): void
    {
        // PPM root category IDs - Baza (id=1) and Wszystko (id=2)
        $rootCategoryIds = [
            1, // Baza - root category
            2, // Wszystko - child of Baza, parent of all product categories
        ];

        foreach ($rootCategoryIds as $categoryId) {
            $category = Category::find($categoryId);
            if (!$category) {
                Log::warning('PPM root category not found', [
                    'product_id' => $product->id,
                    'category_id' => $categoryId,
                ]);
                continue;
            }

            // Check and add to DEFAULT categories (shop_id=NULL)
            $hasDefault = DB::table('product_categories')
                ->where('product_id', $product->id)
                ->where('category_id', $categoryId)
                ->whereNull('shop_id')
                ->exists();

            if (!$hasDefault) {
                DB::table('product_categories')->insert([
                    'product_id' => $product->id,
                    'category_id' => $categoryId,
                    'shop_id' => null,
                    'is_primary' => false,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('PPM root category assigned (default)', [
                    'product_id' => $product->id,
                    'category_id' => $categoryId,
                    'category_name' => $category->name,
                ]);
            }

            // Check and add to PER-SHOP categories
            $hasPerShop = DB::table('product_categories')
                ->where('product_id', $product->id)
                ->where('category_id', $categoryId)
                ->where('shop_id', $shop->id)
                ->exists();

            if (!$hasPerShop) {
                DB::table('product_categories')->insert([
                    'product_id' => $product->id,
                    'category_id' => $categoryId,
                    'shop_id' => $shop->id,
                    'is_primary' => false,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('PPM root category assigned (per-shop)', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                    'category_id' => $categoryId,
                    'category_name' => $category->name,
                ]);
            }
        }

        // FIX 2025-11-25 v3: ALSO update ProductShopData.category_mappings.ui.selected
        // ProductForm reads from category_mappings, not product_categories table!
        $this->syncRootCategoriesToProductShopData($product, $shop, $rootCategoryIds);
    }

    /**
     * Build category_mappings from product_categories table
     *
     * FIX 2025-11-25: Main fix for UI/DB mismatch
     * ProductForm reads from ProductShopData.category_mappings, NOT product_categories table
     * This method builds the category_mappings structure after syncProductCategories()
     *
     * @param Product $product
     * @param PrestaShopShop $shop
     * @return void
     */
    protected function buildCategoryMappingsFromProductCategories(Product $product, PrestaShopShop $shop): void
    {
        // Get per-shop categories first, fallback to default
        $categories = DB::table('product_categories')
            ->where('product_id', $product->id)
            ->where('shop_id', $shop->id)
            ->get();

        if ($categories->isEmpty()) {
            // Fallback to default categories (shop_id=NULL)
            $categories = DB::table('product_categories')
                ->where('product_id', $product->id)
                ->whereNull('shop_id')
                ->get();
        }

        if ($categories->isEmpty()) {
            Log::warning('No categories found for product - cannot build category_mappings', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
            ]);
            return;
        }

        // Build ui.selected - all category IDs
        $selectedIds = $categories->pluck('category_id')->toArray();

        // Find primary category
        $primaryCategory = $categories->firstWhere('is_primary', true);
        $primaryId = $primaryCategory ? $primaryCategory->category_id : $selectedIds[0];

        // Build mappings - category_id => prestashop_id
        $mappings = [];
        foreach ($selectedIds as $categoryId) {
            // Get PrestaShop mapping
            $shopMapping = ShopMapping::where('shop_id', $shop->id)
                ->where('mapping_type', ShopMapping::TYPE_CATEGORY)
                ->where('ppm_value', $categoryId)
                ->where('is_active', true)
                ->first();

            if ($shopMapping) {
                $mappings[(string)$categoryId] = $shopMapping->prestashop_id;
            } else {
                // No mapping - use category_id as prestashop_id (auto-created categories)
                $mappings[(string)$categoryId] = $categoryId;
            }
        }

        // Root categories (1, 2) are PPM-only, don't need PrestaShop mapping
        // But they MUST be in ui.selected for tree visibility
        // FIX 2025-12-08: ALSO add to mappings with value 0 (no PrestaShop mapping)
        // Without this, validator fails: "Mappings keys must match selected categories"
        $rootCategoryIds = [1, 2];
        foreach ($rootCategoryIds as $rootId) {
            if (!in_array($rootId, $selectedIds)) {
                $selectedIds[] = $rootId;
                $mappings[(string)$rootId] = 0; // 0 = PPM-only, no PrestaShop mapping
            }
        }

        // Build complete category_mappings structure
        $categoryMappings = [
            'ui' => [
                'selected' => $selectedIds,
                'primary' => $primaryId,
            ],
            'mappings' => $mappings,
            'metadata' => [
                'last_updated' => now()->toIso8601String(),
                'source' => 'import_build',
            ],
        ];

        // Update ProductShopData
        $productShopData = ProductShopData::where('product_id', $product->id)
            ->where('shop_id', $shop->id)
            ->first();

        if ($productShopData) {
            $productShopData->category_mappings = $categoryMappings;
            $productShopData->save();

            Log::info('Built category_mappings from product_categories', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'selected_count' => count($selectedIds),
                'selected_ids' => $selectedIds,
                'primary_id' => $primaryId,
                'mappings_count' => count($mappings),
            ]);
        } else {
            Log::warning('ProductShopData not found - cannot save category_mappings', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
            ]);
        }
    }

    /**
     * Sync root categories (Baza, Wszystko) to ProductShopData.category_mappings
     *
     * FIX 2025-11-25 v3: ProductForm uses category_mappings.ui.selected, not product_categories table
     * This ensures UI shows correct checkboxes for root categories
     *
     * @param Product $product
     * @param PrestaShopShop $shop
     * @param array $rootCategoryIds
     * @return void
     */
    protected function syncRootCategoriesToProductShopData(Product $product, PrestaShopShop $shop, array $rootCategoryIds): void
    {
        $productShopData = \App\Models\ProductShopData::where('product_id', $product->id)
            ->where('shop_id', $shop->id)
            ->first();

        if (!$productShopData) {
            Log::debug('No ProductShopData to sync root categories', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
            ]);
            return;
        }

        $categoryMappings = $productShopData->category_mappings;

        // FIX 2025-11-25 v4: Don't modify if mappings is empty
        // CategoryMappingsCast returns empty structure for NULL, validation requires min:1 mappings
        if (empty($categoryMappings) || !isset($categoryMappings['ui']['selected']) || empty($categoryMappings['mappings'])) {
            Log::debug('ProductShopData has empty/invalid category_mappings - skipping root sync', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'has_mappings' => !empty($categoryMappings['mappings']),
            ]);
            return;
        }

        $selected = $categoryMappings['ui']['selected'];
        $updated = false;

        foreach ($rootCategoryIds as $categoryId) {
            if (!in_array($categoryId, $selected)) {
                $selected[] = $categoryId;
                $updated = true;

                Log::info('Added root category to ProductShopData.category_mappings', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                    'category_id' => $categoryId,
                ]);
            }
        }

        if ($updated) {
            $categoryMappings['ui']['selected'] = $selected;
            $categoryMappings['metadata']['last_updated'] = now()->toIso8601String();
            $categoryMappings['metadata']['source'] = 'import_root_sync';

            $productShopData->category_mappings = $categoryMappings;
            $productShopData->save();

            Log::info('ProductShopData.category_mappings updated with root categories', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'selected_count' => count($selected),
            ]);
        }
    }

    /**
     * Import product features from PrestaShop associations
     *
     * ETAP_07e FIX 2025-12-03 - Feature import during product import
     *
     * Workflow:
     * 1. Extract product_features from PrestaShop associations
     * 2. For each feature, find PrestashopFeatureMapping
     * 3. Fetch feature value text from PrestaShop API
     * 4. Create/update ProductFeature records in PPM
     *
     * PrestaShop structure:
     * associations.product_features = [
     *   {id: 5, id_feature_value: 42},  // id = feature type, id_feature_value = value
     *   {id: 8, id_feature_value: 103},
     *   ...
     * ]
     *
     * @param Product $product PPM Product
     * @param array $prestashopData Raw PrestaShop product data
     * @param PrestaShopShop $shop Shop instance
     * @param mixed $client PrestaShop API client
     * @return void
     */
    protected function syncProductFeatures(
        Product $product,
        array $prestashopData,
        PrestaShopShop $shop,
        $client
    ): void {
        // Extract features from PrestaShop associations
        $prestashopFeatures = data_get($prestashopData, 'associations.product_features', []);

        if (empty($prestashopFeatures)) {
            Log::debug('[FEATURE IMPORT] No features in PrestaShop product', [
                'product_id' => $product->id,
                'prestashop_product_id' => data_get($prestashopData, 'id'),
            ]);
            return;
        }

        Log::info('[FEATURE IMPORT] Starting feature import', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'feature_count' => count($prestashopFeatures),
        ]);

        $importedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($prestashopFeatures as $psFeature) {
            $psFeatureId = (int) data_get($psFeature, 'id', 0);
            $psFeatureValueId = (int) data_get($psFeature, 'id_feature_value', 0);

            if ($psFeatureId <= 0 || $psFeatureValueId <= 0) {
                $skippedCount++;
                continue;
            }

            try {
                // Find mapping: PrestaShop feature ID -> PPM FeatureType
                $mapping = PrestashopFeatureMapping::where('shop_id', $shop->id)
                    ->where('prestashop_feature_id', $psFeatureId)
                    ->where('is_active', true)
                    ->first();

                if (!$mapping) {
                    // No mapping - try to auto-create one by matching feature name
                    $mapping = $this->autoCreateFeatureMapping($psFeatureId, $shop, $client);

                    if (!$mapping) {
                        Log::debug('[FEATURE IMPORT] No mapping for PS feature', [
                            'prestashop_feature_id' => $psFeatureId,
                            'shop_id' => $shop->id,
                        ]);
                        $skippedCount++;
                        continue;
                    }
                }

                // Check if mapping allows import (sync_direction)
                if (!$mapping->canPullFromPrestaShop()) {
                    Log::debug('[FEATURE IMPORT] Mapping does not allow import', [
                        'mapping_id' => $mapping->id,
                        'sync_direction' => $mapping->sync_direction,
                    ]);
                    $skippedCount++;
                    continue;
                }

                // Fetch feature value text from PrestaShop API
                $featureValueText = $this->getFeatureValueText($psFeatureValueId, $shop, $client);

                if ($featureValueText === null) {
                    Log::warning('[FEATURE IMPORT] Could not get feature value text', [
                        'prestashop_feature_value_id' => $psFeatureValueId,
                    ]);
                    $skippedCount++;
                    continue;
                }

                // Create/update ProductFeature in PPM
                ProductFeature::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'feature_type_id' => $mapping->feature_type_id,
                    ],
                    [
                        'custom_value' => $featureValueText,
                        'feature_value_id' => null, // Using custom_value, not predefined
                    ]
                );

                $importedCount++;

                Log::debug('[FEATURE IMPORT] Feature imported', [
                    'product_id' => $product->id,
                    'feature_type_id' => $mapping->feature_type_id,
                    'prestashop_feature_id' => $psFeatureId,
                    'value' => $featureValueText,
                ]);

            } catch (\Exception $e) {
                $errorCount++;
                Log::error('[FEATURE IMPORT] Error importing feature', [
                    'product_id' => $product->id,
                    'prestashop_feature_id' => $psFeatureId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('[FEATURE IMPORT] Completed', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'imported' => $importedCount,
            'skipped' => $skippedCount,
            'errors' => $errorCount,
        ]);
    }

    /**
     * Get feature value text from PrestaShop API
     *
     * @param int $featureValueId PrestaShop feature_value ID
     * @param PrestaShopShop $shop Shop instance
     * @param mixed $client PrestaShop API client
     * @return string|null Feature value text or null if not found
     */
    protected function getFeatureValueText(int $featureValueId, PrestaShopShop $shop, $client): ?string
    {
        try {
            // Use getProductFeatureValue method (PrestaShop8Client)
            $valueData = $client->getProductFeatureValue($featureValueId);

            // Unwrap if nested
            if (isset($valueData['product_feature_value'])) {
                $valueData = $valueData['product_feature_value'];
            }

            // Extract value text (multilang structure)
            // Structure: {value: [{id: 1, value: "Text PL"}, {id: 2, value: "Text EN"}]}
            $valueText = data_get($valueData, 'value');

            if (is_array($valueText)) {
                // Multilang - get first language value
                $firstLang = reset($valueText);
                return is_array($firstLang) ? data_get($firstLang, 'value') : $firstLang;
            }

            return $valueText;

        } catch (\Exception $e) {
            Log::warning('[FEATURE IMPORT] Failed to fetch feature value', [
                'feature_value_id' => $featureValueId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Auto-create feature mapping by matching PrestaShop feature name to PPM FeatureType
     * If no matching FeatureType exists, creates a new one automatically
     *
     * @param int $psFeatureId PrestaShop feature ID
     * @param PrestaShopShop $shop Shop instance
     * @param mixed $client PrestaShop API client
     * @return PrestashopFeatureMapping|null Created mapping or null
     */
    protected function autoCreateFeatureMapping(int $psFeatureId, PrestaShopShop $shop, $client): ?PrestashopFeatureMapping
    {
        try {
            // Fetch feature details from PrestaShop using getProductFeature()
            $featureData = $client->getProductFeature($psFeatureId);

            // Unwrap if nested
            if (isset($featureData['product_feature'])) {
                $featureData = $featureData['product_feature'];
            }

            if (!$featureData) {
                return null;
            }

            // Extract feature name (multilang)
            $featureName = data_get($featureData, 'name');
            if (is_array($featureName)) {
                $firstLang = reset($featureName);
                $featureName = is_array($firstLang) ? data_get($firstLang, 'value') : $firstLang;
            }

            if (!$featureName) {
                return null;
            }

            // Find matching PPM FeatureType by name or prestashop_name
            $featureType = FeatureType::where('prestashop_name', $featureName)
                ->orWhere('name', $featureName)
                ->first();

            // FIX 2025-12-03: Auto-create FeatureType if not found
            if (!$featureType) {
                // Generate code from name (lowercase, underscores, no special chars)
                $code = $this->generateFeatureTypeCode($featureName);

                // FIX 2025-12-03 v2: Get or create "Importowane z PrestaShop" FeatureGroup
                $importedGroup = $this->getOrCreateImportedFeatureGroup();

                // Create new FeatureType with feature_group_id for UI display
                $featureType = FeatureType::create([
                    'code' => $code,
                    'name' => $featureName,
                    'value_type' => FeatureType::VALUE_TYPE_TEXT, // Default to text
                    'prestashop_name' => $featureName,
                    'is_active' => true,
                    'feature_group_id' => $importedGroup->id, // CRITICAL: UI filters by this!
                    'group' => 'Importowane z PrestaShop', // Legacy field
                ]);

                Log::info('[FEATURE IMPORT] Auto-created FeatureType', [
                    'feature_type_id' => $featureType->id,
                    'code' => $featureType->code,
                    'name' => $featureType->name,
                    'prestashop_feature_id' => $psFeatureId,
                    'prestashop_feature_name' => $featureName,
                ]);
            }

            // Create mapping
            $mapping = PrestashopFeatureMapping::create([
                'feature_type_id' => $featureType->id,
                'shop_id' => $shop->id,
                'prestashop_feature_id' => $psFeatureId,
                'prestashop_feature_name' => $featureName,
                'sync_direction' => PrestashopFeatureMapping::SYNC_BOTH,
                'auto_create_values' => true,
                'is_active' => true,
            ]);

            Log::info('[FEATURE IMPORT] Auto-created feature mapping', [
                'mapping_id' => $mapping->id,
                'feature_type_id' => $featureType->id,
                'feature_type_name' => $featureType->name,
                'prestashop_feature_id' => $psFeatureId,
                'prestashop_feature_name' => $featureName,
                'shop_id' => $shop->id,
            ]);

            return $mapping;

        } catch (\Exception $e) {
            Log::warning('[FEATURE IMPORT] Failed to auto-create mapping', [
                'prestashop_feature_id' => $psFeatureId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate a unique code for FeatureType from name
     *
     * @param string $name Feature name
     * @return string Generated code
     */
    protected function generateFeatureTypeCode(string $name): string
    {
        // Convert to lowercase, replace spaces with underscores, remove special chars
        $code = strtolower($name);
        $code = preg_replace('/[^a-z0-9_\s]/', '', $code);
        $code = preg_replace('/\s+/', '_', $code);
        $code = trim($code, '_');

        // Ensure unique code
        $baseCode = $code;
        $counter = 1;
        while (FeatureType::where('code', $code)->exists()) {
            $code = $baseCode . '_' . $counter;
            $counter++;
        }

        return $code;
    }

    /**
     * Get or create the "Nieprzydzielone" FeatureGroup
     *
     * FIX 2025-12-03: Required for UI to display imported features
     * The UI filters by feature_group_id, not the legacy 'group' string field
     * UPDATE 2025-12-03: Renamed to "Nieprzydzielone" (Unassigned)
     *
     * @return FeatureGroup
     */
    protected function getOrCreateImportedFeatureGroup(): FeatureGroup
    {
        // Try new code first, then legacy code for backward compatibility
        $group = FeatureGroup::where('code', 'unassigned')
            ->orWhere('code', 'imported_prestashop')
            ->first();

        if (!$group) {
            $group = FeatureGroup::create([
                'code' => 'unassigned',
                'name' => 'Unassigned',
                'name_pl' => 'Nieprzydzielone',
                'icon' => 'info',
                'color' => 'gray',
                'sort_order' => 999, // At the end
                'description' => 'Cechy bez przypisanej grupy - do przydzielenia',
                'is_active' => true,
                'is_collapsible' => true,
            ]);

            Log::info('[FEATURE IMPORT] Auto-created FeatureGroup for unassigned features', [
                'group_id' => $group->id,
                'code' => $group->code,
            ]);
        }

        return $group;
    }

    /**
     * Import product variants (combinations) from PrestaShop to PPM
     *
     * FIX 2025-12-10: Import variants from PrestaShop combinations API
     *
     * Workflow:
     * 1. Fetch combinations from PrestaShop API
     * 2. For each combination:
     *    - Create/Update ProductVariant
     *    - Create/Update VariantAttribute (link to AttributeValue)
     *    - Create/Update VariantPrice
     *    - Create/Update VariantStock
     *
     * PrestaShop combination structure:
     * - id: combination ID
     * - reference: SKU
     * - ean13: EAN code
     * - price: price impact (modifier)
     * - weight: weight modifier
     * - quantity: stock quantity
     * - default_on: is default variant (1 or empty)
     * - associations.product_option_values: array of attribute values
     *
     * @param Product $product PPM Product
     * @param array $prestashopData Raw PrestaShop product data
     * @param PrestaShopShop $shop Shop instance
     * @param mixed $client PrestaShop API client
     * @return void
     */
    protected function syncProductVariants(
        Product $product,
        array $prestashopData,
        PrestaShopShop $shop,
        $client
    ): void {
        $prestashopProductId = (int) data_get($prestashopData, 'id', 0);

        if ($prestashopProductId <= 0) {
            Log::warning('[VARIANT IMPORT] No PrestaShop product ID', [
                'product_id' => $product->id,
            ]);
            return;
        }

        Log::info('[VARIANT IMPORT] Starting variant import', [
            'product_id' => $product->id,
            'prestashop_product_id' => $prestashopProductId,
            'shop_id' => $shop->id,
        ]);

        try {
            // Fetch all combinations for this product
            $combinations = $client->getCombinations($prestashopProductId);

            if (empty($combinations)) {
                Log::info('[VARIANT IMPORT] No combinations found', [
                    'product_id' => $product->id,
                    'prestashop_product_id' => $prestashopProductId,
                ]);
                return;
            }

            Log::info('[VARIANT IMPORT] Found combinations', [
                'product_id' => $product->id,
                'combination_count' => count($combinations),
            ]);

            $importedCount = 0;
            $skippedCount = 0;
            $errorCount = 0;

            foreach ($combinations as $combination) {
                try {
                    $result = $this->importSingleVariant($product, $combination, $shop, $client);

                    if ($result === 'imported' || $result === 'updated') {
                        $importedCount++;
                    } else {
                        $skippedCount++;
                    }

                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error('[VARIANT IMPORT] Error importing variant', [
                        'product_id' => $product->id,
                        'combination_id' => data_get($combination, 'id'),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('[VARIANT IMPORT] Completed', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'imported' => $importedCount,
                'skipped' => $skippedCount,
                'errors' => $errorCount,
            ]);

            // FIX: Mark product as variant master if variants were imported
            if ($importedCount > 0 && !$product->is_variant_master) {
                $product->update(['is_variant_master' => true]);
                Log::info('[VARIANT IMPORT] Product marked as variant master', [
                    'product_id' => $product->id,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('[VARIANT IMPORT] Failed to fetch combinations', [
                'product_id' => $product->id,
                'prestashop_product_id' => $prestashopProductId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Import single variant (combination) from PrestaShop
     *
     * @param Product $product PPM Product
     * @param array $combination PrestaShop combination data
     * @param PrestaShopShop $shop Shop instance
     * @param mixed $client PrestaShop API client
     * @return string 'imported'|'updated'|'skipped'
     */
    protected function importSingleVariant(
        Product $product,
        array $combination,
        PrestaShopShop $shop,
        $client
    ): string {
        $combinationId = (int) data_get($combination, 'id', 0);
        $variantSku = data_get($combination, 'reference', '');

        // Generate SKU if not provided
        if (empty($variantSku)) {
            $variantSku = $product->sku . '-V' . $combinationId;
            Log::debug('[VARIANT IMPORT] Generated variant SKU', [
                'generated_sku' => $variantSku,
                'combination_id' => $combinationId,
            ]);
        }

        // Build variant name from attribute values
        $variantName = $this->buildVariantName($combination, $client);
        if (empty($variantName)) {
            $variantName = "Wariant #{$combinationId}";
        }

        // Check if variant exists (by SKU)
        $existingVariant = \App\Models\ProductVariant::where('sku', $variantSku)->first();
        $isUpdate = (bool) $existingVariant;

        // Prepare variant data
        $variantData = [
            'product_id' => $product->id,
            'sku' => $variantSku,
            'name' => $variantName,
            'is_active' => true,
            'is_default' => data_get($combination, 'default_on') == '1',
            'position' => (int) data_get($combination, 'position', 0),
        ];

        // Create or update variant
        if ($existingVariant) {
            $existingVariant->update($variantData);
            $variant = $existingVariant;
        } else {
            $variant = \App\Models\ProductVariant::create($variantData);
        }

        // Import variant attributes (color, size, etc.)
        $this->importVariantAttributes($variant, $combination, $shop, $client);

        // Import variant price (price modifier)
        $this->importVariantPrice($variant, $combination, $product);

        // Import variant stock
        $this->importVariantStock($variant, $combination);

        // Import variant images from PrestaShop combination
        $this->importVariantImages($variant, $combination, $product, $shop);

        Log::debug('[VARIANT IMPORT] Variant ' . ($isUpdate ? 'updated' : 'imported'), [
            'variant_id' => $variant->id,
            'sku' => $variantSku,
            'name' => $variantName,
            'combination_id' => $combinationId,
        ]);

        return $isUpdate ? 'updated' : 'imported';
    }

    /**
     * Build variant name from attribute values
     *
     * @param array $combination PrestaShop combination data
     * @param mixed $client PrestaShop API client
     * @return string Variant name (e.g., "Czerwony / XL")
     */
    protected function buildVariantName(array $combination, $client): string
    {
        $attributeNames = [];

        // Get product_option_values from associations
        $optionValues = data_get($combination, 'associations.product_option_values', []);

        if (empty($optionValues)) {
            return '';
        }

        foreach ($optionValues as $optionValue) {
            $optionValueId = (int) data_get($optionValue, 'id', 0);

            if ($optionValueId <= 0) {
                continue;
            }

            try {
                // Fetch attribute value name from PrestaShop
                $valueData = $client->getProductOptionValue($optionValueId);

                if (isset($valueData['product_option_value'])) {
                    $valueData = $valueData['product_option_value'];
                }

                // Extract name (multilang structure)
                $name = data_get($valueData, 'name');

                if (is_array($name)) {
                    // Use first language value
                    $name = data_get($name, '0.value', data_get($name, 'language.value', ''));
                }

                if (!empty($name)) {
                    $attributeNames[] = $name;
                }

            } catch (\Exception $e) {
                Log::debug('[VARIANT IMPORT] Could not get option value name', [
                    'option_value_id' => $optionValueId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return implode(' / ', $attributeNames);
    }

    /**
     * Import variant attributes from PrestaShop combination
     *
     * Maps PrestaShop product_option_values to PPM VariantAttribute
     *
     * @param \App\Models\ProductVariant $variant PPM Variant
     * @param array $combination PrestaShop combination data
     * @param PrestaShopShop $shop Shop instance
     * @param mixed $client PrestaShop API client
     * @return void
     */
    protected function importVariantAttributes(
        \App\Models\ProductVariant $variant,
        array $combination,
        PrestaShopShop $shop,
        $client
    ): void {
        $optionValues = data_get($combination, 'associations.product_option_values', []);

        if (empty($optionValues)) {
            return;
        }

        // Clear existing attributes for this variant (replace strategy)
        $variant->attributes()->delete();

        foreach ($optionValues as $optionValue) {
            $optionValueId = (int) data_get($optionValue, 'id', 0);

            if ($optionValueId <= 0) {
                continue;
            }

            try {
                // Fetch full attribute value data from PrestaShop
                $valueData = $client->getProductOptionValue($optionValueId);

                if (isset($valueData['product_option_value'])) {
                    $valueData = $valueData['product_option_value'];
                }

                $optionId = (int) data_get($valueData, 'id_attribute_group', 0);
                $valueName = $this->extractMultilangValue(data_get($valueData, 'name'));
                $colorHex = data_get($valueData, 'color', null);

                if ($optionId <= 0 || empty($valueName)) {
                    continue;
                }

                // Find or create PPM AttributeType
                $attributeType = $this->findOrCreateAttributeType($optionId, $shop, $client);

                if (!$attributeType) {
                    continue;
                }

                // Find or create PPM AttributeValue
                $attributeValue = $this->findOrCreateAttributeValue(
                    $attributeType,
                    $valueName,
                    $colorHex,
                    $shop,           // FIX: Pass shop for mapping creation
                    $optionValueId   // FIX: Pass PS attribute value ID for mapping
                );

                if (!$attributeValue) {
                    continue;
                }

                // Create VariantAttribute link
                // Note: color_hex is stored in AttributeValue, not VariantAttribute
                \App\Models\VariantAttribute::create([
                    'variant_id' => $variant->id,
                    'attribute_type_id' => $attributeType->id,
                    'value_id' => $attributeValue->id,
                ]);

                Log::debug('[VARIANT IMPORT] Attribute imported', [
                    'variant_id' => $variant->id,
                    'attribute_type' => $attributeType->name,
                    'value' => $valueName,
                ]);

            } catch (\Exception $e) {
                Log::warning('[VARIANT IMPORT] Could not import attribute', [
                    'variant_id' => $variant->id,
                    'option_value_id' => $optionValueId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Find or create PPM AttributeType from PrestaShop attribute group
     *
     * @param int $psAttributeGroupId PrestaShop attribute group ID
     * @param PrestaShopShop $shop Shop instance
     * @param mixed $client PrestaShop API client
     * @return \App\Models\AttributeType|null
     */
    protected function findOrCreateAttributeType(
        int $psAttributeGroupId,
        PrestaShopShop $shop,
        $client
    ): ?\App\Models\AttributeType {
        try {
            // Fetch attribute group from PrestaShop
            $groupData = $client->getProductOption($psAttributeGroupId);

            if (isset($groupData['product_option'])) {
                $groupData = $groupData['product_option'];
            }

            $groupName = $this->extractMultilangValue(data_get($groupData, 'name'));
            $groupType = data_get($groupData, 'group_type', 'select'); // select, color, radio

            if (empty($groupName)) {
                return null;
            }

            // FIX 2025-12-15: First try to find by name (case-insensitive)
            $existingByName = \App\Models\AttributeType::whereRaw('LOWER(name) = ?', [strtolower($groupName)])->first();
            if ($existingByName) {
                Log::debug('[VARIANT IMPORT] Found existing AttributeType by name', [
                    'name' => $groupName,
                    'existing_id' => $existingByName->id,
                    'existing_code' => $existingByName->code,
                ]);
                return $existingByName;
            }

            // Generate code from name - FIX: strtolower FIRST, then regex
            $code = preg_replace('/[^a-z0-9]/', '_', strtolower($groupName));
            $code = trim(preg_replace('/_+/', '_', $code), '_');

            // Determine display type
            $displayType = match ($groupType) {
                'color' => \App\Models\AttributeType::DISPLAY_TYPE_COLOR,
                'radio' => \App\Models\AttributeType::DISPLAY_TYPE_RADIO,
                default => \App\Models\AttributeType::DISPLAY_TYPE_DROPDOWN,
            };

            // Find or create AttributeType
            $attributeType = \App\Models\AttributeType::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $groupName,
                    'display_type' => $displayType,
                    'is_active' => true,
                    'position' => 0,
                ]
            );

            return $attributeType;

        } catch (\Exception $e) {
            Log::warning('[VARIANT IMPORT] Could not get/create attribute type', [
                'prestashop_group_id' => $psAttributeGroupId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Find or create PPM AttributeValue with PrestaShop mapping
     *
     * FIX 2025-12-11: Create AttributeValuePsMapping for shop synchronization tracking
     *
     * @param \App\Models\AttributeType $attributeType PPM AttributeType
     * @param string $valueName Value name
     * @param string|null $colorHex Color hex code
     * @param PrestaShopShop|null $shop PrestaShop shop instance (for mapping)
     * @param int|null $psAttributeValueId PrestaShop attribute value ID (for mapping)
     * @return \App\Models\AttributeValue|null
     */
    protected function findOrCreateAttributeValue(
        \App\Models\AttributeType $attributeType,
        string $valueName,
        ?string $colorHex,
        ?PrestaShopShop $shop = null,
        ?int $psAttributeValueId = null
    ): ?\App\Models\AttributeValue {
        // Generate code from name (with Polish character conversion)
        $polishMap = [
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
            'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
            'Ą' => 'a', 'Ć' => 'c', 'Ę' => 'e', 'Ł' => 'l', 'Ń' => 'n',
            'Ó' => 'o', 'Ś' => 's', 'Ź' => 'z', 'Ż' => 'z',
        ];
        $transliterated = strtr($valueName, $polishMap);
        $code = strtolower(preg_replace('/[^a-z0-9]/', '_', $transliterated));
        $code = trim(preg_replace('/_+/', '_', $code), '_');

        // Find or create AttributeValue
        $attributeValue = \App\Models\AttributeValue::firstOrCreate(
            [
                'attribute_type_id' => $attributeType->id,
                'code' => $code,
            ],
            [
                'label' => $valueName,
                'color_hex' => $colorHex,
                'position' => 0,
                'is_active' => true,
            ]
        );

        // FIX 2025-12-11: Create PrestaShop mapping for this attribute value
        if ($shop && $psAttributeValueId && $attributeValue) {
            \App\Models\AttributeValuePsMapping::updateOrCreate(
                [
                    'attribute_value_id' => $attributeValue->id,
                    'prestashop_shop_id' => $shop->id,
                ],
                [
                    'prestashop_attribute_id' => $psAttributeValueId,
                    'prestashop_label' => $valueName,
                    'prestashop_color' => $colorHex,
                    'is_synced' => true,
                    'sync_status' => 'synced',
                    'last_synced_at' => now(),
                    'sync_notes' => 'Auto-created during product import',
                ]
            );

            Log::debug('[VARIANT IMPORT] Created/Updated AttributeValue PS mapping', [
                'attribute_value_id' => $attributeValue->id,
                'shop_id' => $shop->id,
                'prestashop_attribute_id' => $psAttributeValueId,
            ]);
        }

        return $attributeValue;
    }

    /**
     * Import variant price from PrestaShop combination
     *
     * @param \App\Models\ProductVariant $variant PPM Variant
     * @param array $combination PrestaShop combination data
     * @param Product $product Parent product
     * @return void
     */
    protected function importVariantPrice(
        \App\Models\ProductVariant $variant,
        array $combination,
        Product $product
    ): void {
        // PrestaShop stores price modifier, not absolute price
        $priceModifier = (float) data_get($combination, 'price', 0);

        // Get default price group
        $defaultPriceGroup = \App\Models\PriceGroup::where('code', 'retail')->first();

        if (!$defaultPriceGroup) {
            return;
        }

        // Get parent product base price
        $basePrice = $product->prices()
            ->where('price_group_id', $defaultPriceGroup->id)
            ->first();

        $basePriceNet = $basePrice ? $basePrice->price_net : 0;
        $variantPriceNet = $basePriceNet + $priceModifier;

        // Create/update VariantPrice
        // Note: variant_prices table has: price, special_price, special_price_from, special_price_to
        \App\Models\VariantPrice::updateOrCreate(
            [
                'variant_id' => $variant->id,
                'price_group_id' => $defaultPriceGroup->id,
            ],
            [
                'price' => $variantPriceNet,
            ]
        );
    }

    /**
     * Import variant stock from PrestaShop combination
     *
     * @param \App\Models\ProductVariant $variant PPM Variant
     * @param array $combination PrestaShop combination data
     * @return void
     */
    protected function importVariantStock(
        \App\Models\ProductVariant $variant,
        array $combination
    ): void {
        $quantity = (int) data_get($combination, 'quantity', 0);

        // Get default warehouse
        $defaultWarehouse = \App\Models\Warehouse::where('code', 'MPPTRADE')
            ->orWhere('is_default', true)
            ->first();

        if (!$defaultWarehouse) {
            return;
        }

        // Create/update VariantStock
        \App\Models\VariantStock::updateOrCreate(
            [
                'variant_id' => $variant->id,
                'warehouse_id' => $defaultWarehouse->id,
            ],
            [
                'quantity' => $quantity,
                'reserved' => 0,
            ]
        );
    }

    /**
     * Import variant images from PrestaShop combination
     *
     * Downloads and stores variant images from PrestaShop API.
     * Strategy:
     * 1. Try to match existing Media with PS image ID
     * 2. Download from PrestaShop API and store locally
     * 3. Fallback to first product image if no combination images
     *
     * PrestaShop API structure:
     * combination.associations.images = [{id: 123}, {id: 456}]
     *
     * @param \App\Models\ProductVariant $variant PPM Variant
     * @param array $combination PrestaShop combination data
     * @param Product $product PPM Product (for fallback images)
     * @param PrestaShopShop $shop Shop instance (for API access)
     * @return void
     */
    protected function importVariantImages(
        \App\Models\ProductVariant $variant,
        array $combination,
        Product $product,
        PrestaShopShop $shop
    ): void {
        // Get image IDs from combination associations
        $combinationImages = data_get($combination, 'associations.images', []);

        // Normalize format: can be [{id: 123}] or [{'id': '123'}] or single {id: 123}
        if (isset($combinationImages['id'])) {
            $combinationImages = [$combinationImages];
        }

        $imageIds = [];
        foreach ($combinationImages as $img) {
            $imageId = (int) data_get($img, 'id', 0);
            if ($imageId > 0) {
                $imageIds[] = $imageId;
            }
        }

        // If no combination images, try to use first product image as fallback
        if (empty($imageIds)) {
            $firstMedia = $product->media()->first();
            if ($firstMedia) {
                // Create VariantImage from existing product media
                \App\Models\VariantImage::updateOrCreate(
                    [
                        'variant_id' => $variant->id,
                        'image_path' => $firstMedia->file_path,
                    ],
                    [
                        'image_thumb_path' => $firstMedia->thumbnail_path,
                        'image_url' => $firstMedia->original_url,
                        'is_cover' => true,
                        'position' => 0,
                    ]
                );

                Log::debug('[VARIANT IMPORT] Used product fallback image', [
                    'variant_id' => $variant->id,
                    'media_id' => $firstMedia->id,
                ]);
            }
            return;
        }

        // Clear existing variant images (replace strategy)
        $variant->images()->delete();

        // Get PrestaShop product ID
        $productPsId = (int) data_get($combination, 'id_product', 0);

        // Get existing product media for matching
        $productMedia = $product->media()->orderBy('sort_order')->get();

        // Import each combination image
        $position = 0;
        $downloadedCount = 0;
        $linkedCount = 0;

        foreach ($imageIds as $psImageId) {
            try {
                $existingMedia = null;
                $isCover = ($position === 0);

                // Strategy 1: Try to find media with this PS image ID in prestashop_mapping JSON
                foreach ($productMedia as $media) {
                    $mapping = $media->prestashop_mapping ?? [];
                    foreach ($mapping as $shopKey => $shopData) {
                        $mappedImageId = $shopData['ps_image_id'] ?? $shopData['image_id'] ?? null;
                        if ($mappedImageId !== null && (int)$mappedImageId === $psImageId) {
                            $existingMedia = $media;
                            break 2;
                        }
                    }
                }

                // Strategy 2: Use product media by position (if PS image positions match PPM)
                if (!$existingMedia && isset($productMedia[$position])) {
                    $existingMedia = $productMedia[$position];
                }

                if ($existingMedia) {
                    // Link to existing Media record
                    \App\Models\VariantImage::create([
                        'variant_id' => $variant->id,
                        'image_path' => $existingMedia->file_path ?? '',
                        'image_thumb_path' => $existingMedia->thumbnail_path ?? '',
                        'image_url' => $existingMedia->url ?? '',
                        'is_cover' => $isCover,
                        'position' => $position,
                    ]);
                    $linkedCount++;
                } else {
                    // Strategy 3: Download image from PrestaShop API and store locally
                    $variantImage = $this->downloadVariantImageFromPrestaShop(
                        $variant,
                        $productPsId,
                        $psImageId,
                        $shop,
                        $position,
                        $isCover
                    );

                    if ($variantImage) {
                        $downloadedCount++;
                    } else {
                        // Strategy 4: Create record with URL only (fallback)
                        $shopUrl = rtrim($shop->shop_url ?? '', '/');
                        $imageUrl = !empty($shopUrl) && $productPsId > 0
                            ? "{$shopUrl}/api/images/products/{$productPsId}/{$psImageId}"
                            : '';

                        \App\Models\VariantImage::create([
                            'variant_id' => $variant->id,
                            'image_path' => '',
                            'image_url' => $imageUrl,
                            'is_cover' => $isCover,
                            'position' => $position,
                        ]);

                        Log::warning('[VARIANT IMPORT] Could not download image, saved URL only', [
                            'variant_id' => $variant->id,
                            'ps_image_id' => $psImageId,
                        ]);
                    }
                }

                $position++;

            } catch (\Exception $e) {
                Log::warning('[VARIANT IMPORT] Could not import variant image', [
                    'variant_id' => $variant->id,
                    'prestashop_image_id' => $psImageId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('[VARIANT IMPORT] Variant images imported', [
            'variant_id' => $variant->id,
            'total_images' => $position,
            'linked_from_media' => $linkedCount,
            'downloaded_from_api' => $downloadedCount,
            'prestashop_image_ids' => $imageIds,
        ]);
    }

    /**
     * Download single variant image from PrestaShop API and store locally
     *
     * @param \App\Models\ProductVariant $variant PPM Variant
     * @param int $psProductId PrestaShop product ID
     * @param int $psImageId PrestaShop image ID
     * @param PrestaShopShop $shop Shop instance
     * @param int $position Image position
     * @param bool $isCover Is cover image
     * @return \App\Models\VariantImage|null Created VariantImage or null on failure
     */
    protected function downloadVariantImageFromPrestaShop(
        \App\Models\ProductVariant $variant,
        int $psProductId,
        int $psImageId,
        PrestaShopShop $shop,
        int $position,
        bool $isCover
    ): ?\App\Models\VariantImage {
        // Use dedicated service for downloading
        $downloadService = app(\App\Services\Media\VariantImageDownloadService::class);

        return $downloadService->downloadAndStore(
            $variant,
            $psProductId,
            $psImageId,
            $shop,
            $position,
            $isCover
        );
    }

    /**
     * Extract value from multilang PrestaShop field
     *
     * @param mixed $value Multilang value or string
     * @return string Extracted value
     */
    protected function extractMultilangValue($value): string
    {
        if (empty($value)) {
            return '';
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            // Format: [{id: 1, value: "PL"}, {id: 2, value: "EN"}]
            if (isset($value[0]['value'])) {
                return (string) $value[0]['value'];
            }

            // Format: {language: {value: "Text"}}
            if (isset($value['language']['value'])) {
                return (string) $value['language']['value'];
            }

            // Format: {language: [{id: 1, value: "PL"}]}
            if (isset($value['language'][0]['value'])) {
                return (string) $value['language'][0]['value'];
            }
        }

        return '';
    }
}
