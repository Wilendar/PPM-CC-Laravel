<?php

namespace App\Http\Livewire\Admin\Parameters;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

/**
 * ProductParametersManager - Panel Zarządzania Parametrami Produktu
 *
 * Główny komponent z tabs: Atrybuty | Marki | Magazyny | Typy Produktów
 * Każdy tab ładowany lazy dla wydajności
 */
#[Layout('layouts.admin')]
class ProductParametersManager extends Component
{
    /**
     * Active tab (persisted in URL)
     */
    #[Url]
    public string $activeTab = 'attributes';

    /**
     * Available tabs configuration
     */
    public array $tabs = [
        'attributes' => [
            'label' => 'Atrybuty wariantów',
            'icon' => 'tags',
            'description' => 'Zarządzaj atrybutami wariantów (rozmiar, kolor, itd.)',
        ],
        'manufacturers' => [
            'label' => 'Marki',
            'icon' => 'building',
            'description' => 'Zarządzaj markami produktów i ich przypisaniem do sklepów',
        ],
        'warehouses' => [
            'label' => 'Magazyny',
            'icon' => 'warehouse',
            'description' => 'Zarządzaj magazynami i lokalizacjami',
        ],
        'product-types' => [
            'label' => 'Typy produktów',
            'icon' => 'cubes',
            'description' => 'Zarządzaj typami produktów',
        ],
        'data-cleanup' => [
            'label' => 'Czyszczenie danych',
            'icon' => 'trash',
            'description' => 'Wykrywaj i usuwaj osierocone dane zakłócające integralność bazy',
        ],
        'status-monitoring' => [
            'label' => 'Monitorowanie zgodności',
            'icon' => 'shield-check',
            'description' => 'Konfiguruj monitorowanie zgodności danych produktów z integracjami',
        ],
    ];

    /**
     * Switch to a different tab
     */
    public function switchTab(string $tab): void
    {
        if (array_key_exists($tab, $this->tabs)) {
            $this->activeTab = $tab;
        }
    }

    public function render()
    {
        return view('livewire.admin.parameters.product-parameters-manager');
    }
}
