{{--
    Product Status Icon Component
    Displays a compact icon for global product issues with tooltip

    @param string $type - Issue type: 'zero_price', 'low_stock', 'no_images', 'not_in_prestashop', 'ok'
    @param int|null $count - Optional count for grouped issues (e.g., variant count)

    Usage:
    <x-product-status-icon type="zero_price" />
    <x-product-status-icon type="variant_issues" :count="3" />

    @since 2026-02-04
    @see Plan_Projektu/synthetic-mixing-thunder.md
--}}

@props(['type', 'count' => null])

@php
    $config = match($type) {
        'zero_price' => [
            'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'color' => 'red',
            'bg' => 'bg-red-900/30',
            'text' => 'text-red-400',
            'border' => 'border-red-700',
            'tooltip' => 'Cena 0,00 zł w aktywnej grupie cenowej',
        ],
        'low_stock' => [
            'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
            'color' => 'yellow',
            'bg' => 'bg-yellow-900/30',
            'text' => 'text-yellow-400',
            'border' => 'border-yellow-700',
            'tooltip' => 'Poniżej stanu minimalnego w magazynie domyślnym',
        ],
        'no_images' => [
            'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
            'color' => 'orange',
            'bg' => 'bg-orange-900/30',
            'text' => 'text-orange-400',
            'border' => 'border-orange-700',
            'tooltip' => 'Brak zdjęć produktu',
        ],
        'not_in_prestashop' => [
            'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
            'color' => 'gray',
            'bg' => 'bg-gray-700/30',
            'text' => 'text-gray-400',
            'border' => 'border-gray-600',
            'tooltip' => 'Produkt nie jest w żadnym sklepie PrestaShop',
        ],
        'variant_issues' => [
            'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
            'color' => 'purple',
            'bg' => 'bg-purple-900/30',
            'text' => 'text-purple-400',
            'border' => 'border-purple-700',
            'tooltip' => $count ? "Problemy z wariantami ({$count})" : 'Problemy z wariantami',
        ],
        'ok' => [
            'icon' => 'M5 13l4 4L19 7',
            'color' => 'green',
            'bg' => 'bg-green-900/30',
            'text' => 'text-green-400',
            'border' => 'border-green-700',
            'tooltip' => 'Wszystko w porządku',
        ],
        default => [
            'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            'color' => 'gray',
            'bg' => 'bg-gray-700/30',
            'text' => 'text-gray-400',
            'border' => 'border-gray-600',
            'tooltip' => 'Nieznany status',
        ],
    };
@endphp

<span class="relative inline-flex items-center justify-center w-6 h-6 rounded border {{ $config['bg'] }} {{ $config['text'] }} {{ $config['border'] }} cursor-help transition-colors hover:opacity-80"
      title="{{ $config['tooltip'] }}">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $config['icon'] }}"/>
    </svg>

    {{-- Count badge (for variant issues, etc.) --}}
    @if($count && $count > 0)
        <span class="absolute -top-1.5 -right-1.5 w-4 h-4 text-[10px] font-bold rounded-full {{ $config['bg'] }} {{ $config['text'] }} border {{ $config['border'] }} flex items-center justify-center">
            {{ $count > 9 ? '9+' : $count }}
        </span>
    @endif
</span>
