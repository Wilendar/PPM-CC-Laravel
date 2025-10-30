{{-- Enhanced Category Actions - BULLETPROOF JavaScript Portal Pattern --}}
<div x-data="bulletproofCategoryActions({{ $category->id }})"
     x-init="init()"
     class="relative inline-block">

    {{-- Enhanced Actions Button --}}
    <button @click="toggleDropdown()"
            @click.away="closeDropdown()"
            class="group inline-flex items-center px-4 py-3 border-2 border-gray-200 dark:border-gray-600
                   rounded-2xl bg-gray-700 text-sm font-semibold text-gray-300
                   hover:border-blue-300 dark:hover:border-blue-500 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50
                   dark:hover:from-blue-900/20 dark:hover:to-indigo-900/20 hover:text-blue-700 dark:hover:text-blue-300
                   transition-all duration-300 transform hover:scale-105 shadow-md hover:shadow-lg"
            :class="{ 'ring-4 ring-blue-500/20 border-blue-500': open }"
            :id="'category-btn-' + {{ $category->id }}">
        <i class="fas fa-ellipsis-v group-hover:text-blue-600 transition-colors duration-300"></i>
    </button>

    {{-- Dropdown Content Template (hidden, will be portalled) --}}
    <div x-show="false" :id="'dropdown-template-' + {{ $category->id }}" class="hidden">
        {{-- Dropdown Header --}}
        <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20
                   rounded-t-3xl border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-lg flex items-center justify-center">
                        <i class="fas fa-folder text-white text-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-white text-lg">{{ $category->name }}</h3>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            ID: {{ $category->id }} • Poziom: {{ $category->level ?? 1 }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Category Path --}}
            @if($category->parent)
            <div class="mt-4 flex items-center text-xs text-gray-600 dark:text-gray-300">
                <i class="fas fa-sitemap mr-2"></i>
                <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-md">
                    {{ $category->parent->name }} → {{ $category->name }}
                </span>
            </div>
            @endif

            {{-- Quick Stats --}}
            <div class="mt-4 grid grid-cols-2 gap-4 text-center">
                <div class="bg-gray-800 p-3 rounded-xl border border-gray-200 dark:border-gray-600">
                    <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $category->children_count ?? 0 }}</div>
                    <div class="text-xs text-gray-500">Podkategorie</div>
                </div>
                <div class="bg-gray-800 p-3 rounded-xl border border-gray-200 dark:border-gray-600">
                    <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ $category->products_count ?? 0 }}</div>
                    <div class="text-xs text-gray-500">Produkty</div>
                </div>
            </div>
        </div>

        {{-- Dropdown Actions --}}
        <div class="p-4 space-y-2">
            {{-- Edit Category --}}
            <button @click="editCategory({{ $category->id }})"
                    class="w-full flex items-center space-x-3 px-4 py-3 text-sm text-gray-200
                           hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-xl transition-colors duration-200 group"
                    role="menuitem">
                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center group-hover:bg-blue-200 dark:group-hover:bg-blue-800/50 transition-colors duration-200">
                    <i class="fas fa-edit text-blue-600 dark:text-blue-400"></i>
                </div>
                <div class="flex-1 text-left">
                    <div class="font-medium">Edytuj kategorię</div>
                    <div class="text-xs text-gray-500">Zmień nazwę, opis i ustawienia</div>
                </div>
            </button>

            {{-- Add Subcategory --}}
            <button @click="addSubcategory({{ $category->id }})"
                    class="w-full flex items-center space-x-3 px-4 py-3 text-sm text-gray-200
                           hover:bg-green-50 dark:hover:bg-green-900/20 rounded-xl transition-colors duration-200 group"
                    role="menuitem">
                <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center group-hover:bg-green-200 dark:group-hover:bg-green-800/50 transition-colors duration-200">
                    <i class="fas fa-plus text-green-600 dark:text-green-400"></i>
                </div>
                <div class="flex-1 text-left">
                    <div class="font-medium">Dodaj podkategorię</div>
                    <div class="text-xs text-gray-500">Utwórz kategorię potomną</div>
                </div>
            </button>

            {{-- Toggle Active Status --}}
            @if($category->is_active ?? true)
                <button @click="toggleStatus({{ $category->id }}, false)"
                        class="w-full flex items-center space-x-3 px-4 py-3 text-sm text-gray-200
                               hover:bg-yellow-50 dark:hover:bg-yellow-900/20 rounded-xl transition-colors duration-200 group"
                        role="menuitem">
                    <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center group-hover:bg-yellow-200 dark:group-hover:bg-yellow-800/50 transition-colors duration-200">
                        <i class="fas fa-eye-slash text-yellow-600 dark:text-yellow-400"></i>
                    </div>
                    <div class="flex-1 text-left">
                        <div class="font-medium">Ukryj kategorię</div>
                        <div class="text-xs text-gray-500">Dezaktywuj w sklepach</div>
                    </div>
                </button>
            @else
                <button @click="toggleStatus({{ $category->id }}, true)"
                        class="w-full flex items-center space-x-3 px-4 py-3 text-sm text-gray-200
                               hover:bg-green-50 dark:hover:bg-green-900/20 rounded-xl transition-colors duration-200 group"
                        role="menuitem">
                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center group-hover:bg-green-200 dark:group-hover:bg-green-800/50 transition-colors duration-200">
                        <i class="fas fa-eye text-green-600 dark:text-green-400"></i>
                    </div>
                    <div class="flex-1 text-left">
                        <div class="font-medium">Pokaż kategorię</div>
                        <div class="text-xs text-gray-500">Aktywuj w sklepach</div>
                    </div>
                </button>
            @endif

            {{-- Delete Category --}}
            <hr class="my-2 border-gray-200 dark:border-gray-600">
            <button @click="deleteCategory({{ $category->id }})"
                    class="w-full flex items-center space-x-3 px-4 py-3 text-sm text-gray-200
                           hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-colors duration-200 group"
                    role="menuitem">
                <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center group-hover:bg-red-200 dark:group-hover:bg-red-800/50 transition-colors duration-200">
                    <i class="fas fa-trash text-red-600 dark:text-red-400"></i>
                </div>
                <div class="flex-1 text-left">
                    <div class="font-medium">Usuń kategorię</div>
                    <div class="text-xs text-gray-500">Uwaga: Nieodwracalne!</div>
                </div>
            </button>
        </div>

        {{-- Dropdown Footer --}}
        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-800/50 rounded-b-3xl border-t border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>Ostatnia modyfikacja: {{ $category->updated_at ? $category->updated_at->diffForHumans() : 'Nigdy' }}</span>
                <button @click="closeDropdown()"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function bulletproofCategoryActions(categoryId) {
    return {
        open: false,
        dropdownElement: null,

        init() {
            console.log('Bulletproof category actions initialized for:', categoryId);
        },

        toggleDropdown() {
            if (this.open) {
                this.closeDropdown();
            } else {
                this.openDropdown();
            }
        },

        openDropdown() {
            this.closeAllOtherDropdowns(); // Close other open dropdowns

            // Create dropdown portal in body
            this.createDropdownPortal();
            this.open = true;
        },

        closeDropdown() {
            this.open = false;
            this.removeDropdownPortal();
        },

        createDropdownPortal() {
            // Remove existing portal if any
            this.removeDropdownPortal();

            // Get button position
            const button = document.getElementById(`category-btn-${categoryId}`);
            const template = document.getElementById(`dropdown-template-${categoryId}`);

            if (!button || !template) return;

            const buttonRect = button.getBoundingClientRect();

            // Create dropdown element
            this.dropdownElement = document.createElement('div');
            this.dropdownElement.className = 'fixed w-80 rounded-3xl shadow-2xl bg-gray-800 ring-1 ring-black ring-opacity-5 border-2 border-gray-100 dark:border-gray-700';
            this.dropdownElement.style.cssText = `
                z-index: 999999 !important;
                top: ${buttonRect.bottom + 8}px;
                left: ${Math.max(16, buttonRect.right - 320)}px;
                transform: scale(0.9);
                opacity: 0;
                transition: all 0.2s ease-out;
            `;
            this.dropdownElement.setAttribute('data-category-id', categoryId);

            // Clone content from template
            this.dropdownElement.innerHTML = template.innerHTML;

            // Append to body
            document.body.appendChild(this.dropdownElement);

            // Animate in
            requestAnimationFrame(() => {
                this.dropdownElement.style.transform = 'scale(1)';
                this.dropdownElement.style.opacity = '1';
            });

            // Add click outside listener
            setTimeout(() => {
                document.addEventListener('click', this.handleClickOutside);
            }, 100);

            // Add escape key listener
            document.addEventListener('keydown', this.handleEscapeKey);
        },

        removeDropdownPortal() {
            if (this.dropdownElement) {
                // Animate out
                this.dropdownElement.style.transform = 'scale(0.9)';
                this.dropdownElement.style.opacity = '0';

                setTimeout(() => {
                    if (this.dropdownElement && this.dropdownElement.parentNode) {
                        this.dropdownElement.parentNode.removeChild(this.dropdownElement);
                    }
                    this.dropdownElement = null;
                }, 200);
            }

            document.removeEventListener('click', this.handleClickOutside);
            document.removeEventListener('keydown', this.handleEscapeKey);
        },

        handleClickOutside: (event) => {
            const button = document.getElementById(`category-btn-${categoryId}`);
            const dropdown = document.querySelector(`[data-category-id="${categoryId}"]`);

            if (button && dropdown &&
                !button.contains(event.target) &&
                !dropdown.contains(event.target)) {
                // Find Alpine component and close dropdown
                const component = Alpine.$data(button.closest('[x-data*="bulletproofCategoryActions"]'));
                if (component) {
                    component.closeDropdown();
                }
            }
        },

        handleEscapeKey: (event) => {
            if (event.key === 'Escape') {
                // Find Alpine component and close dropdown
                const button = document.getElementById(`category-btn-${categoryId}`);
                if (button) {
                    const component = Alpine.$data(button.closest('[x-data*="bulletproofCategoryActions"]'));
                    if (component) {
                        component.closeDropdown();
                    }
                }
            }
        },

        closeAllOtherDropdowns() {
            // Close all other category dropdowns
            document.querySelectorAll('[data-category-id]').forEach(dropdown => {
                if (dropdown.getAttribute('data-category-id') !== categoryId.toString()) {
                    const button = document.getElementById(`category-btn-${dropdown.getAttribute('data-category-id')}`);
                    if (button) {
                        const component = Alpine.$data(button.closest('[x-data*="bulletproofCategoryActions"]'));
                        if (component) {
                            component.closeDropdown();
                        }
                    }
                }
            });
        },

        // Action methods
        editCategory(id) {
            this.closeDropdown();
            window.location.href = `/admin/products/categories/${id}/edit`;
        },

        addSubcategory(parentId) {
            this.closeDropdown();
            window.location.href = `/admin/products/categories/create?parent=${parentId}`;
        },

        toggleStatus(id, newStatus) {
            this.closeDropdown();
            // Livewire call
            Livewire.emit('toggleCategoryStatus', id, newStatus);
        },

        deleteCategory(id) {
            this.closeDropdown();
            if (confirm('Czy na pewno chcesz usunąć tę kategorię? Ta operacja jest nieodwracalna!')) {
                Livewire.emit('deleteCategory', id);
            }
        }
    }
}

// Global cleanup on page unload
window.addEventListener('beforeunload', () => {
    document.querySelectorAll('[data-category-id]').forEach(dropdown => {
        if (dropdown.parentNode) {
            dropdown.parentNode.removeChild(dropdown);
        }
    });
});
</script>