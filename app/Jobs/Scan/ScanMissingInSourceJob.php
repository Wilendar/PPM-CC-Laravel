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
use Carbon\Carbon;

/**
 * ScanMissingInSourceJob
 *
 * ETAP_10: Product Scan System - FAZA 2
 *
 * Finds PPM products that DO NOT exist in the source system.
 * These are products that can be published/exported to the source.
 *
 * Features:
 * - ShouldBeUnique: prevents duplicate job execution
 * - Chunked processing for large datasets (20k+ products)
 * - Detects products missing in source (for publication)
 * - Real-time progress tracking via ProductScanSession
 * - Results can be used to publish products to source
 *
 * @package App\Jobs\Scan
 * @version 2.0.0
 */
class ScanMissingInSourceJob implements ShouldQueue, ShouldBeUnique
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
        return "scan_missing_source_{$this->scanSessionId}";
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
            Log::error('ScanMissingInSourceJob: Session not found', [
                'session_id' => $this->scanSessionId,
            ]);
            return;
        }

        Log::info('ScanMissingInSourceJob: Starting', [
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
                'ppm_products_count' => $results['ppm_products_count'],
            ]);

            Log::info('ScanMissingInSourceJob: Completed', [
                'session_id' => $this->scanSessionId,
                'total_scanned' => $results['total_scanned'],
                'matched' => $results['matched'],
                'orphans' => $results['unmatched'],
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
     * @return array{total_scanned: int, matched: int, unmatched: int, errors: int, source_skus_count: int, ppm_products_count: int}
     */
    protected function executeScan(
        ProductScanSession $session,
        ScanSourceInterface $source,
        ProductScanService $scanService
    ): array {
        $stats = [
            'total_scanned' => 0,
            'matched' => 0,
            'unmatched' => 0, // missing in source - for publication
            'errors' => 0,
            'source_skus_count' => 0,
            'ppm_products_count' => 0,
        ];

        // Step 1: Fetch all SKUs from source
        Log::debug('ScanMissingInSourceJob: Fetching source SKUs', [
            'session_id' => $session->id,
        ]);

        $sourceSkus = $source->getAllSkus();
        $sourceSkuMap = array_flip($sourceSkus); // For O(1) lookup
        $stats['source_skus_count'] = count($sourceSkus);

        Log::debug('ScanMissingInSourceJob: Source SKUs fetched', [
            'count' => count($sourceSkus),
        ]);

        // Step 2: Get ALL PPM products (not just linked ones)
        $ppmProductsQuery = $this->getPpmProductsQuery($session);
        $totalProducts = $ppmProductsQuery->count();
        $stats['ppm_products_count'] = $totalProducts;

        $session->update(['total_scanned' => $totalProducts]);

        Log::debug('ScanMissingInSourceJob: PPM products count', [
            'count' => $totalProducts,
        ]);

        // Step 3: Process in chunks - check if each PPM product exists in source
        $ppmProductsQuery->chunk($this->batchSize, function ($products) use (
            $session,
            $source,
            $sourceSkuMap,
            &$stats
        ) {
            foreach ($products as $product) {
                try {
                    $result = $this->processPpmProduct($product, $session, $source, $sourceSkuMap);

                    $stats['total_scanned']++;
                    if ($result['exists_in_source']) {
                        $stats['matched']++;
                        $session->incrementMatched();
                    } else {
                        $stats['unmatched']++; // missing - can be published
                        $session->incrementUnmatched();
                    }

                } catch (\Exception $e) {
                    $stats['errors']++;
                    $session->incrementErrors();

                    Log::warning('ScanMissingInSourceJob: Product processing error', [
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
     * Get query for ALL PPM products (for missing in source check).
     *
     * @param ProductScanSession $session
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getPpmProductsQuery(ProductScanSession $session)
    {
        // Get ALL PPM products with valid SKU
        return Product::query()
            ->whereNotNull('sku')
            ->where('sku', '!=', '')
            ->select(['id', 'sku', 'name', 'ean', 'manufacturer_id'])
            ->with([
                'manufacturerRelation:id,name',
                'erpData.erpConnection:id,instance_name,erp_type,label_color,label_icon',  // Load ERP links with label customization
                'shopData.shop:id,name,label_color,label_icon',                             // Load Shop links with label customization
            ]);
    }

    /**
     * Process a single PPM product - check if it exists in source.
     *
     * @param Product $product
     * @param ProductScanSession $session
     * @param ScanSourceInterface $source
     * @param array $sourceSkuMap SKU lookup map
     * @return array{exists_in_source: bool, result_id: int|null}
     */
    protected function processPpmProduct(
        Product $product,
        ProductScanSession $session,
        ScanSourceInterface $source,
        array $sourceSkuMap
    ): array {
        $sku = (string) $product->sku;
        $existsInSource = isset($sourceSkuMap[$sku]);

        // Only create result for products NOT in source (for publication)
        if ($existsInSource) {
            // Product exists in source - no action needed
            return [
                'exists_in_source' => true,
                'result_id' => null,
            ];
        }

        // Get existing links data for "Powiązania" column
        $linksData = $this->getProductLinksData($product);

        // Product missing in source - create result for publication
        $resultData = [
            'scan_session_id' => $session->id,
            'sku' => $sku,
            'name' => $product->name,
            'external_id' => null, // No external ID - product not in source yet
            'ppm_product_id' => $product->id,
            'external_source_type' => $this->sourceType,
            'external_source_id' => $this->sourceId,
            'match_status' => ProductScanResult::MATCH_UNMATCHED, // missing in source
            'resolution_status' => ProductScanResult::RESOLUTION_PENDING,
            'ppm_data' => [
                'id' => $product->id,
                'sku' => $sku,
                'name' => $product->name,
                'ean' => $product->ean,
                'manufacturer' => $product->manufacturerRelation->name ?? null,
                'links' => $linksData, // Include existing links for display
            ],
            'source_data' => null, // No source data - product doesn't exist in source
            'diff_data' => [
                'reason' => 'Product exists in PPM but not found in source - can be published',
                'existing_links' => $linksData,
            ],
        ];

        $result = ProductScanResult::create($resultData);

        Log::debug('ScanMissingInSourceJob: Missing in source detected', [
            'product_id' => $product->id,
            'sku' => $sku,
            'existing_links_count' => count($linksData['erp'] ?? []) + count($linksData['shops'] ?? []),
        ]);

        return [
            'exists_in_source' => false,
            'result_id' => $result->id,
        ];
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

        // Get ERP links with label customization
        foreach ($product->erpData as $erpData) {
            $links['erp'][] = [
                'connection_id' => $erpData->erp_connection_id,
                'connection_name' => $erpData->erpConnection->instance_name ?? 'ERP #' . $erpData->erp_connection_id,
                'external_id' => $erpData->external_id,
                'label_color' => $erpData->erpConnection->label_color ?? null,
                'label_icon' => $erpData->erpConnection->label_icon ?? null,
            ];
        }

        // Get Shop links with label customization
        foreach ($product->shopData as $shopData) {
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
        Log::error('ScanMissingInSourceJob: Failed', [
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
        Log::error('ScanMissingInSourceJob: Permanently failed', [
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
            'scan-missing-source',
            'session:' . $this->scanSessionId,
            'source:' . $this->sourceType,
        ];
    }
}
