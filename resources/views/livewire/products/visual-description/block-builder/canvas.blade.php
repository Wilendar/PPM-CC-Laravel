<div>
    @if($show)
    <div
        class="fixed inset-0 z-50 overflow-hidden"
        x-data="blockBuilderCanvas({
            document: @js($document),
            selectedId: @entangle('selectedElementId'),
        })"
        x-on:keydown.ctrl.z.prevent="$wire.undo()"
        x-on:keydown.ctrl.y.prevent="$wire.redo()"
        x-on:keydown.delete.prevent="$wire.deleteElement()"
        x-on:keydown.ctrl.c.prevent="$wire.copyElement()"
        x-on:keydown.ctrl.v.prevent="$wire.pasteElement()"
        x-on:keydown.ctrl.d.prevent="$wire.duplicateElement()"
        x-on:keydown.escape.prevent="deselectElement"
    >
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-sm"></div>

        {{-- Main Container --}}
        <div class="relative h-full flex flex-col">
            {{-- Header --}}
            <header class="flex-shrink-0 h-14 bg-gray-800 border-b border-gray-700 flex items-center justify-between px-4">
                <div class="flex items-center gap-4">
                    <button
                        wire:click="close"
                        class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-400">Visual Block Builder</span>
                        <span class="text-gray-600">|</span>
                        <input
                            type="text"
                            wire:model.blur="blockName"
                            placeholder="Nazwa bloku..."
                            class="bg-transparent border-none text-white text-sm focus:outline-none focus:ring-0 w-48"
                        >
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    {{-- Undo/Redo --}}
                    <div class="flex items-center gap-1 mr-4">
                        <button
                            wire:click="undo"
                            @if(!$this->canUndo) disabled @endif
                            class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                            title="Cofnij (Ctrl+Z)"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                            </svg>
                        </button>
                        <button
                            wire:click="redo"
                            @if(!$this->canRedo) disabled @endif
                            class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                            title="Ponow (Ctrl+Y)"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10h-10a8 8 0 00-8 8v2M21 10l-6 6m6-6l-6-6"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Import HTML --}}
                    <div x-data="{ showImportModal: false, importHtml: '' }">
                        <button
                            @click="showImportModal = true"
                            class="px-3 py-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors flex items-center gap-2"
                            title="Importuj HTML"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            <span class="text-sm">Import</span>
                        </button>

                        {{-- Import HTML Modal --}}
                        <div
                            x-show="showImportModal"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="fixed inset-0 z-[60] flex items-center justify-center"
                            @keydown.escape.window="showImportModal = false"
                        >
                            <div class="absolute inset-0 bg-black/50" @click="showImportModal = false"></div>
                            <div class="relative bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl mx-4 border border-gray-700">
                                <div class="flex items-center justify-between p-4 border-b border-gray-700">
                                    <h3 class="text-lg font-medium text-white">Importuj HTML</h3>
                                    <button @click="showImportModal = false" class="text-gray-400 hover:text-white">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="p-4">
                                    <p class="text-sm text-gray-400 mb-3">Wklej kod HTML z PrestaShop aby przekonwertowac go na edytowalne elementy.</p>
                                    <textarea
                                        x-model="importHtml"
                                        rows="12"
                                        placeholder="<div class='pd-merits'>...</div>"
                                        class="w-full bg-gray-900 border border-gray-700 rounded-lg p-3 text-sm text-gray-200 font-mono focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                    ></textarea>
                                </div>
                                <div class="flex justify-end gap-3 p-4 border-t border-gray-700">
                                    <button
                                        @click="showImportModal = false"
                                        class="px-4 py-2 text-gray-400 hover:text-white transition-colors"
                                    >
                                        Anuluj
                                    </button>
                                    <button
                                        @click="if(importHtml.trim()) { $wire.importHtmlContent(importHtml); showImportModal = false; importHtml = ''; }"
                                        class="px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white text-sm font-medium rounded-lg transition-all"
                                    >
                                        Importuj
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Export HTML --}}
                    <div x-data="{ showExportModal: false, exportedHtml: '', minified: false }"
                         x-on:copy-to-clipboard.window="navigator.clipboard.writeText($event.detail.html)">
                        <button
                            @click="showExportModal = true; $wire.getExportedHtml(minified).then(html => exportedHtml = html)"
                            class="px-3 py-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors flex items-center gap-2"
                            title="Eksportuj HTML"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <span class="text-sm">Export</span>
                        </button>

                        {{-- Export HTML Modal --}}
                        <div
                            x-show="showExportModal"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="fixed inset-0 z-[60] flex items-center justify-center"
                            @keydown.escape.window="showExportModal = false"
                        >
                            <div class="absolute inset-0 bg-black/50" @click="showExportModal = false"></div>
                            <div class="relative bg-gray-800 rounded-xl shadow-2xl w-full max-w-3xl mx-4 border border-gray-700">
                                <div class="flex items-center justify-between p-4 border-b border-gray-700">
                                    <h3 class="text-lg font-medium text-white">Eksportuj HTML</h3>
                                    <button @click="showExportModal = false" class="text-gray-400 hover:text-white">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <p class="text-sm text-gray-400">Skopiuj wygenerowany kod HTML do PrestaShop.</p>
                                        <label class="flex items-center gap-2 text-sm text-gray-400">
                                            <input
                                                type="checkbox"
                                                x-model="minified"
                                                @change="$wire.getExportedHtml(minified).then(html => exportedHtml = html)"
                                                class="rounded border-gray-600 bg-gray-700 text-amber-500 focus:ring-amber-500"
                                            >
                                            Minifikuj
                                        </label>
                                    </div>
                                    <div class="relative">
                                        <textarea
                                            x-model="exportedHtml"
                                            readonly
                                            rows="14"
                                            class="w-full bg-gray-900 border border-gray-700 rounded-lg p-3 text-sm text-gray-200 font-mono focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                        ></textarea>
                                        <button
                                            @click="navigator.clipboard.writeText(exportedHtml); $dispatch('notify', {type: 'success', message: 'Skopiowano do schowka'})"
                                            class="absolute top-2 right-2 p-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-gray-300 hover:text-white transition-colors"
                                            title="Kopiuj do schowka"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex justify-end gap-3 p-4 border-t border-gray-700">
                                    <button
                                        @click="showExportModal = false"
                                        class="px-4 py-2 text-gray-400 hover:text-white transition-colors"
                                    >
                                        Zamknij
                                    </button>
                                    <button
                                        @click="navigator.clipboard.writeText(exportedHtml); $dispatch('notify', {type: 'success', message: 'Skopiowano do schowka'})"
                                        class="px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white text-sm font-medium rounded-lg transition-all flex items-center gap-2"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                        Kopiuj HTML
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Apply to Editor (visible only when opened from VE for inline editing) --}}
                    @if(!$definitionId)
                    <button
                        wire:click="applyToEditor"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium rounded-lg transition-all flex items-center gap-2"
                        title="Zastosuj zmiany do edytora wizualnego"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Zastosuj
                    </button>
                    @endif

                    {{-- Save as Block (always visible) --}}
                    <button
                        wire:click="save"
                        class="px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white text-sm font-medium rounded-lg transition-all flex items-center gap-2"
                        title="Zapisz jako dedykowany blok sklepu"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        Zapisz blok
                    </button>
                </div>
            </header>

            {{-- Main Content --}}
            <div class="flex-1 flex overflow-hidden">
                {{-- Left Panel - Element Palette --}}
                <aside class="w-64 bg-gray-800 border-r border-gray-700 flex flex-col overflow-hidden">
                    <div class="p-3 border-b border-gray-700">
                        <h3 class="text-sm font-medium text-white">Elementy</h3>
                    </div>

                    <div class="flex-1 overflow-y-auto p-3 space-y-4">
                        {{-- Content Elements --}}
                        <div>
                            <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-2">Tresc</h4>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    wire:click="addElement('heading')"
                                    class="flex flex-col items-center gap-1 p-3 bg-gray-700/50 hover:bg-gray-700 rounded-lg text-gray-300 hover:text-white transition-colors"
                                    draggable="true"
                                    x-on:dragstart="startDrag($event, 'heading')"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>
                                    </svg>
                                    <span class="text-xs">Naglowek</span>
                                </button>

                                <button
                                    wire:click="addElement('text')"
                                    class="flex flex-col items-center gap-1 p-3 bg-gray-700/50 hover:bg-gray-700 rounded-lg text-gray-300 hover:text-white transition-colors"
                                    draggable="true"
                                    x-on:dragstart="startDrag($event, 'text')"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                    </svg>
                                    <span class="text-xs">Tekst</span>
                                </button>

                                <button
                                    wire:click="addElement('image')"
                                    class="flex flex-col items-center gap-1 p-3 bg-gray-700/50 hover:bg-gray-700 rounded-lg text-gray-300 hover:text-white transition-colors"
                                    draggable="true"
                                    x-on:dragstart="startDrag($event, 'image')"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="text-xs">Obraz</span>
                                </button>

                                <button
                                    wire:click="addElement('icon')"
                                    class="flex flex-col items-center gap-1 p-3 bg-gray-700/50 hover:bg-gray-700 rounded-lg text-gray-300 hover:text-white transition-colors"
                                    draggable="true"
                                    x-on:dragstart="startDrag($event, 'icon')"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                    <span class="text-xs">Ikona</span>
                                </button>

                                <button
                                    wire:click="addElement('button')"
                                    class="flex flex-col items-center gap-1 p-3 bg-gray-700/50 hover:bg-gray-700 rounded-lg text-gray-300 hover:text-white transition-colors"
                                    draggable="true"
                                    x-on:dragstart="startDrag($event, 'button')"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                                    </svg>
                                    <span class="text-xs">Przycisk</span>
                                </button>

                                <button
                                    wire:click="addElement('separator')"
                                    class="flex flex-col items-center gap-1 p-3 bg-gray-700/50 hover:bg-gray-700 rounded-lg text-gray-300 hover:text-white transition-colors"
                                    draggable="true"
                                    x-on:dragstart="startDrag($event, 'separator')"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                    </svg>
                                    <span class="text-xs">Separator</span>
                                </button>
                            </div>
                        </div>

                        {{-- Layout Elements --}}
                        <div>
                            <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-2">Layout</h4>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    wire:click="addElement('container')"
                                    class="flex flex-col items-center gap-1 p-3 bg-gray-700/50 hover:bg-gray-700 rounded-lg text-gray-300 hover:text-white transition-colors"
                                    draggable="true"
                                    x-on:dragstart="startDrag($event, 'container')"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                                    </svg>
                                    <span class="text-xs">Kontener</span>
                                </button>

                                <button
                                    wire:click="addElement('row')"
                                    class="flex flex-col items-center gap-1 p-3 bg-gray-700/50 hover:bg-gray-700 rounded-lg text-gray-300 hover:text-white transition-colors"
                                    draggable="true"
                                    x-on:dragstart="startDrag($event, 'row')"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>
                                    </svg>
                                    <span class="text-xs">Wiersz</span>
                                </button>

                                <button
                                    wire:click="addElement('column')"
                                    class="flex flex-col items-center gap-1 p-3 bg-gray-700/50 hover:bg-gray-700 rounded-lg text-gray-300 hover:text-white transition-colors"
                                    draggable="true"
                                    x-on:dragstart="startDrag($event, 'column')"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="text-xs">Kolumna</span>
                                </button>

                                <button
                                    wire:click="addElement('grid')"
                                    class="flex flex-col items-center gap-1 p-3 bg-gray-700/50 hover:bg-gray-700 rounded-lg text-gray-300 hover:text-white transition-colors"
                                    draggable="true"
                                    x-on:dragstart="startDrag($event, 'grid')"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                    </svg>
                                    <span class="text-xs">Siatka</span>
                                </button>

                                <button
                                    wire:click="addElement('background')"
                                    class="flex flex-col items-center gap-1 p-3 bg-gray-700/50 hover:bg-gray-700 rounded-lg text-gray-300 hover:text-white transition-colors"
                                    draggable="true"
                                    x-on:dragstart="startDrag($event, 'background')"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="text-xs">Tlo z obrazem</span>
                                </button>

                                <button
                                    wire:click="addElement('repeater')"
                                    class="flex flex-col items-center gap-1 p-3 bg-gray-700/50 hover:bg-gray-700 rounded-lg text-gray-300 hover:text-white transition-colors"
                                    draggable="true"
                                    x-on:dragstart="startDrag($event, 'repeater')"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                    </svg>
                                    <span class="text-xs">Repeater</span>
                                </button>

                                <button
                                    wire:click="addElement('slide')"
                                    class="flex flex-col items-center gap-1 p-3 bg-gray-700/50 hover:bg-gray-700 rounded-lg text-gray-300 hover:text-white transition-colors"
                                    draggable="true"
                                    x-on:dragstart="startDrag($event, 'slide')"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                                    </svg>
                                    <span class="text-xs">Slajd</span>
                                </button>
                            </div>
                        </div>

                        {{-- PrestaShop Templates --}}
                        <div>
                            <h4 class="text-xs font-medium text-amber-400 uppercase tracking-wider mb-2 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                Szablony PrestaShop
                            </h4>
                            <div class="space-y-2">
                                {{-- pd-intro --}}
                                <button
                                    wire:click="addTemplate('pd-intro')"
                                    class="w-full flex items-center gap-3 p-2 bg-amber-900/20 hover:bg-amber-900/40 border border-amber-700/30 rounded-lg text-gray-300 hover:text-amber-400 transition-colors"
                                >
                                    <div class="flex-shrink-0 w-8 h-8 bg-amber-800/40 rounded flex items-center justify-center">
                                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <span class="text-xs font-medium block">Intro</span>
                                        <span class="text-[10px] text-gray-500">Naglowek z obrazem</span>
                                    </div>
                                </button>

                                {{-- pd-merits --}}
                                <button
                                    wire:click="addTemplate('pd-merits')"
                                    class="w-full flex items-center gap-3 p-2 bg-amber-900/20 hover:bg-amber-900/40 border border-amber-700/30 rounded-lg text-gray-300 hover:text-amber-400 transition-colors"
                                >
                                    <div class="flex-shrink-0 w-8 h-8 bg-amber-800/40 rounded flex items-center justify-center">
                                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <span class="text-xs font-medium block">Zalety</span>
                                        <span class="text-[10px] text-gray-500">Karty z ikonami</span>
                                    </div>
                                </button>

                                {{-- pd-specification --}}
                                <button
                                    wire:click="addTemplate('pd-specification')"
                                    class="w-full flex items-center gap-3 p-2 bg-amber-900/20 hover:bg-amber-900/40 border border-amber-700/30 rounded-lg text-gray-300 hover:text-amber-400 transition-colors"
                                >
                                    <div class="flex-shrink-0 w-8 h-8 bg-amber-800/40 rounded flex items-center justify-center">
                                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <span class="text-xs font-medium block">Specyfikacja</span>
                                        <span class="text-[10px] text-gray-500">Tabela danych</span>
                                    </div>
                                </button>

                                {{-- pd-features --}}
                                <button
                                    wire:click="addTemplate('pd-features')"
                                    class="w-full flex items-center gap-3 p-2 bg-amber-900/20 hover:bg-amber-900/40 border border-amber-700/30 rounded-lg text-gray-300 hover:text-amber-400 transition-colors"
                                >
                                    <div class="flex-shrink-0 w-8 h-8 bg-amber-800/40 rounded flex items-center justify-center">
                                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <span class="text-xs font-medium block">Lista cech</span>
                                        <span class="text-[10px] text-gray-500">Checkmarki z tekstem</span>
                                    </div>
                                </button>

                                {{-- pd-slider (Splide carousel) --}}
                                <button
                                    wire:click="addTemplate('pd-slider')"
                                    class="w-full flex items-center gap-3 p-2 bg-amber-900/20 hover:bg-amber-900/40 border border-amber-700/30 rounded-lg text-gray-300 hover:text-amber-400 transition-colors"
                                >
                                    <div class="flex-shrink-0 w-8 h-8 bg-amber-800/40 rounded flex items-center justify-center">
                                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <span class="text-xs font-medium block">Slider</span>
                                        <span class="text-[10px] text-gray-500">Karuzela Splide.js</span>
                                    </div>
                                </button>

                                {{-- pd-parallax (fullwidth parallax) --}}
                                <button
                                    wire:click="addTemplate('pd-parallax')"
                                    class="w-full flex items-center gap-3 p-2 bg-amber-900/20 hover:bg-amber-900/40 border border-amber-700/30 rounded-lg text-gray-300 hover:text-amber-400 transition-colors"
                                >
                                    <div class="flex-shrink-0 w-8 h-8 bg-amber-800/40 rounded flex items-center justify-center">
                                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <span class="text-xs font-medium block">Parallax</span>
                                        <span class="text-[10px] text-gray-500">Fullwidth z efektem</span>
                                    </div>
                                </button>

                                {{-- pd-asset-list (product parameters) --}}
                                <button
                                    wire:click="addTemplate('pd-asset-list')"
                                    class="w-full flex items-center gap-3 p-2 bg-amber-900/20 hover:bg-amber-900/40 border border-amber-700/30 rounded-lg text-gray-300 hover:text-amber-400 transition-colors"
                                >
                                    <div class="flex-shrink-0 w-8 h-8 bg-amber-800/40 rounded flex items-center justify-center">
                                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <span class="text-xs font-medium block">Parametry</span>
                                        <span class="text-[10px] text-gray-500">Ikony + wartosci</span>
                                    </div>
                                </button>

                                {{-- pd-cover (hero/cover image) --}}
                                <button
                                    wire:click="addTemplate('pd-cover')"
                                    class="w-full flex items-center gap-3 p-2 bg-amber-900/20 hover:bg-amber-900/40 border border-amber-700/30 rounded-lg text-gray-300 hover:text-amber-400 transition-colors"
                                >
                                    <div class="flex-shrink-0 w-8 h-8 bg-amber-800/40 rounded flex items-center justify-center">
                                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <span class="text-xs font-medium block">Cover</span>
                                        <span class="text-[10px] text-gray-500">Hero sekcja</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </aside>

                {{-- Center - Canvas --}}
                <main class="flex-1 bg-gray-900 overflow-auto flex items-start justify-center p-4">
                    <div
                        class="w-full max-w-7xl bg-white rounded-lg shadow-2xl min-h-[600px] relative overflow-hidden"
                        x-on:dragover.prevent="handleDragOver($event)"
                        x-on:drop.prevent="handleDrop($event)"
                    >
                        {{-- Canvas Toolbar --}}
                        <div class="absolute -top-10 left-0 right-0 flex items-center justify-center gap-2">
                            <span class="text-xs text-gray-400">Podglad bloku</span>
                        </div>

                        {{-- PrestaShop CSS injection - styles are loaded from shop configuration --}}
                        <style>
                            {!! $this->previewCss !!}
                        </style>

                        {{-- Render Document Tree with PrestaShop-compatible structure --}}
                        <div class="p-4 vbb-canvas-preview">
                            <div class="product-description">
                                <div class="rte-content">
                                    @include('livewire.products.visual-description.block-builder.partials.element-renderer', ['element' => $document['root'] ?? []])
                                </div>
                            </div>
                        </div>
                    </div>
                </main>

                {{-- Right Panel - Properties/Layers --}}
                <aside class="w-72 bg-gray-800 border-l border-gray-700 flex flex-col overflow-hidden">
                    {{-- Panel Tabs --}}
                    <div class="flex border-b border-gray-700">
                        <button
                            wire:click="$set('activePanel', 'properties')"
                            @class([
                                'flex-1 py-3 text-sm font-medium transition-colors',
                                'text-amber-400 border-b-2 border-amber-400' => $activePanel === 'properties',
                                'text-gray-400 hover:text-white' => $activePanel !== 'properties',
                            ])
                        >
                            Wlasciwosci
                        </button>
                        <button
                            wire:click="$set('activePanel', 'layers')"
                            @class([
                                'flex-1 py-3 text-sm font-medium transition-colors',
                                'text-amber-400 border-b-2 border-amber-400' => $activePanel === 'layers',
                                'text-gray-400 hover:text-white' => $activePanel !== 'layers',
                            ])
                        >
                            Warstwy
                        </button>
                    </div>

                    {{-- Panel Content --}}
                    <div class="flex-1 overflow-y-auto">
                        @if($activePanel === 'properties')
                            @include('livewire.products.visual-description.block-builder.partials.property-panel')
                        @else
                            @include('livewire.products.visual-description.block-builder.partials.layer-panel')
                        @endif
                    </div>
                </aside>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    function blockBuilderCanvas(config) {
        return {
            document: config.document,
            selectedId: config.selectedId,

            // Drag & Drop state
            draggedType: null,           // Type from palette (new element)
            draggedElementId: null,      // ID of existing element being moved
            dropTargetId: null,          // Container ID where drop will occur
            dropPosition: -1,            // Position within container (-1 = end)
            isDraggingExisting: false,   // True when dragging existing element

            init() {
                // Keyboard shortcuts are handled by Alpine x-on directives
            },

            // ==========================================
            // PALETTE DRAG (new element from palette)
            // ==========================================
            startDrag(event, type) {
                this.draggedType = type;
                this.isDraggingExisting = false;
                event.dataTransfer.setData('text/plain', JSON.stringify({ type: type, isNew: true }));
                event.dataTransfer.effectAllowed = 'copy';

                // Add dragging class for visual feedback
                event.target.classList.add('opacity-50');
            },

            // ==========================================
            // EXISTING ELEMENT DRAG (move/reorder)
            // ==========================================
            handleElementDragStart(event, elementId, elementType) {
                // Don't drag if clicking on action buttons
                if (event.target.closest('button')) {
                    event.preventDefault();
                    return;
                }

                this.draggedElementId = elementId;
                this.draggedType = elementType;
                this.isDraggingExisting = true;
                event.dataTransfer.setData('text/plain', JSON.stringify({
                    elementId: elementId,
                    type: elementType,
                    isNew: false
                }));
                event.dataTransfer.effectAllowed = 'move';

                // Add visual feedback
                event.target.classList.add('opacity-40', 'ring-2', 'ring-amber-400');

                // Prevent parent containers from also starting drag
                event.stopPropagation();
            },

            handleElementDragEnd(event) {
                // Reset visual state
                event.target.classList.remove('opacity-40', 'opacity-50', 'ring-2', 'ring-amber-400');
                this.resetDragState();
            },

            // ==========================================
            // CONTAINER DROP HANDLING
            // ==========================================
            handleContainerDragOver(event, containerId) {
                event.preventDefault();

                // Don't allow dropping element into itself
                if (this.isDraggingExisting && this.draggedElementId === containerId) {
                    event.dataTransfer.dropEffect = 'none';
                    return;
                }

                // Check if trying to drop into a descendant (prevent circular reference)
                const containerEl = document.querySelector(`[data-element-id="${containerId}"]`);
                const draggedEl = document.querySelector(`[data-element-id="${this.draggedElementId}"]`);
                if (draggedEl && containerEl && draggedEl.contains(containerEl)) {
                    event.dataTransfer.dropEffect = 'none';
                    return;
                }

                this.dropTargetId = containerId;
                event.dataTransfer.dropEffect = this.isDraggingExisting ? 'move' : 'copy';
            },

            handleContainerDragLeave(event, containerId) {
                // Only clear if we're actually leaving the container (not entering a child)
                const relatedTarget = event.relatedTarget;
                const container = event.currentTarget;

                if (!relatedTarget || !container.contains(relatedTarget)) {
                    if (this.dropTargetId === containerId) {
                        this.dropTargetId = null;
                        this.dropPosition = -1;
                    }
                }
            },

            handleContainerDrop(event, containerId) {
                event.preventDefault();
                event.stopPropagation();

                try {
                    const data = JSON.parse(event.dataTransfer.getData('text/plain'));

                    if (data.isNew) {
                        // New element from palette -> add to specific container
                        this.$wire.addElementToContainer(data.type, containerId, this.dropPosition);
                    } else {
                        // Existing element -> move to container
                        if (data.elementId !== containerId) {
                            this.$wire.moveElementToContainer(data.elementId, containerId, this.dropPosition);
                        }
                    }
                } catch (e) {
                    // Fallback: try plain text type
                    const type = event.dataTransfer.getData('text/plain');
                    if (type && !type.startsWith('{')) {
                        this.$wire.addElementToContainer(type, containerId, this.dropPosition);
                    }
                }

                this.resetDragState();
            },

            // ==========================================
            // CANVAS DROP (root level)
            // ==========================================
            handleDragOver(event) {
                event.preventDefault();
                event.dataTransfer.dropEffect = this.isDraggingExisting ? 'move' : 'copy';
            },

            handleDrop(event) {
                event.preventDefault();

                try {
                    const data = JSON.parse(event.dataTransfer.getData('text/plain'));

                    if (data.isNew) {
                        // New element from palette -> add to root
                        this.$wire.addElement(data.type);
                    } else {
                        // Existing element -> move to root
                        this.$wire.moveElementToContainer(data.elementId, 'root', -1);
                    }
                } catch (e) {
                    // Fallback: try plain text type
                    const type = event.dataTransfer.getData('text/plain');
                    if (type && !type.startsWith('{')) {
                        this.$wire.addElement(type);
                    }
                }

                this.resetDragState();
            },

            // ==========================================
            // DROP POSITION (for ordering)
            // ==========================================
            setDropPosition(position) {
                this.dropPosition = position;
            },

            // ==========================================
            // STATE MANAGEMENT
            // ==========================================
            resetDragState() {
                this.draggedType = null;
                this.draggedElementId = null;
                this.dropTargetId = null;
                this.dropPosition = -1;
                this.isDraggingExisting = false;
            },

            selectElement(elementId) {
                this.$wire.selectElement(elementId);
            },

            deselectElement() {
                this.$wire.selectElement(null);
            },
        };
    }

    /**
     * WYSIWYG Editor Alpine.js Component
     * Rich text editing with toolbar for Block Builder
     */
    function wysiwygEditor(config) {
        return {
            content: config.content || '',
            elementId: config.elementId,
            isButton: config.isButton || false,
            htmlMode: false,
            activeFormats: {},
            debounceTimer: null,

            init() {
                // Set initial content to editor
                this.$nextTick(() => {
                    if (this.$refs.editor) {
                        this.$refs.editor.innerHTML = this.content;
                    }
                });

                // Update active formats on selection change
                document.addEventListener('selectionchange', () => {
                    this.updateActiveFormats();
                });
            },

            // Execute document command for formatting
            execCommand(command, value = null) {
                // Focus editor first
                if (this.$refs.editor) {
                    this.$refs.editor.focus();
                }
                document.execCommand(command, false, value);
                this.updateActiveFormats();
                this.onInput();
            },

            // Check if format is currently active
            isActive(command) {
                try {
                    return document.queryCommandState(command);
                } catch (e) {
                    return false;
                }
            },

            // Update active format states
            updateActiveFormats() {
                this.activeFormats = {
                    bold: this.isActive('bold'),
                    italic: this.isActive('italic'),
                    underline: this.isActive('underline'),
                    strikeThrough: this.isActive('strikeThrough'),
                };
            },

            // Insert link with prompt
            insertLink() {
                const selection = window.getSelection();
                const selectedText = selection.toString();

                if (!selectedText && !this.isActive('createLink')) {
                    // No text selected - prompt for link text too
                    const text = prompt('Tekst linku:', '');
                    if (text === null) return;

                    const url = prompt('Adres URL:', 'https://');
                    if (url === null || url === '' || url === 'https://') return;

                    // Insert text with link
                    const linkHtml = `<a href="${url}" target="_blank">${text}</a>`;
                    document.execCommand('insertHTML', false, linkHtml);
                } else {
                    // Text selected - just ask for URL
                    const url = prompt('Adres URL:', 'https://');
                    if (url === null || url === '' || url === 'https://') return;

                    document.execCommand('createLink', false, url);

                    // Add target="_blank" to the link
                    const links = this.$refs.editor.querySelectorAll('a');
                    links.forEach(link => {
                        if (link.href === url) {
                            link.setAttribute('target', '_blank');
                        }
                    });
                }

                this.onInput();
            },

            // Toggle HTML source mode
            toggleHtmlMode() {
                if (this.htmlMode) {
                    // Switching from HTML to WYSIWYG
                    this.$nextTick(() => {
                        if (this.$refs.editor) {
                            this.$refs.editor.innerHTML = this.content;
                        }
                    });
                } else {
                    // Switching from WYSIWYG to HTML
                    if (this.$refs.editor) {
                        this.content = this.$refs.editor.innerHTML;
                    }
                }
                this.htmlMode = !this.htmlMode;
            },

            // Handle input changes with debounce
            onInput(event) {
                if (this.$refs.editor) {
                    this.content = this.$refs.editor.innerHTML;
                }

                // Debounce sync to Livewire
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => {
                    this.syncContent();
                }, 500);
            },

            // Sync content to Livewire
            syncContent() {
                if (this.$refs.editor && !this.htmlMode) {
                    this.content = this.$refs.editor.innerHTML;
                }

                // Clean up content before sending
                let cleanContent = this.cleanHtml(this.content);

                // Update Livewire component
                this.$wire.updateElementProperty(this.elementId, 'content', cleanContent);
            },

            // Clean HTML - remove empty tags, normalize whitespace
            cleanHtml(html) {
                if (!html) return '';

                // Remove empty paragraph tags
                html = html.replace(/<p><br><\/p>/gi, '');
                html = html.replace(/<p>\s*<\/p>/gi, '');

                // Remove multiple br tags
                html = html.replace(/(<br\s*\/?>\s*){3,}/gi, '<br><br>');

                // Trim whitespace
                html = html.trim();

                // If content is just whitespace or empty divs, return empty
                if (html === '<br>' || html === '<div><br></div>') {
                    return '';
                }

                return html;
            },

            // Get plain text content (for character count)
            getPlainText() {
                if (this.$refs.editor) {
                    return this.$refs.editor.innerText || this.$refs.editor.textContent || '';
                }
                // Create temp element to extract text from HTML
                const temp = document.createElement('div');
                temp.innerHTML = this.content;
                return temp.innerText || temp.textContent || '';
            },

            // Check if content has HTML formatting
            hasHtml() {
                const plainText = this.getPlainText();
                const htmlLength = this.content.length;
                // If HTML is longer than plain text by more than 10 chars, it has formatting
                return htmlLength > plainText.length + 10;
            },
        };
    }

    /**
     * Size Picker Alpine.js Component
     * Value + Unit selector for CSS dimensions
     */
    function sizePicker(config) {
        return {
            value: config.value || '',
            property: config.property,
            elementId: config.elementId,
            numValue: '',
            unit: 'px',

            init() {
                this.parseValue(this.value);
            },

            parseValue(val) {
                if (!val || val === 'auto' || val === 'none') {
                    this.numValue = val || '';
                    this.unit = 'px';
                    return;
                }

                // Extract number and unit from value like "100px", "50%", "2rem"
                const match = String(val).match(/^([\d.]+)(px|%|rem|em|vh|vw)?$/);
                if (match) {
                    this.numValue = match[1];
                    this.unit = match[2] || 'px';
                } else {
                    this.numValue = val;
                    this.unit = 'px';
                }
            },

            updateValue() {
                let finalValue = '';

                if (this.numValue === '' || this.numValue === 'auto' || this.numValue === 'none') {
                    finalValue = this.numValue;
                } else if (!isNaN(parseFloat(this.numValue))) {
                    finalValue = this.numValue + this.unit;
                } else {
                    finalValue = this.numValue;
                }

                this.$wire.updateElementProperty(this.elementId, this.property, finalValue);
            }
        };
    }
</script>
@endpush
