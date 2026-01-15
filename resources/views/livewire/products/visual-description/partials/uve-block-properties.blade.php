{{-- UVE Block Properties - Panel wlasciwosci bloku --}}
@props(['block', 'index'])

<div class="uve-block-properties">
    <div class="uve-property-section">
        <h4 class="uve-property-section-title">Informacje</h4>

        <div class="uve-property-row">
            <label class="uve-property-label">ID</label>
            <span class="uve-property-value uve-property-value-muted">{{ $block['id'] ?? '-' }}</span>
        </div>

        <div class="uve-property-row">
            <label class="uve-property-label">Typ</label>
            <span class="uve-property-value">{{ $block['type'] ?? 'unknown' }}</span>
        </div>

        <div class="uve-property-row">
            <label class="uve-property-label">Status</label>
            <span class="uve-property-value">
                @if($block['locked'] ?? true)
                    <span class="uve-badge uve-badge-gray">Zamrozony</span>
                @else
                    <span class="uve-badge uve-badge-blue">Edycja</span>
                @endif
            </span>
        </div>
    </div>

    <div class="uve-property-section">
        <h4 class="uve-property-section-title">Akcje</h4>

        <div class="uve-property-actions">
            @if($block['locked'] ?? true)
                <button
                    type="button"
                    wire:click="unfreezeBlock({{ $index }})"
                    class="uve-btn uve-btn-sm uve-btn-primary w-full"
                >
                    <x-heroicon-o-pencil class="w-4 h-4" />
                    Edytuj blok
                </button>
            @else
                <button
                    type="button"
                    wire:click="freezeBlock({{ $index }}, true)"
                    class="uve-btn uve-btn-sm w-full"
                >
                    <x-heroicon-o-check class="w-4 h-4" />
                    Zatwierdz zmiany
                </button>
            @endif

            <button
                type="button"
                wire:click="duplicateBlock({{ $index }})"
                class="uve-btn uve-btn-sm w-full"
            >
                <x-heroicon-o-document-duplicate class="w-4 h-4" />
                Duplikuj
            </button>

            <button
                type="button"
                wire:click="removeBlock({{ $index }})"
                wire:confirm="Czy na pewno chcesz usunac ten blok?"
                class="uve-btn uve-btn-sm uve-btn-danger w-full"
            >
                <x-heroicon-o-trash class="w-4 h-4" />
                Usun blok
            </button>
        </div>
    </div>

    @if(!empty($block['meta']))
        <div class="uve-property-section">
            <h4 class="uve-property-section-title">Meta</h4>

            @if(!empty($block['meta']['created_from']))
                <div class="uve-property-row">
                    <label class="uve-property-label">Zrodlo</label>
                    <span class="uve-property-value uve-property-value-muted">{{ $block['meta']['created_from'] }}</span>
                </div>
            @endif

            @if(!empty($block['meta']['created_at']))
                <div class="uve-property-row">
                    <label class="uve-property-label">Utworzono</label>
                    <span class="uve-property-value uve-property-value-muted">{{ $block['meta']['created_at'] }}</span>
                </div>
            @endif
        </div>
    @endif
</div>

<style>
/* UVE Block Properties - Dark Theme (PPM Standard) */
.uve-block-properties {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.uve-property-section {
    padding-bottom: 1rem;
    border-bottom: 1px solid #334155;
}

.uve-property-section:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.uve-property-section-title {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin: 0 0 0.75rem 0;
}

.uve-property-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.375rem 0;
}

.uve-property-label {
    font-size: 0.875rem;
    color: #94a3b8;
}

.uve-property-value {
    font-size: 0.875rem;
    color: #e2e8f0;
}

.uve-property-value-muted {
    color: #64748b;
    font-family: monospace;
    font-size: 0.8rem;
}

.uve-property-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.uve-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.125rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 9999px;
}

.uve-badge-gray {
    background: #334155;
    color: #94a3b8;
}

.uve-badge-blue {
    background: rgba(224, 172, 126, 0.2);
    color: #e0ac7e;
}

.uve-btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
}

.uve-btn-danger {
    color: #f87171;
    border-color: rgba(248, 113, 113, 0.3);
}

.uve-btn-danger:hover {
    background: rgba(248, 113, 113, 0.15);
    border-color: #f87171;
}
</style>
