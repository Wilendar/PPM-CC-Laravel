<div class="dashboard-widget" wire:poll.60s.visible="loadAlerts" role="region" aria-label="Alerty bezpieczenstwa">
    {{-- Header --}}
    <div class="dashboard-widget__header">
        <span class="dashboard-widget__title">Alerty bezpieczenstwa</span>
        <div class="flex items-center gap-2">
            {{-- Severity counters --}}
            @if($highCount > 0)
                <span class="text-xs px-2 py-0.5 rounded-full font-semibold bg-red-500/20 text-red-400">
                    {{ $highCount }} kryt.
                </span>
            @endif
            @if($mediumCount > 0)
                <span class="text-xs px-2 py-0.5 rounded-full font-semibold bg-yellow-500/20 text-yellow-400">
                    {{ $mediumCount }} sred.
                </span>
            @endif
            <div class="dashboard-widget__icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
        </div>
    </div>

    {{-- Alert list --}}
    @if(count($alerts) === 0)
        <div class="flex flex-col items-center justify-center py-6 text-center">
            <svg class="w-10 h-10 text-emerald-500/50 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <p class="text-sm text-gray-400">Brak alertow</p>
            <p class="text-xs text-gray-600 mt-1">System bezpieczny</p>
        </div>
    @else
        <div class="space-y-2">
            @foreach($alerts as $index => $alert)
                <div wire:key="alert-{{ $index }}" class="activity-item">
                    {{-- Severity icon --}}
                    <div class="activity-item__icon {{ $alert['severity'] === 'high' ? 'bg-red-500/20 text-red-400' : 'bg-yellow-500/20 text-yellow-400' }}">
                        @if($alert['type'] === 'multi_session')
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        @elseif($alert['type'] === 'brute_force')
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        @else
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @endif
                    </div>

                    {{-- Alert content --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-300 truncate" title="{{ $alert['message'] }}">
                            {{ $alert['message'] }}
                        </p>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-xs px-1.5 py-0.5 rounded font-semibold
                                {{ $alert['severity'] === 'high' ? 'bg-red-500/20 text-red-400' : 'bg-yellow-500/20 text-yellow-400' }}">
                                {{ $alert['severity'] === 'high' ? 'Krytyczny' : 'Sredni' }}
                            </span>
                            <span class="activity-item__time">{{ $alert['timestamp'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
