<?php

namespace App\Jobs\PrestaShop;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Models\SyncJob;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\PrestaShop\PrestaShopImportService;
use App\Services\JobProgressService;
use App\Models\CategoryPreview;

/**
 * BulkImportProducts Job - Import products from PrestaShop to PPM-CC-Laravel
 *
 * ETAP_07 FAZA 3: PrestaShop Product Import
 *
 * Supports three import modes:
 * - 'all': Import ALL products from shop
 * - 'category': Import products from specific category (with optional subcategories)
 * - 'individual': Import specific products by ID list
 *
 * Features:
 * - Background processing via Laravel Queue
 * - Automatic SKU conflict detection
 * - Progress tracking and error handling
 * - Notification on completion
 *
 * Usage:
 * ```
 * BulkImportProducts::dispatch($shop, 'all');
 * BulkImportProducts::dispatch($shop, 'category', ['category_id' => 5, 'include_subcategories' => true]);
 * BulkImportProducts::dispatch($shop, 'individual', ['product_ids' => [1, 2, 3]]);
 * ```
 *
 * @package App\Jobs\PrestaShop
 * @version 1.0
 * @since ETAP_07_FAZA_3
 */
class BulkImportProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The PrestaShop shop instance
     *
     * @var PrestaShopShop
     */
    protected PrestaShopShop $shop;

    /**
     * Import mode: all|category|individual
     *
     * @var string
     */
    protected string $mode;

    /**
     * Import options (category_id, include_subcategories, product_ids)
     *
     * @var array
     */
    protected array $options;

    /**
     * Pre-generated job ID (UUID) for progress tracking
     *
     * @var string|null
     */
    protected ?string $jobId;

    /**
     * SyncJob ID for tracking in /admin/shops/sync
     * FIX 2025-11-25: Store only ID to avoid SerializesModels issues
     *
     * @var int|null
     */
    protected ?int $syncJobId = null;

    /**
     * Number of tries for the job
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * Timeout for the job (15 minutes)
     *
     * @var int
     */
    public int $timeout = 900;

    /**
     * Create a new job instance
     *
     * FIX 2025-11-25: Create SyncJob for visibility in /admin/shops/sync
     *
     * @param PrestaShopShop $shop
     * @param string $mode all|category|individual
     * @param array $options
     * @param string|null $jobId Pre-generated UUID for progress tracking (optional)
     */
    public function __construct(PrestaShopShop $shop, string $mode = 'all', array $options = [], ?string $jobId = null)
    {
        $this->shop = $shop;
        $this->mode = $mode;
        $this->options = $options;
        $this->jobId = $jobId;

        // FIX 2025-11-25 #2: Use firstOrCreate to avoid Duplicate entry error
        // When job is re-dispatched with same jobId (e.g., from BulkCreateCategories)
        $syncJobUuid = $jobId ?? \Str::uuid()->toString();
        $syncJob = SyncJob::firstOrCreate(
            ['job_id' => $syncJobUuid],
            [
                'job_type' => 'import_products',
                'job_name' => "Import Products from {$shop->name} ({$mode})",
                'source_type' => SyncJob::TYPE_PRESTASHOP,
                'source_id' => $shop->id,
                'target_type' => SyncJob::TYPE_PPM,
                'target_id' => null, // Multiple products
                'status' => SyncJob::STATUS_PENDING,
                'trigger_type' => SyncJob::TRIGGER_MANUAL,
                'user_id' => auth()->id() ?? 1, // Fallback to admin
                'queue_name' => 'default',
                'total_items' => 0, // Will be updated after fetching products
                'processed_items' => 0,
                'successful_items' => 0,
                'failed_items' => 0,
                'scheduled_at' => now(),
                'job_config' => [
                    'mode' => $mode,
                    'options' => $options,
                ],
            ]
        );

        // Store only ID (not model instance) to avoid serialization issues
        $this->syncJobId = $syncJob->id;

        Log::info('BulkImportProducts: SyncJob created', [
            'sync_job_id' => $syncJob->id,
            'job_id' => $syncJob->job_id,
            'shop_id' => $shop->id,
            'mode' => $mode,
        ]);
    }

    /**
     * Get SyncJob instance (gracefully handles deleted jobs)
     *
     * @return SyncJob|null
     */
    protected function getSyncJob(): ?SyncJob
    {
        if (!$this->syncJobId) {
            return null;
        }

        try {
            return SyncJob::find($this->syncJobId);
        } catch (\Exception $e) {
            Log::warning('Failed to load SyncJob (may have been deleted by cleanup)', [
                'sync_job_id' => $this->syncJobId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Execute the job
     */
    public function handle(JobProgressService $progressService): void
    {
        $startTime = microtime(true);
        $progressId = null;
        $syncJob = $this->getSyncJob();

        Log::info('BulkImportProducts job started', [
            'shop_id' => $this->shop->id,
            'shop_name' => $this->shop->name,
            'mode' => $this->mode,
            'options' => $this->options,
            'pre_generated_job_id' => $this->jobId,
            'sync_job_id' => $syncJob?->id,
        ]);

        try {
            // FIX 2025-11-25: Update SyncJob status to running
            $syncJob?->start();
            // 🔧 FIX: Fetch products FIRST to know total count for JobProgress
            $client = app(PrestaShopClientFactory::class)->create($this->shop);
            $productsToImport = $this->getProductsToImport($client);
            $total = count($productsToImport);

            Log::info('BulkImportProducts: Products fetched', [
                'shop_id' => $this->shop->id,
                'shop_name' => $this->shop->name,
                'total_products' => $total,
                'mode' => $this->mode,
            ]);

            // FIX 2025-11-25: Update SyncJob with total items
            $syncJob?->update(['total_items' => $total]);

            // 🆕 ETAP_07 FAZA 3D: Check if category analysis needed AFTER knowing total count
            if ($this->shouldAnalyzeCategories()) {
                Log::info('Category analysis required - dispatching AnalyzeMissingCategories', [
                    'shop_id' => $this->shop->id,
                    'job_id' => $this->jobId,
                    'mode' => $this->mode,
                    'total_products' => $total,
                ]);

                $productIds = array_map(fn($p) => (int) ($p['id'] ?? $p['product']['id'] ?? 0), $productsToImport);
                $productIds = array_filter($productIds); // Remove zeros

                // 🔧 FIX: Update JobProgress with actual total BEFORE halting
                // DON'T set status='completed' - job is being halted for category analysis!
                // AnalyzeMissingCategories will handle status updates
                if ($this->jobId) {
                    $pendingProgress = \App\Models\JobProgress::where('job_id', $this->jobId)->first();
                    if ($pendingProgress) {
                        // Update with real total count but keep status as 'pending'
                        // This is NOT completion - just updating total after fetching products
                        $pendingProgress->update([
                            'total_count' => $total,
                            // DON'T set status='completed' - category analysis is starting!
                        ]);

                        Log::info('JobProgress updated before category analysis', [
                            'progress_id' => $pendingProgress->id,
                            'job_id' => $this->jobId,
                            'total_count' => $total,
                            'status' => 'pending', // Still pending - waiting for category creation
                        ]);
                    }
                }

                // Dispatch AnalyzeMissingCategories and HALT product import
                AnalyzeMissingCategories::dispatch(
                    $productIds,
                    $this->shop,
                    $this->jobId,
                    [
                        'mode' => $this->mode,
                        'options' => $this->options,
                    ]
                );

                Log::info('AnalyzeMissingCategories dispatched - product import halted', [
                    'shop_id' => $this->shop->id,
                    'job_id' => $this->jobId,
                    'product_count' => count($productIds),
                ]);

                // HALT execution - waiting for category preview approval
                return;
            }

            $importService = app(PrestaShopImportService::class);

            // 📊 UPDATE PENDING PROGRESS TO RUNNING (or create new if legacy dispatch)
            if ($this->jobId) {
                // Pre-generated job_id exists → Update pending progress to running
                $progressId = $progressService->startPendingJob($this->jobId, $total);

                if (!$progressId) {
                    // Fallback: pending progress not found, create new
                    Log::warning('Pending progress not found, creating new', ['job_id' => $this->jobId]);
                    $progressId = $progressService->createJobProgress(
                        $this->jobId,
                        $this->shop,
                        'import',
                        $total
                    );
                }
            } else {
                // Legacy: No pre-generated job_id → Create new progress (backward compatibility)
                $progressId = $progressService->createJobProgress(
                    $this->job->getJobId(),
                    $this->shop,
                    'import',
                    $total
                );
            }

            Log::info('BulkImportProducts: Products to import', [
                'shop_id' => $this->shop->id,
                'shop_name' => $this->shop->name,
                'total_products' => $total,
                'mode' => $this->mode,
                'progress_id' => $progressId,
            ]);

            $imported = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];

            foreach ($productsToImport as $index => $psProduct) {
                try {
                    $prestashopProductId = (int) ($psProduct['id'] ?? 0);
                    $sku = $psProduct['reference'] ?? null;

                    // 📊 UPDATE PROGRESS every 5 products for efficiency
                    if ($index % 5 === 0 && $progressId) {
                        $progressService->updateProgress($progressId, $index + 1, $errors);
                        $errors = []; // Reset errors array after batch update
                    }

                    $result = $this->importProduct($prestashopProductId, $sku, $importService);

                    if ($result === 'imported') {
                        $imported++;
                    } elseif ($result === 'updated') {
                        $updated++;
                    } elseif (str_starts_with($result, 'skipped_')) {
                        $skipped++;

                        // Add skip reason to errors for user visibility
                        $skipReason = match($result) {
                            'skipped_no_id' => 'Brak ID produktu PrestaShop',
                            'skipped_no_sku' => 'Brak SKU w danych PrestaShop',
                            default => 'Pominięto z nieznanego powodu'
                        };

                        $errors[] = [
                            'sku' => $sku ?? 'N/A',
                            'message' => $skipReason,
                        ];
                    } else {
                        $skipped++;
                    }

                } catch (\Exception $e) {
                    $errors[] = [
                        'sku' => $psProduct['reference'] ?? 'N/A',
                        'message' => $e->getMessage(),
                    ];

                    Log::error('Failed to import product', [
                        'shop_id' => $this->shop->id,
                        'product_id' => $psProduct['id'] ?? null,
                        'sku' => $psProduct['reference'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 📊 FINAL PROGRESS UPDATE with remaining errors
            if ($progressId) {
                $progressService->updateProgress($progressId, $total, $errors);
            }

            $executionTime = (int) ((microtime(true) - $startTime) * 1000);

            // ⚠️ CRITICAL: Add warning message if ZERO products imported/updated
            $warningMessage = null;
            $successfulCount = $imported + $updated;

            if ($successfulCount === 0 && $total > 0) {
                $warningMessage = sprintf(
                    'Import zakończony: 0 produktów utworzonych/zaktualizowanych z %d wybranych. ',
                    $total
                );

                if ($skipped > 0) {
                    $warningMessage .= sprintf('%d produktów pominięto (brak SKU lub nieprawidłowe dane PrestaShop). ', $skipped);
                }

                if (count($errors) > 0) {
                    $warningMessage .= sprintf('%d produktów z błędami. ', count($errors));
                }

                Log::warning('Import completed with ZERO products imported/updated', [
                    'shop_id' => $this->shop->id,
                    'total' => $total,
                    'imported' => $imported,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'errors' => count($errors),
                    'message' => $warningMessage,
                ]);
            }

            // 📊 MARK AS COMPLETED with summary + warning message
            if ($progressId) {
                $summary = [
                    'imported' => $imported,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'execution_time_ms' => $executionTime,
                ];

                // Add warning message to summary if zero imported/updated
                if ($warningMessage) {
                    $summary['warning_message'] = $warningMessage;
                }

                $progressService->markCompleted($progressId, $summary);
            }

            // FIX 2025-11-25: Update SyncJob progress and complete
            $syncJob?->updateProgress(
                processedItems: $total,
                successfulItems: $imported + $updated,
                failedItems: $skipped + count($errors)
            );
            $syncJob?->complete([
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors_count' => count($errors),
                'execution_time_ms' => $executionTime,
            ]);

            Log::info('BulkImportProducts job completed', [
                'shop_id' => $this->shop->id,
                'shop_name' => $this->shop->name,
                'mode' => $this->mode,
                'total' => $total,
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => count($errors),
                'success_rate' => $total > 0 ? round(($successfulCount / $total) * 100, 1) . '%' : '0%',
                'execution_time_ms' => $executionTime,
                'execution_time_readable' => round($executionTime / 1000, 2) . 's',
                'progress_id' => $progressId,
            ]);

        } catch (\Exception $e) {
            // 📊 MARK AS FAILED
            if ($progressId) {
                $progressService->markFailed($progressId, $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            // FIX 2025-11-25: Mark SyncJob as failed
            $syncJob?->fail(
                errorMessage: $e->getMessage(),
                errorDetails: $e->getFile() . ':' . $e->getLine(),
                stackTrace: $e->getTraceAsString()
            );

            Log::error('BulkImportProducts job failed', [
                'shop_id' => $this->shop->id,
                'shop_name' => $this->shop->name,
                'mode' => $this->mode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'progress_id' => $progressId,
                'sync_job_id' => $syncJob?->id,
            ]);

            throw $e;
        }
    }

    /**
     * Get products to import based on mode
     *
     * @param mixed $client PrestaShop client instance
     * @return array
     */
    protected function getProductsToImport($client): array
    {
        switch ($this->mode) {
            case 'all':
                return $this->getAllProducts($client);

            case 'category':
                return $this->getProductsByCategory($client);

            case 'individual':
                return $this->getProductsByIds($client);

            default:
                throw new \InvalidArgumentException("Invalid import mode: {$this->mode}");
        }
    }

    /**
     * Get ALL products from shop
     *
     * @param mixed $client
     * @return array
     */
    protected function getAllProducts($client): array
    {
        $response = $client->getProducts(['display' => 'full']);

        if (isset($response['products']) && is_array($response['products'])) {
            return $response['products'];
        } elseif (isset($response[0])) {
            return $response;
        }

        return [];
    }

    /**
     * Get products by category
     *
     * FIXED: PrestaShop API does NOT support filtering products by associations.
     * Solution: Fetch category with associations.products to get product IDs,
     * then fetch each product individually.
     *
     * @param mixed $client
     * @return array
     */
    protected function getProductsByCategory($client): array
    {
        $categoryId = $this->options['category_id'] ?? null;
        $includeSubcategories = $this->options['include_subcategories'] ?? false;

        if (!$categoryId) {
            throw new \InvalidArgumentException('category_id is required for category mode');
        }

        Log::info('BulkImportProducts: Fetching products by category', [
            'category_id' => $categoryId,
            'include_subcategories' => $includeSubcategories,
        ]);

        // STEP 1: Get category with display=full to fetch associations.products
        try {
            $categoryResponse = $client->getCategory($categoryId);

            Log::debug('BulkImportProducts: Category response received', [
                'category_id' => $categoryId,
                'response_keys' => array_keys($categoryResponse),
            ]);

            // Extract product IDs from category associations
            $productIds = $this->extractProductIdsFromCategory($categoryResponse);

            // STEP 2: If include_subcategories, recursively get all child categories
            if ($includeSubcategories) {
                $childCategoryIds = $this->getChildCategoryIds($categoryId, $client);

                foreach ($childCategoryIds as $childCategoryId) {
                    $childCategory = $client->getCategory($childCategoryId);
                    $childProductIds = $this->extractProductIdsFromCategory($childCategory);
                    $productIds = array_merge($productIds, $childProductIds);
                }

                // Remove duplicates
                $productIds = array_unique($productIds);
            }

            Log::info('BulkImportProducts: Found products in category', [
                'category_id' => $categoryId,
                'product_count' => count($productIds),
                'product_ids' => $productIds,
            ]);

            // STEP 3: If no products found, return empty array
            if (empty($productIds)) {
                Log::warning('BulkImportProducts: No products found in category', [
                    'category_id' => $categoryId,
                ]);
                return [];
            }

            // STEP 4: Fetch all products using OR filter on ID
            // PrestaShop API supports: filter[id]=[1|2|3|4]
            $idsFilter = '[' . implode('|', $productIds) . ']';

            $params = [
                'display' => 'full',
                'filter[id]' => $idsFilter,
            ];

            Log::info('BulkImportProducts: Fetching products by IDs', [
                'product_ids_count' => count($productIds),
                'filter' => $idsFilter,
            ]);

            $response = $client->getProducts($params);

            // Parse response
            $products = [];
            if (isset($response['products']) && is_array($response['products'])) {
                $products = $response['products'];
            } elseif (isset($response[0])) {
                $products = $response;
            }

            Log::info('BulkImportProducts: Products fetched successfully', [
                'category_id' => $categoryId,
                'product_count' => count($products),
            ]);

            return $products;

        } catch (\Exception $e) {
            Log::error('BulkImportProducts: Failed to fetch products by category', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Extract product IDs from category response associations
     *
     * @param array $categoryResponse Category data from API
     * @return array Product IDs
     */
    protected function extractProductIdsFromCategory(array $categoryResponse): array
    {
        $productIds = [];

        // Try different response formats
        // Format 1: {category: {associations: {products: [{id: 1}, {id: 2}]}}}
        if (isset($categoryResponse['category']['associations']['products'])) {
            $products = $categoryResponse['category']['associations']['products'];

            if (is_array($products)) {
                foreach ($products as $product) {
                    if (isset($product['id'])) {
                        $productIds[] = (int) $product['id'];
                    }
                }
            }
        }

        // Format 2: {associations: {products: [{id: 1}, {id: 2}]}}
        if (empty($productIds) && isset($categoryResponse['associations']['products'])) {
            $products = $categoryResponse['associations']['products'];

            if (is_array($products)) {
                foreach ($products as $product) {
                    if (isset($product['id'])) {
                        $productIds[] = (int) $product['id'];
                    }
                }
            }
        }

        return $productIds;
    }

    /**
     * Get all child category IDs recursively
     *
     * @param int $parentCategoryId Parent category ID
     * @param mixed $client PrestaShop client
     * @return array Child category IDs
     */
    protected function getChildCategoryIds(int $parentCategoryId, $client): array
    {
        $childIds = [];

        try {
            // Get all categories
            $categoriesResponse = $client->getCategories(['display' => '[id,id_parent]']);

            $categories = [];
            if (isset($categoriesResponse['categories']) && is_array($categoriesResponse['categories'])) {
                $categories = $categoriesResponse['categories'];
            } elseif (isset($categoriesResponse[0])) {
                $categories = $categoriesResponse;
            }

            // Find direct children
            foreach ($categories as $category) {
                $categoryData = $category['category'] ?? $category;

                if (isset($categoryData['id_parent']) && (int) $categoryData['id_parent'] === $parentCategoryId) {
                    $childId = (int) $categoryData['id'];
                    $childIds[] = $childId;

                    // Recursively get children of this child
                    $grandChildIds = $this->getChildCategoryIds($childId, $client);
                    $childIds = array_merge($childIds, $grandChildIds);
                }
            }

        } catch (\Exception $e) {
            Log::warning('BulkImportProducts: Failed to fetch child categories', [
                'parent_category_id' => $parentCategoryId,
                'error' => $e->getMessage(),
            ]);
        }

        return $childIds;
    }

    /**
     * Get specific products by IDs
     *
     * @param mixed $client
     * @return array
     */
    protected function getProductsByIds($client): array
    {
        $productIds = $this->options['product_ids'] ?? [];

        if (empty($productIds)) {
            throw new \InvalidArgumentException('product_ids array is required for individual mode');
        }

        $products = [];

        foreach ($productIds as $productId) {
            try {
                $response = $client->getProduct($productId);

                // CRITICAL FIX: PrestaShop API returns {product: {...}} for single product
                // Extract actual product data from response
                $product = null;
                if (isset($response['product']) && is_array($response['product'])) {
                    $product = $response['product'];
                } elseif (isset($response['id'])) {
                    // Fallback: response is already product data (some API versions)
                    $product = $response;
                }

                if ($product) {
                    $products[] = $product;
                } else {
                    Log::warning('Invalid product response structure', [
                        'shop_id' => $this->shop->id,
                        'product_id' => $productId,
                        'response_keys' => array_keys($response),
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch individual product', [
                    'shop_id' => $this->shop->id,
                    'product_id' => $productId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $products;
    }

    /**
     * Import single product from PrestaShop using PrestaShopImportService
     *
     * REFACTORED: Now uses PrestaShopImportService for complete import with:
     * - ProductSyncStatus creation (assigns shop to product)
     * - product_shop_data creation (shop-specific data)
     * - Price group mapping
     * - Stock synchronization
     * - SyncLog audit trail
     * - Category sync (CRITICAL FIX: Now works for re-imports!)
     *
     * @param int $prestashopProductId PrestaShop product ID
     * @param string|null $sku Product SKU (for logging)
     * @param PrestaShopImportService $importService Import service instance
     * @return string 'imported'|'updated'|'skipped'
     */
    protected function importProduct(
        int $prestashopProductId,
        ?string $sku,
        PrestaShopImportService $importService
    ): string
    {
        if (!$prestashopProductId) {
            Log::warning('Product without PrestaShop ID - skipped', [
                'shop_id' => $this->shop->id,
                'sku' => $sku,
            ]);
            return 'skipped_no_id';
        }

        if (!$sku) {
            Log::warning('Product without SKU - skipped', [
                'shop_id' => $this->shop->id,
                'prestashop_product_id' => $prestashopProductId,
            ]);
            return 'skipped_no_sku';
        }

        // Check if product already exists (for logging purposes)
        $existingProduct = Product::where('sku', $sku)->first();
        $isUpdate = (bool) $existingProduct;

        try {
            // 🚀 USE PrestaShopImportService for complete import/update
            // This will CREATE OR UPDATE:
            // 1. Product record
            // 2. ProductSyncStatus (assigns shop to product!)
            // 3. product_shop_data (shop-specific data for ProductForm)
            // 4. ProductPrice records (price groups)
            // 5. Stock records (if Stock model exists)
            // 6. Product categories (CRITICAL FIX: syncProductCategories works for UPDATE!)
            // 7. SyncLog audit entry
            $product = $importService->importProductFromPrestaShop(
                $prestashopProductId,
                $this->shop
            );

            Log::info($isUpdate ? 'Product updated successfully' : 'Product imported successfully', [
                'shop_id' => $this->shop->id,
                'shop_name' => $this->shop->name,
                'sku' => $sku,
                'prestashop_id' => $prestashopProductId,
                'ppm_id' => $product->id,
                'product_name' => $product->name,
                'operation' => $isUpdate ? 'update' : 'create',
            ]);

            return $isUpdate ? 'updated' : 'imported';

        } catch (\Exception $e) {
            Log::error('Failed to import/update product via PrestaShopImportService', [
                'shop_id' => $this->shop->id,
                'shop_name' => $this->shop->name,
                'sku' => $sku,
                'prestashop_id' => $prestashopProductId,
                'operation' => $isUpdate ? 'update' : 'create',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to be caught by handle() method
            throw $e;
        }
    }

    /**
     * Check if category analysis is needed
     *
     * ETAP_07 FAZA 3D: Category Import Preview System integration
     *
     * Returns TRUE if:
     * - Category preview feature is enabled
     * - No preview exists yet for this job
     * - OR preview exists but not approved yet
     *
     * Returns FALSE if:
     * - Feature disabled in config
     * - Preview already approved (categories created)
     * - Preview pending (already created, waiting for user)
     *
     * @return bool
     */
    protected function shouldAnalyzeCategories(): bool
    {
        // 🔧 FIX: Check if explicitly skipped (dispatched from AnalyzeMissingCategories)
        if (!empty($this->options['skip_category_analysis'])) {
            Log::debug('Category analysis explicitly skipped (dispatched from AnalyzeMissingCategories)', [
                'job_id' => $this->jobId,
                'shop_id' => $this->shop->id,
            ]);
            return false;
        }

        // Check if feature enabled in config
        if (!config('prestashop.category_preview_enabled', true)) {
            Log::debug('Category preview disabled in config');
            return false;
        }

        // Check if preview already exists for this job
        if ($this->jobId) {
            $existingPreview = CategoryPreview::forJob($this->jobId)->first();

            if ($existingPreview) {
                if ($existingPreview->status === CategoryPreview::STATUS_APPROVED) {
                    Log::debug('Category preview already approved - proceeding to import', [
                        'preview_id' => $existingPreview->id,
                        'job_id' => $this->jobId,
                    ]);
                    return false; // Categories already created
                }

                if ($existingPreview->status === CategoryPreview::STATUS_PENDING && !$existingPreview->isExpired()) {
                    Log::debug('Category preview pending - waiting for user approval', [
                        'preview_id' => $existingPreview->id,
                        'job_id' => $this->jobId,
                    ]);
                    return false; // Preview already created, waiting for user
                }
            }
        }

        // Category analysis needed
        Log::debug('Category analysis needed', [
            'job_id' => $this->jobId,
            'shop_id' => $this->shop->id,
        ]);

        return true;
    }

    /**
     * Handle job failure
     *
     * @param \Throwable $exception
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('BulkImportProducts job failed permanently', [
            'shop_id' => $this->shop->id,
            'mode' => $this->mode,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // TODO: Send failure notification to user
    }
}
