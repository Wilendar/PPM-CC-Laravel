{{-- RIGHT COLUMN: Detail / Products --}}
<div class="feature-browser__column">
    @if($selectedLocationId && $selectedLocationData)
        {{-- Column header with location info --}}
        <div class="feature-browser__column-header">
            <span class="flex items-center gap-2">
                <svg class="w-3.5 h-3.5 header-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                PRODUKTY
            </span>
            <div class="flex items-center gap-1">
                <button wire:click="editLocation({{ $selectedLocationData->id }})"
                        class="location-action-btn" title="Edytuj">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                </button>
                <button wire:click="deleteLocation({{ $selectedLocationData->id }})"
                        wire:confirm="Czy na pewno chcesz usunac te lokalizacje?"
                        class="location-action-btn location-action-btn--danger" title="Usun">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Location detail header --}}
        <div class="location-browser__detail-header">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-white">{{ $selectedLocationData->code }}</h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        <span class="location-badge location-badge--{{ $selectedLocationData->pattern_type }}">
                            {{ $selectedLocationData->pattern_type }}
                        </span>
                        &middot; {{ $selectedLocationData->product_count }} produktow
                    </p>
                </div>
            </div>
            @if($selectedLocationData->description)
                <p class="text-xs text-gray-400 mt-2">{{ $selectedLocationData->description }}</p>
            @endif
        </div>

        {{-- Product search --}}
        <div class="location-browser__search">
            <span class="location-browser__search-dot"></span>
            <input type="text"
                   wire:model.live.debounce.300ms="productSearch"
                   placeholder="Szukaj produktu...">
        </div>

        {{-- Product list --}}
        <div class="feature-browser__column-content">
            @forelse($products as $stockItem)
                <div wire:key="stock-{{ $stockItem->id }}"
                     class="feature-browser__product-item">
                    <div class="location-browser__product-icon">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium location-link-brand truncate">
                            {{ $stockItem->product->sku ?? 'N/A' }}
                        </p>
                        <p class="text-xs text-gray-400 truncate">
                            {{ $stockItem->product->name ?? '' }}
                        </p>
                        @if($stockItem->product->manufacturerRelation ?? null)
                            <p class="text-xs text-gray-500 truncate">
                                {{ $stockItem->product->manufacturerRelation->name }}
                            </p>
                        @endif
                    </div>
                    <a href="/admin/products/{{ $stockItem->product_id }}/edit"
                       class="text-xs location-link-brand flex-shrink-0"
                       title="Edytuj produkt">
                        Edytuj
                    </a>
                </div>
            @empty
                <div class="flex items-center justify-center h-full text-gray-500 py-8">
                    <p class="text-xs">Brak produktow w tej lokalizacji.</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
            <div class="feature-browser__column-footer">
                {{ $products->links('livewire::simple-tailwind') }}
            </div>
        @else
            <div class="feature-browser__column-footer">
                {{ $selectedLocationData->product_count ?? 0 }} produktow
            </div>
        @endif
    @else
        {{-- Column header placeholder --}}
        <div class="feature-browser__column-header">
            <span class="flex items-center gap-2">
                <svg class="w-3.5 h-3.5 header-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                SZCZEGOLY
            </span>
        </div>

        {{-- Placeholder when no location selected --}}
        <div class="feature-browser__column-content">
            <div class="feature-browser__empty-state">
                <div class="feature-browser__empty-state-icon">
                    <svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <p class="feature-browser__empty-state-text">Wybierz lokalizacje z drzewa</p>
                <p class="feature-browser__empty-state-hint">aby zobaczyc przypisane produkty</p>
            </div>
        </div>
    @endif
</div>
