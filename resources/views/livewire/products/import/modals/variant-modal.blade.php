{{-- ETAP_06 FAZA 5.4: VariantModal - Tworzenie wariantow z collapsible panels + editable cards --}}
<div>
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="variant-modal-title" role="dialog" aria-modal="true">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-black/70 transition-opacity" wire:click="closeModal"></div>

        {{-- Modal container --}}
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-5xl bg-gray-800 rounded-xl shadow-2xl border border-gray-700"
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

                        {{-- LEFT COLUMN: Attribute selection with collapsible panels --}}
                        <div class="space-y-4">
                            <h4 class="text-sm font-medium text-gray-300 uppercase tracking-wider">
                                1. Wybierz typy atrybutow
                            </h4>

                            {{-- Collapsible attribute panels --}}
                            <div class="space-y-0 max-h-[280px] overflow-y-auto pr-1"
                                 x-data="{ expanded: {}, valueSearch: {} }">
                                @foreach($this->attributeTypes as $type)
                                    @include('livewire.products.import.modals.partials.attribute-type-panel', ['type' => $type])
                                @endforeach
                            </div>

                            {{-- SKU options --}}
                            <div class="p-4 bg-gray-700/30 rounded-lg">
                                <h5 class="text-sm font-medium text-gray-300 mb-3">Opcje SKU</h5>

                                {{-- Checkbox: Use DB config --}}
                                <label class="flex items-center p-2 bg-gray-600/30 rounded-lg cursor-pointer mb-3
                                              hover:bg-gray-600/50 transition-colors">
                                    <input type="checkbox"
                                           wire:model.live="useDbSuffixPrefix"
                                           class="w-4 h-4 rounded bg-gray-700 border-gray-500 text-green-500 focus:ring-green-500/30">
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

                        {{-- RIGHT COLUMN: Generated variants with editable cards --}}
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
                                        wire:confirm="Czy na pewno wyczyscic wszystkie warianty?"
                                        class="text-xs text-red-400 hover:text-red-300">
                                    Wyczysc
                                </button>
                                @endif
                            </div>

                            {{-- Summary bar --}}
                            @if(count($generatedVariants) > 0)
                                @php
                                    $activeCount = count(array_filter($variantActiveStates, fn($s) => $s));
                                    $inactiveCount = count($generatedVariants) - $activeCount;
                                @endphp
                                <div class="import-variant-summary-bar">
                                    <span class="import-variant-summary-count">{{ count($generatedVariants) }} wariantow</span>
                                    <span class="import-variant-summary-active">{{ $activeCount }} aktywnych</span>
                                    @if($inactiveCount > 0)
                                        <span class="import-variant-summary-inactive">{{ $inactiveCount }} wylaczonych</span>
                                    @endif
                                </div>
                            @endif

                            {{-- Variant cards or empty state --}}
                            @if(count($generatedVariants) === 0)
                            <div class="p-8 bg-gray-700/30 rounded-lg text-center">
                                <svg class="w-12 h-12 mx-auto text-gray-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                <p class="text-gray-400 text-sm">Wybierz atrybuty i kliknij "Generuj kombinacje"</p>
                            </div>
                            @else
                            <div class="space-y-0 max-h-[400px] overflow-y-auto pr-1">
                                @foreach($generatedVariants as $index => $variant)
                                    @include('livewire.products.import.modals.partials.variant-card-editable', [
                                        'variant' => $variant,
                                        'index' => $index,
                                    ])
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
