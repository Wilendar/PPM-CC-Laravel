<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-black relative">
    
    <!-- Animated Background Elements with MPP TRADE Colors -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-32 -right-32 w-64 h-64 rounded-full blur-3xl animate-pulse" style="background: radial-gradient(circle, rgba(224, 172, 126, 0.1), rgba(209, 151, 90, 0.05));"></div>
        <div class="absolute -bottom-32 -left-32 w-64 h-64 rounded-full blur-3xl animate-pulse" style="background: radial-gradient(circle, rgba(209, 151, 90, 0.1), rgba(224, 172, 126, 0.05)); animation-delay: 2s;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 rounded-full blur-3xl animate-pulse" style="background: radial-gradient(circle, rgba(224, 172, 126, 0.05), rgba(209, 151, 90, 0.03)); animation-delay: 4s;"></div>
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <div class="absolute inset-0 rounded-xl opacity-75 blur animate-pulse" style="background: linear-gradient(45deg, #e0ac7e, #d1975a);"></div>
                        </div>
                    </div>
                    <div class="ml-4 flex-1 min-w-0">
                        <h1 class="text-xl font-bold tracking-tight" style="color: #e0ac7e !important;">
                            DODAJ NOWY SKLEP PRESTASHOP
                        </h1>
                        <p class="text-xs font-medium text-gray-400 tracking-wide">
                            {{ $stepDescription }}
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

    <!-- Content Section with proper spacing -->
    <div class="relative z-10 max-w-7xl mx-auto px-6 sm:px-8 lg:px-12 py-8">

        <!-- Progress Bar -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-white">Krok {{ $currentStep }} z {{ $totalSteps }}</span>
                <span class="text-sm text-gray-400">{{ number_format($progressPercentage, 0) }}% ukończone</span>
            </div>
            <div class="w-full bg-gray-700 rounded-full h-3 shadow-inner">
                <div class="bg-gradient-to-r from-[#e0ac7e] to-[#d1975a] h-3 rounded-full transition-all duration-500 ease-out shadow-lg" 
                     style="width: {{ $progressPercentage }}%"></div>
            </div>
        </div>

        <!-- Step Indicators -->
        <div class="flex items-center justify-center mb-12">
            <div class="flex items-center space-x-4">
                @for ($i = 1; $i <= $totalSteps; $i++)
                    <div class="flex items-center">
                        <div class="relative">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center font-semibold transition-all duration-300 shadow-lg
                                @if ($i <= $currentStep) text-white @else text-gray-400 @endif"
                                style="@if ($i <= $currentStep) background: linear-gradient(45deg, #e0ac7e, #d1975a); @else background: rgba(75, 85, 99, 0.6); border: 2px solid rgba(156, 163, 175, 0.3); @endif">
                                @if ($i < $currentStep)
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                @else
                                    {{ $i }}
                                @endif
                            </div>
                            <div class="absolute -bottom-8 left-1/2 transform -translate-x-1/2 text-xs font-medium text-gray-300 whitespace-nowrap">
                                {{ $this->getStepTitle($i) }}
                            </div>
                        </div>
                        @if ($i < $totalSteps)
                            <div class="w-16 h-1 mx-4 rounded transition-all duration-300"
                                style="@if ($i < $currentStep) background: linear-gradient(90deg, #e0ac7e, #d1975a); @else background: rgba(75, 85, 99, 0.6); @endif"></div>
                        @endif
                    </div>
                @endfor
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
                        <h3 class="text-sm font-medium text-red-300 mb-2">Błędy walidacji:</h3>
                        <ul class="text-sm text-red-200 list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Main Content Card -->
        <div class="relative backdrop-blur-xl shadow-2xl rounded-xl border p-8" 
             style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(224, 172, 126, 0.3);">
        
        <!-- Step Content -->
        <div class="mb-8">
            
            <!-- Step 1: Basic Info -->
            @if ($currentStep === 1)
                <div class="space-y-6">
                    <div>
                        <label for="shopName" class="block text-sm font-medium text-white mb-2">
                            Nazwa sklepu *
                        </label>
                        <input type="text" 
                               id="shopName"
                               wire:model.defer="shopName" 
                               class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200 placeholder-gray-400" 
                               placeholder="np. MPP Trade - Sklep główny">
                        @error('shopName') 
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p> 
                        @enderror
                    </div>

                    <div>
                        <label for="shopUrl" class="block text-sm font-medium text-white mb-2">
                            URL sklepu *
                        </label>
                        <input type="url" 
                               id="shopUrl"
                               wire:model.defer="shopUrl" 
                               class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200 placeholder-gray-400" 
                               placeholder="https://sklep.mpptrade.pl">
                        @error('shopUrl') 
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p> 
                        @enderror
                    </div>

                    <div>
                        <label for="shopDescription" class="block text-sm font-medium text-white mb-2">
                            Opis sklepu
                        </label>
                        <textarea id="shopDescription"
                                  wire:model.defer="shopDescription" 
                                  rows="4"
                                  class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200 placeholder-gray-400" 
                                  placeholder="Opcjonalny opis sklepu dla łatwiejszej identyfikacji..."></textarea>
                        @error('shopDescription') 
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p> 
                        @enderror
                    </div>
                </div>

            <!-- Step 2: API Credentials -->
            @elseif ($currentStep === 2)
                <div class="space-y-6">
                    <div>
                        <label for="prestashopVersion" class="block text-sm font-medium text-white mb-2">
                            Wersja PrestaShop *
                        </label>
                        <select id="prestashopVersion"
                                wire:model.defer="prestashopVersion" 
                                class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                            <option value="8">PrestaShop 8.x</option>
                            <option value="9">PrestaShop 9.x</option>
                        </select>
                        @error('prestashopVersion') 
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p> 
                        @enderror
                    </div>

                    <div>
                        <label for="apiKey" class="block text-sm font-medium text-white mb-2">
                            Klucz API *
                        </label>
                        <input type="text" 
                               id="apiKey"
                               wire:model.defer="apiKey" 
                               class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200 font-mono placeholder-gray-400" 
                               placeholder="Wklej tutaj klucz API z panelu administracyjnego PrestaShop">
                        @error('apiKey') 
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p> 
                        @enderror
                    </div>

                    <div>
                        <label for="apiSecret" class="block text-sm font-medium text-white mb-2">
                            Sekret API (opcjonalnie)
                        </label>
                        <input type="password" 
                               id="apiSecret"
                               wire:model.defer="apiSecret" 
                               class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200 font-mono placeholder-gray-400" 
                               placeholder="Sekret API (jeśli wymagany)">
                        <p class="text-gray-400 text-sm mt-1">Niektóre konfiguracje PrestaShop wymagają dodatkowego sekretu API</p>
                    </div>

                    <!-- API Info Card -->
                    <div class="bg-blue-900 bg-opacity-20 border border-blue-500 border-opacity-30 rounded-lg p-4 backdrop-blur-sm">
                        <div class="flex">
                            <svg class="w-5 h-5 text-blue-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <h4 class="text-sm font-medium text-blue-300 mb-1">Jak uzyskać klucz API?</h4>
                                <p class="text-sm text-blue-200">
                                    Przejdź do Panelu Administracyjnego PrestaShop → Zaawansowane parametry → Webservice → 
                                    Dodaj nowy klucz z uprawnieniami do Products, Categories, Orders.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- Step 3: Connection Test -->
            @elseif ($currentStep === 3)
                <div class="space-y-6">
                    <!-- Connection Status -->
                    <div class="text-center">
                        @if ($connectionStatus === 'testing')
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-yellow-900 bg-opacity-40 rounded-full mb-4">
                                <svg class="animate-spin w-8 h-8 text-yellow-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-white mb-2">Testowanie połączenia...</h3>
                            
                        @elseif ($connectionStatus === 'success')
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-green-900 bg-opacity-40 rounded-full mb-4">
                                <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-green-300 mb-2">Połączenie pomyślne!</h3>
                            
                        @elseif ($connectionStatus === 'error')
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-red-900 bg-opacity-40 rounded-full mb-4">
                                <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-red-300 mb-2">Błąd połączenia</h3>
                            
                        @else
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-800 bg-opacity-40 rounded-full mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-white mb-2">Gotowy do testowania</h3>
                        @endif
                        
                        <p class="text-gray-300 mb-6">{{ $connectionMessage }}</p>
                    </div>

                    <!-- Test Connection Button -->
                    @if ($connectionStatus !== 'testing')
                        <div class="text-center">
                            <button wire:click="testConnection" 
                                    class="px-6 py-3 bg-[#e0ac7e] text-white rounded-lg hover:bg-[#d1975a] transition-colors duration-200 font-medium">
                                @if ($connectionStatus) Testuj ponownie @else Testuj połączenie @endif
                            </button>
                        </div>
                    @endif

                    <!-- Diagnostics -->
                    @if (!empty($diagnostics))
                        <div class="space-y-3">
                            <h4 class="font-medium text-white">Szczegóły diagnostyki:</h4>
                            @foreach ($diagnostics as $diagnostic)
                                <div class="flex items-start p-4 rounded-lg backdrop-blur-sm
                                    @if ($diagnostic['status'] === 'success') bg-green-900 bg-opacity-20 border border-green-500 border-opacity-30
                                    @elseif ($diagnostic['status'] === 'error') bg-red-900 bg-opacity-20 border border-red-500 border-opacity-30
                                    @else bg-yellow-900 bg-opacity-20 border border-yellow-500 border-opacity-30 @endif">
                                    
                                    <div class="flex-shrink-0 mr-3 mt-0.5">
                                        @if ($diagnostic['status'] === 'success')
                                            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        @elseif ($diagnostic['status'] === 'error')
                                            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        @endif
                                    </div>
                                    
                                    <div class="flex-1">
                                        <h5 class="font-medium 
                                            @if ($diagnostic['status'] === 'success') text-green-300
                                            @elseif ($diagnostic['status'] === 'error') text-red-300
                                            @else text-yellow-300 @endif">
                                            {{ $diagnostic['check'] }}
                                        </h5>
                                        <p class="text-sm 
                                            @if ($diagnostic['status'] === 'success') text-green-200
                                            @elseif ($diagnostic['status'] === 'error') text-red-200
                                            @else text-yellow-200 @endif mt-1">
                                            {{ $diagnostic['message'] }}
                                        </p>
                                        @if (!empty($diagnostic['details']))
                                            <p class="text-xs 
                                                @if ($diagnostic['status'] === 'success') text-green-400
                                                @elseif ($diagnostic['status'] === 'error') text-red-400
                                                @else text-yellow-400 @endif mt-1">
                                                {{ $diagnostic['details'] }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            <!-- Step 4: Initial Sync Settings -->
            @elseif ($currentStep === 4)
                <div class="space-y-6">
                    <div>
                        <label for="syncFrequency" class="block text-sm font-medium text-white mb-2">
                            Częstotliwość synchronizacji
                        </label>
                        <select id="syncFrequency"
                                wire:model.defer="syncFrequency" 
                                class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                            <option value="real-time">Czasie rzeczywistym (webhook)</option>
                            <option value="hourly">Co godzinę</option>
                            <option value="daily">Raz dziennie</option>
                            <option value="manual">Tylko ręcznie</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-white mb-4">
                            Zakres synchronizacji
                        </label>
                        <div class="space-y-3">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       wire:model.defer="syncProducts" 
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-3 text-sm text-white">Produkty (nazwa, opis, cena, dostępność)</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       wire:model.defer="syncCategories" 
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-3 text-sm text-white">Kategorie (struktura, nazwy, opisy)</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       wire:model.defer="syncPrices" 
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-3 text-sm text-white">Ceny (wszystkie grupy cenowe)</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   wire:model.defer="autoSyncEnabled" 
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-3 text-sm text-white">Włącz automatyczną synchronizację</span>
                        </label>
                        <p class="text-gray-400 text-sm mt-1 ml-6">
                            Jeśli wyłączone, synchronizacja będzie możliwa tylko ręcznie z panelu administracyjnego
                        </p>
                    </div>

                    <!-- Summary Card -->
                    <div class="bg-gray-800 bg-opacity-40 border border-gray-600 rounded-lg p-6 backdrop-blur-sm">
                        <h4 class="font-medium text-white mb-4">Podsumowanie konfiguracji:</h4>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Sklep:</dt>
                                <dd class="font-medium text-white">{{ $shopName }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-400">URL:</dt>
                                <dd class="font-medium text-white">{{ $shopUrl }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Wersja PrestaShop:</dt>
                                <dd class="font-medium text-white">{{ $prestashopVersion }}.x</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Synchronizacja:</dt>
                                <dd class="font-medium text-white">{{ ucfirst(str_replace('-', ' ', $syncFrequency)) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Status połączenia:</dt>
                                <dd class="font-medium 
                                    @if ($connectionStatus === 'success') text-green-400
                                    @elseif ($connectionStatus === 'error') text-red-400
                                    @else text-yellow-400 @endif">
                                    @if ($connectionStatus === 'success') ✓ Połączono pomyślnie
                                    @elseif ($connectionStatus === 'error') ✗ Błąd połączenia
                                    @else ⚠ Nie testowano @endif
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

            <!-- Step 5: Advanced Settings -->
            @elseif ($currentStep === 5)
                <div class="space-y-6">
                    
                    <!-- Conflict Resolution -->
                    <div class="bg-gray-800 bg-opacity-30 border border-gray-600 rounded-lg p-6 backdrop-blur-sm">
                        <h4 class="font-medium text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 text-[#e0ac7e] mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Rozwiązywanie konfliktów
                        </h4>
                        <div>
                            <label for="conflictResolution" class="block text-sm font-medium text-white mb-2">
                                Strategia rozwiązywania konfliktów danych
                            </label>
                            <select id="conflictResolution"
                                    wire:model.defer="conflictResolution" 
                                    class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                                <option value="ppm_wins">PPM ma pierwszeństwo (zalecane)</option>
                                <option value="prestashop_wins">PrestaShop ma pierwszeństwo</option>
                                <option value="newest_wins">Najnowsze zmiany wygrywają</option>
                                <option value="manual">Manualne rozwiązywanie konfliktów</option>
                            </select>
                            <p class="text-gray-400 text-xs mt-1">Określa jak system ma postępować gdy te same dane różnią się między PPM a PrestaShop</p>
                        </div>
                    </div>

                    <!-- Extended Sync Options -->
                    <div class="bg-gray-800 bg-opacity-30 border border-gray-600 rounded-lg p-6 backdrop-blur-sm">
                        <h4 class="font-medium text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 text-[#e0ac7e] mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Rozszerzone opcje synchronizacji
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       wire:model.defer="syncStock" 
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-3 text-sm text-white">Synchronizuj stany magazynowe</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       wire:model.defer="syncOrders" 
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-3 text-sm text-white">Synchronizuj zamówienia</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       wire:model.defer="syncCustomers" 
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-3 text-sm text-white">Synchronizuj klientów</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       wire:model.defer="syncMetaData" 
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-3 text-sm text-white">Synchronizuj metadane SEO</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       wire:model.defer="syncOnlyActiveProducts" 
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-3 text-sm text-white">Tylko aktywne produkty</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       wire:model.defer="preserveLocalImages" 
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-3 text-sm text-white">Zachowaj lokalne zdjęcia</span>
                            </label>
                        </div>
                    </div>

                    <!-- Performance & Reliability -->
                    <div class="bg-gray-800 bg-opacity-30 border border-gray-600 rounded-lg p-6 backdrop-blur-sm">
                        <h4 class="font-medium text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 text-[#e0ac7e] mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Wydajność i niezawodność
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="syncBatchSize" class="block text-sm font-medium text-white mb-2">
                                    Wielkość paczki synchronizacji
                                </label>
                                <input type="number" 
                                       id="syncBatchSize"
                                       wire:model.defer="syncBatchSize" 
                                       min="1" max="500"
                                       class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                                <p class="text-gray-400 text-xs mt-1">Ile produktów synchronizować jednocześnie (1-500)</p>
                            </div>
                            
                            <div>
                                <label for="syncTimeoutMinutes" class="block text-sm font-medium text-white mb-2">
                                    Timeout synchronizacji (minuty)
                                </label>
                                <input type="number" 
                                       id="syncTimeoutMinutes"
                                       wire:model.defer="syncTimeoutMinutes" 
                                       min="5" max="180"
                                       class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                                <p class="text-gray-400 text-xs mt-1">Maksymalny czas na jedną operację sync</p>
                            </div>
                            
                            <div>
                                <label for="maxRetryAttempts" class="block text-sm font-medium text-white mb-2">
                                    Maksymalne próby powtórzenia
                                </label>
                                <input type="number" 
                                       id="maxRetryAttempts"
                                       wire:model.defer="maxRetryAttempts" 
                                       min="1" max="10"
                                       class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                                <p class="text-gray-400 text-xs mt-1">Ile razy powtórzyć nieudaną synchronizację</p>
                            </div>
                            
                            <div class="flex items-center">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           wire:model.defer="retryFailedSyncs" 
                                           class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                    <span class="ml-3 text-sm text-white">Automatyczne ponawianie nieudanych sync</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications & Webhooks -->
                    <div class="bg-gray-800 bg-opacity-30 border border-gray-600 rounded-lg p-6 backdrop-blur-sm">
                        <h4 class="font-medium text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 text-[#e0ac7e] mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5-5 5-5h-5m-6 10v3a2 2 0 01-2 2H5a2 2 0 01-2-2v-3m10 0h.01M19 12a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v7z"></path>
                            </svg>
                            Powiadomienia i webhooks
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       wire:model.defer="notifyOnSyncErrors" 
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-3 text-sm text-white">Powiadamiaj o błędach synchronizacji</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       wire:model.defer="notifyOnSyncComplete" 
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-3 text-sm text-white">Powiadamiaj o ukończeniu sync</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       wire:model.defer="enableWebhooks" 
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-3 text-sm text-white">Włącz webhooks (real-time sync)</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       wire:model.defer="realTimeSyncEnabled" 
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-3 text-sm text-white">Synchronizacja w czasie rzeczywistym</span>
                            </label>
                        </div>
                        <div class="mt-4 p-4 bg-blue-900 bg-opacity-20 border border-blue-500 border-opacity-30 rounded-lg">
                            <p class="text-sm text-blue-200">
                                <strong>Info:</strong> Webhooks umożliwiają natychmiastową synchronizację gdy dane się zmienią. 
                                Wymaga dodatkowej konfiguracji w PrestaShop.
                            </p>
                        </div>
                    </div>

                    <!-- Final Summary -->
                    <div class="bg-gray-800 bg-opacity-40 border border-gray-600 rounded-lg p-6 backdrop-blur-sm">
                        <h4 class="font-medium text-white mb-4">Ostateczne podsumowanie konfiguracji:</h4>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <dt class="text-gray-400">Sklep:</dt>
                                    <dd class="font-medium text-white">{{ $shopName }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-400">URL:</dt>
                                    <dd class="font-medium text-white">{{ Str::limit($shopUrl, 30) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-400">Wersja:</dt>
                                    <dd class="font-medium text-white">PrestaShop {{ $prestashopVersion }}.x</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-400">Synchronizacja:</dt>
                                    <dd class="font-medium text-white">{{ ucfirst(str_replace('-', ' ', $syncFrequency)) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-400">Konflikty:</dt>
                                    <dd class="font-medium text-white">{{ ucfirst(str_replace('_', ' ', $conflictResolution)) }}</dd>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <dt class="text-gray-400">Batch size:</dt>
                                    <dd class="font-medium text-white">{{ $syncBatchSize }} produktów</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-400">Timeout:</dt>
                                    <dd class="font-medium text-white">{{ $syncTimeoutMinutes }} minut</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-400">Retry:</dt>
                                    <dd class="font-medium text-white">{{ $retryFailedSyncs ? $maxRetryAttempts . ' prób' : 'Wyłączone' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-400">Powiadomienia:</dt>
                                    <dd class="font-medium text-white">{{ $notifyOnSyncErrors ? 'Włączone' : 'Wyłączone' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-400">Real-time:</dt>
                                    <dd class="font-medium text-white">{{ $enableWebhooks ? 'Webhooks' : 'Batch sync' }}</dd>
                                </div>
                            </div>
                        </dl>
                    </div>
                </div>
            @endif
        </div>

        <!-- Navigation Buttons -->
        <div class="flex items-center justify-between pt-6 border-t border-gray-600">
            <div>
                @if ($currentStep > 1)
                    <button wire:click="previousStep" 
                            class="px-6 py-2 bg-gray-700 bg-opacity-60 text-gray-300 rounded-lg hover:bg-gray-600 hover:bg-opacity-80 transition-colors duration-200 flex items-center border border-gray-600">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Poprzedni krok
                    </button>
                @endif
            </div>

            <div class="flex space-x-3">
                @if ($currentStep < $totalSteps)
                    <button wire:click="nextStep" 
                            class="relative px-6 py-3 text-white rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center font-medium"
                            style="background: linear-gradient(45deg, rgba(224, 172, 126, 0.8), rgba(209, 151, 90, 0.8)); border: 1px solid rgba(224, 172, 126, 0.5);"
                            onmouseover="this.style.background='linear-gradient(45deg, rgba(209, 151, 90, 0.9), rgba(194, 132, 73, 0.9))'"
                            onmouseout="this.style.background='linear-gradient(45deg, rgba(224, 172, 126, 0.8), rgba(209, 151, 90, 0.8))'">
                        Następny krok
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                @else
                    <button wire:click="saveShop" 
                            class="relative px-8 py-3 text-white rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center font-medium"
                            style="background: linear-gradient(45deg, rgba(34, 197, 94, 0.8), rgba(22, 163, 74, 0.8)); border: 1px solid rgba(34, 197, 94, 0.5);"
                            onmouseover="this.style.background='linear-gradient(45deg, rgba(22, 163, 74, 0.9), rgba(21, 128, 61, 0.9))'"
                            onmouseout="this.style.background='linear-gradient(45deg, rgba(34, 197, 94, 0.8), rgba(22, 163, 74, 0.8))'">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Zapisz sklep
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>