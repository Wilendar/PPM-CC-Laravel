<?php

declare(strict_types=1);

namespace App\Services\Import;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * CsvParserService - parsowanie CSV z automatycznym wykrywaniem encoding i delimiter
 *
 * ETAP_06 FAZA 4 - Import CSV
 *
 * Features:
 * - Auto-detection encoding (UTF-8, Windows-1250, ISO-8859-2, etc.)
 * - Auto-detection delimiter (comma, semicolon, tab, pipe)
 * - Normalization to UTF-8
 * - Structure validation (max 10k rows)
 *
 * Reference: Laravel 12.x UploadedFile documentation
 * Dependencies: None (native PHP)
 *
 * @package App\Services\Import
 */
class CsvParserService
{
    /**
     * Supported delimiters (priority order)
     */
    public const DELIMITERS = [
        ',' => 'Przecinek (,)',
        ';' => 'Średnik (;)',
        "\t" => 'Tabulator (TAB)',
        '|' => 'Pionowa kreska (|)',
    ];

    /**
     * Supported encodings (check order)
     */
    public const ENCODINGS = [
        'UTF-8',
        'Windows-1250',
        'ISO-8859-2',
        'Windows-1252',
        'ISO-8859-1',
    ];

    /**
     * Max rows limit
     */
    public const MAX_ROWS = 10000;

    /**
     * Parse CSV file
     *
     * @param UploadedFile $file Uploaded CSV file
     * @param string|null $delimiter Force delimiter (null = auto-detect)
     * @param string|null $encoding Force encoding (null = auto-detect)
     * @return array{headers: array, rows: array, detected_delimiter: string, detected_encoding: string, total_rows: int}
     * @throws \InvalidArgumentException If file is not readable
     */
    public function parseCSV(
        UploadedFile $file,
        ?string $delimiter = null,
        ?string $encoding = null
    ): array {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Nieprawidłowy plik upload');
        }

        $path = $file->getRealPath();

        // Auto-detect encoding if not provided
        $detectedEncoding = $encoding ?? $this->detectEncoding($path);

        // Auto-detect delimiter if not provided
        $detectedDelimiter = $delimiter ?? $this->detectDelimiter($path, $detectedEncoding);

        Log::debug('CSV Parser: detected settings', [
            'encoding' => $detectedEncoding,
            'delimiter' => $detectedDelimiter,
            'file' => $file->getClientOriginalName(),
        ]);

        // Read file with detected encoding
        $content = file_get_contents($path);

        // Convert to UTF-8 if needed
        if ($detectedEncoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $detectedEncoding);
        }

        // Remove BOM if present
        $content = $this->removeBOM($content);

        // Parse CSV
        $lines = $this->splitLines($content);
        $headers = $this->parseCSVLine(array_shift($lines) ?? '', $detectedDelimiter);

        // Clean empty headers
        $headers = array_map('trim', $headers);
        $headers = array_filter($headers, fn($h) => $h !== '');

        if (empty($headers)) {
            throw new \InvalidArgumentException('Brak nagłówków kolumn w pliku CSV');
        }

        $rows = [];
        $headerCount = count($headers);

        foreach ($lines as $lineIndex => $line) {
            if (trim($line) === '') {
                continue;
            }

            $row = $this->parseCSVLine($line, $detectedDelimiter);

            // Pad row if shorter than headers
            if (count($row) < $headerCount) {
                $row = array_pad($row, $headerCount, '');
            }

            // Trim row if longer than headers
            if (count($row) > $headerCount) {
                $row = array_slice($row, 0, $headerCount);
            }

            // Combine with headers
            $rowAssoc = [];
            foreach ($headers as $index => $header) {
                $rowAssoc[$header] = trim($row[$index] ?? '');
            }

            $rows[] = $rowAssoc;

            // Safety limit
            if (count($rows) >= self::MAX_ROWS) {
                Log::warning('CSV Parser: row limit reached', ['limit' => self::MAX_ROWS]);
                break;
            }
        }

        return [
            'headers' => array_values($headers),
            'rows' => $rows,
            'detected_delimiter' => $detectedDelimiter,
            'detected_encoding' => $detectedEncoding,
            'total_rows' => count($rows),
        ];
    }

    /**
     * Parse single CSV line handling quoted values
     *
     * @param string $line
     * @param string $delimiter
     * @return array
     */
    protected function parseCSVLine(string $line, string $delimiter): array
    {
        return str_getcsv($line, $delimiter, '"', '\\');
    }

    /**
     * Split content into lines (handle different line endings)
     *
     * @param string $content
     * @return array
     */
    protected function splitLines(string $content): array
    {
        // Normalize line endings to \n
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        return explode("\n", $content);
    }

    /**
     * Remove BOM (Byte Order Mark) from UTF-8 content
     *
     * @param string $content
     * @return string
     */
    protected function removeBOM(string $content): string
    {
        $bom = "\xEF\xBB\xBF";
        if (str_starts_with($content, $bom)) {
            return substr($content, 3);
        }

        return $content;
    }

    /**
     * Detect file encoding using mb_detect_encoding and BOM
     *
     * @param string $filePath Path to file
     * @return string Detected encoding
     */
    public function detectEncoding(string $filePath): string
    {
        // Check BOM first (most reliable for UTF-8)
        $bom = file_get_contents($filePath, false, null, 0, 4);

        // UTF-8 BOM
        if (substr($bom, 0, 3) === "\xEF\xBB\xBF") {
            return 'UTF-8';
        }

        // UTF-16 LE BOM
        if (substr($bom, 0, 2) === "\xFF\xFE") {
            return 'UTF-16LE';
        }

        // UTF-16 BE BOM
        if (substr($bom, 0, 2) === "\xFE\xFF") {
            return 'UTF-16BE';
        }

        // Use mb_detect_encoding on sample
        $sample = file_get_contents($filePath, false, null, 0, 50000); // First 50KB

        $detected = mb_detect_encoding($sample, self::ENCODINGS, true);

        return $detected ?: 'UTF-8';
    }

    /**
     * Detect CSV delimiter by analyzing first 10 lines
     *
     * Strategy: Count occurrences of each delimiter, choose most consistent
     *
     * @param string $filePath Path to file
     * @param string $encoding File encoding
     * @return string Detected delimiter
     */
    public function detectDelimiter(string $filePath, string $encoding): string
    {
        $sample = file_get_contents($filePath, false, null, 0, 10000); // First 10KB

        // Convert to UTF-8 for analysis
        if ($encoding !== 'UTF-8') {
            $sample = mb_convert_encoding($sample, 'UTF-8', $encoding);
        }

        $sample = $this->removeBOM($sample);
        $lines = array_slice($this->splitLines($sample), 0, 10);

        $delimiterScores = [];

        foreach (array_keys(self::DELIMITERS) as $delimiter) {
            $counts = [];

            foreach ($lines as $line) {
                if (trim($line) === '') {
                    continue;
                }

                // Count delimiter occurrences outside quotes
                $count = $this->countDelimiterOccurrences($line, $delimiter);
                $counts[] = $count;
            }

            if (empty($counts)) {
                continue;
            }

            // Calculate consistency: high count + low variance = good delimiter
            $avgCount = array_sum($counts) / count($counts);
            $variance = $this->calculateVariance($counts);

            // Score: favor high count and low variance
            $delimiterScores[$delimiter] = $avgCount > 0 ? ($avgCount / (1 + $variance)) : 0;
        }

        arsort($delimiterScores);

        $bestDelimiter = key($delimiterScores);

        return $bestDelimiter ?: ',';
    }

    /**
     * Count delimiter occurrences outside quoted strings
     *
     * @param string $line
     * @param string $delimiter
     * @return int
     */
    protected function countDelimiterOccurrences(string $line, string $delimiter): int
    {
        $count = 0;
        $inQuotes = false;

        for ($i = 0; $i < strlen($line); $i++) {
            $char = $line[$i];

            if ($char === '"') {
                $inQuotes = !$inQuotes;
            } elseif (!$inQuotes && $char === $delimiter) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Calculate variance for delimiter detection
     *
     * @param array<int> $values
     * @return float
     */
    protected function calculateVariance(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $values)) / count($values);

        return $variance;
    }

    /**
     * Validate CSV structure
     *
     * @param array $parsedData Output from parseCSV()
     * @return array{valid: bool, errors: array}
     */
    public function validateStructure(array $parsedData): array
    {
        $errors = [];

        if (empty($parsedData['headers'])) {
            $errors[] = 'Brak nagłówków kolumn w pliku CSV';
        }

        if (empty($parsedData['rows'])) {
            $errors[] = 'Brak danych w pliku CSV';
        }

        if (isset($parsedData['total_rows']) && $parsedData['total_rows'] >= self::MAX_ROWS) {
            $errors[] = sprintf('Plik zawiera więcej niż %d wierszy (limit systemu)', self::MAX_ROWS);
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
