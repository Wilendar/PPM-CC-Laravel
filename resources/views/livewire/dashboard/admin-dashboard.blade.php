<div>
    {{-- Dashboard Header --}}
    <div class="mb-6">
        <h1 class="text-h1 font-bold text-dark-primary">Dashboard</h1>
        <p class="text-dark-muted mt-1">Witaj w panelu administracyjnym PPM - {{ $userRole }}</p>
    </div>

    {{-- Widget Grid --}}
    <div class="dashboard-grid">
        @foreach($visibleWidgets as $widget)
            @if($widget['component'] === 'divider')
                <div class="dashboard-span-4">
                    <div class="dashboard-section-divider">
                        <div class="dashboard-section-divider__line"></div>
                        <span class="dashboard-section-divider__label">{{ $widget['label'] }}</span>
                        <div class="dashboard-section-divider__line dashboard-section-divider__line--reverse"></div>
                    </div>
                </div>
            @elseif($widget['component'] === 'logo')
                <div class="dashboard-span-{{ $widget['span'] }}">
                    <div class="dashboard-widget dashboard-logo-widget" role="region" aria-label="Logo MPP TRADE"
                         x-data x-init="$nextTick(() => {
                            const sibling = $el.closest('.dashboard-grid').querySelector('.dashboard-widget:not(.dashboard-logo-widget)');
                            if (sibling) $el.style.height = sibling.offsetHeight + 'px';
                         })">
                        <img src="https://mm.mpptrade.pl/images/logo/mpptrade/dark-bg/color/mpp-trade-logo-color.png"
                             alt="MPP TRADE Logo"
                             class="dashboard-logo-img"
                             onerror="this.closest('.dashboard-logo-widget').innerHTML='<span class=\'text-2xl font-bold\' style=\'color: var(--mpp-primary)\'>MPP TRADE</span>'"
                        >
                    </div>
                </div>
            @else
                <div class="dashboard-span-{{ $widget['span'] }}">
                    @livewire($widget['component'], key($widget['component']))
                </div>
            @endif
        @endforeach
    </div>
</div>
