<?php

namespace App\Services\Scan;

use App\Models\Product;
use App\Models\ProductErpData;
use App\Models\PendingProduct;
use App\Models\ProductScanSession;
use App\Models\ProductScanResult;
use App\Models\ERPConnection;
use App\Services\Scan\Contracts\ScanSourceInterface;
use App\Services\Scan\Sources\SubiektGTScanSource;
use App\Services\Scan\Sources\PrestaShopScanSource;
use App\Services\Scan\Sources\BaselinkerScanSource;
use App\Services\Scan\Sources\DynamicsScanSource;
use App\Exceptions\ScanSourceException;
use App\Models\PrestaShopShop;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * ProductScanService
 *
 * Main service for product scanning and comparison operations.
 * Coordinates between PPM database and external sources (ERP, PrestaShop).
 *
 * Features:
 * - SKU-based product matching
 * - Diff generation for data comparison
 * - Bulk operations (link, create, ignore)
 * - Session management for scan tracking
 *
 * @package App\Services\Scan
 * @version 1.0.0
 */
class ProductScanService
{
    /**
     * Create a new scan session.
     *
     * @param string $scanType Type of scan: 'links', 'missing_in_ppm', 'missing_in_source'
     * @param string $sourceType Source type: 'subiekt_gt', 'prestashop', 'baselinker'
     * @param int|null $sourceId Source instance ID (erp_connection_id or shop_id)
     * @param int|null $userId User who initiated the scan
     * @return ProductScanSession
     */
    public function createScanSession(
        string $scanType,
        string $sourceType,
        ?int $sourceId,
        ?int $userId
    ): ProductScanSession {
        Log::info('ProductScanService::createScanSession', [
            'scan_type' => $scanType,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'user_id' => $userId,
        ]);

        return ProductScanSession::create([
            'scan_type' => $scanType,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'user_id' => $userId,
            'status' => ProductScanSession::STATUS_PENDING,
        ]);
    }

    /**
     * Start a scan session (mark as running).
     *
     * @param ProductScanSession $session
     * @return void
     */
    public function startScan(ProductScanSession $session): void
    {
        $session->update([
            'status' => ProductScanSession::STATUS_RUNNING,
            'started_at' => Carbon::now(),
        ]);

        Log::info('ProductScanService::startScan', [
            'session_id' => $session->id,
            'scan_type' => $session->scan_type,
        ]);
    }

    /**
     * Get scan source adapter by type and ID.
     *
     * @param string $sourceType Source type identifier
     * @param int|null $sourceId Source instance ID
     * @return ScanSourceInterface
     * @throws ScanSourceException When source is not found or invalid
     */
    public function getScanSource(string $sourceType, ?int $sourceId): ScanSourceInterface
    {
        return match ($sourceType) {
            ERPConnection::ERP_SUBIEKT_GT => $this->createSubiektGTSource($sourceId),
            ERPConnection::ERP_BASELINKER => $this->createBaselinkerSource($sourceId),
            ERPConnection::ERP_DYNAMICS => $this->createDynamicsSource($sourceId),
            'prestashop' => $this->createPrestaShopSource($sourceId),
            default => throw new ScanSourceException("Unsupported source type: {$sourceType}"),
        };
    }

    /**
     * Create Subiekt GT scan source adapter.
     *
     * @param int|null $connectionId ERP connection ID
     * @return SubiektGTScanSource
     * @throws ScanSourceException
     */
    protected function createSubiektGTSource(?int $connectionId): SubiektGTScanSource
    {
        $connection = $connectionId
            ? ERPConnection::find($connectionId)
            : ERPConnection::subiektGT()->active()->first();

        if (!$connection) {
            throw new ScanSourceException(
                $connectionId
                    ? "Subiekt GT connection with ID {$connectionId} not found"
                    : "No active Subiekt GT connection found"
            );
        }

        return new SubiektGTScanSource($connection);
    }

    /**
     * Create PrestaShop scan source adapter.
     *
     * @param int|null $shopId PrestaShop shop ID
     * @return PrestaShopScanSource
     * @throws ScanSourceException
     */
    protected function createPrestaShopSource(?int $shopId): PrestaShopScanSource
    {
        $shop = $shopId
            ? PrestaShopShop::find($shopId)
            : PrestaShopShop::where('is_active', true)->first();

        if (!$shop) {
            throw new ScanSourceException(
                $shopId
                    ? "PrestaShop shop with ID {$shopId} not found"
                    : "No active PrestaShop shop found"
            );
        }

        return new PrestaShopScanSource($shop);
    }

    /**
     * Create Baselinker scan source adapter.
     *
     * @param int|null $connectionId ERP connection ID
     * @return BaselinkerScanSource
     * @throws ScanSourceException
     */
    protected function createBaselinkerSource(?int $connectionId): BaselinkerScanSource
    {
        $connection = $connectionId
            ? ERPConnection::find($connectionId)
            : ERPConnection::baselinker()->active()->first();

        if (!$connection) {
            throw new ScanSourceException(
                $connectionId
                    ? "Baselinker connection with ID {$connectionId} not found"
                    : "No active Baselinker connection found"
            );
        }

        return new BaselinkerScanSource($connection);
    }

    /**
     * Create Microsoft Dynamics scan source adapter.
     *
     * @param int|null $connectionId ERP connection ID
     * @return DynamicsScanSource
     * @throws ScanSourceException
     */
    protected function createDynamicsSource(?int $connectionId): DynamicsScanSource
    {
        $connection = $connectionId
            ? ERPConnection::find($connectionId)
            : ERPConnection::dynamics()->active()->first();

        if (!$connection) {
            throw new ScanSourceException(
                $connectionId
                    ? "Dynamics connection with ID {$connectionId} not found"
                    : "No active Microsoft Dynamics connection found"
            );
        }

        return new DynamicsScanSource($connection);
    }

    /**
     * Match product by SKU in the source.
     *
     * @param string $sku SKU to search for
     * @param ScanSourceInterface $source Source adapter
     * @return array|null Normalized product data or null if not found
     */
    public function matchBySku(string $sku, ScanSourceInterface $source): ?array
    {
        try {
            return $source->getProductBySku($sku);
        } catch (ScanSourceException $e) {
            Log::warning('ProductScanService::matchBySku - Error', [
                'sku' => $sku,
                'source_type' => $source->getSourceType(),
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Calculate differences between PPM and source data.
     *
     * @param array $ppmData PPM product data
     * @param array $sourceData Source product data (normalized)
     * @return array Diff data with fields and values
     */
    public function calculateDiff(array $ppmData, array $sourceData): array
    {
        $diff = [
            'has_differences' => false,
            'fields' => [],
        ];

        $fieldsToCompare = [
            'name',
            'sku',
            'ean',
            'description',
            'price_net',
            'price_gross',
            'stock',
            'weight',
            'manufacturer',
            'is_active',
        ];

        foreach ($fieldsToCompare as $field) {
            $ppmValue = $ppmData[$field] ?? null;
            $sourceValue = $sourceData[$field] ?? null;

            // Normalize values for comparison
            $ppmNormalized = $this->normalizeValueForComparison($ppmValue);
            $sourceNormalized = $this->normalizeValueForComparison($sourceValue);

            if ($ppmNormalized !== $sourceNormalized) {
                $diff['has_differences'] = true;
                $diff['fields'][$field] = [
                    'ppm' => $ppmValue,
                    'source' => $sourceValue,
                ];
            }
        }

        return $diff;
    }

    /**
     * Normalize value for comparison (handle nulls, floats, etc.).
     *
     * @param mixed $value
     * @return mixed
     */
    protected function normalizeValueForComparison($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_float($value)) {
            return round($value, 2);
        }

        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }

    /**
     * Bulk link scan results to PPM products.
     *
     * Creates ProductErpData records for matched products.
     *
     * @param array $resultIds Array of ProductScanResult IDs
     * @param int $userId User performing the action
     * @return array{linked: int, errors: array}
     */
    public function bulkLink(array $resultIds, int $userId): array
    {
        $linked = 0;
        $errors = [];

        Log::info('ProductScanService::bulkLink - Starting', [
            'result_count' => count($resultIds),
            'user_id' => $userId,
        ]);

        DB::beginTransaction();

        try {
            $results = ProductScanResult::whereIn('id', $resultIds)
                ->where('match_status', ProductScanResult::MATCH_STATUS_MATCHED)
                ->where('resolution_status', ProductScanResult::RESOLUTION_PENDING)
                ->get();

            foreach ($results as $result) {
                try {
                    $this->linkSingleResult($result, $userId);
                    $linked++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'result_id' => $result->id,
                        'sku' => $result->sku,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            Log::info('ProductScanService::bulkLink - Completed', [
                'linked' => $linked,
                'errors_count' => count($errors),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProductScanService::bulkLink - Transaction failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return [
            'linked' => $linked,
            'errors' => $errors,
        ];
    }

    /**
     * Link a single scan result to PPM product.
     *
     * @param ProductScanResult $result
     * @param int $userId
     * @return ProductErpData
     */
    protected function linkSingleResult(ProductScanResult $result, int $userId): ProductErpData
    {
        if (!$result->ppm_product_id) {
            throw new \InvalidArgumentException('Result has no PPM product ID');
        }

        $session = $result->scanSession;
        $sourceData = $result->source_data ?? [];

        // Create or update ProductErpData
        $erpData = ProductErpData::updateOrCreate(
            [
                'product_id' => $result->ppm_product_id,
                'erp_connection_id' => $session->source_id,
            ],
            [
                'external_id' => $result->external_id,
                'sku' => $result->sku,
                'name' => $sourceData['name'] ?? null,
                'sync_status' => ProductErpData::STATUS_SYNCED,
                'last_sync_at' => Carbon::now(),
                'external_data' => $sourceData,
            ]
        );

        // Update result status
        $result->update([
            'resolution_status' => ProductScanResult::RESOLUTION_LINKED,
            'resolved_at' => Carbon::now(),
            'resolved_by' => $userId,
        ]);

        return $erpData;
    }

    /**
     * Bulk create PendingProducts from scan results.
     *
     * @param array $resultIds Array of ProductScanResult IDs
     * @param int $userId User performing the action
     * @return array{created: int, errors: array}
     */
    public function bulkCreatePendingProducts(array $resultIds, int $userId): array
    {
        $created = 0;
        $errors = [];

        Log::info('ProductScanService::bulkCreatePendingProducts - Starting', [
            'result_count' => count($resultIds),
            'user_id' => $userId,
        ]);

        DB::beginTransaction();

        try {
            $results = ProductScanResult::whereIn('id', $resultIds)
                ->where('match_status', ProductScanResult::MATCH_STATUS_UNMATCHED)
                ->where('resolution_status', ProductScanResult::RESOLUTION_PENDING)
                ->get();

            foreach ($results as $result) {
                try {
                    $this->createPendingProductFromResult($result, $userId);
                    $created++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'result_id' => $result->id,
                        'sku' => $result->sku,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            Log::info('ProductScanService::bulkCreatePendingProducts - Completed', [
                'created' => $created,
                'errors_count' => count($errors),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProductScanService::bulkCreatePendingProducts - Transaction failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return [
            'created' => $created,
            'errors' => $errors,
        ];
    }

    /**
     * Create PendingProduct from scan result.
     *
     * @param ProductScanResult $result
     * @param int $userId
     * @return PendingProduct
     */
    protected function createPendingProductFromResult(
        ProductScanResult $result,
        int $userId
    ): PendingProduct {
        $sourceData = $result->source_data ?? [];

        $pendingProduct = PendingProduct::create([
            'sku' => $result->sku ?? $sourceData['sku'] ?? null,
            'name' => $result->name ?? $sourceData['name'] ?? null,
            'ean' => $sourceData['ean'] ?? null,
            'manufacturer' => $sourceData['manufacturer'] ?? null,
            'weight' => $sourceData['weight'] ?? null,
            'base_price' => $sourceData['price_net'] ?? null,
            'short_description' => $sourceData['description'] ?? null,
            'tax_rate' => $sourceData['vat_rate'] ?? 23.0,
            'imported_by' => $userId,
            'imported_at' => Carbon::now(),
            'publish_status' => PendingProduct::PUBLISH_STATUS_DRAFT,
        ]);

        // Update result status
        $result->update([
            'resolution_status' => ProductScanResult::RESOLUTION_CREATED,
            'resolved_at' => Carbon::now(),
            'resolved_by' => $userId,
        ]);

        return $pendingProduct;
    }

    /**
     * Bulk ignore scan results.
     *
     * @param array $resultIds Array of ProductScanResult IDs
     * @param int $userId User performing the action
     * @return array{ignored: int, errors: array}
     */
    public function bulkIgnore(array $resultIds, int $userId): array
    {
        $ignored = 0;
        $errors = [];

        Log::info('ProductScanService::bulkIgnore - Starting', [
            'result_count' => count($resultIds),
            'user_id' => $userId,
        ]);

        try {
            $updated = ProductScanResult::whereIn('id', $resultIds)
                ->where('resolution_status', ProductScanResult::RESOLUTION_PENDING)
                ->update([
                    'resolution_status' => ProductScanResult::RESOLUTION_IGNORED,
                    'resolved_at' => Carbon::now(),
                    'resolved_by' => $userId,
                ]);

            $ignored = $updated;

            Log::info('ProductScanService::bulkIgnore - Completed', [
                'ignored' => $ignored,
            ]);

        } catch (\Exception $e) {
            Log::error('ProductScanService::bulkIgnore - Failed', [
                'error' => $e->getMessage(),
            ]);
            $errors[] = ['error' => $e->getMessage()];
        }

        return [
            'ignored' => $ignored,
            'errors' => $errors,
        ];
    }

    /**
     * Get PPM products without ERP link for given connection.
     *
     * @param int $erpConnectionId ERP connection ID
     * @param int $page Page number (1-based)
     * @param int $perPage Items per page
     * @return array{data: array, total: int, page: int, per_page: int}
     */
    public function getPpmProductsWithoutErpLink(
        int $erpConnectionId,
        int $page = 1,
        int $perPage = 100
    ): array {
        $query = Product::whereDoesntHave('erpData', function ($q) use ($erpConnectionId) {
            $q->where('erp_connection_id', $erpConnectionId);
        });

        $total = $query->count();

        $products = $query
            ->select(['id', 'sku', 'name', 'ean', 'manufacturer_id'])
            ->with('manufacturer:id,name')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $data = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'ean' => $product->ean,
                'manufacturer' => $product->manufacturer->name ?? null,
            ];
        })->toArray();

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * Get all PPM SKUs.
     *
     * @return array<string>
     */
    public function getAllPpmSkus(): array
    {
        return Product::whereNotNull('sku')
            ->where('sku', '!=', '')
            ->pluck('sku')
            ->map(fn($sku) => (string) $sku)
            ->toArray();
    }

    /**
     * Find PPM product by SKU.
     *
     * @param string $sku
     * @return Product|null
     */
    public function findPpmProductBySku(string $sku): ?Product
    {
        return Product::where('sku', $sku)->first();
    }

    /**
     * Complete a scan session.
     *
     * @param ProductScanSession $session
     * @param int $totalScanned
     * @param int $matchedCount
     * @param int $unmatchedCount
     * @param int $errorsCount
     * @param array|null $resultSummary
     * @return void
     */
    public function completeScan(
        ProductScanSession $session,
        int $totalScanned,
        int $matchedCount,
        int $unmatchedCount,
        int $errorsCount = 0,
        ?array $resultSummary = null
    ): void {
        $session->update([
            'status' => ProductScanSession::STATUS_COMPLETED,
            'completed_at' => Carbon::now(),
            'total_scanned' => $totalScanned,
            'matched_count' => $matchedCount,
            'unmatched_count' => $unmatchedCount,
            'errors_count' => $errorsCount,
            'result_summary' => $resultSummary,
        ]);

        Log::info('ProductScanService::completeScan', [
            'session_id' => $session->id,
            'total_scanned' => $totalScanned,
            'matched_count' => $matchedCount,
            'unmatched_count' => $unmatchedCount,
        ]);
    }

    /**
     * Fail a scan session.
     *
     * @param ProductScanSession $session
     * @param string $errorMessage
     * @return void
     */
    public function failScan(ProductScanSession $session, string $errorMessage): void
    {
        $session->update([
            'status' => ProductScanSession::STATUS_FAILED,
            'completed_at' => Carbon::now(),
            'error_message' => $errorMessage,
        ]);

        Log::error('ProductScanService::failScan', [
            'session_id' => $session->id,
            'error_message' => $errorMessage,
        ]);
    }
}
