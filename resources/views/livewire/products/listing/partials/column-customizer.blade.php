{{-- Column Customizer Dropdown --}}
<div x-data="columnCustomizer()" class="relative">
    <button @click="open = !open"
            type="button"
            class="btn-secondary inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-300 whitespace-nowrap"
            title="Dostosuj kolumny">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>
        </svg>
        <span class="hidden sm:inline">Kolumny</span>
    </button>

    <div x-show="open"
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-2 w-56 bg-gray-800 border border-gray-700 rounded-lg shadow-xl z-50"
         style="display: none;">

        {{-- Header --}}
        <div class="p-3 border-b border-gray-700">
            <h4 class="text-sm font-semibold text-white">Widoczne kolumny</h4>
        </div>

        {{-- Column Toggles --}}
        <div class="p-2 space-y-1">
            <template x-for="col in columns" :key="col.key">
                <label class="flex items-center px-2 py-1.5 rounded hover:bg-gray-700/50 cursor-pointer transition-colors">
                    <input type="checkbox"
                           :checked="col.visible"
                           @change="toggleColumn(col.key)"
                           :disabled="col.required"
                           class="mr-2 rounded border-gray-600 text-orange-500 focus:ring-orange-500"
                           :class="{ 'opacity-50 cursor-not-allowed': col.required }">
                    <span class="text-sm text-gray-300" x-text="col.label"></span>
                    <span x-show="col.required" class="ml-1 text-xs text-gray-500">(wymagana)</span>
                </label>
            </template>
        </div>

        {{-- Reset --}}
        <div class="p-2 border-t border-gray-700">
            <button @click="resetToDefaults()"
                    class="w-full px-3 py-1.5 text-xs text-gray-400 hover:text-white text-center transition-colors">
                Przywroc domyslne
            </button>
        </div>
    </div>
</div>

@script
<script>
Alpine.data('columnCustomizer', () => ({
    open: false,
    columns: [
        { key: 'thumbnail', label: 'Miniaturka', visible: true, required: false },
        { key: 'sku', label: 'SKU', visible: true, required: true },
        { key: 'name', label: 'Nazwa', visible: true, required: true },
        { key: 'type', label: 'Typ', visible: true, required: false },
        { key: 'manufacturer', label: 'Producent', visible: true, required: false },
        { key: 'price', label: 'Cena', visible: true, required: false },
        { key: 'stock', label: 'Stan', visible: true, required: false },
        { key: 'status', label: 'Status', visible: true, required: false },
        { key: 'compliance', label: 'Zgodnosc', visible: true, required: false },
        { key: 'updated', label: 'Aktualizacja', visible: true, required: false },
        { key: 'actions', label: 'Akcje', visible: true, required: true },
    ],

    init() {
        const saved = localStorage.getItem('ppm_product_list_columns');
        if (saved) {
            try {
                const savedState = JSON.parse(saved);
                this.columns.forEach(col => {
                    if (savedState[col.key] !== undefined && !col.required) {
                        col.visible = savedState[col.key];
                    }
                });
            } catch(e) {
                // Ignore malformed localStorage data
            }
        }
        this.broadcastState();
    },

    toggleColumn(key) {
        const col = this.columns.find(c => c.key === key);
        if (col && !col.required) {
            col.visible = !col.visible;
            this.saveState();
            this.broadcastState();
        }
    },

    resetToDefaults() {
        this.columns.forEach(col => col.visible = true);
        localStorage.removeItem('ppm_product_list_columns');
        this.broadcastState();
    },

    saveState() {
        const state = {};
        this.columns.forEach(col => state[col.key] = col.visible);
        localStorage.setItem('ppm_product_list_columns', JSON.stringify(state));
    },

    broadcastState() {
        window.dispatchEvent(new CustomEvent('columns-changed', {
            detail: this.columns.reduce((acc, col) => {
                acc[col.key] = col.visible;
                return acc;
            }, {})
        }));
    },

    isVisible(key) {
        const col = this.columns.find(c => c.key === key);
        return col ? col.visible : true;
    }
}));
</script>
@endscript
