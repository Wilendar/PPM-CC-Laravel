{{--
    Layout Grid Control - ETAP_07f_P5 FAZA PP.2
    Kontrolka do edycji CSS Grid layout
    grid-template-columns, grid-template-rows, gap, align-items, justify-items
--}}
@props([
    'controlId' => 'layout-grid',
    'value' => [],
    'options' => [],
    'onChange' => null,
])

@php
    $columnPresets = $options['columnPresets'] ?? [
        '1fr' => '1 kol.',
        'repeat(2, 1fr)' => '2 kol.',
        'repeat(3, 1fr)' => '3 kol.',
        'repeat(4, 1fr)' => '4 kol.',
        'repeat(6, 1fr)' => '6 kol.',
        'repeat(auto-fit, minmax(280px, 1fr))' => 'Auto-fit',
    ];
    $rowPresets = $options['rowPresets'] ?? [
        'auto' => 'Auto',
        '1fr' => '1fr',
        'repeat(2, 1fr)' => '2 wiersze',
    ];
@endphp

<div
    class="uve-control uve-control--layout-grid"
    x-data="uveLayoutGridControl(@js($value))"
    wire:ignore.self
>
    {{-- Visual Grid Preview --}}
    <div class="uve-grid-preview" :style="previewStyle">
        <template x-for="i in previewCells" :key="i">
            <div class="uve-grid-cell"></div>
        </template>
    </div>

    {{-- Enable Grid Toggle --}}
    <div class="uve-control__field">
        <label class="uve-toggle-row">
            <input
                type="checkbox"
                x-model="enabled"
                @change="emitChange()"
                class="uve-checkbox"
            />
            <span class="uve-toggle-label">Wlacz Grid</span>
        </label>
    </div>

    <template x-if="enabled">
        <div class="uve-grid-options">
            {{-- Column Presets --}}
            <div class="uve-control__field">
                <label class="uve-control__label">Kolumny</label>
                <div class="uve-grid-presets">
                    @foreach($columnPresets as $presetVal => $presetLabel)
                        <button
                            type="button"
                            @click="gridTemplateColumns = '{{ $presetVal }}'; emitChange()"
                            class="uve-grid-preset-btn"
                            :class="{ 'uve-grid-preset-btn--active': gridTemplateColumns === '{{ $presetVal }}' }"
                        >
                            {{ $presetLabel }}
                        </button>
                    @endforeach
                </div>
                <input
                    type="text"
                    x-model="gridTemplateColumns"
                    @input="emitChange()"
                    class="uve-input uve-input--sm"
                    placeholder="repeat(3, 1fr)"
                />
            </div>

            {{-- Row Presets --}}
            <div class="uve-control__field">
                <label class="uve-control__label">Wiersze</label>
                <div class="uve-grid-presets">
                    @foreach($rowPresets as $presetVal => $presetLabel)
                        <button
                            type="button"
                            @click="gridTemplateRows = '{{ $presetVal }}'; emitChange()"
                            class="uve-grid-preset-btn"
                            :class="{ 'uve-grid-preset-btn--active': gridTemplateRows === '{{ $presetVal }}' }"
                        >
                            {{ $presetLabel }}
                        </button>
                    @endforeach
                </div>
                <input
                    type="text"
                    x-model="gridTemplateRows"
                    @input="emitChange()"
                    class="uve-input uve-input--sm"
                    placeholder="auto"
                />
            </div>

            {{-- Gap --}}
            <div class="uve-control__field">
                <label class="uve-control__label">Odstep (gap)</label>
                <div class="uve-gap-row">
                    <input
                        type="text"
                        x-model="gap"
                        @input="emitChange()"
                        class="uve-input uve-input--sm"
                        placeholder="1rem"
                    />
                    <div class="uve-gap-presets">
                        <button type="button" @click="gap = '0.5rem'; emitChange()" class="uve-gap-btn">S</button>
                        <button type="button" @click="gap = '1rem'; emitChange()" class="uve-gap-btn">M</button>
                        <button type="button" @click="gap = '1.5rem'; emitChange()" class="uve-gap-btn">L</button>
                        <button type="button" @click="gap = '2rem'; emitChange()" class="uve-gap-btn">XL</button>
                    </div>
                </div>
            </div>

            {{-- Align Items --}}
            <div class="uve-control__field">
                <label class="uve-control__label">Wyrownanie w komorce (align-items)</label>
                <select x-model="alignItems" @change="emitChange()" class="uve-select">
                    <option value="stretch">Rozciagnij</option>
                    <option value="start">Gora</option>
                    <option value="center">Srodek</option>
                    <option value="end">Dol</option>
                </select>
            </div>

            {{-- Justify Items --}}
            <div class="uve-control__field">
                <label class="uve-control__label">Wyrownanie poziome (justify-items)</label>
                <select x-model="justifyItems" @change="emitChange()" class="uve-select">
                    <option value="stretch">Rozciagnij</option>
                    <option value="start">Lewo</option>
                    <option value="center">Srodek</option>
                    <option value="end">Prawo</option>
                </select>
            </div>

            {{-- Grid Auto Flow --}}
            <div class="uve-control__field">
                <label class="uve-control__label">Auto flow</label>
                <div class="uve-btn-group-full">
                    <button
                        type="button"
                        @click="gridAutoFlow = 'row'; emitChange()"
                        class="uve-btn uve-btn-sm"
                        :class="{ 'uve-btn-active': gridAutoFlow === 'row' }"
                    >
                        Wiersz
                    </button>
                    <button
                        type="button"
                        @click="gridAutoFlow = 'column'; emitChange()"
                        class="uve-btn uve-btn-sm"
                        :class="{ 'uve-btn-active': gridAutoFlow === 'column' }"
                    >
                        Kolumna
                    </button>
                    <button
                        type="button"
                        @click="gridAutoFlow = 'dense'; emitChange()"
                        class="uve-btn uve-btn-sm"
                        :class="{ 'uve-btn-active': gridAutoFlow === 'dense' }"
                    >
                        Dense
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>

<style>
/* Layout Grid Control Styles */
.uve-control--layout-grid {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Grid Preview */
.uve-grid-preview {
    display: grid;
    min-height: 60px;
    padding: 0.375rem;
    background: #1e293b;
    border: 2px dashed #475569;
    border-radius: 0.375rem;
    gap: 0.25rem;
}

.uve-grid-cell {
    min-height: 16px;
    background: #e0ac7e;
    border-radius: 2px;
    opacity: 0.8;
}

/* Grid Options */
.uve-grid-options {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Grid Presets */
.uve-grid-presets {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    margin-bottom: 0.375rem;
}

.uve-grid-preset-btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.7rem;
    color: #94a3b8;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-grid-preset-btn:hover {
    background: #475569;
    color: #e2e8f0;
}

.uve-grid-preset-btn--active {
    background: #e0ac7e;
    border-color: #e0ac7e;
    color: #0f172a;
}

/* Gap Row */
.uve-gap-row {
    display: flex;
    gap: 0.375rem;
    align-items: center;
}

.uve-gap-presets {
    display: flex;
    gap: 0.125rem;
}

.uve-gap-btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.7rem;
    font-weight: 500;
    color: #94a3b8;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-gap-btn:hover {
    background: #475569;
    color: #e2e8f0;
}
</style>

<script>
function uveLayoutGridControl(initialValue) {
    return {
        enabled: initialValue.display === 'grid',
        gridTemplateColumns: initialValue.gridTemplateColumns || 'repeat(3, 1fr)',
        gridTemplateRows: initialValue.gridTemplateRows || 'auto',
        gap: initialValue.gap || '1rem',
        alignItems: initialValue.alignItems || 'stretch',
        justifyItems: initialValue.justifyItems || 'stretch',
        gridAutoFlow: initialValue.gridAutoFlow || 'row',

        get previewCells() {
            // Calculate preview cells based on column template
            const cols = this.gridTemplateColumns;
            if (cols.includes('repeat(')) {
                const match = cols.match(/repeat\((\d+)/);
                if (match) {
                    return parseInt(match[1]) * 2; // 2 rows
                }
            }
            if (cols === '1fr') return 2;
            return 6; // default
        },

        get previewStyle() {
            if (!this.enabled) return 'opacity: 0.5; grid-template-columns: repeat(3, 1fr);';
            return `
                grid-template-columns: ${this.gridTemplateColumns};
                grid-template-rows: ${this.gridTemplateRows};
                gap: ${this.gap || '0.25rem'};
                align-items: ${this.alignItems};
                justify-items: ${this.justifyItems};
            `;
        },

        emitChange() {
            const value = {
                display: this.enabled ? 'grid' : 'block',
                gridTemplateColumns: this.gridTemplateColumns,
                gridTemplateRows: this.gridTemplateRows,
                gap: this.gap,
                alignItems: this.alignItems,
                justifyItems: this.justifyItems,
                gridAutoFlow: this.gridAutoFlow,
            };
            this.$wire.updateControlValue('layout-grid', value);
        }
    }
}
</script>
