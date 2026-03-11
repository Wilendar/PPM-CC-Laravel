{{-- Job Progress Bar Component - ETAP_07c FAZA 2: Rich Progress with Accordion --}}
{{-- FAZA 3: Added ARIA accessibility attributes --}}
{{-- FAZA 4 FIX: Added wire:poll.3s for real-time progress updates --}}
<div wire:poll.3s="fetchProgress"
     x-data="{
        hideAfterCompletion: @entangle('isCompleted'),
        visible: @entangle('isVisible'),
        expanded: false
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
     role="region"
     aria-label="Postep zadania: {{ $this->jobTypeLabel }}"
     aria-live="polite"
     class="job-progress-bar backdrop-blur-sm rounded-lg overflow-hidden
         @switch($this->status)
             @case('running') job-progress-bar--running @break
             @case('completed') job-progress-bar--completed @break
             @case('failed') job-progress-bar--failed @break
             @case('awaiting_user')
                 {{-- ETAP_07c: Blue border when user took action, yellow when awaiting --}}
                 @if($this->isUserActionTaken) job-progress-bar--running @else job-progress-bar--awaiting @endif
             @break
             @default job-progress-bar--pending
         @endswitch
     ">

    <!-- Main Progress Content -->
    <div class="p-4">
        <div class="flex items-center justify-between gap-3">
            <!-- Status Icon + Job Type Badge -->
            <div class="flex items-center gap-2 flex-shrink-0">
                @include('livewire.components.partials.job-progress-icon', ['status' => $this->status, 'userActionTaken' => $this->isUserActionTaken])

                {{-- Job Type Badge --}}
                <span class="px-2 py-0.5 text-xs font-medium rounded-full
                    @switch($this->jobType)
                        @case('import') bg-blue-500/20 text-blue-300 @break
                        @case('export') bg-green-500/20 text-green-300 @break
                        @case('category_analysis') bg-yellow-500/20 text-yellow-300 @break
                        @case('bulk_export') bg-emerald-500/20 text-emerald-300 @break
                        @case('bulk_update') bg-orange-500/20 text-orange-300 @break
                        @case('stock_sync') bg-purple-500/20 text-purple-300 @break
                        @case('price_sync') bg-pink-500/20 text-pink-300 @break
                        @default bg-gray-500/20 text-gray-300
                    @endswitch">
                    {{ $this->jobTypeLabel }}
                </span>
            </div>

            <!-- Progress Info -->
            <div class="flex-1 min-w-0">
                {{-- Phase label for running jobs --}}
                @if($this->phaseLabel && $this->status === 'running')
                    <p class="text-xs text-gray-400 mb-0.5">{{ $this->phaseLabel }}</p>
                @endif

                <p class="text-sm font-semibold text-white truncate">{{ $this->message }}</p>

                {{-- Initiated by + Duration --}}
                <div class="flex items-center gap-3 mt-0.5">
                    @if($this->initiatedBy)
                        <span class="text-xs text-gray-400">
                            <span class="text-gray-500">Rozpoczal:</span> {{ $this->initiatedBy }}
                        </span>
                    @endif
                    @if($this->duration)
                        <span class="text-xs text-gray-400">
                            <span class="text-gray-500">Czas:</span> {{ $this->duration }}
                        </span>
                    @endif
                </div>

                <!-- Progress Bar with ARIA accessibility -->
                <div class="mt-2 flex items-center gap-2"
                     role="progressbar"
                     aria-valuenow="{{ $this->percentage }}"
                     aria-valuemin="0"
                     aria-valuemax="100"
                     aria-label="{{ $this->jobTypeLabel }}: {{ $this->percentage }}% ukonczone">
                    <div class="flex-1 h-2 bg-black/30 rounded-full overflow-hidden">
                        {{-- FIX (2025-12-02): Blue bar when userActionTaken, yellow when awaiting --}}
                        <div class="h-full transition-all duration-500 ease-out rounded-full"
                             :class="{
                                 'bg-blue-500': '{{ $this->status }}' === 'running' || ('{{ $this->status }}' === 'awaiting_user' && {{ $this->isUserActionTaken ? 'true' : 'false' }}),
                                 'bg-green-500': '{{ $this->status }}' === 'completed',
                                 'bg-red-500': '{{ $this->status }}' === 'failed',
                                 'bg-gray-500': '{{ $this->status }}' === 'pending',
                                 'bg-yellow-500': '{{ $this->status }}' === 'awaiting_user' && !{{ $this->isUserActionTaken ? 'true' : 'false' }}
                             }"
                             style="width: {{ $this->percentage }}%"></div>
                    </div>
                    <span class="text-xs font-bold text-white w-10 text-right" aria-hidden="true">{{ $this->percentage }}%</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2 flex-shrink-0">
                {{-- ETAP_07c: Action Button for awaiting_user status OR Processing state --}}
                @if($this->isAwaitingUser && $this->hasActionButton)
                    @if($this->isUserActionTaken)
                        {{-- User already clicked - show processing state --}}
                        <div class="flex items-center gap-1.5 px-4 py-2 rounded-lg bg-blue-500/20 border border-blue-500/30 text-blue-300 text-sm">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Przetwarzanie wybranych kategorii...</span>
                        </div>
                    @else
                        {{-- Show action button --}}
                        <button wire:click="handleActionButton"
                                class="flex items-center gap-1.5 px-4 py-2 rounded-lg bg-yellow-500 hover:bg-yellow-400 text-gray-900 font-bold text-sm transition-all duration-200 shadow-lg hover:shadow-xl animate-pulse hover:animate-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <span>{{ $this->actionButtonLabel }}</span>
                        </button>
                    @endif
                @endif

                {{-- Error Badge --}}
                @if($this->errorCount > 0)
                    <button wire:click="showErrors"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-500/20 hover:bg-red-500/30 border border-red-500/30 transition-colors duration-200">
                        <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3l-6.928-12c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <span class="text-xs font-bold text-red-400">{{ $this->errorCount }}</span>
                    </button>
                @endif

                {{-- Conflict Resolution --}}
                @if($this->conflictCount > 0 && $this->status === 'completed')
                    @if($this->hasSingleConflict)
                        <button wire:click="resolveConflict"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-orange-500/20 hover:bg-orange-500/30 border border-orange-500/30 transition-colors duration-200">
                            <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3l-6.928-12c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <span class="text-xs font-bold text-orange-400">Rozwiaz konflikt</span>
                        </button>
                    @elseif($this->hasBulkConflicts)
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-orange-500/20 border border-orange-500/30">
                            <span class="text-xs font-medium text-orange-400">{{ $this->conflictCount }} konfliktow</span>
                            <button wire:click="downloadConflictsCsv"
                                    class="px-2 py-0.5 text-xs bg-orange-600 hover:bg-orange-700 text-white rounded transition-colors duration-200">
                                CSV
                            </button>
                        </div>
                    @endif
                @endif

                {{-- Expand/Collapse Button --}}
                <button @click="expanded = !expanded"
                        class="rounded-lg p-1.5 hover:bg-white/10 transition-colors duration-200"
                        :class="{ 'bg-white/10': expanded }"
                        :aria-expanded="expanded"
                        aria-controls="job-details-{{ $jobId }}"
                        :aria-label="expanded ? 'Zwi\u0144 szczego\u0142y' : 'Rozwi\u0144 szczego\u0142y'">
                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-200"
                         :class="{ 'rotate-180': expanded }"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                {{-- Close/Cancel Button --}}
                <button wire:click="cancelJob"
                        wire:confirm="Czy na pewno chcesz anulowac to zadanie?"
                        class="rounded-lg p-1.5 hover:bg-red-500/20 hover:text-red-400 transition-colors duration-200"
                        aria-label="Anuluj zadanie i zamknij"
                        title="Anuluj zadanie">
                    <svg class="w-5 h-5 text-gray-400 hover:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Expandable Details Section (Accordion) -->
    <div x-show="expanded"
         x-collapse
         class="border-t border-white/10">
        <div class="p-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            {{-- Shop Info --}}
            <div>
                <p class="text-gray-500 text-xs mb-1">Sklep</p>
                <p class="text-white font-medium">{{ $this->shopName ?? 'N/A' }}</p>
            </div>

            {{-- Job ID --}}
            <div>
                <p class="text-gray-500 text-xs mb-1">Job ID</p>
                <p class="text-gray-300 font-mono text-xs truncate" title="{{ $this->jobIdShort }}">
                    {{ $this->jobIdShort }}
                </p>
            </div>

            {{-- Products Count --}}
            <div>
                <p class="text-gray-500 text-xs mb-1">Produkty</p>
                <p class="text-white">
                    <span class="font-bold">{{ $this->currentCount }}</span>
                    <span class="text-gray-400">/ {{ $this->totalCount }}</span>
                </p>
            </div>

            {{-- Started At --}}
            <div>
                <p class="text-gray-500 text-xs mb-1">Rozpoczeto</p>
                <p class="text-gray-300">{{ $this->startedAtFormatted ?? 'N/A' }}</p>
            </div>

            {{-- Sample Products (if available) --}}
            @if($this->productsSample && count($this->productsSample) > 0)
                <div class="col-span-2 md:col-span-4">
                    <p class="text-gray-500 text-xs mb-1">Przykladowe produkty</p>
                    <div class="flex flex-wrap gap-1">
                        @foreach($this->productsSample as $sku)
                            <span class="px-2 py-0.5 bg-white/10 rounded text-xs text-gray-300 font-mono">
                                {{ $sku }}
                            </span>
                        @endforeach
                        @if($this->totalCount > count($this->productsSample))
                            <span class="px-2 py-0.5 text-xs text-gray-500">
                                +{{ $this->totalCount - count($this->productsSample) }} wiecej
                            </span>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Metadata Details (phase, mode, etc.) --}}
            @if($this->metadataDetails && count($this->metadataDetails) > 0)
                <div class="col-span-2 md:col-span-4 border-t border-white/5 pt-3 mt-2">
                    <p class="text-gray-500 text-xs mb-2">Szczegoly</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        @foreach($this->metadataDetails as $key => $value)
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500 text-xs">{{ $key }}:</span>
                                <span class="text-gray-300 text-xs">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
