@php
    $buttonClasses = trim('btn-enterprise-primary w-full py-3 text-lg ' . ($classes ?? ''));
    $canSave = isset($userPermissions) ? ($isEditMode ? ($userPermissions['update'] ?? false) : ($userPermissions['create'] ?? false)) : true;
@endphp

@if($canSave)
{{-- ETAP_08.5: Check both PrestaShop AND ERP job status --}}
<button type="button"
        @click="
            const psRunning = ($wire.activeJobStatus === 'pending' || $wire.activeJobStatus === 'processing');
            const erpRunning = ($wire.activeErpJobStatus === 'pending' || $wire.activeErpJobStatus === 'running');
            if (psRunning || erpRunning) {
                window.location.href = '/admin/products';
            } else {
                $wire.saveAndClose();
            }
        "
        class="{{ $buttonClasses }}"
        :disabled="$wire.activeJobStatus === 'processing' || $wire.activeErpJobStatus === 'running'"
        wire:loading.attr="disabled"
        wire:target="saveAndClose">

    {{-- Show "Wroc do Listy Produktow" when ANY job running (PrestaShop OR ERP) --}}
    <template x-if="$wire.activeJobStatus === 'pending' || $wire.activeJobStatus === 'processing' || $wire.activeErpJobStatus === 'pending' || $wire.activeErpJobStatus === 'running'">
        <span>
            <i class="fas fa-arrow-left mr-3"></i>
            Wroc do Listy Produktow
        </span>
    </template>

    {{-- Show normal "Zapisz zmiany" when no job --}}
    <template x-if="(!$wire.activeJobStatus || $wire.activeJobStatus === 'completed' || $wire.activeJobStatus === 'failed') && (!$wire.activeErpJobStatus || $wire.activeErpJobStatus === 'completed' || $wire.activeErpJobStatus === 'failed')">
        <span wire:loading.remove wire:target="saveAndClose">
            <i class="fas fa-save mr-3"></i>
            {{ $isEditMode ? 'Zapisz zmiany' : 'Utworz produkt' }}
        </span>
    </template>

    {{-- Show "Zapisywanie..." when save in progress --}}
    <span wire:loading wire:target="saveAndClose">
        <i class="fas fa-spinner fa-spin mr-3"></i>
        Zapisywanie...
    </span>
</button>
@else
{{-- READ-ONLY: Disabled save button with lock icon --}}
<button type="button"
        disabled
        class="btn-enterprise-secondary w-full py-3 text-lg opacity-50 cursor-not-allowed"
        title="Brak uprawnien do zapisu">
    <i class="fas fa-lock mr-3"></i>
    Tryb podgladu
</button>
@endif
