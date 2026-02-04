<?php

namespace App\Jobs\Scan;

use App\Models\Product;
use App\Models\ProductErpData;
use App\Models\ProductShopData;
use App\Models\ProductScanSession;
use App\Models\ProductScanResult;
use App\Services\Scan\ProductScanService;
use App\Services\Scan\Contracts\ScanSourceInterface;
use App\Exceptions\ScanSourceException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * ScanProductLinksJob
 *
 * ETAP_10: Product Scan System - FAZA 2
 *
 * Finds PPM products WITHOUT link (ProductErpData/ProductShopData) to selected source.
 * Fetches all SKUs from source and matches by SKU.
 * If SKU from PPM exists in source -> matched, else -> unmatched.
 *
 * Features:
 * - ShouldBeUnique: prevents duplicate job execution
 * - Chunked processing (100 products per batch) for 20k+ products
 * - Real-time progress tracking via ProductScanSession
 * - SKU-based matching (PPM-first architecture)
 *
 * @package App\Jobs\Scan
 * @version 1.0.0
 */
class ScanProductLinksJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Job timeout in seconds (1 hour) */
    public int $timeout = 3600;

    /** Number of retry attempts */
    public int $tries = 3;

    /** Backoff strategy (seconds between retries) */
    public array $backoff = [60, 300, 600];

    /** Unique lock duration in seconds */
    public int $uniqueFor = 1800;

    /** Batch size for chunked processing */
    protected int $batchSize = 100;

    /**
     * Create a new job instance.
     *
     * @param int $scanSessionId ProductScanSession ID
     * @param string $sourceType Source type: 'subiekt_gt', 'prestashop', etc.
     * @param int|null $sourceId Source instance ID (erp_connection_id or shop_id)
     */
    public function __construct(
        protected int $scanSessionId,
        protected string $sourceType,
        protected ?int $sourceId = null
    ) {
        $this->onQueue('scan');
    }

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return "scan_links_{$this->scanSessionId}";
    }

    /**
     * Execute the job.
     *
     * @param ProductScanService $scanService
     * @return void
     */
    public function handle(ProductScanService $scanService): void
    {
        $startTime = Carbon::now();

        $session = ProductScanSession::find($this->scanSessionId);
        if (!$session) {
            Log::error('ScanProductLinksJob: Session not found', [
                'session_id' => $this->scanSessionId,
            ]);
            return;
        }

        Log::info('ScanProductLinksJob: Starting', [
            'session_id' => $this->scanSessionId,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
        ]);

        // Mark session as running
        $session->markAsRunning();

        try {
            // Get scan source adapter
            $source = $scanService->getScanSource($this->sourceType, $this->sourceId);

            // Execute scan
            $results = $this->executeScan($session, $source, $scanService);

            // Complete session
            $duration = $startTime->diffInSeconds(Carbon::now());

            $session->markAsCompleted([
                'duration_seconds' => $duration,
                'source_name' => $source->getSourceName(),
                'source_skus_count' => $results['source_skus_count'],
            ]);

            Log::info('ScanProductLinksJob: Completed', [
                'session_id' => $this->scanSessionId,
                'total_scanned' => $results['total_scanned'],
                'matched' => $results['matched'],
                'unmatched' => $results['unmatched'],
                'duration_seconds' => $duration,
            ]);

        } catch (ScanSourceException $e) {
            $this->handleScanFailure($session, $e);
            throw $e;
        } catch (\Exception $e) {
            $this->handleScanFailure($session, $e);
            throw $e;
        }
    }

    /**
     * Execute the scan operation.
     *
     * @param ProductScanSession $session
     * @param ScanSourceInterface $source
     * @param ProductScanService $scanService
     * @return array{total_scanned: int, matched: int, unmatched: int, errors: int, source_skus_count: int}
     */
    protected function executeScan(
        ProductScanSession $session,
        ScanSourceInterface $source,
        ProductScanService $scanService
    ): array {
        $stats = [
            'total_scanned' => 0,
            'matched' => 0,
            'unmatched' => 0,
            'already_linked' => 0,
            'errors' => 0,
            'source_skus_count' => 0,
        ];

        // Step 1: Fetch all SKUs from source
        Log::debug('ScanProductLinksJob: Fetching source SKUs', [
            'session_id' => $session->id,
        ]);

        $sourceSkus = $source->getAllSkus();
        $sourceSkuMap = array_flip($sourceSkus); // For O(1) lookup
        $stats['source_skus_count'] = count($sourceSkus);

        Log::debug('ScanProductLinksJob: Source SKUs fetched', [
            'count' => count($sourceSkus),
        ]);

        // Step 2: Get ALL PPM products (with or without link)
        $ppmProductsQuery = $this->getAllPpmProductsQuery($session);
        $totalProducts = $ppmProductsQuery->count();

        $session->update(['total_scanned' => $totalProducts]);

        Log::debug('ScanProductLinksJob: All PPM products to scan', [
            'count' => $totalProducts,
        ]);

        // Step 3: Process in chunks
        $ppmProductsQuery->chunk($this->batchSize, function ($products) use (
            $session,
            $source,
            $sourceSkuMap,
            &$stats
        ) {
            foreach ($products as $product) {
                try {
                    $result = $this->processProduct($product, $session, $source, $sourceSkuMap);

                    $stats['total_scanned']++;
                    if ($result['already_linked']) {
                        $stats['already_linked']++;
                        $session->incrementMatched(); // Linked = success
                    } elseif ($result['matched']) {
                        $stats['matched']++;
                        $session->incrementMatched();
                    } else {
                        $stats['unmatched']++;
                        $session->incrementUnmatched();
                    }

                } catch (\Exception $e) {
                    $stats['errors']++;
                    $session->incrementErrors();

                    Log::warning('ScanProductLinksJob: Product processing error', [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Update session progress
            $session->refresh();
        });

        return $stats;
    }

    /**
     * Get query for ALL PPM products (with or without link).
     *
     * @param ProductScanSession $session
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getAllPpmProductsQuery(ProductScanSession $session)
    {
        $query = Product::query()
            ->whereNotNull('sku')
            ->where('sku', '!=', '')
            ->select(['id', 'sku', 'name', 'ean', 'manufacturer_id']);

        if ($session->source_type === ProductScanSession::SOURCE_PRESTASHOP) {
            // Load existing links for this shop + ALL links for Powiązania column
            $query->with([
                'shopData' => function ($q) use ($session) {
                    $q->where('shop_id', $session->source_id);
                },
                'manufacturerRelation:id,name',
                'erpData.erpConnection:id,instance_name',  // ALL ERP links
                'shopData.shop:id,name',                   // ALL Shop links (full)
            ]);
        } else {
            // Load existing links for this ERP connection + ALL links for Powiązania column
            $query->with([
                'erpData' => function ($q) use ($session) {
                    $q->where('erp_connection_id', $session->source_id);
                },
                'manufacturerRelation:id,name',
                'erpData.erpConnection:id,instance_name',  // ALL ERP links (full)
                'shopData.shop:id,name',                   // ALL Shop links
            ]);
        }

        return $query;
    }

    /**
     * Process a single product - check if SKU exists in source and if already linked.
     *
     * @param Product $product
     * @param ProductScanSession $session
     * @param ScanSourceInterface $source
     * @param array $sourceSkuMap SKU lookup map
     * @return array{matched: bool, already_linked: bool, result_id: int}
     */
    protected function processProduct(
        Product $product,
        ProductScanSession $session,
        ScanSourceInterface $source,
        array $sourceSkuMap
    ): array {
        $sku = (string) $product->sku;
        $matched = isset($sourceSkuMap[$sku]);

        // Check if product is already linked to this source
        $alreadyLinked = $this->isProductAlreadyLinked($product, $session);
        $existingExternalId = $this->getExistingExternalId($product, $session);

        // Determine match status
        if ($alreadyLinked) {
            $matchStatus = ProductScanResult::MATCH_ALREADY_LINKED;
            $resolutionStatus = ProductScanResult::RESOLUTION_LINKED;
        } elseif ($matched) {
            $matchStatus = ProductScanResult::MATCH_MATCHED;
            $resolutionStatus = ProductScanResult::RESOLUTION_PENDING;
        } else {
            $matchStatus = ProductScanResult::MATCH_UNMATCHED;
            $resolutionStatus = ProductScanResult::RESOLUTION_PENDING;
        }

        // Get existing links data for "Powiązania" column
        $linksData = $this->getProductLinksData($product);

        // Prepare result data
        $resultData = [
            'scan_session_id' => $session->id,
            'sku' => $sku,
            'name' => $product->name,
            'ppm_product_id' => $product->id,
            'external_source_type' => $this->sourceType,
            'external_source_id' => $this->sourceId,
            'external_id' => $existingExternalId,
            'match_status' => $matchStatus,
            'resolution_status' => $resolutionStatus,
            'ppm_data' => [
                'id' => $product->id,
                'sku' => $sku,
                'name' => $product->name,
                'ean' => $product->ean,
                'manufacturer' => $product->manufacturerRelation->name ?? null,
                'links' => $linksData,
            ],
        ];

        // If matched or already linked, fetch full product data from source for diff
        if ($matched || $alreadyLinked) {
            try {
                $sourceProduct = $source->getProductBySku($sku);
                if ($sourceProduct) {
                    $resultData['external_id'] = $sourceProduct['external_id'] ?? $existingExternalId;
                    $resultData['source_data'] = $sourceProduct;

                    // Calculate diff (only for non-linked products)
                    if (!$alreadyLinked) {
                        $diff = $this->calculateDiff($resultData['ppm_data'], $sourceProduct);
                        if (!empty($diff['fields'])) {
                            $resultData['diff_data'] = $diff;
                            // Mark as conflict if there are differences
                            $resultData['match_status'] = ProductScanResult::MATCH_CONFLICT;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::debug('ScanProductLinksJob: Could not fetch source product details', [
                    'sku' => $sku,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Create scan result
        $result = ProductScanResult::create($resultData);

        return [
            'matched' => $matched,
            'already_linked' => $alreadyLinked,
            'result_id' => $result->id,
        ];
    }

    /**
     * Check if product is already linked to the source.
     *
     * @param Product $product
     * @param ProductScanSession $session
     * @return bool
     */
    protected function isProductAlreadyLinked(Product $product, ProductScanSession $session): bool
    {
        if ($session->source_type === ProductScanSession::SOURCE_PRESTASHOP) {
            return $product->shopData->isNotEmpty();
        }

        return $product->erpData->isNotEmpty();
    }

    /**
     * Get existing external ID from link data.
     *
     * @param Product $product
     * @param ProductScanSession $session
     * @return string|null
     */
    protected function getExistingExternalId(Product $product, ProductScanSession $session): ?string
    {
        if ($session->source_type === ProductScanSession::SOURCE_PRESTASHOP) {
            $link = $product->shopData->first();
            return $link?->external_id;
        }

        $link = $product->erpData->first();
        return $link?->external_id;
    }

    /**
     * Calculate differences between PPM and source data.
     *
     * @param array $ppmData
     * @param array $sourceData
     * @return array
     */
    protected function calculateDiff(array $ppmData, array $sourceData): array
    {
        $diff = ['fields' => []];
        $fieldsToCompare = ['name', 'ean', 'manufacturer'];

        foreach ($fieldsToCompare as $field) {
            $ppmValue = $ppmData[$field] ?? null;
            $sourceValue = $sourceData[$field] ?? null;

            // Normalize for comparison
            $ppmNormalized = is_string($ppmValue) ? trim($ppmValue) : $ppmValue;
            $sourceNormalized = is_string($sourceValue) ? trim($sourceValue) : $sourceValue;

            if ($ppmNormalized !== $sourceNormalized && ($ppmNormalized || $sourceNormalized)) {
                $diff['fields'][$field] = [
                    'ppm' => $ppmValue,
                    'source' => $sourceValue,
                ];
            }
        }

        return $diff;
    }

    /**
     * Get ALL link data (ERP and Shop) for product.
     * Used to display "Powiązania" column with custom label colors.
     *
     * @param Product $product
     * @return array{erp: array, shops: array}
     */
    protected function getProductLinksData(Product $product): array
    {
        $links = [
            'erp' => [],
            'shops' => [],
        ];

        // Get ERP links (need to reload full collection for all connections)
        $allErpData = $product->erpData()->with('erpConnection:id,instance_name,erp_type,label_color,label_icon')->get();
        foreach ($allErpData as $erpData) {
            $links['erp'][] = [
                'connection_id' => $erpData->erp_connection_id,
                'connection_name' => $erpData->erpConnection->instance_name ?? 'ERP #' . $erpData->erp_connection_id,
                'external_id' => $erpData->external_id,
                'label_color' => $erpData->erpConnection->label_color ?? null,
                'label_icon' => $erpData->erpConnection->label_icon ?? null,
            ];
        }

        // Get Shop links (need to reload full collection for all shops)
        $allShopData = $product->shopData()->with('shop:id,name,label_color,label_icon')->get();
        foreach ($allShopData as $shopData) {
            $links['shops'][] = [
                'shop_id' => $shopData->shop_id,
                'shop_name' => $shopData->shop->name ?? 'Shop #' . $shopData->shop_id,
                'external_id' => $shopData->external_id,
                'label_color' => $shopData->shop->label_color ?? null,
                'label_icon' => $shopData->shop->label_icon ?? null,
            ];
        }

        return $links;
    }

    /**
     * Handle scan failure.
     *
     * @param ProductScanSession $session
     * @param \Throwable $exception
     */
    protected function handleScanFailure(ProductScanSession $session, \Throwable $exception): void
    {
        Log::error('ScanProductLinksJob: Failed', [
            'session_id' => $this->scanSessionId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $session->markAsFailed($exception->getMessage());
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ScanProductLinksJob: Permanently failed', [
            'session_id' => $this->scanSessionId,
            'error' => $exception->getMessage(),
        ]);

        $session = ProductScanSession::find($this->scanSessionId);
        if ($session) {
            $session->markAsFailed('Job permanently failed: ' . $exception->getMessage());
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'scan',
            'scan-links',
            'session:' . $this->scanSessionId,
            'source:' . $this->sourceType,
        ];
    }
}
