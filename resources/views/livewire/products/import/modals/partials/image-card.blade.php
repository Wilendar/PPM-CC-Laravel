{{-- Image card partial for ImageUploadModal --}}
@props([
    'image',
    'index',
    'isGlobalCover' => false,
    'isVariantCover' => false,
    'showVariantAssignment' => false,
    'variants' => [],
    'variantCovers' => [],
    'selectedImages' => [],
])

@php
    $isSelected = in_array($index, $selectedImages);
@endphp

<div class="relative group bg-gray-700/50 rounded-lg overflow-hidden
            {{ $isGlobalCover ? 'ring-2 ring-green-500' : '' }}
            {{ $isVariantCover && !$isGlobalCover ? 'ring-2 ring-amber-500' : '' }}
            {{ $isSelected ? 'import-image-selected' : '' }}">
    {{-- Image preview --}}
    <div class="aspect-square relative">
        <img src="{{ Storage::disk('public')->url($image['path']) }}"
             alt="{{ $image['filename'] ?? 'Image' }}"
             class="w-full h-full object-cover">

        {{-- Selection checkbox (top-left) --}}
        <div class="import-image-checkbox {{ $isSelected ? 'selected' : '' }}"
             wire:click.stop="toggleImageSelection({{ $index }})"
             title="Zaznacz/odznacz">
            @if($isSelected)
                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
            @endif
        </div>

        {{-- Global cover badge (top-right) --}}
        @if($isGlobalCover)
        <div class="import-cover-badge-global">
            OKLADKA
        </div>
        @endif

        {{-- Variant cover badge (top-right, stacked below global via CSS adjacency rule) --}}
        @if($isVariantCover)
        <div class="import-cover-badge-variant">
            Okladka wariantu
        </div>
        @endif

        {{-- Position badge --}}
        <div class="absolute bottom-2 right-2 w-6 h-6 bg-gray-900/80 text-white text-xs
                    rounded-full flex items-center justify-center">
            {{ $index + 1 }}
        </div>

        {{-- Hover overlay --}}
        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100
                    transition-opacity flex items-center justify-center gap-2">
            {{-- Set as global cover --}}
            @if(!$isGlobalCover)
            <button type="button"
                    wire:click="setCover({{ $index }})"
                    class="p-2 bg-green-900/60 hover:bg-green-900/80 border border-green-600/50 rounded-lg text-green-300"
                    title="Ustaw jako okladke produktu">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </button>
            @endif

            {{-- Set as variant cover --}}
            @if($showVariantAssignment && !empty($image['variant_sku']) && !$isVariantCover)
            <button type="button"
                    wire:click="setVariantCover({{ $index }}, '{{ $image['variant_sku'] }}')"
                    class="p-2 bg-amber-900/60 hover:bg-amber-900/80 border border-amber-600/50 rounded-lg text-amber-300"
                    title="Ustaw jako okladke wariantu">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
            </button>
            @endif

            {{-- Move up --}}
            @if($index > 0)
            <button type="button"
                    wire:click="moveUp({{ $index }})"
                    class="p-2 bg-blue-900/60 hover:bg-blue-900/80 border border-blue-600/50 rounded-lg text-blue-300"
                    title="Przesun w gore">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                </svg>
            </button>
            @endif

            {{-- Move down --}}
            @if($index < count($images ?? []) - 1)
            <button type="button"
                    wire:click="moveDown({{ $index }})"
                    class="p-2 bg-blue-900/60 hover:bg-blue-900/80 border border-blue-600/50 rounded-lg text-blue-300"
                    title="Przesun w dol">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            @endif

            {{-- Remove --}}
            <button type="button"
                    wire:click="removeImage({{ $index }})"
                    class="p-2 bg-red-900/60 hover:bg-red-900/80 border border-red-600/50 rounded-lg text-red-300"
                    title="Usun">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Filename --}}
    <div class="p-2 text-center">
        <p class="text-xs text-gray-400 truncate" title="{{ $image['filename'] ?? '' }}">
            {{ $image['filename'] ?? 'image.jpg' }}
        </p>
        @if(!empty($image['size']))
        <p class="text-xs text-gray-500">
            {{ number_format($image['size'] / 1024, 1) }} KB
        </p>
        @endif
    </div>

    {{-- Variant assignment dropdown --}}
    @if($showVariantAssignment && count($variants) > 0)
    <div class="px-2 pb-2">
        <select wire:change="assignToVariant({{ $index }}, $event.target.value)"
                class="w-full text-xs bg-gray-800 border border-gray-600 rounded px-2 py-1.5
                       text-gray-300 focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
            <option value="" {{ empty($image['variant_sku']) ? 'selected' : '' }}>
                Produkt glowny
            </option>
            @foreach($variants as $variant)
            <option value="{{ $variant['sku_suffix'] ?? '' }}"
                    {{ ($image['variant_sku'] ?? '') === ($variant['sku_suffix'] ?? '') ? 'selected' : '' }}>
                {{ $this->getVariantDisplayName($variant) }}
            </option>
            @endforeach
        </select>
        @if(!empty($image['variant_sku']))
        <div class="mt-1 text-center">
            <span class="inline-block px-1.5 py-0.5 bg-purple-900/50 text-purple-300 border border-purple-700/50 text-xs rounded">
                {{ $image['variant_sku'] }}
            </span>
        </div>
        @endif
    </div>
    @endif
</div>
