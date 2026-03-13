{{-- Grid View --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    @forelse($products as $product)
        {{-- Clickable Card with hover/click animations --}}
        <div x-data="{ pressing: false }"
             @click="window.location.href = '{{ route('products.edit', $product) }}'"
             @mousedown="pressing = true"
             @mouseup="pressing = false"
             @mouseleave="pressing = false"
             :class="{ 'scale-[0.98] ring-2 ring-orange-500/50': pressing }"
             class="product-grid-card cursor-pointer card glass-effect rounded-xl shadow-soft border border-primary overflow-hidden transition-all duration-200 ease-out hover:shadow-xl hover:shadow-orange-500/10 hover:border-orange-500/30 hover:-translate-y-1">
            {{-- Product Image Placeholder --}}
            <div class="h-48 bg-card flex items-center justify-center">
                <svg class="w-12 h-12 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>

            {{-- Product Info --}}
            <div class="p-4">
                <div class="flex items-start justify-between mb-2">
                    <h3 class="text-sm font-medium text-primary line-clamp-2">
                        {{ $product->name }}
                    </h3>
                    <div @click.stop>
                        <input type="checkbox"
                               wire:key="grid-select-{{ $product->id }}"
                               value="{{ $product->id }}"
                               wire:model.live="selectedProducts"
                               class="rounded border-primary text-orange-500 shadow-sm focus:ring-orange-500 focus:ring-opacity-50 bg-input cursor-pointer">
                    </div>
                </div>

                <p class="text-xs text-muted mb-2">SKU: {{ $product->sku }}</p>

                <div class="flex items-center justify-between mb-3" @click.stop>
                    @if($product->productType)
                        <x-product-type-badge :type="$product->productType" />
                    @else
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-800 text-gray-200">
                        Brak typu
                    </span>
                    @endif

                    <button wire:click="toggleStatus({{ $product->id }})"
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium transition-colors
                                {{ $product->is_active
                                    ? 'bg-green-800 text-green-200 hover:bg-green-700'
                                    : 'bg-red-800 text-red-200 hover:bg-red-700' }}">
                        <span class="w-2 h-2 rounded-full mr-1 {{ $product->is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
                        {{ $product->is_active ? 'Aktywny' : 'Nieaktywny' }}
                    </button>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-between" @click.stop>
                    <span class="text-xs text-muted">
                        {{ $product->updated_at->format('d.m.Y') }}
                    </span>

                    <div class="flex items-center space-x-1">
                        {{-- Quick Preview --}}
                        <button wire:click="showProductPreview({{ $product->id }})"
                                class="p-1 text-muted hover:text-blue-500 transition-colors duration-300"
                                title="Szybki podglad">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>

                        {{-- Duplicate --}}
                        <button wire:click="duplicateProduct({{ $product->id }})"
                                class="p-1 text-muted hover:text-green-500 transition-colors duration-300"
                                title="Duplikuj">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>

                        {{-- Delete --}}
                        <button wire:click="confirmDelete({{ $product->id }})"
                                class="p-1 text-muted hover:text-red-500 transition-colors duration-300"
                                title="Usun">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-full">
            <div class="flex flex-col items-center py-12">
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
        </div>
    @endforelse
</div>

{{-- Grid Pagination --}}
@if($products->hasPages())
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
            {{ $products->links('components.pagination-compact') }}
        </div>
    </div>
@endif
