{{--
    resources/views/livewire/products/management/partials/create-category-modal.blade.php

    ETAP_07b FAZA 4.2.3: Create Category Modal
    - Creates new category in PrestaShop via API
    - Only available in Shop TAB context (not Default)
    - Parent category selection from PrestaShop tree

    @uses $showCreateCategoryModal - bool, controls visibility
    @uses $createCategoryShopId - int|null, shop context
    @uses $newCategoryName - string, category name input
    @uses $newCategoryParentId - int|null, parent category
--}}

@if($showCreateCategoryModal)
<div class="fixed inset-0 z-50 flex items-center justify-center"
     x-data="{ isOpen: @entangle('showCreateCategoryModal') }"
     x-show="isOpen"
     x-cloak
     role="dialog"
     aria-modal="true"
     aria-labelledby="create-category-title">

    {{-- Background Overlay --}}
    <div x-show="isOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="$wire.closeCreateCategoryModal()"
         class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity"></div>

    {{-- Modal Container --}}
    <div x-show="isOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         @click.stop
         class="relative w-full max-w-md p-6 mx-4 bg-gradient-to-br from-gray-800 via-gray-900 to-gray-800 rounded-xl shadow-2xl border border-green-500/30">

        {{-- Modal Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h4 class="text-lg font-bold text-white flex items-center gap-2" id="create-category-title">
                    <svg class="w-6 h-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                    </svg>
                    Nowa kategoria
                </h4>
                <p class="text-xs text-gray-400 mt-1">
                    Utworzy kategorie w PrestaShop
                    @if($createCategoryShopId)
                        @php
                            $shopName = \App\Models\PrestaShopShop::find($createCategoryShopId)?->name ?? 'Sklep';
                        @endphp
                        ({{ $shopName }})
                    @endif
                </p>
            </div>
            <button @click="$wire.closeCreateCategoryModal()"
                    type="button"
                    class="rounded-lg p-2 hover:bg-gray-700/50 transition-colors duration-200">
                <svg class="w-6 h-6 text-gray-300 hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        {{-- Form Fields --}}
        <div class="space-y-4">
            {{-- Category Name --}}
            <div>
                <label for="newCategoryName" class="block text-sm font-medium text-gray-300 mb-2">
                    Nazwa kategorii <span class="text-red-400">*</span>
                </label>
                <input type="text"
                       id="newCategoryName"
                       wire:model.live="newCategoryName"
                       placeholder="np. Elektronika, Odzież, Akcesoria..."
                       class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                @error('newCategoryName')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Parent Category --}}
            <div>
                <label for="newCategoryParentId" class="block text-sm font-medium text-gray-300 mb-2">
                    Kategoria nadrzedna (opcjonalnie)
                </label>
                <select id="newCategoryParentId"
                        wire:model.live="newCategoryParentId"
                        class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                    <option value="">-- Brak (kategoria glowna) --</option>
                    @foreach($this->parentCategoryOptions as $categoryId => $categoryName)
                        <option value="{{ $categoryId }}">{{ $categoryName }}</option>
                    @endforeach
                </select>
                @error('newCategoryParentId')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Info Note --}}
            <div class="p-3 bg-blue-900/20 border border-blue-500/30 rounded-lg">
                <p class="text-xs text-blue-300 flex items-start gap-2">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <span>
                        Kategoria zostanie utworzona w PrestaShop i automatycznie zmapowana do PPM.
                        Po utworzeniu pojawi sie w drzewie kategorii.
                    </span>
                </p>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-gray-700/50">
            <button @click="$wire.closeCreateCategoryModal()"
                    type="button"
                    class="px-4 py-2 text-sm font-semibold text-gray-300 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors duration-200">
                Anuluj
            </button>
            <button wire:click="createNewCategory"
                    type="button"
                    wire:loading.attr="disabled"
                    wire:target="createNewCategory"
                    class="px-4 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors duration-200 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                <span wire:loading.remove wire:target="createNewCategory">
                    <svg class="w-5 h-5 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path>
                    </svg>
                    Utworz kategorie
                </span>
                <span wire:loading wire:target="createNewCategory" class="flex items-center gap-2">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Tworzenie...
                </span>
            </button>
        </div>
    </div>
</div>
@endif
