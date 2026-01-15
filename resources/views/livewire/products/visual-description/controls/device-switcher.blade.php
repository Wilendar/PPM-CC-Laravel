{{--
    Device Switcher Control - ETAP_07f_P5 FAZA PP.4
    Toggle Desktop/Tablet/Mobile z wyswietlaniem aktualnych wymiarow
    Do umieszczenia w toolbarze edytora
--}}
@props([
    'currentDevice' => 'desktop',
    'compact' => false,
    'showDimensions' => true,
    'onChange' => null,
])

@php
    $devices = [
        'desktop' => [
            'label' => 'Desktop',
            'width' => '100%',
            'minWidth' => '1024px',
            'icon' => 'desktop',
        ],
        'tablet' => [
            'label' => 'Tablet',
            'width' => '768px',
            'minWidth' => '768px',
            'icon' => 'tablet',
        ],
        'mobile' => [
            'label' => 'Mobile',
            'width' => '375px',
            'minWidth' => '375px',
            'icon' => 'mobile',
        ],
    ];
@endphp

<div
    class="uve-device-switcher {{ $compact ? 'uve-device-switcher--compact' : '' }}"
    x-data="uveDeviceSwitcher(@js($currentDevice), @js($devices))"
    wire:ignore.self
>
    {{-- Device Buttons --}}
    <div class="uve-device-buttons">
        {{-- Desktop --}}
        <button
            type="button"
            @click="switchDevice('desktop')"
            class="uve-device-btn"
            :class="{ 'uve-device-btn--active': device === 'desktop' }"
            wire:click="switchDevice('desktop')"
            title="Desktop (>1024px)"
        >
            <svg class="uve-device-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                </path>
            </svg>
            @if(!$compact)
                <span class="uve-device-label">Desktop</span>
            @endif
        </button>

        {{-- Tablet --}}
        <button
            type="button"
            @click="switchDevice('tablet')"
            class="uve-device-btn"
            :class="{ 'uve-device-btn--active': device === 'tablet' }"
            wire:click="switchDevice('tablet')"
            title="Tablet (768px)"
        >
            <svg class="uve-device-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                </path>
            </svg>
            @if(!$compact)
                <span class="uve-device-label">Tablet</span>
            @endif
        </button>

        {{-- Mobile --}}
        <button
            type="button"
            @click="switchDevice('mobile')"
            class="uve-device-btn"
            :class="{ 'uve-device-btn--active': device === 'mobile' }"
            wire:click="switchDevice('mobile')"
            title="Mobile (375px)"
        >
            <svg class="uve-device-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z">
                </path>
            </svg>
            @if(!$compact)
                <span class="uve-device-label">Mobile</span>
            @endif
        </button>
    </div>

    {{-- Dimensions Display --}}
    @if($showDimensions && !$compact)
        <div class="uve-device-dimensions">
            <span class="uve-device-dimension-icon">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4">
                    </path>
                </svg>
            </span>
            <span class="uve-device-dimension-value" x-text="getDimensions()"></span>
        </div>
    @endif

    {{-- Breakpoint Info Badge (compact mode) --}}
    @if($compact)
        <div class="uve-device-badge" :class="'uve-device-badge--' + device">
            <span x-text="getBreakpointLabel()"></span>
        </div>
    @endif

    {{-- Responsive Indicator --}}
    @if(!$compact)
        <div class="uve-device-responsive-info">
            <div class="uve-device-breakpoint-bar">
                <div
                    class="uve-device-breakpoint-indicator"
                    :style="getIndicatorStyle()"
                ></div>
                <div class="uve-device-breakpoint-marks">
                    <span class="uve-device-breakpoint-mark" style="left: 0%">0</span>
                    <span class="uve-device-breakpoint-mark" style="left: 25%">375</span>
                    <span class="uve-device-breakpoint-mark" style="left: 50%">768</span>
                    <span class="uve-device-breakpoint-mark" style="left: 75%">1024</span>
                    <span class="uve-device-breakpoint-mark" style="left: 100%">1440</span>
                </div>
            </div>
            <p class="uve-device-hint">
                <span x-show="device === 'desktop'">Style bedzie widoczne na szerokich ekranach (>1024px)</span>
                <span x-show="device === 'tablet'">Style bedzie widoczne na tabletach (768px - 1024px)</span>
                <span x-show="device === 'mobile'">Style bedzie widoczne na telefonach (<768px)</span>
            </p>
        </div>
    @endif
</div>

<style>
/* Device Switcher Styles */
.uve-device-switcher {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.uve-device-switcher--compact {
    flex-direction: row;
    align-items: center;
    gap: 0.5rem;
}

/* Device Buttons */
.uve-device-buttons {
    display: flex;
    gap: 0.25rem;
    padding: 0.25rem;
    background: #1e293b;
    border-radius: 0.5rem;
    border: 1px solid #334155;
}

.uve-device-btn {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.75rem;
    background: transparent;
    border: none;
    border-radius: 0.375rem;
    color: #64748b;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-device-btn:hover {
    background: rgba(51, 65, 85, 0.5);
    color: #94a3b8;
}

.uve-device-btn--active {
    background: #e0ac7e;
    color: #0f172a;
}

.uve-device-btn--active:hover {
    background: #d19a6c;
    color: #0f172a;
}

.uve-device-icon {
    width: 1.125rem;
    height: 1.125rem;
}

.uve-device-label {
    font-size: 0.75rem;
    font-weight: 500;
}

/* Compact Mode */
.uve-device-switcher--compact .uve-device-buttons {
    padding: 0.125rem;
}

.uve-device-switcher--compact .uve-device-btn {
    padding: 0.375rem 0.5rem;
}

.uve-device-switcher--compact .uve-device-icon {
    width: 1rem;
    height: 1rem;
}

/* Dimensions Display */
.uve-device-dimensions {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.625rem;
    background: rgba(30, 41, 59, 0.5);
    border-radius: 0.375rem;
    border: 1px solid #334155;
}

.uve-device-dimension-icon {
    color: #64748b;
}

.uve-device-dimension-value {
    font-size: 0.75rem;
    font-family: monospace;
    color: #94a3b8;
}

/* Device Badge (compact) */
.uve-device-badge {
    padding: 0.125rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.65rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.uve-device-badge--desktop {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
}

.uve-device-badge--tablet {
    background: rgba(59, 130, 246, 0.2);
    color: #60a5fa;
}

.uve-device-badge--mobile {
    background: rgba(249, 115, 22, 0.2);
    color: #fb923c;
}

/* Responsive Info */
.uve-device-responsive-info {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.uve-device-breakpoint-bar {
    position: relative;
    height: 8px;
    background: #1e293b;
    border-radius: 4px;
    border: 1px solid #334155;
    overflow: visible;
}

.uve-device-breakpoint-indicator {
    position: absolute;
    top: -1px;
    height: 10px;
    background: #e0ac7e;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.uve-device-breakpoint-marks {
    position: relative;
    margin-top: 0.25rem;
}

.uve-device-breakpoint-mark {
    position: absolute;
    transform: translateX(-50%);
    font-size: 0.6rem;
    color: #64748b;
}

.uve-device-hint {
    font-size: 0.7rem;
    color: #64748b;
    margin: 0.375rem 0 0;
    padding-top: 0.75rem;
}

.uve-device-hint span {
    display: none;
}

.uve-device-hint span[style*=""] {
    display: inline;
}
</style>

<script>
function uveDeviceSwitcher(initialDevice, devices) {
    return {
        device: initialDevice || 'desktop',
        devices: devices,

        switchDevice(newDevice) {
            if (this.device === newDevice) return;

            this.device = newDevice;
            this.$wire.call('switchDevice', newDevice);

            // Dispatch event for iframe resize
            this.$wire.dispatch('device-changed', {
                device: newDevice,
                width: this.devices[newDevice].width,
                breakpoint: this.getBreakpoint()
            });
        },

        getDimensions() {
            const deviceConfig = this.devices[this.device];
            if (this.device === 'desktop') {
                return '100% width';
            }
            return deviceConfig.width;
        },

        getBreakpoint() {
            switch (this.device) {
                case 'mobile': return 'max-width: 767px';
                case 'tablet': return 'min-width: 768px and max-width: 1023px';
                case 'desktop': return 'min-width: 1024px';
                default: return '';
            }
        },

        getBreakpointLabel() {
            switch (this.device) {
                case 'mobile': return '<768px';
                case 'tablet': return '768px';
                case 'desktop': return '>1024px';
                default: return '';
            }
        },

        getIndicatorStyle() {
            switch (this.device) {
                case 'mobile':
                    return 'left: 0; width: 25%;';
                case 'tablet':
                    return 'left: 25%; width: 25%;';
                case 'desktop':
                    return 'left: 50%; width: 50%;';
                default:
                    return 'left: 50%; width: 50%;';
            }
        }
    }
}
</script>
