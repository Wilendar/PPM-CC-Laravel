<div class="min-h-screen bg-main-gradient">
    {{-- Header Section --}}
    <div class="sticky top-0 z-40 glass-effect border-b border-primary shadow-lg">
        <div class="px-6 sm:px-8 lg:px-12 py-4">
            {{-- Title & Action Bar --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white">Produkty</h1>
                    <p class="mt-1 text-sm text-gray-400">
                        Zarządzaj wszystkimi produktami w systemie PIM
                    </p>
                </div>

                {{-- Primary Actions --}}
                <div class="flex items-center space-x-3">
                    {{-- Add Product Button --}}
                    <a href="{{ route('admin.products.create') }}"
                       class="btn-primary inline-flex items-center px-4 py-2 text-white text-sm font-medium rounded-lg transition-all duration-300">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Dodaj produkt
                    </a>

                    {{-- Import from PrestaShop Button --}}
                    <button wire:click="openImportModal('all')"
                            class="btn-secondary inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all duration-300">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                        </svg>
                        Importuj z PrestaShop
                    </button>

                    {{-- View Mode Toggle --}}
                    <div class="flex bg-card rounded-lg p-1">
                        <button wire:click="changeViewMode('table')"
                                class="px-3 py-1 text-sm rounded-md transition-all duration-300 {{ $viewMode === 'table' ? 'bg-orange-500 text-white shadow-soft' : 'text-muted hover:text-primary hover:bg-card-hover' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                        </button>
                        <button wire:click="changeViewMode('grid')"
                                class="px-3 py-1 text-sm rounded-md transition-all duration-300 {{ $viewMode === 'grid' ? 'bg-orange-500 text-white shadow-soft' : 'text-muted hover:text-primary hover:bg-card-hover' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                        </button>
                    </div>

                    {{-- Filters Toggle --}}
                    <button wire:click="$toggle('showFilters')"
                            class="btn-secondary inline-flex items-center px-3 py-2 border border-primary text-sm font-medium rounded-lg text-secondary hover:bg-card-hover transition-all duration-300 focus:ring-2 focus:ring-orange-500 focus:ring-opacity-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z" />
                        </svg>
                        Filtry
                        @if($hasFilters)
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-500 text-white">
                                Aktywne
                            </span>
                        @endif
                    </button>
                </div>
            </div>

            {{-- Filters Panel --}}
            @if($showFilters)
                <div class="mt-4 p-4 card glass-effect rounded-lg border border-primary">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        {{-- Search --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Wyszukaj</label>
                            <input wire:model.live.debounce.500ms="search"
                                   type="text"
                                   placeholder="SKU, nazwa, kod dostawcy..."
                                   class="form-input w-full rounded-lg shadow-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
                        </div>

                        {{-- Category Filter --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Kategoria</label>
                            <select wire:model.live="categoryFilter"
                                    class="form-input w-full rounded-lg shadow-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
                                <option value="">Wszystkie kategorie</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">
                                        {{ str_repeat('--', $category->level) }} {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Status Filter --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Status</label>
                            <select wire:model.live="statusFilter"
                                    class="form-input w-full rounded-lg shadow-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
                                <option value="all">Wszystkie statusy</option>
                                <option value="active">Aktywne</option>
                                <option value="inactive">Nieaktywne</option>
                            </select>
                        </div>

                        {{-- Stock Filter --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Stan magazynowy</label>
                            <select wire:model.live="stockFilter"
                                    class="form-input w-full rounded-lg shadow-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
                                <option value="all">Wszystkie stany</option>
                                <option value="in_stock">Na stanie</option>
                                <option value="low_stock">Niski stan</option>
                                <option value="out_of_stock">Brak na stanie</option>
                            </select>
                        </div>

                        {{-- Product Type Filter --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Typ produktu</label>
                            <select wire:model.live="productTypeFilter"
                                    class="form-input w-full rounded-lg shadow-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
                                <option value="all">Wszystkie typy</option>
                                <option value="vehicle">Pojazd</option>
                                <option value="spare_part">Część zamienna</option>
                                <option value="clothing">Odzież</option>
                                <option value="other">Inne</option>
                            </select>
                        </div>

                        {{-- ETAP_05 - Advanced Filters (1.1.1.2.4-1.1.1.2.8) --}}

                        {{-- 1.1.1.2.4: Price Range Filter --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-300 mb-2">Zakres cen (PLN)</label>
                            <div class="flex items-center space-x-2">
                                <input wire:model.live="priceMin"
                                       type="number"
                                       min="0"
                                       step="0.01"
                                       placeholder="Od"
                                       class="form-input w-full rounded-lg shadow-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
                                <span class="text-gray-400">-</span>
                                <input wire:model.live="priceMax"
                                       type="number"
                                       min="0"
                                       step="0.01"
                                       placeholder="Do"
                                       class="form-input w-full rounded-lg shadow-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
                            </div>
                        </div>

                        {{-- 1.1.1.2.5: Date Range Filters --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Typ daty</label>
                            <select wire:model.live="dateType"
                                    class="form-input w-full rounded-lg shadow-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
                                <option value="created_at">Data utworzenia</option>
                                <option value="updated_at">Data modyfikacji</option>
                                <option value="last_sync_at">Ostatnia synchronizacja</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Data od</label>
                            <input wire:model.live="dateFrom"
                                   type="date"
                                   class="form-input w-full rounded-lg shadow-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Data do</label>
                            <input wire:model.live="dateTo"
                                   type="date"
                                   class="form-input w-full rounded-lg shadow-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
                        </div>

                        {{-- 1.1.1.2.7: Integration Status Filter --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Status integracji</label>
                            <select wire:model.live="integrationFilter"
                                    class="form-input w-full rounded-lg shadow-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
                                <option value="all">Wszystkie statusy</option>
                                <option value="synced">Zsynchronizowane</option>
                                <option value="pending">Oczekujące</option>
                                <option value="error">Błąd synchronizacji</option>
                            </select>
                        </div>

                        {{-- 1.1.1.2.8: Media Status Filter --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Status mediów</label>
                            <select wire:model.live="mediaFilter"
                                    class="form-input w-full rounded-lg shadow-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
                                <option value="all">Wszystkie</option>
                                <option value="has_images">Ze zdjęciami</option>
                                <option value="no_images">Bez zdjęć</option>
                                <option value="primary_image">Z głównym zdjęciem</option>
                            </select>
                        </div>
                    </div>

                    {{-- Filter Actions --}}
                    @if($hasFilters)
                        <div class="mt-4 flex items-center justify-between">
                            <p class="text-sm text-gray-400">
                                {{ $products->total() }} produktów znalezionych
                            </p>
                            <button wire:click="clearFilters"
                                    class="text-sm text-orange-500 hover:text-orange-400 transition-colors duration-300">
                                Wyczyść filtry
                            </button>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Bulk Actions Bar --}}
            @if($showBulkActions)
                <div class="mt-4 p-3 bg-orange-900/20 border border-orange-800 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="text-sm font-medium text-orange-200">
                                Zaznaczono {{ $selectedCount }} {{ $selectedCount == 1 ? 'produkt' : ($selectedCount < 5 ? 'produkty' : 'produktów') }}
                            </span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button class="px-3 py-1 text-sm btn-secondary rounded hover:bg-card-hover transition-all duration-300">
                                Aktywuj
                            </button>
                            <button class="px-3 py-1 text-sm btn-secondary rounded hover:bg-card-hover transition-all duration-300">
                                Deaktywuj
                            </button>
                            <button class="px-3 py-1 text-sm btn-secondary rounded hover:bg-card-hover transition-all duration-300">
                                Przypisz kategorię
                            </button>
                            <button wire:click="openQuickSendModal"
                                    class="px-3 py-1 text-sm bg-purple-600 hover:bg-purple-700 text-white rounded transition-all duration-300">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                                Wyślij na sklepy
                            </button>
                            <button wire:click="resetSelection"
                                    class="px-3 py-1 text-sm text-muted hover:text-primary transition-colors duration-300">
                                Anuluj
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Main Content --}}
    <div class="px-6 sm:px-8 lg:px-12 py-6">
        @if($viewMode === 'table')
            {{-- Table View --}}
            <div class="card glass-effect shadow-soft rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-700">
                        <thead class="bg-card">
                            <tr>
                                {{-- Bulk Select --}}
                                <th class="w-12 px-6 py-3">
                                    <input type="checkbox"
                                           wire:model.live="selectAll"
                                           class="rounded border-primary text-orange-500 shadow-sm focus:ring-orange-500 focus:ring-opacity-50 bg-input">
                                </th>

                                {{-- Sortable Headers --}}
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider cursor-pointer hover:bg-card-hover transition-all duration-300"
                                    wire:click="sortBy('sku')">
                                    <div class="flex items-center">
                                        SKU
                                        @if($sortBy === 'sku')
                                            <svg class="w-4 h-4 ml-1 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                            </svg>
                                        @endif
                                    </div>
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider cursor-pointer hover:bg-card-hover transition-all duration-300"
                                    wire:click="sortBy('name')">
                                    <div class="flex items-center">
                                        Nazwa
                                        @if($sortBy === 'name')
                                            <svg class="w-4 h-4 ml-1 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                            </svg>
                                        @endif
                                    </div>
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                                    Typ
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                                    Producent
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                                    Status
                                </th>

                                {{-- FAZA 1.5: Multi-Store Sync Status Column --}}
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                        </svg>
                                        Sync Status
                                    </div>
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider cursor-pointer hover:bg-card-hover transition-all duration-300"
                                    wire:click="sortBy('updated_at')">
                                    <div class="flex items-center">
                                        Ostatnia aktualizacja
                                        @if($sortBy === 'updated_at')
                                            <svg class="w-4 h-4 ml-1 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                            </svg>
                                        @endif
                                    </div>
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-medium text-muted uppercase tracking-wider">
                                    Akcje
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-card divide-y divide-border-primary">
                            @forelse($products as $product)
                                <tr class="hover:bg-card-hover transition-all duration-300">
                                    {{-- Bulk Select --}}
                                    <td class="px-6 py-4">
                                        <input type="checkbox"
                                               wire:key="select-{{ $product->id }}"
                                               value="{{ $product->id }}"
                                               wire:model.live="selectedProducts"
                                               class="rounded border-primary text-orange-500 shadow-sm focus:ring-orange-500 focus:ring-opacity-50 bg-input">
                                    </td>

                                    {{-- SKU --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-primary">
                                            {{ $product->sku }}
                                        </div>
                                        @if($product->supplier_code)
                                            <div class="text-xs text-muted">
                                                {{ $product->supplier_code }}
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Name --}}
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-primary">
                                            <a href="{{ route('products.edit', $product) }}"
                                               class="hover:text-orange-500 transition-colors duration-300">
                                                {{ Str::limit($product->name, 50) }}
                                            </a>
                                        </div>
                                        @if($product->is_variant_master)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-800 text-purple-200">
                                                Master
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Type --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($product->productType)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                            @switch($product->productType->slug)
                                                @case('pojazd') bg-green-800 text-green-200 @break
                                                @case('czesc-zamienna') bg-blue-800 text-blue-200 @break
                                                @case('odziez') bg-yellow-800 text-yellow-200 @break
                                                @default bg-gray-800 text-gray-200
                                            @endswitch">
                                            {{ $product->productType->name }}
                                        </span>
                                        @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-800 text-gray-200">
                                            Brak typu
                                        </span>
                                        @endif
                                    </td>

                                    {{-- Manufacturer --}}
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-primary">
                                        {{ $product->manufacturer ?? '-' }}
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button wire:click="toggleStatus({{ $product->id }})"
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium transition-colors
                                                    {{ $product->is_active
                                                        ? 'bg-green-800 text-green-200 hover:bg-green-700'
                                                        : 'bg-red-800 text-red-200 hover:bg-red-700' }}">
                                            <span class="w-2 h-2 rounded-full mr-1 {{ $product->is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                            {{ $product->is_active ? 'Aktywny' : 'Nieaktywny' }}
                                        </button>
                                    </td>

                                    {{-- FAZA 1.5: Multi-Store Sync Status --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $syncSummary = $product->getMultiStoreSyncSummary();
                                            $totalShops = $syncSummary['total_shops'];
                                            $syncedShops = $syncSummary['synced_shops'];
                                            $conflictShops = $syncSummary['conflict_shops'];
                                            $errorShops = $syncSummary['error_shops'];
                                            $healthPercentage = $syncSummary['sync_health_percentage'];
                                        @endphp

                                        @if($totalShops === 0)
                                            {{-- No shops configured --}}
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-800 text-gray-300">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                                </svg>
                                                Brak sklepów
                                            </span>
                                        @elseif($healthPercentage >= 90)
                                            {{-- Excellent sync health --}}
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-800 text-green-200">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                {{ $syncedShops }}/{{ $totalShops }} sklepów
                                            </span>
                                        @elseif($healthPercentage >= 70)
                                            {{-- Good sync health --}}
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-800 text-yellow-200">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                                </svg>
                                                {{ $syncedShops }}/{{ $totalShops }} OK
                                            </span>
                                        @elseif($conflictShops > 0)
                                            {{-- Has conflicts --}}
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-800 text-orange-200">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                                </svg>
                                                {{ $conflictShops }} konfliktów
                                            </span>
                                        @elseif($errorShops > 0)
                                            {{-- Has errors --}}
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-800 text-red-200">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                                {{ $errorShops }} błędów
                                            </span>
                                        @else
                                            {{-- Pending sync --}}
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-800 text-blue-200">
                                                <svg class="w-3 h-3 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                                Oczekuje sync
                                            </span>
                                        @endif

                                        {{-- Additional info on hover --}}
                                        @if($totalShops > 0)
                                            <div class="text-xs text-muted mt-1">
                                                {{ round($healthPercentage, 0) }}% sync health
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Updated At --}}
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-muted">
                                        <div>{{ $product->updated_at->format('d.m.Y') }}</div>
                                        <div class="text-xs">{{ $product->updated_at->format('H:i') }}</div>
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-1">
                                            {{-- Quick Preview Modal Button --}}
                                            <button wire:click="showProductPreview({{ $product->id }})"
                                                    class="text-muted hover:text-blue-500 transition-colors duration-300"
                                                    title="Szybki podgląd">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </button>

                                            {{-- Edit Product --}}
                                            <a href="{{ route('products.edit', $product) }}"
                                               class="text-muted hover:text-orange-500 transition-colors duration-300"
                                               title="Edytuj produkt">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>

                                            {{-- Duplicate Product --}}
                                            <button wire:click="duplicateProduct({{ $product->id }})"
                                                    class="text-muted hover:text-green-500 transition-colors duration-300"
                                                    title="Duplikuj produkt">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                            </button>

                                            {{-- FAZA 1.5: Multi-Store Actions --}}
                                            {{-- Sync/Refresh Product --}}
                                            <button wire:click="syncProduct({{ $product->id }})"
                                                    class="text-muted hover:text-purple-500 transition-colors duration-300"
                                                    title="Synchronizuj ze sklepami">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                            </button>

                                            {{-- Publish to Shops --}}
                                            <button wire:click="publishToShops({{ $product->id }})"
                                                    class="text-muted hover:text-cyan-500 transition-colors duration-300"
                                                    title="Wyślij na sklepy">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                                </svg>
                                            </button>

                                            {{-- Delete Product --}}
                                            <button wire:click="confirmDelete({{ $product->id }})"
                                                    class="text-muted hover:text-red-500 transition-colors duration-300"
                                                    title="Usuń produkt">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-muted mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                                            </svg>
                                            <p class="text-muted text-sm">
                                                @if($hasFilters)
                                                    Nie znaleziono produktów pasujących do filtrów
                                                @else
                                                    Brak produktów w systemie
                                                @endif
                                            </p>
                                            @if(!$hasFilters)
                                                <a href="{{ route('admin.products.create') }}"
                                                   class="mt-3 btn-primary inline-flex items-center px-4 py-2 text-white text-sm font-medium rounded-lg transition-all duration-300">
                                                    Dodaj pierwszy produkt
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pagination --}}
            <div class="mt-6 flex items-center justify-between">
                <div class="flex items-center space-x-2 text-sm text-muted">
                    <span>Wyświetl:</span>
                    <select wire:model.live="perPage" class="form-input rounded text-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="200">200</option>
                    </select>
                    <span>na stronę</span>
                </div>

                <div>
                    {{ $products->links() }}
                </div>
            </div>

        @else
            {{-- Grid View --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @forelse($products as $product)
                    <div class="card glass-effect rounded-xl shadow-soft border border-primary overflow-hidden card-hover transition-all duration-300">
                        {{-- Product Image Placeholder --}}
                        <div class="h-48 bg-card flex items-center justify-center">
                            <svg class="w-12 h-12 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>

                        {{-- Product Info --}}
                        <div class="p-4">
                            <div class="flex items-start justify-between mb-2">
                                <h3 class="text-sm font-medium text-primary line-clamp-2">
                                    <a href="{{ route('products.edit', $product) }}" class="hover:text-orange-500 transition-colors duration-300">
                                        {{ $product->name }}
                                    </a>
                                </h3>
                                <input type="checkbox"
                                       wire:key="grid-select-{{ $product->id }}"
                                       value="{{ $product->id }}"
                                       wire:model.live="selectedProducts"
                                       class="rounded border-primary text-orange-500 shadow-sm focus:ring-orange-500 focus:ring-opacity-50 bg-input">
                            </div>

                            <p class="text-xs text-muted mb-2">SKU: {{ $product->sku }}</p>

                            <div class="flex items-center justify-between mb-3">
                                @if($product->productType)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    @switch($product->productType->slug)
                                        @case('pojazd') bg-green-800 text-green-200 @break
                                        @case('czesc-zamienna') bg-blue-800 text-blue-200 @break
                                        @case('odziez') bg-yellow-800 text-yellow-200 @break
                                        @default bg-gray-800 text-gray-200
                                    @endswitch">
                                    {{ $product->productType->name }}
                                </span>
                                @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-800 text-gray-200">
                                    Brak typu
                                </span>
                                @endif

                                <button wire:click="toggleStatus({{ $product->id }})"
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium transition-colors
                                            {{ $product->is_active
                                                ? 'bg-green-800 text-green-200 hover:bg-green-700'
                                                : 'bg-red-800 text-red-200 hover:bg-red-700' }}">
                                    <span class="w-2 h-2 rounded-full mr-1 {{ $product->is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                    {{ $product->is_active ? 'Aktywny' : 'Nieaktywny' }}
                                </button>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-muted">
                                    {{ $product->updated_at->format('d.m.Y') }}
                                </span>

                                <div class="flex items-center space-x-1">
                                    <a href="{{ route('products.edit', $product) }}"
                                       class="p-1 text-muted hover:text-orange-500 transition-colors duration-300"
                                       title="Edytuj szczegóły">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>

                                    <a href="{{ route('products.edit', $product) }}"
                                       class="p-1 text-muted hover:text-orange-500 transition-colors duration-300"
                                       title="Edytuj">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>

                                    <button wire:click="duplicateProduct({{ $product->id }})"
                                            class="p-1 text-muted hover:text-green-500 transition-colors duration-300"
                                            title="Duplikuj">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full">
                        <div class="flex flex-col items-center py-12">
                            <svg class="w-12 h-12 text-muted mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                            </svg>
                            <p class="text-muted text-sm">
                                @if($hasFilters)
                                    Nie znaleziono produktów pasujących do filtrów
                                @else
                                    Brak produktów w systemie
                                @endif
                            </p>
                            @if(!$hasFilters)
                                <a href="{{ route('admin.products.create') }}"
                                   class="mt-3 btn-primary inline-flex items-center px-4 py-2 text-white text-sm font-medium rounded-lg transition-all duration-300">
                                    Dodaj pierwszy produkt
                                </a>
                            @endif
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- Grid Pagination --}}
            @if($products->hasPages())
                <div class="mt-6 flex items-center justify-between">
                    <div class="flex items-center space-x-2 text-sm text-muted">
                        <span>Wyświetl:</span>
                        <select wire:model.live="perPage" class="form-input rounded text-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                        </select>
                        <span>na stronę</span>
                    </div>

                    <div>
                        {{ $products->links() }}
                    </div>
                </div>
            @endif
        @endif
    </div>

    {{-- QUICK PREVIEW MODAL --}}
    @if($showPreviewModal && $selectedProduct)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-4 text-center sm:p-0">
                {{-- Background overlay --}}
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" wire:click="closePreviewModal"></div>

                {{-- Modal content --}}
                <div class="inline-block align-middle bg-card rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    {{-- Header --}}
                    <div class="bg-card px-6 py-4 border-b border-primary">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <h3 class="text-lg font-medium text-primary">
                                    Podgląd produktu: {{ $selectedProduct->sku }}
                                </h3>
                                @if($selectedProduct->is_active)
                                    <span class="ml-3 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-800 text-green-200">
                                        Aktywny
                                    </span>
                                @else
                                    <span class="ml-3 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-800 text-red-200">
                                        Nieaktywny
                                    </span>
                                @endif
                            </div>
                            <button wire:click="closePreviewModal"
                                    class="text-muted hover:text-primary transition-colors duration-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="bg-card px-6 py-4">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {{-- Product Info --}}
                            <div>
                                <h4 class="text-sm font-medium text-muted uppercase tracking-wider mb-3">Informacje podstawowe</h4>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300">Nazwa</label>
                                        <p class="text-sm text-primary">{{ $selectedProduct->name }}</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-300">SKU</label>
                                            <p class="text-sm text-primary font-mono">{{ $selectedProduct->sku }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-300">Kod dostawcy</label>
                                            <p class="text-sm text-primary">{{ $selectedProduct->supplier_code ?? '-' }}</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-300">Producent</label>
                                            <p class="text-sm text-primary">{{ $selectedProduct->manufacturer ?? '-' }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-300">Typ produktu</label>
                                            <p class="text-sm text-primary">{{ $selectedProduct->productType->name ?? '-' }}</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Descriptions --}}
                                @if($selectedProduct->short_description)
                                    <div class="mt-6">
                                        <label class="block text-sm font-medium text-gray-300 mb-2">Krótki opis</label>
                                        <div class="text-sm text-muted bg-card-hover rounded-lg p-3">
                                            {{ Str::limit($selectedProduct->short_description, 200) }}
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Multi-Store Sync Status --}}
                            <div>
                                <h4 class="text-sm font-medium text-muted uppercase tracking-wider mb-3">Status synchronizacji</h4>
                                @php
                                    $syncSummary = $selectedProduct->getMultiStoreSyncSummary();
                                    $conflicts = $selectedProduct->getShopsWithConflicts();
                                @endphp

                                <div class="space-y-4">
                                    {{-- Overall Sync Health --}}
                                    <div class="bg-card-hover rounded-lg p-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-medium text-gray-300">Ogólny stan synchronizacji</span>
                                            <span class="text-lg font-bold text-primary">{{ $syncSummary['sync_health_percentage'] }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-700 rounded-full h-2">
                                            <div class="h-2 rounded-full {{ $syncSummary['sync_health_percentage'] >= 90 ? 'bg-green-500' : ($syncSummary['sync_health_percentage'] >= 70 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                                 style="width: {{ $syncSummary['sync_health_percentage'] }}%"></div>
                                        </div>
                                    </div>

                                    {{-- Sync Stats --}}
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="bg-card-hover rounded-lg p-3 text-center">
                                            <div class="text-lg font-bold text-green-400">{{ $syncSummary['synced_shops'] }}</div>
                                            <div class="text-xs text-muted">Zsynchronizowane</div>
                                        </div>
                                        <div class="bg-card-hover rounded-lg p-3 text-center">
                                            <div class="text-lg font-bold text-blue-400">{{ $syncSummary['published_shops'] }}</div>
                                            <div class="text-xs text-muted">Opublikowane</div>
                                        </div>
                                        <div class="bg-card-hover rounded-lg p-3 text-center">
                                            <div class="text-lg font-bold text-orange-400">{{ $syncSummary['conflict_shops'] }}</div>
                                            <div class="text-xs text-muted">Konflikty</div>
                                        </div>
                                        <div class="bg-card-hover rounded-lg p-3 text-center">
                                            <div class="text-lg font-bold text-red-400">{{ $syncSummary['error_shops'] }}</div>
                                            <div class="text-xs text-muted">Błędy</div>
                                        </div>
                                    </div>

                                    {{-- Conflicts Details --}}
                                    @if($conflicts->count() > 0)
                                        <div class="bg-orange-900/20 border border-orange-800 rounded-lg p-3">
                                            <h5 class="text-sm font-medium text-orange-300 mb-2">Konflikty wymagające uwagi:</h5>
                                            <div class="space-y-2">
                                                @foreach($conflicts->take(3) as $conflict)
                                                    <div class="text-xs text-orange-200">
                                                        • {{ $conflict['shop_name'] }} - {{ $conflict['time_since_conflict'] }}
                                                    </div>
                                                @endforeach
                                                @if($conflicts->count() > 3)
                                                    <div class="text-xs text-orange-400">
                                                        ... i {{ $conflicts->count() - 3 }} więcej
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Actions Footer --}}
                    <div class="bg-card px-6 py-4 border-t border-primary">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2 text-xs text-muted">
                                <span>Utworzono: {{ $selectedProduct->created_at->format('d.m.Y H:i') }}</span>
                                <span>•</span>
                                <span>Aktualizacja: {{ $selectedProduct->updated_at->format('d.m.Y H:i') }}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button wire:click="syncProduct({{ $selectedProduct->id }})"
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-purple-600 hover:bg-purple-700 transition-colors duration-300">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Synchronizuj
                                </button>
                                <a href="{{ route('products.edit', $selectedProduct) }}"
                                   class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-orange-600 hover:bg-orange-700 transition-colors duration-300">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edytuj
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Loading Overlay --}}
    <div wire:loading.delay class="fixed inset-0 bg-black bg-opacity-25 flex items-center justify-center z-50">
        <div class="card glass-effect rounded-lg p-6 flex items-center space-x-3">
            <svg class="animate-spin h-5 w-5 text-orange-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-primary">Ładowanie...</span>
        </div>
    </div>

    {{-- Toast notifications --}}
    <div x-data="{
    show: false,
    message: '',
    type: 'success',
    init() {
        this.$wire.on('success', (data) => {
            this.showToast(data.message, 'success');
        });
        this.$wire.on('error', (data) => {
            this.showToast(data.message, 'error');
        });
    },
    showToast(message, type) {
        this.message = message;
        this.type = type;
        this.show = true;
        setTimeout(() => { this.show = false; }, 5000);
    }
}"
class="fixed top-4 right-4 z-50">
    <div x-show="show"
         x-transition:enter="transform ease-out duration-300 transition"
         x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
         x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="max-w-sm w-full card glass-effect shadow-lg rounded-lg pointer-events-auto ring-1 ring-orange-500 ring-opacity-20 overflow-hidden">
        <div class="p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <template x-if="type === 'success'">
                        <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                    <template x-if="type === 'error'">
                        <svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                </div>
                <div class="ml-3 w-0 flex-1 pt-0.5">
                    <p class="text-sm font-medium text-primary" x-text="message"></p>
                </div>
                <div class="ml-4 flex-shrink-0 flex">
                    <button @click="show = false"
                            class="card rounded-md inline-flex text-muted hover:text-primary focus:outline-none transition-colors duration-300">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- QUICK SEND TO SHOPS MODAL --}}
@if($showQuickSendModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" wire:click="closeQuickSendModal"></div>

        {{-- Modal Content --}}
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="inline-block bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                {{-- Header --}}
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        Wyślij produkty na sklepy
                    </h3>
                    <button wire:click="closeQuickSendModal" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Content --}}
                <div class="mb-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        Wybierz sklepy, na które chcesz wysłać {{ count($selectedProducts) }} {{ count($selectedProducts) == 1 ? 'produkt' : (count($selectedProducts) < 5 ? 'produkty' : 'produktów') }}:
                    </p>

                    @if(count($this->availableShops) > 0)
                        <div class="space-y-2 max-h-64 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg p-3">
                            @foreach($this->availableShops as $shop)
                                <label class="flex items-center p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded cursor-pointer">
                                    <input type="checkbox"
                                           wire:model="selectedShopsForBulk"
                                           value="{{ $shop->id }}"
                                           class="mr-3 h-4 w-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                    <div class="flex-1">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $shop->name }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $shop->url }}
                                        </div>
                                    </div>
                                    <div class="ml-2">
                                        @if($shop->connection_status === 'connected')
                                            <span class="inline-block w-2 h-2 bg-green-500 rounded-full"></span>
                                        @else
                                            <span class="inline-block w-2 h-2 bg-red-500 rounded-full"></span>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                            Brak dostępnych sklepów
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="mt-6 flex justify-between items-center">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ count($selectedShopsForBulk) }} {{ count($selectedShopsForBulk) == 1 ? 'sklep wybrany' : (count($selectedShopsForBulk) < 5 ? 'sklepy wybrane' : 'sklepów wybranych') }}
                    </span>
                    <div class="flex space-x-3">
                        <button wire:click="closeQuickSendModal"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            Anuluj
                        </button>
                        <button wire:click="bulkSendToShops"
                                class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors"
                                @if(empty($selectedShopsForBulk)) disabled @endif>
                            Wyślij produkty
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- IMPORT FROM PRESTASHOP MODAL --}}
@if($showImportModal)
<div class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden">

        {{-- Facebook-style Stagger Animation for Categories --}}
        <style>
            /* Stagger fade-in animation for newly loaded categories */
            [x-show][x-transition] {
                animation: fadeInStagger 0.3s ease-out forwards;
            }

            @keyframes fadeInStagger {
                from {
                    opacity: 0;
                    transform: translateY(-4px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            /* Progressive delay for sequential children (Facebook-style) */
            [x-show][x-transition]:nth-child(1) { animation-delay: 0ms; }
            [x-show][x-transition]:nth-child(2) { animation-delay: 50ms; }
            [x-show][x-transition]:nth-child(3) { animation-delay: 100ms; }
            [x-show][x-transition]:nth-child(4) { animation-delay: 150ms; }
            [x-show][x-transition]:nth-child(5) { animation-delay: 200ms; }
            [x-show][x-transition]:nth-child(6) { animation-delay: 250ms; }
            [x-show][x-transition]:nth-child(7) { animation-delay: 300ms; }
            [x-show][x-transition]:nth-child(8) { animation-delay: 350ms; }
            [x-show][x-transition]:nth-child(9) { animation-delay: 400ms; }
            [x-show][x-transition]:nth-child(10) { animation-delay: 450ms; }
        </style>

        {{-- Modal Header --}}
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                📥 Import produktów z PrestaShop
            </h3>
            <button wire:click="closeImportModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Modal Body --}}
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">

            {{-- Step 1: Shop Selection --}}
            @if(!$importShopId)
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        1️⃣ Wybierz sklep PrestaShop
                    </label>
                    {{-- CRITICAL FIX: Use computed property $this->availableShops instead of inline query --}}
                    <select wire:model.live="importShopId"
                            class="form-select w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        <option value="">-- Wybierz sklep --</option>
                        @foreach($this->availableShops as $shop)
                            <option value="{{ $shop->id }}">
                                {{ $shop->name }}
                                @if($shop->version)
                                    (PrestaShop {{ $shop->version }})
                                @endif
                            </option>
                        @endforeach
                    </select>

                    {{-- CRITICAL FIX: Visual confirmation after shop selection --}}
                    @if($importShopId)
                        <div class="mt-2 p-2 bg-green-50 dark:bg-green-900 dark:bg-opacity-20 rounded text-sm text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800">
                            ✅ Wybrany sklep: <strong>{{ $this->availableShops->find($importShopId)->name ?? 'N/A' }}</strong>
                        </div>
                    @endif
                </div>
            @else
                {{-- Shop Selected - Show mode tabs --}}
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Sklep:</span>
                            <strong class="text-gray-900 dark:text-white ml-2">
                                {{ $this->availableShops->find($importShopId)->name ?? 'N/A' }}
                            </strong>
                        </div>
                        <button wire:click="resetShopSelection" class="text-sm text-orange-500 hover:underline">
                            Zmień sklep
                        </button>
                    </div>

                    {{-- Mode Tabs --}}
                    <div class="flex space-x-2 mb-4 border-b border-gray-200 dark:border-gray-700">
                        <button wire:click="$set('importMode', 'all')"
                                class="px-4 py-2 border-b-2 {{ $importMode === 'all' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-600 dark:text-gray-400' }}">
                            📦 Wszystkie
                        </button>
                        <button wire:click="$set('importMode', 'category')"
                                class="px-4 py-2 border-b-2 {{ $importMode === 'category' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-600 dark:text-gray-400' }}">
                            📁 Kategoria
                        </button>
                        <button wire:click="$set('importMode', 'individual')"
                                class="px-4 py-2 border-b-2 {{ $importMode === 'individual' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-600 dark:text-gray-400' }}">
                            ✅ Wybrane produkty
                        </button>
                    </div>

                    {{-- MODE: All Products --}}
                    @if($importMode === 'all')
                        <div class="p-6 bg-yellow-50 dark:bg-yellow-900 dark:bg-opacity-20 rounded-lg">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">
                                ⚠️ Import wszystkich produktów
                            </h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                Zaimportujesz WSZYSTKIE produkty ze sklepu PrestaShop.
                                Operacja może zająć kilka minut w zależności od liczby produktów.
                            </p>
                            <button wire:click="importAllProducts"
                                    class="btn-primary inline-flex items-center px-4 py-2 text-white text-sm font-medium rounded-lg">
                                🚀 Rozpocznij import wszystkich produktów
                            </button>
                        </div>
                    @endif

                    {{-- MODE: Category --}}
                    @if($importMode === 'category')
                        <div>
                            @if(empty($prestashopCategories))
                                <div class="text-center py-8">
                                    {{-- Loading spinner - shows during API call --}}
                                    <div wire:loading wire:target="setImportShop,updatedImportShopId,loadPrestaShopCategories">
                                        <svg class="animate-spin h-8 w-8 mx-auto text-orange-500" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <p class="mt-2 text-gray-600 dark:text-gray-400">Ładowanie kategorii z PrestaShop...</p>
                                    </div>

                                    {{-- Empty state - shows when not loading and no categories --}}
                                    <div wire:loading.remove wire:target="setImportShop,updatedImportShopId,loadPrestaShopCategories">
                                        <p class="text-gray-500 dark:text-gray-400 text-sm">
                                            ⏳ Kategorie zostaną załadowane automatycznie po wybrze sklepu
                                        </p>
                                    </div>
                                </div>
                            @else
                                <div class="mb-4">
                                    <label class="flex items-center text-sm text-gray-700 dark:text-gray-300">
                                        <input type="checkbox" wire:model.live="importIncludeSubcategories"
                                               class="form-checkbox mr-2 text-orange-500">
                                        Uwzględnij podkategorie
                                    </label>
                                </div>

                                {{-- ALPINE.JS OPTIMIZED: Client-side expand/collapse with skeleton loaders --}}
                                <div class="border border-gray-300 dark:border-gray-600 rounded-lg max-h-96 overflow-y-auto p-4"
                                     x-data="{
                                         expanded: $wire.entangle('expandedCategories'),
                                         loading: null,
                                         skeletonCount: 3,
                                         toggleExpand(categoryId) {
                                             const idx = this.expanded.indexOf(categoryId);
                                             if (idx !== -1) {
                                                 // Collapse - INSTANT (no server call)
                                                 this.expanded.splice(idx, 1);
                                             } else {
                                                 // Expand - show skeleton loaders, then fetch
                                                 this.loading = categoryId;
                                                 this.expanded.push(categoryId); // Show container immediately

                                                 $wire.fetchCategoryChildren(categoryId).then(() => {
                                                     // CRITICAL FIX: Wait for Livewire DOM update before hiding skeleton
                                                     // Livewire re-render (~235KB template) + DOM injection takes time
                                                     // 100ms was too fast - skeleton disappeared before children appeared
                                                     // Now using Livewire.hook('morph.updated') or longer timeout
                                                     this.$nextTick(() => {
                                                         setTimeout(() => this.loading = null, 300); // Wait for DOM update
                                                     });
                                                 }).catch(() => {
                                                     this.loading = null;
                                                     this.expanded.splice(this.expanded.indexOf(categoryId), 1); // Collapse on error
                                                 });
                                             }
                                         },
                                         isExpanded(categoryId) {
                                             return this.expanded.includes(categoryId);
                                         },
                                         isLoading(categoryId) {
                                             return this.loading === categoryId;
                                         }
                                     }">
                                    @foreach($prestashopCategories as $index => $category)
                                        @php
                                            $categoryId = (int)($category['id'] ?? 0);
                                            $categoryName = $category['name'] ?? 'Unknown';
                                            $levelDepth = (int)($category['level_depth'] ?? 0);
                                            $parentId = (int)($category['id_parent'] ?? 0);

                                            // OPTIMISTIC HEURISTIC: Show expand button if category might have children
                                            // We use nb_products_recursive > 0 as indicator
                                            // ROLLBACK: Back to lazy loading (root categories only), children loaded on-demand
                                            $hasChildren = ($category['nb_products_recursive'] ?? 0) > 0;

                                            // SPECIAL CASE: Baza (ID=1) and Wszystko (ID=2) should NOT have collapse arrows
                                            // They are always expanded by default (see auto-expand in backend loadPrestaShopCategories)
                                            // User should not be able to collapse root categories
                                            $isRootCategory = in_array($categoryId, [1, 2]);
                                            $showExpandButton = $hasChildren && !$isRootCategory && $levelDepth < 5;

                                            // Calculate indent based on level (1.5rem per level)
                                            $indent = $levelDepth * 1.5;

                                            // CRITICAL FIX: Level 0-2 (Baza, Wszystko, Main categories) always visible
                                            // Level 3+ (subcategories) visible only when parent is expanded
                                            $alwaysVisible = $levelDepth <= 2;

                                            // DEBUG: Log every category render to see level_depth values
                                            if ($levelDepth > 2) {
                                                \Log::debug("Rendering category with indent", [
                                                    'id' => $categoryId,
                                                    'name' => $categoryName,
                                                    'level_depth' => $levelDepth,
                                                    'indent_rem' => $indent,
                                                    'parent_id' => $parentId,
                                                ]);
                                            }
                                        @endphp

                                        {{-- DEBUG: data attributes for browser inspection --}}
                                        <div wire:key="cat-{{ $categoryId }}"
                                             class="flex items-center mb-1"
                                             style="padding-left: {{ $indent }}rem;"
                                             data-cat-id="{{ $categoryId }}"
                                             data-parent="{{ $parentId }}"
                                             data-level="{{ $levelDepth }}"
                                             data-name="{{ $categoryName }}"
                                             data-indent="{{ $indent }}"
                                             @if($alwaysVisible)
                                                 {{-- Level 0-2: Always visible --}}
                                             @else
                                                 {{-- Level 3+: Visible only when parent expanded --}}
                                                 x-show="expanded.includes({{ $parentId }})"
                                                 x-transition:enter="transition ease-out duration-150"
                                                 x-transition:enter-start="opacity-0 transform -translate-y-1"
                                                 x-transition:enter-end="opacity-100 transform translate-y-0"
                                             @endif>
                                            {{-- Expand/Collapse Button --}}
                                            @if($showExpandButton)
                                                <button @click="toggleExpand({{ $category['id'] }})"
                                                        :disabled="isLoading({{ $category['id'] }})"
                                                        class="flex-shrink-0 w-6 h-6 flex items-center justify-center text-gray-500 hover:text-orange-500 mr-1 relative">
                                                    {{-- Expand/Collapse Icon --}}
                                                    <span x-show="!isLoading({{ $category['id'] }})">
                                                        <span x-show="isExpanded({{ $category['id'] }})" class="text-sm">▼</span>
                                                        <span x-show="!isExpanded({{ $category['id'] }})" class="text-sm">▶</span>
                                                    </span>
                                                    {{-- Loading Spinner --}}
                                                    <svg x-show="isLoading({{ $category['id'] }})"
                                                         x-cloak
                                                         class="animate-spin h-4 w-4 text-orange-500"
                                                         xmlns="http://www.w3.org/2000/svg"
                                                         fill="none"
                                                         viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                </button>
                                            @else
                                                <span class="w-6 h-6 flex-shrink-0 mr-1"></span>
                                            @endif

                                            {{-- Category Button --}}
                                            <button wire:click="selectImportCategory({{ $category['id'] }})"
                                                    class="flex-1 text-left py-2 px-4 rounded hover:bg-gray-100 dark:hover:bg-gray-700 {{ $importCategoryId === $category['id'] ? 'bg-orange-500 bg-opacity-20 border border-orange-500' : '' }}">
                                                <span class="font-medium">{{ $category['name'] }}</span>
                                                <span class="text-xs text-gray-500 ml-2">
                                                    ({{ $category['nb_products_recursive'] ?? 0 }} prod.)
                                                </span>
                                            </button>
                                        </div>

                                        {{-- Skeleton Loaders - Facebook Style --}}
                                        @if($showExpandButton)
                                            @php
                                                // Child skeleton indent (1 level deeper)
                                                $skeletonIndent = ($levelDepth + 1) * 1.5;
                                            @endphp
                                            <div x-show="isLoading({{ $category['id'] }})"
                                                 x-cloak
                                                 style="padding-left: {{ $skeletonIndent }}rem;"
                                                 x-transition:enter="transition ease-out duration-150"
                                                 x-transition:enter-start="opacity-0"
                                                 x-transition:enter-end="opacity-100"
                                                 x-transition:leave="transition ease-in duration-100"
                                                 x-transition:leave-start="opacity-100"
                                                 x-transition:leave-end="opacity-0">
                                                {{-- Skeleton Item 1 (wider) --}}
                                                <div class="flex items-center mb-2 animate-pulse">
                                                    <span class="w-6 h-6 flex-shrink-0 mr-1"></span>
                                                    <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-3/4"></div>
                                                </div>
                                                {{-- Skeleton Item 2 (medium) --}}
                                                <div class="flex items-center mb-2 animate-pulse" style="animation-delay: 75ms;">
                                                    <span class="w-6 h-6 flex-shrink-0 mr-1"></span>
                                                    <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-2/3"></div>
                                                </div>
                                                {{-- Skeleton Item 3 (narrower) --}}
                                                <div class="flex items-center mb-2 animate-pulse" style="animation-delay: 150ms;">
                                                    <span class="w-6 h-6 flex-shrink-0 mr-1"></span>
                                                    <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-1/2"></div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>

                                @if($importCategoryId)
                                    <button wire:click="importFromCategory"
                                            class="btn-primary inline-flex items-center px-4 py-2 text-white text-sm font-medium rounded-lg mt-4">
                                        🚀 Importuj z wybranej kategorii
                                    </button>
                                @endif
                            @endif
                        </div>
                    @endif

                    {{-- MODE: Individual Products --}}
                    @if($importMode === 'individual')
                        <div>
                            {{-- CRITICAL: Search Input --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    🔍 Wyszukaj produkt (po nazwie lub SKU)
                                </label>
                                <div class="relative">
                                    <input type="text"
                                           wire:model.live.debounce.500ms="importSearch"
                                           placeholder="Wpisz min. 3 znaki nazwy lub SKU..."
                                           class="form-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white pr-10">
                                    <div wire:loading wire:target="loadPrestaShopProducts" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                        <svg class="animate-spin h-5 w-5 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    💡 Wpisz minimum 3 znaki aby rozpocząć wyszukiwanie
                                </p>

                                @if(!empty($importSearch))
                                    <p class="text-sm text-orange-500 mt-1">
                                        🔎 Wyszukiwanie: "{{ $importSearch }}"
                                    </p>
                                @endif
                            </div>

                            @if(empty($prestashopProducts))
                                <div class="text-center py-8">
                                    {{-- Loading spinner - shows during API call or search --}}
                                    <div wire:loading wire:target="setImportShop,updatedImportShopId,loadPrestaShopProducts,updatedImportSearch">
                                        <svg class="animate-spin h-8 w-8 mx-auto text-orange-500" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                                            @if(!empty($importSearch))
                                                Wyszukiwanie produktów...
                                            @else
                                                Ładowanie produktów z PrestaShop...
                                            @endif
                                        </p>
                                    </div>

                                    {{-- Empty state - shows when not loading and no products --}}
                                    <div wire:loading.remove wire:target="setImportShop,updatedImportShopId,loadPrestaShopProducts,updatedImportSearch">
                                        @if(!empty($importSearch))
                                            <p class="text-gray-500 dark:text-gray-400 text-sm">
                                                🔍 Brak produktów pasujących do wyszukiwania: <strong>"{{ $importSearch }}"</strong>
                                            </p>
                                        @else
                                            <p class="text-gray-500 dark:text-gray-400 text-sm">
                                                ⏳ Produkty zostaną załadowane automatycznie po wyborze sklepu<br>
                                                lub użyj wyszukiwarki powyżej
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="mb-2 text-sm text-gray-600 dark:text-gray-400">
                                    Znaleziono: <strong>{{ count($prestashopProducts) }}</strong> produktów
                                    @if(count($selectedProductsToImport) > 0)
                                        | Wybrano: <strong class="text-orange-500">{{ count($selectedProductsToImport) }}</strong>
                                    @endif
                                </div>

                                <div class="border border-gray-300 dark:border-gray-600 rounded-lg max-h-96 overflow-y-auto">
                                    @foreach($prestashopProducts as $product)
                                        @php
                                            $isSelected = in_array($product['id'], $selectedProductsToImport);
                                            $existsInPPM = App\Models\Product::where('sku', $product['reference'] ?? '')->exists();
                                        @endphp

                                        <label class="flex items-center p-3 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-200 dark:border-gray-700 cursor-pointer {{ $isSelected ? 'bg-orange-500 bg-opacity-10' : '' }}">
                                            <input type="checkbox"
                                                   wire:click="toggleProductSelection({{ $product['id'] }})"
                                                   {{ $isSelected ? 'checked' : '' }}
                                                   class="form-checkbox mr-3 text-orange-500">

                                            <div class="flex-1">
                                                <div class="font-medium text-gray-900 dark:text-white">
                                                    {{ $product['name'] ?? 'Brak nazwy' }}
                                                </div>
                                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                                    SKU: <strong>{{ $product['reference'] ?? 'N/A' }}</strong>
                                                    | ID: {{ $product['id'] }}
                                                </div>
                                            </div>

                                            @if($existsInPPM)
                                                <span class="ml-2 px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs rounded">
                                                    ✅ Istnieje w PPM
                                                </span>
                                            @endif
                                        </label>
                                    @endforeach
                                </div>

                                @if(count($selectedProductsToImport) > 0)
                                    <button wire:click="importSelectedProducts"
                                            class="btn-primary inline-flex items-center px-4 py-2 text-white text-sm font-medium rounded-lg mt-4">
                                        🚀 Importuj wybrane ({{ count($selectedProductsToImport) }})
                                    </button>
                                @endif
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Modal Footer --}}
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end">
            <button wire:click="closeImportModal" class="btn-secondary px-4 py-2 text-sm font-medium rounded-lg">
                Anuluj
            </button>
        </div>
    </div>
</div>
@endif

{{-- MPP TRADE Custom Styles --}}
<style>
/* MPP TRADE Color Variables */
:root {
    --mpp-primary: #e0ac7e;
    --mpp-primary-dark: #d1975a;
    --bg-card: rgba(31, 41, 55, 0.8);
    --bg-card-hover: rgba(55, 65, 81, 0.8);
    --bg-input: #374151;
    --border-primary: rgba(75, 85, 99, 0.2);
    --text-primary: #ffffff;
    --text-secondary: #f3f4f6;
    --text-muted: #d1d5db;
}

/* Dark theme main gradient */
.bg-main-gradient {
    background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
}

/* Glass morphism effect */
.glass-effect {
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    background: var(--bg-card);
}

/* Card styles */
.card {
    background: var(--bg-card);
    border: 1px solid var(--border-primary);
}

.card-hover:hover {
    background: var(--bg-card-hover);
    transform: translateY(-2px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
}

/* Button styles */
.btn-primary {
    background: linear-gradient(45deg, var(--mpp-primary), var(--mpp-primary-dark));
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: linear-gradient(45deg, var(--mpp-primary-dark), #c08449);
    transform: scale(1.05);
    box-shadow: 0 10px 25px rgba(224, 172, 126, 0.3);
}

.btn-secondary {
    background: var(--bg-card);
    border: 1px solid var(--border-primary);
    color: var(--text-secondary);
}

/* Form input styles */
.form-input {
    background: var(--bg-input) !important;
    border: 1px solid var(--border-primary) !important;
    color: var(--text-primary) !important;
}

.form-input:focus {
    border-color: var(--mpp-primary) !important;
    box-shadow: 0 0 0 3px rgba(224, 172, 126, 0.1) !important;
    outline: none !important;
}

/* Text color utilities */
.text-primary { color: var(--text-primary) !important; }
.text-secondary { color: var(--text-secondary) !important; }
.text-muted { color: var(--text-muted) !important; }

/* Background utilities */
.bg-card { background: var(--bg-card) !important; }
.bg-card-hover { background: var(--bg-card-hover) !important; }
.bg-input { background: var(--bg-input) !important; }

/* Border utilities */
.border-primary { border-color: var(--border-primary) !important; }

/* Shadow utilities */
.shadow-soft {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1),
                0 4px 6px -4px rgba(0, 0, 0, 0.1);
}

/* Orange focus ring for accessibility */
.focus\:ring-orange-500:focus {
    --tw-ring-color: var(--mpp-primary) !important;
}

.focus\:ring-orange-500:focus {
    box-shadow: 0 0 0 3px rgba(224, 172, 126, 0.5) !important;
}

/* Status colors with orange theme */
.text-orange-500 { color: var(--mpp-primary) !important; }
.text-orange-400 { color: #f4b986 !important; }
.bg-orange-500 { background-color: var(--mpp-primary) !important; }
.bg-orange-50 { background-color: #fef7f0 !important; }
.dark\:bg-orange-900\/20 { background-color: rgba(124, 45, 18, 0.2) !important; }
.border-orange-200 { border-color: #fed7aa !important; }
.dark\:border-orange-800 { border-color: #9a3412 !important; }
.text-orange-900 { color: #7c2d12 !important; }
.dark\:text-orange-200 { color: #fed7aa !important; }

/* Smooth transitions */
* {
    transition-duration: 0.3s !important;
    transition-timing-function: ease !important;
}

/* Custom checkbox styling */
input[type="checkbox"] {
    accent-color: var(--mpp-primary) !important;
}
</style>