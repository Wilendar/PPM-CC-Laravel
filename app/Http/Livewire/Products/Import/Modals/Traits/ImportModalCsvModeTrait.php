<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\Import\Modals\Traits;

use App\Models\ImportSession;
use App\Models\PendingProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ImportModalCsvModeTrait - CSV import mode for ProductImportModal
 *
 * Handles:
 * - Pasting semicolon-separated text
 * - Uploading CSV file (via CsvParserService)
 * - Auto-mapping headers to PPM fields
 * - Manual mapping override
 * - Preview before import
 * - Batch creation of PendingProducts
 *
 * @package App\Http\Livewire\Products\Import\Modals\Traits
 */
trait ImportModalCsvModeTrait
{
    /**
     * Raw text input (pasted CSV data, semicolon separated)
     */
    public string $csvTextInput = '';

    /**
     * Uploaded CSV file (Livewire TemporaryUploadedFile)
     */
    public $csvFile = null;

    /**
     * Template headers for CSV export/reference
     */
    public array $csvTemplateHeaders = [
        'SKU', 'Nazwa', 'Typ produktu', 'Kod Dostawcy', 'Dostawca',
        'Producent', 'Importer', 'EAN', 'Kod CN', 'Material',
        'Symbol z wada', 'Zastosowanie',
    ];

    /**
     * Auto-detected mapping: column index => PPM field key
     */
    public array $csvDetectedMapping = [];

    /**
     * User-adjusted manual mapping: column index => PPM field key
     */
    public array $csvManualMapping = [];

    /**
     * Parsed data rows from CSV text or file
     */
    public array $csvParsedRows = [];

    /**
     * Parsed header names from first row
     */
    public array $csvParsedHeaders = [];

    /**
     * Wizard step: true = show mapping UI
     */
    public bool $csvMappingStep = false;

    /**
     * Wizard step: true = show preview before import
     */
    public bool $csvPreviewStep = false;

    /**
     * Total parsed rows count
     */
    public int $csvTotalRows = 0;

    /**
     * Sample values from first data row per column index
     */
    public array $csvSampleValues = [];

    /**
     * Preview headers (PPM field labels for mapped columns): field_key => label
     */
    public array $csvPreviewHeaders = [];

    /**
     * Preview rows (max 10, mapped data): array of [field_key => value]
     */
    public array $csvPreviewRows = [];

    /**
     * Available PPM fields for mapping dropdown
     */
    public const CSV_MAPPABLE_FIELDS = [
        'sku' => 'SKU',
        'name' => 'Nazwa',
        'product_type' => 'Typ produktu',
        'supplier_code' => 'Kod Dostawcy',
        'supplier' => 'Dostawca',
        'manufacturer' => 'Producent',
        'importer' => 'Importer',
        'ean' => 'EAN',
        'cn_code' => 'Kod CN',
        'material' => 'Material',
        'defect_symbol' => 'Symbol z wada',
        'application' => 'Zastosowanie',
    ];

    /**
     * Parse pasted CSV text input
     *
     * Splits by newlines, then by semicolons.
     * First row is treated as headers if it matches known patterns.
     * Auto-maps recognized headers to PPM fields.
     * Handles BOM, encoding detection, and validates data structure.
     */
    public function parseCsvInput(): void
    {
        if (empty(trim($this->csvTextInput))) {
            $this->addError('csvTextInput', 'Wklej dane CSV do pola tekstowego');
            return;
        }

        Log::debug('ImportModalCsvModeTrait: parseCsvInput CALLED', [
            'input_length' => strlen($this->csvTextInput),
        ]);

        try {
            $input = trim($this->csvTextInput);

            // Remove UTF-8 BOM if present
            $bom = "\xEF\xBB\xBF";
            if (str_starts_with($input, $bom)) {
                $input = substr($input, 3);
            }

            $lines = preg_split('/\r?\n/', $input);
            $lines = array_filter($lines, fn(string $line) => trim($line) !== '');
            $lines = array_values($lines); // re-index after filter

            if (empty($lines)) {
                $this->addError('csvTextInput', 'Brak danych do przetworzenia');
                return;
            }

            // Parse first line as headers - auto-detect separator
            $firstLine = array_shift($lines);
            $separator = str_contains($firstLine, "\t")
                ? "\t"
                : (str_contains($firstLine, ';') ? ';' : ',');
            $this->csvParsedHeaders = array_map('trim', explode($separator, $firstLine));

            // Filter out empty headers
            $this->csvParsedHeaders = array_values(array_filter(
                $this->csvParsedHeaders,
                fn(string $h) => $h !== ''
            ));

            // Validate: minimum 1 header column
            if (count($this->csvParsedHeaders) < 1) {
                $this->addError('csvTextInput', 'Nie wykryto naglowkow kolumn. Sprawdz format danych.');
                return;
            }

            // Validate: at least 1 data row
            if (empty($lines)) {
                $this->addError('csvTextInput', 'Brak wierszy danych (tylko naglowki). Dodaj co najmniej 1 wiersz z danymi.');
                return;
            }

            // Parse remaining lines as data rows
            $this->csvParsedRows = [];
            foreach ($lines as $line) {
                if (trim($line) === '') {
                    continue;
                }

                $values = array_map('trim', explode($separator, $line));

                // Pad or trim to match header count
                $row = [];
                foreach ($this->csvParsedHeaders as $index => $header) {
                    $row[$index] = $values[$index] ?? '';
                }

                // Skip completely empty rows
                if (count(array_filter($row, fn($v) => $v !== '')) === 0) {
                    continue;
                }

                $this->csvParsedRows[] = $row;
            }

            if (empty($this->csvParsedRows)) {
                $this->addError('csvTextInput', 'Wszystkie wiersze danych sa puste. Sprawdz format danych.');
                return;
            }

            $this->csvTotalRows = count($this->csvParsedRows);

            // Build sample values from first data row
            $this->csvSampleValues = [];
            if (!empty($this->csvParsedRows[0])) {
                foreach ($this->csvParsedHeaders as $index => $header) {
                    $this->csvSampleValues[$index] = $this->csvParsedRows[0][$index] ?? '';
                }
            }

            // Auto-map headers (wrapped in try-catch internally)
            $this->applyCsvAutoMapping();

            // Move to mapping step
            $this->csvMappingStep = true;
            $this->csvPreviewStep = false;

            Log::debug('ImportModalCsvModeTrait: parseCsvInput COMPLETED', [
                'headers_count' => count($this->csvParsedHeaders),
                'rows_count' => $this->csvTotalRows,
            ]);
        } catch (\Exception $e) {
            Log::error('ImportModalCsvModeTrait: parseCsvInput FAILED', [
                'error' => $e->getMessage(),
            ]);

            $this->addError('csvTextInput', 'Blad parsowania danych: ' . $e->getMessage());
        }
    }

    /**
     * Livewire lifecycle hook - auto-trigger parsing when CSV file uploads
     *
     * Called automatically by Livewire when $csvFile property is updated
     * (i.e., after file upload via wire:model="csvFile" completes).
     */
    public function updatedCsvFile(): void
    {
        if (!$this->csvFile) {
            return;
        }

        Log::debug('ImportModalCsvModeTrait: updatedCsvFile - file uploaded, auto-parsing', [
            'filename' => $this->csvFile->getClientOriginalName(),
            'size' => $this->csvFile->getSize(),
        ]);

        $this->uploadCsvFile();
    }

    /**
     * Upload and parse CSV file using CsvParserService
     *
     * Requires WithFileUploads trait on parent component.
     */
    public function uploadCsvFile(): void
    {
        // Check if file exists first
        if (!$this->csvFile) {
            $this->addError('csvFile', 'Wybierz plik CSV');
            return;
        }

        // Validate file size
        $this->validate([
            'csvFile' => ['required', 'file', 'max:51200'],
        ], [
            'csvFile.max' => 'Plik nie moze przekraczac 50MB',
        ]);

        // Extension check (mimes can fail for CSV on Windows)
        $extension = strtolower($this->csvFile->getClientOriginalExtension());
        $allowedExtensions = ['csv', 'txt', 'xlsx', 'xls'];
        if (!in_array($extension, $allowedExtensions)) {
            $this->addError('csvFile', 'Dozwolone formaty: CSV, TXT, XLSX, XLS');
            return;
        }

        // MIME type validation (additional security)
        $allowedMimeTypes = [
            'text/csv',
            'text/plain',
            'application/csv',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/octet-stream', // Some systems report CSV as octet-stream
        ];
        $mimeType = $this->csvFile->getMimeType();
        if (!in_array($mimeType, $allowedMimeTypes)) {
            Log::warning('ImportModalCsvModeTrait: uploadCsvFile - unexpected MIME type', [
                'mime' => $mimeType,
                'extension' => $extension,
            ]);
            // Don't reject - just log warning, as MIME detection can be unreliable for CSV
        }

        Log::debug('ImportModalCsvModeTrait: uploadCsvFile CALLED', [
            'filename' => $this->csvFile->getClientOriginalName(),
            'size' => $this->csvFile->getSize(),
            'mime' => $mimeType,
            'extension' => $extension,
        ]);

        try {
            // Verify file is readable
            $realPath = $this->csvFile->getRealPath();
            if (!$realPath || !file_exists($realPath)) {
                $this->addError('csvFile', 'Nie mozna odczytac pliku. Sprobuj ponownie.');
                return;
            }

            $parsed = $this->csvParser->parseCSV($this->csvFile);

            $this->csvParsedHeaders = $parsed['headers'] ?? [];
            $this->csvTotalRows = $parsed['total_rows'] ?? 0;

            // Convert rows from associative (header=>value) to indexed arrays
            $this->csvParsedRows = [];
            foreach (($parsed['rows'] ?? []) as $row) {
                $indexedRow = [];
                foreach ($this->csvParsedHeaders as $index => $header) {
                    $indexedRow[$index] = $row[$header] ?? '';
                }
                $this->csvParsedRows[] = $indexedRow;
            }

            // Build sample values from first data row
            $this->csvSampleValues = [];
            if (!empty($this->csvParsedRows[0])) {
                foreach ($this->csvParsedHeaders as $index => $header) {
                    $this->csvSampleValues[$index] = $this->csvParsedRows[0][$index] ?? '';
                }
            }

            $this->applyCsvAutoMapping();

            $this->csvMappingStep = true;
            $this->csvPreviewStep = false;

            Log::debug('ImportModalCsvModeTrait: uploadCsvFile COMPLETED', [
                'headers' => $this->csvParsedHeaders,
                'rows_count' => $this->csvTotalRows,
            ]);

        } catch (\Exception $e) {
            Log::error('ImportModalCsvModeTrait: uploadCsvFile FAILED', [
                'error' => $e->getMessage(),
            ]);

            $this->addError('csvFile', 'Blad parsowania pliku: ' . $e->getMessage());
        }
    }

    /**
     * Auto-map CSV headers to PPM fields
     *
     * Uses ColumnMappingService for fuzzy matching.
     * Populates csvDetectedMapping and csvManualMapping.
     * Wrapped in try-catch to prevent 500 errors on malformed data.
     */
    protected function applyCsvAutoMapping(): void
    {
        if (empty($this->csvParsedHeaders)) {
            return;
        }

        // Validate minimum column count
        if (count($this->csvParsedHeaders) < 1) {
            Log::warning('ImportModalCsvModeTrait: applyCsvAutoMapping - no headers to map');
            return;
        }

        $this->csvDetectedMapping = [];
        $this->csvManualMapping = [];

        try {
            $suggestions = $this->mappingService->guessColumnMapping($this->csvParsedHeaders);

            foreach ($this->csvParsedHeaders as $index => $header) {
                $suggestion = $suggestions[$header] ?? null;
                $ppmField = $suggestion['ppm_field'] ?? null;

                $this->csvDetectedMapping[$index] = $ppmField;
                $this->csvManualMapping[$index] = $ppmField ?? '';
            }

            Log::debug('ImportModalCsvModeTrait: applyCsvAutoMapping', [
                'mapped_count' => count(array_filter($this->csvDetectedMapping)),
                'total_headers' => count($this->csvParsedHeaders),
            ]);
        } catch (\Exception $e) {
            Log::error('ImportModalCsvModeTrait: applyCsvAutoMapping FAILED - fallback to manual mapping', [
                'error' => $e->getMessage(),
                'headers' => $this->csvParsedHeaders,
            ]);

            // Fallback: initialize empty manual mapping (user must map manually)
            foreach ($this->csvParsedHeaders as $index => $header) {
                $this->csvDetectedMapping[$index] = null;
                $this->csvManualMapping[$index] = '';
            }
        }
    }

    /**
     * Manually set mapping for a specific column
     */
    public function setCsvManualMapping(int $colIndex, string $ppmField): void
    {
        // Clear field if empty
        if ($ppmField === '' || $ppmField === '--') {
            $this->csvManualMapping[$colIndex] = '';
            return;
        }

        // Clear any other column that was mapped to the same field
        foreach ($this->csvManualMapping as $idx => $field) {
            if ($field === $ppmField && $idx !== $colIndex) {
                $this->csvManualMapping[$idx] = '';
            }
        }

        $this->csvManualMapping[$colIndex] = $ppmField;

        Log::debug('ImportModalCsvModeTrait: setCsvManualMapping', [
            'col_index' => $colIndex,
            'ppm_field' => $ppmField,
        ]);
    }

    /**
     * Confirm mapping and move to preview step
     *
     * Validates that SKU column is mapped.
     */
    public function confirmCsvMapping(): void
    {
        $hasSkuMapping = in_array('sku', array_values($this->csvManualMapping), true);

        if (!$hasSkuMapping) {
            $this->addError('csvMapping', 'Musisz zmapowac kolumne SKU (wymagane)');
            return;
        }

        // Build preview headers (only mapped columns)
        $this->csvPreviewHeaders = [];
        $mappedFields = [];
        foreach ($this->csvManualMapping as $colIndex => $ppmField) {
            if (!empty($ppmField)) {
                $label = self::CSV_MAPPABLE_FIELDS[$ppmField] ?? $ppmField;
                $this->csvPreviewHeaders[$ppmField] = $label;
                $mappedFields[$colIndex] = $ppmField;
            }
        }

        // Build preview rows (max 10)
        $this->csvPreviewRows = [];
        $previewLimit = min(10, count($this->csvParsedRows));
        for ($i = 0; $i < $previewLimit; $i++) {
            $row = [];
            foreach ($mappedFields as $colIndex => $ppmField) {
                $row[$ppmField] = $this->csvParsedRows[$i][$colIndex] ?? '';
            }
            $this->csvPreviewRows[] = $row;
        }

        $this->csvPreviewStep = true;

        Log::debug('ImportModalCsvModeTrait: confirmCsvMapping - moving to preview', [
            'mapping' => $this->csvManualMapping,
            'preview_headers' => $this->csvPreviewHeaders,
            'preview_rows_count' => count($this->csvPreviewRows),
        ]);
    }

    /**
     * Import CSV rows as PendingProducts
     *
     * Creates ImportSession, then PendingProduct for each valid row.
     * Applies switches from ImportModalSwitchesTrait.
     */
    public function importCsvRows(): void
    {
        if (empty($this->csvParsedRows)) {
            $this->addError('csvImport', 'Brak danych do zaimportowania');
            return;
        }

        Log::debug('ImportModalCsvModeTrait: importCsvRows CALLED', [
            'rows_count' => count($this->csvParsedRows),
            'mapping' => $this->csvManualMapping,
        ]);

        try {
            DB::beginTransaction();

            $userId = Auth::id() ?? 8;

            $session = ImportSession::create([
                'uuid' => Str::uuid()->toString(),
                'session_name' => 'Modal Import (CSV) ' . now()->format('Y-m-d H:i'),
                'import_method' => 'modal_import',
                'status' => ImportSession::STATUS_PARSING,
                'imported_by' => $userId,
                'total_rows' => count($this->csvParsedRows),
                'products_created' => 0,
                'products_published' => 0,
                'products_failed' => 0,
                'products_skipped' => 0,
            ]);

            $createdCount = 0;
            $skippedCount = 0;
            $errors = [];

            foreach ($this->csvParsedRows as $rowIndex => $row) {
                $mappedRow = $this->mapCsvRowToFields($row);

                $sku = strtoupper(trim($mappedRow['sku'] ?? ''));

                if (empty($sku)) {
                    $skippedCount++;
                    $errors[] = "Wiersz " . ($rowIndex + 2) . ": brak SKU";
                    continue;
                }

                // Check for duplicates in DB
                $existsInProducts = \App\Models\Product::where('sku', $sku)->exists();
                $existsInPending = PendingProduct::where('sku', $sku)
                    ->whereNull('published_at')
                    ->exists();

                if ($existsInProducts || $existsInPending) {
                    $skippedCount++;
                    continue;
                }

                $product = PendingProduct::create([
                    'import_session_id' => $session->id,
                    'sku' => $sku,
                    'name' => $mappedRow['name'] ?? null,
                    'supplier_code' => $mappedRow['supplier_code'] ?? null,
                    'ean' => $mappedRow['ean'] ?? null,
                    'cn_code' => $mappedRow['cn_code'] ?? null,
                    'material' => $mappedRow['material'] ?? null,
                    'defect_symbol' => $mappedRow['defect_symbol'] ?? null,
                    'application' => $mappedRow['application'] ?? null,
                    'imported_by' => $userId,
                    'imported_at' => now(),
                ]);

                // Resolve and set relationship IDs
                $this->resolveRelationshipFields($product, $mappedRow);

                // Apply switches
                $this->applySwitchesToProduct($product);
                $product->save();

                $createdCount++;
            }

            $session->markAsReady($createdCount, $skippedCount);

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $session->addError('csv_parse', $error);
                }
            }

            DB::commit();

            Log::info('ImportModalCsvModeTrait: importCsvRows COMPLETED', [
                'session_id' => $session->id,
                'created' => $createdCount,
                'skipped' => $skippedCount,
                'errors_count' => count($errors),
            ]);

            $this->dispatch('importCompleted', count: $createdCount);

            // Close modal after successful import
            $this->closeModal();

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('ImportModalCsvModeTrait: importCsvRows FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addError('csvImport', 'Blad podczas importu: ' . $e->getMessage());
        }
    }

    /**
     * Map a single CSV row (indexed array) to PPM field names using manual mapping
     */
    protected function mapCsvRowToFields(array $row): array
    {
        $mapped = [];

        foreach ($this->csvManualMapping as $colIndex => $ppmField) {
            if (!empty($ppmField) && isset($row[$colIndex])) {
                $mapped[$ppmField] = trim($row[$colIndex]);
            }
        }

        return $mapped;
    }

    /**
     * Resolve text-based relationship fields to IDs
     *
     * Maps: product_type => product_type_id, supplier => supplier_id,
     *       manufacturer => manufacturer_id, importer => importer_id
     */
    protected function resolveRelationshipFields(PendingProduct $product, array $mappedRow): void
    {
        // Product type by name
        if (!empty($mappedRow['product_type'])) {
            $productType = \App\Models\ProductType::where('name', 'like', '%' . $mappedRow['product_type'] . '%')
                ->active()
                ->first();

            if ($productType) {
                $product->product_type_id = $productType->id;
            }
        }

        // Manufacturer by name
        if (!empty($mappedRow['manufacturer'])) {
            $manufacturer = \App\Models\BusinessPartner::manufacturers()
                ->active()
                ->where('name', 'like', '%' . $mappedRow['manufacturer'] . '%')
                ->first();

            if ($manufacturer) {
                $product->manufacturer_id = $manufacturer->id;
            }
        }

        // Supplier by name
        if (!empty($mappedRow['supplier'])) {
            $supplier = \App\Models\BusinessPartner::suppliers()
                ->active()
                ->where('name', 'like', '%' . $mappedRow['supplier'] . '%')
                ->first();

            if ($supplier) {
                $product->supplier_id = $supplier->id;
            }
        }

        // Importer by name
        if (!empty($mappedRow['importer'])) {
            $importer = \App\Models\BusinessPartner::importers()
                ->active()
                ->where('name', 'like', '%' . $mappedRow['importer'] . '%')
                ->first();

            if ($importer) {
                $product->importer_id = $importer->id;
            }
        }
    }

    /**
     * Main entry point for parsing CSV data (text or file)
     */
    public function parseCsvData(): void
    {
        if ($this->csvFile) {
            $this->uploadCsvFile();
        } elseif (!empty(trim($this->csvTextInput))) {
            $this->parseCsvInput();
        } else {
            $this->addError('csvTextInput', 'Wklej dane CSV lub wybierz plik');
        }
    }

    /**
     * Go back from mapping step to input step
     */
    public function goBackToCsvInput(): void
    {
        $this->csvMappingStep = false;
        $this->csvPreviewStep = false;
    }

    /**
     * Go back from preview step to mapping step
     */
    public function goBackToCsvMapping(): void
    {
        $this->csvPreviewStep = false;
    }

    /**
     * Reset mapping to auto-detected values
     */
    public function resetCsvMapping(): void
    {
        $this->csvManualMapping = $this->csvDetectedMapping;
    }

    /**
     * Reset all CSV mode state
     */
    protected function resetCsvState(): void
    {
        $this->csvTextInput = '';
        $this->csvFile = null;
        $this->csvDetectedMapping = [];
        $this->csvManualMapping = [];
        $this->csvParsedRows = [];
        $this->csvParsedHeaders = [];
        $this->csvMappingStep = false;
        $this->csvPreviewStep = false;
        $this->csvTotalRows = 0;
        $this->csvSampleValues = [];
        $this->csvPreviewHeaders = [];
        $this->csvPreviewRows = [];
    }
}
