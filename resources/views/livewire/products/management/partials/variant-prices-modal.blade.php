{{-- resources/views/livewire/products/management/partials/variant-prices-modal.blade.php --}}
{{-- Modal for editing variant prices - ETAP_14 --}}
{{-- Lock system identical to prices-tab.blade.php --}}

@if($showVariantPricesModal && $selectedVariantIdForPrices)
<template x-teleport="body">
    <div class="fixed inset-0 overflow-y-auto" style="z-index: 9999;" @keydown.escape.window="$wire.closeVariantPricesModal()">
        {{-- Overlay --}}
        <div class="fixed inset-0 bg-black/70 transition-opacity" @click="$wire.closeVariantPricesModal()"></div>

        {{-- Modal Panel --}}
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-gray-800 rounded-xl shadow-2xl border border-gray-700 transform transition-all"
                 @click.stop>

                {{-- Header with Lock Button --}}
                <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-green-900/30 rounded-lg">
                            <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-white">Ceny wariantu</h3>
                            <p class="text-sm text-gray-400">{{ $selectedVariantForPricesData['sku'] ?? 'N/A' }}</p>
                        </div>

                        {{-- Lock/Unlock Button (identical to prices-tab.blade.php) --}}
                        @if(!$this->isVariantModalLocked())
                            <button type="button"
                                    wire:click="toggleVariantPricesLock"
                                    class="ml-4 inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg transition-all duration-200 {{ $variantPricesUnlocked ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-gray-700 hover:bg-gray-600 text-gray-300' }}"
                                    title="{{ $variantPricesUnlocked ? 'Zablokuj edycje cen' : 'Odblokuj edycje cen' }}">
                                @if($variantPricesUnlocked)
                                    {{-- Lock Open Icon --}}
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                    </svg>
                                    <span>Odblokowane</span>
                                @else
                                    {{-- Lock Closed Icon --}}
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                    </svg>
                                    <span>Zablokowane</span>
                                @endif
                            </button>
                        @else
                            {{-- Sync in progress - show read-only indicator --}}
                            <span class="ml-4 inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg bg-gray-800 text-gray-400 border border-gray-700">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                                <span>Tylko odczyt</span>
                            </span>
                        @endif
                    </div>
                    <button type="button"
                            wire:click="closeVariantPricesModal"
                            class="text-gray-400 hover:text-white transition-colors p-2 hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Sync Lock Banner (ERP/PrestaShop sync in progress) --}}
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
                @endif

                {{-- ERP Warning Banner (when prices are unlocked) --}}
                @if($variantPricesUnlocked && !$this->isVariantModalLocked())
                <div class="px-6 py-3 bg-amber-900/20 border-b border-amber-700/30">
                    <div class="flex items-center gap-2 text-amber-300 text-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Edycja odblokowana - zmiany cen moga wplynac na synchronizacje ERP (Subiekt GT, Baselinker)</span>
                    </div>
                </div>
                @endif

                {{-- Content --}}
                @php
                    $isEditable = $variantPricesUnlocked && !$this->isVariantModalLocked();
                @endphp
                <div class="px-6 py-4 max-h-[60vh] overflow-y-auto {{ !$isEditable ? 'opacity-60' : '' }}"
                     x-data="{
                         taxRate: {{ $tax_rate ?? 23 }},
                         isLocked: {{ !$isEditable ? 'true' : 'false' }},
                         calculateGross(netValue, groupId) {
                             if (this.isLocked) return;
                             if (!netValue || netValue === '') return;
                             const gross = parseFloat(netValue) * (1 + this.taxRate / 100);
                             $wire.set('variantModalPrices.' + groupId + '.gross', gross.toFixed(2));
                         },
                         calculateNet(grossValue, groupId) {
                             if (this.isLocked) return;
                             if (!grossValue || grossValue === '') return;
                             const net = parseFloat(grossValue) / (1 + this.taxRate / 100);
                             $wire.set('variantModalPrices.' + groupId + '.net', net.toFixed(2));
                         }
                     }">

                    {{-- Prices Table --}}
                    <table class="variant-modal-table w-full text-sm">
                        <thead class="text-xs text-gray-400 uppercase bg-gray-900/50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left">Grupa cenowa</th>
                                <th scope="col" class="px-4 py-3 text-right">Cena netto (PLN)</th>
                                <th scope="col" class="px-4 py-3 text-right">Cena brutto (PLN)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($priceGroups as $groupId => $group)
                                <tr class="border-b border-gray-700/50 hover:bg-gray-700/30">
                                    {{-- Price Group Name --}}
                                    <td class="px-4 py-3">
                                        <span class="font-medium text-white">{{ $group['name'] }}</span>
                                        @if(!empty($group['code']))
                                            <span class="text-xs text-gray-500 ml-2">({{ $group['code'] }})</span>
                                        @endif
                                    </td>

                                    {{-- Price Net --}}
                                    <td class="px-4 py-3">
                                        <input type="number"
                                               step="0.01"
                                               min="0"
                                               wire:model.defer="variantModalPrices.{{ $groupId }}.net"
                                               @input="calculateGross($event.target.value, {{ $groupId }})"
                                               @class([
                                                   'w-full border text-sm rounded-lg px-3 py-2 text-right',
                                                   'bg-gray-700 border-gray-600 text-white focus:ring-blue-500 focus:border-blue-500' => $isEditable,
                                                   'bg-gray-800 border-gray-700 text-gray-400 cursor-not-allowed' => !$isEditable,
                                               ])
                                               placeholder="0.00"
                                               {{ !$isEditable ? 'readonly' : '' }}>
                                    </td>

                                    {{-- Price Gross --}}
                                    <td class="px-4 py-3">
                                        <input type="number"
                                               step="0.01"
                                               min="0"
                                               wire:model.defer="variantModalPrices.{{ $groupId }}.gross"
                                               @input="calculateNet($event.target.value, {{ $groupId }})"
                                               @class([
                                                   'w-full border text-sm rounded-lg px-3 py-2 text-right',
                                                   'bg-gray-700 border-gray-600 text-white focus:ring-blue-500 focus:border-blue-500' => $isEditable,
                                                   'bg-gray-800 border-gray-700 text-gray-400 cursor-not-allowed' => !$isEditable,
                                               ])
                                               placeholder="0.00"
                                               {{ !$isEditable ? 'readonly' : '' }}>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-gray-500">
                                        Brak grup cenowych w systemie.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{-- VAT Info --}}
                    <div class="mt-4 flex items-center gap-2 text-xs text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Stawka VAT: {{ $tax_rate ?? 23 }}%</span>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-700 flex items-center justify-between">
                    {{-- Status Info --}}
                    <div class="text-xs text-gray-500">
                        @if(!$isEditable)
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Odblokuj edycje aby moc zapisac zmiany
                            </span>
                        @else
                            <span class="flex items-center gap-1 text-green-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                </svg>
                                Edycja odblokowana
                            </span>
                        @endif
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex items-center gap-3">
                        <button type="button"
                                wire:click="closeVariantPricesModal"
                                class="btn-enterprise-secondary px-4 py-2 text-sm font-medium rounded-lg">
                            Anuluj
                        </button>
                        <button type="button"
                                wire:click="saveVariantModalPrices"
                                wire:loading.attr="disabled"
                                {{ !$isEditable ? 'disabled' : '' }}
                                @class([
                                    'btn-enterprise-primary px-4 py-2 text-sm font-medium rounded-lg inline-flex items-center gap-2',
                                    'opacity-50 cursor-not-allowed' => !$isEditable,
                                ])>
                            <span wire:loading.remove wire:target="saveVariantModalPrices">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </span>
                            <span wire:loading wire:target="saveVariantModalPrices">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                            {{ !$isEditable ? 'Zablokowane' : 'Zapisz zmiany' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
@endif
