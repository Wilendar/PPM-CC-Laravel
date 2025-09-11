<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Zarządzanie Motywami</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Twórz, importuj i zarządzaj motywami panelu admin</p>
        </div>
        
        <div class="flex space-x-2">
            <button wire:click="$set('isCreatingNew', true)" class="btn btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Nowy motyw
            </button>
        </div>
    </div>

    <!-- Current Active Theme -->
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 p-6 mb-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"></path>
                    </svg>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold text-blue-800 dark:text-blue-200">
                        {{ $currentTheme->theme_name ?? 'Domyślny Motyw' }}
                    </h4>
                    <p class="text-blue-600 dark:text-blue-300 text-sm">
                        Aktualnie aktywny motyw
                    </p>
                    
                    @if($currentTheme)
                        <div class="flex items-center space-x-4 mt-2 text-xs text-blue-600 dark:text-blue-300">
                            <span>Density: {{ ucfirst($currentTheme->layout_density) }}</span>
                            <span>Sidebar: {{ $currentTheme->sidebar_position === 'left' ? 'Lewo' : 'Prawo' }}</span>
                            <span>Utworzony: {{ $currentTheme->created_at->format('d.m.Y') }}</span>
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="flex items-center space-x-2">
                @if($currentTheme)
                    <button wire:click="exportTheme({{ $currentTheme->id }})" 
                            class="btn btn-sm btn-outline text-blue-700 border-blue-300 hover:bg-blue-100">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                        </svg>
                        Eksportuj
                    </button>
                @endif
                
                <span class="px-3 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-full text-xs font-medium">
                    Aktywny
                </span>
            </div>
        </div>
    </div>

    <!-- Theme Creation Form -->
    @if($isCreatingNew)
        <div class="bg-white dark:bg-gray-800 rounded-lg border shadow-sm p-6 mb-8">
            <div class="flex justify-between items-center mb-6">
                <h4 class="text-md font-medium text-gray-900 dark:text-white">Tworzenie Nowego Motywu</h4>
                <button wire:click="$set('isCreatingNew', false)" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nazwa motywu
                        </label>
                        <input type="text" 
                               wire:model="themeName" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Mój Niestandardowy Motyw">
                    </div>
                    
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Kolor podstawowy
                            </label>
                            <input type="color" 
                                   wire:model="primaryColor" 
                                   class="w-full h-10 border border-gray-300 rounded cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Kolor drugorzędny
                            </label>
                            <input type="color" 
                                   wire:model="secondaryColor" 
                                   class="w-full h-10 border border-gray-300 rounded cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Kolor akcentu
                            </label>
                            <input type="color" 
                                   wire:model="accentColor" 
                                   class="w-full h-10 border border-gray-300 rounded cursor-pointer">
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Gęstość layoutu
                        </label>
                        <select wire:model="layoutDensity" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="compact">Kompaktowy</option>
                            <option value="normal">Normalny</option>
                            <option value="spacious">Przestronny</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Pozycja sidebar
                            </label>
                            <select wire:model="sidebarPosition" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="left">Po lewej</option>
                                <option value="right">Po prawej</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Styl header
                            </label>
                            <select wire:model="headerStyle" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="fixed">Przyklejony</option>
                                <option value="static">Statyczny</option>
                                <option value="floating">Pływający</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-2 mt-6 pt-6 border-t border-gray-200 dark:border-gray-600">
                <button wire:click="$set('isCreatingNew', false)" class="btn btn-secondary">
                    Anuluj
                </button>
                <button wire:click="createNewTheme" class="btn btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Utwórz motyw
                </button>
            </div>
        </div>
    @endif

    <!-- Available Themes Grid -->
    <div class="space-y-6">
        <h4 class="text-md font-medium text-gray-900 dark:text-white">Dostępne Motywy</h4>
        
        @if(!empty($availableThemes))
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($availableThemes as $theme)
                    <div class="bg-white dark:bg-gray-800 rounded-lg border hover:border-blue-400 transition-colors overflow-hidden
                               {{ $currentTheme && $currentTheme->id === $theme['id'] ? 'ring-2 ring-blue-500' : '' }}">
                        
                        <!-- Theme Preview -->
                        <div class="h-32 bg-gradient-to-br p-4 relative"
                             style="background: linear-gradient(135deg, {{ $theme['primary_color'] ?? '#3b82f6' }}, {{ $theme['accent_color'] ?? '#10b981' }})">
                            
                            <!-- Mock Interface -->
                            <div class="flex h-full">
                                <!-- Sidebar -->
                                <div class="w-8 bg-white bg-opacity-20 rounded mr-2 {{ $theme['sidebar_position'] === 'right' ? 'order-2 ml-2 mr-0' : '' }}"></div>
                                
                                <!-- Content Area -->
                                <div class="flex-1 bg-white bg-opacity-10 rounded p-2 space-y-1">
                                    <div class="h-2 bg-white bg-opacity-30 rounded w-3/4"></div>
                                    <div class="h-2 bg-white bg-opacity-20 rounded w-1/2"></div>
                                    <div class="h-2 bg-white bg-opacity-20 rounded w-2/3"></div>
                                </div>
                            </div>
                            
                            <!-- Active Badge -->
                            @if($currentTheme && $currentTheme->id === $theme['id'])
                                <div class="absolute top-2 right-2 bg-green-500 text-white px-2 py-1 rounded-full text-xs font-medium">
                                    Aktywny
                                </div>
                            @endif
                        </div>
                        
                        <!-- Theme Info -->
                        <div class="p-4">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h5 class="font-medium text-gray-900 dark:text-white">
                                        {{ $theme['theme_name'] }}
                                    </h5>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        @if($theme['user_id'] === auth()->id())
                                            Twój motyw
                                        @elseif($theme['is_default'])
                                            Motyw systemowy
                                        @else
                                            Motyw współdzielony
                                        @endif
                                    </p>
                                </div>
                                
                                @if($theme['user_id'] === auth()->id())
                                    <div class="flex items-center space-x-1">
                                        <button wire:click="editTheme({{ $theme['id'] }})" 
                                                class="text-gray-400 hover:text-blue-600 p-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        
                                        @if($currentTheme && $currentTheme->id !== $theme['id'])
                                            <button wire:click="deleteTheme({{ $theme['id'] }})" 
                                                    class="text-gray-400 hover:text-red-600 p-1"
                                                    onclick="return confirm('Czy na pewno usunąć ten motyw?')">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Theme Properties -->
                            <div class="space-y-2 text-xs text-gray-500 mb-4">
                                <div class="flex items-center justify-between">
                                    <span>Layout:</span>
                                    <span>{{ ucfirst($theme['layout_density']) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Sidebar:</span>
                                    <span>{{ $theme['sidebar_position'] === 'left' ? 'Lewo' : 'Prawo' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Header:</span>
                                    <span>{{ ucfirst($theme['header_style']) }}</span>
                                </div>
                            </div>
                            
                            <!-- Color Palette -->
                            <div class="flex space-x-2 mb-4">
                                <div class="w-4 h-4 rounded-full border" 
                                     style="background-color: {{ $theme['primary_color'] }}"
                                     title="Podstawowy: {{ $theme['primary_color'] }}"></div>
                                <div class="w-4 h-4 rounded-full border" 
                                     style="background-color: {{ $theme['secondary_color'] }}"
                                     title="Drugorzędny: {{ $theme['secondary_color'] }}"></div>
                                <div class="w-4 h-4 rounded-full border" 
                                     style="background-color: {{ $theme['accent_color'] }}"
                                     title="Akcent: {{ $theme['accent_color'] }}"></div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex justify-between items-center">
                                <div class="flex space-x-2">
                                    <button wire:click="exportTheme({{ $theme['id'] }})" 
                                            class="text-xs text-blue-600 hover:text-blue-700">
                                        Eksportuj
                                    </button>
                                    
                                    @if($theme['user_id'] === auth()->id())
                                        <button wire:click="cloneTheme({{ $theme['id'] }})" 
                                                class="text-xs text-gray-600 hover:text-gray-700">
                                            Klonuj
                                        </button>
                                    @endif
                                </div>
                                
                                @if($currentTheme && $currentTheme->id !== $theme['id'])
                                    <button wire:click="switchTheme({{ $theme['id'] }})" 
                                            class="btn btn-sm btn-primary">
                                        Aktywuj
                                    </button>
                                @else
                                    <span class="text-xs text-green-600 font-medium">Aktywny</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    Brak dostępnych motywów
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Utwórz swój pierwszy niestandardowy motyw lub zaimportuj istniejący.
                </p>
                <button wire:click="$set('isCreatingNew', true)" class="btn btn-primary">
                    Utwórz nowy motyw
                </button>
            </div>
        @endif
    </div>

    <!-- Import/Export Section -->
    <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-600">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-6">Import i Eksport</h4>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Import Theme -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                        </svg>
                    </div>
                    <div>
                        <h5 class="font-medium text-gray-900 dark:text-white">Import Motywu</h5>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Wczytaj motyw z pliku JSON</p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <input type="file" 
                               wire:model="importFile" 
                               accept=".json"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    
                    @if($importFile)
                        <div class="text-sm text-green-600">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Plik gotowy do importu: {{ $importFile->getClientOriginalName() }}
                        </div>
                    @endif
                    
                    <button wire:click="importTheme" 
                            class="w-full btn btn-primary"
                            {{ !$importFile ? 'disabled' : '' }}>
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                        </svg>
                        Importuj Motyw
                    </button>
                    
                    <div class="text-xs text-gray-500">
                        <p>• Obsługiwane formaty: JSON</p>
                        <p>• Maksymalny rozmiar: 1MB</p>
                        <p>• Motywy zostaną zaimportowane jako nieaktywne</p>
                    </div>
                </div>
            </div>

            <!-- Export Current Theme -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                    </div>
                    <div>
                        <h5 class="font-medium text-gray-900 dark:text-white">Eksport Motywu</h5>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Pobierz aktualny motyw jako plik JSON</p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    @if($currentTheme)
                        <div class="bg-white dark:bg-gray-600 rounded p-3 text-sm">
                            <div class="font-medium text-gray-900 dark:text-white mb-1">
                                {{ $currentTheme->theme_name }}
                            </div>
                            <div class="text-gray-600 dark:text-gray-300">
                                Utworzony: {{ $currentTheme->created_at->format('d.m.Y H:i') }}
                            </div>
                        </div>
                        
                        <button wire:click="exportTheme({{ $currentTheme->id }})" 
                                class="w-full btn btn-secondary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            Eksportuj Aktualny Motyw
                        </button>
                    @else
                        <div class="text-center py-4 text-gray-500">
                            <p>Brak aktywnego motywu do eksportu</p>
                        </div>
                    @endif
                    
                    <div class="text-xs text-gray-500">
                        <p>• Format eksportu: JSON</p>
                        <p>• Zawiera wszystkie ustawienia motywu</p>
                        <p>• Można importować w innych instalacjach</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Theme Sharing -->
    <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-600">
        <div x-data="{ showSharing: false }">
            <button @click="showSharing = !showSharing" 
                    class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900">
                <svg class="w-4 h-4 mr-2 transform transition-transform" 
                     :class="{ 'rotate-90': showSharing }" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                Udostępnianie Motywów
            </button>
            
            <div x-show="showSharing" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 class="mt-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 p-6">
                
                <h5 class="font-medium text-blue-800 dark:text-blue-200 mb-4">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
                    </svg>
                    Funkcje Udostępniania
                </h5>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-blue-700 dark:text-blue-300">
                    <div>
                        <h6 class="font-medium mb-2">Udostępnianie motywów</h6>
                        <ul class="space-y-1">
                            <li>• Możesz udostępnić swoje motywy innym administratorom</li>
                            <li>• Udostępnione motywy są dostępne dla wszystkich</li>
                            <li>• Twórca motywu zachowuje kontrolę nad nim</li>
                            <li>• Motywy można oznaczać jako publiczne lub prywatne</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h6 class="font-medium mb-2">Korzystanie z motywów</h6>
                        <ul class="space-y-1">
                            <li>• Możesz używać motywów udostępnionych przez innych</li>
                            <li>• Klonowanie pozwala na tworzenie własnych wersji</li>
                            <li>• Każdy użytkownik ma własną instancję motywu</li>
                            <li>• Motywy systemowe są dostępne dla wszystkich</li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-4 pt-4 border-t border-blue-200 dark:border-blue-700">
                    <div class="flex justify-between items-center">
                        <span class="text-blue-700 dark:text-blue-300 text-sm">
                            Chcesz udostępnić swój motyw? Skontaktuj się z administratorem systemu.
                        </span>
                        <button class="btn btn-sm btn-outline text-blue-700 border-blue-300 hover:bg-blue-100">
                            Kontakt
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>