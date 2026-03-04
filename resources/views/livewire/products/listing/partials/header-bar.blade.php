{{-- Header Section --}}
<div class="sticky top-0 z-40 glass-effect border-b border-primary shadow-lg">
    <div class="px-4 sm:px-6 lg:px-8 py-4">
        {{-- Title & Action Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-xl sm:text-2xl font-bold text-white truncate">Produkty</h1>
                <p class="mt-1 text-sm text-gray-400">
                    Zarządzaj wszystkimi produktami w systemie PIM
                </p>
            </div>

            {{-- Global Search Bar - always visible --}}
            <div class="flex-1 max-w-sm mx-4 hidden md:block">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input wire:model.live.debounce.500ms="search"
                           type="text"
                           placeholder="Szukaj: SKU, nazwa, kod dostawcy..."
                           class="product-list-search-input form-input w-full pl-10 pr-4 py-2 rounded-lg text-sm">
                </div>
            </div>

            {{-- Primary Actions --}}
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                {{-- Add Product Button --}}
                @if($this->userCan('create'))
                <a href="{{ route('admin.products.create') }}"
                   class="btn-primary inline-flex items-center px-3 sm:px-4 py-2 text-white text-sm font-medium rounded-lg transition-all duration-300 whitespace-nowrap">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="hidden sm:inline">Dodaj produkt</span>
                    <span class="sm:hidden ml-1">Dodaj</span>
                </a>
                @endif

                {{-- Import from PrestaShop Button --}}
                @if($this->userCan('import'))
                <button wire:click="openImportModal('all')"
                        class="btn-secondary inline-flex items-center px-3 sm:px-4 py-2 text-sm font-medium rounded-lg transition-all duration-300 whitespace-nowrap">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                    </svg>
                    <span class="hidden lg:inline">Importuj z PrestaShop</span>
                    <span class="lg:hidden ml-1">Import</span>
                </button>
                @endif

                {{-- Import from ERP Button (FAZA 10) --}}
                @if($this->userCan('import'))
                <button wire:click="openERPImportModal"
                        class="btn-secondary inline-flex items-center px-3 sm:px-4 py-2 text-sm font-medium rounded-lg transition-all duration-300 whitespace-nowrap">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    <span class="hidden lg:inline">Import z ERP</span>
                    <span class="lg:hidden ml-1">ERP</span>
                </button>
                @endif

                {{-- View Mode Toggle --}}
                <div class="flex bg-card rounded-lg p-1">
                    <button wire:click="changeViewMode('table')"
                            class="px-2 sm:px-3 py-1 text-sm rounded-md transition-all duration-300 {{ $viewMode === 'table' ? 'bg-orange-500 text-white shadow-soft' : 'text-muted hover:text-primary hover:bg-card-hover' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </button>
                    <button wire:click="changeViewMode('grid')"
                            class="px-2 sm:px-3 py-1 text-sm rounded-md transition-all duration-300 {{ $viewMode === 'grid' ? 'bg-orange-500 text-white shadow-soft' : 'text-muted hover:text-primary hover:bg-card-hover' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </button>
                </div>

                {{-- Column Customizer --}}
                @include('livewire.products.listing.partials.column-customizer')

                {{-- Filter Presets --}}
                @include('livewire.products.listing.partials.filter-presets')

                {{-- Filters Toggle --}}
                <button wire:click="$toggle('showFilters')"
                        class="btn-secondary inline-flex items-center px-3 py-2 border border-primary text-sm font-medium rounded-lg text-secondary hover:bg-card-hover transition-all duration-300 focus:ring-2 focus:ring-orange-500 focus:ring-opacity-50 whitespace-nowrap">
                    <svg class="w-4 h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z" />
                    </svg>
                    <span class="hidden sm:inline">Filtry</span>
                    @if($hasFilters)
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-500 text-white">
                            <span class="hidden sm:inline">Aktywne</span>
                            <span class="sm:hidden">!</span>
                        </span>
                    @endif
                </button>
            </div>
        </div>

        {{-- Filters Panel --}}
        @if($showFilters)
            @include('livewire.products.listing.partials.filters-panel')
        @endif

        {{-- Selection Banners --}}
        @include('livewire.products.listing.partials.bulk-actions-bar')
    </div>
</div>
