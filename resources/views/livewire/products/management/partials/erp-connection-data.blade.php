{{-- ETAP_08.3: ERP Connection Data Display (Shop-Tab Pattern!) --}}
{{-- Shows detailed data from selected ERP connection with EDITABLE fields and validation badges --}}
@php
    $connection = $erpExternalData['connection'] ?? null;
    $mapping = $erpExternalData['mapping'] ?? null;
    $externalData = $erpExternalData['external_data'] ?? [];
    $syncStatus = $erpExternalData['sync_status'] ?? 'not_synced';
    $lastSyncAt = $erpExternalData['last_sync_at'] ?? null;
    $errorMessage = $erpExternalData['error_message'] ?? null;
@endphp

@if($connection)
<div class="mt-4 p-4 bg-gray-900 rounded-lg border border-blue-700/30">
    {{-- HEADER: Title + Buttons (mirrors shop-tab pattern) --}}
    <div class="flex items-center justify-between mb-4">
        <h5 class="text-lg font-medium text-white">Informacje podstawowe</h5>

        <div class="flex items-center space-x-3">
            {{-- Load from ERP Button (mirrors "Wczytaj z PrestaShop") --}}
            @if($isEditMode)
                <button type="button"
                        wire:click="loadProductDataFromErp({{ $connection->id }}, true)"
                        wire:loading.attr="disabled"
                        wire:target="loadProductDataFromErp"
                        class="btn-enterprise-secondary text-sm inline-flex items-center space-x-1"
                        title="Wczytaj ponownie dane produktu z ERP">
                    <span wire:loading.remove wire:target="loadProductDataFromErp">🔄</span>
                    <span wire:loading wire:target="loadProductDataFromErp">⏳</span>
                    <span wire:loading.remove wire:target="loadProductDataFromErp">Wczytaj z ERP</span>
                    <span wire:loading wire:target="loadProductDataFromErp">Wczytywanie...</span>
                </button>
            @endif

            {{-- Active ERP Indicator --}}
            <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-blue-900/30 text-blue-200 border border-blue-700/50">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Edytujesz: {{ $connection->instance_name }}
            </span>
        </div>
    </div>

    {{-- SYNC STATUS PANEL (mirrors shop-tab pattern) --}}
    @php
        $syncDisplay = $this->getErpSyncStatusDisplay($connection->id);
    @endphp

    <div class="mb-4 p-4 border border-gray-600 rounded-lg bg-gray-800 shadow-sm">
        <div class="flex items-center justify-between">
            {{-- Status Info --}}
            <div class="flex items-center space-x-3">
                <span class="text-2xl">{{ $syncDisplay['icon'] }}</span>
                <div>
                    <h4 class="font-semibold {{ str_contains($syncDisplay['class'], 'green') ? 'text-green-400' : (str_contains($syncDisplay['class'], 'red') ? 'text-red-400' : 'text-yellow-400') }}">
                        Status synchronizacji: {{ $syncDisplay['text'] }}
                    </h4>

                    @if($syncDisplay['external_id'])
                        <p class="text-sm text-gray-400">
                            {{ ucfirst($connection->erp_type) }} ID: <strong class="font-mono">#{{ $syncDisplay['external_id'] }}</strong>
                        </p>
                    @else
                        <p class="text-sm text-gray-500">
                            Produkt nie zostal jeszcze zsynchronizowany z tym systemem ERP
                        </p>
                    @endif

                    @if($syncDisplay['last_sync'])
                        <p class="text-xs text-gray-500">
                            Ostatnia synchronizacja: {{ $syncDisplay['last_sync'] }}
                        </p>
                    @endif
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex space-x-2">
                @if($syncDisplay['status'] === 'error')
                    <button type="button"
                            wire:click="syncToErp({{ $connection->id }})"
                            class="btn-enterprise-secondary text-sm inline-flex items-center"
                            title="Ponow synchronizacje">
                        🔄 Ponow
                    </button>
                @endif

                @if($syncDisplay['external_id'])
                    {{-- Baselinker link --}}
                    @if($connection->erp_type === 'baselinker')
                        <a href="https://panel.baselinker.com/inventory/products?inventory_id={{ $connection->default_inventory_id ?? '' }}&search={{ $syncDisplay['external_id'] }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="btn-enterprise-secondary text-sm inline-flex items-center"
                           title="Otworz produkt w Baselinker">
                            🔗 Baselinker
                        </a>
                    @endif
                @else
                    {{-- Sync to ERP button for unsynced products --}}
                    <button type="button"
                            wire:click="syncToErp({{ $connection->id }})"
                            class="btn-enterprise-primary text-sm inline-flex items-center"
                            title="Dodaj produkt do tego systemu ERP">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Dodaj do ERP
                    </button>
                @endif
            </div>
        </div>

        {{-- Error Message Display --}}
        @if($syncDisplay['status'] === 'error' && $errorMessage)
            <div class="mt-3 p-3 bg-red-900/20 border border-red-800 rounded">
                <p class="text-sm text-red-400">
                    <strong>Blad:</strong> {{ $errorMessage }}
                </p>
            </div>
        @endif

        {{-- Collapsible: Szczegoly synchronizacji --}}
        @if($mapping)
            <div class="shop-details-collapsible mt-3" x-data="{ expanded: false }">
                <button
                    @click="expanded = !expanded"
                    class="collapsible-header"
                    type="button"
                >
                    <span class="text-sm font-medium text-gray-300">Szczegoly synchronizacji</span>
                    <svg x-show="!expanded" class="w-4 h-4 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                    <svg x-show="expanded" class="w-4 h-4 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                    </svg>
                </button>

                <div x-show="expanded" x-collapse class="collapsible-content">
                    <div class="shop-info-compact mt-3 space-y-1">
                        <p class="text-sm text-gray-400">
                            <strong class="text-gray-300">System ERP:</strong>
                            {{ ucfirst($connection->erp_type) }} - {{ $connection->instance_name }}
                        </p>
                        <p class="text-sm text-gray-400">
                            <strong class="text-gray-300">External ID:</strong>
                            {{ $mapping->external_id ?? 'Nie zsynchronizowane' }}
                        </p>
                        <p class="text-sm text-gray-400">
                            <strong class="text-gray-300">Ostatnia synchronizacja:</strong>
                            {{ $lastSyncAt ? \Carbon\Carbon::parse($lastSyncAt)->format('d.m.Y H:i') : 'Nigdy' }}
                        </p>
                    </div>

                    {{-- Actions --}}
                    <div class="shop-actions-compact mt-4 flex gap-2">
                        <button type="button"
                                wire:click="syncToErp({{ $connection->id }})"
                                wire:loading.attr="disabled"
                                class="btn-enterprise-primary text-sm inline-flex items-center">
                            <svg class="w-4 h-4 mr-1.5" wire:loading.class="animate-spin" wire:target="syncToErp({{ $connection->id }})" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Synchronizuj do ERP
                        </button>
                        <button type="button"
                                wire:click="pullFromErp({{ $connection->id }})"
                                wire:loading.attr="disabled"
                                class="btn-enterprise-secondary text-sm inline-flex items-center">
                            <svg class="w-4 h-4 mr-1.5" wire:loading.class="animate-spin" wire:target="pullFromErp({{ $connection->id }})" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Pobierz z ERP
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- EDITABLE FIELDS with validation badges (mirrors shop-tab pattern) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- SKU Field --}}
        <div class="md:col-span-1">
            <label for="erp_sku" class="block text-sm font-medium text-gray-300 mb-2">
                SKU produktu <span class="text-red-500">*</span>
                @php
                    $skuIndicator = $this->getErpFieldStatusIndicator('sku');
                @endphp
                @if($skuIndicator['show'])
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $skuIndicator['class'] }}">
                        {{ $skuIndicator['text'] }}
                    </span>
                @endif
            </label>
            <input wire:model.live="sku"
                   wire:change="trackErpFieldChange('sku')"
                   type="text"
                   id="erp_sku"
                   placeholder="np. ABC123456"
                   class="{{ $this->getErpFieldClasses('sku') }} @error('sku') !border-red-500 @enderror">
            @if($skuIndicator['show'] && $skuIndicator['class'] === 'status-label-different')
                <p class="mt-1 text-xs text-gray-400">
                    Wartosc w ERP: <code class="bg-gray-700 px-1 rounded">{{ $externalData['sku'] ?? '-' }}</code>
                </p>
            @endif
        </div>

        {{-- Name Field --}}
        <div class="md:col-span-2">
            <label for="erp_name" class="block text-sm font-medium text-gray-300 mb-2">
                Nazwa produktu <span class="text-red-500">*</span>
                @php
                    $nameIndicator = $this->getErpFieldStatusIndicator('name');
                @endphp
                @if($nameIndicator['show'])
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $nameIndicator['class'] }}">
                        {{ $nameIndicator['text'] }}
                    </span>
                @endif
            </label>
            <input wire:model.live="name"
                   wire:change="trackErpFieldChange('name')"
                   type="text"
                   id="erp_name"
                   placeholder="Wprowadz nazwe produktu"
                   class="{{ $this->getErpFieldClasses('name') }} @error('name') !border-red-500 @enderror">
            @if($nameIndicator['show'] && $nameIndicator['class'] === 'status-label-different')
                <p class="mt-1 text-xs text-gray-400">
                    Wartosc w ERP: <code class="bg-gray-700 px-1 rounded text-ellipsis overflow-hidden max-w-md inline-block align-bottom">{{ $externalData['text_fields']['name'] ?? '-' }}</code>
                </p>
            @endif
        </div>

        {{-- EAN Field --}}
        <div class="md:col-span-1">
            <label for="erp_ean" class="block text-sm font-medium text-gray-300 mb-2">
                Kod EAN
                @php
                    $eanIndicator = $this->getErpFieldStatusIndicator('ean');
                @endphp
                @if($eanIndicator['show'])
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $eanIndicator['class'] }}">
                        {{ $eanIndicator['text'] }}
                    </span>
                @endif
            </label>
            <input wire:model.live="ean"
                   wire:change="trackErpFieldChange('ean')"
                   type="text"
                   id="erp_ean"
                   placeholder="Kod kreskowy EAN"
                   class="{{ $this->getErpFieldClasses('ean') }} @error('ean') !border-red-500 @enderror">
            @if($eanIndicator['show'] && $eanIndicator['class'] === 'status-label-different')
                <p class="mt-1 text-xs text-gray-400">
                    Wartosc w ERP: <code class="bg-gray-700 px-1 rounded">{{ $externalData['ean'] ?? '-' }}</code>
                </p>
            @endif
        </div>

        {{-- Stock Info (read-only from ERP) --}}
        @if(isset($externalData['stock']))
            <div class="md:col-span-1">
                <label class="block text-sm font-medium text-gray-300 mb-2">
                    Stan magazynowy w ERP
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium status-label-inherited">
                        Tylko odczyt
                    </span>
                </label>
                <div class="bg-gray-800 border border-gray-600 rounded-md px-4 py-2.5 text-white">
                    @if(is_array($externalData['stock']))
                        {{ array_sum($externalData['stock']) }} szt.
                        @if(count($externalData['stock']) > 1)
                            <span class="text-gray-400 text-xs ml-2">
                                ({{ count($externalData['stock']) }} magazynow)
                            </span>
                        @endif
                    @else
                        {{ $externalData['stock'] }} szt.
                    @endif
                </div>
            </div>
        @endif

        {{-- Prices Info (read-only from ERP) --}}
        @if(isset($externalData['prices']) && is_array($externalData['prices']))
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-300 mb-2">
                    Ceny w ERP
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium status-label-inherited">
                        Tylko odczyt
                    </span>
                </label>
                <div class="flex flex-wrap gap-2">
                    @foreach($externalData['prices'] as $priceGroupId => $price)
                        <span class="inline-flex items-center px-3 py-1.5 bg-gray-800 border border-gray-600 rounded-md text-sm">
                            <span class="text-gray-400">Gr. {{ $priceGroupId }}:</span>
                            <span class="ml-1 text-white font-medium">{{ number_format($price, 2) }} zl</span>
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Variants Info (read-only from ERP) --}}
        @if(isset($externalData['variants']) && is_array($externalData['variants']) && count($externalData['variants']) > 0)
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-300 mb-2">
                    Warianty w ERP ({{ count($externalData['variants']) }})
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium status-label-inherited">
                        Tylko odczyt
                    </span>
                </label>
                <div class="space-y-2 max-h-40 overflow-y-auto">
                    @foreach($externalData['variants'] as $variantId => $variantData)
                        <div class="flex items-center justify-between bg-gray-800 border border-gray-600 rounded-md px-3 py-2">
                            <div>
                                <span class="text-sm text-white">{{ $variantData['name'] ?? 'Wariant' }}</span>
                                @if(isset($variantData['sku']))
                                    <span class="ml-2 text-xs text-gray-400 font-mono">{{ $variantData['sku'] }}</span>
                                @endif
                            </div>
                            <span class="text-xs text-gray-500">ID: {{ $variantId }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Images Count (read-only from ERP) --}}
        @if(isset($externalData['images']) && is_array($externalData['images']))
            <div class="md:col-span-1">
                <label class="block text-sm font-medium text-gray-300 mb-2">
                    Zdjecia w ERP
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium status-label-inherited">
                        Tylko odczyt
                    </span>
                </label>
                <div class="bg-gray-800 border border-gray-600 rounded-md px-4 py-2.5 text-white">
                    {{ count($externalData['images']) }} zdjec
                </div>
            </div>
        @endif
    </div>

    {{-- Action Bar (save/sync buttons at bottom) --}}
    <div class="mt-6 pt-4 border-t border-gray-700">
        <div class="flex flex-col lg:flex-row gap-4">
            @include('livewire.products.management.partials.actions.save-and-close-button', [
                'classes' => 'lg:flex-1'
            ])

            <button type="button"
                    wire:click="syncToErp({{ $connection->id }})"
                    wire:loading.attr="disabled"
                    class="btn-enterprise-primary lg:flex-1 inline-flex items-center justify-center">
                <svg class="w-4 h-4 mr-2" wire:loading.class="animate-spin" wire:target="syncToErp({{ $connection->id }})" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span wire:loading.remove wire:target="syncToErp({{ $connection->id }})">Synchronizuj do ERP</span>
                <span wire:loading wire:target="syncToErp({{ $connection->id }})">Synchronizacja...</span>
            </button>

            <button type="button"
                    wire:click="pullFromErp({{ $connection->id }})"
                    wire:loading.attr="disabled"
                    class="btn-enterprise-secondary lg:flex-1 inline-flex items-center justify-center">
                <svg class="w-4 h-4 mr-2" wire:loading.class="animate-spin" wire:target="pullFromErp({{ $connection->id }})" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span wire:loading.remove wire:target="pullFromErp({{ $connection->id }})">Pobierz z ERP</span>
                <span wire:loading wire:target="pullFromErp({{ $connection->id }})">Pobieranie...</span>
            </button>
        </div>
    </div>
</div>
@endif
