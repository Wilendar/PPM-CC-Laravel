{{-- QUICK SEND TO SHOPS MODAL --}}
@if($showQuickSendModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" wire:click="closeQuickSendModal"></div>

        {{-- Modal Content --}}
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="inline-block bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                {{-- Header --}}
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-white">
                        Wyślij produkty na sklepy
                    </h3>
                    <button wire:click="closeQuickSendModal" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Content --}}
                <div class="mb-4">
                    <p class="text-sm text-gray-400 mb-3">
                        Wybierz sklepy, na które chcesz wysłać {{ count($selectedProducts) }} {{ count($selectedProducts) == 1 ? 'produkt' : (count($selectedProducts) < 5 ? 'produkty' : 'produktów') }}:
                    </p>

                    @if(count($this->availableShops) > 0)
                        <div class="space-y-2 max-h-64 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg p-3">
                            @foreach($this->availableShops as $shop)
                                <label class="flex items-center p-2 hover:bg-gray-700 rounded cursor-pointer">
                                    <input type="checkbox"
                                           wire:model="selectedShopsForBulk"
                                           value="{{ $shop->id }}"
                                           class="mr-3 h-4 w-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                    <div class="flex-1">
                                        <div class="text-sm font-medium text-white">
                                            {{ $shop->name }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $shop->url }}
                                        </div>
                                    </div>
                                    <div class="ml-2">
                                        @if($shop->connection_status === 'connected')
                                            <span class="inline-block w-2 h-2 bg-green-500 rounded-full"></span>
                                        @else
                                            <span class="inline-block w-2 h-2 bg-red-500 rounded-full"></span>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                            Brak dostępnych sklepów
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="mt-6 flex justify-between items-center">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ count($selectedShopsForBulk) }} {{ count($selectedShopsForBulk) == 1 ? 'sklep wybrany' : (count($selectedShopsForBulk) < 5 ? 'sklepy wybrane' : 'sklepów wybranych') }}
                    </span>
                    <div class="flex space-x-3">
                        <button wire:click="closeQuickSendModal"
                                class="px-4 py-2 border border-gray-600 text-sm font-medium rounded-lg text-gray-300 bg-gray-800 hover:bg-gray-700 transition-colors">
                            Anuluj
                        </button>
                        <button wire:click="bulkSendToShops"
                                class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors"
                                @if(empty($selectedShopsForBulk)) disabled @endif>
                            Wyślij produkty
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
