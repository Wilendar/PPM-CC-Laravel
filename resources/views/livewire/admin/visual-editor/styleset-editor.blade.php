<div class="p-6" x-data="{ showPreview: @entangle('showPreview'), showCustomCss: @entangle('showCustomCss') }">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-100">Edytor Styleset</h1>
            <p class="text-sm text-gray-400">Edycja zmiennych CSS dla sklepu</p>
        </div>
        <div class="flex items-center gap-3">
            @if($isDirty)
                <span class="px-2 py-1 text-xs bg-amber-500/20 text-amber-400 rounded">Niezapisane zmiany</span>
            @endif
            @if($selectedShopId)
                <button
                    wire:click="openImportModal"
                    class="px-3 py-2 text-sm bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition flex items-center gap-2"
                    title="Import JSON"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Import
                </button>
                <button
                    wire:click="exportStyleset"
                    class="px-3 py-2 text-sm bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition flex items-center gap-2"
                    title="Export JSON"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export
                </button>
            @endif
            <button
                wire:click="resetToDefaults"
                class="px-4 py-2 text-sm bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition"
            >
                Resetuj
            </button>
            <button
                wire:click="save"
                class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition flex items-center gap-2"
                @if(!empty($validationErrors)) disabled @endif
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Zapisz
            </button>
        </div>
    </div>

    {{-- Shop Selector --}}
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-300 mb-2">Sklep</label>
        <select
            wire:model.live="selectedShopId"
            wire:change="loadShopStyleset($event.target.value)"
            class="w-full md:w-64 px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        >
            <option value="">-- Wybierz sklep --</option>
            @foreach($this->shops as $shop)
                <option value="{{ $shop->id }}">{{ $shop->name }}</option>
            @endforeach
        </select>
    </div>

    @if($selectedShopId)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Panel: Variables Editor --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Styleset Info --}}
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Nazwa stylesetu</label>
                            <input
                                type="text"
                                wire:model.blur="stylesetName"
                                class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-100 focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Namespace CSS</label>
                            <input
                                type="text"
                                wire:model.blur="cssNamespace"
                                class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-100 focus:ring-2 focus:ring-blue-500"
                                placeholder="pd"
                            >
                        </div>
                    </div>
                </div>

                {{-- Variable Groups Tabs --}}
                <div class="bg-gray-800 border border-gray-700 rounded-lg">
                    <div class="flex border-b border-gray-700 overflow-x-auto">
                        @foreach(array_keys($editorGroups) as $group)
                            <button
                                wire:click="setActiveGroup('{{ $group }}')"
                                class="px-4 py-3 text-sm font-medium whitespace-nowrap transition
                                    {{ $activeGroup === $group ? 'text-blue-400 border-b-2 border-blue-400 bg-gray-700/50' : 'text-gray-400 hover:text-gray-200' }}"
                            >
                                {{ $group }}
                            </button>
                        @endforeach
                        <button
                            @click="showCustomCss = !showCustomCss"
                            class="px-4 py-3 text-sm font-medium whitespace-nowrap transition
                                {{ $showCustomCss ? 'text-blue-400 border-b-2 border-blue-400 bg-gray-700/50' : 'text-gray-400 hover:text-gray-200' }}"
                        >
                            Custom CSS
                        </button>
                    </div>

                    <div class="p-4">
                        {{-- Variables Grid --}}
                        @if(!$showCustomCss && isset($editorGroups[$activeGroup]))
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($editorGroups[$activeGroup] as $field)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">
                                            {{ $field['label'] }}
                                        </label>
                                        <div class="flex items-center gap-2">
                                            @if($field['type'] === 'color')
                                                <input
                                                    type="color"
                                                    value="{{ $variables[$field['name']] ?? '#ffffff' }}"
                                                    wire:change="updateVariable('{{ $field['name'] }}', $event.target.value)"
                                                    class="w-10 h-10 rounded border border-gray-700 cursor-pointer"
                                                >
                                                <input
                                                    type="text"
                                                    value="{{ $variables[$field['name']] ?? '' }}"
                                                    wire:change="updateVariable('{{ $field['name'] }}', $event.target.value)"
                                                    class="flex-1 px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-100 text-sm focus:ring-2 focus:ring-blue-500"
                                                >
                                            @elseif($field['type'] === 'font')
                                                <select
                                                    wire:change="updateVariable('{{ $field['name'] }}', $event.target.value)"
                                                    class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-100 text-sm focus:ring-2 focus:ring-blue-500"
                                                >
                                                    <option value="system-ui, -apple-system, sans-serif" {{ ($variables[$field['name']] ?? '') === 'system-ui, -apple-system, sans-serif' ? 'selected' : '' }}>System UI</option>
                                                    <option value="Arial, sans-serif" {{ ($variables[$field['name']] ?? '') === 'Arial, sans-serif' ? 'selected' : '' }}>Arial</option>
                                                    <option value="'Roboto', sans-serif" {{ str_contains($variables[$field['name']] ?? '', 'Roboto') ? 'selected' : '' }}>Roboto</option>
                                                    <option value="'Oswald', sans-serif" {{ str_contains($variables[$field['name']] ?? '', 'Oswald') ? 'selected' : '' }}>Oswald</option>
                                                    <option value="'Playfair Display', serif" {{ str_contains($variables[$field['name']] ?? '', 'Playfair') ? 'selected' : '' }}>Playfair Display</option>
                                                    <option value="inherit" {{ ($variables[$field['name']] ?? '') === 'inherit' ? 'selected' : '' }}>Inherit</option>
                                                </select>
                                            @else
                                                <input
                                                    type="text"
                                                    value="{{ $variables[$field['name']] ?? '' }}"
                                                    wire:change="updateVariable('{{ $field['name'] }}', $event.target.value)"
                                                    class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-100 text-sm focus:ring-2 focus:ring-blue-500"
                                                    placeholder="{{ $field['type'] === 'size' ? '1rem' : '' }}"
                                                >
                                            @endif
                                        </div>
                                        @if(isset($validationErrors[$field['name']]))
                                            <p class="mt-1 text-xs text-red-400">{{ $validationErrors[$field['name']] }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Custom CSS Editor --}}
                        @if($showCustomCss)
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">
                                    Custom CSS
                                    <span class="text-gray-500 font-normal">(dodatkowe style)</span>
                                </label>
                                <textarea
                                    wire:model.blur="customCss"
                                    wire:change="updateCustomCss($event.target.value)"
                                    rows="15"
                                    class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-100 font-mono text-sm focus:ring-2 focus:ring-blue-500"
                                    placeholder="/* Dodatkowe style CSS */"
                                ></textarea>
                                @if(!empty($validationWarnings))
                                    <div class="mt-2">
                                        @foreach($validationWarnings as $warning)
                                            <p class="text-xs text-amber-400">{{ $warning }}</p>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Right Panel: Preview --}}
            <div class="lg:col-span-1">
                <div class="bg-gray-800 border border-gray-700 rounded-lg sticky top-6">
                    <div class="flex items-center justify-between p-4 border-b border-gray-700">
                        <h3 class="font-medium text-gray-100">Podglad</h3>
                        <button
                            @click="showPreview = !showPreview"
                            class="text-gray-400 hover:text-gray-200"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>

                    <div x-show="showPreview" class="p-4" x-transition>
                        {{-- Preview Box --}}
                        <div class="border border-gray-700 rounded-lg overflow-hidden">
                            <style>{{ $this->previewCss }}</style>
                            <div class="{{ $cssNamespace }}-wrapper bg-white p-4">
                                {{-- Hero Preview --}}
                                <div class="{{ $cssNamespace }}-hero mb-4" style="padding: 1rem;">
                                    <h2 class="{{ $cssNamespace }}-hero__title" style="font-size: 1.5rem; margin-bottom: 0.5rem;">
                                        Naglowek Hero
                                    </h2>
                                    <p class="{{ $cssNamespace }}-hero__subtitle">
                                        Opis produktu lub sekcji
                                    </p>
                                </div>

                                {{-- Feature Card Preview --}}
                                <div class="{{ $cssNamespace }}-feature-card mb-4">
                                    <div class="{{ $cssNamespace }}-feature-card__icon">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <h3 class="{{ $cssNamespace }}-feature-card__title">Cecha produktu</h3>
                                    <p class="{{ $cssNamespace }}-feature-card__text">Opis cechy produktu</p>
                                </div>

                                {{-- Button Preview --}}
                                <div class="flex gap-2">
                                    <a href="#" class="{{ $cssNamespace }}-cta">Przycisk CTA</a>
                                    <a href="#" class="{{ $cssNamespace }}-cta {{ $cssNamespace }}-cta--secondary">Secondary</a>
                                </div>
                            </div>
                        </div>

                        {{-- CSS Variables Output --}}
                        <div class="mt-4">
                            <button
                                @click="$el.nextElementSibling.classList.toggle('hidden')"
                                class="text-sm text-gray-400 hover:text-gray-200"
                            >
                                Pokaz wygenerowany CSS
                            </button>
                            <pre class="hidden mt-2 p-3 bg-gray-900 rounded-lg text-xs text-gray-400 overflow-x-auto max-h-48">{{ $this->previewCss }}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Empty State --}}
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <svg class="w-16 h-16 text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-300 mb-2">Wybierz sklep</h3>
            <p class="text-sm text-gray-500">Wybierz sklep z listy aby edytowac jego styleset CSS</p>
        </div>
    @endif

    {{-- Import Modal --}}
    @if($showImportModal)
        @teleport('body')
        <div
            x-data="{ show: true }"
            x-show="show"
            x-cloak
            @keydown.escape.window="$wire.closeImportModal()"
            class="fixed inset-0 z-50"
        >
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="closeImportModal"></div>
            <div class="relative z-10 h-full flex items-center justify-center p-4">
                <div class="bg-gray-800 rounded-xl shadow-2xl max-w-lg w-full border border-gray-700">
                    {{-- Modal Header --}}
                    <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-100">Import Styleset</h3>
                        <button wire:click="closeImportModal" class="text-gray-400 hover:text-gray-200 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="p-6 space-y-4">
                        <p class="text-sm text-gray-400 mb-4">
                            Wklej dane JSON stylesetu wyeksportowanego wczesniej. Zaimportowane wartosci zastopia aktualne ustawienia.
                        </p>

                        {{-- JSON Input --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Dane JSON *</label>
                            <textarea
                                wire:model="importJson"
                                rows="12"
                                class="w-full px-4 py-2.5 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 font-mono text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                placeholder='{"name": "...", "variables": {...}, "custom_css": "..."}'
                            ></textarea>
                        </div>

                        <div class="p-3 bg-amber-500/10 border border-amber-500/30 rounded-lg">
                            <p class="text-xs text-amber-400">
                                <strong>Uwaga:</strong> Import zastapi aktualne wartosci zmiennych. Po imporcie zapisz zmiany przyciskiem "Zapisz".
                            </p>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="px-6 py-4 border-t border-gray-700 flex justify-end gap-3">
                        <button wire:click="closeImportModal" class="px-4 py-2 text-sm bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition">
                            Anuluj
                        </button>
                        <button wire:click="importStyleset" class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            Importuj
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endteleport
    @endif
</div>

@script
<script>
    // Handle JSON export download
    $wire.on('download-json', ({ filename, content }) => {
        const blob = new Blob([content], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });
</script>
@endscript
