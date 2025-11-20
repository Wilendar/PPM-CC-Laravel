@php
    $baseClasses = 'btn-compact btn-compact-primary flex items-center justify-center';
    $buttonClasses = trim($baseClasses . ' ' . ($classes ?? ''));
@endphp

<button
    type="button"
    wire:click="syncShop({{ $shopId }})"
    class="{{ $buttonClasses }}"
    wire:loading.attr="disabled"
    wire:target="syncShop({{ $shopId }})"
>
    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
    </svg>
    <span wire:loading.remove wire:target="syncShop({{ $shopId }})">Aktualizuj aktualny sklep</span>
    <span wire:loading wire:target="syncShop({{ $shopId }})">Aktualizowanie...</span>
</button>
