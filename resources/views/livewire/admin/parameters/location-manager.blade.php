<div>
    {{-- Header: Title + Actions --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold text-white">Lokalizacje magazynowe</h1>
            <p class="text-sm text-gray-400 mt-1">Zarzadzanie lokalizacjami produktow na magazynach</p>
        </div>
        <div class="flex items-center gap-2">
            @if($selectedWarehouseId)
                <button wire:click="populateLocations" wire:loading.attr="disabled"
                        class="btn-enterprise-secondary text-sm px-3 py-2">
                    <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Skanuj product_stock
                </button>
                <button wire:click="refreshCounts" wire:loading.attr="disabled"
                        class="btn-enterprise-secondary text-sm px-3 py-2">
                    <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Odswiez liczniki
                </button>
            @endif
        </div>
    </div>

    {{-- Stats Grid (only when warehouse selected) --}}
    @if($selectedWarehouseId && $stats)
        <div class="location-stats-grid mb-4">
            <div class="location-stat-card">
                <div class="location-stat-value">{{ $stats['total'] }}</div>
                <div class="location-stat-label">Lokalizacje</div>
            </div>
            <div class="location-stat-card">
                <div class="location-stat-value">{{ $stats['occupied'] }}</div>
                <div class="location-stat-label">Zajete</div>
            </div>
            <div class="location-stat-card">
                <div class="location-stat-value">{{ $stats['empty'] }}</div>
                <div class="location-stat-label">Puste</div>
            </div>
            <div class="location-stat-card">
                <div class="location-stat-value">{{ $stats['zones_count'] }}</div>
                <div class="location-stat-label">Strefy</div>
            </div>
        </div>
    @endif

    {{-- 3-column panel: Left (Warehouses) | Center (Tree) | Right (Products) --}}
    <div class="location-panel">

        {{-- LEFT: Magazyny --}}
        <div class="location-panel-left">
            <div class="p-3 border-b border-gray-700">
                <h3 class="text-sm font-semibold text-gray-300 uppercase tracking-wide">Magazyny</h3>
            </div>
            <div class="p-2 space-y-1">
                @foreach($warehouses as $warehouse)
                    <button wire:click="selectWarehouse({{ $warehouse->id }})"
                            wire:key="wh-{{ $warehouse->id }}"
                            class="w-full text-left px-3 py-2 rounded-lg text-sm transition-colors duration-200
                                   {{ $selectedWarehouseId === $warehouse->id
                                       ? 'bg-[#e0ac7e]/20 text-[#e0ac7e] border border-[#e0ac7e]/30'
                                       : 'text-gray-300 hover:bg-gray-700' }}">
                        <div class="font-medium truncate">{{ $warehouse->name }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">
                            {{ $warehouse->locations_count ?? 0 }} lokalizacji
                        </div>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- CENTER: Drzewo lokalizacji --}}
        <div class="location-panel-center">
            @if($selectedWarehouseId)
                {{-- Filters --}}
                <div class="p-3 border-b border-gray-700 flex items-center gap-3">
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Szukaj lokalizacji..."
                           class="flex-1 bg-gray-800 border border-gray-600 text-white text-sm rounded-lg px-3 py-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e]">
                    <select wire:model.live="patternFilter"
                            class="bg-gray-800 border border-gray-600 text-white text-sm rounded-lg px-2 py-2">
                        <option value="all">Wszystkie typy</option>
                        <option value="coded">Kodowane (AA_01_03)</option>
                        <option value="dash">Myslnikowe (AA-01-03)</option>
                        <option value="wall">Scianowe</option>
                        <option value="named">Nazwane (SKLEP)</option>
                        <option value="gift">Prezentowe</option>
                    </select>
                    <select wire:model.live="occupancyFilter"
                            class="bg-gray-800 border border-gray-600 text-white text-sm rounded-lg px-2 py-2">
                        <option value="all">Zajete + Puste</option>
                        <option value="occupied">Tylko zajete</option>
                        <option value="empty">Tylko puste</option>
                    </select>
                </div>

                {{-- Tree --}}
                <div class="p-3 space-y-1 overflow-y-auto location-tree-scroll">
                    @forelse($locationTree as $zoneKey => $zone)
                        {{-- Zone level --}}
                        <div x-data="{ expanded: false }" wire:key="zone-{{ $zoneKey }}">
                            <button @click="expanded = !expanded"
                                    class="location-tree-item location-tree-item--zone w-full text-left flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="location-zone-indicator location-zone-indicator--{{ strtolower($zone['zone'] ?? 'other') }}"></span>
                                    <svg class="w-3 h-3 transition-transform" :class="expanded ? 'rotate-90' : ''"
                                         fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M6 6l4 4-4 4V6z"/>
                                    </svg>
                                    <span>{{ $zone['label'] }}</span>
                                </div>
                                <span class="location-product-count {{ ($zone['product_count'] ?? 0) === 0 ? 'location-product-count--zero' : (($zone['product_count'] ?? 0) >= 20 ? 'location-product-count--high' : '') }}">
                                    {{ $zone['product_count'] ?? 0 }}
                                </span>
                            </button>

                            <div x-show="expanded" x-collapse class="ml-4 space-y-0.5">
                                @if(!empty($zone['children']))
                                    @foreach($zone['children'] as $rowKey => $row)
                                        {{-- Row level --}}
                                        <div x-data="{ rowExpanded: false }" wire:key="row-{{ $zoneKey }}-{{ $rowKey }}">
                                            <button @click="rowExpanded = !rowExpanded"
                                                    class="location-tree-item location-tree-item--row w-full text-left flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-3 h-3 transition-transform" :class="rowExpanded ? 'rotate-90' : ''"
                                                         fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M6 6l4 4-4 4V6z"/>
                                                    </svg>
                                                    <span>{{ $row['label'] }}</span>
                                                </div>
                                                <span class="location-product-count">{{ $row['product_count'] ?? 0 }}</span>
                                            </button>

                                            <div x-show="rowExpanded" x-collapse class="ml-4 space-y-0.5">
                                                @if(!empty($row['children']))
                                                    @foreach($row['children'] as $shelfKey => $shelf)
                                                        {{-- Shelf level --}}
                                                        <div x-data="{ shelfExpanded: false }" wire:key="shelf-{{ $zoneKey }}-{{ $rowKey }}-{{ $shelfKey }}">
                                                            <button @click="shelfExpanded = !shelfExpanded"
                                                                    class="location-tree-item location-tree-item--shelf w-full text-left flex items-center justify-between">
                                                                <div class="flex items-center gap-2">
                                                                    <svg class="w-3 h-3 transition-transform" :class="shelfExpanded ? 'rotate-90' : ''"
                                                                         fill="currentColor" viewBox="0 0 20 20">
                                                                        <path d="M6 6l4 4-4 4V6z"/>
                                                                    </svg>
                                                                    <span>{{ $shelf['label'] }}</span>
                                                                </div>
                                                                <span class="location-product-count">{{ $shelf['product_count'] ?? 0 }}</span>
                                                            </button>

                                                            <div x-show="shelfExpanded" x-collapse class="ml-4 space-y-0.5">
                                                                @foreach($shelf['children'] ?? [] as $bin)
                                                                    {{-- Bin level (leaf) --}}
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
                                                @else
                                                    {{-- Direct bin children without shelf (named locations etc.) --}}
                                                    @foreach($row['bins'] ?? [] as $bin)
                                                        <button wire:click="selectLocation({{ $bin['id'] }})"
                                                                wire:key="bin-direct-{{ $bin['id'] }}"
                                                                class="location-tree-item location-tree-item--bin w-full text-left flex items-center justify-between
                                                                       {{ ($selectedLocationId ?? null) === ($bin['id'] ?? null) ? 'location-tree-item--selected' : '' }}">
                                                            <span>{{ $bin['code'] }}</span>
                                                            <span class="location-product-count">{{ $bin['product_count'] ?? 0 }}</span>
                                                        </button>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    {{-- Named location (SKLEP, UNAMEL) - direct zone-level location --}}
                                    @if(isset($zone['id']))
                                        <button wire:click="selectLocation({{ $zone['id'] }})"
                                                wire:key="zone-loc-{{ $zone['id'] }}"
                                                class="location-tree-item location-tree-item--bin w-full text-left flex items-center justify-between
                                                       {{ ($selectedLocationId ?? null) === ($zone['id'] ?? null) ? 'location-tree-item--selected' : '' }}">
                                            <span>{{ $zone['code'] ?? $zone['label'] }}</span>
                                            <span class="location-product-count">{{ $zone['product_count'] ?? 0 }}</span>
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-gray-500 py-8">
                            <p class="text-sm">Brak lokalizacji.</p>
                            <p class="text-xs mt-1">Kliknij "Skanuj product_stock" aby zaimportowac.</p>
                        </div>
                    @endforelse
                </div>
            @else
                {{-- No warehouse selected placeholder --}}
                <div class="flex items-center justify-center h-full text-gray-500">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <p class="text-sm">Wybierz magazyn z listy po lewej</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- RIGHT: Produkty w lokalizacji (conditional) --}}
        @if($selectedLocationId && $selectedLocationData)
            <div class="location-panel-right">
                {{-- Location header --}}
                <div class="p-3 border-b border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-white">{{ $selectedLocationData->code }}</h3>
                            <p class="text-xs text-gray-400 mt-0.5">
                                <span class="location-badge location-badge--{{ $selectedLocationData->pattern_type }}">
                                    {{ $selectedLocationData->pattern_type }}
                                </span>
                                &middot; {{ $selectedLocationData->product_count }} produktow
                            </p>
                        </div>
                        <div class="flex items-center gap-1">
                            <button wire:click="editLocation({{ $selectedLocationData->id }})"
                                    class="p-1.5 text-gray-400 hover:text-white rounded" title="Edytuj">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                            </button>
                            <button wire:click="deleteLocation({{ $selectedLocationData->id }})"
                                    wire:confirm="Czy na pewno chcesz usunac te lokalizacje?"
                                    class="p-1.5 text-gray-400 hover:text-red-400 rounded" title="Usun">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    @if($selectedLocationData->description)
                        <p class="text-xs text-gray-400 mt-2">{{ $selectedLocationData->description }}</p>
                    @endif
                </div>

                {{-- Product search --}}
                <div class="p-2 border-b border-gray-700">
                    <input type="text"
                           wire:model.live.debounce.300ms="productSearch"
                           placeholder="Szukaj produktu..."
                           class="w-full bg-gray-800 border border-gray-600 text-white text-xs rounded px-2 py-1.5 focus:ring-[#e0ac7e] focus:border-[#e0ac7e]">
                </div>

                {{-- Product list --}}
                <div class="overflow-y-auto location-product-list-scroll">
                    @forelse($products as $stockItem)
                        <div wire:key="stock-{{ $stockItem->id }}"
                             class="p-3 border-b border-gray-700/50 hover:bg-gray-800/50 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-white truncate">
                                        {{ $stockItem->product->sku ?? 'N/A' }}
                                    </p>
                                    <p class="text-xs text-gray-400 truncate mt-0.5">
                                        {{ $stockItem->product->name ?? '' }}
                                    </p>
                                    @if($stockItem->product->manufacturer ?? null)
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            {{ $stockItem->product->manufacturer->name }}
                                        </p>
                                    @endif
                                </div>
                                <a href="/admin/products/{{ $stockItem->product_id }}/edit"
                                   class="text-xs text-[#e0ac7e] hover:text-[#d1975a] flex-shrink-0 ml-2"
                                   title="Edytuj produkt">
                                    Edytuj
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-gray-500 text-xs">
                            Brak produktow w tej lokalizacji.
                        </div>
                    @endforelse
                </div>

                {{-- Pagination --}}
                @if($products->hasPages())
                    <div class="p-2 border-t border-gray-700">
                        {{ $products->links('livewire::simple-tailwind') }}
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- EDIT MODAL --}}
    @if($showEditModal)
        <div class="modal-overlay show" wire:click.self="$set('showEditModal', false)">
            <div class="audit-modal-dialog max-w-md">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Edytuj lokalizacje</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Kod</label>
                            <input type="text" wire:model="editCode" readonly
                                   class="w-full bg-gray-700 border border-gray-600 text-gray-400 text-sm rounded-lg px-3 py-2 cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Opis</label>
                            <input type="text" wire:model="editDescription"
                                   class="w-full bg-gray-700 border border-gray-600 text-white text-sm rounded-lg px-3 py-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e]"
                                   placeholder="Opcjonalny opis lokalizacji">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Notatki</label>
                            <textarea wire:model="editNotes" rows="3"
                                      class="w-full bg-gray-700 border border-gray-600 text-white text-sm rounded-lg px-3 py-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e]"
                                      placeholder="Dodatkowe notatki..."></textarea>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" wire:model="editIsActive" id="editIsActive"
                                   class="checkbox-enterprise">
                            <label for="editIsActive" class="text-sm text-gray-300">Aktywna</label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                        <button wire:click="$set('showEditModal', false)"
                                class="btn-enterprise-secondary text-sm px-4 py-2">
                            Anuluj
                        </button>
                        <button wire:click="saveLocation"
                                wire:loading.attr="disabled"
                                class="btn-enterprise-primary text-sm px-4 py-2">
                            Zapisz
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
