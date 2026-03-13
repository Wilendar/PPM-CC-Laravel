{{-- Zone management modal --}}
@if($showZoneModal)
    <div class="modal-overlay show" wire:click.self="closeZoneModal">
        <div class="audit-modal-dialog max-w-sm">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-white mb-4">
                    {{ $editingZone ? 'Edytuj strefe' : 'Nowa strefa' }}
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Nazwa strefy</label>
                        <input type="text" wire:model="zoneName"
                               class="w-full location-input-enterprise"
                               placeholder="np. A, B, WYSYLKA">
                        @error('zoneName')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="closeZoneModal"
                            class="btn-enterprise-secondary text-sm px-4 py-2">
                        Anuluj
                    </button>
                    <button wire:click="saveZone"
                            wire:loading.attr="disabled"
                            class="btn-enterprise-primary text-sm px-4 py-2">
                        Zapisz
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
