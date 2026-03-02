<?php

namespace App\Http\Livewire\Admin\Scan\Traits;

use App\Models\DismissedBrandSuggestion;
use App\Services\Scan\CrossSourceMatrixService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * MatrixDataTrait
 *
 * Odpowiada za ladowanie danych macierzy Cross-Source.
 * Pobiera liste zrodel, dane macierzy oraz statystyki via CrossSourceMatrixService.
 *
 * @package App\Http\Livewire\Admin\Scan\Traits
 */
trait MatrixDataTrait
{
    /** @var array<int, array{type: string, id: int, name: string, icon: string, color: string, is_shop: bool}> */
    public array $sources = [];

    /** @var array<int, array{key: string, label: string, type: string, id: int}> Kolumny macierzy */
    public array $sourceColumns = [];

    /**
     * Laduje liste aktywnych zrodel (PrestaShop + ERP) i buduje sourceColumns.
     *
     * @return void
     */
    public function loadSources(): void
    {
        /** @var CrossSourceMatrixService $service */
        $service = app(CrossSourceMatrixService::class);

        $this->sources = $service->getAvailableSources();

        $this->sourceColumns = array_map(function (array $source): array {
            return [
                'key'   => $source['type'] . '_' . $source['id'],
                'label' => $source['name'],
                'type'  => $source['type'],
                'id'    => $source['id'],
                'icon'  => $source['icon'],
                'color' => $source['color'],
            ];
        }, $this->sources);
    }

    /**
     * Zwraca dane macierzy z zastosowanymi filtrami.
     * Uzywa infinite scroll - laduje perPage + loadedCount produktow naraz.
     *
     * @return LengthAwarePaginator
     */
    public function getMatrixData(): LengthAwarePaginator
    {
        /** @var CrossSourceMatrixService $service */
        $service = app(CrossSourceMatrixService::class);

        $totalToLoad = $this->perPage + $this->loadedCount;

        return $service->getQuickMatrixData(
            $this->getActiveFilters(),
            $totalToLoad
        );
    }

    /**
     * Laduje kolejna porcje produktow (infinite scroll).
     * W trybie selectAllMatching auto-zaznacza nowo zaladowane produkty.
     *
     * @return void
     */
    public function loadMore(): void
    {
        $this->loadedCount += $this->perPage;

        // W trybie selectAllMatching: auto-zaznacz nowo zaladowane produkty
        if ($this->selectAllMatching) {
            $this->syncSelectedWithMatching();
        }
    }

    /**
     * Zwraca calkowita liczbe produktow pasujacych do aktywnych filtrow (bez paginacji).
     */
    public function getTotalMatchingCount(): int
    {
        return app(CrossSourceMatrixService::class)
            ->getFilteredProductCount($this->getActiveFilters());
    }

    /**
     * Zwraca wszystkie ID produktow pasujacych do aktywnych filtrow (bez paginacji).
     *
     * @return int[]
     */
    public function getAllMatchingProductIds(): array
    {
        return app(CrossSourceMatrixService::class)
            ->getFilteredProductIds($this->getActiveFilters());
    }

    /**
     * Synchronizuje selectedProducts z wszystkimi widocznymi produktami (minus wykluczone).
     * Uzywane w trybie selectAllMatching po loadMore().
     */
    private function syncSelectedWithMatching(): void
    {
        $matrixData = $this->getMatrixData();
        $allVisibleIds = collect($matrixData->items())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $this->selectedProducts = array_values(
            array_diff($allVisibleIds, $this->excludedProducts)
        );
    }

    /**
     * Zwraca statystyki podsumowania dla widocznych danych macierzy.
     *
     * @return array<string, int>
     */
    public function getSummaryStatsData(): array
    {
        /** @var CrossSourceMatrixService $service */
        $service = app(CrossSourceMatrixService::class);

        $matrixData = $this->getMatrixData();

        return $service->getSummaryStats(
            collect($matrixData->items()),
            $this->sources
        );
    }

    /**
     * Laduje sugestie brandow dla sklepow PrestaShop.
     * Rozdziela sugestie na aktywne i odrzucone per uzytkownik.
     *
     * @return array{active: array<int, array{shop_id: int, shop_name: string, suggestions: Collection}>, dismissed: array<int, array{shop_id: int, shop_name: string, suggestions: Collection}>}
     */
    public function loadBrandSuggestions(): array
    {
        /** @var CrossSourceMatrixService $service */
        $service = app(CrossSourceMatrixService::class);

        $userId             = auth()->id() ?? 8;
        $activeSuggestions  = [];
        $dismissedList      = [];

        foreach ($this->sources as $source) {
            if ($source['is_shop'] && $source['type'] === 'prestashop') {
                $shopSuggestions = $service->getBrandSuggestions($source['id']);
                $dismissedBrands = DismissedBrandSuggestion::getDismissedBrands($userId, $source['id']);

                $active = $shopSuggestions->reject(
                    fn ($item) => in_array($item->manufacturerRelation?->name, $dismissedBrands, true)
                );

                $dismissed = $shopSuggestions->filter(
                    fn ($item) => in_array($item->manufacturerRelation?->name, $dismissedBrands, true)
                );

                if ($active->isNotEmpty()) {
                    $activeSuggestions[] = [
                        'shop_id'     => $source['id'],
                        'shop_name'   => $source['name'],
                        'suggestions' => $active,
                    ];
                }

                if ($dismissed->isNotEmpty()) {
                    $dismissedList[] = [
                        'shop_id'     => $source['id'],
                        'shop_name'   => $source['name'],
                        'suggestions' => $dismissed,
                    ];
                }
            }
        }

        return [
            'active'    => $activeSuggestions,
            'dismissed' => $dismissedList,
        ];
    }

    /**
     * Buduje tablice aktywnych filtrow z properties filtrow.
     *
     * @return array{search?: string, status?: string, manufacturer_id?: int, sort_field?: string, sort_direction?: string}
     */
    protected function getActiveFilters(): array
    {
        $filters = [];

        if (!empty($this->search)) {
            $filters['search'] = $this->search;
        }

        if (!empty($this->statusFilter) && $this->statusFilter !== 'all') {
            $filters['status'] = $this->statusFilter;
        }

        if ($this->brandFilter !== null) {
            $filters['manufacturer_id'] = $this->brandFilter;
        }

        if (!empty($this->visibleSources)) {
            $filters['visible_sources'] = $this->visibleSources;
        }

        if (!empty($this->sortField)) {
            $filters['sort_field']     = $this->sortField;
            $filters['sort_direction'] = $this->sortDirection ?? 'asc';
        }

        return $filters;
    }

    /**
     * Odswierza widok macierzy - resetuje infinite scroll i odswierza komponent.
     *
     * @return void
     */
    public function refreshMatrix(): void
    {
        $this->loadedCount      = 0;
        $this->hasMoreProducts  = true;
        $this->dispatch('matrix-refreshed');
    }
}
