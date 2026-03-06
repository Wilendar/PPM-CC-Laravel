<div class="dashboard-widget" role="region" aria-label="Powitanie">
    <div class="flex items-start gap-4">
        {{-- Avatar icon --}}
        <div class="dashboard-widget__icon dashboard-widget__icon--lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
        </div>

        {{-- User info --}}
        <div class="flex-1 min-w-0">
            <p class="text-sm text-gray-400 mb-1">{{ $greeting }}</p>
            <h2 class="text-xl font-bold text-white truncate">{{ $userName }}</h2>
            <p class="text-sm text-gray-400 mt-0.5">{{ $userEmail }}</p>
        </div>

        {{-- Role badge --}}
        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-500/15 text-amber-400 border border-amber-500/20 whitespace-nowrap">
            {{ $userRole }}
        </span>

        {{-- Real-time clock (Alpine.js) --}}
        <div class="welcome-clock"
             x-data="{
                 time: '', day: '', date: '',
                 tick() {
                     const now = new Date();
                     this.time = now.toLocaleTimeString('pl-PL', { hour: '2-digit', minute: '2-digit' });
                     const days = ['Niedziela','Poniedzialek','Wtorek','Sroda','Czwartek','Piatek','Sobota'];
                     this.day = days[now.getDay()];
                     const dd = String(now.getDate()).padStart(2, '0');
                     const mm = String(now.getMonth() + 1).padStart(2, '0');
                     this.date = dd + '.' + mm + '.' + now.getFullYear();
                 }
             }"
             x-init="tick(); setInterval(() => tick(), 1000)">
            <span class="welcome-clock__time" x-text="time"></span>
            <div class="welcome-clock__daydate">
                <span class="welcome-clock__day" x-text="day"></span>
                <span class="welcome-clock__date" x-text="date"></span>
            </div>
        </div>
    </div>

    {{-- Session info row --}}
    <div class="grid grid-cols-2 gap-4 mt-5 pt-4 border-t border-gray-700/50">
        {{-- Last login --}}
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Ostatnie logowanie</p>
                <p class="text-sm text-gray-300 truncate">{{ $lastLoginAt ?? 'Brak danych' }}</p>
                @if($lastLoginIp)
                    <p class="text-xs text-gray-500 truncate">IP: {{ $lastLoginIp }}</p>
                @endif
            </div>
        </div>

        {{-- Session duration today --}}
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Sesja dzis</p>
                <p class="text-sm text-gray-300">{{ $formattedDuration }}</p>
            </div>
        </div>
    </div>
</div>
