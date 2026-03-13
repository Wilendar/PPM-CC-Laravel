{{-- Export Advanced Filters (collapsible) --}}
<div class="border-t border-gray-700 pt-4" x-data="{ open: @entangle('showAdvancedFilters') }">
    <button @click="open = !open" type="button"
            class="export-filter__advanced-toggle">
        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
        </svg>
        <span class="flex-1 text-left">Filtry zaawansowane</span>
        @if($this->getActiveAdvancedFilterCount() > 0)
            <span class="badge-enterprise--warning rounded-full px-2 py-0.5 text-xs font-bold">
                {{ $this->getActiveAdvancedFilterCount() }}
            </span>
        @endif
        <svg class="h-4 w-4 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="open" x-collapse class="mt-4">
        <div class="export-filter__advanced-content space-y-4">

            {{-- Dostawca --}}
            @if(!empty($availableSuppliersList))
                <div x-data="{ searchSup: '' }">
                    <label class="mb-2 block text-sm font-medium text-gray-300">
                        Dostawca
                        @if(!empty($filterSupplierIds))
                            <span class="ml-1 text-xs" style="color: var(--mpp-primary)">({{ count($filterSupplierIds) }})</span>
                        @endif
                    </label>
                    <input x-model="searchSup" type="text" placeholder="Szukaj dostawcy..."
                           class="form-input-enterprise mb-2 w-full text-sm">
                    <div class="max-h-36 overflow-y-auto rounded-lg border border-gray-700 bg-gray-800/30 p-3">
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            @foreach($availableSuppliersList as $sup)
                                <label wire:key="filter-sup-{{ $sup['id'] }}"
                                       x-show="!searchSup || '{{ strtolower(addslashes($sup['name'])) }}'.includes(searchSup.toLowerCase())"
                                       class="flex cursor-pointer items-center gap-2 rounded bg-gray-700/50 px-3 py-1.5 transition-colors hover:bg-gray-700">
                                    <input type="checkbox" value="{{ $sup['id'] }}" wire:model.live="filterSupplierIds"
                                           class="checkbox-enterprise">
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
                            <span class="ml-1 text-xs" style="color: var(--mpp-primary)">({{ count($filterWarehouseIds) }})</span>
                        @endif
                    </label>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach($availableWarehousesList as $wh)
                            <label wire:key="filter-wh-{{ $wh['id'] }}"
                                   class="flex cursor-pointer items-center gap-2 rounded bg-gray-700/50 px-3 py-2 transition-colors hover:bg-gray-700">
                                <input type="checkbox" value="{{ $wh['id'] }}" wire:model.live="filterWarehouseIds"
                                       class="checkbox-enterprise">
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
                            class="form-input-enterprise w-full">
                        <option value="">Dowolna grupa</option>
                        @foreach($availablePriceGroupsList as $pg)
                            <option value="{{ $pg['id'] }}">{{ $pg['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-300">Cena minimalna (netto)</label>
                    <input wire:model.live.debounce.500ms="filterPriceMin" type="number" min="0" step="0.01"
                           class="form-input-enterprise w-full"
                           placeholder="0.00">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-300">Cena maksymalna (netto)</label>
                    <input wire:model.live.debounce.500ms="filterPriceMax" type="number" min="0" step="0.01"
                           class="form-input-enterprise w-full"
                           placeholder="0.00">
                </div>
            </div>

            {{-- Integracje ERP --}}
            @if(!empty($availableErpConnections))
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-300">
                        Integracje ERP
                        @if(!empty($filterErpConnectionIds))
                            <span class="ml-1 text-xs" style="color: var(--mpp-primary)">({{ count($filterErpConnectionIds) }})</span>
                        @endif
                    </label>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach($availableErpConnections as $erp)
                            <label wire:key="filter-erp-{{ $erp['id'] }}"
                                   class="flex cursor-pointer items-center gap-2 rounded bg-gray-700/50 px-3 py-2 transition-colors hover:bg-gray-700">
                                <input type="checkbox" value="{{ $erp['id'] }}" wire:model.live="filterErpConnectionIds"
                                       class="checkbox-enterprise">
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
                           class="form-input-enterprise w-full">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-300">Data do</label>
                    <input wire:model.live="filterDateTo" type="date"
                           class="form-input-enterprise w-full">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-300">Typ daty</label>
                    <select wire:model.live="filterDateType"
                            class="form-input-enterprise w-full">
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
                            class="form-input-enterprise w-full">
                        <option value="">Wszystkie</option>
                        <option value="with_images">Ze zdjeciami</option>
                        <option value="without_images">Bez zdjec</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-300">Dopasowania pojazdow</label>
                    <select wire:model.live="filterHasCompatibility"
                            class="form-input-enterprise w-full">
                        <option value="">Wszystkie</option>
                        <option value="with">Z dopasowaniami</option>
                        <option value="without">Bez dopasowan</option>
                    </select>
                </div>
            </div>

            {{-- Reset button --}}
            @if($this->getActiveAdvancedFilterCount() > 0)
                <div class="flex justify-end pt-2">
                    <button wire:click="resetAdvancedFilters" type="button"
                            class="text-sm text-gray-400 transition-colors hover:text-white">
                        Resetuj filtry zaawansowane
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
