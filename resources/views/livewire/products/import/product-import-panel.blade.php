{{-- ETAP_06: Product Import Panel - COMPACT LAYOUT --}}
<div class="space-y-2">
    {{-- SINGLE ROW: Title + Stats + Import Buttons --}}
    <div class="enterprise-card px-4 py-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            {{-- Left: Title + subtitle --}}
            <div class="flex items-baseline gap-3">
                <h1 class="text-lg font-semibold text-gray-100">Import produktow</h1>
                <span class="text-xs text-gray-500 hidden lg:inline">Produkty oczekujace na uzupelnienie</span>
            </div>

            {{-- Center: Stats badges (compact) --}}
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 px-3 py-1 rounded-md bg-slate-700/50">
                    <span class="text-lg font-bold text-gray-100">{{ $this->stats['total'] }}</span>
                    <span class="text-xs text-gray-400">Wszystkie</span>
                </div>
                <div class="flex items-center gap-2 px-3 py-1 rounded-md bg-green-900/30">
                    <span class="text-lg font-bold text-green-400">{{ $this->stats['ready'] }}</span>
                    <span class="text-xs text-gray-400">Gotowe</span>
                </div>
                <div class="flex items-center gap-2 px-3 py-1 rounded-md bg-yellow-900/30">
                    <span class="text-lg font-bold text-yellow-400">{{ $this->stats['incomplete'] }}</span>
                    <span class="text-xs text-gray-400">Niekompletne</span>
                </div>
            </div>

            {{-- Right: Import button (FAZA 9.2 - unified modal) --}}
            <div class="flex items-center gap-2">
                <button wire:click="openImportModal()" class="btn-enterprise-primary btn-sm flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Importuj produkty
                </button>
            </div>
        </div>
    </div>

    {{-- SINGLE ROW: Filters + Bulk Actions --}}
    <div class="enterprise-card px-4 py-2">
        <div class="import-filters-inline flex items-center gap-3 flex-nowrap">
            {{-- Search --}}
            <input type="text"
                   wire:model.live.debounce.300ms="filterSearch"
                   placeholder="Szukaj po SKU, nazwie..."
                   class="form-input-dark filter-search h-8 text-sm">

            {{-- Dropdowns inline - SHORT labels --}}
            <select wire:model.live="filterStatus" class="form-select-dark h-8 text-sm">
                <option value="">Status</option>
                <option value="ready">Gotowe</option>
                <option value="incomplete">Niekompletne</option>
            </select>

            <select wire:model.live="filterProductType" class="form-select-dark h-8 text-sm">
                <option value="">Typ</option>
                @foreach($this->productTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterSessionId" class="form-select-dark h-8 text-sm">
                <option value="">Sesja</option>
                @foreach($this->importSessions as $session)
                    <option value="{{ $session->id }}">{{ Str::limit($session->session_name ?? 'Sesja #' . $session->id, 15) }}</option>
                @endforeach
            </select>

            {{-- Reset filter --}}
            @if($filterStatus || $filterProductType || $filterSessionId || $filterSearch)
                <button wire:click="resetFilters" class="p-1.5 rounded hover:bg-red-900/30 text-red-400 transition-colors flex-shrink-0" title="Wyczysc filtry">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            @endif

            {{-- Spacer --}}
            <div class="flex-1"></div>

            {{-- Bulk actions (right side) --}}
            @if(count($selectedIds) > 0)
                <div class="flex items-center gap-2 pl-3 border-l border-gray-700">
                    <span class="text-xs text-gray-400">
                        <span class="font-medium text-gray-200">{{ count($selectedIds) }}</span> wybrano
                    </span>
                    <button wire:click="bulkDelete"
                            wire:confirm="Czy na pewno usunac {{ count($selectedIds) }} produktow?"
                            class="btn-enterprise-danger btn-sm flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Usun
                    </button>
                    <button wire:click="bulkEditCompatibility" class="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs transition-colors flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        Dopasowania
                    </button>
                    <button wire:click="bulkPublish" class="btn-enterprise-success btn-sm flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Publikuj ({{ $this->getSelectedReadyCount() }})
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Tabela produktow z resizable columns --}}
    <div class="enterprise-card overflow-hidden" x-data="resizableTable('import-panel')" data-resizable-table>
        {{-- Reset widths button --}}
        <div class="flex items-center justify-end px-4 py-2 border-b border-gray-700/50">
            <button x-on:click="resetWidths()" class="text-xs text-gray-500 hover:text-gray-300 transition-colors">
                <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Reset kolumn
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="enterprise-table w-full">
                <thead>
                    <tr>
                        <th class="w-10 px-3">
                            <input type="checkbox"
                                   wire:model.live="selectAll"
                                   wire:click="selectAllOnPage"
                                   class="form-checkbox-dark">
                        </th>
                        <th class="w-14 px-2">Obraz</th>
                        <th class="px-2 cursor-pointer hover:bg-gray-700/50 relative resizable-column" data-column-id="sku" style="width: 100px; min-width: 80px;">
                            <div class="flex items-center gap-1" wire:click="sortBy('sku')">
                                SKU
                                @include('livewire.products.import.partials.sort-indicator', ['field' => 'sku'])
                            </div>
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'sku')"></div>
                        </th>
                        <th class="px-2 cursor-pointer hover:bg-gray-700/50 relative resizable-column" data-column-id="name" style="width: 140px; min-width: 100px;">
                            <div class="flex items-center gap-1" wire:click="sortBy('name')">
                                Nazwa
                                @include('livewire.products.import.partials.sort-indicator', ['field' => 'name'])
                            </div>
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'name')"></div>
                        </th>
                        <th class="px-2 w-24 relative resizable-column" data-column-id="type" style="width: 96px; min-width: 80px;">
                            <span class="text-xs">Typ</span>
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'type')"></div>
                        </th>

                        {{-- MARKA (manufacturer_id) --}}
                        <th class="px-2 w-24 relative resizable-column" data-column-id="manufacturer" style="width: 96px; min-width: 80px;">
                            <span class="text-xs text-gray-400">Marka</span>
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'manufacturer')"></div>
                        </th>

                        {{-- CENA - klik otwiera modal cen (FAZA 9.4) --}}
                        <th class="px-2 w-20 relative resizable-column" data-column-id="price" style="width: 80px; min-width: 60px;">
                            <span class="text-xs text-gray-400">Cena</span>
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'price')"></div>
                        </th>

                        {{-- KATEGORIE - 4 kolumny z bulk actions --}}
                        @php
                            $hasSelection = count($selectedIds ?? []) > 0;
                        @endphp

                        <th class="px-1 w-24 text-xs relative resizable-column" data-column-id="cat_l3" style="width: 96px; min-width: 70px;">
                            @if($hasSelection)
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-500">L3</span>
                                    @include('livewire.products.import.partials.bulk-category-dropdown', ['level' => 3])
                                </div>
                            @else
                                <span class="text-gray-400">Kategoria</span>
                            @endif
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'cat_l3')"></div>
                        </th>
                        <th class="px-1 w-24 text-xs relative resizable-column" data-column-id="cat_l4" style="width: 96px; min-width: 70px;">
                            @if($hasSelection)
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-500">L4</span>
                                    @include('livewire.products.import.partials.bulk-category-dropdown', ['level' => 4])
                                </div>
                            @else
                                <span class="text-gray-400">Podkat.</span>
                            @endif
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'cat_l4')"></div>
                        </th>
                        <th class="px-1 w-24 text-xs relative resizable-column" data-column-id="cat_l5" style="width: 96px; min-width: 70px;">
                            @if($hasSelection)
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-500">L5</span>
                                    @include('livewire.products.import.partials.bulk-category-dropdown', ['level' => 5])
                                </div>
                            @else
                                <span class="text-gray-400">Szczeg.</span>
                            @endif
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'cat_l5')"></div>
                        </th>
                        <th class="px-1 w-8 text-xs text-center text-gray-500">+</th>

                        {{-- PUBLIKACJA (FAZA 9.3 - zastepuje Sklepy) --}}
                        <th class="px-1 w-28 relative resizable-column" data-column-id="publication" style="width: 112px; min-width: 80px;">
                            <span class="text-gray-400 text-xs">Publikacja</span>
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'publication')"></div>
                        </th>
                        {{-- DATA PUBLIKACJI (FAZA 9.3) --}}
                        <th class="px-2 w-36 relative resizable-column" data-column-id="schedule" style="width: 144px; min-width: 110px;">
                            <span class="text-gray-400 text-xs">Data publikacji</span>
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'schedule')"></div>
                        </th>
                        {{-- PUBLIKUJ button (FAZA 9.3) --}}
                        <th class="px-2 w-24 text-center">
                            <span class="text-gray-400 text-xs">Publikuj</span>
                        </th>
                        <th class="px-2 w-16 text-center cursor-pointer hover:bg-gray-700/50" wire:click="sortBy('completion_percentage')">
                            <div class="flex items-center justify-center gap-1 text-xs">
                                Status
                                @include('livewire.products.import.partials.sort-indicator', ['field' => 'completion_percentage'])
                            </div>
                        </th>
                        <th class="px-2 w-28 text-right text-xs">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->pendingProducts as $product)
                        @include('livewire.products.import.partials.product-row', ['product' => $product])
                    @empty
                        <tr>
                            <td colspan="16" class="text-center py-12">
                                <div class="text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <p class="text-lg font-medium mb-2">Brak produktow do wyswietlenia</p>
                                    <p class="text-sm">
                                        Uzyj przycisku "Importuj produkty" aby dodac produkty
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginacja --}}
        @if($this->pendingProducts->hasPages())
            <div class="px-4 py-3 border-t border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm text-gray-400">
                        <span>Pokazuj:</span>
                        <select wire:model.live="perPage" class="form-select-dark-sm">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span>z {{ $this->pendingProducts->total() }}</span>
                    </div>
                    {{ $this->pendingProducts->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- Modal: Wklejanie SKU (FAZA 3 - legacy) --}}
    @if($activeModal === 'sku-paste')
        @livewire('products.import.modals.s-k-u-paste-modal', key('sku-paste-modal'))
    @endif
</div>
