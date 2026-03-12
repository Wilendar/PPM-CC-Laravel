{{-- CENTER COLUMN: Location tree --}}
<div class="feature-browser__column">
    <div class="feature-browser__column-header">
        <span class="flex items-center gap-2">
            <svg class="w-3.5 h-3.5 header-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
            DRZEWO LOKALIZACJI
        </span>
        @if($selectedWarehouseId)
            <button wire:click="openCreateModal"
                    class="location-action-btn location-action-btn--add"
                    title="Dodaj lokalizacje">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </button>
        @endif
    </div>

    @if($selectedWarehouseId)
        {{-- Search --}}
        <div class="location-browser__search">
            <span class="location-browser__search-dot"></span>
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Szukaj lokalizacji...">
        </div>

        {{-- Filters --}}
        <div class="location-browser__filters">
            <select wire:model.live="patternFilter" class="location-select-enterprise flex-1 text-xs py-1.5">
                <option value="all">Wszystkie typy</option>
                <option value="coded">Kodowane (AA_01_03)</option>
                <option value="dash">Myslnikowe (AA-01-03)</option>
                <option value="wall">Scianowe</option>
                <option value="named">Nazwane (SKLEP)</option>
                <option value="gift">Prezentowe</option>
            </select>
            <select wire:model.live="occupancyFilter" class="location-select-enterprise flex-1 text-xs py-1.5">
                <option value="all">Zajete + Puste</option>
                <option value="occupied">Tylko zajete</option>
                <option value="empty">Tylko puste</option>
            </select>
        </div>

        {{-- Tree content --}}
        <div class="feature-browser__column-content">
            @forelse($locationTree as $zoneKey => $zone)
                {{-- Zone level --}}
                <div x-data="{ expanded: false }" wire:key="zone-{{ $zoneKey }}">
                    <div class="location-tree-item location-tree-item--zone w-full text-left flex items-center justify-between">
                        <button @click="expanded = !expanded" class="flex items-center gap-2 flex-1 min-w-0">
                            <span class="location-zone-indicator location-zone-indicator--{{ strtolower($zone['zone'] ?? 'other') }}"></span>
                            <svg class="w-3 h-3 transition-transform flex-shrink-0" :class="expanded ? 'rotate-90' : ''"
                                 fill="currentColor" viewBox="0 0 20 20">
                                <path d="M6 6l4 4-4 4V6z"/>
                            </svg>
                            <span class="truncate">{{ $zone['label'] }}</span>
                        </button>
                        <div class="flex items-center gap-1">
                            <span class="location-product-count {{ ($zone['product_count'] ?? 0) === 0 ? 'location-product-count--zero' : (($zone['product_count'] ?? 0) >= 20 ? 'location-product-count--high' : '') }}">
                                {{ $zone['product_count'] ?? 0 }}
                            </span>
                            <div class="location-tree-actions">
                                <button wire:click="openZoneModal('{{ $zone['zone'] }}')"
                                        class="location-action-btn" title="Edytuj strefe">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                </button>
                                <button wire:click="deleteZone('{{ $zone['zone'] }}')"
                                        wire:confirm="Usunac strefe '{{ $zone['label'] }}' i wszystkie jej lokalizacje?"
                                        class="location-action-btn location-action-btn--danger" title="Usun strefe">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div x-show="expanded" x-collapse class="ml-4 space-y-0.5">
                        {{-- Direct zone-level bins (named/other locations) --}}
                        @foreach($zone['bins'] ?? [] as $bin)
                            <button wire:click="selectLocation({{ $bin['id'] }})"
                                    wire:key="zone-bin-{{ $bin['id'] }}"
                                    class="location-tree-item location-tree-item--bin w-full text-left flex items-center justify-between
                                           {{ ($selectedLocationId ?? null) === ($bin['id'] ?? null) ? 'location-tree-item--selected' : '' }}
                                           {{ ($bin['product_count'] ?? 0) === 0 ? 'location-tree-item--empty' : '' }}">
                                <span>{{ $bin['code'] }}</span>
                                <span class="location-product-count {{ ($bin['product_count'] ?? 0) === 0 ? 'location-product-count--zero' : '' }}">
                                    {{ $bin['product_count'] ?? 0 }}
                                </span>
                            </button>
                        @endforeach

                        {{-- Row containers --}}
                        @foreach($zone['children'] as $rowKey => $row)
                            <div x-data="{ rowExpanded: false }" wire:key="row-{{ $zoneKey }}-{{ $rowKey }}">
                                <button @click="rowExpanded = !rowExpanded"
                                        class="location-tree-item location-tree-item--row w-full text-left flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-3 h-3 transition-transform" :class="rowExpanded ? 'rotate-90' : ''"
                                             fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M6 6l4 4-4 4V6z"/>
                                        </svg>
                                        <span>{{ $row['label'] ?? $row['code'] ?? "\u{2014}" }}</span>
                                    </div>
                                    <span class="location-product-count">{{ $row['product_count'] ?? 0 }}</span>
                                </button>

                                <div x-show="rowExpanded" x-collapse class="ml-4 space-y-0.5">
                                    {{-- Direct row-level bins (no shelf) --}}
                                    @foreach($row['bins'] ?? [] as $bin)
                                        <button wire:click="selectLocation({{ $bin['id'] }})"
                                                wire:key="row-bin-{{ $bin['id'] }}"
                                                class="location-tree-item location-tree-item--bin w-full text-left flex items-center justify-between
                                                       {{ ($selectedLocationId ?? null) === ($bin['id'] ?? null) ? 'location-tree-item--selected' : '' }}
                                                       {{ ($bin['product_count'] ?? 0) === 0 ? 'location-tree-item--empty' : '' }}">
                                            <span>{{ $bin['code'] }}</span>
                                            <span class="location-product-count {{ ($bin['product_count'] ?? 0) === 0 ? 'location-product-count--zero' : '' }}">
                                                {{ $bin['product_count'] ?? 0 }}
                                            </span>
                                        </button>
                                    @endforeach

                                    {{-- Shelf containers --}}
                                    @foreach($row['children'] ?? [] as $shelfKey => $shelf)
                                        <div x-data="{ shelfExpanded: false }" wire:key="shelf-{{ $zoneKey }}-{{ $rowKey }}-{{ $shelfKey }}">
                                            <button @click="shelfExpanded = !shelfExpanded"
                                                    class="location-tree-item location-tree-item--shelf w-full text-left flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-3 h-3 transition-transform" :class="shelfExpanded ? 'rotate-90' : ''"
                                                         fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M6 6l4 4-4 4V6z"/>
                                                    </svg>
                                                    <span>{{ $shelf['label'] ?? $shelf['code'] ?? "\u{2014}" }}</span>
                                                </div>
                                                <span class="location-product-count">{{ $shelf['product_count'] ?? 0 }}</span>
                                            </button>

                                            <div x-show="shelfExpanded" x-collapse class="ml-4 space-y-0.5">
                                                @foreach($shelf['children'] ?? [] as $bin)
                                                    <button wire:click="selectLocation({{ $bin['id'] }})"
                                                            wire:key="bin-{{ $bin['id'] }}"
                                                            class="location-tree-item location-tree-item--bin w-full text-left flex items-center justify-between
                                                                   {{ ($selectedLocationId ?? null) === ($bin['id'] ?? null) ? 'location-tree-item--selected' : '' }}
                                                                   {{ ($bin['product_count'] ?? 0) === 0 ? 'location-tree-item--empty' : '' }}">
                                                        <span>{{ $bin['code'] }}</span>
                                                        <span class="location-product-count {{ ($bin['product_count'] ?? 0) === 0 ? 'location-product-count--zero' : '' }}">
                                                            {{ $bin['product_count'] ?? 0 }}
                                                        </span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        @if(empty($zone['children']) && empty($zone['bins'] ?? []))
                            <div class="text-xs text-gray-500 pl-4 py-1 italic">Brak lokalizacji w tej strefie</div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center text-gray-500 py-8">
                    <p class="text-sm">Brak lokalizacji.</p>
                    <p class="text-xs mt-1">Kliknij "Skanuj" aby zaimportowac.</p>
                </div>
            @endforelse
        </div>

        {{-- Footer --}}
        <div class="feature-browser__column-footer">
            {{ count($locationTree) }} stref
        </div>
    @else
        {{-- No warehouse selected placeholder --}}
        <div class="feature-browser__column-content">
            <div class="feature-browser__empty-state">
                <div class="feature-browser__empty-state-icon">
                    <svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <p class="feature-browser__empty-state-text">Wybierz magazyn z listy po lewej</p>
            </div>
        </div>
    @endif
</div>
