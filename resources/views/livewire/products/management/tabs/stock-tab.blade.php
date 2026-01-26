{{-- resources/views/livewire/products/management/tabs/stock-tab.blade.php --}}
{{-- UPDATED 2026-01-24: Granular column locks with dirty tracking --}}
<div class="tab-content active space-y-6"
     x-data="{
         showUnlockModal: false,
         pendingColumn: null
     }"
     x-on:show-stock-unlock-modal.window="showUnlockModal = true"
     x-on:close-stock-unlock-modal.window="showUnlockModal = false; pendingColumn = null">

    {{-- Header Section --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <h3 class="text-lg font-medium text-white">
                <svg class="w-6 h-6 inline mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                Stany magazynowe
            </h3>

            {{-- Dirty Changes Indicator --}}
            @if($this->hasAnyStockDirty())
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-yellow-900/40 text-yellow-300 border border-yellow-600/50">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    Niezapisane zmiany
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

    {{-- Warning Banner (ERP Sync Info) --}}
    <div class="bg-amber-900/20 border border-amber-700/50 rounded-lg p-4 mb-4">
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
                    przy naglowku kolumny. Tylko odblokowane i edytowane kolumny zostana zsynchronizowane do ERP.
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6">
        {{-- Stock Grid --}}
        <div class="bg-gray-800 rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-md font-medium text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    Magazyny ({{ count($warehouses) }})
                </h4>

                {{-- Column Lock Status Summary --}}
                <div class="flex items-center gap-2 text-xs">
                    @foreach(['quantity' => 'Stan', 'reserved' => 'Rez.', 'minimum' => 'Min.'] as $col => $label)
                        @php $isUnlocked = $this->isStockColumnUnlocked($col); @endphp
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded {{ $isUnlocked ? 'bg-green-900/30 text-green-400' : 'bg-gray-700 text-gray-400' }}">
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
            </div>

            {{-- Stock Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-400 uppercase bg-gray-900">
                        <tr>
                            <th scope="col" class="px-4 py-3 w-48">Magazyn</th>

                            {{-- Stan dostępny - with lock button --}}
                            <th scope="col" class="px-4 py-3">
                                <div class="flex items-center justify-between gap-2">
                                    <span>Stan dostepny</span>
                                    @if($this->canUnlockStockColumn('quantity'))
                                        <button type="button"
                                                wire:click="{{ $this->isStockColumnUnlocked('quantity') ? 'lockStockColumn(\'quantity\')' : 'requestStockColumnUnlock(\'quantity\')' }}"
                                                class="p-1 rounded transition-colors {{ $this->isStockColumnUnlocked('quantity') ? 'text-green-400 hover:bg-green-900/30' : 'text-gray-500 hover:bg-gray-700' }}"
                                                title="{{ $this->isStockColumnUnlocked('quantity') ? 'Zablokuj kolumne' : 'Odblokuj do edycji' }}">
                                            @if($this->isStockColumnUnlocked('quantity'))
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
                            <th scope="col" class="px-4 py-3">
                                <div class="flex items-center justify-between gap-2">
                                    <span>Zarezerwowane</span>
                                    @if($this->canUnlockStockColumn('reserved'))
                                        <button type="button"
                                                wire:click="{{ $this->isStockColumnUnlocked('reserved') ? 'lockStockColumn(\'reserved\')' : 'requestStockColumnUnlock(\'reserved\')' }}"
                                                class="p-1 rounded transition-colors {{ $this->isStockColumnUnlocked('reserved') ? 'text-green-400 hover:bg-green-900/30' : 'text-gray-500 hover:bg-gray-700' }}"
                                                title="{{ $this->isStockColumnUnlocked('reserved') ? 'Zablokuj kolumne' : 'Odblokuj do edycji' }}">
                                            @if($this->isStockColumnUnlocked('reserved'))
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
                            <th scope="col" class="px-4 py-3">
                                <div class="flex items-center justify-between gap-2">
                                    <span>Minimum</span>
                                    @if($this->canUnlockStockColumn('minimum'))
                                        <button type="button"
                                                wire:click="{{ $this->isStockColumnUnlocked('minimum') ? 'lockStockColumn(\'minimum\')' : 'requestStockColumnUnlock(\'minimum\')' }}"
                                                class="p-1 rounded transition-colors {{ $this->isStockColumnUnlocked('minimum') ? 'text-green-400 hover:bg-green-900/30' : 'text-gray-500 hover:bg-gray-700' }}"
                                                title="{{ $this->isStockColumnUnlocked('minimum') ? 'Zablokuj kolumne' : 'Odblokuj do edycji' }}">
                                            @if($this->isStockColumnUnlocked('minimum'))
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

                            {{-- Lokalizacja - always editable --}}
                            <th scope="col" class="px-4 py-3">Lokalizacja</th>

                            <th scope="col" class="px-4 py-3 text-center w-24">Status</th>
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

                                // Column states
                                $qtyUnlocked = $this->isStockColumnUnlocked('quantity');
                                $qtyDirty = $this->isStockCellDirty($warehouseId, 'quantity');
                                $resUnlocked = $this->isStockColumnUnlocked('reserved');
                                $resDirty = $this->isStockCellDirty($warehouseId, 'reserved');
                                $minUnlocked = $this->isStockColumnUnlocked('minimum');
                                $minDirty = $this->isStockCellDirty($warehouseId, 'minimum');
                            @endphp
                            <tr class="border-b border-gray-700 hover:bg-gray-750">
                                {{-- Warehouse Name --}}
                                <td class="px-4 py-3">
                                    <span class="font-medium text-white">{{ $warehouse['name'] }}</span>
                                    @if(!empty($warehouse['code']))
                                        <span class="text-xs text-gray-500 ml-2">({{ $warehouse['code'] }})</span>
                                    @endif
                                </td>

                                {{-- Quantity (editable when unlocked) --}}
                                <td class="px-4 py-3">
                                    <div class="relative">
                                        <input type="number"
                                               min="0"
                                               wire:model.live="stock.{{ $warehouseId }}.quantity"
                                               wire:change="markStockDirty({{ $warehouseId }}, 'quantity')"
                                               @class([
                                                   'w-full border text-sm rounded-lg px-3 py-2 text-right',
                                                   'bg-gray-800 border-gray-700 text-gray-400 cursor-not-allowed' => !$qtyUnlocked,
                                                   'bg-gray-700 border-gray-600 text-white focus:ring-blue-500 focus:border-blue-500' => $qtyUnlocked && !$qtyDirty,
                                                   'bg-yellow-900/30 border-yellow-500 text-yellow-200 focus:ring-yellow-500 focus:border-yellow-500' => $qtyUnlocked && $qtyDirty,
                                               ])
                                               placeholder="0"
                                               {{ $qtyUnlocked ? '' : 'readonly' }}>
                                        @if($qtyDirty)
                                            <span class="absolute -top-2.5 left-0 px-1.5 py-0.5 text-[9px] font-semibold bg-yellow-600 text-yellow-100 rounded whitespace-nowrap z-10">
                                                Edytowano
                                            </span>
                                        @endif
                                    </div>
                                    @if($actualAvailable != $available && $reserved > 0)
                                        <span class="text-xs text-gray-500 mt-1 block text-right">Dostepne: {{ $actualAvailable }}</span>
                                    @endif
                                </td>

                                {{-- Reserved (editable when unlocked) --}}
                                <td class="px-4 py-3">
                                    <div class="relative">
                                        <input type="number"
                                               min="0"
                                               wire:model.live="stock.{{ $warehouseId }}.reserved"
                                               wire:change="markStockDirty({{ $warehouseId }}, 'reserved')"
                                               @class([
                                                   'w-full border text-sm rounded-lg px-3 py-2 text-right',
                                                   'bg-gray-800 border-gray-700 text-gray-500 cursor-not-allowed' => !$resUnlocked,
                                                   'bg-gray-700 border-gray-600 text-orange-400 focus:ring-blue-500 focus:border-blue-500' => $resUnlocked && !$resDirty,
                                                   'bg-yellow-900/30 border-yellow-500 text-yellow-200 focus:ring-yellow-500 focus:border-yellow-500' => $resUnlocked && $resDirty,
                                               ])
                                               placeholder="0"
                                               {{ $resUnlocked ? '' : 'readonly' }}>
                                        @if($resDirty)
                                            <span class="absolute -top-2.5 left-0 px-1.5 py-0.5 text-[9px] font-semibold bg-yellow-600 text-yellow-100 rounded whitespace-nowrap z-10">
                                                Edytowano
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Minimum (editable when unlocked) --}}
                                <td class="px-4 py-3">
                                    <div class="relative">
                                        <input type="number"
                                               min="0"
                                               wire:model.live="stock.{{ $warehouseId }}.minimum"
                                               wire:change="markStockDirty({{ $warehouseId }}, 'minimum')"
                                               @class([
                                                   'w-full border text-sm rounded-lg px-3 py-2 text-right',
                                                   'bg-gray-800 border-gray-700 text-gray-400 cursor-not-allowed' => !$minUnlocked,
                                                   'bg-gray-700 border-gray-600 text-white focus:ring-blue-500 focus:border-blue-500' => $minUnlocked && !$minDirty,
                                                   'bg-yellow-900/30 border-yellow-500 text-yellow-200 focus:ring-yellow-500 focus:border-yellow-500' => $minUnlocked && $minDirty,
                                               ])
                                               placeholder="0"
                                               {{ $minUnlocked ? '' : 'readonly' }}>
                                        @if($minDirty)
                                            <span class="absolute -top-2.5 left-0 px-1.5 py-0.5 text-[9px] font-semibold bg-yellow-600 text-yellow-100 rounded whitespace-nowrap z-10">
                                                Edytowano
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Location (always editable) --}}
                                <td class="px-4 py-3">
                                    <div class="relative">
                                        <input type="text"
                                               wire:model.live="stock.{{ $warehouseId }}.location"
                                               class="form-input-enterprise w-full text-sm"
                                               maxlength="50"
                                               placeholder="Kod lokalizacji">
                                    </div>
                                </td>

                                {{-- Stock Status --}}
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full border {{ $statusClass }}">
                                        {{ $statusText }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr class="border-b border-gray-700">
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                    <p class="text-sm">Brak magazynow w systemie.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Dirty Columns Summary --}}
            @if($this->hasAnyStockDirty())
                <div class="mt-4 p-3 bg-yellow-900/20 border border-yellow-700/50 rounded-lg">
                    <div class="flex items-center gap-2 text-sm text-yellow-300">
                        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span>
                            Masz niezapisane zmiany. Kliknij "Zapisz zmiany" aby zsynchronizowac z systemami ERP.
                        </span>
                    </div>
                </div>
            @endif
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
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/60"
                 x-on:click="$wire.cancelStockColumnUnlock()"></div>

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
                            wire:click="cancelStockColumnUnlock"
                            class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-700 border border-gray-600 rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors">
                        Anuluj
                    </button>
                    <button type="button"
                            wire:click="confirmStockColumnUnlock"
                            class="px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 transition-colors">
                        Potwierdz odblokowanie
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
