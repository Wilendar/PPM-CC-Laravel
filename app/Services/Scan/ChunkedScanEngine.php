<?php

namespace App\Services\Scan;

use App\Models\Product;
use App\Models\ProductScanSession;
use App\Models\ProductScanResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ChunkedScanEngine
 *
 * Engine do chunked skanowania produktow w trybie AJAX.
 * Porownuje dane z PPM z danymi zewnetrznych zrodel (PrestaShop, Subiekt GT, Baselinker, Dynamics)
 * w chunkach po 500 produktow, z mozliwoscia sledzenia postepu.
 *
 * Chunked scan szuka wylacznie KONFLIKTOW (roznic danych) - NIE szuka missing products!
 * Missing products wykrywa oddzielny mechanizm (Quick Matrix).
 *
 * @package App\Services\Scan
 * @version 1.0.0
 */
class ChunkedScanEngine
{
    /** Domyslny rozmiar chunka */
    private const DEFAULT_CHUNK_SIZE = 500;

    /** TTL cache dla danych zrodel (1 godzina) */
    private const CACHE_TTL = 3600;

    public function __construct(
        private ProductScanService $scanService
    ) {}

    /**
     * Prefetch SKU data from all sources and prepare session for chunked scanning.
     *
     * Pobiera wszystkie SKU z kazdego zrodla i zapisuje do cache.
     * Oblicza total_chunks na podstawie liczby produktow PPM i rozmiaru chunka.
     * Oznacza sesje jako running.
     *
     * @param ProductScanSession $session Sesja skanowania
     * @param array $sourceConfigs Lista konfiguracji zrodel, np.
     *   [['type' => 'prestashop', 'id' => 1], ['type' => 'subiekt_gt', 'id' => 3]]
     * @param int $chunkSize Rozmiar chunka (domyslnie 500)
     * @return array{session_id: int, total_products: int, total_chunks: int, chunk_size: int, sources_cached: int}
     */
    public function prefetchSourceData(
        ProductScanSession $session,
        array $sourceConfigs,
        int $chunkSize = self::DEFAULT_CHUNK_SIZE
    ): array {
        Log::info('ChunkedScanEngine::prefetchSourceData STARTED', [
            'session_id' => $session->id,
            'sources_count' => count($sourceConfigs),
            'chunk_size' => $chunkSize,
        ]);

        $sourcesCached = 0;

        foreach ($sourceConfigs as $config) {
            $sourceType = $config['type'];
            $sourceId = $config['id'] ?? null;

            try {
                $source = $this->scanService->getScanSource($sourceType, $sourceId);
                $skus = $source->getAllSkus();

                $cacheKey = $this->getSourceCacheKey($session->id, $sourceType, $sourceId ?? 0);
                Cache::put($cacheKey, $skus, self::CACHE_TTL);

                $sourcesCached++;

                Log::info('ChunkedScanEngine: source SKUs cached', [
                    'session_id' => $session->id,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'skus_count' => count($skus),
                ]);
            } catch (\Exception $e) {
                Log::error('ChunkedScanEngine: failed to prefetch source', [
                    'session_id' => $session->id,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        $totalProducts = Product::whereNotNull('sku')->where('sku', '!=', '')->count();
        $totalChunks = (int) ceil($totalProducts / $chunkSize);

        $session->update([
            'total_chunks' => $totalChunks,
            'processed_chunks' => 0,
            'chunk_size' => $chunkSize,
            'scan_mode' => 'chunked',
            'source_cache_key' => json_encode($sourceConfigs),
        ]);

        $session->markAsRunning();

        Log::info('ChunkedScanEngine::prefetchSourceData COMPLETED', [
            'session_id' => $session->id,
            'total_products' => $totalProducts,
            'total_chunks' => $totalChunks,
            'sources_cached' => $sourcesCached,
        ]);

        return [
            'session_id' => $session->id,
            'total_products' => $totalProducts,
            'total_chunks' => $totalChunks,
            'chunk_size' => $chunkSize,
            'sources_cached' => $sourcesCached,
        ];
    }

    /**
     * Process a single chunk of products.
     *
     * Pobiera chunk produktow PPM, porownuje z danymi ze zrodel (z cache SKU map),
     * i zapisuje wyniki konfliktu do bazy. Jeden chunk fail nie przerywa calego skanu.
     *
     * @param ProductScanSession $session Sesja skanowania
     * @param int $chunkIndex Indeks chunka (0-based)
     * @return array{chunk_index: int, processed: int, conflicts_found: int, progress_pct: float}
     */
    public function processChunk(ProductScanSession $session, int $chunkIndex): array
    {
        Log::info('ChunkedScanEngine::processChunk STARTED', [
            'session_id' => $session->id,
            'chunk_index' => $chunkIndex,
        ]);

        $conflictsFound = 0;
        $processed = 0;
        $resultsToUpsert = [];

        try {
            $products = Product::whereNotNull('sku')
                ->where('sku', '!=', '')
                ->select(['id', 'sku', 'name', 'ean', 'manufacturer_id', 'weight'])
                ->with('manufacturerRelation:id,name')
                ->orderBy('id')
                ->skip($chunkIndex * $session->chunk_size)
                ->take($session->chunk_size)
                ->get();

            $sourceConfigs = json_decode($session->source_cache_key ?? '[]', true);

            foreach ($products as $product) {
                $processed++;
                $ppmData = $this->buildPpmData($product);

                foreach ($sourceConfigs as $config) {
                    $sourceType = $config['type'];
                    $sourceId = $config['id'] ?? null;
                    $sourceIdInt = (int) ($sourceId ?? 0);

                    try {
                        $cacheKey = $this->getSourceCacheKey($session->id, $sourceType, $sourceIdInt);
                        $cachedSkus = Cache::get($cacheKey, []);

                        // SKU NIE istnieje w zrodle -> zapisz jako unmatched (not_found)
                        if (!in_array($product->sku, $cachedSkus, true)) {
                            $resultsToUpsert[] = [
                                'scan_session_id'      => $session->id,
                                'sku'                  => $product->sku,
                                'external_id'          => null,
                                'name'                 => $product->name,
                                'match_status'         => ProductScanResult::MATCH_UNMATCHED,
                                'ppm_product_id'       => $product->id,
                                'external_source_type' => $sourceType,
                                'external_source_id'   => $sourceIdInt ?: null,
                                'source_data'          => null,
                                'ppm_data'             => json_encode($ppmData),
                                'diff_data'            => null,
                                'resolution_status'    => ProductScanResult::RESOLUTION_PENDING,
                                'created_at'           => now(),
                            ];
                            continue;
                        }

                        $source = $this->scanService->getScanSource($sourceType, $sourceId);
                        $sourceProductData = $source->getProductBySku($product->sku);

                        if ($sourceProductData === null) {
                            // SKU na liscie ale nie mozna pobrac danych -> traktuj jako unmatched
                            $resultsToUpsert[] = [
                                'scan_session_id'      => $session->id,
                                'sku'                  => $product->sku,
                                'external_id'          => null,
                                'name'                 => $product->name,
                                'match_status'         => ProductScanResult::MATCH_UNMATCHED,
                                'ppm_product_id'       => $product->id,
                                'external_source_type' => $sourceType,
                                'external_source_id'   => $sourceIdInt ?: null,
                                'source_data'          => null,
                                'ppm_data'             => json_encode($ppmData),
                                'diff_data'            => null,
                                'resolution_status'    => ProductScanResult::RESOLUTION_PENDING,
                                'created_at'           => now(),
                            ];
                            continue;
                        }

                        $diff = $this->scanService->calculateDiff($ppmData, $sourceProductData);

                        if ($diff['has_differences']) {
                            // Produkt istnieje ale dane sie roznia -> conflict
                            $conflictsFound++;
                            $matchStatus = ProductScanResult::MATCH_CONFLICT;
                        } else {
                            // Produkt istnieje i dane sie zgadzaja -> matched
                            $matchStatus = ProductScanResult::MATCH_MATCHED;
                        }

                        $resultsToUpsert[] = [
                            'scan_session_id'      => $session->id,
                            'sku'                  => $product->sku,
                            'external_id'          => $sourceProductData['external_id'] ?? null,
                            'name'                 => $sourceProductData['name'] ?? $product->name,
                            'match_status'         => $matchStatus,
                            'ppm_product_id'       => $product->id,
                            'external_source_type' => $sourceType,
                            'external_source_id'   => $sourceIdInt ?: null,
                            'source_data'          => json_encode($sourceProductData),
                            'ppm_data'             => json_encode($ppmData),
                            'diff_data'            => $diff['has_differences'] ? json_encode($diff) : null,
                            'resolution_status'    => $diff['has_differences']
                                ? ProductScanResult::RESOLUTION_PENDING
                                : ProductScanResult::RESOLUTION_LINKED,
                            'created_at'           => now(),
                        ];
                    } catch (\Exception $e) {
                        Log::error('ChunkedScanEngine: error processing product/source', [
                            'session_id' => $session->id,
                            'sku' => $product->sku,
                            'source_type' => $sourceType,
                            'error' => $e->getMessage(),
                        ]);
                        $session->increment('errors_count');
                    }
                }
            }

            // Bulk upsert w porcjach po 500 (duzo wiecej rekordow niz wczesniej)
            if (!empty($resultsToUpsert)) {
                foreach (array_chunk($resultsToUpsert, 500) as $batch) {
                    ProductScanResult::upsert(
                        $batch,
                        ['sku', 'external_source_type', 'external_source_id'],
                        ['scan_session_id', 'match_status', 'external_id', 'name', 'ppm_product_id', 'source_data', 'ppm_data', 'diff_data', 'resolution_status']
                    );
                }
            }

            $session->increment('processed_chunks');
            $session->increment('matched_count', $conflictsFound);

        } catch (\Exception $e) {
            Log::error('ChunkedScanEngine::processChunk FAILED', [
                'session_id' => $session->id,
                'chunk_index' => $chunkIndex,
                'error' => $e->getMessage(),
            ]);
            $session->increment('errors_count');
        }

        $session->refresh();
        $progressPct = $session->total_chunks > 0
            ? round(($session->processed_chunks / $session->total_chunks) * 100, 2)
            : 0.0;

        Log::info('ChunkedScanEngine::processChunk COMPLETED', [
            'session_id' => $session->id,
            'chunk_index' => $chunkIndex,
            'processed' => $processed,
            'conflicts_found' => $conflictsFound,
            'progress_pct' => $progressPct,
        ]);

        return [
            'chunk_index' => $chunkIndex,
            'processed' => $processed,
            'conflicts_found' => $conflictsFound,
            'progress_pct' => $progressPct,
        ];
    }

    /**
     * Finalize a completed scan session.
     *
     * Oznacza sesje jako completed, czysci cache i zwraca podsumowanie statystyk.
     *
     * @param ProductScanSession $session Sesja skanowania
     * @return array{total_scanned: int, conflicts_found: int, duration_seconds: int}
     */
    public function finalizeScan(ProductScanSession $session): array
    {
        $session->refresh();

        $totalScanned = Product::whereNotNull('sku')->where('sku', '!=', '')->count();
        $conflictsFound = $session->matched_count;
        $durationSeconds = $session->getDuration() ?? 0;

        $this->scanService->completeScan(
            session: $session,
            totalScanned: $totalScanned,
            matchedCount: $conflictsFound,
            unmatchedCount: 0,
            errorsCount: $session->errors_count,
            resultSummary: [
                'total_scanned' => $totalScanned,
                'conflicts_found' => $conflictsFound,
                'duration_seconds' => $durationSeconds,
                'scan_mode' => 'chunked',
            ]
        );

        $this->clearSessionCache($session);

        Log::info('ChunkedScanEngine::finalizeScan COMPLETED', [
            'session_id' => $session->id,
            'total_scanned' => $totalScanned,
            'conflicts_found' => $conflictsFound,
            'duration_seconds' => $durationSeconds,
        ]);

        return [
            'total_scanned' => $totalScanned,
            'conflicts_found' => $conflictsFound,
            'duration_seconds' => $durationSeconds,
        ];
    }

    /**
     * Cancel an active scan session.
     *
     * Oznacza sesje jako cancelled i czysci cache.
     *
     * @param ProductScanSession $session Sesja skanowania
     * @return void
     */
    public function cancelScan(ProductScanSession $session): void
    {
        $session->markAsCancelled();
        $this->clearSessionCache($session);

        Log::info('ChunkedScanEngine::cancelScan', [
            'session_id' => $session->id,
        ]);
    }

    /**
     * Resume an existing chunked scan session.
     *
     * Sprawdza czy sesja jest w trybie chunked i jest aktywna.
     * Zwraca stan do wznowienia lub null jezeli sesja nie kwalifikuje sie.
     *
     * @param ProductScanSession $session Sesja skanowania
     * @return array{session_id: int, processed_chunks: int, total_chunks: int, progress_pct: float}|null
     */
    public function resumeScan(ProductScanSession $session): ?array
    {
        if ($session->status !== ProductScanSession::STATUS_RUNNING) {
            return null;
        }

        if ($session->scan_mode !== 'chunked') {
            return null;
        }

        $totalChunks = $session->total_chunks ?? 0;
        $processedChunks = $session->processed_chunks ?? 0;

        $progressPct = $totalChunks > 0
            ? round(($processedChunks / $totalChunks) * 100, 2)
            : 0.0;

        Log::info('ChunkedScanEngine::resumeScan', [
            'session_id' => $session->id,
            'processed_chunks' => $processedChunks,
            'total_chunks' => $totalChunks,
        ]);

        return [
            'session_id' => $session->id,
            'processed_chunks' => $processedChunks,
            'total_chunks' => $totalChunks,
            'progress_pct' => $progressPct,
        ];
    }

    /**
     * Build normalized PPM product data array for diff comparison.
     *
     * @param Product $product
     * @return array
     */
    private function buildPpmData(Product $product): array
    {
        return [
            'sku' => $product->sku,
            'name' => $product->name,
            'ean' => $product->ean,
            'weight' => $product->weight,
            'manufacturer' => $product->manufacturerRelation?->name,
        ];
    }

    /**
     * Build cache key for a source SKU list.
     *
     * @param int $sessionId
     * @param string $type Source type
     * @param int $id Source ID (0 if null)
     * @return string
     */
    private function getSourceCacheKey(int $sessionId, string $type, int $id): string
    {
        return "scan_{$sessionId}_{$type}_{$id}";
    }

    /**
     * Clear all cache entries associated with a scan session.
     *
     * @param ProductScanSession $session
     * @return void
     */
    private function clearSessionCache(ProductScanSession $session): void
    {
        $sourceConfigs = json_decode($session->source_cache_key ?? '[]', true);

        foreach ($sourceConfigs as $config) {
            $sourceType = $config['type'];
            $sourceId = (int) ($config['id'] ?? 0);
            $cacheKey = $this->getSourceCacheKey($session->id, $sourceType, $sourceId);
            Cache::forget($cacheKey);
        }

        Log::info('ChunkedScanEngine: session cache cleared', [
            'session_id' => $session->id,
            'sources_cleared' => count($sourceConfigs),
        ]);
    }
}
