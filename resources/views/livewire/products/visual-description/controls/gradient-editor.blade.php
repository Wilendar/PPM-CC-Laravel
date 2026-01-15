{{--
    Gradient Editor Control - ETAP_07f_P5 FAZA PP.2
    Kontrolka do edycji gradientow (linear, radial)
    Z podgladem i color stops
--}}
@props([
    'controlId' => 'gradient-editor',
    'value' => [],
    'options' => [],
    'onChange' => null,
])

@php
    $types = $options['types'] ?? ['linear', 'radial'];
    $presets = $options['presets'] ?? [
        'ppm-brand' => [
            'type' => 'linear', 'angle' => 135,
            'stops' => [
                ['color' => '#e0ac7e', 'position' => 0],
                ['color' => '#d1975a', 'position' => 50],
                ['color' => '#c08449', 'position' => 100],
            ],
        ],
        'dark-overlay' => [
            'type' => 'linear', 'angle' => 180,
            'stops' => [
                ['color' => 'rgba(0,0,0,0)', 'position' => 0],
                ['color' => 'rgba(0,0,0,0.7)', 'position' => 100],
            ],
        ],
        'light-fade' => [
            'type' => 'linear', 'angle' => 180,
            'stops' => [
                ['color' => '#ffffff', 'position' => 0],
                ['color' => '#f6f6f6', 'position' => 100],
            ],
        ],
    ];
    $maxStops = $options['maxStops'] ?? 5;
@endphp

<div
    class="uve-control uve-control--gradient-editor"
    x-data="uveGradientEditorControl(@js($value), @js($maxStops))"
    wire:ignore.self
>
    {{-- Gradient Preview --}}
    <div class="uve-gradient-preview" :style="'background: ' + gradientCss"></div>

    {{-- Type Selector --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Typ gradientu</label>
        <div class="uve-btn-group-full">
            @foreach($types as $typeOption)
                <button
                    type="button"
                    @click="type = '{{ $typeOption }}'; emitChange()"
                    class="uve-btn uve-btn-sm"
                    :class="{ 'uve-btn-active': type === '{{ $typeOption }}' }"
                >
                    {{ $typeOption === 'linear' ? 'Liniowy' : 'Radialny' }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Direction/Angle (for linear) --}}
    <div class="uve-control__field" x-show="type === 'linear'">
        <label class="uve-control__label">Kierunek</label>
        <div class="uve-gradient-direction">
            {{-- Direction Grid --}}
            <div class="uve-direction-grid">
                <template x-for="dir in directions" :key="dir.angle">
                    <button
                        type="button"
                        @click="angle = dir.angle; emitChange()"
                        class="uve-direction-btn"
                        :class="{ 'uve-direction-btn--active': angle === dir.angle }"
                        :title="dir.label"
                    >
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24" :style="'transform: rotate(' + dir.angle + 'deg)'">
                            <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8-8-8z"></path>
                        </svg>
                    </button>
                </template>
            </div>

            {{-- Custom Angle --}}
            <div class="uve-angle-input">
                <input
                    type="number"
                    x-model="angle"
                    @input="emitChange()"
                    class="uve-input uve-input--sm"
                    min="0"
                    max="360"
                />
                <span class="uve-angle-unit">deg</span>
            </div>
        </div>
    </div>

    {{-- Color Stops --}}
    <div class="uve-control__field">
        <div class="uve-stops-header">
            <label class="uve-control__label">Kolory</label>
            <button
                type="button"
                @click="addStop()"
                class="uve-btn uve-btn-xs"
                :disabled="stops.length >= maxStops"
                title="Dodaj kolor"
            >
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
            </button>
        </div>

        <div class="uve-stops-list">
            <template x-for="(stop, index) in stops" :key="index">
                <div class="uve-stop-item">
                    <div class="uve-stop-color-wrapper">
                        <input
                            type="color"
                            :value="stop.color.startsWith('#') ? stop.color : '#000000'"
                            @input="stop.color = $event.target.value; emitChange()"
                            class="uve-stop-color-input"
                        />
                        <div class="uve-stop-color-preview" :style="'background-color: ' + stop.color"></div>
                    </div>

                    <input
                        type="number"
                        x-model="stop.position"
                        @input="emitChange()"
                        class="uve-input uve-input--xs"
                        min="0"
                        max="100"
                        placeholder="0"
                    />
                    <span class="uve-stop-unit">%</span>

                    <button
                        type="button"
                        @click="removeStop(index)"
                        class="uve-btn uve-btn-xs uve-btn-danger"
                        :disabled="stops.length <= 2"
                        title="Usun"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </template>
        </div>
    </div>

    {{-- Presets --}}
    <div class="uve-gradient-presets">
        <span class="uve-presets-label">Presety:</span>
        <div class="uve-gradient-preset-btns">
            @foreach($presets as $presetKey => $preset)
                <button
                    type="button"
                    @click="applyPreset(@js($preset))"
                    class="uve-gradient-preset-btn"
                    style="background: {{ $preset['type'] === 'linear'
                        ? 'linear-gradient(' . $preset['angle'] . 'deg, ' . implode(', ', array_map(fn($s) => $s['color'] . ' ' . $s['position'] . '%', $preset['stops'])) . ')'
                        : 'radial-gradient(circle, ' . implode(', ', array_map(fn($s) => $s['color'] . ' ' . $s['position'] . '%', $preset['stops'])) . ')'
                    }}"
                    title="{{ $presetKey }}"
                ></button>
            @endforeach
        </div>
    </div>

    {{-- Clear Button --}}
    <div class="uve-gradient-actions">
        <button
            type="button"
            @click="clearGradient()"
            class="uve-btn uve-btn-sm"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
            Wyczysc
        </button>
    </div>
</div>

<style>
/* Gradient Editor Control Styles */
.uve-control--gradient-editor {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Gradient Preview */
.uve-gradient-preview {
    width: 100%;
    height: 60px;
    border-radius: 0.375rem;
    border: 2px solid #475569;
    background-image:
        linear-gradient(45deg, #334155 25%, transparent 25%),
        linear-gradient(-45deg, #334155 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, #334155 75%),
        linear-gradient(-45deg, transparent 75%, #334155 75%);
    background-size: 10px 10px;
    background-position: 0 0, 0 5px, 5px -5px, -5px 0px;
    position: relative;
}

/* Direction Grid */
.uve-gradient-direction {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.uve-direction-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2px;
}

.uve-direction-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.25rem;
    color: #64748b;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-direction-btn:hover {
    background: #475569;
    color: #e2e8f0;
}

.uve-direction-btn--active {
    background: #e0ac7e;
    border-color: #e0ac7e;
    color: #0f172a;
}

.uve-angle-input {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.uve-angle-input .uve-input--sm {
    width: 60px;
}

.uve-angle-unit {
    font-size: 0.75rem;
    color: #64748b;
}

/* Color Stops */
.uve-stops-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.uve-stops-list {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.uve-stop-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.uve-stop-color-wrapper {
    position: relative;
    width: 28px;
    height: 28px;
    flex-shrink: 0;
}

.uve-stop-color-input {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.uve-stop-color-preview {
    width: 100%;
    height: 100%;
    border-radius: 0.25rem;
    border: 2px solid #475569;
}

.uve-input--xs {
    width: 50px;
    padding: 0.25rem 0.375rem;
    font-size: 0.75rem;
}

.uve-stop-unit {
    font-size: 0.7rem;
    color: #64748b;
}

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

/* Gradient Presets */
.uve-gradient-presets {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    padding-top: 0.5rem;
    border-top: 1px solid #334155;
}

.uve-gradient-preset-btns {
    display: flex;
    gap: 0.375rem;
}

.uve-gradient-preset-btn {
    width: 36px;
    height: 24px;
    border-radius: 0.25rem;
    border: 2px solid #475569;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-gradient-preset-btn:hover {
    border-color: #e0ac7e;
    transform: scale(1.05);
}

/* Actions */
.uve-gradient-actions {
    display: flex;
    justify-content: flex-end;
}
</style>

<script>
function uveGradientEditorControl(initialValue, maxStops) {
    return {
        type: initialValue.type || 'linear',
        angle: initialValue.angle || 180,
        stops: initialValue.stops || [
            { color: '#f6f6f6', position: 0 },
            { color: '#e0ac7e', position: 100 },
        ],
        maxStops: maxStops,

        directions: [
            { angle: 315, label: 'Do gory-lewo' },
            { angle: 0, label: 'Do gory' },
            { angle: 45, label: 'Do gory-prawo' },
            { angle: 270, label: 'Do lewej' },
            { angle: 0, label: 'Srodek' },
            { angle: 90, label: 'Do prawej' },
            { angle: 225, label: 'Do dolu-lewo' },
            { angle: 180, label: 'Do dolu' },
            { angle: 135, label: 'Do dolu-prawo' },
        ],

        get gradientCss() {
            const stopsStr = this.stops
                .sort((a, b) => a.position - b.position)
                .map(s => `${s.color} ${s.position}%`)
                .join(', ');

            if (this.type === 'linear') {
                return `linear-gradient(${this.angle}deg, ${stopsStr})`;
            } else {
                return `radial-gradient(circle, ${stopsStr})`;
            }
        },

        addStop() {
            if (this.stops.length >= this.maxStops) return;

            // Add stop at middle position
            const positions = this.stops.map(s => s.position).sort((a, b) => a - b);
            let newPos = 50;
            if (positions.length >= 2) {
                // Find largest gap
                let maxGap = 0;
                let gapStart = 0;
                for (let i = 0; i < positions.length - 1; i++) {
                    const gap = positions[i + 1] - positions[i];
                    if (gap > maxGap) {
                        maxGap = gap;
                        gapStart = positions[i];
                    }
                }
                newPos = Math.round(gapStart + maxGap / 2);
            }

            this.stops.push({ color: '#888888', position: newPos });
            this.emitChange();
        },

        removeStop(index) {
            if (this.stops.length <= 2) return;
            this.stops.splice(index, 1);
            this.emitChange();
        },

        applyPreset(preset) {
            this.type = preset.type;
            this.angle = preset.angle || 180;
            this.stops = JSON.parse(JSON.stringify(preset.stops));
            this.emitChange();
        },

        clearGradient() {
            this.type = 'linear';
            this.angle = 180;
            this.stops = [
                { color: '#f6f6f6', position: 0 },
                { color: '#e0ac7e', position: 100 },
            ];
            this.emitChange();
        },

        emitChange() {
            const value = {
                type: this.type,
                angle: this.angle,
                stops: this.stops,
                css: this.gradientCss,
            };
            this.$wire.updateControlValue('gradient-editor', value);
        }
    }
}
</script>
