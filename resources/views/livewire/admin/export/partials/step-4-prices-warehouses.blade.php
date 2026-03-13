<div>
    <h2 class="mb-4 text-lg font-semibold text-white">Grupy cenowe i magazyny</h2>
    <p class="mb-5 text-sm text-gray-400">Wybierz, ktore grupy cenowe i magazyny uwzglednic w eksporcie.</p>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        {{-- Price Groups --}}
        <div class="rounded-lg border border-gray-700 bg-gray-800/30 p-4">
            <h3 class="mb-3 text-sm font-semibold text-white">
                Grupy cenowe
                @if(!empty($selectedPriceGroups))
                    <span class="ml-1 text-xs font-normal text-[#e0ac7e]">({{ count($selectedPriceGroups) }})</span>
                @endif
            </h3>
            @if(!empty($availablePriceGroups))
                <div class="space-y-2">
                    @foreach($availablePriceGroups as $pg)
                        <label wire:key="pg-{{ $pg['id'] }}"
                               class="flex cursor-pointer items-center gap-2 rounded bg-gray-700/50 px-3 py-2 transition-colors hover:bg-gray-700">
                            <input type="checkbox" value="{{ $pg['id'] }}" wire:model.live="selectedPriceGroups"
                                   class="rounded border-gray-500 bg-gray-600 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="text-sm text-gray-300">{{ $pg['name'] }}</span>
                            <span class="ml-auto text-xs text-gray-500">{{ $pg['code'] }}</span>
                        </label>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500">Brak aktywnych grup cenowych.</p>
            @endif
        </div>

        {{-- Warehouses --}}
        <div class="rounded-lg border border-gray-700 bg-gray-800/30 p-4">
            <h3 class="mb-3 text-sm font-semibold text-white">
                Magazyny
                @if(!empty($selectedWarehouses))
                    <span class="ml-1 text-xs font-normal text-[#e0ac7e]">({{ count($selectedWarehouses) }})</span>
                @endif
            </h3>
            @if(!empty($availableWarehouses))
                <div class="space-y-2">
                    @foreach($availableWarehouses as $wh)
                        <label wire:key="wh-{{ $wh['id'] }}"
                               class="flex cursor-pointer items-center gap-2 rounded bg-gray-700/50 px-3 py-2 transition-colors hover:bg-gray-700">
                            <input type="checkbox" value="{{ $wh['id'] }}" wire:model.live="selectedWarehouses"
                                   class="rounded border-gray-500 bg-gray-600 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="text-sm text-gray-300">{{ $wh['name'] }}</span>
                            <span class="ml-auto text-xs text-gray-500">{{ $wh['code'] }}</span>
                        </label>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500">Brak aktywnych magazynow.</p>
            @endif
        </div>
    </div>
</div>
