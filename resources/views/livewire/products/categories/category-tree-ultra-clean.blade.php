<div>
    <div class="bg-gray-800 border-b border-gray-700 px-6 py-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-sitemap text-white"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Kategorie produktów</h1>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $categories->count() }} kategorii • {{ $categories->where('is_active', true)->count() }} aktywnych
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                {{-- View Mode Toggle --}}
                <div class="flex items-center bg-gray-700 rounded-lg p-1">
                    <button wire:click="$set('viewMode', 'tree')"
                            class="flex items-center px-3 py-1.5 text-sm font-medium rounded-md transition-colors
                                   {{ $viewMode === 'tree' ? 'category-view-toggle-active' : 'category-view-toggle-inactive' }}">
                        <i class="fas fa-sitemap mr-2"></i>
                        Drzewo
                    </button>
                    <button wire:click="$set('viewMode', 'flat')"
                            class="flex items-center px-3 py-1.5 text-sm font-medium rounded-md transition-colors
                                   {{ $viewMode === 'flat' ? 'category-view-toggle-active' : 'category-view-toggle-inactive' }}">
                        <i class="fas fa-list mr-2"></i>
                        Lista
                    </button>
                </div>

                <div class="relative">
                    <input type="text" wire:model.live="search" placeholder="Szukaj kategorii..."
                           class="category-search-input">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>

                <label class="flex items-center space-x-2 text-sm">
                    <input type="checkbox" wire:model.live="showActiveOnly" class="category-checkbox">
                    <span class="text-gray-300">Tylko aktywne</span>
                </label>

                {{-- Tree Controls (only visible in tree mode) --}}
                @if($viewMode === 'tree')
                    <div class="flex items-center gap-2">
                        <button wire:click="expandAll" class="category-expand-btn" title="Rozwiń wszystkie">
                            <i class="fas fa-expand-arrows-alt mr-1"></i>
                            Rozwiń
                        </button>
                        <button wire:click="collapseAll" class="category-expand-btn" title="Zwiń wszystkie">
                            <i class="fas fa-compress-arrows-alt mr-1"></i>
                            Zwiń
                        </button>
                    </div>
                @endif

                {{-- NOTE: Creates a main category (level 2) - levels 0 and 1 are reserved for PrestaShop structure --}}
                <a href="/admin/products/categories/create?level=2"
                   class="category-add-btn"
                   title="Dodaje nową kategorię główną (poziom 2)">
                    <i class="fas fa-plus mr-2"></i>
                    Dodaj kategorię
                </a>
            </div>
        </div>
    </div>
    {{-- Bulk Actions Toolbar (visible tylko gdy selectedCategories > 0) --}}
    @if(count($selectedCategories) > 0)
    <div class="category-bulk-bar"
         x-data="{ bulkMenuOpen: false }"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-check-circle category-bulk-icon"></i>
                    <span class="category-bulk-text">
                        Zaznaczono: <strong>{{ count($selectedCategories) }}</strong>
                        {{ count($selectedCategories) === 1 ? 'kategoria' : (count($selectedCategories) < 5 ? 'kategorie' : 'kategorii') }}
                    </span>
                </div>

                <div class="relative">
                    <button @click="bulkMenuOpen = !bulkMenuOpen" class="category-bulk-btn">
                        <i class="fas fa-tasks mr-2"></i>
                        Operacje masowe
                        <i class="fas fa-chevron-down ml-2 text-xs" :class="{ 'rotate-180': bulkMenuOpen }"></i>
                    </button>

                    {{-- Dropdown Menu --}}
                    <div x-show="bulkMenuOpen"
                         @click.away="bulkMenuOpen = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute left-0 mt-2 w-56 bg-gray-800 rounded-lg shadow-xl border border-gray-700 z-50"
                         style="display: none;">
                        <div class="py-1">
                            <button wire:click="bulkActivate"
                                    @click="bulkMenuOpen = false"
                                    class="w-full flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-700 dark:hover:text-green-400 transition-colors">
                                <i class="fas fa-check-circle w-5 text-green-600 dark:text-green-400"></i>
                                <span class="ml-3">Aktywuj wybrane</span>
                            </button>

                            <button wire:click="bulkDeactivate"
                                    @click="bulkMenuOpen = false"
                                    class="w-full flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 transition-colors">
                                <i class="fas fa-pause-circle w-5 text-gray-400"></i>
                                <span class="ml-3">Dezaktywuj wybrane</span>
                            </button>

                            <hr class="my-1 border-gray-200 dark:border-gray-600">

                            <button wire:click="showBulkDeleteConfirmation"
                                    @click="bulkMenuOpen = false"
                                    class="w-full flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-700 dark:hover:text-red-400 transition-colors">
                                <i class="fas fa-trash w-5 text-red-600 dark:text-red-400"></i>
                                <span class="ml-3">Usuń wybrane</span>
                            </button>

                            <hr class="my-1 border-gray-200 dark:border-gray-600">

                            <button wire:click="bulkExport"
                                    @click="bulkMenuOpen = false"
                                    class="w-full flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-700 dark:hover:text-blue-400 transition-colors">
                                <i class="fas fa-download w-5 text-blue-600 dark:text-blue-400"></i>
                                <span class="ml-3">Eksportuj wybrane</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <button wire:click="deselectAll"
                    class="text-sm text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                <i class="fas fa-times-circle mr-1"></i>
                Odznacz wszystkie
            </button>
        </div>
    </div>
    @endif

    <div class="bg-gray-800">
        <div style="overflow: visible !important;">
            <table class="min-w-full divide-y divide-gray-700" style="table-layout: auto; width: 100%;">
                <thead class="bg-gray-900/50">
                    <tr>
                        {{-- Checkbox Column (Master) --}}
                        <th class="px-3 py-3 text-left w-12"
                            x-data="{
                                selectedCount: {{ count($selectedCategories) }},
                                totalCount: {{ count($categories) }}
                            }"
                            x-init="$nextTick(() => {
                                const cb = $el.querySelector('input[type=checkbox]');
                                if (cb) cb.indeterminate = (selectedCount > 0 && selectedCount < totalCount);
                            })">
                            <input type="checkbox"
                                   wire:key="master-checkbox-{{ count($selectedCategories) === count($categories) && count($categories) > 0 ? '1' : '0' }}"
                                   wire:click="toggleSelectAll"
                                   @checked(count($selectedCategories) === count($categories) && count($categories) > 0)
                                   class="category-checkbox"
                                   aria-label="Zaznacz/odznacz wszystkie kategorie"
                                   title="{{ count($selectedCategories) > 0 ? 'Odznacz wszystkie' : 'Zaznacz wszystkie widoczne kategorie' }}">
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            {{-- Drag Handle Column --}}
                            @if($viewMode === 'tree')
                                <i class="fas fa-grip-vertical mr-2"></i>
                            @endif
                            Kategoria
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Poziom</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Produkty</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Akcje</th>
                    </tr>
                </thead>
                <tbody class="bg-gray-800 divide-y divide-gray-700 sortable-tbody category-tree-keyboard"
                       style="overflow: visible !important;"
                       role="tree"
                       aria-label="Drzewo kategorii produktow"
                       @if($viewMode === 'tree')
                           x-data="{ ...categoryDragDrop(), ...categoryKeyboardNav() }"
                           x-init="initSortable(); initKeyboardNav()"
                           @keydown="handleKeydown($event)"
                           tabindex="0"
                       @endif>
                    {{-- NOTE: Poziomy 0 i 1 są zarezerwowane dla struktury PrestaShop --}}
                    {{-- Przycisk "Dodaj kategorię" w headerze dodaje kategorię na poziomie 2 (główna kategoria użytkownika) --}}
                    @forelse($categories as $category)
                        <tr wire:key="category-row-{{ $category->id }}"
                            class="transition-colors category-row {{ in_array($category->id, $selectedCategories) ? 'category-row-selected' : 'bg-gray-800 hover:bg-gray-700/50' }} {{ $viewMode === 'tree' && ($category->level ?? 0) > 0 ? 'category-level-border' : '' }}"
                            data-category-id="{{ $category->id }}"
                            data-level="{{ $category->level ?? 0 }}"
                            role="treeitem"
                            aria-level="{{ ($category->level ?? 0) + 1 }}"
                            aria-expanded="{{ $category->children_count > 0 ? (in_array($category->id, $expandedNodes) ? 'true' : 'false') : 'undefined' }}"
                            aria-selected="{{ in_array($category->id, $selectedCategories) ? 'true' : 'false' }}"
                            tabindex="-1">

                            {{-- Checkbox Column --}}
                            <td class="px-3 py-4 whitespace-nowrap w-12">
                                <input type="checkbox"
                                       wire:key="checkbox-{{ $category->id }}-{{ in_array($category->id, $selectedCategories) ? '1' : '0' }}"
                                       wire:click="toggleSelection({{ $category->id }})"
                                       @checked(in_array($category->id, $selectedCategories))
                                       class="category-checkbox"
                                       aria-label="Zaznacz kategorię {{ $category->name }}"
                                       title="Zaznacz kategorię">
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-3">
                                    {{-- Drag Handle (Tree Mode Only) --}}
                                    @if($viewMode === 'tree')
                                        <div class="drag-handle cursor-move opacity-30 hover:opacity-60 transition-opacity p-1"
                                             title="Przeciągnij aby zmienić kolejność">
                                            <i class="fas fa-grip-vertical text-gray-400 text-xs"></i>
                                        </div>
                                    @endif

                                    {{-- Tree Mode: Enhanced Hierarchy Visualization --}}
                                    @if($viewMode === 'tree' && ($category->level ?? 0) > 0)
                                        <div class="flex items-center space-x-1 text-gray-400" style="width: {{ ($category->level ?? 0) * 24 }}px;">
                                            @for($i = 0; $i < ($category->level ?? 0); $i++)
                                                <div class="w-6 h-px bg-gradient-to-r from-gray-300 to-transparent"></div>
                                            @endfor
                                            <i class="fas fa-arrow-turn-down-right text-xs category-hierarchy-arrow"></i>
                                        </div>
                                    @endif

                                    {{-- Category Expand/Collapse Button (Tree Mode Only) --}}
                                    @if($viewMode === 'tree' && $category->children_count > 0)
                                        <button wire:click="toggleNode({{ $category->id }})"
                                                class="category-expand-chevron {{ in_array($category->id, $expandedNodes) ? 'is-expanded' : '' }}"
                                                title="{{ in_array($category->id, $expandedNodes) ? 'Zwiń' : 'Rozwiń' }} podkategorie"
                                                aria-label="{{ in_array($category->id, $expandedNodes) ? 'Zwiń' : 'Rozwiń' }} {{ $category->name }}">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    @else
                                        <div class="w-6 h-6"></div> {{-- Spacer for alignment --}}
                                    @endif

                                    {{-- Category Icon & Details --}}
                                    @if($viewMode === 'tree')
                                        @php
                                            // Level-based folder icon classes (0-5+)
                                            $folderLevel = min($category->level ?? 0, 5);
                                        @endphp
                                        <div class="category-folder-icon category-folder-icon--level-{{ $folderLevel }}">
                                            <i class="fas fa-{{ $category->children_count > 0 && in_array($category->id, $expandedNodes) ? 'folder-open' : 'folder' }}"></i>
                                        </div>
                                    @else
                                        <div class="w-8 h-8 bg-gray-700 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-folder text-gray-400 text-sm"></i>
                                        </div>
                                    @endif

                                    <div class="category-name-cell relative">
                                        <div class="text-sm font-medium text-white
                                             {{ $viewMode === 'tree' && ($category->level ?? 0) > 0 ? 'text-sm' : 'text-base' }}">
                                            {{ $category->name }}

                                            {{-- Child Count Badge (Tree Mode) --}}
                                            @if($viewMode === 'tree' && $category->children_count > 0)
                                                <span class="ml-2 category-badge-subcategories category-badge-subcategories-level-{{ min($category->level ?? 0, 2) }}">
                                                    {{ $category->children_count }} {{ $category->children_count === 1 ? 'podkategoria' : 'podkategorii' }}
                                                </span>
                                            @endif
                                        </div>
                                        @if($category->description)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ Str::limit($category->description, 50) }}</div>
                                        @endif

                                        {{-- Breadcrumb Tooltip (hover reveal) --}}
                                        @if($viewMode === 'tree' && ($category->level ?? 0) > 0)
                                            @php
                                                $breadcrumbParts = [];
                                                $parent = $category->parent;
                                                while ($parent) {
                                                    array_unshift($breadcrumbParts, $parent->name);
                                                    $parent = $parent->parent;
                                                }
                                            @endphp
                                            @if(count($breadcrumbParts) > 0)
                                            <div class="category-breadcrumb-tooltip">
                                                <div class="category-breadcrumb-tooltip-content">
                                                    @foreach($breadcrumbParts as $index => $part)
                                                        <span>{{ $part }}</span>
                                                        <span class="separator"><i class="fas fa-chevron-right"></i></span>
                                                    @endforeach
                                                    <span class="current">{{ $category->name }}</span>
                                                </div>
                                            </div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                {{-- Level column with add child inline form (FAZA 2.1 ETAP_15) --}}
                                @php
                                    $childLevel = ($category->level ?? 0) + 1;
                                    $childIconClass = match($childLevel) {
                                        0 => 'category-insert-icon--level-0',
                                        1 => 'category-insert-icon--level-1',
                                        2 => 'category-insert-icon--level-2',
                                        3 => 'category-insert-icon--level-3',
                                        4 => 'category-insert-icon--level-4',
                                        default => 'category-insert-icon--level-5',
                                    };
                                    // Color for level preview badge (levels 0-10)
                                    $childLevelColor = match($childLevel) {
                                        0 => '#60a5fa', // Blue
                                        1 => '#4ade80', // Green
                                        2 => '#c084fc', // Purple
                                        3 => '#fb923c', // Orange
                                        4 => '#f472b6', // Pink
                                        5 => '#2dd4bf', // Teal
                                        6 => '#a3e635', // Lime
                                        7 => '#f87171', // Red
                                        8 => '#818cf8', // Indigo
                                        9 => '#fbbf24', // Amber
                                        default => '#e879f9', // Fuchsia (10+)
                                    };
                                @endphp
                                <div class="category-level-column"
                                     x-data="{ ...categoryInlineForm({ parentId: {{ $category->id }}, level: {{ $childLevel }} }), hideTimeout: null }"
                                     :class="{ 'is-adding-child': isOpen }"
                                     @mouseenter="clearTimeout(hideTimeout); showTrigger = true"
                                     @mouseleave="if (!isOpen) { hideTimeout = setTimeout(() => showTrigger = false, 400) }">
                                    {{-- Badge with expanded hover area --}}
                                    <div class="category-level-badge-wrapper">
                                        <span class="category-badge-level">
                                            Poziom {{ $category->level ?? 0 }}
                                        </span>
                                    </div>

                                    {{-- Add child trigger (slides DOWN below badge, CENTERED) --}}
                                    {{-- Block level 1 creation (only Baza/level 0 can have level 1 children via other methods) --}}
                                    @if($childLevel > 1)
                                    <div class="category-add-child-popup"
                                         x-show="showTrigger && !isOpen"
                                         x-cloak
                                         x-transition:enter="transition ease-out duration-150"
                                         x-transition:enter-start="opacity-0 -translate-y-1"
                                         x-transition:enter-end="opacity-100 translate-y-0"
                                         x-transition:leave="transition ease-in duration-100"
                                         x-transition:leave-start="opacity-100 translate-y-0"
                                         x-transition:leave-end="opacity-0 -translate-y-1">
                                        <button type="button"
                                                class="category-add-child-trigger"
                                                @click="open()"
                                                title="Dodaj podkategorię (poziom {{ $childLevel }})">
                                            {{-- Arrow and level preview --}}
                                            <i class="fas fa-arrow-down category-add-child-arrow"></i>
                                            <span class="category-add-child-level-preview" data-level-color="{{ $childLevelColor }}">+{{ $childLevel }}</span>
                                        </button>
                                    </div>

                                    {{-- Inline form popup (below trigger) --}}
                                    @endif
                                    @if($childLevel > 1)
                                    <div class="category-add-child-form"
                                         x-show="isOpen"
                                         x-cloak
                                         x-transition:enter="transition ease-out duration-150"
                                         x-transition:enter-start="opacity-0 translate-y-1"
                                         x-transition:enter-end="opacity-100 translate-y-0"
                                         @click.outside="close()">
                                        <div class="category-add-child-form-inner">
                                            {{-- Folder icon with level color --}}
                                            <div class="category-insert-icon {{ $childIconClass }}">
                                                <i class="fas fa-folder"></i>
                                            </div>

                                            {{-- Input with +OPIS toggle --}}
                                            <div class="category-insert-input-wrapper">
                                                <input type="text"
                                                       x-model="name"
                                                       x-ref="nameInput"
                                                       @keydown.enter="save()"
                                                       @keydown.escape="close()"
                                                       class="category-insert-form-input"
                                                       placeholder="Nazwa podkategorii...">
                                                <button type="button"
                                                        @click="showDescription = !showDescription"
                                                        :class="{ 'is-active': showDescription }"
                                                        class="category-insert-desc-toggle">
                                                    <i class="fas fa-plus mr-1"></i>OPIS
                                                </button>
                                            </div>

                                            {{-- Action buttons --}}
                                            <div class="category-insert-form-actions">
                                                <button type="button"
                                                        @click="save()"
                                                        :disabled="!name.trim()"
                                                        class="category-insert-form-btn category-insert-form-btn--save">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button"
                                                        @click="close()"
                                                        class="category-insert-form-btn category-insert-form-btn--cancel">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Description textarea --}}
                                        <div class="category-insert-desc-row" :class="{ 'is-visible': showDescription }">
                                            <textarea x-model="description"
                                                      @keydown.escape="close()"
                                                      class="category-insert-form-textarea"
                                                      placeholder="Opis podkategorii (opcjonalny)..."></textarea>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="category-product-badge {{ ($category->products_count ?? 0) > 0 ? 'category-product-badge--has-products' : '' }}">
                                    <i class="fas fa-box"></i>
                                    <span>{{ $category->products_count ?? 0 }}</span>
                                    @if(($category->products_count ?? 0) > 0)
                                        <span class="category-primary-count">({{ $category->primary_products_count ?? 0 }} glownych)</span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($category->is_active ?? true)
                                    <span class="category-status-active">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Aktywna
                                    </span>
                                @else
                                    <span class="category-status-inactive">
                                        <i class="fas fa-pause-circle mr-1"></i>
                                        Nieaktywna
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium position-relative">
                                @include('livewire.products.categories.partials.compact-category-actions', ['category' => $category])
                            </td>
                        </tr>

                        {{-- Inline Insert Line (+ between categories) - FAZA 2 ETAP_15 --}}
                        {{-- RESTRICTION: Levels 0 and 1 are reserved for PrestaShop structure - no insert allowed --}}
                        @if($viewMode === 'tree' && ($category->level ?? 0) >= 2)
                            @php
                                $insertLevel = $category->level ?? 0;
                                $insertParentId = $category->parent_id;
                                // Level colors match main category icons from components.css
                                $iconLevelClass = match($insertLevel) {
                                    0 => 'category-insert-icon--level-0', // Blue (never shown - restricted)
                                    1 => 'category-insert-icon--level-1', // Green (never shown - restricted)
                                    2 => 'category-insert-icon--level-2', // Purple
                                    3 => 'category-insert-icon--level-3', // Orange
                                    4 => 'category-insert-icon--level-4', // Pink
                                    default => 'category-insert-icon--level-5', // Teal (5+)
                                };
                            @endphp
                            <tr class="category-insert-line"
                                wire:key="insert-line-{{ $category->id }}"
                                x-data="categoryInlineForm({ parentId: {{ $insertParentId ?? 'null' }}, level: {{ $insertLevel }} })"
                                :class="{ 'is-adding': isOpen }">
                                <td class="w-12"></td>
                                <td colspan="5" class="px-6 category-insert-cell">
                                    {{-- Trigger button --}}
                                    <button class="category-insert-trigger"
                                            @click="open()"
                                            x-show="!isOpen"
                                            title="Dodaj kategorię na tym poziomie">
                                        <i class="fas fa-plus"></i>
                                    </button>

                                    {{-- Inline form --}}
                                    <div class="category-insert-form" x-show="isOpen" x-cloak>
                                        <div class="category-insert-form-row">
                                            {{-- Folder icon (auto-color based on level) --}}
                                            <div class="category-insert-icon {{ $iconLevelClass }}">
                                                <i class="fas fa-folder"></i>
                                            </div>

                                            {{-- Input wrapper with +OPIS button --}}
                                            <div class="category-insert-input-wrapper">
                                                <input type="text"
                                                       x-model="name"
                                                       x-ref="nameInput"
                                                       @keydown.enter="save()"
                                                       @keydown.escape="close()"
                                                       class="category-insert-form-input"
                                                       placeholder="Nazwa nowej kategorii...">
                                                <button type="button"
                                                        @click="showDescription = !showDescription"
                                                        :class="{ 'is-active': showDescription }"
                                                        class="category-insert-desc-toggle">
                                                    <i class="fas fa-plus mr-1"></i>OPIS
                                                </button>
                                            </div>

                                            {{-- Action buttons --}}
                                            <div class="category-insert-form-actions">
                                                <button type="button"
                                                        @click="save()"
                                                        :disabled="!name.trim()"
                                                        class="category-insert-form-btn category-insert-form-btn--save">
                                                    <i class="fas fa-check"></i>
                                                    Dodaj
                                                </button>
                                                <button type="button"
                                                        @click="close()"
                                                        class="category-insert-form-btn category-insert-form-btn--cancel">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Description textarea (expandable) --}}
                                        <div class="category-insert-desc-row" :class="{ 'is-visible': showDescription }">
                                            <textarea x-model="description"
                                                      @keydown.escape="close()"
                                                      class="category-insert-form-textarea"
                                                      placeholder="Opis kategorii (opcjonalny)..."></textarea>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-folder-open text-4xl mb-4"></i>
                                    <h3 class="text-lg font-medium mb-2">Brak kategorii</h3>
                                    <p class="text-sm">Dodaj pierwszą kategorię aby rozpocząć organizację produktów.</p>
                                    <a href="/admin/products/categories/create" class="category-add-btn mt-4">
                                        <i class="fas fa-plus mr-2"></i>
                                        Dodaj kategorię
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{-- Loading overlay - TYLKO dla ciężkich operacji (nie toggleNode!) --}}
    <div wire:loading.delay.longer
         wire:target="saveCategory, deleteCategory, confirmForceDelete, bulkDelete, bulkActivate, bulkDeactivate, mergeCategories, reorderCategory"
         class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-gray-800 rounded-lg p-6 max-w-sm w-full mx-4">
            <div class="flex items-center space-x-4">
                <div class="animate-spin category-loading-spinner"></div>
                <div>
                    <h4 class="text-lg font-medium text-white">Zapisywanie...</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Proszę czekać</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Drag and Drop Script - MOVED INSIDE ROOT DIV --}}
    <script>
document.addEventListener('alpine:init', () => {
    Alpine.data('categoryDragDrop', () => ({
        sortable: null,
        dragStartPosition: null,

        initSortable() {
            // Check if SortableJS is available
            if (typeof Sortable === 'undefined') {
                console.warn('SortableJS not loaded. Drag and drop functionality disabled.');
                return;
            }

            const tbody = this.$el;
            if (!tbody) return;

            this.sortable = Sortable.create(tbody, {
                animation: 200,
                handle: '.drag-handle',
                ghostClass: 'category-ghost',
                dragClass: 'category-drag',
                chosenClass: 'category-chosen',

                onStart: (evt) => {
                    this.dragStartPosition = {
                        oldIndex: evt.oldIndex,
                        categoryId: parseInt(evt.item.dataset.categoryId),
                        level: parseInt(evt.item.dataset.level)
                    };

                    // Add visual feedback
                    document.body.classList.add('category-dragging');
                    evt.item.classList.add('opacity-75');
                },

                onEnd: (evt) => {
                    document.body.classList.remove('category-dragging');
                    evt.item.classList.remove('opacity-75');

                    // Check if position actually changed
                    if (evt.oldIndex === evt.newIndex) {
                        return;
                    }

                    const categoryId = this.dragStartPosition.categoryId;
                    const newSortOrder = evt.newIndex;

                    // Calculate new parent (same level categories)
                    const newParentId = this.calculateNewParent(evt.newIndex, this.dragStartPosition.level);

                    // Call Livewire method
                    this.$wire.reorderCategory(categoryId, newParentId, newSortOrder)
                        .then(() => {
                            // Show success notification
                            this.showNotification('Kolejność kategorii została zaktualizowana.', 'success');
                        })
                        .catch((error) => {
                            console.error('Error reordering category:', error);
                            this.showNotification('Błąd podczas zmiany kolejności kategorii.', 'error');

                            // Revert the change
                            if (evt.oldIndex < evt.newIndex) {
                                evt.to.insertBefore(evt.item, evt.to.children[evt.oldIndex]);
                            } else {
                                evt.to.insertBefore(evt.item, evt.to.children[evt.oldIndex + 1]);
                            }
                        });
                },

                // Only allow dropping on same level categories
                onMove: (evt) => {
                    const draggedLevel = parseInt(evt.dragged.dataset.level);
                    const relatedLevel = parseInt(evt.related.dataset.level);

                    // Allow moving within same level or to direct parent/child
                    return Math.abs(draggedLevel - relatedLevel) <= 1;
                }
            });
        },

        calculateNewParent(newIndex, categoryLevel) {
            const tbody = this.$el;
            const rows = Array.from(tbody.children);

            // Look for parent category before this position
            for (let i = newIndex - 1; i >= 0; i--) {
                const row = rows[i];
                if (!row) continue;

                const level = parseInt(row.dataset.level);

                if (level === categoryLevel - 1) {
                    return parseInt(row.dataset.categoryId);
                } else if (level < categoryLevel - 1) {
                    break;
                }
            }

            return null; // Root level
        },

        showNotification(message, type) {
            // Simple notification - can be enhanced with toast library
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 transition-all duration-300 transform translate-x-full ${
                type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            }`;
            notification.textContent = message;

            document.body.appendChild(notification);

            // Animate in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 10);

            // Remove after 3 seconds
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
    }));

    // Keyboard Navigation Component - FAZA 4 ETAP_15
    Alpine.data('categoryKeyboardNav', () => ({
        currentIndex: -1,
        rows: [],

        initKeyboardNav() {
            this.updateRows();
            // Listen for Livewire updates
            document.addEventListener('livewire:navigated', () => this.updateRows());
        },

        updateRows() {
            this.rows = Array.from(this.$el.querySelectorAll('tr[role="treeitem"]'));
        },

        handleKeydown(event) {
            // Skip if inside an input field
            if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA') {
                return;
            }

            const handlers = {
                'ArrowDown': () => this.moveDown(),
                'ArrowUp': () => this.moveUp(),
                'ArrowRight': () => this.expandCurrent(),
                'ArrowLeft': () => this.collapseCurrent(),
                'Enter': () => this.editCurrent(),
                'Delete': () => this.deleteCurrent(),
                'n': () => this.addChildToCurrent(),
                'N': () => this.addChildToCurrent(),
                'Space': () => this.toggleSelectCurrent(),
            };

            if (handlers[event.key]) {
                event.preventDefault();
                handlers[event.key]();
            }
        },

        moveDown() {
            this.updateRows();
            if (this.rows.length === 0) return;

            // Remove current focus
            if (this.currentIndex >= 0 && this.rows[this.currentIndex]) {
                this.rows[this.currentIndex].classList.remove('keyboard-focused');
            }

            // Move to next row
            this.currentIndex = Math.min(this.currentIndex + 1, this.rows.length - 1);
            this.focusRow(this.currentIndex);
        },

        moveUp() {
            this.updateRows();
            if (this.rows.length === 0) return;

            // Remove current focus
            if (this.currentIndex >= 0 && this.rows[this.currentIndex]) {
                this.rows[this.currentIndex].classList.remove('keyboard-focused');
            }

            // Move to previous row
            this.currentIndex = Math.max(this.currentIndex - 1, 0);
            this.focusRow(this.currentIndex);
        },

        focusRow(index) {
            if (index < 0 || index >= this.rows.length) return;

            const row = this.rows[index];
            row.classList.add('keyboard-focused');
            row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

            // Update ARIA
            this.rows.forEach(r => r.setAttribute('tabindex', '-1'));
            row.setAttribute('tabindex', '0');
            row.focus();
        },

        expandCurrent() {
            const row = this.rows[this.currentIndex];
            if (!row) return;

            const categoryId = row.dataset.categoryId;
            const isExpanded = row.getAttribute('aria-expanded') === 'true';

            if (!isExpanded && row.getAttribute('aria-expanded') !== 'undefined') {
                this.$wire.toggleNode(parseInt(categoryId));
            }
        },

        collapseCurrent() {
            const row = this.rows[this.currentIndex];
            if (!row) return;

            const categoryId = row.dataset.categoryId;
            const isExpanded = row.getAttribute('aria-expanded') === 'true';

            if (isExpanded) {
                this.$wire.toggleNode(parseInt(categoryId));
            }
        },

        editCurrent() {
            const row = this.rows[this.currentIndex];
            if (!row) return;

            const categoryId = row.dataset.categoryId;
            // Use existing editCategory method
            this.$wire.editCategory(parseInt(categoryId));
        },

        deleteCurrent() {
            const row = this.rows[this.currentIndex];
            if (!row) return;

            const categoryId = row.dataset.categoryId;
            // Confirm before delete
            if (confirm('Czy na pewno chcesz usunac te kategorie?')) {
                this.$wire.deleteCategory(parseInt(categoryId));
            }
        },

        addChildToCurrent() {
            const row = this.rows[this.currentIndex];
            if (!row) return;

            // Navigate to create page with parent_id parameter
            const categoryId = row.dataset.categoryId;
            const level = parseInt(row.dataset.level) + 1;
            window.location.href = `/admin/products/categories/create?parent_id=${categoryId}&level=${level}`;
        },

        toggleSelectCurrent() {
            const row = this.rows[this.currentIndex];
            if (!row) return;

            const categoryId = row.dataset.categoryId;
            this.$wire.toggleSelection(parseInt(categoryId));
        }
    }));

    // Inline Form Component for adding categories - FAZA 2 ETAP_15
    Alpine.data('categoryInlineForm', (config) => ({
        isOpen: false,
        showTrigger: false, // For hover reveal in POZIOM column
        name: '',
        description: '',
        showDescription: false,
        parentId: config.parentId,
        level: config.level,

        open() {
            this.isOpen = true;
            this.name = '';
            this.description = '';
            this.showDescription = false;

            // Focus input after DOM update
            this.$nextTick(() => {
                if (this.$refs.nameInput) {
                    this.$refs.nameInput.focus();
                }
            });
        },

        close() {
            this.isOpen = false;
            this.name = '';
            this.description = '';
            this.showDescription = false;
        },

        save() {
            const trimmedName = this.name.trim();
            if (!trimmedName) return;

            // Call Livewire method to save category
            this.$wire.saveInlineCategory(
                trimmedName,
                this.description.trim(),
                this.parentId
            ).then(() => {
                this.close();
            }).catch((error) => {
                console.error('Error saving category:', error);
            });
        }
    }));
});
    </script>

    {{-- Drag and Drop Styles - MOVED TO category-tree.css --}}

    {{-- SortableJS CDN - Load if not already present - MOVED INSIDE ROOT DIV --}}
    <script>
if (typeof Sortable === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js';
    script.onload = () => {
        console.log('SortableJS loaded successfully');
        // Reinitialize if Alpine is already loaded
        if (window.Alpine) {
            window.Alpine.nextTick(() => {
                document.querySelectorAll('[x-data*="categoryDragDrop"]').forEach(el => {
                    if (el._x_dataStack && el._x_dataStack[0].initSortable) {
                        el._x_dataStack[0].initSortable();
                    }
                });
            });
        }
    };
    script.onerror = () => {
        console.error('Failed to load SortableJS. Drag and drop functionality disabled.');
    };
    document.head.appendChild(script);
}
    </script>

    {{-- Enhanced Category Modal with Tabs - RE-ENABLED for inline insert (FAZA 2 ETAP_15) --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto"
             x-data="{
                 show: @entangle('showModal'),
                 activeTab: 'basic',
                 tabs: {
                     'basic': { name: 'Podstawowe', icon: 'fas fa-folder' },
                     'seo': { name: 'SEO i Meta', icon: 'fas fa-search' },
                     'visual': { name: 'Wygląd', icon: 'fas fa-palette' },
                     'visibility': { name: 'Widoczność', icon: 'fas fa-eye' },
                     'defaults': { name: 'Domyślne', icon: 'fas fa-cog' }
                 },
                 setActiveTab(tab) {
                     this.activeTab = tab;
                 }
             }"
             x-show="show">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" @click="$wire.closeModal()"></div>

                <div class="inline-block align-bottom bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <form wire:submit.prevent="saveCategory">
                        {{-- Modal Header with Tabs --}}
                        <div class="bg-gray-800 border-b border-gray-700">
                            <div class="px-6 py-4">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <div class="icon-chip">
                                            <i class="fas fa-folder"></i>
                                        </div>
                                        <h3 class="ml-4 text-xl font-semibold text-white">
                                            {{ $modalMode === 'create' ? 'Dodaj kategorię' : 'Edytuj kategorię' }}
                                        </h3>
                                    </div>
                                    <button type="button" @click="$wire.closeModal()"
                                            class="text-gray-400 hover:text-white transition-colors">
                                        <i class="fas fa-times text-xl"></i>
                                    </button>
                                </div>

                                {{-- Tab Navigation --}}
                                <div class="border-b border-gray-600">
                                    <nav class="-mb-px flex space-x-8">
                                        <template x-for="(tab, key) in tabs" :key="key">
                                            <button type="button"
                                                    @click="setActiveTab(key)"
                                                    :class="{
                                                        'border-mpp-primary text-mpp-primary': activeTab === key,
                                                        'border-transparent text-gray-400 hover:text-gray-300': activeTab !== key
                                                    }"
                                                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm flex items-center transition-colors">
                                                <i :class="tab.icon" class="mr-2"></i>
                                                <span x-text="tab.name"></span>
                                            </button>
                                        </template>
                                    </nav>
                                </div>
                            </div>
                        </div>

                        {{-- Modal Content --}}
                        <div class="bg-gray-800 px-6 py-6 max-h-96 overflow-y-auto">

                            {{-- Basic Tab --}}
                            <div x-show="activeTab === 'basic'" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {{-- Nazwa kategorii --}}
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-300 mb-2">
                                            Nazwa kategorii *
                                        </label>
                                        <input type="text" wire:model.defer="categoryForm.name"
                                               class="w-full px-3 py-2 border border-gray-600 rounded-md
                                                      bg-gray-700 text-white
                                                      focus:border-mpp-primary focus:ring-1 focus:ring-mpp-primary"
                                               required>
                                        @error('categoryForm.name')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Slug --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">
                                            Slug (URL)
                                        </label>
                                        <input type="text" wire:model.defer="categoryForm.slug"
                                               class="w-full px-3 py-2 border border-gray-600 rounded-md
                                                      bg-gray-700 text-white
                                                      focus:border-mpp-primary focus:ring-1 focus:ring-mpp-primary">
                                        @error('categoryForm.slug')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Kolejność sortowania --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">
                                            Kolejność sortowania
                                        </label>
                                        <input type="number" wire:model.defer="categoryForm.sort_order"
                                               class="w-full px-3 py-2 border border-gray-600 rounded-md
                                                      bg-gray-700 text-white
                                                      focus:border-mpp-primary focus:ring-1 focus:ring-mpp-primary"
                                               min="0">
                                        @error('categoryForm.sort_order')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Opis długi --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        Opis kategorii
                                    </label>
                                    <textarea wire:model.defer="categoryForm.description" rows="4"
                                              class="w-full px-3 py-2 border border-gray-600 rounded-md
                                                     bg-gray-700 text-white
                                                     focus:border-mpp-primary focus:ring-1 focus:ring-mpp-primary"></textarea>
                                    @error('categoryForm.description')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Opis krótki --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        Krótki opis (dla listy kategorii)
                                    </label>
                                    <textarea wire:model.defer="categoryForm.short_description" rows="2"
                                              class="w-full px-3 py-2 border border-gray-600 rounded-md
                                                     bg-gray-700 text-white
                                                     focus:border-mpp-primary focus:ring-1 focus:ring-mpp-primary"></textarea>
                                    @error('categoryForm.short_description')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Checkboxy --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="flex items-center">
                                        <input type="checkbox" wire:model.defer="categoryForm.is_active"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <label class="ml-2 text-sm font-medium text-gray-300">
                                            Kategoria aktywna
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="checkbox" wire:model.defer="categoryForm.is_featured"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <label class="ml-2 text-sm font-medium text-gray-300">
                                            Kategoria wyróżniona
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- SEO Tab --}}
                            <div x-show="activeTab === 'seo'" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {{-- Meta Title --}}
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-300 mb-2">
                                            Meta Title
                                        </label>
                                        <input type="text" wire:model.defer="categoryForm.meta_title"
                                               class="w-full px-3 py-2 border border-gray-600 rounded-md
                                                      bg-gray-700 text-white
                                                      focus:border-mpp-primary focus:ring-1 focus:ring-mpp-primary">
                                        @error('categoryForm.meta_title')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Meta Description --}}
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-300 mb-2">
                                            Meta Description
                                        </label>
                                        <textarea wire:model.defer="categoryForm.meta_description" rows="3"
                                                  class="w-full px-3 py-2 border border-gray-600 rounded-md
                                                         bg-gray-700 text-white
                                                         focus:border-mpp-primary focus:ring-1 focus:ring-mpp-primary"></textarea>
                                        @error('categoryForm.meta_description')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Meta Keywords --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">
                                            Meta Keywords
                                        </label>
                                        <input type="text" wire:model.defer="categoryForm.meta_keywords"
                                               class="w-full px-3 py-2 border border-gray-600 rounded-md
                                                      bg-gray-700 text-white
                                                      focus:border-mpp-primary focus:ring-1 focus:ring-mpp-primary">
                                        @error('categoryForm.meta_keywords')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Canonical URL --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">
                                            Canonical URL
                                        </label>
                                        <input type="url" wire:model.defer="categoryForm.canonical_url"
                                               class="w-full px-3 py-2 border border-gray-600 rounded-md
                                                      bg-gray-700 text-white
                                                      focus:border-mpp-primary focus:ring-1 focus:ring-mpp-primary">
                                        @error('categoryForm.canonical_url')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Open Graph --}}
                                    <div class="md:col-span-2">
                                        <h4 class="text-lg font-medium text-white mb-4">Open Graph</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-300 mb-2">
                                                    OG Title
                                                </label>
                                                <input type="text" wire:model.defer="categoryForm.og_title"
                                                       class="w-full px-3 py-2 border border-gray-600 rounded-md
                                                              bg-gray-700 text-white
                                                              focus:border-mpp-primary focus:ring-1 focus:ring-mpp-primary">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-300 mb-2">
                                                    OG Image URL
                                                </label>
                                                <input type="url" wire:model.defer="categoryForm.og_image"
                                                       class="w-full px-3 py-2 border border-gray-600 rounded-md
                                                              bg-gray-700 text-white
                                                              focus:border-mpp-primary focus:ring-1 focus:ring-mpp-primary">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-medium text-gray-300 mb-2">
                                                    OG Description
                                                </label>
                                                <textarea wire:model.defer="categoryForm.og_description" rows="2"
                                                          class="w-full px-3 py-2 border border-gray-600 rounded-md
                                                                 bg-gray-700 text-white
                                                                 focus:border-mpp-primary focus:ring-1 focus:ring-mpp-primary"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Visual Tab --}}
                            <div x-show="activeTab === 'visual'" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {{-- Ikona --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">
                                            Ikona (Font Awesome)
                                        </label>
                                        <input type="text" wire:model.defer="categoryForm.icon"
                                               placeholder="np. fas fa-car"
                                               class="w-full px-3 py-2 border border-gray-600 rounded-md
                                                      bg-gray-700 text-white
                                                      focus:border-mpp-primary focus:ring-1 focus:ring-mpp-primary">
                                        @error('categoryForm.icon')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Ścieżka ikony --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">
                                            Ścieżka do pliku ikony
                                        </label>
                                        <input type="text" wire:model.defer="categoryForm.icon_path"
                                               class="w-full px-3 py-2 border border-gray-600 rounded-md
                                                      bg-gray-700 text-white
                                                      focus:border-mpp-primary focus:ring-1 focus:ring-mpp-primary">
                                        @error('categoryForm.icon_path')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Banner path --}}
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-300 mb-2">
                                            Ścieżka do bannera kategorii
                                        </label>
                                        <input type="text" wire:model.defer="categoryForm.banner_path"
                                               class="w-full px-3 py-2 border border-gray-600 rounded-md
                                                      bg-gray-700 text-white
                                                      focus:border-mpp-primary focus:ring-1 focus:ring-mpp-primary">
                                        @error('categoryForm.banner_path')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Visual Settings jako JSON textarea --}}
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-300 mb-2">
                                            Ustawienia wizualne (JSON)
                                        </label>
                                        <textarea wire:model.defer="categoryForm.visual_settings" rows="4"
                                                  placeholder='{"color_primary": "#3B82F6", "color_secondary": "#EFF6FF"}'
                                                  class="w-full px-3 py-2 border border-gray-600 rounded-md
                                                         bg-gray-700 text-white font-mono text-sm
                                                         focus:border-mpp-primary focus:ring-1 focus:ring-mpp-primary"></textarea>
                                        @error('categoryForm.visual_settings')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Visibility Tab --}}
                            <div x-show="activeTab === 'visibility'" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {{-- Visibility Settings jako JSON textarea --}}
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-300 mb-2">
                                            Ustawienia widoczności (JSON)
                                        </label>
                                        <textarea wire:model.defer="categoryForm.visibility_settings" rows="6"
                                                  placeholder='{"is_visible": true, "show_in_menu": true, "show_in_filter": true, "show_product_count": true}'
                                                  class="w-full px-3 py-2 border border-gray-600 rounded-md
                                                         bg-gray-700 text-white font-mono text-sm
                                                         focus:border-mpp-primary focus:ring-1 focus:ring-mpp-primary"></textarea>
                                        @error('categoryForm.visibility_settings')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Defaults Tab --}}
                            <div x-show="activeTab === 'defaults'" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {{-- Default Values jako JSON textarea --}}
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-300 mb-2">
                                            Domyślne wartości dla produktów (JSON)
                                        </label>
                                        <textarea wire:model.defer="categoryForm.default_values" rows="6"
                                                  placeholder='{"default_tax_rate": 23.00, "default_weight": null, "default_dimensions": {"height": null, "width": null, "length": null}}'
                                                  class="w-full px-3 py-2 border border-gray-600 rounded-md
                                                         bg-gray-700 text-white font-mono text-sm
                                                         focus:border-mpp-primary focus:ring-1 focus:ring-mpp-primary"></textarea>
                                        @error('categoryForm.default_values')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                        </div>

                        {{-- Modal Footer --}}
                        <div class="bg-gray-700 px-6 py-4 flex flex-row-reverse space-x-reverse space-x-3">
                            <button type="submit"
                                    class="btn-enterprise-primary"
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove class="flex items-center">
                                    <i class="fas fa-save mr-2"></i>
                                    {{ $modalMode === 'create' ? 'Dodaj kategorię' : 'Zapisz zmiany' }}
                                </span>
                                <span wire:loading class="flex items-center">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>
                                    Zapisywanie...
                                </span>
                            </button>
                            <button type="button" wire:click="closeModal"
                                    class="btn-enterprise-secondary">
                                <i class="fas fa-times mr-2"></i>
                                Anuluj
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Force Delete Confirmation Modal --}}
    @if($showForceDeleteModal)
    <div class="fixed inset-0 z-[9999] overflow-y-auto"
         x-data="{ show: @entangle('showForceDeleteModal') }"
         x-show="show"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">

        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity"></div>

        {{-- Modal Content --}}
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0">

                {{-- Header --}}
                <div class="flex items-start mb-4">
                    <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/20">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-lg font-semibold text-white">
                            Wymuszenie usunięcia kategorii
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Ta kategoria zawiera dane. Potwierdź usunięcie.
                        </p>
                    </div>
                    <button wire:click="cancelForceDelete"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                {{-- Warnings List --}}
                @if(!empty($deleteWarnings))
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 mb-4">
                    <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-400 mb-2">
                        <i class="fas fa-info-circle mr-1"></i> Ostrzeżenia:
                    </h4>
                    <ul class="list-disc list-inside space-y-1 text-sm text-yellow-700 dark:text-yellow-300">
                        @foreach($deleteWarnings as $warning)
                        <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- Confirmation Text --}}
                <div class="mb-6">
                    <p class="text-sm text-gray-400">
                        <strong>Operacja nieodwracalna!</strong> Wszystkie przypisania produktów do tej kategorii oraz podkategorie zostaną permanentnie usunięte.
                    </p>
                </div>

                {{-- Actions --}}
                <div class="flex justify-end space-x-3">
                    <button wire:click="cancelForceDelete"
                            class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-700 border border-gray-600 rounded-lg hover:bg-gray-600">
                        Anuluj
                    </button>
                    <button wire:click="confirmForceDelete"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">
                        <i class="fas fa-trash mr-2"></i>
                        Potwierdź usunięcie
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Job Progress Bar for Delete (if deleteProgressId exists) --}}
    @if($deleteProgressId)
    <div class="fixed bottom-4 right-4 z-50" wire:key="delete-progress-{{ $deleteProgressId }}">
        @livewire('components.job-progress-bar', ['jobId' => $deleteProgressId], key('delete-progress-' . $deleteProgressId))
    </div>
    @endif

    {{-- Bulk Delete Confirmation Modal --}}
    @if($showBulkDeleteModal)
    <div class="fixed inset-0 z-[9999] overflow-y-auto"
         x-data="{ show: @entangle('showBulkDeleteModal') }"
         x-show="show"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">

        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity"></div>

        {{-- Modal Content --}}
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0">

                {{-- Header --}}
                <div class="flex items-start mb-4">
                    <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/20">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-lg font-semibold text-white">
                            Potwierdzenie usuniecia {{ count($bulkDeleteWarnings) }} kategorii
                        </h3>
                        <p class="text-sm text-gray-400 mt-1">
                            Ta operacja jest <strong class="text-red-400">nieodwracalna</strong>. Kategorie zostana permanentnie usuniete z bazy danych.
                        </p>
                    </div>
                    <button wire:click="cancelBulkDelete"
                            class="text-gray-400 hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                {{-- Categories List --}}
                @if(!empty($bulkDeleteWarnings))
                <div class="bg-gray-700/50 border border-gray-600 rounded-lg p-4 mb-4 max-h-64 overflow-y-auto">
                    <h4 class="text-sm font-medium text-gray-300 mb-3">
                        <i class="fas fa-list mr-1"></i> Kategorie do usuniecia:
                    </h4>
                    <div class="space-y-2">
                        @foreach($bulkDeleteWarnings as $warning)
                        <div class="flex items-center justify-between p-2 rounded {{ $warning['can_delete'] ? 'bg-green-900/20 border border-green-700/50' : 'bg-red-900/20 border border-red-700/50' }}">
                            <div class="flex items-center">
                                <i class="fas {{ $warning['can_delete'] ? 'fa-check-circle text-green-400' : 'fa-times-circle text-red-400' }} mr-2"></i>
                                <span class="text-sm text-gray-200">{{ $warning['name'] }}</span>
                                <span class="text-xs text-gray-500 ml-2">(L{{ $warning['level'] }})</span>
                            </div>
                            <div class="flex items-center space-x-3 text-xs">
                                @if($warning['products_count'] > 0)
                                <span class="text-yellow-400">
                                    <i class="fas fa-box mr-1"></i>{{ $warning['products_count'] }} produktow
                                </span>
                                @endif
                                @if($warning['children_count'] > 0)
                                <span class="text-orange-400">
                                    <i class="fas fa-folder mr-1"></i>{{ $warning['children_count'] }} podkategorii
                                </span>
                                @endif
                                @if($warning['can_delete'])
                                <span class="text-green-400">
                                    <i class="fas fa-check mr-1"></i>mozna usunac
                                </span>
                                @else
                                <span class="text-red-400">
                                    <i class="fas fa-ban mr-1"></i>nie mozna usunac
                                </span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Summary --}}
                @php
                    $canDelete = collect($bulkDeleteWarnings)->where('can_delete', true)->count();
                    $cannotDelete = collect($bulkDeleteWarnings)->where('can_delete', false)->count();
                    $totalDescendants = collect($bulkDeleteWarnings)->sum('descendants_count');
                    $totalProducts = collect($bulkDeleteWarnings)->sum('products_count');
                @endphp
                <div class="bg-yellow-900/20 border border-yellow-700 rounded-lg p-4 mb-4">
                    <h4 class="text-sm font-medium text-yellow-400 mb-2">
                        <i class="fas fa-info-circle mr-1"></i> Podsumowanie:
                    </h4>
                    <ul class="list-disc list-inside space-y-1 text-sm text-gray-300">
                        <li><span class="text-green-400">{{ $canDelete }}</span> kategorii mozna usunac bezposrednio</li>
                        @if($cannotDelete > 0)
                        <li><span class="text-orange-400">{{ $cannotDelete }}</span> kategorii zawiera podkategorie lub produkty</li>
                        @endif
                        @if($totalDescendants > 0)
                        <li><span class="text-orange-400">{{ $totalDescendants }}</span> podkategorii (potomkow) do usuniecia</li>
                        @endif
                        @if($totalProducts > 0)
                        <li><span class="text-yellow-400">{{ $totalProducts }}</span> produktow straci przypisanie kategorii</li>
                        @endif
                    </ul>
                </div>

                {{-- Force Delete Option --}}
                @if($cannotDelete > 0)
                <div class="bg-red-900/30 border border-red-700 rounded-lg p-4 mb-4">
                    <label class="flex items-start cursor-pointer">
                        <input type="checkbox"
                               wire:model.live="forceBulkDelete"
                               class="mt-1 h-4 w-4 rounded border-gray-500 bg-gray-700 text-red-600 focus:ring-red-500">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-red-400">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Usun rowniez kategorie z podkategoriami i produktami
                            </span>
                            <p class="text-xs text-gray-400 mt-1">
                                Zaznacz aby usunac wszystkie wybrane kategorie wraz z ich podkategoriami.
                                Produkty nie zostana usuniete, ale straca przypisanie do tych kategorii.
                            </p>
                        </div>
                    </label>
                </div>
                @endif
                @endif

                {{-- Actions --}}
                <div class="flex justify-end space-x-3">
                    <button wire:click="cancelBulkDelete"
                            class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-700 border border-gray-600 rounded-lg hover:bg-gray-600">
                        Anuluj
                    </button>
                    @if($canDelete > 0 || $forceBulkDelete)
                    <button wire:click="confirmBulkDelete"
                            class="px-4 py-2 text-sm font-medium text-white {{ $forceBulkDelete ? 'bg-red-700 hover:bg-red-800' : 'bg-red-600 hover:bg-red-700' }} rounded-lg">
                        <i class="fas fa-trash mr-2"></i>
                        @if($forceBulkDelete)
                            Usun wszystkie ({{ count($bulkDeleteWarnings) + $totalDescendants }})
                        @else
                            Usun {{ $canDelete }} {{ $canDelete === 1 ? 'kategorie' : 'kategorii' }}
                        @endif
                    </button>
                    @else
                    <button disabled
                            class="px-4 py-2 text-sm font-medium text-gray-500 bg-gray-700 border border-gray-600 rounded-lg cursor-not-allowed">
                        <i class="fas fa-ban mr-2"></i>
                        Brak kategorii do usuniecia
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Category Merge Modal --}}
    @if($showMergeCategoriesModal)
    <div class="fixed inset-0 z-[9999] overflow-y-auto"
         x-data="{ show: @entangle('showMergeCategoriesModal'), loading: false }"
         x-show="show"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">

        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity"></div>

        {{-- Modal Content --}}
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0">

                {{-- Header --}}
                <div class="flex items-start mb-4">
                    <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-purple-100 dark:bg-purple-900/20">
                        <i class="fas fa-code-branch text-purple-600 dark:text-purple-400 text-xl"></i>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-lg font-semibold text-white">
                            Połącz kategorie
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Przenieś produkty i podkategorie do kategorii docelowej
                        </p>
                    </div>
                    <button wire:click="closeCategoryMergeModal"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            aria-label="Zamknij">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="space-y-4 mb-6">
                    {{-- Source Category Display (read-only) --}}
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Kategoria źródłowa (zostanie usunięta):
                        </label>
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-red-100 dark:bg-red-900/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-folder text-red-600 dark:text-red-400"></i>
                            </div>
                            <div>
                                @if($sourceCategoryId)
                                    @php
                                        $sourceCategory = \App\Models\Category::find($sourceCategoryId);
                                    @endphp
                                    <strong class="text-white">{{ $sourceCategory?->name ?? 'Nie znaleziono kategorii' }}</strong>
                                    @if($sourceCategory)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            Produkty: {{ $sourceCategory->products_count ?? 0 }} | Podkategorie: {{ $sourceCategory->children_count ?? 0 }}
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Target Category Selector --}}
                    <div>
                        <label for="targetCategoryId" class="block text-sm font-medium text-gray-300 mb-2">
                            Kategoria docelowa (otrzyma produkty i podkategorie): <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="targetCategoryId"
                                id="targetCategoryId"
                                class="w-full px-3 py-2 border border-gray-600 rounded-lg
                                       bg-gray-700 text-white
                                       focus:border-mpp-primary focus:ring-1 focus:ring-mpp-primary"
                                required>
                            <option value="">-- Wybierz kategorię docelową --</option>
                            @foreach($parentOptions as $categoryId => $categoryName)
                                @if($categoryId != $sourceCategoryId)
                                    <option value="{{ $categoryId }}">{{ $categoryName }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('targetCategoryId')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Warnings Display --}}
                    @if(!empty($mergeWarnings))
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-400 mb-2 flex items-center">
                            <i class="fas fa-exclamation-triangle mr-2"></i> Ostrzeżenia:
                        </h4>
                        <ul class="list-disc list-inside space-y-1 text-sm text-yellow-700 dark:text-yellow-300">
                            @foreach($mergeWarnings as $warning)
                            <li>{{ $warning }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>

                {{-- Modal Footer --}}
                <div class="flex justify-end space-x-3">
                    <button wire:click="closeCategoryMergeModal"
                            type="button"
                            class="px-4 py-2 text-sm font-medium text-gray-300
                                   bg-gray-700 border border-gray-600
                                   rounded-lg hover:bg-gray-600 transition-colors"
                            :disabled="loading">
                        Anuluj
                    </button>
                    <button wire:click="mergeCategories"
                            type="button"
                            class="px-4 py-2 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700
                                   rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="loading || !$wire.targetCategoryId"
                            x-on:click="loading = true">
                        <span wire:loading.remove wire:target="mergeCategories">
                            <i class="fas fa-code-branch mr-2"></i>
                            Połącz kategorie
                        </span>
                        <span wire:loading wire:target="mergeCategories" class="flex items-center">
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            Łączenie...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>