{{-- resources/views/livewire/products/import/modals/import-prices-modal.blade.php --}}
{{-- FAZA 9.4: Import Prices Modal - pattern from variant-prices-modal --}}
<div>
@if($showPricesModal && $editingProductId)
<div class="fixed inset-0 overflow-y-auto import-prices-modal-overlay" @keydown.escape.window="$wire.closePricesModal()">
    {{-- Overlay --}}
    <div class="fixed inset-0 bg-black/70 transition-opacity" @click="$wire.closePricesModal()"></div>

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
                        <h3 class="text-lg font-semibold text-white">Ceny produktu</h3>
                        <p class="text-sm text-gray-400">{{ $editingProductSku }}</p>
                    </div>

                    {{-- Lock/Unlock Button --}}
                    <button type="button"
                            wire:click="togglePricesLock"
                            class="ml-4 inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg transition-all duration-200 {{ $pricesUnlocked ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-gray-700 hover:bg-gray-600 text-gray-300' }}"
                            title="{{ $pricesUnlocked ? 'Zablokuj edycje cen' : 'Odblokuj edycje cen' }}">
                        @if($pricesUnlocked)
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                            <span>Odblokowane</span>
                        @else
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                            <span>Zablokowane</span>
                        @endif
                    </button>
                </div>

                <button type="button"
                        wire:click="closePricesModal"
                        class="text-gray-400 hover:text-white transition-colors p-2 hover:bg-gray-700 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Content --}}
            @php
                $isEditable = $pricesUnlocked;
            @endphp
            <div class="px-6 py-4 max-h-[60vh] overflow-y-auto {{ !$isEditable ? 'opacity-60' : '' }}"
                 x-data="{
                     taxRate: {{ $taxRate }},
                     isLocked: {{ !$isEditable ? 'true' : 'false' }},
                     prices: @js($modalPrices ?? []),
                     calculateGross(groupId) {
                         if (this.isLocked) return;
                         const net = parseFloat(this.prices[groupId]?.net || 0);
                         if (isNaN(net) || net === 0) return;
                         const gross = (net * (1 + this.taxRate / 100)).toFixed(2);
                         this.prices[groupId] = this.prices[groupId] || {};
                         this.prices[groupId].gross = gross;
                         $wire.set('modalPrices.' + groupId + '.gross', gross, false);
                     },
                     calculateNet(groupId) {
                         if (this.isLocked) return;
                         const gross = parseFloat(this.prices[groupId]?.gross || 0);
                         if (isNaN(gross) || gross === 0) return;
                         const net = (gross / (1 + this.taxRate / 100)).toFixed(2);
                         this.prices[groupId] = this.prices[groupId] || {};
                         this.prices[groupId].net = net;
                         $wire.set('modalPrices.' + groupId + '.net', net, false);
                     }
                 }">

                {{-- Prices Table --}}
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-400 uppercase bg-gray-900/50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left">Grupa cenowa</th>
                            <th scope="col" class="px-4 py-3 text-right">Cena netto (PLN)</th>
                            <th scope="col" class="px-4 py-3 text-right">Cena brutto (PLN)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->priceGroups as $group)
                            <tr class="border-b border-gray-700/50 hover:bg-gray-700/30">
                                {{-- Price Group Name --}}
                                <td class="px-4 py-3">
                                    <span class="font-medium text-white">{{ $group->name }}</span>
                                    @if($group->code)
                                        <span class="text-xs text-gray-500 ml-2">({{ $group->code }})</span>
                                    @endif
                                    @if($group->is_default)
                                        <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-900/30 text-blue-400">domyslna</span>
                                    @endif
                                </td>

                                {{-- Price Net --}}
                                <td class="px-4 py-3">
                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           x-model="prices[{{ $group->id }}] ? prices[{{ $group->id }}].net : ''"
                                           @input="calculateGross({{ $group->id }})"
                                           wire:model.live="modalPrices.{{ $group->id }}.net"
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
                                           x-model="prices[{{ $group->id }}] ? prices[{{ $group->id }}].gross : ''"
                                           @input="calculateNet({{ $group->id }})"
                                           wire:model.live="modalPrices.{{ $group->id }}.gross"
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
                    <span>Stawka VAT: {{ $taxRate }}% | Cena domyslnej grupy = base_price produktu</span>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-700 flex items-center justify-between">
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

                <div class="flex items-center gap-3">
                    <button type="button"
                            wire:click="closePricesModal"
                            class="px-4 py-2 text-sm font-medium rounded-lg bg-gray-700 text-gray-300 hover:bg-gray-600 transition-colors">
                        Anuluj
                    </button>
                    <button type="button"
                            wire:click="savePrices"
                            wire:loading.attr="disabled"
                            {{ !$isEditable ? 'disabled' : '' }}
                            @class([
                                'px-4 py-2 text-sm font-medium rounded-lg inline-flex items-center gap-2 transition-colors',
                                'bg-blue-600 hover:bg-blue-700 text-white' => $isEditable,
                                'bg-gray-700 text-gray-500 cursor-not-allowed' => !$isEditable,
                            ])>
                        <span wire:loading.remove wire:target="savePrices">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                        <span wire:loading wire:target="savePrices">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                        {{ !$isEditable ? 'Zablokowane' : 'Zapisz ceny' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
</div>
