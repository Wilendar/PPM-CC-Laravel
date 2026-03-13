{{-- Export Product Table with search, sort, pagination --}}
@php
    $products = $this->exportProducts;
    $totalCount = $this->exportProductsCount;
@endphp

{{-- Toolbar --}}
<div class="export-filter-browser__toolbar">
    {{-- Search --}}
    <div class="export-filter-browser__search relative">
        <svg class="absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="text"
               wire:model.live.debounce.300ms="exportSearch"
               placeholder="Szukaj po SKU lub nazwie..."
               class="form-input-enterprise">
    </div>

    {{-- Bulk actions --}}
    <div class="export-filter-browser__toolbar-actions">
        <button wire:click="excludeAllFromFilter"
                wire:confirm="Wyklucz wszystkie produkty pasujace do filtrow (max 10 000)?"
                type="button"
                class="export-filter-browser__toolbar-btn export-filter-browser__toolbar-btn--danger">
            Wyklucz wszystkie
        </button>
        @if(!empty($excludedProductIds))
            <button wire:click="restoreAllProducts" type="button"
                    class="export-filter-browser__toolbar-btn export-filter-browser__toolbar-btn--success">
                Przywroc ({{ count($excludedProductIds) }})
            </button>
        @endif

        {{-- Per page --}}
        <div class="export-filter-browser__per-page">
            <select wire:model.live="exportPerPage">
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
    </div>
</div>

{{-- Table --}}
<div class="export-filter-browser__table-wrap">
    @if(!$this->hasActiveProductFilter)
        <div class="export-filter-browser__empty">
            <div class="export-filter-browser__empty-icon">
                <svg class="mx-auto h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
            </div>
            <p class="export-filter-browser__empty-text">Wybierz co najmniej jedna kategorie</p>
            <p class="export-filter-browser__empty-hint">Zaznacz kategorie w panelu po lewej, aby wyswietlic produkty do eksportu</p>
        </div>
    @elseif($products->isEmpty())
        <div class="export-filter-browser__empty">
            <div class="export-filter-browser__empty-icon">
                <svg class="mx-auto h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
            </div>
            <p class="export-filter-browser__empty-text">Brak produktow pasujacych do filtrow</p>
            <p class="export-filter-browser__empty-hint">Zmien kryteria filtrowania lub wybierz inne kategorie</p>
        </div>
    @else
        <table class="export-filter-browser__table">
            <thead>
                <tr>
                    <th class="export-filter-browser__checkbox-cell">
                        <span class="sr-only">Wybierz</span>
                    </th>
                    <th class="export-filter-browser__mini-cell">Mini</th>
                    <th class="sortable {{ $exportSortBy === 'sku' ? 'sorted' : '' }}"
                        wire:click="sortExportBy('sku')">
                        SKU
                        <span class="sort-icon">
                            @if($exportSortBy === 'sku')
                                {{ $exportSortDirection === 'asc' ? "\u{25B2}" : "\u{25BC}" }}
                            @else
                                &#x25B4;
                            @endif
                        </span>
                    </th>
                    <th class="sortable {{ $exportSortBy === 'name' ? 'sorted' : '' }}"
                        wire:click="sortExportBy('name')">
                        Nazwa
                        <span class="sort-icon">
                            @if($exportSortBy === 'name')
                                {{ $exportSortDirection === 'asc' ? "\u{25B2}" : "\u{25BC}" }}
                            @else
                                &#x25B4;
                            @endif
                        </span>
                    </th>
                    <th>Cena</th>
                    <th>Stan</th>
                    <th class="sortable {{ $exportSortBy === 'is_active' ? 'sorted' : '' }}"
                        wire:click="sortExportBy('is_active')">
                        Status
                        <span class="sort-icon">
                            @if($exportSortBy === 'is_active')
                                {{ $exportSortDirection === 'asc' ? "\u{25B2}" : "\u{25BC}" }}
                            @else
                                &#x25B4;
                            @endif
                        </span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                    @include('livewire.admin.export.partials.export-product-row', [
                        'product' => $product,
                        'isExcluded' => in_array($product->id, $excludedProductIds, true),
                    ])
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- Pagination --}}
@if($products->hasPages())
    <div class="export-filter-browser__pagination">
        <span>
            Wyswietlono {{ $products->firstItem() }}-{{ $products->lastItem() }} z {{ $products->total() }}
        </span>
        <div>
            {{ $products->links('components.pagination-compact') }}
        </div>
    </div>
@endif
