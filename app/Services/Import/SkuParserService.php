<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\PendingProduct;
use App\Models\Product;
use Illuminate\Support\Str;

/**
 * SKUParserService - parsowanie wklejonej listy SKU
 *
 * ETAP_06 FAZA 3 - Import SKU (wklejanie listy)
 *
 * Obsluguje:
 * - Parsowanie SKU (tylko SKU lub SKU + Nazwa)
 * - Auto-detekcja separatora
 * - Walidacja formatu SKU
 * - Wykrywanie duplikatow (w batch, PPM, pending)
 *
 * @package App\Services\Import
 * @version 2.0
 * @since ETAP_06 - System Importu Produktow
 */
class SkuParserService
{
    /**
     * Supported separators (auto-detection priority order)
     */
    public const SEPARATORS = [
        'auto' => 'Automatyczny',
        'tab' => 'Tabulator',
        'semicolon' => 'Srednik (;)',
        'comma' => 'Przecinek (,)',
        'pipe' => 'Pionowa kreska (|)',
    ];

    /**
     * Separator character map
     */
    private const SEPARATOR_CHARS = [
        'tab' => "\t",
        'semicolon' => ';',
        'comma' => ',',
        'pipe' => '|',
    ];

    /**
     * Import modes
     */
    public const IMPORT_MODES = [
        'sku_only' => 'Tylko SKU',
        'sku_name' => 'SKU + Nazwa',
    ];

    /** @var string Regex dla prawidlowego SKU */
    private const SKU_PATTERN = '/^[A-Za-z0-9\-_.\/]+$/';

    /** @var int Minimalna dlugosc SKU */
    private const MIN_SKU_LENGTH = 2;

    /** @var int Maksymalna dlugosc SKU */
    private const MAX_SKU_LENGTH = 64;

    /**
     * Glowna metoda parsowania (FAZA 3 spec)
     *
     * @param string $input Surowe dane wejsciowe
     * @param string $mode Tryb: 'sku_only' lub 'sku_name'
     * @param string $separator Separator: 'auto', 'newline', 'multi' lub konkretny
     * @return array{items: array, errors: array, warnings: array, stats: array}
     */
    public function parse(string $input, string $mode = 'sku_only', string $separator = 'auto'): array
    {
        $result = [
            'items' => [],
            'errors' => [],
            'warnings' => [],
            'stats' => [
                'total_lines' => 0,
                'valid_items' => 0,
                'skipped_empty' => 0,
                'duplicates_in_batch' => 0,
            ],
        ];

        // Podzial na linie
        $lines = $this->splitLines($input);
        $result['stats']['total_lines'] = count($lines);

        if (empty($lines)) {
            $result['errors'][] = [
                'line' => 0,
                'message' => 'Brak danych do zaimportowania',
            ];
            return $result;
        }

        // Auto-detekcja separatora jesli potrzeba
        $detectedSeparator = ($separator === 'auto')
            ? $this->detectSeparator($input)
            : $this->getSeparatorCharacter($separator);

        // Parsowanie w zaleznosci od trybu
        if ($mode === 'sku_only') {
            // Inteligentne parsowanie SKU (wykrywa multi-separator lub newline)
            $parsedItems = $this->parseSkuOnlyIntelligent($lines, $separator);
        } else {
            $parsedItems = $this->parseSkuName($lines, $detectedSeparator);
        }

        // Walidacja SKU
        foreach ($parsedItems as $item) {
            $validation = $this->validateSKUFormat($item['sku']);

            if (!$validation['valid']) {
                $result['errors'][] = [
                    'line' => $item['line'],
                    'sku' => $item['sku'],
                    'message' => $validation['message'],
                ];
                continue;
            }

            $result['items'][] = $item;
        }

        // Wykrycie duplikatow w batch
        $duplicatesInBatch = $this->checkDuplicatesInBatch(
            array_column($result['items'], 'sku')
        );

        foreach ($duplicatesInBatch as $sku => $lines) {
            $result['warnings'][] = [
                'type' => 'duplicate_in_batch',
                'sku' => $sku,
                'lines' => $lines,
                'message' => "SKU '{$sku}' pojawia sie wielokrotnie (linie: " . implode(', ', $lines) . ")",
            ];
            $result['stats']['duplicates_in_batch']++;
        }

        // Usuniecie duplikatow z items (zachowaj pierwsze wystapienie)
        $uniqueItems = [];
        $seenSkus = [];
        foreach ($result['items'] as $item) {
            $skuNormalized = strtoupper(trim($item['sku']));
            if (!isset($seenSkus[$skuNormalized])) {
                $seenSkus[$skuNormalized] = true;
                $uniqueItems[] = $item;
            }
        }
        $result['items'] = $uniqueItems;

        $result['stats']['valid_items'] = count($result['items']);
        $result['stats']['skipped_empty'] = $result['stats']['total_lines'] - count($parsedItems);

        return $result;
    }

    /**
     * Legacy method for backwards compatibility
     */
    public function parseText(string $text): array
    {
        return $this->parse($text, 'sku_only', 'auto');
    }

    /**
     * Podzial inputu na linie
     *
     * @return array<string>
     */
    public function splitLines(string $input): array
    {
        // Normalizacja line endings
        $normalized = str_replace(["\r\n", "\r"], "\n", $input);

        // Podzial na linie i filtrowanie pustych
        $lines = explode("\n", $normalized);

        return array_values(array_filter(
            array_map('trim', $lines),
            fn($line) => $line !== ''
        ));
    }

    /**
     * Automatyczne wykrywanie separatora (z obsługa inline separators)
     *
     * @param string $input Surowy input
     * @return string Wykryty separator character
     */
    public function detectSeparator(string $input): string
    {
        $separatorCounts = [
            "\t" => 0,
            ';' => 0,
            ',' => 0,
            '|' => 0,
        ];

        $lines = $this->splitLines($input);
        $sampleLines = array_slice($lines, 0, min(10, count($lines)));

        foreach ($sampleLines as $line) {
            foreach (array_keys($separatorCounts) as $sep) {
                $separatorCounts[$sep] += substr_count($line, $sep);
            }
        }

        // Znajdz separator z najwieksza iloscia wystapien
        arsort($separatorCounts);
        $bestSeparator = key($separatorCounts);
        $bestCount = current($separatorCounts);

        // Jesli najlepszy separator ma mniej niz 1 wystapienie na linie, uzyj tab
        if ($bestCount < count($sampleLines)) {
            return "\t";
        }

        return $bestSeparator;
    }

    /**
     * Wykrywa czy input zawiera inline separatory (przecinki, sredniki, spacje)
     *
     * @param string $input Surowy input
     * @return bool True jesli zawiera inline separatory miedzy slowami
     */
    public function hasInlineSeparators(string $input): bool
    {
        $lines = $this->splitLines($input);

        // Sprawdz pierwsze 10 linii
        $sampleLines = array_slice($lines, 0, min(10, count($lines)));

        foreach ($sampleLines as $line) {
            // Sprawdz czy linia zawiera separatory miedzy slowami
            if (preg_match('/\S+[\s,;]+\S+/', $line)) {
                // Sprawdz czy to nie jest format SKU+Nazwa (tab/pipe separator)
                if (!str_contains($line, "\t") && !str_contains($line, '|')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Parsowanie trybu SKU only (legacy - pojedynczy SKU na linie)
     *
     * @param array<string> $lines
     * @return array<array{sku: string, name: string|null, line: int}>
     */
    public function parseSkuOnly(array $lines): array
    {
        $items = [];

        foreach ($lines as $index => $line) {
            $sku = trim($line);

            if ($sku === '') {
                continue;
            }

            $items[] = [
                'sku' => $sku,
                'name' => null,
                'line' => $index + 1,
            ];
        }

        return $items;
    }

    /**
     * Parsuje SKU z wielu separatorow w jednej linii
     *
     * Obsluguje formaty:
     * - SKU001, SKU002, SKU003 (przecinki)
     * - SKU001;SKU002;SKU003 (sredniki)
     * - SKU001 SKU002 SKU003 (spacje/tabulatory)
     * - Mieszane formaty w roznych liniach
     *
     * @param array<string> $lines Tablica linii
     * @return array<array{sku: string, name: string|null, line: int}> Rozparsowane SKU z line mapping
     */
    public function parseSkuOnlyMultiSeparator(array $lines): array
    {
        $items = [];

        foreach ($lines as $index => $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            // Split przez wiele separatorow: przecinek, srednik, spacja/tab
            // Regex: split przez ,;spacja/tab z opcjonalnymi spacjami wokol
            $skus = preg_split('/[\s,;]+/', $line, -1, PREG_SPLIT_NO_EMPTY);

            foreach ($skus as $sku) {
                $sku = trim($sku);

                if ($sku === '') {
                    continue;
                }

                $items[] = [
                    'sku' => $sku,
                    'name' => null,
                    'line' => $index + 1, // Original line number
                ];
            }
        }

        return $items;
    }

    /**
     * Router do odpowiedniej metody parsowania SKU only
     *
     * Automatycznie wykrywa czy input zawiera:
     * - Format "jeden SKU na linie" (newline)
     * - Format "wiele SKU w linii z separatorami" (multi)
     * - Format mieszany (multi)
     *
     * @param array<string> $lines Tablica linii
     * @param string $separator 'auto', 'newline', lub 'multi'
     * @return array<array{sku: string, name: string|null, line: int}>
     */
    public function parseSkuOnlyIntelligent(array $lines, string $separator = 'auto'): array
    {
        // Jesli uzytkownik wymusil konkretny tryb
        if ($separator === 'newline') {
            return $this->parseSkuOnly($lines);
        }

        if ($separator === 'multi') {
            return $this->parseSkuOnlyMultiSeparator($lines);
        }

        // Auto-detekcja: sprawdz czy linie zawieraja inline separatory
        $hasInline = false;
        $sampleLines = array_slice($lines, 0, min(10, count($lines)));

        foreach ($sampleLines as $line) {
            // Sprawdz czy linia zawiera wiele "slow" oddzielonych separatorami
            if (preg_match('/\S+[\s,;]+\S+/', $line)) {
                $hasInline = true;
                break;
            }
        }

        // Wybierz odpowiednia metode
        return $hasInline
            ? $this->parseSkuOnlyMultiSeparator($lines)
            : $this->parseSkuOnly($lines);
    }

    /**
     * Parsowanie trybu SKU + Nazwa
     *
     * @param array<string> $lines
     * @param string $separator
     * @return array<array{sku: string, name: string|null, line: int}>
     */
    public function parseSkuName(array $lines, string $separator): array
    {
        $items = [];

        foreach ($lines as $index => $line) {
            $parts = explode($separator, $line, 2);

            $sku = trim($parts[0] ?? '');
            $name = isset($parts[1]) ? trim($parts[1]) : null;

            if ($sku === '') {
                continue;
            }

            $items[] = [
                'sku' => $sku,
                'name' => $name ?: null,
                'line' => $index + 1,
            ];
        }

        return $items;
    }

    /**
     * Parsuje dwie niezalezne listy (SKU + Nazwy) i paruje je
     *
     * Use case: Uzytkownik wkleja SKU w jednej kolumnie, nazwy w drugiej
     * Metoda parsuje obie listy i paruje je po indeksie (1-to-1 mapping)
     *
     * SKU input moze zawierac multi-separatory (przecinki, sredniki, spacje)
     * Name input zawsze jeden na linie
     *
     * @param string $skuInput Lista SKU (moze zawierac multi-separatory)
     * @param string $nameInput Lista nazw (jeden na linie)
     * @return array{items: array, errors: array, warnings: array, stats: array}
     */
    public function parseTwoColumn(string $skuInput, string $nameInput): array
    {
        $result = [
            'items' => [],
            'errors' => [],
            'warnings' => [],
            'stats' => [
                'total_skus' => 0,
                'total_names' => 0,
                'paired_items' => 0,
                'unpaired_skus' => 0,
                'unpaired_names' => 0,
            ],
        ];

        // Parsuj SKU (z obsługa multi-separators)
        $skuLines = $this->splitLines($skuInput);
        $skuItems = $this->parseSkuOnlyIntelligent($skuLines, 'auto');
        $result['stats']['total_skus'] = count($skuItems);

        // Parsuj nazwy (jedna na linie)
        $nameLines = $this->splitLines($nameInput);
        $result['stats']['total_names'] = count($nameLines);

        // Walidacja count mismatch
        $skuCount = count($skuItems);
        $nameCount = count($nameLines);

        if ($skuCount !== $nameCount) {
            $result['warnings'][] = [
                'type' => 'count_mismatch',
                'message' => "Liczba SKU ({$skuCount}) rozni sie od liczby nazw ({$nameCount})",
                'skus' => $skuCount,
                'names' => $nameCount,
            ];
        }

        // Parowanie SKU + Nazwy
        $maxCount = max($skuCount, $nameCount);

        for ($i = 0; $i < $maxCount; $i++) {
            $sku = $skuItems[$i]['sku'] ?? null;
            $name = isset($nameLines[$i]) ? trim($nameLines[$i]) : null;

            // SKU bez nazwy
            if ($sku && !$name) {
                $validation = $this->validateSKUFormat($sku);

                if (!$validation['valid']) {
                    $result['errors'][] = [
                        'line' => $i + 1,
                        'sku' => $sku,
                        'message' => $validation['message'],
                    ];
                    continue;
                }

                $result['items'][] = [
                    'sku' => $sku,
                    'name' => null,
                    'line' => $i + 1,
                ];

                $result['warnings'][] = [
                    'type' => 'missing_name',
                    'line' => $i + 1,
                    'sku' => $sku,
                    'message' => "SKU '{$sku}' nie ma nazwy (linia " . ($i + 1) . ")",
                ];

                $result['stats']['unpaired_skus']++;
                continue;
            }

            // Nazwa bez SKU
            if (!$sku && $name) {
                $result['warnings'][] = [
                    'type' => 'missing_sku',
                    'line' => $i + 1,
                    'name' => $name,
                    'message' => "Nazwa '{$name}' nie ma SKU (linia " . ($i + 1) . ")",
                ];

                $result['stats']['unpaired_names']++;
                continue;
            }

            // Para SKU + Nazwa
            if ($sku && $name) {
                $validation = $this->validateSKUFormat($sku);

                if (!$validation['valid']) {
                    $result['errors'][] = [
                        'line' => $i + 1,
                        'sku' => $sku,
                        'message' => $validation['message'],
                    ];
                    continue;
                }

                $result['items'][] = [
                    'sku' => $sku,
                    'name' => $name,
                    'line' => $i + 1,
                ];

                $result['stats']['paired_items']++;
            }
        }

        return $result;
    }

    /**
     * Walidacja formatu SKU
     *
     * @return array{valid: bool, message: string|null}
     */
    public function validateSKUFormat(string $sku): array
    {
        $sku = trim($sku);

        if (strlen($sku) < self::MIN_SKU_LENGTH) {
            return [
                'valid' => false,
                'message' => "SKU zbyt krotkie (min. " . self::MIN_SKU_LENGTH . " znaki)",
            ];
        }

        if (strlen($sku) > self::MAX_SKU_LENGTH) {
            return [
                'valid' => false,
                'message' => "SKU zbyt dlugie (max. " . self::MAX_SKU_LENGTH . " znakow)",
            ];
        }

        if (!preg_match(self::SKU_PATTERN, $sku)) {
            return [
                'valid' => false,
                'message' => "SKU zawiera niedozwolone znaki (dozwolone: litery, cyfry, - _ . /)",
            ];
        }

        return ['valid' => true, 'message' => null];
    }

    /**
     * Sprawdzanie duplikatow w batch
     *
     * @param array<string> $skus
     * @return array<string, array<int>> Mapa SKU => linie dla duplikatow
     */
    public function checkDuplicatesInBatch(array $skus): array
    {
        $occurrences = [];
        $duplicates = [];

        foreach ($skus as $index => $sku) {
            $normalized = strtoupper(trim($sku));

            if (!isset($occurrences[$normalized])) {
                $occurrences[$normalized] = [];
            }

            $occurrences[$normalized][] = $index + 1; // Line number
        }

        foreach ($occurrences as $sku => $lines) {
            if (count($lines) > 1) {
                $duplicates[$sku] = $lines;
            }
        }

        return $duplicates;
    }

    /**
     * Walidacja duplikatow wzgledem bazy PPM
     *
     * @param array<string> $skus Lista SKU do sprawdzenia
     * @return array<string, int> Mapa SKU => product_id dla istniejacych
     */
    public function checkExistingInPPM(array $skus): array
    {
        if (empty($skus)) {
            return [];
        }

        $normalizedSkus = array_map(fn($s) => strtoupper(trim($s)), $skus);

        return Product::whereIn('sku', $normalizedSkus)
            ->pluck('id', 'sku')
            ->toArray();
    }

    /**
     * Walidacja duplikatow wzgledem pending products
     *
     * @param array<string> $skus Lista SKU do sprawdzenia
     * @param int|null $excludeSessionId ID sesji do wykluczenia
     * @return array<string, int> Mapa SKU => pending_product_id
     */
    public function checkExistingInPending(array $skus, ?int $excludeSessionId = null): array
    {
        if (empty($skus)) {
            return [];
        }

        $normalizedSkus = array_map(fn($s) => strtoupper(trim($s)), $skus);

        $query = PendingProduct::whereIn('sku', $normalizedSkus)
            ->whereNull('published_at');

        if ($excludeSessionId) {
            $query->where('import_session_id', '!=', $excludeSessionId);
        }

        return $query->pluck('id', 'sku')->toArray();
    }

    /**
     * Pelna walidacja z wykrywaniem wszystkich konfliktow
     *
     * @param array<string> $skus Lista SKU
     * @param int|null $sessionId ID sesji do wykluczenia
     * @return array{in_ppm: array, in_pending: array}
     */
    public function validateAgainstExisting(array $skus, ?int $sessionId = null): array
    {
        return [
            'in_ppm' => $this->checkExistingInPPM($skus),
            'in_pending' => $this->checkExistingInPending($skus, $sessionId),
        ];
    }

    /**
     * Konwersja nazwy separatora na znak
     */
    private function getSeparatorCharacter(string $separator): string
    {
        return match ($separator) {
            'tab' => "\t",
            'semicolon' => ';',
            'comma' => ',',
            'pipe' => '|',
            default => "\t",
        };
    }

    /**
     * Pobierz nazwe separatora z znaku
     */
    public function getSeparatorName(string $char): string
    {
        return match ($char) {
            "\t" => 'tab',
            ';' => 'semicolon',
            ',' => 'comma',
            '|' => 'pipe',
            default => 'auto',
        };
    }

    /**
     * Extract sample SKUs for preview (first 5)
     *
     * @param array $items
     * @return array
     */
    public function extractSample(array $items): array
    {
        return array_slice(
            array_map(fn($item) => $item['sku'], $items),
            0,
            5
        );
    }

    /**
     * Group items by status
     *
     * @param array $items
     * @return array{valid: array, duplicates: array, invalid: array, existing: array}
     */
    public function groupByStatus(array $items): array
    {
        $grouped = [
            'valid' => [],
            'duplicates' => [],
            'invalid' => [],
            'existing' => [],
        ];

        foreach ($items as $item) {
            if (isset($item['error'])) {
                if ($item['error'] === 'duplicate') {
                    $grouped['duplicates'][] = $item;
                } else {
                    $grouped['invalid'][] = $item;
                }
            } elseif ($item['existing'] ?? false) {
                $grouped['existing'][] = $item;
            } else {
                $grouped['valid'][] = $item;
            }
        }

        return $grouped;
    }

    /**
     * Convert parsed items to PendingProduct format
     *
     * @param array $items Valid items only
     * @return array
     */
    public function convertToPendingProducts(array $items): array
    {
        return array_map(function ($item) {
            return [
                'sku' => $item['sku'],
                'name' => $item['name'] ?? null,
                'status' => 'incomplete', // Will be filled by user in wizard
                'missing_fields' => ['productType', 'category', 'price'], // Minimal required
            ];
        }, $items);
    }
}
