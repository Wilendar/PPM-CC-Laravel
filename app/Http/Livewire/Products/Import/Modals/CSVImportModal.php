<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\Import\Modals;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use App\Services\Import\CsvParserService;
use App\Services\Import\ExcelParserService;
use App\Services\Import\ColumnMappingService;
use App\Services\Import\BatchImportProcessor;
use App\Models\ImportSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CSVImportModal - Modal importu CSV/Excel
 *
 * ETAP_06 FAZA 4 - Import CSV/Excel
 *
 * Workflow:
 * 1. Upload file -> parseFile()
 * 2. Auto-detect columns -> applyAutoMapping()
 * 3. User adjusts mapping (optional)
 * 4. Preview -> validateMapping()
 * 5. Import -> processBatch()
 *
 * Uses Traits:
 * - CsvFileUploadTrait - file upload handling
 * - CsvColumnMappingTrait - mapping logic
 * - CsvPreviewTrait - preview rendering
 *
 * @package App\Http\Livewire\Products\Import\Modals
 */
class CSVImportModal extends Component
{
    use WithFileUploads;
    use Traits\CsvFileUploadTrait;
    use Traits\CsvColumnMappingTrait;
    use Traits\CsvPreviewTrait;

    // === FILE UPLOAD ===

    /**
     * Uploaded file (Livewire temporary file)
     */
    #[Validate(['nullable', 'file', 'mimes:csv,xlsx,xls,txt', 'max:51200'])] // 50MB
    public $uploadedFile = null;

    /**
     * File type detected ('csv' or 'excel')
     */
    public string $fileType = '';

    /**
     * Upload in progress flag
     */
    public bool $isUploading = false;

    /**
     * Upload progress (0-100%)
     */
    public int $uploadProgress = 0;

    // === PARSED DATA ===

    /**
     * Parsed data from CSV/Excel
     */
    public array $parsedData = [
        'headers' => [],
        'rows' => [],
        'total_rows' => 0,
        'detected_delimiter' => '',
        'detected_encoding' => '',
        'sheet_name' => '',
    ];

    // === COLUMN MAPPING ===

    /**
     * Column mapping: Excel column => PPM field
     */
    public array $columnMapping = [];

    /**
     * Auto-mapping suggestions: Excel column => {ppm_field, confidence, suggestions}
     */
    public array $autoMappingSuggestions = [];

    // === PREVIEW ===

    /**
     * Preview rows (first 5 for display)
     */
    public array $previewRows = [];

    // === IMPORT PROCESSING ===

    /**
     * Import processing flag
     */
    public bool $isProcessing = false;

    /**
     * Import progress (0-100%)
     */
    public int $importProgress = 0;

    /**
     * Import result statistics
     */
    public array $importResult = [
        'created' => 0,
        'skipped' => 0,
        'errors' => [],
    ];

    // === WIZARD STATE ===

    /**
     * Current wizard step: upload, mapping, preview, result
     */
    public string $currentStep = 'upload';

    // === MODAL STATE ===

    /**
     * Modal visibility
     */
    public bool $showModal = false;

    // === ERRORS ===

    /**
     * Parsing/validation errors
     */
    public array $errors = [];

    // === SERVICES ===

    protected CsvParserService $csvParser;
    protected ExcelParserService $excelParser;
    protected ColumnMappingService $mappingService;
    protected BatchImportProcessor $batchProcessor;

    /**
     * Boot - inject services
     */
    public function boot(
        CsvParserService $csvParser,
        ExcelParserService $excelParser,
        ColumnMappingService $mappingService,
        BatchImportProcessor $batchProcessor
    ): void {
        $this->csvParser = $csvParser;
        $this->excelParser = $excelParser;
        $this->mappingService = $mappingService;
        $this->batchProcessor = $batchProcessor;
    }

    // === LIFECYCLE ===

    /**
     * Open modal event handler
     */
    #[On('openCsvImportModal')]
    public function openModal(): void
    {
        $this->showModal = true;
        $this->resetState();
    }

    /**
     * Reset state when modal opens
     */
    public function resetState(): void
    {
        $this->uploadedFile = null;
        $this->fileType = '';
        $this->parsedData = [
            'headers' => [],
            'rows' => [],
            'total_rows' => 0,
            'detected_delimiter' => '',
            'detected_encoding' => '',
            'sheet_name' => '',
        ];
        $this->columnMapping = [];
        $this->autoMappingSuggestions = [];
        $this->previewRows = [];
        $this->isUploading = false;
        $this->isProcessing = false;
        $this->uploadProgress = 0;
        $this->importProgress = 0;
        $this->importResult = [
            'created' => 0,
            'skipped' => 0,
            'errors' => [],
        ];
        $this->currentStep = 'upload';
        $this->errors = [];
    }

    // === WIZARD NAVIGATION ===

    /**
     * Go to mapping step
     */
    public function goToMapping(): void
    {
        if (empty($this->parsedData['headers'])) {
            $this->errors[] = 'Najpierw wgraj plik';
            return;
        }

        $this->errors = [];
        $this->currentStep = 'mapping';
    }

    /**
     * Go to preview step
     */
    public function goToPreview(): void
    {
        $validation = $this->validateMapping();

        if (!$validation['valid']) {
            $this->errors = $validation['errors'];
            return;
        }

        $this->errors = [];
        $this->currentStep = 'preview';
    }

    /**
     * Go back to previous step
     */
    public function goBack(): void
    {
        $this->errors = [];

        if ($this->currentStep === 'mapping') {
            $this->currentStep = 'upload';
        } elseif ($this->currentStep === 'preview') {
            $this->currentStep = 'mapping';
        } elseif ($this->currentStep === 'result') {
            // Close modal on result back
            $this->close();
        }
    }

    // === IMPORT ===

    /**
     * Import - create PendingProduct records via batch processor
     */
    public function import(): void
    {
        // Validate mapping
        $validation = $this->validateMapping();

        if (!$validation['valid']) {
            $this->errors = $validation['errors'];
            return;
        }

        $this->isProcessing = true;
        $this->importProgress = 0;
        $this->errors = [];

        try {
            // Create import session
            $session = ImportSession::create([
                'uuid' => Str::uuid()->toString(),
                'session_name' => 'CSV Import ' . now()->format('Y-m-d H:i'),
                'import_method' => $this->fileType === 'csv'
                    ? ImportSession::METHOD_CSV
                    : ImportSession::METHOD_EXCEL,
                'import_source_file' => $this->uploadedFile?->getClientOriginalName() ?? 'unknown',
                'status' => ImportSession::STATUS_PARSING,
                'imported_by' => Auth::id(),
            ]);

            Log::info('CSVImportModal: starting import', [
                'session_id' => $session->id,
                'file_type' => $this->fileType,
                'total_rows' => $this->parsedData['total_rows'],
            ]);

            // Map rows (Excel column names -> PPM field values)
            $mappedRows = $this->getMappedRows();

            // Process in batch
            $result = $this->batchProcessor->processBatch($mappedRows, $session);

            $this->importResult = $result;
            $this->importProgress = 100;
            $this->currentStep = 'result';

            Log::info('CSVImportModal: import completed', [
                'session_id' => $session->id,
                'created' => $result['created'],
                'skipped' => $result['skipped'],
            ]);

            // Dispatch event to parent panel (pass count as first argument)
            $this->dispatch('csvImportCompleted', count: $result['created']);

        } catch (\Exception $e) {
            Log::error('CSVImportModal: import failed', [
                'error' => $e->getMessage(),
            ]);

            $this->errors[] = 'Blad podczas importu: ' . $e->getMessage();
        } finally {
            $this->isProcessing = false;
        }
    }

    // === MODAL CONTROL ===

    /**
     * Close modal
     */
    public function close(): void
    {
        $this->showModal = false;
        $this->resetState();
    }

    // === GETTERS ===

    /**
     * Get current step index (for progress indicator)
     */
    public function getStepIndex(): int
    {
        return match ($this->currentStep) {
            'upload' => 1,
            'mapping' => 2,
            'preview' => 3,
            'result' => 4,
            default => 1,
        };
    }

    /**
     * Check if can proceed to next step
     */
    public function canProceed(): bool
    {
        return match ($this->currentStep) {
            'upload' => !empty($this->parsedData['headers']),
            'mapping' => $this->isSkuMapped(),
            'preview' => true,
            default => false,
        };
    }

    /**
     * Get button text for current step
     */
    public function getNextButtonText(): string
    {
        return match ($this->currentStep) {
            'upload' => 'Dalej: Mapowanie kolumn',
            'mapping' => 'Dalej: Podglad',
            'preview' => sprintf('Importuj %d produktow', count($this->getMappedRows())),
            'result' => 'Zamknij',
            default => 'Dalej',
        };
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.products.import.modals.csv-import-modal', [
            'availablePPMFields' => $this->mappingService->getAvailablePPMFields(),
        ]);
    }
}
