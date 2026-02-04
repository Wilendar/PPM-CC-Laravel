{{--
    Product Status Filters Partial
    Filter controls for data integrity status

    Wire models:
    - $dataStatusFilter: null, 'issues', 'ok'
    - $issueTypeFilters: ['zero_price', 'low_stock', 'no_images', 'not_in_prestashop', 'discrepancy']

    @since 2026-02-04
    @see Plan_Projektu/synthetic-mixing-thunder.md
--}}

<div class="bg-gray-800/50 rounded-lg p-4 border border-gray-700">
    <h4 class="text-sm font-medium text-white mb-3 flex items-center gap-2">
        <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Status zgodności danych
    </h4>

    {{-- General filter: All / Issues / OK --}}
    <div class="mb-4">
        <label class="block text-xs text-gray-400 mb-2">Pokaż produkty</label>
        <div class="flex gap-2">
            <button type="button"
                    wire:click="$set('dataStatusFilter', null)"
                    class="px-3 py-1.5 text-xs rounded-md transition-colors
                        {{ $dataStatusFilter === null
                            ? 'bg-orange-500/20 text-orange-400 border border-orange-500/50'
                            : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                Wszystkie
            </button>
            <button type="button"
                    wire:click="$set('dataStatusFilter', 'issues')"
                    class="px-3 py-1.5 text-xs rounded-md transition-colors
                        {{ $dataStatusFilter === 'issues'
                            ? 'bg-red-500/20 text-red-400 border border-red-500/50'
                            : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                <span class="flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Z problemami
                </span>
            </button>
            <button type="button"
                    wire:click="$set('dataStatusFilter', 'ok')"
                    class="px-3 py-1.5 text-xs rounded-md transition-colors
                        {{ $dataStatusFilter === 'ok'
                            ? 'bg-green-500/20 text-green-400 border border-green-500/50'
                            : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                <span class="flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Zgodne
                </span>
            </button>
        </div>
    </div>

    {{-- Specific issue type filters --}}
    <div>
        <label class="block text-xs text-gray-400 mb-2">Typ problemu (multi-select)</label>
        <div class="flex flex-wrap gap-2">
            {{-- Zero price --}}
            <label class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md cursor-pointer transition-colors
                {{ in_array('zero_price', $issueTypeFilters)
                    ? 'bg-red-500/20 text-red-400 border border-red-500/50'
                    : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                <input type="checkbox"
                       wire:model.live="issueTypeFilters"
                       value="zero_price"
                       class="sr-only">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-xs">Cena 0</span>
            </label>

            {{-- Low stock --}}
            <label class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md cursor-pointer transition-colors
                {{ in_array('low_stock', $issueTypeFilters)
                    ? 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/50'
                    : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                <input type="checkbox"
                       wire:model.live="issueTypeFilters"
                       value="low_stock"
                       class="sr-only">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <span class="text-xs">Niski stan</span>
            </label>

            {{-- No images --}}
            <label class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md cursor-pointer transition-colors
                {{ in_array('no_images', $issueTypeFilters)
                    ? 'bg-orange-500/20 text-orange-400 border border-orange-500/50'
                    : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                <input type="checkbox"
                       wire:model.live="issueTypeFilters"
                       value="no_images"
                       class="sr-only">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="text-xs">Brak zdjęć</span>
            </label>

            {{-- Not in PrestaShop --}}
            <label class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md cursor-pointer transition-colors
                {{ in_array('not_in_prestashop', $issueTypeFilters)
                    ? 'bg-gray-500/30 text-gray-300 border border-gray-500/50'
                    : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                <input type="checkbox"
                       wire:model.live="issueTypeFilters"
                       value="not_in_prestashop"
                       class="sr-only">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span class="text-xs">Brak w PS</span>
            </label>

            {{-- Data discrepancy --}}
            <label class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md cursor-pointer transition-colors
                {{ in_array('discrepancy', $issueTypeFilters)
                    ? 'bg-purple-500/20 text-purple-400 border border-purple-500/50'
                    : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                <input type="checkbox"
                       wire:model.live="issueTypeFilters"
                       value="discrepancy"
                       class="sr-only">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <span class="text-xs">Rozbieżność</span>
            </label>
        </div>
    </div>

    {{-- Clear filters button --}}
    @if($dataStatusFilter !== null || !empty($issueTypeFilters))
        <div class="mt-3 pt-3 border-t border-gray-700">
            <button type="button"
                    wire:click="$set('dataStatusFilter', null); $set('issueTypeFilters', [])"
                    class="text-xs text-gray-400 hover:text-white flex items-center gap-1 transition-colors">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Wyczyść filtry statusu
            </button>
        </div>
    @endif
</div>
