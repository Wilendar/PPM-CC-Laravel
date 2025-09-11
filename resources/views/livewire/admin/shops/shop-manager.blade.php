<div class="shop-manager">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 mb-1">Sklepy PrestaShop</h2>
            <p class="text-muted mb-0">Zarządzanie połączeniami ze sklepami PrestaShop</p>
        </div>
        <button wire:click="startWizard" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Dodaj Sklep
        </button>
    </div>

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="card-title mb-0">Wszystkie</h6>
                            <h3 class="mb-0">{{ $stats['total'] }}</h3>
                        </div>
                        <i class="fas fa-store fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="card-title mb-0">Aktywne</h6>
                            <h3 class="mb-0">{{ $stats['active'] }}</h3>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="card-title mb-0">Połączone</h6>
                            <h3 class="mb-0">{{ $stats['connected'] }}</h3>
                        </div>
                        <i class="fas fa-link fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="card-title mb-0">Problemy</h6>
                            <h3 class="mb-0">{{ $stats['issues'] }}</h3>
                        </div>
                        <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="card-title mb-0">Do sync</h6>
                            <h3 class="mb-0">{{ $stats['sync_due'] }}</h3>
                        </div>
                        <i class="fas fa-sync fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters and Search --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input 
                            type="text" 
                            wire:model.debounce.300ms="search"
                            class="form-control" 
                            placeholder="Szukaj sklepów..."
                        >
                    </div>
                </div>
                <div class="col-md-3">
                    <select wire:model="statusFilter" class="form-select">
                        <option value="all">Wszystkie statusy</option>
                        <option value="active">Aktywne</option>
                        <option value="inactive">Nieaktywne</option>
                        <option value="connected">Połączone</option>
                        <option value="issues">Z problemami</option>
                        <option value="sync_due">Do synchronizacji</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select wire:model="sortBy" class="form-select">
                        <option value="name">Sortuj: Nazwa</option>
                        <option value="created_at">Sortuj: Data dodania</option>
                        <option value="last_sync_at">Sortuj: Ostatnia sync</option>
                        <option value="connection_status">Sortuj: Status</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button wire:click="resetFilters" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times me-1"></i>Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Shops List --}}
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Lista Sklepów</h5>
        </div>
        <div class="card-body p-0">
            @if($shops->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th wire:click="sortBy('name')" class="cursor-pointer">
                                    Nazwa 
                                    @if($sortBy === 'name')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </th>
                                <th>URL</th>
                                <th>Status</th>
                                <th>Wersja PS</th>
                                <th wire:click="sortBy('last_sync_at')" class="cursor-pointer">
                                    Ostatnia Sync
                                    @if($sortBy === 'last_sync_at')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </th>
                                <th>Sukces Rate</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($shops as $shop)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            @if($shop->is_active)
                                                <span class="badge bg-success">Aktywny</span>
                                            @else
                                                <span class="badge bg-secondary">Nieaktywny</span>
                                            @endif
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $shop->name }}</h6>
                                            @if($shop->description)
                                                <small class="text-muted">{{ Str::limit($shop->description, 50) }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ $shop->url }}" target="_blank" class="text-decoration-none">
                                        {{ Str::limit($shop->url, 40) }}
                                        <i class="fas fa-external-link-alt ms-1 text-muted"></i>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge {{ $shop->connection_badge }}">
                                        <i class="fas fa-{{ $shop->connection_status === 'connected' ? 'check-circle' : 'exclamation-triangle' }} me-1"></i>
                                        {{ ucfirst($shop->connection_status) }}
                                    </span>
                                    @if($shop->last_response_time)
                                        <small class="d-block text-muted">{{ $shop->last_response_time }}ms</small>
                                    @endif
                                </td>
                                <td>
                                    @if($shop->prestashop_version)
                                        <span class="badge {{ $shop->version_compatible ? 'bg-success' : 'bg-warning' }}">
                                            v{{ $shop->prestashop_version }}
                                        </span>
                                    @else
                                        <span class="text-muted">Nieznana</span>
                                    @endif
                                </td>
                                <td>
                                    @if($shop->last_sync_at)
                                        {{ $shop->last_sync_at->diffForHumans() }}
                                        <small class="d-block text-muted">{{ $shop->products_synced }} produktów</small>
                                    @else
                                        <span class="text-muted">Nigdy</span>
                                    @endif
                                </td>
                                <td>
                                    @if($shop->sync_success_rate > 0)
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                <div class="progress-bar bg-{{ $shop->sync_success_rate >= 90 ? 'success' : ($shop->sync_success_rate >= 70 ? 'warning' : 'danger') }}" 
                                                     style="width: {{ $shop->sync_success_rate }}%"></div>
                                            </div>
                                            <small>{{ $shop->sync_success_rate }}%</small>
                                        </div>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button wire:click="showDetails({{ $shop->id }})" 
                                                class="btn btn-sm btn-outline-info"
                                                title="Szczegóły">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                        
                                        <button wire:click="testConnection({{ $shop->id }})" 
                                                class="btn btn-sm btn-outline-primary"
                                                wire:loading.attr="disabled"
                                                wire:target="testConnection({{ $shop->id }})"
                                                title="Test połączenia">
                                            <i class="fas fa-{{ $testingConnection ? 'spinner fa-spin' : 'plug' }}"></i>
                                        </button>

                                        <button wire:click="syncShop({{ $shop->id }})" 
                                                class="btn btn-sm btn-outline-success"
                                                wire:loading.attr="disabled"
                                                wire:target="syncShop({{ $shop->id }})"
                                                title="Synchronizuj">
                                            <i class="fas fa-{{ $syncingShop ? 'spinner fa-spin' : 'sync' }}"></i>
                                        </button>

                                        <button wire:click="toggleShopStatus({{ $shop->id }})" 
                                                class="btn btn-sm btn-outline-{{ $shop->is_active ? 'warning' : 'success' }}"
                                                title="{{ $shop->is_active ? 'Dezaktywuj' : 'Aktywuj' }}">
                                            <i class="fas fa-{{ $shop->is_active ? 'pause' : 'play' }}"></i>
                                        </button>

                                        <button wire:click="deleteShop({{ $shop->id }})" 
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Czy na pewno chcesz usunąć ten sklep?')"
                                                title="Usuń">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="card-footer">
                    {{ $shops->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-store fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Brak sklepów PrestaShop</h5>
                    <p class="text-muted">Dodaj pierwszy sklep, aby rozpocząć synchronizację.</p>
                    <button wire:click="startWizard" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Dodaj Sklep
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Add Shop Wizard Modal --}}
    @if($showAddShop)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>
                        Kreator Dodawania Sklepu PrestaShop
                    </h5>
                    <button type="button" wire:click="closeWizard" class="btn-close"></button>
                </div>
                <div class="modal-body">
                    {{-- Wizard Progress --}}
                    <div class="mb-4">
                        <div class="row">
                            <div class="col-4">
                                <div class="text-center">
                                    <div class="wizard-step {{ $wizardStep >= 1 ? 'active' : '' }}">1</div>
                                    <small>Informacje podstawowe</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center">
                                    <div class="wizard-step {{ $wizardStep >= 2 ? 'active' : '' }}">2</div>
                                    <small>Konfiguracja API</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center">
                                    <div class="wizard-step {{ $wizardStep >= 3 ? 'active' : '' }}">3</div>
                                    <small>Test połączenia</small>
                                </div>
                            </div>
                        </div>
                        <div class="progress mt-2" style="height: 4px;">
                            <div class="progress-bar" style="width: {{ ($wizardStep / 3) * 100 }}%"></div>
                        </div>
                    </div>

                    {{-- Step 1: Basic Information --}}
                    @if($wizardStep === 1)
                    <div class="wizard-content">
                        <h6 class="mb-3">Krok 1: Informacje podstawowe</h6>
                        
                        <div class="mb-3">
                            <label class="form-label">Nazwa sklepu *</label>
                            <input type="text" wire:model="shopForm.name" class="form-control @error('shopForm.name') is-invalid @enderror">
                            @error('shopForm.name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">URL sklepu *</label>
                            <input type="url" wire:model="shopForm.url" class="form-control @error('shopForm.url') is-invalid @enderror" placeholder="https://example.com">
                            @error('shopForm.url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Opis</label>
                            <textarea wire:model="shopForm.description" class="form-control @error('shopForm.description') is-invalid @enderror" rows="3"></textarea>
                            @error('shopForm.description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    @endif

                    {{-- Step 2: API Configuration --}}
                    @if($wizardStep === 2)
                    <div class="wizard-content">
                        <h6 class="mb-3">Krok 2: Konfiguracja API</h6>
                        
                        <div class="mb-3">
                            <label class="form-label">Klucz API PrestaShop *</label>
                            <input type="password" wire:model="shopForm.api_key" class="form-control @error('shopForm.api_key') is-invalid @enderror">
                            @error('shopForm.api_key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Wygeneruj klucz API w panelu administracyjnym PrestaShop</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Wersja API</label>
                                    <select wire:model="shopForm.api_version" class="form-select @error('shopForm.api_version') is-invalid @enderror">
                                        <option value="1.6">PrestaShop 1.6</option>
                                        <option value="1.7">PrestaShop 1.7</option>
                                        <option value="8.0">PrestaShop 8.0</option>
                                        <option value="9.0">PrestaShop 9.0</option>
                                    </select>
                                    @error('shopForm.api_version')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Timeout (sekundy)</label>
                                    <input type="number" wire:model="shopForm.timeout_seconds" class="form-control @error('shopForm.timeout_seconds') is-invalid @enderror" min="5" max="300">
                                    @error('shopForm.timeout_seconds')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Limit API (na minutę)</label>
                                    <input type="number" wire:model="shopForm.rate_limit_per_minute" class="form-control @error('shopForm.rate_limit_per_minute') is-invalid @enderror" min="1" max="1000">
                                    @error('shopForm.rate_limit_per_minute')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" wire:model="shopForm.ssl_verify" class="form-check-input" id="ssl_verify">
                                        <label class="form-check-label" for="ssl_verify">
                                            Weryfikacja SSL
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Step 3: Connection Test --}}
                    @if($wizardStep === 3)
                    <div class="wizard-content">
                        <h6 class="mb-3">Krok 3: Test połączenia</h6>
                        
                        @if($testingConnection)
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Testowanie połączenia...</span>
                                </div>
                                <p class="mt-2">Testowanie połączenia z PrestaShop...</p>
                            </div>
                        @elseif($connectionTestResult)
                            @if($connectionTestResult['success'])
                                <div class="alert alert-success">
                                    <h6><i class="fas fa-check-circle me-2"></i>Połączenie pomyślne!</h6>
                                    <ul class="mb-0">
                                        @if(isset($connectionTestResult['prestashop_version']))
                                            <li>Wersja PrestaShop: {{ $connectionTestResult['prestashop_version'] }}</li>
                                        @endif
                                        @if(isset($connectionTestResult['response_time']))
                                            <li>Czas odpowiedzi: {{ $connectionTestResult['response_time'] }}ms</li>
                                        @endif
                                        @if(isset($connectionTestResult['supported_features']) && count($connectionTestResult['supported_features']) > 0)
                                            <li>Wspierane funkcje: {{ implode(', ', $connectionTestResult['supported_features']) }}</li>
                                        @endif
                                    </ul>
                                </div>
                            @else
                                <div class="alert alert-danger">
                                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Test połączenia nieudany</h6>
                                    <p class="mb-0">{{ $connectionTestResult['message'] }}</p>
                                </div>
                                
                                <button type="button" wire:click="testShopConnection" class="btn btn-primary">
                                    <i class="fas fa-redo me-2"></i>Ponów test
                                </button>
                            @endif
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-plug fa-3x text-muted mb-3"></i>
                                <p>Kliknij przycisk poniżej, aby przetestować połączenie z PrestaShop.</p>
                                <button type="button" wire:click="testShopConnection" class="btn btn-primary">
                                    <i class="fas fa-play me-2"></i>Test połączenia
                                </button>
                            </div>
                        @endif

                        {{-- Sync Settings --}}
                        <div class="mt-4">
                            <h6>Ustawienia synchronizacji</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Częstotliwość synchronizacji</label>
                                        <select wire:model="shopForm.sync_frequency" class="form-select">
                                            <option value="realtime">Czas rzeczywisty</option>
                                            <option value="hourly">Co godzinę</option>
                                            <option value="daily">Raz dziennie</option>
                                            <option value="manual">Tylko ręcznie</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Rozwiązywanie konfliktów</label>
                                        <select wire:model="shopForm.conflict_resolution" class="form-select">
                                            <option value="ppm_wins">PPM ma priorytet</option>
                                            <option value="prestashop_wins">PrestaShop ma priorytet</option>
                                            <option value="manual">Ręcznie</option>
                                            <option value="newest_wins">Najnowsza wersja</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" wire:model="shopForm.auto_sync_products" class="form-check-input" id="auto_sync_products">
                                        <label class="form-check-label" for="auto_sync_products">
                                            Auto sync produktów
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" wire:model="shopForm.auto_sync_categories" class="form-check-input" id="auto_sync_categories">
                                        <label class="form-check-label" for="auto_sync_categories">
                                            Auto sync kategorii
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" wire:model="shopForm.auto_sync_prices" class="form-check-input" id="auto_sync_prices">
                                        <label class="form-check-label" for="auto_sync_prices">
                                            Auto sync cen
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" wire:model="shopForm.auto_sync_stock" class="form-check-input" id="auto_sync_stock">
                                        <label class="form-check-label" for="auto_sync_stock">
                                            Auto sync stanów
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    @if($wizardStep > 1)
                        <button type="button" wire:click="previousWizardStep" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Poprzedni
                        </button>
                    @endif
                    
                    @if($wizardStep < 3)
                        <button type="button" wire:click="nextWizardStep" class="btn btn-primary">
                            Następny <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    @else
                        <button type="button" wire:click="completeWizard" class="btn btn-success">
                            <i class="fas fa-check me-2"></i>Dodaj Sklep
                        </button>
                    @endif
                    
                    <button type="button" wire:click="closeWizard" class="btn btn-outline-secondary">
                        Anuluj
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Shop Details Modal --}}
    @if($showShopDetails && $selectedShop)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>
                        Szczegóły sklepu: {{ $selectedShop->name }}
                    </h5>
                    <button type="button" wire:click="closeDetails" class="btn-close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            {{-- Shop Information --}}
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Informacje o sklepie</h6>
                                </div>
                                <div class="card-body">
                                    <dl class="row">
                                        <dt class="col-sm-3">Nazwa:</dt>
                                        <dd class="col-sm-9">{{ $selectedShop->name }}</dd>
                                        
                                        <dt class="col-sm-3">URL:</dt>
                                        <dd class="col-sm-9">
                                            <a href="{{ $selectedShop->url }}" target="_blank" class="text-decoration-none">
                                                {{ $selectedShop->url }}
                                                <i class="fas fa-external-link-alt ms-1"></i>
                                            </a>
                                        </dd>
                                        
                                        <dt class="col-sm-3">Opis:</dt>
                                        <dd class="col-sm-9">{{ $selectedShop->description ?: 'Brak opisu' }}</dd>
                                        
                                        <dt class="col-sm-3">Wersja PS:</dt>
                                        <dd class="col-sm-9">{{ $selectedShop->prestashop_version ?: 'Nieznana' }}</dd>
                                        
                                        <dt class="col-sm-3">Utworzony:</dt>
                                        <dd class="col-sm-9">{{ $selectedShop->created_at->format('d.m.Y H:i') }}</dd>
                                    </dl>
                                </div>
                            </div>

                            {{-- Recent Sync Jobs --}}
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Ostatnie synchronizacje</h6>
                                </div>
                                <div class="card-body">
                                    @if($selectedShop->syncJobs->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Typ</th>
                                                        <th>Status</th>
                                                        <th>Przetworzonych</th>
                                                        <th>Czas</th>
                                                        <th>Data</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($selectedShop->syncJobs as $job)
                                                    <tr>
                                                        <td>{{ $job->job_name }}</td>
                                                        <td><span class="badge {{ $job->status_badge }}">{{ $job->status_text }}</span></td>
                                                        <td>{{ $job->successful_items }}/{{ $job->total_items }}</td>
                                                        <td>{{ $job->duration_human }}</td>
                                                        <td>{{ $job->created_at->diffForHumans() }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-muted mb-0">Brak historii synchronizacji</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            {{-- Status Cards --}}
                            <div class="card mb-3 bg-{{ $selectedShop->connection_health }} text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-{{ $selectedShop->connection_status === 'connected' ? 'check-circle' : 'exclamation-triangle' }} fa-2x mb-2"></i>
                                    <h6>Status połączenia</h6>
                                    <h4>{{ ucfirst($selectedShop->connection_status) }}</h4>
                                    @if($selectedShop->last_connection_test)
                                        <small>Ostatni test: {{ $selectedShop->last_connection_test->diffForHumans() }}</small>
                                    @endif
                                </div>
                            </div>

                            <div class="card mb-3">
                                <div class="card-body text-center">
                                    <i class="fas fa-sync fa-2x text-primary mb-2"></i>
                                    <h6>Synchronizacje</h6>
                                    <h4>{{ $selectedShop->sync_success_rate }}%</h4>
                                    <small class="text-muted">{{ $selectedShop->sync_success_count }} udanych / {{ $selectedShop->sync_error_count }} błędnych</small>
                                </div>
                            </div>

                            <div class="card mb-3">
                                <div class="card-body text-center">
                                    <i class="fas fa-box fa-2x text-success mb-2"></i>
                                    <h6>Produkty</h6>
                                    <h4>{{ number_format($selectedShop->products_synced) }}</h4>
                                    <small class="text-muted">zsynchronizowanych</small>
                                </div>
                            </div>

                            @if($selectedShop->avg_response_time)
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-tachometer-alt fa-2x text-info mb-2"></i>
                                    <h6>Średni czas odpowiedzi</h6>
                                    <h4>{{ $selectedShop->avg_response_time }}ms</h4>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" wire:click="testConnection({{ $selectedShop->id }})" class="btn btn-outline-primary">
                        <i class="fas fa-plug me-2"></i>Test połączenia
                    </button>
                    <button type="button" wire:click="syncShop({{ $selectedShop->id }})" class="btn btn-outline-success">
                        <i class="fas fa-sync me-2"></i>Synchronizuj
                    </button>
                    <button type="button" wire:click="closeDetails" class="btn btn-secondary">
                        Zamknij
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
.wizard-step {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #e9ecef;
    border: 2px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin: 0 auto 10px;
    transition: all 0.3s ease;
}

.wizard-step.active {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white;
}

.cursor-pointer {
    cursor: pointer;
}

.shop-manager .badge {
    font-size: 0.75em;
}
</style>