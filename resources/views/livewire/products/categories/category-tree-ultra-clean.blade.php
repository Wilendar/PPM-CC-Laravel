<div>
    {{-- Flash Messages Component --}}
    <x-flash-messages />

    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-sitemap text-white"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kategorie produktów</h1>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $categories->count() }} kategorii • {{ $categories->where('is_active', true)->count() }} aktywnych
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                {{-- View Mode Toggle --}}
                <div class="flex items-center bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                    <button wire:click="$set('viewMode', 'tree')"
                            class="flex items-center px-3 py-1.5 text-sm font-medium rounded-md transition-colors
                                   {{ $viewMode === 'tree'
                                      ? 'bg-white dark:bg-gray-600 text-blue-600 dark:text-blue-400 shadow-sm'
                                      : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
                        <i class="fas fa-sitemap mr-2"></i>
                        Drzewo
                    </button>
                    <button wire:click="$set('viewMode', 'flat')"
                            class="flex items-center px-3 py-1.5 text-sm font-medium rounded-md transition-colors
                                   {{ $viewMode === 'flat'
                                      ? 'bg-white dark:bg-gray-600 text-blue-600 dark:text-blue-400 shadow-sm'
                                      : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
                        <i class="fas fa-list mr-2"></i>
                        Lista
                    </button>
                </div>

                <div class="relative">
                    <input type="text" wire:model.live="search" placeholder="Szukaj kategorii..."
                           class="w-64 pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                  bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                  focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>

                <label class="flex items-center space-x-2 text-sm">
                    <input type="checkbox" wire:model.live="showActiveOnly"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-gray-700 dark:text-gray-300">Tylko aktywne</span>
                </label>

                {{-- Tree Controls (only visible in tree mode) --}}
                @if($viewMode === 'tree')
                    <div class="flex items-center gap-2">
                        <button wire:click="expandAll"
                                class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                title="Rozwiń wszystkie">
                            <i class="fas fa-expand-arrows-alt mr-1"></i>
                            Rozwiń
                        </button>
                        <button wire:click="collapseAll"
                                class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                title="Zwiń wszystkie">
                            <i class="fas fa-compress-arrows-alt mr-1"></i>
                            Zwiń
                        </button>
                    </div>
                @endif

                <a href="/admin/products/categories/create"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700
                          text-white text-sm font-medium rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Dodaj kategorię
                </a>
            </div>
        </div>
    </div>
    {{-- Bulk Actions Toolbar (visible tylko gdy selectedCategories > 0) --}}
    @if(count($selectedCategories) > 0)
    <div class="bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200 dark:border-blue-700 px-6 py-3"
         x-data="{ bulkMenuOpen: false }"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-check-circle text-blue-600 dark:text-blue-400"></i>
                    <span class="text-sm font-medium text-blue-900 dark:text-blue-100">
                        Zaznaczono: <strong>{{ count($selectedCategories) }}</strong>
                        {{ count($selectedCategories) === 1 ? 'kategoria' : (count($selectedCategories) < 5 ? 'kategorie' : 'kategorii') }}
                    </span>
                </div>

                <div class="relative">
                    <button @click="bulkMenuOpen = !bulkMenuOpen"
                            class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors">
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
                         class="absolute left-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50"
                         style="display: none;">
                        <div class="py-1">
                            <button wire:click="bulkActivate"
                                    @click="bulkMenuOpen = false"
                                    class="w-full flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-700 dark:hover:text-green-400 transition-colors">
                                <i class="fas fa-check-circle w-5 text-green-600 dark:text-green-400"></i>
                                <span class="ml-3">Aktywuj wybrane</span>
                            </button>

                            <button wire:click="bulkDeactivate"
                                    @click="bulkMenuOpen = false"
                                    class="w-full flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <i class="fas fa-pause-circle w-5 text-gray-600 dark:text-gray-400"></i>
                                <span class="ml-3">Dezaktywuj wybrane</span>
                            </button>

                            <hr class="my-1 border-gray-200 dark:border-gray-600">

                            <button wire:click="bulkDelete"
                                    @click="bulkMenuOpen = false"
                                    class="w-full flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-700 dark:hover:text-red-400 transition-colors">
                                <i class="fas fa-trash w-5 text-red-600 dark:text-red-400"></i>
                                <span class="ml-3">Usuń wybrane</span>
                            </button>

                            <hr class="my-1 border-gray-200 dark:border-gray-600">

                            <button wire:click="bulkExport"
                                    @click="bulkMenuOpen = false"
                                    class="w-full flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-700 dark:hover:text-blue-400 transition-colors">
                                <i class="fas fa-download w-5 text-blue-600 dark:text-blue-400"></i>
                                <span class="ml-3">Eksportuj wybrane</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <button wire:click="deselectAll"
                    class="text-sm text-gray-600 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                <i class="fas fa-times-circle mr-1"></i>
                Odznacz wszystkie
            </button>
        </div>
    </div>
    @endif

    <div class="bg-white dark:bg-gray-800">
        <div style="overflow: visible !important;">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" style="table-layout: auto; width: 100%;">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        {{-- Checkbox Column (Master) --}}
                        <th class="px-3 py-3 text-left w-12">
                            <input type="checkbox"
                                   wire:click="{{ count($selectedCategories) === count($categories) && count($categories) > 0 ? 'deselectAll' : 'selectAll' }}"
                                   {{ count($selectedCategories) === count($categories) && count($categories) > 0 ? 'checked' : '' }}
                                   class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                   aria-label="Zaznacz/odznacz wszystkie kategorie"
                                   title="Zaznacz/odznacz wszystkie widoczne kategorie">
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
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 sortable-tbody"
                       style="overflow: visible !important;"
                       @if($viewMode === 'tree')
                           x-data="categoryDragDrop"
                           x-init="initSortable()"
                       @endif>
                    @forelse($categories as $category)
                        <tr class="transition-colors category-row {{ in_array($category->id, $selectedCategories) ? 'bg-blue-50 dark:bg-blue-900/10 hover:bg-blue-100 dark:hover:bg-blue-900/20' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50' }} {{ $viewMode === 'tree' && ($category->level ?? 0) > 0 ? (($category->level ?? 0) === 1 ? 'border-l-4 border-l-blue-500' : (($category->level ?? 0) === 2 ? 'border-l-4 border-l-green-500' : (($category->level ?? 0) === 3 ? 'border-l-4 border-l-purple-500' : 'border-l-4 border-l-orange-500'))) : '' }}"
                            data-category-id="{{ $category->id }}"
                            data-level="{{ $category->level ?? 0 }}">

                            {{-- Checkbox Column --}}
                            <td class="px-3 py-4 whitespace-nowrap w-12">
                                <input type="checkbox"
                                       wire:click="toggleSelection({{ $category->id }})"
                                       {{ in_array($category->id, $selectedCategories) ? 'checked' : '' }}
                                       class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 cursor-pointer"
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
                                            <i class="fas fa-arrow-turn-down-right text-xs text-blue-500"></i>
                                        </div>
                                    @endif

                                    {{-- Category Expand/Collapse Button (Tree Mode Only) --}}
                                    @if($viewMode === 'tree' && $category->children_count > 0)
                                        <button wire:click="toggleNode({{ $category->id }})"
                                                class="w-5 h-5 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center hover:bg-blue-200 dark:hover:bg-blue-700 transition-colors text-xs"
                                                title="{{ in_array($category->id, $expandedNodes) ? 'Zwiń' : 'Rozwiń' }} podkategorie">
                                            <i class="fas fa-{{ in_array($category->id, $expandedNodes) ? 'minus' : 'plus' }} text-gray-600 dark:text-gray-300"></i>
                                        </button>
                                    @else
                                        <div class="w-5 h-5"></div> {{-- Spacer for alignment --}}
                                    @endif

                                    {{-- Category Icon & Details --}}
                                    <div class="w-8 h-8 {{ $viewMode === 'tree' ?
                                        (($category->level ?? 0) === 0 ? 'bg-blue-100 dark:bg-blue-900/20' :
                                         (($category->level ?? 0) === 1 ? 'bg-green-100 dark:bg-green-900/20' :
                                          (($category->level ?? 0) === 2 ? 'bg-purple-100 dark:bg-purple-900/20' :
                                           'bg-orange-100 dark:bg-orange-900/20'))) :
                                        'bg-gray-100 dark:bg-gray-700' }} rounded-lg flex items-center justify-center">
                                        @if($viewMode === 'tree')
                                            <i class="fas fa-folder text-sm
                                                {{ ($category->level ?? 0) === 0 ? 'text-blue-600 dark:text-blue-400' :
                                                   (($category->level ?? 0) === 1 ? 'text-green-600 dark:text-green-400' :
                                                    (($category->level ?? 0) === 2 ? 'text-purple-600 dark:text-purple-400' :
                                                     'text-orange-600 dark:text-orange-400')) }}"></i>
                                        @else
                                            <i class="fas fa-folder text-gray-600 dark:text-gray-400 text-sm"></i>
                                        @endif
                                    </div>

                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white
                                             {{ $viewMode === 'tree' && ($category->level ?? 0) > 0 ? 'text-sm' : 'text-base' }}">
                                            {{ $category->name }}

                                            {{-- Child Count Badge (Tree Mode) --}}
                                            @if($viewMode === 'tree' && $category->children_count > 0)
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs
                                                    {{ ($category->level ?? 0) === 0 ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400' :
                                                       (($category->level ?? 0) === 1 ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' :
                                                        'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400') }}">
                                                    {{ $category->children_count }} {{ $category->children_count === 1 ? 'podkategoria' : 'podkategorii' }}
                                                </span>
                                            @endif
                                        </div>
                                        @if($category->description)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ Str::limit($category->description, 50) }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                           {{ ($category->level ?? 0) == 0 ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400' :
                                              (($category->level ?? 0) == 1 ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' :
                                               (($category->level ?? 0) == 2 ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400' :
                                                'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400')) }}">
                                    Poziom {{ $category->level ?? 0 }}
                                </span>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-box text-xs"></i>
                                    <span>{{ $category->products_count ?? 0 }}</span>
                                    @if($category->products_count > 0)
                                        <span class="text-xs text-blue-600 dark:text-blue-400">(+{{ $category->primary_products_count ?? 0 }} głównych)</span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($category->is_active ?? true)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                               bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Aktywna
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                               bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        <i class="fas fa-pause-circle mr-1"></i>
                                        Nieaktywna
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium position-relative">
                                @include('livewire.products.categories.partials.compact-category-actions', ['category' => $category])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-folder-open text-4xl mb-4"></i>
                                    <h3 class="text-lg font-medium mb-2">Brak kategorii</h3>
                                    <p class="text-sm">Dodaj pierwszą kategorię aby rozpocząć organizację produktów.</p>
                                    <a href="/admin/products/categories/create"
                                       class="inline-flex items-center mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700
                                              text-white text-sm font-medium rounded-lg transition-colors">
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
    <div wire:loading class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-sm w-full mx-4">
            <div class="flex items-center space-x-4">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <div>
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white">Ładowanie...</h4>
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
});
    </script>

    {{-- Drag and Drop Styles - MOVED INSIDE ROOT DIV --}}
    <style>
/* Drag states */
.category-ghost {
    background: rgba(59, 130, 246, 0.1) !important;
    border: 2px dashed rgba(59, 130, 246, 0.3) !important;
}

.category-drag {
    transform: rotate(5deg);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.category-chosen {
    background: rgba(59, 130, 246, 0.05) !important;
}

/* Drag handle enhancement */
.drag-handle:hover {
    background: rgba(59, 130, 246, 0.1);
    border-radius: 4px;
}

/* Global drag state */
body.category-dragging {
    cursor: grabbing !important;
}

body.category-dragging * {
    cursor: grabbing !important;
}

/* Enhanced row hover when dragging */
.sortable-tbody tr:hover .drag-handle {
    opacity: 0.8;
}

.sortable-tbody tr.category-chosen .drag-handle {
    opacity: 1;
    background: rgba(59, 130, 246, 0.2);
}
    </style>

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

    {{-- Enhanced Category Modal with Tabs - DISABLED: Using CategoryForm page instead --}}
    @if(false && $showModal)
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

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <form wire:submit.prevent="saveCategory">
                        {{-- Modal Header with Tabs --}}
                        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                            <div class="px-6 py-4">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900">
                                            <i class="fas fa-folder text-blue-600 dark:text-blue-400"></i>
                                        </div>
                                        <h3 class="ml-4 text-xl font-semibold text-gray-900 dark:text-white">
                                            {{ $modalMode === 'create' ? 'Dodaj kategorię' : 'Edytuj kategorię' }}
                                        </h3>
                                    </div>
                                    <button type="button" @click="$wire.closeModal()"
                                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                        <i class="fas fa-times text-xl"></i>
                                    </button>
                                </div>

                                {{-- Tab Navigation --}}
                                <div class="border-b border-gray-200 dark:border-gray-600">
                                    <nav class="-mb-px flex space-x-8">
                                        <template x-for="(tab, key) in tabs" :key="key">
                                            <button type="button"
                                                    @click="setActiveTab(key)"
                                                    :class="{
                                                        'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === key,
                                                        'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300': activeTab !== key
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
                        <div class="bg-white dark:bg-gray-800 px-6 py-6 max-h-96 overflow-y-auto">

                            {{-- Basic Tab --}}
                            <div x-show="activeTab === 'basic'" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {{-- Nazwa kategorii --}}
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Nazwa kategorii *
                                        </label>
                                        <input type="text" wire:model.defer="categoryForm.name"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md
                                                      bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                                      focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                               required>
                                        @error('categoryForm.name')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Slug --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Slug (URL)
                                        </label>
                                        <input type="text" wire:model.defer="categoryForm.slug"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md
                                                      bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                                      focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                        @error('categoryForm.slug')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Kolejność sortowania --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Kolejność sortowania
                                        </label>
                                        <input type="number" wire:model.defer="categoryForm.sort_order"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md
                                                      bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                                      focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                               min="0">
                                        @error('categoryForm.sort_order')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Opis długi --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Opis kategorii
                                    </label>
                                    <textarea wire:model.defer="categoryForm.description" rows="4"
                                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md
                                                     bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                                     focus:border-blue-500 focus:ring-1 focus:ring-blue-500"></textarea>
                                    @error('categoryForm.description')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Opis krótki --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Krótki opis (dla listy kategorii)
                                    </label>
                                    <textarea wire:model.defer="categoryForm.short_description" rows="2"
                                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md
                                                     bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                                     focus:border-blue-500 focus:ring-1 focus:ring-blue-500"></textarea>
                                    @error('categoryForm.short_description')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Checkboxy --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="flex items-center">
                                        <input type="checkbox" wire:model.defer="categoryForm.is_active"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <label class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Kategoria aktywna
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="checkbox" wire:model.defer="categoryForm.is_featured"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <label class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
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
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Meta Title
                                        </label>
                                        <input type="text" wire:model.defer="categoryForm.meta_title"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md
                                                      bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                                      focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                        @error('categoryForm.meta_title')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Meta Description --}}
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Meta Description
                                        </label>
                                        <textarea wire:model.defer="categoryForm.meta_description" rows="3"
                                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md
                                                         bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                                         focus:border-blue-500 focus:ring-1 focus:ring-blue-500"></textarea>
                                        @error('categoryForm.meta_description')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Meta Keywords --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Meta Keywords
                                        </label>
                                        <input type="text" wire:model.defer="categoryForm.meta_keywords"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md
                                                      bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                                      focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                        @error('categoryForm.meta_keywords')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Canonical URL --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Canonical URL
                                        </label>
                                        <input type="url" wire:model.defer="categoryForm.canonical_url"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md
                                                      bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                                      focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                        @error('categoryForm.canonical_url')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Open Graph --}}
                                    <div class="md:col-span-2">
                                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Open Graph</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    OG Title
                                                </label>
                                                <input type="text" wire:model.defer="categoryForm.og_title"
                                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md
                                                              bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                                              focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    OG Image URL
                                                </label>
                                                <input type="url" wire:model.defer="categoryForm.og_image"
                                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md
                                                              bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                                              focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    OG Description
                                                </label>
                                                <textarea wire:model.defer="categoryForm.og_description" rows="2"
                                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md
                                                                 bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                                                 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"></textarea>
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
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Ikona (Font Awesome)
                                        </label>
                                        <input type="text" wire:model.defer="categoryForm.icon"
                                               placeholder="np. fas fa-car"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md
                                                      bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                                      focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                        @error('categoryForm.icon')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Ścieżka ikony --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Ścieżka do pliku ikony
                                        </label>
                                        <input type="text" wire:model.defer="categoryForm.icon_path"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md
                                                      bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                                      focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                        @error('categoryForm.icon_path')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Banner path --}}
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Ścieżka do bannera kategorii
                                        </label>
                                        <input type="text" wire:model.defer="categoryForm.banner_path"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md
                                                      bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                                      focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                        @error('categoryForm.banner_path')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Visual Settings jako JSON textarea --}}
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Ustawienia wizualne (JSON)
                                        </label>
                                        <textarea wire:model.defer="categoryForm.visual_settings" rows="4"
                                                  placeholder='{"color_primary": "#3B82F6", "color_secondary": "#EFF6FF"}'
                                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md
                                                         bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm
                                                         focus:border-blue-500 focus:ring-1 focus:ring-blue-500"></textarea>
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
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Ustawienia widoczności (JSON)
                                        </label>
                                        <textarea wire:model.defer="categoryForm.visibility_settings" rows="6"
                                                  placeholder='{"is_visible": true, "show_in_menu": true, "show_in_filter": true, "show_product_count": true}'
                                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md
                                                         bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm
                                                         focus:border-blue-500 focus:ring-1 focus:ring-blue-500"></textarea>
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
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Domyślne wartości dla produktów (JSON)
                                        </label>
                                        <textarea wire:model.defer="categoryForm.default_values" rows="6"
                                                  placeholder='{"default_tax_rate": 23.00, "default_weight": null, "default_dimensions": {"height": null, "width": null, "length": null}}'
                                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md
                                                         bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm
                                                         focus:border-blue-500 focus:ring-1 focus:ring-blue-500"></textarea>
                                        @error('categoryForm.default_values')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                        </div>

                        {{-- Modal Footer --}}
                        <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 flex flex-row-reverse space-x-reverse space-x-3">
                            <button type="submit"
                                    class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-6 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove>
                                    <i class="fas fa-save mr-2"></i>
                                    {{ $modalMode === 'create' ? 'Dodaj kategorię' : 'Zapisz zmiany' }}
                                </span>
                                <span wire:loading class="flex items-center">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>
                                    Zapisywanie...
                                </span>
                            </button>
                            <button type="button" wire:click="closeModal"
                                    class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-6 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:bg-gray-600 dark:text-gray-300 dark:border-gray-500 dark:hover:bg-gray-500 transition-colors">
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
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0">

                {{-- Header --}}
                <div class="flex items-start mb-4">
                    <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/20">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
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
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <strong>Operacja nieodwracalna!</strong> Wszystkie przypisania produktów do tej kategorii oraz podkategorie zostaną permanentnie usunięte.
                    </p>
                </div>

                {{-- Actions --}}
                <div class="flex justify-end space-x-3">
                    <button wire:click="cancelForceDelete"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">
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
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0">

                {{-- Header --}}
                <div class="flex items-start mb-4">
                    <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-purple-100 dark:bg-purple-900/20">
                        <i class="fas fa-code-branch text-purple-600 dark:text-purple-400 text-xl"></i>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
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
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
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
                                    <strong class="text-gray-900 dark:text-white">{{ $sourceCategory?->name ?? 'Nie znaleziono kategorii' }}</strong>
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
                        <label for="targetCategoryId" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Kategoria docelowa (otrzyma produkty i podkategorie): <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="targetCategoryId"
                                id="targetCategoryId"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                       bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                       focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
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
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300
                                   bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600
                                   rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
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