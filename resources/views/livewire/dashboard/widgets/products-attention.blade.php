<div class="dashboard-widget" role="region" aria-label="Produkty wymagajace uwagi">
    {{-- Header --}}
    <div class="dashboard-widget__header">
        <span class="dashboard-widget__title">Wymagaja uwagi</span>
        <div class="flex items-center gap-2">
            <button wire:click="refreshMetrics"
                    wire:loading.class="animate-spin"
                    wire:target="refreshMetrics"
                    class="text-gray-500 hover:text-amber-400 transition-colors"
                    title="Odswiez dane">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
            <div class="dashboard-widget__icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
        </div>
    </div>

    {{-- Attention metrics --}}
    <div class="grid grid-cols-2 gap-3">
        {{-- No images --}}
        <a href="/admin/products?filter=no_images" class="kpi-metric group cursor-pointer hover:border-amber-500/30 transition-colors">
            <div class="flex items-center justify-center gap-1.5 mb-1">
                <div class="health-dot {{ $this->getSeverityDot($metrics['no_images'] ?? 0) }}"
                     title="{{ ($metrics['no_images'] ?? 0) == 0 ? 'OK' : (($metrics['no_images'] ?? 0) > 10 ? 'Krytyczny' : 'Uwaga') }}"
                     aria-label="{{ ($metrics['no_images'] ?? 0) == 0 ? 'OK' : (($metrics['no_images'] ?? 0) > 10 ? 'Krytyczny' : 'Uwaga') }}"></div>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <p class="kpi-metric__value {{ $this->getSeverityClass($metrics['no_images'] ?? 0) }}">
                {{ $metrics['no_images'] ?? 0 }}
            </p>
            <p class="kpi-metric__label">Bez zdjec</p>
        </a>

        {{-- No prices --}}
        <a href="/admin/products?filter=no_prices" class="kpi-metric group cursor-pointer hover:border-amber-500/30 transition-colors">
            <div class="flex items-center justify-center gap-1.5 mb-1">
                <div class="health-dot {{ $this->getSeverityDot($metrics['no_prices'] ?? 0) }}"
                     title="{{ ($metrics['no_prices'] ?? 0) == 0 ? 'OK' : (($metrics['no_prices'] ?? 0) > 10 ? 'Krytyczny' : 'Uwaga') }}"
                     aria-label="{{ ($metrics['no_prices'] ?? 0) == 0 ? 'OK' : (($metrics['no_prices'] ?? 0) > 10 ? 'Krytyczny' : 'Uwaga') }}"></div>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="kpi-metric__value {{ $this->getSeverityClass($metrics['no_prices'] ?? 0) }}">
                {{ $metrics['no_prices'] ?? 0 }}
            </p>
            <p class="kpi-metric__label">Bez cen</p>
        </a>

        {{-- Empty categories --}}
        <a href="/admin/products?filter=empty_categories" class="kpi-metric group cursor-pointer hover:border-amber-500/30 transition-colors">
            <div class="flex items-center justify-center gap-1.5 mb-1">
                <div class="health-dot {{ $this->getSeverityDot($metrics['empty_categories'] ?? 0) }}"
                     title="{{ ($metrics['empty_categories'] ?? 0) == 0 ? 'OK' : (($metrics['empty_categories'] ?? 0) > 10 ? 'Krytyczny' : 'Uwaga') }}"
                     aria-label="{{ ($metrics['empty_categories'] ?? 0) == 0 ? 'OK' : (($metrics['empty_categories'] ?? 0) > 10 ? 'Krytyczny' : 'Uwaga') }}"></div>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
            </div>
            <p class="kpi-metric__value {{ $this->getSeverityClass($metrics['empty_categories'] ?? 0) }}">
                {{ $metrics['empty_categories'] ?? 0 }}
            </p>
            <p class="kpi-metric__label">Puste kategorie</p>
        </a>

        {{-- Sync failed --}}
        <a href="/admin/shops?filter=sync_failed" class="kpi-metric group cursor-pointer hover:border-amber-500/30 transition-colors">
            <div class="flex items-center justify-center gap-1.5 mb-1">
                <div class="health-dot {{ $this->getSeverityDot($metrics['sync_failed'] ?? 0) }}"
                     title="{{ ($metrics['sync_failed'] ?? 0) == 0 ? 'OK' : (($metrics['sync_failed'] ?? 0) > 10 ? 'Krytyczny' : 'Uwaga') }}"
                     aria-label="{{ ($metrics['sync_failed'] ?? 0) == 0 ? 'OK' : (($metrics['sync_failed'] ?? 0) > 10 ? 'Krytyczny' : 'Uwaga') }}"></div>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </div>
            <p class="kpi-metric__value {{ $this->getSeverityClass($metrics['sync_failed'] ?? 0) }}">
                {{ $metrics['sync_failed'] ?? 0 }}
            </p>
            <p class="kpi-metric__label">Sync bledy (7d)</p>
        </a>
    </div>
</div>
