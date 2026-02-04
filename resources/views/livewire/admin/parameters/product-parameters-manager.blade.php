<div class="min-h-screen bg-gray-900">
    {{-- Header --}}
    <div class="border-b border-gray-700 bg-gray-800/50">
        <div class="px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-white">Zarzadzanie parametrami produktu</h1>
                    <p class="text-sm text-gray-400 mt-1">Atrybuty wariantow, marki, magazyny i typy produktow</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs Navigation --}}
    <div class="border-b border-gray-700 bg-gray-800/30">
        <div class="px-4 sm:px-6 lg:px-8">
            <nav class="-mb-px flex space-x-6 overflow-x-auto" aria-label="Tabs">
                @foreach($tabs as $tabKey => $tab)
                    <button wire:click="switchTab('{{ $tabKey }}')"
                            class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors
                                   {{ $activeTab === $tabKey
                                      ? 'border-orange-500 text-orange-400'
                                      : 'border-transparent text-gray-400 hover:text-gray-300 hover:border-gray-600' }}">
                        <div class="flex items-center gap-2">
                            @switch($tab['icon'])
                                @case('tags')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                    @break
                                @case('building')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    @break
                                @case('warehouse')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                                    </svg>
                                    @break
                                @case('cubes')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    @break
                                @case('trash')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    @break
                                @case('shield-check')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                    @break
                            @endswitch
                            {{ $tab['label'] }}
                        </div>
                    </button>
                @endforeach
            </nav>
        </div>
    </div>

    {{-- Tab Content --}}
    <div class="px-4 sm:px-6 lg:px-8 py-6">
        {{-- Tab Description --}}
        <div class="mb-6 p-4 bg-gray-800/50 rounded-lg border border-gray-700">
            <p class="text-sm text-gray-400">{{ $tabs[$activeTab]['description'] }}</p>
        </div>

        {{-- Dynamic Tab Content --}}
        @if($activeTab === 'attributes')
            @livewire('admin.variants.variant-panel-container', key('attributes-tab'))
        @elseif($activeTab === 'manufacturers')
            @livewire('admin.parameters.manufacturer-manager', key('manufacturers-tab'))
        @elseif($activeTab === 'warehouses')
            @livewire('admin.parameters.warehouse-manager', key('warehouses-tab'))
        @elseif($activeTab === 'product-types')
            @livewire('admin.products.product-type-manager', ['embedded' => true], key('product-types-tab'))
        @elseif($activeTab === 'data-cleanup')
            @livewire('admin.parameters.orphan-data-manager', key('data-cleanup-tab'))
        @elseif($activeTab === 'status-monitoring')
            @livewire('admin.parameters.status-monitoring-config', key('status-monitoring-tab'))
        @endif
    </div>
</div>
