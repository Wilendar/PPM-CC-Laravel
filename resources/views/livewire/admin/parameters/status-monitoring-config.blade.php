{{--
    Status Monitoring Configuration Panel
    Admin panel for configuring product status monitoring

    @since 2026-02-04
    @see Plan_Projektu/synthetic-mixing-thunder.md
--}}

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-medium text-white">Konfiguracja monitorowania zgodności</h3>
            <p class="text-sm text-gray-400 mt-1">
                Określ które dane produktów są monitorowane pod kątem rozbieżności z integracjami.
            </p>
        </div>
        <div class="flex gap-2">
            <button wire:click="clearCache"
                    class="px-3 py-2 text-sm bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Wyczyść cache
            </button>
            <button wire:click="resetToDefaults"
                    class="px-3 py-2 text-sm bg-red-900/30 hover:bg-red-900/50 text-red-400 rounded-lg transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"/>
                </svg>
                Reset do domyślnych
            </button>
        </div>
    </div>

    {{-- Monitoring Categories --}}
    <div class="bg-gray-800/50 rounded-lg p-5 border border-gray-700">
        <h4 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Kategorie do monitorowania
        </h4>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Basic Data --}}
            <label class="flex items-start gap-3 p-3 bg-gray-900/50 rounded-lg cursor-pointer hover:bg-gray-900/70 transition-colors">
                <input type="checkbox"
                       wire:model.live="monitorBasic"
                       class="mt-1 rounded border-gray-600 text-orange-500 focus:ring-orange-500/20">
                <div>
                    <span class="text-sm font-medium text-white">Dane podstawowe</span>
                    <p class="text-xs text-gray-400 mt-0.5">Nazwa, producent, VAT, status</p>
                </div>
            </label>

            {{-- Descriptions --}}
            <label class="flex items-start gap-3 p-3 bg-gray-900/50 rounded-lg cursor-pointer hover:bg-gray-900/70 transition-colors">
                <input type="checkbox"
                       wire:model.live="monitorDescriptions"
                       class="mt-1 rounded border-gray-600 text-orange-500 focus:ring-orange-500/20">
                <div>
                    <span class="text-sm font-medium text-white">Opisy</span>
                    <p class="text-xs text-gray-400 mt-0.5">Krótki i długi opis</p>
                </div>
            </label>

            {{-- Physical Properties --}}
            <label class="flex items-start gap-3 p-3 bg-gray-900/50 rounded-lg cursor-pointer hover:bg-gray-900/70 transition-colors">
                <input type="checkbox"
                       wire:model.live="monitorPhysical"
                       class="mt-1 rounded border-gray-600 text-orange-500 focus:ring-orange-500/20">
                <div>
                    <span class="text-sm font-medium text-white">Wymiary/waga</span>
                    <p class="text-xs text-gray-400 mt-0.5">Waga, wysokość, szerokość, długość</p>
                </div>
            </label>

            {{-- Images --}}
            <label class="flex items-start gap-3 p-3 bg-gray-900/50 rounded-lg cursor-pointer hover:bg-gray-900/70 transition-colors">
                <input type="checkbox"
                       wire:model.live="monitorImages"
                       class="mt-1 rounded border-gray-600 text-orange-500 focus:ring-orange-500/20">
                <div>
                    <span class="text-sm font-medium text-white">Zdjęcia</span>
                    <p class="text-xs text-gray-400 mt-0.5">Brak zdjęć, nieprzypisane do integracji</p>
                </div>
            </label>

            {{-- Zero Price --}}
            <label class="flex items-start gap-3 p-3 bg-gray-900/50 rounded-lg cursor-pointer hover:bg-gray-900/70 transition-colors">
                <input type="checkbox"
                       wire:model.live="monitorZeroPrice"
                       class="mt-1 rounded border-gray-600 text-orange-500 focus:ring-orange-500/20">
                <div>
                    <span class="text-sm font-medium text-white">Cena zerowa</span>
                    <p class="text-xs text-gray-400 mt-0.5">Produkty z ceną 0,00 zł w aktywnej grupie</p>
                </div>
            </label>

            {{-- Low Stock --}}
            <label class="flex items-start gap-3 p-3 bg-gray-900/50 rounded-lg cursor-pointer hover:bg-gray-900/70 transition-colors">
                <input type="checkbox"
                       wire:model.live="monitorLowStock"
                       class="mt-1 rounded border-gray-600 text-orange-500 focus:ring-orange-500/20">
                <div>
                    <span class="text-sm font-medium text-white">Niski stan</span>
                    <p class="text-xs text-gray-400 mt-0.5">Poniżej stanu min. w magazynie domyślnym</p>
                </div>
            </label>

            {{-- Attributes (Conditional) --}}
            <label class="flex items-start gap-3 p-3 bg-purple-900/20 rounded-lg cursor-pointer hover:bg-purple-900/30 transition-colors border border-purple-700/30">
                <input type="checkbox"
                       wire:model.live="monitorAttributes"
                       class="mt-1 rounded border-purple-600 text-purple-500 focus:ring-purple-500/20">
                <div>
                    <span class="text-sm font-medium text-purple-300">Atrybuty</span>
                    <p class="text-xs text-purple-400/70 mt-0.5">Tylko dla typu: Pojazd</p>
                </div>
            </label>

            {{-- Compatibility (Conditional) --}}
            <label class="flex items-start gap-3 p-3 bg-blue-900/20 rounded-lg cursor-pointer hover:bg-blue-900/30 transition-colors border border-blue-700/30">
                <input type="checkbox"
                       wire:model.live="monitorCompatibility"
                       class="mt-1 rounded border-blue-600 text-blue-500 focus:ring-blue-500/20">
                <div>
                    <span class="text-sm font-medium text-blue-300">Dopasowania</span>
                    <p class="text-xs text-blue-400/70 mt-0.5">Tylko dla typu: Część zamienna</p>
                </div>
            </label>
        </div>
    </div>

    {{-- Ignored Fields --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Ignored Basic Fields --}}
        <div class="bg-gray-800/50 rounded-lg p-5 border border-gray-700">
            <h4 class="text-sm font-semibold text-white mb-3">Ignorowane pola (Dane podstawowe)</h4>
            <p class="text-xs text-gray-400 mb-4">Te pola nie będą uwzględniane przy wykrywaniu rozbieżności.</p>

            <div class="space-y-2">
                @foreach($availableBasicFields as $field => $label)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox"
                               wire:model.live="ignoredBasicFields"
                               value="{{ $field }}"
                               class="rounded border-gray-600 text-yellow-500 focus:ring-yellow-500/20">
                        <span class="text-sm text-gray-300">{{ $label }}</span>
                        <code class="text-xs text-gray-500 ml-auto">{{ $field }}</code>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Ignored Description Fields --}}
        <div class="bg-gray-800/50 rounded-lg p-5 border border-gray-700">
            <h4 class="text-sm font-semibold text-white mb-3">Ignorowane pola (Opisy)</h4>
            <p class="text-xs text-gray-400 mb-4">Pola SEO są domyślnie ignorowane.</p>

            <div class="space-y-2">
                @foreach($availableDescFields as $field => $label)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox"
                               wire:model.live="ignoredDescFields"
                               value="{{ $field }}"
                               class="rounded border-gray-600 text-yellow-500 focus:ring-yellow-500/20">
                        <span class="text-sm text-gray-300">{{ $label }}</span>
                        <code class="text-xs text-gray-500 ml-auto">{{ $field }}</code>
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Cache Settings --}}
    <div class="bg-gray-800/50 rounded-lg p-5 border border-gray-700">
        <h4 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            Wydajność (Cache)
        </h4>

        <div class="flex flex-wrap items-center gap-6">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox"
                       wire:model.live="cacheEnabled"
                       class="rounded border-gray-600 text-green-500 focus:ring-green-500/20">
                <span class="text-sm text-gray-300">Włącz cache statusów</span>
            </label>

            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-400">TTL (sekundy):</label>
                <input type="number"
                       wire:model.live="cacheTtl"
                       min="60"
                       max="3600"
                       step="60"
                       class="w-24 px-2 py-1 text-sm bg-gray-900 border border-gray-600 rounded text-white focus:border-orange-500 focus:ring-orange-500/20"
                       {{ !$cacheEnabled ? 'disabled' : '' }}>
                <span class="text-xs text-gray-500">({{ floor($cacheTtl / 60) }} min)</span>
            </div>
        </div>
    </div>

    {{-- Save Button --}}
    <div class="flex justify-end">
        <button wire:click="saveConfig"
                class="px-6 py-2.5 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition-colors flex items-center gap-2 font-medium">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Zapisz konfigurację
        </button>
    </div>
</div>
