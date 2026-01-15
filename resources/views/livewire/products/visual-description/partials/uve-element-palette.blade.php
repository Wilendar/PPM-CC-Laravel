{{-- UVE Element Palette - Elementy do dodania wewnatrz bloku (gdy blok jest odmrozony) --}}
<div class="uve-element-palette">
    <div class="uve-palette-category">
        <h4 class="uve-palette-category-title">Tekst</h4>
        <div class="uve-palette-grid">
            <button
                type="button"
                wire:click="addElementToBlock('heading')"
                class="uve-palette-item"
                draggable="true"
            >
                <x-heroicon-o-bars-3-bottom-left class="w-5 h-5" />
                <span class="uve-palette-label">Naglowek</span>
            </button>
            <button
                type="button"
                wire:click="addElementToBlock('text')"
                class="uve-palette-item"
                draggable="true"
            >
                <x-heroicon-o-document-text class="w-5 h-5" />
                <span class="uve-palette-label">Tekst</span>
            </button>
        </div>
    </div>

    <div class="uve-palette-category">
        <h4 class="uve-palette-category-title">Media</h4>
        <div class="uve-palette-grid">
            <button
                type="button"
                wire:click="addElementToBlock('image')"
                class="uve-palette-item"
                draggable="true"
            >
                <x-heroicon-o-photo class="w-5 h-5" />
                <span class="uve-palette-label">Obraz</span>
            </button>
            <button
                type="button"
                wire:click="addElementToBlock('icon')"
                class="uve-palette-item"
                draggable="true"
            >
                <x-heroicon-o-star class="w-5 h-5" />
                <span class="uve-palette-label">Ikona</span>
            </button>
        </div>
    </div>

    <div class="uve-palette-category">
        <h4 class="uve-palette-category-title">Interakcja</h4>
        <div class="uve-palette-grid">
            <button
                type="button"
                wire:click="addElementToBlock('button')"
                class="uve-palette-item"
                draggable="true"
            >
                <x-heroicon-o-cursor-arrow-rays class="w-5 h-5" />
                <span class="uve-palette-label">Przycisk</span>
            </button>
            <button
                type="button"
                wire:click="addElementToBlock('link')"
                class="uve-palette-item"
                draggable="true"
            >
                <x-heroicon-o-link class="w-5 h-5" />
                <span class="uve-palette-label">Link</span>
            </button>
        </div>
    </div>

    <div class="uve-palette-category">
        <h4 class="uve-palette-category-title">Layout</h4>
        <div class="uve-palette-grid">
            <button
                type="button"
                wire:click="addElementToBlock('container')"
                class="uve-palette-item"
                draggable="true"
            >
                <x-heroicon-o-square-2-stack class="w-5 h-5" />
                <span class="uve-palette-label">Kontener</span>
            </button>
            <button
                type="button"
                wire:click="addElementToBlock('row')"
                class="uve-palette-item"
                draggable="true"
            >
                <x-heroicon-o-view-columns class="w-5 h-5" />
                <span class="uve-palette-label">Wiersz</span>
            </button>
            <button
                type="button"
                wire:click="addElementToBlock('divider')"
                class="uve-palette-item"
                draggable="true"
            >
                <x-heroicon-o-minus class="w-5 h-5" />
                <span class="uve-palette-label">Separator</span>
            </button>
            <button
                type="button"
                wire:click="addElementToBlock('spacer')"
                class="uve-palette-item"
                draggable="true"
            >
                <x-heroicon-o-arrows-up-down class="w-5 h-5" />
                <span class="uve-palette-label">Odstep</span>
            </button>
        </div>
    </div>
</div>

<style>
/* UVE Element Palette - Dark Theme (matches Block Palette) */
.uve-element-palette {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.uve-element-palette .uve-palette-category-title {
    font-size: 0.7rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin: 0 0 0.5rem 0;
    padding-left: 0.25rem;
}

.uve-element-palette .uve-palette-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
}

.uve-element-palette .uve-palette-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 0.5rem;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.5rem;
    cursor: grab;
    transition: all 0.2s ease;
    color: #94a3b8;
}

.uve-element-palette .uve-palette-item:hover {
    background: #475569;
    border-color: #e0ac7e;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    color: #e0ac7e;
}

.uve-element-palette .uve-palette-item:active {
    cursor: grabbing;
    transform: translateY(0);
}

.uve-element-palette .uve-palette-label {
    font-size: 0.7rem;
    font-weight: 500;
    color: #cbd5e1;
    text-align: center;
}

.uve-element-palette .uve-palette-item:hover .uve-palette-label {
    color: #f1f5f9;
}
</style>
