{{-- ETAP_06 FAZA 5.2: Inline Category Select - kompaktowy dropdown z wyszukiwaniem i tworzeniem --}}
{{-- @props: $product, $level (3+), $disabled (bool), $parentCategoryId --}}
{{-- FIX 2025-12-09: Dodano pole wyszukiwania, opcje "Dodaj nowa", naprawiono x-show --}}
@php
    $levelIndex = $level - 3; // 0, 1, 2, 3, 4
    $productCats = $product->category_ids ?? [];

    // Get categories for this level based on parent
    $categories = collect();
    $selectedId = null;

    // Find current selection for this level
    if ($level === 3) {
        // L3 - children of Wszystko (level=1)
        $wszystko = \App\Models\Category::where('level', 1)->where('is_active', true)->first();
        if ($wszystko) {
            $categories = \App\Models\Category::where('parent_id', $wszystko->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }
        // Find if any L3 is selected (level 2 in DB = L3)
        $selectedId = \App\Models\Category::whereIn('id', $productCats)
            ->where('level', 2)
            ->value('id');
    } elseif ($level >= 4 && $parentCategoryId) {
        // L4, L5, L6, L7 - children of parent
        $categories = \App\Models\Category::where('parent_id', $parentCategoryId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $selectedId = \App\Models\Category::whereIn('id', $productCats)
            ->where('parent_id', $parentCategoryId)
            ->value('id');
    }

    $hasOptions = $categories->isNotEmpty();
    // Pozwól otworzyć dropdown nawet gdy brak opcji (żeby można było utworzyć pierwszą kategorię na tym poziomie),
    // o ile istnieje parent (dla L4+).
    $isDisabled = $disabled || ($level > 3 && !$parentCategoryId);
    $selectedCategory = $selectedId ? $categories->firstWhere('id', $selectedId) : null;

    // Level labels for placeholder
    $levelLabel = $level >= 3 ? "Kategoria L{$level}" : "Poziom {$level}";
@endphp

<div class="inline-category-select relative"
     wire:ignore.self
     x-data="{
         open: false,
         search: '',
         showCreateForm: false,
         newCategoryName: '',
         selected: {{ $selectedId ?? 'null' }},
         selectedName: '{{ addslashes($selectedCategory?->name ?? '') }}',
         productId: {{ $product->id }},
         level: {{ $level }},
         parentId: {{ $parentCategoryId ?? 'null' }},
         categories: {{ Js::from($categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name])) }},
         get filteredCategories() {
             if (!this.search.trim()) return this.categories;
             const q = this.search.toLowerCase();
             return this.categories.filter(c => c.name.toLowerCase().includes(q));
         },
         selectCategory(id, name) {
             this.selected = id;
             this.selectedName = name;
             this.open = false;
             this.search = '';
             $wire.setCategoryForLevel(this.productId, this.level, id, this.parentId);
         },
         clearCategory() {
             this.selected = null;
             this.selectedName = '';
             this.open = false;
             this.search = '';
             $wire.setCategoryForLevel(this.productId, this.level, null, this.parentId);
         },
         async createCategory() {
             if (!this.newCategoryName.trim()) return;

             // Call Livewire to create category
             const result = await $wire.createInlineCategory(this.productId, this.level, this.parentId, this.newCategoryName);

             if (result && result.id) {
                 // Add to local list and select
                 this.categories.push({ id: result.id, name: result.name });
                 this.selectCategory(result.id, result.name);
             }

             this.newCategoryName = '';
             this.showCreateForm = false;
         }
     }"
     @keydown.escape.window="if(open) { open = false; search = ''; showCreateForm = false; }"
     @click.outside="open = false">

    {{-- Trigger button --}}
    <button type="button"
            @click="open = !open; if(open) $nextTick(() => $refs.searchInput?.focus())"
            @if($isDisabled) disabled @endif
            class="inline-cat-trigger group flex items-center gap-1 px-2 py-1 rounded text-xs transition-all
                   {{ $isDisabled ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer' }}
                   {{ $selectedId
                      ? 'bg-purple-900/40 text-purple-300 hover:bg-purple-900/60 border border-purple-500/30'
                      : 'bg-gray-700/50 text-gray-400 hover:bg-gray-600/50 border border-gray-600/50' }}">
        {{-- Icon --}}
        <svg class="w-3 h-3 flex-shrink-0 {{ $selectedId ? 'text-purple-400' : 'text-gray-500' }}"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
        </svg>

        {{-- Label --}}
        <span class="truncate max-w-20" x-text="selectedName || 'Dodaj'">
            {{ $selectedCategory?->name ?? 'Dodaj' }}
        </span>

        {{-- Chevron --}}
        <svg class="w-3 h-3 flex-shrink-0 transition-transform"
             :class="{ 'rotate-180': open }"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Dropdown menu - absolute positioning (reliable after modal close) --}}
    {{-- NO x-teleport - breaks Livewire snapshots in child components --}}
    <div x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="inline-cat-dropdown absolute left-0 top-full mt-1 w-56 bg-gray-800 border border-gray-600 rounded-lg shadow-xl"
         style="z-index: 9999;">

        {{-- Search input --}}
        <div class="p-2 border-b border-gray-700">
            <div class="relative">
                <svg class="absolute left-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-500"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text"
                       x-ref="searchInput"
                       x-model="search"
                       placeholder="Szukaj {{ strtolower($levelLabel) }}..."
                       class="w-full pl-7 pr-2 py-1.5 text-xs bg-gray-700 border border-gray-600 rounded
                              text-white placeholder-gray-400 focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
            </div>
        </div>

        {{-- Category options (scrollable) --}}
        <div class="max-h-40 overflow-y-auto">
            {{-- Clear option (if selected) --}}
            <template x-if="selected">
                <button type="button"
                        @click="clearCategory()"
                        class="w-full px-3 py-2 text-left text-xs text-gray-400 hover:bg-gray-700/50
                               border-b border-gray-700 flex items-center gap-2">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Wyczysc wybor
                </button>
            </template>

            {{-- Filtered categories --}}
            <template x-for="cat in filteredCategories" :key="cat.id">
                <button type="button"
                        @click="selectCategory(cat.id, cat.name)"
                        class="w-full px-3 py-2 text-left text-xs transition-colors flex items-center gap-2"
                        :class="selected === cat.id
                            ? 'bg-purple-900/30 text-purple-200'
                            : 'text-gray-300 hover:bg-gray-700/50'">
                    <template x-if="selected === cat.id">
                        <svg class="w-3 h-3 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </template>
                    <template x-if="selected !== cat.id">
                        <span class="w-3"></span>
                    </template>
                    <span class="truncate" x-text="cat.name"></span>
                </button>
            </template>

            {{-- No results --}}
            <template x-if="filteredCategories.length === 0 && search.trim()">
                <div class="px-3 py-2 text-xs text-gray-500 italic">
                    Brak wynikow dla "<span x-text="search"></span>"
                </div>
            </template>

            {{-- Empty state (no categories at all) --}}
            <template x-if="categories.length === 0 && !search.trim()">
                <div class="px-3 py-2 text-xs text-gray-500 italic">
                    {{ $level === 3 ? 'Brak kategorii' : ($parentCategoryId ? 'Brak kategorii na tym poziomie' : 'Wybierz wyzszy poziom') }}
                </div>
            </template>
        </div>

        {{-- Create new category section --}}
        @if($level === 3 || $parentCategoryId)
            <div class="border-t border-gray-700">
                {{-- Toggle create form button --}}
                <button type="button"
                        x-show="!showCreateForm"
                        @click="showCreateForm = true; $nextTick(() => $refs.newCatInput?.focus())"
                        class="w-full px-3 py-2 text-left text-xs text-green-400 hover:bg-green-900/20
                               flex items-center gap-2 transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Dodaj nowa kategorie
                </button>

                {{-- Create form --}}
                <div x-show="showCreateForm"
                     x-cloak
                     class="p-2 bg-gray-750">
                    <div class="flex items-center gap-2">
                        <input type="text"
                               x-ref="newCatInput"
                               x-model="newCategoryName"
                               @keydown.enter="createCategory()"
                               @keydown.escape="showCreateForm = false; newCategoryName = ''"
                               placeholder="Nazwa nowej kategorii..."
                               class="flex-1 px-2 py-1.5 text-xs bg-gray-700 border border-gray-600 rounded
                                      text-white placeholder-gray-400 focus:border-green-500 focus:ring-1 focus:ring-green-500">
                        <button type="button"
                                @click="createCategory()"
                                :disabled="!newCategoryName.trim()"
                                class="p-1.5 bg-green-600 hover:bg-green-700 text-white rounded transition-colors
                                       disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                        <button type="button"
                                @click="showCreateForm = false; newCategoryName = ''"
                                class="p-1.5 bg-gray-600 hover:bg-gray-500 text-gray-300 rounded transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
