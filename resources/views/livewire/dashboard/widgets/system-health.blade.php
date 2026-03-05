<div class="dashboard-widget" wire:poll.60s.visible="runChecks" role="region" aria-label="Zdrowie systemu">
    {{-- Header --}}
    <div class="dashboard-widget__header">
        <span class="dashboard-widget__title">Zdrowie systemu</span>
        <div class="flex items-center gap-2">
            <button wire:click="runChecks"
                    wire:loading.class="animate-spin"
                    wire:target="runChecks"
                    class="text-gray-500 hover:text-amber-400 transition-colors"
                    title="Sprawdz ponownie">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
            <div class="dashboard-widget__icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
        </div>
    </div>

    {{-- Health indicators --}}
    <div class="space-y-3">
        @foreach(['database' => 'Baza danych', 'cache' => 'Cache', 'storage' => 'Dysk', 'queue' => 'Kolejka'] as $key => $label)
            @php $check = $checks[$key] ?? ['status' => 'unknown', 'message' => 'Brak danych', 'details' => '']; @endphp
            <div class="health-indicator">
                <span class="health-dot health-dot--{{ $check['status'] }}"></span>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-200">{{ $label }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full font-semibold
                            {{ $check['status'] === 'healthy' ? 'bg-emerald-500/20 text-emerald-400' : '' }}
                            {{ $check['status'] === 'warning' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                            {{ $check['status'] === 'error' ? 'bg-red-500/20 text-red-400' : '' }}
                            {{ $check['status'] === 'unknown' ? 'bg-gray-500/20 text-gray-400' : '' }}
                        ">
                            {{ $check['status'] === 'healthy' ? 'OK' : ($check['status'] === 'warning' ? 'Uwaga' : ($check['status'] === 'error' ? 'Blad' : 'N/A')) }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5 truncate" title="{{ $check['details'] }}">
                        {{ $check['message'] }}
                        @if(!empty($check['details']))
                            &middot; {{ $check['details'] }}
                        @endif
                    </p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Expandable DB Details --}}
    @if(!empty($dbDetails))
        <div x-data="{ open: false }" class="mt-4">
            <button @click="open = !open"
                    class="flex items-center gap-2 w-full text-xs text-gray-500 hover:text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="uppercase tracking-wide font-medium">Szczegoly bazy danych</span>
            </button>
            <div x-show="open" x-collapse class="mt-3 space-y-3">
                {{-- Connection & slow queries --}}
                <div class="flex gap-4 text-xs">
                    <span class="text-gray-500">Polaczenia: <span class="text-gray-300 font-semibold">{{ $dbDetails['connections'] ?? 0 }}</span></span>
                    <span class="text-gray-500">Slow queries: <span class="text-gray-300 font-semibold">{{ $dbDetails['slow_queries'] ?? 0 }}</span></span>
                </div>
                {{-- Top tables --}}
                @if(!empty($dbDetails['tables']))
                    <div class="space-y-1.5">
                        @foreach($dbDetails['tables'] as $table)
                            <div class="flex items-center justify-between text-xs px-2 py-1.5 rounded bg-gray-800/50">
                                <span class="text-gray-400 font-mono truncate">{{ $table['name'] }}</span>
                                <div class="flex gap-3 flex-shrink-0">
                                    <span class="text-gray-500">{{ $table['rows'] }} rows</span>
                                    <span class="text-gray-400 font-semibold">{{ $table['size'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
