<?php

namespace App\Services\CSV;

use Illuminate\Support\Facades\Log;

/**
 * CSV Error Reporter Service
 *
 * Tracks and reports import errors with detailed row/column information.
 *
 * Features:
 * - Track errors per row and column
 * - Generate error report CSV
 * - Categorize error types
 * - Export error summary
 */
class ErrorReporter
{
    /**
     * Tracked errors
     * Format: [row_number => [column_name => [errors]]]
     */
    protected array $errors = [];

    /**
     * Error statistics
     */
    protected array $stats = [
        'total_rows' => 0,
        'error_rows' => 0,
        'valid_rows' => 0,
        'error_types' => [],
    ];

    /**
     * Track error for specific row and column
     *
     * @param int $rowNumber Row number (1-based, excluding header)
     * @param string|null $columnName Column name (null for row-level errors)
     * @param string $errorMessage Error message
     * @param string $errorType Error type (validation, existence, format, etc.)
     * @return void
     */
    public function trackError(
        int $rowNumber,
        ?string $columnName,
        string $errorMessage,
        string $errorType = 'validation'
    ): void {
        if (!isset($this->errors[$rowNumber])) {
            $this->errors[$rowNumber] = [];
        }

        $columnKey = $columnName ?? '__row_error__';

        if (!isset($this->errors[$rowNumber][$columnKey])) {
            $this->errors[$rowNumber][$columnKey] = [];
        }

        $this->errors[$rowNumber][$columnKey][] = [
            'message' => $errorMessage,
            'type' => $errorType,
        ];

        // Update stats
        if (!isset($this->stats['error_types'][$errorType])) {
            $this->stats['error_types'][$errorType] = 0;
        }
        $this->stats['error_types'][$errorType]++;

        Log::warning('ErrorReporter: Error tracked', [
            'row_number' => $rowNumber,
            'column_name' => $columnName,
            'error_type' => $errorType,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Track multiple errors for a row
     *
     * @param int $rowNumber Row number
     * @param array $errors Array of error messages
     * @param string|null $columnName Column name (null for row-level)
     * @return void
     */
    public function trackRowErrors(int $rowNumber, array $errors, ?string $columnName = null): void
    {
        foreach ($errors as $error) {
            $this->trackError($rowNumber, $columnName, $error);
        }
    }

    /**
     * Check if row has errors
     *
     * @param int $rowNumber Row number
     * @return bool
     */
    public function hasErrors(int $rowNumber): bool
    {
        return isset($this->errors[$rowNumber]);
    }

    /**
     * Get errors for specific row
     *
     * @param int $rowNumber Row number
     * @return array
     */
    public function getRowErrors(int $rowNumber): array
    {
        return $this->errors[$rowNumber] ?? [];
    }

    /**
     * Get all errors
     *
     * @return array
     */
    public function getAllErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get error count
     *
     * @return int Total number of error rows
     */
    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    /**
     * Get error summary statistics
     *
     * @return array
     */
    public function getStats(): array
    {
        $this->stats['error_rows'] = count($this->errors);
        $this->stats['valid_rows'] = $this->stats['total_rows'] - $this->stats['error_rows'];

        return $this->stats;
    }

    /**
     * Set total row count
     *
     * @param int $count
     * @return void
     */
    public function setTotalRows(int $count): void
    {
        $this->stats['total_rows'] = $count;
    }

    /**
     * Generate error report as CSV
     *
     * @param string $filename Output filename
     * @return string Path to generated error report
     */
    public function generateErrorReport(string $filename = 'import_errors'): string
    {
        Log::info('ErrorReporter: Generating error report', [
            'filename' => $filename,
            'error_count' => count($this->errors),
        ]);

        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $filePath = $tempDir . '/' . $filename . '.csv';

        $file = fopen($filePath, 'w');

        // Write UTF-8 BOM
        fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Write header
        fputcsv($file, [
            'Numer wiersza',
            'Kolumna',
            'Typ bledu',
            'Opis bledu',
        ], ';');

        // Write error rows
        foreach ($this->errors as $rowNumber => $columns) {
            foreach ($columns as $columnName => $errorList) {
                foreach ($errorList as $error) {
                    $displayColumnName = $columnName === '__row_error__' ? '(caly wiersz)' : $columnName;

                    fputcsv($file, [
                        $rowNumber,
                        $displayColumnName,
                        $error['type'],
                        $error['message'],
                    ], ';');
                }
            }
        }

        fclose($file);

        Log::info('ErrorReporter: Error report generated', [
            'file_path' => $filePath,
            'file_size' => filesize($filePath),
        ]);

        return $filePath;
    }

    /**
     * Export errors as structured array (for JSON response)
     *
     * @return array
     */
    public function exportErrors(): array
    {
        $exported = [];

        foreach ($this->errors as $rowNumber => $columns) {
            $rowErrors = [];

            foreach ($columns as $columnName => $errorList) {
                $displayColumnName = $columnName === '__row_error__' ? null : $columnName;

                foreach ($errorList as $error) {
                    $rowErrors[] = [
                        'column' => $displayColumnName,
                        'type' => $error['type'],
                        'message' => $error['message'],
                    ];
                }
            }

            $exported[] = [
                'row' => $rowNumber,
                'errors' => $rowErrors,
            ];
        }

        return $exported;
    }

    /**
     * Generate error summary text
     *
     * @return string
     */
    public function getSummaryText(): string
    {
        $stats = $this->getStats();

        $summary = "Podsumowanie importu:\n";
        $summary .= "- Wszystkich wierszy: {$stats['total_rows']}\n";
        $summary .= "- Poprawnych wierszy: {$stats['valid_rows']}\n";
        $summary .= "- Wierszy z bledami: {$stats['error_rows']}\n\n";

        if (!empty($stats['error_types'])) {
            $summary .= "Typy bledow:\n";
            foreach ($stats['error_types'] as $type => $count) {
                $summary .= "- {$type}: {$count}\n";
            }
        }

        return $summary;
    }

    /**
     * Clear all tracked errors
     *
     * @return void
     */
    public function clearErrors(): void
    {
        $this->errors = [];
        $this->stats = [
            'total_rows' => 0,
            'error_rows' => 0,
            'valid_rows' => 0,
            'error_types' => [],
        ];

        Log::info('ErrorReporter: Errors cleared');
    }

    /**
     * Check if any errors were tracked
     *
     * @return bool
     */
    public function hasAnyErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get error types with counts
     *
     * @return array [error_type => count]
     */
    public function getErrorTypes(): array
    {
        return $this->stats['error_types'];
    }

    /**
     * Get rows with specific error type
     *
     * @param string $errorType Error type to filter
     * @return array Row numbers with this error type
     */
    public function getRowsWithErrorType(string $errorType): array
    {
        $rows = [];

        foreach ($this->errors as $rowNumber => $columns) {
            foreach ($columns as $columnName => $errorList) {
                foreach ($errorList as $error) {
                    if ($error['type'] === $errorType) {
                        $rows[] = $rowNumber;
                        break 2; // Move to next row
                    }
                }
            }
        }

        return array_unique($rows);
    }

    /**
     * Format error for display
     *
     * @param int $rowNumber Row number
     * @return string Formatted error message
     */
    public function formatRowError(int $rowNumber): string
    {
        if (!$this->hasErrors($rowNumber)) {
            return '';
        }

        $errors = $this->getRowErrors($rowNumber);
        $formatted = "Wiersz {$rowNumber}:\n";

        foreach ($errors as $columnName => $errorList) {
            $displayColumnName = $columnName === '__row_error__' ? 'Ogolny' : $columnName;

            foreach ($errorList as $error) {
                $formatted .= "  - [{$displayColumnName}] {$error['message']}\n";
            }
        }

        return $formatted;
    }
}
