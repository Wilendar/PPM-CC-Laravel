{{-- resources/views/livewire/products/management/tabs/attributes-tab.blade.php --}}
<div class="tab-content active space-y-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-white">
            <svg class="w-6 h-6 inline mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
            Atrybuty produktu
        </h3>

        {{-- Active Shop Indicator --}}
        @if($activeShopId !== null && isset($availableShops))
            @php
                $currentShop = collect($availableShops)->firstWhere('id', $activeShopId);
            @endphp
            <div class="flex items-center">
                <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-orange-900/30 text-orange-200 border border-orange-700/50">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Edytujesz: {{ $currentShop['name'] ?? 'Nieznany sklep' }}
                </span>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-6">
        {{-- Attributes Management per Shop --}}
        <div class="bg-gray-800 rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-md font-medium text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    Cechy i parametry produktu
                </h4>
            </div>

            {{-- Attributes List --}}
            <div class="space-y-4">
                {{-- Placeholder for attributes --}}
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    <h3 class="text-lg font-medium text-white mb-2">System atrybutów</h3>
                    <p class="text-sm mb-4">
                        Zarządzaj atrybutami produktu takimi jak Model, Oryginał, Zamiennik, Kolor, Rozmiar.
                        <br>Każdy sklep może mieć różne wartości atrybutów.
                    </p>
                    <div class="text-xs bg-blue-50 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded-lg p-3">
                        <strong>Nadchodząca funkcja:</strong> Interfejs zarządzania atrybutami będzie dostępny w najbliższej aktualizacji.
                        Backend jest już przygotowany dla systemu EAV (Entity-Attribute-Value).
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
