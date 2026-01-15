<div
    class="h-screen flex flex-col bg-gray-900"
    x-data="{
        draggedBlockType: null,
        isDragging: false,
        showCodeView: @entangle('viewMode') === 'code'
    }"
    @keydown.ctrl.z.window.prevent="$wire.undo()"
    @keydown.ctrl.y.window.prevent="$wire.redo()"
    @keydown.ctrl.s.window.prevent="$wire.save()"
>
    {{-- Editor Header --}}
    <header class="flex items-center justify-between px-4 py-3 bg-gray-800 border-b border-gray-700">
        <div class="flex items-center gap-4">
            {{-- Back Button --}}
            @if($this->product)
                <a href="{{ route('products.edit', $productId) }}" class="text-gray-400 hover:text-gray-200 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
            @endif

            {{-- Title --}}
            <div>
                <h1 class="text-lg font-semibold text-gray-100">
                    Edytor Wizualny
                    @if($this->product)
                        <span class="text-gray-400">- {{ $this->product->name }}</span>
                    @endif
                </h1>
                @if($this->shop)
                    <p class="text-xs text-gray-500">{{ $this->shop->name }}</p>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-3">
            {{-- Dirty Indicator --}}
            @if($isDirty)
                <span class="px-2 py-1 text-xs bg-amber-500/20 text-amber-400 rounded">Niezapisane zmiany</span>
            @endif

            {{-- Undo/Redo --}}
            <div class="flex items-center gap-1 border-r border-gray-700 pr-3">
                <button
                    wire:click="undo"
                    @if(!$this->canUndo) disabled @endif
                    class="p-2 text-gray-400 hover:text-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition"
                    title="Cofnij (Ctrl+Z)"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                </button>
                <button
                    wire:click="redo"
                    @if(!$this->canRedo) disabled @endif
                    class="p-2 text-gray-400 hover:text-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition"
                    title="Ponow (Ctrl+Y)"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10h-10a8 8 0 00-8 8v2M21 10l-6 6m6-6l-6-6"/>
                    </svg>
                </button>
            </div>

            {{-- View Mode Toggle --}}
            <div class="flex items-center bg-gray-700 rounded-lg p-0.5">
                <button
                    wire:click="setViewMode('edit')"
                    class="px-3 py-1.5 text-xs font-medium rounded-md transition
                        {{ $viewMode === 'edit' ? 'bg-gray-600 text-white' : 'text-gray-400 hover:text-gray-200' }}"
                >
                    Edycja
                </button>
                <button
                    wire:click="setViewMode('preview')"
                    class="px-3 py-1.5 text-xs font-medium rounded-md transition
                        {{ $viewMode === 'preview' ? 'bg-gray-600 text-white' : 'text-gray-400 hover:text-gray-200' }}"
                >
                    Podglad
                </button>
                <button
                    wire:click="setViewMode('code')"
                    class="px-3 py-1.5 text-xs font-medium rounded-md transition
                        {{ $viewMode === 'code' ? 'bg-gray-600 text-white' : 'text-gray-400 hover:text-gray-200' }}"
                >
                    HTML
                </button>
            </div>

            {{-- Import --}}
            <div class="relative" x-data="{ open: false }">
                <button
                    @click="open = !open"
                    class="px-3 py-2 text-sm bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Import
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div
                    x-show="open"
                    @click.outside="open = false"
                    x-transition
                    class="absolute right-0 mt-1 w-48 bg-gray-800 border border-gray-700 rounded-lg shadow-xl py-1 z-10"
                >
                    <button
                        wire:click="openImportModal('html')"
                        @click="open = false"
                        class="w-full px-4 py-2 text-left text-sm text-gray-300 hover:bg-gray-700 flex items-center gap-2"
                    >
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                        Wklej HTML
                    </button>
                    <button
                        wire:click="openImportModal('prestashop')"
                        @click="open = false"
                        class="w-full px-4 py-2 text-left text-sm text-gray-300 hover:bg-gray-700 flex items-center gap-2"
                    >
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Z PrestaShop
                    </button>
                </div>
            </div>

            {{-- Templates --}}
            <button
                wire:click="openLoadTemplateModal"
                class="px-3 py-2 text-sm bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition"
            >
                Szablony
            </button>

            {{-- Variables --}}
            <button
                wire:click="openVariableModal"
                class="px-3 py-2 text-sm bg-purple-600/20 hover:bg-purple-600/30 text-purple-400 border border-purple-500/30 rounded-lg transition flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                Zmienne
            </button>

            {{-- CSS/JS Editor --}}
            <button
                wire:click="openCssJsEditor"
                class="px-3 py-2 text-sm bg-cyan-600/20 hover:bg-cyan-600/30 text-cyan-400 border border-cyan-500/30 rounded-lg transition flex items-center gap-2"
                title="Edytuj CSS/JS sklepu"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                </svg>
                CSS/JS
            </button>

            {{-- Keyboard Shortcuts Help --}}
            <div class="relative" x-data="{ showHelp: false }">
                <button
                    @click="showHelp = !showHelp"
                    class="p-2 text-gray-400 hover:text-gray-200 hover:bg-gray-700 rounded-lg transition"
                    title="Skroty klawiszowe"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </button>
                {{-- Shortcuts Panel --}}
                <div
                    x-show="showHelp"
                    @click.outside="showHelp = false"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-0 mt-2 w-64 bg-gray-800 border border-gray-700 rounded-xl shadow-2xl p-4 z-50"
                >
                    <h3 class="text-sm font-semibold text-gray-200 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Skroty klawiszowe
                    </h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-400">Zapisz</span>
                            <kbd class="px-2 py-0.5 bg-gray-700 text-gray-300 text-xs rounded font-mono">Ctrl+S</kbd>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-400">Cofnij</span>
                            <kbd class="px-2 py-0.5 bg-gray-700 text-gray-300 text-xs rounded font-mono">Ctrl+Z</kbd>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-400">Ponow</span>
                            <kbd class="px-2 py-0.5 bg-gray-700 text-gray-300 text-xs rounded font-mono">Ctrl+Y</kbd>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-400">Zamknij modal</span>
                            <kbd class="px-2 py-0.5 bg-gray-700 text-gray-300 text-xs rounded font-mono">Escape</kbd>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-700">
                        <p class="text-xs text-gray-500">Tip: Przeciagnij bloki z palety na canvas lub kliknij aby dodac</p>
                    </div>
                </div>
            </div>

            {{-- Save --}}
            <button
                wire:click="save"
                class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Zapisz
            </button>
        </div>
    </header>

    {{-- Main Editor Area --}}
    <div class="flex-1 flex overflow-hidden">
        @if($viewMode === 'edit')
            {{-- Block Palette (Left) --}}
            @include('livewire.products.visual-description.partials.block-palette')

            {{-- Layers Panel (Between palette and canvas) --}}
            @include('livewire.products.visual-description.partials.layer-panel')

            {{-- Canvas (Center) --}}
            @include('livewire.products.visual-description.partials.block-canvas')

            {{-- Properties Panel (Right) --}}
            @include('livewire.products.visual-description.partials.property-panel')
        @elseif($viewMode === 'preview')
            {{-- Full Preview --}}
            @include('livewire.products.visual-description.partials.preview-pane', ['fullWidth' => true])
        @else
            {{-- Code View - Editable HTML --}}
            <div class="flex-1 p-4 overflow-auto flex flex-col">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-sm text-gray-400">
                        Edytuj HTML bezposrednio. Po zakonczeniu kliknij "Zastosuj zmiany".
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            wire:click="applyHtmlChanges"
                            class="px-3 py-1.5 text-xs bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition flex items-center gap-1"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Zastosuj zmiany
                        </button>
                        <button
                            wire:click="resetHtmlToBlocks"
                            class="px-3 py-1.5 text-xs bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition"
                        >
                            Resetuj
                        </button>
                    </div>
                </div>
                <textarea
                    wire:model.defer="codeViewHtml"
                    class="flex-1 w-full p-4 bg-gray-800 border border-gray-700 rounded-lg text-sm text-gray-300 font-mono resize-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="<div class='pd-hero'>...</div>"
                    spellcheck="false"
                >{{ $this->previewHtml }}</textarea>
            </div>
        @endif
    </div>

    {{-- Template Modal --}}
    @if($showTemplateModal)
        @include('livewire.products.visual-description.partials.template-modal')
    @endif

    {{-- Media Picker Modal - ETAP_07f Faza 7 --}}
    @if($showMediaPicker)
        @include('livewire.products.visual-description.partials.media-picker-modal', [
            'showModal' => $showMediaPicker,
            'fieldIndex' => $mediaPickerFieldIndex,
            'fieldName' => $mediaPickerFieldName,
            'productId' => $productId,
            'multiple' => $mediaPickerMultiple,
        ])
    @endif

    {{-- Import Modal - ETAP_07f Faza 3 --}}
    @if($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/60"
                wire:click="closeImportModal"
            ></div>

            {{-- Modal Content --}}
            <div class="relative w-full max-w-2xl bg-gray-800 rounded-xl shadow-2xl border border-gray-700 mx-4">
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-100">
                        Import opisu
                    </h3>
                    <button
                        wire:click="closeImportModal"
                        class="text-gray-400 hover:text-gray-200 transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="p-6 space-y-6">
                    {{-- Import Source Selection --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-3">Zrodlo importu</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label
                                class="flex items-center gap-3 p-4 rounded-lg border-2 cursor-pointer transition
                                    {{ $importSource === 'html' ? 'border-blue-500 bg-blue-500/10' : 'border-gray-600 bg-gray-700/50 hover:border-gray-500' }}"
                            >
                                <input
                                    type="radio"
                                    wire:model.live="importSource"
                                    value="html"
                                    class="hidden"
                                >
                                <svg class="w-6 h-6 {{ $importSource === 'html' ? 'text-blue-400' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                                </svg>
                                <div>
                                    <span class="block font-medium {{ $importSource === 'html' ? 'text-blue-300' : 'text-gray-300' }}">
                                        Wklej HTML
                                    </span>
                                    <span class="text-xs text-gray-500">Import z kodu HTML</span>
                                </div>
                            </label>

                            <label
                                class="flex items-center gap-3 p-4 rounded-lg border-2 cursor-pointer transition
                                    {{ $importSource === 'prestashop' ? 'border-blue-500 bg-blue-500/10' : 'border-gray-600 bg-gray-700/50 hover:border-gray-500' }}"
                            >
                                <input
                                    type="radio"
                                    wire:model.live="importSource"
                                    value="prestashop"
                                    class="hidden"
                                >
                                <svg class="w-6 h-6 {{ $importSource === 'prestashop' ? 'text-blue-400' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                <div>
                                    <span class="block font-medium {{ $importSource === 'prestashop' ? 'text-blue-300' : 'text-gray-300' }}">
                                        PrestaShop
                                    </span>
                                    <span class="text-xs text-gray-500">Pobierz z produktu PS</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- HTML Input (visible only for HTML source) --}}
                    @if($importSource === 'html')
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                Kod HTML
                            </label>
                            <textarea
                                wire:model="importHtml"
                                rows="10"
                                class="w-full px-4 py-3 bg-gray-900 border border-gray-600 rounded-lg text-sm text-gray-200 font-mono
                                    focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-y"
                                placeholder="<div class='pd-hero'>...</div>"
                            ></textarea>
                            <p class="mt-2 text-xs text-gray-500">
                                Parser rozpoznaje klasy: pd-hero, pd-cols, pd-heading, pd-text, pd-image, pd-video, blok-*
                            </p>
                        </div>
                    @else
                        <div class="p-4 bg-gray-700/50 rounded-lg">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div>
                                    <p class="text-sm text-gray-300">
                                        Opis zostanie pobrany bezposrednio z PrestaShop dla produktu:
                                    </p>
                                    @if($this->product)
                                        <p class="mt-1 font-medium text-gray-200">
                                            {{ $this->product->name }} (SKU: {{ $this->product->sku }})
                                        </p>
                                    @endif
                                    @if($this->shop)
                                        <p class="text-xs text-gray-400 mt-1">
                                            Sklep: {{ $this->shop->name }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Import Mode --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-3">Tryb importu</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="radio"
                                    wire:model.live="importMode"
                                    value="append"
                                    class="checkbox-enterprise"
                                >
                                <span class="text-sm text-gray-300">Dodaj do istniejacych blokow</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="radio"
                                    wire:model.live="importMode"
                                    value="replace"
                                    class="checkbox-enterprise"
                                >
                                <span class="text-sm text-gray-300">Zastap wszystkie bloki</span>
                            </label>
                        </div>
                        @if($importMode === 'replace' && count($blocks) > 0)
                            <p class="mt-2 text-xs text-amber-400">
                                Uwaga: Ta operacja usunie {{ count($blocks) }} istniejacych blokow
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-900/50 rounded-b-xl border-t border-gray-700">
                    <button
                        wire:click="closeImportModal"
                        class="px-4 py-2 text-sm text-gray-400 hover:text-gray-200 transition"
                    >
                        Anuluj
                    </button>
                    <button
                        wire:click="executeImport"
                        class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Importuj
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Variable Picker Modal - ETAP_07f Faza 4 --}}
    @include('livewire.products.visual-description.partials.variable-picker-modal')

    {{-- CSS/JS Editor Modal - ETAP_07f_P3: Full CSS/JS discovery from PrestaShop --}}
    <livewire:products.visual-description.css-js-editor-modal />

    {{-- Block Generator Modal - ETAP_07f_P3: Create dedicated blocks from prestashop-section --}}
    <livewire:products.visual-description.block-generator-modal />

    {{-- Visual Block Builder - ETAP_07f_P4: Elementor-style visual block builder --}}
    <livewire:products.visual-description.block-builder.block-builder-canvas />
</div>
