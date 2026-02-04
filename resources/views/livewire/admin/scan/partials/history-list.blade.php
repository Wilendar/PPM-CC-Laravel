{{-- Scan History List --}}
<div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-700 bg-gray-700/50">
        <h3 class="text-sm font-medium text-white">Historia Skanow</h3>
    </div>

    @if($history->isEmpty())
        <div class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="text-sm font-medium text-white mb-2">Brak historii skanow</h3>
            <p class="text-xs text-gray-400">Uruchom pierwszy skan aby rozpoczac</p>
        </div>
    @else
        <div class="divide-y divide-gray-700">
            @foreach($history as $session)
                <div wire:key="history-{{ $session->id }}"
                     class="p-4 hover:bg-gray-700/30 transition-colors duration-150">
                    <div class="flex items-start justify-between">
                        {{-- Left side: Scan info --}}
                        <div class="flex items-start gap-3">
                            {{-- Status icon --}}
                            @switch($session->status)
                                @case('completed')
                                    <div class="w-10 h-10 flex items-center justify-center rounded-lg bg-green-600/20">
                                        <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    @break
                                @case('failed')
                                    <div class="w-10 h-10 flex items-center justify-center rounded-lg bg-red-600/20">
                                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </div>
                                    @break
                                @case('cancelled')
                                    <div class="w-10 h-10 flex items-center justify-center rounded-lg bg-gray-600/20">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
                                        </svg>
                                    </div>
                                    @break
                                @case('running')
                                    <div class="w-10 h-10 flex items-center justify-center rounded-lg bg-blue-600/20">
                                        <svg class="w-5 h-5 text-blue-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                    </div>
                                    @break
                                @default
                                    <div class="w-10 h-10 flex items-center justify-center rounded-lg bg-yellow-600/20">
                                        <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                            @endswitch

                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-white">{{ $session->getScanTypeLabel() }}</span>
                                    <span class="px-2 py-0.5 text-xs rounded
                                        {{ $session->getStatusBadgeClass() === 'badge-success' ? 'bg-green-600/20 text-green-400 border border-green-500/30' : '' }}
                                        {{ $session->getStatusBadgeClass() === 'badge-danger' ? 'bg-red-600/20 text-red-400 border border-red-500/30' : '' }}
                                        {{ $session->getStatusBadgeClass() === 'badge-warning' ? 'bg-yellow-600/20 text-yellow-400 border border-yellow-500/30' : '' }}
                                        {{ $session->getStatusBadgeClass() === 'badge-info' ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : '' }}
                                        {{ $session->getStatusBadgeClass() === 'badge-secondary' ? 'bg-gray-600/20 text-gray-400 border border-gray-500/30' : '' }}">
                                        {{ $session->getStatusLabel() }}
                                    </span>
                                </div>

                                <div class="text-xs text-gray-400 mt-1">
                                    {{ $session->getSourceTypeLabel() }}
                                    @if($session->source_id)
                                        - {{ $this->getSourceName($session->source_type, $session->source_id) }}
                                    @endif
                                </div>

                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $session->created_at->format('d.m.Y H:i') }}
                                    @if($session->user)
                                        przez {{ $session->user->name }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Right side: Statistics --}}
                        <div class="flex items-center gap-4 text-xs">
                            <div class="text-center">
                                <div class="font-semibold text-white">{{ number_format($session->total_scanned) }}</div>
                                <div class="text-gray-500">Skanowanych</div>
                            </div>
                            <div class="text-center">
                                <div class="font-semibold text-green-400">{{ number_format($session->matched_count) }}</div>
                                <div class="text-gray-500">Dopasowanych</div>
                            </div>
                            <div class="text-center">
                                <div class="font-semibold text-yellow-400">{{ number_format($session->unmatched_count) }}</div>
                                <div class="text-gray-500">Niedopasowanych</div>
                            </div>
                            @if($session->errors_count > 0)
                                <div class="text-center">
                                    <div class="font-semibold text-red-400">{{ number_format($session->errors_count) }}</div>
                                    <div class="text-gray-500">Bledow</div>
                                </div>
                            @endif
                            <div class="text-center">
                                <div class="font-medium text-gray-300">{{ $session->getDurationForHumans() }}</div>
                                <div class="text-gray-500">Czas trwania</div>
                            </div>
                        </div>
                    </div>

                    {{-- Error message if failed --}}
                    @if($session->isFailed() && $session->error_message)
                        <div class="mt-3 p-2 bg-red-900/20 border border-red-800 rounded text-xs text-red-400">
                            {{ Str::limit($session->error_message, 200) }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
