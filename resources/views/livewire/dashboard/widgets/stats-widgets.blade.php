<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <!-- Total Products Widget -->
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg rounded-xl border border-gray-200 dark:border-gray-700 widget-enter">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Całkowita liczba produktów
                        </h3>
                        <div class="flex items-baseline">
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($totalProducts['total']) }}
                            </p>
                            @if($showTrends && $totalProducts['trend'] !== 0)
                                <div class="ml-2 flex items-center">
                                    <svg class="w-4 h-4 {{ $totalProducts['trend_direction'] === 'up' ? 'text-green-500' : 'text-red-500' }}" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($totalProducts['trend_direction'] === 'up')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7l-9.2 9.2M7 7v10h10"></path>
                                        @endif
                                    </svg>
                                    <span class="text-sm font-semibold {{ $totalProducts['trend_direction'] === 'up' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ abs($totalProducts['trend_percentage']) }}%
                                    </span>
                                </div>
                            @endif
                        </div>
                        @if($showTrends)
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                @if($totalProducts['trend'] > 0)
                                    +{{ $totalProducts['trend'] }} od wczoraj
                                @elseif($totalProducts['trend'] < 0)
                                    {{ $totalProducts['trend'] }} od wczoraj
                                @else
                                    Bez zmian od wczoraj
                                @endif
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <!-- Progress bar for visual appeal -->
        <div class="bg-gray-50 dark:bg-gray-700 px-6 py-3">
            <div class="flex items-center text-xs">
                <span class="text-gray-600 dark:text-gray-400">Status: </span>
                <span class="ml-1 font-semibold text-green-600 dark:text-green-400">Aktywny</span>
                <div class="ml-auto flex items-center">
                    <div class="w-2 h-2 bg-green-400 rounded-full pulse-dot"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Users Widget -->
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg rounded-xl border border-gray-200 dark:border-gray-700 widget-enter">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Aktywni użytkownicy
                        </h3>
                        <div class="flex items-baseline">
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                                {{ $activeUsers['today'] }}
                            </p>
                            <span class="ml-2 text-lg text-gray-500 dark:text-gray-400">
                                / {{ $activeUsers['total'] }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $activeUsers['active_percentage'] }}% użytkowników aktywnych dziś
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 px-6 py-3">
            <div class="text-xs text-gray-600 dark:text-gray-400">
                <div class="flex justify-between">
                    <span>Wczoraj: {{ $activeUsers['yesterday'] }}</span>
                    <span>Ten tydzień: {{ $activeUsers['week'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Stats Widget -->
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg rounded-xl border border-gray-200 dark:border-gray-700 widget-enter">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14-7H5a2 2 0 00-2 2v14c0 1.1.9 2 2 2h14a2 2 0 002-2V5a1 1 0 00-1-1zM17 9h.01M17 13h.01"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Kategorie
                        </h3>
                        <div class="flex items-baseline">
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                                {{ $categoriesStats['with_products'] }}
                            </p>
                            <span class="ml-2 text-lg text-gray-500 dark:text-gray-400">
                                / {{ $categoriesStats['total'] }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $categoriesStats['utilization_rate'] }}% wykorzystanych
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 px-6 py-3">
            @if($categoriesStats['empty'] > 0)
                <div class="flex items-center text-xs">
                    <div class="w-2 h-2 bg-yellow-400 rounded-full mr-2"></div>
                    <span class="text-gray-600 dark:text-gray-400">{{ $categoriesStats['empty'] }} kategorii bez produktów</span>
                </div>
            @else
                <div class="flex items-center text-xs">
                    <div class="w-2 h-2 bg-green-400 rounded-full mr-2"></div>
                    <span class="text-gray-600 dark:text-gray-400">Wszystkie kategorie wykorzystane</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Stock Alerts Widget -->
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg rounded-xl border border-gray-200 dark:border-gray-700 widget-enter">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-r from-{{ $stockAlerts['status'] === 'critical' ? 'red' : ($stockAlerts['status'] === 'warning' ? 'yellow' : 'green') }}-500 to-{{ $stockAlerts['status'] === 'critical' ? 'red' : ($stockAlerts['status'] === 'warning' ? 'yellow' : 'green') }}-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Alerty magazynowe
                        </h3>
                        <div class="flex items-baseline">
                            <p class="text-3xl font-bold {{ $stockAlerts['status'] === 'critical' ? 'text-red-600' : ($stockAlerts['status'] === 'warning' ? 'text-yellow-600' : 'text-green-600') }}">
                                {{ $stockAlerts['alerts_count'] }}
                            </p>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            @if($stockAlerts['status'] === 'critical')
                                Wymagana uwaga
                            @elseif($stockAlerts['status'] === 'warning')
                                Monitorowanie
                            @else
                                Stan prawidłowy
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 px-6 py-3">
            <div class="grid grid-cols-3 gap-2 text-xs">
                <div class="text-center">
                    <div class="font-semibold text-red-600">{{ $stockAlerts['out_of_stock'] }}</div>
                    <div class="text-gray-500 dark:text-gray-400">Brak</div>
                </div>
                <div class="text-center">
                    <div class="font-semibold text-yellow-600">{{ $stockAlerts['low_stock'] }}</div>
                    <div class="text-gray-500 dark:text-gray-400">Niski</div>
                </div>
                <div class="text-center">
                    <div class="font-semibold text-blue-600">{{ $stockAlerts['over_stocked'] }}</div>
                    <div class="text-gray-500 dark:text-gray-400">Nadmiar</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Activity Extended Widget (spans full width) -->
    <div class="md:col-span-2 lg:col-span-4 bg-white dark:bg-gray-800 overflow-hidden shadow-lg rounded-xl border border-gray-200 dark:border-gray-700 widget-enter">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Aktywność z dzisiaj
                </h3>
                <div class="flex items-center space-x-2">
                    <button wire:click="toggleTrends" 
                            class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        {{ $showTrends ? 'Ukryj trendy' : 'Pokaż trendy' }}
                    </button>
                    <button wire:click="$refresh" 
                            class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <!-- Products Added Today -->
                <div class="text-center">
                    <div class="flex items-center justify-center w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full mx-auto mb-3">
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $todayActivity['products_added'] }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Produkty dodane</div>
                </div>
                
                <!-- Products Updated Today -->
                <div class="text-center">
                    <div class="flex items-center justify-center w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full mx-auto mb-3">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $todayActivity['products_updated'] }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Produkty zaktualizowane</div>
                </div>
                
                <!-- Users Logged In Today -->
                <div class="text-center">
                    <div class="flex items-center justify-center w-16 h-16 bg-purple-100 dark:bg-purple-900 rounded-full mx-auto mb-3">
                        <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $todayActivity['users_logged_in'] }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Logowania</div>
                </div>
                
                <!-- Sync Events Today -->
                <div class="text-center">
                    <div class="flex items-center justify-center w-16 h-16 bg-orange-100 dark:bg-orange-900 rounded-full mx-auto mb-3">
                        <svg class="w-8 h-8 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $todayActivity['sync_events'] }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Synchronizacje</div>
                </div>
            </div>
        </div>
        
        <!-- Activity Summary Bar -->
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900 dark:to-purple-900 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600 dark:text-gray-300">
                    <strong>Całkowita aktywność:</strong> {{ $todayActivity['total_activity'] }} wydarzeń
                </div>
                <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                    <div class="w-2 h-2 bg-green-400 rounded-full mr-2 pulse-dot"></div>
                    Live monitoring
                </div>
            </div>
        </div>
    </div>
</div>