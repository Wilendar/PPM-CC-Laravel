<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\BusinessPartner;
use App\Models\ImportSession;
use App\Models\PendingProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * BatchImportProcessor - przetwarzanie importu w batch
 *
 * ETAP_06 FAZA 4 - Batch Processing
 *
 * Features:
 * - Chunk processing (100 rows per batch)
 * - Transaction per chunk (rollback on error in chunk)
 * - Continue on error (collect errors, don't stop)
 * - Progress tracking (update ImportSession counters)
 * - SKU validation + duplicate check per row
 * - Error collection with row numbers
 *
 * @package App\Services\Import
 */
class BatchImportProcessor
{
    /**
     * Default batch size
     */
    protected int $batchSize = 100;

    /**
     * Current import session
     */
    protected ?ImportSession $session = null;

    /**
     * SKU parser service
     */
    protected SkuParserService $skuParser;

    public function __construct(SkuParserService $skuParser)
    {
        $this->skuParser = $skuParser;
    }

    /**
     * Process rows in batch with progress tracking
     *
     * @param array<array> $rows Mapped rows (PPM field => value)
     * @param ImportSession $session
     * @return array{created: int, skipped: int, errors: array}
     */
    public function processBatch(array $rows, ImportSession $session): array
    {
        $this->session = $session;

        Log::info('BatchImportProcessor: starting batch', [
            'session_id' => $session->id,
            'total_rows' => count($rows),
            'batch_size' => $this->batchSize,
        ]);

        $session->update([
            'status' => ImportSession::STATUS_PARSING,
            'started_at' => now(),
            'total_rows' => count($rows),
        ]);

        $created = 0;
        $skipped = 0;
        $errors = [];

        // Chunk processing
        $chunks = array_chunk($rows, $this->batchSize);
        $totalChunks = count($chunks);

        foreach ($chunks as $chunkIndex => $chunk) {
            try {
                DB::beginTransaction();

                $chunkResult = $this->processChunk($chunk, $chunkIndex);

                $created += $chunkResult['created'];
                $skipped += $chunkResult['skipped'];
                $errors = array_merge($errors, $chunkResult['errors']);

                DB::commit();

                // Update progress after each chunk
                $session->update([
                    'products_created' => $created,
                    'products_skipped' => $skipped,
                ]);

                Log::debug('BatchImportProcessor: chunk completed', [
                    'session_id' => $session->id,
                    'chunk' => ($chunkIndex + 1) . '/' . $totalChunks,
                    'created' => $chunkResult['created'],
                    'skipped' => $chunkResult['skipped'],
                ]);

            } catch (\Exception $e) {
                DB::rollBack();

                Log::error('BatchImportProcessor: chunk failed', [
                    'session_id' => $session->id,
                    'chunk_index' => $chunkIndex,
                    'error' => $e->getMessage(),
                ]);

                $errors[] = [
                    'chunk' => $chunkIndex,
                    'row' => null,
                    'sku' => null,
                    'message' => 'Blad przetwarzania grupy wierszy: ' . $e->getMessage(),
                ];

                // Skip this chunk entirely, continue with next
                $skipped += count($chunk);
            }
        }

        // Final status update
        $session->update([
            'status' => ImportSession::STATUS_READY,
            'products_created' => $created,
            'products_skipped' => $skipped,
            'products_failed' => count($errors),
            'finished_at' => now(),
        ]);

        Log::info('BatchImportProcessor: batch completed', [
            'session_id' => $session->id,
            'created' => $created,
            'skipped' => $skipped,
            'errors_count' => count($errors),
        ]);

        return [
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Process single chunk
     *
     * @param array<array> $chunk
     * @param int $chunkIndex
     * @return array{created: int, skipped: int, errors: array}
     */
    protected function processChunk(array $chunk, int $chunkIndex): array
    {
        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($chunk as $rowIndex => $row) {
            $globalRowIndex = ($chunkIndex * $this->batchSize) + $rowIndex + 2; // +2 because row 1 is header

            try {
                $result = $this->processRow($row, $globalRowIndex);

                if ($result['success']) {
                    $created++;
                } else {
                    $skipped++;
                    if (!empty($result['error'])) {
                        $errors[] = $result['error'];
                    }
                }

            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $globalRowIndex,
                    'sku' => $row['sku'] ?? '',
                    'message' => 'Blad zapisu: ' . $e->getMessage(),
                ];
                $skipped++;
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Process single row
     *
     * @param array $row Mapped row data
     * @param int $rowNumber Row number in original file
     * @return array{success: bool, error: array|null}
     */
    protected function processRow(array $row, int $rowNumber): array
    {
        $sku = trim(strtoupper($row['sku'] ?? ''));

        // Validate SKU exists
        if (empty($sku)) {
            return [
                'success' => false,
                'error' => [
                    'row' => $rowNumber,
                    'sku' => '',
                    'message' => 'Brak SKU w wierszu',
                ],
            ];
        }

        // Validate SKU format
        $skuValidation = $this->skuParser->validateSKUFormat($sku);

        if (!$skuValidation['valid']) {
            return [
                'success' => false,
                'error' => [
                    'row' => $rowNumber,
                    'sku' => $sku,
                    'message' => $skuValidation['message'] ?? 'Nieprawidlowy format SKU',
                ],
            ];
        }

        // Check if SKU exists in PPM
        $existingInPPM = $this->skuParser->checkExistingInPPM([$sku]);

        if (!empty($existingInPPM)) {
            return [
                'success' => false,
                'error' => [
                    'row' => $rowNumber,
                    'sku' => $sku,
                    'message' => 'SKU juz istnieje w bazie PPM',
                ],
            ];
        }

        // Check if SKU already exists in this import session
        $existsInSession = PendingProduct::where('import_session_id', $this->session->id)
            ->where('sku', $sku)
            ->exists();

        if ($existsInSession) {
            return [
                'success' => false,
                'error' => [
                    'row' => $rowNumber,
                    'sku' => $sku,
                    'message' => 'SKU juz istnieje w tej sesji importu (duplikat)',
                ],
            ];
        }

        // Resolve business partner names to FK IDs
        $manufacturerId = null;
        if (!empty($row['manufacturer'])) {
            $manufacturerId = $this->resolveBusinessPartnerId($row['manufacturer'], 'manufacturer');
        }

        $supplierId = null;
        if (isset($row['supplier_id'])) {
            $supplierId = is_numeric($row['supplier_id'])
                ? (int) $row['supplier_id']
                : $this->resolveBusinessPartnerId($row['supplier_id'], 'supplier');
        }

        $importerId = null;
        if (isset($row['importer_id'])) {
            $importerId = is_numeric($row['importer_id'])
                ? (int) $row['importer_id']
                : $this->resolveBusinessPartnerId($row['importer_id'], 'importer');
        }

        // Create PendingProduct
        PendingProduct::create([
            'import_session_id' => $this->session->id,
            'sku' => $sku,
            'name' => $this->trimOrNull($row['name'] ?? null),
            'manufacturer' => $this->trimOrNull($row['manufacturer'] ?? null),
            'manufacturer_id' => $manufacturerId,
            'supplier_id' => $supplierId,
            'importer_id' => $importerId,
            'supplier_code' => $this->trimOrNull($row['supplier_code'] ?? null),
            'ean' => $this->trimOrNull($row['ean'] ?? null),
            'weight' => $this->parseFloat($row['weight'] ?? null),
            'height' => $this->parseFloat($row['height'] ?? null),
            'width' => $this->parseFloat($row['width'] ?? null),
            'length' => $this->parseFloat($row['length'] ?? null),
            'base_price' => $this->parseFloat($row['price'] ?? null),
            'purchase_price' => $this->parseFloat($row['purchase_price'] ?? null),
            'short_description' => $this->trimOrNull($row['short_description'] ?? null),
            'long_description' => $this->trimOrNull($row['long_description'] ?? null),
            'category' => $this->trimOrNull($row['category'] ?? null),
            'vin' => $this->trimOrNull($row['vin'] ?? null),
            'engine_number' => $this->trimOrNull($row['engine_number'] ?? null),
            'model' => $this->trimOrNull($row['model'] ?? null),
            'year' => $this->trimOrNull($row['year'] ?? null),
            'original_code' => $this->trimOrNull($row['original_code'] ?? null),
            'replacement_code' => $this->trimOrNull($row['replacement_code'] ?? null),
            'imported_by' => auth()->id(),
            'imported_at' => now(),
        ]);

        return [
            'success' => true,
            'error' => null,
        ];
    }

    /**
     * Resolve BusinessPartner name to ID (find or auto-create)
     *
     * @param string|null $name Partner name from import
     * @param string $type Partner type (supplier, manufacturer, importer)
     * @return int|null Partner ID or null if name empty
     */
    protected function resolveBusinessPartnerId(?string $name, string $type): ?int
    {
        if (empty($name)) {
            return null;
        }

        $name = trim($name);
        if ($name === '') {
            return null;
        }

        // Find existing partner by name and type
        $partner = BusinessPartner::where('name', $name)
            ->where('type', $type)
            ->first();

        if ($partner) {
            return $partner->id;
        }

        // Auto-create new partner
        $code = Str::slug($name, '_');

        // Ensure unique code+type combination
        $baseCode = $code;
        $counter = 1;
        while (BusinessPartner::where('code', $code)->where('type', $type)->exists()) {
            $code = $baseCode . '_' . $counter++;
        }

        $partner = BusinessPartner::create([
            'name' => $name,
            'code' => $code,
            'type' => $type,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        Log::info('BatchImportProcessor: auto-created BusinessPartner', [
            'session_id' => $this->session?->id,
            'partner_id' => $partner->id,
            'name' => $name,
            'type' => $type,
            'code' => $code,
        ]);

        return $partner->id;
    }

    /**
     * Trim string or return null if empty
     *
     * @param string|null $value
     * @return string|null
     */
    protected function trimOrNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Parse string to float or return null
     *
     * @param string|null $value
     * @return float|null
     */
    protected function parseFloat(?string $value): ?float
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        // Replace comma with dot for European format
        $value = str_replace(',', '.', $value);

        // Remove non-numeric except dot and minus
        $value = preg_replace('/[^0-9.\-]/', '', $value);

        if ($value === '' || $value === '-') {
            return null;
        }

        return (float) $value;
    }

    /**
     * Set batch size
     *
     * @param int $size
     * @return self
     */
    public function setBatchSize(int $size): self
    {
        $this->batchSize = max(10, min(1000, $size)); // Limit between 10-1000

        return $this;
    }

    /**
     * Get current batch size
     *
     * @return int
     */
    public function getBatchSize(): int
    {
        return $this->batchSize;
    }
}
