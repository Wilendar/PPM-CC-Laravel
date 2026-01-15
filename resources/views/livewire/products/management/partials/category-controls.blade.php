{{--
    resources/views/livewire/products/management/partials/category-controls.blade.php

    ETAP_07b FAZA 4.2: Category Controls Component
    - Search input (real-time filtering)
    - Expand/Collapse all buttons
    - Clear selection button

    Works in BOTH Default TAB and Shop TAB contexts.

    @param string|null $context - 'default' or shop_id
--}}
@props([
    'context' => 'default',
    {{-- FAZA 4.2.3: showCreateButton removed - inline creation via + buttons in tree --}}
])

{{-- FIX 2025-11-26: Added context as Alpine property to survive Livewire morphing --}}
{{-- Problem: Hardcoded context in functions wasn't updated after morphing between tabs --}}
{{-- FIX 2025-11-26 v2: wire:key forces re-init on TAB change, wire:ignore.self prevents poll reset --}}
{{-- Root Cause: wire:poll.5s="checkJobStatus" causes re-render every 5s, resetting allExpanded --}}
{{-- Solution: wire:key changes ONLY when context changes (tab switch), wire:ignore.self preserves Alpine state during poll --}}
{{-- 2025-11-26 v3: Added disabled state during PrestaShop job (freeze controls) --}}
<div class="category-controls-wrapper mb-4"
     wire:key="category-controls-{{ $context }}"
     wire:ignore.self
     :class="$wire.categoryEditingDisabled ? 'category-controls-disabled' : ''"
     x-data="{
         searchQuery: '',
         allExpanded: false,
         showClearConfirm: false,
         context: '{{ $context }}',

         // PERFORMANCE FIX 2025-11-27: Use consolidated event (86% listener reduction)
         // Toggle all categories expand/collapse
         toggleAllExpanded() {
             this.allExpanded = !this.allExpanded;
             window.dispatchEvent(new CustomEvent('category-event', {
                 detail: { type: 'toggle-all', expanded: this.allExpanded, context: this.context }
             }));
         },

         // Clear search
         clearSearch() {
             this.searchQuery = '';
             window.dispatchEvent(new CustomEvent('category-event', {
                 detail: { type: 'search', query: '', context: this.context }
             }));
         },

         // Handle search input
         handleSearch() {
             window.dispatchEvent(new CustomEvent('category-event', {
                 detail: { type: 'search', query: this.searchQuery, context: this.context }
             }));
         },

         // Clear selection (with confirmation for shop context)
         clearSelection() {
             @if($context !== 'default')
                 if (!this.showClearConfirm) {
                     this.showClearConfirm = true;
                     return;
                 }
             @endif
             $wire.clearCategorySelection(this.context);
             this.showClearConfirm = false;
         },

         cancelClear() {
             this.showClearConfirm = false;
         }
     }">

    {{-- Controls Row --}}
    <div class="flex flex-wrap items-center gap-3">

        {{-- Search Input --}}
        <div class="flex-1 min-w-[200px] relative">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    x-model="searchQuery"
                    @input.debounce.300ms="handleSearch()"
                    placeholder="Szukaj kategorii..."
                    class="w-full pl-10 pr-8 py-2 text-sm rounded-lg border border-gray-600 bg-gray-700 text-white placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="$wire.categoryEditingDisabled"
                >
                {{-- Clear search button --}}
                <button
                    x-show="searchQuery.length > 0"
                    x-transition
                    @click="clearSearch()"
                    type="button"
                    class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-white"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Expand/Collapse Buttons --}}
        <div class="flex items-center gap-1">
            <button
                type="button"
                @click="toggleAllExpanded()"
                class="category-control-btn inline-flex items-center px-3 py-2 text-xs font-medium rounded-lg border border-gray-600 bg-gray-700 text-gray-300 hover:bg-gray-600 hover:text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                :title="allExpanded ? 'Zwiń wszystkie' : 'Rozwiń wszystkie'"
                :disabled="$wire.categoryEditingDisabled"
            >
                <svg x-show="!allExpanded" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
                <svg x-show="allExpanded" x-cloak class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                </svg>
                <span x-text="allExpanded ? 'Zwiń' : 'Rozwiń'"></span>
            </button>
        </div>

        {{-- Clear Selection Button --}}
        <div class="relative" x-data>
            <button
                type="button"
                @click="clearSelection()"
                x-show="!showClearConfirm"
                class="category-control-btn inline-flex items-center px-3 py-2 text-xs font-medium rounded-lg border border-red-600/50 bg-red-900/20 text-red-300 hover:bg-red-900/40 hover:text-red-200 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                title="Odznacz wszystkie kategorie"
                :disabled="$wire.categoryEditingDisabled"
            >
                {{-- 2025-11-26: Changed from trash icon to X-circle (more appropriate for "uncheck") --}}
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Odznacz
            </button>

            {{-- Confirmation for Shop Context --}}
            @if($context !== 'default')
                <div
                    x-show="showClearConfirm"
                    x-transition
                    class="absolute right-0 top-full mt-1 p-3 bg-gray-800 border border-red-500/50 rounded-lg shadow-xl z-50 min-w-[220px]"
                >
                    <p class="text-xs text-gray-300 mb-2">
                        <svg class="w-4 h-4 inline mr-1 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        Usunąć wszystkie kategorie specyficzne dla sklepu?
                    </p>
                    <div class="flex gap-2">
                        <button
                            @click="clearSelection()"
                            type="button"
                            class="flex-1 px-2 py-1 text-xs bg-red-600 hover:bg-red-700 text-white rounded"
                        >
                            Tak, usuń
                        </button>
                        <button
                            @click="cancelClear()"
                            type="button"
                            class="flex-1 px-2 py-1 text-xs bg-gray-600 hover:bg-gray-700 text-white rounded"
                        >
                            Anuluj
                        </button>
                    </div>
                </div>
            @endif
        </div>

        {{-- FAZA 4.2.3: "Nowa" button removed - inline creation via + buttons in tree --}}
    </div>

    {{-- Search Results Info --}}
    <div x-show="searchQuery.length > 0" x-transition class="mt-2">
        <p class="text-xs text-gray-400">
            <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Wyszukiwanie: "<span x-text="searchQuery" class="font-medium text-blue-400"></span>"
            <button @click="clearSearch()" class="ml-2 text-blue-400 hover:text-blue-300 underline">
                Wyczyść
            </button>
        </p>
    </div>
</div>
