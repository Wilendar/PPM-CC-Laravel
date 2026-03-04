{{-- Single Product Table Row --}}
@php
    $rowStatusClass = '';
    $productStatus = $this->productStatuses[$product->id] ?? null;
    if ($productStatus) {
        if ($productStatus->hasActiveSyncJob()) {
            $rowStatusClass = 'product-row--syncing';
        } elseif ($productStatus->getSeverity() === \App\DTOs\ProductStatusDTO::SEVERITY_CRITICAL) {
            $rowStatusClass = 'product-row--error';
        } elseif ($productStatus->getSeverity() === \App\DTOs\ProductStatusDTO::SEVERITY_WARNING) {
            $rowStatusClass = 'product-row--warning';
        }
    }
@endphp
<tr @click="window.location.href = '{{ route('products.edit', $product) }}'"
    @mousedown="pressing = true"
    @mouseup="pressing = false"
    @mouseleave="pressing = false"
    :class="{
        'scale-[0.995] bg-orange-500/10': pressing,
        'product-row-expanded': expanded && hasVariants,
        'product-row-expandable': hasVariants
    }"
    class="product-list-row cursor-pointer hover:bg-orange-500/5 hover:shadow-lg hover:shadow-orange-500/5 transition-all duration-200 ease-out {{ $rowStatusClass }}">
    {{-- Bulk Select --}}
    <td class="px-6 py-4" @click.stop>
        @if(!($isReadOnly ?? false))
        <input type="checkbox"
               wire:key="select-{{ $product->id }}"
               value="{{ $product->id }}"
               wire:model.live="selectedProducts"
               class="rounded border-primary text-orange-500 shadow-sm focus:ring-orange-500 focus:ring-opacity-50 bg-input cursor-pointer">
        @endif
    </td>

    {{-- ETAP_07d FAZA 7: Thumbnail --}}
    <td class="product-list-thumbnail-cell">
        @if($product->media->first())
            <img src="{{ $product->media->first()->thumbnailUrl ?? $product->media->first()->url }}"
                 alt="{{ $product->name }}"
                 class="product-list-thumbnail"
                 loading="lazy" />
        @else
            <div class="product-list-thumbnail-placeholder">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif
    </td>

    {{-- SKU + Expand Toggle --}}
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="flex items-center gap-2">
            {{-- Expand Toggle (tylko dla produktów z wariantami) --}}
            <template x-if="hasVariants">
                <button @click.stop="expanded = !expanded"
                        class="expand-toggle"
                        :class="{ 'expanded': expanded }">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </template>

            <div>
                <div class="text-sm font-medium text-primary">
                    {{ $product->sku }}
                </div>
                @if($product->supplier_code)
                    <div class="text-xs text-muted">
                        {{ $product->supplier_code }}
                    </div>
                @endif
            </div>
        </div>
    </td>

    {{-- Name + Variants Badge --}}
    <td class="px-6 py-4">
        <div class="flex items-center">
            <div class="text-sm text-primary">
                <a href="{{ route('products.edit', $product) }}"
                   class="hover:text-orange-500 transition-colors duration-300">
                    {{ Str::limit($product->name, 50) }}
                </a>
            </div>

            {{-- Variants Count Badge --}}
            @if($product->variants && $product->variants->count() > 0)
                <button @click.stop="expanded = !expanded"
                        class="variants-badge">
                    Warianty: {{ $product->variants->count() }}
                </button>
            @endif
        </div>

        @if($product->is_variant_master)
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-800 text-purple-200 mt-1">
                Master
            </span>
        @endif
    </td>

    {{-- Type --}}
    <td class="px-6 py-4 whitespace-nowrap">
        @if($product->productType)
            <x-product-type-badge :type="$product->productType" />
        @else
        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-800 text-gray-200">
            Brak typu
        </span>
        @endif
    </td>

    {{-- Manufacturer --}}
    <td class="px-6 py-4 whitespace-nowrap text-sm text-primary">
        {{ $product->manufacturer ?? '-' }}
    </td>

    {{-- Cena --}}
    <td class="px-4 py-4 whitespace-nowrap text-sm" @click.stop
        x-data="{ show: false, tipStyle: '' }"
        @mouseenter="show = true; $nextTick(() => { const r = $refs.priceVal.getBoundingClientRect(); const vh = window.innerHeight; const flip = r.bottom + 200 > vh; tipStyle = flip ? 'bottom:' + (vh - r.top + 2) + 'px;top:auto;left:' + Math.max(8, r.left) + 'px' : 'top:' + (r.bottom + 2) + 'px;left:' + Math.max(8, r.left) + 'px'; })"
        @mouseleave="show = false">
        @php
            $defaultPrice = $this->getDefaultPriceForProduct($product);
            $allPrices = $this->getAllPricesForProduct($product);
        @endphp
        <span x-ref="priceVal" class="text-orange-400 font-medium">
            {{ $this->formatPrice($defaultPrice) }}
        </span>
        @if(count($allPrices) > 1)
            <span class="text-xs text-gray-500 ml-1">({{ count($allPrices) }})</span>
        @endif

        @if(count($allPrices) > 0)
            <template x-teleport="body">
                <div x-show="show" x-transition x-cloak class="price-tooltip" :style="tipStyle">
                    <div class="text-xs font-semibold text-gray-300 mb-1 pb-1 border-b border-gray-600">
                        Grupy cenowe ({{ $priceDisplayMode }})
                    </div>
                    @foreach($allPrices as $priceData)
                        <div class="flex justify-between text-xs py-0.5">
                            <span class="mr-3 {{ ($priceData['is_default'] ?? false) ? 'text-orange-400 font-medium' : 'text-gray-400' }}">{{ $priceData['group'] }}{{ ($priceData['is_default'] ?? false) ? ' *' : '' }}</span>
                            <span class="{{ ($priceData['is_default'] ?? false) ? 'text-orange-300' : 'text-white' }} font-medium">
                                {{ number_format($priceDisplayMode === 'netto' ? $priceData['netto'] : $priceData['brutto'], 2, ',', ' ') }} zl
                            </span>
                        </div>
                    @endforeach
                </div>
            </template>
        @endif
    </td>

    {{-- Stan --}}
    <td class="px-4 py-4 whitespace-nowrap text-sm" @click.stop
        x-data="{ show: false, tipStyle: '' }"
        @mouseenter="show = true; $nextTick(() => { const r = $refs.stockVal.getBoundingClientRect(); const vh = window.innerHeight; const flip = r.bottom + 350 > vh; tipStyle = flip ? 'bottom:' + (vh - r.top + 2) + 'px;top:auto;left:' + Math.max(8, r.left - 100) + 'px' : 'top:' + (r.bottom + 2) + 'px;left:' + Math.max(8, r.left - 100) + 'px'; })"
        @mouseleave="show = false">
        @php
            $defaultStock = $this->getDefaultStockForProduct($product);
            $allStock = $this->getAllStockForProduct($product);
            $stockClass = $this->getStockIndicatorClass($defaultStock);
        @endphp
        <span x-ref="stockVal" class="{{ $stockClass }}">
            {{ $defaultStock !== null ? $defaultStock . ' szt.' : '-' }}
        </span>
        @if(count($allStock) > 1)
            <span class="text-xs text-gray-500 ml-1">({{ count($allStock) }})</span>
        @endif

        @if(count($allStock) > 0)
            <template x-teleport="body">
                <div x-show="show" x-transition x-cloak class="stock-tooltip" :style="tipStyle">
                    <div class="text-xs font-semibold text-gray-300 mb-2 pb-1 border-b border-gray-600">
                        Stany magazynowe
                    </div>
                    <table class="stock-tooltip__table">
                        <thead>
                            <tr class="text-xs text-gray-500">
                                <th class="text-left pr-3 pb-1 font-medium">Magazyn</th>
                                <th class="text-right pr-2 pb-1 font-medium">Stan</th>
                                <th class="text-right pr-2 pb-1 font-medium">Rez.</th>
                                <th class="text-right pr-2 pb-1 font-medium">Dost.</th>
                                <th class="text-right pb-1 font-medium">Limit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allStock as $stockData)
                                <tr class="text-xs {{ $stockData['is_out'] ? 'stock-tooltip__row--out' : ($stockData['is_low'] ? 'stock-tooltip__row--low' : '') }}">
                                    <td class="pr-3 py-0.5 {{ $stockData['is_default'] ?? false ? 'text-orange-400 font-medium' : 'text-gray-400' }}">{{ $stockData['warehouse'] }}{{ ($stockData['is_default'] ?? false) ? ' *' : '' }}</td>
                                    <td class="text-right pr-2 py-0.5 text-white">{{ $stockData['quantity'] }}</td>
                                    <td class="text-right pr-2 py-0.5 text-gray-400">{{ $stockData['reserved'] }}</td>
                                    <td class="text-right pr-2 py-0.5 font-medium {{ $stockData['is_out'] ? 'text-red-400' : ($stockData['is_low'] ? 'text-yellow-400' : 'text-green-400') }}">{{ $stockData['available'] }}</td>
                                    <td class="text-right py-0.5 text-gray-500">{{ $stockData['minimum_stock'] > 0 ? $stockData['minimum_stock'] : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </template>
        @endif
    </td>

    {{-- Status --}}
    <td class="px-6 py-4 whitespace-nowrap" @click.stop>
        @if($this->userCan('update'))
        <button wire:click="toggleStatus({{ $product->id }})"
                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium transition-colors
                    {{ $product->is_active
                        ? 'bg-green-800 text-green-200 hover:bg-green-700'
                        : 'bg-red-800 text-red-200 hover:bg-red-700' }}">
            <span class="w-2 h-2 rounded-full mr-1 {{ $product->is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
            {{ $product->is_active ? 'Aktywny' : 'Nieaktywny' }}
        </button>
        @else
        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                    {{ $product->is_active ? 'bg-green-800/50 text-green-200' : 'bg-red-800/50 text-red-200' }}">
            <span class="w-2 h-2 rounded-full mr-1 {{ $product->is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
            {{ $product->is_active ? 'Aktywny' : 'Nieaktywny' }}
        </span>
        @endif
    </td>

    {{-- ETAP: Product Status Column (2026-02-04) - Replaces PrestaShop Sync Status --}}
    @include('livewire.products.listing.partials.status-column', [
        'product' => $product,
        'status' => $this->productStatuses[$product->id] ?? null
    ])

    {{-- Updated At --}}
    <td class="px-6 py-4 whitespace-nowrap text-sm text-muted">
        <div>{{ $product->updated_at->format('d.m.Y') }}</div>
        <div class="text-xs">{{ $product->updated_at->format('H:i') }}</div>
    </td>

    {{-- Actions --}}
    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" @click.stop>
        <div class="flex items-center justify-end space-x-1">
            {{-- Quick Preview Modal Button --}}
            <button wire:click="showProductPreview({{ $product->id }})"
                    class="text-muted hover:text-blue-500 transition-colors duration-300"
                    title="Szybki podgląd">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </button>

            {{-- Edit Product --}}
            <a href="{{ route('products.edit', $product) }}"
               class="text-muted hover:text-orange-500 transition-colors duration-300"
               title="Edytuj produkt">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </a>

            {{-- Duplicate Product --}}
            @if($this->userCan('create'))
            <button wire:click="duplicateProduct({{ $product->id }})"
                    class="text-muted hover:text-green-500 transition-colors duration-300"
                    title="Duplikuj produkt">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            </button>
            @endif

            {{-- FAZA 1.5: Multi-Store Actions --}}
            @if($this->userCan('update'))
            {{-- Sync/Refresh Product --}}
            <button wire:click="syncProduct({{ $product->id }})"
                    class="text-muted hover:text-purple-500 transition-colors duration-300"
                    title="Synchronizuj ze sklepami">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>

            {{-- Publish to Shops --}}
            <button wire:click="publishToShops({{ $product->id }})"
                    class="text-muted hover:text-cyan-500 transition-colors duration-300"
                    title="Wyślij na sklepy">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
            </button>
            @endif

            {{-- Delete Product --}}
            @if($this->userCan('delete'))
            <button wire:click="confirmDelete({{ $product->id }})"
                    class="text-muted hover:text-red-500 transition-colors duration-300"
                    title="Usuń produkt">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
            @endif
        </div>
    </td>
</tr>

{{-- FAZA 4: Expandable Variant Rows --}}
@if($product->variants && $product->variants->count() > 0)
    @foreach($product->variants as $variant)
        @include('livewire.products.listing.partials.variant-row', ['variant' => $variant])
    @endforeach
@endif
