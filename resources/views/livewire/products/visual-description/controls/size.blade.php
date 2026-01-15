{{--
    Size Control - ETAP_07f_P5 FAZA PP.2
    Kontrolka do edycji rozmiarow:
    width, height, min-width, min-height, max-width, max-height
--}}
@props([
    'controlId' => 'size',
    'value' => [],
    'options' => [],
    'onChange' => null,
])

@php
    $widthPresets = $options['widthPresets'] ?? [
        'auto' => 'Auto',
        '100%' => '100%',
        '50%' => '50%',
        'fit-content' => 'Fit',
        'max-content' => 'Max',
    ];
    $maxWidthPresets = [
        'none' => 'Brak',
        '100%' => '100%',
        '1200px' => '1200px',
        '900px' => '900px',
        '600px' => '600px',
    ];
@endphp

<div
    class="uve-control uve-control--size"
    x-data="uveSizeControl(@js($value))"
    wire:ignore.self
>
    {{-- Width --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Szerokosc (width)</label>
        <div class="uve-size-row">
            <input
                type="text"
                x-model="width"
                @input="emitChange()"
                class="uve-input uve-input--sm"
                placeholder="auto"
            />
            <select x-model="widthUnit" @change="emitChange()" class="uve-select uve-select--unit">
                <option value="">-</option>
                <option value="px">px</option>
                <option value="%">%</option>
                <option value="rem">rem</option>
                <option value="vw">vw</option>
            </select>
        </div>
        <div class="uve-size-presets">
            @foreach($widthPresets as $presetVal => $presetLabel)
                <button
                    type="button"
                    @click="setWidth('{{ $presetVal }}')"
                    class="uve-size-preset-btn"
                >
                    {{ $presetLabel }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Height --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Wysokosc (height)</label>
        <div class="uve-size-row">
            <input
                type="text"
                x-model="height"
                @input="emitChange()"
                class="uve-input uve-input--sm"
                placeholder="auto"
            />
            <select x-model="heightUnit" @change="emitChange()" class="uve-select uve-select--unit">
                <option value="">-</option>
                <option value="px">px</option>
                <option value="%">%</option>
                <option value="rem">rem</option>
                <option value="vh">vh</option>
            </select>
        </div>
        <div class="uve-size-presets">
            <button type="button" @click="setHeight('auto')" class="uve-size-preset-btn">Auto</button>
            <button type="button" @click="setHeight('100%')" class="uve-size-preset-btn">100%</button>
            <button type="button" @click="setHeight('100vh')" class="uve-size-preset-btn">100vh</button>
            <button type="button" @click="setHeight('fit-content')" class="uve-size-preset-btn">Fit</button>
        </div>
    </div>

    {{-- Advanced Toggle --}}
    <div class="uve-size-advanced-toggle">
        <button
            type="button"
            @click="showAdvanced = !showAdvanced"
            class="uve-btn uve-btn-sm"
        >
            <svg class="w-4 h-4" :class="{ 'transform rotate-180': showAdvanced }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
            <span x-text="showAdvanced ? 'Ukryj zaawansowane' : 'Pokaz zaawansowane'"></span>
        </button>
    </div>

    {{-- Advanced Options --}}
    <template x-if="showAdvanced">
        <div class="uve-size-advanced">
            {{-- Min Width --}}
            <div class="uve-control__field">
                <label class="uve-control__label">Min szerokosc (min-width)</label>
                <div class="uve-size-row">
                    <input
                        type="text"
                        x-model="minWidth"
                        @input="emitChange()"
                        class="uve-input uve-input--sm"
                        placeholder="0"
                    />
                    <select x-model="minWidthUnit" @change="emitChange()" class="uve-select uve-select--unit">
                        <option value="">-</option>
                        <option value="px">px</option>
                        <option value="%">%</option>
                        <option value="rem">rem</option>
                    </select>
                </div>
            </div>

            {{-- Max Width --}}
            <div class="uve-control__field">
                <label class="uve-control__label">Max szerokosc (max-width)</label>
                <div class="uve-size-row">
                    <input
                        type="text"
                        x-model="maxWidth"
                        @input="emitChange()"
                        class="uve-input uve-input--sm"
                        placeholder="none"
                    />
                    <select x-model="maxWidthUnit" @change="emitChange()" class="uve-select uve-select--unit">
                        <option value="">-</option>
                        <option value="px">px</option>
                        <option value="%">%</option>
                        <option value="rem">rem</option>
                    </select>
                </div>
                <div class="uve-size-presets">
                    @foreach($maxWidthPresets as $presetVal => $presetLabel)
                        <button
                            type="button"
                            @click="setMaxWidth('{{ $presetVal }}')"
                            class="uve-size-preset-btn"
                        >
                            {{ $presetLabel }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Min Height --}}
            <div class="uve-control__field">
                <label class="uve-control__label">Min wysokosc (min-height)</label>
                <div class="uve-size-row">
                    <input
                        type="text"
                        x-model="minHeight"
                        @input="emitChange()"
                        class="uve-input uve-input--sm"
                        placeholder="0"
                    />
                    <select x-model="minHeightUnit" @change="emitChange()" class="uve-select uve-select--unit">
                        <option value="">-</option>
                        <option value="px">px</option>
                        <option value="%">%</option>
                        <option value="vh">vh</option>
                    </select>
                </div>
            </div>

            {{-- Max Height --}}
            <div class="uve-control__field">
                <label class="uve-control__label">Max wysokosc (max-height)</label>
                <div class="uve-size-row">
                    <input
                        type="text"
                        x-model="maxHeight"
                        @input="emitChange()"
                        class="uve-input uve-input--sm"
                        placeholder="none"
                    />
                    <select x-model="maxHeightUnit" @change="emitChange()" class="uve-select uve-select--unit">
                        <option value="">-</option>
                        <option value="px">px</option>
                        <option value="%">%</option>
                        <option value="vh">vh</option>
                    </select>
                </div>
            </div>

            {{-- Aspect Ratio --}}
            <div class="uve-control__field">
                <label class="uve-control__label">Proporcje (aspect-ratio)</label>
                <div class="uve-aspect-presets">
                    <button type="button" @click="setAspectRatio('')" class="uve-aspect-btn" :class="{ 'uve-aspect-btn--active': aspectRatio === '' }">Auto</button>
                    <button type="button" @click="setAspectRatio('1/1')" class="uve-aspect-btn" :class="{ 'uve-aspect-btn--active': aspectRatio === '1/1' }">1:1</button>
                    <button type="button" @click="setAspectRatio('4/3')" class="uve-aspect-btn" :class="{ 'uve-aspect-btn--active': aspectRatio === '4/3' }">4:3</button>
                    <button type="button" @click="setAspectRatio('16/9')" class="uve-aspect-btn" :class="{ 'uve-aspect-btn--active': aspectRatio === '16/9' }">16:9</button>
                    <button type="button" @click="setAspectRatio('21/9')" class="uve-aspect-btn" :class="{ 'uve-aspect-btn--active': aspectRatio === '21/9' }">21:9</button>
                </div>
            </div>

            {{-- Object Fit (for images/videos) --}}
            <div class="uve-control__field">
                <label class="uve-control__label">Dopasowanie (object-fit)</label>
                <div class="uve-btn-group-full">
                    <button type="button" @click="objectFit = 'fill'; emitChange()" class="uve-btn uve-btn-sm" :class="{ 'uve-btn-active': objectFit === 'fill' }">Fill</button>
                    <button type="button" @click="objectFit = 'cover'; emitChange()" class="uve-btn uve-btn-sm" :class="{ 'uve-btn-active': objectFit === 'cover' }">Cover</button>
                    <button type="button" @click="objectFit = 'contain'; emitChange()" class="uve-btn uve-btn-sm" :class="{ 'uve-btn-active': objectFit === 'contain' }">Contain</button>
                    <button type="button" @click="objectFit = 'none'; emitChange()" class="uve-btn uve-btn-sm" :class="{ 'uve-btn-active': objectFit === 'none' }">None</button>
                </div>
            </div>
        </div>
    </template>
</div>

<style>
/* Size Control Styles */
.uve-control--size {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Size Row */
.uve-size-row {
    display: flex;
    gap: 0.375rem;
}

.uve-size-row .uve-input--sm {
    flex: 1;
}

.uve-select--unit {
    width: 60px;
    flex-shrink: 0;
}

/* Size Presets */
.uve-size-presets {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    margin-top: 0.375rem;
}

.uve-size-preset-btn {
    padding: 0.125rem 0.375rem;
    font-size: 0.65rem;
    color: #94a3b8;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-size-preset-btn:hover {
    background: #475569;
    color: #e2e8f0;
}

/* Advanced Toggle */
.uve-size-advanced-toggle {
    padding-top: 0.5rem;
    border-top: 1px solid #334155;
}

.uve-size-advanced-toggle .uve-btn {
    width: 100%;
    justify-content: center;
}

/* Advanced Options */
.uve-size-advanced {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 0.375rem;
}

/* Aspect Ratio Presets */
.uve-aspect-presets {
    display: flex;
    gap: 0.25rem;
}

.uve-aspect-btn {
    flex: 1;
    padding: 0.375rem;
    font-size: 0.7rem;
    font-weight: 500;
    color: #94a3b8;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-aspect-btn:hover {
    background: #475569;
    color: #e2e8f0;
}

.uve-aspect-btn--active {
    background: #e0ac7e;
    border-color: #e0ac7e;
    color: #0f172a;
}
</style>

<script>
function uveSizeControl(initialValue) {
    // Parse initial values
    const parseValue = (val) => {
        if (!val || val === 'auto' || val === 'none') return { value: '', unit: '' };
        const match = val.match(/^([\d.]+)(.*)$/);
        if (match) return { value: match[1], unit: match[2] || 'px' };
        return { value: val, unit: '' };
    };

    const w = parseValue(initialValue.width);
    const h = parseValue(initialValue.height);
    const minW = parseValue(initialValue.minWidth);
    const maxW = parseValue(initialValue.maxWidth);
    const minH = parseValue(initialValue.minHeight);
    const maxH = parseValue(initialValue.maxHeight);

    return {
        width: w.value,
        widthUnit: w.unit,
        height: h.value,
        heightUnit: h.unit,
        minWidth: minW.value,
        minWidthUnit: minW.unit,
        maxWidth: maxW.value,
        maxWidthUnit: maxW.unit,
        minHeight: minH.value,
        minHeightUnit: minH.unit,
        maxHeight: maxH.value,
        maxHeightUnit: maxH.unit,
        aspectRatio: initialValue.aspectRatio || '',
        objectFit: initialValue.objectFit || 'fill',
        showAdvanced: false,

        setWidth(val) {
            if (val === 'auto' || val === 'fit-content' || val === 'max-content') {
                this.width = val;
                this.widthUnit = '';
            } else {
                const parsed = this.parseValue(val);
                this.width = parsed.value;
                this.widthUnit = parsed.unit;
            }
            this.emitChange();
        },

        setHeight(val) {
            if (val === 'auto' || val === 'fit-content') {
                this.height = val;
                this.heightUnit = '';
            } else {
                const parsed = this.parseValue(val);
                this.height = parsed.value;
                this.heightUnit = parsed.unit;
            }
            this.emitChange();
        },

        setMaxWidth(val) {
            if (val === 'none') {
                this.maxWidth = '';
                this.maxWidthUnit = '';
            } else {
                const parsed = this.parseValue(val);
                this.maxWidth = parsed.value;
                this.maxWidthUnit = parsed.unit;
            }
            this.emitChange();
        },

        setAspectRatio(val) {
            this.aspectRatio = val;
            this.emitChange();
        },

        parseValue(val) {
            const match = val.match(/^([\d.]+)(.*)$/);
            if (match) return { value: match[1], unit: match[2] || 'px' };
            return { value: val, unit: '' };
        },

        formatValue(val, unit) {
            if (!val) return '';
            if (val === 'auto' || val === 'none' || val === 'fit-content' || val === 'max-content') return val;
            return val + (unit || 'px');
        },

        emitChange() {
            const value = {
                width: this.formatValue(this.width, this.widthUnit),
                height: this.formatValue(this.height, this.heightUnit),
                minWidth: this.formatValue(this.minWidth, this.minWidthUnit),
                maxWidth: this.formatValue(this.maxWidth, this.maxWidthUnit),
                minHeight: this.formatValue(this.minHeight, this.minHeightUnit),
                maxHeight: this.formatValue(this.maxHeight, this.maxHeightUnit),
                aspectRatio: this.aspectRatio,
                objectFit: this.objectFit,
            };
            this.$wire.updateControlValue('size', value);
        }
    }
}
</script>
