<div class="dashboard-widget" role="region" aria-label="Moje zgloszenia bledow">
    {{-- Header --}}
    <div class="dashboard-widget__header">
        <span class="dashboard-widget__title">Moje zgloszenia</span>
        <div class="dashboard-widget__icon">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
        </div>
    </div>

    {{-- Status stats row --}}
    <div class="grid grid-cols-4 gap-2 mb-4">
        <div class="text-center p-2 rounded-lg bg-blue-500/10 border border-blue-500/20">
            <p class="text-lg font-bold text-blue-400">{{ $statusCounts['new'] ?? 0 }}</p>
            <p class="text-xs text-gray-500">Nowe</p>
        </div>
        <div class="text-center p-2 rounded-lg bg-yellow-500/10 border border-yellow-500/20">
            <p class="text-lg font-bold text-yellow-400">{{ $statusCounts['in_progress'] ?? 0 }}</p>
            <p class="text-xs text-gray-500">W toku</p>
        </div>
        <div class="text-center p-2 rounded-lg bg-emerald-500/10 border border-emerald-500/20">
            <p class="text-lg font-bold text-emerald-400">{{ $statusCounts['resolved'] ?? 0 }}</p>
            <p class="text-xs text-gray-500">Rozwiazane</p>
        </div>
        <div class="text-center p-2 rounded-lg bg-gray-500/10 border border-gray-500/20">
            <p class="text-lg font-bold text-gray-400">{{ $statusCounts['closed'] ?? 0 }}</p>
            <p class="text-xs text-gray-500">Zamkniete</p>
        </div>
    </div>

    {{-- Reports list --}}
    <div class="space-y-0">
        @forelse($reports as $report)
            <div wire:key="bug-{{ $report->id }}" class="activity-item">
                {{-- Severity icon --}}
                <div class="activity-item__icon
                    @switch($report->severity)
                        @case('critical') bg-red-500/15 @break
                        @case('high') bg-orange-500/15 @break
                        @case('medium') bg-yellow-500/15 @break
                        @default bg-gray-500/15
                    @endswitch
                ">
                    <i class="{{ $report->severity_icon }}
                        @switch($report->severity)
                            @case('critical') text-red-400 @break
                            @case('high') text-orange-400 @break
                            @case('medium') text-yellow-400 @break
                            @default text-gray-400
                        @endswitch
                    "></i>
                </div>

                {{-- Report info --}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-300 truncate">{{ $report->title }}</p>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="bug-status bug-status--{{ $report->status }}">
                            {{ $report->status_label }}
                        </span>
                        <span class="text-xs text-gray-500">{{ $report->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-6">
                <svg class="w-8 h-8 mx-auto text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm text-gray-500">Brak zgloszen</p>
            </div>
        @endforelse
    </div>

    {{-- Footer link --}}
    @if($totalCount > 0)
        <div class="mt-4 pt-3 border-t border-gray-700/50">
            <a href="{{ url('/profile/bug-reports') }}"
               class="flex items-center justify-between text-sm text-gray-400 hover:text-amber-400 transition-colors">
                <span>Wszystkie zgloszenia ({{ $totalCount }})</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    @endif
</div>
