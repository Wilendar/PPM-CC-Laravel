<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\Import\Modals;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use App\Models\PendingProduct;
use App\Models\ProductType;
use App\Models\BusinessPartner;
use App\Services\Import\CsvParserService;
use App\Services\Import\ColumnMappingService;
use Illuminate\Support\Facades\Log;

/**
 * ProductImportModal - Unified product import modal
 *
 * Replaces old SKUPasteModal and CSVImportModal with a single modal
 * that supports two modes:
 *
 * 1. Column Mode (default): Spreadsheet-like interface with dynamic columns
 *    - Fixed columns: SKU, Name
 *    - Optional columns: ProductType, Supplier, Manufacturer, EAN, etc.
 *    - Add/remove rows, paste from clipboard
 *    - Edit mode: pre-fill from existing PendingProduct
 *
 * 2. CSV Mode: Paste or upload CSV data
 *    - Paste semicolon-separated text
 *    - Upload CSV file
 *    - Auto-mapping with manual override
 *    - Preview before import
 *
 * Both modes share switches (shop_internet, split_payment, variant_product)
 * and create ImportSession + PendingProduct records.
 *
 * @package App\Http\Livewire\Products\Import\Modals
 */
class ProductImportModal extends Component
{
    use WithFileUploads;
    use Traits\ImportModalCsvModeTrait;
    use Traits\ImportModalColumnModeTrait;
    use Traits\ImportModalSwitchesTrait;

    /*
    |--------------------------------------------------------------------------
    | MODAL STATE
    |--------------------------------------------------------------------------
    */

    /**
     * Modal visibility flag
     */
    public bool $showModal = false;

    /**
     * Active import mode: 'column' or 'csv'
     */
    public string $activeMode = 'column';

    /**
     * Editing existing PendingProduct ID (null = new import)
     */
    public ?int $editingPendingProductId = null;

    /*
    |--------------------------------------------------------------------------
    | SERVICES (injected via boot)
    |--------------------------------------------------------------------------
    */

    protected CsvParserService $csvParser;
    protected ColumnMappingService $mappingService;

    /**
     * Boot - inject services on every request
     *
     * Uses Livewire boot() lifecycle method for dependency injection
     * to avoid non-nullable property issues.
     */
    public function boot(
        CsvParserService $csvParser,
        ColumnMappingService $mappingService
    ): void {
        $this->csvParser = $csvParser;
        $this->mappingService = $mappingService;
    }

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE
    |--------------------------------------------------------------------------
    */

    /**
     * Open import modal
     *
     * If pendingProductId is provided, opens in edit mode (column mode)
     * with pre-filled data from the existing PendingProduct.
     */
    #[On('openImportModal')]
    public function openModal(?int $pendingProductId = null): void
    {
        Log::debug('ProductImportModal: openModal CALLED', [
            'pendingProductId' => $pendingProductId,
        ]);

        $this->resetAllState();
        $this->showModal = true;

        if ($pendingProductId) {
            $this->openForEdit($pendingProductId);
        } else {
            $this->activeMode = 'column';
            $this->initColumnMode();
        }
    }

    /**
     * Open modal pre-filled with existing PendingProduct data
     *
     * Automatically switches to column mode.
     * Sets activeColumns based on which fields have values.
     */
    #[On('openImportModalForEdit')]
    public function openForEdit(int $pendingProductId): void
    {
        Log::debug('ProductImportModal: openForEdit CALLED', [
            'pendingProductId' => $pendingProductId,
        ]);

        $product = PendingProduct::find($pendingProductId);

        if (!$product) {
            $this->addError('modal', 'Nie znaleziono produktu o ID: ' . $pendingProductId);
            Log::warning('ProductImportModal: PendingProduct not found', [
                'id' => $pendingProductId,
            ]);
            return;
        }

        $this->resetAllState();
        $this->showModal = true;
        $this->activeMode = 'column';
        $this->editingPendingProductId = $pendingProductId;

        // Initialize dropdown data
        $this->initColumnMode();

        // Load product data into the form
        $this->loadEditData($product);

        Log::debug('ProductImportModal: openForEdit COMPLETED', [
            'sku' => $product->sku,
            'active_columns' => $this->activeColumns,
        ]);
    }

    /**
     * Close modal and reset all state
     */
    public function closeModal(): void
    {
        Log::debug('ProductImportModal: closeModal');

        $this->showModal = false;
        $this->resetAllState();
    }

    /**
     * Switch between import modes
     *
     * Resets mode-specific state when switching.
     */
    public function switchMode(string $mode): void
    {
        if (!in_array($mode, ['column', 'csv'], true)) {
            return;
        }

        if ($this->activeMode === $mode) {
            return;
        }

        Log::debug('ProductImportModal: switchMode', [
            'from' => $this->activeMode,
            'to' => $mode,
        ]);

        $this->activeMode = $mode;

        // Reset mode-specific state
        if ($mode === 'column') {
            $this->resetCsvState();
            $this->initColumnMode();
        } else {
            $this->resetColumnState();
        }

        // Clear any mode-specific errors
        $this->resetErrorBag();
    }

    /*
    |--------------------------------------------------------------------------
    | STATE MANAGEMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Reset all state across all traits
     */
    protected function resetAllState(): void
    {
        $this->activeMode = 'column';
        $this->editingPendingProductId = null;

        $this->resetCsvState();
        $this->resetColumnState();
        $this->resetSwitches();
        $this->resetErrorBag();
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED DATA FOR VIEW
    |--------------------------------------------------------------------------
    */

    /**
     * Get available columns that haven't been activated yet
     *
     * For the "Add Column" dropdown in column mode.
     *
     * @return array<string, string> key => label
     */
    public function getAvailableColumnsForAdd(): array
    {
        $available = [];

        foreach ($this->getAvailableColumns() as $key => $meta) {
            if (!in_array($key, $this->activeColumns, true)) {
                $available[$key] = $meta['label'];
            }
        }

        return $available;
    }

    /**
     * Get CSV mappable fields for dropdown
     *
     * @return array<string, string>
     */
    public function getCsvMappableFields(): array
    {
        return self::CSV_MAPPABLE_FIELDS;
    }

    /**
     * Check if currently in edit mode
     */
    public function isEditMode(): bool
    {
        return $this->editingPendingProductId !== null;
    }

    /**
     * Get modal title based on current mode and state
     */
    public function getModalTitle(): string
    {
        if ($this->isEditMode()) {
            return 'Edycja produktu';
        }

        return match ($this->activeMode) {
            'csv' => 'Import produktow (CSV)',
            'column' => 'Import produktow',
            default => 'Import produktow',
        };
    }

    /**
     * Get the count of valid rows ready for import (column mode)
     */
    public function getValidRowCount(): int
    {
        return count(array_filter($this->rows, function (array $row) {
            return !empty(trim($row['sku'] ?? ''));
        }));
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    /**
     * Render component
     *
     * This is a CHILD component - no layout specified.
     * Passes dropdown data and computed values to the view.
     */
    public function render()
    {
        return view('livewire.products.import.modals.product-import-modal', [
            'columnDefinitions' => $this->getActiveColumnDefinitions(),
            'availableColumnsForAdd' => $this->getAvailableColumnsForAdd(),
            'availableColumns' => $this->getAvailableColumns(),
            'csvMappableFields' => $this->getCsvMappableFields(),
            'modalTitle' => $this->getModalTitle(),
            'validRowCount' => $this->getValidRowCount(),
            'isEditMode' => $this->isEditMode(),
        ]);
    }
}
