<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\Import\Modals\Traits;

use App\Models\BusinessPartner;
use App\Models\ImportSession;
use App\Models\PendingProduct;
use App\Models\PriceGroup;
use App\Models\ProductType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ImportModalColumnModeTrait - Column-based import mode for ProductImportModal
 *
 * Provides a dynamic spreadsheet-like interface where:
 * - SKU and Name columns are always visible (fixed)
 * - Optional columns can be added/removed dynamically
 * - Rows can be added, removed, or pasted from clipboard
 * - Dropdown columns load data from DB (ProductType, BusinessPartner)
 * - Supports edit mode for existing PendingProducts
 *
 * @package App\Http\Livewire\Products\Import\Modals\Traits
 */
trait ImportModalColumnModeTrait
{
    /**
     * Fixed columns that are always present and cannot be removed
     */
    public const FIXED_COLUMNS = ['sku', 'name'];

    /**
     * Base optional columns with metadata (static, no DB dependency)
     *
     * type: 'input' = free text, 'dropdown' = select from DB, 'price' = price input
     */
    public static array $baseColumns = [
        'product_type_id' => ['label' => 'Typ produktu', 'type' => 'dropdown'],
        'supplier_code'   => ['label' => 'Kod Dostawcy', 'type' => 'input'],
        'supplier_id'     => ['label' => 'Dostawca', 'type' => 'dropdown'],
        'manufacturer_id' => ['label' => 'Producent', 'type' => 'dropdown'],
        'importer_id'     => ['label' => 'Importer', 'type' => 'dropdown'],
        'ean'             => ['label' => 'EAN', 'type' => 'input'],
        'cn_code'         => ['label' => 'Kod CN', 'type' => 'input'],
        'material'        => ['label' => 'Material', 'type' => 'input'],
        'defect_symbol'   => ['label' => 'Symbol z wada', 'type' => 'input'],
        'application'     => ['label' => 'Zastosowanie', 'type' => 'input'],
    ];

    /**
     * Cached available columns (base + dynamic price groups)
     */
    protected ?array $cachedAvailableColumns = null;

    /**
     * Price display mode: 'net' or 'gross'
     */
    public string $priceDisplayMode = 'net';

    /**
     * Currently active optional columns (keys from getAvailableColumns())
     */
    public array $activeColumns = [];

    /**
     * Row data: indexed array of associative arrays
     * Each row has keys for fixed + active columns.
     *
     * Example: [
     *   ['sku' => 'ABC-001', 'name' => 'Product 1', 'ean' => '1234567890123'],
     *   ['sku' => 'ABC-002', 'name' => 'Product 2', 'ean' => ''],
     * ]
     */
    public array $rows = [];

    /**
     * Dropdown options loaded from DB
     */
    public array $productTypes = [];
    public array $suppliers = [];
    public array $manufacturers = [];
    public array $importers = [];

    /**
     * Get available columns: base + dynamic price groups from DB
     *
     * @return array<string, array{label: string, type: string}>
     */
    public function getAvailableColumns(): array
    {
        if ($this->cachedAvailableColumns !== null) {
            return $this->cachedAvailableColumns;
        }

        $columns = self::$baseColumns;

        $priceGroups = PriceGroup::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'code']);

        foreach ($priceGroups as $group) {
            $key = 'price_group_' . $group->id;
            $columns[$key] = [
                'label' => $group->name,
                'type' => 'price',
                'price_group_id' => $group->id,
                'price_group_code' => $group->code,
            ];
        }

        $this->cachedAvailableColumns = $columns;

        return $columns;
    }

    /**
     * Toggle price display mode between net and gross
     */
    public function togglePriceDisplayMode(): void
    {
        $this->priceDisplayMode = $this->priceDisplayMode === 'net' ? 'gross' : 'net';
        $this->saveColumnLayout();
    }

    /**
     * Paste clipboard text into a single column (targeted paste)
     *
     * Each line of text fills one row in the specified column.
     */
    public function pasteToColumn(string $columnKey, string $text): void
    {
        $lines = preg_split('/\r?\n/', trim($text));
        $lines = array_values(array_filter($lines, fn($l) => trim($l) !== ''));

        if (empty($lines)) {
            return;
        }

        // Clear rows if first row is empty placeholder
        if (count($this->rows) === 1
            && empty(trim($this->rows[0]['sku'] ?? ''))
            && empty(trim($this->rows[0]['name'] ?? ''))
        ) {
            $this->rows = [];
        }

        foreach ($lines as $i => $value) {
            $value = trim($value);
            if (!isset($this->rows[$i])) {
                $this->rows[$i] = $this->createEmptyRow();
            }
            $this->rows[$i][$columnKey] = $value;
        }

        Log::debug('ImportModalColumnModeTrait: pasteToColumn', [
            'column' => $columnKey,
            'lines' => count($lines),
            'total_rows' => count($this->rows),
        ]);
    }

    /**
     * Copy value from first row to all rows in a column
     */
    public function copyDownColumn(string $columnKey): void
    {
        if (empty($this->rows)) {
            return;
        }

        $sourceValue = $this->rows[0][$columnKey] ?? '';
        if ($sourceValue === '' || $sourceValue === null) {
            return;
        }

        foreach ($this->rows as $i => &$row) {
            if ($i === 0) {
                continue;
            }
            $row[$columnKey] = $sourceValue;
        }
        unset($row);
    }

    /**
     * Fill column range with source row value (drag-fill)
     */
    public function fillColumnRange(string $colKey, int $sourceRow, int $startRow, int $endRow): void
    {
        $value = $this->rows[$sourceRow][$colKey] ?? '';

        for ($i = $startRow; $i <= $endRow; $i++) {
            if (isset($this->rows[$i])) {
                $this->rows[$i][$colKey] = $value;
            }
        }
    }

    /**
     * Load saved column layout from user preferences
     */
    public function loadSavedColumnLayout(): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }
        $prefs = $user->import_column_preferences ?? [];

        if (!empty($prefs['active_columns'])) {
            $available = array_keys($this->getAvailableColumns());
            $this->activeColumns = array_values(
                array_intersect($prefs['active_columns'], $available)
            );
        }

        $this->priceDisplayMode = $prefs['price_display_mode'] ?? 'net';
    }

    /**
     * Save current column layout to user preferences
     */
    public function saveColumnLayout(): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $user->update([
            'import_column_preferences' => [
                'active_columns' => $this->activeColumns,
                'price_display_mode' => $this->priceDisplayMode,
                'updated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Initialize column mode
     *
     * Loads dropdown data from DB and sets initial empty row.
     */
    public function initColumnMode(): void
    {
        $this->loadDropdownData();
        $this->loadSavedColumnLayout();

        // Start with one empty row if no rows exist
        if (empty($this->rows)) {
            $this->rows = [$this->createEmptyRow()];
        }

        Log::debug('ImportModalColumnModeTrait: initColumnMode COMPLETED', [
            'productTypes_count' => count($this->productTypes),
            'suppliers_count' => count($this->suppliers),
            'manufacturers_count' => count($this->manufacturers),
            'importers_count' => count($this->importers),
            'active_columns' => $this->activeColumns,
            'price_display_mode' => $this->priceDisplayMode,
        ]);
    }

    /**
     * Load dropdown data from database
     */
    protected function loadDropdownData(): void
    {
        $this->productTypes = ProductType::active()
            ->ordered()
            ->get(['id', 'name'])
            ->map(fn($t) => ['id' => $t->id, 'name' => $t->name])
            ->toArray();

        $this->suppliers = BusinessPartner::getForDropdown(BusinessPartner::TYPE_SUPPLIER)
            ->map(fn($p) => ['id' => $p->id, 'name' => $p->name])
            ->toArray();

        $this->manufacturers = BusinessPartner::getForDropdown(BusinessPartner::TYPE_MANUFACTURER)
            ->map(fn($p) => ['id' => $p->id, 'name' => $p->name])
            ->toArray();

        $this->importers = BusinessPartner::getForDropdown(BusinessPartner::TYPE_IMPORTER)
            ->map(fn($p) => ['id' => $p->id, 'name' => $p->name])
            ->toArray();
    }

    /**
     * Add an optional column
     */
    public function addColumn(string $columnKey): void
    {
        if (!array_key_exists($columnKey, $this->getAvailableColumns())) {
            return;
        }

        if (in_array($columnKey, $this->activeColumns, true)) {
            return;
        }

        $this->activeColumns[] = $columnKey;

        // Add empty value for this column in all existing rows
        foreach ($this->rows as $index => $row) {
            $this->rows[$index][$columnKey] = '';
        }

        $this->saveColumnLayout();

        Log::debug('ImportModalColumnModeTrait: addColumn', [
            'column' => $columnKey,
            'active_columns' => $this->activeColumns,
        ]);
    }

    /**
     * Remove an optional column
     */
    public function removeColumn(string $columnKey): void
    {
        // Cannot remove fixed columns
        if (in_array($columnKey, self::FIXED_COLUMNS, true)) {
            return;
        }

        $this->activeColumns = array_values(
            array_filter($this->activeColumns, fn($col) => $col !== $columnKey)
        );

        // Remove column data from all rows
        foreach ($this->rows as $index => $row) {
            unset($this->rows[$index][$columnKey]);
        }

        $this->saveColumnLayout();

        Log::debug('ImportModalColumnModeTrait: removeColumn', [
            'column' => $columnKey,
            'active_columns' => $this->activeColumns,
        ]);
    }

    /**
     * Add an empty row at the end
     */
    public function addEmptyRow(): void
    {
        $this->rows[] = $this->createEmptyRow();
    }

    /**
     * Remove a row by index
     *
     * Minimum 1 row must remain.
     */
    public function removeRow(int $index): void
    {
        if (count($this->rows) <= 1) {
            return;
        }

        if (!isset($this->rows[$index])) {
            return;
        }

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);

        Log::debug('ImportModalColumnModeTrait: removeRow', [
            'removed_index' => $index,
            'remaining_rows' => count($this->rows),
        ]);
    }

    /**
     * Handle pasted text from clipboard
     *
     * Detects tab or semicolon separator.
     * Creates one row per line, distributing values across columns in order:
     * fixed columns first (sku, name), then active columns.
     * Auto-activates additional columns if paste has more data than current columns.
     *
     * Called from Alpine.js paste event handler.
     */
    public function pasteFromClipboard(string $pastedText): void
    {
        if (empty(trim($pastedText))) {
            return;
        }

        Log::debug('ImportModalColumnModeTrait: pasteFromClipboard CALLED', [
            'text_length' => strlen($pastedText),
        ]);

        $lines = preg_split('/\r?\n/', trim($pastedText));
        $lines = array_filter($lines, fn(string $line) => trim($line) !== '');

        if (empty($lines)) {
            return;
        }

        // Detect separator: tab or semicolon
        $firstLine = $lines[0];
        $separator = str_contains($firstLine, "\t") ? "\t" : ';';

        // Count columns in pasted data
        $pastedColCount = count(array_map('trim', explode($separator, $firstLine)));
        $currentColCount = count($this->getColumnOrder());

        // Auto-activate additional columns if paste has more data
        if ($pastedColCount > $currentColCount) {
            $availableKeys = array_keys($this->getAvailableColumns());
            $unusedKeys = array_values(array_diff($availableKeys, $this->activeColumns));

            $needed = $pastedColCount - $currentColCount;
            $toAdd = array_slice($unusedKeys, 0, $needed);

            foreach ($toAdd as $key) {
                $this->addColumn($key);
            }
        }

        // Get ordered column keys (updated after adding)
        $columnOrder = $this->getColumnOrder();

        // Clear existing rows if first row is empty
        if (count($this->rows) === 1
            && empty(trim($this->rows[0]['sku'] ?? ''))
            && empty(trim($this->rows[0]['name'] ?? ''))
        ) {
            $this->rows = [];
        }

        foreach ($lines as $line) {
            $values = array_map('trim', explode($separator, $line));

            $row = $this->createEmptyRow();

            foreach ($columnOrder as $colIndex => $colKey) {
                if (isset($values[$colIndex])) {
                    $row[$colKey] = $values[$colIndex];
                }
            }

            $this->rows[] = $row;
        }

        Log::debug('ImportModalColumnModeTrait: pasteFromClipboard COMPLETED', [
            'lines_parsed' => count($lines),
            'separator' => $separator === "\t" ? 'tab' : 'semicolon',
            'total_rows' => count($this->rows),
            'active_columns' => $this->activeColumns,
        ]);
    }

    /**
     * Update a specific field in a specific row
     */
    public function updateRowField(int $rowIndex, string $field, $value): void
    {
        if (!isset($this->rows[$rowIndex])) {
            return;
        }

        $this->rows[$rowIndex][$field] = $value;
    }

    /**
     * Import column rows as PendingProducts
     *
     * Validates all rows (SKU required per row).
     * Creates ImportSession, PendingProduct per row.
     * Applies switches from ImportModalSwitchesTrait.
     */
    public function importColumnRows(): void
    {
        // Validate rows
        $validRows = [];
        $validationErrors = [];

        foreach ($this->rows as $index => $row) {
            $sku = strtoupper(trim($row['sku'] ?? ''));

            if (empty($sku)) {
                $validationErrors[] = "Wiersz " . ($index + 1) . ": brak SKU";
                continue;
            }

            // EAN validation (if provided)
            $ean = trim($row['ean'] ?? '');
            if (!empty($ean) && !$this->validateEan($ean)) {
                $validationErrors[] = "Wiersz " . ($index + 1) . ": nieprawidlowy EAN '{$ean}'";
            }

            $validRows[] = $row;
        }

        if (empty($validRows)) {
            $this->addError('columnImport', 'Brak prawidlowych wierszy do importu');
            return;
        }

        Log::debug('ImportModalColumnModeTrait: importColumnRows CALLED', [
            'valid_rows' => count($validRows),
            'validation_errors' => count($validationErrors),
        ]);

        try {
            DB::beginTransaction();

            $userId = Auth::id() ?? 8;

            $session = ImportSession::create([
                'uuid' => Str::uuid()->toString(),
                'session_name' => 'Modal Import (Column) ' . now()->format('Y-m-d H:i'),
                'import_method' => 'modal_import',
                'status' => ImportSession::STATUS_PARSING,
                'imported_by' => $userId,
                'total_rows' => count($validRows),
                'products_created' => 0,
                'products_published' => 0,
                'products_failed' => 0,
                'products_skipped' => 0,
            ]);

            $createdCount = 0;
            $skippedCount = 0;

            foreach ($validRows as $row) {
                $sku = strtoupper(trim($row['sku']));

                // Check for duplicates
                $existsInProducts = \App\Models\Product::where('sku', $sku)->exists();
                $existsInPending = PendingProduct::where('sku', $sku)
                    ->whereNull('published_at')
                    ->exists();

                // In edit mode, allow updating existing pending product
                if ($this->editingPendingProductId && $existsInPending) {
                    $this->updateExistingPendingProduct($row);
                    $createdCount++;
                    continue;
                }

                if ($existsInProducts || $existsInPending) {
                    $skippedCount++;
                    continue;
                }

                // Extract price data from price_group_* columns
                $priceData = $this->extractPriceData($row);

                $product = PendingProduct::create([
                    'import_session_id' => $session->id,
                    'sku' => $sku,
                    'name' => trim($row['name'] ?? '') ?: null,
                    'product_type_id' => !empty($row['product_type_id']) ? (int) $row['product_type_id'] : null,
                    'supplier_id' => !empty($row['supplier_id']) ? (int) $row['supplier_id'] : null,
                    'manufacturer_id' => !empty($row['manufacturer_id']) ? (int) $row['manufacturer_id'] : null,
                    'importer_id' => !empty($row['importer_id']) ? (int) $row['importer_id'] : null,
                    'supplier_code' => trim($row['supplier_code'] ?? '') ?: null,
                    'ean' => trim($row['ean'] ?? '') ?: null,
                    'cn_code' => trim($row['cn_code'] ?? '') ?: null,
                    'material' => trim($row['material'] ?? '') ?: null,
                    'defect_symbol' => trim($row['defect_symbol'] ?? '') ?: null,
                    'application' => trim($row['application'] ?? '') ?: null,
                    'price_data' => !empty($priceData) ? $priceData : null,
                    'imported_by' => $userId,
                    'imported_at' => now(),
                ]);

                // Apply switches
                $this->applySwitchesToProduct($product);
                $product->save();

                $createdCount++;
            }

            $session->markAsReady($createdCount, $skippedCount);

            DB::commit();

            Log::info('ImportModalColumnModeTrait: importColumnRows COMPLETED', [
                'session_id' => $session->id,
                'created' => $createdCount,
                'skipped' => $skippedCount,
            ]);

            $this->dispatch('importCompleted', count: $createdCount);

            // Close modal after successful import
            $this->closeModal();

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('ImportModalColumnModeTrait: importColumnRows FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addError('columnImport', 'Blad podczas importu: ' . $e->getMessage());
        }
    }

    /**
     * Load existing PendingProduct data for edit mode
     *
     * Populates rows[0] with product data and activates relevant columns.
     */
    protected function loadEditData(PendingProduct $product): void
    {
        Log::debug('ImportModalColumnModeTrait: loadEditData CALLED', [
            'pending_product_id' => $product->id,
            'sku' => $product->sku,
        ]);

        // Build row from product data
        $row = [
            'sku' => $product->sku ?? '',
            'name' => $product->name ?? '',
        ];

        // Check each optional column and activate if product has data
        $columnsToActivate = [];

        $fieldMapping = [
            'product_type_id' => $product->product_type_id,
            'supplier_code' => $product->supplier_code,
            'supplier_id' => $product->supplier_id,
            'manufacturer_id' => $product->manufacturer_id,
            'importer_id' => $product->importer_id,
            'ean' => $product->ean,
            'cn_code' => $product->cn_code,
            'material' => $product->material,
            'defect_symbol' => $product->defect_symbol,
            'application' => $product->application,
        ];

        foreach ($fieldMapping as $key => $value) {
            if (!is_null($value) && $value !== '') {
                $columnsToActivate[] = $key;
                $row[$key] = (string) $value;
            }
        }

        // Activate columns that have data
        $this->activeColumns = $columnsToActivate;

        // Ensure all active columns exist in the row
        foreach ($this->activeColumns as $col) {
            if (!isset($row[$col])) {
                $row[$col] = '';
            }
        }

        $this->rows = [$row];

        // Load switches from product
        $this->switchShopInternet = (bool) $product->shop_internet;
        $this->switchSplitPayment = (bool) $product->split_payment;
        $this->switchVariantProduct = (bool) $product->is_variant_master;

        Log::debug('ImportModalColumnModeTrait: loadEditData COMPLETED', [
            'active_columns' => $this->activeColumns,
            'row_data' => $row,
        ]);
    }

    /**
     * Update an existing PendingProduct (edit mode)
     */
    protected function updateExistingPendingProduct(array $row): void
    {
        if (!$this->editingPendingProductId) {
            return;
        }

        $product = PendingProduct::find($this->editingPendingProductId);
        if (!$product) {
            return;
        }

        $priceData = $this->extractPriceData($row);

        $product->update([
            'sku' => strtoupper(trim($row['sku'] ?? $product->sku)),
            'name' => trim($row['name'] ?? '') ?: $product->name,
            'product_type_id' => !empty($row['product_type_id']) ? (int) $row['product_type_id'] : $product->product_type_id,
            'supplier_id' => !empty($row['supplier_id']) ? (int) $row['supplier_id'] : $product->supplier_id,
            'manufacturer_id' => !empty($row['manufacturer_id']) ? (int) $row['manufacturer_id'] : $product->manufacturer_id,
            'importer_id' => !empty($row['importer_id']) ? (int) $row['importer_id'] : $product->importer_id,
            'supplier_code' => trim($row['supplier_code'] ?? '') ?: $product->supplier_code,
            'ean' => trim($row['ean'] ?? '') ?: $product->ean,
            'cn_code' => trim($row['cn_code'] ?? '') ?: $product->cn_code,
            'material' => trim($row['material'] ?? '') ?: $product->material,
            'defect_symbol' => trim($row['defect_symbol'] ?? '') ?: $product->defect_symbol,
            'application' => trim($row['application'] ?? '') ?: $product->application,
            'price_data' => !empty($priceData) ? $priceData : $product->price_data,
        ]);

        $this->applySwitchesToProduct($product);
        $product->save();

        Log::info('ImportModalColumnModeTrait: updated existing PendingProduct', [
            'pending_product_id' => $product->id,
            'sku' => $product->sku,
        ]);
    }

    /**
     * Extract price data from row's price_group_* columns
     *
     * Converts entered values to net/gross based on priceDisplayMode.
     * Uses 23% VAT as default (TODO: use product-specific VAT rate).
     *
     * @return array{groups: array<int, array{net: float, gross: float}>}
     */
    protected function extractPriceData(array $row): array
    {
        $priceData = ['groups' => []];
        $vatRate = 1.23; // Default 23% VAT

        foreach ($row as $key => $value) {
            if (!str_starts_with($key, 'price_group_')) {
                continue;
            }
            if ($value === '' || $value === null) {
                continue;
            }

            $groupId = (int) str_replace('price_group_', '', $key);
            $numericValue = (float) str_replace(',', '.', $value);

            if ($this->priceDisplayMode === 'net') {
                $netPrice = $numericValue;
                $grossPrice = round($numericValue * $vatRate, 2);
            } else {
                $grossPrice = $numericValue;
                $netPrice = round($numericValue / $vatRate, 2);
            }

            $priceData['groups'][$groupId] = [
                'net' => $netPrice,
                'gross' => $grossPrice,
            ];
        }

        return $priceData;
    }

    /**
     * Validate EAN-13 or EAN-8 check digit
     */
    protected function validateEan(string $ean): bool
    {
        // Remove spaces
        $ean = preg_replace('/\s+/', '', $ean);

        // Must be numeric
        if (!ctype_digit($ean)) {
            return false;
        }

        $length = strlen($ean);

        // Support EAN-8 and EAN-13
        if ($length !== 8 && $length !== 13) {
            return false;
        }

        // Calculate check digit
        $digits = str_split($ean);
        $checkDigit = (int) array_pop($digits);

        $sum = 0;
        foreach ($digits as $index => $digit) {
            $digit = (int) $digit;

            if ($length === 13) {
                // EAN-13: odd positions x1, even positions x3
                $sum += ($index % 2 === 0) ? $digit : $digit * 3;
            } else {
                // EAN-8: odd positions x3, even positions x1
                $sum += ($index % 2 === 0) ? $digit * 3 : $digit;
            }
        }

        $calculatedCheck = (10 - ($sum % 10)) % 10;

        return $calculatedCheck === $checkDigit;
    }

    /**
     * Reset all column mode state
     */
    protected function resetColumnState(): void
    {
        $this->activeColumns = [];
        $this->rows = [$this->createEmptyRow()];
        $this->productTypes = [];
        $this->suppliers = [];
        $this->manufacturers = [];
        $this->importers = [];
    }

    /**
     * Get active column definitions for blade rendering
     *
     * Returns FIXED_COLUMNS + activeColumns with full metadata.
     *
     * @return array<string, array{label: string, type: string, fixed: bool}>
     */
    public function getActiveColumnDefinitions(): array
    {
        $definitions = [];

        // Fixed columns first
        $definitions['sku'] = [
            'label' => 'SKU',
            'type' => 'input',
            'fixed' => true,
        ];

        $definitions['name'] = [
            'label' => 'Nazwa',
            'type' => 'input',
            'fixed' => true,
        ];

        // Active optional columns
        $available = $this->getAvailableColumns();
        foreach ($this->activeColumns as $key) {
            if (isset($available[$key])) {
                $definitions[$key] = array_merge(
                    $available[$key],
                    ['fixed' => false]
                );
            }
        }

        return $definitions;
    }

    /**
     * Get dropdown options for a specific column
     *
     * @return array<array{id: int, name: string}>
     */
    public function getDropdownOptionsForColumn(string $columnKey): array
    {
        return match ($columnKey) {
            'product_type_id' => $this->productTypes,
            'supplier_id' => $this->suppliers,
            'manufacturer_id' => $this->manufacturers,
            'importer_id' => $this->importers,
            default => [],
        };
    }

    /**
     * Get ordered column keys (fixed first, then active)
     *
     * @return array<string>
     */
    protected function getColumnOrder(): array
    {
        return array_merge(self::FIXED_COLUMNS, $this->activeColumns);
    }

    /**
     * Create an empty row with all current column keys
     *
     * @return array<string, string>
     */
    protected function createEmptyRow(): array
    {
        $row = [];

        foreach (self::FIXED_COLUMNS as $col) {
            $row[$col] = '';
        }

        foreach ($this->activeColumns as $col) {
            $row[$col] = '';
        }

        return $row;
    }
}
