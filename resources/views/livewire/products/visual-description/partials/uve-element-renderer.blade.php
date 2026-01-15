{{-- UVE Element Renderer - Rekurencyjne renderowanie elementow w trybie edycji --}}
@props(['element', 'blockIndex', 'editable' => false, 'parentId' => null])

@php
    $elementId = $element['id'] ?? '';
    $type = $element['type'] ?? 'container';
    $tag = $element['tag'] ?? 'div';
    $classes = implode(' ', $element['classes'] ?? []);
    $content = $element['content'] ?? '';
    $children = $element['children'] ?? [];
    $isSelected = $selectedElementId === $elementId;
    $isLocked = $element['locked'] ?? false;
    $isRoot = $parentId === null;
@endphp

<{{ $tag }}
    class="uve-element uve-element-{{ $type }} {{ $classes }} {{ $isSelected ? 'uve-element-selected' : '' }} {{ $isLocked ? 'uve-element-locked' : '' }}"
    data-element-id="{{ $elementId }}"
    data-element-type="{{ $type }}"
    data-parent-id="{{ $parentId ?? 'root' }}"
    @if($editable && !$isRoot && !$isLocked)
        draggable="true"
        x-on:dragstart="window.dispatchEvent(new CustomEvent('uve-dragstart', { detail: { event: $event, elementId: '{{ $elementId }}' } }))"
        x-on:dragend="window.dispatchEvent(new CustomEvent('uve-dragend', { detail: { event: $event } }))"
        x-on:dragover.prevent="window.dispatchEvent(new CustomEvent('uve-dragover', { detail: { event: $event, elementId: '{{ $elementId }}' } }))"
        x-on:dragleave="window.dispatchEvent(new CustomEvent('uve-dragleave', { detail: { event: $event } }))"
        x-on:drop.prevent="window.dispatchEvent(new CustomEvent('uve-drop', { detail: { event: $event, elementId: '{{ $elementId }}', parentId: '{{ $parentId ?? 'root' }}' } }))"
    @endif
    @if($editable)
        wire:click.stop="selectElement('{{ $elementId }}')"
        x-on:dblclick.stop="window.dispatchEvent(new CustomEvent('uve-inline-edit', { detail: { elementId: '{{ $elementId }}' } }))"
    @endif
    @if(!empty($element['styles']))
        style="{{ collect($element['styles'])->map(fn($v, $k) => strtolower(preg_replace('/([A-Z])/', '-$1', $k)) . ': ' . $v)->implode('; ') }}"
    @endif
    @if(!empty($element['src']))
        src="{{ $element['src'] }}"
    @endif
    @if(!empty($element['alt']))
        alt="{{ $element['alt'] }}"
    @endif
    @if(!empty($element['href']))
        href="{{ $element['href'] }}"
    @endif
>
    @if(in_array($type, ['heading', 'text', 'button', 'link']))
        {{-- Text content --}}
        {{ $content }}
    @elseif($type === 'image')
        {{-- Images are self-closing, content is in src --}}
    @else
        {{-- Container types: render children --}}
        @foreach($children as $child)
            @if($child['visible'] ?? true)
                @include('livewire.products.visual-description.partials.uve-element-renderer', [
                    'element' => $child,
                    'blockIndex' => $blockIndex,
                    'editable' => $editable,
                    'parentId' => $elementId,
                ])
            @endif
        @endforeach
    @endif
</{{ $tag }}>

@once
<style>
/* UVE Element Renderer - Elements inside editing block (light BG) */
.uve-element {
    position: relative;
    transition: outline 0.15s, box-shadow 0.15s;
    cursor: pointer;
}

.uve-element:hover {
    outline: 1px dashed #e0ac7e;
    outline-offset: 2px;
}

.uve-element-selected {
    outline: 2px solid #e0ac7e !important;
    outline-offset: 2px;
    box-shadow: 0 0 0 4px rgba(224, 172, 126, 0.2);
}

/* Element type indicators */
.uve-element-container {
    min-height: 40px;
    padding: 0.5rem;
}

.uve-element-row {
    display: flex;
    gap: 1rem;
}

.uve-element-column {
    flex: 1;
}

.uve-element-heading {
    font-weight: 600;
}

.uve-element-button {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: #e0ac7e;
    color: #0f172a;
    border-radius: 0.25rem;
    text-decoration: none;
    cursor: pointer;
    font-weight: 500;
}

.uve-element-button:hover {
    background: #d1975a;
}

.uve-element-divider {
    height: 1px;
    background: #cbd5e1;
    margin: 1rem 0;
}

.uve-element-spacer {
    height: 2rem;
    background: repeating-linear-gradient(
        45deg,
        transparent,
        transparent 4px,
        rgba(148, 163, 184, 0.1) 4px,
        rgba(148, 163, 184, 0.1) 8px
    );
}

.uve-element-image {
    max-width: 100%;
    height: auto;
}

.uve-element-link {
    color: #e0ac7e;
    text-decoration: underline;
}

.uve-element-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Locked elements */
.uve-element-locked {
    cursor: not-allowed;
    opacity: 0.7;
}

.uve-element-locked::after {
    content: '\1F512';
    position: absolute;
    top: 2px;
    right: 2px;
    font-size: 10px;
    opacity: 0.5;
}

.uve-element-locked:hover {
    outline-color: #64748b;
}
</style>
@endonce
