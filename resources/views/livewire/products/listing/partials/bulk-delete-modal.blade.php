{{-- BULK DELETE CONFIRMATION MODAL --}}
@if($showBulkDeleteModal)
<div class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="closeBulkDeleteModal">
    <div class="bg-gray-800 rounded-lg shadow-2xl w-full max-w-md p-6">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-white flex items-center">
                <svg class="w-6 h-6 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-1.964-1.333-2.732 0L3.732 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Potwierdzenie usunięcia
            </h3>
            <button wire:click="closeBulkDeleteModal" class="text-gray-400 hover:text-gray-500 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="mb-6">
            <p class="text-gray-300 mb-3">
                Czy na pewno chcesz <span class="font-bold text-red-600">TRWALE USUNĄĆ</span>
                <span class="font-bold text-red-600">{{ $this->selectedCount }}</span>
                {{ $this->selectedCount == 1 ? 'produkt' : ($this->selectedCount < 5 ? 'produkty' : 'produktów') }}?
            </p>
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
                <p class="text-sm text-red-800 dark:text-red-300">
                    <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <strong>UWAGA:</strong> Ta operacja jest <strong>nieodwracalna</strong>!<br>
                    Produkty zostaną <strong>FIZYCZNIE USUNIĘTE</strong> z bazy danych (nie soft delete).<br>
                    Wszystkie powiązane dane (kategorie, ceny, stany magazynowe) również zostaną usunięte.
                </p>
            </div>
        </div>

        {{-- Footer - Actions --}}
        <div class="flex justify-end space-x-3">
            <button wire:click="closeBulkDeleteModal"
                    class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-700 border border-gray-600 rounded-lg hover:bg-gray-600 transition-colors">
                Anuluj
            </button>
            <button wire:click="confirmBulkDelete"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Tak, usuń produkty
            </button>
        </div>
    </div>
</div>
@endif
