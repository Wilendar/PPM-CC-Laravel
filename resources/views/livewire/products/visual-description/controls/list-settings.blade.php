{{--
    UVE Property Panel: List Settings Control
    ETAP_07h PP.2: Block-Specific Controls

    Provides controls for MeritListBlock and pd-asset-list:
    - List style (bullets, numbers, icons, checkmarks)
    - Icon style and color
    - Layout (vertical, horizontal, grid)
    - Columns and spacing
--}}

@props([
    'controlId' => 'list-settings',
    'value' => [],
    'options' => [],
    'onChange' => null,
])

@php
    $defaults = [
        'listStyle' => 'checkmarks',
        'iconStyle' => 'check-circle',
        'iconColor' => 'brand',
        'layout' => 'vertical',
        'columns' => 1,
        'itemGap' => '0.75rem',
        'iconSize' => 'medium',
        'indentation' => '0',
    ];
    $currentValue = array_merge($defaults, $value ?? []);

    $listStyles = $options['listStyles'] ?? [
        'none' => 'Brak znacznikow',
        'bullets' => 'Punktory',
        'numbers' => 'Numerowana',
        'checkmarks' => 'Ptaszki',
        'icons' => 'Ikony',
        'arrows' => 'Strzalki',
    ];

    $iconStyles = $options['iconStyles'] ?? [
        'check' => 'Ptaszek prosty',
        'check-circle' => 'Ptaszek w kolku',
        'check-badge' => 'Ptaszek w znaczku',
        'star' => 'Gwiazdka',
        'arrow-right' => 'Strzalka',
        'chevron-right' => 'Chevron',
        'plus' => 'Plus',
        'dot' => 'Kropka',
    ];

    $iconColors = $options['iconColors'] ?? [
        'brand' => 'Kolor marki',
        'success' => 'Zielony',
        'info' => 'Niebieski',
        'warning' => 'Zolty',
        'dark' => 'Ciemny',
        'inherit' => 'Dziedziczony',
    ];

    $layouts = $options['layouts'] ?? [
        'vertical' => 'Pionowy',
        'horizontal' => 'Poziomy',
        'grid' => 'Siatka',
    ];

    $columnPresets = $options['columnPresets'] ?? [
        1 => '1 kolumna',
        2 => '2 kolumny',
        3 => '3 kolumny',
        4 => '4 kolumny',
    ];

    $gapPresets = $options['gapPresets'] ?? [
        '0.25rem' => 'XS',
        '0.5rem' => 'S',
        '0.75rem' => 'M',
        '1rem' => 'L',
        '1.5rem' => 'XL',
    ];

    $iconSizes = $options['iconSizes'] ?? [
        'small' => 'Maly (16px)',
        'medium' => 'Sredni (20px)',
        'large' => 'Duzy (24px)',
    ];
@endphp

<div
    x-data="uveListSettingsControl(@js($currentValue))"
    class="uve-control uve-control--list-settings"
>
    {{-- List Style --}}
    <div class="uve-control__group">
        <label class="uve-control__label">Styl listy</label>
        <div class="uve-control__button-group uve-control__button-group--wrap">
            @foreach($listStyles as $styleKey => $styleLabel)
                <button
                    type="button"
                    class="uve-control__button"
                    :class="{ 'uve-control__button--active': value.listStyle === '{{ $styleKey }}' }"
                    @click="updateValue('listStyle', '{{ $styleKey }}')"
                    title="{{ $styleLabel }}"
                >
                    @if($styleKey === 'none')
                        <span class="uve-icon">—</span>
                    @elseif($styleKey === 'bullets')
                        <span class="uve-icon">•</span>
                    @elseif($styleKey === 'numbers')
                        <span class="uve-icon">1.</span>
                    @elseif($styleKey === 'checkmarks')
                        <x-heroicon-o-check class="w-4 h-4" />
                    @elseif($styleKey === 'icons')
                        <x-heroicon-o-star class="w-4 h-4" />
                    @elseif($styleKey === 'arrows')
                        <x-heroicon-o-arrow-right class="w-4 h-4" />
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    {{-- Icon Style (only visible when listStyle is checkmarks/icons/arrows) --}}
    <template x-if="['checkmarks', 'icons', 'arrows'].includes(value.listStyle)">
        <div class="uve-control__group">
            <label class="uve-control__label">Ikona</label>
            <select
                class="uve-control__select"
                x-model="value.iconStyle"
                @change="emitChange()"
            >
                @foreach($iconStyles as $iconKey => $iconLabel)
                    <option value="{{ $iconKey }}">{{ $iconLabel }}</option>
                @endforeach
            </select>
        </div>
    </template>

    {{-- Icon Color (only visible when using icons) --}}
    <template x-if="['checkmarks', 'icons', 'arrows'].includes(value.listStyle)">
        <div class="uve-control__group">
            <label class="uve-control__label">Kolor ikony</label>
            <div class="uve-control__button-group">
                @foreach($iconColors as $colorKey => $colorLabel)
                    <button
                        type="button"
                        class="uve-control__color-btn uve-control__color-btn--{{ $colorKey }}"
                        :class="{ 'uve-control__color-btn--active': value.iconColor === '{{ $colorKey }}' }"
                        @click="updateValue('iconColor', '{{ $colorKey }}')"
                        title="{{ $colorLabel }}"
                    ></button>
                @endforeach
            </div>
        </div>
    </template>

    {{-- Icon Size (only visible when using icons) --}}
    <template x-if="['checkmarks', 'icons', 'arrows'].includes(value.listStyle)">
        <div class="uve-control__group">
            <label class="uve-control__label">Rozmiar ikony</label>
            <div class="uve-control__button-group">
                @foreach($iconSizes as $sizeKey => $sizeLabel)
                    <button
                        type="button"
                        class="uve-control__button"
                        :class="{ 'uve-control__button--active': value.iconSize === '{{ $sizeKey }}' }"
                        @click="updateValue('iconSize', '{{ $sizeKey }}')"
                    >
                        {{ explode(' ', $sizeLabel)[0] }}
                    </button>
                @endforeach
            </div>
        </div>
    </template>

    {{-- Layout --}}
    <div class="uve-control__group">
        <label class="uve-control__label">Uklad</label>
        <div class="uve-control__button-group">
            @foreach($layouts as $layoutKey => $layoutLabel)
                <button
                    type="button"
                    class="uve-control__button"
                    :class="{ 'uve-control__button--active': value.layout === '{{ $layoutKey }}' }"
                    @click="updateValue('layout', '{{ $layoutKey }}')"
                >
                    @if($layoutKey === 'vertical')
                        <x-heroicon-o-bars-3 class="w-4 h-4" />
                    @elseif($layoutKey === 'horizontal')
                        <x-heroicon-o-bars-3 class="w-4 h-4 rotate-90" />
                    @elseif($layoutKey === 'grid')
                        <x-heroicon-o-squares-2x2 class="w-4 h-4" />
                    @endif
                    <span class="ml-1">{{ $layoutLabel }}</span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Columns (only visible when layout is grid or horizontal) --}}
    <template x-if="['grid', 'horizontal'].includes(value.layout)">
        <div class="uve-control__group">
            <label class="uve-control__label">Kolumny</label>
            <div class="uve-control__button-group">
                @foreach($columnPresets as $colNum => $colLabel)
                    <button
                        type="button"
                        class="uve-control__button"
                        :class="{ 'uve-control__button--active': value.columns === {{ $colNum }} }"
                        @click="updateValue('columns', {{ $colNum }})"
                    >
                        {{ $colNum }}
                    </button>
                @endforeach
            </div>
        </div>
    </template>

    {{-- Item Gap --}}
    <div class="uve-control__group">
        <label class="uve-control__label">Odstep miedzy elementami</label>
        <div class="uve-control__button-group">
            @foreach($gapPresets as $gapValue => $gapLabel)
                <button
                    type="button"
                    class="uve-control__button"
                    :class="{ 'uve-control__button--active': value.itemGap === '{{ $gapValue }}' }"
                    @click="updateValue('itemGap', '{{ $gapValue }}')"
                >
                    {{ $gapLabel }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Indentation --}}
    <div class="uve-control__group">
        <label class="uve-control__label">Wciecie</label>
        <div class="uve-control__input-row">
            <input
                type="text"
                class="uve-control__input"
                x-model="value.indentation"
                @change="emitChange()"
                placeholder="0"
            >
            <span class="uve-control__unit">px/rem</span>
        </div>
    </div>
</div>

<style>
    .uve-control--list-settings {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .uve-control__button-group--wrap {
        flex-wrap: wrap;
    }

    .uve-control__color-btn {
        width: 24px;
        height: 24px;
        border-radius: 4px;
        border: 2px solid transparent;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .uve-control__color-btn--active {
        border-color: var(--color-primary, #3b82f6);
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
    }

    .uve-control__color-btn--brand {
        background: linear-gradient(135deg, #ef8248, #f59e0b);
    }

    .uve-control__color-btn--success {
        background: linear-gradient(135deg, #10b981, #34d399);
    }

    .uve-control__color-btn--info {
        background: linear-gradient(135deg, #3b82f6, #60a5fa);
    }

    .uve-control__color-btn--warning {
        background: linear-gradient(135deg, #f59e0b, #fbbf24);
    }

    .uve-control__color-btn--dark {
        background: linear-gradient(135deg, #374151, #6b7280);
    }

    .uve-control__color-btn--inherit {
        background: linear-gradient(135deg, #9ca3af, #d1d5db);
    }

    .uve-control__input-row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .uve-control__unit {
        font-size: 0.75rem;
        color: var(--color-text-secondary, #6b7280);
    }
</style>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('uveListSettingsControl', (initialValue) => ({
            value: initialValue,

            updateValue(key, val) {
                this.value[key] = val;
                this.emitChange();
            },

            emitChange() {
                if (typeof $wire !== 'undefined') {
                    $wire.updateControlValue('list-settings', this.value);
                }
                this.$dispatch('control-change', {
                    control: 'list-settings',
                    value: this.value
                });
            }
        }));
    });
</script>
