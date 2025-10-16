{{-- ProductForm Component - Advanced Product Create/Edit Form --}}
{{-- CSS loaded via admin layout --}}

<div class="category-form-container">
<div class="w-full py-4">
    {{-- Header Section --}}
    <div class="mb-6 px-4 xl:px-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-dark-primary mb-2">
                    @if($isEditMode)
                        <i class="fas fa-edit text-mpp-orange mr-2"></i>
                        Edytuj produkt
                    @else
                        <i class="fas fa-plus-circle text-green-400 mr-2"></i>
                        Nowy produkt
                    @endif
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb-dark flex items-center space-x-2 text-sm">
                        <li>
                            <a href="{{ route('admin.dashboard') }}" class="hover:text-mpp-orange">
                                <i class="fas fa-home"></i> Panel administracyjny
                            </a>
                        </li>
                        <li class="text-dark-muted">></li>
                        <li>
                            <a href="{{ route('admin.products.index') }}" class="hover:text-mpp-orange">
                                <i class="fas fa-box"></i> Produkty
                            </a>
                        </li>
                        <li class="text-dark-muted">></li>
                        <li class="text-dark-secondary">
                            @if($isEditMode)
                                Edycja: {{ $name ?? 'Produkt' }}
                            @else
                                Nowy produkt
                            @endif
                        </li>
                    </ol>
                </nav>
            </div>
            <div class="flex gap-4">
                @if($hasUnsavedChanges)
                    <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        Niezapisane zmiany
                    </span>
                @endif
                <a href="{{ route('admin.products.index') }}"
                   class="btn-enterprise-secondary">
                    <i class="fas fa-times"></i>
                    Anuluj
                </a>
            </div>
        </div>
    </div>

    {{-- Messages --}}
    @if (session()->has('message'))
        <div class="alert-dark-success flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert-dark-error flex items-center">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ session('error') }}
        </div>
    @endif

    @if($successMessage)
        <div x-data="{ show: true }"
             x-show="show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             class="alert-dark-success flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                {{ $successMessage }}
            </div>
            <button @click="show = false" class="ml-4">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    {{-- Main Form --}}
    <form wire:submit.prevent="save">
        <div class="category-form-main-container">
            {{-- Left Column - Form Content --}}
            <div class="category-form-left-column">
                <div class="enterprise-card p-8">
                    {{-- Enterprise Tab Navigation --}}
                    <div class="tabs-enterprise">
                        <button class="tab-enterprise {{ $activeTab === 'basic' ? 'active' : '' }}"
                                type="button"
                                wire:click="switchTab('basic')">
                            <i class="fas fa-info-circle icon"></i>
                            <span>Informacje podstawowe</span>
                        </button>

                        <button class="tab-enterprise {{ $activeTab === 'description' ? 'active' : '' }}"
                                type="button"
                                wire:click="switchTab('description')">
                            <i class="fas fa-align-left icon"></i>
                            <span>Opisy i SEO</span>
                        </button>

                        <button class="tab-enterprise {{ $activeTab === 'physical' ? 'active' : '' }}"
                                type="button"
                                wire:click="switchTab('physical')">
                            <i class="fas fa-box icon"></i>
                            <span>Właściwości fizyczne</span>
                        </button>

                        <button class="tab-enterprise {{ $activeTab === 'attributes' ? 'active' : '' }}"
                                type="button"
                                wire:click="switchTab('attributes')">
                            <i class="fas fa-tags icon"></i>
                            <span>Atrybuty</span>
                        </button>
                    </div>

                {{-- MULTI-STORE MANAGEMENT (Second Line) --}}
                {{-- Dostępne zarówno w create jak i edit mode --}}
                    <div class="mt-3 bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    Zarządzanie sklepami
                                </h4>

                                {{-- Default Data Toggle --}}
                                <button type="button"
                                        wire:click="switchToShop(null)"
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
                                <button type="button"
                                        wire:click="openShopSelector"
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
                                    @foreach($exportedShops as $shopId)
                                        @php
                                            $shop = collect($availableShops)->firstWhere('id', $shopId);
                                        @endphp
                                        @if($shop)
                                            <div wire:key="shop-label-{{ $shopId }}" class="inline-flex items-center group">
                                                @php
                                                    $syncDisplay = $this->getSyncStatusDisplay($shop['id']);
                                                @endphp

                                                {{-- Shop Button - ETAP_07 FIX: Auto-load data on click --}}
                                                <button type="button"
                                                        wire:click="switchToShop({{ $shop['id'] }})"
                                                        wire:loading.attr="disabled"
                                                        wire:key="shop-btn-{{ $shop['id'] }}"
                                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-l-lg transition-all duration-200 {{ $activeShopId === $shop['id'] ? 'bg-gradient-to-r from-orange-500 to-orange-600 text-white shadow-md' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600' }}">
                                                    {{-- Shop Connection Status Icon --}}
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

                                                    {{-- Sync Status Badge - ENHANCED --}}
                                                    <span class="inline-flex items-center ml-2 px-2 py-0.5 rounded text-xs font-medium {{ $syncDisplay['class'] }}">
                                                        {{ $syncDisplay['icon'] }} {{ $syncDisplay['text'] }}
                                                    </span>

                                                    {{-- PrestaShop ID badge (if exists) --}}
                                                    @if($syncDisplay['prestashop_id'])
                                                        <span class="ml-1 text-xs text-gray-500 dark:text-gray-400 font-mono">
                                                            #{{ $syncDisplay['prestashop_id'] }}
                                                        </span>
                                                    @endif
                                                </button>

                                                {{-- Visibility Toggle --}}
                                                <button type="button"
                                                        wire:click="toggleShopVisibility({{ $shop['id'] }})"
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

                                                {{-- Delete from PrestaShop Button (Physical Delete) --}}
                                                <button type="button"
                                                        wire:click="deleteFromPrestaShop({{ $shop['id'] }})"
                                                        wire:confirm="Czy na pewno FIZYCZNIE USUNĄĆ produkt ze sklepu PrestaShop? Ta operacja jest nieodwracalna!"
                                                        title="Usuń fizycznie w sklepie PrestaShop"
                                                        class="px-2 py-1.5 text-xs bg-red-700 hover:bg-red-800 text-white transition-all duration-200 opacity-0 group-hover:opacity-100">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                    </svg>
                                                </button>

                                                {{-- Remove Association Button (Local only) --}}
                                                <button type="button"
                                                        wire:click="removeFromShop({{ $shop['id'] }})"
                                                        wire:confirm="Czy na pewno usunąć powiązanie z tego sklepu? (produkt pozostanie w PrestaShop)"
                                                        title="Usuń powiązanie (produkt pozostanie w sklepie)"
                                                        class="px-2 py-1.5 text-xs bg-orange-500 hover:bg-orange-600 text-white rounded-r-lg transition-all duration-200 opacity-0 group-hover:opacity-100">
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

                {{-- BASIC INFORMATION TAB --}}
                <div class="{{ $activeTab === 'basic' ? '' : 'hidden' }}">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Informacje podstawowe</h3>

                        <div class="flex items-center space-x-3">
                            {{-- Load from PrestaShop Button (ETAP_07 FIX) --}}
                            @if($activeShopId && $isEditMode)
                                <button type="button"
                                        wire:click="loadProductDataFromPrestaShop({{ $activeShopId }}, true)"
                                        wire:loading.attr="disabled"
                                        wire:target="loadProductDataFromPrestaShop"
                                        class="btn-enterprise-secondary text-sm inline-flex items-center space-x-1"
                                        title="Wczytaj ponownie dane produktu z PrestaShop">
                                    <span wire:loading.remove wire:target="loadProductDataFromPrestaShop">🔄</span>
                                    <span wire:loading wire:target="loadProductDataFromPrestaShop">⏳</span>
                                    <span wire:loading.remove wire:target="loadProductDataFromPrestaShop">Wczytaj z PrestaShop</span>
                                    <span wire:loading wire:target="loadProductDataFromPrestaShop">Wczytywanie...</span>
                                </button>
                            @endif

                            {{-- Active Shop Indicator --}}
                            @if($activeShopId !== null && isset($availableShops))
                                @php
                                    $currentShop = collect($availableShops)->firstWhere('id', $activeShopId);
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Edytujesz: {{ $currentShop['name'] ?? 'Nieznany sklep' }}
                                </span>
                            @endif
                        </div>
                    </div> {{-- Close flex items-center justify-between mb-6 (line 246) --}}

                    {{-- SYNC STATUS PANEL - DETAILED (NOWY KOMPONENT) --}}
                    @if($activeShopId && $isEditMode)
                        @php
                            $syncDisplay = $this->getSyncStatusDisplay($activeShopId);
                            $syncStatus = $this->getSyncStatusForShop($activeShopId);
                            $currentShop = collect($availableShops)->firstWhere('id', $activeShopId);
                        @endphp

                        <div class="mb-4 p-4 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-800 shadow-sm">
                            <div class="flex items-center justify-between">
                                {{-- Status Info --}}
                                <div class="flex items-center space-x-3">
                                    <span class="text-2xl">{{ $syncDisplay['icon'] }}</span>
                                    <div>
                                        <h4 class="font-semibold {{ $syncDisplay['class'] }}">
                                            Status synchronizacji: {{ $syncDisplay['text'] }}
                                        </h4>

                                        @if($syncDisplay['prestashop_id'])
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                PrestaShop ID: <strong class="font-mono">#{{ $syncDisplay['prestashop_id'] }}</strong>
                                            </p>
                                        @else
                                            <p class="text-sm text-gray-500 dark:text-gray-500">
                                                Produkt nie został jeszcze zsynchronizowany z tym sklepem
                                            </p>
                                        @endif

                                        @if(isset($syncDisplay['last_sync']))
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                Ostatnia synchronizacja: {{ $syncDisplay['last_sync'] }}
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                {{-- Action Buttons --}}
                                <div class="flex space-x-2">
                                    @if($syncDisplay['status'] === 'error')
                                        <button type="button"
                                                wire:click="retrySync({{ $activeShopId }})"
                                                class="btn-enterprise-secondary text-sm inline-flex items-center"
                                                title="Ponów synchronizację">
                                            🔄 Ponów
                                        </button>
                                    @endif

                                    @if($syncDisplay['prestashop_id'])
                                        @php
                                            // ETAP_07 FIX: Get correct frontend URL (not admin URL)
                                            $prestashopUrl = $this->getProductPrestaShopUrl($activeShopId);
                                        @endphp
                                        @if($prestashopUrl)
                                            <a href="{{ $prestashopUrl }}"
                                               target="_blank"
                                               rel="noopener noreferrer"
                                               class="btn-enterprise-secondary text-sm inline-flex items-center"
                                               title="Otwórz produkt w PrestaShop (frontend)">
                                                🔗 PrestaShop
                                            </a>
                                        @else
                                            {{-- Fallback to admin URL if frontend URL not available --}}
                                            <a href="{{ $currentShop['url'] }}/admin-dev/index.php?controller=AdminProducts&id_product={{ $syncDisplay['prestashop_id'] }}"
                                               target="_blank"
                                               rel="noopener noreferrer"
                                               class="btn-enterprise-secondary text-sm inline-flex items-center"
                                               title="Otwórz produkt w PrestaShop (admin)">
                                                🔗 PrestaShop (admin)
                                            </a>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            {{-- Error Message Display --}}
                            @if($syncDisplay['status'] === 'error' && isset($syncDisplay['error_message']))
                                <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded">
                                    <p class="text-sm text-red-700 dark:text-red-400">
                                        <strong>Błąd:</strong> {{ $syncDisplay['error_message'] }}
                                    </p>
                                    @if(isset($syncDisplay['retry_count']))
                                        <p class="text-xs text-red-600 dark:text-red-500 mt-1">
                                            Liczba prób: {{ $syncDisplay['retry_count'] }} / 3
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- SKU Field --}}
                        <div class="md:col-span-1">
                            <label for="sku" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                SKU produktu <span class="text-red-500">*</span>
                                @php
                                            $skuIndicator = $this->getFieldStatusIndicator('sku');
                                        @endphp
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
                                @php
                                            $typeIndicator = $this->getFieldStatusIndicator('product_type_id');
                                        @endphp
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
                                @php
                                            $nameIndicator = $this->getFieldStatusIndicator('name');
                                        @endphp
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
                                    @php
                                            $slugIndicator = $this->getFieldStatusIndicator('slug');
                                        @endphp
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
                                @php
                                            $manufacturerIndicator = $this->getFieldStatusIndicator('manufacturer');
                                        @endphp
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
                                @php
                                            $supplierIndicator = $this->getFieldStatusIndicator('supplier_code');
                                        @endphp
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
                                @php
                                            $eanIndicator = $this->getFieldStatusIndicator('ean');
                                        @endphp
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
                                            @php
                                            $status = $product->getPublishingStatus();
                                        @endphp
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
                                @php
                                            $categoryIndicator = $this->getCategoryStatusIndicator();
                                        @endphp
                                @if($categoryIndicator['show'])
                                    <span class="ml-2 {{ $categoryIndicator['class'] }}">
                                        {{ $categoryIndicator['text'] }}
                                    </span>
                                @endif
                            </label>

                            {{-- Category Conflict Warning Banner - ADDED 2025-10-13 --}}
                            @if($this->hasCategoryConflict)
                                <div class="mb-4 p-4 bg-orange-900/20 border border-orange-500/50 rounded-lg">
                                    <div class="flex items-start gap-3">
                                        <svg class="w-5 h-5 text-orange-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        <div class="flex-1">
                                            <h4 class="text-sm font-bold text-orange-300 mb-1">Konflikt Struktury Kategorii</h4>
                                            <p class="text-xs text-orange-200/90 mb-3">
                                                Ten produkt ma różne kategorie na tym sklepie niż w danych domyślnych. Musisz wybrać którą strukturę zachować.
                                            </p>
                                            <button type="button"
                                                    wire:click="$dispatch('showCategoryConflict', {productId: {{ $product->id }}, shopId: {{ $activeShopId }}})"
                                                    class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold rounded-lg transition-colors duration-200 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                                                </svg>
                                                Rozwiąż Konflikt Kategorii
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @php
                                            $availableCategories = $this->getAvailableCategories();
                                        @endphp
                            @if($availableCategories && $availableCategories->count() > 0)
                                <div class="{{ $this->getCategoryClasses() }} max-h-64 overflow-y-auto" wire:key="categories-ctx-{{ $activeShopId ?? 'default' }}">
                                    @foreach($availableCategories as $rootCategory)
                                        @include('livewire.products.management.partials.category-tree-item', [
                                            'category' => $rootCategory,
                                            'level' => 0,
                                            'context' => $activeShopId ?? 'default'
                                        ])
                                    @endforeach
                                </div>

                                @if($this->getCategoriesForContext($activeShopId))
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        Wybrano {{ count($this->getCategoriesForContext($activeShopId)) }} {{ count($this->getCategoriesForContext($activeShopId)) == 1 ? 'kategorię' : 'kategori' }}.
                                        @if($this->getPrimaryCategoryForContext($activeShopId))
                                            Główna: <strong>{{ $availableCategories->find($this->getPrimaryCategoryForContext($activeShopId))?->name }}</strong>
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
                            @php
                                $currentShop = collect($availableShops)->firstWhere('id', $activeShopId);
                            @endphp
                            <div class="flex items-center">
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Edytujesz: {{ $currentShop['name'] ?? 'Nieznany sklep' }}
                                </span>
                            </div>
                        @endif
                    </div> {{-- Close flex items-center justify-between mb-6 (line 635) --}}

                    <div class="space-y-6">
                        {{-- Short Description --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label for="short_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Krótki opis
                                    {{-- Status indicator --}}
                                    @php
                                            $shortDescIndicator = $this->getFieldStatusIndicator('short_description');
                                        @endphp
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
                                    @php
                                            $longDescIndicator = $this->getFieldStatusIndicator('long_description');
                                        @endphp
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
                                        @php
                                            $metaTitleIndicator = $this->getFieldStatusIndicator('meta_title');
                                        @endphp
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
                                        @php
                                            $metaDescIndicator = $this->getFieldStatusIndicator('meta_description');
                                        @endphp
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
                            @php
                                $currentShop = collect($availableShops)->firstWhere('id', $activeShopId);
                            @endphp
                            <div class="flex items-center">
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Edytujesz: {{ $currentShop['name'] ?? 'Nieznany sklep' }}
                                </span>
                            </div>
                        @endif
                    </div> {{-- Close flex items-center justify-between mb-6 (line 765) --}}

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Dimensions Section --}}
                        <div class="md:col-span-2">
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Wymiary</h4>

                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                {{-- Height --}}
                                <div>
                                    <label for="height" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Wysokość (cm)
                                        @php
                                            $heightIndicator = $this->getFieldStatusIndicator('height');
                                        @endphp
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
                                        @php
                                            $widthIndicator = $this->getFieldStatusIndicator('width');
                                        @endphp
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
                                        @php
                                            $lengthIndicator = $this->getFieldStatusIndicator('length');
                                        @endphp
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
                                @php
                                            $weightIndicator = $this->getFieldStatusIndicator('weight');
                                        @endphp
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
                                @php
                                            $taxRateIndicator = $this->getFieldStatusIndicator('tax_rate');
                                        @endphp
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

                {{-- ATTRIBUTES TAB CONTENT --}}
                <div class="{{ $activeTab === 'attributes' ? '' : 'hidden' }}">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            <svg class="w-6 h-6 inline mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            Atrybuty produktu
                        </h3>

                        {{-- Active Shop Indicator --}}
                        @if($activeShopId !== null && isset($availableShops))
                            @php
                                $currentShop = collect($availableShops)->firstWhere('id', $activeShopId);
                            @endphp
                            <div class="flex items-center">
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Edytujesz: {{ $currentShop['name'] ?? 'Nieznany sklep' }}
                                </span>
                            </div>
                        @endif
                    </div> {{-- Close flex items-center justify-between mb-6 (line 936) --}}

                    <div class="grid grid-cols-1 gap-6">
                        {{-- Attributes Management per Shop --}}
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-md font-medium text-gray-900 dark:text-white flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    Cechy i parametry produktu
                                </h4>
                            </div>

                            {{-- Attributes List --}}
                            <div class="space-y-4">
                                {{-- Placeholder for attributes --}}
                                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">System atrybutów</h3>
                                    <p class="text-sm mb-4">
                                        Zarządzaj atrybutami produktu takimi jak Model, Oryginał, Zamiennik, Kolor, Rozmiar.
                                        <br>Każdy sklep może mieć różne wartości atrybutów.
                                    </p>
                                    <div class="text-xs bg-blue-50 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded-lg p-3">
                                        <strong>Nadchodząca funkcja:</strong> Interfejs zarządzania atrybutami będzie dostępny w najbliższej aktualizacji.
                                        Backend jest już przygotowany dla systemu EAV (Entity-Attribute-Value).
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                {{-- Form Footer - ALL ACTION BUTTONS MOVED HERE --}}
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


                            {{-- Reset to Defaults Button - Only show when there are unsaved changes --}}
                            @if($hasUnsavedChanges)
                                <button wire:click="resetToDefaults"
                                        wire:loading.attr="disabled"
                                        wire:target="resetToDefaults"
                                        class="px-4 py-2 border border-yellow-500 dark:border-yellow-400 text-sm font-medium rounded-lg text-yellow-700 dark:text-yellow-300 bg-yellow-50 dark:bg-yellow-900/20 hover:bg-yellow-100 dark:hover:bg-yellow-900/40 transition-colors duration-200">
                                    <div wire:loading.remove wire:target="resetToDefaults">
                                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Przywróć domyślne
                                    </div>
                                    <div wire:loading wire:target="resetToDefaults">
                                        <span class="inline-block w-4 h-4 mr-2 border-2 border-current border-t-transparent rounded-full animate-spin"></span>
                                        Przywracanie...
                                    </div>
                                </button>
                            @endif

                            {{-- Enhanced Sync Button - Context-aware name and functionality --}}
                            <button wire:click="syncToShops"
                                    wire:loading.attr="disabled"
                                    wire:target="syncToShops"
                                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:bg-purple-400 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                <div wire:loading.remove wire:target="syncToShops">
                                    <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    @if($activeShopId === null)
                                        Aktualizuj na wszystkich sklepach
                                    @else
                                        Zaktualizuj na sklepie
                                    @endif
                                </div>
                                <div wire:loading wire:target="syncToShops">
                                    <span class="inline-block w-4 h-4 mr-2 border-2 border-current border-t-transparent rounded-full animate-spin"></span>
                                    @if($activeShopId === null)
                                        Aktualizowanie wszystkich sklepów...
                                    @else
                                        Aktualizowanie sklepu...
                                    @endif
                                </div>
                            </button>

                            {{-- Save All Changes Button - Only show when there are pending changes --}}
                            @if($hasUnsavedChanges)
                                <button wire:click="saveAllPendingChanges"
                                        wire:loading.attr="disabled"
                                        wire:target="saveAllPendingChanges"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                    <div wire:loading.remove wire:target="saveAllPendingChanges">
                                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                        </svg>
                                        Zapisz wszystkie zmiany
                                    </div>
                                    <div wire:loading wire:target="saveAllPendingChanges">
                                        <span class="inline-block w-4 h-4 mr-2 border-2 border-current border-t-transparent rounded-full animate-spin"></span>
                                        Zapisywanie wszystkich zmian...
                                    </div>
                                </button>
                            @endif

                            <button wire:click="saveAndClose"
                                    wire:loading.attr="disabled"
                                    wire:target="saveAndClose"
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                <div wire:loading.remove wire:target="saveAndClose">
                                    <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ $isEditMode ? 'Zapisz i Zamknij' : 'Zapisz' }}
                                </div>
                                <div wire:loading wire:target="saveAndClose">
                                    <span class="inline-block w-4 h-4 mr-2 border-2 border-current border-t-transparent rounded-full animate-spin"></span>
                                    Zapisywanie...
                                </div>
                            </button>
                        </div> {{-- Close action buttons flex --}}
                    </div> {{-- Close form footer flex wrapper --}}
                </div> {{-- Close form footer bg-gray-50 --}}
            </div> {{-- Close category-form-left-column --}}

            {{-- Right Column - Quick Actions & Info --}}
            <div class="category-form-right-column">
                {{-- Quick Actions Panel --}}
                <div class="enterprise-card p-6">
                    <h4 class="text-lg font-bold text-dark-primary mb-6 flex items-center">
                        <i class="fas fa-bolt text-mpp-orange mr-2"></i>
                        Szybkie akcje
                    </h4>
                    <div class="space-y-4">
                        {{-- Save Button --}}
                        <button wire:click="saveAndClose"
                                class="btn-enterprise-primary w-full py-3 text-lg"
                                wire:loading.attr="disabled"
                                wire:target="saveAndClose">
                            <span wire:loading.remove wire:target="saveAndClose">
                                <i class="fas fa-save mr-3"></i>
                                {{ $isEditMode ? 'Zapisz zmiany' : 'Utwórz produkt' }}
                            </span>
                            <span wire:loading wire:target="saveAndClose">
                                <i class="fas fa-spinner fa-spin mr-3"></i>
                                Zapisywanie...
                            </span>
                        </button>

                        {{-- Sync to Shops Button (Edit Mode) --}}
                        @if($isEditMode && !empty($exportedShops))
                            <button wire:click="syncToShops"
                                    class="btn-enterprise-secondary w-full py-3"
                                    wire:loading.attr="disabled"
                                    wire:target="syncToShops">
                                <span wire:loading.remove wire:target="syncToShops">
                                    <i class="fas fa-sync-alt mr-2"></i>
                                    Synchronizuj sklepy
                                </span>
                                <span wire:loading wire:target="syncToShops">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>
                                    Synchronizacja...
                                </span>
                            </button>
                        @endif

                        {{-- Cancel Button --}}
                        <a href="{{ route('admin.products.index') }}"
                           class="btn-enterprise-secondary w-full py-3">
                            <i class="fas fa-times mr-2"></i>
                            Anuluj i wróć
                        </a>
                    </div>
                </div>

                {{-- Product Info (Edit Mode) --}}
                @if($isEditMode)
                    <div class="enterprise-card p-6 mt-6">
                        <h4 class="text-lg font-bold text-dark-primary mb-6 flex items-center">
                            <i class="fas fa-info-circle text-blue-400 mr-2"></i>
                            Informacje o produkcie
                        </h4>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between items-center py-2 border-b border-gray-700">
                                <span class="text-dark-muted">SKU:</span>
                                <span class="text-dark-primary font-semibold">{{ $sku }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-700">
                                <span class="text-dark-muted">Status:</span>
                                <span class="text-dark-primary">
                                    @if($is_active ?? false)
                                        <i class="fas fa-check-circle text-green-400 mr-1"></i> Aktywny
                                    @else
                                        <i class="fas fa-times-circle text-red-400 mr-1"></i> Nieaktywny
                                    @endif
                                </span>
                            </div>
                            @if(!empty($exportedShops))
                                <div class="flex justify-between items-center py-2 border-b border-gray-700">
                                    <span class="text-dark-muted">Sklepy:</span>
                                    <span class="text-dark-primary font-semibold">{{ count($exportedShops) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div> {{-- Close category-form-main-container --}}
    </form> {{-- Close form --}}


    {{-- SHOP SELECTOR MODAL --}}
    @if($showShopSelector)
        <div class="fixed inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="z-index: 9999 !important;">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-4 text-center sm:p-0">
                {{-- Background overlay --}}
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" wire:click="closeShopSelector" style="z-index: 9998 !important;"></div>

                {{-- Modal content --}}
                <div class="inline-block align-middle bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative" style="z-index: 10000 !important;">
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
                            {{-- Error Messages --}}
                            @if($errors->has('general'))
                                <div class="mb-4 bg-red-100 dark:bg-red-900/20 border border-red-400 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg">
                                    {{ $errors->first('general') }}
                                </div>
                            @endif

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
</div> {{-- Close w-full py-4 ROOT ELEMENT --}}
</div> {{-- Close category-form-container --}}

{{-- JavaScript section - moved outside root element like CategoryForm --}}
@push('scripts')
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

    // Prevent accidental navigation with unsaved changes
    window.addEventListener('beforeunload', (e) => {
        try {
            // Use Livewire 3.x API
            const component = window.Livewire?.find('{{ $this->getId() }}');
            if (component?.get('hasUnsavedChanges')) {
                e.preventDefault();
                e.returnValue = 'Masz niezapisane zmiany. Czy na pewno chcesz opuścić stronę?';
            }
        } catch (error) {
            // Fallback: Skip the check if Livewire API is not available
            console.log('Livewire beforeunload check skipped:', error);
        }
    });
});
</script>
@endpush
