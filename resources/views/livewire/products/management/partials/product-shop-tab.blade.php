{{--
    FAZA 9.4: Shop Tab Partial View

    Wyświetla zakładki sklepów dla produktu z pełną wizualizacją:
    - Status synchronizacji per sklep
    - Validation warnings
    - Akcje: sync, pull, view, unlink

    MANDATORY: NO inline styles - use CSS classes from product-form.css
--}}

<div class="enterprise-card shop-tab-container">
    @if($product->shopData->isEmpty())
        {{-- No shops linked state --}}
        <div class="shop-empty-state">
            <svg class="shop-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <p class="shop-empty-title">Brak połączonych sklepów</p>
            <p class="shop-empty-description">Ten produkt nie jest jeszcze połączony z żadnym sklepem PrestaShop. Aby zsynchronizować produkt, najpierw połącz go ze sklepem w panelu synchronizacji.</p>
        </div>
    @else
        {{-- Shop Tabs Navigation --}}
        <div class="shop-tabs-nav">
            @foreach($product->shopData as $shopData)
                <button
                    type="button"
                    wire:click="selectShopTab({{ $shopData->shop_id }})"
                    class="shop-tab-button {{ $activeShopTab === 'shop_' . $shopData->shop_id ? 'active' : '' }}"
                >
                    <span class="shop-tab-name">{{ $shopData->shop->name }}</span>

                    {{-- Warning badge if validation warnings present --}}
                    @if($shopData->has_validation_warnings && !empty($shopData->validation_warnings))
                        <span class="shop-tab-badge badge-warning">
                            {{ count($shopData->validation_warnings) }}
                        </span>
                    @endif

                    {{-- Pending sync indicator --}}
                    @if($shopData->sync_status === 'pending')
                        <span class="shop-tab-badge badge-pending">
                            <svg class="badge-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Shop Data Display --}}
        @if($selectedShopId)
            @php
                $shopData = $product->shopData->where('shop_id', $selectedShopId)->first();
            @endphp

            @if($shopData)
                <div class="shop-data-container">
                    {{-- Shop Info Section --}}
                    <div class="shop-info-section">
                        <div class="shop-info-header">
                            <div>
                                <h3 class="shop-name">{{ $shopData->shop->name }}</h3>
                                <p class="shop-url">{{ $shopData->shop->url }}</p>
                            </div>

                            {{-- Sync Status Badge --}}
                            <span class="status-badge status-{{ $shopData->sync_status }}">
                                @switch($shopData->sync_status)
                                    @case('synced')
                                        <svg class="status-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Zsynchronizowane
                                        @break
                                    @case('pending')
                                        <svg class="status-icon status-icon-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        Oczekuje
                                        @break
                                    @case('syncing')
                                        <svg class="status-icon status-icon-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        Synchronizacja...
                                        @break
                                    @case('error')
                                        <svg class="status-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Błąd
                                        @break
                                    @case('conflict')
                                        <svg class="status-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                        Konflikt
                                        @break
                                    @default
                                        {{ ucfirst($shopData->sync_status) }}
                                @endswitch
                            </span>
                        </div>

                        <div class="shop-info-details">
                            <div class="info-item">
                                <span class="info-label">External ID:</span>
                                <span class="info-value">{{ $shopData->prestashop_product_id ?? 'Nie zsynchronizowane' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Timestamps Section --}}
                    <div class="timestamps-section">
                        <div class="timestamp-item">
                            <svg class="timestamp-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                            </svg>
                            <div>
                                <span class="timestamp-label">Ostatnie pobranie:</span>
                                <span class="timestamp-value">{{ $shopData->last_pulled_at?->diffForHumans() ?? 'Nigdy' }}</span>
                            </div>
                        </div>

                        <div class="timestamp-item">
                            <svg class="timestamp-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <div>
                                <span class="timestamp-label">Ostatnia synchronizacja:</span>
                                <span class="timestamp-value">{{ $shopData->last_sync_at?->diffForHumans() ?? 'Nigdy' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Changed Fields Section (if any) --}}
                    @if(!empty($shopData->pending_fields))
                        <div class="changed-fields-section">
                            <h4 class="section-title">
                                <svg class="section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                                Oczekujące zmiany:
                            </h4>
                            <ul class="changed-fields-list">
                                @foreach($shopData->pending_fields as $field)
                                    <li class="changed-field-item">
                                        <svg class="field-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                        {{ $field }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Error Message Section --}}
                    @if($shopData->sync_status === 'error' && $shopData->error_message)
                        <div class="error-message-section">
                            <h4 class="section-title section-title-error">
                                <svg class="section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Błąd synchronizacji:
                            </h4>
                            <p class="error-message-text">{{ $shopData->error_message }}</p>
                        </div>
                    @endif

                    {{-- Validation Warnings Section (integration with 9.5) --}}
                    @if($shopData->has_validation_warnings && !empty($shopData->validation_warnings))
                        <div class="validation-warnings-section">
                            <h4 class="section-title section-title-warning">
                                <svg class="section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                Ostrzeżenia walidacji:
                            </h4>
                            @foreach($shopData->validation_warnings as $warning)
                                <div class="warning-item severity-{{ $warning['severity'] ?? 'info' }}">
                                    <div class="warning-icon-wrapper">
                                        <svg class="warning-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                    </div>
                                    <div class="warning-content">
                                        <p class="warning-message">{{ $warning['message'] ?? 'Nieznane ostrzeżenie' }}</p>
                                        @if(isset($warning['ppm_value']) || isset($warning['prestashop_value']))
                                            <div class="warning-comparison">
                                                <div class="comparison-item">
                                                    <span class="comparison-label">PPM:</span>
                                                    <span class="comparison-value">{{ $warning['ppm_value'] ?? 'N/A' }}</span>
                                                </div>
                                                <svg class="comparison-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                                </svg>
                                                <div class="comparison-item">
                                                    <span class="comparison-label">PrestaShop:</span>
                                                    <span class="comparison-value">{{ $warning['prestashop_value'] ?? 'N/A' }}</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Actions Section --}}
                    <div class="shop-actions">
                        {{-- Sync This Shop --}}
                        <button
                            type="button"
                            wire:click="syncShop({{ $shopData->shop_id }})"
                            class="btn-enterprise-primary"
                            wire:loading.attr="disabled"
                            wire:target="syncShop({{ $shopData->shop_id }})"
                        >
                            <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span wire:loading.remove wire:target="syncShop({{ $shopData->shop_id }})">Synchronizuj sklep</span>
                            <span wire:loading wire:target="syncShop({{ $shopData->shop_id }})">Synchronizowanie...</span>
                        </button>

                        {{-- Pull Latest Data --}}
                        <button
                            type="button"
                            wire:click="pullShopData({{ $shopData->shop_id }})"
                            class="btn-enterprise-secondary"
                            wire:loading.attr="disabled"
                            wire:target="pullShopData({{ $shopData->shop_id }})"
                        >
                            <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                            </svg>
                            <span wire:loading.remove wire:target="pullShopData({{ $shopData->shop_id }})">Pobierz dane</span>
                            <span wire:loading wire:target="pullShopData({{ $shopData->shop_id }})">Pobieranie...</span>
                        </button>

                        {{-- View on PrestaShop --}}
                        @if($shopData->prestashop_product_id)
                            <a
                                href="{{ rtrim($shopData->shop->url, '/') }}/admin-dev/index.php?controller=AdminProducts&id_product={{ $shopData->prestashop_product_id }}&updateproduct"
                                target="_blank"
                                class="btn-enterprise-outline"
                                rel="noopener noreferrer"
                            >
                                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                                Zobacz w PrestaShop
                            </a>
                        @endif

                        {{-- Unlink Shop --}}
                        <button
                            type="button"
                            wire:click="unlinkShop({{ $shopData->shop_id }})"
                            class="btn-enterprise-danger"
                            onclick="return confirm('Czy na pewno chcesz odłączyć ten sklep? Ta akcja nie usunie produktu z PrestaShop.')"
                        >
                            <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                            </svg>
                            Odłącz sklep
                        </button>
                    </div>
                </div>
            @endif
        @else
            {{-- No shop selected state --}}
            <div class="shop-select-prompt">
                <svg class="select-prompt-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                </svg>
                <p class="select-prompt-text">Wybierz sklep z zakładek powyżej, aby zobaczyć szczegóły synchronizacji</p>
            </div>
        @endif
    @endif
</div>
