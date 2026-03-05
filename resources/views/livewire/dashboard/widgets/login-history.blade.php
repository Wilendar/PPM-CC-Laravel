<div class="dashboard-widget">
    {{-- Header --}}
    <div class="dashboard-widget__header">
        <span class="dashboard-widget__title">Historia logowan</span>
        <div class="dashboard-widget__icon">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
        </div>
    </div>

    {{-- Login history table --}}
    @if ($logins->isNotEmpty())
        <div class="overflow-x-auto -mx-1.5">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-gray-500 text-xs uppercase tracking-wider">
                        <th class="text-left pb-3 px-2 font-medium">Data</th>
                        <th class="text-left pb-3 px-2 font-medium">IP</th>
                        <th class="text-left pb-3 px-2 font-medium">Lokalizacja</th>
                        <th class="text-center pb-3 px-2 font-medium">Urzadzenie</th>
                        <th class="text-left pb-3 px-2 font-medium">Przegladarka</th>
                        <th class="text-center pb-3 px-2 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logins as $login)
                        <tr wire:key="login-{{ $login->id }}" class="border-t border-gray-700/50">
                            <td class="py-2.5 px-2 text-gray-300 whitespace-nowrap">
                                {{ $login->attempted_at->format('d.m.Y H:i') }}
                            </td>
                            <td class="py-2.5 px-2 text-gray-400 font-mono text-xs">
                                {{ $login->ip_address }}
                            </td>
                            <td class="py-2.5 px-2 text-gray-400">
                                {{ $this->formatLocation($login) }}
                            </td>
                            <td class="py-2.5 px-2 text-center">
                                <svg class="w-4 h-4 text-gray-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                     title="{{ $login->device_type ?? 'desktop' }}">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="{{ $this->getDeviceIconPath($login->device_type ?? 'desktop') }}" />
                                </svg>
                            </td>
                            <td class="py-2.5 px-2 text-gray-400 text-xs">
                                {{ $login->browser ?? '-' }}
                            </td>
                            <td class="py-2.5 px-2 text-center">
                                @if ($login->success)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-400">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        OK
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-500/20 text-red-400">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Blad
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @elseif ($usingSessionFallback && $sessions->isNotEmpty())
        {{-- Fallback: UserSession data --}}
        <div class="overflow-x-auto -mx-1.5">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-gray-500 text-xs uppercase tracking-wider">
                        <th class="text-left pb-3 px-2 font-medium">Data</th>
                        <th class="text-left pb-3 px-2 font-medium">IP</th>
                        <th class="text-left pb-3 px-2 font-medium">Lokalizacja</th>
                        <th class="text-center pb-3 px-2 font-medium">Urzadzenie</th>
                        <th class="text-left pb-3 px-2 font-medium">Przegladarka</th>
                        <th class="text-center pb-3 px-2 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sessions as $session)
                        <tr wire:key="session-{{ $session->id }}" class="border-t border-gray-700/50">
                            <td class="py-2.5 px-2 text-gray-300 whitespace-nowrap">
                                {{ $session->created_at->format('d.m.Y H:i') }}
                            </td>
                            <td class="py-2.5 px-2 text-gray-400 font-mono text-xs">
                                {{ $session->ip_address }}
                            </td>
                            <td class="py-2.5 px-2 text-gray-400">
                                {{ $session->getLocationInfo() }}
                            </td>
                            <td class="py-2.5 px-2 text-center">
                                <svg class="w-4 h-4 text-gray-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                     title="{{ $session->device_type ?? 'desktop' }}">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="{{ $session->getDeviceIconPath() }}" />
                                </svg>
                            </td>
                            <td class="py-2.5 px-2 text-gray-400 text-xs">
                                {{ $session->browser ?? '-' }}
                            </td>
                            <td class="py-2.5 px-2 text-center">
                                @if ($session->is_active)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-400">OK</span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-500/20 text-gray-400">Zakonczona</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-gray-600 mt-2 text-center">Dane z historii sesji (fallback)</p>
    @else
        <div class="text-center py-6">
            <svg class="w-8 h-8 text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <p class="text-sm text-gray-500">Brak historii logowan</p>
        </div>
    @endif
</div>
