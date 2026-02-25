{{-- MODE: Category --}}
<div>
    @if(empty($prestashopCategories))
        <div class="text-center py-8">
            {{-- Loading spinner - shows during API call --}}
            <div wire:loading wire:target="setImportShop,updatedImportShopId,loadPrestaShopCategories">
                <svg class="animate-spin h-8 w-8 mx-auto text-orange-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-2 text-gray-400">Ładowanie kategorii z PrestaShop...</p>
            </div>

            {{-- Empty state - shows when not loading and no categories --}}
            <div wire:loading.remove wire:target="setImportShop,updatedImportShopId,loadPrestaShopCategories">
                <p class="text-gray-400 text-sm">
                    <svg class="w-4 h-4 inline-block mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Kategorie zostaną załadowane automatycznie po wybrze sklepu
                </p>
            </div>
        </div>
    @else
        <div class="mb-4">
            <label class="flex items-center text-sm text-gray-300">
                <input type="checkbox" wire:model.live="importIncludeSubcategories"
                       class="form-checkbox mr-2 text-orange-500">
                Uwzględnij podkategorie
            </label>
        </div>

        {{-- ALPINE.JS OPTIMIZED: Client-side expand/collapse with skeleton loaders --}}
        <div class="border border-gray-600 rounded-lg max-h-64 overflow-y-auto p-4"
             x-data="{
                 expanded: $wire.entangle('expandedCategories'),
                 loading: null,
                 skeletonCount: 3,
                 toggleExpand(categoryId) {
                     const idx = this.expanded.indexOf(categoryId);
                     if (idx !== -1) {
                         // Collapse - INSTANT (no server call)
                         this.expanded.splice(idx, 1);
                     } else {
                         // Expand - show skeleton loaders, then fetch
                         this.loading = categoryId;
                         this.expanded.push(categoryId); // Show container immediately

                         $wire.fetchCategoryChildren(categoryId).then(() => {
                             // CRITICAL FIX: Wait for Livewire DOM update before hiding skeleton
                             // Livewire re-render (~235KB template) + DOM injection takes time
                             // 100ms was too fast - skeleton disappeared before children appeared
                             // Now using Livewire.hook('morph.updated') or longer timeout
                             this.$nextTick(() => {
                                 setTimeout(() => this.loading = null, 300); // Wait for DOM update
                             });
                         }).catch(() => {
                             this.loading = null;
                             this.expanded.splice(this.expanded.indexOf(categoryId), 1); // Collapse on error
                         });
                     }
                 },
                 isExpanded(categoryId) {
                     return this.expanded.includes(categoryId);
                 },
                 isLoading(categoryId) {
                     return this.loading === categoryId;
                 }
             }">
            @foreach($prestashopCategories as $index => $category)
                @php
                    $categoryId = (int)($category['id'] ?? 0);
                    $categoryName = $category['name'] ?? 'Unknown';
                    $levelDepth = (int)($category['level_depth'] ?? 0);
                    $parentId = (int)($category['id_parent'] ?? 0);

                    // OPTIMISTIC HEURISTIC: Show expand button if category might have children
                    // We use nb_products_recursive > 0 as indicator
                    // ROLLBACK: Back to lazy loading (root categories only), children loaded on-demand
                    $hasChildren = ($category['nb_products_recursive'] ?? 0) > 0;

                    // SPECIAL CASE: Baza (ID=1) and Wszystko (ID=2) should NOT have collapse arrows
                    // They are always expanded by default (see auto-expand in backend loadPrestaShopCategories)
                    // User should not be able to collapse root categories
                    $isRootCategory = in_array($categoryId, [1, 2]);
                    $showExpandButton = $hasChildren && !$isRootCategory && $levelDepth < 5;

                    // Calculate indent based on level (1.5rem per level)
                    $indent = $levelDepth * 1.5;

                    // CRITICAL FIX: Level 0-2 (Baza, Wszystko, Main categories) always visible
                    // Level 3+ (subcategories) visible only when parent is expanded
                    $alwaysVisible = $levelDepth <= 2;

                @endphp

                <div wire:key="cat-{{ $categoryId }}"
                     class="flex items-center mb-1"
                     style="padding-left: {{ $indent }}rem;"
                     @if($alwaysVisible)
                         {{-- Level 0-2: Always visible --}}
                     @else
                         {{-- Level 3+: Visible only when parent expanded --}}
                         x-show="expanded.includes({{ $parentId }})"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 transform -translate-y-1"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                     @endif>
                    {{-- Expand/Collapse Button --}}
                    @if($showExpandButton)
                        <button @click="toggleExpand({{ $category['id'] }})"
                                :disabled="isLoading({{ $category['id'] }})"
                                class="flex-shrink-0 w-6 h-6 flex items-center justify-center text-gray-500 hover:text-orange-500 mr-1 relative">
                            {{-- Expand/Collapse Icon --}}
                            <span x-show="!isLoading({{ $category['id'] }})">
                                <span x-show="isExpanded({{ $category['id'] }})" class="text-sm">&#9660;</span>
                                <span x-show="!isExpanded({{ $category['id'] }})" class="text-sm">&#9654;</span>
                            </span>
                            {{-- Loading Spinner --}}
                            <svg x-show="isLoading({{ $category['id'] }})"
                                 x-cloak
                                 class="animate-spin h-4 w-4 text-orange-500"
                                 xmlns="http://www.w3.org/2000/svg"
                                 fill="none"
                                 viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    @else
                        <span class="w-6 h-6 flex-shrink-0 mr-1"></span>
                    @endif

                    {{-- Category Button --}}
                    <button wire:click="selectImportCategory({{ $category['id'] }})"
                            class="flex-1 text-left py-2 px-4 rounded hover:bg-gray-700 {{ $importCategoryId === $category['id'] ? 'bg-orange-500 bg-opacity-20 border border-orange-500' : '' }}">
                        <span class="font-medium">{{ $category['name'] }}</span>
                        <span class="text-xs text-gray-500 ml-2">
                            ({{ $category['nb_products_recursive'] ?? 0 }} prod.)
                        </span>
                    </button>
                </div>

                {{-- Skeleton Loaders - Facebook Style --}}
                @if($showExpandButton)
                    @php
                        // Child skeleton indent (1 level deeper)
                        $skeletonIndent = ($levelDepth + 1) * 1.5;
                    @endphp
                    <div x-show="isLoading({{ $category['id'] }})"
                         x-cloak
                         style="padding-left: {{ $skeletonIndent }}rem;"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0">
                        {{-- Skeleton Item 1 (wider) --}}
                        <div class="flex items-center mb-2 animate-pulse">
                            <span class="w-6 h-6 flex-shrink-0 mr-1"></span>
                            <div class="h-4 bg-gray-600 rounded w-3/4"></div>
                        </div>
                        {{-- Skeleton Item 2 (medium) --}}
                        <div class="flex items-center mb-2 animate-pulse ps-import-skeleton-delay-1">
                            <span class="w-6 h-6 flex-shrink-0 mr-1"></span>
                            <div class="h-4 bg-gray-600 rounded w-2/3"></div>
                        </div>
                        {{-- Skeleton Item 3 (narrower) --}}
                        <div class="flex items-center mb-2 animate-pulse ps-import-skeleton-delay-2">
                            <span class="w-6 h-6 flex-shrink-0 mr-1"></span>
                            <div class="h-4 bg-gray-600 rounded w-1/2"></div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        @if($importCategoryId)
            {{-- Variant Import Checkbox --}}
            <div class="mt-4 mb-4">
                <label class="flex items-center text-sm text-gray-300 cursor-pointer hover:text-white transition-colors">
                    <input type="checkbox"
                           wire:model.live="importWithVariants"
                           class="form-checkbox mr-2 text-orange-500 rounded border-gray-500 focus:ring-orange-500">
                    <span>Automatycznie importuj brakujace warianty z PrestaShop</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-6">
                    Dla produktow z wariantami (combinations) zostana utworzone odpowiednie warianty w PPM
                </p>
            </div>

            <button wire:click="importFromCategory"
                    class="btn-enterprise-primary inline-flex items-center">
                <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/></svg>
                Importuj z wybranej kategorii
            </button>
        @endif
    @endif
</div>
