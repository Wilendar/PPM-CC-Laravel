@php
    $flatList = $this->flatElementList;
@endphp

<div class="p-4 space-y-2">
    <div class="flex items-center justify-between pb-2 border-b border-gray-700">
        <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Struktura bloku</span>
        <span class="text-xs text-gray-500">{{ count($flatList) }} elementow</span>
    </div>

    <div class="space-y-1">
        @foreach($flatList as $item)
            @php
                $isSelected = $selectedElementId === $item['id'];
                $isRoot = $item['depth'] === 0;
            @endphp
            <div
                wire:key="layer-{{ $item['id'] }}"
                wire:click="selectElement('{{ $item['id'] }}')"
                @class([
                    'flex items-center gap-2 px-2 py-1.5 rounded cursor-pointer transition-colors group',
                    'bg-amber-500/20 text-amber-400' => $isSelected,
                    'hover:bg-gray-700 text-gray-300' => !$isSelected,
                ])
                style="padding-left: {{ 8 + ($item['depth'] * 16) }}px"
            >
                {{-- Expand/Collapse indicator for containers --}}
                @if($item['hasChildren'])
                    <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                @else
                    <span class="w-3"></span>
                @endif

                {{-- Element Type Icon --}}
                @switch($item['type'])
                    @case('heading')
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>
                        </svg>
                        @break
                    @case('text')
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        @break
                    @case('image')
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        @break
                    @case('button')
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                        </svg>
                        @break
                    @case('separator')
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                        </svg>
                        @break
                    @case('container')
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                        </svg>
                        @break
                    @case('row')
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        @break
                    @case('icon')
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                        @break
                    @default
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                @endswitch

                {{-- Element Label --}}
                <span class="flex-1 text-sm truncate">
                    @if($isRoot)
                        <span class="font-medium">Root</span>
                    @elseif($item['content'])
                        {{ Str::limit($item['content'], 20) }}
                    @else
                        {{ ucfirst($item['type']) }}
                    @endif
                </span>

                {{-- Actions - ALWAYS VISIBLE with opacity --}}
                <div @class([
                    'flex items-center gap-1',
                    'opacity-100' => $isSelected,
                    'opacity-60 group-hover:opacity-100' => !$isSelected,
                ])>
                    @if(!$isRoot)
                        {{-- Move Up --}}
                        <button
                            wire:click.stop="moveElementUp('{{ $item['id'] }}')"
                            class="p-1.5 hover:bg-gray-600 rounded transition-colors"
                            title="Przesun w gore"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                            </svg>
                        </button>

                        {{-- Move Down --}}
                        <button
                            wire:click.stop="moveElementDown('{{ $item['id'] }}')"
                            class="p-1.5 hover:bg-gray-600 rounded transition-colors"
                            title="Przesun w dol"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <span class="w-px h-4 bg-gray-600 mx-0.5"></span>
                    @endif

                    {{-- Visibility Toggle --}}
                    <button
                        wire:click.stop="toggleVisibility('{{ $item['id'] }}')"
                        class="p-1.5 hover:bg-gray-600 rounded transition-colors"
                        title="{{ $item['visible'] ? 'Ukryj' : 'Pokaz' }}"
                    >
                        @if($item['visible'])
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        @endif
                    </button>

                    {{-- Lock Toggle --}}
                    <button
                        wire:click.stop="toggleLock('{{ $item['id'] }}')"
                        class="p-1.5 hover:bg-gray-600 rounded transition-colors"
                        title="{{ $item['locked'] ? 'Odblokuj' : 'Zablokuj' }}"
                    >
                        @if($item['locked'])
                            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                            </svg>
                        @endif
                    </button>

                    {{-- Delete (not for root) --}}
                    @if(!$isRoot)
                        <button
                            wire:click.stop="deleteElement('{{ $item['id'] }}')"
                            class="p-1.5 hover:bg-red-600/50 rounded transition-colors text-red-400"
                            title="Usun"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Help Text --}}
    <div class="pt-4 mt-4 border-t border-gray-700">
        <p class="text-xs text-gray-500">
            Kliknij element, aby go zaznaczyc.<br>
            <span class="text-gray-600">↑↓</span> Zmien kolejnosc |
            <span class="text-gray-600">👁</span> Widocznosc |
            <span class="text-gray-600">🔒</span> Blokada
        </p>
    </div>
</div>
