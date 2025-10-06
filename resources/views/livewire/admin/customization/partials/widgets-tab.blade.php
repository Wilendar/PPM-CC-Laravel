<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Zarządzanie Widgetami</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Dostosuj layout i konfigurację widgetów dashboard</p>
        </div>
        
        <div class="flex space-x-2">
            <button wire:click="resetWidgetLayout" 
                    class="btn btn-secondary"
                    onclick="return confirm('Czy zresetować układ widgetów do domyślnego?')">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Reset
            </button>
        </div>
    </div>

    <!-- Widget Layout Editor -->
    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-8">
        <div class="flex justify-between items-center mb-4">
            <h4 class="text-md font-medium text-gray-900 dark:text-white">Edytor Układu Dashboard</h4>
            
            <div class="flex items-center space-x-4">
                <label class="flex items-center space-x-2">
                    <input type="checkbox" 
                           wire:model.live="dashboardSettings.show_gridlines"
                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Pokaż siatkę</span>
                </label>
                
                <select wire:model.live="widgetLayout.grid.columns" 
                        class="px-3 py-1 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="12">12 kolumn</option>
                    <option value="10">10 kolumn</option>
                    <option value="8">8 kolumn</option>
                    <option value="6">6 kolumn</option>
                </select>
            </div>
        </div>
        
        <!-- Grid Layout Preview -->
        <div class="widget-grid-container bg-white dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-500 p-4"
             x-data="widgetGrid()"
             x-init="initGrid(@this.widgetLayout)"
             :class="{ 'show-grid': $wire.dashboardSettings.show_gridlines }">
            
            <!-- Grid Lines (shown when enabled) -->
            <div class="grid-lines absolute inset-0 pointer-events-none opacity-20"
                 :class="{ 'opacity-100': $wire.dashboardSettings.show_gridlines }">
                @for($i = 1; $i <= 12; $i++)
                    <div class="grid-line col-{{ $i }}"></div>
                @endfor
            </div>
            
            <!-- Draggable Widgets -->
            <div class="widgets-container relative" style="min-height: 400px;">
                @if(!empty($widgetLayout['widgets']))
                    @foreach($widgetLayout['widgets'] as $index => $widget)
                        <div class="widget-item draggable bg-white dark:bg-gray-700 rounded-lg shadow border-2 border-transparent hover:border-blue-400 cursor-move"
                             data-widget-id="{{ $widget['id'] }}"
                             data-index="{{ $index }}"
                             style="grid-column: {{ $widget['x'] + 1 }} / span {{ $widget['w'] }}; 
                                    grid-row: {{ $widget['y'] + 1 }} / span {{ $widget['h'] }};">
                            
                            <!-- Widget Header -->
                            <div class="widget-header bg-gray-50 dark:bg-gray-600 px-3 py-2 rounded-t-lg flex justify-between items-center">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"></path>
                                    </svg>
                                    <span class="text-sm font-medium">{{ $this->getWidgetName($widget['id']) }}</span>
                                </div>
                                
                                <div class="flex items-center space-x-1">
                                    <!-- Resize Handle -->
                                    <div class="resize-handle cursor-se-resize">
                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21l4-4m0 0l4 4m-4-4v-8"></path>
                                        </svg>
                                    </div>
                                    
                                    <!-- Remove Widget -->
                                    <button wire:click="removeWidget({{ $index }})" 
                                            class="text-red-500 hover:text-red-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Widget Content Preview -->
                            <div class="widget-content p-3">
                                @include('livewire.admin.customization.partials.widget-preview', ['widgetId' => $widget['id']])
                            </div>
                            
                            <!-- Widget Info -->
                            <div class="widget-info text-xs text-gray-500 px-3 py-1 bg-gray-50 dark:bg-gray-600 rounded-b-lg">
                                Pozycja: {{ $widget['x'] }},{{ $widget['y'] }} | 
                                Rozmiar: {{ $widget['w'] }}×{{ $widget['h'] }}
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <!-- Available Widgets -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Widget Library -->
        <div>
            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Biblioteka Widgetów</h4>
            
            <div class="space-y-3">
                @foreach($this->getAvailableWidgets() as $widgetId => $widget)
                    <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 rounded-lg border hover:border-blue-400 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                                {!! $widget['icon'] !!}
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $widget['name'] }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ $widget['description'] }}</div>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-gray-500">{{ $widget['size'] }}</span>
                            <button wire:click="addWidget('{{ $widgetId }}')" 
                                    class="btn btn-sm btn-secondary">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Dodaj
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- Custom Widget Creator -->
            <div class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                <h5 class="font-medium text-yellow-800 dark:text-yellow-200 mb-2">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Tworzenie Własnych Widgetów
                </h5>
                <p class="text-sm text-yellow-700 dark:text-yellow-300 mb-3">
                    Możesz utworzyć własne widgety używając Custom CSS i HTML. 
                    Skontaktuj się z administratorem systemu w celu dodania nowych typów widgetów.
                </p>
                <button class="btn btn-sm btn-outline text-yellow-700 border-yellow-300 hover:bg-yellow-100">
                    Dowiedz się więcej
                </button>
            </div>
        </div>

        <!-- Widget Configuration -->
        <div>
            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Konfiguracja Widgetów</h4>
            
            <div class="space-y-4">
                <!-- Widget Refresh Settings -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border p-4">
                    <h5 class="font-medium text-gray-900 dark:text-white mb-3">Ustawienia Odświeżania</h5>
                    
                    <div class="space-y-3">
                        <label class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Auto-odświeżanie widgetów</span>
                            <input type="checkbox" 
                                   wire:model.live="dashboardSettings.auto_refresh"
                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        </label>
                        
                        @if($dashboardSettings['auto_refresh'] ?? false)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Interwał odświeżania
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
                </div>

                <!-- Widget Appearance -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border p-4">
                    <h5 class="font-medium text-gray-900 dark:text-white mb-3">Wygląd Widgetów</h5>
                    
                    <div class="space-y-3">
                        <label class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Kompaktowe widgety</span>
                            <input type="checkbox" 
                                   wire:model.live="dashboardSettings.compact_widgets"
                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        </label>
                        
                        <label class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Animacje przejść</span>
                            <input type="checkbox" 
                                   wire:model.live="dashboardSettings.enable_animations"
                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        </label>
                        
                        <label class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Podpowiedzi (tooltips)</span>
                            <input type="checkbox" 
                                   wire:model.live="dashboardSettings.show_tooltips"
                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        </label>
                    </div>
                </div>

                <!-- Widget Data Sources -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border p-4">
                    <h5 class="font-medium text-gray-900 dark:text-white mb-3">Źródła Danych</h5>
                    
                    <div class="space-y-2 text-sm">
                        @foreach($this->getDataSources() as $source)
                            <div class="flex items-center justify-between py-2">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 rounded-full {{ $source['status'] === 'active' ? 'bg-green-400' : 'bg-red-400' }}"></div>
                                    <span class="text-gray-700 dark:text-gray-300">{{ $source['name'] }}</span>
                                </div>
                                <span class="text-xs text-gray-500">{{ $source['latency'] }}ms</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Widget Sharing -->
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 p-4">
                    <h5 class="font-medium text-blue-800 dark:text-blue-200 mb-2">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
                        </svg>
                        Udostępnianie Układów
                    </h5>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mb-3">
                        Możesz udostępnić swój układ widgetów innym administratorom lub zapisać jako szablon.
                    </p>
                    <div class="flex space-x-2">
                        <button wire:click="shareWidgetLayout" class="btn btn-sm btn-outline text-blue-700 border-blue-300 hover:bg-blue-100">
                            Udostępnij
                        </button>
                        <button wire:click="saveAsTemplate" class="btn btn-sm btn-outline text-blue-700 border-blue-300 hover:bg-blue-100">
                            Zapisz jako szablon
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Widget Templates -->
    <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-600">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-6">Szablony Układów</h4>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($this->getWidgetTemplates() as $template)
                <div class="bg-white dark:bg-gray-800 rounded-lg border hover:border-blue-400 transition-colors overflow-hidden">
                    <!-- Template Preview -->
                    <div class="h-32 bg-gray-100 dark:bg-gray-700 p-2">
                        <div class="grid grid-cols-4 gap-1 h-full">
                            @for($i = 0; $i < 12; $i++)
                                <div class="bg-gray-300 dark:bg-gray-600 rounded opacity-{{ $i % 3 === 0 ? '100' : '60' }}"></div>
                            @endfor
                        </div>
                    </div>
                    
                    <!-- Template Info -->
                    <div class="p-4">
                        <h5 class="font-medium text-gray-900 dark:text-white mb-1">{{ $template['name'] }}</h5>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ $template['description'] }}</p>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500">{{ count($template['widgets']) }} widgetów</span>
                            <button wire:click="applyTemplate('{{ $template['id'] }}')" 
                                    class="btn btn-sm btn-primary">
                                Zastosuj
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Styles moved to resources/css/admin/components.css --}}

@push('scripts')
<script>
    function widgetGrid() {
        return {
            gridColumns: 12,
            widgets: [],
            dragging: null,
            
            initGrid(layout) {
                this.gridColumns = layout.grid?.columns || 12;
                this.widgets = layout.widgets || [];
                this.setupDragAndDrop();
                
                document.documentElement.style.setProperty('--grid-columns', this.gridColumns);
            },
            
            setupDragAndDrop() {
                const container = this.$el;
                
                // Make widgets draggable
                const widgets = container.querySelectorAll('.widget-item');
                widgets.forEach(widget => {
                    widget.draggable = true;
                    
                    widget.addEventListener('dragstart', (e) => {
                        this.dragging = widget;
                        widget.classList.add('dragging');
                        e.dataTransfer.effectAllowed = 'move';
                    });
                    
                    widget.addEventListener('dragend', (e) => {
                        widget.classList.remove('dragging');
                        this.dragging = null;
                    });
                });
                
                // Handle drop zones
                container.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                });
                
                container.addEventListener('drop', (e) => {
                    e.preventDefault();
                    
                    if (this.dragging) {
                        const rect = container.getBoundingClientRect();
                        const x = e.clientX - rect.left;
                        const y = e.clientY - rect.top;
                        
                        // Calculate grid position
                        const gridX = Math.floor((x / container.offsetWidth) * this.gridColumns);
                        const gridY = Math.floor(y / 80); // Assuming 80px row height
                        
                        // Update widget position
                        const widgetId = this.dragging.dataset.widgetId;
                        this.updateWidgetPosition(widgetId, gridX, gridY);
                    }
                });
            },
            
            updateWidgetPosition(widgetId, x, y) {
                // Emit to Livewire
                this.$wire.call('updateWidgetPosition', widgetId, x, y);
            }
        }
    }
    
    // Auto-save widget layout changes
    let saveTimeout;
    window.addEventListener('widgetLayoutChanged', function() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            @this.call('saveWidgetLayout');
        }, 2000);
    });
</script>
@endpush