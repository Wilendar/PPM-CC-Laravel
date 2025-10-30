{{-- Category Actions Dropdown - Auto positioning to avoid cutoff --}}
<div x-data="{
    open: false,
    dropdownPosition: 'bottom',
    checkPosition() {
        this.$nextTick(() => {
            const button = this.$refs.button;
            const dropdown = this.$refs.dropdown;
            if (button && dropdown) {
                const rect = button.getBoundingClientRect();
                const spaceBelow = window.innerHeight - rect.bottom;
                const dropdownHeight = 400;
                this.dropdownPosition = spaceBelow < dropdownHeight ? 'top' : 'bottom';
            }
        });
    },
    toggle() {
        this.open = !this.open;
        if (this.open) this.checkPosition();
    }
}" @click.away="open = false" class="relative inline-block text-left">
    {{-- Actions Button --}}
    <button @click="toggle()"
            x-ref="button"
            class="inline-flex items-center px-3 py-1 border border-gray-600 rounded-md bg-gray-700 text-sm font-medium text-gray-300 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-150"
            :class="{ 'ring-2 ring-blue-500 ring-opacity-50': open }">
        <i class="fas fa-ellipsis-v transition-transform duration-150" :class="{ 'rotate-90': open }"></i>
    </button>

    {{-- Dropdown Menu - Auto positioning --}}
    <div x-show="open"
         x-ref="dropdown"
         x-cloak
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute right-0 w-56 rounded-md shadow-lg bg-gray-700 ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
         :class="{
             'bottom-full mb-2 origin-bottom-right': dropdownPosition === 'top',
             'top-full mt-2 origin-top-right': dropdownPosition === 'bottom'
         }">

        <div class="py-1" role="menu">
            {{-- View/Edit Action --}}
            <button wire:click="editCategory({{ $category->id }})"
                    @click="open = false"
                    class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center"
                    role="menuitem">
                <i class="fas fa-edit mr-3 text-blue-600"></i>
                Edytuj kategorię
            </button>

            {{-- Add Subcategory --}}
            @if($category->level < \App\Models\Category::MAX_LEVEL)
                <button wire:click="createCategory({{ $category->id }})"
                        @click="open = false"
                        class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center"
                        role="menuitem">
                    <i class="fas fa-plus mr-3 text-green-600"></i>
                    Dodaj podkategorię
                </button>
            @else
                <div class="px-4 py-2 text-sm text-gray-400 dark:text-gray-500 flex items-center">
                    <i class="fas fa-ban mr-3"></i>
                    Maksymalna głębokość
                </div>
            @endif

            {{-- Divider --}}
            <div class="border-t border-gray-100 dark:border-gray-600"></div>

            {{-- Toggle Active Status --}}
            @if($category->is_active)
                <button wire:click="bulkDeactivate"
                        wire:confirm="Czy na pewno chcesz dezaktywować tę kategorię?"
                        @click="open = false"
                        class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center"
                        role="menuitem">
                    <i class="fas fa-eye-slash mr-3 text-yellow-600"></i>
                    Dezaktywuj
                </button>
            @else
                <button wire:click="bulkActivate"
                        @click="open = false"
                        class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center"
                        role="menuitem">
                    <i class="fas fa-eye mr-3 text-green-600"></i>
                    Aktywuj
                </button>
            @endif

            {{-- Category Statistics --}}
            <div class="border-t border-gray-100 dark:border-gray-600"></div>
            <div class="px-4 py-2">
                <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                    <div class="flex justify-between">
                        <span>Produkty:</span>
                        <span class="font-medium">{{ $category->products_count ?? 0 }}</span>
                    </div>
                    @if(($category->primary_products_count ?? 0) > 0)
                        <div class="flex justify-between">
                            <span>Główne:</span>
                            <span class="font-medium text-green-600">{{ $category->primary_products_count }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span>Poziom:</span>
                        <span class="font-medium">{{ $category->level + 1 }}</span>
                    </div>
                    @if($category->children && $category->children->count() > 0)
                        <div class="flex justify-between">
                            <span>Podkategorie:</span>
                            <span class="font-medium">{{ $category->children->count() }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Advanced Actions --}}
            <div class="border-t border-gray-100 dark:border-gray-600"></div>

            {{-- Copy Category ID --}}
            <button x-data="{ copied: false }"
                    @click="
                        navigator.clipboard.writeText('{{ $category->id }}');
                        copied = true;
                        setTimeout(() => copied = false, 2000);
                        open = false;
                    "
                    class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center"
                    role="menuitem">
                <i class="fas fa-copy mr-3 text-gray-600"></i>
                <span x-text="copied ? 'Skopiowano!' : 'Kopiuj ID ({{ $category->id }})'"></span>
            </button>

            {{-- View Category Path --}}
            @if($category->ancestors && $category->ancestors->count() > 0)
                <div class="px-4 py-2 border-t border-gray-100 dark:border-gray-600">
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        <div class="font-medium mb-1">Ścieżka:</div>
                        <div class="truncate">
                            {{ $category->ancestors->pluck('name')->join(' > ') }} > <strong>{{ $category->name }}</strong>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Danger Zone --}}
            @if(($category->products_count ?? 0) === 0 && ($category->children()->count() ?? 0) === 0)
                <div class="border-t border-gray-100 dark:border-gray-600"></div>
                <button wire:click="deleteCategory({{ $category->id }})"
                        wire:confirm="Czy na pewno chcesz usunąć kategorię '{{ $category->name }}'? Ta operacja jest nieodwracalna."
                        @click="open = false"
                        class="w-full text-left px-4 py-2 text-sm text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center"
                        role="menuitem">
                    <i class="fas fa-trash mr-3 text-red-600"></i>
                    Usuń kategorię
                </button>
            @else
                <div class="px-4 py-2 border-t border-gray-100 dark:border-gray-600">
                    <div class="text-xs text-gray-400 dark:text-gray-500 flex items-center">
                        <i class="fas fa-info-circle mr-2"></i>
                        @if(($category->products_count ?? 0) > 0)
                            Nie można usunąć - zawiera produkty
                        @elseif(($category->children()->count() ?? 0) > 0)
                            Nie można usunąć - zawiera podkategorie
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>