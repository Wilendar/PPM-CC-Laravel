{{-- ETAP_08.5: Universal sync button (Shop + ERP) --}}
@php
    $baseClasses = 'btn-compact btn-compact-primary flex items-center justify-center';
    $buttonClasses = trim($baseClasses . ' ' . ($classes ?? ''));

    // Determine context: ERP or Shop
    $isErpContext = isset($erpConnectionId) && $erpConnectionId !== null;
    $targetId = $isErpContext ? $erpConnectionId : ($shopId ?? null);
    $methodName = $isErpContext ? 'syncToErp' : 'syncShop';
    $buttonLabel = $isErpContext ? 'Aktualizuj w ERP' : 'Aktualizuj aktualny sklep';
    $loadingLabel = $isErpContext ? 'Synchronizacja ERP...' : 'Aktualizowanie...';
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
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
    </svg>
    <span wire:loading.remove wire:target="{{ $methodName }}({{ $targetId }})">{{ $buttonLabel }}</span>
    <span wire:loading wire:target="{{ $methodName }}({{ $targetId }})">{{ $loadingLabel }}</span>
</button>
@endif
