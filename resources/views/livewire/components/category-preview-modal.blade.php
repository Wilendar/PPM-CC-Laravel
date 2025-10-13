{{-- CategoryPreviewModal Component - ETAP_07 FAZA 3D --}}
{{-- Enterprise category import preview system --}}
<div x-data="{ isOpen: @entangle('isOpen') }"
     x-show="isOpen"
     x-cloak
     class="fixed inset-0 overflow-y-auto z-[9999]"
     aria-labelledby="modal-title"
     role="dialog"
     aria-modal="true">

    <!-- Background Overlay -->
    <div x-show="isOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="isOpen = false"
         class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity"></div>

    <!-- Modal Container -->
    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0 relative modal-z-content">
        <div x-show="isOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             @click.stop
             class="relative transform overflow-hidden rounded-xl shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-4xl modal-bg-enterprise modal-border-brand">

            <!-- Modal Header - DARK THEME -->
            <div class="px-6 py-4 border-b border-brand-500/30 bg-gradient-to-r from-gray-800 via-gray-900 to-gray-800">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white flex items-center gap-2" id="modal-title">
                            <svg class="w-5 h-5 text-brand-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                            </svg>
                            Podgląd Kategorii do Zaimportowania
                        </h3>
                        <p class="text-sm text-gray-300 mt-1">
                            Sklep: <strong class="text-brand-400">{{ $shopName }}</strong> | Znaleziono: <strong class="text-white">{{ $totalCount }}</strong> {{ $totalCount === 1 ? 'kategoria' : ($totalCount < 5 ? 'kategorie' : 'kategorii') }}
                        </p>
                    </div>
                    <button @click="isOpen = false"
                            class="rounded-lg p-2 hover:bg-gray-700/50 transition-colors duration-200">
                        <svg class="w-6 h-6 text-gray-300 hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Actions Bar -->
            <div class="px-6 py-3 bg-gray-800/30 border-b border-gray-700/50">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-3 flex-wrap">
                        <button wire:click="selectAll"
                                @disabled($skipCategories)
                                class="px-3 py-1.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Zaznacz wszystkie
                        </button>
                        <button wire:click="deselectAll"
                                @disabled($skipCategories)
                                class="px-3 py-1.5 text-sm font-semibold text-white bg-gray-600 hover:bg-gray-700 rounded-lg transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                            Odznacz wszystkie
                        </button>
                        <div class="h-5 w-px bg-gray-600"></div>
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="checkbox"
                                   x-data="{ checked: @entangle('skipCategories').live }"
                                   x-model="checked"
                                   class="w-4 h-4 rounded border-gray-600 text-orange-600 focus:ring-orange-500 focus:ring-offset-gray-900 cursor-pointer">
                            <span class="text-sm font-medium text-gray-300 group-hover:text-white transition-colors">
                                <svg class="w-4 h-4 inline-block mr-1 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                Importuj produkty BEZ kategorii
                            </span>
                        </label>
                    </div>
                    <span class="text-sm text-gray-400">
                        Wybrano: <strong class="text-brand-400">{{ count($selectedCategoryIds) }}</strong> / {{ $totalCount }}
                    </span>
                </div>

                @if($skipCategories)
                    <div class="mt-3 px-3 py-2 bg-orange-900/20 border border-orange-500/30 rounded-lg">
                        <p class="text-xs text-orange-300 flex items-center gap-2">
                            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <span><strong>Tryb bez kategorii:</strong> Produkty zostaną zaimportowane bez przypisania kategorii. Zaznaczanie kategorii jest wyłączone.</span>
                        </p>
                    </div>
                @endif
            </div>

            <!-- Category Tree -->
            <div class="px-6 py-6 max-h-[50vh] overflow-y-auto {{ $skipCategories ? 'opacity-30 pointer-events-none' : '' }}">
                @if(empty($categoryTree))
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto mb-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <h4 class="text-xl font-bold text-white mb-2">Wszystkie kategorie już istnieją!</h4>
                        <p class="text-gray-300 text-sm max-w-md mx-auto">
                            Wszystkie kategorie używane przez wybrane produkty są już dostępne w PPM.
                            Możesz kontynuować import produktów bez potrzeby tworzenia nowych kategorii.
                        </p>

                        {{-- Category Mapping Info --}}
                        @if(isset($sourceCategoryName) && $sourceCategoryName)
                            <div class="mt-6 p-4 bg-gray-800/50 border border-gray-600/30 rounded-lg max-w-md mx-auto">
                                <p class="text-sm text-gray-300 mb-2">
                                    <strong class="text-white">Importujesz z kategorii PrestaShop:</strong><br>
                                    <span class="text-brand-400">{{ $sourceCategoryName }}</span>
                                </p>
                                @if(isset($targetCategoryName) && $targetCategoryName)
                                    <p class="text-sm text-gray-300">
                                        <strong class="text-white">Produkty trafią do kategorii PPM:</strong><br>
                                        <span class="text-green-400">{{ $targetCategoryName }}</span>
                                    </p>
                                @else
                                    <p class="text-xs text-gray-400 mt-2 italic">
                                        <svg class="w-4 h-4 inline-block mr-1 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                        </svg>
                                        Kategoria docelowa PPM: będzie przypisana podczas importu zgodnie z istniejącym mapowaniem produktów
                                    </p>
                                @endif
                            </div>
                        @endif

                        <div class="mt-6 p-4 bg-blue-900/20 border border-blue-500/30 rounded-lg max-w-md mx-auto">
                            <p class="text-sm text-blue-300 flex items-start gap-2">
                                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Kliknij <strong>"Importuj Produkty"</strong> poniżej aby kontynuować, lub zaznacz opcję "Importuj BEZ kategorii" jeśli chcesz pominąć przypisanie kategorii.</span>
                            </p>
                        </div>
                    </div>
                @else
                    <div class="space-y-1">
                        @foreach($categoryTree as $category)
                            <x-category-tree-item
                                :category="$category"
                                :level="0"
                                wire:key="cat-{{ $category['prestashop_id'] }}"
                            />
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-700/50 bg-gray-800/30 flex items-center justify-between">
                <button wire:click="reject"
                        wire:loading.attr="disabled"
                        wire:target="reject"
                        class="px-6 py-2 rounded-lg font-semibold text-sm text-white bg-gray-700 hover:bg-gray-600 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                    Anuluj Import
                </button>

                <button wire:click="approve"
                        wire:loading.attr="disabled"
                        wire:target="approve"
                        @disabled(!empty($categoryTree) && !$skipCategories && count($selectedCategoryIds) === 0)
                        class="px-6 py-2 rounded-lg font-semibold text-sm text-white transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 {{ $skipCategories ? 'bg-orange-600 hover:bg-orange-700' : 'bg-green-600 hover:bg-green-700' }}">
                    <span wire:loading.remove wire:target="approve">
                        @if($skipCategories)
                            <svg class="w-5 h-5 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Importuj Produkty BEZ Kategorii
                        @elseif($totalCount === 0 || count($selectedCategoryIds) === 0)
                            {{-- All categories exist OR none selected --}}
                            <svg class="w-5 h-5 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Importuj Produkty
                        @else
                            {{-- Has categories to create --}}
                            <svg class="w-5 h-5 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Utwórz Kategorie i Importuj ({{ count($selectedCategoryIds) }})
                        @endif
                    </span>
                    <span wire:loading wire:target="approve" class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        @if($skipCategories)
                            Importowanie...
                        @else
                            Tworzenie...
                        @endif
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
