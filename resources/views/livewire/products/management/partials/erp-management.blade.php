{{-- ETAP_08.3: ERP MANAGEMENT (Shop-Tab Pattern) --}}
{{-- Dostepne zarowno w create jak i edit mode --}}
{{-- KLUCZOWA ZMIANA: Badges wyswietlaja sie przy GLOWNYCH polach formularza --}}
@php
    $erpConnections = $this->activeErpConnections;
    $productErpStatus = $this->getProductErpSyncStatus();
@endphp

@if($erpConnections->isNotEmpty())
{{-- KRYTYCZNE: wire:poll MUSI być ZAWSZE w DOM (Livewire 3.x Golden Rule) --}}
{{-- Dodajemy wire:poll do głównego wrappera, nie do warunkowego include --}}
<div class="mt-3 bg-gray-800 rounded-lg p-3 relative"
     @if($this->hasActiveErpSyncJob()) wire:poll.2s="checkErpJobStatus" @endif>
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <h4 class="text-sm font-semibold text-white">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Integracje ERP
            </h4>
        </div>

        <div class="flex items-center space-x-2">
            {{-- Dane domyslne Button (Like Shop-Tab pattern) --}}
            <button type="button"
                    x-on:click="$wire.selectDefaultErpTab()"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded transition-all duration-200
                           {{ $activeErpConnectionId === null ? 'bg-ppm-primary text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dane PPM
            </button>

            {{-- ETAP_08.8: Check ERP Button (gdy ERP TAB wybrany i produkt nie powiazany) --}}
            @if($activeErpConnectionId !== null && ($this->erpExternalData['sync_status'] ?? '') === 'not_linked')
                <button type="button"
                        wire:click="checkProductInErp({{ $activeErpConnectionId }})"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-blue-600 hover:bg-blue-700 text-white transition-all duration-200">
                    <svg class="w-3.5 h-3.5 mr-1.5" wire:loading.class="animate-spin" wire:target="checkProductInErp" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span wire:loading.remove wire:target="checkProductInErp">Sprawdz czy jest w ERP</span>
                    <span wire:loading wire:target="checkProductInErp">Sprawdzam...</span>
                </button>
            @endif

            {{-- ETAP_08.8: Add to ERP Button (gdy sprawdzono i nie znaleziono) --}}
            @if($activeErpConnectionId !== null && ($this->erpExternalData['sync_status'] ?? '') === 'not_found')
                <button type="button"
                        wire:click="addProductToErp({{ $activeErpConnectionId }})"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-green-600 hover:bg-green-700 text-white transition-all duration-200">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Dodac do ERP?
                </button>
            @endif

            {{-- Link to ERP Manager --}}
            <a href="{{ route('admin.integrations') }}"
               class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-300 hover:text-white transition-colors duration-200">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Zarzadzaj ERP
            </a>
        </div>
    </div>

    {{-- ERP Connections List --}}
    <div class="mt-3">
        <div class="flex flex-wrap gap-2">
            @foreach($erpConnections as $connection)
                @php
                    $syncDisplay = $this->getErpSyncStatusDisplay($connection->id);
                    $isActive = $activeErpConnectionId === $connection->id;
                @endphp
                <div wire:key="erp-label-{{ $connection->id }}" class="inline-flex items-center group">
                    {{-- ERP Connection Button --}}
                    <button type="button"
                            x-on:click="$wire.selectErpTab({{ $connection->id }})"
                            wire:loading.attr="disabled"
                            wire:key="erp-btn-{{ $connection->id }}"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-l-lg transition-all duration-200
                                   {{ $isActive ? 'erp-tab-active' : 'erp-tab-inactive' }}">
                        {{-- ERP Type Icon --}}
                        @switch($connection->erp_type)
                            @case('baselinker')
                                <svg class="w-3 h-3 mr-1.5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            @break
                            @case('subiekt_gt')
                                <svg class="w-3 h-3 mr-1.5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm3 5a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1zm0 3a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1z" clip-rule="evenodd"/>
                                </svg>
                            @break
                            @case('dynamics')
                                <svg class="w-3 h-3 mr-1.5 text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"/>
                                    <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"/>
                                </svg>
                            @break
                            @default
                                <svg class="w-3 h-3 mr-1.5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V5zm11 1H6v8l4-2 4 2V6z" clip-rule="evenodd"/>
                                </svg>
                        @endswitch

                        {{ Str::limit($connection->instance_name, 15) }}

                        {{-- Sync Status Badge --}}
                        <span class="inline-flex items-center ml-2 px-2 py-0.5 rounded text-xs font-medium {{ $syncDisplay['class'] }}">
                            {{ $syncDisplay['icon'] }} {{ $syncDisplay['text'] }}
                        </span>

                        {{-- External ID badge (if exists) --}}
                        @if($syncDisplay['external_id'])
                            <span class="ml-1.5 text-xs font-mono erp-external-id">
                                #{{ $syncDisplay['external_id'] }}
                            </span>
                        @endif
                    </button>

                    {{-- ETAP_08.8: Sync/Pull buttons ONLY when product is linked to ERP --}}
                    @if(!in_array($syncDisplay['status'], ['not_linked', 'not_found']))
                        {{-- Sync to ERP Button - height matches label (py-1.5 + border = need py-2) --}}
                        <button type="button"
                                wire:click="syncToErp({{ $connection->id }})"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50"
                                title="Synchronizuj do {{ $connection->instance_name }}"
                                class="px-3 py-2 text-xs bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-200 flex items-center">
                            <svg class="w-3.5 h-3.5" wire:loading.class="animate-spin" wire:target="syncToErp({{ $connection->id }})" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </button>

                        {{-- Pull from ERP Button - height matches label --}}
                        <button type="button"
                                wire:click="pullProductDataFromErp({{ $connection->id }})"
                                wire:loading.attr="disabled"
                                title="Pobierz dane z {{ $connection->instance_name }}"
                                class="px-3 py-2 text-xs bg-green-600 hover:bg-green-700 text-white transition-colors duration-200 flex items-center">
                            <svg class="w-3.5 h-3.5" wire:loading.class="animate-spin" wire:target="pullProductDataFromErp({{ $connection->id }})" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </button>

                        {{-- ETAP_09.5: Unlink from ERP Button --}}
                        <button type="button"
                                wire:click="unlinkFromErp({{ $connection->id }})"
                                wire:loading.attr="disabled"
                                wire:confirm="Czy na pewno chcesz odlaczyc ten produkt od {{ $connection->instance_name }}? Powiazanie zostanie usuniete, ale produkt pozostanie w ERP."
                                title="Odlacz od {{ $connection->instance_name }}"
                                class="px-3 py-2 text-xs bg-red-600 hover:bg-red-700 text-white rounded-r-lg transition-colors duration-200 flex items-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    @else
                        {{-- Rounded right corner for unlinked state --}}
                        <span class="w-2 rounded-r-lg"></span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- ERP Sync Status Panel (when ERP tab is selected) --}}
    @if($activeErpConnectionId !== null && !empty($erpExternalData))
        @include('livewire.products.management.partials.erp-sync-status-panel')
    @endif

    {{-- ETAP_08.7: Light overlay for ERP sync indicator (NOT blocking!) --}}
    {{-- Changed from @if to x-show for reactivity --}}
    <div x-show="erpIsJobRunning"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="erp-sync-indicator mt-3 px-4 py-2 bg-blue-900/30 border border-blue-500/30 rounded-lg">
        <div class="flex items-center">
            <svg class="animate-spin h-4 w-4 text-blue-400 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-blue-300 text-sm">Synchronizacja ERP w toku...</span>
        </div>
    </div>
</div>
@endif
