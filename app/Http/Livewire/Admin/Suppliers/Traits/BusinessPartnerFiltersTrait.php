<?php

namespace App\Http\Livewire\Admin\Suppliers\Traits;

/**
 * BusinessPartnerFiltersTrait - Filtry i wyszukiwanie w panelu BusinessPartner
 *
 * Obsluguje:
 * - Wyszukiwanie partnerow (entitySearch)
 * - Wyszukiwanie produktow (productSearch)
 * - Filtr statusu (active/inactive/all)
 * - Resetowanie filtrow przy zmianie zakladki
 *
 * Wymaga: WithPagination z BusinessPartnerPanel
 */
trait BusinessPartnerFiltersTrait
{
    // Wyszukiwanie partnerow (lewa kolumna)
    public string $entitySearch = '';

    // Filtr statusu partnera
    public string $statusFilter = 'all'; // 'all', 'active', 'inactive'

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE HOOKS (Livewire updated* hooks)
    |--------------------------------------------------------------------------
    */

    /**
     * Przy zmianie wyszukiwania partnerow - reset paginacji
     */
    public function updatedEntitySearch(): void
    {
        $this->resetPage();
    }

    /**
     * Przy zmianie wyszukiwania produktow - reset paginacji produktow
     */
    public function updatedProductSearch(): void
    {
        $this->resetPage('products');
    }

    /**
     * Przy zmianie filtra statusu - reset paginacji
     */
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Przy zmianie filtra BRAK - reset paginacji produktow
     */
    public function updatedBrakFilter(): void
    {
        $this->resetPage('products');
    }

    /**
     * Przy zmianie zakladki (z URL) - reset stanu
     */
    public function updatedActiveTab(): void
    {
        $this->selectedEntityId = null;
        $this->resetFilters();
        $this->resetPage();
        $this->resetPage('products');
    }

    /*
    |--------------------------------------------------------------------------
    | FILTER METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Resetuj wszystkie filtry
     */
    public function resetFilters(): void
    {
        $this->entitySearch = '';
        $this->productSearch = '';
        $this->statusFilter = 'all';
        $this->brakFilter = 'any';
    }
}
