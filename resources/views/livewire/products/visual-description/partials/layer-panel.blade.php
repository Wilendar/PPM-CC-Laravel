{{-- Layer Panel - Block List with Move/Delete Controls --}}
{{-- IMPORTANT: No x-data on aside - it conflicts with Livewire morphing of block list --}}
<aside
    class="ve-layers flex-shrink-0 bg-gray-800 border-r border-gray-700 flex flex-col relative transition-all duration-300"
    style="width: {{ ($isLayersCollapsed ?? false) ? '48px' : '220px' }}"
>
    {{-- Header --}}
    <div class="flex items-center justify-between p-3 border-b border-gray-700">
        <button
            wire:click="toggleLayers"
            class="p-1.5 text-gray-400 hover:text-gray-200 hover:bg-gray-700 rounded transition"
        >
            <svg class="w-4 h-4 transition-transform {{ ($isLayersCollapsed ?? false) ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
            </svg>
        </button>
        @if(!($isLayersCollapsed ?? false))
            <span class="font-medium text-gray-200 text-sm">Warstwy</span>
            <span class="text-xs text-gray-500">{{ count($blocks) }}</span>
        @endif
    </div>

    @if(!($isLayersCollapsed ?? false))
        {{-- Block List --}}
        <div class="flex-1 overflow-y-auto p-2 space-y-1">
            @forelse($blocks as $index => $block)
                @php
                    $isSelected = $selectedBlockIndex === $index;
                    $blockType = $block['type'] ?? 'unknown';
                    $blockLabel = $this->getBlockLabel($index);
                @endphp
                <div
                    wire:key="layer-block-{{ $block['id'] ?? $index }}"
                    wire:click="selectBlock({{ $index }})"
                    @class([
                        'flex items-center gap-2 px-2 py-1.5 rounded cursor-pointer transition-colors group',
                        'bg-blue-500/20 text-blue-400 border border-blue-500/30' => $isSelected,
                        'hover:bg-gray-700 text-gray-300' => !$isSelected,
                    ])
                >
                    {{-- Block Type Icon --}}
                    @switch($blockType)
                        @case('heading')
                        @case('pd-heading')
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>
                            </svg>
                            @break
                        @case('text')
                        @case('paragraph')
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                            </svg>
                            @break
                        @case('image')
                        @case('pd-cover')
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            @break
                        @case('hero')
                        @case('hero-banner')
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z"/>
                            </svg>
                            @break
                        @case('columns')
                        @case('pd-cols')
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>
                            </svg>
                            @break
                        @case('list')
                        @case('pd-asset-list')
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                            </svg>
                            @break
                        @case('separator')
                        @case('divider')
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                            </svg>
                            @break
                        @case('prestashop-section')
                        @case('html')
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                            </svg>
                            @break
                        @case('video')
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            @break
                        @case('button')
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                            </svg>
                            @break
                        @default
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                    @endswitch

                    {{-- Block Label --}}
                    <span class="flex-1 text-xs truncate">
                        {{ $blockLabel }}
                    </span>

                    {{-- Actions --}}
                    <div @class([
                        'flex items-center gap-0.5',
                        'opacity-100' => $isSelected,
                        'opacity-0 group-hover:opacity-100' => !$isSelected,
                    ])>
                        {{-- Move Up --}}
                        @if($index > 0)
                        <button
                            wire:click.stop="moveBlockUp({{ $index }})"
                            class="p-1 hover:bg-gray-600 rounded transition-colors"
                            title="Przesun w gore"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                            </svg>
                        </button>
                        @endif

                        {{-- Move Down --}}
                        @if($index < count($blocks) - 1)
                        <button
                            wire:click.stop="moveBlockDown({{ $index }})"
                            class="p-1 hover:bg-gray-600 rounded transition-colors"
                            title="Przesun w dol"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        @endif

                        {{-- Delete --}}
                        <button
                            wire:click.stop="removeBlock({{ $index }})"
                            class="p-1 hover:bg-red-600/50 rounded transition-colors text-red-400"
                            title="Usun blok"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-8 text-center">
                    <svg class="w-8 h-8 text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <p class="text-xs text-gray-500">Brak blokow</p>
                    <p class="text-xs text-gray-600">Przeciagnij z palety</p>
                </div>
            @endforelse
        </div>

        {{-- Help Footer --}}
        <div class="p-2 border-t border-gray-700">
            <p class="text-xs text-gray-600 text-center">
                Kliknij blok aby wybrac
            </p>
        </div>
    @endif
</aside>
