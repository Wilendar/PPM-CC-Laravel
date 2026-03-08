{{--
    Preset Controls - Save/Load filter presets
    Used within filters-bar.blade.php
--}}
<div class="compat-preset-controls" x-data="{ open: false }">
    {{-- Preset Dropdown Toggle --}}
    <div class="compat-filter-item">
        <button
            @click="open = !open"
            class="compat-preset-btn"
            type="button"
        >
            <i class="fas fa-bookmark"></i>
            <span>Presety</span>
            @if($this->activePresetId)
                <span class="compat-preset-active-dot"></span>
            @endif
            <i class="fas fa-chevron-down compat-preset-chevron" :class="{ 'rotate-180': open }"></i>
        </button>

        {{-- Dropdown Panel --}}
        <div
            x-show="open"
            @click.outside="open = false"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            class="compat-preset-dropdown"
        >
            {{-- Saved Presets List --}}
            @forelse($this->presets ?? [] as $preset)
                <div class="compat-preset-item {{ $activePresetId === $preset->id ? 'compat-preset-item--active' : '' }}">
                    <button
                        wire:click="loadPreset({{ $preset->id }})"
                        class="compat-preset-item-name"
                        @click="open = false"
                    >
                        @if($preset->is_default)
                            <i class="fas fa-star compat-preset-default-icon"></i>
                        @endif
                        {{ $preset->name }}
                    </button>
                    <div class="compat-preset-item-actions">
                        <button
                            wire:click="setDefaultPreset({{ $preset->id }})"
                            class="compat-preset-action-btn"
                            title="{{ $preset->is_default ? 'Usun domyslny' : 'Ustaw jako domyslny' }}"
                        >
                            <i class="{{ $preset->is_default ? 'fas' : 'far' }} fa-star"></i>
                        </button>
                        <button
                            wire:click="deletePreset({{ $preset->id }})"
                            class="compat-preset-action-btn compat-preset-action-btn--danger"
                            title="Usun preset"
                        >
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            @empty
                <div class="compat-preset-empty">
                    <i class="fas fa-bookmark"></i>
                    <span>Brak zapisanych presetow</span>
                </div>
            @endforelse

            {{-- Save New Preset --}}
            <div class="compat-preset-save">
                <input
                    type="text"
                    wire:model="newPresetName"
                    placeholder="Nazwa presetu..."
                    class="compat-preset-save-input"
                    @keydown.enter="$wire.savePreset(); open = false;"
                />
                <button
                    wire:click="savePreset"
                    class="compat-preset-save-btn"
                    @click="open = false"
                >
                    <i class="fas fa-save"></i>
                </button>
            </div>
        </div>
    </div>
</div>
