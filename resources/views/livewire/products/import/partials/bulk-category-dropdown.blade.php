{{-- ETAP_06: Bulk Category Dropdown dla naglowka tabeli --}}
{{-- @props: $level (3, 4, 5) --}}
{{-- FIX 2025-12-10: Dodano wyszukiwanie, wyczysc wybor, dodaj kategorie --}}
@php
    // Get categories for this level
    $categories = collect();
    if ($level === 3) {
        $wszystko = \App\Models\Category::where('level', 1)->where('is_active', true)->first();
        if ($wszystko) {
            $categories = \App\Models\Category::where('parent_id', $wszystko->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }
    }
    // For L4/L5, bulk dropdown shows all categories at that level (simplified)
    elseif ($level === 4) {
        $categories = \App\Models\Category::where('level', 3)
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(50)
            ->get();
    }
    elseif ($level === 5) {
        $categories = \App\Models\Category::where('level', 4)
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(50)
            ->get();
    }

    $levelLabels = [
        3 => 'glowna',
        4 => 'podkategorie',
        5 => 'szczegolowa',
    ];
    $levelLabel = $levelLabels[$level] ?? "poziom $level";
@endphp

<div class="bulk-cat-dropdown relative"
     x-data="{
         open: false,
         search: '',
         showCreateForm: false,
         newCategoryName: '',
         level: {{ $level }},
         categories: {{ Js::from($categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name])) }},
         btnRect: null,
         updatePosition() {
             this.btnRect = this.$refs.btn.getBoundingClientRect();
         },
         get filteredCategories() {
             if (!this.search.trim()) return this.categories;
             const q = this.search.toLowerCase();
             return this.categories.filter(c => c.name.toLowerCase().includes(q));
         },
         selectCategory(id) {
             this.open = false;
             this.search = '';
             $wire.bulkSetCategoryLevel(this.level, id);
         },
         clearAll() {
             this.open = false;
             this.search = '';
             $wire.bulkClearCategoryLevel(this.level);
         },
         async createCategory() {
             if (!this.newCategoryName.trim()) return;

             // Get parent ID based on level
             let parentId = null;
             if (this.level === 3) {
                 // Parent is Wszystko (level=1)
                 parentId = await $wire.getWszystkoId();
             }
             // For L4/L5 bulk create is more complex - skip for now

             if (parentId) {
                 const result = await $wire.createBulkCategory(this.level, parentId, this.newCategoryName);
                 if (result && result.id) {
                     this.categories.push({ id: result.id, name: result.name });
                     this.selectCategory(result.id);
                 }
             }

             this.newCategoryName = '';
             this.showCreateForm = false;
         }
     }"
     @click.outside="open = false; search = ''; showCreateForm = false"
     @keydown.escape.window="if(open) { open = false; search = ''; showCreateForm = false; }">

    <button type="button"
            x-ref="btn"
            @click="updatePosition(); open = !open; if(open) $nextTick(() => $refs.searchInput?.focus())"
            class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] rounded
                   bg-blue-900/40 text-blue-300 hover:bg-blue-900/60 border border-blue-500/30">
        <span>Ustaw</span>
        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <template x-teleport="body">
        <div x-show="open"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             @click.outside="open = false; search = ''; showCreateForm = false"
             class="fixed z-[9999] w-56 bg-gray-800 border border-gray-600 rounded-lg shadow-xl"
             :style="btnRect ? `top: ${btnRect.bottom + 4}px; left: ${btnRect.left}px;` : ''"
             style="display: none;">

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
                           placeholder="Szukaj kategoria {{ $levelLabel }}..."
                           class="w-full pl-7 pr-2 py-1.5 text-xs bg-gray-700 border border-gray-600 rounded
                                  text-white placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
            </div>

            {{-- Category options (scrollable) --}}
            <div class="max-h-40 overflow-y-auto">
                {{-- Clear all option --}}
                <button type="button"
                        @click="clearAll()"
                        class="w-full px-3 py-2 text-left text-xs text-gray-400 hover:bg-gray-700/50
                               border-b border-gray-700 flex items-center gap-2">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Wyczysc wybor
                </button>

                {{-- Filtered categories --}}
                <template x-for="cat in filteredCategories" :key="cat.id">
                    <button type="button"
                            @click="selectCategory(cat.id)"
                            class="w-full px-3 py-2 text-left text-xs text-gray-300 hover:bg-gray-700/50 flex items-center gap-2">
                        <span class="w-3"></span>
                        <span class="truncate" x-text="cat.name"></span>
                    </button>
                </template>

                {{-- No results --}}
                <template x-if="filteredCategories.length === 0 && search.trim()">
                    <div class="px-3 py-2 text-xs text-gray-500 italic">
                        Brak wynikow dla "<span x-text="search"></span>"
                    </div>
                </template>

                {{-- Empty state --}}
                <template x-if="categories.length === 0 && !search.trim()">
                    <div class="px-3 py-2 text-xs text-gray-500 italic">
                        Brak kategorii
                    </div>
                </template>
            </div>

            {{-- Create new category section (only for L3) --}}
            @if($level === 3)
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
    </template>
</div>
