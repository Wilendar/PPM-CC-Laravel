<div class="import-panel-container">
    {{-- Flash Messages (compact) --}}
    @if (session()->has('message') || session()->has('error') || session()->has('warning'))
        <div class="flash-messages-row">
            @if (session()->has('message'))
                <div class="flash-message flash-{{ session('message_type', 'info') }}">{{ session('message') }}</div>
            @endif
            @if (session()->has('error'))
                <div class="flash-message flash-error">{{ session('error') }}</div>
            @endif
            @if (session()->has('warning'))
                <div class="flash-message flash-warning">{{ session('warning') }}</div>
            @endif
        </div>
    @endif

    {{-- COMPACT HEADER: Title + Stats + Actions w jednym wierszu --}}
    <div class="enterprise-card import-compact-header">
        <div class="import-header-row">
            {{-- Left: Title --}}
            <div class="import-title-section">
                <h1 class="import-title">Import produktow</h1>
                <span class="import-subtitle">Produkty oczekujace na uzupelnienie i publikacje</span>
            </div>

            {{-- Center: Stats badges --}}
            <div class="import-stats-badges">
                <span class="stat-badge stat-all">
                    <span class="stat-value">{{ $pendingProducts->total() }}</span>
                    <span class="stat-label">Wszystkie</span>
                </span>
                <span class="stat-badge stat-ready">
                    <span class="stat-value">{{ $readyCount ?? 0 }}</span>
                    <span class="stat-label">Gotowe</span>
                </span>
                <span class="stat-badge stat-incomplete">
                    <span class="stat-value">{{ $incompleteCount ?? 0 }}</span>
                    <span class="stat-label">Niekompletne</span>
                </span>
            </div>

            {{-- Right: Action buttons --}}
            <div class="import-actions">
                <button wire:click="openSKUPasteModal" class="btn-enterprise-primary btn-sm">
                    <svg class="icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Wklej SKU
                </button>
                <button wire:click="openCSVImportModal" class="btn-enterprise-secondary btn-sm">
                    <svg class="icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    Import CSV
                </button>
            </div>
        </div>
    </div>

    {{-- COMPACT FILTERS: Wszystko w jednym wierszu --}}
    <div class="enterprise-card import-compact-filters">
        <div class="filters-inline-row">
            {{-- Search (szersze) --}}
            <div class="filter-inline filter-search">
                <input
                    wire:model.live.debounce.300ms="search"
                    type="text"
                    placeholder="Szukaj po SKU, nazwie..."
                    class="input-text"
                />
            </div>

            {{-- Status dropdown --}}
            <div class="filter-inline">
                <select wire:model.live="statusFilter" class="select-field">
                    <option value="all">Wszystkie statusy</option>
                    <option value="incomplete">Niekompletne</option>
                    <option value="ready">Gotowe</option>
                    <option value="unpublished">Nieopublikowane</option>
                    <option value="published">Opublikowane</option>
                </select>
            </div>

            {{-- Type dropdown --}}
            <div class="filter-inline">
                <select wire:model.live="productTypeFilter" class="select-field">
                    <option value="all">Wszystkie typy</option>
                    @foreach ($productTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Session dropdown --}}
            <div class="filter-inline">
                <select wire:model.live="sessionFilter" class="select-field">
                    <option value="all">Wszystkie sesje</option>
                    @foreach ($importSessions as $session)
                        <option value="{{ $session->id }}">{{ $session->session_name }} ({{ $session->products_created }})</option>
                    @endforeach
                </select>
            </div>

            {{-- Reset button (tylko gdy sa aktywne filtry) --}}
            @if ($search || $statusFilter !== 'all' || $productTypeFilter !== 'all' || $sessionFilter !== 'all')
                <button wire:click="resetAllFilters" class="btn-reset-inline" title="Wyczysc filtry">
                    <svg class="icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            @endif
        </div>
    </div>

    {{-- Bulk Actions Bar --}}
    @if ($showBulkActions && count($selectedIds) > 0)
        <div class="bulk-actions-bar">
            <div class="bulk-selection-info">
                <span class="selected-count">{{ count($selectedIds) }} zaznaczonych</span>
                <button wire:click="deselectAll" class="btn-deselect-all">Odznacz wszystkie</button>
            </div>

            <div class="bulk-actions">
                <button
                    wire:click="selectAllOnPage"
                    class="btn-bulk-action"
                >
                    Zaznacz wszystkie na stronie
                </button>

                <button
                    wire:click="bulkDelete"
                    wire:confirm="Czy na pewno usunac zaznaczone produkty?"
                    class="btn-bulk-action btn-danger"
                >
                    Usuń zaznaczone
                </button>

                <button
                    wire:click="publishSelected"
                    wire:confirm="Czy opublikowac zaznaczone produkty?"
                    class="btn-bulk-action btn-primary"
                >
                    Publikuj zaznaczone
                </button>
            </div>
        </div>
    @endif

    {{-- Products Table --}}
    <div class="enterprise-card table-card">
        <div class="table-responsive">
            <table class="import-products-table">
                <thead>
                    <tr>
                        <th class="col-checkbox">
                            <input
                                type="checkbox"
                                wire:model.live="selectAll"
                                @change="$wire.selectAll ? $wire.selectAllOnPage() : $wire.deselectAll()"
                            />
                        </th>
                        <th class="col-image">Zdjęcie</th>
                        <th class="col-sku" wire:click="sortBy('sku')">
                            SKU
                            @if ($sortField === 'sku')
                                <span class="sort-indicator">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="col-name" wire:click="sortBy('name')">
                            Nazwa
                            @if ($sortField === 'name')
                                <span class="sort-indicator">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="col-type">Typ</th>
                        <th class="col-categories">Kategorie</th>
                        <th class="col-shops">Sklepy</th>
                        <th class="col-completion" wire:click="sortBy('completion_percentage')">
                            Kompletność
                            @if ($sortField === 'completion_percentage')
                                <span class="sort-indicator">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="col-status">Status</th>
                        <th class="col-actions">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pendingProducts as $product)
                        <tr wire:key="pending-product-{{ $product->id }}">
                            {{-- Checkbox --}}
                            <td>
                                <input
                                    type="checkbox"
                                    wire:model.live="selectedIds"
                                    value="{{ $product->id }}"
                                />
                            </td>

                            {{-- Image --}}
                            <td>
                                @if ($product->primary_image_path)
                                    <img
                                        src="{{ asset('storage/' . $product->primary_image_path) }}"
                                        alt="{{ $product->name }}"
                                        class="product-thumbnail"
                                    />
                                @else
                                    <div class="no-image-placeholder">
                                        <svg class="icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                @endif
                            </td>

                            {{-- SKU (editable) --}}
                            <td>
                                <input
                                    type="text"
                                    value="{{ $product->sku }}"
                                    wire:blur="updateSKU({{ $product->id }}, $event.target.value)"
                                    class="inline-edit-input"
                                />
                            </td>

                            {{-- Name (editable) --}}
                            <td>
                                <input
                                    type="text"
                                    value="{{ $product->name }}"
                                    wire:blur="updateName({{ $product->id }}, $event.target.value)"
                                    class="inline-edit-input"
                                />
                            </td>

                            {{-- Product Type --}}
                            <td>
                                <span class="badge badge-blue">
                                    {{ $product->productType?->name ?? 'Brak' }}
                                </span>
                            </td>

                            {{-- Categories --}}
                            <td>
                                <span class="badge badge-gray">
                                    {{ $product->category_count }} kat.
                                </span>
                            </td>

                            {{-- Shops --}}
                            <td>
                                <span class="badge badge-purple">
                                    {{ $product->shop_count }} skl.
                                </span>
                            </td>

                            {{-- Completion --}}
                            <td>
                                <div class="completion-indicator">
                                    <div class="completion-bar">
                                        <div
                                            class="completion-fill completion-{{ $this->getCompletionColor($product->completion_percentage) }}"
                                            style="width: {{ $product->completion_percentage }}%"
                                        ></div>
                                    </div>
                                    <span class="completion-text">{{ $product->completion_percentage }}%</span>
                                </div>
                            </td>

                            {{-- Status --}}
                            <td>
                                <span class="badge badge-{{ $this->getStatusColor($product) }}">
                                    {{ $product->status_label }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td>
                                <div class="action-buttons">
                                    <button
                                        wire:click="publishProduct({{ $product->id }})"
                                        class="btn-icon btn-icon-primary"
                                        title="Publikuj"
                                        @if (!$product->canPublish()) disabled @endif
                                    >
                                        <svg class="icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>

                                    <button
                                        wire:click="deleteProduct({{ $product->id }})"
                                        wire:confirm="Czy na pewno usunac produkt {{ $product->sku }}?"
                                        class="btn-icon btn-icon-danger"
                                        title="Usuń"
                                    >
                                        <svg class="icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="empty-state">
                                <div class="empty-state-content">
                                    <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <h3>Brak produktow do wyswietlenia</h3>
                                    <p>Zaimportuj produkty uzywajac przycisku "Wklej SKU" lub "Import CSV"</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($pendingProducts->hasPages())
            <div class="pagination-wrapper">
                {{ $pendingProducts->links() }}
            </div>
        @endif>
    </div>

    {{-- SKU Paste Modal (FAZA 3 implementation) --}}
    @if ($showSKUPasteModal)
        <div class="modal-backdrop">
            <div class="modal-content">
                <h2>Import SKU - Funkcja dostepna w FAZY 3</h2>
                <button wire:click="closeSKUPasteModal" class="btn-enterprise-secondary">Zamknij</button>
            </div>
        </div>
    @endif

    {{-- CSV Import Modal (FAZA 4 implementation) --}}
    @if ($showCSVImportModal)
        <div class="modal-backdrop">
            <div class="modal-content">
                <h2>Import CSV - Funkcja dostepna w FAZY 4</h2>
                <button wire:click="closeCSVImportModal" class="btn-enterprise-secondary">Zamknij</button>
            </div>
        </div>
    @endif
</div>
