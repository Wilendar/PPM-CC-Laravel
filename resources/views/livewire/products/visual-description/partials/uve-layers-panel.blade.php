{{-- UVE Layers Panel - Hierarchia blokow i elementow (FAZA 4.5.3.2) --}}
<div class="uve-layers-panel">
    @if(count($blocks) > 0)
        <div class="uve-layers-list">
            @foreach($blocks as $index => $block)
                <div
                    class="uve-layer-item {{ $selectedBlockIndex === $index ? 'uve-layer-selected' : '' }}"
                    wire:click="selectBlock({{ $index }})"
                    wire:key="layer-{{ $block['id'] }}"
                >
                    <div class="uve-layer-content">
                        <span class="uve-layer-icon">
                            @if($editingBlockIndex === $index)
                                <x-heroicon-s-pencil class="w-4 h-4 uve-icon-editing" />
                            @elseif($block['locked'] ?? true)
                                <x-heroicon-o-lock-closed class="w-4 h-4 uve-icon-locked" />
                            @else
                                <x-heroicon-o-lock-open class="w-4 h-4 uve-icon-unlocked" />
                            @endif
                        </span>
                        <span class="uve-layer-name">{{ $block['type'] }}</span>
                        <span class="uve-layer-id">{{ substr($block['id'], 0, 8) }}</span>
                    </div>

                    <div class="uve-layer-actions">
                        @if($index > 0)
                            <button
                                type="button"
                                wire:click.stop="moveBlockUp({{ $index }})"
                                class="uve-layer-action"
                                title="W gore"
                            >
                                <x-heroicon-o-chevron-up class="w-3 h-3" />
                            </button>
                        @endif
                        @if($index < count($blocks) - 1)
                            <button
                                type="button"
                                wire:click.stop="moveBlockDown({{ $index }})"
                                class="uve-layer-action"
                                title="W dol"
                            >
                                <x-heroicon-o-chevron-down class="w-3 h-3" />
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Show child layers when block is selected or editing --}}
                @if($selectedBlockIndex === $index || $editingBlockIndex === $index)
                    @php
                        $blockLayers = $this->getBlockLayers($index);
                        $isRawHtml = ($block['type'] ?? '') === 'raw-html';
                    @endphp

                    @if(count($blockLayers) > 0)
                        <div class="uve-layer-children">
                            @foreach($blockLayers as $layerIndex => $layer)
                                @if($isRawHtml)
                                    {{-- Raw-HTML parsed layers - ETAP_07h FIX #4: Added wire:click for layer selection --}}
                                    <div
                                        class="uve-layer-child {{ $selectedElementId === $layer['id'] ? 'uve-layer-child-selected' : '' }}"
                                        wire:click="selectElementFromLayers('{{ $layer['id'] }}')"
                                        wire:key="layer-{{ $index }}-{{ $layer['id'] }}"
                                        title="{{ $layer['classes'] }}"
                                    >
                                        <span class="uve-layer-tag">{{ $layer['tag'] }}</span>
                                        <span class="uve-layer-name">{{ $layer['name'] }}</span>
                                        @if($layer['zIndex'] !== null)
                                            <span class="uve-layer-zindex">z:{{ $layer['zIndex'] }}</span>
                                        @endif
                                        @if($layer['preview'])
                                            <span class="uve-layer-preview">{{ $layer['preview'] }}</span>
                                        @endif
                                    </div>
                                @else
                                    {{-- Structured block elements --}}
                                    @include('livewire.products.visual-description.partials.uve-layer-element', [
                                        'element' => $layer,
                                        'depth' => 1,
                                    ])
                                @endif
                            @endforeach
                        </div>
                    @endif
                @endif
            @endforeach
        </div>
    @else
        <div class="uve-layers-empty">
            <x-heroicon-o-square-3-stack-3d class="w-8 h-8 uve-icon-muted" />
            <p class="uve-empty-text">Brak blokow</p>
        </div>
    @endif
</div>

<style>
/* UVE Layers Panel - Dark Theme (PPM Standard) */
.uve-layers-panel {
    height: 100%;
}

.uve-layers-list {
    display: flex;
    flex-direction: column;
}

.uve-layer-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid #334155;
    cursor: pointer;
    transition: background 0.15s;
}

.uve-layer-item:hover {
    background: #334155;
}

.uve-layer-selected {
    background: rgba(224, 172, 126, 0.15);
    border-left: 3px solid #e0ac7e;
}

.uve-layer-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    min-width: 0;
}

.uve-layer-icon {
    flex-shrink: 0;
}

.uve-layer-name {
    font-size: 0.875rem;
    color: #e2e8f0;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.uve-layer-id {
    font-size: 0.75rem;
    font-family: monospace;
    color: #64748b;
}

.uve-layer-actions {
    display: flex;
    gap: 0.125rem;
}

.uve-layer-action {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    color: #64748b;
    background: transparent;
    border: none;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-layer-action:hover {
    background: #475569;
    color: #e2e8f0;
}

.uve-layer-children {
    border-left: 2px solid #e0ac7e;
    margin-left: 1rem;
    background: rgba(30, 41, 59, 0.5);
}

/* Raw-HTML Layer Child Styles (FAZA 4.5.3.2) - Dark Theme */
.uve-layer-child {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
    color: #94a3b8;
    border-bottom: 1px solid #1e293b;
}

.uve-layer-child:hover {
    background: #334155;
    cursor: pointer;
}

/* ETAP_07h FIX #4: Selected layer child style */
.uve-layer-child-selected {
    background: rgba(224, 172, 126, 0.15);
    border-left: 2px solid #e0ac7e;
    color: #e2e8f0;
}

.uve-layer-tag {
    font-family: monospace;
    font-size: 0.625rem;
    color: #64748b;
    background: #1e293b;
    padding: 0.125rem 0.25rem;
    border-radius: 0.125rem;
    text-transform: uppercase;
}

.uve-layer-zindex {
    font-family: monospace;
    font-size: 0.625rem;
    color: #e0ac7e;
    background: rgba(224, 172, 126, 0.2);
    padding: 0.125rem 0.25rem;
    border-radius: 0.125rem;
}

.uve-layer-preview {
    font-size: 0.625rem;
    color: #64748b;
    font-style: italic;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 100px;
}

.uve-layers-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    text-align: center;
    color: #64748b;
}

/* Icon Colors (Dark Theme) */
.uve-icon-editing {
    color: #e0ac7e;
}

.uve-icon-locked {
    color: #64748b;
}

.uve-icon-unlocked {
    color: #10b981;
}

.uve-icon-muted {
    color: #475569;
}

.uve-empty-text {
    font-size: 0.875rem;
    color: #64748b;
    margin-top: 0.5rem;
}
</style>
