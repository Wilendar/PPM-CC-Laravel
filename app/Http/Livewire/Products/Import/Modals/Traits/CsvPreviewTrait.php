<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\Import\Modals\Traits;

/**
 * CsvPreviewTrait - preview rendering for CSVImportModal
 *
 * ETAP_06 FAZA 4 - Import CSV/Excel
 *
 * Features:
 * - First 5 rows preview
 * - Mapped vs original view
 * - Row count for UI badge
 *
 * @package App\Http\Livewire\Products\Import\Modals\Traits
 */
trait CsvPreviewTrait
{
    /**
     * Default preview row limit
     */
    protected int $previewLimit = 5;

    /**
     * Update preview rows (called after parse or mapping change)
     */
    public function updatePreview(): void
    {
        if (empty($this->parsedData['rows'])) {
            $this->previewRows = [];
            return;
        }

        $this->previewRows = array_slice($this->parsedData['rows'], 0, $this->previewLimit);
    }

    /**
     * Get preview row count for display
     *
     * @return int
     */
    public function getPreviewRowCount(): int
    {
        return count($this->previewRows);
    }

    /**
     * Get total row count
     *
     * @return int
     */
    public function getTotalRowCount(): int
    {
        return $this->parsedData['total_rows'] ?? 0;
    }

    /**
     * Get single preview row by index
     *
     * @param int $index
     * @return array
     */
    public function getPreviewRow(int $index): array
    {
        return $this->previewRows[$index] ?? [];
    }

    /**
     * Check if preview is available
     *
     * @return bool
     */
    public function hasPreview(): bool
    {
        return !empty($this->previewRows);
    }

    /**
     * Get preview row as mapped values (PPM fields)
     *
     * @param int $index
     * @return array<string, string> PPM field => value
     */
    public function getMappedPreviewRow(int $index): array
    {
        $row = $this->previewRows[$index] ?? [];
        $mappedRow = [];

        foreach ($this->columnMapping as $excelColumn => $ppmField) {
            if (!empty($ppmField) && isset($row[$excelColumn])) {
                $mappedRow[$ppmField] = $row[$excelColumn];
            }
        }

        return $mappedRow;
    }

    /**
     * Get all preview rows as mapped values
     *
     * @return array<array<string, string>>
     */
    public function getMappedPreviewRows(): array
    {
        $mapped = [];

        foreach ($this->previewRows as $index => $row) {
            $mapped[] = $this->getMappedPreviewRow($index);
        }

        return $mapped;
    }

    /**
     * Get sample value for column from first row
     *
     * @param string $excelColumn
     * @return string
     */
    public function getSampleValue(string $excelColumn): string
    {
        if (empty($this->previewRows)) {
            return '';
        }

        $value = $this->previewRows[0][$excelColumn] ?? '';

        // Truncate long values for display
        if (strlen($value) > 50) {
            return substr($value, 0, 47) . '...';
        }

        return $value;
    }

    /**
     * Get column header for display
     *
     * @param string $excelColumn Original column name
     * @return string Display name (mapped field or original)
     */
    public function getDisplayColumnHeader(string $excelColumn): string
    {
        $ppmField = $this->columnMapping[$excelColumn] ?? null;

        if ($ppmField) {
            $ppmFields = $this->mappingService->getAvailablePPMFields();
            return $ppmFields[$ppmField] ?? $ppmField;
        }

        return $excelColumn;
    }

    /**
     * Get cells to display in preview (only mapped columns)
     *
     * @return array<string> List of Excel column names that are mapped
     */
    public function getMappedColumnsForPreview(): array
    {
        return array_keys(array_filter($this->columnMapping));
    }

    /**
     * Get preview summary text
     *
     * @return string
     */
    public function getPreviewSummary(): string
    {
        $total = $this->getTotalRowCount();
        $mapped = $this->getMappedColumnCount();
        $columns = count($this->parsedData['headers'] ?? []);

        return sprintf(
            '%d wierszy, %d / %d kolumn zmapowanych',
            $total,
            $mapped,
            $columns
        );
    }

    /**
     * Check if there are too many rows (show warning)
     *
     * @param int $threshold
     * @return bool
     */
    public function hasManyRows(int $threshold = 1000): bool
    {
        return $this->getTotalRowCount() > $threshold;
    }

    /**
     * Get warning message for large files
     *
     * @return string|null
     */
    public function getLargeFileWarning(): ?string
    {
        $total = $this->getTotalRowCount();

        if ($total > 5000) {
            return sprintf(
                'Plik zawiera %d wierszy. Import moze zajac kilka minut.',
                $total
            );
        }

        if ($total > 1000) {
            return sprintf(
                'Plik zawiera %d wierszy. Prosze czekac na zakonczenie importu.',
                $total
            );
        }

        return null;
    }
}
