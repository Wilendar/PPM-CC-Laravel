<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\Import\Modals\Traits;

use Illuminate\Support\Facades\Log;

/**
 * CsvColumnMappingTrait - column mapping logic for CSVImportModal
 *
 * ETAP_06 FAZA 4 - Import CSV/Excel
 *
 * Features:
 * - Auto-mapping with confidence scores
 * - Manual mapping override
 * - SKU required validation
 * - Row transformation
 *
 * @package App\Http\Livewire\Products\Import\Modals\Traits
 */
trait CsvColumnMappingTrait
{
    /**
     * Apply auto-mapping using ColumnMappingService
     * Called after successful file parse
     */
    public function applyAutoMapping(): void
    {
        if (empty($this->parsedData['headers'])) {
            return;
        }

        try {
            // Get auto-mapping suggestions
            $this->autoMappingSuggestions = $this->mappingService->guessColumnMapping(
                $this->parsedData['headers']
            );

            // Apply auto-mapped fields (only if confidence >= 70%)
            $this->columnMapping = [];

            foreach ($this->autoMappingSuggestions as $excelColumn => $suggestion) {
                if (!empty($suggestion['ppm_field'])) {
                    $this->columnMapping[$excelColumn] = $suggestion['ppm_field'];
                } else {
                    $this->columnMapping[$excelColumn] = null;
                }
            }

            $autoMappedCount = count(array_filter($this->columnMapping));

            Log::debug('CsvColumnMappingTrait: auto-mapping applied', [
                'total_columns' => count($this->parsedData['headers']),
                'auto_mapped' => $autoMappedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('CsvColumnMappingTrait: auto-mapping failed', [
                'error' => $e->getMessage(),
            ]);

            // Initialize empty mapping on failure
            $this->columnMapping = array_fill_keys($this->parsedData['headers'], null);
            $this->autoMappingSuggestions = [];
        }
    }

    /**
     * Update column mapping for specific column (manual override)
     *
     * @param string $excelColumn Column name from Excel/CSV file
     * @param string|null $ppmField PPM field name or null to clear
     */
    public function updateColumnMapping(string $excelColumn, ?string $ppmField): void
    {
        // Clear mapping if empty string
        if ($ppmField === '') {
            $ppmField = null;
        }

        // If mapping to a field that's already used, clear the previous mapping
        if ($ppmField !== null) {
            foreach ($this->columnMapping as $column => $field) {
                if ($field === $ppmField && $column !== $excelColumn) {
                    $this->columnMapping[$column] = null;
                }
            }
        }

        $this->columnMapping[$excelColumn] = $ppmField;

        Log::debug('CsvColumnMappingTrait: mapping updated', [
            'excel_column' => $excelColumn,
            'ppm_field' => $ppmField,
        ]);

        // Update preview after mapping change
        $this->updatePreview();
    }

    /**
     * Validate mapping - SKU is required
     *
     * @return array{valid: bool, errors: array}
     */
    public function validateMapping(): array
    {
        $validation = $this->mappingService->validateMapping($this->columnMapping);

        if (!$validation['valid']) {
            $this->errors = $validation['errors'];
        }

        return $validation;
    }

    /**
     * Get mapped rows (Excel columns transformed to PPM fields)
     *
     * @return array<array<string, string>>
     */
    public function getMappedRows(): array
    {
        return $this->mappingService->applyMapping(
            $this->parsedData['rows'],
            $this->columnMapping
        );
    }

    /**
     * Reset all mappings
     */
    public function resetMapping(): void
    {
        $this->columnMapping = array_fill_keys($this->parsedData['headers'] ?? [], null);

        Log::debug('CsvColumnMappingTrait: mapping reset');

        $this->updatePreview();
    }

    /**
     * Get confidence badge class for display
     *
     * @param string $excelColumn
     * @return string CSS class
     */
    public function getConfidenceBadgeClass(string $excelColumn): string
    {
        $confidence = $this->autoMappingSuggestions[$excelColumn]['confidence'] ?? 0;

        if ($confidence >= 0.9) {
            return 'badge-success'; // Green - very high confidence
        }

        if ($confidence >= 0.7) {
            return 'badge-primary'; // Blue - auto-mapped
        }

        if ($confidence >= 0.5) {
            return 'badge-warning'; // Yellow - suggestion available
        }

        return 'badge-secondary'; // Gray - no match
    }

    /**
     * Get confidence percentage for display
     *
     * @param string $excelColumn
     * @return int Percentage 0-100
     */
    public function getConfidencePercent(string $excelColumn): int
    {
        $confidence = $this->autoMappingSuggestions[$excelColumn]['confidence'] ?? 0;

        return (int) round($confidence * 100);
    }

    /**
     * Get alternative suggestions for column
     *
     * @param string $excelColumn
     * @return array<string>
     */
    public function getAlternativeSuggestions(string $excelColumn): array
    {
        return $this->autoMappingSuggestions[$excelColumn]['suggestions'] ?? [];
    }

    /**
     * Check if column is mapped
     *
     * @param string $excelColumn
     * @return bool
     */
    public function isColumnMapped(string $excelColumn): bool
    {
        return !empty($this->columnMapping[$excelColumn]);
    }

    /**
     * Get count of mapped columns
     *
     * @return int
     */
    public function getMappedColumnCount(): int
    {
        return count(array_filter($this->columnMapping));
    }

    /**
     * Get count of unmapped columns
     *
     * @return int
     */
    public function getUnmappedColumnCount(): int
    {
        return count($this->columnMapping) - $this->getMappedColumnCount();
    }

    /**
     * Check if SKU is mapped
     *
     * @return bool
     */
    public function isSkuMapped(): bool
    {
        return in_array('sku', array_values($this->columnMapping), true);
    }
}
