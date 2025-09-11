<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Custom CSS</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Dodaj własne style CSS dla zaawansowanej customizacji</p>
        </div>
        
        <div class="flex space-x-2">
            <button wire:click="validateCSS" class="btn btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Waliduj CSS
            </button>
            
            <button wire:click="updateCustomCss" class="btn btn-primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Zapisz CSS
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- CSS Editor -->
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Edytor CSS
                </label>
                
                <div class="css-editor-container bg-gray-900 rounded-lg overflow-hidden border">
                    <div class="flex items-center justify-between bg-gray-800 px-4 py-2 text-sm">
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-red-400 rounded-full"></div>
                            <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>
                            <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                            <span class="text-gray-300 ml-4">custom.css</span>
                        </div>
                        
                        <div class="flex items-center space-x-2 text-gray-400">
                            <span class="text-xs">CSS</span>
                            <button @click="$wire.set('showCSSHelp', !$wire.showCSSHelp)" 
                                    class="hover:text-white">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <textarea wire:model.live.debounce.500ms="customCss"
                              class="w-full h-96 bg-gray-900 text-gray-100 font-mono text-sm p-4 border-none resize-none focus:outline-none focus:ring-0"
                              placeholder="/* Wprowadź swoje style CSS tutaj */

/* Przykład: Customizacja nagłówka */
.admin-header {
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    border-radius: 0 0 10px 10px;
}

/* Przykład: Stylizacja widgetów */
.widget {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(var(--primary-color), 0.1);
}

/* Przykład: Customowe przycisk */
.btn-custom {
    background: var(--primary-color);
    color: white;
    border-radius: 25px;
    padding: 10px 20px;
    transition: all 0.3s ease;
}

.btn-custom:hover {
    background: var(--accent-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}"></textarea>
                </div>
                
                <!-- CSS Stats -->
                <div class="flex items-center justify-between text-sm text-gray-500 mt-2">
                    <div class="flex items-center space-x-4">
                        <span>Linii: {{ substr_count($customCss, "\n") + 1 }}</span>
                        <span>Znaków: {{ strlen($customCss) }}</span>
                        <span>Rozmiar: {{ number_format(strlen($customCss) / 1024, 1) }}KB</span>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        @if($errors->has('customCss'))
                            <span class="text-red-500">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Błąd CSS
                            </span>
                        @else
                            <span class="text-green-500">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Prawidłowy
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- CSS Actions -->
            <div class="flex flex-wrap gap-2">
                <button @click="$wire.insertCSSTemplate('button')" 
                        class="btn btn-sm btn-outline">
                    Dodaj szablon: Przycisk
                </button>
                <button @click="$wire.insertCSSTemplate('widget')" 
                        class="btn btn-sm btn-outline">
                    Dodaj szablon: Widget
                </button>
                <button @click="$wire.insertCSSTemplate('animation')" 
                        class="btn btn-sm btn-outline">
                    Dodaj szablon: Animacja
                </button>
                <button @click="$wire.insertCSSTemplate('responsive')" 
                        class="btn btn-sm btn-outline">
                    Dodaj szablon: Responsive
                </button>
            </div>
        </div>

        <!-- CSS Preview & Help -->
        <div class="space-y-6">
            <!-- Live Preview -->
            <div>
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Podgląd na Żywo</h4>
                
                <div class="bg-white dark:bg-gray-800 rounded-lg border p-6" style="min-height: 300px;">
                    <style id="live-css-preview">
                        {!! $customCss !!}
                    </style>
                    
                    <!-- Preview Elements -->
                    <div class="space-y-4">
                        <div class="admin-header-preview bg-blue-600 text-white p-3 rounded">
                            <h5 class="font-semibold">Header Preview</h5>
                            <p class="text-sm opacity-90">Podgląd stylizowanego nagłówka</p>
                        </div>
                        
                        <div class="widget bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <h6 class="font-medium mb-2">Widget Preview</h6>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Przykładowa zawartość widgetu z zastosowanymi stylami
                            </p>
                        </div>
                        
                        <div class="flex space-x-2">
                            <button class="btn btn-primary">Standardowy</button>
                            <button class="btn btn-custom">Custom</button>
                            <button class="btn btn-secondary">Pomocniczy</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CSS Variables Reference -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <h5 class="font-medium text-gray-900 dark:text-white mb-3">Dostępne Zmienne CSS</h5>
                
                <div class="grid grid-cols-1 gap-2 text-sm font-mono">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">--primary-color</span>
                        <span style="color: var(--primary-color, #3b82f6)">{{ $primaryColor }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">--secondary-color</span>
                        <span style="color: var(--secondary-color, #64748b)">{{ $secondaryColor }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">--accent-color</span>
                        <span style="color: var(--accent-color, #10b981)">{{ $accentColor }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">--layout-density</span>
                        <span class="text-gray-500">{{ $layoutDensity }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">--sidebar-width</span>
                        <span class="text-gray-500">
                            {{ $layoutDensity === 'compact' ? '200px' : ($layoutDensity === 'spacious' ? '280px' : '250px') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- CSS Help Panel -->
            @if($showCSSHelp ?? false)
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                    <h5 class="font-medium text-blue-800 dark:text-blue-200 mb-3">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Pomoc CSS
                    </h5>
                    
                    <div class="text-sm text-blue-700 dark:text-blue-300 space-y-2">
                        <h6 class="font-medium">Główne selektory:</h6>
                        <ul class="space-y-1 ml-4">
                            <li><code>.admin-header</code> - Nagłówek aplikacji</li>
                            <li><code>.admin-sidebar</code> - Panel boczny</li>
                            <li><code>.admin-content</code> - Główna zawartość</li>
                            <li><code>.widget</code> - Widgety dashboard</li>
                            <li><code>.btn</code> - Wszystkie przyciski</li>
                            <li><code>.table</code> - Tabele danych</li>
                        </ul>
                        
                        <h6 class="font-medium mt-3">Wskazówki:</h6>
                        <ul class="space-y-1 ml-4">
                            <li>• Używaj zmiennych CSS dla spójności</li>
                            <li>• Testuj w trybie jasnym i ciemnym</li>
                            <li>• Unikaj !important, chyba że konieczne</li>
                            <li>• Responsywność: używaj media queries</li>
                            <li>• Animacje: transition i transform</li>
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Security Notice -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">
                <h5 class="font-medium text-yellow-800 dark:text-yellow-200 mb-2">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    Bezpieczeństwo CSS
                </h5>
                <div class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
                    <p>System automatycznie filtruje niebezpieczne funkcje CSS:</p>
                    <ul class="ml-4 space-y-1">
                        <li>• <code>expression()</code> - JavaScript w CSS</li>
                        <li>• <code>url()</code> - Zewnętrzne zasoby</li>
                        <li>• <code>@import</code> - Import zewnętrznych plików</li>
                        <li>• Inne potencjalnie niebezpieczne funkcje</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- CSS Examples & Templates -->
    <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-600">
        <div x-data="{ activeExample: null }">
            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-6">Przykłady i Szablony CSS</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($this->getCSSExamples() as $example)
                    <div class="bg-white dark:bg-gray-800 rounded-lg border hover:border-blue-400 transition-colors overflow-hidden">
                        <div class="p-4">
                            <h5 class="font-medium text-gray-900 dark:text-white mb-2">{{ $example['name'] }}</h5>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ $example['description'] }}</p>
                            
                            <div class="flex justify-between items-center">
                                <button @click="activeExample = activeExample === '{{ $example['id'] }}' ? null : '{{ $example['id'] }}'" 
                                        class="text-sm text-blue-600 hover:text-blue-700">
                                    Zobacz kod
                                </button>
                                <button wire:click="insertCSSTemplate('{{ $example['id'] }}')" 
                                        class="btn btn-sm btn-primary">
                                    Wstaw
                                </button>
                            </div>
                        </div>
                        
                        <!-- Code Preview -->
                        <div x-show="activeExample === '{{ $example['id'] }}'" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             class="border-t bg-gray-900 p-4">
                            <pre class="text-gray-300 text-xs overflow-x-auto"><code>{{ $example['code'] }}</code></pre>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Live CSS preview updates
    document.addEventListener('DOMContentLoaded', function() {
        const customCssTextarea = document.querySelector('[wire\\:model\\.live\\.debounce\\.500ms="customCss"]');
        const previewStyle = document.getElementById('live-css-preview');
        
        if (customCssTextarea && previewStyle) {
            customCssTextarea.addEventListener('input', function() {
                // Update preview with slight delay
                setTimeout(() => {
                    previewStyle.textContent = this.value;
                }, 100);
            });
        }
    });
    
    // CSS syntax highlighting (basic)
    function highlightCSS(css) {
        return css
            .replace(/(\/\*[\s\S]*?\*\/)/g, '<span style="color: #6b7280;">$1</span>')
            .replace(/([a-zA-Z-]+)(\s*:\s*)/g, '<span style="color: #3b82f6;">$1</span>$2')
            .replace(/(#[a-fA-F0-9]{3,6})/g, '<span style="color: #10b981;">$1</span>');
    }
    
    // Auto-save CSS changes
    let cssTimeout;
    function autoSaveCSS() {
        clearTimeout(cssTimeout);
        cssTimeout = setTimeout(() => {
            @this.call('autoSaveCSS');
        }, 5000);
    }
</script>
@endpush