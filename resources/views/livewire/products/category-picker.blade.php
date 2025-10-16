{{-- CategoryPicker Component - ETAP_07 FAZA 3D - ETAP 2 - DEEP FIX 2025-10-15 --}}
{{-- Hierarchical category tree picker with multi-select --}}
{{-- CRITICAL FIX: Pass selectedCategories to Alpine.js to avoid Livewire lifecycle in nested Blade components --}}
<div class="category-picker-container"
     wire:key="picker-{{ $context }}"
     x-data="{ selectedCategories: @js($selectedCategories) }">
    <!-- Search & Filters Header -->
    <div class="category-picker-header">
        <div class="flex items-center justify-between gap-4 mb-4">
            <!-- Search Input -->
            <div class="flex-1">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Szukaj kategorii..."
                           class="category-picker-search">
                    @if(!empty($search))
                        <button wire:click="clearSearch"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2">
                <button @click="$dispatch('create-category-requested')"
                        class="px-3 py-1.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors duration-200 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Nowa</span>
                </button>
                <button wire:click="selectAll"
                        class="category-picker-btn-secondary">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    Wszystkie
                </button>
                <button wire:click="deselectAll"
                        class="category-picker-btn-secondary">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                    Wyczyść
                </button>
            </div>
        </div>

        <!-- Filter Toggle & Stats -->
        <div class="flex items-center justify-between gap-4 pb-3 border-b border-gray-700/50">
            <label class="flex items-center gap-2 cursor-pointer group">
                <input type="checkbox"
                       wire:model.live="showOnlySelected"
                       class="category-picker-checkbox">
                <span class="text-sm text-gray-300 group-hover:text-white transition-colors">
                    Pokaż tylko wybrane
                </span>
            </label>

            <div class="text-sm text-gray-400">
                <span>Wybrano: <strong class="text-brand-400">{{ $selectedCount }}</strong></span>
                <span class="mx-2">|</span>
                <span>Widoczne: <strong class="text-white">{{ $visibleCount }}</strong></span>
            </div>
        </div>
    </div>

    <!-- Category Tree -->
    <div class="category-picker-tree">
        @if(empty($categoryTree))
            <div class="category-picker-empty">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="text-gray-400 text-sm">
                    @if(!empty($search))
                        Brak kategorii pasujących do wyszukiwania "{{ $search }}"
                    @elseif($showOnlySelected)
                        Brak wybranych kategorii
                    @else
                        Brak dostępnych kategorii
                    @endif
                </p>
            </div>
        @else
            <div class="space-y-1">
                @foreach($categoryTree as $category)
                    <x-category-picker-node
                        :category="$category"
                        :context="$context"
                        x-bind:selected-categories="selectedCategories"
                    />
                @endforeach
            </div>
        @endif
    </div>
</div>
