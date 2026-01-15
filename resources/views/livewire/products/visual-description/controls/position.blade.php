{{--
    Position Control - ETAP_07f_P5 FAZA PP.2
    Kontrolka do edycji pozycjonowania CSS:
    position type, top, right, bottom, left, z-index
--}}
@props([
    'controlId' => 'position',
    'value' => [],
    'options' => [],
    'onChange' => null,
])

@php
    $positionTypes = [
        'static' => 'Static',
        'relative' => 'Relative',
        'absolute' => 'Absolute',
        'fixed' => 'Fixed',
        'sticky' => 'Sticky',
    ];
@endphp

<div
    class="uve-control uve-control--position"
    x-data="uvePositionControl(@js($value))"
    wire:ignore.self
>
    {{-- Position Type --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Typ pozycjonowania</label>
        <div class="uve-position-types">
            @foreach($positionTypes as $posVal => $posLabel)
                <button
                    type="button"
                    @click="position = '{{ $posVal }}'; emitChange()"
                    class="uve-position-type-btn"
                    :class="{ 'uve-position-type-btn--active': position === '{{ $posVal }}' }"
                >
                    {{ $posLabel }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Position Offsets (only for non-static) --}}
    <template x-if="position !== 'static'">
        <div class="uve-position-offsets">
            <div class="uve-control__field">
                <label class="uve-control__label">Przesuniecia</label>

                {{-- Visual Position Diagram --}}
                <div class="uve-position-diagram">
                    <div class="uve-position-offset uve-position-offset--top">
                        <input
                            type="text"
                            x-model="top"
                            @input="emitChange()"
                            class="uve-input uve-input--xs"
                            placeholder="auto"
                        />
                    </div>
                    <div class="uve-position-offset uve-position-offset--right">
                        <input
                            type="text"
                            x-model="right"
                            @input="emitChange()"
                            class="uve-input uve-input--xs"
                            placeholder="auto"
                        />
                    </div>
                    <div class="uve-position-offset uve-position-offset--bottom">
                        <input
                            type="text"
                            x-model="bottom"
                            @input="emitChange()"
                            class="uve-input uve-input--xs"
                            placeholder="auto"
                        />
                    </div>
                    <div class="uve-position-offset uve-position-offset--left">
                        <input
                            type="text"
                            x-model="left"
                            @input="emitChange()"
                            class="uve-input uve-input--xs"
                            placeholder="auto"
                        />
                    </div>
                    <div class="uve-position-center">
                        <span class="uve-position-center-label">Element</span>
                    </div>
                </div>

                {{-- Unit Selector --}}
                <div class="uve-unit-selector">
                    <button
                        type="button"
                        @click="unit = 'px'"
                        class="uve-unit-btn"
                        :class="{ 'uve-unit-btn--active': unit === 'px' }"
                    >px</button>
                    <button
                        type="button"
                        @click="unit = '%'"
                        class="uve-unit-btn"
                        :class="{ 'uve-unit-btn--active': unit === '%' }"
                    >%</button>
                    <button
                        type="button"
                        @click="unit = 'rem'"
                        class="uve-unit-btn"
                        :class="{ 'uve-unit-btn--active': unit === 'rem' }"
                    >rem</button>
                    <button
                        type="button"
                        @click="unit = 'vh'"
                        class="uve-unit-btn"
                        :class="{ 'uve-unit-btn--active': unit === 'vh' }"
                    >vh</button>
                </div>
            </div>

            {{-- Quick Presets --}}
            <div class="uve-control__field">
                <label class="uve-control__label">Szybkie ustawienia</label>
                <div class="uve-position-presets">
                    <button type="button" @click="applyPreset('fill')" class="uve-position-preset-btn" title="Wypelnij caly obszar">
                        Wypelnij
                    </button>
                    <button type="button" @click="applyPreset('center')" class="uve-position-preset-btn" title="Wycentruj">
                        Srodek
                    </button>
                    <button type="button" @click="applyPreset('top-left')" class="uve-position-preset-btn" title="Lewy gorny rog">
                        LG
                    </button>
                    <button type="button" @click="applyPreset('top-right')" class="uve-position-preset-btn" title="Prawy gorny rog">
                        PG
                    </button>
                    <button type="button" @click="applyPreset('bottom-left')" class="uve-position-preset-btn" title="Lewy dolny rog">
                        LD
                    </button>
                    <button type="button" @click="applyPreset('bottom-right')" class="uve-position-preset-btn" title="Prawy dolny rog">
                        PD
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- Z-Index --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Warstwa (z-index)</label>
        <div class="uve-zindex-row">
            <input
                type="number"
                x-model="zIndex"
                @input="emitChange()"
                class="uve-input uve-input--sm"
                placeholder="auto"
            />
            <div class="uve-zindex-presets">
                <button type="button" @click="zIndex = ''; emitChange()" class="uve-zindex-btn">auto</button>
                <button type="button" @click="zIndex = '1'; emitChange()" class="uve-zindex-btn">1</button>
                <button type="button" @click="zIndex = '10'; emitChange()" class="uve-zindex-btn">10</button>
                <button type="button" @click="zIndex = '50'; emitChange()" class="uve-zindex-btn">50</button>
                <button type="button" @click="zIndex = '100'; emitChange()" class="uve-zindex-btn">100</button>
            </div>
        </div>
        <p class="uve-zindex-note">
            Uzyj wartosci z systemu warstw PPM: 1 (base), 10 (panel), 100 (modal), 200 (overlay)
        </p>
    </div>
</div>

<style>
/* Position Control Styles */
.uve-control--position {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Position Types */
.uve-position-types {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
}

.uve-position-type-btn {
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
    color: #94a3b8;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-position-type-btn:hover {
    background: #475569;
    color: #e2e8f0;
}

.uve-position-type-btn--active {
    background: #e0ac7e;
    border-color: #e0ac7e;
    color: #0f172a;
}

/* Position Offsets */
.uve-position-offsets {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Position Diagram */
.uve-position-diagram {
    position: relative;
    width: 100%;
    height: 120px;
    background: #1e293b;
    border: 2px dashed #475569;
    border-radius: 0.375rem;
}

.uve-position-offset {
    position: absolute;
}

.uve-position-offset--top {
    top: 8px;
    left: 50%;
    transform: translateX(-50%);
}

.uve-position-offset--right {
    top: 50%;
    right: 8px;
    transform: translateY(-50%);
}

.uve-position-offset--bottom {
    bottom: 8px;
    left: 50%;
    transform: translateX(-50%);
}

.uve-position-offset--left {
    top: 50%;
    left: 8px;
    transform: translateY(-50%);
}

.uve-position-offset .uve-input--xs {
    width: 50px;
    text-align: center;
}

.uve-position-center {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 60px;
    height: 40px;
    background: #334155;
    border: 2px solid #e0ac7e;
    border-radius: 0.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.uve-position-center-label {
    font-size: 0.65rem;
    color: #94a3b8;
}

/* Unit Selector */
.uve-unit-selector {
    display: flex;
    gap: 0.25rem;
    margin-top: 0.5rem;
}

.uve-unit-btn {
    flex: 1;
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

.uve-unit-btn:hover {
    background: #475569;
    color: #e2e8f0;
}

.uve-unit-btn--active {
    background: #e0ac7e;
    border-color: #e0ac7e;
    color: #0f172a;
}

/* Position Presets */
.uve-position-presets {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
}

.uve-position-preset-btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.7rem;
    color: #94a3b8;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-position-preset-btn:hover {
    background: #475569;
    color: #e2e8f0;
}

/* Z-Index Row */
.uve-zindex-row {
    display: flex;
    gap: 0.375rem;
    align-items: center;
}

.uve-zindex-row .uve-input--sm {
    width: 70px;
}

.uve-zindex-presets {
    display: flex;
    gap: 0.125rem;
}

.uve-zindex-btn {
    padding: 0.25rem 0.375rem;
    font-size: 0.65rem;
    color: #94a3b8;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-zindex-btn:hover {
    background: #475569;
    color: #e2e8f0;
}

.uve-zindex-note {
    margin-top: 0.375rem;
    font-size: 0.65rem;
    color: #64748b;
}
</style>

<script>
function uvePositionControl(initialValue) {
    return {
        position: initialValue.position || 'static',
        top: initialValue.top?.replace(/[^0-9.-]/g, '') || '',
        right: initialValue.right?.replace(/[^0-9.-]/g, '') || '',
        bottom: initialValue.bottom?.replace(/[^0-9.-]/g, '') || '',
        left: initialValue.left?.replace(/[^0-9.-]/g, '') || '',
        zIndex: initialValue.zIndex || '',
        unit: 'px',

        applyPreset(preset) {
            switch (preset) {
                case 'fill':
                    this.top = '0';
                    this.right = '0';
                    this.bottom = '0';
                    this.left = '0';
                    break;
                case 'center':
                    this.top = '50%';
                    this.left = '50%';
                    this.right = '';
                    this.bottom = '';
                    // Note: transform: translate(-50%, -50%) should be added separately
                    break;
                case 'top-left':
                    this.top = '0';
                    this.left = '0';
                    this.right = '';
                    this.bottom = '';
                    break;
                case 'top-right':
                    this.top = '0';
                    this.right = '0';
                    this.left = '';
                    this.bottom = '';
                    break;
                case 'bottom-left':
                    this.bottom = '0';
                    this.left = '0';
                    this.top = '';
                    this.right = '';
                    break;
                case 'bottom-right':
                    this.bottom = '0';
                    this.right = '0';
                    this.top = '';
                    this.left = '';
                    break;
            }
            this.emitChange();
        },

        formatValue(val) {
            if (!val || val === 'auto') return 'auto';
            // If already has unit, return as is
            if (/[a-z%]/.test(val)) return val;
            // Add current unit
            return val + this.unit;
        },

        emitChange() {
            const value = {
                position: this.position,
                top: this.top ? this.formatValue(this.top) : '',
                right: this.right ? this.formatValue(this.right) : '',
                bottom: this.bottom ? this.formatValue(this.bottom) : '',
                left: this.left ? this.formatValue(this.left) : '',
                zIndex: this.zIndex,
            };
            this.$wire.updateControlValue('position', value);
        }
    }
}
</script>
