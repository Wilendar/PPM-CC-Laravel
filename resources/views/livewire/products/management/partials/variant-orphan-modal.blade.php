{{-- Variant Orphan Decision Modal --}}
{{-- Shows when user unchecks "Produkt z wariantami" but product has existing variants --}}

<div x-data="{ show: false, variantCount: 0 }"
     x-on:show-variant-orphan-modal.window="show = true; variantCount = $event.detail.variantCount"
     x-show="show"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">

    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
         x-show="show"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="show = false; $wire.call('handleCancelVariantUncheck')">
    </div>

    {{-- Modal Container --}}
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="relative inline-block align-bottom bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full"
             x-show="show"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             @click.away="show = false; $wire.call('handleCancelVariantUncheck')">

            {{-- Header --}}
            <div class="bg-orange-600 px-6 py-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="ml-3 text-lg font-semibold text-white">
                        Uwaga: Produkt ma <span x-text="variantCount"></span> wariantów
                    </h3>
                </div>
            </div>

            {{-- Body --}}
            <div class="bg-gray-800 px-6 py-6">
                <div class="text-gray-300 space-y-4">
                    <p class="text-base">
                        Ten produkt ma zdefiniowane warianty w bazie danych. Odznaczenie checkboxa "Produkt z wariantami" wymaga decyzji co zrobić z istniejącymi wariantami.
                    </p>

                    <div class="bg-gray-700 rounded-lg p-4 space-y-3">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 mt-0.5">
                                <svg class="h-5 w-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-white">Opcja 1: Konwertuj warianty na produkty</h4>
                                <p class="text-sm text-gray-400 mt-1">
                                    Każdy wariant zostanie przekonwertowany na osobny produkt standardowy.
                                    Wszystkie dane (SKU, ceny, stany magazynowe, zdjęcia) zostaną zachowane.
                                    Kategorie będą dziedziczone z produktu głównego.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 mt-0.5">
                                <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-white">Opcja 2: Usuń wszystkie warianty</h4>
                                <p class="text-sm text-gray-400 mt-1">
                                    Wszystkie warianty zostaną trwale usunięte z bazy danych.
                                    <span class="text-red-400 font-medium">To działanie jest nieodwracalne!</span>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 mt-0.5">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-white">Opcja 3: Anuluj</h4>
                                <p class="text-sm text-gray-400 mt-1">
                                    Checkbox pozostanie zaznaczony. Warianty pozostają bez zmian.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer with action buttons --}}
            <div class="bg-gray-750 px-6 py-4 flex flex-col sm:flex-row sm:justify-end space-y-2 sm:space-y-0 sm:space-x-3">
                {{-- Cancel Button --}}
                <button type="button"
                        @click="show = false; $wire.call('handleCancelVariantUncheck')"
                        class="w-full sm:w-auto px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white text-sm font-medium rounded-md transition-colors duration-200">
                    <svg class="inline-block w-4 h-4 mr-2 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Anuluj
                </button>

                {{-- Delete Button --}}
                <button type="button"
                        @click="show = false; $wire.call('handleDeleteVariants')"
                        class="w-full sm:w-auto px-4 py-2 bg-red-600 hover:bg-red-500 text-white text-sm font-medium rounded-md transition-colors duration-200">
                    <svg class="inline-block w-4 h-4 mr-2 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Usuń warianty
                </button>

                {{-- Convert Button (Primary) --}}
                <button type="button"
                        @click="show = false; $wire.call('handleConvertVariants')"
                        class="w-full sm:w-auto px-4 py-2 bg-green-600 hover:bg-green-500 text-white text-sm font-medium rounded-md transition-colors duration-200">
                    <svg class="inline-block w-4 h-4 mr-2 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Konwertuj na produkty
                </button>
            </div>
        </div>
    </div>
</div>
