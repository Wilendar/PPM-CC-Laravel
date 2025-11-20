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
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ProductShopData;
use App\Models\SyncLog;
use App\Exceptions\PrestaShopAPIException;

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
     */
    public function __construct(
        private ProductTransformer $transformer,
        private CategoryMapper $categoryMapper,
        private PriceGroupMapper $priceMapper,
        private WarehouseMapper $warehouseMapper,
        private PrestaShopPriceExporter $priceExporter
    ) {}

    /**
     * Synchronize product to PrestaShop
     */
    public function syncToPrestaShop(
        Model $model,
        BasePrestaShopClient $client,
        PrestaShopShop $shop
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
        // Include categories, prices, stock
        if ($shopData && $shopData->hasCategoryMappings()) {
            // Extract PrestaShop IDs from mappings (values only)
            $mappings = $shopData->category_mappings['mappings'] ?? [];
            $data['categories'] = collect($mappings)
                ->values() // Get PrestaShop IDs
                ->sort()
                ->values()
                ->toArray();

            Log::debug('[FIX #12] Checksum using Option A mappings', [
                'product_id' => $model->id,
                'shop_id' => $shop->id,
                'prestashop_ids' => $data['categories'],
            ]);
        } else {
            // Fallback: global categories (PPM category IDs)
            $data['categories'] = $model->categories->pluck('id')->sort()->values()->toArray();
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
}
