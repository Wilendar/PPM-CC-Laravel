<?php

declare(strict_types=1);

namespace App\Services\Import;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * ExcelParserService - parsowanie XLSX/XLS via PhpSpreadsheet
 *
 * ETAP_06 FAZA 4 - Import Excel
 *
 * Features:
 * - PhpSpreadsheet reader (setReadDataOnly dla performance)
 * - Sheet selection (multi-sheet support)
 * - Empty row filtering
 * - Memory optimization (readEmptyCells = false)
 * - Max 10k rows limit
 *
 * Reference: Laravel-Excel (maatwebsite/excel) already installed in project
 * Dependencies: phpoffice/phpspreadsheet (via Laravel-Excel)
 *
 * @package App\Services\Import
 */
class ExcelParserService
{
    /**
     * Max rows limit
     */
    public const MAX_ROWS = 10000;

    /**
     * Parse Excel file (XLSX or XLS)
     *
     * @param UploadedFile $file Uploaded Excel file
     * @param int $sheetIndex Sheet index to read (0-based)
     * @return array{headers: array, rows: array, sheet_name: string, total_rows: int}
     * @throws \InvalidArgumentException If file cannot be read
     */
    public function parseExcel(UploadedFile $file, int $sheetIndex = 0): array
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Nieprawidlowy plik upload');
        }

        $path = $file->getRealPath();

        Log::debug('Excel Parser: loading file', [
            'file' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'sheet_index' => $sheetIndex,
        ]);

        try {
            $reader = IOFactory::createReader($this->getReaderType($file));
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);

            $spreadsheet = $reader->load($path);
            $worksheet = $spreadsheet->getSheet($sheetIndex);

            return $this->extractDataFromWorksheet($worksheet);

        } catch (\Exception $e) {
            Log::error('Excel Parser: failed to read file', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
            ]);

            throw new \InvalidArgumentException('Nie mozna odczytac pliku Excel: ' . $e->getMessage());
        }
    }

    /**
     * Extract data from worksheet
     *
     * @param Worksheet $worksheet
     * @return array{headers: array, rows: array, sheet_name: string, total_rows: int}
     */
    protected function extractDataFromWorksheet(Worksheet $worksheet): array
    {
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();

        // Extract headers (row 1)
        $headers = [];
        $headerRow = $worksheet->rangeToArray('A1:' . $highestColumn . '1', null, true, false);

        if (!empty($headerRow[0])) {
            foreach ($headerRow[0] as $header) {
                $headerValue = trim((string) ($header ?? ''));
                if ($headerValue !== '') {
                    $headers[] = $headerValue;
                }
            }
        }

        if (empty($headers)) {
            throw new \InvalidArgumentException('Brak naglowkow kolumn w arkuszu Excel');
        }

        $headerCount = count($headers);

        // Extract data rows (row 2+)
        $rows = [];

        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = $worksheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, true, false);

            if (empty($rowData[0])) {
                continue;
            }

            $cellValues = $rowData[0];

            // Skip completely empty rows
            $hasData = false;
            foreach ($cellValues as $cell) {
                if (!empty(trim((string) ($cell ?? '')))) {
                    $hasData = true;
                    break;
                }
            }

            if (!$hasData) {
                continue;
            }

            // Combine with headers
            $rowAssoc = [];
            foreach ($headers as $index => $header) {
                $rowAssoc[$header] = trim((string) ($cellValues[$index] ?? ''));
            }

            $rows[] = $rowAssoc;

            // Safety limit
            if (count($rows) >= self::MAX_ROWS) {
                Log::warning('Excel Parser: row limit reached', ['limit' => self::MAX_ROWS]);
                break;
            }
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'sheet_name' => $worksheet->getTitle(),
            'total_rows' => count($rows),
        ];
    }

    /**
     * Get PhpSpreadsheet reader type from file extension
     *
     * @param UploadedFile $file
     * @return string
     */
    protected function getReaderType(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'xlsx' => 'Xlsx',
            'xls' => 'Xls',
            'csv' => 'Csv',
            default => 'Xlsx',
        };
    }

    /**
     * Get list of sheets in Excel file
     *
     * @param UploadedFile $file
     * @return array{index: int, name: string}[]
     */
    public function getSheetNames(UploadedFile $file): array
    {
        $path = $file->getRealPath();

        try {
            $reader = IOFactory::createReader($this->getReaderType($file));
            $reader->setReadDataOnly(true);

            $spreadsheet = $reader->load($path);
            $sheets = [];

            foreach ($spreadsheet->getAllSheets() as $index => $sheet) {
                $sheets[] = [
                    'index' => $index,
                    'name' => $sheet->getTitle(),
                ];
            }

            return $sheets;

        } catch (\Exception $e) {
            Log::error('Excel Parser: failed to get sheet names', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Validate Excel structure
     *
     * @param array $parsedData Output from parseExcel()
     * @return array{valid: bool, errors: array}
     */
    public function validateStructure(array $parsedData): array
    {
        $errors = [];

        if (empty($parsedData['headers'])) {
            $errors[] = 'Brak naglowkow kolumn w arkuszu Excel';
        }

        if (empty($parsedData['rows'])) {
            $errors[] = 'Brak danych w arkuszu Excel';
        }

        if (isset($parsedData['total_rows']) && $parsedData['total_rows'] >= self::MAX_ROWS) {
            $errors[] = sprintf('Arkusz zawiera wiecej niz %d wierszy (limit systemu)', self::MAX_ROWS);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get preview of first N rows
     *
     * @param array $parsedData
     * @param int $limit
     * @return array
     */
    public function getPreviewRows(array $parsedData, int $limit = 5): array
    {
        return array_slice($parsedData['rows'], 0, $limit);
    }
}
