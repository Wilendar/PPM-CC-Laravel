{{-- Bulk Actions Toolbar --}}
<div class="bg-gray-800 rounded-lg border border-gray-700 p-3 mb-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        {{-- Left side: Search and Filters --}}
        <div class="flex items-center gap-3 flex-wrap">
            {{-- Search --}}
            <div class="relative">
                <input wire:model.live.debounce.300ms="search"
                       type="text"
                       class="pl-8 pr-3 py-1.5 text-xs bg-gray-700 border border-gray-600 rounded-md text-white
                              placeholder-gray-400 focus:outline-none focus:border-[#e0ac7e] w-48"
                       placeholder="Szukaj SKU, nazwa...">
                <svg class="w-4 h-4 absolute left-2 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            {{-- Match Status Filter --}}
            <select wire:model.live="matchStatusFilter"
                    class="px-2 py-1.5 text-xs bg-gray-700 border border-gray-600 rounded-md text-white
                           focus:outline-none focus:border-[#e0ac7e]">
                <option value="all">Wszystkie statusy</option>
                <option value="matched">Dopasowane</option>
                <option value="unmatched">Niedopasowane</option>
                <option value="conflict">Konflikty</option>
                <option value="multiple">Wielokrotne</option>
            </select>

            {{-- Resolution Filter --}}
            <select wire:model.live="resolutionFilter"
                    class="px-2 py-1.5 text-xs bg-gray-700 border border-gray-600 rounded-md text-white
                           focus:outline-none focus:border-[#e0ac7e]">
                <option value="all">Wszystkie rozwiazania</option>
                <option value="pending">Oczekujace</option>
                <option value="linked">Polaczone</option>
                <option value="created">Utworzone</option>
                <option value="ignored">Zignorowane</option>
            </select>

            {{-- Reset Filters --}}
            @if($search || $matchStatusFilter !== 'all' || $resolutionFilter !== 'all')
                <button wire:click="resetFilters"
                        class="px-2 py-1.5 text-xs text-gray-400 hover:text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            @endif
        </div>

        {{-- Right side: Bulk Actions --}}
        <div class="flex items-center gap-2">
            @if(count($selectedResults) > 0)
                <span class="text-xs text-gray-400">
                    Zaznaczono: {{ count($selectedResults) }}
                </span>

                {{-- Bulk Link (for links tab) --}}
                @if($activeTab === 'links')
                    <button wire:click="bulkLink"
                            wire:loading.attr="disabled"
                            class="px-3 py-1.5 text-xs font-medium text-white bg-green-600 hover:bg-green-500
                                   rounded-md transition-colors duration-150 disabled:opacity-50">
                        <span wire:loading.remove wire:target="bulkLink">Polacz wybrane</span>
                        <span wire:loading wire:target="bulkLink">Laczenie...</span>
                    </button>
                @endif

                {{-- Bulk Create (for missing_ppm tab) --}}
                @if($activeTab === 'missing_ppm')
                    <button wire:click="bulkCreate"
                            wire:loading.attr="disabled"
                            class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-500
                                   rounded-md transition-colors duration-150 disabled:opacity-50">
                        <span wire:loading.remove wire:target="bulkCreate">Importuj jako draft</span>
                        <span wire:loading wire:target="bulkCreate">Importowanie...</span>
                    </button>
                @endif

                {{-- Bulk Ignore --}}
                <button wire:click="bulkIgnore"
                        wire:loading.attr="disabled"
                        class="px-3 py-1.5 text-xs font-medium text-gray-300 bg-gray-700 hover:bg-gray-600
                               border border-gray-600 rounded-md transition-colors duration-150 disabled:opacity-50">
                    <span wire:loading.remove wire:target="bulkIgnore">Ignoruj wybrane</span>
                    <span wire:loading wire:target="bulkIgnore">...</span>
                </button>
            @endif
        </div>
    </div>
</div>
