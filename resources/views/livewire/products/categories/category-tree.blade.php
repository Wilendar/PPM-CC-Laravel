{{-- CategoryTree Component - Interactive Category Management UI --}}
<div x-data="categoryTreeManager()" x-init="init()" class="space-y-6">

    {{-- Header Section --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
            {{-- Title and Stats --}}
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Zarządzanie Kategoriami
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Hierarchia kategorii produktów - {{ $categories->count() }} kategorii
                    @if(!empty($search))
                        (filtrowane: "{{ $search }}")
                    @endif
                </p>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap items-center gap-2">
                {{-- View Mode Toggle --}}
                <div class="flex rounded-lg bg-gray-100 dark:bg-gray-700 p-1">
                    <button wire:click="$set('viewMode', 'tree')"
                            class="px-3 py-1 text-sm font-medium rounded-md transition-colors
                                   {{ $viewMode === 'tree' ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow' : 'text-gray-600 dark:text-gray-300' }}">
                        <i class="fas fa-sitemap mr-1"></i>
                        Drzewo
                    </button>
                    <button wire:click="$set('viewMode', 'flat')"
                            class="px-3 py-1 text-sm font-medium rounded-md transition-colors
                                   {{ $viewMode === 'flat' ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow' : 'text-gray-600 dark:text-gray-300' }}">
                        <i class="fas fa-list mr-1"></i>
                        Lista
                    </button>
                </div>

                {{-- Tree Actions (only in tree mode) --}}
                @if($viewMode === 'tree')
                    <div class="flex gap-1">
                        <button wire:click="expandAll"
                                class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <i class="fas fa-expand-arrows-alt mr-1"></i>
                            Rozwiń wszystko
                        </button>
                        <button wire:click="collapseAll"
                                class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <i class="fas fa-compress-arrows-alt mr-1"></i>
                            Zwiń wszystko
                        </button>
                    </div>
                @endif

                {{-- Add Category Button --}}
                <button wire:click="createCategory()"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-1"></i>
                    Dodaj kategorię
                </button>
            </div>
        </div>
    </div>

    {{-- Filters and Search Section --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Search Input --}}
            <div class="lg:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Wyszukaj kategorie
                </label>
                <div class="relative">
                    <input wire:model.debounce.300ms="search"
                           type="text"
                           id="search"
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Wyszukaj po nazwie lub opisie...">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    @if(!empty($search))
                        <button wire:click="$set('search', '')"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i class="fas fa-times text-gray-400 hover:text-gray-600"></i>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Active Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Status
                </label>
                <select wire:model="showActiveOnly"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="0">Wszystkie kategorie</option>
                    <option value="1">Tylko aktywne</option>
                </select>
            </div>

            {{-- Products Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Produkty
                </label>
                <select wire:model="showWithProductsOnly"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="0">Wszystkie kategorie</option>
                    <option value="1">Tylko z produktami</option>
                </select>
            </div>
        </div>

        {{-- Bulk Actions --}}
        @if(!empty($selectedCategories))
            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-check-circle text-blue-600"></i>
                        <span class="text-sm font-medium text-blue-800 dark:text-blue-200">
                            Wybrano {{ count($selectedCategories) }} kategorii
                        </span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button wire:click="bulkActivate"
                                class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700 transition-colors">
                            <i class="fas fa-eye mr-1"></i>
                            Aktywuj
                        </button>
                        <button wire:click="bulkDeactivate"
                                class="px-3 py-1 bg-yellow-600 text-white text-sm rounded hover:bg-yellow-700 transition-colors">
                            <i class="fas fa-eye-slash mr-1"></i>
                            Dezaktywuj
                        </button>
                        <button wire:click="deselectAll"
                                class="px-3 py-1 bg-gray-600 text-white text-sm rounded hover:bg-gray-700 transition-colors">
                            <i class="fas fa-times mr-1"></i>
                            Anuluj
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Category Tree/List Section --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        {{-- Loading Overlay --}}
        <div wire:loading.class="opacity-50" wire:target="search,showActiveOnly,showWithProductsOnly,viewMode">
            {{-- Tree View --}}
            @if($viewMode === 'tree')
                <div class="divide-y divide-gray-200 dark:divide-gray-700"
                     x-show="!$wire.loadingStates.tree">

                    @if($treeStructure && count($treeStructure) > 0)
                        {{-- Tree Header --}}
                        <div class="p-4 bg-gray-50 dark:bg-gray-700">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <button wire:click="selectAll"
                                            class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                        Zaznacz wszystkie
                                    </button>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">|</span>
                                    <button wire:click="deselectAll"
                                            class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                        Odznacz wszystkie
                                    </button>
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    Przeciągnij i upuść aby zmienić kolejność
                                </div>
                            </div>
                        </div>

                        {{-- Tree Content --}}
                        <div class="p-4"
                             x-data="{ draggedItem: null, dropTarget: null }"
                             x-ref="treeContainer">
                            @foreach($treeStructure as $node)
                                @include('livewire.products.categories.partials.tree-node', ['node' => $node, 'level' => 0])
                            @endforeach
                        </div>
                    @else
                        {{-- Empty State for Tree --}}
                        <div class="p-12 text-center">
                            <div class="mx-auto h-12 w-12 text-gray-400 mb-4">
                                <i class="fas fa-sitemap text-4xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                @if(!empty($search))
                                    Brak kategorii spełniających kryteria
                                @else
                                    Brak kategorii
                                @endif
                            </h3>
                            <p class="text-gray-500 dark:text-gray-400 mb-6">
                                @if(!empty($search))
                                    Spróbuj zmienić kryteria wyszukiwania
                                @else
                                    Rozpocznij od utworzenia pierwszej kategorii głównej
                                @endif
                            </p>
                            @if(empty($search))
                                <button wire:click="createCategory()"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-plus mr-2"></i>
                                    Utwórz pierwszą kategorię
                                </button>
                            @endif
                        </div>
                    @endif
                </div>

            @else
                {{-- Flat List View --}}
                <div class="overflow-x-auto">
                    @if($categories->count() > 0)
                        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="w-8 px-6 py-3">
                                        <input type="checkbox"
                                               x-model="selectAllFlat"
                                               @change="toggleSelectAllFlat()"
                                               class="rounded border-gray-300 dark:border-gray-600">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer"
                                        wire:click="$set('sortField', 'name')">
                                        Nazwa
                                        @if($sortField === 'name')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                        @endif
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Hierarchia
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer"
                                        wire:click="$set('sortField', 'products_count')">
                                        Produkty
                                        @if($sortField === 'products_count')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                        @endif
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Akcje
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($categories as $category)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4">
                                            <input type="checkbox"
                                                   wire:model="selectedCategories"
                                                   value="{{ $category->id }}"
                                                   class="rounded border-gray-300 dark:border-gray-600">
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                @if($category->icon)
                                                    <i class="{{ $category->icon }} text-gray-400 mr-2"></i>
                                                @endif
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ $category->name }}
                                                    </div>
                                                    @if($category->description)
                                                        <div class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                                            {{ Str::limit($category->description, 60) }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                            @if($category->ancestors->count() > 0)
                                                {{ $category->ancestors->pluck('name')->join(' > ') }} >
                                            @endif
                                            <span class="font-medium">{{ $category->name }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                            <div class="flex items-center space-x-2">
                                                <span class="font-medium">{{ $category->products_count ?? 0 }}</span>
                                                @if($category->primary_products_count ?? 0 > 0)
                                                    <span class="text-xs text-blue-600 dark:text-blue-400">
                                                        ({{ $category->primary_products_count }} głównych)
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($category->is_active)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                    <i class="fas fa-check mr-1"></i>
                                                    Aktywna
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                    <i class="fas fa-times mr-1"></i>
                                                    Nieaktywna
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            @include('livewire.products.categories.partials.category-actions', ['category' => $category])
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        {{-- Empty State for Flat List --}}
                        <div class="p-12 text-center">
                            <div class="mx-auto h-12 w-12 text-gray-400 mb-4">
                                <i class="fas fa-list text-4xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                Brak kategorii
                            </h3>
                            <p class="text-gray-500 dark:text-gray-400 mb-6">
                                Rozpocznij od utworzenia pierwszej kategorii
                            </p>
                            <button wire:click="createCategory()"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i>
                                Utwórz kategorię
                            </button>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Loading Indicator --}}
        <div wire:loading.class="block" wire:loading.class.remove="hidden"
             wire:target="search,showActiveOnly,showWithProductsOnly,viewMode"
             class="hidden absolute inset-0 bg-white bg-opacity-75 dark:bg-gray-800 dark:bg-opacity-75 flex items-center justify-center z-10">
            <div class="flex items-center space-x-2">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                <span class="text-sm text-gray-600 dark:text-gray-400">Ładowanie...</span>
            </div>
        </div>
    </div>

    {{-- Category Modal --}}
    @if($showModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
             x-show="$wire.showModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800">
                {{-- Modal Header --}}
                <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        @if($modalMode === 'create')
                            Dodaj nową kategorię
                        @else
                            Edytuj kategorię
                        @endif
                    </h3>
                    <button wire:click="closeModal"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                {{-- Modal Content --}}
                <div class="mt-4 space-y-4">
                    {{-- Parent Category --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Kategoria nadrzędna
                        </label>
                        <select wire:model="categoryForm.parent_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- Kategoria główna --</option>
                            @foreach($parentOptions as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('categoryForm.parent_id')
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Category Name --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Nazwa kategorii *
                        </label>
                        <input wire:model="categoryForm.name"
                               type="text"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Wprowadź nazwę kategorii">
                        @error('categoryForm.name')
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Opis
                        </label>
                        <textarea wire:model="categoryForm.description"
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Opcjonalny opis kategorii"></textarea>
                        @error('categoryForm.description')
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Icon --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Ikona (Font Awesome)
                        </label>
                        <input wire:model="categoryForm.icon"
                               type="text"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="np. fas fa-car, fas fa-tools">
                        @error('categoryForm.icon')
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- SEO Fields --}}
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Meta tytuł (SEO)
                            </label>
                            <input wire:model="categoryForm.meta_title"
                                   type="text"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Tytuł dla wyszukiwarek">
                            @error('categoryForm.meta_title')
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Meta opis (SEO)
                            </label>
                            <textarea wire:model="categoryForm.meta_description"
                                      rows="2"
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Opis dla wyszukiwarek"></textarea>
                            @error('categoryForm.meta_description')
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Active Status --}}
                    <div class="flex items-center">
                        <input wire:model="categoryForm.is_active"
                               type="checkbox"
                               id="is_active"
                               class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                        <label for="is_active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            Kategoria aktywna
                        </label>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="flex items-center justify-end pt-4 mt-6 border-t border-gray-200 dark:border-gray-700 space-x-2">
                    <button wire:click="closeModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        Anuluj
                    </button>
                    <button wire:click="saveCategory"
                            wire:loading.attr="disabled"
                            wire:target="saveCategory"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50">
                        <span wire:loading.remove wire:target="saveCategory">
                            @if($modalMode === 'create')
                                Utwórz kategorię
                            @else
                                Zapisz zmiany
                            @endif
                        </span>
                        <span wire:loading wire:target="saveCategory" class="flex items-center">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                            Zapisywanie...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Alpine.js Category Tree Manager Script --}}
    <script>
    function categoryTreeManager() {
        return {
            draggedItem: null,
            dropTarget: null,
            selectAllFlat: false,

            init() {
                this.initializeDragAndDrop();
                this.initializeKeyboardShortcuts();
            },

            initializeDragAndDrop() {
                // Drag and drop will be implemented in next step
                console.log('Drag and drop initialized');
            },

            initializeKeyboardShortcuts() {
                document.addEventListener('keydown', (e) => {
                    // Ctrl+A - Select all
                    if (e.ctrlKey && e.key === 'a' && !e.target.matches('input, textarea')) {
                        e.preventDefault();
                        @this.call('selectAll');
                    }

                    // Escape - Deselect all / Close modal
                    if (e.key === 'Escape') {
                        @this.call('deselectAll');
                        if (@this.showModal) {
                            @this.call('closeModal');
                        }
                    }
                });
            },

            toggleSelectAllFlat() {
                if (this.selectAllFlat) {
                    @this.call('selectAll');
                } else {
                    @this.call('deselectAll');
                }
            }
        }
    }
    </script>
</div>