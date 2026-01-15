{{--
    CompatibilityManagement - Tile-Based Vehicle Compatibility UI
    ETAP_05d FAZA 3 - Per-shop compatibility editing

    Features:
    - Tile-based vehicle selection (click = toggle)
    - Per-shop filtering
    - Smart suggestions panel
    - Collapsible brand sections
--}}
<div class="compatibility-management-panel">
    @vite(['resources/css/products/compatibility-tiles.css'])

    {{-- Header --}}
    <div class="compat-panel-header">
        <div class="compat-header-content">
            <div class="compat-header-title">
                <i class="fas fa-link"></i>
                <div>
                    <h1>Dopasowania Czesci Zamiennych</h1>
                    <p>Zarzadzanie globalnymi dopasowaniami czesci do pojazdow - centralna baza dla wszystkich sklepow</p>
                </div>
            </div>

            {{-- Statistics --}}
            <div class="compat-stats">
                <div class="compat-stat">
                    <span class="compat-stat-value">{{ $statistics['total_compatibilities'] ?? 0 }}</span>
                    <span class="compat-stat-label">Dopasowan</span>
                </div>
                <div class="compat-stat">
                    <span class="compat-stat-value">{{ $statistics['unique_products'] ?? 0 }}</span>
                    <span class="compat-stat-label">Czesci</span>
                </div>
                <div class="compat-stat">
                    <span class="compat-stat-value">{{ $statistics['unique_vehicles'] ?? 0 }}</span>
                    <span class="compat-stat-label">Pojazdow</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Bar --}}
    <div class="compat-filters-bar">
        <div class="compat-filters-row">
            {{-- Search Parts --}}
            <div class="compat-filter-item compat-filter-search">
                <label>
                    <i class="fas fa-search"></i>
                    Szukaj Czesci
                </label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="searchPart"
                    placeholder="SKU lub nazwa czesci..."
                    class="compat-filter-input"
                />
            </div>

            {{-- Shop Context --}}
            <div class="compat-filter-item">
                <label>
                    <i class="fas fa-store"></i>
                    Kontekst Sklepu
                </label>
                <select wire:model.live="shopContext" class="compat-filter-select">
                    <option value="">Dane domyslne</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Brand Filter --}}
            <div class="compat-filter-item">
                <label>
                    <i class="fas fa-car"></i>
                    Marka Pojazdu
                </label>
                <select wire:model.live="filterBrand" class="compat-filter-select">
                    <option value="">Wszystkie marki</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand }}">{{ $brand }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Vehicle Search --}}
            <div class="compat-filter-item">
                <label>
                    <i class="fas fa-motorcycle"></i>
                    Szukaj Pojazdu
                </label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="vehicleSearch"
                    placeholder="Marka lub model..."
                    class="compat-filter-input"
                />
            </div>

            {{-- No Matches Filter --}}
            <div class="compat-filter-item compat-filter-checkbox">
                <label class="compat-checkbox-label">
                    <input
                        type="checkbox"
                        wire:model.live="filterNoMatches"
                        class="compat-checkbox"
                    />
                    <span>
                        <i class="fas fa-exclamation-circle"></i>
                        Tylko bez dopasowan
                    </span>
                </label>
            </div>

            {{-- Reset Button --}}
            @if($searchPart || $shopContext || $filterBrand || $vehicleSearch || $filterNoMatches)
                <button wire:click="resetFilters" class="compat-btn-reset">
                    <i class="fas fa-undo"></i>
                    Resetuj
                </button>
            @endif
        </div>
    </div>

    {{-- Main Content: Two-Column Layout --}}
    <div class="compat-main-content">
        {{-- Left: Parts List --}}
        <div class="compat-parts-column">
            <div class="compat-parts-header">
                <h3>
                    <i class="fas fa-puzzle-piece"></i>
                    Lista Czesci
                </h3>
                @if(count($selectedPartIds) > 0)
                    <button wire:click="openBulkEdit" class="compat-btn-bulk">
                        <i class="fas fa-edit"></i>
                        Edycja masowa ({{ count($selectedPartIds) }})
                    </button>
                @endif
            </div>

            <div class="compat-parts-list">
                @forelse($parts as $part)
                    <div
                        wire:key="part-{{ $part->id }}"
                        class="compat-part-item {{ $editingProductId === $part->id ? 'compat-part-item--active' : '' }} {{ $this->productHasUnsavedChanges($part->id) ? 'compat-part-item--unsaved' : '' }}"
                        wire:click="editPart({{ $part->id }})"
                    >
                        <div class="compat-part-checkbox" wire:click.stop="togglePartSelection({{ $part->id }})">
                            <input
                                type="checkbox"
                                {{ in_array($part->id, $selectedPartIds) ? 'checked' : '' }}
                            />
                        </div>

                        <div class="compat-part-info">
                            <span class="compat-part-sku">
                                {{ $part->sku }}
                                @if($this->productHasUnsavedChanges($part->id))
                                    <span class="compat-unsaved-badge" title="Niezapisane zmiany">
                                        <i class="fas fa-exclamation-circle"></i>
                                    </span>
                                @endif
                            </span>
                            <span class="compat-part-name">{{ Str::limit($part->name, 40) }}</span>
                        </div>

                        <div class="compat-part-counts">
                            <span class="inline-flex items-center justify-center min-w-[1.5rem] px-1.5 py-0.5 text-xs font-medium rounded bg-blue-600 text-white" title="Oryginal">
                                {{ $part->original_count }}
                            </span>
                            <span class="inline-flex items-center justify-center min-w-[1.5rem] px-1.5 py-0.5 text-xs font-medium rounded bg-orange-600 text-white" title="Zamiennik">
                                {{ $part->replacement_count }}
                            </span>
                        </div>

                        <div class="compat-part-status">
                            @if($part->sync_status === 'synced')
                                <span class="compat-status-badge compat-status-synced" title="{{ $part->sync_shop_names ?? '' }}">
                                    <i class="fas fa-check-circle"></i>
                                    {{ $part->sync_shops_count }} {{ $part->sync_shops_count === 1 ? 'sklep' : 'sklepy' }}
                                </span>
                            @else
                                <span class="compat-status-badge compat-status-not-published">
                                    <i class="fas fa-times-circle"></i>
                                    Brak
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="compat-parts-empty">
                        <i class="fas fa-search"></i>
                        <p>Brak czesci spelniajacych kryteria</p>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            <div class="compat-parts-pagination">
                {{ $parts->links() }}
            </div>
        </div>

        {{-- Right: Vehicle Tiles --}}
        <div class="compat-vehicles-column">
            @if($editingProductId)
                @php
                    $editingPart = $parts->firstWhere('id', $editingProductId);
                @endphp

                {{-- Editing Header --}}
                <div class="compat-vehicles-header">
                    <div class="compat-editing-info">
                        <h3>
                            <i class="fas fa-cog"></i>
                            Edycja dopasowań:
                        </h3>
                        <span class="compat-editing-sku">{{ $editingPart?->sku ?? 'Produkt' }}</span>
                    </div>

                    <div class="compat-selection-counts">
                        <span class="compat-count-label">
                            <i class="fas fa-circle" style="color: var(--compat-original);"></i>
                            Oryginal: {{ $this->getOriginalCount() }}
                        </span>
                        <span class="compat-count-label">
                            <i class="fas fa-circle" style="color: var(--compat-zamiennik);"></i>
                            Zamiennik: {{ $this->getZamiennikCount() }}
                        </span>
                    </div>
                </div>

                {{-- Suggestions Panel --}}
                @if($showSuggestions && $suggestions->count() > 0)
                    <div class="suggestions-panel">
                        <div class="suggestions-panel__header">
                            <h4>
                                <i class="fas fa-lightbulb"></i>
                                Sugestie AI ({{ $suggestions->count() }})
                            </h4>
                            <button wire:click="$toggle('showSuggestions')" class="suggestions-toggle">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div class="suggestions-panel__list">
                            @foreach($suggestions as $suggestion)
                                <div class="suggestion-item" wire:key="sugg-{{ $suggestion->id }}">
                                    <div class="suggestion-info">
                                        <span class="suggestion-vehicle">
                                            {{ $suggestion->vehicleModel?->brand }} {{ $suggestion->vehicleModel?->model }}
                                        </span>
                                        <span class="suggestion-score">
                                            {{ number_format($suggestion->confidence_score * 100, 0) }}%
                                        </span>
                                    </div>
                                    <div class="suggestion-actions">
                                        <button
                                            wire:click="applySuggestion({{ $suggestion->id }}, 'original')"
                                            class="suggestion-btn suggestion-btn--original"
                                            title="Dodaj jako Oryginal"
                                        >
                                            <i class="fas fa-plus"></i> O
                                        </button>
                                        <button
                                            wire:click="applySuggestion({{ $suggestion->id }}, 'replacement')"
                                            class="suggestion-btn suggestion-btn--replacement"
                                            title="Dodaj jako Zamiennik"
                                        >
                                            <i class="fas fa-plus"></i> Z
                                        </button>
                                        <button
                                            wire:click="dismissSuggestion({{ $suggestion->id }})"
                                            class="suggestion-btn suggestion-btn--dismiss"
                                            title="Odrzuc"
                                        >
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Vehicle Tiles Grid by Brand --}}
                <div class="compat-tiles-container">
                    @forelse($vehiclesGrouped as $brand => $brandVehicles)
                        <div class="brand-section" wire:key="brand-{{ $brand }}">
                            <div
                                class="brand-section__header"
                                wire:click="toggleBrandCollapse('{{ $brand }}')"
                            >
                                <div class="brand-section__title">
                                    <i class="fas fa-chevron-{{ $this->isBrandCollapsed($brand) ? 'right' : 'down' }}"></i>
                                    <span>{{ $brand }}</span>
                                    <span class="brand-section__count">({{ $brandVehicles->count() }})</span>
                                </div>
                                <div class="brand-section__actions">
                                    <button
                                        wire:click.stop="selectAllInBrand('{{ $brand }}')"
                                        class="brand-btn brand-btn--select"
                                        title="Zaznacz wszystkie w marce"
                                    >
                                        <i class="fas fa-check-double"></i>
                                    </button>
                                    <button
                                        wire:click.stop="deselectAllInBrand('{{ $brand }}')"
                                        class="brand-btn brand-btn--deselect"
                                        title="Odznacz wszystkie w marce"
                                    >
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            @unless($this->isBrandCollapsed($brand))
                                <div class="vehicle-tiles-grid">
                                    @foreach($brandVehicles as $vehicle)
                                        <div
                                            wire:key="tile-{{ $vehicle->id }}"
                                            class="vehicle-tile {{ $this->getVehicleStateClass($vehicle->id) }}"
                                            wire:click="toggleVehicle({{ $vehicle->id }})"
                                        >
                                            <div class="vehicle-tile__content">
                                                {{-- 2025-12-08: Changed from brand/model to manufacturer/name (Product instead of VehicleModel) --}}
                                                <span class="vehicle-tile__brand">{{ $vehicle->manufacturer }}</span>
                                                <span class="vehicle-tile__model">{{ $vehicle->name }}</span>
                                                {{-- year_from/year_to not available in Product model --}}
                                            </div>

                                            {{-- Selection Indicator --}}
                                            @if($this->isBoth($vehicle->id))
                                                <div class="vehicle-tile__indicator vehicle-tile__indicator--both">
                                                    <span>O+Z</span>
                                                </div>
                                            @elseif($this->isOriginal($vehicle->id))
                                                <div class="vehicle-tile__indicator vehicle-tile__indicator--original">
                                                    <span>O</span>
                                                </div>
                                            @elseif($this->isZamiennik($vehicle->id))
                                                <div class="vehicle-tile__indicator vehicle-tile__indicator--zamiennik">
                                                    <span>Z</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endunless
                        </div>
                    @empty
                        <div class="compat-vehicles-empty">
                            <i class="fas fa-motorcycle"></i>
                            <p>Brak pojazdow dla wybranych filtrow</p>
                            @if($filterBrand || $shopContext)
                                <button wire:click="resetFilters" class="compat-btn-reset">
                                    <i class="fas fa-undo"></i>
                                    Resetuj filtry
                                </button>
                            @endif
                        </div>
                    @endforelse
                </div>

                {{-- Floating Action Bar - ZAWSZE widoczny przy edycji dla latwego przelaczania trybu --}}
                <div class="floating-action-bar floating-action-bar--visible"
                    @if($this->hasSyncJobsActive())
                        wire:poll.2s="refreshSyncStatus"
                    @endif
                >
                    <div class="floating-action-bar__left">
                        {{-- Selection Mode Toggle --}}
                        <div class="selection-mode-toggle">
                            <button
                                class="mode-btn {{ $selectionMode === 'original' ? 'mode-btn--active-original' : '' }}"
                                wire:click="setSelectionMode('original')"
                            >
                                <i class="{{ $selectionMode === 'original' ? 'fas' : 'far' }} fa-circle"></i>
                                Oryginal
                            </button>
                            <button
                                class="mode-btn {{ $selectionMode === 'zamiennik' ? 'mode-btn--active-zamiennik' : '' }}"
                                wire:click="setSelectionMode('zamiennik')"
                            >
                                <i class="{{ $selectionMode === 'zamiennik' ? 'fas' : 'far' }} fa-circle"></i>
                                Zamiennik
                            </button>
                        </div>

                        {{-- Pending Changes Counter --}}
                        @if($this->hasPendingChanges())
                            <span class="pending-changes-badge">
                                {{ $this->getPendingChangesCount() }} zmian
                            </span>
                        @endif

                        {{-- Sync Job Status Badge --}}
                        @if($this->hasSyncJobsActive())
                            @php $syncStatus = $this->getOverallSyncStatus(); @endphp
                            <span class="sync-status-badge sync-status-{{ $syncStatus }}">
                                @if($syncStatus === 'pending')
                                    <i class="fas fa-clock fa-spin"></i>
                                    Oczekuje...
                                @elseif($syncStatus === 'running')
                                    <i class="fas fa-sync fa-spin"></i>
                                    Synchronizacja...
                                @elseif($syncStatus === 'completed')
                                    <i class="fas fa-check"></i>
                                    Ukonczone
                                @elseif($syncStatus === 'failed')
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Blad
                                @endif
                            </span>
                        @endif
                    </div>

                    <div class="floating-action-bar__right">
                        <button
                            wire:click="cancelEdit"
                            class="floating-btn floating-btn--cancel"
                        >
                            <i class="fas fa-times"></i>
                            Anuluj
                        </button>
                        <button
                            wire:click="saveCompatibility"
                            class="floating-btn floating-btn--save"
                            @if($this->isSyncInProgress()) disabled @endif
                        >
                            <i class="fas fa-save"></i>
                            Zapisz
                        </button>
                        <button
                            wire:click="saveAndSync"
                            class="floating-btn floating-btn--sync"
                            @if($this->isSyncInProgress()) disabled @endif
                        >
                            @if($this->isSyncInProgress())
                                <i class="fas fa-spinner fa-spin"></i>
                                Synchronizacja...
                            @else
                                <i class="fas fa-cloud-upload-alt"></i>
                                Zapisz i wyslij
                            @endif
                        </button>
                    </div>
                </div>
            @else
                {{-- No Part Selected --}}
                <div class="compat-vehicles-placeholder">
                    <i class="fas fa-hand-pointer"></i>
                    <h3>Wybierz czesc z listy</h3>
                    <p>Kliknij na czesc po lewej stronie, aby edytowac jej dopasowania do pojazdow.</p>

                    <div class="compat-placeholder-legend">
                        <div class="legend-item">
                            <span class="legend-color legend-color--original"></span>
                            <span>Oryginal - czesc pasuje do pojazdu oryginalnie</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color legend-color--zamiennik"></span>
                            <span>Zamiennik - czesc moze zastapic oryginalna</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color legend-color--both"></span>
                            <span>Oba - czesc jest zarowno oryginalem jak i zamiennikiem</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
