{{-- Edit location modal --}}
@if($showEditModal)
    <div class="modal-overlay show" wire:click.self="closeEditModal">
        <div class="audit-modal-dialog max-w-md">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Edytuj lokalizacje</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Kod</label>
                        <input type="text" wire:model="editCode" readonly
                               class="w-full bg-gray-700 border border-gray-600 text-gray-400 text-sm rounded-lg px-3 py-2 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Opis</label>
                        <input type="text" wire:model="editDescription"
                               class="w-full location-input-enterprise"
                               placeholder="Opcjonalny opis lokalizacji">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Notatki</label>
                        <textarea wire:model="editNotes" rows="3"
                                  class="w-full location-input-enterprise"
                                  placeholder="Dodatkowe notatki..."></textarea>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="editIsActive" id="editIsActive"
                               class="checkbox-enterprise">
                        <label for="editIsActive" class="text-sm text-gray-300">Aktywna</label>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="closeEditModal"
                            class="btn-enterprise-secondary text-sm px-4 py-2">
                        Anuluj
                    </button>
                    <button wire:click="saveLocation"
                            wire:loading.attr="disabled"
                            class="btn-enterprise-primary text-sm px-4 py-2">
                        Zapisz
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
