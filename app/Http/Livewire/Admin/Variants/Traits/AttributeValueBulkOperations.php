<?php

namespace App\Http\Livewire\Admin\Variants\Traits;

use App\Models\AttributeValue;

/**
 * Trait AttributeValueBulkOperations
 *
 * Bulk operations for AttributeValueManager
 * Extracted to comply with 300-line file limit
 *
 * FEATURES:
 * - Bulk selection (select all, toggle)
 * - Delete unused values
 * - Delete selected values (only unused)
 * - Search/Filter/Sort with URL persistence
 *
 * @package App\Http\Livewire\Admin\Variants\Traits
 * @since ETAP_05b FAZA 5 (2025-12-11)
 */
trait AttributeValueBulkOperations
{
    /*
    |--------------------------------------------------------------------------
    | BULK SELECTION
    |--------------------------------------------------------------------------
    */

    /**
     * Toggle select all values
     */
    public function toggleSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedValues = $this->getValues()->pluck('id')->toArray();
        } else {
            $this->selectedValues = [];
        }
    }

    /**
     * Toggle single value selection
     */
    public function toggleValueSelection(int $valueId): void
    {
        if (in_array($valueId, $this->selectedValues)) {
            $this->selectedValues = array_diff($this->selectedValues, [$valueId]);
        } else {
            $this->selectedValues[] = $valueId;
        }

        // Update selectAll state
        $this->selectAll = count($this->selectedValues) === $this->getValues()->count();
    }

    /*
    |--------------------------------------------------------------------------
    | BULK DELETE OPERATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Delete all unused values for current type
     */
    public function deleteUnusedValues(): void
    {
        try {
            $unusedValues = $this->getUsageService()->getUnusedValuesForType($this->attributeTypeId);
            $count = $unusedValues->count();

            if ($count === 0) {
                session()->flash('info', 'Brak nieuzywanych wartosci do usuniecia');
                return;
            }

            foreach ($unusedValues as $value) {
                $this->getAttributeManager()->deleteAttributeValue($value);
            }

            session()->flash('message', "Usunieto {$count} nieuzywanych wartosci");
            $this->selectedValues = [];
            $this->loadData(); // PERFORMANCE: Refresh cache after bulk mutation

        } catch (\Exception $e) {
            $this->addError('bulk', 'Blad: ' . $e->getMessage());
        }
    }

    /**
     * Delete selected values (only unused ones)
     */
    public function deleteSelectedValues(): void
    {
        if (empty($this->selectedValues)) {
            session()->flash('info', 'Nie zaznaczono zadnych wartosci');
            return;
        }

        try {
            $stats = $this->getUsageStats();
            $deleted = 0;
            $skipped = 0;

            foreach ($this->selectedValues as $valueId) {
                $usage = $stats->get($valueId);

                // Skip if value is in use
                if ($usage && $usage['products_count'] > 0) {
                    $skipped++;
                    continue;
                }

                $value = AttributeValue::find($valueId);
                if ($value) {
                    $this->getAttributeManager()->deleteAttributeValue($value);
                    $deleted++;
                }
            }

            $message = "Usunieto {$deleted} wartosci";
            if ($skipped > 0) {
                $message .= ", pominieto {$skipped} uzywanych";
            }

            session()->flash('message', $message);
            $this->selectedValues = [];
            $this->selectAll = false;
            $this->loadData(); // PERFORMANCE: Refresh cache after bulk mutation

        } catch (\Exception $e) {
            $this->addError('bulk', 'Blad: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SEARCH/FILTER/SORT
    |--------------------------------------------------------------------------
    */

    /**
     * Update search term (debounced from wire:model.live.debounce.300ms)
     */
    public function updatedSearch(): void
    {
        $this->selectedValues = [];
        $this->selectAll = false;
        $this->refreshValues(); // PERFORMANCE: Only refresh filtered values, not all data
    }

    /**
     * Update filter status
     */
    public function updatedFilterStatus(): void
    {
        $this->selectedValues = [];
        $this->selectAll = false;
        $this->refreshValues(); // PERFORMANCE: Only refresh filtered values
    }

    /**
     * Update sort field
     */
    public function updatedSortField(): void
    {
        $this->refreshValues(); // PERFORMANCE: Only refresh filtered values
    }

    /**
     * Sort by field
     */
    public function sortBy(string $field): void
    {
        $allowedFields = ['position', 'label', 'code', 'is_active'];

        if (!in_array($field, $allowedFields)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->refreshValues(); // PERFORMANCE: Refresh after sort change
    }

    /**
     * Reset all filters
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterStatus = 'all';
        $this->sortField = 'position';
        $this->sortDirection = 'asc';
        $this->selectedValues = [];
        $this->selectAll = false;
    }

    /**
     * Get filter status options
     */
    public function getFilterOptions(): array
    {
        return [
            'all' => 'Wszystkie',
            'used' => 'Uzywane',
            'unused' => 'Nieuzywane',
        ];
    }

    /**
     * Get sort options
     */
    public function getSortOptions(): array
    {
        return [
            'position' => 'Pozycja',
            'label' => 'Nazwa',
            'code' => 'Kod',
        ];
    }
}
