{{-- Admin Export Manager --}}
<div class="min-h-screen py-6 px-4 sm:px-6 lg:px-8"
     x-data="{
         copyToClipboard(url) {
             navigator.clipboard.writeText(url).then(() => {
                 $dispatch('success', { message: 'URL skopiowany do schowka' });
             }).catch(() => {
                 $dispatch('error', { message: 'Nie udalo sie skopiowac URL' });
             });
         }
     }"
     @copy-to-clipboard.window="copyToClipboard($event.detail.url)">

    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <div class="icon-chip">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Eksport & Feedy</h1>
                    <p class="text-gray-400">Zarzadzanie profilami eksportu produktow</p>
                </div>
            </div>
            @can('export.create')
                <a href="{{ route('admin.export.create') }}" wire:navigate
                   class="btn-enterprise-primary flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Nowy profil
                </a>
            @endcan
        </div>

        {{-- Tabs --}}
        <div class="mb-6 flex border-b border-gray-700">
            <button wire:click="switchTab('profiles')"
                    class="px-4 py-2 text-sm font-medium {{ $activeTab === 'profiles' ? 'border-b-2 border-[#e0ac7e] text-[#e0ac7e]' : 'text-gray-400 hover:text-white' }}">
                Profile eksportu
            </button>
            @can('export.view_logs')
            <button wire:click="switchTab('logs')"
                    class="px-4 py-2 text-sm font-medium {{ $activeTab === 'logs' ? 'border-b-2 border-[#e0ac7e] text-[#e0ac7e]' : 'text-gray-400 hover:text-white' }}">
                Logi
            </button>
            @endcan
        </div>
    </div>

    @if($activeTab === 'profiles')
    {{-- ===== PROFILES TAB ===== --}}
    <div>
        {{-- Statistics Cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
            <div class="enterprise-card p-3 text-center">
                <p class="text-2xl font-bold text-white">{{ $this->stats['total'] }}</p>
                <p class="text-xs text-gray-400">Profile</p>
            </div>
            <div class="enterprise-card p-3 text-center">
                <p class="text-2xl font-bold text-green-400">{{ $this->stats['active'] }}</p>
                <p class="text-xs text-gray-400">Aktywne</p>
            </div>
            <div class="enterprise-card p-3 text-center">
                <p class="text-2xl font-bold text-blue-400">{{ $this->stats['public'] ?? 0 }}</p>
                <p class="text-xs text-gray-400">Publiczne</p>
            </div>
            <div class="enterprise-card p-3 text-center">
                <p class="text-2xl font-bold text-[#e0ac7e]">{{ $this->stats['total_products'] ?? 0 }}</p>
                <p class="text-xs text-gray-400">Produktow</p>
            </div>
            <div class="enterprise-card p-3 text-center">
                <p class="text-2xl font-bold text-purple-400">{{ $this->stats['total_size'] ?? '0 B' }}</p>
                <p class="text-xs text-gray-400">Rozmiar plikow</p>
            </div>
            <div class="enterprise-card p-3 text-center">
                <p class="text-2xl font-bold text-cyan-400">{{ $this->stats['downloads'] }}</p>
                <p class="text-xs text-gray-400">Pobrania</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="enterprise-card p-4 mb-6">
            <div class="flex flex-wrap gap-4 items-end">
                {{-- Search --}}
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs text-gray-400 mb-1">Szukaj</label>
                    <div class="relative">
                        <input type="text"
                               wire:model.live.debounce.300ms="search"
                               class="form-input-enterprise w-full pl-10"
                               placeholder="Nazwa, slug lub format...">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>

                {{-- Format Filter --}}
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Format</label>
                    <select wire:model.live="filterFormat" class="form-input-enterprise">
                        <option value="">Wszystkie formaty</option>
                        @foreach($formatLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Schedule Filter --}}
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Harmonogram</label>
                    <select wire:model.live="filterSchedule" class="form-input-enterprise">
                        <option value="">Wszystkie harmonogramy</option>
                        @foreach($scheduleLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Clear Filters --}}
                @if($search || $filterFormat || $filterSchedule)
                    <button wire:click="clearFilters" class="text-sm text-gray-400 hover:text-white transition-colors">
                        Wyczysc filtry
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Profile List --}}
    <div class="space-y-3">
        @forelse($this->profiles as $profile)
            <div wire:key="profile-{{ $profile->id }}" class="enterprise-card p-5">
                {{-- Top Row: Name + Status Badges --}}
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <h3 class="text-lg font-semibold text-white truncate">{{ $profile->name }}</h3>
                        <div class="mt-1.5 flex flex-wrap items-center gap-3 text-sm text-gray-400">
                            {{-- Format Badge --}}
                            <span class="inline-flex items-center rounded bg-gray-700 px-2 py-0.5 text-xs font-medium uppercase tracking-wide text-gray-300">
                                {{ $formatLabels[$profile->format] ?? strtoupper($profile->format) }}
                            </span>
                            {{-- Schedule --}}
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $scheduleLabels[$profile->schedule] ?? $profile->schedule }}
                            </span>
                            {{-- Last Generated --}}
                            @if($profile->last_generated_at)
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    {{ $profile->last_generated_at->diffForHumans() }}
                                </span>
                            @endif
                            {{-- Product Count --}}
                            @if($profile->product_count)
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                    {{ number_format($profile->product_count) }} prod.
                                </span>
                            @endif
                            {{-- File Size --}}
                            @if($profile->file_size)
                                <span class="text-xs text-gray-500">
                                    {{ $this->formatFileSize($profile->file_size) }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Status Badges --}}
                    <div class="flex items-center gap-2 flex-shrink-0">
                        @if($profile->is_active)
                            <span class="inline-flex items-center rounded-full bg-green-900/50 px-2.5 py-0.5 text-xs font-medium text-green-400 border border-green-800/50">
                                Aktywny
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-gray-700/50 px-2.5 py-0.5 text-xs font-medium text-gray-500 border border-gray-600/50">
                                Nieaktywny
                            </span>
                        @endif
                        @if($profile->is_public)
                            <span class="inline-flex items-center rounded-full bg-blue-900/50 px-2.5 py-0.5 text-xs font-medium text-blue-400 border border-blue-800/50">
                                Publiczny
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Feed URL (if public) --}}
                @if($profile->is_public && $profile->token)
                    <div class="mt-3 flex items-center gap-2">
                        <code class="flex-1 rounded bg-gray-900/80 px-3 py-1.5 text-xs text-gray-400 truncate font-mono border border-gray-700/50">
                            {{ $profile->getFeedUrl() }}
                        </code>
                        <button wire:click="copyFeedUrl({{ $profile->id }})"
                                class="flex-shrink-0 rounded p-1.5 text-gray-400 hover:text-white hover:bg-gray-700 transition-colors"
                                title="Kopiuj URL">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </button>
                    </div>
                    {{-- Security badges --}}
                    <div class="mt-1.5 flex flex-wrap items-center gap-2">
                        @if($profile->token_expires_at)
                            @if($profile->isTokenExpired())
                                <span class="inline-flex items-center rounded bg-red-900/40 px-2 py-0.5 text-xs text-red-400">
                                    Token wygasl {{ $profile->token_expires_at->diffForHumans() }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded bg-gray-700/50 px-2 py-0.5 text-xs text-gray-400">
                                    Wygasa {{ $profile->token_expires_at->diffForHumans() }}
                                </span>
                            @endif
                        @endif
                        @if($profile->token_rotated_at)
                            <span class="inline-flex items-center rounded bg-gray-700/50 px-2 py-0.5 text-xs text-gray-500" title="Ostatnia rotacja tokena">
                                Rotacja {{ $profile->token_rotated_at->diffForHumans() }}
                            </span>
                        @endif
                        @if(!empty($profile->allowed_ips))
                            <span class="inline-flex items-center rounded bg-blue-900/40 px-2 py-0.5 text-xs text-blue-400" title="{{ implode(', ', $profile->allowed_ips) }}">
                                IP whitelist: {{ count($profile->allowed_ips) }}
                            </span>
                        @endif

                        {{-- Rate Limit Status --}}
                        @php $rlStatus = $this->getRateLimitStatus($profile->id); @endphp
                        @if($rlStatus['blocked'])
                            <span class="inline-flex items-center rounded bg-red-900/40 px-2 py-0.5 text-xs text-red-400">
                                Rate limit: ZABLOKOWANY
                            </span>
                            @can('export.manage_feeds')
                                <button wire:click="clearRateLimit({{ $profile->id }})"
                                        class="text-xs text-yellow-400 transition-colors hover:text-yellow-300">
                                    Odblokuj
                                </button>
                            @endcan
                        @elseif($rlStatus['attempts'] > 0)
                            <span class="inline-flex items-center rounded bg-gray-700/50 px-2 py-0.5 text-xs text-gray-500">
                                {{ $rlStatus['remaining'] }}/30 req
                            </span>
                        @endif

                        {{-- Generation Lock Status --}}
                        @if($this->isGenerationLocked($profile->id))
                            <span class="inline-flex items-center rounded bg-yellow-900/40 px-2 py-0.5 text-xs text-yellow-400">
                                Generacja aktywna
                            </span>
                            @can('export.manage_feeds')
                                <button wire:click="releaseGenerationLock({{ $profile->id }})"
                                        class="text-xs text-yellow-400 transition-colors hover:text-yellow-300">
                                    Zwolnij lock
                                </button>
                            @endcan
                        @endif
                    </div>
                @endif

                {{-- Mini Feed Stats --}}
                @if(isset($feedStats[$profile->id]) && $feedStats[$profile->id]['total_requests'] > 0)
                    @php $pStats = $feedStats[$profile->id]; @endphp
                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                        <span title="Laczna liczba requestow">{{ $pStats['total_requests'] }} req</span>
                        <span title="Unikalne adresy IP">{{ $pStats['unique_ips'] }} IP</span>
                        <span title="Requesty od botow">{{ $pStats['bot_requests'] }} botow</span>
                        <span title="Sredni czas odpowiedzi">avg {{ round($pStats['avg_response_ms'] ?? 0) }}ms</span>
                        @php
                            $totalServed = ($pStats['cache_hits'] ?? 0) + ($pStats['cache_misses'] ?? 0);
                            $cacheRate = $totalServed > 0 ? round(($pStats['cache_hits'] / $totalServed) * 100) : 0;
                        @endphp
                        <span title="Cache hit rate">cache {{ $cacheRate }}%</span>
                    </div>
                @endif

                {{-- Actions --}}
                <div class="mt-3 flex flex-wrap items-center gap-x-1 gap-y-2 border-t border-gray-700/50 pt-3">
                    {{-- Generate Now --}}
                    <button wire:click="generateNow({{ $profile->id }})"
                            wire:loading.attr="disabled"
                            wire:target="generateNow({{ $profile->id }})"
                            class="text-sm font-medium transition-colors text-[#e0ac7e] hover:text-[#c9956a] px-2 py-1 rounded hover:bg-gray-700/50">
                        <span wire:loading.remove wire:target="generateNow({{ $profile->id }})" class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Generuj
                        </span>
                        <span wire:loading wire:target="generateNow({{ $profile->id }})" class="flex items-center gap-1 text-gray-400">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Generowanie...
                        </span>
                    </button>

                    <span class="text-gray-600">|</span>

                    {{-- Download --}}
                    @if($profile->file_path)
                        <a href="{{ $profile->getDownloadUrl() }}"
                           class="text-sm text-gray-400 hover:text-white transition-colors flex items-center gap-1 px-2 py-1 rounded hover:bg-gray-700/50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Pobierz
                        </a>
                        <span class="text-gray-600">|</span>
                    @endif

                    {{-- Edit --}}
                    @can('export.update')
                        <a href="{{ route('admin.export.edit', $profile) }}" wire:navigate
                           class="text-sm text-gray-400 hover:text-white transition-colors flex items-center gap-1 px-2 py-1 rounded hover:bg-gray-700/50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Edytuj
                        </a>
                    @endcan

                    <span class="text-gray-600">|</span>

                    {{-- Toggle Active --}}
                    @can('export.update')
                        <button wire:click="toggleActive({{ $profile->id }})"
                                class="text-sm text-gray-400 hover:text-white transition-colors px-2 py-1 rounded hover:bg-gray-700/50">
                            {{ $profile->is_active ? 'Dezaktywuj' : 'Aktywuj' }}
                        </button>
                    @endcan

                    {{-- Toggle Public --}}
                    @can('export.manage_feeds')
                        <button wire:click="togglePublic({{ $profile->id }})"
                                class="text-sm text-gray-400 hover:text-white transition-colors px-2 py-1 rounded hover:bg-gray-700/50">
                            {{ $profile->is_public ? 'Ukryj feed' : 'Upublicznij' }}
                        </button>
                    @endcan

                    <span class="text-gray-600">|</span>

                    {{-- Regenerate Token --}}
                    @can('export.manage_feeds')
                        @if($profile->is_public)
                            <button wire:click="regenerateToken({{ $profile->id }})"
                                    wire:confirm="Regeneracja tokena spowoduje zmiane URL feeda. Stary URL przestanie dzialac. Kontynuowac?"
                                    class="text-sm text-yellow-400 hover:text-yellow-300 transition-colors px-2 py-1 rounded hover:bg-yellow-900/30"
                                    title="Zmien token (stary URL przestanie dzialac)">
                                Regeneruj token
                            </button>
                            <span class="text-gray-600">|</span>
                        @endif
                    @endcan

                    {{-- Duplicate --}}
                    @can('export.create')
                        <button wire:click="duplicateProfile({{ $profile->id }})"
                                class="text-sm text-gray-400 hover:text-white transition-colors px-2 py-1 rounded hover:bg-gray-700/50">
                            Duplikuj
                        </button>
                    @endcan

                    {{-- Delete --}}
                    @can('export.delete')
                        <span class="text-gray-600">|</span>
                        <button wire:click="confirmDelete({{ $profile->id }})"
                                class="text-sm text-red-400 hover:text-red-300 transition-colors px-2 py-1 rounded hover:bg-red-900/30">
                            Usun
                        </button>
                    @endcan
                </div>
            </div>
        @empty
            <div class="enterprise-card p-8 text-center">
                <svg class="w-12 h-12 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                </svg>
                <p class="text-gray-400 mb-4">Brak profili eksportu. Utworz pierwszy profil aby rozpoczac.</p>
                @can('export.create')
                    <a href="{{ route('admin.export.create') }}" wire:navigate
                       class="btn-enterprise-primary inline-flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Nowy profil
                    </a>
                @endcan
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($this->profiles->hasPages())
        <div class="mt-6">
            {{ $this->profiles->links() }}
        </div>
    @endif

    @endif {{-- END profiles tab --}}

    {{-- ===== LOGS TAB ===== --}}
    @if($activeTab === 'logs')
    @can('export.view_logs')
    <div>
        {{-- Log Filters --}}
        <div class="enterprise-card p-4 mb-6">
            <div class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Profil</label>
                    <select wire:model.live="logFilterProfile" class="form-input-enterprise">
                        <option value="">Wszystkie profile</option>
                        @foreach(\App\Models\ExportProfile::orderBy('name')->pluck('name', 'id') as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Akcja</label>
                    <select wire:model.live="logFilterAction" class="form-input-enterprise">
                        <option value="">Wszystkie akcje</option>
                        <option value="generated">Wygenerowano</option>
                        <option value="downloaded">Pobrano</option>
                        <option value="accessed">Dostep</option>
                        <option value="alert">Alerty</option>
                        <option value="token_rotated">Rotacja tokena</option>
                        <option value="error">Blad</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Od</label>
                    <input type="date" wire:model.live="logFilterDateFrom" class="form-input-enterprise">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Do</label>
                    <input type="date" wire:model.live="logFilterDateTo" class="form-input-enterprise">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Typ</label>
                    <select wire:model.live="logFilterBotType" class="form-input-enterprise">
                        <option value="">Wszystkie</option>
                        <option value="bots">Tylko boty</option>
                        <option value="humans">Tylko ludzie</option>
                    </select>
                </div>
                @if($logFilterProfile || $logFilterAction || $logFilterDateFrom || $logFilterDateTo || $logFilterBotType)
                    <button wire:click="clearLogFilters"
                            class="text-sm text-gray-400 hover:text-white transition-colors">
                        Wyczysc filtry
                    </button>
                @endif
            </div>
        </div>

        {{-- Logs Table --}}
        <div class="overflow-x-auto rounded-lg border border-gray-700">
            <table class="w-full text-sm text-gray-300">
                <thead class="bg-gray-800 text-xs uppercase text-gray-400">
                    <tr>
                        <th class="px-4 py-3 text-left">Data</th>
                        <th class="px-4 py-3 text-left">Profil</th>
                        <th class="px-4 py-3 text-left">Akcja</th>
                        <th class="px-4 py-3 text-left">Uzytkownik / IP</th>
                        <th class="px-4 py-3 text-center">Bot</th>
                        <th class="px-4 py-3 text-center">Zrodlo</th>
                        <th class="px-4 py-3 text-right">Czas odp.</th>
                        <th class="px-4 py-3 text-left">Referer</th>
                        <th class="px-4 py-3 text-right">Produktow</th>
                        <th class="px-4 py-3 text-right">Rozmiar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($this->logs as $log)
                    <tr class="hover:bg-gray-800/50" wire:key="log-{{ $log->id }}">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td class="px-4 py-3">{{ $log->profile?->name ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @switch($log->action)
                                @case('generated')
                                    <span class="rounded bg-green-900/50 px-2 py-0.5 text-xs text-green-400">Wygenerowano</span>
                                    @break
                                @case('downloaded')
                                    <span class="rounded bg-blue-900/50 px-2 py-0.5 text-xs text-blue-400">Pobrano</span>
                                    @break
                                @case('accessed')
                                    <span class="rounded bg-gray-700 px-2 py-0.5 text-xs text-gray-300">Dostep</span>
                                    @break
                                @case('alert')
                                    <span class="rounded bg-orange-900/50 px-2 py-0.5 text-xs text-orange-400">Alert</span>
                                    @break
                                @case('token_rotated')
                                    <span class="rounded bg-purple-900/50 px-2 py-0.5 text-xs text-purple-400">Rotacja tokena</span>
                                    @break
                                @case('error')
                                    <span class="rounded bg-red-900/50 px-2 py-0.5 text-xs text-red-400">Blad</span>
                                    @break
                            @endswitch
                        </td>
                        <td class="px-4 py-3 text-xs">
                            {{ $log->user?->name ?? $log->ip_address ?? '-' }}
                        </td>
                        {{-- Bot --}}
                        <td class="px-4 py-3 text-center">
                            @if($log->is_bot)
                                <span class="inline-flex items-center gap-1 rounded bg-yellow-900/40 px-2 py-0.5 text-xs text-yellow-400" title="{{ $log->bot_name }}">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    {{ $log->bot_name }}
                                </span>
                            @else
                                <span class="text-gray-500 text-xs">
                                    <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </span>
                            @endif
                        </td>
                        {{-- Served From --}}
                        <td class="px-4 py-3 text-center">
                            @if($log->served_from === 'cache')
                                <span class="rounded bg-green-900/40 px-2 py-0.5 text-xs text-green-400">Cache</span>
                            @elseif($log->served_from === 'on_the_fly')
                                <span class="rounded bg-yellow-900/40 px-2 py-0.5 text-xs text-yellow-400">On-the-fly</span>
                            @elseif($log->served_from === 'generated')
                                <span class="rounded bg-blue-900/40 px-2 py-0.5 text-xs text-blue-400">Generacja</span>
                            @else
                                <span class="text-gray-500 text-xs">-</span>
                            @endif
                        </td>
                        {{-- Response Time --}}
                        <td class="px-4 py-3 text-right text-xs">
                            {{ $this->formatResponseTime($log->response_time_ms) }}
                        </td>
                        {{-- Referer --}}
                        <td class="px-4 py-3 text-xs text-gray-400 max-w-[150px] truncate" title="{{ $log->referer }}">
                            {{ $this->formatReferer($log->referer) }}
                        </td>
                        <td class="px-4 py-3 text-right">{{ $log->product_count ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($log->file_size)
                                {{ $this->formatFileSize($log->file_size) }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @if(in_array($log->action, ['error', 'alert', 'token_rotated']) && $log->error_message)
                    <tr class="{{ $log->action === 'alert' ? 'bg-orange-900/10' : ($log->action === 'token_rotated' ? 'bg-purple-900/10' : 'bg-red-900/10') }}" wire:key="log-err-{{ $log->id }}">
                        <td colspan="10" class="px-4 py-2 text-xs {{ $log->action === 'alert' ? 'text-orange-400' : ($log->action === 'token_rotated' ? 'text-purple-400' : 'text-red-400') }}">
                            {{ $log->error_message }}
                        </td>
                    </tr>
                    @endif
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-8 text-center text-gray-500">Brak logow</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Logs Pagination --}}
        @if($this->logs->hasPages())
            <div class="mt-6">
                {{ $this->logs->links() }}
            </div>
        @endif
    </div>
    @endcan
    @endif {{-- END logs tab --}}

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-trap.inert.noscroll="true">
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="$set('showDeleteModal', false)"></div>

            {{-- Modal Content --}}
            <div class="enterprise-card relative z-10 w-full max-w-md p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-white">Potwierdzenie usuwania</h3>
                        <p class="text-sm text-gray-400">Ta operacja jest nieodwracalna</p>
                    </div>
                </div>

                <p class="text-gray-300 mb-6">
                    Czy na pewno chcesz usunac profil
                    <strong class="text-white">"{{ $deletingProfileName }}"</strong>?
                    Powiazane pliki rowniez zostana usuniete.
                </p>

                <div class="flex items-center justify-end gap-3">
                    <button wire:click="$set('showDeleteModal', false)"
                            class="btn-enterprise-secondary">
                        Anuluj
                    </button>
                    <button wire:click="deleteProfile"
                            wire:loading.attr="disabled"
                            wire:target="deleteProfile"
                            class="btn-enterprise-danger">
                        <span wire:loading.remove wire:target="deleteProfile">Usun profil</span>
                        <span wire:loading wire:target="deleteProfile">Usuwanie...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
