{{-- UVE Import Modal --}}
<div
    class="uve-modal-overlay"
    x-data="{ }"
    x-on:click.self="$wire.closeImportModal()"
    x-on:keydown.escape.window="$wire.closeImportModal()"
>
    <div class="uve-modal">
        <div class="uve-modal-header">
            <h3 class="uve-modal-title">Import opisu</h3>
            <button
                type="button"
                wire:click="closeImportModal"
                class="uve-modal-close"
            >
                <x-heroicon-o-x-mark class="w-5 h-5" />
            </button>
        </div>

        <div class="uve-modal-body">
            {{-- Import Source Tabs --}}
            <div class="uve-import-tabs">
                <button
                    type="button"
                    wire:click="$set('importSource', 'html')"
                    class="uve-import-tab {{ $importSource === 'html' ? 'active' : '' }}"
                >
                    <x-heroicon-o-code-bracket class="w-4 h-4" />
                    HTML
                </button>
                <button
                    type="button"
                    wire:click="$set('importSource', 'prestashop')"
                    class="uve-import-tab {{ $importSource === 'prestashop' ? 'active' : '' }}"
                >
                    <x-heroicon-o-cloud-arrow-down class="w-4 h-4" />
                    PrestaShop
                </button>
            </div>

            @if($importSource === 'html')
                {{-- HTML Import --}}
                <div class="uve-import-content">
                    <div class="uve-property-field">
                        <label class="uve-property-label">Kod HTML</label>
                        <textarea
                            wire:model="importHtml"
                            class="uve-textarea"
                            rows="12"
                            placeholder="Wklej tutaj kod HTML..."
                        ></textarea>
                    </div>

                    <div class="uve-property-field">
                        <label class="uve-property-label">Tryb importu</label>
                        <div class="uve-radio-group">
                            <label class="uve-radio-label">
                                <input
                                    type="radio"
                                    wire:model="importMode"
                                    value="replace"
                                    class="uve-radio"
                                />
                                <span>Zastap istniejace</span>
                            </label>
                            <label class="uve-radio-label">
                                <input
                                    type="radio"
                                    wire:model="importMode"
                                    value="append"
                                    class="uve-radio"
                                />
                                <span>Dodaj na koniec</span>
                            </label>
                        </div>
                    </div>
                </div>
            @else
                {{-- PrestaShop Import --}}
                <div class="uve-import-content">
                    <div class="uve-import-info">
                        <x-heroicon-o-information-circle class="w-5 h-5 text-blue-500" />
                        <p>Import z PrestaShop pobierze aktualny opis produktu z wybranego sklepu.</p>
                    </div>

                    @if($this->shop)
                        <div class="uve-import-shop-info">
                            <span class="uve-import-shop-label">Sklep:</span>
                            <span class="uve-import-shop-name">{{ $this->shop->name ?? 'Nieznany' }}</span>
                        </div>
                    @endif

                    <div class="uve-property-field">
                        <label class="uve-property-label">Tryb importu</label>
                        <div class="uve-radio-group">
                            <label class="uve-radio-label">
                                <input
                                    type="radio"
                                    wire:model="importMode"
                                    value="replace"
                                    class="uve-radio"
                                />
                                <span>Zastap istniejace</span>
                            </label>
                            <label class="uve-radio-label">
                                <input
                                    type="radio"
                                    wire:model="importMode"
                                    value="append"
                                    class="uve-radio"
                                />
                                <span>Dodaj na koniec</span>
                            </label>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="uve-modal-footer">
            <button
                type="button"
                wire:click="closeImportModal"
                class="uve-btn"
            >
                Anuluj
            </button>
            <button
                type="button"
                wire:click="executeImport"
                class="uve-btn uve-btn-primary"
                @if($importSource === 'html' && empty($importHtml)) disabled @endif
            >
                <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                Importuj
            </button>
        </div>
    </div>
</div>

<style>
.uve-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
    padding: 1rem;
}

.uve-modal {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 600px;
    max-height: calc(100vh - 2rem);
    display: flex;
    flex-direction: column;
}

.uve-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.uve-modal-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin: 0;
}

.uve-modal-close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    color: #6b7280;
    background: transparent;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
}

.uve-modal-close:hover {
    background: #f3f4f6;
    color: #374151;
}

.uve-modal-body {
    flex: 1;
    padding: 1.5rem;
    overflow-y: auto;
}

.uve-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid #e2e8f0;
}

/* Import Tabs */
.uve-import-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.uve-import-tab {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
    background: #f3f4f6;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-import-tab:hover {
    color: #374151;
}

.uve-import-tab.active {
    background: #2563eb;
    color: white;
}

.uve-import-content {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.uve-textarea {
    width: 100%;
    padding: 0.75rem;
    font-size: 0.875rem;
    font-family: 'Fira Code', monospace;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    resize: vertical;
}

.uve-textarea:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
}

.uve-radio-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.uve-radio-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #374151;
    cursor: pointer;
}

.uve-radio {
    width: 16px;
    height: 16px;
}

.uve-import-info {
    display: flex;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: #eff6ff;
    border-radius: 0.375rem;
}

.uve-import-info p {
    margin: 0;
    font-size: 0.875rem;
    color: #1e40af;
}

.uve-import-shop-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: #f8fafc;
    border-radius: 0.375rem;
}

.uve-import-shop-label {
    font-size: 0.875rem;
    color: #6b7280;
}

.uve-import-shop-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: #111827;
}
</style>
