<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
    <!-- Header Section -->
    <div class="bg-gradient-to-r from-gray-800 via-gray-700 to-gray-800 border-b border-gray-600 backdrop-blur-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">Eksport Masowy Produktów</h1>
                    <p class="mt-1 text-gray-300">Zarządzanie eksportem produktów do sklepów PrestaShop</p>
                </div>
                
                <!-- Quick Stats -->
                <div class="grid grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-400">{{ $stats['total_products'] }}</div>
                        <div class="text-xs text-gray-400">Produkty</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-400">{{ $stats['selected_products'] }}</div>
                        <div class="text-xs text-gray-400">Wybrane</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-400">{{ $stats['selected_shops'] }}</div>
                        <div class="text-xs text-gray-400">Sklepy</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-400">{{ $stats['active_exports'] }}</div>
                        <div class="text-xs text-gray-400">Aktywne</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        @if (session()->has('success'))
            <div class="mb-6 bg-green-800/30 border border-green-600 text-green-200 px-4 py-3 rounded-lg backdrop-blur-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-6 bg-red-800/30 border border-red-600 text-red-200 px-4 py-3 rounded-lg backdrop-blur-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Column - Filters & Product Selection -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Product Filters -->
                <div class="bg-gray-800/50 rounded-xl border border-gray-600 backdrop-blur-sm">
                    <div class="p-6 border-b border-gray-600">
                        <h3 class="text-xl font-semibold text-white mb-4">Filtry Produktów</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            <!-- Search -->
                            <div class="xl:col-span-3">
                                <label class="block text-sm font-medium text-gray-300 mb-1">Wyszukaj produkty</label>
                                <input wire:model.live="search" type="text" 
                                       class="w-full bg-gray-700 border border-gray-500 rounded-lg px-3 py-2 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                       placeholder="SKU, nazwa, opis...">
                            </div>
                            
                            <!-- Category Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Kategoria</label>
                                <select wire:model.live="categoryFilter" 
                                        class="w-full bg-gray-700 border border-gray-500 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                                    <option value="all">Wszystkie kategorie</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @foreach($category->children as $child)
                                            <option value="{{ $child->id }}">-- {{ $child->name }}</option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Brand Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Marka</label>
                                <select wire:model.live="brandFilter" 
                                        class="w-full bg-gray-700 border border-gray-500 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                                    <option value="all">Wszystkie marki</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand }}">{{ $brand }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Stock Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Stan magazynowy</label>
                                <select wire:model.live="stockFilter" 
                                        class="w-full bg-gray-700 border border-gray-500 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                                    @foreach($stockFilterOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <!-- Price Range -->
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Cena min (PLN)</label>
                                <input wire:model.live="priceMinFilter" type="number" step="0.01" min="0"
                                       class="w-full bg-gray-700 border border-gray-500 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Cena max (PLN)</label>
                                <input wire:model.live="priceMaxFilter" type="number" step="0.01" min="0"
                                       class="w-full bg-gray-700 border border-gray-500 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center mt-4">
                            <button wire:click="resetFilters" 
                                    class="text-orange-400 hover:text-orange-300 text-sm font-medium">
                                🔄 Wyczyść filtry
                            </button>
                            
                            <div class="flex items-center">
                                <label class="flex items-center">
                                    <input wire:model="selectAllProducts" type="checkbox" 
                                           class="mr-2 bg-gray-700 border-gray-500 rounded text-orange-500 focus:ring-orange-500">
                                    <span class="text-sm text-gray-300">Zaznacz wszystkie</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    @if($errors->any())
                        <div class="p-4 bg-red-800/20 border-t border-red-600">
                            @foreach($errors->all() as $error)
                                <p class="text-red-400 text-sm">{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif
                </div>
                
                <!-- Products List -->
                <div class="bg-gray-800/50 rounded-xl border border-gray-600 backdrop-blur-sm">
                    <div class="p-6">
                        <h3 class="text-xl font-semibold text-white mb-4">
                            Produkty do eksportu 
                            <span class="text-sm font-normal text-gray-400">
                                ({{ $products->total() }} produktów, {{ count($selectedProducts) }} wybranych)
                            </span>
                        </h3>
                        
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            @forelse($products as $product)
                                <div class="flex items-center justify-between p-3 bg-gray-700/50 rounded-lg hover:bg-gray-700 transition-colors">
                                    <label class="flex items-center cursor-pointer flex-1">
                                        <input wire:model="selectedProducts" 
                                               type="checkbox" 
                                               value="{{ $product->id }}"
                                               class="mr-3 bg-gray-600 border-gray-500 rounded text-orange-500 focus:ring-orange-500">
                                        <div class="flex-1">
                                            <div class="text-white font-medium">{{ $product->name }}</div>
                                            <div class="text-sm text-gray-400">
                                                SKU: {{ $product->sku }} | 
                                                Stan: {{ $product->stock_quantity }} szt. | 
                                                Cena: {{ number_format($product->price_retail, 2) }} PLN
                                            </div>
                                        </div>
                                    </label>
                                    
                                    <div class="flex items-center space-x-2 ml-4">
                                        @if($product->stock_quantity > 0)
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-800 text-green-200">
                                                ✓ Dostępny
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-800 text-red-200">
                                                ✗ Brak
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-8 text-gray-400">
                                    Brak produktów spełniających kryteria
                                </div>
                            @endforelse
                        </div>
                        
                        <div class="mt-4">
                            {{ $products->links() }}
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - Export Configuration -->
            <div class="space-y-6">
                
                <!-- Shop Selection -->
                <div class="bg-gray-800/50 rounded-xl border border-gray-600 backdrop-blur-sm">
                    <div class="p-6">
                        <h3 class="text-xl font-semibold text-white mb-4">Wybierz Sklepy</h3>
                        
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            @forelse($shops as $shop)
                                <label class="flex items-center p-3 bg-gray-700/50 rounded-lg hover:bg-gray-700 transition-colors cursor-pointer">
                                    <input wire:model="selectedShops" 
                                           type="checkbox" 
                                           value="{{ $shop->id }}"
                                           class="mr-3 bg-gray-600 border-gray-500 rounded text-orange-500 focus:ring-orange-500">
                                    <div class="flex-1">
                                        <div class="text-white font-medium">{{ $shop->name }}</div>
                                        <div class="text-sm text-gray-400">{{ $shop->url }}</div>
                                    </div>
                                    <div class="ml-2">
                                        @if($shop->connection_status === 'connected')
                                            <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                                        @else
                                            <span class="w-2 h-2 bg-red-400 rounded-full"></span>
                                        @endif
                                    </div>
                                </label>
                            @empty
                                <div class="text-center py-8 text-gray-400">
                                    Brak dostępnych sklepów
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                
                <!-- Export Configuration -->
                <div class="bg-gray-800/50 rounded-xl border border-gray-600 backdrop-blur-sm">
                    <div class="p-6">
                        <h3 class="text-xl font-semibold text-white mb-4">Konfiguracja Eksportu</h3>
                        
                        <!-- Export Format -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-300 mb-2">Format eksportu</label>
                            <select wire:model="exportFormat" 
                                    class="w-full bg-gray-700 border border-gray-500 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                                @foreach($exportFormats as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Include Options -->
                        <div class="space-y-3 mb-4">
                            <h4 class="font-medium text-gray-300">Elementy do eksportu:</h4>
                            
                            <label class="flex items-center">
                                <input wire:model="includeImages" type="checkbox" 
                                       class="mr-2 bg-gray-600 border-gray-500 rounded text-orange-500 focus:ring-orange-500">
                                <span class="text-sm text-gray-300">Zdjęcia produktów</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input wire:model="includeDescriptions" type="checkbox" 
                                       class="mr-2 bg-gray-600 border-gray-500 rounded text-orange-500 focus:ring-orange-500">
                                <span class="text-sm text-gray-300">Opisy produktów</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input wire:model="includeCategories" type="checkbox" 
                                       class="mr-2 bg-gray-600 border-gray-500 rounded text-orange-500 focus:ring-orange-500">
                                <span class="text-sm text-gray-300">Kategorie</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input wire:model="includeStock" type="checkbox" 
                                       class="mr-2 bg-gray-600 border-gray-500 rounded text-orange-500 focus:ring-orange-500">
                                <span class="text-sm text-gray-300">Stany magazynowe</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input wire:model="includePricing" type="checkbox" 
                                       class="mr-2 bg-gray-600 border-gray-500 rounded text-orange-500 focus:ring-orange-500">
                                <span class="text-sm text-gray-300">Cenniki</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input wire:model="includeVariants" type="checkbox" 
                                       class="mr-2 bg-gray-600 border-gray-500 rounded text-orange-500 focus:ring-orange-500">
                                <span class="text-sm text-gray-300">Warianty produktów</span>
                            </label>
                        </div>
                        
                        <!-- Performance Settings -->
                        <div class="space-y-3 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Rozmiar paczki</label>
                                <input wire:model="batchSize" type="number" min="10" max="500"
                                       class="w-full bg-gray-700 border border-gray-500 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <p class="text-xs text-gray-400 mt-1">Liczba produktów przetwarzanych jednocześnie</p>
                            </div>
                            
                            <label class="flex items-center">
                                <input wire:model="validateBeforeExport" type="checkbox" 
                                       class="mr-2 bg-gray-600 border-gray-500 rounded text-orange-500 focus:ring-orange-500">
                                <span class="text-sm text-gray-300">Walidacja przed eksportem</span>
                            </label>
                        </div>
                        
                        <!-- Export Button -->
                        <button wire:click="startBulkExport" 
                                @if($exportInProgress) disabled @endif
                                class="w-full bg-gradient-to-r from-orange-600 to-orange-500 hover:from-orange-700 hover:to-orange-600 disabled:from-gray-600 disabled:to-gray-500 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 disabled:scale-100">
                            @if($exportInProgress)
                                <div class="flex items-center justify-center">
                                    <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                                    Eksport w toku...
                                </div>
                            @else
                                🚀 Rozpocznij Eksport
                            @endif
                        </button>
                    </div>
                </div>
                
                <!-- Active Exports -->
                @if(!empty($activeExports))
                    <div class="bg-gray-800/50 rounded-xl border border-gray-600 backdrop-blur-sm">
                        <div class="p-6">
                            <h3 class="text-xl font-semibold text-white mb-4">Aktywne Eksporty</h3>
                            
                            <div class="space-y-3">
                                @foreach($activeExports as $export)
                                    <div class="p-3 bg-gray-700/50 rounded-lg">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm text-white font-medium">{{ $export['job_name'] }}</span>
                                            <button wire:click="cancelExportJob('{{ $export['job_id'] }}')" 
                                                    class="text-red-400 hover:text-red-300 text-sm">
                                                ✕ Anuluj
                                            </button>
                                        </div>
                                        
                                        @if(isset($exportProgress[$export['job_id']]))
                                            @php
                                                $progress = $exportProgress[$export['job_id']];
                                            @endphp
                                            
                                            <div class="w-full bg-gray-600 rounded-full h-2 mb-2">
                                                <div class="bg-orange-500 h-2 rounded-full" style="width: {{ $progress['progress'] }}%"></div>
                                            </div>
                                            
                                            <div class="flex justify-between text-xs text-gray-400">
                                                <span>{{ $progress['progress'] }}% ukończone</span>
                                                @if(isset($progress['eta_seconds']))
                                                    <span>ETA: {{ gmdate('H:i:s', $progress['eta_seconds']) }}</span>
                                                @endif
                                            </div>
                                            
                                            @if($progress['message'])
                                                <p class="text-xs text-gray-300 mt-1">{{ $progress['message'] }}</p>
                                            @endif
                                        @else
                                            <div class="flex items-center">
                                                <div class="animate-pulse w-2 h-2 bg-orange-400 rounded-full mr-2"></div>
                                                <span class="text-xs text-gray-400">Oczekuje...</span>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
                
                <!-- Recent Exports -->
                @if(!empty($recentExports))
                    <div class="bg-gray-800/50 rounded-xl border border-gray-600 backdrop-blur-sm">
                        <div class="p-6">
                            <h3 class="text-xl font-semibold text-white mb-4">Ostatnie Eksporty</h3>
                            
                            <div class="space-y-2">
                                @foreach($recentExports->take(5) as $export)
                                    <div class="flex items-center justify-between p-2 text-sm">
                                        <div class="flex-1">
                                            <div class="text-white">{{ $export->job_name }}</div>
                                            <div class="text-gray-400 text-xs">{{ $export->created_at->diffForHumans() }}</div>
                                        </div>
                                        
                                        <span class="px-2 py-1 rounded text-xs font-medium
                                            @if($export->status === 'completed') bg-green-800 text-green-200
                                            @elseif($export->status === 'failed') bg-red-800 text-red-200
                                            @elseif($export->status === 'cancelled') bg-gray-800 text-gray-200
                                            @else bg-orange-800 text-orange-200 @endif">
                                            {{ ucfirst($export->status) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>