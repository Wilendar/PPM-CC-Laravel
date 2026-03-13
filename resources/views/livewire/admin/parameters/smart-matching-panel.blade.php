<div class="space-y-6">
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="p-3 bg-green-900/40 border border-green-700 rounded-lg text-green-400 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('info'))
        <div class="p-3 bg-blue-900/40 border border-blue-700 rounded-lg text-blue-400 text-sm">
            {{ session('info') }}
        </div>
    @endif

    {{-- Section Tabs --}}
    <div class="flex gap-2 border-b border-gray-700 pb-0">
        @foreach([
            ['key' => 'keyword-rules',   'label' => 'Reguly Keyword'],
            ['key' => 'model-detection', 'label' => 'Detekcja Modeli'],
            ['key' => 'sync-rules',      'label' => 'Reguly Sync'],
            ['key' => 'ai-config',       'label' => 'Konfiguracja AI'],
        ] as $section)
        <button wire:click="$set('activeSection', '{{ $section['key'] }}')"
                class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors
                       {{ $activeSection === $section['key']
                           ? 'border-orange-500 text-orange-400'
                           : 'border-transparent text-gray-400 hover:text-gray-300' }}">
            {{ $section['label'] }}
        </button>
        @endforeach
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- SEKCJA 1: Keyword Rules --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if($activeSection === 'keyword-rules')
    <div>
        <div class="flex justify-between items-center mb-4">
            <div>
                <h3 class="text-base font-semibold text-white">Reguly dopasowania keyword</h3>
                <p class="text-xs text-gray-500 mt-1">Definiuj slowa kluczowe zwiekszajace score dopasowania czesci do pojazdu</p>
            </div>
            <button wire:click="openAddKeywordModal" class="btn-enterprise-primary text-sm">
                + Dodaj regule
            </button>
        </div>

        {{-- Bulk Actions Bar --}}
        @if(count($selectedRuleIds) > 0)
        <div class="flex items-center gap-3 p-3 bg-orange-900/20 border border-orange-700/40 rounded-lg mb-4">
            <span class="text-sm text-orange-300">Zaznaczono: {{ count($selectedRuleIds) }}</span>
            <button wire:click="toggleSelectedRulesActive(true)"
                    class="btn-enterprise-secondary text-xs">
                Aktywuj
            </button>
            <button wire:click="toggleSelectedRulesActive(false)"
                    class="btn-enterprise-secondary text-xs">
                Dezaktywuj
            </button>
            <button wire:click="deleteSelectedRules"
                    wire:confirm="Usunac {{ count($selectedRuleIds) }} zaznaczonych regul?"
                    class="text-xs text-red-400 hover:text-red-300 transition-colors ml-auto">
                Usun zaznaczone
            </button>
        </div>
        @endif

        <div class="overflow-x-auto rounded-lg border border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-800 text-gray-400 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-3 py-3 w-8">
                            <input type="checkbox"
                                   wire:click="selectAllRules"
                                   {{ count($selectedRuleIds) === count($keywordRules) && count($keywordRules) > 0 ? 'checked' : '' }}
                                   class="rounded border-gray-600 bg-gray-700 text-orange-500 focus:ring-orange-500">
                        </th>
                        <th class="px-4 py-3 text-left">Keyword</th>
                        <th class="px-4 py-3 text-left">Pole</th>
                        <th class="px-4 py-3 text-left">Typ</th>
                        <th class="px-4 py-3 text-left">Typ pojazdu</th>
                        <th class="px-4 py-3 text-left">Marka</th>
                        <th class="px-4 py-3 text-center">Bonus</th>
                        <th class="px-4 py-3 text-center">Aktywna</th>
                        <th class="px-4 py-3 text-right">Akcje</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/50">
                    @forelse($keywordRules as $rule)
                    <tr class="hover:bg-gray-800/40 transition-colors {{ !$rule['is_active'] ? 'opacity-50' : '' }}">
                        <td class="px-3 py-3">
                            <input type="checkbox"
                                   wire:click="toggleRuleSelection({{ $rule['id'] }})"
                                   {{ in_array($rule['id'], $selectedRuleIds) ? 'checked' : '' }}
                                   class="rounded border-gray-600 bg-gray-700 text-orange-500 focus:ring-orange-500">
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-mono text-orange-400">{{ $rule['keyword'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-400">
                            @switch($rule['match_field'])
                                @case('any')  Dowolne @break
                                @case('name') Nazwa   @break
                                @case('sku')  SKU     @break
                                @default      {{ $rule['match_field'] }}
                            @endswitch
                        </td>
                        <td class="px-4 py-3 text-gray-400">
                            @switch($rule['match_type'])
                                @case('contains')    Zawiera         @break
                                @case('starts_with') Zaczyna sie od  @break
                                @case('exact')       Dokladne        @break
                                @case('regex')       Regex           @break
                                @default             {{ $rule['match_type'] }}
                            @endswitch
                        </td>
                        <td class="px-4 py-3 text-gray-300">
                            @if($rule['target_vehicle_type'])
                                @php
                                    $catLabel = collect($vehicleCategories)->firstWhere('slug', $rule['target_vehicle_type']);
                                @endphp
                                {{ $catLabel ? $catLabel['label'] : $rule['target_vehicle_type'] }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-300">
                            {{ $rule['target_brand'] ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge-enterprise--warning text-xs">+{{ number_format($rule['score_bonus'], 2) }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button wire:click="toggleRuleActive({{ $rule['id'] }})"
                                    class="w-10 h-5 rounded-full relative transition-colors {{ $rule['is_active'] ? 'bg-green-600' : 'bg-gray-600' }}">
                                <span class="absolute top-0.5 {{ $rule['is_active'] ? 'right-0.5' : 'left-0.5' }} w-4 h-4 bg-white rounded-full transition-all block"></span>
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <button wire:click="openEditKeywordModal({{ $rule['id'] }})"
                                        class="text-xs text-blue-400 hover:text-blue-300 transition-colors">
                                    Edytuj
                                </button>
                                <button wire:click="deleteKeywordRule({{ $rule['id'] }})"
                                        wire:confirm="Usunac regule {{ $rule['keyword'] }}?"
                                        class="text-xs text-red-400 hover:text-red-300 transition-colors">
                                    Usun
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center text-gray-500">
                            Brak regul keyword. Dodaj pierwsza regule.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Modal: Dodaj/Edytuj regule keyword --}}
        @if($showKeywordModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 w-full max-w-lg mx-4 shadow-2xl">
                <h4 class="text-base font-semibold text-white mb-4">
                    {{ $editingRuleId ? 'Edytuj regule keyword' : 'Nowa regula keyword' }}
                </h4>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Keyword *</label>
                        <input wire:model="editKeyword" type="text"
                               placeholder="np. pitbike, kayo, dirt bike..."
                               class="form-input-enterprise w-full">
                        @error('editKeyword') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Pole dopasowania</label>
                            <select wire:model="editMatchField" class="form-input-enterprise w-full">
                                <option value="any">Dowolne</option>
                                <option value="name">Nazwa</option>
                                <option value="sku">SKU</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Typ dopasowania</label>
                            <select wire:model="editMatchType" class="form-input-enterprise w-full">
                                <option value="contains">Zawiera</option>
                                <option value="starts_with">Zaczyna sie od</option>
                                <option value="exact">Dokladne</option>
                                <option value="regex">Regex</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Typ pojazdu (opcja)</label>
                            <select wire:model="editTargetVehicleType" class="form-input-enterprise w-full">
                                <option value="">-- Dowolny --</option>
                                @foreach($vehicleCategories as $cat)
                                    <option value="{{ $cat['slug'] }}">{{ $cat['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Marka (opcja)</label>
                            <input wire:model="editTargetBrand" type="text"
                                   placeholder="np. KAYO, Zipp..."
                                   class="form-input-enterprise w-full">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Bonus do score (0.00 - 1.00)</label>
                        <input wire:model="editScoreBonus" type="number"
                               step="0.05" min="0" max="1"
                               class="form-input-enterprise w-32">
                        @error('editScoreBonus') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Notatki (opcja)</label>
                        <textarea wire:model="editNotes" rows="2"
                                  class="form-input-enterprise w-full"
                                  placeholder="Opis reguly..."></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button wire:click="$set('showKeywordModal', false)"
                            class="btn-enterprise-secondary text-sm">
                        Anuluj
                    </button>
                    <button wire:click="saveKeywordRule"
                            class="btn-enterprise-primary text-sm">
                        Zapisz regule
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- SEKCJA 2: Vehicle Aliases (Detekcja Modeli) --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if($activeSection === 'model-detection')
    <div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Lewa: Wyszukiwarka pojazdow --}}
            <div class="bg-gray-800/50 rounded-lg border border-gray-700 p-4">
                <h4 class="text-sm font-semibold text-white mb-3">Wyszukaj pojazd</h4>
                <div class="relative">
                    <input wire:model.live.debounce.300ms="aliasSearchVehicle"
                           type="text"
                           placeholder="Szukaj po nazwie lub SKU..."
                           class="form-input-enterprise w-full mb-2">
                    {{-- Wyniki autouzupelniania --}}
                    @if(count($vehicleSearchResults) > 0)
                    <div class="absolute top-10 left-0 right-0 bg-gray-800 border border-gray-600 rounded-lg shadow-xl z-20 max-h-64 overflow-y-auto">
                        @foreach($vehicleSearchResults as $vehicle)
                        <button wire:click="selectVehicle({{ $vehicle['id'] }})"
                                class="w-full px-3 py-2 text-left text-sm hover:bg-gray-700 transition-colors border-b border-gray-700/50 last:border-0">
                            <span class="text-white block truncate">{{ $vehicle['name'] }}</span>
                            <span class="text-gray-500 text-xs font-mono">{{ $vehicle['sku'] }}</span>
                        </button>
                        @endforeach
                    </div>
                    @endif
                </div>

                @if($selectedVehicleId)
                <div class="mt-4 p-3 bg-orange-900/20 border border-orange-700/40 rounded-lg">
                    <p class="text-xs text-gray-400 mb-1">Wybrany pojazd:</p>
                    <p class="text-sm text-orange-300 font-medium">{{ $selectedVehicleName }}</p>
                </div>
                @endif

                <div class="mt-4 pt-4 border-t border-gray-700">
                    <button wire:click="autoGenerateAllAliases"
                            wire:confirm="Wygenerowac aliasy dla WSZYSTKICH pojazdow? Moze to chwile potrwac."
                            wire:loading.attr="disabled"
                            wire:target="autoGenerateAllAliases"
                            class="btn-enterprise-secondary text-xs w-full">
                        <span wire:loading.remove wire:target="autoGenerateAllAliases">
                            Auto-generuj dla wszystkich pojazdow
                        </span>
                        <span wire:loading wire:target="autoGenerateAllAliases">
                            Generowanie...
                        </span>
                    </button>
                </div>
            </div>

            {{-- Prawa: Aliasy wybranego pojazdu --}}
            <div class="lg:col-span-2 bg-gray-800/50 rounded-lg border border-gray-700 p-4">
                @if($selectedVehicleId)
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-sm font-semibold text-white">
                            Aliasy pojazdu
                            <span class="text-gray-500 font-normal text-xs ml-2">({{ count($vehicleAliases) }})</span>
                        </h4>
                        <div class="flex gap-2">
                            <button wire:click="autoGenerateAliases"
                                    wire:loading.attr="disabled"
                                    wire:target="autoGenerateAliases"
                                    class="btn-enterprise-secondary text-xs">
                                <span wire:loading.remove wire:target="autoGenerateAliases">Auto-generuj</span>
                                <span wire:loading wire:target="autoGenerateAliases">Generowanie...</span>
                            </button>
                            <button wire:click="openAddAliasModal"
                                    class="btn-enterprise-primary text-xs">
                                + Dodaj alias
                            </button>
                        </div>
                    </div>

                    @forelse($vehicleAliases as $alias)
                    <div class="flex items-center justify-between p-3 mb-2 bg-gray-900/40 rounded-lg border border-gray-700/50 hover:border-gray-600 transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-sm text-white">{{ $alias['alias'] }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full
                                {{ $alias['is_auto_generated']
                                    ? 'bg-blue-900/40 text-blue-400 border border-blue-700/40'
                                    : 'bg-purple-900/40 text-purple-400 border border-purple-700/40' }}">
                                {{ $alias['is_auto_generated'] ? 'auto' : 'manual' }}
                            </span>
                            <span class="text-xs text-gray-600">{{ $alias['alias_type'] }}</span>
                        </div>
                        <div class="flex gap-2">
                            <button wire:click="openEditAliasModal({{ $alias['id'] }})"
                                    class="text-xs text-blue-400 hover:text-blue-300 transition-colors">
                                Edytuj
                            </button>
                            <button wire:click="deleteAlias({{ $alias['id'] }})"
                                    wire:confirm="Usunac alias {{ $alias['alias'] }}?"
                                    class="text-xs text-red-500 hover:text-red-400 transition-colors">
                                Usun
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-gray-500 py-10 text-sm">
                        Brak aliasow. Uzyj "Auto-generuj" lub dodaj alias recznie.
                    </div>
                    @endforelse
                @else
                    <div class="flex items-center justify-center h-48 text-gray-500 text-sm">
                        Wybierz pojazd z listy po lewej stronie
                    </div>
                @endif
            </div>
        </div>

        {{-- Tabela WSZYSTKICH aliasow --}}
        <div class="mt-6 bg-gray-800/50 rounded-lg border border-gray-700 p-4">
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-sm font-semibold text-white">
                    Wszystkie aliasy
                    <span class="text-gray-500 font-normal text-xs ml-2">({{ $this->allAliases->total() }})</span>
                </h4>
                <div class="flex items-center gap-3">
                    <button wire:click="deleteAllAutoAliases"
                            wire:confirm="Usunac WSZYSTKIE automatycznie wygenerowane aliasy? Ta operacja jest nieodwracalna."
                            class="text-xs text-red-400 hover:text-red-300 transition-colors">
                        Usun wszystkie auto
                    </button>
                    <div class="relative w-64">
                        <input wire:model.live.debounce.300ms="allAliasesSearch"
                               type="text"
                               placeholder="Szukaj alias lub pojazd..."
                               class="form-input-enterprise w-full text-xs py-1.5 pl-8">
                        <svg class="w-3.5 h-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Bulk Actions Bar --}}
            @if(count($selectedAliasIds) > 0)
            <div class="flex items-center gap-3 p-3 bg-orange-900/20 border border-orange-700/40 rounded-lg mb-4">
                <span class="text-sm text-orange-300">Zaznaczono: {{ count($selectedAliasIds) }}</span>
                <button wire:click="deleteSelectedAliases"
                        wire:confirm="Usunac {{ count($selectedAliasIds) }} zaznaczonych aliasow?"
                        class="text-xs text-red-400 hover:text-red-300 transition-colors ml-auto">
                    Usun zaznaczone
                </button>
            </div>
            @endif

            <div class="overflow-x-auto rounded-lg border border-gray-700">
                <table class="w-full text-sm">
                    <thead class="bg-gray-800 text-gray-400 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-3 py-2 w-8">
                                @php
                                    $pageIds = $this->allAliases->pluck('id')->toArray();
                                    $allChecked = count($pageIds) > 0 && count(array_intersect($pageIds, $selectedAliasIds)) === count($pageIds);
                                @endphp
                                <input type="checkbox"
                                       wire:key="select-all-aliases-{{ $allChecked ? '1' : '0' }}"
                                       wire:click="selectAllAliases"
                                       {{ $allChecked ? 'checked' : '' }}
                                       class="rounded border-gray-600 bg-gray-700 text-orange-500 focus:ring-orange-500">
                            </th>
                            <th class="px-4 py-2 text-left">Alias</th>
                            <th class="px-4 py-2 text-left">Pojazd</th>
                            <th class="px-4 py-2 text-left">Typ</th>
                            <th class="px-4 py-2 text-center">Auto/Manual</th>
                            <th class="px-4 py-2 text-right">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700/50">
                        @forelse($this->allAliases as $alias)
                        <tr class="hover:bg-gray-800/40 transition-colors cursor-pointer"
                            wire:click="selectVehicle({{ $alias->vehicle_product_id }})">
                            <td class="px-3 py-2" wire:click.stop>
                                <input type="checkbox"
                                       wire:key="alias-cb-{{ $alias->id }}-{{ in_array($alias->id, $selectedAliasIds) ? '1' : '0' }}"
                                       wire:click.stop="toggleAliasSelection({{ $alias->id }})"
                                       {{ in_array($alias->id, $selectedAliasIds) ? 'checked' : '' }}
                                       class="rounded border-gray-600 bg-gray-700 text-orange-500 focus:ring-orange-500">
                            </td>
                            <td class="px-4 py-2">
                                <span class="font-mono text-orange-400">{{ $alias->alias }}</span>
                            </td>
                            <td class="px-4 py-2 text-gray-300">
                                {{ $alias->vehicleProduct?->manufacturer }} {{ $alias->vehicleProduct?->name }}
                            </td>
                            <td class="px-4 py-2 text-gray-500 text-xs">{{ $alias->alias_type }}</td>
                            <td class="px-4 py-2 text-center">
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $alias->is_auto_generated ? 'bg-blue-900/40 text-blue-400 border border-blue-700/40' : 'bg-purple-900/40 text-purple-400 border border-purple-700/40' }}">
                                    {{ $alias->is_auto_generated ? 'auto' : 'manual' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-right">
                                <div class="flex justify-end gap-2">
                                    <button wire:click.stop="openEditAliasModal({{ $alias->id }})"
                                            class="text-xs text-blue-400 hover:text-blue-300 transition-colors">
                                        Edytuj
                                    </button>
                                    <button wire:click.stop="deleteAlias({{ $alias->id }})"
                                            wire:confirm="Usunac alias {{ $alias->alias }}?"
                                            class="text-xs text-red-500 hover:text-red-400 transition-colors">
                                        Usun
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 text-sm">
                                Brak aliasow w systemie. Uzyj "Auto-generuj" aby wygenerowac.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($this->allAliases->hasPages())
                <div class="mt-3">
                    {{ $this->allAliases->links('components.pagination-compact') }}
                </div>
            @endif
        </div>

        {{-- Modal: Dodaj / Edytuj alias --}}
        @if($showAliasModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 w-full max-w-md mx-4 shadow-2xl">
                <h4 class="text-base font-semibold text-white mb-4">
                    {{ $editingAliasId ? 'Edytuj alias pojazdu' : 'Dodaj alias pojazdu' }}
                </h4>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Alias *</label>
                        <input wire:model="{{ $editingAliasId ? 'editAliasText' : 'newAliasText' }}" type="text"
                               placeholder="np. T110, TTR110, Thumpstar 110..."
                               class="form-input-enterprise w-full">
                        @error('newAliasText') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        @error('editAliasText') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Typ aliasu</label>
                        <select wire:model="{{ $editingAliasId ? 'editAliasType' : 'newAliasType' }}" class="form-input-enterprise w-full">
                            <option value="model_code">Kod modelu</option>
                            <option value="brand_model">Marka + model</option>
                            <option value="popular_name">Popularna nazwa</option>
                            <option value="sku_prefix">Prefiks SKU</option>
                            <option value="custom">Niestandardowy</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button wire:click="$set('showAliasModal', false); $set('editingAliasId', null)"
                            class="btn-enterprise-secondary text-sm">
                        Anuluj
                    </button>
                    <button wire:click="{{ $editingAliasId ? 'saveAliasEdit' : 'saveAlias' }}"
                            class="btn-enterprise-primary text-sm">
                        {{ $editingAliasId ? 'Zapisz zmiany' : 'Dodaj alias' }}
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- SEKCJA 3: Sync Brand Rules --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if($activeSection === 'sync-rules')
    <div>
        {{-- Wybor sklepu --}}
        <div class="flex flex-wrap items-center gap-4 mb-6 p-4 bg-gray-800/50 rounded-lg border border-gray-700">
            <label class="text-sm text-gray-400 whitespace-nowrap">Sklep PrestaShop:</label>
            <select wire:model.live="selectedShopId" class="form-input-enterprise flex-1 max-w-xs">
                <option value="">Wybierz sklep...</option>
                @foreach($availableShops as $shop)
                    <option value="{{ $shop['id'] }}">{{ $shop['name'] }}</option>
                @endforeach
            </select>

            @if($selectedShopId)
            <button wire:click="migrateFromLegacy"
                    wire:confirm="Migrowac reguly z pola allowed_vehicle_brands? Istniejace reguly nie zostana nadpisane."
                    class="btn-enterprise-secondary text-xs ml-auto">
                Migruj z legacy
            </button>
            @endif
        </div>

        @if($selectedShopId)
            <div class="flex justify-between items-center mb-4">
                <p class="text-sm text-gray-400">
                    Wybierz marki dozwolone do synchronizacji pojazdow z tym sklepem.
                </p>
                <div class="flex items-center gap-3">
                    <button wire:click="enableAllBrands"
                            wire:confirm="Zezwolic na WSZYSTKIE marki?"
                            class="text-xs text-green-400 hover:text-green-300 transition-colors">
                        Zezwol na wszystkie
                    </button>
                    <span class="text-gray-700">|</span>
                    <button wire:click="disableAllBrands"
                            wire:confirm="Zablokowac WSZYSTKIE marki?"
                            class="text-xs text-red-400 hover:text-red-300 transition-colors">
                        Zablokuj wszystkie
                    </button>
                    <span class="text-xs text-gray-600 ml-2">
                        {{ collect($brandRules)->where('is_allowed', true)->count() }} / {{ count($availableBrands) }} aktywnych
                    </span>
                </div>
            </div>

            @if(count($availableBrands) > 0)
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                @foreach($availableBrands as $brand)
                @php
                    $rule = collect($brandRules)->firstWhere('brand', $brand);
                    $isAllowed = $rule ? $rule['is_allowed'] : false;
                @endphp
                <div class="flex items-center justify-between p-3 bg-gray-800 rounded-lg border transition-colors
                            {{ $isAllowed ? 'border-green-700/60 bg-green-900/10' : 'border-gray-700' }}">
                    <span class="text-sm font-medium {{ $isAllowed ? 'text-green-300' : 'text-gray-500' }} truncate mr-2"
                          title="{{ $brand }}">
                        {{ $brand }}
                    </span>
                    <button wire:click="toggleBrandAllowed('{{ $brand }}')"
                            class="flex-shrink-0 w-10 h-5 rounded-full relative transition-colors
                                   {{ $isAllowed ? 'bg-green-600' : 'bg-gray-600' }}">
                        <span class="absolute top-0.5 {{ $isAllowed ? 'right-0.5' : 'left-0.5' }} w-4 h-4 bg-white rounded-full transition-all block"></span>
                    </button>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center text-gray-500 py-12 text-sm">
                Brak dostepnych marek. Dodaj produkty pojazdy z uzupelnionym polem "Producent".
            </div>
            @endif

        @else
            <div class="flex items-center justify-center h-48 text-gray-500 text-sm">
                Wybierz sklep PrestaShop aby skonfigurowac reguly sync marek
            </div>
        @endif
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- SEKCJA 4: AI Scoring Config --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if($activeSection === 'ai-config')
        @livewire('admin.parameters.ai-scoring-config-panel', [], key('ai-scoring-config'))
    @endif
</div>
