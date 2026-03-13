<div class="min-h-screen bg-main-gradient">
    {{-- Header Section (header-bar includes filters-panel and bulk-actions-bar) --}}
    @include('livewire.products.listing.partials.header-bar')

    {{-- Sync Status Polling - refreshes integration status badges after job completion --}}
    <div wire:poll.5s="checkSyncJobStatuses"></div>

    {{-- Real-Time Progress Tracking - wire:poll MUST be outside @if to work! --}}
    <div wire:poll.3s="checkForPendingCategoryPreviews">
        @if(!empty($this->activeJobProgress))
            <div class="px-6 sm:px-8 lg:px-12 pt-6">
                <div class="mb-6 space-y-3">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wide flex items-center">
                        <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Aktywne Operacje
                    </h3>

                    @foreach($this->activeJobProgress as $job)
                        <livewire:components.job-progress-bar
                            :key="'job-progress-' . $job['id']"
                            :jobId="(int)$job['id']" />
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Main Content with Category Panel --}}
    <div class="product-list-with-panel"
         x-data="categoryPanel({
            tree: @js($this->categoryTreeForPanel),
            productMap: @js($this->getProductCategoryMapForPanel()),
            initialFilter: @js($categoryFilter)
         })"
         @toggle-category-panel.window="togglePanel($event.detail.side)"
         @product-hover.window="onProductHover($event.detail.id)">

        {{-- Left Panel --}}
        <div x-show="panelSide === 'left'" x-cloak>
            @include('livewire.products.listing.partials.category-panel', ['side' => 'left'])
        </div>

        <div class="product-list-with-panel__content px-4 sm:px-6 lg:px-8 py-6">
            @if($viewMode === 'table')
                @include('livewire.products.listing.partials.table-view')
            @else
                @include('livewire.products.listing.partials.grid-view')
            @endif
        </div>

        {{-- Right Panel --}}
        <div x-show="panelSide === 'right'" x-cloak>
            @include('livewire.products.listing.partials.category-panel', ['side' => 'right'])
        </div>
    </div>

    @include('livewire.products.listing.partials.preview-modal')

    {{-- Loading Overlay - only for heavy operations (bulk actions, sync) --}}
    <div wire:loading.delay.longer
         wire:target="bulkAction, syncSelectedToPrestaShop, deleteSelected, restoreSelected"
         class="fixed top-20 right-4 z-50">
        <div class="card glass-effect rounded-lg p-3 flex items-center space-x-2 shadow-lg">
            <svg class="animate-spin h-4 w-4 text-orange-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-primary text-sm">Przetwarzanie...</span>
        </div>
    </div>

    @include('livewire.products.listing.partials.quick-send-modal')
    @include('livewire.products.listing.partials.delete-modal')
    @include('livewire.products.listing.partials.bulk-delete-modal')
    @include('livewire.products.listing.partials.bulk-assign-categories-modal')
    @include('livewire.products.listing.partials.bulk-remove-categories-modal')
    @include('livewire.products.listing.partials.bulk-move-categories-modal')
    @include('livewire.products.listing.partials.import-prestashop-modal')
    @include('livewire.products.listing.partials.category-analysis-overlay')
    @include('livewire.products.listing.partials.erp-import-modal')

{{-- MPP TRADE Custom Styles --}}
<style>
/* MPP TRADE Color Variables */
:root {
    --mpp-primary: #e0ac7e;
    --mpp-primary-dark: #d1975a;
    --bg-card: rgba(31, 41, 55, 0.8);
    --bg-card-hover: rgba(55, 65, 81, 0.8);
    --bg-input: #374151;
    --border-primary: rgba(75, 85, 99, 0.2);
    --text-primary: #ffffff;
    --text-secondary: #f3f4f6;
    --text-muted: #d1d5db;
}

/* Dark theme main gradient */
.bg-main-gradient {
    background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
}

/* Glass morphism effect */
.glass-effect {
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    background: var(--bg-card);
}

/* Card styles */
.card {
    background: var(--bg-card);
    border: 1px solid var(--border-primary);
}

.card-hover:hover {
    background: var(--bg-card-hover);
    transform: translateY(-2px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
}

/* Button styles */
.btn-primary {
    background: linear-gradient(45deg, var(--mpp-primary), var(--mpp-primary-dark));
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: linear-gradient(45deg, var(--mpp-primary-dark), #c08449);
    transform: scale(1.05);
    box-shadow: 0 10px 25px rgba(224, 172, 126, 0.3);
}

.btn-secondary {
    background: var(--bg-card);
    border: 1px solid var(--border-primary);
    color: var(--text-secondary);
}

/* Form input styles */
.form-input {
    background: var(--bg-input) !important;
    border: 1px solid var(--border-primary) !important;
    color: var(--text-primary) !important;
}

.form-input:focus {
    border-color: var(--mpp-primary) !important;
    box-shadow: 0 0 0 3px rgba(224, 172, 126, 0.1) !important;
    outline: none !important;
}

/* Text color utilities */
.text-primary { color: var(--text-primary) !important; }
.text-secondary { color: var(--text-secondary) !important; }
.text-muted { color: var(--text-muted) !important; }

/* Background utilities */
.bg-card { background: var(--bg-card) !important; }
.bg-card-hover { background: var(--bg-card-hover) !important; }
.bg-input { background: var(--bg-input) !important; }

/* Border utilities */
.border-primary { border-color: var(--border-primary) !important; }

/* Shadow utilities */
.shadow-soft {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1),
                0 4px 6px -4px rgba(0, 0, 0, 0.1);
}

/* Orange focus ring for accessibility */
.focus\:ring-orange-500:focus {
    --tw-ring-color: var(--mpp-primary) !important;
}

.focus\:ring-orange-500:focus {
    box-shadow: 0 0 0 3px rgba(224, 172, 126, 0.5) !important;
}

/* Status colors with orange theme */
.text-orange-500 { color: var(--mpp-primary) !important; }
.text-orange-400 { color: #f4b986 !important; }
.bg-orange-500 { background-color: var(--mpp-primary) !important; }
.bg-orange-50 { background-color: #fef7f0 !important; }
.dark\:bg-orange-900\/20 { background-color: rgba(124, 45, 18, 0.2) !important; }
.border-orange-200 { border-color: #fed7aa !important; }
.dark\:border-orange-800 { border-color: #9a3412 !important; }
.text-orange-900 { color: #7c2d12 !important; }
.dark\:text-orange-200 { color: #fed7aa !important; }

/* Smooth transitions - targeted selectors only (not * which forces layout on ALL elements) */
.product-list-row,
.btn-enterprise-primary,
.btn-enterprise-secondary,
.btn-enterprise-danger,
.enterprise-card,
.form-input-enterprise,
input[type="checkbox"] {
    transition: all 0.3s ease;
}

/* Custom checkbox styling */
input[type="checkbox"] {
    accent-color: var(--mpp-primary) !important;
}
</style>
</div>
