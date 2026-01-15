{{-- ETAP_06 FAZA 5.5: FeatureTemplateModal - Cechy produktu dla pending products --}}
<div>
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="feature-modal-title" role="dialog" aria-modal="true">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-black/70 transition-opacity" wire:click="closeModal"></div>

        {{-- Modal container --}}
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-3xl bg-gray-800 rounded-xl shadow-2xl border border-gray-700"
                 @keydown.escape.window="$wire.closeModal()">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700">
                    <div>
                        <h3 id="feature-modal-title" class="text-lg font-semibold text-white">
                            Cechy produktu
                        </h3>
                        @if($pendingProduct)
                        <p class="text-sm text-gray-400 mt-1">
                            {{ $pendingProduct->sku }} - {{ $pendingProduct->name ?? '(brak nazwy)' }}
                        </p>
                        @endif
                    </div>
                    <button wire:click="closeModal"
                            class="text-gray-400 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="p-6 max-h-[70vh] overflow-y-auto">

                    {{-- Template selector --}}
                    <div class="mb-6 p-4 bg-purple-900/20 border border-purple-600/30 rounded-lg">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-medium text-purple-300">
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                Szablony cech
                            </h4>
                            <a href="/admin/features/vehicles" target="_blank"
                               class="text-xs text-purple-400 hover:text-purple-300 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                                Zarzadzaj szablonami
                            </a>
                        </div>
                        <div class="flex gap-2">
                            <select wire:model.live="selectedTemplateId"
                                    class="form-select-dark-sm flex-1">
                                <option value="">-- Wybierz szablon --</option>
                                @foreach($this->featureTemplates as $template)
                                <option value="{{ $template->id }}">
                                    {{ $template->name }}
                                    @if($template->is_predefined)(predefiniowany)@endif
                                    ({{ $template->getFeaturesCount() }} cech)
                                </option>
                                @endforeach
                            </select>
                            <button type="button"
                                    wire:click="loadTemplate"
                                    @disabled(!$selectedTemplateId)
                                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg
                                           transition-colors text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                Wczytaj
                            </button>
                        </div>

                        {{-- Template actions --}}
                        @if($this->filledCount > 0)
                        <div class="mt-3 pt-3 border-t border-purple-600/30 flex gap-2">
                            <button type="button"
                                    wire:click="openSaveTemplateModal"
                                    class="px-3 py-1.5 bg-green-600/20 hover:bg-green-600/30 text-green-400
                                           rounded text-xs transition-colors">
                                <svg class="w-3 h-3 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Zapisz jako nowy szablon
                            </button>
                            @if($selectedTemplateId && $this->selectedTemplate && !$this->selectedTemplate->is_predefined)
                            <button type="button"
                                    wire:click="updateTemplate"
                                    wire:confirm="Czy na pewno zaktualizowac szablon '{{ $this->selectedTemplate->name }}'?"
                                    class="px-3 py-1.5 bg-yellow-600/20 hover:bg-yellow-600/30 text-yellow-400
                                           rounded text-xs transition-colors">
                                <svg class="w-3 h-3 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                Aktualizuj szablon
                            </button>
                            @endif
                        </div>
                        @endif
                    </div>

                    {{-- Copy from product --}}
                    <div class="mb-6 p-4 bg-gray-700/30 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-300 mb-3">
                            Kopiuj cechy z innego produktu
                        </h4>
                        <div class="flex gap-2">
                            <input type="text"
                                   wire:model="copyFromSku"
                                   placeholder="Wpisz SKU produktu..."
                                   class="form-input-dark-sm flex-1"
                                   wire:keydown.enter="copyFromProduct">
                            <button type="button"
                                    wire:click="copyFromProduct"
                                    @disabled(empty($copyFromSku))
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg
                                           transition-colors text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                Kopiuj
                            </button>
                        </div>
                    </div>

                    {{-- Filters --}}
                    <div class="mb-4 flex flex-wrap gap-3">
                        <div class="flex-1 min-w-[200px]">
                            <input type="text"
                                   wire:model.live.debounce.300ms="featureSearch"
                                   placeholder="Szukaj cechy..."
                                   class="form-input-dark-sm w-full">
                        </div>
                        <div class="w-48">
                            <select wire:model.live="selectedGroupId" class="form-select-dark-sm w-full">
                                <option value="">Wszystkie grupy</option>
                                @foreach($this->featureGroups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if($this->filledCount > 0)
                        <button type="button"
                                wire:click="clearFeatures"
                                wire:confirm="Czy na pewno wyczyścić wszystkie cechy?"
                                class="px-3 py-2 text-red-400 hover:text-red-300 hover:bg-red-900/20
                                       rounded-lg text-sm transition-colors">
                            Wyczysc ({{ $this->filledCount }})
                        </button>
                        @endif
                    </div>

                    {{-- Feature types list --}}
                    <div class="space-y-3">
                        @forelse($this->featureTypes as $type)
                        @php
                            $hasValue = isset($featureValues[$type->id]) &&
                                       (!empty($featureValues[$type->id]['value']) || !empty($featureValues[$type->id]['value_id']));
                            $predefinedValues = $type->value_type === 'select' ? $this->getValuesForType($type->id) : collect();
                        @endphp

                        <div class="p-3 rounded-lg transition-colors
                                    {{ $hasValue ? 'bg-green-900/20 border border-green-600/30' : 'bg-gray-700/30' }}">
                            <div class="flex items-start gap-3">
                                {{-- Label --}}
                                <div class="w-48 flex-shrink-0">
                                    <label class="text-sm font-medium text-gray-200">
                                        {{ $type->name }}
                                    </label>
                                    @if($type->unit)
                                    <span class="text-xs text-gray-500 ml-1">({{ $type->unit }})</span>
                                    @endif
                                    @if($type->featureGroup)
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $type->featureGroup->name }}</p>
                                    @endif
                                </div>

                                {{-- Input --}}
                                <div class="flex-1">
                                    @if($type->value_type === 'select' && $predefinedValues->isNotEmpty())
                                        {{-- Select dropdown --}}
                                        <select wire:change="updateFeatureValueId({{ $type->id }}, $event.target.value)"
                                                class="form-select-dark-sm w-full">
                                            <option value="">-- wybierz --</option>
                                            @foreach($predefinedValues as $val)
                                            <option value="{{ $val->id }}"
                                                    @selected(($featureValues[$type->id]['value_id'] ?? null) === $val->id)>
                                                {{ $val->value }}
                                            </option>
                                            @endforeach
                                        </select>
                                    @elseif($type->value_type === 'bool')
                                        {{-- Boolean toggle --}}
                                        <div class="flex items-center gap-4">
                                            <label class="flex items-center cursor-pointer">
                                                <input type="radio"
                                                       name="feature_{{ $type->id }}"
                                                       wire:click="updateFeatureValue({{ $type->id }}, 'Tak')"
                                                       @checked(($featureValues[$type->id]['value'] ?? '') === 'Tak')
                                                       class="form-radio text-green-500">
                                                <span class="ml-2 text-sm text-gray-300">Tak</span>
                                            </label>
                                            <label class="flex items-center cursor-pointer">
                                                <input type="radio"
                                                       name="feature_{{ $type->id }}"
                                                       wire:click="updateFeatureValue({{ $type->id }}, 'Nie')"
                                                       @checked(($featureValues[$type->id]['value'] ?? '') === 'Nie')
                                                       class="form-radio text-red-500">
                                                <span class="ml-2 text-sm text-gray-300">Nie</span>
                                            </label>
                                            @if(!empty($featureValues[$type->id]['value']))
                                            <button type="button"
                                                    wire:click="updateFeatureValue({{ $type->id }}, '')"
                                                    class="text-xs text-gray-500 hover:text-gray-400">
                                                Wyczysc
                                            </button>
                                            @endif
                                        </div>
                                    @elseif($type->value_type === 'number')
                                        {{-- Number input --}}
                                        <input type="number"
                                               wire:model.blur="featureValues.{{ $type->id }}.value"
                                               step="any"
                                               placeholder="{{ $type->input_placeholder ?? 'Wpisz wartosc...' }}"
                                               class="form-input-dark-sm w-full">
                                    @else
                                        {{-- Text input (default) --}}
                                        <input type="text"
                                               wire:model.blur="featureValues.{{ $type->id }}.value"
                                               placeholder="{{ $type->input_placeholder ?? 'Wpisz wartosc...' }}"
                                               class="form-input-dark-sm w-full">
                                    @endif
                                </div>

                                {{-- Status indicator --}}
                                <div class="w-6 flex-shrink-0">
                                    @if($hasValue)
                                    <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="p-8 bg-gray-700/30 rounded-lg text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="text-gray-400 text-sm">Brak cech do wyswietlenia</p>
                            <p class="text-gray-500 text-xs mt-1">Zmien filtry lub dodaj cechy w panelu administracyjnym</p>
                        </div>
                        @endforelse
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-between px-6 py-4 border-t border-gray-700 bg-gray-800/50">
                    <div class="flex items-center gap-4">
                        <div class="text-sm text-gray-400">
                            Wypelnione: <span class="text-green-400 font-medium">{{ $this->filledCount }}</span>
                        </div>

                        {{-- Skip features info badge --}}
                        @if($this->isSkipped)
                        <div class="flex items-center gap-2 px-3 py-1.5 bg-red-900/30 border border-red-600/50 rounded-lg">
                            <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            <span class="text-sm text-red-400">Oznaczono jako "Brak cech"</span>
                            <button type="button"
                                    wire:click="clearSkipFeatures"
                                    class="ml-1 text-red-400 hover:text-red-300 underline text-xs">
                                Cofnij
                            </button>
                        </div>
                        @endif
                    </div>

                    <div class="flex gap-3">
                        {{-- Brak cech button --}}
                        @if(!$this->isSkipped)
                        <button type="button"
                                wire:click="setSkipFeatures"
                                wire:confirm="Czy na pewno oznaczyc jako 'Brak cech'? Produkt zostanie oznaczony jako kompletny bez cech."
                                @disabled($isProcessing)
                                class="px-4 py-2 bg-red-600/30 hover:bg-red-600/50 text-red-400 border border-red-600/50
                                       rounded-lg transition-colors text-sm disabled:opacity-50 disabled:cursor-not-allowed
                                       flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Brak cech
                        </button>
                        @endif

                        <button type="button"
                                wire:click="closeModal"
                                class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition-colors">
                            Anuluj
                        </button>

                        <button type="button"
                                wire:click="saveFeatures"
                                @disabled($isProcessing)
                                class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors
                                       font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                            @if($isProcessing)
                            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white inline-block" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Zapisywanie...
                            @else
                            Zapisz cechy
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Save as template modal --}}
    @if($showSaveTemplateModal)
    <div class="fixed inset-0 z-[60] overflow-y-auto" aria-labelledby="save-template-modal" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/80 transition-opacity" wire:click="closeSaveTemplateModal"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-gray-800 rounded-xl shadow-2xl border border-gray-700">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-semibold text-white">
                        Zapisz jako nowy szablon
                    </h3>
                    <button wire:click="closeSaveTemplateModal" class="text-gray-400 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6">
                    <label class="block text-sm text-gray-300 mb-2">Nazwa szablonu</label>
                    <input type="text"
                           wire:model="newTemplateName"
                           wire:keydown.enter="saveAsTemplate"
                           placeholder="np. Motocykle elektryczne..."
                           class="form-input-dark w-full"
                           autofocus>
                    <p class="text-xs text-gray-500 mt-2">
                        Szablon bedzie zawierał {{ $this->filledCount }} wypelnionych cech
                    </p>
                </div>
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-700">
                    <button type="button"
                            wire:click="closeSaveTemplateModal"
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg text-sm">
                        Anuluj
                    </button>
                    <button type="button"
                            wire:click="saveAsTemplate"
                            @disabled(empty($newTemplateName))
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm
                                   disabled:opacity-50 disabled:cursor-not-allowed">
                        Zapisz szablon
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
