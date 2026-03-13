{{-- Zone naming configuration modal --}}
@if($showZoneConfigModal)
    <div class="modal-overlay show" wire:click.self="closeZoneConfigModal">
        <div class="audit-modal-dialog max-w-md">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Konfiguracja nazewnictwa stref</h3>
                <p class="text-sm text-gray-400 mb-4">
                    Ustawienia automatycznego nazewnictwa stref dla tego magazynu.
                    Np. strefa "A" bedzie wyswietlana jako "{{ $zonePrefix }}{{ $zoneSeparator }}A".
                </p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Prefiks strefy</label>
                        <input type="text" wire:model.live="zonePrefix"
                               class="w-full location-input-enterprise"
                               placeholder="np. Strefa, Sekcja, Hala">
                        <p class="text-gray-500 text-xs mt-1">Tekst przed nazwa strefy (np. "Strefa", "Sekcja", "Hala")</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Separator</label>
                        <input type="text" wire:model.live="zoneSeparator"
                               class="w-full location-input-enterprise"
                               placeholder="np. spacja, -, _"
                               maxlength="5">
                        <p class="text-gray-500 text-xs mt-1">Znak miedzy prefiksem a nazwa (spacja, myslnik, etc.)</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model.live="zoneAutoUppercase" id="zoneAutoUppercase"
                               class="checkbox-enterprise">
                        <label for="zoneAutoUppercase" class="text-sm text-gray-300">Automatycznie wielkie litery</label>
                    </div>

                    {{-- Preview --}}
                    <div class="p-3 bg-gray-800/50 rounded-lg border border-gray-700">
                        <p class="text-xs text-gray-400 mb-2">Podglad:</p>
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-white">
                                A &rarr; <strong>{{ $zonePrefix }}{{ $zoneSeparator }}{{ $zoneAutoUppercase ? 'A' : 'a' }}</strong>
                            </span>
                            <span class="text-sm text-white">
                                b &rarr; <strong>{{ $zonePrefix }}{{ $zoneSeparator }}{{ $zoneAutoUppercase ? 'B' : 'b' }}</strong>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="closeZoneConfigModal"
                            class="btn-enterprise-secondary text-sm px-4 py-2">
                        Anuluj
                    </button>
                    <button wire:click="saveZoneConfig"
                            wire:loading.attr="disabled"
                            class="btn-enterprise-primary text-sm px-4 py-2">
                        Zapisz
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
