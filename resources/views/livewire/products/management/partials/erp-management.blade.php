{{-- ETAP_08.3: ERP MANAGEMENT (Shop-Tab Pattern) --}}
{{-- Dostepne zarowno w create jak i edit mode --}}
{{-- KLUCZOWA ZMIANA: Badges wyswietlaja sie przy GLOWNYCH polach formularza --}}
@php
    $erpConnections = $this->activeErpConnections;
    $productErpStatus = $this->getProductErpSyncStatus();
@endphp

@if($erpConnections->isNotEmpty())
<div class="mt-3 bg-gray-800 rounded-lg p-3">
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
                                   {{ $isActive
                                      ? 'bg-blue-600 text-white border-2 border-blue-500'
                                      : 'bg-gray-700 text-gray-300 hover:bg-gray-600 border border-gray-600' }}">
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
                            <span class="ml-1 text-xs text-gray-500 font-mono">
                                #{{ $syncDisplay['external_id'] }}
                            </span>
                        @endif
                    </button>

                    {{-- Sync to ERP Button --}}
                    <button type="button"
                            wire:click="syncToErp({{ $connection->id }})"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50"
                            title="Synchronizuj do {{ $connection->instance_name }}"
                            class="px-2 py-1.5 text-xs bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-200">
                        <svg class="w-3 h-3" wire:loading.class="animate-spin" wire:target="syncToErp({{ $connection->id }})" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>

                    {{-- Pull from ERP Button --}}
                    <button type="button"
                            wire:click="pullProductDataFromErp({{ $connection->id }})"
                            wire:loading.attr="disabled"
                            title="Pobierz dane z {{ $connection->instance_name }}"
                            class="px-2 py-1.5 text-xs bg-green-600 hover:bg-green-700 text-white rounded-r-lg transition-colors duration-200">
                        <svg class="w-3 h-3" wire:loading.class="animate-spin" wire:target="pullProductDataFromErp({{ $connection->id }})" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ERP Sync Status Panel (when ERP tab is selected) --}}
    @if($activeErpConnectionId !== null && !empty($erpExternalData))
        @include('livewire.products.management.partials.erp-sync-status-panel')
    @endif
</div>
@endif
