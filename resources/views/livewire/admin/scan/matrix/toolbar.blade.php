{{-- Toolbar z filtrami --}}
<div class="matrix-toolbar flex flex-wrap items-center gap-4 mb-4 p-4 bg-gray-800/50 border border-gray-700 rounded-lg">

    {{-- Search --}}
    <div class="relative flex-1 min-w-48">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <input type="text"
               wire:model.live.debounce.300ms="search"
               placeholder="Szukaj SKU, nazwy..."
               class="w-full pl-10 pr-4 py-2 bg-gray-700 border border-gray-600 text-white placeholder-gray-400 rounded-lg text-sm focus:outline-none focus:border-[#e0ac7e] focus:ring-1 focus:ring-[#e0ac7e]">
    </div>

    {{-- Status filter --}}
    <select wire:model.live="statusFilter"
            class="px-3 py-2 bg-gray-700 border border-gray-600 text-white rounded-lg text-sm focus:outline-none focus:border-[#e0ac7e] focus:ring-1 focus:ring-[#e0ac7e]">
        <option value="all">Wszystkie</option>
        <option value="linked">Powiazane</option>
        <option value="not_linked">Niepowiazane (w zrodle)</option>
        <option value="not_found">Nie znaleziono</option>
        <option value="unknown">Nieznany (brak skanu)</option>
        <option value="ignored">Ignorowane</option>
        <option value="conflicts">Konflikty</option>
        <option value="brand_blocked">Zablokowane marki</option>
        <option value="pending_sync">Oczekuje sync</option>
    </select>

    {{-- Brand filter --}}
    <select wire:model.live="brandFilter"
            class="px-3 py-2 bg-gray-700 border border-gray-600 text-white rounded-lg text-sm focus:outline-none focus:border-[#e0ac7e] focus:ring-1 focus:ring-[#e0ac7e]">
        <option value="">Wszystkie marki</option>
        @foreach($availableBrands as $brand)
            <option value="{{ $brand['id'] }}">{{ $brand['name'] }}</option>
        @endforeach
    </select>

    {{-- Source visibility filter (kolumny integracji) --}}
    <div x-data="{ sourceFilterOpen: false }" class="relative">
        <button @click="sourceFilterOpen = !sourceFilterOpen"
                class="flex items-center gap-2 px-3 py-2 bg-gray-700 border border-gray-600 text-white rounded-lg text-sm hover:border-gray-500 transition-colors">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z"/>
            </svg>
            Integracje
            @if(!empty($visibleSources))
                <span class="matrix-source-filter-badge">{{ count($visibleSources) }}</span>
            @endif
        </button>
        <div x-show="sourceFilterOpen" @click.away="sourceFilterOpen = false" x-transition
             class="absolute top-full left-0 mt-1 w-56 bg-gray-800 border border-gray-600 rounded-lg shadow-xl z-20 py-2">
            @foreach($sourceColumns as $col)
                <label class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-700 cursor-pointer transition-colors">
                    <input type="checkbox"
                           wire:click="toggleSourceVisibility('{{ $col['key'] }}')"
                           @checked(empty($visibleSources) || in_array($col['key'], $visibleSources))
                           class="rounded border-gray-600 bg-gray-700 text-[#e0ac7e] focus:ring-[#e0ac7e] focus:ring-offset-gray-900">
                    <span class="text-sm text-gray-300 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full" style="background: {{ $col['color'] }};"></span>
                        {{ $col['label'] }}
                    </span>
                </label>
            @endforeach
            @if(!empty($visibleSources))
                <div class="border-t border-gray-700 mt-1 pt-1 px-3">
                    <button wire:click="showAllSources" class="text-xs text-[#e0ac7e] hover:underline">
                        Pokaz wszystkie
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Grouped view toggle --}}
    <label class="flex items-center space-x-2 text-sm text-gray-300 cursor-pointer">
        <input type="checkbox"
               wire:model.live="groupedView"
               class="rounded border-gray-600 bg-gray-700 text-[#e0ac7e] focus:ring-[#e0ac7e] focus:ring-offset-gray-900">
        <span>Grupuj wg marki</span>
    </label>

    {{-- Spacer --}}
    <div class="flex-1"></div>

    {{-- Reset filters button --}}
    @if($search !== '' || $statusFilter !== 'all' || $brandFilter !== null || $groupedView || !empty($visibleSources))
        <button wire:click="resetFilters"
                class="px-3 py-2 text-sm text-gray-400 hover:text-white border border-gray-600 hover:border-gray-500 rounded-lg transition-colors">
            Resetuj
        </button>
    @endif

    {{-- Scan button z natychmiastowym feedbackiem --}}
    <div x-data="{ preparing: false }">
        {{-- Idle state --}}
        <button wire:click="startChunkedScan"
                x-show="$wire.scanPhase === 'idle' && !preparing"
                @click="preparing = true"
                class="flex items-center gap-2 px-4 py-2 bg-[#e0ac7e] text-gray-900 text-sm font-medium rounded-lg hover:bg-[#d1975a] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Skanuj konflikty
        </button>

        {{-- Preparing state (natychmiastowy feedback po kliknieciu) --}}
        <div x-show="preparing && $wire.scanPhase === 'idle'"
             x-transition
             class="flex items-center gap-2 px-4 py-2 bg-gray-700 text-[#e0ac7e] text-sm rounded-lg border border-[#e0ac7e]/30">
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
            </svg>
            Przygotowanie do skanowania...
        </div>

        {{-- Scanning/prefetching state --}}
        <div x-show="$wire.scanPhase !== 'idle'"
             x-init="$watch('$wire.scanPhase', v => { if (v !== 'idle') preparing = false })"
             class="flex items-center gap-2 px-4 py-2 bg-gray-700 text-gray-400 text-sm rounded-lg border border-gray-600">
            <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            {{ $scanPhase === 'prefetching' ? 'Pobieranie danych zrodel...' : 'Skanowanie...' }}
        </div>
    </div>

</div>
