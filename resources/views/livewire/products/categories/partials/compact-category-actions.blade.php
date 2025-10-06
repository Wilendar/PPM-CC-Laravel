{{-- Simplified Category Actions Dropdown - Auto positioning to avoid cutoff --}}
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
                const dropdownHeight = 250; // estimated dropdown height
                this.dropdownPosition = spaceBelow < dropdownHeight ? 'top' : 'bottom';
            }
        });
    },
    toggle() {
        this.open = !this.open;
        if (this.open) this.checkPosition();
    }
}" @click.away="open = false" class="relative inline-block text-left">
    {{-- Action Button --}}
    <button @click="toggle()"
            x-ref="button"
            type="button"
            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600
                   rounded-md bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300
                   hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2
                   focus:ring-offset-2 focus:ring-blue-500 text-sm transition-all duration-150
                   hover:shadow-md active:scale-95"
            :class="{ 'ring-2 ring-blue-500 ring-opacity-50': open }">
        <i class="fas fa-ellipsis-h transition-transform duration-150" :class="{ 'rotate-90': open }"></i>
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
         class="absolute right-0 w-56 rounded-lg shadow-lg
                bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 focus:outline-none
                border border-gray-200 dark:border-gray-600 z-50"
         :class="{
             'bottom-full mb-2 origin-bottom-right': dropdownPosition === 'top',
             'top-full mt-2 origin-top-right': dropdownPosition === 'bottom'
         }"

        <div class="py-1" role="menu">
            {{-- Edit Category --}}
            <a href="/admin/products/categories/{{ $category->id }}/edit"
               class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300
                      hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
               role="menuitem">
                <i class="fas fa-edit mr-3 text-blue-500"></i>
                Edytuj kategorię
            </a>

            {{-- Add Subcategory --}}
            <a href="/admin/products/categories/create?parent_id={{ $category->id }}"
               class="w-full text-left flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300
                      hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
               role="menuitem">
                <i class="fas fa-plus mr-3 text-green-500"></i>
                Dodaj podkategorię
            </a>

            {{-- Toggle Status --}}
            @if($category->is_active ?? true)
                <button wire:click="toggleStatus({{ $category->id }}, false)"
                        class="w-full text-left flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300
                               hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                        role="menuitem">
                    <i class="fas fa-eye-slash mr-3 text-yellow-500"></i>
                    Dezaktywuj
                </button>
            @else
                <button wire:click="toggleStatus({{ $category->id }}, true)"
                        class="w-full text-left flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300
                               hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                        role="menuitem">
                    <i class="fas fa-eye mr-3 text-green-500"></i>
                    Aktywuj
                </button>
            @endif

            {{-- Separator --}}
            <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>

            {{-- Delete Category --}}
            <button wire:click="deleteCategory({{ $category->id }})"
                    wire:confirm="Czy na pewno chcesz usunąć kategorię '{{ $category->name }}'? Ta operacja jest nieodwracalna!"
                    class="w-full text-left flex items-center px-4 py-2 text-sm text-red-600 dark:text-red-400
                           hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                    role="menuitem">
                <i class="fas fa-trash mr-3"></i>
                Usuń kategorię
            </button>
        </div>
    </div>
</div>