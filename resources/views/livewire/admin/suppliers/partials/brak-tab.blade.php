{{-- Zakladka BRAK - Produkty bez przypisanych podmiotow --}}
<div class="enterprise-card">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-lg font-semibold text-white">Produkty bez przypisania</h2>
            <p class="text-sm text-gray-400 mt-1">
                Produkty, ktore nie maja przypisanego dostawcy, producenta lub importera
            </p>
        </div>

        {{-- Stats --}}
        @if(isset($this->brakStats))
            <div class="flex items-center gap-2">
                <span class="supplier-panel__count-badge supplier-panel__count-badge--warning">
                    {{ $this->brakStats['total'] ?? 0 }} produktow bez przypisania
                </span>
            </div>
        @endif
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        {{-- Brak Filter --}}
        <div>
            <select wire:model.live="brakFilter" class="form-select-dark text-sm">
                <option value="any">Brak dowolnego</option>
                <option value="supplier">Brak dostawcy</option>
                <option value="manufacturer">Brak producenta</option>
                <option value="importer">Brak importera</option>
            </select>
        </div>

        {{-- Search --}}
        <div class="relative flex-1 max-w-sm">
            <input type="text"
                   wire:model.live.debounce.300ms="productSearch"
                   placeholder="Szukaj produktu..."
                   class="form-input-dark w-full pl-9 text-sm">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            @if($productSearch)
                <button wire:click="$set('productSearch', '')"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            @endif
        </div>
    </div>

    {{-- Table --}}
    <div class="supplier-panel__table-wrapper" wire:loading.class="opacity-50" wire:target="brakFilter, productSearch">
        <table class="min-w-full divide-y divide-gray-700">
            <thead class="bg-gray-800">
                <tr>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Produkt</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">SKU</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Kod dostawcy</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Dostawca</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Producent</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Importer</th>
                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Sklepy</th>
                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">ERP</th>
                    <th class="px-3 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($this->brakProducts as $product)
                    <tr wire:key="brak-product-{{ $product->id }}" class="hover:bg-gray-700/50 transition-colors">
                        {{-- Nazwa produktu --}}
                        <td class="px-3 py-3">
                            <div class="text-sm font-medium text-white truncate max-w-xs" title="{{ $product->name }}">
                                {{ $product->name }}
                            </div>
                        </td>

                        {{-- SKU --}}
                        <td class="px-3 py-3">
                            <code class="text-xs font-mono text-gray-300 bg-gray-800 px-2 py-0.5 rounded">
                                {{ $product->sku }}
                            </code>
                        </td>

                        {{-- Kod dostawcy - EDYTOWALNY --}}
                        <td class="px-3 py-3">
                            <input type="text"
                                   value="{{ $product->supplier_code ?? '' }}"
                                   wire:change="updateProductSupplierCode({{ $product->id }}, $event.target.value)"
                                   class="supplier-panel__inline-input"
                                   placeholder="Kod...">
                        </td>

                        {{-- Dostawca - EDYTOWALNY --}}
                        <td class="px-3 py-3">
                            <select wire:change="updateProductAssignment({{ $product->id }}, 'supplier_id', $event.target.value)"
                                    class="supplier-panel__inline-select {{ !$product->supplier_id ? 'supplier-panel__inline-select--missing' : '' }}">
                                <option value="">-- Brak --</option>
                                @foreach($this->allSuppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                            @selected(($product->supplier_id ?? null) == $supplier->id)>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        {{-- Producent - EDYTOWALNY --}}
                        <td class="px-3 py-3">
                            <select wire:change="updateProductAssignment({{ $product->id }}, 'manufacturer_id', $event.target.value)"
                                    class="supplier-panel__inline-select {{ !$product->manufacturer_id ? 'supplier-panel__inline-select--missing' : '' }}">
                                <option value="">-- Brak --</option>
                                @foreach($this->allManufacturers as $manufacturer)
                                    <option value="{{ $manufacturer->id }}"
                                            @selected(($product->manufacturer_id ?? null) == $manufacturer->id)>
                                        {{ $manufacturer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        {{-- Importer - EDYTOWALNY --}}
                        <td class="px-3 py-3">
                            <select wire:change="updateProductAssignment({{ $product->id }}, 'importer_id', $event.target.value)"
                                    class="supplier-panel__inline-select {{ !$product->importer_id ? 'supplier-panel__inline-select--missing' : '' }}">
                                <option value="">-- Brak --</option>
                                @foreach($this->allImporters as $importer)
                                    <option value="{{ $importer->id }}"
                                            @selected(($product->importer_id ?? null) == $importer->id)>
                                        {{ $importer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        {{-- Sklepy --}}
                        <td class="px-3 py-3 text-center">
                            <div class="flex flex-wrap justify-center gap-1">
                                @forelse($product->shopData ?? [] as $shopData)
                                    <span class="supplier-panel__badge supplier-panel__badge--shop"
                                          title="{{ $shopData->shop->name ?? '' }}">
                                        {{ Str::limit($shopData->shop->name ?? '?', 8) }}
                                    </span>
                                @empty
                                    <span class="text-xs text-gray-600">-</span>
                                @endforelse
                            </div>
                        </td>

                        {{-- ERP --}}
                        <td class="px-3 py-3 text-center">
                            <div class="flex flex-wrap justify-center gap-1">
                                @forelse($product->erpData ?? [] as $erpData)
                                    <span class="supplier-panel__badge supplier-panel__badge--erp"
                                          title="{{ $erpData->erp_system ?? '' }}">
                                        {{ Str::limit($erpData->erp_system ?? '?', 8) }}
                                    </span>
                                @empty
                                    <span class="text-xs text-gray-600">-</span>
                                @endforelse
                            </div>
                        </td>

                        {{-- Akcje --}}
                        <td class="px-3 py-3 text-right">
                            <a href="{{ route('admin.products.edit', $product->id) }}"
                               class="inline-flex items-center text-sm text-blue-400 hover:text-blue-300 transition-colors"
                               title="Zobacz produkt">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-3 py-12 text-center text-gray-500">
                            <svg class="mx-auto h-10 w-10 mb-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm font-medium text-gray-400">Wszystkie produkty sa przypisane</p>
                            <p class="text-xs text-gray-600 mt-1">Nie znaleziono produktow bez przypisania dla wybranego filtru</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($this->brakProducts && $this->brakProducts->hasPages())
        <div class="mt-4 border-t border-gray-700 pt-4">
            {{ $this->brakProducts->links() }}
        </div>
    @endif
</div>
