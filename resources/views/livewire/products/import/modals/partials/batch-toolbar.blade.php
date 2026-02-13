{{-- Batch operations toolbar - shows when images are selected --}}
@if(count($selectedImages) > 0)
<div class="import-batch-toolbar">
    {{-- Selection count --}}
    <span class="import-batch-toolbar-count">
        {{ count($selectedImages) }} {{ count($selectedImages) === 1 ? 'zaznaczone' : 'zaznaczonych' }}
    </span>

    {{-- Separator --}}
    <span class="text-gray-600">|</span>

    {{-- Assign to variant --}}
    @if(count($variants) > 0)
    <div class="flex items-center gap-2">
        <span class="text-xs text-gray-400">Przypisz do:</span>
        <select wire:change="batchAssignToVariant($event.target.value); $event.target.selectedIndex = 0"
                class="import-batch-toolbar-btn import-batch-toolbar-assign">
            <option value="" disabled selected>-- Wybierz --</option>
            <option value="_main">Produkt glowny</option>
            @foreach($variants as $variant)
            <option value="{{ $variant['sku_suffix'] ?? '' }}">
                {{ $this->getVariantDisplayName($variant) }}
            </option>
            @endforeach
        </select>
    </div>
    @endif

    {{-- Remove selected --}}
    <button type="button"
            wire:click="batchRemoveImages"
            wire:confirm="Czy na pewno usunac {{ count($selectedImages) }} zaznaczonych zdjec?"
            class="import-batch-toolbar-btn import-batch-toolbar-remove">
        Usun zaznaczone
    </button>

    {{-- Deselect --}}
    <button type="button"
            wire:click="deselectAllImages"
            class="import-batch-toolbar-btn text-gray-400 hover:text-white">
        Odznacz
    </button>
</div>
@endif
