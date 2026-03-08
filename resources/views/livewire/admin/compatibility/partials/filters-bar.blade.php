{{--
    Filters Bar Partial - Shared between Tab 1 (published) and Tab 2 (pending)

    Required variables:
    - $searchModel: string - wire:model name for search input ('searchPart' or 'searchPending')
    - $brands: Collection - available vehicle brands
    - $showShopContext: bool - whether to show shop context filter (Tab 1 only)
    - $shops: Collection|null - available shops (Tab 1 only)
    - $showNoMatches: bool - whether to show "no matches" checkbox (Tab 1 only)
    - $showAdvancedFilters: bool - whether to show category/manufacturer/compat count filters
--}}
@php
    $filterCounts = ($showAdvancedFilters ?? true) ? $this->getFilterCounts() : [];
@endphp
<div class="compat-filters-bar">
    <div class="compat-filters-row">
        {{-- Search Parts --}}
        <div class="compat-filter-item compat-filter-search">
            <label>
                <i class="fas fa-search"></i>
                Szukaj Czesci
            </label>
            <input
                type="text"
                wire:model.live.debounce.300ms="{{ $searchModel }}"
                placeholder="SKU lub nazwa..."
                class="compat-filter-input"
            />
        </div>

        {{-- Category Filter --}}
        @if($showAdvancedFilters ?? true)
            <div class="compat-filter-item compat-filter-category">
                <label>
                    <i class="fas fa-folder-tree"></i>
                    Kategoria
                </label>
                <select wire:model.live="filterCategory" class="compat-filter-select">
                    <option value="">Wszystkie kategorie</option>
                    @foreach($this->getCategoryFilterOptions() as $catId => $catName)
                        @php $catCount = $filterCounts['categories'][$catId] ?? 0; @endphp
                        <option value="{{ $catId }}">{{ $catName }} ({{ $catCount }})</option>
                    @endforeach
                </select>
            </div>
        @endif

        {{-- Shop Context (Tab 1 only) --}}
        @if($showShopContext ?? false)
            <div class="compat-filter-item">
                <label>
                    <i class="fas fa-store"></i>
                    Kontekst Sklepu
                </label>
                <select wire:model.live="shopContext" class="compat-filter-select">
                    <option value="">Dane domyslne</option>
                    @foreach($shops ?? [] as $shop)
                        <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        {{-- Brand Filter --}}
        <div class="compat-filter-item">
            <label>
                <i class="fas fa-car"></i>
                Marka Pojazdu
            </label>
            <select wire:model.live="filterBrand" class="compat-filter-select">
                <option value="">Wszystkie marki</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand }}">{{ $brand }}</option>
                @endforeach
            </select>
        </div>

        {{-- Vehicle Search --}}
        <div class="compat-filter-item">
            <label>
                <i class="fas fa-motorcycle"></i>
                Szukaj Pojazdu
            </label>
            <input
                type="text"
                wire:model.live.debounce.300ms="vehicleSearch"
                placeholder="Marka lub model..."
                class="compat-filter-input"
            />
        </div>
    </div>

    {{-- Row 2: Advanced filters --}}
    <div class="compat-filters-row compat-filters-row--secondary">
        {{-- Manufacturer Filter --}}
        @if($showAdvancedFilters ?? true)
            <div class="compat-filter-item">
                <label>
                    <i class="fas fa-industry"></i>
                    Producent
                </label>
                <select wire:model.live="filterManufacturer" class="compat-filter-select">
                    <option value="">Wszyscy producenci</option>
                    @foreach($this->getManufacturerFilterOptions() as $mfr)
                        @php $mfrCount = $filterCounts['manufacturers'][$mfr] ?? 0; @endphp
                        <option value="{{ $mfr }}">{{ $mfr }} ({{ $mfrCount }})</option>
                    @endforeach
                </select>
            </div>

            {{-- Shop Assignment Filter --}}
            <div class="compat-filter-item">
                <label>
                    <i class="fas fa-store-alt"></i>
                    Przypisanie do sklepu
                </label>
                <select wire:model.live="filterShopAssignment" class="compat-filter-select">
                    <option value="">Wszystkie</option>
                    <option value="none">Bez sklepu</option>
                    <option value="any">Dowolny sklep</option>
                    @foreach($this->getShopFilterOptions() as $shop)
                        <option value="shop_{{ $shop->id }}">{{ $shop->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Compatibility Count Range --}}
            <div class="compat-filter-item">
                <label>
                    <i class="fas fa-chart-bar"></i>
                    Dopasowania
                </label>
                <select wire:model.live="filterCompatCountRange" class="compat-filter-select">
                    <option value="">Dowolna ilosc</option>
                    <option value="0">Brak ({{ $filterCounts['compat_ranges']['0'] ?? 0 }})</option>
                    <option value="1-5">1 - 5 ({{ $filterCounts['compat_ranges']['1-5'] ?? 0 }})</option>
                    <option value="6-20">6 - 20 ({{ $filterCounts['compat_ranges']['6-20'] ?? 0 }})</option>
                    <option value="20+">Powyzej 20 ({{ $filterCounts['compat_ranges']['20+'] ?? 0 }})</option>
                </select>
            </div>
        @endif

        {{-- No Matches Checkbox (Tab 1 only) --}}
        @if($showNoMatches ?? false)
            <div class="compat-filter-item compat-filter-checkbox">
                <label class="compat-checkbox-label">
                    <input
                        type="checkbox"
                        wire:model.live="filterNoMatches"
                        class="compat-checkbox"
                    />
                    <span>
                        <i class="fas fa-exclamation-circle"></i>
                        Tylko bez dopasowan
                    </span>
                </label>
            </div>
        @endif

        {{-- Preset Controls --}}
        @include('livewire.admin.compatibility.partials.preset-controls')

        {{-- Active Filters Count + Reset --}}
        @if($this->hasActiveFilters())
            <div class="compat-filter-item compat-filter-actions">
                <span class="compat-active-filters-badge">
                    <i class="fas fa-filter"></i>
                    {{ $this->getActiveFiltersCount() }} {{ $this->getActiveFiltersCount() === 1 ? 'filtr' : 'filtrow' }}
                </span>
                <button wire:click="resetFilters" class="compat-btn-reset">
                    <i class="fas fa-undo"></i>
                    Resetuj
                </button>
            </div>
        @endif
    </div>
</div>
