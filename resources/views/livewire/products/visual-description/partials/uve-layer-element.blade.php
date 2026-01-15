{{-- UVE Layer Element - Pojedynczy element w layers panel (rekurencyjny) --}}
@props(['element', 'depth' => 0])

@php
    $elementId = $element['id'] ?? '';
    $type = $element['type'] ?? 'element';
    $children = $element['children'] ?? [];
    $isSelected = $selectedElementId === $elementId;
    $paddingLeft = $depth * 0.75;
@endphp

<div
    class="uve-layer-element {{ $isSelected ? 'uve-layer-element-selected' : '' }}"
    style="padding-left: {{ $paddingLeft }}rem;"
    wire:click.stop="selectElement('{{ $elementId }}')"
>
    <div class="uve-layer-element-content">
        <span class="uve-layer-element-icon">
            @switch($type)
                @case('container')
                    <x-heroicon-o-square-2-stack class="w-3.5 h-3.5" />
                    @break
                @case('row')
                    <x-heroicon-o-view-columns class="w-3.5 h-3.5" />
                    @break
                @case('heading')
                    <x-heroicon-o-bars-3-bottom-left class="w-3.5 h-3.5" />
                    @break
                @case('text')
                    <x-heroicon-o-document-text class="w-3.5 h-3.5" />
                    @break
                @case('image')
                    <x-heroicon-o-photo class="w-3.5 h-3.5" />
                    @break
                @case('button')
                    <x-heroicon-o-cursor-arrow-rays class="w-3.5 h-3.5" />
                    @break
                @default
                    <x-heroicon-o-cube class="w-3.5 h-3.5" />
            @endswitch
        </span>
        <span class="uve-layer-element-type">{{ $type }}</span>
    </div>
</div>

@if(count($children) > 0)
    @foreach($children as $child)
        @include('livewire.products.visual-description.partials.uve-layer-element', [
            'element' => $child,
            'depth' => $depth + 1,
        ])
    @endforeach
@endif

@once
<style>
.uve-layer-element {
    display: flex;
    align-items: center;
    padding: 0.375rem 0.5rem;
    cursor: pointer;
    transition: background 0.15s;
}

.uve-layer-element:hover {
    background: #f9fafb;
}

.uve-layer-element-selected {
    background: #dbeafe;
}

.uve-layer-element-content {
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.uve-layer-element-icon {
    color: #9ca3af;
}

.uve-layer-element-selected .uve-layer-element-icon {
    color: #2563eb;
}

.uve-layer-element-type {
    font-size: 0.8125rem;
    color: #6b7280;
}

.uve-layer-element-selected .uve-layer-element-type {
    color: #1e40af;
    font-weight: 500;
}
</style>
@endonce
