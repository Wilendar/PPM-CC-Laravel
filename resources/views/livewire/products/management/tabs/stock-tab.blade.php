{{-- resources/views/livewire/products/management/tabs/stock-tab.blade.php --}}
<div class="tab-content active space-y-6">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <h3 class="text-lg font-medium text-white">
                <svg class="w-6 h-6 inline mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                Stany magazynowe
            </h3>

            {{-- Lock/Unlock Button --}}
            @if($this->canUnlockStock())
                <button type="button"
                        wire:click="toggleStockLock"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg transition-all duration-200 {{ $stockUnlocked ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-gray-700 hover:bg-gray-600 text-gray-300' }}"
                        title="{{ $stockUnlocked ? 'Zablokuj edycje stanow' : 'Odblokuj edycje stanow' }}">
                    @if($stockUnlocked)
                        {{-- Lock Open Icon (Heroicons) --}}
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                        <span>Odblokowane</span>
                    @else
                        {{-- Lock Closed Icon (Heroicons) --}}
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                        <span>Zablokowane</span>
                    @endif
                </button>
            @else
                {{-- Read-only indicator for users without permission --}}
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg bg-gray-800 text-gray-400 border border-gray-700">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                    <span>Tylko odczyt</span>
                </span>
            @endif
        </div>

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
        {{-- Stock Grid - 6 Warehouses --}}
        <div class="bg-gray-800 rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-md font-medium text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    Magazyny (6)
                </h4>
                <div class="text-xs text-gray-400">
                    Zarządzaj stanami dla wszystkich magazynów
                </div>
            </div>

            {{-- Stock Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-400 uppercase bg-gray-900">
                        <tr>
                            <th scope="col" class="px-4 py-3">Magazyn</th>
                            <th scope="col" class="px-4 py-3 text-right">Stan dostępny</th>
                            <th scope="col" class="px-4 py-3 text-right">Zarezerwowane</th>
                            <th scope="col" class="px-4 py-3 text-right">Minimum</th>
                            <th scope="col" class="px-4 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($warehouses as $warehouseId => $warehouse)
                            @php
                                $available = isset($stock[$warehouseId]['quantity']) ? $stock[$warehouseId]['quantity'] : 0;
                                $reserved = isset($stock[$warehouseId]['reserved']) ? $stock[$warehouseId]['reserved'] : 0;
                                $minimum = isset($stock[$warehouseId]['minimum']) ? $stock[$warehouseId]['minimum'] : 0;
                                $actualAvailable = $available - $reserved;

                                // Stock status logic
                                if ($actualAvailable <= 0) {
                                    $statusClass = 'bg-red-900/30 text-red-400 border-red-700/50';
                                    $statusText = 'Brak';
                                } elseif ($actualAvailable <= $minimum) {
                                    $statusClass = 'bg-yellow-900/30 text-yellow-400 border-yellow-700/50';
                                    $statusText = 'Niski';
                                } else {
                                    $statusClass = 'bg-green-900/30 text-green-400 border-green-700/50';
                                    $statusText = 'OK';
                                }
                            @endphp
                            <tr class="border-b border-gray-700 hover:bg-gray-750">
                                {{-- Warehouse Name --}}
                                <td class="px-4 py-3">
                                    <span class="font-medium text-white">{{ $warehouse['name'] }}</span>
                                    @if(!empty($warehouse['code']))
                                        <span class="text-xs text-gray-500 ml-2">({{ $warehouse['code'] }})</span>
                                    @endif
                                </td>

                                {{-- Total Quantity (editable) --}}
                                <td class="px-4 py-3">
                                    <input type="number"
                                           min="0"
                                           wire:model.defer="stock.{{ $warehouseId }}.quantity"
                                           class="w-full border text-sm rounded-lg px-3 py-2 {{ $stockUnlocked ? 'bg-gray-700 border-gray-600 text-white focus:ring-blue-500 focus:border-blue-500' : 'bg-gray-800 border-gray-700 text-gray-400 cursor-not-allowed' }}"
                                           placeholder="0"
                                           {{ $stockUnlocked ? '' : 'readonly' }}>
                                    @if($actualAvailable != $available)
                                        <span class="text-xs text-gray-500 mt-1 block">Dostepne: {{ $actualAvailable }}</span>
                                    @endif
                                </td>

                                {{-- Reserved Quantity (editable) --}}
                                <td class="px-4 py-3">
                                    <input type="number"
                                           min="0"
                                           wire:model.defer="stock.{{ $warehouseId }}.reserved"
                                           class="w-full border text-sm rounded-lg px-3 py-2 {{ $stockUnlocked ? 'bg-gray-700 border-gray-600 text-orange-400 focus:ring-blue-500 focus:border-blue-500' : 'bg-gray-800 border-gray-700 text-gray-500 cursor-not-allowed' }}"
                                           placeholder="0"
                                           {{ $stockUnlocked ? '' : 'readonly' }}>
                                </td>

                                {{-- Minimum Stock Level (editable) --}}
                                <td class="px-4 py-3">
                                    <input type="number"
                                           min="0"
                                           wire:model.defer="stock.{{ $warehouseId }}.minimum"
                                           class="w-full border text-sm rounded-lg px-3 py-2 {{ $stockUnlocked ? 'bg-gray-700 border-gray-600 text-white focus:ring-blue-500 focus:border-blue-500' : 'bg-gray-800 border-gray-700 text-gray-400 cursor-not-allowed' }}"
                                           placeholder="0"
                                           {{ $stockUnlocked ? '' : 'readonly' }}>
                                </td>

                                {{-- Stock Status (readonly, auto-calculated) --}}
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full {{ $statusClass }}">
                                        {{ $statusText }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr class="border-b border-gray-700">
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                    <p class="text-sm">Brak magazynów w systemie.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
