{{--
    Transition Control - ETAP_07f_P5 FAZA PP.4
    Kontrolka do ustawien CSS transitions
    Duration, timing-function, delay, properties
--}}
@props([
    'controlId' => 'transition',
    'value' => [],
    'options' => [],
    'onChange' => null,
])

@php
    $timingFunctions = $options['timingFunctions'] ?? [
        'ease' => 'Ease',
        'linear' => 'Linear',
        'ease-in' => 'Ease In',
        'ease-out' => 'Ease Out',
        'ease-in-out' => 'Ease In Out',
        'cubic-bezier' => 'Custom Bezier',
    ];

    $properties = $options['properties'] ?? [
        'all' => 'Wszystkie',
        'opacity' => 'Opacity',
        'transform' => 'Transform',
        'background' => 'Background',
        'background-color' => 'Background Color',
        'color' => 'Color',
        'border' => 'Border',
        'box-shadow' => 'Box Shadow',
        'width' => 'Width',
        'height' => 'Height',
        'padding' => 'Padding',
        'margin' => 'Margin',
    ];

    $presets = [
        'instant' => ['duration' => 0, 'timing' => 'linear', 'delay' => 0],
        'fast' => ['duration' => 150, 'timing' => 'ease', 'delay' => 0],
        'normal' => ['duration' => 300, 'timing' => 'ease', 'delay' => 0],
        'slow' => ['duration' => 500, 'timing' => 'ease-in-out', 'delay' => 0],
        'smooth' => ['duration' => 400, 'timing' => 'cubic-bezier(0.4, 0, 0.2, 1)', 'delay' => 0],
        'bounce' => ['duration' => 500, 'timing' => 'cubic-bezier(0.68, -0.55, 0.265, 1.55)', 'delay' => 0],
    ];
@endphp

<div
    class="uve-control uve-control--transition"
    x-data="uveTransitionControl(@js($value), @js($timingFunctions), @js($properties))"
    wire:ignore.self
>
    {{-- Quick Presets --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Preset</label>
        <div class="uve-transition-presets">
            @foreach($presets as $presetName => $preset)
                <button
                    type="button"
                    @click="applyPreset(@js($preset))"
                    class="uve-transition-preset-btn"
                    :class="{ 'uve-transition-preset-btn--active': isPresetActive(@js($preset)) }"
                >
                    {{ ucfirst($presetName) }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Duration --}}
    <div class="uve-control__field">
        <label class="uve-control__label">
            Czas trwania
            <span class="uve-value-badge" x-text="duration + 'ms'"></span>
        </label>
        <div class="uve-transition-slider-row">
            <input
                type="range"
                x-model.number="duration"
                @input="emitChange()"
                min="0"
                max="2000"
                step="50"
                class="uve-slider uve-slider--full"
            />
            <input
                type="number"
                x-model.number="duration"
                @input="emitChange()"
                min="0"
                max="5000"
                step="10"
                class="uve-input uve-input--number"
            />
        </div>
        <div class="uve-slider-markers">
            <span>0</span>
            <span>500</span>
            <span>1000</span>
            <span>1500</span>
            <span>2000</span>
        </div>
    </div>

    {{-- Timing Function --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Funkcja czasowa</label>
        <select x-model="timing" @change="onTimingChange()" class="uve-select">
            @foreach($timingFunctions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>

        {{-- Cubic Bezier Editor --}}
        <div x-show="timing === 'cubic-bezier'" x-collapse class="uve-bezier-editor">
            <div class="uve-bezier-preview">
                <canvas
                    x-ref="bezierCanvas"
                    width="120"
                    height="120"
                    class="uve-bezier-canvas"
                ></canvas>
            </div>
            <div class="uve-bezier-inputs">
                <div class="uve-bezier-input-group">
                    <label>P1</label>
                    <input type="number" x-model.number="bezier.x1" @input="updateBezier()" min="0" max="1" step="0.1" class="uve-input uve-input--xs" />
                    <input type="number" x-model.number="bezier.y1" @input="updateBezier()" min="-1" max="2" step="0.1" class="uve-input uve-input--xs" />
                </div>
                <div class="uve-bezier-input-group">
                    <label>P2</label>
                    <input type="number" x-model.number="bezier.x2" @input="updateBezier()" min="0" max="1" step="0.1" class="uve-input uve-input--xs" />
                    <input type="number" x-model.number="bezier.y2" @input="updateBezier()" min="-1" max="2" step="0.1" class="uve-input uve-input--xs" />
                </div>
            </div>
            <div class="uve-bezier-common">
                <span class="uve-bezier-common-label">Typowe:</span>
                <button type="button" @click="setBezier(0.4, 0, 0.2, 1)" class="uve-bezier-common-btn">Material</button>
                <button type="button" @click="setBezier(0.68, -0.55, 0.265, 1.55)" class="uve-bezier-common-btn">Bounce</button>
                <button type="button" @click="setBezier(0.25, 0.1, 0.25, 1)" class="uve-bezier-common-btn">Default</button>
            </div>
        </div>
    </div>

    {{-- Delay --}}
    <div class="uve-control__field">
        <label class="uve-control__label">
            Opoznienie
            <span class="uve-value-badge" x-text="delay + 'ms'"></span>
        </label>
        <div class="uve-transition-slider-row">
            <input
                type="range"
                x-model.number="delay"
                @input="emitChange()"
                min="0"
                max="1000"
                step="25"
                class="uve-slider uve-slider--full"
            />
            <input
                type="number"
                x-model.number="delay"
                @input="emitChange()"
                min="0"
                max="2000"
                step="10"
                class="uve-input uve-input--number"
            />
        </div>
    </div>

    {{-- Properties --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Wlasciwosci</label>
        <div class="uve-transition-properties">
            @foreach($properties as $propValue => $propLabel)
                <label class="uve-transition-property-item">
                    <input
                        type="checkbox"
                        :checked="selectedProperties.includes('{{ $propValue }}')"
                        @change="toggleProperty('{{ $propValue }}')"
                        class="uve-checkbox-sm"
                    />
                    <span>{{ $propLabel }}</span>
                </label>
            @endforeach
        </div>
    </div>

    {{-- Preview --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Podglad</label>
        <div class="uve-transition-preview-wrapper">
            <div
                class="uve-transition-preview-box"
                :style="getPreviewStyle()"
                @mouseenter="previewHover = true"
                @mouseleave="previewHover = false"
                x-ref="previewBox"
            >
                <span class="uve-transition-preview-text">Hover me</span>
            </div>
            <p class="uve-control__hint-sm">Najezdz kursorem aby zobaczyc efekt przejscia</p>
        </div>
    </div>

    {{-- Generated CSS --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Wygenerowany CSS</label>
        <div class="uve-transition-css-output">
            <code x-text="getTransitionCss()"></code>
            <button
                type="button"
                @click="copyCss()"
                class="uve-transition-copy-btn"
                title="Kopiuj CSS"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
            </button>
        </div>
    </div>
</div>

<style>
/* Transition Control Styles */
.uve-control--transition {
    display: flex;
    flex-direction: column;
    gap: 0.875rem;
}

/* Presets */
.uve-transition-presets {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
}

.uve-transition-preset-btn {
    padding: 0.25rem 0.625rem;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.25rem;
    color: #94a3b8;
    font-size: 0.7rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-transition-preset-btn:hover {
    background: #475569;
    color: #e2e8f0;
}

.uve-transition-preset-btn--active {
    background: #e0ac7e;
    border-color: #e0ac7e;
    color: #0f172a;
}

/* Value Badge */
.uve-value-badge {
    float: right;
    padding: 0.125rem 0.375rem;
    background: #334155;
    color: #e0ac7e;
    font-size: 0.65rem;
    font-family: monospace;
    border-radius: 0.25rem;
}

/* Slider Row */
.uve-transition-slider-row {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.uve-slider--full {
    flex: 1;
    height: 6px;
    appearance: none;
    background: #334155;
    border-radius: 3px;
    cursor: pointer;
}

.uve-slider--full::-webkit-slider-thumb {
    appearance: none;
    width: 16px;
    height: 16px;
    background: #e0ac7e;
    border: 2px solid #0f172a;
    border-radius: 50%;
    cursor: pointer;
}

.uve-slider--full::-moz-range-thumb {
    width: 16px;
    height: 16px;
    background: #e0ac7e;
    border: 2px solid #0f172a;
    border-radius: 50%;
    cursor: pointer;
}

.uve-input--number {
    width: 70px;
    text-align: center;
}

/* Slider Markers */
.uve-slider-markers {
    display: flex;
    justify-content: space-between;
    padding: 0 8px;
    margin-top: 0.25rem;
}

.uve-slider-markers span {
    font-size: 0.6rem;
    color: #64748b;
}

/* Bezier Editor */
.uve-bezier-editor {
    margin-top: 0.75rem;
    padding: 0.75rem;
    background: #1e293b;
    border-radius: 0.375rem;
    border: 1px solid #334155;
}

.uve-bezier-preview {
    display: flex;
    justify-content: center;
    margin-bottom: 0.75rem;
}

.uve-bezier-canvas {
    background: #0f172a;
    border-radius: 0.25rem;
    border: 1px solid #334155;
}

.uve-bezier-inputs {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-bottom: 0.75rem;
}

.uve-bezier-input-group {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.uve-bezier-input-group label {
    font-size: 0.7rem;
    color: #64748b;
    width: 20px;
}

.uve-input--xs {
    width: 50px;
    padding: 0.25rem;
    font-size: 0.7rem;
}

.uve-bezier-common {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    flex-wrap: wrap;
}

.uve-bezier-common-label {
    font-size: 0.7rem;
    color: #64748b;
}

.uve-bezier-common-btn {
    padding: 0.125rem 0.375rem;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.25rem;
    color: #94a3b8;
    font-size: 0.65rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-bezier-common-btn:hover {
    background: #475569;
    color: #e2e8f0;
}

/* Properties */
.uve-transition-properties {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.25rem 0.5rem;
}

.uve-transition-property-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0;
    cursor: pointer;
}

.uve-transition-property-item span {
    font-size: 0.75rem;
    color: #94a3b8;
}

.uve-checkbox-sm {
    width: 0.875rem;
    height: 0.875rem;
    accent-color: #e0ac7e;
}

/* Preview */
.uve-transition-preview-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.uve-transition-preview-box {
    width: 100%;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #334155;
    border-radius: 0.5rem;
    cursor: pointer;
}

.uve-transition-preview-text {
    font-size: 0.8rem;
    color: #94a3b8;
    pointer-events: none;
}

/* CSS Output */
.uve-transition-css-output {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 0.375rem;
}

.uve-transition-css-output code {
    flex: 1;
    font-size: 0.7rem;
    font-family: monospace;
    color: #10b981;
    word-break: break-all;
}

.uve-transition-copy-btn {
    padding: 0.25rem;
    background: transparent;
    border: none;
    color: #64748b;
    cursor: pointer;
    border-radius: 0.25rem;
    transition: all 0.15s;
}

.uve-transition-copy-btn:hover {
    background: #334155;
    color: #e2e8f0;
}
</style>

<script>
function uveTransitionControl(initialValue, timingFunctions, properties) {
    return {
        duration: initialValue.duration || 300,
        timing: initialValue.timing || 'ease',
        delay: initialValue.delay || 0,
        selectedProperties: initialValue.properties || ['all'],
        bezier: {
            x1: 0.4,
            y1: 0,
            x2: 0.2,
            y2: 1
        },
        previewHover: false,

        init() {
            // Parse bezier if timing is cubic-bezier
            if (this.timing.startsWith('cubic-bezier')) {
                const match = this.timing.match(/cubic-bezier\(([\d.-]+),\s*([\d.-]+),\s*([\d.-]+),\s*([\d.-]+)\)/);
                if (match) {
                    this.bezier = {
                        x1: parseFloat(match[1]),
                        y1: parseFloat(match[2]),
                        x2: parseFloat(match[3]),
                        y2: parseFloat(match[4])
                    };
                }
            }

            // Draw bezier curve after init
            this.$nextTick(() => this.drawBezierCurve());
        },

        applyPreset(preset) {
            this.duration = preset.duration;
            this.delay = preset.delay;
            if (preset.timing.startsWith('cubic-bezier')) {
                this.timing = 'cubic-bezier';
                const match = preset.timing.match(/cubic-bezier\(([\d.-]+),\s*([\d.-]+),\s*([\d.-]+),\s*([\d.-]+)\)/);
                if (match) {
                    this.bezier = {
                        x1: parseFloat(match[1]),
                        y1: parseFloat(match[2]),
                        x2: parseFloat(match[3]),
                        y2: parseFloat(match[4])
                    };
                }
            } else {
                this.timing = preset.timing;
            }
            this.emitChange();
        },

        isPresetActive(preset) {
            return this.duration === preset.duration &&
                   this.delay === preset.delay &&
                   this.getTimingValue() === (preset.timing.startsWith('cubic-bezier') ? preset.timing : preset.timing);
        },

        onTimingChange() {
            if (this.timing === 'cubic-bezier') {
                this.$nextTick(() => this.drawBezierCurve());
            }
            this.emitChange();
        },

        updateBezier() {
            this.drawBezierCurve();
            this.emitChange();
        },

        setBezier(x1, y1, x2, y2) {
            this.bezier = { x1, y1, x2, y2 };
            this.drawBezierCurve();
            this.emitChange();
        },

        drawBezierCurve() {
            const canvas = this.$refs.bezierCanvas;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            const w = canvas.width;
            const h = canvas.height;
            const padding = 10;
            const size = w - padding * 2;

            ctx.clearRect(0, 0, w, h);

            // Grid
            ctx.strokeStyle = '#334155';
            ctx.lineWidth = 1;
            ctx.beginPath();
            for (let i = 0; i <= 4; i++) {
                const x = padding + (size / 4) * i;
                const y = padding + (size / 4) * i;
                ctx.moveTo(x, padding);
                ctx.lineTo(x, h - padding);
                ctx.moveTo(padding, y);
                ctx.lineTo(w - padding, y);
            }
            ctx.stroke();

            // Control points and lines
            const p0 = { x: padding, y: h - padding };
            const p1 = { x: padding + this.bezier.x1 * size, y: h - padding - this.bezier.y1 * size };
            const p2 = { x: padding + this.bezier.x2 * size, y: h - padding - this.bezier.y2 * size };
            const p3 = { x: w - padding, y: padding };

            // Control lines
            ctx.strokeStyle = '#475569';
            ctx.lineWidth = 1;
            ctx.setLineDash([3, 3]);
            ctx.beginPath();
            ctx.moveTo(p0.x, p0.y);
            ctx.lineTo(p1.x, p1.y);
            ctx.moveTo(p3.x, p3.y);
            ctx.lineTo(p2.x, p2.y);
            ctx.stroke();
            ctx.setLineDash([]);

            // Bezier curve
            ctx.strokeStyle = '#e0ac7e';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(p0.x, p0.y);
            ctx.bezierCurveTo(p1.x, p1.y, p2.x, p2.y, p3.x, p3.y);
            ctx.stroke();

            // Control points
            ctx.fillStyle = '#3b82f6';
            ctx.beginPath();
            ctx.arc(p1.x, p1.y, 5, 0, Math.PI * 2);
            ctx.fill();
            ctx.beginPath();
            ctx.arc(p2.x, p2.y, 5, 0, Math.PI * 2);
            ctx.fill();
        },

        toggleProperty(prop) {
            if (prop === 'all') {
                this.selectedProperties = ['all'];
            } else {
                // Remove 'all' if selecting specific property
                this.selectedProperties = this.selectedProperties.filter(p => p !== 'all');

                if (this.selectedProperties.includes(prop)) {
                    this.selectedProperties = this.selectedProperties.filter(p => p !== prop);
                } else {
                    this.selectedProperties.push(prop);
                }

                // If no properties selected, default to 'all'
                if (this.selectedProperties.length === 0) {
                    this.selectedProperties = ['all'];
                }
            }
            this.emitChange();
        },

        getTimingValue() {
            if (this.timing === 'cubic-bezier') {
                return `cubic-bezier(${this.bezier.x1}, ${this.bezier.y1}, ${this.bezier.x2}, ${this.bezier.y2})`;
            }
            return this.timing;
        },

        getTransitionCss() {
            const props = this.selectedProperties.join(', ');
            const timing = this.getTimingValue();
            return `transition: ${props} ${this.duration}ms ${timing} ${this.delay}ms;`;
        },

        getPreviewStyle() {
            const timing = this.getTimingValue();
            const props = this.selectedProperties.join(', ');
            let style = `transition: ${props} ${this.duration}ms ${timing} ${this.delay}ms;`;

            if (this.previewHover) {
                style += ' transform: scale(1.1); background: #e0ac7e;';
            }

            return style;
        },

        copyCss() {
            navigator.clipboard.writeText(this.getTransitionCss());
            this.$wire.dispatch('notify', { type: 'success', message: 'CSS skopiowany' });
        },

        emitChange() {
            const value = {
                duration: this.duration,
                timing: this.getTimingValue(),
                delay: this.delay,
                properties: this.selectedProperties
            };
            this.$wire.call('updateControlValue', 'transition', value);
        }
    }
}
</script>
