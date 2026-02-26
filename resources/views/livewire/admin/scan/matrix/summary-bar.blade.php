{{-- Summary Stats Cards --}}
<div class="grid grid-cols-2 gap-3 mb-4 sm:grid-cols-3 lg:grid-cols-6">

    {{-- Powiazane --}}
    <div class="matrix-summary-card matrix-stat-linked">
        <div class="flex items-center space-x-2">
            <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center bg-green-900/40">
                <svg class="w-4 h-4 matrix-stat-value-linked" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div>
                <p class="text-xl font-bold matrix-stat-value-linked">{{ $summaryStats['linked'] ?? 0 }}</p>
                <p class="text-xs text-gray-400">Powiazane</p>
            </div>
        </div>
    </div>

    {{-- Niepowiazane (istnieja w zrodle) --}}
    <div class="matrix-summary-card matrix-stat-not-linked">
        <div class="flex items-center space-x-2">
            <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center bg-blue-900/40">
                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
            </div>
            <div>
                <p class="text-xl font-bold text-blue-400">{{ $summaryStats['not_linked'] ?? 0 }}</p>
                <p class="text-xs text-gray-400">Niepowiazane</p>
            </div>
        </div>
    </div>

    {{-- Nie znaleziono (nie istnieja w zrodle) --}}
    <div class="matrix-summary-card matrix-stat-not-found">
        <div class="flex items-center space-x-2">
            <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center bg-red-900/40">
                <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <div>
                <p class="text-xl font-bold text-red-400">{{ $summaryStats['not_found'] ?? 0 }}</p>
                <p class="text-xs text-gray-400">Nie znaleziono</p>
            </div>
        </div>
    </div>

    {{-- Nieznany (brak skanu) --}}
    <div class="matrix-summary-card matrix-stat-unknown">
        <div class="flex items-center space-x-2">
            <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center bg-gray-700/50">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xl font-bold text-gray-400">{{ $summaryStats['unknown'] ?? 0 }}</p>
                <p class="text-xs text-gray-400">Nieznany</p>
            </div>
        </div>
    </div>

    {{-- Konflikty --}}
    <div class="matrix-summary-card matrix-stat-conflicts">
        <div class="flex items-center space-x-2">
            <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center bg-yellow-900/40">
                <svg class="w-4 h-4 matrix-stat-value-conflicts" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div>
                <p class="text-xl font-bold matrix-stat-value-conflicts">{{ $summaryStats['conflicts'] ?? 0 }}</p>
                <p class="text-xs text-gray-400">Konflikty</p>
            </div>
        </div>
    </div>

    {{-- Zablokowane marki --}}
    <div class="matrix-summary-card matrix-stat-blocked">
        <div class="flex items-center space-x-2">
            <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center bg-gray-700/50">
                <svg class="w-4 h-4 matrix-stat-value-blocked" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6"/>
                </svg>
            </div>
            <div>
                <p class="text-xl font-bold matrix-stat-value-blocked">{{ $summaryStats['brand_blocked'] ?? 0 }}</p>
                <p class="text-xs text-gray-400">Zablokowane</p>
            </div>
        </div>
    </div>

</div>

{{-- Export buttons --}}
<div class="flex items-center gap-2 mb-4">
    <button wire:click="exportMatrix('xlsx')" class="flex items-center gap-1 px-2.5 py-1.5 bg-gray-700 text-gray-300 text-xs rounded-lg border border-gray-600 hover:bg-gray-600 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        XLSX
    </button>
    <button wire:click="exportMatrix('csv')" class="flex items-center gap-1 px-2.5 py-1.5 bg-gray-700 text-gray-300 text-xs rounded-lg border border-gray-600 hover:bg-gray-600 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        CSV
    </button>
</div>
