{{-- MULTI-STORE MANAGEMENT (Second Line) --}}
{{-- Dostępne zarówno w create jak i edit mode --}}
<div class="mt-3 bg-gray-800 rounded-lg p-3">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <h4 class="text-sm font-semibold text-white">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Zarządzanie sklepami
            </h4>

            {{-- Default Data Toggle --}}
            <button type="button"
                    wire:click="switchToShop(null)"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full transition-colors duration-200 {{ $activeShopId === null ? 'bg-gradient-to-r from-orange-500 to-orange-600 text-white shadow-md' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 1v4" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 1v4" />
                </svg>
                Dane domyślne
            </button>
        </div>

        {{-- Shop Management Buttons --}}
        <div class="flex items-center space-x-2">
            <button type="button"
                    wire:click="openShopSelector"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors duration-200">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Dodaj do sklepu
            </button>
        </div>
    </div>

    {{-- Exported Shops List --}}
    @if(!empty($exportedShops))
        <div class="mt-3">
            <div class="flex flex-wrap gap-2">
                @foreach($exportedShops as $shopId)
                    @php
                        $shop = collect($availableShops)->firstWhere('id', $shopId);
                    @endphp
                    @if($shop)
                        <div wire:key="shop-label-{{ $shopId }}" class="inline-flex items-center group">
                            @php
                                $syncDisplay = $this->getSyncStatusDisplay($shop['id']);
                            @endphp

                            {{-- Shop Button - ETAP_07 FIX: Auto-load data on click --}}
                            <button type="button"
                                    wire:click="switchToShop({{ $shop['id'] }})"
                                    wire:loading.attr="disabled"
                                    wire:key="shop-btn-{{ $shop['id'] }}"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-l-lg transition-all duration-200 {{ $activeShopId === $shop['id'] ? 'shop-tab-active' : 'shop-tab-inactive' }}">
                                {{-- Shop Connection Status Icon --}}
                                @if($shop['connection_status'] === 'connected')
                                    <svg class="w-3 h-3 mr-1.5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    <svg class="w-3 h-3 mr-1.5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                                {{ Str::limit($shop['name'], 12) }}

                                {{-- Sync Status Badge - ENHANCED --}}
                                <span class="inline-flex items-center ml-2 px-2 py-0.5 rounded text-xs font-medium {{ $syncDisplay['class'] }}">
                                    {{ $syncDisplay['icon'] }} {{ $syncDisplay['text'] }}
                                </span>

                                {{-- PrestaShop ID badge (if exists) --}}
                                @if($syncDisplay['prestashop_id'])
                                    <span class="ml-1 text-xs text-gray-500 dark:text-gray-400 font-mono">
                                        #{{ $syncDisplay['prestashop_id'] }}
                                    </span>
                                @endif
                            </button>

                            {{-- Visibility Toggle --}}
                            <button type="button"
                                    wire:click="toggleShopVisibility({{ $shop['id'] }})"
                                    title="{{ $this->getShopVisibility($shop['id']) ? 'Ukryj w sklepie' : 'Pokaż w sklepie' }}"
                                    class="px-2 py-1.5 text-xs transition-colors duration-200 {{ $this->getShopVisibility($shop['id']) ? 'bg-green-500 hover:bg-green-600 text-white' : 'bg-gray-300 hover:bg-gray-400 text-gray-700' }}">
                                @if($this->getShopVisibility($shop['id']))
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"/>
                                        <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"/>
                                    </svg>
                                @endif
                            </button>

                            {{-- Delete from PrestaShop Button (Physical Delete) --}}
                            <button type="button"
                                    wire:click="deleteFromPrestaShop({{ $shop['id'] }})"
                                    wire:confirm="Czy na pewno FIZYCZNIE USUNĄĆ produkt ze sklepu PrestaShop? Ta operacja jest nieodwracalna!"
                                    title="Usuń fizycznie w sklepie PrestaShop"
                                    class="px-2 py-1.5 text-xs bg-red-700 hover:bg-red-800 text-white transition-all duration-200 opacity-0 group-hover:opacity-100">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </button>

                            {{-- Remove Association Button (Local only) --}}
                            <button type="button"
                                    wire:click="removeFromShop({{ $shop['id'] }})"
                                    wire:confirm="Czy na pewno usunąć powiązanie z tego sklepu? (produkt pozostanie w PrestaShop)"
                                    title="Usuń powiązanie (produkt pozostanie w sklepie)"
                                    class="px-2 py-1.5 text-xs bg-orange-500 hover:bg-orange-600 text-white rounded-r-lg transition-all duration-200 opacity-0 group-hover:opacity-100">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @else
        <div class="mt-3">
            <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                Ten produkt nie jest jeszcze eksportowany do żadnego sklepu
            </p>
        </div>
    @endif
</div>
