{{-- Resume banner - niedokonczony skan --}}
@if($scanPhase === 'idle' && $activeScanSessionId)
    <div class="mb-4 bg-amber-900/20 border border-amber-700 rounded-lg p-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span class="text-amber-300 text-sm">
                Wykryto niedokonczony skan
                @if($totalChunks > 0)
                    ({{ $processedChunks }}/{{ $totalChunks }} chunkow)
                @endif
            </span>
        </div>
        <button wire:click="resumeScan"
                class="px-3 py-1 text-sm bg-amber-600 text-white rounded hover:bg-amber-500 transition-colors">
            Wznow skan
        </button>
    </div>
@endif

{{-- Active scan progress --}}
<div x-show="$wire.scanPhase !== 'idle'"
     x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 transform -translate-y-2"
     x-transition:enter-end="opacity-100 transform translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 transform translate-y-0"
     x-transition:leave-end="opacity-0 transform -translate-y-2"
     class="mb-4 bg-gray-800 border border-gray-700 rounded-lg p-4">

    {{-- Phase label --}}
    <div class="flex items-center gap-2 mb-3">
        <svg class="w-4 h-4 text-[#e0ac7e] animate-spin flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        <span class="text-sm text-gray-300">
            @switch($scanPhase)
                @case('prefetching')
                    Pobieranie danych ze zrodel...
                    @break
                @case('scanning')
                    Skanowanie konfliktow...
                    @break
                @case('finalizing')
                    Finalizacja wynikow...
                    @break
                @default
                    Trwa przetwarzanie...
            @endswitch
        </span>
    </div>

    {{-- Progress bar --}}
    <div class="matrix-progress-bar w-full bg-gray-700 rounded-full h-3">
        <div class="matrix-progress-fill bg-green-500 h-3 rounded-full transition-all duration-300"
             :style="'width: ' + $wire.scanProgress + '%'">
        </div>
    </div>

    {{-- Stats row --}}
    <div class="flex items-center justify-between mt-2">
        <span class="text-xs text-gray-400">
            @if($totalChunks > 0)
                Chunk {{ $processedChunks }}/{{ $totalChunks }}
            @else
                Przygotowanie...
            @endif
        </span>
        <span class="text-xs font-medium text-gray-300">
            {{ $scanProgress }}%
        </span>
        <span class="text-xs text-gray-400">
            @if($estimatedTimeRemaining)
                Pozostalo: {{ $estimatedTimeRemaining }}
            @else
                Szacowanie czasu...
            @endif
        </span>
    </div>

    {{-- Cancel button --}}
    <div class="mt-3">
        <button wire:click="cancelScan"
                class="px-3 py-1 text-sm text-red-400 hover:text-red-300 border border-red-700 hover:border-red-600 rounded transition-colors">
            Anuluj skan
        </button>
    </div>

</div>
