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
                <button wire:click="openImportModal()"
                        @disabled(!$this->canSeeBasicData())
                        class="btn-enterprise-primary btn-sm flex items-center gap-1.5 {{ !$this->canSeeBasicData() ? 'opacity-30 cursor-not-allowed' : '' }}"
                        title="{{ !$this->canSeeBasicData() ? 'Brak uprawnien do importu' : 'Importuj produkty' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Importuj produkty
                </button>
            </div>
        </div>
    </div>

    {{-- FILTERS: Row 1 (main) + Row 2 (advanced toggles) + Bulk Actions --}}
    <div class="enterprise-card px-4 py-2">
        {{-- Row 1: Main filters + Bulk actions --}}
        <div class="import-filters-inline flex items-center gap-3 flex-nowrap">
            {{-- Search --}}
            <input type="text"
                   wire:model.live.debounce.300ms="filterSearch"
                   placeholder="Szukaj po SKU, nazwie..."
                   class="form-input-dark filter-search h-8 text-sm">

            {{-- Status --}}
            <select wire:model.live="filterStatus" class="form-select-dark h-8 text-sm">
                <option value="">Status</option>
                <option value="ready">Gotowe</option>
                <option value="incomplete">Niekompletne</option>
                <option value="published">Opublikowane</option>
            </select>

            {{-- Typ produktu --}}
            <select wire:model.live="filterProductType" class="form-select-dark h-8 text-sm">
                <option value="">Typ</option>
                @foreach($this->productTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>

            {{-- Marka (manufacturer) --}}
            <select wire:model.live="filterManufacturerId" class="form-select-dark h-8 text-sm">
                <option value="">Marka</option>
                @foreach($this->manufacturers as $m)
                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                @endforeach
            </select>

            {{-- Cel publikacji --}}
            <select wire:model.live="filterPublicationTarget" class="form-select-dark h-8 text-sm">
                <option value="">Cel publikacji</option>
                <option value="erp">ERP</option>
                <option value="prestashop">PrestaShop</option>
                <option value="both">ERP + PrestaShop</option>
            </select>

            {{-- Reset filter --}}
            @if($this->hasActiveFilters())
                <button wire:click="resetFilters" class="p-1.5 rounded hover:bg-red-900/30 text-red-400 transition-colors flex-shrink-0" title="Wyczysc filtry">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            @endif

            {{-- Spacer --}}
            <div class="flex-1"></div>

            {{-- Toggle advanced filters --}}
            <button wire:click="toggleAdvancedFilters"
                    class="import-filter-expand-btn flex items-center gap-1"
                    title="{{ $showAdvancedFilters ? 'Zwin filtry' : 'Rozwin filtry' }}">
                <svg class="w-3.5 h-3.5 transition-transform {{ $showAdvancedFilters ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
                Filtry
            </button>

            {{-- Bulk actions (right side) --}}
            @if(count($selectedIds) > 0)
                <div class="flex items-center gap-2 pl-3 border-l border-gray-700">
                    <span class="text-xs text-gray-400">
                        <span class="font-medium text-gray-200">{{ count($selectedIds) }}</span> wybrano
                    </span>
                    <button wire:click="bulkDelete"
                            wire:confirm="Czy na pewno usunac {{ count($selectedIds) }} produktow?"
                            @disabled(!$this->canDuplicateDelete())
                            class="btn-enterprise-danger btn-sm flex items-center gap-1 {{ !$this->canDuplicateDelete() ? 'opacity-30 cursor-not-allowed' : '' }}"
                            title="{{ !$this->canDuplicateDelete() ? 'Brak uprawnien' : 'Usun zaznaczone' }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Usun
                    </button>
                    <button wire:click="bulkEditCompatibility"
                            @disabled(!$this->canManageCompatibility())
                            class="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs transition-colors flex items-center gap-1 {{ !$this->canManageCompatibility() ? 'opacity-30 cursor-not-allowed' : '' }}"
                            title="{{ !$this->canManageCompatibility() ? 'Brak uprawnien' : 'Dopasowania' }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        Dopasowania
                    </button>
                    <button wire:click="bulkPublish"
                            @disabled(!$this->canPublish())
                            class="btn-enterprise-success btn-sm flex items-center gap-1 {{ !$this->canPublish() ? 'opacity-30 cursor-not-allowed' : '' }}"
                            title="{{ !$this->canPublish() ? 'Brak uprawnien' : 'Publikuj zaznaczone' }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Publikuj ({{ $this->getSelectedReadyCount() }})
                    </button>
                </div>
            @endif
        </div>

        {{-- Row 2: Advanced filters (toggles + date range) --}}
        @if($showAdvancedFilters)
            <div class="import-filter-toggle-row border-t border-gray-700/50 mt-2 pt-2">
                {{-- Hide published --}}
                <label class="import-filter-toggle-label">
                    <input type="checkbox" wire:model.live="filterHidePublished"
                           class="rounded border-gray-600 bg-gray-700 text-green-500 focus:ring-green-500/30">
                    Ukryj opublikowane
                </label>

                <div class="import-filter-separator"></div>

                {{-- Without images --}}
                <label class="import-filter-toggle-label">
                    <input type="checkbox" wire:model.live="filterNoImages"
                           class="rounded border-gray-600 bg-gray-700 text-amber-500 focus:ring-amber-500/30">
                    Bez zdjec
                </label>

                {{-- Without descriptions --}}
                <label class="import-filter-toggle-label">
                    <input type="checkbox" wire:model.live="filterNoDescriptions"
                           class="rounded border-gray-600 bg-gray-700 text-amber-500 focus:ring-amber-500/30">
                    Bez opisow
                </label>

                {{-- Without compatibility (Czesc zamienna) --}}
                <label class="import-filter-toggle-label">
                    <input type="checkbox" wire:model.live="filterNoCompatibility"
                           class="rounded border-gray-600 bg-gray-700 text-amber-500 focus:ring-amber-500/30">
                    Bez dopasowan
                </label>

                {{-- Without features (Pojazd) --}}
                <label class="import-filter-toggle-label">
                    <input type="checkbox" wire:model.live="filterNoFeatures"
                           class="rounded border-gray-600 bg-gray-700 text-amber-500 focus:ring-amber-500/30">
                    Bez atrybutow
                </label>

                <div class="import-filter-separator"></div>

                {{-- Date range --}}
                <div class="import-filter-date-group">
                    <span class="text-xs text-gray-500">Od:</span>
                    <input type="date" wire:model.live="filterPublishedFrom"
                           class="import-filter-date-input">
                    <span class="text-xs text-gray-500">Do:</span>
                    <input type="date" wire:model.live="filterPublishedTo"
                           class="import-filter-date-input">
                </div>
            </div>
        @endif
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
        @php
            $tableMinWidth = 40 + 56 + 130 + 230
                + 100 + 100 + 85
                + 90 + 90 + 90 + 80
                + 160 + 100 + 150 + 100
                + 70 + 120;
            if ($this->effectiveCategoryMaxLevel >= 6) $tableMinWidth += 90;
            if ($this->effectiveCategoryMaxLevel >= 7) $tableMinWidth += 90;
        @endphp
        <div class="overflow-x-auto import-table-scroll-container" x-data="scrollDetector">
            <table class="enterprise-table" style="min-width: {{ $tableMinWidth }}px">
                <thead>
                    <tr>
                        {{-- LEFT STICKY: Checkbox --}}
                        <th class="px-3 import-table-sticky-left import-sticky-col-0">
                            <input type="checkbox"
                                   wire:click="toggleSelectAllOnPage"
                                   @checked($selectAll)
                                   class="form-checkbox-dark">
                        </th>
                        {{-- LEFT STICKY: Obraz --}}
                        <th class="px-2 import-table-sticky-left import-sticky-col-1 {{ !$this->canSeeImages() ? 'opacity-30' : '' }}">Obraz</th>
                        {{-- LEFT STICKY: SKU --}}
                        <th class="px-2 cursor-pointer hover:bg-gray-700/50 relative import-table-sticky-left import-sticky-col-2 {{ !$this->canSeeBasicData() ? 'opacity-30 pointer-events-none' : '' }}"
                            data-column-id="sku">
                            <div class="flex items-center gap-1" wire:click="sortBy('sku')">
                                SKU
                                @include('livewire.products.import.partials.sort-indicator', ['field' => 'sku'])
                            </div>
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'sku')"></div>
                        </th>
                        {{-- LEFT STICKY: Nazwa (shadow edge) --}}
                        <th class="px-2 cursor-pointer hover:bg-gray-700/50 relative import-table-sticky-left import-sticky-col-3 import-sticky-shadow-left {{ !$this->canSeeBasicData() ? 'opacity-30 pointer-events-none' : '' }}"
                            data-column-id="name">
                            <div class="flex items-center gap-1" wire:click="sortBy('name')">
                                Nazwa
                                @include('livewire.products.import.partials.sort-indicator', ['field' => 'name'])
                            </div>
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'name')"></div>
                        </th>
                        <th class="px-2 relative resizable-column {{ !$this->canSeeBasicData() ? 'opacity-30' : '' }}" data-column-id="type" style="width: 100px;">
                            <span class="text-xs">Typ</span>
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'type')"></div>
                        </th>

                        {{-- MARKA (manufacturer_id) --}}
                        <th class="px-2 relative resizable-column {{ !$this->canSeeBasicData() ? 'opacity-30' : '' }}" data-column-id="manufacturer" style="width: 100px;">
                            <span class="text-xs text-gray-400">Marka</span>
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'manufacturer')"></div>
                        </th>

                        {{-- CENA - klik otwiera modal cen (FAZA 9.4) --}}
                        <th class="px-2 relative resizable-column {{ !$this->canSeePrices() ? 'opacity-30 pointer-events-none' : '' }}" data-column-id="price" style="width: 85px;">
                            <div class="flex items-center gap-1">
                                <span class="text-xs text-gray-400">Cena</span>
                                <button wire:click="togglePriceDisplay"
                                        @disabled(!$this->canSeePrices())
                                        class="import-price-toggle-btn {{ $priceDisplayMode === 'net' ? 'import-price-toggle-net' : 'import-price-toggle-gross' }}"
                                        title="Przelacz netto/brutto">
                                    {{ $priceDisplayMode === 'net' ? 'NETTO' : 'BRUTTO' }}
                                </button>
                            </div>
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'price')"></div>
                        </th>

                        {{-- KATEGORIE - L3-L5 + rozszerzane L6-L8 przez "+" --}}
                        @php
                            $hasSelection = count($selectedIds ?? []) > 0;
                            $effectiveCategoryMaxLevel = $this->effectiveCategoryMaxLevel;
                        @endphp

                        <th class="px-1 text-xs relative resizable-column {{ !$this->canSeeCategories() ? 'opacity-30 pointer-events-none' : '' }}" data-column-id="cat_l3" style="width: 90px;">
                            @if($hasSelection)
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-500">L3</span>
                                    @include('livewire.products.import.partials.bulk-category-dropdown', ['level' => 3])
                                </div>
                            @else
                                <span class="text-gray-400">Kat L3</span>
                            @endif
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'cat_l3')"></div>
                        </th>
                        <th class="px-1 text-xs relative resizable-column {{ !$this->canSeeCategories() ? 'opacity-30 pointer-events-none' : '' }}" data-column-id="cat_l4" style="width: 90px;">
                            @if($hasSelection)
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-500">L4</span>
                                    @include('livewire.products.import.partials.bulk-category-dropdown', ['level' => 4])
                                </div>
                            @else
                                <span class="text-gray-400">Kat L4</span>
                            @endif
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'cat_l4')"></div>
                        </th>
                        <th class="px-1 text-xs relative resizable-column {{ !$this->canSeeCategories() ? 'opacity-30 pointer-events-none' : '' }}" data-column-id="cat_l5" style="width: 90px;">
                            @if($hasSelection)
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-500">L5</span>
                                    @include('livewire.products.import.partials.bulk-category-dropdown', ['level' => 5])
                                </div>
                            @else
                                <span class="text-gray-400">Kat L5</span>
                            @endif
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'cat_l5')"></div>
                        </th>

                        @php
                            $autoMaxLevel = $this->autoCategoryMaxLevel;
                            $canCollapse = fn(int $level) => $effectiveCategoryMaxLevel >= $level && $effectiveCategoryMaxLevel > $autoMaxLevel && $effectiveCategoryMaxLevel > 5;
                        @endphp

                        {{-- L6 - ukryte do czasu klikniecia "+" --}}
                        <th class="px-1 text-xs text-center text-gray-500 relative resizable-column" data-column-id="cat_l6" style="width: 90px;">
                            @if($effectiveCategoryMaxLevel >= 6)
                                <div class="flex items-center justify-center gap-1">
                                    <span class="text-gray-400">KAT L6</span>
                                    @if($canCollapse(6) && $effectiveCategoryMaxLevel == 6)
                                        <button type="button"
                                                wire:click="collapseCategoryColumns"
                                                class="inline-flex items-center justify-center w-5 h-5 rounded text-gray-500
                                                       hover:bg-red-900/30 hover:text-red-400 transition-colors"
                                                title="Ukryj KAT L6">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            @else
                                <button type="button"
                                        wire:click="expandCategoryColumns"
                                        class="inline-flex items-center justify-center w-7 h-7 rounded text-gray-500
                                               bg-gray-700/30 hover:bg-gray-700/50 hover:text-green-400 transition-colors"
                                        title="Pokaż KAT L6">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </button>
                            @endif
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'cat_l6')"></div>
                        </th>

                        {{-- L7 (placeholder "+" dopoki nie zostanie dodane) --}}
                        @if($effectiveCategoryMaxLevel >= 6)
                            <th class="px-1 text-xs text-center text-gray-500 relative resizable-column" data-column-id="cat_l7" style="width: 90px;">
                                @if($effectiveCategoryMaxLevel >= 7)
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="text-gray-400">KAT L7</span>
                                        @if($canCollapse(7) && $effectiveCategoryMaxLevel == 7)
                                            <button type="button"
                                                    wire:click="collapseCategoryColumns"
                                                    class="inline-flex items-center justify-center w-5 h-5 rounded text-gray-500
                                                           hover:bg-red-900/30 hover:text-red-400 transition-colors"
                                                    title="Ukryj KAT L7">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    <button type="button"
                                            wire:click="expandCategoryColumns"
                                            class="inline-flex items-center justify-center w-7 h-7 rounded text-gray-500
                                                   bg-gray-700/30 hover:bg-gray-700/50 hover:text-green-400 transition-colors"
                                            title="Pokaż KAT L7">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </button>
                                @endif
                                <div class="resize-handle" x-on:mousedown="startResize($event, 'cat_l7')"></div>
                            </th>
                        @endif

                        {{-- L8 (placeholder "+" dopoki nie zostanie dodane) --}}
                        @if($effectiveCategoryMaxLevel >= 7)
                            <th class="px-1 text-xs text-center text-gray-500 relative resizable-column" data-column-id="cat_l8" style="width: 90px;">
                                @if($effectiveCategoryMaxLevel >= 8)
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="text-gray-400">KAT L8</span>
                                        @if($canCollapse(8) && $effectiveCategoryMaxLevel == 8)
                                            <button type="button"
                                                    wire:click="collapseCategoryColumns"
                                                    class="inline-flex items-center justify-center w-5 h-5 rounded text-gray-500
                                                           hover:bg-red-900/30 hover:text-red-400 transition-colors"
                                                    title="Ukryj KAT L8">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    <button type="button"
                                            wire:click="expandCategoryColumns"
                                            class="inline-flex items-center justify-center w-7 h-7 rounded text-gray-500
                                                   bg-gray-700/30 hover:bg-gray-700/50 hover:text-green-400 transition-colors"
                                            title="Pokaż KAT L8">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </button>
                                @endif
                                <div class="resize-handle" x-on:mousedown="startResize($event, 'cat_l8')"></div>
                            </th>
                        @endif

                        {{-- PUBLIKACJA (FAZA 9.3 - zastepuje Sklepy) --}}
                        <th class="px-1 relative resizable-column {{ !$this->canSeePublication() ? 'opacity-30' : '' }}" data-column-id="publication" style="width: 160px;">
                            <span class="text-gray-400 text-xs">Publikacja</span>
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'publication')"></div>
                        </th>
                        {{-- DATA DODANIA (sortowalna) --}}
                        <th class="px-2 relative resizable-column" data-column-id="imported_at" style="width: 100px;">
                            <div class="flex items-center gap-1 cursor-pointer" wire:click="sortBy('imported_at')">
                                <span class="text-gray-400 text-xs">Data dodania</span>
                                @include('livewire.products.import.partials.sort-indicator', ['field' => 'imported_at'])
                            </div>
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'imported_at')"></div>
                        </th>
                        {{-- DATA PUBLIKACJI (FAZA 9.3) --}}
                        <th class="px-2 relative resizable-column {{ !$this->canSeeScheduleDate() ? 'opacity-30' : '' }}" data-column-id="schedule" style="width: 150px;">
                            <span class="text-gray-400 text-xs">Data publikacji</span>
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'schedule')"></div>
                        </th>
                        {{-- PUBLIKUJ button (FAZA 9.3) --}}
                        <th class="px-2 text-center relative resizable-column {{ !$this->canPublish() ? 'opacity-30' : '' }}" data-column-id="publish" style="width: 100px;">
                            <span class="text-gray-400 text-xs">Publikuj</span>
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'publish')"></div>
                        </th>
                        {{-- RIGHT STICKY: Status (shadow edge) --}}
                        <th class="px-2 text-center cursor-pointer hover:bg-gray-700/50 relative import-table-sticky-right import-sticky-col-r1 import-sticky-shadow-right"
                            data-column-id="status" wire:click="sortBy('completion_percentage')">
                            <div class="flex items-center justify-center gap-1 text-xs">
                                Status
                                @include('livewire.products.import.partials.sort-indicator', ['field' => 'completion_percentage'])
                            </div>
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'status')"></div>
                        </th>
                        {{-- RIGHT STICKY: Akcje --}}
                        <th class="px-2 text-right text-xs relative import-table-sticky-right import-sticky-col-r0"
                            data-column-id="actions">
                            Akcje
                            <div class="resize-handle" x-on:mousedown="startResize($event, 'actions')"></div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->pendingProducts as $product)
                        @include('livewire.products.import.partials.product-row', [
                            'product' => $product,
                            'effectiveCategoryMaxLevel' => $effectiveCategoryMaxLevel,
                            'allShops' => $this->activePrestaShopShops,
                        ])
                    @empty
                        <tr>
                            <td colspan="{{ 17 + ($effectiveCategoryMaxLevel >= 6 ? 1 : 0) + ($effectiveCategoryMaxLevel >= 7 ? 1 : 0) }}"
                                class="text-center py-12">
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

    {{-- Modal: Potwierdzenie cofniecia publikacji (PPM modal) --}}
    @include('livewire.products.import.modals.unpublish-confirm-modal')
</div>
