<div>
    {{-- Flash message --}}
    @if(session()->has('ai-config-success'))
    <div class="mb-4 p-3 bg-green-900/30 border border-green-700/40 rounded-lg text-green-400 text-sm">
        {{ session('ai-config-success') }}
    </div>
    @endif

    {{-- Header z przyciskami --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-semibold text-white">Konfiguracja algorytmu AI</h3>
            <p class="text-sm text-gray-400 mt-1">Wagi i progi dla systemu inteligentnych sugestii dopasowania</p>
        </div>
        <div class="flex gap-3">
            <button wire:click="resetToDefaults"
                    wire:confirm="Przywrocic domyslne wartosci? Wszystkie zmiany zostana utracone."
                    class="btn-enterprise-secondary text-xs">
                Przywroc domyslne
            </button>
            <button wire:click="saveConfig"
                    class="btn-enterprise-primary text-xs {{ !$hasChanges ? 'opacity-50 cursor-not-allowed' : '' }}"
                    {{ !$hasChanges ? 'disabled' : '' }}>
                <span wire:loading.remove wire:target="saveConfig">Zapisz konfiguracje</span>
                <span wire:loading wire:target="saveConfig">Zapisywanie...</span>
            </button>
        </div>
    </div>

    {{-- Grupy konfiguracji --}}
    @foreach($fieldDefinitions as $groupKey => $group)
    <div wire:key="group-{{ $groupKey }}" class="mb-6 bg-gray-800/50 border border-gray-700 rounded-lg p-5">
        <h4 class="text-sm font-semibold text-orange-400 uppercase tracking-wider mb-4">
            {{ $group['label'] }}
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($group['fields'] as $fieldKey => $field)
            <div wire:key="field-{{ $fieldKey }}" class="bg-gray-900/50 rounded-lg p-4 border border-gray-700/50">
                {{-- Label + reset button --}}
                <div class="flex items-center justify-between mb-2">
                    <label class="text-xs font-medium text-gray-300">
                        {{ $field['label'] }}
                    </label>
                    @if(isset($values[$fieldKey]) && isset($defaults[$fieldKey]))
                        @php
                            $isDefault = is_float($defaults[$fieldKey])
                                ? abs((float)$values[$fieldKey] - (float)$defaults[$fieldKey]) < 0.001
                                : (int)$values[$fieldKey] === (int)$defaults[$fieldKey];
                        @endphp
                        @if(!$isDefault)
                        <button wire:click="resetField('{{ $fieldKey }}')"
                                class="text-xs text-gray-500 hover:text-orange-400 transition-colors"
                                title="Przywroc domyslna wartosc: {{ $defaults[$fieldKey] }}">
                            &#8617;
                        </button>
                        @endif
                    @endif
                </div>

                {{-- Suwak + input numeryczny --}}
                <div class="flex items-center gap-3">
                    <input type="range"
                           wire:model.live.debounce.300ms="values.{{ $fieldKey }}"
                           min="{{ $field['min'] }}"
                           max="{{ $field['max'] }}"
                           step="{{ $field['step'] }}"
                           class="flex-1 h-1.5 bg-gray-700 rounded-lg appearance-none cursor-pointer accent-orange-500">
                    <input type="number"
                           wire:model.live.debounce.300ms="values.{{ $fieldKey }}"
                           min="{{ $field['min'] }}"
                           max="{{ $field['max'] }}"
                           step="{{ $field['step'] }}"
                           class="w-16 px-2 py-1 text-xs text-center bg-gray-700 border border-gray-600 rounded text-white focus:border-orange-500 focus:ring-1 focus:ring-orange-500">
                </div>

                {{-- Opis pola --}}
                @if(isset($field['description']))
                <p class="mt-2 text-xs text-gray-500">{{ $field['description'] }}</p>
                @endif

                {{-- Domyslna wartosc --}}
                <p class="mt-1 text-xs text-gray-600">
                    Domyslnie: {{ $defaults[$fieldKey] ?? '-' }}
                </p>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach

    {{-- Info: wyjasnienie algorytmu --}}
    <div class="mt-4 p-4 bg-gray-800/30 border border-gray-700/30 rounded-lg">
        <h4 class="text-xs font-semibold text-gray-400 uppercase mb-2">Jak dziala algorytm</h4>
        <p class="text-xs text-gray-500 leading-relaxed">
            Algorytm oblicza wynik pewnosci (0.00 - 1.00) sumujac warstwy:
            <span class="text-gray-400">Keyword Rules</span> &rarr;
            <span class="text-gray-400">Detekcja Modelu</span> &rarr;
            <span class="text-gray-400">Detekcja Marki</span> &rarr;
            <span class="text-gray-400">Opis</span> &rarr;
            <span class="text-gray-400">Kategoria</span>.
            Wynik &ge; "Minimalny prog" = sugestia widoczna.
            Wynik &ge; "Prog auto-zatwierdzania" = sugestia zatwierdzana automatycznie.
            Suma jest ograniczona do 1.00.
        </p>
    </div>
</div>
