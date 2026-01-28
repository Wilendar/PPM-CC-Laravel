{{-- resources/views/livewire/products/management/partials/variant-stock-modal.blade.php --}}
{{-- Modal for editing variant stock - ETAP_14 --}}

@if($showVariantStockModal && $selectedVariantIdForStock)
<template x-teleport="body">
    <div class="fixed inset-0 overflow-y-auto" style="z-index: 9999;" @keydown.escape.window="$wire.closeVariantStockModal()">
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

                {{-- Content --}}
                <div class="px-4 py-4 max-h-[60vh] overflow-y-auto overflow-x-auto">
                    {{-- Stock Table --}}
                    <table class="variant-modal-table w-full text-sm min-w-[700px]">
                        <thead class="text-xs text-gray-400 uppercase bg-gray-900/50">
                            <tr>
                                <th scope="col" class="px-3 py-2 text-left" style="min-width: 180px;">Magazyn</th>
                                <th scope="col" class="px-2 py-2 text-right" style="width: 80px;">Stan</th>
                                <th scope="col" class="px-2 py-2 text-right" style="width: 80px;">Rezerw.</th>
                                <th scope="col" class="px-2 py-2 text-right" style="width: 80px;">Min.</th>
                                <th scope="col" class="px-2 py-2 text-left" style="width: 120px;">Lokalizacja</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($variantModalStock as $warehouseId => $stockData)
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
                                               class="variant-modal-stock-input w-full text-right"
                                               placeholder="0">
                                    </td>

                                    {{-- Reserved --}}
                                    <td class="px-2 py-2">
                                        <input type="number"
                                               step="1"
                                               min="0"
                                               wire:model.defer="variantModalStock.{{ $warehouseId }}.reserved"
                                               class="variant-modal-stock-input w-full text-right"
                                               placeholder="0">
                                    </td>

                                    {{-- Minimum --}}
                                    <td class="px-2 py-2">
                                        <input type="number"
                                               step="1"
                                               min="0"
                                               wire:model.defer="variantModalStock.{{ $warehouseId }}.minimum"
                                               class="variant-modal-stock-input w-full text-right"
                                               placeholder="0">
                                    </td>

                                    {{-- Location --}}
                                    <td class="px-2 py-2">
                                        <input type="text"
                                               wire:model.defer="variantModalStock.{{ $warehouseId }}.location"
                                               class="variant-modal-location-input w-full"
                                               placeholder="A1-R2">
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
                            class="btn-enterprise-primary px-4 py-2 text-sm font-medium rounded-lg inline-flex items-center gap-2">
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
                        Zapisz zmiany
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
@endif
