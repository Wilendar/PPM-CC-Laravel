{{--
    Transform Control - ETAP_07f_P5 FAZA PP.2
    Kontrolka do edycji transformacji CSS:
    rotate, scale, translate, transform-origin
--}}
@props([
    'controlId' => 'transform',
    'value' => [],
    'options' => [],
    'onChange' => null,
])

<div
    class="uve-control uve-control--transform"
    x-data="uveTransformControl(@js($value))"
    wire:ignore.self
>
    {{-- Transform Preview --}}
    <div class="uve-transform-preview-container">
        <div class="uve-transform-preview" :style="previewStyle">
            <span>T</span>
        </div>
    </div>

    {{-- Rotate --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Obrot (rotate)</label>
        <div class="uve-transform-slider-row">
            <input
                type="range"
                x-model="rotate"
                @input="emitChange()"
                min="-180"
                max="180"
                step="1"
                class="uve-slider uve-slider--rotate"
            />
            <div class="uve-transform-value">
                <input
                    type="number"
                    x-model="rotate"
                    @input="emitChange()"
                    class="uve-input uve-input--xs"
                    min="-180"
                    max="180"
                />
                <span class="uve-transform-unit">deg</span>
            </div>
        </div>
        <div class="uve-transform-presets">
            <button type="button" @click="rotate = 0; emitChange()" class="uve-transform-preset-btn">0</button>
            <button type="button" @click="rotate = 45; emitChange()" class="uve-transform-preset-btn">45</button>
            <button type="button" @click="rotate = 90; emitChange()" class="uve-transform-preset-btn">90</button>
            <button type="button" @click="rotate = 180; emitChange()" class="uve-transform-preset-btn">180</button>
            <button type="button" @click="rotate = -45; emitChange()" class="uve-transform-preset-btn">-45</button>
            <button type="button" @click="rotate = -90; emitChange()" class="uve-transform-preset-btn">-90</button>
        </div>
    </div>

    {{-- Scale --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Skalowanie (scale)</label>

        {{-- Scale X --}}
        <div class="uve-transform-slider-row">
            <span class="uve-transform-axis">X</span>
            <input
                type="range"
                x-model="scaleX"
                @input="onScaleChange('x')"
                min="0"
                max="2"
                step="0.1"
                class="uve-slider"
            />
            <div class="uve-transform-value">
                <input
                    type="number"
                    x-model="scaleX"
                    @input="onScaleChange('x')"
                    class="uve-input uve-input--xs"
                    step="0.1"
                    min="0"
                    max="3"
                />
            </div>
        </div>

        {{-- Scale Y --}}
        <div class="uve-transform-slider-row">
            <span class="uve-transform-axis">Y</span>
            <input
                type="range"
                x-model="scaleY"
                @input="onScaleChange('y')"
                min="0"
                max="2"
                step="0.1"
                class="uve-slider"
            />
            <div class="uve-transform-value">
                <input
                    type="number"
                    x-model="scaleY"
                    @input="onScaleChange('y')"
                    class="uve-input uve-input--xs"
                    step="0.1"
                    min="0"
                    max="3"
                />
            </div>
        </div>

        {{-- Link Scale --}}
        <label class="uve-toggle-row uve-toggle-row--sm">
            <input
                type="checkbox"
                x-model="scaleLinked"
                class="uve-checkbox"
            />
            <span class="uve-toggle-label">Proporcjonalnie</span>
        </label>
    </div>

    {{-- Translate --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Przesuniecie (translate)</label>

        <div class="uve-translate-row">
            <div class="uve-translate-input">
                <span class="uve-transform-axis">X</span>
                <input
                    type="text"
                    x-model="translateX"
                    @input="emitChange()"
                    class="uve-input uve-input--sm"
                    placeholder="0"
                />
            </div>
            <div class="uve-translate-input">
                <span class="uve-transform-axis">Y</span>
                <input
                    type="text"
                    x-model="translateY"
                    @input="emitChange()"
                    class="uve-input uve-input--sm"
                    placeholder="0"
                />
            </div>
            <select x-model="translateUnit" @change="emitChange()" class="uve-select uve-select--unit">
                <option value="px">px</option>
                <option value="%">%</option>
                <option value="rem">rem</option>
                <option value="em">em</option>
            </select>
        </div>
    </div>

    {{-- Skew --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Pochylenie (skew)</label>

        <div class="uve-translate-row">
            <div class="uve-translate-input">
                <span class="uve-transform-axis">X</span>
                <input
                    type="number"
                    x-model="skewX"
                    @input="emitChange()"
                    class="uve-input uve-input--sm"
                    min="-45"
                    max="45"
                />
            </div>
            <div class="uve-translate-input">
                <span class="uve-transform-axis">Y</span>
                <input
                    type="number"
                    x-model="skewY"
                    @input="emitChange()"
                    class="uve-input uve-input--sm"
                    min="-45"
                    max="45"
                />
            </div>
            <span class="uve-transform-unit">deg</span>
        </div>
    </div>

    {{-- Transform Origin --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Punkt odniesienia (transform-origin)</label>
        <div class="uve-origin-grid">
            <template x-for="origin in origins" :key="origin.value">
                <button
                    type="button"
                    @click="transformOrigin = origin.value; emitChange()"
                    class="uve-origin-btn"
                    :class="{ 'uve-origin-btn--active': transformOrigin === origin.value }"
                    :title="origin.label"
                >
                    <span class="uve-origin-dot"></span>
                </button>
            </template>
        </div>
    </div>

    {{-- Reset Button --}}
    <div class="uve-transform-actions">
        <button
            type="button"
            @click="resetTransform()"
            class="uve-btn uve-btn-sm"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Reset
        </button>
    </div>
</div>

<style>
/* Transform Control Styles */
.uve-control--transform {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Transform Preview */
.uve-transform-preview-container {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 80px;
    background: #1e293b;
    border: 2px dashed #475569;
    border-radius: 0.375rem;
    overflow: visible;
}

.uve-transform-preview {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: #e0ac7e;
    border-radius: 0.25rem;
    color: #0f172a;
    font-weight: 700;
    font-size: 1.25rem;
    transition: transform 0.15s ease;
}

/* Slider Row */
.uve-transform-slider-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.uve-transform-axis {
    width: 16px;
    font-size: 0.7rem;
    font-weight: 600;
    color: #64748b;
    text-align: center;
}

.uve-transform-slider-row .uve-slider {
    flex: 1;
}

.uve-transform-value {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.uve-transform-value .uve-input--xs {
    width: 50px;
}

.uve-transform-unit {
    font-size: 0.7rem;
    color: #64748b;
}

/* Slider for Rotate */
.uve-slider--rotate {
    background: linear-gradient(to right, #3b82f6, #e2e8f0 50%, #ef4444);
}

/* Presets */
.uve-transform-presets {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    margin-top: 0.375rem;
}

.uve-transform-preset-btn {
    padding: 0.125rem 0.375rem;
    font-size: 0.65rem;
    color: #94a3b8;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-transform-preset-btn:hover {
    background: #475569;
    color: #e2e8f0;
}

/* Translate Row */
.uve-translate-row {
    display: flex;
    gap: 0.375rem;
    align-items: center;
}

.uve-translate-input {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    flex: 1;
}

.uve-translate-input .uve-input--sm {
    flex: 1;
}

.uve-select--unit {
    width: 60px;
    flex-shrink: 0;
}

/* Toggle Row Small */
.uve-toggle-row--sm {
    font-size: 0.75rem;
    margin-top: 0.375rem;
}

.uve-toggle-row--sm .uve-checkbox {
    width: 14px;
    height: 14px;
}

/* Origin Grid */
.uve-origin-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2px;
    width: 80px;
}

.uve-origin-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 2px;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-origin-btn:hover {
    background: #475569;
}

.uve-origin-btn--active {
    background: #e0ac7e;
    border-color: #e0ac7e;
}

.uve-origin-dot {
    width: 6px;
    height: 6px;
    background: #94a3b8;
    border-radius: 50%;
}

.uve-origin-btn--active .uve-origin-dot {
    background: #0f172a;
}

/* Actions */
.uve-transform-actions {
    padding-top: 0.5rem;
    border-top: 1px solid #334155;
}

.uve-transform-actions .uve-btn {
    width: 100%;
    justify-content: center;
}
</style>

<script>
function uveTransformControl(initialValue) {
    return {
        rotate: parseInt(initialValue.rotate) || 0,
        scaleX: parseFloat(initialValue.scaleX) || 1,
        scaleY: parseFloat(initialValue.scaleY) || 1,
        scaleLinked: true,
        translateX: initialValue.translateX?.replace(/[^0-9.-]/g, '') || '0',
        translateY: initialValue.translateY?.replace(/[^0-9.-]/g, '') || '0',
        translateUnit: initialValue.translateUnit || 'px',
        skewX: parseInt(initialValue.skewX) || 0,
        skewY: parseInt(initialValue.skewY) || 0,
        transformOrigin: initialValue.transformOrigin || 'center center',

        origins: [
            { value: 'left top', label: 'Lewo gora' },
            { value: 'center top', label: 'Srodek gora' },
            { value: 'right top', label: 'Prawo gora' },
            { value: 'left center', label: 'Lewo srodek' },
            { value: 'center center', label: 'Srodek' },
            { value: 'right center', label: 'Prawo srodek' },
            { value: 'left bottom', label: 'Lewo dol' },
            { value: 'center bottom', label: 'Srodek dol' },
            { value: 'right bottom', label: 'Prawo dol' },
        ],

        get previewStyle() {
            const transforms = [];

            if (this.rotate !== 0) transforms.push(`rotate(${this.rotate}deg)`);
            if (this.scaleX !== 1 || this.scaleY !== 1) transforms.push(`scale(${this.scaleX}, ${this.scaleY})`);
            if (this.translateX !== '0' || this.translateY !== '0') {
                transforms.push(`translate(${this.translateX}${this.translateUnit}, ${this.translateY}${this.translateUnit})`);
            }
            if (this.skewX !== 0 || this.skewY !== 0) transforms.push(`skew(${this.skewX}deg, ${this.skewY}deg)`);

            return `
                transform: ${transforms.length > 0 ? transforms.join(' ') : 'none'};
                transform-origin: ${this.transformOrigin};
            `;
        },

        onScaleChange(axis) {
            if (this.scaleLinked) {
                if (axis === 'x') {
                    this.scaleY = this.scaleX;
                } else {
                    this.scaleX = this.scaleY;
                }
            }
            this.emitChange();
        },

        resetTransform() {
            this.rotate = 0;
            this.scaleX = 1;
            this.scaleY = 1;
            this.translateX = '0';
            this.translateY = '0';
            this.skewX = 0;
            this.skewY = 0;
            this.transformOrigin = 'center center';
            this.emitChange();
        },

        emitChange() {
            const transforms = [];

            if (this.rotate !== 0) transforms.push(`rotate(${this.rotate}deg)`);
            if (this.scaleX !== 1 || this.scaleY !== 1) transforms.push(`scale(${this.scaleX}, ${this.scaleY})`);
            if (this.translateX !== '0' || this.translateY !== '0') {
                transforms.push(`translate(${this.translateX}${this.translateUnit}, ${this.translateY}${this.translateUnit})`);
            }
            if (this.skewX !== 0 || this.skewY !== 0) transforms.push(`skew(${this.skewX}deg, ${this.skewY}deg)`);

            const value = {
                transform: transforms.length > 0 ? transforms.join(' ') : 'none',
                transformOrigin: this.transformOrigin,
                rotate: this.rotate,
                scaleX: this.scaleX,
                scaleY: this.scaleY,
                translateX: this.translateX,
                translateY: this.translateY,
                translateUnit: this.translateUnit,
                skewX: this.skewX,
                skewY: this.skewY,
            };
            this.$wire.updateControlValue('transform', value);
        }
    }
}
</script>
