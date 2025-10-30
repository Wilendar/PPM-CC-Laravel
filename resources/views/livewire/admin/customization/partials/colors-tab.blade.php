<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h3 class="text-lg font-medium text-white">Kolory Motywu</h3>
            <p class="text-sm text-gray-400">Dostosuj schemat kolorów panelu administracyjnego</p>
        </div>
        
        <button wire:click="updateColors" class="btn btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Zapisz Kolory
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Primary Color -->
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <label class="block text-sm font-medium text-gray-300 mb-3">
                Kolor Podstawowy
            </label>
            
            <div class="flex items-center space-x-3">
                <input type="color" 
                       wire:model.live="primaryColor" 
                       class="color-picker"
                       title="Wybierz kolor podstawowy">
                       
                <div class="flex-1">
                    <input type="text" 
                           wire:model.live="primaryColor" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                           placeholder="#3b82f6">
                           
                    <p class="text-xs text-gray-500 mt-1">
                        Używany dla przycisków, linków i elementów interaktywnych
                    </p>
                </div>
            </div>
            
            <!-- Preview -->
            <div class="mt-4 p-3 rounded" :style="`background-color: ${$wire.primaryColor}`">
                <div class="text-white text-sm font-medium">Przykład elementu</div>
            </div>
        </div>

        <!-- Secondary Color -->
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <label class="block text-sm font-medium text-gray-300 mb-3">
                Kolor Drugorzędny
            </label>
            
            <div class="flex items-center space-x-3">
                <input type="color" 
                       wire:model.live="secondaryColor" 
                       class="color-picker"
                       title="Wybierz kolor drugorzędny">
                       
                <div class="flex-1">
                    <input type="text" 
                           wire:model.live="secondaryColor" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                           placeholder="#64748b">
                           
                    <p class="text-xs text-gray-500 mt-1">
                        Używany dla tekstu pomocniczego i elementów tła
                    </p>
                </div>
            </div>
            
            <!-- Preview -->
            <div class="mt-4 p-3 rounded border-2" :style="`border-color: ${$wire.secondaryColor}; color: ${$wire.secondaryColor}`">
                <div class="text-sm font-medium">Przykład elementu</div>
            </div>
        </div>

        <!-- Accent Color -->
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <label class="block text-sm font-medium text-gray-300 mb-3">
                Kolor Akcentu
            </label>
            
            <div class="flex items-center space-x-3">
                <input type="color" 
                       wire:model.live="accentColor" 
                       class="color-picker"
                       title="Wybierz kolor akcentu">
                       
                <div class="flex-1">
                    <input type="text" 
                           wire:model.live="accentColor" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                           placeholder="#10b981">
                           
                    <p class="text-xs text-gray-500 mt-1">
                        Używany dla powiadomień sukcesu i wyróżnień
                    </p>
                </div>
            </div>
            
            <!-- Preview -->
            <div class="mt-4 p-3 rounded" :style="`background-color: ${$wire.accentColor}`">
                <div class="text-white text-sm font-medium">Przykład elementu</div>
            </div>
        </div>
    </div>

    <!-- Color Palette Presets -->
    <div class="mt-8">
        <h4 class="text-md font-medium text-white mb-4">Gotowe Palety Kolorów</h4>
        
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <!-- Blue Palette -->
            <div class="color-palette-card" 
                 wire:click="$set('primaryColor', '#3b82f6'); $set('secondaryColor', '#64748b'); $set('accentColor', '#10b981')"
                 title="Niebieski (Domyślny)">
                <div class="flex h-16 rounded-lg overflow-hidden cursor-pointer hover:ring-2 hover:ring-blue-500">
                    <div class="flex-1" style="background-color: #3b82f6"></div>
                    <div class="flex-1" style="background-color: #64748b"></div>
                    <div class="flex-1" style="background-color: #10b981"></div>
                </div>
                <p class="text-xs text-center mt-2">Niebieski</p>
            </div>

            <!-- Green Palette -->
            <div class="color-palette-card" 
                 wire:click="$set('primaryColor', '#059669'); $set('secondaryColor', '#6b7280'); $set('accentColor', '#f59e0b')"
                 title="Zielony">
                <div class="flex h-16 rounded-lg overflow-hidden cursor-pointer hover:ring-2 hover:ring-green-500">
                    <div class="flex-1" style="background-color: #059669"></div>
                    <div class="flex-1" style="background-color: #6b7280"></div>
                    <div class="flex-1" style="background-color: #f59e0b"></div>
                </div>
                <p class="text-xs text-center mt-2">Zielony</p>
            </div>

            <!-- Purple Palette -->
            <div class="color-palette-card" 
                 wire:click="$set('primaryColor', '#7c3aed'); $set('secondaryColor', '#6b7280'); $set('accentColor', '#ec4899')"
                 title="Fioletowy">
                <div class="flex h-16 rounded-lg overflow-hidden cursor-pointer hover:ring-2 hover:ring-purple-500">
                    <div class="flex-1" style="background-color: #7c3aed"></div>
                    <div class="flex-1" style="background-color: #6b7280"></div>
                    <div class="flex-1" style="background-color: #ec4899"></div>
                </div>
                <p class="text-xs text-center mt-2">Fioletowy</p>
            </div>

            <!-- Orange Palette -->
            <div class="color-palette-card" 
                 wire:click="$set('primaryColor', '#ea580c'); $set('secondaryColor', '#6b7280'); $set('accentColor', '#06b6d4')"
                 title="Pomarańczowy">
                <div class="flex h-16 rounded-lg overflow-hidden cursor-pointer hover:ring-2 hover:ring-orange-500">
                    <div class="flex-1" style="background-color: #ea580c"></div>
                    <div class="flex-1" style="background-color: #6b7280"></div>
                    <div class="flex-1" style="background-color: #06b6d4"></div>
                </div>
                <p class="text-xs text-center mt-2">Pomarańczowy</p>
            </div>

            <!-- Red Palette -->
            <div class="color-palette-card" 
                 wire:click="$set('primaryColor', '#dc2626'); $set('secondaryColor', '#6b7280'); $set('accentColor', '#16a34a')"
                 title="Czerwony">
                <div class="flex h-16 rounded-lg overflow-hidden cursor-pointer hover:ring-2 hover:ring-red-500">
                    <div class="flex-1" style="background-color: #dc2626"></div>
                    <div class="flex-1" style="background-color: #6b7280"></div>
                    <div class="flex-1" style="background-color: #16a34a"></div>
                </div>
                <p class="text-xs text-center mt-2">Czerwony</p>
            </div>

            <!-- Dark Palette -->
            <div class="color-palette-card" 
                 wire:click="$set('primaryColor', '#1f2937'); $set('secondaryColor', '#4b5563'); $set('accentColor', '#fbbf24')"
                 title="Ciemny">
                <div class="flex h-16 rounded-lg overflow-hidden cursor-pointer hover:ring-2 hover:ring-gray-500">
                    <div class="flex-1" style="background-color: #1f2937"></div>
                    <div class="flex-1" style="background-color: #4b5563"></div>
                    <div class="flex-1" style="background-color: #fbbf24"></div>
                </div>
                <p class="text-xs text-center mt-2">Ciemny</p>
            </div>
        </div>
    </div>

    <!-- Advanced Color Options -->
    <div class="mt-8" x-data="{ showAdvanced: false }">
        <button @click="showAdvanced = !showAdvanced" 
                class="flex items-center text-sm font-medium text-gray-300 hover:text-white">
            <svg class="w-4 h-4 mr-2 transform transition-transform" 
                 :class="{ 'rotate-90': showAdvanced }" 
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            Zaawansowane Opcje Kolorów
        </button>
        
        <div x-show="showAdvanced" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             class="mt-4 space-y-4">
            
            <!-- Company Colors -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <h5 class="text-sm font-medium text-white mb-3">Kolory Firmowe</h5>
                <p class="text-xs text-gray-400 mb-4">
                    Dodaj kolory firmowe dla spójnego brandingu
                </p>
                
                <div class="flex flex-wrap gap-2 mb-3">
                    @foreach($companyColors as $index => $color)
                        <div class="flex items-center space-x-2 bg-gray-800 dark:bg-gray-600 rounded-lg p-2">
                            <div class="w-6 h-6 rounded border" style="background-color: {{ $color }}"></div>
                            <span class="text-xs font-mono">{{ $color }}</span>
                            <button wire:click="removeCompanyColor({{ $index }})" 
                                    class="text-red-500 hover:text-red-700 text-xs">
                                ×
                            </button>
                        </div>
                    @endforeach
                </div>
                
                <div class="flex items-center space-x-2">
                    <input type="color" 
                           x-data="{ color: '#000000' }"
                           x-model="color"
                           @change="$wire.addCompanyColor(color)"
                           class="color-picker w-8 h-8">
                    <span class="text-xs text-gray-500">Kliknij, aby dodać nowy kolor firmowy</span>
                </div>
            </div>

            <!-- Color Accessibility -->
            <div class="bg-yellow-50 dark:bg-yellow-900 rounded-lg p-4">
                <h5 class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-2">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Dostępność Kolorów
                </h5>
                <p class="text-xs text-yellow-700 dark:text-yellow-300">
                    Upewnij się, że wybrane kolory zapewniają wystarczający kontrast dla wszystkich użytkowników.
                    Zalecany stosunek kontrastu to minimum 4.5:1 dla normalnego tekstu.
                </p>
            </div>
        </div>
    </div>
</div>