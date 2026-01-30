{{-- resources/views/livewire/products/management/tabs/basic-tab.blade.php --}}
<div class="tab-content active space-y-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-white">Informacje podstawowe</h3>

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
                <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-orange-900/30 text-orange-200 border border-orange-700/50">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Edytujesz: {{ $currentShop['name'] ?? 'Nieznany sklep' }}
                </span>
            @endif
        </div>
    </div>

    {{-- SYNC STATUS PANEL - DETAILED (NOWY KOMPONENT) --}}
    @if($activeShopId && $isEditMode)
        @php
            $syncDisplay = $this->getSyncStatusDisplay($activeShopId);
            $syncStatus = $this->getSyncStatusForShop($activeShopId);
            $currentShop = collect($availableShops)->firstWhere('id', $activeShopId);
        @endphp

        <div class="mb-4 p-4 border border-gray-600 rounded-lg bg-gray-800 shadow-sm">
            <div class="flex items-center justify-between">
                {{-- Status Info --}}
                <div class="flex items-center space-x-3">
                    <span class="text-2xl">{{ $syncDisplay['icon'] }}</span>
                    <div>
                        <h4 class="font-semibold {{ $syncDisplay['class'] }}">
                            Status synchronizacji: {{ $syncDisplay['text'] }}
                        </h4>

                        @if($syncDisplay['prestashop_id'])
                            <p class="text-sm text-gray-400">
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
                    @else
                        {{-- FIX 2025-11-18: Przycisk "Dodaj do sklepu" dla nie-zsynchronizowanych produktów --}}
                        <button type="button"
                                wire:click="syncShop({{ $activeShopId }})"
                                class="btn-enterprise-primary text-sm inline-flex items-center"
                                :disabled="$wire.activeJobStatus === 'pending' || $wire.activeJobStatus === 'processing'"
                                title="Dodaj produkt do tego sklepu">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Dodaj do sklepu
                        </button>
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

            {{-- COLLAPSIBLE: Szczegóły synchronizacji (FAZA 9.4 Refactor) --}}
            @php
                $shopData = $product->shopData->where('shop_id', $activeShopId)->first();
            @endphp

            @if($shopData)
                <div class="shop-details-collapsible" x-data="{ expanded: false }">
                    <button
                        @click="expanded = !expanded"
                        class="collapsible-header"
                        type="button"
                    >
                        <span class="text-sm font-medium text-gray-300">Szczegóły synchronizacji</span>
                        <svg x-show="!expanded" class="w-4 h-4 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                        <svg x-show="expanded" class="w-4 h-4 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                        </svg>
                    </button>

                    <div x-show="expanded" x-collapse class="collapsible-content">
                        {{-- Shop Info - kompaktowa --}}
                        <div class="shop-info-compact">
                            <p class="text-sm text-gray-400">
                                <strong class="text-gray-300">Sklep:</strong>
                                {{ $shopData->shop->name }}
                            </p>
                            <p class="text-sm text-gray-400">
                                <strong class="text-gray-300">External ID:</strong>
                                {{ $shopData->prestashop_product_id ?? 'Nie zsynchronizowane' }}
                            </p>
                            {{-- ETAP_13.3: Updated Timestamps (pull/push) --}}
                            <p class="text-sm text-gray-400">
                                <strong class="text-gray-300">Ostatnie wczytanie danych:</strong>
                                {{ $shopData->getTimeSinceLastPull() }}
                            </p>
                            <p class="text-sm text-gray-400">
                                <strong class="text-gray-300">Ostatnia aktualizacja sklepu:</strong>
                                {{ $shopData->getTimeSinceLastPush() }}
                            </p>
                        </div>

                        {{-- ETAP_13.3: Oczekujące zmiany - DYNAMIC (getPendingChangesForShop) --}}
                        @php
                            $pendingChanges = $this->getPendingChangesForShop($shopData->shop_id);
                        @endphp

                        @if(!empty($pendingChanges))
                            <div class="pending-changes-compact mt-3 p-3 bg-yellow-900 bg-opacity-20 rounded border border-yellow-700">
                                <h5 class="text-sm font-semibold text-yellow-300 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    Oczekujące zmiany ({{ count($pendingChanges) }})
                                </h5>
                                <ul class="compact-list space-y-1">
                                    @foreach($pendingChanges as $fieldLabel)
                                        <li class="text-sm text-yellow-200 flex items-center">
                                            <i class="fas fa-circle text-yellow-400 mr-2" style="font-size: 0.4rem;"></i>
                                            {{ $fieldLabel }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <p class="text-sm text-green-400 mt-3">
                                <i class="fas fa-check-circle mr-2"></i>
                                Wszystkie dane zsynchronizowane
                            </p>
                        @endif

                        {{-- Validation Warnings (jeśli są) --}}
                        @if($shopData->has_validation_warnings && !empty($shopData->validation_warnings))
                            <div class="validation-warnings-compact">
                                <h5 class="text-sm font-semibold text-gray-300 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    Ostrzeżenia walidacji ({{ count($shopData->validation_warnings) }})
                                </h5>
                                @foreach($shopData->validation_warnings as $warning)
                                    <div class="warning-compact severity-{{ $warning['severity'] ?? 'info' }}">
                                        <p class="text-sm">{{ $warning['message'] ?? 'Nieznane ostrzeżenie' }}</p>
                                        @if(isset($warning['ppm_value']) || isset($warning['prestashop_value']))
                                            <div class="text-xs text-gray-500 mt-1">
                                                PPM: {{ $warning['ppm_value'] ?? 'N/A' }} → PrestaShop: {{ $warning['prestashop_value'] ?? 'N/A' }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Actions - Per-Shop Buttons (ETAP_13) --}}
                        <div class="shop-actions-compact">
                            @include('livewire.products.management.partials.actions.shop-sync-button', [
                                'shopId' => $shopData->shop_id
                            ])
                            @include('livewire.products.management.partials.actions.shop-pull-button', [
                                'shopId' => $shopData->shop_id
                            ])
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- SKU Field --}}
        <div class="md:col-span-1">
            <label for="sku" class="block text-sm font-medium text-gray-300 mb-2">
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
            <label for="product_type_id" class="block text-sm font-medium text-gray-300 mb-2">
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
            <label for="name" class="block text-sm font-medium text-gray-300 mb-2">
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

        {{-- Sekcja "Informacje rozszerzone (ERP)" - domyslnie zwinieta (FAZA 4.1-4.2) --}}
        <div class="md:col-span-2 mt-2 border border-gray-700 rounded-lg overflow-hidden">
            {{-- Header kliknij aby zwinac/rozwinac --}}
            <button type="button"
                    wire:click="toggleExtendedInfo"
                    class="w-full flex items-center justify-between px-4 py-3 bg-gray-800/50 hover:bg-gray-800 transition-colors">
                <span class="text-sm font-medium text-gray-300">
                    Informacje rozszerzone (ERP)
                </span>
                <svg class="w-5 h-5 text-gray-400 transition-transform duration-200 {{ $extendedInfoExpanded ? 'rotate-180' : '' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            {{-- Content --}}
            @if($extendedInfoExpanded)
            <div class="px-4 py-4 bg-gray-900/30 border-t border-gray-700">

                {{-- Slug URL (przeniesiony tutaj) --}}
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <label for="slug" class="block text-sm font-medium text-gray-300">
                            Slug URL (opcjonalne)
                        </label>
                        <button wire:click="toggleSlugField"
                                type="button"
                                class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-500">
                            {{ $showSlugField ? 'Ukryj' : 'Pokaz' }} slug
                        </button>
                    </div>
                    @if($showSlugField)
                        <div class="space-y-1">
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
                            Automatycznie: <code class="bg-gray-700 px-2 py-1 rounded">{{ $slug ?: 'automatycznie-generowany-slug' }}</code>
                        </p>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-4">
                    {{-- Kod CN --}}
                    <div>
                        <label for="cnCode" class="block text-sm font-medium text-gray-300 mb-1">Kod CN</label>
                        <input type="text"
                               wire:model="cnCode"
                               id="cnCode"
                               maxlength="50"
                               placeholder="np. 8714 91 30"
                               class="form-input-enterprise w-full">
                    </div>

                    {{-- Material --}}
                    <div>
                        <label for="material" class="block text-sm font-medium text-gray-300 mb-1">Material</label>
                        <input type="text"
                               wire:model="material"
                               id="material"
                               maxlength="50"
                               placeholder="np. stal, aluminium"
                               class="form-input-enterprise w-full">
                    </div>

                    {{-- Symbol z wada --}}
                    <div>
                        <label for="defectSymbol" class="block text-sm font-medium text-gray-300 mb-1">Symbol z wada</label>
                        <input type="text"
                               wire:model="defectSymbol"
                               id="defectSymbol"
                               maxlength="50"
                               placeholder="np. DEFECT-001"
                               class="form-input-enterprise w-full">
                    </div>

                    {{-- Zastosowanie --}}
                    <div class="col-span-2">
                        <label for="application" class="block text-sm font-medium text-gray-300 mb-1">Zastosowanie</label>
                        <input type="text"
                               wire:model="application"
                               id="application"
                               maxlength="255"
                               placeholder="np. Motocykle, Rowery, ATV"
                               class="form-input-enterprise w-full">
                    </div>
                </div>

                {{-- Switche --}}
                <div class="mt-4 flex flex-wrap gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox"
                               wire:model="shopInternet"
                               class="checkbox-enterprise">
                        <span class="text-sm text-gray-300">Sklep internetowy</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox"
                               wire:model="splitPayment"
                               class="checkbox-enterprise">
                        <span class="text-sm text-gray-300">Mechanizm podzielonej platnosci</span>
                    </label>
                </div>
            </div>
            @endif
        </div>

        {{-- Dostawca (BusinessPartner dropdown) --}}
        <div class="md:col-span-1">
            <label for="supplier_id" class="block text-sm font-medium text-gray-300 mb-2">
                Dostawca
                @php $supplierBpIndicator = $this->getFieldStatusIndicator('supplier_id'); @endphp
                @if($supplierBpIndicator['show'])
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $supplierBpIndicator['class'] }}">
                        {{ $supplierBpIndicator['text'] }}
                    </span>
                @endif
            </label>
            <select wire:model.live="supplier_id" id="supplier_id"
                    class="{{ $this->getFieldClasses('supplier_id') }} @error('supplier_id') !border-red-500 @enderror">
                <option value="">-- brak --</option>
                @foreach($this->suppliersForDropdown as $bp)
                    <option value="{{ $bp->id }}">{{ $bp->name }}</option>
                @endforeach
            </select>
            @error('supplier_id')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Producent (BusinessPartner dropdown) --}}
        <div class="md:col-span-1">
            <label for="manufacturer_id" class="block text-sm font-medium text-gray-300 mb-2">
                Producent
                @php $mfgIndicator = $this->getFieldStatusIndicator('manufacturer_id'); @endphp
                @if($mfgIndicator['show'])
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $mfgIndicator['class'] }}">
                        {{ $mfgIndicator['text'] }}
                    </span>
                @endif
            </label>
            <select wire:model.live="manufacturer_id" id="manufacturer_id"
                    class="{{ $this->getFieldClasses('manufacturer_id') }} @error('manufacturer_id') !border-red-500 @enderror">
                <option value="">-- brak --</option>
                @foreach($this->manufacturersForDropdown as $bp)
                    <option value="{{ $bp->id }}">{{ $bp->name }}</option>
                @endforeach
            </select>
            @error('manufacturer_id')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Importer (BusinessPartner dropdown) --}}
        <div class="md:col-span-1">
            <label for="importer_id" class="block text-sm font-medium text-gray-300 mb-2">
                Importer
                @php $impIndicator = $this->getFieldStatusIndicator('importer_id'); @endphp
                @if($impIndicator['show'])
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $impIndicator['class'] }}">
                        {{ $impIndicator['text'] }}
                    </span>
                @endif
            </label>
            <select wire:model.live="importer_id" id="importer_id"
                    class="{{ $this->getFieldClasses('importer_id') }} @error('importer_id') !border-red-500 @enderror">
                <option value="">-- brak --</option>
                @foreach($this->importersForDropdown as $bp)
                    <option value="{{ $bp->id }}">{{ $bp->name }}</option>
                @endforeach
            </select>
            @error('importer_id')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Supplier Code --}}
        <div class="md:col-span-1">
            <label for="supplier_code" class="block text-sm font-medium text-gray-300 mb-2">
                Kod dostawcy
                @php $supplierCodeIndicator = $this->getFieldStatusIndicator('supplier_code'); @endphp
                @if($supplierCodeIndicator['show'])
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $supplierCodeIndicator['class'] }}">
                        {{ $supplierCodeIndicator['text'] }}
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
            <label for="ean" class="block text-sm font-medium text-gray-300 mb-2">
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
            <label for="sort_order" class="block text-sm font-medium text-gray-300 mb-2">
                Kolejność sortowania
            </label>
            <input wire:model.live="sort_order"
                   type="number"
                   id="sort_order"
                   min="0"
                   class="{{ $this->getFieldClasses('sort_order') }} @error('sort_order') !border-red-500 @enderror">
            @error('sort_order')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- ========== ETAP_07d: SEKCJA STAWKA VAT + STATUS + HARMONOGRAM + ZDJECIE GLOWNE ========== --}}
        {{-- Grid 2-kolumnowy wewnatrz glownego grida: Lewa (VAT+Status+Harmonogram), Prawa (Zdjecie glowne) --}}
        @if($isEditMode && $product && $product->id)
        <div class="md:col-span-2 grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- LEWA KOLUMNA: STAWKA VAT + Status produktu + Harmonogram publikacji --}}
            <div class="space-y-6">
            {{-- Tax Rate Field - RELOCATED FROM PHYSICAL TAB (FAZA 5.2 - 2025-11-14) --}}
            <div>
                <label for="tax_rate" class="form-label-enterprise">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                @if($activeShopId === null)
                    Stawka VAT
                @else
                    @php
                        $currentShop = collect($availableShops)->firstWhere('id', $activeShopId);
                    @endphp
                    Stawka VAT dla {{ $currentShop['name'] ?? 'sklepu' }}
                @endif
                <span class="text-red-400">*</span>

                {{-- STATUS INDICATOR --}}
                @php
                    $indicator = $this->getTaxRateIndicator($activeShopId);
                @endphp
                @if($indicator['show'])
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $indicator['class'] }}">
                        {{ $indicator['text'] }}
                    </span>
                @endif
            </label>

            {{-- DROPDOWN: Tax Rate Selection --}}
            <select wire:model.live="selectedTaxRateOption"
                    wire:key="tax-rate-{{ $activeShopId ?? 'default' }}"
                    id="tax_rate"
                    class="{{ $this->getFieldClasses('tax_rate') }} @error('tax_rate') !border-red-500 @enderror">

                @if($activeShopId === null)
                    {{-- DEFAULT MODE: Standard rates + Custom --}}
                    <option value="23.00">VAT 23% (Standard)</option>
                    <option value="8.00">VAT 8% (Obniżony)</option>
                    <option value="5.00">VAT 5% (Obniżony)</option>
                    <option value="0.00">VAT 0% (Zwolniony)</option>
                    <option value="custom" class="tax-option-custom">Własna stawka...</option>
                @else
                    {{-- SHOP MODE: Inherit + PrestaShop rules + Custom --}}
                    @php
                        $defaultRate = $this->tax_rate ?? 23.00;
                    @endphp
                    <option value="use_default" class="tax-option-default">✓ Użyj domyślnej PPM ({{ number_format($defaultRate, 2) }}%)</option>

                    {{-- PrestaShop Tax Rules (if mapped) --}}
                    @if(isset($availableTaxRuleGroups[$activeShopId]))
                        @foreach($availableTaxRuleGroups[$activeShopId] as $taxRule)
                            {{-- Skip rates that match default to avoid duplicates (e.g., 23% shown twice) --}}
                            @if(number_format($taxRule['rate'], 2, '.', '') != number_format($defaultRate, 2, '.', ''))
                                <option value="{{ number_format($taxRule['rate'], 2, '.', '') }}" class="tax-option-mapped">
                                    VAT {{ number_format($taxRule['rate'], 2) }}%
                                    (PrestaShop: {{ $taxRule['label'] }})
                                </option>
                            @endif
                        @endforeach
                    @endif

                    <option value="custom" class="tax-option-custom">Własna stawka...</option>
                @endif
            </select>

            {{-- CONDITIONAL: Custom Tax Rate Input --}}
            @if($selectedTaxRateOption === 'custom')
                <input wire:model.live="customTaxRate"
                       type="number"
                       step="0.01"
                       min="0"
                       max="100"
                       placeholder="Wpisz stawkę VAT (np. 23.00)"
                       class="mt-2 {{ $this->getFieldClasses('tax_rate') }} @error('customTaxRate') !border-red-500 @enderror">
                @error('customTaxRate')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            @endif

            {{-- HELP TEXT (Shop Mode) --}}
            @if($activeShopId !== null)
                <p class="mt-2 text-xs text-gray-400">
                    <svg class="w-4 h-4 inline mr-1 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Wybierz zmapowaną regułę podatkową PrestaShop lub własną stawkę dla tego sklepu.
                </p>
            @endif

            @error('tax_rate')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror

            {{-- VALIDATION WARNING (if rate not mapped) --}}
            @if($activeShopId !== null && $indicator['show'] && isset($indicator['type']) && $indicator['type'] === 'different')
                <p class="text-yellow-400 text-xs mt-1">
                    ⚠️ Ta stawka nie jest zmapowana w konfiguracji sklepu. Synchronizacja może się nie powieść.
                </p>
            @endif
        </div>
        </div>

            {{-- PRAWA KOLUMNA: Zdjecie glowne (duze) - ETAP_07d --}}
            @if($isEditMode && $product && $product->id)
            <div class="h-full">
                @include('livewire.products.management.partials.primary-image-preview', [
                    'product' => $product
                ])
            </div>
            @endif
        </div>
        @endif
        {{-- ========== KONIEC SEKCJI VAT + ZDJECIE (EDIT MODE ONLY) ========== --}}

        {{-- ========== STATUS PRODUKTU + HARMONOGRAM (ZAWSZE WIDOCZNE - FIX BUG /create) ========== --}}
        <div class="md:col-span-2 grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Status produktu --}}
            <fieldset class="space-y-3">
                <legend class="text-sm font-medium text-gray-300">Status produktu</legend>

                <div class="flex items-center">
                    <input wire:click="toggleActiveStatus"
                           type="checkbox"
                           {{ $is_active ? 'checked' : '' }}
                           id="is_active"
                           class="rounded border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 cursor-pointer">
                    <label for="is_active" class="ml-2 text-sm text-gray-300 cursor-pointer">
                        Produkt aktywny
                        @if($is_active)
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium product-active-label">
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
                           class="rounded border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <label for="is_variant_master" class="ml-2 text-sm text-gray-300">
                        Produkt z wariantami
                    </label>
                </div>

                <div class="flex items-center">
                    <input wire:model.live="is_featured"
                           type="checkbox"
                           id="is_featured"
                           class="rounded border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <label for="is_featured" class="ml-2 text-sm text-gray-300">
                        Produkt wyrozzniony
                        @if($is_featured)
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium product-featured-label">
                                Wyrozzniony
                            </span>
                        @endif
                    </label>
                </div>
            </fieldset>

            {{-- Harmonogram publikacji --}}
            <fieldset class="space-y-3">
                <legend class="text-sm font-medium text-gray-300">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Harmonogram publikacji
                </legend>

                <div class="space-y-3">
                    <div>
                        <label for="available_from" class="block text-sm font-medium text-gray-400 mb-1">
                            Dostepny od
                        </label>
                        <input wire:model.live="available_from"
                               type="datetime-local"
                               id="available_from"
                               class="block w-full rounded-md border-gray-600 bg-gray-700 text-white shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">Pozostaw puste dla "od zawsze"</p>
                    </div>

                    <div>
                        <label for="available_to" class="block text-sm font-medium text-gray-400 mb-1">
                            Dostepny do
                        </label>
                        <input wire:model.live="available_to"
                               type="datetime-local"
                               id="available_to"
                               class="block w-full rounded-md border-gray-600 bg-gray-700 text-white shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">Pozostaw puste dla "na zawsze"</p>
                    </div>
                </div>
            </fieldset>
        </div>
        {{-- ========== KONIEC STATUS + HARMONOGRAM ========== --}}

        {{-- Categories Section --}}
        <div class="md:col-span-2">
            {{-- Header with Refresh Button (ETAP_07b FAZA 1) + Status Badge (FAZA 2) --}}
            @if($activeShopId)
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <label class="block text-sm font-medium text-gray-300">
                            Kategorie produktu ({{ collect($availableShops)->firstWhere('id', $activeShopId)['name'] ?? 'PrestaShop' }})
                        </label>

                        {{-- Category Validation Status Badge (ETAP_07b FAZA 2) --}}
                        @php
                            // FIX 2025-11-21: Check sync_status FIRST (like other fields in ShopTab)
                            $shopData = $product->shopData->where('shop_id', $activeShopId)->first();
                            $syncStatus = $shopData?->sync_status ?? 'synced';

                            // If pending sync, show pending badge (blue) instead of validation badge
                            if ($syncStatus === 'pending') {
                                $badge = ['icon' => 'clock', 'text' => 'Oczekuje'];
                                $tooltip = 'Kategorie są aktualnie synchronizowane z PrestaShop. Zmiany zostaną zastosowane po zakończeniu synchronizacji.';
                                $status = 'pending';
                                $badgeClass = 'status-label-pending'; // Blue badge (like other pending fields)
                            } else {
                                // Normal validation badge (synced state)
                                $validationStatus = $this->getCategoryValidationStatus();
                                if ($validationStatus) {
                                    $badge = $validationStatus['badge'];
                                    $tooltip = $validationStatus['tooltip'];
                                    $status = $validationStatus['status'];
                                    // Map status to existing CSS classes (NO INLINE STYLES!)
                                    $badgeClass = match($status) {
                                        'zgodne' => 'status-label-same',      // Green
                                        'własne' => 'status-label-different', // Orange
                                        'dziedziczone' => 'status-label-inherited', // Purple
                                        default => 'status-label-inherited',
                                    };
                                } else {
                                    $badge = null; // No badge if no validation status
                                }
                            }
                        @endphp
                        @if($badge && $isEditMode)
                            @php
                                // Badge and tooltip already set above
                            @endphp
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs rounded-md {{ $badgeClass }}"
                                @if($tooltip)
                                    x-data="{ showTooltip: false }"
                                    @mouseenter="showTooltip = true"
                                    @mouseleave="showTooltip = false"
                                @endif
                            >
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    @if($badge['icon'] === 'check-circle')
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    @elseif($badge['icon'] === 'adjustments')
                                        <path d="M5 4a1 1 0 00-2 0v7.268a2 2 0 000 3.464V16a1 1 0 102 0v-1.268a2 2 0 000-3.464V4zM11 4a1 1 0 10-2 0v1.268a2 2 0 000 3.464V16a1 1 0 102 0V8.732a2 2 0 000-3.464V4zM16 3a1 1 0 011 1v7.268a2 2 0 010 3.464V16a1 1 0 11-2 0v-1.268a2 2 0 010-3.464V4a1 1 0 011-1z"/>
                                    @elseif($badge['icon'] === 'arrow-down')
                                        <path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    @elseif($badge['icon'] === 'clock')
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                    @else
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                    @endif
                                </svg>
                                {{ $badge['text'] }}

                                {{-- Tooltip --}}
                                @if($tooltip)
                                    <div
                                        x-show="showTooltip"
                                        x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0 transform scale-95"
                                        x-transition:enter-end="opacity-100 transform scale-100"
                                        x-transition:leave="transition ease-in duration-150"
                                        x-transition:leave-start="opacity-100 transform scale-100"
                                        x-transition:leave-end="opacity-0 transform scale-95"
                                        class="absolute z-50 px-3 py-2 text-xs font-normal text-white bg-gray-900 rounded-lg shadow-xl border border-gray-700 max-w-sm -mt-15"
                                        x-cloak
                                    >
                                        {{ $tooltip }}
                                        <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 translate-y-full">
                                            <div class="w-2 h-2 rotate-45 bg-gray-900 border-r border-b border-gray-700"></div>
                                        </div>
                                    </div>
                                @endif
                            </span>
                        @endif
                    </div>

                    <button
                        type="button"
                        wire:click="refreshCategoriesFromShop"
                        wire:loading.attr="disabled"
                        class="btn-enterprise-secondary text-sm inline-flex items-center whitespace-nowrap">
                        <span wire:loading.remove wire:target="refreshCategoriesFromShop" class="inline-flex items-center">
                            <svg class="w-4 h-4 mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Odśwież kategorie
                        </span>
                        <span wire:loading wire:target="refreshCategoriesFromShop" class="inline-flex items-center">
                            <svg class="w-4 h-4 mr-1.5 flex-shrink-0 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Odświeżanie...
                        </span>
                    </button>
                </div>
            @else
                <label class="block text-sm font-medium text-gray-300 mb-3">
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
            @endif

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
                // ETAP_07b FAZA 1 FIX: Use getShopCategories() to show PrestaShop categories when shop is active
                $availableCategories = $this->getShopCategories();
            @endphp

            {{-- ETAP_07b FAZA 4.2: Category Controls (Search, Expand/Collapse, Clear) --}}
            {{-- FAZA 4.2.3: showCreateButton removed - inline creation via + buttons in tree --}}
            @include('livewire.products.management.partials.category-controls', [
                'context' => $activeShopId ?? 'default',
            ])

            @if($availableCategories && count($availableCategories) > 0)
                {{-- FIX 2025-11-24 (v3 - FINAL): Static wire:key to PREVENT forced re-renders --}}
                {{-- wire:key includes ONLY activeShopId (to distinguish shop contexts) --}}
                {{-- REMOVED: $primaryCatId from wire:key (was causing Alpine.js state reset!) --}}
                {{-- Livewire automatically re-renders on property changes, no force re-render needed --}}
                @php
                    $categoryContainerClasses = $this->getCategoryClasses();
                @endphp
                {{-- FIX 2025-11-25: Add frozen state when sync job is running for current shop --}}
                {{-- FIX 2025-11-26: Added resize-y for expandable category tree --}}
                <div class="{{ $categoryContainerClasses }} min-h-40 max-h-96 overflow-y-auto resize-y"
                     wire:key="categories-ctx-{{ $activeShopId ?? 'default' }}"
                     :class="{ 'category-tree-frozen': $wire.activeShopId !== null && ($wire.activeJobStatus === 'pending' || $wire.activeJobStatus === 'processing') }">
                    @foreach($availableCategories as $rootCategory)
                        @include('livewire.products.management.partials.category-tree-item', [
                            'category' => $rootCategory,
                            'level' => 0,
                            'context' => $activeShopId ?? 'default',
                            'expandedCategoryIds' => $this->expandedCategoryIds
                        ])
                    @endforeach
                </div>

                @if($this->getCategoriesForContext($activeShopId))
                    <p class="mt-2 text-sm text-gray-400">
                        Wybrano {{ count($this->getCategoriesForContext($activeShopId)) }} {{ count($this->getCategoriesForContext($activeShopId)) == 1 ? 'kategorię' : 'kategori' }}.
                        @if($this->getPrimaryCategoryForContext($activeShopId))
                            Główna: <strong>{{ collect($availableCategories)->firstWhere('id', $this->getPrimaryCategoryForContext($activeShopId))?->name }}</strong>
                        @endif
                        @if($activeShopId !== null)
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-orange-900/30 text-orange-200 border border-orange-700/50 ml-2">
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

    {{-- Inline Action Bar (duplicates key quick actions for current scope) --}}
    {{-- ETAP_08.5: Footer Actions - rozpoznaje kontekst Shop/ERP --}}
    <div class="mt-6 mb-8">
        @php
            // Determine active context: ERP takes priority over Shop
            $hasErpContext = isset($activeErpConnectionId) && $activeErpConnectionId !== null;
            $hasShopContext = isset($activeShopId) && $activeShopId !== null;
            $hasAnyContext = $hasErpContext || $hasShopContext;
        @endphp

        @if(!$hasAnyContext)
            {{-- Default tab (Dane PPM) - only save/cancel buttons --}}
            <div class="flex flex-col lg:flex-row gap-4">
                @include('livewire.products.management.partials.actions.save-and-close-button', [
                    'classes' => 'lg:flex-1'
                ])
                @include('livewire.products.management.partials.actions.cancel-link', [
                    'classes' => 'lg:flex-1'
                ])
            </div>
        @else
            {{-- Shop or ERP context active - show all buttons --}}
            <div class="space-y-4">
                <div class="flex flex-col lg:flex-row gap-4">
                    @include('livewire.products.management.partials.actions.save-and-close-button', [
                        'classes' => 'lg:flex-1'
                    ])
                    @include('livewire.products.management.partials.actions.cancel-link', [
                        'classes' => 'lg:flex-1'
                    ])
                </div>
                <div class="flex flex-col lg:flex-row gap-4">
                    {{-- ETAP_08.5: Universal sync/pull buttons (Shop or ERP based on context) --}}
                    @if($hasErpContext)
                        {{-- ERP Context: "Aktualizuj w ERP" / "Wczytaj z ERP" --}}
                        @include('livewire.products.management.partials.actions.shop-sync-button', [
                            'erpConnectionId' => $activeErpConnectionId,
                            'classes' => 'w-full lg:flex-1'
                        ])
                        @include('livewire.products.management.partials.actions.shop-pull-button', [
                            'erpConnectionId' => $activeErpConnectionId,
                            'classes' => 'w-full lg:flex-1'
                        ])
                    @else
                        {{-- Shop Context: "Aktualizuj aktualny sklep" / "Wczytaj z aktualnego sklepu" --}}
                        @include('livewire.products.management.partials.actions.shop-sync-button', [
                            'shopId' => $activeShopId,
                            'classes' => 'w-full lg:flex-1'
                        ])
                        @include('livewire.products.management.partials.actions.shop-pull-button', [
                            'shopId' => $activeShopId,
                            'classes' => 'w-full lg:flex-1'
                        ])
                    @endif
                </div>
            </div>
        @endif
    </div>
</div> {{-- Close tab-content (line 2) --}}
