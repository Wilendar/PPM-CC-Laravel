<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\Import\Modals\Traits;

use Illuminate\Support\Facades\Log;

/**
 * CsvFileUploadTrait - file upload handling for CSVImportModal
 *
 * ETAP_06 FAZA 4 - Import CSV/Excel
 *
 * Features:
 * - File type detection (csv/excel)
 * - Auto-parsing after upload
 * - Size validation (50MB limit)
 * - Progress tracking
 *
 * @package App\Http\Livewire\Products\Import\Modals\Traits
 */
trait CsvFileUploadTrait
{
    /**
     * Triggered when uploadedFile property is updated by Livewire
     * Automatically parses the file after upload completes
     */
    public function updatedUploadedFile(): void
    {
        if (!$this->uploadedFile) {
            return;
        }

        $this->isUploading = true;
        $this->uploadProgress = 50;
        $this->errors = [];

        try {
            // Validate file size
            $validation = $this->validateFileSize();
            if (!$validation['valid']) {
                $this->errors = $validation['errors'];
                $this->isUploading = false;
                return;
            }

            $this->uploadProgress = 75;

            // Detect file type
            $this->fileType = $this->detectFileType();

            $this->uploadProgress = 100;

            // Parse file
            $this->parseFile();

        } catch (\Exception $e) {
            Log::error('CsvFileUploadTrait: upload failed', [
                'error' => $e->getMessage(),
            ]);
            $this->errors[] = 'Blad podczas wczytywania pliku: ' . $e->getMessage();
        } finally {
            $this->isUploading = false;
        }
    }

    /**
     * Parse the uploaded file using appropriate parser
     */
    public function parseFile(): void
    {
        if (!$this->uploadedFile) {
            $this->errors[] = 'Brak pliku do parsowania';
            return;
        }

        $this->errors = [];

        try {
            if ($this->fileType === 'csv') {
                // Parse CSV
                $this->parsedData = $this->csvParser->parseCSV($this->uploadedFile);

                Log::debug('CsvFileUploadTrait: CSV parsed', [
                    'headers' => count($this->parsedData['headers'] ?? []),
                    'rows' => $this->parsedData['total_rows'] ?? 0,
                    'delimiter' => $this->parsedData['detected_delimiter'] ?? '',
                    'encoding' => $this->parsedData['detected_encoding'] ?? '',
                ]);
            } else {
                // Parse Excel
                $this->parsedData = $this->excelParser->parseExcel($this->uploadedFile);

                Log::debug('CsvFileUploadTrait: Excel parsed', [
                    'headers' => count($this->parsedData['headers'] ?? []),
                    'rows' => $this->parsedData['total_rows'] ?? 0,
                    'sheet' => $this->parsedData['sheet_name'] ?? '',
                ]);
            }

            // Validate structure
            $structureValidation = $this->fileType === 'csv'
                ? $this->csvParser->validateStructure($this->parsedData)
                : $this->excelParser->validateStructure($this->parsedData);

            if (!$structureValidation['valid']) {
                $this->errors = $structureValidation['errors'];
                return;
            }

            // Auto-mapping after successful parse
            $this->applyAutoMapping();

            // Update preview
            $this->updatePreview();

        } catch (\InvalidArgumentException $e) {
            $this->errors[] = $e->getMessage();
        } catch (\Exception $e) {
            Log::error('CsvFileUploadTrait: parse failed', [
                'error' => $e->getMessage(),
                'file_type' => $this->fileType,
            ]);
            $this->errors[] = 'Blad podczas parsowania pliku: ' . $e->getMessage();
        }
    }

    /**
     * Validate file size (50MB limit)
     *
     * @return array{valid: bool, errors: array}
     */
    public function validateFileSize(): array
    {
        $errors = [];
        $maxSize = 50 * 1024 * 1024; // 50MB in bytes

        if (!$this->uploadedFile) {
            $errors[] = 'Brak pliku do walidacji';
            return ['valid' => false, 'errors' => $errors];
        }

        $fileSize = $this->uploadedFile->getSize();

        if ($fileSize > $maxSize) {
            $sizeMB = round($fileSize / (1024 * 1024), 2);
            $errors[] = sprintf('Plik jest za duzy (%s MB). Maksymalny rozmiar to 50 MB.', $sizeMB);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Detect file type based on extension
     *
     * @return string 'csv' | 'excel'
     */
    public function detectFileType(): string
    {
        if (!$this->uploadedFile) {
            return 'csv';
        }

        $extension = strtolower($this->uploadedFile->getClientOriginalExtension());

        return match ($extension) {
            'csv' => 'csv',
            'xlsx', 'xls' => 'excel',
            default => 'csv',
        };
    }

    /**
     * Get uploaded file name for display
     *
     * @return string
     */
    public function getUploadedFileName(): string
    {
        return $this->uploadedFile?->getClientOriginalName() ?? '';
    }

    /**
     * Get uploaded file size for display
     *
     * @return string
     */
    public function getUploadedFileSize(): string
    {
        if (!$this->uploadedFile) {
            return '0 KB';
        }

        $bytes = $this->uploadedFile->getSize();

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }

        return round($bytes / 1024, 2) . ' KB';
    }

    /**
     * Clear uploaded file and reset state
     */
    public function clearUploadedFile(): void
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
        $this->errors = [];
    }
}
