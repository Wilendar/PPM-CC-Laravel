{{--
    Box Model Control - ETAP_07f_P5 FAZA PP.2
    Kontrolka do edycji margin, padding, border-radius
    Wsparcie dla linked (wszystkie strony razem) i jednostek (px, rem, em, %)
--}}
@props([
    'controlId' => 'box-model',
    'value' => [],
    'options' => [],
    'onChange' => null,
])

@php
    $units = $options['units'] ?? ['px', 'rem', 'em', '%'];
    $presets = $options['presets'] ?? [];

    // Default values
    $margin = $value['margin'] ?? ['top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'linked' => true];
    $padding = $value['padding'] ?? ['top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'linked' => true];
    $borderRadius = $value['borderRadius'] ?? ['top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'linked' => true];
@endphp

<div
    class="uve-control uve-control--box-model"
    x-data="uveBoxModelControl(@js($value), @js($units), @js($presets))"
    wire:ignore.self
>
    {{-- Margin Section --}}
    <div class="uve-box-model-section">
        <div class="uve-box-model-header">
            <span class="uve-control__label">Margin</span>
            <button
                type="button"
                @click="toggleLinked('margin')"
                class="uve-link-btn"
                :class="{ 'uve-link-btn--active': margin.linked }"
                title="Polacz wszystkie strony"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="margin.linked" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                    <path x-show="!margin.linked" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1M6 18L18 6"></path>
                </svg>
            </button>
        </div>

        {{-- Visual Box Model Diagram --}}
        <div class="uve-box-diagram uve-box-diagram--margin">
            <input
                type="text"
                x-model="margin.top"
                @input="onMarginChange('top')"
                class="uve-box-input uve-box-input--top"
                placeholder="0"
            />
            <input
                type="text"
                x-model="margin.right"
                @input="onMarginChange('right')"
                class="uve-box-input uve-box-input--right"
                placeholder="0"
            />
            <input
                type="text"
                x-model="margin.bottom"
                @input="onMarginChange('bottom')"
                class="uve-box-input uve-box-input--bottom"
                placeholder="0"
            />
            <input
                type="text"
                x-model="margin.left"
                @input="onMarginChange('left')"
                class="uve-box-input uve-box-input--left"
                placeholder="0"
            />
            <div class="uve-box-center">
                <span class="uve-box-label">MARGIN</span>
            </div>
        </div>

        {{-- Unit Selector --}}
        <div class="uve-unit-selector">
            <template x-for="unit in units" :key="unit">
                <button
                    type="button"
                    @click="setUnit('margin', unit)"
                    class="uve-unit-btn"
                    :class="{ 'uve-unit-btn--active': currentUnits.margin === unit }"
                    x-text="unit"
                ></button>
            </template>
        </div>
    </div>

    {{-- Padding Section --}}
    <div class="uve-box-model-section">
        <div class="uve-box-model-header">
            <span class="uve-control__label">Padding</span>
            <button
                type="button"
                @click="toggleLinked('padding')"
                class="uve-link-btn"
                :class="{ 'uve-link-btn--active': padding.linked }"
                title="Polacz wszystkie strony"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="padding.linked" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                    <path x-show="!padding.linked" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1M6 18L18 6"></path>
                </svg>
            </button>
        </div>

        <div class="uve-box-diagram uve-box-diagram--padding">
            <input
                type="text"
                x-model="padding.top"
                @input="onPaddingChange('top')"
                class="uve-box-input uve-box-input--top"
                placeholder="0"
            />
            <input
                type="text"
                x-model="padding.right"
                @input="onPaddingChange('right')"
                class="uve-box-input uve-box-input--right"
                placeholder="0"
            />
            <input
                type="text"
                x-model="padding.bottom"
                @input="onPaddingChange('bottom')"
                class="uve-box-input uve-box-input--bottom"
                placeholder="0"
            />
            <input
                type="text"
                x-model="padding.left"
                @input="onPaddingChange('left')"
                class="uve-box-input uve-box-input--left"
                placeholder="0"
            />
            <div class="uve-box-center">
                <span class="uve-box-label">PADDING</span>
            </div>
        </div>

        <div class="uve-unit-selector">
            <template x-for="unit in units" :key="unit">
                <button
                    type="button"
                    @click="setUnit('padding', unit)"
                    class="uve-unit-btn"
                    :class="{ 'uve-unit-btn--active': currentUnits.padding === unit }"
                    x-text="unit"
                ></button>
            </template>
        </div>
    </div>

    {{-- Presets --}}
    @if(count($presets) > 0)
        <div class="uve-presets">
            <span class="uve-presets-label">Presety:</span>
            <div class="uve-presets-btns">
                @foreach($presets as $key => $preset)
                    <button
                        type="button"
                        wire:click="applyPreset('{{ $controlId }}', '{{ $key }}')"
                        class="uve-preset-btn"
                    >
                        {{ $preset['label'] }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif
</div>

<style>
/* Box Model Control Styles */
.uve-control--box-model {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.uve-box-model-section {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.uve-box-model-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.uve-link-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.25rem;
    color: #64748b;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-link-btn:hover {
    background: #475569;
    color: #e2e8f0;
}

.uve-link-btn--active {
    background: rgba(224, 172, 126, 0.2);
    border-color: #e0ac7e;
    color: #e0ac7e;
}

/* Box Model Diagram */
.uve-box-diagram {
    position: relative;
    width: 100%;
    height: 100px;
    border: 2px dashed #475569;
    border-radius: 0.375rem;
    background: #1e293b;
}

.uve-box-diagram--margin {
    border-color: #f97316;
}

.uve-box-diagram--padding {
    border-color: #3b82f6;
}

.uve-box-input {
    position: absolute;
    width: 40px;
    padding: 0.25rem;
    font-size: 0.75rem;
    text-align: center;
    background: #0f172a;
    border: 1px solid #475569;
    border-radius: 0.25rem;
    color: #e2e8f0;
}

.uve-box-input:focus {
    outline: none;
    border-color: #e0ac7e;
}

.uve-box-input--top {
    top: 4px;
    left: 50%;
    transform: translateX(-50%);
}

.uve-box-input--right {
    top: 50%;
    right: 4px;
    transform: translateY(-50%);
}

.uve-box-input--bottom {
    bottom: 4px;
    left: 50%;
    transform: translateX(-50%);
}

.uve-box-input--left {
    top: 50%;
    left: 4px;
    transform: translateY(-50%);
}

.uve-box-center {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.uve-box-label {
    font-size: 0.65rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Unit Selector */
.uve-unit-selector {
    display: flex;
    gap: 0.25rem;
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

/* Presets */
.uve-presets {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    padding-top: 0.5rem;
    border-top: 1px solid #334155;
}

.uve-presets-label {
    font-size: 0.7rem;
    color: #64748b;
}

.uve-presets-btns {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
}

.uve-preset-btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.7rem;
    color: #94a3b8;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-preset-btn:hover {
    background: #475569;
    color: #e2e8f0;
    border-color: #e0ac7e;
}
</style>

<script>
function uveBoxModelControl(initialValue, units, presets) {
    return {
        margin: initialValue.margin || { top: '', right: '', bottom: '', left: '', linked: true },
        padding: initialValue.padding || { top: '', right: '', bottom: '', left: '', linked: true },
        borderRadius: initialValue.borderRadius || { top: '', right: '', bottom: '', left: '', linked: true },
        units: units,
        presets: presets,
        currentUnits: {
            margin: 'px',
            padding: 'px',
            borderRadius: 'px'
        },

        toggleLinked(type) {
            this[type].linked = !this[type].linked;
            this.emitChange();
        },

        setUnit(type, unit) {
            this.currentUnits[type] = unit;
        },

        onMarginChange(side) {
            if (this.margin.linked) {
                const value = this.margin[side];
                this.margin.top = value;
                this.margin.right = value;
                this.margin.bottom = value;
                this.margin.left = value;
            }
            this.emitChange();
        },

        onPaddingChange(side) {
            if (this.padding.linked) {
                const value = this.padding[side];
                this.padding.top = value;
                this.padding.right = value;
                this.padding.bottom = value;
                this.padding.left = value;
            }
            this.emitChange();
        },

        emitChange() {
            const value = {
                margin: { ...this.margin },
                padding: { ...this.padding },
                borderRadius: { ...this.borderRadius }
            };
            this.$wire.updateControlValue('box-model', value);
        }
    }
}
</script>
