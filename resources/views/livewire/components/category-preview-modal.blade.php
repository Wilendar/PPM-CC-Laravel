{{-- CategoryPreviewModal Component - ETAP_07 FAZA 3D --}}
{{-- Enterprise category import preview system --}}
{{-- LIVEWIRE ROOT: Transparent wrapper (no stacking context) --}}
<div>
    {{-- Preview Modal --}}
    <div x-data="{ isOpen: @entangle('isOpen') }"
         x-show="isOpen"
         x-cloak
         class="modal-category-preview-root"
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
    <div class="p-4 text-center sm:p-0 relative modal-z-content">
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
                        <p class="text-sm text-gray-300 mt-1 flex items-center gap-3">
                            <span>Sklep: <strong class="text-brand-400">{{ $shopName }}</strong> | Znaleziono: <strong class="text-white">{{ $totalCount }}</strong> {{ $totalCount === 1 ? 'kategoria' : ($totalCount < 5 ? 'kategorie' : 'kategorii') }}</span>
                            @if(count($detectedConflicts) > 0)
                                <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-orange-500/20 border border-orange-500/30 text-orange-400">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ count($detectedConflicts) }} {{ count($detectedConflicts) === 1 ? 'konflikt' : 'konfliktów' }}
                                </span>
                            @endif
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
                    <div class="flex items-center gap-3">
                        @if(count($detectedConflicts) > 0)
                            <button wire:click="toggleConflicts"
                                    class="px-3 py-1.5 text-sm font-semibold text-white rounded-lg transition-colors duration-200 flex items-center gap-2 {{ $showConflicts ? 'bg-orange-700 hover:bg-orange-800' : 'bg-orange-600 hover:bg-orange-700' }}">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path>
                                </svg>
                                <span>{{ $showConflicts ? 'Ukryj konflikty' : 'Rozwiąż konflikty' }}</span>
                                <span class="px-1.5 py-0.5 bg-white/20 rounded text-xs">{{ count($detectedConflicts) }}</span>
                            </button>
                        @endif
                        <span class="text-sm text-gray-400">
                            Wybrano: <strong class="text-brand-400">{{ count($selectedCategoryIds) }}</strong> / {{ $totalCount }}
                        </span>
                    </div>
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

                {{-- Conflicts Section (2025-10-13) --}}
                @if(count($detectedConflicts) > 0 && $showConflicts)
                    <div class="mt-3 p-4 bg-orange-900/10 border border-orange-500/20 rounded-lg">
                        <div class="mb-3">
                            <h4 class="text-sm font-bold text-orange-300 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                Wykryte konflikty kategorii ({{ count($detectedConflicts) }})
                            </h4>
                            <p class="text-xs text-gray-400 mt-1">
                                Następujące produkty będą re-importowane z innymi kategoriami niż obecnie przypisane w PPM:
                            </p>
                        </div>

                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            @foreach($detectedConflicts as $index => $conflict)
                                <div wire:key="conflict-{{ $conflict['product_id'] }}"
                                     class="p-3 bg-gray-800/40 border border-gray-700/50 rounded-lg hover:bg-gray-800/60 transition-colors">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-sm font-semibold text-white truncate">{{ $conflict['name'] }}</span>
                                                <span class="text-xs text-gray-400">(SKU: {{ $conflict['sku'] }})</span>
                                            </div>
                                            <div class="text-xs text-gray-400 space-y-1">
                                                @if($conflict['has_default_conflict'])
                                                    <div class="flex items-start gap-2">
                                                        <span class="text-orange-400 font-medium whitespace-nowrap">Domyślne:</span>
                                                        <span class="text-gray-300">
                                                            {{ count($conflict['ppm_default_categories']) }} → {{ count($conflict['import_will_assign_categories']) }} kategorii
                                                        </span>
                                                    </div>
                                                @endif
                                                @if($conflict['has_shop_conflict'])
                                                    <div class="flex items-start gap-2">
                                                        <span class="text-orange-400 font-medium whitespace-nowrap">Sklep {{ $shopName }}:</span>
                                                        <span class="text-gray-300">
                                                            {{ count($conflict['shop_categories']) }} → {{ count($conflict['import_will_assign_categories']) }} kategorii
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 flex-shrink-0">
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $conflict['has_default_conflict'] && $conflict['has_shop_conflict'] ? 'bg-red-500/20 text-red-400' : 'bg-orange-500/20 text-orange-400' }}">
                                                {{ $conflict['has_default_conflict'] && $conflict['has_shop_conflict'] ? 'Oba' : ($conflict['has_default_conflict'] ? 'Domyślne' : 'Sklep') }}
                                            </span>
                                            <button wire:click="openConflictResolution({{ $conflict['product_id'] }})"
                                                    class="px-2 py-1 text-xs font-semibold text-white bg-orange-600 hover:bg-orange-700 rounded transition-colors duration-200">
                                                Rozwiąż
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 p-3 bg-blue-900/20 border border-blue-500/30 rounded-lg">
                            <p class="text-xs text-blue-300 flex items-start gap-2">
                                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                <span>
                                    <strong>Co zrobić?</strong><br>
                                    Po zakończeniu importu, zostaniesz poproszony o rozwiązanie każdego konfliktu - wybierzesz czy zachować obecne kategorie PPM, czy zaktualizować je danymi z PrestaShop.
                                </span>
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Quick Category Creator - Button Only (ETAP 4) -->
            <div class="px-6 py-4 border-t border-gray-700/50">
                <button wire:click="showCreateCategoryForm"
                        class="px-4 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Utwórz nową kategorię</span>
                </button>
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

    {{-- ETAP 4: Quick Category Creator Form (z-index 9999 - INSIDE Livewire root for wire:click to work) --}}
    @if($showCreateForm)
    <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/90 backdrop-blur-md"
         role="dialog"
         aria-modal="true"
         aria-labelledby="create-category-title">

            <!-- Background Overlay -->
            <div @click="$wire.hideCreateCategoryForm()"
                 class="absolute inset-0 bg-black/90 backdrop-blur-md"></div>

            <!-- Modal Content -->
            <div @click.stop
                 class="relative w-full max-w-md p-6 bg-gradient-to-br from-gray-800 via-gray-900 to-gray-800 rounded-xl shadow-2xl border border-green-500/30">

                <!-- Modal Header -->
                <div class="mb-6">
                    <h4 class="text-lg font-bold text-white flex items-center gap-2" id="create-category-title">
                        <svg class="w-6 h-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                        </svg>
                        Utwórz nową kategorię
                    </h4>
                    <p class="text-xs text-gray-400 mt-1">Wypełnij formularz aby utworzyć nową kategorię w PPM</p>
                </div>

                <!-- Form Fields -->
                <div class="space-y-4">
                    <!-- Category Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Nazwa kategorii <span class="text-red-400">*</span>
                        </label>
                        <input type="text"
                               wire:model.live="newCategoryForm.name"
                               placeholder="np. Elektronika, Odzież, Akcesoria..."
                               class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                        @error('newCategoryForm.name')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Parent Category -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Kategoria nadrzędna (opcjonalnie)
                        </label>
                        <select wire:model.live="newCategoryForm.parent_id"
                                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                            <option value="">-- Brak (kategoria główna) --</option>
                            @foreach($this->parentCategoryOptions as $categoryId => $categoryName)
                                <option value="{{ $categoryId }}">{{ $categoryName }}</option>
                            @endforeach
                        </select>
                        @error('newCategoryForm.parent_id')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Opis (opcjonalnie)
                        </label>
                        <textarea wire:model.live="newCategoryForm.description"
                                  rows="2"
                                  placeholder="Krótki opis kategorii..."
                                  class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all resize-none"></textarea>
                        @error('newCategoryForm.description')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Active Checkbox -->
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="checkbox"
                                   wire:model.live="newCategoryForm.is_active"
                                   class="w-4 h-4 rounded border-gray-600 text-green-600 focus:ring-green-500 focus:ring-offset-gray-900 cursor-pointer">
                            <span class="text-sm text-gray-300 group-hover:text-white transition-colors">
                                Kategoria aktywna (widoczna)
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-gray-700/50">
                    <button @click="$wire.hideCreateCategoryForm()"
                            type="button"
                            class="px-4 py-2 text-sm font-semibold text-gray-300 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors duration-200">
                        Anuluj
                    </button>
                    <button wire:click="createQuickCategory"
                            type="button"
                            wire:loading.attr="disabled"
                            wire:target="createQuickCategory"
                            class="px-4 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors duration-200 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="createQuickCategory">
                            <svg class="w-5 h-5 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path>
                            </svg>
                            Utwórz kategorię
                        </span>
                        <span wire:loading wire:target="createQuickCategory" class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Tworzenie...
                        </span>
                    </button>
                </div>
            </div>
    </div>
@endif

{{-- ETAP 3.4: Conflict Resolution Modal (OUTSIDE main modal for proper z-index) --}}
@if($showConflictResolutionModal && $selectedConflictProduct)
        <div x-data="{ isOpen: @entangle('showConflictResolutionModal') }"
             x-show="isOpen"
             x-cloak
             class="modal-conflict-resolution-root"
             aria-labelledby="conflict-modal-title"
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
                 @click="$wire.closeConflictResolution()"
                 class="absolute inset-0 bg-black/80 backdrop-blur-sm transition-opacity"></div>

            <!-- Modal Container -->
            <div class="p-4 text-center sm:p-0 relative">
                <div x-show="isOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @click.stop
                     class="relative overflow-hidden rounded-xl shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-5xl modal-bg-enterprise modal-border-brand">

                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-orange-500/30 bg-gradient-to-r from-gray-800 via-orange-900/20 to-gray-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-white flex items-center gap-2" id="conflict-modal-title">
                                    <svg class="w-5 h-5 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    Rozwiązywanie Konfliktu Kategorii
                                </h3>
                                <p class="text-sm text-gray-300 mt-1">
                                    <span class="font-semibold text-white">{{ $selectedConflictProduct['name'] }}</span>
                                    <span class="text-gray-400">(SKU: {{ $selectedConflictProduct['sku'] }})</span>
                                </p>
                            </div>
                            <button @click="$wire.closeConflictResolution()"
                                    class="rounded-lg p-2 hover:bg-gray-700/50 transition-colors duration-200">
                                <svg class="w-6 h-6 text-gray-300 hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Visual Diff Section -->
                    <div class="px-6 py-4 bg-gray-800/30 border-b border-gray-700/50 text-left">
                        <h4 class="text-sm font-bold text-gray-200 mb-3">Porównanie Kategorii</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Current Categories in PPM -->
                            <div class="p-4 bg-blue-900/20 border border-blue-500/30 rounded-lg text-left">
                                <div class="flex items-center gap-2 mb-3">
                                    <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                                    </svg>
                                    <h5 class="text-sm font-semibold text-blue-300">Obecne w PPM</h5>
                                </div>

                                <ul class="text-xs text-gray-300 space-y-1">
                                    @if($selectedConflictProduct['has_default_conflict'])
                                        @forelse($selectedConflictProduct['ppm_default_categories'] as $cat)
                                            <li class="flex items-center gap-2">
                                                {{-- Hierarchical indent spacer --}}
                                                @if(is_array($cat) && isset($cat['level']) && $cat['level'] > 0)
                                                    <div class="category-indent-spacer category-indent-spacer-{{ min($cat['level'], 5) }}"></div>
                                                @endif

                                                <svg class="w-3 h-3 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span class="flex-1">{{ is_array($cat) ? $cat['name'] : $cat }}</span>
                                                <span class="text-xs text-gray-500 italic">domyślne</span>
                                            </li>
                                        @empty
                                            <li class="text-gray-500 italic">(brak domyślnych)</li>
                                        @endforelse
                                    @endif

                                    @if($selectedConflictProduct['has_shop_conflict'])
                                        @forelse($selectedConflictProduct['shop_categories'] as $cat)
                                            <li class="flex items-center gap-2">
                                                {{-- Hierarchical indent spacer --}}
                                                @if(is_array($cat) && isset($cat['level']) && $cat['level'] > 0)
                                                    <div class="category-indent-spacer category-indent-spacer-{{ min($cat['level'], 5) }}"></div>
                                                @endif

                                                <svg class="w-3 h-3 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span class="flex-1">{{ is_array($cat) ? $cat['name'] : $cat }}</span>
                                                <span class="text-xs text-gray-500 italic">sklep</span>
                                            </li>
                                        @empty
                                            <li class="text-gray-500 italic">(brak per-shop)</li>
                                        @endforelse
                                    @endif

                                    @if(!$selectedConflictProduct['has_default_conflict'] && !$selectedConflictProduct['has_shop_conflict'])
                                        <li class="text-gray-500 italic">(brak kategorii)</li>
                                    @endif
                                </ul>
                            </div>

                            <!-- Import Categories -->
                            <div class="p-4 bg-green-900/20 border border-green-500/30 rounded-lg text-left">
                                <div class="flex items-center gap-2 mb-3">
                                    <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                    <h5 class="text-sm font-semibold text-green-300">Z importu PrestaShop</h5>
                                </div>
                                <ul class="text-xs text-gray-300 space-y-1">
                                    @forelse($selectedConflictProduct['import_will_assign_categories'] as $cat)
                                        <li class="flex items-center gap-2">
                                            {{-- Hierarchical indent spacer --}}
                                            @if(is_array($cat) && isset($cat['level']) && $cat['level'] > 0)
                                                <div class="category-indent-spacer category-indent-spacer-{{ min($cat['level'], 5) }}"></div>
                                            @endif

                                            <svg class="w-3 h-3 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="flex-1">{{ is_array($cat) ? $cat['name'] : $cat }}</span>
                                        </li>
                                    @empty
                                        <li class="text-gray-500 italic">(brak)</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Resolution Options -->
                    <div class="px-6 py-6">
                        <h4 class="text-sm font-bold text-gray-200 mb-4">Wybierz sposób rozwiązania konfliktu:</h4>

                        <div class="space-y-3">
                            <!-- Option 1: Overwrite (Replace default categories) -->
                            <div wire:click="selectResolutionOption('overwrite')"
                                 class="p-4 border-2 rounded-lg cursor-pointer transition-all duration-200 {{ $selectedResolution === 'overwrite' ? 'border-orange-500 bg-orange-900/20' : 'border-gray-700 bg-gray-800/30 hover:border-gray-600' }}">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 mt-0.5">
                                        <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center {{ $selectedResolution === 'overwrite' ? 'border-orange-500 bg-orange-500' : 'border-gray-600' }}">
                                            @if($selectedResolution === 'overwrite')
                                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <h5 class="text-sm font-bold text-white mb-1">Zastąp domyślne kategorie</h5>
                                        <p class="text-xs text-gray-400">
                                            Usuń wszystkie obecne kategorie (domyślne i per-shop) i przypisz kategorie z importu jako domyślne dla wszystkich sklepów.
                                        </p>
                                        <div class="mt-2 flex items-center gap-2">
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-500/20 text-red-400">
                                                Destrukcyjne
                                            </span>
                                            <span class="text-xs text-gray-500">Stare kategorie zostaną usunięte</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Option 2: Keep (Keep default, create per-shop override) -->
                            <div wire:click="selectResolutionOption('keep')"
                                 class="p-4 border-2 rounded-lg cursor-pointer transition-all duration-200 {{ $selectedResolution === 'keep' ? 'border-orange-500 bg-orange-900/20' : 'border-gray-700 bg-gray-800/30 hover:border-gray-600' }}">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 mt-0.5">
                                        <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center {{ $selectedResolution === 'keep' ? 'border-orange-500 bg-orange-500' : 'border-gray-600' }}">
                                            @if($selectedResolution === 'keep')
                                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <h5 class="text-sm font-bold text-white mb-1">Zachowaj domyślne, dodaj per-shop</h5>
                                        <p class="text-xs text-gray-400">
                                            Zachowaj obecne domyślne kategorie PPM bez zmian. Utwórz osobne przypisanie kategorii z importu tylko dla sklepu {{ $shopName }}.
                                        </p>
                                        <div class="mt-2 flex items-center gap-2">
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-500/20 text-blue-400">
                                                Bezpieczne
                                            </span>
                                            <span class="text-xs text-gray-500">Zachowuje obecny stan</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Option 3: Manual (Select categories manually) -->
                            <div wire:click="selectResolutionOption('manual')"
                                 class="border-2 rounded-lg cursor-pointer transition-all duration-200 {{ $selectedResolution === 'manual' ? 'border-orange-500 bg-orange-900/20' : 'border-gray-700 bg-gray-800/30 hover:border-gray-600' }}">
                                <div class="p-4">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-shrink-0 mt-0.5">
                                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center {{ $selectedResolution === 'manual' ? 'border-orange-500 bg-orange-500' : 'border-gray-600' }}">
                                                @if($selectedResolution === 'manual')
                                                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <h5 class="text-sm font-bold text-white mb-1">Wybierz kategorie ręcznie</h5>
                                            <p class="text-xs text-gray-400 mb-3">
                                                Wybierz dokładnie kategorie które chcesz przypisać jako domyślne dla tego produktu. Zastąpi obecne domyślne kategorie.
                                            </p>
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-500/20 text-purple-400">
                                                    Pełna kontrola
                                                </span>
                                                <span class="text-xs text-gray-500">Wybierz własne kategorie</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Category Picker (ETAP 2 embedded) -->
                                {{-- FIX 2025-10-15: Removed :shop-id to show ALL PPM categories, not just shop-mapped ones --}}
                                @if($selectedResolution === 'manual')
                                    <div class="px-4 pb-4 border-t border-gray-700/50 pt-4"
                                         wire:key="picker-container-{{ $selectedConflictProduct['product_id'] }}-{{ $modalInstanceId }}"
                                         wire:ignore>
                                        <livewire:products.category-picker
                                            wire:model="manuallySelectedCategories"
                                            context="conflict-resolution"
                                            :key="'conflict-picker-' . $selectedConflictProduct['product_id'] . '-' . $modalInstanceId"
                                        />
                                    </div>
                                @endif
                            </div>

                            <!-- Option 4: Cancel (Skip, no changes) -->
                            <div wire:click="selectResolutionOption('cancel')"
                                 class="p-4 border-2 rounded-lg cursor-pointer transition-all duration-200 {{ $selectedResolution === 'cancel' ? 'border-orange-500 bg-orange-900/20' : 'border-gray-700 bg-gray-800/30 hover:border-gray-600' }}">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 mt-0.5">
                                        <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center {{ $selectedResolution === 'cancel' ? 'border-orange-500 bg-orange-500' : 'border-gray-600' }}">
                                            @if($selectedResolution === 'cancel')
                                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <h5 class="text-sm font-bold text-white mb-1">Pomiń ten produkt</h5>
                                        <p class="text-xs text-gray-400">
                                            Nie wprowadzaj żadnych zmian w kategoriach. Produkt zostanie pominięty podczas importu i nie zostanie zaktualizowany.
                                        </p>
                                        <div class="mt-2 flex items-center gap-2">
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-600/20 text-gray-400">
                                                Brak zmian
                                            </span>
                                            <span class="text-xs text-gray-500">Zachowaj status quo</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 border-t border-gray-700/50 bg-gray-800/30 flex items-center justify-between">
                        <button wire:click="closeConflictResolution"
                                class="px-6 py-2 rounded-lg font-semibold text-sm text-white bg-gray-700 hover:bg-gray-600 transition-colors duration-200">
                            Anuluj
                        </button>

                        <button wire:click="confirmConflictResolution"
                                wire:loading.attr="disabled"
                                wire:target="confirmConflictResolution"
                                @disabled(!$selectedResolution)
                                class="px-6 py-2 rounded-lg font-semibold text-sm text-white bg-orange-600 hover:bg-orange-700 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                            <span wire:loading.remove wire:target="confirmConflictResolution">
                                <svg class="w-5 h-5 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                Potwierdź Rozwiązanie
                            </span>
                            <span wire:loading wire:target="confirmConflictResolution" class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Przetwarzanie...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
