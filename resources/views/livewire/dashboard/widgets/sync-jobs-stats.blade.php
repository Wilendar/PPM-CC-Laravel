<div class="dashboard-widget" wire:poll.30s.visible="loadMetrics" role="region" aria-label="Synchronizacje">
    {{-- Header --}}
    <div class="dashboard-widget__header">
        <span class="dashboard-widget__title">Synchronizacje</span>
        <div class="flex items-center gap-2">
            <button wire:click="refreshMetrics"
                    wire:loading.class="animate-spin"
                    wire:target="refreshMetrics"
                    class="text-gray-500 hover:text-amber-400 transition-colors"
                    title="Odswiez dane">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
            <div class="dashboard-widget__icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </div>
        </div>
    </div>

    {{-- Top row: Running, Pending, Failed, Success Rate --}}
    <div class="grid grid-cols-4 gap-3 mb-5">
        <div class="kpi-metric">
            <p class="kpi-metric__value text-blue-400">{{ $metrics['running_jobs'] ?? 0 }}</p>
            <p class="kpi-metric__label">Aktywne</p>
        </div>
        <div class="kpi-metric">
            <p class="kpi-metric__value text-amber-400">{{ $metrics['pending_jobs'] ?? 0 }}</p>
            <p class="kpi-metric__label">Oczekujace</p>
        </div>
        <div class="kpi-metric">
            <p class="kpi-metric__value text-red-400">{{ $metrics['failed_jobs'] ?? 0 }}</p>
            <p class="kpi-metric__label">Nieudane (7d)</p>
        </div>
        <div class="kpi-metric">
            @php $rate = $metrics['success_rate'] ?? 100; @endphp
            <p class="kpi-metric__value {{ $rate >= 95 ? 'text-emerald-400' : ($rate >= 80 ? 'text-amber-400' : 'text-red-400') }}">
                {{ $rate }}%
            </p>
            <p class="kpi-metric__label">Skutecznosc</p>
        </div>
    </div>

    {{-- Bottom row: Completed today/week/month, Avg time --}}
    <p class="text-xs text-gray-500 uppercase tracking-wide mb-3">Ukonczone</p>
    <div class="grid grid-cols-4 gap-3">
        <div class="kpi-metric">
            <p class="kpi-metric__value text-emerald-400">{{ $metrics['completed_today'] ?? 0 }}</p>
            <p class="kpi-metric__label">Dzis</p>
        </div>
        <div class="kpi-metric">
            <p class="kpi-metric__value">{{ $metrics['completed_week'] ?? 0 }}</p>
            <p class="kpi-metric__label">Tydzien</p>
        </div>
        <div class="kpi-metric">
            <p class="kpi-metric__value">{{ $metrics['completed_month'] ?? 0 }}</p>
            <p class="kpi-metric__label">Miesiac</p>
        </div>
        <div class="kpi-metric">
            <div class="flex items-center justify-center gap-1 mb-1">
                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="kpi-metric__value text-sm">{{ $this->formatDuration($metrics['avg_duration'] ?? 0) }}</p>
            <p class="kpi-metric__label">Sredni czas</p>
        </div>
    </div>
</div>
