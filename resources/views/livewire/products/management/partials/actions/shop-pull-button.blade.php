{{-- ETAP_08.5: Universal pull button (Shop + ERP) --}}
@php
    $baseClasses = 'btn-compact btn-compact-secondary flex items-center justify-center';
    $buttonClasses = trim($baseClasses . ' ' . ($classes ?? ''));

    // Determine context: ERP or Shop
    $isErpContext = isset($erpConnectionId) && $erpConnectionId !== null;
    $targetId = $isErpContext ? $erpConnectionId : ($shopId ?? null);
    $methodName = $isErpContext ? 'pullProductDataFromErp' : 'pullShopData';
    $buttonLabel = $isErpContext ? 'Wczytaj z ERP' : 'Wczytaj z aktualnego sklepu';
    $loadingLabel = $isErpContext ? 'Pobieranie z ERP...' : 'Wczytywanie...';
@endphp

@if($targetId)
<button
    type="button"
    wire:click="{{ $methodName }}({{ $targetId }})"
    class="{{ $buttonClasses }}"
    wire:loading.attr="disabled"
    wire:target="{{ $methodName }}({{ $targetId }})"
>
    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
    </svg>
    <span wire:loading.remove wire:target="{{ $methodName }}({{ $targetId }})">{{ $buttonLabel }}</span>
    <span wire:loading wire:target="{{ $methodName }}({{ $targetId }})">{{ $loadingLabel }}</span>
</button>
@endif
