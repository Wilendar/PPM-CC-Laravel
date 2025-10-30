<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h3 class="text-lg font-medium text-white">Ustawienia Layoutu</h3>
            <p class="text-sm text-gray-400">Dostosuj układ i gęstość interfejsu</p>
        </div>
        
        <button wire:click="updateLayout" class="btn btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Zapisz Layout
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Layout Density -->
        <div class="space-y-4">
            <h4 class="text-md font-medium text-white">Gęstość Layoutu</h4>
            
            <div class="space-y-3">
                <!-- Compact -->
                <label class="flex items-start space-x-3 p-4 border-2 rounded-lg cursor-pointer transition-colors
                             {{ $layoutDensity === 'compact' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 hover:border-gray-300' }}">
                    <input type="radio" 
                           wire:model.live="layoutDensity" 
                           value="compact" 
                           class="mt-1">
                    <div class="flex-1">
                        <div class="font-medium text-white">Kompaktowy</div>
                        <div class="text-sm text-gray-400">
                            Mniejsze odstępy, węższa sidebar (200px), niższy header (50px)
                        </div>
                        <div class="mt-2 text-xs text-gray-500">
                            Idealne dla małych ekranów i użytkowników preferujących więcej treści
                        </div>
                    </div>
                    <!-- Visual Preview -->
                    <div class="w-16 h-12 bg-gray-100 rounded border overflow-hidden">
                        <div class="flex h-full">
                            <div class="w-1/3 bg-gray-300 border-r"></div>
                            <div class="flex-1 p-1 space-y-1">
                                <div class="h-1 bg-gray-400 rounded"></div>
                                <div class="h-1 bg-gray-300 rounded"></div>
                                <div class="h-1 bg-gray-300 rounded"></div>
                            </div>
                        </div>
                    </div>
                </label>

                <!-- Normal -->
                <label class="flex items-start space-x-3 p-4 border-2 rounded-lg cursor-pointer transition-colors
                             {{ $layoutDensity === 'normal' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 hover:border-gray-300' }}">
                    <input type="radio" 
                           wire:model.live="layoutDensity" 
                           value="normal" 
                           class="mt-1">
                    <div class="flex-1">
                        <div class="font-medium text-white">Normalny</div>
                        <div class="text-sm text-gray-400">
                            Standardowe odstępy, sidebar (250px), header (60px)
                        </div>
                        <div class="mt-2 text-xs text-gray-500">
                            Zbalansowany layout dla większości użytkowników
                        </div>
                    </div>
                    <!-- Visual Preview -->
                    <div class="w-16 h-12 bg-gray-100 rounded border overflow-hidden">
                        <div class="flex h-full">
                            <div class="w-2/5 bg-gray-300 border-r"></div>
                            <div class="flex-1 p-1 space-y-1">
                                <div class="h-1.5 bg-gray-400 rounded"></div>
                                <div class="h-1.5 bg-gray-300 rounded"></div>
                                <div class="h-1.5 bg-gray-300 rounded"></div>
                            </div>
                        </div>
                    </div>
                </label>

                <!-- Spacious -->
                <label class="flex items-start space-x-3 p-4 border-2 rounded-lg cursor-pointer transition-colors
                             {{ $layoutDensity === 'spacious' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 hover:border-gray-300' }}">
                    <input type="radio" 
                           wire:model.live="layoutDensity" 
                           value="spacious" 
                           class="mt-1">
                    <div class="flex-1">
                        <div class="font-medium text-white">Przestronny</div>
                        <div class="text-sm text-gray-400">
                            Duże odstępy, szersza sidebar (280px), wyższy header (70px)
                        </div>
                        <div class="mt-2 text-xs text-gray-500">
                            Komfortowe czytanie dla użytkowników na dużych ekranach
                        </div>
                    </div>
                    <!-- Visual Preview -->
                    <div class="w-16 h-12 bg-gray-100 rounded border overflow-hidden">
                        <div class="flex h-full">
                            <div class="w-1/2 bg-gray-300 border-r"></div>
                            <div class="flex-1 p-2 space-y-1">
                                <div class="h-2 bg-gray-400 rounded"></div>
                                <div class="h-2 bg-gray-300 rounded"></div>
                            </div>
                        </div>
                    </div>
                </label>
            </div>
        </div>

        <!-- Sidebar & Header Configuration -->
        <div class="space-y-6">
            <!-- Sidebar Position -->
            <div class="space-y-4">
                <h4 class="text-md font-medium text-white">Pozycja Sidebar</h4>
                
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex flex-col items-center p-4 border-2 rounded-lg cursor-pointer transition-colors
                                 {{ $sidebarPosition === 'left' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 hover:border-gray-300' }}">
                        <input type="radio" 
                               wire:model.live="sidebarPosition" 
                               value="left" 
                               class="sr-only">
                        <div class="w-12 h-8 bg-gray-100 rounded border overflow-hidden mb-2">
                            <div class="flex h-full">
                                <div class="w-1/3 bg-blue-400"></div>
                                <div class="flex-1 bg-gray-800"></div>
                            </div>
                        </div>
                        <span class="text-sm font-medium">Po lewej</span>
                    </label>

                    <label class="flex flex-col items-center p-4 border-2 rounded-lg cursor-pointer transition-colors
                                 {{ $sidebarPosition === 'right' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 hover:border-gray-300' }}">
                        <input type="radio" 
                               wire:model.live="sidebarPosition" 
                               value="right" 
                               class="sr-only">
                        <div class="w-12 h-8 bg-gray-100 rounded border overflow-hidden mb-2">
                            <div class="flex h-full">
                                <div class="flex-1 bg-gray-800"></div>
                                <div class="w-1/3 bg-blue-400"></div>
                            </div>
                        </div>
                        <span class="text-sm font-medium">Po prawej</span>
                    </label>
                </div>
            </div>

            <!-- Header Style -->
            <div class="space-y-4">
                <h4 class="text-md font-medium text-white">Styl Nagłówka</h4>
                
                <div class="space-y-3">
                    <label class="flex items-center space-x-3 p-3 border-2 rounded-lg cursor-pointer transition-colors
                                 {{ $headerStyle === 'fixed' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 hover:border-gray-300' }}">
                        <input type="radio" 
                               wire:model.live="headerStyle" 
                               value="fixed">
                        <div>
                            <div class="font-medium">Przyklejony</div>
                            <div class="text-sm text-gray-400">
                                Header zawsze widoczny na górze
                            </div>
                        </div>
                    </label>

                    <label class="flex items-center space-x-3 p-3 border-2 rounded-lg cursor-pointer transition-colors
                                 {{ $headerStyle === 'static' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 hover:border-gray-300' }}">
                        <input type="radio" 
                               wire:model.live="headerStyle" 
                               value="static">
                        <div>
                            <div class="font-medium">Statyczny</div>
                            <div class="text-sm text-gray-400">
                                Header przewija się z zawartością
                            </div>
                        </div>
                    </label>

                    <label class="flex items-center space-x-3 p-3 border-2 rounded-lg cursor-pointer transition-colors
                                 {{ $headerStyle === 'floating' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 hover:border-gray-300' }}">
                        <input type="radio" 
                               wire:model.live="headerStyle" 
                               value="floating">
                        <div>
                            <div class="font-medium">Pływający</div>
                            <div class="text-sm text-gray-400">
                                Header z lekkim cieniem
                            </div>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Settings -->
    <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-600">
        <h4 class="text-md font-medium text-white mb-6">Ustawienia Dashboard</h4>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Auto Refresh -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <label class="flex items-center justify-between">
                    <div>
                        <div class="font-medium text-white">Auto-odświeżanie</div>
                        <div class="text-sm text-gray-400">
                            Automatyczne odświeżanie danych dashboard
                        </div>
                    </div>
                    <input type="checkbox" 
                           wire:model.live="dashboardSettings.auto_refresh"
                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                </label>
                
                @if($dashboardSettings['auto_refresh'] ?? false)
                    <div class="mt-3">
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Interwał odświeżania (sekundy)
                        </label>
                        <select wire:model.live="dashboardSettings.refresh_interval" 
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="30">30 sekund</option>
                            <option value="60">1 minuta</option>
                            <option value="300">5 minut</option>
                            <option value="600">10 minut</option>
                        </select>
                    </div>
                @endif
            </div>

            <!-- Show Tooltips -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <label class="flex items-center justify-between">
                    <div>
                        <div class="font-medium text-white">Podpowiedzi</div>
                        <div class="text-sm text-gray-400">
                            Wyświetlaj pomocne podpowiedzi
                        </div>
                    </div>
                    <input type="checkbox" 
                           wire:model.live="dashboardSettings.show_tooltips"
                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                </label>
            </div>

            <!-- Compact Widgets -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <label class="flex items-center justify-between">
                    <div>
                        <div class="font-medium text-white">Kompaktowe widgety</div>
                        <div class="text-sm text-gray-400">
                            Mniejsze widgety na dashboard
                        </div>
                    </div>
                    <input type="checkbox" 
                           wire:model.live="dashboardSettings.compact_widgets"
                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                </label>
            </div>

            <!-- Show Gridlines -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <label class="flex items-center justify-between">
                    <div>
                        <div class="font-medium text-white">Siatka układu</div>
                        <div class="text-sm text-gray-400">
                            Pokaż siatkę przy układaniu widgetów
                        </div>
                    </div>
                    <input type="checkbox" 
                           wire:model.live="dashboardSettings.show_gridlines"
                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                </label>
            </div>

            <!-- Enable Animations -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <label class="flex items-center justify-between">
                    <div>
                        <div class="font-medium text-white">Animacje</div>
                        <div class="text-sm text-gray-400">
                            Płynne przejścia i animacje
                        </div>
                    </div>
                    <input type="checkbox" 
                           wire:model.live="dashboardSettings.enable_animations"
                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                </label>
            </div>

            <!-- Date Format -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <label class="block text-sm font-medium text-gray-300 mb-2">
                    Format daty i czasu
                </label>
                <select wire:model.live="dashboardSettings.date_format" 
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="d/m/Y H:i">31/12/2024 15:30</option>
                    <option value="Y-m-d H:i">2024-12-31 15:30</option>
                    <option value="d.m.Y H:i">31.12.2024 15:30</option>
                    <option value="j M Y H:i">31 Dec 2024 15:30</option>
                </select>
            </div>
        </div>

        <!-- Save Dashboard Settings -->
        <div class="mt-6 flex justify-end">
            <button wire:click="updateDashboardSettings" class="btn btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Zapisz Ustawienia Dashboard
            </button>
        </div>
    </div>

    <!-- Layout Preview -->
    <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-600">
        <h4 class="text-md font-medium text-white mb-4">Podgląd Layoutu</h4>
        
        <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
            <div class="preview-container" style="height: 200px; position: relative;">
                <!-- Header Preview -->
                <div class="absolute top-0 left-0 right-0 h-8 bg-blue-500 rounded-t
                           {{ $headerStyle === 'floating' ? 'shadow-lg' : '' }}
                           {{ $layoutDensity === 'compact' ? 'h-6' : ($layoutDensity === 'spacious' ? 'h-10' : 'h-8') }}">
                </div>
                
                <!-- Sidebar Preview -->
                <div class="absolute top-8 {{ $sidebarPosition === 'right' ? 'right-0' : 'left-0' }} bottom-0
                           {{ $layoutDensity === 'compact' ? 'w-16' : ($layoutDensity === 'spacious' ? 'w-24' : 'w-20') }}
                           bg-gray-300 dark:bg-gray-600 rounded-bl{{ $sidebarPosition === 'left' ? '' : '-none' }}">
                </div>
                
                <!-- Content Preview -->
                <div class="absolute top-8 bottom-0 {{ $sidebarPosition === 'right' ? 'left-0 right-24' : 'left-20 right-0' }}
                           bg-gray-700 rounded-br{{ $sidebarPosition === 'right' ? '' : '-none' }} p-2">
                    <div class="space-y-2">
                        <div class="h-2 bg-gray-400 rounded" style="width: 60%"></div>
                        <div class="h-2 bg-gray-300 rounded" style="width: 80%"></div>
                        <div class="h-2 bg-gray-300 rounded" style="width: 40%"></div>
                    </div>
                </div>
            </div>
            
            <p class="text-sm text-gray-400 mt-2 text-center">
                Layout: {{ ucfirst($layoutDensity) }} | 
                Sidebar: {{ $sidebarPosition === 'left' ? 'Po lewej' : 'Po prawej' }} |
                Header: {{ ucfirst($headerStyle) }}
            </p>
        </div>
    </div>
</div>