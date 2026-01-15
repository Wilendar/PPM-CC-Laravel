{{-- Block Palette - Left Panel --}}
<aside class="ve-palette {{ $isPaletteCollapsed ? 'w-12' : 'w-64' }} flex-shrink-0 bg-gray-800 border-r border-gray-700 flex flex-col transition-all duration-300">
    {{-- Header --}}
    <div class="flex items-center justify-between p-3 border-b border-gray-700">
        @if(!$isPaletteCollapsed)
            <span class="font-medium text-gray-200">Bloki</span>
        @endif
        <button
            wire:click="togglePalette"
            class="p-1.5 text-gray-400 hover:text-gray-200 hover:bg-gray-700 rounded transition"
        >
            <svg class="w-4 h-4 transition-transform {{ $isPaletteCollapsed ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
            </svg>
        </button>
    </div>

    @if(!$isPaletteCollapsed)
        {{-- Create Custom Block Button --}}
        <div class="p-3 border-b border-gray-700">
            <button
                wire:click="$dispatch('openBlockBuilder', { shopId: {{ $shopId }} })"
                class="w-full flex items-center justify-center gap-2 px-3 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white text-sm font-medium rounded-lg transition-all"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Stworz blok wizualnie
            </button>
        </div>

        {{-- Search --}}
        <div class="p-3 border-b border-gray-700">
            <input
                type="text"
                x-data="{ search: '' }"
                x-model="search"
                placeholder="Szukaj blokow..."
                class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm text-gray-200 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
        </div>

        {{-- Block Categories --}}
        <div class="flex-1 overflow-y-auto p-3 space-y-4">
            @foreach($this->blockPalette as $category => $categoryData)
                <div x-data="{ expanded: true }">
                    {{-- Category Header --}}
                    <button
                        @click="expanded = !expanded"
                        class="w-full flex items-center justify-between py-2 text-sm font-medium text-gray-400 hover:text-gray-200 transition"
                    >
                        <span>{{ $categoryData['label'] ?? ucfirst($category) }}</span>
                        <svg class="w-4 h-4 transition-transform" :class="expanded ? '' : '-rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    {{-- Block Items --}}
                    <div x-show="expanded" x-collapse class="grid grid-cols-2 gap-2 mt-2">
                        @foreach($categoryData['blocks'] ?? [] as $block)
                            @php
                                $blockDescription = $block->description ?? match($block->type) {
                                    'hero-banner' => 'Baner pelnej szerokosci z obrazem/video',
                                    'two-column' => 'Uklad dwukolumnowy',
                                    'three-column' => 'Uklad trzech kolumn',
                                    'grid' => 'Elastyczna siatka CSS',
                                    'full-width' => 'Kontener pelnej szerokosci',
                                    'heading' => 'Naglowek H1-H6',
                                    'text' => 'Blok tekstowy WYSIWYG',
                                    'feature-card' => 'Karta cechy z ikona',
                                    'specs-table' => 'Tabela specyfikacji',
                                    'benefits-list' => 'Lista zalet z ikonami',
                                    'info-card' => 'Karta informacyjna',
                                    'image' => 'Pojedynczy obraz',
                                    'gallery' => 'Galeria obrazow',
                                    'video-embed' => 'Video YouTube/Vimeo',
                                    'parallax-image' => 'Obraz z efektem paralaksy',
                                    'responsive-picture' => 'Obraz responsywny',
                                    'slider' => 'Slider/karuzela',
                                    'accordion' => 'Rozwijane sekcje FAQ',
                                    'tabs' => 'Zakladki z przelaczaniem',
                                    'cta-button' => 'Przycisk Call-to-Action',
                                    'raw-html' => 'Wlasny kod HTML',
                                    default => 'Kliknij lub przeciagnij aby dodac'
                                };
                            @endphp
                            <div
                                x-data="{ showTooltip: false }"
                                @mouseenter="showTooltip = true"
                                @mouseleave="showTooltip = false"
                                draggable="true"
                                @dragstart="draggedBlockType = '{{ $block->type }}'; isDragging = true; showTooltip = false"
                                @dragend="draggedBlockType = null; isDragging = false"
                                wire:click="addBlock('{{ $block->type }}')"
                                class="ve-palette-block relative flex flex-col items-center p-3 bg-gray-900 border border-gray-700 rounded-lg cursor-grab hover:border-blue-500 hover:bg-gray-900/80 transition group"
                                title="{{ $blockDescription }}"
                            >
                                <x-icon :name="$block->icon" class="w-6 h-6 mb-1 group-hover:scale-110 transition-transform text-gray-400 group-hover:text-blue-400" />
                                <span class="text-xs text-gray-400 text-center">{{ $block->name }}</span>
                                {{-- Tooltip --}}
                                <div
                                    x-show="showTooltip"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-cloak
                                    class="absolute z-50 left-full ml-2 top-1/2 -translate-y-1/2 w-48 px-3 py-2 bg-gray-950 border border-gray-700 rounded-lg shadow-xl text-xs text-gray-300 pointer-events-none"
                                >
                                    {{ $blockDescription }}
                                    <div class="absolute right-full top-1/2 -translate-y-1/2 border-8 border-transparent border-r-gray-950"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- Collapsed View - Icons Only --}}
        <div class="flex-1 overflow-y-auto py-2 space-y-1">
            @foreach($this->blockPalette as $category => $categoryData)
                @foreach($categoryData['blocks'] ?? [] as $block)
                    <button
                        wire:click="addBlock('{{ $block->type }}')"
                        class="w-full p-2 flex items-center justify-center hover:bg-gray-700 transition"
                        title="{{ $block->name }}"
                    >
                        <x-icon :name="$block->icon" class="w-5 h-5 text-gray-400 hover:text-blue-400" />
                    </button>
                @endforeach
            @endforeach
        </div>
    @endif
</aside>
