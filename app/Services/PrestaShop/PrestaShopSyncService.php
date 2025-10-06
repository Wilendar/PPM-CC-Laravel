<?php

namespace App\Services\PrestaShop;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Category;
use App\Models\PrestaShopShop;
use App\Models\ProductSyncStatus;
use App\Models\SyncLog;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\PrestaShop\Sync\ProductSyncStrategy;
use App\Services\PrestaShop\Sync\CategorySyncStrategy;
use App\Jobs\PrestaShop\SyncProductToPrestaShop;
use App\Jobs\PrestaShop\BulkSyncProducts;
use App\Jobs\PrestaShop\SyncCategoryToPrestaShop;
use App\Exceptions\PrestaShopAPIException;

/**
 * PrestaShop Sync Service - Orchestration Layer
 *
 * Główny serwis orkiestracji synchronizacji PPM ↔ PrestaShop
 *
 * Responsibilities:
 * - Koordynacja wszystkich komponentów sync (API Clients, Strategies, Transformers, Jobs)
 * - Proste API dla Livewire components
 * - Zarządzanie sync scheduling i bulk operations
 * - Monitoring progress synchronizacji
 * - Connection testing i validation
 *
 * ETAP_07 FAZA 1F - Service Orchestration
 *
 * @package App\Services\PrestaShop
 */
class PrestaShopSyncService
{
    /**
     * Logging channel dla PrestaShop operations
     */
    private const LOG_CHANNEL = 'stack'; // Default Laravel logging

    /**
     * Constructor with dependency injection (Laravel 12.x pattern)
     */
    public function __construct(
        private PrestaShopClientFactory $clientFactory,
        private ProductSyncStrategy $productSyncStrategy,
        private CategorySyncStrategy $categorySyncStrategy
    ) {}

    /**
     * Test connection to PrestaShop API
     *
     * Verifies:
     * - API credentials validity
     * - API endpoint accessibility
     * - PrestaShop version detection
     *
     * @param PrestaShopShop $shop Shop configuration to test
     * @return array{success: bool, version: string|null, message: string, details: array|null}
     */
    public function testConnection(PrestaShopShop $shop): array
    {
        $startTime = microtime(true);

        Log::info('Testing PrestaShop connection', [
            'shop_id' => $shop->id,
            'shop_name' => $shop->name,
            'shop_url' => $shop->shop_url,
            'version' => $shop->version,
        ]);

        try {
            // Create API client
            $client = $this->clientFactory->create($shop);

            // Test basic API call (get shop info)
            $response = $client->testConnection();

            $executionTimeMs = round((microtime(true) - $startTime) * 1000, 2);

            if ($response['success']) {
                Log::info('PrestaShop connection test successful', [
                    'shop_id' => $shop->id,
                    'version' => $response['version'] ?? 'unknown',
                    'execution_time_ms' => $executionTimeMs,
                ]);

                return [
                    'success' => true,
                    'version' => $response['version'] ?? $shop->version,
                    'message' => 'Connection successful',
                    'details' => [
                        'execution_time_ms' => $executionTimeMs,
                        'api_version' => $response['version'] ?? null,
                        'shop_name' => $response['shop_name'] ?? null,
                    ],
                ];
            }

            throw new PrestaShopAPIException($response['message'] ?? 'Connection test failed');

        } catch (\Exception $e) {
            $executionTimeMs = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('PrestaShop connection test failed', [
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
                'execution_time_ms' => $executionTimeMs,
            ]);

            return [
                'success' => false,
                'version' => null,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'details' => [
                    'execution_time_ms' => $executionTimeMs,
                    'error_type' => get_class($e),
                ],
            ];
        }
    }

    /**
     * Sync single product to specific shop (synchronous)
     *
     * @param Product $product Product to sync
     * @param PrestaShopShop $shop Target PrestaShop shop
     * @return bool True on success, false on failure
     */
    public function syncProduct(Product $product, PrestaShopShop $shop): bool
    {
        Log::info('Starting product sync', [
            'product_id' => $product->id,
            'product_sku' => $product->sku,
            'shop_id' => $shop->id,
            'shop_name' => $shop->name,
        ]);

        try {
            // Verify shop is active
            if (!$shop->is_active) {
                Log::warning('Product sync aborted - shop not active', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                ]);
                return false;
            }

            // Create API client
            $client = $this->clientFactory->create($shop);

            // Execute sync through strategy
            $result = $this->productSyncStrategy->syncToPrestaShop($product, $client, $shop);

            if ($result['success']) {
                Log::info('Product sync completed successfully', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                    'external_id' => $result['external_id'] ?? null,
                    'operation' => $result['operation'] ?? 'unknown',
                ]);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Product sync failed', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Sync product to all active shops (synchronous)
     *
     * @param Product $product Product to sync
     * @return array{shop_id: bool} Associative array of shop_id => success/fail
     */
    public function syncProductToAllShops(Product $product): array
    {
        Log::info('Starting product sync to all shops', [
            'product_id' => $product->id,
            'product_sku' => $product->sku,
        ]);

        $results = [];
        $activeShops = PrestaShopShop::where('is_active', true)->get();

        foreach ($activeShops as $shop) {
            $results[$shop->id] = $this->syncProduct($product, $shop);
        }

        $successCount = count(array_filter($results));
        $totalCount = count($results);

        Log::info('Product sync to all shops completed', [
            'product_id' => $product->id,
            'success_count' => $successCount,
            'total_count' => $totalCount,
            'results' => $results,
        ]);

        return $results;
    }

    /**
     * Queue product sync job (asynchronous background processing)
     *
     * @param Product $product Product to sync
     * @param PrestaShopShop $shop Target shop
     * @param int $priority Job priority (1=highest, 10=lowest)
     * @return void
     */
    public function queueProductSync(Product $product, PrestaShopShop $shop, int $priority = 5): void
    {
        Log::info('Queueing product sync job', [
            'product_id' => $product->id,
            'product_sku' => $product->sku,
            'shop_id' => $shop->id,
            'priority' => $priority,
        ]);

        // Update or create sync status with priority
        ProductSyncStatus::updateOrCreate(
            [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
            ],
            [
                'priority' => $priority,
                'sync_status' => ProductSyncStatus::STATUS_PENDING,
            ]
        );

        // Dispatch job
        SyncProductToPrestaShop::dispatch($product, $shop);
    }

    /**
     * Queue bulk product sync (asynchronous)
     *
     * Dispatches BulkSyncProducts job which will create individual
     * SyncProductToPrestaShop jobs for each product
     *
     * @param Collection $products Collection of Product models
     * @param PrestaShopShop $shop Target shop
     * @return void
     */
    public function queueBulkProductSync(Collection $products, PrestaShopShop $shop): void
    {
        Log::info('Queueing bulk product sync', [
            'product_count' => $products->count(),
            'shop_id' => $shop->id,
            'shop_name' => $shop->name,
        ]);

        // Mark all products as pending
        foreach ($products as $product) {
            ProductSyncStatus::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                ],
                [
                    'sync_status' => ProductSyncStatus::STATUS_PENDING,
                ]
            );
        }

        // Dispatch bulk job
        BulkSyncProducts::dispatch($products, $shop);
    }

    /**
     * Sync single category to specific shop (synchronous)
     *
     * @param Category $category Category to sync
     * @param PrestaShopShop $shop Target shop
     * @return bool True on success
     */
    public function syncCategory(Category $category, PrestaShopShop $shop): bool
    {
        Log::info('Starting category sync', [
            'category_id' => $category->id,
            'category_name' => $category->name,
            'shop_id' => $shop->id,
        ]);

        try {
            if (!$shop->is_active) {
                Log::warning('Category sync aborted - shop not active', [
                    'category_id' => $category->id,
                    'shop_id' => $shop->id,
                ]);
                return false;
            }

            $client = $this->clientFactory->create($shop);
            $result = $this->categorySyncStrategy->syncToPrestaShop($category, $client, $shop);

            if ($result['success']) {
                Log::info('Category sync completed successfully', [
                    'category_id' => $category->id,
                    'shop_id' => $shop->id,
                    'external_id' => $result['external_id'] ?? null,
                ]);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Category sync failed', [
                'category_id' => $category->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Sync complete category hierarchy to shop (synchronous)
     *
     * Syncs all active categories in correct hierarchy order (parent-first)
     *
     * @param PrestaShopShop $shop Target shop
     * @return array{synced: int, failed: int, errors: array}
     */
    public function syncCategoryHierarchy(PrestaShopShop $shop): array
    {
        Log::info('Starting category hierarchy sync', [
            'shop_id' => $shop->id,
            'shop_name' => $shop->name,
        ]);

        $startTime = microtime(true);

        try {
            if (!$shop->is_active) {
                return [
                    'synced' => 0,
                    'failed' => 0,
                    'errors' => ['Shop is not active'],
                ];
            }

            $client = $this->clientFactory->create($shop);
            $result = $this->categorySyncStrategy->syncCategoryHierarchy($shop, $client);

            $executionTimeMs = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('Category hierarchy sync completed', [
                'shop_id' => $shop->id,
                'synced' => $result['synced'] ?? 0,
                'errors' => $result['errors'] ?? 0,
                'execution_time_ms' => $executionTimeMs,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Category hierarchy sync failed', [
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'synced' => 0,
                'failed' => 1,
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Get sync status for product-shop pair
     *
     * @param Product $product Product to check
     * @param PrestaShopShop $shop Shop to check
     * @return ProductSyncStatus|null Sync status record or null if not found
     */
    public function getSyncStatus(Product $product, PrestaShopShop $shop): ?ProductSyncStatus
    {
        return ProductSyncStatus::where('product_id', $product->id)
            ->where('shop_id', $shop->id)
            ->first();
    }

    /**
     * Get sync statistics for shop
     *
     * @param PrestaShopShop $shop Shop to analyze
     * @return array{total: int, synced: int, pending: int, errors: int, syncing: int, success_rate: float}
     */
    public function getSyncStatistics(PrestaShopShop $shop): array
    {
        $total = ProductSyncStatus::where('shop_id', $shop->id)->count();
        $synced = ProductSyncStatus::where('shop_id', $shop->id)
            ->where('sync_status', ProductSyncStatus::STATUS_SYNCED)
            ->count();
        $pending = ProductSyncStatus::where('shop_id', $shop->id)
            ->where('sync_status', ProductSyncStatus::STATUS_PENDING)
            ->count();
        $errors = ProductSyncStatus::where('shop_id', $shop->id)
            ->where('sync_status', ProductSyncStatus::STATUS_ERROR)
            ->count();
        $syncing = ProductSyncStatus::where('shop_id', $shop->id)
            ->where('sync_status', ProductSyncStatus::STATUS_SYNCING)
            ->count();

        $successRate = $total > 0 ? round(($synced / $total) * 100, 2) : 0.0;

        return [
            'total' => $total,
            'synced' => $synced,
            'pending' => $pending,
            'errors' => $errors,
            'syncing' => $syncing,
            'success_rate' => $successRate,
        ];
    }

    /**
     * Get recent sync logs for shop
     *
     * @param PrestaShopShop $shop Shop to query
     * @param int $limit Number of logs to retrieve
     * @return Collection Collection of SyncLog models
     */
    public function getRecentSyncLogs(PrestaShopShop $shop, int $limit = 20): Collection
    {
        return SyncLog::where('shop_id', $shop->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Retry failed product syncs for shop
     *
     * Queues all products with error status that haven't exceeded max retries
     *
     * @param PrestaShopShop $shop Shop to retry syncs for
     * @return int Number of syncs queued for retry
     */
    public function retryFailedSyncs(PrestaShopShop $shop): int
    {
        $failedSyncs = ProductSyncStatus::where('shop_id', $shop->id)
            ->where('sync_status', ProductSyncStatus::STATUS_ERROR)
            ->whereColumn('retry_count', '<', 'max_retries')
            ->with('product')
            ->get();

        $count = 0;

        foreach ($failedSyncs as $syncStatus) {
            if ($syncStatus->product) {
                $this->queueProductSync($syncStatus->product, $shop, ProductSyncStatus::PRIORITY_HIGHEST);
                $count++;
            }
        }

        Log::info('Queued failed syncs for retry', [
            'shop_id' => $shop->id,
            'retry_count' => $count,
        ]);

        return $count;
    }

    /**
     * Reset sync status for product-shop pair
     *
     * Useful for manual re-sync after fixing data issues
     *
     * @param Product $product Product to reset
     * @param PrestaShopShop $shop Shop to reset
     * @return bool True on success
     */
    public function resetSyncStatus(Product $product, PrestaShopShop $shop): bool
    {
        try {
            $syncStatus = ProductSyncStatus::where('product_id', $product->id)
                ->where('shop_id', $shop->id)
                ->first();

            if ($syncStatus) {
                $syncStatus->update([
                    'sync_status' => ProductSyncStatus::STATUS_PENDING,
                    'error_message' => null,
                    'retry_count' => 0,
                    'checksum' => null,
                ]);

                Log::info('Sync status reset', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                ]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Failed to reset sync status', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get products pending sync for shop
     *
     * @param PrestaShopShop $shop Shop to query
     * @param int $limit Number of products to retrieve
     * @return Collection Collection of Product models with sync status
     */
    public function getPendingSyncs(PrestaShopShop $shop, int $limit = 50): Collection
    {
        return ProductSyncStatus::where('shop_id', $shop->id)
            ->where('sync_status', ProductSyncStatus::STATUS_PENDING)
            ->with('product')
            ->orderBy('priority', 'asc')
            ->limit($limit)
            ->get()
            ->pluck('product')
            ->filter(); // Remove nulls
    }

    /**
     * Check if product needs sync to shop
     *
     * Based on checksum change detection
     *
     * @param Product $product Product to check
     * @param PrestaShopShop $shop Shop to check
     * @return bool True if sync needed
     */
    public function needsSync(Product $product, PrestaShopShop $shop): bool
    {
        return $this->productSyncStrategy->needsSync($product, $shop);
    }
}
