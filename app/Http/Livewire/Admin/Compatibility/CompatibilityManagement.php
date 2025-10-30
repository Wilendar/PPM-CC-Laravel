<?php

namespace App\Http\Livewire\Admin\Compatibility;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * CompatibilityManagement Component - PLACEHOLDER VERSION
 *
 * ETAP_05d FAZA 1.2: Frontend UI tylko (Blade + CSS)
 * FAZA 2+: Backend implementation (models, migrations, relations)
 *
 * NOTE: Ten component używa MOCK DATA aby pokazać UI.
 * Prawdziwe dane będą ładowane gdy migrations/models zostaną wdrożone.
 *
 * @package App\Http\Livewire\Admin\Compatibility
 * @version 1.0-placeholder
 * @since 2025-10-24 ETAP_05d FAZA 1.2
 */
class CompatibilityManagement extends Component
{
    use WithPagination;

    public string $searchPart = '';
    public ?int $filterShopId = null;
    public string $filterBrand = '';
    public string $filterStatus = 'all';
    public string $sortField = 'sku';
    public string $sortDirection = 'asc';
    public array $expandedPartIds = [];
    public array $selectedPartIds = [];
    public bool $showBulkEditModal = false;

    protected $queryString = [
        'searchPart' => ['except' => ''],
        'filterShopId' => ['except' => null],
        'filterBrand' => ['except' => ''],
        'filterStatus' => ['except' => 'all'],
        'sortField' => ['except' => 'sku'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function mount(): void
    {
        $this->expandedPartIds = [];
        $this->selectedPartIds = [];
    }

    /**
     * Open bulk edit modal
     */
    public function openBulkEdit(): void
    {
        if (count($this->selectedPartIds) === 0) {
            $this->dispatch('notify', message: 'Zaznacz przynajmniej 1 część', type: 'warning');
            return;
        }

        $this->dispatch('open-bulk-modal', [
            'direction' => 'part_to_vehicle',
            'selectedIds' => $this->selectedPartIds
        ]);
    }

    /**
     * Toggle part selection (checkbox)
     */
    public function togglePartSelection(int $partId): void
    {
        if (in_array($partId, $this->selectedPartIds)) {
            $this->selectedPartIds = array_values(array_diff($this->selectedPartIds, [$partId]));
        } else {
            $this->selectedPartIds[] = $partId;
        }
    }

    public function render(): View
    {
        return view('livewire.admin.compatibility.compatibility-management');
    }

    /**
     * MOCK: Parts data for UI demonstration
     */
    public function getPartsProperty()
    {
        // PLACEHOLDER: Return mock data with pagination
        // Real implementation will query database when models/migrations deployed
        $items = collect([
            (object)[
                'id' => 1,
                'sku' => 'DEMO-001',
                'name' => 'Przykładowa Część 1 (MOCK DATA - Frontend UI Only)',
                'original_count' => 0,
                'replacement_count' => 0,
                'compatibilities' => collect([])
            ],
            (object)[
                'id' => 2,
                'sku' => 'DEMO-002',
                'name' => 'Przykładowa Część 2 (MOCK DATA - Backend w FAZA 2+)',
                'original_count' => 0,
                'replacement_count' => 0,
                'compatibilities' => collect([])
            ]
        ]);

        return new LengthAwarePaginator(
            $items,
            $items->count(),
            50,
            1,
            ['path' => request()->url()]
        );
    }

    /**
     * MOCK: Shops data for filter dropdown
     */
    public function getShopsProperty()
    {
        // PLACEHOLDER: Return empty collection
        // Real implementation will query PrestaShopShop table
        return collect([]);
    }

    /**
     * MOCK: Brands data for filter dropdown
     */
    public function getBrandsProperty()
    {
        // PLACEHOLDER: Return empty collection
        // Real implementation will query VehicleModel table
        return collect([]);
    }

    public function toggleExpand(int $partId): void
    {
        if (in_array($partId, $this->expandedPartIds)) {
            $this->expandedPartIds = array_diff($this->expandedPartIds, [$partId]);
        } else {
            $this->expandedPartIds[] = $partId;
        }
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function resetFilters(): void
    {
        $this->searchPart = '';
        $this->filterShopId = null;
        $this->filterBrand = '';
        $this->filterStatus = 'all';
        $this->sortField = 'sku';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    public function updatedSearchPart(): void
    {
        $this->resetPage();
    }

    public function updatedFilterShopId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterBrand(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function isExpanded(int $partId): bool
    {
        return in_array($partId, $this->expandedPartIds);
    }

    public function getStatusBadgeClass(int $originalCount, int $replacementCount): string
    {
        if ($originalCount > 0 && $replacementCount > 0) {
            return 'status-badge-full';
        } elseif ($originalCount > 0 || $replacementCount > 0) {
            return 'status-badge-partial';
        } else {
            return 'status-badge-none';
        }
    }

    public function getStatusBadgeLabel(int $originalCount, int $replacementCount): string
    {
        if ($originalCount > 0 && $replacementCount > 0) {
            return '✅ Pełny';
        } elseif ($originalCount > 0 || $replacementCount > 0) {
            return '🟡 Częściowy';
        } else {
            return '❌ Brak';
        }
    }
}
