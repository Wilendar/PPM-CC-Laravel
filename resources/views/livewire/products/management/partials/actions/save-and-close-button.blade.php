@php
    $buttonClasses = trim('btn-enterprise-primary w-full py-3 text-lg ' . ($classes ?? ''));
@endphp

<button type="button"
        @click="if ($wire.activeJobStatus === 'pending' || $wire.activeJobStatus === 'processing') { window.location.href = '/admin/products'; } else { $wire.saveAndClose(); }"
        class="{{ $buttonClasses }}"
        :disabled="$wire.activeJobStatus === 'processing'"
        wire:loading.attr="disabled"
        wire:target="saveAndClose">

    {{-- Show "Wr�� do Listy Produkt�w" when job running (anti-duplicate) --}}
    <template x-if="$wire.activeJobStatus === 'pending' || $wire.activeJobStatus === 'processing'">
        <span>
            <i class="fas fa-arrow-left mr-3"></i>
            Wróć do Listy Produktów
        </span>
    </template>

    {{-- Show normal "Zapisz zmiany" when no job --}}
    <template x-if="!$wire.activeJobStatus || $wire.activeJobStatus === 'completed' || $wire.activeJobStatus === 'failed'">
        <span wire:loading.remove wire:target="saveAndClose">
            <i class="fas fa-save mr-3"></i>
            {{ $isEditMode ? 'Zapisz zmiany' : 'Utwórz produkt' }}
        </span>
    </template>

    {{-- Show "Zapisywanie..." when save in progress --}}
    <span wire:loading wire:target="saveAndClose">
        <i class="fas fa-spinner fa-spin mr-3"></i>
        Zapisywanie...
    </span>
</button>
