{{-- BULK MOVE CATEGORIES MODAL (ETAP_07a FAZA 2.2.2.2.3) --}}
@if($showBulkMoveCategoriesModal)
<div class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="closeBulkMoveCategories">
    <div class="bg-gray-800 rounded-lg shadow-2xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-white flex items-center">
                <svg class="w-6 h-6 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                Przenieś produkty między kategoriami
            </h3>
            <button wire:click="closeBulkMoveCategories" class="text-gray-400 hover:text-gray-500 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="mb-6">
            <p class="text-sm text-gray-400 mb-4">
                Przeniesiesz <strong class="text-orange-500">{{ count($selectedProducts) }}</strong>
                {{ count($selectedProducts) == 1 ? 'produkt' : (count($selectedProducts) < 5 ? 'produkty' : 'produktów') }}
                z jednej kategorii do drugiej.
            </p>

            {{-- FROM Category --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-2">
                    Z kategorii (źródłowa):
                </label>
                <select wire:model.live="fromCategoryId"
                        class="form-input w-full rounded-lg border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Wybierz kategorię źródłową</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">
                            {{ str_repeat('--', $category->level) }} {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- TO Category --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-2">
                    Do kategorii (docelowa):
                </label>
                <select wire:model.live="toCategoryId"
                        class="form-input w-full rounded-lg border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Wybierz kategorię docelową</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @if($category->id == $fromCategoryId) disabled @endif>
                            {{ str_repeat('--', $category->level) }} {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Move Mode --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-2">
                    Tryb przenoszenia:
                </label>
                <div class="space-y-2">
                    <label class="flex items-start p-3 border border-gray-600 rounded-lg cursor-pointer hover:bg-gray-700 {{ $moveMode === 'replace' ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-500' : '' }}">
                        <input type="radio"
                               wire:model.live="moveMode"
                               value="replace"
                               class="mt-1 mr-3 text-blue-500 border-gray-300 focus:ring-blue-500">
                        <div class="flex-1">
                            <div class="font-medium text-white">
                                Zamień kategorię
                            </div>
                            <div class="text-xs text-gray-400">
                                Usuń kategorię źródłową i dodaj docelową (produkt przestanie być w starej kategorii)
                            </div>
                        </div>
                    </label>

                    <label class="flex items-start p-3 border border-gray-600 rounded-lg cursor-pointer hover:bg-gray-700 {{ $moveMode === 'add_keep' ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-500' : '' }}">
                        <input type="radio"
                               wire:model.live="moveMode"
                               value="add_keep"
                               class="mt-1 mr-3 text-blue-500 border-gray-300 focus:ring-blue-500">
                        <div class="flex-1">
                            <div class="font-medium text-white">
                                Dodaj i zachowaj obie
                            </div>
                            <div class="text-xs text-gray-400">
                                Dodaj kategorię docelową zachowując źródłową (produkt będzie w obu kategoriach)
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Info about products without FROM category --}}
            @if($fromCategoryId && $toCategoryId)
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                    <p class="text-sm text-blue-800 dark:text-blue-300">
                        <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <strong>Uwaga:</strong> Operacja dotyczy tylko produktów posiadających kategorię źródłową.
                        Produkty bez tej kategorii zostaną pominięte.
                    </p>
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="flex justify-end space-x-3">
            <button wire:click="closeBulkMoveCategories"
                    class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-700 border border-gray-600 rounded-lg hover:bg-gray-600 transition-colors">
                Anuluj
            </button>
            <button wire:click="bulkMoveCategories"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors flex items-center"
                    @if(!$fromCategoryId || !$toCategoryId) disabled @endif>
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                {{ $moveMode === 'replace' ? 'Przenieś' : 'Skopiuj' }} produkty
            </button>
        </div>
    </div>
</div>
@endif
