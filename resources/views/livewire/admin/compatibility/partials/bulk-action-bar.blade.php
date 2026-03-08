{{--
    Bulk Action Bar - Floating bar for bulk operations on selected parts
    Shows when parts are selected via checkboxes

    Required: ManagesBulkActions trait on the component
--}}
@if($this->getSelectedCount() > 0)
    <div class="compat-bulk-bar {{ $editingProductId ? 'compat-bulk-bar--elevated' : '' }}" x-data="{ showCopyModal: false }">
        <div class="compat-bulk-bar__info">
            <span class="compat-bulk-bar__count">
                <i class="fas fa-check-square"></i>
                Zaznaczono: {{ $this->getSelectedCount() }}
            </span>
        </div>

        <div class="compat-bulk-bar__actions">
            {{-- Bulk Edit (existing modal) --}}
            <button
                wire:click="openBulkEdit"
                class="compat-bulk-btn"
                title="Edycja masowa dopasowania"
            >
                <i class="fas fa-edit"></i>
                Przypisz pojazd
            </button>

            {{-- Export Selected --}}
            <button
                wire:click="exportSelected"
                class="compat-bulk-btn"
                title="Eksportuj zaznaczone do CSV"
            >
                <i class="fas fa-file-export"></i>
                Eksport
            </button>

            {{-- Clear Selection --}}
            <button
                wire:click="clearSelection"
                class="compat-bulk-btn compat-bulk-btn--secondary"
                title="Odznacz wszystkie"
            >
                <i class="fas fa-times"></i>
                Odznacz
            </button>
        </div>
    </div>
@endif
