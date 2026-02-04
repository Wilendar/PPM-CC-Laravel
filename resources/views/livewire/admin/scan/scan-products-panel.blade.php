<div class="scan-products-panel bg-gray-900 min-h-screen">
    {{-- Header --}}
    <div class="border-b border-gray-800 bg-gray-900/95 backdrop-blur-sm sticky top-0 z-40">
        <div class="flex items-center justify-between px-4 py-3">
            <div>
                <h1 class="text-lg font-semibold text-white">Skanowanie Produktow</h1>
                <p class="text-xs text-gray-400 mt-0.5">Wyszukiwanie i powiazywanie produktow miedzy PPM a zrodlami zewnetrznymi</p>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="$refresh"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-300 hover:text-white
                               bg-gray-800 hover:bg-gray-700 border border-gray-700 hover:border-gray-600
                               rounded-md transition-colors duration-150">
                    <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Odswiez
                </button>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session()->has('success'))
        <div class="mx-4 mt-4 p-3 bg-green-900/50 border border-green-700 rounded-md text-green-300 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session()->has('error'))
        <div class="mx-4 mt-4 p-3 bg-red-900/50 border border-red-700 rounded-md text-red-300 text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Tabs Navigation --}}
    @include('livewire.admin.scan.partials.tabs-navigation')

    {{-- Main Content --}}
    <div class="p-4">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
            {{-- Left Column: Source Selector --}}
            <div class="lg:col-span-1">
                @include('livewire.admin.scan.partials.source-selector')
            </div>

            {{-- Right Column: Content based on active tab --}}
            <div class="lg:col-span-3">
                @if($activeTab === 'history')
                    @include('livewire.admin.scan.partials.history-list')
                @else
                    {{-- Scan Progress (if active) --}}
                    @if($activeScanSessionId)
                        <div wire:poll.2s="checkScanStatus">
                            @include('livewire.admin.scan.partials.scan-progress')
                        </div>
                    @endif

                    {{-- Bulk Actions Toolbar --}}
                    @include('livewire.admin.scan.partials.bulk-actions-toolbar')

                    {{-- Results Table --}}
                    @include('livewire.admin.scan.partials.results-table')
                @endif
            </div>
        </div>
    </div>
</div>
