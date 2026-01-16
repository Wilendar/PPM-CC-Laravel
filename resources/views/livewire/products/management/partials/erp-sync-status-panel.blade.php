{{-- ETAP_08.4: ERP Sync Status Panel (Full Shop-Tab Pattern) --}}
{{-- Panel statusu synchronizacji - wyswietlany gdy wybrany ERP tab --}}
{{-- UWAGA: Dane ERP NADPISUJA pola formularza - identycznie jak Shop Tab --}}

@php
    $connection = $erpExternalData['connection'] ?? null;
    $externalId = $erpExternalData['external_id'] ?? null;
    $syncStatus = $erpExternalData['sync_status'] ?? 'pending';
    $pendingFields = $erpExternalData['pending_fields'] ?? [];
    $externalData = $erpExternalData['external_data'] ?? [];
    $errorMessage = $erpExternalData['error_message'] ?? null;
    $lastSyncAt = $erpExternalData['last_sync_at'] ?? null;
    $lastPullAt = $erpExternalData['last_pull_at'] ?? null;
    $lastPushAt = $erpExternalData['last_push_at'] ?? null;
    $pendingCount = count($pendingFields);
    $isSyncing = $syncStatus === 'syncing';
    $hasPending = $pendingCount > 0;
@endphp

@if($connection)
<div class="mt-4 p-4 bg-gray-900 rounded-lg border border-blue-700/30">
    {{-- Header with Status Badge --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <div class="flex items-center space-x-3">
                <h5 class="text-lg font-medium text-white flex items-center">
                    {{-- ERP Type Icon --}}
                    @switch($connection->erp_type)
                        @case('baselinker')
                            <svg class="w-5 h-5 mr-2 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        @break
                        @default
                            <svg class="w-5 h-5 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V5zm11 1H6v8l4-2 4 2V6z" clip-rule="evenodd"/>
                            </svg>
                    @endswitch
                    {{ $connection->instance_name }}
                </h5>

                {{-- Status Badge --}}
                @switch($syncStatus)
                    @case('synced')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-600 text-white">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Zsynchronizowany
                        </span>
                    @break
                    @case('syncing')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-600 text-white animate-pulse">
                            <svg class="w-3 h-3 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Synchronizacja...
                        </span>
                    @break
                    @case('error')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-600 text-white">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            Blad
                        </span>
                    @break
                    @case('pending')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-600 text-white">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            Oczekuje ({{ $pendingCount }})
                        </span>
                    @break
                    @case('conflict')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-600 text-white">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            Konflikt
                        </span>
                    @break
                    @default
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-600 text-gray-300">
                            Nieznany
                        </span>
                @endswitch
            </div>

            @if($externalId)
                <p class="text-sm text-gray-400 mt-1">
                    External ID: <code class="px-2 py-0.5 bg-gray-800 rounded text-blue-400 font-mono">{{ $externalId }}</code>
                </p>
            @else
                <p class="text-sm text-yellow-400 mt-1">
                    Produkt nie jest powiazany z tym ERP
                </p>
            @endif
        </div>

        {{-- Action Buttons --}}
        <div class="flex space-x-2">
            {{-- Pull from ERP Button --}}
            <button wire:click="pullProductDataFromErp({{ $activeErpConnectionId }})"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    @if($isSyncing) disabled @endif
                    class="btn-enterprise-secondary text-sm inline-flex items-center {{ $isSyncing ? 'opacity-50 cursor-not-allowed' : '' }}">
                <svg class="w-4 h-4 mr-1" wire:loading.class="animate-spin" wire:target="pullProductDataFromErp" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span wire:loading.remove wire:target="pullProductDataFromErp">Wczytaj z ERP</span>
                <span wire:loading wire:target="pullProductDataFromErp">Pobieranie...</span>
            </button>

            {{-- Sync to ERP Button --}}
            <button wire:click="syncToErp({{ $activeErpConnectionId }})"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    @if($isSyncing) disabled @endif
                    class="btn-enterprise-primary text-sm inline-flex items-center {{ $isSyncing ? 'opacity-50 cursor-not-allowed' : '' }}">
                <svg class="w-4 h-4 mr-1" wire:loading.class="animate-spin" wire:target="syncToErp" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span wire:loading.remove wire:target="syncToErp">
                    @if($hasPending)
                        Wyslij zmiany ({{ $pendingCount }})
                    @else
                        Synchronizuj do ERP
                    @endif
                </span>
                <span wire:loading wire:target="syncToErp">Wysylanie...</span>
            </button>
        </div>
    </div>

    {{-- Syncing Progress Indicator --}}
    @if($isSyncing)
        <div class="mb-4 p-3 bg-blue-900/30 border border-blue-700 rounded">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span class="text-sm text-blue-300">Trwa synchronizacja z {{ $connection->instance_name }}... Prosze czekac.</span>
            </div>
        </div>
    @endif

    {{-- Sync Timestamps --}}
    <div class="grid grid-cols-3 gap-4 mb-4 text-sm">
        <div class="bg-gray-800 rounded p-3">
            <div class="text-gray-500 text-xs uppercase tracking-wide mb-1">Ostatnia sync</div>
            <div class="text-white">{{ $lastSyncAt ? $lastSyncAt->diffForHumans() : 'Nigdy' }}</div>
        </div>
        <div class="bg-gray-800 rounded p-3">
            <div class="text-gray-500 text-xs uppercase tracking-wide mb-1">Ostatni pull (ERP -> PPM)</div>
            <div class="text-green-400">{{ $lastPullAt ? $lastPullAt->diffForHumans() : 'Nigdy' }}</div>
        </div>
        <div class="bg-gray-800 rounded p-3">
            <div class="text-gray-500 text-xs uppercase tracking-wide mb-1">Ostatni push (PPM -> ERP)</div>
            <div class="text-blue-400">{{ $lastPushAt ? $lastPushAt->diffForHumans() : 'Nigdy' }}</div>
        </div>
    </div>

    {{-- Error Message (if any) --}}
    @if($syncStatus === 'error' && $errorMessage)
        <div class="mb-4 p-3 bg-red-900/30 border border-red-700 rounded">
            <h6 class="text-sm font-medium text-red-400 flex items-center mb-1">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Blad synchronizacji
            </h6>
            <p class="text-sm text-red-300">{{ $errorMessage }}</p>
        </div>
    @endif

    {{-- Pending Fields Warning --}}
    @if($hasPending)
        <div class="mb-4 p-3 bg-yellow-900/20 border border-yellow-700 rounded">
            <h6 class="text-sm font-medium text-yellow-400 flex items-center mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Oczekujace zmiany do synchronizacji ({{ $pendingCount }}):
            </h6>
            <ul class="text-sm text-yellow-300 list-disc list-inside">
                @foreach($pendingFields as $field)
                    <li>{{ __("products.fields.{$field}", [], 'pl') ?: ucfirst(str_replace('_', ' ', $field)) }}</li>
                @endforeach
            </ul>
            <p class="text-xs text-yellow-500 mt-2">
                Kliknij "Wyslij zmiany" lub "Zapisz i zamknij" aby wyslac zmiany do ERP.
            </p>
        </div>
    @endif

    {{-- Info about ERP Tab pattern --}}
    <div class="p-3 bg-blue-900/20 border border-blue-700/50 rounded">
        <h6 class="text-sm font-medium text-blue-400 flex items-center mb-2">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Tryb ERP (ETAP_08.4)
        </h6>
        <p class="text-sm text-blue-300">
            Formularz wyswietla dane z ERP {{ $connection->instance_name }}.
            Zmiany sa zapisywane lokalnie i oznaczane jako "Oczekujace" do momentu synchronizacji.
        </p>
        <ul class="text-sm text-blue-300 mt-2 space-y-1">
            <li>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium status-label-same mr-2">Zgodne</span>
                Wartosc w PPM jest taka sama jak w ERP
            </li>
            <li>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium status-label-different mr-2">Wlasne</span>
                Wartosc w PPM rozni sie od ERP (zmieniona lokalnie)
            </li>
            <li>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium status-label-inherited mr-2">Dziedziczone</span>
                Brak wartosci w ERP (uzywana wartosc PPM)
            </li>
        </ul>
    </div>

    {{-- External Data Preview (collapsible) --}}
    @if(!empty($externalData))
        <div class="mt-4" x-data="{ open: false }">
            <button type="button"
                    x-on:click="open = !open"
                    class="text-sm text-gray-400 hover:text-white flex items-center">
                <svg class="w-4 h-4 mr-1 transition-transform" x-bind:class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                Podglad danych z ERP (raw)
            </button>

            <div x-show="open" x-collapse class="mt-2">
                <pre class="text-xs bg-gray-950 text-gray-400 p-3 rounded overflow-x-auto max-h-60 overflow-y-auto">{{ json_encode($externalData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
    @endif
</div>
@endif
