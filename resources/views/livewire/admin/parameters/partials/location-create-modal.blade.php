{{-- Create location modal --}}
@if($showCreateModal)
    <div class="modal-overlay show" wire:click.self="closeCreateModal">
        <div class="audit-modal-dialog max-w-md">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Nowa lokalizacja</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Kod lokalizacji</label>
                        <input type="text" wire:model="createCode"
                               class="w-full location-input-enterprise"
                               placeholder="np. A-01-03, SKLEP, B_02_01">
                        @error('createCode')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-gray-500 text-xs mt-1">Format zostanie automatycznie rozpoznany (kodowany, myslnikowy, nazwany...)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Opis</label>
                        <input type="text" wire:model="createDescription"
                               class="w-full location-input-enterprise"
                               placeholder="Opcjonalny opis lokalizacji">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Notatki</label>
                        <textarea wire:model="createNotes" rows="2"
                                  class="w-full location-input-enterprise"
                                  placeholder="Dodatkowe notatki..."></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="closeCreateModal"
                            class="btn-enterprise-secondary text-sm px-4 py-2">
                        Anuluj
                    </button>
                    <button wire:click="createLocation"
                            wire:loading.attr="disabled"
                            class="btn-enterprise-primary text-sm px-4 py-2">
                        Utworz
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
