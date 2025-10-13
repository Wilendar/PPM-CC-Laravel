{{-- Job Progress Bar Component --}}
<div x-data="{
        hideAfterCompletion: @entangle('isCompleted'),
        visible: @entangle('isVisible')
     }"
     x-show="visible"
     x-init="
         $watch('hideAfterCompletion', value => {
             if (value) {
                 setTimeout(() => visible = false, 60000);
             }
         })
     "
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform scale-95"
     x-transition:enter-end="opacity-100 transform scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 transform scale-100"
     x-transition:leave-end="opacity-0 transform scale-95"
     wire:poll.3s="fetchProgress"
     class="backdrop-blur-xl rounded-lg shadow-lg overflow-hidden border"
     :class="{
         'bg-gradient-to-r from-blue-900/80 to-cyan-900/80 border-blue-500/30': '{{ $this->status }}' === 'running',
         'bg-gradient-to-r from-green-900/80 to-emerald-900/80 border-green-500/30': '{{ $this->status }}' === 'completed',
         'bg-gradient-to-r from-red-900/80 to-rose-900/80 border-red-500/30': '{{ $this->status }}' === 'failed',
         'bg-gradient-to-r from-gray-900/80 to-gray-800/80 border-gray-500/30': '{{ $this->status }}' === 'pending'
     }">

    <!-- Progress Content -->
    <div class="p-4">
        <div class="flex items-center justify-between gap-3">
            <!-- Status Icon -->
            <div class="flex-shrink-0">
                @if($this->status === 'running')
                    <svg class="w-6 h-6 text-blue-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                @elseif($this->status === 'completed')
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                @elseif($this->status === 'failed')
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                @else
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                @endif
            </div>

            <!-- Progress Info -->
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-white">{{ $this->message }}</p>
                <div class="mt-2 flex items-center gap-2">
                    <!-- Progress Bar -->
                    <div class="flex-1 h-2 bg-black/30 rounded-full overflow-hidden">
                        <div class="h-full transition-all duration-500 ease-out rounded-full"
                             :class="{
                                 'bg-blue-500': '{{ $this->status }}' === 'running',
                                 'bg-green-500': '{{ $this->status }}' === 'completed',
                                 'bg-red-500': '{{ $this->status }}' === 'failed',
                                 'bg-gray-500': '{{ $this->status }}' === 'pending'
                             }"
                             style="width: {{ $this->percentage }}%"></div>
                    </div>
                    <!-- Percentage -->
                    <span class="text-xs font-bold text-white">{{ $this->percentage }}%</span>
                </div>
            </div>

            <!-- Error Badge (if errors exist) -->
            @if($this->errorCount > 0)
                <button wire:click="showErrors"
                        class="flex-shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-500/20 hover:bg-red-500/30 border border-red-500/30 transition-colors duration-200">
                    <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3l-6.928-12c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <span class="text-xs font-bold text-red-400">{{ $this->errorCount }}</span>
                </button>
            @endif

            <!-- Close Button -->
            <button wire:click="hide"
                    class="flex-shrink-0 rounded-lg p-1.5 hover:bg-white/10 transition-colors duration-200">
                <svg class="w-5 h-5 text-gray-400 hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>
</div>
