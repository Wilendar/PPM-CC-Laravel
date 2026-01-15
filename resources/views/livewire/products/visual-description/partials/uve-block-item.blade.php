{{-- UVE Block Item - Pojedynczy blok na canvas --}}
@props(['block', 'index', 'isSelected', 'isEditing'])

<div
    class="uve-block {{ $isSelected ? 'uve-block-selected' : '' }} {{ $isEditing ? 'uve-block-editing' : '' }}"
    wire:key="block-{{ $block['id'] }}"
    wire:click="selectBlock({{ $index }})"
    x-data="{ showActions: false }"
    x-on:mouseenter="showActions = true"
    x-on:mouseleave="showActions = false"
>
    {{-- Block Toolbar --}}
    <div
        class="uve-block-toolbar"
        x-show="showActions || @js($isSelected) || @js($isEditing)"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
    >
        <div class="uve-block-toolbar-left">
            <span class="uve-block-type">{{ $block['type'] }}</span>
        </div>

        <div class="uve-block-toolbar-actions">
            @if($isEditing)
                {{-- Editing mode: Confirm / Cancel --}}
                <button
                    type="button"
                    wire:click.stop="freezeBlock({{ $index }}, true)"
                    class="uve-block-action uve-block-action-confirm"
                    title="Zatwierdz zmiany"
                >
                    <x-heroicon-s-check class="w-4 h-4" />
                </button>
                <button
                    type="button"
                    wire:click.stop="cancelBlockEdit({{ $index }})"
                    class="uve-block-action uve-block-action-cancel"
                    title="Anuluj zmiany"
                >
                    <x-heroicon-s-x-mark class="w-4 h-4" />
                </button>
            @else
                {{-- Normal mode: Edit / Move / Actions --}}
                <button
                    type="button"
                    wire:click.stop="unfreezeBlock({{ $index }})"
                    class="uve-block-action uve-block-action-edit"
                    title="Edytuj blok"
                >
                    <x-heroicon-o-pencil class="w-4 h-4" />
                </button>

                @if($index > 0)
                    <button
                        type="button"
                        wire:click.stop="moveBlockUp({{ $index }})"
                        class="uve-block-action"
                        title="Przesun w gore"
                    >
                        <x-heroicon-o-chevron-up class="w-4 h-4" />
                    </button>
                @endif

                @if($index < count($blocks) - 1)
                    <button
                        type="button"
                        wire:click.stop="moveBlockDown({{ $index }})"
                        class="uve-block-action"
                        title="Przesun w dol"
                    >
                        <x-heroicon-o-chevron-down class="w-4 h-4" />
                    </button>
                @endif

                <button
                    type="button"
                    wire:click.stop="duplicateBlock({{ $index }})"
                    class="uve-block-action"
                    title="Duplikuj"
                >
                    <x-heroicon-o-document-duplicate class="w-4 h-4" />
                </button>

                <button
                    type="button"
                    wire:click.stop="removeBlock({{ $index }})"
                    wire:confirm="Czy na pewno chcesz usunac ten blok?"
                    class="uve-block-action uve-block-action-delete"
                    title="Usun"
                >
                    <x-heroicon-o-trash class="w-4 h-4" />
                </button>
            @endif
        </div>
    </div>

    {{-- Block Content --}}
    <div class="uve-block-content">
        @if($isEditing)
            {{-- Editable mode: render elements as interactive --}}
            @if(isset($block['document']['root']))
                @include('livewire.products.visual-description.partials.uve-element-renderer', [
                    'element' => $block['document']['root'],
                    'blockIndex' => $index,
                    'editable' => true,
                ])
            @else
                <div class="uve-block-placeholder">
                    <p class="text-gray-400">Blok pusty - dodaj elementy</p>
                </div>
            @endif
        @else
            {{-- Locked mode: render compiled HTML --}}
            @if(!empty($block['compiled_html']))
                <div class="uve-block-preview">
                    {!! $block['compiled_html'] !!}
                </div>
            @else
                <div class="uve-block-placeholder">
                    <x-heroicon-o-cube class="w-8 h-8 text-gray-300" />
                    <p class="text-gray-400 text-sm mt-1">{{ $block['type'] }}</p>
                </div>
            @endif
        @endif
    </div>
</div>

<style>
/* UVE Block Item - Canvas Block (Light BG for content preview) */
.uve-block {
    position: relative;
    margin-bottom: 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
    background: white;
}

.uve-block:hover {
    border-color: #94a3b8;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.uve-block-selected {
    border-color: #e0ac7e;
    box-shadow: 0 0 0 3px rgba(224, 172, 126, 0.2);
}

.uve-block-editing {
    border-color: #e0ac7e;
    box-shadow: 0 0 0 3px rgba(224, 172, 126, 0.3), 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Toolbar - Dark Theme */
.uve-block-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 0.75rem;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-radius: 0.375rem 0.375rem 0 0;
    margin: -2px -2px 0 -2px;
}

.uve-block-type {
    font-size: 0.75rem;
    color: #e0ac7e;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.uve-block-toolbar-actions {
    display: flex;
    gap: 0.25rem;
}

.uve-block-action {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    color: #94a3b8;
    background: transparent;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-block-action:hover {
    color: white;
    background: #334155;
}

.uve-block-action-edit:hover {
    color: #e0ac7e;
    background: rgba(224, 172, 126, 0.15);
}

.uve-block-action-confirm:hover {
    color: #34d399;
    background: rgba(52, 211, 153, 0.15);
}

.uve-block-action-cancel:hover {
    color: #f87171;
    background: rgba(248, 113, 113, 0.15);
}

.uve-block-action-delete:hover {
    color: #f87171;
    background: rgba(248, 113, 113, 0.15);
}

/* Content - Light for preview */
.uve-block-content {
    padding: 1rem;
    min-height: 60px;
    background: white;
    border-radius: 0 0 0.375rem 0.375rem;
}

.uve-block-preview {
    pointer-events: none;
}

.uve-block-editing .uve-block-preview {
    pointer-events: auto;
}

.uve-block-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    background: #f8fafc;
    border: 2px dashed #cbd5e1;
    border-radius: 0.375rem;
    color: #64748b;
}

.uve-block-placeholder p {
    margin: 0.5rem 0 0;
}
</style>
