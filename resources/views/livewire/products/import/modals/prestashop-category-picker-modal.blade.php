{{-- PrestaShop Category Picker Modal - FAZA 9.7b Feature #8 --}}
<div>
@if($isOpen && $shopId)
<div class="fixed inset-0 overflow-y-auto import-category-picker-modal-overlay" @keydown.escape.window="$wire.close()">
    {{-- Overlay --}}
    <div class="fixed inset-0 bg-black/70 transition-opacity" @click="$wire.close()"></div>

    {{-- Modal Panel --}}
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-2xl bg-gray-800 rounded-xl shadow-2xl border border-gray-700 transform transition-all"
             @click.stop>

            {{-- Header --}}
            <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-purple-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-white">Kategorie PrestaShop</h3>
                        <p class="text-sm text-gray-400">{{ $shopName }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    {{-- Refresh button --}}
                    <button type="button"
                            wire:click="refreshCategories"
                            wire:loading.attr="disabled"
                            class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
                            title="Odswiez drzewko kategorii">
                        <svg class="w-5 h-5 {{ $isLoading ? 'animate-spin' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>

                    {{-- Close button --}}
                    <button type="button"
                            wire:click="close"
                            class="text-gray-400 hover:text-white transition-colors p-2 hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Search --}}
            <div class="px-6 py-3 border-b border-gray-700/50">
                <div class="relative">
                    <input type="text"
                           wire:model.live.debounce.300ms="searchQuery"
                           class="w-full pl-10 pr-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-sm text-white placeholder-gray-400 focus:ring-purple-500 focus:border-purple-500"
                           placeholder="Szukaj kategorii...">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            {{-- Content: Category Tree --}}
            <div class="px-6 py-4 max-h-[50vh] overflow-y-auto">
                @php
                    $tree = $this->categoryTree;
                @endphp

                @if(empty($tree))
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-gray-500">Brak kategorii do wyswietlenia</p>
                        <p class="text-xs text-gray-600 mt-1">Kliknij przycisk odswiezania aby pobrac kategorie</p>
                    </div>
                @else
                    {{-- Search mode: flat list --}}
                    @if(!empty($searchQuery))
                        @php
                            $filtered = $this->filterCategoriesRecursive($tree, strtolower($searchQuery));
                        @endphp
                        @if(empty($filtered))
                            <p class="text-center text-gray-500 py-4">Brak wynikow dla "{{ $searchQuery }}"</p>
                        @else
                            <div class="space-y-1">
                                @foreach($filtered as $cat)
                                    @include('livewire.products.import.modals.partials.ps-category-item', ['category' => $cat, 'depth' => 0])
                                @endforeach
                            </div>
                        @endif
                    @else
                        {{-- Tree mode: hierarchical --}}
                        <div class="space-y-1">
                            @foreach($tree as $cat)
                                @include('livewire.products.import.modals.partials.ps-category-tree-item', ['category' => $cat, 'depth' => 0])
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-700 flex items-center justify-between">
                <div class="text-sm text-gray-400">
                    @if(count($selectedCategoryIds) > 0)
                        <span class="text-purple-400 font-medium">{{ count($selectedCategoryIds) }}</span> kategorii wybranych
                    @else
                        Wybierz kategorie dla tego sklepu
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <button type="button"
                            wire:click="close"
                            class="px-4 py-2 text-sm font-medium rounded-lg bg-gray-700 text-gray-300 hover:bg-gray-600 transition-colors">
                        Anuluj
                    </button>
                    <button type="button"
                            wire:click="save"
                            class="px-4 py-2 text-sm font-medium rounded-lg bg-purple-600 hover:bg-purple-700 text-white transition-colors inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Zapisz ({{ count($selectedCategoryIds) }})
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
</div>
