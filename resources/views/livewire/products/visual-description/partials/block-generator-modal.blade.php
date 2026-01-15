{{-- Block Generator Modal --}}
{{-- ETAP_07f_P3: Creates dedicated blocks from prestashop-section HTML --}}

<div>
@if($show)
<div
    class="fixed inset-0 z-50 flex items-center justify-center"
    x-data="{ show: @entangle('show') }"
    x-show="show"
    x-cloak
>
    {{-- Backdrop --}}
    <div
        class="absolute inset-0 bg-black/70"
        @click="$wire.close()"
    ></div>

    {{-- Modal Content --}}
    <div class="relative w-full max-w-4xl max-h-[90vh] bg-gray-900 rounded-xl shadow-2xl overflow-hidden flex flex-col">
        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-amber-500/20 rounded-lg">
                    <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-white">Utworz dedykowany blok</h2>
                    <p class="text-sm text-gray-400">
                        @if($this->shop)
                            Sklep: {{ $this->shop->name }}
                        @endif
                    </p>
                </div>
            </div>

            <button
                wire:click="close"
                class="p-2 text-gray-400 hover:text-white rounded-lg hover:bg-gray-800 transition"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Step Indicator --}}
        <div class="flex items-center justify-center gap-2 px-6 py-3 bg-gray-800/50">
            @foreach([1 => 'Analiza', 2 => 'Konfiguracja', 3 => 'Podglad', 4 => 'Zapis'] as $step => $label)
                <div class="flex items-center gap-2">
                    <div @class([
                        'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium',
                        'bg-amber-500 text-white' => $currentStep === $step,
                        'bg-green-500 text-white' => $currentStep > $step,
                        'bg-gray-700 text-gray-400' => $currentStep < $step,
                    ])>
                        @if($currentStep > $step)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            {{ $step }}
                        @endif
                    </div>
                    <span @class([
                        'text-sm',
                        'text-white font-medium' => $currentStep === $step,
                        'text-green-400' => $currentStep > $step,
                        'text-gray-500' => $currentStep < $step,
                    ])>{{ $label }}</span>

                    @if($step < 4)
                        <div class="w-8 h-0.5 bg-gray-700"></div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Content --}}
        <div class="flex-1 overflow-y-auto p-6">
            @if(!empty($analysisErrors))
                <div class="mb-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg">
                    <h4 class="text-sm font-medium text-red-400 mb-2">Bledy analizy:</h4>
                    <ul class="text-sm text-red-300 space-y-1">
                        @foreach($analysisErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Step 1: Analysis --}}
            @if($currentStep === 1)
                <div class="space-y-4">
                    <div class="p-4 bg-gray-800 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-300 mb-2">Zrodlowy HTML:</h3>
                        <pre class="text-xs text-gray-400 bg-gray-900 p-3 rounded max-h-48 overflow-auto">{{ $sourceHtml }}</pre>
                    </div>

                    <div wire:loading wire:target="analyze" class="flex items-center justify-center py-8">
                        <svg class="w-8 h-8 text-amber-500 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <span class="ml-2 text-gray-400">Analizowanie struktury HTML...</span>
                    </div>

                    @if(!$isAnalyzed)
                        <button
                            wire:click="analyze"
                            class="w-full py-3 bg-amber-500 hover:bg-amber-600 text-white font-medium rounded-lg transition"
                        >
                            Analizuj HTML
                        </button>
                    @endif
                </div>
            @endif

            {{-- Step 2: Configuration --}}
            @if($currentStep === 2)
                <div class="space-y-6">
                    {{-- Analysis Summary --}}
                    @if(!empty($this->analysisSummary))
                        <div class="grid grid-cols-4 gap-4">
                            <div class="p-3 bg-gray-800 rounded-lg text-center">
                                <div class="text-2xl font-bold text-amber-400">{{ $this->analysisSummary['elementCount'] }}</div>
                                <div class="text-xs text-gray-500">Elementow</div>
                            </div>
                            <div class="p-3 bg-gray-800 rounded-lg text-center">
                                <div class="text-2xl font-bold text-blue-400">{{ $this->analysisSummary['cssClassCount'] }}</div>
                                <div class="text-xs text-gray-500">Klas CSS</div>
                            </div>
                            <div class="p-3 bg-gray-800 rounded-lg text-center">
                                <div class="text-2xl font-bold text-green-400">{{ $this->analysisSummary['contentFieldCount'] }}</div>
                                <div class="text-xs text-gray-500">Pol tresci</div>
                            </div>
                            <div class="p-3 bg-gray-800 rounded-lg text-center">
                                <div class="text-2xl font-bold text-purple-400">{{ $this->analysisSummary['depth'] }}</div>
                                <div class="text-xs text-gray-500">Glebokosc</div>
                            </div>
                        </div>
                    @endif

                    {{-- Block Settings --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Nazwa bloku *</label>
                            <input
                                type="text"
                                wire:model.live="blockName"
                                class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                                placeholder="np. Sekcja zalet KAYO"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Identyfikator typu</label>
                            <input
                                type="text"
                                wire:model="blockType"
                                class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                                placeholder="np. pd-merits-kayo"
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Ikona</label>
                            <select
                                wire:model="blockIcon"
                                class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                            >
                                @foreach($this->availableIcons as $icon => $label)
                                    <option value="{{ $icon }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Opis (opcjonalnie)</label>
                            <input
                                type="text"
                                wire:model="blockDescription"
                                class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                                placeholder="Krotki opis bloku"
                            >
                        </div>
                    </div>

                    {{-- Detected Content Fields --}}
                    @if(!empty($contentFields))
                        <div>
                            <h4 class="text-sm font-medium text-gray-300 mb-2">Wykryte pola tresci:</h4>
                            <div class="space-y-2">
                                @foreach($contentFields as $index => $field)
                                    <div class="flex items-center gap-3 p-2 bg-gray-800 rounded">
                                        <span class="px-2 py-0.5 text-xs bg-blue-500/20 text-blue-400 rounded">{{ $field['type'] }}</span>
                                        <span class="text-sm text-gray-300">{{ $field['label'] }}</span>
                                        <span class="text-xs text-gray-500">{{ $field['name'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Step 3: Preview & Template --}}
            @if($currentStep === 3)
                <div class="space-y-4">
                    <div>
                        <h4 class="text-sm font-medium text-gray-300 mb-2">Szablon renderowania:</h4>
                        <textarea
                            wire:model="renderTemplate"
                            rows="12"
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white font-mono text-xs focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                        ></textarea>
                        <p class="mt-1 text-xs text-gray-500">
                            Uzyj <code class="text-amber-400">@{{ $content.fieldName }}</code> dla pol tresci i <code class="text-amber-400">@{{ $settings.cssClass }}</code> dla ustawien.
                        </p>
                    </div>

                    <div>
                        <h4 class="text-sm font-medium text-gray-300 mb-2">Podglad:</h4>
                        <div class="p-4 bg-white rounded-lg">
                            <div class="prose prose-sm max-w-none">
                                {!! $previewHtml !!}
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Step 4: Save --}}
            @if($currentStep === 4)
                <div class="space-y-6">
                    <div class="p-6 bg-gray-800 rounded-lg text-center">
                        <svg class="w-16 h-16 text-green-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h3 class="text-lg font-medium text-white mb-2">Gotowe do zapisania</h3>
                        <p class="text-gray-400">
                            Blok <strong class="text-amber-400">{{ $blockName }}</strong> zostanie utworzony dla sklepu
                            <strong class="text-blue-400">{{ $this->shop?->name }}</strong>.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div class="p-3 bg-gray-800 rounded">
                            <span class="text-gray-500">Typ:</span>
                            <span class="text-white ml-2">{{ $blockType }}</span>
                        </div>
                        <div class="p-3 bg-gray-800 rounded">
                            <span class="text-gray-500">Ikona:</span>
                            <span class="text-white ml-2">{{ $blockIcon }}</span>
                        </div>
                        <div class="p-3 bg-gray-800 rounded">
                            <span class="text-gray-500">Pol tresci:</span>
                            <span class="text-white ml-2">{{ count($contentFields) }}</span>
                        </div>
                        <div class="p-3 bg-gray-800 rounded">
                            <span class="text-gray-500">Klas CSS:</span>
                            <span class="text-white ml-2">{{ count($analysisResult['cssClasses'] ?? []) }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between px-6 py-4 border-t border-gray-700 bg-gray-800/50">
            <button
                wire:click="close"
                class="px-4 py-2 text-gray-400 hover:text-white transition"
            >
                Anuluj
            </button>

            <div class="flex items-center gap-3">
                @if($currentStep > 1)
                    <button
                        wire:click="previousStep"
                        class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition"
                    >
                        Wstecz
                    </button>
                @endif

                @if($currentStep < 4)
                    <button
                        wire:click="nextStep"
                        @if(!$isAnalyzed) disabled @endif
                        @class([
                            'px-4 py-2 rounded-lg font-medium transition',
                            'bg-amber-500 text-white hover:bg-amber-600' => $isAnalyzed,
                            'bg-gray-700 text-gray-500 cursor-not-allowed' => !$isAnalyzed,
                        ])
                    >
                        Dalej
                    </button>
                @else
                    <button
                        wire:click="save"
                        wire:loading.attr="disabled"
                        class="px-6 py-2 bg-green-500 text-white font-medium rounded-lg hover:bg-green-600 transition"
                    >
                        <span wire:loading.remove wire:target="save">Zapisz blok</span>
                        <span wire:loading wire:target="save">Zapisywanie...</span>
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
</div>
