{{-- BULK ASSIGN CATEGORIES MODAL (ETAP_07a FAZA 2.2.2.2.1) --}}
@if($showBulkAssignCategoriesModal)
<div class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="closeBulkAssignCategories">
    <div class="bg-gray-800 rounded-lg shadow-2xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-white flex items-center">
                <svg class="w-6 h-6 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                Przypisz kategorie do zaznaczonych produktów
            </h3>
            <button wire:click="closeBulkAssignCategories" class="text-gray-400 hover:text-gray-500 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="mb-6">
            <p class="text-sm text-gray-400 mb-4">
                Przypisujesz kategorie do <strong class="text-orange-500">{{ count($selectedProducts) }}</strong>
                {{ count($selectedProducts) == 1 ? 'produktu' : (count($selectedProducts) < 5 ? 'produktów' : 'produktów') }}.
                Wybierz maksymalnie 10 kategorii.
            </p>

            {{-- Category Picker - Multi-select Tree --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-2">
                    Wybierz kategorie:
                </label>
                <div class="border border-gray-600 rounded-lg p-4 max-h-96 overflow-y-auto bg-gray-50 dark:bg-gray-900">
                    @if(count($categories) > 0)
                        @foreach($categories as $category)
                            @php
                                $isSelected = in_array($category->id, $selectedCategoriesForBulk);
                                $indent = $category->level * 1.5;
                            @endphp
                            <label class="flex items-center p-2 hover:bg-gray-800 dark:hover:bg-gray-800 rounded cursor-pointer mb-1"
                                   style="padding-left: {{ $indent }}rem;">
                                <input type="checkbox"
                                       wire:model.live="selectedCategoriesForBulk"
                                       value="{{ $category->id }}"
                                       class="mr-3 h-4 w-4 text-orange-500 border-gray-300 rounded focus:ring-orange-500">
                                <span class="text-sm font-medium text-white">
                                    {{ $category->name }}
                                </span>
                                <span class="ml-2 text-xs text-gray-500">
                                    (poziom {{ $category->level }})
                                </span>
                            </label>
                        @endforeach
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                            Brak dostępnych kategorii
                        </p>
                    @endif
                </div>

                @if(count($selectedCategoriesForBulk) > 0)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        Wybrano: <strong class="text-orange-500">{{ count($selectedCategoriesForBulk) }}</strong> / 10 kategorii
                    </p>
                @endif

                @if(count($selectedCategoriesForBulk) > 10)
                    <p class="text-xs text-red-600 dark:text-red-400 mt-1">
                        Przekroczono limit 10 kategorii!
                    </p>
                @endif
            </div>

            {{-- Primary Category Selection --}}
            @if(count($selectedCategoriesForBulk) > 0)
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        Kategoria główna (opcjonalnie):
                    </label>
                    <select wire:model.live="primaryCategoryForBulk"
                            class="form-input w-full rounded-lg border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Bez kategorii głównej</option>
                        @foreach($categories->whereIn('id', $selectedCategoriesForBulk) as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Kategoria główna będzie używana do breadcrumbs i URL produktu
                    </p>
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="flex justify-end space-x-3">
            <button wire:click="closeBulkAssignCategories"
                    class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-700 border border-gray-600 rounded-lg hover:bg-gray-600 transition-colors">
                Anuluj
            </button>
            <button wire:click="bulkAssignCategories"
                    class="px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg transition-colors flex items-center"
                    @if(empty($selectedCategoriesForBulk) || count($selectedCategoriesForBulk) > 10) disabled @endif>
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                Przypisz kategorie
            </button>
        </div>
    </div>
</div>
@endif
