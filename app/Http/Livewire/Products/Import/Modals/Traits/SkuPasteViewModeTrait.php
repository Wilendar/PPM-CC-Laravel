<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\Import\Modals\Traits;

/**
 * SkuPasteViewModeTrait - Logika trybu widoku dla SKUPasteModal
 *
 * ETAP_06 FAZA 4: Two-column mode helpers
 *
 * Odpowiedzialnosc:
 * - Przełączanie między single-column a two-column mode
 * - Placeholder texts dla textarea
 * - Count helpers dla SKU/Names
 * - Count mismatch detection
 *
 * @method void parseInput() Trigger parsing (from SkuPasteParsingTrait)
 * @method void parseInputTwoColumn() Trigger two-column parsing (from SkuPasteParsingTrait)
 */
trait SkuPasteViewModeTrait
{
    /**
     * Callback when view mode changes
     *
     * Logic:
     * - Single → Two: Split existing data
     * - Two → Single: Merge back (SKU only)
     * - Reset parsing state
     */
    public function updatedViewMode(): void
    {
        // Reset parsing
        $this->parsedItems = [];
        $this->errors = [];
        $this->warnings = [];
        $this->viewModeWarnings = [];

        if ($this->viewMode === 'single_column') {
            // Merge back: copy SKU input to rawInput
            $this->rawInput = $this->rawSkuInput;
            $this->rawSkuInput = '';
            $this->rawNameInput = '';

            // Force to sku_only mode when switching to single-column
            $this->importMode = 'sku_only';

            $this->parseInput();
        } else {
            // Split: move rawInput to rawSkuInput
            $this->rawSkuInput = $this->rawInput;
            $this->rawNameInput = '';
            $this->rawInput = '';

            // Two-column mode always uses SKU + Name
            $this->importMode = 'sku_name';

            $this->parseInputTwoColumn();
        }
    }

    /**
     * Get placeholder text for textarea based on mode
     *
     * @param string $field 'single', 'sku', or 'name'
     * @return string
     */
    public function getPlaceholderText(string $field = 'single'): string
    {
        return match ($field) {
            'single' => $this->importMode === 'sku_only'
                ? "Wklej liste SKU (jeden na linie):\nSKU001\nSKU002\nSKU003"
                : "Wklej liste SKU + Nazwy (oddzielone separatorem):\nSKU001\tNazwa produktu 1\nSKU002\tNazwa produktu 2",

            'sku' => "Wklej liste SKU (jeden na linie):\nSKU001\nSKU002\nSKU003",

            'name' => "Wklej liste nazw (jeden na linie):\nNazwa produktu 1\nNazwa produktu 2\nNazwa produktu 3",

            default => '',
        };
    }

    /**
     * Get SKU count (two-column mode)
     *
     * @return int
     */
    public function getSkuCount(): int
    {
        if (empty(trim($this->rawSkuInput))) {
            return 0;
        }

        return count($this->parserService->splitLines($this->rawSkuInput));
    }

    /**
     * Get Name count (two-column mode)
     *
     * @return int
     */
    public function getNameCount(): int
    {
        if (empty(trim($this->rawNameInput))) {
            return 0;
        }

        return count($this->parserService->splitLines($this->rawNameInput));
    }

    /**
     * Check if count mismatch exists (two-column mode)
     *
     * @return bool
     */
    public function hasCountMismatch(): bool
    {
        if ($this->viewMode !== 'two_columns') {
            return false;
        }

        $skuCount = $this->getSkuCount();
        $nameCount = $this->getNameCount();

        // No mismatch if either is empty
        if ($skuCount === 0 || $nameCount === 0) {
            return false;
        }

        return $skuCount !== $nameCount;
    }

    /**
     * Get count mismatch message (two-column mode)
     *
     * @return string|null
     */
    public function getCountMismatchMessage(): ?string
    {
        if (!$this->hasCountMismatch()) {
            return null;
        }

        $skuCount = $this->getSkuCount();
        $nameCount = $this->getNameCount();

        return sprintf(
            'Liczba SKU (%d) nie zgadza sie z liczba nazw (%d). SKU bez odpowiadajacej nazwy otrzymaja null.',
            $skuCount,
            $nameCount
        );
    }

    /**
     * Get view mode label for UI
     *
     * @return string
     */
    public function getViewModeLabel(): string
    {
        return match ($this->viewMode) {
            'single_column' => 'Jedna kolumna',
            'two_columns' => 'Dwie kolumny (SKU | Nazwy)',
            default => 'Nieznany tryb',
        };
    }

    /**
     * Check if can switch to two-column mode
     *
     * Prevents switch if single-column has SKU + Name separator data
     *
     * @return bool
     */
    public function canSwitchToTwoColumn(): bool
    {
        // Can always switch if empty
        if (empty(trim($this->rawInput))) {
            return true;
        }

        // Can switch if sku_only mode (no separator data to lose)
        if ($this->importMode === 'sku_only') {
            return true;
        }

        // Warning: switching will lose separator structure
        return false;
    }

    /**
     * Get switch mode warning message
     *
     * @return string|null
     */
    public function getSwitchModeWarning(): ?string
    {
        if ($this->viewMode === 'two_columns' && !$this->canSwitchToTwoColumn()) {
            return 'Przełączenie na tryb jedna kolumna spowoduje utrate nazw. Skopiuj dane przed przełaczeniem.';
        }

        if ($this->viewMode === 'single_column' && $this->importMode === 'sku_name' && !empty(trim($this->rawInput))) {
            return 'Przełączenie na tryb dwie kolumny spowoduje utrate struktury SKU+Nazwa. Dane zostana rozdzielone jako SKU (lewa) i puste nazwy (prawa).';
        }

        return null;
    }
}
