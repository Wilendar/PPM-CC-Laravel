<div class="space-y-6">
    {{-- ================================================ --}}
    {{-- HEADER --}}
    {{-- ================================================ --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Historia aktywnosci</h1>
            <p class="mt-1 text-sm text-gray-400">Przegladaj swoje ostatnie operacje w systemie</p>
        </div>
        <a href="{{ route('profile.edit') }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-300 bg-gray-800 border border-gray-600 rounded-lg hover:bg-gray-700 hover:text-white transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Powrot do profilu
        </a>
    </div>

    {{-- ================================================ --}}
    {{-- STATS CARDS --}}
    {{-- ================================================ --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- Today --}}
        <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-5">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg bg-emerald-500/20">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Dzisiaj</p>
                    <p class="text-2xl font-bold text-white">{{ $this->stats['today'] }}</p>
                </div>
            </div>
        </div>

        {{-- This week --}}
        <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-5">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg bg-blue-500/20">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Ten tydzien</p>
                    <p class="text-2xl font-bold text-white">{{ $this->stats['week'] }}</p>
                </div>
            </div>
        </div>

        {{-- Total --}}
        <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-5">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg bg-purple-500/20">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Lacznie</p>
                    <p class="text-2xl font-bold text-white">{{ number_format($this->stats['total']) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ================================================ --}}
    {{-- FILTERS --}}
    {{-- ================================================ --}}
    <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-5">
        <div class="flex flex-col lg:flex-row lg:items-end gap-4">
            {{-- Event type filter --}}
            <div class="flex-1 min-w-0">
                <label for="eventFilter" class="block text-sm font-medium text-gray-300 mb-1.5">Typ zdarzenia</label>
                <select id="eventFilter"
                        wire:model.live="eventFilter"
                        class="w-full bg-gray-700 border border-gray-600 text-white rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500/50 focus:border-orange-500">
                    <option value="">Wszystkie zdarzenia</option>
                    @foreach($this->availableEvents as $event)
                        <option value="{{ $event }}">{{ $this->getEventLabel($event) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date from --}}
            <div class="flex-1 min-w-0">
                <label for="dateFrom" class="block text-sm font-medium text-gray-300 mb-1.5">Od daty</label>
                <input id="dateFrom"
                       type="date"
                       wire:model.live="dateFrom"
                       class="w-full bg-gray-700 border border-gray-600 text-white rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500/50 focus:border-orange-500">
            </div>

            {{-- Date to --}}
            <div class="flex-1 min-w-0">
                <label for="dateTo" class="block text-sm font-medium text-gray-300 mb-1.5">Do daty</label>
                <input id="dateTo"
                       type="date"
                       wire:model.live="dateTo"
                       class="w-full bg-gray-700 border border-gray-600 text-white rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500/50 focus:border-orange-500">
            </div>

            {{-- Quick filters --}}
            <div class="flex items-end gap-2">
                <button wire:click="setQuickFilter('today')"
                        class="px-3 py-2 text-sm font-medium text-gray-300 bg-gray-700 border border-gray-600 rounded-lg hover:bg-gray-600 hover:text-white transition-colors whitespace-nowrap">
                    Dzis
                </button>
                <button wire:click="setQuickFilter('week')"
                        class="px-3 py-2 text-sm font-medium text-gray-300 bg-gray-700 border border-gray-600 rounded-lg hover:bg-gray-600 hover:text-white transition-colors whitespace-nowrap">
                    Tydzien
                </button>
                <button wire:click="setQuickFilter('month')"
                        class="px-3 py-2 text-sm font-medium text-gray-300 bg-gray-700 border border-gray-600 rounded-lg hover:bg-gray-600 hover:text-white transition-colors whitespace-nowrap">
                    Miesiac
                </button>
                <button wire:click="clearFilters"
                        class="px-3 py-2 text-sm font-medium text-orange-400 bg-gray-700 border border-orange-500/40 rounded-lg hover:bg-orange-500/10 hover:text-orange-300 transition-colors whitespace-nowrap">
                    Wyczysc filtry
                </button>
            </div>
        </div>
    </div>

    {{-- ================================================ --}}
    {{-- ACTIVITY LIST --}}
    {{-- ================================================ --}}
    <div class="bg-gray-800/50 border border-gray-700 rounded-xl overflow-hidden">
        @if($this->logs->isEmpty())
            <div class="p-12 text-center">
                <svg class="w-12 h-12 text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-gray-400 text-sm">Brak zdarzen w wybranym zakresie</p>
                <button wire:click="clearFilters" class="mt-3 text-sm text-orange-400 hover:text-orange-300 transition-colors">
                    Wyczysc filtry i pokaz wszystko
                </button>
            </div>
        @else
            <div class="divide-y divide-gray-700/50">
                @foreach($this->logs as $log)
                    <div wire:key="log-{{ $log->id }}"
                         x-data="{ expanded: false }"
                         class="p-4 hover:bg-gray-700/30 transition-colors">
                        <div class="flex items-start gap-4">
                            {{-- Event icon --}}
                            <div class="flex-shrink-0 mt-0.5">
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center {{ $this->getEventIconClasses($log->event) }}">
                                    @switch($log->event)
                                        @case('created')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            @break
                                        @case('updated')
                                        @case('bulk_update')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            @break
                                        @case('deleted')
                                        @case('bulk_delete')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            @break
                                        @case('restored')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                            </svg>
                                            @break
                                        @case('login')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                            </svg>
                                            @break
                                        @case('login_failed')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            @break
                                        @case('logout')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                            </svg>
                                            @break
                                        @case('bulk_export')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            @break
                                        @case('synced')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            @break
                                        @case('imported')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                            </svg>
                                            @break
                                        @case('exported')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            @break
                                        @case('matched')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                            </svg>
                                            @break
                                        @default
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                    @endswitch
                                </div>
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-semibold text-white">
                                        {{ $log->event_display }}
                                    </span>
                                    <span class="text-sm text-gray-300">
                                        {{ $this->getModelLabel($log->auditable_type) }}
                                    </span>
                                    @if($log->auditable_id)
                                        <span class="text-xs text-gray-500">(ID: {{ $log->auditable_id }})</span>
                                    @endif
                                    @if($log->source && $log->source !== 'web')
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-700 text-gray-400">
                                            {{ $log->source_display }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Change summary line --}}
                                @php $changeSummary = $this->getChangeSummary($log); @endphp
                                @if($changeSummary)
                                    <div class="mt-0.5 text-xs text-gray-400">
                                        <span class="font-mono">{{ $changeSummary }}</span>
                                    </div>
                                @endif

                                <div class="flex items-center gap-3 mt-1 text-xs text-gray-500">
                                    <span title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
                                        {{ $log->created_at->diffForHumans() }}
                                    </span>
                                    @if($log->ip_address)
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                                            </svg>
                                            {{ $log->ip_address }}
                                        </span>
                                    @endif
                                    @if($log->comment)
                                        <span class="text-gray-400 italic truncate max-w-xs" title="{{ $log->comment }}">
                                            {{ $log->comment }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Expandable changes for events with data --}}
                                @if(in_array($log->event, ['created', 'updated', 'deleted', 'bulk_update', 'imported', 'synced']) && ($log->old_values || $log->new_values))
                                    <button @click="expanded = !expanded"
                                            class="mt-2 text-xs text-orange-400 hover:text-orange-300 transition-colors flex items-center gap-1">
                                        <svg class="w-3 h-3 transition-transform" :class="expanded && 'rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                        <span x-text="expanded ? 'Ukryj szczegoly' : 'Pokaz zmiany'"></span>
                                    </button>

                                    <div x-show="expanded"
                                         x-transition:enter="transition ease-out duration-200"
                                         x-transition:enter-start="opacity-0 -translate-y-1"
                                         x-transition:enter-end="opacity-100 translate-y-0"
                                         x-cloak
                                         class="mt-3">
                                        @php $changes = $log->getChanges(); @endphp
                                        @if(!empty($changes))
                                            <div class="bg-gray-900/50 border border-gray-700 rounded-lg overflow-hidden">
                                                <table class="w-full text-xs">
                                                    <thead>
                                                        <tr class="border-b border-gray-700">
                                                            <th class="px-3 py-2 text-left text-gray-400 font-medium">Pole</th>
                                                            <th class="px-3 py-2 text-left text-gray-400 font-medium">Poprzednia wartosc</th>
                                                            <th class="px-3 py-2 text-left text-gray-400 font-medium">Nowa wartosc</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-700/50">
                                                        @foreach($changes as $field => $change)
                                                            <tr>
                                                                <td class="px-3 py-1.5 text-gray-300 font-mono">{{ $field }}</td>
                                                                <td class="px-3 py-1.5 text-red-400/80 max-w-xs truncate" title="{{ is_array($change['old']) ? json_encode($change['old']) : $change['old'] }}">
                                                                    {{ is_null($change['old']) ? '—' : (is_array($change['old']) ? json_encode($change['old']) : \Illuminate\Support\Str::limit((string)$change['old'], 60)) }}
                                                                </td>
                                                                <td class="px-3 py-1.5 text-emerald-400/80 max-w-xs truncate" title="{{ is_array($change['new']) ? json_encode($change['new']) : $change['new'] }}">
                                                                    {{ is_null($change['new']) ? '—' : (is_array($change['new']) ? json_encode($change['new']) : \Illuminate\Support\Str::limit((string)$change['new'], 60)) }}
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <p class="text-xs text-gray-500 italic">Brak szczegolowych danych o zmianach</p>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            {{-- Timestamp (right side) --}}
                            <div class="flex-shrink-0 text-right hidden sm:block">
                                <p class="text-xs text-gray-500">{{ $log->created_at->format('d.m.Y') }}</p>
                                <p class="text-xs text-gray-600">{{ $log->created_at->format('H:i:s') }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="px-4 py-3 border-t border-gray-700">
                {{ $this->logs->links() }}
            </div>
        @endif
    </div>

    {{-- ================================================ --}}
    {{-- ADMIN LINK --}}
    {{-- ================================================ --}}
    @can('audit.read')
        <div class="text-center">
            <a href="{{ url('/admin/activity-log') }}"
               class="inline-flex items-center gap-2 text-sm text-gray-400 hover:text-orange-400 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Zobacz pelny log aktywnosci (admin)
            </a>
        </div>
    @endcan
</div>
