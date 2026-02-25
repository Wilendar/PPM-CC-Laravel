{{-- MODE: Individual Products --}}
<div>
    {{-- CRITICAL: Search Input --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-300 mb-2">
            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            Wyszukaj produkt (po nazwie lub SKU)
        </label>
        <div class="relative">
            <input type="text"
                   wire:model.live.debounce.500ms="importSearch"
                   placeholder="Wpisz min. 3 znaki nazwy lub SKU..."
                   class="form-input-enterprise w-full rounded-lg pr-10">
            <div wire:loading wire:target="loadPrestaShopProducts" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                <svg class="animate-spin h-5 w-5 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>
        <p class="text-xs text-gray-500 mt-1">
            <svg class="w-3.5 h-3.5 inline-block mr-1 text-gray-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
            Wpisz minimum 3 znaki aby rozpocząć wyszukiwanie
        </p>

        @if(!empty($importSearch))
            <p class="text-sm text-orange-500 mt-1">
                <svg class="w-3.5 h-3.5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Wyszukiwanie: "{{ $importSearch }}"
            </p>
        @endif
    </div>

    @if(empty($prestashopProducts))
        <div class="text-center py-8">
            {{-- Loading spinner - shows during API call or search --}}
            <div wire:loading wire:target="setImportShop,updatedImportShopId,loadPrestaShopProducts,updatedImportSearch">
                <svg class="animate-spin h-8 w-8 mx-auto text-orange-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-2 text-gray-400">
                    @if(!empty($importSearch))
                        Wyszukiwanie produktów...
                    @else
                        Ładowanie produktów z PrestaShop...
                    @endif
                </p>
            </div>

            {{-- Empty state - shows when not loading and no products --}}
            <div wire:loading.remove wire:target="setImportShop,updatedImportShopId,loadPrestaShopProducts,updatedImportSearch">
                @if(!empty($importSearch))
                    <p class="text-gray-400 text-sm">
                        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Brak produktów pasujących do wyszukiwania: <strong>"{{ $importSearch }}"</strong>
                    </p>
                @else
                    <p class="text-gray-400 text-sm">
                        <svg class="w-4 h-4 inline-block mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Produkty zostaną załadowane automatycznie po wyborze sklepu<br>
                        lub użyj wyszukiwarki powyżej
                    </p>
                @endif
            </div>
        </div>
    @else
        <div class="mb-2 text-sm text-gray-400">
            Znaleziono: <strong>{{ count($prestashopProducts) }}</strong> produktów
            @if(count($selectedProductsToImport) > 0)
                | Wybrano: <strong class="text-orange-500">{{ count($selectedProductsToImport) }}</strong>
            @endif
        </div>

        <div class="border border-gray-600 rounded-lg max-h-64 overflow-y-auto">
            @foreach($prestashopProducts as $product)
                @php
                    $isSelected = in_array($product['id'], $selectedProductsToImport);
                    $existsInPPM = App\Models\Product::where('sku', $product['reference'] ?? '')->exists();
                @endphp

                <label class="flex items-center p-3 hover:bg-gray-700 border-b border-gray-700 cursor-pointer {{ $isSelected ? 'bg-orange-500 bg-opacity-10' : '' }}">
                    <input type="checkbox"
                           wire:click="toggleProductSelection({{ $product['id'] }})"
                           {{ $isSelected ? 'checked' : '' }}
                           class="form-checkbox mr-3 text-orange-500">

                    <div class="flex-1">
                        <div class="font-medium text-white">
                            {{ $product['name'] ?? 'Brak nazwy' }}
                        </div>
                        <div class="text-sm text-gray-400">
                            SKU: <strong>{{ $product['reference'] ?? 'N/A' }}</strong>
                            | ID: {{ $product['id'] }}
                        </div>
                    </div>

                    @if($existsInPPM)
                        <span class="ml-2 px-2 py-1 bg-green-900/50 text-green-300 text-xs rounded">
                            <svg class="w-3 h-3 inline-block mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            Istnieje w PPM
                        </span>
                    @endif
                </label>
            @endforeach
        </div>

        @if(count($selectedProductsToImport) > 0)
            {{-- Variant Import Checkbox --}}
            <div class="mt-4 mb-4">
                <label class="flex items-center text-sm text-gray-300 cursor-pointer hover:text-white transition-colors">
                    <input type="checkbox"
                           wire:model.live="importWithVariants"
                           class="form-checkbox mr-2 text-orange-500 rounded border-gray-500 focus:ring-orange-500">
                    <span>Automatycznie importuj brakujace warianty z PrestaShop</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-6">
                    Dla produktow z wariantami (combinations) zostana utworzone odpowiednie warianty w PPM
                </p>
            </div>

            <button wire:click="importSelectedProducts"
                    class="btn-enterprise-primary inline-flex items-center">
                <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/></svg>
                Importuj wybrane ({{ count($selectedProductsToImport) }})
            </button>
        @endif
    @endif
</div>
