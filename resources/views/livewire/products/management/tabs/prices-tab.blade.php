{{-- resources/views/livewire/products/management/tabs/prices-tab.blade.php --}}
<div class="tab-content active space-y-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-white">
            <svg class="w-6 h-6 inline mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Ceny produktu
        </h3>

        {{-- Active Shop Indicator --}}
        @if($activeShopId !== null && isset($availableShops))
            @php
                $currentShop = collect($availableShops)->firstWhere('id', $activeShopId);
            @endphp
            <div class="flex items-center">
                <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-orange-900/30 text-orange-200 border border-orange-700/50">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Edytujesz: {{ $currentShop['name'] ?? 'Nieznany sklep' }}
                </span>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-6">
        {{-- Prices Grid - 8 Price Groups --}}
        <div class="bg-gray-800 rounded-lg p-4"
             x-data="{
                 taxRate: {{ $tax_rate }},
                 calculateGross(netValue, groupId) {
                     if (!netValue || netValue === '') return;
                     const gross = parseFloat(netValue) * (1 + this.taxRate / 100);
                     $wire.set('prices.' + groupId + '.gross', gross.toFixed(2));
                 },
                 calculateNet(grossValue, groupId) {
                     if (!grossValue || grossValue === '') return;
                     const net = parseFloat(grossValue) / (1 + this.taxRate / 100);
                     $wire.set('prices.' + groupId + '.net', net.toFixed(2));
                 }
             }">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-md font-medium text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Grupy cenowe (8)
                </h4>
                <div class="text-xs text-gray-400">
                    Zarządzaj cenami dla wszystkich grup cenowych
                </div>
            </div>

            {{-- Prices Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-400 uppercase bg-gray-900">
                        <tr>
                            <th scope="col" class="px-4 py-3">Grupa cenowa</th>
                            <th scope="col" class="px-4 py-3 text-right">Cena netto (PLN)</th>
                            <th scope="col" class="px-4 py-3 text-right">Cena brutto (PLN)</th>
                            <th scope="col" class="px-4 py-3 text-right">Marża (%)</th>
                            <th scope="col" class="px-4 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($priceGroups as $groupId => $group)
                            <tr class="border-b border-gray-700 hover:bg-gray-750">
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
                                           wire:model.defer="prices.{{ $groupId }}.net"
                                           @input="calculateGross($event.target.value, {{ $groupId }})"
                                           class="w-full bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2"
                                           placeholder="0.00">
                                </td>

                                {{-- Price Gross --}}
                                <td class="px-4 py-3">
                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           wire:model.defer="prices.{{ $groupId }}.gross"
                                           @input="calculateNet($event.target.value, {{ $groupId }})"
                                           class="w-full bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2"
                                           placeholder="0.00">
                                </td>

                                {{-- Margin (readonly for now) --}}
                                <td class="px-4 py-3 text-right">
                                    @if(isset($prices[$groupId]['margin']) && $prices[$groupId]['margin'] !== null)
                                        <span class="text-green-400 font-mono">{{ number_format($prices[$groupId]['margin'], 2, ',', ' ') }}%</span>
                                    @else
                                        <span class="text-gray-600 text-xs">—</span>
                                    @endif
                                </td>

                                {{-- Status (checkbox) --}}
                                <td class="px-4 py-3 text-center">
                                    <input type="checkbox"
                                           wire:model.defer="prices.{{ $groupId }}.is_active"
                                           class="w-4 h-4 text-blue-600 bg-gray-700 border-gray-600 rounded focus:ring-blue-500 focus:ring-2">
                                </td>
                            </tr>
                        @empty
                            <tr class="border-b border-gray-700">
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                    <p class="text-sm">Brak grup cenowych w systemie.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
