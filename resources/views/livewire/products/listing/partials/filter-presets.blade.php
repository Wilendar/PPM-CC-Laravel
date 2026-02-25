{{-- Filter Presets Dropdown --}}
<div x-data="{ showPresets: false }" class="relative">
    <button @click="showPresets = !showPresets"
            type="button"
            class="btn-secondary inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-300 whitespace-nowrap">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
        </svg>
        <span class="hidden sm:inline">Presety</span>
    </button>

    <div x-show="showPresets"
         @click.away="showPresets = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-2 w-64 bg-gray-800 border border-gray-700 rounded-lg shadow-xl z-50"
         style="display: none;">

        {{-- Header --}}
        <div class="p-3 border-b border-gray-700">
            <h4 class="text-sm font-semibold text-white">Zapisane presety</h4>
        </div>

        {{-- Preset List --}}
        <div class="max-h-48 overflow-y-auto">
            @forelse($this->savedPresets as $preset)
                <div wire:key="preset-{{ $preset->id }}"
                     class="flex items-center justify-between px-3 py-2 hover:bg-gray-700/50 transition-colors">
                    <button wire:click="applyPreset({{ $preset->id }})"
                            class="text-sm text-gray-300 hover:text-white truncate flex-1 text-left">
                        {{ $preset->name }}
                        @if($preset->is_default)
                            <span class="text-xs text-orange-400 ml-1">(domyslny)</span>
                        @endif
                    </button>
                    <button wire:click="deletePreset({{ $preset->id }})"
                            wire:confirm="Usunac preset '{{ $preset->name }}'?"
                            class="p-1 text-gray-500 hover:text-red-400 transition-colors ml-2 flex-shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            @empty
                <div class="px-3 py-4 text-center text-sm text-gray-500">
                    Brak zapisanych presetow
                </div>
            @endforelse
        </div>

        {{-- Save New Preset --}}
        <div class="p-3 border-t border-gray-700">
            @if($showPresetModal)
                <div class="space-y-2">
                    <input wire:model="newPresetName"
                           type="text"
                           placeholder="Nazwa presetu..."
                           class="form-input w-full text-sm rounded-lg bg-gray-700 border-gray-600 text-white placeholder-gray-400 focus:ring-orange-500 focus:border-orange-500">
                    <label class="flex items-center text-xs text-gray-400 cursor-pointer">
                        <input wire:model="newPresetIsDefault"
                               type="checkbox"
                               class="mr-2 rounded border-gray-600 text-orange-500 focus:ring-orange-500">
                        Ustaw jako domyslny
                    </label>
                    <div class="flex gap-2">
                        <button wire:click="saveCurrentFiltersAsPreset"
                                class="flex-1 px-3 py-1.5 text-xs bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors font-medium">
                            Zapisz
                        </button>
                        <button wire:click="$set('showPresetModal', false)"
                                class="px-3 py-1.5 text-xs text-gray-400 hover:text-white transition-colors">
                            Anuluj
                        </button>
                    </div>
                </div>
            @else
                <button wire:click="$set('showPresetModal', true)"
                        class="w-full px-3 py-2 text-sm text-center text-orange-400 hover:text-orange-300 hover:bg-gray-700/50 rounded-lg transition-colors">
                    + Zapisz biezace filtry
                </button>
            @endif
        </div>
    </div>
</div>
