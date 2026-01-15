{{-- Variable Picker Modal - ETAP_07f FAZA 4 --}}
@if($showVariableModal ?? false)
<div
    class="fixed inset-0 z-50 overflow-y-auto"
    x-data="{ search: '', copied: null }"
    @keydown.escape.window="$wire.closeVariableModal()"
>
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="closeVariableModal"></div>

    {{-- Modal --}}
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="relative bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl border border-gray-700">
            {{-- Header --}}
            <div class="flex items-center justify-between p-4 border-b border-gray-700">
                <div>
                    <h3 class="text-lg font-semibold text-white">Zmienne produktu</h3>
                    <p class="text-sm text-gray-400 mt-1">Kliknij aby skopiowac do schowka</p>
                </div>
                <button
                    wire:click="closeVariableModal"
                    class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Search --}}
            <div class="p-4 border-b border-gray-700">
                <input
                    type="text"
                    x-model="search"
                    placeholder="Szukaj zmiennych..."
                    class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
            </div>

            {{-- Variables List --}}
            <div class="max-h-96 overflow-y-auto p-4 space-y-4">
                @php
                    $variableService = app(\App\Services\VisualEditor\TemplateVariableService::class);
                    $categories = $variableService->getVariableCategories();
                    $grouped = $variableService->getVariablesGroupedByCategory();
                @endphp

                @foreach($categories as $categoryKey => $categoryLabel)
                    @if(isset($grouped[$categoryKey]) && count($grouped[$categoryKey]) > 0)
                        <div class="space-y-2">
                            {{-- Category Header --}}
                            <h4 class="text-sm font-medium text-gray-400 uppercase tracking-wider flex items-center gap-2">
                                @switch($categoryKey)
                                    @case('product')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        @break
                                    @case('media')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        @break
                                    @case('features')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                        </svg>
                                        @break
                                    @case('manufacturer')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                        @break
                                    @case('category')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                        </svg>
                                        @break
                                    @case('shop')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                        @break
                                    @case('datetime')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        @break
                                @endswitch
                                {{ $categoryLabel }}
                            </h4>

                            {{-- Variables in Category --}}
                            <div class="grid gap-2">
                                @foreach($grouped[$categoryKey] as $varKey => $varData)
                                    <button
                                        x-show="!search || '{{ $varKey }}'.toLowerCase().includes(search.toLowerCase()) || '{{ $varData['description'] }}'.toLowerCase().includes(search.toLowerCase())"
                                        @click="navigator.clipboard.writeText('{' + '{' + '{{ $varKey }}' + '}' + '}').then(() => { copied = '{{ $varKey }}'; setTimeout(() => copied = null, 2000); })"
                                        class="group flex items-start gap-3 p-3 bg-gray-900/50 hover:bg-gray-900 border border-gray-700 hover:border-blue-500/50 rounded-lg transition text-left"
                                    >
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <code class="text-sm text-blue-400 font-mono" x-text="'{' + '{' + '{{ $varKey }}' + '}' + '}'"></code>
                                                <span
                                                    x-show="copied === '{{ $varKey }}'"
                                                    x-transition
                                                    class="text-xs text-green-400"
                                                >Skopiowano!</span>
                                            </div>
                                            <p class="text-sm text-gray-400 mt-1">{{ $varData['description'] }}</p>
                                            <p class="text-xs text-gray-600 mt-1">
                                                Przyklad: <span class="text-gray-500">{{ Str::limit($varData['example'], 50) }}</span>
                                            </p>
                                        </div>
                                        <svg class="w-4 h-4 text-gray-600 group-hover:text-blue-400 transition flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- Footer --}}
            <div class="p-4 border-t border-gray-700 bg-gray-800/50 rounded-b-xl">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-500">
                        <span class="text-gray-400">Tip:</span> Zmienne sa zamieniane na prawdziwe dane produktu podczas renderowania
                    </p>
                    <button
                        wire:click="closeVariableModal"
                        class="px-4 py-2 text-sm bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition"
                    >
                        Zamknij
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
