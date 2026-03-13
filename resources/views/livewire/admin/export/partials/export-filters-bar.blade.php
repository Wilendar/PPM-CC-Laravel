{{-- Export Filters Toolbar - Basic + Manufacturer + Shops --}}
<div class="export-filter__bar">
    {{-- Status aktywnosci --}}
    <div>
        <span class="export-filter__bar-label">Status</span>
        <select wire:model.live="filterIsActive" class="form-input-enterprise w-full text-sm">
            <option value="all">Wszystkie</option>
            <option value="true">Aktywne</option>
            <option value="false">Nieaktywne</option>
        </select>
    </div>

    {{-- Typ produktu --}}
    <div>
        <span class="export-filter__bar-label">Typ produktu</span>
        <select wire:model.live="filterProductTypeId" class="form-input-enterprise w-full text-sm">
            <option value="">Wszystkie typy</option>
            @foreach($availableProductTypes as $pt)
                <option value="{{ $pt['id'] }}">{{ $pt['name'] }}</option>
            @endforeach
        </select>
    </div>

    {{-- Stan magazynowy --}}
    <div>
        <span class="export-filter__bar-label">Stan magazynowy</span>
        <select wire:model.live="filterStockStatus" class="form-input-enterprise w-full text-sm">
            <option value="">Wszystkie</option>
            <option value="in_stock">Na stanie</option>
            <option value="low_stock">Niski stan</option>
            <option value="out_of_stock">Brak na stanie</option>
        </select>
    </div>

    {{-- Producent (dropdown z checkboxami) --}}
    @if(!empty($availableManufacturersList))
        <div x-data="{ open: false, searchMfr: '' }" class="relative">
            <span class="export-filter__bar-label">Producent</span>
            <button @click="open = !open" type="button"
                    class="form-input-enterprise w-full text-sm text-left flex justify-between items-center">
                <span class="truncate">
                    @if(!empty($filterManufacturerIds))
                        Wybrano: {{ count($filterManufacturerIds) }}
                    @else
                        Wszyscy
                    @endif
                </span>
                <svg class="h-4 w-4 flex-shrink-0 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-transition @click.outside="open = false"
                 class="absolute left-0 top-full z-20 mt-1 w-64 rounded-lg border border-gray-700 bg-gray-800 shadow-xl">
                <div class="p-2 border-b border-gray-700">
                    <input x-model="searchMfr" type="text" placeholder="Szukaj producenta..."
                           class="form-input-enterprise w-full text-xs" @click.stop>
                </div>
                <div class="max-h-48 overflow-y-auto p-1">
                    @foreach($availableManufacturersList as $mfr)
                        <label wire:key="filter-mfr-bar-{{ $mfr['id'] }}"
                               x-show="!searchMfr || '{{ strtolower(addslashes($mfr['name'])) }}'.includes(searchMfr.toLowerCase())"
                               class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-xs text-gray-300 transition-colors hover:bg-gray-700/50">
                            <input type="checkbox" value="{{ $mfr['id'] }}" wire:model.live="filterManufacturerIds"
                                   class="checkbox-enterprise" @click.stop>
                            {{ $mfr['name'] }}
                        </label>
                    @endforeach
                </div>
                @if(!empty($filterManufacturerIds))
                    <div class="border-t border-gray-700 p-2">
                        <button wire:click="$set('filterManufacturerIds', [])" type="button" @click="open = false"
                                class="text-xs text-gray-400 hover:text-white transition-colors">
                            Wyczysc wybor
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Sklepy --}}
    @if(!empty($availableShops))
        <div>
            <span class="export-filter__bar-label">
                Sklepy
                @if(!empty($filterShopIds))
                    <span class="text-xs" style="color: var(--mpp-primary)">({{ count($filterShopIds) }})</span>
                @endif
            </span>
            <div class="flex flex-wrap gap-1.5 mt-1">
                @foreach($availableShops as $shop)
                    <label wire:key="filter-shop-bar-{{ $shop['id'] }}"
                           class="flex cursor-pointer items-center gap-1.5 rounded bg-gray-700/50 px-2 py-1 text-xs text-gray-300 transition-colors hover:bg-gray-700">
                        <input type="checkbox"
                               wire:click="toggleShop({{ $shop['id'] }})"
                               @checked(in_array((string) $shop['id'], $filterShopIds))
                               class="checkbox-enterprise">
                        {{ $shop['name'] }}
                    </label>
                @endforeach
            </div>
        </div>
    @endif
</div>
