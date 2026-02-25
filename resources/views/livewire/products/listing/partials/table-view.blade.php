{{-- Table View --}}
<div class="card glass-effect shadow-soft rounded-xl overflow-hidden">
    <div class="overflow-x-auto -mx-4 sm:mx-0">
        <div class="inline-block min-w-full align-middle">
            <table class="min-w-full divide-y divide-gray-700 product-list-table">
            <thead class="bg-card">
                <tr>
                    {{-- Bulk Select --}}
                    <th class="w-12 px-6 py-3">
                        <input type="checkbox"
                               wire:model.live="selectAll"
                               class="rounded border-primary text-orange-500 shadow-sm focus:ring-orange-500 focus:ring-opacity-50 bg-input">
                    </th>

                    {{-- ETAP_07d FAZA 7: Thumbnail Column --}}
                    <th class="product-list-thumbnail-cell text-center text-xs font-medium text-muted uppercase tracking-wider">
                        <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </th>

                    {{-- Sortable Headers --}}
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider cursor-pointer hover:bg-card-hover transition-all duration-300"
                        wire:click="setSortColumn('sku')">
                        <div class="flex items-center">
                            SKU
                            @if($sortBy === 'sku')
                                <svg class="w-4 h-4 ml-1 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                </svg>
                            @endif
                        </div>
                    </th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider cursor-pointer hover:bg-card-hover transition-all duration-300"
                        wire:click="setSortColumn('name')">
                        <div class="flex items-center">
                            Nazwa
                            @if($sortBy === 'name')
                                <svg class="w-4 h-4 ml-1 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                </svg>
                            @endif
                        </div>
                    </th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Typ
                    </th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Producent
                    </th>

                    {{-- Cena --}}
                    <th class="px-4 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider cursor-pointer hover:bg-card-hover transition-all duration-300"
                        wire:click="setSortColumn('price')">
                        <div class="flex items-center gap-1">
                            Cena
                            <button wire:click.stop="togglePriceDisplay"
                                    class="text-xs px-1 py-0.5 rounded bg-gray-700 hover:bg-gray-600 text-gray-300 transition-colors"
                                    title="Przelacz netto/brutto">
                                {{ $priceDisplayMode === 'netto' ? 'N' : 'B' }}
                            </button>
                            @if($sortBy === 'price')
                                <svg class="w-4 h-4 ml-1 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                </svg>
                            @endif
                        </div>
                    </th>

                    {{-- Stan --}}
                    <th class="px-4 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider cursor-pointer hover:bg-card-hover transition-all duration-300"
                        wire:click="setSortColumn('stock')">
                        <div class="flex items-center">
                            Stan
                            @if($sortBy === 'stock')
                                <svg class="w-4 h-4 ml-1 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                </svg>
                            @endif
                        </div>
                    </th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Status
                    </th>

                    {{-- ETAP: Product Status Column (2026-02-04) - Replaces PrestaShop Sync --}}
                    <th class="px-3 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Zgodność
                        </div>
                    </th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider cursor-pointer hover:bg-card-hover transition-all duration-300"
                        wire:click="setSortColumn('updated_at')">
                        <div class="flex items-center">
                            Ostatnia aktualizacja
                            @if($sortBy === 'updated_at')
                                <svg class="w-4 h-4 ml-1 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                </svg>
                            @endif
                        </div>
                    </th>

                    <th class="px-6 py-3 text-right text-xs font-medium text-muted uppercase tracking-wider">
                        Akcje
                    </th>
                </tr>
            </thead>
            {{-- Multiple tbody elements are valid HTML5 - used for row grouping with Alpine state --}}
                @forelse($products as $product)
                    <tbody x-data="{
                            pressing: false,
                            expanded: false,
                            hasVariants: {{ $product->variants && $product->variants->count() > 0 ? 'true' : 'false' }}
                        }"
                        class="bg-card divide-y divide-border-primary product-tbody-group">
                    @include('livewire.products.listing.partials.table-row', ['product' => $product])
                    </tbody>
                @empty
                <tbody class="bg-card">
                    <tr>
                        <td colspan="12" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-muted mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                                </svg>
                                <p class="text-muted text-sm">
                                    @if($hasFilters)
                                        Nie znaleziono produktów pasujących do filtrów
                                    @else
                                        Brak produktów w systemie
                                    @endif
                                </p>
                                @if(!$hasFilters)
                                    <a href="{{ route('admin.products.create') }}"
                                       class="mt-3 btn-primary inline-flex items-center px-4 py-2 text-white text-sm font-medium rounded-lg transition-all duration-300">
                                        Dodaj pierwszy produkt
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                </tbody>
                @endforelse
            </table>
        </div>
    </div>
</div>

{{-- Table Pagination --}}
<div class="mt-6 flex items-center justify-between">
    <div class="flex items-center space-x-2 text-sm text-muted">
        <span>Wyświetl:</span>
        <select wire:model.live="perPage" class="form-input rounded text-sm focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50">
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
            <option value="200">200</option>
        </select>
        <span>na stronę</span>
    </div>

    <div>
        {{ $products->links() }}
    </div>
</div>
