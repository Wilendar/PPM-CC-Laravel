<div x-data="{ search: @entangle('search'), showActiveOnly: @entangle('showActiveOnly') }" class="no-stacking-context">
    {{-- Compact CategoryTree Component - Minimal UI for better dropdown compatibility --}}

    {{-- Compact Header --}}
    <div class="bg-gray-800 border-b border-gray-700 px-6 py-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            {{-- Title and Stats --}}
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

            {{-- Compact Controls --}}
            <div class="flex flex-wrap items-center gap-3">
                {{-- Search --}}
                <div class="relative">
                    <input type="text"
                           x-model="search"
                           placeholder="Szukaj kategorii..."
                           class="w-64 pl-10 pr-4 py-2 border border-gray-600 rounded-lg
                                  bg-gray-700 text-white
                                  focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>

                {{-- Filters --}}
                <label class="flex items-center space-x-2 text-sm">
                    <input type="checkbox" x-model="showActiveOnly"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-gray-300">Tylko aktywne</span>
                </label>

                {{-- Add Category Button --}}
                <a href="/admin/products/categories/create"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700
                          text-white text-sm font-medium rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Dodaj kategorię
                </a>
            </div>
        </div>
    </div>

    {{-- Compact Category Table --}}
    <div class="bg-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Kategoria
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Poziom
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Produkty
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Akcje
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($categories as $category)
                        <tr class="hover:bg-gray-700/50 transition-colors">
                            {{-- Category Name with Hierarchy --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-3">
                                    {{-- Hierarchy Indicator --}}
                                    @if($category->level > 1)
                                        <div class="flex items-center space-x-1 text-gray-400">
                                            @for($i = 1; $i < $category->level; $i++)
                                                <div class="w-4 h-px bg-gray-300"></div>
                                            @endfor
                                            <i class="fas fa-arrow-right text-xs"></i>
                                        </div>
                                    @endif

                                    {{-- Category Icon --}}
                                    <div class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-folder text-gray-600 dark:text-gray-400 text-sm"></i>
                                    </div>

                                    {{-- Category Details --}}
                                    <div>
                                        <div class="text-sm font-medium text-white">
                                            {{ $category->name }}
                                        </div>
                                        @if($category->description)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {{ Str::limit($category->description, 50) }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Level --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                           {{ $category->level == 1 ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400' :
                                              ($category->level == 2 ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' :
                                               'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400') }}">
                                    Poziom {{ $category->level ?? 1 }}
                                </span>
                            </td>

                            {{-- Products Count --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-box text-xs"></i>
                                    <span>{{ $category->products_count ?? 0 }}</span>
                                </div>
                            </td>

                            {{-- Status --}}
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

                            {{-- Actions - SIMPLE DROPDOWN --}}
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                @include('livewire.products.categories.partials.compact-category-actions', ['category' => $category])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
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
</div>

    {{-- Loading States --}}
    <div wire:loading class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-gray-800 rounded-lg p-6 max-w-sm w-full mx-4">
            <div class="flex items-center space-x-4">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <div>
                    <h4 class="text-lg font-medium text-white">Ładowanie...</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Proszę czekać</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Internal Styles --}}
    <style>
/* Minimal CSS - no transforms, gradients, or stacking contexts */
.category-table-row:hover {
    background-color: rgba(249, 250, 251, 0.5);
}

.dark .category-table-row:hover {
    background-color: rgba(55, 65, 81, 0.5);
}

/* Ensure dropdown container has clean z-index context */
.dropdown-container {
    position: relative;
    z-index: 1;
}

/* Remove any problematic CSS properties */
* {
    /* Reset any problematic properties that might create stacking contexts */
    backdrop-filter: none !important;
    filter: none !important;
    mix-blend-mode: normal !important;
    opacity: 1 !important;
}

/* Exception for loading states */
.loading-overlay {
    opacity: 0.8 !important;
}

    /* Exception for dropdowns */
    .dropdown-menu {
        opacity: 1 !important;
        z-index: 999999 !important;
        position: absolute !important;
    }
    </style>

</div> {{-- End main container --}}