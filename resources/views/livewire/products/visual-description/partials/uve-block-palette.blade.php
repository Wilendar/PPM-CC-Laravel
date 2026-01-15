{{-- UVE Block Palette - Lista dostepnych blokow do dodania --}}
<div class="uve-block-palette">
    @foreach($this->blockPalette as $category)
        <div class="uve-palette-category">
            <h4 class="uve-palette-category-title">{{ $category['category'] }}</h4>
            <div class="uve-palette-grid">
                @foreach($category['blocks'] as $blockDef)
                    <button
                        type="button"
                        wire:click="addBlock('{{ $blockDef['type'] }}')"
                        class="uve-palette-item"
                        title="{{ $blockDef['label'] }}"
                        draggable="true"
                        x-on:dragstart="$event.dataTransfer.setData('text/plain', JSON.stringify({ type: '{{ $blockDef['type'] }}', isNew: true }))"
                    >
                        <div class="uve-palette-icon">
                            @switch($blockDef['icon'])
                                @case('text')
                                    <x-heroicon-o-bars-3-bottom-left class="w-5 h-5" />
                                    @break
                                @case('align-left')
                                    <x-heroicon-o-document-text class="w-5 h-5" />
                                    @break
                                @case('image')
                                    <x-heroicon-o-photo class="w-5 h-5" />
                                    @break
                                @case('cursor-click')
                                    <x-heroicon-o-cursor-arrow-rays class="w-5 h-5" />
                                    @break
                                @case('square')
                                    <x-heroicon-o-square-2-stack class="w-5 h-5" />
                                    @break
                                @case('view-columns')
                                    <x-heroicon-o-view-columns class="w-5 h-5" />
                                    @break
                                @case('sparkles')
                                    <x-heroicon-o-sparkles class="w-5 h-5" />
                                    @break
                                @case('check-badge')
                                    <x-heroicon-o-check-badge class="w-5 h-5" />
                                    @break
                                @case('table-cells')
                                    <x-heroicon-o-table-cells class="w-5 h-5" />
                                    @break
                                @case('list-bullet')
                                    <x-heroicon-o-list-bullet class="w-5 h-5" />
                                    @break
                                @case('photo')
                                    <x-heroicon-o-photo class="w-5 h-5" />
                                    @break
                                @case('code-bracket')
                                    <x-heroicon-o-code-bracket class="w-5 h-5" />
                                    @break
                                @case('code-bracket-square')
                                    <x-heroicon-o-code-bracket-square class="w-5 h-5" />
                                    @break
                                @case('heroicons-arrows-pointing-out')
                                @case('arrows-pointing-out')
                                    <x-heroicon-o-arrows-pointing-out class="w-5 h-5" />
                                    @break
                                @default
                                    <x-heroicon-o-cube class="w-5 h-5" />
                            @endswitch
                        </div>
                        <span class="uve-palette-label">{{ $blockDef['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

<style>
/* UVE Block Palette - Dark Theme */
.uve-block-palette {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.uve-palette-category-title {
    font-size: 0.7rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin: 0 0 0.5rem 0;
    padding-left: 0.25rem;
}

.uve-palette-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
}

.uve-palette-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 0.5rem;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.5rem;
    cursor: grab;
    transition: all 0.2s ease;
}

.uve-palette-item:hover {
    background: #475569;
    border-color: #e0ac7e;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.uve-palette-item:active {
    cursor: grabbing;
    transform: translateY(0);
}

.uve-palette-icon {
    color: #94a3b8;
    transition: color 0.2s;
}

.uve-palette-item:hover .uve-palette-icon {
    color: #e0ac7e;
}

.uve-palette-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #cbd5e1;
    text-align: center;
}

.uve-palette-item:hover .uve-palette-label {
    color: #f1f5f9;
}
</style>
