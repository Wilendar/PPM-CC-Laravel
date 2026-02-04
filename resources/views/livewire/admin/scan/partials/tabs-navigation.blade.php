{{-- Tabs Navigation --}}
<div class="border-b border-gray-800">
    <div class="flex items-center px-4 py-2 gap-1">
        {{-- Skan Powiazan Tab --}}
        <button wire:click="setTab('links')"
                class="px-4 py-2 text-sm font-medium rounded-t-md transition-colors duration-150
                       {{ $activeTab === 'links' ? 'bg-[#e0ac7e] text-gray-900' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                <span>Skan Powiązań</span>
            </div>
        </button>

        {{-- Brak w PPM Tab --}}
        <button wire:click="setTab('missing_ppm')"
                class="px-4 py-2 text-sm font-medium rounded-t-md transition-colors duration-150
                       {{ $activeTab === 'missing_ppm' ? 'bg-[#e0ac7e] text-gray-900' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <span>Brak w PPM</span>
            </div>
        </button>

        {{-- Brak w Zrodle Tab --}}
        <button wire:click="setTab('missing_source')"
                class="px-4 py-2 text-sm font-medium rounded-t-md transition-colors duration-150
                       {{ $activeTab === 'missing_source' ? 'bg-[#e0ac7e] text-gray-900' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Brak w Źródle</span>
            </div>
        </button>

        {{-- Historia Tab --}}
        <button wire:click="setTab('history')"
                class="px-4 py-2 text-sm font-medium rounded-t-md transition-colors duration-150
                       {{ $activeTab === 'history' ? 'bg-[#e0ac7e] text-gray-900' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Historia</span>
            </div>
        </button>

        {{-- Scan Status Indicator --}}
        @if($activeScanSessionId)
            <div class="ml-auto flex items-center gap-2 px-3 py-1 bg-blue-900/50 border border-blue-700 rounded-md">
                <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                <span class="text-xs text-blue-300">Skan w toku...</span>
            </div>
        @endif
    </div>
</div>
