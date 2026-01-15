<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\Import\Modals;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\PendingProduct;
use App\Models\ImportSession;
use App\Services\Import\SkuParserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Livewire\Products\Import\Modals\Traits\SkuPasteParsingTrait;
use App\Http\Livewire\Products\Import\Modals\Traits\SkuPasteViewModeTrait;

/**
 * SKUPasteModal - Modal wklejania listy SKU
 *
 * ETAP_06 FAZA 3: Import SKU (wklejanie listy)
 * ETAP_06 FAZA 4: Two-column mode (SKU + Names separated)
 *
 * Obsluguje:
 * - Wklejanie danych z clipboard (single-column)
 * - Two-column mode (SKU | Names oddzielnie)
 * - Preview rozpoznanych SKU w czasie rzeczywistym
 * - Walidacja i wykrywanie duplikatow
 * - Tworzenie PendingProduct rekordow
 *
 * @property string $viewMode View mode: 'single_column' | 'two_columns'
 * @property string $rawInput Single-column textarea input
 * @property string $rawSkuInput Two-column SKU textarea
 * @property string $rawNameInput Two-column Names textarea
 * @property string $importMode Import mode: 'sku_only' | 'sku_name'
 * @property string $separator Separator: 'auto', 'tab', 'semicolon', 'comma', 'pipe'
 * @property string $separatorMode Separator mode: 'auto' | 'newline' | 'multi'
 * @property array $parsedItems Parsed items from input
 * @property array $errors Parsing errors
 * @property array $warnings Parsing warnings (duplicates, conflicts)
 * @property array $viewModeWarnings Count mismatch warnings for two-column mode
 * @property array $stats Parsing statistics
 * @property array $existingInPPM Existing SKUs in PPM database
 * @property array $existingInPending Existing SKUs in pending products
 * @property bool $isProcessing Import processing flag
 */
class SKUPasteModal extends Component
{
    use SkuPasteParsingTrait;
    use SkuPasteViewModeTrait;

    /**
     * View mode: 'single_column' | 'two_columns'
     */
    public string $viewMode = 'single_column';

    /**
     * Surowe dane wejsciowe z textarea (single-column mode)
     */
    public string $rawInput = '';

    /**
     * Two-column mode: SKU textarea
     */
    public string $rawSkuInput = '';

    /**
     * Two-column mode: Names textarea
     */
    public string $rawNameInput = '';

    /**
     * Tryb importu: 'sku_only' lub 'sku_name'
     */
    public string $importMode = 'sku_only';

    /**
     * Separator: 'auto', 'tab', 'semicolon', 'comma', 'pipe'
     */
    public string $separator = 'auto';

    /**
     * Separator mode: 'auto' | 'newline' | 'multi'
     */
    public string $separatorMode = 'auto';

    /**
     * Rozpoznane pozycje z parsingu
     */
    public array $parsedItems = [];

    /**
     * Bledy parsowania
     */
    public array $errors = [];

    /**
     * Ostrzezenia (np. duplikaty)
     */
    public array $warnings = [];

    /**
     * View mode warnings (count mismatch)
     */
    public array $viewModeWarnings = [];

    /**
     * Statystyki parsowania
     */
    public array $stats = [
        'total_lines' => 0,
        'valid_items' => 0,
        'skipped_empty' => 0,
        'duplicates_in_batch' => 0,
    ];

    /**
     * Istniejace SKU w bazie PPM
     */
    public array $existingInPPM = [];

    /**
     * Istniejace SKU w pending products
     */
    public array $existingInPending = [];

    /**
     * Flaga ladowania
     */
    public bool $isProcessing = false;

    /**
     * Service instance
     */
    protected SkuParserService $parserService;

    /**
     * Boot - inject service
     */
    public function boot(SkuParserService $parserService): void
    {
        $this->parserService = $parserService;
    }

    /**
     * Reset state when modal opens
     */
    #[On('openSkuModal')]
    public function resetState(): void
    {
        $this->reset([
            'rawInput',
            'rawSkuInput',
            'rawNameInput',
            'parsedItems',
            'errors',
            'warnings',
            'viewModeWarnings',
            'stats',
            'existingInPPM',
            'existingInPending',
            'isProcessing',
        ]);
        $this->viewMode = 'single_column';
        $this->importMode = 'sku_only';
        $this->separator = 'auto';
        $this->separatorMode = 'auto';
    }

    /**
     * Import - tworzenie PendingProduct rekordow
     */
    public function import(): void
    {
        if (empty($this->parsedItems)) {
            $this->addError('import', 'Brak danych do zaimportowania');
            return;
        }

        $this->isProcessing = true;

        try {
            DB::beginTransaction();

            // Get user ID with fallback for development mode
            $userId = Auth::id() ?? 8; // Fallback to admin user ID 8 in dev mode

            // Utworz sesje importu
            $session = ImportSession::create([
                'uuid' => \Str::uuid()->toString(),
                'session_name' => 'SKU Import ' . now()->format('Y-m-d H:i'),
                'import_method' => ImportSession::METHOD_PASTE_SKU,
                'status' => ImportSession::STATUS_PARSING,
                'imported_by' => $userId,
                'total_rows' => count($this->parsedItems),
                'products_created' => 0,
                'products_published' => 0,
                'products_failed' => 0,
                'products_skipped' => 0,
            ]);

            $createdCount = 0;
            $skippedCount = 0;

            foreach ($this->parsedItems as $item) {
                $sku = strtoupper(trim($item['sku']));

                // Pomin jesli juz istnieje w PPM
                if (isset($this->existingInPPM[$sku])) {
                    $skippedCount++;
                    continue;
                }

                // Pomin jesli juz istnieje w pending
                if (isset($this->existingInPending[$sku])) {
                    $skippedCount++;
                    continue;
                }

                PendingProduct::create([
                    'import_session_id' => $session->id,
                    'sku' => $sku,
                    'name' => $item['name'] ?? null,
                    'imported_by' => $userId,
                    'completion_status' => [
                        'sku' => true,
                        'name' => !empty($item['name']),
                        'product_type' => false,
                        'categories' => false,
                        'shops' => false,
                    ],
                ]);

                $createdCount++;
            }

            // Aktualizuj sesje
            $session->update([
                'status' => ImportSession::STATUS_READY,
                'products_created' => $createdCount,
                'products_skipped' => $skippedCount,
            ]);

            DB::commit();

            Log::info('SKU Import completed', [
                'session_id' => $session->id,
                'created' => $createdCount,
                'skipped' => $skippedCount,
                'user_id' => $userId,
                'view_mode' => $this->viewMode,
            ]);

            // Dispatch event to parent
            $this->dispatch('skuImportCompleted', $createdCount);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('SKU Import failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            $this->addError('import', 'Blad podczas importu: ' . $e->getMessage());
        } finally {
            $this->isProcessing = false;
        }
    }

    /**
     * Close modal
     */
    public function close(): void
    {
        $this->dispatch('closeModal')->to('products.import.product-import-panel');
    }

    /**
     * Get separators for dropdown
     */
    public function getSeparators(): array
    {
        return SkuParserService::SEPARATORS;
    }

    /**
     * Get import modes for radio buttons
     */
    public function getImportModes(): array
    {
        return SkuParserService::IMPORT_MODES;
    }

    /**
     * Count items ready to import (excluding conflicts)
     */
    public function getImportableCount(): int
    {
        $conflictSkus = array_merge(
            array_keys($this->existingInPPM),
            array_keys($this->existingInPending)
        );

        return count(array_filter($this->parsedItems, function ($item) use ($conflictSkus) {
            return !in_array(strtoupper(trim($item['sku'])), $conflictSkus);
        }));
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.products.import.modals.sku-paste-modal', [
            'separators' => $this->getSeparators(),
            'importModes' => $this->getImportModes(),
            'importableCount' => $this->getImportableCount(),
        ]);
    }
}
