{{-- LEFT COLUMN: Warehouses --}}
<div class="feature-browser__column">
    <div class="feature-browser__column-header">
        <span class="flex items-center gap-2">
            <svg class="w-3.5 h-3.5 header-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            MAGAZYNY
        </span>
    </div>
    <div class="feature-browser__column-content">
        @foreach($warehouses as $warehouse)
            <button wire:click="selectWarehouse({{ $warehouse->id }})"
                    wire:key="wh-{{ $warehouse->id }}"
                    class="feature-browser__group-item {{ $selectedWarehouseId === $warehouse->id ? 'active' : '' }}">
                <span class="flex-1 truncate font-medium">{{ $warehouse->name }}</span>
                <span class="feature-browser__badge {{ ($warehouse->locations_count ?? 0) > 0 ? 'feature-browser__badge--active' : 'feature-browser__badge--zero' }}">
                    {{ $warehouse->locations_count ?? 0 }}
                </span>
            </button>
        @endforeach
    </div>
    <div class="feature-browser__column-footer">
        {{ count($warehouses) }} magazynow
    </div>
</div>
