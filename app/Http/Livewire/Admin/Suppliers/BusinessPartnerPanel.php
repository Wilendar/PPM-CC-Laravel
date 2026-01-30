<?php

namespace App\Http\Livewire\Admin\Suppliers;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

/**
 * BusinessPartnerPanel - Panel zarzadzania dostawcami/producentami/importerami
 *
 * 4 zakladki (DOSTAWCA/PRODUCENT/IMPORTER/BRAK) z 2-kolumnowym layoutem (25%/75%).
 * Lewa kolumna: lista partnerow z filtrowaniem
 * Prawa kolumna: szczegoly wybranego partnera + przypisane produkty
 *
 * Model BusinessPartner (z ST2) ma type enum: supplier/manufacturer/importer.
 * Kazdy partner moze byc dostawca, producent lub importer.
 *
 * @see \App\Models\BusinessPartner
 */
#[Layout('layouts.admin')]
class BusinessPartnerPanel extends Component
{
    use WithPagination;
    use WithFileUploads;
    use Traits\BusinessPartnerCrudTrait;
    use Traits\BusinessPartnerProductsTrait;
    use Traits\BusinessPartnerFiltersTrait;

    /**
     * Aktywna zakladka (persisted in URL)
     */
    #[Url]
    public string $activeTab = 'supplier';

    /**
     * Konfiguracja zakladek
     */
    public array $tabs = [
        'supplier' => [
            'label' => 'DOSTAWCA',
            'icon' => 'truck',
            'description' => 'Zarzadzaj dostawcami produktow',
        ],
        'manufacturer' => [
            'label' => 'PRODUCENT',
            'icon' => 'cog',
            'description' => 'Zarzadzaj producentami produktow',
        ],
        'importer' => [
            'label' => 'IMPORTER',
            'icon' => 'globe-alt',
            'description' => 'Zarzadzaj importerami produktow',
        ],
        'brak' => [
            'label' => 'BRAK',
            'icon' => 'exclamation-triangle',
            'description' => 'Produkty bez przypisanego partnera',
        ],
    ];

    /**
     * ID wybranego partnera w lewej kolumnie
     */
    public ?int $selectedEntityId = null;

    /**
     * Przelacz zakladke
     */
    public function switchTab(string $tab): void
    {
        if (! array_key_exists($tab, $this->tabs)) {
            return;
        }

        $this->activeTab = $tab;
        $this->selectedEntityId = null;
        $this->resetFilters();
        $this->resetPage();
        $this->resetPage('products');
    }

    /**
     * Wybierz partnera z listy
     */
    public function selectEntity(int $id): void
    {
        $this->selectedEntityId = $id;
        $this->productSearch = '';
        $this->resetPage('products');
    }

    /**
     * Odznacz wybranego partnera
     */
    public function deselectEntity(): void
    {
        $this->selectedEntityId = null;
        $this->productSearch = '';
        $this->resetPage('products');
    }

    public function render()
    {
        return view('livewire.admin.suppliers.business-partner-panel', [
            'title' => 'Zarzadzanie dostawcami - Admin PPM',
            'breadcrumb' => 'Zarzadzanie dostawcami',
        ]);
    }
}
