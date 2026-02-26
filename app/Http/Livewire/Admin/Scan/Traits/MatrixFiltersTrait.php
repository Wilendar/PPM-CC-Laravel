<?php

namespace App\Http\Livewire\Admin\Scan\Traits;

use App\Models\Product;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

/**
 * MatrixFiltersTrait
 *
 * Zarzadza filtrami panelu macierzy Cross-Source.
 * Udostepnia filtry: wyszukiwanie, status, brand, widok grupowany, paginacja.
 *
 * @package App\Http\Livewire\Admin\Scan\Traits
 */
trait MatrixFiltersTrait
{
    /** Fraza wyszukiwania (SKU/nazwa) */
    #[Url]
    public string $search = '';

    /**
     * Filtr statusu komorki macierzy.
     * Mozliwe wartosci: all | missing | linked | conflict | brand_not_allowed | pending_sync
     */
    #[Url]
    public string $statusFilter = 'all';

    /** ID producenta (manufacturer_id) - null = brak filtru */
    #[Url]
    public ?int $brandFilter = null;

    /** Widok grupowany po producencie */
    #[Url]
    public bool $groupedView = false;

    /** Pole sortowania: sku | name | manufacturer */
    #[Url]
    public string $sortField = 'sku';

    /** Kierunek sortowania: asc | desc */
    #[Url]
    public string $sortDirection = 'asc';

    /** Widoczne zrodla (klucze sourceColumns) - pusta tablica = wszystkie widoczne */
    public array $visibleSources = [];

    /** Liczba produktow na strone (bazowy rozmiar strony dla infinite scroll) */
    public int $perPage = 50;

    /** Liczba dodatkowych zaladowanych produktow (mnoznik dla loadMore) */
    public int $loadedCount = 0;

    /** Czy sa kolejne produkty do zaladowania */
    public bool $hasMoreProducts = true;

    /**
     * Reaguje na zmiane pola search - resetuje infinite scroll.
     *
     * @return void
     */
    public function updatedSearch(): void
    {
        $this->loadedCount      = 0;
        $this->hasMoreProducts  = true;
    }

    /**
     * Reaguje na zmiane statusFilter - resetuje infinite scroll.
     *
     * @return void
     */
    public function updatedStatusFilter(): void
    {
        $this->loadedCount      = 0;
        $this->hasMoreProducts  = true;
    }

    /**
     * Reaguje na zmiane brandFilter - resetuje infinite scroll.
     *
     * @return void
     */
    public function updatedBrandFilter(): void
    {
        $this->loadedCount      = 0;
        $this->hasMoreProducts  = true;
    }

    /**
     * Przelacza widok grupowany (po producencie).
     *
     * @return void
     */
    public function toggleGroupedView(): void
    {
        $this->groupedView      = !$this->groupedView;
        $this->loadedCount      = 0;
        $this->hasMoreProducts  = true;
    }

    /**
     * Resetuje wszystkie filtry do wartosci domyslnych.
     *
     * @return void
     */
    public function resetFilters(): void
    {
        $this->search           = '';
        $this->statusFilter     = 'all';
        $this->brandFilter      = null;
        $this->groupedView      = false;
        $this->visibleSources   = [];
        $this->sortField        = 'sku';
        $this->sortDirection    = 'asc';
        $this->perPage          = 50;
        $this->loadedCount      = 0;
        $this->hasMoreProducts  = true;
    }

    /**
     * Przelacza widocznosc zrodla (kolumny) w macierzy.
     * Gdy visibleSources jest pusty = wszystkie widoczne.
     * Klikniecie checkboxa "odznacza" to zrodlo (chowa kolumne).
     *
     * @param string $sourceKey Klucz zrodla (np. 'prestashop_1')
     * @return void
     */
    public function toggleSourceVisibility(string $sourceKey): void
    {
        // Jesli pusta tablica (= wszystkie widoczne), wypelnij WSZYSTKIMI kluczami
        // a nastepnie usun klikniety (uzytkownik chce go ukryc)
        if (empty($this->visibleSources)) {
            $allKeys = array_map(fn (array $col) => $col['key'], $this->sourceColumns);
            $this->visibleSources = array_values(array_diff($allKeys, [$sourceKey]));
            return;
        }

        if (in_array($sourceKey, $this->visibleSources)) {
            $this->visibleSources = array_values(
                array_diff($this->visibleSources, [$sourceKey])
            );
        } else {
            $this->visibleSources[] = $sourceKey;
        }

        // Jesli po toggle wszystkie zrodla sa widoczne - zresetuj do pustej tablicy
        $allKeys = array_map(fn (array $col) => $col['key'], $this->sourceColumns);
        if (count($this->visibleSources) >= count($allKeys)) {
            $this->visibleSources = [];
        }
    }

    /**
     * Sortuje macierz po podanym polu. Jesli to samo pole - odwraca kierunek.
     *
     * @param  string $field Pole: sku | name | manufacturer
     * @return void
     */
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField     = $field;
            $this->sortDirection = 'asc';
        }

        $this->loadedCount     = 0;
        $this->hasMoreProducts = true;
    }

    /**
     * Ustawia wszystkie zrodla jako widoczne (reset filtra kolumn).
     *
     * @return void
     */
    public function showAllSources(): void
    {
        $this->visibleSources = [];
    }

    /**
     * Sprawdza czy dane zrodlo jest widoczne.
     * Pusta tablica visibleSources = wszystkie widoczne.
     *
     * @param string $sourceKey
     * @return bool
     */
    public function isSourceVisible(string $sourceKey): bool
    {
        if (empty($this->visibleSources)) {
            return true;
        }
        return in_array($sourceKey, $this->visibleSources);
    }

    /**
     * Zwraca liste dostepnych producentow (z SKU).
     * Uzywane do wyboru filtru brand.
     *
     * @return Collection<int, array{id: int, name: string}>
     */
    public function getAvailableBrands(): Collection
    {
        return Product::whereNotNull('sku')
            ->where('sku', '!=', '')
            ->whereNotNull('manufacturer_id')
            ->with('manufacturerRelation:id,name')
            ->select('manufacturer_id')
            ->distinct()
            ->get()
            ->map(function (Product $product): array {
                return [
                    'id'   => $product->manufacturer_id,
                    'name' => $product->manufacturerRelation?->name ?? 'ID: ' . $product->manufacturer_id,
                ];
            })
            ->sortBy('name')
            ->values();
    }
}
