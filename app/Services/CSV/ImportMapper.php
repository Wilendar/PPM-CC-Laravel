<?php

namespace App\Services\CSV;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CSV Import Mapper Service
 *
 * Handles column detection and mapping from CSV to database fields.
 * Supports flexible column name detection (e.g., "SKU", "Produkt SKU", "Product Code").
 *
 * Features:
 * - Auto-detect columns based on patterns
 * - Map CSV columns to model attributes
 * - Handle Polish and English column names
 * - Normalize column names for matching
 */
class ImportMapper
{
    /**
     * Column mapping patterns for auto-detection
     *
     * @var array<string, array<string>>
     */
    protected array $columnPatterns = [
        // Basic product fields
        'sku' => ['SKU', 'Produkt SKU', 'Product Code', 'Code', 'Kod produktu'],
        'parent_sku' => ['Rodzic SKU', 'Parent SKU', 'Parent Code', 'Produkt macierzysty'],
        'name' => ['Nazwa', 'Name', 'Nazwa wariantu', 'Variant Name', 'Product Name'],

        // Boolean fields
        'is_active' => ['Aktywny', 'Active', 'Status', 'Enabled'],
        'is_default' => ['Domyslny', 'Default', 'Glowny', 'Primary'],
        'is_verified' => ['Zweryfikowane', 'Verified', 'Checked'],

        // Numeric fields
        'position' => ['Pozycja', 'Position', 'Order', 'Sort'],
        'price' => ['Cena', 'Price', 'Amount'],
        'stock' => ['Stan', 'Stock', 'Quantity', 'Qty', 'Ilosc'],

        // Vehicle compatibility fields
        'vehicle_brand' => ['Marka pojazdu', 'Vehicle Brand', 'Brand', 'Marka'],
        'vehicle_model' => ['Model pojazdu', 'Vehicle Model', 'Model'],
        'vehicle_sku' => ['SKU pojazdu', 'Vehicle SKU', 'VIN'],
        'year_from' => ['Rok od', 'Year From', 'Start Year'],
        'year_to' => ['Rok do', 'Year To', 'End Year'],
        'engine_type' => ['Typ silnika', 'Engine Type'],
        'engine_cc' => ['Pojemnosc', 'Engine CC', 'Capacity'],

        // Compatibility attributes
        'compatibility_type' => ['Typ dopasowania', 'Compatibility Type', 'Type', 'Attribute'],
        'source' => ['Zrodlo', 'Source', 'Origin'],
        'notes' => ['Uwagi', 'Notes', 'Comments', 'Description'],

        // Images
        'cover_image' => ['Zdjecie glowne', 'Cover Image', 'Main Image', 'Image URL', 'Photo'],
    ];

    /**
     * Detect columns from CSV header row
     *
     * @param array $headerRow CSV header row
     * @return array Detected column mappings [csv_column => field_name]
     */
    public function detectColumns(array $headerRow): array
    {
        Log::info('ImportMapper: Detecting columns from CSV header', [
            'header_row' => $headerRow,
            'column_count' => count($headerRow),
        ]);

        $detectedMappings = [];

        foreach ($headerRow as $index => $csvColumn) {
            $normalizedColumn = $this->normalizeColumnName($csvColumn);

            // Check against patterns
            $detectedField = $this->matchColumnToField($normalizedColumn, $csvColumn);

            if ($detectedField) {
                $detectedMappings[$csvColumn] = $detectedField;
                Log::info('ImportMapper: Column detected', [
                    'csv_column' => $csvColumn,
                    'detected_field' => $detectedField,
                ]);
            } else {
                // Check for dynamic columns (attributes, features, prices, stock)
                $dynamicField = $this->detectDynamicColumn($csvColumn);
                if ($dynamicField) {
                    $detectedMappings[$csvColumn] = $dynamicField;
                    Log::info('ImportMapper: Dynamic column detected', [
                        'csv_column' => $csvColumn,
                        'dynamic_field' => $dynamicField,
                    ]);
                }
            }
        }

        Log::info('ImportMapper: Column detection completed', [
            'detected_count' => count($detectedMappings),
            'undetected_count' => count($headerRow) - count($detectedMappings),
        ]);

        return $detectedMappings;
    }

    /**
     * Map CSV row to model attributes
     *
     * @param array $csvRow CSV data row
     * @param array $columnMappings Column mappings from detectColumns()
     * @return array Mapped data [field_name => value]
     */
    public function mapToModel(array $csvRow, array $columnMappings): array
    {
        $mappedData = [];

        foreach ($columnMappings as $csvColumn => $fieldName) {
            // Find column index in CSV row
            $columnIndex = array_search($csvColumn, array_keys($csvRow));
            if ($columnIndex === false) {
                continue;
            }

            $value = $csvRow[$csvColumn] ?? null;

            // Apply transformations based on field type
            $mappedData[$fieldName] = $this->transformValue($value, $fieldName);
        }

        return $mappedData;
    }

    /**
     * Normalize column name for matching
     *
     * @param string $columnName Raw column name from CSV
     * @return string Normalized column name
     */
    protected function normalizeColumnName(string $columnName): string
    {
        // Remove accents, convert to lowercase, remove special chars
        $normalized = Str::ascii($columnName);
        $normalized = Str::lower($normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized);
        $normalized = trim($normalized, '_');

        return $normalized;
    }

    /**
     * Match CSV column to database field using patterns
     *
     * @param string $normalizedColumn Normalized column name
     * @param string $originalColumn Original column name
     * @return string|null Matched field name or null
     */
    protected function matchColumnToField(string $normalizedColumn, string $originalColumn): ?string
    {
        foreach ($this->columnPatterns as $fieldName => $patterns) {
            foreach ($patterns as $pattern) {
                $normalizedPattern = $this->normalizeColumnName($pattern);

                // Exact match
                if ($normalizedColumn === $normalizedPattern) {
                    return $fieldName;
                }

                // Partial match (column contains pattern)
                if (str_contains($normalizedColumn, $normalizedPattern)) {
                    return $fieldName;
                }
            }
        }

        return null;
    }

    /**
     * Detect dynamic column (attributes, features, prices, stock)
     *
     * @param string $csvColumn CSV column name
     * @return string|null Dynamic field identifier or null
     */
    protected function detectDynamicColumn(string $csvColumn): ?string
    {
        // Attribute columns: "Atrybut: Rozmiar" → attribute:rozmiar
        if (preg_match('/^Atrybut:\s*(.+)$/i', $csvColumn, $matches)) {
            $attributeName = trim($matches[1]);
            return 'attribute:' . Str::slug($attributeName);
        }

        // Feature columns: "Cecha: Moc (W)" → feature:moc
        if (preg_match('/^Cecha:\s*(.+?)(\s*\(.*\))?(\s*\[.*\])?$/i', $csvColumn, $matches)) {
            $featureName = trim($matches[1]);
            return 'feature:' . Str::slug($featureName);
        }

        // Price columns: "Cena: Detaliczna" → price:detaliczna
        if (preg_match('/^Cena:\s*(.+)$/i', $csvColumn, $matches)) {
            $priceGroupName = trim($matches[1]);
            return 'price:' . Str::slug($priceGroupName);
        }

        // Stock columns: "Stan: MPPTRADE" → stock:mpptrade
        if (preg_match('/^Stan:\s*(.+)$/i', $csvColumn, $matches)) {
            $warehouseName = trim($matches[1]);
            return 'stock:' . Str::slug($warehouseName);
        }

        return null;
    }

    /**
     * Transform value based on field type
     *
     * @param mixed $value Raw value from CSV
     * @param string $fieldName Field name
     * @return mixed Transformed value
     */
    protected function transformValue($value, string $fieldName)
    {
        // Handle null/empty values
        if ($value === null || $value === '') {
            return null;
        }

        // Boolean fields: TAK/NIE, YES/NO, 1/0
        if (in_array($fieldName, ['is_active', 'is_default', 'is_verified'])) {
            return $this->transformBoolean($value);
        }

        // Numeric fields
        if (in_array($fieldName, ['position', 'stock', 'year_from', 'year_to', 'engine_cc'])) {
            return $this->transformInteger($value);
        }

        // Price fields (handle Polish number format: 123,45)
        if (str_starts_with($fieldName, 'price:') || $fieldName === 'price') {
            return $this->transformDecimal($value);
        }

        // String fields - trim whitespace
        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }

    /**
     * Transform boolean value from CSV
     *
     * @param mixed $value
     * @return bool
     */
    protected function transformBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $stringValue = Str::upper(trim((string) $value));

        return in_array($stringValue, ['TAK', 'YES', 'TRUE', '1', 'Y', 'T']);
    }

    /**
     * Transform integer value from CSV
     *
     * @param mixed $value
     * @return int|null
     */
    protected function transformInteger($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Remove non-numeric characters except minus sign
        $cleaned = preg_replace('/[^0-9-]/', '', (string) $value);

        return $cleaned !== '' ? (int) $cleaned : null;
    }

    /**
     * Transform decimal value from CSV (handle Polish format: 123,45)
     *
     * @param mixed $value
     * @return float|null
     */
    protected function transformDecimal($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Convert Polish format to standard: 123,45 → 123.45
        $stringValue = (string) $value;
        $stringValue = str_replace(' ', '', $stringValue); // Remove spaces
        $stringValue = str_replace(',', '.', $stringValue); // Replace comma with dot

        // Remove non-numeric characters except dot and minus
        $cleaned = preg_replace('/[^0-9.-]/', '', $stringValue);

        return $cleaned !== '' ? (float) $cleaned : null;
    }

    /**
     * Get required columns for specific import type
     *
     * @param string $importType Type: variants, features, compatibility
     * @return array List of required field names
     */
    public function getRequiredColumns(string $importType): array
    {
        return match ($importType) {
            'variants' => ['sku', 'parent_sku', 'name'],
            'features' => ['sku'],
            'compatibility' => ['sku', 'vehicle_brand', 'vehicle_model'],
            default => ['sku'],
        };
    }

    /**
     * Validate that all required columns are present in mappings
     *
     * @param array $columnMappings Detected column mappings
     * @param string $importType Import type
     * @return array Missing required columns
     */
    public function getMissingRequiredColumns(array $columnMappings, string $importType): array
    {
        $requiredColumns = $this->getRequiredColumns($importType);
        $detectedFields = array_values($columnMappings);

        $missingColumns = [];
        foreach ($requiredColumns as $requiredField) {
            if (!in_array($requiredField, $detectedFields)) {
                $missingColumns[] = $requiredField;
            }
        }

        return $missingColumns;
    }
}
