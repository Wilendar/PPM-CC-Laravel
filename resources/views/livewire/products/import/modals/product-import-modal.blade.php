{{--
    ProductImportModal - Unified import modal with CSV and Column modes
    Combines CSV text/file import with column-based spreadsheet-like entry.
    Shared switches at the bottom for common product settings.

    Properties expected from Livewire component:
    - $showModal (bool)
    - $activeMode ('csv' | 'column')
    - $switchShopInternet (bool)
    - $switchSplitPayment (bool)
    - $switchVariantProduct (bool)
    - $editingPendingProductId (?int)
    - CSV: $csvTextInput, $csvMappingStep, $csvPreviewStep, $csvTotalRows
    - Column: $rows (array), $activeColumns (array)
--}}
<div>
    <div x-show="$wire.showModal"
         x-cloak
         x-trap.noscroll="$wire.showModal"
         x-on:keydown.escape.window="$wire.closeModal()"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         role="dialog"
         aria-modal="true"
         aria-labelledby="product-import-modal-title"
         class="fixed inset-0 import-modal-overlay">

            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm"
                 wire:click="closeModal"></div>

            {{-- Modal Content --}}
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-7xl bg-gray-800 rounded-xl shadow-2xl border border-gray-700
                            flex flex-col max-h-[90vh]"
                     x-on:click.stop>

                    {{-- ============================================================
                         HEADER: Title + Mode Tabs + Close Button
                         ============================================================ --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700 bg-gray-800/80 flex-shrink-0">
                        {{-- Title --}}
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-amber-500/15">
                                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                            </div>
                            <h2 id="product-import-modal-title" class="text-lg font-semibold text-white">
                                @if($editingPendingProductId)
                                    Edytuj produkt
                                @else
                                    Importuj produkty
                                @endif
                            </h2>
                        </div>

                        {{-- Mode Tabs --}}
                        <div class="flex items-center gap-2 bg-gray-900/50 rounded-lg p-1">
                            <button wire:click="switchMode('csv')"
                                    class="px-4 py-2 rounded-md text-sm font-medium transition-colors
                                           {{ $activeMode === 'csv'
                                               ? 'bg-amber-600 text-white shadow-sm'
                                               : 'text-gray-400 hover:text-gray-200 hover:bg-gray-700/50' }}">
                                <svg class="w-4 h-4 inline-block mr-1.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                CSV
                            </button>
                            <button wire:click="switchMode('column')"
                                    class="px-4 py-2 rounded-md text-sm font-medium transition-colors
                                           {{ $activeMode === 'column'
                                               ? 'bg-amber-600 text-white shadow-sm'
                                               : 'text-gray-400 hover:text-gray-200 hover:bg-gray-700/50' }}">
                                <svg class="w-4 h-4 inline-block mr-1.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                Kolumnowy
                            </button>
                        </div>

                        {{-- Close Button --}}
                        <button wire:click="closeModal"
                                class="p-2 text-gray-400 hover:text-white transition-colors rounded-lg hover:bg-gray-700/50"
                                aria-label="Zamknij modal">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- ============================================================
                         BODY: Mode-specific content
                         ============================================================ --}}
                    <div class="flex-1 overflow-y-auto p-6">
                        {{-- Error Messages (session flash) --}}
                        @if(session()->has('import-error'))
                            <div class="mb-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="text-sm text-red-300">{{ session('import-error') }}</p>
                                </div>
                            </div>
                        @endif

                        {{-- Error Messages (Livewire validation) --}}
                        @error('columnImport')
                            <div class="mb-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="text-sm text-red-300">{{ $message }}</p>
                                </div>
                            </div>
                        @enderror
                        @error('csvImport')
                            <div class="mb-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="text-sm text-red-300">{{ $message }}</p>
                                </div>
                            </div>
                        @enderror

                        @if($activeMode === 'csv')
                            @include('livewire.products.import.modals.partials.csv-mode')
                        @else
                            @include('livewire.products.import.modals.partials.column-mode')
                        @endif
                    </div>

                    {{-- ============================================================
                         FOOTER: Switches + Action Buttons
                         ============================================================ --}}
                    {{-- Switches Section --}}
                    <div class="border-t border-gray-700 px-6 py-3 bg-gray-800/50 flex-shrink-0">
                        <div class="flex items-center gap-6 flex-wrap">
                            {{-- Switch: Sklep Internetowy --}}
                            <label class="flex items-center gap-2.5 cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox"
                                           wire:model.live="switchShopInternet"
                                           class="sr-only peer">
                                    <div class="w-9 h-5 bg-gray-600 peer-checked:bg-amber-500 rounded-full transition-colors"></div>
                                    <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4"></div>
                                </div>
                                <span class="text-sm text-gray-300 group-hover:text-gray-200 transition-colors">Sklep Internetowy</span>
                            </label>

                            {{-- Switch: Podzielona platnosc --}}
                            <label class="flex items-center gap-2.5 cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox"
                                           wire:model.live="switchSplitPayment"
                                           class="sr-only peer">
                                    <div class="w-9 h-5 bg-gray-600 peer-checked:bg-amber-500 rounded-full transition-colors"></div>
                                    <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4"></div>
                                </div>
                                <span class="text-sm text-gray-300 group-hover:text-gray-200 transition-colors">Podzielona platnosc</span>
                            </label>

                            {{-- Switch: Produkt Wariantowy --}}
                            <label class="flex items-center gap-2.5 cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox"
                                           wire:model.live="switchVariantProduct"
                                           class="sr-only peer">
                                    <div class="w-9 h-5 bg-gray-600 peer-checked:bg-cyan-500 rounded-full transition-colors"></div>
                                    <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4"></div>
                                </div>
                                <span class="text-sm text-gray-300 group-hover:text-gray-200 transition-colors">Produkt Wariantowy</span>
                            </label>

                            {{-- Variant button (shown when switchVariantProduct is true) --}}
                            @if($switchVariantProduct)
                                <button wire:click="openVariantModalFromImport"
                                        class="px-3 py-1.5 bg-cyan-700 hover:bg-cyan-600 rounded-lg text-sm text-white font-medium transition-colors flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                    Przejdz do wariantow
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="border-t border-gray-700 px-6 py-4 bg-gray-800/80 flex-shrink-0">
                        <div class="flex items-center justify-between">
                            {{-- Left: Row/Product count --}}
                            <div class="text-sm text-gray-400">
                                @if($activeMode === 'column')
                                    <span class="text-gray-300 font-medium">{{ count($rows) }}</span>
                                    {{ count($rows) == 1 ? 'produkt' : 'produktow' }}
                                @elseif($activeMode === 'csv' && ($csvPreviewStep ?? false))
                                    <span class="text-gray-300 font-medium">{{ $csvTotalRows ?? 0 }}</span>
                                    produktow do importu
                                @endif
                            </div>

                            {{-- Right: Buttons --}}
                            <div class="flex items-center gap-3">
                                <button wire:click="closeModal"
                                        class="btn-enterprise-ghost">
                                    Anuluj
                                </button>

                                @if($activeMode === 'column')
                                    <button wire:click="importColumnRows"
                                            wire:loading.attr="disabled"
                                            wire:target="importColumnRows"
                                            class="btn-enterprise-primary">
                                        <span wire:loading.remove wire:target="importColumnRows">
                                            {{ $editingPendingProductId ? 'Zapisz zmiany' : 'Importuj' }}
                                        </span>
                                        <span wire:loading wire:target="importColumnRows" class="flex items-center gap-2">
                                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                            </svg>
                                            Importowanie...
                                        </span>
                                    </button>
                                @elseif($activeMode === 'csv' && ($csvPreviewStep ?? false))
                                    <button wire:click="importCsvRows"
                                            wire:loading.attr="disabled"
                                            wire:target="importCsvRows"
                                            class="btn-enterprise-primary">
                                        <span wire:loading.remove wire:target="importCsvRows">
                                            Importuj
                                        </span>
                                        <span wire:loading wire:target="importCsvRows" class="flex items-center gap-2">
                                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                            </svg>
                                            Importowanie...
                                        </span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
