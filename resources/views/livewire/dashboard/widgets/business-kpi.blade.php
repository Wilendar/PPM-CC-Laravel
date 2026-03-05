<div class="dashboard-widget">
    {{-- Header --}}
    <div class="dashboard-widget__header">
        <span class="dashboard-widget__title">Wskazniki KPI</span>
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
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
        </div>
    </div>

    {{-- Products time range --}}
    <p class="text-xs text-gray-500 uppercase tracking-wide mb-3">Nowe produkty</p>
    <div class="grid grid-cols-4 gap-3 mb-5">
        <div class="kpi-metric">
            <p class="kpi-metric__value text-amber-400">{{ $metrics['products_today'] ?? 0 }}</p>
            <p class="kpi-metric__label">Dzis</p>
        </div>
        <div class="kpi-metric">
            <p class="kpi-metric__value">{{ $metrics['products_week'] ?? 0 }}</p>
            <p class="kpi-metric__label">Tydzien</p>
        </div>
        <div class="kpi-metric">
            <p class="kpi-metric__value">{{ $metrics['products_month'] ?? 0 }}</p>
            <p class="kpi-metric__label">Miesiac</p>
        </div>
        <div class="kpi-metric">
            <p class="kpi-metric__value">{{ $metrics['products_year'] ?? 0 }}</p>
            <p class="kpi-metric__label">Rok</p>
        </div>
    </div>

    {{-- Totals --}}
    <p class="text-xs text-gray-500 uppercase tracking-wide mb-3">Podsumowanie</p>
    <div class="grid grid-cols-3 gap-3">
        <div class="kpi-metric">
            <div class="flex items-center justify-center gap-1.5 mb-1">
                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
            <p class="kpi-metric__value text-blue-400">{{ number_format($metrics['total_products'] ?? 0) }}</p>
            <p class="kpi-metric__label">Produkty</p>
        </div>
        <div class="kpi-metric">
            <div class="flex items-center justify-center gap-1.5 mb-1">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
            </div>
            <p class="kpi-metric__value text-emerald-400">{{ number_format($metrics['total_categories'] ?? 0) }}</p>
            <p class="kpi-metric__label">Kategorie</p>
        </div>
        <div class="kpi-metric">
            <div class="flex items-center justify-center gap-1.5 mb-1">
                <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <p class="kpi-metric__value text-purple-400">{{ $metrics['active_users'] ?? 0 }}</p>
            <p class="kpi-metric__label">Aktywni</p>
        </div>
    </div>
</div>
