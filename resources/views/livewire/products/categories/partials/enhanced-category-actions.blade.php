{{-- Enhanced Category Actions - Modern Dropdown with Rich Features --}}
<div x-data="enhancedCategoryActions({{ $category->id }})"
     x-init="init()"
     class="relative inline-block">
    {{-- Enhanced Actions Button --}}
    <button @click="toggleDropdown()"
            @click.away="closeDropdown()"
            class="group inline-flex items-center px-4 py-3 border-2 border-gray-200 dark:border-gray-600
                   rounded-2xl bg-white dark:bg-gray-700 text-sm font-semibold text-gray-700 dark:text-gray-300
                   hover:border-blue-300 dark:hover:border-blue-500 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50
                   dark:hover:from-blue-900/20 dark:hover:to-indigo-900/20 hover:text-blue-700 dark:hover:text-blue-300
                   transition-all duration-300 transform hover:scale-105 shadow-md hover:shadow-lg"
            :class="{ 'ring-4 ring-blue-500/20 border-blue-500': open }">
        <i class="fas fa-ellipsis-v group-hover:text-blue-600 transition-colors duration-300"></i>
    </button>

    {{-- Enhanced Dropdown Menu - PORTAL TO BODY --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-90"
         x-teleport="body"
         :data-category-id="{{ $category->id }}"
         class="dropdown-fix origin-top-right right-0 mt-3 w-80 rounded-3xl shadow-2xl bg-white dark:bg-gray-800
                ring-1 ring-black ring-opacity-5 focus:outline-none border-2 border-gray-100 dark:border-gray-700"
         style="display: none; z-index: 999999 !important; position: fixed !important;">

        {{-- Dropdown Header --}}
        <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20
                   rounded-t-3xl border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-indigo-200 dark:from-blue-900/30 dark:to-indigo-800/30
                           rounded-2xl flex items-center justify-center shadow-md">
                    @if($category->icon)
                        <i class="{{ $category->icon }} text-lg text-blue-600 dark:text-blue-400"></i>
                    @else
                        <i class="fas fa-folder text-lg text-gray-500"></i>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white truncate">
                        {{ $category->name }}
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        ID: {{ $category->id }} • Poziom {{ ($category->level ?? 0) + 1 }}
                    </p>
                </div>
            </div>
        </div>

        <div class="py-2" role="menu">
            {{-- Primary Actions --}}
            <div class="px-2 space-y-1">
                {{-- Edit Action --}}
                <button wire:click="editCategory({{ $category->id }})"
                        @click="closeDropdown()"
                        class="group w-full flex items-center px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300
                               hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 dark:hover:from-blue-900/20 dark:hover:to-indigo-900/20
                               hover:text-blue-700 dark:hover:text-blue-300 rounded-2xl transition-all duration-300 transform hover:scale-105"
                        role="menuitem">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center mr-4
                               group-hover:bg-blue-200 dark:group-hover:bg-blue-800/50 transition-colors duration-300">
                        <i class="fas fa-edit text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold">Edytuj kategorię</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Zmień nazwę, opis i ustawienia</div>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400 group-hover:text-blue-500 transition-colors duration-300"></i>
                </button>

                {{-- Add Subcategory --}}
                @if(($category->level ?? 0) < (\App\Models\Category::MAX_LEVEL - 1))
                    <button wire:click="createCategory({{ $category->id }})"
                            @click="closeDropdown()"
                            class="group w-full flex items-center px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300
                                   hover:bg-gradient-to-r hover:from-green-50 hover:to-emerald-50 dark:hover:from-green-900/20 dark:hover:to-emerald-900/20
                                   hover:text-green-700 dark:hover:text-green-300 rounded-2xl transition-all duration-300 transform hover:scale-105"
                            role="menuitem">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center mr-4
                                   group-hover:bg-green-200 dark:group-hover:bg-green-800/50 transition-colors duration-300">
                            <i class="fas fa-plus text-green-600 dark:text-green-400"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold">Dodaj podkategorię</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Utwórz kategorię podrzędną</div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400 group-hover:text-green-500 transition-colors duration-300"></i>
                    </button>
                @else
                    <div class="w-full flex items-center px-4 py-3 text-sm text-gray-400 dark:text-gray-500 rounded-2xl">
                        <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-ban text-gray-400"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold">Maksymalna głębokość</div>
                            <div class="text-xs">Nie można dodać więcej poziomów</div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Divider --}}
            <div class="my-3 mx-4 border-t border-gray-100 dark:border-gray-700"></div>

            {{-- Category Statistics --}}
            <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-blue-50 dark:from-gray-700/30 dark:to-blue-900/10 mx-2 rounded-2xl">
                <h5 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center">
                    <i class="fas fa-chart-bar mr-2 text-blue-500"></i>
                    Statystyki kategorii
                </h5>
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-2xl flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-box text-lg text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $category->products_count ?? 0 }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Produktów</div>
                    </div>
                    @if(($category->primary_products_count ?? 0) > 0)
                        <div class="text-center">
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-2xl flex items-center justify-center mx-auto mb-2">
                                <i class="fas fa-star text-lg text-green-600 dark:text-green-400"></i>
                            </div>
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $category->primary_products_count }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Głównych</div>
                        </div>
                    @endif
                </div>

                {{-- Additional Stats --}}
                <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-600 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-layer-group text-purple-500"></i>
                        <span>Poziom: <strong>{{ ($category->level ?? 0) + 1 }}</strong></span>
                    </div>
                    @if($category->children && $category->children->count() > 0)
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-sitemap text-indigo-500"></i>
                            <span>Podkategorii: <strong>{{ $category->children->count() }}</strong></span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Divider --}}
            <div class="my-3 mx-4 border-t border-gray-100 dark:border-gray-700"></div>

            {{-- Secondary Actions --}}
            <div class="px-2 space-y-1">
                {{-- Toggle Status --}}
                @if($category->is_active)
                    <button wire:click="bulkDeactivate"
                            wire:confirm="Czy na pewno chcesz dezaktywować kategorię '{{ $category->name }}'?"
                            @click="closeDropdown()"
                            class="group w-full flex items-center px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300
                                   hover:bg-gradient-to-r hover:from-amber-50 hover:to-orange-50 dark:hover:from-amber-900/20 dark:hover:to-orange-900/20
                                   hover:text-amber-700 dark:hover:text-amber-300 rounded-2xl transition-all duration-300 transform hover:scale-105"
                            role="menuitem">
                        <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center mr-4
                                   group-hover:bg-amber-200 dark:group-hover:bg-amber-800/50 transition-colors duration-300">
                            <i class="fas fa-eye-slash text-amber-600 dark:text-amber-400"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold">Dezaktywuj kategorię</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Ukryj w systemie</div>
                        </div>
                    </button>
                @else
                    <button wire:click="bulkActivate"
                            @click="closeDropdown()"
                            class="group w-full flex items-center px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300
                                   hover:bg-gradient-to-r hover:from-green-50 hover:to-emerald-50 dark:hover:from-green-900/20 dark:hover:to-emerald-900/20
                                   hover:text-green-700 dark:hover:text-green-300 rounded-2xl transition-all duration-300 transform hover:scale-105"
                            role="menuitem">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center mr-4
                                   group-hover:bg-green-200 dark:group-hover:bg-green-800/50 transition-colors duration-300">
                            <i class="fas fa-eye text-green-600 dark:text-green-400"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold">Aktywuj kategorię</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Pokaż w systemie</div>
                        </div>
                    </button>
                @endif

                {{-- Copy Category ID --}}
                <button x-data="{ copied: false }"
                        @click="
                            navigator.clipboard.writeText('{{ $category->id }}');
                            copied = true;
                            setTimeout(() => copied = false, 2000);
                            closeDropdown();
                        "
                        class="group w-full flex items-center px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300
                               hover:bg-gradient-to-r hover:from-gray-50 hover:to-blue-50 dark:hover:from-gray-700/50 dark:hover:to-blue-900/10
                               hover:text-gray-800 dark:hover:text-gray-200 rounded-2xl transition-all duration-300 transform hover:scale-105"
                        role="menuitem">
                    <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center mr-4
                               group-hover:bg-gray-200 dark:group-hover:bg-gray-600 transition-colors duration-300">
                        <i class="fas fa-copy text-gray-600 dark:text-gray-400"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold" x-text="copied ? 'Skopiowano!' : 'Kopiuj ID'">Kopiuj ID</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $category->id }}</div>
                    </div>
                    <div x-show="copied" class="text-green-500">
                        <i class="fas fa-check"></i>
                    </div>
                </button>
            </div>

            {{-- Category Path (if has ancestors) --}}
            @if($category->ancestors && $category->ancestors->count() > 0)
                <div class="mx-2 mt-3 p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/10 dark:to-pink-900/10 rounded-2xl">
                    <h5 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                        <i class="fas fa-route mr-2 text-purple-500"></i>
                        Ścieżka kategorii
                    </h5>
                    <div class="flex items-center space-x-2 text-xs text-gray-600 dark:text-gray-400 overflow-x-auto">
                        @foreach($category->ancestors as $ancestor)
                            <span class="inline-flex items-center px-2 py-1 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 whitespace-nowrap">
                                {{ $ancestor->name }}
                            </span>
                            <i class="fas fa-chevron-right text-gray-400 flex-shrink-0"></i>
                        @endforeach
                        <span class="inline-flex items-center px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded-lg border border-purple-200 dark:border-purple-700 font-semibold whitespace-nowrap">
                            {{ $category->name }}
                        </span>
                    </div>
                </div>
            @endif

            {{-- Danger Zone --}}
            <div class="my-3 mx-4 border-t border-red-200 dark:border-red-800"></div>
            @if(($category->products_count ?? 0) === 0 && ($category->children()->count() ?? 0) === 0)
                <div class="px-2">
                    <button wire:click="deleteCategory({{ $category->id }})"
                            wire:confirm="⚠️ UWAGA! Czy na pewno chcesz nieodwracalnie usunąć kategorię '{{ $category->name }}'? Ta operacja nie może zostać cofnięta!"
                            @click="closeDropdown()"
                            class="group w-full flex items-center px-4 py-3 text-sm font-medium text-red-700 dark:text-red-400
                                   hover:bg-gradient-to-r hover:from-red-50 hover:to-rose-50 dark:hover:from-red-900/20 dark:hover:to-rose-900/20
                                   hover:text-red-800 dark:hover:text-red-300 rounded-2xl transition-all duration-300 transform hover:scale-105 border-2 border-transparent hover:border-red-200 dark:hover:border-red-700"
                            role="menuitem">
                        <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center mr-4
                                   group-hover:bg-red-200 dark:group-hover:bg-red-800/50 transition-colors duration-300">
                            <i class="fas fa-trash text-red-600 dark:text-red-400"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold">Usuń kategorię</div>
                            <div class="text-xs text-red-500 dark:text-red-400">Operacja nieodwracalna!</div>
                        </div>
                        <i class="fas fa-exclamation-triangle text-red-500 animate-pulse"></i>
                    </button>
                </div>
            @else
                <div class="px-6 py-4 bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/10 dark:to-rose-900/10 mx-2 rounded-2xl border border-red-200 dark:border-red-800">
                    <div class="flex items-center text-sm text-red-700 dark:text-red-400">
                        <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-shield-alt text-red-600 dark:text-red-400"></i>
                        </div>
                        <div>
                            <div class="font-semibold">Usuwanie zablokowane</div>
                            <div class="text-xs">
                                @if(($category->products_count ?? 0) > 0)
                                    Kategoria zawiera {{ $category->products_count }} {{ $category->products_count === 1 ? 'produkt' : 'produktów' }}
                                @elseif(($category->children()->count() ?? 0) > 0)
                                    Kategoria zawiera {{ $category->children()->count() }} {{ $category->children()->count() === 1 ? 'podkategorię' : 'podkategorii' }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Enhanced Alpine.js Component --}}
<script>
function enhancedCategoryActions(categoryId) {
    return {
        open: false,
        categoryId: categoryId,
        loading: false,

        init() {
            // Initialize with any setup needed
            console.log('Enhanced category actions initialized for:', categoryId);
        },

        toggleDropdown() {
            this.open = !this.open;

            if (this.open) {
                // Position dropdown relative to button
                this.$nextTick(() => {
                    const button = this.$el.querySelector('button');
                    const dropdown = document.body.querySelector('.dropdown-fix');
                    if (button && dropdown) {
                        const buttonRect = button.getBoundingClientRect();
                        dropdown.style.top = `${buttonRect.bottom + 8}px`;
                        dropdown.style.left = `${buttonRect.right - 320}px`; // 320px = w-80
                        dropdown.dataset.categoryId = categoryId; // Mark dropdown with category ID
                    }
                });
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }

            // Focus management for accessibility
            if (this.open) {
                this.$nextTick(() => {
                    const dropdown = document.body.querySelector(`[data-category-id="${categoryId}"]`);
                    if (dropdown) {
                        const firstFocusable = dropdown.querySelector('button[role="menuitem"]');
                        if (firstFocusable) {
                            firstFocusable.focus();
                        }
                    }
                });
            }
        },

        closeDropdown() {
            this.open = false;
            document.body.style.overflow = '';
        },

        handleKeyDown(event) {
            if (!this.open) return;

            switch (event.key) {
                case 'Escape':
                    this.closeDropdown();
                    break;
                case 'ArrowDown':
                    event.preventDefault();
                    this.focusNextMenuItem();
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    this.focusPreviousMenuItem();
                    break;
            }
        },

        focusNextMenuItem() {
            const menuItems = this.$el.querySelectorAll('button[role="menuitem"]');
            const currentIndex = Array.from(menuItems).findIndex(item => item === document.activeElement);
            const nextIndex = currentIndex + 1 < menuItems.length ? currentIndex + 1 : 0;
            menuItems[nextIndex].focus();
        },

        focusPreviousMenuItem() {
            const menuItems = this.$el.querySelectorAll('button[role="menuitem"]');
            const currentIndex = Array.from(menuItems).findIndex(item => item === document.activeElement);
            const previousIndex = currentIndex - 1 >= 0 ? currentIndex - 1 : menuItems.length - 1;
            menuItems[previousIndex].focus();
        }
    }
}

// Global keyboard shortcuts for enhanced UX
document.addEventListener('keydown', (e) => {
    // Close all dropdowns on Escape
    if (e.key === 'Escape') {
        const openDropdowns = document.querySelectorAll('[x-data*="enhancedCategoryActions"]');
        openDropdowns.forEach(dropdown => {
            const alpineData = Alpine.$data(dropdown);
            if (alpineData && alpineData.open) {
                alpineData.closeDropdown();
            }
        });
    }
});
</script>