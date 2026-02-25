{{-- BULK REMOVE CATEGORIES MODAL (ETAP_07a FAZA 2.2.2.2.2) --}}
@if($showBulkRemoveCategoriesModal)
<div class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="closeBulkRemoveCategories">
    <div class="bg-gray-800 rounded-lg shadow-2xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-white flex items-center">
                <svg class="w-6 h-6 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Usuń kategorie z zaznaczonych produktów
            </h3>
            <button wire:click="closeBulkRemoveCategories" class="text-gray-400 hover:text-gray-500 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="mb-6">
            <p class="text-sm text-gray-400 mb-4">
                Usuwasz kategorie z <strong class="text-orange-500">{{ count($selectedProducts) }}</strong>
                {{ count($selectedProducts) == 1 ? 'produktu' : (count($selectedProducts) < 5 ? 'produktów' : 'produktów') }}.
            </p>

            {{-- Common Categories List --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-2">
                    Wspólne kategorie (obecne we wszystkich wybranych produktach):
                </label>

                @if(count($commonCategories) > 0)
                    <div class="border border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-900 max-h-96 overflow-y-auto">
                        @foreach($commonCategories as $category)
                            <label class="flex items-center p-2 hover:bg-gray-800 dark:hover:bg-gray-800 rounded cursor-pointer mb-1">
                                <input type="checkbox"
                                       wire:model.live="categoriesToRemove"
                                       value="{{ $category['id'] }}"
                                       class="mr-3 h-4 w-4 text-red-500 border-gray-300 rounded focus:ring-red-500">
                                <span class="text-sm font-medium text-white">
                                    {{ $category['name'] }}
                                </span>
                                @if($category['is_primary_in_any'])
                                    <span class="ml-2 px-2 py-0.5 bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300 text-xs rounded">
                                        Główna w niektórych produktach
                                    </span>
                                @endif
                            </label>
                        @endforeach
                    </div>

                    @if(count($categoriesToRemove) > 0)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            Wybrano do usunięcia: <strong class="text-red-500">{{ count($categoriesToRemove) }}</strong> kategorii
                        </p>
                    @endif
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        Wybrane produkty nie mają wspólnych kategorii
                    </p>
                @endif
            </div>

            {{-- Warning about primary categories --}}
            @if(count($categoriesToRemove) > 0 && collect($commonCategories)->whereIn('id', $categoriesToRemove)->where('is_primary_in_any', true)->count() > 0)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3 mb-4">
                    <p class="text-sm text-yellow-800 dark:text-yellow-300">
                        <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <strong>Uwaga:</strong> Usuwasz kategorię główną z niektórych produktów.
                        Pierwsza pozostała kategoria zostanie automatycznie ustawiona jako główna.
                    </p>
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="flex justify-end space-x-3">
            <button wire:click="closeBulkRemoveCategories"
                    class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-700 border border-gray-600 rounded-lg hover:bg-gray-600 transition-colors">
                Anuluj
            </button>
            <button wire:click="bulkRemoveCategories"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors flex items-center"
                    @if(empty($categoriesToRemove)) disabled @endif>
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Usuń kategorie
            </button>
        </div>
    </div>
</div>
@endif
