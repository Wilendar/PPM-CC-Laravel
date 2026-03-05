<div class="dashboard-widget" role="region" aria-label="Uzytkownicy">
    {{-- Header --}}
    <div class="dashboard-widget__header">
        <span class="dashboard-widget__title">Uzytkownicy</span>
        <div class="flex items-center gap-2">
            <button wire:click="refreshStats"
                    wire:loading.class="animate-spin"
                    wire:target="refreshStats"
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
                          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
        </div>
    </div>

    {{-- KPI metrics row --}}
    <div class="grid grid-cols-3 gap-3 mb-5">
        <div class="kpi-metric">
            <p class="kpi-metric__value text-blue-400">{{ $metrics['total_users'] ?? 0 }}</p>
            <p class="kpi-metric__label">Wszyscy</p>
        </div>
        <div class="kpi-metric">
            <p class="kpi-metric__value text-emerald-400">{{ $metrics['active_users'] ?? 0 }}</p>
            <p class="kpi-metric__label">Aktywni</p>
        </div>
        <div class="kpi-metric">
            <div class="flex items-center justify-center gap-1 mb-1">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            </div>
            <p class="kpi-metric__value text-amber-400">{{ $metrics['online_now'] ?? 0 }}</p>
            <p class="kpi-metric__label">Online</p>
        </div>
    </div>

    {{-- New registrations --}}
    <div class="flex items-center gap-4 mb-5 px-1">
        <div class="flex items-center gap-2 text-sm">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            <span class="text-gray-400">Nowi:</span>
            <span class="text-white font-semibold">{{ $metrics['new_today'] ?? 0 }}</span>
            <span class="text-gray-500">dzis</span>
            <span class="text-gray-600">/</span>
            <span class="text-white font-semibold">{{ $metrics['new_this_week'] ?? 0 }}</span>
            <span class="text-gray-500">tydzien</span>
        </div>
    </div>

    {{-- Role breakdown --}}
    @if(!empty($roleBreakdown))
        <p class="text-xs text-gray-500 uppercase tracking-wide mb-3">Role</p>
        <div class="space-y-2">
            @foreach($roleBreakdown as $role)
                <div wire:key="role-{{ $role['name'] }}" class="flex items-center justify-between px-1">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 role-dot--{{ strtolower($role['name']) }}"></span>
                        <span class="text-sm text-gray-300">{{ $role['name'] }}</span>
                    </div>
                    <span class="text-sm font-semibold text-white">{{ $role['count'] }}</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
