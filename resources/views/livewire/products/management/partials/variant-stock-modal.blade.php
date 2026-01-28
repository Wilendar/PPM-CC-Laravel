{{-- resources/views/livewire/products/management/partials/variant-stock-modal.blade.php --}}
{{-- Modal for editing variant stock - ETAP_14 with column locks --}}

@if($showVariantStockModal && $selectedVariantIdForStock)
<template x-teleport="body">
    <div class="fixed inset-0 overflow-y-auto" style="z-index: 9999;"
         x-data="{
             showUnlockModal: false
         }"
         x-on:show-variant-stock-unlock-modal.window="showUnlockModal = true"
         x-on:close-variant-stock-unlock-modal.window="showUnlockModal = false"
         @keydown.escape.window="$wire.closeVariantStockModal()">

        {{-- Overlay --}}
        <div class="fixed inset-0 bg-black/70 transition-opacity" @click="$wire.closeVariantStockModal()"></div>

        {{-- Modal Panel --}}
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-5xl bg-gray-800 rounded-xl shadow-2xl border border-gray-700 transform transition-all"
                 @click.stop>

                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-purple-900/30 rounded-lg">
                            <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-white">Stany magazynowe wariantu</h3>
                            <p class="text-sm text-gray-400">{{ $selectedVariantForStockData['sku'] ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <button type="button"
                            wire:click="closeVariantStockModal"
                            class="text-gray-400 hover:text-white transition-colors p-2 hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Sync Lock Banner (if parent sync active) --}}
                @if($this->isVariantModalLocked())
                <div class="px-6 py-3 bg-amber-900/30 border-b border-amber-700/50">
                    <div class="flex items-center gap-2 text-amber-400 text-sm">
                        <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span class="font-medium">{{ $this->getVariantModalLockReason() }}</span>
                        <span class="text-amber-500">- edycja zablokowana</span>
                    </div>
                </div>
                @else
                {{-- ERP Warning Banner --}}
                <div class="px-6 py-3 bg-amber-900/20 border-b border-amber-700/50">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div class="text-sm text-amber-200">
                            <p class="font-medium mb-1">Synchronizacja z systemami ERP</p>
                            <p class="text-amber-300/80">
                                Aby edytowac wartosci, kliknij ikone
                                <svg class="w-4 h-4 inline mx-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                przy naglowku kolumny.
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Column Lock Status Summary --}}
                @if(!$this->isVariantModalLocked())
                <div class="px-6 py-2 bg-gray-900/50 border-b border-gray-700 flex items-center justify-end gap-2">
                    @foreach(['quantity' => 'Stan', 'reserved' => 'Rez.', 'minimum' => 'Min.'] as $col => $label)
                        @php $isUnlocked = $this->isVariantStockColumnUnlocked($col); @endphp
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs {{ $isUnlocked ? 'bg-green-900/30 text-green-400' : 'bg-gray-700 text-gray-400' }}">
                            @if($isUnlocked)
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                </svg>
                            @else
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            @endif
                            {{ $label }}
                        </span>
                    @endforeach
                </div>
                @endif

                {{-- Content --}}
                <div class="px-4 py-4 max-h-[60vh] overflow-y-auto overflow-x-auto {{ $this->isVariantModalLocked() ? 'opacity-60' : '' }}">
                    {{-- Stock Table --}}
                    <table class="variant-modal-table w-full text-sm min-w-[700px]">
                        <thead class="text-xs text-gray-400 uppercase bg-gray-900/50">
                            <tr>
                                <th scope="col" class="px-3 py-2 text-left" style="min-width: 180px;">Magazyn</th>

                                {{-- Stan - with lock button --}}
                                <th scope="col" class="px-2 py-2 text-right" style="width: 100px;">
                                    <div class="flex items-center justify-between gap-1">
                                        <span>Stan</span>
                                        @if(!$this->isVariantModalLocked())
                                            <button type="button"
                                                    wire:click="{{ $this->isVariantStockColumnUnlocked('quantity') ? 'lockVariantStockColumn(\'quantity\')' : 'requestVariantStockColumnUnlock(\'quantity\')' }}"
                                                    class="p-1 rounded transition-colors {{ $this->isVariantStockColumnUnlocked('quantity') ? 'text-green-400 hover:bg-green-900/30' : 'text-gray-500 hover:bg-gray-700' }}"
                                                    title="{{ $this->isVariantStockColumnUnlocked('quantity') ? 'Zablokuj kolumne' : 'Odblokuj do edycji' }}">
                                                @if($this->isVariantStockColumnUnlocked('quantity'))
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                                    </svg>
                                                @endif
                                            </button>
                                        @endif
                                    </div>
                                </th>

                                {{-- Zarezerwowane - with lock button --}}
                                <th scope="col" class="px-2 py-2 text-right" style="width: 100px;">
                                    <div class="flex items-center justify-between gap-1">
                                        <span>Rezerw.</span>
                                        @if(!$this->isVariantModalLocked())
                                            <button type="button"
                                                    wire:click="{{ $this->isVariantStockColumnUnlocked('reserved') ? 'lockVariantStockColumn(\'reserved\')' : 'requestVariantStockColumnUnlock(\'reserved\')' }}"
                                                    class="p-1 rounded transition-colors {{ $this->isVariantStockColumnUnlocked('reserved') ? 'text-green-400 hover:bg-green-900/30' : 'text-gray-500 hover:bg-gray-700' }}"
                                                    title="{{ $this->isVariantStockColumnUnlocked('reserved') ? 'Zablokuj kolumne' : 'Odblokuj do edycji' }}">
                                                @if($this->isVariantStockColumnUnlocked('reserved'))
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                                    </svg>
                                                @endif
                                            </button>
                                        @endif
                                    </div>
                                </th>

                                {{-- Minimum - with lock button --}}
                                <th scope="col" class="px-2 py-2 text-right" style="width: 100px;">
                                    <div class="flex items-center justify-between gap-1">
                                        <span>Min.</span>
                                        @if(!$this->isVariantModalLocked())
                                            <button type="button"
                                                    wire:click="{{ $this->isVariantStockColumnUnlocked('minimum') ? 'lockVariantStockColumn(\'minimum\')' : 'requestVariantStockColumnUnlock(\'minimum\')' }}"
                                                    class="p-1 rounded transition-colors {{ $this->isVariantStockColumnUnlocked('minimum') ? 'text-green-400 hover:bg-green-900/30' : 'text-gray-500 hover:bg-gray-700' }}"
                                                    title="{{ $this->isVariantStockColumnUnlocked('minimum') ? 'Zablokuj kolumne' : 'Odblokuj do edycji' }}">
                                                @if($this->isVariantStockColumnUnlocked('minimum'))
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                                    </svg>
                                                @endif
                                            </button>
                                        @endif
                                    </div>
                                </th>

                                <th scope="col" class="px-2 py-2 text-left" style="width: 120px;">Lokalizacja</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($variantModalStock as $warehouseId => $stockData)
                                @php
                                    $qtyUnlocked = $this->isVariantStockColumnUnlocked('quantity');
                                    $resUnlocked = $this->isVariantStockColumnUnlocked('reserved');
                                    $minUnlocked = $this->isVariantStockColumnUnlocked('minimum');
                                    $isLocked = $this->isVariantModalLocked();
                                @endphp
                                <tr class="border-b border-gray-700/50 hover:bg-gray-700/30">
                                    {{-- Warehouse Name --}}
                                    <td class="px-3 py-2">
                                        <span class="font-medium text-white text-sm">{{ $stockData['warehouse_name'] ?? 'Magazyn #'.$warehouseId }}</span>
                                    </td>

                                    {{-- Quantity --}}
                                    <td class="px-2 py-2">
                                        <input type="number"
                                               step="1"
                                               min="0"
                                               wire:model.defer="variantModalStock.{{ $warehouseId }}.quantity"
                                               @class([
                                                   'w-full border text-sm rounded-lg px-3 py-2 text-right',
                                                   'bg-gray-800 border-gray-700 text-gray-400 cursor-not-allowed' => !$qtyUnlocked || $isLocked,
                                                   'bg-gray-700 border-gray-600 text-white focus:ring-blue-500 focus:border-blue-500' => $qtyUnlocked && !$isLocked,
                                               ])
                                               placeholder="0"
                                               {{ (!$qtyUnlocked || $isLocked) ? 'readonly' : '' }}>
                                    </td>

                                    {{-- Reserved --}}
                                    <td class="px-2 py-2">
                                        <input type="number"
                                               step="1"
                                               min="0"
                                               wire:model.defer="variantModalStock.{{ $warehouseId }}.reserved"
                                               @class([
                                                   'w-full border text-sm rounded-lg px-3 py-2 text-right',
                                                   'bg-gray-800 border-gray-700 text-gray-500 cursor-not-allowed' => !$resUnlocked || $isLocked,
                                                   'bg-gray-700 border-gray-600 text-orange-400 focus:ring-blue-500 focus:border-blue-500' => $resUnlocked && !$isLocked,
                                               ])
                                               placeholder="0"
                                               {{ (!$resUnlocked || $isLocked) ? 'readonly' : '' }}>
                                    </td>

                                    {{-- Minimum --}}
                                    <td class="px-2 py-2">
                                        <input type="number"
                                               step="1"
                                               min="0"
                                               wire:model.defer="variantModalStock.{{ $warehouseId }}.minimum"
                                               @class([
                                                   'w-full border text-sm rounded-lg px-3 py-2 text-right',
                                                   'bg-gray-800 border-gray-700 text-gray-400 cursor-not-allowed' => !$minUnlocked || $isLocked,
                                                   'bg-gray-700 border-gray-600 text-white focus:ring-blue-500 focus:border-blue-500' => $minUnlocked && !$isLocked,
                                               ])
                                               placeholder="0"
                                               {{ (!$minUnlocked || $isLocked) ? 'readonly' : '' }}>
                                    </td>

                                    {{-- Location (always editable unless parent sync active) --}}
                                    <td class="px-2 py-2">
                                        <input type="text"
                                               wire:model.defer="variantModalStock.{{ $warehouseId }}.location"
                                               class="variant-modal-location-input w-full {{ $isLocked ? 'bg-gray-800 border-gray-700 text-gray-400 cursor-not-allowed' : '' }}"
                                               placeholder="A1-R2"
                                               {{ $isLocked ? 'readonly' : '' }}>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                        Brak magazynow w systemie.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-700 flex items-center justify-end gap-3">
                    <button type="button"
                            wire:click="closeVariantStockModal"
                            class="btn-enterprise-secondary px-4 py-2 text-sm font-medium rounded-lg">
                        Anuluj
                    </button>
                    <button type="button"
                            wire:click="saveVariantModalStock"
                            wire:loading.attr="disabled"
                            {{ $this->isVariantModalLocked() ? 'disabled' : '' }}
                            class="btn-enterprise-primary px-4 py-2 text-sm font-medium rounded-lg inline-flex items-center gap-2 {{ $this->isVariantModalLocked() ? 'opacity-50 cursor-not-allowed' : '' }}">
                        <span wire:loading.remove wire:target="saveVariantModalStock">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                        <span wire:loading wire:target="saveVariantModalStock">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                        {{ $this->isVariantModalLocked() ? 'Zablokowane' : 'Zapisz zmiany' }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Unlock Confirmation Modal --}}
        <div x-show="showUnlockModal"
             x-cloak
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto" style="z-index: 10000;">
            <div class="flex items-center justify-center min-h-screen px-4">
                {{-- Backdrop --}}
                <div class="fixed inset-0 bg-black/60"
                     x-on:click="$wire.cancelVariantStockColumnUnlock()"></div>

                {{-- Modal Content --}}
                <div class="relative bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6 border border-gray-700"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95">

                    <div class="flex items-start gap-4">
                        {{-- Warning Icon --}}
                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-amber-900/50 flex items-center justify-center">
                            <svg class="w-6 h-6 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>

                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-white">
                                Potwierdzenie odblokowania
                            </h3>
                            <p class="mt-2 text-gray-300 text-sm">
                                Odblokowanie tej kolumny spowoduje mozliwosc edycji
                                i <strong class="text-amber-400">synchronizacji zmian do systemu ERP</strong>
                                (Subiekt GT, Baselinker).
                            </p>
                            <p class="mt-3 text-amber-300 text-sm font-medium">
                                Czy na pewno chcesz kontynuowac?
                            </p>
                        </div>
                    </div>

                    {{-- Buttons --}}
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button"
                                wire:click="cancelVariantStockColumnUnlock"
                                class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-700 border border-gray-600 rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors">
                            Anuluj
                        </button>
                        <button type="button"
                                wire:click="confirmVariantStockColumnUnlock"
                                class="px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 transition-colors">
                            Potwierdz odblokowanie
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
@endif
