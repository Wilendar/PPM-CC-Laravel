<?php

namespace App\Http\Livewire\Products\Import\Traits;

use Livewire\Attributes\Url;
use Illuminate\Database\Eloquent\Builder;

/**
 * ImportPanelFilters - Trait dla filtrowania pending products
 *
 * ETAP_06: Filter logic dla ProductImportPanel
 */
trait ImportPanelFilters
{
    /**
     * Filter: Status (incomplete, ready, published)
     */
    #[Url]
    public ?string $filterStatus = null;

    /**
     * Filter: Product type ID
     */
    #[Url]
    public ?int $filterProductType = null;

    /**
     * Filter: Import session ID
     */
    #[Url]
    public ?int $filterSessionId = null;

    /**
     * Filter: Search query (SKU, name)
     */
    #[Url]
    public string $filterSearch = '';

    /**
     * Filter: Completion percentage range
     */
    public ?int $filterCompletionMin = null;
    public ?int $filterCompletionMax = null;

    /**
     * Reset all filters to default
     */
    public function resetFilters(): void
    {
        $this->filterStatus = null;
        $this->filterProductType = null;
        $this->filterSessionId = null;
        $this->filterSearch = '';
        $this->filterCompletionMin = null;
        $this->filterCompletionMax = null;
    }

    /**
     * Apply status filter to query
     *
     * Logika oparta na completion_percentage:
     * - incomplete = completion_percentage < 100 (partiallyComplete)
     * - ready = completion_percentage == 100 (fullyComplete)
     * - published = published_at IS NOT NULL
     */
    protected function applyStatusFilter(Builder $query): Builder
    {
        return match ($this->filterStatus) {
            'incomplete' => $query->partiallyComplete(),
            'ready' => $query->fullyComplete(),
            'published' => $query->published(),
            default => $query,
        };
    }

    /**
     * Apply search filter (SKU + name)
     */
    protected function applySearchFilter(Builder $query): Builder
    {
        $search = trim($this->filterSearch);
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('sku', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%")
              ->orWhere('manufacturer', 'like', "%{$search}%");
        });
    }

    /**
     * Apply completion range filter
     */
    protected function applyCompletionFilter(Builder $query): Builder
    {
        if ($this->filterCompletionMin !== null) {
            $query->where('completion_percentage', '>=', $this->filterCompletionMin);
        }
        if ($this->filterCompletionMax !== null) {
            $query->where('completion_percentage', '<=', $this->filterCompletionMax);
        }
        return $query;
    }

    /**
     * Set filter for quick filtering buttons
     */
    public function setQuickFilter(string $type): void
    {
        $this->resetFilters();

        match ($type) {
            'all' => null,
            'incomplete' => $this->filterStatus = 'incomplete',
            'ready' => $this->filterStatus = 'ready',
            'today' => $this->setTodayFilter(),
            default => null,
        };

        $this->resetPage();
    }

    /**
     * Set filter for today's imports
     */
    protected function setTodayFilter(): void
    {
        // This would need additional scope in model
        // For now, we handle via created_at
    }

    /**
     * Get available status options
     */
    public function getStatusOptions(): array
    {
        return [
            '' => 'Wszystkie statusy',
            'incomplete' => 'Niekompletne',
            'ready' => 'Gotowe do publikacji',
            'published' => 'Opublikowane',
        ];
    }
}
