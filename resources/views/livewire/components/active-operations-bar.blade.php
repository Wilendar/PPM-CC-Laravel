{{-- Active Operations Bar Component - ETAP_07c FAZA 2 --}}
{{-- Aggregates multiple JobProgressBar instances in a unified container --}}
{{-- Root div ZAWSZE renderowany (Livewire wymaga) --}}

<div wire:poll.5s="refreshActiveJobs"
     class="{{ $this->hasActiveOperations ? 'mb-6' : '' }}"
     x-data="{ collapsed: @entangle('isCollapsed') }">
@if($this->hasActiveOperations)

    {{-- Header with title and controls --}}
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-3">
            {{-- Collapse toggle button --}}
            <button @click="collapsed = !collapsed"
                    class="flex items-center gap-2 text-gray-400 hover:text-white transition-colors duration-200">
                <svg class="w-4 h-4 transition-transform duration-200"
                     :class="{ '-rotate-90': collapsed }"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
                <span class="text-sm font-semibold uppercase tracking-wider">Aktywne Operacje</span>
            </button>

            {{-- Active count badge --}}
            <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold rounded-full
                {{ $this->runningCount > 0 ? 'bg-blue-500/20 text-blue-400' : 'bg-gray-500/20 text-gray-400' }}">
                {{ $this->activeCount }}
            </span>

            {{-- Awaiting user badge (pulsing) --}}
            @if($this->awaitingCount > 0)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-bold rounded-full bg-yellow-500/20 text-yellow-400 animate-pulse">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    {{ $this->awaitingCount }} wymaga akcji
                </span>
            @endif
        </div>

        {{-- Clear completed button --}}
        @if($this->activeCount > $this->runningCount)
            <button wire:click="clearCompleted"
                    class="text-xs text-gray-500 hover:text-gray-300 transition-colors duration-200">
                Ukryj zakonczone
            </button>
        @endif
    </div>

    {{-- Progress bars container --}}
    <div x-show="!collapsed"
         x-collapse
         class="space-y-3">
        @foreach($activeJobIds as $jobId)
            <livewire:components.job-progress-bar
                :jobId="$jobId"
                :shopId="$shopId"
                :key="'progress-'.$jobId" />
        @endforeach
    </div>

    {{-- Collapsed summary --}}
    <div x-show="collapsed"
         x-collapse
         class="px-4 py-2 rounded-lg bg-gray-800/50 border border-gray-700/50">
        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-400">
                {{ $this->activeCount }} {{ $this->activeCount === 1 ? 'operacja' : 'operacji' }} w toku
            </span>
            @if($this->runningCount > 0)
                <span class="flex items-center gap-1 text-blue-400">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ $this->runningCount }} aktywnych
                </span>
            @endif
        </div>
    </div>
@endif
</div>
