<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductShopData;
use App\Models\PrestaShopShop;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * SyncVerificationService
 *
 * FAZA 1.5: Multi-Store Synchronization System - Verification Engine
 *
 * Serwis odpowiedzialny za weryfikację synchronizacji między PPM a sklepami PrestaShop.
 * Wykrywa rozbieżności, konflikty i zarządza statusami synchronizacji.
 *
 * Features:
 * - Detection of data conflicts between PPM and PrestaShop stores
 * - Sync status verification and updates
 * - Performance optimized dla 100K+ products x shops
 * - Batch processing z queue support
 * - Comprehensive reporting dla admin dashboard
 *
 * @package App\Services
 * @version 1.0
 * @since FAZA 1.5 - Multi-Store System
 */
class SyncVerificationService
{
    /**
     * Verification result constants
     */
    const RESULT_SYNCED = 'synced';
    const RESULT_CONFLICT = 'conflict';
    const RESULT_ERROR = 'error';
    const RESULT_MISSING = 'missing';
    const RESULT_PENDING = 'pending';

    /**
     * Batch size for processing large datasets
     */
    const BATCH_SIZE = 100;

    /**
     * Maximum verification time per product (seconds)
     */
    const MAX_VERIFICATION_TIME = 30;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Service initialization
    }

    // ==========================================
    // MAIN VERIFICATION METHODS
    // ==========================================

    /**
     * Verify synchronization for single product across all shops
     *
     * @param Product $product
     * @param array $options ['force' => bool, 'shops' => array]
     * @return array Verification results
     */
    public function verifyProductSync(Product $product, array $options = []): array
    {
        $startTime = microtime(true);
        $results = [];

        try {
            Log::info('Starting product sync verification', [
                'product_id' => $product->id,
                'product_sku' => $product->sku,
                'options' => $options
            ]);

            // Get shops to verify (all or specific subset)
            $shops = $this->getShopsToVerify($product, $options);

            foreach ($shops as $shop) {
                // Check for timeout
                if ((microtime(true) - $startTime) > self::MAX_VERIFICATION_TIME) {
                    Log::warning('Product verification timeout reached', [
                        'product_id' => $product->id,
                        'elapsed_time' => microtime(true) - $startTime
                    ]);
                    break;
                }

                $shopResult = $this->verifyProductShopSync($product, $shop, $options);
                $results[$shop->id] = $shopResult;
            }

            // Update overall product sync summary
            $this->updateProductSyncSummary($product, $results);

        } catch (\Exception $e) {
            Log::error('Product sync verification failed', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $results['error'] = [
                'status' => self::RESULT_ERROR,
                'message' => $e->getMessage(),
                'timestamp' => Carbon::now()
            ];
        }

        $results['verification_time'] = round(microtime(true) - $startTime, 3);

        return $results;
    }

    /**
     * Verify synchronization for specific product-shop combination
     *
     * @param Product $product
     * @param PrestaShopShop $shop
     * @param array $options
     * @return array
     */
    public function verifyProductShopSync(Product $product, PrestaShopShop $shop, array $options = []): array
    {
        try {
            // Get or create shop data record
            $shopData = $product->getOrCreateShopData($shop->id);

            // Skip verification if sync is disabled
            if ($shopData->isSyncDisabled()) {
                return [
                    'status' => 'disabled',
                    'message' => 'Synchronization is disabled for this shop',
                    'last_check' => Carbon::now(),
                ];
            }

            // Fetch current data from PrestaShop (simulated for now)
            $prestashopData = $this->fetchProductDataFromShop($product, $shop);

            if (!$prestashopData) {
                return $this->handleMissingProduct($shopData);
            }

            // Compare PPM data with PrestaShop data
            $comparisonResult = $this->compareProductData($product, $shopData, $prestashopData);

            // Update shop data based on comparison
            $this->updateShopDataBasedOnComparison($shopData, $comparisonResult);

            return [
                'status' => $comparisonResult['status'],
                'differences' => $comparisonResult['differences'] ?? [],
                'conflicts' => $comparisonResult['conflicts'] ?? [],
                'last_check' => Carbon::now(),
                'prestashop_data' => $prestashopData,
                'sync_hash' => $shopData->last_sync_hash,
            ];

        } catch (\Exception $e) {
            Log::error('Product-shop sync verification failed', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage()
            ]);

            return [
                'status' => self::RESULT_ERROR,
                'message' => $e->getMessage(),
                'last_check' => Carbon::now(),
            ];
        }
    }

    /**
     * Verify synchronization for all products in batch
     *
     * @param array $options ['limit' => int, 'offset' => int, 'shop_ids' => array]
     * @return array
     */
    public function verifyBatchSync(array $options = []): array
    {
        $startTime = microtime(true);
        $limit = $options['limit'] ?? self::BATCH_SIZE;
        $offset = $options['offset'] ?? 0;

        Log::info('Starting batch sync verification', [
            'limit' => $limit,
            'offset' => $offset,
            'options' => $options
        ]);

        $results = [
            'processed' => 0,
            'verified' => 0,
            'conflicts' => 0,
            'errors' => 0,
            'results' => [],
        ];

        try {
            // Get products to verify
            $products = Product::active()
                               ->with(['shopData.shop'])
                               ->offset($offset)
                               ->limit($limit)
                               ->get();

            foreach ($products as $product) {
                $productResult = $this->verifyProductSync($product, $options);

                $results['processed']++;
                $results['results'][$product->id] = $productResult;

                // Count results by status
                foreach ($productResult as $shopId => $shopResult) {
                    if (is_array($shopResult) && isset($shopResult['status'])) {
                        switch ($shopResult['status']) {
                            case self::RESULT_SYNCED:
                                $results['verified']++;
                                break;
                            case self::RESULT_CONFLICT:
                                $results['conflicts']++;
                                break;
                            case self::RESULT_ERROR:
                                $results['errors']++;
                                break;
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Batch sync verification failed', [
                'error' => $e->getMessage(),
                'processed' => $results['processed']
            ]);

            $results['error'] = $e->getMessage();
        }

        $results['processing_time'] = round(microtime(true) - $startTime, 3);

        return $results;
    }

    // ==========================================
    // DATA COMPARISON METHODS
    // ==========================================

    /**
     * Compare product data between PPM and PrestaShop
     *
     * @param Product $product
     * @param ProductShopData $shopData
     * @param array $prestashopData
     * @return array
     */
    protected function compareProductData(Product $product, ProductShopData $shopData, array $prestashopData): array
    {
        $differences = [];
        $conflicts = [];
        $status = self::RESULT_SYNCED;

        // Compare basic product information
        $basicComparison = $this->compareBasicData($product, $shopData, $prestashopData);
        if (!empty($basicComparison['differences'])) {
            $differences = array_merge($differences, $basicComparison['differences']);
        }
        if (!empty($basicComparison['conflicts'])) {
            $conflicts = array_merge($conflicts, $basicComparison['conflicts']);
        }

        // Compare categories
        $categoryComparison = $this->compareCategoryMappings($shopData, $prestashopData);
        if (!empty($categoryComparison['differences'])) {
            $differences = array_merge($differences, $categoryComparison['differences']);
        }

        // Compare attributes
        $attributeComparison = $this->compareAttributeMappings($shopData, $prestashopData);
        if (!empty($attributeComparison['differences'])) {
            $differences = array_merge($differences, $attributeComparison['differences']);
        }

        // Compare images
        $imageComparison = $this->compareImageSettings($shopData, $prestashopData);
        if (!empty($imageComparison['differences'])) {
            $differences = array_merge($differences, $imageComparison['differences']);
        }

        // Determine overall status
        if (!empty($conflicts)) {
            $status = self::RESULT_CONFLICT;
        } elseif (!empty($differences)) {
            $status = self::RESULT_PENDING; // Needs sync but no conflicts
        }

        return [
            'status' => $status,
            'differences' => $differences,
            'conflicts' => $conflicts,
            'comparison_timestamp' => Carbon::now(),
        ];
    }

    /**
     * Compare basic product data (name, descriptions, etc.)
     *
     * @param Product $product
     * @param ProductShopData $shopData
     * @param array $prestashopData
     * @return array
     */
    protected function compareBasicData(Product $product, ProductShopData $shopData, array $prestashopData): array
    {
        $differences = [];
        $conflicts = [];

        // Compare name
        $expectedName = $shopData->name ?: $product->name;
        $actualName = $prestashopData['name'] ?? '';

        if ($expectedName !== $actualName) {
            if (!empty($actualName) && !empty($expectedName)) {
                $conflicts[] = [
                    'field' => 'name',
                    'expected' => $expectedName,
                    'actual' => $actualName,
                    'severity' => 'high'
                ];
            } else {
                $differences[] = [
                    'field' => 'name',
                    'expected' => $expectedName,
                    'actual' => $actualName,
                    'action' => 'update'
                ];
            }
        }

        // Compare short description
        $expectedShortDesc = $shopData->short_description ?: $product->short_description;
        $actualShortDesc = $prestashopData['short_description'] ?? '';

        if ($this->normalizeDescription($expectedShortDesc) !== $this->normalizeDescription($actualShortDesc)) {
            $differences[] = [
                'field' => 'short_description',
                'expected' => $expectedShortDesc,
                'actual' => $actualShortDesc,
                'action' => 'update'
            ];
        }

        // Compare long description
        $expectedLongDesc = $shopData->long_description ?: $product->long_description;
        $actualLongDesc = $prestashopData['long_description'] ?? '';

        if ($this->normalizeDescription($expectedLongDesc) !== $this->normalizeDescription($actualLongDesc)) {
            $differences[] = [
                'field' => 'long_description',
                'expected' => $expectedLongDesc,
                'actual' => $actualLongDesc,
                'action' => 'update'
            ];
        }

        // Compare publication status
        $expectedPublished = $shopData->is_published;
        $actualPublished = $prestashopData['active'] ?? false;

        if ($expectedPublished !== $actualPublished) {
            $differences[] = [
                'field' => 'publication_status',
                'expected' => $expectedPublished,
                'actual' => $actualPublished,
                'action' => $expectedPublished ? 'publish' : 'unpublish'
            ];
        }

        return [
            'differences' => $differences,
            'conflicts' => $conflicts,
        ];
    }

    /**
     * Compare category mappings
     *
     * @param ProductShopData $shopData
     * @param array $prestashopData
     * @return array
     */
    protected function compareCategoryMappings(ProductShopData $shopData, array $prestashopData): array
    {
        $differences = [];

        $expectedCategories = $shopData->category_mappings ?? [];
        $actualCategories = $prestashopData['categories'] ?? [];

        // Simple array comparison (can be enhanced with more sophisticated logic)
        if (json_encode($expectedCategories) !== json_encode($actualCategories)) {
            $differences[] = [
                'field' => 'categories',
                'expected' => $expectedCategories,
                'actual' => $actualCategories,
                'action' => 'update_categories'
            ];
        }

        return ['differences' => $differences];
    }

    /**
     * Compare attribute mappings
     *
     * @param ProductShopData $shopData
     * @param array $prestashopData
     * @return array
     */
    protected function compareAttributeMappings(ProductShopData $shopData, array $prestashopData): array
    {
        $differences = [];

        $expectedAttributes = $shopData->attribute_mappings ?? [];
        $actualAttributes = $prestashopData['attributes'] ?? [];

        if (json_encode($expectedAttributes) !== json_encode($actualAttributes)) {
            $differences[] = [
                'field' => 'attributes',
                'expected' => $expectedAttributes,
                'actual' => $actualAttributes,
                'action' => 'update_attributes'
            ];
        }

        return ['differences' => $differences];
    }

    /**
     * Compare image settings
     *
     * @param ProductShopData $shopData
     * @param array $prestashopData
     * @return array
     */
    protected function compareImageSettings(ProductShopData $shopData, array $prestashopData): array
    {
        $differences = [];

        $expectedImages = $shopData->image_settings ?? [];
        $actualImages = $prestashopData['images'] ?? [];

        if (json_encode($expectedImages) !== json_encode($actualImages)) {
            $differences[] = [
                'field' => 'images',
                'expected' => $expectedImages,
                'actual' => $actualImages,
                'action' => 'update_images'
            ];
        }

        return ['differences' => $differences];
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Fetch product data from PrestaShop store
     *
     * @param Product $product
     * @param PrestaShopShop $shop
     * @return array|null
     */
    protected function fetchProductDataFromShop(Product $product, PrestaShopShop $shop): ?array
    {
        try {
            // SIMULATION: Real implementation would use PrestaShop API
            // For now, we simulate realistic data with random variations

            if (!$shop->is_active || !$shop->api_key) {
                return null;
            }

            // Simulate API call with realistic response time
            usleep(mt_rand(50000, 200000)); // 50-200ms

            // Find existing shop data to get external_id
            $shopData = $product->dataForShop($shop->id)->first();
            $externalId = $shopData?->external_id;

            if (!$externalId) {
                // Product doesn't exist in PrestaShop
                return null;
            }

            // Simulate realistic product data from PrestaShop
            $simulatedData = [
                'id' => $externalId,
                'name' => $shopData->name ?: $product->name,
                'short_description' => $shopData->short_description ?: $product->short_description,
                'long_description' => $shopData->long_description ?: $product->long_description,
                'active' => $shopData->is_published ?? false,
                'categories' => $shopData->category_mappings ?? [],
                'attributes' => $shopData->attribute_mappings ?? [],
                'images' => $shopData->image_settings ?? [],
                'last_modified' => Carbon::now()->subMinutes(mt_rand(1, 1440))->toISOString(), // Random time in last 24h
                'api_response_time' => mt_rand(80, 300) / 10, // 8-30ms response time
            ];

            // Occasionally introduce realistic differences for testing
            if (mt_rand(1, 10) === 1) { // 10% chance of differences
                $simulatedData['short_description'] .= ' [Modified in PrestaShop]';
            }

            Log::info('Fetched product data from PrestaShop (simulated)', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'external_id' => $externalId,
                'api_response_time' => $simulatedData['api_response_time']
            ]);

            return $simulatedData;

        } catch (\Exception $e) {
            Log::error('Failed to fetch product data from PrestaShop', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Get shops to verify for a product
     *
     * @param Product $product
     * @param array $options
     * @return Collection
     */
    protected function getShopsToVerify(Product $product, array $options = []): Collection
    {
        $query = PrestaShopShop::active();

        // Filter by specific shops if provided
        if (!empty($options['shops'])) {
            $query->whereIn('id', $options['shops']);
        }

        // Only shops that have this product (have shop data)
        if (!($options['include_all'] ?? false)) {
            $query->whereHas('productShopData', function ($subquery) use ($product) {
                $subquery->where('product_id', $product->id);
            });
        }

        return $query->get();
    }

    /**
     * Handle missing product in PrestaShop
     *
     * @param ProductShopData $shopData
     * @return array
     */
    protected function handleMissingProduct(ProductShopData $shopData): array
    {
        if ($shopData->is_published) {
            // Product should exist but doesn't - this is a conflict
            $shopData->markAsConflict([
                'type' => 'missing_product',
                'message' => 'Product is marked as published but not found in PrestaShop',
                'suggested_action' => 'republish_product'
            ]);

            return [
                'status' => self::RESULT_CONFLICT,
                'message' => 'Product not found in PrestaShop but marked as published',
                'last_check' => Carbon::now(),
            ];
        } else {
            // Product is not published, this is expected
            return [
                'status' => 'not_published',
                'message' => 'Product not published to this shop',
                'last_check' => Carbon::now(),
            ];
        }
    }

    /**
     * Update shop data based on comparison results
     *
     * @param ProductShopData $shopData
     * @param array $comparisonResult
     * @return void
     */
    protected function updateShopDataBasedOnComparison(ProductShopData $shopData, array $comparisonResult): void
    {
        switch ($comparisonResult['status']) {
            case self::RESULT_SYNCED:
                $shopData->markAsSynced();
                break;

            case self::RESULT_CONFLICT:
                $shopData->markAsConflict([
                    'conflicts' => $comparisonResult['conflicts'],
                    'detected_at' => $comparisonResult['comparison_timestamp'],
                ]);
                break;

            case self::RESULT_PENDING:
                $shopData->markAsPending();
                break;

            case self::RESULT_ERROR:
                $shopData->markAsError([
                    'comparison_error' => $comparisonResult['error'] ?? 'Unknown comparison error'
                ]);
                break;
        }
    }

    /**
     * Update product sync summary across all shops
     *
     * @param Product $product
     * @param array $results
     * @return void
     */
    protected function updateProductSyncSummary(Product $product, array $results): void
    {
        // This could update a cached summary or trigger events
        // For now, we just log the summary

        $summary = $product->getMultiStoreSyncSummary();

        Log::info('Product sync summary updated', [
            'product_id' => $product->id,
            'summary' => $summary,
            'verification_results' => array_keys($results)
        ]);
    }

    /**
     * Normalize description for comparison (remove whitespace differences)
     *
     * @param string|null $description
     * @return string
     */
    protected function normalizeDescription(?string $description): string
    {
        if (!$description) {
            return '';
        }

        // Remove extra whitespace, normalize line endings
        return trim(preg_replace('/\s+/', ' ', $description));
    }

    // ==========================================
    // PUBLIC UTILITY METHODS
    // ==========================================

    /**
     * Get verification statistics for dashboard
     *
     * @param array $filters ['shop_ids' => array, 'date_from' => Carbon, 'date_to' => Carbon]
     * @return array
     */
    public function getVerificationStatistics(array $filters = []): array
    {
        $query = ProductShopData::query();

        // Apply filters
        if (!empty($filters['shop_ids'])) {
            $query->whereIn('shop_id', $filters['shop_ids']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('last_sync_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('last_sync_at', '<=', $filters['date_to']);
        }

        $stats = [
            'total_products' => $query->distinct('product_id')->count(),
            'total_shop_products' => $query->count(),
            'synced' => $query->where('sync_status', 'synced')->count(),
            'pending' => $query->where('sync_status', 'pending')->count(),
            'conflicts' => $query->where('sync_status', 'conflict')->count(),
            'errors' => $query->where('sync_status', 'error')->count(),
            'disabled' => $query->where('sync_status', 'disabled')->count(),
        ];

        $stats['sync_health_percentage'] = $stats['total_shop_products'] > 0
            ? round(($stats['synced'] / $stats['total_shop_products']) * 100, 1)
            : 0;

        return $stats;
    }

    /**
     * Get products needing verification
     *
     * @param int $limit
     * @param array $filters
     * @return Collection
     */
    public function getProductsNeedingVerification(int $limit = 50, array $filters = []): Collection
    {
        return Product::whereHas('shopData', function ($query) use ($filters) {
            $query->whereIn('sync_status', ['pending', 'error', 'conflict']);

            if (!empty($filters['shop_ids'])) {
                $query->whereIn('shop_id', $filters['shop_ids']);
            }
        })
        ->with(['shopData' => function ($query) use ($filters) {
            $query->whereIn('sync_status', ['pending', 'error', 'conflict']);

            if (!empty($filters['shop_ids'])) {
                $query->whereIn('shop_id', $filters['shop_ids']);
            }
        }])
        ->limit($limit)
        ->get();
    }

    /**
     * Get sync conflicts requiring manual resolution
     *
     * @param int $limit
     * @return Collection
     */
    public function getConflictsRequiringResolution(int $limit = 20): Collection
    {
        return ProductShopData::with(['product', 'shop'])
                              ->where('sync_status', 'conflict')
                              ->whereNotNull('conflict_detected_at')
                              ->orderBy('conflict_detected_at', 'desc')
                              ->limit($limit)
                              ->get();
    }
}