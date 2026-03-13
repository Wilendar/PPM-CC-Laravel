<div class="space-y-5">
    {{-- Header z licznikiem aktywnych filtrow --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-white">Filtry produktow</h2>
            <p class="mt-1 text-sm text-gray-400">
                Okresl, ktore produkty maja byc uwzglednione w eksporcie.
                @if($this->getActiveAdvancedFilterCount() > 0)
                    <span class="text-[#e0ac7e]">({{ $this->getActiveAdvancedFilterCount() }} zaawansowanych aktywnych)</span>
                @endif
            </p>
        </div>
        @if($this->getActiveAdvancedFilterCount() > 0)
            <button wire:click="resetAdvancedFilters" class="text-sm text-gray-400 transition-colors hover:text-white">
                Resetuj filtry zaawansowane
            </button>
        @endif
    </div>

    {{-- ============================================= --}}
    {{-- SEKCJA PODSTAWOWA (zawsze widoczna)           --}}
    {{-- ============================================= --}}

    {{-- Row 1: Status aktywnosci + Typ produktu + Stan magazynowy --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-300">Status aktywnosci</label>
            <select wire:model.live="filterIsActive"
                    class="w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-white focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
                <option value="all">Wszystkie</option>
                <option value="true">Tylko aktywne</option>
                <option value="false">Tylko nieaktywne</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-300">Typ produktu</label>
            <select wire:model.live="filterProductTypeId"
                    class="w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-white focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
                <option value="">Wszystkie typy</option>
                @foreach($availableProductTypes as $pt)
                    <option value="{{ $pt['id'] }}">{{ $pt['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-300">Stan magazynowy</label>
            <select wire:model.live="filterStockStatus"
                    class="w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-white focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
                <option value="">Wszystkie</option>
                <option value="in_stock">Na stanie</option>
                <option value="low_stock">Niski stan</option>
                <option value="out_of_stock">Brak na stanie</option>
            </select>
        </div>
    </div>

    {{-- Kategorie + Produkty (dwukolumnowy widok) --}}
    <div>
        <label class="mb-2 block text-sm font-medium text-gray-300">
            Kategorie i produkty
            @if(!empty($filterCategoryIds))
                <span class="ml-1 text-xs text-[#e0ac7e]">({{ count($filterCategoryIds) }} kat.)</span>
            @endif
            @if(!empty($excludedProductIds))
                <span class="ml-1 text-xs text-red-400">({{ count($excludedProductIds) }} wyklucz.)</span>
            @endif
        </label>

        <div class="export-category-browser">
            <div class="export-category-browser__columns">
                {{-- LEWA KOLUMNA: Drzewko kategorii --}}
                <div class="export-category-browser__column">
                    <div class="export-category-browser__column-header">
                        <h3>Kategorie</h3>
                        <span class="badge bg-gray-700 text-gray-300">{{ count($filterCategoryIds) }} wybranych</span>
                    </div>
                    <div class="export-category-browser__column-content">
                        <livewire:products.category-picker
                            wire:model="filterCategoryIds"
                            context="export-filter"
                            :showCreateButton="false"
                            :enableChildAutoSelect="true"
                            wire:key="export-category-picker" />
                    </div>
                </div>

                {{-- PRAWA KOLUMNA: Produkty z wybranych kategorii --}}
                <div class="export-category-browser__column">
                    <div class="export-category-browser__column-header">
                        <h3>
                            Produkty
                            @if($categoryProductsTotal > 0)
                                <span class="text-gray-500 normal-case">({{ $categoryProductsTotal }})</span>
                            @endif
                        </h3>
                        @if(!empty($categoryProducts))
                            <div class="export-category-browser__product-controls">
                                <button wire:click="excludeAllProducts" type="button">Wyklucz wszystkie</button>
                                <button wire:click="restoreAllProducts" type="button">Przywroc</button>
                            </div>
                        @endif
                    </div>
                    <div class="export-category-browser__column-content">
                        @if(empty($filterCategoryIds))
                            <div class="export-category-browser__empty">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                <p>Zaznacz kategorie, aby zobaczyc produkty</p>
                            </div>
                        @elseif(empty($categoryProducts))
                            <div class="export-category-browser__empty">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p>Brak produktow w wybranych kategoriach</p>
                            </div>
                        @else
                            @foreach($categoryProducts as $product)
                                <div wire:key="cat-product-{{ $product['id'] }}"
                                     class="export-category-browser__product-item {{ in_array($product['id'], $excludedProductIds) ? 'excluded' : '' }}">
                                    <label>
                                        <input type="checkbox"
                                               wire:click="toggleProductExclusion({{ $product['id'] }})"
                                               @checked(!in_array($product['id'], $excludedProductIds))
                                               class="rounded border-gray-500 bg-gray-600 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                        <span class="product-sku">{{ $product['sku'] }}</span>
                                        <span class="product-name">{{ $product['name'] }}</span>
                                    </label>
                                </div>
                            @endforeach

                            @if($hasMoreProducts)
                                <button wire:click="loadMoreProducts" type="button"
                                        class="export-category-browser__load-more">
                                    Zaladuj wiecej ({{ $categoryProductsTotal - count($categoryProducts) }} pozostalych)
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            {{-- Summary bar --}}
            @if(!empty($filterCategoryIds))
                <div class="export-category-browser__summary">
                    <span>Kategorie: {{ count($filterCategoryIds) }}</span>
                    <span>Produkty: {{ $categoryProductsTotal }}
                        @if(!empty($excludedProductIds))
                            ({{ count($excludedProductIds) }} wykluczonych)
                        @endif
                    </span>
                    <span>Do eksportu: {{ $categoryProductsTotal - count($excludedProductIds) }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Producent (multiselect z wyszukiwaniem) --}}
    @if(!empty($availableManufacturersList))
        <div x-data="{ searchMfr: '' }">
            <label class="mb-2 block text-sm font-medium text-gray-300">
                Producent
                @if(!empty($filterManufacturerIds))
                    <span class="ml-1 text-xs text-[#e0ac7e]">({{ count($filterManufacturerIds) }} wybranych)</span>
                @endif
            </label>
            <input x-model="searchMfr" type="text" placeholder="Szukaj producenta..."
                   class="mb-2 w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-sm text-white placeholder-gray-400 focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
            <div class="max-h-48 overflow-y-auto rounded-lg border border-gray-700 bg-gray-800/30 p-3">
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @foreach($availableManufacturersList as $mfr)
                        <label wire:key="filter-mfr-{{ $mfr['id'] }}"
                               x-show="!searchMfr || '{{ strtolower(addslashes($mfr['name'])) }}'.includes(searchMfr.toLowerCase())"
                               class="flex cursor-pointer items-center gap-2 rounded bg-gray-700/50 px-3 py-1.5 transition-colors hover:bg-gray-700">
                            <input type="checkbox" value="{{ $mfr['id'] }}" wire:model.live="filterManufacturerIds"
                                   class="rounded border-gray-500 bg-gray-600 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="text-xs text-gray-300">{{ $mfr['name'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Sklepy PrestaShop --}}
    @if(!empty($availableShops))
        <div>
            <label class="mb-2 block text-sm font-medium text-gray-300">
                Sklepy PrestaShop
                @if(!empty($filterShopIds))
                    <span class="ml-1 text-xs text-[#e0ac7e]">({{ count($filterShopIds) }} wybranych)</span>
                @endif
            </label>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                @foreach($availableShops as $shop)
                    <label wire:key="filter-shop-{{ $shop['id'] }}"
                           class="flex cursor-pointer items-center gap-2 rounded bg-gray-700/50 px-3 py-2 transition-colors hover:bg-gray-700">
                        <input type="checkbox"
                               wire:click="toggleShop({{ $shop['id'] }})"
                               {{ in_array((string) $shop['id'], $filterShopIds) ? 'checked' : '' }}
                               class="rounded border-gray-500 bg-gray-600 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                        <span class="text-sm text-gray-300">{{ $shop['name'] }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ============================================= --}}
    {{-- SEKCJA ZAAWANSOWANA (collapsible)             --}}
    {{-- ============================================= --}}

    <div class="border-t border-gray-700 pt-4">
        <button wire:click="$toggle('showAdvancedFilters')" type="button"
                class="flex w-full items-center justify-between text-sm font-medium text-gray-300 transition-colors hover:text-white">
            <span class="flex items-center gap-2">
                <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
                Filtry zaawansowane
                @if($this->getActiveAdvancedFilterCount() > 0)
                    <span class="rounded-full bg-[#e0ac7e] px-2 py-0.5 text-xs font-bold text-gray-900">
                        {{ $this->getActiveAdvancedFilterCount() }}
                    </span>
                @endif
            </span>
            <svg class="h-4 w-4 transition-transform {{ $showAdvancedFilters ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        @if($showAdvancedFilters)
        <div class="mt-4 space-y-4">

            {{-- Dostawca (multiselect z wyszukiwaniem) --}}
            @if(!empty($availableSuppliersList))
                <div x-data="{ searchSup: '' }">
                    <label class="mb-2 block text-sm font-medium text-gray-300">
                        Dostawca
                        @if(!empty($filterSupplierIds))
                            <span class="ml-1 text-xs text-[#e0ac7e]">({{ count($filterSupplierIds) }})</span>
                        @endif
                    </label>
                    <input x-model="searchSup" type="text" placeholder="Szukaj dostawcy..."
                           class="mb-2 w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-sm text-white placeholder-gray-400 focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
                    <div class="max-h-36 overflow-y-auto rounded-lg border border-gray-700 bg-gray-800/30 p-3">
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            @foreach($availableSuppliersList as $sup)
                                <label wire:key="filter-sup-{{ $sup['id'] }}"
                                       x-show="!searchSup || '{{ strtolower(addslashes($sup['name'])) }}'.includes(searchSup.toLowerCase())"
                                       class="flex cursor-pointer items-center gap-2 rounded bg-gray-700/50 px-3 py-1.5 transition-colors hover:bg-gray-700">
                                    <input type="checkbox" value="{{ $sup['id'] }}" wire:model.live="filterSupplierIds"
                                           class="rounded border-gray-500 bg-gray-600 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                    <span class="text-xs text-gray-300">{{ $sup['name'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Magazyny --}}
            @if(!empty($availableWarehousesList))
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-300">
                        Magazyny
                        @if(!empty($filterWarehouseIds))
                            <span class="ml-1 text-xs text-[#e0ac7e]">({{ count($filterWarehouseIds) }})</span>
                        @endif
                    </label>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach($availableWarehousesList as $wh)
                            <label wire:key="filter-wh-{{ $wh['id'] }}"
                                   class="flex cursor-pointer items-center gap-2 rounded bg-gray-700/50 px-3 py-2 transition-colors hover:bg-gray-700">
                                <input type="checkbox" value="{{ $wh['id'] }}" wire:model.live="filterWarehouseIds"
                                       class="rounded border-gray-500 bg-gray-600 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="text-sm text-gray-300">{{ $wh['name'] }}</span>
                                <span class="ml-auto text-xs text-gray-500">{{ $wh['code'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Grupa cenowa + Zakres cen --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-300">Grupa cenowa</label>
                    <select wire:model.live="filterPriceGroupId"
                            class="w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-white focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
                        <option value="">Dowolna grupa</option>
                        @foreach($availablePriceGroupsList as $pg)
                            <option value="{{ $pg['id'] }}">{{ $pg['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-300">Cena minimalna (netto)</label>
                    <input wire:model.live.debounce.500ms="filterPriceMin" type="number" min="0" step="0.01"
                           class="w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-white placeholder-gray-400 focus:border-[#e0ac7e] focus:ring-[#e0ac7e]"
                           placeholder="0.00">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-300">Cena maksymalna (netto)</label>
                    <input wire:model.live.debounce.500ms="filterPriceMax" type="number" min="0" step="0.01"
                           class="w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-white placeholder-gray-400 focus:border-[#e0ac7e] focus:ring-[#e0ac7e]"
                           placeholder="0.00">
                </div>
            </div>

            {{-- Integracje ERP --}}
            @if(!empty($availableErpConnections))
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-300">
                        Integracje ERP
                        @if(!empty($filterErpConnectionIds))
                            <span class="ml-1 text-xs text-[#e0ac7e]">({{ count($filterErpConnectionIds) }})</span>
                        @endif
                    </label>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach($availableErpConnections as $erp)
                            <label wire:key="filter-erp-{{ $erp['id'] }}"
                                   class="flex cursor-pointer items-center gap-2 rounded bg-gray-700/50 px-3 py-2 transition-colors hover:bg-gray-700">
                                <input type="checkbox" value="{{ $erp['id'] }}" wire:model.live="filterErpConnectionIds"
                                       class="rounded border-gray-500 bg-gray-600 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="text-sm text-gray-300">{{ $erp['name'] }}</span>
                                <span class="ml-auto text-xs text-gray-500">{{ $erp['erp_type'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Data od/do + typ daty --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-300">Data od</label>
                    <input wire:model.live="filterDateFrom" type="date"
                           class="w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-white focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-300">Data do</label>
                    <input wire:model.live="filterDateTo" type="date"
                           class="w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-white focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-300">Typ daty</label>
                    <select wire:model.live="filterDateType"
                            class="w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-white focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
                        <option value="created_at">Data utworzenia</option>
                        <option value="updated_at">Data aktualizacji</option>
                    </select>
                </div>
            </div>

            {{-- Media + Dopasowania pojazdow --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-300">Media</label>
                    <select wire:model.live="filterMediaStatus"
                            class="w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-white focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
                        <option value="">Wszystkie</option>
                        <option value="with_images">Ze zdjeciami</option>
                        <option value="without_images">Bez zdjec</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-300">Dopasowania pojazdow</label>
                    <select wire:model.live="filterHasCompatibility"
                            class="w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-white focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
                        <option value="">Wszystkie</option>
                        <option value="with">Z dopasowaniami</option>
                        <option value="without">Bez dopasowan</option>
                    </select>
                </div>
            </div>

        </div>
        @endif
    </div>
</div>
