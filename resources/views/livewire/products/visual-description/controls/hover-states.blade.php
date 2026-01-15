{{--
    Hover States Control - ETAP_07f_P5 FAZA PP.4
    Toggle Normal/Hover + wskaznik ktory stan edytujemy
    Kontrolka do zarzadzania stanami hover elementow
--}}
@props([
    'hoverState' => 'normal',
    'compact' => false,
    'onChange' => null,
])

<div
    class="uve-control uve-control--hover-states {{ $compact ? 'uve-control--compact' : '' }}"
    x-data="uveHoverStatesControl(@js($hoverState))"
    wire:ignore.self
>
    @if(!$compact)
        <label class="uve-control__label">Stan elementu</label>
        <p class="uve-control__hint">Edytuj style dla normalnego stanu lub po najechaniu kursorem</p>
    @endif

    {{-- State Toggle --}}
    <div class="uve-hover-toggle">
        <button
            type="button"
            @click="setState('normal')"
            class="uve-hover-btn"
            :class="{ 'uve-hover-btn--active': state === 'normal' }"
            wire:click="switchHoverState('normal')"
        >
            <svg class="uve-hover-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122">
                </path>
            </svg>
            <span class="uve-hover-btn-label">Normal</span>
        </button>

        <button
            type="button"
            @click="setState('hover')"
            class="uve-hover-btn"
            :class="{ 'uve-hover-btn--active': state === 'hover' }"
            wire:click="switchHoverState('hover')"
        >
            <svg class="uve-hover-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11">
                </path>
            </svg>
            <span class="uve-hover-btn-label">:hover</span>
        </button>
    </div>

    {{-- State Indicator --}}
    <div class="uve-hover-indicator" :class="{ 'uve-hover-indicator--hover': state === 'hover' }">
        <span class="uve-hover-indicator-dot"></span>
        <span class="uve-hover-indicator-text" x-text="state === 'normal' ? 'Edytujesz stan normalny' : 'Edytujesz stan :hover'"></span>
    </div>

    @if(!$compact)
        {{-- Hover Preview Toggle --}}
        <div class="uve-hover-preview">
            <label class="uve-checkbox-wrapper">
                <input
                    type="checkbox"
                    x-model="previewHover"
                    @change="togglePreviewHover()"
                    class="uve-checkbox"
                />
                <span class="uve-checkbox-label">Podglad hover w edytorze</span>
            </label>
            <p class="uve-control__hint-sm">Symuluj stan hover aby zobaczyc efekt</p>
        </div>

        {{-- Hover Properties Info --}}
        <div class="uve-hover-info">
            <div class="uve-hover-info-header">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                    </path>
                </svg>
                <span>Wskazowka</span>
            </div>
            <p class="uve-hover-info-text">
                Ustaw <strong>Transition</strong> w zakladce Zaawansowane, aby animowac przejscie miedzy stanami.
            </p>
        </div>

        {{-- Quick Hover Presets --}}
        <div class="uve-hover-presets">
            <label class="uve-control__label">Szybkie efekty hover</label>
            <div class="uve-hover-preset-grid">
                <button
                    type="button"
                    @click="applyPreset('opacity')"
                    class="uve-hover-preset-btn"
                    title="Przezroczystosc przy hover"
                >
                    <span class="uve-hover-preset-demo uve-hover-preset-demo--opacity"></span>
                    <span>Opacity</span>
                </button>
                <button
                    type="button"
                    @click="applyPreset('scale')"
                    class="uve-hover-preset-btn"
                    title="Powiekszenie przy hover"
                >
                    <span class="uve-hover-preset-demo uve-hover-preset-demo--scale"></span>
                    <span>Scale</span>
                </button>
                <button
                    type="button"
                    @click="applyPreset('shadow')"
                    class="uve-hover-preset-btn"
                    title="Cien przy hover"
                >
                    <span class="uve-hover-preset-demo uve-hover-preset-demo--shadow"></span>
                    <span>Shadow</span>
                </button>
                <button
                    type="button"
                    @click="applyPreset('color')"
                    class="uve-hover-preset-btn"
                    title="Zmiana koloru przy hover"
                >
                    <span class="uve-hover-preset-demo uve-hover-preset-demo--color"></span>
                    <span>Color</span>
                </button>
                <button
                    type="button"
                    @click="applyPreset('lift')"
                    class="uve-hover-preset-btn"
                    title="Uniesienie przy hover"
                >
                    <span class="uve-hover-preset-demo uve-hover-preset-demo--lift"></span>
                    <span>Lift</span>
                </button>
                <button
                    type="button"
                    @click="applyPreset('glow')"
                    class="uve-hover-preset-btn"
                    title="Poswiecenie przy hover"
                >
                    <span class="uve-hover-preset-demo uve-hover-preset-demo--glow"></span>
                    <span>Glow</span>
                </button>
            </div>
        </div>
    @endif
</div>

<style>
/* Hover States Control Styles */
.uve-control--hover-states {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.uve-control--hover-states.uve-control--compact {
    gap: 0.5rem;
}

.uve-control--compact .uve-control__label,
.uve-control--compact .uve-control__hint {
    display: none;
}

/* State Toggle */
.uve-hover-toggle {
    display: flex;
    gap: 0.375rem;
    padding: 0.25rem;
    background: #1e293b;
    border-radius: 0.5rem;
    border: 1px solid #334155;
}

.uve-hover-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    padding: 0.5rem 0.75rem;
    background: transparent;
    border: none;
    border-radius: 0.375rem;
    color: #94a3b8;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-hover-btn:hover {
    background: rgba(51, 65, 85, 0.5);
    color: #e2e8f0;
}

.uve-hover-btn--active {
    background: #e0ac7e;
    color: #0f172a;
}

.uve-hover-btn--active:hover {
    background: #d19a6c;
    color: #0f172a;
}

.uve-hover-btn-icon {
    width: 1rem;
    height: 1rem;
}

.uve-hover-btn-label {
    font-family: monospace;
}

/* State Indicator */
.uve-hover-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background: rgba(30, 41, 59, 0.5);
    border-radius: 0.375rem;
    border: 1px solid #334155;
}

.uve-hover-indicator--hover {
    border-color: #3b82f6;
    background: rgba(59, 130, 246, 0.1);
}

.uve-hover-indicator-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10b981;
}

.uve-hover-indicator--hover .uve-hover-indicator-dot {
    background: #3b82f6;
    animation: pulse-hover 1.5s infinite;
}

@keyframes pulse-hover {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.6; transform: scale(1.2); }
}

.uve-hover-indicator-text {
    font-size: 0.75rem;
    color: #94a3b8;
}

.uve-hover-indicator--hover .uve-hover-indicator-text {
    color: #60a5fa;
}

/* Preview Toggle */
.uve-hover-preview {
    padding: 0.75rem;
    background: #1e293b;
    border-radius: 0.375rem;
}

.uve-checkbox-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.uve-checkbox {
    width: 1rem;
    height: 1rem;
    accent-color: #e0ac7e;
}

.uve-checkbox-label {
    font-size: 0.8rem;
    color: #e2e8f0;
}

.uve-control__hint-sm {
    font-size: 0.7rem;
    color: #64748b;
    margin: 0.375rem 0 0 1.5rem;
}

/* Info Box */
.uve-hover-info {
    padding: 0.625rem 0.75rem;
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.3);
    border-radius: 0.375rem;
}

.uve-hover-info-header {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    color: #60a5fa;
    font-size: 0.75rem;
    font-weight: 500;
    margin-bottom: 0.375rem;
}

.uve-hover-info-text {
    font-size: 0.75rem;
    color: #94a3b8;
    margin: 0;
    line-height: 1.4;
}

.uve-hover-info-text strong {
    color: #e2e8f0;
}

/* Hover Presets */
.uve-hover-presets {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.uve-hover-preset-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.375rem;
}

.uve-hover-preset-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.375rem;
    padding: 0.625rem 0.5rem;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 0.375rem;
    color: #94a3b8;
    font-size: 0.65rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-hover-preset-btn:hover {
    background: #334155;
    color: #e2e8f0;
    border-color: #475569;
}

/* Preset Demos */
.uve-hover-preset-demo {
    width: 24px;
    height: 24px;
    background: #475569;
    border-radius: 0.25rem;
    transition: all 0.3s ease;
}

.uve-hover-preset-btn:hover .uve-hover-preset-demo--opacity {
    opacity: 0.5;
}

.uve-hover-preset-btn:hover .uve-hover-preset-demo--scale {
    transform: scale(1.15);
}

.uve-hover-preset-btn:hover .uve-hover-preset-demo--shadow {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}

.uve-hover-preset-btn:hover .uve-hover-preset-demo--color {
    background: #e0ac7e;
}

.uve-hover-preset-btn:hover .uve-hover-preset-demo--lift {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
}

.uve-hover-preset-btn:hover .uve-hover-preset-demo--glow {
    box-shadow: 0 0 12px rgba(224, 172, 126, 0.6);
}

/* Compact Mode Adjustments */
.uve-control--compact .uve-hover-toggle {
    padding: 0.125rem;
}

.uve-control--compact .uve-hover-btn {
    padding: 0.375rem 0.5rem;
    font-size: 0.7rem;
}

.uve-control--compact .uve-hover-indicator {
    padding: 0.375rem 0.5rem;
}
</style>

<script>
function uveHoverStatesControl(initialState) {
    return {
        state: initialState || 'normal',
        previewHover: false,

        setState(newState) {
            this.state = newState;
            this.$wire.call('switchHoverState', newState);
        },

        togglePreviewHover() {
            this.$wire.dispatch('uve-preview-hover', { enabled: this.previewHover });
        },

        applyPreset(preset) {
            const presets = {
                opacity: {
                    transition: { property: 'opacity', duration: '200ms', timing: 'ease' },
                    hover: { opacity: '0.7' }
                },
                scale: {
                    transition: { property: 'transform', duration: '200ms', timing: 'ease' },
                    hover: { transform: 'scale(1.05)' }
                },
                shadow: {
                    transition: { property: 'box-shadow', duration: '200ms', timing: 'ease' },
                    hover: { boxShadow: '0 10px 25px rgba(0, 0, 0, 0.25)' }
                },
                color: {
                    transition: { property: 'background-color, color', duration: '200ms', timing: 'ease' },
                    hover: { backgroundColor: '#e0ac7e', color: '#0f172a' }
                },
                lift: {
                    transition: { property: 'transform, box-shadow', duration: '200ms', timing: 'ease' },
                    hover: { transform: 'translateY(-4px)', boxShadow: '0 8px 20px rgba(0, 0, 0, 0.2)' }
                },
                glow: {
                    transition: { property: 'box-shadow', duration: '300ms', timing: 'ease' },
                    hover: { boxShadow: '0 0 20px rgba(224, 172, 126, 0.5)' }
                }
            };

            const presetConfig = presets[preset];
            if (presetConfig) {
                this.$wire.call('applyHoverPreset', presetConfig);
                this.$wire.dispatch('notify', { type: 'success', message: `Preset "${preset}" zastosowany` });
            }
        }
    }
}
</script>
