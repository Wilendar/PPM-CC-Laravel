<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-black relative">
    
    <!-- Animated Background Elements with MPP TRADE Colors -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-32 -right-32 w-64 h-64 rounded-full blur-3xl animate-pulse" style="background: radial-gradient(circle, rgba(224, 172, 126, 0.1), rgba(209, 151, 90, 0.05));"></div>
        <div class="absolute -bottom-32 -left-32 w-64 h-64 rounded-full blur-3xl animate-pulse" style="background: radial-gradient(circle, rgba(209, 151, 90, 0.1), rgba(224, 172, 126, 0.05)); animation-delay: 2s;"></div>
    </div>
    
    <!-- Page Header -->
    <div class="relative backdrop-blur-xl shadow-2xl" style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border-bottom: 1px solid rgba(224, 172, 126, 0.3); z-index: 10000;">
        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-12">
            <div class="flex items-center justify-between h-24">
                <div class="flex items-center">
                    <!-- Logo and Title -->
                    <div class="flex-shrink-0">
                        <div class="relative w-12 h-12 rounded-xl flex items-center justify-center shadow-lg transform transition-transform duration-300 hover:scale-105" style="background: linear-gradient(45deg, #e0ac7e, #d1975a);">
                            <svg class="w-7 h-7 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <div class="absolute inset-0 rounded-xl opacity-75 blur animate-pulse" style="background: linear-gradient(45deg, #e0ac7e, #d1975a);"></div>
                        </div>
                    </div>
                    <div class="ml-4 flex-1 min-w-0">
                        <h1 class="text-xl font-bold tracking-tight" style="color: #e0ac7e !important;">
                            KONTROLA SYNCHRONIZACJI
                        </h1>
                        <p class="text-xs font-medium text-gray-400 tracking-wide">
                            Zarządzanie synchronizacją sklepów PrestaShop
                        </p>
                    </div>
                </div>
                
                <!-- Back Button -->
                <a href="{{ route('admin.shops') }}" 
                   class="relative inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-lg text-white transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"
                   style="background: linear-gradient(45deg, rgba(224, 172, 126, 0.8), rgba(209, 151, 90, 0.8)); border: 1px solid rgba(224, 172, 126, 0.5);"
                   onmouseover="this.style.background='linear-gradient(45deg, rgba(209, 151, 90, 0.9), rgba(194, 132, 73, 0.9))'"
                   onmouseout="this.style.background='linear-gradient(45deg, rgba(224, 172, 126, 0.8), rgba(209, 151, 90, 0.8))'">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Powrót do sklepów
                </a>
            </div>
        </div>
    </div>

    <!-- Content Section -->
    <div class="relative z-10 max-w-7xl mx-auto px-6 sm:px-8 lg:px-12 py-8">

        <!-- Sync Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-6 mb-8">
            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border" 
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Sklepy</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['total_shops'] }}</p>
                    </div>
                </div>
            </div>

            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border" 
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-yellow-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Aktywne zadania</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['active_sync_jobs'] }}</p>
                    </div>
                </div>
            </div>

            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border" 
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Dzisiaj ukończone</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['completed_today'] }}</p>
                    </div>
                </div>
            </div>

            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border" 
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-red-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Dzisiaj błędy</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['failed_today'] }}</p>
                    </div>
                </div>
            </div>

            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border" 
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-orange-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Wymagają sync</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['sync_due_count'] }}</p>
                    </div>
                </div>
            </div>

            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border" 
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-purple-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Średni czas</p>
                        <p class="text-2xl font-bold text-white">{{ number_format($stats['avg_sync_time'], 1) }}s</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sync Configuration Panel -->
        <div class="relative backdrop-blur-xl shadow-2xl rounded-xl border p-6 mb-8" 
             style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(224, 172, 126, 0.3);">
            
            <h3 class="text-lg font-semibold text-white mb-6 flex items-center">
                <svg class="w-6 h-6 text-[#e0ac7e] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Konfiguracja Synchronizacji
            </h3>
            
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Sync Types -->
                <div>
                    <label class="block text-sm font-medium text-white mb-3">Typ synchronizacji</label>
                    <div class="space-y-2">
                        @foreach(['products' => 'Produkty', 'categories' => 'Kategorie', 'prices' => 'Ceny', 'stock' => 'Stany'] as $type => $label)
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       wire:model.defer="selectedSyncTypes" 
                                       value="{{ $type }}"
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-2 text-sm text-white">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Batch Size -->
                <div>
                    <label for="batchSize" class="block text-sm font-medium text-white mb-2">Wielkość paczki</label>
                    <input type="number" 
                           id="batchSize"
                           wire:model.defer="batchSize" 
                           min="1" max="100"
                           class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                    <p class="text-gray-400 text-xs mt-1">1-100 rekordów na raz</p>
                </div>

                <!-- Timeout -->
                <div>
                    <label for="syncTimeout" class="block text-sm font-medium text-white mb-2">Timeout (sekundy)</label>
                    <input type="number" 
                           id="syncTimeout"
                           wire:model.defer="syncTimeout" 
                           min="60" max="3600"
                           class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                    <p class="text-gray-400 text-xs mt-1">60-3600 sekund</p>
                </div>

                <!-- Conflict Resolution -->
                <div>
                    <label for="conflictResolution" class="block text-sm font-medium text-white mb-2">Rozwiązywanie konfliktów</label>
                    <select id="conflictResolution"
                            wire:model.defer="conflictResolution"
                            class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                        <option value="ppm_wins">PPM wygrywa</option>
                        <option value="prestashop_wins">PrestaShop wygrywa</option>
                        <option value="newest_wins">Najnowsze wygrywa</option>
                        <option value="manual">Manualne</option>
                    </select>
                </div>
            </div>

            <!-- Advanced Configuration Toggle Button -->
            <div class="flex items-center justify-center mt-6 pt-4 border-t border-gray-600">
                <button wire:click="toggleSyncConfig"
                        class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                    </svg>
                    {{ $showSyncConfig ? 'Ukryj zaawansowaną konfigurację' : 'Pokaż zaawansowaną konfigurację' }}
                </button>
            </div>
        </div>

        <!-- Advanced Sync Configuration Panel - SEKCJA 2.2.1.2 -->
        @if($showSyncConfig)
        <div class="relative backdrop-blur-xl shadow-2xl rounded-xl border p-6 mb-8"
             style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(224, 172, 126, 0.3);">

            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 text-[#e0ac7e] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                    </svg>
                    Zaawansowana Konfiguracja Synchronizacji
                </h3>
                <span class="text-xs text-gray-400">SEKCJA 2.2.1.2 - Sync Configuration</span>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">

                <!-- Auto-sync Scheduler - 2.2.1.2.1 -->
                <div class="backdrop-blur-xl rounded-xl p-4" style="background: linear-gradient(135deg, rgba(55, 65, 81, 0.6), rgba(31, 41, 55, 0.4)); border: 1px solid rgba(224, 172, 126, 0.2);">
                    <h4 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Harmonogram
                    </h4>

                    <div class="space-y-4">
                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model.defer="autoSyncEnabled"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-2 text-sm text-white">Włącz auto-sync</span>
                        </label>

                        @if($autoSyncEnabled)
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Częstotliwość</label>
                            <select wire:model.defer="autoSyncFrequency"
                                    class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] text-sm">
                                <option value="hourly">Co godzinę</option>
                                <option value="daily">Codziennie</option>
                                <option value="weekly">Tygodniowo</option>
                            </select>
                        </div>

                        @if($autoSyncFrequency !== 'hourly')
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Godzina</label>
                            <input type="number"
                                   wire:model.defer="autoSyncScheduleHour"
                                   min="0" max="23"
                                   class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] text-sm">
                        </div>
                        @endif

                        @if($autoSyncFrequency === 'weekly')
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Dni tygodnia</label>
                            <div class="space-y-1">
                                @foreach(['monday' => 'Pon', 'tuesday' => 'Wt', 'wednesday' => 'Śr', 'thursday' => 'Czw', 'friday' => 'Pt', 'saturday' => 'Sob', 'sunday' => 'Nd'] as $day => $label)
                                    <label class="flex items-center">
                                        <input type="checkbox"
                                               wire:model.defer="autoSyncDaysOfWeek"
                                               value="{{ $day }}"
                                               class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                        <span class="ml-2 text-sm text-white">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model.defer="autoSyncOnlyConnected"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-2 text-sm text-white">Tylko połączone</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model.defer="autoSyncSkipMaintenanceMode"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-2 text-sm text-white">Pomiń konserwację</span>
                        </label>
                        @endif
                    </div>
                </div>

                <!-- Retry Logic - 2.2.1.2.2 -->
                <div class="backdrop-blur-xl rounded-xl p-4" style="background: linear-gradient(135deg, rgba(55, 65, 81, 0.6), rgba(31, 41, 55, 0.4)); border: 1px solid rgba(224, 172, 126, 0.2);">
                    <h4 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Ponawianie
                    </h4>

                    <div class="space-y-4">
                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model.defer="retryEnabled"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-2 text-sm text-white">Włącz retry</span>
                        </label>

                        @if($retryEnabled)
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Max prób</label>
                            <input type="number"
                                   wire:model.defer="maxRetryAttempts"
                                   min="1" max="10"
                                   class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Opóźnienie (min)</label>
                            <input type="number"
                                   wire:model.defer="retryDelayMinutes"
                                   min="1" max="1440"
                                   class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Mnożnik backoff</label>
                            <input type="number"
                                   wire:model.defer="retryBackoffMultiplier"
                                   min="1" max="5" step="0.1"
                                   class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] text-sm">
                        </div>

                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model.defer="retryOnlyTransientErrors"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-2 text-sm text-white">Tylko błędy przejściowe</span>
                        </label>
                        @endif
                    </div>
                </div>

                <!-- Notifications - 2.2.1.2.3 -->
                <div class="backdrop-blur-xl rounded-xl p-4" style="background: linear-gradient(135deg, rgba(55, 65, 81, 0.6), rgba(31, 41, 55, 0.4)); border: 1px solid rgba(224, 172, 126, 0.2);">
                    <h4 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 13h6l-6 6v-6zM9 3l8 8-8 8V3z"></path>
                        </svg>
                        Powiadomienia
                    </h4>

                    <div class="space-y-4">
                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model.defer="notificationsEnabled"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-2 text-sm text-white">Włącz powiadomienia</span>
                        </label>

                        @if($notificationsEnabled)
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox"
                                       wire:model.defer="notifyOnSuccess"
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-2 text-sm text-white">Sukces</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox"
                                       wire:model.defer="notifyOnFailure"
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-2 text-sm text-white">Błędy</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox"
                                       wire:model.defer="notifyOnRetryExhausted"
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-2 text-sm text-white">Retry wyczerpane</span>
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Kanały</label>
                            <div class="space-y-1">
                                <label class="flex items-center">
                                    <input type="checkbox"
                                           wire:model.defer="notificationChannels"
                                           value="email"
                                           class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                    <span class="ml-2 text-sm text-white">Email</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox"
                                           wire:model.defer="notificationChannels"
                                           value="slack"
                                           class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                    <span class="ml-2 text-sm text-white">Slack</span>
                                </label>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Performance - 2.2.1.2.4 -->
                <div class="backdrop-blur-xl rounded-xl p-4" style="background: linear-gradient(135deg, rgba(55, 65, 81, 0.6), rgba(31, 41, 55, 0.4)); border: 1px solid rgba(224, 172, 126, 0.2);">
                    <h4 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Wydajność
                    </h4>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Tryb wydajności</label>
                            <select wire:model.defer="performanceMode"
                                    class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] text-sm">
                                <option value="economy">Ekonomiczny</option>
                                <option value="balanced">Zrównoważony</option>
                                <option value="performance">Wydajnościowy</option>
                            </select>
                            <p class="text-xs text-gray-400 mt-1">{{ $this->getPerformanceModeDescription($performanceMode) }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Max równoczesnych</label>
                            <input type="number"
                                   wire:model.defer="maxConcurrentJobs"
                                   min="1" max="10"
                                   class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Opóźnienie (ms)</label>
                            <input type="number"
                                   wire:model.defer="jobProcessingDelay"
                                   min="0" max="5000"
                                   class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Pamięć (MB)</label>
                            <input type="number"
                                   wire:model.defer="memoryLimit"
                                   min="128" max="2048"
                                   class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] text-sm">
                        </div>
                    </div>
                </div>

                <!-- Backup - 2.2.1.2.5 -->
                <div class="backdrop-blur-xl rounded-xl p-4" style="background: linear-gradient(135deg, rgba(55, 65, 81, 0.6), rgba(31, 41, 55, 0.4)); border: 1px solid rgba(224, 172, 126, 0.2);">
                    <h4 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 text-purple-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                        </svg>
                        Backup
                    </h4>

                    <div class="space-y-4">
                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model.defer="backupBeforeSync"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-2 text-sm text-white">Backup przed sync</span>
                        </label>

                        @if($backupBeforeSync)
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Retencja (dni)</label>
                            <input type="number"
                                   wire:model.defer="backupRetentionDays"
                                   min="1" max="365"
                                   class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] text-sm">
                        </div>

                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model.defer="backupOnlyOnMajorChanges"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-2 text-sm text-white">Tylko duże zmiany</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model.defer="backupCompressionEnabled"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-2 text-sm text-white">Kompresja</span>
                        </label>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Configuration Actions -->
            <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-600">
                <div class="flex items-center space-x-3">
                    <button wire:click="saveSyncConfiguration"
                            class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Zapisz konfigurację
                    </button>

                    <button wire:click="testSyncConfiguration"
                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Testuj konfigurację
                    </button>

                    <button wire:click="resetSyncConfigurationToDefaults"
                            class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center"
                            onclick="return confirm('Czy na pewno chcesz zresetować konfigurację do wartości domyślnych?')">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Reset do domyślnych
                    </button>
                </div>

                <div class="text-sm text-gray-400">
                    {{ $this->getSyncScheduleDescription() }}
                </div>
            </div>
        </div>
        @endif

        <!-- Bulk Actions -->
        <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 mb-8 border" 
             style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
            
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <label class="flex items-center">
                        <input type="checkbox" 
                               wire:model="selectAll"
                               wire:click="toggleSelectAll"
                               class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                        <span class="ml-2 text-sm text-white">Zaznacz wszystkie</span>
                    </label>
                    
                    @if(count($selectedShops) > 0)
                        <span class="text-sm text-gray-400">
                            Wybrano: {{ count($selectedShops) }} sklepów
                        </span>
                    @endif
                </div>

                <div class="flex items-center space-x-3">
                    @if(count($selectedShops) > 0)
                        <button wire:click="syncSelectedShops" 
                                wire:loading.attr="disabled"
                                class="relative px-6 py-2 text-white rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center font-medium"
                                style="background: linear-gradient(45deg, rgba(34, 197, 94, 0.8), rgba(22, 163, 74, 0.8)); border: 1px solid rgba(34, 197, 94, 0.5);">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span wire:loading.remove wire:target="syncSelectedShops">Synchronizuj wybrane</span>
                            <span wire:loading wire:target="syncSelectedShops">Uruchamianie...</span>
                        </button>
                    @endif
                    
                    <button wire:click="resetFilters" 
                            class="px-6 py-2 bg-gray-700 bg-opacity-60 text-gray-300 rounded-lg hover:bg-gray-600 hover:bg-opacity-80 transition-colors duration-200 flex items-center border border-gray-600">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Error Messages -->
        @if ($errors->any())
            <div class="mb-6 bg-red-900 bg-opacity-20 border border-red-500 border-opacity-30 rounded-lg p-4 backdrop-blur-sm">
                <div class="flex">
                    <svg class="w-5 h-5 text-red-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-red-300 mb-2">Błędy:</h3>
                        <ul class="text-sm text-red-200 list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Active Sync Jobs -->
        @if(count($activeSyncJobs) > 0)
            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 mb-8 border" 
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Aktywne synchronizacje
                </h3>

                <div class="space-y-3">
                    @foreach($activeSyncJobs as $job)
                        <div class="bg-gray-800 bg-opacity-40 border border-gray-600 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-white">{{ $job['job_name'] ?? 'Synchronizacja' }}</h4>
                                    <p class="text-sm text-gray-400">ID: {{ $job['job_id'] }}</p>
                                </div>
                                
                                <div class="flex items-center space-x-3">
                                    @if(isset($syncProgress[$job['job_id']]))
                                        <div class="w-32 bg-gray-700 rounded-full h-2">
                                            <div class="bg-[#e0ac7e] h-2 rounded-full transition-all duration-300" 
                                                 style="width: {{ $syncProgress[$job['job_id']]['progress'] ?? 0 }}%"></div>
                                        </div>
                                        <span class="text-sm text-gray-300">{{ $syncProgress[$job['job_id']]['progress'] ?? 0 }}%</span>
                                    @endif
                                    
                                    <button wire:click="cancelSyncJob('{{ $job['job_id'] }}')" 
                                            class="px-3 py-1 bg-red-600 bg-opacity-60 text-red-200 rounded text-xs hover:bg-red-500 hover:bg-opacity-80 transition-colors">
                                        Anuluj
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Shops List -->
        <div class="relative backdrop-blur-xl shadow-2xl rounded-xl border" 
             style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(224, 172, 126, 0.3);">
            
            <!-- Search and Filters -->
            <div class="p-6 border-b border-gray-600">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white">Sklepy PrestaShop</h3>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <!-- Search -->
                    <div>
                        <input type="text" 
                               wire:model.debounce.300ms="search"
                               placeholder="Szukaj sklepów..."
                               class="w-full px-4 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200 placeholder-gray-400">
                    </div>
                    
                    <!-- Status Filter -->
                    <div>
                        <select wire:model="statusFilter"
                                class="w-full px-4 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                            <option value="all">Wszystkie sklepy</option>
                            <option value="connected">Połączone</option>
                            <option value="sync_due">Wymagają synchronizacji</option>
                            <option value="sync_errors">Z błędami sync</option>
                            <option value="never_synced">Nigdy nie synchronizowane</option>
                        </select>
                    </div>
                    
                    <!-- Sort -->
                    <div>
                        <select wire:model="sortBy"
                                class="w-full px-4 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                            <option value="last_sync_at">Ostatnia synchronizacja</option>
                            <option value="name">Nazwa sklepu</option>
                            <option value="created_at">Data dodania</option>
                            <option value="sync_success_count">Udane synchronizacje</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Shops Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-800 bg-opacity-40 border-b border-gray-600">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                <input type="checkbox" wire:model="selectAll" wire:click="toggleSelectAll" class="rounded border-gray-600 bg-gray-800 text-[#e0ac7e]">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Sklep</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Ostatnia sync</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Statystyki</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-600">
                        @forelse($shops as $shop)
                            <tr class="hover:bg-gray-800 hover:bg-opacity-30 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" 
                                           wire:click="toggleShopSelection({{ $shop->id }})"
                                           @if(in_array($shop->id, $selectedShops)) checked @endif
                                           class="rounded border-gray-600 bg-gray-800 text-[#e0ac7e]">
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-white">{{ $shop->name }}</div>
                                        <div class="text-sm text-gray-400">{{ Str::limit($shop->url, 40) }}</div>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($shop->connection_status === 'connected') bg-green-900 bg-opacity-40 text-green-300
                                        @elseif($shop->connection_status === 'error') bg-red-900 bg-opacity-40 text-red-300
                                        @else bg-gray-700 bg-opacity-40 text-gray-300 @endif">
                                        @if($shop->connection_status === 'connected') Połączony
                                        @elseif($shop->connection_status === 'error') Błąd połączenia
                                        @else Nieznany @endif
                                    </span>
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($shop->last_sync_at)
                                        <div class="text-sm text-white">{{ $shop->last_sync_at->format('d.m.Y H:i') }}</div>
                                        <div class="text-xs text-gray-400">{{ $shop->last_sync_at->diffForHumans() }}</div>
                                    @else
                                        <span class="text-sm text-gray-400">Nigdy</span>
                                    @endif
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-4 text-xs">
                                        <span class="text-green-400">✓{{ $shop->sync_success_count }}</span>
                                        <span class="text-red-400">✗{{ $shop->sync_error_count }}</span>
                                        <span class="text-gray-400">{{ $shop->products_synced }} prod.</span>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button wire:click="syncSingleShop({{ $shop->id }})" 
                                            class="inline-flex items-center px-3 py-1 bg-[#e0ac7e] bg-opacity-80 text-white rounded text-xs hover:bg-[#d1975a] transition-colors">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        Sync
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="text-gray-400">
                                        <svg class="w-12 h-12 mx-auto mb-4 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        <p class="text-lg font-medium">Brak sklepów</p>
                                        <p class="text-sm">Dodaj pierwszy sklep PrestaShop aby rozpocząć synchronizację</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($shops->hasPages())
                <div class="px-6 py-4 border-t border-gray-600">
                    {{ $shops->links() }}
                </div>
            @endif
        </div>

        <!-- Recent Sync Jobs -->
        <div class="mt-8 relative backdrop-blur-xl shadow-lg rounded-lg border" 
             style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
            
            <div class="px-6 py-4 border-b border-gray-600">
                <h3 class="text-lg font-semibold text-white flex items-center">
                    <svg class="w-5 h-5 text-[#e0ac7e] mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Ostatnie zadania synchronizacji
                </h3>
            </div>
            
            <div class="p-6">
                <div class="space-y-3">
                    @forelse($recentJobs as $job)
                        <div class="flex items-center justify-between p-3 bg-gray-800 bg-opacity-40 rounded-lg border border-gray-600">
                            <div>
                                <div class="text-sm font-medium text-white">{{ $job->job_name }}</div>
                                <div class="text-xs text-gray-400">
                                    {{ $job->created_at->format('d.m.Y H:i') }} • {{ $job->created_at->diffForHumans() }}
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($job->status === 'completed') bg-green-900 bg-opacity-40 text-green-300
                                    @elseif($job->status === 'failed') bg-red-900 bg-opacity-40 text-red-300
                                    @elseif($job->status === 'running') bg-yellow-900 bg-opacity-40 text-yellow-300
                                    @else bg-gray-700 bg-opacity-40 text-gray-300 @endif">
                                    {{ ucfirst($job->status) }}
                                </span>
                                
                                @if($job->duration_seconds)
                                    <span class="text-xs text-gray-400">{{ $job->duration_seconds }}s</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-gray-400 py-8">
                            <svg class="w-8 h-8 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm">Brak ostatnich zadań synchronizacji</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>