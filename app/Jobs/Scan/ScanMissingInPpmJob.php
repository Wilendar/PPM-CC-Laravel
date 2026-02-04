<?php

namespace App\Jobs\Scan;

use App\Models\Product;
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
 * ScanMissingInPpmJob
 *
 * ETAP_10: Product Scan System - FAZA 2
 *
 * Finds products that exist in source (ERP/PrestaShop) but are missing from PPM.
 * Fetches all SKUs from source, compares with PPM SKUs.
 * Difference: source_SKUs - ppm_SKUs = missing in PPM.
 *
 * Features:
 * - ShouldBeUnique: prevents duplicate job execution
 * - Chunked processing for large datasets (20k+ products)
 * - Fetches full product data for missing items
 * - Real-time progress tracking via ProductScanSession
 * - Results can be bulk-imported as PendingProduct
 *
 * @package App\Jobs\Scan
 * @version 1.0.0
 */
class ScanMissingInPpmJob implements ShouldQueue, ShouldBeUnique
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
        return "scan_missing_ppm_{$this->scanSessionId}";
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
            Log::error('ScanMissingInPpmJob: Session not found', [
                'session_id' => $this->scanSessionId,
            ]);
            return;
        }

        Log::info('ScanMissingInPpmJob: Starting', [
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
                'ppm_skus_count' => $results['ppm_skus_count'],
            ]);

            Log::info('ScanMissingInPpmJob: Completed', [
                'session_id' => $this->scanSessionId,
                'total_scanned' => $results['total_scanned'],
                'missing_count' => $results['unmatched'],
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
     * @return array{total_scanned: int, matched: int, unmatched: int, errors: int, source_skus_count: int, ppm_skus_count: int}
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
            'errors' => 0,
            'source_skus_count' => 0,
            'ppm_skus_count' => 0,
        ];

        // Step 1: Fetch all SKUs from source
        Log::debug('ScanMissingInPpmJob: Fetching source SKUs', [
            'session_id' => $session->id,
        ]);

        $sourceSkus = $source->getAllSkus();
        $stats['source_skus_count'] = count($sourceSkus);

        Log::debug('ScanMissingInPpmJob: Source SKUs fetched', [
            'count' => count($sourceSkus),
        ]);

        // Step 2: Get all PPM SKUs
        $ppmSkus = $scanService->getAllPpmSkus();
        $ppmSkuMap = array_flip($ppmSkus); // For O(1) lookup
        $stats['ppm_skus_count'] = count($ppmSkus);

        Log::debug('ScanMissingInPpmJob: PPM SKUs fetched', [
            'count' => count($ppmSkus),
        ]);

        // Step 3: Find missing SKUs (in source but not in PPM)
        $missingSkus = array_diff($sourceSkus, $ppmSkus);
        $totalMissing = count($missingSkus);

        $session->update(['total_scanned' => $totalMissing]);

        Log::debug('ScanMissingInPpmJob: Missing SKUs identified', [
            'count' => $totalMissing,
        ]);

        // Step 4: Process missing SKUs in chunks
        $chunks = array_chunk($missingSkus, $this->batchSize);

        foreach ($chunks as $chunkIndex => $skuChunk) {
            foreach ($skuChunk as $sku) {
                try {
                    $this->processMissingSku($sku, $session, $source);

                    $stats['total_scanned']++;
                    $stats['unmatched']++;
                    $session->incrementUnmatched();

                } catch (\Exception $e) {
                    $stats['errors']++;
                    $session->incrementErrors();

                    Log::warning('ScanMissingInPpmJob: SKU processing error', [
                        'sku' => $sku,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Log chunk progress
            Log::debug('ScanMissingInPpmJob: Chunk processed', [
                'chunk' => $chunkIndex + 1,
                'total_chunks' => count($chunks),
                'processed' => $stats['total_scanned'],
            ]);
        }

        return $stats;
    }

    /**
     * Process a single missing SKU - fetch data from source and save result.
     *
     * @param string $sku
     * @param ProductScanSession $session
     * @param ScanSourceInterface $source
     * @return ProductScanResult
     */
    protected function processMissingSku(
        string $sku,
        ProductScanSession $session,
        ScanSourceInterface $source
    ): ProductScanResult {
        // Fetch full product data from source
        $sourceProduct = null;
        try {
            $sourceProduct = $source->getProductBySku($sku);
        } catch (\Exception $e) {
            Log::debug('ScanMissingInPpmJob: Could not fetch product details', [
                'sku' => $sku,
                'error' => $e->getMessage(),
            ]);
        }

        // Prepare result data
        $resultData = [
            'scan_session_id' => $session->id,
            'sku' => $sku,
            'name' => $sourceProduct['name'] ?? null,
            'external_id' => $sourceProduct['external_id'] ?? null,
            'ppm_product_id' => null, // Missing in PPM
            'external_source_type' => $this->sourceType,
            'external_source_id' => $this->sourceId,
            'match_status' => ProductScanResult::MATCH_UNMATCHED,
            'resolution_status' => ProductScanResult::RESOLUTION_PENDING,
            'source_data' => $sourceProduct,
            'ppm_data' => null, // No PPM data - product doesn't exist
        ];

        return ProductScanResult::create($resultData);
    }

    /**
     * Handle scan failure.
     *
     * @param ProductScanSession $session
     * @param \Throwable $exception
     */
    protected function handleScanFailure(ProductScanSession $session, \Throwable $exception): void
    {
        Log::error('ScanMissingInPpmJob: Failed', [
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
        Log::error('ScanMissingInPpmJob: Permanently failed', [
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
            'scan-missing-ppm',
            'session:' . $this->scanSessionId,
            'source:' . $this->sourceType,
        ];
    }
}
