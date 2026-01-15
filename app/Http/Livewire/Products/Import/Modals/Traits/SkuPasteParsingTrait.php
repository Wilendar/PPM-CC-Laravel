<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\Import\Modals\Traits;

/**
 * SkuPasteParsingTrait - Logika parsowania dla SKUPasteModal
 *
 * ETAP_06 FAZA 3: Single-column parsing
 * ETAP_06 FAZA 4: Two-column parsing
 *
 * Odpowiedzialnosc:
 * - Parsowanie single-column input (SKU only lub SKU + Name)
 * - Parsowanie two-column input (SKU | Names oddzielnie)
 * - Watchers dla zmian inputu (debounce w Blade)
 * - Validacja przeciwko istniejacym produktom
 * - Reset parsowania
 *
 * @method SkuParserService $parserService Service injected in main component
 */
trait SkuPasteParsingTrait
{
    /**
     * Parsowanie danych przy zmianie inputu (debounce w Blade)
     *
     * Single-column mode: parsuje $rawInput
     */
    public function parseInput(): void
    {
        if ($this->viewMode === 'two_columns') {
            $this->parseInputTwoColumn();
            return;
        }

        if (empty(trim($this->rawInput))) {
            $this->resetParseResults();
            return;
        }

        $result = $this->parserService->parse(
            $this->rawInput,
            $this->importMode,
            $this->separator
        );

        $this->applyParseResults($result);
    }

    /**
     * Parsowanie two-column mode (SKU | Names oddzielnie)
     *
     * Wymaga rownej liczby linii w obu textarea
     */
    public function parseInputTwoColumn(): void
    {
        if (empty(trim($this->rawSkuInput))) {
            $this->resetParseResults();
            return;
        }

        // Split lines
        $skuLines = $this->parserService->splitLines($this->rawSkuInput);
        $nameLines = $this->parserService->splitLines($this->rawNameInput);

        // Check count mismatch
        $this->viewModeWarnings = [];
        if (!empty($nameLines) && count($skuLines) !== count($nameLines)) {
            $this->viewModeWarnings[] = [
                'type' => 'count_mismatch',
                'sku_count' => count($skuLines),
                'name_count' => count($nameLines),
                'message' => sprintf(
                    'Liczba SKU (%d) nie zgadza sie z liczba nazw (%d)',
                    count($skuLines),
                    count($nameLines)
                ),
            ];
        }

        // Parse SKU lines
        $items = [];
        foreach ($skuLines as $index => $skuLine) {
            $sku = trim($skuLine);

            if ($sku === '') {
                continue;
            }

            $items[] = [
                'sku' => $sku,
                'name' => isset($nameLines[$index]) ? trim($nameLines[$index]) : null,
                'line' => $index + 1,
            ];
        }

        // Build result compatible with parse() output
        $result = [
            'items' => [],
            'errors' => [],
            'warnings' => [],
            'stats' => [
                'total_lines' => count($skuLines),
                'valid_items' => 0,
                'skipped_empty' => count($skuLines) - count($items),
                'duplicates_in_batch' => 0,
            ],
        ];

        // Validate SKU format
        foreach ($items as $item) {
            $validation = $this->parserService->validateSKUFormat($item['sku']);

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

        // Check duplicates in batch
        $duplicatesInBatch = $this->parserService->checkDuplicatesInBatch(
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

        // Remove duplicates (keep first)
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

        $this->applyParseResults($result);
    }

    /**
     * Callback when rawInput changes (single-column mode)
     */
    public function updatedRawInput(): void
    {
        if ($this->viewMode === 'single_column') {
            $this->parseInput();
        }
    }

    /**
     * Callback when rawSkuInput changes (two-column mode)
     */
    public function updatedRawSkuInput(): void
    {
        if ($this->viewMode === 'two_columns') {
            $this->parseInputTwoColumn();
        }
    }

    /**
     * Callback when rawNameInput changes (two-column mode)
     */
    public function updatedRawNameInput(): void
    {
        if ($this->viewMode === 'two_columns') {
            $this->parseInputTwoColumn();
        }
    }

    /**
     * Callback when import mode changes
     */
    public function updatedImportMode(): void
    {
        if ($this->viewMode === 'single_column') {
            $this->parseInput();
        }
        // Two-column mode ignores importMode (always SKU + Name)
    }

    /**
     * Callback when separator changes
     */
    public function updatedSeparator(): void
    {
        if ($this->viewMode === 'single_column') {
            $this->parseInput();
        }
        // Two-column mode ignores separator (newline-based)
    }

    /**
     * Apply parse results to component properties
     *
     * @param array $result Parse result from service
     */
    protected function applyParseResults(array $result): void
    {
        $this->parsedItems = $result['items'];
        $this->errors = $result['errors'];
        $this->warnings = $result['warnings'];
        $this->stats = $result['stats'];

        // Check conflicts with existing products
        if (!empty($this->parsedItems)) {
            $skus = array_column($this->parsedItems, 'sku');
            $conflicts = $this->parserService->validateAgainstExisting($skus);

            $this->existingInPPM = $conflicts['in_ppm'];
            $this->existingInPending = $conflicts['in_pending'];

            // Add warnings for conflicts
            foreach ($this->existingInPPM as $sku => $productId) {
                $this->warnings[] = [
                    'type' => 'exists_in_ppm',
                    'sku' => $sku,
                    'product_id' => $productId,
                    'message' => "SKU '{$sku}' juz istnieje w bazie produktow (ID: {$productId})",
                ];
            }

            foreach ($this->existingInPending as $sku => $pendingId) {
                $this->warnings[] = [
                    'type' => 'exists_in_pending',
                    'sku' => $sku,
                    'pending_id' => $pendingId,
                    'message' => "SKU '{$sku}' juz istnieje w oczekujacych produktach (ID: {$pendingId})",
                ];
            }
        }
    }

    /**
     * Reset parse results
     */
    protected function resetParseResults(): void
    {
        $this->parsedItems = [];
        $this->errors = [];
        $this->warnings = [];
        $this->viewModeWarnings = [];
        $this->stats = [
            'total_lines' => 0,
            'valid_items' => 0,
            'skipped_empty' => 0,
            'duplicates_in_batch' => 0,
        ];
        $this->existingInPPM = [];
        $this->existingInPending = [];
    }
}
