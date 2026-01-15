{{-- Block Canvas - Center Area --}}
{{-- Google Fonts must be loaded via link tag, not @import in dynamic style --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">

{{-- PrestaShop CSS injection - scoped to .ve-canvas to prevent admin UI leak --}}
<style>
    /* === CRITICAL: CSS Grid Subgrid for proper column inheritance === */
    /* This allows block content to use parent grid columns while keeping wrapper box */

    /* Drop zones span full grid width */
    .ve-canvas .rte-content > .ve-drop-zone,
    .ve-canvas .rte-content > .ve-drop-zone-end {
        grid-column: 1 / -1;
    }

    /* Block wrapper uses subgrid to inherit parent columns */
    .ve-canvas .rte-content > .ve-canvas-block {
        display: grid;
        grid-column: 1 / -1; /* Span full parent width */
        grid-template-columns: subgrid; /* Inherit columns from .rte-content */
        position: relative; /* For toolbar positioning */
    }

    /* Preview wrapper also uses subgrid */
    .ve-canvas .ve-canvas-block > .pointer-events-none {
        display: grid;
        grid-column: 1 / -1;
        grid-template-columns: subgrid;
    }

    /* Ensure toolbar stays positioned correctly above block */
    .ve-canvas .ve-canvas-block > [class*="absolute"] {
        grid-column: 1 / -1;
        position: absolute; /* Override any grid positioning */
    }

    {!! $this->canvasPreviewCss !!}
</style>

<main
    class="ve-canvas flex-1 overflow-y-auto p-6 bg-gray-900"
    x-data="{
        dropIndex: null,
        showDropPreview: false,
        handleDrop(index) {
            if (draggedBlockType) {
                $wire.addBlock(draggedBlockType, index);
            }
            this.dropIndex = null;
            this.showDropPreview = false;
        },
        handleDragEnter(index) {
            if (isDragging) {
                this.dropIndex = index;
                this.showDropPreview = true;
            }
        },
        handleDragLeave(e) {
            // Only reset if leaving the canvas entirely
            if (!e.relatedTarget || !e.currentTarget.contains(e.relatedTarget)) {
                this.dropIndex = null;
                this.showDropPreview = false;
            }
        }
    }"
    @dragover.prevent="isDragging && (showDropPreview = true)"
    @dragleave="handleDragLeave($event)"
    @drop.prevent="handleDrop(0)"
>
    @if(empty($blocks))
        {{-- Empty State --}}
        <div
            class="flex flex-col items-center justify-center h-full p-8 border-2 border-dashed border-gray-700 rounded-xl"
            @dragover.prevent="isDragging && (dropIndex = 0)"
            @drop.prevent="handleDrop(0)"
            :class="{ 'border-blue-500 bg-blue-500/5': isDragging }"
        >
            <svg class="w-16 h-16 text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-300 mb-2">Przeciagnij bloki tutaj</h3>
            <p class="text-sm text-gray-500 text-center max-w-sm">
                Wybierz bloki z palety po lewej stronie lub kliknij na nie aby dodac do opisu.
            </p>
            <button
                wire:click="openLoadTemplateModal"
                class="mt-4 px-4 py-2 text-sm bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg transition"
            >
                Zaladuj szablon
            </button>
        </div>
    @else
        {{-- Block List --}}
        {{-- CRITICAL: Mirror PrestaShop structure for CSS grid to work correctly --}}
        {{-- PrestaShop: #product > .tabs > #description > .product-description > .rte-content --}}
        {{-- We simulate: .product-description > .rte-content (triggers grid layout) --}}
        <div class="product-description">
            <div
                class="rte-content ve-canvas-blocks w-full"
                wire:sortable="reorderBlocks"
                wire:sortable.options="{ animation: 150, ghostClass: 've-sortable-ghost' }"
            >
            @foreach($blocks as $index => $block)
                @php $blockId = $block['id'] ?? 'idx_' . $index; @endphp
                {{-- Drop Zone Before - Enhanced with insertion line indicator --}}
                <div
                    class="ve-drop-zone relative transition-all duration-200 ease-out"
                    @dragover.prevent="handleDragEnter({{ $index }})"
                    @dragenter.prevent="handleDragEnter({{ $index }})"
                    @drop.prevent="handleDrop({{ $index }})"
                    :class="dropIndex === {{ $index }} ? 'h-16 my-2' : 'h-2 -my-1'"
                >
                    {{-- Insertion Line Indicator --}}
                    <div
                        x-show="dropIndex === {{ $index }}"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-y-0"
                        x-transition:enter-end="opacity-100 scale-y-100"
                        class="absolute inset-x-0 top-1/2 -translate-y-1/2 flex items-center gap-2"
                    >
                        {{-- Left dot --}}
                        <div class="w-3 h-3 rounded-full bg-blue-500 shadow-lg shadow-blue-500/50 animate-pulse"></div>
                        {{-- Line --}}
                        <div class="flex-1 h-0.5 bg-gradient-to-r from-blue-500 via-blue-400 to-blue-500 rounded-full"></div>
                        {{-- Right dot --}}
                        <div class="w-3 h-3 rounded-full bg-blue-500 shadow-lg shadow-blue-500/50 animate-pulse"></div>
                    </div>
                    {{-- Drop hint text --}}
                    <div
                        x-show="dropIndex === {{ $index }}"
                        x-transition:enter="transition ease-out duration-200 delay-100"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        class="absolute inset-x-0 top-1/2 -translate-y-1/2 flex justify-center"
                    >
                        <span class="px-3 py-1 text-xs font-medium text-blue-300 bg-blue-500/20 rounded-full border border-blue-500/30">
                            Upusc tutaj
                        </span>
                    </div>
                </div>

                {{-- Block Item --}}
                {{-- NO bg-gray-800 - let PrestaShop CSS backgrounds show through --}}
                {{-- mt-4 gives space for toolbar above, NO overflow-hidden (clips toolbar) --}}
                <div
                    wire:sortable.item="{{ $blockId }}"
                    wire:key="block-{{ $blockId }}"
                    wire:click="selectBlock({{ $index }})"
                    class="ve-canvas-block group relative mt-4 border-2 rounded-lg cursor-pointer transition-all
                        {{ $selectedBlockIndex === $index ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-transparent hover:border-gray-400' }}"
                >
                    {{-- Block Toolbar - positioned above block --}}
                    {{-- Visible on hover OR when block is selected --}}
                    <div @class([
                        'absolute -top-3 left-4 flex items-center gap-1 transition-opacity z-10',
                        'opacity-100' => $selectedBlockIndex === $index,
                        'opacity-0 group-hover:opacity-100' => $selectedBlockIndex !== $index,
                    ])>
                        <span class="px-2 py-0.5 text-xs bg-gray-700 text-gray-300 rounded">
                            {{ $block['type'] }}
                        </span>
                    </div>

                    <div @class([
                        'absolute -top-3 right-4 flex items-center gap-1 transition-opacity z-10',
                        'opacity-100' => $selectedBlockIndex === $index,
                        'opacity-0 group-hover:opacity-100' => $selectedBlockIndex !== $index,
                    ])>
                        {{-- Move Handle --}}
                        <button
                            wire:sortable.handle
                            class="p-1 bg-gray-700 text-gray-400 hover:text-gray-200 rounded cursor-grab"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                            </svg>
                        </button>

                        {{-- Edit in VBB (Visual Block Builder) --}}
                        <button
                            wire:click.stop="openBlockInVBB({{ $index }})"
                            class="p-1 bg-blue-600 text-white hover:bg-blue-500 rounded"
                            title="Edytuj w Visual Block Builder"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>

                        {{-- Create Dedicated Block (only for prestashop-section) --}}
                        @if($block['type'] === 'prestashop-section')
                        <button
                            wire:click.stop="openBlockGenerator({{ $index }})"
                            class="p-1 bg-amber-600/80 text-amber-100 hover:bg-amber-500 rounded"
                            title="Utworz dedykowany blok"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </button>
                        @endif

                        {{-- Duplicate --}}
                        <button
                            wire:click.stop="duplicateBlock({{ $index }})"
                            class="p-1 bg-gray-700 text-gray-400 hover:text-gray-200 rounded"
                            title="Duplikuj"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>

                        {{-- Delete --}}
                        <button
                            wire:click.stop="removeBlock({{ $index }})"
                            class="p-1 bg-gray-700 text-gray-400 hover:text-red-400 hover:bg-red-500/10 rounded"
                            title="Usun"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Block Preview --}}
                    <div class="pointer-events-none">
                        {!! $this->renderBlockPreview($index) !!}
                    </div>
                </div>
            @endforeach

            {{-- Drop Zone After Last Block - Enhanced --}}
            <div
                class="ve-drop-zone-end relative mt-4 transition-all duration-200 ease-out rounded-lg"
                @dragover.prevent="handleDragEnter({{ count($blocks) }})"
                @dragenter.prevent="handleDragEnter({{ count($blocks) }})"
                @drop.prevent="handleDrop({{ count($blocks) }})"
                :class="dropIndex === {{ count($blocks) }}
                    ? 'h-24 border-2 border-blue-500 bg-blue-500/10'
                    : 'h-12 border-2 border-dashed border-gray-700 hover:border-gray-600'"
            >
                {{-- Default state hint --}}
                <div
                    x-show="dropIndex !== {{ count($blocks) }}"
                    class="absolute inset-0 flex items-center justify-center"
                >
                    <span class="text-xs text-gray-500">Przeciagnij blok tutaj</span>
                </div>
                {{-- Active drop state --}}
                <div
                    x-show="dropIndex === {{ count($blocks) }}"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="absolute inset-0 flex flex-col items-center justify-center gap-2"
                >
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-blue-500 shadow-lg shadow-blue-500/50 animate-pulse"></div>
                        <div class="w-32 h-0.5 bg-gradient-to-r from-blue-500 via-blue-400 to-blue-500 rounded-full"></div>
                        <div class="w-3 h-3 rounded-full bg-blue-500 shadow-lg shadow-blue-500/50 animate-pulse"></div>
                    </div>
                    <span class="text-sm font-medium text-blue-300">Dodaj na koniec</span>
                </div>
            </div>
            </div>{{-- Close .rte-content --}}
        </div>{{-- Close .product-description --}}
    @endif
</main>
