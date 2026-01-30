<?php

declare(strict_types=1);

namespace App\Services\Import;

use Illuminate\Support\Facades\Log;

/**
 * ColumnMappingService - auto-mapowanie kolumn z confidence score
 *
 * ETAP_06 FAZA 4 - Column Mapping
 *
 * Features:
 * - 78 synonimow dla 24 PPM fields
 * - Confidence score (exact match = 1.0, Levenshtein + substring)
 * - Auto-map only if >=70% confidence
 * - Top 3 suggestions for user choice
 * - Normalizacja (lowercase, diacritics, multiple spaces)
 * - Validation (SKU required)
 *
 * @package App\Services\Import
 */
class ColumnMappingService
{
    /**
     * Minimum confidence for auto-mapping
     */
    public const MIN_AUTO_MAP_CONFIDENCE = 0.7;

    /**
     * Minimum confidence for suggestions
     */
    public const MIN_SUGGESTION_CONFIDENCE = 0.5;

    /**
     * Slownik mapowania: PPM field => array of synonyms
     *
     * Format: case-insensitive, normalized (trim, lowercase, no diacritics)
     * Total: 78 synonyms for 24 PPM fields
     */
    public const MAPPING_DICTIONARY = [
        'sku' => [
            // Primary
            'sku', 'kod', 'indeks', 'reference', 'ref',
            // International
            'product code', 'item code', 'article number', 'product number',
            // ERP systems
            'code article', 'artykul', 'symbol',
            // Variants
            'kod produktu', 'numer produktu', 'symbol produktu',
        ],

        'name' => [
            // Primary
            'nazwa', 'name', 'tytul', 'title', 'product', 'produkt',
            // Descriptive
            'nazwa produktu', 'product name', 'item name', 'description',
            // International
            'nom', 'bezeichnung', 'denominazione',
        ],

        'product_type' => [
            'typ', 'type', 'rodzaj', 'kategoria glowna', 'product type',
            'typ produktu', 'rodzaj produktu',
        ],

        'manufacturer' => [
            'producent', 'manufacturer', 'marka', 'brand', 'fabrikant',
            'wytworca', 'maker', 'nazwa producenta', 'manufacturer name',
        ],

        'supplier_id' => [
            'dostawca', 'supplier', 'dostawca nazwa', 'supplier name',
            'nazwa dostawcy', 'vendor',
        ],

        'importer_id' => [
            'importer', 'importer nazwa', 'importer name',
            'nazwa importera',
        ],

        'supplier_code' => [
            'kod dostawcy', 'supplier code', 'dostawca kod', 'external code',
            'kod zewnetrzny',
        ],

        'ean' => [
            'ean', 'ean13', 'barcode', 'kod kreskowy', 'gtin', 'upc',
        ],

        'weight' => [
            'waga', 'weight', 'masa', 'gewicht', 'poids',
            'waga produktu', 'product weight',
        ],

        'height' => [
            'wysokosc', 'height', 'h', 'hohe',
        ],

        'width' => [
            'szerokosc', 'width', 'w', 'breite', 'largeur',
        ],

        'length' => [
            'dlugosc', 'length', 'l', 'lange', 'longueur',
        ],

        'price' => [
            'cena', 'price', 'cena netto', 'net price', 'preis',
            'cena detaliczna', 'retail price',
        ],

        'purchase_price' => [
            'cena zakupu', 'purchase price', 'cost', 'koszt', 'cena kosztowa',
        ],

        'quantity' => [
            'ilosc', 'quantity', 'qty', 'stock', 'stan', 'dostepnosc',
            'stan magazynowy', 'available', 'quantite',
        ],

        'short_description' => [
            'krotki opis', 'short description', 'opis krotki', 'summary',
            'streszczenie', 'description courte',
        ],

        'long_description' => [
            'pelny opis', 'long description', 'opis pelny', 'opis',
            'description', 'detale', 'details',
        ],

        'category' => [
            'kategoria', 'category', 'kategorie', 'categories',
            'kategoria l3', 'main category',
        ],

        // Vehicle-specific fields
        'vin' => [
            'vin', 'numer vin', 'vehicle identification number',
        ],

        'engine_number' => [
            'numer silnika', 'engine number', 'engine no', 'silnik',
        ],

        'model' => [
            'model', 'model pojazdu', 'vehicle model', 'car model',
        ],

        'year' => [
            'rok', 'year', 'rocznik', 'production year', 'rok produkcji',
        ],

        // Compatibility fields
        'original_code' => [
            'oryginal', 'original', 'oe', 'oe number', 'numer oe',
            'original code', 'kod oryginalny',
        ],

        'replacement_code' => [
            'zamiennik', 'replacement', 'alternative', 'alternatywa',
            'kod zamiennika',
        ],
    ];

    /**
     * Auto-map Excel headers to PPM fields with confidence score
     *
     * @param array<string> $excelHeaders Headers from CSV/Excel file
     * @return array<string, array{ppm_field: string|null, confidence: float, suggestions: array}>
     */
    public function guessColumnMapping(array $excelHeaders): array
    {
        $mappings = [];

        Log::debug('ColumnMappingService: auto-mapping headers', [
            'headers_count' => count($excelHeaders),
        ]);

        foreach ($excelHeaders as $excelHeader) {
            $normalized = $this->normalizeColumnName($excelHeader);

            $bestMatch = $this->findBestMatch($normalized);

            $mappings[$excelHeader] = [
                'ppm_field' => $bestMatch['field'],
                'confidence' => $bestMatch['confidence'],
                'suggestions' => $bestMatch['suggestions'], // Alternative matches
            ];
        }

        return $mappings;
    }

    /**
     * Find best matching PPM field for normalized column name
     *
     * @param string $normalized Normalized column name
     * @return array{field: string|null, confidence: float, suggestions: array}
     */
    protected function findBestMatch(string $normalized): array
    {
        $scores = [];

        foreach (self::MAPPING_DICTIONARY as $ppmField => $synonyms) {
            foreach ($synonyms as $synonym) {
                $score = $this->calculateSimilarity($normalized, $synonym);

                if ($score > self::MIN_SUGGESTION_CONFIDENCE) {
                    if (!isset($scores[$ppmField]) || $score > $scores[$ppmField]) {
                        $scores[$ppmField] = $score;
                    }
                }
            }
        }

        arsort($scores);

        $bestField = key($scores);
        $bestScore = current($scores) ?: 0;

        // Get top 3 suggestions (excluding best match)
        $allSuggestions = array_keys($scores);
        $suggestions = array_slice(array_diff($allSuggestions, [$bestField]), 0, 3);

        return [
            'field' => $bestScore >= self::MIN_AUTO_MAP_CONFIDENCE ? $bestField : null,
            'confidence' => $bestScore,
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Calculate similarity between two strings
     *
     * Strategies:
     * - Exact match = 1.0
     * - Contains check (substring ratio)
     * - Levenshtein distance (normalized)
     *
     * @param string $str1
     * @param string $str2
     * @return float Similarity score 0-1
     */
    protected function calculateSimilarity(string $str1, string $str2): float
    {
        // Exact match
        if ($str1 === $str2) {
            return 1.0;
        }

        // Contains check (substring)
        if (str_contains($str1, $str2) || str_contains($str2, $str1)) {
            $shorter = strlen($str1) < strlen($str2) ? $str1 : $str2;
            $longer = strlen($str1) < strlen($str2) ? $str2 : $str1;

            // High score for substring matches
            return 0.8 + (strlen($shorter) / strlen($longer)) * 0.2;
        }

        // Levenshtein distance (edit distance)
        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen === 0) {
            return 1.0;
        }

        $levenshtein = levenshtein($str1, $str2);
        $levScore = 1 - ($levenshtein / $maxLen);

        return max(0, $levScore);
    }

    /**
     * Normalize column name for matching
     *
     * - Lowercase
     * - Trim whitespace
     * - Remove diacritics
     * - Replace multiple spaces with single space
     *
     * @param string $columnName
     * @return string
     */
    protected function normalizeColumnName(string $columnName): string
    {
        // Lowercase
        $normalized = mb_strtolower($columnName, 'UTF-8');

        // Trim
        $normalized = trim($normalized);

        // Remove diacritics (Polish characters)
        $normalized = $this->removeDiacritics($normalized);

        // Replace multiple spaces
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return $normalized ?? '';
    }

    /**
     * Remove diacritics from string
     *
     * @param string $str
     * @return string
     */
    protected function removeDiacritics(string $str): string
    {
        $diacritics = [
            // Polish
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l',
            'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
            // German
            'ä' => 'a', 'ö' => 'o', 'ü' => 'u', 'ß' => 'ss',
            // French
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'à' => 'a', 'â' => 'a',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'û' => 'u', 'ù' => 'u',
            'ç' => 'c',
        ];

        return strtr($str, $diacritics);
    }

    /**
     * Validate mapping (at least SKU must be mapped)
     *
     * @param array<string, string|null> $mapping Excel column => PPM field
     * @return array{valid: bool, errors: array}
     */
    public function validateMapping(array $mapping): array
    {
        $errors = [];

        // Check if SKU is mapped
        $hasSku = in_array('sku', array_values($mapping), true);

        if (!$hasSku) {
            $errors[] = 'Musisz zmapowac kolumne SKU (wymagane)';
        }

        // Check for duplicate mappings
        $mappedFields = array_filter(array_values($mapping));
        $duplicates = array_diff_assoc($mappedFields, array_unique($mappedFields));

        if (!empty($duplicates)) {
            $errors[] = 'Pole PPM nie moze byc zmapowane do wielu kolumn: ' . implode(', ', array_unique($duplicates));
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get available PPM fields for dropdown
     *
     * @return array<string, string> field_key => Human-readable label
     */
    public function getAvailablePPMFields(): array
    {
        return [
            '' => '-- Nie mapuj --',
            'sku' => 'SKU (wymagane)',
            'name' => 'Nazwa',
            'product_type' => 'Typ produktu',
            'manufacturer' => 'Producent',
            'supplier_id' => 'Dostawca',
            'importer_id' => 'Importer',
            'supplier_code' => 'Kod dostawcy',
            'ean' => 'EAN',
            'weight' => 'Waga (kg)',
            'height' => 'Wysokosc (cm)',
            'width' => 'Szerokosc (cm)',
            'length' => 'Dlugosc (cm)',
            'price' => 'Cena',
            'purchase_price' => 'Cena zakupu',
            'quantity' => 'Ilosc',
            'short_description' => 'Krotki opis',
            'long_description' => 'Pelny opis',
            'category' => 'Kategoria',
            'vin' => 'VIN',
            'engine_number' => 'Numer silnika',
            'model' => 'Model',
            'year' => 'Rok',
            'original_code' => 'Kod oryginalny',
            'replacement_code' => 'Kod zamiennika',
        ];
    }

    /**
     * Apply user mapping to parsed rows
     *
     * @param array<array<string, string>> $rows Original rows (excel column => value)
     * @param array<string, string|null> $mapping Excel column => PPM field
     * @return array<array<string, string>> Mapped rows (ppm field => value)
     */
    public function applyMapping(array $rows, array $mapping): array
    {
        $mappedRows = [];

        foreach ($rows as $row) {
            $mappedRow = [];

            foreach ($mapping as $excelColumn => $ppmField) {
                if (!empty($ppmField) && isset($row[$excelColumn])) {
                    $mappedRow[$ppmField] = $row[$excelColumn];
                }
            }

            // Only include rows with at least SKU
            if (!empty($mappedRow['sku'])) {
                $mappedRows[] = $mappedRow;
            }
        }

        return $mappedRows;
    }
}
