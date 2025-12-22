{{--
    compatibility-tab.blade.php
    ETAP_05d FAZA 4 - Vehicle Compatibility Tab for ProductForm

    Features:
    - Tile-based vehicle selection (Original/Zamiennik)
    - Per-shop context support
    - Brand-grouped vehicles with collapse
    - Search and filter
    - Integration with ProductForm save flow
--}}
<div class="tab-content active space-y-6">
    {{-- CSS loaded via admin.blade.php @vite - removed from here to fix Livewire DOM morphing issue --}}

    {{-- Header with counts --}}
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-white flex items-center">
            <svg class="w-6 h-6 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
            Dopasowania do pojazdow
        </h3>

        <div class="flex items-center space-x-3">
            {{-- Compatibility Counts --}}
            @php
                $counts = $this->getCompatibilityCounts();
            @endphp
            <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-blue-900/30 text-blue-200 border border-blue-700/50">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <circle cx="10" cy="10" r="6"/>
                </svg>
                Oryginal: {{ $counts['original'] }}
            </span>
            <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-orange-900/30 text-orange-200 border border-orange-700/50">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <circle cx="10" cy="10" r="6"/>
                </svg>
                Zamiennik: {{ $counts['zamiennik'] }}
            </span>

            {{-- Active Shop Indicator --}}
            @if(isset($selectedShop) && $selectedShop !== null && isset($availableShops))
                @php
                    $currentShop = collect($availableShops)->firstWhere('id', $selectedShop);
                @endphp
                <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-orange-900/30 text-orange-200 border border-orange-700/50">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ $currentShop['name'] ?? 'Sklep' }}
                </span>
            @endif
        </div>
    </div>

    {{-- Mode Selection & Filters Row --}}
    <div class="flex flex-wrap items-center gap-4 p-4 bg-gray-800/50 rounded-lg border border-gray-700">
        {{-- Selection Mode Toggle --}}
        <div class="flex items-center bg-gray-900/50 rounded-lg p-1">
            <button type="button"
                    wire:click="setCompatibilityMode('original')"
                    class="px-4 py-2 text-sm font-medium rounded-md transition-colors {{ $compatibilityMode === 'original' ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white' }}">
                <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <circle cx="10" cy="10" r="6"/>
                </svg>
                Oryginal
            </button>
            <button type="button"
                    wire:click="setCompatibilityMode('zamiennik')"
                    class="px-4 py-2 text-sm font-medium rounded-md transition-colors {{ $compatibilityMode === 'zamiennik' ? 'bg-orange-600 text-white' : 'text-gray-400 hover:text-white' }}">
                <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <circle cx="10" cy="10" r="6"/>
                </svg>
                Zamiennik
            </button>
        </div>

        <div class="h-6 w-px bg-gray-600"></div>

        {{-- Brand Filter --}}
        <div class="flex items-center space-x-2">
            <label class="text-xs text-gray-400">Marka:</label>
            <select wire:model.live="vehicleBrandFilter"
                    class="form-select-dark text-sm py-1.5 px-3 min-w-[150px]">
                <option value="">Wszystkie</option>
                @foreach($this->getAvailableBrands() as $brand)
                    <option value="{{ $brand }}">{{ $brand }}</option>
                @endforeach
            </select>
        </div>

        {{-- Vehicle Search --}}
        <div class="flex items-center space-x-2 flex-1">
            <label class="text-xs text-gray-400">Szukaj:</label>
            <div class="relative flex-1 max-w-xs">
                <input type="text"
                       wire:model.live.debounce.300ms="vehicleSearch"
                       placeholder="Nazwa lub SKU..."
                       class="form-input-dark text-sm py-1.5 pl-8 pr-3 w-full">
                <svg class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>

        {{-- Clear Filters --}}
        @if(!empty($vehicleBrandFilter) || !empty($vehicleSearch))
            <button type="button"
                    wire:click="$set('vehicleBrandFilter', ''); $set('vehicleSearch', '')"
                    class="text-xs text-gray-400 hover:text-white">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Reset
            </button>
        @endif
    </div>

    {{-- Legend --}}
    <div class="flex items-center gap-6 text-xs text-gray-400 px-2">
        <div class="flex items-center">
            <span class="w-4 h-4 rounded border-2 border-blue-500 bg-blue-500/20 mr-2"></span>
            <span>Oryginal - czesc pasuje oryginalnie</span>
        </div>
        <div class="flex items-center">
            <span class="w-4 h-4 rounded border-2 border-orange-500 bg-orange-500/20 mr-2"></span>
            <span>Zamiennik - czesc moze zastapic</span>
        </div>
        <div class="flex items-center">
            <span class="w-4 h-4 rounded border-2 mr-2 compat-legend-both"></span>
            <span>Oba typy</span>
        </div>
    </div>

    {{-- Vehicle Tiles by Brand --}}
    @php
        $filteredVehicles = $this->getFilteredVehiclesByBrand();
    @endphp

    @if(count($filteredVehicles) > 0)
        <div class="space-y-4">
            @foreach($filteredVehicles as $brand => $vehicles)
                <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden" wire:key="compat-brand-{{ $brand }}">
                    {{-- Brand Header (Collapsible) - FIX 2025-12-08: Changed from <button> to <div> to allow nested buttons --}}
                    <div class="w-full flex items-center justify-between p-3 hover:bg-gray-700/50 transition-colors cursor-pointer"
                         wire:click="toggleBrandCollapse('{{ $brand }}')">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-gray-400 transition-transform duration-200 mr-2 {{ in_array($brand, $collapsedBrands) ? '' : 'rotate-90' }}"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            <span class="text-white font-medium">{{ $brand }}</span>
                            <span class="ml-2 text-xs text-gray-500">({{ count($vehicles) }})</span>

                            {{-- Brand selection count --}}
                            @php
                                $brandOriginalCount = 0;
                                $brandZamiennikCount = 0;
                                foreach ($vehicles as $v) {
                                    if (in_array($v['id'], $compatibilityOriginal)) $brandOriginalCount++;
                                    if (in_array($v['id'], $compatibilityZamiennik)) $brandZamiennikCount++;
                                }
                            @endphp
                            @if($brandOriginalCount > 0 || $brandZamiennikCount > 0)
                                <span class="ml-3 text-xs">
                                    @if($brandOriginalCount > 0)
                                        <span class="text-blue-400">{{ $brandOriginalCount }}O</span>
                                    @endif
                                    @if($brandOriginalCount > 0 && $brandZamiennikCount > 0)
                                        <span class="text-gray-500">/</span>
                                    @endif
                                    @if($brandZamiennikCount > 0)
                                        <span class="text-orange-400">{{ $brandZamiennikCount }}Z</span>
                                    @endif
                                </span>
                            @endif
                        </div>

                        {{-- Brand Actions --}}
                        <div class="flex items-center space-x-2" wire:click.stop>
                            <button type="button"
                                    wire:click="selectAllVehiclesInBrand('{{ $brand }}')"
                                    class="p-1.5 text-gray-400 hover:text-green-400 hover:bg-gray-600/50 rounded transition-colors"
                                    title="Zaznacz wszystkie">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </button>
                            <button type="button"
                                    wire:click="deselectAllVehiclesInBrand('{{ $brand }}')"
                                    class="p-1.5 text-gray-400 hover:text-red-400 hover:bg-gray-600/50 rounded transition-colors"
                                    title="Odznacz wszystkie">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Vehicle Tiles Grid --}}
                    @unless(in_array($brand, $collapsedBrands))
                        <div class="vehicle-tiles-grid border-t border-gray-700">
                            @foreach($vehicles as $vehicle)
                                @php
                                    $vehicleId = $vehicle['id'];
                                    $tileClass = $this->getCompatibilityTileClass($vehicleId);
                                    $isOriginal = $this->isCompatibilityOriginal($vehicleId);
                                    $isZamiennik = $this->isCompatibilityZamiennik($vehicleId);
                                    $isBoth = $isOriginal && $isZamiennik;
                                @endphp
                                <div wire:key="compat-tile-{{ $vehicleId }}"
                                     wire:click="toggleCompatibilityVehicle({{ $vehicleId }})"
                                     class="vehicle-tile {{ $tileClass }}">
                                    <div class="vehicle-tile__content">
                                        <span class="vehicle-tile__brand">{{ $vehicle['manufacturer'] }}</span>
                                        <span class="vehicle-tile__model">{{ $vehicle['name'] }}</span>
                                        @if(!empty($vehicle['sku']))
                                            <span class="vehicle-tile__sku text-[10px] text-gray-500">{{ $vehicle['sku'] }}</span>
                                        @endif
                                    </div>

                                    {{-- Selection Indicator --}}
                                    @if($isBoth)
                                        <div class="vehicle-tile__indicator vehicle-tile__indicator--both">
                                            <span>O+Z</span>
                                        </div>
                                    @elseif($isOriginal)
                                        <div class="vehicle-tile__indicator vehicle-tile__indicator--original">
                                            <span>O</span>
                                        </div>
                                    @elseif($isZamiennik)
                                        <div class="vehicle-tile__indicator vehicle-tile__indicator--zamiennik">
                                            <span>Z</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endunless
                </div>
            @endforeach
        </div>
    @else
        {{-- Empty State --}}
        <div class="text-center py-16 bg-gray-800 rounded-lg border border-gray-700">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            @if(!empty($vehicleBrandFilter) || !empty($vehicleSearch))
                <h3 class="text-lg font-medium text-white mb-2">Brak wynikow</h3>
                <p class="text-sm text-gray-400 mb-4">
                    Nie znaleziono pojazdow pasujacych do filtrow
                </p>
                <button type="button"
                        wire:click="$set('vehicleBrandFilter', ''); $set('vehicleSearch', '')"
                        class="btn-enterprise-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Resetuj filtry
                </button>
            @else
                <h3 class="text-lg font-medium text-white mb-2">Brak pojazdow w systemie</h3>
                <p class="text-sm text-gray-400">
                    Aby dodac dopasowania, najpierw dodaj pojazdy do systemu (produkty typu "pojazd")
                </p>
            @endif
        </div>
    @endif

    {{-- Info Box --}}
    @if(isset($selectedShop) && $selectedShop && isset($product) && $product->id)
        <div class="p-4 bg-blue-900/20 border border-blue-700/50 rounded-lg">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h4 class="text-sm font-medium text-blue-300">Tryb sklepu</h4>
                    <p class="text-xs text-blue-400/70 mt-1">
                        Edytujesz dopasowania dla konkretnego sklepu. Zmiany zostana zapisane przy zapisywaniu produktu.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Pending Changes Indicator --}}
    @if(count($compatibilityPendingChanges) > 0)
        <div class="fixed bottom-24 right-6 z-40">
            <div class="bg-orange-900/90 border border-orange-600 rounded-lg shadow-lg px-4 py-2 text-sm text-orange-200">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                {{ count($compatibilityPendingChanges) }} niezapisanych zmian
            </div>
        </div>
    @endif
</div>
