<div class="container-fluid px-4">
    {{-- Header Section --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="text-primary fw-bold mb-1">
                        <i class="fas fa-tags me-2"></i>Grupy Cenowe
                    </h2>
                    <p class="text-muted mb-0">
                        Zarządzanie grupami cenowymi systemu PPM-CC-Laravel
                    </p>
                </div>

                @can('prices.groups')
                <button wire:click="create" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Nowa Grupa
                </button>
                @endcan
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-primary mb-2">
                        <i class="fas fa-tags fa-2x"></i>
                    </div>
                    <h4 class="fw-bold mb-1">{{ $totalGroups }}</h4>
                    <small class="text-muted">Łącznie grup</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-success mb-2">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <h4 class="fw-bold mb-1">{{ $activeGroups }}</h4>
                    <small class="text-muted">Aktywne grupy</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-warning mb-2">
                        <i class="fas fa-star fa-2x"></i>
                    </div>
                    <h4 class="fw-bold mb-1">{{ $defaultGroup ? $defaultGroup->name : 'Brak' }}</h4>
                    <small class="text-muted">Grupa domyślna</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-info mb-2">
                        <i class="fas fa-percentage fa-2x"></i>
                    </div>
                    <h4 class="fw-bold mb-1">
                        {{ $defaultGroup ? number_format($defaultGroup->margin_percentage, 1) . '%' : 'N/A' }}
                    </h4>
                    <small class="text-muted">Domyślna marża</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters and Search --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" placeholder="Szukaj grup cenowych..."
                               wire:model.live.debounce.300ms="search">
                    </div>
                </div>

                <div class="col-md-2">
                    <select class="form-select" wire:model.live="filterActive">
                        <option value="all">Wszystkie</option>
                        <option value="active">Aktywne</option>
                        <option value="inactive">Nieaktywne</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <select class="form-select" wire:model.live="sortBy">
                        <option value="sort_order">Sortuj według porządku</option>
                        <option value="name">Sortuj według nazwy</option>
                        <option value="margin_percentage">Sortuj według marży</option>
                        <option value="products_count">Sortuj według liczby produktów</option>
                        <option value="created_at">Sortuj według daty</option>
                    </select>
                </div>

                @if(!empty($selectedGroups))
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" wire:model="bulkAction">
                            <option value="">Wybierz akcję...</option>
                            <option value="activate">Aktywuj wybrane</option>
                            <option value="deactivate">Dezaktywuj wybrane</option>
                        </select>
                        <button class="btn btn-outline-primary btn-sm" wire:click="executeBulkAction">
                            Wykonaj
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Price Groups Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            @can('prices.groups')
                            <th width="40">
                                <input type="checkbox" class="form-check-input"
                                       wire:model="selectAll"
                                       @if(count($selectedGroups) === count($priceGroups)) checked @endif>
                            </th>
                            @endcan

                            <th wire:click="sortBy('sort_order')" style="cursor: pointer;">
                                <i class="fas fa-sort-numeric-down me-2"></i>Porządek
                                @if($sortBy === 'sort_order')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>

                            <th wire:click="sortBy('name')" style="cursor: pointer;">
                                <i class="fas fa-tag me-2"></i>Grupa Cenowa
                                @if($sortBy === 'name')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>

                            <th wire:click="sortBy('margin_percentage')" style="cursor: pointer;">
                                <i class="fas fa-percentage me-2"></i>Marża
                                @if($sortBy === 'margin_percentage')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>

                            <th wire:click="sortBy('products_count')" style="cursor: pointer;">
                                <i class="fas fa-cube me-2"></i>Produkty
                                @if($sortBy === 'products_count')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>

                            <th><i class="fas fa-cogs me-2"></i>Status</th>

                            <th><i class="fas fa-tools me-2"></i>Akcje</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($priceGroups as $group)
                        <tr>
                            @can('prices.groups')
                            <td>
                                <input type="checkbox" class="form-check-input"
                                       wire:model="selectedGroups" value="{{ $group->id }}">
                            </td>
                            @endcan

                            <td>
                                <span class="badge bg-secondary">{{ $group->sort_order }}</span>
                            </td>

                            <td>
                                <div class="d-flex align-items-center">
                                    @if($group->is_default)
                                        <i class="fas fa-star text-warning me-2" title="Grupa domyślna"></i>
                                    @endif

                                    <div>
                                        <div class="fw-semibold">{{ $group->name }}</div>
                                        <small class="text-muted">{{ $group->code }}</small>
                                        @if($group->description)
                                            <div class="text-muted small">{{ Str::limit($group->description, 50) }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td>
                                @if($group->margin_percentage)
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-info me-2">
                                            {{ number_format($group->margin_percentage, 1) }}%
                                        </span>
                                        @php
                                            $examplePrice = $group->calculatePrice(100);
                                        @endphp
                                        @if($examplePrice['net'] > 0)
                                            <small class="text-muted">
                                                (100 → {{ number_format($examplePrice['net'], 2) }} PLN)
                                            </small>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">Brak marży</span>
                                @endif
                            </td>

                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-primary me-2">{{ $group->prices_count }}</span>
                                    @if($group->prices_count > 0)
                                        <small class="text-muted">produktów</small>
                                    @else
                                        <small class="text-muted">brak cen</small>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <div class="d-flex flex-column gap-1">
                                    @if($group->is_active)
                                        <span class="badge bg-success">Aktywna</span>
                                    @else
                                        <span class="badge bg-secondary">Nieaktywna</span>
                                    @endif

                                    @if($group->is_default)
                                        <span class="badge bg-warning">Domyślna</span>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <div class="btn-group" role="group">
                                    @can('prices.groups')
                                    <button class="btn btn-outline-primary btn-sm"
                                            wire:click="edit({{ $group->id }})"
                                            title="Edytuj">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    @endcan

                                    @can('prices.groups')
                                    @if($group->canDelete())
                                    <button class="btn btn-outline-danger btn-sm"
                                            wire:click="confirmDelete({{ $group->id }})"
                                            title="Usuń">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @else
                                    <button class="btn btn-outline-secondary btn-sm" disabled
                                            title="Nie można usunąć - grupa ma przypisane ceny lub jest domyślna">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                    @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Brak grup cenowych spełniających kryteria wyszukiwania.</p>
                                @can('prices.groups')
                                <button wire:click="create" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Utwórz pierwszą grupę
                                </button>
                                @endcan
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($priceGroups->hasPages())
        <div class="card-footer">
            {{ $priceGroups->links() }}
        </div>
        @endif
    </div>

    {{-- Create/Edit Form Modal --}}
    @if($showForm)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-{{ $editMode ? 'edit' : 'plus' }} me-2"></i>
                        {{ $editMode ? 'Edytuj Grupę Cenową' : 'Nowa Grupa Cenowa' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cancel"></button>
                </div>

                <div class="modal-body">
                    <form wire:submit.prevent="save">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Nazwa grupy cenowej <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           wire:model="name" placeholder="np. Dealer Premium">
                                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Kod grupy <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control @error('code') is-invalid @enderror"
                                               wire:model="code" placeholder="dealer_premium">
                                        <button type="button" class="btn btn-outline-secondary"
                                                wire:click="generateCode" title="Generuj kod z nazwy">
                                            <i class="fas fa-magic"></i>
                                        </button>
                                    </div>
                                    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Marża domyślna (%)</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control @error('margin_percentage') is-invalid @enderror"
                                               wire:model.live="margin_percentage"
                                               placeholder="0.00" step="0.01" min="-100" max="999.99">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    @error('margin_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror

                                    @if($margin_percentage)
                                        @php $example = $this->calculateExamplePrice(); @endphp
                                        <small class="text-muted">
                                            Przykład: 100 PLN → {{ number_format($example['net'], 2) }} PLN netto
                                            ({{ number_format($example['gross'], 2) }} PLN brutto)
                                        </small>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Porządek sortowania</label>
                                    <input type="number" class="form-control" wire:model="sort_order"
                                           placeholder="1" min="1">
                                    <small class="text-muted">Określa kolejność wyświetlania</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Opis grupy</label>
                            <textarea class="form-control" wire:model="description"
                                      rows="3" placeholder="Opis grupy cenowej..."></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           wire:model="is_active" id="is_active">
                                    <label class="form-check-label" for="is_active">
                                        Grupa aktywna
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           wire:model="is_default" id="is_default">
                                    <label class="form-check-label" for="is_default">
                                        Grupa domyślna
                                    </label>
                                    <small class="text-muted d-block">Tylko jedna grupa może być domyślna</small>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="cancel">Anuluj</button>
                    <button type="button" class="btn btn-primary" wire:click="save">
                        <i class="fas fa-save me-2"></i>
                        {{ $editMode ? 'Zapisz zmiany' : 'Utwórz grupę' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($deleteConfirmation)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-trash me-2"></i>Potwierdź usunięcie
                    </h5>
                </div>

                <div class="modal-body">
                    <p class="mb-3">Czy na pewno chcesz usunąć tę grupę cenową?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Uwaga!</strong> Ta akcja jest nieodwracalna.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                            wire:click="$set('deleteConfirmation', false)">
                        Anuluj
                    </button>
                    <button type="button" class="btn btn-danger" wire:click="delete">
                        <i class="fas fa-trash me-2"></i>Usuń grupę
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Flash Messages --}}
    @if(session('message'))
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
            <div class="toast show" role="alert">
                <div class="toast-header bg-success text-white">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong class="me-auto">Sukces</strong>
                </div>
                <div class="toast-body">
                    {{ session('message') }}
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
            <div class="toast show" role="alert">
                <div class="toast-header bg-danger text-white">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong class="me-auto">Błąd</strong>
                </div>
                <div class="toast-body">
                    {{ session('error') }}
                </div>
            </div>
        </div>
    @endif
</div>