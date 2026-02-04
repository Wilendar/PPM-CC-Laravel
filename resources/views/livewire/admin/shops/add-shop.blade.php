<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-black relative">
    
    <!-- Animated Background Elements with MPP TRADE Colors -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-32 -right-32 w-64 h-64 shop-wizard-blob shop-wizard-blob-primary"></div>
        <div class="absolute -bottom-32 -left-32 w-64 h-64 shop-wizard-blob shop-wizard-blob-secondary" style="animation-delay: 2s;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 shop-wizard-blob shop-wizard-blob-center" style="animation-delay: 4s;"></div>
    </div>
    
    <!-- Page Header -->
    <div class="relative backdrop-blur-xl shadow-2xl shop-wizard-header">
        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-12">
            <div class="flex items-center justify-between h-24">
                <div class="flex items-center">
                    <!-- Logo and Title -->
                    <div class="flex-shrink-0">
                        <div class="relative w-12 h-12 rounded-xl flex items-center justify-center shadow-lg shop-wizard-icon">
                            <svg class="w-7 h-7 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <div class="absolute inset-0 rounded-xl shop-wizard-icon-glow"></div>
                        </div>
                    </div>
                    <div class="ml-4 flex-1 min-w-0">
                        <h1 class="text-xl font-bold tracking-tight shop-wizard-title">
                            DODAJ NOWY SKLEP PRESTASHOP
                        </h1>
                        <p class="text-xs font-medium text-gray-400 tracking-wide">
                            {{ $stepDescription }}
                        </p>
                    </div>
                </div>
                
                <!-- Back Button -->
                <a href="{{ route('admin.shops') }}"
                   class="relative inline-flex items-center px-6 py-3 text-sm font-bold rounded-lg text-white transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl shop-wizard-btn-primary">
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
                                @if ($i <= $currentStep) shop-wizard-step-active @else shop-wizard-step-inactive @endif">
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
                            <div class="w-16 h-1 mx-4 rounded transition-all duration-300 @if ($i < $currentStep) shop-wizard-connector-done @else shop-wizard-connector-pending @endif"></div>
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
        <div class="relative backdrop-blur-xl shadow-2xl rounded-xl border p-8 shop-wizard-content">
        
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

                    {{-- ETAP_10: Label Customization --}}
                    <div class="pt-4 border-t border-gray-700">
                        <h4 class="text-sm font-medium text-white mb-3">Personalizacja etykiety</h4>
                        <p class="text-xs text-gray-400 mb-4">Kolor i ikona wyswietlane w kolumnie Powiazania</p>

                        <div class="grid grid-cols-2 gap-4">
                            {{-- Label Color --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-300 mb-2">Kolor etykiety</label>
                                <div class="grid grid-cols-7 gap-1">
                                    @foreach(\App\Models\PrestaShopShop::getAvailableLabelColors() as $color => $name)
                                        <button type="button"
                                                wire:click="$set('labelColor', '{{ $color }}')"
                                                class="w-6 h-6 rounded border-2 transition-all duration-150 {{ ($labelColor ?? '') === $color ? 'border-white scale-110' : 'border-transparent hover:border-gray-500' }}"
                                                style="background-color: {{ $color }}"
                                                title="{{ $name }}">
                                        </button>
                                    @endforeach
                                </div>
                                @if($labelColor)
                                    @php
                                        $iconSvgPaths = [
                                            'database' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4',
                                            'cloud' => 'M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z',
                                            'server' => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01',
                                            'link' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
                                            'cog' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
                                            'cube' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                                            'archive' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
                                            'folder' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
                                            'shopping-cart' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
                                            'tag' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
                                            'briefcase' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                                            'building' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                                            'store' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17',
                                        ];
                                        $iconPath = $iconSvgPaths[$labelIcon] ?? null;
                                    @endphp
                                    <div class="mt-2 flex items-center gap-2">
                                        <span class="text-xs text-gray-400">Wybrany:</span>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs border"
                                              style="background-color: {{ $labelColor }}20; color: {{ $labelColor }}; border-color: {{ $labelColor }}50;">
                                            @if($iconPath)
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/>
                                                </svg>
                                            @endif
                                            {{ $shopName ?: 'Nazwa sklepu' }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            {{-- Label Icon --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-300 mb-2">Ikona etykiety</label>
                                <select wire:model.live="labelIcon"
                                        class="w-full px-3 py-2 text-sm bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:ring-2 focus:ring-[#e0ac7e]">
                                    <option value="">Domyslna (koszyk)</option>
                                    @foreach(\App\Models\PrestaShopShop::getAvailableLabelIcons() as $icon => $name)
                                        <option value="{{ $icon }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
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

            <!-- Step 4: Price Group Mapping + Tax Rules Mapping -->
            @elseif ($currentStep === 4)
                <div class="space-y-6">
                    <div class="text-center mb-6">
                        <h3 class="text-lg font-semibold text-white mb-2">Konfiguracja mapowań</h3>
                        <p class="text-gray-300">Skonfiguruj grupy podatkowe i cenowe PrestaShop</p>
                    </div>

                    {{-- Tax Rules Mapping Section (FAZA 5.1) --}}
                    <div class="tax-rules-mapping-section">
                        <div class="section-header mb-4">
                            <h4 class="text-lg font-semibold text-white flex items-center mb-2">
                                <svg class="w-5 h-5 mr-2 text-[#e0ac7e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                Mapowanie Grup Podatkowych
                            </h4>
                            <p class="text-sm text-gray-300">
                                Wybierz grupy podatkowe PrestaShop odpowiadające stawkom VAT w PPM.
                                <span class="text-red-400 font-medium ml-2">*Stawka 23% jest wymagana</span>
                            </p>
                        </div>

                        {{-- Loading State --}}
                        @if (!isset($taxRulesFetched) || $taxRulesFetched === false)
                            <div class="tax-rules-loading flex items-center justify-center py-6">
                                <svg class="animate-spin w-5 h-5 text-[#e0ac7e] mr-3" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-gray-300">Pobieranie grup podatkowych z PrestaShop...</span>
                            </div>
                        @endif

                        {{-- Error State --}}
                        @error('tax_rules')
                            <div class="alert alert-warning p-4 rounded-lg bg-red-900 bg-opacity-20 border border-red-500 border-opacity-30 flex items-start mb-4">
                                <svg class="w-5 h-5 text-red-400 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm text-red-300">{{ $message }}</p>
                                    <button wire:click="fetchTaxRuleGroups"
                                            class="mt-2 text-sm text-red-200 hover:text-red-100 underline transition-colors duration-200">
                                        Spróbuj ponownie
                                    </button>
                                </div>
                            </div>
                        @enderror

                        {{-- Tax Rules Grid --}}
                        @if (isset($availableTaxRuleGroups) && count($availableTaxRuleGroups) > 0)
                            <div class="tax-rules-grid">
                                {{-- 23% VAT (Required) --}}
                                <div class="tax-rule-item required">
                                    <label for="taxRulesGroup23" class="form-label block text-sm font-medium text-white mb-2">
                                        VAT 23% (Standard) <span class="required-asterisk">*</span>
                                    </label>
                                    <select
                                        wire:model.defer="taxRulesGroup23"
                                        id="taxRulesGroup23"
                                        class="form-select w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200 @error('taxRulesGroup23') border-red-500 @enderror"
                                        required>
                                        <option value="">-- Wybierz grupę --</option>
                                        @foreach ($availableTaxRuleGroups as $group)
                                            <option value="{{ $group['id'] }}">
                                                {{ $group['name'] }} (ID: {{ $group['id'] }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('taxRulesGroup23')
                                        <span class="invalid-feedback text-red-400 text-sm mt-1 block">{{ $message }}</span>
                                    @enderror
                                    @if (isset($taxRulesGroup23) && $taxRulesGroup23)
                                        <span class="selected-indicator text-green-400 text-sm mt-1 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Wybrano
                                        </span>
                                    @endif
                                </div>

                                {{-- 8% VAT (Optional) --}}
                                <div class="tax-rule-item">
                                    <label for="taxRulesGroup8" class="form-label block text-sm font-medium text-white mb-2">
                                        VAT 8% (Obniżona)
                                    </label>
                                    <select
                                        wire:model.defer="taxRulesGroup8"
                                        id="taxRulesGroup8"
                                        class="form-select w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                                        <option value="">-- Wybierz grupę (opcjonalnie) --</option>
                                        @foreach ($availableTaxRuleGroups as $group)
                                            <option value="{{ $group['id'] }}">
                                                {{ $group['name'] }} (ID: {{ $group['id'] }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (isset($taxRulesGroup8) && $taxRulesGroup8)
                                        <span class="selected-indicator text-green-400 text-sm mt-1 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Wybrano
                                        </span>
                                    @endif
                                </div>

                                {{-- 5% VAT (Optional) --}}
                                <div class="tax-rule-item">
                                    <label for="taxRulesGroup5" class="form-label block text-sm font-medium text-white mb-2">
                                        VAT 5% (Super Obniżona)
                                    </label>
                                    <select
                                        wire:model.defer="taxRulesGroup5"
                                        id="taxRulesGroup5"
                                        class="form-select w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                                        <option value="">-- Wybierz grupę (opcjonalnie) --</option>
                                        @foreach ($availableTaxRuleGroups as $group)
                                            <option value="{{ $group['id'] }}">
                                                {{ $group['name'] }} (ID: {{ $group['id'] }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (isset($taxRulesGroup5) && $taxRulesGroup5)
                                        <span class="selected-indicator text-green-400 text-sm mt-1 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Wybrano
                                        </span>
                                    @endif
                                </div>

                                {{-- 0% VAT (Optional) --}}
                                <div class="tax-rule-item">
                                    <label for="taxRulesGroup0" class="form-label block text-sm font-medium text-white mb-2">
                                        VAT 0% (Zwolniona)
                                    </label>
                                    <select
                                        wire:model.defer="taxRulesGroup0"
                                        id="taxRulesGroup0"
                                        class="form-select w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                                        <option value="">-- Wybierz grupę (opcjonalnie) --</option>
                                        @foreach ($availableTaxRuleGroups as $group)
                                            <option value="{{ $group['id'] }}">
                                                {{ $group['name'] }} (ID: {{ $group['id'] }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (isset($taxRulesGroup0) && $taxRulesGroup0)
                                        <span class="selected-indicator text-green-400 text-sm mt-1 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Wybrano
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Info Card --}}
                            <div class="tax-rules-info mt-4 p-4 rounded-lg bg-blue-900 bg-opacity-20 border border-blue-500 border-opacity-30 flex items-start">
                                <svg class="w-5 h-5 text-blue-400 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-sm text-blue-200">Inteligentne domyślne wybory zastosowane na podstawie nazw grup. Możesz je zmienić ręcznie wybierając odpowiednią grupę z listy.</span>
                            </div>
                        @endif
                    </div>

                    {{-- Price Group Mapping Section --}}
                    <div class="price-group-mapping-section mt-8">
                        <div class="section-header mb-4">
                            <h4 class="text-lg font-semibold text-white flex items-center mb-2">
                                <svg class="w-5 h-5 mr-2 text-[#e0ac7e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Mapowanie Grup Cenowych
                            </h4>
                            <p class="text-sm text-gray-300">
                                Połącz grupy cenowe PrestaShop z grupami cenowymi PPM
                            </p>
                        </div>

                    <!-- Fetch Button -->
                    @if (empty($prestashopPriceGroups))
                        <div class="text-center">
                            <button wire:click="fetchPrestashopPriceGroups"
                                    wire:loading.attr="disabled"
                                    wire:target="fetchPrestashopPriceGroups"
                                    class="relative px-6 py-3 text-white rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center mx-auto shop-wizard-btn-primary">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="fetchPrestashopPriceGroups">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" wire:loading wire:target="fetchPrestashopPriceGroups">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span wire:loading.remove wire:target="fetchPrestashopPriceGroups">
                                    Pobierz grupy cenowe z PrestaShop
                                </span>
                                <span wire:loading wire:target="fetchPrestashopPriceGroups">
                                    Pobieram grupy cenowe...
                                </span>
                            </button>
                        </div>
                    @endif

                    <!-- Error Display -->
                    @if ($fetchPriceGroupsError)
                        <div class="bg-red-900 bg-opacity-20 border border-red-500 border-opacity-30 rounded-lg p-4 backdrop-blur-sm">
                            <div class="flex">
                                <svg class="w-5 h-5 text-red-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <h4 class="text-sm font-medium text-red-300 mb-1">Błąd:</h4>
                                    <p class="text-sm text-red-200">{{ $fetchPriceGroupsError }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Mapping Table -->
                    @if (!empty($prestashopPriceGroups))
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-gray-800 bg-opacity-60">
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-white border-b border-gray-600">Grupa PrestaShop</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-white border-b border-gray-600">ID</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-white border-b border-gray-600">Grupa PPM</th>
                                        <th class="px-4 py-3 text-center text-sm font-semibold text-white border-b border-gray-600">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($prestashopPriceGroups as $psGroup)
                                        <tr class="border-b border-gray-700 hover:bg-gray-800 hover:bg-opacity-30 transition-colors">
                                            <td class="px-4 py-3 text-white">{{ $psGroup['name'] }}</td>
                                            <td class="px-4 py-3 text-gray-400 text-sm">#{{ $psGroup['id'] }}</td>
                                            <td class="px-4 py-3">
                                                <select wire:model.defer="priceGroupMappings.{{ $psGroup['id'] }}"
                                                        class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200 text-sm">
                                                    <option value="">-- Wybierz grupę PPM --</option>
                                                    @foreach ($ppmPriceGroups as $ppmGroup)
                                                        <option value="{{ $ppmGroup }}">{{ $ppmGroup }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                @if (!empty($priceGroupMappings[$psGroup['id']]))
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-900 bg-opacity-40 text-green-300 border border-green-500 border-opacity-30">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                        Zmapowane
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-900 bg-opacity-40 text-yellow-300 border border-yellow-500 border-opacity-30">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        Nie zmapowane
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Info Card -->
                        <div class="bg-blue-900 bg-opacity-20 border border-blue-500 border-opacity-30 rounded-lg p-4 backdrop-blur-sm">
                            <div class="flex">
                                <svg class="w-5 h-5 text-blue-400 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <h4 class="text-sm font-medium text-blue-300 mb-1">Mapowanie grup cenowych</h4>
                                    <p class="text-sm text-blue-200">
                                        Mapowanie pozwala na synchronizację cen specjalnych z PrestaShop do odpowiednich grup cenowych w PPM.
                                        Musisz zmapować przynajmniej jedną grupę aby przejść dalej.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                    </div>
                </div>

            <!-- Step 5: Initial Sync Settings (was Step 4) -->
            @elseif ($currentStep === 5)
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

            <!-- Step 6: Advanced Settings (was Step 5) -->
            @elseif ($currentStep === 6)
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

                    <!-- ETAP_07f: CSS/JS Sync Configuration -->
                    <div class="bg-gray-800 bg-opacity-30 border border-gray-600 rounded-lg p-6 backdrop-blur-sm">
                        <h4 class="font-medium text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 text-[#e0ac7e] mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            Synchronizacja CSS/JS (Opis Wizualny)
                        </h4>
                        <p class="text-gray-400 text-sm mb-4">
                            Skanuj pliki CSS/JS ze sklepu PrestaShop przez FTP.
                            Wybrane pliki beda uzywane do wyswietlania opisow produktow w PPM.
                        </p>

                        <!-- FTP/SFTP Configuration (FIRST - required for scanning) -->
                        <div class="mb-6">
                            <label class="flex items-center mb-4">
                                <input type="checkbox"
                                       wire:model.live="enableFtpSync"
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-3 text-sm text-white font-medium">
                                    Konfiguracja FTP/SFTP
                                    <span class="text-yellow-400 text-xs ml-2">(wymagane do skanowania i edycji CSS/JS)</span>
                                </span>
                            </label>

                            @if ($enableFtpSync)
                                <div class="space-y-4 pl-6 border-l-2 border-[#e0ac7e] border-opacity-50 mb-6">
                                    <!-- Protocol & Host -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label for="ftpProtocol" class="block text-sm font-medium text-white mb-2">
                                                Protokol
                                            </label>
                                            <select id="ftpProtocol"
                                                    wire:model.defer="ftpProtocol"
                                                    class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                                                <option value="ftp">FTP</option>
                                                <option value="sftp">SFTP</option>
                                            </select>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label for="ftpHost" class="block text-sm font-medium text-white mb-2">
                                                Host FTP
                                            </label>
                                            <input type="text"
                                                   id="ftpHost"
                                                   wire:model.defer="ftpHost"
                                                   class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200 placeholder-gray-400"
                                                   placeholder="ftp.sklep.pl">
                                        </div>
                                    </div>

                                    <!-- Port & User & Password -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label for="ftpPort" class="block text-sm font-medium text-white mb-2">
                                                Port
                                            </label>
                                            <input type="number"
                                                   id="ftpPort"
                                                   wire:model.defer="ftpPort"
                                                   class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200"
                                                   placeholder="21">
                                        </div>
                                        <div>
                                            <label for="ftpUser" class="block text-sm font-medium text-white mb-2">
                                                Uzytkownik
                                            </label>
                                            <input type="text"
                                                   id="ftpUser"
                                                   wire:model.defer="ftpUser"
                                                   class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200 placeholder-gray-400"
                                                   placeholder="username">
                                        </div>
                                        <div>
                                            <label for="ftpPassword" class="block text-sm font-medium text-white mb-2">
                                                Haslo
                                            </label>
                                            <input type="password"
                                                   id="ftpPassword"
                                                   wire:model.defer="ftpPassword"
                                                   class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200 placeholder-gray-400"
                                                   placeholder="********">
                                            @if ($isEditing)
                                                <p class="text-yellow-400 text-xs mt-1">Pozostaw puste aby zachowac aktualne haslo</p>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Test FTP Connection Button -->
                                    <div class="flex items-center space-x-4">
                                        <button type="button"
                                                wire:click="testFtpConnection"
                                                wire:loading.attr="disabled"
                                                wire:target="testFtpConnection"
                                                class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 flex items-center text-sm">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="testFtpConnection">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                                            </svg>
                                            <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" wire:loading wire:target="testFtpConnection">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Testuj polaczenie FTP
                                        </button>

                                        @if ($ftpConnectionStatus)
                                            <div class="flex items-center">
                                                @if ($ftpConnectionStatus === 'success')
                                                    <svg class="w-5 h-5 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                    <span class="text-green-400 text-sm">{{ $ftpConnectionMessage }}</span>
                                                @elseif ($ftpConnectionStatus === 'error')
                                                    <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                    <span class="text-red-400 text-sm">{{ $ftpConnectionMessage }}</span>
                                                @elseif ($ftpConnectionStatus === 'testing')
                                                    <span class="text-yellow-400 text-sm">{{ $ftpConnectionMessage }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Scan Button (requires FTP config) -->
                        <div class="mb-6 border-t border-gray-600 pt-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <button type="button"
                                        wire:click="scanCssJsFiles"
                                        wire:loading.attr="disabled"
                                        wire:target="scanCssJsFiles"
                                        class="px-5 py-2.5 bg-[#e0ac7e] text-white rounded-lg hover:bg-[#d1975a] transition-colors duration-200 flex items-center text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                                        @if(!$enableFtpSync || empty($ftpHost) || empty($ftpUser) || empty($ftpPassword)) disabled @endif>
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="scanCssJsFiles">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" wire:loading wire:target="scanCssJsFiles">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span wire:loading.remove wire:target="scanCssJsFiles">Skanuj pliki CSS/JS ze sklepu</span>
                                    <span wire:loading wire:target="scanCssJsFiles">Skanowanie...</span>
                                </button>

                                {{-- CSS Editor Button (ETAP_07h FAZA 8) - only in edit mode with FTP configured --}}
                                @if($isEditing && $editingShopId && $enableFtpSync && !empty($ftpHost) && !empty($ftpUser))
                                    <a href="{{ route('admin.shops.css-editor', ['shopId' => $editingShopId]) }}"
                                       class="px-5 py-2.5 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 flex items-center text-sm font-medium border border-gray-600">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                        </svg>
                                        Edytuj CSS
                                    </a>
                                @endif
                            </div>
                            @if(!$enableFtpSync || empty($ftpHost) || empty($ftpUser) || empty($ftpPassword))
                                <p class="text-yellow-400 text-xs mt-2">Wypelnij dane FTP powyzej aby skanowac pliki CSS/JS.</p>
                            @endif
                        </div>

                        <!-- Scan Status Message -->
                        @if($scanStatus)
                            <div class="mb-4 p-3 rounded-lg @if($scanStatus === 'success') bg-green-900 bg-opacity-20 border border-green-500 border-opacity-30 @elseif($scanStatus === 'error') bg-red-900 bg-opacity-20 border border-red-500 border-opacity-30 @else bg-blue-900 bg-opacity-20 border border-blue-500 border-opacity-30 @endif">
                                <div class="flex items-center">
                                    @if($scanStatus === 'success')
                                        <svg class="w-5 h-5 text-green-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span class="text-green-300 text-sm">{{ $scanMessage }}</span>
                                    @elseif($scanStatus === 'error')
                                        <svg class="w-5 h-5 text-red-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        <span class="text-red-300 text-sm">{{ $scanMessage }}</span>
                                    @else
                                        <svg class="w-5 h-5 text-blue-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span class="text-blue-300 text-sm">{{ $scanMessage }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Scanned CSS Files List -->
                        @if(count($scannedCssFiles) > 0)
                            <div class="mb-6">
                                <h5 class="text-sm font-medium text-white mb-3 flex items-center">
                                    <svg class="w-4 h-4 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Pliki CSS ({{ count(array_filter($scannedCssFiles, fn($f) => $f['enabled'] ?? false)) }}/{{ count($scannedCssFiles) }} wlaczonych)
                                </h5>
                                <div class="space-y-2 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
                                    @foreach($scannedCssFiles as $index => $file)
                                        <label class="flex items-start p-3 rounded-lg bg-gray-700 bg-opacity-40 hover:bg-opacity-60 transition-colors cursor-pointer border border-transparent hover:border-gray-500">
                                            <input type="checkbox"
                                                   wire:click="toggleCssFile('{{ $file['url'] }}')"
                                                   @if($file['enabled'] ?? false) checked @endif
                                                   class="rounded border-gray-500 bg-gray-800 text-[#e0ac7e] focus:ring-[#e0ac7e] mt-0.5">
                                            <div class="ml-3 flex-1 min-w-0">
                                                <span class="text-white text-sm block truncate">{{ $file['filename'] ?? basename($file['url']) }}</span>
                                                <span class="text-gray-400 text-xs block truncate">{{ $file['url'] }}</span>
                                                <div class="flex items-center mt-1 space-x-2">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                        @if(($file['category'] ?? 'other') === 'theme') bg-purple-900 bg-opacity-50 text-purple-300
                                                        @elseif(($file['category'] ?? 'other') === 'custom') bg-green-900 bg-opacity-50 text-green-300
                                                        @elseif(($file['category'] ?? 'other') === 'module') bg-blue-900 bg-opacity-50 text-blue-300
                                                        @else bg-gray-700 text-gray-300 @endif">
                                                        {{ ucfirst($file['category'] ?? 'other') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Scanned JS Files List -->
                        @if(count($scannedJsFiles) > 0)
                            <div class="mb-6">
                                <h5 class="text-sm font-medium text-white mb-3 flex items-center">
                                    <svg class="w-4 h-4 text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                    </svg>
                                    Pliki JS ({{ count(array_filter($scannedJsFiles, fn($f) => $f['enabled'] ?? false)) }}/{{ count($scannedJsFiles) }} wlaczonych)
                                </h5>
                                <div class="space-y-2 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
                                    @foreach($scannedJsFiles as $index => $file)
                                        <label class="flex items-start p-3 rounded-lg bg-gray-700 bg-opacity-40 hover:bg-opacity-60 transition-colors cursor-pointer border border-transparent hover:border-gray-500">
                                            <input type="checkbox"
                                                   wire:click="toggleJsFile('{{ $file['url'] }}')"
                                                   @if($file['enabled'] ?? false) checked @endif
                                                   class="rounded border-gray-500 bg-gray-800 text-[#e0ac7e] focus:ring-[#e0ac7e] mt-0.5">
                                            <div class="ml-3 flex-1 min-w-0">
                                                <span class="text-white text-sm block truncate">{{ $file['filename'] ?? basename($file['url']) }}</span>
                                                <span class="text-gray-400 text-xs block truncate">{{ $file['url'] }}</span>
                                                <div class="flex items-center mt-1 space-x-2">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                        @if(($file['category'] ?? 'other') === 'theme') bg-purple-900 bg-opacity-50 text-purple-300
                                                        @elseif(($file['category'] ?? 'other') === 'custom') bg-green-900 bg-opacity-50 text-green-300
                                                        @elseif(($file['category'] ?? 'other') === 'module') bg-blue-900 bg-opacity-50 text-blue-300
                                                        @else bg-gray-700 text-gray-300 @endif">
                                                        {{ ucfirst($file['category'] ?? 'other') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Performance & Reliability -->
                    <div class="bg-gray-800 bg-opacity-30 border border-gray-600 rounded-lg p-6 backdrop-blur-sm">
                        <h4 class="font-medium text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 text-[#e0ac7e] mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Wydajnosc i niezawodnosc
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="syncBatchSize" class="block text-sm font-medium text-white mb-2">
                                    Wielkosc paczki synchronizacji
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
                            class="relative px-6 py-3 text-white rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center shop-wizard-btn-primary">
                        Następny krok
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                @else
                    <button wire:click="saveShop"
                            class="relative px-8 py-3 text-white rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center shop-wizard-btn-success">
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