<?php

namespace App\Services\CSV;

use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

/**
 * CSV Export Formatter Service
 *
 * Formats export data for user-friendly CSV/Excel files.
 *
 * Features:
 * - Multi-sheet Excel support (XLSX)
 * - Polish localization (headers, date/boolean formats)
 * - UTF-8 BOM for Excel compatibility
 * - ZIP compression for large exports
 * - Formatted dates, booleans, and prices
 */
class ExportFormatter
{
    /**
     * Format data for Excel export (multi-sheet XLSX)
     *
     * @param array $sheets Array of sheets [sheet_name => [rows]]
     * @param string $filename Output filename (without extension)
     * @return string Path to generated XLSX file
     */
    public function formatForExcel(array $sheets, string $filename): string
    {
        Log::info('ExportFormatter: Formatting data for Excel', [
            'sheet_count' => count($sheets),
            'filename' => $filename,
        ]);

        $spreadsheet = new Spreadsheet();

        $sheetIndex = 0;
        foreach ($sheets as $sheetName => $rows) {
            if ($sheetIndex === 0) {
                $worksheet = $spreadsheet->getActiveSheet();
            } else {
                $worksheet = $spreadsheet->createSheet();
            }

            $worksheet->setTitle($sheetName);

            // Write rows to worksheet
            $rowNumber = 1;
            foreach ($rows as $row) {
                $columnLetter = 'A';
                foreach ($row as $cellValue) {
                    $worksheet->setCellValue($columnLetter . $rowNumber, $cellValue);
                    $columnLetter++;
                }
                $rowNumber++;
            }

            // Auto-size columns
            if (!empty($rows)) {
                $columnCount = count($rows[0]);
                for ($col = 0; $col < $columnCount; $col++) {
                    $columnLetter = chr(65 + $col); // A, B, C, ...
                    $worksheet->getColumnDimension($columnLetter)->setAutoSize(true);
                }
            }

            // Style header row (bold)
            $worksheet->getStyle('A1:Z1')->getFont()->setBold(true);

            $sheetIndex++;
        }

        // Save to temp file
        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $filePath = $tempDir . '/' . $filename . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        Log::info('ExportFormatter: Excel file created', [
            'file_path' => $filePath,
            'file_size' => filesize($filePath),
        ]);

        return $filePath;
    }

    /**
     * Format data for CSV export (single sheet with UTF-8 BOM)
     *
     * @param array $rows Array of rows
     * @param string $filename Output filename (without extension)
     * @return string Path to generated CSV file
     */
    public function formatForCsv(array $rows, string $filename): string
    {
        Log::info('ExportFormatter: Formatting data for CSV', [
            'row_count' => count($rows),
            'filename' => $filename,
        ]);

        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $filePath = $tempDir . '/' . $filename . '.csv';

        // Open file with UTF-8 BOM for Excel compatibility
        $file = fopen($filePath, 'w');

        // Write UTF-8 BOM
        fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Write rows
        foreach ($rows as $row) {
            fputcsv($file, $row, ';'); // Use semicolon for Polish Excel
        }

        fclose($file);

        Log::info('ExportFormatter: CSV file created', [
            'file_path' => $filePath,
            'file_size' => filesize($filePath),
        ]);

        return $filePath;
    }

    /**
     * Format boolean value for export (TAK/NIE)
     *
     * @param mixed $value Boolean value
     * @return string "TAK" or "NIE"
     */
    public function formatBoolean($value): string
    {
        return $value ? 'TAK' : 'NIE';
    }

    /**
     * Format price for export (Polish format with 2 decimals)
     *
     * @param float|null $value Price value
     * @return string Formatted price (e.g., "123,45 zł")
     */
    public function formatPrice(?float $value): string
    {
        if ($value === null) {
            return '';
        }

        return number_format($value, 2, ',', ' ') . ' zl';
    }

    /**
     * Format date for export (Y-m-d format)
     *
     * @param mixed $value Date value (string, Carbon, DateTime)
     * @return string Formatted date or empty string
     */
    public function formatDate($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value)) {
            try {
                $date = new \DateTime($value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                return $value;
            }
        }

        if ($value instanceof \DateTime || $value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return '';
    }

    /**
     * Format datetime for export (Y-m-d H:i:s format)
     *
     * @param mixed $value Datetime value
     * @return string Formatted datetime or empty string
     */
    public function formatDateTime($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value)) {
            try {
                $date = new \DateTime($value);
                return $date->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return $value;
            }
        }

        if ($value instanceof \DateTime || $value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return '';
    }

    /**
     * Create ZIP archive from multiple files
     *
     * @param array $files Array of file paths to include
     * @param string $zipFilename Output ZIP filename (without extension)
     * @return string Path to created ZIP file
     */
    public function createZip(array $files, string $zipFilename): string
    {
        Log::info('ExportFormatter: Creating ZIP archive', [
            'file_count' => count($files),
            'zip_filename' => $zipFilename,
        ]);

        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = $tempDir . '/' . $zipFilename . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Log::error('ExportFormatter: Failed to create ZIP archive', ['zip_path' => $zipPath]);
            throw new \RuntimeException("Cannot create ZIP archive: {$zipPath}");
        }

        foreach ($files as $file) {
            if (file_exists($file)) {
                $zip->addFile($file, basename($file));
            } else {
                Log::warning('ExportFormatter: File not found for ZIP', ['file' => $file]);
            }
        }

        $zip->close();

        Log::info('ExportFormatter: ZIP archive created', [
            'zip_path' => $zipPath,
            'zip_size' => filesize($zipPath),
        ]);

        return $zipPath;
    }

    /**
     * Check if export should be compressed (based on row count)
     *
     * @param int $rowCount Total row count
     * @param int $threshold Compression threshold (default: 1000 rows)
     * @return bool True if should compress
     */
    public function shouldCompress(int $rowCount, int $threshold = 1000): bool
    {
        return $rowCount > $threshold;
    }

    /**
     * Clean up temporary files
     *
     * @param array $filePaths Array of file paths to delete
     * @return void
     */
    public function cleanupTempFiles(array $filePaths): void
    {
        Log::info('ExportFormatter: Cleaning up temporary files', ['file_count' => count($filePaths)]);

        foreach ($filePaths as $filePath) {
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info('ExportFormatter: Deleted temp file', ['file_path' => $filePath]);
            }
        }
    }

    /**
     * Format variant data for export
     *
     * @param \App\Models\ProductVariant $variant
     * @return array Formatted row data
     */
    public function formatVariantForExport($variant): array
    {
        $row = [
            'SKU' => $variant->sku,
            'Rodzic SKU' => $variant->product->sku ?? '',
            'Nazwa wariantu' => $variant->name,
            'Aktywny' => $this->formatBoolean($variant->is_active),
            'Domyslny' => $this->formatBoolean($variant->is_default),
            'Pozycja' => $variant->position ?? '',
        ];

        // Add attributes
        foreach ($variant->attributes as $attribute) {
            $columnName = 'Atrybut: ' . $attribute->attributeType->name;
            $row[$columnName] = $attribute->value;
        }

        // Add prices
        foreach ($variant->prices as $price) {
            $columnName = 'Cena: ' . $price->priceGroup->name;
            $row[$columnName] = $this->formatPrice($price->price);
        }

        // Add stock
        foreach ($variant->stock as $stock) {
            $columnName = 'Stan: ' . $stock->warehouse->name;
            $row[$columnName] = $stock->quantity;
        }

        // Add cover image
        $row['Zdjecie glowne'] = $variant->getCoverImage()?->url ?? '';

        return $row;
    }

    /**
     * Format feature data for export
     *
     * @param \App\Models\Product $product
     * @return array Formatted row data
     */
    public function formatFeaturesForExport($product): array
    {
        $row = [
            'SKU' => $product->sku,
        ];

        // Add features
        foreach ($product->features as $feature) {
            $columnName = 'Cecha: ' . $feature->featureType->name;

            if ($feature->featureType->unit) {
                $columnName .= ' (' . $feature->featureType->unit . ')';
            }

            if ($feature->featureType->value_type === 'bool') {
                $row[$columnName] = $this->formatBoolean($feature->value);
            } else {
                $row[$columnName] = $feature->value ?? $feature->custom_value;
            }
        }

        return $row;
    }

    /**
     * Format compatibility data for export
     *
     * @param \App\Models\VehicleCompatibility $compatibility
     * @return array Formatted row data
     */
    public function formatCompatibilityForExport($compatibility): array
    {
        return [
            'SKU' => $compatibility->part_sku,
            'Marka pojazdu' => $compatibility->vehicleModel->brand ?? '',
            'Model pojazdu' => $compatibility->vehicleModel->model ?? '',
            'Rok od' => $compatibility->vehicleModel->year_from ?? '',
            'Rok do' => $compatibility->vehicleModel->year_to ?? '',
            'SKU pojazdu' => $compatibility->vehicle_sku,
            'Typ dopasowania' => $compatibility->compatibilityAttribute->name ?? '',
            'Zrodlo' => $compatibility->compatibilitySource->name ?? '',
            'Zweryfikowane' => $this->formatBoolean($compatibility->is_verified),
            'Uwagi' => $compatibility->notes ?? '',
        ];
    }
}
