<?php

namespace App\Http\Livewire\Admin\CSV;

use App\Services\CSV\BulkOperationService;
use App\Services\CSV\ErrorReporter;
use App\Services\CSV\ImportMapper;
use App\Services\CSV\ImportValidator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Import Preview Livewire Component
 *
 * Displays CSV import preview with:
 * - Column mapping detection
 * - Row validation
 * - Conflict resolution UI
 * - Error reporting
 *
 * Workflow:
 * 1. Upload CSV file
 * 2. Auto-detect columns and map to fields
 * 3. Validate first 10 rows (preview)
 * 4. Show conflicts and errors
 * 5. User confirms or adjusts mappings
 * 6. Process full import
 */
class ImportPreview extends Component
{
    use WithFileUploads;

    /**
     * Component state
     */
    public $csvFile;
    public $importType; // variants, features, compatibility
    public $step = 'upload'; // upload, preview, processing, complete

    /**
     * Import data
     */
    public $csvData = [];
    public $headerRow = [];
    public $columnMappings = [];
    public $previewRows = [];
    public $validationErrors = [];
    public $conflicts = [];

    /**
     * Statistics
     */
    public $totalRows = 0;
    public $validRows = 0;
    public $errorRows = 0;
    public $conflictRows = 0;

    /**
     * Conflict resolution
     */
    public $conflictResolution = 'skip'; // skip, overwrite, update

    /**
     * Services
     */
    protected ?ImportMapper $mapper = null;
    protected ?ImportValidator $validator = null;
    protected ?ErrorReporter $errorReporter = null;
    protected ?BulkOperationService $bulkService = null;

    /**
     * Boot component lifecycle - initialize services
     *
     * Called BEFORE any user interaction (mount, hydrate, property updates)
     * Ensures services are available for updatedCsvFile() and other methods
     */
    public function boot()
    {
        // Initialize services if not already set
        if ($this->mapper === null) {
            $this->mapper = app(ImportMapper::class);
        }
        if ($this->validator === null) {
            $this->validator = app(ImportValidator::class);
        }
        if ($this->errorReporter === null) {
            $this->errorReporter = app(ErrorReporter::class);
        }
        if ($this->bulkService === null) {
            $this->bulkService = app(BulkOperationService::class);
        }
    }

    /**
     * Mount component
     */
    public function mount(string $importType = 'variants')
    {
        $this->importType = $importType;
    }

    /**
     * Handle file upload
     */
    public function updatedCsvFile()
    {
        $this->validate([
            'csvFile' => 'required|file|mimes:csv,txt,xlsx|max:10240', // 10MB max
        ], [
            'csvFile.required' => 'Plik CSV jest wymagany.',
            'csvFile.mimes' => 'Dozwolone formaty: CSV, TXT, XLSX.',
            'csvFile.max' => 'Maksymalny rozmiar pliku to 10MB.',
        ]);

        try {
            // Parse CSV file
            $this->parseCsvFile();

            // Detect columns
            $this->columnMappings = $this->mapper->detectColumns($this->headerRow);

            // Validate mappings
            $missingColumns = $this->mapper->getMissingRequiredColumns(
                $this->columnMappings,
                $this->importType
            );

            if (!empty($missingColumns)) {
                session()->flash('error', 'Brakujące wymagane kolumny: ' . implode(', ', $missingColumns));
                $this->step = 'upload';
                return;
            }

            // Preview first 10 rows
            $this->previewRows = array_slice($this->csvData, 0, 10);

            // Validate preview rows
            $this->validatePreviewRows();

            // Move to preview step
            $this->step = 'preview';

            Log::info('ImportPreview: File uploaded and parsed', [
                'import_type' => $this->importType,
                'total_rows' => $this->totalRows,
                'detected_columns' => count($this->columnMappings),
            ]);
        } catch (\Exception $e) {
            Log::error('ImportPreview: File upload failed', [
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Błąd parsowania pliku: ' . $e->getMessage());
            $this->step = 'upload';
        }
    }

    /**
     * Parse CSV file
     */
    protected function parseCsvFile(): void
    {
        $path = $this->csvFile->getRealPath();

        $file = fopen($path, 'r');

        // Read header row
        $this->headerRow = fgetcsv($file, 0, ';');

        // Remove UTF-8 BOM if present
        if (isset($this->headerRow[0])) {
            $this->headerRow[0] = $this->removeBom($this->headerRow[0]);
        }

        // Read data rows
        $this->csvData = [];
        while (($row = fgetcsv($file, 0, ';')) !== false) {
            if (count($row) === count($this->headerRow)) {
                // Map row to associative array
                $mappedRow = array_combine($this->headerRow, $row);
                $this->csvData[] = $mappedRow;
            }
        }

        fclose($file);

        $this->totalRows = count($this->csvData);
    }

    /**
     * Remove UTF-8 BOM from string
     */
    protected function removeBom(string $text): string
    {
        if (substr($text, 0, 3) === chr(0xEF) . chr(0xBB) . chr(0xBF)) {
            return substr($text, 3);
        }

        return $text;
    }

    /**
     * Validate preview rows
     */
    protected function validatePreviewRows(): void
    {
        $this->validationErrors = [];
        $this->validRows = 0;
        $this->errorRows = 0;

        foreach ($this->previewRows as $index => $row) {
            // Map row to model data
            $mappedData = $this->mapper->mapToModel($row, $this->columnMappings);

            // Validate row
            [$isValid, $errors] = $this->validator->validateRow(
                $mappedData,
                $this->importType,
                $index + 2 // +2 for header row and 1-based indexing
            );

            if (!$isValid) {
                $this->validationErrors[$index] = $errors;
                $this->errorRows++;
            } else {
                $this->validRows++;
            }
        }
    }

    /**
     * Process full import
     */
    public function processImport()
    {
        $this->step = 'processing';

        try {
            Log::info('ImportPreview: Starting full import', [
                'import_type' => $this->importType,
                'total_rows' => $this->totalRows,
            ]);

            // Map all rows
            $mappedData = [];
            foreach ($this->csvData as $row) {
                $mappedData[] = $this->mapper->mapToModel($row, $this->columnMappings);
            }

            // Validate all rows
            $this->errorReporter->setTotalRows($this->totalRows);

            [$isValid, $allErrors] = $this->validator->validateCsvData($mappedData, $this->importType);

            if (!$isValid) {
                foreach ($allErrors as $rowNumber => $errors) {
                    $this->errorReporter->trackRowErrors($rowNumber, $errors);
                }

                // Generate error report
                $errorReportPath = $this->errorReporter->generateErrorReport(
                    'import_errors_' . date('Y-m-d_H-i-s')
                );

                session()->flash('error', 'Import zawiera błędy. Pobierz raport błędów.');
                session()->flash('error_report_path', $errorReportPath);

                $this->step = 'preview';
                return;
            }

            // Process import based on type
            $result = match ($this->importType) {
                'variants' => $this->bulkService->bulkCreateVariants($mappedData),
                'compatibility' => $this->bulkService->bulkAddCompatibility($mappedData, 'add'),
                default => ['success' => 0, 'failed' => 0, 'errors' => []],
            };

            $this->validRows = $result['success'];
            $this->errorRows = $result['failed'];

            $this->step = 'complete';

            Log::info('ImportPreview: Full import completed', [
                'success_count' => $this->validRows,
                'failed_count' => $this->errorRows,
            ]);

            session()->flash('success', "Import zakończony. Zaimportowano {$this->validRows} wierszy.");
        } catch (\Exception $e) {
            Log::error('ImportPreview: Import failed', [
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Błąd importu: ' . $e->getMessage());
            $this->step = 'preview';
        }
    }

    /**
     * Reset component
     */
    public function resetImport()
    {
        $this->csvFile = null;
        $this->csvData = [];
        $this->headerRow = [];
        $this->columnMappings = [];
        $this->previewRows = [];
        $this->validationErrors = [];
        $this->conflicts = [];
        $this->totalRows = 0;
        $this->validRows = 0;
        $this->errorRows = 0;
        $this->conflictRows = 0;
        $this->step = 'upload';

        $this->errorReporter->clearErrors();
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.admin.csv.import-preview')
            ->layout('layouts.admin', [
                'title' => 'CSV Import - PPM'
            ]);
    }
}
