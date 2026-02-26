@if($activePopup)
@php
    $popupProductId = $activePopup['productId'];
    $popupSourceKey = $activePopup['sourceKey'];

    preg_match('/^(.+)_(\d+)$/', $popupSourceKey, $matches);
    $popupSourceType = $matches[1] ?? '';
    $popupSourceId = (int)($matches[2] ?? 0);

    $popupProduct = $matrixData->firstWhere('id', $popupProductId);
    $popupCell = $popupProduct?->matrix_cells[$popupSourceKey] ?? ['status' => 'unknown'];
    $popupStatus = $popupCell['status'] ?? 'unknown';
    $popupBrand = $popupProduct?->manufacturerRelation?->name ?? '';
    $popupSource = collect($sources)->first(fn($s) => ($s['type'] . '_' . $s['id']) === $popupSourceKey);

    $statusConfig = [
        'linked'            => ['label' => 'Powiazany',             'class' => 'text-green-400',  'bg' => 'bg-green-900/30'],
        'not_linked'        => ['label' => 'Niepowiazany',           'class' => 'text-blue-400',   'bg' => 'bg-blue-900/30'],
        'not_found'         => ['label' => 'Nie znaleziono',         'class' => 'text-red-400',    'bg' => 'bg-red-900/30'],
        'unknown'           => ['label' => 'Brak danych skanu',      'class' => 'text-gray-400',   'bg' => 'bg-gray-700/50'],
        'ignored'           => ['label' => 'Ignorowany',             'class' => 'text-gray-500',   'bg' => 'bg-gray-800/50'],
        'conflict'          => ['label' => 'Konflikt danych',        'class' => 'text-yellow-400', 'bg' => 'bg-yellow-900/30'],
        'brand_not_allowed' => ['label' => 'Marka niedozwolona',     'class' => 'text-amber-400',  'bg' => 'bg-amber-900/30'],
        'pending_sync'      => ['label' => 'Oczekuje na sync',       'class' => 'text-blue-400',   'bg' => 'bg-blue-900/30'],
    ];
    $currentStatus = $statusConfig[$popupStatus] ?? $statusConfig['unknown'];
@endphp

<div class="fixed inset-0 z-50" wire:click="closePopup" aria-modal="true" role="dialog">
    <div class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-gray-800 border border-gray-600 rounded-lg shadow-2xl min-w-[260px] max-w-[340px] w-full"
         wire:click.stop
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-700">
            <div class="flex flex-col min-w-0">
                <span class="text-sm font-semibold text-white truncate">{{ $popupProduct?->sku ?? '?' }}</span>
                @if($popupSource)
                <span class="text-xs text-gray-500 truncate">{{ $popupSource['name'] }}</span>
                @endif
            </div>
            <button wire:click="closePopup" class="ml-2 p-1 text-gray-400 hover:text-white rounded transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Status badge --}}
        <div class="px-4 py-2 border-b border-gray-700">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $currentStatus['bg'] }} {{ $currentStatus['class'] }}">
                {{ $currentStatus['label'] }}
            </span>
            @if(!empty($popupCell['external_id']))
            <span class="text-xs text-gray-500 ml-2">ID: {{ $popupCell['external_id'] }}</span>
            @endif
        </div>

        {{-- Akcje --}}
        <div class="py-1">
            @switch($popupStatus)

                {{-- POWIAZANY --}}
                @case('linked')
                    <a href="/admin/products/{{ $popupProductId }}/edit" target="_blank" class="matrix-popup-action">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        Otworz produkt
                    </a>
                    <button wire:click="cellAction({{ $popupProductId }}, '{{ $popupSourceType }}', {{ $popupSourceId }}, 'force_sync')"
                            class="matrix-popup-action">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Wymus sync
                    </button>
                    <button wire:click="cellAction({{ $popupProductId }}, '{{ $popupSourceType }}', {{ $popupSourceId }}, 'unlink')"
                            class="matrix-popup-action text-red-400 hover:text-red-300 hover:bg-red-900/20">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        Rozlacz
                    </button>
                    @break

                {{-- NIEPOWIAZANY (istnieje w zrodle) -> POWIAZ --}}
                @case('not_linked')
                    <button wire:click="cellAction({{ $popupProductId }}, '{{ $popupSourceType }}', {{ $popupSourceId }}, 'link')"
                            class="matrix-popup-action text-blue-400 hover:text-blue-300 hover:bg-blue-900/20 font-medium">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        Powiaz z PPM
                    </button>
                    <button wire:click="cellAction({{ $popupProductId }}, '{{ $popupSourceType }}', {{ $popupSourceId }}, 'ignore')"
                            class="matrix-popup-action text-gray-500 hover:text-gray-400">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M3 3l18 18"/>
                        </svg>
                        Ignoruj
                    </button>
                    <a href="/admin/products/{{ $popupProductId }}/edit" target="_blank" class="matrix-popup-action">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        Otworz produkt
                    </a>
                    @break

                {{-- NIE ZNALEZIONO (nie istnieje w zrodle) -> EKSPORTUJ --}}
                @case('not_found')
                    <button wire:click="cellAction({{ $popupProductId }}, '{{ $popupSourceType }}', {{ $popupSourceId }}, 'publish')"
                            class="matrix-popup-action text-red-400 hover:text-red-300 hover:bg-red-900/20 font-medium">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        Eksportuj do zrodla
                    </button>
                    <button wire:click="cellAction({{ $popupProductId }}, '{{ $popupSourceType }}', {{ $popupSourceId }}, 'ignore')"
                            class="matrix-popup-action text-gray-500 hover:text-gray-400">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M3 3l18 18"/>
                        </svg>
                        Ignoruj
                    </button>
                    <a href="/admin/products/{{ $popupProductId }}/edit" target="_blank" class="matrix-popup-action">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        Otworz produkt
                    </a>
                    @break

                {{-- IGNOROWANY -> PRZYWROC --}}
                @case('ignored')
                    <div class="px-4 py-2 text-xs text-gray-500">
                        Produkt zignorowany dla tego zrodla.
                    </div>
                    <button wire:click="cellAction({{ $popupProductId }}, '{{ $popupSourceType }}', {{ $popupSourceId }}, 'unignore')"
                            class="matrix-popup-action text-amber-400 hover:text-amber-300 hover:bg-amber-900/20">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Przywroc
                    </button>
                    @break

                {{-- NIEZNANY (brak skanu) --}}
                @case('unknown')
                    <div class="px-4 py-2 text-xs text-gray-500">
                        Brak danych skanowania. Uruchom skan aby sprawdzic status.
                    </div>
                    <button wire:click="cellAction({{ $popupProductId }}, '{{ $popupSourceType }}', {{ $popupSourceId }}, 'ignore')"
                            class="matrix-popup-action text-gray-500 hover:text-gray-400">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M3 3l18 18"/>
                        </svg>
                        Ignoruj
                    </button>
                    @break

                {{-- KONFLIKT --}}
                @case('conflict')
                    <button wire:click="toggleDiffViewer({{ $popupProductId }})" class="matrix-popup-action text-yellow-400 hover:bg-yellow-900/20">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/>
                        </svg>
                        Pokaz roznice
                    </button>
                    <button wire:click="cellAction({{ $popupProductId }}, '{{ $popupSourceType }}', {{ $popupSourceId }}, 'force_sync')" class="matrix-popup-action">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Nadpisz danymi PPM
                    </button>
                    @break

                {{-- MARKA NIEDOZWOLONA --}}
                @case('brand_not_allowed')
                    @if($popupBrand)
                    <button wire:click="addBrandToAllowed('{{ addslashes($popupBrand) }}', {{ $popupSourceId }})" class="matrix-popup-action text-amber-400 hover:bg-amber-900/20">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Dodaj {{ $popupBrand }} do dozwolonych
                    </button>
                    @endif
                    @break

                {{-- PENDING SYNC --}}
                @case('pending_sync')
                    <div class="px-4 py-2 text-xs text-blue-400 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Oczekuje na synchronizacje
                    </div>
                    <button wire:click="cellAction({{ $popupProductId }}, '{{ $popupSourceType }}', {{ $popupSourceId }}, 'force_sync')" class="matrix-popup-action">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Wymus sync
                    </button>
                    @break

            @endswitch
        </div>

        {{-- Footer --}}
        @if($popupProduct?->name)
        <div class="px-4 py-2 border-t border-gray-700">
            <span class="text-xs text-gray-500 truncate block">{{ Str::limit($popupProduct->name, 50) }}</span>
        </div>
        @endif
    </div>
</div>
@endif
