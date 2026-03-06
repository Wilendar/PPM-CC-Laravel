<?php

namespace App\Http\Livewire\Dashboard\Widgets;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class QuickLinks extends Component
{
    public array $links = [];

    public function mount(): void
    {
        $this->links = array_values(array_filter($this->buildLinks(), fn($link) => $link['allowed']));
    }

    /**
     * Build quick links array based on user permissions.
     */
    protected function buildLinks(): array
    {
        $user = Auth::user();

        return [
            [
                'label' => 'Dashboard',
                'url' => '/admin',
                'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                'allowed' => true,
            ],
            [
                'label' => 'Produkty',
                'url' => '/admin/products',
                'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                'allowed' => $user?->can('products.read') ?? false,
            ],
            [
                'label' => 'Nowy produkt',
                'url' => '/admin/products/create',
                'icon' => 'M12 6v6m0 0v6m0-6h6m-6 0H6',
                'allowed' => $user?->can('products.create') ?? false,
            ],
            [
                'label' => 'Import',
                'url' => '/admin/products/import',
                'icon' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12',
                'allowed' => $user?->can('products.import') ?? false,
            ],
            [
                'label' => 'Sklepy',
                'url' => '/admin/shops',
                'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                'allowed' => $user?->can('shops.read') ?? false,
            ],
            [
                'label' => 'Integracje',
                'url' => '/admin/integrations',
                'icon' => 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                'allowed' => $user?->can('integrations.read') ?? false,
            ],
            [
                'label' => 'Uzytkownicy',
                'url' => '/admin/users',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'allowed' => $user?->can('users.manage') ?? false,
            ],
            [
                'label' => 'Ustawienia',
                'url' => '/admin/system-settings',
                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
                'allowed' => $user?->can('system.settings') ?? false,
            ],
            [
                'label' => 'Kategorie',
                'url' => '/admin/categories',
                'icon' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
                'allowed' => $user?->can('categories.read') ?? false,
            ],
            [
                'label' => 'Zamowienia',
                'url' => '/admin/orders',
                'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z',
                'allowed' => $user?->can('orders.read') ?? false,
            ],
            [
                'label' => 'Dostawy',
                'url' => '/admin/deliveries',
                'icon' => 'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0',
                'allowed' => $user?->can('deliveries.read') ?? false,
            ],
            [
                'label' => 'Reklamacje',
                'url' => '/admin/claims',
                'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'allowed' => $user?->can('claims.read') ?? false,
            ],
            [
                'label' => 'Raporty',
                'url' => '/admin/reports',
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                'allowed' => $user?->can('reports.read') ?? false,
            ],
            [
                'label' => 'Media',
                'url' => '/admin/media',
                'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
                'allowed' => $user?->can('media.read') ?? false,
            ],
            [
                'label' => 'Zgloszenia',
                'url' => '/admin/bug-reports',
                'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                'allowed' => $user?->can('bugs.read') ?? false,
            ],
        ];
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.quick-links');
    }
}
