{{--
    Layout Flex Control - ETAP_07f_P5 FAZA PP.2
    Kontrolka do edycji Flexbox layout
    flex-direction, flex-wrap, justify-content, align-items, gap
--}}
@props([
    'controlId' => 'layout-flex',
    'value' => [],
    'options' => [],
    'onChange' => null,
])

@php
    $directions = $options['directions'] ?? [
        'row' => 'Wiersz',
        'row-reverse' => 'Wiersz (odwr.)',
        'column' => 'Kolumna',
        'column-reverse' => 'Kolumna (odwr.)',
    ];
    $wraps = $options['wraps'] ?? [
        'nowrap' => 'Brak',
        'wrap' => 'Zawijaj',
        'wrap-reverse' => 'Odwr.',
    ];
    $justifies = $options['justifies'] ?? [
        'flex-start' => 'Start',
        'flex-end' => 'End',
        'center' => 'Srodek',
        'space-between' => 'Between',
        'space-around' => 'Around',
        'space-evenly' => 'Evenly',
    ];
    $aligns = $options['aligns'] ?? [
        'flex-start' => 'Start',
        'flex-end' => 'End',
        'center' => 'Srodek',
        'stretch' => 'Rozciagnij',
        'baseline' => 'Baseline',
    ];
@endphp

<div
    class="uve-control uve-control--layout-flex"
    x-data="uveLayoutFlexControl(@js($value))"
    wire:ignore.self
>
    {{-- Visual Flexbox Preview --}}
    <div class="uve-flex-preview" :style="previewStyle">
        <div class="uve-flex-item"></div>
        <div class="uve-flex-item"></div>
        <div class="uve-flex-item"></div>
    </div>

    {{-- Enable Flex Toggle --}}
    <div class="uve-control__field">
        <label class="uve-toggle-row">
            <input
                type="checkbox"
                x-model="enabled"
                @change="emitChange()"
                class="uve-checkbox"
            />
            <span class="uve-toggle-label">Wlacz Flexbox</span>
        </label>
    </div>

    <template x-if="enabled">
        <div class="uve-flex-options">
            {{-- Direction --}}
            <div class="uve-control__field">
                <label class="uve-control__label">Kierunek</label>
                <div class="uve-flex-direction-grid">
                    @foreach($directions as $dirVal => $dirLabel)
                        <button
                            type="button"
                            @click="flexDirection = '{{ $dirVal }}'; emitChange()"
                            class="uve-flex-dir-btn"
                            :class="{ 'uve-flex-dir-btn--active': flexDirection === '{{ $dirVal }}' }"
                            title="{{ $dirLabel }}"
                        >
                            @if($dirVal === 'row')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                </svg>
                            @elseif($dirVal === 'row-reverse')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                            @elseif($dirVal === 'column')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                                </svg>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Wrap --}}
            <div class="uve-control__field">
                <label class="uve-control__label">Zawijanie</label>
                <div class="uve-btn-group-full">
                    @foreach($wraps as $wrapVal => $wrapLabel)
                        <button
                            type="button"
                            @click="flexWrap = '{{ $wrapVal }}'; emitChange()"
                            class="uve-btn uve-btn-sm"
                            :class="{ 'uve-btn-active': flexWrap === '{{ $wrapVal }}' }"
                        >
                            {{ $wrapLabel }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Justify Content --}}
            <div class="uve-control__field">
                <label class="uve-control__label">Wyrownanie poziome (justify)</label>
                <div class="uve-justify-grid">
                    @foreach($justifies as $justVal => $justLabel)
                        <button
                            type="button"
                            @click="justifyContent = '{{ $justVal }}'; emitChange()"
                            class="uve-justify-btn"
                            :class="{ 'uve-justify-btn--active': justifyContent === '{{ $justVal }}' }"
                            title="{{ $justLabel }}"
                        >
                            <div class="uve-justify-preview uve-justify-preview--{{ str_replace('-', '', $justVal) }}">
                                <span></span><span></span><span></span>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Align Items --}}
            <div class="uve-control__field">
                <label class="uve-control__label">Wyrownanie pionowe (align)</label>
                <div class="uve-align-grid">
                    @foreach($aligns as $alignVal => $alignLabel)
                        <button
                            type="button"
                            @click="alignItems = '{{ $alignVal }}'; emitChange()"
                            class="uve-align-btn"
                            :class="{ 'uve-align-btn--active': alignItems === '{{ $alignVal }}' }"
                            title="{{ $alignLabel }}"
                        >
                            <div class="uve-align-preview uve-align-preview--{{ str_replace('-', '', $alignVal) }}">
                                <span></span><span></span><span></span>
                            </div>
                        </button>
                    @endforeach
                </div>
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
        </div>
    </template>
</div>

<style>
/* Layout Flex Control Styles */
.uve-control--layout-flex {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Flex Preview */
.uve-flex-preview {
    display: flex;
    height: 50px;
    padding: 0.375rem;
    background: #1e293b;
    border: 2px dashed #475569;
    border-radius: 0.375rem;
    gap: 0.25rem;
}

.uve-flex-item {
    width: 12px;
    height: 12px;
    background: #e0ac7e;
    border-radius: 2px;
    flex-shrink: 0;
}

/* Toggle Row */
.uve-toggle-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.uve-checkbox {
    width: 18px;
    height: 18px;
    accent-color: #e0ac7e;
    cursor: pointer;
}

.uve-toggle-label {
    font-size: 0.875rem;
    color: #e2e8f0;
}

/* Flex Options */
.uve-flex-options {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Direction Grid */
.uve-flex-direction-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.25rem;
}

.uve-flex-dir-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.25rem;
    color: #94a3b8;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-flex-dir-btn:hover {
    background: #475569;
    color: #e2e8f0;
}

.uve-flex-dir-btn--active {
    background: #e0ac7e;
    border-color: #e0ac7e;
    color: #0f172a;
}

/* Justify/Align Grids */
.uve-justify-grid,
.uve-align-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.25rem;
}

.uve-justify-btn,
.uve-align-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.375rem;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-justify-btn:hover,
.uve-align-btn:hover {
    background: #475569;
}

.uve-justify-btn--active,
.uve-align-btn--active {
    background: rgba(224, 172, 126, 0.2);
    border-color: #e0ac7e;
}

/* Justify Preview Boxes */
.uve-justify-preview {
    display: flex;
    width: 100%;
    height: 16px;
    padding: 2px;
    background: #1e293b;
    border-radius: 2px;
}

.uve-justify-preview span {
    width: 4px;
    height: 100%;
    background: #e0ac7e;
    border-radius: 1px;
}

.uve-justify-preview--flexstart { justify-content: flex-start; gap: 2px; }
.uve-justify-preview--flexend { justify-content: flex-end; gap: 2px; }
.uve-justify-preview--center { justify-content: center; gap: 2px; }
.uve-justify-preview--spacebetween { justify-content: space-between; }
.uve-justify-preview--spacearound { justify-content: space-around; }
.uve-justify-preview--spaceevenly { justify-content: space-evenly; }

/* Align Preview Boxes */
.uve-align-preview {
    display: flex;
    flex-direction: column;
    width: 24px;
    height: 24px;
    padding: 2px;
    background: #1e293b;
    border-radius: 2px;
}

.uve-align-preview span {
    width: 100%;
    height: 4px;
    background: #e0ac7e;
    border-radius: 1px;
}

.uve-align-preview--flexstart { justify-content: flex-start; gap: 1px; }
.uve-align-preview--flexend { justify-content: flex-end; gap: 1px; }
.uve-align-preview--center { justify-content: center; gap: 1px; }
.uve-align-preview--stretch span { height: 6px; }
.uve-align-preview--stretch { justify-content: space-between; }
.uve-align-preview--baseline { justify-content: flex-end; gap: 1px; }
.uve-align-preview--baseline span:first-child { height: 6px; }

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
function uveLayoutFlexControl(initialValue) {
    return {
        enabled: initialValue.display === 'flex',
        flexDirection: initialValue.flexDirection || 'row',
        flexWrap: initialValue.flexWrap || 'nowrap',
        justifyContent: initialValue.justifyContent || 'flex-start',
        alignItems: initialValue.alignItems || 'stretch',
        gap: initialValue.gap || '',

        get previewStyle() {
            if (!this.enabled) return 'opacity: 0.5;';
            return `
                flex-direction: ${this.flexDirection};
                flex-wrap: ${this.flexWrap};
                justify-content: ${this.justifyContent};
                align-items: ${this.alignItems};
                gap: ${this.gap || '0.25rem'};
            `;
        },

        emitChange() {
            const value = {
                display: this.enabled ? 'flex' : 'block',
                flexDirection: this.flexDirection,
                flexWrap: this.flexWrap,
                justifyContent: this.justifyContent,
                alignItems: this.alignItems,
                gap: this.gap,
            };
            this.$wire.updateControlValue('layout-flex', value);
        }
    }
}
</script>
