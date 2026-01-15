{{--
    UVE Property Panel V2 - ETAP_07f_P5 FAZA PP.4
    Glowny panel wlasciwosci z 4 zakladkami: Style, Layout, Advanced, Classes
    Integruje kontrolki PP.2 i PP.3, wsparcie Hover States i Responsive
--}}
@props([
    'panelConfig' => [],
    'activeTab' => 'style',
    'hoverState' => 'normal',
    'currentDevice' => 'desktop',
    'elementStyles' => [],
    'selectedElementId' => null,
])

@php
    $tabs = [
        'style' => ['label' => 'Style', 'icon' => 'palette'],
        'layout' => ['label' => 'Layout', 'icon' => 'template'],
        'advanced' => ['label' => 'Zaawansowane', 'icon' => 'adjustments'],
        'classes' => ['label' => 'Klasy CSS', 'icon' => 'code'],
    ];

    $controls = $panelConfig['controls'] ?? [];
    $tabsWithControls = $panelConfig['tabs'] ?? [];
    $values = $panelConfig['values'] ?? [];
    $hoverSupported = $panelConfig['hoverSupported'] ?? [];
    $responsiveSupported = $panelConfig['responsive'] ?? [];
@endphp

<div
    class="uve-property-panel-v2"
    x-data="uvePropertyPanelV2({
        activeTab: @js($activeTab),
        hoverState: @js($hoverState),
        currentDevice: @js($currentDevice),
        values: @js($values),
        hoverValues: @js($panelConfig['hoverValues'] ?? []),
        responsiveValues: @js($panelConfig['responsiveValues'] ?? []),
        hoverSupported: @js($hoverSupported),
        responsiveSupported: @js($responsiveSupported)
    })"
    wire:ignore.self
>
    {{-- Header with Element Info --}}
    @if($selectedElementId)
        <div class="uve-pp-header">
            <div class="uve-pp-element-info">
                <span class="uve-pp-element-tag">{{ $panelConfig['elementType'] ?? 'div' }}</span>
                <span class="uve-pp-element-id" title="{{ $selectedElementId }}">
                    #{{ Str::limit($selectedElementId, 12) }}
                </span>
            </div>
            {{-- Device Switcher (inline) --}}
            <div class="uve-pp-device-switcher">
                @include('livewire.products.visual-description.controls.device-switcher', [
                    'currentDevice' => $currentDevice,
                    'compact' => true
                ])
            </div>
        </div>
    @else
        <div class="uve-pp-empty-state">
            <svg class="uve-pp-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122">
                </path>
            </svg>
            <p class="uve-pp-empty-text">Wybierz element do edycji</p>
            <p class="uve-pp-empty-hint">Kliknij element w edytorze lub warstwie</p>
        </div>
    @endif

    @if($selectedElementId)
        {{-- Tab Navigation --}}
        <div class="uve-pp-tabs">
            @foreach($tabs as $tabKey => $tab)
                <button
                    type="button"
                    @click="switchTab('{{ $tabKey }}')"
                    class="uve-pp-tab"
                    :class="{ 'uve-pp-tab--active': activeTab === '{{ $tabKey }}' }"
                    wire:click="switchTab('{{ $tabKey }}')"
                >
                    @switch($tab['icon'])
                        @case('palette')
                            <svg class="uve-pp-tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01">
                                </path>
                            </svg>
                            @break
                        @case('template')
                            <svg class="uve-pp-tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z">
                                </path>
                            </svg>
                            @break
                        @case('adjustments')
                            <svg class="uve-pp-tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4">
                                </path>
                            </svg>
                            @break
                        @case('code')
                            <svg class="uve-pp-tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4">
                                </path>
                            </svg>
                            @break
                    @endswitch
                    <span class="uve-pp-tab-label">{{ $tab['label'] }}</span>
                </button>
            @endforeach
        </div>

        {{-- Hover State Toggle --}}
        <div class="uve-pp-state-toggle" x-show="hasHoverControls()">
            @include('livewire.products.visual-description.controls.hover-states', [
                'hoverState' => $hoverState,
                'compact' => true
            ])
        </div>

        {{-- Tab Content --}}
        <div class="uve-pp-content">
            {{-- Style Tab --}}
            <div x-show="activeTab === 'style'" class="uve-pp-tab-content">
                {{-- Typography Section --}}
                @if(isset($controls['typography']))
                    <div class="uve-pp-section">
                        <button type="button" class="uve-pp-section-header" @click="toggleSection('typography')">
                            <span class="uve-pp-section-title">Typografia</span>
                            <svg class="uve-pp-section-chevron" :class="{ 'rotate-180': openSections.includes('typography') }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openSections.includes('typography')" x-collapse class="uve-pp-section-content">
                            @include('livewire.products.visual-description.controls.typography', [
                                'controlId' => 'typography',
                                'value' => $values['typography'] ?? [],
                                'options' => $controls['typography']['options'] ?? [],
                                'selectedElementId' => $selectedElementId
                            ])
                        </div>
                    </div>
                @endif

                {{-- Image Settings Section --}}
                @if(isset($controls['image-settings']))
                    <div class="uve-pp-section">
                        <button type="button" class="uve-pp-section-header" @click="toggleSection('image-settings')">
                            <span class="uve-pp-section-title">Ustawienia obrazu</span>
                            <svg class="uve-pp-section-chevron" :class="{ 'rotate-180': openSections.includes('image-settings') }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openSections.includes('image-settings')" x-collapse class="uve-pp-section-content">
                            @include('livewire.products.visual-description.controls.image-settings', [
                                'control' => $controls['image-settings'] ?? [],
                                'value' => $values['image-settings'] ?? [],
                                'selectedElementId' => $selectedElementId,
                            ])
                        </div>
                    </div>
                @endif

                {{-- Colors Section --}}
                @if(isset($controls['color-picker']))
                    <div class="uve-pp-section">
                        <button type="button" class="uve-pp-section-header" @click="toggleSection('colors')">
                            <span class="uve-pp-section-title">Kolory</span>
                            <svg class="uve-pp-section-chevron" :class="{ 'rotate-180': openSections.includes('colors') }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openSections.includes('colors')" x-collapse class="uve-pp-section-content">
                            @include('livewire.products.visual-description.controls.color-picker', [
                                'controlId' => 'color',
                                'value' => $values['color'] ?? '',
                                'label' => 'Kolor tekstu',
                                'property' => 'color'
                            ])
                        </div>
                    </div>
                @endif

                {{-- Background Section --}}
                @if(isset($controls['background']))
                    <div class="uve-pp-section">
                        <button type="button" class="uve-pp-section-header" @click="toggleSection('background')">
                            <span class="uve-pp-section-title">Tlo</span>
                            <svg class="uve-pp-section-chevron" :class="{ 'rotate-180': openSections.includes('background') }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openSections.includes('background')" x-collapse class="uve-pp-section-content">
                            @include('livewire.products.visual-description.controls.background', [
                                'controlId' => 'background',
                                'value' => $values['background'] ?? [],
                                'options' => $controls['background']['options'] ?? []
                            ])
                        </div>
                    </div>
                @endif

                {{-- Border Section --}}
                @if(isset($controls['border']))
                    <div class="uve-pp-section">
                        <button type="button" class="uve-pp-section-header" @click="toggleSection('border')">
                            <span class="uve-pp-section-title">Obramowanie</span>
                            <svg class="uve-pp-section-chevron" :class="{ 'rotate-180': openSections.includes('border') }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openSections.includes('border')" x-collapse class="uve-pp-section-content">
                            @include('livewire.products.visual-description.controls.border', [
                                'controlId' => 'border',
                                'value' => $values['border'] ?? [],
                                'options' => $controls['border']['options'] ?? []
                            ])
                        </div>
                    </div>
                @endif

                {{-- Effects Section --}}
                @if(isset($controls['effects']))
                    <div class="uve-pp-section">
                        <button type="button" class="uve-pp-section-header" @click="toggleSection('effects')">
                            <span class="uve-pp-section-title">Efekty</span>
                            <svg class="uve-pp-section-chevron" :class="{ 'rotate-180': openSections.includes('effects') }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openSections.includes('effects')" x-collapse class="uve-pp-section-content">
                            @include('livewire.products.visual-description.controls.effects', [
                                'controlId' => 'effects',
                                'value' => $values['effects'] ?? [],
                                'options' => $controls['effects']['options'] ?? []
                            ])
                        </div>
                    </div>
                @endif
            </div>

            {{-- Layout Tab --}}
            <div x-show="activeTab === 'layout'" class="uve-pp-tab-content">
                {{-- Size Section --}}
                @if(isset($controls['size']))
                    <div class="uve-pp-section">
                        <button type="button" class="uve-pp-section-header" @click="toggleSection('size')">
                            <span class="uve-pp-section-title">Rozmiar</span>
                            <svg class="uve-pp-section-chevron" :class="{ 'rotate-180': openSections.includes('size') }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openSections.includes('size')" x-collapse class="uve-pp-section-content">
                            @include('livewire.products.visual-description.controls.size', [
                                'controlId' => 'size',
                                'value' => $values['size'] ?? [],
                                'options' => $controls['size']['options'] ?? []
                            ])
                        </div>
                    </div>
                @endif

                {{-- Box Model Section --}}
                @if(isset($controls['box-model']))
                    <div class="uve-pp-section">
                        <button type="button" class="uve-pp-section-header" @click="toggleSection('box-model')">
                            <span class="uve-pp-section-title">Odstepy (Margin/Padding)</span>
                            <svg class="uve-pp-section-chevron" :class="{ 'rotate-180': openSections.includes('box-model') }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openSections.includes('box-model')" x-collapse class="uve-pp-section-content">
                            @include('livewire.products.visual-description.controls.box-model', [
                                'controlId' => 'box-model',
                                'value' => $values['boxModel'] ?? [],
                                'options' => $controls['box-model']['options'] ?? []
                            ])
                        </div>
                    </div>
                @endif

                {{-- Flex Layout Section --}}
                @if(isset($controls['layout-flex']))
                    <div class="uve-pp-section">
                        <button type="button" class="uve-pp-section-header" @click="toggleSection('flex')">
                            <span class="uve-pp-section-title">Flexbox</span>
                            <svg class="uve-pp-section-chevron" :class="{ 'rotate-180': openSections.includes('flex') }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openSections.includes('flex')" x-collapse class="uve-pp-section-content">
                            @include('livewire.products.visual-description.controls.layout-flex', [
                                'controlId' => 'layout-flex',
                                'value' => $values['layoutFlex'] ?? [],
                                'options' => $controls['layout-flex']['options'] ?? []
                            ])
                        </div>
                    </div>
                @endif

                {{-- Grid Layout Section --}}
                @if(isset($controls['layout-grid']))
                    <div class="uve-pp-section">
                        <button type="button" class="uve-pp-section-header" @click="toggleSection('grid')">
                            <span class="uve-pp-section-title">Grid</span>
                            <svg class="uve-pp-section-chevron" :class="{ 'rotate-180': openSections.includes('grid') }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openSections.includes('grid')" x-collapse class="uve-pp-section-content">
                            @include('livewire.products.visual-description.controls.layout-grid', [
                                'controlId' => 'layout-grid',
                                'value' => $values['layoutGrid'] ?? [],
                                'options' => $controls['layout-grid']['options'] ?? []
                            ])
                        </div>
                    </div>
                @endif

                {{-- Position Section --}}
                @if(isset($controls['position']))
                    <div class="uve-pp-section">
                        <button type="button" class="uve-pp-section-header" @click="toggleSection('position')">
                            <span class="uve-pp-section-title">Pozycjonowanie</span>
                            <svg class="uve-pp-section-chevron" :class="{ 'rotate-180': openSections.includes('position') }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openSections.includes('position')" x-collapse class="uve-pp-section-content">
                            @include('livewire.products.visual-description.controls.position', [
                                'controlId' => 'position',
                                'value' => $values['position'] ?? [],
                                'options' => $controls['position']['options'] ?? []
                            ])
                        </div>
                    </div>
                @endif
            </div>

            {{-- Advanced Tab --}}
            <div x-show="activeTab === 'advanced'" class="uve-pp-tab-content">
                {{-- Transform Section --}}
                @if(isset($controls['transform']))
                    <div class="uve-pp-section">
                        <button type="button" class="uve-pp-section-header" @click="toggleSection('transform')">
                            <span class="uve-pp-section-title">Transformacje</span>
                            <svg class="uve-pp-section-chevron" :class="{ 'rotate-180': openSections.includes('transform') }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openSections.includes('transform')" x-collapse class="uve-pp-section-content">
                            @include('livewire.products.visual-description.controls.transform', [
                                'controlId' => 'transform',
                                'value' => $values['transform'] ?? [],
                                'options' => $controls['transform']['options'] ?? []
                            ])
                        </div>
                    </div>
                @endif

                {{-- Transition Section --}}
                <div class="uve-pp-section">
                    <button type="button" class="uve-pp-section-header" @click="toggleSection('transition')">
                        <span class="uve-pp-section-title">Przejscia (Transitions)</span>
                        <svg class="uve-pp-section-chevron" :class="{ 'rotate-180': openSections.includes('transition') }"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="openSections.includes('transition')" x-collapse class="uve-pp-section-content">
                        @include('livewire.products.visual-description.controls.transition', [
                            'controlId' => 'transition',
                            'value' => $values['transition'] ?? []
                        ])
                    </div>
                </div>

                {{-- Hover States Section --}}
                <div class="uve-pp-section">
                    <button type="button" class="uve-pp-section-header" @click="toggleSection('hover-states')">
                        <span class="uve-pp-section-title">Stany Hover</span>
                        <svg class="uve-pp-section-chevron" :class="{ 'rotate-180': openSections.includes('hover-states') }"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="openSections.includes('hover-states')" x-collapse class="uve-pp-section-content">
                        @include('livewire.products.visual-description.controls.hover-states', [
                            'hoverState' => $hoverState,
                            'compact' => false
                        ])
                    </div>
                </div>

                {{-- Slider Settings (if applicable) --}}
                @if(isset($controls['slider-settings']))
                    <div class="uve-pp-section">
                        <button type="button" class="uve-pp-section-header" @click="toggleSection('slider')">
                            <span class="uve-pp-section-title">Ustawienia Slidera</span>
                            <svg class="uve-pp-section-chevron" :class="{ 'rotate-180': openSections.includes('slider') }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openSections.includes('slider')" x-collapse class="uve-pp-section-content">
                            @include('livewire.products.visual-description.controls.slider-settings', [
                                'controlId' => 'slider-settings',
                                'value' => $values['sliderSettings'] ?? []
                            ])
                        </div>
                    </div>
                @endif

                {{-- Parallax Settings (if applicable) --}}
                @if(isset($controls['parallax-settings']))
                    <div class="uve-pp-section">
                        <button type="button" class="uve-pp-section-header" @click="toggleSection('parallax')">
                            <span class="uve-pp-section-title">Ustawienia Parallax</span>
                            <svg class="uve-pp-section-chevron" :class="{ 'rotate-180': openSections.includes('parallax') }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openSections.includes('parallax')" x-collapse class="uve-pp-section-content">
                            @include('livewire.products.visual-description.controls.parallax-settings', [
                                'controlId' => 'parallax-settings',
                                'value' => $values['parallaxSettings'] ?? []
                            ])
                        </div>
                    </div>
                @endif
            </div>

            {{-- Classes Tab --}}
            <div x-show="activeTab === 'classes'" class="uve-pp-tab-content">
                <div class="uve-pp-classes-section">
                    {{-- Current Classes --}}
                    <div class="uve-pp-classes-current">
                        <label class="uve-control__label">Aktualne klasy</label>
                        <div class="uve-pp-classes-list">
                            @forelse($panelConfig['cssClasses'] ?? [] as $class)
                                <span class="uve-pp-class-tag">
                                    {{ $class }}
                                    @if(!in_array($class, $panelConfig['readonlyClasses'] ?? []))
                                        <button
                                            type="button"
                                            @click="removeClass('{{ $class }}')"
                                            class="uve-pp-class-remove"
                                            title="Usun klase"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    @else
                                        <svg class="w-3 h-3 uve-pp-class-lock" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Klasa zablokowana">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                    @endif
                                </span>
                            @empty
                                <span class="uve-pp-classes-empty">Brak klas CSS</span>
                            @endforelse
                        </div>
                    </div>

                    {{-- Add Class --}}
                    <div class="uve-pp-classes-add">
                        <label class="uve-control__label">Dodaj klase</label>
                        <div class="uve-pp-classes-input-row">
                            <input
                                type="text"
                                x-model="newClass"
                                @keydown.enter="addClass()"
                                class="uve-input"
                                placeholder="np. text-center, my-4"
                            />
                            <button
                                type="button"
                                @click="addClass()"
                                class="uve-btn uve-btn-primary"
                                :disabled="!newClass.trim()"
                            >
                                Dodaj
                            </button>
                        </div>
                    </div>

                    {{-- PrestaShop Classes Quick Access --}}
                    <div class="uve-pp-classes-presets">
                        <label class="uve-control__label">Klasy PrestaShop</label>
                        <div class="uve-pp-classes-preset-groups">
                            @foreach($panelConfig['classesTab']['prestashopClasses'] ?? [] as $group => $classes)
                                <div class="uve-pp-preset-group">
                                    <span class="uve-pp-preset-group-label">{{ $group }}</span>
                                    <div class="uve-pp-preset-buttons">
                                        @foreach(array_slice($classes, 0, 6) as $class)
                                            <button
                                                type="button"
                                                @click="toggleClass('{{ $class }}')"
                                                class="uve-pp-preset-btn"
                                                :class="{ 'uve-pp-preset-btn--active': hasClass('{{ $class }}') }"
                                            >
                                                {{ $class }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer Actions --}}
        <div class="uve-pp-footer">
            <button
                type="button"
                @click="resetStyles()"
                class="uve-btn uve-btn-sm"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Reset
            </button>
            <button
                type="button"
                @click="copyStyles()"
                class="uve-btn uve-btn-sm"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                Kopiuj
            </button>
            <button
                type="button"
                @click="pasteStyles()"
                class="uve-btn uve-btn-sm"
                :disabled="!hasClipboard"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                Wklej
            </button>
        </div>
    @endif
</div>

<style>
/* UVE Property Panel V2 Styles */
.uve-property-panel-v2 {
    display: flex;
    flex-direction: column;
    height: 100%;
    background: var(--color-bg-primary, #0f172a);
    border-left: 1px solid #334155;
}

/* Header */
.uve-pp-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #334155;
    background: var(--color-bg-secondary, #1e293b);
}

.uve-pp-element-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.uve-pp-element-tag {
    padding: 0.125rem 0.375rem;
    background: #e0ac7e;
    color: #0f172a;
    font-size: 0.7rem;
    font-weight: 600;
    border-radius: 0.25rem;
    text-transform: uppercase;
}

.uve-pp-element-id {
    font-size: 0.75rem;
    color: #64748b;
    font-family: monospace;
}

/* Empty State */
.uve-pp-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 1.5rem;
    text-align: center;
}

.uve-pp-empty-icon {
    width: 3rem;
    height: 3rem;
    color: #475569;
    margin-bottom: 1rem;
}

.uve-pp-empty-text {
    font-size: 0.875rem;
    color: #94a3b8;
    margin: 0 0 0.25rem;
}

.uve-pp-empty-hint {
    font-size: 0.75rem;
    color: #64748b;
    margin: 0;
}

/* Tabs */
.uve-pp-tabs {
    display: flex;
    border-bottom: 1px solid #334155;
    background: var(--color-bg-secondary, #1e293b);
}

.uve-pp-tab {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 0.625rem 0.5rem;
    background: transparent;
    border: none;
    color: #64748b;
    cursor: pointer;
    transition: all 0.15s;
    position: relative;
}

.uve-pp-tab:hover {
    color: #94a3b8;
    background: rgba(51, 65, 85, 0.5);
}

.uve-pp-tab--active {
    color: #e0ac7e;
}

.uve-pp-tab--active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: #e0ac7e;
}

.uve-pp-tab-icon {
    width: 1.125rem;
    height: 1.125rem;
}

.uve-pp-tab-label {
    font-size: 0.65rem;
    font-weight: 500;
}

/* State Toggle */
.uve-pp-state-toggle {
    padding: 0.5rem 1rem;
    border-bottom: 1px solid #334155;
    background: rgba(30, 41, 59, 0.5);
}

/* Content */
.uve-pp-content {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
}

.uve-pp-tab-content {
    padding: 0.75rem;
}

/* Sections */
.uve-pp-section {
    margin-bottom: 0.5rem;
    border: 1px solid #334155;
    border-radius: 0.5rem;
    overflow: hidden;
}

.uve-pp-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 0.625rem 0.75rem;
    background: var(--color-bg-secondary, #1e293b);
    border: none;
    color: #e2e8f0;
    cursor: pointer;
    transition: background 0.15s;
}

.uve-pp-section-header:hover {
    background: #334155;
}

.uve-pp-section-title {
    font-size: 0.8rem;
    font-weight: 500;
}

.uve-pp-section-chevron {
    width: 1rem;
    height: 1rem;
    color: #64748b;
    transition: transform 0.2s;
}

.uve-pp-section-content {
    padding: 0.75rem;
    background: var(--color-bg-primary, #0f172a);
}

/* Classes Tab */
.uve-pp-classes-section {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.uve-pp-classes-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
}

.uve-pp-class-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    background: #334155;
    color: #e2e8f0;
    font-size: 0.75rem;
    font-family: monospace;
    border-radius: 0.25rem;
}

.uve-pp-class-remove {
    display: flex;
    padding: 0.125rem;
    background: transparent;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    border-radius: 0.125rem;
    transition: all 0.15s;
}

.uve-pp-class-remove:hover {
    background: #ef4444;
    color: white;
}

.uve-pp-class-lock {
    color: #64748b;
}

.uve-pp-classes-empty {
    font-size: 0.75rem;
    color: #64748b;
    font-style: italic;
}

.uve-pp-classes-input-row {
    display: flex;
    gap: 0.375rem;
}

.uve-pp-classes-input-row .uve-input {
    flex: 1;
}

.uve-pp-preset-group {
    margin-bottom: 0.75rem;
}

.uve-pp-preset-group-label {
    display: block;
    font-size: 0.7rem;
    color: #64748b;
    margin-bottom: 0.375rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.uve-pp-preset-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
}

.uve-pp-preset-btn {
    padding: 0.25rem 0.5rem;
    background: #334155;
    border: 1px solid #475569;
    color: #94a3b8;
    font-size: 0.65rem;
    font-family: monospace;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-pp-preset-btn:hover {
    background: #475569;
    color: #e2e8f0;
}

.uve-pp-preset-btn--active {
    background: #e0ac7e;
    border-color: #e0ac7e;
    color: #0f172a;
}

/* Footer */
.uve-pp-footer {
    display: flex;
    gap: 0.375rem;
    padding: 0.75rem 1rem;
    border-top: 1px solid #334155;
    background: var(--color-bg-secondary, #1e293b);
}

.uve-pp-footer .uve-btn {
    flex: 1;
    justify-content: center;
    gap: 0.25rem;
}

/* Utilities */
.rotate-180 {
    transform: rotate(180deg);
}
</style>

<script>
function uvePropertyPanelV2(config) {
    return {
        activeTab: config.activeTab || 'style',
        hoverState: config.hoverState || 'normal',
        currentDevice: config.currentDevice || 'desktop',
        values: config.values || {},
        hoverValues: config.hoverValues || {},
        responsiveValues: config.responsiveValues || {},
        hoverSupported: config.hoverSupported || [],
        responsiveSupported: config.responsiveSupported || [],
        openSections: ['typography', 'colors', 'size', 'box-model'],
        newClass: '',
        hasClipboard: false,
        clipboard: null,
        currentClasses: [],

        init() {
            // Initialize classes from Livewire
            this.currentClasses = this.$wire.get('panelConfig.cssClasses') || [];

            // Listen for clipboard changes
            this.$watch('clipboard', (val) => {
                this.hasClipboard = !!val;
            });
        },

        switchTab(tab) {
            this.activeTab = tab;
        },

        toggleSection(section) {
            if (this.openSections.includes(section)) {
                this.openSections = this.openSections.filter(s => s !== section);
            } else {
                this.openSections.push(section);
            }
        },

        hasHoverControls() {
            return this.hoverSupported.length > 0;
        },

        // Classes management
        addClass() {
            const cls = this.newClass.trim();
            if (cls && !this.currentClasses.includes(cls)) {
                this.$wire.call('addClass', cls);
                this.newClass = '';
            }
        },

        removeClass(cls) {
            this.$wire.call('removeClass', cls);
        },

        toggleClass(cls) {
            if (this.hasClass(cls)) {
                this.removeClass(cls);
            } else {
                this.$wire.call('addClass', cls);
            }
        },

        hasClass(cls) {
            return this.currentClasses.includes(cls);
        },

        // Style operations
        resetStyles() {
            if (confirm('Czy na pewno chcesz zresetowac style tego elementu?')) {
                this.$wire.call('resetElementStyles');
            }
        },

        copyStyles() {
            this.clipboard = { ...this.values };
            this.hasClipboard = true;
            this.$wire.dispatch('notify', { type: 'info', message: 'Style skopiowane' });
        },

        pasteStyles() {
            if (this.clipboard) {
                this.$wire.call('applyStyles', this.clipboard);
                this.$wire.dispatch('notify', { type: 'success', message: 'Style wklejone' });
            }
        }
    }
}
</script>
