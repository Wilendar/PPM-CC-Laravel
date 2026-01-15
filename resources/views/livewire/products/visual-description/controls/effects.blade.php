{{--
    Effects Control - ETAP_07f_P5 FAZA PP.2
    Kontrolka do edycji efektow: box-shadow, text-shadow, opacity
    Z mozliwoscia dodawania wielu cieni
--}}
@props([
    'controlId' => 'effects',
    'value' => [],
    'options' => [],
    'onChange' => null,
])

@php
    $shadowPresets = $options['shadowPresets'] ?? [
        'none' => ['label' => 'Brak', 'value' => 'none'],
        'sm' => ['label' => 'S', 'value' => '0 1px 2px 0 rgba(0, 0, 0, 0.05)'],
        'md' => ['label' => 'M', 'value' => '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1)'],
        'lg' => ['label' => 'L', 'value' => '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1)'],
        'xl' => ['label' => 'XL', 'value' => '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1)'],
    ];
@endphp

<div
    class="uve-control uve-control--effects"
    x-data="uveEffectsControl(@js($value))"
    wire:ignore.self
>
    {{-- Effects Preview --}}
    <div class="uve-effects-preview" :style="previewStyle">
        <span class="uve-effects-preview-text">Efekty</span>
    </div>

    {{-- Opacity --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Przezroczystosc</label>
        <div class="uve-opacity-control">
            <input
                type="range"
                x-model="opacity"
                @input="emitChange()"
                min="0"
                max="100"
                step="1"
                class="uve-slider"
            />
            <span class="uve-opacity-value" x-text="opacity + '%'"></span>
        </div>
    </div>

    {{-- Box Shadow --}}
    <div class="uve-control__field">
        <div class="uve-effects-header">
            <label class="uve-control__label">Cien elementu (box-shadow)</label>
            <button
                type="button"
                @click="addBoxShadow()"
                class="uve-btn uve-btn-xs"
                :disabled="boxShadows.length >= 3"
                title="Dodaj cien"
            >
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
            </button>
        </div>

        {{-- Shadow Presets --}}
        <div class="uve-shadow-presets">
            @foreach($shadowPresets as $presetKey => $preset)
                <button
                    type="button"
                    @click="applyBoxShadowPreset('{{ $preset['value'] }}')"
                    class="uve-shadow-preset-btn"
                    title="{{ $preset['label'] }}"
                >
                    {{ $preset['label'] }}
                </button>
            @endforeach
        </div>

        {{-- Shadow List --}}
        <div class="uve-shadows-list">
            <template x-for="(shadow, index) in boxShadows" :key="index">
                <div class="uve-shadow-item">
                    <div class="uve-shadow-row">
                        {{-- Inset Toggle --}}
                        <button
                            type="button"
                            @click="shadow.inset = !shadow.inset; emitChange()"
                            class="uve-btn uve-btn-xs"
                            :class="{ 'uve-btn-active': shadow.inset }"
                            title="Inset (wewnetrzny)"
                        >
                            In
                        </button>

                        {{-- Color --}}
                        <div class="uve-shadow-color-wrapper">
                            <input
                                type="color"
                                :value="shadow.color.startsWith('#') ? shadow.color : '#000000'"
                                @input="shadow.color = $event.target.value; emitChange()"
                                class="uve-shadow-color-input"
                            />
                            <div class="uve-shadow-color-preview" :style="'background-color: ' + shadow.color"></div>
                        </div>

                        {{-- Remove --}}
                        <button
                            type="button"
                            @click="removeBoxShadow(index)"
                            class="uve-btn uve-btn-xs uve-btn-danger"
                            title="Usun"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="uve-shadow-inputs">
                        <div class="uve-shadow-input-group">
                            <label>X</label>
                            <input type="number" x-model="shadow.x" @input="emitChange()" class="uve-input uve-input--xs" />
                        </div>
                        <div class="uve-shadow-input-group">
                            <label>Y</label>
                            <input type="number" x-model="shadow.y" @input="emitChange()" class="uve-input uve-input--xs" />
                        </div>
                        <div class="uve-shadow-input-group">
                            <label>Blur</label>
                            <input type="number" x-model="shadow.blur" @input="emitChange()" class="uve-input uve-input--xs" min="0" />
                        </div>
                        <div class="uve-shadow-input-group">
                            <label>Spread</label>
                            <input type="number" x-model="shadow.spread" @input="emitChange()" class="uve-input uve-input--xs" />
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Text Shadow --}}
    <div class="uve-control__field">
        <div class="uve-effects-header">
            <label class="uve-control__label">Cien tekstu (text-shadow)</label>
            <label class="uve-toggle-row uve-toggle-row--sm">
                <input
                    type="checkbox"
                    x-model="textShadowEnabled"
                    @change="emitChange()"
                    class="uve-checkbox"
                />
                <span class="uve-toggle-label">Wlacz</span>
            </label>
        </div>

        <template x-if="textShadowEnabled">
            <div class="uve-text-shadow-controls">
                <div class="uve-shadow-row">
                    <div class="uve-shadow-color-wrapper">
                        <input
                            type="color"
                            x-model="textShadow.color"
                            @input="emitChange()"
                            class="uve-shadow-color-input"
                        />
                        <div class="uve-shadow-color-preview" :style="'background-color: ' + textShadow.color"></div>
                    </div>
                </div>

                <div class="uve-shadow-inputs">
                    <div class="uve-shadow-input-group">
                        <label>X</label>
                        <input type="number" x-model="textShadow.x" @input="emitChange()" class="uve-input uve-input--xs" />
                    </div>
                    <div class="uve-shadow-input-group">
                        <label>Y</label>
                        <input type="number" x-model="textShadow.y" @input="emitChange()" class="uve-input uve-input--xs" />
                    </div>
                    <div class="uve-shadow-input-group">
                        <label>Blur</label>
                        <input type="number" x-model="textShadow.blur" @input="emitChange()" class="uve-input uve-input--xs" min="0" />
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

<style>
/* Effects Control Styles */
.uve-control--effects {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Effects Preview */
.uve-effects-preview {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 60px;
    background: #f6f6f6;
    border-radius: 0.375rem;
    transition: all 0.15s;
}

.uve-effects-preview-text {
    font-size: 0.875rem;
    font-weight: 500;
    color: #333;
}

/* Effects Header */
.uve-effects-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Opacity Control */
.uve-opacity-control {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.uve-slider {
    flex: 1;
    height: 6px;
    appearance: none;
    background: linear-gradient(to right, transparent, #e0ac7e);
    border-radius: 3px;
    cursor: pointer;
}

.uve-slider::-webkit-slider-thumb {
    appearance: none;
    width: 16px;
    height: 16px;
    background: #e0ac7e;
    border: 2px solid #0f172a;
    border-radius: 50%;
    cursor: pointer;
}

.uve-opacity-value {
    width: 40px;
    font-size: 0.75rem;
    color: #94a3b8;
    text-align: right;
}

/* Shadow Presets */
.uve-shadow-presets {
    display: flex;
    gap: 0.25rem;
    margin-bottom: 0.5rem;
}

.uve-shadow-preset-btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.7rem;
    color: #94a3b8;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-shadow-preset-btn:hover {
    background: #475569;
    color: #e2e8f0;
}

/* Shadow List */
.uve-shadows-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.uve-shadow-item {
    padding: 0.5rem;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 0.375rem;
}

.uve-shadow-row {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    margin-bottom: 0.375rem;
}

.uve-shadow-color-wrapper {
    position: relative;
    width: 24px;
    height: 24px;
    flex-shrink: 0;
}

.uve-shadow-color-input {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.uve-shadow-color-preview {
    width: 100%;
    height: 100%;
    border-radius: 0.25rem;
    border: 2px solid #475569;
}

.uve-shadow-inputs {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.375rem;
}

.uve-shadow-input-group {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.uve-shadow-input-group label {
    font-size: 0.65rem;
    color: #64748b;
}

.uve-input--xs {
    width: 100%;
    padding: 0.25rem 0.375rem;
    font-size: 0.75rem;
}

/* Toggle Row Small */
.uve-toggle-row--sm {
    font-size: 0.75rem;
}

.uve-toggle-row--sm .uve-checkbox {
    width: 14px;
    height: 14px;
}

/* Text Shadow Controls */
.uve-text-shadow-controls {
    margin-top: 0.375rem;
}

/* Button Styles */
.uve-btn-xs {
    padding: 0.25rem;
    font-size: 0.7rem;
}

.uve-btn-danger {
    color: #f87171;
}

.uve-btn-danger:hover {
    background: rgba(248, 113, 113, 0.2);
    border-color: #f87171;
}
</style>

<script>
function uveEffectsControl(initialValue) {
    return {
        opacity: parseInt(initialValue.opacity) || 100,
        boxShadows: initialValue.boxShadows || [
            { x: 0, y: 4, blur: 6, spread: -1, color: 'rgba(0, 0, 0, 0.1)', inset: false }
        ],
        textShadowEnabled: !!initialValue.textShadow,
        textShadow: initialValue.textShadow || { x: 1, y: 1, blur: 2, color: 'rgba(0, 0, 0, 0.3)' },

        get previewStyle() {
            let style = `opacity: ${this.opacity / 100};`;

            const boxShadowStr = this.boxShadows.map(s =>
                `${s.inset ? 'inset ' : ''}${s.x}px ${s.y}px ${s.blur}px ${s.spread}px ${s.color}`
            ).join(', ');
            if (boxShadowStr) {
                style += `box-shadow: ${boxShadowStr};`;
            }

            if (this.textShadowEnabled) {
                style += `text-shadow: ${this.textShadow.x}px ${this.textShadow.y}px ${this.textShadow.blur}px ${this.textShadow.color};`;
            }

            return style;
        },

        addBoxShadow() {
            if (this.boxShadows.length >= 3) return;
            this.boxShadows.push({ x: 0, y: 4, blur: 6, spread: 0, color: 'rgba(0, 0, 0, 0.2)', inset: false });
            this.emitChange();
        },

        removeBoxShadow(index) {
            this.boxShadows.splice(index, 1);
            this.emitChange();
        },

        applyBoxShadowPreset(value) {
            if (value === 'none') {
                this.boxShadows = [];
            } else {
                // Parse preset into shadow objects
                this.boxShadows = [{ x: 0, y: 4, blur: 6, spread: -1, color: 'rgba(0, 0, 0, 0.1)', inset: false }];
            }
            this.emitChange();
        },

        emitChange() {
            const boxShadowCss = this.boxShadows.length > 0
                ? this.boxShadows.map(s =>
                    `${s.inset ? 'inset ' : ''}${s.x}px ${s.y}px ${s.blur}px ${s.spread}px ${s.color}`
                ).join(', ')
                : 'none';

            const textShadowCss = this.textShadowEnabled
                ? `${this.textShadow.x}px ${this.textShadow.y}px ${this.textShadow.blur}px ${this.textShadow.color}`
                : 'none';

            const value = {
                opacity: this.opacity + '%',
                boxShadow: boxShadowCss,
                textShadow: textShadowCss,
                boxShadows: this.boxShadows,
            };
            this.$wire.updateControlValue('effects', value);
        }
    }
}
</script>
