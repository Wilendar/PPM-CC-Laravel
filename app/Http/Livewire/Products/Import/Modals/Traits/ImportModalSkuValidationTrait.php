<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\Import\Modals\Traits;

use App\Models\PendingProduct;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

/**
 * ImportModalSkuValidationTrait - SKU duplicate validation for ProductImportModal
 *
 * Provides real-time SKU duplicate detection across:
 * - Published products (Product model)
 * - Pending products (PendingProduct model, unpublished)
 * - Internal batch duplicates (same SKU in multiple rows)
 *
 * Used by both Column Mode and CSV Mode.
 *
 * @package App\Http\Livewire\Products\Import\Modals\Traits
 */
trait ImportModalSkuValidationTrait
{
    /**
     * Duplicate SKU validation results
     *
     * Structure: [SKU => ['source' => 'product'|'pending'|'batch', 'id' => ?int, 'name' => ?string, 'url' => ?string, 'label' => string]]
     */
    public array $duplicateSkuResults = [];

    /**
     * Indices of rows selected for bulk duplicate actions (string values from wire:model.live)
     */
    public array $selectedDuplicateRows = [];

    /**
     * Select-all checkbox flag (wire:model.live binding)
     */
    public bool $selectAllDuplicatesFlag = false;

    /*
    |--------------------------------------------------------------------------
    | LIVEWIRE HOOKS - Checkbox sync (Wzorzec Select All A)
    |--------------------------------------------------------------------------
    */

    /**
     * Hook: when select-all checkbox changes
     */
    public function updatedSelectAllDuplicatesFlag(): void
    {
        if ($this->selectAllDuplicatesFlag) {
            $this->selectAllDuplicates();
        } else {
            $this->selectedDuplicateRows = [];
        }
    }

    /**
     * Hook: when per-row checkboxes change - sync selectAll flag
     */
    public function updatedSelectedDuplicateRows(): void
    {
        $duplicateSkus = array_keys($this->duplicateSkuResults);
        $totalDuplicateRows = 0;
        foreach ($this->rows as $row) {
            $sku = strtoupper(trim($row['sku'] ?? ''));
            if (in_array($sku, $duplicateSkus, true)) {
                $totalDuplicateRows++;
            }
        }
        $this->selectAllDuplicatesFlag = count($this->selectedDuplicateRows) >= $totalDuplicateRows && $totalDuplicateRows > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLIC METHODS - Batch validation
    |--------------------------------------------------------------------------
    */

    /**
     * Validate all SKUs from current rows (column mode)
     *
     * Collects SKUs from $this->rows, runs batch DB check + internal duplicate detection.
     * Called after paste, row removal, or any bulk operation that changes SKUs.
     */
    public function validateSkus(): void
    {
        $allSkus = [];

        foreach ($this->rows as $row) {
            $sku = strtoupper(trim($row['sku'] ?? ''));
            if (!empty($sku)) {
                $allSkus[] = $sku;
            }
        }

        Log::debug('ImportModalSkuValidationTrait: validateSkus CALLED', [
            'total_rows' => count($this->rows),
            'non_empty_skus' => count($allSkus),
        ]);

        if (empty($allSkus)) {
            $this->duplicateSkuResults = [];
            $this->selectedDuplicateRows = [];
            return;
        }

        $dbDuplicates = $this->batchCheckSkus($allSkus);
        $internalDuplicates = $this->detectInternalDuplicates($allSkus);

        // Merge: DB duplicates take priority over internal batch duplicates
        $this->duplicateSkuResults = array_merge($internalDuplicates, $dbDuplicates);
        $this->selectedDuplicateRows = [];

        Log::debug('ImportModalSkuValidationTrait: validateSkus COMPLETED', [
            'db_duplicates' => count($dbDuplicates),
            'internal_duplicates' => count($internalDuplicates),
            'total_duplicates' => count($this->duplicateSkuResults),
        ]);
    }

    /**
     * Validate a single SKU for a specific row (column mode, on blur)
     *
     * Efficiently checks one SKU against DB and other rows in the batch.
     * Updates only the relevant entry in $duplicateSkuResults.
     */
    public function validateSingleSku(int $rowIndex): void
    {
        if (!isset($this->rows[$rowIndex])) {
            return;
        }

        $sku = strtoupper(trim($this->rows[$rowIndex]['sku'] ?? ''));

        // If empty, remove any existing result for SKUs that were in this row before
        if (empty($sku)) {
            // Re-validate all to clean up stale entries
            $this->validateSkus();
            return;
        }

        Log::debug('ImportModalSkuValidationTrait: validateSingleSku CALLED', [
            'rowIndex' => $rowIndex,
            'sku' => $sku,
        ]);

        // Check DB for this single SKU
        $dbResult = $this->batchCheckSkus([$sku]);

        // Check internal: does the same SKU exist in other rows?
        $internalCount = 0;
        foreach ($this->rows as $idx => $row) {
            if ($idx === $rowIndex) {
                continue;
            }
            $otherSku = strtoupper(trim($row['sku'] ?? ''));
            if ($otherSku === $sku) {
                $internalCount++;
            }
        }

        // Remove old entry for this SKU (if any)
        // First, find what SKU was previously at this index and clean it
        $this->cleanStaleSkuResults();

        // Set new result
        if (!empty($dbResult[$sku])) {
            $this->duplicateSkuResults[$sku] = $dbResult[$sku];
        } elseif ($internalCount > 0) {
            $this->duplicateSkuResults[$sku] = [
                'source' => 'batch',
                'id' => null,
                'name' => null,
                'url' => null,
                'label' => 'Duplikat wewnetrzny w paczce',
            ];
        } else {
            // No duplicate - remove from results if present
            unset($this->duplicateSkuResults[$sku]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLIC METHODS - Row management
    |--------------------------------------------------------------------------
    */

    /**
     * Remove specific rows by their indices
     *
     * After removal, re-indexes $this->rows and re-validates SKUs.
     */
    public function removeDuplicateRows(array $indices): void
    {
        if (empty($indices)) {
            return;
        }

        Log::debug('ImportModalSkuValidationTrait: removeDuplicateRows CALLED', [
            'indices' => $indices,
            'rows_before' => count($this->rows),
        ]);

        // Sort descending to avoid index shift issues
        $indices = array_unique(array_map('intval', $indices));
        rsort($indices);

        foreach ($indices as $index) {
            if (isset($this->rows[$index])) {
                unset($this->rows[$index]);
            }
        }

        // Re-index
        $this->rows = array_values($this->rows);

        // Ensure at least one row remains
        if (empty($this->rows)) {
            $this->rows = [$this->createEmptyRow()];
        }

        // Re-validate after removal
        $this->validateSkus();

        Log::debug('ImportModalSkuValidationTrait: removeDuplicateRows COMPLETED', [
            'rows_after' => count($this->rows),
        ]);
    }

    /**
     * Remove ALL rows that have a duplicate SKU
     */
    public function removeAllDuplicateRows(): void
    {
        if (empty($this->duplicateSkuResults)) {
            return;
        }

        $duplicateSkus = array_keys($this->duplicateSkuResults);

        $indicesToRemove = [];
        foreach ($this->rows as $index => $row) {
            $sku = strtoupper(trim($row['sku'] ?? ''));
            if (in_array($sku, $duplicateSkus, true)) {
                $indicesToRemove[] = $index;
            }
        }

        Log::debug('ImportModalSkuValidationTrait: removeAllDuplicateRows', [
            'duplicate_skus' => $duplicateSkus,
            'indices_to_remove' => $indicesToRemove,
        ]);

        $this->removeDuplicateRows($indicesToRemove);
    }

    /**
     * Select all rows that have duplicate SKUs (for bulk actions)
     */
    public function selectAllDuplicates(): void
    {
        $duplicateSkus = array_keys($this->duplicateSkuResults);
        $this->selectedDuplicateRows = [];

        foreach ($this->rows as $index => $row) {
            $sku = strtoupper(trim($row['sku'] ?? ''));
            if (in_array($sku, $duplicateSkus, true)) {
                // String type to match wire:model.live checkbox value attribute
                $this->selectedDuplicateRows[] = (string) $index;
            }
        }
    }

    /**
     * Toggle all duplicate selection (wire:click pattern)
     *
     * If any selected -> CLEAR all. If none selected -> SELECT all duplicates.
     */
    /*
    |--------------------------------------------------------------------------
    | PUBLIC METHODS - State queries
    |--------------------------------------------------------------------------
    */

    /**
     * Check if any duplicates exist
     */
    public function hasDuplicates(): bool
    {
        return count($this->duplicateSkuResults) > 0;
    }

    /**
     * Get duplicate info for a specific row index
     *
     * Returns null if the row's SKU is not a duplicate.
     */
    public function getDuplicateInfoForRow(int $rowIndex): ?array
    {
        if (!isset($this->rows[$rowIndex])) {
            return null;
        }

        $sku = strtoupper(trim($this->rows[$rowIndex]['sku'] ?? ''));
        if (empty($sku)) {
            return null;
        }

        return $this->duplicateSkuResults[$sku] ?? null;
    }

    /**
     * Reset all SKU validation state
     *
     * Called on modal close, mode switch, or state reset.
     */
    public function resetSkuValidationState(): void
    {
        $this->duplicateSkuResults = [];
        $this->selectedDuplicateRows = [];
        $this->selectAllDuplicatesFlag = false;
    }

    /*
    |--------------------------------------------------------------------------
    | PROTECTED METHODS - DB queries
    |--------------------------------------------------------------------------
    */

    /**
     * Batch check SKUs against Product and PendingProduct tables
     *
     * Runs exactly 2 queries regardless of SKU count.
     * In edit mode, excludes the currently edited PendingProduct.
     *
     * @param array $skus Array of SKU strings (should be uppercase+trimmed)
     * @return array [SKU => ['source' => ..., 'id' => ..., 'name' => ..., 'url' => ..., 'label' => ...]]
     */
    protected function batchCheckSkus(array $skus): array
    {
        $upperSkus = array_map(fn($s) => strtoupper(trim($s)), $skus);
        $uniqueSkus = array_values(array_unique(array_filter($upperSkus)));

        if (empty($uniqueSkus)) {
            return [];
        }

        // Query 1: Published products
        $existingProducts = Product::whereIn('sku', $uniqueSkus)
            ->select('id', 'sku', 'slug')
            ->get()
            ->keyBy(fn($p) => strtoupper($p->sku));

        // Query 2: Pending products (unpublished only, exclude self in edit mode)
        $pendingQuery = PendingProduct::whereIn('sku', $uniqueSkus)
            ->whereNull('published_at');

        if (property_exists($this, 'editingPendingProductId') && $this->editingPendingProductId) {
            $pendingQuery->where('id', '!=', $this->editingPendingProductId);
        }

        $existingPending = $pendingQuery
            ->select('id', 'sku')
            ->get()
            ->keyBy(fn($p) => strtoupper($p->sku));

        $results = [];

        foreach ($uniqueSkus as $sku) {
            if ($existingProducts->has($sku)) {
                $product = $existingProducts->get($sku);
                $results[$sku] = [
                    'source' => 'product',
                    'id' => $product->id,
                    'name' => null,
                    'url' => '/admin/products/' . $product->id . '/edit',
                    'label' => 'Istnieje w produktach',
                ];
            } elseif ($existingPending->has($sku)) {
                $pending = $existingPending->get($sku);
                $results[$sku] = [
                    'source' => 'pending',
                    'id' => $pending->id,
                    'name' => null,
                    'url' => null,
                    'label' => 'Istnieje w oczekujacych',
                ];
            }
        }

        return $results;
    }

    /**
     * Detect internal duplicates within the current batch
     *
     * Finds SKUs that appear more than once in the provided array.
     *
     * @param array $skus Array of all SKU strings (already uppercase+trimmed)
     * @return array [SKU => ['source' => 'batch', ...]] for duplicate SKUs
     */
    protected function detectInternalDuplicates(array $skus): array
    {
        $counts = array_count_values($skus);
        $results = [];

        foreach ($counts as $sku => $count) {
            if ($count > 1) {
                $results[$sku] = [
                    'source' => 'batch',
                    'id' => null,
                    'name' => null,
                    'url' => null,
                    'label' => 'Duplikat wewnetrzny w paczce',
                ];
            }
        }

        return $results;
    }

    /**
     * Clean stale SKU results that no longer exist in current rows
     *
     * Removes entries from duplicateSkuResults for SKUs that are
     * no longer present in $this->rows.
     */
    protected function cleanStaleSkuResults(): void
    {
        $currentSkus = [];
        foreach ($this->rows as $row) {
            $sku = strtoupper(trim($row['sku'] ?? ''));
            if (!empty($sku)) {
                $currentSkus[] = $sku;
            }
        }

        foreach (array_keys($this->duplicateSkuResults) as $sku) {
            if (!in_array($sku, $currentSkus, true)) {
                unset($this->duplicateSkuResults[$sku]);
            }
        }
    }
}
