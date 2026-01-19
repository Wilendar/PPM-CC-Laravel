{{-- ETAP_08.5: ERP Sync Status Panel (Compact Shop-Tab Pattern) --}}
{{-- Panel statusu synchronizacji - domyslnie zwiniety, jak Shop TAB --}}

{{-- wire:poll dla sprawdzania statusu joba (tylko gdy aktywny job) --}}
@if($this->hasActiveErpSyncJob())
<div wire:poll.2s="checkErpJobStatus" class="hidden"></div>
@endif

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
    $isSyncing = $syncStatus === 'syncing' || $this->hasActiveErpSyncJob();
    $hasPending = $pendingCount > 0;
@endphp

@if($connection)
<div class="mb-4 p-4 border border-gray-600 rounded-lg bg-gray-800 shadow-sm"
     x-data="erpSyncStatusTracker(@entangle('activeErpJobStatus'), @entangle('activeErpJobType'), @entangle('erpJobResult'), @entangle('erpJobCreatedAt'))">

    {{-- ========================================== --}}
    {{-- ANIMATED PROGRESS BAR (during sync) --}}
    {{-- ========================================== --}}
    <div class="sync-status-container sync-status-running mb-4"
         x-show="isJobRunning"
         x-cloak>
        <div class="sync-status-header">
            <i class="fas fa-sync fa-spin text-blue-400 mr-2"></i>
            <span class="font-bold text-blue-400">Trwa synchronizacja ERP</span>
        </div>
        <div class="sync-progress-bar-container">
            <div class="sync-progress-bar" :style="`width: ${progress}%`" style="background: linear-gradient(90deg, #3b82f6 0%, #60a5fa 100%);"></div>
        </div>
        <div class="sync-status-time text-sm text-dark-secondary mt-2">
            <span x-text="statusText"></span>
            <span class="ml-2" x-show="remainingSeconds > 0">
                (<span x-text="remainingSeconds"></span>s)
            </span>
        </div>
    </div>

    {{-- SUCCESS/ERROR COMPLETION STATES --}}
    <div class="sync-status-container sync-status-success mb-4"
         x-show="showCompletionStatus && completionResult === 'success'"
         x-cloak>
        <div class="sync-status-header">
            <i class="fas fa-check-circle text-green-500 mr-2 text-xl"></i>
            <span class="font-bold text-green-500">SUKCES</span>
        </div>
        <div class="text-sm text-dark-secondary mt-2">
            Synchronizacja ERP zakonczona pomyslnie
        </div>
    </div>

    <div class="sync-status-container sync-status-error mb-4"
         x-show="showCompletionStatus && completionResult === 'error'"
         x-cloak>
        <div class="sync-status-header">
            <i class="fas fa-exclamation-triangle text-red-500 mr-2 text-xl"></i>
            <span class="font-bold text-red-500">BLAD</span>
        </div>
        <div class="text-sm text-dark-secondary mt-2">
            Wystapil blad podczas synchronizacji ERP
            @if($erpJobMessage ?? null)
                <p class="text-red-400 mt-1">{{ $erpJobMessage }}</p>
            @endif
        </div>
    </div>

    {{-- ========================================== --}}
    {{-- COMPACT STATUS INFO (like Shop TAB Image #8) --}}
    {{-- ========================================== --}}
    <div class="flex items-center justify-between" x-show="!isJobRunning">
        {{-- Status Info --}}
        <div class="flex items-center space-x-3">
            {{-- Status Icon --}}
            @switch($syncStatus)
                @case('synced')
                    <span class="text-2xl text-green-500">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                @break
                @case('syncing')
                    <span class="text-2xl text-blue-400">
                        <svg class="w-8 h-8 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </span>
                @break
                @case('error')
                    <span class="text-2xl text-red-500">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                @break
                @case('pending')
                    <span class="text-2xl text-yellow-500">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                @break
                @default
                    <span class="text-2xl text-gray-400">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                        </svg>
                    </span>
            @endswitch

            <div>
                {{-- Status Title --}}
                <h4 class="font-semibold @switch($syncStatus) @case('synced') text-green-400 @break @case('error') text-red-400 @break @case('syncing') text-blue-400 @break @case('pending') text-yellow-400 @break @default text-gray-400 @endswitch">
                    Status synchronizacji:
                    @switch($syncStatus)
                        @case('synced') Zsynchronizowany @break
                        @case('syncing') Synchronizacja... @break
                        @case('error') Blad @break
                        @case('pending') Oczekuje ({{ $pendingCount }}) @break
                        @default Nie zsynchronizowano @break
                    @endswitch
                </h4>

                {{-- External ID --}}
                @if($externalId)
                    <p class="text-sm text-gray-400">
                        {{ ucfirst($connection->erp_type) }} ID: <strong class="font-mono">#{{ $externalId }}</strong>
                    </p>
                @else
                    <p class="text-sm text-gray-500">
                        Produkt nie jest powiazany z tym ERP
                    </p>
                @endif

                {{-- Last Sync Time --}}
                @if($lastSyncAt)
                    <p class="text-xs text-gray-500">
                        Ostatnia synchronizacja: {{ $lastSyncAt->diffForHumans() }}
                    </p>
                @endif
            </div>
        </div>

        {{-- Action Button (like "PrestaShop" button in Image #8) --}}
        <div class="flex space-x-2">
            @if($externalId && $connection->erp_type === 'baselinker')
                <a href="https://panel.baselinker.com/inventory/products?inventory_id={{ $connection->default_inventory_id ?? '' }}&search={{ $externalId }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="btn-enterprise-secondary text-sm inline-flex items-center px-4 py-2">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    Baselinker
                </a>
            @endif
        </div>
    </div>

    {{-- Error Message Display --}}
    @if($syncStatus === 'error' && $errorMessage)
        <div class="mt-3 p-3 bg-red-900/20 border border-red-800 rounded">
            <p class="text-sm text-red-400">
                <strong>Blad:</strong> {{ $errorMessage }}
            </p>
        </div>
    @endif

    {{-- Pending Fields Warning (compact) --}}
    @if($hasPending)
        <div class="mt-3 p-3 bg-yellow-900/20 border border-yellow-700 rounded">
            <p class="text-sm text-yellow-400">
                <strong>Oczekujace zmiany:</strong>
                {{ implode(', ', array_map(fn($f) => __("products.fields.{$f}", [], 'pl') ?: ucfirst(str_replace('_', ' ', $f)), $pendingFields)) }}
            </p>
        </div>
    @endif

    {{-- ========================================== --}}
    {{-- COLLAPSIBLE: Szczegoly synchronizacji --}}
    {{-- ========================================== --}}
    <div class="shop-details-collapsible mt-3" x-data="{ expanded: false }">
        <button
            @click="expanded = !expanded"
            class="collapsible-header"
            type="button"
        >
            <span class="text-sm font-medium text-gray-300">Szczegoly synchronizacji</span>
            <svg x-show="!expanded" class="w-4 h-4 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
            <svg x-show="expanded" class="w-4 h-4 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
            </svg>
        </button>

        <div x-show="expanded" x-collapse class="collapsible-content">
            {{-- Detailed Timestamps --}}
            <div class="grid grid-cols-3 gap-4 mt-3 text-sm">
                <div class="bg-gray-900 rounded p-3">
                    <div class="text-gray-500 text-xs uppercase tracking-wide mb-1">Ostatnia sync</div>
                    <div class="text-white">{{ $lastSyncAt ? $lastSyncAt->diffForHumans() : 'Nigdy' }}</div>
                </div>
                <div class="bg-gray-900 rounded p-3">
                    <div class="text-gray-500 text-xs uppercase tracking-wide mb-1">Ostatni pull</div>
                    <div class="text-green-400">{{ $lastPullAt ? $lastPullAt->diffForHumans() : 'Nigdy' }}</div>
                </div>
                <div class="bg-gray-900 rounded p-3">
                    <div class="text-gray-500 text-xs uppercase tracking-wide mb-1">Ostatni push</div>
                    <div class="text-blue-400">{{ $lastPushAt ? $lastPushAt->diffForHumans() : 'Nigdy' }}</div>
                </div>
            </div>

            {{-- Connection Info --}}
            <div class="shop-info-compact mt-3 space-y-1">
                <p class="text-sm text-gray-400">
                    <strong class="text-gray-300">System ERP:</strong>
                    {{ ucfirst($connection->erp_type) }} - {{ $connection->instance_name }}
                </p>
                <p class="text-sm text-gray-400">
                    <strong class="text-gray-300">External ID:</strong>
                    {{ $externalId ?? 'Nie zsynchronizowane' }}
                </p>
            </div>

            {{-- Action Buttons --}}
            <div class="shop-actions-compact mt-4 flex gap-2">
                <button type="button"
                        wire:click="syncToErp({{ $connection->id }})"
                        wire:loading.attr="disabled"
                        @if($isSyncing) disabled @endif
                        class="btn-enterprise-primary text-sm inline-flex items-center {{ $isSyncing ? 'opacity-50 cursor-not-allowed' : '' }}">
                    <svg class="w-4 h-4 mr-1.5" wire:loading.class="animate-spin" wire:target="syncToErp({{ $connection->id }})" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span wire:loading.remove wire:target="syncToErp({{ $connection->id }})">
                        @if($hasPending)
                            Wyslij zmiany ({{ $pendingCount }})
                        @else
                            Synchronizuj do ERP
                        @endif
                    </span>
                    <span wire:loading wire:target="syncToErp({{ $connection->id }})">Wysylanie...</span>
                </button>

                <button type="button"
                        wire:click="pullProductDataFromErp({{ $connection->id }})"
                        wire:loading.attr="disabled"
                        @if($isSyncing) disabled @endif
                        class="btn-enterprise-secondary text-sm inline-flex items-center {{ $isSyncing ? 'opacity-50 cursor-not-allowed' : '' }}">
                    <svg class="w-4 h-4 mr-1.5" wire:loading.class="animate-spin" wire:target="pullProductDataFromErp({{ $connection->id }})" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span wire:loading.remove wire:target="pullProductDataFromErp({{ $connection->id }})">Wczytaj z ERP</span>
                    <span wire:loading wire:target="pullProductDataFromErp({{ $connection->id }})">Pobieranie...</span>
                </button>
            </div>

            {{-- Raw External Data Preview (dev only) --}}
            @if(!empty($externalData) && config('app.debug'))
                <div class="mt-4" x-data="{ showRaw: false }">
                    <button type="button"
                            @click="showRaw = !showRaw"
                            class="text-xs text-gray-500 hover:text-gray-400 flex items-center">
                        <svg class="w-3 h-3 mr-1 transition-transform" x-bind:class="showRaw ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        Podglad danych z ERP (debug)
                    </button>

                    <div x-show="showRaw" x-collapse class="mt-2">
                        <pre class="text-xs bg-gray-950 text-gray-400 p-3 rounded overflow-x-auto max-h-40 overflow-y-auto">{{ json_encode($externalData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ETAP_08.5: Alpine component 'erpSyncStatusTracker' is registered in app.js --}}
@endif
