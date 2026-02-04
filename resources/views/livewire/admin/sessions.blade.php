{{-- ETAP_04 FAZA A: Session Monitor View --}}
<div wire:poll.5s class="min-h-screen bg-gray-900">
    {{-- Page Header --}}
    <div class="bg-gray-900/95 backdrop-blur-sm border-b border-gray-800 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-white">
                        Monitor Sesji
                    </h1>
                    <p class="mt-1 text-sm text-gray-400">
                        Zarzadzanie aktywnymi sesjami uzytkownikow
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    {{-- View Mode Tabs --}}
                    <div class="inline-flex rounded-md shadow-sm">
                        <button wire:click="setViewMode('active')"
                            class="px-4 py-2 text-sm font-medium rounded-l-md {{ $viewMode === 'active' ? 'bg-[#e0ac7e] text-gray-900' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }} border border-gray-700">
                            Aktywne Sesje
                        </button>
                        <button wire:click="setViewMode('analytics')"
                            class="px-4 py-2 text-sm font-medium {{ $viewMode === 'analytics' ? 'bg-[#e0ac7e] text-gray-900' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }} border-t border-b border-gray-700">
                            Analityka
                        </button>
                        <button wire:click="setViewMode('security')"
                            class="px-4 py-2 text-sm font-medium rounded-r-md {{ $viewMode === 'security' ? 'bg-[#e0ac7e] text-gray-900' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }} border border-gray-700">
                            Bezpieczenstwo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-gray-800/50 rounded-lg border border-gray-700 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-900/50 rounded-lg p-3">
                        <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Aktywne Sesje</p>
                        <p class="text-2xl font-semibold text-white">{{ $sessionStats['active'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800/50 rounded-lg border border-gray-700 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-900/50 rounded-lg p-3">
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Dzisiaj</p>
                        <p class="text-2xl font-semibold text-white">{{ $sessionStats['today'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800/50 rounded-lg border border-gray-700 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-900/50 rounded-lg p-3">
                        <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Podejrzane</p>
                        <p class="text-2xl font-semibold text-white">{{ $sessionStats['suspicious'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800/50 rounded-lg border border-gray-700 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-900/50 rounded-lg p-3">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Szczyt</p>
                        <p class="text-2xl font-semibold text-white">{{ $sessionStats['peak_concurrent'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        @if($viewMode === 'active')
            {{-- Filters --}}
            <div class="bg-gray-800/50 rounded-lg border border-gray-700 mb-6 p-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Szukaj</label>
                        <input wire:model.live.debounce.300ms="search" type="text"
                            class="w-full rounded-md bg-gray-700 border-gray-600 text-white placeholder-gray-400 shadow-sm focus:border-[#e0ac7e] focus:ring-[#e0ac7e]"
                            placeholder="IP, uzytkownik, lokalizacja...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Uzytkownik</label>
                        <select wire:model.live="userFilter"
                            class="w-full rounded-md bg-gray-700 border-gray-600 text-white shadow-sm focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <option value="all">Wszyscy</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->full_name ?? $user->email }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Urzadzenie</label>
                        <select wire:model.live="deviceFilter"
                            class="w-full rounded-md bg-gray-700 border-gray-600 text-white shadow-sm focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <option value="all">Wszystkie</option>
                            @foreach($deviceTypes as $type)
                                <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Status</label>
                        <select wire:model.live="statusFilter"
                            class="w-full rounded-md bg-gray-700 border-gray-600 text-white shadow-sm focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <option value="all">Wszystkie</option>
                            <option value="active">Aktywne</option>
                            <option value="inactive">Nieaktywne</option>
                            <option value="suspicious">Podejrzane</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3 flex justify-end">
                    <button wire:click="clearFilters" class="text-sm text-gray-400 hover:text-gray-200">
                        Wyczysc filtry
                    </button>
                </div>
            </div>

            {{-- Sessions Table --}}
            <div class="bg-gray-800/50 rounded-lg border border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-700">
                        <thead class="bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    <input type="checkbox" wire:model.live="selectAll"
                                        class="rounded border-gray-600 bg-gray-700 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                </th>
                                <th wire:click="sortBy('user_id')" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:text-white">
                                    Uzytkownik
                                    @if($sortField === 'user_id')
                                        <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    Urzadzenie
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    Lokalizacja
                                </th>
                                <th wire:click="sortBy('last_activity')" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:text-white">
                                    Ostatnia aktywnosc
                                    @if($sortField === 'last_activity')
                                        <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    Akcje
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-gray-800/30 divide-y divide-gray-700">
                            @forelse($sessions as $session)
                                <tr class="{{ $session->is_suspicious ? 'bg-red-900/20' : '' }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" wire:model.live="selectedSessions" value="{{ $session->id }}"
                                            class="rounded border-gray-600 bg-gray-700 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                @if($session->user && $session->user->avatar_url)
                                                    <img class="h-10 w-10 rounded-full" src="{{ $session->user->avatar_url }}" alt="">
                                                @else
                                                    <div class="h-10 w-10 rounded-full bg-gray-600 flex items-center justify-center">
                                                        <span class="text-gray-300 font-medium text-sm">
                                                            {{ $session->user ? strtoupper(substr($session->user->first_name ?? $session->user->email, 0, 2)) : '?' }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-white">
                                                    {{ $session->user->full_name ?? 'Nieznany' }}
                                                </div>
                                                <div class="text-sm text-gray-400">
                                                    {{ $session->user->email ?? '' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $this->getDeviceIcon($session->device_type) }}"></path>
                                            </svg>
                                            <div>
                                                <div class="text-sm text-white">{{ $session->browser ?? 'Nieznana' }}</div>
                                                <div class="text-xs text-gray-400">{{ $session->os ?? '' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-white">{{ $session->ip_address }}</div>
                                        <div class="text-xs text-gray-400">
                                            {{ $session->city ?? '' }}{{ $session->city && $session->country ? ', ' : '' }}{{ $session->country ?? '' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                        {{ $session->last_activity ? $session->last_activity->diffForHumans() : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $statusBadge = $session->getStatusBadge();
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $statusBadge['color'] === 'green' ? 'bg-green-900/50 text-green-300' : '' }}
                                            {{ $statusBadge['color'] === 'yellow' ? 'bg-yellow-900/50 text-yellow-300' : '' }}
                                            {{ $statusBadge['color'] === 'red' ? 'bg-red-900/50 text-red-300' : '' }}
                                            {{ $statusBadge['color'] === 'gray' ? 'bg-gray-700 text-gray-300' : '' }}">
                                            {{ $statusBadge['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button wire:click="viewSessionDetails({{ $session->id }})"
                                            class="text-[#e0ac7e] hover:text-[#d49a6a] mr-3">
                                            Szczegoly
                                        </button>
                                        @if($session->is_active && $session->session_id !== session()->getId())
                                            <button wire:click="forceLogout({{ $session->id }})"
                                                wire:confirm="Czy na pewno chcesz wylogowac ta sesje?"
                                                class="text-red-400 hover:text-red-300">
                                                Wyloguj
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                        Brak sesji do wyswietlenia.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="px-6 py-4 border-t border-gray-700">
                    {{ $sessions->links() }}
                </div>
            </div>

            {{-- Bulk Actions --}}
            @if(count($selectedSessions) > 0)
                <div class="fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-gray-800 rounded-lg shadow-lg border border-gray-700 px-6 py-3 flex items-center space-x-4">
                    <span class="text-sm text-gray-300">
                        Zaznaczono: <strong class="text-white">{{ count($selectedSessions) }}</strong>
                    </span>
                    <button wire:click="openBulkModal"
                        class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">
                        Wyloguj zaznaczone
                    </button>
                </div>
            @endif
        @endif

        @if($viewMode === 'analytics')
            {{-- Analytics View --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Device Distribution --}}
                <div class="bg-gray-800/50 rounded-lg border border-gray-700 p-6">
                    <h3 class="text-lg font-medium text-white mb-4">Urzadzenia</h3>
                    <div class="space-y-3">
                        @foreach($deviceStats as $device)
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-300">{{ ucfirst($device['type']) }}</span>
                                <div class="flex items-center">
                                    <div class="w-32 h-2 bg-gray-700 rounded-full mr-2">
                                        <div class="h-2 rounded-full bg-[#e0ac7e]"
                                             x-data="{ percent: {{ $device['percentage'] }} }"
                                             :style="{ width: percent + '%' }"></div>
                                    </div>
                                    <span class="text-sm text-white">{{ $device['percentage'] }}%</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Location Distribution --}}
                <div class="bg-gray-800/50 rounded-lg border border-gray-700 p-6">
                    <h3 class="text-lg font-medium text-white mb-4">Lokalizacje</h3>
                    <div class="space-y-3">
                        @foreach($locationStats as $location)
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-300">{{ $location['country'] }}</span>
                                <div class="flex items-center">
                                    <div class="w-32 h-2 bg-gray-700 rounded-full mr-2">
                                        <div class="h-2 rounded-full bg-blue-500"
                                             x-data="{ percent: {{ $location['percentage'] }} }"
                                             :style="{ width: percent + '%' }"></div>
                                    </div>
                                    <span class="text-sm text-white">{{ $location['count'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if($viewMode === 'security')
            {{-- Security Alerts --}}
            <div class="space-y-6">
                {{-- Alerts --}}
                @if(count($securityAlerts) > 0)
                    <div class="bg-gray-800/50 rounded-lg border border-gray-700">
                        <div class="px-6 py-4 border-b border-gray-700">
                            <h3 class="text-lg font-medium text-white">Alerty Bezpieczenstwa</h3>
                        </div>
                        <div class="divide-y divide-gray-700">
                            @foreach($securityAlerts as $index => $alert)
                                <div class="px-6 py-4 flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <span class="inline-flex items-center justify-center h-10 w-10 rounded-full
                                                {{ $alert['severity'] === 'high' ? 'bg-red-900/50' : 'bg-yellow-900/50' }}">
                                                <svg class="w-5 h-5 {{ $alert['severity'] === 'high' ? 'text-red-400' : 'text-yellow-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="ml-4">
                                            <p class="text-sm font-medium text-white">{{ $alert['message'] }}</p>
                                            <p class="text-xs text-gray-400">{{ $alert['type'] }}</p>
                                        </div>
                                    </div>
                                    <button wire:click="dismissSecurityAlert({{ $index }})"
                                        class="text-gray-400 hover:text-gray-200">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Multiple Sessions Users --}}
                @if(count($multipleSessionUsers) > 0)
                    <div class="bg-gray-800/50 rounded-lg border border-gray-700">
                        <div class="px-6 py-4 border-b border-gray-700">
                            <h3 class="text-lg font-medium text-white">Uzytkownicy z wieloma sesjami</h3>
                        </div>
                        <div class="divide-y divide-gray-700">
                            @foreach($multipleSessionUsers as $userData)
                                <div class="px-6 py-4 flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="text-sm font-medium text-white">
                                            {{ $userData['user']->full_name ?? $userData['user']->email }}
                                        </div>
                                        <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full
                                            {{ $userData['severity'] === 'high' ? 'bg-red-900/50 text-red-300' : 'bg-yellow-900/50 text-yellow-300' }}">
                                            {{ $userData['session_count'] }} sesji
                                        </span>
                                    </div>
                                    <button wire:click="blockUserSessions({{ $userData['user']->id }})"
                                        wire:confirm="Czy na pewno chcesz wylogowac wszystkie sesje tego uzytkownika?"
                                        class="text-red-400 hover:text-red-300 text-sm font-medium">
                                        Wyloguj wszystkie
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(count($securityAlerts) === 0 && count($multipleSessionUsers) === 0)
                    <div class="bg-gray-800/50 rounded-lg border border-gray-700 p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-white">Brak alertow bezpieczenstwa</h3>
                        <p class="mt-2 text-sm text-gray-400">System nie wykryl zadnych podejrzanych aktywnosci.</p>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Session Details Modal --}}
    @if($showSessionDetails && $selectedSession)
        <div class="fixed inset-0 z-[60] overflow-y-auto" x-data x-show="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-20 pb-20 text-center sm:p-0 sm:pt-24">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-900 opacity-75"></div>
                </div>
                <div class="inline-block align-middle bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full border border-gray-700 relative">
                    <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between">
                        <h3 class="text-lg font-medium text-white">Szczegoly Sesji</h3>
                        <button wire:click="closeSessionDetails" class="text-gray-400 hover:text-gray-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="text-xs font-medium text-gray-400 uppercase">Uzytkownik</label>
                            <p class="text-sm text-white">{{ $selectedSession->user->full_name ?? 'Nieznany' }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-400 uppercase">Adres IP</label>
                            <p class="text-sm text-white">{{ $selectedSession->ip_address }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-400 uppercase">Urzadzenie</label>
                            <p class="text-sm text-white">{{ $selectedSession->getDeviceInfo() }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-400 uppercase">Lokalizacja</label>
                            <p class="text-sm text-white">{{ $selectedSession->getLocationInfo() }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-400 uppercase">Rozpoczeto</label>
                            <p class="text-sm text-white">{{ $selectedSession->created_at->format('Y-m-d H:i:s') }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-400 uppercase">Ostatnia aktywnosc</label>
                            <p class="text-sm text-white">{{ $selectedSession->last_activity ? $selectedSession->last_activity->format('Y-m-d H:i:s') : 'N/A' }}</p>
                        </div>
                        @if($selectedSession->last_url)
                            <div>
                                <label class="text-xs font-medium text-gray-400 uppercase">Ostatnia strona</label>
                                <p class="text-sm text-white truncate">{{ $selectedSession->last_url }}</p>
                            </div>
                        @endif
                        @if($selectedSession->is_suspicious)
                            <div class="bg-red-900/30 border border-red-800 rounded-lg p-3">
                                <label class="text-xs font-medium text-red-400 uppercase">Powod podejrzliwosci</label>
                                <p class="text-sm text-red-200">{{ $selectedSession->suspicious_reason }}</p>
                            </div>
                        @endif
                    </div>
                    <div class="px-6 py-4 bg-gray-700/50 flex justify-end space-x-3">
                        <button wire:click="closeSessionDetails"
                            class="px-4 py-2 bg-gray-600 text-gray-200 rounded-md hover:bg-gray-500">
                            Zamknij
                        </button>
                        @if($selectedSession->is_active && $selectedSession->session_id !== session()->getId())
                            <button wire:click="forceLogout({{ $selectedSession->id }})"
                                wire:confirm="Czy na pewno chcesz wylogowac ta sesje?"
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                Wyloguj sesje
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            class="fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg">
            {{ session('error') }}
        </div>
    @endif
</div>
