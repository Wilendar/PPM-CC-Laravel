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

    {{-- Typy pojazdow --}}
    <div class="mb-6 bg-gray-800/50 border border-gray-700 rounded-lg p-5">
        <h4 class="text-sm font-semibold text-orange-400 uppercase tracking-wider mb-4">
            Typy pojazdow (filtr dopasowania)
        </h4>
        <p class="text-xs text-gray-500 mb-4">
            Gdy nazwa produktu zawiera slowo kluczowe typu (np. "buggy"), sugestie sa ograniczone do pojazdow tego samego typu.
            Prefix to poczatek nazwy pojazdu w bazie (np. "Buggy KAYO S200" ma prefix "buggy").
        </p>

        {{-- Istniejace typy --}}
        <div class="space-y-3 mb-4">
            @foreach($vehicleTypes as $index => $type)
            <div wire:key="vtype-{{ $type['key'] }}" class="bg-gray-900/50 rounded-lg p-3 border border-gray-700/50"
                 x-data="{ addingKeyword: false, newKw: '' }">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium text-white">{{ $type['label'] }}</span>
                        <span class="text-xs text-gray-500">klucz: {{ $type['key'] }}</span>
                        <span class="text-xs text-gray-500">prefix: "{{ $type['prefix'] }}"</span>
                    </div>
                    <button wire:click="removeVehicleType('{{ $type['key'] }}')"
                            wire:confirm="Usunac typ '{{ $type['label'] }}'?"
                            class="text-xs text-red-400 hover:text-red-300 transition-colors">
                        Usun typ
                    </button>
                </div>
                <div class="flex flex-wrap gap-1.5 items-center">
                    <span class="text-xs text-gray-500 mr-1">Slowa kluczowe:</span>
                    @foreach($type['keywords'] as $keyword)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-700 rounded text-xs text-gray-300">
                        {{ $keyword }}
                        @if(count($type['keywords']) > 1)
                        <button wire:click="removeKeywordFromType('{{ $type['key'] }}', '{{ $keyword }}')"
                                class="text-gray-500 hover:text-red-400 ml-0.5">&times;</button>
                        @endif
                    </span>
                    @endforeach
                    {{-- Inline add keyword (Alpine-only local state) --}}
                    <template x-if="addingKeyword">
                        <div class="inline-flex items-center gap-1">
                            <input type="text" x-model="newKw"
                                   x-on:keydown.enter="$wire.addKeywordToType('{{ $type['key'] }}', newKw); newKw=''; addingKeyword=false"
                                   placeholder="nowe slowo..."
                                   class="w-24 px-2 py-0.5 text-xs bg-gray-700 border border-gray-600 rounded text-white focus:border-orange-500">
                            <button x-on:click="$wire.addKeywordToType('{{ $type['key'] }}', newKw); newKw=''; addingKeyword=false"
                                    class="text-xs text-green-400 hover:text-green-300">&#10003;</button>
                            <button x-on:click="addingKeyword=false; newKw=''"
                                    class="text-xs text-gray-500 hover:text-gray-300">&times;</button>
                        </div>
                    </template>
                    <button x-show="!addingKeyword" x-on:click="addingKeyword=true"
                            class="text-xs text-gray-500 hover:text-orange-400 transition-colors">+ dodaj</button>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Formularz dodawania nowego typu (Alpine local state - no wire:model!) --}}
        <div class="bg-gray-900/30 rounded-lg p-3 border border-dashed border-gray-700"
             x-data="{ vtKey: '', vtLabel: '', vtPrefix: '', vtKeywords: '' }">
            <p class="text-xs text-gray-400 mb-3 font-medium">Dodaj nowy typ pojazdu</p>
            @if(session()->has('ai-config-error'))
            <p class="text-xs text-red-400 mb-2">{{ session('ai-config-error') }}</p>
            @endif
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                <div>
                    <input type="text" x-model="vtKey" placeholder="np. motocykl"
                           class="w-full px-2 py-1.5 text-xs bg-gray-700 border border-gray-600 rounded text-white focus:border-orange-500">
                    <p class="mt-1 text-[10px] text-gray-400 leading-tight">
                        <span class="text-gray-500 font-medium">Klucz</span> &mdash; unikalny identyfikator (male litery, bez spacji, np. "motocykl", "side_by_side")
                    </p>
                </div>
                <div>
                    <input type="text" x-model="vtLabel" placeholder="np. Motocykl"
                           class="w-full px-2 py-1.5 text-xs bg-gray-700 border border-gray-600 rounded text-white focus:border-orange-500">
                    <p class="mt-1 text-[10px] text-gray-400 leading-tight">
                        <span class="text-gray-500 font-medium">Nazwa</span> &mdash; wyswietlana w panelu (np. "Motocykl", "Side by Side")
                    </p>
                </div>
                <div>
                    <input type="text" x-model="vtPrefix" placeholder="np. motocykl"
                           class="w-full px-2 py-1.5 text-xs bg-gray-700 border border-gray-600 rounded text-white focus:border-orange-500">
                    <p class="mt-1 text-[10px] text-gray-400 leading-tight">
                        <span class="text-gray-500 font-medium">Prefix</span> &mdash; poczatek nazwy pojazdu w bazie, malymi literami (np. pojazd "Motocykl Honda CBR" ma prefix "motocykl")
                    </p>
                </div>
                <div>
                    <input type="text" x-model="vtKeywords" placeholder="np. motocykl, moto, motor"
                           class="w-full px-2 py-1.5 text-xs bg-gray-700 border border-gray-600 rounded text-white focus:border-orange-500">
                    <p class="mt-1 text-[10px] text-gray-400 leading-tight">
                        <span class="text-gray-500 font-medium">Slowa kluczowe</span> &mdash; szukane w nazwie czesci, oddzielone przecinkami (np. "motocykl, moto, motor" &rarr; czesc "Filtr moto Honda" dopasuje ten typ)
                    </p>
                </div>
            </div>
            <button x-on:click="$wire.addVehicleType(vtKey, vtLabel, vtPrefix, vtKeywords).then(() => { vtKey=''; vtLabel=''; vtPrefix=''; vtKeywords=''; })"
                    class="mt-3 px-3 py-1.5 text-xs bg-gray-700 border border-gray-600 rounded text-gray-300 hover:bg-gray-600 hover:text-white transition-colors">
                + Dodaj typ
            </button>
        </div>
    </div>

    {{-- Info: wyjasnienie algorytmu --}}
    <div class="mt-4 p-4 bg-gray-800/30 border border-gray-700/30 rounded-lg">
        <h4 class="text-xs font-semibold text-gray-400 uppercase mb-2">Jak dziala algorytm</h4>
        <p class="text-xs text-gray-500 leading-relaxed">
            Algorytm oblicza wynik pewnosci (0.00 - 1.00) sumujac warstwy:
            <span class="text-gray-400">Filtr typu</span> &rarr;
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

    {{-- Sticky save bar - widoczny gdy sa niezapisane zmiany --}}
    @if($hasChanges)
    <div class="sticky bottom-0 mt-4 -mx-5 -mb-5 px-5 py-3 bg-gray-900/95 border-t border-orange-500/30 backdrop-blur-sm flex items-center justify-between rounded-b-lg z-10">
        <span class="text-xs text-orange-400 flex items-center gap-2">
            <span class="w-2 h-2 bg-orange-400 rounded-full animate-pulse"></span>
            Masz niezapisane zmiany
        </span>
        <div class="flex gap-3">
            <button wire:click="resetToDefaults"
                    wire:confirm="Przywrocic domyslne wartosci? Wszystkie zmiany zostana utracone."
                    class="btn-enterprise-secondary text-xs">
                Przywroc domyslne
            </button>
            <button wire:click="saveConfig"
                    class="btn-enterprise-primary text-xs">
                <span wire:loading.remove wire:target="saveConfig">Zapisz konfiguracje</span>
                <span wire:loading wire:target="saveConfig">Zapisywanie...</span>
            </button>
        </div>
    </div>
    @endif
</div>
