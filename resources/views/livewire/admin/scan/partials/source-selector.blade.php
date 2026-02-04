{{-- Source Selector Panel --}}
<div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-700 bg-gray-700/50">
        <h3 class="text-sm font-medium text-white">Wybierz Zrodlo</h3>
    </div>

    <div class="p-4 space-y-4">
        {{-- Grouped Sources --}}
        @foreach($groupedSources as $group => $sourcesInGroup)
            <div>
                <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-2">{{ $group }}</h4>
                <div class="space-y-1">
                    @foreach($sourcesInGroup as $source)
                        <button wire:click="selectSource('{{ $source['type'] }}', {{ $source['id'] }})"
                                wire:key="source-{{ $source['type'] }}-{{ $source['id'] }}"
                                class="w-full flex items-center gap-3 px-3 py-2 text-sm rounded-md transition-colors duration-150
                                       {{ $selectedSourceType === $source['type'] && $selectedSourceId === $source['id']
                                          ? 'bg-[#e0ac7e]/20 text-[#e0ac7e] border border-[#e0ac7e]/30'
                                          : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                            {{-- Icon based on source type --}}
                            @switch($source['icon'])
                                @case('subiekt')
                                    <div class="w-8 h-8 flex items-center justify-center rounded bg-purple-600/20">
                                        <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                                        </svg>
                                    </div>
                                    @break
                                @case('baselinker')
                                    <div class="w-8 h-8 flex items-center justify-center rounded bg-blue-600/20">
                                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101"/>
                                        </svg>
                                    </div>
                                    @break
                                @case('dynamics')
                                    <div class="w-8 h-8 flex items-center justify-center rounded bg-green-600/20">
                                        <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                                        </svg>
                                    </div>
                                    @break
                                @case('prestashop')
                                    <div class="w-8 h-8 flex items-center justify-center rounded bg-pink-600/20">
                                        <svg class="w-4 h-4 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                    </div>
                                    @break
                                @default
                                    <div class="w-8 h-8 flex items-center justify-center rounded bg-gray-600/20">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                                        </svg>
                                    </div>
                            @endswitch

                            <div class="flex-1 text-left">
                                <div class="flex items-center gap-1">
                                    <span class="font-medium">{{ $source['name'] }}</span>
                                    @if($source['is_default'] ?? false)
                                        <span class="px-1.5 py-0.5 text-xs bg-amber-600/20 text-amber-400 rounded">DOM</span>
                                    @endif
                                </div>
                                @if(isset($source['url']))
                                    <div class="text-xs text-gray-500 truncate">{{ $source['url'] }}</div>
                                @endif
                            </div>

                            {{-- Selected indicator --}}
                            @if($selectedSourceType === $source['type'] && $selectedSourceId === $source['id'])
                                <svg class="w-4 h-4 text-[#e0ac7e]" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach

        @if($sources->isEmpty())
            <div class="text-center py-4">
                <p class="text-sm text-gray-400">Brak dostepnych zrodel</p>
                <p class="text-xs text-gray-500 mt-1">Dodaj polaczenie ERP lub sklep PrestaShop</p>
            </div>
        @endif
    </div>

    {{-- Scan Actions --}}
    @if($selectedSourceType && $selectedSourceId)
        <div class="px-4 py-3 border-t border-gray-700 bg-gray-700/30">
            <div class="space-y-2">
                @if($activeTab === 'links')
                    <button wire:click="startLinksScan"
                            wire:loading.attr="disabled"
                            {{ $activeScanSessionId ? 'disabled' : '' }}
                            class="w-full px-3 py-2 text-sm font-medium text-white rounded-md
                                   btn-enterprise-primary disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="startLinksScan">Rozpocznij Skan Powiazan</span>
                        <span wire:loading wire:target="startLinksScan">Uruchamianie...</span>
                    </button>
                @elseif($activeTab === 'missing_ppm')
                    <button wire:click="startMissingInPpmScan"
                            wire:loading.attr="disabled"
                            {{ $activeScanSessionId ? 'disabled' : '' }}
                            class="w-full px-3 py-2 text-sm font-medium text-white rounded-md
                                   btn-enterprise-primary disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="startMissingInPpmScan">Skan Brakujacych w PPM</span>
                        <span wire:loading wire:target="startMissingInPpmScan">Uruchamianie...</span>
                    </button>
                @elseif($activeTab === 'missing_source')
                    <button wire:click="startMissingInSourceScan"
                            wire:loading.attr="disabled"
                            {{ $activeScanSessionId ? 'disabled' : '' }}
                            class="w-full px-3 py-2 text-sm font-medium text-white rounded-md
                                   btn-enterprise-primary disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="startMissingInSourceScan">Skan Brakujacych w Zrodle</span>
                        <span wire:loading wire:target="startMissingInSourceScan">Uruchamianie...</span>
                    </button>
                @endif

                @if($activeScanSessionId)
                    <button wire:click="cancelScan"
                            class="w-full px-3 py-2 text-sm font-medium text-red-400 hover:text-red-300
                                   bg-red-900/20 hover:bg-red-900/30 border border-red-700 rounded-md">
                        Anuluj Skan
                    </button>
                @endif
            </div>
        </div>
    @endif
</div>
