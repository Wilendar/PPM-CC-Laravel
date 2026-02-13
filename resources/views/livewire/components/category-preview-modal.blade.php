{{-- CategoryPreviewModal Component - ETAP_07 FAZA 3D --}}
{{-- Enterprise category import preview system --}}
{{-- LIVEWIRE ROOT: Transparent wrapper (no stacking context) --}}
<div>
    {{-- Preview Modal --}}
    <div x-data="{ isOpen: @entangle('isOpen'), activeTab: @entangle('activeTab') }"
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
         class="fixed inset-0 bg-black/70 backdrop-blur-sm transition-opacity"></div>

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
                            Podglad Kategorii do Zaimportowania
                        </h3>
                        <p class="text-sm text-gray-300 mt-1 flex items-center gap-3">
                            <span>Sklep: <strong class="text-brand-400">{{ $shopName }}</strong> | Znaleziono: <strong class="text-white">{{ $totalCount }}</strong> {{ $totalCount === 1 ? 'kategoria' : ($totalCount < 5 ? 'kategorie' : 'kategorii') }}</span>
                            @if(count($detectedConflicts) > 0)
                                <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-orange-500/20 border border-orange-500/30 text-orange-400">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ count($detectedConflicts) }} {{ count($detectedConflicts) === 1 ? 'konflikt' : 'konfliktow' }}
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

            {{-- Tab Bar --}}
            <div class="px-6 py-2 bg-gray-800/50 border-b border-gray-700/50">
                <div class="flex gap-1 bg-gray-900/50 rounded-lg p-1">
                    <button @click="activeTab = 'categories'; $wire.setActiveTab('categories')"
                            :class="activeTab === 'categories' ? 'category-preview-tab category-preview-tab--active' : 'category-preview-tab category-preview-tab--inactive'"
                            class="flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        Drzewko Kategorii
                    </button>
                    <button @click="activeTab = 'product_types'; $wire.setActiveTab('product_types')"
                            :class="activeTab === 'product_types' ? 'category-preview-tab category-preview-tab--active' : 'category-preview-tab category-preview-tab--inactive'"
                            class="flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        Typy Produktow
                        @php $typesTotal = $this->productsWithDetectedTypes['total'] ?? 0; @endphp
                        @if($typesTotal > 0)
                            <span class="ml-1.5 px-1.5 py-0.5 text-xs rounded-full bg-gray-700 text-gray-300">
                                {{ $typesTotal }}
                            </span>
                        @endif
                    </button>
                </div>
            </div>

            {{-- ========== TAB 1: Categories ========== --}}
            <div x-show="activeTab === 'categories'">

                {{-- Conflicts Toggle Section (only if conflicts detected) --}}
                @if(count($detectedConflicts) > 0)
                    <div class="px-6 py-2 bg-gray-800/30 border-b border-gray-700/50" x-data="{ showConflicts: @entangle('showConflicts') }">
                        <button @click="showConflicts = !showConflicts"
                                class="w-full flex items-center justify-between py-1 text-sm text-orange-400 hover:text-orange-300 transition-colors">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="font-medium">{{ count($detectedConflicts) }} {{ count($detectedConflicts) === 1 ? 'konflikt kategorii' : 'konfliktow kategorii' }}</span>
                            </span>
                            <svg class="w-4 h-4 transition-transform duration-200" :class="showConflicts ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="showConflicts" x-collapse class="mt-2 pb-2">
                            <div class="space-y-2 max-h-48 overflow-y-auto">
                                @foreach($detectedConflicts as $index => $conflict)
                                    <div wire:key="conflict-{{ $conflict['product_id'] }}"
                                         class="p-2.5 bg-gray-800/40 border border-gray-700/50 rounded-lg hover:bg-gray-800/60 transition-colors">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="flex-1 min-w-0">
                                                <span class="text-sm font-medium text-white truncate">{{ $conflict['name'] }}</span>
                                                <span class="text-xs text-gray-400 ml-1">({{ $conflict['sku'] }})</span>
                                            </div>
                                            <button wire:click="openConflictResolution({{ $conflict['product_id'] }})"
                                                    class="px-2 py-1 text-xs font-semibold text-white bg-orange-600 hover:bg-orange-700 rounded transition-colors duration-200 flex-shrink-0">
                                                Rozwiaz
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Full PPM Tree with merged missing categories -->
                <div class="px-6 py-4 overflow-y-auto"
                     x-data="{ scrollToCategory(categoryId) {
                         $nextTick(() => {
                             const element = document.getElementById('category-ppm-' + categoryId);
                             if (element) {
                                 element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                 element.classList.add('bg-brand-900/40', 'ring-2', 'ring-brand-500');
                                 setTimeout(() => {
                                     element.classList.remove('bg-brand-900/40', 'ring-2', 'ring-brand-500');
                                 }, 2000);
                             }
                         });
                     }}"
                     @category-created.window="scrollToCategory($event.detail.categoryId)"
                     class="max-h-[50vh]">

                    @if(!empty($this->fullPpmTree))
                        {{-- Summary counters --}}
                        @php
                            $ppmTotal = 0;
                            $newToAdd = count($selectedCategoryIds);
                            $countNodes = function($nodes) use (&$countNodes, &$ppmTotal) {
                                foreach ($nodes as $n) {
                                    $ppmTotal++;
                                    if (!empty($n['children'])) $countNodes($n['children']);
                                }
                            };
                            $countNodes($this->fullPpmTree);
                        @endphp
                        <div class="mb-3 flex items-center gap-4 text-sm text-gray-400">
                            <span>Kategorie PPM: <strong class="text-white">{{ $ppmTotal }}</strong></span>
                            @if($newToAdd > 0)
                                <span class="text-emerald-400">Nowe do dodania: <strong>{{ $newToAdd }}</strong></span>
                            @endif
                            <span class="ml-auto">Wybrano: <strong class="text-brand-400">{{ count($selectedCategoryIds) }}</strong> / {{ $totalCount }}</span>
                        </div>

                        @foreach($this->fullPpmTree as $node)
                            @include('livewire.components.partials.category-preview-tree-node', [
                                'node' => $node,
                                'level' => 0,
                                'expandedNodes' => $expandedNodes,
                                'selectedCategoryIds' => $selectedCategoryIds,
                                'skipCategories' => $skipCategories,
                            ])
                        @endforeach
                    @else
                        {{-- Empty state --}}
                        <div class="text-center py-8 text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                            <p>Brak kategorii do wyswietlenia</p>
                        </div>
                    @endif
                </div>

            </div>{{-- end Tab 1 --}}

            {{-- ========== TAB 2: Product Types ========== --}}
            <div x-show="activeTab === 'product_types'" x-cloak>
                <div class="px-6 py-4">
                    @php $typesData = $this->productsWithDetectedTypes; @endphp
                    @if(!empty($typesData['data']))
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-gray-700">
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">SKU</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Nazwa</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Wykryty Typ</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-700/50">
                                    @foreach($typesData['data'] as $product)
                                    <tr class="hover:bg-gray-700/30 transition-colors" wire:key="type-{{ $loop->index }}">
                                        <td class="px-4 py-2.5 text-sm text-gray-300 font-mono">{{ $product['sku'] }}</td>
                                        <td class="px-4 py-2.5 text-sm text-white">{{ Str::limit($product['name'], 60) }}</td>
                                        <td class="px-4 py-2.5">
                                            <span class="category-preview-type-badge category-preview-type-badge--{{ $product['type_color'] }}">
                                                {{ $product['detected_type'] }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination --}}
                        @if($typesData['last_page'] > 1)
                        <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-700/50">
                            <span class="text-sm text-gray-400">
                                Strona {{ $typesData['current_page'] }} z {{ $typesData['last_page'] }}
                                ({{ $typesData['total'] }} produktow)
                            </span>
                            <div class="flex gap-1">
                                <button wire:click="setProductTypesPage({{ $typesData['current_page'] - 1 }})"
                                        @disabled($typesData['current_page'] <= 1)
                                        class="px-3 py-1 text-sm rounded bg-gray-700 text-gray-300 hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Poprzednia
                                </button>
                                <button wire:click="setProductTypesPage({{ $typesData['current_page'] + 1 }})"
                                        @disabled($typesData['current_page'] >= $typesData['last_page'])
                                        class="px-3 py-1 text-sm rounded bg-gray-700 text-gray-300 hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Nastepna
                                </button>
                            </div>
                        </div>
                        @endif
                    @else
                        <div class="text-center py-8 text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            <p>Brak danych o typach produktow</p>
                            <p class="text-sm text-gray-500 mt-1">Typy produktow sa wykrywane automatycznie na podstawie kategorii</p>
                        </div>
                    @endif
                </div>
            </div>{{-- end Tab 2 --}}

            {{-- Modal Footer --}}
            <div class="px-6 py-4 border-t border-gray-700/50 bg-gray-800/30">
                <div class="flex items-center justify-between">
                    <button wire:click="close"
                            class="btn-enterprise-secondary">
                        Anuluj Import
                    </button>

                    <div class="flex items-center gap-3">
                        {{-- Import without categories button --}}
                        <button wire:click="importWithoutCategories"
                                class="px-4 py-2 text-sm font-medium rounded-lg border border-amber-500/30 text-amber-400 hover:bg-amber-500/10 transition-colors">
                            Importuj BEZ kategorii
                        </button>

                        {{-- Main CTA --}}
                        <button wire:click="approve"
                                wire:loading.attr="disabled"
                                class="btn-enterprise-primary">
                            <span wire:loading.remove wire:target="approve">
                                Utworz Kategorie i Importuj ({{ count($selectedCategoryIds) }})
                            </span>
                            <span wire:loading wire:target="approve">
                                <svg class="animate-spin h-4 w-4 inline-block mr-1" fill="none" viewBox="0 0 24 24">
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
    </div>

    {{-- Warning: Products without categories --}}
    @if($showNoCategoryWarning)
    <div class="fixed inset-0 layer-overlay bg-black/80 flex items-center justify-center" x-data x-transition>
        <div class="bg-gray-800 rounded-xl border border-amber-500/30 max-w-md p-6 shadow-2xl">
            <div class="flex items-start gap-3 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-amber-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-lg font-bold text-amber-400">Uwaga!</h4>
                    <p class="text-gray-300 mt-2 text-sm">
                        <strong class="text-white">{{ $productsWithoutCategoriesCount }}</strong> produktow nie posiada zadnych kategorii w PPM.
                        Kontynuacja bez kategorii moze utrudnic zarzadzanie produktami.
                    </p>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button wire:click="dismissNoCategoryWarning"
                        class="btn-enterprise-secondary">
                    Anuluj
                </button>
                <button wire:click="confirmImportWithoutCategories"
                        class="btn-enterprise-danger">
                    Kontynuuj bez kategorii
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ETAP 4: Quick Category Creator Form --}}
    @if($showCreateForm)
    <div class="fixed inset-0 layer-overlay flex items-center justify-center bg-black/90 backdrop-blur-md"
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
                        Utworz nowa kategorie
                    </h4>
                    <p class="text-xs text-gray-400 mt-1">Wypelnij formularz aby utworzyc nowa kategorie w PPM</p>
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
                               placeholder="np. Elektronika, Odziez, Akcesoria..."
                               class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                        @error('newCategoryForm.name')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Parent Category -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Kategoria nadrzedna (opcjonalnie)
                        </label>
                        <select wire:model.live="newCategoryForm.parent_id"
                                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                            <option value="">-- Brak (kategoria glowna) --</option>
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
                                  placeholder="Krotki opis kategorii..."
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
                            Utworz kategorie
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

    {{-- ETAP 3.4: Conflict Resolution Modal --}}
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
                                    Rozwiazywanie Konfliktu Kategorii
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
                        <h4 class="text-sm font-bold text-gray-200 mb-3">Porownanie Kategorii</h4>
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
                                                @if(is_array($cat) && isset($cat['level']) && $cat['level'] > 0)
                                                    <div class="category-indent-spacer category-indent-spacer-{{ min($cat['level'], 5) }}"></div>
                                                @endif
                                                <svg class="w-3 h-3 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span class="flex-1">{{ is_array($cat) ? $cat['name'] : $cat }}</span>
                                                <span class="text-xs text-gray-500 italic">domyslne</span>
                                            </li>
                                        @empty
                                            <li class="text-gray-500 italic">(brak domyslnych)</li>
                                        @endforelse
                                    @endif
                                    @if($selectedConflictProduct['has_shop_conflict'])
                                        @forelse($selectedConflictProduct['shop_categories'] as $cat)
                                            <li class="flex items-center gap-2">
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
                        <h4 class="text-sm font-bold text-gray-200 mb-4">Wybierz sposob rozwiazania konfliktu:</h4>

                        <div class="space-y-3">
                            <!-- Option 1: Overwrite -->
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
                                        <h5 class="text-sm font-bold text-white mb-1">Zastap domyslne kategorie</h5>
                                        <p class="text-xs text-gray-400">
                                            Usun wszystkie obecne kategorie (domyslne i per-shop) i przypisz kategorie z importu jako domyslne dla wszystkich sklepow.
                                        </p>
                                        <div class="mt-2 flex items-center gap-2">
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-500/20 text-red-400">Destrukcyjne</span>
                                            <span class="text-xs text-gray-500">Stare kategorie zostana usuniete</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Option 2: Keep -->
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
                                        <h5 class="text-sm font-bold text-white mb-1">Zachowaj domyslne, dodaj per-shop</h5>
                                        <p class="text-xs text-gray-400">
                                            Zachowaj obecne domyslne kategorie PPM bez zmian. Utworz osobne przypisanie kategorii z importu tylko dla sklepu {{ $shopName }}.
                                        </p>
                                        <div class="mt-2 flex items-center gap-2">
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-500/20 text-blue-400">Bezpieczne</span>
                                            <span class="text-xs text-gray-500">Zachowuje obecny stan</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Option 3: Manual -->
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
                                            <h5 class="text-sm font-bold text-white mb-1">Wybierz kategorie recznie</h5>
                                            <p class="text-xs text-gray-400 mb-3">
                                                Wybierz dokladnie kategorie ktore chcesz przypisac jako domyslne dla tego produktu. Zastapi obecne domyslne kategorie.
                                            </p>
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-500/20 text-purple-400">Pelna kontrola</span>
                                                <span class="text-xs text-gray-500">Wybierz wlasne kategorie</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Category Picker (ETAP 2 embedded) -->
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

                            <!-- Option 4: Cancel -->
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
                                        <h5 class="text-sm font-bold text-white mb-1">Pomin ten produkt</h5>
                                        <p class="text-xs text-gray-400">
                                            Nie wprowadzaj zadnych zmian w kategoriach. Produkt zostanie pominiety podczas importu i nie zostanie zaktualizowany.
                                        </p>
                                        <div class="mt-2 flex items-center gap-2">
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-600/20 text-gray-400">Brak zmian</span>
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
                                Potwierdz Rozwiazanie
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
