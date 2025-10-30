<div class="compatibility-management-panel">
    {{-- Header --}}
    <div class="panel-header mb-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">
                    <i class="fas fa-link text-blue-600 mr-3"></i>
                    Dopasowania Części Zamiennych
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    Zarządzanie globalnymi dopasowaniami części do pojazdów - centralna baza dla wszystkich sklepów
                </p>
            </div>

            {{-- Bulk Edit button (FAZA 2.2) --}}
            @if(count($selectedPartIds) > 0)
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        Zaznaczono: <strong>{{ count($selectedPartIds) }}</strong>
                    </span>
                    <button
                        class="btn-bulk-edit px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center"
                        wire:click="openBulkEdit"
                    >
                        <i class="fas fa-edit mr-2"></i>
                        Edycja masowa ({{ count($selectedPartIds) }})
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Filters Section --}}
    <div class="filters-section grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        {{-- 1. Search input (searchPart) --}}
        <div class="filter-item">
            <label for="searchPart" class="block text-sm font-medium text-gray-300 mb-2">
                <i class="fas fa-search mr-1"></i>
                Szukaj Części
            </label>
            <input
                type="text"
                id="searchPart"
                wire:model.live.debounce.300ms="searchPart"
                placeholder="SKU lub nazwa części..."
                class="filter-input w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
        </div>

        {{-- 2. Shop dropdown (filterShopId) --}}
        <div class="filter-item">
            <label for="filterShopId" class="block text-sm font-medium text-gray-300 mb-2">
                <i class="fas fa-store mr-1"></i>
                Sklep
            </label>
            <select
                id="filterShopId"
                wire:model.live="filterShopId"
                class="filter-select w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
                <option value="">Wszystkie sklepy</option>
                @foreach($this->shops as $shop)
                    <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- 3. Brand dropdown (filterBrand) --}}
        <div class="filter-item">
            <label for="filterBrand" class="block text-sm font-medium text-gray-300 mb-2">
                <i class="fas fa-car mr-1"></i>
                Marka Pojazdu
            </label>
            <select
                id="filterBrand"
                wire:model.live="filterBrand"
                class="filter-select w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
                <option value="">Wszystkie marki</option>
                @foreach($this->brands as $brand)
                    <option value="{{ $brand }}">{{ $brand }}</option>
                @endforeach
            </select>
        </div>

        {{-- 4. Status dropdown (filterStatus) --}}
        <div class="filter-item">
            <label for="filterStatus" class="block text-sm font-medium text-gray-300 mb-2">
                <i class="fas fa-filter mr-1"></i>
                Status Dopasowań
            </label>
            <select
                id="filterStatus"
                wire:model.live="filterStatus"
                class="filter-select w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
                <option value="all">Wszystkie statusy</option>
                <option value="full">✅ Pełny (Oryginał + Zamiennik)</option>
                <option value="partial">🟡 Częściowy (tylko jeden typ)</option>
                <option value="none">❌ Brak (bez dopasowań)</option>
            </select>
        </div>
    </div>

    {{-- Reset Filters Button (conditional: show if any filter active) --}}
    @if($searchPart || $filterShopId || $filterBrand || $filterStatus !== 'all')
        <div class="mb-6 flex justify-end">
            <button wire:click="resetFilters" class="btn-reset px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-all">
                <i class="fas fa-undo mr-2"></i>
                Resetuj Filtry
            </button>
        </div>
    @endif

    {{-- Parts Table --}}
    <div class="enterprise-table bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 dark:bg-gray-900">
                    <tr>
                        {{-- Checkbox (bulk select) --}}
                        <th class="px-4 py-3 text-left w-12">
                            <input
                                type="checkbox"
                                class="rounded border-gray-600 text-blue-600 focus:ring-blue-500"
                                wire:model.live="selectAll"
                            />
                        </th>

                        {{-- SKU (sortable) --}}
                        <th
                            wire:click="sortBy('sku')"
                            class="px-4 py-3 text-left cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors"
                        >
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-gray-300">SKU</span>
                                @if($sortField === 'sku')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-blue-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>

                        {{-- Nazwa --}}
                        <th class="px-4 py-3 text-left">
                            <span class="font-semibold text-gray-300">Nazwa Części</span>
                        </th>

                        {{-- Oryginał count (sortable) --}}
                        <th
                            wire:click="sortBy('original_count')"
                            class="px-4 py-3 text-center cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors"
                        >
                            <div class="flex items-center justify-center gap-2">
                                <span class="font-semibold text-gray-300">Oryginał</span>
                                @if($sortField === 'original_count')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-blue-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>

                        {{-- Zamiennik count (sortable) --}}
                        <th
                            wire:click="sortBy('replacement_count')"
                            class="px-4 py-3 text-center cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors"
                        >
                            <div class="flex items-center justify-center gap-2">
                                <span class="font-semibold text-gray-300">Zamiennik</span>
                                @if($sortField === 'replacement_count')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-blue-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>

                        {{-- Model count (sortable) --}}
                        <th
                            wire:click="sortBy('model_count')"
                            class="px-4 py-3 text-center cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors"
                        >
                            <div class="flex items-center justify-center gap-2">
                                <span class="font-semibold text-gray-300">Model (auto)</span>
                                @if($sortField === 'model_count')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-blue-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>

                        {{-- Status --}}
                        <th class="px-4 py-3 text-center">
                            <span class="font-semibold text-gray-300">Status</span>
                        </th>

                        {{-- Akcje --}}
                        <th class="px-4 py-3 text-center w-24">
                            <span class="font-semibold text-gray-300">Akcje</span>
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-700">
                    @forelse($this->parts as $part)
                        {{-- Main row --}}
                        <tr
                            wire:key="part-{{ $part->id }}"
                            class="hover:bg-gray-700 transition-colors"
                        >
                            {{-- Checkbox --}}
                            <td class="px-4 py-3">
                                <input
                                    type="checkbox"
                                    wire:model.live="selectedPartIds"
                                    value="{{ $part->id }}"
                                    class="rounded border-gray-600 text-blue-600 focus:ring-blue-500"
                                />
                            </td>

                            {{-- SKU --}}
                            <td class="px-4 py-3">
                                <span class="font-mono font-semibold text-white">
                                    {{ $part->sku }}
                                </span>
                            </td>

                            {{-- Nazwa --}}
                            <td class="px-4 py-3">
                                <span class="text-gray-300">
                                    {{ $part->name }}
                                </span>
                            </td>

                            {{-- Oryginał count --}}
                            <td class="px-4 py-3 text-center">
                                <span class="count-badge count-original">
                                    {{ $part->original_count }}
                                </span>
                            </td>

                            {{-- Zamiennik count --}}
                            <td class="px-4 py-3 text-center">
                                <span class="count-badge count-replacement">
                                    {{ $part->replacement_count }}
                                </span>
                            </td>

                            {{-- Model count (sum) --}}
                            <td class="px-4 py-3 text-center">
                                <span class="count-badge count-model">
                                    {{ $part->original_count + $part->replacement_count }}
                                </span>
                            </td>

                            {{-- Status badge --}}
                            <td class="px-4 py-3 text-center">
                                <span class="{{ $this->getStatusBadgeClass($part->original_count, $part->replacement_count) }}">
                                    {{ $this->getStatusBadgeLabel($part->original_count, $part->replacement_count) }}
                                </span>
                            </td>

                            {{-- Expand button --}}
                            <td class="px-4 py-3 text-center">
                                <button
                                    wire:click="toggleExpand({{ $part->id }})"
                                    class="btn-expand text-2xl text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-all"
                                >
                                    {{ in_array($part->id, $expandedPartIds) ? '▲' : '▼' }}
                                </button>
                            </td>
                        </tr>

                        {{-- Expandable row (vehicles list) --}}
                        @if(in_array($part->id, $expandedPartIds))
                            <tr wire:key="expand-{{ $part->id }}" class="expandable-row bg-gray-50 dark:bg-gray-900">
                                <td colspan="8" class="px-4 py-6">
                                    <div class="compatibility-details grid grid-cols-1 lg:grid-cols-3 gap-6">
                                        {{-- Oryginał section --}}
                                        <div class="compatibility-section original-section">
                                            <h4 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                                                <div class="w-4 h-4 rounded-full bg-green-500"></div>
                                                Oryginał
                                                <span class="text-sm text-gray-600 dark:text-gray-400">({{ $part->original_count }})</span>
                                            </h4>

                                            <div class="vehicle-badges flex flex-wrap gap-2 mb-4">
                                                @foreach($part->compatibilities->where('compatibilityAttribute.code', 'original') as $compat)
                                                    <span
                                                        wire:key="compat-original-{{ $compat->id }}"
                                                        class="vehicle-badge badge-original"
                                                    >
                                                        {{ $compat->vehicleModel->brand }} {{ $compat->vehicleModel->model }}
                                                        <button
                                                            wire:click="removeCompatibility({{ $compat->id }})"
                                                            class="btn-remove ml-2"
                                                        >
                                                            ×
                                                        </button>
                                                    </span>
                                                @endforeach

                                                @if($part->original_count === 0)
                                                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                                                        Brak dopasowanych pojazdów
                                                    </p>
                                                @endif
                                            </div>

                                            <button class="btn-add-vehicle" wire:click="addVehicle({{ $part->id }}, 'original')">
                                                <i class="fas fa-plus mr-2"></i>
                                                Dodaj Pojazd
                                            </button>
                                        </div>

                                        {{-- Zamiennik section --}}
                                        <div class="compatibility-section replacement-section">
                                            <h4 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                                                <div class="w-4 h-4 rounded-full bg-orange-500"></div>
                                                Zamiennik
                                                <span class="text-sm text-gray-600 dark:text-gray-400">({{ $part->replacement_count }})</span>
                                            </h4>

                                            <div class="vehicle-badges flex flex-wrap gap-2 mb-4">
                                                @foreach($part->compatibilities->where('compatibilityAttribute.code', 'replacement') as $compat)
                                                    <span
                                                        wire:key="compat-replacement-{{ $compat->id }}"
                                                        class="vehicle-badge badge-replacement"
                                                    >
                                                        {{ $compat->vehicleModel->brand }} {{ $compat->vehicleModel->model }}
                                                        <button
                                                            wire:click="removeCompatibility({{ $compat->id }})"
                                                            class="btn-remove ml-2"
                                                        >
                                                            ×
                                                        </button>
                                                    </span>
                                                @endforeach

                                                @if($part->replacement_count === 0)
                                                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                                                        Brak dopasowanych pojazdów
                                                    </p>
                                                @endif
                                            </div>

                                            <button class="btn-add-vehicle" wire:click="addVehicle({{ $part->id }}, 'replacement')">
                                                <i class="fas fa-plus mr-2"></i>
                                                Dodaj Pojazd
                                            </button>
                                        </div>

                                        {{-- Model section (auto-generated, read-only) --}}
                                        <div class="compatibility-section model-section">
                                            <h4 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                                                <div class="w-4 h-4 rounded-full bg-blue-500"></div>
                                                Model (auto)
                                                <span class="text-sm text-gray-600 dark:text-gray-400">({{ $part->original_count + $part->replacement_count }})</span>
                                            </h4>

                                            <div class="info-box bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                                <p class="info-text text-sm text-blue-800 dark:text-blue-300 mb-2">
                                                    <i class="fas fa-info-circle mr-2"></i>
                                                    <strong>Auto-generated:</strong> Union(Oryginał, Zamiennik) without duplicates.
                                                </p>
                                                <p class="info-text text-sm text-blue-800 dark:text-blue-300">
                                                    Total: <strong>{{ $part->original_count + $part->replacement_count }}</strong> unikalnych pojazdów.
                                                </p>
                                            </div>

                                            <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                                                <p class="mb-2">
                                                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                                    Automatycznie synchronizowane
                                                </p>
                                                <p>
                                                    <i class="fas fa-shield-alt text-blue-500 mr-2"></i>
                                                    Tylko do odczytu
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-4">
                                    <i class="fas fa-search text-6xl text-gray-400"></i>
                                    <p class="text-gray-600 dark:text-gray-400 text-lg">
                                        Brak części spełniających kryteria wyszukiwania
                                    </p>
                                    @if($searchPart || $filterShopId || $filterBrand || $filterStatus !== 'all')
                                        <button
                                            wire:click="resetFilters"
                                            class="btn-reset px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all"
                                        >
                                            <i class="fas fa-undo mr-2"></i>
                                            Resetuj Filtry
                                        </button>
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
    <div class="pagination mt-6">
        {{ $this->parts->links() }}
    </div>

    {{-- Bulk Edit Modal (FAZA 2.2) --}}
    @livewire('admin.compatibility.bulk-edit-compatibility-modal')
</div>
