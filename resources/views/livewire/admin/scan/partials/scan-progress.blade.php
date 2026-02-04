{{-- Scan Progress Panel --}}
@php
    $scan = $activeScan;
    $progress = $stats['progress'] ?? 0;
    $status = $stats['status'] ?? 'pending';
@endphp

<div class="bg-blue-900/30 border border-blue-700 rounded-lg p-4 mb-4">
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-3">
            {{-- Animated spinner --}}
            <div class="w-8 h-8 flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-400 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            <div>
                <h4 class="text-sm font-medium text-blue-300">
                    @if($scan)
                        {{ $scan->getScanTypeLabel() }} - {{ $this->getSourceName($scan->source_type, $scan->source_id) }}
                    @else
                        Skanowanie w toku...
                    @endif
                </h4>
                <p class="text-xs text-blue-400/70">
                    @if($scan && $scan->started_at)
                        Rozpoczeto: {{ $scan->started_at->format('H:i:s') }}
                        ({{ $scan->getDurationForHumans() }})
                    @else
                        Oczekiwanie na start...
                    @endif
                </p>
            </div>
        </div>

        {{-- Status badge --}}
        <div>
            @switch($status)
                @case('pending')
                    <span class="px-2 py-1 text-xs font-medium bg-yellow-600/20 text-yellow-400 border border-yellow-500/30 rounded">
                        Oczekuje
                    </span>
                    @break
                @case('running')
                    <span class="px-2 py-1 text-xs font-medium bg-blue-600/20 text-blue-400 border border-blue-500/30 rounded">
                        W trakcie
                    </span>
                    @break
                @default
                    <span class="px-2 py-1 text-xs font-medium bg-gray-600/20 text-gray-400 border border-gray-500/30 rounded">
                        {{ ucfirst($status) }}
                    </span>
            @endswitch
        </div>
    </div>

    {{-- Progress Bar --}}
    <div class="mb-3">
        <div class="flex justify-between text-xs text-blue-300 mb-1">
            <span>Postep</span>
            <span>{{ number_format($progress, 0) }}%</span>
        </div>
        <div class="w-full h-2 bg-gray-700 rounded-full overflow-hidden">
            <div class="h-full bg-gradient-to-r from-blue-500 to-blue-400 rounded-full transition-all duration-300"
                 style="width: {{ $progress }}%"></div>
        </div>
    </div>

    {{-- Statistics Grid --}}
    <div class="grid grid-cols-4 gap-3">
        <div class="text-center p-2 bg-gray-800/50 rounded">
            <div class="text-lg font-semibold text-white">{{ number_format($stats['total'] ?? 0) }}</div>
            <div class="text-xs text-gray-400">Do przeskanowania</div>
        </div>
        <div class="text-center p-2 bg-green-900/30 rounded border border-green-800/30">
            <div class="text-lg font-semibold text-green-400">{{ number_format($stats['matched'] ?? 0) }}</div>
            <div class="text-xs text-green-400/70">Dopasowanych</div>
        </div>
        <div class="text-center p-2 bg-yellow-900/30 rounded border border-yellow-800/30">
            <div class="text-lg font-semibold text-yellow-400">{{ number_format($stats['unmatched'] ?? 0) }}</div>
            <div class="text-xs text-yellow-400/70">Niedopasowanych</div>
        </div>
        <div class="text-center p-2 bg-red-900/30 rounded border border-red-800/30">
            <div class="text-lg font-semibold text-red-400">{{ number_format($stats['errors'] ?? 0) }}</div>
            <div class="text-xs text-red-400/70">Bledow</div>
        </div>
    </div>
</div>
