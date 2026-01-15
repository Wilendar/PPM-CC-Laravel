{{-- ETAP_05b PHASE 6 + ETAP_05f: Variant Create Modal with Auto SKU --}}
<div x-data="{ showCreateModal: false }"
     @open-variant-create-modal.window="showCreateModal = true"
     @close-variant-modal.window="showCreateModal = false"
     x-show="showCreateModal"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">

    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/70 backdrop-blur-sm transition-opacity"
         @click="showCreateModal = false"
         x-show="showCreateModal"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
    </div>

    {{-- Modal Content --}}
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="relative bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full border border-gray-700"
             @click.stop
             x-show="showCreateModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-4">

            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700">
                <h3 class="text-lg font-semibold text-white">
                    <i class="fas fa-plus-circle text-green-500 mr-2"></i>
                    Dodaj Nowy Wariant
                </h3>
                <button type="button"
                        @click.stop="showCreateModal = false"
                        class="text-gray-400 hover:text-white transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="px-6 py-6 space-y-6 max-h-[70vh] overflow-y-auto">

                {{-- Auto SKU Checkbox (ETAP_05f) --}}
                <div class="bg-blue-900/20 border border-blue-700 rounded-lg p-4">
                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox"
                               wire:model.live="variantData.auto_generate_sku"
                               class="w-5 h-5 text-blue-500 bg-gray-900 border-gray-600 rounded focus:ring-blue-500 focus:ring-2">
                        <span class="text-sm text-gray-300">
                            <i class="fas fa-magic text-blue-400 mr-2"></i>
                            Automatycznie generuj SKU z atrybutow
                        </span>
                    </label>
                    <p class="text-xs text-gray-500 mt-2 ml-8">
                        SKU bedzie skladane z prefix/suffix zdefiniowanych w atrybutach
                    </p>
                </div>

                {{-- SKU Field --}}
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        SKU Wariantu *
                        @if($variantData['auto_generate_sku'] ?? false)
                            <span class="text-xs text-blue-400 ml-2">(generowane automatycznie)</span>
                        @endif
                    </label>
                    <input type="text"
                           wire:model="variantData.sku"
                           @if($variantData['auto_generate_sku'] ?? false) readonly @endif
                           class="w-full px-4 py-2 bg-gray-900 border border-gray-600 rounded-lg text-white placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors {{ ($variantData['auto_generate_sku'] ?? false) ? 'opacity-75 cursor-not-allowed' : '' }}"
                           placeholder="np. PROD-001-RED-M">
                    @error('variantData.sku')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Name Field --}}
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        Nazwa Wariantu *
                    </label>
                    <input type="text"
                           wire:model="variantData.name"
                           class="w-full px-4 py-2 bg-gray-900 border border-gray-600 rounded-lg text-white placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                           placeholder="np. Produkt - Czerwony - Medium">
                    @error('variantData.name')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Attribute Selection (ETAP_05f) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        Atrybuty Wariantu
                    </label>
                    @php
                        $attributeTypes = $this->getAttributeTypes();
                    @endphp

                    @if($attributeTypes->count() > 0)
                        <div class="space-y-3">
                            @foreach($attributeTypes as $type)
                                <div wire:key="attr-type-create-{{ $type->id }}" class="bg-gray-900/50 border border-gray-700 rounded-lg p-3">
                                    <label class="block text-xs text-gray-400 mb-2">{{ $type->name }}</label>
                                    <select wire:change="setVariantAttribute({{ $type->id }}, $event.target.value)"
                                            class="w-full px-4 py-2 bg-gray-900 border border-gray-600 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors">
                                        <option value="">-- Wybierz {{ $type->name }} --</option>
                                        @foreach($this->getAttributeValues($type->id) as $value)
                                            <option value="{{ $value->id }}"
                                                    @if(isset($variantAttributes[$type->id]) && $variantAttributes[$type->id] == $value->id) selected @endif>
                                                {{ $value->label }}
                                                @if($value->auto_prefix_enabled || $value->auto_suffix_enabled)
                                                    (SKU:
                                                    @if($value->auto_prefix_enabled && $value->auto_prefix){{ $value->auto_prefix }}-@endif
                                                    ...
                                                    @if($value->auto_suffix_enabled && $value->auto_suffix)-{{ $value->auto_suffix }}@endif
                                                    )
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="bg-gray-900 border border-gray-600 rounded-lg p-4">
                            <p class="text-sm text-gray-400 text-center italic">
                                <i class="fas fa-info-circle text-blue-400 mr-2"></i>
                                Brak typow atrybutow. Utworz je w
                                <a href="{{ route('admin.variants') }}" class="text-blue-400 hover:underline">/admin/variants</a>
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Checkboxes --}}
                <div class="space-y-3">
                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox"
                               wire:model="variantData.is_active"
                               class="w-5 h-5 text-blue-500 bg-gray-900 border-gray-600 rounded focus:ring-blue-500 focus:ring-2">
                        <span class="text-sm text-gray-300">Wariant aktywny</span>
                    </label>

                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox"
                               wire:model="variantData.is_default"
                               class="w-5 h-5 text-orange-500 bg-gray-900 border-gray-600 rounded focus:ring-orange-500 focus:ring-2">
                        <span class="text-sm text-gray-300">Ustaw jako wariant domyslny</span>
                    </label>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="flex items-center justify-end space-x-3 px-6 py-4 bg-gray-900/50 border-t border-gray-700 rounded-b-xl">
                <button type="button"
                        @click.stop="showCreateModal = false"
                        class="btn-enterprise-secondary px-4 py-2">
                    Anuluj
                </button>
                <button type="button"
                        wire:click="createVariant"
                        wire:loading.attr="disabled"
                        class="btn-enterprise-primary px-6 py-2">
                    <span wire:loading.remove wire:target="createVariant">Dodaj Wariant</span>
                    <span wire:loading wire:target="createVariant">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Tworzenie...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
