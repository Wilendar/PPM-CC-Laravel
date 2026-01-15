<?php

namespace App\Http\Livewire\Admin\Import\Traits;

use App\Models\PendingProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * ImportPanelFilters Trait
 *
 * ETAP_06 FAZA 2: Query building i filtering logic dla ProductImportPanel
 *
 * Responsibilities:
 * - Build optimized query z filterami
 * - Apply search, status, type, session, completion filters
 * - Sorting logic
 * - Performance optimization (eager loading)
 *
 * @package App\Http\Livewire\Admin\Import\Traits
 */
trait ImportPanelFilters
{
    /**
     * Build base query with all filters applied
     */
    protected function buildQuery(): Builder
    {
        $query = PendingProduct::query()
            ->with(['productType', 'importSession', 'importer'])
            ->byUser(Auth::id());

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $searchTerm = '%' . $this->search . '%';
                $q->where('sku', 'like', $searchTerm)
                    ->orWhere('name', 'like', $searchTerm)
                    ->orWhere('manufacturer', 'like', $searchTerm);
            });
        }

        // Apply status filter
        if ($this->statusFilter !== 'all') {
            match ($this->statusFilter) {
                'incomplete' => $query->incomplete(),
                'ready' => $query->readyForPublish(),
                'published' => $query->published(),
                'unpublished' => $query->unpublished(),
                default => null,
            };
        }

        // Apply product type filter
        if ($this->productTypeFilter !== 'all') {
            $query->byProductType((int) $this->productTypeFilter);
        }

        // Apply import session filter
        if ($this->sessionFilter !== 'all') {
            $query->bySession((int) $this->sessionFilter);
        }

        // Apply completion range filter
        if ($this->completionMin > 0 || $this->completionMax < 100) {
            $query->byCompletion($this->completionMin, $this->completionMax);
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        return $query;
    }

    /**
     * Apply sorting
     */
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            // Toggle direction if same field
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            // New field - default to ascending
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Get sort icon for column header
     */
    public function getSortIcon(string $field): string
    {
        if ($this->sortField !== $field) {
            return 'heroicon-o-arrows-up-down';
        }

        return $this->sortDirection === 'asc'
            ? 'heroicon-o-arrow-up'
            : 'heroicon-o-arrow-down';
    }
}
