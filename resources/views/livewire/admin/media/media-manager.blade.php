{{-- Admin Media Manager - PEŁNY REDESIGN --}}
<div class="admin-media-manager"
     x-data="{
         lightbox: {
             open: false,
             images: [],
             currentIndex: 0,
             currentImage: null,
             productName: ''
         },
         openLightbox(images, index, productName) {
             this.lightbox.images = images;
             this.lightbox.currentIndex = index;
             this.lightbox.currentImage = images[index];
             this.lightbox.productName = productName;
             this.lightbox.open = true;
             document.body.style.overflow = 'hidden';
         },
         closeLightbox() {
             this.lightbox.open = false;
             document.body.style.overflow = '';
         },
         nextImage() {
             this.lightbox.currentIndex = (this.lightbox.currentIndex + 1) % this.lightbox.images.length;
             this.lightbox.currentImage = this.lightbox.images[this.lightbox.currentIndex];
         },
         prevImage() {
             this.lightbox.currentIndex = (this.lightbox.currentIndex - 1 + this.lightbox.images.length) % this.lightbox.images.length;
             this.lightbox.currentImage = this.lightbox.images[this.lightbox.currentIndex];
         }
     }"
     @keydown.escape.window="closeLightbox()"
     @keydown.arrow-right.window="lightbox.open && nextImage()"
     @keydown.arrow-left.window="lightbox.open && prevImage()">
    {{-- Page Header --}}
    <div class="enterprise-card mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-100">Zarządzanie Mediami</h1>
                <p class="text-gray-400 mt-1">Panel administracyjny zdjęć produktów</p>
            </div>
            <div class="flex gap-3">
                @if($selectMode)
                    <button wire:click="toggleSelectMode" class="btn-enterprise-secondary">
                        <i class="fas fa-times mr-2"></i>Anuluj zaznaczanie
                    </button>
                @else
                    <button wire:click="toggleSelectMode" class="btn-enterprise-secondary">
                        <i class="fas fa-check-square mr-2"></i>Zaznacz wiele
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Clickable Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="enterprise-card-stat {{ $statFilter === 'all' ? 'active' : '' }}"
             wire:click="filterByStat('all')">
            <div class="stat-icon bg-primary-600/20">
                <i class="fas fa-images text-primary-400"></i>
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ number_format($stats['totalMedia']) }}</span>
                <span class="stat-label">Wszystkie zdjęcia</span>
            </div>
        </div>
        <div class="enterprise-card-stat {{ $statFilter === 'orphaned' ? 'active' : '' }}"
             wire:click="filterByStat('orphaned')">
            <div class="stat-icon bg-yellow-600/20">
                <i class="fas fa-unlink text-yellow-400"></i>
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ number_format($stats['orphanedMedia']) }}</span>
                <span class="stat-label">Osierocone</span>
            </div>
        </div>
        <div class="enterprise-card-stat {{ $statFilter === 'pending' ? 'active' : '' }}"
             wire:click="filterByStat('pending')">
            <div class="stat-icon bg-blue-600/20">
                <i class="fas fa-clock text-blue-400"></i>
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ number_format($stats['pendingSync']) }}</span>
                <span class="stat-label">Oczekuje na sync</span>
            </div>
        </div>
        <div class="enterprise-card-stat {{ $statFilter === 'errors' ? 'active' : '' }}"
             wire:click="filterByStat('errors')">
            <div class="stat-icon bg-red-600/20">
                <i class="fas fa-exclamation-triangle text-red-400"></i>
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ number_format($stats['syncErrors']) }}</span>
                <span class="stat-label">Błędy sync</span>
            </div>
        </div>
    </div>

    {{-- Alert Message --}}
    @if($message)
        <div class="alert alert-{{ $messageType }} mb-4">
            <span>{{ $message }}</span>
            <button wire:click="clearMessage" class="ml-auto">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="tabs-enterprise mb-6">
        <button class="tab-enterprise {{ $activeTab === 'products' ? 'active' : '' }}"
                wire:click="switchTab('products')">
            <i class="fas fa-box icon"></i>
            <span>Produkty z galeriami</span>
        </button>
        <button class="tab-enterprise {{ $activeTab === 'orphaned' ? 'active' : '' }}"
                wire:click="switchTab('orphaned')">
            <i class="fas fa-unlink icon"></i>
            <span>Osierocone zdjęcia ({{ $stats['orphanedMedia'] }})</span>
        </button>
        <button class="tab-enterprise {{ $activeTab === 'sync' ? 'active' : '' }}"
                wire:click="switchTab('sync')">
            <i class="fas fa-sync icon"></i>
            <span>Synchronizacja</span>
        </button>
    </div>

    {{-- Tab Content --}}
    <div class="enterprise-card media-manager-content">
        {{-- Products Tab --}}
        @if($activeTab === 'products')
            {{-- Toolbar z Bulk Actions + Search + Filters --}}
            <div class="media-toolbar">
                <div class="media-toolbar-left">
                    {{-- Bulk Actions --}}
                    @if($selectMode)
                        <button wire:click="bulkSyncProducts"
                                class="btn-enterprise-primary"
                                {{ count($selectedProductIds) === 0 ? 'disabled' : '' }}>
                            <i class="fas fa-sync mr-2"></i>Sync ({{ count($selectedProductIds) }})
                        </button>
                    @endif
                    <div class="relative flex-1">
                        <input type="text" wire:model.live.debounce.300ms="search"
                               placeholder="Szukaj po SKU lub nazwie..."
                               class="input-enterprise w-full pl-10">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500"></i>
                    </div>
                    <select wire:model.live="filterSyncStatus" class="input-enterprise">
                        <option value="">Wszystkie statusy</option>
                        <option value="synced">Zsynchronizowane</option>
                        <option value="pending">Oczekujace</option>
                        <option value="error">Z bledami</option>
                    </select>
                    @if($search || $filterSyncStatus)
                        <button wire:click="resetFilters" class="btn-enterprise-ghost">
                            <i class="fas fa-times mr-2"></i>Wyczysc
                        </button>
                    @endif
                </div>
                <div class="media-toolbar-right">
                    {{-- Per Page Selector --}}
                    <select wire:model.live="perPage" class="input-enterprise input-enterprise-sm" title="Produktow na stronie">
                        <option value="12">12 / strona</option>
                        <option value="24">24 / strona</option>
                        <option value="48">48 / strona</option>
                        <option value="96">96 / strona</option>
                    </select>
                    {{-- View Toggle (Grid / List) --}}
                    <div class="media-view-toggle">
                        <button type="button"
                                wire:click="$set('viewMode', 'grid')"
                                class="media-view-btn {{ $viewMode === 'grid' ? 'active' : '' }}"
                                title="Widok kafelkowy">
                            <i class="fas fa-th"></i>
                        </button>
                        <button type="button"
                                wire:click="$set('viewMode', 'list')"
                                class="media-view-btn {{ $viewMode === 'list' ? 'active' : '' }}"
                                title="Widok listy">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                    <select wire:model.live="sortBy" class="input-enterprise">
                        <option value="updated_at">Ostatnio aktualizowane</option>
                        <option value="created_at">Ostatnio dodane</option>
                        <option value="media_count">Liczba zdjec</option>
                        <option value="sku">SKU (A-Z)</option>
                        <option value="name">Nazwa (A-Z)</option>
                    </select>
                </div>
            </div>

            {{-- Products Grid/List --}}
            @if($products->count() > 0)
                <div class="{{ $viewMode === 'list' ? 'media-products-list' : 'media-products-grid' }}">
                    @foreach($products as $product)
                        <div class="media-product-card {{ in_array($product->id, $selectedProductIds) ? 'selected' : '' }}"
                             x-data="{ expanded: false }"
                             @click="if (!$event.target.closest('button, a, input, .media-product-thumb')) { window.location.href = '{{ route('admin.products.edit', $product->id) }}?tab=gallery'; }">
                            {{-- Checkbox for bulk select --}}
                            @if($selectMode)
                                <input type="checkbox"
                                       class="media-product-checkbox"
                                       wire:click="toggleProductSelection({{ $product->id }})"
                                       {{ in_array($product->id, $selectedProductIds) ? 'checked' : '' }}>
                            @endif

                            {{-- Header --}}
                            <div class="media-product-header">
                                <span class="media-product-sku">{{ $product->sku }}</span>
                                <span class="media-product-count">{{ $product->media_count }} zdjec</span>
                            </div>

                            <div class="media-product-name">{{ Str::limit($product->name, 50) }}</div>

                            {{-- Gallery with sync status per image --}}
                            @php
                                $mediaUrls = $product->media->map(fn($m) => [
                                    'url' => $m->url,
                                    'name' => $m->original_name ?? 'Zdjęcie'
                                ])->values()->toArray();
                            @endphp
                            <div class="media-product-gallery"
                                 :class="{ 'expanded': expanded }"
                                 x-data="{ isListView: {{ $viewMode === 'list' ? 'true' : 'false' }} }">
                                @foreach($product->media as $index => $media)
                                    {{-- Wrapper for thumb + shop labels below --}}
                                    <div class="media-thumb-wrapper"
                                         x-show="isListView || expanded || {{ $index }} < 4"
                                         x-transition>
                                        {{-- Thumbnail with overlays --}}
                                        <div class="media-product-thumb clickable"
                                             @click="openLightbox({{ json_encode($mediaUrls) }}, {{ $index }}, '{{ addslashes($product->name) }}')">
                                            <img src="{{ $media->thumbnailUrl ?? $media->url }}"
                                                 alt="{{ $media->original_name }}" loading="lazy">
                                            {{-- Sync status badge --}}
                                            <span class="media-sync-badge media-sync-{{ $media->sync_status ?? 'pending' }}"
                                                  title="{{ ucfirst($media->sync_status ?? 'pending') }}">
                                                @if($media->sync_status === 'synced')
                                                    <i class="fas fa-check"></i>
                                                @elseif($media->sync_status === 'error')
                                                    <i class="fas fa-exclamation"></i>
                                                @else
                                                    <i class="fas fa-clock"></i>
                                                @endif
                                            </span>
                                            @if($media->is_primary)
                                                <span class="media-primary-badge">
                                                    <i class="fas fa-star"></i>
                                                </span>
                                            @endif
                                        </div>
                                        {{-- Shop labels BELOW thumbnail --}}
                                        @php
                                            $mediaSyncedShops = [];
                                            if ($media->prestashop_mapping && is_array($media->prestashop_mapping)) {
                                                foreach ($media->prestashop_mapping as $storeKey => $mapping) {
                                                    if (isset($mapping['synced_at']) || isset($mapping['ps_image_id'])) {
                                                        $storeId = (int) str_replace('store_', '', $storeKey);
                                                        $shop = $shops->firstWhere('id', $storeId);
                                                        $mediaSyncedShops[] = $shop?->name ?? "Store {$storeId}";
                                                    }
                                                }
                                            }
                                        @endphp
                                        <div class="media-thumb-shops">
                                            @if(count($mediaSyncedShops) > 0)
                                                @foreach($mediaSyncedShops as $shopName)
                                                    <span class="media-thumb-shop-badge" title="{{ $shopName }}">
                                                        {{ Str::limit($shopName, 8, '') }}
                                                    </span>
                                                @endforeach
                                            @else
                                                <span class="media-thumb-shop-badge no-sync">---</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Expand/Collapse button --}}
                            @if($product->media_count > 4)
                                <button type="button"
                                        class="media-expand-btn"
                                        @click="expanded = !expanded">
                                    <span x-show="!expanded">
                                        <i class="fas fa-chevron-down mr-1"></i>
                                        Pokaz wszystkie (+{{ $product->media_count - 4 }})
                                    </span>
                                    <span x-show="expanded">
                                        <i class="fas fa-chevron-up mr-1"></i>
                                        Zwin
                                    </span>
                                </button>
                            @endif

                            {{-- Card Footer with actions --}}
                            <div class="media-product-footer">
                                <a href="{{ route('admin.products.edit', $product->id) }}?tab=gallery"
                                   class="media-footer-btn media-footer-btn-primary">
                                    <i class="fas fa-edit"></i> Edytuj
                                </a>
                                <button wire:click="syncProductMedia({{ $product->id }})"
                                        class="media-footer-btn media-footer-btn-secondary"
                                        wire:loading.attr="disabled"
                                        wire:target="syncProductMedia({{ $product->id }})">
                                    <i class="fas fa-sync"></i> Sync
                                </button>
                                {{-- Count badge - visible only in list view (via CSS) --}}
                                <span class="media-footer-count">{{ $product->media_count }} zdjec</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $products->links() }}
                </div>
            @else
                <div class="text-center py-12 text-gray-400">
                    <i class="fas fa-images text-5xl mb-4"></i>
                    <p class="text-lg">Brak produktów z galeriami</p>
                    @if($search)
                        <p class="text-sm mt-2">Spróbuj zmienić kryteria wyszukiwania</p>
                    @endif
                </div>
            @endif
        @endif

        {{-- Orphaned Media Tab - REDESIGNED --}}
        @if($activeTab === 'orphaned')
            {{-- Orphaned Toolbar --}}
            <div class="orphaned-toolbar">
                <div class="orphaned-toolbar-left">
                    {{-- Search --}}
                    <div class="relative" style="min-width: 250px;">
                        <input type="text" wire:model.live.debounce.300ms="orphanedSearch"
                               placeholder="Szukaj po nazwie pliku..."
                               class="input-enterprise w-full pl-10">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500"></i>
                    </div>

                    {{-- Per Page Selector --}}
                    <select wire:model.live="orphanedPerPage" class="input-enterprise input-enterprise-sm" title="Zdjec na stronie">
                        <option value="12">12 / strona</option>
                        <option value="24">24 / strona</option>
                        <option value="48">48 / strona</option>
                        <option value="96">96 / strona</option>
                    </select>

                    {{-- Select Mode Toggle --}}
                    @if(!$selectMode)
                        <button wire:click="toggleSelectMode" class="btn-enterprise-secondary">
                            <i class="fas fa-check-square mr-2"></i>Zaznacz wiele
                        </button>
                    @else
                        <button wire:click="toggleSelectMode" class="btn-enterprise-ghost">
                            <i class="fas fa-times mr-2"></i>Anuluj zaznaczanie
                        </button>
                    @endif

                    {{-- Select All / Deselect All --}}
                    @if($selectMode && $orphanedMedia->count() > 0)
                        @if(count($selectedMediaIds) === 0)
                            <button wire:click="selectAllOrphaned" class="btn-enterprise-secondary">
                                <i class="fas fa-check-double mr-2"></i>Zaznacz wszystkie ({{ $orphanedMedia->count() }})
                            </button>
                        @else
                            <button wire:click="deselectAllOrphaned" class="btn-enterprise-secondary">
                                <i class="fas fa-square mr-2"></i>Odznacz wszystkie
                            </button>
                        @endif
                    @endif

                    {{-- Bulk Actions (visible when items selected) --}}
                    @if($selectMode && count($selectedMediaIds) > 0)
                        <button wire:click="openBulkAssignModal"
                                class="btn-enterprise-primary">
                            <i class="fas fa-link mr-2"></i>Przypisz ({{ count($selectedMediaIds) }})
                        </button>
                        <button wire:click="bulkDeleteOrphaned"
                                class="btn-enterprise-danger"
                                onclick="return confirm('Czy na pewno chcesz usunac {{ count($selectedMediaIds) }} zdjec?')">
                            <i class="fas fa-trash mr-2"></i>Usun ({{ count($selectedMediaIds) }})
                        </button>
                    @endif
                </div>
                <div class="orphaned-toolbar-right">
                    {{-- View Toggle --}}
                    <div class="media-view-toggle">
                        <button type="button"
                                wire:click="$set('orphanedViewMode', 'grid')"
                                class="media-view-btn {{ $orphanedViewMode === 'grid' ? 'active' : '' }}"
                                title="Widok kafelkowy">
                            <i class="fas fa-th"></i>
                        </button>
                        <button type="button"
                                wire:click="$set('orphanedViewMode', 'list')"
                                class="media-view-btn {{ $orphanedViewMode === 'list' ? 'active' : '' }}"
                                title="Widok listy">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>

            @if($orphanedMedia->count() > 0)
                {{-- Grid View --}}
                @if($orphanedViewMode === 'grid')
                    <div class="orphaned-media-grid">
                        @foreach($orphanedMedia as $media)
                            <div class="orphaned-media-card {{ in_array($media->id, $selectedMediaIds) ? 'selected' : '' }}">
                                {{-- Checkbox --}}
                                @if($selectMode)
                                    <input type="checkbox"
                                           class="orphaned-media-checkbox"
                                           wire:click="toggleMediaSelection({{ $media->id }})"
                                           {{ in_array($media->id, $selectedMediaIds) ? 'checked' : '' }}>
                                @endif

                                {{-- Image Preview --}}
                                <div class="orphaned-media-preview"
                                     @click="openLightbox([{url: '{{ $media->url }}', name: '{{ addslashes($media->original_name) }}'}], 0, '{{ addslashes($media->original_name) }}')">
                                    <img src="{{ $media->thumbnailUrl ?? $media->url }}"
                                         alt="{{ $media->original_name }}"
                                         loading="lazy">
                                </div>

                                {{-- Info --}}
                                <div class="orphaned-media-info">
                                    <span class="orphaned-media-name" title="{{ $media->original_name }}">
                                        {{ Str::limit($media->original_name, 25) }}
                                    </span>
                                    <div class="orphaned-media-meta">
                                        <span title="Rozmiar">{{ $media->human_size ?? '-' }}</span>
                                        <span title="Data">{{ $media->created_at->format('d.m.Y') }}</span>
                                    </div>
                                    {{-- Orphan History --}}
                                    @if($media->orphan_history_display)
                                        <div class="orphaned-media-history" title="Historia osierocenia">
                                            <i class="fas fa-history"></i>
                                            @if($media->orphan_history_display['product_sku'])
                                                <span class="orphan-history-sku">{{ $media->orphan_history_display['product_sku'] }}</span>
                                            @endif
                                            @if($media->orphan_history_display['product_name'])
                                                <span class="orphan-history-name">{{ Str::limit($media->orphan_history_display['product_name'], 20) }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="orphaned-media-history orphan-history-unknown">
                                            <i class="fas fa-question-circle"></i>
                                            <span>Brak historii</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Actions --}}
                                <div class="orphaned-media-actions">
                                    <button wire:click="openAssignModal({{ $media->id }})"
                                            class="orphaned-action-btn orphaned-action-assign"
                                            title="Przypisz do produktu">
                                        <i class="fas fa-link"></i>
                                        <span>Przypisz</span>
                                    </button>
                                    <button wire:click="deleteOrphanedMedia({{ $media->id }})"
                                            class="orphaned-action-btn orphaned-action-delete"
                                            title="Usun"
                                            onclick="return confirm('Czy na pewno chcesz usunac to zdjecie?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- List View --}}
                    <div class="orphaned-media-list">
                        <div class="orphaned-list-header orphaned-list-header-extended">
                            <div class="orphaned-list-col-check">
                                @if($selectMode)
                                    <span class="text-gray-500 text-xs">#</span>
                                @endif
                            </div>
                            <div class="orphaned-list-col-preview">Podglad</div>
                            <div class="orphaned-list-col-name">Nazwa pliku</div>
                            <div class="orphaned-list-col-history">Poprzedni produkt</div>
                            <div class="orphaned-list-col-size">Rozmiar</div>
                            <div class="orphaned-list-col-date">Data dodania</div>
                            <div class="orphaned-list-col-actions">Akcje</div>
                        </div>
                        @foreach($orphanedMedia as $media)
                            <div class="orphaned-list-row orphaned-list-row-extended {{ in_array($media->id, $selectedMediaIds) ? 'selected' : '' }}">
                                <div class="orphaned-list-col-check">
                                    @if($selectMode)
                                        <input type="checkbox"
                                               wire:click="toggleMediaSelection({{ $media->id }})"
                                               {{ in_array($media->id, $selectedMediaIds) ? 'checked' : '' }}>
                                    @endif
                                </div>
                                <div class="orphaned-list-col-preview">
                                    <img src="{{ $media->thumbnailUrl ?? $media->url }}"
                                         alt="{{ $media->original_name }}"
                                         class="orphaned-list-thumb"
                                         @click="openLightbox([{url: '{{ $media->url }}', name: '{{ addslashes($media->original_name) }}'}], 0, '{{ addslashes($media->original_name) }}')"
                                         loading="lazy">
                                </div>
                                <div class="orphaned-list-col-name">
                                    <span title="{{ $media->original_name }}">{{ Str::limit($media->original_name, 40) }}</span>
                                </div>
                                <div class="orphaned-list-col-history">
                                    @if($media->orphan_history_display)
                                        <div class="orphan-history-cell">
                                            @if($media->orphan_history_display['product_sku'])
                                                <span class="orphan-history-sku">{{ $media->orphan_history_display['product_sku'] }}</span>
                                            @endif
                                            @if($media->orphan_history_display['product_name'])
                                                <span class="orphan-history-name">{{ Str::limit($media->orphan_history_display['product_name'], 25) }}</span>
                                            @endif
                                            @if($media->orphan_history_display['orphaned_at'])
                                                <span class="orphan-history-date">{{ $media->orphan_history_display['orphaned_at'] }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="orphan-history-unknown">Brak danych</span>
                                    @endif
                                </div>
                                <div class="orphaned-list-col-size">{{ $media->human_size ?? '-' }}</div>
                                <div class="orphaned-list-col-date">{{ $media->created_at->format('d.m.Y H:i') }}</div>
                                <div class="orphaned-list-col-actions">
                                    <button wire:click="openAssignModal({{ $media->id }})"
                                            class="orphaned-list-btn orphaned-list-btn-assign"
                                            title="Przypisz">
                                        <i class="fas fa-link"></i>
                                    </button>
                                    <button wire:click="deleteOrphanedMedia({{ $media->id }})"
                                            class="orphaned-list-btn orphaned-list-btn-delete"
                                            title="Usun"
                                            onclick="return confirm('Czy na pewno?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Pagination with info --}}
                <div class="media-pagination-wrapper">
                    <div class="media-pagination-info">
                        Wyswietlono <strong>{{ $orphanedMedia->firstItem() ?? 0 }}-{{ $orphanedMedia->lastItem() ?? 0 }}</strong>
                        z <strong>{{ $orphanedMedia->total() }}</strong> osieroconych zdjec
                        @if($selectMode && count($selectedMediaIds) > 0)
                            | Zaznaczono: <strong>{{ count($selectedMediaIds) }}</strong>
                        @endif
                    </div>
                    <div>
                        {{ $orphanedMedia->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-12 text-gray-400">
                    <i class="fas fa-check-circle text-5xl mb-4 text-green-500"></i>
                    <p class="text-lg">Brak osieroconych zdjec</p>
                    <p class="text-sm mt-2">Wszystkie media sa przypisane do produktow</p>
                </div>
            @endif
        @endif

        {{-- Sync Tab --}}
        @if($activeTab === 'sync')
            <div class="space-y-6">
                {{-- Pending Sync Info --}}
                <div class="enterprise-card-inner">
                    <h3 class="text-lg font-semibold text-gray-200 mb-4">
                        <i class="fas fa-clock mr-2 text-blue-400"></i>
                        Oczekujące na synchronizację ({{ $stats['pendingSync'] }})
                    </h3>
                    @if($stats['pendingSync'] > 0)
                        <p class="text-gray-400 mb-4">
                            Wybierz sklep aby zsynchronizować oczekujące zdjęcia:
                        </p>
                        <div class="flex flex-wrap gap-3">
                            @foreach($shops as $shop)
                                <button wire:click="syncPendingToShop({{ $shop->id }})"
                                        class="btn-enterprise-secondary"
                                        wire:loading.attr="disabled">
                                    <i class="fas fa-sync mr-2"></i>
                                    {{ $shop->name }}
                                </button>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-400">Brak zdjęć oczekujących na synchronizację.</p>
                    @endif
                </div>

                {{-- Sync Errors --}}
                @if($stats['syncErrors'] > 0)
                    <div class="enterprise-card-inner border-red-600/30">
                        <h3 class="text-lg font-semibold text-gray-200 mb-4">
                            <i class="fas fa-exclamation-triangle mr-2 text-red-400"></i>
                            Błędy synchronizacji ({{ $stats['syncErrors'] }})
                        </h3>
                        <p class="text-gray-400 mb-4">
                            Niektóre zdjęcia nie zostały poprawnie zsynchronizowane.
                            Sprawdź logi lub spróbuj ponownie.
                        </p>
                        <button wire:click="loadStats" class="btn-enterprise-ghost">
                            <i class="fas fa-redo mr-2"></i>Odśwież statystyki
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Sticky Bulk Actions Bar --}}
    <div class="bulk-actions-bar {{ count($selectedMediaIds) > 0 || count($selectedProductIds) > 0 ? 'visible' : '' }}">
        <span class="bulk-actions-count">
            {{ count($selectedMediaIds) + count($selectedProductIds) }} zaznaczonych
        </span>

        @if(count($selectedMediaIds) > 0 && $activeTab === 'orphaned')
            <button wire:click="bulkDeleteOrphaned" class="btn-enterprise-danger"
                    wire:loading.attr="disabled">
                <i class="fas fa-trash mr-2"></i>Usuń zaznaczone
            </button>
        @endif

        @if(count($selectedProductIds) > 0 && $activeTab === 'products')
            <button wire:click="bulkSyncProducts" class="btn-enterprise-primary"
                    wire:loading.attr="disabled">
                <i class="fas fa-sync mr-2"></i>Synchronizuj zaznaczone
            </button>
        @endif

        <button wire:click="toggleSelectMode" class="btn-enterprise-ghost">
            <i class="fas fa-times mr-2"></i>Anuluj
        </button>
    </div>

    {{-- Assign Modal --}}
    @if($showAssignModal)
        <div class="assign-modal-overlay" wire:click.self="closeAssignModal">
            <div class="assign-modal" @click.stop>
                {{-- Header --}}
                <div class="assign-modal-header">
                    <h3 class="assign-modal-title">
                        <i class="fas fa-link mr-2 text-primary-400"></i>
                        @if($bulkAssignMode)
                            Przypisz {{ count($selectedMediaIds) }} zdjec do produktu
                        @else
                            Przypisz zdjecie do produktu
                        @endif
                    </h3>
                    <button wire:click="closeAssignModal" class="assign-modal-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                {{-- Content --}}
                <div class="assign-modal-content">
                    {{-- Media Preview (single mode) --}}
                    @if(!$bulkAssignMode && $this->assignMedia)
                        <div class="assign-media-preview">
                            <img src="{{ $this->assignMedia->thumbnailUrl ?? $this->assignMedia->url }}"
                                 alt="{{ $this->assignMedia->original_name }}">
                            <span class="assign-media-name">{{ $this->assignMedia->original_name }}</span>
                        </div>
                    @endif

                    {{-- Bulk mode info --}}
                    @if($bulkAssignMode)
                        <div class="assign-bulk-info">
                            <i class="fas fa-images mr-2"></i>
                            Wybrano {{ count($selectedMediaIds) }} zdjec do przypisania
                        </div>
                    @endif

                    {{-- Product Search --}}
                    <div class="assign-search-section">
                        <label class="assign-label">Wyszukaj produkt (SKU lub nazwa):</label>
                        <div class="relative">
                            <input type="text"
                                   wire:model.live.debounce.300ms="assignProductSearch"
                                   class="input-enterprise w-full pl-10"
                                   placeholder="Wpisz min. 2 znaki...">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500"></i>
                        </div>
                    </div>

                    {{-- Search Results --}}
                    @if(count($assignSearchResults) > 0)
                        <div class="assign-results">
                            @foreach($assignSearchResults as $result)
                                <div class="assign-result-item {{ $assignSelectedProductId === $result['id'] ? 'selected' : '' }}"
                                     wire:click="selectAssignProduct({{ $result['id'] }})">
                                    <div class="assign-result-sku">{{ $result['sku'] }}</div>
                                    <div class="assign-result-name">{{ Str::limit($result['name'], 40) }}</div>
                                    <div class="assign-result-count">{{ $result['media_count'] }} zdjec</div>
                                </div>
                            @endforeach
                        </div>
                    @elseif(strlen($assignProductSearch) >= 2)
                        <div class="assign-no-results">
                            <i class="fas fa-search mr-2"></i>
                            Nie znaleziono produktow
                        </div>
                    @endif

                    {{-- Selected Product --}}
                    @if($this->assignSelectedProduct)
                        <div class="assign-selected-product">
                            <div class="assign-selected-label">Wybrany produkt:</div>
                            <div class="assign-selected-info">
                                <span class="assign-selected-sku">{{ $this->assignSelectedProduct['sku'] }}</span>
                                <span class="assign-selected-name">{{ $this->assignSelectedProduct['name'] }}</span>
                                <span class="assign-selected-count">({{ $this->assignSelectedProduct['media_count'] }} zdjec)</span>
                            </div>
                        </div>
                    @endif

                    {{-- Options --}}
                    <div class="assign-options">
                        <label class="assign-option">
                            <input type="checkbox" wire:model="assignSetAsPrimary">
                            <span>Ustaw jako glowne zdjecie produktu</span>
                        </label>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="assign-modal-footer">
                    <button wire:click="closeAssignModal" class="btn-enterprise-secondary">
                        <i class="fas fa-times mr-2"></i>Anuluj
                    </button>
                    <button wire:click="confirmAssign"
                            class="btn-enterprise-primary"
                            {{ !$assignSelectedProductId ? 'disabled' : '' }}>
                        <i class="fas fa-check mr-2"></i>Przypisz
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Loading Overlay --}}
    @if($isLoading)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="enterprise-card p-8 text-center">
                <div class="animate-spin w-12 h-12 border-4 border-primary-500 border-t-transparent rounded-full mx-auto mb-4"></div>
                <p class="text-gray-300">Przetwarzanie...</p>
            </div>
        </div>
    @endif

    {{-- Lightbox Modal --}}
    <div x-show="lightbox.open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="media-lightbox"
         @click.self="closeLightbox()"
         x-cloak>
        <div class="media-lightbox-content">
            {{-- Close Button --}}
            <button type="button" class="media-lightbox-close" @click="closeLightbox()">
                <i class="fas fa-times"></i>
            </button>

            {{-- Navigation Buttons --}}
            <template x-if="lightbox.images.length > 1">
                <button type="button" class="media-lightbox-nav prev" @click.stop="prevImage()">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </template>
            <template x-if="lightbox.images.length > 1">
                <button type="button" class="media-lightbox-nav next" @click.stop="nextImage()">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </template>

            {{-- Image --}}
            <img :src="lightbox.currentImage?.url"
                 :alt="lightbox.currentImage?.name"
                 class="media-lightbox-image"
                 @click.stop>

            {{-- Info --}}
            <div class="media-lightbox-info">
                <span x-text="lightbox.productName"></span>
                <span class="mx-2">|</span>
                <span x-text="(lightbox.currentIndex + 1) + ' / ' + lightbox.images.length"></span>
            </div>
        </div>
    </div>
</div>
