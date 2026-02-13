{{-- Editable variant card for VariantModal --}}
@php
    $isActive = $variantActiveStates[$index] ?? true;
@endphp

<div class="import-variant-card {{ !$isActive ? 'import-variant-card-inactive' : '' }}"
     wire:key="variant-card-{{ $index }}">

    {{-- Header: SKU + Toggle + Remove --}}
    <div class="import-variant-card-header">
        <span class="import-variant-card-sku">{{ $variant['full_sku'] ?? $variant['sku_suffix'] ?? '-' }}</span>

        {{-- Active/Inactive toggle --}}
        <div class="import-variant-card-toggle {{ $isActive ? 'import-variant-card-toggle-on' : 'import-variant-card-toggle-off' }}"
             wire:click="toggleVariantActive({{ $index }})"
             title="{{ $isActive ? 'Wylacz wariant' : 'Wlacz wariant' }}">
            <div class="import-variant-card-toggle-dot {{ $isActive ? 'import-variant-card-toggle-dot-on' : 'import-variant-card-toggle-dot-off' }}"></div>
        </div>

        {{-- Remove button --}}
        <button type="button"
                wire:click="removeVariant({{ $index }})"
                class="import-variant-card-remove"
                title="Usun wariant">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
        </button>
    </div>

    {{-- Details: Name + Attribute badges + Price --}}
    <div class="import-variant-card-details">
        <span class="import-variant-card-name">{{ $variant['name'] ?? '-' }}</span>

        {{-- Attribute badges --}}
        @foreach($variant['attributes'] ?? [] as $attr)
            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[0.625rem] bg-gray-700/50 text-gray-300">
                @if(!empty($attr['color_hex']))
                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                          style="background-color: {{ $attr['color_hex'] }}"></span>
                @endif
                {{ $attr['value'] ?? '' }}
            </span>
        @endforeach

        {{-- Price input --}}
        <div class="ml-auto flex items-center gap-1 flex-shrink-0">
            <input type="number"
                   step="0.01"
                   min="0"
                   wire:change="setVariantPrice({{ $index }}, $event.target.value)"
                   value="{{ $variantPrices[$index] ?? '' }}"
                   placeholder="Cena"
                   class="import-variant-price-input"
                   title="Cena wariantu (PLN)">
            <span class="text-[0.625rem] text-gray-500">PLN</span>
        </div>
    </div>
</div>
