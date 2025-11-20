@php
    $baseClasses = 'btn-compact btn-compact-success flex items-center justify-center';
    $buttonClasses = trim($baseClasses . ' ' . ($classes ?? ''));
@endphp

<button
    type="button"
    wire:click="saveAndClose"
    class="{{ $buttonClasses }}"
    wire:loading.attr="disabled"
    wire:target="saveAndClose"
>
    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
    </svg>
    <span wire:loading.remove wire:target="saveAndClose">Zapisz zmiany</span>
    <span wire:loading wire:target="saveAndClose">Zapisywanie...</span>
</button>
