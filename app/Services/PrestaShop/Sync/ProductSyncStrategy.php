<?php

namespace App\Services\PrestaShop\Sync;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\PrestaShop\BasePrestaShopClient;
use App\Services\PrestaShop\ProductTransformer;
use App\Services\PrestaShop\CategoryMapper;
use App\Services\PrestaShop\PriceGroupMapper;
use App\Services\PrestaShop\WarehouseMapper;
use App\Services\PrestaShop\PrestaShopPriceExporter;
use App\Services\PrestaShop\CategoryAssociationService;
use App\Models\Product;
use App\Models\ProductDescription;
use App\Models\PrestaShopShop;
use App\Models\ProductShopData;
use App\Models\SyncLog;
use App\Models\ShopVariant;
use App\Exceptions\PrestaShopAPIException;
use App\Jobs\PrestaShop\SyncShopVariantsToPrestaShopJob;

/**
 * Product Sync Strategy
 *
 * Implements ISyncStrategy dla Product model synchronization PPM → PrestaShop
 *
 * Features:
 * - Multi-shop support z shop-specific data inheritance
 * - Category mapping z hierarchia preservation
 * - Price group mapping (8 grup PPM → PrestaShop price groups)
 * - Stock mapping (multi-warehouse → PrestaShop stock)
 * - Checksum-based change detection
 * - Comprehensive error handling z retry support
 * - Transaction-based atomic operations
 *
 * @package App\Services\PrestaShop\Sync
 */
class ProductSyncStrategy implements ISyncStrategy
{
    /**
     * Constructor with dependency injection
     *
     * UPDATED (2025-11-14): Added PrestaShopPriceExporter for specific_prices sync
     * UPDATED (2025-11-27): Added CategoryAssociationService for direct DB category sync
     */
    public function __construct(
        private ProductTransformer $transformer,
        private CategoryMapper $categoryMapper,
        private PriceGroupMapper $priceMapper,
        private WarehouseMapper $warehouseMapper,
        private PrestaShopPriceExporter $priceExporter,
        private CategoryAssociationService $categoryAssociationService
    ) {}

    /**
     * Synchronize product to PrestaShop
     * ETAP_07d (2025-12-02): Added pendingMediaChanges for media sync
     *
     * @param array $pendingMediaChanges Pending media changes from session (passed via job)
     */
    public function syncToPrestaShop(
        Model $model,
        BasePrestaShopClient $client,
        PrestaShopShop $shop,
        array $pendingMediaChanges = []
    ): array {
        if (!$model instanceof Product) {
            throw new \InvalidArgumentException('Model must be instance of Product');
        }

        $startTime = microtime(true);

        // DEBUG: Entry point
        Log::debug('[SYNC DEBUG] ProductSyncStrategy::syncToPrestaShop START', [
            'product_id' => $model->id,
            'product_sku' => $model->sku,
            'shop_id' => $shop->id,
            'shop_name' => $shop->name,
        ]);

        // Validate before sync
        Log::debug('[SYNC DEBUG] Starting validation');
        $validationErrors = $this->validateBeforeSync($model, $shop);
        if (!empty($validationErrors)) {
            $errorMessage = 'Product validation failed: ' . implode(', ', $validationErrors);
            Log::warning('Product sync validation failed', [
                'product_id' => $model->id,
                'product_sku' => $model->sku,
                'shop_id' => $shop->id,
                'errors' => $validationErrors,
            ]);
            throw new \InvalidArgumentException($errorMessage);
        }
        Log::debug('[SYNC DEBUG] Validation passed');

        // Get or create sync status record
        Log::debug('[SYNC DEBUG] Getting sync status record');
        $syncStatus = $this->getOrCreateSyncStatus($model, $shop);
        Log::debug('[SYNC DEBUG] Sync status retrieved', [
            'sync_status' => $syncStatus->sync_status,
            'prestashop_product_id' => $syncStatus->prestashop_product_id,
        ]);

        // Check if sync needed based on checksum
        Log::debug('[SYNC DEBUG] Checking if sync needed');
        if (!$this->needsSync($model, $shop)) {
            Log::info('Product sync skipped - no changes detected', [
                'product_id' => $model->id,
                'shop_id' => $shop->id,
                'checksum' => $syncStatus->checksum,
            ]);

            // ETAP_05d FIX: Still sync compatibilities even if product checksum unchanged
            // This allows compatibility changes to be pushed without product changes
            if ($syncStatus->prestashop_product_id) {
                Log::debug('[SYNC DEBUG] Checking for compatibility sync despite skipped product sync');
                $this->syncCompatibilitiesIfEnabled($model, $shop, $syncStatus->prestashop_product_id, $client);
            }

            return [
                'success' => true,
                'external_id' => $syncStatus->prestashop_product_id,
                'message' => 'No changes - sync skipped',
                'checksum' => $syncStatus->checksum,
                'skipped' => true,
            ];
        }
        Log::debug('[SYNC DEBUG] Sync IS needed - proceeding');

        Log::debug('[SYNC DEBUG] Beginning database transaction');
        DB::beginTransaction();

        try {
            // Mark as syncing
            Log::debug('[SYNC DEBUG] Updating sync status to "syncing"');
            $syncStatus->update([
                'sync_status' => 'syncing',
                'last_sync_at' => now(),
                'error_message' => null,
            ]);
            Log::debug('[SYNC DEBUG] Status updated to syncing');

            // Transform product data
            Log::debug('[SYNC DEBUG] Transforming product data for PrestaShop');
            $productData = $this->transformer->transformForPrestaShop($model, $client);
            Log::debug('[SYNC DEBUG] Product data transformed', [
                'has_product_key' => isset($productData['product']),
                'product_reference' => $productData['product']['reference'] ?? 'N/A',
            ]);

            // Determine if create or update
            $isUpdate = !empty($syncStatus->prestashop_product_id);
            Log::debug('[SYNC DEBUG] Operation type determined', [
                'operation' => $isUpdate ? 'UPDATE' : 'CREATE',
                'prestashop_product_id' => $syncStatus->prestashop_product_id,
            ]);

            // Track changed fields for UPDATE operations (2025-11-07)
            $changedFields = [];
            $syncedData = [];

            if ($isUpdate) {
                Log::debug('[SYNC DEBUG] UPDATE operation - fetching previous sync');
                // Get previous successful sync to detect changes
                $previousSync = \App\Models\SyncJob::where('source_type', \App\Models\SyncJob::TYPE_PPM)
                    ->where('source_id', $model->id)
                    ->where('target_type', \App\Models\SyncJob::TYPE_PRESTASHOP)
                    ->where('target_id', $shop->id)
                    ->where('status', \App\Models\SyncJob::STATUS_COMPLETED)
                    ->whereNotNull('result_summary->synced_data')
                    ->latest('completed_at')
                    ->first();
                Log::debug('[SYNC DEBUG] Previous sync fetched', [
                    'found' => $previousSync !== null,
                    'sync_job_id' => $previousSync?->id,
                ]);

                // Extract trackable fields from transformed data
                Log::debug('[SYNC DEBUG] Extracting trackable fields');
                $syncedData = $this->extractTrackableFields($productData, $model, $shop);
                Log::debug('[SYNC DEBUG] Trackable fields extracted', [
                    'fields_count' => count($syncedData),
                ]);

                // Compare with previous sync if exists
                if ($previousSync && isset($previousSync->result_summary['synced_data'])) {
                    Log::debug('[SYNC DEBUG] Detecting changed fields');
                    $changedFields = $this->detectChangedFields(
                        $previousSync->result_summary['synced_data'],
                        $syncedData
                    );
                    Log::debug('[SYNC DEBUG] Changed fields detected', [
                        'changed_count' => count($changedFields),
                        'changed_fields' => array_keys($changedFields),
                    ]);
                }

                Log::debug('[SYNC DEBUG] Calling PrestaShop API updateProduct', [
                    'prestashop_product_id' => $syncStatus->prestashop_product_id,
                ]);
                $response = $client->updateProduct($syncStatus->prestashop_product_id, $productData);
                Log::debug('[SYNC DEBUG] PrestaShop API updateProduct returned');
                $operation = 'update';
            } else {
                Log::debug('[SYNC DEBUG] CREATE operation - extracting trackable fields');
                // CREATE: Extract data but don't track changes (per user request)
                $syncedData = $this->extractTrackableFields($productData, $model, $shop);
                Log::debug('[SYNC DEBUG] Trackable fields extracted', [
                    'fields_count' => count($syncedData),
                ]);

                Log::debug('[SYNC DEBUG] Calling PrestaShop API createProduct');
                $response = $client->createProduct($productData);
                Log::debug('[SYNC DEBUG] PrestaShop API createProduct returned');
                $operation = 'create';
            }

            // Extract PrestaShop product ID
            Log::debug('[SYNC DEBUG] Extracting external ID from response');
            $externalId = $this->extractExternalId($response);
            if (!$externalId) {
                throw new PrestaShopAPIException(
                    'Failed to extract product ID from PrestaShop response',
                    0,
                    null,
                    ['response' => $response]
                );
            }
            Log::debug('[SYNC DEBUG] External ID extracted', [
                'external_id' => $externalId,
            ]);

            // Calculate new checksum
            Log::debug('[SYNC DEBUG] Calculating new checksum');
            $newChecksum = $this->calculateChecksum($model, $shop);
            Log::debug('[SYNC DEBUG] Checksum calculated', [
                'checksum' => substr($newChecksum, 0, 16) . '...',
            ]);

            // Update sync status to success
            Log::debug('[SYNC DEBUG] Updating sync status to "synced"');
            $syncStatus->update([
                'sync_status' => 'synced',
                'prestashop_product_id' => $externalId,
                'last_success_sync_at' => now(),
                'last_push_at' => now(), // ETAP_13: Track PPM → PrestaShop push timestamp
                'checksum' => $newChecksum,
                'retry_count' => 0,
                'error_message' => null,
            ]);
            Log::debug('[SYNC DEBUG] Sync status updated to success');

            // Log successful sync
            Log::debug('[SYNC DEBUG] Creating SyncLog record');
            SyncLog::create([
                'shop_id' => $shop->id,
                'product_id' => $model->id,
                'operation' => 'sync_product',
                'direction' => 'ppm_to_ps',
                'status' => 'success',
                'message' => "Product {$operation}d successfully",
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'created_at' => now(),
            ]);
            Log::debug('[SYNC DEBUG] SyncLog created');

            Log::debug('[SYNC DEBUG] Committing database transaction');
            DB::commit();
            Log::debug('[SYNC DEBUG] Transaction committed');

            // FIX 2025-11-27: Ensure category associations via direct DB
            // WORKAROUND: PrestaShop API ignores associations.categories
            Log::debug('[SYNC DEBUG] Starting category association sync');
            try {
                // Extract PrestaShop category IDs from product data
                $prestashopCategoryIds = [];
                if (isset($productData['product']['associations']['categories'])) {
                    $prestashopCategoryIds = collect($productData['product']['associations']['categories'])
                        ->pluck('id')
                        ->map(fn($id) => (int) $id)
                        ->toArray();
                }

                if (!empty($prestashopCategoryIds)) {
                    $this->categoryAssociationService->ensureProductCategories(
                        $model,
                        $externalId,
                        $prestashopCategoryIds,
                        $shop
                    );
                    Log::info('[SYNC DEBUG] Category association sync completed', [
                        'product_id' => $model->id,
                        'prestashop_id' => $externalId,
                        'category_count' => count($prestashopCategoryIds),
                    ]);
                }
            } catch (\Exception $e) {
                // Log error but don't fail entire sync
                Log::error('[SYNC DEBUG] Category association sync failed (non-fatal)', [
                    'product_id' => $model->id,
                    'shop_id' => $shop->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // NEW (2025-11-14): Export specific_prices for product
            // ISSUE FIX: PRESTASHOP_PRICE_SYNC_ISSUE.md
            Log::debug('[SYNC DEBUG] Starting price export');
            try {
                $priceExportResults = $this->priceExporter->exportPricesForProduct($model, $shop, $externalId);
                Log::info('[SYNC DEBUG] Price export completed', [
                    'created' => count($priceExportResults['created']),
                    'updated' => count($priceExportResults['updated']),
                    'deleted' => count($priceExportResults['deleted']),
                ]);
            } catch (\Exception $e) {
                // Log error but don't fail entire sync
                Log::error('[SYNC DEBUG] Price export failed (non-fatal)', [
                    'product_id' => $model->id,
                    'shop_id' => $shop->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // NEW (2025-12-01): ETAP_07d Phase 4.4 - Sync media if enabled
            // ETAP_07d (2025-12-02): Pass pendingMediaChanges from job
            Log::debug('[SYNC DEBUG] Checking media sync');
            $this->syncMediaIfEnabled($model, $shop, $externalId, $pendingMediaChanges);

            // NEW (2025-12-03): ETAP_07e FAZA 4.4 - Sync product features
            Log::debug('[SYNC DEBUG] Starting feature sync');
            $this->syncFeaturesIfEnabled($model, $shop, $externalId, $client);

            // NEW (2025-12-09): ETAP_05d FAZA 4.5 - Sync vehicle compatibilities
            Log::debug('[SYNC DEBUG] Starting compatibility sync');
            $this->syncCompatibilitiesIfEnabled($model, $shop, $externalId, $client);

            // NEW (2026-01-28): Auto-dispatch variant sync if product has shop variants
            Log::debug('[SYNC DEBUG] Checking for variant sync');
            $this->dispatchVariantSyncIfNeeded($model, $shop, $externalId);

            // NEW (2025-12-11): ETAP_07f FAZA 8.2 - Mark visual description as synced
            Log::debug('[SYNC DEBUG] Marking visual description as synced');
            $this->markVisualDescriptionAsSynced($model, $shop);

            Log::info('Product synced successfully to PrestaShop', [
                'product_id' => $model->id,
                'shop_id' => $shop->id,
                'external_id' => $externalId,
                'operation' => $operation,
                'changed_fields_count' => count($changedFields),
            ]);

            $result = [
                'success' => true,
                'external_id' => $externalId,
                'message' => "Product {$operation}d successfully",
                'checksum' => $newChecksum,
                'operation' => $operation,
                'synced_data' => $syncedData, // Store for next comparison
            ];

            // Add changed_fields only for UPDATE (per user request)
            if ($operation === 'update' && !empty($changedFields)) {
                $result['changed_fields'] = $changedFields;
                $result['changed_fields_count'] = count($changedFields);
            }

            Log::debug('[SYNC DEBUG] ProductSyncStrategy::syncToPrestaShop COMPLETED SUCCESSFULLY', [
                'product_id' => $model->id,
                'shop_id' => $shop->id,
                'operation' => $operation,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('[SYNC DEBUG] ProductSyncStrategy::syncToPrestaShop EXCEPTION', [
                'product_id' => $model->id,
                'shop_id' => $shop->id,
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
                'exception_file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            DB::rollBack();
            Log::debug('[SYNC DEBUG] Transaction rolled back');
            $this->handleSyncError($e, $model, $shop);
            Log::debug('[SYNC DEBUG] Error handled, re-throwing exception');
            throw $e;
        }
    }

    /**
     * Calculate checksum dla product data
     */
    public function calculateChecksum(Model $model, PrestaShopShop $shop): string
    {
        if (!$model instanceof Product) {
            throw new \InvalidArgumentException('Model must be instance of Product');
        }

        $data = [
            'sku' => $model->sku,
            'name' => $model->name,
            'short_description' => $model->short_description,
            'long_description' => $model->long_description,
            'weight' => $model->weight,
            'ean' => $model->ean,
            'is_active' => $model->is_active,
        ];

        // Include shop-specific data
        $shopData = $model->dataForShop($shop->id)->first();
        if ($shopData) {
            $data['shop_name'] = $shopData->name;
            $data['shop_short_description'] = $shopData->short_description;
            $data['shop_long_description'] = $shopData->long_description;
        }

        // FIX 2025-11-18 (#12): Use Option A mappings values for checksum
        // CRITICAL: Checksum must use PrestaShop IDs (not PPM IDs) to detect changes
        //
        // Option A structure:
        // {
        //   "ui": {"selected": [100, 103, 42], "primary": 100},
        //   "mappings": {"100": 9, "103": 15, "42": 800},
        //   "metadata": {...}
        // }
        //
        // Why use mappings values?
        // - PrestaShop IDs are what gets sent to API
        // - Changes in PPM → PrestaShop mapping MUST trigger sync
        // - Backward compatible: ProductShopDataCast auto-converts legacy formats
        //
        // DIAGNOSIS 2025-11-21: Debug category data read from DB
        Log::debug('[CATEGORY SYNC DEBUG] ProductSyncStrategy: Reading category data from DB', [
            'product_id' => $model->id,
            'shop_id' => $shop->id,
            'shopData_exists' => $shopData !== null,
            'raw_category_mappings' => $shopData ? $shopData->category_mappings : 'NO_SHOP_DATA',
            'hasCategoryMappings' => $shopData ? $shopData->hasCategoryMappings() : false,
        ]);

        // Include categories, prices, stock
        if ($shopData && $shopData->hasCategoryMappings()) {
            // Extract PrestaShop IDs from mappings (values only)
            $mappings = $shopData->category_mappings['mappings'] ?? [];
            $data['categories'] = collect($mappings)
                ->values() // Get PrestaShop IDs
                ->sort()
                ->values()
                ->toArray();

            Log::debug('[CATEGORY SYNC DEBUG] ProductSyncStrategy: Using shop-specific mappings', [
                'product_id' => $model->id,
                'shop_id' => $shop->id,
                'mappings' => $mappings,
                'prestashop_ids' => $data['categories'],
            ]);

            Log::debug('[FIX #12] Checksum using Option A mappings', [
                'product_id' => $model->id,
                'shop_id' => $shop->id,
                'prestashop_ids' => $data['categories'],
            ]);
        } else {
            // Fallback: global categories (PPM category IDs)
            $data['categories'] = $model->categories->pluck('id')->sort()->values()->toArray();

            Log::debug('[CATEGORY SYNC DEBUG] ProductSyncStrategy: Using FALLBACK (default categories)', [
                'product_id' => $model->id,
                'shop_id' => $shop->id,
                'reason' => $shopData ? 'hasCategoryMappings() = false' : 'shopData = null',
                'default_categories_count' => count($data['categories']),
                'default_category_ids' => $data['categories'],
            ]);
        }

        // CRITICAL FIX (2025-11-12): Include prices in checksum
        // User complaint: "zmiana stanów magazynowych nie pojawia się w CHANGED FIELDS"
        // ROOT CAUSE: Checksum didn't include prices/stock → sync was skipped → no change detection
        $defaultPrice = $model->prices()->where('price_group_id', 1)->first();
        if ($defaultPrice) {
            $data['price_net'] = $defaultPrice->price_net;
            $data['price_gross'] = $defaultPrice->price_gross;
        }

        // Include stock (calculate total across all warehouses for this shop)
        try {
            $warehouseMapper = app(\App\Services\PrestaShop\Mappers\WarehouseMapper::class);
            $data['stock_quantity'] = $warehouseMapper->calculateStockForShop($model, $shop);
        } catch (\Exception $e) {
            // Non-blocking: If stock calculation fails, use 0
            \Log::warning('Failed to calculate stock for checksum', [
                'product_id' => $model->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);
            $data['stock_quantity'] = 0;
        }

        // ETAP_07h FIX (2026-01-12): Include visual description in checksum
        // Without this, CSS/style changes in UVE don't trigger sync because
        // ProductDescription changes weren't included in checksum calculation
        $visualDescription = ProductDescription::where('product_id', $model->id)
            ->where('shop_id', $shop->id)
            ->where('sync_to_prestashop', true)
            ->first();

        if ($visualDescription) {
            // Include hash of rendered_html to detect visual description changes
            $data['visual_description_hash'] = md5($visualDescription->rendered_html ?? '');
            // Include cssRules hash to detect style changes
            $data['css_rules_hash'] = md5(json_encode($visualDescription->css_rules ?? []));
        }

        ksort($data);
        return hash('sha256', json_encode($data));
    }

    /**
     * Handle sync error
     */
    public function handleSyncError(
        \Exception $exception,
        Model $model,
        PrestaShopShop $shop
    ): void {
        if (!$model instanceof Product) {
            return;
        }

        $syncStatus = $this->getOrCreateSyncStatus($model, $shop);
        $retryCount = $syncStatus->retry_count + 1;

        $syncStatus->update([
            'sync_status' => 'error',
            'error_message' => $exception->getMessage(),
            'retry_count' => $retryCount,
        ]);

        SyncLog::create([
            'shop_id' => $shop->id,
            'product_id' => $model->id,
            'operation' => 'sync_product',
            'direction' => 'ppm_to_ps',
            'status' => 'error',
            'message' => $exception->getMessage(),
            'created_at' => now(),
        ]);

        Log::error('Product sync failed', [
            'product_id' => $model->id,
            'shop_id' => $shop->id,
            'error' => $exception->getMessage(),
            'retry_count' => $retryCount,
        ]);
    }

    /**
     * Validate product before sync
     */
    public function validateBeforeSync(Model $model, PrestaShopShop $shop): array
    {
        if (!$model instanceof Product) {
            return ['Model must be instance of Product'];
        }

        $errors = [];

        if (empty($model->sku)) {
            $errors[] = 'SKU is required';
        }

        if (empty($model->name)) {
            $errors[] = 'Product name is required';
        }

        if (!$model->is_active) {
            $errors[] = 'Product must be active to sync';
        }

        return $errors;
    }

    /**
     * Check if product needs sync
     */
    public function needsSync(Model $model, PrestaShopShop $shop): bool
    {
        if (!$model instanceof Product) {
            return false;
        }

        $syncStatus = $this->getOrCreateSyncStatus($model, $shop);

        // Always sync if never synced
        if ($syncStatus->sync_status === 'pending' || empty($syncStatus->checksum)) {
            return true;
        }

        // Check if data changed using checksum
        $currentChecksum = $this->calculateChecksum($model, $shop);
        return $currentChecksum !== $syncStatus->checksum;
    }

    /**
     * Get or create ProductShopData record (CONSOLIDATED 2025-10-13)
     *
     * Updated to use ProductShopData instead of deprecated ProductSyncStatus
     */
    private function getOrCreateSyncStatus(Product $product, PrestaShopShop $shop): ProductShopData
    {
        return ProductShopData::firstOrCreate(
            [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
            ],
            [
                'sync_status' => 'pending',
                'retry_count' => 0,
            ]
        );
    }

    /**
     * Extract external ID from PrestaShop API response
     */
    private function extractExternalId(array $response): ?int
    {
        if (isset($response['product']['id'])) {
            return (int) $response['product']['id'];
        }

        if (isset($response['id'])) {
            return (int) $response['id'];
        }

        return null;
    }

    /**
     * Extract trackable fields from product data
     *
     * Extracts key fields from transformed PrestaShop data for change detection
     *
     * @param array $productData Transformed PrestaShop product data
     * @param \App\Models\Product|null $model PPM Product model (optional, for gross price tracking)
     * @param PrestaShopShop|null $shop PrestaShop shop (optional, for stock calculation)
     * @return array Trackable fields (flat structure for easy comparison)
     */
    private function extractTrackableFields(array $productData, ?\App\Models\Product $model = null, ?PrestaShopShop $shop = null): array
    {
        $product = $productData['product'] ?? [];

        $fields = [
            // Basic fields
            'sku' => $product['reference'] ?? null,
            'ean' => $product['ean13'] ?? null,
            'active' => $product['active'] ?? null,

            // Multilang fields (extract from first language)
            'name' => is_array($product['name'] ?? null)
                ? ($product['name'][0]['value'] ?? null)
                : ($product['name'] ?? null),
            'short_description' => is_array($product['description_short'] ?? null)
                ? ($product['description_short'][0]['value'] ?? null)
                : ($product['description_short'] ?? null),
            'long_description' => is_array($product['description'] ?? null)
                ? ($product['description'][0]['value'] ?? null)
                : ($product['description'] ?? null),

            // Pricing and physical
            // PrestaShop stores net price
            'price (netto)' => $product['price'] ?? null,
            'weight' => $product['weight'] ?? null,
            'width' => $product['width'] ?? null,
            'height' => $product['height'] ?? null,
            'depth' => $product['depth'] ?? null,
            'quantity' => $product['quantity'] ?? null,

            // Associations
            'categories' => isset($product['associations']['categories'])
                ? collect($product['associations']['categories'])->pluck('id')->sort()->values()->toArray()
                : [],

            // Metadata
            'manufacturer_name' => $product['manufacturer_name'] ?? null,
            'tax_rules_group' => $product['id_tax_rules_group'] ?? null,
        ];

        // BUG FIX #13 (2025-11-12): Add gross price from PPM if model available
        // User wants to see BRUTTO price in Changed Fields
        if ($model) {
            // Get default price group (Detaliczna = 1)
            $defaultPrice = $model->prices()->where('price_group_id', 1)->first();
            if ($defaultPrice) {
                $fields['price (brutto)'] = $defaultPrice->price_gross;
            }

            // BUG FIX #15 (2025-11-12): Extract quantity from PPM, not PrestaShop response
            // User complaint: "zmiana stanów magazynowych nie pojawia się w CHANGED FIELDS"
            // ROOT CAUSE: Quantity was extracted from PrestaShop response (which may be 0/stale)
            //             instead of actual PPM value that was sent
            // SOLUTION: Calculate stock from PPM warehouse data (same value that's sent to PrestaShop)
            if ($shop) {
                try {
                    $warehouseMapper = app(\App\Services\PrestaShop\Mappers\WarehouseMapper::class);
                    $fields['quantity'] = $warehouseMapper->calculateStockForShop($model, $shop);
                } catch (\Exception $e) {
                    \Log::warning('Failed to calculate quantity for tracking', [
                        'product_id' => $model->id,
                        'shop_id' => $shop->id,
                        'error' => $e->getMessage(),
                    ]);
                    // Keep PrestaShop response quantity as fallback
                }
            }
        }

        return $fields;
    }

    /**
     * Detect changed fields between old and new product data
     *
     * Compares two field arrays and returns only changed fields with old/new values
     *
     * @param array $oldData Previous synced data
     * @param array $newData Current data to sync
     * @return array Changed fields in format ['field' => ['old' => x, 'new' => y]]
     */
    private function detectChangedFields(array $oldData, array $newData): array
    {
        $changes = [];

        foreach ($newData as $field => $newValue) {
            $oldValue = $oldData[$field] ?? null;

            // Skip if values are identical
            if ($this->valuesAreEqual($oldValue, $newValue)) {
                continue;
            }

            // Format change for display
            $changes[$field] = [
                'old' => $this->formatValueForDisplay($oldValue),
                'new' => $this->formatValueForDisplay($newValue),
            ];
        }

        return $changes;
    }

    /**
     * Compare two values for equality (handles arrays, nulls, numeric types)
     *
     * @param mixed $value1 First value
     * @param mixed $value2 Second value
     * @return bool True if values are equal
     */
    private function valuesAreEqual($value1, $value2): bool
    {
        // Both null
        if ($value1 === null && $value2 === null) {
            return true;
        }

        // One null, one not
        if ($value1 === null || $value2 === null) {
            return false;
        }

        // Arrays
        if (is_array($value1) && is_array($value2)) {
            return $value1 === $value2;
        }

        // Numeric comparison (handle float precision)
        if (is_numeric($value1) && is_numeric($value2)) {
            return abs((float) $value1 - (float) $value2) < 0.001;
        }

        // String comparison
        return (string) $value1 === (string) $value2;
    }

    /**
     * Format value for display in changed fields
     *
     * @param mixed $value Value to format
     * @return mixed Formatted value (truncate long strings, format arrays)
     */
    private function formatValueForDisplay($value)
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return count($value) > 10
                ? array_slice($value, 0, 10) // Truncate long arrays
                : $value;
        }

        if (is_string($value) && mb_strlen($value) > 200) {
            return mb_substr($value, 0, 200) . '...'; // Truncate long strings
        }

        return $value;
    }

    /**
     * Sync media to PrestaShop if enabled (ETAP_07d Phase 4.4)
     *
     * Processes pending media shop changes from session:
     * - 'sync' actions: Push image to PrestaShop
     * - 'unsync' actions: Delete image from PrestaShop
     *
     * Called synchronously during product sync to ensure images are synced
     * TOGETHER with product data (not as separate Job).
     *
     * Respects checkbox assignments from GalleryTab.
     *
     * @param Product $product PPM Product
     * @param PrestaShopShop $shop Target shop
     * @param int $externalId PrestaShop product ID
     * @param array $pendingMediaChanges Pending media changes passed from job (2025-12-02)
     */
    protected function syncMediaIfEnabled(Product $product, PrestaShopShop $shop, int $externalId, array $pendingMediaChanges = []): void
    {
        try {
            // Check if auto-sync is enabled in SystemSettings
            $autoSyncEnabled = \App\Models\SystemSetting::get('media.auto_sync_on_product_sync', false);

            if (!$autoSyncEnabled) {
                Log::debug('[MEDIA SYNC] Auto-sync disabled, skipping media push', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                ]);
                return;
            }

            // ETAP_07d (2025-12-02): Use pendingMediaChanges from job parameter
            // Session is NOT available in queue context!
            $pendingChanges = $pendingMediaChanges;

            Log::info('[MEDIA SYNC] Processing media sync for shop (REPLACE ALL STRATEGY)', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
                'pending_changes_count' => count($pendingChanges),
            ]);

            // Filter pending changes for THIS shop only
            $shopChanges = [];
            foreach ($pendingChanges as $key => $action) {
                [$mediaId, $changeShopId] = explode(':', $key);
                if ((int) $changeShopId === $shop->id) {
                    $shopChanges[(int) $mediaId] = $action;
                }
            }

            // FIX 2026-02-05: Always use REPLACE ALL strategy even without pending changes
            // Per user request: "zawsze uploaduj zdjecia z PPM aby uniknac bledow z istniejacymi zdjeciami"
            // This ensures images are always fresh from PPM, avoiding mapping mismatch issues
            if (empty($shopChanges)) {
                Log::info('[MEDIA SYNC] No pending checkbox changes, using REPLACE ALL for currently assigned media', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                ]);

                // Build shopChanges from currently mapped media (treat as 'sync' action)
                // This will cause REPLACE ALL to delete and re-upload all assigned images
                $storeKey = "store_{$shop->id}";
                $allMedia = \App\Models\Media::where('mediable_type', Product::class)
                    ->where('mediable_id', $product->id)
                    ->active()
                    ->get();

                foreach ($allMedia as $media) {
                    $mapping = $media->prestashop_mapping[$storeKey] ?? [];
                    if (!empty($mapping['ps_image_id'])) {
                        // Media is currently synced - include it for re-upload
                        $shopChanges[$media->id] = 'sync';
                    }
                }

                if (empty($shopChanges)) {
                    Log::debug('[MEDIA SYNC] No media assigned to shop, nothing to sync', [
                        'product_id' => $product->id,
                        'shop_id' => $shop->id,
                    ]);
                    return;
                }

                Log::info('[MEDIA SYNC] Built shopChanges from existing mappings for REPLACE ALL', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                    'media_count' => count($shopChanges),
                ]);
            }

            Log::info('[MEDIA SYNC] Shop-specific changes found', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'changes' => $shopChanges,
            ]);

            // =================================================================
            // REPLACE ALL STRATEGY (ETAP_07d)
            // - Delete ALL images from PrestaShop
            // - Upload ONLY the selected images
            // - Set correct cover image based on is_primary
            // =================================================================

            // Get ALL media for this product
            $allMedia = \App\Models\Media::where('mediable_type', Product::class)
                ->where('mediable_id', $product->id)
                ->active()
                ->orderBy('is_primary', 'desc')
                ->orderBy('sort_order', 'asc')
                ->get();

            // Calculate FINAL desired state: which media SHOULD be on PrestaShop
            // Logic:
            // - If has 'sync' action → INCLUDE (user checked the checkbox)
            // - If has 'unsync' action → EXCLUDE (user unchecked the checkbox)
            // - If no action → keep current state (synced = include, not synced = exclude)
            $storeKey = "store_{$shop->id}";
            $selectedMedia = $allMedia->filter(function($media) use ($storeKey, $shopChanges) {
                $mediaId = $media->id;
                $mapping = $media->prestashop_mapping[$storeKey] ?? [];
                $isCurrentlySynced = !empty($mapping['ps_image_id']);

                if (isset($shopChanges[$mediaId])) {
                    // Explicit user action - respect it
                    return $shopChanges[$mediaId] === 'sync';
                }

                // No change - keep if currently synced
                return $isCurrentlySynced;
            });

            Log::info('[MEDIA SYNC] Calculated final desired state', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'total_media' => $allMedia->count(),
                'selected_count' => $selectedMedia->count(),
                'selected_ids' => $selectedMedia->pluck('id')->toArray(),
            ]);

            // Get MediaSyncService
            $syncService = app(\App\Services\Media\MediaSyncService::class);

            // Execute REPLACE ALL strategy
            $result = $syncService->replaceAllImages($product, $shop, $selectedMedia);

            Log::info('[MEDIA SYNC] REPLACE ALL strategy completed', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'deleted' => $result['deleted'],
                'uploaded' => $result['uploaded'],
                'errors_count' => count($result['errors']),
                'cover_set' => $result['cover_set'],
            ]);

            // ETAP_07d (2025-12-02): Session cleanup moved to ProductForm::dispatchSyncJobsForAllShops()
            // Session is not available in queue context, so we don't try to update it here

            Log::info('[MEDIA SYNC] Media sync completed for shop', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'strategy' => 'replace_all',
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            // Log error but don't fail product sync
            Log::error('[MEDIA SYNC] Failed during media sync (non-fatal)', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sync cover image to PrestaShop if PPM primary differs from PS cover
     *
     * ETAP_07d (2025-12-02): Called when no pending checkbox changes exist
     * but cover image may have changed (user clicked "Ustaw jako glowne")
     *
     * @param Product $product PPM Product
     * @param PrestaShopShop $shop Target shop
     * @param int $psProductId PrestaShop product ID
     */
    protected function syncCoverImageIfNeeded(Product $product, PrestaShopShop $shop, int $psProductId): void
    {
        try {
            // Get primary media from PPM
            $primaryMedia = \App\Models\Media::where('mediable_type', Product::class)
                ->where('mediable_id', $product->id)
                ->where('is_primary', true)
                ->active()
                ->first();

            if (!$primaryMedia) {
                Log::debug('[MEDIA SYNC] No primary media in PPM, skipping cover sync', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                ]);
                return;
            }

            // Check if primary media is mapped to PrestaShop
            $storeKey = "store_{$shop->id}";
            $mapping = $primaryMedia->prestashop_mapping[$storeKey] ?? [];
            $psImageId = $mapping['ps_image_id'] ?? null;

            if (!$psImageId) {
                Log::debug('[MEDIA SYNC] Primary media not mapped to PrestaShop, skipping cover sync', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                    'media_id' => $primaryMedia->id,
                ]);
                return;
            }

            // Check if already set as cover
            $isCover = $mapping['is_cover'] ?? false;

            Log::info('[MEDIA SYNC] Checking cover image sync', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'media_id' => $primaryMedia->id,
                'ps_image_id' => $psImageId,
                'is_cover_in_mapping' => $isCover,
            ]);

            // Always try to set cover - PrestaShop API will handle if already set
            $client = new \App\Services\PrestaShop\PrestaShop8Client($shop);
            $coverSet = $client->setProductImageCover($psProductId, (int) $psImageId);

            // Update mapping
            if ($coverSet) {
                $primaryMedia->setPrestaShopMapping($shop->id, array_merge($mapping, [
                    'is_cover' => true,
                    'cover_synced_at' => now()->toIso8601String(),
                ]));

                Log::info('[MEDIA SYNC] Cover image synced successfully', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                    'media_id' => $primaryMedia->id,
                    'ps_image_id' => $psImageId,
                ]);
            } else {
                Log::warning('[MEDIA SYNC] Failed to set cover image', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                    'media_id' => $primaryMedia->id,
                    'ps_image_id' => $psImageId,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('[MEDIA SYNC] Cover image sync failed (non-fatal)', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sync product features to PrestaShop if enabled (ETAP_07e FAZA 4.4)
     *
     * Synchronizes product features (technical specifications) to PrestaShop:
     * - Maps PPM feature types to PrestaShop features
     * - Creates/updates feature values with multilang support
     * - Updates product associations with feature values
     *
     * Called synchronously during product sync for consistency.
     * Non-blocking: errors are logged but don't fail the product sync.
     *
     * @param Product $product PPM Product
     * @param PrestaShopShop $shop Target shop
     * @param int $externalId PrestaShop product ID
     * @param BasePrestaShopClient $client PrestaShop API client
     */
    protected function syncFeaturesIfEnabled(Product $product, PrestaShopShop $shop, int $externalId, BasePrestaShopClient $client): void
    {
        try {
            // Check if feature sync is enabled in SystemSettings
            $featureSyncEnabled = \App\Models\SystemSetting::get('features.auto_sync_on_product_sync', true);

            if (!$featureSyncEnabled) {
                Log::debug('[FEATURE SYNC] Auto-sync disabled, skipping feature push', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                ]);
                return;
            }

            // Load product features
            $productFeatures = $product->productFeatures()
                ->with('featureType')
                ->get();

            if ($productFeatures->isEmpty()) {
                Log::debug('[FEATURE SYNC] No features to sync for product', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                ]);
                return;
            }

            Log::info('[FEATURE SYNC] Starting feature sync for product', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
                'features_count' => $productFeatures->count(),
            ]);

            // Create PrestaShopFeatureSyncService with EXISTING client
            // CRITICAL FIX: Don't use app() DI - it creates new client with empty shop!
            $transformer = new \App\Services\PrestaShop\Transformers\FeatureTransformer();
            $featureSyncService = new \App\Services\PrestaShop\PrestaShopFeatureSyncService($client, $transformer);

            // Sync product features
            $result = $featureSyncService->syncProductFeatures($product, $shop, $externalId);

            Log::info('[FEATURE SYNC] Feature sync completed', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'success' => empty($result['errors']),
                'synced_count' => $result['synced'] ?? 0,
                'skipped_count' => $result['skipped'] ?? 0,
                'errors_count' => count($result['errors'] ?? []),
            ]);

        } catch (\Exception $e) {
            // Log error but don't fail product sync (non-blocking)
            Log::error('[FEATURE SYNC] Failed during feature sync (non-fatal)', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Sync vehicle compatibilities to PrestaShop if enabled (ETAP_05d FAZA 4.5)
     *
     * Synchronizes vehicle compatibility data to PrestaShop features:
     * - Feature 431 (Oryginal): Original vehicles
     * - Feature 433 (Zamiennik): Replacement vehicles
     * - Feature 432 (Model): Computed union of Original + Replacement
     *
     * Only applies to spare parts (product_type = 'czesc-zamienna').
     * Non-blocking: errors are logged but don't fail the product sync.
     *
     * @param Product $product PPM Product
     * @param PrestaShopShop $shop Target shop
     * @param int $externalId PrestaShop product ID
     * @param BasePrestaShopClient $client PrestaShop API client
     */
    protected function syncCompatibilitiesIfEnabled(
        Product $product,
        PrestaShopShop $shop,
        int $externalId,
        BasePrestaShopClient $client
    ): void {
        try {
            // Check if compatibility sync is enabled in SystemSettings
            $compatSyncEnabled = \App\Models\SystemSetting::get(
                'compatibility.auto_sync_on_product_sync',
                true
            );

            if (!$compatSyncEnabled) {
                Log::debug('[COMPAT SYNC] Auto-sync disabled, skipping compatibility push', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                ]);
                return;
            }

            // Check if product has any compatibilities (works for any product type)
            $compatCount = \App\Models\VehicleCompatibility::byProduct($product->id)
                ->where(function ($query) use ($shop) {
                    $query->where('shop_id', $shop->id)
                        ->orWhereNull('shop_id');
                })
                ->count();

            if ($compatCount === 0) {
                Log::debug('[COMPAT SYNC] No compatibilities to sync for product', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                ]);
                return;
            }

            Log::info('[COMPAT SYNC] Starting compatibility sync for product', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
                'compat_count' => $compatCount,
            ]);

            // Create VehicleCompatibilitySyncService with client
            $compatService = new \App\Services\PrestaShop\VehicleCompatibilitySyncService();
            $compatService->setClient($client);
            $compatService->setShop($shop);

            // Transform compatibilities to PrestaShop features
            $featureAssociations = $compatService->transformToPrestaShopFeatures(
                $product,
                $shop->id
            );

            if (empty($featureAssociations)) {
                Log::debug('[COMPAT SYNC] No feature associations generated', [
                    'product_id' => $product->id,
                ]);
                return;
            }

            // Get current product data from PrestaShop
            $productData = $client->getProduct($externalId);

            if (!$productData) {
                Log::warning('[COMPAT SYNC] Could not fetch product from PrestaShop', [
                    'product_id' => $product->id,
                    'external_id' => $externalId,
                ]);
                return;
            }

            // Merge compatibility features with existing product_features
            $existingFeatures = $productData['product']['associations']['product_features'] ?? [];

            // Remove old compatibility features (431, 432, 433)
            $compatFeatureIds = [
                \App\Services\PrestaShop\VehicleCompatibilitySyncService::FEATURE_ORYGINAL,
                \App\Services\PrestaShop\VehicleCompatibilitySyncService::FEATURE_MODEL,
                \App\Services\PrestaShop\VehicleCompatibilitySyncService::FEATURE_ZAMIENNIK,
            ];

            $filteredFeatures = array_filter($existingFeatures, function ($f) use ($compatFeatureIds) {
                return !in_array((int) $f['id'], $compatFeatureIds);
            });

            // Add new compatibility features
            $mergedFeatures = array_merge($filteredFeatures, $featureAssociations);

            // GET-MODIFY-PUT Pattern: Modify existing product data and PUT back everything
            // PrestaShop PUT REPLACES entire resource - missing fields = EMPTY values!
            //
            // CRITICAL (2025-12-09): Must transform multilang fields from GET format to PUT format!
            // GET returns: 'name' => ['language' => [['@attributes' => ['id' => 1], 'value' => '...']]]
            // PUT expects: 'name' => [['@attributes' => ['id' => 1], 'value' => '...']]
            // (without 'language' wrapper)

            $updateData = $productData['product'];

            // Update ONLY the associations.product_features
            $updateData['associations']['product_features'] = array_values($mergedFeatures);

            // Remove READONLY fields that PrestaShop doesn't accept in PUT
            $readonlyFields = [
                'manufacturer_name',
                'quantity',          // Managed via /stock_availables
                'cache_is_pack',
                'cache_has_attachments',
                'cache_default_attribute',
                'date_add',
                'date_upd',
                'indexed',
                'position_in_category',
                'type',
                'id_shop_default',
                'pack_stock_type',
            ];

            foreach ($readonlyFields as $field) {
                unset($updateData[$field]);
            }

            // CRITICAL: Transform multilang fields to PUT format
            // PrestaShop GET returns: STRING directly (xmlToArray extracts value)
            // PrestaShop PUT expects: [['id' => 1, 'value' => 'Text']] for arrayToXml
            $multilangFields = [
                'name', 'description', 'description_short', 'link_rewrite',
                'meta_title', 'meta_description', 'meta_keywords',
                'available_now', 'available_later', 'delivery_in_stock', 'delivery_out_stock',
            ];
            $defaultLangId = 1; // Polish language ID

            foreach ($multilangFields as $field) {
                if (isset($updateData[$field])) {
                    $value = $updateData[$field];

                    // If already array with 'language' wrapper, extract it
                    if (is_array($value) && isset($value['language'])) {
                        $value = $value['language'];
                    }

                    // If still array (proper format), leave as is
                    if (is_array($value) && isset($value[0]['id']) && isset($value[0]['value'])) {
                        continue; // Already in correct format
                    }

                    // If string (from GET), convert to proper multilang array format
                    if (is_string($value)) {
                        $updateData[$field] = [
                            ['id' => $defaultLangId, 'value' => $value]
                        ];
                    }
                }
            }

            // Clean associations - remove readonly nested data
            if (isset($updateData['associations']['images'])) {
                // Images are managed via separate /images/products/{id} endpoint
                unset($updateData['associations']['images']);
            }
            if (isset($updateData['associations']['stock_availables'])) {
                // Stock is managed via /stock_availables endpoint
                unset($updateData['associations']['stock_availables']);
            }

            // Ensure ID is set
            $updateData['id'] = $externalId;

            $result = $client->updateProduct($externalId, ['product' => $updateData]);

            Log::info('[COMPAT SYNC] Compatibility sync completed', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'external_id' => $externalId,
                'features_synced' => count($featureAssociations),
                'total_features' => count($mergedFeatures),
            ]);

        } catch (\Exception $e) {
            // Log error but don't fail product sync (non-blocking)
            Log::error('[COMPAT SYNC] Failed during compatibility sync (non-fatal)', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Mark visual description as synced to PrestaShop (ETAP_07f FAZA 8.2)
     *
     * Updates ProductDescription sync tracking fields after successful product sync.
     * This method is called AFTER the product is synced (including description field).
     *
     * The visual description HTML is already included in the product sync via
     * ProductTransformer::getVisualDescription(), this method just marks it as synced.
     *
     * Non-blocking: errors are logged but don't fail the product sync.
     *
     * @param Product $product PPM Product
     * @param PrestaShopShop $shop Target shop
     */
    protected function markVisualDescriptionAsSynced(Product $product, PrestaShopShop $shop): void
    {
        try {
            // Find visual description for this product-shop pair
            $visualDescription = ProductDescription::where('product_id', $product->id)
                ->where('shop_id', $shop->id)
                ->where('sync_to_prestashop', true)
                ->first();

            // No visual description or sync disabled - nothing to mark
            if (!$visualDescription) {
                Log::debug('[VISUAL DESC SYNC] No visual description to mark as synced', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                ]);
                return;
            }

            // Check if there were any changes to sync
            if (!$visualDescription->needsSync()) {
                Log::debug('[VISUAL DESC SYNC] Visual description already synced (no changes)', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                    'last_synced_at' => $visualDescription->last_synced_at,
                ]);
                return;
            }

            // Mark as synced (updates last_synced_at and sync_checksum)
            $visualDescription->markAsSynced();

            Log::info('[VISUAL DESC SYNC] Visual description marked as synced', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'target_field' => $visualDescription->target_field,
                'include_inline_css' => $visualDescription->include_inline_css,
            ]);

        } catch (\Exception $e) {
            // Log error but don't fail product sync (non-blocking)
            Log::error('[VISUAL DESC SYNC] Failed to mark visual description as synced (non-fatal)', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Dispatch variant sync job if product has shop variants (FIX 2026-01-28)
     *
     * PROBLEM SOLVED: Product sync was completing without syncing variants because
     * SyncShopVariantsToPrestaShopJob was only dispatched on manual "commit" action.
     *
     * SOLUTION: Auto-dispatch variant sync after product sync completes if:
     * - Product has variants (is variant master)
     * - Shop has variant overrides for this product
     *
     * Non-blocking: errors are logged but don't fail the product sync.
     *
     * @param Product $product PPM Product
     * @param PrestaShopShop $shop Target shop
     * @param int $externalId PrestaShop product ID
     */
    protected function dispatchVariantSyncIfNeeded(Product $product, PrestaShopShop $shop, int $externalId): void
    {
        try {
            // Check if product is a variant master (has variants)
            if (!$product->isVariantMaster()) {
                Log::debug('[VARIANT SYNC] Product is not a variant master, skipping', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                ]);
                return;
            }

            // Check if product has any shop variant overrides for this shop
            $shopVariantCount = ShopVariant::where('product_id', $product->id)
                ->where('shop_id', $shop->id)
                ->count();

            // Also check default variants that should be synced
            $defaultVariantCount = $product->variants()->count();

            if ($shopVariantCount === 0 && $defaultVariantCount === 0) {
                Log::debug('[VARIANT SYNC] No variants to sync', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                    'shop_variant_count' => $shopVariantCount,
                    'default_variant_count' => $defaultVariantCount,
                ]);
                return;
            }

            Log::info('[VARIANT SYNC] Dispatching variant sync job after product sync', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'prestashop_product_id' => $externalId,
                'shop_variant_count' => $shopVariantCount,
                'default_variant_count' => $defaultVariantCount,
            ]);

            // Dispatch the variant sync job
            SyncShopVariantsToPrestaShopJob::dispatch(
                $product->id,
                $shop->id
            );

            Log::info('[VARIANT SYNC] Variant sync job dispatched successfully', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
            ]);

        } catch (\Exception $e) {
            // Log error but don't fail product sync (non-blocking)
            Log::error('[VARIANT SYNC] Failed to dispatch variant sync job (non-fatal)', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
