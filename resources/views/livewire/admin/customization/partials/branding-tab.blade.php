<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Branding Firmy</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Dostosuj logo, nazwę i kolory firmowe</p>
        </div>
        
        <button wire:click="updateBranding" class="btn btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Zapisz Branding
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Company Logo Section -->
        <div class="space-y-6">
            <div>
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Logo Firmy</h4>
                
                <!-- Current Logo Display -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-4">
                    <div class="flex items-center justify-center h-32 border-2 border-dashed border-gray-300 dark:border-gray-500 rounded-lg">
                        @if($currentTheme && $currentTheme->company_logo)
                            <img src="{{ Storage::url($currentTheme->company_logo) }}" 
                                 alt="Company Logo" 
                                 class="max-h-24 max-w-full object-contain">
                        @else
                            <div class="text-center text-gray-500">
                                <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <p class="text-sm">Brak logo</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Logo Upload -->
                <div class="space-y-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Prześlij nowe logo
                    </label>
                    
                    <div class="flex items-center space-x-4">
                        <input type="file" 
                               wire:model="logoFile" 
                               accept="image/jpeg,image/jpg,image/png,image/svg+xml,image/webp"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    
                    @if($logoFile)
                        <div class="mt-2">
                            <p class="text-sm text-green-600">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Plik gotowy do przesłania: {{ $logoFile->getClientOriginalName() }}
                            </p>
                        </div>
                    @endif
                    
                    <div class="text-xs text-gray-500 space-y-1">
                        <p>• Maksymalny rozmiar: 2MB</p>
                        <p>• Obsługiwane formaty: JPG, PNG, SVG, WEBP</p>
                        <p>• Zalecane wymiary: 200x60px (lub proporcjonalnie)</p>
                        <p>• Logo będzie automatycznie przeskalowane dla różnych miejsc w interfejsie</p>
                    </div>
                </div>
            </div>

            <!-- Logo Positioning -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Pozycjonowanie Logo</h5>
                
                <div class="grid grid-cols-3 gap-2 text-xs">
                    <div class="bg-white dark:bg-gray-600 p-2 rounded text-center">
                        <div class="font-semibold">Sidebar</div>
                        <div class="text-gray-500">40x30px</div>
                    </div>
                    <div class="bg-white dark:bg-gray-600 p-2 rounded text-center">
                        <div class="font-semibold">Header</div>
                        <div class="text-gray-500">120x40px</div>
                    </div>
                    <div class="bg-white dark:bg-gray-600 p-2 rounded text-center">
                        <div class="font-semibold">Login</div>
                        <div class="text-gray-500">200x60px</div>
                    </div>
                </div>
                
                <p class="text-xs text-gray-500 mt-2">
                    Logo zostanie automatycznie dopasowane do każdego miejsca z zachowaniem proporcji.
                </p>
            </div>
        </div>

        <!-- Company Information -->
        <div class="space-y-6">
            <div>
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Informacje Firmy</h4>
                
                <div class="space-y-4">
                    <!-- Company Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nazwa firmy
                        </label>
                        <input type="text" 
                               wire:model.live="companyName" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="PPM Admin">
                        <p class="text-xs text-gray-500 mt-1">
                            Wyświetlana w nagłówku, sidebar i na stronie logowania
                        </p>
                    </div>
                    
                    <!-- Company Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Opis firmy (opcjonalny)
                        </label>
                        <textarea wire:model.live="dashboardSettings.company_description" 
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Zarządzanie produktami dla sklepów PrestaShop..."></textarea>
                        <p class="text-xs text-gray-500 mt-1">
                            Krótki opis wyświetlany na stronie logowania
                        </p>
                    </div>
                </div>
            </div>

            <!-- Brand Colors Palette -->
            <div>
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Paleta Kolorów Firmowych</h4>
                
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <div class="space-y-3">
                        <!-- Current Company Colors -->
                        @if(!empty($companyColors))
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Aktualne kolory firmowe
                                </label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($companyColors as $index => $color)
                                        <div class="flex items-center space-x-2 bg-white dark:bg-gray-600 rounded-lg p-2 group">
                                            <div class="w-8 h-8 rounded-lg border-2 border-gray-200" 
                                                 style="background-color: {{ $color }}"></div>
                                            <div>
                                                <span class="text-sm font-mono">{{ $color }}</span>
                                                <div class="text-xs text-gray-500">
                                                    {{ $this->getColorName($color) }}
                                                </div>
                                            </div>
                                            <button wire:click="removeCompanyColor({{ $index }})" 
                                                    class="text-red-500 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        
                        <!-- Add New Color -->
                        <div x-data="{ newColor: '#' + Math.floor(Math.random()*16777215).toString(16) }">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Dodaj nowy kolor firmowy
                            </label>
                            <div class="flex items-center space-x-3">
                                <input type="color" 
                                       x-model="newColor"
                                       class="color-picker w-12 h-10">
                                <input type="text" 
                                       x-model="newColor"
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                                       placeholder="#000000">
                                <button @click="$wire.addCompanyColor(newColor); newColor = '#' + Math.floor(Math.random()*16777215).toString(16)"
                                        class="btn btn-secondary px-3 py-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Brand Application Examples -->
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <h5 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Zastosowanie brandingu
                </h5>
                <div class="text-xs text-blue-700 dark:text-blue-300 space-y-1">
                    <p>• Logo i nazwa firmy będą widoczne w nagłówku aplikacji</p>
                    <p>• Kolory firmowe zostaną zastosowane do elementów UI</p>
                    <p>• Branding będzie widoczny na stronie logowania</p>
                    <p>• Eksportowane raporty będą zawierać branding firmy</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Branding Preview -->
    <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-600">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-6">Podgląd Brandingu</h4>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Header Preview -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-4 py-3">
                    <div class="flex items-center space-x-3">
                        @if($currentTheme && $currentTheme->company_logo)
                            <img src="{{ Storage::url($currentTheme->company_logo) }}" 
                                 alt="Logo" 
                                 class="h-8 w-auto">
                        @else
                            <div class="w-8 h-8 bg-white bg-opacity-20 rounded flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                        @endif
                        <span class="text-white font-semibold">{{ $companyName ?: 'PPM Admin' }}</span>
                    </div>
                </div>
                <div class="p-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">Podgląd nagłówka aplikacji</div>
                </div>
            </div>

            <!-- Login Page Preview -->
            <div class="bg-gray-100 dark:bg-gray-900 rounded-lg border overflow-hidden">
                <div class="p-6 text-center">
                    @if($currentTheme && $currentTheme->company_logo)
                        <img src="{{ Storage::url($currentTheme->company_logo) }}" 
                             alt="Logo" 
                             class="h-16 w-auto mx-auto mb-4">
                    @else
                        <div class="w-16 h-16 bg-gray-300 rounded-lg mx-auto mb-4 flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                    @endif
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        {{ $companyName ?: 'PPM Admin' }}
                    </h3>
                    @if($dashboardSettings['company_description'] ?? false)
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            {{ $dashboardSettings['company_description'] }}
                        </p>
                    @endif
                    <div class="bg-white dark:bg-gray-800 rounded p-3">
                        <div class="text-xs text-gray-500">Podgląd strony logowania</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Color Palette Display -->
        @if(!empty($companyColors))
            <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Paleta kolorów w użyciu:</h5>
                <div class="flex space-x-2">
                    @foreach($companyColors as $color)
                        <div class="flex flex-col items-center">
                            <div class="w-12 h-12 rounded-lg border-2 border-white shadow-sm" 
                                 style="background-color: {{ $color }}"></div>
                            <span class="text-xs font-mono mt-1">{{ $color }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Branding Guidelines -->
    <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-600">
        <div x-data="{ showGuidelines: false }">
            <button @click="showGuidelines = !showGuidelines" 
                    class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900">
                <svg class="w-4 h-4 mr-2 transform transition-transform" 
                     :class="{ 'rotate-90': showGuidelines }" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                Wytyczne Brandingu
            </button>
            
            <div x-show="showGuidelines" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 class="mt-4 bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    <div>
                        <h5 class="font-medium text-gray-900 dark:text-white mb-2">Logo</h5>
                        <ul class="text-gray-600 dark:text-gray-400 space-y-1">
                            <li>• Preferowany format: SVG (skalowalny)</li>
                            <li>• PNG z przezroczystym tłem jako alternatywa</li>
                            <li>• Minimalna szerokość: 120px</li>
                            <li>• Maksymalna wysokość: 60px</li>
                            <li>• Zachowaj proporcje oryginalnego logo</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h5 class="font-medium text-gray-900 dark:text-white mb-2">Kolory</h5>
                        <ul class="text-gray-600 dark:text-gray-400 space-y-1">
                            <li>• Maksymalnie 5-6 kolorów w palecie</li>
                            <li>• Uwzględnij kontrast dla dostępności</li>
                            <li>• Jedna dominująca barwa + uzupełniające</li>
                            <li>• Testuj w trybie jasnym i ciemnym</li>
                            <li>• Zachowaj spójność w całej aplikacji</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Color name helper function
    window.getColorName = function(hex) {
        const colors = {
            '#FF0000': 'Czerwony',
            '#00FF00': 'Zielony', 
            '#0000FF': 'Niebieski',
            '#FFFF00': 'Żółty',
            '#FF00FF': 'Magenta',
            '#00FFFF': 'Cyjan',
            '#000000': 'Czarny',
            '#FFFFFF': 'Biały',
            '#808080': 'Szary'
        };
        
        return colors[hex.toUpperCase()] || 'Niestandardowy';
    };
</script>
@endpush