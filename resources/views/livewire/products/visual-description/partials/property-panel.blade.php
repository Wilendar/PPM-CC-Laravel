{{-- Property Panel - Right Panel with Resize --}}
<aside
    class="ve-properties flex-shrink-0 bg-gray-800 border-l border-gray-700 flex flex-col relative"
    x-data="{
        width: {{ $isPropertiesCollapsed ? '48' : '400' }},
        minWidth: 300,
        maxWidth: 600,
        isResizing: false,
        startX: 0,
        startWidth: 0,

        startResize(e) {
            this.isResizing = true;
            this.startX = e.clientX;
            this.startWidth = this.width;
            document.addEventListener('mousemove', this.resize.bind(this));
            document.addEventListener('mouseup', this.stopResize.bind(this));
            document.body.style.cursor = 'ew-resize';
            document.body.style.userSelect = 'none';
        },

        resize(e) {
            if (!this.isResizing) return;
            const delta = this.startX - e.clientX;
            let newWidth = this.startWidth + delta;
            newWidth = Math.max(this.minWidth, Math.min(this.maxWidth, newWidth));
            this.width = newWidth;
        },

        stopResize() {
            this.isResizing = false;
            document.removeEventListener('mousemove', this.resize.bind(this));
            document.removeEventListener('mouseup', this.stopResize.bind(this));
            document.body.style.cursor = '';
            document.body.style.userSelect = '';
        }
    }"
    :style="'width: ' + ({{ $isPropertiesCollapsed ? 1 : 0 }} ? '48px' : width + 'px')"
    :class="{ 'transition-all duration-300': !isResizing }"
>
    {{-- Resize Handle --}}
    @if(!$isPropertiesCollapsed)
    <div
        class="absolute left-0 top-0 bottom-0 w-1 cursor-ew-resize hover:bg-blue-500/50 transition-colors z-10 group"
        @mousedown.prevent="startResize($event)"
    >
        <div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-12 bg-gray-600 group-hover:bg-blue-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
    </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between p-3 border-b border-gray-700">
        <button
            wire:click="toggleProperties"
            class="p-1.5 text-gray-400 hover:text-gray-200 hover:bg-gray-700 rounded transition"
        >
            <svg class="w-4 h-4 transition-transform {{ $isPropertiesCollapsed ? '' : 'rotate-180' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
            </svg>
        </button>
        @if(!$isPropertiesCollapsed)
            <span class="font-medium text-gray-200">Wlasciwosci</span>
        @endif
    </div>

    @if(!$isPropertiesCollapsed)
        @if($this->selectedBlock)
            @php
                $block = $this->selectedBlock;
                $registry = app(\App\Services\VisualEditor\BlockRegistry::class);
                $blockInstance = $registry->get($block['type']);
                $schema = $blockInstance ? $blockInstance->getSchema() : [];
            @endphp

            {{-- Block Type Badge --}}
            <div class="px-4 py-2 border-b border-gray-700">
                <span class="px-2 py-1 text-xs bg-blue-500/20 text-blue-400 rounded">
                    {{ $block['type'] }}
                </span>
            </div>

            {{-- Property Tabs --}}
            <div class="flex-1 overflow-y-auto" x-data="{ activeTab: 'content' }">
                {{-- Tab Navigation --}}
                <div class="flex border-b border-gray-700">
                    <button
                        @click="activeTab = 'content'"
                        class="flex-1 px-4 py-2 text-sm font-medium transition"
                        :class="activeTab === 'content' ? 'text-blue-400 border-b-2 border-blue-400' : 'text-gray-400 hover:text-gray-200'"
                    >
                        Tresc
                    </button>
                    <button
                        @click="activeTab = 'style'"
                        class="flex-1 px-4 py-2 text-sm font-medium transition"
                        :class="activeTab === 'style' ? 'text-blue-400 border-b-2 border-blue-400' : 'text-gray-400 hover:text-gray-200'"
                    >
                        Styl
                    </button>
                    <button
                        @click="activeTab = 'advanced'"
                        class="flex-1 px-4 py-2 text-sm font-medium transition"
                        :class="activeTab === 'advanced' ? 'text-blue-400 border-b-2 border-blue-400' : 'text-gray-400 hover:text-gray-200'"
                    >
                        Zaawansowane
                    </button>
                </div>

                {{-- Content Tab --}}
                <div x-show="activeTab === 'content'" class="p-4 space-y-4">
                    @foreach($schema['content'] ?? [] as $propName => $propConfig)
                        @include('livewire.products.visual-description.partials.property-field', [
                            'name' => $propName,
                            'config' => $propConfig,
                            'value' => data_get($block['data'], $propName),
                            'index' => $selectedBlockIndex,
                        ])
                    @endforeach
                </div>

                {{-- Style Tab --}}
                <div x-show="activeTab === 'style'" class="p-4 space-y-4">
                    @foreach($schema['settings'] ?? [] as $propName => $propConfig)
                        @php
                            $group = $propConfig['group'] ?? 'default';
                        @endphp
                        @if(!in_array($group, ['advanced', 'wrapper']))
                            @include('livewire.products.visual-description.partials.property-field', [
                                'name' => $propName,
                                'config' => $propConfig,
                                'value' => data_get($block['data'], $propName),
                                'index' => $selectedBlockIndex,
                            ])
                        @endif
                    @endforeach

                    {{-- Common Style Properties --}}
                    <div class="pt-4 border-t border-gray-700">
                        <h4 class="text-xs font-medium text-gray-500 uppercase mb-3">Ogolne</h4>

                        {{-- Margin --}}
                        <div class="mb-4">
                            <label class="block text-sm text-gray-400 mb-1">Margines</label>
                            <div class="grid grid-cols-4 gap-2">
                                <input type="text" placeholder="T" class="ve-property-input" wire:change="updateBlockProperty({{ $selectedBlockIndex }}, 'margin.top', $event.target.value)" value="{{ $block['data']['margin']['top'] ?? '' }}">
                                <input type="text" placeholder="R" class="ve-property-input" wire:change="updateBlockProperty({{ $selectedBlockIndex }}, 'margin.right', $event.target.value)" value="{{ $block['data']['margin']['right'] ?? '' }}">
                                <input type="text" placeholder="B" class="ve-property-input" wire:change="updateBlockProperty({{ $selectedBlockIndex }}, 'margin.bottom', $event.target.value)" value="{{ $block['data']['margin']['bottom'] ?? '' }}">
                                <input type="text" placeholder="L" class="ve-property-input" wire:change="updateBlockProperty({{ $selectedBlockIndex }}, 'margin.left', $event.target.value)" value="{{ $block['data']['margin']['left'] ?? '' }}">
                            </div>
                        </div>

                        {{-- Padding --}}
                        <div>
                            <label class="block text-sm text-gray-400 mb-1">Padding</label>
                            <div class="grid grid-cols-4 gap-2">
                                <input type="text" placeholder="T" class="ve-property-input" wire:change="updateBlockProperty({{ $selectedBlockIndex }}, 'padding.top', $event.target.value)" value="{{ $block['data']['padding']['top'] ?? '' }}">
                                <input type="text" placeholder="R" class="ve-property-input" wire:change="updateBlockProperty({{ $selectedBlockIndex }}, 'padding.right', $event.target.value)" value="{{ $block['data']['padding']['right'] ?? '' }}">
                                <input type="text" placeholder="B" class="ve-property-input" wire:change="updateBlockProperty({{ $selectedBlockIndex }}, 'padding.bottom', $event.target.value)" value="{{ $block['data']['padding']['bottom'] ?? '' }}">
                                <input type="text" placeholder="L" class="ve-property-input" wire:change="updateBlockProperty({{ $selectedBlockIndex }}, 'padding.left', $event.target.value)" value="{{ $block['data']['padding']['left'] ?? '' }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Advanced Tab --}}
                <div x-show="activeTab === 'advanced'" class="p-4 space-y-4">
                    {{-- Block-specific advanced properties --}}
                    @foreach($schema['content'] ?? [] as $propName => $propConfig)
                        @if(($propConfig['group'] ?? '') === 'advanced' || ($propConfig['type'] ?? '') === 'code')
                            @include('livewire.products.visual-description.partials.property-field', [
                                'name' => $propName,
                                'config' => $propConfig,
                                'value' => data_get($block['data'], $propName),
                                'index' => $selectedBlockIndex,
                            ])
                        @endif
                    @endforeach

                    @foreach($schema['settings'] ?? [] as $propName => $propConfig)
                        @if(in_array($propConfig['group'] ?? '', ['advanced', 'wrapper']))
                            @include('livewire.products.visual-description.partials.property-field', [
                                'name' => $propName,
                                'config' => $propConfig,
                                'value' => data_get($block['data'], $propName),
                                'index' => $selectedBlockIndex,
                            ])
                        @endif
                    @endforeach

                    {{-- Common Advanced Properties (for all blocks) --}}
                    <div class="pt-4 border-t border-gray-700">
                        <h4 class="text-xs font-medium text-gray-500 uppercase mb-3">Atrybuty elementu</h4>

                        {{-- CSS Classes --}}
                        <div class="mb-3">
                            <label class="block text-sm text-gray-400 mb-1">Dodatkowe klasy CSS</label>
                            <input
                                type="text"
                                class="ve-property-input w-full"
                                wire:change="updateBlockProperty({{ $selectedBlockIndex }}, 'cssClasses', $event.target.value)"
                                value="{{ $block['data']['cssClasses'] ?? '' }}"
                                placeholder="my-class another-class"
                            >
                            <p class="mt-1 text-xs text-gray-600">Rozdziel klasy spacjami</p>
                        </div>

                        {{-- Custom ID --}}
                        <div class="mb-3">
                            <label class="block text-sm text-gray-400 mb-1">ID elementu</label>
                            <input
                                type="text"
                                class="ve-property-input w-full"
                                wire:change="updateBlockProperty({{ $selectedBlockIndex }}, 'customId', $event.target.value)"
                                value="{{ $block['data']['customId'] ?? '' }}"
                                placeholder="my-unique-id"
                            >
                        </div>

                        {{-- Data Attributes --}}
                        <div class="mb-3">
                            <label class="block text-sm text-gray-400 mb-1">Atrybuty data-*</label>
                            <textarea
                                rows="3"
                                class="ve-property-input w-full font-mono text-xs"
                                wire:change="updateBlockProperty({{ $selectedBlockIndex }}, 'dataAttributes', $event.target.value)"
                                placeholder="product-id=123&#10;category=akcesoria&#10;price=99.99"
                            >{{ $block['data']['dataAttributes'] ?? '' }}</textarea>
                            <p class="mt-1 text-xs text-gray-600">Jedna para klucz=wartosc na linie</p>
                        </div>
                    </div>

                    {{-- Visibility & Behavior --}}
                    <div class="pt-4 border-t border-gray-700">
                        <h4 class="text-xs font-medium text-gray-500 uppercase mb-3">Widocznosc</h4>

                        <div class="space-y-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    class="checkbox-enterprise"
                                    wire:change="updateBlockProperty({{ $selectedBlockIndex }}, 'hidden', $event.target.checked)"
                                    {{ ($block['data']['hidden'] ?? false) ? 'checked' : '' }}
                                >
                                <span class="text-sm text-gray-400">Ukryty (nie renderuj)</span>
                            </label>

                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    class="checkbox-enterprise"
                                    wire:change="updateBlockProperty({{ $selectedBlockIndex }}, 'mobileHidden', $event.target.checked)"
                                    {{ ($block['data']['mobileHidden'] ?? false) ? 'checked' : '' }}
                                >
                                <span class="text-sm text-gray-400">Ukryj na mobile</span>
                            </label>

                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    class="checkbox-enterprise"
                                    wire:change="updateBlockProperty({{ $selectedBlockIndex }}, 'desktopHidden', $event.target.checked)"
                                    {{ ($block['data']['desktopHidden'] ?? false) ? 'checked' : '' }}
                                >
                                <span class="text-sm text-gray-400">Ukryj na desktop</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Block Actions --}}
            <div class="p-3 border-t border-gray-700 space-y-2">
                {{-- Edit in VBB - Primary Action --}}
                <button
                    wire:click="openBlockInVBB({{ $selectedBlockIndex }})"
                    class="w-full px-3 py-2 text-sm bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition flex items-center justify-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edytuj w VBB
                </button>
                <button
                    wire:click="duplicateBlock({{ $selectedBlockIndex }})"
                    class="w-full px-3 py-2 text-sm bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition"
                >
                    Duplikuj blok
                </button>
                <button
                    wire:click="removeBlock({{ $selectedBlockIndex }})"
                    class="w-full px-3 py-2 text-sm bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg transition"
                >
                    Usun blok
                </button>
            </div>
        @else
            {{-- No Block Selected --}}
            <div class="flex-1 flex flex-col items-center justify-center p-6 text-center">
                <svg class="w-12 h-12 text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                </svg>
                <p class="text-sm text-gray-500">Wybierz blok aby edytowac jego wlasciwosci</p>
            </div>
        @endif
    @endif
</aside>

<style>
.ve-property-input {
    @apply px-2 py-1.5 bg-gray-900 border border-gray-700 rounded text-sm text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}
</style>
