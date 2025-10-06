<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    {{-- Header Section --}}
    <div class="sticky top-0 z-40 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            {{-- Title & Action Bar --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        @if($isEditMode)
                            Edytuj produkt: {{ $name }}
                        @else
                            Dodaj nowy produkt
                        @endif
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        @if($isEditMode)
                            Aktualizuj informacje o produkcie SKU: {{ $sku }}
                        @else
                            Wypełnij wszystkie wymagane pola, aby dodać produkt do systemu
                        @endif
                    </p>
                </div>

                {{-- Compact Action Buttons - Only essential nav --}}
                <div class="flex items-center space-x-2">
                    {{-- Quick Status Indicator --}}
                    @if($hasUnsavedChanges)
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Niezapisane zmiany
                        </span>
                    @endif

                    {{-- Back to Products List --}}
                    <a href="/admin/products"
                       class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Lista produktów
                    </a>

                            <div wire:loading.remove wire:target="resetToDefaults">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </div>
                            <div wire:loading wire:target="resetToDefaults">
                                <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <span wire:loading.remove wire:target="resetToDefaults">
                                Przywróć domyślne
                            </span>
                            <span wire:loading wire:target="resetToDefaults">Przywracanie...</span>
                        </button>
                    @endif

                    {{-- Enhanced Sync Button - Context-aware name and functionality --}}
                    <button wire:click="syncToShops"
                            wire:loading.attr="disabled"
                            wire:target="syncToShops"
                            class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:bg-purple-400 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                        <div wire:loading.remove wire:target="syncToShops">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </div>
                        <div wire:loading wire:target="syncToShops">
                            <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <span wire:loading.remove wire:target="syncToShops">
                            @if($activeShopId === null)
                                Aktualizuj na wszystkich sklepach
                            @else
                                Zaktualizuj na sklepie
                            @endif
                        </span>
                        <span wire:loading wire:target="syncToShops">
                            @if($activeShopId === null)
                                Aktualizowanie wszystkich sklepów...
                            @else
                                Aktualizowanie sklepu...
                            @endif
                        </span>
                    </button>

                    {{-- Save All Changes Button - Only show when there are pending changes --}}
                    @if($hasUnsavedChanges)
                        <button wire:click="saveAllChanges"
                                wire:loading.attr="disabled"
                                wire:target="saveAllChanges"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                            <div wire:loading.remove wire:target="saveAllChanges">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                </svg>
                            </div>
                            <div wire:loading wire:target="saveAllChanges">
                                <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <span wire:loading.remove wire:target="saveAllChanges">
                                Zapisz wszystkie zmiany
                            </span>
                            <span wire:loading wire:target="saveAllChanges">Zapisywanie wszystkich zmian...</span>
                        </button>
                    @endif

                    {{-- Save and Close Button --}}
                    <button wire:click="saveAndClose"
                            wire:loading.attr="disabled"
                            wire:target="saveAndClose"
                            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                        <div wire:loading.remove wire:target="saveAndClose">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div wire:loading wire:target="saveAndClose">
                            <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <span wire:loading.remove wire:target="saveAndClose">
                            {{ $isEditMode ? 'Zapisz i Zamknij' : 'Zapisz' }}
                        </span>
                        <span wire:loading wire:target="saveAndClose">Zapisywanie...</span>
                    </button>
                </div>
            </div>

            {{-- Tab Navigation --}}
            <div class="mt-6">
                {{-- Primary Tabs (First Line) --}}
                <nav class="flex space-x-8 border-b border-gray-200 dark:border-gray-700">
                    {{-- Basic Information Tab --}}
                    <button wire:click="switchTab('basic')"
                            class="py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200 {{ $activeTab === 'basic' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Informacje podstawowe
                        </div>
                    </button>

                    {{-- Description Tab --}}
                    <button wire:click="switchTab('description')"
                            class="py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200 {{ $activeTab === 'description' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                            Opisy i SEO
                        </div>
                    </button>

                    {{-- Physical Properties Tab --}}
                    <button wire:click="switchTab('physical')"
                            class="py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200 {{ $activeTab === 'physical' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4a1 1 0 011-1h4.586a1 1 0 01.707.293l1.414 1.414a1 1 0 00.707.293H20a1 1 0 011 1v12a1 1 0 01-1 1H4a1 1 0 01-1-1V8a1 1 0 011-1z" />
                            </svg>
                            Właściwości fizyczne
                        </div>
                    </button>
                </nav>

                {{-- MULTI-STORE MANAGEMENT (Second Line) --}}
                {{-- Dostępne zarówno w create jak i edit mode --}}
                    <div class="mt-4 bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    Zarządzanie sklepami
                                </h4>

                                {{-- Default Data Toggle --}}
                                <button wire:click="switchToShop(null)"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full transition-colors duration-200 {{ $activeShopId === null ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' }}">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 1v4" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 1v4" />
                                    </svg>
                                    Dane domyślne
                                </button>
                            </div>

                            {{-- Shop Management Buttons --}}
                            <div class="flex items-center space-x-2">
                                <button wire:click="openShopSelector"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Dodaj do sklepu
                                </button>
                            </div>
                        </div>

                        {{-- Exported Shops List --}}
                        @if(!empty($exportedShops))
                            <div class="mt-3">
                                <div class="flex flex-wrap gap-2">
                                    @foreach($availableShops as $shop)
                                        @if(in_array($shop['id'], $exportedShops))
                                            <div class="inline-flex items-center group">
                                                {{-- Shop Button --}}
                                                <button wire:click="switchToShop({{ $shop['id'] }})"
                                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-l-lg transition-all duration-200 {{ $activeShopId === $shop['id'] ? 'bg-orange-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600' }}">
                                                    {{-- Shop Status Icon --}}
                                                    @if($shop['connection_status'] === 'connected')
                                                        <svg class="w-3 h-3 mr-1.5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                        </svg>
                                                    @else
                                                        <svg class="w-3 h-3 mr-1.5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                        </svg>
                                                    @endif
                                                    {{ Str::limit($shop['name'], 12) }}

                                                    {{-- Sync Status Indicator --}}
                                                    @if(isset($shopData[$shop['id']]))
                                                        @php($syncStatus = $shopData[$shop['id']]['sync_status'] ?? 'pending')
                                                        @if($syncStatus === 'synced')
                                                            <span class="ml-1 w-2 h-2 bg-green-400 rounded-full"></span>
                                                        @elseif($syncStatus === 'pending')
                                                            <span class="ml-1 w-2 h-2 bg-yellow-400 rounded-full"></span>
                                                        @elseif($syncStatus === 'error')
                                                            <span class="ml-1 w-2 h-2 bg-red-400 rounded-full"></span>
                                                        @endif
                                                    @endif
                                                </button>

                                                {{-- Visibility Toggle --}}
                                                <button wire:click="toggleShopVisibility({{ $shop['id'] }})"
                                                        title="{{ $this->getShopVisibility($shop['id']) ? 'Ukryj w sklepie' : 'Pokaż w sklepie' }}"
                                                        class="px-2 py-1.5 text-xs transition-colors duration-200 {{ $this->getShopVisibility($shop['id']) ? 'bg-green-500 hover:bg-green-600 text-white' : 'bg-gray-300 hover:bg-gray-400 text-gray-700' }}">
                                                    @if($this->getShopVisibility($shop['id']))
                                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                                        </svg>
                                                    @else
                                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"/>
                                                            <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"/>
                                                        </svg>
                                                    @endif
                                                </button>

                                                {{-- Remove Button --}}
                                                <button wire:click="removeFromShop({{ $shop['id'] }})"
                                                        wire:confirm="Czy na pewno usunąć produkt z tego sklepu?"
                                                        title="Usuń z sklepu"
                                                        class="px-2 py-1.5 text-xs bg-red-500 hover:bg-red-600 text-white rounded-r-lg transition-all duration-200 opacity-0 group-hover:opacity-100">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="mt-3">
                                <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                                    Ten produkt nie jest jeszcze eksportowany do żadnego sklepu
                                </p>
                            </div>
                        @endif
                    </div>
            </div>
        </div>
    </div>

    {{-- Form Content --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Success Message --}}
        @if($successMessage)
            <div x-data="{ show: true }"
                 x-show="show"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-300"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform translate-y-2"
                 class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <p class="text-green-800 dark:text-green-200">{{ $successMessage }}</p>
                    </div>
                    <button @click="show = false" class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-200 ml-4">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        {{-- Main Form Card --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            {{-- Tab Content --}}
            <div class="p-6">
                {{-- BASIC INFORMATION TAB --}}
                <div class="{{ $activeTab === 'basic' ? '' : 'hidden' }}">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Informacje podstawowe</h3>

                        {{-- Active Shop Indicator --}}
                        @if($activeShopId !== null && isset($availableShops))
                            @php($currentShop = collect($availableShops)->firstWhere('id', $activeShopId))
                            <div class="flex items-center">
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Edytujesz: {{ $currentShop['name'] ?? 'Nieznany sklep' }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- SKU Field --}}
                        <div class="md:col-span-1">
                            <label for="sku" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                SKU produktu <span class="text-red-500">*</span>
                                @php($skuIndicator = $this->getFieldStatusIndicator('sku'))
                                @if($skuIndicator['show'])
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $skuIndicator['class'] }}">
                                        {{ $skuIndicator['text'] }}
                                    </span>
                                @endif
                            </label>
                            <input wire:model.live="sku"
                                   type="text"
                                   id="sku"
                                   placeholder="np. ABC123456"
                                   class="{{ $this->getFieldClasses('sku') }} @error('sku') !border-red-500 @enderror">
                            @error('sku')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Product Type --}}
                        <div class="md:col-span-1">
                            <label for="product_type_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Typ produktu <span class="text-red-500">*</span>
                                @php($typeIndicator = $this->getFieldStatusIndicator('product_type_id'))
                                @if($typeIndicator['show'])
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $typeIndicator['class'] }}">
                                        {{ $typeIndicator['text'] }}
                                    </span>
                                @endif
                            </label>
                            <select wire:model.live="product_type_id"
                                    id="product_type_id"
                                    class="{{ $this->getFieldClasses('product_type_id') }} @error('product_type_id') !border-red-500 @enderror">
                                <option value="">-- Wybierz typ produktu --</option>
                                @foreach($productTypes as $type)
                                    <option value="{{ $type->id }}" title="{{ $type->description }}">
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('product_type_id')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Product Name --}}
                        <div class="md:col-span-2">
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nazwa produktu <span class="text-red-500">*</span>
                                {{-- Status indicator --}}
                                @php($nameIndicator = $this->getFieldStatusIndicator('name'))
                                @if($nameIndicator['show'])
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $nameIndicator['class'] }}">
                                        {{ $nameIndicator['text'] }}
                                    </span>
                                @endif
                            </label>
                            <input wire:model.live="name"
                                   type="text"
                                   id="name"
                                   placeholder="Wprowadź nazwę produktu"
                                   class="{{ $this->getFieldClasses('name') }} @error('name') !border-red-500 @enderror">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Slug Field (Optional, Toggleable) --}}
                        <div class="md:col-span-2">
                            <div class="flex items-center justify-between mb-2">
                                <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Slug URL (opcjonalne)
                                </label>
                                <button wire:click="toggleSlugField"
                                        type="button"
                                        class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-500">
                                    {{ $showSlugField ? 'Ukryj' : 'Pokaż' }} slug
                                </button>
                            </div>
                            @if($showSlugField)
                                <div class="space-y-1">
                                    {{-- Status indicator for slug --}}
                                    @php($slugIndicator = $this->getFieldStatusIndicator('slug'))
                                    @if($slugIndicator['show'])
                                        <div class="flex items-center space-x-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $slugIndicator['class'] }}">
                                                {{ $slugIndicator['text'] }}
                                            </span>
                                        </div>
                                    @endif
                                    <input wire:model.live="slug"
                                           type="text"
                                           id="slug"
                                           placeholder="automatycznie-generowany-slug"
                                           class="{{ $this->getFieldClasses('slug') }} @error('slug') !border-red-500 @enderror">
                                    @error('slug')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Automatycznie: <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $slug ?: 'automatycznie-generowany-slug' }}</code>
                                </p>
                            @endif
                        </div>

                        {{-- Manufacturer --}}
                        <div class="md:col-span-1">
                            <label for="manufacturer" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Producent
                                @php($manufacturerIndicator = $this->getFieldStatusIndicator('manufacturer'))
                                @if($manufacturerIndicator['show'])
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $manufacturerIndicator['class'] }}">
                                        {{ $manufacturerIndicator['text'] }}
                                    </span>
                                @endif
                            </label>
                            <input wire:model.live="manufacturer"
                                   type="text"
                                   id="manufacturer"
                                   placeholder="np. Honda, Toyota, Bosch"
                                   class="{{ $this->getFieldClasses('manufacturer') }} @error('manufacturer') !border-red-500 @enderror">
                            @error('manufacturer')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Supplier Code --}}
                        <div class="md:col-span-1">
                            <label for="supplier_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Kod dostawcy
                                @php($supplierIndicator = $this->getFieldStatusIndicator('supplier_code'))
                                @if($supplierIndicator['show'])
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $supplierIndicator['class'] }}">
                                        {{ $supplierIndicator['text'] }}
                                    </span>
                                @endif
                            </label>
                            <input wire:model.live="supplier_code"
                                   type="text"
                                   id="supplier_code"
                                   placeholder="Kod u dostawcy"
                                   class="{{ $this->getFieldClasses('supplier_code') }} @error('supplier_code') !border-red-500 @enderror">
                            @error('supplier_code')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- EAN Code --}}
                        <div class="md:col-span-1">
                            <label for="ean" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Kod EAN
                                @php($eanIndicator = $this->getFieldStatusIndicator('ean'))
                                @if($eanIndicator['show'])
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $eanIndicator['class'] }}">
                                        {{ $eanIndicator['text'] }}
                                    </span>
                                @endif
                            </label>
                            <input wire:model.live="ean"
                                   type="text"
                                   id="ean"
                                   placeholder="Kod kreskowy EAN"
                                   class="{{ $this->getFieldClasses('ean') }} @error('ean') !border-red-500 @enderror">
                            @error('ean')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Sort Order --}}
                        <div class="md:col-span-1">
                            <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Kolejność sortowania
                            </label>
                            <input wire:model.live="sort_order"
                                   type="number"
                                   id="sort_order"
                                   min="0"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('sort_order')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Status Checkboxes --}}
                        <div class="md:col-span-2">
                            <fieldset class="space-y-3">
                                <legend class="text-sm font-medium text-gray-700 dark:text-gray-300">Status produktu</legend>

                                <div class="flex items-center">
                                    <input wire:click="toggleActiveStatus"
                                           type="checkbox"
                                           {{ $is_active ? 'checked' : '' }}
                                           id="is_active"
                                           class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 cursor-pointer">
                                    <label for="is_active" class="ml-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                        Produkt aktywny
                                        @if($is_active)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                Aktywny
                                            </span>
                                        @else
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                                Nieaktywny
                                            </span>
                                        @endif
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input wire:model.live="is_variant_master"
                                           type="checkbox"
                                           id="is_variant_master"
                                           class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <label for="is_variant_master" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                        Produkt z wariantami
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input wire:model.live="is_featured"
                                           type="checkbox"
                                           id="is_featured"
                                           class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <label for="is_featured" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                        Produkt wyróżniony
                                        @if($is_featured)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                                ⭐ Wyróżniony
                                            </span>
                                        @endif
                                    </label>
                                </div>
                            </fieldset>
                        </div>

                        {{-- Publishing Schedule Section --}}
                        <div class="md:col-span-2">
                            <fieldset class="space-y-4">
                                <legend class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Harmonogram publikacji
                                </legend>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="available_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Dostępny od
                                        </label>
                                        <input wire:model.live="available_from"
                                               type="datetime-local"
                                               id="available_from"
                                               class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm">
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Pozostaw puste dla "od zawsze"
                                        </p>
                                    </div>

                                    <div>
                                        <label for="available_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Dostępny do
                                        </label>
                                        <input wire:model.live="available_to"
                                               type="datetime-local"
                                               id="available_to"
                                               class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm">
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Pozostaw puste dla "na zawsze"
                                        </p>
                                    </div>
                                </div>

                                {{-- Publishing Status Display --}}
                                @if($isEditMode && $product)
                                    <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <div class="flex items-center space-x-2">
                                            @php($status = $product->getPublishingStatus())
                                            @if($status['is_available'])
                                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                                <span class="text-sm font-medium text-green-700 dark:text-green-300">Dostępny</span>
                                            @else
                                                <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                                <span class="text-sm font-medium text-red-700 dark:text-red-300">Niedostępny</span>
                                            @endif
                                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $status['status_text'] }}</span>
                                        </div>
                                    </div>
                                @endif
                            </fieldset>
                        </div>

                        {{-- Categories Section --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                Kategorie produktu
                                {{-- Category Status Indicator --}}
                                @php($categoryIndicator = $this->getCategoryStatusIndicator())
                                @if($categoryIndicator['show'])
                                    <span class="ml-2 {{ $categoryIndicator['class'] }}">
                                        {{ $categoryIndicator['text'] }}
                                    </span>
                                @endif
                            </label>

                            @if($categories->count() > 0)
                                <div class="{{ $this->getCategoryClasses() }} max-h-64 overflow-y-auto">
                                    @foreach($categories as $category)
                                        <div class="flex items-center space-x-2 py-1">
                                            <input wire:click="toggleCategory({{ $category->id }})"
                                                   type="checkbox"
                                                   id="category_{{ $category->id }}"
                                                   {{ in_array($category->id, $this->currentSelectedCategories) ? 'checked' : '' }}
                                                   class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">

                                            <label for="category_{{ $category->id }}" class="flex-1 text-sm text-gray-700 dark:text-gray-300">
                                                {{ str_repeat('—', $category->level) }} {{ $category->name }}
                                            </label>

                                            @if(in_array($category->id, $this->currentSelectedCategories))
                                                <button wire:click="setPrimaryCategory({{ $category->id }})"
                                                        type="button"
                                                        class="px-2 py-1 text-xs rounded {{ $this->currentPrimaryCategoryId == $category->id ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                                                    {{ $this->currentPrimaryCategoryId == $category->id ? 'Główna' : 'Ustaw główną' }}
                                                </button>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                @if($this->currentSelectedCategories)
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        Wybrano {{ count($this->currentSelectedCategories) }} {{ count($this->currentSelectedCategories) == 1 ? 'kategorię' : 'kategori' }}.
                                        @if($this->currentPrimaryCategoryId)
                                            Główna: <strong>{{ $categories->find($this->currentPrimaryCategoryId)?->name }}</strong>
                                        @endif
                                        @if($activeShopId !== null)
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300 ml-2">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                                </svg>
                                                Kategorie specyficzne dla sklepu
                                            </span>
                                        @endif
                                    </p>
                                @endif
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">Brak dostępnych kategorii.</p>
                            @endif

                            @error('categories')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- DESCRIPTION TAB --}}
                <div class="{{ $activeTab === 'description' ? '' : 'hidden' }}">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Opisy i SEO</h3>

                        {{-- Active Shop Indicator --}}
                        @if($activeShopId !== null && isset($availableShops))
                            @php($currentShop = collect($availableShops)->firstWhere('id', $activeShopId))
                            <div class="flex items-center">
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Edytujesz: {{ $currentShop['name'] ?? 'Nieznany sklep' }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-6">
                        {{-- Short Description --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label for="short_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Krótki opis
                                    {{-- Status indicator --}}
                                    @php($shortDescIndicator = $this->getFieldStatusIndicator('short_description'))
                                    @if($shortDescIndicator['show'])
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $shortDescIndicator['class'] }}">
                                            {{ $shortDescIndicator['text'] }}
                                        </span>
                                    @endif
                                </label>
                                <span class="text-sm {{ $shortDescriptionWarning ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ $shortDescriptionCount }}/800
                                </span>
                            </div>
                            <textarea wire:model.live="short_description"
                                      id="short_description"
                                      rows="4"
                                      placeholder="Krótki opis produktu widoczny w listach i kartach produktów..."
                                      class="{{ $this->getFieldClasses('short_description') }} @error('short_description') !border-red-500 @enderror {{ $shortDescriptionWarning ? '!border-orange-500 focus:!border-orange-500 focus:!ring-orange-500' : '' }}"></textarea>
                            @if($shortDescriptionWarning)
                                <p class="mt-1 text-sm text-orange-600 dark:text-orange-400">Przekraczasz zalecany limit znaków</p>
                            @endif
                            @error('short_description')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Long Description --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label for="long_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Długi opis
                                    @php($longDescIndicator = $this->getFieldStatusIndicator('long_description'))
                                    @if($longDescIndicator['show'])
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $longDescIndicator['class'] }}">
                                            {{ $longDescIndicator['text'] }}
                                        </span>
                                    @endif
                                </label>
                                <span class="text-sm {{ $longDescriptionWarning ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ $longDescriptionCount }}/21844
                                </span>
                            </div>
                            <textarea wire:model.live="long_description"
                                      id="long_description"
                                      rows="8"
                                      placeholder="Szczegółowy opis produktu z specyfikacją techniczną, zastosowaniem, kompatybilnością..."
                                      class="{{ $this->getFieldClasses('long_description') }} @error('long_description') !border-red-500 @enderror {{ $longDescriptionWarning ? '!border-orange-500 focus:!border-orange-500 focus:!ring-orange-500' : '' }}"></textarea>
                            @if($longDescriptionWarning)
                                <p class="mt-1 text-sm text-orange-600 dark:text-orange-400">Przekraczasz zalecany limit znaków</p>
                            @endif
                            @error('long_description')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- SEO Fields --}}
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Optymalizacja SEO</h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {{-- Meta Title --}}
                                <div class="md:col-span-2">
                                    <label for="meta_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Tytuł SEO (meta title)
                                        @php($metaTitleIndicator = $this->getFieldStatusIndicator('meta_title'))
                                        @if($metaTitleIndicator['show'])
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $metaTitleIndicator['class'] }}">
                                                {{ $metaTitleIndicator['text'] }}
                                            </span>
                                        @endif
                                    </label>
                                    <input wire:model.live="meta_title"
                                           type="text"
                                           id="meta_title"
                                           placeholder="Tytuł strony produktu dla wyszukiwarek"
                                           class="{{ $this->getFieldClasses('meta_title') }} @error('meta_title') !border-red-500 @enderror">
                                    @error('meta_title')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Meta Description --}}
                                <div class="md:col-span-2">
                                    <label for="meta_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Opis SEO (meta description)
                                        @php($metaDescIndicator = $this->getFieldStatusIndicator('meta_description'))
                                        @if($metaDescIndicator['show'])
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $metaDescIndicator['class'] }}">
                                                {{ $metaDescIndicator['text'] }}
                                            </span>
                                        @endif
                                    </label>
                                    <textarea wire:model.live="meta_description"
                                              id="meta_description"
                                              rows="3"
                                              placeholder="Opis produktu widoczny w wynikach wyszukiwania Google"
                                              class="{{ $this->getFieldClasses('meta_description') }} @error('meta_description') !border-red-500 @enderror"></textarea>
                                    @error('meta_description')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- PHYSICAL PROPERTIES TAB --}}
                <div class="{{ $activeTab === 'physical' ? '' : 'hidden' }}">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Właściwości fizyczne</h3>

                        {{-- Active Shop Indicator --}}
                        @if($activeShopId !== null && isset($availableShops))
                            @php($currentShop = collect($availableShops)->firstWhere('id', $activeShopId))
                            <div class="flex items-center">
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Edytujesz: {{ $currentShop['name'] ?? 'Nieznany sklep' }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Dimensions Section --}}
                        <div class="md:col-span-2">
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Wymiary</h4>

                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                {{-- Height --}}
                                <div>
                                    <label for="height" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Wysokość (cm)
                                        @php($heightIndicator = $this->getFieldStatusIndicator('height'))
                                        @if($heightIndicator['show'])
                                            <span class="ml-1 inline-flex items-center px-1 py-0.5 rounded text-xs font-medium {{ $heightIndicator['class'] }}">
                                                {{ $heightIndicator['text'] }}
                                            </span>
                                        @endif
                                    </label>
                                    <input wire:model.live="height"
                                           type="number"
                                           id="height"
                                           step="0.01"
                                           min="0"
                                           placeholder="0.00"
                                           class="{{ $this->getFieldClasses('height') }} @error('height') !border-red-500 @enderror">
                                    @error('height')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Width --}}
                                <div>
                                    <label for="width" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Szerokość (cm)
                                        @php($widthIndicator = $this->getFieldStatusIndicator('width'))
                                        @if($widthIndicator['show'])
                                            <span class="ml-1 inline-flex items-center px-1 py-0.5 rounded text-xs font-medium {{ $widthIndicator['class'] }}">
                                                {{ $widthIndicator['text'] }}
                                            </span>
                                        @endif
                                    </label>
                                    <input wire:model.live="width"
                                           type="number"
                                           id="width"
                                           step="0.01"
                                           min="0"
                                           placeholder="0.00"
                                           class="{{ $this->getFieldClasses('width') }} @error('width') !border-red-500 @enderror">
                                    @error('width')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Length --}}
                                <div>
                                    <label for="length" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Długość (cm)
                                        @php($lengthIndicator = $this->getFieldStatusIndicator('length'))
                                        @if($lengthIndicator['show'])
                                            <span class="ml-1 inline-flex items-center px-1 py-0.5 rounded text-xs font-medium {{ $lengthIndicator['class'] }}">
                                                {{ $lengthIndicator['text'] }}
                                            </span>
                                        @endif
                                    </label>
                                    <input wire:model.live="length"
                                           type="number"
                                           id="length"
                                           step="0.01"
                                           min="0"
                                           placeholder="0.00"
                                           class="{{ $this->getFieldClasses('length') }} @error('length') !border-red-500 @enderror">
                                    @error('length')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Calculated Volume --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Objętość (m³)
                                    </label>
                                    <div class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-400">
                                        {{ $calculatedVolume ? number_format($calculatedVolume, 6) : '—' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Weight --}}
                        <div>
                            <label for="weight" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Waga (kg)
                                @php($weightIndicator = $this->getFieldStatusIndicator('weight'))
                                @if($weightIndicator['show'])
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $weightIndicator['class'] }}">
                                        {{ $weightIndicator['text'] }}
                                    </span>
                                @endif
                            </label>
                            <input wire:model.live="weight"
                                   type="number"
                                   id="weight"
                                   step="0.001"
                                   min="0"
                                   placeholder="0.000"
                                   class="{{ $this->getFieldClasses('weight') }} @error('weight') !border-red-500 @enderror">
                            @error('weight')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Tax Rate --}}
                        <div>
                            <label for="tax_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Stawka VAT (%) <span class="text-red-500">*</span>
                                @php($taxRateIndicator = $this->getFieldStatusIndicator('tax_rate'))
                                @if($taxRateIndicator['show'])
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $taxRateIndicator['class'] }}">
                                        {{ $taxRateIndicator['text'] }}
                                    </span>
                                @endif
                            </label>
                            <input wire:model.live="tax_rate"
                                   type="number"
                                   id="tax_rate"
                                   step="0.01"
                                   min="0"
                                   max="100"
                                   placeholder="23.00"
                                   class="{{ $this->getFieldClasses('tax_rate') }} @error('tax_rate') !border-red-500 @enderror">
                            @error('tax_rate')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Physical Properties Info --}}
                        <div class="md:col-span-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <h5 class="font-medium text-blue-900 dark:text-blue-200">Informacje o wymiarach</h5>
                                    <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                                        Wymiary są używane do obliczania kosztów wysyłki, optymalizacji pakowania oraz integracji z systemami logistycznymi.
                                        Wszystkie wymiary podawaj w centymetrach (cm), wagę w kilogramach (kg).
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Form Footer --}}
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                <div class="flex items-center justify-between">
                    {{-- Validation Info --}}
                    <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                        @if($hasChanges)
                            <svg class="w-4 h-4 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Formularz zawiera niezapisane zmiany
                        @else
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Wszystkie pola są poprawne
                        @endif
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex items-center space-x-3">
                        <button wire:click="cancel"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            Anuluj
                        </button>

                        <button wire:click="updateOnly"
                                wire:loading.attr="disabled"
                                wire:target="updateOnly"
                                class="px-4 py-2 bg-orange-600 hover:bg-orange-700 disabled:bg-orange-400 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                            <span wire:loading.remove wire:target="updateOnly">
                                Zaktualizuj
                            </span>
                            <span wire:loading wire:target="updateOnly">Aktualizowanie...</span>
                        </button>

                        <button wire:click="saveAndClose"
                                wire:loading.attr="disabled"
                                wire:target="saveAndClose"
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                            <span wire:loading.remove wire:target="saveAndClose">
                                {{ $isEditMode ? 'Zapisz i Zamknij' : 'Zapisz' }}
                            </span>
                            <span wire:loading wire:target="saveAndClose">Zapisywanie...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alpine.js Tab Management --}}
    <script>
        document.addEventListener('livewire:init', () => {
            // Handle tab switching animations
            Livewire.on('tab-switched', (event) => {
                console.log('Tab switched to:', event.tab);
            });

            // Handle product saved event
            Livewire.on('product-saved', (event) => {
                console.log('Product saved with ID:', event.productId);

                // Optional: Show success notification or redirect
                setTimeout(() => {
                    if (confirm('Produkt został zapisany. Czy chcesz przejść do listy produktów?')) {
                        window.location.href = '{{ route("admin.products.index") }}';
                    }
                }, 2000);
            });


            // Prevent accidental navigation with unsaved changes
            window.addEventListener('beforeunload', (e) => {
                if (window.livewire.find('{{ $this->getId() }}').get('hasChanges')) {
                    e.preventDefault();
                    e.returnValue = 'Masz niezapisane zmiany. Czy na pewno chcesz opuścić stronę?';
                }
            });
        });
    </script>

    {{-- SHOP SELECTOR MODAL --}}
    @if($showShopSelector)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-4 text-center sm:p-0">
                {{-- Background overlay --}}
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" wire:click="closeShopSelector"></div>

                {{-- Modal content --}}
                <div class="inline-block align-middle bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    {{-- Header --}}
                    <div class="bg-white dark:bg-gray-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                Wybierz sklepy
                            </h3>
                            <button wire:click="closeShopSelector"
                                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors duration-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Content --}}
                    <form wire:submit.prevent="addToShops">
                        <div class="bg-white dark:bg-gray-800 px-6 py-4 max-h-96 overflow-y-auto">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                Wybierz sklepy, do których chcesz dodać ten produkt:
                            </p>

                            <div class="space-y-3">
                                @foreach($availableShops as $shop)
                                    @if(!in_array($shop['id'], $exportedShops))
                                        <label class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                            <input type="checkbox"
                                                   value="{{ $shop['id'] }}"
                                                   wire:model="selectedShopsToAdd"
                                                   class="h-4 w-4 text-orange-600 border-gray-300 dark:border-gray-600 rounded focus:ring-orange-500 dark:bg-gray-700">

                                            <div class="ml-3 flex-1">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                            {{ $shop['name'] }}
                                                        </p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                                            {{ $shop['url'] }}
                                                        </p>
                                                    </div>

                                                    {{-- Shop Status --}}
                                                    <div class="flex items-center">
                                                        @if($shop['connection_status'] === 'connected')
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                                </svg>
                                                                Połączony
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                                </svg>
                                                                Błąd
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    @endif
                                @endforeach
                            </div>

                            @if(count($availableShops) === count($exportedShops))
                                <div class="text-center py-8">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        Produkt jest już dostępny we wszystkich sklepach
                                    </p>
                                </div>
                            @endif
                        </div>

                        {{-- Footer --}}
                        <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 flex justify-end space-x-3">
                            <button type="button"
                                    wire:click="closeShopSelector"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Anuluj
                            </button>
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 transition-colors">
                                Dodaj do wybranych sklepów
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- JavaScript for Status Change Confirmation --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            // Listen for confirmation events
            Livewire.on('confirm-status-change', (event) => {
                const data = event[0] || event;
                const message = data.message;
                const newStatus = data.newStatus;

                if (confirm(message)) {
                    // User confirmed - proceed with status change
                    @this.call('confirmStatusChange', newStatus);
                } else {
                    // User cancelled - keep checkbox in current state
                    const checkbox = document.getElementById('is_active');
                    if (checkbox) {
                        checkbox.checked = !newStatus; // Revert to original state
                    }
                }
            });
        });
    </script>
</div>