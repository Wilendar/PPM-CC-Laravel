{{-- ProductForm Component - Advanced Product Create/Edit Form --}}
{{-- CSS loaded via admin layout --}}

{{-- ETAP_13.6: Job Monitoring with CONDITIONAL Polling (PERFORMANCE FIX 2025-11-27) --}}
{{-- FIX 2025-11-27: Use $wire.activeJobStatus for reactive checking instead of static Blade value --}}
{{-- Previous bug: isJobActive() used static '{{ $activeJobStatus }}' which didn't update after Livewire changes --}}
<div
    x-data="{
        pollingInterval: null,
        pollCount: 0,
        maxPolls: 120,
        async isJobActive() {
            const status = await $wire.get('activeJobStatus');
            console.log('[POLL DEBUG] Checking job status:', status, 'pollCount:', this.pollCount);
            return status && status !== 'completed' && status !== 'failed' && status !== '';
        },
        async startPolling() {
            if (this.pollingInterval) {
                console.log('[POLL DEBUG] Polling already active');
                return;
            }
            console.log('[POLL DEBUG] Starting polling...');
            this.pollCount = 0;
            this.pollingInterval = setInterval(async () => {
                this.pollCount++;
                if (this.pollCount > this.maxPolls) {
                    console.log('[POLL DEBUG] Max polls reached, stopping');
                    this.stopPolling();
                    return;
                }
                const active = await this.isJobActive();
                if (active) {
                    console.log('[POLL DEBUG] Job still active, calling checkJobStatus');
                    $wire.checkJobStatus();
                } else {
                    console.log('[POLL DEBUG] Job no longer active, stopping polling');
                    this.stopPolling();
                }
            }, 5000);
        },
        stopPolling() {
            if (this.pollingInterval) {
                console.log('[POLL DEBUG] Stopping polling');
                clearInterval(this.pollingInterval);
                this.pollingInterval = null;
            }
        }
    }"
    x-init="$nextTick(async () => { if (await isJobActive()) startPolling(); })"
    @job-started.window="startPolling()"
    @job-completed.window="stopPolling()"
    @job-failed.window="stopPolling()"
>
<div
    class="category-form-container"
    @redirect-to-product-list.window="window.skipBeforeUnload = true; window.location.href = '/admin/products'">
<div class="w-full py-4">
    {{-- Header Section --}}
    @include('livewire.products.management.partials.form-header')

    {{-- Messages --}}
    @include('livewire.products.management.partials.form-messages')

    {{-- Main Layout Container --}}
    {{-- FIX 2025-12-08: Layout repair via global Livewire hooks in @push('scripts') --}}
    <div class="category-form-main-container">
        {{-- Left Column - Form Content (inside form) --}}
        <form wire:submit.prevent="save" class="category-form-left-column" wire:key="left-column-{{ $product?->id ?? 'new' }}">
            <div class="enterprise-card p-8 relative" wire:key="enterprise-card-{{ $product?->id ?? 'new' }}">
                    {{-- Loading Overlay - Shop Data Fetch (FIX 2025-11-28 v2) --}}
                    {{-- Using Alpine.js + Livewire events for precise control --}}
                    {{-- Only shows when ACTUALLY calling PrestaShop API (not on cache hits) --}}
                    <div x-data="{ isLoadingPrestaShop: false }"
                         @prestashop-loading-start.window="isLoadingPrestaShop = true"
                         @prestashop-loading-end.window="isLoadingPrestaShop = false">
                        <div x-show="isLoadingPrestaShop"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-200"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="shop-data-loading-overlay">
                            <div class="shop-data-loading-content">
                                <svg class="shop-data-loading-spinner" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="shop-data-loading-text">Pobieranie danych z PrestaShop...</span>
                            </div>
                        </div>
                    </div>

                    {{-- Tab Navigation --}}
                    @include('livewire.products.management.partials.tab-navigation')

                    {{-- Multi-Store Management (hidden for Gallery, Stock, Prices - combined data tabs) --}}
                    {{-- FIX 2025-12-01: These tabs show combined data from ALL shops, so shop tabs are not applicable --}}
                    @if(!in_array($activeTab, ['gallery', 'stock', 'prices']))
                        @include('livewire.products.management.partials.shop-management')
                    @endif

                    {{-- TAB CONTENT AREA - CONDITIONAL RENDERING (Only 1 tab in DOM at a time) --}}
                    {{-- FIX 2025-12-08: Wrap in div with activeTab in wire:key to force full replacement instead of morphing --}}
                    <div wire:key="tab-content-{{ $activeTab }}-{{ $product?->id ?? 'new' }}">
                        @if($activeTab === 'basic')
                            @include('livewire.products.management.tabs.basic-tab')
                        @elseif($activeTab === 'description')
                            @include('livewire.products.management.tabs.description-tab')
                        @elseif($activeTab === 'physical')
                            @include('livewire.products.management.tabs.physical-tab')
                        @elseif($activeTab === 'attributes')
                            @include('livewire.products.management.tabs.attributes-tab')
                        @elseif($activeTab === 'compatibility')
                            @include('livewire.products.management.tabs.compatibility-tab')
                        @elseif($activeTab === 'prices')
                            @include('livewire.products.management.tabs.prices-tab')
                        @elseif($activeTab === 'stock')
                            @include('livewire.products.management.tabs.stock-tab')
                        @elseif($activeTab === 'variants')
                            @include('livewire.products.management.tabs.variants-tab')
                        @elseif($activeTab === 'gallery')
                            <livewire:products.management.tabs.gallery-tab :productId="$product?->id" />
                        @elseif($activeTab === 'visual-description')
                            @include('livewire.products.management.tabs.visual-description-tab')
                        @endif
                    </div>

            </div> {{-- Close enterprise-card --}}
        </form> {{-- Close form (which is also left-column) --}}

        {{-- Right Column - Quick Actions & Info (OUTSIDE form to prevent morphing issues) --}}
        <div class="category-form-right-column" wire:key="right-column-{{ $product?->id ?? 'new' }}">
            {{-- Quick Actions Panel --}}
            @include('livewire.products.management.partials.quick-actions')

            {{-- Product Info (Edit Mode) --}}
            @include('livewire.products.management.partials.product-info')
        </div>
    </div> {{-- Close category-form-main-container --}}


    {{-- SHOP SELECTOR MODAL --}}
    @if($showShopSelector)
        <div class="fixed inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="z-index: 9999 !important;">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-4 text-center sm:p-0">
                {{-- Background overlay --}}
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" wire:click="closeShopSelector" style="z-index: 9998 !important;"></div>

                {{-- Modal content --}}
                <div class="inline-block align-middle bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative" style="z-index: 10000 !important;">
                    {{-- Header --}}
                    <div class="bg-gray-800 px-6 py-4 border-b border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-white">
                                Wybierz sklepy
                            </h3>
                            <button type="button"
                                    wire:click="closeShopSelector"
                                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors duration-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Content --}}
                    <form wire:submit.prevent="addToShops">
                        <div class="bg-gray-800 px-6 py-4 max-h-96 overflow-y-auto">
                            {{-- Error Messages --}}
                            @if($errors->has('general'))
                                <div class="mb-4 bg-red-100 dark:bg-red-900/20 border border-red-400 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg">
                                    {{ $errors->first('general') }}
                                </div>
                            @endif

                            <p class="text-sm text-gray-400 mb-4">
                                Wybierz sklepy, do których chcesz dodać ten produkt:
                            </p>

                            <div class="space-y-3">
                                @foreach($availableShops as $shop)
                                    @if(!in_array($shop['id'], $exportedShops))
                                        <label class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-700 cursor-pointer">
                                            <input type="checkbox"
                                                   value="{{ $shop['id'] }}"
                                                   wire:model="selectedShopsToAdd"
                                                   class="h-4 w-4 text-orange-600 border-gray-600 rounded focus:ring-orange-500 dark:bg-gray-700">

                                            <div class="ml-3 flex-1">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <p class="text-sm font-medium text-white">
                                                            {{ $shop['name'] }}
                                                        </p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                                            {{ $shop['url'] }}
                                                        </p>
                                                    </div>

                                                    {{-- Shop Status --}}
                                                    <div class="flex items-center">
                                                        @if($shop['connection_status'] === 'connected')
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                                </svg>
                                                                Połączony
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                                </svg>
                                                                Błąd
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    @endif
                                @endforeach
                            </div>

                            @if(count($availableShops) === count($exportedShops))
                                <div class="text-center py-8">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        Produkt jest już dostępny we wszystkich sklepach
                                    </p>
                                </div>
                            @endif
                        </div>

                        {{-- Footer --}}
                        <div class="bg-gray-700 px-6 py-4 flex justify-end space-x-3">
                            <button type="button"
                                    wire:click="closeShopSelector"
                                    class="inline-flex items-center px-4 py-2 border border-gray-600 text-sm font-medium rounded-lg text-gray-300 bg-gray-800 hover:bg-gray-700 transition-colors">
                                Anuluj
                            </button>
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 transition-colors">
                                Dodaj do wybranych sklepów
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- FAZA 4.2.3: Modal removed - inline category creation via + buttons in tree --}}

</div> {{-- Close w-full py-4 ROOT ELEMENT --}}
</div> {{-- Close category-form-container --}}
</div> {{-- Close Alpine.js job polling wrapper (PERFORMANCE FIX 2025-11-27) --}}

{{-- JavaScript section - moved outside root element like CategoryForm --}}
@push('scripts')
<script>
document.addEventListener('livewire:init', () => {
    // FIX 2025-12-08: Repair DOM structure after Livewire morphing
    // Livewire's morph algorithm sometimes moves right-column outside main-container
    function repairLayoutStructure() {
        const mainContainer = document.querySelector('.category-form-main-container');
        const rightCol = document.querySelector('.category-form-right-column');
        if (mainContainer && rightCol && rightCol.parentElement !== mainContainer) {
            console.log('[LAYOUT FIX] Moving right-column back to main-container');
            mainContainer.appendChild(rightCol);
        }
    }

    // Run repair after every Livewire update
    Livewire.hook('morph.updated', ({ el, component }) => {
        setTimeout(repairLayoutStructure, 50);
    });

    // Also run on message.processed as backup
    Livewire.hook('message.processed', (message, component) => {
        setTimeout(repairLayoutStructure, 100);
    });

    // Handle tab switching animations
    Livewire.on('tab-switched', (event) => {
        console.log('Tab switched to:', event.tab);
        // Run repair after tab switch specifically
        setTimeout(repairLayoutStructure, 150);
    });

    // Handle product saved event
    Livewire.on('product-saved', (event) => {
        console.log('Product saved with ID:', event.productId);

        // Optional: Show success notification or redirect
        setTimeout(() => {
            if (confirm('Produkt został zapisany. Czy chcesz przejść do listy produktów?')) {
                window.location.href = '{{ route("admin.products.index") }}';
            }
        }, 2000);
    });

    // Listen for confirmation events
    Livewire.on('confirm-status-change', (event) => {
        const data = event[0] || event;
        const message = data.message;
        const newStatus = data.newStatus;

        if (confirm(message)) {
            // User confirmed - proceed with status change
            @this.call('confirmStatusChange', newStatus);
        } else {
            // User cancelled - keep checkbox in current state
            const checkbox = document.getElementById('is_active');
            if (checkbox) {
                checkbox.checked = !newStatus; // Revert to original state
            }
        }
    });

    // Prevent accidental navigation with unsaved changes
    // FIX 2025-11-21 (ETAP_07b Fix #9): Skip check if saveAndClose() set skipBeforeUnload flag
    window.addEventListener('beforeunload', (e) => {
        // Allow redirect after successful save
        if (window.skipBeforeUnload === true) {
            return; // Don't show dialog
        }

        try {
            // Use Livewire 3.x API
            const component = window.Livewire?.find('{{ $this->getId() }}');
            if (component?.get('hasUnsavedChanges')) {
                e.preventDefault();
                e.returnValue = 'Masz niezapisane zmiany. Czy na pewno chcesz opuścić stronę?';
            }
        } catch (error) {
            // Fallback: Skip the check if Livewire API is not available
            console.log('Livewire beforeunload check skipped:', error);
        }
    });
});

/**
 * ETAP_13: Job Countdown Animation (0-60s)
 * Alpine.js component for real-time countdown during background job execution
 *
 * Usage: x-data="jobCountdown(@entangle('jobCreatedAt'), @entangle('activeJobStatus'), @entangle('jobResult'), @entangle('activeJobType'))"
 */
function jobCountdown(jobCreatedAt, activeJobStatus, jobResult, activeJobType) {
    return {
        jobCreatedAt: jobCreatedAt,
        activeJobStatus: activeJobStatus,
        jobResult: jobResult,
        activeJobType: activeJobType,
        currentTime: Date.now(),
        remainingSeconds: 60,
        progress: 0,
        interval: null,

        init() {
            // FIX 2025-11-18: Start countdown for BOTH pending AND processing
            // (Backend sets 'pending', not 'processing' - queue worker may be delayed)
            if (this.activeJobStatus === 'pending' || this.activeJobStatus === 'processing') {
                this.startCountdown();
            }

            // Watch for status changes
            this.$watch('activeJobStatus', (value) => {
                if (value === 'pending' || value === 'processing') {
                    this.startCountdown();
                } else {
                    // Stop countdown when status → null/completed/failed
                    this.stopCountdown();
                }
            });

            // Auto-clear on success/error after 5s
            this.$watch('jobResult', (value) => {
                if (value) {
                    setTimeout(() => {
                        this.clearJob();
                    }, 5000);
                }
            });
        },

        startCountdown() {
            if (!this.jobCreatedAt) return;

            this.interval = setInterval(() => {
                this.currentTime = Date.now();
                const createdAtTime = new Date(this.jobCreatedAt).getTime();
                const elapsed = (this.currentTime - createdAtTime) / 1000;

                this.remainingSeconds = Math.max(0, 60 - Math.floor(elapsed));
                this.progress = Math.min(100, (elapsed / 60) * 100);

                // FIX 2025-11-18: Auto-clear job status when countdown reaches 0
                // (Job completed or timeout - either way, clear UI state)
                if (this.remainingSeconds <= 0) {
                    this.stopCountdown();

                    // Auto-clear job status after 2s delay (allow user to see completion)
                    setTimeout(() => {
                        this.clearJob();
                    }, 2000);
                }
            }, 1000);
        },

        stopCountdown() {
            if (this.interval) {
                clearInterval(this.interval);
                this.interval = null;
            }
        },

        clearJob() {
            // Reset all properties
            this.activeJobStatus = null;
            this.jobResult = null;
            this.progress = 0;
            this.remainingSeconds = 60;

            // Notify Livewire to clear job properties
            this.$wire.set('activeJobId', null);
            this.$wire.set('activeJobStatus', null);
            this.$wire.set('jobResult', null);
            this.$wire.set('activeJobType', null);
        },

        destroy() {
            this.stopCountdown();
        }
    }
}
</script>
@endpush
