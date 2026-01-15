{{-- ETAP_06 FAZA 5.4: VariantModal - Tworzenie wariantow dla pending products --}}
<div>
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="variant-modal-title" role="dialog" aria-modal="true">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-black/70 transition-opacity" wire:click="closeModal"></div>

        {{-- Modal container --}}
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-4xl bg-gray-800 rounded-xl shadow-2xl border border-gray-700"
                 @keydown.escape.window="$wire.closeModal()">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700">
                    <div>
                        <h3 id="variant-modal-title" class="text-lg font-semibold text-white">
                            Warianty produktu
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
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                        {{-- Left: Attribute selection --}}
                        <div class="space-y-4">
                            <h4 class="text-sm font-medium text-gray-300 uppercase tracking-wider">
                                1. Wybierz typy atrybutow
                            </h4>

                            <div class="space-y-2 max-h-48 overflow-y-auto">
                                @foreach($this->attributeTypes as $type)
                                <label class="flex items-center p-3 bg-gray-700/50 rounded-lg cursor-pointer
                                              hover:bg-gray-700 transition-colors
                                              {{ isset($selectedAttributeTypes[$type->id]) && $selectedAttributeTypes[$type->id] ? 'ring-2 ring-green-500 bg-gray-700' : '' }}">
                                    <input type="checkbox"
                                           wire:click="toggleAttributeType({{ $type->id }})"
                                           @checked(isset($selectedAttributeTypes[$type->id]) && $selectedAttributeTypes[$type->id])
                                           class="form-checkbox-dark">
                                    <span class="ml-3">
                                        <span class="text-white text-sm font-medium">{{ $type->name }}</span>
                                        <span class="text-gray-500 text-xs ml-2">({{ $type->code }})</span>
                                    </span>
                                    @if($type->display_type === 'color')
                                    <span class="ml-auto text-xs text-purple-400">Kolor</span>
                                    @endif
                                </label>
                                @endforeach
                            </div>

                            {{-- Values for selected types --}}
                            @foreach($selectedAttributeTypes as $typeId => $selected)
                                @if($selected)
                                @php $type = $this->attributeTypes->firstWhere('id', $typeId); @endphp
                                <div class="mt-4 p-4 bg-gray-700/30 rounded-lg">
                                    <h5 class="text-sm font-medium text-gray-300 mb-3">
                                        {{ $type?->name ?? 'Typ' }} - wartosci
                                    </h5>
                                    <div class="flex flex-wrap gap-2 max-h-32 overflow-y-auto">
                                        @foreach($this->getValuesForType($typeId) as $value)
                                        <button type="button"
                                                wire:click="toggleValue({{ $typeId }}, {{ $value->id }})"
                                                class="px-3 py-1.5 rounded-lg text-sm transition-colors
                                                       {{ isset($selectedValues[$typeId][$value->id]) && $selectedValues[$typeId][$value->id]
                                                           ? 'bg-green-600 text-white'
                                                           : 'bg-gray-600 text-gray-300 hover:bg-gray-500' }}"
                                                title="{{ $value->auto_suffix ? 'Suffix: ' . $value->auto_suffix : '' }}{{ $value->auto_prefix ? 'Prefix: ' . $value->auto_prefix : '' }}">
                                            @if($value->color_hex)
                                            <span class="inline-block w-4 h-4 rounded mr-1.5"
                                                  style="background-color: {{ $value->color_hex }}; vertical-align: middle;"></span>
                                            @endif
                                            {{ $value->label }}
                                            @if($value->auto_suffix)
                                            <span class="text-xs text-gray-400 ml-1">({{ $value->auto_suffix }})</span>
                                            @endif
                                        </button>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            @endforeach

                            {{-- SKU options --}}
                            <div class="p-4 bg-gray-700/30 rounded-lg mt-4">
                                <h5 class="text-sm font-medium text-gray-300 mb-3">Opcje SKU</h5>

                                {{-- Checkbox: Use DB config --}}
                                <label class="flex items-center p-2 bg-gray-600/30 rounded-lg cursor-pointer mb-3
                                              hover:bg-gray-600/50 transition-colors">
                                    <input type="checkbox"
                                           wire:model.live="useDbSuffixPrefix"
                                           class="form-checkbox-dark">
                                    <span class="ml-3 text-sm text-gray-300">
                                        Uzyj suffix/prefix z konfiguracji atrybutow
                                    </span>
                                </label>

                                @if(!$useDbSuffixPrefix)
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-xs text-gray-400 mb-1 block">Tryb SKU</label>
                                        <select wire:model="skuMode" class="form-select-dark-sm w-full">
                                            <option value="suffix">Suffix (SKU-XXX)</option>
                                            <option value="prefix">Prefix (XXX-SKU)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-400 mb-1 block">Separator</label>
                                        <input type="text" wire:model="skuSeparator"
                                               maxlength="3"
                                               class="form-input-dark-sm w-full">
                                    </div>
                                </div>
                                @else
                                <p class="text-xs text-gray-400">
                                    SKU bedzie generowane na podstawie auto_suffix/auto_prefix zdefiniowanych w
                                    <a href="/admin/variants" target="_blank" class="text-blue-400 hover:underline">panelu wariantow</a>.
                                </p>
                                @endif
                            </div>

                            {{-- Generate button --}}
                            <button type="button"
                                    wire:click="generateVariants"
                                    @disabled(empty($selectedAttributeTypes))
                                    class="w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg
                                           transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Generuj kombinacje
                            </button>
                        </div>

                        {{-- Right: Generated variants --}}
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-sm font-medium text-gray-300 uppercase tracking-wider">
                                    2. Wygenerowane warianty
                                    @if(count($generatedVariants) > 0)
                                    <span class="ml-2 px-2 py-0.5 bg-green-600 text-white text-xs rounded-full">
                                        {{ count($generatedVariants) }}
                                    </span>
                                    @endif
                                </h4>
                                @if(count($generatedVariants) > 0)
                                <button type="button"
                                        wire:click="clearVariants"
                                        wire:confirm="Czy na pewno wyczyścić wszystkie warianty?"
                                        class="text-xs text-red-400 hover:text-red-300">
                                    Wyczysc
                                </button>
                                @endif
                            </div>

                            @if(count($generatedVariants) === 0)
                            <div class="p-8 bg-gray-700/30 rounded-lg text-center">
                                <svg class="w-12 h-12 mx-auto text-gray-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                <p class="text-gray-400 text-sm">Wybierz atrybuty i kliknij "Generuj kombinacje"</p>
                            </div>
                            @else
                            <div class="space-y-2 max-h-[400px] overflow-y-auto">
                                @foreach($generatedVariants as $index => $variant)
                                <div class="p-3 bg-gray-700/50 rounded-lg flex items-center justify-between group
                                            hover:bg-gray-700 transition-colors">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="font-mono text-sm text-green-400">
                                                {{ $variant['full_sku'] ?? '-' }}
                                            </span>
                                        </div>
                                        <p class="text-gray-300 text-sm truncate">{{ $variant['name'] ?? '-' }}</p>
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            @foreach($variant['attributes'] ?? [] as $attr)
                                            <span class="inline-flex items-center px-2 py-0.5 bg-gray-600 rounded text-xs text-gray-300">
                                                @if(!empty($attr['color_hex']))
                                                <span class="w-3 h-3 rounded mr-1"
                                                      style="background-color: {{ $attr['color_hex'] }}"></span>
                                                @endif
                                                {{ $attr['attribute_type_name'] ?? '' }}: {{ $attr['value'] ?? '' }}
                                            </span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <button type="button"
                                            wire:click="removeVariant({{ $index }})"
                                            class="p-1.5 text-gray-500 hover:text-red-400 transition-colors
                                                   opacity-0 group-hover:opacity-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-between px-6 py-4 border-t border-gray-700 bg-gray-800/50">
                    <button type="button"
                            wire:click="closeModal"
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition-colors">
                        Anuluj
                    </button>

                    <button type="button"
                            wire:click="saveVariants"
                            @disabled(count($generatedVariants) === 0 || $isProcessing)
                            class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors
                                   font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                        @if($isProcessing)
                        <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white inline-block" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Zapisywanie...
                        @else
                        Zapisz warianty ({{ count($generatedVariants) }})
                        @endif
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
