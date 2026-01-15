<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h3 class="text-lg font-medium text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Osierocone dane
            </h3>
            <p class="text-sm text-gray-400 mt-1">
                Wykrywaj i usuwaj sieroce rekordy zaklocajace integralnosc bazy danych
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="refreshStats"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50"
                    class="btn-enterprise-secondary flex items-center gap-2">
                <svg class="w-4 h-4" wire:loading.class="animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span wire:loading.remove>Odswiez</span>
                <span wire:loading>Ladowanie...</span>
            </button>

            @if($this->hasOrphans)
                <button wire:click="confirmCleanupAll"
                        wire:loading.attr="disabled"
                        class="btn-enterprise-danger flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Wyczysc wszystko ({{ $this->totalOrphanCount }})
                </button>
            @endif
        </div>
    </div>

    {{-- Summary Card --}}
    <div class="enterprise-card">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                @if($this->hasOrphans)
                    <div class="w-10 h-10 rounded-lg bg-red-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-white font-medium">Wykryto {{ $this->totalOrphanCount }} sierocych rekordow</p>
                        <p class="text-sm text-gray-400">Kliknij na kafelki ponizej aby zobaczyc szczegoly</p>
                    </div>
                @else
                    <div class="w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-white font-medium">Baza danych jest czysta</p>
                        <p class="text-sm text-gray-400">Nie wykryto sierocych rekordow</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Dashboard Cards Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        @foreach($orphanTypes as $type => $config)
            <div wire:click="selectType('{{ $type }}')"
                 class="enterprise-card cursor-pointer transition-all duration-200
                        {{ $selectedType === $type ? 'ring-2 ring-orange-500 border-orange-500/50' : 'hover:border-gray-600' }}">
                {{-- Header with icon and severity --}}
                <div class="flex items-center justify-between mb-3">
                    <div class="w-8 h-8 rounded-lg {{ ($stats[$type] ?? 0) > 0 ? 'bg-red-500/20' : 'bg-gray-700' }} flex items-center justify-center">
                        @switch($config['icon'] ?? 'folder')
                            @case('folder')
                                <svg class="w-4 h-4 {{ ($stats[$type] ?? 0) > 0 ? 'text-red-400' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                </svg>
                                @break
                            @case('box')
                                <svg class="w-4 h-4 {{ ($stats[$type] ?? 0) > 0 ? 'text-red-400' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                @break
                            @case('image')
                            @case('images')
                                <svg class="w-4 h-4 {{ ($stats[$type] ?? 0) > 0 ? 'text-red-400' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                @break
                            @case('link')
                                <svg class="w-4 h-4 {{ ($stats[$type] ?? 0) > 0 ? 'text-red-400' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                                @break
                            @case('file-text')
                                <svg class="w-4 h-4 {{ ($stats[$type] ?? 0) > 0 ? 'text-red-400' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                @break
                            @default
                                <svg class="w-4 h-4 {{ ($stats[$type] ?? 0) > 0 ? 'text-red-400' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                </svg>
                        @endswitch
                    </div>

                    @if($config['severity'] === 'high' && ($stats[$type] ?? 0) > 0)
                        <span class="px-1.5 py-0.5 text-xs font-medium rounded bg-red-500/20 text-red-400">!</span>
                    @endif
                </div>

                {{-- Label --}}
                <p class="text-xs text-gray-400 mb-1 truncate" title="{{ $config['label'] }}">
                    {{ $config['label'] }}
                </p>

                {{-- Count --}}
                <p class="text-2xl font-bold {{ ($stats[$type] ?? 0) > 0 ? 'text-red-400' : 'text-green-400' }}">
                    {{ $stats[$type] ?? 0 }}
                </p>

                {{-- Cleanup button --}}
                @if(($stats[$type] ?? 0) > 0)
                    <button wire:click.stop="confirmCleanup('{{ $type }}')"
                            class="mt-2 text-xs text-orange-400 hover:text-orange-300 transition-colors">
                        Wyczysc &rarr;
                    </button>
                @else
                    <p class="mt-2 text-xs text-gray-500">OK</p>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Detail View --}}
    @if($selectedType && !empty($selectedOrphans))
        <div class="enterprise-card">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-white font-medium flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    {{ $orphanTypes[$selectedType]['label'] }} - szczegoly ({{ count($selectedOrphans) }} rekordow)
                </h4>
                <button wire:click="selectType('{{ $selectedType }}')"
                        class="text-gray-400 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <p class="text-sm text-gray-400 mb-4">{{ $orphanTypes[$selectedType]['description'] }}</p>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-gray-400 border-b border-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left">ID</th>
                            @if(str_starts_with($selectedType, 'shop_mappings'))
                                <th class="px-4 py-2 text-left">PPM Value</th>
                                <th class="px-4 py-2 text-left">PrestaShop ID</th>
                                <th class="px-4 py-2 text-left">Sklep</th>
                            @elseif(str_starts_with($selectedType, 'media'))
                                <th class="px-4 py-2 text-left">Mediable ID</th>
                                <th class="px-4 py-2 text-left">Typ</th>
                                <th class="px-4 py-2 text-left">Sciezka</th>
                            @elseif($selectedType === 'product_categories')
                                <th class="px-4 py-2 text-left">Product ID</th>
                                <th class="px-4 py-2 text-left">Category ID</th>
                                <th class="px-4 py-2 text-left">Primary</th>
                            @else
                                <th class="px-4 py-2 text-left">Dane</th>
                            @endif
                            <th class="px-4 py-2 text-left">Data utworzenia</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-300">
                        @foreach($selectedOrphans as $orphan)
                            <tr class="border-b border-gray-800 hover:bg-gray-800/50">
                                <td class="px-4 py-2 font-mono text-xs">{{ $orphan['id'] ?? '-' }}</td>

                                @if(str_starts_with($selectedType, 'shop_mappings'))
                                    <td class="px-4 py-2">
                                        <span class="text-red-400">{{ $orphan['ppm_value'] ?? '-' }}</span>
                                    </td>
                                    <td class="px-4 py-2">{{ $orphan['prestashop_id'] ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $orphan['shop']['name'] ?? '-' }}</td>
                                @elseif(str_starts_with($selectedType, 'media'))
                                    <td class="px-4 py-2">
                                        <span class="text-red-400">{{ $orphan['mediable_id'] ?? '-' }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-xs">{{ class_basename($orphan['mediable_type'] ?? '') }}</td>
                                    <td class="px-4 py-2 text-xs truncate max-w-xs" title="{{ $orphan['path'] ?? '' }}">
                                        {{ $orphan['path'] ?? '-' }}
                                    </td>
                                @elseif($selectedType === 'product_categories')
                                    <td class="px-4 py-2">{{ $orphan['product_id'] ?? '-' }}</td>
                                    <td class="px-4 py-2">
                                        <span class="text-red-400">{{ $orphan['category_id'] ?? '-' }}</span>
                                    </td>
                                    <td class="px-4 py-2">{{ ($orphan['is_primary'] ?? false) ? 'Tak' : 'Nie' }}</td>
                                @else
                                    <td class="px-4 py-2" colspan="3">
                                        <pre class="text-xs">{{ json_encode($orphan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </td>
                                @endif

                                <td class="px-4 py-2 text-xs text-gray-500">
                                    {{ $orphan['created_at'] ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if(count($selectedOrphans) >= 50)
                <p class="text-xs text-gray-500 mt-3 text-center">
                    Pokazano pierwsze 50 rekordow. Wyczysc aby zobaczyc pozostale.
                </p>
            @endif
        </div>
    @elseif($selectedType && empty($selectedOrphans))
        <div class="enterprise-card text-center py-8">
            <svg class="w-12 h-12 mx-auto text-green-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-white font-medium">Brak sierocych rekordow</p>
            <p class="text-sm text-gray-400 mt-1">
                Typ "{{ $orphanTypes[$selectedType]['label'] }}" jest czysty
            </p>
        </div>
    @endif

    {{-- Last Cleanup Result --}}
    @if($lastCleanupResult)
        <div class="enterprise-card border-green-500/30 bg-green-500/5">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-white font-medium">Czyszczenie zakonczone</p>
                    <p class="text-sm text-gray-400">
                        Usunieto {{ $lastCleanupResult['deleted'] }} rekordow typu
                        "{{ $orphanTypes[$lastCleanupResult['type']]['label'] ?? $lastCleanupResult['type'] }}"
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Single Type Cleanup Confirmation Modal --}}
    @if($showConfirmModal && $pendingCleanupType)
        <div class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4">
            <div class="enterprise-card max-w-md w-full">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-red-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h4 class="text-white font-medium">Potwierdzenie usuwania</h4>
                </div>

                <p class="text-gray-300 mb-6">
                    Czy na pewno chcesz usunac
                    <span class="text-red-400 font-bold">{{ $stats[$pendingCleanupType] ?? 0 }}</span>
                    sierocych rekordow typu
                    <span class="text-orange-400">"{{ $orphanTypes[$pendingCleanupType]['label'] ?? '' }}"</span>?
                </p>

                <p class="text-sm text-gray-500 mb-6">
                    {{ $orphanTypes[$pendingCleanupType]['description'] ?? '' }}
                </p>

                <div class="flex justify-end gap-3">
                    <button wire:click="cancelCleanup"
                            class="btn-enterprise-secondary">
                        Anuluj
                    </button>
                    <button wire:click="executeCleanup"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50"
                            class="btn-enterprise-danger flex items-center gap-2">
                        <span wire:loading.remove>Usun</span>
                        <span wire:loading class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Usuwanie...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Cleanup All Confirmation Modal --}}
    @if($showCleanupAllModal)
        <div class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4">
            <div class="enterprise-card max-w-md w-full">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-red-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h4 class="text-white font-medium">Usuwanie wszystkich sierocych danych</h4>
                </div>

                <p class="text-gray-300 mb-4">
                    Czy na pewno chcesz usunac
                    <span class="text-red-400 font-bold">{{ $this->totalOrphanCount }}</span>
                    sierocych rekordow ze wszystkich typow?
                </p>

                <div class="bg-gray-800/50 rounded-lg p-3 mb-6">
                    <p class="text-sm text-gray-400 mb-2">Typy do wyczyszczenia:</p>
                    <ul class="text-sm space-y-1">
                        @foreach($orphanTypes as $type => $config)
                            @if(($stats[$type] ?? 0) > 0)
                                <li class="flex items-center justify-between">
                                    <span class="text-gray-300">{{ $config['label'] }}</span>
                                    <span class="text-red-400 font-medium">{{ $stats[$type] }}</span>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>

                <div class="flex justify-end gap-3">
                    <button wire:click="cancelCleanupAll"
                            class="btn-enterprise-secondary">
                        Anuluj
                    </button>
                    <button wire:click="executeCleanupAll"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50"
                            class="btn-enterprise-danger flex items-center gap-2">
                        <span wire:loading.remove>Usun wszystko</span>
                        <span wire:loading class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Usuwanie...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
