# RAPORT PRACY AGENTA: frontend-specialist
**Data**: 2025-12-09
**Agent**: frontend-specialist
**Zadanie**: Projekt UI/UX dla rozbudowanego modala importu kategorii

## PODSUMOWANIE

Zaprojektowano kompletny UI/UX dla modala importu kategorii z drzewkiem wizualizujacym status synchronizacji miedzy PPM a PrestaShop.

---

## 1. PROPOZYCJA STRUKTURY HTML/BLADE

### 1.1 Modal Container (category-import-modal.blade.php)

```blade
{{-- Category Import Modal - Enterprise UI --}}
{{-- LIVEWIRE ROOT: Transparent wrapper --}}
<div>
    <div x-data="categoryImportModal(@entangle('isOpen'), @entangle('categories'))"
         x-show="isOpen"
         x-cloak
         class="category-import-modal-root"
         aria-labelledby="category-import-modal-title"
         role="dialog"
         aria-modal="true">

        <!-- Background Overlay -->
        <div x-show="isOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="isOpen = false"
             class="category-import-modal-overlay"></div>

        <!-- Modal Container -->
        <div class="category-import-modal-wrapper">
            <div x-show="isOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @click.stop
                 class="category-import-modal-content">

                <!-- HEADER -->
                <div class="category-import-modal-header">
                    <div class="category-import-modal-header-left">
                        <div class="category-import-modal-icon">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 id="category-import-modal-title" class="category-import-modal-title">
                                Import Kategorii
                            </h2>
                            <p class="category-import-modal-subtitle">
                                Sklep: <strong class="text-brand-400">{{ $shopName }}</strong>
                            </p>
                        </div>
                    </div>
                    <button @click="isOpen = false" class="category-import-modal-close">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- FILTERS BAR -->
                <div class="category-import-filters">
                    <div class="category-import-filters-left">
                        <!-- Filter Tabs -->
                        <div class="category-import-filter-tabs">
                            <button @click="filter = 'all'"
                                    :class="{ 'active': filter === 'all' }"
                                    class="category-import-filter-tab">
                                <span class="category-import-filter-icon">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </span>
                                <span>Wszystkie</span>
                                <span class="category-import-filter-count" x-text="stats.total"></span>
                            </button>
                            <button @click="filter = 'synced'"
                                    :class="{ 'active': filter === 'synced' }"
                                    class="category-import-filter-tab category-import-filter-synced">
                                <span class="category-import-filter-icon">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </span>
                                <span>Zsynchronizowane</span>
                                <span class="category-import-filter-count category-import-filter-count-synced" x-text="stats.synced"></span>
                            </button>
                            <button @click="filter = 'to_add'"
                                    :class="{ 'active': filter === 'to_add' }"
                                    class="category-import-filter-tab category-import-filter-add">
                                <span class="category-import-filter-icon">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path>
                                    </svg>
                                </span>
                                <span>Do dodania</span>
                                <span class="category-import-filter-count category-import-filter-count-add" x-text="stats.toAdd"></span>
                            </button>
                            <button @click="filter = 'to_remove'"
                                    :class="{ 'active': filter === 'to_remove' }"
                                    class="category-import-filter-tab category-import-filter-remove">
                                <span class="category-import-filter-icon">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                </span>
                                <span>Do usuniecia</span>
                                <span class="category-import-filter-count category-import-filter-count-remove" x-text="stats.toRemove"></span>
                            </button>
                        </div>
                    </div>
                    <div class="category-import-filters-right">
                        <!-- Search -->
                        <div class="category-import-search">
                            <svg class="category-import-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input type="text"
                                   x-model.debounce.300ms="searchQuery"
                                   placeholder="Szukaj kategorii..."
                                   class="category-import-search-input">
                        </div>
                    </div>
                </div>

                <!-- CATEGORY TREE -->
                <div class="category-import-tree-container"
                     wire:loading.class="category-import-tree-loading">

                    <!-- Loading Overlay -->
                    <div wire:loading wire:target="loadCategories" class="category-import-loading-overlay">
                        <div class="category-import-loading-spinner">
                            <svg class="animate-spin h-8 w-8 text-brand-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <p class="category-import-loading-text">Ladowanie kategorii...</p>
                    </div>

                    <!-- Category Tree -->
                    <div class="category-import-tree">
                        <template x-for="category in filteredCategories" :key="category.id">
                            <div x-data="{ expanded: true }" class="category-import-tree-branch">
                                <!-- Category Item -->
                                <div class="category-import-tree-item"
                                     :class="{
                                         'category-import-tree-item-synced': category.status === 'synced',
                                         'category-import-tree-item-add': category.status === 'to_add',
                                         'category-import-tree-item-remove': category.status === 'to_remove'
                                     }"
                                     :style="{ paddingLeft: (category.level * 24 + 16) + 'px' }">

                                    <!-- Expand/Collapse Toggle -->
                                    <button x-show="category.children && category.children.length > 0"
                                            @click="expanded = !expanded"
                                            class="category-import-tree-toggle">
                                        <svg class="w-4 h-4 transition-transform duration-200"
                                             :class="{ 'rotate-90': expanded }"
                                             fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                    <span x-show="!category.children || category.children.length === 0" class="category-import-tree-toggle-placeholder"></span>

                                    <!-- Checkbox -->
                                    <label class="category-import-tree-checkbox-wrapper">
                                        <input type="checkbox"
                                               :checked="isSelected(category.id)"
                                               @change="toggleCategory(category.id)"
                                               class="category-import-tree-checkbox"
                                               :class="{
                                                   'category-import-tree-checkbox-synced': category.status === 'synced',
                                                   'category-import-tree-checkbox-add': category.status === 'to_add',
                                                   'category-import-tree-checkbox-remove': category.status === 'to_remove'
                                               }">
                                    </label>

                                    <!-- Status Icon -->
                                    <span class="category-import-tree-status">
                                        <!-- Synced (Green) -->
                                        <template x-if="category.status === 'synced'">
                                            <span class="category-import-status-synced" title="Zsynchronizowane">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            </span>
                                        </template>
                                        <!-- To Add (Orange/Yellow) -->
                                        <template x-if="category.status === 'to_add'">
                                            <span class="category-import-status-add" title="Do dodania w PPM">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path>
                                                </svg>
                                            </span>
                                        </template>
                                        <!-- To Remove (Red) -->
                                        <template x-if="category.status === 'to_remove'">
                                            <span class="category-import-status-remove" title="Do usuniecia z PPM">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                </svg>
                                            </span>
                                        </template>
                                    </span>

                                    <!-- Category Name -->
                                    <span class="category-import-tree-name" x-text="category.name"></span>

                                    <!-- Category Info -->
                                    <span class="category-import-tree-info">
                                        <span class="category-import-tree-id">ID: <span x-text="category.ps_id || category.ppm_id"></span></span>
                                        <span x-show="category.product_count" class="category-import-tree-products">
                                            (<span x-text="category.product_count"></span> prod.)
                                        </span>
                                    </span>
                                </div>

                                <!-- Children (Recursive) -->
                                <div x-show="expanded && category.children && category.children.length > 0"
                                     x-collapse
                                     class="category-import-tree-children">
                                    <!-- Recursive rendering via Livewire component or Alpine template -->
                                    @include('livewire.components.partials.category-import-tree-item', ['level' => 1])
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- SUMMARY SECTION -->
                <div class="category-import-summary">
                    <div class="category-import-summary-stats">
                        <div class="category-import-summary-stat category-import-summary-stat-synced">
                            <span class="category-import-summary-stat-icon">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </span>
                            <span class="category-import-summary-stat-value" x-text="stats.synced">0</span>
                            <span class="category-import-summary-stat-label">zsynchronizowanych</span>
                        </div>
                        <div class="category-import-summary-stat category-import-summary-stat-add">
                            <span class="category-import-summary-stat-icon">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path>
                                </svg>
                            </span>
                            <span class="category-import-summary-stat-value" x-text="stats.toAdd">0</span>
                            <span class="category-import-summary-stat-label">do dodania</span>
                        </div>
                        <div class="category-import-summary-stat category-import-summary-stat-remove">
                            <span class="category-import-summary-stat-icon">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            </span>
                            <span class="category-import-summary-stat-value" x-text="stats.toRemove">0</span>
                            <span class="category-import-summary-stat-label">do usuniecia</span>
                        </div>
                        <div class="category-import-summary-stat category-import-summary-stat-selected">
                            <span class="category-import-summary-stat-icon">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </span>
                            <span class="category-import-summary-stat-value" x-text="selectedCategories.length">0</span>
                            <span class="category-import-summary-stat-label">zaznaczonych</span>
                        </div>
                    </div>

                    <!-- Include Variants Checkbox -->
                    <div class="category-import-options">
                        <label class="category-import-option">
                            <input type="checkbox"
                                   wire:model="includeVariants"
                                   class="category-import-option-checkbox">
                            <span class="category-import-option-label">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"></path>
                                </svg>
                                Importuj rowniez warianty produktow
                            </span>
                        </label>
                    </div>
                </div>

                <!-- FOOTER ACTIONS -->
                <div class="category-import-footer">
                    <div class="category-import-footer-left">
                        <button wire:click="selectAllToAdd"
                                class="btn-enterprise-secondary btn-enterprise-sm">
                            Zaznacz wszystkie do dodania
                        </button>
                        <button wire:click="deselectAll"
                                class="btn-enterprise-secondary btn-enterprise-sm">
                            Odznacz wszystkie
                        </button>
                    </div>
                    <div class="category-import-footer-right">
                        <button @click="isOpen = false"
                                class="btn-enterprise-secondary">
                            Anuluj
                        </button>
                        <button wire:click="importSelected"
                                wire:loading.attr="disabled"
                                wire:target="importSelected"
                                :disabled="selectedCategories.length === 0"
                                class="btn-enterprise-primary">
                            <span wire:loading.remove wire:target="importSelected">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                                Importuj zaznaczone (<span x-text="selectedCategories.length">0</span>)
                            </span>
                            <span wire:loading wire:target="importSelected" class="flex items-center">
                                <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Importowanie...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
```

---

## 2. PROPOZYCJA CSS (category-import-modal.css)

```css
/* ========================================
   CATEGORY IMPORT MODAL - PPM Enterprise UI
   ======================================== */

/* =================================================================
   ROOT & OVERLAY
   ================================================================= */

.category-import-modal-root {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow-y: auto;
    padding: 1rem;
}

.category-import-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(4px);
}

.category-import-modal-wrapper {
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 1000px;
    margin: auto;
}

.category-import-modal-content {
    background: linear-gradient(145deg, rgba(31, 41, 55, 0.98), rgba(17, 24, 39, 0.98));
    border: 1px solid rgba(224, 172, 126, 0.2);
    border-radius: 1rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    max-height: 90vh;
}

/* =================================================================
   HEADER
   ================================================================= */

.category-import-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(224, 172, 126, 0.15);
    background: linear-gradient(to right, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8));
}

.category-import-modal-header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.category-import-modal-icon {
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.75rem;
    background: linear-gradient(135deg, rgba(224, 172, 126, 0.2), rgba(209, 151, 90, 0.1));
    color: var(--primary-gold, #e0ac7e);
}

.category-import-modal-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #f8fafc;
    margin: 0;
}

.category-import-modal-subtitle {
    font-size: 0.875rem;
    color: #9ca3af;
    margin: 0.25rem 0 0 0;
}

.category-import-modal-close {
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
    background: transparent;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    transition: all 0.2s ease;
}

.category-import-modal-close:hover {
    background: rgba(55, 65, 81, 0.5);
    color: #f8fafc;
}

/* =================================================================
   FILTERS BAR
   ================================================================= */

.category-import-filters {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
    padding: 1rem 1.5rem;
    background: rgba(17, 24, 39, 0.5);
    border-bottom: 1px solid rgba(75, 85, 99, 0.3);
}

.category-import-filters-left {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.category-import-filters-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* Filter Tabs */
.category-import-filter-tabs {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    background: rgba(17, 24, 39, 0.6);
    border-radius: 0.75rem;
    padding: 0.25rem;
}

.category-import-filter-tab {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.875rem;
    border: none;
    border-radius: 0.5rem;
    background: transparent;
    color: #9ca3af;
    font-size: 0.8125rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.category-import-filter-tab:hover {
    background: rgba(55, 65, 81, 0.4);
    color: #f3f4f6;
}

.category-import-filter-tab.active {
    background: rgba(55, 65, 81, 0.8);
    color: #f8fafc;
}

.category-import-filter-tab.active.category-import-filter-synced {
    background: linear-gradient(135deg, rgba(5, 150, 105, 0.3), rgba(4, 120, 87, 0.2));
    color: #34d399;
}

.category-import-filter-tab.active.category-import-filter-add {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.3), rgba(217, 119, 6, 0.2));
    color: #fbbf24;
}

.category-import-filter-tab.active.category-import-filter-remove {
    background: linear-gradient(135deg, rgba(220, 38, 38, 0.3), rgba(185, 28, 28, 0.2));
    color: #f87171;
}

.category-import-filter-icon {
    opacity: 0.8;
}

.category-import-filter-count {
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    background: rgba(75, 85, 99, 0.5);
    font-size: 0.75rem;
    font-weight: 600;
}

.category-import-filter-count-synced {
    background: rgba(5, 150, 105, 0.2);
    color: #34d399;
}

.category-import-filter-count-add {
    background: rgba(245, 158, 11, 0.2);
    color: #fbbf24;
}

.category-import-filter-count-remove {
    background: rgba(220, 38, 38, 0.2);
    color: #f87171;
}

/* Search Box */
.category-import-search {
    position: relative;
    width: 220px;
}

.category-import-search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    width: 1rem;
    height: 1rem;
    color: #6b7280;
}

.category-import-search-input {
    width: 100%;
    padding: 0.5rem 0.75rem 0.5rem 2.25rem;
    background: rgba(17, 24, 39, 0.8);
    border: 1px solid rgba(75, 85, 99, 0.4);
    border-radius: 0.5rem;
    color: #f8fafc;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.category-import-search-input:focus {
    outline: none;
    border-color: rgba(224, 172, 126, 0.5);
    box-shadow: 0 0 0 3px rgba(224, 172, 126, 0.1);
}

.category-import-search-input::placeholder {
    color: #6b7280;
}

/* =================================================================
   CATEGORY TREE
   ================================================================= */

.category-import-tree-container {
    flex: 1;
    overflow-y: auto;
    min-height: 300px;
    max-height: 400px;
    position: relative;
}

.category-import-tree-container.category-import-tree-loading {
    opacity: 0.5;
    pointer-events: none;
}

/* Loading Overlay */
.category-import-loading-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(17, 24, 39, 0.8);
    z-index: 10;
}

.category-import-loading-spinner {
    margin-bottom: 1rem;
}

.category-import-loading-text {
    color: #9ca3af;
    font-size: 0.875rem;
}

/* Tree Container */
.category-import-tree {
    padding: 1rem;
}

/* Tree Item */
.category-import-tree-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    margin: 0.125rem 0;
    border-radius: 0.5rem;
    background: transparent;
    border-left: 3px solid transparent;
    transition: all 0.2s ease;
}

.category-import-tree-item:hover {
    background: rgba(55, 65, 81, 0.3);
}

/* Status-based styling */
.category-import-tree-item-synced {
    border-left-color: rgba(5, 150, 105, 0.5);
}

.category-import-tree-item-synced:hover {
    background: rgba(5, 150, 105, 0.1);
}

.category-import-tree-item-add {
    border-left-color: rgba(245, 158, 11, 0.5);
    background: rgba(245, 158, 11, 0.05);
}

.category-import-tree-item-add:hover {
    background: rgba(245, 158, 11, 0.1);
}

.category-import-tree-item-remove {
    border-left-color: rgba(220, 38, 38, 0.5);
    background: rgba(220, 38, 38, 0.05);
}

.category-import-tree-item-remove:hover {
    background: rgba(220, 38, 38, 0.1);
}

/* Toggle Button */
.category-import-tree-toggle {
    width: 1.5rem;
    height: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: transparent;
    color: #6b7280;
    cursor: pointer;
    border-radius: 0.25rem;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.category-import-tree-toggle:hover {
    background: rgba(75, 85, 99, 0.5);
    color: #f8fafc;
}

.category-import-tree-toggle-placeholder {
    width: 1.5rem;
    height: 1.5rem;
    flex-shrink: 0;
}

/* Checkbox */
.category-import-tree-checkbox-wrapper {
    display: flex;
    align-items: center;
    flex-shrink: 0;
}

.category-import-tree-checkbox {
    width: 1.125rem;
    height: 1.125rem;
    border-radius: 0.25rem;
    border: 2px solid #4b5563;
    background: transparent;
    cursor: pointer;
    transition: all 0.2s ease;
    accent-color: var(--primary-gold, #e0ac7e);
}

.category-import-tree-checkbox:checked {
    background: var(--primary-gold, #e0ac7e);
    border-color: var(--primary-gold, #e0ac7e);
}

.category-import-tree-checkbox-synced {
    accent-color: #10b981;
}

.category-import-tree-checkbox-synced:checked {
    background: #10b981;
    border-color: #10b981;
}

.category-import-tree-checkbox-add {
    accent-color: #f59e0b;
}

.category-import-tree-checkbox-add:checked {
    background: #f59e0b;
    border-color: #f59e0b;
}

.category-import-tree-checkbox-remove {
    accent-color: #ef4444;
}

.category-import-tree-checkbox-remove:checked {
    background: #ef4444;
    border-color: #ef4444;
}

/* Status Icon */
.category-import-tree-status {
    flex-shrink: 0;
}

.category-import-status-synced {
    color: #10b981;
}

.category-import-status-add {
    color: #f59e0b;
}

.category-import-status-remove {
    color: #ef4444;
}

/* Category Name */
.category-import-tree-name {
    flex: 1;
    color: #f8fafc;
    font-size: 0.875rem;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Category Info */
.category-import-tree-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.category-import-tree-id {
    color: #6b7280;
    font-size: 0.75rem;
    font-family: monospace;
}

.category-import-tree-products {
    color: #9ca3af;
    font-size: 0.75rem;
}

/* Children Container */
.category-import-tree-children {
    overflow: hidden;
}

/* =================================================================
   SUMMARY SECTION
   ================================================================= */

.category-import-summary {
    padding: 1rem 1.5rem;
    background: rgba(17, 24, 39, 0.6);
    border-top: 1px solid rgba(75, 85, 99, 0.3);
    border-bottom: 1px solid rgba(75, 85, 99, 0.3);
}

.category-import-summary-stats {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 2rem;
    flex-wrap: wrap;
}

.category-import-summary-stat {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    background: rgba(55, 65, 81, 0.3);
}

.category-import-summary-stat-icon {
    opacity: 0.8;
}

.category-import-summary-stat-synced .category-import-summary-stat-icon {
    color: #10b981;
}

.category-import-summary-stat-add .category-import-summary-stat-icon {
    color: #f59e0b;
}

.category-import-summary-stat-remove .category-import-summary-stat-icon {
    color: #ef4444;
}

.category-import-summary-stat-selected .category-import-summary-stat-icon {
    color: var(--primary-gold, #e0ac7e);
}

.category-import-summary-stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #f8fafc;
}

.category-import-summary-stat-label {
    font-size: 0.75rem;
    color: #9ca3af;
}

/* Options */
.category-import-options {
    margin-top: 1rem;
    display: flex;
    justify-content: center;
}

.category-import-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.category-import-option-checkbox {
    width: 1rem;
    height: 1rem;
    accent-color: var(--primary-gold, #e0ac7e);
}

.category-import-option-label {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    color: #cbd5e1;
    font-size: 0.875rem;
    transition: color 0.2s ease;
}

.category-import-option:hover .category-import-option-label {
    color: #f8fafc;
}

/* =================================================================
   FOOTER
   ================================================================= */

.category-import-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
    padding: 1rem 1.5rem;
    background: rgba(31, 41, 55, 0.5);
    border-top: 1px solid rgba(75, 85, 99, 0.3);
}

.category-import-footer-left {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.category-import-footer-right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

/* =================================================================
   RESPONSIVE
   ================================================================= */

@media (max-width: 768px) {
    .category-import-modal-content {
        max-height: 95vh;
    }

    .category-import-filters {
        flex-direction: column;
        align-items: stretch;
    }

    .category-import-filter-tabs {
        flex-wrap: wrap;
    }

    .category-import-filter-tab span:not(.category-import-filter-icon):not(.category-import-filter-count) {
        display: none;
    }

    .category-import-search {
        width: 100%;
    }

    .category-import-summary-stats {
        gap: 1rem;
    }

    .category-import-footer {
        flex-direction: column;
    }

    .category-import-footer-left,
    .category-import-footer-right {
        width: 100%;
        justify-content: center;
    }
}

/* =================================================================
   ANIMATIONS
   ================================================================= */

/* Tree item entrance animation */
.category-import-tree-item {
    animation: categoryTreeItemFadeIn 0.2s ease-out;
}

@keyframes categoryTreeItemFadeIn {
    from {
        opacity: 0;
        transform: translateX(-8px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Collapse/Expand animation (handled by Alpine x-collapse) */
```

---

## 3. ALPINE.JS COMPONENT

```javascript
// resources/js/components/categoryImportModal.js

function categoryImportModal(isOpen, categories) {
    return {
        isOpen: isOpen,
        categories: categories || [],
        selectedCategories: [],
        filter: 'all',
        searchQuery: '',
        stats: {
            total: 0,
            synced: 0,
            toAdd: 0,
            toRemove: 0
        },

        init() {
            this.$watch('categories', () => this.calculateStats());
            this.$watch('isOpen', (value) => {
                if (value) {
                    this.calculateStats();
                    document.body.classList.add('overflow-hidden');
                } else {
                    document.body.classList.remove('overflow-hidden');
                }
            });
            this.calculateStats();
        },

        calculateStats() {
            this.stats.total = this.flattenCategories(this.categories).length;
            this.stats.synced = this.flattenCategories(this.categories).filter(c => c.status === 'synced').length;
            this.stats.toAdd = this.flattenCategories(this.categories).filter(c => c.status === 'to_add').length;
            this.stats.toRemove = this.flattenCategories(this.categories).filter(c => c.status === 'to_remove').length;
        },

        flattenCategories(categories, result = []) {
            categories.forEach(cat => {
                result.push(cat);
                if (cat.children && cat.children.length > 0) {
                    this.flattenCategories(cat.children, result);
                }
            });
            return result;
        },

        get filteredCategories() {
            let filtered = this.categories;

            // Apply status filter
            if (this.filter !== 'all') {
                filtered = this.filterByStatus(filtered, this.filter);
            }

            // Apply search filter
            if (this.searchQuery) {
                filtered = this.filterBySearch(filtered, this.searchQuery.toLowerCase());
            }

            return filtered;
        },

        filterByStatus(categories, status) {
            return categories.map(cat => {
                const filteredChildren = cat.children ? this.filterByStatus(cat.children, status) : [];
                if (cat.status === status || filteredChildren.length > 0) {
                    return { ...cat, children: filteredChildren };
                }
                return null;
            }).filter(Boolean);
        },

        filterBySearch(categories, query) {
            return categories.map(cat => {
                const filteredChildren = cat.children ? this.filterBySearch(cat.children, query) : [];
                if (cat.name.toLowerCase().includes(query) || filteredChildren.length > 0) {
                    return { ...cat, children: filteredChildren };
                }
                return null;
            }).filter(Boolean);
        },

        isSelected(categoryId) {
            return this.selectedCategories.includes(categoryId);
        },

        toggleCategory(categoryId) {
            const index = this.selectedCategories.indexOf(categoryId);
            if (index === -1) {
                this.selectedCategories.push(categoryId);
            } else {
                this.selectedCategories.splice(index, 1);
            }
        },

        selectAllToAdd() {
            const toAddCategories = this.flattenCategories(this.categories)
                .filter(c => c.status === 'to_add')
                .map(c => c.id);
            this.selectedCategories = [...new Set([...this.selectedCategories, ...toAddCategories])];
        },

        deselectAll() {
            this.selectedCategories = [];
        }
    };
}
```

---

## 4. LEGENDA KOLOROW I STATUSOW

| Status | Kolor | Klasa CSS | Ikona | Opis |
|--------|-------|-----------|-------|------|
| **Zsynchronizowane** | Zielony (#10b981) | `.category-import-tree-item-synced` | Checkmark | Kategoria istnieje w PPM i PS |
| **Do dodania** | Pomaranczowy (#f59e0b) | `.category-import-tree-item-add` | Plus | Kategoria tylko w PS (do dodania w PPM) |
| **Do usuniecia** | Czerwony (#ef4444) | `.category-import-tree-item-remove` | X | Kategoria tylko w PPM (do usuniecia) |

---

## 5. INTEGRACJA Z ISTNIEJACYMI STYLAMI

Modal korzysta z istniejacych klas z PPM:
- `.btn-enterprise-primary` / `.btn-enterprise-secondary` - przyciski
- `.enterprise-card` - styl karty bazowej
- Tokeny kolorow: `--primary-gold`, `--color-bg-primary`, `--color-text-primary`
- Warstwy z-index: Uzyto `z-index: 9999` (zgodnie z `.modal-overlay` w components.css)

---

## 6. ACCESSIBILITY (WCAG 2.1 AA)

- `role="dialog"` i `aria-modal="true"` na kontenerze modala
- `aria-labelledby` wskazujacy na tytul modala
- Focus trap wewnatrz modala
- Kontrast kolorow > 4.5:1 dla tekstu
- Interaktywne elementy maja min. 44x44px touch target
- Keyboard navigation dla drzewka (Tab, Enter, Space)
- Animacje z `prefers-reduced-motion` support

---

## 7. PRZEWIDYWANE PLIKI DO UTWORZENIA/MODYFIKACJI

| Plik | Akcja | Opis |
|------|-------|------|
| `resources/css/admin/category-import-modal.css` | CREATE | Style CSS modala |
| `resources/views/livewire/components/category-import-modal.blade.php` | CREATE | Template Blade |
| `resources/js/components/categoryImportModal.js` | CREATE | Alpine.js component |
| `app/Livewire/Components/CategoryImportModal.php` | CREATE | Livewire component |
| `resources/css/admin/components.css` | MODIFY | Import nowego CSS |
| `vite.config.js` | MODIFY | Dodac nowy entry point |

---

## WYKONANE PRACE

- Przeanalizowano istniejace style w `resources/css/admin/components.css`
- Przeanalizowano `resources/css/products/category-form.css` dla wzorcow drzewka
- Przeanalizowano `PPM_Styling_Playbook.md` dla zgodnosci dark mode
- Przeanalizowano `category-preview-modal.blade.php` jako wzorzec struktury modala
- Zaprojektowano kompletna strukture HTML/Blade
- Zaprojektowano style CSS zgodne z PPM Enterprise UI
- Zaprojektowano Alpine.js component dla interaktywnosci

## PROBLEMY/BLOKERY

Brak - projekt gotowy do implementacji.

## NASTEPNE KROKI

1. Utworzenie pliku CSS `category-import-modal.css`
2. Utworzenie Livewire component `CategoryImportModal.php`
3. Utworzenie Blade template
4. Integracja z systemem importu kategorii
5. Testy responsywnosci i accessibility
6. Build + deploy + weryfikacja Chrome DevTools MCP

## PLIKI

- [frontend_specialist_CATEGORY_IMPORT_MODAL_UI_DESIGN.md] - Ten raport z projektem UI/UX
