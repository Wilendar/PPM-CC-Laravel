{{--
    CompatibilityModal - ETAP_06 FAZA 5.6 (REDESIGNED + BULK MODE)
    Tile-based vehicle compatibility modal - KOPIA widoku /admin/compatibility

    Features:
    - Tile-based vehicle selection (click = toggle)
    - Per-shop filtering
    - Brand sections with collapse/expand
    - Selection mode toggle (Original/Zamiennik)
    - BULK MODE: Edit multiple products with sidebar
--}}
<div>
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-hidden" aria-labelledby="compatibility-modal-title" role="dialog" aria-modal="true">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-black/70 transition-opacity" wire:click="closeModal"></div>

        {{-- Modal container - Full height --}}
        <div class="fixed inset-4 flex items-stretch justify-center">
            <div class="relative w-full max-w-7xl bg-gray-800 rounded-xl shadow-2xl border border-gray-700 flex flex-col"
                 @keydown.escape.window="$wire.closeModal()">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700 flex-shrink-0">
                    <div class="flex items-center gap-4">
                        <div class="p-2 bg-blue-600/20 rounded-lg">
                            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                        </div>
                        <div>
                            <h3 id="compatibility-modal-title" class="text-lg font-semibold text-white">
                                Dopasowania pojazdow
                                @if($bulkMode)
                                    <span class="ml-2 px-2 py-0.5 bg-purple-600 text-white text-xs rounded-full">
                                        BULK: {{ count($bulkProductIds) }} produktow
                                    </span>
                                @endif
                            </h3>
                            @if($bulkMode)
                                <p class="text-sm text-gray-400">
                                    Edycja dopasowań dla {{ count($bulkProductIds) }} wybranych produktów
                                </p>
                            @elseif($pendingProductData)
                            <p class="text-sm text-gray-400">
                                <span class="font-mono text-green-400">{{ $pendingProductData['sku'] ?? '-' }}</span>
                                <span class="mx-2">-</span>
                                <span>{{ $pendingProductData['name'] ?? '(brak nazwy)' }}</span>
                            </p>
                            @endif
                        </div>
                    </div>

                    {{-- Selection Counts --}}
                    <div class="flex items-center gap-6">
                        <div class="flex items-center gap-4 text-sm">
                            <span class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                                <span class="text-gray-400">Oryginal:</span>
                                <span class="text-white font-medium">{{ $this->getOriginalCount() }}</span>
                            </span>
                            <span class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-orange-500"></span>
                                <span class="text-gray-400">Zamiennik:</span>
                                <span class="text-white font-medium">{{ $this->getZamiennikCount() }}</span>
                            </span>
                        </div>

                        <button wire:click="closeModal"
                                class="text-gray-400 hover:text-white transition-colors p-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Filters Bar --}}
                <div class="px-6 py-3 border-b border-gray-700 bg-gray-800/50 flex-shrink-0">
                    <div class="flex flex-wrap items-center gap-4">
                        {{-- Shop Context --}}
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-gray-400 uppercase tracking-wider">Sklep</label>
                            <select wire:model.live="shopContext" class="form-select-dark-sm text-sm">
                                <option value="">Wszystkie pojazdy</option>
                                @foreach($this->shops as $shop)
                                    <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Brand Filter --}}
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-gray-400 uppercase tracking-wider">Marka</label>
                            <select wire:model.live="filterBrand" class="form-select-dark-sm text-sm">
                                <option value="">Wszystkie marki</option>
                                @foreach($this->brands as $brand)
                                    <option value="{{ $brand }}">{{ $brand }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Vehicle Search --}}
                        <div class="flex items-center gap-2 flex-1 max-w-xs">
                            <label class="text-xs text-gray-400 uppercase tracking-wider">Szukaj</label>
                            <input type="text"
                                   wire:model.live.debounce.300ms="vehicleSearch"
                                   placeholder="Nazwa lub SKU pojazdu..."
                                   class="form-input-dark-sm text-sm flex-1">
                        </div>

                        {{-- Copy from Product --}}
                        <div class="flex items-center gap-2">
                            <input type="text"
                                   wire:model="copyFromSku"
                                   placeholder="Kopiuj z SKU..."
                                   class="form-input-dark-sm text-sm w-32"
                                   wire:keydown.enter="copyFromProduct">
                            <button type="button"
                                    wire:click="copyFromProduct"
                                    @disabled(empty($copyFromSku))
                                    class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded
                                           transition-colors text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Reset Filters --}}
                        @if($filterBrand || $vehicleSearch || $shopContext)
                            <button wire:click="resetFilters"
                                    class="px-3 py-1.5 bg-gray-600 hover:bg-gray-500 text-white rounded text-sm">
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Reset
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Body - Flex container for sidebar + vehicle tiles --}}
                <div class="flex-1 flex overflow-hidden">

                    {{-- BULK MODE SIDEBAR --}}
                    @if($bulkMode && !empty($bulkProductsData))
                    <div class="w-64 flex-shrink-0 bg-gray-850 border-r border-gray-700 flex flex-col">
                        {{-- Sidebar Header --}}
                        <div class="px-4 py-3 border-b border-gray-700 bg-gray-800/50">
                            <h4 class="text-sm font-medium text-gray-300">
                                Wybrane produkty ({{ count($bulkProductsData) }})
                            </h4>
                            <p class="text-xs text-gray-500 mt-1">
                                Kliknij aby podejrzec aktualne dopasowania
                            </p>
                        </div>

                        {{-- Products List --}}
                        <div class="flex-1 overflow-y-auto">
                            @foreach($bulkProductsData as $product)
                                <button wire:click="selectBulkProduct({{ $product['id'] }})"
                                        class="w-full px-4 py-3 text-left border-b border-gray-700/50 transition-colors
                                               {{ $activeProductId === $product['id']
                                                   ? 'bg-purple-600/20 border-l-2 border-l-purple-500'
                                                   : 'hover:bg-gray-700/30' }}">
                                    <div class="text-sm font-mono text-green-400 truncate">
                                        {{ $product['sku'] }}
                                    </div>
                                    <div class="text-xs text-gray-400 truncate mt-1">
                                        {{ Str::limit($product['name'], 28) }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        @if($product['compatibility_count'] > 0)
                                            <span class="text-blue-400">{{ $product['compatibility_count'] }} dopasowan</span>
                                        @else
                                            <span class="text-gray-600">brak dopasowan</span>
                                        @endif
                                    </div>
                                </button>
                            @endforeach
                        </div>

                        {{-- Sidebar Footer Info --}}
                        <div class="px-4 py-3 border-t border-gray-700 bg-gray-800/30">
                            <div class="text-xs text-yellow-400">
                                <svg class="w-3.5 h-3.5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Zmiany zostana zapisane do WSZYSTKICH produktow
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Vehicle Tiles Area --}}
                    <div class="flex-1 overflow-y-auto p-6">
                    {{-- Vehicle Tiles Grid by Brand --}}
                    <div class="space-y-4">
                        @forelse($this->vehiclesGrouped as $brand => $brandVehicles)
                            <div class="brand-section bg-gray-700/30 rounded-lg overflow-hidden" wire:key="brand-{{ $brand }}">
                                {{-- Brand Header --}}
                                <div class="flex items-center justify-between px-4 py-3 bg-gray-700/50 cursor-pointer hover:bg-gray-700/70 transition-colors"
                                     wire:click="toggleBrandCollapse('{{ $brand }}')">
                                    <div class="flex items-center gap-3">
                                        <svg class="w-4 h-4 text-gray-400 transition-transform {{ $this->isBrandCollapsed($brand) ? '' : 'rotate-90' }}"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                        <span class="font-medium text-white">{{ $brand }}</span>
                                        <span class="px-2 py-0.5 bg-gray-600 text-gray-300 text-xs rounded-full">
                                            {{ $brandVehicles->count() }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <button wire:click.stop="selectAllInBrand('{{ $brand }}')"
                                                class="px-2 py-1 bg-gray-600 hover:bg-gray-500 text-xs text-gray-300 rounded transition-colors"
                                                title="Zaznacz wszystkie">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </button>
                                        <button wire:click.stop="deselectAllInBrand('{{ $brand }}')"
                                                class="px-2 py-1 bg-gray-600 hover:bg-gray-500 text-xs text-gray-300 rounded transition-colors"
                                                title="Odznacz wszystkie">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Vehicle Tiles --}}
                                @unless($this->isBrandCollapsed($brand))
                                    <div class="p-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
                                        @foreach($brandVehicles as $vehicle)
                                            @php
                                                $stateClass = $this->getVehicleStateClass($vehicle->id);
                                                $isOriginal = $this->isOriginal($vehicle->id);
                                                $isZamiennik = $this->isZamiennik($vehicle->id);
                                                $isBoth = $this->isBoth($vehicle->id);
                                            @endphp
                                            <div wire:key="tile-{{ $vehicle->id }}"
                                                 wire:click="toggleVehicle({{ $vehicle->id }})"
                                                 class="relative p-3 rounded-lg cursor-pointer transition-all
                                                        border-2
                                                        {{ $isBoth ? 'bg-gradient-to-br from-blue-600/30 to-orange-600/30 border-purple-500' :
                                                           ($isOriginal ? 'bg-blue-600/20 border-blue-500' :
                                                           ($isZamiennik ? 'bg-orange-600/20 border-orange-500' :
                                                           'bg-gray-700/50 border-gray-600 hover:border-gray-500')) }}">
                                                {{-- Vehicle Info --}}
                                                <div class="text-center">
                                                    <div class="text-xs text-gray-400 truncate">{{ $vehicle->manufacturer }}</div>
                                                    <div class="text-sm text-white font-medium truncate" title="{{ $vehicle->name }}">
                                                        {{ Str::limit($vehicle->name, 20) }}
                                                    </div>
                                                    <div class="text-xs text-gray-500 font-mono truncate">{{ $vehicle->sku }}</div>
                                                </div>

                                                {{-- Selection Indicator --}}
                                                @if($isBoth)
                                                    <div class="absolute -top-1 -right-1 px-1.5 py-0.5 bg-purple-500 text-white text-xs font-bold rounded">
                                                        O+Z
                                                    </div>
                                                @elseif($isOriginal)
                                                    <div class="absolute -top-1 -right-1 px-1.5 py-0.5 bg-blue-500 text-white text-xs font-bold rounded">
                                                        O
                                                    </div>
                                                @elseif($isZamiennik)
                                                    <div class="absolute -top-1 -right-1 px-1.5 py-0.5 bg-orange-500 text-white text-xs font-bold rounded">
                                                        Z
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endunless
                            </div>
                        @empty
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 mx-auto text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                                </svg>
                                <p class="text-gray-400 text-lg">Brak pojazdow dla wybranych filtrow</p>
                                @if($filterBrand || $vehicleSearch || $shopContext)
                                    <button wire:click="resetFilters"
                                            class="mt-4 px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition-colors">
                                        Resetuj filtry
                                    </button>
                                @endif
                            </div>
                        @endforelse
                    </div>
                    </div>
                </div>
                {{-- End of Body Flex container --}}

                {{-- Footer - Floating Action Bar --}}
                <div class="flex items-center justify-between px-6 py-4 border-t border-gray-700 bg-gray-800/80 flex-shrink-0">
                    {{-- Left: Selection Mode Toggle + Skip Info --}}
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-400">Tryb zaznaczania:</span>
                        <div class="flex rounded-lg overflow-hidden border border-gray-600">
                            <button wire:click="setSelectionMode('original')"
                                    class="px-4 py-2 text-sm font-medium transition-colors
                                           {{ $selectionMode === 'original'
                                               ? 'bg-blue-600 text-white'
                                               : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                                <span class="w-2 h-2 inline-block rounded-full bg-blue-400 mr-2"></span>
                                Oryginal
                            </button>
                            <button wire:click="setSelectionMode('zamiennik')"
                                    class="px-4 py-2 text-sm font-medium transition-colors
                                           {{ $selectionMode === 'zamiennik'
                                               ? 'bg-orange-600 text-white'
                                               : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                                <span class="w-2 h-2 inline-block rounded-full bg-orange-400 mr-2"></span>
                                Zamiennik
                            </button>
                        </div>

                        {{-- Pending Changes Badge --}}
                        @if($this->hasPendingChanges())
                            <span class="px-2 py-1 bg-yellow-600/30 text-yellow-400 text-sm rounded-lg">
                                {{ $this->getPendingChangesCount() }} zmian
                            </span>
                        @endif

                        {{-- Skip compatibility info badge (not in bulk mode) --}}
                        @if(!$bulkMode && $this->isSkipped)
                        <div class="flex items-center gap-2 px-3 py-1.5 bg-red-900/30 border border-red-600/50 rounded-lg">
                            <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            <span class="text-sm text-red-400">Oznaczono jako "Brak dopasowan"</span>
                            <button type="button"
                                    wire:click="clearSkipCompatibility"
                                    class="ml-1 text-red-400 hover:text-red-300 underline text-xs">
                                Cofnij
                            </button>
                        </div>
                        @endif
                    </div>

                    {{-- Right: Action Buttons --}}
                    <div class="flex items-center gap-3">
                        {{-- Clear All --}}
                        @if($this->getOriginalCount() > 0 || $this->getZamiennikCount() > 0)
                            <button wire:click="clearAllSelections"
                                    wire:confirm="Czy na pewno usunac wszystkie zaznaczenia?"
                                    class="px-4 py-2 bg-red-600/30 hover:bg-red-600/50 text-red-400 rounded-lg transition-colors text-sm">
                                Wyczysc wszystkie
                            </button>
                        @endif

                        {{-- Brak dopasowan button (not in bulk mode) --}}
                        @if(!$bulkMode && !$this->isSkipped)
                        <button type="button"
                                wire:click="setSkipCompatibility"
                                wire:confirm="Czy na pewno oznaczyc jako 'Brak dopasowan'? Produkt zostanie oznaczony jako kompletny bez dopasowan."
                                @disabled($isProcessing)
                                class="px-4 py-2 bg-red-600/30 hover:bg-red-600/50 text-red-400 border border-red-600/50
                                       rounded-lg transition-colors text-sm disabled:opacity-50 disabled:cursor-not-allowed
                                       flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Brak dopasowan
                        </button>
                        @endif

                        <button wire:click="closeModal"
                                class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition-colors">
                            Anuluj
                        </button>

                        <button wire:click="saveCompatibilities"
                                @disabled($isProcessing)
                                class="px-6 py-2 {{ $bulkMode ? 'bg-purple-600 hover:bg-purple-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg transition-colors
                                       font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                            @if($isProcessing)
                                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Zapisywanie...</span>
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                @if($bulkMode)
                                    <span>Zapisz do {{ count($bulkProductIds) }} produktow</span>
                                @else
                                    <span>Zapisz dopasowania</span>
                                @endif
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
