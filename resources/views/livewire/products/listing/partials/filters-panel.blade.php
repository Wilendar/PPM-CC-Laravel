<div class="mt-4 p-4 card glass-effect rounded-lg border border-primary">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
        {{-- Category Filter - Tree Dropdown --}}
        @include('livewire.products.listing.partials.category-tree-dropdown')

        {{-- Status Filter - SECURITY: only with products.status --}}
        @if($this->userCan('status_read'))
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Status</label>
            <select wire:model.live="statusFilter"
                    class="form-input w-full rounded-lg shadow-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
                <option value="all">Wszystkie statusy</option>
                <option value="active">Aktywne</option>
                <option value="inactive">Nieaktywne</option>
            </select>
        </div>
        @endif

        {{-- Stock Filter (basic) - SECURITY: only with stock.read --}}
        @if($this->userCan('stock_read'))
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
        @endif

        {{-- Product Type Filter --}}
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Typ produktu</label>
            <select wire:model.live="productTypeFilter"
                    class="form-input w-full rounded-lg shadow-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
                <option value="all">Wszystkie typy</option>
                @foreach($this->availableProductTypes as $type)
                    <option value="{{ $type->slug }}">{{ $type->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- ETAP_05 - Advanced Filters (1.1.1.2.4-1.1.1.2.8) --}}

        {{-- 1.1.1.2.4: Price Group Filter - SECURITY: only with prices.read --}}
        @if($this->userCan('prices_read'))
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Grupa cenowa</label>
            <select wire:model.live="priceGroupFilter"
                    class="form-input w-full rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
                <option value="">Wszystkie grupy</option>
                @foreach($this->availablePriceGroups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Price Range --}}
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Zakres cen (PLN)</label>
            <div class="flex items-center gap-2">
                <input wire:model.live.debounce.500ms="priceMin"
                       type="number" min="0" step="0.01" placeholder="Od"
                       class="form-input w-full rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
                <span class="text-gray-400 flex-shrink-0">-</span>
                <input wire:model.live.debounce.500ms="priceMax"
                       type="number" min="0" step="0.01" placeholder="Do"
                       class="form-input w-full rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
            </div>
        </div>
        @endif

        {{-- Warehouse Filter - SECURITY: only with stock.read --}}
        @if($this->userCan('stock_read'))
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Magazyn</label>
            <select wire:model.live="stockWarehouseFilter"
                    class="form-input w-full rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
                <option value="">Wszystkie magazyny</option>
                @foreach($this->availableWarehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Stock Range --}}
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Zakres stanów (szt.)</label>
            <div class="flex items-center gap-2">
                <input wire:model.live.debounce.500ms="stockMin"
                       type="number" min="0" step="1" placeholder="Min"
                       class="form-input w-full rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
                <span class="text-gray-400 flex-shrink-0">-</span>
                <input wire:model.live.debounce.500ms="stockMax"
                       type="number" min="0" step="1" placeholder="Max"
                       class="form-input w-full rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
            </div>
        </div>
        @endif

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

        {{-- 1.1.1.2.7: Integration Status Filter - SECURITY: only with products.compliance --}}
        @if($this->userCan('compliance_read'))
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Status integracji</label>
            <select wire:model.live="integrationFilter"
                    class="form-input w-full rounded-lg shadow-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
                <option value="all">Wszystkie statusy</option>
                @foreach($this->syncStatusOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @endif

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

    {{-- ETAP: Product Status Filters (2026-02-04) --}}
    <div class="mt-4">
        @include('livewire.products.listing.partials.status-filters')
    </div>

    {{-- Filter Actions --}}
    @if($hasFilters)
        <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <p class="text-sm text-gray-400">
                {{ $products->total() }} produktów znalezionych
            </p>
            <button wire:click="clearFilters"
                    class="text-sm text-orange-500 hover:text-orange-400 transition-colors duration-300 text-left sm:text-right">
                Wyczyść filtry
            </button>
        </div>
    @endif
</div>
