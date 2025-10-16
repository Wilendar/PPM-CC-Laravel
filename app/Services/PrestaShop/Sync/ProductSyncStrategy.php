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
     */
    public function __construct(
        private ProductTransformer $transformer,
        private CategoryMapper $categoryMapper,
        private PriceGroupMapper $priceMapper,
        private WarehouseMapper $warehouseMapper
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

        // Validate before sync
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

        // Get or create sync status record
        $syncStatus = $this->getOrCreateSyncStatus($model, $shop);

        // Check if sync needed based on checksum
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

        DB::beginTransaction();

        try {
            // Mark as syncing
            $syncStatus->update([
                'sync_status' => 'syncing',
                'last_sync_at' => now(),
                'error_message' => null,
            ]);

            // Transform product data
            $productData = $this->transformer->transformForPrestaShop($model, $client);

            // Determine if create or update
            $isUpdate = !empty($syncStatus->prestashop_product_id);

            if ($isUpdate) {
                $response = $client->updateProduct($syncStatus->prestashop_product_id, $productData);
                $operation = 'update';
            } else {
                $response = $client->createProduct($productData);
                $operation = 'create';
            }

            // Extract PrestaShop product ID
            $externalId = $this->extractExternalId($response);
            if (!$externalId) {
                throw new PrestaShopAPIException(
                    'Failed to extract product ID from PrestaShop response',
                    0,
                    null,
                    ['response' => $response]
                );
            }

            // Calculate new checksum
            $newChecksum = $this->calculateChecksum($model, $shop);

            // Update sync status to success
            $syncStatus->update([
                'sync_status' => 'synced',
                'prestashop_product_id' => $externalId,
                'last_success_sync_at' => now(),
                'checksum' => $newChecksum,
                'retry_count' => 0,
                'error_message' => null,
            ]);

            // Log successful sync
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

            DB::commit();

            Log::info('Product synced successfully to PrestaShop', [
                'product_id' => $model->id,
                'shop_id' => $shop->id,
                'external_id' => $externalId,
                'operation' => $operation,
            ]);

            return [
                'success' => true,
                'external_id' => $externalId,
                'message' => "Product {$operation}d successfully",
                'checksum' => $newChecksum,
                'operation' => $operation,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            $this->handleSyncError($e, $model, $shop);
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

        // Include categories, prices, stock
        $data['categories'] = $model->categories->pluck('id')->sort()->values()->toArray();

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
}
