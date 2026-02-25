{{-- Select All Pages Banner --}}
@if($selectAll && !$selectingAllPages && $this->totalFilteredCount > $this->selectedCount)
    <div class="mt-4 p-3 bg-blue-900/20 border border-blue-700 rounded-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm text-blue-200">
                    Zaznaczono <strong>{{ $this->selectedCount }}</strong> produktów na tej stronie.
                </span>
            </div>
            <button wire:click="selectAllPages"
                    class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-all duration-200">
                Zaznacz wszystkie {{ $this->totalFilteredCount }} produktów
            </button>
        </div>
    </div>
@endif

{{-- Deselect All Pages Banner --}}
@if($selectingAllPages)
    <div class="mt-4 p-3 bg-green-900/20 border border-green-700 rounded-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm text-green-200">
                    Zaznaczono <strong>wszystkie {{ $this->selectedCount }}</strong> produktów pasujących do filtrów.
                </span>
            </div>
            <button wire:click="deselectAllPages"
                    class="px-4 py-2 text-sm font-semibold text-white bg-gray-600 hover:bg-gray-700 rounded-lg transition-all duration-200">
                Odznacz (tylko bieżąca strona)
            </button>
        </div>
    </div>
@endif

{{-- Bulk Actions Bar --}}
@if($showBulkActions)
    <div class="mt-4 p-3 bg-orange-900/20 border border-orange-800 rounded-lg">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center">
                <span class="text-sm font-medium text-orange-200">
                    @if($selectingAllPages)
                        Zaznaczono <strong>wszystkie {{ $this->selectedCount }}</strong> produktów
                    @else
                        Zaznaczono {{ $this->selectedCount }} {{ $this->selectedCount == 1 ? 'produkt' : ($this->selectedCount < 5 ? 'produkty' : 'produktów') }}
                    @endif
                </span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button wire:click="bulkActivate"
                        class="px-3 py-1 text-sm btn-secondary rounded hover:bg-card-hover transition-all duration-300">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Aktywuj
                </button>
                <button wire:click="bulkDeactivate"
                        class="px-3 py-1 text-sm btn-secondary rounded hover:bg-card-hover transition-all duration-300">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Deaktywuj
                </button>
                {{-- Category Operations Dropdown (ETAP_07a FAZA 2) --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="px-3 py-1 text-sm btn-secondary rounded hover:bg-card-hover transition-all duration-300 inline-flex items-center">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        Kategorie
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="open"
                         @click.away="open = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute left-0 mt-2 w-56 rounded-md shadow-lg bg-gray-700 ring-1 ring-black ring-opacity-5 z-50"
                         style="display: none;">
                        <div class="py-1" role="menu">
                            <button wire:click="openBulkAssignCategories"
                                    @click="open = false"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Przypisz kategorie
                            </button>

                            <button wire:click="openBulkRemoveCategories"
                                    @click="open = false"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Usuń kategorie
                            </button>

                            <button wire:click="openBulkMoveCategories"
                                    @click="open = false"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                                Przenieś między kategoriami
                            </button>
                        </div>
                    </div>
                </div>
                <button wire:click="bulkExportCsv"
                        class="px-3 py-1 text-sm bg-green-600 hover:bg-green-700 text-white rounded transition-all duration-300">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export CSV
                </button>
                <button wire:click="openQuickSendModal"
                        class="px-3 py-1 text-sm bg-purple-600 hover:bg-purple-700 text-white rounded transition-all duration-300">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    Wyślij na sklepy
                </button>
                <button wire:click="openBulkDeleteModal"
                        class="px-3 py-1 text-sm bg-red-600 hover:bg-red-700 text-white rounded transition-all duration-300">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Usuń
                </button>
                <button wire:click="resetSelection"
                        class="px-3 py-1 text-sm text-muted hover:text-primary transition-colors duration-300">
                    Anuluj
                </button>
            </div>
        </div>
    </div>
@endif
