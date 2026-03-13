<div class="space-y-6">

    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 px-5 py-4 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-500/20 border border-red-500/30 text-red-400 px-5 py-4 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if (session()->has('info'))
        <div class="bg-amber-500/20 border border-amber-500/30 text-amber-400 px-5 py-4 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('info') }}</span>
        </div>
    @endif

    {{-- Header --}}
    <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-[#e0ac7e]/20 rounded-xl">
                    <svg class="w-6 h-6 text-[#e0ac7e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-semibold text-white">Aktywne sesje</h1>
                    <p class="text-sm text-gray-400 mt-1">
                        Zarzadzaj sesjami na swoich urzadzeniach
                    </p>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-[#e0ac7e]/20 text-[#e0ac7e] border border-[#e0ac7e]/30">
                    {{ $this->activeSessionCount }} aktywnych
                </span>
            </div>

            {{-- Terminate All Other Sessions --}}
            <div x-data="{ confirmAll: false }">
                <button
                    x-show="!confirmAll"
                    x-on:click="confirmAll = true"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-500/20 text-red-400 border border-red-500/30 rounded-lg font-semibold text-sm hover:bg-red-500/30 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Wyloguj ze wszystkich innych urzadzen
                </button>

                <div x-show="confirmAll" x-cloak class="flex items-center gap-3">
                    <span class="text-sm text-red-400">Na pewno?</span>
                    <button
                        wire:click="terminateAllOtherSessions"
                        x-on:click="confirmAll = false"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 text-white rounded-lg font-semibold text-sm hover:bg-red-700 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Tak, zakoncz
                    </button>
                    <button
                        x-on:click="confirmAll = false"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-700 text-gray-300 rounded-lg font-semibold text-sm hover:bg-gray-600 transition-colors"
                    >
                        Anuluj
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Sessions List --}}
    <div class="space-y-3">
        @forelse ($this->sessions as $session)
            <div
                wire:key="session-{{ $session->id }}"
                class="bg-gray-800/50 border rounded-xl p-5 transition-colors
                    {{ $session->isCurrentSession() ? 'border-[#e0ac7e]/50 bg-[#e0ac7e]/5' : 'border-gray-700 hover:border-gray-600' }}"
            >
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    {{-- Device Icon --}}
                    <div class="flex-shrink-0 p-3 bg-gray-700/50 rounded-xl">
                        <svg class="w-6 h-6 {{ $session->isCurrentSession() ? 'text-[#e0ac7e]' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $session->getDeviceIconPath() }}"/>
                        </svg>
                    </div>

                    {{-- Session Details --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1.5">
                            {{-- Device Info --}}
                            <span class="text-white font-medium text-sm">
                                {{ $session->getDeviceInfo() }}
                            </span>

                            {{-- Current Session Badge --}}
                            @if ($session->isCurrentSession())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-[#e0ac7e]/20 text-[#e0ac7e] border border-[#e0ac7e]/30">
                                    Ta sesja
                                </span>
                            @endif

                            {{-- Status Badge --}}
                            @php $badge = $session->getStatusBadge(); @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $this->getStatusColorClasses($badge['color']) }}">
                                {{ $badge['label'] }}
                            </span>

                            {{-- Suspicious Badge --}}
                            @if ($session->is_suspicious)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-500/20 text-red-400 border border-red-500/30">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                    </svg>
                                    Podejrzana
                                </span>
                            @endif
                        </div>

                        {{-- Meta Info --}}
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-400">
                            {{-- IP Address --}}
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                                </svg>
                                {{ $session->ip_address }}
                            </span>

                            {{-- Location --}}
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ $session->getLocationInfo() }}
                            </span>

                            {{-- Last Activity --}}
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ $session->last_activity ? $session->last_activity->diffForHumans() : 'Brak danych' }}
                            </span>

                            {{-- End Reason (for terminated sessions) --}}
                            @if ($session->end_reason)
                                <span class="inline-flex items-center gap-1.5 text-gray-500">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ $session->end_reason }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Terminate Button --}}
                    @if ($session->is_active && !$session->isCurrentSession())
                        <div x-data="{ confirm: false }" class="flex-shrink-0">
                            <button
                                x-show="!confirm"
                                x-on:click="confirm = true"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gray-700/50 text-gray-300 border border-gray-600 rounded-lg text-sm font-medium hover:bg-red-500/20 hover:text-red-400 hover:border-red-500/30 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Zakoncz
                            </button>

                            <div x-show="confirm" x-cloak class="flex items-center gap-2">
                                <button
                                    wire:click="terminateSession({{ $session->id }})"
                                    x-on:click="confirm = false"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-700 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Tak
                                </button>
                                <button
                                    x-on:click="confirm = false"
                                    class="inline-flex items-center px-3.5 py-2 bg-gray-700 text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-600 transition-colors"
                                >
                                    Nie
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-12 text-center">
                <svg class="w-12 h-12 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <p class="text-gray-400 text-lg">Brak zarejestrowanych sesji</p>
                <p class="text-gray-500 text-sm mt-1">Historia sesji pojawi sie po pierwszym logowaniu.</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($this->sessions->hasPages())
        <div class="mt-6">
            {{ $this->sessions->links('components.pagination-compact') }}
        </div>
    @endif

    {{-- Info Note --}}
    <div class="bg-gray-800/30 border border-gray-700/50 rounded-xl p-5">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-gray-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm text-gray-500 leading-relaxed">
                <p>Sesja oznaczona jako <strong class="text-[#e0ac7e]">Ta sesja</strong> to urzadzenie, z ktorego aktualnie korzystasz.</p>
                <p class="mt-1">Zakonczenie sesji na innym urzadzeniu spowoduje natychmiastowe wylogowanie na tamtym urzadzeniu.</p>
            </div>
        </div>
    </div>

</div>
