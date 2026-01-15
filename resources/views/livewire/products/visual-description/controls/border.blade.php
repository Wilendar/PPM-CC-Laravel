{{--
    Border Control - ETAP_07f_P5 FAZA PP.2
    Kontrolka do edycji border-width, border-style, border-color, border-radius
    Z wsarciem dla pojedynczych stron i linked mode
--}}
@props([
    'controlId' => 'border',
    'value' => [],
    'options' => [],
    'onChange' => null,
])

@php
    $styles = $options['styles'] ?? [
        'none' => 'Brak',
        'solid' => 'Ciagla',
        'dashed' => 'Kreskowana',
        'dotted' => 'Kropkowana',
        'double' => 'Podwojna',
    ];
    $units = $options['units'] ?? ['px', 'rem', 'em'];
    $radiusPresets = $options['radiusPresets'] ?? [
        '0' => 'Brak',
        '0.25rem' => 'XS',
        '0.5rem' => 'S',
        '0.75rem' => 'M',
        '1rem' => 'L',
        '50%' => 'Okrag',
    ];
@endphp

<div
    class="uve-control uve-control--border"
    x-data="uveBorderControl(@js($value))"
    wire:ignore.self
>
    {{-- Border Preview --}}
    <div class="uve-border-preview" :style="previewStyle">
        <span class="uve-border-preview-text">Border</span>
    </div>

    {{-- Border Width --}}
    <div class="uve-control__field">
        <div class="uve-border-header">
            <label class="uve-control__label">Szerokosc obramowania</label>
            <button
                type="button"
                @click="toggleLinkedWidth()"
                class="uve-link-btn"
                :class="{ 'uve-link-btn--active': widthLinked }"
                title="Polacz wszystkie strony"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="widthLinked" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                    <path x-show="!widthLinked" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1M6 18L18 6"></path>
                </svg>
            </button>
        </div>

        <template x-if="widthLinked">
            <div class="uve-border-single">
                <input
                    type="text"
                    x-model="width.all"
                    @input="onWidthAllChange()"
                    class="uve-input uve-input--sm"
                    placeholder="1px"
                />
            </div>
        </template>

        <template x-if="!widthLinked">
            <div class="uve-border-sides">
                <div class="uve-border-side">
                    <span class="uve-border-side-label">T</span>
                    <input type="text" x-model="width.top" @input="emitChange()" class="uve-input uve-input--xs" placeholder="0" />
                </div>
                <div class="uve-border-side">
                    <span class="uve-border-side-label">R</span>
                    <input type="text" x-model="width.right" @input="emitChange()" class="uve-input uve-input--xs" placeholder="0" />
                </div>
                <div class="uve-border-side">
                    <span class="uve-border-side-label">B</span>
                    <input type="text" x-model="width.bottom" @input="emitChange()" class="uve-input uve-input--xs" placeholder="0" />
                </div>
                <div class="uve-border-side">
                    <span class="uve-border-side-label">L</span>
                    <input type="text" x-model="width.left" @input="emitChange()" class="uve-input uve-input--xs" placeholder="0" />
                </div>
            </div>
        </template>
    </div>

    {{-- Border Style --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Styl obramowania</label>
        <div class="uve-border-styles">
            @foreach($styles as $styleVal => $styleLabel)
                <button
                    type="button"
                    @click="borderStyle = '{{ $styleVal }}'; emitChange()"
                    class="uve-border-style-btn"
                    :class="{ 'uve-border-style-btn--active': borderStyle === '{{ $styleVal }}' }"
                    title="{{ $styleLabel }}"
                >
                    <div class="uve-border-style-preview uve-border-style-preview--{{ $styleVal }}"></div>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Border Color --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Kolor obramowania</label>
        <div class="uve-color-row">
            <div class="uve-color-preview-wrapper">
                <input
                    type="color"
                    x-model="borderColor"
                    @input="emitChange()"
                    class="uve-color-native-input"
                />
                <div class="uve-color-preview" :style="'background-color: ' + borderColor"></div>
            </div>
            <input
                type="text"
                x-model="borderColor"
                @input="emitChange()"
                class="uve-input uve-input--color-hex"
                placeholder="#000000"
            />
        </div>
    </div>

    {{-- Border Radius --}}
    <div class="uve-control__field">
        <div class="uve-border-header">
            <label class="uve-control__label">Zaokraglenie (border-radius)</label>
            <button
                type="button"
                @click="toggleLinkedRadius()"
                class="uve-link-btn"
                :class="{ 'uve-link-btn--active': radiusLinked }"
                title="Polacz wszystkie rogi"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="radiusLinked" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                    <path x-show="!radiusLinked" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1M6 18L18 6"></path>
                </svg>
            </button>
        </div>

        {{-- Radius Presets --}}
        <div class="uve-radius-presets">
            @foreach($radiusPresets as $presetVal => $presetLabel)
                <button
                    type="button"
                    @click="setRadiusPreset('{{ $presetVal }}')"
                    class="uve-radius-preset-btn"
                    :class="{ 'uve-radius-preset-btn--active': radius.all === '{{ $presetVal }}' }"
                >
                    {{ $presetLabel }}
                </button>
            @endforeach
        </div>

        <template x-if="radiusLinked">
            <div class="uve-border-single">
                <input
                    type="text"
                    x-model="radius.all"
                    @input="onRadiusAllChange()"
                    class="uve-input uve-input--sm"
                    placeholder="0.5rem"
                />
            </div>
        </template>

        <template x-if="!radiusLinked">
            <div class="uve-radius-corners">
                <div class="uve-radius-corner uve-radius-corner--tl">
                    <input type="text" x-model="radius.topLeft" @input="emitChange()" class="uve-input uve-input--xs" placeholder="0" />
                </div>
                <div class="uve-radius-corner uve-radius-corner--tr">
                    <input type="text" x-model="radius.topRight" @input="emitChange()" class="uve-input uve-input--xs" placeholder="0" />
                </div>
                <div class="uve-radius-corner uve-radius-corner--bl">
                    <input type="text" x-model="radius.bottomLeft" @input="emitChange()" class="uve-input uve-input--xs" placeholder="0" />
                </div>
                <div class="uve-radius-corner uve-radius-corner--br">
                    <input type="text" x-model="radius.bottomRight" @input="emitChange()" class="uve-input uve-input--xs" placeholder="0" />
                </div>
                <div class="uve-radius-center"></div>
            </div>
        </template>
    </div>
</div>

<style>
/* Border Control Styles */
.uve-control--border {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Border Preview */
.uve-border-preview {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 50px;
    background: #1e293b;
    transition: all 0.15s;
}

.uve-border-preview-text {
    font-size: 0.75rem;
    color: #64748b;
}

/* Border Header */
.uve-border-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Border Sides */
.uve-border-single {
    margin-top: 0.25rem;
}

.uve-border-sides {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.375rem;
    margin-top: 0.25rem;
}

.uve-border-side {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.125rem;
}

.uve-border-side-label {
    font-size: 0.65rem;
    font-weight: 600;
    color: #64748b;
}

/* Border Styles */
.uve-border-styles {
    display: flex;
    gap: 0.375rem;
}

.uve-border-style-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-border-style-btn:hover {
    background: #475569;
}

.uve-border-style-btn--active {
    background: rgba(224, 172, 126, 0.2);
    border-color: #e0ac7e;
}

.uve-border-style-preview {
    width: 100%;
    height: 4px;
}

.uve-border-style-preview--none {
    background: transparent;
    border: 1px dashed #475569;
}

.uve-border-style-preview--solid {
    background: #e0ac7e;
}

.uve-border-style-preview--dashed {
    background: repeating-linear-gradient(90deg, #e0ac7e 0, #e0ac7e 4px, transparent 4px, transparent 8px);
}

.uve-border-style-preview--dotted {
    background: repeating-linear-gradient(90deg, #e0ac7e 0, #e0ac7e 2px, transparent 2px, transparent 6px);
}

.uve-border-style-preview--double {
    border-top: 2px solid #e0ac7e;
    border-bottom: 2px solid #e0ac7e;
    height: 6px;
    background: transparent;
}

/* Color Row */
.uve-color-row {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.uve-color-preview-wrapper {
    position: relative;
    width: 36px;
    height: 36px;
    flex-shrink: 0;
}

.uve-color-native-input {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.uve-color-preview {
    width: 100%;
    height: 100%;
    border-radius: 0.375rem;
    border: 2px solid #475569;
}

.uve-input--color-hex {
    flex: 1;
    font-family: monospace;
    text-transform: uppercase;
}

/* Radius Presets */
.uve-radius-presets {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    margin-bottom: 0.375rem;
}

.uve-radius-preset-btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.7rem;
    color: #94a3b8;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-radius-preset-btn:hover {
    background: #475569;
    color: #e2e8f0;
}

.uve-radius-preset-btn--active {
    background: #e0ac7e;
    border-color: #e0ac7e;
    color: #0f172a;
}

/* Radius Corners */
.uve-radius-corners {
    position: relative;
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-rows: 1fr 1fr;
    gap: 0.5rem;
    padding: 0.75rem;
    margin-top: 0.25rem;
}

.uve-radius-corner {
    display: flex;
}

.uve-radius-corner--tl { justify-content: flex-start; }
.uve-radius-corner--tr { justify-content: flex-end; }
.uve-radius-corner--bl { justify-content: flex-start; }
.uve-radius-corner--br { justify-content: flex-end; }

.uve-radius-center {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 40px;
    height: 40px;
    background: #334155;
    border: 2px solid #475569;
    border-radius: 0.375rem;
}

.uve-input--xs {
    width: 50px;
    padding: 0.25rem 0.375rem;
    font-size: 0.75rem;
}
</style>

<script>
function uveBorderControl(initialValue) {
    return {
        width: {
            all: initialValue.width || '',
            top: initialValue.borderTopWidth || '',
            right: initialValue.borderRightWidth || '',
            bottom: initialValue.borderBottomWidth || '',
            left: initialValue.borderLeftWidth || '',
        },
        widthLinked: true,
        borderStyle: initialValue.style || 'solid',
        borderColor: initialValue.color || '#475569',
        radius: {
            all: initialValue.radius || '',
            topLeft: initialValue.borderTopLeftRadius || '',
            topRight: initialValue.borderTopRightRadius || '',
            bottomLeft: initialValue.borderBottomLeftRadius || '',
            bottomRight: initialValue.borderBottomRightRadius || '',
        },
        radiusLinked: true,

        get previewStyle() {
            const w = this.widthLinked ? this.width.all : `${this.width.top || 0} ${this.width.right || 0} ${this.width.bottom || 0} ${this.width.left || 0}`;
            const r = this.radiusLinked ? this.radius.all : `${this.radius.topLeft || 0} ${this.radius.topRight || 0} ${this.radius.bottomRight || 0} ${this.radius.bottomLeft || 0}`;

            return `
                border-width: ${w || '1px'};
                border-style: ${this.borderStyle};
                border-color: ${this.borderColor};
                border-radius: ${r || '0'};
            `;
        },

        toggleLinkedWidth() {
            this.widthLinked = !this.widthLinked;
            if (this.widthLinked && this.width.top) {
                this.width.all = this.width.top;
            }
        },

        toggleLinkedRadius() {
            this.radiusLinked = !this.radiusLinked;
            if (this.radiusLinked && this.radius.topLeft) {
                this.radius.all = this.radius.topLeft;
            }
        },

        onWidthAllChange() {
            this.width.top = this.width.all;
            this.width.right = this.width.all;
            this.width.bottom = this.width.all;
            this.width.left = this.width.all;
            this.emitChange();
        },

        onRadiusAllChange() {
            this.radius.topLeft = this.radius.all;
            this.radius.topRight = this.radius.all;
            this.radius.bottomLeft = this.radius.all;
            this.radius.bottomRight = this.radius.all;
            this.emitChange();
        },

        setRadiusPreset(value) {
            this.radius.all = value;
            this.radiusLinked = true;
            this.onRadiusAllChange();
        },

        emitChange() {
            const value = {
                width: this.widthLinked ? this.width.all : null,
                borderTopWidth: !this.widthLinked ? this.width.top : null,
                borderRightWidth: !this.widthLinked ? this.width.right : null,
                borderBottomWidth: !this.widthLinked ? this.width.bottom : null,
                borderLeftWidth: !this.widthLinked ? this.width.left : null,
                style: this.borderStyle,
                color: this.borderColor,
                radius: this.radiusLinked ? this.radius.all : null,
                borderTopLeftRadius: !this.radiusLinked ? this.radius.topLeft : null,
                borderTopRightRadius: !this.radiusLinked ? this.radius.topRight : null,
                borderBottomLeftRadius: !this.radiusLinked ? this.radius.bottomLeft : null,
                borderBottomRightRadius: !this.radiusLinked ? this.radius.bottomRight : null,
            };
            this.$wire.updateControlValue('border', value);
        }
    }
}
</script>
