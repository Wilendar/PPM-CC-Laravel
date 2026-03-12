<div>
    {{-- LOCATION BROWSER CARD --}}
    <div class="feature-browser location-browser--fullheight">
        {{-- HEADER --}}
        <div class="feature-browser__header">
            <div class="flex items-center gap-4">
                <div class="location-browser__header-icon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-white">Lokalizacje magazynowe</h3>
                    <p class="text-sm text-gray-400">Zarzadzanie lokalizacjami produktow na magazynach</p>
                </div>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                @if($selectedWarehouseId)
                    <div class="location-browser__stat-pill">
                        <span class="location-browser__stat-pill__label">Lokalizacje:</span>
                        <span class="location-browser__stat-pill__value">{{ $stats['total'] }}</span>
                    </div>
                    <div class="location-browser__stat-pill">
                        <span class="location-browser__stat-pill__label">Zajete:</span>
                        <span class="location-browser__stat-pill__value">{{ $stats['occupied'] }}</span>
                    </div>
                    <div class="location-browser__stat-pill">
                        <span class="location-browser__stat-pill__label">Strefy:</span>
                        <span class="location-browser__stat-pill__value">{{ $stats['zones_count'] }}</span>
                    </div>
                    <button wire:click="populateLocations" wire:loading.attr="disabled"
                            class="btn-enterprise-secondary text-sm px-3 py-1.5">
                        <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Skanuj
                    </button>
                    <button wire:click="refreshCounts" wire:loading.attr="disabled"
                            class="btn-enterprise-secondary text-sm px-3 py-1.5">
                        <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Odswiez
                    </button>
                    <button wire:click="openZoneConfigModal"
                            class="btn-enterprise-secondary text-sm px-3 py-1.5"
                            title="Konfiguracja nazewnictwa stref">
                        <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Strefy
                    </button>
                @endif
            </div>
        </div>

        {{-- 3-COLUMN GRID --}}
        <div class="feature-browser__columns">
            @include('livewire.admin.parameters.partials.location-warehouse-list')
            @include('livewire.admin.parameters.partials.location-tree')
            @include('livewire.admin.parameters.partials.location-detail-panel')
        </div>
    </div>

    {{-- Modals --}}
    @include('livewire.admin.parameters.partials.location-edit-modal')
    @include('livewire.admin.parameters.partials.location-create-modal')
    @include('livewire.admin.parameters.partials.location-zone-modal')
    @include('livewire.admin.parameters.partials.location-zone-config-modal')
</div>
