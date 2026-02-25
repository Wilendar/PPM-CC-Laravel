{{-- IMPORT FROM PRESTASHOP MODAL --}}
@if($showImportModal)
<div class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center layer-overlay">
    <div class="bg-gray-800 rounded-lg shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden">

        {{-- Stagger animation moved to import-panel.css --}}

        {{-- Modal Header --}}
        <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-white">
                <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Import produktów z PrestaShop
            </h3>
            <button wire:click="closeImportModal" class="text-gray-400 hover:text-gray-300 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Modal Body --}}
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">

            {{-- Step 1: Shop Selection --}}
            @if(!$importShopId)
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-gray-700 text-xs font-bold text-gray-300 mr-1">1</span> Wybierz sklep PrestaShop
                    </label>
                    {{-- CRITICAL FIX: Use computed property $this->availableShops instead of inline query --}}
                    <select wire:model.live="importShopId"
                            class="form-input-enterprise w-full rounded-lg">
                        <option value="">-- Wybierz sklep --</option>
                        @foreach($this->availableShops as $shop)
                            <option value="{{ $shop->id }}">
                                {{ $shop->name }}
                                @if($shop->version)
                                    (PrestaShop {{ $shop->version }})
                                @endif
                            </option>
                        @endforeach
                    </select>

                    {{-- CRITICAL FIX: Visual confirmation after shop selection --}}
                    @if($importShopId)
                        <div class="mt-2 p-2 bg-green-900/20 rounded text-sm text-green-300 border border-green-800">
                            <svg class="w-4 h-4 inline-block mr-1 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            Wybrany sklep: <strong>{{ $this->availableShops->find($importShopId)->name ?? 'N/A' }}</strong>
                        </div>
                    @endif
                </div>
            @else
                {{-- Shop Selected - Show mode tabs --}}
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <span class="text-sm text-gray-400">Sklep:</span>
                            <strong class="text-white ml-2">
                                {{ $this->availableShops->find($importShopId)->name ?? 'N/A' }}
                            </strong>
                        </div>
                        <button wire:click="resetShopSelection" class="text-sm text-orange-500 hover:underline">
                            Zmień sklep
                        </button>
                    </div>

                    {{-- Mode Tabs --}}
                    <div class="flex gap-1 mb-4 bg-gray-900/50 rounded-lg p-1">
                        <button wire:click="$set('importMode', 'all')"
                                class="ps-import-tab {{ $importMode === 'all' ? 'ps-import-tab--active' : 'ps-import-tab--inactive' }}">
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            Wszystkie
                        </button>
                        <button wire:click="$set('importMode', 'category')"
                                class="ps-import-tab {{ $importMode === 'category' ? 'ps-import-tab--active' : 'ps-import-tab--inactive' }}">
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                            Kategoria
                        </button>
                        <button wire:click="$set('importMode', 'individual')"
                                class="ps-import-tab {{ $importMode === 'individual' ? 'ps-import-tab--active' : 'ps-import-tab--inactive' }}">
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Wybrane produkty
                        </button>
                    </div>

                    {{-- MODE: All Products --}}
                    @if($importMode === 'all')
                        @include('livewire.products.listing.partials.import-prestashop-mode-all')
                    @endif

                    {{-- MODE: Category --}}
                    @if($importMode === 'category')
                        @include('livewire.products.listing.partials.import-prestashop-mode-category')
                    @endif

                    {{-- MODE: Individual Products --}}
                    @if($importMode === 'individual')
                        @include('livewire.products.listing.partials.import-prestashop-mode-individual')
                    @endif
                </div>
            @endif
        </div>

        {{-- Modal Footer --}}
        <div class="px-6 py-4 border-t border-gray-700 flex justify-end">
            <button wire:click="closeImportModal" class="btn-enterprise-secondary">
                Anuluj
            </button>
        </div>
    </div>
</div>
@endif
