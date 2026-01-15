{{-- Value Edit Form Partial for AttributeValueManager --}}
<div class="absolute inset-0 bg-gray-900/90 flex items-center justify-center z-10">
    <div class="bg-gray-800 rounded-lg p-6 max-w-lg w-full border border-gray-700 shadow-xl">
        <h4 class="text-lg font-semibold text-white mb-4">
            {{ $editingValueId ? 'Edytuj wartosc' : 'Nowa wartosc' }}
        </h4>

        <form wire:submit.prevent="save" class="space-y-4"
              x-data="{
                  label: @entangle('formData.label'),
                  code: @entangle('formData.code'),
                  slugify(text) {
                      const polishMap = {
                          'ą': 'a', 'ć': 'c', 'ę': 'e', 'ł': 'l', 'ń': 'n',
                          'ó': 'o', 'ś': 's', 'ź': 'z', 'ż': 'z',
                          'Ą': 'a', 'Ć': 'c', 'Ę': 'e', 'Ł': 'l', 'Ń': 'n',
                          'Ó': 'o', 'Ś': 's', 'Ź': 'z', 'Ż': 'z'
                      };
                      return text
                          .split('').map(c => polishMap[c] || c).join('')
                          .toLowerCase()
                          .replace(/[^a-z0-9]+/g, '_')
                          .replace(/^_|_$/g, '');
                  }
              }"
              x-effect="if(label) code = slugify(label)">
            {{-- Label & Code (auto-generated) --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Etykieta (wyswietlana)</label>
                    <input type="text" x-model="label"
                           class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                           placeholder="np. Czerwony, XL, 42">
                    @error('formData.label') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Kod (auto)</label>
                    <input type="text" x-model="code" readonly
                           class="w-full px-3 py-2 bg-gray-950 border border-gray-700 rounded-lg text-gray-400 cursor-not-allowed"
                           placeholder="auto-generowany">
                    @error('formData.code') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Color Picker (only for color types) --}}
            @if($this->getIsColorType())
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Kolor HEX</label>
                    <div class="flex items-center gap-3">
                        <livewire:components.attribute-color-picker
                            :color="$formData['color_hex'] ?? '#000000'"
                            :key="'color-picker-' . ($editingValueId ?? 'new')"
                        />
                        <input type="text" wire:model="formData.color_hex"
                               class="flex-1 px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 font-mono"
                               placeholder="#ff0000">
                    </div>
                    @error('formData.color_hex') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            @endif

            {{-- Auto SKU Prefix/Suffix --}}
            <div class="border-t border-gray-700 pt-4">
                <h5 class="text-sm font-medium text-gray-400 mb-3">Auto-generowanie SKU wariantu</h5>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <input type="checkbox" wire:model="formData.auto_prefix_enabled" id="prefix-enabled"
                                   class="rounded border-gray-600 bg-gray-700 text-blue-500 focus:ring-blue-500">
                            <label for="prefix-enabled" class="text-sm text-gray-300">Dodaj prefix</label>
                        </div>
                        <input type="text" wire:model="formData.auto_prefix"
                               class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200"
                               placeholder="np. RED" {{ !$formData['auto_prefix_enabled'] ? 'disabled' : '' }}>
                        <p class="text-xs text-gray-500 mt-1">Rezultat: <span class="text-blue-400">{{ $formData['auto_prefix'] ?: 'XXX' }}</span>-SKU</p>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <input type="checkbox" wire:model="formData.auto_suffix_enabled" id="suffix-enabled"
                                   class="rounded border-gray-600 bg-gray-700 text-blue-500 focus:ring-blue-500">
                            <label for="suffix-enabled" class="text-sm text-gray-300">Dodaj suffix</label>
                        </div>
                        <input type="text" wire:model="formData.auto_suffix"
                               class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200"
                               placeholder="np. R" {{ !$formData['auto_suffix_enabled'] ? 'disabled' : '' }}>
                        <p class="text-xs text-gray-500 mt-1">Rezultat: SKU-<span class="text-green-400">{{ $formData['auto_suffix'] ?: 'XXX' }}</span></p>
                    </div>
                </div>
            </div>

            {{-- Position & Active --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Pozycja</label>
                    <input type="number" wire:model="formData.position" min="0"
                           class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 focus:border-blue-500">
                </div>
                <div class="flex items-center pt-6">
                    <input type="checkbox" wire:model="formData.is_active" id="is-active"
                           class="rounded border-gray-600 bg-gray-700 text-blue-500 focus:ring-blue-500 mr-2">
                    <label for="is-active" class="text-sm text-gray-300">Aktywna wartosc</label>
                </div>
            </div>

            {{-- Errors --}}
            @error('save') <div class="text-red-400 text-sm">{{ $message }}</div> @enderror

            {{-- Actions --}}
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-700">
                <button type="button" wire:click="cancelEdit" class="btn-enterprise-secondary">Anuluj</button>
                <button type="submit" class="btn-enterprise-primary">
                    {{ $editingValueId ? 'Zapisz zmiany' : 'Utworz wartosc' }}
                </button>
            </div>
        </form>
    </div>
</div>
